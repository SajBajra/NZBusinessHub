<?php
/**
 * Franchise Manager Admin Settings.
 *
 * @since 2.0.0
 * @package Geodir_Franchise
 * @author AyeCode Ltd
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * GeoDir_Franchise_Settings_Franchise class.
 */
if ( ! class_exists( 'GeoDir_Franchise_Settings_Franchise', false ) ) :

	class GeoDir_Franchise_Settings_Franchise extends GeoDir_Settings_Page {

		/**
		 * Constructor.
		 */
		public function __construct() {
			$this->id    = 'franchise';
			$this->label = __( 'Franchise Manager', 'geodir-franchise' );

			add_filter( 'geodir_settings_tabs_array', array( $this, 'add_settings_page' ), 23 );
			add_action( 'geodir_settings_' . $this->id, array( $this, 'output' ) );
			add_action( 'geodir_sections_' . $this->id, array( $this, 'output_sections' ) );
			//add_action( 'geodir_sections_' . $this->id, array( $this, 'output_toggle_advanced' ) );
			add_action( 'geodir_settings_form_method_tab_' . $this->id, array( $this, 'form_method' ) );
			add_action( 'geodir_settings_save_' . $this->id, array( $this, 'save' ) );
		}

		/**
		 * Get sections.
		 *
		 * @return array
		 */
		public function get_sections() {
			$sections = array(
				'' => __( 'Settings', 'geodir-franchise' ),
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
			$settings = apply_filters( 'geodir_franchise_options', 
				array(
					array(
						'name' => __( 'Franchise Manager Settings', 'geodir-franchise' ),
						'type' => 'title', 
						'desc' => '', 
						'id' => 'geodir_franchise_manager_settings' 
					),
					array(
						'type' => 'checkbox',
						'id'   => 'franchise_show_main',
						'name' => __( 'Show main listing?', 'geodir-franchise' ),
						'desc' => __( 'This will display the main listing in the list of its franchises.', 'geodir-franchise' ),
						'default' => '1',
						'advanced' => false
					),
					array(
						'type' => 'checkbox',
						'id'   => 'franchise_show_viewing',
						'name' => __( 'Show viewing franchise?', 'geodir-franchise' ),
						'desc' => __( 'This will display current viewing franchise in list of franchises.', 'geodir-franchise' ),
						'default' => '1',
						'advanced' => false
					),
					array(
						'type' => 'sectionend', 
						'id' => 'geodir_franchise_manager_settings'
					),
					array(
						'name' => __( 'Map Filter', 'geodir-franchise' ),
						'type' => 'title', 
						'desc' => '', 
						'id' => 'geodir_franchise_map_settings' 
					),
					array(
						'type' => 'checkbox',
						'id'   => 'franchise_map_show_main',
						'name' => __( 'Show main listing on map?', 'geodir-franchise' ),
						'desc' => __( 'Display the main listing with its franchises on the map.', 'geodir-franchise' ),
						'default' => '1',
						'advanced' => false
					),
					array(
						'type' => 'checkbox',
						'id'   => 'franchise_map_show_viewing',
						'name' => __( 'Show viewing franchise on map?', 'geodir-franchise' ),
						'desc' => __( 'Display the current viewing franchise with its franchises on the map.', 'geodir-franchise' ),
						'default' => '1',
						'advanced' => false
					),
					array(
						'type' => 'sectionend', 
						'id' => 'geodir_franchise_map_settings'
					)
				)
			);
			return apply_filters( 'geodir_get_settings_' . $this->id, $settings, $current_section );
		}
		
		/**
		 * Form method.
		 *
		 * @param  string $method
		 *
		 * @return string
		 */
		public function form_method( $method ) {
			global $current_section;

			return 'post';
		}
	}

endif;

return new GeoDir_Franchise_Settings_Franchise();
