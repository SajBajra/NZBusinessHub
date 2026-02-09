<?php
/**
 * GeoDirectory Marketplace AJAX class
 *
 * GeoDirectory Marketplace AJAX Event Handler.
 *
 * @package GeoDir_Marketplace
 * @author AyeCode Ltd
 * @since 2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * GeoDir_Marketplace_AJAX class.
 */
class GeoDir_Marketplace_AJAX {

	/**
	 * Hook in ajax handlers.
	 *
	 * @since 2.0
	 */
	public static function init() {
		self::add_ajax_events();
	}

	/**
	 * Hook in methods - uses WordPress ajax handlers (admin-ajax).
	 *
	 * @since 2.0
	 */
	public static function add_ajax_events() {
		// geodir_EVENT => nopriv
		$ajax_events = array(
			'marketplace_post_options' => false,
		);

		foreach ( $ajax_events as $ajax_event => $nopriv ) {
			add_action( 'wp_ajax_geodir_' . $ajax_event, array( __CLASS__, $ajax_event ) );

			if ( $nopriv ) {
				add_action( 'wp_ajax_nopriv_geodir_' . $ajax_event, array( __CLASS__, $ajax_event ) );
			}

			// GeoDir AJAX can be used for frontend ajax requests.
			add_action( 'geodir_ajax_geodir_' . $ajax_event, array( __CLASS__, $ajax_event ) );
		}
	}

	/**
	 * Get user post options.
	 *
	 * @since 2.0
	 */
	public static function marketplace_post_options() {
		// Security
		check_ajax_referer( 'geodir_basic_nonce', 'security' );

		GeoDir_Marketplace_WooCommerce::handle_ajax_post_options( $_POST );

		wp_die();
	}
}