<?php
/**
 * Displays a list of sync calendars.
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Sync Calendar table class.
 *
 * @package GeoDirectory
 * @subpackage GeoDirectory Bookings
 * @version 1.0.0
 * @since   1.0.0
 */
class GeoDir_Booking_Sync_Calendar_Table extends WP_List_Table {

	/**
	 * Query object.
	 *
	 * @var WP_Query
	 */
	public $query;

	/**
	 * Total number of bookings.
	 *
	 * @var int
	 */
	public $total_count;

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
				'singular' => __( 'Calendar', 'geodir-booking' ),
				'plural'   => __( 'Calendars', 'geodir-booking' ),
				'ajax'     => false,
			)
		);

		$this->process_bulk_action();

		$this->prepare_query();
	}

	/**
	 * Prepares the display query.
	 */
	public function prepare_query() {
		global $wpdb, $plugin_prefix;

		$this->query = geodir_booking_get_bookable_listings_query(
			array(
				'number'  => $this->per_page,
				'paged'   => $this->get_paged(),
				'orderby' => isset( $_GET['orderby'] ) ? $_GET['orderby'] : 'id',
				'order'   => isset( $_GET['order'] ) ? $_GET['order'] : 'DESC',
			)
		);

		$this->items       = $this->query->get_posts();
		$this->total_count = $this->query->found_posts;
	}

	/**
	 * Retrieve the current page number.
	 *
	 * @return int
	 */
	public function get_paged() {
		return isset( $_GET['paged'] ) ? absint( (int) $_GET['paged'] ) : 1;
	}

	/**
	 * Handles data query and filter, sorting, and pagination.
	 */
	public function prepare_items() {
		$columns  = $this->get_columns();
		$hidden   = array();
		$sortable = $this->get_sortable_columns();

		$this->_column_headers = array( $columns, $hidden, $sortable );

		$this->set_pagination_args(
			array(
				'total_items' => $this->total_count,
				'per_page'    => $this->per_page,
				'total_pages' => ceil( $this->total_count / $this->per_page ),
			)
		);
	}

	/**
	 * Displays a message when no bookable places are available.
	 */
	public function no_items() {
		esc_html_e( 'No bookable places available.', 'geodir-booking' );
	}

	/**
	 * Retrieve the table columns.
	 *
	 * @return array Array of all the list table columns.
	 */
	public function get_columns() {
		$columns = array(
			'cb'     => '<input type="checkbox" />',
			'title'  => __( 'Listing', 'geodir-booking' ),
			'export' => __( 'Export', 'geodir-booking' ),
			'import' => __( 'External Calendars', 'geodir-booking' ),
		);

		return $columns;
	}

	/**
	 * Render a column when no column specific method exists.
	 *
	 * @param object $item Row data.
	 * @param string $column_name Column name.
	 * @return mixed
	 */
	public function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			default:
				return '<div>&mdash;</div>';
		}
	}

	/**
	 * Render the bulk edit checkbox.
	 *
	 * @param object $item Row data.
	 * @return string
	 */
	public function column_cb( $item ) {
		return sprintf( '<input type="checkbox" name="ids[]" value="%s" />', esc_attr( $item->ID ) );
	}

	/**
	 * Render the title column.
	 *
	 * @param object $item Row data.
	 * @return string
	 */
	public function column_title( $item ) {
		$edit_url = esc_url(
			add_query_arg(
				array(
					'page'       => 'geodir-booking-external-ical',
					'listing_id' => $item->ID,
				)
			)
		);

		$upload_url = esc_url(
			add_query_arg(
				array(
					'page'       => 'geodir-booking-ical-import',
					'action'     => 'upload',
					'listing_id' => $item->ID,
				)
			)
		);

		$sync_url = esc_url(
			add_query_arg(
				array(
					'page'       => 'geodir-booking-ical-import',
					'action'     => 'sync',
					'listing_id' => $item->ID,
				)
			)
		);

		$actions = array(
			'edit'   => sprintf( '<a href="%s">%s</a>', esc_url( $edit_url ), __( 'Edit', 'geodir-booking' ) ),
			'upload' => sprintf( '<a href="%s">%s</a>', esc_url( $upload_url ), __( 'Import Calendar', 'geodir-booking' ) ),
			'sync'   => sprintf( '<a href="%s">%s</a>', esc_url( $sync_url ), __( 'Sync External Calendars', 'geodir-booking' ) ),
		);

		$title = '<strong><a href="' . esc_url( $edit_url ) . '">' . ( ! empty( $item->post_title ) ? $item->post_title : _x( '(no title)', 'Placeholder for empty place title', 'geodir-booking' ) ) . '</a></strong>';

		$item = geodir_get_post_info( $item->ID );
		if ( $item && isset( $item->property_type ) ) {
			$title .= '<span style="color:#999">' . $item->property_type . '</span>';
		}

		return $title . $this->row_actions( $actions );
	}

	/**
	 * Render the export column.
	 *
	 * @param object $item Row data.
	 * @return string
	 */
	public function column_export( $item ) {
		$query_args = array(
			'feed'       => 'gdbooking.ics',
			'listing_id' => $item->ID,
		);

		$ics_url = add_query_arg( $query_args, site_url( '/' ) );

		$export  = ' <code>' . esc_url( $ics_url ) . '</code>';
		$export .= '<p><a href="' . esc_url( $ics_url ) . '">' . __( 'Download Calendar', 'geodir-booking' ) . '</a></p>';

		return $export;
	}

	/**
	 * Render the import column.
	 *
	 * @param object $item Row data.
	 * @return string
	 */
	public function column_import( $item ) {
		$sync_urls = GeoDir_Booking_Sync_Urls::instance()->get_urls( $item->ID );

		if ( ! empty( $sync_urls ) ) {
			return implode( '<br/>', $sync_urls );
		} else {
			return $this->column_default( $item, 'import' );
		}
	}

	/**
	 * Columns to make sortable.
	 *
	 * @return array
	 */
	public function get_sortable_columns() {
		$sortable = array(
			'title' => array( 'title', true ),
		);

		return $sortable;
	}

	/**
	 * Returns an associative array containing the bulk action.
	 *
	 * @return array
	 */
	public function get_bulk_actions() {
		$actions = array(
			'sync' => __( 'Sync External Calendars', 'geodir-booking' ),
		);

		return $actions;
	}

	/**
	 * Process actions of the table.
	 */
	public function process_bulk_action() {
		$action = $this->current_action();
		if ( empty( $action ) ) {
			return;
		}

		check_admin_referer( 'bulk-' . $this->get_plural() );

		switch ( $action ) {
			case 'sync':
				$ids = isset( $_POST['ids'] ) ? array_map( 'absint', $_POST['ids'] ) : array();
				$ids = array_filter( $ids );

				if ( ! empty( $ids ) ) {
					$import_url = add_query_arg(
						array(
							'page'        => 'geodir-booking-ical-import',
							'action'      => 'sync',
							'listing_ids' => implode( ',', $ids ),
						),
						admin_url( 'admin.php' )
					);

					wp_redirect( $import_url );
					exit;
				}

				break;
		}
	}

	/**
	 * Retrieve the plural label for the table.
	 *
	 * @return string
	 */
	public function get_plural() {
		return $this->_args['plural'];
	}
}
