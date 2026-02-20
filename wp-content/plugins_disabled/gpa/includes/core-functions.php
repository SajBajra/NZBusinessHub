<?php

/**
 * Returns a list of display rules.
 */
function adv_get_display_rules() {

	$tabs = array(
		'users' => array(
			'label' => __( 'Users', 'advertising' ),
			'icon'  => 'dashicons-admin-users',
		),
		'post_types' => array(
			'label' => __( 'Post Types', 'advertising' ),
			'icon'  => 'dashicons-admin-page',
		),
		'taxonomies' => array(
			'label' => __( 'Taxonomies', 'advertising' ),
			'icon'  => 'dashicons-cloud',
		),
		'posts' => array(
			'label' => __( 'Posts', 'advertising' ),
			'icon'  => 'dashicons-admin-post',
		),
		'terms' => array(
			'label' => __( 'Terms', 'advertising' ),
			'icon'  => 'dashicons-admin-generic',
		),

	);

	return apply_filters( 'adv_display_rules', $tabs );
}

/**
 * Logs an error
 */
function adv_log( $log, $title = '', $file = '', $line = '', $exit = false ) {
	$should_log = apply_filters( 'adv_log', WP_DEBUG );

	if ( true === $should_log ) {
		$label = '';
		if ( $file && $file !== '' ) {
			$label .= basename( $file ) . ( $line ? '(' . $line . ')' : '' );
		}

		if ( $title && $title !== '' ) {
			$label = $label !== '' ? $label . ' ' : '';
			$label .= $title . ' ';
		}

		$label = $label !== '' ? trim( $label ) . ' : ' : '';

		if ( is_array( $log ) || is_object( $log ) ) {
			error_log( $label . print_r( $log, true ) );
		} else {
			error_log( $label . $log );
		}

		if ( $exit ) {
			exit;
		}
	}
}

/**
 * Checks whether or not the site can display ads
 */
function adv_can_display_ads() {
	$display = true;

	if ( defined( 'ADS_DISABLED' ) && ADS_DISABLED ) {
		$display = false;
	}

	return apply_filters( 'adv_can_display_ads', $display );
}

/**
 * Get truncated string with specified width.
 *
 * @since 1.0.0
 *
 * @param string $str The string being decoded.
 * @param int $start The start position offset. Number of characters from the beginning of string.
 *                      For negative value, number of characters from the end of the string.
 * @param int $width The width of the desired trim. Negative widths count from the end of the string.
 * @param string $trimmaker A string that is added to the end of string when string is truncated. Ex: "...".
 * @param string $encoding The encoding parameter is the character encoding. Default "UTF-8".
 * @return string
 */
function adv_utf8_strimwidth( $str, $start, $width, $trimmaker = '', $encoding = 'UTF-8' ) {
	if ( function_exists( 'mb_strimwidth' ) ) {
		return mb_strimwidth( $str, $start, $width, $trimmaker, $encoding );
	}

	return adv_utf8_substr( $str, $start, $width, $encoding ) . $trimmaker;
}

/**
 * Get the string length.
 *
 * @since 1.0.0
 *
 * @param string $str The string being checked for length.
 * @param string $encoding The encoding parameter is the character encoding. Default "UTF-8".
 * @return int Returns the number of characters in string.
 */
function adv_utf8_strlen( $str, $encoding = 'UTF-8' ) {
	if ( function_exists( 'mb_strlen' ) ) {
		return mb_strlen( $str, $encoding );
	}

	return strlen( $str );
}

function adv_utf8_strtolower( $str, $encoding = 'UTF-8' ) {
	if ( function_exists( 'mb_strtolower' ) ) {
		return mb_strtolower( $str, $encoding );
	}

	return strtolower( $str );
}

function adv_utf8_strtoupper( $str, $encoding = 'UTF-8' ) {
	if ( function_exists( 'mb_strtoupper' ) ) {
		return mb_strtoupper( $str, $encoding );
	}

	return strtoupper( $str );
}

/**
 * Find position of first occurrence of string in a string
 *
 * @since 1.0.0
 *
 * @param string $str The string being checked.
 * @param string $find The string to find in input string.
 * @param int $offset The search offset. Default "0". A negative offset counts from the end of the string.
 * @param string $encoding The encoding parameter is the character encoding. Default "UTF-8".
 * @return int Returns the position of the first occurrence of search in the string.
 */
function adv_utf8_strpos( $str, $find, $offset = 0, $encoding = 'UTF-8' ) {
	if ( function_exists( 'mb_strpos' ) ) {
		return mb_strpos( $str, $find, $offset, $encoding );
	}

	return strpos( $str, $find, $offset );
}

/**
 * Find position of last occurrence of a string in a string.
 *
 * @since 1.0.0
 *
 * @param string $str The string being checked, for the last occurrence of search.
 * @param string $find The string to find in input string.
 * @param int $offset Specifies begin searching an arbitrary number of characters into the string.
 * @param string $encoding The encoding parameter is the character encoding. Default "UTF-8".
 * @return int Returns the position of the last occurrence of search.
 */
function adv_utf8_strrpos( $str, $find, $offset = 0, $encoding = 'UTF-8' ) {
	if ( function_exists( 'mb_strrpos' ) ) {
		return mb_strrpos( $str, $find, $offset, $encoding );
	}

	return strrpos( $str, $find, $offset );
}

/**
 * Get the part of string.
 *
 * @since 1.0.0
 *
 * @param string $str The string to extract the substring from.
 * @param int $start If start is non-negative, the returned string will start at the entered position in string, counting from zero.
 *                      If start is negative, the returned string will start at the entered position from the end of string.
 * @param int|null $length Maximum number of characters to use from string.
 * @param string $encoding The encoding parameter is the character encoding. Default "UTF-8".
 * @return string
 */
function adv_utf8_substr( $str, $start, $length = null, $encoding = 'UTF-8' ) {
	if ( function_exists( 'mb_substr' ) ) {
		if ( $length === null ) {
			return mb_substr( $str, $start, adv_utf8_strlen( $str, $encoding ), $encoding );
		} else {
			return mb_substr( $str, $start, $length, $encoding );
		}
	}

	return substr( $str, $start, $length );
}

/**
 * Get the width of string.
 *
 * @since 1.0.0
 *
 * @param string $str The string being decoded.
 * @param string $encoding The encoding parameter is the character encoding. Default "UTF-8".
 * @return string The width of string.
 */
function adv_utf8_strwidth( $str, $encoding = 'UTF-8' ) {
	if ( function_exists( 'mb_strwidth' ) ) {
		return mb_strwidth( $str, $encoding );
	}

	return adv_utf8_strlen( $str, $encoding );
}

function adv_utf8_ucfirst( $str, $lower_str_end = false, $encoding = 'UTF-8' ) {
	if ( function_exists( 'mb_strlen' ) ) {
		$first_letter = adv_utf8_strtoupper( adv_utf8_substr( $str, 0, 1, $encoding ), $encoding );
		$str_end = "";

		if ( $lower_str_end ) {
			$str_end = adv_utf8_strtolower( adv_utf8_substr( $str, 1, adv_utf8_strlen( $str, $encoding ), $encoding ), $encoding );
		} else {
			$str_end = adv_utf8_substr( $str, 1, adv_utf8_strlen( $str, $encoding ), $encoding );
		}

		return $first_letter . $str_end;
	}

	return ucfirst( $str );
}

function adv_utf8_ucwords( $str, $encoding = 'UTF-8' ) {
	if ( function_exists( 'mb_convert_case' ) ) {
		return mb_convert_case( $str, MB_CASE_TITLE, $encoding );
	}

	return ucwords( $str );
}


function adv_calculate_ctr( $clicks, $views ) {
	$ctr = 0;

	if ( (int)$clicks > 0 && (int)$views > 0 ) {
		$ctr = round( (int)$clicks * 100 / (int)$views, 2 );
	}


	return $ctr;
}

function adv_tracking_slug() {
	$slug = adv_get_option( 'tracking_slug' );

	if ( !empty( $slug ) ) {
		$slug = sanitize_key( trim( $slug ) );
	}

	if ( empty( $slug ) ) {
		$slug = 'click'; // Default slug
	}

	$slug = sanitize_key( trim( $slug ) );

	return apply_filters( 'adv_tracking_slug', $slug );
}

function adv_tracking_base_link() {
	$base_slug = sanitize_text_field( adv_tracking_slug() );

	if ( get_option( 'permalink_structure' ) ) {
		$url = home_url( "/$base_slug/AD_ID/" );
	} else {
		$url = add_query_arg( $base_slug, 'AD_ID', home_url() );
	}

	return apply_filters( 'adv_tracking_base_link', $url );
}

function adv_tracking_url( $ad_id = 0 ) {
	return str_replace( 'AD_ID', trim( $ad_id ), adv_tracking_base_link() );
}

function adv_http_referer() {
	$referrer = !empty( $_SERVER['HTTP_REFERER'] ) ? $_SERVER['HTTP_REFERER'] : adv_current_url();

	return apply_filters( 'adv_http_referer', $referrer );
}

function adv_current_url() {
	$current_url = 'http';
	if ( isset( $_SERVER['HTTPS'] ) && strtolower( $_SERVER['HTTPS'] ) == 'on' ) {
		$current_url .= 's';
	}
	$current_url .= "://";

	/*
	 * Since we are assigning the URI from the server variables, we first need
	 * to determine if we are running on apache or IIS.  If PHP_SELF and REQUEST_URI
	 * are present, we will assume we are running on apache.
	 */
	if ( !empty( $_SERVER['PHP_SELF'] ) && !empty( $_SERVER['REQUEST_URI'] ) ) {
		// To build the entire URI we need to prepend the protocol, and the http host
		// to the URI string.
		$current_url .= $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
	} else {
		/*
		 * Since we do not have REQUEST_URI to work with, we will assume we are
		 * running on IIS and will therefore need to work some magic with the SCRIPT_NAME and
		 * QUERY_STRING environment variables.
		 *
		 * IIS uses the SCRIPT_NAME variable instead of a REQUEST_URI variable... thanks, MS
		 */
		$current_url .= $_SERVER['HTTP_HOST'] . $_SERVER['SCRIPT_NAME'];

		// If the query string exists append it to the URI string
		if ( isset( $_SERVER['QUERY_STRING']) && !empty( $_SERVER['QUERY_STRING'] ) ) {
			$current_url .= '?' . $_SERVER['QUERY_STRING'];
		}
	}

	return apply_filters( 'adv_current_url', $current_url );
}

function adv_get_pages() {
	$pages_options = array();

	$pages = get_pages();
	if ( $pages ) {
		foreach ( $pages as $page ) {
			$title = $page->post_title . ' (' . $page->post_name . ')';
			$pages_options[ $page->ID ] = $title;
		}
	}

	return $pages_options;
}

function adv_get_page_id( $page ) {
	$page = apply_filters( 'adv_get_' . $page . '_page_id', adv_get_option( $page . '_page_id' ) );
	return $page ? absint( $page ) : -1;
}

function adv_get_page_permalink( $page, $args = array() ) {
	$page_id   = adv_get_page_id( $page );
	$permalink = 0 < $page_id ? get_permalink( $page_id ) : get_home_url();

	if ( !empty( $args ) && is_array( $args ) ) {
		$permalink = add_query_arg( $args, $permalink );
	}

	return apply_filters( 'adv_get_' . $page . '_page_permalink', $permalink );
}

function adv_dashboard_endpoint_url( $endpoint, $args = array() ) {

	// UsersWP support.
	if ( defined( 'USERSWP_PLUGIN_FILE' ) && apply_filters( 'adv_enable_uwp_integration', true ) ) {
		$account_url = uwp_get_account_page_url();

		if ( ! empty( $account_url ) ) {
			$account_url = add_query_arg( 'type', 'ads', $account_url );

			if ( 'dashboard' !== $endpoint ) {
				$account_url = add_query_arg( 'adv', urlencode( $endpoint ), $account_url );
			}

			return add_query_arg( $args, $account_url );
		}
	}

	if ( 'dashboard' === $endpoint ) {
		return adv_get_page_permalink( 'dashboard', $args );
	}

	return adv_get_endpoint_url( $endpoint, '', adv_get_page_permalink( 'dashboard', $args ) );
}

function adv_get_endpoint_url( $endpoint, $value = '', $permalink = '' ) {
	global $wp;

	if ( !$permalink ) {
		$permalink = get_permalink();
	}

	$endpoint = !empty( $wp->query_vars[ $endpoint ] ) ? $wp->query_vars[ $endpoint ] : $endpoint;

	if ( get_option( 'permalink_structure' ) ) {
		if ( strstr( $permalink, '?' ) ) {
			$query_string = '?' . parse_url( $permalink, PHP_URL_QUERY );
			$permalink    = current( explode( '?', $permalink ) );
		} else {
			$query_string = '';
		}
		$url = trailingslashit( $permalink ) . $endpoint . '/' . $value . $query_string;
	} else {
		$url = add_query_arg( $endpoint, $value, $permalink );
	}

	return apply_filters( 'adv_get_endpoint_url', $url, $endpoint, $value, $permalink );
}

function adv_is_dashboard_page() {
	return is_page( adv_get_page_id( 'dashboard' ) ) || adv_post_content_has_shortcode( 'ads_dashboard' ) || apply_filters( 'ads_is_dashboard_page', false ) || ( isset( $_GET['type'] ) && $_GET['type'] === 'ads' );
}

function adv_dashboard_current_endpoint() {
    global $wp;

    $menus = adv_dashboard_nav_items();

    $current = 'dashboard';

    foreach ( $menus as $endpoint => $nav ) {
        if ( isset( $wp->query_vars[ $endpoint ] ) ) {
            $current = $endpoint;
        }
    }

	if ( isset( $_GET['adv'] ) ) {
		$current = sanitize_key( $_GET['adv'] );
	}

    return apply_filters( 'adv_dashboard_current_endpoint', $current );
}

function adv_dashboard_nav_items() {
    $endpoints = array(
        'dashboard' => get_option( 'adv_dashboard_ads_endpoint', 'ads' ),
        'new-ad'    => get_option( 'adv_dashboard_new_ad_endpoint', 'new-ad' ),
    );

    $items = array(
        'dashboard' => array(
            'label'    => __( 'My Ads', 'advertising' ),
            'svg-icon' => '<svg xmlns="http://www.w3.org/2000/svg" class="mt-2" height="64" viewBox="0 0 24 24" width="64" fill="#6c757d"><path d="M0 0h24v24H0z" fill="none"/><path d="M4 14h4v-4H4v4zm0 5h4v-4H4v4zM4 9h4V5H4v4zm5 5h12v-4H9v4zm0 5h12v-4H9v4zM9 5v4h12V5H9z"/></svg>',
        ),
        'new-ad' => array(
            'label'    => __( 'Add a new Ad', 'advertising' ),
            'svg-icon' => '<svg xmlns="http://www.w3.org/2000/svg" class="mt-2" height="64" viewBox="0 0 24 24" width="64" fill="#6c757d"><path d="M0 0h24v24H0z" fill="none"/><path d="M4 6H2v14c0 1.1.9 2 2 2h14v-2H4V6zm16-4H8c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm-1 9h-4v4h-2v-4H9V9h4V5h2v4h4v2z"/></svg>',
        ),
    );

    foreach ( $endpoints as $endpoint_id => $endpoint ) {
        if ( empty( $endpoint ) ) {
            unset( $items[ $endpoint_id ] );
        }
    }

    return apply_filters( 'adv_dashboard_nav_items', $items );
}

function adv_dashboard_nav_item_classes( $endpoint ) {
    global $wp;

    $classes = array(
        'adv-dashboard-nav',
        'adv-dashboard-nav-' . $endpoint,
    );

    $current = isset( $wp->query_vars[ $endpoint ] );
    if ( 'dashboard' === $endpoint && ( isset( $wp->query_vars['page'] ) || empty( $wp->query_vars ) ) ) {
        $current = true;
    }

    if ( $current ) {
        $classes[] = 'active';
    }

    $classes = apply_filters( 'adv_dashboard_nav_item_classes', $classes, $endpoint );

    return implode( ' ', array_map( 'sanitize_html_class', $classes ) );
}

/**
 * Checks whether the currently being viewd content has a given shortcode
 */
function adv_post_content_has_shortcode( $tag = '' ) {
	global $post;
	return is_singular() && has_shortcode( $post->post_content, $tag );
}

function adv_has_term( $terms = '', $taxonomies = '' ) {

	if ( empty( $taxonomies ) ) {
		$taxonomies = get_taxonomies();
	}

	// Single post.
	if ( is_singular() ) {

		// Loop through all taxonomies and check if the term exists in any of them.
		foreach ( $taxonomies as $taxonomy ) {
			if ( has_term( $terms, $taxonomy ) ) {
				return true;
			}
		}
	}

	// Archive pages.
	if ( ! empty( $terms ) && is_tax( $taxonomies, $terms ) ) {
		return true;
	}

	// GeoDirectory search page.
	if ( ! class_exists( 'GeoDirectory' ) || ! geodir_is_page( 'search' ) || empty( $_REQUEST['spost_category'] ) ) {
		return false;
	}

	$categories = wp_parse_id_list( $_REQUEST['spost_category'] );

	if ( empty( $terms ) ) {
		return ! empty( $categories );
	}

	$to_check = wp_parse_id_list( $terms );

	return ! empty( array_intersect( $categories, $to_check ) );
}

/**
 * Checks whether a given object can be displayed on the current terms page.
 *
 * @since 1.0.1-dev
 *
 * @param string $object Adv_Zone or Adv_Ad
 * @param string $can_display The current value of whether or not it can display
 */
function adv_can_display_on_terms( $can_display, $object  ) {

	if( false === $can_display ) {
		return false;
	}

	//Abort if this is not a supported object
	if ( !( $object instanceof Adv_Zone ) && !( $object instanceof Adv_Ad ) ) {
		return $can_display;
	}

	//Prepare data
	$terms   = explode( ',', $object->get( 'terms' ) );
	$term_to = $object->get( 'term_to' );

	//Abort if $can_display is already false or no term restrictions have been set
	if(! $can_display || empty( $term_to ) || 'all' == $term_to ) {
		return $can_display;
	}

	// Are we hiding on all terms.
	if( 'none' == $term_to && ( adv_has_term() || !is_singular() ) ) {
		return false;
	}

	$has_term = false;

	//If this post has any of the given terms
	if( adv_has_term( $terms ) || has_category( $terms ) || has_tag( $terms ) ) {
		$has_term = true;
	}

	//If this is an archive page of any of the given terms
	if( ! $has_term && ( is_tax( get_taxonomies(), $terms ) || is_category( $terms ) || is_tag( $terms ) ) ) {
		$has_term = true;
	}

	//Are we only showing on places having the term...
	if( 'show' == $term_to ) {
		return $has_term;
	}

	//...or hiding on places containing the term
	return ! $has_term;

}
add_filter( 'adv_can_display_object', 'adv_can_display_on_terms', 50, 2 );

/**
 * Checks whether a given object can be displayed on the current post or page
 *
 * @since 1.0.1-dev
 *
 * @param string $object Adv_Zone or Adv_Ad
 * @param string $can_display The current value of whether or not it can display
 */
function adv_can_display_on_posts( $can_display, $object  ) {

	if( false === $can_display ) {
		return false;
	}

	// Check if single
	if(! is_singular() ) {
		return $can_display;
	}

	//Abort if this is not a supported object
	if ( !( $object instanceof Adv_Zone ) && !( $object instanceof Adv_Ad ) ) {
		return $can_display;
	}

	//Prepare data
	$posts   = trim( $object->get( 'posts' ) );
	$post_to = $object->get( 'post_to' );

	//Are we are hiding on all posts
	if( is_singular() && 'none' == $post_to ) {
		return false;
	}

	//Abort if $can_display is already false or no post restrictions have been set
	if(! $can_display || empty( $posts ) || empty( $post_to ) || 'all' == $post_to ) {
		return $can_display;
	}

	$posts   = explode( ',', $posts );

	//...or only displaying on specific posts
	if(! is_singular() && 'show' == $post_to ) {
		return $can_display;
	}

	//If this post has any of the given terms
	$is_filtered_page = false;

	if(  is_single( $posts ) || is_page( $posts ) ) {
		$is_filtered_page = true;
	}

	//Are we only showing on specific posts
	if( 'show' == $post_to ) {
		return $is_filtered_page;
	}

	//...or hiding on specific posts
	return ! $is_filtered_page;

}
add_filter( 'adv_can_display_object', 'adv_can_display_on_posts', 20, 2 );

/**
 * Checks whether a given object can be displayed on the current taxonomy
 *
 * @since 1.0.1-dev
 *
 * @param string $object Adv_Zone or Adv_Ad
 * @param string $can_display The current value of whether or not it can display
 */
function adv_can_display_on_taxonomies( $can_display, $object  ) {

	if( false === $can_display ) {
		return false;
	}

	// type check
	if( !is_tax() ){
		return $can_display;
	}

	//Abort if this is not a supported object
	if ( !( $object instanceof Adv_Zone ) && !( $object instanceof Adv_Ad ) ) {
		return $can_display;
	}

	//Prepare data
	$taxonomies   = (array) $object->get( 'taxonomies' );
	$taxonomy_to  = $object->get( 'taxonomy_to' );

	//Abort if $can_display is already false or no post restrictions have been set
	if( ! $can_display || empty( $taxonomy_to ) || 'all' == $taxonomy_to ) {
		return $can_display;
	}

	//Are we are hiding on all taxonomies
	if(! is_singular() && 'none' == $taxonomy_to ) {
		return false;
	}

	//...or only displaying on specific taxonomies
	if( is_singular() && 'show' == $taxonomy_to ) {
		return false;
	}

	//If this is an archive with a taxonomy
	$is_filtered_tax = false;
	if(  is_tax( $taxonomies ) ) {
		$is_filtered_tax = true;
	}

	// is_tax() returns false when is_category() or is_tag() returns true
	// https://core.trac.wordpress.org/ticket/18636
	if ( in_array( 'category', $taxonomies ) && is_category() ) {
		$is_filtered_tax = true;
	}

	if ( in_array( 'post_tag', $taxonomies ) && is_tag() ) {
		$is_filtered_tax = true;
	}

	//Are we only showing on specific taxonomies
	if( 'show' == $taxonomy_to ) {
		return $is_filtered_tax;
	}

	//...or hiding on specific taxonomies
	return ! $is_filtered_tax;

}
add_filter( 'adv_can_display_object', 'adv_can_display_on_taxonomies', 40, 2 );

/**
 * Checks whether a given object can be displayed on the current post type
 *
 * @since 1.0.1-dev
 *
 * @param string $object Adv_Zone or Adv_Ad
 * @param string $can_display The current value of whether or not it can display
 */
function adv_can_display_on_post_types( $can_display, $object  ) {

	if( false === $can_display ) {
		return false;
	}


	//@todo we prob need to remove single checks from here.
	if( is_post_type_archive() || is_single() ){}else{
		return $can_display;
	}

	//Abort if this is not a supported object
	if ( !( $object instanceof Adv_Zone ) && !( $object instanceof Adv_Ad ) ) {
		return $can_display;
	}

	//Prepare data
	$post_types    = (array) $object->get( 'post_types' );
	$post_type_to  = $object->get( 'post_type_to' );

	//Abort if $can_display is already false or no post restrictions have been set
	if(! $can_display || empty( $post_type_to ) || 'all' == $post_type_to ) {
		return $can_display;
	}

	//Are we are hiding on all post types
	if( 'none' == $post_type_to ) {
		return false;
	}

	//If this is a filtered post type
	$is_filtered = false;
	if(  is_singular( $post_types ) || is_post_type_archive( $post_types ) ) {
		$is_filtered = true;
	}

	//Are we only showing on specific post types
	if( 'show' == $post_type_to ) {
		return $is_filtered;
	}

	//...or hiding on specific post types
	return ! $is_filtered;

}
add_filter( 'adv_can_display_object', 'adv_can_display_on_post_types', 30, 2 );

/**
 * Checks whether a given object can be displayed to the current user
 *
 * @since 1.0.1-dev
 *
 * @param string $object Adv_Zone or Adv_Ad
 * @param string $can_display The current value of whether or not it can display
 */
function adv_can_display_to_current_user( $can_display, $object  ) {

	if( false === $can_display ) {
		return false;
	}

	//Abort if this is not a supported object
	if ( !( $object instanceof Adv_Zone ) && !( $object instanceof Adv_Ad ) ) {
		return $can_display;
	}

	//Prepare data
	$user_roles    		= (array) $object->get( 'user_roles' );
	$user_role_to  		= $object->get( 'user_role_to' );
	$current_user_roles = adv_get_user_roles();

	//Abort if $can_display is already false or no post restrictions have been set
	if(! $can_display || empty( $user_role_to ) || 'all' == $user_role_to ) {
		return $can_display;
	}

	//If this is a filtered user?
	$is_filtered = (bool) count( array_intersect( $user_roles, $current_user_roles ) );

	//Are we only showing to specific users
	if( 'show' == $user_role_to ) {
		return $is_filtered;
	}

	//...or hiding to specific users
	return ! $is_filtered;

}
add_filter( 'adv_can_display_object', 'adv_can_display_to_current_user', 10, 2 );

/**
 * Displays a hidden field.
 */
function adv_hidden_field( $name, $value ) {
    $name  = sanitize_text_field( $name );
    $value = esc_attr( $value );

    echo "<input type='hidden' name='$name' value='$value' />";
}

/**
 * Add our tabs to UsersWP account tabs.
 *
 * @since 1.0.6
 * @param  array $tabs
 * @return array
 */
function adv_filter_userswp_account_tabs( $tabs ) {

	$tabs['ads'] = array(
		'title' => __( 'Ads', 'advertising' ),
		'icon'  => 'fas fa-cog',
	);

    return $tabs;
}
add_filter( 'uwp_account_available_tabs', 'adv_filter_userswp_account_tabs', 6 );

/**
 * Display our UsersWP account tabs.
 *
 * @since 1.0.6
 * @param string $tabs
 */
function adv_display_userswp_account_tabs( $tab ) {

    if ( 'ads' === $tab ) {
        echo Adv_Dashboard::instance()->get_html();
    }

}
add_action( 'uwp_account_form_display', 'adv_display_userswp_account_tabs' );

/**
 * Filters the account page title.
 *
 * @since  1.0.6
 * @param  string $title Current title.
 * @param  string $tab   Current tab.
 * @return string Title.
 */
function adv_filter_userswp_account_title( $title, $tab ) {

    if ( 'ads' === $tab ) {
        return '0';
    }

    return $title;
}
add_filter( 'uwp_account_page_title', 'adv_filter_userswp_account_title', 10, 2 );

/**
 * The function is for include templates files.
 *
 * @since 2.0.0
 *
 * @param string $template_name Template name.
 * @param array $args Optional. Template arguments. Default array().
 * @param string $template_path Optional. Template path. Default null.
 * @param string $default_path Optional. Default path. Default null.
 */
function adv_get_template( $template_name, $args = array(), $template_path = '', $default_path = '' ) {
	if ( ! empty( $args ) && is_array( $args ) ) {
		extract( $args );
	}

	$located = adv_locate_template( $template_name, $template_path, $default_path );

	if ( file_exists( $located ) ) {
		include $located;
	}

}

/**
 * The function is use for Retrieve the name of the highest
 * priority template file that exists.
 *
 * @since  2.0.0
 *
 * @param $template_name Template files to search for, in order.
 * @param string $template_path Optional. Template path. Default null.
 * @param string $default_path Optional. Default path. Default null.
 *
 * @return string Template path.
 */
function adv_locate_template( $template_name, $template_path = '', $default_path = '' ) {
	if ( ! $template_path ) {
		$template_path = 'gpa';
	}

	if ( ! $default_path ) {
		$default_path = ADVERTISING_PLUGIN_DIR . 'templates/';
	}

	// Look within passed path within the theme - this is priority.
	$template = locate_template(
		array(
			untrailingslashit( $template_path ) . '/' . $template_name,
		)
	);

	// Get default template
	if ( ! $template ) {
		$template = untrailingslashit( $default_path ) . '/' . $template_name;
	}

	// Return what we found.
	return apply_filters( 'adv_locate_template', $template, $template_name, $template_path );
}

/**
 * Filters post permalinks.
 *
 * @param string $permalink
 * @param WP_Post $post
 */
function adv_filter_listing_permalink( $permalink, $post ) {

    // Compare the listings before filtering.
    if ( $GLOBALS['adv_current_listing'] && $GLOBALS['adv_current_listing']->ID === $post->ID ) {
		return adv_tracking_url( $GLOBALS['adv_current_ad_id'] );
    }

    return $permalink;

}

/**
 * Filters listing title.
 *
 * @param string $title
 */
function adv_filter_listing_title( $title ) {
	return '[ad] ' . $title;
}

function adv_replace_gd_title_ad_text( $output ) {

	$html_ad = '><span class="text-white adv-ad-text px-2 rounded" style="display: inline-block;background: rgb(38 50 56 / 95%);">' . __( 'Ad', 'advertising' ) . '</span>';
	return str_replace( '>[ad]', $html_ad, $output );
}

/**
 * Filters heading widgets and change their content.
 *
 * @since 1.0.11
 *
 * @param string                 $widget_content The widget HTML output.
 * @param \Elementor\Widget_Base $widget         The widget instance.
 * @return string The changed widget content.
 */
function adv_elementor_replace_gd_title_ad_text( $widget_content, $widget ) {
	if ( strpos( $widget_content, ">[ad]" ) !== false && 'heading' === $widget->get_name() ) {
		$html_ad = '><span class="text-white adv-ad-text px-2 rounded" style="display:inline-block;background:rgb(38 50 56 / 95%);">' . __( 'Ad', 'advertising' ) . '</span>';
		$widget_content = str_replace( '>[ad]', $html_ad, $widget_content );
	}

	return $widget_content;
}

function adv_zone_wrapper_class( $has_ads ) {
	global $adv_zone_wrapper_class;

	$class = 'gpa-zone-wrapper overflow-hidden w-100 mw-100 mb-4 bg-light py-2 rounded';

	if ( $has_ads ) {
		$class .= ' d-flex flex-column align-items-center justify-content-center';
	}

	if ( ! empty( $adv_zone_wrapper_class['wrapper'] ) ) {
		$class .= ' ' . $adv_zone_wrapper_class['wrapper'];
	}

	return $class;
}

function adv_near_search_location_type() {
	return apply_filters( 'adv_near_search_location_type', 'city' );
}

function adv_search_locations() {

	if ( ! geodir_is_page('search') ) {
		return array();
	}

	$locations = array();

	// City/region search.
	if ( ! empty( $GLOBALS['geodirectory']->location ) ) {
		$location = $GLOBALS['geodirectory']->location;

		if ( ! empty( $location->city_slug ) ) {
			$locations[] = $location->city_slug;
		}
	
		if ( ! empty( $location->region_slug ) ) {
			$locations[] = $location->region_slug;
		}

	}

	$near = adv_near_search_location();

	if ( ! empty( $near ) ) {
		$locations[] = $near;
		$locations[] = sanitize_title( $near );
	}

	$location_type = adv_near_search_location_type();
	if ( isset( $_REQUEST[ $location_type ] ) ) {
		$city = stripslashes_deep( $_REQUEST[ $location_type ] );

		$locations[] = $city;
		$locations[] = sanitize_title( $city );
	}

	return array_filter( $locations );
}

function adv_near_search_location() {
	global $adv_near_search_location;

	if ( isset( $adv_near_search_location ) ) {
		return $adv_near_search_location;
	}

	$adv_near_search_location = '';

	if ( class_exists( 'GeoDir_Location_API' ) && geodir_is_page('search') && ! empty( $_REQUEST['sgeo_lat'] ) && ! empty( $_REQUEST['sgeo_lon'] ) ) {

		$location_type = adv_near_search_location_type();
		$args          = array(
			'what'      => $location_type,
			'orderby'   => 'lat_lon',
			'latitude'  => wp_unslash( sanitize_text_field( $_REQUEST['sgeo_lat'] ) ),
			'longitude' => wp_unslash( sanitize_text_field( $_REQUEST['sgeo_lon'] ) ),
			'number'    => 1,
		);

		$locations = GeoDir_Location_API::get_locations( $args );

		if ( ! empty( $locations ) && ! empty( $locations[0]->{$location_type} ) ) {
			$adv_near_search_location = $locations[0]->{$location_type};
		}
	}

	return $adv_near_search_location;
}
