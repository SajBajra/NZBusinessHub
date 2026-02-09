<?php
/**
 * Bookings ical import page class.
 *
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Handles the import of iCalendars for bookings.
 */
class GeoDir_Booking_Ical_Import_Page {
	/**
	 * The one true instance of GeoDir_Booking_Ical_Import_Page.
	 *
	 * @var GeoDir_Booking_Ical_Import_Page
	 */
	private static $instance;

	/**
	 * The action to perform - "upload" or "sync".
	 *
	 * @var string
	 */
	private $action;

	/**
	 * The ID of the listing being processed.
	 *
	 * @var int
	 */
	private $listing_id = 0;

	/**
	 * Indicates if a file has been uploaded and ready for import.
	 *
	 * @var bool
	 */
	private $file_uploaded = false;

	/**
	 * Indicates if a background import is in progress.
	 *
	 * @var bool
	 */
	private $is_uploading = false;

	/**
	 * Initializes the GeoDir_Booking_Ical_Import_Page object.
	 */
	public function __construct() {
		$this->action        = isset( $_GET['action'] ) ? sanitize_text_field( wp_unslash( $_GET['action'] ) ) : '';
		$this->file_uploaded = 'upload' === $this->action && isset( $_FILES['import'] );

		$this->process();

		$this->hooks();
	}

	/**
	 * Get the one true instance of GeoDir_Booking_Ical_Import_Page.
	 *
	 * @since 1.0
	 * @return $instance
	 */
	public static function instance() {
		if ( ! self::$instance ) {
			self::$instance = new GeoDir_Booking_Ical_Import_Page();
		}

		return self::$instance;
	}

	/**
	 * Registers hooks to enqueue scripts.
	 *
	 * @since  1.0
	 * @return void
	 */
	private function hooks() {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
	}

	/**
	 * Register admin scripts
	 */
	public function enqueue_scripts() {
		$is_import_page = (
			isset( $_REQUEST['page'] ) &&
			'geodir-booking-ical-import' === $_REQUEST['page']
		);

		if ( ! $is_import_page ) {
			return;
		}

		wp_enqueue_style( 'geodir-booking-ical-css', plugin_dir_url( GEODIR_BOOKING_FILE ) . 'assets/css/admin-ical.css', array(), GEODIR_BOOKING_VERSION );

		if ( $this->is_uploading ) {
			wp_enqueue_script( 'geodir-booking-admin-ical', plugin_dir_url( GEODIR_BOOKING_FILE ) . 'assets/js/admin-ical.js', array( 'jquery' ), GEODIR_BOOKING_VERSION, true );

			wp_localize_script(
				'geodir-booking-admin-ical',
				'Geodir_Booking_iCal',
				array(
					'ajaxUrl'    => admin_url( 'admin-ajax.php' ),
					'actions'    => array(
						'upload' => array(
							'progress' => 'geodir_booking_ical_upload_get_progress',
							'abort'    => 'geodir_booking_ical_upload_abort',
						),
					),
					'nonces'     => GeoDir_Booking_Ajax::instance()->get_nonces(),
					'i18n'       => array(
						'abort'    => __( 'Abort Process', 'geodir-booking' ),
						'aborting' => __( 'Aborting...', 'geodir-booking' ),
					),
					'inProgress' => $this->is_uploading,
				)
			);
		}
	}

	/**
	 * Processes uploads and synchronization of iCalendars.
	 */
	public function process() {
		$listing_id  = isset( $_GET['listing_id'] ) ? (int) $_GET['listing_id'] : 0;
		$listing_ids = isset( $_GET['listing_ids'] ) ? wp_unslash( $_GET['listing_ids'] ) : null;

		// Determine the listing IDs to process based on input parameters.
		if ( ! empty( $listing_id ) ) {
			$listing_ids = array( $listing_id );

		} elseif ( is_null( $listing_ids ) ) {
			$listing_ids = array();

		} elseif ( 'all' === $listing_ids ) {
			$listings_query = geodir_booking_get_bookable_listings_query(
				array(
					'post_status' => geodir_get_post_stati( 'public', array( 'post_type' => 'gd_place' ) ),
					'posts_per_page' => -1,
					'fields' => 'ids', 
					'order'  => 'ASC',
					'_gdbooking_context' => 'ical_import'
				)
			);

			$listing_ids = $listings_query->get_posts();

		} elseif ( strpos( $listing_ids, ',' ) !== false ) {
			$ids         = array_map( 'intval', explode( ',', $listing_ids ) );
			$listing_ids = array_filter( $ids, 'is_numeric' );

		} else {
			$listing_ids = array();
		}

		$this->listing_id = absint( $listing_id );

		// Process based on the action type.
		switch ( $this->action ) {
			case 'upload':
				$uploader = GeoDir_Booking_Background_Uploader::instance();

				if ( $this->file_uploaded ) {
					$uploader->reset();
					// Ensure the file path is sanitized.
					// Do not unslash $_FILES['import']['tmp_name'] to avoid issues on Windows.
                    // phpcs:ignore
					$calendar_url = isset( $_FILES['import']['tmp_name'] ) ? sanitize_text_field( $_FILES['import']['tmp_name'] ) : '';
					$uploader->parse_calendar( $listing_id, $calendar_url );
					$this->is_uploading = true;
				} else {
					$this->is_uploading = $uploader->is_in_progress();
					$uploader->touch();
				}
 
				break;

			case 'sync':
				$importer = GeoDir_Booking_Queued_Sync::instance();
				$importer->sync( $listing_ids );

				// Redirect to the synchronization status page.
				wp_safe_redirect(
					add_query_arg(
						array(
							'page' => 'geodir-booking-sync-status',
						),
						admin_url( 'admin.php' )
					)
				);
				break;

		}
	}

	/**
	 * Displays the import calendars page.
	 *
	 * @return void
	 *
	 */
	public function display() {
		$listing      = $this->listing_id ? geodir_get_post_info( $this->listing_id ) : array();
		$logs_handler = new GeoDir_Booking_Logs_Handler();

		?>
		<div class="wrap">
			<h1 class="wp-heading-inline"><?php esc_html_e( 'Import Calendar', 'geodir-booking' ); ?></h1>
			<a class="page-title-action" href="<?php echo esc_url( admin_url( 'admin.php?page=geodir-booking-ical' ) ); ?>"><?php esc_html_e( 'Back', 'geodir-booking' ); ?> &#10548;&#xFE0E;</a>
			<hr class="wp-header-end" />

			<?php if ( ! empty( $listing->ID ) ) : ?>
				<h3><?php printf( esc_html__( 'Listing: %s', 'geodir-booking' ), '<strong>' . wp_kses_post( $listing->post_title ) . '</strong>' ); ?></h3>
			<?php endif; ?>

			<div class="geodir-booking-upload-import-details-wrapper">
				<?php
				// Display message for ongoing file import.
				if ( $this->file_uploaded ) {
					echo '<p>', esc_html__( 'Please be patient while the calendars are imported. You will be notified via this page when the process is completed.', 'geodir-booking' ), '</p>';
				}

				// Display progress if file is being uploaded.
				if ( $this->is_uploading ) {
					$logs_handler->display_progress();
				}
				?>

				<hr class="wp-header-end" />

				<?php
				// Display file upload form if no file is being uploaded.
				if ( ! $this->file_uploaded && ! $this->is_uploading && ! empty( $listing->ID ) ) {
					$upload_url = add_query_arg(
						array(
							'page'       => 'geodir-booking-ical-import',
							'action'     => 'upload',
							'listing_id' => $listing->ID,
						),
						admin_url( 'admin.php' )
					);

					wp_import_upload_form( $upload_url );
				}

				// Show process information (only if uploading current file)
				if ( $this->is_uploading ) {
					// Load logs and counts via AJAX
					$process_details = array(
						'logs'  => array(),
						'stats' => GeoDir_Booking_Stats::instance()->empty_stats(),
					);

					$logs_handler->display( $process_details );
				}

				// Show "Back" button and "Import Calendar" button if file uploaded or uploading.
				if ( ( $this->file_uploaded || $this->is_uploading ) && ! empty( $listing->ID ) ) {
					$back_url = add_query_arg(
						array(
							'page'       => 'geodir-booking-ical-import',
							'action'     => 'upload',
							'listing_id' => $listing->ID,
						),
						admin_url( 'admin.php' )
					);

					?>
					<a class="button button-primary" href="<?php echo esc_url( admin_url( 'admin.php?page=geodir-booking-ical' ) ); ?>"><?php esc_html_e( 'Back', 'geodir-booking' ); ?></a>
					<a class="button button-secondary" href="<?php echo esc_url( $back_url ); ?>"><?php esc_html_e( 'Import Calendar', 'geodir-booking' ); ?></a>
				<?php } ?>
			</div>
		</div>
		<?php
	}
}
