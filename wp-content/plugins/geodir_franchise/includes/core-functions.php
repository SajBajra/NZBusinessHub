<?php
/**
 * Franchise Manager Core Functions.
 *
 * @since 2.0.0
 * @package Geodir_Franchise
 * @author AyeCode Ltd
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function geodir_franchise_add_franchise_url( $post_id ) {
	$url = geodir_add_listing_page_url( get_post_type( $post_id ) );

	if ( $main_post_id = geodir_franchise_main_post_id( $post_id ) ) {
		$url = geodir_add_listing_page_url( get_post_type( $main_post_id ) );
		$url = geodir_getlink( $url, array( 'task' => 'add_franchise', 'franchise_of' => $main_post_id ), false );
	}

	return apply_filters( 'geodir_franchise_add_franchise_url', $url, $post_id, $main_post_id );
}

function geodir_franchise_main_post_id( $post_id ) {
	return GeoDir_Franchise_Post::get_main_post_id( $post_id );
}

function geodir_franchise_is_main( $post_id ) {
	return GeoDir_Franchise_Post::is_main( $post_id );
}

function geodir_franchise_is_franchise( $post_id ) {
	return GeoDir_Franchise_Post::is_franchise( $post_id );
}

function geodir_franchise_get_locked_fields( $post_type = 'gd_place', $package_id = '', $default = 'all', $output = 'names' ) {

	return GeoDir_Franchise_Fields::get_locked_fields( $post_type, $package_id, $default, $output );
}

function geodir_franchise_post_locked_fields( $post_id, $context = 'db' ) {

	return GeoDir_Franchise_Post::get_locked_fields( $post_id, $context );
}

function geodir_franchise_post_franchises( $post_id, $args = array() ) {
	return GeoDir_Franchise_Post::get_post_franchises( $post_id, $args );
}

function geodir_franchise_can_add_franchise( $post_id ) {
	return GeoDir_Franchise_Post::can_add_franchise( $post_id );
}