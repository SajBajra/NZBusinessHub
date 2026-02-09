<?php
/**
 * Save Search Notifications Functions
 *
 * @author    AyeCode Ltd
 * @package   GeoDir_Save_Search
 * @version   1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get the templates path.
 *
 * @since 1.0
 *
 * @return string Templates path.
 */
function geodir_save_search_templates_path() {
	return GEODIR_SAVE_SEARCH_PLUGIN_DIR . 'templates';
}

/**
 * Get the templates url.
 *
 * @since 1.0
 *
 * @return string Templates url.
 */
function geodir_save_search_templates_url() {
    return GEODIR_SAVE_SEARCH_PLUGIN_URL . '/templates';
}

/**
 * Get theme templates folder.
 *
 * @since 1.0
 *
 * @return string Theme template folder name.
 */
function geodir_save_search_theme_templates_dir() {
	return untrailingslashit( apply_filters( 'geodir_save_search_templates_dir', 'geodir-save-search-notifications' ) );
}

function geodir_save_search_locate_template( $template, $template_name, $template_path = '' ) {
	if ( file_exists( $template ) ) {
		return $template;
	}

	$template_path = geodir_save_search_theme_templates_dir();
	$default_path = geodir_save_search_templates_path();
	$default_template = untrailingslashit( $default_path ) . '/' . $template_name;

	if ( ! file_exists( $default_template ) ) {
		return $template;
	}

	// Look within passed path within the theme - this is priority.
	$template = locate_template( array( untrailingslashit( $template_path ) . '/' . $template_name, $template_name ) );

	// Get default template.
	if ( ! $template ) {
		$template = $default_template;
	}

	return $template;
}

/**
 * Schedule events.
 *
 * @since 1.0
 */
function geodir_save_search_schedule_events( $time = 0 ) {
	$interval = (int) geodir_get_option( 'save_search_interval' );

	if ( $interval > 0 && ! wp_next_scheduled( 'geodir_save_search_scheduled_emails' ) ) {
		if ( empty( $time ) ) {
			$time = time();
		}

		wp_schedule_event( $time, 'geodir_every_x_hours', 'geodir_save_search_scheduled_emails' );

		geodir_update_option( 'save_search_interval_time', $interval );
	}
}

/**
 * Add custom cron schedules.
 *
 * @since 1.0
 *
 * @param array $schedules List of WP scheduled cron jobs.
 * @return array Cron schedules.
 */
function geodir_save_search_cron_schedules( $schedules ) {
	$interval = (int) geodir_get_option( 'save_search_interval' );

	if ( empty( $schedules['geodir_every_x_hours'] ) && $interval > 0 ) {
		$intervals = GeoDir_Save_Search_Email::email_intervals();

		$title = isset( $intervals[ $interval ] ) ? $intervals[ $interval ] : __( 'Every x Hours', 'geodir-save-search' );

		$schedules['geodir_every_x_hours'] = array(
			'interval' => $interval,
			'display' => $title
		);
	}

	return $schedules;
}

function geodir_save_search_parse_args( $query_args ) {
	$args = array();

	foreach ( $query_args as $key => $value ) {
		if ( is_array( $value ) ) {
			$value = array_filter( $value );

			if ( ! empty( $value ) ) {
				if ( isset( $value['from'] ) || isset( $value['to'] ) ) {
					$value_from = isset( $value['from'] ) ? ( is_array( $value['from'] ) ? ',' . implode( ",", $value['from'] ) . ',' : trim( $value['from'] ) ) : '';
					$value_to = isset( $value['to'] ) ? ( is_array( $value['to'] ) ? ',' . implode( ",", $value['to'] ) . ',' : trim( $value['to'] ) ) : '';

					$args[ $key ] = $value_from . ' to ' . $value_to;
				} else {
					$args[ $key ] = ',' . implode( ",", $value ) . ',';
				}
			}
		} else if ( is_scalar( $value ) ) {
			$value = trim( $value );

			if ( $value !== '' && $value !== false && $value !== null && $value !== 0 ) {
				$args[ $key ] = $value;
			}
		}
	}

	$parse_args = $args;

	$skip_args = array( 'geodir_search', 'stype', 'paged' );

	foreach ( $skip_args as $skip_arg ) {
		if ( isset( $args[ $skip_arg ] ) ) {
			unset( $args[ $skip_arg ] );
		}
	}

	return apply_filters( 'geodir_save_search_parse_args', $args, $parse_args, $query_args );
}

function geodir_save_search_get_fields( $post_type ) {
	$search_fields = GeoDir_Adv_Search_Fields::get_search_fields( $post_type );

	$fields = geodir_save_search_global_params( $post_type );

	if ( ! empty( $search_fields ) ) {
		foreach ( $search_fields as $key => $field ) {
			if ( empty( $field->htmlvar_name ) ) {
				continue;
			}

			if ( $field->htmlvar_name == 'business_hours' ) {
				$field->htmlvar_name = 'open_now';
			} else if ( $field->htmlvar_name == 'distance' ) {
				$field->htmlvar_name = 'dist';
			}

			$fields[] = $field->htmlvar_name;

			if ( $field->input_type == 'RANGE' && $field->search_condition == 'FROM' ) {
				$fields[] = 'min' . $field->htmlvar_name;
				$fields[] = 'max' . $field->htmlvar_name;
			}
		}
	}

	return apply_filters( 'geodir_save_search_get_fields', $fields, $post_type );
}

function geodir_save_search_parse_fields( $post_type, $query_params ) {
	$search_fields = geodir_save_search_get_fields( $post_type );

	$fields = array();

	if ( ! empty( $query_params ) ) {
		$_query_params = $query_params;

		if ( class_exists( 'GeoDir_Location_City' ) ) {
			if ( ! empty( $_query_params['neighbourhood'] ) ) {
				$location = GeoDir_Location_Neighbourhood::get_info_by_slug( $_query_params['neighbourhood'] );

				if ( ! empty( $location ) ) {
					$_query_params['neighbourhood'] = $location->neighbourhood . ',' . $location->city . ',' . $location->region . ',' . $location->country;
					//$_query_params['city'] = $location->city . ',' . $location->region . ',' . $location->country;
					//$_query_params['region'] = $location->region . ',' . $location->country;
					//$_query_params['country'] = $location->country;
					if ( isset( $_query_params['city'] ) ) {
						unset( $_query_params['city'] );
					}
					if ( isset( $_query_params['region'] ) ) {
						unset( $_query_params['region'] );
					}
					if ( isset( $_query_params['country'] ) ) {
						unset( $_query_params['country'] );
					}
				}
			} else if ( ! empty( $_query_params['city'] ) ) {
				$location = GeoDir_Location_City::get_info_by_slug( $_query_params['city'] );

				if ( ! empty( $location ) ) {
					$_query_params['city'] = $location->city . ',' . $location->region . ',' . $location->country;
					if ( isset( $_query_params['region'] ) ) {
						unset( $_query_params['region'] );
					}
					if ( isset( $_query_params['country'] ) ) {
						unset( $_query_params['country'] );
					}
					//$_query_params['region'] = $location->region . ',' . $location->country;
					//$_query_params['country'] = $location->country;
				}
			} else if ( ! empty( $_query_params['region'] ) ) {
				$location = GeoDir_Location_City::get_info_by_slug( $_query_params['region'] );

				if ( ! empty( $location ) ) {
					$_query_params['region'] = $location->region . ',' . $location->country;
					if ( isset( $_query_params['country'] ) ) {
						unset( $_query_params['country'] );
					}
					//$_query_params['country'] = $location->country;
				}
			} else if ( ! empty( $_query_params['country'] ) ) {
				$location = GeoDir_Location_City::get_info_by_slug( $_query_params['country'] );

				if ( ! empty( $location ) ) {
					$_query_params['country'] = $location->country;
				}
			}
		}

		foreach ( $_query_params as $query_key => $query_value ) {
			$field_key = '';
			$squery_key = strpos( $query_key, 's' ) === 0 && strlen( $query_key ) > 1 ? substr( $query_key, 1 ) : $query_key;

			if ( in_array( $query_key, $search_fields ) ) {
				$field_key = $query_key;
			} else if ( in_array( $squery_key, $search_fields ) ) {
				$field_key = $squery_key;
			}

			$field_key = apply_filters( 'geodir_save_search_parse_field_key', $field_key, $query_key, $query_value, $post_type );

			if ( empty( $field_key ) ) {
				continue;
			}

			if ( $field_key == 'event_dates' ) {
				$query_value = str_replace( __( ' to ', 'geodirectory' ), ' to ', $query_value );
			}

			$fields[ $field_key ] = apply_filters( 'geodir_save_search_parse_field_value', $query_value, $query_key, $field_key, $post_type );
		}

		if ( GeoDir_Post_types::supports( $post_type, 'events' ) && empty( $fields['etype'] ) ) {
			$event_type = ! empty( $_REQUEST['etype'] ) ? sanitize_text_field( $_REQUEST['etype'] ) : geodir_get_option( 'event_default_filter' );

			if ( ( $gd_event_type = get_query_var( 'gd_event_type' ) ) ) {
				$event_type = $gd_event_type;
			}

			if ( ! empty( $event_type ) ) {
				$fields['etype'] = $event_type;
			}
		}
	}

	return apply_filters( 'geodir_save_search_parse_fields', $fields, $post_type, $query_params );
}

function geodir_save_search_user_name( $user ) {
	$user_name = '';

	if ( ! empty( $user ) ) {
		if ( isset( $user->display_name ) && trim( $user->display_name ) != '' ) {
			$user_name = trim( $user->display_name );
		} else if ( isset( $user->user_nicename ) && trim( $user->user_nicename ) != '' ) {
			$user_name = trim( $user->user_nicename );
		} else {
			$user_name = trim( $user->user_login );
		}
	}

	return $user_name;
}

function geodir_save_search_global_params( $post_type = '' ) {
	$params = array( 'post_type', 'post_category', 'post_tags', 'country', 'region', 'city', 'neighbourhood', 'etype', 's', 'near', 'geo_lat', 'geo_lon' );

	return apply_filters( 'geodir_save_search_global_params', $params, $post_type );
}

function geodir_save_search_has_action( $action, $args = array() ) {
	if ( $action == 'edit_post' ) {
		$has_action = (int) geodir_get_option( 'email_user_save_search_edit' ) == 1 ? true : false;
	} else {
		$has_action = (int) geodir_get_option( 'email_user_save_search' ) == 1 ? true : false;
	}

	return apply_filters( 'geodir_save_search_has_action', $has_action, $action, $args );
}