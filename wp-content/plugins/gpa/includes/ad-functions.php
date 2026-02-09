<?php

/**
 * Returns the post type of an ad
 */
function adv_ad_post_type() {
    /**
     * Filters the post type of an ad
     */
    return apply_filters( 'adv_ad_post_type', 'adv_ad' );
}

/**
 * Returns the default status of an ad posted on the frontend
 */
function adv_ad_new_status() {

    $status = adv_get_option( 'ad_new_status', 'pending' );
    $status = empty( $status ) ? 'pending' : trim( $status );
    
    return apply_filters( 'adv_ad_new_status', $status );
}

/**
 * Returns an array of supported ad types
 */
function advertising_ad_types( $is_admin = true ) {
    $types = array(
        'text'  => __( 'Text Ad', 'advertising' ),
        'code'  => __( 'HTML Ad', 'advertising' ),
        'image' => __( 'Image Ad', 'advertising' ),
    );

    // Listings.
    if ( class_exists( 'GeoDirectory' ) ) {
        $types['listing'] = __( 'Listing Ad', 'advertising' );
    }

    if ( 'disable' == adv_get_option( 'html_ads', 'admin' ) ) {
        unset( $types['code'] );
    }

    if ( ! $is_admin && 'allow' != adv_get_option( 'html_ads', 'admin' ) ) {
        unset( $types['code'] );
    }

    return apply_filters( 'advertising_ad_types', $types );
}

/**
 * Returns all properties for an ad
 */
function adv_ad_props() {

    $properties = array( 
        'description', 
        'zone', 
        'type',
        'target_url',
        'locations',
        'new_tab',
        'no_bg_color',
        'bg_color',
        'text_color',
        'image',
        'code',
        'views',
        'clicks',
        'date_paid',
        'ctr',
        'listing',
        'listing_content'
    );

    /**
     * Filters the properties associated with an ad
     */
    return apply_filters( 'adv_ad_props', $properties );
}

/**
 * Returns all property values for an ad
 */
function adv_get_ad_props( $ad ) {

    if( empty( $ad ) ) {
        return array();
    }

    if( is_object( $ad ) ) {
        $ad = $ad->ID;
    }

    //Fetch all property values
    $properties  = adv_ad_props();

    //Fetch all meta values
    $meta_values = get_post_meta( $ad );

    $filtered = array();

    foreach( $properties as $property ) {
        $meta_key = strpos( $property, '_adv_ad_' ) === 0 ? $property : '_adv_ad_' . $property;
        
        if ( isset( $meta_values[ $meta_key ] ) ) {
            $filtered[ $property ] = maybe_unserialize( $meta_values[ $meta_key ][0] );
        } else {
            $filtered[ $property ] = '';
        }
    }
    
    /**
     * Filters the properties values for an ad
     */
    return apply_filters( 'adv_ad_props', $filtered, $ad );
}

/**
 * Retrieves single meta value for an ad
 */
function adv_ad_get_meta( $post_id, $field = '', $single = true, $default = NULL ) {
    if ( empty( $post_id ) || empty( $field ) ) {
        return NULL;
    }
    
    $meta_key = strpos( $field, '_adv_ad_' ) === 0 ? $field : '_adv_ad_' . $field;
 
    $value = get_post_meta( $post_id, $meta_key, $single );

    $value = ( $value === NULL || $value === false || $value === '' ) && $default !== NULL ? $default : $value;
    
    return apply_filters( 'adv_ad_meta_' . $meta_key, $value, $post_id, $field, $single, $default );
}

function adv_ad_calculate_ctr( $post_id, $post = array(), $update = false ) {
    if ( empty( $post_id ) ) {
        return NULL;
    }
    
    $extra_fields = array( '_adv_ad_payments', '_adv_ad_views', '_adv_ad_clicks' );

    foreach ( $extra_fields as $meta_key ) {
        if ( !is_numeric( ${$meta_key} = adv_ad_get_meta( $post_id, $meta_key ) ) ) {
            update_post_meta( $post_id, $meta_key, 0 );
        }
    }

    $ctr = $_adv_ad_views > 0 ? round( ( (int)$_adv_ad_clicks / (int)$_adv_ad_views ) * 100, 2 ) : 0;
    
    update_post_meta( $post_id, '_adv_ad_ctr', $ctr );
    
    do_action( 'adv_ad_calculate_ctr', $post_id, $post, $update );
}
add_action( 'adv_save_adv_ad', 'adv_ad_calculate_ctr', 10, 3 );

/**
 * Logs ad views.
 */
function adv_log_ad_view( $ad_id ) {

    // Do not log views from site admins...
    if ( current_user_can( 'manage_options' ) ) {
        return;
    }

    // ... or ad owners.
    if ( get_current_user_id() == get_post_field( 'post_author', $ad_id ) ) {
        return;
    }

    // Log this impression.
    $views  = (int)get_post_meta( $ad_id, '_adv_ad_views', true );
    $clicks = (int)get_post_meta( $ad_id, '_adv_ad_clicks', true );

    update_post_meta( $ad_id, '_adv_ad_views', $views + 1 );
    update_post_meta( $ad_id, '_adv_ad_ctr', adv_calculate_ctr( $clicks, $views + 1 ) );

    $ad = adv_get_ad( $ad_id );
    adv_check_ad_limits( $ad );

}

function adv_ad_ctr( $post_id, $display = false ) {
    if ( empty( $post_id ) ) {
        return NULL;
    }
    
    $ctr = (float)adv_ad_get_meta( $post_id, 'ctr' );
    
    if ( $display ) {
        $ctr = ( $ctr > 0 ? number_format_i18n( $ctr, 2 ) : 0 ) . '%';
    }
    
    return apply_filters( 'adv_ad_ctr', $ctr, $post_id, $display );
}

function adv_ad_clicks( $post_id, $display = false ) {
    if ( empty( $post_id ) ) {
        return NULL;
    }
    
    $clicks = (int)adv_ad_get_meta( $post_id, 'clicks' );
    
    if ( $display ) {
        $clicks = number_format_i18n( $clicks );
    }
    
    return apply_filters( 'adv_ad_clicks', $clicks, $post_id, $display );
}

function adv_ad_impressions( $post_id, $display = false ) {
    if ( empty( $post_id ) ) {
        return NULL;
    }
    
    $impressions = (int)adv_ad_get_meta( $post_id, 'views' );
    
    if ( $display ) {
        $impressions = number_format_i18n( $impressions );
    }
    
    return apply_filters( 'adv_ad_impressions', $impressions, $post_id, $display );
}


//Returns the type of the ad
function adv_ad_type( $post_id, $display = false ) {
    if ( empty( $post_id ) ) {
        return NULL;
    }
    
    $type  = adv_ad_get_meta( $post_id, 'type' );
    if ( $display ) {
        $types = advertising_ad_types();
        $type  = isset( $types[$type] ) ? $types[$type] : __( 'Uknown', 'advertising' );
    }
    
    return apply_filters( 'adv_ad_type', $type, $post_id, $display );
}

//Returns the ad size for a zone
function adv_ad_size( $post_id, $any = false ) {
    if ( empty( $post_id ) ) {
        return NULL;
    }
    
    //Default size is any
    $size    = $any ? __( 'Any size', 'advertising' ) : '-';
    
    //Get the zone for the ad
    $zone_id = adv_ad_zone( $post_id );
    
    if ( $zone_id > 0 ) {
        $width  = (int)adv_zone_get_meta( $zone_id, 'width' );
        $height = (int)adv_zone_get_meta( $zone_id, 'height' );
        
        if ( $width > 0 || $height > 0 ) {
            if ( $width > 0 && !$height > 0 ) {
                $size = $width . ' x ' . $width;
            } else if ( !$width > 0 && $height > 0 ) {
                $size = $height . ' x ' . $height;
            } else {
                $size = $width . ' x ' . $height;
            }
        }
    }
    
    return apply_filters( 'adv_ad_size', $size, $post_id, $any );
}

//Returns the zone for an ad
function adv_ad_zone( $post_id, $display=false ) {
    if ( empty( $post_id ) ) {
        return NULL;
    }
    
    $zone = (int) adv_ad_get_meta( $post_id, 'zone' );

    //In case there is no zone...
    if(! $zone ) {
        $return = $display ? __( 'No Zone', 'advertising' ) : 0;
        return $return;
    }

    if( $display ) {
        $zone = get_the_title( $zone );
    }
    
    return apply_filters( 'adv_ad_zone', $zone, $post_id, $display );
}

/**
 * Retrieves the user id of the advertiser of an ad
 */
function adv_ad_advertiser( $ad ) {
    $ad = adv_get_ad( $ad );
    return $ad->get( 'post_author' );
}

/**
 * Returns the html required to display a single ad
 */
function adv_get_ad_html( $ad ) {
    $ad     = adv_get_ad( $ad );
    $before = '';
    $after  = '';

    if ( $ad->can_display_ad() ) {

        $zone   = adv_get_zone( $ad->get( 'zone' ) );
        $width  = esc_attr( $zone->get('width') );
		$height = esc_attr( $zone->get('height') );

		if ( is_numeric( $width ) && ! empty( $width ) ) {
			$width = absint( $width ) . 'px';
		}

		if ( is_numeric( $height ) && ! empty( $height ) ) {
			$height = absint( $height ) . 'px';
		}

        $style = '';
		if ( ! empty( $width ) ) {
			$style .= "width: $width;max-width: fit-content;";
		}

		if ( ! empty( $height ) ) {
			$style .= "height: $height;";
		}

        if ( ! empty( $style ) ) {
            $before = "<div class='overflow-hidden'><div class='overflow-hidden gpa-inner-container' style='$style'>";
            $after  = '</div></div>';
        }

    }

    return $before . $ad->get_html() . $after;
}

/**
 * Retrieves a single ad
 */
function adv_get_ad( $ad ) {
    return new Adv_Ad( $ad );
}

/**
 * Retrieves all ads by a given user
 */
function adv_get_ads_by_advertiser( $advertiser_id, $post_status = 'publish' ) {
    if ( empty( $advertiser_id ) ) {
        return NULL;
    }
    
    $args = array(
        'advertiser'    => $advertiser_id,
        'post_status'   => $post_status,
    );
    
    $ads = adv_get_ads( $args );

    return apply_filters( 'adv_get_ads_by_advertiser', $ads, $advertiser_id );
}

/**
 * Retrieves ads in a given zone
 */
function adv_get_ads_by_zone( $zone,  $count = null, $shuffle = true ) {
    $zone = new Adv_Zone ( $zone );
    return $zone->get_ads( $count, $shuffle );
}

function adv_get_ads( $args = array() ) {
    $defaults = array(
        'post_type'     => 'adv_ad',
        'post_status'   => 'publish',
        'fields'        => 'ids',
        'numberposts'   => 100,
        'advertiser'    => NULL,
        'meta_query'    => array(),
    );
    
    $args = wp_parse_args( $args, $defaults );

    if (! empty( $args['advertiser'] ) ) {
        $args['author'] = $args['advertiser'];
    }
    unset( $args['advertiser'] );

    
    if ( empty( $args['meta_query'] ) ) {
        unset( $args['meta_query'] );
    }

    return get_posts( $args );
    
}

/**
 * Checks if a given ad is published
 */
function adv_ad_is_active( $post_id ) {
    if ( empty( $post_id ) || get_post_status( $post_id ) != 'publish' ) {
        return false;
    }
    
    return true;
}

/**
 * Checks if a given ad can be displayed on the frontend
 */
function adv_ad_is_available( $ad ) {
    $ad = adv_get_ad( $ad );
    return $ad->can_display_ad();
}

//Deletes an ad via ajax
function adv_delete_advertiser_ad() {
    $is_ajax = defined( 'DOING_AJAX' ) && DOING_AJAX;
    
    if ( !$is_ajax || empty( $_POST['_nonce'] ) || !is_user_logged_in() ) {
        return;
    }
    
    $response = array();
    $response['success'] = false;
        
    if ( !wp_verify_nonce( sanitize_text_field( $_POST['_nonce'] ), 'adv-nonce' ) ) {
        $response['error'] = __( 'Nonce verification has failed.', 'advertising' );
        wp_send_json( $response );
    }
    
    //Ensure the post exists and it is an ad
    $post_id = !empty( $_POST['_id'] ) ? absint( $_POST['_id'] ) : 0;
    $ad      = adv_get_ad( $post_id );
    if ( !$post_id || !$ad->ID ) {
        $response['error'] = __( 'Ad not found.', 'advertising' );
        wp_send_json( $response );
    }
    
    //Users can only delete their own ads. Admins can delete any ad
    if ( get_current_user_id() != $ad->get('post_author') && !current_user_can('manage_options') ) {
        $response['error'] = __( 'You are not allowed to delete this ad.', 'advertising' );
        wp_send_json( $response );
    }
    
    $check = apply_filters( 'adv_pre_delete_advertiser_ad', null, $post_id );
    if ( null !== $check ) {
        $response['error'] = __( 'Can not delete this ad.', 'advertising' );
        wp_send_json( $response );
    }
    
    $return = adv_delete_ad( $post_id, true );

    if ( $return && !is_wp_error( $return ) ) {
        $response['success'] = true;
    } else {
        $response['error'] = __( 'Error occurred in deleting ad.', 'advertising' );
    }
    
    wp_send_json( $response );
}
add_action( 'adv_delete_advertiser_ad', 'adv_delete_advertiser_ad' );


function adv_renew_advertiser_ad() {
    $is_ajax = defined( 'DOING_AJAX' ) && DOING_AJAX;
    
    if ( !$is_ajax || empty( $_POST['_nonce'] ) || !is_user_logged_in() ) {
        return;
    }
    
    $response = array();
    $response['success'] = false;
        
    if ( !wp_verify_nonce( sanitize_text_field( $_POST['_nonce'] ), 'adv-nonce' ) ) {
        $response['error'] = __( 'Nonce verification has failed.', 'advertising' );
        wp_send_json( $response );
    }
    
    //Ensure the post exists and it is an ad
    $post_id = !empty( $_POST['_id'] ) ? absint( $_POST['_id'] ) : 0;
    $ad      = adv_get_ad( $post_id );
    if ( !$post_id || !$ad->ID ) {
        $response['error'] = __( 'Ad not found.', 'advertising' );
        wp_send_json( $response );
    }

    //Users can only delete their own ads. Admins can delete any ad
    if ( get_current_user_id() != $ad->get('post_author') && !current_user_can('manage_options') ) {
        $response['error'] = __( 'You are not allowed to renew this ad.', 'advertising' );
        wp_send_json( $response );
    }

    $response['success'] = true;
    $response['payment_url'] = esc_url( apply_filters( 'adv_renew_ad_link', '', $ad ) );
    
    wp_send_json( $response );
}
add_action( 'adv_renew_advertiser_ad', 'adv_renew_advertiser_ad' );

function adv_delete_ad( $post_id = 0, $force_delete = false ) {
    if ( empty( $post_id ) ) {
        return false;
    }
    
    $post = get_post( $post_id );
    if ( !( !empty( $post->post_type ) && $post->post_type == 'adv_ad' ) ) {
        return false;
    }
    
    if ( !$force_delete ) { // trash
        $check = apply_filters( 'adv_pre_trash_ad', null, $post_id, $force_delete );
        if ( null !== $check ) {
            return false;
        }
        
        do_action( 'adv_before_trash_ad', $post_id );
        
        $return = wp_trash_post( $post_id );
        
        if ( !$return || is_wp_error( $return ) ) {
            return $return;
        }
        
        do_action( 'adv_after_trash_ad', $post_id );
    } else {
        $check = apply_filters( 'adv_pre_delete_ad', null, $post_id, $force_delete );
        if ( null !== $check ) {
            return false;
        }
        
        do_action( 'adv_before_delete_ad', $post_id );
        
        $return = wp_delete_post( $post_id, true );
        
        if ( !$return || is_wp_error( $return ) ) {
            return $return;
        }
        
        do_action( 'adv_after_delete_ad', $post_id );
    }
    
    return true;
}

/**
 * Retrieves the ad associated with a product
 */
function adv_get_product_ad( $product_id, $meta_key ) {
    global $wpdb;

    //Sanitize product
    $product_id = (int) $product_id;
    $meta_key   = '_adv_ad_' . sanitize_key( $meta_key );

    $sql = "SELECT post_id 
            FROM {$wpdb->postmeta} 
            WHERE meta_key='$meta_key' AND meta_value='$product_id' 
            LIMIT 1";

    return (int) $wpdb->get_var($sql);
}

/**
 * Checks if an ad has surpassed its limits and unpublishes it.
 * 
 * @param Adv_Ad $ad
 */
function adv_check_ad_limits( $ad ) {

    $zone_id      = $ad->get( 'zone' );
    $pricing_term = (int) adv_zone_get_meta( $zone_id, 'pricing_term', true, '1000' );
    $pricing_type = adv_zone_get_meta( $zone_id, 'pricing_type', true, 'impressions' );
    $package_link = adv_zone_get_meta( $zone_id, 'link_to_packages', true, false );
    $renewals     = (int) get_post_meta( $ad->ID, 'adv_renewals', true ) + 1;
    $invoice      = adv_ad_get_meta( $ad->ID, 'invoicing_invoice_id' );
    $order        = adv_ad_get_meta( $ad->ID, 'woocommerce_order_id' );
    $qty          = get_post_meta( $ad->ID, '_adv_ad_qty', true );
    $qty          = empty( $qty ) ? 1 : (int) $qty;
    $pricing_term = $pricing_term * $qty;

    if ( ! $ad->is_published() || empty( $zone_id ) || empty( $pricing_term ) || $package_link || ( empty( $invoice ) && empty( $order ) ) ) {
        return;
    }

    // Price per impressions.
    if ( 'impressions' == $pricing_type && ( $pricing_term * $renewals ) < (int) $ad->get( 'views' ) ) {
        adv_unpublish_ad( $ad->ID );
        update_post_meta( $ad->ID, 'adv_repay', 1 );
    }

    // Price per clicks.
    if ( 'clicks' == $pricing_type && ( $pricing_term  * $renewals ) < (int) $ad->get( 'clicks' ) ) {
        adv_unpublish_ad( $ad->ID );
        update_post_meta( $ad->ID, 'adv_repay', 1 );
    }

    // Price per time period.
    if ( 'days' == $pricing_type &&  current_time( 'timestamp' ) > (int) $ad->get( 'date_paid' ) + ( $pricing_term * DAY_IN_SECONDS ) ) {
        adv_unpublish_ad( $ad->ID );
        update_post_meta( $ad->ID, 'adv_repay', 1 );
    }

}

/**
 * Unpublishes an advert.
 * 
 * @param int $ad_id
 */
function adv_unpublish_ad( $ad_id ) {

    wp_update_post(
        array(
            'post_status' => 'pending',
            'ID'		  => $ad_id,
        )
    );

}

/**
 * Check ad has expired or not.
 * 
 * @param int $ad_id post id for ad.
 */
function adv_check_ad_expired( $ad_id ) {
	$ad = adv_get_ad( $ad_id );
	$ad->get( 'clicks' );
	$expired      = false;
	$zone_id      = $ad->get( 'zone' );
	$pricing_term = (int) adv_zone_get_meta( $zone_id, 'pricing_term', true, '1000' );
	$pricing_type = adv_zone_get_meta( $zone_id, 'pricing_type', true, 'impressions' );
	// Price per impressions.
	if ( 'impressions' === $pricing_type && $pricing_term < (int) $ad->get( 'views' ) ) {
		$expired = true;
	}

	// Price per clicks.
	if ( 'clicks' === $pricing_type && $pricing_term < (int) $ad->get( 'clicks' ) ) {
		$expired = true;
	}

	// Price per time period.
	if ( 'days' === $pricing_type && current_time( 'timestamp' ) > (int) $ad->get( 'date_paid' ) + ( $pricing_term * DAY_IN_SECONDS ) ) {
		$expired = true;
	}
	return $expired;
}

/**
 * Ad current status.
 *
 * @param int  $post_id post id for ad.
 * @param bool $display show or not.
 */
function adv_ad_current_status( $post_id, $display = false ) {
	if ( empty( $post_id ) ) {
		return null;
	}

    $ad_status = get_post_status( $post_id ) == 'publish' ? __( 'Live', 'advertising' ) : __( 'Pending', 'advertising' );
	$invoice   = adv_ad_get_meta( $post_id, 'invoicing_invoice_id' );

	if ( function_exists('wpinv_get_invoice') && $invoice = wpinv_get_invoice( $invoice ) ) {
        $ad_status = $invoice->get_status_label_html();
	}

	if ( ! empty( $invoice ) && adv_check_ad_expired( $post_id ) ) {
		$ad_status = __( 'Expired', 'advertising' );
	}

	return apply_filters( 'adv_ad_current_status', $ad_status, $post_id, $display );
}





/**
 * A helper function for margin inputs.
 *
 * @param string $type
 * @param array $overwrite
 *
 * @return array
 */
function adv_get_sd_margin_input($type = 'mt', $overwrite = array(), $include_negatives = true ){
    $options = array(
        "" => __('None', 'advertising'),
        "0" => "0",
        "1" => "1",
        "2" => "2",
        "3" => "3",
        "4" => "4",
        "5" => "5",
    );

    if ( $include_negatives ) {
        $options['n1'] = '-1';
        $options['n2'] = '-2';
        $options['n3'] = '-3';
        $options['n4'] = '-4';
        $options['n5'] = '-5';
    }

    $defaults = array(
        'type' => 'select',
        'title' => __('Margin top', 'advertising'),
        'options' =>  $options,
        'default' => '',
        'desc_tip' => true,
        'group'     => __("Wrapper Styles",'advertising')
    );

    // title
    if( $type == 'mt' ){
        $defaults['title'] = __('Margin top', 'advertising');
        $defaults['icon']  = 'box-top';
        $defaults['row'] = array(
            'title' => __('Margins', 'advertising'),
            'key'   => 'wrapper-margins',
            'open' => true,
            'class' => 'text-center',
        );
    }elseif( $type == 'mr' ){
        $defaults['title'] = __('Margin right', 'advertising');
        $defaults['icon']  = 'box-right';
        $defaults['row'] = array(
            'key'   => 'wrapper-margins',
        );
    }elseif( $type == 'mb' ){
        $defaults['title'] = __('Margin bottom', 'advertising');
        $defaults['icon']  = 'box-bottom';
        $defaults['row'] = array(
            'key'   => 'wrapper-margins',
        );
    }elseif( $type == 'ml' ){
        $defaults['title'] = __('Margin left', 'advertising');
        $defaults['icon']  = 'box-left';
        $defaults['row'] = array(
            'key'   => 'wrapper-margins',
            'close'   => true,
        );
    }

    $input = wp_parse_args( $overwrite, $defaults );


    return $input;
}

/**
 * A helper function for padding inputs.
 *
 * @param string $type
 * @param array $overwrite
 *
 * @return array
 */
function adv_get_sd_padding_input($type = 'pt', $overwrite = array() ){
    $options = array(
        "" => __('None', 'advertising'),
        "0" => "0",
        "1" => "1",
        "2" => "2",
        "3" => "3",
        "4" => "4",
        "5" => "5",
    );

    $defaults = array(
        'type' => 'select',
        'title' => __('Padding top', 'advertising'),
        'options' =>  $options,
        'default' => '',
        'desc_tip' => true,
        'group'     => __("Wrapper Styles",'advertising')
    );

    // title
    if( $type == 'pt' ){
        $defaults['title'] = __('Padding top', 'advertising');
        $defaults['icon']  = 'box-top';
        $defaults['row'] = array(
            'title' => __('Padding', 'advertising'),
            'key'   => 'wrapper-padding',
            'open' => true,
            'class' => 'text-center',
        );
    }elseif( $type == 'pr' ){
        $defaults['title'] = __('Padding right', 'advertising');
        $defaults['icon']  = 'box-right';
        $defaults['row'] = array(
            'key'   => 'wrapper-padding',
        );
    }elseif( $type == 'pb' ){
        $defaults['title'] = __('Padding bottom', 'advertising');
        $defaults['icon']  = 'box-bottom';
        $defaults['row'] = array(
            'key'   => 'wrapper-padding',
        );
    }elseif( $type == 'pl' ){
        $defaults['title'] = __('Padding left', 'advertising');
        $defaults['icon']  = 'box-left';
        $defaults['row'] = array(
            'key'   => 'wrapper-padding',
            'close'   => true,

        );
    }

    $input = wp_parse_args( $overwrite, $defaults );


    return $input;
}

/**
 * A helper function for border inputs.
 *
 * @param string $type
 * @param array $overwrite
 *
 * @return array
 */
function adv_get_sd_border_input($type = 'border', $overwrite = array() ){

    $defaults = array(
        'type' => 'select',
        'title' => __('Border', 'advertising'),
        'options' =>  array(),
        'default' => '',
        'desc_tip' => true,
        'group'     => __("Wrapper Styles",'advertising')
    );

    // title
    if( $type == 'rounded' ){
        $defaults['title'] = __('Border radius type', 'advertising');
        $defaults['options'] = array(
            ''  =>  __("Default",'advertising'),
            'rounded'  =>  'rounded',
            'rounded-top'  =>  'rounded-top',
            'rounded-right'  =>  'rounded-right',
            'rounded-bottom'  =>  'rounded-bottom',
            'rounded-left'  =>  'rounded-left',
            'rounded-circle'  =>  'rounded-circle',
            'rounded-pill'  =>  'rounded-pill',
            'rounded-0'  =>  'rounded-0',
        );
    }elseif( $type == 'rounded_size' ){
        $defaults['title'] = __('Border radius size', 'advertising');
        $defaults['options'] = array(
            ''  =>  __("Default",'advertising'),
            'sm'  =>  __("Small",'advertising'),
            'lg'  =>  __("Large",'advertising'),
        );
    }elseif( $type == 'type' ){
        $defaults['title'] = __('Border type', 'advertising');
        $defaults['options'] = array(
            ''  =>  __("None",'advertising'),
            'border'  =>  __("Full",'advertising'),
            'border-top'  =>  __("Top",'advertising'),
            'border-bottom'  =>  __("Bottom",'advertising'),
            'border-left'  =>  __("Left",'advertising'),
            'border-right'  =>  __("Right",'advertising'),
        );
    }else{
        $defaults['title'] = __('Border color', 'advertising');
        $defaults['options'] = array(
                                   ''  =>  __("Default",'advertising'),
                                   '0'  =>  __("None",'advertising'),
                               ) + adv_aui_colors();
    }

    $input = wp_parse_args( $overwrite, $defaults );


    return $input;
}

/**
 * A helper function for padding inputs.
 *
 * @param string $type
 * @param array $overwrite
 *
 * @return array
 */
function adv_get_sd_shadow_input($type = 'shadow', $overwrite = array() ){
    $options = array(
        "" => __('None', 'advertising'),
        "shadow-sm" => __('Small', 'advertising'),
        "shadow" => __('Regular', 'advertising'),
        "shadow-lg" => __('Large', 'advertising'),
    );

    $defaults = array(
        'type' => 'select',
        'title' => __('Shadow', 'advertising'),
        'options' =>  $options,
        'default' => '',
        'desc_tip' => true,
        'group'     => __("Wrapper Styles",'advertising')
    );


    $input = wp_parse_args( $overwrite, $defaults );


    return $input;
}

/**
 * A helper function for padding inputs.
 *
 * @param string $type
 * @param array $overwrite
 *
 * @return array
 */
function adv_get_sd_background_input($type = 'bg', $overwrite = array() ){
    $options = array(
                   ''  =>  __("None",'advertising'),
               ) + adv_aui_colors();

    $defaults = array(
        'type' => 'select',
        'title' => __('Background color', 'advertising'),
        'options' =>  $options,
        'default' => '',
        'desc_tip' => true,
        'group'     => __("Wrapper Styles",'advertising')
    );


    $input = wp_parse_args( $overwrite, $defaults );


    return $input;
}

/**
 * A helper function for title tag inputs.
 *
 * @param string $type
 * @param array $overwrite
 *
 * @return array
 */
function adv_get_sd_title_tag_input( $overwrite = array() ){

    $defaults = array(
        'title' => __('Title HTML tag', 'advertising'),
        'desc' => __('Set the HTML tag for the title.', 'advertising'),
        'type' => 'select',
        'options'   =>  array(
            "" => __("Default (theme widget default)",'advertising'),
            "h1" => "h1",
            "h2" => "h2",
            "h3" => "h3",
            "h4" => "h4",
            "h5" => "h5",
            "h6" => "h6",
        ),
        'default'  => '',
        'desc_tip' => true,
        'advanced' => false,
        'group'     => __("Title",'advertising')
    );


    $input = wp_parse_args( $overwrite, $defaults );


    return $input;
}

/**
 * A helper function for font size inputs.
 *
 * @param string $type
 * @param array $overwrite
 *
 * @return array
 */
function adv_get_sd_font_size_input( $overwrite = array() ){

    $defaults = array(
        'title' => __('Font size', 'advertising'),
        'type' => 'select',
        'options'   =>  array(
            "" => __("Default (title tag size)",'advertising'),
            "h1" => 'XXL',
            "h2" => 'XL',
            "h3" => 'L',
            "h4" => 'M',
            "h5" => 'S',
            "h6" => 'XS',
            "display-1" => "display-1",
            "display-2" => "display-2",
            "display-3" => "display-3",
            "display-4" => "display-4",
        ),
        'default'  => '',
        'desc_tip' => true,
        'advanced' => false,
        'group'     => __("Title",'advertising')
    );


    $input = wp_parse_args( $overwrite, $defaults );


    return $input;
}


/**
 * A helper function for title tag inputs.
 *
 * @param string $type
 * @param array $overwrite
 *
 * @return array
 */
function adv_get_sd_text_align_input( $overwrite = array() ){

    $defaults = array(
        'title' => __('Text align', 'advertising'),
        'type' => 'select',
        'options'   =>  array(
            "" => __("Default (left)",'advertising'),
            "text-left" => __("Left",'advertising'),
            "text-center" => __("Center",'advertising'),
            "text-right" => __("Right",'advertising'),
        ),
        'default'  => '',
        'desc_tip' => true,
        'advanced' => false,
        'group'     => __("Title",'advertising')
    );


    $input = wp_parse_args( $overwrite, $defaults );


    return $input;
}

/**
 * A helper function for padding inputs.
 *
 * @param string $type
 * @param array $overwrite
 *
 * @return array
 */
function adv_get_sd_text_color_input( $overwrite = array() ){
    $options = array(
                   ''  =>  __("Default (theme color)",'advertising'),
               ) + adv_aui_colors();

    $defaults = array(
        'type' => 'select',
        'title' => __('Text color', 'advertising'),
        'options' =>  $options,
        'default' => '',
        'desc_tip' => true,
        'advanced' => false,
        'group'     => __("Title",'advertising')
    );


    $input = wp_parse_args( $overwrite, $defaults );


    return $input;
}

function adv_aui_colors($include_branding = false, $include_outlines = false, $outline_button_only_text = false){
    $theme_colors = array();

    $theme_colors["primary"] = __('Primary', 'advertising');
    $theme_colors["secondary"] = __('Secondary', 'advertising');
    $theme_colors["success"] = __('Success', 'advertising');
    $theme_colors["danger"] = __('Danger', 'advertising');
    $theme_colors["warning"] = __('Warning', 'advertising');
    $theme_colors["info"] = __('Info', 'advertising');
    $theme_colors["light"] = __('Light', 'advertising');
    $theme_colors["dark"] = __('Dark', 'advertising');
    $theme_colors["white"] = __('White', 'advertising');
    $theme_colors["purple"] = __('Purple', 'advertising');
    $theme_colors["salmon"] = __('Salmon', 'advertising');
    $theme_colors["cyan"] = __('Cyan', 'advertising');
    $theme_colors["gray"] = __('Gray', 'advertising');
    $theme_colors["indigo"] = __('Indigo', 'advertising');
    $theme_colors["orange"] = __('Orange', 'advertising');

    if($include_outlines){
        $button_only =  $outline_button_only_text ? " ".__("(button only)",'advertising') : '';
        $theme_colors["outline-primary"] = __('Primary outline', 'advertising') . $button_only;
        $theme_colors["outline-secondary"] = __('Secondary outline', 'advertising') . $button_only;
        $theme_colors["outline-success"] = __('Success outline', 'advertising') . $button_only;
        $theme_colors["outline-danger"] = __('Danger outline', 'advertising') . $button_only;
        $theme_colors["outline-warning"] = __('Warning outline', 'advertising') . $button_only;
        $theme_colors["outline-info"] = __('Info outline', 'advertising') . $button_only;
        $theme_colors["outline-light"] = __('Light outline', 'advertising') . $button_only;
        $theme_colors["outline-dark"] = __('Dark outline', 'advertising') . $button_only;
        $theme_colors["outline-white"] = __('White outline', 'advertising') . $button_only;
        $theme_colors["outline-purple"] = __('Purple outline', 'advertising') . $button_only;
        $theme_colors["outline-salmon"] = __('Salmon outline', 'advertising') . $button_only;
        $theme_colors["outline-cyan"] = __('Cyan outline', 'advertising') . $button_only;
        $theme_colors["outline-gray"] = __('Gray outline', 'advertising') . $button_only;
        $theme_colors["outline-indigo"] = __('Indigo outline', 'advertising') . $button_only;
        $theme_colors["outline-orange"] = __('Orange outline', 'advertising') . $button_only;
    }


    if($include_branding){
        $theme_colors = $theme_colors  + adv_aui_branding_colors();
    }

    return $theme_colors;
}

function adv_aui_branding_colors(){
    return array(
        "facebook" => __('Facebook', 'advertising'),
        "twitter" => __('Twitter', 'advertising'),
        "instagram" => __('Instagram', 'advertising'),
        "linkedin" => __('Linkedin', 'advertising'),
        "flickr" => __('Flickr', 'advertising'),
        "github" => __('GitHub', 'advertising'),
        "youtube" => __('YouTube', 'advertising'),
        "wordpress" => __('WordPress', 'advertising'),
        "google" => __('Google', 'advertising'),
        "yahoo" => __('Yahoo', 'advertising'),
        "vkontakte" => __('Vkontakte', 'advertising'),
    );
}


/**
 * Build AUI classes from settings.
 *
 * @todo find best way to use px- py- or general p-
 * @param $args
 *
 * @return string
 */
function adv_build_aui_class($args){
    global $aui_bs5;

    $classes = array();

    // margins
    if ( !empty( $args['mt'] ) || ( isset( $args['mt'] ) && $args['mt'] == '0' ) ) { $classes[] = "mt-" . sanitize_html_class( $args['mt'] ); }
    if ( !empty( $args['mr'] ) || ( isset( $args['mr'] ) && $args['mr'] == '0' ) ) { $classes[] = ( $aui_bs5 ? 'me-' : 'mr-' ) . sanitize_html_class( $args['mr'] ); }
    if ( !empty( $args['mb'] ) || ( isset( $args['mb'] ) && $args['mb'] == '0' ) ) { $classes[] = "mb-" . sanitize_html_class($args['mb']); }
    if ( !empty( $args['ml'] ) || ( isset( $args['ml'] ) && $args['ml'] == '0' ) ) { $classes[] = ( $aui_bs5 ? 'ms-' : 'ml-' ) . sanitize_html_class( $args['ml'] ); }

    // padding
    if ( !empty( $args['pt'] ) || ( isset( $args['pt'] ) && $args['pt'] == '0' ) ) { $classes[] = "pt-" . sanitize_html_class( $args['pt'] ); }
    if ( !empty( $args['pr'] ) || ( isset( $args['pr'] ) && $args['pr'] == '0' ) ) { $classes[] = ( $aui_bs5 ? 'pe-' : 'pr-' ) . sanitize_html_class( $args['pr'] ); }
    if ( !empty( $args['pb'] ) || ( isset( $args['pb'] ) && $args['pb'] == '0' ) ) { $classes[] = "pb-" . sanitize_html_class( $args['pb'] ); }
    if ( !empty( $args['pl'] ) || ( isset( $args['pl'] ) && $args['pl'] == '0' ) ) { $classes[] = ( $aui_bs5 ? 'ps-' : 'pl-' ) . sanitize_html_class( $args['pl'] ); }

    // border
    if ( !empty( $args['border'] ) && ( $args['border']=='none' || $args['border']==='0') ) { $classes[] = "border-0"; }
    elseif ( !empty( $args['border'] ) ) { $classes[] = "border border-".sanitize_html_class($args['border']); }

    // border radius type
    if ( !empty( $args['rounded'] ) ) {
        $args['rounded'] = str_replace( array( '-left', '-right' ), array( '-start', '-end' ), $args['rounded'] );
        $classes[] = sanitize_html_class( $args['rounded'] );
    }

    // border radius size
    if ( !empty( $args['rounded_size'] ) ) {
        $args['rounded'] = str_replace( array( 'sm', 'lg' ), array( '1', '3' ), $args['rounded_size'] );
        $classes[] = "rounded-" . sanitize_html_class( $args['rounded_size'] );
        // if we set a size then we need to remove "rounded" if set
        if (($key = array_search("rounded", $classes)) !== false) {
            unset($classes[$key]);
        }
    }

    // shadow
    if ( !empty( $args['shadow'] ) ) { $classes[] = sanitize_html_class($args['shadow']); }

    // background
    if ( !empty( $args['bg'] ) ) { $classes[] = "bg-".sanitize_html_class($args['bg']); }

    // text_color
    if ( !empty( $args['text_color'] ) ) { $classes[] = "text-".sanitize_html_class($args['text_color']); }

    return implode(" ",$classes);
}