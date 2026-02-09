<?php

/**
 * Contains the main REST API manager class.
 *
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Bookings REST API manager class.
 */
class Geodir_Booking_Rest extends WP_REST_Controller {

	/**
	 * Loads the class.
	 *
	 */
	public function __construct() {
		$this->namespace = 'geodir/v2';
		$this->rest_base = 'booking';

		// Register rest routes.
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Registers REST routes.
	 *
	 * @since 1.0.0
	 */
	public function register_routes() {

		// Updates a pricing rule set.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/update_ruleset',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'update_ruleset' ),
					'permission_callback' => 'is_user_logged_in',
				),
				'schema' => '__return_empty_array',
			)
		);

		// Updates day rules.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/update_day_rules',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'update_day_rules' ),
					'permission_callback' => 'is_user_logged_in',
				),
				'schema' => '__return_empty_array',
			)
		);

		// Update room/listing title.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/update_listing_title',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'update_listing_title' ),
					'permission_callback' => 'is_user_logged_in',
				),
				'schema' => '__return_empty_array',
			)
		);

		// Process a booking.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/process_booking',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'process_booking' ),
					'permission_callback' => '__return_true',
				),
				'schema' => '__return_empty_array',
			)
		);

		// Customer refresh details.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)/refresh_details',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'customer_refresh_details' ),
					'permission_callback' => '__return_true',
				),
				'schema' => '__return_empty_array',
			)
		);

		// Fetch bookings.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/bookings',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_bookings' ),
					'permission_callback' => 'is_user_logged_in',
				),
				'schema' => '__return_empty_array',
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/customer_bookings',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_customer_bookings' ),
					'permission_callback' => 'is_user_logged_in',
				),
				'schema' => '__return_empty_array',
			)
		);

		// Save booking.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/save_booking',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'save_booking' ),
					'permission_callback' => 'is_user_logged_in',
				),
				'schema' => '__return_empty_array',
			)
		);

		// Search listings.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/search',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'search_listings' ),
					'permission_callback' => 'is_user_logged_in',
				),
				'schema' => '__return_empty_array',
			)
		);

		// Cancel booking.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/cancel_booking',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'cancel_booking' ),
					'permission_callback' => 'is_user_logged_in',
				),
				'schema' => '__return_empty_array',
			)
		);

		// Delete booking.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/delete_booking',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'delete_booking' ),
					'permission_callback' => 'is_user_logged_in',
				),
				'schema' => '__return_empty_array',
			)
		);

		// Cancel booking.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/customer_cancel_booking',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'customer_cancel_booking' ),
					'permission_callback' => 'is_user_logged_in',
				),
				'schema' => '__return_empty_array',
			)
		);

		// Customer confirms a booking.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)/customer_confirm',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'customer_confirm_booking' ),
					'permission_callback' => '__return_true',
				),
				'schema' => '__return_empty_array',
			)
		);

		// iCalendar Sync.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/ical_sync',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'ical_sync' ),
					'permission_callback' => 'is_user_logged_in',
				),
				'schema' => '__return_empty_array',
			)
		);

		// iCalendar Sync Status.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/ical_sync_status',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'ical_sync_status' ),
					'permission_callback' => 'is_user_logged_in',
				),
				'schema' => '__return_empty_array',
			)
		);
	}

	/**
	 * Updates a ruleset.
	 *
	 * @param WP_REST_Request $request
	 */
	public function update_ruleset( $request ) {
		global $plugin_prefix, $wpdb;

		// Check if there is a listing.
		if ( empty( $request['ruleset']['listing_id'] ) ) {
			return new WP_Error( 'no_listing', esc_html__( 'Listing not found.', 'geodir-booking' ), array( 'status' => 400 ) );
		}

		// Check if the current user is the owner of the listing.
		$listing = get_post( $request['ruleset']['listing_id'] );

		if ( ! ( ! empty( $listing ) && geodir_listing_belong_to_current_user( (int) $listing->ID ) ) ) {
			return new WP_Error( 'rest_forbidden', esc_html__( 'You do not have permission to perform this action.', 'geodir-booking' ), array( 'status' => rest_authorization_required_code() ) );
		}

		// Retrieve the rule set.
		$ruleset = new Geodir_Booking_Ruleset();
		$ruleset->set_args( $request['ruleset'] );
		$result = $ruleset->save();

		// Update the nightly price.
		$custom_fields   = GeoDir_Settings_Cpt_Cf::get_cpt_custom_fields( $listing->post_type );
		$has_price_field = wp_list_filter( $custom_fields, array( 'htmlvar_name' => 'gdbprice' ) );

		if ( ! is_wp_error( $result ) && ! empty( $has_price_field ) ) {
			$_result = $wpdb->update(
				$plugin_prefix . sanitize_key( $listing->post_type ) . '_detail',
				array( 'gdbprice' => floatval( $ruleset->nightly_price ) ),
				array( 'post_id' => $listing->ID ),
				array( '%f' ),
				array( '%d' )
			);
		}

		// Update and return the response.
		return rest_ensure_response( $result );
	}

	/**
	 * Updates day rules.
	 *
	 * @param WP_REST_Request $request
	 */
	public function update_day_rules( $request ) {

		// Ensure we have day rules.
		if ( empty( $request['day_rules'] ) ) {
			return new WP_Error( 'no_day_rules', esc_html__( 'Please provide at least 1 day rule.', 'geodir-booking' ), array( 'status' => 400 ) );
		}

		// Save each day rule.
		foreach ( $request['day_rules'] as $new_day_rule ) {

			// Check if there is a listing.
			if ( empty( $new_day_rule['listing_id'] ) ) {
				return new WP_Error( 'no_listing', esc_html__( 'Listing not found.', 'geodir-booking' ), array( 'status' => 400 ) );
			}

			// Check if the current user is the owner of the listing.
			$listing = get_post( $new_day_rule['listing_id'] );

			if ( ! ( ! empty( $listing ) && geodir_listing_belong_to_current_user( (int) $listing->ID ) ) ) {
				return new WP_Error( 'rest_forbidden', esc_html__( 'You do not have permission to perform this action.', 'geodir-booking' ), array( 'status' => rest_authorization_required_code() ) );
			}

			// Retrieve the day rule.
			$day_rule = new GeoDir_Booking_Day_Rule( $new_day_rule );

			if ( true === (bool) $new_day_rule['is_available'] ) {
				$day_rule->uid              = '';
				$day_rule->ical_prodid      = '';
				$day_rule->ical_summary     = '';
				$day_rule->ical_description = '';
				$day_rule->sync_id          = '';
				$day_rule->sync_queue_id    = '';
			}

			// Update the day rule.
			$result = $day_rule->save();

			// If there was an error, return it.
			if ( is_wp_error( $result ) ) {
				return $result;
			}

			$day_rule->release_dates();
		}

		// Sync with the availability table.
		$day_rules = geodir_booking_sync_day_rules( $request['day_rules'][0]['listing_id'] );

		// Update and return the response.
		return rest_ensure_response(
			array(
				'day_rules' => $day_rules,
			)
		);
	}

	/**
	 * Updates a listing's title.
	 *
	 * @param WP_REST_Request $request
	 */
	public function update_listing_title( $request ) {

		// Ensure we have a listing and title.
		if ( empty( $request['listing_id'] ) || empty( $request['new_title'] ) ) {
			return new WP_Error( 'no_listing', esc_html__( 'Listing not found.', 'geodir-booking' ), array( 'status' => 400 ) );
		}

		// Check if the current user is the owner of the listing.
		$listing = get_post( $request['listing_id'] );

		if ( ! ( ! empty( $listing ) && geodir_listing_belong_to_current_user( (int) $listing->ID ) ) ) {
			return new WP_Error( 'rest_forbidden', esc_html__( 'You do not have permission to perform this action.', 'geodir-booking' ), array( 'status' => rest_authorization_required_code() ) );
		}

		// Update the listing title.
		$result = wp_update_post(
			array(
				'ID'         => $request['listing_id'],
				'post_title' => sanitize_text_field( $request['new_title'] ),
			)
		);

		// Return the response.
		return rest_ensure_response( $result );
	}

	/**
	 * Processes a booking.
	 *
	 * @param WP_REST_Request $request
	 */
	public function process_booking( $request ) {

		// Verify nonce.
		if ( ! isset( $request['nonce'] ) || ! wp_verify_nonce( $request['nonce'], 'gd_booking_process_booking' ) ) {
			return new WP_Error( 'rest_invalid_nonce', esc_html__( 'Invalid nonce.', 'geodir-booking' ), array( 'status' => 400 ) );
		}

		// If we have a booking ID, check that the emails match.
		$booking_id = empty( $request['booking_id'] ) ? 0 : absint( $request['booking_id'] );

		if ( $booking_id ) {

			// Fetch the booking.
			$booking = new GeoDir_Customer_Booking( $booking_id );

			// Check if it exists.
			if ( ! $booking->exists() ) {
				return new WP_Error( 'no_booking', esc_html__( 'Booking not found. It might have been deleted.', 'geodir-booking' ), array( 'status' => 404 ) );
			}

			// Check the booking email.
			if ( $booking->email !== geodir_booking_decrypt( $request['encrypted_email'] ) ) {
				return new WP_Error( 'not_authorized', esc_html__( 'You do not have permission to edit this booking.', 'geodir-booking' ), array( 'status' => rest_authorization_required_code() ) );
			}

			// We can only update draft bookings.
			if ( 'draft' !== $booking->status ) {
				return new WP_Error( 'not_authorized', esc_html__( 'You can only edit draft bookings.', 'geodir-booking' ), array( 'status' => rest_authorization_required_code() ) );
			}
		}

		// Check if there is a listing.
		if ( empty( $request['listing_id'] ) ) {
			return new WP_Error( 'no_listing', esc_html__( 'Listing not found.', 'geodir-booking' ), array( 'status' => 400 ) );
		}

		// Ensure the listing is published.
		$listing = get_post( (int) $request['listing_id'] );

		// if $listing is room use post_parent to get listing options.
		$listing_id = ! empty( $listing->post_parent ) ? $listing->post_parent : $listing->ID;
		$post_info  = geodir_get_post_info( $listing_id );

		if ( empty( $post_info ) ) {
			$post_info = geodir_get_post_info( $listing->ID );
		}

		if ( empty( $listing ) || 'publish' !== $listing->post_status ) {
			return new WP_Error( 'inactive_listing', esc_html__( 'This listing is not published.', 'geodir-booking' ), array( 'status' => 400 ) );
		}

		// Ensure we have a name.
		if ( empty( $request['name'] ) ) {
			return new WP_Error( 'no_name', esc_html__( 'Please provide your name.', 'geodir-booking' ), array( 'status' => 400 ) );
		}

		// Ensure we have a phone.
		if ( empty( $request['phone'] ) ) {
			return new WP_Error( 'no_phone', esc_html__( 'Please provide your phone number.', 'geodir-booking' ), array( 'status' => 400 ) );
		}

		// Ensure we have a email.
		if ( empty( $request['email'] ) || ! is_email( $request['email'] ) ) {
			return new WP_Error( 'no_email', esc_html__( 'Please provide a valid email address.', 'geodir-booking' ), array( 'status' => 400 ) );
		}

		// Ensure we have a date.
		if ( empty( $request['start_date'] ) || empty( $request['end_date'] ) ) {
			return new WP_Error( 'no_date', esc_html__( 'Please provide a valid date.', 'geodir-booking' ), array( 'status' => 400 ) );
		}

		// End date cannot be before start date.
		if ( strtotime( $request['end_date'] ) < strtotime( $request['start_date'] ) ) {
			return new WP_Error( 'end_date_before_start_date', esc_html__( 'End date cannot be before start date.', 'geodir-booking' ), array( 'status' => 400 ) );
		}

		// Check for intersectigng bookings.
		$intersecting_bookings = geodir_get_bookings(
			array(
				'listings'                   => array( (int) $listing->ID ),
				'availability_checkin_date'  => $request['start_date'],
				'availability_checkout_date' => $request['end_date'],
				'status_in'                  => array(
					'pending_payment',
					'pending_confirmation',
					'confirmed',
				),
			)
		);

		$intersections_count = count( $intersecting_bookings );
		if ( $intersections_count > 0 ) {
			return new WP_Error( 'dates_not_available', sprintf( esc_html__( 'The dates from %1$s to %2$s are already booked. Please try different dates.', 'geodir-booking' ), $request['start_date'], $request['end_date'] ), array( 'status' => 400 ) );
		}

		// Allow plugins to validate the booking.
		$validation_result = apply_filters( 'geodir_booking_validate_booking', true, $request, $listing );

		if ( is_wp_error( $validation_result ) ) {
			return $validation_result;
		}

		$booking_details = array(
			'id'         => $booking_id,
			'listing_id' => (int) $listing->ID,
			'name'       => $request['name'],
			'email'      => $request['email'],
			'phone'      => $request['phone'],
			'start_date' => $request['start_date'],
			'end_date'   => $request['end_date'],
			'created'    => current_time( 'mysql' ),
			'guests'     => empty( $request['guests'] ) ? 1 : absint( $request['guests'] ),
			'adults'     => empty( $request['adults'] ) ? 1 : absint( $request['adults'] ),
			'children'   => absint( $request['children'] ),
			'infants'    => absint( $request['infants'] ),
			'pets'       => absint( $request['pets'] ),
		);

		$can_manange_listing = ( current_user_can( 'manage_options' ) || absint( $listing->post_author ) === get_current_user_id() ) ? true : false;

		// Only users that can manage the current listing can set a private note and change booking status.
		if ( true === $can_manange_listing ) {
			if ( $request->has_param( 'private_note' ) ) {
				$private_note                    = $request->get_param( 'private_note' );
				$booking_details['private_note'] = sanitize_textarea_field( $private_note );
			}

			if ( $request->has_param( 'status' ) ) {
				$booking_status = $request->get_param( 'status' );

				$valid_statuses = geodir_get_booking_statuses();
				if ( ! in_array( $booking_status, array_keys( $valid_statuses ) ) ) {
					return new WP_Error( 'invalid_status', esc_html__( 'Please select a valid status.', 'geodir-booking' ), array( 'status' => 400 ) );
				}

				$booking_details['status'] = sanitize_text_field( $booking_status );
			}

			// Leave a booking request for non instant book listings.
		} elseif ( ! $booking_id && isset( $post_info->gdb_instant_book ) && (bool) $post_info->gdb_instant_book === false ) {
			$booking_details['status'] = 'pending_confirmation';
		}

		// Create the booking.
		$result = geodir_save_booking( $booking_details );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		if ( true === $can_manange_listing && isset( $booking_details['status'] ) ) {
			if ( 'confirmed' === $booking_details['status'] ) {
				$result->confirm( true );

			} elseif ( 'pending_confirmation' === $booking_details['status'] ) {
				geodir_booking_send_email( 'user_booking_pending', $result );
				geodir_booking_send_email( 'owner_booking_request', $result );
			}

			$redirect = false;
		} else {
			if ( isset( $booking_details['status'] ) && 'pending_confirmation' === $booking_details['status'] ) {
				geodir_booking_send_email( 'user_booking_pending', $result );
				geodir_booking_send_email( 'owner_booking_request', $result );
			} else {
				$confirmed = $result->customer_confirm();

				// Abort if an error occured.
				if ( is_wp_error( $confirmed ) ) {
					return $confirmed;
				}
			}

			$redirect = $result->get_payment_url();
		}

		// Return the booking details.
		return rest_ensure_response(
			array(
				'booking'         => $result->to_array(),
				'listing'         => $result->get_listing_details(),
				'booking_id'      => $result->id,
				'encrypted_email' => geodir_booking_encrypt( $result->email ),
				'redirect'        => $redirect,
			)
		);
	}

	/**
	 * Fired when a customer refreshes a booking.
	 *
	 * @param WP_REST_Request $request
	 */
	public function customer_refresh_details( $request ) {

		// Verify nonce.
		if ( ! isset( $request['nonce'] ) || ! wp_verify_nonce( $request['nonce'], 'gd_booking_process_booking' ) ) {
			return new WP_Error( 'rest_invalid_nonce', esc_html__( 'Invalid nonce.', 'geodir-booking' ), array( 'status' => 400 ) );
		}

		// Fetch the booking.
		$booking = new GeoDir_Customer_Booking( (int) $request['id'] );

		// Check if it exists.
		if ( ! $booking->exists() ) {
			return new WP_Error( 'no_booking', esc_html__( 'Booking not found. It might have been deleted.', 'geodir-booking' ), array( 'status' => 404 ) );
		}

		// Check the booking email.
		if ( $booking->email !== geodir_booking_decrypt( $request['encrypted_email'] ) ) {
			return new WP_Error( 'not_authorized', esc_html__( 'This booking belongs to a different user.', 'geodir-booking' ), array( 'status' => rest_authorization_required_code() ) );
		}

		$details = array(
			'email'           => $booking->email,
			'name'            => $booking->name,
			'phone'           => $booking->phone,
			'encrypted_email' => geodir_booking_encrypt( $booking->email ),
		);

		if ( 'draft' === $booking->status ) {
			$details['confirmBooking'] = true;
			$details['bookingDetails'] = $booking->to_array();
			$details['booking_id']     = $booking->id;
			$details['start_date']     = $booking->id;
			$details['end_date']       = $booking->id;
		}

		return rest_ensure_response( $details );
	}

	/**
	 * Fired when a customer cancels a booking.
	 *
	 * @param WP_REST_Request $request
	 */
	public function customer_cancel_booking( $request ) {

		// Verify nonce.
		if ( ! isset( $request['nonce'] ) || ! wp_verify_nonce( $request['nonce'], 'geodir_booking_cancel_booking' ) ) {
			return new WP_Error( 'rest_invalid_nonce', esc_html__( 'Invalid nonce.', 'geodir-booking' ), array( 'status' => 400 ) );
		}

		// Fetch the booking.
		$booking = new GeoDir_Customer_Booking( (int) $request['id'] );

		// Check if it exists.
		if ( ! $booking->exists() ) {
			return new WP_Error( 'no_booking', esc_html__( 'Booking not found. It might have been deleted.', 'geodir-booking' ), array( 'status' => 404 ) );
		}

		// Check the booking email.
		if ( $booking->email !== geodir_booking_decrypt( $request['encrypted_customer_email'] ) ) {
			return new WP_Error( 'not_authorized', esc_html__( 'You do not have permission to cancel this booking.', 'geodir-booking' ), array( 'status' => rest_authorization_required_code() ) );
		}

		// Cancel the booking.
		$booking->cancel();

		return rest_ensure_response( $booking->get_customer_details() );
	}

	/**
	 * Fired when a customer confirms a booking.
	 *
	 * @param WP_REST_Request $request
	 */
	public function customer_confirm_booking( $request ) {

		// Verify nonce.
		if ( ! isset( $request['nonce'] ) || ! wp_verify_nonce( $request['nonce'], 'gd_booking_process_booking' ) ) {
			return new WP_Error( 'rest_invalid_nonce', esc_html__( 'Invalid nonce.', 'geodir-booking' ), array( 'status' => 400 ) );
		}

		// Fetch the booking.
		$booking = new GeoDir_Customer_Booking( (int) $request['id'] );

		// Check if it exists.
		if ( ! $booking->exists() ) {
			return new WP_Error( 'no_booking', esc_html__( 'Booking not found. It might have been deleted.', 'geodir-booking' ), array( 'status' => 404 ) );
		}

		// Check the booking email.
		if ( $booking->email !== geodir_booking_decrypt( $request['encrypted_email'] ) ) {
			return new WP_Error( 'not_authorized', esc_html__( 'You do not have permission to confirm this booking.', 'geodir-booking' ), array( 'status' => rest_authorization_required_code() ) );
		}

		return rest_ensure_response( $booking->customer_confirm() );
	}

	/**
	 * Fetch bookings for a listing.
	 *
	 * @param WP_REST_Request $request The REST API request object.
	 * @return WP_Error|WP_REST_Response The bookings data or an error response.
	 */
	public function get_bookings( $request ) {
		if ( ! isset( $request['nonce'] ) || ! wp_verify_nonce( $request['nonce'], 'gd_booking_view_bookings' ) ) {
			return new WP_Error( 'rest_invalid_nonce', esc_html__( 'Invalid nonce.', 'geodir-booking' ), array( 'status' => 400 ) );
		}

		// Check if there is a listing.
		if ( empty( $request['listing_id'] ) ) {
			return new WP_Error( 'no_listing', esc_html__( 'Listing not found.', 'geodir-booking' ), array( 'status' => 400 ) );
		}

		// Ensure the listing is published.
		$listing_id = (int) $request['listing_id'];
		$listing    = get_post( $listing_id );

		if ( empty( $listing ) ) {
			return new WP_Error( 'no_listing', esc_html__( 'Listing not found.', 'geodir-booking' ), array( 'status' => 404 ) );
		}

		// Check that the user can view the listing.
		if ( ! geodir_listing_belong_to_current_user( (int) $listing->ID ) ) {
			return new WP_Error( 'cannot_view_listing', esc_html__( 'You do not have permission to manage this listing.', 'geodir-booking' ), array( 'status' => 400 ) );
		}

		$display_imported_bookings = isset( $request['imported'] ) ? (bool) $request['imported'] : false;

		$args = array(
			'listings' => array( $listing_id ),
		);

		// Filter out imported bookings.
		if ( ! $display_imported_bookings ) {
			$args['is_imported'] = false;
		}

		$bookings = geodir_get_bookings( $args );

		return rest_ensure_response( $bookings );
	}


	/**
	 * Fetch customer bookings for a listing.
	 *
	 * @param WP_REST_Request $request
	 */
	public function get_customer_bookings( $request ) {

		// Check if there is a listing.
		if ( empty( $request['listing_id'] ) ) {
			return new WP_Error( 'no_listing', esc_html__( 'Listing not found.', 'geodir-booking' ), array( 'status' => 400 ) );
		}

		// Ensure the listing is published.
		$listing = get_post( (int) $request['listing_id'] );

		if ( empty( $listing ) ) {
			return new WP_Error( 'no_listing', esc_html__( 'Listing not found.', 'geodir-booking' ), array( 'status' => 404 ) );
		}

		// Prepare current user's email.
		$current_user = wp_get_current_user();

		// Fetch the bookings.
		$bookings = geodir_get_bookings(
			array(
				'listings' => array( (int) $request['listing_id'] ),
				'email'    => $current_user->user_email,
			)
		);

		$prepared_results = array(
			'listing'  => array(
				'title' => $listing->post_title,
				'url'   => geodir_get_listing_url( $listing->ID ),
				'image' => get_the_post_thumbnail_url( $listing->ID, 'thumbnail' ),
			),
			'bookings' => array(),
		);

		foreach ( $bookings as $booking ) {
			$prepared_results['bookings'][] = $booking->get_customer_details();
		}

		return rest_ensure_response( $prepared_results );
	}

	/**
	 * Cancels a booking.
	 *
	 * @param WP_REST_Request $request
	 */
	public function cancel_booking( $request ) {

		// Verify nonce.
		if ( ! isset( $request['nonce'] ) || ! wp_verify_nonce( $request['nonce'], 'gd_booking_view_bookings' ) ) {
			return new WP_Error( 'rest_invalid_nonce', esc_html__( 'Invalid nonce.', 'geodir-booking' ), array( 'status' => 400 ) );
		}

		// Check if there is a listing.
		if ( empty( $request['listing_id'] ) ) {
			return new WP_Error( 'no_listing', esc_html__( 'Listing not found.', 'geodir-booking' ), array( 'status' => 400 ) );
		}

		// Ensure the listing is published.
		$listing = get_post( (int) $request['listing_id'] );

		if ( empty( $listing ) ) {
			return new WP_Error( 'no_listing', esc_html__( 'Listing not found.', 'geodir-booking' ), array( 'status' => 404 ) );
		}

		// Check that the user can view the listing.
		if ( ! geodir_listing_belong_to_current_user( (int) $listing->ID ) ) {
			return new WP_Error( 'cannot_view_listing', esc_html__( 'You do not have permission to manage this listing.', 'geodir-booking' ), array( 'status' => 400 ) );
		}

		// Check if there is a booking.
		if ( empty( $request['id'] ) ) {
			return new WP_Error( 'no_booking', esc_html__( 'Booking not found.', 'geodir-booking' ), array( 'status' => 400 ) );
		}

		$booking = geodir_get_booking( (int) $request['id'] );

		if ( empty( $booking ) || $booking->listing_id !== $listing->ID ) {
			return new WP_Error( 'no_booking', esc_html__( 'Booking not found.', 'geodir-booking' ), array( 'status' => 404 ) );
		}

		// Set the private note.
		$booking->private_note = empty( $request['private_note'] ) ? '' : $request['private_note'];

		// Cancel the booking.
		$booking->cancel();

		return rest_ensure_response( $booking->to_array() );
	}

	/**
	 * Saves a booking.
	 *
	 * @param WP_REST_Request $request
	 */
	public function save_booking( $request ) {

		// Verify nonce.
		if ( ! isset( $request['nonce'] ) || ! wp_verify_nonce( $request['nonce'], 'gd_booking_view_bookings' ) ) {
			return new WP_Error( 'rest_invalid_nonce', esc_html__( 'Invalid nonce.', 'geodir-booking' ), array( 'status' => 400 ) );
		}

		// Check if there is a listing.
		if ( empty( $request['listing_id'] ) ) {
			return new WP_Error( 'no_listing', esc_html__( 'Listing not found.', 'geodir-booking' ), array( 'status' => 400 ) );
		}

		// Ensure the listing is published.
		$listing = get_post( (int) $request['listing_id'] );

		if ( empty( $listing ) ) {
			return new WP_Error( 'no_listing', esc_html__( 'Listing not found.', 'geodir-booking' ), array( 'status' => 404 ) );
		}

		// Check that the user can view the listing.
		if ( ! geodir_listing_belong_to_current_user( (int) $listing->ID ) ) {
			return new WP_Error( 'cannot_view_listing', esc_html__( 'You do not have permission to manage this listing.', 'geodir-booking' ), array( 'status' => 400 ) );
		}

		// Check if there is a booking.
		if ( empty( $request['id'] ) ) {
			return new WP_Error( 'no_booking', esc_html__( 'Booking not found.', 'geodir-booking' ), array( 'status' => 400 ) );
		}

		$booking = geodir_get_booking( (int) $request['id'] );

		if ( empty( $booking ) || $booking->listing_id !== $listing->ID ) {
			return new WP_Error( 'no_booking', esc_html__( 'Booking not found.', 'geodir-booking' ), array( 'status' => 404 ) );
		}

		// Ensure we have a name.
		if ( $request->has_param( 'name' ) ) {
			$customer_name = sanitize_text_field( $request->get_param( 'name' ) );

			if ( empty( $customer_name ) ) {
				return new WP_Error( 'no_name', esc_html__( 'Please provide your name.', 'geodir-booking' ), array( 'status' => 400 ) );
			}

			$booking->name = $customer_name;
		}

		// Ensure we have a phone.
		if ( $request->has_param( 'phone' ) ) {
			$customer_phone = sanitize_text_field( $request->get_param( 'phone' ) );

			if ( empty( $customer_phone ) ) {
				return new WP_Error( 'no_phone', esc_html__( 'Please provide your phone number.', 'geodir-booking' ), array( 'status' => 400 ) );
			}

			$booking->phone = $customer_phone;
		}

		// Ensure we have a email.
		if ( $request->has_param( 'email' ) ) {
			$customer_email = sanitize_email( $request->get_param( 'email' ) );

			if ( empty( $customer_email ) || ! is_email( $customer_email ) ) {
				return new WP_Error( 'no_email', esc_html__( 'Please provide a valid email address.', 'geodir-booking' ), array( 'status' => 400 ) );
			}

			$booking->email = $customer_email;
		}

		// Set the private note.
		if ( $request->has_param( 'private_note' ) ) {
			$private_note = sanitize_textarea_field( $request->get_param( 'private_note' ) );

			$booking->private_note = $private_note;
		}

		// Booking current status.
		$current_status = ! empty( $booking->status ) ? $booking->status : '';

		// Set the booking status.
		if ( $request->has_param( 'status' ) ) {
			$booking_status = sanitize_text_field( $request->get_param( 'status' ) );

			$valid_statuses = geodir_get_booking_statuses();

			if ( ! in_array( $booking_status, array_keys( $valid_statuses ) ) ) {
				return new WP_Error( 'invalid_status', esc_html__( 'Please select a valid status.', 'geodir-booking' ), array( 'status' => 400 ) );
			}

			$booking->status = $booking_status;
		}

		// Maybe set the booking status.
		if ( 'pending_confirmation' === $current_status && ! empty( $request['status'] ) && in_array( $request['status'], array( 'confirmed', 'rejected' ) ) ) {
			$booking->status = $current_status;

			if ( empty( $booking->id ) ) {
				$booking->save();
			}

			if ( 'confirmed' === $request['status'] ) {
				$booking->confirm( true );
			} else {
				$booking->reject();
			}
		} else {
			$booking->save();
		}

		return rest_ensure_response( $booking->to_array() );
	}

	/**
	 * Deletes a booking.
	 *
	 * @param WP_REST_Request $request
	 */
	public function delete_booking( $request ) {

		// Verify nonce.
		if ( ! isset( $request['nonce'] ) || ! wp_verify_nonce( $request['nonce'], 'gd_booking_view_bookings' ) ) {
			return new WP_Error( 'rest_invalid_nonce', esc_html__( 'Invalid nonce.', 'geodir-booking' ), array( 'status' => 400 ) );
		}

		// Check if there is a booking.
		if ( empty( $request['booking_id'] ) ) {
			return new WP_Error( 'no_booking', esc_html__( 'Booking not found.', 'geodir-booking' ), array( 'status' => 400 ) );
		}

		// Ensure the listing is published.
		$booking = geodir_get_booking( (int) $request['booking_id'] );

		if ( empty( $booking ) ) {
			return new WP_Error( 'no_booking', esc_html__( 'Booking not found.', 'geodir-booking' ), array( 'status' => 400 ) );
		}

		$listing = get_post( (int) $booking->listing_id );

		// Check that the user can view the listing.
		if ( get_current_user_id() !== $listing->post_author && ! current_user_can( 'manage_options' ) ) {
			return new WP_Error( 'cannot_view_listing', esc_html__( 'You do not have permission to manage this listing.', 'geodir-booking' ), array( 'status' => 400 ) );
		}

		$deleted = geodir_delete_booking( $booking->get_id() );

		return rest_ensure_response(
			array(
				'deleted' => (bool) $deleted,
			)
		);
	}

	/**
	 * Search Listings.
	 *
	 * @param WP_REST_Request $request
	 */
	public function search_listings( $request ) {
		$nonce = $request->get_param( 'nonce' );

		// Verify nonce.
		if ( ! $nonce || ! wp_verify_nonce( $nonce, 'gd_booking_view_bookings' ) ) {
			return new WP_Error( 'rest_invalid_nonce', esc_html__( 'Invalid nonce.', 'geodir-booking' ), array( 'status' => 400 ) );
		}

		$checkin_date  = sanitize_text_field( $request->get_param( 'checkin_date' ) );
		$checkout_date = sanitize_text_field( $request->get_param( 'checkout_date' ) );
		$listing_id    = $request->has_param( 'listing_id' ) ? absint( (int) $request->get_param( 'listing_id' ) ) : 0;
		$adults        = $request->has_param( 'adults' ) ? absint( (int) $request->get_param( 'adults' ) ) : 0;
		$children      = $request->has_param( 'children' ) ? absint( (int) $request->get_param( 'children' ) ) : 0;

		if ( empty( $checkin_date ) || empty( $checkout_date ) ) {
			return rest_ensure_response(
				array(
					'success' => true,
					'message' => esc_html__( 'Check-in and check-out dates are required.', 'geodir-booking' ),
				)
			);
		} elseif ( strtotime( $checkout_date ) < strtotime( $checkin_date ) ) {
			return rest_ensure_response(
				array(
					'success' => true,
					'message' => esc_html__( 'Check-out date cannot be before check-in date.', 'geodir-booking' ),
				)
			);
		}

		$add_booking_page = GeoDir_Booking_Add_Booking_Page::instance();

		$rooms    = $add_booking_page->get_available_rooms( $checkin_date, $checkout_date, $listing_id );
		$rooms    = $add_booking_page->filter_rooms_by_capacity( $rooms, $adults, $children );
		$listings = $add_booking_page->pull_rooms_info( $rooms );

		$found_listings = $found_rooms = count( $listings );
		$message        = '';

		if ( $found_listings > 0 ) {
			$message = sprintf(
				esc_html( _n( '%s listing found', '%s listings found', $found_listings, 'geodir-booking' ) ),
				esc_html( number_format_i18n( $found_listings ) )
			);
		} else {
			$message = esc_html__( 'No listings found', 'geodir-booking' );
		}

		if ( $listing_id && isset( $listings[ $listing_id ] ) && isset( $listings[ $listing_id ]['rooms'] ) ) {
			$found_rooms = count( $listings[ $listing_id ]['rooms'] );
			if ( $found_rooms > 0 ) {
				$message = sprintf(
					esc_html( _n( '%s room found', '%s rooms found', $found_rooms, 'geodir-booking' ) ),
					esc_html( number_format_i18n( $found_rooms ) )
				);
			} else {
				$message = esc_html__( 'No rooms found', 'geodir-booking' );
			}
		}

		$message .= sprintf(
			esc_html__( ' from %1$s - till %2$s', 'geodir-booking' ),
			esc_html( geodir_booking_date( $checkin_date, 'view_day' ) ),
			esc_html( geodir_booking_date( $checkout_date, 'view_day' ) )
		);

		if ( $found_listings === 0 || $found_rooms === 0 ) {
			return rest_ensure_response(
				array(
					'success' => true,
					'message' => $message,
				)
			);
		}

		return rest_ensure_response(
			array(
				'success'        => true,
				'listings_found' => $message,
				'listings'       => $listings,
			)
		);
	}

	/**
	 * Ical sync.
	 *
	 * @param WP_REST_Request $request
	 */
	public function ical_sync( $request ) {
		// Check if there is a listing.
		if ( empty( $request['listing_id'] ) ) {
			return new WP_Error( 'no_listing', esc_html__( 'Listing not found.', 'geodir-booking' ), array( 'status' => 400 ) );
		}

		if ( ! isset( $request['sync_urls'] ) ) {
			return new WP_Error( 'no_sync_urls', esc_html__( 'Sync URLs not found.', 'geodir-booking' ), array( 'status' => 400 ) );
		}

		// Ensure the listing is published.
		$listing = get_post( (int) $request['listing_id'] );

		if ( empty( $listing ) ) {
			return new WP_Error( 'no_listing', esc_html__( 'Listing not found.', 'geodir-booking' ), array( 'status' => 404 ) );
		}

		// Check that the user can view the listing.
		if ( ! geodir_listing_belong_to_current_user( (int) $listing->ID ) ) {
			return new WP_Error( 'cannot_view_listing', esc_html__( 'You do not have permission to manage this listing.', 'geodir-booking' ), array( 'status' => 400 ) );
		}

		$sync_urls = (array) $request['sync_urls'];

		$sync_urls = array_filter( $sync_urls );
		$sync_urls = array_unique( $sync_urls );
		$sync_urls = array_map( 'wp_unslash', $sync_urls );
		$sync_urls = array_map( 'esc_url_raw', $sync_urls );

		GeoDir_Booking_Sync_Urls::instance()->update_urls( $listing->ID, $sync_urls );

		$importer     = GeoDir_Booking_Queued_Sync::instance();
		$logs_handler = new GeoDir_Booking_Logs_Handler();

		$importer->sync(
			array(
				$listing->ID,
			)
		);

		ob_start();
		$logs_handler->display_stats( $importer->synchronizer->stats->get_stats() );
		$logs_handler->display_logs( $importer->synchronizer->logger->get_logs() );
		$sync_progress = ob_get_contents();
		ob_end_clean();

		return rest_ensure_response(
			array(
				'in_progress' => $importer->synchronizer->is_in_progress(),
				'queue_id'    => $importer->synchronizer->stats->get_queue_id(),
				'stats'       => $sync_progress,
			)
		);
	}

	/**
	 * Ical sync status.
	 *
	 * @param WP_REST_Request $request
	 */
	public function ical_sync_status( $request ) {
		// Check if there is a listing.
		if ( ! isset( $request['listing_id'] ) || empty( $request['listing_id'] ) ) {
			return new WP_Error( 'no_listing', esc_html__( 'Listing not found.', 'geodir-booking' ), array( 'status' => 400 ) );
		}

		// Check if there is a queue ID.
		if ( ! isset( $request['queue_id'] ) || empty( $request['queue_id'] ) ) {
			return new WP_Error( 'no_queue', esc_html__( 'Queue not found.', 'geodir-booking' ), array( 'status' => 400 ) );
		}

		// Check if the current user is the owner of the listing.
		$listing = get_post( $request['listing_id'] );

		if ( ! ( ! empty( $listing ) && geodir_listing_belong_to_current_user( (int) $listing->ID ) ) ) {
			return new WP_Error( 'rest_forbidden', esc_html__( 'You do not have permission to perform this action.', 'geodir-booking' ), array( 'status' => rest_authorization_required_code() ) );
		}

		$queue_id = absint( $request['queue_id'] );

		$uploader    = GeoDir_Booking_Background_Uploader::instance();
		$sync_stats  = new GeoDir_Booking_Stats( (int) $queue_id );
		$stats       = $sync_stats->get_stats();
		$sync_logger = new GeoDir_Booking_Logger( (int) $queue_id );
		$logs        = $sync_logger->get_logs();

		$logs_handler = new GeoDir_Booking_Logs_Handler();

		ob_start();
		$logs_handler->display_stats( $stats );
		$sync_stats = ob_get_contents();
		ob_end_clean();

		ob_start();
		$logs_handler->display_logs( $logs );
		$sync_logs = ob_get_contents();
		ob_end_clean();

		return rest_ensure_response(
			array(
				'booked_dates' => GeoDir_Booking::get_booked_dates( $listing->ID ),
				'in_progress'  => $uploader->is_in_progress(),
				'logs'         => $sync_logs,
				'stats'        => $sync_stats,
			)
		);
	}
}
