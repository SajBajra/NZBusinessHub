<?php
/**
 * Main AJAX Class.
 *
 * @package GeoDirectory
 * @subpackage Booking
 * @version 1.0.0
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Main AJAX class for GeoDirectory Bookings.
 *
 * This class handles AJAX requests related to bookings.
 *
 * @package GeoDirectory
 * @subpackage Booking
 * @since   1.0.0
 */
class GeoDir_Booking_Ajax {
	/**
	 * The single instance of GeoDir_Booking_Ajax.
	 *
	 * @var GeoDir_Booking_Ajax|null
	 */
	private static $instance = null;

	/**
	 * Name of the nonce used for security verification.
	 *
	 * @var string
	 */
	protected $nonce_name = 'geodir_booking_nonce';

	/**
	 * Prefix used for AJAX action names.
	 *
	 * @var string
	 */
	protected $action_prefix = 'geodir_booking_';

	/**
	 * List of AJAX actions along with their details.
	 *
	 * @var array
	 */
	protected $ajax_actions = array(
		'create_booking'           => array(
			'method' => 'POST',
		),
		'ical_sync_abort'          => array(
			'method' => 'POST',
		),
		'ical_sync_clear_all'      => array(
			'method' => 'POST',
		),
		'ical_sync_remove_item'    => array(
			'method' => 'POST',
		),
		'ical_sync_get_progress'   => array(
			'method' => 'POST',
		),
		'ical_upload_get_progress' => array(
			'method' => 'GET',
		),
		'ical_upload_abort'        => array(
			'method' => 'POST',
		),
	);

	/**
	 * GeoDir_Booking_Ajax constructor.
	 */
	public function __construct() {
		foreach ( $this->ajax_actions as $action => $details ) {
			$no_priv = isset( $details['no_priv'] ) ? $details['no_priv'] : false;
			$this->add_ajax_action( $action, $no_priv );
		}
	}

	/**
	 * Retrieve the single instance of GeoDir_Booking_Ajax.
	 *
	 * @since 1.0
	 * @return GeoDir_Booking_Ajax
	 */
	public static function instance() {
		if ( ! self::$instance ) {
			self::$instance = new GeoDir_Booking_Ajax();
		}

		return self::$instance;
	}

	/**
	 * Retrieves input data for processing AJAX requests.
	 *
	 * @param string $action The name of the AJAX action without the 'wp' prefix.
	 * @return array An array containing input data for the AJAX request.
	 */
	protected function get_request_input( $action ) {
		// Determine the HTTP method for the AJAX action.
		$method = isset( $this->ajax_actions[ $action ]['method'] ) ? $this->ajax_actions[ $action ]['method'] : '';

		// Retrieve input data based on the HTTP method.
		switch ( $method ) {
			case 'GET':
				$input = $_GET;
				break;
			case 'POST':
				$input = $_POST;
				break;
			default:
				$input = $_REQUEST;
		}

		return $input;
	}

	/**
	 * Retrieve nonces for AJAX actions.
	 *
	 * @return array Nonces for AJAX actions.
	 */
	public function get_nonces() {
		$nonces = array();
		foreach ( $this->ajax_actions as $action_name => $details ) {
			$nonces[ $this->action_prefix . $action_name ] = wp_create_nonce( $this->action_prefix . $action_name );
		}

		return $nonces;
	}

	/**
	 * Add AJAX action hooks.
	 *
	 * @param string $action AJAX action name.
	 * @param bool   $no_priv Whether the action is available for non-logged in users.
	 */
	public function add_ajax_action( $action, $no_priv = false ) {
		add_action( 'wp_ajax_' . $this->action_prefix . $action, array( $this, $action ) );

		if ( $no_priv ) {
			add_action( 'wp_ajax_nopriv_' . $this->action_prefix . $action, array( $this, $action ) );
		}
	}

	/**
	 * Check the validity of the nonce.
	 *
	 * @param string $action AJAX action name.
	 * @return bool True if the nonce is valid, otherwise false.
	 */
	protected function check_nonce( $action ) {
		if ( ! isset( $this->ajax_actions[ $action ] ) ) {
			return false;
		}

		$input = $this->get_request_input( $action );

		$nonce = isset( $input[ $this->nonce_name ] ) ? $input[ $this->nonce_name ] : '';

		return wp_verify_nonce( $nonce, $this->action_prefix . $action );
	}

	/**
	 * Verify the validity of the nonce.
	 *
	 * @param string $action AJAX action name.
	 */
	protected function verify_nonce( $action ) {
		if ( ! $this->check_nonce( $action ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Request does not pass security verification. Please refresh the page and try one more time.', 'geodir-booking' ),
				)
			);
		}
	}

	/**
	 * Handles the creation of a new booking.
	 *
	 * @param string $_POST['gdbc_check_in_date'] Check-in date in 'Y-m-d' format.
	* @param string $_POST['gdbc_check_out_date'] Check-out date in 'Y-m-d' format.
	* @param array  $_POST['gdbc_room_details'] Array of room details, each containing:
	*                                           - 'room_id': ID of the room.
	*                                           - 'adults': Number of adults.
	*                                           - 'children': Number of children.
	* @param string $_POST['gdbc_customer_name'] Name of the customer.
	* @param string $_POST['gdbc_customer_email'] Email address of the customer.
	* @param string $_POST['gdbc_customer_phone'] Phone number of the customer.
	* @param string $_POST['gdbc_private_note'] (Optional) Private note for the booking.
	* @param string $_POST['gdbc_booking_status'] (Optional) Status of the booking. Default is 'draft'.
	*
	* @return void Sends a JSON response with either an error message or a redirect URL.
	 */
	public function create_booking() {
		$this->verify_nonce( __FUNCTION__ );

		$check_in_date  = isset( $_POST['gdbc_check_in_date'] ) ? sanitize_text_field( $_POST['gdbc_check_in_date'] ) : '';
		$check_out_date = isset( $_POST['gdbc_check_out_date'] ) ? sanitize_text_field( $_POST['gdbc_check_out_date'] ) : '';
		$rooms          = isset( $_POST['gdbc_room_details'] ) ? (array) wp_unslash( $_POST['gdbc_room_details'] ) : array();
		$customer_name  = isset( $_POST['gdbc_customer_name'] ) ? sanitize_text_field( $_POST['gdbc_customer_name'] ) : '';
		$customer_email = isset( $_POST['gdbc_customer_email'] ) ? sanitize_email( $_POST['gdbc_customer_email'] ) : '';
		$customer_phone = isset( $_POST['gdbc_customer_phone'] ) ? sanitize_text_field( $_POST['gdbc_customer_phone'] ) : '';
		$private_note   = isset( $_POST['gdbc_private_note'] ) ? sanitize_textarea_field( $_POST['gdbc_private_note'] ) : '';
		$booking_status = isset( $_POST['gdbc_booking_status'] ) ? sanitize_text_field( $_POST['gdbc_booking_status'] ) : 'draft';

		if ( empty( $rooms ) ) {
			wp_send_json_error(
				array(
					'message' => esc_html__( 'No room selected.', 'geodir-booking' ),
				)
			);
		}

		// Ensure we have a name.
		if ( empty( $customer_name ) ) {
			wp_send_json_error(
				array(
					'message' => esc_html__( 'Customer name is required.', 'geodir-booking' ),
				)
			);
		}

		// Ensure we have a phone.
		if ( empty( $customer_phone ) ) {
			wp_send_json_error(
				array(
					'message' => esc_html__( 'Phone number is required.', 'geodir-booking' ),
				)
			);
		}

		// Ensure we have a email.
		if ( empty( $customer_email ) || ! is_email( $customer_email ) ) {
			wp_send_json_error(
				array(
					'message' => esc_html__( 'Valid email is required.', 'geodir-booking' ),
				)
			);
		}

		// Ensure we have a date.
		if ( empty( $check_in_date ) || empty( $check_out_date ) ) {
			wp_send_json_error(
				array(
					'message' => esc_html__( 'Check-in and check-out dates are required.', 'geodir-booking' ),
				)
			);
		}

		// End date cannot be before start date.
		if ( strtotime( $check_out_date ) < strtotime( $check_in_date ) ) {
			wp_send_json_error(
				array(
					'message' => esc_html__( 'Check-out date cannot be before check-in date.', 'geodir-booking' ),
				)
			);
		}

		$valid_statuses = geodir_get_booking_statuses();
		if ( ! in_array( $booking_status, array_keys( $valid_statuses ) ) ) {
			wp_send_json_error(
				array(
					'message' => esc_html__( 'Please select a valid status.', 'geodir-booking' ),
				)
			);
		}

		// Process room bookings.
		foreach ( $rooms as $key => $room ) {
			$listing = get_post( (int) $room['room_id'] );

			if ( empty( $listing ) || 'publish' !== $listing->post_status ) {
				wp_send_json_error(
					array(
						'message' => esc_html__( 'Selected listing is not available.', 'geodir-booking' ),
					)
				);
			}

			$adults   = absint( (int) $room['adults'] );
			$children = absint( (int) $room['children'] );

			$result = geodir_save_booking(
				array(
					'name'         => $customer_name,
					'email'        => $customer_email,
					'phone'        => $customer_phone,
					'private_note' => $private_note,
					'status'       => $booking_status,
					'start_date'   => $check_in_date,
					'end_date'     => $check_out_date,
					'listing_id'   => (int) $listing->ID,
					'guests'       => $adults + $children,
					'adults'       => $adults,
					'children'     => $children,
					'created'      => current_time( 'mysql' ),
				)
			);

			if ( ! is_wp_error( $result ) ) {
				$result->customer_confirm();
			}

			$rooms[ $key ]['booking_id'] = $result->id;
		}

		$page_args = array(
			'page' => 'geodir-booking',
		);

		if ( 1 === count( $rooms ) ) {
			$room                    = current( $rooms );
			$page_args['gd-booking'] = absint( $room['booking_id'] );
		}

		$redirect_url = admin_url(
			add_query_arg(
				$page_args,
				'admin.php'
			)
		);

		return wp_send_json(
			array(
				'callback' => "window.location.href='{$redirect_url}'",
			)
		);
	}

	/**
	 * Callback function for aborting iCal synchronization.
	 *
	 * This function is triggered via AJAX to abort the iCal synchronization process.
	 *
	 * @since 1.0.0
	 */
	public function ical_sync_abort() {
		$this->verify_nonce( __FUNCTION__ );

		GeoDir_Booking_Queued_Sync::instance()->abort_all();

		wp_send_json_success();
	}

	/**
	 * Callback function for clearing all iCal sync items.
	 *
	 * This function is triggered via AJAX to clear all items in the iCal synchronization queue.
	 *
	 * @since 1.0.0
	 */
	public function ical_sync_clear_all() {
		$this->verify_nonce( __FUNCTION__ );

		GeoDir_Booking_Queued_Sync::instance()->clear_all();

		wp_send_json_success();
	}

	/**
	 * Callback function for removing an iCal sync item.
	 *
	 * This function is triggered via AJAX to remove a specific item from the iCal synchronization queue.
	 *
	 * @since 1.0.0
	 */
	public function ical_sync_remove_item() {
		$this->verify_nonce( __FUNCTION__ );

		$room_key = geodir_booking_clean( wp_unslash( $_POST['geodir_booking_room_key'] ) );

		GeoDir_Booking_Queued_Sync::instance()->remove_item( $room_key );

		wp_send_json_success();
	}

	/**
	 * Callback function for getting iCal sync progress.
	 *
	 * This function is triggered via AJAX to retrieve the progress of iCal synchronization.
	 *
	 * @since 1.0.0
	 */
	public function ical_sync_get_progress() {
		$this->verify_nonce( __FUNCTION__ );

		$items     = isset( $_POST['focus'] ) ? (array) wp_unslash( $_POST['focus'] ) : array();
		$queue     = GeoDir_Booking_Queue::instance()->select_items( $items );
		$queue_ids = array_keys( $queue );
		$stats     = GeoDir_Booking_Stats::instance()->select_stats( $queue_ids );

		$processed_items = array();

		foreach ( $queue_ids as $queue_id ) {
			$queue_name   = $queue[ $queue_id ]['queue'];
			$status       = $queue[ $queue_id ]['status'];
			$status_class = 'geodir-booking-status-' . $status;

			switch ( $status ) {
				case GeoDir_Booking_Queue::STATUS_WAIT:
					$status_title = __( 'Waiting', 'geodir-booking' );
					break;
				case GeoDir_Booking_Queue::STATUS_IN_PROGRESS:
					$status_title = __( 'Processing', 'geodir-booking' );
					break;
				case GeoDir_Booking_Queue::STATUS_DONE:
					$status_title = __( 'Done', 'geodir-booking' );
					break;

				default:
					$status_title = ucfirst( str_replace( '-', ' ', $status ) );
					break;
			}

			$item_stats = $stats[ $queue_id ];

			$processed_items[ $queue_name ] = array(
				'status' => array(
					'code'  => $status,
					'class' => $status_class,
					'text'  => $status_title,
				),
				'stats'  => $item_stats,
			);
		}

		wp_send_json_success(
			array(
				'items'      => $processed_items,
				'inProgress' => GeoDir_Booking_Queued_Sync::instance()->is_in_progress(),
			)
		);
	}

	/**
	 * Callback function for getting iCal upload progress.
	 *
	 * This function is triggered via AJAX to retrieve the progress of iCal uploads.
	 *
	 * @since 1.0.0
	 */
	public function ical_upload_get_progress() {
		$this->verify_nonce( __FUNCTION__ );

		$logs_shown      = isset( $_GET['logsShown'] ) ? absint( (int) $_GET['logsShown'] ) : 0;
		$logs_handler    = new GeoDir_Booking_Logs_Handler();
		$uploader        = GeoDir_Booking_Background_Uploader::instance();
		$process_details = $uploader->get_details( $logs_shown );
		$logs            = $process_details['logs'];
		$stats           = $process_details['stats'];
		$is_finished     = ! $uploader->is_in_progress();
		$notice          = '';

		// Build notice
		if ( $is_finished ) {
			$notice = $logs_handler->build_notice( $stats['succeed'], $stats['failed'] );
		}

		// Calculate new "logs_shown"
		$logs_shown += count( $logs );

		wp_send_json_success(
			array(
				'total'      => (int) $stats['total'],
				'succeed'    => (int) $stats['succeed'],
				'skipped'    => (int) $stats['skipped'],
				'failed'     => (int) $stats['failed'],
				'removed'    => (int) $stats['removed'],
				'progress'   => $uploader->get_progress(),
				'logs'       => $logs_handler->logs_to_html( $logs ),
				'logsShown'  => $logs_shown,
				'notice'     => $notice,
				'isFinished' => (bool) $is_finished,
			)
		);
	}

	/**
	 * Callback function for aborting iCal uploads.
	 *
	 * This function is triggered via AJAX to abort iCal uploads.
	 *
	 * @since 1.0.0
	 */
	public function ical_upload_abort() {
		$this->verify_nonce( __FUNCTION__ );

		GeoDir_Booking_Background_Uploader::instance()->abort();

		wp_send_json_success();
	}
}
