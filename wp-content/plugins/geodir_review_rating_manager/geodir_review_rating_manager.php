<?php
/**
 * GeoDirectory Review Rating Manager
 *
 * @package           Geodir_Review_Rating_Manager
 * @author            AyeCode Ltd
 * @copyright         2019 AyeCode Ltd
 * @license           GPLv3
 *
 * @wordpress-plugin
 * Plugin Name:       GeoDirectory Review Rating Manager
 * Plugin URI:        https://wpgeodirectory.com/downloads/multiratings-and-reviews/
 * Description:       With our slick multi-ratings and reviews manager, you can turn your site into a professional reviews directory.
 * Version:           2.3.12
 * Requires at least: 5.0
 * Requires PHP:      5.6
 * Author:            AyeCode Ltd
 * Author URI:        https://ayecode.io
 * License:           GPLv3
 * License URI:       http://www.gnu.org/licenses/gpl-3.0.html
 * Requires Plugins:  geodirectory
 * Text Domain:       geodir_reviewratings
 * Domain Path:       /languages
 * Update URL:        https://wpgeodirectory.com
 * Update ID:         65876
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

if ( ! defined( 'GEODIR_REVIEWRATING_VERSION' ) ) {
	define( 'GEODIR_REVIEWRATING_VERSION', '2.3.12' );
}

if ( ! defined( 'GEODIR_REVIEWRATING_MIN_CORE' ) ) {
	define( 'GEODIR_REVIEWRATING_MIN_CORE', '2.3' );
}

define( 'GEODIR_REVIEWRATING_PLUGIN_FILE', __FILE__ );
define( 'GEODIR_REVIEWRATING_PLUGINDIR_PATH', WP_PLUGIN_DIR . '/' . plugin_basename( dirname( __FILE__ ) ) );
define( 'GEODIR_REVIEWRATING_PLUGINDIR_URL', plugins_url( '', __FILE__ ) );

global $wpdb;

if ( is_admin() ) {
	if ( ! function_exists( 'is_plugin_active' ) ) {
		/**
		 * Include WordPress plugin core file to use core functions to check for active plugins.
		 */
		include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
	}

	// GEODIRECTORY UPDATE CHECKS
	if ( ! function_exists( 'ayecode_show_update_plugin_requirement' ) ) {
		require_once('gd_update.php');
	}

	if ( ! is_plugin_active( 'geodirectory/geodirectory.php' ) ) {
		deactivate_plugins( plugin_basename( __FILE__ ) );

		function geodir_review_rating_requires_gd_plugin() {
			echo '<div class="notice notice-warning is-dismissible"><p><strong>' . wp_sprintf( __( '%s requires %sGeoDirectory%s plugin to be installed and active.', 'geodir_reviewratings' ), 'GeoDirectory Review Rating Manager', '<a href="https://wordpress.org/plugins/geodirectory/" target="_blank">', '</a>' ) . '</strong></p></div>';
		}

		add_action( 'admin_notices', 'geodir_review_rating_requires_gd_plugin' );
		return;
	}
}

/* Tables Constants */
if ( ! defined( 'GEODIR_REVIEWRATING_STYLE_TABLE' ) ) define('GEODIR_REVIEWRATING_STYLE_TABLE', $wpdb->prefix . 'geodir_rating_style' );
if ( ! defined( 'GEODIR_REVIEWRATING_CATEGORY_TABLE' ) ) define('GEODIR_REVIEWRATING_CATEGORY_TABLE', $wpdb->prefix . 'geodir_rating_category');
if ( ! defined( 'GEODIR_REVIEWRATING_POSTREVIEW_TABLE' ) ) define('GEODIR_REVIEWRATING_POSTREVIEW_TABLE', $wpdb->prefix . 'geodir_post_review' );
if ( ! defined( 'GEODIR_COMMENTS_REVIEWS_TABLE' ) ) define('GEODIR_COMMENTS_REVIEWS_TABLE', $wpdb->prefix . 'geodir_comments_reviews' );

require plugin_dir_path(__FILE__) . 'includes/class-geodir-review-rating.php';

require_once( 'gd_upgrade.php' );

//register_activation_hook(__FILE__ , 'activate_gd_review_rating');

function activate_gd_review_rating( $network_wide ) {
	require_once( 'includes/activator.php' );

	GeoDir_Review_Rating_Manager_Activator::activate();
}

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since 1.0
 */
function init_gd_review_rating() {
	global $geodir_review_rating_manager;

	// Min core version check
	if ( ! function_exists( 'geodir_min_version_check' ) || ! geodir_min_version_check( 'Ratings Manager', GEODIR_REVIEWRATING_MIN_CORE ) ) {
		return '';
	}

	return $geodir_review_rating_manager = GeoDir_Review_Rating_Manager::get_instance();
}
add_action( 'plugins_loaded', 'init_gd_review_rating', apply_filters( 'gd_review_rating_action_priority', 10 ) );