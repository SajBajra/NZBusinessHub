<?php

/**
 * This template renders an HTML email.
 *
 * You can overide this template by copying it to your-theme-folder/geodir-booking/plain_text-email.php
 *
 * @since 1.0.0
 */

// Exit if accessed directly.
defined('ABSPATH') || exit;

do_action( 'geodir_email_header', $email_heading, $email_name, $email_vars, true, false );

if ( ! empty( $message_body ) ) {
    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    echo wp_strip_all_tags( $message_body );
}

do_action( 'geodir_email_footer', $email_name, $email_vars, true, false );
