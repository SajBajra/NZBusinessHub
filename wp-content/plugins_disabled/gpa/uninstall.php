<?php

/**
 * Maybe delete data on uninstall.
 *
 * @author      AyeCode Ltd
 * @package     Advertising
 * @version     1.0.0
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

$adv_settings = get_option( 'adv_settings' );

if ( empty( $adv_settings ) || empty( $adv_settings['uninstall_advertising'] ) ) {
	return;
}


	// Delete the dashboard page
	if(!empty($adv_settings['dashboard_page_id'])){
		wp_delete_post( absint( $adv_settings['dashboard_page_id'] ) );
	}

	// Delete core options
	delete_option( 'advertising_version' );
	delete_option( 'advertising_db_version' );
	delete_option( 'adv_settings' );
	delete_option( 'adv_flushed_rewrite_rules' );


	// Delete adverts.
	$wpdb->query(
		"DELETE a,b
		FROM wp_posts a
		LEFT JOIN wp_postmeta b
			ON (a.ID = b.post_id)
		WHERE a.post_type = 'adv_ad'"
	);

	// Delete zones.
	$wpdb->query(
		"DELETE a,b
		FROM wp_posts a
		LEFT JOIN wp_postmeta b
			ON (a.ID = b.post_id)
		WHERE a.post_type = 'adv_zone'"
	);

	// Clear any cached data that has been removed.
	wp_cache_flush();
