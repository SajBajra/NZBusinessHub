<?php
/**
 * Main calendar exporter Class.
 *
 * @package GeoDirectory
 * @subpackage Booking
 * @version 1.0.0
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

use GeoDir\Bookings\Libraries\iCalendar\ZCiCal;
use GeoDir\Bookings\Libraries\iCalendar\ZCiCalNode;
use GeoDir\Bookings\Libraries\iCalendar\ZCiCalDataNode;

/**
 * Main calendar exporter class for exporting iCal data.
 *
 * @package GeoDirectory
 * @subpackage Booking
 * @version 1.0.0
 * @since   1.0.0
 */
class GeoDir_Booking_Ical_Exporter {
	/**
	 * Export iCal data for a given room ID.
	 *
	 * @param int $room_id The ID of the room to export iCal data for.
	 */
	public function export( $room_id ) {
		// Time when calendar was created. Format: "Ymd\THis\Z"
		$datestamp = ZCiCal::fromUnixDateTime() . 'Z';

		// Create calendar
		$calendar = new GeoDir_Booking_Ical();
		$calendar->remove_method_property(); // Remove property METHOD

		// Change default PRODID
		$prodid = '-//' . geodir_booking_site_domain() . '//GeoDirectory Booking ' . GEODIR_BOOKING_VERSION;
		$calendar->set_prodid( $prodid );

		// Fill the calendar with events
		$bookings = $this->pull_bookings( $room_id );
		$this->add_bookings( $calendar, $datestamp, $bookings, $room_id );

		$post_name = get_post_field( 'post_name', $room_id, 'raw' );
		// %domain%-%name%-%date%.ics - booking.dev-comfort-triple-room-1-20170710.ics
		$filename = geodir_booking_site_domain() . '-' . $post_name . '-' . date( 'Ymd' ) . '.ics';

		header( 'Content-type: text/calendar; charset=utf-8' );
		header( 'Content-Disposition: inline; filename=' . $filename );
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $calendar->export();
	}

	/**
	 * Pull bookings for the given room ID.
	 *
	 * @param int $room_id The ID of the room to pull bookings for.
	 * @return array Array of bookings.
	 */
	protected function pull_bookings( $room_id ) {
		$bookings = geodir_get_bookings(
			array(
				'listings'      => array( (int) $room_id ),
				'start_date'    => date( 'Y-m-d H:i:s', 0 ),
				'end_date'      => '2036-01-01 00:00:01', // Max year for 32 bit systems
			)
		);

		return $bookings;
	}

	/**
	 * Add bookings to the calendar.
	 *
	 * @param object $calendar The calendar object to add bookings to.
	 * @param string $datestamp The datestamp for the calendar.
	 * @param array $bookings Array of bookings to add.
	 * @param int $room_id The ID of the room.
	 */
	protected function add_bookings( $calendar, $datestamp, $bookings, $room_id ) {
		$export_imports = (bool) geodir_booking_get_option( 'ical_export_imports', false );

		foreach ( $bookings as $booking ) {
			// Don't export imported bookings. If Sync ID is set means it was imported.
			if ( ! empty( $booking->sync_id ) && ! $export_imports ) {
				continue;
			}

			$summary     = $this->create_summary( $booking );
			$description = $this->create_description( $booking, $room_id );

			if ( $booking->listing_id == $room_id ) {
				$event = new ZCiCalNode( 'VEVENT', $calendar->curnode );

				// If UID = null, then it did not exist on import
				if ( ! empty( $booking->uid ) ) {
					$event->addNode( new ZCiCalDataNode( 'UID:' . $booking->uid ) );
				}

				$start_date = new DateTime( $booking->start_date );
				$end_date   = new DateTime( $booking->end_date );

				$event->addNode( new ZCiCalDataNode( 'DTSTART;VALUE=DATE:' . ZCiCal::fromSqlDateTime( $start_date->format( 'Y-m-d' ) ) ) );
				$event->addNode( new ZCiCalDataNode( 'DTEND;VALUE=DATE:' . ZCiCal::fromSqlDateTime( $end_date->format( 'Y-m-d' ) ) ) );
				$event->addNode( new ZCiCalDataNode( 'DTSTAMP:' . $datestamp ) );
				$event->addNode( new ZCiCalDataNode( 'SUMMARY:' . $summary ) );

				// ZCiCal library can limit DESCRIPTION by 80 characters, so some of the content can be pushed on the next line
				$event->addNode( new ZCiCalDataNode( 'DESCRIPTION:' . $description ) );
			}
		}
	}

	/**
	 * Create a summary for the booking.
	 *
	 * @param GeoDir_Customer_Booking $booking The booking object.
	 * @return string The summary for the booking.
	 */
	protected function create_summary( $booking ) {
		$summary = $booking->ical_summary;

		if ( ! empty( $summary ) ) {
			// Remove "", added on import
			$summary = substr( $summary, 1, -1 );
		} else {
			$summary = trim( sprintf( '%s (%d)', $booking->name, $booking->id ) );
		}

		return $summary;
	}

	/**
	 * Create a description for the booking.
	 *
	 * @param GeoDir_Customer_Booking $booking The booking object.
	 * @param int $room_id The ID of the room.
	 * @return string The description for the booking.
	 */
	protected function create_description( $booking, $room_id ) {
		$description = $booking->ical_description;

		if ( ! empty( $description ) ) {
			$description = substr( $description, 1, -1 ); // Remove "", added on import
			$description = str_replace( PHP_EOL, '\n', $description );
		} else {
			$start_date = new DateTime( $booking->start_date );
			$end_date   = new DateTime( $booking->end_date );

			$check_in  = $start_date->format( 'Y-m-d' );
			$check_out = $end_date->format( 'Y-m-d' );
			$nights    = self::calc_nights( $start_date, $end_date );

			$description = sprintf( 'CHECKIN: %s\nCHECKOUT: %s\nNIGHTS: %d\n', $check_in, $check_out, $nights );

			$property_name = get_the_title( $room_id );
			if ( ! empty( $property_name ) ) {
				$description .= sprintf( 'PROPERTY: %s\n', $property_name );
			}
		}

		return $description;
	}

	/**
	 * Calculate the number of nights between two dates.
	 *
	 * @param \DateTime $check_in_date The check-in date.
	 * @param \DateTime $check_out_date The check-out date.
	 * @return int The number of nights.
	 */
	public static function calc_nights( \DateTime $check_in_date, \DateTime $check_out_date ) {
		$from = clone $check_in_date;
		$to   = clone $check_out_date;

		// set same time to dates
		$from->setTime( 0, 0, 0 );
		$to->setTime( 0, 0, 0 );

		$diff = $from->diff( $to );

		return (int) $diff->format( '%r%a' );
	}
}
