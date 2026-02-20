<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    GeoDirectory_Marker_Cluster
 * @subpackage GeoDirectory_Marker_Cluster/admin
 * @author     GeoDirectory <info@wpgeodirectory.com>
 */
class GeoDir_Marker_Cluster_Admin {

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    2.0.0
	 */
	public function __construct() {
	}

	public function admin_enqueue_scripts( $hook ) {
	}

	public function load_settings_page( $settings_pages ) {
		$post_type = ! empty( $_REQUEST['post_type'] ) ? sanitize_title( $_REQUEST['post_type'] ) : 'gd_place';

		if ( ! ( isset( $_REQUEST['page'] ) && $_REQUEST['page'] == $post_type . '-settings' ) ) {
			$settings_pages[] = include( GEODIR_MARKERCLUSTER_PLUGINDIR_PATH . '/admin/settings/class-geodir-marker-cluster-settings.php' );
		}

		return $settings_pages;
	}

}
