<?php
/**
 * Franchise Manager Admin Functions.
 *
 * @since 2.0.0
 * @package Geodir_Franchise
 * @author AyeCode Ltd
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function geodir_franchise_admin_params() {
	$params = array(
    );

    return apply_filters( 'geodir_franchise_admin_params', $params );
}

/**
 * Add the plugin to uninstall settings.
 *
 * @since 2.0.0
 *
 * @return array $settings the settings array.
 * @return array The modified settings.
 */
function geodir_franchise_uninstall_settings( $settings ) {
    array_pop( $settings );

	$settings[] = array(
		'name'     => __( 'Franchise Manager', 'geodir-franchise' ),
		'desc'     => __( 'Check this box if you would like to completely remove all of its data when Franchise Manager is deleted.', 'geodir-franchise' ),
		'id'       => 'uninstall_geodir_franchise',
		'type'     => 'checkbox',
	);
	$settings[] = array( 
		'type' => 'sectionend',
		'id' => 'uninstall_options'
	);

    return $settings;
}

function geodir_franchise_cpt_db_columns( $columns, $cpt, $post_type ) {
	if ( GeoDir_Post_types::supports( $post_type, 'franchise' ) ) {
		$columns['franchise'] = "franchise TINYINT(1) UNSIGNED DEFAULT '0'";
		$columns['franchise_fields'] = "franchise_fields TEXT NULL";
		$columns['franchise_of'] = "franchise_of INT(11) UNSIGNED DEFAULT '0'";
	}

	return $columns;
}

function geodir_franchise_franchise_cpt_tabs_settings( $fields, $post_type ) {
	if ( GeoDir_Post_types::supports( $post_type, 'franchise' ) ) {
		$location_filter = GeoDir_Post_types::supports( $post_type, 'location' ) ? ' add_location_filter="0"' : '';

		$fields[] = array(
			'tab_type'   => 'shortcode',
			'tab_name'   => __( 'Franchises', 'geodir-franchise' ),
			'tab_icon'   => 'fas fa-sitemap',
			'tab_key'    => 'franchises',
			'tab_content'=> '[gd_listings post_type="' . $post_type . '" sort_by="latest" title_tag="h3" layout="list" post_limit="5" franchise_of="auto"' . $location_filter . ']'
		);
	}

	return $fields;
}

function geodir_franchise_posts_columns( $columns = array() ) {
	if ( ( $offset = array_search( 'gd_tags', array_keys( $columns ) ) ) !== false ) {
		$offset = $offset + 1;
	} else {
		$offset = 5;
	}

	$new_columns = array(
		'franchise' => '<span class="dashicons dashicons-networking" title="' . esc_attr( __( 'Franchise Type', 'geodir-franchise' ) ) . '"> </span>',
		'franchise_of' => __( 'Franchise Of', 'geodir-franchise' )
	);

	$columns = array_merge( array_slice( $columns, 0, $offset ), $new_columns, array_slice( $columns, $offset ) );

	return $columns;
}

function geodir_franchise_posts_sortable_columns( $columns = array() ) {
	$columns[] = 'franchise';
	$columns[] = 'franchise_of';
	return $columns;
}

function geodir_franchise_posts_custom_column( $column, $post_id ) {
	if ( $column == 'franchise' ) {
		if ( geodir_franchise_is_main( (int) $post_id ) ) {
			$class = 'gdfr-franchise-m';
			$title = __( 'Main Listing', 'geodir-franchise' );
			$color = 'orange';
		} else if ( geodir_franchise_is_franchise( (int) $post_id ) ) {
			$class = 'gdfr-franchise-s';
			$title = geodir_franchise_label( 'singular_name', get_post_type( (int) $post_id ) );
			$color = '#00a0d2';
		} else {
			$class = 'gdfr-franchise-n';
			$title = '';
			$color = '#ccc';
		}
		echo '<span class="' . $class . ' dashicons dashicons-networking" title="' . esc_attr( $title ) . '" style="color:' . $color . '"> </span>';
	} else if ( $column == 'franchise_of' ) {
		$value = '<span aria-hidden="true">&mdash;</span>';
		if ( $main_post_id = geodir_franchise_main_post_id( (int) $post_id ) ) {
			if ( $main_post_id != $post_id ) {
				$value = '<a target="_blank" href="' . esc_url( get_permalink( $main_post_id ) ) . '" title="' . esc_attr__( 'View', 'geodir-franchise' ) . '">' . get_the_title( $main_post_id ) . '</a>';
				$value .= '<br><small>' . __( 'ID:', 'geodir-franchise' ) . ' <a href="' . esc_url( get_edit_post_link( $main_post_id ) ) . '" title="' . esc_attr__( 'Edit', 'geodir-franchise' ) . '">' . $main_post_id . '</a></small>';
			}
		}
		
		echo $value;
	}
}

function geodir_franchise_admin_add_franchise_url( $post_id ) {
	$url = admin_url( 'post-new.php?post_type=' . get_post_type( $post_id ) );

	if ( $main_post_id = geodir_franchise_main_post_id( $post_id ) ) {
		$url = geodir_getlink( $url, array( 'task' => 'add_franchise', 'franchise_of' => $main_post_id ), false );
	}

	return apply_filters( 'geodir_franchise_admin_add_franchise_url', $url, $post_id, $main_post_id );
}

/**
 * Add the link in post row actions array to display Add Franchise link in back-end listing page.
 *
 * @package GeoDirectory_Franchise_Manager
 * @since 1.0.0
 *
 * @param array $actions An array of row action links.
 * @param WP_Post $post The post object.
 * @return array An array of row action links.
 */
function geodir_franchise_post_row_actions( $actions, $post ) {
	if ( $post->post_status == 'publish' && geodir_franchise_is_main( (int) $post->ID ) && geodir_franchise_can_add_franchise( (int) $post->ID ) ) {
		$actions['add_franchise'] = '<a href="' . esc_url( geodir_franchise_admin_add_franchise_url( $post->ID ) ) . '">' . geodir_franchise_label( 'add_new_item', $post->post_type ) . '</a>';
	}
	return $actions;
}

/**
 * Add the franchise option names that requires to add for translation.
 *
 * @package GeoDirectory_Franchise_Manager
 * @since 1.0.0
 *
 * @param  array $gd_options Array of option names.
 * @return array Array of option names.
 */
function geodir_franchise_settings_to_translation( $gd_options = array() ) {
	$new_options = array(
		'email_user_franchise_approved_subject',
		'email_user_franchise_approved_body'
	);

	$gd_options = array_merge( $gd_options, $new_options );

	return $gd_options;
}