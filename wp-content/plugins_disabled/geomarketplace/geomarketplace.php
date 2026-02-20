<?php
/**
 * GeoDirectory Marketplace
 *
 * @package           GeoDir_Marketplace
 * @author            AyeCode Ltd
 * @copyright         2019 AyeCode Ltd
 * @license           GPLv3
 *
 * @wordpress-plugin
 * Plugin Name:       GeoDirectory Marketplace
 * Plugin URI:        https://wpgeodirectory.com/downloads/geomarketplace
 * Description:       Integrates GeoDirectory, WooCommerce, and multivendor plugins like Dokan, MultiVendorX, WCFM & WC Vendors Pro, allowing users to sell products directly from their listings.
 * Version:           2.2.1
 * Requires at least: 5.0
 * Requires PHP:      5.6
 * Author:            AyeCode Ltd
 * Author URI:        https://ayecode.io
 * License:           GPLv3
 * License URI:       http://www.gnu.org/licenses/gpl-3.0.html
 * Requires Plugins:  geodirectory, woocommerce
 * Text Domain:       geomarketplace
 * Domain Path:       /languages
 * Update URL:        https://wpgeodirectory.com
 * Update ID:         2684822
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

if ( ! defined( 'GEODIR_MARKETPLACE_VERSION' ) ) {
	define( 'GEODIR_MARKETPLACE_VERSION', '2.2.1' );
}

if ( ! defined( 'GEODIR_MARKETPLACE_MIN_CORE' ) ) {
	define( 'GEODIR_MARKETPLACE_MIN_CORE', '2.3' );
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
function geodir_load_marketplace() {
	global $geodir_marketplace;

	if ( ! defined( 'GEODIR_MARKETPLACE_PLUGIN_FILE' ) ) {
		define( 'GEODIR_MARKETPLACE_PLUGIN_FILE', __FILE__ );
	}

	// Min core version check
	if ( ! function_exists( 'geodir_min_version_check' ) || ! geodir_min_version_check( 'Marketplace', GEODIR_MARKETPLACE_MIN_CORE ) ) {
		return '';
	}

	/**
	 * The core plugin class that is used to define internationalization,
	 * dashboard-specific hooks, and public-facing site hooks.
	 */
	require_once ( plugin_dir_path( GEODIR_MARKETPLACE_PLUGIN_FILE ) . 'includes/class-geodir-marketplace.php' );

	$geodir_marketplace = GeoDir_Marketplace::instance();
}
add_action( 'plugins_loaded', 'geodir_load_marketplace', 11 );
