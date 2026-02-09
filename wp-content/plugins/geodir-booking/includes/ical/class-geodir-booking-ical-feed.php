<?php
/**
 * Main calendar feed Class.
 *
 * @package GeoDirectory
 * @subpackage Booking
 * @version 1.0.0
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Main calendar feed class.
 *
 * This class handles the initialization of the iCal feed and exports iCal data.
 *
 * @package GeoDirectory
 * @subpackage Booking
 * @since 1.0.0
 */
class GeoDir_Booking_Ical_Feed {

	/**
	 * Constructor.
	 * Hooks into WordPress init action to initialize the iCal feed.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'init_ical_feed' ) );
	}

	/**
	 * Initializes the iCal feed by adding a custom feed.
	 *
	 * @since 1.0.0
	 */
	public function init_ical_feed() {
		add_feed( 'gdbooking.ics', array( $this, 'export_ics' ) );
	}

	/**
	 * Export iCal data.
	 *
	 * Retrieves the listing ID from the request and exports the iCal data for the specified listing.
	 *
	 * @since 1.0.0
	 */
	public function export_ics() {
		$listing_id = isset( $_REQUEST['listing_id'] ) ? absint( (int) $_REQUEST['listing_id'] ) : 0;

		if ( empty( $listing_id ) ) {
			return;
		}

		$listing = get_post( $listing_id );
		if ( ! $listing ) {
			return;
		}

		$exporter = new GeoDir_Booking_Ical_Exporter();
		$exporter->export( $listing->ID );
	}
}
