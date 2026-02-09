<?php
/**
 * Returns the default content for owner booking confirmation emails.
 */

defined( 'ABSPATH' ) || exit;

/* translators: %s: Listing owner. */
$message = sprintf( __( 'Hi %s,', 'geodir-booking' ), '[#post_author_name#]' ) . "\r\n\r\n";

$message .= __( 'The following booking has been confirmed.', 'geodir-booking' ) . "\r\n\r\n";

$message .= implode( "\r\n", geodir_booking_listing_owner_email_details( 'confirmed' ) );

return apply_filters( 'geodir_booking_default_owner_booking_confirmation_body', $message );
