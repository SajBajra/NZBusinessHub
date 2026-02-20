<?php

/**
 * Uninstall GeoDirectory Custom Google Maps.
 *
 * Uninstalling GeoDirectory Custom Google Maps deletes the plugin options.
 *
 * @since 2.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

/**
 * Delete Custom Google Maps option when plugin uninstall.
 *
 * @since 2.0.0
 */

$options = get_option( 'geodir_settings' );

if ( empty( $options ) ) {
    $options = array();
}

if ( isset( $options[ 'custom_google_maps' ] ) ) {
    unset( $options[ 'custom_google_maps' ] );
}

update_option( 'geodir_settings', $options );

// Delete custom maps home style.
delete_option('gd_custom_maps_home_style');

// Delete custom maps listing style.
delete_option('gd_custom_maps_listing_style');

// Delete custom maps details style.
delete_option('gd_custom_maps_details_style');

// Delete custom maps add listing style.
delete_option('gd_custom_maps_add_listing_style');

// Delete custom osm maps home style.
delete_option('gd_custom_osm_home_options');

// Delete custom osm maps listing style.
delete_option('gd_custom_osm_listing_options');

// Delete custom osm maps detail style.
delete_option('gd_custom_osm_detail_options');

// Delete custom osm maps add listing style.
delete_option('gd_custom_osm_add_listing_options');