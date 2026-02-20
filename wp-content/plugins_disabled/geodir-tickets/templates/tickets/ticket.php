<?php
/**
 * Template that prints the main tickets page.
 *
 * This template can be overridden by copying it to yourtheme/invoicing/tickets/base.php.
 *
 * @var GeoDir_Ticket $ticket
 * @version 1.0.19
 */

defined( 'ABSPATH' ) || exit;

$author = get_userdata( $GLOBALS['post']->post_author );

global $aui_bs5;
?>
<style>
	.geodir-ticket-stub .top {
		text-transform: uppercase;
	}
	.geodir-ticket-line-sep {
		height: 40px;
		width: 3px;
	}
	.geodir-ticket-border-top:before {
		content: '';
		background: #ef5658;
		display: block;
		width: 40px;
		height: 3px;
		margin-bottom: 5px;
	}
	.geodir-ticket-stub .geodir-ticket-border-top:before {
		background: #fff;
	}
	@media print {
		.geodir-ticket {
			break-inside: avoid;
		}
	}
</style>
<div class="geodir-ticket row shadow-sm my-4 mx-auto" style="max-width: 100%; width: 800px;">
	<div class="geodir-ticket-stub col-sm-4 d-flex flex-column bg-primary text-white position-relative p-3" style="min-height: 250px;">
		<div class="top my-2 <?php echo ( $aui_bs5 ? 'fw-bold' : 'font-weight-bold' ); ?>">
			<span class="text-warning"><?php echo esc_html( get_the_title( $ticket->get_type() ) ); ?></span>
			<span><?php _e( 'Ticket', 'geodir-tickets'); ?></span>
		</div>
		<div class="flex-grow-1 d-flex align-items-center justify-content-center">
			<div class="h3 bg-primary text-light">
				<?php echo wpinv_price( $ticket->get_price(), $invoice->get_currency() ); ?>
			</div>
		</div>
		<div class="row">
			<div class="geodir-ticket-border-top col"><?php echo esc_html( wpinv_get_blogname() ) ?></div>
			<div class="geodir-ticket-border-top col"><?php echo esc_html( $author->display_name ); ?></div>
		</div>
	</div>
	<div class="geodir-ticket-check d-flex align-items-center bg-white text-dark col-sm-8 position-relative p-3">
		<div class="w-100">
			<div class="h4 text-dark"><?php echo esc_html( get_the_title( $ticket->get_event_id() ) ) ?></div>

			<div class="geodir-ticket-info row small mt-3">
				<section class="col geodir-ticket-border-top">
					<div class="<?php echo ( $aui_bs5 ? 'fw-bold' : 'font-weight-bold' ); ?>"><?php _e( 'Start', 'geodir-tickets' ); ?></div>
					<div><?php echo wp_kses_post( $start ); ?></div>
				</section>
				<section class="col geodir-ticket-border-top">
					<div class="<?php echo ( $aui_bs5 ? 'fw-bold' : 'font-weight-bold' ); ?>"><div class="title"><?php _e( 'End', 'geodir-tickets' ); ?></div></div>
					<div><?php echo wp_kses_post( $end ); ?></div>
				</section>
				<section class="col geodir-ticket-border-top">
					<div class="<?php echo ( $aui_bs5 ? 'fw-bold' : 'font-weight-bold' ); ?>"><?php _e( 'Attendee', 'geodir-tickets' ); ?></div>
					<div><?php echo esc_html( $invoice->get_customer_full_name() ); ?></div>
				</section>
				<div class="col">
					<?php geodir_print_ticket_qr_code( $ticket->get_number() ); ?>
					<br />
					<span class="text-muted"><?php echo esc_html( $ticket->get_number() ); ?></span>
				</div>
			</div>
		</div>
	</div>
</div>
