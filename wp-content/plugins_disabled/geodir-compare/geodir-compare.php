<?php
/**
 * GeoDirectory Compare Listings
 *
 * @package           GeoDir_Compare
 * @author            AyeCode Ltd
 * @copyright         2019 AyeCode Ltd
 * @license           GPLv3
 *
 * @wordpress-plugin
 * Plugin Name:       GeoDirectory Compare Listings
 * Plugin URI:        https://wpgeodirectory.com/downloads/compare-listings/
 * Description:       Compare listings side by side and compare vital information.
 * Version:           2.2.3
 * Requires at least: 5.0
 * Requires PHP:      5.6
 * Author:            AyeCode Ltd
 * Author URI:        https://ayecode.io
 * License:           GPLv3
 * License URI:       http://www.gnu.org/licenses/gpl-3.0.html
 * Requires Plugins:  geodirectory
 * Text Domain:       geodir-compare
 * Domain Path:       /languages
 * Update URL:        https://wpgeodirectory.com
 * Update ID:         724713
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

if ( ! defined( 'GEODIR_COMPARE_VERSION' ) ) {
	define( 'GEODIR_COMPARE_VERSION', '2.2.3' );
}

if ( ! defined( 'GEODIR_COMPARE_MIN_CORE' ) ) {
	define( 'GEODIR_COMPARE_MIN_CORE', '2.3' );
}

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function GeoDir_Compare() {
	global $geodir_compare;

	if ( ! defined( 'GEODIR_COMPARE_PLUGIN_FILE' ) ) {
		define( 'GEODIR_COMPARE_PLUGIN_FILE', __FILE__ );
	}

	// Min core version check.
	if ( ! function_exists( 'geodir_min_version_check' ) || ! geodir_min_version_check( 'Compare Listings', GEODIR_COMPARE_MIN_CORE ) ) {
		return '';
	}

	/**
	 * The core plugin class that is used to define internationalization,
	 * dashboard-specific hooks, and public-facing site hooks.
	 */
	require_once ( plugin_dir_path( GEODIR_COMPARE_PLUGIN_FILE ) . 'includes/class-geodir-compare.php' );

	return $geodir_compare = GeoDir_Compare::instance();
}
add_action( 'geodirectory_loaded', 'GeoDir_Compare' );
