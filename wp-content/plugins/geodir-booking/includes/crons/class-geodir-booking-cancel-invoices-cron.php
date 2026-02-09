<?php
/**
 * Main cancel invoices cron Class.
 *
 * @package GeoDirectory
 * @subpackage Booking
 * @version 1.0.0
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Main class for cancelling invoices via cron job.
 *
 * @package GeoDirectory
 * @subpackage Booking
 * @version 1.0.0
 * @since   1.0.0
 */
class GeoDir_Booking_Cancel_Invoices_Cron extends GeoDir_Booking_Abstract_Cron {
	/**
	 * Retrieves the duration in minutes to hold bookings.
	 *
	 * @return int The duration in minutes to hold bookings.
	 */
	public static function get_held_duration() {
		return (int) geodir_booking_get_option( 'hold_booking_minutes', 15 );
	}

	/**
	 * Execute the cron job to cancel unpaid invoices after a certain duration.
	 */
	public function do_cron_job() {
		$held_duration = $this->get_held_duration();

		if ( empty( $held_duration ) ) {
			return;
		}

		$held_duration = absint( $held_duration );

		// Query unpaid invoices that have exceeded the hold duration
		$invoices = new WP_Query(
			apply_filters(
				'geodir_booking_cancellable_invoices_query',
				array(
					'date_query'    => array(
						'before' => "-$held_duration minutes",
					),
					'meta_query'    => array(
						'relation' => 'AND',
						array(
							'key'     => '_gd_booking_id',
							'compare' => 'EXISTS',
						),
					),
					'post_status'   => 'wpi-pending',
					'post_type'     => 'wpi_invoice',
					'fields'        => 'ids',
					'no_found_rows' => false,
				),
				$held_duration,
				$this
			)
		);

		// Cancel unpaid invoices that have exceeded the hold duration
		foreach ( $invoices->posts as $invoice_id ) {
			$invoice = new WPInv_Invoice( $invoice_id );

			if ( ! $invoice->is_paid() && apply_filters( 'geodir_booking_cancel_unpaid_invoice', true, $invoice ) ) {
				$invoice->update_status( 'wpi-cancelled', esc_html__( 'Unpaid invoice cancelled - time limit reached.', 'geodir-booking' ) );
			}
		}
	}
}
