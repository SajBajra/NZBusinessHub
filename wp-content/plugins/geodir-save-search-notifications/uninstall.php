<?php
/**
 * GeoDirectory Save Search Notifications Uninstall
 *
 * @author    AyeCode Ltd
 * @package   GeoDir_Save_Search
 * @version   1.0
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

wp_clear_scheduled_hook( 'geodir_save_search_scheduled_emails' );

$geodir_settings = get_option( 'geodir_settings' );

if ( ( ! empty( $geodir_settings ) && ( ! empty( $geodir_settings['admin_uninstall'] ) || ! empty( $geodir_settings['uninstall_geodir_save_search'] ) ) ) || ( defined( 'GEODIR_UNINSTALL_SAVE_SEARCH' ) && true === GEODIR_UNINSTALL_SAVE_SEARCH ) ) {
	$plugin_prefix = $wpdb->prefix . 'geodir_';

	$save_search_emails_table = defined( 'GEODIR_SAVE_SEARCH_EMAILS_TABLE' ) ? GEODIR_SAVE_SEARCH_EMAILS_TABLE : $plugin_prefix . 'save_search_emails';
	$save_search_fields_table = defined( 'GEODIR_SAVE_SEARCH_FIELDS_TABLE' ) ? GEODIR_SAVE_SEARCH_FIELDS_TABLE : $plugin_prefix . 'save_search_fields';
	$save_search_subscribers_table = defined( 'GEODIR_SAVE_SEARCH_SUBSCRIBERS_TABLE' ) ? GEODIR_SAVE_SEARCH_SUBSCRIBERS_TABLE : $plugin_prefix . 'save_search_subscribers';

	// Delete database tables
	$wpdb->query( "DROP TABLE IF EXISTS `{$save_search_emails_table}`" );
	$wpdb->query( "DROP TABLE IF EXISTS `{$save_search_fields_table}`" );
	$wpdb->query( "DROP TABLE IF EXISTS `{$save_search_subscribers_table}`" );

	if ( ! empty( $geodir_settings ) ) {
		$save_settings = $geodir_settings;

		$remove_options = array(
			'email_user_save_search',
			'save_search_interval',
			'save_search_limit',
			'email_user_save_search_subject',
			'email_user_save_search_body',
			'email_user_save_search_edit',
			'email_user_save_search_edit_subject',
			'email_user_save_search_edit_body',
			'save_search_trigger_send',
			'save_search_interval_time',
			'save_search_loop',
			'save_search_loop_shortcode',
			'uninstall_geodir_save_search'
		);

		foreach ( $remove_options as $option ) {
			if ( isset( $save_settings[ $option ] ) ) {
				unset( $save_settings[ $option ] );
			}
		}

		// Update options.
		update_option( 'geodir_settings', $save_settings );
	}

	// Delete core options
	delete_option( 'geodir_save_search_version' );
	delete_option( 'geodir_save_search_db_version' );
	delete_option( 'widget_gd_save_search' );

	// Clear any cached data that has been removed.
	wp_cache_flush();
}