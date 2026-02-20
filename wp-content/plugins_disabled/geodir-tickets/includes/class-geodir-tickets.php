<?php
/**
 * Tickets manager database class.
 *
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Tickets manager database class.
 *
 */
class GeoDir_Tickets {

	/**
	 * Main admin class.
	 *
	 */
	public $admin;

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
		require_once plugin_dir_path( __FILE__ ) . 'class-geodir-tickets-admin.php';
		require_once plugin_dir_path( __FILE__ ) . 'class-geodir-tickets-query.php';
		require_once plugin_dir_path( __FILE__ ) . 'class-geodir-ticket.php';
		require_once plugin_dir_path( __FILE__ ) . 'class-geodir-tickets-data-store.php';

	}

	/**
	 * Inits the plugin.
	 *
	 */
	protected function init() {

		add_filter( 'getpaid_data_stores', array( $this, 'register_data_store' ) );
		add_action( 'getpaid_init', array( $this, 'maybe_install' ) );
		add_action( 'getpaid_widget_classes', array( $this, 'register_widget' ) );
		add_action( 'wp_footer',array( $this, 'maybe_load_scripts' ), 1 );
		add_action( 'wp_ajax_geodir_sell_tickets', array( $this, 'geodir_sell_tickets' ) );
		add_action( 'wp_ajax_geodir_delete_ticket', array( $this, 'geodir_delete_ticket' ) );
		add_action( 'wp_ajax_geodir_edit_ticket', array( $this, 'geodir_edit_ticket' ) );
		add_action( 'wp_ajax_geodir_resend_ticket', array( $this, 'geodir_resend_ticket' ) );
		add_action( 'wp_ajax_geodir_refund_ticket', array( $this, 'geodir_refund_ticket' ) );
		add_filter( 'wpinv_get_item_types', array( $this, 'filter_item_types' ) );
		add_action( 'getpaid_invoice_status_publish', array( $this, 'generate_invoice_tickets' ), 20, 2 );
		add_action( 'getpaid_invoice_status_changed', array( $this, 'maybe_deactivate_invoice_tickets' ), 10, 3 );
		add_action( 'wp_ajax_geodir_verify_ticket', array( $this, 'geodir_verify_ticket' ) );
		add_action( 'wp_ajax_geodir_redeem_ticket', array( $this, 'geodir_redeem_ticket' ) );
		add_action( 'geodir_generated_invoice_tickets', array( $this, 'send_ticket_emails' ), 10, 2 );
		add_action( 'getpaid_template_default_template_path', array( $this, 'maybe_filter_default_template_path' ), 10, 2 );
		add_filter( 'wpinv_get_emails', array( $this, 'register_email_settings' ) );
		add_filter( 'getpaid_inadiquate_stock_text', array( $this, 'fitler_stock_message' ), 10, 3 );
		add_filter( 'getpaid_out_of_stock_text', array( $this, 'fitler_out_of_stock_message' ), 10, 2 );
		add_filter( 'getpaid_in_stock_text', array( $this, 'fitler_stock_text' ), 10, 2 );
		add_action( 'getpaid_before_payment_form_items', array( $this, 'payment_form_date_select' ) );
		add_action( 'getpaid_checkout_invoice_updated', array( $this, 'maybe_save_ticket_dates' ) );
		$this->admin = new GeoDir_Tickets_Admin();

	}

	/**
	 * Registers the tickets data store.
	 *
	 * @param array $data_stores
	 * @return array
	 */
	public function register_data_store( $data_stores ) {
		$data_stores['ticket'] = 'GeoDir_Tickets_Data_Store';
		return $data_stores;
	}

	/**
     * Installs the plugin.
     *
     * @param array $tabs
     */
    public function maybe_install() {

        // Maybe upgrade the database.
        if ( get_option( 'geodir_tickets_db_version' ) != 1 ) {

            // Init the db installer/updater.
			require_once plugin_dir_path( __FILE__ ) . 'class-geodir-tickets-installer.php';
            new GeoDir_Tickets_Installer( (int) get_option( 'geodir_tickets_db_version' ) );
            update_option( 'geodir_tickets_db_version', 1 );

        }

    }

	/**
	 * Registers widgets.
	 *
	 */
	public function register_widget( $widgets ) {

		require_once plugin_dir_path( __FILE__ ) . 'widgets/sell-tickets.php';
		$widgets[] = 'GeoDir_Tickets_Sell_Tickets_Widget';
		return $widgets;

    }

	/**
	 * Loads scripts.
	 */
	public static function maybe_load_scripts() {

		if ( ! empty( $GLOBALS['geodir_ticket_modals'] ) ) {
			echo '<div class="bsui">';

			foreach ( $GLOBALS['geodir_ticket_modals'] as $modal ) {
				echo $modal;
			}

			echo '</div>';
		}

        if ( ! empty( $GLOBALS['geodir_tickets_load_scripts'] ) ) {
			$suffix       = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
			$url          = plugin_dir_url( __FILE__ ) . 'scripts/frontend' . $suffix . '.js';
			$version      = filemtime( plugin_dir_path( __FILE__ ) . 'scripts/frontend' . $suffix . '.js' );
			$dependancies = array( 'jquery' );

			if ( ! empty( $GLOBALS['geodir_tickets_load_management_scripts'] ) ) {
				$dependancies = array( 'jquery', 'chartjs', 'html5-qrcode' );
				wp_enqueue_script( 'html5-qrcode', plugin_dir_url( __FILE__ ) . 'scripts/html5-qrcode.min.js', array(), '2.0.11', true );
				wp_enqueue_script( 'chartjs', plugin_dir_url( __FILE__ ) . 'scripts/chart.min.js', array(), '3.5.1', true );

				wp_localize_script(
					'geodir-tickets',
					'geodir_tickets_code',
					array(
						'select_camera' => __( 'Select Camera', 'geodir-tickets' ),
					)
				);
			}

			wp_enqueue_script( 'geodir-tickets', $url, $dependancies, $version, true );

			wp_localize_script(
				'geodir-tickets',
				'geodir_tickets',
				array(
					'ajax_url'             => admin_url( 'admin-ajax.php' ),
					'i18n_ticket_refunded' => __( 'The ticket was refunded successfully', 'geodir-tickets' ),
					'i18n_ticket_reset' => __( 'The ticket was resent successfully', 'geodir-tickets' ),
					'i18n_success' => __( 'Success!', 'geodir-tickets' ),
					'i18n_error' => __( 'Error', 'geodir-tickets' ),
					'i18n_invalid_ticket' => __( 'Invalid Ticket', 'geodir-tickets' ),
					'i18n_confirm_delete' => esc_js( __( 'Are you sure you want to delete this ticket?', 'geodir-tickets' ) )
				)
			);

			wp_enqueue_script( 'bootstrap-table', plugin_dir_url( __FILE__ ) . 'scripts/bootstrap-table.min.js', array( 'geodir-tickets' ), 'v1.18.3', true );
			wp_enqueue_style( 'bootstrap-table', plugin_dir_url( __FILE__ ) . 'scripts/bootstrap-table.min.css', array(), 'v1.18.3' );
        }

	}

	public function current_user_can_manage_listing( $listing_author ) {
		return current_user_can( 'manage_options' ) || get_current_user_id() == $listing_author;
	}

	public function geodir_sell_tickets() {

        check_ajax_referer( 'geodir_sell_tickets_nonce' );

		if ( empty( $_POST['geodir']['ticket_names'] ) ) {
			exit;
		}

		$listing_id  = intval( $_POST['listing_id'] );
		$listing     = get_post( $listing_id );

		if ( empty( $listing ) || ! $this->current_user_can_manage_listing( $listing->post_author ) ) {
			exit;
		}

		$saved_items = array();
		foreach ( $_POST['geodir']['ticket_names'] as $index => $ticket_name ) {
			if ( empty( $ticket_name ) ) {
				continue;
			}

			$item = new WPInv_Item();
			$item->set_name( sanitize_text_field( wp_unslash( $ticket_name ) ) );
			$item->set_price( floatval( $_POST['geodir']['ticket_prices'][ $index ] ) );
			$item->set_type( 'ticket' );
			$item->set_status( 'publish' );
			$item->set_author( $listing->post_author );
			$item->save();

			if ( $item->exists() ) {
				update_post_meta( $item->get_id(), '_stock', intval( $_POST['geodir']['ticket_quantity'][ $index ] ) );
				update_post_meta( $item->get_id(), 'ticket_listing', intval( $_POST['listing_id'] ) );
				update_post_meta( $item->get_id(), 'selected_by_default', (int) isset( $_POST['geodir']['ticket_default'][ $index ] ) );
				update_post_meta( $item->get_id(), 'sell_till', ( isset( $_POST['geodir']['sell_tickets_till'][ $index ] ) && 'ends' === $_POST['geodir']['sell_tickets_till'][ $index ] ) ? 'ends' : 'starts' );
				$saved_items[] = $item->get_id();
			}
		}

		update_post_meta( intval( $_POST['listing_id'] ), 'listing_tickets', $saved_items );

        wp_send_json_success( true );

	}

	public function filter_item_types( $item_types ) {
		$item_types['ticket'] = __( 'Ticket', 'geodir-tickets' );
        return $item_types;
	}

	public function geodir_delete_ticket() {

		if ( empty( $_POST['item'] ) ) {
			exit;
		}

		$item_id = (int) $_POST['item'];
        check_ajax_referer( 'geodir_delete_ticket' . $item_id );

		$item = new WPInv_Item( $item_id );
		if ( ! $item->exists() || ! $this->current_user_can_manage_listing( $item->get_author() ) ) {
			exit;
		}

		$listing_id = get_post_meta( $item->get_id(), 'ticket_listing', true );

		$item->delete( true );

		$listing_items = array();

		foreach ( wp_parse_id_list( get_post_meta( $listing_id, 'listing_tickets', true ) ) as $_item_id ) {
			if ( $item_id != $_item_id ) {
				$listing_items[] = $_item_id;
			}
		}

		update_post_meta( $listing_id, 'listing_tickets', array_unique( $listing_items ) );

        wp_send_json_success( true );

	}

	public function geodir_edit_ticket() {

		check_ajax_referer( 'geodir_edit_ticket' );

		$listing_id  = intval( $_POST['listing_id'] );
		$listing     = get_post( $listing_id );

		if ( empty( $listing ) || ! $this->current_user_can_manage_listing( $listing->post_author ) ) {
			exit;
		}

		if ( empty( $_POST['geodir_item_id'] ) ) {
			$geodir_item = new WPInv_Item();
		} else {
			$geodir_item = new WPInv_Item( (int) $_POST['geodir_item_id'] );

			if ( ! $geodir_item->exists() || ! $this->current_user_can_manage_listing( $geodir_item->get_author() ) ) {
				exit;
			}
		}

		$geodir_item->set_name( sanitize_text_field( wp_unslash( $_POST['geodir_ticket_name'] ) ) );
		$geodir_item->set_price( floatval( $_POST['geodir_ticket_price'] ) );
		$geodir_item->set_type( 'ticket' );
		$geodir_item->set_status( 'publish' );
		$geodir_item->set_author( $listing->post_author );
		$geodir_item->save();

		if ( ! $geodir_item->exists() ) {
			exit;
		}

		update_post_meta( $geodir_item->get_id(), '_stock', intval( $_POST['geodir_ticket_quantity'] ) );
		update_post_meta( $geodir_item->get_id(), 'ticket_listing', intval( $listing_id ) );
		update_post_meta( $geodir_item->get_id(), 'selected_by_default', (int) ! empty( $_POST['geodir_ticket_selected_by_default'] ) );
		update_post_meta( $geodir_item->get_id(), 'sell_till', ( 'ends' === $_POST['geodir_sell_tickets_till'] ) ? 'ends' : 'starts' );

		$listing_items = wp_parse_id_list( get_post_meta( $listing_id, 'listing_tickets', true ) );
		$listing_items[] = $geodir_item->get_id();
		update_post_meta( $listing_id, 'listing_tickets', array_unique( $listing_items ) );

		ob_start();
		include plugin_dir_path( __FILE__ ) . 'views/manage-tickets/type-row.php';
		$row = ob_get_clean();

        wp_send_json_success(
			array(
				'replace' => '#geodir-manage-ticket-type-item-' . $geodir_item->get_id(),
				'row'     => $row,
			)
		);

	}

	public function geodir_resend_ticket() {

		$seller_id   = get_current_user_id();
		$invoice_id  = intval( $_POST['invoice'] );
		$invoice     = wpinv_get_invoice( $invoice_id );

		if ( ! $seller_id || ! $invoice->exists() || ! $invoice->is_paid() ) {
			exit;
		}

		check_ajax_referer( 'geodir_resend_invoice_tickets_' . $invoice_id );

		$tickets = geodir_get_tickets(
			array(
				'seller_in'  => array( $seller_id ),
				'invoice_in' => array( $invoice_id ),
			)
		);

		if ( empty( $tickets ) ) {
			wp_send_json_success();
		}

		$this->send_user_tickets_email(
			$invoice,
			new GetPaid_Notification_Email( 'user_tickets' ),
			$tickets
		);

		wp_send_json_success();
	}

	public function geodir_refund_ticket() {
		$seller_id   = get_current_user_id();
		$invoice_id  = intval( $_POST['invoice'] );
		$invoice     = wpinv_get_invoice( $invoice_id );

		if ( ! $seller_id || ! $invoice->exists() || ! $invoice->is_paid() ) {
			exit;
		}

		check_ajax_referer( 'geodir_refund_invoice_tickets_' . $invoice_id );

		$tickets = geodir_get_tickets(
			array(
				'seller_in'  => array( $seller_id ),
				'invoice_in' => array( $invoice_id ),
			)
		);

		if ( empty( $tickets ) ) {
			exit;
		}

		$invoice->set_status( 'wpi-refunded' );
		$invoice->save();

		wp_send_json_success();
	}

	/**
     * Generate tickets after an invoice is paid.
     *
     * @param WPInv_Invoice $invoice
     */
    public function generate_invoice_tickets( $invoice, $status_transition = array() ) {

        // Abort if the invoice does not exist.
        if ( ! $invoice->get_id() ) {
            return;
        }

        // (Maybe) re-activate the tickets in-case we already generated tickets.
        if ( $this->has_tickets( $invoice->get_id() ) ) {
            return $this->maybe_reactivate_tickets( $invoice );
        }

		$tickets = array();
        foreach ( $invoice->get_items() as $item ) {
            $tickets[] = $this->generate_item_ticket( $item, $invoice );
        }

		$tickets = array_filter( $tickets );
		if ( 0 < count( $tickets ) ) {
			update_post_meta( $invoice->get_id(), 'generated_tickets', true );
			do_action( 'geodir_generated_invoice_tickets', $invoice, $tickets );
		}

    }

	/**
     * Checks if an invoice has tickets.
     *
     * @param int $invoice_id
     * @return array
     */
    public function has_tickets( $invoice_id ) {
        $has_tickets = get_post_meta( $invoice_id, 'generated_tickets', true );
        return ! empty( $has_tickets );
    }

	/**
     * Generates tickets for an invoice item.
     *
     * @param GetPaid_Form_Item $item
     * @param WPInv_Invoice $invoice
	 * @return GeoDir_Ticket|false
     */
    protected function generate_item_ticket( $item, $invoice ) {

        // Abort if the item does not exist...
        if ( ! $item->exists() ) {
            return false;
        }

        // ... or if it does not support tickets.
        if ( 'ticket' !== $item->get_type() ) {
            return false;
        }

        // Create a ticket per item.
		$seller_earnings = 0;
		for ( $i=0; $i < $item->get_quantity(); $i++ ) {

			$ticket = new GeoDir_Ticket();
			$ticket->set_invoice_id( $invoice->get_id() );
			$ticket->set_buyer_id( $invoice->get_customer_id() );
			$ticket->set_seller_id( $item->get_author() );
			$ticket->set_event_id( (int) get_post_meta( $item->get_id(), 'ticket_listing', true ) );
			$ticket->set_date_created( current_time( 'mysql' ) );
			$ticket->set_price( $item->get_price() );
			$ticket->set_seller_price( $item->get_price() * ( 100 - geodir_tickets_get_commision_percentage() ) * 0.01 );
			$ticket->set_site_commision( $item->get_price() * geodir_tickets_get_commision_percentage() * 0.01 );
			$ticket->set_type( $item->get_id() );
			$ticket->set_status( 'confirmed' );
			$ticket->save();

			if ( $ticket->exists() ) {
				$seller_earnings += $ticket->get_seller_price();
			} else {

				$note = sprintf(
					__( 'Error generating event tickets for %s', 'geodir-tickets' ),
					sanitize_text_field( $item->get_raw_name() )
				);

				$invoice->add_system_note( $note );

				return false;
			}

		}

        $note = sprintf(
			__( 'Generated event tickets for %s', 'geodir-tickets' ),
			sanitize_text_field( $item->get_raw_name() )
		);

		$invoice->add_system_note( $note );

		if ( 0 < $seller_earnings ) {
			$account_funds = wpinv_wallet_get_user_balance( $item->get_author(), false, $invoice->get_currency() );

			wpinv_wallet_add_new_transaction(
				$item->get_author(),
				array(
					'type'     => 'tickets',
					'amount'   => $seller_earnings,
					'balance'  => $account_funds + $seller_earnings,
					'currency' => $invoice->get_currency(),
					'details'  => sanitize_text_field(
						sprintf(
							__( '%s ticket earnings', 'geodir-tickets' ),
							$item->get_raw_name()
						)
					),
				)
			);

		}

		return $ticket;
    }

	/**
     * Re-activates invoice tickets.
     *
     * @param WPInv_Invoice $invoice
     */
    public function maybe_reactivate_tickets( $invoice ) {

        // Fetch inactive invoice tickets.
        $args  = array(
            'invoice_in'  => $invoice->get_id(),
            'number'      => -1,
            'status'      => array( 'pending', 'refunded' )
        );
        $query = geodir_get_tickets( $args, 'query' );

        // Activate each of them.
		$seller_earnings = 0;
		$ticket_seller   = 0;
        foreach ( $query->get_results() as $ticket ) {
            $ticket->set_status( 'confirmed' );
            $ticket->save();
			$seller_earnings += $ticket->get_seller_price();
			$ticket_seller    = $ticket->get_seller_id();
        }

		if ( 0 < $seller_earnings ) {
			$account_funds = wpinv_wallet_get_user_balance( $ticket_seller, false, $invoice->get_currency() );

			wpinv_wallet_add_new_transaction(
				$ticket_seller,
				array(
					'type'     => 'tickets',
					'amount'   => $seller_earnings,
					'balance'  => $account_funds + $seller_earnings,
					'currency' => $invoice->get_currency(),
					'details'  => sanitize_text_field(
						sprintf(
							__( 'Tickets for invoice #%s re-paid', 'geodir-tickets' ),
							$invoice->get_number()
						)
					),
				)
			);

		}

    }

	/**
     * Save event dates.
     *
     * @param WPInv_Invoice $invoice
     */
    public function maybe_save_ticket_dates( $invoice ) {

		if ( $invoice->exists() && isset( $_POST['geodir_tickets_date'] ) ) {
			update_post_meta( $invoice->get_id(), 'geodir_tickets_date', sanitize_text_field( urldecode( $_POST['geodir_tickets_date'] ) ) );
		}

	}

	/**
     * Deactivates the invoice tickets whenever an invoice status changes.
     *
     * @param WPInv_Invoice $invoice
     * @param string $from
     * @param string $to
     */
    public function maybe_deactivate_invoice_tickets( $invoice, $from, $to ) {

        // Abort if this is a renewal invoice or the previous status was not published.
        if ( $invoice->is_renewal() || 'publish' != $from || 'publish' == $to ) {
            return;
        }

        // Fetch invoice licenses.
        $args  = array(
            'invoice_in'  => array( $invoice->get_id() ),
            'count_total' => false,
        );
        $tickets = geodir_get_tickets( $args );

        // Refund each of them.
		$seller_earnings = 0;
		$ticket_seller   = 0;
        foreach ( $tickets as $ticket ) {
			if ( 'confirmed' === $ticket->get_status() ) {
				$ticket->set_status( 'refunded' );
            	$ticket->save();
				$seller_earnings += $ticket->get_seller_price();
				$ticket_seller    = $ticket->get_seller_id();

				// Inventory manager doesn't handle this.
				if ( $invoice->is_refunded() ) {
					$available_stock = (int) get_post_meta( $ticket->get_type(), '_stock', true );
					update_post_meta( $ticket->get_type(), '_stock', $available_stock + 1 );
				}

			}
        }

		if ( 0 < $seller_earnings ) {
			$account_funds = wpinv_wallet_get_user_balance( $ticket_seller, false, $invoice->get_currency() );

			wpinv_wallet_add_new_transaction(
				$ticket_seller,
				array(
					'type'     => 'tickets',
					'amount'   => 0 - $seller_earnings,
					'balance'  => $account_funds - $seller_earnings,
					'currency' => $invoice->get_currency(),
					'details'  => sanitize_text_field(
						sprintf(
							__( 'Tickets for invoice #%s refunded', 'geodir-tickets' ),
							$invoice->get_number()
						)
					),
				)
			);

		}

    }

	public function geodir_verify_ticket() {

        check_ajax_referer( 'geodir_verify_ticket_nonce' );

		if ( empty( $_POST['listing_id'] ) || empty( $_POST['geodir-ticket-number'] ) ) {
			exit;
		}

		$listing_id    = intval( $_POST['listing_id'] );
		$ticket_number = explode( '-', sanitize_text_field( wp_unslash( $_POST['geodir-ticket-number'] ) ) );

		if ( $listing_id != $ticket_number[0] || empty( $ticket_number[2] ) ) {
			exit;
		}

		$ticket = new GeoDir_Ticket( $ticket_number[2] );

		if ( ! $ticket->exists() || ! $this->current_user_can_manage_listing( $ticket->get_seller_id() ) || $ticket->get_event_id() != $listing_id || $ticket->get_type() != $ticket_number[1] ) {
			exit;
		}

		if ( isset( $ticket_number[3] ) && substr( strtotime( $ticket->get_date_created() ), -4 ) != $ticket_number[3] ) {
			exit;
		}

		$statuses = geodir_get_ticket_statuses();
		$data     = array(
			'date_created' => sprintf(
				__( '%s ago', 'geodir-tickets' ),
				human_time_diff( strtotime( $ticket->get_date_created() ), current_time( 'timestamp' ) )
			),
			'id'           => '#' . $ticket->get_id(),
			'type'         => get_the_title( $ticket->get_type() ),
			'status'       => $statuses[ $ticket->get_status() ],
			'number'       => $ticket->get_number(),
		);

		$dates       = geodir_get_invoice_ticket_dates( $ticket->get_invoice_id(), $ticket->get_event_id() );
		$date_status = geodir_check_invoice_ticket_dates( $dates, $ticket->get_invoice_id() );

		if ( 'expired' === $date_status ) {
			wp_send_json_success(
				array_merge(
					$data,
					array(
						'state'  => 'danger',
						'status' => __( 'Expired', 'geodir-tickets' ),
						'msg'    => __( 'Expired Ticket', 'geodir-tickets' ),
					)
				)
			);
		}

		if ( 'used' == $ticket->get_status() && 'used' == $date_status ) {
			wp_send_json_success(
				array_merge(
					$data,
					array(
						'state'  => 'warning',
						'msg'    => sprintf(
							__( 'This ticket was used %s ago', 'geodir-tickets' ),
							human_time_diff( strtotime( $ticket->get_date_used() ), current_time( 'timestamp' ) )
						),
					)
				)
			);
		}

		if ( 'refunded' == $ticket->get_status() ) {
			wp_send_json_success(
				array_merge(
					$data,
					array(
						'state'  => 'warning',
						'msg'    => __( 'This ticket was refunded', 'geodir-tickets' ),
					)
				)
			);
		}

		if ( 'pending' == $ticket->get_status() ) {
			wp_send_json_success(
				array_merge(
					$data,
					array(
						'state'  => 'warning',
						'msg'    => __( 'This ticket is pending payment', 'geodir-tickets' ),
					)
				)
			);
		}

		if ( 'future' === $date_status ) {
			wp_send_json_success(
				array_merge(
					$data,
					array(
						'state'  => 'warning',
						'status' => __( 'Future', 'geodir-tickets' ),
						'msg'    => sprintf( __( 'Ticket Valid for %s', 'geodir-tickets' ), is_array( $dates ) ? getpaid_format_date( $dates[0] ) : getpaid_format_date( $dates ) ),
					)
				)
			);
		}

		wp_send_json_success(
			array_merge(
				$data,
				array(
					'state'  => 'success',
					'msg'    => __( 'Valid!', 'geodir-tickets' ),
				)
			)
		);

	}

	public function geodir_redeem_ticket() {

        check_ajax_referer( 'geodir_verify_ticket_nonce' );

		if ( empty( $_POST['listing'] ) || empty( $_POST['ticket'] ) ) {
			wp_die( -1, 400 );
		}

		$listing_id    = intval( $_POST['listing'] );
		$ticket_number = explode( '-', sanitize_text_field( wp_unslash( $_POST['ticket'] ) ) );

		if ( $listing_id != $ticket_number[0] || empty( $ticket_number[2] ) ) {
			wp_die( -1, 400 );
		}

		$ticket = new GeoDir_Ticket( $ticket_number[2] );

		if ( ! $ticket->exists() || ! $this->current_user_can_manage_listing( $ticket->get_seller_id() ) || $ticket->get_event_id() != $listing_id || $ticket->get_type() != $ticket_number[1] ) {
			wp_die( -1, 404 );
		}

		$used_dates = get_post_meta( $ticket->get_invoice_id(), 'geodir_tickets_used_dates', true );

		if ( empty( $used_dates ) ) {
			$used_dates = array();
		}

		$used_dates[] = date( 'Y-m-d' );
		update_post_meta( $ticket->get_invoice_id(), 'geodir_tickets_used_dates', $used_dates );

		$ticket->set_status( 'used' );
		$ticket->set_date_used( current_time( 'mysql' ) );
		$ticket->save();
		exit;

	}

	/**
	 * Registers the quotes email settings.
	 *
	 * @since    1.0.0
	 * @param array $settings Current email settings.
	 */
	public function register_email_settings( $settings ) {

		return array_merge(
			$settings,
			array(

				'user_tickets' => array(

					'email_user_tickets_header' => array(
						'id'       => 'email_user_tickets_header',
						'name'     => '<h3>' . __( 'User Tickets', 'geodir-tickets' ) . '</h3>',
						'desc'     => __( 'These emails are sent to the customer with information about their tickets.', 'geodir-tickets' ),
						'type'     => 'header',
					),

					'email_user_tickets_active' => array(
						'id'       => 'email_user_tickets_active',
						'name'     => __( 'Enable/Disable', 'geodir-tickets' ),
						'desc'     => __( 'Enable this email notification', 'geodir-tickets' ),
						'type'     => 'checkbox',
						'std'      => 1
					),

					'email_user_tickets_admin_bcc' => array(
						'id'       => 'email_user_tickets_admin_bcc',
						'name'     => __( 'Enable Admin BCC', 'geodir-tickets' ),
						'desc'     => __( 'Check if you want to send a copy of this notification email to to the site admin.', 'geodir-tickets' ),
						'type'     => 'checkbox',
						'std'      => 0
					),

					'email_user_tickets_subject' => array(
						'id'       => 'email_user_tickets_subject',
						'name'     => __( 'Subject', 'geodir-tickets' ),
						'desc'     => __( 'Enter the subject line for this email.', 'geodir-tickets' ),
						'help-tip' => true,
						'type'     => 'text',
						'std'      => __( '[{site_title}] Your tickets for {event_name}', 'geodir-tickets' ),
						'size'     => 'large'
					),

					'email_user_tickets_heading' => array(
						'id'       => 'email_user_tickets_heading',
						'name'     => __( 'Email Heading', 'geodir-tickets' ),
						'desc'     => __( 'Enter the main heading contained within the email notification.', 'geodir-tickets' ),
						'help-tip' => true,
						'type'     => 'text',
						'std'      => __( 'Your ticket details', 'geodir-tickets' ),
						'size'     => 'large'
					),

					'email_user_tickets_body' => array(
						'id'       => 'email_user_tickets_body',
						'name'     => __( 'Email Content', 'geodir-tickets' ),
						'desc'     => $this->get_merge_tags_help_text(),
						'type'     => 'rich_editor',
						'std'      => __( '<p>Hi {customer_name},</p><p>Click on the button below to view your tickets for <a href="{post_url}">{event_name}</a>.</p>', 'geodir-tickets' ),
						'class'    => 'large',
						'size'     => '10'
					),
				),

				'tickets_sold' => array(

					'email_tickets_sold_header' => array(
						'id'       => 'email_tickets_sold_header',
						'name'     => '<h3>' . __( 'Tickets Sold', 'geodir-tickets' ) . '</h3>',
						'desc'     => __( 'These emails are sent to the event organizer whenever a customer buys a ticket.', 'geodir-tickets' ),
						'type'     => 'header',
					),

					'email_tickets_sold_active' => array(
						'id'       => 'email_tickets_sold_active',
						'name'     => __( 'Enable/Disable', 'geodir-tickets' ),
						'desc'     => __( 'Enable this email notification', 'geodir-tickets' ),
						'type'     => 'checkbox',
						'std'      => 1
					),

					'email_tickets_sold_admin_bcc' => array(
						'id'       => 'email_tickets_sold_admin_bcc',
						'name'     => __( 'Enable Admin BCC', 'geodir-tickets' ),
						'desc'     => __( 'Check if you want to send a copy of this notification email to to the site admin.', 'geodir-tickets' ),
						'type'     => 'checkbox',
						'std'      => 0
					),

					'email_tickets_sold_subject' => array(
						'id'       => 'email_tickets_sold_subject',
						'name'     => __( 'Subject', 'geodir-tickets' ),
						'desc'     => __( 'Enter the subject line for this email.', 'geodir-tickets' ),
						'help-tip' => true,
						'type'     => 'text',
						'std'      => __( '[{site_title}] New tickets sold for {event_name}', 'geodir-tickets' ),
						'size'     => 'large'
					),

					'email_tickets_sold_heading' => array(
						'id'       => 'email_tickets_sold_heading',
						'name'     => __( 'Email Heading', 'geodir-tickets' ),
						'desc'     => __( 'Enter the main heading contained within the email notification.', 'geodir-tickets' ),
						'help-tip' => true,
						'type'     => 'text',
						'std'      => __( 'New tickets sold for {event_name}', 'geodir-tickets' ),
						'size'     => 'large'
					),

					'email_tickets_sold_body' => array(
						'id'       => 'email_tickets_sold_body',
						'name'     => __( 'Email Content', 'geodir-tickets' ),
						'desc'     => $this->get_merge_tags_help_text(),
						'type'     => 'rich_editor',
						'std'      => __( '<p>Hi {post_author},</p><p>You just sold new tickets worth {invoice_total} for <a href="{post_url}">{event_name}</a></p><p>Congratulations on the sale!</p>', 'geodir-tickets' ),
						'class'    => 'large',
						'size'     => '10'
					),
				),

			)
		);

	}

	/**
	 * Returns the merge tags help text.
	 *
	 * @since    1.0.0
	 * @return string
	 */
	public function get_merge_tags_help_text() {

		$link = sprintf(
			'<strong><a href="%s" target="_blank">%s</a></strong>',
			'https://gist.github.com/picocodes/ba65d0476c4127a8447e3c485b62ebda',
			esc_html__( 'View available merge tags.', 'geodir-tickets' )
		);

		$description = esc_html__( 'The content of the email (Merge Tags and HTML are allowed).', 'geodir-tickets' );

		return "$description $link";

	}

	/**
	 * Sends ticket emails.
	 *
	 * @since 1.0.0
	 * @param WPInv_Invoice $invoice
	 * @param GeoDir_Ticket[] $tickets
	 */
	public function send_ticket_emails( $invoice, $tickets ) {

		if ( empty( $tickets ) ) {
			return;
		}

		foreach ( array( 'user_tickets', 'tickets_sold' ) as $type ) {

			$email = new GetPaid_Notification_Email( $type );

			// Abort if it is not active.
			if ( $email->is_active() ) {
				call_user_func( array( $this, 'send_' . $type . '_email' ), $invoice, $email, $tickets );
			}

		}

	}

	/**
	 * Sends the user tickets email
	 *
	 * @param WPInv_Invoice $invoice
	 * @param GetPaid_Notification_Email $email
	 * @param GeoDir_Ticket[] $tickets
	 */
	public function send_user_tickets_email( $invoice, $email, $tickets ) {

		if ( empty( $tickets ) ) {
			return false;
		}

		$mailer     = new GetPaid_Notification_Email_Sender();
		$merge_tags = geodir_get_ticket_event_merge_tags( $invoice, $tickets[0]->get_event_id() );
		$email->object = $invoice;

		$result = $mailer->send(
			apply_filters( 'getpaid_invoice_email_recipients', array( $invoice->get_email() ), $email ),
			$email->add_merge_tags( $email->get_subject(), $merge_tags ),
			$email->get_content( $merge_tags ),
			array()
		);

		// Maybe send a copy to the admin.
		if ( $email->include_admin_bcc() ) {
			$mailer->send(
				wpinv_get_admin_email(),
				$email->add_merge_tags( $email->get_subject() . __( ' - ADMIN BCC COPY', 'geodir-tickets' ), $merge_tags ),
				$email->get_content( $merge_tags ),
				array()
			);
		}

		if ( $result ) {
			$invoice->add_system_note( __( 'Successfully sent user tickets to the customer.', 'geodir-tickets' ) );
		} else {
			$invoice->add_system_note( __( 'Failed sending user tickets to the customer.', 'geodir-tickets' ) );
		}

		return $result;
	}

	/**
	 * Sends the tickets sold.
	 *
	 * @param WPInv_Invoice $invoice
	 * @param GetPaid_Notification_Email $email
	 * @param GeoDir_Ticket[] $tickets
	 */
	public function send_tickets_sold_email( $invoice, $email, $tickets ) {

		$mailer      = new GetPaid_Notification_Email_Sender();
		$merge_tags  = geodir_get_ticket_event_merge_tags( $invoice, $tickets[0]->get_event_id() );
		$post_author = get_userdata( $tickets[0]->get_seller_id() );

		if ( empty( $post_author ) ) {
			return;
		}

		$result = $mailer->send(
			$post_author->user_email,
			$email->add_merge_tags( $email->get_subject(), $merge_tags ),
			$email->get_content( $merge_tags ),
			array()
		);

		// Maybe send a copy to the admin.
		if ( $email->include_admin_bcc() ) {
			$mailer->send(
				wpinv_get_admin_email(),
				$email->add_merge_tags( $email->get_subject() . __( ' - ADMIN BCC COPY', 'geodir-tickets' ), $merge_tags ),
				$email->get_content( $merge_tags ),
				array()
			);
		}

		if ( $result ) {
			$invoice->add_system_note( __( 'Successfully sent ticket notification to the event organizer.', 'geodir-tickets' ) );
		} else {
			$invoice->add_system_note( __( 'Failed sending ticket notification to the event organizer.', 'geodir-tickets' ) );
		}

		return $result;
	}

	/**
	 * Filters the default template paths.
	 *
	 * @since 1.0.0
	 */
	public function maybe_filter_default_template_path( $default_path, $template_name ) {

		$our_emails = array(
			'emails/wpinv-email-user_tickets.php',
			'emails/wpinv-email-tickets_sold.php',
			'tickets/ticket.php',
			'tickets/base.php'
		);

		if ( in_array( $template_name, $our_emails, true ) ) {
			return plugin_dir_path( GEODIR_TICKETS_FILE ) . 'templates';
		}

		return $default_path;
	}

	/**
	 * Filters stock message.
	 *
	 * @param string $text
	 * @param GetPaid_Form_Item $item
	 * @param int $available_stock
	 */
	public static function fitler_stock_message( $text, $item, $available_stock ) {

		if ( 'ticket' === $item->get_type() ) {

			if ( empty( $available_stock ) ) {

				return sprintf(
					/* translators: %1$s: item */
					__( '"%1$s" is sold out.', 'geodir-tickets' ),
					sanitize_text_field( $item->get_raw_name() )
				);

			}

            return sprintf(
				/* translators: %1$s: item , %2$s Ordered quantity, %3$s Available quantity  */
				__( '"%1$s" does not have enough tickets to fullfill your order of %2$s tickets. (Only %3$s available).', 'geodir-tickets' ),
				sanitize_text_field( $item->get_raw_name() ),
				absint( $item->get_quantity() ),
				absint( $available_stock )
			);
        }

		return $text;
	}

	/**
	 * Filters out of stock message.
	 *
	 * @param string $text
	 * @param GetPaid_Form_Item $item
	 */
	public static function fitler_out_of_stock_message( $text, $item ) {

		if ( 'ticket' === $item->get_type() ) {
            return __( 'Sold Out', 'geodir-tickets' );
        }

		return $text;
	}

	/**
	 * Filters stock text
	 *
	 * @param string $text
	 * @param GetPaid_Form_Item $item
	 */
	public static function fitler_stock_text( $text, $item ) {
		
		if ( 'ticket' !== $item->get_type() || empty( $GLOBALS['getpaid_item_inventory'] ) ) {
            return $text;
        }

		$inv          = $GLOBALS['getpaid_item_inventory'];
		$display      = __( 'Available', 'geodir-tickets' );
		$stock_amount = (int) $inv->inventory->available_stock( $item->get_id() );

		switch ( wpinv_get_option( 'stock_format', 'no_amount' ) ) {

			case 'low_amount':

				if ( $inv->inventory->has_low_stock( $item->get_id() ) ) {

					$display = sprintf(
						/* translators: %s: stock amount */
						__( 'Only %s tickets left', 'geodir-tickets' ),
						$inv->format_stock_amount( $stock_amount, $item )
					);

				}
				break;

			case '':

				$display = sprintf(
					/* translators: %s: stock amount */
					__( '%s tickets left', 'geodir-tickets' ),
					$inv->format_stock_amount( $stock_amount, $item )
				);
				break;

			case 'no_stock':

				$display = '';
				break;

		}

		return $display;
	}

	/**
	 * Filters stock text
	 *
	 * @param GetPaid_Payment_Form $form
	 */
	public static function payment_form_date_select( $form ) {
		global $aui_bs5;

		foreach ( $form->get_items() as $item ) {

			if ( 'ticket' === $item->get_type() ) {
				$event_id  = (int) get_post_meta( $item->get_id(), 'ticket_listing', true );
				$schedules = GeoDir_Event_Schedules::get_schedules( $event_id, 'upcoming' );

				if ( empty( $schedules ) ) {
					return;
				}

				$current_date = sanitize_text_field( $schedules[0]->start_date );
				if ( ! empty( $_GET['gde'] ) ) {
					$current_date = date( 'Y-m-d', strtotime( $_GET['gde'] ) );
				}

				echo '<div class="getpaid-payment-form-event-dates ' . ( $aui_bs5 ? 'mb-3' : 'form-group' ) . '">';

				// If only one date, hidden field.
				if ( 1 === count( $schedules ) ) {
					printf(
						'<strong>%s</strong>: %s',
						__( 'Select Event Date', 'geodir-tickets' ),
						getpaid_format_date( $current_date )
					);
					getpaid_hidden_field( 'geodir_tickets_date', $current_date );
				} else {

					$dates = array();
					foreach ( $schedules as $schedule ) {
						$dates[ $schedule->start_date ] = getpaid_format_date( $schedule->start_date );
					}

					echo aui()->select(
						array(
							'name'       => 'geodir_tickets_date',
							'id'         => 'geodir_tickets_date' . uniqid( '_' ),
							'required'   => true,
							'label'      => __( 'Event Date', 'geodir-tickets' ),
							'label_type' => 'vertical',
							'inline'     => false,
							'options'    => $dates,
							'value'      => $current_date,
						)
					);

				}

				echo '</div>';

				if ( 1 < count( $form->get_items() ) ) {
					printf(
						'<label>%s</label>',
						__( 'Select Tickets', 'geodir-tickets' )
					);
				}
				break;
			}

		}

	}

}
