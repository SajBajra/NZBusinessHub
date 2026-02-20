<?php
/**
 * Advertising Core Cart integration class.
 *
 * @since 1.0.0
 * @package Advertising
 * @author AyeCode Ltd
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Dummy class used when no payment cart is selected
 * @see Adv_Cart
 */
class Adv_Cart_Core extends Adv_Cart {

	public function __construct() {

	}
}