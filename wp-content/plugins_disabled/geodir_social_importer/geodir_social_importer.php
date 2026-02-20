<?php
/**
 * GeoDirectory Social Importer
 *
 * @package           Geodir_Social_Importer
 * @author            AyeCode Ltd
 * @copyright         2019 AyeCode Ltd
 * @license           GPLv3
 *
 * @wordpress-plugin
 * Plugin Name:       GeoDirectory Social Importer
 * Plugin URI:        https://wpgeodirectory.com/downloads/social-importer/
 * Description:       Quickly import page information from Facebook, Yelp, TripAdvisor or Google My Business sites just by entering the page URL.
 * Version:           2.3.8
 * Requires at least: 5.0
 * Requires PHP:      5.6
 * Author:            AyeCode Ltd
 * Author URI:        https://ayecode.io
 * License:           GPLv3
 * License URI:       http://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:       gd-social-importer
 * Domain Path:       /languages
 * Update URL:        https://wpgeodirectory.com
 * Update ID:         65886
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

if ( ! defined( 'GEODIR_SOCIAL_IMPORTER_VERSION' ) ) {
	define( 'GEODIR_SOCIAL_IMPORTER_VERSION', '2.3.8' );
}
if ( ! defined( 'GEODIR_SOCIAL_IMPORTER_MIN_CORE' ) ) {
	define( 'GEODIR_SOCIAL_IMPORTER_MIN_CORE', '2.3' );
}

// check current logged in user is admin.
if( is_admin() ) {
	include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

	// check Geodirectory plugin not activate.
	if ( ! is_plugin_active( 'geodirectory/geodirectory.php' ) ) {
		deactivate_plugins( plugin_basename( __FILE__ ) );

		function gd_social_import_requires_gd_plugin() {
			echo '<div class="notice notice-warning is-dismissible"><p><strong>' . wp_sprintf( __( '%s requires to install the %sGeoDirectory%s plugin to be installed and active.', 'gd-social-importer' ), 'GeoDirectory Social Importer', '<a href="https://wpgeodirectory.com" target="_blank" title=" GeoDirectory">', '</a>' ) . '</strong></p></div>';
		}

		add_action( 'admin_notices', 'gd_social_import_requires_gd_plugin' );
		return;
	}

	// check ayecode_show_update_plugin_requirement function exists or not.
	if ( ! function_exists( 'ayecode_show_update_plugin_requirement' ) ) {
		function ayecode_show_update_plugin_requirement() {
			if ( ! defined( 'WP_EASY_UPDATES_ACTIVE' ) ) {
				echo '<div class="notice notice-warning is-dismissible"><p><strong>' . wp_sprintf( __( 'The plugin %sWP Easy Updates%s is required to check for and update some installed plugins, please install it now.', 'gd-social-importer' ), '<a href="https://wpeasyupdates.com/" target="_blank" title="WP Easy Updates">', '</a>' ).'</strong></p></div>';
			}
		}

		add_action( 'admin_notices', 'ayecode_show_update_plugin_requirement' );
	}

	// show a notice about facebook import feature issue.
	function gd_social_import_facebook_feature_issue() {
		global $aui_bs5;

		$tab = !empty( $_GET['tab'] )? $_GET['tab']: '';
		$page = !empty( $_GET['page'] )? $_GET['page']: '';		

		if ( 'gd-settings' == $page && 'social-importer' == $tab && empty( $_GET['section'] ) ) {
			echo '<div class="notice-warning notice notice-alt is-dismissible"><p' . ( $aui_bs5 ? ' class="my-2"' : '' ) . '>' . 
			wp_sprintf(
				__( 'Due to Facebook restrictions, we have now moved to %sa 3rd party solution%s and the first 50 imports are free.', 'gd-social-importer' ),
				'<a href="https://scraping-bot.io/" target="_blank">',
				'</a>'
			) . '</p></div>';
		}
	}

	add_action( 'admin_notices', 'gd_social_import_facebook_feature_issue' );
}

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/geodir-social-importer-activate.php
 *
 * @since 2.0.0
 */
function activate_gd_social_importer() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/geodir-social-importer-activate.php';

	GD_Social_Importer_Activate::activate();
}
register_activation_hook( __FILE__, 'activate_gd_social_importer' );

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/geodir-social-importer-deactivate.php
 *
 * @since 2.0.0
 */
function deactivate_gd_social_importer() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/geodir-social-importer-deactivate.php';

	GD_Social_Importer_Deactivate::deactivate();
}
register_deactivation_hook( __FILE__, 'deactivate_gd_social_importer' );

/**
 * Include GD Social Importer main class file.
 *
 * @since 2.0.0
 */
include_once ( dirname( __FILE__).'/class-social-importer.php' );

/**
 * Check gd_social_importer function exists or not.
 *
 * @since 2.0.0
 *
 */
if( ! function_exists( 'gd_social_importer ' ) ) {
	/**
	 * Loads a single instance of GD Social Importer.
	 *
	 * @since 2.0.0
	 *
	 * @see GD_Social_Importer::get_instance()
	 *
	 * @return object GD_Social_Importer Returns an instance of the class.
	 */
	function gd_social_importer() {
		// min core version check
		if ( ! function_exists( "geodir_min_version_check" ) || ! geodir_min_version_check( "Social Importer", GEODIR_SOCIAL_IMPORTER_MIN_CORE ) ) {
			return '';
		}

		return GD_Social_Importer::get_instance();
	}
}
add_action( 'geodirectory_loaded', 'gd_social_importer', 10 );