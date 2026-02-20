<?php
/**
 * Per day ruleset class.
 *
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Per day ruleset class.
 *
 */
class GeoDir_Booking_Day_Rule implements JsonSerializable {

	/**
	 * Rule ID.
	 *
	 * @var int
	 */
	public $id = 0;

	/**
	 * Listing ID.
	 *
	 * @var int
	 */
	public $listing_id = 0;

	/**
	 * Checks if the date is available.
	 *
	 * @var bool
	 */
	public $is_available = true;

	/**
	 * The rule date.
	 *
	 * @var string
	 */
	public $rule_date = null;

	/**
	 * Per night price.
	 *
	 * @var float
	 */
	public $nightly_price = null;

	/**
	 * Private note.
	 *
	 * @var string
	 */
	public $private_note = '';

	/**
	 * The Booking Sync ID.
	 *
	 * @var string
	 */
	public $sync_id;

	/**
	 * The Booking Sync Queue ID.
	 *
	 * @var string
	 */
	public $sync_queue_id;

	/**
	 * The Booking UID.
	 *
	 * @var string
	 */
	public $uid;

	/**
	 * The Ical ProdID.
	 *
	 * @var string
	 */
	public $ical_prodid;

	/**
	 * The Ical Summary.
	 *
	 * @var string
	 */
	public $ical_summary;

	/**
	 * The Ical Description.
	 *
	 * @var string
	 */
	public $ical_description;

	/**
	 * Checkin date.
	 *
	 * @var string
	 */
	public $checkin_date;

	/**
	 * Checkin date formatted.
	 *
	 * @var string
	 */
	public $checkin_formatted;

	/**
	 * Checkout date.
	 *
	 * @var string
	 */
	public $checkout_date;

	/**
	 * Checkout date formatted.
	 *
	 * @var string
	 */
	public $checkout_formatted;

	/**
	 * Inits the ruleset from the provided args.
	 *
	 * @param array $args Args.
	 */
	public function __construct( $args ) {
		foreach ( $args as $key => $value ) {

			switch ( $key ) {

				// IDs.
				case 'id':
				case 'listing_id':
				case 'sync_queue_id':
					$this->$key = absint( $value );
					break;

				// Dates.
				case 'rule_date':
					$this->$key = empty( $value ) ? null : gmdate( 'Y-m-d', strtotime( $value ) );
					break;

				// Texts.
				case 'private_note':
					$this->$key = esc_html( $value );
					break;

				case 'uid':
				case 'sync_id':
				case 'ical_prodid':
				case 'ical_summary':
				case 'ical_description':
					$this->$key = empty( $value ) ? '' : sanitize_text_field( $value );
					break;

				// Booleans.
				case 'is_available':
					$this->$key = ! empty( $value );
					break;

				// Floats.
				case 'nightly_price':
					$this->$key = max( geodir_booking_night_min_price(), floatval( $value ) );
					break;

				default:
					break;
			}
		}
	}

	/**
	 * Returns whether or not this day rule is upcoming.
	 *
	 * @return bool
	 */
	public function is_upcoming() {
		return strtotime( $this->rule_date ) > strtotime( 'today 00:00:00' );
	}

	/**
	 * Return the ruleset as an array.
	 */
	public function to_array() {
		return get_object_vars( $this );
	}

	/**
	 * Return the ruleset for use in JSON.
	 *
	 * @return array
	 */
	#[\ReturnTypeWillChange]
	public function jsonSerialize() {
		return $this->to_array();
	}

	/**
	 * Save the ruleset.
	 *
	 * @return array|WP_Error
	 */
	public function save() {

		global $wpdb;

		// Check that we have a rule date.
		if ( ! $this->rule_date ) {
			return new WP_Error( 'missing_dates', __( 'Missing rule date.', 'geodir-booking' ) );
		}

		// Check that we have a listing id.
		if ( ! $this->listing_id ) {
			return new WP_Error( 'missing_listing_id', __( 'Missing listing id.', 'geodir-booking' ) );
		}

		// If we have no id, try fetching one using the rule date and listing id.
		if ( ! $this->id ) {
			$this->id = (int) $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}gdbc_day_rules WHERE listing_id = %d AND rule_date = %s", $this->listing_id, $this->rule_date ) );
		}

		// Prepare data.
		$data = array(
			'listing_id'       => $this->listing_id,
			'private_note'     => $this->private_note,
			'rule_date'        => $this->rule_date,
			'is_available'     => (int) $this->is_available,
			'nightly_price'    => $this->nightly_price,
			'uid'              => $this->uid,
			'sync_id'          => $this->sync_id,
			'sync_queue_id'    => $this->sync_queue_id,
			'ical_prodid'      => $this->ical_prodid,
			'ical_summary'     => $this->ical_summary,
			'ical_description' => $this->ical_description,
		);

		// Prepare formats.
		$formats = array(
			'%d', // listing_id
			'%s', // private_note
			'%s', // rule_date
			'%d', // is_available
			'%f', // nightly_price
			'%s', // uid
			'%s', // sync_id
			'%d', // sync_queue_id
			'%s', // ical_prodid
			'%s', // ical_summary
			'%s', // ical_description
		);

		if ( $this->id ) {
			$result = $wpdb->update( $wpdb->prefix . 'gdbc_day_rules', $data, array( 'id' => $this->id ), $formats, array( '%d' ) );
		} else {

			$result = $wpdb->insert( $wpdb->prefix . 'gdbc_day_rules', $data, $formats );

			if ( $result ) {
				$this->id = $wpdb->insert_id;
			}
		}

		if ( false === $result ) {
			return new WP_Error( 'gdbc_day_rule_save_error', sprintf( __( 'Error saving day rule: %s', 'geodir-booking' ), $wpdb->last_error ) );
		}

		return $this->to_array();
	}

	/**
	 * Retrieves the UID (Unique Identifier) of the booking item.
	 *
	 * @return string The UID of the booking.
	 */
	public function get_uid() {
		return $this->uid;
	}

	/**
	 * Retrieves the synchronization ID associated with the booking item.
	 *
	 * @return int The synchronization ID of the booking.
	 */
	public function get_sync_id() {
		return $this->sync_id;
	}

	/**
	 * Retrieves the synchronization queue ID associated with the booking item.
	 *
	 * @return int The synchronization queue ID of the booking.
	 */
	public function get_sync_queue_id() {
		return $this->sync_queue_id;
	}

	/**
	 * Retrieves the iCal product ID associated with the booking item.
	 *
	 * @return string The iCal product ID of the booking.
	 */
	public function get_ical_prodid() {
		return $this->ical_prodid;
	}

	/**
	 * Retrieves the iCal summary (brief description) associated with the booking item.
	 *
	 * @return string The iCal summary of the booking.
	 */
	public function get_ical_summary() {
		return $this->ical_summary;
	}

	/**
	 * Retrieves the iCal description associated with the booking item.
	 *
	 * @return string The iCal description of the booking.
	 */
	public function get_ical_description() {
		return $this->ical_description;
	}


	/**
	 * Checks if the booking item has been imported.
	 *
	 * @return bool True if the booking is imported, false otherwise.
	 *
	 * @since 1.0.0
	 */
	public function is_imported() {
		return ! empty( $this->sync_id );
	}

	/**
	 * Checks whether the day rule exists.
	 *
	 * @return bool
	 */
	public function exists() {
		return ! empty( $this->id );
	}

	/**
	 * Retrieves a day rule by rule_date and listing_id.
	 *
	 * @param string $rule_date The date of the rule in 'Y-m-d' format.
	 * @param int $listing_id The ID of the listing.
	 * @return GeoDir_Booking_Day_Rule|null The day rule object or null if not found.
	 */
	public static function get_by_rule_date( $rule_date, $listing_id ) {
		global $wpdb;

		// Fetch the rule from the database
		$data = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}gdbc_day_rules WHERE listing_id = %d AND rule_date = %s",
				$listing_id,
				$rule_date
			),
			ARRAY_A
		);

		if ( ! $data ) {
			return null;
		}

		return new self( $data );
	}

	/**
	 * Releases the day rule dates based on the rule date comparison (day, month, and year).
	 */
	public function release_dates() {
		global $wpdb;

		// Extract the rule's year, month, and day from the current rule_date.
		$rule_year  = intval( date( 'Y', strtotime( $this->rule_date ) ) );
		$rule_month = intval( date( 'm', strtotime( $this->rule_date ) ) );
		$rule_day   = intval( date( 'd', strtotime( $this->rule_date ) ) );

		// Convert the rule's date to the day of the year.
		$day_of_year = intval( date( 'z', strtotime( $this->rule_date ) ) ) + 1;  // 'z' is zero-based, so add 1.

		// Prepare the day column (e.g., "d1", "d2", ..., "d366").
		$day_column = 'd' . $day_of_year;

		if ( $this->is_available ) {
			$wpdb->update(
				"{$wpdb->prefix}gdbc_availability",
				array( $day_column => null ),
				array(
					'post_id' => $this->listing_id,
					'year'    => $rule_year,
				)
			);
		}

		// Sync the day rules after releasing the dates.
		geodir_booking_sync_day_rules( $this->listing_id );
	}
}
