<?php
/**
 * Check GeoDir_Lists class exists or not.
 */
if( ! class_exists( 'GeoDir_Lists' ) ) {

	/**
	 * Main GD Lists class.
	 *
	 * @class GeoDir_Lists
	 *
	 * @since 2.0.0
	 */
	final class GeoDir_Lists {

		/**
		 * GD Lists instance.
		 *
		 * @access private
		 * @since  2.0.0
		 *
		 * @var GeoDir_Lists instance.
		 */
		private static $instance = null;

		/**
		 * GD Lists Admin Object.
		 *
		 * @since  2.0.0
		 *
		 * @access public
		 *
		 * @var GeoDir_Lists object.
		 */
		public $plugin_admin;

		/**
		 * GD Lists Public Object.
		 *
		 * @since  2.0.0
		 *
		 * @access public
		 *
		 * @var GeoDir_Lists object.
		 */
		public $plugin_public;

		/**
		 * Get the instance and store the class inside it. This plugin utilises.
		 *
		 * @since 2.0.0
		 *
		 * @return object GeoDir_Lists
		 */
		public static function get_instance() {
			if ( ! isset( self::$instance ) && ! ( self::$instance instanceof GeoDir_Lists ) ) {
				self::$instance = new GeoDir_Lists();
				self::$instance->setup_constants();
				self::$instance->hooks();
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
			if ( ! defined( 'GD_LISTS_TEXTDOMAIN' ) ) {
				define( 'GD_LISTS_TEXTDOMAIN', 'gd-lists' );
			}

			if ( ! defined( 'GD_LISTS_PLUGIN_DIR' ) ) {
				define( 'GD_LISTS_PLUGIN_DIR', dirname( GD_LISTS_PLUGIN_FILE ) );
			}

			if ( ! defined( 'GD_LISTS_PLUGIN_URL' ) ) {
				define( 'GD_LISTS_PLUGIN_URL', plugin_dir_url( GD_LISTS_PLUGIN_FILE ) );
			}

			if ( ! defined( 'GD_LISTS_PLUGIN_DIR_PATH' ) ) {
				define( 'GD_LISTS_PLUGIN_DIR_PATH', plugin_dir_path( GD_LISTS_PLUGIN_FILE ) );
			}

			if ( ! defined( 'GD_LISTS_PLUGIN_BASENAME' ) ) {
				define( 'GD_LISTS_PLUGIN_BASENAME', plugin_basename( GD_LISTS_PLUGIN_FILE ) );
			}
		}

		/**
		 * Define Hooks.
		 *
		 * @since 2.0.0
		 */
		public function hooks(){
			add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
			add_filter( 'geodir_uninstall_options', array($this, 'uninstall_options'), 10, 1);
		}
		
		public function uninstall_options($settings){
			array_pop( $settings );
			$settings[] = array(
				'name'     => __( 'List Manager', 'gd-lists' ),
				'desc'     => __( 'Check this box if you would like to completely remove all of its data when List Manager is deleted.', 'gd-lists' ),
				'id'       => 'uninstall_geodir_lists',
				'type'     => 'checkbox',
			);
			$settings[] = array( 'type' => 'sectionend', 'id' => 'uninstall_options' );

			return $settings;
		}

		/**
		 * Register and enqueue duplicate alert styles and scripts.
		 *
		 * @since 2.0.0
		 */
		public function enqueue_scripts() {
			$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

			wp_register_script( 'list-manager-public-script', GD_LISTS_PLUGIN_URL . 'assets/js/geodir_list_manager_public' . $suffix . '.js', array( 'jquery' ), '', true );
			wp_enqueue_script( 'list-manager-public-script' );

			$vars = array(
				'field_required' => __( 'This field is required.', 'gd-lists' ),
				'select_item' => __( 'Please select listed items.', 'gd-lists' ),
				'save_list_text' => __( 'Save', 'gd-lists' ),
				'saved_list_text' => __( 'Saved', 'gd-lists' ),
				'saving_list_text' => __( 'Saving...', 'gd-lists' ),
				'aui' => geodir_design_style()
			);

			$vars = apply_filters('gd_lists_localize_vars', $vars);
			wp_localize_script( 'list-manager-public-script', 'gd_list_manager_vars', $vars);

			if ( ! geodir_design_style() ) {
				wp_register_style('list-manager-public-style', GD_LISTS_PLUGIN_URL . 'assets/css/geodir_list_manager_public.css', array(), '2.0.0');
				wp_enqueue_style('list-manager-public-style' );
			}
		}

		/**
		 * Includes.
		 *
		 * @since 2.0.0
		 */
		public function includes(){
			/**
			 * The class responsible for defining all list manager general functions.
			 */
			require_once( GD_LISTS_PLUGIN_DIR . '/includes/helper-functions.php' );
			require_once( GD_LISTS_PLUGIN_DIR . '/includes/class-geodir-lists-list.php' );
			require_once( GD_LISTS_PLUGIN_DIR . '/includes/class-geodir-lists-compatibility.php' );

			/**
			 * The class responsible for defining all actions that occur in the Admin area.
			 */
			require_once( GD_LISTS_PLUGIN_DIR . '/includes/class-geodir-lists-cpt.php' );
			require_once( GD_LISTS_PLUGIN_DIR . '/includes/class-geodir-lists-data.php' );

			/**
			 * The class responsible for defining all actions that occur in the Admin area.
			 */
			require_once( GD_LISTS_PLUGIN_DIR . '/includes/admin/class-geodir-lists-admin.php' );

			self::$instance->plugin_admin = new GeoDir_Lists_Admin();

			/**
			 * The class responsible for defining list widget.
			 */
			require_once( GD_LISTS_PLUGIN_DIR . '/includes/widgets/class-geodir-widget-list-single-description.php' );
			require_once( GD_LISTS_PLUGIN_DIR . '/includes/widgets/class-geodir-widget-list-save.php' );
			require_once( GD_LISTS_PLUGIN_DIR . '/includes/widgets/class-geodir-widget-list-loop.php' );
			require_once( GD_LISTS_PLUGIN_DIR . '/includes/widgets/class-geodir-widget-list-loop-actions.php' );

			// Check if BuddyPress exists or not.
			if ( gd_list_check_buddypress_exists() ) {
				/**
				 * The class responsible for defining all buddypress related functions.
				 */
				require_once( GD_LISTS_PLUGIN_DIR . '/includes/class_buddypress_functions.php' );
			}

			// ajax
			require_once( GD_LISTS_PLUGIN_DIR . '/includes/class-geodir-lists-ajax.php' );

			// forms
			require_once( GD_LISTS_PLUGIN_DIR . '/includes/class-geodir-lists-forms.php' );

			if ( is_admin() ) {
				require_once( GD_LISTS_PLUGIN_DIR . '/includes/admin/class-geodir-lists-admin-install.php' );
				GeoDir_Lists_Admin_Install::init(); // init the install class
			}
		}

		/**
		 * Load Localisation files.
		 *
		 * Note: the first-loaded translation file overrides any following ones if the same translation is present.
		 *
		 * @since 2.0.0
		 * 
		 * Locales found in:
		 *      - WP_LANG_DIR/gd-lists/gd-lists-LOCALE.mo
		 *      - WP_LANG_DIR/plugins/gd-lists-LOCALE.mo
		 */
		public function load_textdomain() {
			// Determines the current locale.
			$locale = determine_locale();

			/**
			 * Filter the plugin locale.
			 *
			 * @since   1.0.0
			 * @package GeoDir_Lists
			 */
			$locale = apply_filters( 'plugin_locale', $locale, 'gd-lists' );

			unload_textdomain( 'gd-lists', true );
			load_textdomain( 'gd-lists', WP_LANG_DIR . '/gd-lists/gd-lists-' . $locale . '.mo' );
			load_plugin_textdomain( 'gd-lists', false, basename( dirname( GD_LISTS_PLUGIN_FILE ) ) . '/languages/' );
		}
	}
}
