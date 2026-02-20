<?php
/**
 * Advertising & WooCommerce integration class.
 *
 * @since 1.0.0
 * @package Advertising
 * @author AyeCode Ltd
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Adv_Cart_WooCommerce class.
 */
class Adv_Cart_WooCommerce extends Adv_Cart {

	public function __construct() {

		//Register hooks if woocommerce is the payment cart and the woocommerce plugin is installed
		if( 'woocommerce' == adv_get_cart() && class_exists( 'WooCommerce' ) ) {
			add_action( 'woocommerce_payment_complete', array( __CLASS__, 'order_paid' ) );
			add_action( 'woocommerce_order_refunded', array( __CLASS__, 'order_refunded' ) );
			add_action( 'adv_save_' . adv_zone_post_type(), array( __CLASS__, 'maybe_create_product' ) );
			add_action( 'adv_save_' . adv_ad_post_type(), array( __CLASS__, 'maybe_create_order' ), 10, 3 );
			add_action( 'adv_ad_status_td', array( __CLASS__, 'show_payment_status' ), 10, 2 );
			add_action( 'adv_ad_saved_successfully', array( __CLASS__, 'show_payment_action' ) );

			add_filter( 'adv_get_currency', 'get_woocommerce_currency' );
			add_filter( 'adv_currency_sign', 'get_woocommerce_currency_symbol' );
			add_filter( 'adv_currency_position', array( $this, 'currency_position' ) );
			add_filter( 'adv_thousand_separator', 'wc_get_price_thousand_separator' );
			add_filter( 'adv_decimal_separator', 'wc_get_price_decimal_separator' );
			add_filter( 'adv_price_decimals', 'wc_get_price_decimals' );
		}

		parent::__construct();
	}

	//Prompts the user to pay for the ad in case they havent
	public static function show_payment_status( $ad_id, $is_active ) {

		$order = adv_ad_get_meta( $ad_id, 'woocommerce_order_id' );

		//Abort if the ad is active or has no order associated with it
		if( $is_active || !$order ) {
			return;
		}

		//Does the associated order exist...
		if(! $order = wc_get_order( $order ) ) {
			return;
		}

		//...and is it payable?
		if( ! $order->needs_payment() ) {
			if ( 1 == get_post_meta( $ad_id, 'adv_repay', true ) ) {
				echo '<em>' . __('Expired', 'advertising') . '</em>';
			} else {
				echo '<em>' . __('Pending admin approval', 'advertising') . '</em>';
			}
			return;
		}

		//...ask the user to pay for it
		$url = esc_url( $order->get_checkout_payment_url() );
		printf( 
			__('%sPay for ad%s', 'advertising'),
			"<a href='$url'>",
			'</a>'
		);
	}

	//Prompts the user to pay for the ad in case they havent
	public static function show_payment_action( $ad_id ) {
		global $adv_woocommerce_saved_ad;
	
		if( empty( $ad_id) && empty( $adv_woocommerce_saved_ad ) ) {
			return;
		}
	
		if( empty( $ad_id ) ) {
			$ad_id = $adv_woocommerce_saved_ad;
		}

		$order = adv_ad_get_meta( $ad_id, 'woocommerce_order_id' );
	
		//Does the associated order exist...
		if(! $order = wc_get_order( $order ) ) {
			return;
		}
	
		//...and is it payable?
		if( ! $order->needs_payment() ) {
			if ( 1 == get_post_meta( $ad_id, 'adv_repay', true ) ) {
				echo '<em>' . __('Expired', 'advertising') . '</em>';
			} else {
				echo '<em>' . __('Pending admin approval', 'advertising') . '</em>';
			}
			return;
		}
	
		//...ask the user to pay for it
		$url = esc_url( $order->get_checkout_payment_url() );
		printf( 
			__('%sPay for ad%s', 'advertising'),
			"<div class='woocommerce'><a class='button alt' href='$url'>",
			'</a></div>'
		);

	}

	//Retrieves the product for a zone
	public static function get_product( $zone ) {
		$product = get_post_meta( $zone, '_wc_product', true );
		if( $product ) {
			return wc_get_product( $product );
		}

		return false;
	}


	//Creates a product and associates it with a zone
	public static function maybe_create_product( $zone ) {

		if(!$zone) {
			return;
		}

		//If there is an associated product update it
		$product = self::get_product( $zone );

		$args = array(
			'post_title' => get_the_title( $zone ),
			'post_type'  => 'product',
			'post_status' => 'publish',
		);

		//Create or update the product
		if( $product ) {
			$args['ID'] = $product->get_id();
			$post_id = wp_update_post($args);
		} else {
			$post_id = wp_insert_post($args);
		}

		//Update post meta
		$note  = sprintf(
			esc_html__( "Advertisement Zone:'%s'"),
			get_the_title( $zone )
		);
		$price = adv_zone_get_meta( $zone, 'price', true, '0.00' );

		wp_set_object_terms( $post_id, 'simple', 'product_type' );
		update_post_meta( $post_id, '_visibility', 'exclude-from-catalog' );
		update_post_meta( $post_id, '_stock_status', 'instock');
		update_post_meta( $post_id, '_downloadable', 'no' );
		update_post_meta( $post_id, '_virtual', 'yes' );
		update_post_meta( $post_id, '_regular_price', $price );
		update_post_meta( $post_id, '_sale_price', '' );
		update_post_meta( $post_id, '_purchase_note', $note );
		update_post_meta( $post_id, '_sku', $zone );
		update_post_meta( $post_id, '_price', $price );
		update_post_meta( $post_id, '_ad_zone', $zone );
		update_post_meta( $zone, '_wc_product', $post_id );
	
		return;
	}

	//Creates an order and associates it with an ad
	public static function maybe_create_order( $ad, $zone = 0, $redirect = false ) {
		global $adv_woocommerce_saved_ad;

		//Abort early if there is already an order
		$order = adv_ad_get_meta( $ad, 'woocommerce_order_id' );
		if( $order && $order = wc_get_order( $order ) ){
			return;
		}
		
		//We use the zone to get a product for the ad
		if( empty( $zone ) ) {
			$zone = adv_ad_zone( $ad );
		}

		//attempt to create a product for the zone if non exists
		if (! $product = self::get_product( $zone ) ) {

			//create the product...
			self::maybe_create_product( $zone );

			//...and abort if we can't
			if(!  $product = self::get_product( $zone ) ){
				return;
			}
		}

		//Create the order
		$order = wc_create_order(array(
			'customer_id'   => adv_ad_advertiser( $ad ),
			'customer_note' => sprintf(
				esc_html__( "Payment for the Ad:'%s' in the zone '%s'"),
				get_the_title( $ad ),
				get_the_title( $zone )
			),
		));

		$order->add_product( $product );
		$order->calculate_totals();
		update_post_meta( $ad, '_adv_ad_woocommerce_order_id', $order->get_id() );
		$adv_woocommerce_saved_ad = $ad;

		if ( $redirect ) {
			wp_redirect( $order->get_checkout_payment_url() );
			exit;
		}

		return;
		
	}

	//Publishes an ad as soon as an order is paid
	public static function order_paid( $order_id ) {

		//Retrieve the ad...
		if( ! $ad = adv_get_product_ad( $order_id, 'woocommerce_order_id' ) ) {
			return;
		}

		//...then publish it
		$paid_status = adv_get_option( 'paid_ads_status' );
		$paid_status = empty( $paid_status ) ? 'publish' : trim( $paid_status );

		if ( 'code' == adv_ad_type( $ad ) ) {
			$paid_status = 'pending';
		}

		$args        = array(
			'post_status' => $paid_status,
			'ID'		  => $ad,
		);
		wp_update_post( $args );

		//Update the payment date for use with the cpt payment type
		update_post_meta( $ad, '_adv_ad_date_paid', time() );
		
	}
	
	//Unpublish a refunded order
	public static function order_refunded( $order_id ) {
		
		//Retrieve the ad...
		if( ! $ad = adv_get_product_ad( $order_id, 'woocommerce_order_id' ) ) {
			return;
		}

		//...then unpublish it
		adv_unpublish_ad( $ad );
	}

	/**
	 * Filters the currency position of the store
	 * 
	 * @param string $currency
	 */
	public function currency_position( $currency = '' ) {
		return get_option( 'woocommerce_currency_pos' );
	}

}