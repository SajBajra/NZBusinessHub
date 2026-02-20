<?php
/**
 * Main iCal auto sync cron Class.
 *
 * @package GeoDirectory
 * @subpackage Booking
 * @version 1.0.0
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Main class for executing iCal auto sync via cron job.
 *
 * @package GeoDirectory
 * @subpackage Booking
 * @version 1.0.0
 * @since   1.0.0
 */
class GeoDir_Booking_Ical_Auto_Sync_Cron extends GeoDir_Booking_Abstract_Cron {
	/**
	 * Execute the cron job to sync iCal for bookable listings.
	 */
	public function do_cron_job() {
		// Get bookable listing IDs
		$listings_query = geodir_booking_get_bookable_listings_query(
			array(
				'post_status' => geodir_get_post_stati( 'public', array( 'post_type' => 'gd_place' ) ),
				'posts_per_page' => -1,
				'fields' => 'ids', 
				'order'  => 'ASC',
				'_gdbooking_context' => 'ical_auto_sync'
			)
		);

		$listing_ids = $listings_query->get_posts();

		// Initiate iCal sync for bookable listings
		GeoDir_Booking_Queued_Sync::instance()->sync( $listing_ids );

		// Update option to indicate that iCal auto sync has worked at least once
		update_option( 'geodir_booking_ical_auto_sync_worked_once', true, 'no' );
	}
}
