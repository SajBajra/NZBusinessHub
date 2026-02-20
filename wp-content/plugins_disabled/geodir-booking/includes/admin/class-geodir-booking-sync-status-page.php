<?php
/**
 * Bookings sync status page class.
 *
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class GeoDir_Booking_Sync_Status_Page
 *
 * Handles the display of booking synchronization status page.
 */
class GeoDir_Booking_Sync_Status_Page {
	/**
	 * The one true instance of GeoDir_Booking_Sync_Status_Page.
	 *
	 * @var GeoDir_Booking_Sync_Status_Page
	 */
	private static $instance;

	/**
	 * @var string|null $queue The synchronization queue identifier.
	 */
	private $queue = null;

	/**
	 * Constructs the GeoDir_Booking_Sync_Status_Page object.
	 */
	public function __construct() {
		if ( isset( $_GET['queue'] ) && ! empty( $_GET['queue'] ) ) {
			$this->queue = sanitize_text_field( wp_unslash( $_GET['queue'] ) );
		}

		$this->hooks();
	}

	/**
	 * Get the one true instance of GeoDir_Booking_Sync_Status_Page.
	 *
	 * @since 1.0
	 * @return $instance
	 */
	public static function instance() {
		if ( ! self::$instance ) {
			self::$instance = new GeoDir_Booking_Sync_Status_Page();
		}

		return self::$instance;
	}

	/**
	 * Register the hooks to kick off meta registration.
	 *
	 * @since  1.0
	 * @return void
	 */
	private function hooks() {
		if ( current_user_can( 'manage_options' ) ) {
			add_action( 'admin_bar_menu', array( $this, 'show_page_link' ), 100 );
		}

		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
	}

	/**
	 * Register admin scripts
	 */
	public function enqueue_scripts() {
		$is_sync_status_page = (
			isset( $_REQUEST['page'] ) &&
			'geodir-booking-sync-status' === $_REQUEST['page']
		);

		if ( ! $is_sync_status_page ) {
			return;
		}

		wp_enqueue_style( 'geodir-booking-ical-css', plugin_dir_url( GEODIR_BOOKING_FILE ) . 'assets/css/admin-ical.css', array(), GEODIR_BOOKING_VERSION );

		if ( is_null( $this->queue ) ) {

			wp_enqueue_script( 'geodir-booking-admin-ical', plugin_dir_url( GEODIR_BOOKING_FILE ) . 'assets/js/admin-ical.js', array( 'jquery' ), GEODIR_BOOKING_VERSION, true );

			wp_localize_script(
				'geodir-booking-admin-ical',
				'Geodir_Booking_iCal',
				array(
					'ajaxUrl'    => admin_url( 'admin-ajax.php' ),
					'actions'    => array(
						'sync' => array(
							'progress'    => 'geodir_booking_ical_sync_get_progress',
							'abort'       => 'geodir_booking_ical_sync_abort',
							'remove_item' => 'geodir_booking_ical_sync_remove_item',
							'clear_all'   => 'geodir_booking_ical_sync_clear_all',
						),
					),
					'nonces'     => GeoDir_Booking_Ajax::instance()->get_nonces(),
					'i18n'       => array(
						'abort'          => __( 'Abort Process', 'geodir-booking' ),
						'aborting'       => __( 'Aborting...', 'geodir-booking' ),
						'clear'          => __( 'Delete All Logs', 'geodir-booking' ),
						'clearing'       => __( 'Deleting...', 'geodir-booking' ),
						'items_singular' => __( '%d item', 'geodir-booking' ),
						'items_plural'   => __( '%d items', 'geodir-booking' ),
					),
					'inProgress' => GeoDir_Booking_Queued_Sync::instance()->is_in_progress(),
				)
			);
		}
	}

	/**
	 * Displays the booking synchronization status page.
	 */
	public function display() {
		$page_request = isset( $_REQUEST['page'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['page'] ) ) : '';

		?>
		<div class="wrap geodir-booking-sync-details-wrapper">
			<h1 class="wp-heading-inline"><?php esc_html_e( 'iCalendars Sync Status', 'geodir-booking' ); ?></h1>
			<p><?php esc_html_e( 'View the synchronization status of your external calendars.', 'geodir-booking' ); ?></p>

			<?php if ( is_null( $this->queue ) ) : ?>

				<?php
				$sync_all_url = add_query_arg(
					array(
						'page'        => 'geodir-booking-ical-import',
						'action'      => 'sync',
						'listing_ids' => 'all',
					),
					admin_url( 'admin.php' )
				);

				$synchronizer = GeoDir_Booking_Queued_Sync::instance();
				?>
				<p>
					<a href="<?php echo esc_url( $sync_all_url ); ?>" class="button"><?php esc_html_e( 'Sync All External Calendars', 'geodir-booking' ); ?></a>
					<button class="button geodir-booking-abort-process" <?php disabled( ! $synchronizer->is_in_progress() ); ?>><?php esc_html_e( 'Abort Process', 'geodir-booking' ); ?></button>
					<button class="button geodir-booking-clear-all"><?php esc_html_e( 'Delete All Logs', 'geodir-booking' ); ?></button>
				</p>
				<?php
			else :
				$listing_id = ! is_null( $this->queue ) ? geodir_booking_parse_queue_listing_id( $this->queue ) : 0;
				$listing    = geodir_get_post_info( $listing_id );
				if ( isset( $listing->ID ) ) {
					echo '<h2>' . esc_html__( $listing->post_title, 'geodir-booking' ) . '</h2>';
				}
				?>
			<?php endif ?>

			<form method="POST" action="">
				<input type="hidden" name="page" value="<?php echo esc_attr( $page_request ); ?>" />
				<?php
				if ( is_null( $this->queue ) ) {
					$table = new GeoDir_Booking_Sync_Listings_Table();
				} else {
					$table = new GeoDir_Booking_Sync_Logs_Table();
				}

				$table->display();
				?>
			</form>
		</div>
		<?php
	}

	public function show_page_link( $admin_bar ) {
		if ( 0 === GeoDir_Booking_Queue::instance()->count_items() ) {
			return;
		}

		$sync_url = add_query_arg(
			array(
				'page' => 'geodir-booking-sync-status',
			),
			admin_url( 'admin.php' )
		);

		$admin_bar->add_node(
			array(
				'id'    => 'geodir-booking-ical-sync-progress',
				'title' => __( 'iCalendars Sync Status', 'geodir-booking' ),
				'href'  => $sync_url,
				'meta'  => array(
					'title' => __( 'Display iCalendars synchronization status.', 'geodir-booking' ),
				),
			)
		);
	}
}
