<?php
/**
 * Embed Admin Settings.
 *
 * @since 2.0.0
 * @package GeoDir_Embed
 * @author AyeCode Ltd
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * GeoDir_Embed_Settings class.
 */
if ( ! class_exists( 'GeoDir_Embed_Admin_Settings', false ) ) :

	class GeoDir_Embed_Admin_Settings extends GeoDir_Settings_Page {

		/**
		 * Constructor.
		 */
		public function __construct() {
			$this->id    = 'embed';
			$this->label = __( 'Embed', 'geodir-embed' );

			add_filter( 'geodir_settings_tabs_array', array( $this, 'add_settings_page' ), 22 );
			add_action( 'geodir_settings_' . $this->id, array( $this, 'output' ) );
//			add_action( 'geodir_sections_' . $this->id, array( $this, 'output_toggle_advanced' ) );
			add_action( 'geodir_settings_save_' . $this->id, array( $this, 'save' ) );
			add_action( 'geodir_sections_' . $this->id, array( $this, 'output_sections' ) );
			add_action( 'geodir_settings_form_method_tab_' . $this->id, array( $this, 'form_method' ) );
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
			$settings = apply_filters( 'geodir_embed_options',
				array(
					array(
						'name' => __( 'Embed Settings', 'geodir-embed' ),
						'type' => 'title',
						'desc' => '',
						'id'   => 'geodir_embed_settings'
					),
					array(
						'id'       => 'embed_user_border_color',
						'name'     => __( 'Border color (user set)', 'geodir-embed' ),
						'desc'     => __( 'This allows the user to adjust the setting while building the embed code.', 'geodir-embed' ),
						'type'     => 'checkbox',
						'default'  => '0',
						'desc_tip' => false,
						'advanced' => true,
					),
					array(
						'id'       => 'embed_border_color',
						'name'     => __( 'Border color', 'geodir-embed' ),
						'desc'     => __( 'The color of the border for the embed.', 'geodir-embed' ),
						'default'  => '#ff9900',
						'type'     => 'color',
						'desc_tip' => true,
						'advanced' => false,
					),

					// border width
					array(
						'id'       => 'embed_user_border_width',
						'name'     => __( 'Border width (user set)', 'geodir-embed' ),
						'desc'     => __( 'This allows the user to adjust the setting while building the embed code.', 'geodir-embed' ),
						'type'     => 'checkbox',
						'default'  => '1',
						'desc_tip' => false,
						'advanced' => true,
					),
					array(
						'id'       => 'embed_border_width',
						'name'     => __( 'Border width', 'geodir-embed' ),
						'desc'     => __( 'The width of the border for the embed.', 'geodir-embed' ),
						'default'  => '2',
						'type'     => 'number',
						'desc_tip' => true,
						'advanced' => false,
					),

					// border radius
					array(
						'id'       => 'embed_user_border_radius',
						'name'     => __( 'Border radius (user set)', 'geodir-embed' ),
						'desc'     => __( 'This allows the user to adjust the setting while building the embed code.', 'geodir-embed' ),
						'type'     => 'checkbox',
						'default'  => '1',
						'desc_tip' => false,
						'advanced' => true,
					),
					array(
						'id'       => 'embed_border_radius',
						'name'     => __( 'Border radius', 'geodir-embed' ),
						'desc'     => __( 'Set the border radius.', 'geodir-embed' ),
						'type'     => 'number',
						'default'  => '0',
						'desc_tip' => true,
						'advanced' => true,
					),

					// border shadow
					array(
						'id'       => 'embed_user_border_shadow',
						'name'     => __( 'Border shadow (user set)', 'geodir-embed' ),
						'desc'     => __( 'This allows the user to adjust the setting while building the embed code.', 'geodir-embed' ),
						'type'     => 'checkbox',
						'default'  => '1',
						'desc_tip' => false,
						'advanced' => true,
					),
					array(
						'id'       => 'embed_border_shadow',
						'name'     => __( 'Border shadow', 'geodir-embed' ),
						'desc'     => __( 'Set if the border shadow should be shown', 'geodir-embed' ),
						'type'     => 'checkbox',
						'default'  => '0',
						'desc_tip' => false,
						'advanced' => false,
					),

					// background color
					array(
						'id'       => 'embed_user_background',
						'name'     => __( 'Background color (user set)', 'geodir-embed' ),
						'desc'     => __( 'This allows the user to adjust the setting while building the embed code.', 'geodir-embed' ),
						'type'     => 'checkbox',
						'default'  => '1',
						'desc_tip' => false,
						'advanced' => true,
					),
					array(
						'id'       => 'embed_background',
						'name'     => __( 'Background color', 'geodir-embed' ),
						'desc'     => __( 'Select the background color to use.', 'geodir-embed' ),
						'default'  => '#FFFFFF',
						'type'     => 'color',
						'desc_tip' => true,
						'advanced' => true,
					),


					// link color
					array(
						'id'       => 'embed_user_link_color',
						'name'     => __( 'Link color (user set)', 'geodir-embed' ),
						'desc'     => __( 'This allows the user to adjust the setting while building the embed code.', 'geodir-embed' ),
						'type'     => 'checkbox',
						'default'  => '0',
						'desc_tip' => false,
						'advanced' => true,
					),
					array(
						'id'       => 'embed_link_color',
						'name'     => __( 'Link color', 'geodir-embed' ),
						'desc'     => __( 'The color of the links in the embed.', 'geodir-embed' ),
						'default'  => '#353535',
						'type'     => 'color',
						'desc_tip' => true,
						'advanced' => false,
					),

					// text color
					array(
						'id'       => 'embed_user_text_color',
						'name'     => __( 'Text color (user set)', 'geodir-embed' ),
						'desc'     => __( 'This allows the user to adjust the setting while building the embed code.', 'geodir-embed' ),
						'type'     => 'checkbox',
						'default'  => '0',
						'desc_tip' => false,
						'advanced' => true,
					),
					array(
						'id'       => 'embed_text_color',
						'name'     => __( 'Text color', 'geodir-embed' ),
						'desc'     => __( 'The color of the text in the embed.', 'geodir-embed' ),
						'default'  => '#7d7d7d',
						'type'     => 'color',
						'desc_tip' => true,
						'advanced' => false,
					),
					array(
						'id'       => 'embed_user_link_ratings',
						'name'     => __( 'Link Ratings', 'geodir-embed' ),
						'desc'     => __( 'Allows user to link ratings to the listing reviews tab.', 'geodir-embed' ),
						'type'     => 'checkbox',
						'default'  => '1',
						'desc_tip' => false,
						'advanced' => true,
					),

					// branding (prob not something we want to let users set)
					array(
						'id'          => 'embed_branding_text',
						'name'        => __( 'Branding text', 'geodir-embed' ),
						'desc'        => __( 'If no logo is set then this is the branding text that will be show.', 'geodir-embed' ),
						'default'     => site_url(),
						'placeholder' => site_url(),
						'type'        => 'text',
						'desc_tip'    => true,
						'advanced'    => false,
					),
					array(
						'id'         => 'embed_logo',
						'name'       => __( 'Embed logo', 'geodir-embed' ),
						'desc'       => __( 'The logo to show on the embed (this replaces the branding text).', 'geodir-embed' ),
						'type'       => 'image',
						'image_size' => 'full',
						'desc_tip'   => true,
						'advanced'   => false,
					),

					array(
						'id'          => 'embed_cdn_url',
						'name'        => __( 'CDN URL', 'geodir-embed' ),
						'desc'        => __( 'Here you can enter a Content Delivery Network (CDN) url, using a CDN can reduce the load on your server but will reduce the update freshness of the rating info to that of your CDN settings (make sure to set and expire time).', 'geodir-embed' ),
						'default'     => '',
						'placeholder' => 'https://mysite.cdn.com/',
						'type'        => 'text',
						'desc_tip'    => true,
						'advanced'    => true,
					),


					array(
						'type' => 'sectionend',
						'id'   => 'geodir_embed_settings'
					),
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

return new GeoDir_Embed_Admin_Settings();