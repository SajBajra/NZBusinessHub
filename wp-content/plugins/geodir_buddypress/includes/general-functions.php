<?php
/**
 * Get user profile link.
 *
 * @package GeoDirectory_BuddyPress_Integration
 *
 * @return string|void
 */
function geodir_buddypress_get_user_domain() {
    // Stop if there is no user displayed or logged in
    if ( !is_user_logged_in() && !bp_displayed_user_id() )
        return;

    // Determine user to use
    if ( bp_displayed_user_domain() ) {
        $user_domain = bp_displayed_user_domain();
    } elseif ( bp_loggedin_user_domain() ) {
        $user_domain = bp_loggedin_user_domain();
    } else {
        return;
    }

    return $user_domain;
}

/**
 * Order by query param
 * 
 * @package GeoDir_BuddyPress
 *
 * @param string $comment_template Comment template path.
 * @return string Modified Comment template path.
 */
function geodir_buddypress_posts_orderby( $orderby, $table, $geodir_post_type, $query_args, $query ){
    $sort_by = !empty( $query_args['order_by'] )? $query_args['order_by']: '';
    if( !empty( $sort_by )){

        $orderby = GeoDir_Query::sort_by_sql( $sort_by, $geodir_post_type, $query );

        $orderby = GeoDir_Query::sort_by_children( $orderby, $sort_by, $geodir_post_type, $query );
    }
    return $orderby;
}

/**
 * Get the listing count of a given user.
 *
 * @since 1.0.0
 * @since 1.1.6 Some WPML compatibility changes.
 * @package GeoDirectory_BuddyPress_Integration
 *
 * @global object $wpdb WordPress Database object.
 * @global string $plugin_prefix Geodirectory plugin table prefix.
 * @global string $table_prefix WordPress Database Table prefix.
 *
 * @param string $post_type post_type of the listing.
 * @param int $user_id ID of the user whose listings are being counted.
 * @return int Listing count of the user.
 */
function geodir_buddypress_count_total( $post_type, $user_id = 0 ) {
    global $wpdb, $table_prefix, $plugin_prefix;

    if ( empty( $user_id ) ) {
        $user_id = ( bp_displayed_user_id() ) ? bp_displayed_user_id() : bp_loggedin_user_id();
    }

    $post_status = is_super_admin() ? " OR p.post_status = 'private'" : '';
    if ( $user_id && $user_id == bp_loggedin_user_id() ) {
        $post_status .= " OR p.post_status = 'draft' OR p.post_status = 'private'";
    }

    $join = "INNER JOIN " . $plugin_prefix . $post_type . '_detail AS l ON l.post_id = p.ID';
    $where = "";

    $where = apply_filters( 'geodir_bp_listings_count_where', $where, $post_type, $user_id  );

    $join = apply_filters( 'geodir_bp_listings_count_join', $join, $post_type, $user_id  );

    $count = (int)$wpdb->get_var( "SELECT count( p.ID ) FROM " . $wpdb->prefix . "posts AS p " . $join . " WHERE p.post_author=" . (int)$user_id . " AND p.post_type='" . $post_type . "' AND ( p.post_status = 'publish' " . $post_status . " ) " . $where );

    return apply_filters( 'geodir_buddypress_count_total', $count, $user_id, $post_type );
}

/**
 * Query listings to display on member profile.
 *
 * @since 1.0.0
 * @since 1.1.6 Some WPML compatibility changes.
 * @package GeoDirectory_BuddyPress_Integration
 *
 * @global object $wpdb WordPress Database object.
 * @global string $plugin_prefix Geodirectory plugin table prefix.
 * @global string $table_prefix WordPress Database Table prefix.
 * @global string $geodir_post_type Post type.
 * @global string $table Listing table name.
 * @global int $paged Global variable contains the page number of a listing of posts.
 *
 * @param array $query_args Query args.
 * @return int|mixed Query results.
 */
function geodir_buddypress_get_bp_listings( $query_args = array() ) {
    global $wpdb, $plugin_prefix, $table_prefix, $geodir_post_type, $table, $paged;
    $current_geodir_post_type = $geodir_post_type;
    $current_table = $table;

    $GLOBALS['gd_query_args_bp'] = $query_args;;
    
    $post_type = $query_args['post_type'];
    $geodir_post_type = $post_type;
    $table = $plugin_prefix . $post_type . '_detail';

    $fields = $wpdb->posts . ".*, " . $table . ".*";
    $fields = apply_filters( 'geodir_filter_bp_listings_fields', $fields, $table, $post_type );

    $join = "INNER JOIN " . $table ." ON (" . $table .".post_id = " . $wpdb->posts . ".ID)";

    if ( $post_type == 'gd_event' && class_exists( 'GeoDir_Event_Manager' ) && defined('GEODIR_EVENT_SCHEDULES_TABLE') ) {
        $fields .= ", " . GEODIR_EVENT_SCHEDULES_TABLE . ".*";
        $join .= " INNER JOIN " . GEODIR_EVENT_SCHEDULES_TABLE ." ON (" . GEODIR_EVENT_SCHEDULES_TABLE .".event_id = " . $wpdb->posts . ".ID)";
    }

    $join = apply_filters( 'geodir_bp_listings_join', $join, $post_type  );

    $post_status = is_super_admin() ? " OR " . $wpdb->posts . ".post_status = 'private'" : '';
    if ( bp_loggedin_user_id() && bp_displayed_user_id() == bp_loggedin_user_id() ) {
        $post_status .= " OR " . $wpdb->posts . ".post_status = 'draft' OR " . $wpdb->posts . ".post_status = 'private'";
    }

    $where = " AND ( " . $wpdb->posts . ".post_status = 'publish' " . $post_status . " ) AND " . $wpdb->posts . ".post_type = '" . $post_type . "'";

    // filter favorites
    if ( isset( $query_args['filter_favorite'] ) && $query_args['filter_favorite'] == 1 ) {
        $user_fav_posts = geodir_get_user_favourites( (int)bp_displayed_user_id());
        $user_fav_posts = !empty( $user_fav_posts ) ? implode( "','", $user_fav_posts ) : "-1";
        $where .= " AND " . $wpdb->posts . ".ID IN ('" . $user_fav_posts . "')";
    } else {
        $where .= " AND " . $wpdb->posts . ".post_author = " . (int)bp_displayed_user_id();
    }

    $where = apply_filters( 'geodir_bp_listings_where', $where, $post_type );
    $where = $where != '' ? " WHERE 1=1 " . $where : '';

    $groupby = " GROUP BY $wpdb->posts.ID ";
    $groupby = apply_filters( 'geodir_bp_listings_groupby', $groupby, $post_type );
    $orderby = apply_filters( 'geodir_bp_listings_orderby', '', $table, $post_type, $query_args, $wpdb );
    $orderby = $orderby != '' ? " ORDER BY " . $orderby : '';
    

    $posts_per_page = !empty( $query_args['posts_per_page'] ) ? $query_args['posts_per_page'] : 5;

    // Paging
    $limit = '';
    if ( $posts_per_page > 0 ) {
        $page = absint( $paged );
        if ( !$page ) {
            $page = 1;
        }

        $pgstrt = absint( ( $page - 1 ) * $posts_per_page ) . ', ';
        $limit = " LIMIT " . $pgstrt . $posts_per_page;
    }
    $limit = apply_filters( 'geodir_widget_listings_limit', $limit, $posts_per_page, $post_type );

    if ( isset( $query_args['count_only'] ) && !empty( $query_args['count_only'] ) ) {
        $sql =  "SELECT COUNT(DISTINCT " . $wpdb->posts . ".ID) AS total FROM " . $wpdb->posts . "
		" . $join . "
		" . $where;

        $rows = (int)$wpdb->get_var($sql);
    } else {
        $sql =  "SELECT SQL_CALC_FOUND_ROWS " . $fields . " FROM " . $wpdb->posts . "
		" . $join . "
		" . $where . "
		" . $groupby . "
		" . $orderby . "
		" . $limit;

        $rows = $wpdb->get_results($sql);
    }

    unset( $GLOBALS['gd_query_args_bp'] );
    unset( $gd_query_args_bp );

    global $geodir_post_type, $table;
    $geodir_post_type = $current_geodir_post_type;
    $table = $current_table;

    return $rows;
}

/**
 * Get the favorite listing count of a given user.
 *
 * @since 1.0.0
 * @since 1.1.6 Some WPML compatibility changes.
 * @package GeoDirectory_BuddyPress_Integration
 *
 * @global object $wpdb WordPress Database object.
 * @global string $plugin_prefix Geodirectory plugin table prefix.
 * @global string $table_prefix WordPress Database Table prefix.
 *
 * @param string $post_type post_type of the listing.
 * @param int $user_id ID of the user whose listings are being counted.
 * @return int favorite listing count of the user.
 */
function geodir_buddypress_count_favorite( $post_type, $user_id = 0 ) {
    global $wpdb, $table_prefix, $plugin_prefix;

    if ( empty( $user_id ) ) {
        $user_id = ( bp_displayed_user_id() ) ? bp_displayed_user_id() : bp_loggedin_user_id();
    }

    $post_status = is_super_admin() ? " OR p.post_status = 'private'" : '';
    if ( $user_id && $user_id == bp_loggedin_user_id() ) {
        $post_status .= " OR p.post_status = 'draft' OR p.post_status = 'private'";
    }

    $join = "INNER JOIN " . $plugin_prefix . $post_type . '_detail AS l ON l.post_id = p.ID';
    $where = "";

    $where = apply_filters( 'geodir_bp_favorite_count_where', $where, $post_type  );

    $join = apply_filters( 'geodir_bp_favorite_count_join', $join, $post_type  );

    $user_fav_posts = geodir_get_user_favourites( (int)$user_id );
    $user_fav_posts = !empty( $user_fav_posts ) ? implode( "','", $user_fav_posts ) : "-1";

    $count = (int)$wpdb->get_var( "SELECT count( p.ID ) FROM " . $wpdb->posts . " AS p " . $join . " WHERE p.ID IN ('" . $user_fav_posts . "') AND p.post_type='" . $post_type . "' AND ( p.post_status = 'publish' " . $post_status . ")" . $where );

    return apply_filters( 'geodir_buddypress_count_favorite', $count, $user_id, $post_type );
}

/**
 * Get the favorite reviews count of a given user.
 *
 * @since 1.0.0
 * @since 1.1.6 Some WPML compatibility changes.
 * @package GeoDirectory_BuddyPress_Integration
 *
 * @global object $wpdb WordPress Database object.
 * @global string $plugin_prefix Geodirectory plugin table prefix.
 * @global string $table_prefix WordPress Database Table prefix.
 *
 * @param string $post_type post_type of the listing.
 * @param int $user_id ID of the user whose reviews are being counted.
 * @return int favorite listing count of the user.
 */
function geodir_buddypress_count_reviews( $post_type, $user_id = 0 ) {
    global $wpdb, $table_prefix, $plugin_prefix;

    if ( empty( $user_id ) ) {
        $user_id = ( bp_displayed_user_id() ) ? bp_displayed_user_id() : bp_loggedin_user_id();
    }
    $logged_id = bp_loggedin_user_id();

    $post_status = is_super_admin() ? " OR p.post_status = 'private'" : '';
    if ( $user_id && $user_id == bp_loggedin_user_id() ) {
        $post_status .= " OR p.post_status = 'draft' OR p.post_status = 'private'";
    }
    $comment_status = " AND ( c.comment_approved='1'";
    if ( $logged_id > 0 && $logged_id == $user_id ) {
        $comment_status .= " OR c.comment_approved='0'";
    }
    $comment_status .= " )";

    $join = "JOIN " . $wpdb->posts . " AS p ON p.ID = c.comment_post_ID JOIN " . $plugin_prefix . $post_type . '_detail AS l ON l.post_id = p.ID';
    $where = geodir_cpt_has_rating_disabled( $post_type ) ? "" : " AND c.comment_parent = 0";

    $where = apply_filters( 'geodir_bp_reviews_count_where', $where, $post_type, $user_id  );

    $join = apply_filters( 'geodir_bp_reviews_count_join', $join, $post_type, $user_id  );

    $query = $wpdb->prepare( "SELECT count( c.comment_post_ID ) FROM " . $wpdb->comments . " AS c " . $join . " WHERE c.user_id = %d AND p.post_type = %s " . $comment_status . " " . $where, array( (int)$user_id, $post_type ) );
    $count = (int)$wpdb->get_var( $query );

    return apply_filters( 'geodir_buddypress_count_reviews', $count, $user_id, $post_type );
}

/**
 * Checks post type has archive and returns the post type if true.
 *
 * @since 1.0.0
 * @package GeoDirectory_BuddyPress_Integration
 *
 * @return int|string
 */
function geodir_buddypress_action_post_type() {
    $action = bp_current_action();
    $gd_post_types = geodir_get_posttypes( 'array' );

    $post_type = '';
    foreach ( $gd_post_types as $gd_post_type => $post_info ) {
        if ( $post_info['has_archive'] == $action ) {
            $post_type = $gd_post_type;
            break;
        }
    }

    return $post_type;
}

/**
 * Add listing tabs to buddypress profile page.
 *
 * @since 1.0.0
 * @package GeoDirectory_BuddyPress_Integration
 */
function geodir_buddypress_screen_listings() {
    $gd_post_types = geodir_get_posttypes( 'array' );
    $post_type = geodir_buddypress_action_post_type();
    $listing_post_types = geodir_get_option( 'geodir_buddypress_tab_listing' );

    if ( !empty( $gd_post_types ) && !empty( $post_type ) && !empty( $listing_post_types ) && array_key_exists( $post_type, $gd_post_types ) && in_array( $post_type, $listing_post_types ) ) {
        add_action( 'bp_template_title', 'geodir_buddypress_listings_title' );
        add_action( 'bp_template_content', 'geodir_buddypress_listings_content' );

        $template = apply_filters( 'bp_core_template_plugin', 'members/single/plugins' );

        bp_core_load_template( apply_filters( 'geodir_buddypress_bp_core_template_plugin', $template ) );
    }
}

/**
 * Add review tabs to buddypress profile page.
 *
 * @since 1.0.0
 * @package GeoDirectory_BuddyPress_Integration
 */
function geodir_buddypress_screen_reviews() {
    $gd_post_types = geodir_get_posttypes( 'array' );
    $post_type = geodir_buddypress_action_post_type();
    $review_post_types = geodir_get_option( 'geodir_buddypress_tab_review' );

    if ( !empty( $gd_post_types ) && !empty( $post_type ) && !empty( $review_post_types ) && array_key_exists( $post_type, $gd_post_types ) && in_array( $post_type, $review_post_types ) ) {
        add_action( 'bp_template_title', 'geodir_buddypress_reviews_title' );
        add_action( 'bp_template_content', 'geodir_buddypress_reviews_content' );

        $template = apply_filters( 'bp_core_template_plugin', 'members/single/plugins' );

        bp_core_load_template( apply_filters( 'geodir_buddypress_bp_core_template_reviews_plugin', $template ) );
    }
}

/**
 * Adds listings on member profile favorites tab.
 *
 * @since 1.0.0
 * @package GeoDirectory_BuddyPress_Integration
 */
function geodir_buddypress_screen_favorites() {
    geodir_buddypress_screen_listings();
}

/**
 * Get post type name to display in member profile listings Tab.
 *
 * @since 1.0.0
 * @package GeoDirectory_BuddyPress_Integration
 *
 * @param string $post_type post_type of the listing.
 * @return string|void
 */
function geodir_buddypress_post_type_name( $post_type = '' ) {
    $action_post_type = geodir_buddypress_action_post_type();
    $post_type = $post_type != '' ? $post_type : $action_post_type;
    $gd_post_types = geodir_get_posttypes( 'array' );

    $return = !empty( $gd_post_types ) && isset( $gd_post_types[$post_type]['labels']['name'] ) ? __( $gd_post_types[$post_type]['labels']['name'], 'geodirectory' ) : '';

    return $return;
}

/**
 * Add listing tab title to buddypress profile page.
 *
 * @since 1.0.0
 * @package GeoDirectory_BuddyPress_Integration
 */
function geodir_buddypress_listings_title() {
    $post_type_name = geodir_buddypress_post_type_name();

    echo apply_filters( 'geodir_buddypress_listings_before_title', '' );
    echo apply_filters( 'geodir_buddypress_listings_title', '<div class="gdbp-content-title screen-heading">' . $post_type_name . '</div>' );
    echo apply_filters( 'geodir_buddypress_listings_after_title', '' );
}

/**
 * Add review tab title to buddypress profile page.
 *
 * @since 1.0.0
 * @package GeoDirectory_BuddyPress_Integration
 */
function geodir_buddypress_reviews_title() {
    $action = bp_current_action();

    $post_type_name = geodir_buddypress_post_type_name();

    $reviews_title = '<div class="gdbp-content-title screen-heading">' . wp_sprintf( __( 'Reviews on %s', 'geodir-buddypress' ), $post_type_name ) . '</div>';
    echo apply_filters( 'geodir_buddypress_reviews_title_' . $action, $reviews_title, $action, $post_type_name );
}

/**
 * Add listing tabs content to buddypress profile page.
 *
 * @since 1.0.0
 * @package GeoDirectory_BuddyPress_Integration
 */
function geodir_buddypress_listings_content() {
    $post_type = geodir_buddypress_action_post_type();

    $post_type_name = geodir_buddypress_post_type_name();

    $args = array();
    $args['post_type'] = $post_type;
    $args['post_type_name'] = $post_type_name;
    $args['post_type_name'] = $post_type_name;
    $args['sort_by'] = ( !empty( $_GET['sort_by'] ))? esc_attr( $_GET['sort_by'] ): 'newest';
    do_action( 'geodir_buddypress_listings_before_content', $args );

    GeoDir_BuddyPress_Template::geodir_buddypress_listings_html( $args );

    do_action( 'geodir_buddypress_listings_content', $args );
    do_action( 'geodir_buddypress_listings_after_content', $args );
}

/**
 * Add review tabs content to buddypress profile page.
 *
 * @since 1.0.0
 * @package GeoDirectory_BuddyPress_Integration
 */
function geodir_buddypress_reviews_content() {
    $post_type = geodir_buddypress_action_post_type();

    $post_type_name = geodir_buddypress_post_type_name();

    $args = array();
    $args['post_type'] = $post_type;
    $args['post_type_name'] = $post_type_name;

    do_action( 'geodir_buddypress_reviews_before_content', $args );

    GeoDir_BuddyPress_Template::geodir_buddypress_reviews_html( $args );

    do_action( 'geodir_buddypress_reviews_content', $args );
    do_action( 'geodir_buddypress_reviews_after_content', $args );
}

/**
 * BuddyPress listings tab pagination.
 *
 * @since 1.0.0
 * @package GeoDirectory_BuddyPress_Integration
 *
 * @param string $before Pagination before HTML.
 * @param string $after Pagination after HTML.
 * @param string $prelabel Pagination previous label text.
 * @param string $nxtlabel Pagination next label text.
 * @param int $pages_to_show Number of pages to show.
 * @param bool $always_show Always display the pagination? Default: false.
 */
function geodir_buddypress_pagination($before = '', $after = '', $prelabel = '', $nxtlabel = '', $pages_to_show = 5, $always_show = false)
{
    global $posts_per_page, $found_posts, $paged;
    if (empty ($prelabel)) {
        $prelabel = '<li><strong>&larr;</strong></li>';
    }
    global $bp;
    $user_domain = geodir_buddypress_get_user_domain();

    if (empty($nxtlabel)) {
        $nxtlabel = '<li><strong>&rarr;</strong></li>';
    }

    $half_pages_to_show = round($pages_to_show / 2);

    if (!is_single() && $found_posts > 0 && $posts_per_page > 0) {
        $numposts = $found_posts;
        $max_page = ceil($numposts / $posts_per_page);

        if (empty($paged)) {
            $paged = 1;
        }

        $current_domain = '';
        $component_domain = '';
        if ($bp->current_component && $bp->current_action) {
            $component_domain = trailingslashit($user_domain . $bp->current_component . '/');
            $current_domain = trailingslashit($user_domain . $bp->current_component . '/' . $bp->current_action);
        }

        if ($max_page > 1 || $always_show) {
            echo "$before <nav class='geodir-pagination'><ul class='page-numbers'>";
            if ($paged >= ($pages_to_show - 1)) {
                $url = get_pagenum_link();
                if ($current_domain) {
                    $url = strpos($url, $current_domain) !== false ? $url : str_replace($component_domain, $current_domain, $url);
                }
                echo '<li><a href="' . str_replace('&paged', '&amp;paged', $url) . '">&laquo;</a></li>';
            }
            ob_start();
            previous_posts_link($prelabel);
            $url = ob_get_clean();
            if ($current_domain) {
                $url = strpos($url, $current_domain) !== false ? $url : str_replace($component_domain, $current_domain, $url);
            }
            echo $url;
            for ($i = $paged - $half_pages_to_show; $i <= $paged + $half_pages_to_show; $i++) {
                if ($i >= 1 && $i <= $max_page) {
                    if ($i == $paged) {
                        echo '<li><span class="page-numbers current">'.$i.'</span></li>';
                    } else {
                        $url = get_pagenum_link($i);
                        if ($current_domain) {
                            $url = strpos($url, $current_domain) !== false ? $url : str_replace($component_domain, $current_domain, $url);
                        }
                        echo '<li><a href="' . str_replace('&paged', '&amp;paged', $url) . '">' . $i . '</a></li>';
                    }
                }
            }
            ob_start();
            next_posts_link($nxtlabel, $max_page);
            $url = ob_get_clean();
            if ($current_domain) {
                $url = strpos($url, $current_domain) !== false ? $url : str_replace($component_domain, $current_domain, $url);
            }
            echo $url;
            if (($paged + $half_pages_to_show) < ($max_page)) {
                $url = get_pagenum_link($max_page);
                if ($current_domain) {
                    $url = strpos($url, $current_domain) !== false ? $url : str_replace($component_domain, $current_domain, $url);
                }
                echo '<li><a href="' . str_replace('&paged', '&amp;paged', $url) . '">&raquo;</a></li>';
            }
            echo "</ul></nav> $after";
        }
    }
}

/**
 * BuddyPress listings tab pagination.
 *
 * @since 1.0.0
 * @package GeoDirectory_BuddyPress_Integration
 *
 * @param string $before Pagination before HTML.
 * @param string $after Pagination after HTML.
 * @param string $prelabel Pagination previous label text.
 * @param string $nxtlabel Pagination next label text.
 * @param int $pages_to_show Number of pages to show.
 * @param bool $always_show Always display the pagination? Default: false.
 */
function geodir_buddypress_pagination_aui( $before = '', $after = '', $prelabel = '', $nxtlabel = '', $pages_to_show = 5, $always_show = false ) {
    global $bp, $aui_bs5, $posts_per_page, $found_posts, $paged;

    if ( empty ( $prelabel ) ) {
        $prelabel = '<span class="nav-prev-text sr-only visually-hidden">' . __( 'Previous', 'geodir-buddypress' ) . '</span> <i class="fas fa-chevron-left' . ( $aui_bs5 ? ' mt-0' : '' ) . '"></i>';
    }

    $user_domain = geodir_buddypress_get_user_domain();

    if (empty($nxtlabel)) {
        $nxtlabel = '<span class="nav-next-text sr-only visually-hidden">' . __( 'Next', 'geodir-buddypress' ) . '</span> <i class="fas fa-chevron-right' . ( $aui_bs5 ? ' mt-0' : '' ) . '"></i>';
    }

    $half_pages_to_show = round($pages_to_show / 2);

    if (!is_single() && $found_posts > 0 && $posts_per_page > 0) {
        $numposts = $found_posts;
        $max_page = ceil($numposts / $posts_per_page);

        if (empty($paged)) {
            $paged = 1;
        }

        $current_domain = '';
        $component_domain = '';
        if ($bp->current_component && $bp->current_action) {
            $component_domain = trailingslashit($user_domain . $bp->current_component . '/');
            $current_domain = trailingslashit($user_domain . $bp->current_component . '/' . $bp->current_action);
        }

        if ($max_page > 1 || $always_show) {
            echo "$before <div class='mb-3'><section class='px-0 py-2 w-100'><nav class='geodir-pagination navigation aui-pagination border-0' role='navigation'><div class='aui-nav-links'><ul class='pagination m-0 p-0'>";
            if ($paged >= ($pages_to_show - 1)) {
                $url = get_pagenum_link();
                if ($current_domain) {
                    $url = strpos($url, $current_domain) !== false ? $url : str_replace($component_domain, $current_domain, $url);
                }
                echo '<li class="page-item' . ( $aui_bs5 ? ' mx-0' : '' ) . '"><a class="page-link' . ( $aui_bs5 ? ' h-100' : '' ) . '" href="' . str_replace('&paged', '&amp;paged', $url) . '">' . ( $aui_bs5 ? '<span class="visually-hidden">' . __( 'First', 'geodir-buddypress' ) . '</span> <i class="fas fa-angles-left mt-0"></i>' : '&laquo;' ) . '</a></li>';
            }
            ob_start();
            previous_posts_link($prelabel);
            $url = ob_get_clean();
            if ($current_domain) {
                $url = strpos($url, $current_domain) !== false ? $url : str_replace($component_domain, $current_domain, $url);
            }
            echo '<li class="page-item' . ( $aui_bs5 ? ' mx-0' : '' ) . '">' . $url . '</li>';
            for ($i = $paged - $half_pages_to_show; $i <= $paged + $half_pages_to_show; $i++) {
                if ($i >= 1 && $i <= $max_page) {
                    if ($i == $paged) {
                        if ( $aui_bs5 ) {
                            echo '<li class="page-item mx-0"><span aria-current="page" class="page-link current active">'.$i.'</span></li>';
                        } else {
                            echo '<li class="page-item active"><span aria-current="page" class="page-link current">'.$i.'</span></li>';
                        }
                    } else {
                        $url = get_pagenum_link($i);
                        if ($current_domain) {
                            $url = strpos($url, $current_domain) !== false ? $url : str_replace($component_domain, $current_domain, $url);
                        }
                        echo '<li class="page-item' . ( $aui_bs5 ? ' mx-0' : '' ) . '"><a class="page-link" href="' . str_replace('&paged', '&amp;paged', $url) . '">' . $i . '</a></li>';
                    }
                }
            }
            ob_start();
            next_posts_link($nxtlabel, $max_page);
            $url = ob_get_clean();
            if ($current_domain) {
                $url = strpos($url, $current_domain) !== false ? $url : str_replace($component_domain, $current_domain, $url);
            }
           echo '<li class="page-item' . ( $aui_bs5 ? ' mx-0' : '' ) . '">' . $url . '</li>';
            if (($paged + $half_pages_to_show) < ($max_page)) {
                $url = get_pagenum_link($max_page);
                if ($current_domain) {
                    $url = strpos($url, $current_domain) !== false ? $url : str_replace($component_domain, $current_domain, $url);
                }
                echo '<li class="page-item' . ( $aui_bs5 ? ' mx-0' : '' ) . '"><a class="page-link' . ( $aui_bs5 ? ' h-100' : '' ) . '" href="' . str_replace('&paged', '&amp;paged', $url) . '">' . ( $aui_bs5 ? '<span class="visually-hidden">' . __( 'Last', 'geodir-buddypress' ) . '</span> <i class="fas fa-angles-right mt-0"></i>' : '&raquo;' ) . '</a></li>';
            }
            echo "</div></ul></nav></section></div> $after";
        }
    }
}

/**
 * Filters the anchor tag attributes for the previous posts page link.
 *
 * @since 2.1.0.1
 *
 * @param string $attributes Attributes for the anchor tag.
 * @return string Filtered attributes.
 */
function geodir_buddypress_previous_posts_link_attributes_aui( $attributes = '' ) {
	global $aui_bs5;

	if ( $aui_bs5 ) {
		if ( strpos( $attributes, 'class="') !== false ) {
			$attributes = str_replace( 'class="', 'class="prev page-link h-100 ', $attributes );
		} else {
			$attributes .= ' class="prev page-link h-100"';
		}

	} else {
		if ( strpos( $attributes, 'class="') !== false ) {
			$attributes = str_replace( 'class="', 'class="prev page-link ', $attributes );
		} else {
			$attributes .= ' class="prev page-link"';
		}
	}

	return $attributes;
}

/**
 * Filters the anchor tag attributes for the next posts page link.
 *
 * @since 2.1.0.1
 *
 * @param string $attributes Attributes for the anchor tag.
 * @return string Filtered attributes.
 */
function geodir_buddypress_next_posts_link_attributes_aui( $attributes = '' ) {
	global $aui_bs5;

	if ( $aui_bs5 ) {
		if ( strpos( $attributes, 'class="') !== false ) {
			$attributes = str_replace( 'class="', 'class="next page-link h-100 ', $attributes );
		} else {
			$attributes .= ' class="next page-link h-100"';
		}
	} else {
		if ( strpos( $attributes, 'class="') !== false ) {
			$attributes = str_replace( 'class="', 'class="next page-link ', $attributes );
		} else {
			$attributes .= ' class="next page-link"';
		}
	}

	return $attributes;
}

/**
 * Remove BuddyPress hook to avoid no review issue on members profile.
 */
add_action( 'geodir_buddypress_reviews_before_content', function($args){
    remove_filter( 'comments_pre_query', 'bp_comments_pre_query', 10, 2 );
}, 10, 1);

function geodir_buddypress_get_user_url( $user_id ) {
	$user_url = function_exists( 'bp_members_get_user_url' ) ? bp_members_get_user_url( $user_id ) : bp_core_get_user_domain( $user_id );

	if ( $user_url ) {
		$user_url = trailingslashit( $user_url );
	}

	return $user_url;
}