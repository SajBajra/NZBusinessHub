<?php
/**
 * Dynamic User Emails admin Class
 *
 * @author    AyeCode Ltd
 * @package   GeoDir_Dynamic_Emails
 * @version   2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * GeoDir_Dynamic_Emails_Admin class.
 */
class GeoDir_Dynamic_Emails_Admin {
	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'init' ) );
		add_action( 'admin_init', array( $this, 'admin_init' ) );
		add_action( 'geodir_clear_version_numbers', array( $this, 'clear_version_number' ), 45 );

		add_filter( 'geodir_gd_options_for_translation', array( $this, 'options_for_translation' ), 45, 1 );
		add_filter( 'geodir_uninstall_options', array( $this, 'uninstall_data' ), 45, 1 );
	}

	/**
	 * Handle init.
	 */
	public function init() {
		GeoDir_Dynamic_Emails_Admin_Settings::init();
	}

	/**
	 * Handle admin init.
	 */
	public function admin_init() {
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ), 51 );
	}

	/**
	 * Deletes the version number from the DB so install functions will run again.
	 */
	public function clear_version_number() {
		delete_option( 'geodir_dynamic_emails_version' );
	}

	/**
	 * Adds options for translations that requires translation.
	 *
	 * @since 2.0.0
	 *
	 * @param array $options GD settings option names.
	 * @return array Modified option names.
	 */
	public function options_for_translation( $options = array() ) {
		$plugin_options = array();

		$options = array_merge( $options, $plugin_options );

		return $options;
	}

	/**
	 * Add the plugin uninstall data settings.
	 *
	 * @since 2.0.0
	 *
	 * @return array $settings the settings array.
	 * @return array The modified settings.
	 */
	public function uninstall_data( $settings ) {
		array_pop( $settings );

		$settings[] = array(
			'name' => __( 'Dynamic User Emails', 'geodir-dynamic-emails' ),
			'desc' => __( 'Tick to completely remove all of its data when Dynamic User Emails is deleted.', 'geodir-dynamic-emails' ),
			'id' => 'uninstall_geodir_dynamic_emails',
			'type' => 'checkbox',
		);

		$settings[] = array( 
			'type' => 'sectionend',
			'id' => 'uninstall_options'
		);

		return $settings;
	}

	public function admin_scripts() {
		$design_style = geodir_design_style();
		$tab = ! empty( $_GET['tab'] ) ? $_GET['tab'] : '';

		if ( ! ( $design_style && $tab == 'dynamic-emails' ) ) {
			return;
		}

		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		wp_register_script( 'geodir-dynamic-emails-admin', GEODIR_DYNAMIC_EMAILS_PLUGIN_URL . '/assets/js/admin' . $suffix . '.js', array( 'jquery', 'geodir-admin-script' ), GEODIR_DYNAMIC_EMAILS_VERSION );

		wp_enqueue_script( 'geodir-dynamic-emails-admin' );
		wp_localize_script( 'geodir-dynamic-emails-admin', 'geodirDynamicEmailsAdmin', geodir_dynamic_emails_admin_params() );
	}
}

return new GeoDir_Dynamic_Emails_Admin();