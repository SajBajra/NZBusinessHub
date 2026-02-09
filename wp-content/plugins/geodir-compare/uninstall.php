<?php
/**
 * GeoDirectory Compare Listings Uninstall
 *
 * @package GeoDir_Compare
 * @author AyeCode Ltd
 * @since 2.2.1
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

$geodir_settings = get_option( 'geodir_settings' );

if ( ( ! empty( $geodir_settings ) && ( ! empty( $geodir_settings['admin_uninstall'] ) || ! empty( $geodir_settings['uninstall_geodir_compare'] ) ) ) || ( defined( 'GEODIR_UNINSTALL_GEODIR_COMPARE' ) && true === GEODIR_UNINSTALL_GEODIR_COMPARE ) ) {
	if ( ! empty( $geodir_settings ) ) {
		$save_settings = $geodir_settings;

		$remove_options = array(
			'geodir_compare_listings_page',
			'uninstall_geodir_compare'
		);

		foreach ( $remove_options as $option ) {
			if ( isset( $save_settings[ $option ] ) ) {
				unset( $save_settings[ $option ] );
			}
		}

		// Update options.
		update_option( 'geodir_settings', $save_settings );
	}

	if ( ! empty( $geodir_settings['geodir_compare_listings_page'] ) ) {
		wp_delete_post( (int) $geodir_settings['geodir_compare_listings_page'], true );
	}

	// Delete options.
	$wpdb->query( "DELETE FROM `{$wpdb->options}` WHERE `option_name` LIKE 'widget_gd_compare%';" );

	delete_option( 'geodir_compare_version' );
	delete_option( 'geodir_compare_db_version' );

	// Clear cache.
	wp_cache_flush();
}

