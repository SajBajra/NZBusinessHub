<?php
/**
 * Plugin Name: NZBH GeoDirectory Contact Fields
 * Description: Ensures gd_place has phone/email/website custom fields so imported values display.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Add missing GeoDirectory custom fields for gd_place.
 *
 * We only add if the field isn't already registered in GEODIR_CUSTOM_FIELDS_TABLE.
 */
function nzbh_geodir_ensure_contact_fields() {
	if ( ! function_exists( 'geodir_custom_field_save' ) || ! function_exists( 'geodir_get_field_infoby' ) ) {
		return;
	}

	$post_type = 'gd_place';

	$ensure = array(
		'phone' => array(
			'field_type'     => 'phone',
			'data_type'      => 'TEXT',
			'admin_title'    => 'Phone',
			'frontend_title' => 'Phone',
			'frontend_desc'  => '',
			'field_icon'     => 'fas fa-phone',
			'show_in'        => '[detail]',
			'is_required'    => 0,
			'is_active'      => 1,
			'for_admin_use'  => 0,
		),
		'email' => array(
			'field_type'     => 'email',
			'data_type'      => 'TEXT',
			'admin_title'    => 'Email',
			'frontend_title' => 'Email',
			'frontend_desc'  => '',
			'field_icon'     => 'far fa-envelope',
			'show_in'        => '[detail]',
			'is_required'    => 0,
			'is_active'      => 1,
			'for_admin_use'  => 0,
		),
		'website' => array(
			'field_type'     => 'url',
			'data_type'      => 'TEXT',
			'admin_title'    => 'Website',
			'frontend_title' => 'Website',
			'frontend_desc'  => '',
			'field_icon'     => 'fas fa-external-link-alt',
			'show_in'        => '[detail]',
			'is_required'    => 0,
			'is_active'      => 1,
			'for_admin_use'  => 0,
		),
	);

	foreach ( $ensure as $key => $cfg ) {
		$existing = geodir_get_field_infoby( 'htmlvar_name', $key, $post_type );
		if ( ! empty( $existing ) ) {
			continue;
		}

		$field = array_merge(
			array(
				'post_type'          => $post_type,
				'htmlvar_name'       => $key,
				'clabels'            => $cfg['frontend_title'],
				'default_value'      => '',
				'placeholder_value'  => '',
				'sort_order'         => 0,
				'is_default'         => 0,
				'packages'           => '',
				'cat_sort'           => 0,
				'cat_filter'         => 0,
				'validation_pattern' => '',
				'validation_msg'     => '',
				'extra'              => array(),
			),
			$cfg
		);

		geodir_custom_field_save( $field );
	}
}

add_action( 'init', 'nzbh_geodir_ensure_contact_fields', 20 );

