<?php
/**
 * Franchise Manager Deprecated Functions.
 *
 * Functions that no longer in use after v2.0.0.
 *
 * @since 2.0.0
 * @package Geodir_Franchise
 * @author AyeCode Ltd
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * @since 2.0.0
 *
 * @deprecated
 */
function geodir_franchise_check( $post_id ) {
    _deprecated_function( __FUNCTION__, '2.0.0.0', 'geodir_franchise_is_main()' );
	return geodir_franchise_is_main( $post_id );
}

/**
 * @since 2.0.0
 *
 * @deprecated
 */
function geodir_franchise_enabled( $post_type = '', $taxonomy = false ) {
    if ( $taxonomy ) {
		_deprecated_function( __FUNCTION__, '2.0.0.0', 'GeoDir_Taxonomies::supports()' );
		return GeoDir_Taxonomies::supports( $post_type, 'franchise' );
	} else {
		_deprecated_function( __FUNCTION__, '2.0.0.0', 'GeoDir_Post_types::supports()' );
		return GeoDir_Post_types::supports( $post_type, 'franchise' );
	}
}

/**
 * @since 2.0.0
 *
 * @deprecated
 */
function geodir_franchise_main_franchise_id( $post_id ) {
    _deprecated_function( __FUNCTION__, '2.0.0.0', 'geodir_franchise_main_post_id()' );
	return geodir_franchise_main_post_id( $post_id );
}
