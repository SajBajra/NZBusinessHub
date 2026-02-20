<?php
/**
 * Ruleset class.
 *
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Ruleset class.
 *
 */
class GeoDir_Booking_Ruleset implements JsonSerializable {

	/**
	 * Ruleset ID.
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
	 * Guests can't check in on...
	 *
	 * @var int[] Index of weekdays starting from 0 (Sunday).
	 */
	public $restricted_check_in_days = array();

	/**
	 * Guests can't check out on...
	 *
	 * @var int[] Index of weekdays starting from 0 (Sunday).
	 */
	public $restricted_check_out_days = array();

	/**
	 * Minimum stay.
	 *
	 * @var int|null
	 */
	public $minimum_stay = null;

	/**
	 * Maximum stay.
	 *
	 * @var int|null
	 */
	public $maximum_stay = null;

	/**
	 * Customize minimum stay by check in day.
	 *
	 * @var bool
	 */
	public $per_day_minimum_stay = false;

	/**
	 * Sunday check in minimum stay.
	 *
	 * @var int|null
	 */
	public $sunday_minimum_stay = null;

	/**
	 * Monday check in minimum stay.
	 *
	 * @var int|null
	 */
	public $monday_minimum_stay = null;

	/**
	 * Tuesday check in minimum stay.
	 *
	 * @var int|null
	 */
	public $tuesday_minimum_stay = null;

	/**
	 * Wednesday check in minimum stay.
	 *
	 * @var int|null
	 */
	public $wednesday_minimum_stay = null;

	/**
	 * Thursday check in minimum stay.
	 *
	 * @var int|null
	 */
	public $thursday_minimum_stay = null;

	/**
	 * Friday check in minimum stay.
	 *
	 * @var int|null
	 */
	public $friday_minimum_stay = null;

	/**
	 * Saturday check in minimum stay.
	 *
	 * @var int|null
	 */
	public $saturday_minimum_stay = null;

	/**
	 * Early birds discounts.
	 *
	 * @var array
	 */
	public $early_bird_discounts = array(); // E.g array( array( 'months' => 5, 'percent' => 10 ) ).

	/**
	 * Last minute discounts.
	 *
	 * @var array
	 */
	public $last_minute_discounts = array(); // E.g array( array( 'days' => 5, 'percent' => 10 ) ).

	/**
	 * Length of stay discounts.
	 *
	 * @var array
	 */
	public $duration_discounts = array(); // E.g array( array( 'nights' => 5, 'percent' => 10 ) ).

	/**
	 * Per night price.
	 *
	 * @var float
	 */
	public $nightly_price = 0; // %ge price for this rule set.

	/**
	 * Stay cleaning feee.
	 *
	 * @var float
	 */
	public $cleaning_fee = 0;

	/**
	 * Stay pet fee.
	 *
	 * @var float
	 */
	public $pet_fee = 0;

	/**
	 * Extra guests count.
	 *
	 * @var float
	 */
	public $extra_guest_count = 0;

	/**
	 * Extra guest feee.
	 *
	 * @var float
	 */
	public $extra_guest_fee = 0;

	/**
	 * Error message.
	 *
	 * @var string
	 */
	public $error = ''; // An error message if there is one.

	/**
	 * Checks if the ruleset is blocking.
	 *
	 * @var bool
	 */
	public $is_saving = false;

	/**
	 * Checks if the ruleset was recently saved.
	 *
	 * @var bool
	 */
	public $is_saved = false;

	/**
	 * Class constructor.
	 *
	 * @param int $id Optional. Ruleset ID.
	 * @param int $listing_id Optional. Listing ID.
	 */
	public function __construct( $id = 0, $listing_id = 0 ) {
		global $wpdb;

		$this->id = (int) $id;

		if ( $listing_id ) {
			$this->listing_id = (int) $listing_id;
		}

		$this->listing_id = geodir_booking_post_id( $this->listing_id );

		// Maybe load the ruleset.
		if ( $this->id ) {
			$ruleset = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}gdbc_rulesets WHERE id = %d", $this->id ), ARRAY_A );
		}

		if ( empty( $ruleset ) && $this->listing_id ) {
			$ruleset = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}gdbc_rulesets WHERE listing_id = %d", $this->listing_id ), ARRAY_A );
		}

		if ( ! empty( $ruleset ) ) {
			$this->set_args( $ruleset );
		}
	}

	/**
	 * Inits the ruleset from the provided args.
	 *
	 * @param array $args Args.
	 */
	public function set_args( $args ) {

		foreach ( $args as $key => $value ) {

			switch ( $key ) {

				// IDs.
				case 'id':
				case 'listing_id':
				case 'extra_guest_count':
					$this->$key = absint( $value );
					break;

				// Integers.
				case 'minimum_stay':
				case 'maximum_stay':
				case 'monday_minimum_stay':
				case 'tuesday_minimum_stay':
				case 'wednesday_minimum_stay':
				case 'thursday_minimum_stay':
				case 'friday_minimum_stay':
				case 'saturday_minimum_stay':
				case 'sunday_minimum_stay':
					$this->$key = empty( $value ) ? null : absint( $value );
					break;

				// Numeric arrays.
				case 'restricted_check_in_days':
				case 'restricted_check_out_days':
					$this->$key = array_unique( wp_parse_id_list( $value ) );
					break;

				// Booleans.
				case 'per_day_minimum_stay':
					$this->$key = ! empty( $value );
					break;

				// Arrays.
				case 'last_minute_discounts':
				case 'early_bird_discounts':
				case 'duration_discounts':
					if ( is_string( $value ) ) {
						$value = json_decode( $value, true );
					}

					$this->$key = is_array( $value ) ? $value : array();
					break;

				// Floats.
				case 'nightly_price':
					$this->$key = max( geodir_booking_night_min_price(), floatval( $value ) );
					break;

				case 'cleaning_fee':
				case 'pet_fee':
				case 'extra_guest_fee':
					$this->$key = floatval( $value );
					break;

				default:
					break;
			}
		}

		if ( ! empty( $this->listing_id ) ) {
			$this->listing_id = geodir_booking_post_id( $this->listing_id );
		}
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

		$data = array(
			'listing_id'                => $this->listing_id,
			'minimum_stay'              => $this->minimum_stay,
			'maximum_stay'              => $this->maximum_stay,
			'per_day_minimum_stay'      => (int) $this->per_day_minimum_stay,
			'restricted_check_in_days'  => implode( ',', $this->restricted_check_in_days ),
			'restricted_check_out_days' => implode( ',', $this->restricted_check_out_days ),
			'monday_minimum_stay'       => $this->monday_minimum_stay,
			'tuesday_minimum_stay'      => $this->tuesday_minimum_stay,
			'wednesday_minimum_stay'    => $this->wednesday_minimum_stay,
			'thursday_minimum_stay'     => $this->thursday_minimum_stay,
			'friday_minimum_stay'       => $this->friday_minimum_stay,
			'saturday_minimum_stay'     => $this->saturday_minimum_stay,
			'sunday_minimum_stay'       => $this->sunday_minimum_stay,
			'early_bird_discounts'      => wp_json_encode( $this->early_bird_discounts ),
			'last_minute_discounts'     => wp_json_encode( $this->last_minute_discounts ),
			'duration_discounts'        => wp_json_encode( $this->duration_discounts ),
			'nightly_price'             => $this->nightly_price,
			'cleaning_fee'              => $this->cleaning_fee,
			'pet_fee'                   => $this->pet_fee,
			'extra_guest_count'         => $this->extra_guest_count,
			'extra_guest_fee'           => $this->extra_guest_fee,
		);

		$formats = array(
			'%d', // listing_id
			'%d', // minimum_stay
			'%d', // maximum_stay
			'%d', // per_day_minimum_stay
			'%s', // restricted_check_in_days
			'%s', // restricted_check_out_days
			'%d', // monday_minimum_stay
			'%d', // tuesday_minimum_stay
			'%d', // wednesday_minimum_stay
			'%d', // thursday_minimum_stay
			'%d', // friday_minimum_stay
			'%d', // saturday_minimum_stay
			'%d', // sunday_minimum_stay
			'%s', // early_bird_discounts
			'%s', // last_minute_discounts
			'%s', // duration_discounts
			'%f', // nightly_price
			'%f', // cleaning_fee
			'%f', // pet_fee
			'%d', // extra_guest_count
			'%f', // extra_guest_fee
		);

		if ( $this->id ) {
			$result = $wpdb->update( $wpdb->prefix . 'gdbc_rulesets', $data, array( 'id' => $this->id ), $formats, array( '%d' ) );
		} else {

			$result = $wpdb->insert( $wpdb->prefix . 'gdbc_rulesets', $data, $formats );

			if ( $result ) {
				$this->id = $wpdb->insert_id;
			}
		}

		if ( false === $result ) {
			return new WP_Error( 'gdbc_ruleset_save_error', sprintf( __( 'Error saving ruleset: %s', 'geodir-booking' ), $wpdb->last_error ) );
		}

		return $this->to_array();
	}

	/**
	 * Retrieves the early bird discount for the given date.
	 *
	 * @param  string $date The date to get the discount for.
	 * @return float The percentage discount for the given date.
	 */
	public function get_early_bird_discount( $date ) {

		// Get all discounts sorted by the months in DESC order.
		$early_bird_discounts = $this->early_bird_discounts;
		usort( $early_bird_discounts, array( $this, 'sort_early_bird_discounts' ) );

		// Loop through the discounts and return the first discount that matches.
		foreach ( $early_bird_discounts as $discount ) {

			if ( $date >= gmdate( 'Y-m-d', strtotime( "+{$discount['months']} months" ) ) ) {
				return (float) $discount['percent'];
			}
		}

		return 0;
	}

	/**
	 * Sort the early bird discounts by the month.
	 *
	 * @param  array $a The first discount.
	 * @param  array $b The second discount.
	 * @return int
	 */
	public function sort_early_bird_discounts( $a, $b ) {
		return (int) $b['months'] - (int) $a['months'];
	}

	/**
	 * Retrieves the last minute discount for the given date.
	 *
	 * @param  string $date The date to get the discount for.
	 * @return float The percentage discount for the given date.
	 */
	public function get_last_minute_discount( $date ) {

		// Get all discounts sorted by the days in ASC order.
		$last_minute_discounts = $this->last_minute_discounts;
		usort( $last_minute_discounts, array( $this, 'sort_last_minute_discounts' ) );

		$date_timestamp = strtotime( $date );

		// Loop through the discounts and return the first discount that matches.
		foreach ( $last_minute_discounts as $discount ) {
			$discount_date_limit = strtotime( "+{$discount['days']} days", strtotime( gmdate( 'Y-m-d' ) ) );

			if ( $date_timestamp < $discount_date_limit ) {
				return (float) $discount['percent'];
			}
		}

		return 0;
	}

	/**
	 * Sort the last minute discounts by the day.
	 *
	 * @param  array $a The first discount.
	 * @param  array $b The second discount.
	 * @return int
	 */
	public function sort_last_minute_discounts( $a, $b ) {
		return (int) $a['days'] - (int) $b['days'];
	}

	/**
	 * Retrieves the duration discount for the given date.
	 *
	 * @param  int    $duration The duration of the stay.
	 * @return float The percentage discount for the given date.
	 */
	public function get_duration_discount( $duration ) {

		// Get all discounts sorted by the nights in DESC order.
		$duration_discounts = $this->duration_discounts;
		usort( $duration_discounts, array( $this, 'sort_duration_discounts' ) );

		// Loop through the discounts and return the first discount that matches.
		foreach ( $duration_discounts as $discount ) {

			if ( isset( $discount['nights'] ) && (int) $duration >= (int) $discount['nights'] ) {
				return (float) $discount['percent'];
			}
		}

		return 0;
	}

	/**
	 * Sort the duration discounts by the night.
	 *
	 * @param  array $a The first discount.
	 * @param  array $b The second discount.
	 * @return int
	 */
	public function sort_duration_discounts( $a, $b ) {
		return (int) $b['nights'] - (int) $a['nights'];
	}
}
