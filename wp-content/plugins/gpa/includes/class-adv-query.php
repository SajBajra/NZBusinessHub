<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Adv_Query {
    
    public $query_vars = array();

    public function __construct() {
        add_action( 'init', array( $this, 'add_endpoints' ) );
        
        if ( !is_admin() ) {
            add_filter( 'query_vars', array( $this, 'add_query_vars' ), 0 );
            add_action( 'parse_request', array( $this, 'parse_request' ), 0 );
            add_action( 'pre_get_posts', array( $this, 'pre_get_posts' ) );
        }
        $this->init_query_vars();
    }

    public function init_query_vars() {
        $this->query_vars = array(
            'ads' => get_option( 'adv_dashboard_ads_endpoint', 'ads' ),
            'new-ad' => get_option( 'adv_dashboard_campaigns_endpoint', 'new-ad' ),
        );
    }

    public function get_endpoint_title( $endpoint ) {
        global $wp;

        switch ( $endpoint ) {
            case 'ads' :
                if ( ! empty( $wp->query_vars['ads'] ) ) {
                    $title = sprintf( __( 'My Ads (page %d)', 'advertising' ), intval( $wp->query_vars['ads'] ) );
                } else {
                    $title = __( 'My Ads', 'advertising' );
                }
            break;
            case 'campaigns' :
                if ( ! empty( $wp->query_vars['campaigns'] ) ) {
                    $title = sprintf( __( 'My Campaigns (page %d)', 'advertising' ), intval( $wp->query_vars['campaigns'] ) );
                } else {
                    $title = __( 'My Campaigns', 'advertising' );
                }
            break;
            case 'packages' :
                if ( ! empty( $wp->query_vars['packages'] ) ) {
                    $title = sprintf( __( 'Packages (page %d)', 'advertising' ), intval( $wp->query_vars['packages'] ) );
                } else {
                    $title = __( 'Packages', 'advertising' );
                }
            break;
            case 'new-ad' :
                $title = __( 'Add a new Ad', 'advertising' );
            break;
            case 'new-campaign' :
                $title = __( 'Create Campaign', 'advertising' );
            break;
            case 'reports' :
                $title = __( 'My Reports', 'advertising' );
            break;
            default :
                $title = '';
            break;
        }

        return apply_filters( 'adv_endpoint_' . $endpoint . '_title', $title, $endpoint );
    }

    public function get_endpoints_mask() {
        if ( 'page' === get_option( 'show_on_front' ) ) {
            $page_on_front     = get_option( 'page_on_front' );
            $dashboard_page_id = adv_get_option( 'adv_dashboard_page_id' );

            if ( in_array( $page_on_front, array( $dashboard_page_id ) ) ) {
                return EP_ROOT | EP_PAGES;
            }
        }

        return EP_PAGES;
    }

    public function add_endpoints() {
        $mask = $this->get_endpoints_mask();

        foreach ( $this->query_vars as $key => $var ) {
            if ( !empty( $var ) ) {
                add_rewrite_endpoint( $var, $mask );
            }
        }

    }

    public function add_query_vars( $vars ) {
        foreach ( $this->get_query_vars() as $key => $var ) {
            $vars[] = $key;
        }
        return $vars;
    }

    public function get_query_vars() {
        return apply_filters( 'adv_get_query_vars', $this->query_vars );
    }

    public function get_current_endpoint() {
        global $wp;
        foreach ( $this->get_query_vars() as $key => $value ) {
            if ( isset( $wp->query_vars[ $key ] ) ) {
                return $key;
            }
        }
        return '';
    }

    public function parse_request() {
        global $wp;

        foreach ( $this->get_query_vars() as $key => $var ) {
            if ( isset( $_GET[ $var ] ) ) {
                $wp->query_vars[ $key ] = sanitize_text_field( $_GET[ $var ] );
            } elseif ( isset( $wp->query_vars[ $var ] ) ) {
                $wp->query_vars[ $key ] = $wp->query_vars[ $var ];
            }
        }
    }

    private function is_showing_page_on_front( $q ) {
        return $q->is_home() && 'page' === get_option( 'show_on_front' );
    }

    private function page_on_front_is( $page_id ) {
        return absint( get_option( 'page_on_front' ) ) === absint( $page_id );
    }

    public function pre_get_posts( $q ) {
        if ( ! $q->is_main_query() ) {
            return;
        }

        if ( $this->is_showing_page_on_front( $q ) && ! $this->page_on_front_is( $q->get( 'page_id' ) ) ) {
            $_query = wp_parse_args( $q->query );
            if ( ! empty( $_query ) && array_intersect( array_keys( $_query ), array_keys( $this->query_vars ) ) ) {
                $q->is_page     = true;
                $q->is_home     = false;
                $q->is_singular = true;
                $q->set( 'page_id', (int) get_option( 'page_on_front' ) );
                add_filter( 'redirect_canonical', '__return_false' );
            }
        }
    }
}