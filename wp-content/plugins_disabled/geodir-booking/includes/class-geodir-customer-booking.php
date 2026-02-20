<?php
/**
 * Container for a single booking.
 *
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Container for a single booking.
 *
 */
class GeoDir_Customer_Booking implements JsonSerializable {

	/**
	 * The booking ID.
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
	 * Invoice ID.
	 *
	 * @var int
	 */
	public $invoice_id = null;

	/**
	 * The customer name.
	 *
	 * @var string
	 */
	public $name = '';

	/**
	 * The customer email.
	 *
	 * @var string
	 */
	public $email = '';

	/**
	 * The customer phone.
	 *
	 * @var string
	 */
	public $phone = '';

	/**
	 * The check-in date.
	 *
	 * @var string
	 */
	public $start_date = '0000-00-00';

	/**
	 * The check-out date.
	 *
	 * @var string
	 */
	public $end_date = '0000-00-00';

	/**
	 * The last minute discount %ge
	 *
	 * @var float
	 */
	public $last_minute_discount_ge = 0;

	/**
	 * The early bird discount %ge
	 *
	 * @var float
	 */
	public $early_bird_discount_ge = 0;

	/**
	 * The duration discount %ge.
	 *
	 * @var float
	 */
	public $duration_discount_ge = 0;

	/**
	 * The final discount %ge.
	 *
	 * @var float
	 */
	public $discount_ge = 0;

	/**
	 * Total price for each booked day.
	 *
	 * @var float[]
	 */
	public $date_amounts = array();

	/**
	 * Total price.
	 *
	 * @var float
	 */
	public $total_amount = 0;

	/**
	 * The amount payable.
	 *
	 * @var float
	 */
	public $payable_amount = 0;

	/**
	 * The total discount.
	 *
	 * @var float
	 */
	public $total_discount = 0;

	/**
	 * The total discount with negative sign.
	 *
	 * @var float
	 */
	public $total_discount_m = 0;

	/**
	 * The service fee.
	 *
	 * @var float
	 */
	public $service_fee = 0;

	/**
	 * The cleaning fee.
	 *
	 * @var float
	 */
	public $cleaning_fee = 0;

	/**
	 * The pet fee.
	 *
	 * @var float
	 */
	public $pet_fee = 0;

	/**
	 * The extra guest fee.
	 *
	 * @var float
	 */
	public $extra_guest_fee = 0;

	/**
	 * The deposit amount.
	 *
	 * @var float
	 */
	public $deposit_amount = 0;

	/**
	 * The site commision.
	 *
	 * @var float
	 */
	public $site_commission = 0;

	/**
	 * The booking status.
	 *
	 * @var string
	 */
	public $status = 'draft';

	/**
	 * The creation date.
	 *
	 * @var string
	 */
	public $created = '0000-00-00 00:00:00';

	/**
	 * The modification date.
	 *
	 * @var string
	 */
	public $modified = '0000-00-00 00:00:00';

	/**
	 * The private note.
	 *
	 * @var string
	 */
	public $private_note = '';

	/**
	 * The number of guests.
	 *
	 * @var int
	 */
	public $guests = 1;

	/**
	 * The number of adults.
	 *
	 * @var int
	 */
	public $adults = 1;

	/**
	 * The number of children.
	 *
	 * @var int
	 */
	public $children = 0;

	/**
	 * The number of infants.
	 *
	 * @var int
	 */
	public $infants = 0;

	/**
	 * The number of pets.
	 *
	 * @var int
	 */
	public $pets = 0;

	/**
	 * The Booking UID.
	 *
	 * @var string
	 */
	public $uid;

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
	 * Class constructor.
	 *
	 * @param int $id Optional. Ruleset ID.
	 */
	public function __construct( $id = 0 ) {
		global $wpdb;

		if ( is_array( $id ) ) {
			$this->set_args( $id );
			return;
		}

		$this->id = (int) $id;

		// Maybe load the booking.
		if ( $this->id ) {
			$args = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}gdbc_bookings WHERE id = %d", $this->id ), ARRAY_A );

			if ( ! empty( $args ) ) {
				$this->set_args( $args );
			}
		}
	}

	/**
	 * Inits the booking from the provided args.
	 *
	 * @param array $args Args.
	 */
	public function set_args( $args ) {

		foreach ( $args as $key => $value ) {

			switch ( $key ) {

				// IDs.
				case 'id':
				case 'listing_id':
				case 'invoice_id':
				case 'sync_queue_id':
				case 'guests':
				case 'adults':
				case 'children':
				case 'infants':
				case 'pets':
					$this->$key = absint( $value );
					break;

				// String.
				case 'name':
				case 'email':
				case 'phone':
				case 'status':
				case 'uid':
					$this->$key = empty( $value ) ? '' : sanitize_text_field( $value );
					break;

				// Dates
				case 'start_date':
				case 'end_date':
					$this->$key = empty( $value ) ? '0000-00-00' : gmdate( 'Y-m-d', strtotime( $value ) );
					break;

				// Date and time.
				case 'created':
				case 'modified':
					$this->$key = empty( $value ) ? '0000-00-00 00:00:00' : gmdate( 'Y-m-d H:i:s', strtotime( $value ) );
					break;

				// Text.
				case 'private_note':
					$this->$key = empty( $value ) ? '' : sanitize_textarea_field( $value );
					break;

				// Numbers.
				case 'total_amount':
				case 'payable_amount':
				case 'site_commission':
				case 'deposit_amount':
				case 'total_discount':
				case 'last_minute_discount_ge':
				case 'early_bird_discount_ge':
				case 'duration_discount_ge':
				case 'service_fee':
				case 'cleaning_fee':
				case 'pet_fee':
				case 'extra_guest_fee':
					$this->$key = empty( $value ) ? 0 : floatval( $value );
					break;

				// Arrays.
				case 'date_amounts':
					$value      = is_string( $value ) ? json_decode( $value, true ) : $value;
					$this->$key = is_array( $value ) ? $value : array();
					break;

				default:
					break;
			}
		}

		// booking uid.
		if ( $this->exists() && empty( $this->uid ) ) {
			$this->uid = md5( $this->id ) . '@' . geodir_booking_site_domain();
		}
	}

	/**
	 * Retrieves the ID of the booking item.
	 *
	 * @return int The ID of the booking.
	 */
	public function get_id() {
		return (int) $this->id;
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
	 * Retrieves the list of reserved rooms associated with the booking item.
	 *
	 * @return array The list of reserved rooms.
	 */
	public function get_reserved_rooms() {
		$rooms = geodir_get_listing_rooms( $this->listing_id );
		return $rooms;
	}

	/**
	 * Retrieves the check-in date of the booking.
	 *
	 * @return DateTime The check-in date.
	 */
	public function get_check_in_date() {
		return new DateTime( $this->start_date );
	}

	/**
	 * Retrieves the check-out date of the booking.
	 *
	 * @return DateTime The check-out date.
	 */
	public function get_check_out_date() {
		return new DateTime( $this->end_date );
	}

	/**
	 * Formats the per day amounts.
	 *
	 * @return array
	 */
	public function format_date_amounts() {
		$formatted = array();

		foreach ( $this->date_amounts as $date => $amount ) {
			$formatted[] = array(
				'date'   => geodir_booking_date( $date, 'view_day' ),
				'amount' => $amount,
			);
		}

		return $formatted;
	}

	/**
	 * Generates a textual summary of the number of guests, infants, and pets, accounting for pluralization.
	 *
	 * @return string The summary containing counts of guests, infants, and pets, formatted for display.
	 */
	public function get_guests_summary() {
		$summary = absint( $this->guests ) . ' ' . _n( 'guest', 'guests', $this->guests, 'geodir-booking' );

		if ( $this->infants > 0 ) {
			$summary .= ', ' . absint( $this->infants ) . ' ' . _n( 'infant', 'infants', $this->infants, 'geodir-booking' );
		}

		if ( $this->pets > 0 ) {
			$summary .= ', ' . absint( $this->pets ) . ' ' . _n( 'pet', 'pets', $this->pets, 'geodir-booking' );
		}

		return $summary;
	}

	/**
	 * Returns the booking details.
	 *
	 * @return array
	 */
	public function get_details() {
		$details = array(
			'booking-number' => array(
				'label' => __( 'Booking Number', 'geodir-booking' ),
				'value' => esc_html( sanitize_text_field( $this->id ) ),
			),
			'check-in'       => array(
				'label' => __( 'Check-in', 'geodir-booking' ),
				'value' => geodir_booking_date( $this->start_date, 'view_day' ),
			),
			'check-out'      => array(
				'label' => __( 'Check-out', 'geodir-booking' ),
				'value' => geodir_booking_date( $this->end_date, 'view_day' ),
			),
			'guests'         => array(
				'label' => __( 'Guests', 'geodir-booking' ),
				'value' => $this->get_guests_summary(),
			),
			'booked'         => array(
				'label' => __( 'Booked', 'geodir-booking' ),
				'value' => geodir_booking_date( $this->created ),
			),
			'modified'       => array(
				'label' => __( 'Modified', 'geodir-booking' ),
				'value' => geodir_booking_date( $this->modified ),
			),
			'payable_amount' => array(
				'label' => __( 'Total', 'geodir-booking' ),
				'value' => wpinv_price( $this->payable_amount ),
			),
			'deposit_amount' => array(
				'label' => __( 'Deposit', 'geodir-booking' ),
				'value' => wpinv_price( $this->deposit_amount ),
			),
			'owner_earnings' => array(
				'label' => __( 'You receive', 'geodir-booking' ),
				'value' => wpinv_price( max( 0, $this->deposit_amount - $this->site_commission ) ),
			),
			'status'         => array(
				'label' => __( 'Status', 'geodir-booking' ),
				'value' => $this->get_status_html(),
			),
		);

		// If total amount is equal to payable amount, then we don't need to show the deposit amount.
		if ( empty( $this->deposit_amount ) || $this->deposit_amount === $this->payable_amount ) {
			unset( $details['deposit_amount'] );
		}

		if ( max( 0, $this->deposit_amount - $this->site_commission ) === 0 ) {
			unset( $details['owner_earnings'] );
		}

		if ( 0 === $this->payable_amount ) {
			unset( $details['payable_amount'] );
		}

		return $details;
	}

	/**
	 * Returns the listing image URL.
	 *
	 * @return array
	 */
	public function get_listing_image() {
		$post_image = geodir_get_images( (int) $this->listing_id, 1, false, 0, array( 'post_images' ) );
		return ! empty( $post_image ) && ! empty( $post_image[0] ) ? geodir_get_image_src( $post_image[0] ) : '';
	}

	/**
	 * Returns the listing details (customer_view).
	 *
	 * @return array
	 */
	public function get_listing_details() {

		return array(
			'url'   => geodir_get_listing_url( $this->listing_id ),
			'title' => get_the_title( $this->listing_id ),
			'image' => $this->get_listing_image(),
		);
	}

	/**
	 * Returns the booking details (customer_view).
	 *
	 * @return array
	 */
	public function get_customer_details() {
		$number_of_nights = max( count( $this->date_amounts ), 1 );
		$amount_per_night = (float) $this->total_amount / $number_of_nights;

		// Base details
		$details = array(
			'booking-number' => array(
				'label' => __( 'Booking Number', 'geodir-booking' ),
				'value' => esc_html( sanitize_text_field( $this->id ) ),
			),
			'name'           => array(
				'label' => __( 'Name', 'geodir-booking' ),
				'value' => esc_html( sanitize_text_field( $this->name ) ),
			),
			'email'          => array(
				'label' => __( 'Email', 'geodir-booking' ),
				'value' => esc_html( sanitize_email( $this->email ) ),
			),
			'phone'          => array(
				'label' => __( 'Phone', 'geodir-booking' ),
				'value' => esc_html( sanitize_text_field( $this->phone ) ),
			),
			'guests'         => array(
				'label' => __( 'Guests', 'geodir-booking' ),
				'value' => $this->get_guests_summary(),
			),
			'check_in'       => array(
				'label' => __( 'Check-in', 'geodir-booking' ),
				'value' => geodir_booking_date( $this->start_date, 'view_day' ),
			),
			'check_out'      => array(
				'label' => __( 'Check-out', 'geodir-booking' ),
				'value' => geodir_booking_date( $this->end_date, 'view_day' ),
			),
			'booked'         => array(
				'label' => __( 'Booked', 'geodir-booking' ),
				'value' => geodir_booking_date( $this->created ),
			),
			'total_amount'   => array(
				'label' => wpinv_price( $amount_per_night ) . ' x ' . $number_of_nights . ' ' . _n( 'night', 'nights', $number_of_nights, 'geodir-booking' ),
				'value' => wpinv_price( $this->total_amount ),
			),
		);

		// Conditional details
		if ( $this->extra_guest_fee > 0 ) {
			$details['extra_guest_fee'] = array(
				'label' => __( 'Extra Guests Fee', 'geodir-booking' ),
				'value' => wpinv_price( $this->extra_guest_fee ),
			);
		}

		if ( $this->cleaning_fee > 0 ) {
			$details['cleaning_fee'] = array(
				'label' => __( 'Cleaning Fee', 'geodir-booking' ),
				'value' => wpinv_price( $this->cleaning_fee ),
			);
		}

		if ( $this->pet_fee > 0 ) {
			$details['pet_fee'] = array(
				'label' => __( 'Pet Fee', 'geodir-booking' ),
				'value' => wpinv_price( $this->pet_fee ),
			);
		}

		if ( $this->total_discount > 0 ) {
			$details['discount_amount'] = array(
				'label' => __( 'Discount', 'geodir-booking' ),
				'value' => '-' . wpinv_price( $this->total_discount ),
			);
		}

		if ( $this->service_fee > 0 ) {
			$details['service_fee'] = array(
				'label' => __( 'Service Fee', 'geodir-booking' ),
				'value' => wpinv_price( $this->service_fee ),
			);
		}

		if ( $this->payable_amount > 0 ) {
			$details['payable_amount'] = array(
				'label' => __( 'Booking Total', 'geodir-booking' ),
				'value' => wpinv_price( $this->payable_amount ),
			);
		}

		// If total amount is equal to payable amount, then we don't need to show the deposit amount.
		if ( $this->deposit_amount > 0 && $this->deposit_amount !== $this->payable_amount ) {
			$details['deposit_amount'] = array(
				'label' => __( 'Deposit', 'geodir-booking' ),
				'value' => wpinv_price( $this->deposit_amount ),
			);
		}

		$details['cancellation_policy'] = array(
			'label' => __( 'Cancellation Policy', 'geodir-booking' ),
			'value' => $this->get_cancellation_policy(),
		);

		$details['status'] = array(
			'label'  => __( 'Status', 'geodir-booking' ),
			'value'  => $this->get_status_html(),
			'cancel' => $this->get_cancel_details(),
		);

		// Maybe remove the pay link.
		if ( ! empty( $details['pay']['value'] ) ) {
			$details['pay'] = array(
				'label' => __( 'Pay', 'geodir-booking' ),
				'value' => $this->get_pay_link(),
			);
		}

		// Handle cancellation policy
		if ( ! $this->can_cancel() || empty( $details['cancellation_policy']['value'] ) ) {
			unset( $details['cancellation_policy'] );
		} else {
			$all_policies = geodir_booking_get_cancellation_policies();
			$policy       = isset( $all_policies[ $details['cancellation_policy']['value'] ] ) ? $all_policies[ $details['cancellation_policy']['value'] ] : array();
			$policy_name  = isset( $policy['policy_name'] ) ? $policy['policy_name'] : '';
			$policy_desc  = isset( $policy['policy_desc'] ) ? $policy['policy_desc'] : '';

			$details['cancellation_policy']['value'] = ! empty( $policy_desc ) ? esc_html( $policy_desc ) : esc_html( $policy_name );
		}

		return $details;
	}

	/**
	 * Checks if a booking is past its checkout date.
	 *
	 * @return bool True if the booking is past, false otherwise.
	 */
	public function is_booking_past() {
		$checkout_timestamp = strtotime( $this->end_date );
		// Get the current time in the WordPress timezone.
		$current_time = current_time( 'timestamp' );

		return $current_time > $checkout_timestamp;
	}

	/**
	 * Checks if we can cancel.
	 *
	 * @return string
	 */
	public function can_cancel() {
		return 'confirmed' === $this->status && $this->days_to_checkin() >= 0;
	}

	/**
	 * Returns the cancel booking details.
	 *
	 * @return array
	 */
	public function get_cancel_details() {
		return array(
			'can_cancel'               => $this->can_cancel(),
			'id'                       => $this->id,
			'encrypted_customer_email' => geodir_booking_encrypt( $this->email ),
			'nonce'                    => wp_create_nonce( 'geodir_booking_cancel_booking' ),
		);
	}

	/**
	 * Returns the status HTML.
	 */
	public function get_status_html() {
		global $aui_bs5;

		return sprintf(
			'<span class="status badge ' . ( $aui_bs5 ? 'text-bg-%s' : 'badge-%s' ) . '">%s</span>',
			esc_attr( geodir_get_booking_status_context( $this->status ) ),
			esc_html( geodir_get_booking_status_label( $this->status ) )
		);
	}

	/**
	 * Returns whether or not this booking is upcoming.
	 *
	 * @return bool
	 */
	public function is_upcoming() {
		return strtotime( $this->start_date ) >= strtotime( 'today 00:00:00' );
	}

	/**
	 * Returns the payment link HTML.
	 */
	public function get_pay_link() {

		$url = $this->get_payment_url();

		if ( empty( $url ) ) {
			return '';
		}

		return sprintf(
			'<a href="%s" class="btn btn-success btn-sm">%s</a>',
			esc_url( $url ),
			esc_html__( 'Pay', 'geodir-booking' )
		);
	}

	/**
	 * Returns the payment URL.
	 *
	 * @return string|false
	 */
	public function get_payment_url() {

		// Abort if we're not collecting payments.
		if ( geodir_booking_get_payment_type( $this->listing_id ) === 'none' ) {
			return false;
		}

		// You can not pay for a booking that has passed.
		if ( ! $this->is_upcoming() ) {
			return false;
		}

		// Ensure the booking is pending payment.
		if ( ! in_array( $this->status, array( 'pending_payment' ), true ) ) {
			return false;
		}

		// Ensure the booking has a deposit amount.
		if ( $this->deposit_amount <= 0 ) {
			return false;
		}

		// Fetch the invoice.
		$invoice = new WPInv_Invoice( (int) $this->invoice_id );

		if ( $invoice->exists() && $invoice->needs_payment() ) {
			return $invoice->get_checkout_payment_url();
		}

		return false;
	}

	/**
	 * Returns the booking's extra data.
	 */
	public function extra_data() {
		$post      = get_post( $this->listing_id );
		$parent_id = empty( $post->post_parent ) ? $this->listing_id : $post->post_parent;

		return apply_filters(
			'gd_customer_booking_extra_data',
			array(
				'old_status'       => $this->status,
				'encrypted_email'  => geodir_booking_encrypt( $this->email ),
				'status_label'     => geodir_get_booking_status_label( $this->status ),
				'context_class'    => geodir_get_booking_status_context( $this->status ),
				'is_upcoming'      => $this->is_upcoming(),
				'payment_url'      => $this->get_payment_url(),
				'check_in'         => date_i18n( get_option( 'date_format', 'Y-m-d' ), strtotime( $this->start_date ) ),
				'check_out'        => date_i18n( get_option( 'date_format', 'Y-m-d' ), strtotime( $this->end_date ) ),
				'booking_date'     => date_i18n( get_option( 'date_format', 'Y-m-d' ), strtotime( $this->created ) ),
				'booking_time'     => date_i18n( get_option( 'time_format', 'H:i:s' ), strtotime( $this->created ) ),
				'modified_date'    => date_i18n( get_option( 'date_format', 'Y-m-d' ), strtotime( $this->modified ) ),
				'listing_url'      => geodir_get_listing_url( $this->listing_id ),
				'listing_title'    => empty( $post->post_title ) ? '' : $post->post_title,
				'date_amounts_fmt' => $this->format_date_amounts(),
				'details'          => $this->get_details(),
				'customer_details' => $this->get_customer_details(),
				'avatar'           => get_avatar( $this->email, 32, '', $this->name, array( 'class' => 'rounded-circle' ) ),
				'saving'           => false,
			)
		);
	}

	/**
	 * Return the ruleset as an array.
	 */
	public function to_array() {
		return array_merge( get_object_vars( $this ), $this->extra_data() );
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
	 * (Maybe) create a new invoice for the booking.
	 *
	 * @return int
	 */
	protected function get_invoice_id() {
		// Prepare the invoice.
		$invoice = new WPInv_Invoice( (int) $this->invoice_id );

		// Only update the invoice if it's not paid and the booking is still pending payment.
		if ( geodir_booking_get_payment_type( $this->listing_id ) === 'none' || $invoice->is_paid() || 'pending_payment' !== $this->status || $this->deposit_amount <= 0 ) {
			return $invoice->get_id();
		}

		$GLOBALS['geodir_booking_fees'] = array( $this->service_fee, $this->site_commission );

		// Set item.
		$item_id      = geodir_booking_item();
		$invoice_item = $invoice->get_item( $item_id );

		if ( empty( $invoice_item ) ) {
			$invoice_item = new GetPaid_Form_Item( $item_id );
		}

		$invoice_item->set_name( get_the_title( $this->listing_id ) );
		$invoice_item->set_description(
			sprintf(
				__( 'Booking for %s', 'geodir-booking' ),
				date_i18n( geodir_date_format(), strtotime( $this->start_date ) ) . ' - ' . date_i18n( geodir_date_format(), strtotime( $this->end_date ) )
			)
		);
		$invoice_item->set_quantity( 1 );
		// Deposit of the Booking total + the service fee.
		$invoice_item->set_price( $this->deposit_amount );
		$invoice->add_item( $invoice_item );

		// Set customer.
		$user = get_user_by( 'email', $this->email );

		if ( $invoice->exists() && $invoice->get_user_id() ) {
			$user_id = $invoice->get_user_id();
		} elseif ( ! empty( $user ) ) {
			$user_id = $user->ID;
		} elseif ( get_current_user_id() ) {
			$user_id = get_current_user_id();
		} else {

			// Attempt to create the user.
			$user_id = wpinv_create_user( sanitize_email( $this->email ), $this->name );

			if ( is_wp_error( $user_id ) ) {
				return $invoice->get_id();
			}
		}

		$invoice->set_user_id( $user_id );
		$invoice->set_user_email( $this->email );

		// Set the name.
		if ( ! empty( $this->name ) ) {
			$name = explode( ' ', trim( $this->name ), 2 );

			if ( ! empty( $name[0] ) ) {
				$invoice->set_first_name( $name[0] );
			}

			if ( ! empty( $name[1] ) ) {
				$invoice->set_last_name( $name[1] );
			}
		}

		// Set the billing phone.
		if ( ! empty( $this->phone ) ) {
			$invoice->set_phone( $this->phone );
		}

		// Set the payment form.
		$invoice->set_payment_form( geodir_booking_get_payment_form( $this->listing_id ) );

		$invoice->set_created_via( 'booking' );
		$invoice->update_meta_data( '_gd_booking_id', $this->id );
		$invoice->recalculate_total();
		$invoice->save();
		$this->invoice_id = $invoice->get_id();
		return $invoice->get_id();
	}

	/**
	 * Updates the invoice's booking id.
	 */
	protected function update_invoice_booking_id() {
		global $sitepress;

		// Set lang if WPML is active.
		if ( ! empty( $sitepress ) && geodir_booking_is_wpml() ) {
			$lang = $sitepress->get_current_language();

			update_post_meta( (int) $this->id, '_gd_booking_lang', sanitize_text_field( $lang ) );
		}

		// Prepare the invoice.
		$invoice = new WPInv_Invoice( (int) $this->invoice_id );

		// Abort if the invoice doesn't exist.
		if ( ! $invoice->exists() ) {
			return;
		}

		// Update the meta and save.
		$invoice->update_meta_data( '_gd_booking_id', $this->id );
		$invoice->save();
	}

	/**
	 * Fired when a customer confirms a booking.
	 *
	 * @return array|WP_Error
	 */
	public function customer_confirm() {

		// Users can only confirm draft bookings.
		if ( 'draft' !== $this->status ) {
			return new WP_Error( 'not_draft', esc_html__( 'You have already confirmed this booking.', 'geodir-booking' ), array( 'status' => 400 ) );
		}

		// Depending on settings, we'll either create an invoice or mark the booking as pending_confirmation.
		if ( geodir_booking_get_payment_type( $this->listing_id ) === 'none' ) {
			$this->status = apply_filters( 'geodir_booking_zero_payment_status', 'pending_confirmation' );
		} else {
			$this->status = 'pending_payment';
		}

		$this->save();

		// Send the booking pending emails.
		if ( 'pending_confirmation' === $this->status ) {
			geodir_booking_send_email( 'user_booking_pending', $this );
			geodir_booking_send_email( 'owner_booking_request', $this );
		}

		return array(
			'booking'  => $this->to_array(),
			'redirect' => $this->get_payment_url(),
		);
	}

	/**
	 * Confirms a booking.
	 *
	 * @param bool $manual Whether or not the confirmation was done manually.
	 */
	public function confirm( $manual = false ) {

		// Bail if the booking is already confirmed.
		if ( 'confirmed' === $this->status ) {
			return;
		}

		// Update the booking.
		$this->status = 'confirmed';
		$this->save();

		// Update user wallet.
		if ( ! $manual ) {
			$this->increment_seller_wallet();
		}

		// Send the booking confirmed email.
		geodir_booking_send_email( 'owner_booking_confirmation', $this );
		geodir_booking_send_email( 'user_booking_confirmation', $this );
	}

	/**
	 * Rejects a booking.
	 *
	 */
	public function reject() {

		// Bail if the booking is already rejected.
		if ( 'rejected' === $this->status ) {
			return;
		}

		// Update the booking.
		$this->status = 'rejected';
		$this->save();

		// Send the booking rejected email.
		geodir_booking_send_email( 'user_booking_rejected', $this );

		// Release the booked dates.
		$this->release_dates();
	}

	/**
	 * Complete a booking.
	 */
	public function complete() {

		// Bail if the booking is already complete.
		if ( 'completed' === $this->status ) {
			return;
		}

		if ( 'confirmed' !== $this->status ) {
			return $this->cancel();
		}

		// Update the booking.
		$this->status = 'completed';
		$this->save();

		// Release the booked dates.
		$this->release_dates();
	}

	/**
	 * Cancel a booking.
	 */
	public function cancel() {

		// Bail if the booking is already canceled.
		if ( 'cancelled' === $this->status ) {
			return;
		}

		$was_confirmed = 'confirmed' === $this->status;

		// Update the booking.
		$this->status = 'cancelled';
		$this->save();

		// Release the booked dates.
		$this->release_dates();

		if ( $was_confirmed ) {
			$this->decrement_seller_wallet();
		}

		// Send the booking canceled email.
		geodir_booking_send_email( 'owner_booking_cancellation', $this );
		geodir_booking_send_email( 'user_booking_cancellation', $this );
	}

	/**
	 * Refund a booking.
	 */
	public function refund() {

		// Bail if the booking is already refunded.
		if ( 'refunded' === $this->status ) {
			return;
		}

		$was_confirmed = 'confirmed' === $this->status;

		// Update the booking.
		$this->status = 'refunded';
		$this->save();

		// Release the booked dates.
		$this->release_dates();

		if ( $was_confirmed ) {
			$this->decrement_seller_wallet();
		}

		// Send the booking canceled email.
		geodir_booking_send_email( 'owner_booking_refunded', $this );
		geodir_booking_send_email( 'user_booking_refunded', $this );
	}

	/**
	 * Increments the seller wallet.
	 */
	public function increment_seller_wallet() {

		$invoice     = wpinv_get_invoice( $this->invoice_id );
		$post_object = get_post( $this->listing_id );
		$author      = empty( $post_object ) ? 0 : $post_object->post_author;

		if ( empty( $author ) || empty( $invoice ) || get_post_meta( $invoice->get_id(), 'geodir_booking_incremented_wallet', true ) || ! function_exists( 'wpinv_wallet_add_new_transaction' ) ) {
			return;
		}

		$account_funds = wpinv_wallet_get_user_balance( $author, false, $invoice->get_currency() );
		$earnings      = max( 0, $this->deposit_amount - $this->site_commission );

		wpinv_wallet_add_new_transaction(
			$author,
			array(
				'type'     => 'bookings',
				'amount'   => $earnings,
				'balance'  => $account_funds + $earnings,
				'currency' => $invoice->get_currency(),
				'details'  => sanitize_text_field(
					sprintf(
						__( '%s booking earnings', 'geodir-booking' ),
						get_the_title( $this->listing_id )
					)
				),
			)
		);

		update_post_meta( $invoice->get_id(), 'geodir_booking_incremented_wallet', 1 );
	}

	/**
	 * Returns the number of days to checkin date.
	 *
	 * @return int
	 */
	public function days_to_checkin() {
		return (int) ceil( ( strtotime( $this->start_date ) - time() ) / DAY_IN_SECONDS );
	}

	/**
	 * Retrieves the cancellation policy.
	 *
	 * @return string|false
	 */
	public function get_cancellation_policy() {
		$gd_post = geodir_get_post_info( (int) $this->listing_id );

		if ( ! property_exists( $gd_post, 'gdb_cancellation_policy' ) ) {
			return false;
		}

		$all_policies        = geodir_booking_get_cancellation_policies();
		$cancellation_policy = geodir_get_post_meta( $this->listing_id, 'gdb_cancellation_policy', true );

		if ( empty( $cancellation_policy ) ) {
			$cancellation_policy = geodir_booking_get_default_cancellation_policy();
		}

		return isset( $all_policies[ $cancellation_policy ] ) ? $cancellation_policy : false;
	}

	/**
	 * Retrieves the cancellation refund percent.
	 *
	 * @return float
	 */
	public function get_cancellation_refund_percent() {

		$all_policies        = geodir_booking_get_cancellation_policies();
		$cancellation_policy = $this->get_cancellation_policy();

		if ( ! $cancellation_policy ) {
			return 0;
		}

		$policy = $all_policies[ $cancellation_policy ];

		if ( $this->days_to_checkin() >= (int) $policy['policy_days'] ) {
			return min( 100, floatval( $policy['policy_if'] ) );
		}

		return min( 100, floatval( $policy['policy_if_not'] ) );
	}

	/**
	 * Decrements the seller wallet.
	 */
	public function decrement_seller_wallet() {

		$invoice     = wpinv_get_invoice( $this->invoice_id );
		$post_object = get_post( $this->listing_id );
		$author      = empty( $post_object ) ? 0 : $post_object->post_author;

		if ( empty( $author ) || empty( $invoice ) || ! get_post_meta( $invoice->get_id(), 'geodir_booking_incremented_wallet', true ) || ! function_exists( 'wpinv_wallet_add_new_transaction' ) ) {
			return;
		}

		$account_funds   = wpinv_wallet_get_user_balance( $author, false, $invoice->get_currency() );
		$customer_funds  = wpinv_wallet_get_user_balance( $invoice->get_customer_id(), false, $invoice->get_currency() );
		$site_commission = $this->site_commission;
		$author_earnings = max( 0, $this->deposit_amount - $site_commission );
		$customer_refund = 0;

		// If the booking was canceled, refund according to settings.
		if ( 'cancelled' === $this->status && geodir_booking_has_cancellation_policies() ) {

			// No refunds if cancelled after booking day.
			if ( $this->days_to_checkin() < 0 ) {
				return;
			}

			$refund_percent = $this->get_cancellation_refund_percent();

			if ( $refund_percent > 0 ) {
				$customer_refund = $invoice->get_total() * ( $refund_percent / 100 );
				$author_earnings = max( 0, $author_earnings * ( $refund_percent / 100 ) );
			} else {
				return; // No refunds.
			}
		}

		if ( $author_earnings ) {
			wpinv_wallet_add_new_transaction(
				$author,
				array(
					'type'     => 'bookings',
					'amount'   => 0 - $author_earnings,
					'balance'  => $account_funds - $author_earnings,
					'currency' => $invoice->get_currency(),
					'details'  => sanitize_text_field(
						sprintf(
							__( '%s booking refund', 'geodir-booking' ),
							get_the_title( $this->listing_id )
						)
					),
				)
			);
		}

		if ( $customer_refund ) {
			wpinv_wallet_add_new_transaction(
				$invoice->get_customer_id(),
				array(
					'type'     => 'bookings',
					'amount'   => $customer_refund,
					'balance'  => $customer_funds + $customer_refund,
					'currency' => $invoice->get_currency(),
					'details'  => sanitize_text_field(
						sprintf(
							__( '%s booking refund', 'geodir-booking' ),
							get_the_title( $this->listing_id )
						)
					),
				)
			);
		}

		delete_post_meta( $invoice->get_id(), 'geodir_booking_incremented_wallet' );
	}

	/**
	 * Save the ruleset.
	 *
	 * @return array|WP_Error
	 */
	public function save() {

		global $wpdb;

		// Maybe recalculate the total amount.
		if ( ( 'draft' === $this->status || 'pending_payment' === $this->status || 'pending_confirmation' === $this->status ) && $this->listing_id ) {
			$this->calculate_prices();
		}

		$data = array(
			'listing_id'              => (int) $this->listing_id,
			'invoice_id'              => (int) $this->get_invoice_id(),
			'name'                    => $this->name,
			'email'                   => $this->email,
			'phone'                   => $this->phone,
			'guests'                  => (int) $this->guests,
			'adults'                  => (int) $this->adults,
			'children'                => (int) $this->children,
			'infants'                 => (int) $this->infants,
			'pets'                    => (int) $this->pets,
			'start_date'              => $this->start_date,
			'end_date'                => $this->end_date,
			'last_minute_discount_ge' => $this->last_minute_discount_ge,
			'early_bird_discount_ge'  => $this->early_bird_discount_ge,
			'duration_discount_ge'    => $this->duration_discount_ge,
			'date_amounts'            => wp_json_encode( $this->date_amounts ),
			'total_amount'            => (float) $this->total_amount,
			'total_discount'          => (float) $this->total_discount,
			'payable_amount'          => (float) $this->payable_amount,
			'service_fee'             => (float) $this->service_fee,
			'cleaning_fee'            => (float) $this->cleaning_fee,
			'pet_fee'                 => (float) $this->pet_fee,
			'extra_guest_fee'         => (float) $this->extra_guest_fee,
			'deposit_amount'          => (float) $this->deposit_amount,
			'site_commission'         => (float) $this->site_commission,
			'status'                  => $this->status,
			'created'                 => '0000-00-00 00:00:00' === $this->created ? current_time( 'mysql' ) : $this->created,
			'modified'                => current_time( 'mysql' ),
			'private_note'            => $this->private_note,
			'uid'                     => $this->uid,
		);

		$formats = array(
			'%d', // listing_id
			'%d', // invoice_id
			'%s', // name
			'%s', // email
			'%s', // phone
			'%d', // guests
			'%d', // adults
			'%d', // children
			'%d', // infants
			'%d', // pets
			'%s', // start_date
			'%s', // end_date
			'%f', // last_minute_discount_ge
			'%f', // early_bird_discount_ge
			'%f', // duration_discount_ge
			'%s', // date_amounts
			'%f', // total_amount
			'%f', // total_discount
			'%f', // payable_amount
			'%f', // service_fee
			'%f', // cleaning_fee
			'%f', // pet_fee
			'%f', // extra_guest_fee
			'%f', // deposit_amount
			'%s', // site_commission
			'%s', // status
			'%s', // created
			'%s', // modified
			'%s', // private_note
			'%s', // uid
		);

		if ( $this->id ) {
			$result = $wpdb->update( $wpdb->prefix . 'gdbc_bookings', $data, array( 'id' => $this->id ), $formats, array( '%d' ) );
		} else {

			$result = $wpdb->insert( $wpdb->prefix . 'gdbc_bookings', $data, $formats );

			if ( $result ) {
				$this->id = $wpdb->insert_id;

				$this->update_invoice_booking_id();

			}
		}

		if ( false === $result ) {
			return new WP_Error( 'gdbc_booking_save_error', sprintf( __( 'Error saving booking: %s', 'geodir-booking' ), $wpdb->last_error ) );
		}

		$this->hold_dates();

		return $this;
	}

	/**
	 * Calculates the booking prices and associated fees.
	 */
	public function calculate_prices() {
		// Initialize the ruleset and calculate the booking duration.
		$ruleset    = new GeoDir_Booking_Ruleset( 0, $this->listing_id );
		$start_date = new DateTime( $this->start_date );
		$end_date   = new DateTime( $this->end_date );
		$duration   = $end_date->diff( $start_date )->days;

		// Retrieve nightly prices for specific dates.
		$day_rules = wp_list_pluck( GeoDir_Booking::get_day_rules( $this->listing_id ), 'nightly_price', 'rule_date' );

		// Calculate discounts based on the ruleset.
		$this->last_minute_discount_ge = round( $ruleset->get_last_minute_discount( $this->start_date ), 2 );
		$this->early_bird_discount_ge  = round( $ruleset->get_early_bird_discount( $this->start_date ), 2 );
		$this->duration_discount_ge    = round( $ruleset->get_duration_discount( $duration ), 2 );

		// Initialize array to store amount for each date.
		$this->date_amounts = array();

		// Calculate the price for each date within the booking period.
		for ( $i = 0; $i < $duration; $i++ ) {
			$date                        = gmdate( 'Y-m-d', strtotime( $this->start_date . ' +' . $i . ' days' ) );
			$this->date_amounts[ $date ] = isset( $day_rules[ $date ] ) ? $day_rules[ $date ] : (float) $ruleset->nightly_price;
		}

		// Calculate the total amount for the booking based on date amounts.
		$this->total_amount = array_sum( $this->date_amounts );

		// Set additional fees if applicable.
		$this->cleaning_fee = (float) $ruleset->cleaning_fee;
		$this->pet_fee      = ( $this->pets > 0 && $ruleset->pet_fee ) ? (float) $ruleset->pet_fee : 0;

		// Calculate extra guest fees based on the number of guests exceeding the allowed limit.
		if ( $ruleset->extra_guest_fee > 0 && $ruleset->extra_guest_count > 0 && $this->guests > $ruleset->extra_guest_count ) {
			$booking_extra_guests  = (int) $this->guests - (int) $ruleset->extra_guest_count;
			$this->extra_guest_fee = (float) ( $booking_extra_guests * $ruleset->extra_guest_fee ) * $duration;
		} else {
			$this->extra_guest_fee = 0;
		}

		// Calculate the payable amount including all fees.
		$this->payable_amount = (float) $this->total_amount + (float) $this->extra_guest_fee + (float) $this->pet_fee + (float) $this->cleaning_fee;

		// Calculate total discounts and apply them.
		$timing_discounts = array(
			$this->last_minute_discount_ge,
			$this->early_bird_discount_ge,
		);

		// Choose the higher of the two mutually exclusive time-based discounts.
		$timing_discount   = max( $timing_discounts );
		$duration_discount = $this->duration_discount_ge;

		// Determine the highest discount percentage and cap it at 100%.
		$total_percentage_discount = $timing_discount + $duration_discount;
		$total_percentage_discount = min( 100, $total_percentage_discount );

		// Set the selected discount percentage.
		$this->discount_ge = $total_percentage_discount;

		// Calculate the total discount amount based on the discount percentage.
		$this->total_discount = $total_percentage_discount > 0 ? ( $this->payable_amount * $total_percentage_discount / 100 ) : 0;

		// Calculate the total discount as a negative value for deduction.
		$this->total_discount_m = $this->total_discount * -1;

		// Adjust the payable amount by subtracting the total discount.
		$this->payable_amount = $this->total_discount > 0 ? ( $this->payable_amount - $this->total_discount ) : $this->payable_amount;

		// Calculate the service fee as a percentage of the payable amount.
		$this->service_fee = geodir_booking_calculate_percentage( (float) geodir_booking_get_option( 'service_fee' ), $this->payable_amount );

		// Calculate the site commission as a percentage of the payable amount + service fee.
		$this->site_commission  = geodir_booking_calculate_percentage( geodir_booking_get_site_commission_amount( $this->listing_id ), $this->payable_amount );
		$this->site_commission += (float) $this->service_fee;

		// Add service fee to the payable amount.
		$this->payable_amount += (float) $this->service_fee;

		// Calculate the deposit amount based on a percentage of the total payable amount.
		$this->deposit_amount = geodir_booking_calculate_percentage( geodir_booking_get_deposit_amount( $this->listing_id ), $this->payable_amount );

		// If the payment type is 'none', set deposit and site commission to zero.
		if ( geodir_booking_get_payment_type( $this->listing_id ) === 'none' ) {
			$this->deposit_amount  = 0;
			$this->site_commission = 0;
		}
	}

	/**
	 * Holds the booking dates.
	 */
	public function hold_dates() {
		global $wpdb;

		// Release existing dates.
		$this->release_dates();

		// Do not hold dates for completed/rejected/cancelled bookings.
		if ( in_array( $this->status, array( 'completed', 'rejected', 'cancelled' ), true ) ) {
			return;
		}

		$availability = array();

		// Cancel each date_amount.
		foreach ( array_keys( $this->date_amounts ) as $date ) {

			// Calculate the year.
			$year = (string) gmdate( 'Y', strtotime( $date ) );

			// Calculate the day of the year.
			$day_of_year = gmdate( 'z', strtotime( $date ) ) + 1;

			// Group availability by year.
			if ( ! isset( $availability[ $year ] ) ) {
				$availability[ $year ] = array();
			}

			$availability[ $year ][ "d$day_of_year" ] = $this->id;
		}

		// Abort if there is no availability to hold.
		if ( empty( $availability ) ) {
			return;
		}

		// Fetch the existing availability.
		$saved_availability = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}gdbc_availability WHERE post_id = %d AND year IN (" . implode( ',', array_keys( $availability ) ) . ')',
				$this->listing_id
			),
			ARRAY_A
		);

		// Loop through the existing availability and update the dates.
		foreach ( $saved_availability as $saved ) {
			$year = (string) $saved['year'];

			// Update the availability in the database.
			$wpdb->update(
				$wpdb->prefix . 'gdbc_availability',
				$availability[ $year ],
				array( 'id' => $saved['id'] ),
				'%d',
				array( '%d' )
			);

			// Remove the availability from the array.
			unset( $availability[ $year ] );
		}

		// Insert any remaining availability.
		$post_object = get_post( $this->listing_id );
		$author      = empty( $post_object ) ? 0 : $post_object->post_author;
		foreach ( $availability as $year => $data ) {
			$data['post_id']  = $this->listing_id;
			$data['owner_id'] = $author;
			$data['year']     = (int) $year;

			$wpdb->insert( $wpdb->prefix . 'gdbc_availability', $data, '%d' );
		}
	}

	/**
	 * Releases the booking dates.
	 */
	public function release_dates() {
		global $wpdb;

		// Fetch the existing availability.
		$saved_availability = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}gdbc_availability WHERE post_id = %d",
				$this->listing_id
			)
		);

		// Loop through the existing availabilities.
		foreach ( $saved_availability as $saved ) {

			// Loop through the dates 1-366.
			for ( $i = 1; $i <= 366; $i++ ) {
				$day_of_year = "d$i";

				// If the date is set to this booking ID, then set it to null in the db.
				if ( intval( $saved->{$day_of_year} ) === intval( $this->id ) ) {
					$wpdb->update(
						$wpdb->prefix . 'gdbc_availability',
						array( $day_of_year => null ),
						array( 'id' => $saved->id ),
						'%d',
						array( '%d' )
					);
				}
			}
		}

		// Sync day rules.
		geodir_booking_sync_day_rules( $this->listing_id );
	}

	/**
	 * Returns the manage URL.
	 */
	public function get_manage_url() {
		return add_query_arg(
			array(
				'page'       => 'geodir-booking',
				'gd-booking' => absint( $this->id ),
			),
			admin_url( 'admin.php' )
		);
	}

	/**
	 * Checks whether the booking is exists.
	 *
	 * @return bool
	 */
	public function exists() {
		return ! empty( $this->id );
	}
}
