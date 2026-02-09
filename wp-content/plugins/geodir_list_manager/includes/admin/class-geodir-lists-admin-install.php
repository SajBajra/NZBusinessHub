<?php
/**
 * Installation related functions and actions.
 *
 * @author   AyeCode
 * @category Admin
 * @package  GeoDirectory/Classes
 * @version  2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * GeoDir_Admin_Install Class.
 */
class GeoDir_Lists_Admin_Install {


	/**
	 * Hook in tabs.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'check_version' ), 5 );
	}

	/**
	 * Check GeoDirectory location manager version and run the updater as required.
	 *
	 * This check is done on all requests and runs if the versions do not match.
	 */
	public static function check_version() {
		if ( ! defined( 'IFRAME_REQUEST' ) ) {
			if ( self::is_v2_upgrade() ) {
				// v2 upgrade
			} else if ( get_option( 'geodir_lists_version' ) !== GD_LISTS_VERSION ) {
				self::install();
				do_action( 'geodir_lists_updated' );
			}
		}
	}

	/**
	 * Is v1 to v2 upgrade.
	 *
	 * @since 2.0.0
	 * @return boolean
	 */
	private static function is_v2_upgrade() {
		if ( get_option( 'geodirectory_db_version' ) && version_compare( get_option( 'geodirectory_db_version' ), '2.0.0.0', '<' ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Install GeoDirectory Lists Manager.
	 */
	public static function install() {
		if ( ! is_blog_installed() ) {
			return;
		}

		if ( ! defined( 'GEODIR_LISTS_INSTALLING' ) ) {
			define( 'GEODIR_LISTS_INSTALLING', true );
		}

		// Set default options
		self::save_default_options();

		// Create default pages
		self::create_pages();

		// Update GD version
		self::update_version();

		// Flush rules after install
		wp_schedule_single_event( time(), 'geodir_flush_rewrite_rules' );

		// Trigger action
		do_action( 'geodir_lists_installed' );
	}

	/**
	 * Default options.
	 *
	 * Sets up the default options used on the settings page.
	 */
	private static function save_default_options() {
		$current_settings = geodir_get_settings();

		// install CPT settings if not installed
		if ( ! isset( $current_settings['list_post_type'] ) ) {
			$cpt_settings = GeoDir_Lists_CPT::post_type_args();
			if ( ! empty( $cpt_settings ) ) {
				geodir_update_option( 'list_post_type', $cpt_settings );
			}
		}
	}

	/**
	 * Update GeoDirectory version to current.
	 */
	private static function update_version() {
		delete_option( 'geodir_lists_version' );

		add_option( 'geodir_lists_version', GD_LISTS_VERSION );
	}

	/**
	 * Create new ADD LIST page when plugin activate.
	 *
	 * @since 2.0.0
	 */
	public static function create_pages() {
		$gutenberg = geodir_is_gutenberg();

		$pages = apply_filters( 'geodir_list_create_pages', array(
			'list_page_single' => array(
				'name'    => _x( 'gd-single–lists', 'Page slug', 'gd-lists' ),
				'title'   => _x( 'GD Single - Lists', 'Page title', 'gd-lists' ),
				'content' => geodir_list_page_single_content( false, $gutenberg ),
			),
		) );

		foreach ( $pages as $key => $page ) {
			geodir_create_page( esc_sql( $page['name'] ), $key , $page['title'], $page['content'] );
		}

		delete_transient( 'geodir_cache_excluded_uris' );
	}
}
