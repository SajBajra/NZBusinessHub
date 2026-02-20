<?php
/**
 * Compare plugin main class.
 *
 * @package    GeoDir_Compare
 * @since      2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * GeoDir_Compare class.
 */
final class GeoDir_Compare {

	/**
	 * The single instance of the class.
	 *
	 * @var GeoDir_Compare
	 * @since 1.0.0
	 */
	private static $instance = null;

	/**
	 * The current database version
	 *
	 * @since 1.0.0
	 */
	public $db_version = '1.0.0';

	/**
	 * Minimum PHP version required.
	 *
	 * @since 2.0
	 */
	public $min_php_version = '5.6';

	/**
	 * Main GeoDir_Compare Instance.
	 *
	 * Ensures only one instance of GeoDir_Compare is loaded or can be loaded.
	 *
	 * @since 1.0.0
	 * @static
	 * @see GeoDir_Compare()
	 * @return GeoDir_Compare - Main instance.
	 */
	public static function instance() {
		if ( ! isset( self::$instance ) && ! ( self::$instance instanceof GeoDir_Compare ) ) {
			self::$instance = new GeoDir_Compare;
			self::$instance->setup_constants();

			add_action( 'plugins_loaded', array( self::$instance, 'load_textdomain' ) );

			if ( version_compare( PHP_VERSION, self::$instance->min_php_version, '<' ) ) {
				add_action( 'admin_notices', array( self::$instance, 'php_version_notice' ) );

				return self::$instance;
			}

			self::$instance->includes();
			self::$instance->init_hooks();

			do_action( 'geodir_compare_loaded' );
		}

		return self::$instance;
	}

	/**
	 * Setup plugin constants.
	 *
	 * @since 2.0.0
	 *
	 * @return void
	 */
	private function setup_constants() {
		if ( $this->is_request( 'test' ) ) {
			$plugin_path = dirname( GEODIR_COMPARE_PLUGIN_FILE );
		} else {
			$plugin_path = plugin_dir_path( GEODIR_COMPARE_PLUGIN_FILE );
		}

		$this->define( 'GEODIR_COMPARE_PLUGIN_DIR', $plugin_path );
		$this->define( 'GEODIR_COMPARE_PLUGIN_URL', untrailingslashit( plugins_url( '/', GEODIR_COMPARE_PLUGIN_FILE ) ) );
		$this->define( 'GEODIR_COMPARE_PLUGIN_BASENAME', plugin_basename( GEODIR_COMPARE_PLUGIN_FILE ) );

		// Database tables
		$this->define( 'GEODIR_COMPARE_DB_VERSION', $this->db_version );
	}

	/**
	 * Load plugin textdomain.
	 *
	 * @since 2.0
	 */
	public function load_textdomain() {
		// Determines the current locale.
		$locale = determine_locale();

		$locale = apply_filters( 'plugin_locale', $locale, 'geodir-compare' );

		unload_textdomain( 'geodir-compare', true );
		load_textdomain( 'geodir-compare', WP_LANG_DIR . '/geodir-compare/geodir-compare-' . $locale . '.mo' );
		load_plugin_textdomain( 'geodir-compare', false, basename( dirname( GEODIR_COMPARE_PLUGIN_FILE ) ) . '/languages/' );
	}

	/**
	 * Show minimum PHP version warning.
	 *
	 * @static
	 * @access private
	 * @since 2.0.0
	 * @return void
	 */
	public static function php_version_notice() {
		echo '<div class="error"><p>' . wp_sprintf( __( 'Your version of PHP is below the minimum version of PHP required by GeoDirectory Compare Listings. Please contact your host and request that your version be upgraded to %s or later.', 'geodir-compare' ), $this->min_php_version ) . '</p></div>';
	}

	/**
	 * Include required files.
	 *
	 * @access private
	 * @since 2.0.0
	 * @return void
	 */
	private function includes() {
		require_once( GEODIR_COMPARE_PLUGIN_DIR . 'includes/admin/admin.php' );
		require_once( GEODIR_COMPARE_PLUGIN_DIR . 'includes/template-functions.php' );
		require_once( GEODIR_COMPARE_PLUGIN_DIR . 'includes/widgets/class-geodir-widget-compare-button.php' );
		require_once( GEODIR_COMPARE_PLUGIN_DIR . 'includes/widgets/class-geodir-widget-compare-list.php' );
		require_once( GEODIR_COMPARE_PLUGIN_DIR . 'includes/class-geodir-compare-ajax.php' );
		require_once( GEODIR_COMPARE_PLUGIN_DIR . 'includes/functions.php' );

		if ( $this->is_request( 'admin' ) || $this->is_request( 'test' ) || $this->is_request( 'cli' ) ) {
			new GeoDir_Compare_Admin();

			require_once( GEODIR_COMPARE_PLUGIN_DIR . 'includes/admin/class-geodir-compare-admin-install.php' );

			Geodir_Compare_Admin_Install::init();
		}
	}

	/**
	 * Hook into actions and filters.
	 *
	 * @since  2.0
	 */
	private function init_hooks() {
		do_action( 'before_geodir_compare_init' );

		add_action( 'geodir_get_widgets', array( $this, 'register_widgets' ), 11, 1 );

		// Load css and js
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		
		// Add CF location
		add_filter( 'geodir_show_in_locations', array( $this, 'add_fields_location'), 10,3 );

		// Display post state
		add_filter('display_post_states',array( $this, 'set_page_labels' ),10,2);

		// Add menu endpoints
		add_filter('geodirectory_custom_nav_menu_items',array( $this, 'add_nav_menu_items' ));

		// Add unique class to the comparisons page
		add_filter( 'body_class', array( $this, 'add_body_class' ) );

		// Init the ajax handler
		new GeoDir_Compare_Ajax();

		/**
		 * Fires after GeoDir_Compare initializes
		 *
		 * @since 1.0.0
		 */
		do_action( 'geodir_compare_init' );
	}

	/**
	 * Define constant if not already set.
	 *
	 * @param  string $name
	 * @param  string|bool $value
	 */
	private function define( $name, $value ) {
		if ( ! defined( $name ) ) {
			define( $name, $value );
		}
	}

	/**
	 * Register widgets.
	 *
	 * @since 2.0.0.0
	 *
	 * @param array $widgets The list of available widgets.
	 * @return array Available GD widgets.
	 */
	public function register_widgets( $widgets ) {
		$widgets[] = 'GeoDir_Widget_Compare_Button'; // Compare button widget
		$widgets[] = 'GeoDir_Widget_Compare_List'; // Comparison table

		return $widgets;
	}

	/**
	 * Registers a new custom fields location
	 *
	 * @since 1.0.0
	 */
	public function add_fields_location( $show_in_locations, $field_info, $field_type ) {
		$show_in_locations['[compare]'] = __( "Comparison Page", 'geodir-compare' );

		return $show_in_locations;
	}

	/**
	 * Sets pages labels
	 *
	 * @since 1.0.0
	 */
	public function set_page_labels(  $post_states, $post ) {
		if ( $post->ID == geodir_get_option( 'geodir_compare_listings_page' ) ) {
			$post_states['geodir_compare_listings_page'] = __( 'GD Comparison Page', 'geodir-compare' ) . geodir_help_tip( __( 'This is where users can compare several listings side by side.', 'geodir-compare' ) );
		}

		return $post_states;
	}

	/**
	 * Adds new nav menu items
	 *
	 * @since 1.0.0
	 */
	public function add_nav_menu_items( $items ) {
		// Add the comparison menu item
		$gd_comparison_page_id = geodir_get_option( 'geodir_compare_listings_page' );

		if ( $gd_comparison_page_id ) {
			$item = new stdClass();
			$item->object_id 			= $gd_comparison_page_id;
			$item->db_id 				= 0;
			$item->object 				=  'page';
			$item->menu_item_parent 	= 0;
			$item->type 				= 'post_type';
			$item->title 				= __( 'Compare Listings', 'geodir-compare' );
			$item->url 					= get_page_link( $gd_comparison_page_id );
			$item->target 				= '';
			$item->attr_title 			= '';
			$item->classes 				= array('gd-menu-item');
			$item->xfn 					= '';

			$items['pages'][] = $item;
		}

		return $items;
	}

	/**
	 * Adds a new class to the body items
	 *
	 * @since 1.0.0
	 */
	public function add_body_class( $classes ) {
		global $post;

		// Add the comparison menu item
		if ( ! empty( $post ) && absint( $post->ID ) == absint( geodir_get_option( 'geodir_compare_listings_page' ) ) ) {
			$classes[] = 'geodir-compare-page';
		}

		return $classes;
	}

	/**
	 * Register and enqueue styles and scripts.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_scripts() {
		$assets_url = plugin_dir_url( GEODIR_COMPARE_PLUGIN_FILE ) . 'includes/assets/';
		$assets_dir = plugin_dir_path( GEODIR_COMPARE_PLUGIN_FILE ) . 'includes/assets/';
		$design_style = geodir_design_style();

		// Javascript
		$vars                   = array(
			'items_full'        => __( 'Your comparision list is full. Please remove one item first.', 'geodir-compare' ),
			'compare'           => __( 'Compare', 'geodir-compare' ),
			'ajax_error'        => __( 'There was an error while processing the request.', 'geodir-compare' ),
			'ajax_url'          => admin_url( 'admin-ajax.php' ),
			'cookie_domain'     => COOKIE_DOMAIN,
			'cookie_path'       => COOKIEPATH,
			'cookie_time'       => DAY_IN_SECONDS,
			'comparePage'       => esc_url( add_query_arg( 'compareids', '0', get_the_permalink( (int) geodir_get_option('geodir_compare_listings_page') ) ) )
		);

		if ( ! $design_style ) {
			$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
			wp_register_script( 'geodir-compare', $assets_url . 'scripts' . $suffix . '.js', array( 'jquery', 'geodir_lity' ), filemtime( $assets_dir . 'scripts' . $suffix . '.js' ), true );
			wp_enqueue_script(  'geodir-compare' );
		}

		$script = $design_style ? 'geodir' : 'geodir-compare';
		wp_localize_script( $script , 'GD_Compare', $vars );

		// CSS
		if ( ! $design_style ) {
			wp_enqueue_style( 'geodir-compare', $assets_url . 'styles.css', array(), filemtime( $assets_dir . 'styles.css' ) );
		}
	}

	/**
	 * Request type.
	 *
	 * @param  string $type admin, frontend, ajax, cron, test or CLI.
	 * @return bool
	 */
	private function is_request( $type ) {
		switch ( $type ) {
			case 'admin' :
				return is_admin();
				break;
			case 'ajax' :
				return wp_doing_ajax();
				break;
			case 'cli' :
				return ( defined( 'WP_CLI' ) && WP_CLI );
				break;
			case 'cron' :
				return wp_doing_cron();
				break;
			case 'frontend' :
				return ( ! is_admin() || wp_doing_ajax() ) && ! wp_doing_cron();
				break;
			case 'test' :
				return defined( 'GD_TESTING_MODE' );
				break;
		}

		return null;
	}
}