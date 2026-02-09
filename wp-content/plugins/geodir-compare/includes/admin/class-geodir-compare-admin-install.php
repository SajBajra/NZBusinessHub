<?php
/**
 * Installation related functions and actions.
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Upgrades the database
 */
class Geodir_Compare_Admin_Install {

	/**
	 * Hook in tabs.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'check_version' ), 5 );
	}

	/**
	 * Check plugin version and run the updater as required.
	 *
	 * @since 2.0.0
	 */
	public static function check_version() {
		if ( ! defined( 'IFRAME_REQUEST' ) ) {
			if ( get_option( 'geodir_compare_version' ) !== GEODIR_COMPARE_VERSION ) {
				self::install();

				do_action( 'geodir_compare_updated' );
			}
		}
	}

	/**
	 * Check if fresh install.
	 *
	 * @since 2.0.0
	 * @return boolean
	 */
	private static function is_fresh_install() {
		return is_null( get_option( 'geodir_compare_version', null ) ) && is_null( get_option( 'geodir_compare_db_version', null ) );
	}

	/**
	 * Check if DB update needed.
	 *
	 * @since 2.0.0
	 * @return boolean
	 */
	private static function needs_db_update() {
		$db_version = get_option( 'geodir_compare_db_version', null );

		return ! is_null( $db_version ) && version_compare( $db_version, GEODIR_COMPARE_DB_VERSION, '<' );
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

		if ( ! defined( 'GEODIR_COMPARE_INSTALLING' ) ) {
			define( 'GEODIR_COMPARE_INSTALLING', true );
		}

		// Create pages.
		self::create_pages();

		// Update plugin version.
		self::update_plugin_version();

		// Update plugin DB version.
		self::update_plugin_db_version();

		do_action( 'geodir_compare_installed' );
	}

	/**
	 * Create pages.
	 */
	private static function create_pages() {
		$supports_blocks = geodir_is_gutenberg();

		/**
		 * Filters geodir_compare pages before they are created
		 */
		$pages = apply_filters( 'geodir_compare_create_pages', array(
			'compare' => array(
				'name'    => _x( 'compare', 'Page slug', 'geodir-compare' ),
				'title'   => _x( 'Compare Listings', 'Page title', 'geodir-compare' ),
				'content' => geodir_compare_page_content( false, $supports_blocks )
			)
		) );

		// Create the pages
		foreach ( $pages as $key => $page ) {
			geodir_create_page( esc_sql( $page['name'] ), 'geodir_compare_listings_page', $page['title'], $page['content'] );
		}

		delete_transient( 'geodir_cache_excluded_uris' );
	}

	/**
	 * Update plugin version.
	 *
	 * @since 2.0.0
	 */
	private static function update_plugin_version() {
		update_option( 'geodir_compare_version', GEODIR_COMPARE_VERSION );
	}

	/**
	 * Update plugin DB version.
	 *
	 * @since 2.0.0
	 */
	private static function update_plugin_db_version() {
		update_option( 'geodir_compare_db_version', GEODIR_COMPARE_DB_VERSION );
	}
}
