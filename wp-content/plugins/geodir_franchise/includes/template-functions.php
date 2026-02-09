<?php
/**
 * Franchise Manager Template Functions.
 *
 * @since 2.0.0
 * @package Geodir_Franchise
 * @author AyeCode Ltd
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Register Widgets.
 *
 * @since 2.0.0
 */
function geodir_franchise_register_widgets() {
	if ( get_option( 'geodir_franchise_version' ) ) {
	}
}

/**
 *
 * @since 2.0.0
 */
function geodir_franchise_params() {
	$params = array(
	);

    return apply_filters( 'geodir_franchise_params', $params );
}

function geodir_franchise_detail_author_actions() {
	global $gd_post;

	if ( ! empty( $gd_post->ID ) && is_user_logged_in() ) {
		echo geodir_franchise_add_franchise_link( $gd_post->ID );
	}
}

function geodir_franchise_add_franchise_link( $post_id ) {
	if ( empty( $post_id ) ) {
		return NULL;
	}

	$post_type = get_post_type( $post_id );

	if ( ! geodir_is_gd_post_type( $post_type ) || ! GeoDir_Post_types::supports( $post_type, 'franchise' ) ) {
		return NULL;
	}

	if ( ! geodir_franchise_can_add_franchise( $post_id ) ) {
		return NULL;
	}

	$url = geodir_franchise_add_franchise_url( $post_id );

	return apply_filters( 'geodir_franchise_add_franchise_link', $url, $post_id );
}

/**
 * Get the franchise labels for the post type.
 *
 * @since 2.1.1.0
 *
 * @param string $post_type Post type.
 * @return array Franchise labels for the requested post type.
 */
function geodir_franchise_labels( $post_type = '' ) {
	$defaults = array(
		'name'                     => __( 'Franchises', 'geodir-franchise' ),
		'singular_name'            => __( 'Franchise', 'geodir-franchise' ),
		'add_new'                  => _x( 'Add New', 'franchise', 'geodir-franchise' ),
		'add_new_item'             => __( 'Add New Franchise', 'geodir-franchise' ),
		'edit_item'                => __( 'Edit Franchise', 'geodir-franchise' ),
		'new_item'                 => __( 'New Franchise', 'geodir-franchise' ),
		'view_item'                => __( 'View Franchise', 'geodir-franchise' ),
		'view_items'               => __( 'View Franchises', 'geodir-franchise' ),
		'view_all_items'           => __( 'View All Franchises', 'geodir-franchise' ),
		'search_items'             => __( 'Search Franchises', 'geodir-franchise' ),
		'not_found'                => __( 'No franchises found.', 'geodir-franchise' ),
		'all_items'                => __( 'All Franchises', 'geodir-franchise' ),
		'items_list'               => __( 'Franchises list', 'geodir-franchise' ),
		'item_published'           => __( 'Franchise published.', 'geodir-franchise' ),
		'item_updated'             => __( 'Franchise updated.', 'geodir-franchise' ),
		'item_package_name'        => __( 'Franchise: %s', 'geodir-franchise' ),
		'item_product_name'        => __( '%s Franchise', 'geodir-franchise' ),
		'item_invoice_title'       => __( 'Franchise: %s', 'geodir-franchise' ),
		'items_of'                 => __( 'Franchises Of', 'geodir-franchise' ),
	);

	/**
	 * Filter the franchise labels for the post type.
	 *
	 * @since 2.1.1.0
	 *
	 * @param array $labels Franchise labels. Default empty.
	 * @param array $defaults Default franchise labels.
	 */
	$labels = apply_filters( "geodir_franchise_labels_{$post_type}", array(), $defaults );

	if ( ! empty( $labels ) && is_array( $labels ) ) {
		// Ensure that the filtered labels contain all required default values.
		$labels = array_merge( (array) $defaults, (array) $labels );
	} else {
		$labels = $defaults;
	}

	return $labels;
}

/**
 * Get the franchise label for the post type.
 *
 * @since 2.1.1.0
 *
 * @param string $context Label type.
 * @param string $post_type Post type. Default empty.
 * @return array Franchise label for the requested post type.
 */
function geodir_franchise_label( $context, $post_type = '' ) {
	$labels = geodir_franchise_labels( $post_type );

	if ( ! empty( $context ) && ! empty( $labels ) && ! empty( $labels[ $context ] ) ) {
		$label = $labels[ $context ];
	} else {
		$label = __( 'Franchise', 'geodir-franchise' );
	}

	/**
	 * Filter the franchise labels for the post type.
	 *
	 * @since 2.1.1.0
	 *
	 * @param array $label Franchise label.
	 * @param string $context Label type.
	 * @param string $post_type Post type.
	 */
	return apply_filters( "geodir_franchise_label_{$post_type}", $label, $context );
}