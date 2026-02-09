<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class for templates.
 *
 * @class    GeoDir_BuddyPress_Template
 */
class GeoDir_BuddyPress_Template{

    public function __construct(){
        add_action('init', array($this, 'init_multirating_get_comment_author_link'));
    }

    /**
     * BuddyPress Listings Tab content.
     *
     * @package GeoDir_BuddyPress
     *
     * @param array $args Query arguments.
     */
    public static function geodir_buddypress_listings_html( $args = array() ) {
		if ( geodir_design_style() ) {
			self::geodir_buddypress_listings_html_aui( $args );
			return;
		}
        global $geodirectory, $posts_per_page, $found_posts, $paged, $geodir_is_widget_listing, $gd_layout_class;
        $current_posts_per_page = $posts_per_page;
        $current_found_posts = $found_posts;
        $current_paged = $paged;

        if ( empty( $paged ) && bp_action_variable( 0 ) == 'page' && ( $_paged = absint( bp_action_variable( 1 ) ) ) ) {
            $paged = $_paged;
        }

        $posts_per_page = $posts_per_page > 0 ? $posts_per_page : 5;
        $posts_per_page = geodir_get_option( 'geodir_buddypress_listings_count', $posts_per_page );

        $post_type = $args['post_type'];
        $sort_by = $args['sort_by'];
        $list_sort = apply_filters( 'gdbp_listing_list_sort', $sort_by );
        $posts_per_page = apply_filters( 'gdbp_listing_post_limit', $posts_per_page );
        $post_type_name = !empty( $args['post_type_name'] ) ? geodir_strtolower( $args['post_type_name'] ) : __( 'listings', 'geodir-buddypress' );
		// Layout
		$_layout = geodir_get_option( 'bp_listings_layout' );
		if ( $_layout === false ) {
			$layout = geodir_grid_view_class( 2 );
		} else {
			$layout = $_layout;
		}
		$layout = apply_filters( 'geodir_buddypress_listings_layout', $layout, $args );
		$gd_layout_class = geodir_convert_listing_view_class( $layout );

		// Elementor
		$skin_id = 0;
		$skin_active = false;
		$elementor_wrapper_class = '';
		if ( defined( 'ELEMENTOR_PRO_VERSION' ) ) {
			$skin_id = geodir_get_option( 'bp_listings_skin_id' );
			$skin_id = apply_filters( 'geodir_buddypress_listings_skin_id', $skin_id, $args );

			if ( $skin_id ) {
				if ( get_post_status ( $skin_id ) == 'publish' ) {
					$skin_active = true;
				}

				if ( $skin_active ) {
					$columns = $_layout !== false ? absint( $_layout ) : 1;
					if ( $columns == '0' ) {
						$columns = 6; // we have no 6 row option to lets use list view
					}
					$elementor_wrapper_class = ' elementor-element elementor-element-9ff57fdx elementor-posts--thumbnail-top elementor-grid-' . $columns . ' elementor-grid-tablet-2 elementor-grid-mobile-1 elementor-widget elementor-widget-posts mt-3';
				}
			}
		}

        add_filter( 'geodir_bp_listings_orderby', 'geodir_buddypress_posts_orderby', 99, 5 );
        // pagination
        add_action( 'geodir_after_listing', 'geodir_buddypress_pagination', 20 );

        $query_args = array(
            'posts_per_page' => $posts_per_page,
            'is_geodir_loop' => true,
            'gd_location' 	 => false,
            'post_type' => $post_type,
            'order_by' => $list_sort
        );
        
        if ($post_type == 'gd_event') {
            if (is_user_logged_in() && get_current_user_id() == bp_displayed_user_id()) {

            } else {
                $query_args['geodir_event_type'] = 'upcoming';
                add_filter( 'geodir_filter_bp_listings_where', 'geodir_filter_event_widget_listings_where', 10, 2 );
            }
        }

        if ( (bool)bp_is_current_component( 'favorites' ) ) {
            $query_args['filter_favorite'] = true;
        }

        $query_args['count_only'] = true;
        $found_posts = geodir_buddypress_get_bp_listings( $query_args );
        $query_args['count_only'] = false;

        $widget_listings = geodir_buddypress_get_bp_listings( $query_args );

        $template = apply_filters( "geodir_template_part-widget-listing-listview", geodir_locate_template('content-widget-listing.php') );

        if ( empty( $widget_listings ) ) {
            ?>
            <div class="info" id="message"><p><?php echo wp_sprintf( __( 'There were no %s found.', 'geodir-buddypress' ), $post_type_name ); ?></p></div>
            <?php
        } else {
            // currently set values
            global $post, $geodir_event_widget_listview, $map_jason, $map_canvas_arr, $found_posts;

            $current_post = $post;
            $current_map_jason = $map_jason;
            $current_map_canvas_arr = $map_canvas_arr;
            $geodir_is_widget_listing = true;
            $my_lisitngs = false;
            $old_event_widget_listview = $geodir_event_widget_listview;
            if ( bp_loggedin_user_id() && bp_displayed_user_id() == bp_loggedin_user_id() ) {
                $my_lisitngs = true;
                $_REQUEST['geodir_dashbord'] = true;
            }

            if ( $post_type == 'gd_event' ) {
                $geodir_event_widget_listview = true;
            }

            echo '<div class="clearfix geodir-loop-actions-container">';
            geodir_display_sort_options($post_type);
			if ( ! $skin_active ) {
				geodir_extra_loop_actions();
			}
            echo '</div>';

            //echo '<div class="geodir-loop-container">';
			echo '<div class="geodir-loop-container geodir_locations geodir_location_listing position-relative ' . $elementor_wrapper_class . '">';
			if ( ! $skin_active ) {
				include( $template );
			} else {
				$skin_row_gap = geodir_get_option( 'bp_listings_skin_row_gap' );
				$skin_column_gap = geodir_get_option( 'bp_listings_skin_column_gap' );
				$row_gap = ! empty( $skin_row_gap ) ? absint( $skin_row_gap ) : '35';
				$column_gap = ! empty( $skin_column_gap ) ? absint( $skin_column_gap ) : '30';

				geodir_get_template( 'elementor/content-widget-listing.php', array( 'widget_listings' => $widget_listings, 'skin_id' => $skin_id, 'columns' => $columns, 'column_gap'=> $column_gap, 'row_gap' => $row_gap ) );
		    }
            echo '</div>';

            echo '<div class="clearfix geodir-loop-paging-container">';
            do_action( 'geodir_after_listing' );
            echo '</div>';

            // release original values
            global $geodir_event_widget_listview, $map_jason, $map_canvas_arr;

            $GLOBALS['post'] = $current_post;
            setup_postdata( $current_post );
            $geodir_event_widget_listview = $old_event_widget_listview;
            $map_jason = $current_map_jason;
            $map_canvas_arr = $current_map_canvas_arr;
            if ( $my_lisitngs ) {
                unset( $_REQUEST['geodir_dashbord'] );
            }
        }

        global $posts_per_page, $paged;
        $posts_per_page = $current_posts_per_page;
        $found_posts = $current_found_posts;
        $paged = $current_paged;
    }

	/**
     * BuddyPress Listings Tab content for AUI style.
     *
     * @package GeoDir_BuddyPress
     *
     * @param array $args Query arguments.
     */
    public static function geodir_buddypress_listings_html_aui( $args = array() ) {
        global $geodirectory, $posts_per_page, $found_posts, $paged, $geodir_is_widget_listing, $gd_layout_class;
        $current_posts_per_page = $posts_per_page;
        $current_found_posts = $found_posts;
        $current_paged = $paged;

        if ( empty( $paged ) && bp_action_variable( 0 ) == 'page' && ( $_paged = absint( bp_action_variable( 1 ) ) ) ) {
            $paged = $_paged;
        }

        $posts_per_page = $posts_per_page > 0 ? $posts_per_page : 5;
        $posts_per_page = geodir_get_option( 'geodir_buddypress_listings_count', $posts_per_page );

        $post_type = $args['post_type'];
        $sort_by = $args['sort_by'];
        $list_sort = apply_filters( 'gdbp_listing_list_sort', $sort_by );
        $posts_per_page = apply_filters( 'gdbp_listing_post_limit', $posts_per_page );
        $post_type_name = !empty( $args['post_type_name'] ) ? $args['post_type_name'] : __( 'listings', 'geodir-buddypress' );
		// Layout
		$_layout = geodir_get_option( 'bp_listings_layout' );
		if ( $_layout === false ) {
			$layout = geodir_grid_view_class( 2 );
		} else {
			$layout = $_layout;
		}
		$layout = apply_filters( 'geodir_buddypress_listings_layout', $layout, $args );
		$gd_layout_class = geodir_convert_listing_view_class( $layout );

		// Elementor
		$skin_id = 0;
		$skin_active = false;
		$elementor_wrapper_class = '';
		if ( defined( 'ELEMENTOR_PRO_VERSION' ) ) {
			$skin_id = geodir_get_option( 'bp_listings_skin_id' );
			$skin_id = apply_filters( 'geodir_buddypress_listings_skin_id', $skin_id, $args );
		
			if ( $skin_id ) {
				if ( get_post_status ( $skin_id ) == 'publish' ) {
					$skin_active = true;
				}

				if ( $skin_active ) {
					$columns = $_layout !== false ? absint( $_layout ) : 1;
					if ( $columns == '0' ) {
						$columns = 6; // we have no 6 row option to lets use list view
					}
					$elementor_wrapper_class = ' elementor-element elementor-element-9ff57fdx elementor-posts--thumbnail-top elementor-grid-' . $columns . ' elementor-grid-tablet-2 elementor-grid-mobile-1 elementor-widget elementor-widget-posts mt-3 ';
				}
			}
		}

		$design_style = geodir_design_style();

        add_filter( 'geodir_bp_listings_orderby', 'geodir_buddypress_posts_orderby', 99, 5 );
        // pagination
        add_filter( 'previous_posts_link_attributes', 'geodir_buddypress_previous_posts_link_attributes_aui', 99, 1 );
        add_filter( 'next_posts_link_attributes', 'geodir_buddypress_next_posts_link_attributes_aui', 99, 1 );
        add_action( 'geodir_after_listing', 'geodir_buddypress_pagination_aui', 20 );

        $query_args = array(
            'posts_per_page' => $posts_per_page,
            'is_geodir_loop' => true,
            'gd_location' 	 => false,
            'post_type' => $post_type,
            'order_by' => $list_sort
        );
        
        if ($post_type == 'gd_event') {
            if (is_user_logged_in() && get_current_user_id() == bp_displayed_user_id()) {

            } else {
                $query_args['geodir_event_type'] = 'upcoming';
                add_filter( 'geodir_filter_bp_listings_where', 'geodir_filter_event_widget_listings_where', 10, 2 );
            }
        }

        if ( (bool)bp_is_current_component( 'favorites' ) ) {
            $query_args['filter_favorite'] = true;
        }

        $query_args['count_only'] = true;
        $found_posts = geodir_buddypress_get_bp_listings( $query_args );
        $query_args['count_only'] = false;

        $widget_listings = geodir_buddypress_get_bp_listings( $query_args );

        if ( empty( $widget_listings ) ) {
           $empty_message = aui()->alert(
				array(
					'type'=> 'info',
					'content'=> wp_sprintf( __( 'There were no %s found.', 'geodir-buddypress' ), $post_type_name )
				)
			);

			$empty_message = apply_filters( "geodir_buddypress_template_no_listings_html", $empty_message, $post_type_name );

			echo $empty_message;
        } else {
            // currently set values
            global $post, $geodir_event_widget_listview, $map_jason, $map_canvas_arr, $found_posts;

            $current_post = $post;
            $current_map_jason = $map_jason;
            $current_map_canvas_arr = $map_canvas_arr;
            $geodir_is_widget_listing = true;
            $my_lisitngs = false;
            $old_event_widget_listview = $geodir_event_widget_listview;
            if ( bp_loggedin_user_id() && bp_displayed_user_id() == bp_loggedin_user_id() ) {
                $my_lisitngs = true;
                $_REQUEST['geodir_dashbord'] = true;
            }

            if ( $post_type == 'gd_event' ) {
                $geodir_event_widget_listview = true;
            }

            echo '<div class="clearfix geodir-loop-actions-container bsui"><div class="justify-content-end mb-3" role="toolbar" aria-label="' . esc_attr__( 'Listing sort and view options', 'geodir-buddypress' ) . '">';
            geodir_display_sort_options($post_type);
            if ( ! $skin_active ) {
				geodir_extra_loop_actions();
			}
            echo '</div></div>';

            // All listings html
			$column_gap = geodir_get_option( 'bp_listings_column_gap' );
			$row_gap = geodir_get_option( 'bp_listings_row_gap' );
			$card_border = geodir_get_option( 'bp_listings_card_border' );
			$card_shadow = geodir_get_option( 'bp_listings_card_shadow' );
			
			// Card border class
			$card_border_class = '';
			if ( ! empty( $card_border ) ) {
				if ( $card_border == 'none' ) {
					$card_border_class = 'border-0';
				} else {
					$card_border_class = 'border-' . sanitize_html_class( $card_border );
				}
			}

			// Card shadow class
			$card_shadow_class = '';
			if ( ! empty( $card_shadow ) ) {
				if ( $card_shadow == 'small' ) {
					$card_shadow_class = 'shadow-sm';
				} elseif ( $card_shadow == 'medium' ) {
					$card_shadow_class = 'shadow';
				} elseif( $card_shadow == 'large' ) {
					$card_shadow_class = 'shadow-lg';
				}
			}

            //echo '<div class="geodir-loop-container bsui">';
			echo '<div class="bsui geodir-loop-container position-relative ' . $elementor_wrapper_class . '">';

			if ( ! $skin_active ) {
				$template = $design_style . '/content-widget-listing.php';

				echo geodir_get_template_html( $template, array(
					'widget_listings' => $widget_listings,
					'column_gap_class' => $column_gap ? 'mb-'.absint( $column_gap ) : 'mb-4',
					'row_gap_class' => $row_gap ? 'px-'.absint($row_gap) : '',
					'card_border_class' => $card_border_class,
					'card_shadow_class' => $card_shadow_class,
				) );
			} else {
				$skin_row_gap = geodir_get_option( 'bp_listings_skin_row_gap' );
				$skin_column_gap = geodir_get_option( 'bp_listings_skin_column_gap' );
				$row_gap = ! empty( $skin_row_gap ) ? absint( $skin_row_gap ) : '35';
				$column_gap = ! empty( $skin_column_gap ) ? absint( $skin_column_gap ) : '30';

				geodir_get_template( 'elementor/content-widget-listing.php', array( 'widget_listings' => $widget_listings, 'skin_id' => $skin_id, 'columns' => $columns, 'column_gap'=> $column_gap, 'row_gap' => $row_gap ) );
		    }

            echo '</div>';

            echo '<div class="clearfix geodir-loop-paging-container bsui">';
            do_action( 'geodir_after_listing' );
            echo '</div>';

            // release original values
            global $geodir_event_widget_listview, $map_jason, $map_canvas_arr;

            $GLOBALS['post'] = $current_post;
            setup_postdata( $current_post );
            $geodir_event_widget_listview = $old_event_widget_listview;
            $map_jason = $current_map_jason;
            $map_canvas_arr = $current_map_canvas_arr;
            if ( $my_lisitngs ) {
                unset( $_REQUEST['geodir_dashbord'] );
            }
        }

        global $posts_per_page, $paged;
        $posts_per_page = $current_posts_per_page;
        $found_posts = $current_found_posts;
        $paged = $current_paged;
    }

    /**
     * BuddyPress reviews Tab template.
     *
     * @package GeoDir_BuddyPress
     *
     * @param string $comment_template Comment template path.
     * @return string Modified Comment template path.
     */
    public static function geodir_buddypress_comment_template( $comment_template ) {
        $design_style = geodir_design_style();

        $template = $design_style ? GEODIR_BUDDYPRESS_PLUGIN_PATH . '/templates/' . $design_style . '/reviews.php' : GEODIR_BUDDYPRESS_PLUGIN_PATH . '/templates/reviews.php';

        return $template;
    }

    /**
     * BuddyPress reviews Tab comment class.
     *
     * @package GeoDir_BuddyPress
     *
     * @param array       $classes    An array of comment classes.
     * @param string      $class      A comma-separated list of additional classes added to the list.
     * @param int         $comment_id The comment id.
     * @param object   	  $comment    The comment
     * @param int|WP_Post $post_id    The post ID or WP_Post object.
     * @return array Modified HTML class array.
     */
    public static function geodir_buddypress_comment_class( $classes, $class, $comment_id, $comment, $post_id ) {
        $classes[] = 'bypostauthor';
        return $classes;
    }

    /**
     * BuddyPress reviews Tab content.
     *
     * @package GeoDir_BuddyPress
     *
     * @global bool $gd_buddypress_reviews True if buddypress reviews tab.
     *
     * @param array $args Query arguments.
     */
    public static function geodir_buddypress_reviews_html( $args = array() ) {
        global $gd_buddypress_reviews;
        $gd_buddypress_reviews = true;

        $post_type = $args['post_type'];

        add_filter( 'comments_template', array(__CLASS__, 'geodir_buddypress_comment_template'), 100, 1 );
        add_filter( 'comment_class', array(__CLASS__, 'geodir_buddypress_comment_class'), 100, 5 );
        add_filter( 'comments_clauses', array(__CLASS__, 'geodir_buddypress_comments_clauses'), 1000, 1 );

        if (class_exists('WC_Comments')) {
            remove_filter( 'comments_clauses', array( 'WC_Comments', 'exclude_order_comments' ) );
            remove_filter( 'comments_clauses', array( 'WC_Comments', 'exclude_webhook_comments' ), 10 );
        }

        /* Show Comment Rating */
        if ( defined( 'GEODIR_REVIEWRATING_PLUGINDIR_URL' ) && ( geodir_get_option( 'geodir_reviewrating_enable_rating' ) || geodir_get_option( 'geodir_reviewrating_enable_images' ) || geodir_get_option( 'geodir_reviewrating_enable_review' ) || geodir_get_option( 'geodir_reviewrating_enable_sorting' ) || geodir_get_option( 'geodir_reviewrating_enable_sharing' ) ) ) {
            global $geodir_post_type;
            $geodir_post_type = $post_type;

            wp_register_script( 'geodir-reviewrating-review-script', GEODIR_REVIEWRATING_PLUGINDIR_URL.'/assets/js/comments-script.min.js' );
            wp_enqueue_script( 'geodir-reviewrating-review-script' );

            wp_register_style( 'geodir-reviewratingrating-style', GEODIR_REVIEWRATING_PLUGINDIR_URL .'/assets/css/style.css' );
            wp_enqueue_style( 'geodir-reviewratingrating-style' );
        }

        $review_limit = apply_filters( 'gdbp_review_limit', 5 );
        $author_id = bp_displayed_user_id() ? bp_displayed_user_id() : bp_loggedin_user_id();
        $logged_id = bp_loggedin_user_id();

        $defaults = array();
        $args = array(
            'post_type' => $post_type,
            'order'   => 'DESC',
            'orderby' => 'comment_date_gmt',
            'status'  => 'approve',
            'user_id'  => $author_id,
            'number' => $review_limit
        );

        if ( $logged_id && $logged_id == $author_id ) {
            $args['include_unapproved'] = array( $author_id );
        }
        $args = wp_parse_args( $args, $defaults );

        global $wp_query;
        $query = new WP_Comment_Query;
        $comments = $query->query( $args );

        $wp_query->comments = apply_filters( 'comments_array', $comments, '' );
        $comments = $wp_query->comments;
        $wp_query->comment_count = count($wp_query->comments);

        $overridden_cpage = false;
        if ( '' == get_query_var('cpage') && geodir_get_option('page_comments') ) {
            set_query_var( 'cpage', 'newest' == geodir_get_option('default_comments_page') ? get_comment_pages_count() : 1 );
            $overridden_cpage = true;
        }

        if ( !defined('COMMENTS_TEMPLATE') )
            define('COMMENTS_TEMPLATE', true);

        $file = '/comments.php';
        $theme_template = STYLESHEETPATH . $file;
        /**
         * Filter the path to the theme template file used for the comments template.
         *
         * @param string $theme_template The path to the theme template file.
         */
        $include = apply_filters( 'comments_template', $theme_template );

        if ( file_exists( $include ) )
            require( $include );
        elseif ( file_exists( TEMPLATEPATH . $file ) )
            require( TEMPLATEPATH . $file );
        else // Backward compat code will be removed in a future release
            require( ABSPATH . WPINC . '/theme-compat/comments.php');

        $gd_buddypress_reviews = false;
    }

    /**
     * BuddyPress reviews Tab - comment HTML.
     *
     * @package GeoDir_BuddyPress
     *
     * @param object $comment Comment object.
     * @param array $args Comment arguments.
     * @param int $depth Comment depth.
     */
    public static function geodir_buddypress_comment( $comment, $args, $depth ) {
        global $aui_bs5;

        $GLOBALS['comment'] = $comment;
        switch ( $comment->comment_type ) :
            case 'pingback' :
            case 'trackback' :
                // Display trackbacks differently than normal comments.
                ?>
                <li <?php comment_class( 'geodir-comment' ); ?> id="comment-<?php comment_ID(); ?>">
                <p><?php _e( 'Pingback:', 'geodir-buddypress' ); ?><?php comment_author_link(); ?><?php edit_comment_link( __( '(Edit)', 'geodir-buddypress' ), '<span class="edit-link">', '</span>' ); ?></p>
                <?php
                break;
            default :
                // Proceed with normal comments.
                ?>
            <li <?php comment_class( 'geodir-comment' ); ?> id="li-comment-<?php comment_ID(); ?>">
                <article id="comment-<?php comment_ID(); ?>" class="comment card mt-3 shadow-sm">
                    <header class="comment-meta comment-author vcard card-header border-bottom toast-header">
                        <?php

                        echo '<div class="media-heading ' . ( $aui_bs5 ? 'ps-2 me-auto' : 'pl-2 mr-auto' ) . ' h4 m-0 align-items-center d-flex justify-content-center"><cite><a href="'.get_comment_link($comment->comment_ID).'">'. get_the_title($comment->comment_post_ID).'</a></cite></div>';

                        $rating = GeoDir_Comments::get_comment_rating( $comment->comment_ID );
                        if($rating != 0){
                            echo '<div class="geodir-review-ratings mb-n2">'. geodir_get_rating_stars( $rating, $comment->comment_ID ) . '</div>';
                        }
                        ?>
                    </header>
                    <!-- .comment-meta -->

                    <?php if ( '0' == $comment->comment_approved ) : ?>
                        <p class="comment-awaiting-moderation"><?php _e( 'Your comment is awaiting moderation.', 'geodir-buddypress' ); ?></p>
                    <?php endif; ?>

                    <section class="comment-content comment card-body m-0">
                        <?php comment_text(); ?>
                    </section>
                    <!-- .comment-content -->

                    <div class="comment-links card-footer py-2 px-3 bg-white">
                        <div class='row'>
                            <div class="col-5 align-items-center d-flex">
                                <?php         
                                    printf( ' <a class="hidden-xs-down text-muted" href="%1$s"><i class="far fa-clock"></i> <span class="geodir-review-time" title="%3$s">%2$s</span></a>',
                                        esc_url( get_comment_link( $comment->comment_ID ) ),
                                        sprintf( _x( '%s ago', '%s = human-readable time difference', 'geodir-buddypress' ), human_time_diff( get_comment_time( 'U' ), current_time( 'timestamp' ) ) ),
                                        sprintf( __( '%1$s at %2$s', 'geodir-buddypress' ), get_comment_date(), get_comment_time() )
                                    );
                                ?>
                            </div>
                            <div class="col-7 <?php echo ( $aui_bs5 ? 'text-end' : 'text-right' ); ?>">
                                <?php edit_comment_link( __( 'Edit', 'geodir-buddypress' ), '<span class="edit-link">', '</span>' ); ?>
                            </div>
                        </div>
                    </div>
                    <!-- .reply -->
                </article>
                <!-- #comment-## -->
                <?php
                break;
        endswitch;
    }


    /**
     * Filter the array of comment query clauses.
     *
     * Fix the user_id ambiguous column error in comments and reviews table.
     *
     * @package GeoDir_BuddyPress
     *
     * @global object $wpdb WordPress Database object.
     * @global bool $gd_buddypress_reviews True if buddypress reviews tab.
     *
     * @param array $clauses A compacted array of comment query clauses.
     * @return array Modified comment query clauses.
     */
    public static function geodir_buddypress_comments_clauses( $clauses ) {
        global $wpdb, $gd_buddypress_reviews;

        if ( $gd_buddypress_reviews ) {
            $where = $clauses['where'];

            $where = str_replace( ' user_id', ' ' . $wpdb->comments . '.user_id', $where );
            $where = str_replace( ' comment_approved', ' ' . $wpdb->comments . '.comment_approved', $where );
            $clauses['where'] = $where;
        }
        return $clauses;
    }

    /**
     * Multirating addon strips author link from reviews. This function fixes that.
     *
     * @package GeoDir_BuddyPress
     *
     * @return array Modified comment query clauses.
     */
    function multirating_get_comment_author_link($return, $author, $comment_id) {
        $comment = get_comment( $comment_id );
        if (isset($comment->user_id) && $comment->user_id != '0') {
            $url = geodir_buddypress_get_user_url( $comment->user_id );
            $return = "<a href='$url' rel='external nofollow' class='url'>$author</a>";
        }
        return $return;
    }

    function init_multirating_get_comment_author_link() {
        if ( geodir_get_option( 'geodir_buddypress_link_author' ) && defined('GEODIRREVIEWRATING_VERSION')) {
            add_filter('get_comment_author_link', array($this, 'multirating_get_comment_author_link'), 10, 3);
        }
    }

}

new GeoDir_BuddyPress_Template();