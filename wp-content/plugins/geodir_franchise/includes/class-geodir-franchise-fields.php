<?php
/**
 * Franchise Manager Fields class.
 *
 * @since 2.0.0
 * @package Geodir_Franchise
 * @author AyeCode Ltd
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * GeoDir_Franchise_Fields class.
 */
class GeoDir_Franchise_Fields {

    public static function init() {
	
		if ( ! ( ! empty( $_REQUEST['tab'] ) && $_REQUEST['tab'] == 'cpt' && isset( $_REQUEST['prev_supports_franchise'] ) ) ) {
			add_filter( 'geodir_default_custom_fields', array( __CLASS__, 'default_custom_fields' ), 10, 3 );
		}

		// Admin cpt cf settings
		add_filter( 'geodir_cfa_can_delete_field', array( __CLASS__, 'cfa_can_delete_field' ), 10, 2 );

		// Skip event recurring field
		add_filter( 'geodir_franchise_skip_lock_field_name_recurring', '__return_true', 10, 4 );

		// Listing form
		add_filter( 'geodir_custom_field_input_text_franchise_of', array( __CLASS__, 'input_franchise_of' ), 10, 2 );
		add_filter( 'geodir_custom_field_input_address', array( __CLASS__, 'lock_field' ), 20, 2 );
		add_filter( 'geodir_custom_field_input_business_hours', array( __CLASS__, 'lock_field' ), 20, 2 );
		add_filter( 'geodir_custom_field_input_categories', array( __CLASS__, 'lock_field' ), 20, 2 );
		add_filter( 'geodir_custom_field_input_checkbox', array( __CLASS__, 'lock_field' ), 20, 2 );
		add_filter( 'geodir_custom_field_input_datepicker', array( __CLASS__, 'lock_field' ), 20, 2 );
		add_filter( 'geodir_custom_field_input_email', array( __CLASS__, 'lock_field' ), 20, 2 );
		add_filter( 'geodir_custom_field_input_event', array( __CLASS__, 'lock_field' ), 20, 2 );
		add_filter( 'geodir_custom_field_input_fieldset', array( __CLASS__, 'lock_field' ), 20, 2 );
		add_filter( 'geodir_custom_field_input_file', array( __CLASS__, 'lock_field' ), 20, 2 );
		add_filter( 'geodir_custom_field_input_html', array( __CLASS__, 'lock_field' ), 9, 2 );
		add_filter( 'geodir_custom_field_input_images', array( __CLASS__, 'lock_field' ), 20, 2 );
		add_filter( 'geodir_custom_field_input_link_posts', array( __CLASS__, 'lock_field' ), 20, 2 );
		add_filter( 'geodir_custom_field_input_multiselect', array( __CLASS__, 'lock_field' ), 20, 2 );
		add_filter( 'geodir_custom_field_input_phone', array( __CLASS__, 'lock_field' ), 20, 2 );
		add_filter( 'geodir_custom_field_input_radio', array( __CLASS__, 'lock_field' ), 20, 2 );
		add_filter( 'geodir_custom_field_input_select', array( __CLASS__, 'lock_field' ), 20, 2 );
		add_filter( 'geodir_custom_field_input_tags', array( __CLASS__, 'lock_field' ), 20, 2 );
		add_filter( 'geodir_custom_field_input_text', array( __CLASS__, 'lock_field' ), 20, 2 );
		add_filter( 'geodir_custom_field_input_textarea', array( __CLASS__, 'lock_field' ), 9, 2 );
		add_filter( 'geodir_custom_field_input_time', array( __CLASS__, 'lock_field' ), 20, 2 );
		add_filter( 'geodir_custom_field_input_url', array( __CLASS__, 'lock_field' ), 20, 2 );

		// Events field
		add_filter( 'geodir_event_custom_field_input_event_recurring', array( __CLASS__, 'event_lock_field' ), 20, 3 );
		add_filter( 'geodir_event_custom_field_input_event_event_dates', array( __CLASS__, 'event_lock_field' ), 20, 3 );

		add_filter( 'geodir_filter_geodir_post_custom_fields', array( __CLASS__, 'filter_post_custom_fields' ), 10, 4 );

		// Input value
		add_filter( 'geodir_get_cf_value', array( __CLASS__, 'cf_value' ), 10, 2 );

		// Output field
		add_filter( 'geodir_custom_field_output_text', array( __CLASS__, 'output_franchise_of' ), 40, 5 );
		add_filter( 'wp_super_duper_arguments', array( __CLASS__, 'super_duper_set_franchise_of' ), 10, 3 );
		add_filter( 'geodir_map_params', array( __CLASS__, 'map_widget_params' ), 10, 2 );
		add_filter( 'geodir_rest_markers_query_where', array( __CLASS__, 'rest_markers_query_where' ), 20, 2 );

		// Pricing Manager
		add_filter( 'geodir_pricing_package_exclude_fields', array( __CLASS__, 'pricing_package_exclude_fields' ), 1, 2 );

		// Search
		//add_filter( 'geodir_search_fields_setting_allow_var_franchise_fields', '__return_true', 10, 3 );
		//add_filter( 'geodir_advance_search_field_in_main_search_bar', array( __CLASS__, 'field_in_main_search_bar' ), 10, 3 );
		//add_filter( 'geodir_search_cpt_search_setting_field', array( __CLASS__, 'cpt_search_setting_field' ), 10, 2 );
		//add_filter( 'geodir_search_output_to_main_franchise', array( __CLASS__, 'search_bar_output_franchise' ), 10, 3 );
		//add_filter( 'geodir_search_filter_field_output_franchise', array( __CLASS__, 'search_output_franchise' ), 10, 3 );
		add_filter( 'geodir_search_filter_searched_params', array( __CLASS__, 'search_filter_searched_params' ), 20, 3 );
	}

	public static function franchise_custom_fields( $post_type, $package_id = 0 ) {
		if ( empty( $package_id ) ) {
			$package_id = geodir_get_post_package_id( '', $post_type );
		}

		$package = is_array( $package_id ) && ! empty( $package_id ) ? $package_id : ( $package_id !== '' ? array( $package_id ) : '');

		$fields = array();
		$fields[] = array(
			'post_type' => $post_type,
			'data_type' => 'TINYINT',
			'field_type' => 'radio',
			'field_type_key' => 'franchise',
			'admin_title' => __( 'Has Franchise?', 'geodir-franchise' ),
			'frontend_desc' => __( 'Tick "Yes" if listing has franchises.', 'geodir-franchise' ),
			'frontend_title' => __( 'Has Franchise?', 'geodir-franchise' ),
			'htmlvar_name' => 'franchise',
			'default_value' => '0',
			'sort_order' => '1',
			'is_active' => '1',
			'option_values' => __( 'Yes', 'geodir-franchise' ) . '/1,' . __( 'No', 'geodir-franchise' ) . '/0',
			'is_default' => '0',
			'show_in' => '',
			'show_on_pkg' => $package,
			'field_icon' => 'fas fa-sitemap',
			'clabels' => __( 'Has Franchise?', 'geodir-franchise' ),
			'add_column' => true,
			'single_use' => true,
		);
		$fields[] = array(
			'post_type' => $post_type,
			'data_type' => 'TEXT',
			'field_type' => 'multiselect',
			'field_type_key' => 'franchise_fields',
			'admin_title' => __( 'Lock franchise fields', 'geodir-franchise' ),
			'frontend_desc' => __( 'Select fields to lock from franchise edit.', 'geodir-franchise' ),
			'frontend_title' => __( 'Lock franchise fields', 'geodir-franchise' ),
			'htmlvar_name' => 'franchise_fields',
			'default_value' => '',
			'sort_order' => '1',
			'is_active' => '1',
			'option_values' => '',
			'is_default' => '0',
			'show_in' => '',
			'show_on_pkg' => $package,
			'field_icon' => 'fas fa-lock',
			'clabels' => __( 'Lock franchise fields', 'geodir-franchise' ),
			'single_use' => true,
			'add_column' => true
		);
		$fields[] = array(
			'post_type' => $post_type,
			'data_type' => 'INT',
			'field_type' => 'text',
			'field_type_key' => 'franchise_of',
			'admin_title' => __( 'Main Listing', 'geodir-franchise' ),
			'frontend_desc' => __( 'Enter main listing ID.', 'geodir-franchise' ),
			'frontend_title' => __( 'Main Listing', 'geodir-franchise' ),
			'htmlvar_name' => 'franchise_of',
			'default_value' => '',
			'sort_order' => '1',
			'is_active' => '1',
			'option_values' => '',
			'is_default' => '',
			'show_in' => '',
			'show_on_pkg' => $package,
			'field_icon' => 'fas fa-sitemap',
			'clabels' => __( 'Main Listing', 'geodir-franchise' ),
			'for_admin_use' => true,
			'single_use' => true,
			'add_column' => true
		);

		return $fields;
	}

	public static function default_custom_fields( $fields, $post_type, $package_id ) {
		if ( GeoDir_Post_types::supports( $post_type, 'franchise' ) ) {
			$franchise_fields = self::franchise_custom_fields( $post_type, $package_id );

			if ( ! empty( $franchise_fields ) ) {
				foreach ( $franchise_fields as $key => $field ) {
					$fields[] = $field;
				}
			}
		}

		return $fields;
	}

	public static function cfa_can_delete_field( $delete, $field ) {
		if ( ! empty( $field ) && GeoDir_Post_types::supports( $field->post_type, 'franchise' ) ) {
			if ( ! empty( $field->htmlvar_name ) && ( $field->htmlvar_name == 'franchise' || $field->htmlvar_name == 'franchise_fields' || $field->htmlvar_name == 'franchise_of' ) ) {
				$delete = false;
			}
		}
		return $delete;
	}

	public static function lock_field( $html, $field ) {
		global $gd_post;

		if ( ! GeoDir_Post_types::supports( $field['post_type'], 'franchise' ) ) {
			return $html;
		}

		$main_post_id = 0;
		if ( ! empty( $gd_post->ID ) ) {
			if ( ! empty( $_REQUEST['pid'] ) ) {
				if ( geodir_franchise_is_franchise( (int) $_REQUEST['pid'] ) ) {
					$main_post_id = geodir_franchise_main_post_id( (int) $_REQUEST['pid'] );
				}
			} else {
				if ( geodir_franchise_is_franchise( $gd_post->ID ) ) {
					$main_post_id = geodir_franchise_main_post_id( $gd_post->ID );
				} else if ( ! geodir_franchise_is_main( $gd_post->ID ) && isset( $_REQUEST['task'] ) && $_REQUEST['task'] == 'add_franchise' && isset( $_REQUEST['franchise_of'] ) ) {
					if ( geodir_franchise_is_main( (int) $_REQUEST['franchise_of'] ) ) {
						$main_post_id = (int) $_REQUEST['franchise_of'];
					}
				}
			}
		} else {
			if ( isset( $_REQUEST['task'] ) && $_REQUEST['task'] == 'add_franchise' && isset( $_REQUEST['franchise_of'] ) ) {
				if ( geodir_franchise_is_main( (int) $_REQUEST['franchise_of'] ) ) {
					$main_post_id = (int) $_REQUEST['franchise_of'];
				}
			}
		}

		if ( ! empty( $main_post_id ) ) {
			$lockable_fields = array();

			if ( $field['name'] == 'franchise_of' || ( $field['name'] == 'franchise' && ! is_super_admin() ) ) {
				$html = '<input type="hidden" name="franchise_of" value="' . $main_post_id . '">';
			} else if ( $field['name'] == 'package_id' ) {
				$html = '<input type="hidden" name="package_id" value="' . (int) geodir_get_post_meta( $main_post_id, 'package_id', true ) . '">';
			} else if ( $field['name'] == 'expire_date' && ! is_admin() ) {
				$html = '<!-- -->';
			} else if ( in_array( $field['name'], array( 'franchise', 'franchise_fields' ) ) ) {
				$html = '<!-- -->';
			} else {
				$lockable_fields = self::get_locked_fields( $field['post_type'], '', '', 'names' );
				$locked_fields = geodir_franchise_post_locked_fields( $main_post_id );

				if ( ! empty( $locked_fields ) && in_array( $field['name'], $locked_fields ) ) {
					if ( ( $field['name'] == 'post_content' || $field['name'] == 'post_title' ) && ! is_admin() ) {
						$main_post = get_post( $main_post_id );
						$html = '<input type="hidden" name="' . esc_attr( $field['name'] ) . '" value="' . esc_attr( $main_post->{$field['name']} ) . '">';
					} else {
						$html = '<!-- -->';
					}
				}
			}

			$html = apply_filters( 'geodir_franchise_input_lock_field_html', $html, $field, $main_post_id, $lockable_fields );
		}

		return $html;
	}

	public static function event_lock_field( $html, $field, $package_id ) {
		return self::lock_field( $html, $field );
	}

	public static function input_franchise_of( $html, $cf ) {
		return '<!-- -->';
	}

	public static function output_franchise_of( $html, $location, $cf, $p = '', $output = '' ) {
		if ( ! is_array( $cf ) && $cf != 'franchise_of' ) {
			return $html;
		}

		if ( is_numeric( $p ) ) {
			$gd_post = geodir_get_post_info( $p );
		} else { 
			global $gd_post;
		}

		if ( ! is_array( $cf ) && $cf != '' ) {
			$cf = geodir_get_field_infoby( 'htmlvar_name', $cf, $gd_post->post_type );

			if ( empty( $cf ) ) {
				return $html;
			}
		}

		$htmlvar_name = ! empty( $cf['htmlvar_name'] ) ? $cf['htmlvar_name'] : '';
		if ( $htmlvar_name != 'franchise_of' ) {
			return $html;
		}

		$html = '';

		$htmlvar_name = $cf['htmlvar_name'];

		if ( ! empty( $gd_post->{$htmlvar_name} ) && geodir_franchise_is_main( (int) $gd_post->{$htmlvar_name} ) ) {
			$value = $gd_post->{$htmlvar_name};
			$class = "geodir-i-text";
            $field_label = __( $cf['frontend_title'], 'geodirectory' );
			$field_value = '<a href="' . get_permalink( $value ) . '">' . get_the_title( $value ) . '</a>';
			$field_icon = geodir_field_icon_proccess($cf);
            $output = geodir_field_output_process($output);
            if (strpos($field_icon, 'http') !== false) {
                $field_icon_af = '';
            } elseif ($field_icon == '') {
                $field_icon_af = "";
            } else {
                $field_icon_af = $field_icon;
                $field_icon = '';
            }

			$field_label = apply_filters( 'geodir_franchise_output_label_franchise_of', $field_label, $gd_post, $cf, $location );
			$field_value = apply_filters( 'geodir_franchise_output_value_franchise_of', $field_value, $gd_post, $cf, $location );

            $html = '<div class="geodir_post_meta ' . $cf['css_class'] . ' geodir-field-' . $cf['htmlvar_name'] . '">';
            if ( $output == '' || isset( $output['icon'] ) ) $html .= '<span class="geodir_post_meta_icon '.$class.'" style="' . $field_icon . '">' . $field_icon_af;
            if ( $output == '' || isset( $output['label'] ) ) $html .= trim( $field_label ) != '' ? '<span class="geodir_post_meta_title" >' . $field_label . ': ' . '</span>' : '';
            if ( $output == '' || isset( $output['icon'] ) ) $html .= '</span>';
            if ( $output == '' || isset( $output['value'] ) ) $html .= $field_value;
            $html .= '</div>';
		}

		return $html;
	}

	public static function super_duper_set_franchise_of( $arguments, $options, $instance ) {
		if ( ! empty( $options ) && isset( $options['base_id'] ) && GeoDir_Franchise_Post_Type::get_franchise_post_types() ) {
			if ( $options['base_id'] == 'gd_listings' ) {
				// gd_listings widget
				$arguments['franchise_of'] = array(
					'title' => wp_sprintf( __( 'Filter by %s:', 'geodir-franchise' ), geodir_franchise_label( 'singular_name' ) ),
					'desc' => __( 'Filters franchise posts by related to post. Use "auto" or POST_ID(ex: 135) OR leave blank. auto: Filters the franchise posts by current viewing post. POST_ID(135): Filters the franchise posts by post id = 135. Leave blank to not use this filter.', 'geodir-franchise' ),
					'type' => 'text',
					'default' => '',
					'desc_tip' => 1,
					'placeholder' => __( 'auto OR POST_ID OR leave blank', 'geodir-franchise' ),
					'advanced' => 1,
					'group' => __( 'Filters', 'geodirectory' )
				);
			} else if ( $options['base_id'] == 'gd_map' ) {
				// gd_map widget
				$arguments['franchise_of'] = array(
					'title' => wp_sprintf( __( 'Filter by %s:', 'geodir-franchise' ), geodir_franchise_label( 'singular_name' ) ),
					'desc' => __( 'Use current_post to show franchises of current viewing post. Use post id(ex: 135) to show franchises of 135. Leave blank to skip this filter.', 'geodir-franchise' ),
					'type' => 'text',
					'default' => '',
					'desc_tip' => 1,
					'placeholder' => __( '135 OR current_post OR leave blank', 'geodir-franchise' ),
					'advanced' => 1,
					'element_require' => '[%map_type%]!="post"',
					'group' => __( 'Map Content', 'geodirectory' )
				);
			}
		}

		return $arguments;
	}

	public static function map_widget_params( $params, $map_args = array() ) {
		global $gd_post;

		if ( ! empty( $params['franchise_of'] ) && ! empty( $params['map_type'] ) && $params['map_type'] == 'post' ) {
			$params['franchise_of'] = '';
		}

		if ( ! empty( $params['franchise_of'] ) ) {
			$_franchise_of = sanitize_text_field( $params['franchise_of'] );

			if ( $_franchise_of == 'current_post' ) {
				if ( ( geodir_is_page( 'detail' ) || geodir_is_page( 'preview' ) ) && ! empty( $gd_post->ID ) ) {
					$_franchise_of = $gd_post->post_type == 'revision' ? (int) wp_get_post_parent_id( (int) $gd_post->ID ) : (int) $gd_post->ID;
				} else {
					$_franchise_of = -1;
				}
			} else {
				$_franchise_of = absint( $_franchise_of );
			}

			$franchise_of = $_franchise_of > 0 ? $_franchise_of : -1;

			if ( empty( $params['customQueryArgs'] ) ) {
				$params['customQueryArgs'] = "";
			}

			$params['customQueryArgs'] .= '&franchise=' . $franchise_of;
		}

		return $params;
	}

	public static function rest_markers_query_where( $where, $request ) {
		global $wpdb;

		if ( ! empty( $request['franchise'] ) ) {
			$show_main = geodir_get_option( 'franchise_map_show_main', 1 );
			$show_main = apply_filters( 'geodir_franchise_map_show_main_listing', $show_main, $request );

			$show_current = geodir_get_option( 'franchise_map_show_viewing', 1 );
			$show_current = apply_filters( 'geodir_franchise_map_show_viewing_franchise', $show_current, $request );

			$franchise_of = (int) $request['franchise'];

			$main_listing_id = 0;
			$franchise_id = 0;

			if ( $franchise_of > 0 && GeoDir_Post_types::supports( $request['post_type'], 'franchise' ) ) {
				if ( geodir_franchise_is_main( $franchise_of ) ) {
					$main_listing_id = $franchise_of;
				} else if ( geodir_franchise_is_franchise( $franchise_of ) ) {
					$main_listing_id = geodir_franchise_main_post_id( $franchise_of );
					$franchise_id = $franchise_of;
				}
			}

			if ( $main_listing_id ) {
				$query_where = " AND";

				if ( $show_main ) {
					$query_where .= " (";
				}

				$query_where .= $wpdb->prepare( " `pd`.`franchise_of` = %d", array( $main_listing_id ) );

				if ( $show_main ) {
					// Show main listing
					$query_where .= $wpdb->prepare( " OR `pd`.`post_id` = %d", array( $main_listing_id ) );
				}

				if ( $show_main ) {
					$query_where .= " )";
				}

				// Hide view franchise
				if ( $franchise_id && ! $show_current ) {
					$query_where .= $wpdb->prepare( " AND `pd`.`post_id` != %d", array( $franchise_id ) );
				}
			} else {
				$query_where = " AND `p`.`ID` = '-1'";
			}

			$where .= $query_where;
		}

		return $where;
	}

	public static function filter_post_custom_fields( $fields, $package_id, $post_type, $fields_location ) {
		if ( GeoDir_Post_types::supports( $post_type, 'franchise' ) ) {
			foreach ( $fields as $index => $field ) {
				if ( ! empty( $field['name'] ) && $field['name'] == 'franchise_fields' ) {
					$field_options = self::input_franchise_fields_options( $field['options'], $field, $package_id );

					$options = array();
					if ( ! empty( $field_options ) ) {
						foreach ( $field_options as $name => $label ) {
							$label = __( $label, 'geodirectory' );
							if ( strpos( $label, '/' ) !== false ) {
								$label = str_replace( '/', '\\', $label );
							}
							if ( strpos( $label, ',' ) !== false ) {
								$label = str_replace( ',', '-', $label );
							}
							$options[] = $label . '/' . $name;
						}
					}

					$fields[ $index ]['options'] = $options;
					$fields[ $index ]['option_values'] = ! empty( $options ) ? implode( ',', $options ) : '';
				}
			}
		}
		return $fields;
	}

	public static function input_franchise_fields_options( $field_options, $field, $package_id = '', $default = 'all' ) {
		$options = self::get_locked_fields( $field['post_type'], $package_id, $default, 'options' );

		return apply_filters( 'geodir_franchise_input_franchise_fields_options', $options, $field_options, $field, $package_id, $default );
	}

	/**
	 * Get post type locked field.
	 *
	 * @since 2.0.0
	 *
	 * @param string $post_type Optional. The wordpress post type.
	 * @param int|string $package_id The package ID.
	 * @param string $output Output type. names => only array of names, options => array of name - title pair. Default full object.
	 * @param string $default Optional. When set to "default" it will display only default fields.
	 * @return array|mixed|void Returns custom fields.
	 */
	public static function get_locked_fields( $post_type = 'gd_place', $package_id = '', $default = 'all', $output = 'names' ) {
		if ( ! GeoDir_Post_types::supports( $post_type, 'franchise' ) ) {
			return NULL;
		}

		remove_filter( 'geodir_filter_geodir_post_custom_fields', array( __CLASS__, 'filter_post_custom_fields' ), 10, 4 );

		$fields = geodir_post_custom_fields( $package_id, $default, $post_type );

		if ( GeoDir_Post_types::supports( $post_type, 'comments' ) ) {
			$fields['99999' . $post_type . 'comments'] = array(
				'name' => 'comments',
				'label' => __( 'Comments', 'geodir-franchise' ),
				'type' => 'text',
				'desc' => __( 'Select comments', 'geodir-franchise' ),
				'post_type' => 'gd_school',
				'id' => 0,
				'admin_title' => __( 'Comments', 'geodir-franchise' ),
				'frontend_title' => __( 'Comments', 'geodir-franchise' ),
				'htmlvar_name' => 'comments',
				'field_icon' => 'fas fa-comments'
			);
		}

		$locked_fields = array();
		if ( ! empty( $fields ) ) {
			foreach ( $fields as $index => $field ) {
				if ( empty( $field['name'] ) ) {
					continue;
				}

				if ( in_array( $field['name'], array( 'franchise', 'franchise_fields', 'franchise_of' ) ) ) {
					continue;
				}

				$skip = ! empty( $field['for_admin_use'] ) ? true : false;
				$skip = apply_filters( 'geodir_franchise_skip_lock_field', $skip, $field, $package_id, $default );
				$skip = apply_filters( 'geodir_franchise_skip_lock_field_name_' . $field['name'], $skip, $field, $package_id, $default );

				if ( $skip ) {
					continue;
				}

				if ( $output == 'names' ) {
					$locked_fields[] = $field['name'];
				} else if ( $output == 'options' ) {
					$title = ! empty( $field['frontend_title'] ) ? $field['frontend_title'] : ( ! empty( $field['label'] ) ? $field['label'] : $field['admin_title'] );
					$locked_fields[ $field['name'] ] = $title;
				} else {
					$locked_fields[] = $field;
				}
			}
		}

		add_filter( 'geodir_filter_geodir_post_custom_fields', array( __CLASS__, 'filter_post_custom_fields' ), 10, 4 );

		return apply_filters( 'geodir_franchise_locked_fields', $locked_fields, $post_type, $package_id, $default, $output );
	}

	public static function pricing_package_exclude_fields( $fields, $package_id ) {
		if ( ! empty( $fields ) && in_array( 'franchise', $fields ) ) {
			$fields[] = 'franchise_fields';
			$fields[] = 'franchise_of';
		}

		return $fields;
	}

	public static function cf_value( $value, $cf ) {
		global $gd_post;

		$field_name = ! empty( $cf['name'] ) ? $cf['name'] : '';

		// Set default value
		if ( ! empty( $gd_post->post_status ) && $gd_post->post_status == 'auto-draft' ) {
			if ( $field_name == 'franchise' && isset( $cf['default'] ) ) {
				$value = ! empty( $cf['default'] ) ? 1 : 0;
			}
		}

		return $value;
	}

	public static function search_filter_searched_params( $params, $post_type, $fields = array() ) {
		global $aui_bs5;

		if ( ! empty( $_REQUEST['sfranchise_of'] ) && ( $franchise_of = absint( $_REQUEST['sfranchise_of'] ) ) > 0 ) {
			$field_title = geodir_franchise_label( 'items_of', $post_type );

			if ( ! empty( $fields ) ) {
				foreach( $fields as $key => $field ) {
					if ( $field->htmlvar_name == 'franchise_of' ) {
						$field_title = $field->frontend_title != '' ? $field->frontend_title : $field->admin_title;
						$field_title = stripslashes( __( $field_title, 'geodirectory' ) );
					}
				}
			}

			if ( $field_title ) {
				$field_title .= ':';
			}

			if ( ! empty( $params ) ) {
				foreach ( $params as $key => $param ) {
					if ( strpos( $param, 'gd-adv-search-franchise_of' ) !== false ) {
						unset( $params[ $key ] );
						break;
					}
				}
			}

			$design_style = geodir_design_style();

			$label_class = 'gd-adv-search-label';
			$sublabel_class = 'gd-adv-search-label-t';
			if ( $design_style ) {
				$label_class .= ' badge c-pointer ' . ( $aui_bs5 ? 'bg-info me-2' : 'badge-info mr-2' );
				$sublabel_class .= ' mb-0 c-pointer ' . ( $aui_bs5 ? 'me-1' : 'mr-1' );
			}

			$params[] = '<label class="' . $label_class . ' gd-adv-search-default gd-adv-search-franchise_of" data-name="sfranchise_of"><i class="fas fa-times" aria-hidden="true"></i> <label class="' . $sublabel_class . '">' . $field_title . '</label>' . get_the_title( $franchise_of )  . '</label>';
		}

		return $params;
	}
}