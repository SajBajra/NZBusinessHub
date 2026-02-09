<?php
/**
 * Returns the default content for user booking refunded emails.
 */

defined( 'ABSPATH' ) || exit;

/* translators: %s: Customer name. */
$message = sprintf( __( 'Hi %s,', 'geodir-booking' ), '[#customer_name#]' ) . "\r\n\r\n";

$message .= __( 'Your booking has been refunded.', 'geodir-booking' ) . "\r\n\r\n";

$message .= implode( "\r\n", geodir_booking_listing_customer_email_details( 'refunded' ) );

return apply_filters( 'geodir_booking_default_user_booking_refunded_body', $message );
