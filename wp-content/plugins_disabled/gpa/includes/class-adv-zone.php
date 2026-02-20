<?php

/**
 * Class Adv_Zone
 *
 * @since 1.0.1dev
 */
class Adv_Zone {

    /**
     * Post ID of this zone
     */
    public $ID = null;

    /**
     * Post data for this zone
     */
    public $zone = array();

    /**
     * Meta values for this zone
     */
    public $meta = array();

    /**
     * Contains all ads posted to this zone
     */
    public $ads = null;

    /**
     * Contains all ads that should be skipped
     */
    public $skip_ads = array();

    /**
     * Class constructor
     * @param $zone instance of Adv_Zone or a post ID
     */
    public function __construct( $zone ) {

        //Is this an instance of the class
        if ( $zone instanceof Adv_Zone ) {
            $this->ID   = $zone->ID;
            $this->zone = $zone->zone;
            $this->meta = $zone->meta;
            $this->ads  = $zone->ads;
            $this->skip_ads  = $zone->skip_ads;
			return;
        }

        if ( ! is_numeric( $zone ) ) {
            return;
        }

        //Lets try to fetch it from the cache
        if ( $_zone = wp_cache_get( $zone, 'Adv_Zone' ) ) {
            $this->ID   = $_zone->ID;
            $this->zone = $_zone->zone;
            $this->meta = $_zone->meta;
            $this->ads  = $_zone->ads;
            $this->skip_ads = $_zone->skip_ads;
			return;
        }

        //load data from the db
        if ( get_post_type( $zone ) == adv_zone_post_type() ) {
            $this->ID        = $zone;
            $this->zone      = (array) get_post( $zone );
            $this->meta      = adv_get_zone_props( $zone );
            wp_cache_add( $zone, $this, 'Adv_Zone' );

            return;
        }
    }

    /**
     * Retrieve a property of a zone.
     * @param $field
     */
    public function get( $field ) {

        if ( empty( $field ) ) {
            $value = '';
        } elseif ( isset( $this->zone[ $field ] ) ) {
            $value = $this->zone[ $field ];
        } elseif ( isset( $this->meta[ $field ] ) ) {
            $value = $this->meta[ $field ];
        } else {
            $value = '';
        }

        /**
         * Filters a zone property
         */
        return apply_filters( 'adv_zone_prop', $value, $field, $this );
    }

    public function is_full() {
        $max_ads = $this->get( 'max_ads' );

        if ( empty( $max_ads ) ) {
            return false;
        }

        $available_ads = $this->get_ads( (int) $max_ads, false );

        return ! ( $max_ads > count( $available_ads ) );
    }

    /**
     * Retrieves all the ads on this zone
     *
     * @todo getting all the ads and then shuffling them is not a very efficient way to do this, it should be improved.
     */
    public function get_ads( $count = null, $shuffle = true ) {

        // If there are no ads in the cache add them;
        if ( ! is_array( $this->ads ) ) {

            //Fetch the ads from the db
            $query_args = array(
                'post_type'      => adv_ad_post_type(),
                'post_status'    => 'publish',
                'fields'         => 'ids',
                'posts_per_page' => -1,
                'meta_key'       => '_adv_ad_zone',
                'meta_value'     => $this->ID,
                'orderby'        => 'post_title',
                'post__not_in'   => (array) $this->skip_ads,
            );

            $ads                  = get_posts( $query_args );
            $this->ads            = apply_filters( 'adv_zone_ads', $ads, $this->ID, $this );

            $own_cpt   = (int) adv_zone_get_meta( $this->ID, 'own_post_types', true, 0 );
            $own_tax   = (int) adv_zone_get_meta( $this->ID, 'own_taxonomy', true, 0 );
            $own_terms = (int) adv_zone_get_meta( $this->ID, 'own_terms', true, 0 );
            $own_loc   = (int) adv_zone_get_meta( $this->ID, 'own_location', true, 0 );

            // Remove expired listings.
            if ( ! is_admin() ) :
            foreach ( $this->ads as $i => $ad ) {
					$adv_ad = new Adv_Ad( $ad );

					if ( ! $adv_ad->can_display_ad() ) {
						unset( $this->ads[ $i ] );
						continue;
						}

					if ( 'listing' == $adv_ad->get( 'type' ) ) {
                        $listing    = geodir_get_post_info( (int) $adv_ad->get( 'listing' ) );
                        $taxonomies = array( $listing->post_type . '_tags', $listing->post_type . 'category' );

                        // own location archives rules
                        if ( ! empty( $own_loc ) ) {
                            global $gd_post;

                            $current_location = '';
                            $listing_location = '';
                            /** @var GeoDir_Location $location */
                            $location = ! empty( $GLOBALS['geodirectory']->location ) ? $GLOBALS['geodirectory']->location : array();

                            if ( ! empty( $location->type ) ) {
                                $type = $location->type;

                                if ( ! empty( $location->{$type} ) ) {
                                    $current_location = $location->{$type};

                                    if ( ! empty( $listing->{$type} ) ) {
                                        $listing_location = $listing->{$type};
                                    }
                                }
                            }

                            if ( ! $current_location && ! $listing_location && geodir_is_page( 'single' ) && ! empty( $gd_post ) ) {
                                if ( ! empty( $gd_post->city ) ) {
                                    $current_location = $gd_post->city;

                                    if ( ! empty( $listing->city ) ) {
                                        $listing_location = $listing->city;
                                    }
                                }
                            }

                            $near_search_location = adv_near_search_location();
                            if ( ! empty( $near_search_location ) && geodir_get_current_posttype() == $listing->post_type ) {

                                $location_type = adv_near_search_location_type();

                                if ( ! empty( $listing->{$location_type} ) ) {
                                    $current_location = $near_search_location;
                                    $listing_location = $listing->{$location_type};
                                }
                            }

                            if ( ! ( $current_location && geodir_strtolower( stripslashes( $current_location ) ) == geodir_strtolower( stripslashes( $listing_location ) ) ) ) {
                                unset( $this->ads[ $i ] );
                                continue;
                            }
							}

                        // own terms rules.
                        if ( ! empty( $own_terms ) ) {

                            $listing_tags  = get_the_terms( $listing->ID, $listing->post_type . '_tags' );
                            $listing_terms = empty( $listing->post_category ) ? array() : wp_parse_id_list( $listing->post_category );

                            if ( is_array( $listing_tags ) ) {
                                $listing_terms = array_merge( $listing_terms, wp_list_pluck( $listing_tags, 'term_id' ) );
                            }

                            // Skip if not a term page.
                            if ( empty( $listing_terms ) ) {
                                unset( $this->ads[ $i ] );
                                continue;
                            }

                            // Check if the listing has the current term.
                            if ( ! adv_has_term( $listing_terms, $taxonomies ) ) {
                                unset( $this->ads[ $i ] );
                                continue;
                            }
							}

                        // own taxonomy archives rules.
                        if ( ! empty( $own_tax ) && ! is_singular() && ! is_tax( $taxonomies ) ) {
                            unset( $this->ads[ $i ] );
                            continue;
							}

                        // own CPT archives rules
                        if ( ! empty( $own_cpt ) && ! is_post_type_archive( $listing->post_type ) && geodir_get_current_posttype() !== $listing->post_type ) {
                            unset( $this->ads[ $i ] );
                            continue;
							}
						}
}
            endif;

        }

        $ads = $this->ads;

        // Maybe randomize the ads.
        if ( $shuffle ) {
            shuffle( $ads );
        }

        return array_slice( $ads, 0, $count );
    }

    /**
     * Checks whether or not the zone is published
     */
    public function is_published() {
        return ( $this->ID && $this->get( 'post_status' ) == 'publish' );
    }

    /**
     * Checks whether or not we can display this zone
     */
    public function can_display_zone() {

        $can_display = ( adv_can_display_ads() && $this->is_published() );

        if ( ! $can_display ) {
            return false;
        }

        /**
		 * Filters whether or not we can display this zone
		 *
		 * Filters
		 *
		 * adv_can_display_to_current_user 10
         * adv_can_display_on_posts 20
		 * adv_can_display_on_post_types 30
		 * adv_can_display_on_taxonomies 40
         * adv_can_display_on_terms 50
         *
		 * @since 1.0.1-dev
		 */
        return apply_filters( 'adv_can_display_object', $can_display, $this );
    }

    /**
     * Returns the html code needed to display a zone
     */
    public function get_html() {

        // Maybe abort early.
        if ( ! $this->can_display_zone() ) {
            return '';
        }

        $template = new Adv_Zone_Template( $this );
        return $template->get_html();
    }
}
