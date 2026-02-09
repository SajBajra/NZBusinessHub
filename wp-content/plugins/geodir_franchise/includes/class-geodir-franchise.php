<?php
/**
 * Franchise Manager plugin main class.
 *
 * @package    Geodir_Franchise
 * @since      2.0.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * GeoDir_Franchise class.
 */
final class GeoDir_Franchise {

	/**
	 * The single instance of the class.
	 *
	 * @since 2.0.0
	 */
	private static $instance = null;

	/**
	 * Query instance.
	 *
	 * @var GeoDir_Franchise_Query
	 */
	public $query = null;

	/**
	 * Franchise Manager Main Instance.
	 *
	 * Ensures only one instance of Franchise Manager is loaded or can be loaded.
	 *
	 * @since 2.0.0
	 * @static
	 * @return Franchise Manager - Main instance.
	 */
	public static function instance() {
		if ( ! isset( self::$instance ) && ! ( self::$instance instanceof GeoDir_Franchise ) ) {
			self::$instance = new GeoDir_Franchise;
			self::$instance->setup_constants();

			add_action( 'plugins_loaded', array( self::$instance, 'load_textdomain' ) );

			if ( ! class_exists( 'GeoDirectory' ) ) {
				add_action( 'admin_notices', array( self::$instance, 'geodirectory_notice' ) );

				return self::$instance;
			}

			self::$instance->includes();
			self::$instance->init_hooks();

			do_action( 'geodir_franchise_loaded' );
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
		global $plugin_prefix;

		if ( $this->is_request( 'test' ) ) {
			$plugin_path = dirname( GEODIR_FRANCHISE_PLUGIN_FILE );
		} else {
			$plugin_path = plugin_dir_path( GEODIR_FRANCHISE_PLUGIN_FILE );
		}

		$this->define( 'GEODIR_FRANCHISE_PLUGIN_DIR', $plugin_path );
		$this->define( 'GEODIR_FRANCHISE_PLUGIN_URL', untrailingslashit( plugins_url( '/', GEODIR_FRANCHISE_PLUGIN_FILE ) ) );
		$this->define( 'GEODIR_FRANCHISE_PLUGIN_BASENAME', plugin_basename( GEODIR_FRANCHISE_PLUGIN_FILE ) );
	}

	/**
	 * Include required files.
	 *
	 * @access private
	 * @since 2.0.0
	 * @return void
	 */
	private function includes() {
	   global $wp_version;

	   /**
		 * Class autoloader.
		 */
		include_once( GEODIR_FRANCHISE_PLUGIN_DIR . 'includes/class-geodir-franchise-autoloader.php' );

		GeoDir_Franchise_AJAX::init();
		GeoDir_Franchise_Email::init();
		GeoDir_Franchise_Fields::init();
		GeoDir_Franchise_Post::init();
		GeoDir_Franchise_Post_Type::init();
		
		// If Pricing Manager is installed then fire the payment class
		if ( defined( 'GEODIR_PRICING_VERSION' ) ) {
			GeoDir_Franchise_Pricing::init();
		}

		require_once( GEODIR_FRANCHISE_PLUGIN_DIR . 'includes/core-functions.php' );
		require_once( GEODIR_FRANCHISE_PLUGIN_DIR . 'includes/deprecated-functions.php' );
		require_once( GEODIR_FRANCHISE_PLUGIN_DIR . 'includes/template-functions.php' );

		if ( $this->is_request( 'admin' ) || $this->is_request( 'test' ) || $this->is_request( 'cli' ) ) {
			new GeoDir_Franchise_Admin();

			include_once( GEODIR_FRANCHISE_PLUGIN_DIR . 'includes/admin/admin-functions.php' );

			GeoDir_Franchise_Admin_Install::init();       
		}

		$this->query = new GeoDir_Franchise_Query();
	}

	/**
	 * Hook into actions and filters.
	 * @since  2.0.0
	 */
	private function init_hooks() {
		if ( $this->is_request( 'frontend' ) ) {
			add_action( 'wp_enqueue_scripts', array( $this, 'load_scripts' ) );
		}

		add_action( 'init', array( $this, 'init' ), 0 );

		add_filter( 'geodir_widget_after_detail_user_actions', 'geodir_franchise_detail_author_actions', 11 );
	}

	/**
	 * Initialise plugin when WordPress Initialises.
	 */
	public function init() {
		// Before init action.
		do_action( 'geodir_franchise_before_init' );

		// Init action.
		do_action( 'geodir_franchise_init' );
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

		$locale = apply_filters( 'plugin_locale', $locale, 'geodir-franchise' );

		unload_textdomain( 'geodir-franchise', true );
		load_textdomain( 'geodir-franchise', WP_LANG_DIR . '/geodir-franchise/geodir-franchise-' . $locale . '.mo' );
		load_plugin_textdomain( 'geodir-franchise', false, basename( dirname( GEODIR_FRANCHISE_PLUGIN_FILE ) ) . '/languages/' );
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
		echo '<div class="error"><p>' . __( 'GeoDirectory plugin is required for the Franchise Manager plugin to work properly.', 'geodir-franchise' ) . '</p></div>';
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
	 * Enqueue scripts.
	 */
	public static function register_scripts() {
		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		$in_footer = true;

		// Register scripts
		wp_register_script( 'geodir-franchise-add', GEODIR_FRANCHISE_PLUGIN_URL . '/assets/js/add-franchise' . $suffix . '.js', array( 'jquery', 'geodir-add-listing' ), GEODIR_FRANCHISE_VERSION, $in_footer );
	}

	/**
	 * Load styles & scripts.
	 */
	public function load_scripts() {
		// Register scripts
		self::register_scripts();

		if ( geodir_is_page( 'add-listing' ) ) {
			wp_enqueue_script( 'geodir-franchise-add' );
			wp_localize_script( 'geodir-franchise-add', 'geodir_franchise_params', geodir_franchise_params() );
		}
	}
}
