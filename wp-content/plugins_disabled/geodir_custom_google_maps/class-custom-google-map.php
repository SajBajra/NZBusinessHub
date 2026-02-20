<?php
/**
 * Check GD_Google_Maps class exists or not.
 */
if( ! class_exists( 'GD_Google_Maps' ) ) {

	/**
	 * Main GD Google maps class.
	 *
	 * @class GD_Google_Maps
	 *
	 * @since 2.0.0
	 */
	final class GD_Google_Maps {

		/**
		 * GD Google maps instance.
		 *
		 * @access private
		 * @since 2.0.0
		 *
		 * @var GD_Google_Maps instance.
		 */
		private static $instance = null;

		/**
		 * GD Google maps version.
		 *
		 * @since 2.0.0
		 *
		 * @var string $version.
		 */
		public $version = '2.2.1';

		/**
		 * GD Google maps Admin Object.
		 *
		 * @since 2.0.0
		 *
		 * @access public
		 *
		 * @var GD_Google_Maps object.
		 */
		public $plugin_admin;

		/**
		 * GD Google maps Public Object.
		 *
		 * @since 2.0.0
		 *
		 * @access public
		 *
		 * @var GD_Google_Maps object.
		 */
		public $plugin_public;

		/**
		 * Get the instance and store the class inside it. This plugin utilises.
		 *
		 * @since 2.0.0
		 *
		 * @return object GD_Google_Maps
		 */
		public static function get_instance() {

			if ( ! isset( self::$instance ) && ! ( self::$instance instanceof GD_Google_Maps ) ) {
				self::$instance = new GD_Google_Maps();
				self::$instance->setup_constants();
				self::$instance->hooks();
				self::$instance->includes();
			}

			return self::$instance;
		}

		/**
		 * Set plugin constants.
		 *
		 * @since 2.0.0
		 *
		 * @access  private
		 */
		public function setup_constants() {

			// Define google maps text domain.
			if( !defined( 'GD_GOOGLE_MAPS_TEXTDOMAIN')) {
				define( 'GD_GOOGLE_MAPS_TEXTDOMAIN', 'geodir-custom-google-maps' );
			}

			// Define google maps version.
			if( !defined( 'GD_GOOGLE_MAPS_VERSION')) {
				define( 'GD_GOOGLE_MAPS_VERSION', $this->version );
			}

			// Define google maps plugin file.
			if( !defined( 'GD_GOOGLE_MAPS_PLUGIN_FILE')) {
				define( 'GD_GOOGLE_MAPS_PLUGIN_FILE', __FILE__ );
			}

			// Define google maps plugin directory.
			if( !defined( 'GD_GOOGLE_MAPS_PLUGIN_DIR')) {
				define( 'GD_GOOGLE_MAPS_PLUGIN_DIR', dirname(GD_GOOGLE_MAPS_PLUGIN_FILE ) );
			}

			// Define google maps plugin URL.
			if( !defined( 'GD_GOOGLE_MAPS_PLUGIN_URL')) {
				define( 'GD_GOOGLE_MAPS_PLUGIN_URL', plugin_dir_url(GD_GOOGLE_MAPS_PLUGIN_FILE ) );
			}

			// Define google maps plugin directory path.
			if( !defined( 'GD_GOOGLE_MAPS_PLUGIN_DIR_PATH')) {
				define( 'GD_GOOGLE_MAPS_PLUGIN_DIR_PATH', plugin_dir_path(GD_GOOGLE_MAPS_PLUGIN_FILE ) );
			}

			// Define google maps plugin basename.
			if( !defined( 'GD_GOOGLE_MAPS_PLUGIN_BASENAME')) {
				define( 'GD_GOOGLE_MAPS_PLUGIN_BASENAME', plugin_basename(GD_GOOGLE_MAPS_PLUGIN_FILE ) );
			}
		}

		/**
		 * Define Hooks.
		 *
		 * @since 2.0.0
		 */
		public function hooks() {
			add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
		}

		/**
		 * Includes.
		 *
		 * @since 2.0.0
		 */
		public function includes() {
			require_once( GD_GOOGLE_MAPS_PLUGIN_DIR . '/includes/gd-google-maps-helper.php' );

			require_once( GD_GOOGLE_MAPS_PLUGIN_DIR . '/includes/admin/class-custom-google-maps-admin.php' );
			require_once( GD_GOOGLE_MAPS_PLUGIN_DIR . '/includes/public/class-custom-google-maps-public.php' );

			$this->plugin_admin  = new GD_Google_Maps_Admin();
			$this->plugin_public = new GD_Google_Maps_Public();

			add_action( 'geodir_widget_enqueue_scripts_on_call_after', array( $this->plugin_public, 'enqueue_scripts_after_call' ), 10, 4 );
			add_action( 'geodir_widget_map_scripts_on_call', array( $this->plugin_public, 'load_scripts' ), 10, 1 );
		}

		/**
		 * Load GD google maps language file.
		 *
		 * @since 2.0.0
		 */
		public function load_textdomain() {
			$locale = determine_locale();

			$locale = apply_filters( 'plugin_locale', $locale, 'geodir-custom-google-maps' );

			unload_textdomain( 'geodir-custom-google-maps', true );
			load_textdomain( 'geodir-custom-google-maps', WP_LANG_DIR . '/geodir-custom-google-maps/geodir-custom-google-maps-' . $locale . '.mo' );
			load_plugin_textdomain( 'geodir-custom-google-maps', false, basename( dirname( GD_GOOGLE_MAPS_PLUGIN_FILE ) ) . '/languages/' );
		}
	}

}