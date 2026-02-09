<?php
/**
 * Contains plugin functions.
 *
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Retrieves an option value.
 *
 * @param string $key Option key to retrieve.
 * @param mixed $default The default value.
 * @return mixed
 */
function geodir_booking_get_option( $key, $default = null, $package_id = null, $listing_id = null ) {

	// Fetch the option from the listing.
	if ( $listing_id ) {
		$value = get_post_meta( $listing_id, '_booking_' . $key, true );

		if ( '' !== $value ) {
			return $value;
		}
	}

	// Fetch from the package.
	if ( $package_id && function_exists( 'geodir_pricing_get_meta' ) ) {
		$value = geodir_pricing_get_meta( $package_id, 'geodir_booking_' . $key, true );

		if ( '' !== $value ) {
			return $value;
		}
	}

	// Fetch from saved options and return the default value if not set.
	$options = get_option( 'geodir_booking' );

	if ( empty( $options ) ) {
		$options = array();
	}

	return isset( $options[ $key ] ) ? $options[ $key ] : $default;
}

/**
 * Checks if booking is enabled for a listing.
 *
 * @param int $listing_id The listing ID.
 */
function geodir_booking_is_enabled( $listing_id ) {
	global $gd_post, $gdb_booking_posts;

	if ( empty( $gdb_booking_posts ) ) {
		$gdb_booking_posts = array();
	}

	// Cached on page.
	if ( ! empty( $listing_id ) && isset( $gdb_booking_posts[ $listing_id ] ) ) {
		return $gdb_booking_posts[ $listing_id ];
	}

	$post_type  = get_post_type( $listing_id );
	$package_id = geodir_get_post_package_id( $listing_id );

	if ( ! GeoDir_Post_types::supports( $post_type, 'gdbooking' ) ) {
		$gdb_booking_posts[ $listing_id ] = false;

		return false;
	}

	if ( class_exists( 'GeoDir_Pricing_Package', false ) && ! GeoDir_Pricing_Package::check_field_visibility( true, 'gdbooking', $package_id, $post_type ) ) {
		$gdb_booking_posts[ $listing_id ] = false;

		return false;
	}

	// check if setting set
	if ( isset( $gd_post->ID ) && $listing_id == $gd_post->ID && empty( $gd_post->gdbooking ) ) {
		$gdb_booking_posts[ $listing_id ] = false;

		return false;
	}

	$gdb_booking_posts[ $listing_id ] = true;

	return true;
}

/**
 * Returns the deposit %ge.
 *
 * @param int $listing_id
 * @return float
 */
function geodir_booking_get_deposit_amount( $listing_id = null ) {

	$package_id   = empty( $listing_id ) ? null : geodir_get_post_package_id( $listing_id );
	$payment_type = geodir_booking_get_option( 'payment_type', 'full', $package_id, $listing_id );
	$deposit      = (float) geodir_booking_get_option( 'deposit', 10, $package_id, $listing_id );

	// Abort if we're not collecting deposits.
	if ( 'deposit' !== $payment_type ) {
		return 100;
	}

	// Deposit should be between 0-100.
	return max( 0, min( 100, $deposit ) );
}

/**
 * Returns the site commission %ge.
 *
 * @param int $listing_id
 * @return float
 */
function geodir_booking_get_site_commission_amount( $listing_id = null ) {

	$package_id = empty( $listing_id ) ? null : geodir_get_post_package_id( $listing_id );
	$commision  = (float) geodir_booking_get_option( 'commision', 10, $package_id, $listing_id );

	// Deposit should be between 0-100.
	return max( 0, min( 100, $commision ) );
}

/**
 * Returns the corrent payment form to use.
 *
 * @param int $listing_id
 * @return int
 */
function geodir_booking_get_payment_form( $listing_id = null ) {

	$package_id   = empty( $listing_id ) ? null : geodir_get_post_package_id( $listing_id );
	$payment_form = (int) geodir_booking_get_option( 'payment_form', 10, $package_id, $listing_id );

	if ( empty( $payment_form ) || 'publish' !== get_post_status( $payment_form ) ) {
		return (int) wpinv_get_default_payment_form();
	}

	return (int) $payment_form;
}

/**
 * Returns the corrent payment type to use.
 *
 * @param int $listing_id
 * @return int
 */
function geodir_booking_get_payment_type( $listing_id = null ) {

	$package_id = empty( $listing_id ) ? null : geodir_get_post_package_id( $listing_id );
	return geodir_booking_get_option( 'payment_type', 'full', $package_id, $listing_id );
}

/**
 * Fetches the booking item.
 *
 * @return int
 */
function geodir_booking_item() {

	$booking_item = get_option( 'geodir_booking_item' );

	if ( empty( $booking_item ) || 'publish' !== get_post_status( $booking_item ) ) {
		$item = new WPInv_Item();
		$item->set_price( 0 );
		$item->set_status( 'publish' );
		$item->set_name( __( 'Booking', 'geodir-booking' ) );
		$item->save();

		$booking_item = $item->get_id();

		update_option( 'geodir_booking_item', $booking_item );
	}

	return (int) $booking_item;
}

/**
 * Calculates a percentage.
 *
 * @param float $percentage
 * @param float $amount
 * @return float
 */
function geodir_booking_calculate_percentage( $percentage, $amount ) {

	// Avoid division by zero.
	if ( ! $percentage || ! $amount ) {
		return 0;
	}

	return ( floatval( $percentage ) / 100 ) * floatval( $amount );
}

/**
 * Returns either the package amount or the default amount.
 *
 * @param mixed $package_value
 * @param mixed $default_value
 * @return mixed
 */
function geodir_booking_sanitize_package_option( $package_value, $default_value ) {
	return '' === $package_value ? $default_value : $package_value;
}

/**
 * Returns all supported booking statuses.
 *
 * @return string[]
 */
function geodir_get_booking_statuses() {
	return apply_filters(
		'geodir_get_booking_statuses',
		array(
			'draft'                => __( 'Draft', 'geodir-booking' ),
			'pending_payment'      => __( 'Pending Payment', 'geodir-booking' ),
			'pending_confirmation' => __( 'Pending Confirmation', 'geodir-booking' ),
			'confirmed'            => __( 'Confirmed', 'geodir-booking' ),
			'cancelled'            => __( 'Cancelled', 'geodir-booking' ),
			'rejected'             => __( 'Rejected', 'geodir-booking' ),
			'completed'            => __( 'Completed', 'geodir-booking' ),
			'refunded'             => __( 'Refunded', 'geodir-booking' ),
		)
	);
}

/**
 * Returns the booking status label.
 *
 * @param string $status The booking status.
 * @return string
 */
function geodir_get_booking_status_label( $status ) {
	$statuses = geodir_get_booking_statuses();

	return isset( $statuses[ $status ] ) ? $statuses[ $status ] : $status;
}

/**
 * Returns the booking status context.
 *
 * @param string $status The booking status.
 * @return string
 */
function geodir_get_booking_status_context( $status ) {
	$contexts = apply_filters(
		'geodir_get_booking_status_contexts',
		array(
			'draft'                => 'light',
			'pending_payment'      => 'secondary',
			'pending_confirmation' => 'dark',
			'confirmed'            => 'success',
			'cancelled'            => 'warning',
			'rejected'             => 'danger',
			'completed'            => 'primary',
		)
	);

	return isset( $contexts[ $status ] ) ? $contexts[ $status ] : 'dark';
}

/**
 * Retrieves a booking.
 *
 * @param int $booking_id The booking ID.
 *
 * @return false|GeoDir_Customer_Booking
 */
function geodir_get_booking( $booking_id ) {
	$booking = new GeoDir_Customer_Booking( $booking_id );
	return $booking->exists() ? $booking : false;
}

/**
 * Retrieves bookings.
 *
 * @param array $args
 *
 * @return GeoDir_Customer_Booking[]
 */
function geodir_get_bookings( $args ) {
	global $wpdb, $gd_bookings_last_query_count;

	$defaults = array(
		'include'                    => array(),
		'exclude'                    => array(),
		'listings'                   => array(),
		'status_in'                  => array(),
		'email'                      => 0,
		'sync_id'                    => '',
		'sync_queue_id'              => 0,
		'start_date'                 => null,
		'end_date'                   => null,
		'availability_checkin_date'  => null,
		'availability_checkout_date' => null,
		'is_imported'                => null,
		'limit'                      => 0,
		'paged'                      => 1,
		'orderby'                    => 'id',
		'order'                      => 'DESC',
		'count'                      => false,
	);

	$args = wp_parse_args( $args, $defaults );

	// Prepare listings.
	$listings         = array_unique( wp_parse_id_list( $args['listings'] ) );
	$args['listings'] = $listings;

	if ( ! empty( $args['listings'] ) ) {
		$args['listings'] = geodir_booking_post_ids( $args['listings'] );
	}

	foreach ( $listings as $listing_id ) {
		$rooms = geodir_get_listing_rooms( $listing_id );

		if ( ! empty( $rooms ) ) {
			$args['listings'] = array_merge( $args['listings'], $rooms );
		}
	}

	$where = '';

	// Prepare WHERE query.
	//$where .= ' AND status != "draft"'; // Show bookings added with draft status.

	if ( ! empty( $args['include'] ) ) {
		$where .= ' AND id IN (' . implode( ',', wp_parse_id_list( $args['include'] ) ) . ')';
	}

	if ( ! empty( $args['exclude'] ) ) {
		$where .= ' AND id NOT IN (' . implode( ',', wp_parse_id_list( $args['exclude'] ) ) . ')';
	}

	if ( ! empty( $args['listings'] ) ) {
		$where .= ' AND listing_id IN (' . implode( ',', array_unique( $args['listings'] ) ) . ')';
	}

	if ( ! empty( $args['status_in'] ) ) {
		$statuses = array_map( 'esc_sql', array_unique( $args['status_in'] ) );
		$statuses = array_map(
			function ( $status ) {
				return "'" . $status . "'";
			},
			$statuses
		);

		$where .= ' AND status IN (' . implode( ',', $statuses ) . ')';
	}

	if ( $args['email'] ) {
		$where .= $wpdb->prepare( ' AND email = %s', $args['email'] );
	}

	if ( $args['sync_id'] ) {
		$where .= $wpdb->prepare( ' AND sync_id = %s', $args['sync_id'] );
	}

	if ( $args['sync_queue_id'] ) {
		$where .= $wpdb->prepare( ' AND sync_queue_id = %d', (int) $args['sync_queue_id'] );
	}

	if ( ! empty( $args['search'] ) ) {
		$search = trim( rawurldecode( $args['search'] ), '*' );
		$like   = '%' . $wpdb->esc_like( trim( $search, '%' ) ) . '%';
		$where .= $wpdb->prepare( ' AND ( email LIKE %s OR name LIKE %s OR phone LIKE %s )', $like, $like, $like );
	}

	if ( ! is_null( $args['is_imported'] ) && is_bool( $args['is_imported'] ) ) {
		// Show only imported bookings.
		if ( true === (bool) $args['is_imported'] ) {
			$where .= " AND sync_id IS NOT NULL OR sync_id != '' ";
		} else {
			// Remove imported bookings.
			$where .= " AND sync_id IS NULL OR sync_id = '' ";
		}
	}

	if ( ! is_null( $args['start_date'] ) && strtotime( $args['start_date'] ) !== false ) {
		$where .= $wpdb->prepare( ' AND start_date >= %s', date( 'Y-m-d H:i:s', strtotime( $args['start_date'] ) ) );
	}

	if ( ! is_null( $args['end_date'] ) && strtotime( $args['end_date'] ) !== false ) {
		$where .= $wpdb->prepare( ' AND end_date <= %s', date( 'Y-m-d H:i:s', strtotime( $args['end_date'] ) ) );
	}

	// checks if the existing booking's check-out date is on or after the requested check-in date.
	if ( ! is_null( $args['availability_checkin_date'] ) && strtotime( $args['availability_checkin_date'] ) !== false ) {
		$where .= $wpdb->prepare( ' AND end_date > %s', date( 'Y-m-d', strtotime( $args['availability_checkin_date'] ) ) );
	}

	// checks if the existing booking's check-in date is on or before the requested check-out date.
	if ( ! is_null( $args['availability_checkout_date'] ) && strtotime( $args['availability_checkout_date'] ) !== false ) {
		$where .= $wpdb->prepare( ' AND start_date < %s', date( 'Y-m-d', strtotime( $args['availability_checkout_date'] ) ) );
	}

	// Prepare ORDER BY query.
	$orderby = 'id DESC';

	if ( ! empty( $args['orderby'] ) && in_array( $args['orderby'], array( 'id', 'listing_id', 'name', 'email', 'phone', 'status', 'start_date', 'end_date', 'created', 'modified', 'private_note', 'total_amount', 'payable_amount', 'service_fee', 'site_commission', 'deposit_amount', 'last_minute_discount_ge', 'early_bird_discount_ge', 'duration_discount_ge', 'date_amounts' ) ) ) {
		$order   = strtoupper( $args['order'] ) === 'ASC' ? 'ASC' : 'DESC';
		$orderby = " {$args['orderby']} {$order}";
	}

	// Prepare LIMIT query.
	$limit = '';
	if ( ! empty( $args['limit'] ) ) {
		$limit  = absint( $args['limit'] );
		$paged  = absint( $args['paged'] );
		$offset = $limit * ( $paged - 1 );
		$limit  = "LIMIT {$offset}, {$limit}";
	}

	$count = '';

	if ( $args['count'] ) {
		$count = 'SQL_CALC_FOUND_ROWS ';
	}

	// Prepare query.
	$query = "SELECT $count * FROM {$wpdb->prefix}gdbc_bookings WHERE 1=1 {$where} ORDER BY {$orderby} {$limit}";

	// Get bookings.
	$bookings = $wpdb->get_results( $query, ARRAY_A );

	if ( $args['count'] ) {
		$gd_bookings_last_query_count = $wpdb->get_var( 'SELECT FOUND_ROWS()' );
	}

	// Prepare bookings.
	$prepared = array();

	foreach ( $bookings as $args ) {
		$booking = new GeoDir_Customer_Booking( $args );
		$booking->set_args( $args );
		$prepared[] = $booking;
	}

	return $prepared;
}

/**
 * Returns an array of listing rooms.
 *
 * @param int $listing_id The listing ID.
 *
 * @return int[]
 */
function geodir_get_listing_rooms( $listing_id ) {
	$listing_id = geodir_booking_post_id( $listing_id );

	return array_filter( wp_parse_id_list( get_post_meta( $listing_id, 'gdb_rooms', true ) ) );
}

/**
 * Returns a listing's URL.
 *
 * @param int $listing_id The listing ID.
 *
 * @return string
 */
function geodir_get_listing_url( $listing_id ) {
	$post      = get_post( $listing_id );
	$parent_id = empty( $post->post_parent ) ? $listing_id : $post->post_parent;
	return get_permalink( $parent_id );
}

/**
 * Creates a new booking.
 *
 * @param array $args An array of arguments.
 * @see GeoDir_Customer_Booking::__construct()
 * @return GeoDir_Customer_Booking|WP_Error The booking data on success, or a WP_Error object on failure.
 */
function geodir_save_booking( $args ) {

	// Prepare args.
	$args = wp_parse_args(
		$args,
		array(
			'id' => 0,
		)
	);

	// If updating an existing booking, make sure that it exists.
	if ( ! empty( $args['id'] ) ) {
		$booking = new GeoDir_Customer_Booking( (int) $args['id'] );

		if ( ! $booking->exists() ) {
			return new WP_Error( 'booking_not_found', __( 'Booking not found.', 'geodir-booking' ) );
		}
	} else {
		unset( $args['id'] );
		$booking = new GeoDir_Customer_Booking();
	}

	$args['modified'] = current_time( 'mysql' );

	if ( ! empty( $args['listing_id'] ) ) {
		$args['listing_id'] = geodir_booking_post_id( $args['listing_id'] );
	}

	$booking->set_args( $args );

	// Save the booking.
	return $booking->save();
}

/**
 * Deletes a booking.
 *
 * @param int $booking_id The booking ID.
 * @return int|false Number of deleted bookings on success, or false on failure.
 */
function geodir_delete_booking( $booking_id ) {
	global $wpdb;

	$booking = new GeoDir_Customer_Booking( $booking_id );

	// Check if it exists.
	if ( $booking->exists() ) {
		// release booking dates.
		$booking->release_dates();

		return $wpdb->delete( $wpdb->prefix . 'gdbc_bookings', array( 'id' => (int) $booking->id ), array( '%d' ) );
	}

	return false;
}

/**
 * Formats a date for display.
 *
 * @since  1.0.0
 * @param  string $date Date string.
 * @param  string $context Either view, db or raw.
 * @param  string $fallback Fallback value.
 * @return string
 */
function geodir_booking_date( $date, $context = 'view', $fallback = '' ) {

	if ( empty( $fallback ) && 'db' !== $context ) {
		$fallback = '&mdash;';
	}

	if ( empty( $date ) ) {
		return $fallback;
	}

	if ( 'view' === $context ) {
		return date_i18n( geodir_date_time_format( ' @ ' ), strtotime( $date ) );
	}

	if ( 'view_day' === $context ) {
		return date_i18n( geodir_date_format(), strtotime( $date ) );
	}

	if ( 'db' === $context ) {
		return gmdate( 'Y-m-d H:i:s', strtotime( $date ) );
	}

	return gmdate( 'c', strtotime( $date ) );
}

/**
 * Syncs a listing's day rules with the availability table.
 *
 * @param int $listing_id The listing ID.
 */
function geodir_booking_sync_day_rules( $listing_id ) {
	global $wpdb;

	$this_year          = (int) gmdate( 'Y' );
	$listing_id         = geodir_booking_post_id( absint( $listing_id ) );
	$day_rules          = GeoDir_Booking::get_day_rules( $listing_id );
	$availability       = array();
	$saved_availability = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}gdbc_availability WHERE post_id = %d AND `year` > %d",
			$listing_id,
			$this_year - 1 // Only sync future dates.
		),
		ARRAY_A
	);

	// Prepare the day rules.
	foreach ( $day_rules as $day_rule ) {

		$year = absint( gmdate( 'Y', strtotime( $day_rule->rule_date ) ) );

		// Check if the day is not available and the date is in the future.
		if ( $day_rule->is_available || $year < $this_year ) {
			continue;
		}

		// Cast to string.
		$year = (string) $year;

		// Calculate the day of the year.
		$day_of_year = gmdate( 'z', strtotime( $day_rule->rule_date ) ) + 1;

		// Group availability by year.
		if ( ! isset( $availability[ $year ] ) ) {
			$availability[ $year ] = array();
		}

		$availability[ $year ][ "d$day_of_year" ] = 0; // null = available, 0 = not available, booking_id = booked.
	}

	// Process the saved availability.
	foreach ( $saved_availability as $saved ) {
		$year    = (string) $saved['year'];
		$changes = array();

		// Loop from 1 to 366.
		for ( $i = 1; $i <= 366; $i++ ) {
			$day_of_year = "d$i";

			// Abort if the day is already booked.
			if ( 0 !== $saved[ $day_of_year ] && null !== $saved[ $day_of_year ] ) {
				continue;
			}

			// Check if the day is available.
			if ( isset( $availability[ $year ][ $day_of_year ] ) && null === $saved[ $day_of_year ] ) { // was available, now not available.
				$changes[ $day_of_year ] = 0;
			} elseif ( null !== $saved[ $day_of_year ] ) { // was not available, but now is.
				$changes[ $day_of_year ] = null;
			}
		}

		// Process the changes.
		if ( ! empty( $changes ) ) {
			$wpdb->update(
				$wpdb->prefix . 'gdbc_availability',
				$changes,
				array( 'id' => $saved['id'] ),
				'%d',
				array( '%d' )
			);
		}

		// Remove the year from the availability array.
		unset( $availability[ $year ] );
	}

	// Insert any remaining availability.
	$post_object = get_post( $listing_id );
	$author      = empty( $post_object ) ? 0 : $post_object->post_author;
	foreach ( $availability as $year => $data ) {
		$data['post_id']  = $listing_id;
		$data['owner_id'] = $author;
		$data['year']     = (int) $year;

		$wpdb->insert( $wpdb->prefix . 'gdbc_availability', $data, '%d' );
	}

	// Return the day rules.
	return $day_rules;
}

/**
 * Retreives the booking details for a listing owner email.
 *
 * @param string $context
 * @return array
 */
function geodir_booking_listing_owner_email_details( $context = 'default' ) {

	$details = array(
		'listing'          => sprintf( __( 'Listing: %s', 'geodir-booking' ), '[#listing_link#]' ),
		'customer_name'    => sprintf( __( 'Customer name: %s', 'geodir-booking' ), '[#booking_customer_name#]' ),
		'customer_email'   => sprintf( __( 'Customer email: %s', 'geodir-booking' ), '[#booking_customer_email#]' ),
		'customer_phone'   => sprintf( __( 'Customer phone: %s', 'geodir-booking' ), '[#booking_customer_phone#]' ),
		'status'           => sprintf( __( 'Status: %s', 'geodir-booking' ), '[#booking_status#]' ),
		'created'          => sprintf( __( 'Booked: %s', 'geodir-booking' ), '[#booking_created_date_time#]' ),
		'check_in'         => sprintf( __( 'Check-in: %s', 'geodir-booking' ), '[#booking_check_in_date#]' ),
		'check_out'        => sprintf( __( 'Check-out: %s', 'geodir-booking' ), '[#booking_check_out_date#]' ),
		'payable'          => sprintf( __( 'Amount Payable: %s', 'geodir-booking' ), '[#booking_payable_amount#]' ),
		'potential_payout' => sprintf( __( 'Potential Payout: %s', 'geodir-booking' ), '[#booking_potential_payout_amount#]' ),
	);

	if ( 'refunded' === $context || 'cancel' === $context ) {
		unset( $details['check_in'] );
		unset( $details['check_out'] );
		unset( $details['potential_payout'] );

		$details['payable'] = sprintf( __( 'Amount: %s', 'geodir-booking' ), '[#booking_payable_amount#]' );
	}

	if ( 'confirmed' === $context ) {
		$details['payable'] = sprintf( __( 'Amount: %s', 'geodir-booking' ), '[#booking_payable_amount#]' );
	}

	return apply_filters( 'geodir_booking_listing_owner_email_details', $details, $context );
}

/**
 * Retreives the booking details for a listing customer email.
 *
 * @param string $context
 * @return array
 */
function geodir_booking_listing_customer_email_details( $context = 'default' ) {

	$details = array(
		'listing'        => sprintf( __( 'Listing: %s', 'geodir-booking' ), '[#listing_link#]' ),
		'listed_by'      => sprintf( __( 'Listed by: %s', 'geodir-booking' ), '[#post_author_name#]' ),
		'customer_name'  => sprintf( __( 'Customer name: %s', 'geodir-booking' ), '[#booking_customer_name#]' ),
		'customer_email' => sprintf( __( 'Customer email: %s', 'geodir-booking' ), '[#booking_customer_email#]' ),
		'customer_phone' => sprintf( __( 'Customer phone: %s', 'geodir-booking' ), '[#booking_customer_phone#]' ),
		'status'         => sprintf( __( 'Status: %s', 'geodir-booking' ), '[#booking_status#]' ),
		'created'        => sprintf( __( 'Booked: %s', 'geodir-booking' ), '[#booking_created_date_time#]' ),
		'check_in'       => sprintf( __( 'Check-in: %s', 'geodir-booking' ), '[#booking_check_in_date#]' ),
		'check_out'      => sprintf( __( 'Check-out: %s', 'geodir-booking' ), '[#booking_check_out_date#]' ),
		'payable'        => sprintf( __( 'Booking Amount: %s', 'geodir-booking' ), '[#booking_payable_amount#]' ),
		'service_fee'    => sprintf( __( 'Service Fee: %s', 'geodir-booking' ), '[#booking_service_fee#]' ),
	);

	if ( 'refunded' === $context || 'cancel' === $context ) {
		unset( $details['check_in'] );
		unset( $details['check_out'] );
	}

	return apply_filters( 'geodir_booking_listing_customer_email_details', $details, $context );
}

/**
 * Sends a booking notification email.
 *
 * @param string string $email_name For example, owner_booking_request.
 * @param GeoDir_Customer_Booking $booking The booking object.
 */
function geodir_booking_send_email( $email_name, $booking ) {

	$listing = get_post( $booking->listing_id );

	$GLOBALS['geodir_booking']->emails->send_email( $email_name, $listing, $booking );
}

/**
 * Encrypts a text string.
 *
 * @param string $plaintext The plain text string to encrypt.
 * @return string
 */
function geodir_booking_encrypt( $plaintext ) {

	$ivlen = openssl_cipher_iv_length( 'AES-128-CBC' );
	$iv    = substr( AUTH_SALT, 0, $ivlen );

	// Encrypt then encode.
	$encoded = base64_encode( openssl_encrypt( $plaintext, 'AES-128-CBC', AUTH_KEY, OPENSSL_RAW_DATA, $iv ) );

	// Make URL safe.
	return strtr( $encoded, '+/=', '._-' );
}

/**
 * Decrypts a text string.
 *
 * @param string $encoded The string to decode.
 * @return string
 */
function geodir_booking_decrypt( $encoded ) {

	// Decode.
	// @see geodir_booking_encrypt()
	$decoded = base64_decode( strtr( $encoded, '._-', '+/=' ) );

	if ( empty( $decoded ) ) {
		return '';
	}

	// Prepare args.
	$ivlen = openssl_cipher_iv_length( 'AES-128-CBC' );
	$iv    = substr( AUTH_SALT, 0, $ivlen );

	return openssl_decrypt( $decoded, 'AES-128-CBC', AUTH_KEY, OPENSSL_RAW_DATA, $iv );
}

function geodir_booking_price_placeholder( $placeholder ) {
	return sprintf(
		getpaid_get_price_format(),
		'<span class="getpaid-currency__symbol">' . wpinv_currency_symbol() . '</span>',
		$placeholder
	);
}

/**
 * Add our tabs to UsersWP account tabs.
 *
 * @since 1.0.0
 * @param  array $tabs
 * @return array
 */
function geodir_booking_filter_userswp_account_tabs( $tabs ) {

	if ( ! is_user_logged_in() ) {
		return $tabs;
	}

	$tabs['customer-bookings'] = array(
		'title' => __( 'My Bookings', 'geodir-booking' ),
		'icon'  => 'fas fa-book',
	);

	$listings = geodir_booking_get_listing_ids_by_user_id( get_current_user_id() );

	if ( ! empty( $listings ) ) {
		$tabs['owner-bookings'] = array(
			'title' => __( 'All Bookings', 'geodir-booking' ),
			'icon'  => 'fas fa-bold',
		);
	}

	return $tabs;
}
add_filter( 'uwp_account_available_tabs', 'geodir_booking_filter_userswp_account_tabs' );

/**
 * Display our UsersWP account tabs.
 *
 * @since 1.0.0
 * @param  array $tabs
 * @return array
 */
function geodir_booking_display_userswp_account_tabs( $tab ) {

	if ( 'customer-bookings' === $tab ) {
		echo do_shortcode( '[gd_customer_bookings]' );
	}

	if ( 'owner-bookings' === $tab ) {
		echo do_shortcode( '[gd_owner_bookings]' );
	}
}

add_action( 'uwp_account_form_display', 'geodir_booking_display_userswp_account_tabs' );

/**
 * Filters the account page title.
 *
 * @since  1.0.0
 * @param  string $title Current title.
 * @param  string $tab   Current tab.
 * @return string Title.
 */
function geodir_booking_filter_userswp_account_title( $title, $tab ) {

	if ( 'customer-bookings' === $tab ) {
		return __( 'My Bookings', 'geodir-booking' );
	}

	if ( 'owner-bookings' === $tab ) {
		return __( 'All Bookings', 'geodir-booking' );
	}

	return $title;
}
add_filter( 'uwp_account_page_title', 'geodir_booking_filter_userswp_account_title', 10, 2 );

/**
 * Fetches all listing IDs by a given user ID.
 *
 * @since 1.0.0
 * @param int $user_id The user ID.
 * @return array
 */
function geodir_booking_get_listing_ids_by_user_id( $user_id ) {
	global $wpdb;
	static $cache = array();

	if ( isset( $cache[ $user_id ] ) ) {
		return $cache[ $user_id ];
	}

	// Prepare query.
	$query = $wpdb->prepare( "SELECT post_id FROM {$wpdb->prefix}gdbc_availability WHERE 1=1 AND owner_id = %d", $user_id );

	// Get bookings.
	$listings = $wpdb->get_results( $query, ARRAY_A );

	if ( ! empty( $listings ) ) {
		foreach ( $listings as $listing ) {
			$cache[ $user_id ][] = absint( $listing['post_id'] );
		}
	}

	if ( isset( $cache[ $user_id ] ) ) {
		return $cache[ $user_id ];
	}

	return array();
}

/**
 * Retrieves teh cancellation policies.
 *
 * @return array
 */
function geodir_booking_get_cancellation_policies() {
	$policies = geodir_booking_get_option( 'cancellation_policies', array() );
	$policies = is_array( $policies ) ? $policies : array();
	return apply_filters( 'geodir_booking_cancellation_policies', $policies );
}

/**
 * Checks if we have cancellation policies.
 *
 * @return bool
 */
function geodir_booking_has_cancellation_policies() {
	return 0 < count( geodir_booking_get_cancellation_policies() );
}

/**
 * Retrieves the default cancellation policy.
 *
 * @return string|false
 */
function geodir_booking_get_default_cancellation_policy() {
	$default = geodir_booking_get_option( 'cancellation_policies_default', '' );

	if ( ! empty( $default ) ) {
		return $default;
	}

	$policies = geodir_booking_get_cancellation_policies();
	return ! empty( $policies ) ? key( $policies ) : '';
}

/**
 * Clean variables using sanitize_text_field. Arrays are cleaned recursively.
 * Non-scalar values are ignored.
 *
 * @param string|array $val The value to be cleaned.
 * @return string|array The cleaned value.
 */
function geodir_booking_clean( $val ) {
	if ( is_array( $val ) ) {
		return array_map( 'geodir_booking_clean', $val );
	} else {
		return is_scalar( $val ) ? sanitize_text_field( $val ) : $val;
	}
}

/**
 * Retrieve the domain of the site without the 'www.' prefix.
 *
 * @return string The domain of the site.
 */
function geodir_booking_site_domain() {
	$home_host = wp_parse_url( home_url(), PHP_URL_HOST ); // www.booking.coms
	return preg_replace( '/^www\./', '', $home_host );  // booking.com
}

/**
 * Check if the current page is within the admin section.
 *
 * @note Use this function after the admin_init hook.
 *
 * @return boolean True if the current page is in the admin section, otherwise false.
 */
function geodir_booking_is_current_page() {
	if ( ! is_admin() ) {
		return false;
	}

	$current_screen = get_current_screen();

	if ( $current_screen && $current_screen instanceof WP_Screen ) {
		return true;
	}

	return false;
}

/**
 * Parse the listing ID from the sync queue item.
 *
 * @param string $queue_item Sync queue item, like "%Timestamp%_%Listing ID%".
 * @return int The parsed Listing ID.
 */
function geodir_booking_parse_queue_listing_id( $queue_item ) {
	return (int) preg_replace( '/^\d+_(\d+)/', '$1', $queue_item );
}


/**
 * Adds a JOIN clause to retrieve bookable listings.
 *
 * This function modifies the SQL JOIN clause to retrieve bookable listings
 * when querying posts. It adds an INNER JOIN with the gd_place_detail table
 * based on specific conditions.
 *
 * @param string   $join  The JOIN clause of the SQL query.
 * @param WP_Query $query The WP_Query object representing the current query.
 * @return string The modified JOIN clause.
 */
function geodir_booking_join_bookable_listings( $join, $query ) {
	global $wpdb;

	// Check if the custom join for bookable listings is required
	if ( $query->get( 'bookable_listings_join' ) ) {
		$table = geodir_db_cpt_table( 'gd_place' );

		// Add INNER JOIN with gd_place_detail table
		if ( geodir_column_exist( $table, 'gdbooking' ) ) {
			$join .= ' INNER JOIN `' . $table . "` AS place_detail ON ({$wpdb->posts}.ID = place_detail.post_id AND place_detail.gdbooking = 1)";
		} else {
			$join .= ' INNER JOIN `' . $table . "` AS place_detail ON ({$wpdb->posts}.ID = place_detail.post_id AND place_detail.post_id = -1)";
		}
	}

	return $join;
}
add_filter( 'posts_join', 'geodir_booking_join_bookable_listings', 10, 2 );

/**
 * Retrieves bookable listings.
 *
 * This function retrieves bookable listings based on the arguments provided.
 * It returns a WP_Query object containing the bookable listings.
 *
 * @param array $args Array of arguments for querying bookable listings.
 * @return WP_Query The WP_Query object containing the bookable listings.
 */
function geodir_booking_get_bookable_listings_query( $args ) {
	$defaults = array(
		'post_type'              => 'gd_place',
		'post_status'            => 'any',
		'paged'                  => 1,
		'orderby'                => 'id',
		'order'                  => 'DESC',
		'bookable_listings_join' => true, // Indicates the need for a custom join.
	);

	$query = wp_parse_args( $args, $defaults );

	// Create a WP_Query object with the merged arguments.
	$query = new WP_Query( $query );

	return $query;
}

/**
 * Parses the given time string in 24-hour format and returns an array containing hours and minutes.
 * If the parsing fails, returns default values of "00" for both hours and minutes.
 *
 * @param string $time Time in 24-hour format (e.g., "14:30").
 * @return array Array containing the parsed hours and minutes.
 */
function geodir_booking_parse_and_calc_timestamp( $time ) {
	if ( is_array( $time ) ) {
		$hours   = isset( $time['hours'] ) ? (int) $time['hours'] : '00';
		$minutes = isset( $time['minutes'] ) ? (int) $time['minutes'] : '00';
	} else {
		// Regular expression pattern to match hours and minutes in the time string
		$pattern = '/^(?<hours>[01][0-9]|2[0-3]):(?<minutes>[0-5][0-9])/';

		// Parse the time string using regular expression
		$matched = preg_match( $pattern, $time, $components );
		$hours   = ( $matched ) ? (int) $components['hours'] : '00';
		$minutes = ( $matched ) ? (int) $components['minutes'] : '00';
	}

	// Create a new DateTime object with the parsed hours and minutes
	$date = new \DateTime();
	$date->setTime( (int) $hours, (int) $minutes );

	// Calculate the Unix timestamp for the next occurrence of the provided time
	$next_time = (int) $date->format( 'U' ) + 59; // Adding 59 seconds to ensure we reach the end of the minute

	// Get the current Unix timestamp
	$current_time = time();

	// If the next time is in the future, return it; otherwise, return the next occurrence in the next day
	if ( $next_time >= $current_time ) {
		return $next_time;
	} else {
		return $next_time + DAY_IN_SECONDS; // Add a day's worth of seconds to find the next occurrence
	}
}

/**
 * Render owner booking modals.
 *
 * @since 2.1.0
 */
function geodir_booking_owner_booking_render_modals( $user_id, $modals, $listings = array(), $listing_options = array() ) {
	$gd_booking_add_page = GeoDir_Booking_Add_Booking_Page::instance();

	if ( empty( $listings ) ) {
		$listings = geodir_booking_get_listing_ids_by_user_id( (int) $user_id );
	}

	if ( empty( $listing_options ) ) {
		$listing_options = array();

		foreach ( $listings as $listing_id ) {
			$listing_id = geodir_booking_post_id( $listing_id );
			$listing = geodir_get_post_info( $listing_id );

			if ( isset( $listing->gdbooking ) && $listing->gdbooking === '1' ) {
				$listing_options[ $listing->ID ] = $listing->post_title;
			}
		}
	}

	if ( empty( $listing_options ) ) {
		return;
	}

	$listing_options = array_replace( $gd_booking_add_page->empty_options, $listing_options );
	$adults_options   = $gd_booking_add_page->get_adults_options();
	$children_options = $gd_booking_add_page->get_children_options();
	$booking_statuses = geodir_get_booking_statuses();

	unset( $booking_statuses['draft'] );
	unset( $booking_statuses['rejected'] );

	$args = array(
		'user_id' => $user_id,
		'listings' => $listings,
		'listing_options' => $listing_options,
		'adults_options' => $adults_options,
		'children_options' => $children_options,
		'booking_statuses' => $booking_statuses
	);

	if ( empty( $modals ) || ( ! empty( $modals ) && in_array( 'new', $modals ) ) ) {
		do_action( 'geodir_booking_owner_new_booking_modal', $args );
	}

	if ( empty( $modals ) || ( ! empty( $modals ) && in_array( 'view', $modals ) ) ) {
		do_action( 'geodir_booking_owner_view_booking_modal', $args );
	}
}
add_action( 'geodir_booking_owner_booking_render_modals', 'geodir_booking_owner_booking_render_modals', 10, 4 );

/**
 * Get owner new booking modal content.
 *
 * @since 2.1.0
 */
function geodir_booking_render_owner_new_booking_modal( $args ) {
	geodir_get_template( 'owner-new-booking-modal.php', $args, 'geodir-booking', plugin_dir_path( GEODIR_BOOKING_FILE ) . 'templates' );
}
add_action( 'geodir_booking_owner_new_booking_modal', 'geodir_booking_render_owner_new_booking_modal', 10, 1 );

/**
 * Get owner view booking modal content.
 *
 * @since 2.1.0
 */
function geodir_booking_render_owner_view_booking_modal( $args ) {
	geodir_get_template( 'owner-view-booking-modal.php', $args, 'geodir-booking', plugin_dir_path( GEODIR_BOOKING_FILE ) . 'templates' );
}
add_action( 'geodir_booking_owner_view_booking_modal', 'geodir_booking_render_owner_view_booking_modal', 10, 1 );

/**
 * Render customer booking modals.
 *
 * @since 2.1.1
 */
function geodir_booking_customer_booking_render_modals( $args = array() ) {
	if ( empty( $args['modals'] ) || ( ! empty( $args['modals'] ) && in_array( 'view', $args['modals'] ) ) ) {
		do_action( 'geodir_booking_customer_view_booking_modal', $args );
	}
}
add_action( 'geodir_booking_customer_booking_render_modals', 'geodir_booking_customer_booking_render_modals', 10, 1 );

/**
 * Get customer view booking modal content.
 *
 * @since 2.1.1
 */
function geodir_booking_render_customer_view_booking_modal( $args ) {
	geodir_get_template( 'customer-view-booking-modal.php', $args, 'geodir-booking', plugin_dir_path( GEODIR_BOOKING_FILE ) . 'templates' );
}
add_action( 'geodir_booking_customer_view_booking_modal', 'geodir_booking_render_customer_view_booking_modal', 10, 1 );

function geodir_booking_night_min_price() {
	$has_free_booking = geodir_booking_get_option( 'has_free_booking' );
	$has_free_booking = $has_free_booking && $has_free_booking != 'no' ? true : false;

	$min_price = $has_free_booking ? 0 : 1;

	$min_price = (float) apply_filters( 'geodir_booking_night_min_price', $min_price );

	$min_price = $min_price > 0 ? wpinv_round_amount( $min_price ) : 0;

	return $min_price;
}

/**
 * Get original post ID for translated post.
 *
 * @since 2.1.9
 */
function geodir_booking_post_id( $post_id, $post_type = '' ) {
	global $gdml_booking_posts;

	$orig_post_id = $post_id;

	if ( ! empty( $post_id ) && geodir_booking_is_wpml() ) {
		if ( empty( $gdml_booking_posts ) ) {
			$gdml_booking_posts = array();
		}

		if ( ! empty( $gdml_booking_posts[ $post_id ] ) ) {
			return (int) $gdml_booking_posts[ $post_id ];
		}

		$_post_id = geodir_booking_wpml_post_id( $post_id, $post_type );

		if ( $_post_id ) {
			$gdml_booking_posts[ $orig_post_id ] = $_post_id;

			$post_id = $_post_id;
		}
	}

	return apply_filters( 'geodir_booking_post_id', $post_id, $orig_post_id );
}

function geodir_booking_is_wpml() {
	if ( defined( 'ICL_SITEPRESS_VERSION' ) && ! ICL_PLUGIN_INACTIVE && class_exists( 'SitePress', false ) && function_exists( 'icl_object_id' ) ) {
		return true;
	}

	return false;
}

function geodir_booking_post_ids( $post_ids ) {
	if ( is_scalar( $post_ids ) ) {
		return geodir_booking_post_id( $post_ids );
	}

	if ( ! empty( $post_ids ) ) {
		foreach ( $post_ids as $post_id ) {
		}
	}
	$orig_post_id = $post_id;

	if ( ! empty( $post_ids ) && geodir_booking_is_wpml() ) {
		if ( is_array( $post_ids ) ) {
			$_post_ids = array();

			foreach ( $post_ids as $post_id ) {
				$_post_ids[] = geodir_booking_post_id( $post_id );
			}

			$post_ids = array_unique( array_filter( $_post_ids ) );
		} else if ( is_scalar( $post_ids ) ) {
			$post_ids = geodir_booking_post_id( $post_ids );
		}
	}

	return $post_ids;
}

/**
 * Get WPML original post ID.
 *
 * @since 2.1.9
 */
function geodir_booking_wpml_post_id( $post_id, $post_type = '', $skip_empty = false, $all_statuses = true, $skip_cache = false ) {
	global $sitepress;

	if ( ! geodir_booking_is_wpml() ) {
		return $post_id;
	}

	if ( empty( $post_type ) ) {
		$post_type = get_post_type( $post_type );
	}

	$main_post_id = (int) $sitepress->get_original_element_id( $post_id, 'post_' . $post_type, $skip_empty, $all_statuses, $skip_cache );

	return $main_post_id;
}