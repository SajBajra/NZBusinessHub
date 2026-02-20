<?php
/**
 * Embed Admin.
 *
 * @since 2.0.0
 * @package GeoDir_Embed
 * @author AyeCode Ltd
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * GeoDir_Embed_Admin class.
 */
if ( ! class_exists( 'GeoDir_Embed_Admin', false ) ) :

	class GeoDir_Embed_Admin extends GeoDir_Settings_Page {

		/**
		 * Constructor.
		 */
		public function __construct() {
			add_filter( 'geodir_get_settings_pages', array( $this, 'load_settings_page' ), 22, 1 );
			add_action( 'geodir_pricing_package_settings', array( $this, 'pricing_package_settings' ), 22, 3 );
			add_action( 'geodir_pricing_process_data_for_save', array( $this, 'pricing_process_data_for_save' ), 1, 3 );
		}

		/**
		 * Loads the settings page into GeoDirectory Settings.
		 *
		 * @param $settings_pages
		 *
		 * @since 2.0.0
		 *
		 * @return array
		 */
		public static function load_settings_page( $settings_pages ) {

			$post_type = ! empty( $_REQUEST['post_type'] ) ? sanitize_title( $_REQUEST['post_type'] ) : 'gd_place';
			if ( !( isset( $_REQUEST['page'] ) && $_REQUEST['page'] == $post_type.'-settings' ) ) {
				$settings_pages[] = include_once( dirname( __FILE__ ) . "/class-geodir-embed-admin-settings.php" );
			}
			return $settings_pages;
		}

		/**
		 * Add embed enabled/disable option in package setting.
		 *
		 * @sicne 2.3.1
		 *
		 * @param array $settings Package settings.
		 * @param array $package_data Package data.
		 * @return array Package settings.
		 */
		public function pricing_package_settings( $settings, $package_data ) {
			$new_settings = array();

			foreach ( $settings as $key => $setting ) {
				if ( ! empty( $setting['id'] ) && $setting['id'] == 'package_features_settings' && ! empty( $setting['type'] ) && $setting['type'] == 'sectionend' ) {
					$new_settings[] = array(
						'type' => 'checkbox',
						'id' => 'package_no_embed_rating',
						'title'=> __( 'Disable Embed Rating', 'geodir-embed' ),
						'desc' => __( 'Disable embeddable reviews & ratings for the listings with this package.', 'geodir-embed' ),
						'std' => '0',
						'advanced' => true,
						'value'	=> ( ! empty( $package_data['no_embed_rating'] ) ? '1' : '0' )
					);
				}
				$new_settings[] = $setting;
			}

			return $new_settings;
		}

		/**
		 * Sanitize enabled option in package setting.
		 *
		 * @sicne 2.3.1
		 *
		 * @param array $package_data Package data to save.
		 * @param array $data Package request data.
		 * @param array $package Existing package data.
		 * @return array Package data to save.
		 */
		public function pricing_process_data_for_save( $package_data, $data, $package ) {
			if ( isset( $data['no_embed_rating'] ) ) {
				$package_data['meta']['no_embed_rating'] = ! empty( $data['no_embed_rating'] ) ? 1 : 0;
			} else if ( isset( $package['no_embed_rating'] ) ) {
				$package_data['meta']['no_embed_rating'] = (int) $package['no_embed_rating'];
			} else {
				$package_data['meta']['no_embed_rating'] = 0;
			}

			return $package_data;
		}
	}

endif;
return new GeoDir_Embed_Admin();
