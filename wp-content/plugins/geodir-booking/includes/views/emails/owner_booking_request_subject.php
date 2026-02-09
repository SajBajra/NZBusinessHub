<?php
/**
 * Returns the default subject for owner booking request emails.
 */

defined( 'ABSPATH' ) || exit;

return apply_filters( 'geodir_booking_default_owner_booking_request_subject', __( 'New Booking Request', 'geodir-booking' ) );
