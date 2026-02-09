<?php
/**
 * GeoDirectory Dynamic User Emails Uninstall
 *
 * @author    AyeCode Ltd
 * @package   GeoDir_Dynamic_Emails
 * @version   2.0.0
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

wp_clear_scheduled_hook( 'geodir_dynamic_emails_scheduled_emails' );

$geodir_settings = get_option( 'geodir_settings' );

if ( ( ! empty( $geodir_settings ) && ( ! empty( $geodir_settings['admin_uninstall'] ) || ! empty( $geodir_settings['uninstall_geodir_dynamic_emails'] ) ) ) || ( defined( 'GEODIR_UNINSTALL_DYNAMIC_EMAILS' ) && true === GEODIR_UNINSTALL_DYNAMIC_EMAILS ) ) {
	$plugin_prefix = $wpdb->prefix . 'geodir_';

	$email_lists_table = defined( 'GEODIR_DYNAMIC_EMAILS_LISTS_TABLE' ) ? GEODIR_DYNAMIC_EMAILS_LISTS_TABLE : $plugin_prefix . 'email_lists';
	$email_log_table = defined( 'GEODIR_DYNAMIC_EMAILS_LOG_TABLE' ) ? GEODIR_DYNAMIC_EMAILS_LOG_TABLE : $plugin_prefix . 'email_log ';
	$email_users_table = defined( 'GEODIR_DYNAMIC_EMAILS_USERS_TABLE' ) ? GEODIR_DYNAMIC_EMAILS_USERS_TABLE : $plugin_prefix . 'email_users ';

	// Delete database tables
	$wpdb->query( "DROP TABLE IF EXISTS `{$email_lists_table}`" );
	$wpdb->query( "DROP TABLE IF EXISTS `{$email_log_table}`" );
	$wpdb->query( "DROP TABLE IF EXISTS `{$email_users_table}`" );

	if ( ! empty( $geodir_settings ) ) {
		$save_settings = $geodir_settings;

		$remove_options = array(
			'geodir_dynamic_activation_hook',
			'dynamic_emails_interval_time',
			'dynamic_emails_trigger_send',
			'email_user_dynamic_emails',
			'uninstall_geodir_dynamic_emails'
		);

		foreach ( $remove_options as $option ) {
			if ( isset( $save_settings[ $option ] ) ) {
				unset( $save_settings[ $option ] );
			}
		}

		// Update options.
		update_option( 'geodir_settings', $save_settings );
	}

	// Delete comment meta.
	$wpdb->query( "DELETE FROM `{$wpdb->commentmeta}` WHERE `meta_key` = '_gd_review_response'" );

	// Delete core options
	delete_option( 'geodir_dynamic_emails_version' );
	delete_option( 'geodir_dynamic_emails_db_version' );

	// Clear any cached data that has been removed.
	wp_cache_flush();
}