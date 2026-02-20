<?php
/**
 * Returns the default content for user booking pending emails.
 */

defined( 'ABSPATH' ) || exit;

/* translators: %s: Customer name. */
$message = sprintf( __( 'Hi %s,', 'geodir-booking' ), '[#customer_name#]' ) . "\r\n\r\n";

$message .= __( "Your booking is pending confirmation. We'll let you know once it has been confirmed.", 'geodir-booking' ) . "\r\n\r\n";

$message .= implode( "\r\n", geodir_booking_listing_customer_email_details() );

return apply_filters( 'geodir_booking_default_user_booking_pending_body', $message );
