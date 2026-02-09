<?php
/**
 * Save Search Notifications Admin Functions
 *
 * @author    AyeCode Ltd
 * @package   GeoDir_Save_Search
 * @version   1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Adds options for translations that requires translation.
 *
 * @since 1.0
 *
 * @param array $options GD settings option names.
 * @return array Modified option names.
 */
function geodir_save_search_options_for_translation( $options = array() ) {
	$plugin_options = array(
		'email_user_save_search_subject',
		'email_user_save_search_body',
		'email_user_save_search_edit_subject',
		'email_user_save_search_edit_body'
	);

	$options = array_merge( $options, $plugin_options );

	return $options;
}

/**
 * Add the plugin uninstall data settings.
 *
 * @since 1.0
 *
 * @return array $settings the settings array.
 * @return array The modified settings.
 */
function geodir_save_search_uninstall_settings( $settings ) {
	array_pop( $settings );

	$settings[] = array(
		'name' => __( 'Save Search Notifications', 'geodir-save-search' ),
		'desc' => __( 'Tick to completely remove all of its data when Save Search Notifications is deleted.', 'geodir-save-search' ),
		'id' => 'uninstall_geodir_save_search',
		'type' => 'checkbox',
	);

	$settings[] = array( 
		'type' => 'sectionend',
		'id' => 'uninstall_options'
	);

	return $settings;
}

function geodir_save_search_dashboard_stats( $stats ) {
	$stats['saved-search'] = array(
		'icon' => 'fas fa-bell',
		'label' => __( 'Saved Search', 'geodir-save-search' ),
		'total' => (int) GeoDir_Save_Search_Query::count_subscribers(),
		'url' => '',
		'items' => array(
			array(
				'icon' => 'fas fa-floppy-disk',
				'label' => __( 'Saved Search', 'geodir-save-search' ),
				'total' => (int) GeoDir_Save_Search_Query::count_subscribers(),
				'url' => ''
			),
			array(
				'icon' => 'fas fa-user',
				'label' => __( 'Unique Users', 'geodir-save-search' ),
				'total' => (int) GeoDir_Save_Search_Query::count_users(),
				'url' => ''
			),
			array(
				'icon' => 'fas fa-envelope-circle-check',
				'label' => __( 'Emails Sent', 'geodir-save-search' ),
				'total' => (int) GeoDir_Save_Search_Query::count_emails( 'sent' ),
				'url' => ''
			),
			array(
				'icon' => 'fas fa-envelope',
				'label' => __( 'Emails Pending', 'geodir-save-search' ),
				'total' => (int) GeoDir_Save_Search_Query::count_emails( 'pending' ),
				'url' => ''
			)
		)
	);

	return $stats;
}