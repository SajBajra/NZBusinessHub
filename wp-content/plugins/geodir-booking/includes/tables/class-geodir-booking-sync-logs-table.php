<?php
/**
 * Displays a list of sync logs.
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Sync logs table class.
 *
 * @package GeoDirectory
 * @subpackage GeoDirectory Bookings
 * @since   1.0.0
 * @version 1.0.0
 */
class GeoDir_Booking_Sync_Logs_Table extends WP_List_Table {
	/**
	 * @var int Stores the queue ID.
	 */
	protected $queue_id = 0;

	/**
	 * @var GeoDir_Booking_Logs_Handler Logs handler object.
	 */
	protected $logs_handler;

	/**
	 * Constructor function.
	 */
	public function __construct() {
		parent::__construct(
			array(
				'singular' => __( 'Sync Log', 'geodir-booking' ),
				'plural'   => __( 'Sync Logs', 'geodir-booking' ),
				'ajax'     => false,
			)
		);

		// Retrieve queue ID from the request, if available.
		if ( isset( $_REQUEST['queue-id'] ) ) {
			$this->queue_id = absint( $_REQUEST['queue-id'] );
		}

		// Initialize logs handler.
		$this->logs_handler = new GeoDir_Booking_Logs_Handler();

		// Prepare table items.
		$this->prepare_items();
	}

	/**
	 * Handles data query and filter, sorting, and pagination.
	 */
	public function prepare_items() {
		$columns  = $this->get_columns();
		$hidden   = array();
		$sortable = $this->get_sortable_columns();

		// Set column headers.
		$this->_column_headers = array( $columns, $hidden, $sortable );

		// Query items and set pagination args.
		$this->items = $this->query_items();
	}

	/**
	 * Handles sorting and filtering of data.
	 *
	 * @return array Array of queried items.
	 */
	protected function query_items() {
		$limit  = $this->get_items_per_page( 'sync_logs_per_page', 100 );
		$offset = ( $this->get_pagenum() - 1 ) * $limit;

		$items = GeoDir_Booking_Logger::instance()->select_logs( $this->queue_id, $offset, $limit );

		$total_count = GeoDir_Booking_Logger::instance()->count_logs( $this->queue_id );
		$pages_count = ceil( $total_count / $limit );

		// Set pagination arguments.
		$this->set_pagination_args(
			array(
				'total_items' => $total_count,
				'per_page'    => $limit,
				'total_pages' => $pages_count,
			)
		);

		return $items;
	}

	/**
	 * Retrieve the table columns.
	 *
	 * @return array Array of all the list table columns.
	 */
	public function get_columns() {
		$columns = array(
			'status'  => __( 'Status', 'geodir-booking' ),
			'message' => __( 'Message', 'geodir-booking' ),
		);

		return $columns;
	}

	/**
	 * Render the status column.
	 *
	 * @param object $item Row data.
	 * @return string Rendered HTML for the status column.
	 */
	public function column_status( $item ) {
		$class = 'geodir-booking-status-' . $item['status'];

		switch ( $item['status'] ) {
			case 'success':
				$text = __( 'Success', 'geodir-booking' );
				break;
			case 'info':
				$text = __( 'Info', 'geodir-booking' );
				break;
			case 'warning':
				$text = __( 'Warning', 'geodir-booking' );
				break;
			case 'error':
				$text = __( 'Error', 'geodir-booking' );
				break;
			default:
				$text = ucfirst( str_replace( '-', ' ', $item['status'] ) );
				break;
		}

		return '<span class="' . esc_attr( $class ) . '">' . $text . '</span>';
	}

	/**
	 * Render the message column.
	 *
	 * @param object $item Row data.
	 * @return string Rendered HTML for the message column.
	 */
	public function column_message( $item ) {
		return $this->logs_handler->log_to_html( $item, true );
	}

	/**
	 * Retrieves the CSS classes for the table.
	 *
	 * @return array Array of CSS classes for the table.
	 */
	protected function get_table_classes() {
		$classes = parent::get_table_classes();

		$classes[] = 'geodir-booking-ical-sync-table';
		$classes[] = 'geodir-booking-sync-logs-table';

		return $classes;
	}
}
