<?php
/**
 * Template that prints the main tickets page.
 *
 * This template can be overridden by copying it to yourtheme/invoicing/tickets/base.php.
 *
 * @version 2.1.1
 */

defined( 'ABSPATH' ) || exit;

// Retrieve the current invoice.
$invoice_id = getpaid_get_current_invoice_id();

if ( empty( $invoice_id ) ) {
	wp_die( __( 'Invalid Invoice', 'geodir-tickets' ), 404 );
}

// Can the user view this invoice?
if ( ! wpinv_user_can_view_invoice( $invoice_id ) ) {
	wp_die( __( 'You are not allowed to view this invoice', 'geodir-tickets' ), 400 );
}

$invoice = new WPInv_Invoice( $invoice_id ); 

$page_title = wp_sprintf( __( 'Tickets | %s | %s', 'geodir-tickets' ), $invoice->get_number(), wpinv_get_blogname() );
$page_title = apply_filters( 'geodir_view_tickets_page_title', $page_title, $invoice );
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?> class="bsui">
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1.0" >
	<meta name="robots" content="noindex,nofollow">
	<link rel="profile" href="https://gmpg.org/xfn/11">
	<title><?php echo esc_html( $page_title ); ?></title>
	<?php
		wp_enqueue_scripts();
		wp_print_styles();
		wp_print_head_scripts();
		wp_custom_css_cb();
	?>
	<style type="text/css">
	.body{ 
		background: white;
		width: 100%;
		max-width: 100%;
		text-align: left;
		font-weight: 400;
	}
	/* hide all other elements */
	body::before,
	body::after,
	body > *:not(#getpaid-tickets-embed):not(.flatpickr-calendar) { 
		display:none !important; 
	}
	#getpaid-tickets-embed {
		display: block !important;
		width: 100%;
		height: 100%;
		padding: 20px;
		border: 0;
		margin: 0 auto;
		max-width: 820px;
	}
	@media print {
		#getpaid-tickets-embed .geodir-print-tickets-btn {
			display: none;
		}
	}
	</style>
</head>
<body class="body page-template-default page">
	<div id="getpaid-tickets-embed" class="container my-5 page type-page status-publish hentry post post-content">
		<button class="btn btn-danger geodir-print-tickets-btn" onclick="window.print(); return false;"><?php _e( 'Print Tickets', 'geodir-tickets' ); ?></button>
		<?php do_action( 'geodir_view_tickets_before_tickets_list', $invoice ); ?>
		<?php
			$event_args = array();
			$date       = '';
			$time       = 'N/A';
			$tickets    = geodir_get_tickets(
				array(
					'invoice_in' => array( $invoice_id ),
				)
			);

			if ( ! empty( $tickets ) ) {
				$GLOBALS['post']    = get_post( $tickets[0]->get_event_id() );
				$GLOBALS['gd_post'] = geodir_get_post_info( $tickets[0]->get_event_id() );
				add_filter( 'geodir_get_current_posttype', function() {
					return get_post_type( $GLOBALS['post'] );
				});

				$date  = geodir_get_invoice_ticket_dates( $tickets[0]->get_invoice_id(), $tickets[0]->get_event_id() );
				$start = "&mdash;";
				$end   = "&mdash;";
				$table = constant( 'GEODIR_EVENT_SCHEDULES_TABLE' );

				if ( is_array( $date ) ) {

					$start = getpaid_format_date( $date[0] . ' ' . $date[2], true );

					if ( $date[0] === $date[1] ) {
						$end   = date_i18n( getpaid_time_format(), strtotime( $date[3] ) );
					} else {
						$end   = getpaid_format_date( $date[1] . ' ' . $date[3], true );
					}

				}

				$fields = geodir_post_custom_fields( '', 'all', $GLOBALS['post']->post_type, 'none' );
				if ( ! empty( $fields ) ) {
					foreach( $fields as $_field ) {
						if ( ! empty( $_field['type']) && 'address' == $_field['type'] ) {
							$field = $_field;
							break;
						}
					}
				}
			}

			foreach ( $tickets as $ticket ) {
				$ticket_args = array(
					'ticket_number' => esc_html( $ticket->get_number() ),
					'ticket_price'  => wpinv_price( $ticket->get_price(), $invoice->get_currency() ),
					'ticket_name'   => esc_html( get_the_title( $ticket->get_type() ) ),
				);

				$args = array_merge( $ticket_args );
				wpinv_get_template( 'tickets/ticket.php', array( 'ticket' => $ticket, 'start' => $start, 'end' => $end, 'invoice' => $invoice ) );
			}
		?>
		<?php do_action( 'geodir_view_tickets_after_tickets_list', $invoice, $tickets ); ?>
		<div class="py-2">
			<?php if ( ! empty( $field ) ) : ?>
			<h4><?php _e( 'Location:', 'geodir-tickets' ); ?></h4>
			<div><?php echo apply_filters( 'geodir_custom_field_output_address', '', 'listing', $field ); ?></div>
			<br />
			<?php echo do_shortcode( '[gd_map width="100%" height="425px" maptype="ROADMAP" zoom="0" map_type="post" post_id="' . $GLOBALS['post']->ID  . '" map_directions="1"]' ); ?>
			<?php endif; ?>
		</div>
		<?php do_action( 'geodir_view_tickets_after_address_field', $invoice, $tickets ); ?>
	</div>
	<?php wp_footer(); ?>
</body>
</html>
