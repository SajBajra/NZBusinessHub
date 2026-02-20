<?php
/**
 * GeoDirectory Marketplace plugin main class
 *
 * @package GeoDir_Marketplace
 * @author AyeCode Ltd
 * @since 2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * GeoDir_Marketplace class.
 */
final class GeoDir_Marketplace {
	/**
	 * The single instance of the class.
	 *
	 * @since 2.0
	 */
	private static $instance = null;

	/**
	 * Main Marketplace Instance.
	 *
	 * Ensures only one instance of Marketplace is loaded or can be loaded.
	 *
	 * @since 2.0
	 * @static
	 * @return Marketplace - Main instance.
	 */
	public static function instance() {
		if ( ! isset( self::$instance ) && ! ( self::$instance instanceof GeoDir_Marketplace ) ) {
			self::$instance = new GeoDir_Marketplace;
			self::$instance->setup_constants();

			self::$instance->load_textdomain();

			if ( ! class_exists( 'GeoDirectory' ) || ! class_exists( 'WooCommerce' ) ) {
				add_action( 'admin_notices', array( self::$instance, 'geodirectory_notice' ) );

				return self::$instance;
			}

			// Check multivendor marketplace is active.
			if ( ! self::has_marketplace_active() ) {
				add_action( 'admin_notices', array( self::$instance, 'marketplace_plugin_notice' ) );

				return self::$instance;
			}

			if ( version_compare( PHP_VERSION, '5.6', '<' ) ) {
				add_action( 'admin_notices', array( self::$instance, 'php_version_notice' ) );

				return self::$instance;
			}

			self::$instance->includes();
			self::$instance->init_hooks();

			do_action( 'geodir_marketplace_loaded' );
		}

		return self::$instance;
	}

	/**
	 * Setup plugin constants.
	 *
	 * @since 2.0
	 *
	 * @return void
	 */
	private function setup_constants() {
		if ( $this->is_request( 'test' ) ) {
			$plugin_path = dirname( GEODIR_MARKETPLACE_PLUGIN_FILE );
		} else {
			$plugin_path = plugin_dir_path( GEODIR_MARKETPLACE_PLUGIN_FILE );
		}

		$this->define( 'GEODIR_MARKETPLACE_PLUGIN_DIR', $plugin_path );
		$this->define( 'GEODIR_MARKETPLACE_PLUGIN_URL', untrailingslashit( plugins_url( '/', GEODIR_MARKETPLACE_PLUGIN_FILE ) ) );
		$this->define( 'GEODIR_MARKETPLACE_PLUGIN_BASENAME', plugin_basename( GEODIR_MARKETPLACE_PLUGIN_FILE ) );
	}

	/**
	 * Include required files.
	 *
	 * @since 2.0
	 *
	 * @return void
	 */
	private function includes() {
		global $wp_version;

		/**
		 * Class autoloader.
		 */
		include_once( GEODIR_MARKETPLACE_PLUGIN_DIR . 'includes/class-geodir-marketplace-autoloader.php' );

		GeoDir_Marketplace_AJAX::init();
		GeoDir_Marketplace_WooCommerce::init();

		require_once( GEODIR_MARKETPLACE_PLUGIN_DIR . 'includes/functions.php' );

		if ( $this->is_request( 'admin' ) || $this->is_request( 'test' ) || $this->is_request( 'cli' ) ) {
			new GeoDir_Marketplace_Admin();

			require_once( GEODIR_MARKETPLACE_PLUGIN_DIR . 'includes/admin/admin-functions.php' );

			GeoDir_Marketplace_Admin_Install::init();

			require_once( GEODIR_MARKETPLACE_PLUGIN_DIR . 'update.php' );
		}
	}

	/**
	 * Hook into actions and filters.
	 *
	 * @since  2.0
	 */
	private function init_hooks() {
		add_action( 'init', array( $this, 'init' ), 0 );

		add_filter( 'geodir_get_widgets', 'geodir_marketplace_register_widgets', 20, 1 );
	}

	/**
	 * Initialise plugin when WordPress Initialises.
	 *
	 * @since 2.0
	 */
	public function init() {
		// Before init action.
		do_action( 'geodir_marketplace_before_init' );

		// Init action.
		do_action( 'geodir_marketplace_init' );
	}

	/**
	 * Loads the plugin language files
	 *
	 * @since 2.0
	 *
	 * @return void
	 */
	public function load_textdomain() {
		// Determines the current locale.
		$locale = determine_locale();

		/**
		 * Filter the plugin locale.
		 *
		 * @since 2.0
		 */
		$locale = apply_filters( 'plugin_locale', $locale, 'geomarketplace' );

		unload_textdomain( 'geomarketplace', true );
		load_textdomain( 'geomarketplace', WP_LANG_DIR . '/geomarketplace/geomarketplace' . '-' . $locale . '.mo' );
		load_plugin_textdomain( 'geomarketplace', false, basename( dirname( GEODIR_MARKETPLACE_PLUGIN_FILE ) ) . '/languages/' );
	}

	/**
	 * Define constant if not already set.
	 *
	 * @since 2.0
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
	 * @since 2.0
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
	 * Check plugin compatibility and show warning.
	 *
	 * @since 2.0
	 *
	 * @return void
	 */
	public static function geodirectory_notice() {
		$requires = array();

		if ( ! class_exists( 'GeoDirectory' ) ) {
			$requires[] = '<a href="https://wordpress.org/plugins/geodirectory/" target="_blank">GeoDirectory</a>';
		}

		if ( ! class_exists( 'WooCommerce' ) ) {
			$requires[] = '<a href="https://wordpress.org/plugins/woocommerce/" target="_blank">WooCommerce</a>';
		}

		if ( ! empty( $requires ) ) {
			echo '<div class="error"><p>' . wp_sprintf( __( 'GeoDirectory Marketplace plugin requires %s plugin to be active.', 'geomarketplace' ), implode( ", ", $requires ) ) . '</p></div>';
		}
	}

	/**
	 * Check marketplace plugin compatibility and show warning.
	 *
	 * @since 2.0
	 *
	 * @return void
	 */
	public static function marketplace_plugin_notice() {
		if ( ! self::has_marketplace_active() ) {
			if ( class_exists( 'WC_Vendors' ) && ! class_exists( 'WCVendors_Pro' ) ) {
				$requires = '<a href="https://www.wcvendors.com/product/wc-vendors-pro/" target="_blank">WC Vendors Pro</a>';

				echo '<div class="error"><p>' . wp_sprintf( __( 'GeoDirectory Marketplace plugin requires multivendor marketplace plugin %s to be active.', 'geomarketplace' ), $requires ) . '</p></div>';
				return;
			} else {
				$requires = array();

				$requires[] = '<a href="https://wordpress.org/plugins/dokan-lite/" target="_blank">Dokan</a>';
				$requires[] = '<a href="https://wordpress.org/plugins/wc-frontend-manager/" target="_blank">WCFM - WooCommerce Frontend Manager</a>';
				$requires[] = '<a href="https://wordpress.org/plugins/dc-woocommerce-multi-vendor/" target="_blank">MultiVendorX</a>';
				$requires[] = '<a href="https://www.wcvendors.com/product/wc-vendors-pro/" target="_blank">WC Vendors Pro</a>';
			}

			echo '<div class="error"><p>' . wp_sprintf( __( 'GeoDirectory Marketplace plugin requires any of multivendor marketplace plugin from %s to be active.', 'geomarketplace' ), implode( ", ", $requires ) ) . '</p></div>';
		}
	}

	/**
	 * Check marketplace plugin active or not.
	 *
	 * @since 2.0
	 *
	 * @return void
	 */
	public static function has_marketplace_active() {
		if ( class_exists( 'WeDevs_Dokan' ) || class_exists( 'WCFM' ) || class_exists( 'MVX' ) || class_exists( 'WCMp' ) ) {
			return true;
		}

		if ( class_exists( 'WC_Vendors' ) && class_exists( 'WCVendors_Pro' ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Show a warning to sites running PHP < 5.3
	 *
	 * @since 2.0
	 *
	 * @return void
	 */
	public static function php_version_notice() {
		echo '<div class="error"><p>' . __( 'Your version of PHP is below the minimum version of PHP required by GeoDirectory Marketplace. Please contact your host and request that your version be upgraded to 5.6 or later.', 'geomarketplace' ) . '</p></div>';
	}
}
