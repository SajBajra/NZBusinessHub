<?php
/**
 * Save Search Notifications main class
 *
 * @author    AyeCode Ltd
 * @package   GeoDir_Save_Search
 * @version   1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * GeoDir_Save_Search class.
 */
final class GeoDir_Save_Search {
	/**
	 * The single instance of the class.
	 *
	 * @since 1.0
	 */
	private static $instance = null;

	/**
	 * Save Search Notifications plugin main instance.
	 *
	 * @since 1.0
	 *
	 * @return Save Search Notifications instance.
	 */
	public static function instance() {
		if ( ! isset( self::$instance ) && ! ( self::$instance instanceof GeoDir_Save_Search ) ) {
			self::$instance = new GeoDir_Save_Search;
			self::$instance->setup_constants();

			add_action( 'plugins_loaded', array( self::$instance, 'load_textdomain' ) );

			if ( ! class_exists( 'GeoDirectory' ) || ! function_exists( 'geodir_load_advance_search_filters' ) ) {
				add_action( 'admin_notices', array( self::$instance, 'geodirectory_notice' ) );

				return self::$instance;
			}

			if ( version_compare( PHP_VERSION, '5.6', '<' ) ) {
				add_action( 'admin_notices', array( self::$instance, 'php_version_notice' ) );

				return self::$instance;
			}

			self::$instance->includes();
			self::$instance->init_hooks();

			do_action( 'geodir_save_search_loaded' );
		}

		return self::$instance;
	}

	/**
	 * Setup plugin constants.
	 *
	 * @since 1.0
	 *
	 * @return void
	 */
	private function setup_constants() {
		global $wpdb;

		if ( $this->is_request( 'test' ) ) {
			$plugin_path = dirname( GEODIR_SAVE_SEARCH_PLUGIN_FILE );
		} else {
			$plugin_path = plugin_dir_path( GEODIR_SAVE_SEARCH_PLUGIN_FILE );
		}

		$this->define( 'GEODIR_SAVE_SEARCH_PLUGIN_DIR', $plugin_path );
		$this->define( 'GEODIR_SAVE_SEARCH_PLUGIN_URL', untrailingslashit( plugins_url( '/', GEODIR_SAVE_SEARCH_PLUGIN_FILE ) ) );
		$this->define( 'GEODIR_SAVE_SEARCH_PLUGIN_BASENAME', plugin_basename( GEODIR_SAVE_SEARCH_PLUGIN_FILE ) );

		// Define database tables
		$this->define( 'GEODIR_SAVE_SEARCH_EMAILS_TABLE', $wpdb->prefix . 'geodir_save_search_emails' );
		$this->define( 'GEODIR_SAVE_SEARCH_FIELDS_TABLE', $wpdb->prefix . 'geodir_save_search_fields' );
		$this->define( 'GEODIR_SAVE_SEARCH_SUBSCRIBERS_TABLE', $wpdb->prefix . 'geodir_save_search_subscribers' );
	}

	/**
	 * Include required files.
	 *
	 * @since 1.0
	 *
	 * @return void
	 */
	private function includes() {
		/**
		 * Class autoloader.
		 */
		include_once( GEODIR_SAVE_SEARCH_PLUGIN_DIR . 'includes/class-geodir-save-search-autoloader.php' );

		GeoDir_Save_Search_AJAX::init();
		GeoDir_Save_Search_Email::init();
		GeoDir_Save_Search_Post::init();
		GeoDir_Save_Search_Query::init();

		require_once( GEODIR_SAVE_SEARCH_PLUGIN_DIR . 'includes/functions.php' );

		if ( $this->is_request( 'admin' ) || $this->is_request( 'test' ) || $this->is_request( 'cli' ) ) {
			new GeoDir_Save_Search_Admin();

			require_once( GEODIR_SAVE_SEARCH_PLUGIN_DIR . 'includes/admin/admin-functions.php' );

			GeoDir_Save_Search_Admin_Install::init();

			require_once( GEODIR_SAVE_SEARCH_PLUGIN_DIR . 'upgrade.php' );
		}
	}

	/**
	 * Handle actions and filters.
	 *
	 * @since 1.0
	 */
	private function init_hooks() {
		if ( $this->is_request( 'frontend' ) ) {
			
		}

		add_action( 'init', array( $this, 'init' ), 0 );
		add_action( 'cron_schedules', 'geodir_save_search_cron_schedules', 41, 1 );

		add_filter( 'geodir_get_widgets', array( $this, 'register_widgets' ), 41, 1 );
		add_filter( 'geodir_locate_template', 'geodir_save_search_locate_template', 41, 3 );
	}

	/**
	 * Initialise plugin when WordPress Initialises.
	 *
	 * @since 1.0
	 */
	public function init() {
		// Init action.
		do_action( 'geodir_save_search_init' );
	}

	/**
	 * Loads the plugin language files
	 *
	 * @since 1.0
	 *
	 * @return void
	 */
	public function load_textdomain() {
		// Determines the current locale.
		$locale = determine_locale();

		$locale = apply_filters( 'plugin_locale', $locale, 'geodir-save-search' );

		unload_textdomain( 'geodir-save-search', true );
		load_textdomain( 'geodir-save-search', WP_LANG_DIR . '/geodir-save-search/geodir-save-search-' . $locale . '.mo' );
		load_plugin_textdomain( 'geodir-save-search', false, basename( dirname( GEODIR_SAVE_SEARCH_PLUGIN_FILE ) ) . '/languages/' );
	}

	/**
	 * Check plugin compatibility and show warning.
	 *
	 * @since 1.0
	 *
	 * @return void
	 */
	public static function geodirectory_notice() {
		if ( ! class_exists( 'GeoDirectory' ) ) {
			echo '<div class="error"><p>' . __( 'GeoDirectory plugin is required for the Save Search Notifications plugin to work properly.', 'geodir-save-search' ) . '</p></div>';
		} else if ( ! function_exists( 'geodir_load_advance_search_filters' ) ) {
			echo '<div class="error"><p>' . __( 'GeoDirectory Advanced Search Filters plugin is required for the Save Search Notifications plugin to work properly.', 'geodir-save-search' ) . '</p></div>';
		}
	}

	/**
	 * Show a warning to sites running PHP < 5.6
	 *
	 * @since 1.0
	 *
	 * @return void
	 */
	public static function php_version_notice() {
		echo '<div class="error"><p>' . __( 'Your version of PHP is below the minimum version of PHP required by GeoDirectory Save Search Notifications. Please contact your host and request that your version be upgraded to 5.6 or later.', 'geodir-save-search' ) . '</p></div>';
	}

	/**
	 * Define constant if not already set.
	 *
	 * @since 1.0
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
	 * @since 1.0
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
	 * Register widgets.
	 *
	 * @since 1.0
	 *
	 * @param array $widgets List of GD widgets.
	 * @return array GD widgets.
	 */
	public function register_widgets( $widgets ) {
		if ( geodir_design_style() ) {
			$widgets[] = 'GeoDir_Save_Search_Widget_Save';
			$widgets[] = 'GeoDir_Save_Search_Widget_List';
		}

		return $widgets;
	}
}
