<?php

/**
 * Class Adv_Ad
 *
 * @since 1.0.1dev
 */
class Adv_Ad {

	/**
	 * Post ID of this ad
	 */
	public $ID = null;

	/**
	 * Post data for this ad
	 */
	public $ad = array();

	/**
	 * Meta values for this ad
	 */
	public $meta = array();


	/**
	 * Class constructor
	 * @param $ad instance of Adv_Ad or a post ID
	 */
	public function __construct( $ad ) {

		//Is this an instance of the class
		if ( $ad instanceof Adv_Ad ) {
			$this->ID   = $ad->ID;
			$this->ad   = $ad->ad;
			$this->meta = $ad->meta;
			return;
		}

		if (! is_numeric( $ad ) ) {
			return;
		}

		//Lets try to fetch it from the cache
		if ( $_ad = wp_cache_get( $ad, 'Adv_Ad' ) ) {
			$this->ID   = $_ad->ID;
			$this->ad   = $_ad->ad;
			$this->meta = $_ad->meta;
			return;
		}

		//load data from the db
		if( get_post_type( $ad ) == adv_ad_post_type() ) {
			$this->ID        = $ad;
			$this->ad        = (array) get_post( $ad  );
			$this->meta      = adv_get_ad_props( $ad );
			wp_cache_add( $ad, $this, 'Adv_Ad' );

			return;
		}


	}

	/**
	 * Retrieve a property of an ad.
	 * @param $field
	 */
	public function get( $field ) {

		if( 'advertiser' == $field ) {
			$field = 'post_author';
		}

		if( empty( $field ) ) {
			$value = '';
		} elseif( isset( $this->ad[$field] ) ) {
			$value = $this->ad[$field];
		} elseif( isset( $this->meta[$field] ) ) {
			$value = $this->meta[$field];
		} else {
			$value = '';
		}

		/**
		 * Filters an ad's property
		 */
		return apply_filters( 'adv_ad_prop', $value, $field, $this );

	}

	/**
	 * Checks whether or not the ad is published
	 */
	public function is_published() {
		return ( $this->ID && $this->get( 'post_status' ) == 'publish' );
	}

	/**
	 * Checks whether or not we can display this ad
	 */
	public function can_display_ad() {

		// Abort if GD is not available.
		if ( 'listing' === $this->get( 'type' ) ) {

			// Abort if GD is not available.
			if ( ! class_exists( 'GeoDirectory' ) ) {
				return false;
			}

			// Or if the listing is not published.
			if ( 'publish' !== get_post_status( (int) $this->get( 'listing' ) ) ) {
				return false;
			}
		}

		$can_display = ( adv_can_display_ads() && $this->is_published() );

		return apply_filters( 'adv_can_display_ad', $can_display, $this->ID, $this );

	}

	/**
	 * Returns the html code needed to display this ad
	 */
	public function get_html() {

		// Check limits.
		adv_check_ad_limits( $this );

		// Maybe abort early.
		if ( ! $this->can_display_ad() ) {
			return '';
		}

		$class = apply_filters( 'advertising_ad_template_class', 'Adv_Ad_Template', $this );

		// Display the ad.
		if ( class_exists( $class ) ) {
			$template = new $class( $this );
			return $template->get_html();
		}

		return '';

	}

}
