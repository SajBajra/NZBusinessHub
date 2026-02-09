<?php
/**
 * Tickets manager admin class.
 *
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Tickets manager admin class.
 *
 */
class GeoDir_Tickets_Admin {

    /**
     * Class constructor.
     *
     */
    public function __construct() {

        add_action( 'admin_menu', array( $this, 'admin_menu' ), 10 );
        add_filter( 'wpinv_screen_ids', array( $this, 'screen_ids' ), 10 );
		add_action( 'getpaid_authenticated_admin_action_refund_ticket', array( $this, 'admin_refund_ticket' ) );
        add_action( 'getpaid_authenticated_admin_action_resend_ticket', array( $this, 'admin_resend_ticket' ) );
        add_action( 'getpaid_authenticated_admin_action_delete_ticket', array( $this, 'admin_delete_ticket' ) );
    }

    /**
     * Register admin menus.
     *
     */
    public function admin_menu() {

        add_menu_page(
            esc_html__( 'Sold Tickets', 'geodir-tickets' ),
            esc_html__( 'Tickets', 'geodir-tickets' ),
            wpinv_get_capability(),
            'geodir-tickets',
            null,
            'dashicons-money',
            '54.1234560'
        );

        // Add the manage tickets page.
		add_submenu_page(
			'geodir-tickets',
			esc_html__( 'Sold Tickets', 'geodir-tickets' ),
			esc_html__( 'Sold Tickets', 'geodir-tickets' ),
			wpinv_get_capability(),
			'geodir-tickets',
            array( $this, 'display_manage_tickets_page' )
		);

        // Add the reports page.
		add_submenu_page(
			'geodir-tickets',
			esc_html__( 'Ticket Reports', 'geodir-tickets' ),
			esc_html__( 'Reports', 'geodir-tickets' ),
			wpinv_get_capability(),
			'geodir-tickets-reports',
            array( $this, 'display_ticket_reports_page' )
		);

		// Add the settings page.
		add_submenu_page(
			'geodir-tickets',
			esc_html__( 'Ticket Settings', 'geodir-tickets' ),
			esc_html__( 'Settings', 'geodir-tickets' ),
			wpinv_get_capability(),
			'geodir-tickets-settings',
            array( $this, 'display_ticket_settings_page' )
		);

    }

    /**
     * Displays the manage tickets page.
     *
     */
    public function display_manage_tickets_page() {

        if ( ! current_user_can( wpinv_get_capability() ) ) {
			return;
		}

        require_once plugin_dir_path( __FILE__ ) . 'class-geodir-tickets-list-table.php';

        include plugin_dir_path( __FILE__ ) . 'views/tickets.php';
    }

	/**
	 * Displays the reports page.
	 *
	 */
	public function display_ticket_reports_page() {

		// Display the current tab.
		?>

        <div class="wrap">

			<h1><?php _e( 'Reports', 'geodir-tickets' ); ?></h1>

			<div class="bsui reports">
				<?php
					$reports = new GetPaid_Reports_Report();
					$reports->views = array(

						'events'     => array(
							'label' => __( 'Popular Events', 'geodir-tickets' ),
							'class' => 'GeoDir_Tickets_Report_Events',
							'disable-downloads' => true,
						),

						'event_revenue'     => array(
							'label' => __( 'Highest Revenue Events', 'geodir-tickets' ),
							'class' => 'GeoDir_Tickets_Report_Event_Sales',
							'disable-downloads' => true,
						),

					);
					$reports->display();
				?>
			</div>

        </div>
		<?php

			// Wordfence loads an unsupported version of chart js on our page.
			wp_deregister_style( 'chart-js' );
			wp_deregister_script( 'chart-js' );
			wp_enqueue_script( 'chart-js', WPINV_PLUGIN_URL . 'assets/js/chart.min.js', array( 'jquery' ), '3.7.1', true );
	}

    /**
     * Displays the settings page.
     *
     */
    public function display_ticket_settings_page() {

        if ( ! current_user_can( wpinv_get_capability() ) ) {
			return;
		}

		$saved_settings   = $this->maybe_save_settings();
        $payment_forms    = wp_list_pluck(
			get_posts(
				array(
					'numberposts' => -1,
					'post_type'   => 'wpi_payment_form',
				)
			),
			'post_title',
			'ID'
		);

		$settings         = array(
			'commision'       => array(
				'type'        => 'number',
				'label'       => __( 'Ticket Sales Commission %', 'geodir-tickets' ),
                'desc'        => __( 'Remember to account for gateway fees', 'geodir-tickets' ),
				'default'     => 10,
                'min'         => 0,
                'max'         => 100,
                'placeholder' => 10,
			),
            'payment_form'    => array(
                'type' 		  => 'select',
                'label'       => __( 'Payment Form', 'geodir-tickets' ),
                'desc' 		  => __( 'Optional. Select a payment form to use when paying for tickets.', 'geodir-tickets' ),
                'options'     => $payment_forms,
                'placeholder' => __( 'Select payment form', 'geodir-tickets' ),
                'default'	  => wpinv_get_default_payment_form(),
            ),
		);

		include plugin_dir_path( __FILE__ ) . 'views/settings.php';
    }

    /**
	 * Displays the settings menu.
	 *
	 * @since 1.0.0
	 */
	protected function maybe_save_settings() {

		if ( empty( $_POST['geodir_tickets'] ) ) {
			return '';
		}

		if ( ! current_user_can( 'manage_options' ) || ! wp_verify_nonce( $_POST['geodir-tickets'], 'geodir-tickets' ) ) {
			return false;
		}

        $options              = wp_kses_post_deep( wp_unslash( $_POST['geodir_tickets'] ) );
        $options['commision'] = min( 100, floatval( $options['commision'] ) );

		update_option( 'geodir_tickets', $options );
        return true;
	}

    /**
	 * Returns the merge tags help text.
	 *
	 * @since    1.0.0
	 * @return string
	 */
	public function get_merge_tags_help_text() {

		$tags = array(
			'{SITE_TITLE}',
			'{TICKET_PRICE}',
			'{TICKET_NUMBER}',
			'{TICKET_NAME}',
			'{POST_TITLE}',
			'{EVENT_DATE}',
			'{POST_AUTHOR}',
			'{POST_URL}',
			'{POST_CONTENT}',
			'{ADDRESS}',
			'{$html_var}',
			'{CUSTOMER_NAME}',
			'{FIRST_NAME}',
			'{LAST_NAME}',
			'{EMAIL}',
			'{INVOICE_NUMBER}',
			'{INVOICE_TOTAL}',
			'{INVOICE_LINK}',
			'{INVOICE_RECEIPT_LINK}',
			'{INVOICE_DATE}',
			'{DATE}',
			'{INVOICE_DESCRIPTION}'
		);

		$tags        = '<code>' . implode( '</code> <code>', $tags ) . '</code>';
		$description = __( 'Available template tags:', 'geodir-tickets' ) . ' ' . $tags;
		$link        = '<a  class="thickbox" href="#TB_inline?width=400&height=500&inlineId=geodir-tickets-merge-tags"><span class="dashicons dashicons-info"></span></a>';

		return "$description $link";

	}

    /**
	 * Register screen ids.
	 *
	 * @since 1.0.0
	 */
	public function screen_ids( $screen_ids ) {
        $screen_ids[] = 'toplevel_page_geodir-tickets';
		return $screen_ids;
	}

    public function admin_refund_ticket( $args ) {

        if ( empty( $args['invoice_id'] ) ) {
            return;
        }

		$invoice = wpinv_get_invoice( $args['invoice_id'] );

		if ( ! empty( $invoice ) ) {
			$invoice->set_status( 'wpi-refunded' );
			$invoice->save();
			getpaid_admin()->show_info( __( 'Ticket refunded', 'geodir-tickets' ) );
		}

		wp_safe_redirect(
			add_query_arg(
				array(
					'getpaid-admin-action' => false,
					'getpaid-nonce'        => false,
					'invoice_id'           => false
				)
			)
		);
		exit;
    }

	public function admin_resend_ticket( $args ) {

        if ( empty( $args['invoice_id'] ) ) {
            return;
        }

		$invoice = wpinv_get_invoice( $args['invoice_id'] );

		if ( ! empty( $invoice ) && $invoice->is_paid() ) {

			$tickets = geodir_get_tickets(
				array( 'invoice_in' => array( $invoice->get_id() ), )
			);

			$GLOBALS['geodir_tickets']->send_user_tickets_email(
				$invoice,
				new GetPaid_Notification_Email( 'user_tickets' ),
				$tickets
			);

			getpaid_admin()->show_info( __( 'Ticket was sent successully', 'geodir-tickets' ) );
		}

		wp_safe_redirect(
			add_query_arg(
				array(
					'getpaid-admin-action' => false,
					'getpaid-nonce'        => false,
					'invoice_id'           => false
				)
			)
		);
		exit;
    }

	public function admin_delete_ticket( $args ) {

        if ( empty( $args['invoice_id'] ) ) {
            return;
        }

		$tickets = geodir_get_tickets( array( 'invoice_in' => $args['invoice_id'] ) );

		foreach ( $tickets as $ticket ) {
			$ticket->delete();
		}

		getpaid_admin()->show_info( __( 'Ticket was deleted successully', 'geodir-tickets' ) );

		wp_safe_redirect(
			add_query_arg(
				array(
					'getpaid-admin-action' => false,
					'getpaid-nonce'        => false,
					'invoice_id'           => false
				)
			)
		);
		exit;
    }

}
