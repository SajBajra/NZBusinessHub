<?php
/**
 * Returns the default subject for user booking pending emails.
 */

defined( 'ABSPATH' ) || exit;

return apply_filters( 'geodir_booking_default_user_booking_pending_subject', __( 'Your booking is pending confirmation', 'geodir-booking' ) );
