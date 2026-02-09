<?php
/**
 * This is the main plugin file, here we declare and call the important stuff
 *
 * @package           Geodirectory
 * @subpackage        Booking
 * @copyright         2021 AyeCode Ltd
 * @license           GPLv2
 * @since             2.0.0
 *
 * @wordpress-plugin
 * Plugin Name:       GeoDirectory Booking Engine
 * Plugin URI:        https://wpgeodirectory.com/downloads/geodir-booking/
 * Description:       Converts GeoDirectory listings into bookable products.
 * Version:           2.1.12
 * Author:            AyeCode Ltd
 * Author URI:        https://wpgeodirectory.com/
 * License:           GPLv2
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Requires Plugins:  geodirectory, invoicing
 * Text Domain:       geodir-booking
 * Domain Path:       /languages
 * Update URL:        https://wpgeodirectory.com
 * Update ID:         2885909
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

if ( ! defined( 'GEODIR_BOOKING_VERSION' ) ) {
	define( 'GEODIR_BOOKING_VERSION', '2.1.12' );
}

if ( ! defined( 'GEODIR_BOOKING_DIR' ) ) {
	define( 'GEODIR_BOOKING_DIR', __DIR__ );
}

if ( ! defined( 'GEODIR_BOOKING_FILE' ) ) {
	define( 'GEODIR_BOOKING_FILE', __FILE__ );
}

// Check environment.
require plugin_dir_path( GEODIR_BOOKING_FILE ) . 'setup-environment.php';

/**
 * Loads the plugin after all dependancies have been loaded.
 */
function geodir_booking_load() {
	geodir_booking_load_plugin_textdomain();

	if ( defined( 'GEODIRECTORY_VERSION' ) && defined( 'WPINV_VERSION' ) ) {
		if ( 1 == version_compare( WPINV_VERSION, '2.4.6' ) ) {
			require_once plugin_dir_path( GEODIR_BOOKING_FILE ) . 'includes/class-geodir-booking.php';

			$GLOBALS['geodir_booking'] = new GeoDir_Booking();
		}
	}
}
add_action( 'plugins_loaded', 'geodir_booking_load', 5 );

/**
 * Loads the text domain
 *
 */
function geodir_booking_load_plugin_textdomain() {
	// Determines the current locale.
	$locale = determine_locale();

	/**
	 * Filter the plugin locale.
	 *
	 * @since 1.0
	 */
	$locale = apply_filters( 'plugin_locale', $locale, 'geodir-booking' );

	unload_textdomain( 'geodir-booking', true );
	load_textdomain( 'geodir-booking', WP_LANG_DIR . '/geodir-booking/geodir-booking-' . $locale . '.mo' );
	load_plugin_textdomain( 'geodir-booking', false, basename( dirname( GEODIR_BOOKING_FILE ) ) . '/languages/' );
}