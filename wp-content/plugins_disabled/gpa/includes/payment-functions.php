<?php
/**
 * Advertising Payment Functions.
 *
 * @since 1.0.0
 * @package Advertising
 * @author AyeCode Ltd
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function adv_get_cart_options() {
	$cart_options = array();


	// Invoicing
	if ( defined( 'WPINV_VERSION' ) && version_compare( WPINV_VERSION, '2.0.0', '>=' ) ) {
		$cart_options['invoicing'] = __( 'GetPaid', 'advertising' );
	}else{
		$cart_options[''] = __( 'No Cart', 'advertising' );
	}

	// WooCommerce
//	if ( class_exists( 'WooCommerce' ) ) {
//		$cart_options['woocommerce'] = __( 'WooCommerce', 'advertising' );
//	}

	return apply_filters( 'adv_get_cart_options', $cart_options );
}

function adv_get_cart() {
	return defined('WPINV_VERSION') ? 'invoicing' : '';
//	return adv_get_option( 'cart' );
}

function adv_get_currency() {
	return apply_filters( 'adv_get_currency', 'USD' );
}

function adv_currency_sign() {
	return apply_filters( 'adv_currency_sign', '$' );
}

function adv_currency_position() {
	return apply_filters( 'adv_currency_position', 'left' );
}

function adv_thousand_separator() {
	return apply_filters( 'adv_thousand_separator', ',' );
}

function adv_decimal_separator() {
	return apply_filters( 'adv_decimal_separator', '.' );
}

function adv_price_decimals() {
	return apply_filters( 'adv_price_decimals', '2' );
}

function adv_price_format() {
	global $advertising;

	return $advertising->cart->price_format();
}

function adv_price( $price, $args = array() ) {
	global $advertising;

	return $advertising->cart->price( $price, $args );
}

/**
 * Format decimal numbers ready for DB storage.
 *
 * Sanitize, remove decimals, and optionally round + trim off zeros.
 *
 * This function does not remove thousands - this should be done before passing a value to the function.
 *
 * @since 1.0.0
 *
 * @param  float|string $number Expects either a float or a string with a decimal separator only (no thousands)
 * @param  mixed $dp number of decimal points to use, blank to use geodir_get_price_decimals, or false to avoid all rounding.
 * @param  bool $trim_zeros from end of string
 * @return string
 */
function adv_format_decimal( $number, $dp = false, $trim_zeros = false ) {
	global $advertising;

	return $advertising->cart->format_decimal( $number, $dp, $trim_zeros );
}

function adv_rounding_precision() {
	$precision = adv_price_decimals() + 2;
	if ( defined( 'ADV_ROUNDING_PRECISION' ) && absint( ADV_ROUNDING_PRECISION ) > $precision ) {
		$precision = absint( ADV_ROUNDING_PRECISION );
	}
	return $precision;
}

function adv_trim_zeros( $price ) {
	return preg_replace( '/' . preg_quote( adv_decimal_separator(), '/' ) . '0++$/', '', $price );
}

/**
 *  Get an array of supported paid ad status.
 */
function adv_get_paid_ads_status_options() {
	$ad_status = array(
		'pending' => __( 'Pending review', 'advertising' ),
		'publish' => __( 'Publish', 'advertising' ),
	);
	return apply_filters( 'advertising_paid_ads_status', $ad_status );
}
