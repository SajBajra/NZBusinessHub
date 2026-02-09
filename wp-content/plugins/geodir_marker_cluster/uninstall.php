<?php
/**
 * GeoDirectory Marker Cluster Uninstall
 *
 * Un-installing Marker Cluster deletes marker cluster options.
 *
 * @author      AyeCode Ltd
 * @package     GeoDir_Marker_Cluster
 * @version     1.0.0.0
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

$geodir_settings = get_option( 'geodir_settings' );

if ( ( ! empty( $geodir_settings ) && ( ! empty( $geodir_settings['admin_uninstall'] ) || ! empty( $geodir_settings['uninstall_geodir_marker_cluster'] ) ) ) || ( defined( 'GEODIR_UNINSTALL_GEODIR_MARKER_CLUSTER' ) && true === GEODIR_UNINSTALL_GEODIR_MARKER_CLUSTER ) ) {
	if ( ! empty( $geodir_settings ) ) {
		$save_settings = $geodir_settings;

		// Remove plugin options
		$remove_options = array(
			'marker_cluster_type',
			'marker_cluster_size',
			'marker_cluster_zoom',
			'uninstall_geodir_marker_cluster',
		);

		foreach ( $remove_options as $option ) {
			if ( isset( $save_settings[ $option ] ) ) {
				unset( $save_settings[ $option ] );
			}
		}

		// Update options.
		update_option( 'geodir_settings', $save_settings );
	}
}