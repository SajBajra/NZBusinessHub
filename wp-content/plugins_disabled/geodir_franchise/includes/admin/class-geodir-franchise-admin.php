<?php
/**
 * Franchise Manager Admin.
 *
 * @since 2.0.0
 * @package Geodir_Franchise
 * @author AyeCode Ltd
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * GeoDir_Franchise_Admin class.
 */
class GeoDir_Franchise_Admin {
    
    /**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'includes' ) );
		add_action( 'admin_init', array( $this, 'admin_redirects' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'load_scripts' ) );

		add_filter( 'post_row_actions', 'geodir_franchise_post_row_actions', 10, 2 );
		add_filter( 'geodir_get_settings_pages', array( $this, 'load_settings_page' ), 23, 1 );

		add_filter( 'geodir_db_cpt_default_columns', 'geodir_franchise_cpt_db_columns', 40, 3 );
		add_filter( 'geodir_uninstall_options', 'geodir_franchise_uninstall_settings', 40, 1 );
		add_filter( 'geodir_cpt_settings_tabs_custom_fields', 'geodir_franchise_franchise_cpt_tabs_settings', 40, 2 );
		add_filter( 'geodir_gd_options_for_translation', 'geodir_franchise_settings_to_translation', 40, 1 );
		add_filter( 'edit_form_top', array( $this, 'add_new_franchise_action' ), 20, 1 );

		self::post_type_filters();
	}

	/**
	 * Include any classes we need within admin.
	 */
	public function includes() {
		include_once( GEODIR_FRANCHISE_PLUGIN_DIR . 'includes/admin/admin-functions.php' );
	}

	/**
	 * Handle redirects to setup/welcome page after install and updates.
	 *
	 * For setup wizard, transient must be present, the user must have access rights, and we must ignore the network/bulk plugin updates.
	 */
	public function admin_redirects() {
		// Nonced plugin install redirects (whitelisted)
		if ( ! empty( $_GET['geodir-franchise-install-redirect'] ) ) {
			$plugin_slug = geodir_clean( $_GET['geodir-franchise-install-redirect'] );

			$url = admin_url( 'plugin-install.php?tab=search&type=term&s=' . $plugin_slug );

			wp_safe_redirect( $url );
			exit;
		}

		// Activation redirect
		if ( ! get_transient( '_geodir_franchise_activation_redirect' ) ) {
			return;
		}
	
		delete_transient( '_geodir_franchise_activation_redirect' );

		// Bail if activating from network, or bulk
		if ( is_network_admin() || isset( $_GET['activate-multi'] ) || apply_filters( 'geodir_franchise_prevent_activation_redirect', false ) ) {
			return;
		}

		wp_safe_redirect( admin_url( 'admin.php?page=gd-settings&tab=franchise' ) );
		exit;
	}

	public static function load_settings_page( $settings_pages ) {
		$post_type = ! empty( $_REQUEST['post_type'] ) ? sanitize_text_field( $_REQUEST['post_type'] ) : 'gd_place';

		if ( ! empty( $_REQUEST['page'] ) && $_REQUEST['page'] == $post_type . '-settings' ) {
		} else {
			$settings_pages[] = include( GEODIR_FRANCHISE_PLUGIN_DIR . 'includes/admin/settings/class-geodir-franchise-settings-franchise.php' );
		}

		return $settings_pages;
	}

	/**
	 * Enqueue styles.
	 */
	public static function register_styles() {
		// Register styles
		wp_register_style( 'geodir-franchise-admin', GEODIR_FRANCHISE_PLUGIN_URL . '/assets/css/admin.css', array( 'geodir-admin-css' ), GEODIR_FRANCHISE_VERSION );
	}

	/**
	 * Enqueue scripts.
	 */
	public function register_scripts() {
		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		// Register scripts
		wp_register_script( 'geodir-franchise-admin', GEODIR_FRANCHISE_PLUGIN_URL . '/assets/js/admin' . $suffix . '.js', array( 'jquery', 'geodir-admin-script' ), GEODIR_FRANCHISE_VERSION );
		wp_register_script( 'geodir-franchise-add', GEODIR_FRANCHISE_PLUGIN_URL . '/assets/js/add-franchise' . $suffix . '.js', array( 'jquery', 'geodir-add-listing' ), GEODIR_FRANCHISE_VERSION, true );
	}

	/**
	 * Load styles & scripts.
	 */
	public function load_scripts() {
		global $wp_query, $post, $pagenow;

		$screen       = get_current_screen();
		$screen_id    = $screen ? $screen->id : '';
		$gd_screen_id = sanitize_title( __( 'GeoDirectory', 'geodirectory' ) );
		$post_type    = ! empty( $_REQUEST['post_type'] ) ? sanitize_text_field( $_REQUEST['post_type'] ) : '';
		$page 		  = ! empty( $_GET['page'] ) ? $_GET['page'] : '';
		$suffix       = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		// Register styles
		self::register_styles();

		// Register scripts
		self::register_scripts();

		// Enqueue styles
		wp_enqueue_style( 'geodir-franchise-admin' );

		// Enqueue scripts
		if ( in_array( $screen_id, geodir_get_screen_ids() ) ) {
			wp_enqueue_script( 'geodir-franchise-admin' );
			wp_localize_script( 'geodir-franchise-admin', 'geodir_franchise_admin_params', geodir_franchise_admin_params() );
		}

		if ( 'edit.php' === $pagenow || 'post.php' === $pagenow || 'post-new.php' == $pagenow) {
			wp_enqueue_script( 'geodir-franchise-add' );
			wp_localize_script( 'geodir-franchise-add', 'geodir_franchise_params', geodir_franchise_params() );
		}
	}

	public static function post_type_filters() {
		$post_types = geodir_get_posttypes();

		if ( ! empty( $post_types ) ) {
			foreach ( $post_types as $post_type ) {
				if ( GeoDir_Post_types::supports( $post_type, 'franchise' ) ) {
					add_filter( "manage_edit-{$post_type}_columns", 'geodir_franchise_posts_columns', 200, 1 );
					add_filter( "manage_edit-{$post_type}_sortable_columns", 'geodir_franchise_posts_sortable_columns', 200, 1 );
					add_action( "manage_{$post_type}_posts_custom_column", 'geodir_franchise_posts_custom_column', 200, 2 );
				}
			}
		}
	}

	/**
	 * Show add new franchise button in backend edit page.
	 *
	 * @since 2.1.1.0
	 *
	 * @param object $post The post object.
	 * @return mixed
	 */
	public function add_new_franchise_action( $post = array() ) {
		if ( ! ( ! empty( $post->post_type ) && geodir_is_gd_post_type( $post->post_type ) && GeoDir_Post_types::supports( $post->post_type, 'franchise' ) ) ) {
			return;
		}

		if ( in_array( $post->post_status, geodir_get_publish_statuses( (array) $post ) ) && geodir_franchise_can_add_franchise( (int) $post->ID ) ) {
			echo '<a style="display:none" href="' . esc_url( geodir_franchise_admin_add_franchise_url( $post->ID ) ) . '" class="geodir-add-franchise-action">' . geodir_franchise_label( 'add_new_item', $post->post_type ) . '</a>';
		}
	}
}
