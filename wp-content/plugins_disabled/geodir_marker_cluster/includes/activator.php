<?php

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      2.0.0
 * @package    geodir_marker_cluster
 * @subpackage geodir_marker_cluster/includes
 * @author     GeoDirectory Team <info@wpgeodirectory.com>
 */

class GeoDir_Marker_Cluster_Activator {

	/**
	 * Runs when plugin activated.
	 *
	 * @since    2.0.0
	 */
	public static function activate( $network_wide = false ) {
		do_action( 'geodir_marker_cluster_activate', $network_wide );
	}

	/**
	 * Runs when plugin deactivated.
	 *
	 * @since    2.0.0
	 */
	public static function deactivate() {
		do_action( 'geodir_marker_cluster_deactivate' );
	}
}