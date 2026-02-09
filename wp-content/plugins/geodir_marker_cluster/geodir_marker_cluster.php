<?php
/**
 * GeoDirectory Marker Cluster
 *
 * @package           GeoDir_Marker_Cluster
 * @author            AyeCode Ltd
 * @copyright         2019 AyeCode Ltd
 * @license           GPLv3
 *
 * @wordpress-plugin
 * Plugin Name:       GeoDirectory Marker Cluster
 * Plugin URI:        https://wpgeodirectory.com/downloads/marker-cluster/
 * Description:       Combine map markers of close proximity into clusters and simplify the display of markers on the map.
 * Version:           2.3.3
 * Requires at least: 5.0
 * Requires PHP:      5.6
 * Author:            AyeCode Ltd
 * Author URI:        https://ayecode.io
 * License:           GPLv3
 * License URI:       http://www.gnu.org/licenses/gpl-3.0.html
 * Requires Plugins:  geodirectory
 * Text Domain:       geodir_markercluster
 * Domain Path:       /languages
 * Update URL:        https://wpgeodirectory.com
 * Update ID:         65859
 */

// If this file is called directly, bail.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/* Define Constants */
if ( ! defined( 'GEODIR_MARKERCLUSTER_VERSION' ) ) {
	define( 'GEODIR_MARKERCLUSTER_VERSION', '2.3.3' );
}

if ( ! defined( 'GEODIR_MARKERCLUSTER_MIN_CORE' ) ) {
	define( 'GEODIR_MARKERCLUSTER_MIN_CORE', '2.3' );
}

define( 'GEODIR_MARKERCLUSTER_PLUGIN_FILE', __FILE__ );
define( 'GEODIR_MARKERCLUSTER_PLUGINDIR_PATH', WP_PLUGIN_DIR . '/' . plugin_basename( dirname( __FILE__ ) ) );
define( 'GEODIR_MARKER_PLUGINDIR_URL', plugins_url( '', __FILE__ ) );

if ( is_admin() ) {
	if ( ! function_exists( 'is_plugin_active' ) ) {
		include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
	}

	if ( ! function_exists( 'ayecode_show_update_plugin_requirement' ) ) {
		require_once( 'gd_update.php' );
	}

	if ( ! is_plugin_active( 'geodirectory/geodirectory.php' ) ) {
		deactivate_plugins( plugin_basename( __FILE__ ) );

		function geodir_marker_cluster_requires_gd_plugin() {
			echo '<div class="notice notice-warning is-dismissible"><p><strong>' . sprintf( __( '%s requires %sGeoDirectory%s plugin to be installed and active.', 'geodir_markercluster' ), 'GeoDirectory Marker Cluster', '<a href="https://wordpress.org/plugins/geodirectory/" target="_blank">', '</a>' ) . '</strong></p></div>';
		}

		add_action( 'admin_notices', 'geodir_marker_cluster_requires_gd_plugin' );
		return;
	}
}

require plugin_dir_path(__FILE__) . 'includes/class-geodir-marker-cluster.php';

function activate_gd_marker_cluster(){
	require_once( 'includes/activator.php' );

	GeoDir_Marker_Cluster_Activator::activate();
}
register_activation_hook( __FILE__ , 'activate_gd_marker_cluster' );

function deactivate_gd_marker_cluster(){
	require_once('includes/activator.php');

	GeoDir_Marker_Cluster_Activator::deactivate();
}
register_deactivation_hook( __FILE__ , 'deactivate_gd_marker_cluster' );

function init_gd_marker_cluster() {
	// Min core version check
	if ( ! function_exists( 'geodir_min_version_check' ) || ! geodir_min_version_check( 'Marker Cluster', GEODIR_MARKERCLUSTER_MIN_CORE ) ) {
		return '';
	}

	GeoDir_Marker_Cluster::get_instance();
}
add_action( 'plugins_loaded', 'init_gd_marker_cluster', apply_filters( 'gd_marker_cluster_action_priority', 10 ) );