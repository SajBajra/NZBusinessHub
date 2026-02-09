<?php
/**
 * Returns the default subject for user booking refunded emails.
 */

defined( 'ABSPATH' ) || exit;

return apply_filters( 'geodir_booking_default_user_booking_refunded_subject', __( 'Your booking has been refunded.', 'geodir-booking' ) );
