<?php
/**
 * New Post Email Plain Text
 *
 * This template can be overridden by copying it to yourtheme/geodirectory/emails/plain/email-dynamic-new_post.php.
 *
 * HOWEVER, on occasion GeoDirectory will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see       https://docs.wpgeodirectory.com/article/346-customizing-templates/
 * @package   GeoDir_Dynamic_Emails
 * @version   2.0.0
 */

defined( 'ABSPATH' ) || exit;

do_action( 'geodir_email_header', $email_heading, $email_name, $email_vars, $plain_text, $sent_to_admin );

if ( ! empty( $message_body ) ) {
	echo wp_strip_all_tags( $message_body );
}

do_action( 'geodir_email_footer', $email_name, $email_vars, $plain_text, $sent_to_admin );