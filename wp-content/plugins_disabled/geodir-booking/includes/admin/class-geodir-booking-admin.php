<?php
/**
 * Bookings admin class.
 *
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Bookings admin class.
 *
 */
class GeoDir_Booking_Admin {

	/**
	 * Only save metaboxes once.
	 *
	 * @var bool
	 */
	private $saved_meta_boxes = false;

	/**
	 * Class constructor.
	 *
	 */
	public function __construct() {

		add_action( 'geodir_debug_tools', array( $this, 'register_tools' ) );
		add_action( 'admin_menu', array( $this, 'admin_menu' ), 10 );
		add_filter( 'aui_screen_ids', array( $this, 'screen_ids' ) );
		add_action( 'geodir_booking_admin_before_single_booking', array( $this, 'register_metaboxes' ) );

		// Package settings.
		add_filter( 'geodir_pricing_package_settings', array( $this, 'package_settings' ), 10, 2 );
		add_filter( 'geodir_pricing_process_data_for_save', array( $this, 'save_package_settings' ), 10, 2 );

		// Listing settings.
		add_action( 'add_meta_boxes', array( $this, 'add_listings_meta_box' ) );
		add_action( 'save_post', array( $this, 'save_meta_boxes' ), 1, 2 );

		add_filter( 'geodir_custom_fields_predefined', array( __CLASS__, 'predefined_custom_field' ), 3, 2 );
		add_filter( 'geodir_filter_geodir_post_custom_fields', array( __CLASS__, 'filter_policy_options' ) );
	}

	/**
	 * Tell AyeCode UI to load on certain admin pages.
	 *
	 * @param $screen_ids
	 *
	 * @return array
	 */
	public function screen_ids( $screen_ids = array() ) {
		$screen_ids[] = 'toplevel_page_geodir-booking';
		$screen_ids[] = 'bookings_page_geodir-booking-settings';
		$screen_ids[] = 'admin_page_geodir-booking-external-ical';
		$screen_ids[] = 'admin_page_geodir-booking-add-new';

		return $screen_ids;
	}

	/**
	 * Registers tools.
	 *
	 * @param array $tools
	 * @return array
	 */
	public function register_tools( $tools ) {

		$tools['gd_booking_repair_db'] = array(
			'name'     => __( 'Repair Bookings Database', 'geodir-booking' ),
			'button'   => __( 'Repair', 'geodir-booking' ),
			'desc'     => __( 'Creates missing database tables and columns.', 'geodir-booking' ),
			'callback' => array( $this, 'repair_db' ),
		);

		return $tools;
	}

	/**
	 * Repairs the database.
	 *
	 */
	public function repair_db() {
		require_once plugin_dir_path( GEODIR_BOOKING_FILE ) . 'includes/class-geodir-booking-installer.php';

		$installer = new GeoDir_Booking_Installer( null );
		$installer->create_tables();
	}

	/**
	 * Register admin menus.
	 *
	 */
	public function admin_menu() {

		add_menu_page(
			esc_html__( 'Bookings', 'geodir-booking' ),
			esc_html__( 'Bookings', 'geodir-booking' ),
			'manage_options',
			'geodir-booking',
			null,
			'dashicons-editor-bold',
			'55.19845'
		);

		// Add the manage bookings page.
		add_submenu_page(
			'geodir-booking',
			esc_html__( 'Bookings', 'geodir-booking' ),
			esc_html__( 'Bookings', 'geodir-booking' ),
			'manage_options',
			'geodir-booking',
			array( $this, 'display_manage_bookings_page' )
		);

		// Add the add new bookings page.
		add_submenu_page(
			'none',
			esc_html__( 'Add New Booking', 'geodir-booking' ),
			esc_html__( 'Add New Booking', 'geodir-booking' ),
			'manage_options',
			'geodir-booking-add-new',
			array( $this, 'display_add_bookings_page' )
		);

		// Add the sync calendars page.
		add_submenu_page(
			'geodir-booking',
			esc_html__( 'Sync Calendars', 'geodir-booking' ),
			esc_html__( 'Sync Calendars', 'geodir-booking' ),
			'manage_options',
			'geodir-booking-ical',
			array( $this, 'display_manage_sync_calendars_page' )
		);

		// Add the edit external calendars page.
		add_submenu_page(
			'none',
			esc_html__( 'External Calendars', 'geodir-booking' ),
			esc_html__( 'External Calendars', 'geodir-booking' ),
			'manage_options',
			'geodir-booking-external-ical',
			array( $this, 'display_manage_external_calendar_page' )
		);

		// Add the calendars import page.
		add_submenu_page(
			'none',
			esc_html__( 'Import Calendar', 'geodir-booking' ),
			esc_html__( 'Import Calendar', 'geodir-booking' ),
			'manage_options',
			'geodir-booking-ical-import',
			array( $this, 'display_manage_import_calendars_page' )
		);

		// Add the sync status page.
		add_submenu_page(
			'none',
			esc_html__( 'Sync Status', 'geodir-booking' ),
			esc_html__( 'Sync Status', 'geodir-booking' ),
			'manage_options',
			'geodir-booking-sync-status',
			array( $this, 'display_manage_sync_status_page' )
		);

		// Add the emails page.
		add_submenu_page(
			'geodir-booking',
			esc_html__( 'Emails', 'geodir-booking' ),
			esc_html__( 'Emails', 'geodir-booking' ),
			'manage_options',
			'admin.php?page=gd-settings&tab=emails&section=client_emails#email_owner_booking_request', // Shortcut to GD email settings.
			null
		);

		// Add the settings page.
		add_submenu_page(
			'geodir-booking',
			esc_html__( 'Booking Settings', 'geodir-booking' ),
			esc_html__( 'Settings', 'geodir-booking' ),
			'manage_options',
			'geodir-booking-settings',
			array( $this, 'display_booking_settings_page' )
		);
	}

	/**
	 * Displays the manage bookings page.
	 */
	public function display_manage_bookings_page() {
		$action = ! empty( $_GET['action'] ) ? sanitize_text_field( $_GET['action'] ) : '';

		if ( isset( $_GET['gd-booking'] ) && $action != 'delete' ) {
			$saved_booking = $this->maybe_save_booking();
			wp_enqueue_script( 'postbox' );
			require_once plugin_dir_path( GEODIR_BOOKING_FILE ) . 'includes/views/booking.php';
		} else {
			require_once plugin_dir_path( GEODIR_BOOKING_FILE ) . 'includes/views/bookings.php';
		}
	}

	/**
	 * Displays the add bookings page.
	 */
	public function display_add_bookings_page() {
		$page = GeoDir_Booking_Add_Booking_Page::instance();
		$page->display();
	}

	/**
	 * Displays the sync calendars page.
	 */
	public function display_manage_sync_calendars_page() {
		$table = new GeoDir_Booking_Sync_Calendar_Table();
		$table->prepare_items();

		$sync_all_url = admin_url(
			add_query_arg(
				array(
					'page'        => 'geodir-booking-ical-import',
					'action'      => 'sync',
					'listing_ids' => 'all',
				),
				'admin.php'
			)
		);
		?>
		<div class="wrap">
			<h1 class="wp-heading-inline"><?php esc_html_e( 'Sync, Import and Export Calendars', 'geodir-booking' ); ?></h1>
			<a href="<?php echo esc_url( $sync_all_url ); ?>" class="page-title-action"><?php esc_html_e( 'Sync All External Calendars', 'geodir-booking' ); ?></a>

			<p><?php esc_html_e( 'Sync your bookings across various platforms like Booking.com, TripAdvisor, Airbnb etc. via iCalendar file format.', 'geodir-booking' ); ?></p>

			<form method="POST">
				<?php $table->display(); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Displays the external calendars page.
	 */
	public function display_manage_external_calendar_page() {
		$page = new GeoDir_Booking_External_Calendar_Page();

		if ( true === $page->is_edit ) {
			$page->display();
		} else {
			$this->display_manage_sync_calendars_page();
		}
	}

	/**
	 * Displays the import calendars page.
	 */
	public function display_manage_import_calendars_page() {
		$page = GeoDir_Booking_Ical_Import_Page::instance();
		$page->display();
	}

	/**
	 * Displays the sync status page.
	 */
	public function display_manage_sync_status_page() {
		$page = GeoDir_Booking_Sync_Status_Page::instance();
		$page->display();
	}

	/**
	 * Saves a booking.
	 *
	 * @since 1.0.0
	 */
	protected function maybe_save_booking() {

		if ( empty( $_POST['gd_edit_booking'] ) ) {
			return '';
		}

		if ( ! current_user_can( 'manage_options' ) || ! wp_verify_nonce( $_POST['geodir-booking'], 'geodir-booking' ) ) {
			return false;
		}

		return geodir_save_booking( wp_kses_post_deep( wp_unslash( $_POST['geodir_booking'] ) ) );
	}

	/**
	 * Retrieves booking savings.
	 *
	 * @return array
	 */
	public function get_settings() {

		$payment_forms = wp_list_pluck(
			get_posts(
				array(
					'numberposts' => -1,
					'post_type'   => 'wpi_payment_form',
				)
			),
			'post_title',
			'ID'
		);

		$settings = array(
			'payment_type'             => array(
				'type'        => 'select',
				'label'       => __( 'Booking Payments', 'geodir-booking' ),
				'desc'        => __( 'Select the payment method to use for booking payments.', 'geodir-booking' ),
				'options'     => array(
					'full'    => __( 'Collect full payment for bookings', 'geodir-booking' ),
					'deposit' => __( 'Collect only a deposit', 'geodir-booking' ),
					'none'    => __( 'Do not collect any payment', 'geodir-booking' ),
				),
				'default'     => 'full',
				'placeholder' => __( 'Select an option', 'geodir-booking' ),
			),
			'deposit'                  => array(
				'type'        => 'number',
				'label'       => __( 'Deposit %', 'geodir-booking' ),
				'desc'        => __( 'Set the deposit payable for each booking', 'geodir-booking' ),
				'default'     => '10',
				'min'         => 0,
				'max'         => 100,
				'placeholder' => 10,
			),
			'commision'                => array(
				'type'        => 'number',
				'label'       => __( 'Booking Sales Commission %', 'geodir-booking' ),
				'desc'        => __( 'Remember to account for gateway fees', 'geodir-booking' ),
				'default'     => '10',
				'min'         => 0,
				'max'         => 100,
				'placeholder' => 10,
			),
			'service_fee'              => array(
				'type'         => 'number',
				'label'        => __( 'Additional Service fee %', 'geodir-booking' ),
				'desc'         => __( 'Optional. Payable by the guest.', 'geodir-booking' ),
				'default'      => '0',
				'min'          => 0,
				'max'          => 100,
				'placeholder'  => 0,
				'main_setting' => true,
			),
			'has_free_booking' => array(
				'id'           => 'has_free_booking',
				'label'        => __( 'Allow Free Booking', 'geodir-booking' ),
				'label2'       => __( 'Allows listing owner to setup 0(zero) nightly price to allow free booking.', 'geodir-booking' ),
				'type'         => 'checkbox',
				'default'      => 'no',
				'main_setting' => true,
			),
			'tax_behaviour'            => array(
				'type'         => 'select',
				'label'        => __( 'Tax behaviour', 'geodir-booking' ),
				'desc'         => __( 'How should we calculate tax?', 'geodir-booking' ),
				'options'      => array(
					'default' => __( 'Tax the booking amount', 'geodir-booking' ),
					'full'    => __( 'Tax the booking amount and service fee', 'geodir-booking' ),
					'fee'     => __( 'Tax the service fee and site commision', 'geodir-booking' ),
				),
				'default'      => 'default',
				'placeholder'  => __( 'Select an option', 'geodir-booking' ),
				'main_setting' => true,
			),
			'payment_form'             => array(
				'type'        => 'select',
				'label'       => __( 'Payment Form', 'geodir-booking' ),
				'desc'        => __( 'Optional. Select a payment form to use when paying for a booking.', 'geodir-booking' ),
				'options'     => $payment_forms,
				'placeholder' => __( 'Select payment form', 'geodir-booking' ),
				'default'     => wpinv_get_default_payment_form(),
			),
			'hold_booking_minutes'     => array(
				'type'         => 'number',
				'label'        => __( 'Hold booking (minutes)', 'geodir-booking' ),
				'desc'         => __( 'Hold booking (for unpaid invoices) for x minutes. When this time limit is reached, the pending invoice will be cancelled and the booking released, allowing other customers to book the same dates. Leave blank to disable.', 'geodir-booking' ),
				'default'      => 15,
				'placeholder'  => __( 'Do not hold bookings', 'geodir-booking' ),
				'main_setting' => true,
			),
			'cancellation_policies'    => array(
				'type'         => 'cancellation_policies',
				'label'        => __( 'Cancellation policies', 'geodir-booking' ),
				'desc'         => __( 'Listing owners will be able to select one for each of their listings.', 'geodir-booking' ),
				'main_setting' => true,
			),
			'ical_header'              => array(
				'type'         => 'header',
				'label'        => __( 'iCalendar Synchronization', 'geodir-booking' ),
				'main_setting' => true,
			),
			'ical_dont_export_imports' => array(
				'id'           => 'ical_dont_export_imports',
				'label'        => __( 'Imported Bookings', 'geodir-booking' ),
				'label2'       => __( 'Do not export imported bookings.', 'geodir-booking' ),
				'type'         => 'checkbox',
				'default'      => 0,
				'main_setting' => true,
			),
			'ical_sync_header'         => array(
				'type'         => 'header',
				'label'        => __( 'iCalendar Sync Scheduler', 'geodir-booking' ),
				'main_setting' => true,
			),
			'ical_auto_sync_enable'    => array(
				'id'           => 'ical_auto_sync_enable',
				'label'        => __( 'Enable iCal Synchronization', 'geodir-booking' ),
				'label2'       => __( 'Enable automatic external calendars synchronization.', 'geodir-booking' ),
				'type'         => 'checkbox',
				'default'      => 0,
				'main_setting' => true,
			),
			'ical_auto_sync_clock'     => array(
				'id'           => 'ical_auto_sync_clock',
				'type'         => 'timepicker',
				'label'        => __( 'Clock', 'geodir-booking' ),
				'desc'         => __( 'Sync calendars at this time (UTC) or starting at this time every interval below.', 'geodir-booking' ),
				'main_setting' => true,
			),
			'ical_auto_sync_interval'  => array(
				'id'           => 'ical_auto_sync_interval',
				'type'         => 'select',
				'placeholder'  => __( 'Select Interval', 'geodir-booking' ),
				'label'        => __( 'Interval', 'geodir-booking' ),
				'options'      => array(
					'gdbc_15m'   => __( 'Quarter an Hour', 'geodir-booking' ),
					'gdbc_30m'   => __( 'Half an Hour', 'geodir-booking' ),
					'hourly'     => __( 'Once Hourly', 'geodir-booking' ),
					'twicedaily' => __( 'Twice Daily', 'geodir-booking' ),
					'daily'      => __( 'Once Daily', 'geodir-booking' ),
				),
				'default'      => 'daily',
				'main_setting' => true,
			),
			'ical_auto_delete_period'  => array(
				'id'           => 'ical_auto_delete_period',
				'type'         => 'select',
				'placeholder'  => __( 'Select Period', 'geodir-booking' ),
				'label'        => __( 'Automatically delete sync logs older than', 'geodir-booking' ),
				'options'      => array(
					'day'       => __( 'Day', 'geodir-booking' ),
					'week'      => __( 'Week', 'geodir-booking' ),
					'month'     => __( 'Month', 'geodir-booking' ),
					'quarter'   => __( 'Quarter', 'geodir-booking' ),
					'half_year' => __( 'Half a Year', 'geodir-booking' ),
					'never'     => __( 'Never Delete', 'geodir-booking' ),
				),
				'default'      => 'quarter',
				'main_setting' => true,
			),
		);

		if ( ! wpinv_use_taxes() ) {
			unset( $settings['tax_behaviour'] );
		}

		return $settings;
	}

	/**
	 * Displays the settings page.
	 *
	 */
	public function display_booking_settings_page() {

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$saved_settings = $this->maybe_save_settings();

		$settings = $this->get_settings();

		include plugin_dir_path( GEODIR_BOOKING_FILE ) . 'includes/views/settings.php';
	}

	/**
	 * Displays the settings menu.
	 *
	 * @since 1.0.0
	 */
	protected function maybe_save_settings() {

		if ( empty( $_POST['geodir_booking'] ) ) {
			return '';
		}

		if ( ! current_user_can( 'manage_options' ) || ! wp_verify_nonce( $_POST['geodir-booking'], 'geodir-booking' ) ) {
			return false;
		}

		$options              = wp_kses_post_deep( wp_unslash( $_POST['geodir_booking'] ) );
		$options['commision'] = min( 100, floatval( $options['commision'] ) );

		// Cancellation policies.
		$cancellation_policies = array();
		$old_keys_to_new_keys  = array();

		if ( isset( $options['cancellation_policies'] ) && is_array( $options['cancellation_policies'] ) ) {

			foreach ( $options['cancellation_policies'] as $key => $policy ) {

				// Skip empty policies.
				if ( empty( $policy['policy_name'] ) ) {
					continue;
				}

				$new_id = $key;

				if ( 0 === strpos( $key, 'new_policy_' ) ) {
					$new_id = sanitize_title( $policy['policy_name'] );
				}

				$old_keys_to_new_keys[ $key ]     = $new_id;
				$cancellation_policies[ $new_id ] = $policy;
			}
		}

		if ( ! empty( $options['cancellation_policies_default'] ) && isset( $old_keys_to_new_keys[ $options['cancellation_policies_default'] ] ) ) {
			$options['cancellation_policies_default'] = $old_keys_to_new_keys[ $options['cancellation_policies_default'] ];
		}

		$options['cancellation_policies'] = $cancellation_policies;

		update_option( 'geodir_booking', $options );

		do_action( 'geodir_booking_settings_saved', $options );
		return true;
	}

	/**
	 * Adds payment form fields to the edit package form.
	 *
	 * @param $settings
	 * @param $package_data
	 *
	 * @return array
	 */
	public function package_settings( $settings, $package_data ) {

		$booking_settings = array(

			array(
				'type'  => 'title',
				'id'    => 'geodir_booking_settings',
				'title' => __( 'Bookings', 'geodir-booking' ),
				'desc'  => '',
			),

		);

		foreach ( $this->get_settings() as $id => $data ) {

			if ( ! empty( $data['main_setting'] ) ) {
				continue;
			}

			$current_value = geodir_booking_get_option( $id, $data['default'], $package_data['id'] );
			$default_value = geodir_booking_get_option( $id, $data['default'] );

			if ( $current_value === $default_value ) {
				$current_value = '';
			}

			$placeholder = wp_sprintf( __( 'Use Default (%s)', 'geodir-booking' ), $default_value );

			if ( 'select' === $data['type'] && isset( $data['options'][ $default_value ] ) ) {
				$placeholder = wp_sprintf( __( 'Use Default (%s)', 'geodir-booking' ), wp_strip_all_tags( $data['options'][ $default_value ] ) );
			}

			if ( in_array( $id, array( 'deposit', 'commision' ) ) ) {
				$placeholder = wp_sprintf( __( 'Use Default (%s)', 'geodir-booking' ), floatval( $default_value ) . '%' );
			}

			switch ( $data['type'] ) {

				case 'select':
					$booking_settings[] = array(
						'type'        => 'select',
						'id'          => 'geodir_booking_' . $id,
						'title'       => $data['label'],
						'desc'        => $data['desc'],
						'options'     => $data['options'],
						'default'     => $data['default'],
						'placeholder' => $placeholder,
						'class'       => 'geodir-select',
						'desc_tip'    => true,
						'value'       => $current_value,
					);
					break;

				case 'number':
					$booking_settings[] = array(
						'type'        => 'number',
						'id'          => 'geodir_booking_' . $id,
						'title'       => $data['label'],
						'desc'        => $data['desc'],
						'default'     => $data['default'],
						'min'         => $data['min'],
						'max'         => $data['max'],
						'placeholder' => $placeholder,
						'desc_tip'    => true,
						'value'       => $current_value,
					);
					break;

			}
		}

		$booking_settings[] = array(
			'type' => 'sectionend',
			'id'   => 'geodir_booking_settings',
		);

		return array_merge( $settings, $booking_settings );
	}

	/**
	 * Save the payment forms settings.
	 *
	 * @param $package_data
	 * @param $data
	 *
	 * @return mixed
	 */
	public function save_package_settings( $package_data, $data ) {
		foreach ( $this->get_settings() as $id => $setting ) {
			$_id           = 'geodir_booking_' . $id;
			$current_value = empty( $data[ $_id ] ) ? '' : $data[ $_id ];

			if ( isset( $setting['default'] ) ) {
				$default_value = geodir_booking_get_option( $id, $setting['default'] );
			} else {
				$default_value = geodir_booking_get_option( $id );
			}

			// Do not save default values.
			if ( $current_value === $default_value ) {
				$current_value = '';
			}

			$package_data['meta'][ $_id ] = $current_value;
		}

		return $package_data;
	}

	/**
	 * Adds a bookings metabox to the GD post types.
	 *
	 * @since 1.0.0
	 * @package GeoDirectory
	 * @global object $post WordPress Post object.
	 */
	public function add_listings_meta_box() {
		global $post;

		if ( isset( $post->post_type ) && in_array( $post->post_type, geodir_get_posttypes() ) ) {
			add_meta_box( 'geodir_booking', __( 'Bookings', 'geodir-booking' ), array( $this, 'listings_meta_box' ), $post->post_type, 'side', 'default' );
		}
	}

	/**
	 * Adds booking settings to the edit listing form.
	 *
	 * @return array
	 */
	public function listings_meta_box() {
		global $post_id;

		$booking_settings = array();

		foreach ( $this->get_settings() as $id => $data ) {

			if ( ! empty( $data['main_setting'] ) ) {
				continue;
			}

			$data['value'] = get_post_meta( $post_id, '_booking_' . $id, true );

			$booking_settings[ $id ] = $data;

		}

		include plugin_dir_path( GEODIR_BOOKING_FILE ) . 'includes/views/metabox-listing.php';
	}

	/**
	 * Check if we're saving, then trigger an action based on the post type.
	 *
	 * @param  int    $post_id Post ID.
	 * @param  \WP_Post $post Post object.
	 */
	public function save_meta_boxes( $post_id, $post ) {
		$post_id = absint( $post_id );

		// Check the nonce.
		if ( empty( $_POST['geodir_booking_meta_nonce'] ) || ! wp_verify_nonce( $_POST['geodir_booking_meta_nonce'], 'geodir_booking_meta' ) ) {
			return;
		}

		// Do not save for ajax requests.
		if ( ( defined( 'DOING_AJAX' ) && DOING_AJAX ) || isset( $_REQUEST['bulk_edit'] ) ) {
			return;
		}

		// $post_id and $post are required
		if ( empty( $post_id ) || empty( $post ) || $this->saved_meta_boxes ) {
			return;
		}

		// Check the post type.
		if ( ! geodir_is_gd_post_type( $post->post_type ) ) {
			return;
		}

		// Dont' save meta boxes for revisions or autosaves.
		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || is_int( wp_is_post_revision( $post ) ) || is_int( wp_is_post_autosave( $post ) ) ) {
			return;
		}

		// Check the post being saved == the $post_id to prevent triggering this call for other save_post events.
		if ( empty( $_POST['post_ID'] ) || absint( $_POST['post_ID'] ) !== $post_id ) {
			return;
		}

		// Check user has permission to edit.
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// Ensure we have data.
		if ( empty( $_POST['geodir_booking'] ) || ! is_array( $_POST['geodir_booking'] ) ) {
			return;
		}

		// We need this save event to run once to avoid potential endless loops.
		$this->saved_meta_boxes = true;

		// Loop through the meta boxes and save the data.
		foreach ( $_POST['geodir_booking'] as $key => $value ) {
			if ( '' === trim( $value ) ) {
				delete_post_meta( $post_id, '_booking_' . $key );
			} else {
				update_post_meta( $post_id, '_booking_' . $key, sanitize_text_field( $value ) );
			}
		}
	}

	/**
	 * Registers metaboxes.
	 *
	 * @param GeoDir_Customer_Booking $booking
	 * @since 1.0.0
	 */
	public function register_metaboxes( $booking ) {

		$screen_id = 'toplevel_page_geodir-booking';

		add_meta_box(
			'gd-booking-details',
			__( 'Booking Details', 'geodir-booking' ),
			array( $this, 'display_metabox' ),
			$screen_id,
			'normal',
			'high',
			'booking-details'
		);

		add_meta_box(
			'submitdiv',
			__( 'Update Booking', 'geodir-booking' ),
			array( $this, 'display_metabox' ),
			$screen_id,
			'side',
			'high',
			'booking-status'
		);

		add_meta_box(
			'gd-booking-prices',
			__( 'Price Breakdown', 'geodir-booking' ),
			array( $this, 'display_metabox' ),
			$screen_id,
			'normal',
			'high',
			'booking-prices'
		);

		add_meta_box(
			'gd-booking-note',
			__( 'Private Note', 'geodir-booking' ),
			array( $this, 'display_metabox' ),
			$screen_id,
			'side',
			'high',
			'booking-note'
		);

		if ( wpinv_get_invoice( $booking->invoice_id ) ) {
			add_meta_box(
				'gd-booking-invoice',
				__( 'Booking Invoice' ),
				array( $this, 'display_metabox' ),
				$screen_id,
				'advanced',
				'low',
				'booking-invoice'
			);
		}
	}

	/**
	 * Displays default metaboxes.
	 *
	 * @param GeoDir_Customer_Booking $booking The booking object.
	 * @param array $metabox.
	 * @since 1.0.0
	 */
	public function display_metabox( $booking, $metabox ) {

		$file = trim( $metabox['args'] );
		$file = plugin_dir_path( GEODIR_BOOKING_FILE ) . "includes/views/metabox-$file.php";

		if ( file_exists( $file ) ) {
			include $file;
		}
	}

	/**
	 * Retrieves cancellation policies dropdown.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public static function cancellation_policies_options() {
		$options = __( 'Select Cancellation Policy', 'geodir-booking' ) . '/';

		foreach ( wp_list_pluck( geodir_booking_get_cancellation_policies(), 'policy_name' ) as $value => $label ) {
			$options .= ',' . $label . '/' . $value;
		}

		return $options;
	}

	/**
	 * Filters policy options.
	 *
	 * @since 1.0.0
	 * @param array $fields
	 * @return array
	 */
	public static function filter_policy_options( $fields ) {

		foreach ( $fields as $index => $field ) {
			if ( ! empty( $field['name'] ) && $field['name'] == 'gdb_cancellation_policy' ) {

				$fields[ $index ]['options']       = self::cancellation_policies_options();
				$fields[ $index ]['option_values'] = $fields[ $index ]['options'];
				$fields[ $index ]['default_value'] = geodir_booking_get_default_cancellation_policy();
			}
		}

		return $fields;
	}

	/**
	 * Add predefined custom fields.
	 *
	 * @since 2.0
	 *
	 * @param array  $fields Custom fields.
	 * @param string $post_type The post type.
	 * @return array Custom fields.
	 */
	public static function predefined_custom_field( $fields, $post_type ) {
		// GD Booking field.
		$fields['gdbooking'] = array(
			'field_type'  => 'checkbox',
			'class'       => 'gd-has-booking',
			'icon'        => 'fas fa-calendar-check',
			'name'        => __( 'Booking', 'geodir-booking' ),
			'description' => __( 'Adds a checkbox in add listing page to make the listing bookable.', 'geodir-booking' ),
			'defaults'    => array(
				'data_type'          => 'TINYINT',
				'admin_title'        => 'Is Bookable?',
				'frontend_title'     => 'Is Bookable?',
				'frontend_desc'      => __( 'Enable booking feature for the listing.', 'geodir-booking' ),
				'htmlvar_name'       => 'gdbooking',
				'is_active'          => true,
				'for_admin_use'      => false,
				'is_required'        => false,
				'default_value'      => '0',
				'show_in'            => '',
				'option_values'      => '',
				'validation_pattern' => '',
				'validation_msg'     => '',
				'required_msg'       => '',
				'field_icon'         => 'fas fa-calendar-check',
				'css_class'          => 'gd-has-booking',
				'cat_sort'           => false,
				'cat_filter'         => false,
				'single_use'         => true,
			),
		);

		// GD Instant Book field.
		$fields['gdb_instant_book'] = array(
			'field_type'  => 'checkbox',
			'class'       => 'gd-has-instant-book',
			'icon'        => 'fas fa-calendar-check',
			'name'        => __( 'Instant Book', 'geodir-booking' ),
			'description' => __( 'Adds a checkbox in add listing page to allow users to book listings without waiting for owner approval.', 'geodir-booking' ),
			'defaults'    => array(
				'data_type'          => 'TINYINT',
				'admin_title'        => 'Instant Book',
				'frontend_title'     => 'Instant Book',
				'frontend_desc'      => __( 'Enable to automatically accept bookings. Disable to manually accept or decline booking requests.', 'geodir-booking' ),
				'htmlvar_name'       => 'gdb_instant_book',
				'is_active'          => true,
				'for_admin_use'      => false,
				'is_required'        => false,
				'default_value'      => '1',
				'show_in'            => '',
				'option_values'      => '',
				'validation_pattern' => '',
				'validation_msg'     => '',
				'required_msg'       => '',
				'field_icon'         => 'fas fa-calendar-check',
				'css_class'          => 'gd-has-instant-book',
				'cat_sort'           => false,
				'cat_filter'         => false,
				'single_use'         => true,
			),
		);

		// GD Booking price field.
		$fields['gdbprice'] = array(
			'field_type'  => 'text',
			'class'       => 'gd-booking-nightly-price',
			'icon'        => 'fas fa-dollar-sign',
			'name'        => __( 'Nightly Price', 'geodir-booking' ),
			'description' => __( 'Adds an input for the nightly price.', 'geodir-booking' ),
			'defaults'    => array(
				'htmlvar_name'       => 'gdbprice',
				'data_type'          => 'FLOAT',
				'admin_title'        => __( 'Nightly Price', 'geodir-booking' ),
				'frontend_title'     => __( 'Nightly Price', 'geodir-booking' ),
				'frontend_desc'      => __( 'Enter the default nightly price without a currency symbol', 'geodir-booking' ),
				'field_icon'         => 'fas fa-dollar-sign',
				'is_active'          => true,
				'for_admin_use'      => false,
				'default_value'      => '',
				'show_in'            => '[detail],[listing]',
				'is_required'        => false,
				'decimal_point'      => '2',
				'validation_pattern' => '\d+(\.\d{2})?',
				'validation_msg'     => __( 'Please enter number and decimal only e.g: 100.50', 'geodir-booking' ),
				'required_msg'       => '',
				'css_class'          => '',
				'cat_sort'           => true,
				'cat_filter'         => true,
				'single_use'         => true,
				'extra_fields'       => array(
					'is_price'                  => 1,
					'thousand_separator'        => wpinv_thousands_separator(),
					'decimal_separator'         => wpinv_decimal_separator(),
					'decimal_display'           => 'if',
					'currency_symbol'           => wpinv_currency_symbol(),
					'currency_symbol_placement' => false === strpos( wpinv_currency_position(), 'left' ) ? 'right' : 'left',
					'night_min_price'           => geodir_booking_night_min_price()
				),
			),
		);

		// Property has several rooms.
		$fields['gdb_multiple_units'] = array(
			'field_type'  => 'checkbox',
			'class'       => 'gd-has-multiple-rooms',
			'icon'        => 'fas fa-plus-circle',
			'name'        => __( 'Multiple Units', 'geodir-booking' ),
			'description' => __( 'Adds a checkbox in add listing page to indicate that the listing has several units.', 'geodir-booking' ),
			'defaults'    => array(
				'data_type'          => 'TINYINT',
				'admin_title'        => 'Has multiple units?',
				'frontend_title'     => 'Has multiple units?',
				'frontend_desc'      => __( 'Check this if the listing has several units or rooms.', 'geodir-booking' ),
				'htmlvar_name'       => 'gdb_multiple_units',
				'is_active'          => true,
				'for_admin_use'      => false,
				'is_required'        => false,
				'default_value'      => '0',
				'show_in'            => '',
				'option_values'      => '',
				'validation_pattern' => '',
				'validation_msg'     => '',
				'required_msg'       => '',
				'field_icon'         => 'fas fa-plus-circle',
				'css_class'          => 'gd-has-multiple-rooms',
				'cat_sort'           => false,
				'cat_filter'         => false,
				'single_use'         => true,
			),
		);

		// Number of rooms.
		$fields['gdb_number_of_rooms'] = array(
			'field_type'  => 'text',
			'class'       => 'gd-number-of-rooms',
			'icon'        => 'fas fa-bed',
			'name'        => __( 'Number of Rooms', 'geodir-booking' ),
			'description' => __( 'Adds an input for the number of rooms.', 'geodir-booking' ),
			'defaults'    => array(
				'htmlvar_name'       => 'gdb_number_of_rooms',
				'data_type'          => 'INT',
				'admin_title'        => __( 'Number of Rooms', 'geodir-booking' ),
				'frontend_title'     => __( 'Number of Rooms', 'geodir-booking' ),
				'frontend_desc'      => __( 'Enter the number of rooms.', 'geodir-booking' ),
				'field_icon'         => 'fas fa-bed',
				'is_active'          => true,
				'for_admin_use'      => false,
				'default_value'      => '',
				'show_in'            => '[detail],[listing]',
				'is_required'        => false,
				'decimal_point'      => '0',
				'validation_pattern' => '\d+',
				'validation_msg'     => __( 'Please enter number only e.g: 5', 'geodir-booking' ),
				'required_msg'       => '',
				'css_class'          => '',
				'cat_sort'           => true,
				'cat_filter'         => true,
				'single_use'         => true,
				'extra_fields'       => array(
					'is_number' => 1,
				),
			),
		);

		// GD Booking cancellation policy field.
		$fields['gdb_cancellation_policy'] = array(
			'field_type'  => 'select',
			'class'       => 'gdb-cancellation-policy',
			'icon'        => 'fas fa-ban',
			'name'        => __( 'Cancellation Policy', 'geodirectory' ),
			'description' => __( 'Allows listing owners to set the cancellation policy.', 'geodirectory' ),
			'defaults'    => array(
				'data_type'          => 'VARCHAR',
				'admin_title'        => 'Cancellation Policy',
				'frontend_title'     => 'Cancellation Policy',
				'frontend_desc'      => 'Select the cancellation policy for this listing.',
				'placeholder_value'  => 'Select Cancellation Policy',
				'htmlvar_name'       => 'gdb_cancellation_policy',
				'is_active'          => true,
				'for_admin_use'      => false,
				'default_value'      => geodir_booking_get_default_cancellation_policy(),
				'show_in'            => '[detail],[listing]',
				'is_required'        => false,
				'options'            => self::cancellation_policies_options(),
				'option_values'      => self::cancellation_policies_options(),
				'validation_pattern' => '',
				'validation_msg'     => '',
				'required_msg'       => '',
				'field_icon'         => 'fas fa-ban',
				'css_class'          => 'gdb-cancellation-policy',
				'cat_sort'           => false,
				'cat_filter'         => true,
				'single_use'         => true,
			),
		);

		// Max Guests
		$fields['property_guests'] = array(
			'field_type'  => 'text',
			'class'       => 'gd-field-property-guests',
			'icon'        => 'fas fa-users',
			'name'        => __( 'Property Max Guests', 'geodir-booking' ),
			'description' => __( 'Adds a input to enter max number of guests allowed for the property.', 'geodir-booking' ),
			'defaults'    => array(
				'data_type'          => 'INT',
				'admin_title'        => 'Max Guests',
				'frontend_title'     => 'Max Guests',
				'frontend_desc'      => __( 'Enter max number of guests allowed.', 'geodir-booking' ),
				'htmlvar_name'       => 'property_guests',
				'is_active'          => true,
				'for_admin_use'      => false,
				'is_required'        => false,
				'default_value'      => '',
				'show_in'            => '[detail]',
				'option_values'      => '',
				'validation_pattern' => '',
				'validation_msg'     => '',
				'required_msg'       => '',
				'field_icon'         => 'fas fa-users',
				'css_class'          => 'gd-field-property-guests',
				'cat_sort'           => true,
				'cat_filter'         => true,
				'single_use'         => true,
			),
		);

		// Suitable For Infants
		$fields['property_infants'] = array(
			'field_type'  => 'checkbox',
			'class'       => 'gd-field-property-infants',
			'icon'        => 'fas fa-child',
			'name'        => __( 'Property Suitable For Infants', 'geodir-booking' ),
			'description' => __( 'Adds a checkbox to allow users to tick the listing suitable for infants.', 'geodir-booking' ),
			'defaults'    => array(
				'data_type'          => 'TINYINT',
				'admin_title'        => 'Suitable For Infants',
				'frontend_title'     => 'Suitable For Infants',
				'frontend_desc'      => '',
				'htmlvar_name'       => 'property_infants',
				'is_active'          => true,
				'for_admin_use'      => false,
				'is_required'        => false,
				'default_value'      => '0',
				'show_in'            => '[detail]',
				'option_values'      => '',
				'validation_pattern' => '',
				'validation_msg'     => '',
				'required_msg'       => '',
				'field_icon'         => 'fas fa-child',
				'css_class'          => 'gd-field-property-infants',
				'cat_sort'           => true,
				'cat_filter'         => true,
				'single_use'         => true,
			),
		);

		// Suitable For Pets
		$fields['property_pets'] = array(
			'field_type'  => 'checkbox',
			'class'       => 'gd-field-property-pets',
			'icon'        => 'fas fa-paw',
			'name'        => __( 'Property Suitable For Pets', 'geodir-booking' ),
			'description' => __( 'Adds a checkbox to allow users to tick the listing suitable for pets.', 'geodir-booking' ),
			'defaults'    => array(
				'data_type'          => 'TINYINT',
				'admin_title'        => 'Suitable For Pets',
				'frontend_title'     => 'Suitable For Pets',
				'frontend_desc'      => '',
				'htmlvar_name'       => 'property_pets',
				'is_active'          => true,
				'for_admin_use'      => false,
				'is_required'        => false,
				'default_value'      => '0',
				'show_in'            => '[detail]',
				'option_values'      => '',
				'validation_pattern' => '',
				'validation_msg'     => '',
				'required_msg'       => '',
				'field_icon'         => 'fas fa-paw',
				'css_class'          => 'gd-field-property-pets',
				'cat_sort'           => true,
				'cat_filter'         => true,
				'single_use'         => true,
			),
		);

		return $fields;
	}
}
