<?php
/**
 * Dynamic User Emails Functions
 *
 * @author    AyeCode Ltd
 * @package   GeoDir_Dynamic_Emails
 * @version   2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get the templates path.
 *
 * @since 2.0.0
 *
 * @return string Templates path.
 */
function geodir_dynamic_emails_templates_path() {
	return GEODIR_DYNAMIC_EMAILS_PLUGIN_DIR . 'templates';
}

/**
 * Get the templates url.
 *
 * @since 2.0.0
 *
 * @return string Templates url.
 */
function geodir_dynamic_emails_templates_url() {
    return GEODIR_DYNAMIC_EMAILS_PLUGIN_URL . '/templates';
}

/**
 * Get theme templates folder.
 *
 * @since 2.0.0
 *
 * @return string Theme template folder name.
 */
function geodir_dynamic_emails_theme_templates_dir() {
	return untrailingslashit( apply_filters( 'geodir_dynamic_emails_templates_dir', 'geodir-dynamic-user-emails' ) );
}

function geodir_dynamic_emails_locate_template( $template, $template_name, $template_path = '' ) {
	if ( file_exists( $template ) ) {
		return $template;
	}

	$template_path = geodir_dynamic_emails_theme_templates_dir();
	$default_path = geodir_dynamic_emails_templates_path();
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
 * @since 2.0.0
 */
function geodir_dynamic_emails_schedule_events( $time = 0 ) {
	$interval = (int) geodir_dynamic_emails_schedule_interval();

	if ( $interval > 0 && ! wp_next_scheduled( 'geodir_dynamic_emails_scheduled_emails' ) ) {
		if ( empty( $time ) ) {
			$time = time();
		}

		wp_schedule_event( $time, 'geodir_every_x_minutes', 'geodir_dynamic_emails_scheduled_emails' );

		geodir_update_option( 'dynamic_emails_interval_time', $interval );
	}
}

function geodir_dynamic_emails_schedule_interval() {
	$interval = 5;

	return apply_filters( 'geodir_dynamic_emails_schedule_interval', $interval );
}

function geodir_dynamic_emails_limit() {
	$limit = 0;

	return apply_filters( 'geodir_dynamic_emails_limit', $limit );
}

/**
 * Add custom cron schedules.
 *
 * @since 2.0.0
 *
 * @param array $schedules List of WP scheduled cron jobs.
 * @return array Cron schedules.
 */
function geodir_dynamic_emails_cron_schedules( $schedules ) {
	$interval = (int) geodir_dynamic_emails_schedule_interval();

	if ( empty( $schedules['geodir_every_x_minutes'] ) && $interval > 0 ) {
		$schedules['geodir_every_x_minutes'] = array(
			'interval' => $interval * 60,
			'display' => wp_sprintf( __( 'Every %d Minutes', 'geodir-dynamic-emails' ), $interval )
		);
	}

	return $schedules;
}

function geodir_dynamic_emails_global_params( $post_type = '' ) {
	$params = array( 'post_type', 'post_category', 'post_tags', 'country', 'region', 'city', 'neighbourhood', 'etype', 's', 'near', 'geo_lat', 'geo_lon' );

	return apply_filters( 'geodir_dynamic_emails_global_params', $params, $post_type );
}

function geodir_dynamic_emails_display_action( $action ) {
	$event_actions = GeoDir_Dynamic_Emails_Email::email_actions();

	$display = isset( $event_actions[ $action ] ) ? $event_actions[ $action ] : $action;

	return apply_filters( 'geodir_dynamic_emails_display_action', $display, $action, $event_actions );
}

function geodir_dynamic_emails_display_post_type( $post_type ) {
	$display = __( 'All', 'geodir-dynamic-emails' );

	if ( ! empty( $post_type ) ) {
		if ( ! is_array( $post_type ) ) {
			$_post_type = explode( ",", $post_type );
		} else {
			$_post_type = $post_type;
		}

		$values = array();

		foreach ( $_post_type as $key ) {
			if ( $key ) {
				$values[] = geodir_post_type_name( $key, true );
			}
		}

		$display = ! empty( $values ) ? implode( ", ", $values ) : '';
	}

	return apply_filters( 'geodir_dynamic_emails_display_post_type', $display, $post_type );
}

function geodir_dynamic_emails_display_user_roles( $user_roles ) {
	$display = __( 'All', 'geodir-dynamic-emails' );

	if ( ! empty( $user_roles ) ) {
		$roles = geodir_user_roles();

		if ( ! is_array( $user_roles ) ) {
			$_user_roles = explode( ",", $user_roles );
		} else {
			$_user_roles = $user_roles;
		}

		$values = array();

		foreach ( $_user_roles as $key ) {
			if ( $key && isset( $roles[ $key ] ) ) {
				$values[] = $roles[ $key ];
			}
		}

		$display = ! empty( $values ) ? implode( ", ", $values ) : '';
	}

	return apply_filters( 'geodir_dynamic_emails_display_user_roles', $display, $user_roles );
}

function geodir_dynamic_emails_action_supports( $action, $option ) {
	$supports = false;

	$actions = GeoDir_Dynamic_Emails_Email::email_actions( false );

	if ( ! empty( $actions[ $action ] ) && ! empty( $actions[ $action ]['supports'] ) && in_array( $option, $actions[ $action ]['supports'] ) ) {
		$supports = true;
	}

	return apply_filters( 'geodir_dynamic_emails_action_supports', $supports, $action, $option, $actions );
}


function geodir_dynamic_emails_post_type_actions() {
	$actions = array();

	$all_actions = GeoDir_Dynamic_Emails_Email::email_actions();

	foreach ( $all_actions as $_action => $name ) {
		if ( geodir_dynamic_emails_action_supports( $_action, 'post_type' ) ) {
			$actions[ $_action ] = $name;
		}
	}

	return apply_filters( 'geodir_dynamic_emails_post_type_actions', $actions, $all_actions );
}

function geodir_dynamic_emails_has_action( $action, $args = array() ) {
	$has_action = (int) geodir_get_option( 'email_user_dynamic_emails', 1 ) == 1 ? true : false;

	return apply_filters( 'geodir_dynamic_emails_has_action', $has_action, $action, $args );
}

function geodir_dynamic_emails_parse_array( $string, $type = 'text' ) {
	$array = array();

	if ( ! is_array( $string ) ) {
		if ( ! $string && $string != '0' && $string != 0 ) {
			return $array;
		}

		$array = explode( ",", $string );
	}

	$array = array_unique( array_filter( array_map( 'trim', $array ) ) );

	if ( $type == 'absint' ) {
		$array = array_unique( array_map( 'absint', $array ) );
	} else {
		$array = array_unique( array_map( 'sanitize_text_field', $array ) );
	}

	return apply_filters( 'geodir_dynamic_emails_parse_array', $array, $string );
}
