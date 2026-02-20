<?php
/**
 * Contains the main admin class.
 *
 */

defined( 'ABSPATH' ) || exit;

/**
 * The main admin class.
 */
class Adv_Admin {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'includes' ) );
		add_filter( 'aui_screen_ids', array( $this, 'load_aui' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'load_scripts' ) );
		add_action( 'admin_notices', array( $this, 'display_notices' ) );

		// Post types admin.
		Adv_Post_Types_Admin::init();
	}

	/**
	 * Displays admin notices.
	 */
	public function display_notices() {
		settings_errors( 'adv-notices' );
	}

	/**
	 * Include any classes we need within admin.
	 */
	public function includes() {
		include_once( plugin_dir_path( __FILE__ ) . 'admin-functions.php' );
		include_once( plugin_dir_path( __FILE__ ) . 'class-adv-admin-menus.php' );
	}

	/**
	 * Load AUI on our pages.
	 */
	public function load_aui( $screen_ids ) {
		return array_merge( $screen_ids, $this->get_screen_ids() );
	}

	/**
	 * Load Assets.
	 */
	public function load_scripts() {
		$screen = get_current_screen();

		if ( $screen && in_array( $screen->id, $this->get_screen_ids() ) ) {
			$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

			wp_register_script( 'adv-admin', ADVERTISING_PLUGIN_URL . 'assets/js/admin' . $suffix . '.js', array( 'jquery', 'select2' ), filemtime( ADVERTISING_PLUGIN_DIR . 'assets/js/admin' . $suffix . '.js' ) );

			wp_enqueue_script( 'adv-admin' );
			wp_enqueue_media();
			wp_localize_script( 'adv-admin', 'adv_admin_params', adv_admin_params() );
		}

	}

	/**
	 * Retrieves screen ids.
	 */
	public function get_screen_ids() {
		return array(
			'adv_zone',
			'edit-adv_zone',
			'adv_ad',
			'edit-adv_ad',
			'advertising_page_adv-settings'
		);
	}

}