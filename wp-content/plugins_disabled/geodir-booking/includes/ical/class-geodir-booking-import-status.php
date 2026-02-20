<?php
/**
 * Main import status Class.
 *
 * @package GeoDirectory
 * @subpackage Booking
 * @version 1.0.0
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Handles import status for GeoDirectory bookings.
 *
 * @package GeoDirectory
 * @subpackage Booking
 * @since   1.0.0
 */
class GeoDir_Booking_Import_Status {
	/**
	 * Indicates import failure.
	 */
	const FAILED = 0;

	/**
	 * Indicates successful import.
	 */
	const SUCCESS = 1;

	/**
	 * Indicates skipped import.
	 */
	const SKIPPED = 2;
}
