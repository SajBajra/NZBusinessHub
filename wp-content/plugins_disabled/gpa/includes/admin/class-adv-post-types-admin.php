<?php
/**
 * Post Types Admin.
 *
 */

defined( 'ABSPATH' ) || exit;

/**
 * Post types Admin Class
 *
 */
class Adv_Post_Types_Admin {

    /**
	 * Hook in methods.
	 */
	public static function init() {

		// Init metaboxes.
		Adv_Metaboxes::init();

		// Filter the post updated messages.
		add_filter( 'post_updated_messages', array( __CLASS__, 'post_updated_messages' ) );

		// Zone table columns.
		add_filter( 'manage_edit-' . adv_zone_post_type() . '_columns',              array( __CLASS__, 'zone_columns' ),     10, 1 );
		add_filter( 'manage_edit-' . adv_zone_post_type() . '_sortable_columns',     array( __CLASS__, 'sortable_zone_columns' ), 10, 1 );
		add_action( 'manage_'      . adv_zone_post_type() . '_posts_custom_column' , array( __CLASS__, 'display_zone_columns' ),    10, 2 );
		add_action( 'request', array( __CLASS__, 'reorder_zones' ), 100 );

		// Ad table columns.
		add_filter( 'manage_edit-' . adv_ad_post_type() . '_columns',              array( __CLASS__, 'ad_columns' ),     10, 1 );
		add_filter( 'manage_edit-' . adv_ad_post_type() . '_sortable_columns',     array( __CLASS__, 'sortable_ad_columns' ), 10, 1 );
		add_action( 'manage_'      . adv_ad_post_type() . '_posts_custom_column' , array( __CLASS__, 'display_ad_columns' ),    10, 2 );
		add_action( 'request', array( __CLASS__, 'reorder_ads' ), 100 );

	}

	/**
	 * Post updated messages.
	 */
	public static function post_updated_messages( $messages ) {
		global $post;

		$messages[ adv_zone_post_type() ] = array(
			0 => '', // Unused. Messages start at index 1.
			1 => __( 'Zone updated.' , 'advertising' ),
			2 => __( 'Custom field updated.', 'advertising' ),
			3 => __( 'Custom field deleted.', 'advertising' ),
			4 => __( 'Zone updated.' , 'advertising' ),
			5 => '',
			6 => __( 'Zone published.' , 'advertising' ),
			7 => __( 'Zone saved.' , 'advertising' ),
			8 => __( 'Zone submitted.' , 'advertising' ),
			9 => sprintf( __( 'Zone scheduled for: <strong>%1$s</strong>.' , 'advertising' ),
			  // translators: Publish box date format, see http://php.net/date
			  date_i18n( __( 'M j, Y @ G:i' ), strtotime( $post->post_date ) ) ),
			10 => __( 'Zone draft updated.', 'advertising' ),
		);

		$messages[ adv_ad_post_type() ] = array(
			0 => '', // Unused. Messages start at index 1.
			1 => __( 'Ad updated.' , 'advertising' ),
			2 => __( 'Custom field updated.', 'advertising' ),
			3 => __( 'Custom field deleted.', 'advertising' ),
			4 => __( 'Ad updated.' , 'advertising' ),
			5 => '',
			6 => __( 'Ad published.' , 'advertising' ),
			7 => __( 'Ad saved.' , 'advertising' ),
			8 => __( 'Ad submitted.' , 'advertising' ),
			9 => sprintf( __( 'Ad scheduled for: <strong>%1$s</strong>.' , 'advertising' ),
			  // translators: Publish box date format, see http://php.net/date
			  date_i18n( __( 'M j, Y @ G:i' ), strtotime( $post->post_date ) ) ),
			10 => __( 'Ad draft updated.', 'advertising' ),
		);

		return $messages;

	}

	/**
	 * Returns an array of zone table columns.
	 */
	public static function zone_columns( $columns ) {

		if ( isset( $columns['date'] ) ) {
			unset( $columns['date'] );
		}

		$columns['size']        = __( 'Ad Size', 'advertising' );
		$columns['total_ads']   = __( 'Ads', 'advertising' );
		$columns['ctr']         = __( 'CTR', 'advertising' );
		$columns['clicks']      = __( 'Clicks', 'advertising' );
		$columns['impressions'] = __( 'Impressions', 'advertising' );
		$columns['shortcode']   = __( 'Shortcode', 'advertising' );

		return $columns;
	}

	/**
	 * Returns an array of sortable zone table columns.
	 */
	public static function sortable_zone_columns( $sortable_columns ) {

		$sortable_columns[ 'size' ]         = 'width';
		$sortable_columns[ 'ctr' ]          = 'ctr';
		$sortable_columns[ 'clicks' ]       = 'clicks';
		$sortable_columns[ 'impressions' ]  = 'impressions';

		return $sortable_columns;

	}

	/**
	 * Displays zone table columns.
	 */
	public static function display_zone_columns( $column_name, $post_id ) {

		switch ( $column_name ) {

			case 'size':
				echo adv_zone_ad_size( $post_id, true );
			break;
			case 'total_ads':
				if ( $ads = adv_get_ads_by_zone( $post_id ) ) {
					echo count( $ads );
				} else {
					echo 0;
				}
			break;
			case 'ctr':
				echo adv_zone_ctr( $post_id, true );
			break;
			case 'clicks':
				echo adv_zone_clicks( $post_id, true );
			break;
			case 'impressions':
				echo adv_zone_impressions( $post_id, true );
			break;
			case 'shortcode':
				echo '<input style="max-width: 100%;" type="text" class="adv-shortcode" onclick="this.select();" readonly value="' . esc_attr( adv_zone_shortcode( $post_id, 'views' ) ) . '" />';
			break;

		}

	}

	/**
	 * Returns an array of ad table columns.
	 */
	public static function ad_columns( $columns ) {

		if ( isset( $columns['date'] ) ) {
			unset( $columns['date'] );
		}

		$columns['author']      = __( 'Advertiser', 'advertising' );
		$columns['zone']        = __( 'Zone', 'advertising' );
        $columns['type']        = __( 'Type', 'advertising' );
        $columns['ctr']         = __( 'CTR', 'advertising' );
        $columns['clicks']      = __( 'Clicks', 'advertising' );
        $columns['impressions'] = __( 'Impressions', 'advertising' );
		$columns['ad_status']   = __( 'Status', 'advertising' );
		$columns['shortcode']   = __( 'Shortcode', 'advertising' );

		return $columns;
	}

	/**
	 * Returns an array of sortable ad table columns.
	 */
	public static function sortable_ad_columns( $sortable_columns ) {

		$sortable_columns[ 'author' ]       = 'advertiser';
        $sortable_columns[ 'type' ]         = 'type';
		$sortable_columns[ 'ctr' ]          = 'ctr';
		$sortable_columns[ 'clicks' ]       = 'clicks';
		$sortable_columns[ 'impressions' ]  = 'impressions';

		return $sortable_columns;

	}

	/**
	 * Displays ad table columns.
	 */
	public static function display_ad_columns( $column_name, $post_id ) {

		$post = get_post( $post_id );
		switch ( $column_name ) {

			case 'author':
                echo '<a href="'. get_edit_user_link( $post->post_author ) .'">' . adv_advertiser_name( $post->post_author ) . '</a>';
            break;
			case 'zone':
				echo adv_ad_zone( $post_id, true );
			break;
			case 'type':
				echo adv_ad_type( $post_id, true );
			break;
			case 'ctr':
				echo adv_ad_ctr( $post_id, true );
			break;
			case 'clicks':
				echo adv_ad_clicks( $post_id, true );
			break;
			case 'impressions':
				echo adv_ad_impressions( $post_id, true );
			break;
			case 'ad_status':
				echo adv_ad_current_status( $post_id, true );
			break;
			case 'shortcode':
				echo '<input type="text" style="max-width: 100%;" class="adv-shortcode" onclick="this.select();" readonly value="[ads_ad id=' . absint( $post_id ) . ']" />';
			break;

		}

	}

	/**
	 * Reorders ads.
	 */
	public static function reorder_ads( $vars ) {
		global $typenow;

		if ( ! is_admin() || adv_ad_post_type() !== $typenow || empty( $vars['orderby'] ) ) {
			return $vars;
		}

		foreach ( array( 'ctr', 'clicks', 'impressions' ) as $order_by ) {

			if ( $order_by == $vars['orderby'] ) {
				return array_merge(
					$vars,
					array(
						'meta_key' => "_adv_ad_$order_by",
						'orderby'  => 'meta_value_num'
					)
				);
			}

		}

		foreach ( array( 'zone', 'type' ) as $order_by ) {

			if ( $order_by == $vars['orderby'] ) {
				return array_merge(
					$vars,
					array(
						'meta_key' => "_adv_ad_$order_by",
						'orderby'  => 'meta_value'
					)
				);
			}

		}

		// By advertiser.
		if ( $order_by == $vars['orderby'] ) {
			return array_merge(
				$vars,
				array(
					'orderby'  => 'post_author'
				)
			);
		}

		return $vars;

	}

	/**
	 * Reorders ads.
	 */
	public static function reorder_zones( $vars ) {
		global $typenow;

		if ( ! is_admin() || adv_ad_post_type() !== $typenow || empty( $vars['orderby'] ) ) {
			return $vars;
		}

		foreach ( array( 'ctr', 'clicks', 'impressions', 'width' ) as $order_by ) {

			if ( $order_by == $vars['orderby'] ) {
				return array_merge(
					$vars,
					array(
						'meta_key' => "_adv_ad_$order_by",
						'orderby'  => 'meta_value_num'
					)
				);
			}

		}

		return $vars;

	}

}
