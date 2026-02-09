<?php
/**
 * GeoDirectory Marketplace WooCommerce class
 *
 * @package GeoDir_Marketplace
 * @author AyeCode Ltd
 * @since 2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * GeoDir_Marketplace_WooCommerce class.
 */
class GeoDir_Marketplace_WooCommerce {
	/**
	 * Initialize
	 *
	 * @since 2.0
	 */
	public static function init() {
		if ( is_admin() ) {
			add_filter( 'woocommerce_product_data_tabs', array( __CLASS__ , 'wc_product_data_tabs' ), 1000, 1 );
			add_filter( 'geodir_cpt_settings_tabs_custom_fields', array( __CLASS__ , 'gd_predefined_tab' ), 20, 2 );

			add_action( 'woocommerce_product_data_panels', array( __CLASS__ , 'wc_product_data_panels' ), 1000 );
		} else {
			if ( geodir_get_option( 'mp_link_post' ) && is_user_logged_in() ) {
				// Dokan
				add_action( 'dokan_product_edit_after_main', array( __CLASS__, 'dokan_product_edit_after_main' ), 100, 2 );

				// WCFM - WooCommerce Frontend Manager
				add_action( 'after_wcfm_products_manage_tabs_content', array( __CLASS__, 'wcfm_products_manage_tabs_content' ), 100, 4 );

				if ( class_exists( 'MVX' ) ) {
					// MultiVendorX
					add_filter( 'mvx_product_data_tabs', array( __CLASS__ , 'wc_product_data_tabs' ), 1000, 1 );
					add_action( 'mvx_product_tabs_content', array( __CLASS__, 'mvx_product_tabs_content' ), 100, 3 );
				} else {
					// WC Marketplace
					add_filter( 'wcmp_product_data_tabs', array( __CLASS__ , 'wc_product_data_tabs' ), 1000, 1 );
					add_action( 'wcmp_product_tabs_content', array( __CLASS__, 'wcmp_product_tabs_content' ), 100, 3 );
				}

				// WC Vendors Marketplace
				add_filter( 'wcv_product_meta_tabs', array( __CLASS__ , 'wc_product_data_tabs' ), 1000, 1 );
				add_action( 'wcv-after_seo_tab', array( __CLASS__, 'wcv_after_seo_tab' ), 100, 1 );
			}
		}

		// WCFM - WooCommerce Frontend Manager
		if ( geodir_get_option( 'mp_link_post' ) && is_user_logged_in() ) {
			add_action( 'after_wcfm_products_manage_meta_save', array( __CLASS__, 'wcfm_products_manage_meta_save' ), 500, 2 );
		}

		add_filter( 'woocommerce_shortcode_products_query', array( __CLASS__ , 'wc_shortcode_products_query' ), 10, 3 );

		add_action( 'woocommerce_shortcode_gd_marketplace_loop_no_results', array( __CLASS__ , 'wc_loop_no_results' ), 10, 1 );
		add_action( 'woocommerce_after_product_object_save', array( __CLASS__ , 'wc_after_product_object_save' ), 20, 2 );
	}

	public static function wc_catalog_orderby_options() {
		$catalog_orderby_options = apply_filters(
			'woocommerce_catalog_orderby',
			array(
				'menu_order' => __( 'Default sorting (custom ordering - name)', 'geomarketplace' ),
				'popularity' => __( 'Sort by popularity (sales)', 'geomarketplace' ),
				'rating' => __( 'Sort by average rating', 'geomarketplace' ),
				'date' => __( 'Sort by latest', 'geomarketplace' ),
				'price' => __( 'Sort by price: low to high', 'geomarketplace' ),
				'price-desc' => __( 'Sort by price: high to low', 'geomarketplace' ),
			)
		);

		return $catalog_orderby_options;
	}

	public static function wc_default_catalog_columns() {
		return ( function_exists( 'wc_get_default_products_per_row' ) ? wc_get_default_products_per_row() : 4 );
	}

	public static function wc_default_catalog_orderby() {
		return apply_filters( 'woocommerce_default_catalog_orderby', get_option( 'woocommerce_default_catalog_orderby', 'menu_order' ) );
	}

	public static function wc_default_shop_per_page() {
		if ( ! function_exists( 'wc_get_default_product_rows_per_page' ) ) {
			return 12;
		}

		return apply_filters( 'loop_shop_per_page', self::wc_default_catalog_columns() * wc_get_default_product_rows_per_page() );
	}

	/**
	 * Adds a predefined tab.
	 * 
	 * @since 2.0
	 *
	 * @param $fields 
	 * @param $post_type
	 *
	 * @return array
	 */
	public static function gd_predefined_tab( $fields, $post_type ) {
		$post_types = geodir_marketplace_post_types();

		if ( ! empty( $post_types ) && in_array( $post_type, $post_types ) ) {
			$fields[] = array(
				'tab_type' => 'shortcode',
				'tab_name' => __( 'Shop', 'geomarketplace' ),
				'tab_icon' => 'fas fa-shopping-cart',
				'tab_key' => 'gdmp-shop',
				'tab_content'=> '[gd_marketplace orderby="' . esc_attr( self::wc_default_catalog_orderby() ) . '" per_page="' . (int) self::wc_default_shop_per_page() . '" columns="' . (int) self::wc_default_catalog_columns() . '" paginate="1"]'
			);
		}

		return $fields;
	}

	public static function wc_after_product_object_save( $product, $data_store ) {
		self::save_marketplace_data( (int) $product->get_id() );
	}

	public static function save_marketplace_data( $product_id, $request = array() ) {
		global $geodir_marketplace_save;

		if ( empty( $request ) ) {
			$request = $_POST;
		}

		if ( empty( $geodir_marketplace_save ) ) {
			$geodir_marketplace_save = array();
		}

		if ( ! empty( $product_id ) && ! empty( $request['gdmp_product_id'] ) ) {
			if ( (int) $product_id != (int) $request['gdmp_product_id'] ) {
				return false;
			}

			// Prevent save in loop.
			if ( in_array( (int) $product_id,  $geodir_marketplace_save ) ) {
				return false;
			}

			$geodir_marketplace_save[] = (int) $product_id;
		}

		if ( ! self::allowed_to_save( $request ) ) {
			return false;
		}

		$save_keys = array(
			'gdmp_vendor_id' => '_geomp_vendor_id',
			'gdmp_post_id' => '_geomp_gd_listing_id',
		);

		$data = array();
		foreach ( $save_keys as $var => $key ) {
			if ( isset( $request[ $var ] ) ) {
				$data[ $key ] = absint( $request[ $var ] );
			}
		}

		if ( ! empty( $data ) ) {
			self::save_product_meta( (int) $product_id, $data );
		}
	}

	public static function allowed_to_save( $request = array() ) {
		if ( empty( $request ) ) {
			$request = $_POST;
		}

		if ( ! ( isset( $request['gdmp_post_id'] ) && is_user_logged_in() ) ) {
			return false;
		}

		if ( wc_current_user_has_role( 'administrator' ) ) {
			return true;
		}

		$nonce = ! empty( $request['gdmp_nonce'] ) ? sanitize_text_field( $request['gdmp_nonce'] ) : '';

		if ( wp_verify_nonce( $nonce, 'geodir_marketplace_meta_admin' ) ) {
			return true;
		}

		if ( geodir_get_option( 'mp_link_post' ) && wp_verify_nonce( $nonce, 'geodir_marketplace_meta' ) && geodir_listing_belong_to_current_user( absint( $request['gdmp_post_id'] ) ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Save linked listing data.
	 *
	 * @since 2.0
	 *
	 * @param int $post_id WP post id.
	 * @param array $data Data to save.
	 */
	public static function save_product_meta( $post_id, $data = array() ) {
		if ( ! empty( $post_id ) && ! empty( $data ) ) {
			foreach ( $data as $key => $value ) {
				update_post_meta( $post_id, $key, $value );
			}
		}
	}

	public static function wcfm_products_manage_meta_save( $product_id, $data ) {
		$is_marketplace = wcfm_is_marketplace();

		if ( $is_marketplace == 'wcfmmarketplace' ) {
			self::save_marketplace_data( $product_id, $data );
		}
	}

	public static function wc_product_data_tabs( $tabs ) {
		$tabs['geodir-listing'] = array(
			'label' => __( 'GeoDirectory', 'geomarketplace' ),
			'target' => 'geodir_marketplace_tab',
			'class' => array(),
			'priority' => 9999,
		);

		return $tabs;
	}

	public static function wc_product_data_panels() {
		global $post;

		$product_id = ! empty( $post->ID ) ? (int) $post->ID : 0;
		$vendor_id = (int) get_post_meta( (int) $product_id, '_geomp_vendor_id', true );
		$gd_post_id = (int) get_post_meta( (int) $product_id, '_geomp_gd_listing_id', true );

		if ( wc_current_user_has_role( 'administrator' ) ) {
			$vendor_options = self::get_vendor_options();
		} else {
			$vendor_options = array();
			$vendor_id = $vendor_id > 0 ? $vendor_id : (int) get_current_user_id();
		}

		$vendor_post_options = self::get_user_posts( $vendor_id );

		$template_args = array(
			'gd_post_id' => $gd_post_id,
			'product_id' => $product_id,
			'vendor_id' => $vendor_id,
			'vendor_options' => $vendor_options,
			'vendor_post_options' => $vendor_post_options,
			'extra_args' => array()
		);

		$script = geodir_get_template_html( 'marketplace/script.php', $template_args, '', GEODIR_MARKETPLACE_PLUGIN_DIR . 'templates' );

		/** Contains template to show product panel.
		 *
		 * @since 2.0
		 */
		include_once( dirname( __FILE__ ) . '/admin/views/html-product-panel.php' );
	}

	/**
	 * Dokan
	 */
	public static function dokan_product_edit_after_main( $product, $product_id ) {
		$args = array(
			'provider' => 'dokan',
			'product' => $product
		);

		self::show_vendor_product_tab( $product_id, $args );
	}

	/**
	 * MultiVendorX
	 */
	public static function mvx_product_tabs_content( $self = array(), $product_object = array(), $post = array() ) {
		$args = array(
			'provider' => 'mvx',
			'product' => $product_object
		);

		self::show_vendor_product_tab( (int) $post->ID, $args );
	}

	/**
	 * WC Marketplace
	 */
	public static function wcmp_product_tabs_content( $self = array(), $product_object = array(), $post = array() ) {
		$args = array(
			'provider' => 'wcmp',
			'product' => $product_object
		);

		self::show_vendor_product_tab( (int) $post->ID, $args );
	}

	/**
	 * WCFM - WooCommerce Frontend Manager
	 */
	public static function wcfm_products_manage_tabs_content( $product_id, $product_type = '', $wcfm_is_translated_product = false, $wcfm_wpml_edit_disable_element = false ) {
		$args = array(
			'provider' => 'wcfm',
			'product_type' => $product_type,
			'wcfm_is_translated_product' => $wcfm_is_translated_product,
			'wcfm_wpml_edit_disable_element' => $wcfm_wpml_edit_disable_element
		);

		self::show_vendor_product_tab( (int) $product_id, $args );
	}

	/**
	 * WC Vendors Marketplace
	 */
	public static function wcv_after_seo_tab( $product_id ) {
		$args = array(
			'provider' => 'wcv'
		);

		self::show_vendor_product_tab( (int) $product_id, $args );
	}

	public static function show_vendor_product_tab( $product_id, $args = array() ) {
		$vendor_id = (int) get_post_meta( (int) $product_id, '_geomp_vendor_id', true );
		$gd_post_id = (int) get_post_meta( (int) $product_id, '_geomp_gd_listing_id', true );

		if ( wc_current_user_has_role( 'administrator' ) ) {
			$vendor_options = self::get_vendor_options();
		} else {
			$vendor_options = array();
			$vendor_id = $vendor_id > 0 ? $vendor_id : (int) get_current_user_id();
		}

		$vendor_post_options = self::get_user_posts( $vendor_id );

		$provider = ! empty( $args['provider'] ) ? $args['provider'] : 'default';
		$template = 'marketplace/' . $provider . '.php';

		$template_args = array(
			'gd_post_id' => $gd_post_id,
			'product_id' => $product_id,
			'vendor_id' => $vendor_id,
			'vendor_options' => $vendor_options,
			'vendor_post_options' => $vendor_post_options,
			'extra_args' => $args
		);

		$template_args['script'] = geodir_get_template_html( 'marketplace/script.php', $template_args, '', GEODIR_MARKETPLACE_PLUGIN_DIR . 'templates' );

		geodir_get_template( $template, $template_args, '', GEODIR_MARKETPLACE_PLUGIN_DIR . 'templates' );
	}

	public static function get_user_posts( $user_id = 0, $post_types = '' ) {
		if ( empty( $post_types ) ) {
			$post_types = geodir_marketplace_post_types();
		} else {
			$post_types = geodir_marketplace_parse_post_types( $post_types );
		}

		$args = array(
			'no_found_rows' => true,
			'posts_per_page' => 1000,
			'post_type' => $post_types,
			'author' => $user_id,
			'post_status' => geodir_get_post_stati( 'public', array( 'post_type' => $post_types[0] ) ),
			'orderby' => 'title',
			'order' => 'ASC',
		);

		$args = apply_filters( 'geodir_marketplace_user_posts_query_args', $args, $post_types );

		$query_posts = new WP_Query( $args );
		$posts = $query_posts->get_posts();

		$options = array(
			'' => __( 'Select Listing...', 'geomarketplace' )
		);

		foreach ( $posts as $post ) {
			$options[ $post->ID ] = wp_sprintf(
				/* translators: 1: page name 2: page ID */
				__( '%1$s (ID: %2$s)', 'geomarketplace' ),
				get_the_title( $post ),
				$post->ID
			);
		}

		return $options;
	}

	/**
	 * Handle AJAX request request to get post options.
	 *
	 * @since 2.0
	 */
	public static function handle_ajax_post_options() {
		$post_id = ! empty( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
		$vendor_id = ! empty( $_POST['vendor_id'] ) ? absint( $_POST['vendor_id'] ) : 0;

		$user_posts = self::get_user_posts( $vendor_id );

		$options = '';
		foreach ( $user_posts as $value => $label ) {
			$options .= '<option value="' . absint( $value ) . '" ' . selected( $value && (int) $value === $post_id, true, false ) . '>' . esc_attr( $label ) . '</option>';
		}

		wp_send_json_success( array( 'options' => $options ) );
	}

	public static function wc_shortcode_products_query( $query_args, $attributes, $type ) {
		global $gd_post;

		if ( $type == 'gd_marketplace' ) {
			$query_args['meta_query'][] = array(
				'key' => '_geomp_gd_listing_id',
				'value' => absint( $gd_post->ID ),
				'compare' => '=',
			);
		}

		return $query_args;
	}

	public static function wc_loop_view_all_link( $attributes = array() ) {
		global $gd_post;

		if ( empty( $gd_post->post_author ) ) {
			return;
		}

		$name_url = self::get_store_name_url( (int) $gd_post->post_author );

		if ( empty( $name_url['name'] ) || empty( $name_url['url'] ) ) {
			return;
		}

		echo '<div class="geodir-widget-bottom text-center my-3"><a href="' . esc_url( $name_url['url'] ) . '" class="geodir-all-link btn btn-sm btn-outline-primary">' . wp_sprintf( __( 'View all products from %s', 'geomarketplace' ), $name_url['name'] ) . '</a></div>';
	}

	public static function get_store_name_url( $vendor_id ) {
		$data = array(
			'name' => '',
			'url' => ''
		);

		if ( class_exists( 'WeDevs_Dokan' ) ) {
			// Dokan
			$vendor = dokan()->vendor->get( (int) $vendor_id );

			if ( empty( $vendor ) ) {
				return $data;
			}

			$data['name'] = esc_html( $vendor->get_shop_name() );

			if ( empty( $data['name'] ) ) {
				$data['name'] = esc_html( $vendor->get_name() );
			}

			$data['url'] = $vendor->get_shop_url();
		} else if ( class_exists( 'WCFM' ) ) {
			global $WCFM;

			// WCFM - WooCommerce Frontend Manager
			$data['name'] = esc_html( wcfm_get_vendor_store_name( (int) $vendor_id ) );
			$data['url'] = wcfmmp_get_store_url( (int) $vendor_id );
		} else if ( class_exists( 'MVX' ) ) {
			// MultiVendorX
			$vendor = get_mvx_vendor( (int) $vendor_id );

			if ( empty( $vendor ) ) {
				return $data;
			}

			$data['name'] = esc_html( $vendor->page_title );

			if ( empty( $data['name'] ) && ! empty( $vendor->term_id ) && ( $vendor_term = get_term( $vendor->term_id ) ) ) {
				$data['name'] = esc_html( $vendor_term->name );
			}

			$data['url'] = $vendor->permalink;
		} else if ( class_exists( 'WCMp' ) ) {
			// WC Marketplace
			$vendor = get_wcmp_vendor( (int) $vendor_id );

			if ( empty( $vendor ) ) {
				return $data;
			}

			$data['name'] = esc_html( $vendor->page_title );

			if ( empty( $data['name'] ) && ! empty( $vendor->term_id ) && ( $vendor_term = get_term( $vendor->term_id ) ) ) {
				$data['name'] = esc_html( $vendor_term->name );
			}

			$data['url'] = $vendor->permalink;
		} else if ( class_exists( 'WC_Vendors' ) && class_exists( 'WCVendors_Pro' ) ) {
			// WC Vendors
			$data['name'] = WCV_Vendors::get_vendor_shop_name( (int) $vendor_id );
			$data['url'] = WCV_Vendors::get_vendor_shop_page( (int) $vendor_id );
		}

		return $data;
	}

	public static function wc_loop_no_results() {
		echo '<div class="gdmp-no-results"></div>';
	}

	public static function get_vendor_options() {
		$vendor_options = array(
			'' => __( 'Select Vendor...', 'geomarketplace' )
		);

		if ( class_exists( 'WeDevs_Dokan' ) ) {
			// Dokan
			$vendors = dokan()->vendor->get_vendors( array( 'number' => -1 ) );

			if ( ! empty( $vendors ) ) {
				foreach ( $vendors as $key => $vendor ) {
					$name = esc_html( $vendor->get_shop_name() );

					if ( empty( $name ) ) {
						$name = esc_html( $vendor->get_name() );
					}

					$vendor_options[ (int) $vendor->get_id() ] = wp_sprintf( __( '%s ( ID: %d )', 'geomarketplace' ), $name, (int) $vendor->get_id() );
				}
			}
		} else if ( class_exists( 'WCFM' ) ) {
			global $WCFM;

			// WCFM - WooCommerce Frontend Manager
			$vendors = $WCFM->wcfm_vendor_support->wcfm_get_vendor_list( true, '', '', '', '', false );

			if ( ! empty( $vendors ) ) {
				foreach ( $vendors as $vendor_id => $name ) {
					if ( ! empty( $vendor_id ) ) {
						$vendor_options[ (int) $vendor_id ] = wp_sprintf( __( '%s ( ID: %d )', 'geomarketplace' ), wcfm_get_vendor_store_name( (int) $vendor_id ), (int) $vendor_id );
					}
				}
			}
		} else if ( class_exists( 'MVX' ) ) {
			// MultiVendorX
			$vendors = get_mvx_vendors();

			if ( ! empty( $vendors ) ) {
				foreach ( $vendors as $key => $vendor ) {
					if ( ! empty( $vendor->user_data->user_email ) ) {
						$vendor_term = get_term( $vendor->term_id );

						$vendor_options[ $vendor->user_data->ID ] = wp_sprintf( __( '%s ( ID: %d )', 'geomarketplace' ), $vendor_term->name, (int) $vendor->user_data->ID );
					}
				}
			}
		} else if ( class_exists( 'WCMp' ) ) {
			// WC Marketplace
			$vendors = get_wcmp_vendors();

			if ( ! empty( $vendors ) ) {
				foreach ( $vendors as $key => $vendor ) {
					if ( ! empty( $vendor->user_data->user_email ) ) {
						$vendor_term = get_term( $vendor->term_id );

						$vendor_options[ $vendor->user_data->ID ] = wp_sprintf( __( '%s ( ID: %d )', 'geomarketplace' ), $vendor_term->name, (int) $vendor->user_data->ID );
					}
				}
			}
		} else if ( class_exists( 'WC_Vendors' ) && class_exists( 'WCVendors_Pro' ) ) {
			$vendors = get_users( array( 'role' => 'vendor' ) );

			if ( ! empty( $vendors ) ) {
				foreach ( $vendors as $key => $vendor ) {
					if ( ! empty( $vendor->data->user_email ) ) {
						$vendor_options[ $vendor->data->ID ] = wp_sprintf( __( '%s ( ID: %d )', 'geomarketplace' ), WCV_Vendors::get_vendor_shop_name( (int) $vendor->data->ID ), (int) $vendor->data->ID );
					}
				}
			}
		}

		return $vendor_options;
	}
}

new GeoDir_Marketplace_WooCommerce();