<?php
/**
 * GeoDirectory Marketplace Admin general settings
 *
 * @package GeoDir_Marketplace
 * @author AyeCode Ltd
 * @since 2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * GeoDir_Marketplace_Settings_General class.
 */
if ( ! class_exists( 'GeoDir_Marketplace_Settings_General', false ) ) {

	class GeoDir_Marketplace_Settings_General extends GeoDir_Settings_Page {
		/**
		 * Constructor.
		 *
		 * @since 2.0
		 */
		public function __construct() {
			$this->id    = 'marketplace';
			$this->label = __( 'Marketplace', 'geomarketplace' );

			add_filter( 'geodir_settings_tabs_array', array( $this, 'add_settings_page' ), 25 );
			add_action( 'geodir_settings_' . $this->id, array( $this, 'output' ) );

			add_action( 'geodir_settings_save_' . $this->id, array( $this, 'save' ) );
			add_action( 'geodir_sections_' . $this->id, array( $this, 'output_sections' ) );

			add_action( 'geodir_settings_form_method_tab_' . $this->id, array( $this, 'form_method' ) );
		}

		/**
		 * Get sections.
		 *
		 * @since 2.0
		 *
		 * @return array
		 */
		public function get_sections() {
			$sections = array(
				'' => __( 'Settings', 'geomarketplace' ),
			);

			return apply_filters( 'geodir_get_sections_' . $this->id, $sections );
		}

		/**
		 * Output the settings.
		 *
		 * @since 2.0
		 */
		public function output() {
			global $current_section;

			$settings = $this->get_settings( $current_section );

			GeoDir_Admin_Settings::output_fields( $settings );
		}

		/**
		 * Save settings.
		 *
		 * @since 2.0
		 */
		public function save() {
			global $current_section;

			$settings = $this->get_settings( $current_section );

			GeoDir_Admin_Settings::save_fields( $settings );
		}

		/**
		 * Get settings array.
		 *
		 * @since 2.0
		 *
		 * @return array
		 */
		public function get_settings( $current_section = '' ) {
			global $aui_bs5;

			$settings = apply_filters( 'geodir_marketplace_settings', 
				array(
					array( 
						'name' => __( 'Marketplace Settings', 'geomarketplace' ),
						'type' => 'title', 
						'desc' => '', 
						'id' => 'geodir_marketplace_general_settings' 
					),
					array(
						'type' => 'multiselect',
						'id' => 'mp_post_type',
						'name' => __( 'Link Post Types', 'geomarketplace' ),
						'desc' => __( 'Select post types allowed to linking the listing with the product.', 'geomarketplace' ),
						'placeholder'=> __( 'Select Post Types...', 'geomarketplace' ),
						'options' => geodir_get_posttypes( 'options-plural' ),
						'default' => 'gd_place',
					    'class' => $aui_bs5 ? 'aui-select2' : 'geodir-select',
						'desc_tip' => true,
						'advanced' => false,
					),
					array(
						'type' => 'checkbox',
						'id' => 'mp_link_post',
						'name' => __( 'Allow Vendors to Link Listing?', 'geomarketplace' ),
						'desc' => __( 'Allow vendors to link a product to their listings from frontend vendor dashboard.', 'geomarketplace' ),
						'default' => '1',
						'advanced' => false
					),
					array(
						'type' => 'sectionend', 
						'id' => 'geodir_marketplace_general_settings'
					)
				)
			);

			return apply_filters( 'geodir_get_settings_' . $this->id, $settings, $current_section );
		}
		
		/**
		 * Form method.
		 *
		 * @since 2.0
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
}

return new GeoDir_Marketplace_Settings_General();
