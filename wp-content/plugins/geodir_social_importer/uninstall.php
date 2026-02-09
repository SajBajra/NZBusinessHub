<?php
/**
 * GeoDirectory Social Importer Uninstall
 *
 * Uninstalling Social Importer deletes its data.
 *
 * @author      AyeCode Ltd
 * @package     Geodir_Social_Importer
 * @version     2.0.0.0
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

$geodir_settings = get_option( 'geodir_settings' );

if ( ( ! empty( $geodir_settings ) && ( ! empty( $geodir_settings['admin_uninstall'] ) || ! empty( $geodir_settings['uninstall_geodir_social_importer'] ) ) ) || ( defined( 'GEODIR_UNINSTALL_SOCIAL_IMPORTER' ) && true === GEODIR_UNINSTALL_SOCIAL_IMPORTER ) ) {
	if ( ! empty( $geodir_settings ) ) {
		$save_settings = $geodir_settings;

		$remove_options = array(
			'uninstall_geodir_social_importer',
			'si_fb_app_id',
			'si_fb_app_secret',
			'si_enable_fb_scrapper',
			'si_fb_access_token',
			'si_fb_access_token_expire',
			'si_fb_app_page_post',
			'si_fb_disable_post_to_fb',
			'si_fb_cpt_to_fb',
			'si_fb_disable_auto_post',
			'si_yelp_api_key',
			'si_enable_ta_scrapper',
			'si_gmb_auth_code',
			'si_gmb_access_token',
			'si_gmb_access_token_date',
			'si_gmb_refresh_token',
			'si_gmb_refresh_token_date',
			'si_gmb_expires_in',
			'si_gmb_account',
			'si_gmb_location',
			'si_gmb_cpt_to_import',
			'si_gmb_auto_post_to_gmb',
			'si_gmb_cpt_to_gmb',
			'si_gmb_post_text'
		);

		// Unset options.
		foreach ( $remove_options as $option ) {
			if ( isset( $save_settings[ $option ] ) ) {
				unset( $save_settings[ $option ] );
			}
		}

		// Update options.
		update_option( 'geodir_settings', $save_settings );
	}

	// Delete post meta
	$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->postmeta} WHERE meta_key = %s OR meta_key = %s", array( 'gdfi_posted_facebook', 'gdfi_posted_gmb' ) ) );

	// Delete transients.
	delete_transient( 'geodir_social_gmb_access_token' );
	delete_transient( 'geodir_social_gmb_get_accounts' );
	delete_transient( 'geodir_social_gmb_get_locations' );

	// Delete core options.
	delete_option( 'gdfi_post_to_facebook' );
	delete_option( 'gdfi_post_to_gmb' );

	// Clear any cached data that has been removed.
	wp_cache_flush();
}