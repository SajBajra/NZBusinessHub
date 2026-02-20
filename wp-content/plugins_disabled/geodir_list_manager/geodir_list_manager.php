<?php
/**
 * GeoDirectory List Manager
 *
 * @package           GeoDir_List_Manager
 * @author            AyeCode Ltd
 * @copyright         2019 AyeCode Ltd
 * @license           GPLv3
 *
 * @wordpress-plugin
 * Plugin Name:       GeoDirectory List Manager
 * Plugin URI:        https://wpgeodirectory.com/downloads/list-manager/
 * Description:       Allows users to create and save their own special lists of listings.
 * Version:           2.3.9
 * Requires at least: 5.0
 * Requires PHP:      5.6
 * Author:            AyeCode Ltd
 * Author URI:        https://ayecode.io
 * License:           GPLv3
 * License URI:       http://www.gnu.org/licenses/gpl-3.0.html
 * Requires Plugins:  geodirectory
 * Text Domain:       gd-lists
 * Domain Path:       /languages
 * Update URL:        https://wpgeodirectory.com
 * Update ID:         69994
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

if ( ! defined( 'GD_LISTS_VERSION' ) ) {
	define( 'GD_LISTS_VERSION', "2.3.9" );
}

if ( ! defined( 'GD_LISTS_MIN_CORE' ) ) {
	define( 'GD_LISTS_MIN_CORE', '2.3' );
}

// Check user is_admin or not.
if ( is_admin() ) {
	include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

	// Check Geodirectory plugin not activate.
	if ( ! is_plugin_active( 'geodirectory/geodirectory.php' ) ) {
		deactivate_plugins( plugin_basename( __FILE__ ) );

		function gd_lists_requires_gd_plugin() {
			echo '<div class="notice notice-warning is-dismissible"><p><strong>' . sprintf( __( '%s requires the %sGeoDirectory%s plugin to be installed and active.', 'gd-lists' ), 'GeoDirectory Lists', '<a href="https://wpgeodirectory.com" target="_blank" title=" GeoDirectory">', '</a>' ) . '</strong></p></div>';
		}

		add_action( 'admin_notices', 'gd_lists_requires_gd_plugin' );
		return;
	}

	// Check posts to posts plugin not activate.
	if ( ! is_plugin_active( 'posts-to-posts/posts-to-posts.php' ) ) {
		function gd_lists_requires_ptop_plugin() {
			$action = 'install-plugin';
			$slug = 'posts-to-posts';
			$install_url = wp_nonce_url(
				add_query_arg(
					array(
						'action' => $action,
						'plugin' => $slug
					),
					admin_url( 'update.php' )
				),
				$action.'_'.$slug
			);

			if ( file_exists( WP_PLUGIN_DIR . '/posts-to-posts/posts-to-posts.php' ) ) {
				if ( is_plugin_inactive( 'posts-to-posts/posts-to-posts.php' ) ) {
					$activation_url = wp_nonce_url(admin_url( 'plugins.php?action=activate&plugin=posts-to-posts/posts-to-posts.php' ), 'activate-plugin_posts-to-posts/posts-to-posts.php');
					echo '<div class="notice notice-warning is-dismissible"><p><strong>' . wp_sprintf( __( '%s requires to install the %sPosts 2 Posts%s plugin to be installed and active. %sClick here to activate it.%s', 'gd-lists' ), 'GeoDirectory Lists', '<a href="https://wordpress.org/plugins/posts-to-posts/" target="_blank" title=" Posts 2 Posts">', '</a>', '<a href="'.esc_url($activation_url).'"  title=" Posts 2 Posts">', '</a>' ) . '</strong></p></div>';
				}
			} else {
				echo '<div class="notice notice-warning is-dismissible"><p><strong>' . wp_sprintf( __( '%s requires to install the %sPosts 2 Posts%s plugin to be installed and active. %sClick here to install it.%s', 'gd-lists' ), 'GeoDirectory Lists', '<a href="https://wordpress.org/plugins/posts-to-posts/" target="_blank" title=" Posts 2 Posts">', '</a>', '<a href="' . esc_url( $install_url ).'"  title=" Posts 2 Posts">', '</a>' ) . '</strong></p></div>';
			}
		}

		add_action( 'admin_notices', 'gd_lists_requires_ptop_plugin' );
		return;
	}

	// check ayecode_show_update_plugin_requirement function exists or not.
	if ( ! function_exists( 'ayecode_show_update_plugin_requirement' ) ) {
		function ayecode_show_update_plugin_requirement() {
			if ( ! defined( 'WP_EASY_UPDATES_ACTIVE' ) ) {
				echo '<div class="notice notice-warning is-dismissible"><p><strong>' . wp_sprintf( __( 'The plugin %sWP Easy Updates%s is required to check for and update some installed plugins, please install it now.', 'gd-lists' ), '<a href="https://wpeasyupdates.com/" target="_blank" title="WP Easy Updates">', '</a>' ).'</strong></p></div>';
			}
		}

		add_action( 'admin_notices', 'ayecode_show_update_plugin_requirement' );
	}
}

if ( ! defined( 'GD_LISTS_PLUGIN_FILE' ) ) {
	define( 'GD_LISTS_PLUGIN_FILE', __FILE__ );
}

/**
 * The File is responsible for defining all list general functions.
 *
 * @since 2.0.0
 */
require_once( plugin_dir_path( __FILE__ ) . '/includes/gd_lists_general_functions.php' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/geodir_list_manager_activate.php
 *
 * @since 2.0.0
 */
function activate_gd_lists() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/geodir_list_manager_activate.php';

	GD_Lists_Activate::activate();
}

register_activation_hook( __FILE__, 'activate_gd_lists' );

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/geodir_list_manager_deactivate.php
 *
 * @since 2.0.0
 */
function deactivate_gd_lists() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/geodir_list_manager_deactivate.php';

	GD_Lists_Deactivate::deactivate();
}

register_deactivation_hook( __FILE__, 'deactivate_gd_lists' );

/**
 * Include GD lists main class file.
 *
 * @since 2.0.0
 */
include_once ( dirname( __FILE__).'/includes/class-geodir-lists.php' );

/**
 * Loads a single instance of GD Lists.
 *
 * @since 2.0.0
 *
 * @see GeoDir_Lists::get_instance()
 *
 * @return object GeoDir_Lists Returns an instance of the class.
 */
function init_gd_lists() {
	// Min core version check
	if ( ! function_exists( 'geodir_min_version_check' ) || ! geodir_min_version_check( 'List Manager', GD_LISTS_MIN_CORE ) ) {
		return '';
	}

	return GeoDir_Lists::get_instance();
}

add_action('plugins_loaded','init_gd_lists', apply_filters('init_gd_lists_action', 10));
