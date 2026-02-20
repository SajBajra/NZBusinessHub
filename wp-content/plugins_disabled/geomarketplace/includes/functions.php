<?php
/**
 * GeoDirectory Marketplace functions
 *
 * @package GeoDir_Marketplace
 * @author AyeCode Ltd
 * @since 2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register plugin widgets.
 *
 * @since 2.0
 *
 * @param array $widgets The list of available widgets.
 * @return array Available GD widgets.
 */
function geodir_marketplace_register_widgets( $widgets ) {
	if ( get_option( 'geodirectory_version' )) {
		$widgets[] = 'GeoDir_Marketplace_Widget_Shop';
	}

	return $widgets;
}

/**
 * Get linked post types.
 *
 * @since 2.2
 *
 * @return array The post types.
 */
function geodir_marketplace_post_types() {
	$post_types = geodir_get_option( 'mp_post_type' );
	$post_types = geodir_marketplace_parse_post_types( $post_types );

	return apply_filters( 'geodir_marketplace_get_post_types', $post_types );
}

/**
 * Parse & validate linked post types.
 *
 * @since 2.2
 *
 * @param array $post_types The post types value.
 * @return array Parse post types.
 */
function geodir_marketplace_parse_post_types( $_post_types ) {
	if ( empty( $_post_types ) ) {
		$_post_types = array();
	}

	if ( is_scalar( $_post_types ) ) {
		$_post_types = explode( ",", $_post_types );
		$_post_types = array_filter( array_map( 'trim', $_post_types ) );
	}

	$post_types = array();

	foreach ( $_post_types as $post_type ) {
		if ( geodir_is_gd_post_type( $post_type ) ) {
			$post_types[] = $post_type;
		}
	}

	return $post_types;
}

/**
 * Check valid linked post type.
 *
 * @since 2.2
 *
 * @param string $post_type The post type.
 * @return book True if valid or false.
 */
function geodir_marketplace_valid_post_type( $post_type ) {
	$is_linked = $post_type && in_array( $post_type, geodir_marketplace_post_types() );

	return apply_filters( 'geodir_marketplace_valid_post_type', $is_linked, $post_type );
}