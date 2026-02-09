<?php
/**
 * Displays a list of sync listings.
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Sync listings table class.
 *
 * @package GeoDirectory
 * @subpackage GeoDirectory Bookings
 * @version 1.0.0
 * @since   1.0.0
 */
class GeoDir_Booking_Sync_Listings_Table extends WP_List_Table {

	/**
	 * Number of items per page.
	 *
	 * @var int
	 */
	public $per_page = 20;

	/**
	 * Constructor function.
	 */
	public function __construct() {
		parent::__construct(
			array(
				'singular' => __( 'Sync Listing', 'geodir-booking' ),
				'plural'   => __( 'Sync Listings', 'geodir-booking' ),
				'ajax'     => false,
			)
		);

		$this->prepare_items();
	}

	/**
	 * Handles data query and filter, sorting, and pagination.
	 */
	public function prepare_items() {
		$columns  = $this->get_columns();
		$hidden   = array();
		$sortable = $this->get_sortable_columns();

		$this->_column_headers = array( $columns, $hidden, $sortable );

		// Query items and set pagination args
		$this->items = $this->query_items();
	}

	/**
	 * Handles sort and filter the data.
	 *
	 * @return array Items after querying.
	 */
	public function query_items() {
		$limit  = $this->get_items_per_page( 'sync_listings_per_page', 20 );
		$offset = ( $this->get_pagenum() - 1 ) * $limit;

		$queue     = GeoDir_Booking_Queue::instance()->select_items_page( $offset, $limit );
		$queue_ids = array_keys( $queue );
		$stats     = GeoDir_Booking_Stats::instance()->select_stats( $queue_ids );

		$items = array();

		foreach ( $queue_ids as $queue_id ) {
			$items[ $queue_id ] = array_merge( $queue[ $queue_id ], $stats[ $queue_id ] );
		}

		$items = $this->filter_items( $items );

		$total_count = GeoDir_Booking_Queue::instance()->count_items();

		$this->set_pagination_args(
			array(
				'total_items' => $total_count,
				'per_page'    => $limit,
				'total_pages' => ceil( $total_count / $limit ),
			)
		);

		return $items;
	}

	/**
	 * Filter items, map items, add more fields etc.
	 *
	 * @param array $items Items to be filtered.
	 * @return array Filtered items.
	 */
	protected function filter_items( $items ) {
		$new_items = array();

		foreach ( $items as $queue_id => $item ) {
			$listing_id = geodir_booking_parse_queue_listing_id( $item['queue'] );
			$listing    = geodir_get_post_info( $listing_id );

			// Handling empty listing titles
			if ( ! isset( $listing->ID ) || empty( $listing->post_title ) ) {
				$title = _x( '(no title)', 'Placeholder for empty listing title', 'geodir-booking' );
			} else {
				$title = $listing->post_title;
			}

			$time = GeoDir_Booking_Queued_Sync::retrieve_time_from_item( $item['queue'] );
			$date = date( _x( 'd/m/Y - H:i:s', 'Date and time format', 'geodir-booking' ), $time );

			// Switch case for status titles
			switch ( $item['status'] ) {
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
					$status_title = ucfirst( str_replace( '-', ' ', $item['status'] ) );
					break;
			}

			// Creating a new array with modified items
			$new_items[ $queue_id ] = array(
				'queue-id'    => $queue_id,
				'queue-name'  => $item['queue'],
				'title'       => $title,
				'status'      => $item['status'],
				'status-text' => $status_title,
				'total'       => $item['total'],
				'succeed'     => $item['succeed'],
				'skipped'     => $item['skipped'],
				'failed'      => $item['failed'],
				'removed'     => $item['removed'],
				'date'        => $date,
			);
		}

		return $new_items;
	}

	/**
	 * Retrieve the table columns.
	 *
	 * @return array Array of all the list table columns.
	 */
	public function get_columns() {
		$columns = array(
			'title'   => __( 'Listing', 'geodir-booking' ),
			'status'  => __( 'Status', 'geodir-booking' ),
			'total'   => _x( 'Total', 'Total number of processed bookings', 'geodir-booking' ),
			'succeed' => __( 'Succeed', 'geodir-booking' ),
			'skipped' => __( 'Skipped', 'geodir-booking' ),
			'failed'  => __( 'Failed', 'geodir-booking' ),
			'removed' => __( 'Removed', 'geodir-booking' ),
			'date'    => __( 'Date', 'geodir-booking' ),
		);

		return $columns;
	}

	/**
	 * Render the title column.
	 *
	 * @param array $item Row data.
	 * @return string HTML for the title column.
	 */
	public function column_title( $item ) {
		$view_url = esc_url(
			add_query_arg(
				array(
					'page'     => 'geodir-booking-sync-status',
					'queue'    => $item['queue-name'],
					'queue-id' => $item['queue-id'],
				)
			)
		);

		$actions = array(
			'view'   => sprintf( '<a href="%s">%s</a>', esc_url( $view_url ), __( 'View', 'geodir-booking' ) ),
			'delete' => sprintf( '<a href="%s" class="geodir-booking-remove-item">%s</a>', '#', __( 'Delete', 'geodir-booking' ) ),
		);

		$title = '<strong><a href="' . esc_url( $view_url ) . '">' . $item['title'] . '</a></strong>';

		return $title . $this->row_actions( $actions );
	}

	/**
	 * Render the status column.
	 *
	 * @param array $item Row data.
	 * @return string HTML for the status column.
	 */
	public function column_status( $item ) {
		$class = 'geodir-booking-status-' . $item['status'];
		return '<span class="' . esc_attr( $class ) . '">' . $item['status-text'] . '</span>';
	}

	/**
	 * Render the date column.
	 *
	 * @param array $item Row data.
	 * @return string HTML for the date column.
	 */
	public function column_date( $item ) {
		return $item['date'];
	}

	/**
	 * Render a column when no column specific method exist.
	 *
	 * @param array  $item         Row data.
	 * @param string $column_name  Column name.
	 * @return string              HTML for the column.
	 */
	public function column_default( $item, $column_name ) {
		if ( ! isset( $item[ $column_name ] ) ) {
			return '<div>&mdash;</div>';
		}

		// Handling special cases
		if ( $item[ $column_name ] > 0 ) {
			return $item[ $column_name ];
		} else {
			return '<div>&mdash;</div>';
		}
	}

	/**
	 * Render the single row.
	 *
	 * @param array $item Row data.
	 * @return void Echoes the HTML for the single row.
	 */
	public function single_row( $item ) {
		$atts  = 'data-sync-status="' . esc_attr( $item['status'] ) . '" ';
		$atts .= 'data-item-key="' . esc_attr( $item['queue-name'] ) . '"';

		if ( $item['failed'] > 0 ) {
			$atts .= ' class="geodir-booking-sync-errors"';
		}

        // phpcs:ignore
		echo '<tr ' . $atts . '>';
			$this->single_row_columns( $item );
		echo '</tr>';
	}

	/**
	 * Retrieve the table classes.
	 *
	 * @return array Array of table classes.
	 */
	protected function get_table_classes() {
		$classes = parent::get_table_classes();

		$classes[] = 'geodir-booking-ical-sync-table';
		$classes[] = 'geodir-booking-sync-listings-table';

		return $classes;
	}
}
