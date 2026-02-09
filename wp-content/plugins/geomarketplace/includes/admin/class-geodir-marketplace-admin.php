<?php
/**
 * GeoDirectory Marketplace Admin
 *
 * @package GeoDir_Marketplace
 * @author AyeCode Ltd
 * @since 2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * GeoDir_Marketplace_Admin class.
 */
class GeoDir_Marketplace_Admin {
	/**
	 * Constructor.
	 *
	 * @since 2.0
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'includes' ) );
		add_action( 'geodir_clear_version_numbers', array( $this, 'clear_version_number' ), 80 );

		add_filter( 'geodir_get_settings_pages', array( __CLASS__, 'load_settings_page' ), 24, 1 );
		add_filter( 'geodir_uninstall_options', array( $this, 'uninstall_options' ), 80, 1 );
	}

	/**
	 * Include any classes we need within admin.
	 *
	 * @since 2.0
	 */
	public function includes() {
		include_once( GEODIR_MARKETPLACE_PLUGIN_DIR . 'includes/admin/admin-functions.php' );
	}

	/**
	 * Deletes the version number from the DB so install functions will run again.
	 *
	 * @since 2.0
	 */
	public function clear_version_number(){
		delete_option( 'geodir_marketplace_version' );
	}

	/**
	 * Load settings page.
	 *
	 * @since 2.0
	 */
	public static function load_settings_page( $settings_pages ) {
		$post_type = ! empty( $_REQUEST['post_type'] ) ? sanitize_text_field( $_REQUEST['post_type'] ) : 'gd_place';

		if ( ! empty( $_REQUEST['page'] ) && $_REQUEST['page'] == $post_type . '-settings' ) {
		} else {
			$settings_pages[] = include( GEODIR_MARKETPLACE_PLUGIN_DIR . 'includes/admin/settings/class-geodir-marketplace-settings-general.php' );
		}

		return $settings_pages;
	}

	/**
	 * Add the plugin to uninstall settings.
	 *
	 * @since 2.0
	 *
	 * @return array $settings the settings array.
	 * @return array The modified settings.
	 */
	public function uninstall_options( $settings ) {
		array_pop( $settings );

		$settings[] = array(
			'name'     => __( 'Marketplace', 'geomarketplace' ),
			'desc'     => __( 'Check this box if you would like to completely remove all of its data when Marketplace is deleted.', 'geomarketplace' ),
			'id'       => 'uninstall_geodir_marketplace',
			'type'     => 'checkbox',
		);

		$settings[] = array( 
			'type' => 'sectionend',
			'id' => 'uninstall_options'
		);

		return $settings;
	}
}
