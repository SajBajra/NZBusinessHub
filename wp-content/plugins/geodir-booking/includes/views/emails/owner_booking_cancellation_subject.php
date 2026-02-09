<?php
/**
 * Returns the default subject for owner booking cancelletion emails.
 */

defined( 'ABSPATH' ) || exit;

return apply_filters( 'geodir_booking_default_owner_booking_cancelletion_subject', __( 'Booking Canceled', 'geodir-booking' ) );
