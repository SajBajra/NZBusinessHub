<?php
/**
 * Check GD_Social_Importer class exists or not.
 */
if( ! class_exists( 'GD_Social_Importer' ) ) {
	/**
	 * Main GD Social Importer class.
	 *
	 * @class GD_Social_Importer
	 *
	 * @since 2.0.0
	 */
	final class GD_Social_Importer {
		/**
		 * GD Social Importer instance.
		 *
		 * @access private
		 * @since  2.0.0
		 *
		 * @var GD_Social_Importer instance.
		 */
		private static $instance = null;

		/**
		 * GD Social Importer version.
		 *
		 * @since  2.0.0
		 *
		 * @access public
		 *
		 * @var string $version .
		 */
		public $version = GEODIR_SOCIAL_IMPORTER_VERSION;

		/**
		 * GD Social Importer Admin Object.
		 *
		 * @since  2.0.0
		 *
		 * @access public
		 *
		 * @var GD_Social_Importer object.
		 */
		public $plugin_admin;

		/**
		 * GD Social Importer Public Object.
		 *
		 * @since  2.0.0
		 *
		 * @access public
		 *
		 * @var GD_Social_Importer object.
		 */
		public $plugin_public;

		/**
		 * GD Social Importer Rest Object.
		 *
		 * @since  2.0.0
		 *
		 * @access public
		 *
		 * @var GD_Social_Importer_Rest object.
		 */
		public $plugin_rest;

		/**
		 * Get the instance and store the class inside it. This plugin utilises.
		 *
		 * @since 2.0.0
		 *
		 * @return object GD_Social_Importer
		 */
		public static function get_instance() {
			if ( ! isset( self::$instance ) && ! ( self::$instance instanceof GD_Social_Importer ) ) {
				self::$instance = new GD_Social_Importer();
				self::$instance->setup_constants();

				add_action( 'plugins_loaded', array( self::$instance, 'load_textdomain' ) );

				self::$instance->includes();
			}

			return self::$instance;
		}

		/**
		 * Set plugin constants.
		 *
		 * @since   2.0.0
		 *
		 * @access  public
		 */
		public function setup_constants() {
			// Define GD Social Importer plugin textdomain.
			if ( ! defined( 'GD_SOCIAL_IMPORTER_TEXTDOMAIN' ) ) {
				define( 'GD_SOCIAL_IMPORTER_TEXTDOMAIN', 'gd-social-importer' );
			}

			// Define GD Social Importer plugin version.
			if ( ! defined( 'GD_SOCIAL_IMPORTER_VERSION' ) ) {
				define( 'GD_SOCIAL_IMPORTER_VERSION', $this->version );
			}

			// Define GD Social Importer plugin file.
			if ( ! defined( 'GD_SOCIAL_IMPORTER_PLUGIN_FILE' ) ) {
				define( 'GD_SOCIAL_IMPORTER_PLUGIN_FILE', __FILE__ );
			}

			// Define GD Social Importer plugin Directory.
			if ( ! defined( 'GD_SOCIAL_IMPORTER_PLUGIN_DIR' ) ) {
				define( 'GD_SOCIAL_IMPORTER_PLUGIN_DIR', dirname( GD_SOCIAL_IMPORTER_PLUGIN_FILE ) );
			}

			// Define GD Social Importer plugin URL.
			if ( ! defined( 'GD_SOCIAL_IMPORTER_PLUGIN_URL' ) ) {
				define( 'GD_SOCIAL_IMPORTER_PLUGIN_URL', plugin_dir_url( GD_SOCIAL_IMPORTER_PLUGIN_FILE ) );
			}

			// Define GD Social Importer plugin Directory path.
			if ( ! defined( 'GD_SOCIAL_IMPORTER_PLUGIN_DIR_PATH' ) ) {
				define( 'GD_SOCIAL_IMPORTER_PLUGIN_DIR_PATH', plugin_dir_path( GD_SOCIAL_IMPORTER_PLUGIN_FILE ) );
			}

			// Define GD Social Importer plugin Basepath.
			if ( ! defined( 'GD_SOCIAL_IMPORTER_PLUGIN_BASENAME' ) ) {
				define( 'GD_SOCIAL_IMPORTER_PLUGIN_BASENAME', plugin_basename( GD_SOCIAL_IMPORTER_PLUGIN_FILE ) );
			}

			// Google My Business
			if ( ! defined( 'GEODIR_GMB_AUTH_URL' ) ) {
				define( 'GEODIR_GMB_AUTH_URL', 'https://accounts.google.com/o/oauth2/auth' ); // OAuth 2.0 auth URI
			}

			if ( ! defined( 'GEODIR_GMB_TOKEN_URL' ) ) {
				define( 'GEODIR_GMB_TOKEN_URL', 'https://accounts.google.com/o/oauth2/token' );
			}

			if ( ! defined( 'GEODIR_GMB_REVOKE_URL' ) ) {
				define( 'GEODIR_GMB_REVOKE_URL', 'https://accounts.google.com/o/oauth2/revoke' );
			}

			if ( ! defined( 'GEODIR_GMB_CLIENT_ID' ) ) {
				// AyeCode Sync GMB
				define( 'GEODIR_GMB_CLIENT_ID', '316196763102-1jcj15diau8ua1vdhoe33ga4tjeh524s.apps.googleusercontent.com' ); // Client ID
			}

			if ( ! defined( 'GEODIR_GMB_CLIENT_SECRET' ) ) {
				define( 'GEODIR_GMB_CLIENT_SECRET', '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' ); // Client secret
			}

			if ( ! defined( 'GEODIR_GMB_SCOPE' ) ) {
				define( 'GEODIR_GMB_SCOPE', 'https://www.googleapis.com/auth/business.manage' );
			}

			if ( ! defined( 'GEODIR_GMB_REDIRECT' ) ) {
				define( 'GEODIR_GMB_REDIRECT', 'https://ayecode.io/wp-json/ayecode/v1/oauth2gacode/' ); // Authorised redirect URI
			}
		}

		/**
		 * Includes.
		 *
		 * @since 2.0.0
		 */
		public function includes() {
			/**
			 * The class responsible for defining all common actions.
			 */
			require_once( GD_SOCIAL_IMPORTER_PLUGIN_DIR . '/includes/class-gd-social-importer-function.php' );

			/**
			 * The class responsible for defining all actions for yelp oauth.
			 */
			require_once( GD_SOCIAL_IMPORTER_PLUGIN_DIR . '/includes/yelp-oauth.php' );

			/**
			 * Google My Business functions.
			 */
			require_once( GD_SOCIAL_IMPORTER_PLUGIN_DIR . '/includes/gmb-functions.php' );

			/**
			 * The class responsible for defining all actions for import facebook social URL.
			 */
			require_once( GD_SOCIAL_IMPORTER_PLUGIN_DIR . '/includes/class-gd-social-importer-facebook.php' );

			/**
			 * The class responsible for defining all actions for import Yelp URL.
			 */
			require_once( GD_SOCIAL_IMPORTER_PLUGIN_DIR . '/includes/class-gd-social-importer-yelp.php' );

			/**
			 * The class responsible for defining all actions for import Tripadvisor URL.
			 */
			require_once( GD_SOCIAL_IMPORTER_PLUGIN_DIR . '/includes/class-gd-social-importer-tripadvisor.php' );

			/**
			 * The class responsible for defining all actions that occur in the admin area.
			 */
			require_once( GD_SOCIAL_IMPORTER_PLUGIN_DIR . '/includes/admin/class-geodir-social-importer-admin.php' );

			/**
			 * The class responsible for defining all actions that occur in the public area.
			 */
			require_once( GD_SOCIAL_IMPORTER_PLUGIN_DIR . '/includes/public/class-geodir-social-importer-public.php' );

			/**
			 * The class responsible for defining all actions that occur in the rest api.
			 */
			//require_once( GD_SOCIAL_IMPORTER_PLUGIN_DIR . '/includes/class-geodir-social-importer-rest.php' ); // @todo uncomment once chrome extension is ready

			self::$instance->plugin_admin = new GD_Social_Importer_Admin();

			self::$instance->plugin_public = new GD_Social_Importer_Public();

			//self::$instance->plugin_rest = new GD_Social_Importer_Rest(); // @todo uncomment once chrome extension is ready
		}

		/**
		 * Load GD social importer language file.
		 *
		 * @since 2.0.0
		 */
		public function load_textdomain() {
			$locale = determine_locale();

			/**
			 * Filter the plugin locale.
			 *
			 * @since 1.0.0
			 */
			$locale = apply_filters( 'plugin_locale', $locale, 'gd-social-importer' );

			unload_textdomain( 'gd-social-importer', true );
			load_textdomain( 'gd-social-importer', WP_LANG_DIR . '/gd-social-importer/gd-social-importer-' . $locale . '.mo' );
			load_plugin_textdomain( 'gd-social-importer', false, basename( dirname( GD_SOCIAL_IMPORTER_PLUGIN_FILE ) ) . '/languages/' );
		}
	}
}