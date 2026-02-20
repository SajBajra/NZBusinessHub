<?php
/**
 * Dynamic User Emails main class
 *
 * @author    AyeCode Ltd
 * @package   GeoDir_Dynamic_Emails
 * @version   2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * GeoDir_Dynamic_Emails class.
 */
final class GeoDir_Dynamic_Emails {
	/**
	 * The single instance of the class.
	 *
	 * @since 2.0.0
	 */
	private static $instance = null;

	/**
	 * Plugin main instance.
	 *
	 * @since 2.0.0
	 *
	 * @return Plugin instance.
	 */
	public static function instance() {
		if ( ! isset( self::$instance ) && ! ( self::$instance instanceof GeoDir_Dynamic_Emails ) ) {
			self::$instance = new GeoDir_Dynamic_Emails;
			self::$instance->setup_constants();

			add_action( 'plugins_loaded', array( self::$instance, 'load_textdomain' ) );

			if ( ! class_exists( 'GeoDirectory' ) ) {
				add_action( 'admin_notices', array( self::$instance, 'geodirectory_notice' ) );

				return self::$instance;
			}

			if ( version_compare( PHP_VERSION, '5.6', '<' ) ) {
				add_action( 'admin_notices', array( self::$instance, 'php_version_notice' ) );

				return self::$instance;
			}

			self::$instance->includes();
			self::$instance->init_hooks();

			do_action( 'geodir_dynamic_emails_loaded' );
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
		global $wpdb;

		if ( $this->is_request( 'test' ) ) {
			$plugin_path = dirname( GEODIR_DYNAMIC_EMAILS_PLUGIN_FILE );
		} else {
			$plugin_path = plugin_dir_path( GEODIR_DYNAMIC_EMAILS_PLUGIN_FILE );
		}

		$this->define( 'GEODIR_DYNAMIC_EMAILS_PLUGIN_DIR', $plugin_path );
		$this->define( 'GEODIR_DYNAMIC_EMAILS_PLUGIN_URL', untrailingslashit( plugins_url( '/', GEODIR_DYNAMIC_EMAILS_PLUGIN_FILE ) ) );
		$this->define( 'GEODIR_DYNAMIC_EMAILS_PLUGIN_BASENAME', plugin_basename( GEODIR_DYNAMIC_EMAILS_PLUGIN_FILE ) );

		// Define database tables
		$this->define( 'GEODIR_DYNAMIC_EMAILS_LISTS_TABLE', $wpdb->prefix . 'geodir_email_lists' );
		$this->define( 'GEODIR_DYNAMIC_EMAILS_LOG_TABLE', $wpdb->prefix . 'geodir_email_log' );
		$this->define( 'GEODIR_DYNAMIC_EMAILS_USERS_TABLE', $wpdb->prefix . 'geodir_email_users' );
	}

	/**
	 * Include required files.
	 *
	 * @since 2.0.0
	 *
	 * @return void
	 */
	private function includes() {
		/**
		 * Class autoloader.
		 */
		include_once( GEODIR_DYNAMIC_EMAILS_PLUGIN_DIR . 'includes/class-geodir-dynamic-emails-autoloader.php' );

		GeoDir_Dynamic_Emails_Action::init();
		GeoDir_Dynamic_Emails_AJAX::init();
		GeoDir_Dynamic_Emails_Email::init();
		GeoDir_Dynamic_Emails_Fields::init();
		GeoDir_Dynamic_Emails_List::init();
		GeoDir_Dynamic_Emails_Log::init();
		GeoDir_Dynamic_Emails_User::init();

		require_once( GEODIR_DYNAMIC_EMAILS_PLUGIN_DIR . 'includes/functions.php' );

		if ( $this->is_request( 'admin' ) || $this->is_request( 'test' ) || $this->is_request( 'cli' ) ) {
			include_once( GEODIR_DYNAMIC_EMAILS_PLUGIN_DIR . 'includes/admin/class-geodir-dynamic-emails-admin.php' );

			require_once( GEODIR_DYNAMIC_EMAILS_PLUGIN_DIR . 'includes/admin/admin-functions.php' );

			GeoDir_Dynamic_Emails_Admin_Install::init();

			require_once( GEODIR_DYNAMIC_EMAILS_PLUGIN_DIR . 'upgrade.php' );
		}
	}

	/**
	 * Handle actions and filters.
	 *
	 * @since 2.0.0
	 */
	private function init_hooks() {
		add_action( 'init', array( $this, 'init' ), 0 );
		add_action( 'cron_schedules', 'geodir_dynamic_emails_cron_schedules', 41, 1 );

		add_filter( 'geodir_locate_template', 'geodir_dynamic_emails_locate_template', 41, 3 );
	}

	/**
	 * Initialise plugin when WordPress Initialises.
	 *
	 * @since 2.0.0
	 */
	public function init() {
		// Init action.
		do_action( 'geodir_dynamic_emails_init' );
	}

	/**
	 * Loads the plugin language files
	 *
	 * @since 2.0.0
	 *
	 * @return void
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

		$locale = apply_filters( 'plugin_locale', $locale, 'geodir-dynamic-emails' );

		unload_textdomain( 'geodir-dynamic-emails' );
		load_textdomain( 'geodir-dynamic-emails', WP_LANG_DIR . '/geodir-dynamic-emails/geodir-dynamic-emails-' . $locale . '.mo' );
		load_plugin_textdomain( 'geodir-dynamic-emails', false, basename( dirname( GEODIR_DYNAMIC_EMAILS_PLUGIN_FILE ) ) . '/languages/' );
	}

	/**
	 * Check plugin compatibility and show warning.
	 *
	 * @since 2.0.0
	 *
	 * @return void
	 */
	public static function geodirectory_notice() {
		if ( ! class_exists( 'GeoDirectory' ) ) {
			echo '<div class="error"><p>' . __( 'GeoDirectory plugin is required for the Dynamic User Emails plugin to work properly.', 'geodir-dynamic-emails' ) . '</p></div>';
		}
	}

	/**
	 * Show a warning to sites running PHP < 5.6
	 *
	 * @since 2.0.0
	 *
	 * @return void
	 */
	public static function php_version_notice() {
		echo '<div class="error"><p>' . __( 'Your version of PHP is below the minimum version of PHP required by GeoDirectory Dynamic User Emails. Please contact your host and request that your version be upgraded to 5.6 or later.', 'geodir-dynamic-emails' ) . '</p></div>';
	}

	/**
	 * Define constant if not already set.
	 *
	 * @since 2.0.0
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
	 * @since 2.0.0
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
