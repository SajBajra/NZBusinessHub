<?php
/**
 * GeoDirectory Marketplace Admin installation class
 *
 * @package GeoDir_Marketplace
 * @author AyeCode Ltd
 * @since 2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * GeoDir_Marketplace_Admin_Install Class.
 */
class GeoDir_Marketplace_Admin_Install {
	/** @var array DB updates and callbacks that need to be run per version */
	private static $db_updates = array(
		/*'2.0' => array(
			'geodir_marketplace_update_2_0',
			'geodir_marketplace_update_2_0_db_version'
		)*/
	);

	private static $background_updater;

	/**
	 * Hook in tabs.
	 *
	 * @since 2.0
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'init_background_updater' ), 5 );
		add_action( 'init', array( __CLASS__, 'check_version' ), 5 );
		add_action( 'admin_init', array( __CLASS__, 'install_actions' ) );
		add_action( 'admin_init', array( __CLASS__, 'on_plugin_activation' ), 11 );
	}

	/**
	 * Init background updates
	 *
	 * @since 2.0
	 */
	public static function init_background_updater() {
		if ( ! class_exists( 'GeoDir_Background_Updater' ) ) {
			include_once( GEODIRECTORY_PLUGIN_DIR . 'includes/class-geodir-background-updater.php' );
		}

		self::$background_updater = new GeoDir_Background_Updater();
	}

	/**
	 * Check plugin version and run the updater if required.
	 *
	 * @since 2.0
	 *
	 * This check is done on all requests and runs if the versions do not match.
	 */
	public static function check_version() {
		if ( ! defined( 'IFRAME_REQUEST' ) ) {
			if ( get_option( 'geodir_marketplace_version' ) !== GEODIR_MARKETPLACE_VERSION ) {
				// Install
				self::install();

				do_action( 'geodir_marketplace_updated' );
			}
		}
	}

	/**
	 * Install actions when a update button is clicked within the admin area.
	 *
	 * @since 2.0
	 *
	 * This function is hooked into admin_init to affect admin only.
	 */
	public static function install_actions() {
		if ( ! empty( $_GET['do_update_geodir_marketplace'] ) ) {
			// Update
			self::update();
		}

		if ( ! empty( $_GET['force_update_geodir_marketplace'] ) ) {
			$blog_id = get_current_blog_id();

			// Used to fire an action added in WP_Background_Process::_construct() that calls WP_Background_Process::handle_cron_healthcheck().
			// This method will make sure the database updates are executed even if cron is disabled. Nothing will happen if the updates are already running.
			do_action( 'wp_' . $blog_id . '_geodir_marketplace_updater_cron' );

			wp_safe_redirect( admin_url( 'admin.php?page=gd-settings' ) );

			exit;
		}
	}

	/**
	 * Install plugin.
	 *
	 * @since 2.0
	 */
	public static function install() {
		global $wpdb;

		if ( ! is_blog_installed() ) {
			return;
		}

		if ( ! defined( 'GEODIR_MARKETPLACE_INSTALLING' ) ) {
			define( 'GEODIR_MARKETPLACE_INSTALLING', true );
		}

		// Save default options
		self::save_default_options();

		// Check and merge legacy data
		self::check_legacy_data();

		// Update GD version
		self::update_plugin_version();

		// Update DB version
		self::maybe_update_db_version();

		// Flush rules after install
		do_action( 'geodir_marketplace_flush_rewrite_rules' );

		// Trigger action
		do_action( 'geodir_marketplace_installed' );
	}

	/**
	 * Update plugin.
	 *
	 * @since 2.0
	 */
	private static function update() {
		$current_db_version = get_option( 'geodir_marketplace_db_version' );
		$update_queued = false;

		if ( empty( self::$background_updater ) ) {
			self::init_background_updater();
		}

		// Update callbacks.
		$update_callbacks = self::get_db_update_callbacks();

		if ( ! empty( $update_callbacks ) ) {
			foreach ( $update_callbacks as $version => $update_callbacks ) {
				if ( version_compare( $current_db_version, $version, '<' ) ) {
					foreach ( $update_callbacks as $update_callback ) {
						geodir_error_log( sprintf( 'Queuing %s - %s', $version, $update_callback ) );
						self::$background_updater->push_to_queue( $update_callback );
						$update_queued = true;
					}
				}
			}
		}

		if ( $update_queued ) {
			self::$background_updater->save()->dispatch();
		}
	}

	/**
	 * Check if new install.
	 *
	 * @since 2.0
	 *
	 * @return boolean
	 */
	private static function is_new_install() {
		return is_null( get_option( 'geodir_marketplace_version', null ) ) && is_null( get_option( 'geodir_marketplace_db_version', null ) );
	}

	/**
	 * Check if needs DB update.
	 *
	 * @since 2.0
	 *
	 * @return boolean
	 */
	private static function needs_db_update() {
		$current_db_version = get_option( 'geodir_marketplace_db_version', null );
		$updates = self::get_db_update_callbacks();

		return ! is_null( $current_db_version ) && ! empty( $updates ) && version_compare( $current_db_version, max( array_keys( $updates ) ), '<' );
	}

	/**
	 * See if we need to show or run database updates during install.
	 *
	 * @since 2.0
	 */
	private static function maybe_update_db_version() {
		if ( self::needs_db_update() ) {
			self::update();
		} else {
			self::update_db_version();
		}
	}

	/**
	 * Update plugin version to current.
	 *
	 * @since 2.0
	 */
	private static function update_plugin_version() {
		delete_option( 'geodir_marketplace_version' );

		add_option( 'geodir_marketplace_version', GEODIR_MARKETPLACE_VERSION );
	}

	/**
	 * Update plugin DB version.
	 *
	 * @since 2.0
	 *
	 * @param string $version
	 */
	public static function update_db_version( $version = null ) {
		delete_option( 'geodir_marketplace_db_version' );

		add_option( 'geodir_marketplace_db_version', is_null( $version ) ? GEODIR_MARKETPLACE_VERSION : $version );
	}

	/**
	 * Get list of DB update callbacks.
	 *
	 * @since  2.0
	 *
	 * @return array
	 */
	public static function get_db_update_callbacks() {
		return self::$db_updates;
	}

	/*
	 * Handle plugin activation.
	 *
	 * @since 2.0
	 */
	public static function on_plugin_activation() {
		if ( is_admin() && get_option( 'geodir_activate_marketplace' ) ) {
			delete_option( 'geodir_activate_marketplace' );

			// Handle marketplace activation.
			do_action( 'geodir_marketplace_activated' );
		}
	}

	/**
	 * Default options.
	 *
	 * Sets up the default options used on the settings page.
	 */
	private static function save_default_options() {
		$current_settings = geodir_get_settings();

		$settings = GeoDir_Marketplace_Admin::load_settings_page( array() );

		foreach ( $settings as $section ) {
			if ( ! method_exists( $section, 'get_settings' ) ) {
				continue;
			}
			$subsections = array_unique( array_merge( array( '' ), array_keys( $section->get_sections() ) ) );

			foreach ( $subsections as $subsection ) {
				$options = $section->get_settings( $subsection );
				if ( empty( $options ) ) {
					continue;
				}

				foreach ( $options as $value ) {
					if ( ! isset( $current_settings[ $value['id'] ] ) && isset( $value['default'] ) && isset( $value['id'] ) ) {
						geodir_update_option($value['id'], $value['default']);
					}
				}
			}
		}
	}

	public static function check_legacy_data() {
		global $wpdb;

		if ( get_option( 'geomp_shop_tab_post_type' ) ) {
			$shortcode = '[gd_marketplace per_page="' . (int) get_option( 'geomp_shop_tab_products_pagesize' ) . '" paginate="1"]';

			$wpdb->query( $wpdb->prepare( "INSERT INTO `{$wpdb->prefix}geodir_tabs_layout` SET `post_type` = '" . sanitize_text_field( get_option( 'geomp_shop_tab_post_type' ) ) . "', `sort_order` = " . (int) get_option( 'geomp_shop_tab_sort_order' ) . ", `tab_layout` = 'post', `tab_parent` = 0, `tab_type` = 'shortcode', `tab_level` = 0, `tab_name` = %s, `tab_icon` = 'fas fa-shopping-cart', `tab_key` = 'gdmp-shop', `tab_content` = %s", array( __( 'Shop', 'geomarketplace' ), $shortcode ) ) );

			delete_option( 'geomp_shop_tab_post_type' );
		}
	}
}
