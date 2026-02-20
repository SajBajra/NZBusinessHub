<?php
/**
 * Post Types
 *
 * Registers post types and statuses.
 *
 */

defined( 'ABSPATH' ) || exit;

/**
 * Post types Class
 *
 */
class Adv_Post_Types {

    /**
	 * Hook in methods.
	 */
	public function __construct() {
		add_action( 'init', array( __CLASS__, 'register_post_types' ), 1 );
		add_action( 'adv_flush_rewrite_rules', array( __CLASS__, 'flush_rewrite_rules' ) );
		add_action( 'init', array( __CLASS__, 'maybe_flush_rewrite_rules' ), 20 );
	}

	/**
	 * Register core post types.
	 */
	public static function register_post_types() {

		if ( ! is_blog_installed() || post_type_exists( adv_zone_post_type() ) ) {
			return;
		}

		// Fires before registering post types.
		do_action( 'adv_register_post_types' );

		// Register ad post type.
		register_post_type(
			adv_ad_post_type(),
			apply_filters(
				'adv_register_post_type_advert',
				array(
					'labels'             => array(
						'name'               => _x( 'Ads', 'post type general name', 'advertising' ),
						'singular_name'      => _x( 'Ad', 'post type singular name', 'advertising' ),
						'menu_name'          => _x( 'Ads', 'admin menu', 'advertising' ),
						'name_admin_bar'     => _x( 'Ad', 'add new on admin bar', 'advertising' ),
						'add_new'            => _x( 'Add New', 'Ad', 'advertising' ),
						'add_new_item'       => __( 'Add New Ad', 'advertising' ),
						'new_item'           => __( 'New Ad', 'advertising' ),
						'edit_item'          => __( 'Edit Ad', 'advertising' ),
						'view_item'          => __( 'View Ad', 'advertising' ),
						'all_items'          => __( 'Ads', 'advertising' ),
						'search_items'       => __( 'Search ads', 'advertising' ),
						'parent_item_colon'  => __( 'Parent ad:', 'advertising' ),
						'not_found'          => __( 'No ads found.', 'advertising' ),
						'not_found_in_trash' => __( 'No ads found in trash.', 'advertising' )
					),
					'description'           => __( 'This is where you can add new advert.', 'advertising' ),
					'public'                => false,
					'has_archive'           => false,
					'_builtin'              => false,
					'show_ui'               => true,
					'show_in_menu'          => 'advertising',
					'show_in_nav_menus'     => false,
					'supports'              => array( 'title' ),
					'rewrite'               => false,
					'query_var'             => false,
					'map_meta_cap'          => true,
					'show_in_admin_bar'     => true,
					'can_export'            => true,
				)
			)
		);

		// Register zone post type.
		register_post_type(
			adv_zone_post_type(),
			apply_filters(
				'adv_register_post_type_advertising_zone',
				array(
					'labels'             => array(
						'name'               => _x( 'Zones', 'post type general name', 'advertising' ),
						'singular_name'      => _x( 'Zone', 'post type singular name', 'advertising' ),
						'menu_name'          => _x( 'Zones', 'admin menu', 'advertising' ),
						'name_admin_bar'     => _x( 'Zone', 'add new on admin bar', 'advertising' ),
						'add_new'            => _x( 'Add New', 'Advertising one', 'advertising' ),
						'add_new_item'       => __( 'Add New Zone', 'advertising' ),
						'new_item'           => __( 'New Zone', 'advertising' ),
						'edit_item'          => __( 'Edit Zone', 'advertising' ),
						'view_item'          => __( 'View Zone', 'advertising' ),
						'all_items'          => __( 'Zones', 'advertising' ),
						'search_items'       => __( 'Search Zones', 'advertising' ),
						'parent_item_colon'  => __( 'Parent Zones:', 'advertising' ),
						'not_found'          => __( 'No zones found.', 'advertising' ),
						'not_found_in_trash' => __( 'No zones found in trash.', 'advertising' )
					),
					'description'        => __( 'Add new advertising zones.', 'advertising' ),
					'public'             => false,
					'show_ui'            => true,
					'show_in_menu'       => 'advertising',
					'show_in_nav_menus'  => false,
					'query_var'          => false,
					'rewrite'            => true,
					'map_meta_cap'       => true,
					'has_archive'        => false,
					'hierarchical'       => false,
					'menu_position'      => null,
					'supports'           => array( 'title' ),
				)
			)
		);

		do_action( 'adv_after_register_post_types' );
	}

	/**
	 * Flush rewrite rules.
	 */
	public static function flush_rewrite_rules() {
		flush_rewrite_rules();
	}

	/**
	 * Flush rules to prevent 404.
	 *
	 */
	public static function maybe_flush_rewrite_rules() {
		if ( ! get_option( 'adv_flushed_rewrite_rules' ) ) {
			update_option( 'adv_flushed_rewrite_rules', '1' );
			self::flush_rewrite_rules();
		}
	}

}
