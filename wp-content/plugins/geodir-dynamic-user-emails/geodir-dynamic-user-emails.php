<?php
/**
 * GeoDirectory Dynamic User Emails
 *
 * @package           GeoDir_Dynamic_Emails
 * @author            AyeCode Ltd
 * @copyright         2019 AyeCode Ltd
 * @license           GPLv3
 *
 * @wordpress-plugin
 * Plugin Name:       GeoDirectory Dynamic User Emails
 * Plugin URI:        https://wpgeodirectory.com/downloads/dynamic-user-emails/
 * Description:       Allows to send an dynamic or instant emails to the users.
 * Version:           2.0.5
 * Requires at least: 6.0
 * Requires PHP:      7.2
 * Author:            AyeCode Ltd
 * Author URI:        https://ayecode.io
 * License:           GPLv3
 * License URI:       http://www.gnu.org/licenses/gpl-3.0.html
 * Requires Plugins:  geodirectory
 * Text Domain:       geodir-dynamic-emails
 * Domain Path:       /languages
 * Update URL:        https://wpgeodirectory.com
 * Update ID:         3943311
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

if ( ! defined( 'GEODIR_DYNAMIC_EMAILS_VERSION' ) ) {
	define( 'GEODIR_DYNAMIC_EMAILS_VERSION', '2.0.5' );
}

if ( ! defined( 'GEODIR_DYNAMIC_EMAILS_MIN_CORE' ) ) {
	define( 'GEODIR_DYNAMIC_EMAILS_MIN_CORE', '2.3.15' );
}

/**
 * Sets the activation hook for a plugin.
 *
 * @since 2.0.0
 */
function geodir_dynamic_emails_activation_hook() {
	if ( function_exists( 'geodir_update_option' ) ) {
		geodir_update_option( 'geodir_dynamic_activation_hook', 1 );
	}
}
register_activation_hook( __FILE__, 'geodir_dynamic_emails_activation_hook' );

/**
 * Sets the deactivation hook for a plugin.
 *
 * @since 2.0.0
 */
function geodir_dynamic_emails_deactivation_hook() {
	wp_clear_scheduled_hook( 'geodir_dynamic_emails_scheduled_emails' );
}
register_deactivation_hook( __FILE__, 'geodir_dynamic_emails_deactivation_hook' );

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since 2.0.0
 */
function geodir_load_dynamic_emails() {
	global $geodir_dynamic_emails;

	if ( ! defined( 'GEODIR_DYNAMIC_EMAILS_PLUGIN_FILE' ) ) {
		define( 'GEODIR_DYNAMIC_EMAILS_PLUGIN_FILE', __FILE__ );
	}

	// Min core version check
	if ( ! function_exists( 'geodir_min_version_check' ) || ! geodir_min_version_check( 'Dynamic User Emails', GEODIR_DYNAMIC_EMAILS_MIN_CORE ) ) {
		return '';
	}

	/**
	 * The core plugin class that is used to define internationalization,
	 * dashboard-specific hooks, and public-facing site hooks.
	 */
	require_once( plugin_dir_path( GEODIR_DYNAMIC_EMAILS_PLUGIN_FILE ) . 'includes/class-geodir-dynamic-emails.php' );

	return $geodir_dynamic_emails = GeoDir_Dynamic_Emails::instance();
}
add_action( 'geodirectory_loaded', 'geodir_load_dynamic_emails' );