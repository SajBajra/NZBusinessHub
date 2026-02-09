<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'Geodir_Marker_Cluster_Settings', false ) ) {

	class Geodir_Marker_Cluster_Settings extends GeoDir_Settings_Page {

		/**
		 * Constructor.
		 */
		public function __construct() {
			$this->id    = 'marker_cluster';
			$this->label = __( 'Marker Cluster', 'geodir_markercluster' );

			add_filter( 'geodir_settings_tabs_array', array( $this, 'add_settings_page' ), 25 );
			add_action( 'geodir_settings_' . $this->id, array( $this, 'output' ) );

			add_action( 'geodir_settings_save_' . $this->id, array( $this, 'save' ) );
			add_action( 'geodir_sections_' . $this->id, array( $this, 'output_sections' ) );

			add_filter( 'geodir_uninstall_options', array($this, 'uninstall_options'), 10, 1);
		}

		/**
		 * Get sections.
		 *
		 * @return array
		 */
		public function get_sections() {
			$sections = array(
				'' => __( 'General', 'geodir_markercluster' ),
			);

			return apply_filters( 'geodir_get_sections_' . $this->id, $sections );
		}

		/**
		 * Output the settings.
		 */
		public function output() {
			global $current_section;

			$settings = $this->get_settings( $current_section );

			GeoDir_Admin_Settings::output_fields( $settings );
		}

		/**
		 * Save settings.
		 */
		public function save() {
			global $current_section;

			$settings = $this->get_settings( $current_section );

			GeoDir_Admin_Settings::save_fields( $settings );
		}

		/**
		 * Get settings array.
		 *
		 * @return array
		 */
		public function get_settings( $current_section = '' ) {
			$settings = apply_filters( 'geodir_marker_cluster_general_options', array(
				array(
					'name' => __( 'Marker Cluster Settings', 'geodir_markercluster' ),
					'type' => 'sectionstart',
					'id' => 'marker_cluster_settings'
				),
				array(
					'name' => __( 'Cluster Type', 'geodir_markercluster' ),
					'desc' 	=> __('The type of clustering to use. Client side is better and faster for small directories, server side is better for large directories.', 'geodir_markercluster' ),
					'id' => 'marker_cluster_type',
					'type' => 'select',
					'default' => 'client',
					'options' => array(
						'0' => __( 'Disabled', 'geodir_markercluster' ),
						'client' => __( 'Client side', 'geodir_markercluster' ),
						'server' => __( 'Server side', 'geodir_markercluster' ),
					),
					'desc_tip' => true,
				),
				array(
					'name' => __( 'Grid Size', 'geodir_markercluster' ),
					'desc' 	=> __('The grid size of a cluster in pixel. Each cluster will be a square. If you want the algorithm to run faster, you can set this value larger. Default value 60.', 'geodir_markercluster' ),
					'id' => 'marker_cluster_size',
					'type' => 'select',
					'default' => '60',
					'options' => array(
						'10' => __( '10', 'geodir_markercluster' ),
						'20' => __( '20', 'geodir_markercluster' ),
						'30' => __( '30', 'geodir_markercluster' ),
						'40' => __( '40', 'geodir_markercluster' ),
						'50' => __( '50', 'geodir_markercluster' ),
						'60' => __( '60', 'geodir_markercluster' ),
						'70' => __( '70', 'geodir_markercluster' ),
						'80' => __( '80', 'geodir_markercluster' ),
						'90' => __( '90', 'geodir_markercluster' ),
						'100' => __( '100', 'geodir_markercluster' ),
					),
					'desc_tip' => true,
				),
				array(
					'name' => __( 'Max Zoom', 'geodir_markercluster' ),
					'desc' 	=> __('The max zoom level monitored by a marker cluster. When maxZoom is reached or exceeded all markers will be shown without cluster. Default value 15.', 'geodir_markercluster' ),
					'id' => 'marker_cluster_zoom',
					'type' => 'select',
					'default' => '15',
					'options' => array(
						'1' => __( '1', 'geodir_markercluster' ),
						'2' => __( '2', 'geodir_markercluster' ),
						'3' => __( '3', 'geodir_markercluster' ),
						'4' => __( '4', 'geodir_markercluster' ),
						'5' => __( '5', 'geodir_markercluster' ),
						'6' => __( '6', 'geodir_markercluster' ),
						'7' => __( '7', 'geodir_markercluster' ),
						'8' => __( '8', 'geodir_markercluster' ),
						'9' => __( '9', 'geodir_markercluster' ),
						'10' => __( '10', 'geodir_markercluster' ),
						'11' => __( '11', 'geodir_markercluster' ),
						'12' => __( '12', 'geodir_markercluster' ),
						'13' => __( '13', 'geodir_markercluster' ),
						'14' => __( '14', 'geodir_markercluster' ),
						'15' => __( '15', 'geodir_markercluster' ),
						'16' => __( '16', 'geodir_markercluster' ),
						'17' => __( '17', 'geodir_markercluster' ),
						'18' => __( '18', 'geodir_markercluster' ),
						'19' => __( '19', 'geodir_markercluster' ),
					),
					'desc_tip' => true,
				),

				array( 'type' => 'sectionend', 'id' => 'marker_cluster_settings' ),
			) );

			return apply_filters( 'geodir_get_settings_' . $this->id, $settings, $current_section );
		}

		public function choose_maps() {
			$home_map_widgets = geodir_get_option( 'widget_geodir_map_v3_home_map' );

			$map_canvas_arr = array();

			if ( ! empty( $home_map_widgets ) ) {
				foreach ( $home_map_widgets as $key => $value ) {
					if ( is_numeric( $key ) ) {
						$map_canvas_arr['geodir_map_v3_home_map_' . $key] = 'geodir_map_v3_home_map_' . $key;
					}
				}
			}

			return apply_filters('geodir_map_marker_cluster_choose_maps', $map_canvas_arr);
		}

		public function uninstall_options($settings){
			array_pop( $settings );

			$settings[] = array(
				'name'     => __( 'Marker Cluster', 'geodir_markercluster' ),
				'desc'     => __( 'Check this box if you would like to completely remove all of its data when Marker Cluster is deleted.', 'geodir_markercluster' ),
				'id'       => 'uninstall_geodir_marker_cluster',
				'type'     => 'checkbox',
			);

			$settings[] = array( 'type' => 'sectionend', 'id' => 'uninstall_options' );

			return $settings;
		}
	}
}

return new Geodir_Marker_Cluster_Settings();
