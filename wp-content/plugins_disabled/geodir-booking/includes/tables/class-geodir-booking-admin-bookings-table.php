<?php

/**
 * Displays a list of all bookings.
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WP_List_Table' ) ) {
	include_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Bookings table class.
 */
class GeoDir_Booking_Admin_Bookings_Table extends WP_List_Table {

	/**
	 * Query
	 *
	 * @var   WP_Query
	 * @since 1.0.0
	 */
	public $query;

	/**
	 * Total bookings
	 *
	 * @var   int
	 * @since 1.0.0
	 */
	public $total;

	/**
	 * Per page.
	 *
	 * @var   int
	 * @since 1.0.0
	 */
	public $per_page = 20;

	/**
	 *  Constructor function.
	 */
	public function __construct() {

		parent::__construct(
			array(
				'singular' => 'id',
				'plural'   => 'ids',
			)
		);

		$this->per_page = $this->get_items_per_page( 'geodir_bookings_per_page', 20 );

		$this->process_bulk_action();

		$this->prepare_query();

		$this->prepare_items();
	}

	/**
	 *  Processes a bulk action.
	 */
	public function process_bulk_action() {
		$action = 'bulk-' . $this->_args['plural'];

		if ( ! ( ! empty( $_GET['id'] ) && is_array( $_GET['id'] ) && ! empty( $_GET['_wpnonce'] ) && wp_verify_nonce( $_GET['_wpnonce'], $action ) ) ) {
			return;
		} 

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$action = $this->current_action();
		$deleted = 0;

		if ( 'delete' === $action ) {
			foreach ( $_GET['id'] as $id ) {
				if ( geodir_delete_booking( $id ) ) {
					$deleted++;
				}
			}
		}

		if ( $deleted > 1 ) {
			echo '<div class="updated"><p>' . esc_html( wp_sprintf( __( '%d bookings deleted.', 'geodir-booking' ), $deleted ) ) . '</p></div>';
		} else if ( $deleted == 1 ) {
			echo '<div class="updated"><p>' . esc_html__( 'Booking deleted.', 'geodir-booking' ) . '</p></div>';
		}

		do_action( 'geodir_bookings_process_bulk_action', $action, $this );
	}

	/**
	 *  Prepares the display query
	 */
	public function prepare_query() {
		global $gd_bookings_last_query_count;

		$this->items = geodir_get_bookings(
			array(
				'listings' => isset( $_GET['listings'] ) ? wp_parse_id_list( rawurldecode( $_GET['listings'] ) ) : array(),
				'email'    => 0,
				'limit'    => 0,
				'paged'    => $this->get_pagenum(),
				'orderby'  => isset( $_GET['orderby'] ) ? sanitize_text_field( rawurldecode( $_GET['orderby'] ) ) : 'id',
				'order'    => isset( $_GET['order'] ) ? sanitize_text_field( rawurldecode( $_GET['order'] ) ) : 'DESC',
				'search'   => isset( $_GET['s'] ) ? sanitize_text_field( rawurldecode( $_GET['s'] ) ) : '',
				'count'    => true,
			)
		);

		$this->total = (int) $gd_bookings_last_query_count;
	}

	/**
	 * Displays a column.
	 *
	 * @param GeoDir_Customer_Booking $item item.
	 * @param string $column_name column name.
	 */
	public function column_default( $item, $column_name ) {

		switch ( $column_name ) {

			case 'created':
				echo esc_html( geodir_booking_date( $item->created ) );
				break;

			case 'start_date':
			case 'end_date':
				echo esc_html( geodir_booking_date( $item->$column_name, 'view_day' ) );
				break;

			case 'site_commission':
			case 'payable_amount':
			case 'service_fee':
				echo wp_kses_post( wpinv_price( $item->$column_name ) );

		}

		do_action( "geodir_display_booking_table_$column_name", $item );
	}

	/**
	 * Displays the booking status column.
	 *
	 * @param GeoDir_Customer_Booking $item item.
	 * @return string
	 */
	public function column_status( $item ) {
		return $item->get_status_html();
	}

	/**
	 * Displays the listing column.
	 *
	 * @param GeoDir_Customer_Booking $item item.
	 * @return string
	 */
	public function column_listing( $item ) {

		$listing_id = $item->listing_id;

		if ( $listing_id ) {
			$listing = get_post( $listing_id );

			if ( $listing ) {
				$listing_title = $listing->post_title;
				$listing_link  = get_edit_post_link( $listing_id );
				$listing_link  = '<a href="' . esc_url( $listing_link ) . '">' . esc_html( $listing_title ) . '</a>';
				return $listing_link;
			}
		}

		return '&mdash;';
	}

	/**
	 * Displays the customer column.
	 *
	 * @param GeoDir_Customer_Booking $item item.
	 * @return string
	 */
	public function column_name( $item ) {
		global $aui_bs5;

		return sprintf(
			'<div class="d-flex">
				<div class="image ' . ( $aui_bs5 ? 'me-2' : 'mr-2' ) . '">%s</div>
				<div class="content text-break overflow-hidden">
					<div class="row-title">%s</div>
					%s
					%s
					<div class="row-actions">%s</div>
				</div>
			</div>',
			get_avatar( $item->email, 32, '', $item->name, array( 'class' => 'rounded-circle' ) ),
			sprintf(
				'<a href="%s">%s</a>',
				esc_url( $item->get_manage_url() ),
				esc_html( $item->name )
			),
			sprintf(
				'<a href="mailto:%1$s" class="email text-muted form-text overflow-hidden">%1$s</a>',
				esc_html( $item->email )
			),
			sprintf(
				'<a href="tel:%1$s" class="phone text-muted form-text overflow-hidden">%1$s</a>',
				esc_html( $item->phone )
			),
			$this->get_booking_row_actions( $item )
		);
	}

	/**
	 * Returns available row actions.
	 *
	 * @param GeoDir_Customer_Booking $item item.
	 * @return string
	 */
	public function get_booking_row_actions( $item ) {

		$actions = array(

			'id'     => sprintf(
				// translators: Order ID.
				esc_html__( 'ID: %d', 'geodir-booking' ),
				absint( $item->id )
			),

			'manage' => sprintf(
				'<a href="%s">%s</a>',
				esc_url( $item->get_manage_url() ),
				esc_html__( 'Manage', 'geodir-booking' )
			),

			'delete' => sprintf(
				'<a href="%s" onclick="return confirm(\'%s\')">%s</a>',
				esc_url(
					add_query_arg(
						array(
							'page'       => 'geodir-booking',
							'gd-booking' => absint( $item->id ),
							'action'     => 'delete',
							'nonce'      => wp_create_nonce( 'gd_booking_delete_' . $item->id ),
						),
						admin_url( 'admin.php' )
					)
				), 
				esc_attr__( 'Are you sure you want to delete this booking?', 'geodir-booking' ),
				esc_html__( 'Delete', 'geodir-booking' )
			),

		);

		return $this->row_actions( apply_filters( 'geodir_booking_row_actions', $actions, $item ) );
	}

	/**
	 * This is how checkbox column renders.
	 *
	 * @param  GeoDir_Customer_Booking $item item.
	 * @return string
	 */
	public function column_cb( $item ) {
		return sprintf( '<input type="checkbox" name="id[]" value="%s" />', esc_html( $item->id ) );
	}

	/**
	 * [OPTIONAL] Return array of bulk actions if has any
	 *
	 * @return array
	 */
	public function get_bulk_actions() {

		$actions = array(
			'delete' => __( 'Delete', 'geodir-booking' ),
		);

		/**
		 * Filters the bulk table actions shown on the bookings table.
		 *
		 * @param array $actions An array of bulk actions.
		 */
		return apply_filters( 'manage_geodir_bookings_table_bulk_actions', $actions );
	}

	/**
	 * Whether the table has items to display or not
	 *
	 * @return bool
	 */
	public function has_items() {
		return ! empty( $this->total );
	}

	/**
	 * Fetch data from the database to render on view.
	 */
	public function prepare_items() {

		$columns  = $this->get_columns();
		$hidden   = array();
		$sortable = $this->get_sortable_columns();

		$this->_column_headers = array( $columns, $hidden, $sortable );

		$this->set_pagination_args(
			array(
				'total_items' => $this->total,
				'per_page'    => $this->per_page,
				'total_pages' => ceil( $this->total / $this->per_page ),
			)
		);
	}

	/**
	 * Table columns.
	 *
	 * @return array
	 */
	public function get_columns() {
		$columns = array(
			'cb'              => '<input type="checkbox" />',
			'name'            => __( 'Client', 'geodir-booking' ),
			'listing'         => __( 'Listing', 'geodir-booking' ),
			'payable_amount'  => __( 'Payable Amount', 'geodir-booking' ),
			'service_fee'     => __( 'Service Fee', 'geodir-booking' ),
			'site_commission' => __( 'Site Commission', 'geodir-booking' ),
			'status'          => __( 'Status', 'geodir-booking' ),
			'created'         => __( 'Booked', 'geodir-booking' ),
			'start_date'      => __( 'Check-in', 'geodir-booking' ),
			'end_date'        => __( 'Check-out', 'geodir-booking' ),
		);

		/**
		 * Filters the columns shown in the bookings list table.
		 *
		 * @param array $columns Bookings table columns.
		 */
		return apply_filters( 'manage_geodir_bookings_table_columns', $columns );
	}

	/**
	 * Table sortable columns.
	 *
	 * @return array
	 */
	public function get_sortable_columns() {
		$sortable = array(
			'name'            => array( 'name', false ),
			'created'         => array( 'created', true ),
			'start_date'      => array( 'start_date', true ),
			'end_date'        => array( 'end_date', true ),
			'status'          => array( 'status', false ),
			'site_commission' => array( 'site_commission', false ),
			'payable_amount'  => array( 'payable_amount', false ),
			'service_fee'     => array( 'service_fee', false ),
		);

		/**
		 * Filters the sortable columns in the bookings table.
		 *
		 * @param array $sortable An array of sortable columns.
		 */
		return apply_filters( 'manage_geodir_bookings_sortable_table_columns', $sortable );
	}
}
