<?php
/**
 * Pricing Manager bundle class.
 *
 * @since 2.7.10
 * @package GeoDir_Pricing_Manager
 * @author AyeCode Ltd
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * GeoDir_Pricing_Bundle class.
 */
class GeoDir_Pricing_Bundle {

	/**
	 * Setup.
	 */
	public static function init() {
		if ( ! self::is_cart_active() ) {
			return;
		}

		// Core WP
		add_action( 'save_post', array( __CLASS__, 'on_save_post' ), 99, 3 );

		// GD
		add_action( 'geodir_before_detail_fields', array( __CLASS__, 'geodir_before_detail_fields' ), 1 );
		add_filter( 'geodir_pricing_getpaid_pre_invoice_save', array( __CLASS__, 'pricing_getpaid_pre_invoice_save' ), 11, 5 );
		add_filter( 'geodir_pricing_getpaid_pre_claim_invoice_save', array( __CLASS__, 'pricing_getpaid_pre_claim_invoice_save' ), 11, 5 );
		add_action( 'geodir_pricing_complete_package_post_updated', array( __CLASS__, 'pricing_complete_package_post_updated' ), 11, 5 );

		// GetPaid
		add_filter( 'wpinv_get_item_types', array( __CLASS__, 'register_package_item_type' ), 11, 1 );
		add_filter( 'getpaid_item_type_supports', array( __CLASS__, 'item_type_supports' ), 10, 4 );
		add_action( 'getpaid_invoice_status_changed', array( __CLASS__, 'invoice_status_changed' ), 11, 3 );
		add_action( 'getpaid_subscription_cancelled', array( __CLASS__, 'handle_subscription_cancelled' ), 11, 2 );
		add_action( 'getpaid_subscription_expired',   array( __CLASS__, 'handle_subscription_expired' ), 11, 2 );
	}

	public static function is_cart_active() {
		return geodir_get_option( 'pm_cart' ) == 'invoicing';
	}

	public static function is_getpaid_active() {
		global $geodir_pricing_manager;

		return self::is_cart_active() && ! empty( $geodir_pricing_manager->cart_class ) && $geodir_pricing_manager->cart_class == 'GeoDir_Pricing_Cart_GetPaid' ? true : false;;
	}

	public static function register_package_item_type( $item_types ) {
		if ( ! self::is_getpaid_active() ) {
			return $item_types;
		}

		$item_types['bundle_packages'] = __( 'Bundle Of Packages', 'geodir_pricing' );

		return $item_types;
	}

	public static function item_type_supports( $supports, $item_type, $feature, $item_ID ) {
		if ( $feature == 'buy_now' && $item_type === 'bundle_packages' ) {
			$supports = true;
		}

		return $supports;
	}

	public static function display_meta_box_bundle( $post ) {
		if ( ! self::is_getpaid_active() ) {
			return;
		}

		$post_ID = absint( $post->ID );

		add_action( 'admin_footer', array( __CLASS__, 'add_admin_footer_script' ), 11 );

		$type = get_post_meta( $post_ID, '_wpinv_type', true );
		$items_class = $type == 'bundle_packages' ? '' : ' d-none';
		$type_class = $type == 'bundle_packages' ? ' d-none' : '';

		$results = self::get_bundle_allowed_packages();

		$output = '<div class="bsui"><div class="geodir-bundle-wrap p-3"><input type="hidden" name="geodir_bundle_item" value="' . absint( $post_ID ) . '">';
		$output .= '<div class="geodir-bundle-items' . esc_attr( $items_class ) . '">';

		if ( ! empty( $results ) ) {
			$value = self::get_item_bundle_value( absint( $post_ID ) );

			$output .= '<div class="row mb-3 font-weight-bold fw-bold">';
				$output .= '<div class="col-sm-1">' . esc_html__( 'ID', 'geodir_pricing' ) . '</div>';
				$output .= '<div class="col-sm-2">' . esc_html__( 'Post Type', 'geodir_pricing' ) . '</div>';
				$output .= '<div class="col-sm-2">' . esc_html__( 'No. Of Listings', 'geodir_pricing' ) . '</div>';
				$output .= '<div class="col-sm-7">' . esc_html__( 'Name', 'geodir_pricing' ) . '</div>';
			$output .= '</div>';

			foreach ( $results as $package_id => $item ) {
				$package_name = '';

				if ( ! empty( $item->fa_icon ) ) {
					$package_name .= '<i class="' . esc_attr( $item->fa_icon ) . '" aria-hidden="true"></i> ';
				}

				$package_name .= wp_sprintf( _x( '%1$s %2$s', 'bundle package name', 'geodir_pricing' ), __( $item->name, 'geodirectory' ), '<span class="text-muted d-inline geodir-price-name">(' . geodir_pricing_get_price_name( $item->id ) . ')</span>' );

				if ( geodir_pricing_is_private( $item->id ) ) {
					$package_name .= '<i class="far fa-eye-slash text-warning c-pointer ms-2 ml-2" title="' . esc_html__( 'private package', 'geodir_pricing' ) . '"></i>';
				}

				$output .= '<div class="row">';
					$output .= '<div class="col-sm-1 col-form-label font-weight-bold fw-bold">' . (int) $item->id . '</div>';
					$output .= '<div class="col-sm-2 col-form-label">' . esc_html( geodir_post_type_name( $item->post_type ) ) . '</div>';
					$output .= '<div class="col-sm-2">';

						$output .= aui()->input( array(
								'type' => 'number',
								'id' => 'geodir_bundle_packages_' . esc_attr( $package_id ),
								'name' => esc_attr( 'geodir_bundle_packages[' . $package_id . ']' ),
								'label_show' => false,
								'size' => 'small',
								'extra_attributes' => array(
									'min' => '0',
									'step' => '1',
								),
								'value' => ! empty( $value[ $package_id ] ) ? absint( $value[ $package_id ] ) : ''
							)
						);

					$output .= '</div>';
					$output .= '<label class="col-sm-7 col-form-label" for="' . esc_attr( 'geodir_bundle_packages_' . $package_id ) . '">' . $package_name . '</label>';
				$output .= '</div>';
			}
		} else {
			$output .= aui()->alert(
				array(
					'type'    => 'info',
					'content' => __( 'Paid packages are not found. Free packages are not allowed in Bundle of packages.', 'geodir_pricing' )
				)
			);
		}

		$output .= '</div>';
		$output .= '<div class="geodir-bundle-msg' . esc_attr( $type_class ) . '">';
			$output .= aui()->alert(
				array(
					'type'    => 'info',
					'content' => __( 'Bundle of listings packages feature supported by item type "Bundle Of Packages" only.', 'geodir_pricing' )
				)
			);
		$output .= '</div>';
		$output .= '</div></div>';

		echo $output;
	}

	public static function get_bundle_allowed_packages( $post_type = '' ) {
		global $wpdb;

		if ( ! empty( $post_type ) ) {
			$post_types = is_array( $post_type ) ? $post_type : array( $post_type );
		} else {
			$post_types = geodir_get_posttypes();
		}

		$results = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM `" . GEODIR_PRICING_PACKAGES_TABLE . "` WHERE `status` = 1 AND `amount` > 0 AND `post_type` IN( " . implode( ', ', array_fill( 0, count( $post_types ), '%s' ) ) . " ) ORDER BY `post_type` ASC, `is_default` ASC, `display_order` ASC", $post_types ) );

		if ( ! empty( $results ) ) {
			$_results = array();

			foreach ( $results as $row ) {
				$skip = apply_filters( 'geodir_pricing_bundle_skip_package', false, $row );

				if ( $skip ) {
					continue;
				}

				$_results[ (int) $row->id ] = $row;
			}

			$results = $_results;
		}

		return apply_filters( 'geodir_pricing_bundle_allowed_packages', $results, $post_type );
	}

	public static function get_item_bundle_value( $post_ID, $check = false ) {
		if ( $check ) {
			$valid = get_post_meta( $post_ID, '_wpinv_type', true ) == 'bundle_packages';
		} else {
			$valid = true;
		}

		$value = $valid ? get_post_meta( $post_ID, '_gd_bundle_packages', true ) : array();

		if ( ! is_array( $value ) ) {
			$value = array();
		}

		return apply_filters( 'geodir_pricing_get_item_bundle_value', $value, $post_ID, $check );
	}

	public static function get_user_bundle_value( $user_id = 0 ) {
		if ( empty( $user_id ) ) {
			$user_id = (int) get_current_user_id();
		}

		if ( empty( $user_id ) ) {
			return array();
		}

		$value = get_user_meta( $user_id, '_gd_bundle_packages', true );

		if ( ! is_array( $value ) ) {
			$value = array();
		}

		return apply_filters( 'geodir_pricing_get_user_bundle_value', $value, $user_id );
	}

	public static function get_user_bundle_count( $package_id, $user_id = 0, $invoice_id = 0, $item_id = 0 ) {
		if ( empty( $package_id ) ) {
			return;
		}

		$bundle = self::get_user_bundle_packages( $user_id, $invoice_id, $item_id );

		$count = ! empty( $bundle ) && ! empty( $bundle[ $package_id ] ) ? absint( $bundle[ $package_id ] ) : 0;

		return $count;
	}

	public static function get_user_bundle_packages( $user_id, $invoice_id = 0, $item_id = 0 ) {
		$bundle = self::get_user_bundle_value( $user_id );

		$items = array();

		if ( empty( $bundle ) ) {
			return $items;
		}

		$bundle_packages = array();

		foreach ( $bundle as $_invoice_id => $invoice_data ) {
			if ( ( empty( $invoice_id ) || $invoice_id == $_invoice_id ) && self::is_valid_invoice( $_invoice_id ) ) {
				foreach ( $invoice_data as $_item_id => $item_data ) {
					if ( ( empty( $item_id ) || $item_id == $_item_id ) ) {
						foreach ( $item_data as $package_id => $count ) {
							if ( ! empty( $bundle_packages[ $package_id ] ) ) {
								$bundle_packages[ $package_id ] = $bundle_packages[ $package_id ] + $count;
							} else {
								$bundle_packages[ $package_id ] = $count;
							}
						}
					}
				}
			}
		}

		return $bundle_packages;
	}

	public static function get_user_bundle_details( $user_id ) {
		$bundle = self::get_user_bundle_packages( $user_id );

		$items = array();

		if ( empty( $bundle ) ) {
			return $items;
		}

		foreach ( $bundle as $package_id => $count ) {
			$package = geodir_pricing_get_package( $package_id );

			if ( empty( $package ) || $count < 0 ) {
				continue;
			}

			$post_type_name = geodir_post_type_name( $package->post_type );
			$post_type_singular_name = geodir_post_type_singular_name( $package->post_type );
			$cpt_title = $count > 1 ? $post_type_name : $post_type_singular_name;
			$price_name = geodir_pricing_get_price_name( $package->id );
			$count_badge = '<span class="badge bg-primary rounded-pill">' . (int) $count . '</span>';
			$price_name_display = '<span class="text-muted d-inline geodir-price-name">(' . $price_name . ')</span>';
			$count_posts = wp_sprintf( _x( '%1$s %2$s', 'bundle count post type', 'geodir_pricing' ), $count, geodir_strtolower( $cpt_title ) );

			$title = wp_sprintf( _x( '%1$s with package %2$s %3$s', 'bundle title', 'geodir_pricing' ), '<b>' . $count_posts . '</b>', __( $package->name, 'geodirectory' ), $price_name_display );

			$item = array(
				'user_id' => $user_id,
				'package_id' => $package_id,
				'count' => $count,
				'count_badge' => $count_badge,
				'post_type' => $package->post_type,
				'post_type_name' => $post_type_name,
				'post_type_singular_name' => $post_type_singular_name,
				'title' => $title,
				'price_name' => $price_name,
				'package' => $package
			);

			$items[ $package_id ] = $item;
		}

		return apply_filters( 'geodir_pricing_get_user_bundle_details', $items, $bundle, $user_id );
	}

	public static function get_invoice_bundle_value( $invoice_id ) {
		$value = get_post_meta( $invoice_id, '_gd_bundle_packages', true );

		if ( ! is_array( $value ) ) {
			$value = array();
		}

		return apply_filters( 'geodir_pricing_get_invoice_bundle_value', $value, $invoice_id );
	}

	public static function is_valid_invoice( $invoice_id ) {
		if ( empty( $invoice_id ) ) {
			return false;
		}

		$invoice = wpinv_get_invoice( $invoice_id );

		if ( ! empty( $invoice ) && $invoice->has_status( 'publish' ) ) {
			if ( $invoice->is_recurring() ) {
				if ( ! $invoice->is_parent() ) {
					return false;
				}

				if ( $subscription_id = $invoice->get_subscription_id() ) {
					$subscription = getpaid_get_subscription( $subscription_id );

					if ( ! empty( $subscription ) && $subscription->is_active() ) {
						return true;
					}
				} else {
					return true;
				}
			} else {
				return true;
			}
		}

		return false;
	}

	public static function on_save_post( $post_ID, $post, $update = false ) {
		if ( ! self::is_getpaid_active() ) {
			return;
		}
		if ( ! empty( $_POST['geodir_bundle_item'] ) && (int) $_POST['geodir_bundle_item'] == $post_ID && isset( $_POST['geodir_bundle_packages'] ) ) {
			$type = isset( $_POST['wpinv_item_type'] ) ? wpinv_clean( $_POST['wpinv_item_type'] ) : get_post_meta( $post_ID, '_wpinv_type', true );

			$value = $type == 'bundle_packages' ? $_POST['geodir_bundle_packages'] : array();

			self::save_item_bundle_value( $post_ID, $value );
		}
	}

	public static function save_item_bundle_value( $post_ID, $value ) {
		$_value = self::parse_item_bundle_value( $value );

		if ( ! empty( $_value ) ) {
			update_post_meta( $post_ID, '_gd_bundle_packages', $_value );
		} else {
			delete_post_meta( $post_ID, '_gd_bundle_packages' );
		}

		do_action( 'geodir_pricing_item_bundle_saved', $post_ID, $_value, $value );
	}

	public static function parse_item_bundle_value( $value ) {
		$_value = array();

		if ( is_array( $value ) ) {
			foreach ( $value as $package_id => $posts ) {
				if ( (int) $package_id > 0 && (int) $posts > 0 ) {
					$_value[ (int) $package_id ] = (int) $posts;
				}
			}
		}

		return $_value;
	}

	public static function invoice_status_changed( $invoice, $from, $to ) {
		if ( ! ( $to == 'publish' && $invoice->get_id() && ( ! $invoice->is_recurring() || ( $invoice->is_recurring() && $invoice->is_parent() ) ) ) ) {
			return;
		}

		$items = $invoice->get_items();

		if ( empty( $items ) ) {
			return;
		}

		$user_id = $invoice->get_user_id();
		if ( empty( $user_id ) ) {
			return;
		}

		$invoice_id = $invoice->get_id();
		$saved_bundle = self::get_invoice_bundle_value( $invoice_id );
		if ( ! empty( $saved_bundle ) ) {
			return;
		}

		$invoice_bundle = array();

		foreach ( $items as $key => $item ) {
			$item_id = $item->get_id();

			if ( ! empty( $invoice_bundle[ $invoice_id ][ $item_id ] ) ) {
				continue;
			}

			$bundle = self::get_item_bundle_value( $item_id, true );
			if ( empty( $bundle ) ) {
				continue;
			}

			$quantity = $item->get_quantity();
			if ( $quantity < 1 ) {
				$quantity = 1;
			}

			$_bundle = array();
			foreach ( $bundle as $package_id => $count ) {
				$_bundle[ $package_id ] = $count * $quantity;
			}

			$invoice_bundle[ $invoice_id ][ $item_id ] = $_bundle;
		}

		if ( ! empty( $invoice_bundle ) ) {
			update_post_meta( $invoice_id, '_gd_bundle_packages', $invoice_bundle );

			self::increase_user_bundle( $user_id, $invoice_bundle );
		}
	}

	/**
	 * This method is called whenever a GetPaid subscription is cancelled.
	 *
	 * @param WPInv_Subscription $subscription
	 */
	public static function handle_subscription_cancelled( $subscription, $transition = array() ) {
		global $wpdb;

		$parent_invoice_id = (int) $subscription->get_parent_invoice_id();
		$subscription_id = (int) $subscription->get_id();

		if ( empty( $parent_invoice_id ) || empty( $subscription_id ) ) {
			return;
		}

		$items = $wpdb->get_results( "SELECT * FROM `" . GEODIR_PRICING_POST_PACKAGES_TABLE . "` WHERE `status` = 'publish' AND `cart` = 'nocart' AND meta LIKE '%:\"subscription_id\";i:" . $subscription_id . ";%' AND meta LIKE '%:\"invoice_id\";i:" . $parent_invoice_id . ";%' ORDER BY id ASC" );

		if ( empty( $items ) ) {
			return;
		}

		// Downgrade each listing.
		foreach ( $items as $post_package ) {
			if ( empty( $post_package->post_id ) ) {
				continue;
			}

			$package_id = (int) geodir_get_post_meta( (int) $post_package->post_id, 'package_id', true );

			if ( ! ( ! empty( $package_id ) && ! empty( $post_package->package_id ) && $package_id == (int) $post_package->package_id ) ) {
				// Don't downgrade/expire if listing package is changed.
				continue;
			}

			if ( in_array( get_post_status( $post_package->post_id ), geodir_get_publish_statuses( array( 'post_type' => get_post_type( $post_package->post_id ) ) ) ) ) {
				geodir_save_post_meta( (int) $post_package->post_id, 'expire_date', $subscription->get_expiration() );

				if ( geodir_pricing_is_recurring( $package_id ) ) {
					update_post_meta( (int) $post_package->post_id, '_gdpm_cancel_at_period_end', true );
				}
			}
		}
	}

	/**
	 * This method is called whenever a subscription expires.
	 *
	 * @param WPInv_Subscription $subscription
	 */
	public static function handle_subscription_expired( $subscription, $transition = array() ) {
		return self::handle_subscription_cancelled( $subscription, $transition );
	}

	public static function increase_user_bundle( $user_id, $value ) {
		$bundle = self::get_user_bundle_value( $user_id );

		if ( empty( $bundle ) || ! is_array( $bundle ) ) {
			$bundle = array();
		}

		$prev_bundle = $bundle;

		foreach ( $value as $invoice_id => $_data ) {
			foreach ( $_data as $item_id => $data ) {
				foreach ( $data as $package_id => $count ) {
					if ( ! empty( $bundle[ $invoice_id ][ $package_id ] ) ) {
						$bundle[ $invoice_id ][ $item_id ][ $package_id ] = $bundle[ $invoice_id ][ $item_id ][ $package_id ] + $count;
					} else {
						$bundle[ $invoice_id ][ $item_id ][ $package_id ] = $count;
					}
				}
			}
		}

		$bundle = apply_filters( 'geodir_pricing_increase_user_bundle', $bundle, $user_id, $value, $prev_bundle );

		if ( ! empty( $bundle ) ) {
			update_user_meta( $user_id, '_gd_bundle_packages', $bundle );
		} else {
			delete_user_meta( $user_id, '_gd_bundle_packages' );
		}

		do_action( 'geodir_pricing_user_bundle_increased', $bundle, $user_id, $value, $prev_bundle );

		return $bundle;
	}

	public static function decrease_user_bundle( $user_id, $value, $invoice_id, $item_id ) {
		$bundle = self::get_user_bundle_value( $user_id );

		if ( empty( $bundle ) || ! is_array( $bundle ) ) {
			$bundle = array();
		}

		$prev_bundle = $bundle;

		foreach ( $value as $package_id => $count ) {
			if ( ! isset( $bundle[ $invoice_id ][ $item_id ][ $package_id ] ) ) {
				continue;
			}

			$bundle[ $invoice_id ][ $item_id ][ $package_id ] = (int) $bundle[ $invoice_id ][ $item_id ][ $package_id ] - (int) $count;

			if ( $bundle[ $invoice_id ][ $item_id ][ $package_id ] < 1 ) {
				unset( $bundle[ $invoice_id ][ $item_id ][ $package_id ] );
			}

			if ( empty( $bundle[ $invoice_id ][ $item_id ] ) ) {
				unset( $bundle[ $invoice_id ][ $item_id ] );
			}

			if ( empty( $bundle[ $invoice_id ] ) ) {
				unset( $bundle[ $invoice_id ] );
			}
		}

		$bundle = apply_filters( 'geodir_pricing_decrease_user_bundle', $bundle, $user_id, $value, $prev_bundle, $invoice_id, $item_id );

		if ( ! empty( $bundle ) ) {
			update_user_meta( $user_id, '_gd_bundle_packages', $bundle );
		} else {
			delete_user_meta( $user_id, '_gd_bundle_packages' );
		}

		do_action( 'geodir_pricing_user_bundle_decreased', $bundle, $user_id, $value, $prev_bundle, $invoice_id, $item_id );

		return true;
	}

	public static function display_user_bundle( $args, $user_id = 0 ) {
		$output = '';

		if ( ! self::is_getpaid_active() ) {
			return $output;
		}

		if ( empty( $user_id ) ) {
			$user_id = (int) get_current_user_id();
		}

		if ( empty( $user_id ) ) {
			return $output;
		}

		$wrap_class = geodir_build_aui_class( $args );

		if ( ! empty( $args['css_class'] ) ) {
			$wrap_class .= ' ' . geodir_sanitize_html_class( $args['css_class'] );
		}

		$bundle_items = self::get_user_bundle_details( $user_id );

		if ( ! empty( $bundle_items ) ) {
			$output .= '<div class="geodir-bundle-list-wrap' . ( $wrap_class ? ' ' . esc_attr( $wrap_class ) : '' ) . '">';
				$output .= '<ul class="list-group">';
					$output .= '<li class="list-group-item active">' . esc_html__( 'Bundle of Listings allowed to add for Free', 'geodir_pricing' ) . '</li>';

				foreach ( $bundle_items as $package_id => $data ) {
					$add_listing_button = '';

					if ( geodir_add_listing_check_post_type( $data['post_type'] ) && ( $add_listing_url = geodir_add_listing_page_url( $data['post_type'] ) ) ) {
						$add_listing_button = '<a href="' . esc_url( add_query_arg( array( 'package_id' => $package_id ), $add_listing_url ) ) . '" class="geodir-bundle-add-btn ms-2 ml-2">' . esc_html( wp_sprintf( _x( 'Add %1$s', 'bundle list add listing button', 'geodir_pricing' ), $data['post_type_singular_name'] ) ) . '</a>';
					}

					$output .= '<li class="list-group-item">' . $data['title'] . $add_listing_button . '</li>';
				}

				$output .= '</ul>';
			$output .= '</div>';
		}

		return $output;
	}

	public static function geodir_before_detail_fields() {
		global $gd_post, $geodir_label_type;

		if ( ! self::is_getpaid_active() ) {
			return;
		}

		$post_id = ! empty( $_REQUEST['pid'] ) ? absint( $_REQUEST['pid'] ) : 0;
		$req_package_id = ! empty( $_REQUEST['package_id'] ) ? absint( $_REQUEST['package_id'] ) : 0;
		$current_package_id	= $post_id ? (int) geodir_get_post_meta( $post_id, 'package_id', true ) : 0;

		if ( ! $req_package_id ) {
			$req_package_id = $current_package_id;
		}

		$count = self::get_user_bundle_count( $req_package_id );
		if ( empty( $count ) ) {
			return;
		}

		$current_post_type = geodir_get_current_posttype();
		$current_post_status = $post_id ? get_post_status( $post_id ) : '';

		if ( ( $post_id && $current_post_status && ! in_array( $current_post_status, geodir_get_post_stati( 'published', array( 'post_type' => $current_post_type ) ) ) && ! in_array( $current_post_status, array( 'trash', 'gd-closed', 'pending' ) ) ) || ( empty( $post_id ) && empty( $current_post_status ) ) || ( ! empty( $req_package_id ) && ! empty( $current_package_id ) && $req_package_id != $current_package_id ) ) {
			aui()->alert(
				array(
					'type'    => 'info',
					'content' => wp_sprintf( __( 'Bundle of Listings: You will not be charged for this package as you are allowed to add %s listings for free under this package.', 'geodir_pricing' ), '<b>' . $count . '</b>' ) . '<input type="hidden" name="geodir_is_bundle" value="1">'
				),
				true
			);

			$placeholder = __( 'Select Bundle Item&hellip;', 'geodir_pricing' );

			$bundle_invoice_options = self::package_bundle_invoice_options( $req_package_id );

			$extra_attributes = array();
			$extra_attributes['data-placeholder'] = $placeholder;
			$extra_attributes['option-ajaxchosen'] = 'false';
			$value = '';
			if ( ! empty( $bundle_invoice_options ) ) {
				$value = array_keys( $bundle_invoice_options )[0];
			}

			$bundles_output = aui()->select( array(
				'id' => 'geodir_bundle_invoice',
				'name' => 'geodir_bundle_invoice',
				'placeholder' => $placeholder,
				'value' => $value,
				'label_show' => true,
				'label_type' => ! empty( $geodir_label_type ) ? $geodir_label_type : 'horizontal',
				'label' => __( 'Bundle Item', 'geodir_pricing' ),
				'help_text' => __( 'Select purchased bundle item to skip payment for the listing. Leave blank to use default payment.', 'geodir_pricing' ),
				'extra_attributes' => $extra_attributes,
				'options' => $bundle_invoice_options,
				'select2' => true,
				'data-allow-clear' => true
			) );

			echo $bundles_output;
		}
	}

	public static function package_bundle_invoice_options( $req_package_id ) {
		$options = array();

		$bundle = self::get_user_bundle_value();

		if ( empty( $bundle ) || ! is_array( $bundle ) ) {
			return $options;
		}

		foreach ( $bundle as $invoice_id => $_data ) {
			$invoice = wpinv_get_invoice( $invoice_id );

			$invoice_prefix = '';

			if ( ! empty( $invoice ) && $invoice->has_status( 'publish' ) ) {
				$subscription_id = 0;

				if ( $invoice->is_recurring() ) {
					if ( $invoice->is_parent() ) {
						if ( $_subscription_id = $invoice->get_subscription_id() ) {
							$subscription = getpaid_get_subscription( $_subscription_id );

							if ( ! empty( $subscription ) && $subscription->is_active() ) {
								$invoice_prefix .= wp_sprintf( __( 'Subscription #%d', 'geodir_pricing' ), $_subscription_id ) . ' - ' . $invoice->get_number();
								$subscription_id = $_subscription_id;
							}
						} else {
							$invoice_prefix .= $invoice->get_number();
						}
					}
				} else {
					$invoice_prefix .= $invoice->get_number();
				}

				if ( $invoice_prefix ) {
					foreach ( $_data as $item_id => $data ) {
						if ( ! empty( $data[ $req_package_id ] ) ) {
							$item = wpinv_get_item( $item_id );

							if ( ! empty( $item ) ) {
								$invoice_prefix .= ' - ' . $item->get_name();
							} else {
								$invoice_prefix .= ' - ' . wp_sprintf( __( 'Item #%d', 'geodir_pricing' ), $item_id );
							}

							foreach ( $data as $package_id => $count ) {
								if ( $req_package_id == $package_id ) {
									$options[ $invoice_id .'_'. $item_id . '_' . $package_id . '_' . $subscription_id ] = $invoice_prefix . ' ' . wp_sprintf( $count > 1 ? __( '(%d remaining listings)', 'geodir_pricing' ) : __( '(1 remaining listing)', 'geodir_pricing' ), $count );
								}
							}
						}
					}
				}
			}
		}

		return $options;
	}

	public static function pricing_getpaid_pre_invoice_save( $return, $post_data, $task, $has_invoice ) {
		global $geodir_pricing_manager;

		if ( empty( $post_data['geodir_is_bundle'] ) || empty( $post_data['geodir_bundle_invoice'] ) || ! self::is_getpaid_active() ) {
			return $return;
		}

		$user_id = (int) get_current_user_id();

		if ( empty( $user_id ) ) {
			return $return;
		}

		$package_id = ! empty( $post_data['package_id'] ) ? absint( $post_data['package_id'] ) : 0;

		if ( empty( $package_id ) ) {
			return $return;
		}

		$bundle_invoice = ! empty( $post_data['geodir_bundle_invoice'] ) ?  explode( "_", sanitize_text_field( $post_data['geodir_bundle_invoice'] ) ) : array();
		$invoice_id = ! empty( $bundle_invoice[0] ) ? absint( $bundle_invoice[0] ) : 0;
		$item_id = ! empty( $bundle_invoice[1] ) ? absint( $bundle_invoice[1] ) : 0;
		$_package_id = ! empty( $bundle_invoice[2] ) ? absint( $bundle_invoice[2] ) : 0;
		$subscription_id = ! empty( $bundle_invoice[3] ) ? absint( $bundle_invoice[3] ) : 0;

		if ( empty( $invoice_id ) || empty( $item_id ) || empty( $_package_id ) ) {
			return $return;
		}

		$user_id = !empty($post_data['post_author']) ? (int)$post_data['post_author'] : $user_id;
		$count = self::get_user_bundle_count( $package_id, $user_id, $invoice_id, $item_id );

		if ( $count > 0 ) {
			$post_id = ! empty( $post_data['post_parent'] ) ? $post_data['post_parent'] : $post_data['ID'];

			update_post_meta( $post_id, '_gd_bundle_packages', array( 'user_id' => $user_id, 'package_id' => $package_id, 'invoice_id' => $invoice_id, 'item_id' => $item_id, 'subscription_id' => $subscription_id ) );
			update_post_meta( $post_id, '_gd_bundle_decrease', 1 );

			$data = array(
				'post_id' => $post_id,
				'package_id' => $package_id,
				'product_id' => 0,
				'invoice_id' => 0,
				'task' => $task,
				'cart' => 'nocart',
				'status' => 'pending',
				'meta' => maybe_serialize( array( 'task' => $task, 'user_id' => $user_id, 'package_id' => $package_id, 'invoice_id' => $invoice_id, 'item_id' => $item_id, 'subscription_id' => $subscription_id ) ),
				'date' => date_i18n( 'Y-m-d H:i:s' )
			);

			GeoDir_Pricing_Post_Package::save( $data );

			return $geodir_pricing_manager->cart->post_without_invoice( $post_data );
		}
	}

	public static function pricing_getpaid_pre_claim_invoice_save( $return, $post_id, $package_id, $user_id, $payment_id = 0 ) {
		global $geodir_pricing_manager;

		if ( ! geodir_get_option( 'pm_claim_by_bundle', 1 ) || ! self::is_getpaid_active() || empty( $post_id ) || empty( $package_id ) || empty( $user_id ) ) {
			return $return;
		}

		$bundle_invoice_options = GeoDir_Pricing_Bundle::package_bundle_invoice_options( $package_id );

		if ( empty( $bundle_invoice_options ) ) {
			return $return;
		}

		$bundle_invoice = array_keys( $bundle_invoice_options )[0];
		$_bundle_invoice = explode( "_", $bundle_invoice );

		$invoice_id = ! empty( $_bundle_invoice[0] ) ? absint( $_bundle_invoice[0] ) : 0;
		$item_id = ! empty( $_bundle_invoice[1] ) ? absint( $_bundle_invoice[1] ) : 0;
		$_package_id = ! empty( $_bundle_invoice[2] ) ? absint( $_bundle_invoice[2] ) : 0;
		$subscription_id = ! empty( $_bundle_invoice[3] ) ? absint( $_bundle_invoice[3] ) : 0;

		if ( empty( $invoice_id ) || empty( $item_id ) || empty( $_package_id ) || $_package_id != $package_id ) {
			return $return;
		}

		$count = (int) self::get_user_bundle_count( $package_id, $user_id, $invoice_id, $item_id );

		if ( $count < 1 ) {
			return $return;
		}

		update_post_meta( $post_id, '_gd_bundle_packages', array( 'user_id' => $user_id, 'package_id' => $package_id, 'invoice_id' => $invoice_id, 'item_id' => $item_id, 'subscription_id' => $subscription_id ) );
		update_post_meta( $post_id, '_gd_bundle_decrease', 1 );

		$meta = array(
			'bundle_invoice' => $bundle_invoice,
			'user_id' => $user_id,
			'package_id' => $package_id,
			'invoice_id' => $invoice_id,
			'item_id' => $item_id,
			'subscription_id' => $subscription_id
		);

		return $geodir_pricing_manager->cart->claim_without_invoice( $post_id, $package_id, $user_id, $payment_id, $meta );
	}

	public static function pricing_complete_package_post_updated( $post_id, $package_id, $post_package_id, $revision_id, $price_package ) {
		if ( ! self::is_getpaid_active() ) {
			return;
		}

		if ( ! empty( $price_package ) && $price_package->cart == 'nocart' && get_post_meta( $post_id, '_gd_bundle_decrease', true ) && ( $bundle_packages = get_post_meta( $post_id, '_gd_bundle_packages', true ) ) ) {
			if ( is_array( $bundle_packages ) && ! empty( $bundle_packages['package_id'] ) && ! empty( $bundle_packages['invoice_id'] ) && ! empty( $bundle_packages['item_id'] ) && isset( $bundle_packages['subscription_id'] ) && ! empty( $bundle_packages['user_id'] ) && $bundle_packages['package_id'] == $package_id ) {
				self::decrease_user_bundle( $bundle_packages['user_id'], array( $bundle_packages['package_id'] => 1 ), $bundle_packages['invoice_id'], $bundle_packages['item_id'] );

				delete_post_meta( $post_id, '_gd_bundle_decrease' );

				if ( ! empty( $bundle_packages['subscription_id'] ) ) {
					geodir_save_post_meta( (int) $post_id, 'expire_date', '' );
				} else {
					if ( geodir_pricing_is_recurring( $package_id ) ) {
						$expire_date = geodir_pricing_new_expire_date( geodir_pricing_package_alive_days( $package_id ) );

						geodir_save_post_meta( (int) $post_id, 'expire_date', $expire_date );
						update_post_meta( (int) $post_id, '_gdpm_cancel_at_period_end', true );
					}
				}
			}
		}
	}

	public static function add_admin_footer_script() {
?>
<script type="text/javascript">
jQuery(function(){
	jQuery('select[name="wpinv_item_type"]').on('change', function(){
		geodirPricingHandleBundle();
	});
	jQuery('select[name="wpinv_item_type"]').trigger('change');
	function geodirPricingHandleBundle() {
		var type = jQuery('select[name="wpinv_item_type"]').val();
		if ( type == 'bundle_packages' ) {
			jQuery('.geodir-bundle-items').removeClass('d-none');
			jQuery('.geodir-bundle-msg').addClass('d-none');
		} else {
			jQuery('.geodir-bundle-items').addClass('d-none');
			jQuery('.geodir-bundle-msg').removeClass('d-none');
		}
	}
});
</script>
<?php
	}
}
