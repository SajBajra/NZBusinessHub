<?php

// Check Social_Importer_General class exists or not.
if( ! class_exists( 'Social_Importer_General' ) ) {

	/**
	 * Social_Importer_General Class for import actions.
	 *
	 * @since 2.0.0
	 *
	 * Class Social_Importer_General
	 */
	class Social_Importer_General {

		/**
		 * Constructor.
		 *
		 * @since 2.0.0
		 *
		 * Social_Importer_General constructor.
		 */
		public function __construct() {

		}

		/**
		 * Get Remote request response by request url.
		 *
		 * @since 2.0.0
		 *
		 * @param string $url Get request url.
		 *
		 * @return array|bool|string $response
		 */
		public function get_remote_response( $url, $cookies = false ) {
			global $gdfi_cookies, $gdfi_last_call;


			// introduce a delay
			if(empty($gdfi_last_call)){
				$gdfi_last_call = time();
			}else{
				sleep(2);
				$gdfi_last_call = time();
			}


			$response = '';

			if ( empty( $url ) ) {
				return $response;
			}

			$parsed_url = wp_parse_url( $url );

			// Validate url.
			if ( ! empty( $parsed_url ) && is_array( $parsed_url ) && ! empty( $parsed_url['host'] ) ) {
				// Get page content using page url.
				$args = array( 'timeout' => 20 );

				if ( ! empty( $cookies ) && is_array( $cookies ) ) {
					$args['cookies'] = $cookies;
				}

				$html_response = wp_remote_get( $url, $args );

				$gdfi_cookies = array();
				if ( ! empty( $html_response ) && is_array( $html_response ) && ! empty( $html_response['cookies'] ) ) {
					foreach ( $html_response['cookies'] as $cookie ) {
						$gdfi_cookies[ $cookie->name ] = $cookie->value;
					}
				}

				if ( is_wp_error( $html_response ) ) {
					return $response;
				}

				if ( is_array( $html_response ) && ! empty( $html_response['response']['code'] ) && $html_response['response']['code'] == 200 ) {
					$response = $html_response['body'];
				}
			}

			return $response;
		}

		/**
		 * Get page data using url, start position and end position.
		 *
		 * @since 2.0.0
		 *
		 * @param string $page_html Get selected page html.
		 * @param string $stat_position Get page start position.
		 * @param string $end_position Get page end position.
		 *
		 * @return bool|string $response
		 */
		public function get_page_data( $page_html, $stat_position = "", $end_position = "") {

			// Check page html is empty or not. If html is empty then return.
			if( empty( $page_html ) ) {
				return false;
			}

			$response ='';

			// Page html data explode using start and end position and get value.
			if( !empty( $page_html ) && $page_html !='' ) {

				if( !empty( $page_html ) &&  $page_html !='' ) {

					$explode_html = explode($stat_position, $page_html);

					if ( !empty( $explode_html[1] ) && isset($explode_html[1]) ) {

						$explode_html = !empty( $explode_html[1] ) ? explode($end_position, $explode_html[1]) : '';

						$response = !empty( $explode_html[0] ) ? $explode_html[0] : '';
					}

				}

			}

			return $response;

		}

		/**
		 * Get google meta tag values by page url.
		 *
		 * @since 2.0.0
		 *
		 * @param string $page_html Get selected page html.
		 *
		 * @return array|bool $response
		 */
		public function get_og_meta_tags_values( $page_html, $filter_name = false ) {
			if ( empty( $page_html ) ) {
				return false;
			}

			$response = array();

			if ( ! empty( $page_html ) && $page_html !='' ) {
				$doc = new DOMDocument();
				@$doc->loadHTML( $page_html );

				$metas = $doc->getElementsByTagName( 'meta' );

				for ( $i = 0; $i < $metas->length; $i++ ) {
					$meta = $metas->item($i);

					if($meta->getAttribute('property') == 'og:title') {
						$response['title'] = $meta->getAttribute('content');
					}

					if($meta->getAttribute('property') == 'og:description') {
						$response['description'] = $meta->getAttribute('content');
					}

					if($meta->getAttribute('property') == 'og:type') {
						$response['type'] = $meta->getAttribute('content');
					}

					if($meta->getAttribute('property') == 'og:image') {
						$response['image'] = $meta->getAttribute('content');
					}
				}

				try {
					// Schema
					$domxpath = new DOMXpath( $doc );
					$jsonScripts = $domxpath->query( '//script[@type="application/ld+json"]' );

					if ( ! empty( $jsonScripts ) ) {
						if ( $filter_name && ! empty( $jsonScripts->length ) ) {
							$values = array();

							for ( $n = 0; $n <= (int) $jsonScripts->length; $n++ ) {
								$item = $jsonScripts->item( $n );

								if ( ! empty( $item->nodeValue ) ) {
									$item_data = json_decode( trim( $item->nodeValue ) );

									if ( ! empty( $item_data ) && is_object( $item_data ) && ! empty( $item_data->name ) ) {
										if ( ! empty( $item_data->address ) && ! empty( $item_data->priceRange ) ) {
											$values = (array) $item_data;
											break;
										} else if ( ! empty( $item_data->address ) && ! empty( $item_data->url ) ) {
											$values = (array) $item_data;
											break;
										} else if ( ! empty( $item_data->image ) && ! empty( $item_data->url ) ) {
											$values = (array) $item_data;
											break;
										}
									}
								}
							}

							$response['schema'] = $values;
						} else {
							$item = $jsonScripts->item(0);
							$json = ! empty( $item ) ? trim( $item->nodeValue ) : array();
							$response['schema'] = ! empty( $json ) ? json_decode( $json ) : array();
						}
					}
				} catch ( Exception $e ) {
				}
			}

			return $response;
		}

        /**
         * Get address detail information using address.
         *
         * @since 2.0.0
         *
         * @param string $address Get address.
         *
         * @return array|bool $response
         */
        public function get_address_response( $address ) {

            if( empty( $address ) ) {
                return false;
            }

            $key = geodir_get_option( 'google_maps_api_key' );

			if ( empty( $key ) ) {
				return false;
			}

            $address = str_replace(' ','+',$address);

            $url = 'https://maps.google.com/maps/api/geocode/json?key='.$key.'&address='.$address;

            $address_response = wp_remote_get( $url, array( 'timeout' => 15 ) );

            $response = array();

            if( !empty( $address_response ) && 200 == $address_response['response']['code'] ) {

                $address_decode = !empty($address_response['body']) ? json_decode($address_response['body']) : '';

                if( !empty( $address_decode->results[0]->address_components ) && $address_decode->results[0]->address_components !='' ) {

                    foreach ( $address_decode->results[0]->address_components as $key => $values ) {

                        if( !empty( $values->types[0] ) && 'locality' === $values->types[0] ) {

                            $response['locality'] = !empty( $values->long_name ) ? $values->long_name :'';

                        }

                        if( !empty( $values->types[0] ) && 'administrative_area_level_1' === $values->types[0] ) {

                            $response['state_name'] = !empty( $values->long_name ) ? $values->long_name :'';;
                            $response['state_code'] = !empty( $values->short_name ) ? $values->short_name :'';;

                        }

                        if( !empty( $values->types[0] ) && 'country' === $values->types[0] ) {

                            $response['country_name'] = !empty( $values->long_name ) ? $values->long_name :'';
                            $response['country_code'] = !empty( $values->short_name ) ? $values->short_name :'';

                        }

                        if( !empty( $values->types[0] ) && 'postal_code' === $values->types[0] ) {

                            $response['postal_code'] = !empty( $values->long_name ) ? $values->long_name :'';

                        }

                    }

                }

                $response['latitude'] = !empty( $address_decode->results[0]->geometry->location->lat ) ? $address_decode->results[0]->geometry->location->lat :'';
                $response['longitude'] = !empty( $address_decode->results[0]->geometry->location->lng ) ? $address_decode->results[0]->geometry->location->lng :'';

            }

            return $response;

        }

		/**
		 * Convert time into 12h to 24h format.
		 *
		 * @since 2.0.0
		 *
		 * @param string $time Get time.
		 *
		 * @return bool|string return converted time.
		 */
		public function convert_time_in_24h_format( $time ) {

			if( empty( $time ) ) {
				return false;
			}

			return date("H:i", strtotime( $time ) );

		}

		/**
		 * Get week sort name using week name.
		 *
		 * @since 2.0.0
		 *
		 * @param string $week_name Week full name.
		 *
		 * @return bool|string Week sort name.
		 */
		public function get_week_sort_name( $week_name ) {

			if( empty( $week_name ) ) {
				return false;
			}

			$week = array(
				'Monday' => 'Mo',
				'Tuesday' => 'Tu',
				'Wednesday' => 'We',
				'Thursday' => 'Th',
				'Friday' => 'Fr',
				'Saturday' => 'Sa',
				'Sunday' => 'Su',
			);

			return !empty( $week_name )  ? $week[$week_name] :'';

		}

		/**
		 * Convert business hour from array to string separate by week.
		 *
		 * @since 2.0.0
		 *
		 * @param array $business_hours Get business hours.
		 *
		 * @return string $bh_hidden_val
		 */
		public function get_business_hours_hidden_time( $business_hours ) {

			$bh_hidden_val ='';

			$weeks = array('Mo','Tu','We','Th','Fr','Sa','Su',);

			$hidden_arr = array();

			foreach ( $weeks as $week_val ) {

				$hour_arr = !empty( $business_hours[$week_val] ) ? $business_hours[$week_val] :'';

				$temp_arr = array();
				if( !empty( $hour_arr ) && $hour_arr !='' ) {

					foreach ( $hour_arr as $hour_value ) {
						$temp_arr[] = $hour_value['open'].'-'.$hour_value['close'];
					}
				}

				if( !empty( $temp_arr ) && is_array( $temp_arr ) ) {

					$hidden_arr[] = '"'.$week_val.' '.implode( ',', $temp_arr).'"';

				}
			}

			$bh_hidden_val = !empty( $business_hours ) ? implode( ',', $hidden_arr) :'';

			return $bh_hidden_val;

		}

		/**
		 * Get listing post uploaded image path using images array.
		 *
		 * @since 2.0.0
		 *
		 * @param array $images Get images array.
		 *
		 * @return bool|string $images_path
		 */
		public function get_post_images_path( $images = array() ) {
			if ( empty( $images ) && "" == $images ) {
				return false;
			}

			$wp_upload_dir = wp_upload_dir();

			$images_path = '';

			if ( ! empty( $images ) && $images != '' ) {
				$temp_array = array();

				foreach ( $images as $img_key => $image ) {
					$title = ! empty( $image['title'] ) ? $image['title'] : '';
					$temp_array[] = $wp_upload_dir['baseurl'] . $image['file'] . "|" . $image['ID'] . "|" . $title . "|";
				}

				$images_path = ! empty( $temp_array ) ? implode( '::', $temp_array ) : '';
			}

			return $images_path;
		}

		/**
		 * Get Default selected city location.
		 *
		 * @since 2.0.0
		 *
		 * @return string
		 */
		public function get_default_city_location() {

			global $geodirectory;

			$default_location = $geodirectory->location->get_default_location();

			return !empty( $default_location->city ) ? $default_location->city : '';

		}

        /**
         * Check custom fields is available.
         *
         * @since 2.0.0
         *
         * @param string $post_type get listing post type.
         * @param string $field_key get post type custom field key.
         *
         * @return bool $is_logo
         */
		public function check_cf_key_available( $field_key, $post_type = 'gd_place') {

            $is_logo = false;

		    if( empty($field_key)) {
		        return $is_logo;
            }

            $post_cf_fields = geodir_post_custom_fields( '', 'all', $post_type, 'none' );

            if( !empty($post_cf_fields) && '' != $post_cf_fields ) {
                foreach ( $post_cf_fields as $cf_key => $cf_values ) {
                    if( !empty( $cf_values['field_type_key'] ) && $field_key == $cf_values['field_type_key'] ) {
                        $is_logo = true;
                    }
                }
            }

            return $is_logo;
        }
	
		/**
		 * Allowed selected post type in facebook.
		 *
		 * @since 2.0.0
		 *
		 * @return array $allowed
		 */
		public function post_cpt_options( $type = '' ) {
			$post_types = get_post_types( array( 'public' => true ), 'objects' );

			$not_allowed = array('page', 'attachment', 'revision', 'nav_menu_item', 'wpi_invoice' );

			$not_allowed = apply_filters( 'geodir_social_post_types_not_allowed', $not_allowed, $type );

			$cpt_allowed = array();
			if ( ! empty( $post_types ) ) {
				foreach ( $post_types as $post_type => $object ) {
					if ( ! empty( $object->labels->name ) && ! in_array( $post_type, $not_allowed ) ) {
						$cpt_allowed[ $post_type ] = geodir_is_gd_post_type( $post_type ) ? __( $object->labels->name, 'geodirectory' ) : __( $object->labels->name );
					}
				}
			}

			return apply_filters( 'geodir_social_post_types_allowed', $cpt_allowed, $type );
		}

		public function post_to_gmb_text() {
			return __( "New {pt_singular_name}: {post_title} - {post_content}", "gd-social-importer" );
		}

		public function post_to_gmb_tags( $inline = true ) { 
			$tags = array( '{post_title}', '{post_excerpt}', '{post_content}', '{post_author}', '{pt_plural_name}', '{pt_singular_name}', '{sitename}' );
			
			if ( $inline ) {
				$tags = '<code>' . implode( '</code> <code>', $tags ) . '</code>';
			}
			
			return $tags;
		}
	}
}
