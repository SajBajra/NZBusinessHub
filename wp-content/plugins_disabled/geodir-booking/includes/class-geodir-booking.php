<?php
/**
 * Main plugin class.
 *
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Main plugin class.
 *
 */
class GeoDir_Booking {

	/**
	 * Main admin class.
	 *
	 * @var GeoDir_Booking_Admin
	 */
	public $admin;

	/**
	 * Main ical import page class.
	 *
	 * @var GeoDir_Booking_Ical_Import_Page
	 */
	public $ical_import_page;

	/**
	 * Main sync status page class.
	 *
	 * @var GeoDir_Booking_Sync_Status_Page
	 */
	public $sync_status_page;

	/**
	 * Main add booking page class.
	 *
	 * @var GeoDir_Booking_Add_Booking_Page
	 */
	public $add_booking_page;

	/**
	 * Booking search handler.
	 *
	 * @var GeoDir_Booking_Search
	 */
	public $booking_search;

	/**
	 * Main emails class.
	 *
	 * @var GeoDir_Booking_Emails
	 */
	public $emails;

	/**
	 * Ajax handler.
	 *
	 * @var GeoDir_Booking_Ajax
	 */
	public $ajax;

	/**
	 * Cron handler.
	 *
	 * @var GeoDir_Booking_CRON
	 */
	public $cron;

	/**
	 * iCal Feed handler.
	 *
	 * @var GeoDir_Booking_Ical_Feed
	 */
	public $ical_feed;

	/**
	 * iCal Sync handler.
	 *
	 * @var GeoDir_Booking_Background_Sync
	 */
	public $ical_sync;

	/**
	 * Queued Sync handler.
	 *
	 * @var GeoDir_Booking_Queued_Sync
	 */
	public $queued_sync;

	/**
	 * iCal Uploader handler.
	 *
	 * @var GeoDir_Booking_Background_Uploader
	 */
	public $ical_uploader;

	/**
	 * Rest API.
	 *
	 * @var Geodir_Booking_Rest
	 */
	public $rest_api;

	/**
	 * Class constructor.
	 *
	 */
	public function __construct() {
		$this->load_files();
		$this->init();
	}

	/**
	 * Loads required files.
	 *
	 */
	protected function load_files() {

		require_once plugin_dir_path( __FILE__ ) . 'functions.php';
		require_once plugin_dir_path( __FILE__ ) . 'class-geodir-customer-booking.php';
		require_once plugin_dir_path( __FILE__ ) . '/libraries/zapcal/zapcallib.php';
		require_once plugin_dir_path( __FILE__ ) . '/libraries/wp-background-processing/wp-async-request.php';
		require_once plugin_dir_path( __FILE__ ) . '/libraries/wp-background-processing/wp-background-process.php';

		spl_autoload_register( array( $this, 'autoload' ), true );
	}

	/**
	 * Inits the plugin.
	 *
	 */
	protected function init() {
		add_action( 'getpaid_widget_classes', array( $this, 'register_widgets' ) );
		add_action( 'wp_footer', array( $this, 'maybe_load_scripts' ), 1 );
		//add_action( 'plugins_loaded', array( $this, 'on_plugins_loaded' ), -5 );
		$this->on_plugins_loaded();
		add_action( 'template_redirect', array( $this, 'geodir_booking_action' ) );
		add_action( 'getpaid_invoice_status_publish', array( $this, 'invoice_paid' ) );
		add_action( 'getpaid_invoice_status_wpi-cancelled', array( $this, 'invoice_cancelled' ) );
		add_filter( 'getpaid_invoice_notifications_is_payment_form_invoice', array( $this, 'skip_invoice_email' ), 10, 2 );
		add_filter( 'getpaid_taxable_amount', array( $this, 'filter_getpaid_taxable_amount' ), 10, 2 );
		add_action( 'geodir_save_post_data', array( $this, 'save_post_data' ), 99999, 4 );
		add_action( 'geodir_post_saved', array( $this, 'geodir_post_saved' ) );
		add_action( 'geodir_ajax_post_saved', array( $this, 'on_ajax_post_saved' ), 9, 2 );
		add_action( 'geodir_after_post_save', array( $this, 'on_after_post_save' ), 10, 6 );
		add_action( 'pre_get_posts', array( $this, 'pre_get_posts' ) );
		add_action( 'admin_notices', array( $this, 'feedback_notice' ) );
		add_filter( 'geodir_main_query_posts_where', array( $this, 'main_query_posts_where' ), 11, 3 );
		add_filter( 'geodir_rest_posts_clauses_where', array( $this, 'rest_posts_where' ), 11, 3 );
		add_filter( 'geodir_filter_widget_listings_where', array( $this, 'widget_posts_where' ), 11, 2 );
		add_filter( 'geodir_mail_content', array( $this, 'check_mail_content' ), 21, 5 );
		add_filter( 'geodir_skip_email_send', array( $this, 'room_skip_email_send' ), 21, 3 );
		add_filter( 'geodir_cp_search_posts_query_where', array( $this, 'cp_search_posts_query_where' ), 21, 4 );
		add_filter( 'geodir_uwp_listings_count_sql', array( $this, 'uwp_listings_count_sql' ), 11, 3 );
		add_filter( 'geodir_uwp_favorite_count_sql', array( $this, 'uwp_favorite_count_sql' ), 11, 3 );

		add_filter( 'geodir_custom_field_output_text_var_gdbprice', array( $this, 'display_nightly_price' ), 10, 4 );
		add_action( 'geodir_search_form_inputs', array( $this, 'geodir_render_search_dates' ), 30 );
		add_filter( 'geodir_booking_setup_bookings_ids', array( __CLASS__, 'filter_setup_bookings_ids' ), 10, 1 );

		add_action( 'wp', array( $this, 'redirect_room_post' ), 11 );

		// Dequeue Elementor's Flatpickr.
		add_action( 'elementor/frontend/after_enqueue_scripts', array( $this, 'dequeue_elementor_flatpickr' ) );

		// add some js texts
		add_filter( 'geodir_params', array( $this, 'js_params' ) );
	}

	/**
	 * Class autoloader
	 *
	 * @param       string $class_name The name of the class to load.
	 * @access      public
	 * @since       1.0.0
	 * @return      void
	 */
	public function autoload( $class_name ) {
		// Normalize the class name...
		$class_name = strtolower( $class_name );

		// ... and make sure it is our class.
		if ( false === strpos( $class_name, 'geodir_booking_' ) ) {
			return;
		}

		// Next, prepare the file name from the class.
		$file_name = 'class-' . str_replace( '_', '-', $class_name ) . '.php';

		// Base path of the classes.
		$plugin_path = untrailingslashit( GEODIR_BOOKING_DIR );

		// And an array of possible locations in order of importance.
		$locations = array(
			"$plugin_path/includes",
			"$plugin_path/includes/admin",
			"$plugin_path/includes/tables",
			"$plugin_path/includes/crons",
			"$plugin_path/includes/background-processes",
			"$plugin_path/includes/ical",
		);

		foreach ( $locations as $location ) {
			if ( file_exists( trailingslashit( $location ) . $file_name ) ) {
				include trailingslashit( $location ) . $file_name;
				break;
			}
		}
	}


	public function feedback_notice() {
		global $pagenow;

		// Check if we're on the settings page of your plugin
		if ( $pagenow == 'admin.php' && isset( $_GET['page'] ) && ( $_GET['page'] == 'geodir-booking' || $_GET['page'] == 'geodir-booking-settings' ) ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo '<div class="notice bsui is-dismissible" style="background: none;border: none;padding: 1px 0px;box-shadow: none;margin: 3px 0;">';
			echo aui()->alert(
				array(
					'type'    => 'info',
					'content' => sprintf( esc_html__( 'Make GeoDirectory Bookings better by suggesting improvements: %1$s Give Feedback %2$s', 'geodir-booking' ), '<a href="' . esc_url( 'https://www.facebook.com/groups/1439926262872845' ) . '" target="_blank" class="badge bg-primary text-white text-decoration-none">', '</a>' ),
				)
			);
			echo '</div>';
		}
	}

	/**
	 * Add some JS params that can be used in our JS.
	 *
	 * @param $params
	 *
	 * @return mixed
	 */
	public function js_params( $params ) {
		$params['booking_confirm_cancel_customer'] = __( 'Are you sure you wish to cancel this booking, it may incur a charge depending on the refund policy?', 'geodir-booking' );
		$params['booking_confirm_cancel_owner']    = __( 'Are you sure you wish to cancel this booking, it may incur a charge for card processing fees?', 'geodir-booking' );
		$params['booking_confirm_delete']          = __( 'Are you sure you want to delete this booking?', 'geodir-booking' );
		$params['booking_delete_error_title']      = __( 'Cannot Delete Booking', 'geodir-booking' );
		$params['booking_delete_error_message']    = __( 'An unexpected error occurred. Please try again.', 'geodir-booking' );
		$params['booking_txt_go_back']             = __( 'Go Back', 'geodir-booking' );

		return $params;
	}

	/**
	 * When WP has loaded all plugins, trigger the `hizzle_pay_loaded` hook.
	 *
	 * @since 1.0.0
	 */
	public function on_plugins_loaded() {

		// Init the REST API.
		$this->rest_api = new Geodir_Booking_Rest();

		// Init the admin class.
		$this->admin = new GeoDir_Booking_Admin();

		// Init the booking search handler.
		$this->booking_search = new GeoDir_Booking_Search();

		// Init the emails class.
		$this->emails = new GeoDir_Booking_Emails();

		// Init the ajax handler.
		$this->ajax = GeoDir_Booking_Ajax::instance();

		// Init the cron handler.
		$this->cron = GeoDir_Booking_CRON::instance();

		// Init the ical feed.
		$this->ical_feed = new GeoDir_Booking_Ical_Feed();

		// Init the ical sync.
		$this->ical_sync = new GeoDir_Booking_Background_Sync();

		// Init the queued sync.
		$this->queued_sync = new GeoDir_Booking_Queued_Sync( $this->ical_sync );

		// Init the ical uploader.
		$this->ical_uploader = GeoDir_Booking_Background_Uploader::instance();

		if ( is_admin() ) {
			// Init the ical import sync.
			$this->ical_import_page = GeoDir_Booking_Ical_Import_Page::instance();

			// Init the sync status page.
			$this->sync_status_page = GeoDir_Booking_Sync_Status_Page::instance();

			// Init the add booking page.
			$this->add_booking_page = GeoDir_Booking_Add_Booking_Page::instance();
		}

		// Maybe install.
		$this->maybe_install();

		do_action( 'geodir_booking_loaded' );
	}

	/**
	 * Installs the plugin.
	 *
	 * @param array $tabs
	 */
	public function maybe_install() {
		// Maybe upgrade the database.
		if ( get_option( 'geodir_booking_db_version' ) != 5 ) {

			// Init the db installer/updater.
			new GeoDir_Booking_Installer( (int) get_option( 'geodir_booking_db_version' ) );
			update_option( 'geodir_booking_db_version', 5 );

		}
	}

	/**
	 * Registers widgets.
	 *
	 */
	public function register_widgets( $widgets ) {

		require_once plugin_dir_path( __FILE__ ) . 'widgets/booking.php';
		require_once plugin_dir_path( __FILE__ ) . 'widgets/availability.php';
		require_once plugin_dir_path( __FILE__ ) . 'widgets/calendar-button.php';
		require_once plugin_dir_path( __FILE__ ) . 'widgets/customer-bookings.php';
		require_once plugin_dir_path( __FILE__ ) . 'widgets/owner-bookings.php';

		$widgets[] = 'GeoDir_Booking_Calendar_Button_Widget';
		$widgets[] = 'GeoDir_Booking_Availability_Widget';
		$widgets[] = 'GeoDir_Booking_Widget';
		$widgets[] = 'GeoDir_Customer_Bookings_Widget';
		$widgets[] = 'GeoDir_Owner_Bookings_Widget';
		return $widgets;
	}

	/**
	 * Loads scripts.
	 */
	public static function maybe_load_scripts() {
		/**@var WP_Locale $wp_locale */
		global $wp_locale;

		$script_version = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? time() : GEODIR_BOOKING_VERSION;
		$vue            = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? 'vue.js' : 'vue.min.js';

		wp_register_script( 'vue', plugin_dir_url( GEODIR_BOOKING_FILE ) . 'assets/js/' . $vue, array(), '3.3.4', true );

		if ( ! empty( $GLOBALS['geodir_booking_load_bookings_modal'] ) ) {
			$url          = plugin_dir_url( GEODIR_BOOKING_FILE ) . 'assets/js/calendar.js';
			$dependancies = array( 'jquery', 'vue', 'wp-api-fetch' );

			wp_enqueue_script( 'geodir-booking-calendar', $url, $dependancies, $script_version, true );

			$query_args = array(
				'post_type'               => empty( $GLOBALS['geodir_booking_rendered_post_types'] ) ? geodir_get_posttypes() : array_unique( $GLOBALS['geodir_booking_rendered_post_types'] ),
				'post_status'             => 'publish',
				'author'                  => get_current_user_id(),
				'fields'                  => 'ids',
				'numberposts'             => 25,
				'gd_setup_bookings_query' => true,
				'suppress_filters'        => false
			);

			$query_args = apply_filters( 'geodir_booking_setup_bookings_ids_query_args', $query_args );

			// Set filter.
			self::add_filters_setup_bookings_query( $query_args );

			// Fetch listing ids.
			$listing_ids = get_posts( $query_args );

			// Unset filter.
			self::remove_filters_setup_bookings_query( $query_args );

			// Make sure rendered listings are included.
			if ( ! empty( $GLOBALS['geodir_booking_rendered_listings'] ) ) {
				$listing_ids = array_merge( wp_parse_id_list( $GLOBALS['geodir_booking_rendered_listings'] ), $listing_ids );
			}

			if ( ! empty( $listing_ids ) ) {
				$listing_ids = array_unique( $listing_ids );
			}

			$listing_ids = apply_filters( 'geodir_booking_setup_bookings_ids', $listing_ids );

			$selected = (int) get_the_ID();
			$prepared = array();

			foreach ( $listing_ids as $listing_id ) {
				$listing_id = geodir_booking_post_id( $listing_id );
				$rooms = geodir_get_listing_rooms( $listing_id );

				if ( empty( $rooms ) ) {
					$prepared[] = $listing_id;
				} else {
					if ( $selected === $listing_id ) {
						$selected = current( $rooms );
					}

					$prepared = array_merge( $prepared, $rooms );
				}
			}

			// Fetch listing info.
			$listings = array_filter( array_map( 'geodir_get_post_info', array_unique( $prepared ) ) );

			if ( empty( $selected ) || ( ! in_array( $selected, $listing_ids, true ) && ! in_array( $selected, $prepared, true ) ) ) {
				$selected = current( $listing_ids );
			}

			wp_localize_script(
				'geodir-booking-calendar',
				'GD_Booking_Calendar',
				array(
					'data' => array(
						'ajax_url'           => admin_url( 'admin-ajax.php' ),
						'is_single_listing'  => true,
						'listings'           => self::prepare_listing( $listings ),
						'listing_id'         => (int) geodir_booking_post_id( $selected ),
						'orig_listing_id'    => (int) $selected,
						'ical_sync_status'   => '',
						'show_ical_status'   => false,
						'ical_status_i18n'   => __( 'iCalendar Sync Status', 'geodir-booking' ),
						'total_listings'     => count( $listings ),
						'days'               => array_values( $wp_locale->weekday_abbrev ), // 0 === Sunday.
						'months'             => array_values( $wp_locale->month ), // 0 === January.
						'today'              => current_time( 'Y-n-d' ),
						'current_month_year' => gmdate( 'Y-n' ),
						'rulesets'           => array(),
						'price_format'       => html_entity_decode( getpaid_get_price_format() ),
						'currency_symbol'    => html_entity_decode( wpinv_currency_symbol() ),
						'decimal_places'     => wpinv_decimals(),
						'isFullScreen'       => false,
						'isModal'            => false,
						'selected_days'      => array(),
						'selectedDaySaving'  => false,
						'selectedDayError'   => false,
						'selectedDaySaved'   => false,
						'edit_mode'          => false,
						'night_min_price'    => geodir_booking_night_min_price()
					),
				)
			);

		}

		if ( ! empty( $GLOBALS['geodir_booking_load_setup_modal'] ) ) {
			geodir_get_template( 'setup-booking-modal.php', array(), 'geodir-booking', plugin_dir_path( GEODIR_BOOKING_FILE ) . 'templates' );
		}

		if ( ! empty( $GLOBALS['geodir_booking_load_ical_sync_status_modal'] ) ) {
			geodir_get_template( 'ical-sync-status-modal.php', array(), 'geodir-booking', plugin_dir_path( GEODIR_BOOKING_FILE ) . 'templates' );
		}

		// View bookings app.
		if ( ! empty( $GLOBALS['geodir_booking_load_bookings_modal'] ) ) {
			// Load flatpickr.
			$aui_settings = AyeCode_UI_Settings::instance();
			$aui_settings->enqueue_flatpickr();

			// Load modal.
			geodir_get_template( 'view-bookings-modal.php', array(), 'geodir-booking', plugin_dir_path( GEODIR_BOOKING_FILE ) . 'templates' );

			// Load our script.
			wp_enqueue_script( 'geodir-view-bookings', plugin_dir_url( GEODIR_BOOKING_FILE ) . 'assets/js/view-bookings.js', array( 'jquery', 'vue', 'wp-api-fetch', 'wp-i18n' ), $script_version, true );

			// Localize the script.
			wp_localize_script(
				'geodir-view-bookings',
				'GD_Booking_View_Bookings',
				array(
					'daysi18n'        => array_values( $wp_locale->weekday ), // 0 === Sunday.
					'nonce'           => wp_create_nonce( 'gd_booking_view_bookings' ),
					'price_format'    => getpaid_get_price_format(),
					'currency_symbol' => wpinv_currency_symbol(),
					'decimal_places'  => wpinv_decimals(),
					'night_min_price' => geodir_booking_night_min_price()
				)
			);
		}

		// Availability app.
		if ( ! empty( $GLOBALS['geodir_booking_load_availability_app'] ) ) {
			wp_enqueue_script( 'geodir-booking-availability', plugin_dir_url( GEODIR_BOOKING_FILE ) . 'assets/js/booking-availability.js', array( 'jquery', 'vue' ), $script_version, true );
		}

		// Booking form.
		if ( ! empty( $GLOBALS['geodir_booking_load_booking_form_app'] ) ) {

			// Load flatpickr.
			$aui_settings = AyeCode_UI_Settings::instance();
			$aui_settings->enqueue_flatpickr();

			wp_enqueue_script( 'geodir-booking-form', plugin_dir_url( GEODIR_BOOKING_FILE ) . 'assets/js/booking-form.js', array( 'jquery', 'vue', 'wp-i18n', 'wp-api-fetch' ), $script_version, true );

			wp_localize_script(
				'geodir-booking-form',
				'Geodir_Booking_Form',
				array(
					'days'                => array_values( $wp_locale->weekday_abbrev ), // 0 === Sunday.
					'months'              => array_values( $wp_locale->month ), // 0 === January.
					'today'               => current_time( 'Y-n-d' ),
					'current_month_year'  => gmdate( 'Y-n' ),
					'price_format'        => html_entity_decode( getpaid_get_price_format() ),
					'currency_symbol'     => html_entity_decode( wpinv_currency_symbol() ),
					'thousands_separator' => html_entity_decode( wpinv_thousands_separator() ),
					'decimal_separator'   => html_entity_decode( wpinv_decimal_separator() ),
					'decimal_places'      => wpinv_decimals(),
					'night_min_price'     => geodir_booking_night_min_price()
				)
			);
		}

		// Book now app.
		if ( ! empty( $GLOBALS['geodir_booking_load_book_now_modal'] ) ) {

			// Load flatpickr.
			$aui_settings = AyeCode_UI_Settings::instance();
			$aui_settings->enqueue_flatpickr();

			// Load our modal.
			geodir_get_template( 'book-now-modal.php', array(), 'geodir-booking', plugin_dir_path( GEODIR_BOOKING_FILE ) . 'templates' );

			// Load our script.
			wp_enqueue_script( 'geodir-book-now', plugin_dir_url( GEODIR_BOOKING_FILE ) . 'assets/js/book-now.js', array( 'jquery', 'vue', 'wp-api-fetch', 'wp-i18n' ), $script_version, true );

			// Localize the script.
			$current_user = wp_get_current_user();
			wp_localize_script(
				'geodir-book-now',
				'GD_Booking_BookNow',
				array(
					'daysi18n'        => array_values( $wp_locale->weekday ), // 0 === Sunday.
					'nonce'           => wp_create_nonce( 'gd_booking_process_booking' ),
					'name'            => empty( $current_user->display_name ) ? '' : $current_user->display_name,
					'email'           => empty( $current_user->user_email ) ? '' : $current_user->user_email,
					'encrypted_email' => empty( $current_user->user_email ) ? '' : geodir_booking_encrypt( $current_user->user_email ),
				)
			);
		}

		// Customer bookings app.
		if ( ! empty( $GLOBALS['geodir_booking_load_customer_bookings_modal'] ) ) {

			// Load modal.
			geodir_get_template( 'view-customer-bookings-modal.php', array(), 'geodir-booking', plugin_dir_path( GEODIR_BOOKING_FILE ) . 'templates' );

			// Load our script.
			wp_enqueue_script( 'geodir-view-customer-bookings', plugin_dir_url( GEODIR_BOOKING_FILE ) . 'assets/js/view-customer-bookings.js', array( 'jquery', 'vue', 'wp-api-fetch', 'wp-i18n' ), $script_version, true );

			// Localize the script.
			wp_localize_script(
				'geodir-view-customer-bookings',
				'GD_Booking_View_Customer_Bookings',
				array(
					'table_cols' => array(
						'booking-number' => '#',
						'status'         => __( 'Status', 'geodir-booking' ),
						'check_in'       => __( 'Check-in', 'geodir-booking' ),
						'check_out'      => __( 'Checkout', 'geodir-booking' ),
						'payable_amount' => __( 'Booking Amount', 'geodir-booking' ),
						'service_fee'    => __( 'Service Fee', 'geodir-booking' ),
					),
				)
			);

		}

		// All customer bookings app.
		if ( ! empty( $GLOBALS['geodir_booking_load_all_customer_bookings_script'] ) ) {
			wp_enqueue_script( 'geodir-all-customer-bookings', plugin_dir_url( GEODIR_BOOKING_FILE ) . 'assets/js/all-customer-bookings.js', array( 'jquery', 'vue', 'wp-api-fetch' ), $script_version, true );
		}

		// All owner bookings app.
		if ( ! empty( $GLOBALS['geodir_booking_load_all_owner_bookings_script'] ) ) {
			wp_enqueue_script( 'geodir-all-owner-bookings', plugin_dir_url( GEODIR_BOOKING_FILE ) . 'assets/js/all-owner-bookings.js', array( 'jquery', 'vue', 'wp-api-fetch' ), $script_version, true );

			wp_localize_script(
				'geodir-all-owner-bookings',
				'GD_Booking_All_Owner_Bookings',
				array(
					'daysi18n'        => array_values( $wp_locale->weekday ), // 0 === Sunday.
					'nonces'          => array(
						'process_booking' => wp_create_nonce( 'gd_booking_process_booking' ),
						'view_bookings'   => wp_create_nonce( 'gd_booking_view_bookings' ),
					),
					'price_format'    => getpaid_get_price_format(),
					'currency_symbol' => wpinv_currency_symbol(),
					'decimal_places'  => wpinv_decimals(),
					'night_min_price' => geodir_booking_night_min_price(),
					'i18n'            => array(
						'unexpected_error' => __( 'An unexpected error occurred. Please try again.', 'geodir-booking' ),
					),
				)
			);
		}
	}

	/**
	 * Dequeues Elementor's Flatpickr library and replaces it with AUI version to avoid conflicts.
	 */
	public function dequeue_elementor_flatpickr() {
		if (
			( isset( $GLOBALS['geodir_booking_load_book_now_modal'] ) && ! empty( $GLOBALS['geodir_booking_load_book_now_modal'] ) ) ||
			( isset( $GLOBALS['geodir_booking_load_booking_form_app'] ) && ! empty( $GLOBALS['geodir_booking_load_booking_form_app'] ) ) ||
			( isset( $GLOBALS['geodir_booking_load_bookings_modal'] ) && ! empty( $GLOBALS['geodir_booking_load_bookings_modal'] ) )
		) {
			if ( wp_style_is( 'flatpickr', 'enqueued' ) ) {
				// Dequeue and deregister Elementor's Flatpickr CSS/JS.
				wp_dequeue_style( 'flatpickr' );
				wp_deregister_style( 'flatpickr' );

				wp_dequeue_script( 'flatpickr' );
				wp_deregister_script( 'flatpickr' );

				// Retrieve AyeCode UI settings.
				$aui_settings = AyeCode_UI_Settings::instance();
				$settings     = $aui_settings->get_settings();
				$bs_ver       = $settings['bs_ver'] == '5' ? '-v5' : '';

				wp_register_style( 'flatpickr', $aui_settings->get_url() . 'assets' . $bs_ver . '/css/flatpickr.min.css', array(), $aui_settings->version );
				wp_register_script( 'flatpickr', $aui_settings->get_url() . 'assets/js/flatpickr.min.js', array(), $aui_settings->version );

				$aui_settings->enqueue_flatpickr();
			}
		}
	}

	/**
	 * Prepares a listing.
	 *
	 * @param WP_Post[] $_listings Listings.
	 */
	public static function prepare_listing( $_listings ) {
		$listings = array();

		foreach ( $_listings as $listing ) {
			$parent = empty( $listing->post_parent ) ? $listing->ID : $listing->post_parent;

			// external iCal urls.
			$sync_urls = GeoDir_Booking_Sync_Urls::instance()->get_urls( $listing->ID );
			$sync_urls = array_values( $sync_urls );

			// listing iCal export URL.
			$ics_url = add_query_arg(
				array(
					'feed'       => 'gdbooking.ics',
					'listing_id' => $listing->ID,
				),
				site_url( '/' )
			);

			$bookings = geodir_get_bookings(
				array(
					'listings'  => array( (int) $listing->ID ),
					'status_in' => array(
						'pending_payment',
						'pending_confirmation',
						'confirmed',
						'completed',
					),
				)
			);

			$booked_dates = static::get_booked_dates( $listing->ID );
			$day_rules    = static::get_day_rules( $listing->ID );

			$formatted_day_rules = array();
			foreach ( $day_rules as $day_rule ) {
				if ( ! empty( $day_rule->uid ) ) {
					if ( ! isset( $formatted_day_rules[ $day_rule->uid ] ) ) {
						$formatted_day = geodir_booking_date( $day_rule->rule_date, 'view_day' );

						$formatted_day_rules[ $day_rule->uid ]                     = $day_rule;
						$formatted_day_rules[ $day_rule->uid ]->checkin_date       = $day_rule->rule_date;
						$formatted_day_rules[ $day_rule->uid ]->checkin_formatted  = $formatted_day;
						$formatted_day_rules[ $day_rule->uid ]->checkout_date      = $day_rule->rule_date;
						$formatted_day_rules[ $day_rule->uid ]->checkout_formatted = $formatted_day;
					} else {
						$formatted_day_rules[ $day_rule->uid ]->checkin_date  = min( $formatted_day_rules[ $day_rule->uid ]->rule_date, $day_rule->rule_date );
						$formatted_day_rules[ $day_rule->uid ]->checkout_date = max( $formatted_day_rules[ $day_rule->uid ]->rule_date, $day_rule->rule_date );

						$formatted_day_rules[ $day_rule->uid ]->checkin_formatted  = geodir_booking_date( $formatted_day_rules[ $day_rule->uid ]->checkin_date, 'view_day' );
						$formatted_day_rules[ $day_rule->uid ]->checkout_formatted = geodir_booking_date( $formatted_day_rules[ $day_rule->uid ]->checkout_date, 'view_day' );

					}
				}
			}

			$booking_details = array();
			foreach ( $bookings as $booking ) {

				$checkin  = $booking->get_check_in_date();
				$checkout = $booking->get_check_out_date();

				$checkin_date  = $checkin->format( 'Y-m-d' );
				$checkout_date = $checkout->format( 'Y-m-d' );

				$booking_details[ $checkin_date ] = array(
					'booking_id'         => (int) $booking->id,
					'checkin_date'       => $checkin_date,
					'checkout_date'      => $checkout_date,
					'checkin_formatted'  => geodir_booking_date( $checkin_date, 'view_day' ),
					'checkout_formatted' => geodir_booking_date( $checkout_date, 'view_day' ),
					'guest_name'         => $booking->name,
					'adults'             => (int) $booking->adults,
					'children'           => (int) $booking->children,
					'guests'             => $booking->get_guests_summary(),
					'amount'             => (float) $booking->payable_amount,
					'is_checkin_day'     => true,
				);

				$period = new DatePeriod( $checkin->modify( '+1 day' ), new DateInterval( 'P1D' ), $checkout );
				foreach ( $period as $date ) {
					$date_key = $date->format( 'Y-m-d' );

					$booking_details[ $date_key ]['checkin_date'] = $checkin_date;
				}

				$last_day = ( $checkin_date === $checkout_date ) ? $checkout_date : $checkout->modify( '-1 day' )->format( 'Y-m-d' );

				$booking_details[ $last_day ]['is_checkout_day'] = true;
			}

			uksort( $booking_details, function( $a, $b ) { return strtotime( $a ) - strtotime( $b ); } );

			foreach ( $day_rules as $day_rule ) {
				if ( isset( $formatted_day_rules[ $day_rule->uid ] ) ) {
					$booking_details[ $day_rule->rule_date ] = (array) $formatted_day_rules[ $day_rule->uid ];
				}
			}

			$prepared = array(
				'id'                    => (int) $listing->ID,
				'ID'                    => (int) $listing->ID,
				'post_type'             => $listing->post_type,
				'post_title'            => $listing->post_title,
				'guid'                  => geodir_get_listing_url( $listing->ID ),
				'featured_image'        => get_the_post_thumbnail_url( $parent, 'thumbnail' ),
				'ruleset'               => new GeoDir_Booking_Ruleset( 0, $listing->ID ),
				'day_rules'             => $day_rules,
				'booked_dates'          => $booked_dates,
				'booking_details'       => $booking_details,
				'is_pets_enabled'       => ! empty( $listing->property_pets ) ? true : false,
				'is_max_guests_enabled' => ! empty( $listing->property_guests ) ? true : false,
				'is_syncing'            => false,
				'is_ical_edited'        => false,
				'sync_urls'             => $sync_urls,
				'ics_url'               => $ics_url,
				'editing_title'         => false,
				'new_title'             => '',
			);

			$listings[] = (array) $prepared;
		}

		return $listings;
	}

	/**
	 * Get day rules.
	 *
	 * @param int $listing_id Listing ID.
	 * @return GeoDir_Booking_Day_Rule[]
	 */
	public static function get_day_rules( $listing_id ) {
		global $wpdb;

		$listing_id = geodir_booking_post_id( $listing_id );

		$rules = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}gdbc_day_rules WHERE listing_id = %d", $listing_id ), ARRAY_A );

		$day_rules = array();
		if ( is_array( $rules ) ) {
			foreach ( $rules as $rule ) {
				$day_rules[] = new GeoDir_Booking_Day_Rule( $rule );
			}
		}

		return $day_rules;
	}

	/**
	 * Get booked dates.
	 *
	 * @param int $listing_id Listing ID.
	 * @return array
	 */
	public static function get_booked_dates( $listing_id ) {
		global $wpdb;

		$listing_id = geodir_booking_post_id( $listing_id );

		$availability = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}gdbc_availability WHERE post_id = %d AND year > %d", $listing_id, gmdate( 'Y' ) - 1 ), ARRAY_A );
		$bookings     = array();

		foreach ( $availability as $availability_item ) {
			$year      = (int) $availability_item['year'];
			$first_day = strtotime( "{$year}-01-01" );

			// Loop through all the days in the year and add them to the disabled array.
			for ( $i = 1; $i <= 366; $i++ ) {
				if ( ! empty( $availability_item[ "d{$i}" ] ) ) {
					$bookings[ date( 'Y-m-d', strtotime( "+{$i} days", $first_day ) - DAY_IN_SECONDS ) ] = (int) $availability_item[ "d{$i}" ];
				}
			}
		}

		return $bookings;
	}

	/**
	 * Get disabled dates.
	 *
	 * @param int $listing_id Listing ID.
	 * @return string[]
	 */
	public static function get_disabled_dates( $listing_id ) {
		global $wpdb;

		$listing_id = geodir_booking_post_id( $listing_id );

		// Fetch availability data for the listing
		$availability = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}gdbc_availability WHERE post_id = %d AND year > %d",
				$listing_id,
				gmdate( 'Y' ) - 1
			),
			ARRAY_A
		);

		$disabled = array();

		foreach ( $availability as $availability_item ) {
			$year      = (int) $availability_item['year'];
			$first_day = strtotime( "{$year}-01-01" );

			// Loop through all the days in the year
			for ( $i = 1; $i <= 366; $i++ ) {
				// Only process booked dates
				if ( null !== $availability_item[ "d{$i}" ] ) {
					$current_date = strtotime( "+" . ( $i - 1 ) . " days", $first_day );

					// Add the current date to disabled array (check-in day)
					$disabled[] = date( 'Y-m-d', $current_date );

					// Check if the next day is also booked; if not, treat current day as checkout day
					if ( ! isset( $availability_item[ 'd' . ( $i + 1 ) ] ) || null === $availability_item[ 'd' . ( $i + 1 ) ] ) {
						// Make the checkout day available by skipping it
						//$disabled = array_diff( $disabled, array( date( 'Y-m-d', $current_date ) ) ); // Causes issue when booking falls with dates 2 different years. Ex: 2025-12-28 to 2026-01-03
					}
				}
			}
		}

		return $disabled;
	}

	/**
	 * Booking action.
	 */
	public function geodir_booking_action() {

		if ( isset( $_REQUEST['geodir_booking_action'] ) ) {
			do_action( 'geodir_booking_action_' . $_REQUEST['geodir_booking_action'] );
		}
	}

	/**
	 * Render date search fields for Geodirectory and store them in local storage.
	 */
	public function geodir_render_search_dates() {
		$html = '<script type="text/javascript">';

		// Store guest data in local storage
		$guest_types = array(
			'adults'   => 'gd_booking_adults',
			'children' => 'gd_booking_children',
			'infants'  => 'gd_booking_infants',
			'pets'     => 'gd_booking_pets',
		);

		foreach ( $guest_types as $guest_key => $storage_key ) {
			if ( isset( $_REQUEST[ $guest_key ] ) && ! empty( $_REQUEST[ $guest_key ] ) ) {
				$guest_count = absint( $_REQUEST[ $guest_key ] );
				$html       .= 'localStorage.setItem("' . esc_js( $storage_key ) . '", ' . json_encode( $guest_count ) . ');';
			}
		}

		// Store booking dates in local storage
		if ( isset( $_REQUEST['gdbdate'] ) && ! empty( $_REQUEST['gdbdate'] ) ) {
			$dates = geodir_search_sanitize_date_range( $_REQUEST['gdbdate'] );

			if ( isset( $dates['start'] ) && ! empty( $dates['start'] ) ) {
				$start_date = esc_js( $dates['start'] );
				$html      .= 'localStorage.setItem("gd_booking_start_date", ' . json_encode( $start_date ) . ');';
			}

			if ( isset( $dates['end'] ) && ! empty( $dates['end'] ) ) {
				$end_date = esc_js( $dates['end'] );
				$html    .= 'localStorage.setItem("gd_booking_end_date", ' . json_encode( $end_date ) . ');';
			}
		}

		$html .= '</script>';

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $html;
	}


	/**
	 * Output for nightly price and dynamic price.
	 *
	 * @since 2.6.0.1
	 *
	 * @param string $html HTML output.
	 * @param string $location Field location.
	 * @param array $cf Custom field.
	 * @param array $output Output type.
	 * @return string Output for nightly price.
	 */
	public function display_nightly_price( $html, $location, $cf, $output ) {
		global $gd_post, $aui_bs5;

		if ( isset( $_REQUEST['gdbdate'] ) && ! empty( $_REQUEST['gdbdate'] ) && ! empty( $gd_post ) && ! empty( $gd_post->{$cf['htmlvar_name']} ) ) {

			$dates    = geodir_search_sanitize_date_range( $_REQUEST['gdbdate'] );
			$adults   = isset( $_REQUEST['adults'] ) ? absint( $_REQUEST['adults'] ) : 0;
			$children = isset( $_REQUEST['children'] ) ? absint( $_REQUEST['children'] ) : 0;
			$pets     = isset( $_REQUEST['sproperty_pets'] ) ? 1 : 0;

			$guests = ( $adults + $children );
			$adults = 0 === $guests ? 1 : $adults;

			if ( isset( $dates['start'], $dates['end'] ) ) {
				$rooms      = geodir_get_listing_rooms( $gd_post->ID );
				$listing_id = empty( $rooms ) ? $gd_post->ID : $rooms[0];

				$booking = new GeoDir_Customer_Booking();
				$booking->set_args(
					array(
						'start_date' => $dates['start'],
						'end_date'   => $dates['end'],
						'listing_id' => $listing_id,
						'guests'     => $guests,
						'adults'     => $adults,
						'children'   => true === (bool) $gd_post->property_infants ? $children : 0,
						'pets'       => $pets,
					)
				);
				$booking->calculate_prices();
				$total_payable_amount = (float) $booking->payable_amount;

				// Calculate total and average nightly prices.
				$total_days_amount = array_sum( array_values( $booking->date_amounts ) );

				if ( ( 0 === count( $booking->date_amounts ) ) ) {
					$ruleset         = new GeoDir_Booking_Ruleset( 0, $listing_id );
					$avg_night_price = ( $ruleset->nightly_price ) ? $ruleset->nightly_price : $gd_post->gdbprice;
				} else {
					$avg_night_price = $total_days_amount / count( $booking->date_amounts );
				}

				$price_breakdown = '<ul class="p-0 m-0">';

				$price_breakdown .= sprintf(
					'<li class="d-flex flex-wrap justify-content-between align-items-center">
                        <span class="%s">%s</span>
                        <span class="text-dark %s">%s</span>
                    </li>',
					$aui_bs5 ? 'fw-semibold' : 'font-weight-bold',
					wp_kses_post( wpinv_price( $avg_night_price ) . ' x ' . count( $booking->date_amounts ) ) . ' ' . _n( 'night', 'nights', count( $booking->date_amounts ), 'geodir-booking' ),
					$aui_bs5 ? 'fw-semibold' : 'font-weight-bold',
					wp_kses_post( wpinv_price( $total_days_amount ) )
				);

				$price_details = array(
					'extra_guest_fee'  => __( 'Extra Guests Fee', 'geodir-booking' ),
					'cleaning_fee'     => __( 'Cleaning Fee', 'geodir-booking' ),
					'pet_fee'          => __( 'Pet Fee', 'geodir-booking' ),
					'total_discount_m' => wp_sprintf( __( 'Discount (%s)', 'geodir-booking' ), $booking->discount_ge . '%' ),
					'service_fee'      => __( 'Service Fee', 'geodir-booking' ),
					'payable_amount'   => __( 'Total', 'geodir-booking' ),
				);

				foreach ( $price_details as $price_key => $price_label ) {
					if ( ! empty( $booking->{$price_key} ) ) {
						$price_breakdown .= sprintf(
							'<li class="d-flex flex-wrap justify-content-between align-items-center">
                                <span class="%s">%s</span>
                                <span class="text-dark %s">%s</span>
                            </li>',
							$aui_bs5 ? 'fw-semibold' : 'font-weight-bold',
							esc_html( $price_label ),
							$aui_bs5 ? 'fw-semibold' : 'font-weight-bold',
							wp_kses_post( wpinv_price( (float) $booking->{$price_key} ) )
						);
					}
				}
				$price_breakdown .= '</ul>';

				$html  = '<div class="geodir_post_meta ' . $cf['css_class'] . ' geodir-field-' . $cf['htmlvar_name'] . '">';
				$html .= '<span class="text-dark ' . ( $aui_bs5 ? 'fw-semibold' : 'font-weight-bold' ) . '">' . wp_kses_post( wpinv_price( $avg_night_price ) ) . '</span>&nbsp;';
				$html .= '<span class="text-dark">' . __( 'night', 'geodir-booking' ) . '</span>&nbsp;&bull;&nbsp;';
				$html .= '<span data-bs-toggle="popover" data-bs-placement="bottom" data-bs-custom-class="w-100" title="' . __( 'Price Breakdown', 'geodir-booking' ) . '" data-bs-content="' . esc_html( $price_breakdown ) . '" data-bs-html="true" role="button" class="text-decoration-underline ' . ( $aui_bs5 ? 'fw-semibold' : 'font-weight-bold' ) . '">' . wp_kses_post( wpinv_price( $total_payable_amount ) ) . '&nbsp;' . __( 'total', 'geodir-booking' ) . '</span>';
				$html .= '</div>';
			}
		}

		return $html;
	}

	/**
	 * Maybe confirm bookings after an invoice has been paid.
	 *
	 * @param WPInv_Invoice $invoice
	 *
	 * @since 1.0.0
	 */
	public function invoice_paid( $invoice ) {

		if ( $invoice->get_meta( '_gd_booking_id' ) ) {
			$booking = new GeoDir_Customer_Booking( $invoice->get_meta( '_gd_booking_id' ) );

			if ( $booking->exists() ) {
				$booking->confirm();
			}
		}
	}

	/**
	 * Maybe cancel bookings after an invoice has been paid.
	 *
	 * @param WPInv_Invoice $invoice
	 *
	 * @since 1.0.0
	 */
	public function invoice_cancelled( $invoice ) {

		if ( $invoice->get_meta( '_gd_booking_id' ) ) {
			$booking = new GeoDir_Customer_Booking( $invoice->get_meta( '_gd_booking_id' ) );

			if ( $booking->exists() ) {
				$booking->cancel();
			}
		}
	}

	/**
	 * Skip new invoice emails for bookings.
	 *
	 * @param bool $skip
	 * @param int $invoice_id
	 */
	public function skip_invoice_email( $skip, $invoice_id ) {

		if ( 'booking' === get_post_meta( $invoice_id, 'wpinv_created_via', true ) ) {
			return true;
		}

		return $skip;
	}

	/**
	 * Filters the taxable amount.
	 *
	 * @param float $taxable_amount
	 * @param GetPaid_Form_Item $item
	 * @return float
	 */
	public function filter_getpaid_taxable_amount( $taxable_amount, $item ) {

		// Abort if we're taxing booking amount only...
		if ( 'default' === geodir_booking_get_option( 'tax_behaviour', 'default' ) ) {
			return $taxable_amount;
		}

		// ... or this is not a booking item.
		if ( geodir_booking_item() !== $item->get_id() ) {
			return $taxable_amount;
		}

		$service_fee = 0;
		$commission  = 0;

		// Check if we have service fee and commission cached.
		if ( ! empty( $GLOBALS['geodir_booking_fees'] ) ) {
			list( $service_fee, $commission ) = $GLOBALS['geodir_booking_fees'];
		} elseif ( ! empty( $item->invoice_id ) && get_post_meta( $item->invoice_id, '_gd_booking_id', true ) ) { // Get booking ID from the invoice.
			$booking = new GeoDir_Customer_Booking( get_post_meta( $item->invoice_id, '_gd_booking_id', true ) );

			if ( $booking->exists() ) {
				$service_fee = $booking->service_fee;
				$commission  = $booking->site_commission;
			}
		}

		if ( 'full' === geodir_booking_get_option( 'tax_behaviour', 'default' ) ) {
			return $taxable_amount + $service_fee;
		}

		return $service_fee + $commission;
	}

	/**
	 * GD post data.
	 *
	 * @param array $postarr
	 */
	public function save_post_data( $postarr, $gd_post, $post, $update = false ) {
		global $geodir_post_rooms_data;

		if ( empty( $geodir_post_rooms_data ) ) {
			$geodir_post_rooms_data = array();
		}

		if ( empty( $postarr['post_id'] ) ) {
			return $postarr;
		}

		$post_ID = $postarr['post_id'];

		if ( wp_is_post_revision( $post_ID ) ) {
			$post_ID = wp_get_post_parent_id( $post_ID );
		}

		$geodir_post_rooms_data[ $post_ID ] = $postarr;

		return $postarr;
	}

	/**
	 * Fired when a GD post is saved.
	 *
	 * @param array $data
	 */
	public function geodir_post_saved( $data ) {

		// Abort if no post.
		if ( empty( $data['post_id'] ) ) {
			return;
		}

		$post = get_post( $data['post_id'] );

		unset( $data['post_id'] );

		// Save nightly price.
		$nightly_price = 0;
		if ( array_key_exists( 'gdbprice', $data ) ) {
			$nightly_price = floatval( $data['gdbprice'] );
			$ruleset       = new GeoDir_Booking_Ruleset( 0, $post->ID );

			if ( $ruleset->nightly_price !== $nightly_price ) {
				$ruleset->nightly_price = $nightly_price;
				$ruleset->save();
			}
		}

		// Return if this is a child post.
		if ( ! empty( $post->post_parent ) ) {
			return;
		}

		// Create child posts for each room.
		$saved_ids   = array();
		$saved_units = geodir_get_listing_rooms( $post->ID );

		if ( isset( $data['gdb_multiple_units'] ) ) {
			$gdb_multiple_units  = ! empty( $data['gdb_multiple_units'] ) ? true : false;
			$gdb_number_of_rooms = isset( $data['gdb_number_of_rooms'] ) ? absint( $data['gdb_number_of_rooms'] ) : 0;
		} else {
			$_gd_post = geodir_get_post_info( $post->ID );

			$gdb_multiple_units  = ! empty( $_gd_post ) && ! empty( $_gd_post->gdb_multiple_units ) ? true : false;
			$gdb_number_of_rooms = ! empty( $_gd_post ) && ! empty( $_gd_post->gdb_number_of_rooms ) ? absint( $_gd_post->gdb_number_of_rooms ) : 0;
		}

		if ( ! empty( $gdb_multiple_units ) ) {
			$num_units = $gdb_number_of_rooms;

			if ( $num_units > 1 ) {
				if ( ! has_action( 'save_post', array( 'GeoDir_Post_Data', 'save_post' ) ) ) {
					add_action( 'save_post', array( 'GeoDir_Post_Data', 'save_post' ), 10, 3 );
				}

				for ( $i = 0; $i < $num_units; $i++ ) {
					if ( isset( $saved_units[ $i ] ) ) {
						wp_update_post(
							array_merge(
								$data,
								array(
									'ID'            => $saved_units[ $i ],
									'post_title'    => str_replace( '&#8211;', '-', get_the_title( (int) $saved_units[ $i ] ) ),
									'post_category' => empty( $data['post_category'] ) ? array() : wp_parse_id_list( $data['post_category'] ),
								)
							)
						);
						$saved_ids[] = (int) $saved_units[ $i ];
						continue;
					}

					$room_id = wp_insert_post(
						array_merge(
							$data,
							array(
								'post_title'    => wp_sprintf(
									// translators: %1$s is the post title, %2$d is the room number.
									__( '%1$s - Room %2$d', 'geodir-booking' ),
									get_the_title( $post->ID ),
									$i + 1
								),
								'post_type'     => $post->post_type,
								'post_author'   => $post->post_author,
								'post_status'   => $post->post_status,
								'post_parent'   => $post->ID,
								'post_category' => empty( $data['post_category'] ) ? array() : wp_parse_id_list( $data['post_category'] ),
							)
						)
					);

					if ( $room_id ) {
						$ruleset = new GeoDir_Booking_Ruleset( 0, $room_id );

						$ruleset->nightly_price = $nightly_price;
						$ruleset->save();

						$saved_ids[] = (int) $room_id;
					}
				}
			}

			// Delete extra units.
			if ( count( $saved_units ) > $num_units ) {
				foreach ( $saved_units as $saved_unit ) {
					if ( ! in_array( $saved_unit, $saved_ids ) ) {
						wp_delete_post( $saved_unit, true );
					}
				}
			}
		}

		// If this is a single unit, delete all child posts.
		if ( empty( $saved_ids ) && ! empty( $saved_units ) ) {

			$original = new GeoDir_Booking_Ruleset( 0, $post->ID );
			$ruleset  = new GeoDir_Booking_Ruleset( 0, (int) current( $saved_units ) );

			if ( $ruleset->id > 0 && empty( $original->id ) ) {
				$ruleset->listing_id = $post->ID;
				$ruleset->save();
			}

			foreach ( $saved_units as $saved_unit ) {
				wp_delete_post( $saved_unit, true );
			}
		}

		if ( empty( $saved_ids ) ) {
			delete_post_meta( $post->ID, 'gdb_rooms' );
		} else {
			update_post_meta( $post->ID, 'gdb_rooms', $saved_ids );
		}
	}

	/**
	 * Handle on GD AJAX post save function.
	 */
	public function on_ajax_post_saved( $post_data, $update = false ) {
		global $geodir_post_before, $geodir_rooms_saved, $geodir_post_rooms_data;

		if ( empty( $post_data['ID'] ) ) {
			return;
		}

		$post_ID = $post_data['ID'];

		if ( wp_is_post_revision( $post_ID ) ) {
			$post_ID = wp_get_post_parent_id( $post_ID );
		}

		if ( empty( $geodir_rooms_saved ) ) {
			$geodir_rooms_saved = array();
		}

		if ( empty( $geodir_post_before[ $post_ID ] ) || empty( $geodir_post_rooms_data[ $post_ID ] ) || ! empty( $geodir_rooms_saved[ $post_ID ] ) ) {
			return;
		}

		$_gd_post = $geodir_post_before[ $post_ID ];
		$gd_post  = geodir_get_post_info( $post_ID );

		if ( empty( $gd_post ) ) {
			return;
		}

		$gd_post_before  = array();
		$gd_post_saved   = array();
		$rooms_post_data = $geodir_post_rooms_data[ $post_ID ];

		$match_keys = array( 'post_title', 'post_status', 'gdbooking', 'post_category', 'gdbprice', 'gdb_multiple_units', 'gdb_number_of_rooms' );

		foreach ( $match_keys as $key ) {
			$gd_post_before[ $key ] = isset( $_gd_post->{$key} ) ? $_gd_post->{$key} : '';
			$gd_post_saved[ $key ]  = isset( $gd_post->{$key} ) ? $gd_post->{$key} : '';

			if ( isset( $rooms_post_data[ $key ] ) ) {
				$rooms_post_data[ $key ] = $gd_post_saved[ $key ];
			}
		}

		if ( maybe_serialize( $gd_post_before ) != maybe_serialize( $gd_post_saved ) ) {
			$geodir_rooms_saved[ $post_ID ] = $gd_post_before;
			$post                           = get_post( $post_ID );

			self::geodir_post_saved( $rooms_post_data, $gd_post, $post, $update );
		}
	}

	/**
	 * Fired when a GD post is saved.
	 *
	 * @param array $data
	 */
	public function on_after_post_save( $result, $postarr, $format, $gd_post, $post, $update ) {
		global $wpdb;

		if ( $result === 0 && ! empty( $postarr['gdbooking'] ) && ! empty( $postarr['gdb_multiple_units'] ) && $update && ! empty( $post ) && ! empty( $post->post_parent ) && $post->post_type == get_post_type( $post->post_parent ) && ! wp_is_post_revision( $post->ID ) && geodir_is_gd_post_type( $post->post_type ) ) {
			$_gd_post = geodir_get_post_info( $post->ID );

			// Create room post entry if not created.
			if ( empty( $_gd_post ) ) {
				$wpdb->insert(
					geodir_db_cpt_table( $post->post_type ),
					$postarr,
					$format
				);
			}
		}
	}

	/**
	 * Filter WP_Query to exclude child posts.
	 *
	 * @param WP_Query $query
	 */
	public function pre_get_posts( $query ) {
		if ( $query->is_main_query() || ( ! empty( $query ) && ! empty( $query->query_vars ) && ! empty( $query->query_vars['uwp_geodir_query'] ) ) ) {
			// Check if this is a GD post query.
			$post_type = wp_parse_list( $query->get( 'post_type' ) );

			if ( empty( $post_type ) && ( $_post_type = geodir_get_current_posttype() ) ) {
				$post_type = array( $_post_type );
			}

			if ( ! empty( $post_type ) ) {
				foreach ( $post_type as $type ) {
					if ( geodir_is_gd_post_type( $type ) ) {
						$query->set( 'post_parent', 0 );
						break;
					}
				}
			}
		}
	}

	public function main_query_posts_where( $where, $query, $post_type ) {
		global $wpdb;

		$where .= " AND `{$wpdb->posts}`.`post_parent` = 0 ";

		return $where;
	}

	/**
	 * REST API GD posts where clause.
	 *
	 * @since 2.0.8
	 *
	 * @param string $where Query where value.
	 * @param object $wp_query Wp_query object.
	 * @param string $post_type Post type.
	 * @return string $where.
	 */
	public static function rest_posts_where( $where, $wp_query, $post_type ) {
		global $wpdb;

		$where .= " AND `{$wpdb->posts}`.`post_parent` = 0 ";

		return $where;
	}

	public function widget_posts_where( $where, $post_type ) {
		global  $wpdb, $gd_post, $gd_query_args_widgets;

		$where .= " AND `{$wpdb->posts}`.`post_parent` = 0 ";

		return $where;
	}

	public function check_mail_content( $message, $email_name, $email_vars, $to = '', $subject = '' ) {
		if ( strpos( $email_name, 'booking' ) === false && ! empty( $email_vars ) && ( ( ! empty( $email_vars['post'] ) && is_object( $email_vars['post'] ) && ! empty( $email_vars['post']->post_parent ) ) || ( ! empty( $email_vars['gd_post'] ) && is_object( $email_vars['gd_post'] ) && ! empty( $email_vars['gd_post']->post_parent ) ) ) ) {
			$message = '';
		}

		return $message;
	}

	public function room_skip_email_send( $skip, $email_name, $email_vars ) {
		if ( $skip !== true && strpos( $email_name, 'booking' ) === false && ! empty( $email_vars ) && ( ( ! empty( $email_vars['post'] ) && is_object( $email_vars['post'] ) && ! empty( $email_vars['post']->post_parent ) ) || ( ! empty( $email_vars['gd_post'] ) && is_object( $email_vars['gd_post'] ) && ! empty( $email_vars['gd_post']->post_parent ) ) ) ) {
			$skip = true;
		}

		return $skip;
	}

	public function cp_search_posts_query_where( $where, $search, $post_type, $custom_field ) {
		$where .= ' AND `p`.`post_parent` = 0 ';

		return $where;
	}

	public function uwp_listings_count_sql( $sql, $post_type, $user_id ) {
		$sql = str_replace( ' p.post_author=' . (int) $user_id . ' ', ' p.post_author=' . (int) $user_id . ' AND `p`.`post_parent` = 0 ', $sql );

		return $sql;
	}

	public function uwp_favorite_count_sql( $sql, $post_type, $user_id ) {
		global $wpdb;

		$sql = str_replace( " AND post_type='" . $post_type . "' ", " AND post_type='" . $post_type . "' AND `" . $wpdb->posts . '`.`post_parent` = 0 ', $sql );

		return $sql;
	}

	public function redirect_room_post() {
		global $wpdb, $wp_query;

		if ( is_404() && ! empty( $wp_query ) && ! empty( $wp_query->query_vars['post_type'] ) && ! empty( $wp_query->query_vars['name'] ) && geodir_is_gd_post_type( $wp_query->query_vars['post_type'] ) ) {
			$post_parent = $wpdb->get_var( $wpdb->prepare( "SELECT `post_parent` FROM `{$wpdb->posts}` WHERE `post_name` LIKE %s ORDER BY ID ASC", sanitize_text_field( $wp_query->query_vars['name'] ) ) );

			if ( ! empty( $post_parent ) && get_post_type( $post_parent ) == $wp_query->query_vars['post_type'] ) {
				wp_redirect( get_permalink( $post_parent ), '301' );
				exit;
			}
		}
	}

	/**
	 * Apply filters to the Bookings setup query.
	 *
	 * @since 2.0.17
	 *
	 * @param array The query args.
	 */
	public static function add_filters_setup_bookings_query( $query_args = array() ) {
		add_filter( 'posts_join', array( __CLASS__, 'posts_join_setup_bookings_query' ), 10, 2 );
	}

	/**
	 * Unset filters applied to the Bookings setup query.
	 *
	 * @since 2.0.17
	 *
	 * @param array The query args.
	 */
	public static function remove_filters_setup_bookings_query( $query_args = array() ) {
		remove_filter( 'posts_join', array( __CLASS__, 'posts_join_setup_bookings_query' ), 10, 2 );
	}

	/**
	 * Apply join filter to the Bookings setup query.
	 *
	 * @since 2.0.17
	 *
	 * @param string $join The JOIN clause
	 * @param WP_Query The WP_Query object.
	 * @return The JOIN clause.
	 */
	public static function posts_join_setup_bookings_query( $join, $query ) {
		global $wpdb;

		// Check setup bookings query.
		if ( ! empty( $query ) && $query->get( 'gd_setup_bookings_query' ) && ! empty( $query->query_vars['post_type'] ) && is_array( $query->query_vars['post_type'] ) && count( $query->query_vars['post_type'] ) == 1 ) {
			$table = geodir_db_cpt_table( $query->query_vars['post_type'][0] );

			// Add INNER JOIN with GD detail table.
			if ( geodir_column_exist( $table, 'gdbooking' ) ) {
				$join .= " INNER JOIN `{$table}` ON ( {$wpdb->posts}.ID = `{$table}`.post_id AND `{$table}`.gdbooking = 1 )";
			} else {
				$join .= " INNER JOIN `{$table}` ON ( {$wpdb->posts}.ID = `{$table}`.post_id AND `{$table}`.post_id = -1 )";
			}
		}

		return $join;
	}

	/**
	 * Filter post IDs for booking.
	 *
	 * @since 2.0.17
	 *
	 * @param array $_post_ids Array of post IDs.
	 * @return array Filtered post IDs.
	 */
	public static function filter_setup_bookings_ids( $_post_ids ) {
		$post_ids = array();

		if ( ! empty( $_post_ids ) ) {
			foreach ( $_post_ids as $_post_id ) {
				if ( geodir_booking_is_enabled( (int) $_post_id ) ) {
					$post_ids[] = $_post_id;
				}
			}
		}

		return $post_ids;
	}
}
