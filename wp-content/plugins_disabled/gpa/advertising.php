<?php
/**
 * GetPaid Advertising
 *
 * @package     Advertising
 * @copyright   2021 AyeCode Ltd
 * @license     GPLv3
 * @since       1.0.0
 *
 * @wordpress-plugin
 * Plugin Name: GetPaid Advertising
 * Plugin URI: https://wpgetpaid.com/downloads/advertising/
 * Description: Manage ads and insert them anywhere on the WordPress website.
 * Version: 1.2.5
 * Author: AyeCode Ltd
 * Author URI: https://wpgetpaid.com/
 * Requires at least: 5.0
 * Tested up to: 6.9
 * License: GPLv3
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 * Requires Plugins: invoicing
 * Text Domain: advertising
 * Domain Path: /languages
 * Update URL: https://wpgetpaid.com/
 * Update ID: 45084
 */

if ( ! defined( 'WPINC' ) ) {
    die;
}

if ( ! defined( 'ADVERTISING_VERSION' ) ) {
	define( 'ADVERTISING_VERSION', '1.2.5' );
}

if ( ! defined( 'ADVERTISING_PLUGIN_FILE' ) ) {
	define( 'ADVERTISING_PLUGIN_FILE', __FILE__ );
}

if ( ! defined( 'ADVERTISING_PLUGIN_DIR' ) ) {
	define( 'ADVERTISING_PLUGIN_DIR', plugin_dir_path( ADVERTISING_PLUGIN_FILE ) );
}

if ( ! defined( 'ADVERTISING_PLUGIN_URL' ) ) {
	define( 'ADVERTISING_PLUGIN_URL', plugins_url( '/', __FILE__ ) );
}

/**
 * Registers GetPaid as a required plugin.
 */
function adv_register_required_plugins() {
	/*
	 * Array of plugin arrays. Required keys are name and slug.
	 */
	$plugins = array(

		// This is an example of how to include a plugin from the WordPress Plugin Repository.
		array(
			'name'     => 'GetPaid',
			'slug'     => 'invoicing',
			'required' => true,
			'version'  => '2.5.0',
		),

	);

	/*
	 * Array of configuration settings. Amend each line as needed.
	 *
	 */
	$config = array(
		'id'           => 'adv_',                 // Unique ID for hashing notices for multiple instances of TGMPA.
		'default_path' => '',                             // Default absolute path to bundled plugins.
		'menu'         => 'advertising-install-plugins', // Menu slug.
		'parent_slug'  => 'plugins.php',                  // Parent menu slug.
		'capability'   => 'manage_options',               // Capability needed to view plugin install page, should be a capability associated with the parent menu used.
		'has_notices'  => true,                           // Show admin notices or not.
		'dismissable'  => false,                          // If false, a user cannot dismiss the nag message.
		'dismiss_msg'  => '',                             // If 'dismissable' is false, this message will be output at top of nag.
		'is_automatic' => false,                          // Automatically activate plugins after installation or not.
		'message'      => '',                             // Message to output right before the plugins table.
	);

	tgmpa( $plugins, $config );
}
add_action( 'tgmpa_register', 'adv_register_required_plugins' );
require_once plugin_dir_path( ADVERTISING_PLUGIN_FILE ) . 'includes/libraries/class-tgm-plugin-activation.php';


// Load the main plugin file.
require_once ADVERTISING_PLUGIN_DIR . 'includes/class-advertising.php';
$GLOBALS['advertising'] = new Advertising();
