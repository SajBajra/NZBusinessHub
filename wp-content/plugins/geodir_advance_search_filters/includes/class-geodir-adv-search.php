<?php
/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the dashboard.
 *
 * @link       https://wpgeodirectory.com
 * @since      2.0.0
 *
 * @package    GeoDir_Advance_Search_Filters
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, dashboard-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      2.0.0
 * @package    GeoDir_Adv_Search
 * @author     AyeCode Ltd
 */
final class GeoDir_Adv_Search {

	/**
	 * GeoDirectory Advance Search Filters instance.
	 *
	 * @access private
	 * @since  2.0.0
	 */
	private static $instance = null;

	/**
	 * Query instance.
	 *
	 * @var GeoDir_Adv_Search_Query
	 */
	public $query = null;

	/**
	 * Main GeoDir_Adv_Search Instance.
	 *
	 * Ensures only one instance of GeoDirectory Advance Search Filters is loaded or can be loaded.
	 *
	 * @since 2.0.0
	 * @static
	 * @see GeoDir()
	 * @return GeoDir_Adv_Search - Main instance.
	 */
	public static function instance() {
		if ( ! isset( self::$instance ) && ! ( self::$instance instanceof GeoDir_Adv_Search ) ) {
			self::$instance = new GeoDir_Adv_Search;
			self::$instance->setup_constants();

			add_action( 'plugins_loaded', array( self::$instance, 'load_textdomain' ) );

			if ( ! class_exists( 'GeoDirectory' ) ) {
				add_action( 'admin_notices', array( self::$instance, 'geodirectory_notice' ) );

				return self::$instance;
			}

			if ( version_compare( PHP_VERSION, '5.3', '<' ) ) {
				add_action( 'admin_notices', array( self::$instance, 'php_version_notice' ) );

				return self::$instance;
			}

			self::$instance->includes();
			self::$instance->init_hooks();

			do_action( 'geodir_advance_search_filters_loaded' );
		}

		return self::$instance;
	}

	/**
	 * Setup plugin constants.
	 *
	 * @access private
	 * @since 2.0.0
	 * @return void
	 */
	private function setup_constants() {
		global $wpdb;

		if ( $this->is_request( 'test' ) ) {
			$plugin_path = dirname( GEODIR_ADV_SEARCH_PLUGIN_FILE );
		} else {
			$plugin_path = plugin_dir_path( GEODIR_ADV_SEARCH_PLUGIN_FILE );
		}

		$this->define( 'GEODIR_ADV_SEARCH_PLUGIN_DIR', $plugin_path );
		$this->define( 'GEODIR_ADV_SEARCH_PLUGIN_URL', untrailingslashit( plugins_url( '/', GEODIR_ADV_SEARCH_PLUGIN_FILE ) ) );
		$this->define( 'GEODIR_ADV_SEARCH_PLUGIN_BASENAME', plugin_basename( GEODIR_ADV_SEARCH_PLUGIN_FILE ) );

		// Database tables
		$this->define( 'GEODIR_ADVANCE_SEARCH_TABLE', $wpdb->prefix . 'geodir_custom_advance_search_fields' );
		$this->define( 'GEODIR_BUSINESS_HOURS_TABLE', $wpdb->prefix . 'geodir_business_hours' ); // business hours table
	}

	/**
	 * Loads the plugin language files
	 *
	 * @access public
	 * @since 2.0.0
	 * @return void
	 */
	public function load_textdomain() {
		// Determines the current locale.
		$locale = determine_locale();

		/**
		 * Filter the plugin locale.
		 *
		 * @since   1.0.0
		 * @package GeoDir_Advance_Search_Filters
		 */
		$locale = apply_filters( 'plugin_locale', $locale, 'geodiradvancesearch' );

		unload_textdomain( 'geodiradvancesearch', true );
		load_textdomain( 'geodiradvancesearch', WP_LANG_DIR . '/geodiradvancesearch/geodiradvancesearch-' . $locale . '.mo' );
		load_plugin_textdomain( 'geodiradvancesearch', false, basename( dirname( GEODIR_ADV_SEARCH_PLUGIN_FILE ) ) . '/languages/' );
	}

	/**
	 * Check plugin compatibility and show warning.
	 *
	 * @static
	 * @access private
	 * @since 2.0.0
	 * @return void
	 */
	public static function geodirectory_notice() {
		echo '<div class="error"><p>' . __( 'GeoDirectory plugin is required for the GeoDirectory Advance Search Filters plugin to work properly.', 'geodiradvancesearch' ) . '</p></div>';
	}

	/**
	 * Show a warning to sites running PHP < 5.3
	 *
	 * @static
	 * @access private
	 * @since 2.0.0
	 * @return void
	 */
	public static function php_version_notice() {
		echo '<div class="error"><p>' . __( 'Your version of PHP is below the minimum version of PHP required by GeoDirectory Advance Search Filters. Please contact your host and request that your version be upgraded to 5.3 or later.', 'geodiradvancesearch' ) . '</p></div>';
	}

	/**
	 * Include required files.
	 *
	 * @access private
	 * @since 2.0.0
	 * @return void
	 */
	private function includes() {
		/**
		 * Class autoloader.
		 */
		include_once( GEODIR_ADV_SEARCH_PLUGIN_DIR . 'includes/class-geodir-adv-search-autoloader.php' );

		GeoDir_Adv_Search_AJAX::init();
		GeoDir_Adv_Search_Business_Hours::init(); // Business Hours
		GeoDir_Adv_Search_Fields::init();

		require_once( GEODIR_ADV_SEARCH_PLUGIN_DIR . 'includes/functions.php' );
		require_once( GEODIR_ADV_SEARCH_PLUGIN_DIR . 'includes/template-functions.php' );

		if ( $this->is_request( 'admin' ) || $this->is_request( 'test' ) || $this->is_request( 'cli' ) ) {
			new GeoDir_Adv_Search_Admin();

			require_once( GEODIR_ADV_SEARCH_PLUGIN_DIR . 'includes/admin/admin-functions.php' );

			GeoDir_Adv_Search_Admin_Install::init();

			require_once( GEODIR_ADV_SEARCH_PLUGIN_DIR . 'upgrade.php' );
		}

		$this->query = new GeoDir_Adv_Search_Query();
	}

	/**
	 * Hook into actions and filters.
	 * @since  2.0.0
	 */
	private function init_hooks() {
		add_action( 'init', array( $this, 'init' ), 0 );
		//add_filter( 'wp_super_duper_options_gd_search', 'geodir_search_widget_options' );
		add_filter( 'wp_super_duper_arguments', 'geodir_search_widget_options', 10, 3 );
		add_filter( 'geodir_register_block_pattern_search_attrs', 'geodir_search_block_pattern_attrs', 10, 1 );
		add_filter( 'geodir_search_form_template_params', 'geodir_search_form_template_params', 10, 3 );
		add_filter( 'geodir_params', array( $this, 'localize_core_params' ), 10, 1 );
		add_action( 'super_duper_before_render_bricks_element', array( $this, 'super_duper_bricks_set_attibutes' ), 10, 3 );

		if ( $this->is_request( 'frontend' ) ) {
			add_action( 'wp_enqueue_scripts', array( $this, 'add_styles' ), 10 );
			add_action( 'wp_enqueue_scripts', array( $this, 'add_scripts' ), 10 );
			
			// aui
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_aui' ), 10 );
			
			add_filter( 'wp_super_duper_div_classname_gd_search', 'geodir_search_widget_add_class', 10, 3 );
			add_filter( 'wp_super_duper_div_attrs_gd_search', 'geodir_search_widget_add_attr', 10, 3 );
			add_filter( 'wp_super_duper_before_widget_gd_search', 'geodir_search_before_widget_content', 10, 4 );
			add_filter( 'wp_footer' , 'geodir_search_form_add_script' , 10 );

			add_filter( 'body_class', 'geodir_search_body_class' ); // let's add a class to the body so we can style the new addition to the search
			
			if ( geodir_get_option( 'advs_search_display_searched_params' ) ) {
				add_action( 'geodir_extra_loop_actions', 'geodir_search_show_searched_params', 9999, 1 );
			}

			// AJAX Search
			add_action( 'geodir_search_handle_ajax_request', 'geodir_search_handle_ajax_search_request' );
			add_filter( 'geodir_search_ajax_loop', 'geodir_search_ajax_loop', 10, 1 );
			add_filter( 'geodir_search_ajax_pagination', 'geodir_search_ajax_pagination', 10, 2 );
			add_filter( 'wp_super_duper_widget_output', 'geodir_search_set_loop_params', 20, 4 );
			add_filter( 'geodir_map_params', 'geodir_search_set_map_params', 9999, 2 );
			add_filter( 'wp_head', array( $this, 'enqueue_inline_style' ), 10 );
			//add_filter( 'geodir_search_default_search_button_text', 'geodir_search_ajax_search_button_text', 20, 1 );
			add_filter( 'geodir_search_ajax_search_data', 'geodir_search_ajax_search_data', 10, 1 );
			add_filter( 'geodir_get_option_marker_cluster_type', 'geodir_search_marker_cluster_type', 20, 3 );
		}
	}

	/**
	 * Initialise plugin when WordPress Initialises.
	 */
	public function init() {
		// Before init action.
		do_action( 'geodir_adv_search_before_init' );

		// Init action.
		do_action( 'geodir_adv_search_init' );
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

	/**
	 * Enqueue styles.
	 */
	public function add_styles() {
		$design_style = geodir_design_style();

		if ( ! $design_style ) {
			// Register stypes
			wp_register_style( 'geodir-adv-search', GEODIR_ADV_SEARCH_PLUGIN_URL . '/assets/css/style.css', array(), GEODIR_ADV_SEARCH_VERSION );

			wp_enqueue_style( 'geodir-adv-search' );
		}
	}

	/**
	 * Enqueue scripts.
	 */
	public function add_scripts() {
		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		$design_style = geodir_design_style();

		if ( ! $design_style ) {
			// Register scripts
			wp_register_script( 'geodir-adv-search', GEODIR_ADV_SEARCH_PLUGIN_URL . '/assets/js/script' . $suffix . '.js', array(
				'jquery',
				'geodir',
				'geodir-jquery-ui-timepicker'
			), GEODIR_ADV_SEARCH_VERSION );

			wp_enqueue_script( 'geodir-adv-search' );
		}

		$script = $design_style ? 'geodir' : 'geodir-adv-search';

		wp_localize_script($script , 'geodir_search_params', geodir_adv_search_params() );
	}

	public function enqueue_aui() {
		global $aui_bs5;

		$script = 'geodir';

		if ( $aui_bs5 && geodir_design_style() ) {
			// Prevent JS error when BootStrap JS loaded before GeoDirectory core JS.
			if ( wp_script_is( 'geodir', 'enqueued' ) && wp_script_is( 'bootstrap-js-bundle', 'enqueued' ) ) {
				$script = 'bootstrap-js-bundle';
			}
		}

		wp_add_inline_script( $script, $this->add_scripts_aui() );
	}


	public function add_scripts_aui() {
		return geodir_adv_search_inline_script();
	}

	public function enqueue_inline_style() {
		geodir_adv_search_inline_style();
	}

	public static function localize_core_params( $params = array() ) {
		$params['hasAjaxSearch'] = (bool) geodir_search_has_ajax_search( true );

		return $params;
	}

	public static function super_duper_bricks_set_attibutes( $settings, $widget, $element ) {
		if ( ! empty( $widget ) && ! empty( $widget->id_base ) && $widget->id_base == 'gd_search' ) {
			$mode = ! empty( $settings['customize_filters'] ) ? $settings['customize_filters'] : 'default';

			if ( ! empty( $settings['show'] ) && ( $settings['show'] == 'main' || $settings['show'] == 'advanced' ) ) {
				$mode = 'always';
			}

			$calss = 'geodir-search-container geodir-advance-search-' . sanitize_html_class( $mode );

			if ( $mode == 'searched' && geodir_is_page( 'search' ) ) {
				$mode = 'search';
			}

			$element->set_attribute( '_root', 'class', $calss );
			$element->set_attribute( '_root', 'data-show-adv', esc_attr( $mode ) );
		}
	}
}
