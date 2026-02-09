<?php
/**
 * Main iCal Class.
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
 * Main iCal class.
 *
 * This class extends ZCiCal class to handle iCal functionality.
 *
 * @package GeoDirectory
 * @subpackage Booking
 * @since 1.0.0
 */
class GeoDir_Booking_Ical extends ZCiCal {
	/**
	 * The one true instance of GeoDir_Booking_Ical.
	 *
	 * @var GeoDir_Booking_Ical|null
	 */
	private static $instance = null;

	/**
	 * Get the one true instance of GeoDir_Booking_Ical.
	 *
	 * @since 1.0.0
	 * @return GeoDir_Booking_Ical
	 */
	public static function instance() {
		if ( ! self::$instance ) {
			self::$instance = new GeoDir_Booking_Ical();
		}

		return self::$instance;
	}

	/**
	 * Retrieves all events.
	 *
	 * @since 1.0.0
	 * @return ZCiCalNode[] Array of events.
	 */
	public function get_events() {
		if ( 0 === $this->countEvents() ) {
			return array();
		}

		$events = array();

		$event = $this->getFirstEvent();
		while ( $event ) {
			$events[] = $event;
			$event    = $this->getNextEvent( $event );
		}

		return $events;
	}

	/**
	 * Retrieves data of all events.
	 *
	 * @since 1.0.0
	 *
	 * @param int $listing_id The ID of the listing.
	 * @return array Array of event data.
	 */
	public function get_events_data( $listing_id ) {
		$events = $this->get_events();
		$prodid = $this->get_prodid();
		$values = array();

		foreach ( $events as $event ) {
			$parsed = $this->parse_event( $event );

			// Is parsed valid?
			if ( isset( $parsed['check_in'] ) && isset( $parsed['check_out'] ) ) {
				$parsed['prodid']     = $prodid;
				$parsed['listing_id'] = $listing_id;
				$values[]             = $parsed;
			}
		}

		return $values;
	}

	/**
	 * Parses an event.
	 *
	 * @since 1.0.0
	 *
	 * @param ZCiCalNode $event The event to parse.
	 * @return array Event values.
	 */
	private function parse_event( $event ) {
		$values = array(
			'uid'         => null,
			'summary'     => '',
			'description' => '',
		);

		foreach ( $event->data as $name => $node ) {
			$name  = strtoupper( $name );
			$value = $node->getValues();

			// Convert all dates from format "DATE:20170818" into "2017-08-18".
			if ( $name == 'DTSTART' || $name == 'DTEND' ) {
				preg_match( '/(\d{4})(\d{2})(\d{2})/', $value, $date );
				if ( ! empty( $date ) ) {
					array_shift( $date );
					$value = implode( '-', $date );
				} else {
					continue;
				}
			}

			switch ( $name ) {
				case 'UID':
					$values['uid'] = $value;
					break;

				case 'DTSTART':
					$values['check_in'] = $value;

					if ( isset( $values['check_out'] ) ) {
						break;
					}

				case 'DTEND':
					$values['check_out'] = $value;
					break;

				case 'SUMMARY':
					$values['summary'] = '"' . $value . '"';
					break;

				case 'DESCRIPTION':
					$values['description'] = '"' . $value . '"';
					break;
			}
		}

		return $values;
	}

	/**
	 * Retrieves the PRODID.
	 *
	 * @since 1.0.0
	 * @return string The PRODID.
	 */
	public function get_prodid() {
		return isset( $this->tree->data['PRODID'] ) ? $this->tree->data['PRODID']->getValues() : '';
	}

	/**
	 * Sets the PRODID.
	 *
	 * @since 1.0.0
	 *
	 * @param string $prodid The PRODID to set.
	 */
	public function set_prodid( $prodid ) {
		if ( isset( $this->tree->data['PRODID'] ) ) {
			$prodid_node        = $this->curnode->data['PRODID'];
			$prodid_node->value = array( $prodid );
		} else {
			$prodid_node = new ZCiCalDataNode( 'PRODID:' . $prodid );

			$this->curnode->data[ $prodid_node->getName() ] = $prodid_node;
		}
	}

	/**
	 * Removes the METHOD property.
	 *
	 * @since 1.0.0
	 */
	public function remove_method_property() {
		if ( isset( $this->curnode->data['METHOD'] ) ) {
			unset( $this->curnode->data['METHOD'] );
		}
	}
}
