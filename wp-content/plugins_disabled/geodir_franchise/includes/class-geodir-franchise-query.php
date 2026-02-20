<?php
/**
 * Franchise Manager Query class.
 *
 * @since 2.0.0
 * @package Geodir_Franchise
 * @author AyeCode Ltd
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * GeoDir_Franchise_Query class.
 */
class GeoDir_Franchise_Query {

	function __construct() {
		add_filter( 'geodir_posts_where', array( __CLASS__, 'posts_where' ), 11, 2 );
		add_filter( 'geodir_widget_listings_query_args', array( __CLASS__, 'listings_widget_query_args' ), 101, 2 );
		add_filter( 'geodir_filter_widget_listings_where', array( __CLASS__, 'widget_posts_where' ), 11, 2 );
	}

	public static function posts_where( $where, $query = array() ) {
		global $geodir_post_type;

		if ( ! ( ! empty( $query ) && GeoDir_Query::is_gd_main_query( $query ) ) ) {
			return $where;
		}

		$franchise_of = ! empty( $_REQUEST['franchise_of'] ) ? absint( $_REQUEST['franchise_of'] ) : 0;
		if ( ( $query_franchise_of = get_query_var( 'franchise_of' ) ) ) {
			$franchise_of = $query_franchise_of;
		}

		if ( empty( $franchise_of ) ) {
			return $where;
		}
	
		$where .= self::franchise_query_where( $franchise_of, $geodir_post_type );

		return $where;
	}

	public static function listings_widget_query_args( $query_args, $instance ) {
		if ( ! empty( $instance['franchise_of'] ) ) {
			$query_args['franchise_of'] = $instance['franchise_of'];
		}
		return $query_args;
	}

	public static function widget_posts_where( $where, $post_type ) {
		global  $wpdb, $gd_post, $gd_query_args_widgets;

		if ( empty( $gd_query_args_widgets['franchise_of'] ) ) {
			return $where;
		}

		$where .= self::franchise_query_where( $gd_query_args_widgets['franchise_of'], $post_type );

		return $where;
	}

	public static function franchise_query_where( $franchise_of, $post_type, $table = '' ) {
		global  $wpdb, $gd_post;

		$query_where = '';
		if ( empty( $franchise_of ) ) {
			return $query_where;
		}

		if ( GeoDir_Post_types::supports( $post_type, 'franchise' ) ) {
			$main_listing_id = 0;
			$franchise_id = 0;

			if ( $franchise_of == 'auto' ) {
				if ( ! empty( $gd_post->ID ) ) {
					if ( geodir_franchise_is_main( (int) $gd_post->ID ) ) {
						$main_listing_id = (int) $gd_post->ID;
					} else if ( geodir_franchise_is_franchise( (int) $gd_post->ID ) ) {
						$franchise_id = (int) $gd_post->ID;
						$main_listing_id = geodir_franchise_main_post_id( (int) $franchise_id );
					}
				}
			} else {
				if ( geodir_franchise_is_main( (int) $franchise_of ) ) {
					$main_listing_id = (int) $franchise_of;
				}
			}

			if ( $main_listing_id ) {
				$include_main = geodir_get_option( 'franchise_show_main' );
				$include_franchise = geodir_get_option( 'franchise_show_viewing' );

				if ( empty( $table ) ) {
					$table = geodir_db_cpt_table( $post_type );
				}

				$query_where .= " AND";
				if ( $include_main ) {
					$query_where .= " (";
				}
				$query_where .= $wpdb->prepare( " `{$table}`.`franchise_of` = %d", array( $main_listing_id ) );
				if ( $include_main ) {
					// Show main listing
					$query_where .= $wpdb->prepare( " OR `{$table}`.`post_id` = %d", array( $main_listing_id ) );
				}
				if ( $include_main ) {
					$query_where .= " )";
				}

				// Hide view franchise
				if ( $franchise_id && ! $include_franchise ) {
					$query_where .= $wpdb->prepare( " AND `{$table}`.`post_id` != %d", array( $franchise_id ) );
				}
			} else {
				$query_where .= " AND {$wpdb->posts}.ID = '-1'";
			}
		} else {
			$query_where .= " AND {$wpdb->posts}.ID = '-1'";
		}

		return $query_where;
	}
}