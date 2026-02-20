<?php
/**
 * Marker Cluster plugin main class.
 *
 * @package    GeoDir_Marker_Cluster
 * @since      2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
	}

if ( ! class_exists( 'GeoDir_Marker_Cluster' ) ) {
	/**
	 * GeoDir_Marker_Cluster class.
	 */
	class GeoDir_Marker_Cluster {

		private static $instance;

		public static function get_instance() {
			if ( ! isset( self::$instance ) && ! ( self::$instance instanceof GeoDir_Marker_Cluster ) ) {
				self::$instance = new GeoDir_Marker_Cluster;
				self::$instance->setup_globals();

				add_action( 'plugins_loaded', array( self::$instance, 'load_textdomain' ) );

				self::$instance->includes();
				self::$instance->define_admin_hooks();
				self::$instance->define_public_hooks();

				do_action( 'geodir_marker_cluster_loaded' );
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
		 * @since    2.0.0
		 * @access   private
		 */
		private function define_admin_hooks() {
			$plugin_admin = new GeoDir_Marker_Cluster_Admin();

			add_filter( 'geodir_get_settings_pages', array( $plugin_admin, 'load_settings_page' ), 11, 1 );
			add_action( 'admin_enqueue_scripts', array( $plugin_admin, 'admin_enqueue_scripts' ), 11 );

			do_action( 'gd_marker_cluster_setup_admin_actions' );
		}

		/**
		 * Register all of the hooks related to the public-facing functionality
		 * of the plugin.
		 *
		 * @since    2.0.0
		 * @access   private
		 */
		private function define_public_hooks() {
			$plugin_public = new GeoDir_Marker_Cluster_Public();

			add_action( 'wp_enqueue_scripts', array( $plugin_public, 'enqueue_scripts' ), 99 );
			add_action( 'wp_footer', array( $plugin_public, 'footer_script' ), 99 );
			add_action( 'geodir_params', array( $plugin_public, 'geodir_params' ) );
			add_action( 'geodir_map_params', array( $plugin_public, 'update_map_options' ) );
			add_action( 'geodir_map_api_google_data', array( $plugin_public, 'map_api_google_data' ), 10, 1 );
			add_action( 'geodir_map_api_osm_data', array( $plugin_public, 'map_api_osm_data' ), 10, 1 );
			add_action( 'geodir_widget_map_scripts_on_call', array( $plugin_public, 'load_scripts' ), 10, 1 );

			// Handle marker cluster type on AJAX search
			if ( ! empty( $_REQUEST['geodir_search'] ) ) {
				add_filter( 'geodir_get_option_marker_cluster_type', array( $plugin_public, 'filter_marker_cluster_type' ), 10, 3 );
			}

			do_action( 'gd_marker_cluster_setup_actions' );
		}

		/**
		 * Load the text domain.
		 */
		public function load_textdomain() {
			$locale = determine_locale();

			$locale = apply_filters( 'plugin_locale', $locale, 'geodir_markercluster' );

			unload_textdomain( 'geodir_markercluster', true );
			load_textdomain( 'geodir_markercluster', WP_LANG_DIR . '/geodir_markercluster/geodir_markercluster-' . $locale . '.mo' );
			load_plugin_textdomain( 'geodir_markercluster', false, basename( dirname( GEODIR_MARKERCLUSTER_PLUGIN_FILE ) ) . '/languages/' );
		}

		/**
		 * Include the files.
		 */
		private function includes() {
			require_once( GEODIR_MARKERCLUSTER_PLUGINDIR_PATH . '/admin/class-geodir-marker-cluster-admin.php' );
			require_once( GEODIR_MARKERCLUSTER_PLUGINDIR_PATH . '/public/class-geodir-marker-cluster-public.php' );
			require_once( GEODIR_MARKERCLUSTER_PLUGINDIR_PATH . '/includes/class-geodir-server-side-cluster.php' );
			require_once( GEODIR_MARKERCLUSTER_PLUGINDIR_PATH . '/includes/general-functions.php' );
		}
	}
}