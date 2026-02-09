<?php
/**
 * GeoDirectory Marketplace Uninstall
 *
 * Uninstalling Marketplace deletes data & options.
 *
 * @package GeoDir_Marketplace
 * @author AyeCode Ltd
 * @since 2.0
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

$geodir_settings = get_option( 'geodir_settings' );

if ( ( ! empty( $geodir_settings ) && ( ! empty( $geodir_settings['admin_uninstall'] ) || ! empty( $geodir_settings['uninstall_geodir_marketplace'] ) ) ) || ( defined( 'GEODIR_UNINSTALL_MARKETPLACE' ) && true === GEODIR_UNINSTALL_MARKETPLACE ) ) {
	if ( ! empty( $geodir_settings ) ) {
		$save_settings = $geodir_settings;

		$remove_options = array(
			'mp_post_type',
			'mp_link_post',
			'uninstall_geodir_marketplace',
		);

		foreach ( $remove_options as $option ) {
			if ( isset( $save_settings[ $option ] ) ) {
				unset( $save_settings[ $option ] );
			}
		}

		// Update options.
		update_option( 'geodir_settings', $save_settings );
	}

	// Delete post meta.
	$wpdb->query( "DELETE FROM {$wpdb->postmeta} WHERE `meta_key` IN( '_geomp_vendor_id', '_geomp_gd_listing_id' )" );

	// Delete core options.
	delete_option( 'geodir_marketplace_version' );
	delete_option( 'geodir_marketplace_db_version' );
}