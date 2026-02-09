<?php
/**
 * Uninstall GeoDirectory BuddyPress Integration
 *
 * Uninstalling GeoDirectory BuddyPress Integration deletes the plugin options.
 *
 * @package GeoDirectory_BuddyPress
 */

// Exit if accessed directly.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

$geodir_settings = get_option( 'geodir_settings' );

if ( ( ! empty( $geodir_settings ) && ( ! empty( $geodir_settings['admin_uninstall'] ) || ! empty( $geodir_settings['uninstall_geodir_buddypress_manager'] ) ) ) ) {
    //Remove settings
    $options = array(
        'geodir_buddypress_version',
        'geodir_buddypress_version_upgraded_from',
        'geodir_buddypress_link_listing',
        'geodir_buddypress_link_favorite',
        'geodir_buddypress_link_author',
        'geodir_buddypress_show_feature_image',
        'geodir_buddypress_tab_listing',
        'geodir_buddypress_tab_review',
        'geodir_buddypress_listings_count',
        'geodir_buddypress_activity_listing',
        'geodir_buddypress_activity_review',
        'uninstall_geodir_buddypress_manager',
    );

    $geodir_options = geodir_get_settings();

    foreach ($options as $option){
        unset($geodir_options[$option]);
    }

    update_option( 'geodir_settings', $geodir_options);
}