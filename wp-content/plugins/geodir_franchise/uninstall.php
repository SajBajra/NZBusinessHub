<?php
/**
 * GeoDirectory Franchise Manager Uninstall
 *
 * Uninstalling Franchise Manager deletes its data.
 *
 * @author      AyeCode Ltd
 * @package     Geodir_Franchise
 * @version     2.0.0.0
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb, $plugin_prefix;

$geodir_settings = get_option( 'geodir_settings' );


if ( ( ! empty( $geodir_settings ) && ( ! empty( $geodir_settings['admin_uninstall'] ) || ! empty( $geodir_settings['uninstall_geodir_franchise'] ) ) ) || ( defined( 'GEODIR_UNINSTALL_FRANCHISE' ) && true === GEODIR_UNINSTALL_FRANCHISE ) ) {
	if ( empty( $plugin_prefix ) ) {
		$plugin_prefix = $wpdb->prefix . 'geodir_';
	}

	$custom_fields_table = defined( 'GEODIR_CUSTOM_FIELDS_TABLE' ) ? GEODIR_CUSTOM_FIELDS_TABLE : $plugin_prefix . 'custom_fields';
	$tabs_layout_table = defined( 'GEODIR_TABS_LAYOUT_TABLE' ) ? GEODIR_TABS_LAYOUT_TABLE : $plugin_prefix . 'tabs_layout';
	$package_meta_table = defined( 'GEODIR_PRICING_PACKAGE_META_TABLE' ) ? GEODIR_PRICING_PACKAGE_META_TABLE : $plugin_prefix . 'pricemeta';

	if ( ! empty( $geodir_settings ) ) {
		$save_settings = $geodir_settings;

		$remove_options = array(
			'uninstall_geodir_franchise',
			'franchise_show_main',
			'franchise_show_viewing',
			'franchise_map_show_main',
			'franchise_map_show_viewing',
			'email_bcc_user_franchise_approved',
			'email_user_franchise_approved',
			'email_user_franchise_approved_subject',
			'email_user_franchise_approved_body'
		);

		$post_types = ! empty( $geodir_settings['post_types'] ) ? $geodir_settings['post_types'] : array();

		foreach ( $post_types as $post_type => $data ) {
			$detail_table = $plugin_prefix . $post_type . '_detail';

			// Delete columns
			if ( $wpdb->get_var( "SHOW TABLES LIKE '{$detail_table}'" ) ) {
				$results = $wpdb->get_results("DESC {$detail_table}");

				$columns = array();
				foreach ( $results as $key => $row ) {
					$columns[] = $row->Field;
				}

				$delete_columns = array( 'franchise', 'franchise_fields', 'franchise_of' );

				foreach ( $delete_columns as $delete_column ) {
					if ( in_array( $delete_column, $columns ) ) {
						$wpdb->query( "ALTER TABLE {$detail_table} DROP {$delete_column}" ); // Delete column
					}
				}
			}

			// Delete CPT setting
			if ( isset( $data['supports_franchise'] ) ) {
				unset( $save_settings['post_types'][ $post_type ]['supports_franchise'] );
			}
		}

		foreach ( $remove_options as $option ) {
			if ( isset( $save_settings[ $option ] ) ) {
				unset( $save_settings[ $option ] );
			}
		}

		// Update options.
		update_option( 'geodir_settings', $save_settings );
	}

	// Delete custom fields
	if ( $wpdb->get_var( "SHOW TABLES LIKE '{$custom_fields_table}'" ) ) {
		$wpdb->query( "DELETE FROM {$custom_fields_table} WHERE htmlvar_name IN('franchise', 'franchise_fields', 'franchise_of')" );
	}

	// Delete tabs
	if ( $wpdb->get_var( "SHOW TABLES LIKE '{$tabs_layout_table}'" ) ) {
		$wpdb->query( "DELETE FROM {$tabs_layout_table} WHERE tab_key = 'franchises' AND tab_content LIKE '%franchise_of=%'" );
	}

	// Delete package meta
	if ( $wpdb->get_var( "SHOW TABLES LIKE '{$package_meta_table}'" ) ) {
		$wpdb->query( "DELETE FROM {$package_meta_table} WHERE meta_key IN('enable_franchise', 'franchise_cost', 'franchise_limit')" );
	}

	// Delete core options
	delete_option( 'geodir_franchise_version' );
	delete_option( 'geodir_franchise_db_version' );
	delete_option( 'geodir_franchise_posttypes' );

	// Clear any cached data that has been removed.
	wp_cache_flush();
}