<?php
/**
 * Main minimized logger Class.
 *
 * @package GeoDirectory
 * @subpackage Booking
 * @version 1.0.0
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Handles the mini display of process logs and statistics.
 *
 * @package GeoDirectory
 * @subpackage Booking
 * @version 1.0.0
 * @since   1.0.0
 */
class GeoDir_Booking_Mini_Logger extends GeoDir_Booking_Logger {

	public function log( $status, $message ) {
		// Skip info messages
		if ( 'info' !== $status ) {
			parent::log( $status, $message );
		}
	}
}
