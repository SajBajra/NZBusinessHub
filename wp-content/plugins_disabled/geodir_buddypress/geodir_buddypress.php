<?php
/**
 * GeoDirectory BuddyPress Integration
 *
 * @package           Geodir_BuddyPress
 * @author            AyeCode Ltd
 * @copyright         2019 AyeCode Ltd
 * @license           GPLv3
 *
 * @wordpress-plugin
 * Plugin Name:       GeoDirectory BuddyPress Integration
 * Plugin URI:        https://wpgeodirectory.com/downloads/buddypress-integration/
 * Description:       Integrates GeoDirectory listing activity with the BuddyPress activity.
 * Version:           2.3.2
 * Requires at least: 4.9
 * Requires PHP:      5.6
 * Author:            AyeCode Ltd
 * Author URI:        https://ayecode.io
 * License:           GPLv3
 * License URI:       http://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:       geodir-buddypress
 * Domain Path:       /languages
 * Update URL:        https://wpgeodirectory.com
 * Update ID:         65093
 */

// MUST have WordPress.
if ( !defined( 'WPINC' ) )
	exit( 'Do NOT access this file directly: ' . basename( __FILE__ ) );
	
// Define Constants
define( 'GEODIR_BUDDYPRESS_VERSION', '2.3.2' );
define( 'GEODIR_BUDDYPRESS_PLUGIN_PATH', WP_PLUGIN_DIR . '/' . plugin_basename( dirname( __FILE__ ) ) );
define( 'GEODIR_BUDDYPRESS_PLUGIN_URL', plugins_url('',__FILE__) );
define( 'GEODIR_BUDDYPRESS_PLUGIN_FILE', __FILE__ );
define( 'GDBUDDYPRESS_TEXTDOMAIN', 'geodir-buddypress' );

if ( ! defined( 'GEODIR_BUDDYPRESS_MIN_CORE' ) ) {
	define( 'GEODIR_BUDDYPRESS_MIN_CORE', '2.3' );
}

if ( is_admin() ) {
	// Check if BuddyPress is active, if not bail.
	if ( ! class_exists( 'BuddyPress' ) ) {
		function geodir_gdbuddypress_requires_buddypress_plugin() {
			echo '<div class="notice notice-warning is-dismissible"><p><strong>' . sprintf( __( 'GeoDirectory BuddyPress Integration plugin requires %sBuddyPress%s to be installed and activated.', 'geodir-buddypress' ), '<a href="https://wordpress.org/plugins/buddypress/" target="_blank" title="BuddyPress">', '</a>' ) . '</strong></p></div>';
		}
		add_action( 'admin_notices', 'geodir_gdbuddypress_requires_buddypress_plugin' );
		return;
	}

	// GEODIRECTORY CORE ALIVE CHECK START
	if ( ! function_exists( 'is_plugin_active' ) ) {
		include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
	}

	if ( ! is_plugin_active( 'geodirectory/geodirectory.php' ) ) {
		function geodir_gdbuddypress_requires_core_plugin() {
			echo '<div class="notice notice-warning is-dismissible"><p><strong>' . sprintf( __( 'GeoDirectory BuddyPress Integration plugin requires %sGeoDirectory%s to be installed and activated.', 'geodir-buddypress' ), '<a href="https://wordpress.org/plugins/geodirectory/" target="_blank" title="GeoDirectory - Business Directory Plugin">', '</a>' ) . '</strong></p></div>';
		}

		add_action( 'admin_notices', 'geodir_gdbuddypress_requires_core_plugin' );

		return;
	}
	// GEODIRECTORY CORE ALIVE CHECK END

	// GEODIRECTORY UPDATE CHECKS
	if ( ! function_exists( 'ayecode_show_update_plugin_requirement' ) ) { //only load the update file if needed
		require_once( 'gd_update.php' ); // require update script
	}
}

require plugin_dir_path(__FILE__) . 'includes/class-geodir-buddypress.php';

if ( is_admin() || ( defined( 'WP_CLI' ) && WP_CLI ) ) {
	register_activation_hook(__FILE__, 'activate_gd_buddypress');
}

function activate_gd_buddypress($network_wide){
	require_once('includes/activator.php');

	GeoDir_BuddyPress_Activator::activate();
}

function init_gd_buddypress() {
	if ( ! class_exists( 'GeoDirectory' ) || ! class_exists( 'BuddyPress' ) ) {
		return;
	}

	// Min core version check
	if ( ! function_exists( 'geodir_min_version_check' ) || ! geodir_min_version_check( 'BuddyPress Integration', GEODIR_BUDDYPRESS_MIN_CORE ) ) {
		return '';
	}

	GeoDir_BuddyPress::get_instance();
}
add_action( 'plugins_loaded', 'init_gd_buddypress', apply_filters( 'gd_buddypress_action_priority', 10 ) );