<?php
/**
 * This is the main plugin file, here we declare and call the important stuff
 *
 * @package           GEODIR
 * @subpackage        TICKETS
 * @copyright         2021 AyeCode Ltd
 * @license           GPLv2
 * @since             2.0.0
 *
 * @wordpress-plugin
 * Plugin Name:       GeoDirectory Events Tickets Marketplace
 * Plugin URI:        https://wpgeodirectory.com/downloads/events-tickets-marketplace/
 * Description:       Converts GeoDirectory Event listings into saleable tickets.
 * Version:           2.1.3
 * Author:            AyeCode Ltd
 * Author URI:        https://ayecode.io
 * License:           GPLv3
 * License URI:       http://www.gnu.org/licenses/gpl-3.0.html
 * Requires Plugins:  geodirectory, events-for-geodirectory, invoicing, getpaid-item-inventory, getpaid-wallet
 * Text Domain:       geodir-tickets
 * Domain Path:       /languages
 * Update URL:        https://wpgeodirectory.com
 * Update ID:         2195307
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

if ( ! defined( 'GEODIR_TICKETS_VERSION' ) ) {
	define( 'GEODIR_TICKETS_VERSION', '2.1.3' );
}

if ( ! defined( 'GEODIR_TICKETS_MIN_CORE' ) ) {
	define( 'GEODIR_TICKETS_MIN_CORE', '2.3' );
}

if ( ! defined( 'GEODIR_TICKETS_FILE' ) ) {
	define( 'GEODIR_TICKETS_FILE', __FILE__ );
}

// Check environment.
include plugin_dir_path( GEODIR_TICKETS_FILE ) . 'setup-environment.php';

/**
 * Loads the plugin after all dependencies have been loaded.
 */
function geodir_tickets_load() {
	if ( defined( 'GETPAID_ITEM_INVENTORY_VERSION' ) && defined( 'WPINV_WALLET_VERSION' ) && defined( 'GEODIR_EVENT_VERSION' ) && defined( 'GEODIRECTORY_VERSION' ) && defined( 'WPINV_VERSION' ) ) {
		// Min core version check
		if( ! ( function_exists( 'geodir_min_version_check' ) && geodir_min_version_check( 'Events Tickets Marketplace', GEODIR_TICKETS_MIN_CORE ) ) ) {
			return;
		}

		if ( 1 == version_compare( WPINV_VERSION, '2.4.6' ) ) {
			require_once plugin_dir_path( GEODIR_TICKETS_FILE ) . 'includes/class-geodir-tickets.php';

			$GLOBALS['geodir_tickets'] = new GeoDir_Tickets();
		}
	}
}
add_action( 'plugins_loaded', 'geodir_tickets_load' );

/**
 * Loads the plugin textdomain.
 */
function geodir_tickets_load_plugin_textdomain() {
	// Determines the current locale.
	$locale = determine_locale();

	$locale = apply_filters( 'plugin_locale', $locale, 'geodir-tickets' );

	unload_textdomain( 'geodir-tickets', true );
	load_textdomain( 'geodir-tickets', WP_LANG_DIR . '/geodir-tickets/geodir-tickets-' . $locale . '.mo' );
	load_plugin_textdomain( 'geodir-tickets', false, basename( dirname( GEODIR_TICKETS_FILE ) ) . '/languages/' );
}
add_action( 'plugins_loaded', 'geodir_tickets_load_plugin_textdomain' );
