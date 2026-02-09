<?php defined( 'ABSPATH' ) || exit; ?>
<?php
global $aui_bs5;
?>
<div class="bsui geodir-tickets-modal">
	<div class="modal fade" id="geodir-view-tickets-modal<?php echo esc_attr( $id ); ?>" tabindex="-1" aria-labelledby="geodir-view-tickets-modal<?php echo esc_attr( $id ); ?>" aria-hidden="true">
		<div class="modal-dialog modal-lg modal-dialog-centered">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title" id="geodir-view-tickets-title<?php echo esc_attr( $id ); ?>"><?php _e( 'View Tickets', 'geodir-tickets' ); ?></h5>
					<?php if ( $aui_bs5 ) { ?>
						<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?php esc_attr_e( 'Close', 'geodir-tickets' ); ?>"></button>
					<?php } else { ?>
						<button type="button" class="btn-close close" data-dismiss="modal" aria-label="<?php esc_attr_e( 'Close', 'geodir-tickets' ); ?>">&times;</button>
					<?php } ?>
				</div>
				<div class="modal-body">

					<table data-toggle="table" data-sortable="true" data-pagination="true" data-search="true" class="table my-3 geodir-view-tickets">
						<thead>
							<tr>
								<th scope="col"><?php _e( 'Ticket Number', 'geodir-tickets' ); ?></th>
								<th scope="col" class="text-center"><?php _e( 'Ticket Type', 'geodir-tickets' ); ?></th>
								<th scope="col" class="text-center"><?php _e( 'Status', 'geodir-tickets' ); ?></th>
								<th scope="col" class="text-center"><?php _e( 'Booked', 'geodir-tickets' ); ?></th>
								<th scope="col" class="text-center"><?php _e( 'Invoice', 'geodir-tickets' ); ?></th>
							</tr>
						</thead>
						<tbody>

							<?php

								foreach ( $user_tickets as $ticket ) :

									$invoice = wpinv_get_invoice( $ticket->get_invoice_id() );

									if ( empty( $invoice ) ) {
										continue;
									}

									if ( 'used' == $ticket->get_status() ) {
										$status = sprintf(
											__( 'Used %s ago', 'geodir-tickets' ),
											human_time_diff( strtotime( $ticket->get_date_used() ), current_time( 'timestamp' ) )
										);
									} else {
										$statuses = geodir_get_ticket_statuses();
										$status   = $statuses[ $ticket->get_status() ];
									}

									$date_created = sprintf(
										__( '%s ago', 'geodir-tickets' ),
										human_time_diff( strtotime( $ticket->get_date_created() ), current_time( 'timestamp' ) )
									);

									$invoice_url = sprintf(
										'<a href="%s" target="_blank">#%s</a>',
										esc_url( $invoice->get_view_url() ),
										esc_html( $invoice->get_number() )
									);

									$event_date = $ticket->get_event_date();

									if ( $event_date ) {
										$event_date = '<span class="geodir-ticket-date text-nowrap">' . $event_date . ' </span>';

										if ( $event_time = $ticket->get_event_time() ) {
											$event_date .= '<br><span class="geodir-ticket-time text-nowrap">' . $event_time . '</span>';
										}
									}
							?>
								<tr>
									<th scope="row"><?php echo esc_html( $ticket->get_number() ) . ( $event_date ? '<small class="fs-xs text-sm text-muted lh-sm d-block">' . wp_kses_post( $event_date ) . '</small>' : '' ); ?></th>
									<td class="text-center"><?php echo esc_html( get_the_title( $ticket->get_type() ) ); ?></td>
									<td class="text-center"><?php echo esc_html( $status ); ?></td>
									<td class="text-center"><?php echo esc_html( $date_created ); ?></td>
									<td class="text-center"><?php echo wp_kses_post( $invoice_url ); ?></td>
								</tr>

							<?php endforeach; ?>
						</tbody>
					</table>

				</div>
				<div class="modal-footer justify-content-start">
					<button type="button" data-<?php echo ( $aui_bs5 ? 'bs-' : '' ); ?>dismiss="modal" class="btn btn-primary"><?php _e( 'Close', 'geodir-tickets' ); ?></button>
				</div>
			</div>
		</div>
	</div>
</div>