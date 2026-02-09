<?php
/**
 * GeoDirectory Save Search Notifications Upgrade
 *
 * @author    AyeCode Ltd
 * @package   GeoDir_Save_Search
 * @version   1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( get_option( 'geodir_save_search_db_version' ) != GEODIR_SAVE_SEARCH_VERSION ) {
	add_action( 'plugins_loaded', 'geodir_save_search_upgrade_all', 10 );
}

/**
 * Upgrade for all versions.
 *
 * @since 1.0
 */
function geodir_save_search_upgrade_all() {
	// Upgrade stuff.
}
