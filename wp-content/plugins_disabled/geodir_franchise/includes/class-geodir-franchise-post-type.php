<?php
/**
 * Franchise Manager Post Type class.
 *
 * @since 2.0.0
 * @package Geodir_Franchise
 * @author AyeCode Ltd
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * GeoDir_Franchise_Post_Type class.
 */
class GeoDir_Franchise_Post_Type {

	/**
	 * Hook in methods.
	 */
	public static function init() {
		// Add cpt setting franchise support option.
		add_filter( 'geodir_get_settings_cpt', array( __CLASS__, 'filter_cpt_settings' ), 40, 3 );

		// Sanitize post type data.
		add_filter( 'geodir_save_post_type', array( __CLASS__, 'sanitize_post_type' ), 11, 3 );

		// Post type saved.
		add_action( 'geodir_post_type_saved', array( __CLASS__, 'post_type_saved' ), 11, 3 );

		// Post type franchise supports enabled.
		add_action( 'geodir_franchise_pt_franchise_supports_enabled', array( __CLASS__, 'pt_franchise_supports_enabled' ), 10, 1 );

		// Post type franchise supports disabled.
		add_action( 'geodir_franchise_pt_franchise_supports_disabled', array( __CLASS__, 'pt_franchise_supports_disabled' ), 10, 1 );

		add_filter( 'geodir_post_type_supports', array( __CLASS__, 'post_type_supports' ), 10, 3 );
	}

	public static function filter_cpt_settings( $settings, $current_section = '', $post_type_values = array() ) {
		$post_type = ! empty( $_GET['post_type'] ) ? sanitize_text_field( $_GET['post_type'] ) : '';

		if ( ! empty( $settings ) ) {
			// Franchise supports setting
			$new_settings = array();
			foreach ( $settings as $key => $setting ) {
				if ( ! empty( $setting['id'] ) && $setting['id'] == 'cpt_settings' && $setting['type'] == 'sectionend' ) {
					$new_settings[] =  array(
						'name' => __( 'Enable Franchise?', 'geodir-franchise' ),
						'desc' => __( 'Tick to enable franchise feature for this post type. <span style="color:red;">(WARNING: disabling franchise feature will lose all existing franchises data for this post type.)</span>', 'geodir-franchise' ),
						'id'   => 'supports_franchise',
						'type' => 'checkbox',
						'std'  => '0',
						'advanced' => true,
						'value'	   => ( ! empty( $post_type_values['supports_franchise'] ) ? '1' : '0' )
					);
					$new_settings[] =  array(
						'name' => '',
						'desc' => '',
						'id'   => 'prev_supports_franchise',
						'type' => 'hidden',
						'value'	   => ( ! empty( $post_type_values['supports_franchise'] ) ? 'y' : 'n' )
					);
				}
				$new_settings[] = $setting;
			}
			$settings = $new_settings;
		}

		return $settings;
	}

	public static function sanitize_post_type( $data, $post_type, $request ) {
		// Save supports franchise setting
		$data[ $post_type ]['supports_franchise'] = ! empty( $request['supports_franchise'] ) ? true : false;

		return $data;
	}

	public static function post_type_saved( $post_type, $args, $new = false ) {
		$current = ! empty( $args['supports_franchise'] ) ? true : false;
		$previous = ! empty( $_POST['prev_supports_franchise'] ) && $_POST['prev_supports_franchise'] == 'y' ? true : false;
		if ( $new ) {
			$previous = false;
		}
		if ( $current != $previous ) {
			if ( $current && ! $previous ) { // Franchise support enabled.
				do_action( 'geodir_franchise_pt_franchise_supports_enabled', $post_type );
			} else if ( ! $current && $previous ) { // Franchise support disabled.
				do_action( 'geodir_franchise_pt_franchise_supports_disabled', $post_type );
			}

			do_action( 'geodir_franchise_pt_franchise_supports_changed', $post_type, $current, $previous );
		}
	}

	/**
	 * Check a post type's support for a given feature.
	 *
	 * @param bool $value       True if supports else False.
	 * @param string $post_type The post type being checked.
	 * @param string $feature   The feature being checked.
	 * @return bool Whether the post type supports the given feature.
	 */
	public static function post_type_supports( $value, $post_type, $feature ) {
		// Check a post type supports franchise
		if ( $feature == 'franchise' ) {
			$cache_key = 'supports_franchise:' . $post_type;
			$value = geodir_cache_get( $cache_key, 'geodir_franchise' );
			if ( false !== $value ) {
				return $value;
			}

			$post_type_object = geodir_post_type_object( $post_type );
			if ( ! empty( $post_type_object ) && ! empty( $post_type_object->supports_franchise ) ) {
				$value = true;
			} else {
				$value = NULL;
			}

			geodir_cache_set( $cache_key, $value, 'geodir_franchise' );
		}

		return $value;
	}

	/**
	 * Check a taxonomy's support for a given feature.
	 *
	 * @param bool $value       True if supports else False.
	 * @param string $taxonomy  The taxonomy being checked.
	 * @param string $post_type The post type being checked.
	 * @param string $feature   The feature being checked.
	 * @return bool Whether the taxonomy supports the given feature.
	 */
	public static function taxonomy_supports( $value, $taxonomy, $post_type, $feature ) {
		// Check a post type supports franchise
		if ( $feature == 'franchise' ) {
			$value = GeoDir_Post_types::supports( $post_type, $feature, $value );
		}

		return $value;
	}

	public static function pt_franchise_supports_enabled( $post_type ) {
		$fields = GeoDir_Franchise_Fields::franchise_custom_fields( $post_type );

		if ( ! empty( $fields ) ) {
			foreach ( $fields as $key => $field ) {
				geodir_custom_field_save( $field );
			}

			self::update_fields_sort_order( $post_type );
		}
	}

	public static function pt_franchise_supports_disabled( $post_type ) {
		global $wpdb;

		$fields = GeoDir_Franchise_Fields::franchise_custom_fields( $post_type );

		if ( ! empty( $fields ) ) {
			$cfs = new GeoDir_Settings_Cpt_Cf();

			foreach ( $fields as $key => $field ) {
				if ( $field_id = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM " . GEODIR_CUSTOM_FIELDS_TABLE . " WHERE post_type = %s AND htmlvar_name = %s LIMIT 1", array( $post_type, $field['htmlvar_name'] ) ) ) ) {
					$cfs->delete_custom_field( $field_id );
				}
			}
		}
	}

	public static function update_fields_sort_order( $post_type ) {
		global $wpdb;

		$results = $wpdb->get_results( $wpdb->prepare( "SELECT id FROM `" . GEODIR_CUSTOM_FIELDS_TABLE . "` WHERE post_type = %s ORDER BY sort_order ASC, id ASC", array( $post_type ) ) );

		if ( ! empty( $results ) ) {
			$sort_order = 0;
			foreach ( $results as $key => $row ) {
				$sort_order++;

				$wpdb->update( GEODIR_CUSTOM_FIELDS_TABLE, array( 'sort_order' => $sort_order ), array( 'id' => $row->id ) );
			}
		}
	}

	public static function get_franchise_post_types() {
		global $wpdb;

		$post_types = geodir_cache_get( 'geodir_franchise_post_types', 'geodir_franchise' );

		if ( $post_types !== false ) {
			return $post_types;
		}

		$gd_post_types = geodir_get_posttypes();

		$post_types = array();
		foreach ( $gd_post_types as $post_type ) {
			if ( GeoDir_Post_types::supports( $post_type, 'franchise' ) ) {
				$post_types[] = $post_type;
			}
		}

		geodir_cache_set( 'geodir_franchise_post_types', $post_types, 'geodir_franchise' );

		return $post_types;
	}
}
