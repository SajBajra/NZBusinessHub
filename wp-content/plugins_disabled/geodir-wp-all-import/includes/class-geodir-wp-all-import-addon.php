<?php
function geodir_wp_all_import_get_import_post_type( $import_id = 'new' ) {
	global $wpdb;

	$custom_type = false;

	if ( $import_id == 'new' ) {
		$import_id = wp_all_import_get_import_id();
	}

	$imports_table = $wpdb->prefix . 'pmxi_imports';

	$import_options = $wpdb->get_row( $wpdb->prepare( "SELECT options FROM `{$imports_table}` WHERE id = %d", $import_id ), ARRAY_A );

	if ( ! empty( $import_options ) ) {
		$import_options_arr = geodir_wpai_maybe_unserialize( $import_options['options'] );
		$custom_type = $import_options_arr['custom_type'];
	} else {
		$import_options = $wpdb->get_row( $wpdb->prepare( "SELECT option_name, option_value FROM `{$wpdb->options}` WHERE option_name = %s", '_wpallimport_session_' . $import_id . '_' ), ARRAY_A );
		$import_options_arr = empty( $import_options ) ? array() : geodir_wpai_maybe_unserialize( $import_options['option_value'] );
		$custom_type = empty( $import_options_arr['custom_type'] ) ? '' : $import_options_arr['custom_type'];
	}

	return $custom_type;
}

function geodir_wpai_maybe_unserialize( $value ) {
	$value = maybe_unserialize( $value );

	if ( is_array( $value ) ) {
		return $value;
	}

	if ( geodir_wpai_is_base64( $value ) ) {
		$_value = maybe_unserialize( base64_decode( $value ) );

		if ( is_array( $_value ) ) {
			return $_value;
		}
	}

	return $value;
}

function geodir_wpai_is_base64( $s ) {
	if ( ! preg_match( '/^[a-zA-Z0-9\/+]*={0,2}$/', $s ) ) {
		return false;
	}

	$decoded = base64_decode( $s, true );

	if ( false === $decoded ) {
		return false;
	}

	if ( base64_encode( $decoded ) != $s ) {
		return false;
	}

	return true;
}

function geodir_wpai_setup_import() {
	global $wpdb, $geodir_wpai_addon, $geodir_wpai_post_type, $argv;

	if ( ! function_exists( 'wp_all_import_get_import_id' ) || ! function_exists( 'wp_all_import_get_import_post_type' ) ) {
		return;
	}

	// Used to retrieve the import options
	$import_id = wp_all_import_get_import_id();
	$post_type = wp_all_import_get_import_post_type( $import_id );

	if ( ! geodir_is_gd_post_type( $post_type ) ) {
		$post_type = geodir_wp_all_import_get_import_post_type( $import_id );
	}

	$geodir_wpai_post_type = $post_type;

	if ( ! geodir_is_gd_post_type( $post_type ) ) {
		return;
	}

	// @link https://github.com/soflyy/wp-all-import-rapid-addon
	$geodir_wpai_addon = new RapidAddon( __( 'GeoDirectory Add-On', 'geodir-wpai' ), 'geodir_wpai' );

	$geodir_wpai_addon->disable_default_images();

	// Prepare the import vars
	$table = geodir_db_cpt_table( $post_type );
	$fields = geodir_wpai_get_custom_fields( $post_type );
	$columns = array();

	// If the table exists, fetch its columns
	if ( $wpdb->get_var( "SHOW TABLES LIKE '$table'" ) == $table ) {
		$columns = $wpdb->get_col( "SHOW columns FROM `{$table}`" );
	}

	// Link Posts
	if ( class_exists( 'GeoDir_CP_Link_Posts' ) ) {
		$linked_post_types = GeoDir_CP_Link_Posts::linked_to_post_types( $post_type );

		if ( ! empty( $linked_post_types ) ) {
			$columns = array_merge( $columns, $linked_post_types );
		}
	}

	// Special fields
	$other_fields = array(
		'featured'          => __( 'Featured', 'geodir-wpai' ),
		'post_status'       => __( 'Post Status', 'geodir-wpai' ),
		'featured_image'    => __( 'Featured Image', 'geodir-wpai' ),
		'submit_ip'         => __( 'Submit IP', 'geodir-wpai' ),
		'overall_rating'    => __( 'Overall Rating', 'geodir-wpai' ),
		'rating_count'      => __( 'Rating Count', 'geodir-wpai' ),
		'ratings'           => __( 'Ratings', 'geodir-wpai' ),
		'marker_json'       => __( 'Marker JSON', 'geodir-wpai' ),
		'location_id'       => __( 'Location ID', 'geodir-wpai' ),
		'post_category'     => __( 'Categories', 'geodir-wpai' ),
		'default_category'  => __( 'Default Category', 'geodir-wpai' ),
		'post_tags'         => __( 'Tags', 'geodir-wpai' ),
	);

	if ( GeoDir_Post_types::supports( $post_type, 'events' )  ){
		$other_fields['rsvp_count']     = __( 'RSVP Count', 'geodir-wpai' );
	}

	$other_fields = apply_filters( 'geodir_wpai_import_other_fields', $other_fields );

	foreach ( $other_fields as $slug => $title ) {
		$field_type = apply_filters( 'geodir_wpai_import_other_field_type', 'text', $slug, $title, $other_fields );

		if ( in_array( $slug, $columns ) ) {
			$geodir_wpai_addon->add_field( $slug, $title, $field_type );
		}
	}

	$geodir_wpai_addon->add_field( 'post_images', __('Images (post_images)', 'geodir-wpai'), 'text' );

	// Display the other fields
	if ( is_array( $fields ) ) {
		foreach ( $fields as $field ) {
			// Maybe abort early
			if ( $field->htmlvar_name == "post_title" || $field->htmlvar_name == "post_content" || $field->htmlvar_name == "post_tags" || $field->htmlvar_name == "post_category" || $field->htmlvar_name == "post_images" ) {
				continue;
			}

			// Address fields
			if ( $field->field_type == "address" ) {
				$address_fields = array(
					'zip'           => __('Zip (zip)', 'geodir-wpai'),
					'street'        => __('Street (street)', 'geodir-wpai'),
					'street2'       => __('Street 2 (street2)', 'geodir-wpai'),
					'city'          => __('City (city)', 'geodir-wpai'),
					'region'        => __('Region (region)', 'geodir-wpai'),
					'country'       => __('Country (country)', 'geodir-wpai'),
					'neighbourhood' => __('Neighbourhood (neighbourhood)', 'geodir-wpai'),
					'latitude'      => __('Latitude (latitude)', 'geodir-wpai'),
					'longitude'     => __('Longitude (longitude)', 'geodir-wpai'),
					'mapview'       => __('Map View (mapview)', 'geodir-wpai'),
					'mapzoom'       => __('Map Zoom (mapzoom)', 'geodir-wpai'),
				);

				foreach( $address_fields as $slug => $title ) {
					if ( in_array( $slug, $columns ) ) {
						$geodir_wpai_addon->add_field( $slug, $title, 'text' );
					}
				}

				if ( in_array( 'mapview', $columns ) ) {
					$geodir_wpai_addon->add_field('mapview',  __('Map View (mapview)', 'geodir-wpai'), 'radio', array('ROADMAP' => 'ROADMAP', 'SATELLITE' => 'SATELLITE', 'HYBRID' => 'HYBRID', 'TERRAIN' => 'TERRAIN'));
				}
				continue;
			}

			// Event fields.
			if ( GeoDir_Post_types::supports( $post_type, 'events' ) && $field->htmlvar_name == "event_dates" ) {
				$post_type_name = geodir_post_type_singular_name( $post_type, true ) . ' ';

				$geodir_wpai_addon->add_field( 'start_date', $post_type_name . __( 'Start Date (start_date) Ex: yyyy-mm-dd', 'geodir-wpai' ), 'text' );
				$geodir_wpai_addon->add_field( 'end_date', $post_type_name . __( 'End Date (end_date) Ex: yyyy-mm-dd', 'geodir-wpai' ), 'text' );
				$geodir_wpai_addon->add_field( 'start_time', $post_type_name . __( 'Start Time (start_time) Ex: hh:mm', 'geodir-wpai' ), 'text' );
				$geodir_wpai_addon->add_field( 'end_time', $post_type_name . __( 'End Time (end_time) Ex: hh:mm', 'geodir-wpai' ), 'text' );
				$geodir_wpai_addon->add_field( 'is_all_day_event', $post_type_name . __( 'Is all day event? (is_all_day_event). Ex: 0 or 1', 'geodir-wpai' ), 'text' );
				$geodir_wpai_addon->add_field( 'recurring_duration_days', $post_type_name . __( 'Duration in Days (recurring_duration_days)', 'geodir-wpai' ), 'text' );
				$geodir_wpai_addon->add_field( 'recurring_type', $post_type_name . __( 'Repeat Type (recurring_type). One of from day, week, month, year, custom.', 'geodir-wpai' ), 'text' );
				$geodir_wpai_addon->add_field( 'recurring_interval', $post_type_name . __( 'Repeat Interval (recurring_interval)', 'geodir-wpai' ), 'text' );
				$geodir_wpai_addon->add_field( 'recurring_week_days', $post_type_name . __( 'Repeat Week Days (recurring_week_days). Ex: mon,sat', 'geodir-wpai' ), 'text' );
				$geodir_wpai_addon->add_field( 'recurring_week_numbers', $post_type_name . __( 'Repeat By Weeks (recurring_week_numbers). Ex: 2,3', 'geodir-wpai' ), 'text' );
				$geodir_wpai_addon->add_field( 'recurring_limit', $post_type_name . __( 'Max Repeat Limit (recurring_limit)', 'geodir-wpai' ), 'text' );
				$geodir_wpai_addon->add_field( 'recurring_end_date', $post_type_name . __( 'Repeat End Date (recurring_end_date) Ex: yyyy-mm-dd', 'geodir-wpai' ), 'text' );
				$geodir_wpai_addon->add_field( 'recurring_custom_dates', $post_type_name . __( 'Custom Recurring Dates (recurring_custom_dates) Ex: yyyy-mm-dd,yyyy-mm-dd', 'geodir-wpai' ), 'text' );
				$geodir_wpai_addon->add_field( 'recurring_different_times', $post_type_name . __( 'Recurring Different Times (recurring_different_times). Ex: 0 or 1', 'geodir-wpai' ), 'text' );
				$geodir_wpai_addon->add_field( 'recurring_start_times', $post_type_name . __( 'Recurring Start Times (recurring_start_times) Ex: hh:mm,hh:mm', 'geodir-wpai' ), 'text' );
				$geodir_wpai_addon->add_field( 'recurring_end_times', $post_type_name . __( 'Recurring End Times (recurring_end_times) Ex: hh:mm,hh:mm', 'geodir-wpai' ), 'text' );
				continue;
			}

			// Other fields
			if ( in_array( $field->htmlvar_name, $columns ) ) {
				$title = isset( $field->frontend_title ) ? $field->frontend_title : $field->admin_title;
				$geodir_wpai_addon->add_field( $field->htmlvar_name, "$title ($field->htmlvar_name)", 'text' );
			}
		}
	}

	$geodir_wpai_addon->set_import_function( 'geodir_wpai_import_function' );

	//Only run the addon on our post types
	$geodir_wpai_addon->run(
		array(
			"post_types" => apply_filters('geodir_wpai_post_types', geodir_get_posttypes() ),
		)
	);
}

/**
 * Imports a single post
 * 
 * @param $post_id the id of the post being imported
 * @param $data the data to import
 * @param $import_options 
 */
function geodir_wpai_import_function( $post_id, $data, $import_options, $_post, $logger ) {
	global $geodir_wpai_addon, $wpdb, $geodir_wpai_post_type, $geodirectory;

	$uploads           = wp_upload_dir();
	$post              = get_post( $post_id );
	$post_type         = get_post_type( $post_id );
	$post_type         = ! empty( $post_type ) ? $post_type : ( $geodir_wpai_post_type ? $geodir_wpai_post_type : 'gd_place' );
	$fields            = geodir_wpai_get_custom_fields( $post_type );
	$table             = geodir_db_cpt_table( $post_type );
	$custom_field_data = array();
	$columns           = array();
	$event_cf          = false;
	$address_cf        = false;
	$is_new            = empty( $_post['ID'] ) ? true : false;

    //If the table exists, fetch columns
    if ( $wpdb->get_var( "SHOW TABLES LIKE '$table'" ) == $table ) {
        $columns = $wpdb->get_col( "show columns from $table" );
    }

	// Link Posts
	if ( class_exists( 'GeoDir_CP_Link_Posts' ) ) {
		$linked_post_types = GeoDir_CP_Link_Posts::linked_to_post_types( $post_type );

		if ( ! empty( $linked_post_types ) ) {
			$columns = array_merge( $columns, $linked_post_types );
		}
	}

    /**
     * Fires before running a new import
     */
    do_action( 'geodir_wpai_before_import_fields', $post_id, $data, $import_options );

    call_user_func( $logger, __( 'DO ACTION `geodir_wpai_before_import_fields`...', 'geodir-wpai' ) );

    $data = apply_filters( 'geodir_wpai_import_data', $data, $post_id, $import_options, $geodir_wpai_addon, $is_new );

    $file_fields = array();

    // Set the custom fields info
    foreach ( $fields as $cf ) {
        if ( in_array( $cf->htmlvar_name, array(
            'post_title',
            'post_content',
            'post_tags',
            'post_category',
            'post_images'
        ) ) ) {
            continue;
        }

        if ( $cf->htmlvar_name == 'event_dates' ) {
            $event_cf = $cf;
        }

        if ( $cf->field_type == "address" ) {
            $address_cf = $cf;
        } elseif( $cf->field_type == "file" ) {
            $file_fields[] = $cf->htmlvar_name;
        }

        // Abort early if the field does not exist in our CPT table...
        if ( ! in_array( $cf->htmlvar_name, $columns ) || ! isset( $data[ $cf->htmlvar_name ] ) ) {
            continue; //Note: event dates and addresses will fail this check
        }

        // Don't update if disabled.
        if ( 'post_category' == $cf->htmlvar_name && ! ( $is_new || $geodir_wpai_addon->can_update_taxonomy( $post_type . 'category', $import_options ) ) ) {
            continue;
        } else if ( 'post_tags' == $cf->htmlvar_name && ! ( $is_new || $geodir_wpai_addon->can_update_taxonomy( $post_type . '_tags', $import_options ) ) ) {
            continue;
        } else if ( 'post_images' == $cf->htmlvar_name && ! ( $is_new || $geodir_wpai_addon->can_update_image( $import_options ) ) ) {
            continue;
        } else if ( ! ( $is_new || $geodir_wpai_addon->can_update_meta( $cf->htmlvar_name, $import_options ) ) ) {
            continue;
        }

        // The value of this custom field
        $field_value = $data[ $cf->htmlvar_name ];

        // check for empty numbers and set to NULL so a default 0 or 0.00 is not set
        if ( isset( $cf->data_type ) && ( $cf->data_type == 'DECIMAL' || $cf->data_type == 'INT' ) && $field_value === '' ) {
            $field_value = null;
        }

        // Prepare checkboxes
        if ( 'checkbox' == $cf->field_type ) {
            $field_value = empty( $field_value ) ? 0 : 1;
        }

        $field_value = apply_filters( "geodir_custom_field_value_{$cf->field_type}", $field_value, $data, $cf, $post_id, $post, false );

        if ( is_array( $field_value ) ) {
            $field_value = implode( ',', $field_value );
        }

        if ( ! empty( $field_value ) ) {
            $field_value = stripslashes_deep( $field_value ); // stripslashes
        }

        $custom_field_data[ $cf->htmlvar_name ] = $field_value;
    }

	// Event data.
	if ( ( $is_new || $geodir_wpai_addon->can_update_meta( 'event_dates', $import_options ) ) && GeoDir_Post_types::supports( $post_type, 'events' ) && $event_cf && in_array( 'event_dates', $columns ) && class_exists( 'GeoDir_Event_Fields' ) ) {
		$event_data = $data;
		$event_data['ID'] = $post_id;
		$event_data['post_type'] = $post_type;
		$event_data = GeoDir_Event_Admin_Import_Export::import_validate_post( $event_data, $is_new );

		$event_fields = ! empty( $event_data['event_dates'] ) ? $event_data['event_dates'] : $event_data;
		$event_fields['all_day'] = empty( $event_fields['all_day'] ) ? 0 : 1;
		$event_fields['recurring'] = ! empty( $custom_field_data['recurring'] );

		if ( ! empty( $event_fields['repeat_type'] ) && $event_fields['repeat_type'] == 'custom' && ! empty( $event_fields['recurring_dates'] ) )
		$parse_dates = GeoDir_Event_Fields::parse_array( $event_fields['recurring_dates'] );
		$recurring_dates = array();
		if ( ! empty( $parse_dates ) ) {
			foreach ( $parse_dates as $key => $date ) {
				if ( ! empty( $date ) ) {
					$recurring_dates[] = GeoDir_Event_Admin_Import_Export::parse_import_date( $date );
				}
			}
		}
		$event_fields['recurring_dates'] = $recurring_dates;

		// sanitize event data.
		$custom_field_data['event_dates'] = GeoDir_Event_Fields::sanitize_event_data( $event_fields, $event_fields, $event_cf, $post_id, $post, false );

		// Save event schedules.
		GeoDir_Event_Schedules::save_schedules( $custom_field_data['event_dates'], $post_id );

		call_user_func( $logger, __( 'Saved event dates', 'geodir-wpai' ) );
	}

    //Addresses
    if ( ! empty( $address_cf ) ) {
        $address_fields   = array(
            'zip',
            'street',
            'street2',
            'city',
            'region',
            'country',
            'neighbourhood',
            'latitude',
            'longitude',
            'mapview',
            'mapzoom',
            'mapview'
        );
        $default_location = (array) $geodirectory->location->get_default_location();

		if ( ! empty( $data['latitude'] ) ) {
			$data['latitude'] = geodir_sanitize_float( $data['latitude'] );
		}

		if ( ! empty( $data['longitude'] ) ) {
			$data['longitude'] = geodir_sanitize_float( $data['longitude'] );
		}

		$_address_fields = array();
		foreach ( $address_fields as $address_field ) {
			if ( in_array( $address_field, $columns ) && ( $is_new || $geodir_wpai_addon->can_update_meta( $address_field, $import_options ) ) ) {
				// If Lat or Long are empty, find them from geocoding.
				if ( empty( $data['latitude'] ) || empty( $data['longitude'] ) ) {
					if ( empty( $res_data ) && GeoDir_Post_types::supports( $post_type, 'location' ) ) {
						// Fill in the GPS info from address if missing
						$res_data = GeoDir_Admin_Import_Export::get_post_gps_from_address( $data );

						if ( is_array( $res_data ) && ! empty( $res_data['latitude'] ) && ! empty( $res_data['longitude'] ) ) {
							$data['latitude'] = geodir_sanitize_float( $res_data['latitude'] );
							$data['longitude'] = geodir_sanitize_float( $res_data['longitude'] );
						}
					}
				}

				if ( ! empty( $data[ $address_field ] ) ) {
					$custom_field_data[ $address_field ] = $data[ $address_field ];
				} elseif ( ! empty( $default_location[ $address_field ] ) ) {
					$custom_field_data[ $address_field ] = $default_location[ $address_field ];
				} else {
					$custom_field_data[ $address_field ] = '';
				}
				$_address_fields[] = $address_field;
			}
		}

        if ( ! empty( $_address_fields ) ) {
            call_user_func( $logger, wp_sprintf( __( '- Importing address fields: `%s` ...', 'geodir-wpai' ), implode( ', ', $_address_fields ) ) );
        }

        //Special fields
        $fields = array(
            'featured',
            'post_status',
            'submit_ip',
            'overall_rating',
            'rating_count',
            'ratings',
            'post_dummy',
            'marker_json',
            'location_id',
            'default_category',
            'post_category',
            'post_tags'
        );

        if ( GeoDir_Post_types::supports( $post_type, 'events' ) ) {
            $fields[] = 'rsvp_count';
        }

        $fields = apply_filters( 'geodir_wpai_import_other_fields', $fields, $post_id, $data, $import_options );

        foreach ( $fields as $field ) {
            if ( in_array( $field, $columns ) && ! empty( $data[ $field ] ) ) {
                if ( ( 'post_category' == $field || 'default_category' == $field ) && ! ( $is_new || $geodir_wpai_addon->can_update_taxonomy( $post_type . 'category', $import_options ) ) ) {
                    continue;
                } else if ( 'post_tags' == $field && ! ( $is_new || $geodir_wpai_addon->can_update_taxonomy( $post_type . '_tags', $import_options ) ) ) {
                    continue;
                } else if ( ! ( $is_new || $geodir_wpai_addon->can_update_meta( $field, $import_options ) ) ) {
                    continue;
                }

                // Import categories
                if ( 'post_category' == $field || 'default_category' == $field ) {
                    $custom_field_data[ $field ] = geodir_wpai_get_categories( $data[ $field ], $post_type );
                    continue;
                }

                $custom_field_data[ $field ] = $data[ $field ];
            }
        }

        if ( $is_new || $geodir_wpai_addon->can_update_meta( 'post_title', $import_options ) ) {
            $custom_field_data['post_title'] = $post->post_title;
        }

        // Correct country name.
        if ( ! empty( $custom_field_data['country'] ) ) {
            $req_country = geodir_strtolower( $custom_field_data['country'] );

            if ( $req_country == 'usa' || $req_country == 'us' ) {
                $custom_field_data['country'] = 'United States';
            } else if ( $req_country == 'can' || $req_country == 'ca' ) {
                $custom_field_data['country'] = 'Canada';
            }
        }

        // Business hours
        if ( isset( $custom_field_data['business_hours'] ) && class_exists( 'GeoDir_Adv_Search_Business_Hours' ) && ( $is_new || $geodir_wpai_addon->can_update_meta( 'business_hours', $import_options ) ) && GeoDir_Post_types::supports( $post_type, 'business_hours' ) ) {
            if ( ! empty( $custom_field_data['country'] ) ) {
                $country = $custom_field_data['country'];
            } elseif ( GeoDir_Post_types::supports( $post_type, 'location' ) ) {
                $country = geodir_get_post_meta( $post_id, 'country', true );
            } else {
                $country = geodir_get_option( 'default_location_country' );
            }

            GeoDir_Adv_Search_Business_Hours::save_post_business_hours( $post_id, $custom_field_data['business_hours'], $country );

            call_user_func( $logger, wp_sprintf( __( '- Importing `%s` ...', 'geodir-wpai' ), 'business_hours' ) );
        }

        $temp_file_name = '';

        // Featured images
        if ( isset( $data['featured_image'] ) && ! empty( $data['featured_image'] ) && ( $is_new || $geodir_wpai_addon->can_update_image( $import_options ) )  ) {
            $post_image = $data['featured_image'];
            $is_custom_image = ( strpos( $post_image, ".googleusercontent.com/" ) !== false || strpos( $post_image, "/thumbnail?" ) !== false || strpos( $post_image, "/watermark?" ) !== false ) ? true : false;
            $is_custom_image = apply_filters( 'geodir_wpai_is_custom_image', $is_custom_image, $post_image, $data, $post_id, 'featured_image', $import_options, $geodir_wpai_addon, $is_new );

            // Allow external images with custom url .googleusercontent.com, /thumbnail?, /watermark?.
            if ( $is_custom_image ) {
                $temp_file_name = sanitize_file_name( geodir_sanitize_keyword( get_the_title( $post_id ) ) );
                $image_url = $uploads['basedir'] . '/geodir_temp/' . $post_id . ' ' . $temp_file_name . '.jpg';

                if ( $response = wp_remote_get( $data['featured_image'], array( 'timeout' => 300, 'stream' => true, 'filename' => $image_url ) ) ) {
                    if ( ! empty( $response ) && ! is_wp_error( $response ) && 200 == wp_remote_retrieve_response_code( $response ) ) {
                        $data['featured_image'] = str_replace( $uploads['basedir'], $uploads['baseurl'], $image_url );
                    }
                }
            }

            $featured_image = GeoDir_Post_Data::save_files( $post_id, $data['featured_image'], 'post_images', false, false );

            if ( ! empty( $featured_image ) && ! wp_is_post_revision( absint( $post_id ) ) ) {
                $custom_field_data['featured_image'] = $featured_image;
            }
        }

        // Post images
        if ( isset( $data['post_images'] ) && ! empty( $data['post_images'] ) && ( $is_new || $geodir_wpai_addon->can_update_image( $import_options ) ) ) {
            $post_images = explode( "::", $data['post_images'] );
            $_post_images = array();
            

            foreach ( $post_images as $k => $post_image ) {
                $is_custom_image = ( strpos( $post_image, ".googleusercontent.com/" ) !== false || strpos( $post_image, "/thumbnail?" ) !== false || strpos( $post_image, "/watermark?" ) !== false ) ? true : false;
                $is_custom_image = apply_filters( 'geodir_wpai_is_custom_image', $is_custom_image, $post_image, $data, $post_id, 'post_images', $import_options, $geodir_wpai_addon, $is_new );

                // Allow external images with custom url .googleusercontent.com, /thumbnail?, /watermark?.
                if ( $is_custom_image ) {
                    if ( empty( $temp_file_name ) ) {
                        $temp_file_name = sanitize_file_name( geodir_sanitize_keyword( get_the_title( $post_id ) ) );
                    }

                    $image_url = $uploads['basedir'] . '/geodir_temp/' . $post_id . ' ' . $temp_file_name . ( $k > 0 ? ' ' . $k : '' ) . '.jpg';

                    if ( $response = wp_remote_get( $post_image, array( 'timeout' => 300, 'stream' => true, 'filename' => $image_url ) ) ) {
                        if ( ! empty( $response ) && ! is_wp_error( $response ) && 200 == wp_remote_retrieve_response_code( $response ) ) {
                            $post_image = str_replace( $uploads['basedir'], $uploads['baseurl'], $image_url );
                        }
                    }
                }

                $_post_images[] = $post_image;
            }
            $data['post_images'] = implode( "::", $_post_images );

            $save_post_images = GeoDir_Post_Data::save_files( $post_id, $data['post_images'], 'post_images', false, false );

            if ( ! empty( $save_post_images ) && ! wp_is_post_revision( absint( $post_id ) ) ) {
                $custom_field_data['featured_image'] = $save_post_images;
            }
        }

        // File fields
        if ( ! empty( $file_fields ) ) {
            foreach ( $file_fields as $file_field ) {
                if ( isset( $data[ $file_field ] ) && ! empty( $data[ $file_field ] ) ) {
                    if ( 'post_images' == $file_field && ! ( $is_new || $geodir_wpai_addon->can_update_image( $import_options ) ) ) {
                        continue;
                    } else if ( ! ( $is_new || $geodir_wpai_addon->can_update_meta( $file_field, $import_options ) ) ) {
                        continue;
                    }

                    $post_files = explode( "::", $data[ $file_field ] );
                    $_post_files = array();
                    

                    foreach ( $post_files as $k => $post_file ) {
                        $is_custom_image = ( strpos( $post_file, ".googleusercontent.com/" ) !== false || strpos( $post_file, "/thumbnail?" ) !== false || strpos( $post_file, "/watermark?" ) !== false ) ? true : false;
                        $is_custom_image = apply_filters( 'geodir_wpai_is_custom_image', $is_custom_image, $post_file, $data, $post_id, $file_field, $import_options, $geodir_wpai_addon, $is_new );

                        // Allow external images with custom url .googleusercontent.com, /thumbnail?, /watermark?.
                        if ( $is_custom_image ) {
                            if ( empty( $temp_file_name ) ) {
                                $temp_file_name = sanitize_file_name( geodir_sanitize_keyword( get_the_title( $post_id ) ) );
                            }

                            $post_file_url = $uploads['basedir'] . '/geodir_temp/' . $post_id . ' ' . $temp_file_name . ( $k > 0 ? ' ' . $k : '' ) . '.jpg';

                            if ( $response = wp_remote_get( $post_file, array( 'timeout' => 300, 'stream' => true, 'filename' => $post_file_url ) ) ) {
                                if ( ! empty( $response ) && ! is_wp_error( $response ) && 200 == wp_remote_retrieve_response_code( $response ) ) {
                                    $post_file = str_replace( $uploads['basedir'], $uploads['baseurl'], $post_file_url );
                                }
                            }
                        }

                        $_post_files[] = $post_file;
                    }
                    $data[ $file_field ] = implode( "::", $_post_files );

                    GeoDir_Post_Data::save_files( $post_id, $data[ $file_field ], $file_field, false, false );
                    $custom_field_data[ $file_field ] = GeoDir_Media::get_field_edit_string( $post_id, $file_field );
                }
            }
        }

		// Link Posts
		$linked_post_types = class_exists( 'GeoDir_CP_Link_Posts' ) ? GeoDir_CP_Link_Posts::linked_to_post_types( $post_type ) : array();

		// Sanitize link posts.
		if ( ! empty( $linked_post_types ) && ! empty( $custom_field_data ) ) {
			foreach ( $linked_post_types as $linked_post_type ) {
				if ( ! array_key_exists( $linked_post_type, $custom_field_data ) ) {
					continue;
				}

				if ( empty( $custom_field_data[ $linked_post_type ] ) ) {
					unset( $custom_field_data[ $linked_post_type ] );
					continue;
				}

				$cf = geodir_get_field_infoby( 'htmlvar_name', $linked_post_type, $post_type );

				if ( ! empty( $cf ) ) {
					$custom_field_data[ $linked_post_type ] = GeoDir_CP_Link_Posts::sanitize_link_posts_data( $custom_field_data[ $linked_post_type ], $post, $cf, $post_id, $post, ! $is_new );
				}
			}
		}

        $custom_field_data = apply_filters( 'geodir_wpai_custom_field_data', $custom_field_data, $post_id, $data );

		// Save link posts.
		if ( ! empty( $linked_post_types ) && ! empty( $custom_field_data ) ) {
			foreach ( $linked_post_types as $linked_post_type ) {
				if ( array_key_exists( $linked_post_type, $custom_field_data ) ) {
					$custom_field_data = GeoDir_CP_Link_Posts::save_post_data( $custom_field_data, $post, $post, ! $is_new );
					break;
				}
			}
		}

		if ( ! empty( $custom_field_data ) ) {
			$wpdb->update( $table, $custom_field_data, array( 'post_id' => $post_id ) );

			// Maybe import the new location
			if ( GeoDir_Post_types::supports( $post_type, 'location' ) ) {
				if ( $is_new || ( $geodir_wpai_addon->can_update_meta( 'city', $import_options ) && $geodir_wpai_addon->can_update_meta( 'region', $import_options ) && $geodir_wpai_addon->can_update_meta( 'country', $import_options ) ) ) {
					$post_data = geodir_wpai_maybe_import_location( $custom_field_data, $post_type, $post_id, $post );
				}
			}
		}

		do_action( 'geodir_wpai_after_import_fields', $post_id, $data, $import_options );
    }
}

/**
 * Retrieves a CPTs custom fields
 */
function geodir_wpai_get_custom_fields( $post_type = 'gd_place' ) {
	global $wpdb;

    $fields = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM " . GEODIR_CUSTOM_FIELDS_TABLE . " WHERE post_type = %s ORDER BY sort_order ASC", array( $post_type ) ) );

    return apply_filters('geodir_wpai_custom_fields', $fields, $post_type);
}

/**
 * Saves a new location to the database in case the location manager plugin is installed
 */
function geodir_wpai_maybe_import_location( $post_data, $post_type, $post_id, $post ) {
	global $geodir_location_manager;

	if ( ! ( ! empty( $geodir_location_manager ) && is_object( $geodir_location_manager ) ) ) {
		return;
	}

	$_post_data = $geodir_location_manager->save_location_data( $post_data, $post_data, $post, true );

	if ( ! empty( $post_data['neighbourhood'] ) && ! empty( $_post_data['neighbourhood'] ) && $post_data['neighbourhood'] !== $_post_data['neighbourhood'] && GeoDir_Location_Neighbourhood::is_active() ) {
		geodir_save_post_meta( $post_id, 'neighbourhood', $_post_data['neighbourhood'] );
	}

	return $_post_data;
}

/**
 * Fetch categories
 */
function geodir_wpai_get_categories( $categories, $post_type ) {
	$modified   = array();

	if ( is_scalar( $categories ) ) {
		$categories = preg_split( "/\,(?![^(]+\))/", $categories );
	}

	foreach( $categories as $category ) {
		//If this is an id import it as is
		if ( is_numeric( $category ) ) {
			$modified[] = $category;
			continue;
		}

		// Else fetch the category id
		$cat = get_term_by( 'name', $category, $post_type . 'category' );
		if ( $cat ) {
			$modified[] = $cat->term_id;
			continue;
		}

		// If it don't exist, create it
		// We will never get here unless the user forgets to instruct WPAI to import categories
		$cat = wp_insert_term( $category, $post_type . 'category' );
		if ( is_array( $cat ) ) {
			$modified[] = $cat['term_id'];
		}
	}

	return implode( ',', array_unique( $modified ) );
}