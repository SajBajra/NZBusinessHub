<?php
/**
 * GeoDirectory Franchise Manager
 *
 * @package           Geodir_Franchise
 * @author            AyeCode Ltd
 * @copyright         2019 AyeCode Ltd
 * @license           GPLv3
 *
 * @wordpress-plugin
 * Plugin Name:       GeoDirectory Franchise Manager
 * Plugin URI:        https://wpgeodirectory.com/downloads/franchise-manager/
 * Description:       Allows users to submit listings for chains of businesses or franchises faster and allows directory owners to monetize those listings in a smarter way.
 * Version:           2.3.8
 * Requires at least: 5.0
 * Requires PHP:      5.6
 * Author:            AyeCode Ltd
 * Author URI:        https://ayecode.io
 * License:           GPLv3
 * License URI:       http://www.gnu.org/licenses/gpl-3.0.html
 * Requires Plugins:  geodirectory
 * Text Domain:       geodir-franchise
 * Domain Path:       /languages
 * Update URL:        https://wpgeodirectory.com
 * Update ID:         65845
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

if ( ! defined( 'GEODIR_FRANCHISE_VERSION' ) ) {
	define( 'GEODIR_FRANCHISE_VERSION', '2.3.8' );
}

if ( ! defined( 'GEODIR_FRANCHISE_MIN_CORE' ) ) {
	define( 'GEODIR_FRANCHISE_MIN_CORE', '2.3' );
}

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since 2.0.0
 */
function geodir_load_franchise_manager() {
    global $geodir_franchise;

	if ( ! defined( 'GEODIR_FRANCHISE_PLUGIN_FILE' ) ) {
		define( 'GEODIR_FRANCHISE_PLUGIN_FILE', __FILE__ );
	}

	// Min core version check
	if ( ! function_exists( 'geodir_min_version_check' ) || ! geodir_min_version_check( 'Franchise Manager', GEODIR_FRANCHISE_MIN_CORE ) ) {
		return '';
	}

	/**
	 * The core plugin class that is used to define internationalization,
	 * dashboard-specific hooks, and public-facing site hooks.
	 */
	require_once ( plugin_dir_path( GEODIR_FRANCHISE_PLUGIN_FILE ) . 'includes/class-geodir-franchise.php' );

    return $geodir_franchise = GeoDir_Franchise::instance();
}
add_action( 'geodirectory_loaded', 'geodir_load_franchise_manager' );