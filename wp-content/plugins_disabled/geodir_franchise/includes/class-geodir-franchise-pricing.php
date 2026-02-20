<?php
/**
 * Franchise Manager Pricing Manager class.
 *
 * @since 2.0.0
 * @package Geodir_Franchise
 * @author AyeCode Ltd
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * GeoDir_Franchise_Pricing class.
 */
class GeoDir_Franchise_Pricing {

	public static function init() {
		// Package settings
		add_filter( 'geodir_pricing_package_settings', array( __CLASS__, 'pricing_package_settings' ), 9, 3 );
		add_filter( 'geodir_pricing_process_data_for_save', array( __CLASS__, 'pricing_process_data_for_save' ), 1, 3 );
		add_action( 'geodir_after_custom_fields_updated', array( __CLASS__, 'pricing_save_package_id' ), 99, 1 );

		// Exclude packages for fields
		add_filter( 'geodir_pricing_package_skip_exclude_field_franchise_fields', '__return_true', 10, 3 );
		add_filter( 'geodir_pricing_package_skip_exclude_field_franchise_of', '__return_true', 10, 3 );
		add_filter( 'geodir_franchise_skip_lock_field_name_package_id', '__return_true', 10, 4 );
		add_filter( 'geodir_franchise_skip_lock_field_name_expire_date', '__return_true', 10, 4 );
		add_filter( 'geodir_franchise_add_franchise_url', array( __CLASS__, 'package_franchise_url' ), 40, 3 );
		add_filter( 'geodir_franchise_admin_add_franchise_url', array( __CLASS__, 'package_admin_franchise_url' ), 40, 3 );
		add_filter( 'geodir_franchise_can_add_franchise', array( __CLASS__, 'package_can_add_franchise' ), 10, 2 );
		add_filter( 'geodir_pricing_package_field_options', array( __CLASS__, 'pricing_package_field_options' ), 11, 2 );
		add_filter( 'geodir_pricing_package_features', array( __CLASS__, 'pricing_package_features' ), 11, 4 );
		add_filter( 'geodir_pricing_skip_invoice', array( __CLASS__, 'pricing_skip_invoice' ), 11, 2 );

		add_action( 'geodir_pricing_complete_package_post_updated', array( __CLASS__, 'on_complete_package_post_updated' ), 1, 4 );

		// Invoicing
		add_filter( 'wpinv_get_item_types', array( __CLASS__, 'wpi_register_item_type' ), 40, 1 );
		add_action( 'geodir_pricing_wpi_sync_product_done', array( __CLASS__, 'wpi_sync_product_done' ), 10, 3 );
		add_filter( 'geodir_pricing_wpi_insert_invoice_data', array( __CLASS__, 'wpi_insert_invoice_data' ), 1, 5 );
		add_filter( 'geodir_pricing_wpi_post_package_data', array( __CLASS__, 'wpi_post_package_data' ), 1, 4 );//
		add_filter( 'wpinv_admin_invoice_line_item_summary', array( __CLASS__, 'wpi_admin_line_item_summary' ), 10, 4 );
		add_filter( 'wpinv_email_invoice_line_item_summary', array( __CLASS__, 'wpi_email_line_item_summary' ), 10, 4 );
		add_filter( 'wpinv_print_invoice_line_item_summary', array( __CLASS__, 'wpi_print_line_item_summary' ), 10, 4 );

		// GetPaid
		add_action( 'geodir_pricing_manager_after_sync_package_to_getpaid_item', array( __CLASS__, 'getpaid_sync_product_done' ), 10, 2 );
		add_filter( 'geodir_pricing_getpaid_invoice_item_data', array( __CLASS__, 'getpaid_insert_invoice_data' ), 10, 5 );
		add_filter( 'geodir_pricing_getpaid_post_package_data', array( __CLASS__, 'getpaid_post_package_data' ), 1, 4 );

		// WooCommerce
		add_action( 'geodir_pricing_wc_sync_product_done', array( __CLASS__, 'wc_sync_product_done' ), 10, 3 );
		add_action( 'geodir_pricing_wc_cart_product_id', array( __CLASS__, 'wc_cart_product_id' ), 1, 5 );
		add_filter( 'geodir_pricing_wc_post_package_data', array( __CLASS__, 'wc_post_package_data' ), 1, 4 );
		add_filter( 'geodir_pricing_wc_get_package_id', array( __CLASS__, 'wc_get_package_id' ), 10, 2 );
	}

	public static function pricing_package_settings( $settings, $package_data ) {
		if ( ! ( ! empty( $package_data['post_type'] ) && GeoDir_Post_types::supports( $package_data['post_type'], 'franchise' ) ) ) {
			return $settings;
		}
		
		$new_settings = array();

		foreach ( $settings as $key => $setting ) {
			if ( ! empty( $setting['id'] ) && $setting['id'] == 'package_features_settings' && ! empty( $setting['type'] ) && $setting['type'] == 'sectionend' ) {
				$new_settings[] = array(
					'type'     => 'text',
					'id'       => 'package_franchise_cost',
					'title'    => wp_sprintf( __( 'Franchise Cost (%s)', 'geodir-franchise' ), geodir_pricing_currency_sign() ),
					'desc'     => __( 'Franchise price will be charged to each franchise. Ex: 5.00', 'geodir-franchise' ),
					'placeholder' => __( 'Free', 'geodir-franchise' ),
					'std'      => '',
					'desc_tip' => true,
					'advanced' => false,
					'value'	   => ( ! empty( $package_data['franchise_cost'] ) ? $package_data['franchise_cost'] : '' )
				);
				$new_settings[] = array(
					'type'     => 'number',
					'id'       => 'package_franchise_limit',
					'title'    => __( 'Franchises Limit', 'geodir-franchise' ),
					'desc'     => __( 'Limit the number of franchises that can be added under main listing for this price package. Leave blank or add 0 (zero) for unlimited.', 'geodir-franchise' ),
					'placeholder' => __( 'Unlimited', 'geodir-franchise' ),
					'std'      => '',
					'desc_tip' => true,
					'advanced' => true,
					'custom_attributes' => array(
						'min' => '0',
						'step' => '1',
					),
					'value'	   => ( ! empty( $package_data['franchise_limit'] ) ? absint( $package_data['franchise_limit'] ) : '' )
				);
			}
			$new_settings[] = $setting;
		}

		return $new_settings;
	}

	public static function pricing_process_data_for_save( $package_data, $data, $package ) {
		if ( ! ( ! empty( $package_data['post_type'] ) && GeoDir_Post_types::supports( $package_data['post_type'], 'franchise' ) ) ) {
			return $package_data;
		}

		// Franchise cost
		if ( isset( $data['franchise_cost'] ) ) {
			$package_data['meta']['franchise_cost'] = geodir_pricing_format_decimal( $data['franchise_cost'] );
		} else if ( isset( $package['franchise_cost'] ) ) {
			$package_data['meta']['franchise_cost'] = $package['franchise_cost'];
		} else {
			$package_data['meta']['franchise_cost'] = '0';
		}

		// Franchise limit
		if ( isset( $data['franchise_limit'] ) ) {
			$package_data['meta']['franchise_limit'] = absint( $data['franchise_limit'] );
		} else if ( isset( $package['franchise_limit'] ) ) {
			$package_data['meta']['franchise_limit'] = $package['franchise_limit'];
		} else {
			$package_data['meta']['franchise_limit'] = 0;
		}

		return $package_data;
	}

	public static function pricing_save_package_id( $field_id ) {
		global $wpdb;

		$field = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM " . GEODIR_CUSTOM_FIELDS_TABLE . " WHERE id = %d", array( $field_id ) ) );
		if ( empty( $field ) ) {
			return;
		}

		if ( $field->htmlvar_name == 'franchise' ) {
			$wpdb->query( $wpdb->prepare( "UPDATE " . GEODIR_CUSTOM_FIELDS_TABLE . " SET packages = %s WHERE ( htmlvar_name = 'franchise_of' OR htmlvar_name = 'franchise_fields' ) AND post_type = %s", array( $field->packages, $field->post_type ) ) );
		} else if ( $field->htmlvar_name == 'franchise_fields' || $field->htmlvar_name == 'franchise_of' ) {
			$franchise_field = $wpdb->get_row( $wpdb->prepare( "SELECT id, packages FROM " . GEODIR_CUSTOM_FIELDS_TABLE . " WHERE htmlvar_name = 'franchise' AND post_type = %s", array( $field->post_type ) ) );

			if ( ! empty( $franchise_field ) ) {
				$wpdb->query( $wpdb->prepare( "UPDATE " . GEODIR_CUSTOM_FIELDS_TABLE . " SET packages = %s WHERE ( htmlvar_name = 'franchise_of' OR htmlvar_name = 'franchise_fields' ) AND post_type = %s", array( $franchise_field->packages, $field->post_type ) ) );
			}
		}
	}

	public static function package_has_franchise( $package ) {
		if ( is_int( $package ) ) {
			$package = GeoDir_Pricing_Package::get_package( $package );
		}

		if ( empty( $package->id ) ) {
			return false;
		}

		if ( ! GeoDir_Post_types::supports( $package->post_type, 'franchise' ) ) {
			return false;
		}

		$has_franchise = true;
		$has_franchise = (bool) GeoDir_Pricing_Package::check_field_visibility( $has_franchise, 'franchise', $package->id, $package->post_type );

		return apply_filters( 'geodir_franchise_package_has_franchise', $has_franchise, $package );
	}

	public static function get_franchise_cost( $package_id ) {
		if ( is_object( $package_id ) ) {
			$package_id = $package_id->id;
		}

		$franchise_cost = (float) geodir_pricing_get_meta( (int) $package_id, 'franchise_cost', true );

		return apply_filters( 'geodir_franchise_get_franchise_cost', $franchise_cost, $package_id );
	}

	public static function get_franchise_limit( $package_id ) {
		if ( is_object( $package_id ) ) {
			$package_id = $package_id->id;
		}

		$franchise_limit = (float) geodir_pricing_get_meta( (int) $package_id, 'franchise_limit', true );

		return apply_filters( 'geodir_franchise_get_franchise_limit', $franchise_limit, $package_id );
	}

	public static function package_franchise_url( $url, $post_id, $main_post_id = 0 ) {
		if ( $main_post_id > 0 ) {
			$url = add_query_arg( array( 'package_id' => geodir_get_post_meta( $main_post_id, 'package_id', true ) ), $url );
		}
		return $url;
	}

	public static function package_admin_franchise_url( $url, $post_id, $main_post_id = 0 ) {
		if ( $main_post_id > 0 ) {
			$url = add_query_arg( array( 'package_id' => geodir_get_post_meta( $main_post_id, 'package_id', true ) ), $url );
		}
		return $url;
	}

	public static function package_can_add_franchise( $allow, $post_id ) {
		if ( $allow && ( $main_post_id = geodir_franchise_main_post_id( (int) $post_id ) ) ) {
			$available = self::post_franchise_available( (int) $main_post_id );

			if ( $available == -1 || $available > 0 ) {
				$allow = true;
			} else {
				$allow = false;
			}
		}
		return $allow;
	}

	public static function post_franchise_available( $post_id ) {
		$available = 0;

		$limit = self::post_franchise_limit( $post_id );
		if ( $limit > 0 ) {
			$franchises = geodir_franchise_post_franchises( $post_id, array( 'owner' => false ) );
			$count_franchises = ! empty( $franchises ) ? count( $franchises ) : 0;
			if ( $limit > $count_franchises ) {
				$available = $limit - $count_franchises;
			} else {
				$available = 0;
			}
		} else {
			$available = $limit;
		}

		return $available;
	}

	public static function post_franchise_limit( $post_id ) {
		$limit = 0;

		if ( $package_id = (int) geodir_get_post_meta( $post_id, 'package_id', true ) ) {
			$limit = absint( self::get_franchise_limit( $package_id ) );

			if ( $limit == 0 ) {
				$limit = -1; // Unlimited
			}
		}

		return $limit;
	}

	// Invoicing
	public static function wpi_register_item_type( $item_types ) {
		$item_types['franchise_package'] = __( 'Franchise', 'geodir-franchise' );

		return $item_types;
	}

	public static function wpi_sync_product_done( $item, $package, $new = false ) {
		if ( ! ( ! empty( $package->post_type ) && GeoDir_Post_types::supports( $package->post_type, 'franchise' ) ) ) {
			return;
		}

		if ( self::package_has_franchise( $package ) ) { // Merge franchise to invoice item
			$product_id = self::wpi_product_id( $package );

			$custom_name = wp_sprintf( geodir_franchise_label( 'item_product_name', $package->post_type ), get_post_type_singular_label( $package->post_type ) );

			$data = array(
				'type'                 => 'franchise_package',
				'title'                => wp_sprintf( geodir_franchise_label( 'item_package_name', $package->post_type ), $package->name ),
				'custom_id'            => $package->id,
				'price'                => wpinv_round_amount( self::get_franchise_cost( $package ) ),
				'status'               => $package->status == 1 ? 'publish' : 'pending',
				'custom_name'          => $custom_name,
				'custom_singular_name' => $custom_name,
				'vat_rule'             => 'digital',
				'vat_class'            => '_standard',
				'editable'             => 0,
				'excerpt'              => __( 'Franchise package.', 'geodir-franchise' ),
				'free_trial'		   => '0',
				'trial_period'		   => '',
				'trial_interval'	   => '',
			);

			if ( !empty( $package->recurring ) ) {
				$data['is_recurring']       = 1;
				$data['recurring_period']   = $package->time_unit;
				$data['recurring_interval'] = absint( $package->time_interval );
				$data['recurring_limit']    = absint( $package->recurring_limit );
			} else {
				$data['is_recurring']       = 0;
				$data['recurring_period']   = '';
				$data['recurring_interval'] = '';
				$data['recurring_limit']    = '';
			}

			if ( $product_id ) {
				$data['ID'] = $product_id;
			}

			$data = apply_filters( 'geodir_franchise_wpi_sync_product_data', $data, $package );

			$product = wpinv_create_item( $data, false, true );

			if ( $product ) {
				if ( ! empty( $product->ID ) ) {
					// Update meta.
					geodir_pricing_update_meta( $package->id, 'wpi_franchise_product_id', $product->ID );
				}

				do_action( 'geodir_franchise_wpi_sync_product_done', $product, $package, ! empty( $product_id ) );
			}
		} else {
			
		}
	}

	public static function wpi_product_id( $package ) {
		$package_id = 0;

		if ( is_int( $package ) ) {
			$package_id = $package;
		} else if ( is_object( $package ) && ! empty( $package->id ) ) {
			$package_id = $package->id;
		}

		if ( empty( $package_id ) ) {
			return NULL;
		}

		$franchise_product_id = (int) geodir_pricing_get_meta( (int) $package_id, 'wpi_franchise_product_id', true );
		if ( empty( $franchise_product_id ) ) {
			return NULL;
		}
	
		$product = wpinv_get_item( $franchise_product_id );
		if ( empty( $product ) ) {
			return NULL;
		}

		$product_id = $product->ID;

		return (int) apply_filters( 'geodir_franchise_wpi_product_id', $product_id, $package_id );
	}

	public static function wpi_insert_invoice_data( $data, $task, $post_id, $package_id, $post_data ) {
		if ( geodir_franchise_is_franchise( (int) $post_id ) ) {
			// Add franchise item id.
			if ( $product_id = (int) self::wpi_product_id( (int) $package_id ) ) {
				$data['cart_details'][0]['id'] = $product_id;
			}

			// New franchise
			if ( $task == 'new' ) {
				$data['cart_details'][0]['meta']['invoice_title'] = wp_sprintf( geodir_franchise_label( 'item_invoice_title', get_post_type( $post_id ) ), get_the_title( $post_id ) );
			}
		}
		return $data;
	}

	public static function wpi_post_package_data( $data, $post_id, $package_id, $post_data ) {
		if ( geodir_franchise_is_franchise( (int) $post_id ) ) {
			if ( $product_id = (int) self::wpi_product_id( (int) $package_id ) ) {
				$data['product_id'] = $product_id;
			}
			$meta = ! empty( $data['meta'] ) ? (array) maybe_unserialize( $data['meta'] ) : array();
			$meta['franchise'] = 1;
			$data['meta'] = maybe_serialize( $meta );
		}

		return $data;
	}

	public static function wpi_admin_line_item_summary( $summary, $cart_item, $wpi_item, $invoice ) {
		if ( !empty( $wpi_item ) && !empty( $cart_item['meta']['post_id'] ) && ( $wpi_item->get_type() == 'franchise_package' ) ) {
			$post_link = '<a href="' . get_edit_post_link( $cart_item['meta']['post_id'] ) .'" target="_blank">' . (!empty($cart_item['meta']['invoice_title']) ? $cart_item['meta']['invoice_title'] : get_the_title( $cart_item['meta']['post_id']) ) . '</a>';
			$summary = wp_sprintf( '%s: %s', $wpi_item->get_custom_singular_name(), $post_link );
		}

		return $summary;
	}

	public static function wpi_email_line_item_summary( $summary, $cart_item, $wpi_item, $invoice ) {
		if ( !empty( $wpi_item ) && !empty( $cart_item['meta']['post_id'] ) && ( $wpi_item->get_type() == 'franchise_package' ) ) {
			$post_link = '<a href="' . get_permalink( $cart_item['meta']['post_id'] ) .'" target="_blank">' . ( !empty($cart_item['meta']['invoice_title'] ) ? $cart_item['meta']['invoice_title'] : get_the_title( $cart_item['meta']['post_id']) ) . '</a>';
			$summary = wp_sprintf( '%s: %s', $wpi_item->get_custom_singular_name(), $post_link );
		}

		return $summary;
	}

	public static function wpi_print_line_item_summary( $summary, $cart_item, $wpi_item, $invoice ) {
		if ( !empty( $wpi_item ) && !empty( $cart_item['meta']['post_id'] ) && ( $wpi_item->get_type() == 'franchise_package' ) ) {
			$title = !empty( $cart_item['meta']['invoice_title'] ) ? $cart_item['meta']['invoice_title'] : get_the_title( $cart_item['meta']['post_id'] );
			$summary = wp_sprintf( '%s: %s', $wpi_item->get_custom_singular_name(), $title );
		}
		
		return $summary;
	}

	// WooCommerce
	public static function wc_product_id( $package ) {
		$package_id = 0;

		if ( is_int( $package ) ) {
			$package_id = $package;
		} else if ( is_object( $package ) && ! empty( $package->id ) ) {
			$package_id = $package->id;
		}

		if ( empty( $package_id ) ) {
			return NULL;
		}

		$franchise_product_id = (int) geodir_pricing_get_meta( (int) $package_id, 'wc_franchise_product_id', true );
		if ( empty( $franchise_product_id ) ) {
			return NULL;
		}
	
		$product = wc_get_product( $franchise_product_id );
		if ( empty( $product ) ) {
			return NULL;
		}

		$product_id = $product->get_id();

		return (int) apply_filters( 'geodir_franchise_wc_product_id', $product_id, $package_id );
	}

	public static function wc_sync_product_done( $item, $package, $new = false ) {
		if ( ! ( ! empty( $package->post_type ) && GeoDir_Post_types::supports( $package->post_type, 'franchise' ) ) ) {
			return;
		}

		if ( self::package_has_franchise( $package ) ) { // Merge franchise to invoice item
			$product_id = self::wc_product_id( $package );
			$is_new_product = empty( $product_id ) ? true : false;

			$request = array(
				'id' => $product_id,
				'name' => wp_sprintf( geodir_franchise_label( 'item_package_name', $package->post_type ), $package->name ),
				'status' => ! empty( $package->status ) ? 'publish' : 'draft',
				'description' => $package->description,
				'short_description' => wp_sprintf( geodir_franchise_label( 'item_package_name', $package->post_type ), $package->title ),
				'price' => (float) self::get_franchise_cost( $package ),
				'regular_price' => (float) self::get_franchise_cost( $package ),
				'sku' => $package->post_type . '-franchise-package-' . $package->id,
				'type' => 'simple',
			);

			if ( ! empty( $package->recurring ) ) {
				$request['type'] = 'subscription';

				// YITH Subscriptions
				if ( defined( 'YITH_YWSBS_VERSION' ) ) {
					$request['type'] = 'simple';
				}
				$request['_subscription_price'] = $request['price'];

				$periods = array(
					'D' => 'day',
					'W' => 'week',
					'M' => 'month',
					'Y' => 'year'
				);

				$request['_subscription_period'] = 'day';
				if ( isset( $periods[ $package->time_unit ] ) ) {
					$request['_subscription_period'] = $periods[ $package->time_unit ];
				}

				$request['_subscription_period_interval'] = absint( $package->time_interval );
				$request['_subscription_length'] = absint( $package->recurring_limit );

				$trial_interval = absint( $package->trial_interval );
				if ( $trial_interval ) {
					$request['_subscription_trial_length'] = $trial_interval;
					$request['_subscription_trial_period'] = 'day';
					if ( isset( $periods[ $package->trial_unit ] ) ) {
						$request['_subscription_trial_period'] = $periods[ $package->trial_unit ];
					}
				}
			}

			$request = apply_filters( 'geodir_franchise_wc_sync_product_data', $request, $package );

			try {
				$product = GeoDir_Pricing_Cart_WooCommerce::save_product( $request, $package, $is_new_product );

				if ( is_wp_error( $product ) ) {
					if ( $is_new_product ) {
						geodir_error_log( sprintf( 'Error creating franchise product for package ID %s: %s', $package->id, $product->get_error_message() ), 'Pricing -> WooCommerce' );
					} else {
						geodir_error_log( sprintf( 'Error updating franchise product #%s for package ID %s: %s', $id, $package->id, $product->get_error_message() ), 'Pricing -> WooCommerce' );
					}
					if ( $wp_error ) {
						return $product;
					} else {
						return false;
					}
				} else if ( ! empty( $product ) ) {
					if ( $is_new_product ) {
						geodir_error_log( sprintf( 'Franchise product #%d created for package ID %s', $product, $package->id ), 'Pricing -> WooCommerce' );
					}
					
					// Update meta.
					geodir_pricing_update_meta( $package->id, 'wc_franchise_product_id', $product );

					do_action( 'geodir_franchise_wc_sync_product_done', $product, $package, $is_new_product );

					return $product;
				}
			} catch ( Exception $e ) {
				if ( $is_new_product ) {
					geodir_error_log( sprintf( 'Error creating franchise product for package ID %s: %s', $package->id, $e->getMessage() ), 'Pricing -> WooCommerce' );
				} else {
					geodir_error_log( sprintf( 'Error updating franchise product #%s for package ID %s: %s', $id, $package->id, $e->getMessage() ), 'Pricing -> WooCommerce' );
				}

				if ( $wp_error ) {
					return new WP_Error(
						"geodir_franchise_wc_sync_package_error", $e->getMessage(), array(
							'status' => 404,
						)
					);
				} else {
					return false;
				}
			}

			return false;
		} else {
			
		}
	}

	public static function wc_cart_product_id( $product_id, $task, $post_id, $package_id, $post_data ) {
		if ( geodir_franchise_is_franchise( (int) $post_id ) && ( $wc_product_id = (int) self::wc_product_id( (int) $package_id ) ) ) {
			$product_id = $wc_product_id;
		}

		return $product_id;
	}

	public static function wc_post_package_data( $data, $post_id, $package_id, $post_data ) {
		if ( geodir_franchise_is_franchise( (int) $post_id ) ) {
			$meta = ! empty( $data['meta'] ) ? (array) maybe_unserialize( $data['meta'] ) : array();
			$meta['franchise'] = 1;
			$data['meta'] = maybe_serialize( $meta );
		}

		return $data;
	}

	/**
	 * Get the package ID of the franchise product.
	 *
	 * @since 2.7.15
	 *
	 * @param int $package_id The package ID.
	 * @param int $product_id The product ID.
	 * @return int The package ID.
	 */
	public static function wc_get_package_id( $package_id, $product_id ) {
		global $wpdb;

		if ( empty( $package_id ) ) {
			$package_id = (int) $wpdb->get_var( $wpdb->prepare( "SELECT p.id FROM " . GEODIR_PRICING_PACKAGES_TABLE . " AS p LEFT JOIN " . GEODIR_PRICING_PACKAGE_META_TABLE . " AS pm ON pm.package_id = p.id WHERE pm.meta_key = %s AND pm.meta_value = %s ORDER BY `pm`.`meta_id` ASC", array( 'wc_franchise_product_id' , $product_id ) ) );
		}

		return $package_id;
	}

	public static function pricing_package_field_options( $field_options, $package ) {
		if ( isset( $field_options['franchise'] ) ) {
			unset( $field_options['franchise'] );
		}

		return $field_options;
	}

	public static function pricing_package_features( $features, $package, $params, $args ) {
		if ( GeoDir_Post_types::supports( $package->post_type, 'franchise' ) ) {
			$feature = array( 
				'order' => 8.5 
			);

			if ( self::package_has_franchise( $package ) ) {
				$franchise_limit = self::get_franchise_limit( $package );

				if ( (float) self::get_franchise_cost( $package ) > 0 ) {
					$franchise_cost = geodir_pricing_price( self::get_franchise_cost( $package ) );

					if ( $franchise_limit > 0 ) {
						$text = wp_sprintf( _n( '%d franchise at %s', '%d franchises at %s/franchise', $franchise_limit, 'geodir-franchise' ), $franchise_limit, $franchise_cost );
					} else {
						$text = wp_sprintf( __( 'Unlimited franchises at %s/franchise', 'geodir-franchise' ), $franchise_cost );
					}
				} else {
					if ( $franchise_limit > 0 ) {
						$text = wp_sprintf( _n( '%d franchise for free', '%d franchises for free', $franchise_limit, 'geodir-franchise' ), $franchise_limit );
					} else {
						$text = __( 'Unlimited franchises for free', 'geodir-franchise' );
					}
				}
				$feature['text'] = $text;
				$feature['icon'] = $params['fa_icon_tick'];
				$feature['color'] = $params['color_highlight'];
			} else {
				$feature['text'] = geodir_franchise_label( 'name', $package->post_type );
				$feature['icon'] = $params['fa_icon_untick'];
				$feature['color'] = $params['color_default'];
			}

			$features['franchise'] = $feature;
		}

		return $features;
	}

	public static function pricing_skip_invoice( $skip_invoice, $post_data ) {
		if ( $skip_invoice && empty( $post_data['franchise_of'] ) ) {
			return $skip_invoice;
		}

		$package_id = ! empty( $post_data['package_id'] ) ? absint( $post_data['package_id'] ) : 0;
		if ( empty( $package_id ) || empty( $post_data['ID'] ) ) {
			return $skip_invoice;
		}

		$post_id = absint( $post_data['ID'] );

		if ( $post_parent = wp_is_post_revision( $post_id ) ) {
			$post_id = $post_parent;
		}

		if ( geodir_franchise_is_franchise( $post_id ) && ( $package = geodir_pricing_get_package( $package_id ) ) ) {
			if ( self::package_has_franchise( $package ) ) {
				if ( (float) self::get_franchise_cost( $package ) > 0 ) {
					$skip_invoice = false;
				} else {
					$skip_invoice = true;
				}
			}
		}

		return $skip_invoice;
	}

	public static function on_complete_package_post_updated( $post_id, $package_id, $post_package_id, $revision_id = 0 ) {
		global $wpdb;

		$franchises = geodir_franchise_post_franchises( $post_id, array( 'owner' => false ) );

		if ( ! empty( $franchises ) && ( $main_post = geodir_get_post_info( (int) $post_id ) ) ) {
			foreach ( $franchises as $franchise ) {
				$wpdb->update( geodir_db_cpt_table( $main_post->post_type ), array( 'package_id' => $main_post->package_id, 'expire_date' => $main_post->expire_date ), array( 'post_id' => $franchise ) );
			}
		}
	}

	public static function getpaid_sync_product_done( $item, $package ) {
		if ( ! ( ! empty( $package->post_type ) && GeoDir_Post_types::supports( $package->post_type, 'franchise' ) ) ) {
			return;
		}

		if ( ! self::package_has_franchise( $package ) ) {
			return;
		}

		// Merge franchise to invoice item
		$item_id = self::wpi_product_id( $package );

		// Prepare the associated invoicing item.
		$item = wpinv_get_item_by_id( $item_id );
		$item = new WPInv_Item( $item );

		$name = wp_sprintf( geodir_franchise_label( 'item_package_name', $package->post_type ), $package->name );
		$custom_name = wp_sprintf( geodir_franchise_label( 'item_product_name', $package->post_type ), get_post_type_singular_label( $package->post_type ) );

		// Set the item props.
		$item->set_type( 'franchise_package' );
		$item->set_name( $name );
		$item->set_description( $name );
		$item->set_custom_id( $package->id );
		$item->set_price( wpinv_round_amount( self::get_franchise_cost( $package ) ) );
		$item->set_status( $package->status == 1 ? 'publish' : 'pending' );
		$item->set_custom_name( $custom_name );
		$item->set_custom_singular_name( $custom_name );
		$item->set_is_editable( false );

		// Handle recurring props.
		$trial_interval = absint( $package->trial_interval );
		$item->set_is_recurring( ! empty( $package->recurring ) );
		$item->set_recurring_period( $package->time_unit );
		$item->set_recurring_interval( absint( $package->time_interval ) );
		$item->set_recurring_limit( absint( $package->recurring_limit ) );
		$item->set_is_free_trial( $trial_interval > 0 );
		$item->set_trial_period( $package->trial_unit );
		$item->set_trial_interval( $trial_interval );

		// Save the item.
		$item->save();

		// Abort if it was not successful.
		if ( ! $item->exists() ) {
			return false;
		}

		// Cache the item id to the package.
		geodir_pricing_update_meta( $package->id, 'wpi_franchise_product_id', $item->get_id() );

		// Fires after saving the item.
		do_action( 'geodir_franchise_getpaid_sync_product_done', $item, $package, ! empty( $item_id ) );

		return $item;
	}

	public static function getpaid_insert_invoice_data( $data, $post_id, $package_id, $item, $customer_id ) {
		if ( geodir_franchise_is_franchise( (int) $post_id ) ) {
			// Add franchise item id.
			if ( $product_id = (int) self::wpi_product_id( (int) $package_id ) ) {
				$data['item_id'] = $product_id;
			}

			$data['meta']['invoice_title'] = wp_sprintf( geodir_franchise_label( 'item_invoice_title', get_post_type( $post_id ) ), get_the_title( $post_id ) );
		}

		return $data;
	}

	public static function getpaid_post_package_data( $data, $post_id, $package_id, $post_data ) {
		if ( geodir_franchise_is_franchise( (int) $post_id ) ) {
			if ( $product_id = (int) self::wpi_product_id( (int) $package_id ) ) {
				$data['product_id'] = $product_id;
			}
			$meta = ! empty( $data['meta'] ) ? (array) maybe_unserialize( $data['meta'] ) : array();
			$meta['franchise'] = 1;
			$data['meta'] = maybe_serialize( $meta );
		}

		return $data;
	}
}