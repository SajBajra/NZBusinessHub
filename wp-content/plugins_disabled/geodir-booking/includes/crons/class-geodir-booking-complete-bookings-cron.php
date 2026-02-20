<?php
/**
 * Main complete bookings cron Class.
 *
 * @package GeoDirectory
 * @subpackage Booking
 * @version 1.0.0
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Main class for completing bookings via cron job.
 *
 * @package GeoDirectory
 * @subpackage Booking
 * @version 1.0.0
 * @since   1.0.0
 */
class GeoDir_Booking_Complete_Bookings_Cron extends GeoDir_Booking_Abstract_Cron {
	/**
	 * Execute the cron job to complete past bookings.
	 */
	public function do_cron_job() {
		global $wpdb;

		// Get past bookings that need to be completed
		$past_bookings = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT id FROM {$wpdb->prefix}gdbc_bookings WHERE `status` IN ( 'confirmed', 'pending_payment', 'pending_confirmation' ) AND `end_date` < %s",
				gmdate( 'Y-m-d' )
			),
			ARRAY_A
		);

		// Loop through past bookings and complete them
		foreach ( $past_bookings as $booking ) {
			$booking = new GeoDir_Customer_Booking( (int) $booking['id'] );

			// Check if completing the booking is allowed
			if ( apply_filters( 'geodir_booking_complete_booking', true, $booking ) ) {
				$booking->complete(); // Complete the booking
			}
		}
	}
}
