<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * GeoDirectory GeoDir_Defaults.
 *
 * A place to store default values used in many places.
 *
 * @class    GeoDir_Defaults
 * @package  GeoDirectory/Classes
 * @category Class
 * @author   AyeCode
 */
class GD_Duplicate_Alert_Defaults extends GeoDir_Defaults{

	/**
	 * The default add_listing meta description.
	 *
	 * @return string
	 */
	public static function duplicate_alert_validation_message( $post_type = '', $field_name = '' ) {
		if ( $field_name ) {
			$message = wp_sprintf( __( 'A listing with this %s already exists! Please make sure you are not adding a duplicate entry.' ,'geodir-duplicate-alert' ), $field_name );
		} else {
			$message = __( 'A listing with this field already exists! Please make sure you are not adding a duplicate entry.' ,'geodir-duplicate-alert' );
		}

		return apply_filters( 'geodir_duplicate_alert_default_message', $message, $field_name );
	}
}
