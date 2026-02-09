<?php
/**
 * Dynamic User Emails Admin Functions
 *
 * @author    AyeCode Ltd
 * @package   GeoDir_Dynamic_Emails
 * @version   2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function geodir_dynamic_emails_admin_params() {
	$params = array(
		'actions' => GeoDir_Dynamic_Emails_Email::email_actions( false )
	);

	return apply_filters( 'geodir_dynamic_emails_admin_params', $params );
}