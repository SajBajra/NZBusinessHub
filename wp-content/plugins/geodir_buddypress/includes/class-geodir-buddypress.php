<?php

class GeoDir_BuddyPress {

    private static $instance;

    private $version = GEODIR_BUDDYPRESS_VERSION;

    public static function get_instance() {
        if ( ! isset( self::$instance ) && ! ( self::$instance instanceof GeoDir_BuddyPress ) ) {
            self::$instance = new GeoDir_BuddyPress;
            self::$instance->setup_globals();
            self::$instance->load_textdomain();
            self::$instance->includes();
            self::$instance->define_admin_hooks();
            self::$instance->define_public_hooks();

            do_action( 'geodir_buddypress_loaded' );
        }

        return self::$instance;
    }

    private function __construct() {
        self::$instance = $this;
    }

    private function setup_globals() {

    }

    /**
     * Register all of the hooks related to the admin area functionality
     * of the plugin.
     *
     * @access   private
     */
    private function define_admin_hooks() {

        $plugin_admin = new GeoDir_BuddyPress_Admin();

        add_action('admin_init', array( $plugin_admin, 'activation_redirect'));

        add_action( 'admin_enqueue_scripts', array( $plugin_admin, 'admin_scripts'), 11);

        add_action( 'admin_enqueue_scripts', array( $plugin_admin, 'admin_styles'), 11);

        add_filter( 'geodir_get_settings_pages', array( $plugin_admin, 'load_settings_page' ), 20, 1 );

        do_action( 'gd_buddypress_setup_admin_actions' );
    }

    /**
     * Register all of the hooks related to the public-facing functionality
     * of the plugin.
     *
     * @since    2.0.0
     * @access   private
     */
    private function define_public_hooks() {
        $plugin_public = new GeoDir_BuddyPress_Public();

        add_action('wp_enqueue_scripts', array($plugin_public, 'enqueue_styles'));

        add_action('wp', array($plugin_public, 'geodir_buddypress_author_redirect'));

        add_action('bp_setup_nav', array($plugin_public, 'geodir_buddypress_setup_nav'), 11);

        add_filter('geodir_dashboard_link_my_listing', array($plugin_public, 'geodir_buddypress_link_my_listing'), 10, 3);

        add_filter('geodir_dashboard_link_favorite_listing', array($plugin_public, 'geodir_buddypress_link_favorite_listing'), 10, 3);

        add_filter('author_link', array($plugin_public, 'geodir_buddypress_bp_author_link'), 11, 2);

        add_filter('bp_blogs_record_post_post_types', array($plugin_public, 'geodir_buddypress_record_geodir_post_types'), 10, 1);

        add_filter('bp_blogs_record_comment_post_types', array($plugin_public, 'geodir_buddypress_record_comment_post_types'), 10, 1);

        add_filter('bp_blogs_format_activity_action_new_blog_post', array($plugin_public, 'geodir_buddypress_new_listing_activity'), 99, 2);

        add_filter('bp_activity_comment_action', array($plugin_public, 'geodir_buddypress_new_listing_comment_activity'), 98, 2);
		add_filter('bp_blogs_format_activity_action_new_blog_comment', array($plugin_public, 'geodir_buddypress_new_listing_comment_activity'), 99, 2);

        add_filter('bp_activity_get_activity_id', array($plugin_public, 'geodir_buddypress_get_activity_id'), 99);

        add_filter('bp_get_activity_content_body', array($plugin_public, 'geodir_buddypress_bp_activity_featured_image'), 1, 1);

        add_filter('bp_disable_blogforum_comments', array($plugin_public, 'geodir_buddypress_disable_comment_as_review'), 10, 1);

        //Notifications
        add_filter('notify_post_author', array( $plugin_public, 'notify_listing_owner'), 10, 2 );
        add_filter( 'bp_notifications_get_registered_components', array( $plugin_public, 'register_component' ) );
        add_filter( 'bp_notifications_get_notifications_for_user', array( $plugin_public, 'format_notifications' ), 10, 8 );

        // Filter GD Listings by author on BP Profile page.
        add_filter( 'widget_post_author', array( $plugin_public, 'gd_listings_post_author' ), 20, 3 );
        add_filter( 'widget_favorites_by_user', array( $plugin_public, 'gd_listings_favorites_by_user' ), 20, 3 );

        do_action( 'gd_buddypress_setup_actions' );
    }

	/**
	 * Load the text domain.
	 */
	public function load_textdomain() {
		// Determines the current locale.
		if ( function_exists( 'determine_locale' ) ) {
			$locale = determine_locale();
		} else if ( function_exists( 'get_user_locale' ) ) {
			$locale = get_user_locale();
		} else {
			$locale = get_locale();
		}

		$locale = apply_filters( 'plugin_locale', $locale, 'geodir-buddypress' );

		unload_textdomain( 'geodir-buddypress' );
		load_textdomain( 'geodir-buddypress', WP_LANG_DIR . '/geodir-buddypress/geodir-buddypress-' . $locale . '.mo' );
		load_plugin_textdomain( 'geodir-buddypress', false, basename( dirname( GEODIR_BUDDYPRESS_PLUGIN_FILE ) ) . '/languages/' );
	}

    /**
     * Include the files.
     */
    private function includes() {
        require_once( GEODIR_BUDDYPRESS_PLUGIN_PATH . '/admin/class-geodir-buddypress-admin.php' );
        require_once( GEODIR_BUDDYPRESS_PLUGIN_PATH . '/public/class-geodir-buddypress-public.php' );
        require_once( GEODIR_BUDDYPRESS_PLUGIN_PATH . '/includes/general-functions.php' );
        require_once( GEODIR_BUDDYPRESS_PLUGIN_PATH . '/includes/class-geodir-buddypress-template.php' );
    }
}