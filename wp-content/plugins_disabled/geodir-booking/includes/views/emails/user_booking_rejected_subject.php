<?php
/**
 * Returns the default subject for user booking rejected emails.
 */

defined( 'ABSPATH' ) || exit;

return apply_filters( 'geodir_booking_default_user_booking_rejected_subject', __( 'Your booking has been rejected.', 'geodir-booking' ) );
