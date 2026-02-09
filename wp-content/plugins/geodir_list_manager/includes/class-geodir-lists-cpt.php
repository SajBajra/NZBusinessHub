<?php

// Check GeoDir_Lists_CPT class exists or not.
if ( ! class_exists( 'GeoDir_Lists_CPT' ) ) {

	/**
	 * GeoDir_Lists_CPTs Class for the CPT actions.
	 *
	 * @since 2.0.0
	 *
	 * Class GeoDir_Lists_CPT
	 */
	class GeoDir_Lists_CPT {

		/**
		 * Constructor.
		 *
		 * @since 2.0.0
		 *
		 * GeoDir_Lists_CPT constructor.
		 */
		public function __construct() {

			add_action( 'init', array( $this, 'register_lists_post_type' ) );
			add_action( 'admin_menu', array( $this, 'cpt_settings_menu' ), 10 );
			add_filter( 'geodir_settings_tabs_array' ,array( $this, 'remove_unused_tabs' ) , 100 );
			add_filter( 'geodir_get_settings_cpt', array( $this, 'cpt_settings_inputs' ), 100, 3 );
			add_filter( 'geodir_cpt_settings_cpt_options', array( $this, 'cpt_settings_values' ), 100, 2 );
			add_filter( 'geodir_post_type_save_bypass', array( $this, 'save_cpt_settings' ), 10, 3 );

			if ( ! is_admin() ) {
				add_filter( 'pre_get_posts', array( $this, 'maybe_filter_post_status' ) );
			}
		}

		/**
		 * Add a filter to the query if on a single list page.
		 * 
		 * @param $query
		 *
		 * @return mixed
		 */
		public function maybe_filter_post_status( $query ) {
			if ( $query->is_main_query() && $query->is_singular() && ! empty( $query->query_vars['post_type'] ) && $query->query_vars['post_type'] == 'gd_list' ) {
				add_filter( 'posts_results', array( $this, 'set_post_to_publish' ), 10, 2 );
			}

			return $query;
		}

		/**
		 * Sets the post status of the list to publish on the fly if its private.
		 * 
		 * This allows private lists to be viewed if the direct lin
		 *
		 * @since 2.0.0
		 *
		 * @param  array $posts The post to preview.
		 * @return array The post that is being previewed.
		 */
		public static function set_post_to_publish( $posts ) {
			// Remove the filter again, otherwise it will be applied to other queries too.
			remove_filter( 'posts_results', array( __CLASS__, 'set_post_to_publish' ), 10 );

			if ( empty( $posts ) ) {
				return $posts;
			}

			// Check id post has no author and if the current user owns it
			if ( isset( $posts[0]->post_status ) && $posts[0]->post_status == 'private' ) {
				$posts[0]->post_status = 'publish';

				// Disable comments and pings for this post.
				add_filter( 'comments_open', '__return_false' );
				add_filter( 'pings_open', '__return_false' );
			}

			return $posts;
		}

		/**
		 * Bypass the normal save of the CPT settings and save out values.
		 *
		 * @param $bypass
		 * @param $cpt
		 * @param $current_section
		 *
		 * @return string
		 */
		public function save_cpt_settings( $bypass, $cpt, $current_section ) {
			if ( ! empty( $_REQUEST['post_type'] ) && $_REQUEST['post_type'] == 'gd_list' ) {
				$bypass = 'true';

				if ( ! empty( $cpt['gd_list'] ) ) {
					// Update custom post types
					geodir_update_option( 'list_post_type', $cpt['gd_list'] );
				}
			}

			return $bypass;
		}

		/**
		 * Set the gd_list CPT values for the settings page.
		 *
		 * @param $post_type_option
		 * @param $post_type
		 *
		 * @return array
		 */
		public function cpt_settings_values( $post_type_option, $post_type ) {
			if ( $post_type == 'gd_list' ) {
				$post_type_option = self::post_type_args();
			}

			return $post_type_option;
		}

		/**
		 * Filter the CPT settings inputs.
		 *
		 * @param $settings
		 * @param $current_section
		 * @param $post_type_values
		 *
		 * @return mixed
		 */
		public function cpt_settings_inputs( $settings, $current_section, $post_type_values ) {
			if ( ! empty( $_REQUEST['post_type']) && $_REQUEST['post_type'] == 'gd_list' ) {
				// Unused items
				$unused_settings = array(
					'disable_reviews',
					'disable_frontend_add',
					'disable_favorites',
					'order',
					'disable_location',
					'supports_events',
					'author_posts_private',
					'author_favorites_private',
					'cpt_settings_author',
					'cpt_settings_description',
					'description',
					'cpt_settings_seo',
					'title',
					'meta_title',
					'meta_description',
					'cpt_settings_page',
					'page_details',
					'page_archive',
					//'page_archive_item',
					'disable_comments',
					'single_review',
					'prev_supports_events',
					'prev_disable_location',
					'supports_franchise',
					'prev_supports_franchise',
					'limit_posts',
					'page_add',
					'cpt_settings_classifieds',
					'classified_features',
					'prev_classified_features',
					'cpt_settings_link_posts',
					'fill_fields'                );

				foreach( $settings as $key => $setting ) {
					// Remove any unused items
					if ( ! empty( $setting['id'] ) && in_array( $setting['id'], $unused_settings ) ) {
						unset( $settings[$key] );
					}

					// Make slug cpt non changeable
					if ( ! empty( $setting['id'] ) && $setting['id'] == 'post_type' ) {
						$settings[$key]['custom_attributes']['disabled'] = true;
					}
				}
			}

			return $settings;
		}

		/**
		 * Remove any unused settings tabs for the gd_list CPT.
		 *
		 * @param $tabs
		 *
		 * @return array
		 */
		public function remove_unused_tabs( $tabs ) {
			if ( ! empty( $_REQUEST['post_type'] ) && $_REQUEST['post_type'] == 'gd_list' ) {
				$tabs = array();// reset;
				$tabs['cpt'] = __('General','gd-lists');
			}

			return $tabs;
		}

		/**
		 * Add CPT Settings menu.
		 */
		public function cpt_settings_menu(){
			// Add CPT setting to each GD CPT
			$post_types = geodir_get_option( 'post_types' );
			if(!empty($post_types)){
				foreach($post_types as $name => $cpt){
					//echo '###'.$name;
					//print_r($cpt);
					//add_submenu_page('edit.php?post_type='.$name, __('Settings','gd-lists'), __('Settings','gd-lists'), 'manage_options', $name.'-settings', array( $this, 'settings_page' ) );
				}
			}

			$name = 'gd_list';
			add_submenu_page('edit.php?post_type='.$name, __('Settings','gd-lists'), __('Settings','gd-lists'), 'manage_options', $name.'-settings', array( $this, 'settings_page' ) );

		}

		/**
		 * Init the settings page.
		 */
		public function settings_page() {
			GeoDir_Admin_Settings::output('cpt');
		}

		/**
		 * Register Lists post type.
		 *
		 * @since 2.0.0
		 */
		public function register_lists_post_type() {
			if ( ! post_type_exists('gd_list') ) {

				$args = self::post_type_args();

				register_post_type( 'gd_list', $args );
			}
		}

		/**
		 * Get the CPT args for registering the CPT.
		 *
		 * @return array
		 */
		public static function post_type_args(){
			$labels = array(
				'name'               => _x( 'Lists', 'post type general name', 'gd-lists' ),
				'singular_name'      => _x( 'List', 'post type singular name', 'gd-lists' ),
				'menu_name'          => _x( 'Lists', 'admin menu', 'gd-lists' ),
				'name_admin_bar'     => _x( 'List', 'add new on admin bar', 'gd-lists' ),
				'add_new'            => _x( 'Add New', 'Lists', 'gd-lists' ),
				'add_new_item'       => __( 'Add New List', 'gd-lists' ),
				'new_item'           => __( 'New List', 'gd-lists' ),
				'edit_item'          => __( 'Edit List', 'gd-lists' ),
				'view_item'          => __( 'View List', 'gd-lists' ),
				'all_items'          => __( 'All Lists', 'gd-lists' ),
				'search_items'       => __( 'Search Lists', 'gd-lists' ),
				'parent_item_colon'  => __( 'Parent Lists:', 'gd-lists' ),
				'not_found'          => __( 'No lists found.', 'gd-lists' ),
				'not_found_in_trash' => __( 'No lists found in Trash.', 'gd-lists' )
			);

			$args = array(
				'labels'             => $labels,
				'description'        => __( 'Description.', 'gd-lists' ),
				'public'             => true,
				'publicly_queryable' => true,
				'show_ui'            => true,
				'show_in_menu'       => true,
				'query_var'          => true,
				'rewrite'            => array('slug' => 'lists', 'with_front' => false, 'hierarchical' => true),
				'capability_type'    => 'post',
				'has_archive'        => 'lists',
				'hierarchical'       => false,
				'menu_position'      => null,
				'menu_icon'          => 'dashicons-admin-post',
				'supports'           => array( 'title', 'editor', 'author' ),
				'page_details'       => (int) geodir_get_option( 'list_page_single' ),
				'page_archive_item'  => (int) geodir_get_option( 'list_page_archive_item' )
			);

			if ( defined( 'ELEMENTOR_PRO_VERSION' ) ) {
				$args['elementor_skin'] = 0;
				$args['elementor_skin_columns'] = 2;
				$args['elementor_skin_column_gap'] = 30;
				$args['elementor_skin_row_gap'] = 30;
			}

			$cpt = geodir_get_option('list_post_type');

			$args = wp_parse_args( $cpt, $args  );

			return $args;
		}

	}

	new GeoDir_Lists_CPT();
}