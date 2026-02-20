<?php
/**
 * Returns the default subject for user booking confirmed emails.
 */

defined( 'ABSPATH' ) || exit;

return apply_filters( 'geodir_booking_default_user_booking_confirmed_subject', __( 'Your booking has been confirmed.', 'geodir-booking' ) );
