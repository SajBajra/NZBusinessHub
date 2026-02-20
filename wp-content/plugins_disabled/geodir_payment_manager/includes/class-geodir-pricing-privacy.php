<?php
/**
 * Privacy/GDPR related functionality which ties into WordPress functionality.
 *
 * @since 2.5.0
 * @package GeoDir_Pricing_Manager
 * @author AyeCode Ltd
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * GeoDir_Pricing_Privacy class.
 */
class GeoDir_Pricing_Privacy extends GeoDir_Abstract_Privacy {

	public function __construct() {
		parent::__construct();

		// Initialize data exporters and erasers.
		add_action( 'init', array( $this, 'register_erasers_exporters' ) );

		add_filter( 'geodir_privacy_export_post_personal_data', array( __CLASS__, 'export_post_payment_data' ), 11, 2 );
	}

	/**
	 * Initial registration of privacy erasers and exporters.
	 *
	 * Due to the use of translation functions, this should run only after plugins loaded.
	 */
	public function register_erasers_exporters() {
		$this->name = __( 'GeoDirectory Pricing Manager', 'geodir_pricing' );
	}

	/**
	 * Export post payment data.
	 *
	 * @since 2.0.34
	 * @param array   $personal_data Array of name value pairs to expose in the export.
	 * @param object  $gd_post The post object.
	 * @return array  Filtered data.
	 */
	public static function export_post_payment_data( $personal_data, $gd_post ) {
		if ( ! empty( $gd_post->expire_date ) ) {
			$personal_data[] = array(
				'name'  => __( 'Post Expire Date', 'geodir_pricing' ),
				'value' => $gd_post->expire_date,
			);
		}
		if ( ! empty( $gd_post->package_id ) ) {
			$package_title = geodir_pricing_package_title( (int) $gd_post->package_id );
			$personal_data[] = array(
				'name'  => __( 'Post Package', 'geodir_pricing' ),
				'value' => ( ! empty( $package_title ) ? $package_title . ' ( ' . $gd_post->package_id . ' )' : $gd_post->package_id ),
			);
		}

		return $personal_data;
	}
}
