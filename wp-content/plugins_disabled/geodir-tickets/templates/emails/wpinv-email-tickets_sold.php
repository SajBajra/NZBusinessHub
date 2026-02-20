<?php
/**
 * Template that generates the tickets sold.
 *
 * This template can be overridden by copying it to yourtheme/invoicing/email/wpinv-email-tickets_sold.php.
 *
 * @version 1.0.19
 */

defined( 'ABSPATH' ) || exit;

// Print the email header.
do_action( 'wpinv_email_header', $email_heading, $invoice, $email_type, $sent_to_admin );

// Generate the custom message body.
echo $message_body;

// Print the email footer.
do_action( 'wpinv_email_footer', $invoice, $email_type, $sent_to_admin );
