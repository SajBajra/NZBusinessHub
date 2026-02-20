<?php
/**
 * GeoDirectory Marketplace Update
 *
 * Uninstalling Marketplace deletes data & options.
 *
 * @package GeoDir_Marketplace
 * @author AyeCode Ltd
 * @since 2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( get_option( 'geodir_marketplace_db_version' ) != GEODIR_MARKETPLACE_VERSION ) {
	/**
	 * Include custom database table related functions.
	 *
	 * @since 2.0
	 * @package GeoDir_Marketplace
	 */
	add_action( 'plugins_loaded', 'geodir_marketplace_update_all', 10 );

	// Upgrade old options to new options before loading the rest GD options.
	if ( GEODIR_MARKETPLACE_VERSION <= '2.0' ) {
		add_action( 'init', 'geodir_marketplace_update_2_0' );
	}
}

/**
 * Upgrade for all versions.
 *
 * @since 2.0
 */
function geodir_marketplace_update_all() {
	
}

/**
 * Upgrade for 2.0 version.
 *
 * @since 2.0
 */
function geodir_marketplace_update_2_0() {
	
}
