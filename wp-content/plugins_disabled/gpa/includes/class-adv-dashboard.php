<?php
/**
 * Main Dashboard class
 *
 * @package    Adv_Dashboard
 * @since      1.0.1-dev
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * class Adv_Dashboard
 *
 */
class Adv_Dashboard {
     /**
	 * The single instance of the class.
	 *
	 * @since 1.0.1-dev
	 */
    private static $instance = null;

    /**
	 * Notices.
	 *
	 * @since 1.0.1-dev
	 */
    private static $notices = false;

    /**
	 * Adv_Dashboard Main Instance.
	 *
	 * Ensures only one instance of Adv_Dashboard is loaded or can be loaded.
	 *
	 * @since 1.0.1-dev
	 * @static
	 * @return Adv_Dashboard - Main instance.
	 */
	public static function instance() {
		if ( ! isset( self::$instance ) && ! ( self::$instance instanceof Adv_Dashboard ) ) {
            self::$instance = new Adv_Dashboard;
            self::$instance->init_hooks();
        }

        return self::$instance;
	}


    /**
     * Hook into actions and filters.
     * @since  1.0.1-dev
     */
    private function init_hooks() {
        add_action( 'adv_display_dashboard_logged_in', array( $this, 'print_content' ), 20 );

        add_action( 'adv_display_dashboard_logged_out', array( $this, 'print_logged_out_text' ), 10 );

        add_action( 'adv_dashboard_dashboard_endpoint', array( $this, 'print_dashboard_ads' ), 10 );
        add_action( 'adv_dashboard_reports_endpoint', array( $this, 'print_dashboard_reports' ), 10 );
        add_action( 'adv_dashboard_new-ad_endpoint', array( $this, 'print_dashboard_new_ad' ), 10 );

        add_action( 'template_redirect', array( $this, 'maybe_save_submitted_ad' ), 10 );
    }

    /**
     * Displays the dashboard
     * @since  1.0.1-dev
     */
    public function get_html() {

        $current_endpoint = esc_attr( adv_dashboard_current_endpoint() );

        //Output the dashboard
        ob_start();

        // @todo kadence has a bug where it will nto change page id the URL hash is on the current page
        $maybe_id = $current_endpoint != 'dashboard' ? "id='adv'" : '';
        echo "<div class='adv adv-endpoint-$current_endpoint' $maybe_id>";

        if ( is_user_logged_in() ) {

            /**
		     * Fires when printing the dashboard html for a logged in user
		     *
		     * EVENTS
		     *
             * Adv_Dashboard::print_content 20
             *
		     * @since 1.0.1-dev
		     */
            do_action( 'adv_display_dashboard_logged_in', $current_endpoint );

        } else {

            /**
		     * Fires when printing the dashboard html for a logged out user
		     *
		     * EVENTS
		     *
		     * Adv_Dashboard::print_logged_out_text 10
             *
		     * @since 1.0.1-dev
		     */
            do_action( 'adv_display_dashboard_logged_out', $current_endpoint );

        }

        echo '</div>';

        $dashboard_html = ob_get_clean();

        return apply_filters( 'adv_dashboard_html', $dashboard_html, $this );

    }

    /**
     * Prints the dashboard's content
     * @since  1.0.1-dev
     */
    public function print_content( $current_endpoint ) {

        /**
		 * Fires when printing the dashboard html for a logged out user
		 *
		 * EVENTS
		 *
         * Adv_Dashboard::print_dashboard_reports 10
         * Adv_Dashboard::print_dashboard_new_ad 10
         * Adv_Dashboard::print_dashboard_ads 10
         *
		 * @since 1.0.1-dev
		 */
        do_action( "adv_dashboard_{$current_endpoint}_endpoint" );


    }

    /**
     * Prints the reports dashboard content
     * @since  1.0.1-dev
     */
    public function print_dashboard_reports() {

        $label = __( 'Reports coming soon...', 'advertising' );
        echo "<div class='adv-nav-content adv-content-reports'>
                    <h3 class='adv-navc-title mt-3 mb-3'>$label</h3>
                </div>";
    }

    /**
     * Prints the new-ad dashboard content
     * @since  1.0.1-dev
     */
    public function print_dashboard_new_ad() {

        add_filter( 'adv_load_footer_template', '__return_true' );

        $page_title      = __( 'Add a new Ad', 'advertising' );
        $form_action     = adv_dashboard_endpoint_url( 'new-ad' );
        $post_id         = !empty( $_REQUEST['ad'] ) ? absint( $_REQUEST['ad'] ) : 0;
        $title           = !empty( $_REQUEST['title'] ) ? esc_html( $_REQUEST['title'] ) : '';
        $zone            = !empty( $_REQUEST['zone'] ) ? absint( $_REQUEST['zone'] ) : -1;
        $type            = !empty( $_REQUEST['type'] ) ? esc_attr( $_REQUEST['type'] ) : 'text';
        $target_url      = !empty( $_REQUEST['target_url'] ) ? esc_url( $_REQUEST['target_url'] ) : '';
        $locations       = !empty( $_REQUEST['locations'] ) ? esc_attr( $_REQUEST['locations'] ) : '';
        $image           = !empty( $_REQUEST['image'] ) ? esc_url( $_REQUEST['image'] ) : '';
        $ad_text         = !empty( $_REQUEST['description'] ) ? esc_html( $_REQUEST['description'] ) : '';
        $ad_code         = !empty( $_REQUEST['code'] ) ? esc_html( $_REQUEST['code'] ) : '';
        $listing_content = !empty( $_REQUEST['listing_content'] ) ? sanitize_text_field( $_REQUEST['listing_content'] ) : 'featured_image';
        $listing         = !empty( $_REQUEST['listing'] ) ? absint( $_REQUEST['listing'] ) : '';
        $new_tab         = !empty( $_REQUEST['new_tab'] ) ? esc_attr( $_REQUEST['new_tab'] ) : '';
        $notices         = self::$notices;

        if(! empty( $post_id ) ) {
            $page_title = __( 'Edit Ad', 'advertising' );

            if( empty( $_POST['adv_nonce'] ) ) {
                $title           = esc_html( get_the_title( $post_id ));
                $zone            = adv_ad_get_meta( $post_id, 'zone', true, '-1' );
                $type            = adv_ad_get_meta( $post_id, 'type', true, 'text' );
                $target_url      = adv_ad_get_meta( $post_id, 'target_url', true, '' );
                $locations       = adv_ad_get_meta( $post_id, 'locations', true, '' );
                $image           = adv_ad_get_meta( $post_id, 'image', true, '' );
                $ad_text         = adv_ad_get_meta( $post_id, 'description', true, '' );
                $ad_code         = adv_ad_get_meta( $post_id, 'code', true, '' );
                $listing_content = adv_ad_get_meta( $post_id, 'listing_content', true, 'featured_image' );
                $listing         = adv_ad_get_meta( $post_id, 'listing', true, '' );
                $new_tab         = adv_ad_get_meta( $post_id, 'new_tab', true, '' );
            }
        }
        include ADVERTISING_PLUGIN_DIR . 'includes/admin/views/new-ad-frontend.php';

    }

    /**
     * Saves an ad
     * @since  1.0.1-dev
     */
    public function maybe_save_submitted_ad() {
        global $adv_saved_ad;

        // Maybe abort early
        if ( ! is_user_logged_in() || empty( $_POST['adv_nonce'] ) || $adv_saved_ad ) {
            return;
        }

        $adv_saved_ad = true;

        //Verify the nonce
        if (! wp_verify_nonce( $_REQUEST['adv_nonce'], 'adv_save_ad' ) ) {
            self::$notices = new WP_Error( 'bad_nonce', __( 'We could not save the ad. Please refresh the page and try again.', 'advertising' ) );
            return;
        }

        //Abort if no title has been specified
        if( empty( $_REQUEST['title'] ) ) {
            self::$notices = new WP_Error( 'no_title', __( 'Specify a title for the ad', 'advertising' ) );
            return;
        }
        $title = sanitize_text_field( $_REQUEST['title'] );

        // The zone and ad are mandatory.
        if ( empty( $_POST['ad'] ) && ( empty( $_REQUEST['zone'] ) || '-1' == $_REQUEST['zone'] ) ) {
            self::$notices = new WP_Error( 'no_zone', __( 'Select a zone for the ad', 'advertising' ) );
            return;
        }

        if ( empty( $_REQUEST['target_url'] ) && 'listing' != $_REQUEST['type'] ) {
            self::$notices = new WP_Error( 'no_target', __( 'Specify a target url for the ad', 'advertising' ) );
            return;
        }

        //Either the description or image should be specified
        if ( 'image' == $_POST['type'] && empty( $_REQUEST['image'] ) ) {
            self::$notices = new WP_Error( 'no_image', __( 'set an image for the ad', 'advertising' ) );
            return;
        }

        if ( 'code' == $_POST['type'] && 'allow' != adv_get_option( 'html_ads', 'admin' ) ) {
            self::$notices = new WP_Error( 'not_allowed', __( 'You are not allowed to post HTML ads.', 'advertising' ) );
            return;
        }

        if ( 'text' == $_POST['type'] && empty( $_REQUEST['description'] ) ) {
            self::$notices = new WP_Error( 'no_description', __( 'Set a description for the ad', 'advertising' ) );
            return;
        }
    
        if ( 'listing' == $_POST['type'] && empty( $_REQUEST['listing'] ) ) {
            self::$notices = new WP_Error( 'no_listing', __( 'Select a listing for the ad', 'advertising' ) );
            return;
        }

        // If this is an existing ad...
        if (! empty( $_REQUEST['ad'] ) ) {

            $ad = (int) trim( $_REQUEST['ad'] );

            //...ensure it exists and it is a valid ad
            $ad = get_post( $ad );
            if( empty( $ad ) || $ad->post_type != adv_ad_post_type() || get_current_user_id() != $ad->post_author ) {
                self::$notices = new WP_Error( 'invalid_post', __( 'That ad is not valid.', 'advertising' ) );
                return;
            }

            $post_status = 'code' == $_REQUEST['type'] ? 'pending' : $ad->post_status;
            $ad          = wp_update_post( array(
                'ID'            => $ad->ID,
                'post_title'    => $title,
                'post_status'   => $post_status,
            ), true);

            if ( is_wp_error( $ad ) ) {
                self::$notices = $ad;
                return;
            }

        } else {

            // Create a new ad.
            $post_status = 'code' == $_REQUEST['type'] ? 'pending' : adv_ad_new_status();
            $ad = wp_insert_post( array(
                'post_title'    => $title,
                'post_type'     => adv_ad_post_type(),
                'post_author'   => get_current_user_id(),
                'post_status'   => $post_status,
            ), true);

            if ( is_wp_error( $ad ) ) {
                self::$notices = $ad;
                return;
            }

        }

        //Update the ad's post meta
        $zone   =  ! empty( $_REQUEST['zone'] ) ? sanitize_text_field( $_REQUEST['zone'] ) : '';
        $fields = array(
            'description'      => !empty( $_REQUEST['description'] ) ? esc_html( $_REQUEST['description'] ) : '',
            'code'             => !empty( $_REQUEST['code'] ) ? htmlspecialchars_decode( $_REQUEST['code'] ) : '',
            'zone'             => $zone,
            'qty'              => !empty( $_REQUEST['zone_' . $zone . '_qty'] ) ? absint( $_REQUEST['zone_' . $zone . '_qty'] ) : '1',
            'type'             => !empty( $_REQUEST['type'] ) ? esc_attr( $_REQUEST['type'] ) : 'text',
            'target_url'       => esc_url( $_REQUEST['target_url'] ),
            'locations'        => !empty( $_REQUEST['locations'] ) ? esc_attr( $_REQUEST['locations'] ) : '',
            'image'            => !empty( $_REQUEST['image'] ) ? esc_url( $_REQUEST['image'] ) : '',
            'listing'          => !empty( $_REQUEST['listing'] ) ? esc_attr( $_REQUEST['listing'] ) : '',
            'listing_content'  => !empty( $_REQUEST['listing_content'] ) ? esc_url( $_REQUEST['listing_content'] ) : 'featured_image',
            'new_tab'          => !empty( $_REQUEST['new_tab'] ) ? esc_attr( $_REQUEST['new_tab'] ) : '',
        );

        //An ad's zone can't be updated once the ad is created
        if (! empty( $_POST['ad'] ) ) {
            unset($fields['zone']);
        }

        $fields = apply_filters( '_adv_frontend_ad_meta_save', $fields, $ad);
        foreach( $fields as $key => $value ) {
            $key = '_adv_ad_' . $key;
            update_post_meta( $ad, $key, $value );
        }

        do_action( 'adv_save_' .adv_ad_post_type(), $ad, $zone, true );

        self::$notices = true;
    }

    /**
     * Prints the ads overview dashboard content
     * @since  1.0.1-dev
     */
    public function print_dashboard_ads() {
        adv_get_template(
            'ads-overview.php',
            array(
                'ads' => adv_get_ads_by_advertiser( get_current_user_id(), array( 'publish', 'pending', 'draft' ) ),
            )
        );
    }

    /**
     * Asks the user to login
     * @since  1.0.1-dev
     */
    public function print_logged_out_text() {

        echo aui()->alert(
            array(
                'type'    => 'info',
                'content' => __( 'You must be logged in to access the advertising dashboard.', 'advertising' ) . ' <a href="' . esc_url( wp_login_url( add_query_arg( array() ) ) ) . '" class="uwp-login-link">' . __( 'Login', 'advertising' ) . '</a>'
            )
        );

    }

}

Adv_Dashboard::instance();