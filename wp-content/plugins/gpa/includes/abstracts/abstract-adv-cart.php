<?php
/**
 * Abstract cart
 *
 * @since 1.0.0
 * @package Advertising
 * @author AyeCode Ltd
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Cart Class
 * 
 * Provides functionality for displaying and processing payments.
 * Extend this class to support your payment plugin
 */
abstract class Adv_Cart {

	public function __construct() {}
		
	/**
	 * Returns the store currency
	 */
	public function get_currency() {
		return apply_filters( 'adv_get_currency', 'USD' );
	}

	/**
	 * Returns the store currency's currency sign
	 */
	public function currency_sign( $currency = '' ) {
		return apply_filters( 'adv_currency_sign', '$', $currency );
	}

	/**
	 * Returns the store currency's currency position
	 */
	public function currency_position( $currency = '' ) {
		return apply_filters( 'adv_currency_position', 'left', $currency );
	}

	/**
	 * Returns the store currency's thousand separator
	 */
	public function thousand_separator( $currency = '' ) {
		return apply_filters( 'adv_thousand_separator', ',', $currency );
	}

	/**
	 * Returns the store currency's decimal separator
	 */
	public function decimal_separator( $currency = '' ) {
		return apply_filters( 'adv_decimal_separator', '.', $currency );
	}

	/**
	 * Returns the number of decimals to use
	 */
	public function price_decimals() {
		return apply_filters( 'adv_price_decimals', '2' );
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
	 * @param  mixed $dp number of decimal points to use, blank to use adv_price_decimals, or false to avoid all rounding.
	 * @param  bool $trim_zeros from end of string
	 * @return string
	 */
	public function format_decimal( $number, $dp = false, $trim_zeros = false ) {
		$input_number = $number;
		$locale   = localeconv();
		$decimals = array( adv_decimal_separator(), $locale['decimal_point'], $locale['mon_decimal_point'] );

		// Remove locale from string.
		if ( ! is_float( $number ) ) {
			$number = str_replace( $decimals, '.', $number );
			$number = preg_replace( '/[^0-9\.,-]/', '', sanitize_text_field( $number ) );
		}

		if ( false !== $dp ) {
			$dp     = intval( '' == $dp ? adv_price_decimals() : $dp );
			$number = number_format( floatval( $number ), $dp, '.', '' );

		// DP is false - don't use number format, just return a string in our format
		} elseif ( is_float( $number ) ) {
			// DP is false - don't use number format, just return a string using whatever is given. Remove scientific notation using sprintf.
			$number     = str_replace( $decimals, '.', sprintf( '%.' . adv_rounding_precision() . 'f', $number ) );
			// We already had a float, so trailing zeros are not needed.
			$trim_zeros = true;
		}

		if ( $trim_zeros && strstr( $number, '.' ) ) {
			$number = rtrim( rtrim( $number, '0' ), '.' );
		}

		return apply_filters( 'adv_format_decimal', $number, $input_number, $dp, $trim_zeros );
	}

	/**
	 * Get the price format depending on the currency position.
	 *
	 * @return string
	 */
	public function price_format() {
		$position = $this->currency_position();

		$format = '%1$s%2$s';

		switch ( $position ) {
			case 'left' :
				$format = '%1$s%2$s';
			break;
			case 'right' :
				$format = '%2$s%1$s';
			break;
			case 'left_space' :
				$format = '%1$s&nbsp;%2$s';
			break;
			case 'right_space' :
				$format = '%2$s&nbsp;%1$s';
			break;
		}

		return apply_filters( 'adv_price_format', $format, $position );
	}

	public function price( $price, $args = array() ) {
		$currency = $this->get_currency();
		$args = apply_filters( 'adv_price_args', wp_parse_args( $args, array(
			'currency' 			 => $currency,
			'decimal_separator'  => $this->decimal_separator( $currency ),
			'thousand_separator' => $this->thousand_separator( $currency ),
			'decimals' 			 => $this->price_decimals( $currency ),
			'price_format' 		 => $this->price_format(),
		) ) );

		$unformatted_price = $price;
		$negative          = $price < 0;
		$price             = apply_filters( 'adv_raw_price', floatval( $negative ? $price * -1 : $price ) );
		$price             = apply_filters( 'adv_formatted_price', number_format( $price, $args['decimals'], $args['decimal_separator'], $args['thousand_separator'] ), $price, $args['decimals'], $args['decimal_separator'], $args['thousand_separator'] );

		if ( apply_filters( 'adv_price_trim_zeros', false ) && $args['decimals'] > 0 ) {
			$price = adv_trim_zeros( $price );
		}

		$formatted_price = ( $negative ? '-' : '' ) . wp_sprintf( $args['price_format'], $this->currency_sign( $args['currency'] ), $price );
		$return = $formatted_price;

		return apply_filters( 'adv_price', $return, $price, $args, $unformatted_price );
	}
}
