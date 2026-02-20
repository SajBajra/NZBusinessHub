<?php
/**
 * GeoDirectory Dynamic User Emails Upgrade
 *
 * @author    AyeCode Ltd
 * @package   GeoDir_Dynamic_Emails
 * @version   2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( get_option( 'geodir_dynamic_emails_db_version' ) != GEODIR_DYNAMIC_EMAILS_VERSION ) {
	add_action( 'plugins_loaded', 'geodir_dynamic_emails_upgrade_all', 10 );
}

/**
 * Upgrade for all versions.
 *
 * @since 2.0.0
 */
function geodir_dynamic_emails_upgrade_all() {
	// Upgrade stuff.
}
