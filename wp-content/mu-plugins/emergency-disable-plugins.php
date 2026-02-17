<?php
/**
 * Plugin Name: Emergency Disable Problem Plugins
 * Description: Temporarily disables specific plugins that are causing timeouts / critical errors.
 * Author: Site Admin
 * Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * List of plugin basenames to force-disable.
 *
 * These are based on paths seen in debug.log. You can remove entries from
 * this list one by one later to find which plugin is the culprit.
 */
function nz_emergency_disabled_plugins_list() {
	return array(
		'wp-defender/wp-defender.php',
		'forminator/forminator.php',
		'wp-smush-pro/wp-smush-pro.php',
		'userswp/userswp.php',
		'blockstrap-page-builder-blocks/blockstrap-page-builder-blocks.php',
		'ayecode-connect/ayecode-connect.php',
	);
}

/**
 * Filter the list of active plugins for single-site installations.
 */
function nz_emergency_filter_active_plugins( $active_plugins ) {
	if ( ! is_array( $active_plugins ) ) {
		return $active_plugins;
	}

	$blocked = nz_emergency_disabled_plugins_list();

	// Keep everything except the blocked ones.
	$active_plugins = array_diff( $active_plugins, $blocked );

	// Re-index to avoid gaps.
	return array_values( $active_plugins );
}
add_filter( 'option_active_plugins', 'nz_emergency_filter_active_plugins', 1 );

/**
 * Filter the list of network-activated plugins for multisite.
 */
function nz_emergency_filter_sitewide_plugins( $sitewide_plugins ) {
	if ( ! is_array( $sitewide_plugins ) ) {
		return $sitewide_plugins;
	}

	$blocked = nz_emergency_disabled_plugins_list();

	foreach ( $blocked as $plugin_basename ) {
		if ( isset( $sitewide_plugins[ $plugin_basename ] ) ) {
			unset( $sitewide_plugins[ $plugin_basename ] );
		}
	}

	return $sitewide_plugins;
}
add_filter( 'site_option_active_sitewide_plugins', 'nz_emergency_filter_sitewide_plugins', 1 );

