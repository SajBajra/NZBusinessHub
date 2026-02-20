<?php
/**
 * Contains zone related functions.
 *
 */

defined( 'ABSPATH' ) || exit;


/**
 * Returns the post type of a zone
 */
function adv_zone_post_type() {
	return apply_filters( 'adv_zone_post_type', 'adv_zone' );
}

/**
 * Returns all properties for a zone
 */
function adv_zone_props() {

	$properties = array(
		'show_title',
		'width',
		'height',
		'padding',
		'count',
		'max_ads',
		'link_position',
		'user_role_to',
		'user_roles',
		'post_type_to',
		'post_types',
		'taxonomy_to',
		'taxonomies',
		'post_to',
		'posts',
		'term_to',
		'terms',
		'payments',
		'views',
		'clicks',
		'ctr',
		'invoicing_product_id',
		'woocommerce_product_id',
		'pricing_type', //cpm,cpc,cpt
		'pricing_term',
		'price',
		'inject',
		'display_grid',
		'hide_frontend',
		'allowed_ad_types',
		'gd_post_types',
		'ads_per_grid',
		'packages',
		'packages_to',
		'locations',
		'ad_rotation',
        'ad_rotation_interval',
	);

	/**
	 * Filters the properties associated with a zone
	 */
	return apply_filters( 'adv_zone_props', $properties );
}

/**
 * Returns all property values for a zone
 */
function adv_get_zone_props( $zone ) {

	if ( empty( $zone ) ) {
		return array();
	}

	if ( is_object( $zone ) ) {
		$zone = $zone->ID;
	}

	// Fetch all property values
	$properties  = adv_zone_props();

	//Fetch all meta values
	$meta_values = get_post_meta( $zone );

	$filtered = array();

	foreach( $properties as $property ) {
		$meta_key = strpos( $property, '_adv_zone_' ) === 0 ? $property : '_adv_zone_' . $property;

		if ( isset( $meta_values[ $meta_key ] ) ) {
			$filtered[ $property ] = maybe_unserialize( $meta_values[ $meta_key ][0] );
		} else {
			$filtered[ $property ] = '';
		}
	}

	/**
	 * Filters the properties values for a zone
	 */
	return apply_filters( 'adv_get_zone_props', $filtered, $zone );
}

function adv_zone_get_meta( $post_id, $field = '', $single = true, $default = NULL ) {
	if ( empty( $post_id ) || empty( $field ) ) {
		return $default;
	}

	$meta_key = strpos( $field, '_adv_zone_' ) === 0 ? $field : '_adv_zone_' . $field;

	$value = get_post_meta( $post_id, $meta_key, $single );

	$value = ( $value === NULL || $value === false || $value === '' ) && $default !== NULL ? $default : $value;

	return apply_filters( 'adv_zone_meta_' . $meta_key, $value, $post_id, $field, $single, $default );
}

function adv_zone_calculate_ctr( $post_id, $post = array(), $update = false ) {
	if ( empty( $post_id ) ) {
		return NULL;
	}

	$extra_fields = array( '_adv_zone_payments', '_adv_zone_views', '_adv_zone_clicks' );

	foreach ( $extra_fields as $meta_key ) {
		if ( !is_numeric( ${$meta_key} = adv_zone_get_meta( $post_id, $meta_key ) ) ) {
			update_post_meta( $post_id, $meta_key, 0 );
		}
	}

	$ctr = $_adv_zone_views > 0 ? round( ( (int)$_adv_zone_clicks / (int)$_adv_zone_views ) * 100, 2 ) : 0;

	update_post_meta( $post_id, '_adv_zone_ctr', $ctr );

	do_action( 'adv_zone_calculate_ctr', $post_id, $post, $update );
}
add_action( 'adv_save_adv_zone', 'adv_zone_calculate_ctr', 10, 3 );

function adv_zone_ad_size( $post_id, $any = false ) {
	if ( empty( $post_id ) ) {
		return NULL;
	}

	$size = $any ? __( 'Any size', 'advertising' ) : '-';

	$width = (int)adv_zone_get_meta( $post_id, 'width' );
	$height = (int)adv_zone_get_meta( $post_id, 'height' );

	if ( $width > 0 || $height > 0 ) {
		if ( $width > 0 && !$height > 0 ) {
			$size = $width . ' x ' . $width;
		} else if ( !$width > 0 && $height > 0 ) {
			$size = $height . ' x ' . $height;
		} else {
			$size = $width . ' x ' . $height;
		}
	}

	return apply_filters( 'adv_zone_ad_size', $size, $post_id, $any );
}

function adv_zone_price( $post_id, $format = 'full' ) {
	if ( empty( $post_id ) ) {
		return null;
	}

	$price  = __( 'Free', 'advertising' );

	$amount        = adv_zone_get_meta( $post_id, 'price' );
	$pricing_term  = (int) adv_zone_get_meta( $post_id, 'pricing_term' );
	$pricing_types = adv_get_pricing_types();
	$pricing_type  = adv_zone_get_meta( $post_id, 'pricing_type' );
	$pricing_type  = isset( $pricing_types[ $pricing_type ] ) ? $pricing_types[ $pricing_type ] : $pricing_type;

	if ( empty( $amount ) ) {
		return $price;
	}

	if ( 'price' === $format ) {
		return $amount;
	}

	$price = adv_price( $amount );

	if ( 'display' === $format ) {
		return $price;
	}

	if ( 'per' === $format ) {
		return sprintf(
			// Translators: %1$s is the price, %2$s is the pricing term, %3$s is the pricing type
			__( '%1$s per %2$s %3$s', 'advertising' ),
			$price,
			$pricing_term,
			$pricing_type
		);
	}

	return sprintf(
		// Translators: %1$s is the price, %2$s is the pricing term, %3$s is the pricing type
		__( '%1$s for %2$s %3$s', 'advertising' ),
		$price,
		$pricing_term,
		$pricing_type
	);
}

function adv_zone_ads_display( $post_id, $text = false ) {
	if ( empty( $post_id ) ) {
		return NULL;
	}

	$rows = max( (int)adv_zone_get_meta( $post_id, 'rows' ), 1 );
	$columns = max( (int)adv_zone_get_meta( $post_id, 'columns' ), 1 );

	if ( $text ) {
		$ads_display = sprintf( _n( '1 row', '%s rows', $rows, 'advertising' ), $rows ) . ' x ' . sprintf( _n( '1 column', '%s columns', $columns, 'advertising' ), $columns );
	} else {
		$ads_display = $rows . ' x ' . $columns;
	}

	return apply_filters( 'adv_zone_ads_display', $ads_display, $post_id, $text );
}

function adv_zone_ctr( $post_id, $display = false ) {
	if ( empty( $post_id ) ) {
		return NULL;
	}

	$ctr = (float)adv_zone_get_meta( $post_id, 'ctr' );

	if ( $display ) {
		$ctr = ( $ctr > 0 ? number_format_i18n( $ctr, 2 ) : 0 ) . '%';
	}

	return apply_filters( 'adv_zone_ctr', $ctr, $post_id, $display );
}

function adv_zone_clicks( $post_id, $display = false ) {
	if ( empty( $post_id ) ) {
		return NULL;
	}

	$clicks = (int)adv_zone_get_meta( $post_id, 'clicks' );

	if ( $display ) {
		$clicks = number_format_i18n( $clicks );
	}

	return apply_filters( 'adv_zone_clicks', $clicks, $post_id, $display );
}

function adv_zone_impressions( $post_id, $display = false ) {
	if ( empty( $post_id ) ) {
		return NULL;
	}

	$impressions = (int)adv_zone_get_meta( $post_id, 'views' );

	if ( $display ) {
		$impressions = number_format_i18n( $impressions );
	}

	return apply_filters( 'adv_zone_impressions', $impressions, $post_id, $display );
}

function adv_zone_shortcode( $post_id ) {
	if ( empty( $post_id ) ) {
		return NULL;
	}

	$post_id   = (int) $post_id;
	$shortcode = "[ads zone='$post_id']";

	return apply_filters( 'adv_zone_shortcode', $shortcode, $post_id );
}

function adv_zone_template_code( $zone_id ) {
	if ( empty( $zone_id ) ) {
		return NULL;
	}

	$zone_id       = absint( $zone_id );
	$template_code = "<?php echo ads_get_zone_html($zone_id); ?>";

	return apply_filters( 'adv_zone_template_code', $template_code, $zone_id );
}

function adv_get_zones( $args = array() ) {
	$defaults = array(
		'post_type'     => 'adv_zone',
		'post_status'   => 'publish',
		'fields'        => 'ids',
		'numberposts'   => '-1',
		'orderby'       => 'title',
		'order'         => 'ASC',
		'meta_query'    => array(
			'relation'  => 'OR',
			array(
				'key'     => '_adv_zone_link_to_packages',
				'compare' => '!=',
				'value'   => '1',
			),
			array(
				'key'         => '_adv_zone_link_to_packages',
				'compare_key' => '!=',
			)
		)
	);

	$args = wp_parse_args( $args, $defaults );

	$rows = get_posts( $args );

	return $rows;
}

/**
 * Retrieves the product associated with a zone
 */
function adv_get_zone_product( $zone_id, $cart = null ) {

	if( empty( $cart ) ) {
		$cart = adv_get_cart();
	}

	if( empty( $cart ) ) {
		return 0;
	}

	$product = adv_zone_get_meta( $zone_id, "{$cart}_product_id" );

	return apply_filters( 'adv_get_zone_product', $product );
}

/**
 * Retrieves the zone associated with a product
 */
function adv_get_product_zone( $product_id, $cart = null ) {

	if( empty( $cart ) ) {
		$cart = adv_get_cart();
	}

	if( empty( $cart ) ) {
		return 0;
	}

	$query_args = array(
		'post_type'       => adv_zone_post_type(),
		'fields'          => 'ids',
		'posts_per_page'  => 1,
		'meta_key'        => "_adv_zone_{$cart}_product_id",
		'meta_value'      => $product_id,
	);
	$zone                 = get_posts( $query_args );

	if( empty( $zone ) ) {
		return 0;
	}

	return $zone[0];
}


/**
 * Retrieves a single zone
 */
function adv_get_zone( $zone ) {
	return new Adv_Zone( $zone );
}

/**
 * Retrieves the HTML needed to display a zone
 */
function ads_get_zone_html( $zone ) {
	$zone = adv_get_zone( $zone );
	return $zone->get_html();
}

function adv_dropdown_zones( $args = array() ) {

	// Prepare arguments
	$defaults = array(
		'echo'          => 1,
		'multi'         => false,
		'selected'      => '-1',
		'name'          => 'zone',
		'class'         => 'adv-zones-list bg-light',
		'id'            => uniqid( 'advertising-zone' ),
		'placeholder'   => __( 'Select Zone', 'advertising' ),
		'label'         => __( 'Advertisement Zone', 'advertising' ),
		'help_text'     => __( 'Select the zone where this ad will be shown', 'advertising' ),
		'label_type'    => 'top',
		'is_gd'         => false,
		'hide_full'     => false
	);

	$args = wp_parse_args( $args, $defaults );

	if ( ! empty( $args['is_gd'] ) ) {
		$zones = '<input name="' . esc_attr( $args['name'] ) . '" type="hidden" value="' . esc_attr( $args['selected'] ) . '" /><input type="text" class="regular-text p-1" value="' . esc_attr( get_the_title( $args['selected'] ) ) . '" readonly="readonly" />';
	} else {
		$zones = aui()->select(
			array(
				'id'          => $args['id'],
				'label'       => $args['label'],
				'label_class' => '',
				'class'       => $args['class'],
				'name'        => $args['name'],
				'value'       => $args['selected'],
				'placeholder' => $args['placeholder'],
				'required'    => true,
				'label_type'  => $args['label_type'],
				'options'     => adv_get_dropdown_zones( ! empty( $args['hide_full'] ) ),
				'multiple'    => ! empty( $args['multi'] ),
			)
		);
	}

	$html = apply_filters( 'adv_dropdown_zones', $zones, $args );

	if ( $args['echo'] ) {
		echo $html;
	} else {
		return $html;
	}

}

/**
 * Retrieves a list of zones for use with dropdowns.
 */
function adv_get_dropdown_zones( $hide_full = false ) {

	// Get a list of all zones.
	$all_zones          = adv_get_zones( array( 'fields'        => 'all' ) );

	// And filter them as id => title.
	$all_zones          = wp_list_pluck( $all_zones, 'post_title', 'ID' );
	$_all_zones         = array();

	// Add meta information.
	foreach ( $all_zones as $id => $title ) {

		if ( ! is_admin() && '1' == adv_zone_get_meta( $id, 'hide_frontend', true, 0 ) ) {
			continue;
		}

		if ( $hide_full ) {
			$max_ads = (int) adv_zone_get_meta( $id, 'max_ads', true, '' );

			if ( $max_ads > 0 ) {
				$adv_zone = adv_get_zone( $id );

				// Zone is full
				if ( ! empty( $adv_zone ) && $adv_zone->is_full() ) {
					continue;
				}
			}
		}

		$size              = adv_zone_size( $id, true );
		$price             = adv_zone_price( $id );
		$_all_zones[ $id ] = esc_html( "$title ($size) &mdash; $price" );

	}

	return $_all_zones;
}

//Returns the ad size for a zone
function adv_zone_size( $zone_id, $any = false ) {
	if ( empty( $zone_id ) ) {
		return NULL;
	}

	//Default size is any
	$size    = $any ? __( 'Any size', 'advertising' ) : '-';

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

	return apply_filters( 'adv_zone_size', $size, $zone_id, $any );
}

/**
 * Logs zone views.
 */
function adv_log_zone_view( $zone_id ) {

	// Do not log views from site admins...
	if ( current_user_can( 'manage_options' ) ) {
		return;
	}

	// Log this impression.
	$views  = (int)get_post_meta( $zone_id, '_adv_zone_views', true );
	$clicks = (int)get_post_meta( $zone_id, '_adv_zone_clicks', true );

	update_post_meta( $zone_id, '_adv_zone_views', $views + 1 );
	update_post_meta( $zone_id, '_adv_zone_ctr', adv_calculate_ctr( $clicks, $views + 1 ) );
}


function adv_get_zone_advertisement_url( $zone_id ) {

	$package_link = adv_zone_get_meta( $zone_id, 'link_to_packages', true, false );

	if ( ! empty( $package_link ) ) {
		return false;
	}

	$url = adv_zone_get_meta( $zone_id, 'advertise_here_url', true, '' );

	if ( empty( $url ) ) {
		$url = adv_dashboard_endpoint_url( 'new-ad', array( 'zone' => $zone_id ) );
	}

	return $url;
}

function adv_get_pricing_types() {
	return array(
		'impressions' => __( 'Impressions', 'advertising' ),
		'clicks'      => __( 'Clicks', 'advertising' ),
		'days'        => __( 'Days', 'advertising' ),
	);
}

function adv_get_pricing_type_title( $type ) {
	if ( ! $type ) {
		return $type;
	}

	$types = adv_get_pricing_types();

	$title = $type && isset( $types[ $type ] ) ? $types[ $type ] : __( $type, 'advertising' );

	return $title;
}