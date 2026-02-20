<?php
/**
 * Returns the default subject for owner booking confirmation emails.
 */

defined( 'ABSPATH' ) || exit;

return apply_filters( 'geodir_booking_default_owner_booking_confirmation_subject', __( 'Booking Confirmed', 'geodir-booking' ) );
