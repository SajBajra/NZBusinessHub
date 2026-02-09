<?php
/**
 * GeoDirectory Custom Map Styles
 *
 * @package           Geodir_Custom_Google_Maps
 * @author            AyeCode Ltd
 * @copyright         2019 AyeCode Ltd
 * @license           GPLv3
 *
 * @wordpress-plugin
 * Plugin Name:       GeoDirectory Custom Map Styles
 * Plugin URI:        https://wpgeodirectory.com/downloads/custom-google-maps/
 * Description:       Customize map style to match the styling and color scheme of the maps with the rest of your site.
 * Version:           2.3.5
 * Requires at least: 5.0
 * Requires PHP:      5.6
 * Author:            AyeCode Ltd
 * Author URI:        https://ayecode.io
 * License:           GPLv3
 * License URI:       http://www.gnu.org/licenses/gpl-3.0.html
 * Requires Plugins:  geodirectory
 * Text Domain:       geodir-custom-google-maps
 * Domain Path:       /languages
 * Update URL:        https://wpgeodirectory.com
 * Update ID:         65102
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

if ( ! defined( 'GEODIR_CUSTOM_MAPS_VERSION' ) ) {
	define( 'GEODIR_CUSTOM_MAPS_VERSION', '2.3.5' );
}

if ( ! defined( 'GEODIR_CUSTOM_MAPS_MIN_CORE' ) ) {
	define( 'GEODIR_CUSTOM_MAPS_MIN_CORE', '2.3' );
}

// Check user is admin.
if( is_admin() ) {

    include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

    // Check Geodirectory main plugin activate or not.
    if ( !is_plugin_active( 'geodirectory/geodirectory.php' ) ) {

        deactivate_plugins( plugin_basename( __FILE__ ) );

        function gd_google_maps_requires_gd_plugin() {
            echo '<div class="notice notice-warning is-dismissible"><p><strong>' . sprintf( __( '%s requires to install the %sGeoDirectory%s plugin to be installed and active.', 'geodir-custom-google-maps' ), 'GeoDirectory Custom Google Maps', '<a href="https://wpgeodirectory.com" target="_blank" title=" GeoDirectory">', '</a>' ) . '</strong></p></div>';
        }

        add_action( 'admin_notices', 'gd_google_maps_requires_gd_plugin' );

        return;

    }

    if (!function_exists('ayecode_show_update_plugin_requirement')) {

        function ayecode_show_update_plugin_requirement() {

            if ( !defined('WP_EASY_UPDATES_ACTIVE') ) {

                echo '<div class="notice notice-warning is-dismissible"><p><strong>'.sprintf( __( 'The plugin %sWP Easy Updates%s is required to check for and update some installed plugins, please install it now.', 'geodirectory' ), '<a href="https://wpeasyupdates.com/" target="_blank" title="WP Easy Updates">', '</a>' ).'</strong></p></div>';

            }
        }

        add_action( 'admin_notices', 'ayecode_show_update_plugin_requirement' );

    }

}

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/gd-google-maps-activate.php
 *
 * @since 2.0.0
 */
function activate_gd_google_maps() {

    require_once plugin_dir_path( __FILE__ ) . 'includes/gd-google-maps-activate.php';
    GD_Google_Maps_Activate::activate();

}

register_activation_hook( __FILE__, 'activate_gd_google_maps' );

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/gd-google-maps-deactivate.php
 *
 * @since 2.0.0
 */
function deactivate_gd_google_maps() {

    require_once plugin_dir_path( __FILE__ ) . 'includes/gd-google-maps-deactivate.php';
    GD_Google_Maps_Deactivate::deactivate();

}

register_deactivation_hook( __FILE__, 'deactivate_gd_google_maps' );

/**
 * Include GD custom google map main class file.
 *
 * @since 2.0.0
 */
include_once ( dirname( __FILE__).'/class-custom-google-map.php' );

/**
 * Loads a single instance of Custom Google Maps.
 *
 * @since 2.0.0
 *
 * @see GD_Google_Maps::get_instance()
 *
 * @return object GD_Google_Maps Returns an instance of the class
 */
function gd_google_maps() {
	// Min core version check
	if ( ! function_exists( 'geodir_min_version_check' ) || ! geodir_min_version_check( 'Custom Map Styles', GEODIR_CUSTOM_MAPS_MIN_CORE ) ) {
		return '';
	}

    return GD_Google_Maps::get_instance();

}

add_action('plugins_loaded','gd_google_maps',10);
