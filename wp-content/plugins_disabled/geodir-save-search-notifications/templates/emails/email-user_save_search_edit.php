<?php
/**
 * Save Search Email HTML For Edited Post
 *
 * This template can be overridden by copying it to yourtheme/geodirectory/emails/email-user_save_search_edit.php.
 *
 * HOWEVER, on occasion GeoDirectory will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see       https://wpgeodirectory.com/documentation/article/how-tos/customizing-templates/
 * @package   GeoDir_Save_Search
 * @version   1.0
 */

defined( 'ABSPATH' ) || exit;

do_action( 'geodir_email_header', $email_heading, $email_name, $email_vars, $plain_text, $sent_to_admin );

if ( ! empty( $message_body ) ) {
	echo wpautop( wptexturize( $message_body ) );
}

do_action( 'geodir_email_footer', $email_name, $email_vars, $plain_text, $sent_to_admin );