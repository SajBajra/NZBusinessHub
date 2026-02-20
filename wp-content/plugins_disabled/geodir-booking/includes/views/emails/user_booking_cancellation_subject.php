<?php
/**
 * Returns the default subject for user booking cancellation emails.
 */

defined( 'ABSPATH' ) || exit;

return apply_filters( 'geodir_booking_default_user_booking_cancellation_subject', __( 'Your booking has been canceled.', 'geodir-booking' ) );
