<?php
/**
 * Contains the main plugin class.
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The main plugin class.
 *
 */
class Advertising {

	/**
	 * Query instance.
	 *
	 * @var Adv_Query
	 */
	public $query = null;

    /**
     * Plugin options.
     *
     * @var array
     */
    public $options;

    /**
     * Current cart.
     *
     * @var Adv_Cart_Core
     */
    public $cart;

    /**
     * Post  types
     *
     * @var Adv_Post_Types
     */
    public $post_types;

    /**
	 * The class constructor.
	 *
	 * @since 1.0.0
	 * @return Advertising
	 */
	public function __construct() {

        // Only proceed if we are able to register an autoloader.
		try {
            spl_autoload_register( array( $this, 'autoload' ), true );
            $this->includes();
            $this->init_hooks();
            do_action( 'adv_loaded' );
		} catch ( Exception $e ) {
			error_log( $e->getMessage() . __FILE__ );
        }

	}

    /**
	 * Class autoloader
	 *
	 * @param       string $class_name The name of the class to load.
	 * @access      public
	 * @since       1.0.19
	 * @return      void
	 */
	public function autoload( $class_name ) {

		// Normalize the class name...
		$class_name  = strtolower( $class_name );

		// ... and make sure it is our class.
		if ( false === strpos( $class_name, 'adv_' ) && false === strpos( $class_name, 'advertising_' ) ) {
			return;
		}

		// Next, prepare the file name from the class.
		$file_name = 'class-' . str_replace( '_', '-', $class_name ) . '.php';

		// Base path of the classes.
		$plugin_path = untrailingslashit( ADVERTISING_PLUGIN_DIR );

		// And an array of possible locations in order of importance.
		$locations = array(
			"$plugin_path/includes",
            "$plugin_path/includes/templates",
            "$plugin_path/includes/widgets",
			"$plugin_path/includes/libraries",
			"$plugin_path/includes/carts",
            "$plugin_path/includes/admin",
            "$plugin_path/includes/admin/settings",
            "$plugin_path/includes/admin/metaboxes",
			"$plugin_path/includes/abstracts",
		);

		foreach ( apply_filters( 'advertising_autoload_locations', $locations ) as $location ) {

			if ( file_exists( trailingslashit( $location ) . $file_name ) ) {
				include trailingslashit( $location ) . $file_name;
				break;
			}
		}

    }

    /**
     * Include required files.
     *
     * @access private
     * @since 1.0.0
     * @return void
     */
    private function includes() {
        global $adv_options;

        // Prepare plugin options.
        $adv_options = $this->get_options();

        // Load plugin functions.
        require_once ADVERTISING_PLUGIN_DIR . 'includes/core-functions.php';
        require_once ADVERTISING_PLUGIN_DIR . 'includes/ad-functions.php';
        require_once ADVERTISING_PLUGIN_DIR . 'includes/zone-functions.php';
        require_once ADVERTISING_PLUGIN_DIR . 'includes/advertiser-functions.php';
		require_once ADVERTISING_PLUGIN_DIR . 'includes/payment-functions.php';

        //Load main plugin classes
		require_once ADVERTISING_PLUGIN_DIR . 'includes/abstracts/abstract-adv-cart.php';
        require_once ADVERTISING_PLUGIN_DIR . 'includes/class-adv-ad.php';
        require_once ADVERTISING_PLUGIN_DIR . 'includes/class-adv-zone.php';
        require_once ADVERTISING_PLUGIN_DIR . 'includes/class-adv-dashboard.php';
        require_once ADVERTISING_PLUGIN_DIR . 'includes/class-adv-query.php';
        require_once ADVERTISING_PLUGIN_DIR . 'includes/class-adv-tracking.php';
        include_once ADVERTISING_PLUGIN_DIR . 'includes/admin/admin-settings.php';

		if ( $this->is_request( 'frontend' ) ) {
			require_once ADVERTISING_PLUGIN_DIR . 'includes/class-adv-frontend-assets.php';
		}

		if ( $this->is_request( 'admin' ) || $this->is_request( 'test' ) || $this->is_request( 'cli' ) ) {
            new Adv_Admin();

			include_once ADVERTISING_PLUGIN_DIR . 'includes/admin/admin-functions.php';

        }

        $this->query = new Adv_Query();
    }

    /**
     * Hook into actions and filters.
     * @since  1.0.0
     */
    private function init_hooks() {
		add_action( 'init', array( $this, 'init' ), 0 );
        add_action( 'init', array( $this, 'register_action' ) );
        add_action( 'init', array( $this, 'maybe_upgrade' ) );
        add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
		add_filter( 'adv_class_cart', array( $this, 'extend_cart' ) );
        add_filter( 'the_content', array( $this, 'inject_ads' ), 100 );
        add_action( 'wp_ajax_advertising_listings', array( $this, 'ajax_listings' ) );
        add_action( 'wp_ajax_advertising_image_upload', array( $this, 'ajax_image_upload' ) );
        add_action( 'wp_ajax_advertising_image_crop', array( $this, 'ajax_image_crop' ) );
        add_action( 'wp_footer', array( $this, 'wp_footer' ) );
        add_action( 'plugins_loaded', array( $this, 'load_widgets' ) );
        add_action( 'getpaid_rest_api_loaded', array( $this, 'rest_api_loaded' ) );
    }

    /**
     * Load widgets after plugins are loaded to allow overiding.
     */
    public function load_widgets() {
        require_once ADVERTISING_PLUGIN_DIR . 'vendor/autoload.php';
        require_once ADVERTISING_PLUGIN_DIR . 'includes/widgets/class-adv-widget-zone.php';
        require_once ADVERTISING_PLUGIN_DIR . 'includes/widgets/class-adv-widget-ad.php';
        require_once ADVERTISING_PLUGIN_DIR . 'includes/widgets/class-adv-widget-dashboard.php';
    }

    /**
     * Initialise plugin when WordPress Initialises.
     */
    public function init() {
        // Before init action.
        do_action( 'advertising_before_init' );

		// locations
	    $cart_class_name = apply_filters( 'adv_class_cart', 'Adv_Cart_Core' );
        $this->cart = new $cart_class_name();

        // Post types.
        $this->post_types = new Adv_Post_Types();

        // Widgets
        add_action( 'widgets_init', array( $this, 'register_widgets' ) );

        // If Pricing Manager is installed then init the payment class.
	    if ( class_exists( 'GeoDirectory' ) && defined( 'GEODIR_PRICING_VERSION' ) ) {
		    Adv_Pricing_Manager::init();
	    }

        // If Pricing Manager is installed then init the payment class.
	    if ( class_exists( 'GeoDirectory' ) ) {
		    Adv_GeoDirectory::init();
	    }

        // Init action.
        do_action( 'advertising_init' );
    }

	/**
	 * Loads the plugin language files
	 *
	 * @access public
	 * @since 1.0.0
	 * @return void
	 */
	public function load_textdomain() {
		$locale = determine_locale();

		/**
		 * Filter the plugin locale.
		 *
		 * @since 1.0.0
		 */
		$locale = apply_filters( 'plugin_locale', $locale, 'advertising' );

		unload_textdomain( 'advertising', true );
		load_textdomain( 'advertising', WP_LANG_DIR . '/advertising/advertising-' . $locale . '.mo' );
		load_plugin_textdomain( 'advertising', false, basename( dirname( ADVERTISING_PLUGIN_FILE ) ) . '/languages/' );
	}

    /**
     * Request type.
     *
     * @param  string $type admin, frontend, ajax, cron, test or CLI.
     * @return bool
     */
    private function is_request( $type ) {
        switch ( $type ) {
            case 'admin':
                return is_admin();
            case 'ajax':
                return wp_doing_ajax();
            case 'cli':
                return ( defined( 'WP_CLI' ) && WP_CLI );
            case 'cron':
                return wp_doing_cron();
            case 'frontend':
                return ( ! is_admin() || wp_doing_ajax() ) && ! wp_doing_cron();
            case 'test':
                return defined( 'ADV_TESTING_MODE' );
        }

        return null;
    }

    public function register_action() {
        if ( ! empty( $_REQUEST['adv_action'] ) ) {
            do_action( 'adv_' . sanitize_key( $_REQUEST['adv_action'] ) );
        }
    }

    /**
     * Installs the plugin and flushes rewrite rules.
     *
     * If not yet already.
     */
    public function maybe_upgrade() {
        $options = get_option( 'adv_settings', array() );

        if ( empty( $options['dashboard_page_id'] ) ) {

            $id = wp_insert_post(
                array(
                    'post_content' => '[ads_dashboard]',
                    'post_title'   => __( 'Advertising Dashboard', 'advertising' ),
                    'post_status'  => 'publish',
                    'post_type'    => 'page',
                )
            );

            if ( $id ) {

                $options['dashboard_page_id'] = $id;
                update_option( 'adv_settings', $options );
	            wp_schedule_single_event( time(), 'adv_flush_rewrite_rules' );

                if ( is_admin() ) {
                    wp_safe_redirect( admin_url( 'admin.php?page=advertising-settings' ) );
                    exit;
                }
            }
        }

    }

    public function get_options() {

        if ( ! isset( $this->options ) ) {
            $this->options = get_option( 'adv_settings', array() );
        }

        return $this->options;
    }

    public function get_endpoints_mask() {
        if ( 'page' === get_option( 'show_on_front' ) ) {
            $page_on_front = get_option( 'page_on_front' );
            $dashboard_page_id = adv_get_option( 'dashboard_page_id' );

            if ( in_array( $page_on_front, array( $dashboard_page_id ) ) ) {
                return EP_ROOT | EP_PAGES;
            }
        }

        return EP_PAGES;
    }

	public function extend_cart( $class ) {
		$cart = adv_get_cart();

		if ( 'invoicing' === $cart && defined( 'WPINV_VERSION' ) && version_compare( WPINV_VERSION, '1.0.0', '>=' ) ) {
			$class = 'Adv_Cart_Invoicing';
		} elseif ( 'woocommerce' === $cart && class_exists( 'WooCommerce' ) && version_compare( WC()->version, '3.0.0', '>=' ) ) {
			$class = 'Adv_Cart_WooCommerce';
		}

		return $class;
    }

    /**
	 * Register widgets
	 *
	 */
	public function register_widgets() {
		global $pagenow;

        // Maybe abort early.
		$block_widget_init_screens = function_exists( 'sd_pagenow_exclude' ) ? sd_pagenow_exclude() : array();

		if ( is_admin() && $pagenow && in_array( $pagenow, $block_widget_init_screens, true ) ) {
			return;
		}

		// Only load allowed widgets.
		$exclude = function_exists( 'sd_widget_exclude' ) ? sd_widget_exclude() : array();
		$widgets = apply_filters(
			'adv_widget_classes',
			array(
				'Adv_Widget_Zone',
				'Adv_Widget_Ad',
				'Adv_Widget_Dashboard',
			)
		);

		// For each widget...
		foreach ( $widgets as $widget ) {

			// Abort early if it is excluded for this page.
			if ( in_array( $widget, $exclude, true ) ) {
				continue;
			}

			// SD V1 used to extend the widget class. V2 does not, so we cannot call register widget on it.
			if ( is_subclass_of( $widget, 'WP_Widget' ) ) {
				register_widget( $widget );
			} else {
				new $widget();
			}
		}

	}

    /**
     * Checks if this is a preview request.
     *
     * @return bool
     */
    public function is_preview() {

        // phpcs:disable WordPress.Security.NonceVerification.Recommended

        // Widget preview.
        if ( ! empty( $_GET['legacy-widget-preview'] ) ) {
            return true;
        }

        // Divi preview.
        if ( isset( $_REQUEST['et_fb'] ) || isset( $_REQUEST['et_pb_preview'] ) ) {
            return true;
        }

        // Beaver builder.
        if ( isset( $_REQUEST['fl_builder'] ) ) {
            return true;
        }

        // Elementor builder.
        if ( isset( $_REQUEST['elementor-preview'] ) || ( is_admin() && isset( $_REQUEST['action'] ) && 'elementor' === $_REQUEST['action'] ) || ( isset( $_REQUEST['action'] ) && 'elementor_ajax' === $_REQUEST['action'] ) ) {
            return true;
        }

        // Siteorigin preview.
        if ( ! empty( $_REQUEST['siteorigin_panels_live_editor'] ) ) {
            return true;
        }

        // Cornerstone preview.
        if ( ! empty( $_REQUEST['cornerstone_preview'] ) || 'cornerstone-endpoint' === basename( $_SERVER['REQUEST_URI'] ) ) {
            return true;
        }

        // Fusion builder preview.
        if ( ! empty( $_REQUEST['fb-edit'] ) || ! empty( $_REQUEST['fusion_load_nonce'] ) ) {
            return true;
        }

        // Oxygen preview.
        if ( ! empty( $_REQUEST['ct_builder'] ) || ( ! empty( $_REQUEST['action'] ) && ( 'oxy_render_' === substr( $_REQUEST['action'], 0, 11 ) || 'ct_render_' === substr( $_REQUEST['action'], 0, 10 ) ) ) ) {
            return true;
        }

        // Ninja forms preview.
        if ( isset( $_GET['nf_preview_form'] ) || isset( $_GET['nf_iframe'] ) ) {
            return true;
        }

        // Customizer preview.
        if ( is_customize_preview() ) {
            return true;
        }

        // phpcs:enable WordPress.Security.NonceVerification.Recommended
        return false;

    }

    /**
	 * Injects ads into post content
	 */
	public function inject_ads( $content ) {

		// Check if we're inside the main loop in a single post page.
		if ( ! is_singular() || ! in_the_loop() || ! is_main_query() || is_admin() || $this->is_preview() ) {
			return $content;
		}

		// Do not run this twice.
		remove_filter( 'the_content', array( $this, 'inject_ads' ), 100 );

		// Loop through all active zones.
		foreach ( adv_get_zones( array( 'meta_query' => array() ) ) as $zone_id ) {

            $to_inject = adv_zone_get_meta( $zone_id, 'inject' );

            $to_inject = is_array( $to_inject ) ? $to_inject : array();

            if ( ! in_array( 'before', $to_inject, true ) && ! in_array( 'after', $to_inject, true ) ) {
                continue;
            }

			// Fetch the zone...
			$zone = adv_get_zone( $zone_id );

			// and abort if the zone can not be displayed on this particular page
			if ( ! $zone->can_display_zone() ) {
				continue;
			}

			$html = $zone->get_html();

			// Inject before.
			if ( in_array( 'before', $to_inject, true ) ) {
				$content = $html . $content;
			}

			// Inject after.
			if ( in_array( 'after', $to_inject, true ) ) {
				$content .= $html;
			}
		}

		return $content;

	}

    /**
     * Retrieves the current user's listings.
     */
    public static function ajax_listings() {
        // Verify nonce.
        check_ajax_referer( 'adv-ajax-nonce' );

        // Logged out users have no listings.
        if ( ! get_current_user_id() || ! function_exists( 'geodir_get_posttypes' ) ) {
            wp_send_json_success( array() );
        }

        // We need a search term.
        if ( empty( $_REQUEST['search'] ) ) {
            wp_send_json_success( array() );
        }

        // Retrieve items.
        $item_args = array(
            'post_type'      => geodir_get_posttypes(),
            'orderby'        => 'title',
            'order'          => 'ASC',
            'posts_per_page' => -1,
            'post_status'    => Adv_GeoDirectory::get_post_statuses(),
            'post_parent'    => 0, 
            's'              => sanitize_text_field( urldecode( $_REQUEST['search'] ) ),
        );

        if ( empty( $_REQUEST['isAdmin'] ) ) {
            $item_args['author'] = get_current_user_id();
        }

        if ( ! empty( $_REQUEST['adv_zone'] ) && ( $zone_id = absint( $_REQUEST['adv_zone'] ) ) ) {
            $gd_post_types = adv_zone_get_meta( $zone_id, 'gd_post_types', true );

            if ( ! empty( $gd_post_types ) && is_array( $gd_post_types ) ) {
                $item_args['post_type'] = $gd_post_types;
            }
        }

        if ( ! empty( $_REQUEST['ignore'] ) ) {
            $item_args['exclude'] = wp_parse_id_list( sanitize_text_field( $_REQUEST['ignore'] ) );
        }

        $listings = get_posts( apply_filters( 'adv_ajax_listings_query_args', $item_args ) );
        $data     = array();
        $is_admin = (int) current_user_can( 'manage_options' );

        foreach ( $listings as $listing ) {
            $listing_status = get_post_status( (int) $listing->ID );
            $suffix = '';

            // Display ID to admins.
            if ( $is_admin ) {
                $suffix .= ' #' . (int) $listing->ID;
            }

            // Display post status for non published ad.
            if ( $listing_status != 'publish' ) {
                $suffix .= ' - ' . geodir_get_post_status_name( $listing_status );
            }

            $data[] = array(
                'id'        => $listing->ID,
                'text'      => Adv_GeoDirectory::get_post_title( $listing ) . $suffix,
            );
        }

        wp_send_json_success( $data );
    }

    /**
	 * Handles avatar and banner file upload.
	 *
	 * @since       1.0.0
	 * @package     userswp
	 * @return      void
	 */
	public function ajax_image_upload() {

        check_ajax_referer( 'adv-nonce', 'nonce' );

        $arr_img_ext = array( 'image/png', 'image/jpeg', 'image/jpg', 'image/gif' );

        if ( ! empty( $_FILES['file'] ) && in_array( $_FILES['file']['type'], $arr_img_ext ) ) {
            $file_name = explode( '.', $_FILES['file']['name'] );

            $uploaded = wp_upload_bits(
                uniqid( 'adv_' ) . '.' . array_pop( $file_name ),
                null,
                file_get_contents( $_FILES['file']['tmp_name'] )
            );
        }

        if ( empty( $uploaded ) ) {
            wp_send_json_error( __( 'Missing or unsupported file type.', 'advertising' ) );
        }

        if ( ! empty( $uploaded['error'] ) ) {
            wp_send_json_error( $uploaded['error'] );
        }

        wp_send_json_success(
            array(
                'image_size' => getimagesize( $uploaded['file'] ),
                'image_url'  => $uploaded['url'],
            )
        );

	}

    public function ajax_image_crop() {

        check_ajax_referer( 'adv-nonce', 'nonce' );

        if ( empty( $_POST['image_url'] ) ) {
            wp_send_json_error( __( 'Image Missing', 'advertising' ) );
        }

        $image_url   = esc_url_raw( $_POST['image_url'] );
        $image_ext   = wp_check_filetype( $image_url );
        $arr_img_ext = array( 'image/png', 'image/jpeg', 'image/jpg', 'image/gif' );

        if ( empty( $image_ext['type'] ) || ! in_array( $image_ext['type'], $arr_img_ext ) ) {
            wp_send_json_error( __( 'Unsupported File Type', 'advertising' ) );
        }

        $upload_dir = wp_upload_dir( null, false );
        if ( empty( $upload_dir['baseurl'] ) ) {
            wp_send_json_error( __( 'Error retrieving upload dir', 'advertising' ) );
        }

        if ( 0 === strpos( 'https://', $image_url ) && 0 !== strpos( 'https://', $upload_dir['baseurl'] ) ) {
            str_replace( 'http://', 'https://', $upload_dir['baseurl'] );
        }

        if ( 0 === strpos( $upload_dir['baseurl'], $image_url ) ) {
            $image_path = str_replace( $upload_dir['baseurl'], $upload_dir['basedir'], $image_url );
        }

        if ( empty( $image_path ) || ! file_exists( $image_path ) ) {

            $uploaded = wp_upload_bits(
                uniqid( 'adv_' ) . '.' . $image_ext['ext'],
                null,
                file_get_contents( $image_url )
            );

            if ( empty( $uploaded ) ) {
                wp_send_json_error( __( 'Error uploading image.', 'advertising' ) );
            }

            if ( ! empty( $uploaded['error'] ) ) {
                wp_send_json_error( $uploaded['error'] );
            }

            $image_path = $uploaded['file'];
            $image_url = $uploaded['url'];
        }

        $editor = wp_get_image_editor( $image_path );

        if ( is_wp_error( $editor ) ) {
            wp_send_json_error( $editor->get_error_message() );
        }

        $image_x = (int) $_POST['image_x'];
        $image_y = (int) $_POST['image_y'];
        $image_w = (int) $_POST['image_w'];
        $image_h = (int) $_POST['image_h'];
        $result  = $editor->crop( $image_x, $image_y, $image_w, $image_h );

        if ( is_wp_error( $result ) ) {
            wp_send_json_error( $result->get_error_message() );
        }

        $saved = $editor->save( $editor->generate_filename( 'cropped' ) );

        if ( is_wp_error( $saved ) ) {
            wp_send_json_error( $saved->get_error_message() );
        }

        wp_send_json_success(
            str_replace(
                $upload_dir['basedir'],
                $upload_dir['baseurl'],
                $saved['path']
            )
        );

	}

	public function wp_footer() {
		if ( is_user_logged_in() && apply_filters( 'adv_load_footer_template', false ) ) {
            adv_get_template( 'crop-ad-image.php' );
        }
	}

    public function rest_api_loaded() {
        include_once plugin_dir_path( __FILE__ ) . 'rest-ads.php';
        include_once plugin_dir_path( __FILE__ ) . 'rest-zones.php';
        new GetPaid_REST_Ads_Controller();
        new GetPaid_REST_Ad_Zones_Controller();
    }

}