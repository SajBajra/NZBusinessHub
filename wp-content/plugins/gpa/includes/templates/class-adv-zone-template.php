<?php
/**
 * Contains the zone display template.
 *
 * @since 1.0.0
 * @package Invoicing
 */

defined( 'ABSPATH' ) || exit;

/**
 * The zone display template class.
 */
class Adv_Zone_Template {

	/**
	 * The zone being displayed.
	 *
	 * @param Adv_Zone $zone
	 */
	public $zone;

	/**
     * Contains all ads to display.
     */
    public $ads = null;

	/**
	 * The class constructor
	 * @param Adv_Zone|int $zone The zone to display.
	 */
	public function __construct( $zone, $display = false ) {

		$this->zone = new Adv_Zone( $zone );

		if ( $display ) {
			$this->display();
		}

	}

	/**
	 * Retrieves the ads to display.
	 *
	 */
	public function get_ads() {

		if ( is_null( $this->ads ) ) {

			// Get ads belonging to this zone.
			$count = $this->zone->get( 'count' );

			if ( 'all' === $count ) {
				$count = null;
			} else {
				$count = absint( $count );
			}

			$this->ads = $this->zone->get_ads( $count );
		}

		return $this->ads;

	}

	/**
	 * Generates the ad HTML.
	 */
	public function get_html() {
		ob_start();
		$this->display();
		return ob_get_clean();
	}

	/**
	 * Displays the ad HTML.
	 */
	public function display() {

		ob_start();

		adv_get_template( 'zone-content.php', array( 'zone' => $this ) );

		$zone_inner_content = ob_get_clean();

		$zone_inner_content = trim( $zone_inner_content );

		if ( $zone_inner_content ) {
			adv_get_template(
				'zone.php',
				array(
					'zone'               => $this,
					'zone_inner_content' => $zone_inner_content,
				)
			);
		}

		// Load scripts.
		$GLOBALS['adv_displayed_zones'] = true;
	}

	/**
	 * Retrieves the ad id.
	 *
	 * @return int
	 */
	public function get_id() {
		return absint( $this->zone->ID );
	}

    /**
     * Retrieves the ad rotation.
     *
     * @return int
     */
    public function get_ad_rotation() {
        return (int) $this->zone->get( 'ad_rotation' );
    }

    /**
     * Retrieves the ad rotation interval.
     *
     * @return int
     */
    public function get_ad_rotation_interval() {
        return absint( $this->zone->get( 'ad_rotation_interval' ) );
    }
}
