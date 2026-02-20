<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://wpgeodirectory.com/
 *
 * @package    GeoDir_BuddyPress
 * @subpackage GeoDir_BuddyPress/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    GeoDir_BuddyPress
 * @subpackage GeoDir_BuddyPress/public
 * @author     GeoDirectory <info@wpgeodirectory.com>
 */
class GeoDir_BuddyPress_Public {

	public function __construct() {

	}

    public function enqueue_styles() {

    }

    /**
     * buddypress my listing link.
     *
     * @package GeoDirectory_BuddyPress_Integration
     *
     * @param string $link My listing link.
     * @param string $post_type post_type of the listing.
     * @param string|int $user_id User ID.
     * @return string Modified Listing link.
     */
    public function geodir_buddypress_link_my_listing( $link, $post_type = '', $user_id = '' ) {
        if ( geodir_get_option( 'geodir_buddypress_link_listing' ) ) {
            $gd_post_types = geodir_get_posttypes( 'array' );

            $listing_post_types = geodir_get_option( 'geodir_buddypress_tab_listing' );
            $user_id = (int)$user_id ? $user_id : '';
            if ( !$user_id && is_user_logged_in() ) {
                $user_id = bp_loggedin_user_id();
            }

            $user_domain = geodir_buddypress_get_user_url( $user_id );

            if ( $post_type != '' && !empty( $gd_post_types ) && array_key_exists( $post_type, $gd_post_types ) && !empty( $listing_post_types ) && in_array( $post_type, $listing_post_types ) && $user_domain ) {
                $parent_slug = 'listings';
                $post_type_slug = $gd_post_types[$post_type]['has_archive'];

                $listing_link = trailingslashit( $user_domain . $parent_slug . '/' . $post_type_slug );

                $link = $listing_link;
            }
        }

        return $link;
    }

    /**
     * buddypress favorite listing link.
     *
     * @package GeoDirectory_BuddyPress_Integration
     *
     * @param string $link Favorite listing link.
     * @param string $post_type post_type of the listing.
     * @param string|int $user_id User ID.
     * @return string Modified link.
     */
    public function geodir_buddypress_link_favorite_listing( $link, $post_type = '', $user_id = '' ) {
        if ( geodir_get_option( 'geodir_buddypress_link_favorite' ) ) {
            $gd_post_types = geodir_get_posttypes( 'array' );

            $listing_post_types = geodir_get_option( 'geodir_buddypress_tab_listing' );
            $user_id = (int)$user_id ? $user_id : '';
            if ( !$user_id && is_user_logged_in() ) {
                $user_id = bp_loggedin_user_id();
            }

            $user_domain = geodir_buddypress_get_user_url( $user_id );

            if ( $post_type != '' && !empty( $gd_post_types ) && array_key_exists( $post_type, $gd_post_types ) && !empty( $listing_post_types ) && in_array( $post_type, $listing_post_types ) && $user_domain ) {
                $parent_slug = 'favorites';
                $post_type_slug = $gd_post_types[$post_type]['has_archive'];

                $listing_link = trailingslashit( $user_domain . $parent_slug . '/' . $post_type_slug );

                $link = $listing_link;
            }
        }

        return $link;
    }

    /**
     * Redirect away from gd dashboard to BP registration profile page.
     *
     * @package GeoDirectory_BuddyPress_Integration
     * @param string $body_class HTML body class.
     */
    public function geodir_buddypress_author_redirect($body_class) {
        $gd_dashboard = isset( $_REQUEST['geodir_dashbord'] ) ? true : false;
        $favourite = isset( $_REQUEST['list'] ) && $_REQUEST['list'] == 'favourite' ? true : false;
        $post_type = isset( $_REQUEST['stype'] ) ? $_REQUEST['stype'] : NULL;

        // gd dashboard page
        if ( $gd_dashboard && geodir_get_option( 'geodir_buddypress_link_listing' ) ) {
            $author = get_query_var( 'author_name' ) ? get_user_by( 'slug', get_query_var( 'author_name' ) ) : get_userdata( get_query_var( 'author' ) );

            if ( $favourite && !geodir_get_option( 'geodir_buddypress_link_favorite' ) ) {
                return;
            }

            if ( !empty( $author ) && isset( $author->ID ) && $author_id = $author->ID ) {
                if ( $author_id && ( $user_domain = geodir_buddypress_get_user_url( $author_id ) ) ) {
                    $author_link = $user_domain;

                    if ( $post_type != '' ) {
                        $gd_post_types = geodir_get_posttypes( 'array' );
                        $listing_post_types = geodir_get_option( 'geodir_buddypress_tab_listing' );

                        if ( !empty( $gd_post_types ) && array_key_exists( $post_type, $gd_post_types ) && !empty( $listing_post_types ) && in_array( $post_type, $listing_post_types ) && $user_domain ) {
                            $parent_slug = 'listings';
                            $post_type_slug = $gd_post_types[$post_type]['has_archive'];

                            $author_link = trailingslashit( $user_domain . $parent_slug . '/' . $post_type_slug );
                        }
                    }

                    wp_redirect( $author_link );
                    exit;
                }
            }
        }
        return;
    }

    /**
     * Get the link of the buddypress profile.
     *
     * @param string $author_link The URL to the author's page.
     * @param int    $author_id The author's id.
     * @return string Buddypress profile page.
     */
    public function geodir_buddypress_bp_author_link( $author_link, $author_id ) {
        if ( geodir_get_option( 'geodir_buddypress_link_author' ) ) {
            $author_link = geodir_buddypress_get_user_url( $author_id );
        }
        return $author_link;
    }

    /**
     * Setup navigation.
     *
     * @package GeoDirectory_BuddyPress_Integration
     */
    function geodir_buddypress_setup_nav() {
        $gd_post_types = geodir_get_posttypes( 'array' );

        if ( empty( $gd_post_types ) ) {
            return;
        }

        global $bp;
        $user_domain = geodir_buddypress_get_user_domain();

        // listings
        $listing_post_types = geodir_get_option( 'geodir_buddypress_tab_listing' );

        $position = apply_filters('geodir_buddypress_listing_menu_position', 70);
        if ( geodir_get_option('geodir_buddypress_link_listing') && !empty( $listing_post_types ) ) {
            $parent_slug = 'listings';
            $parent_url = trailingslashit( $user_domain . $parent_slug );

            $parent_nav = array();
            $sub_nav = array();
            $count = 0;
            $total_count = 0;
            foreach ( $listing_post_types as $post_type ) {
                if ( array_key_exists( $post_type, $gd_post_types ) ) {
                    $tab_slug = $gd_post_types[$post_type]['has_archive'];

                    if ( $count == 0 ) {
                        // parent nav
                        $parent_nav = array(
                            'name' => __( 'Listings', 'geodir-buddypress' ),
                            'slug' => $parent_slug,
                            'parent_slug' => $bp->profile->slug,
                            'screen_function' => 'geodir_buddypress_screen_' . $parent_slug,
                            'default_subnav_slug' => $tab_slug,
                            'position' => $position,
                            'item_css_id' => 'gdbuddypress-nav-' . $parent_slug
                        );
                    }

                    // get listing count
                    $listing_count = 0;
                    if ($post_type == 'gd_event') {
                        if (is_user_logged_in() && get_current_user_id() == bp_displayed_user_id()) {
                            $listing_count    = geodir_buddypress_count_total( $post_type );
                        } else {
                            $query_args = array(
                                'posts_per_page' => 10,
                                'is_geodir_loop' => true,
                                'gd_location' 	 => false,
                                'post_type' => $post_type,
                            );
                            $query_args['geodir_event_type'] = 'upcoming';
                            if(class_exists('GeoDir_Event_Query')) {
                                add_filter('geodir_filter_bp_listings_where', array('GeoDir_Event_Query', 'widget_posts_where'), 10, 2);
                            }
                            $query_args['count_only'] = true;
                            $listing_count = geodir_buddypress_get_bp_listings( $query_args );
                        }
                    } else {
                        $listing_count    = geodir_buddypress_count_total( $post_type );
                    }

                    $class    = ( 0 === $listing_count ) ? 'no-count' : 'count';
                    $total_count += $listing_count;

                    // sub nav
                    $sub_nav[] = array(
                        'name' => wp_sprintf( __( '%s <span class="%s">%s</span>', 'geodir-buddypress' ), __( $gd_post_types[$post_type]['labels']['name'], 'geodirectory' ), esc_attr( $class ), number_format_i18n( $listing_count ) ),
                        'slug' => $tab_slug,
                        'parent_url' => $parent_url,
                        'parent_slug' => $parent_slug,
                        'screen_function' => 'geodir_buddypress_screen_' . $parent_slug,
                        'position' => $position,
                        'item_css_id' => 'gdbuddypress-nav-' . $parent_slug . '-' . $tab_slug
                    );

                    $count++;
                }
            }

            if ( !empty( $parent_nav ) ) {
                $class    = ( 0 === $total_count ) ? 'no-count' : 'count';
                $parent_nav['name'] = wp_sprintf( __( 'Listings <span class="%s">%s</span>', 'geodir-buddypress' ), esc_attr( $class ), number_format_i18n( $total_count ) );
            }

            if ( !empty( $parent_nav ) && !empty( $sub_nav ) ) {
                $parent_nav = apply_filters( 'geodir_buddypress_nav_' . $parent_slug, $parent_nav );
                bp_core_new_nav_item( $parent_nav );

                $sub_nav = apply_filters( 'geodir_buddypress_subnav_' . $parent_slug, $sub_nav );
                // Sub nav items are not required
                if ( !empty( $sub_nav ) ) {
                    foreach( $sub_nav as $nav ) {
                        bp_core_new_subnav_item( $nav );
                    }
                }
            }
        }

        // favorites
        $listing_post_types = geodir_get_option( 'geodir_buddypress_tab_listing' );
        $position = apply_filters('geodir_buddypress_favourites_menu_position', 75);
        if ( geodir_get_option( 'geodir_buddypress_link_favorite' ) && !empty( $listing_post_types ) ) {
            $parent_slug = 'favorites';
            $parent_url = trailingslashit( $user_domain . $parent_slug );

            $parent_nav = array();
            $sub_nav = array();
            $count = 0;
            $total_count = 0;
            foreach ( $listing_post_types as $post_type ) {
                if ( array_key_exists( $post_type, $gd_post_types ) ) {
                    $tab_slug = $gd_post_types[$post_type]['has_archive'];

                    if ( $count == 0 ) {
                        $fav_name = __( 'Favorites', 'geodir-buddypress' );
                        $favourite_text = apply_filters('gdbuddypress_favourites_text', $fav_name);
                        // parent nav
                        $parent_nav = array(
                            'name' => $favourite_text,
                            'slug' => $parent_slug,
                            'parent_slug' => $bp->profile->slug,
                            'screen_function' => 'geodir_buddypress_screen_' . $parent_slug,
                            'default_subnav_slug' => $tab_slug,
                            'position' => $position,
                            'item_css_id' => 'gdbuddypress-nav-' . $parent_slug
                        );
                    }

                    // get listing count
                    $listing_count    = geodir_buddypress_count_favorite( $post_type );
                    $class    = ( 0 === $listing_count ) ? 'no-count' : 'count';
                    $total_count += $listing_count;

                    // sub nav
                    $sub_nav[] = array(
                        'name' => wp_sprintf( __( '%s <span class="%s">%s</span>', 'geodir-buddypress' ), __( $gd_post_types[$post_type]['labels']['name'], 'geodirectory' ), esc_attr( $class ), number_format_i18n( $listing_count ) ),
                        'slug' => $tab_slug,
                        'parent_url' => $parent_url,
                        'parent_slug' => $parent_slug,
                        'screen_function' => 'geodir_buddypress_screen_' . $parent_slug,
                        'position' => $position,
                        'item_css_id' => 'gdbuddypress-nav-' . $parent_slug . '-' . $tab_slug
                    );

                    $count++;
                }
            }

            if ( !empty( $parent_nav ) ) {
                $class    = ( 0 === $total_count ) ? 'no-count' : 'count';
                $fav_name = __( 'Favorites', 'geodir-buddypress' );
                $favourite_text = apply_filters('gdbuddypress_favourites_text', $fav_name);
                $parent_nav['name'] = wp_sprintf( __( '%s <span class="%s">%s</span>', 'geodir-buddypress' ), $favourite_text, esc_attr( $class ), number_format_i18n( $total_count ) );
            }

            if ( !empty( $parent_nav ) && !empty( $sub_nav ) ) {
                $parent_nav = apply_filters( 'geodir_buddypress_nav_' . $parent_slug, $parent_nav );
                bp_core_new_nav_item( $parent_nav );

                $sub_nav = apply_filters( 'geodir_buddypress_subnav_' . $parent_slug, $sub_nav );
                // Sub nav items are not required
                if ( !empty( $sub_nav ) ) {
                    foreach( $sub_nav as $nav ) {
                        bp_core_new_subnav_item( $nav );
                    }
                }
            }
        }

        // reviews
        $review_post_types = geodir_get_option( 'geodir_buddypress_tab_review' );
        $position = apply_filters('geodir_buddypress_reviews_menu_position', 80);
        if ( !empty( $review_post_types ) ) {
            $parent_slug = 'reviews';
            $parent_url = trailingslashit( $user_domain . $parent_slug );

            $parent_nav = array();
            $sub_nav = array();
            $count = 0;
            $total_count = 0;
            foreach ( $review_post_types as $post_type ) {
                if ( array_key_exists( $post_type, $gd_post_types ) ) {
                    $tab_slug = $gd_post_types[$post_type]['has_archive'];

                    if ( $count == 0 ) {
                        // parent nav
                        $parent_nav = array(
                            'name' => __( 'Reviews', 'geodir-buddypress' ),
                            'slug' => $parent_slug,
                            'parent_slug' => $bp->profile->slug,
                            'screen_function' => 'geodir_buddypress_screen_' . $parent_slug,
                            'default_subnav_slug' => $tab_slug,
                            'position' => $position,
                            'item_css_id' => 'gdbuddypress-nav-' . $parent_slug
                        );
                    }

                    // get review count
                    $review_count    = geodir_buddypress_count_reviews( $post_type );
                    $class    = ( 0 === $review_count ) ? 'no-count' : 'count';
                    $total_count += $review_count;

                    // sub nav
                    $sub_nav[] = array(
                        'name' => wp_sprintf( __( '%s <span class="%s">%s</span>', 'geodir-buddypress' ), __( $gd_post_types[$post_type]['labels']['name'], 'geodirectory' ), esc_attr( $class ), number_format_i18n( $review_count ) ),
                        'slug' => $tab_slug,
                        'parent_url' => $parent_url,
                        'parent_slug' => $parent_slug,
                        'screen_function' => 'geodir_buddypress_screen_' . $parent_slug,
                        'position' => $position,
                        'item_css_id' => 'gdbuddypress-nav-' . $parent_slug . '-' . $tab_slug
                    );

                    $count++;
                }
            }

            if ( !empty( $parent_nav ) ) {
                $class    = ( 0 === $total_count ) ? 'no-count' : 'count';
                $parent_nav['name'] = wp_sprintf( __( 'Reviews <span class="%s">%s</span>', 'geodir-buddypress' ), esc_attr( $class ), number_format_i18n( $total_count ) );
            }

            if ( !empty( $parent_nav ) && !empty( $sub_nav ) ) {
                $parent_nav = apply_filters( 'geodir_buddypress_nav_' . $parent_slug, $parent_nav );
                bp_core_new_nav_item( $parent_nav );

                $sub_nav = apply_filters( 'geodir_buddypress_subnav_' . $parent_slug, $sub_nav );
                // Sub nav items are not required
                if ( !empty( $sub_nav ) ) {
                    foreach( $sub_nav as $nav ) {
                        bp_core_new_subnav_item( $nav );
                    }
                }
            }
        }
    }

    /**
     * Register activity post type listing.
     *
     * @package GeoDirectory_BuddyPress_Integration
     *
     * @param array $post_types post_types of the listing.
     * @return array Post types array.
     */
    function geodir_buddypress_record_geodir_post_types( $post_types = array() ) {
        $post_types = is_array( $post_types ) && !empty( $post_types ) ? $post_types : array();

        $listing_post_types = geodir_get_option( 'geodir_buddypress_activity_listing' );
        if ( !empty( $listing_post_types ) ) {
            $gd_post_types = geodir_get_posttypes( 'array' );

            foreach ( $listing_post_types as $post_type ) {
                if ( array_key_exists( $post_type, $gd_post_types ) ) {
                    $post_types[] = $post_type;
                }
            }
        }

        return $post_types;
    }

    /**
     * Register activity comment post type.
     *
     * @package GeoDirectory_BuddyPress_Integration
     *
     * @param array $post_types post_types of the listing.
     * @return array Post types array.
     */
    function geodir_buddypress_record_comment_post_types( $post_types = array() ) {
        $post_types = is_array( $post_types ) && !empty( $post_types ) ? $post_types : array();

        $listing_post_types = geodir_get_option( 'geodir_buddypress_activity_review' );
        if ( !empty( $listing_post_types ) ) {
            $gd_post_types = geodir_get_posttypes( 'array' );

            foreach ( $listing_post_types as $post_type ) {
                if ( array_key_exists( $post_type, $gd_post_types ) ) {
                    $post_types[] = $post_type;
                }
            }

            $post_types = array_unique( $post_types );
        }

        return $post_types;
    }

    /**
     * action for listing post type.
     *
     * @package GeoDirectory_BuddyPress_Integration
     *
     * @param string $action BuddyPress Constructed activity action.
     * @param object $activity BuddyPress Activity data object.
     * @return mixed Modified Action.
     */
    function geodir_buddypress_new_listing_activity( $action, $activity ) {
        global $post;
        switch_to_blog( $activity->item_id );
        $post_info = get_post( $activity->secondary_item_id );
        $post_type = !empty( $post_info ) ? $post_info->post_type : '';
        restore_current_blog();
        $gd_post_types = geodir_get_posttypes( 'array' );

        if ( !empty( $post_type ) && array_key_exists( $post_type, $gd_post_types ) ) {
            if ( function_exists( 'bp_blogs_get_blogmeta' ) ) {
                $blog_url  = bp_blogs_get_blogmeta( $activity->item_id, 'url' );
                $blog_name = bp_blogs_get_blogmeta( $activity->item_id, 'name' );
            } else {
                $blog_url  = '';
                $blog_name = '';
            }

            if ( empty( $blog_url ) || empty( $blog_name ) ) {
                $blog_url  = get_home_url( $activity->item_id );
                $blog_name = get_blog_option( $activity->item_id, 'blogname' );

                if ( function_exists( 'bp_blogs_update_blogmeta' ) ) {
                    bp_blogs_update_blogmeta( $activity->item_id, 'url', $blog_url );
                    bp_blogs_update_blogmeta( $activity->item_id, 'name', $blog_name );
                }
            }

            $post_url = esc_url( add_query_arg( 'p', $activity->secondary_item_id, trailingslashit( $blog_url )) );

            $post_title = isset($activity->id) ? bp_activity_get_meta( $activity->id, 'post_title' ) : "";

            // Should only be empty at the time of post creation
            if ( empty( $post_title ) ) {
                if ( is_a( $post_info, 'WP_Post' ) ) {
                    $post_title = $post_info->post_title;
                    if ( ! empty( $activity->id ) ) {bp_activity_update_meta( $activity->id, 'post_title', $post_title );}
                }
            }

            $post_link  = '<a href="' . $post_url . '">' . $post_title . '</a>';

            $user_link = bp_core_get_userlink( $activity->user_id );

            $post_type_name = geodir_strtolower( __( $gd_post_types[$post_type]['labels']['singular_name'], 'geodirectory' ) );

            if ( is_multisite() ) {
                $action  = sprintf( __( '%1$s listed a new %2$s, %3$s, on the site %4$s', 'geodir-buddypress' ), $user_link, $post_type_name, $post_link, '<a href="' . esc_url( $blog_url ) . '">' . esc_html( $blog_name ) . '</a>' );
            } else {
                $action  = sprintf( __( '%1$s listed a new %2$s, %3$s', 'geodir-buddypress' ), $user_link, $post_type_name, $post_link );
            }
        }

        return apply_filters( 'geodir_buddypress_new_listing_activity', $action, $activity, $post_type );
    }

    /**
     * action for listing post type comment.
     *
     * @package GeoDirectory_BuddyPress_Integration
     *
     * @global array $geodir_buddypress_recorded Store recorded activities.
     *
     * @param string $action BuddyPress Constructed activity action.
     * @param object $activity BuddyPress Activity data object.
     * @return string Modified Action.
     */
    function geodir_buddypress_new_listing_comment_activity( $action, $activity ) {
		global $geodir_buddypress_recorded;

		if ( $activity->type != 'activity_comment' && $activity->type != 'new_blog_comment' ) {
			return $action;
		}

		if ( empty( $geodir_buddypress_recorded ) ) {
			$geodir_buddypress_recorded = array();
		}

		$record_id = $activity->type . ':' . $activity->item_id . ':';
		if ( ! empty( $activity->secondary_item_id ) ) {
			$record_id .= $activity->secondary_item_id;
		}

		// Activity recorded already.
		if ( in_array( $record_id, $geodir_buddypress_recorded ) ) {
			return $action;
		}

		$geodir_buddypress_recorded[] = $record_id;

		switch_to_blog( $activity->item_id );

		$parent_activity = new BP_Activity_Activity( $activity->item_id );

		$post_comment_id = 0;
		if ( ! empty( $parent_activity ) && $parent_activity->type == 'new_blog_post' ) {
			$post_type = get_post_type( (int) $parent_activity->secondary_item_id );

			if ( geodir_is_gd_post_type( $post_type ) ) {
				$post_comment_id = bp_activity_get_meta( $activity->id, "bp_blogs_{$post_type}_comment_id" );
			}
		}

		if ( empty( $post_comment_id ) ) {
			$post_comment_id = $activity->secondary_item_id;
		}

		$comment = $post_comment_id ? get_comment( $post_comment_id ) : NULL;
        $comment_post_ID = ! empty( $comment->comment_post_ID ) ? $comment->comment_post_ID : NULL;
		$post_type = ! empty( $comment_post_ID ) ? get_post_type( (int) $comment_post_ID ) : NULL;
		$post_info = ! empty( $comment_post_ID ) ? get_post( $comment_post_ID ) : NULL;

		restore_current_blog();

        if ( geodir_is_gd_post_type( $post_type ) ) {
            if ( function_exists( 'bp_blogs_get_blogmeta' ) ) {
                $blog_url  = bp_blogs_get_blogmeta( $activity->item_id, 'url' );
                $blog_name = bp_blogs_get_blogmeta( $activity->item_id, 'name' );
            } else {
                $blog_url  = '';
                $blog_name = '';
            }

            if ( empty( $blog_url ) || empty( $blog_name ) ) {
                $blog_url  = get_home_url( $activity->item_id );
                $blog_name = get_blog_option( $activity->item_id, 'blogname' );

                if ( function_exists( 'bp_blogs_update_blogmeta' ) ) {
                    bp_blogs_update_blogmeta( $activity->item_id, 'url', $blog_url );
                    bp_blogs_update_blogmeta( $activity->item_id, 'name', $blog_name );
                }
            }

            $post_url   = bp_activity_get_meta( $activity->id, 'post_url' );
            $post_title = bp_activity_get_meta( $activity->id, 'post_title' );

            // Should only be empty at the time of post creation
            if ( empty( $post_url ) || empty( $post_title ) ) {
                if ( ! empty( $comment_post_ID ) ) {
                    $post_url = esc_url( add_query_arg( 'p', $comment_post_ID, trailingslashit( $blog_url ) ));
                    bp_activity_update_meta( $activity->id, 'post_url', $post_url );

                    if ( is_a( $post_info, 'WP_Post' ) ) {
                        $post_title = $post_info->post_title;
                        bp_activity_update_meta( $activity->id, 'post_title', $post_title );
                    }
                }
            }

            $post_link = '<a href="' . $post_url . '">' . $post_title . '</a>';
            $user_link = bp_core_get_userlink( $activity->user_id );

            $post_type_name = geodir_strtolower( geodir_post_type_singular_name( $post_type , true ) );

            if ( is_multisite() ) {
                $action  = sprintf( __( '%1$s commented on the %2$s, %3$s, on the site %4$s', 'geodir-buddypress' ), $user_link, $post_type_name, $post_link, '<a href="' . esc_url( $blog_url ) . '">' . esc_html( $blog_name ) . '</a>' );
            } else {
                $action  = sprintf( __( '%1$s commented on the %2$s, %3$s', 'geodir-buddypress' ), $user_link, $post_type_name, $post_link );
            }
        }

        return apply_filters( 'geodir_buddypress_new_listing_comment_activity', $action, $activity, $post_type );
    }

    /**
     * set parent activity_id to 1 if listing comment has not parent activity_id.
     *
     * @package GeoDirectory_BuddyPress_Integration
     *
     * @param int $activity_id BP activity ID.
     * @return int BP activity ID.
     */
    function geodir_buddypress_get_activity_id( $activity_id ) {
        $version = bp_get_version();
        if (version_compare( $version, '2.4', '>=' )) {
            return $activity_id;
        }

        if ( ! $activity_id && ! defined( 'BP_PLATFORM_VERSION' ) ) {
            $gd_post_types = geodir_get_posttypes( 'array' );

            $comment_post_ID = isset( $_POST['comment_post_ID'] ) ? $_POST['comment_post_ID'] : 0;
            $comment_post = get_post( $comment_post_ID );

            if ( !empty( $comment_post ) && isset( $comment_post->post_type ) && $comment_post->post_type && !empty( $gd_post_types ) && array_key_exists( $comment_post->post_type, $gd_post_types ) ) {
                $activity_id = 1;
            }
        }
        return $activity_id;
    }

    /**
     * Append the featured image for the activity content.
     *
     * @package GeoDirectory_BuddyPress_Integration
     *
     * @param string $content The appended text for the activity content.
     * @return string The activity excerpt.
     */
    function geodir_buddypress_bp_activity_featured_image( $content = '' ) {
        global $activities_template;
        if (!(!empty($activities_template) && isset($activities_template->activity) && !empty($activities_template->activity))) {
            return $content;
        }

        $activity_name = bp_get_activity_object_name();
        $activity_type = bp_get_activity_type();
        $item_id = bp_get_activity_secondary_item_id();

        $gd_post_types = geodir_get_posttypes();
        $post_type = get_post_type($item_id);

        if ($item_id > 0 && ($activity_name == 'activity' || $activity_name == 'blogs') && ($activity_type == 'new_blog_post' || $activity_type == 'new_blog_' . $post_type) && in_array($post_type, $gd_post_types) && geodir_get_option('geodir_buddypress_show_feature_image')) {

            $image = wp_get_attachment_image_src(  get_post_thumbnail_id( $item_id ), 'medium' );
            if (empty($image) || empty($image[0]) ) {
                $post_type_obj = $post_type ? get_post_type_object( $post_type ) : array();
                $image = wp_get_attachment_image_src(  $post_type_obj->default_image, 'medium' );
            }
            
            if (!empty($image) && !empty($image[0])) {
                $listing_title = geodir_get_post_meta( $item_id, 'post_title', true );

                $featured_image = '<a class="gdbp-feature-image" href="' . get_permalink( $item_id ) . '" title="' . esc_attr( $listing_title ) . '"><img alt="' . esc_attr( $listing_title ) . '" src="' . $image[0] . '" /></a>';

                /**
                 * Filter the new listing featured image in activity.
                 *
                 * @param string $featured_image Featured image content.
                 * @param int $item_id Activity item id.
                 * @param string $activity_name Current activity name.
                 * @param string $activity_type Current activity type.
                 */
                $featured_image = apply_filters( 'geodir_buddypress_bp_activity_featured_image', $featured_image, $item_id, $activity_name, $activity_type );

                $content = preg_replace('/<img[^>]*>/Ui', '', $content);

                $content = $featured_image . $content;
            }
        }

        return $content;
    }

    /**
     * Filters whether or not blog and forum activity stream comments are disabled for listings.
     *
     * @param bool $status Whether or not blog and forum activity stream comments are disabled for listings.
     * @return bool $status Activity comment disabled status.
     */
    function geodir_buddypress_disable_comment_as_review($status) {
        $action = isset($_POST['action']) ? $_POST['action'] : '';

        if ($action == 'new_activity_comment' && !empty($_POST['comment_id'])) {
            $comment_id = $_POST['comment_id'];
            $activity = new BP_Activity_Activity($comment_id);

            if (!empty($activity) && isset($activity->secondary_item_id)) {
                $activity_type = $activity->type;

                if ($activity_type == 'activity_comment') {
                    $activity = new BP_Activity_Activity($activity->item_id);

                    if (empty($activity)) {
                        return $status;
                    }

                    $activity_type = $activity->type;
                }
                $item_id = $activity->secondary_item_id;

                $gd_post_types = geodir_get_posttypes();
                $post_type = get_post_type($item_id);

                if ($item_id > 0 && ($activity_type == 'new_blog_post' || $activity_type == 'new_' . $post_type) && in_array($post_type, $gd_post_types)) {
                    $status = true;
                }
            }
        }

        return $status;
    }

    /**
     * Register notification component
     *
     */
    function register_component(  $component_names = array() ) {

        // Force $component_names to be an array
        if ( ! is_array( $component_names ) ) {
            $component_names = array();
        }

        // Add 'geodirectory' component to registered components array
        array_push( $component_names, 'geodirectory' );
        // Return component's with 'geodirectory' appended
        return $component_names;

    }

    /**
     * Notify listing owners when they get a new review
     *
     */
    function notify_listing_owner( $maybe_notify, $comment_id ) {

        $comment = get_comment( $comment_id );

        if ( empty( $comment->comment_parent ) && ! empty( $comment->comment_post_ID ) && geodir_is_gd_post_type( get_post_type( $comment->comment_post_ID ) ) ) {
            
            $post   = get_post( $comment->comment_post_ID );

            if ( bp_is_active( 'notifications' ) ) {
                bp_notifications_add_notification( array(
                'user_id'           => $post->post_author,
                'item_id'           => $comment_id,
                'secondary_item_id' => $comment->user_id,
                'component_name'    => 'geodirectory',
                'component_action'  => 'new_review',
                'date_notified'     => bp_core_current_time(),
                'is_new'            => 1,
                ) );
            }
        }
    
        return $maybe_notify;

    }

    /**
     * Formats a notification for display
     *
     */
    function format_notifications( $deprecated, $item_id, $secondary_item_id, $total_items, $format, $action = '', $component = '', $notification_id = 0 ) {
        // Reviews
        if ( $component == 'geodirectory' && 'new_review' === $action ) {
            $comment      = get_comment( $item_id );
            if ( empty( $comment ) ) {
                return $deprecated;
            }

            $author_name  = $comment->comment_author;
            $post         = get_post( $comment->comment_post_ID );
            if ( empty( $post ) ) {
                return $deprecated;
            }

            if( $post->post_author == $secondary_item_id ) {
                $author_name  = __( 'You', 'geodir-buddypress' );
            }
            if ( (int) $total_items > 1 ) {
                $custom_title = sprintf( __( 'You have %1$d new reviews', 'geodir-buddypress' ) , (int) $total_items );
                $domain  = bp_loggedin_user_domain();
                $custom_link = trailingslashit( $domain . bp_get_notifications_slug() . '/unread' );
                $custom_text = sprintf( __( 'You have %1$d new reviews', 'geodir-buddypress' ) , (int) $total_items );
            } else{
                $custom_title = sprintf( __( '%s left a review on %s', 'geodir-buddypress' ) , $author_name, get_the_title( $comment->comment_post_ID ));
                $custom_link  = get_comment_link( $comment );
                $custom_text = sprintf( __( '%s left a review on %s', 'geodir-buddypress' ) , $author_name, get_the_title( $comment->comment_post_ID ));
            }
            // WordPress Toolbar
            if ( 'string' === $format ) {
                $return = apply_filters( 'geodir_buddypress_notification', '<a href="' . esc_url( $custom_link ) . '" title="' . esc_attr( $custom_title ) . '">' . esc_html( $custom_text ) . '</a>', $custom_text, $custom_link, $comment, $author_name );
            
            // BuddyBar
            } else {

                $return = apply_filters( 'geodir_buddypress_notification_bar', array(
                    'text' => $custom_text,
                    'link' => $custom_link
                ), $custom_link, (int) $total_items, $custom_text, $custom_title, $comment, $author_name );
            }
    
            return $return;
        }

        return $deprecated;
    }

    public function gd_listings_post_author( $post_author, $instance = array(), $id_base = '' ) {
        if ( $id_base == 'gd_listings' && ! empty( $instance['post_author'] ) && $instance['post_author'] == 'current_author' && function_exists( 'bp_displayed_user_id' ) && ( $user_id = bp_displayed_user_id() ) ) {
            $post_author = $user_id;
        }

        return $post_author;
    }

    public function gd_listings_favorites_by_user( $favorites_by_user, $instance = array(), $id_base = '' ) {
        if ( $id_base == 'gd_listings' && ! empty( $instance['favorites_by_user'] ) && $instance['favorites_by_user'] == 'current_author' && function_exists( 'bp_displayed_user_id' ) && ( $user_id = bp_displayed_user_id() ) ) {
            $favorites_by_user = $user_id;
        }

        return $favorites_by_user;
    }
}
