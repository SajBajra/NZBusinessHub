<?php
/**
 * Displays a list of all tickets
 */

if ( ! class_exists( 'WP_List_Table' ) ) {
	include_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Tickets table class.
 */
class GeoDir_Tickets_List_Table extends WP_List_Table {

	/**
	 * Query
	 *
	 * @var   string
	 * @since 1.0.0
	 */
	public $query;

	/**
	 * Total tickets
	 *
	 * @var   int
	 * @since 1.0.0
	 */
	public $total;

	/**
	 * Number of tickets to display per page.
	 *
	 * @var   int
	 * @since 1.0.0
	 */
	public $per_page = 10;

	/**
	 *  Constructor function.
	 */
	public function __construct() {

		$per_page = absint( get_user_meta( get_current_user_id(), 'geodir_tickets_per_page', true ) );

		if ( ! empty( $per_page ) ) {
			$this->per_page = $per_page;
		}

		parent::__construct(
			array(
				'singular' => 'id',
				'plural'   => 'ids',
			)
		);

		$this->process_bulk_action();

		$this->prepare_query();

	}

	/**
	 *  Processes a bulk action.
	 */
	public function process_bulk_action() {

		$action = 'bulk-' . $this->_args['plural'];

		if ( empty( $_POST['id'] ) || empty( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], $action ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$action = $this->current_action();

		switch ( $action ) {

			case 'delete':
				$tickets = geodir_get_tickets( array( 'invoice_in' => wp_parse_id_list( $_POST['id'] ) ) );

				foreach ( $tickets as $ticket ) {
					$ticket->delete();
				}

				break;

			case 'refund':

				foreach ( wp_parse_id_list( $_POST['id'] ) as $invoice_id ) {
					$invoice = wpinv_get_invoice( $invoice_id );

					if ( ! empty( $invoice ) ) {
						$invoice->set_status( 'wpi-refunded' );
						$invoice->save();
					}
				}

				break;

			case 'resend':

				foreach ( wp_parse_id_list( $_POST['id'] ) as $invoice_id ) {
					$invoice = wpinv_get_invoice( $invoice_id );

					if ( ! empty( $invoice ) && $invoice->is_paid() ) {

						$tickets = geodir_get_tickets(
							array( 'invoice_in' => array( $invoice->get_id() ), )
						);

						$GLOBALS['geodir_tickets']->send_user_tickets_email(
							$invoice,
							new GetPaid_Notification_Email( 'user_tickets' ),
							$tickets
						);

					}

				}

				break;

		}

	}

	/**
	 *  Prepares the display query
	 */
	public function prepare_query() {
		global $wpdb;

		// Search.
		if ( ! empty( $_POST['s'] ) ) {
			// TODO.
		}

		$where    = 'WHERE 1=1';

		if ( ! empty( $_POST['geodir_tickets_filter_action'] ) ) {

			if ( ! empty( $_POST['seller'] ) ) {
				$seller  = (int) $_POST['seller'];
				$where  .= " AND seller_id=$seller";
			}

			if ( ! empty( $_POST['buyer'] ) ) {
				$buyer  = (int) $_POST['buyer'];
				$where .= " AND buyer_id=$buyer";
			}

			if ( ! empty( $_POST['event'] ) ) {
				$event  = (int) $_POST['event'];
				$where .= " AND event_id=$event";
			}

			if ( ! empty( $_POST['date'] ) ) {
				$m      = preg_replace( '|[^0-9]|', '', $_POST['date'] );
				$where .= " AND YEAR(date_created)=" . substr( $m, 0, 4 );
				$where .= " AND MONTH(date_created)=" . substr( $m, 4, 2 );
			}

		}

		$order_by = isset( $_GET['orderby'] ) ? sanitize_key( $_GET['orderby'] ) : 'date_created';
		$order    = isset( $_GET['order'] ) && 'asc' === strtolower( $_GET['order'] ) ? 'ASC' : 'DESC';
		$table    = $wpdb->prefix . 'geodir_tickets';
		$tickets  = $wpdb->get_results(
			"SELECT SQL_CALC_FOUND_ROWS *, COUNT(id) as qty, SUM(seller_price) as seller_price, SUM(site_commision) as site_commision, SUM(price) as price FROM $table $where GROUP BY invoice_id ORDER BY $order_by $order;"
		);

		$this->items = $tickets;
		$this->total = (int) $wpdb->get_var( "SELECT FOUND_ROWS()" );

	}

	/**
	 * Default columns.
	 *
	 * @param object $ticket Ticket row.
	 * @param string $column_name column name.
	 */
	public function column_default( $ticket, $column_name ) {
		do_action( "geodir_display_tickets_table_$column_name", $ticket );
	}

	/**
	 * This is how checkbox column renders.
	 *
	 * @param  object $item item.
	 * @return HTML
	 */
	public function column_cb( $item ) {
		return sprintf( '<input type="checkbox" name="id[]" value="%s" />', esc_html( $item->invoice_id ) );
	}

	/**
	 * Displays event title.
	 *
	 * @param  object $item item.
	 * @return HTML
	 */
	public function column_title( $item ) {
		$value = sprintf(
			'<a href="%s"><strong>%s</strong></a>',
			get_edit_post_link( (int) $item->event_id, 'raw' ),
			esc_html( get_the_title( $item->event_id ) )
		);

		$event_dates = geodir_get_invoice_ticket_dates( $item->invoice_id, $item->event_id );
		$event_date = '';

		if ( ! empty( $event_dates ) ) {
			$start_date = '';
			$end_date = '';
			$start_time = '';
			$end_time = '';

			if ( is_array( $event_dates ) && count( $event_dates ) > 3 ) {
				$start_date = $event_dates[0];
				$end_date = $event_dates[1];
				$start_time = $event_dates[2];
				$end_time = $event_dates[3];
			} else if ( is_array( $event_dates ) && count( $event_dates ) == 1 ) {
				$start_date = $event_dates[0];
			} else if ( is_scalar( $event_dates ) ) {
				$start_date = $event_dates;
			}

			if ( $start_date ) {
				$event_date = geodir_ticket_format_event_date( $start_date, $end_date );
				$event_date = '<span class="geodir-ticket-date text-nowrap">' . $event_date . ' </span>';

				if ( $start_time && ( $event_time = geodir_ticket_format_event_time( $start_time, $end_time ) ) ) {
					$event_date .= '<br><span class="geodir-ticket-time text-nowrap">' . $event_time . '</span>';
				}
			}

			if ( $event_date ) {
				$value .= '<div class="bsui"><small class="fs-xs text-sm lh-sm d-block">' . wp_kses_post( $event_date ) . '</small></div>';
			}
		}

		return $value;
	}

	/**
	 * Displays ticket buyer.
	 *
	 * @param  object $item item.
	 * @return HTML
	 */
	public function column_buyer( $item ) {
		$invoice = wpinv_get_invoice( $item->invoice_id );

		if ( ! empty( $invoice ) ) {
			return sprintf(
				'<a href="user-edit.php?user_id=%s">%s</a>',
				absint( $invoice->get_user_id() ),
				esc_html( $invoice->get_user_full_name() )
			);
		} else {
			return __( '(Missing User)', 'geodir-tickets' );
		}
	}

	/**
	 * Displays ticket seller.
	 *
	 * @param  object $item item.
	 * @return HTML
	 */
	public function column_seller( $item ) {

		$user = get_userdata( $item->seller_id );
		if ( $user ) {

			return sprintf(
				'<a href="user-edit.php?user_id=%s">%s</a>',
				absint( $user->ID ),
				! empty( $user->display_name ) ? sanitize_text_field( $user->display_name ) : sanitize_email( $user->user_email )
			);

		}

		return __( '(Missing User)', 'geodir-tickets' );
	}

	/**
	 * Displays the ticket quantity.
	 *
	 * @param  object $item item.
	 * @return HTML
	 */
	public function column_quantity( $item ) {
		return intval( $item->qty );
	}

	/**
	 * Displays the total price.
	 *
	 * @param  object $item item.
	 * @return HTML
	 */
	public function column_price( $item ) {
		$invoice = wpinv_get_invoice( $item->invoice_id );
		return wpinv_price( (float) $item->price, ( ! empty( $invoice ) ? $invoice->get_currency() : '' ) );
	}

	/**
	 * Displays the seller price.
	 *
	 * @param  object $item item.
	 * @return HTML
	 */
	public function column_seller_price( $item ) {
		$invoice = wpinv_get_invoice( $item->invoice_id );
		return wpinv_price( (float) $item->seller_price, ( ! empty( $invoice ) ? $invoice->get_currency() : '' ) );
	}

	/**
	 * Displays the site commission.
	 *
	 * @param  object $item item.
	 * @return HTML
	 */
	public function column_site_commision( $item ) {
		$invoice = wpinv_get_invoice( $item->invoice_id );
		return wpinv_price( (float) $item->site_commision, ( ! empty( $invoice ) ? $invoice->get_currency() : '' ) );
	}

	/**
	 * Displays the creation date.
	 *
	 * @param  object $item item.
	 * @return HTML
	 */
	public function column_date_created( $item ) {

		if ( ( DAY_IN_SECONDS + strtotime( $item->date_created ) ) > current_time( 'timestamp' ) ) {

			$date = sprintf(
				__( '%s ago', 'geodir-tickets' ),
				human_time_diff( strtotime( $item->date_created ), current_time( 'timestamp' ) )
			);

		} else {
			$date = getpaid_format_date( $item->date_created, true );
		}

		return sprintf(
			'%s <div style="color: grey;">%s</div>',
			__( 'Booked', 'geodir-tickets' ),
			$date
		);

	}

	/**
	 * Displays the actions column.
	 *
	 * @param  object $item item.
	 * @return HTML
	 */
	public function column_action( $item ) {

		$actions = array(
			'<a href="' . esc_url( get_permalink( $item->event_id ) ) . '"><i class="fas fa-calendar-week"></i> &nbsp;' . __( 'View Event', 'geodir-tickets' ) . '</a>',
			'<a href="' . get_edit_post_link( $item->invoice_id, 'raw' ) . '"><i class="fas fa-file-invoice"></i> &nbsp;' . __( 'View Invoice', 'geodir-tickets' ) . '</a>'
		);

		$invoice = wpinv_get_invoice( $item->invoice_id );

		if ( $invoice && $invoice->is_paid() ) {

			$actions[] = sprintf(
				'<a href="%s"><i class="fas fa-share"></i> &nbsp; %s</a>',
				esc_url(
					wp_nonce_url(
						add_query_arg(
							array(
								'getpaid-admin-action' => 'refund_ticket',
								'invoice_id'           => $item->invoice_id,
							)
						),
						'getpaid-nonce',
						'getpaid-nonce'
					)
				),
				__( 'Refund', 'geodir-tickets' )
			);

			$actions[] = sprintf(
				'<a href="%s"><i class="fas fa-sync"></i> &nbsp; %s</a>',
				esc_url(
					wp_nonce_url(
						add_query_arg(
							array(
								'getpaid-admin-action' => 'resend_ticket',
								'invoice_id'           => $item->invoice_id,
							)
						),
						'getpaid-nonce',
						'getpaid-nonce'
					)
				),
				__( 'Resend', 'geodir-tickets' )
			);

		}

		if ( geodir_tickets_should_display_download_button( $invoice ) && ( $download_url = geodir_tickets_get_download_url( $invoice ) ) ) {

			$actions[] = sprintf(
				'<a href="%s" style="color: red;" target="_blank"><i class="fas fa-eye"></i> &nbsp; %s</a>',
				$download_url,
				__( 'View Tickets', 'geodir-tickets' )
			);

		}

		$actions[] = sprintf(
			'<a href="%s" style="color: red;" onclick="return confirm(\'%s\')"><i class="fas fa-trash"></i> &nbsp; %s</a>',
			esc_url(
				wp_nonce_url(
					add_query_arg(
						array(
							'getpaid-admin-action' => 'delete_ticket',
							'invoice_id'           => $item->invoice_id,
						)
					),
					'getpaid-nonce',
					'getpaid-nonce'
				)
			),
			esc_attr__( 'Are you sure you want to delete the ticket?', 'geodir-tickets' ),
			__( 'Delete', 'geodir-tickets' )
		);

		return sprintf(
			'<div class="bsui">
				<button class="gp-ticket-action-button" onclick="return false;"><i class="fas fa-2x fa-ellipsis-h text-muted"></i></button>
				<span class="d-none gp-ticket-action-content"><ul><li>%s</li><ul></span>
			</div>',
			implode( '</li><li>', $actions )
		);

	}

	/**
	 * Displays the invoice status.
	 *
	 * @param  object $item item.
	 * @return HTML
	 */
	public function column_status( $item ) {

		$invoice      = wpinv_get_invoice( $item->invoice_id );
		if ( empty( $invoice ) ) {
			echo '-';
			return;
		}

		$status       = sanitize_html_class( $invoice->get_status() );
		$status_label = esc_html( $invoice->get_status_nicename() );

		// If it is paid, show the gateway title.
		if ( $invoice->is_paid() ) {
			$gateway = sanitize_text_field( $invoice->get_gateway_title() );
			$gateway = wp_sprintf( esc_attr__( 'Paid via %s', 'geodir-tickets' ), $gateway );

			echo "<mark class='wpi-help-tip getpaid-invoice-status $status' title='$gateway'><span>$status_label</span></mark>";
		} else {
			echo "<mark class='getpaid-invoice-status $status'><span>$status_label</span></mark>";
		}

	}
				
	/**
	 * [OPTIONAL] Return array of built actions if has any
	 *
	 * @return array
	 */
	public function get_bulk_actions() {

		$actions = array(
			'refund' => __( 'Refund', 'geodir-tickets' ),
			'resend' => __( 'Resend', 'geodir-tickets' ),
			'delete' => __( 'Delete', 'geodir-tickets' ),
		);

		return apply_filters( 'manage_geodir_tickets_table_bulk_actions', $actions );

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
			'cb'             => '<input type="checkbox" />',
			'title'          => __( 'Event', 'geodir-tickets' ),
			'seller'         => __( 'Seller', 'geodir-tickets' ),
			'buyer'          => __( 'Buyer', 'geodir-tickets' ),
			'quantity'       => __( 'Quantity', 'geodir-tickets' ),
			'price'          => __( 'Price', 'geodir-tickets' ),
			'seller_price'   => __( 'Seller Earnings', 'geodir-tickets' ),
			'site_commision' => __( 'Site Commision', 'geodir-tickets' ),
			'status'         => __( 'Status', 'geodir-tickets' ),
			'action'         => __( 'Action', 'geodir-tickets' ),
			'date_created'   => __( 'Date', 'geodir-tickets' ),
		);

		return apply_filters( 'manage_geodir_tickets_table_columns', $columns );
	}

	/**
	 * Table sortable columns.
	 *
	 * @return array
	 */
	public function get_sortable_columns() {
		$sortable = array(
			'id'             => array( 'id', true ),
			'price'          => array( 'price', true ),
			'date_created'   => array( 'date_created', true ),
			'site_commision' => array( 'site_commision', true ),
			'seller_price'   => array( 'seller_price', true ),
			'quantity'       => array( 'qty', true ),
		);

		return apply_filters( 'manage_geodir_tickets_sortable_table_columns', $sortable );
	}

	/**
	 * Generates the table navigation above or below the table
	 *
	 * @since 3.1.0
	 * @param string $which
	 */
	protected function display_tablenav( $which ) {
		if ( 'top' === $which ) {
			parent::display_tablenav( $which );
			echo '<div id="geodir-tickets-table-wrap">';
		} else {
			echo '</div>';
			parent::display_tablenav( $which );
		}
	}

	/**
	 * @param string $which
	 */
	protected function extra_tablenav( $which ) {
		?>
		<div class="alignleft actions">
			<?php
				if ( 'top' === $which ) {
					ob_start();

					$this->ticket_months_dropdown();
					$this->ticket_buyers_dropdown();
					$this->ticket_sellers_dropdown();
					$this->ticket_events_dropdown();

					do_action( 'geodir_manage_tickets_filter' );

					$output = ob_get_clean();

					if ( ! empty( $output ) ) {
						echo $output;
						submit_button( __( 'Filter', 'geodir-tickets' ), '', 'geodir_tickets_filter_action', false, array( 'id' => 'geodir-tickets-filter-submit' ) );
					}

				}
			?>
		</div>
		<?php

	}

	/**
	 * Displays a dropdown for filtering items in the list table by month.
	 *
	 * @since 3.1.0
	 *
	 * @global wpdb      $wpdb      WordPress database abstraction object.
	 * @global WP_Locale $wp_locale WordPress date and time locale object.
	 *
	 */
	protected function ticket_months_dropdown() {
		global $wpdb, $wp_locale;

		$table       = $wpdb->prefix . 'geodir_tickets';
		$months      = $wpdb->get_results( "SELECT DISTINCT YEAR( date_created ) AS year, MONTH( date_created ) AS month FROM $table ORDER BY date_created DESC" );
		$month_count = count( $months );

		if ( ! $month_count || ( 1 == $month_count && 0 == $months[0]->month ) ) {
			return;
		}

		$m           = isset( $_POST['date'] ) ? (int) $_POST['date'] : 0;

		?>
		<label for="filter-by-date" class="screen-reader-text"><?php _e( 'Filter by date', 'geodir-tickets' ); ?></label>
		<select name="date" id="filter-by-date">
			<option<?php selected( $m, 0 ); ?> value="0"><?php _e( 'All dates', 'geodir-tickets' ); ?></option>
			<?php
				foreach ( $months as $arc_row ) {
					if ( 0 == $arc_row->year ) {
						continue;
					}

					$month = zeroise( $arc_row->month, 2 );
					$year  = $arc_row->year;

					printf(
						"<option %s value='%s'>%s</option>\n",
						selected( $m, $year . $month, false ),
						esc_attr( $arc_row->year . $month ),
						/* translators: 1: Month name, 2: 4-digit year. */
						sprintf( __( '%1$s %2$d', 'geodir-tickets' ), $wp_locale->get_month( $month ), $year )
					);
				}
			?>
		</select>
		<?php
	}

	/**
	 * Displays a dropdown for filtering items in the list table by buyer.
	 *
	 * @since 3.1.0
	 *
	 * @global wpdb      $wpdb      WordPress database abstraction object.
	 * @global WP_Locale $wp_locale WordPress date and time locale object.
	 *
	 */
	protected function ticket_buyers_dropdown() {
		global $wpdb;

		$table  = $wpdb->prefix . 'geodir_tickets';
		$buyers = $wpdb->get_col( "SELECT DISTINCT buyer_id FROM $table" );

		if ( empty( $buyers ) ) {
			return;
		}

		printf(
			'<label for="filter-by-buyer" class="screen-reader-text">%s</label>',
			__( 'Filter by buyer', 'geodir-tickets' )
		);

		wp_dropdown_users(
			array(
				'include'           => wp_parse_id_list( $buyers ),
				'selected'          => isset( $_POST['buyer'] ) ? (int) $_POST['buyer'] : 0,
				'name'              => 'buyer',
				'id'                => 'filter-by-buyer',
				'show_option_all'   => __( 'All Buyers', 'geodir-tickets' ),
			)
		);

	}

	/**
	 * Displays a dropdown for filtering items in the list table by seller.
	 *
	 * @since 3.1.0
	 *
	 * @global wpdb      $wpdb      WordPress database abstraction object.
	 * @global WP_Locale $wp_locale WordPress date and time locale object.
	 *
	 */
	protected function ticket_sellers_dropdown() {
		global $wpdb;

		$table   = $wpdb->prefix . 'geodir_tickets';
		$sellers = $wpdb->get_col( "SELECT DISTINCT seller_id FROM $table" );

		if ( empty( $sellers ) ) {
			return;
		}

		printf(
			'<label for="filter-by-seller" class="screen-reader-text">%s</label>',
			__( 'Filter by seller', 'geodir-tickets' )
		);

		wp_dropdown_users(
			array(
				'include'           => wp_parse_id_list( $sellers ),
				'selected'          => isset( $_POST['seller'] ) ? (int) $_POST['seller'] : 0,
				'name'              => 'seller',
				'id'                => 'filter-by-seller',
				'show_option_all'   => __( 'All Sellers', 'geodir-tickets' ),
			)
		);

	}

	/**
	 * Displays a dropdown for filtering items in the list table by event.
	 *
	 * @since 3.1.0
	 *
	 * @global wpdb      $wpdb      WordPress database abstraction object.
	 * @global WP_Locale $wp_locale WordPress date and time locale object.
	 *
	 */
	protected function ticket_events_dropdown() {
		global $wpdb;

		$table  = $wpdb->prefix . 'geodir_tickets';
		$events = $wpdb->get_col( "SELECT DISTINCT event_id FROM $table" );

		if ( empty( $events ) ) {
			return;
		}

		$event = isset( $_POST['event'] ) ? (int) $_POST['event'] : 0;
		?>
		<label for="filter-by-event" class="screen-reader-text"><?php _e( 'Filter by event', 'geodir-tickets' ); ?></label>
		<select name="event" id="filter-by-event">
			<option<?php selected( $event, 0 ); ?> value="0"><?php _e( 'All events', 'geodir-tickets' ); ?></option>
			<?php
				foreach ( $events as $event_id ) {

					$title = get_the_title( $event_id );

					if ( ! empty( $title ) ) {
						printf(
							"<option %s value='%s'>%s</option>\n",
							selected( (int) $event_id, $event, false ),
							esc_attr( $event_id ),
							esc_html( $title )
						);
					}

				}
			?>
		</select>
		<?php

	}

}
