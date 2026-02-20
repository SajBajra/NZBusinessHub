<?php
/**
 * GeoDirectory List Manager Uninstall
 *
 * @package GeoDir_List_Manager
 * @author AyeCode Ltd
 * @since 2.0
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

$geodir_settings = get_option( 'geodir_settings' );

if ( ( ! empty( $geodir_settings ) && ( ! empty( $geodir_settings['admin_uninstall'] ) || ! empty( $geodir_settings['uninstall_geodir_lists'] ) ) ) || ( defined( 'GEODIR_UNINSTALL_GEODIR_LISTS' ) && true === GEODIR_UNINSTALL_GEODIR_LISTS ) ) {
	if ( ! empty( $geodir_settings ) ) {
		$save_settings = $geodir_settings;

		$remove_options = array(
			'list_page_single',
			'list_post_type',
			'uninstall_geodir_lists'
		);

		foreach ( $remove_options as $option ) {
			if ( isset( $save_settings[ $option ] ) ) {
				unset( $save_settings[ $option ] );
			}
		}

		// Update options.
		update_option( 'geodir_settings', $save_settings );
	}

	if ( ! empty( $geodir_settings['geodir_add_list_page'] ) ) {
		wp_delete_post( (int) $geodir_settings['geodir_add_list_page'], true );
	}

	if ( ! empty( $geodir_settings['list_page_single'] ) ) {
		wp_delete_post( (int) $geodir_settings['list_page_single'], true );
	}

	if ( ! empty( $geodir_settings['list_post_type']['page_details'] ) && $geodir_settings['list_post_type']['page_details'] != $geodir_settings['list_page_single'] ) {
		wp_delete_post( (int) $geodir_settings['list_post_type']['page_details'], true );
	}

	if ( ! empty( $geodir_settings['list_post_type']['page_archive_item'] ) && $geodir_settings['list_post_type']['page_archive_item'] != $geodir_settings['list_page_archive_item'] ) {
		wp_delete_post( (int) $geodir_settings['list_post_type']['page_archive_item'], true );
	}

	// Delete posts.
	$wpdb->query( "DELETE FROM `{$wpdb->posts}` WHERE post_type LIKE gd_list'" );

	// Delete orphan post meta.
	$wpdb->query( "DELETE meta FROM `{$wpdb->postmeta}` meta LEFT JOIN {$wpdb->posts} posts ON posts.ID = meta.post_id WHERE posts.ID IS NULL" );

	// Delete core options.
	delete_option( 'geodir_lists_version' );

	// Clear cache.
	wp_cache_flush();
}

