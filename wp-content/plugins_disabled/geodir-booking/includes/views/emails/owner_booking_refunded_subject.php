<?php
/**
 * Returns the default subject for owner booking refunded emails.
 */

defined( 'ABSPATH' ) || exit;

return apply_filters( 'geodir_booking_default_owner_booking_refunded_subject', __( 'Booking Refunded', 'geodir-booking' ) );
