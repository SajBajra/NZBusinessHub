<?php
/**
 * GeoDirectory Save Search Notifications
 *
 * @package           GeoDir_Save_Search
 * @author            AyeCode Ltd
 * @copyright         2019 AyeCode Ltd
 * @license           GPLv3
 *
 * @wordpress-plugin
 * Plugin Name:       GeoDirectory Save Search Notifications
 * Plugin URI:        https://wpgeodirectory.com/downloads/saved-search-notifications/
 * Description:       Allows users to save search and users will receive an email notification when new listings published matching saved search.
 * Version:           2.1.5
 * Requires at least: 6.0
 * Requires PHP:      5.6
 * Author:            AyeCode Ltd
 * Author URI:        https://ayecode.io
 * License:           GPLv3
 * License URI:       http://www.gnu.org/licenses/gpl-3.0.html
 * Requires Plugins:  geodirectory
 * Text Domain:       geodir-save-search
 * Domain Path:       /languages
 * Update URL:        https://wpgeodirectory.com
 * Update ID:         3322593
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

if ( ! defined( 'GEODIR_SAVE_SEARCH_VERSION' ) ) {
	define( 'GEODIR_SAVE_SEARCH_VERSION', '2.1.5' );
}

if ( ! defined( 'GEODIR_SAVE_SEARCH_MIN_CORE' ) ) {
	define( 'GEODIR_SAVE_SEARCH_MIN_CORE', '2.3' );
}

/**
 * Sets the activation hook for a plugin.
 *
 * @since 1.0
 */
function geodir_save_search_activation_hook() {
	if ( function_exists( 'geodir_update_option' ) ) {
		geodir_update_option( 'geodir_save_search_activation_hook', 1 );
	}
}
register_activation_hook( __FILE__, 'geodir_save_search_activation_hook' );

/**
 * Sets the deactivation hook for a plugin.
 *
 * @since 1.0
 */
function geodir_save_search_deactivation_hook() {
	wp_clear_scheduled_hook( 'geodir_save_search_scheduled_emails' );
}
register_deactivation_hook( __FILE__, 'geodir_save_search_deactivation_hook' );

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since 1.0
 */
function geodir_load_save_search_notifications() {
	global $geodir_save_search_notifications;

	if ( ! defined( 'GEODIR_SAVE_SEARCH_PLUGIN_FILE' ) ) {
		define( 'GEODIR_SAVE_SEARCH_PLUGIN_FILE', __FILE__ );
	}

	// Min core version check
	if ( ! function_exists( 'geodir_min_version_check' ) || ! geodir_min_version_check( 'Save Search Notifications', GEODIR_SAVE_SEARCH_MIN_CORE ) ) {
		return '';
	}

	/**
	 * The core plugin class that is used to define internationalization,
	 * dashboard-specific hooks, and public-facing site hooks.
	 */
	require_once( plugin_dir_path( GEODIR_SAVE_SEARCH_PLUGIN_FILE ) . 'includes/class-geodir-save-search.php' );

	return $geodir_save_search_notifications = GeoDir_Save_Search::instance();
}
add_action( 'geodirectory_loaded', 'geodir_load_save_search_notifications' );