<?php
/**
 * Tracks ads.
 *
 * @package Advertising
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Ad tracking class.
 *
 * @package Advertising
 * @since   1.0.0
 */
class Adv_Tracking {

    /**
	 * Class constructor.
	 */
	public function __construct() {

		add_filter( 'query_vars', array( $this, 'custom_query_vars' ) );
        add_action( 'init', array( $this, 'add_rewrite_rule' ), 10, 0 );
		add_action( 'pre_get_posts', array( $this, 'detect_click' ), 1 );

        // Ajax actions.
        add_action( 'wp_ajax_adv_track_impressions', array( $this, 'track_impressions' ) );
		add_action( 'wp_ajax_nopriv_adv_track_impressions', array( $this, 'track_impressions' ) );

        add_action( 'wp_ajax_adv_rotate_ads', array( $this, 'rotate_ads' ) );
		add_action( 'wp_ajax_nopriv_adv_rotate_ads', array( $this, 'rotate_ads' ) );
	}

    /**
	 * Custom query vars.
	 *
	 */
	public function custom_query_vars( $vars ) {
        $slug = adv_tracking_slug();

        if ( ! empty( $slug ) ) {
            $vars[] = $slug;
        }

        return $vars;
	}

    /**
	 * Add rewrite tags and rules.
	 *
	 */
	public function add_rewrite_rule() {

        $tag = adv_tracking_slug();

        if ( empty( $tag ) ) {
            return;
        }

        add_rewrite_tag( "%$tag%", '([^&]+)' );
        add_rewrite_rule( "^$tag/([^/]*)/?", "index.php?$tag=\$matches[1]", 'top' );
	}

    /**
	 * Detects ad clicks.
	 *
	 */
	public function detect_click( $query ) {

        $tag = adv_tracking_slug();

        if ( empty( $tag ) || is_admin() || ! $query->is_main_query() ) {
            return;
        }

		$ad_id = get_query_var( $tag );

        if ( empty( $ad_id ) ) {
            return;
        }

        $referrer = wp_get_raw_referer();

        if ( is_numeric( $ad_id ) && $referrer ) {

            $ad_id = (int) $ad_id;
            $ad    = adv_get_ad( $ad_id );

            if ( adv_can_display_ads() && $ad->is_published() ) {

                nocache_headers();

                // Track the click...
                $this->track_ad_click( $ad_id, $ad->get( 'zone' ) );

                // ... then check for limits ...
                adv_check_ad_limits( $ad );

                //... and redirect to the target page
                $target_url = $ad->get( 'target_url' );

                if ( $ad->get( 'type' ) == 'listing' ) {
                    $target_url = get_permalink( $ad->get( 'listing' ) );
                }

                $target_url = ! empty( $target_url ) ? $target_url : home_url( '/' );
                wp_redirect( esc_url_raw( $target_url ) );

                exit;

            }
}
        wp_redirect( esc_url( home_url() ) );
        exit;
	}

    /**
	 * Update click stats for the zone and ads
	 *
	 */
	public function track_ad_click( $ad, $zone = 0 ) {

        // Abort early if there are no ads
        if ( empty( $ad ) ) {
            return;
        }

        // Do not log clicks from site admins...
        if ( current_user_can( 'manage_options' ) ) {
            return;
        }

        // ... or ad owners.
        if ( get_current_user_id() == get_post_field( 'post_author', $ad ) ) {
            return;
        }

        // Increment the click count & ctr for the ad
        $views  = (int)get_post_meta( $ad, '_adv_ad_views', true );
        $clicks = (int)get_post_meta( $ad, '_adv_ad_clicks', true );
        update_post_meta( $ad, '_adv_ad_clicks', $clicks + 1 );
        update_post_meta( $ad, '_adv_ad_ctr', adv_calculate_ctr( $clicks + 1, $views ) );

        //Increment the view count & ctr for a zone
        if ( empty( $zone ) ) {
            return;
        }

        $views  = (int)get_post_meta( $zone, '_adv_zone_views', true );
        $clicks = (int)get_post_meta( $zone, '_adv_zone_clicks', true );
        update_post_meta( $zone, '_adv_zone_ctr', adv_calculate_ctr( $clicks + 1, $views ) );
        update_post_meta( $zone, '_adv_zone_clicks', $clicks + 1 );
    }

    /**
	 * Tracks ad impressions.
	 */
	public function track_impressions() {

        // Check nonce.
        check_ajax_referer( 'adv-nonce' );

        // Sanitize values.
        $ads   = empty( $_POST['ads'] ) ? array() : wp_parse_id_list( $_POST['ads'] );
        $zones = empty( $_POST['zones'] ) ? array() : wp_parse_id_list( $_POST['zones'] );

        // Log views and impressions.
        array_map( 'adv_log_ad_view', $ads );
        array_map( 'adv_log_zone_view', $zones );

        wp_send_json_success();
	}

    /**
     * Rotates ads.
     *
     * @return void
     */
    public function rotate_ads() {

        // Check nonce.
        check_ajax_referer( 'adv-nonce' );

        if ( ! isset( $_POST['zone_id'] ) || empty( $_POST['zone_id'] ) ) {
            wp_send_json_error();
        }

        $zone_id = isset( $_POST['zone_id'] ) ? absint( $_POST['zone_id'] ) : 0;
        $skip_ads = isset( $_POST['skip_ads'] ) ? (array) $_POST['skip_ads'] : array();
        $skip_ads = array_map( 'absint', $skip_ads );

        $zone    = new Adv_Zone( $zone_id );
        $zone->skip_ads = $skip_ads;
        $html    = $zone->get_html();

        wp_send_json_success( $html );
    }
}
new Adv_Tracking();
