<?php
/**
 * Contains the abstract ad template.
 *
 * @since 1.0.0
 * @package Invoicing
 */

defined( 'ABSPATH' ) || exit;

/**
 * The abstract ad template class.
 */
class Adv_Ad_Template {

    /**
     * Ad arguments.
     * 
     * @param Adv_Ad $ad
     */
    public $ad;

    /**
     * The class constructor
     * @param Adv_Ad|int $ad The ad to display.
     */
    public function __construct( $ad, $display = false ) {

        $this->ad = new Adv_Ad( $ad );

        if ( $display ) {
            $this->display();
        }

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
        adv_get_template( 'ad.php', array( 'ad' => $this ) );

        // Load scripts.
        $GLOBALS['adv_displayed_ads'] = true;
    }

    /**
     * Do not add a link for html ads.
     *
     * @return bool
     */
    public function wrap_with_link() {
        return ! in_array( $this->ad->get( 'type' ), array( 'code', 'listing' ) );
    }

    /**
     * Retrieves the ad id.
     * 
     * @return int
     */
    public function get_id() {
        return absint( $this->ad->ID );
    }

    /**
     * Retrieves the ad url.
     * 
     * @return string
     */
    public function get_url( $escaped = true ) {
        return $escaped ? esc_url( adv_tracking_url( $this->get_id() ) ) : adv_tracking_url( $this->get_id() );
    }

    /**
     * Retrieves the ad title.
     * 
     * @return string
     */
    public function get_title() {
        return sanitize_text_field( get_the_title( $this->get_id() ) );
    }

    /**
     * Returns the ad type.
     *
     * @return string
     */
    public function get_type() {
        return sanitize_text_field( $this->ad->get( 'type' ) );
    }

    /**
     * Returns the ad zone.
     *
     * @return string
     */
    public function get_zone() {
        return sanitize_text_field( $this->ad->get( 'zone' ) );
    }

}
