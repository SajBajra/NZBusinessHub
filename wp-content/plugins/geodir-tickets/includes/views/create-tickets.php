<?php
defined( 'ABSPATH' ) || exit;

global $aui_bs5;
?>
<div class="bsui geodir-tickets-modal">
	<div class="modal fade" id="geodir-sell-tickets-modal<?php echo esc_attr( $id ); ?>" tabindex="-1" aria-labelledby="geodir-sell-tickets-title<?php echo esc_attr( $id ); ?>" aria-hidden="true">
		<div class="modal-dialog modal-dialog-centered">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title" id="geodir-sell-tickets-title<?php echo esc_attr( $id ); ?>"><?php _e( 'Create Tickets', 'geodir-tickets' ); ?></h5>
					<?php if ( $aui_bs5 ) { ?>
						<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?php esc_attr_e( 'Close', 'geodir-tickets' ); ?>"></button>
					<?php } else { ?>
						<button type="button" class="btn-close close" data-dismiss="modal" aria-label="<?php esc_attr_e( 'Close', 'geodir-tickets' ); ?>">&times;</button>
					<?php } ?>
				</div>
				<div class="modal-body">

					<?php
					$commission = geodir_tickets_get_commision_percentage();
					if ( $commission ) {
						echo aui()->alert(
							array(
								'type'=> 'info',
								'content' => sprintf(
								/* translators: %s: commission amount */
									__( 'Fees & commission on ticket sales is %s%%', 'geodir-tickets' ),
									$commission
								),
							)
						);
					}
					?>
					<form class="geodir-sell-tickets-form">
						<div class="geodir-ticket-types-wrapper mb-3 accordion">
							<div class="geodir-ticket-type-wrapper card">

								<div class="card-header d-none bg-white">
									<h5 class="mb-0">
										<button class="btn btn-link geodir-tickets-toggle-accordion" type="button"><?php _e( 'Ticket Details', 'geodir-tickets' ); ?></button>
									</h5>
								</div>

								<div class="collapse show">
									<div class="card-body">
										<div class="<?php echo ( $aui_bs5 ? 'mb-3' : 'form-group' ); ?>">
											<label class="d-block">
												<span><?php echo _e( 'Ticket Name', 'geodir-tickets' ); ?></span>
												<input name="geodir[ticket_names][0]" data-name="geodir[ticket_names]" type="text" class="form-control" placeholder="<?php echo esc_attr_e( 'e.g Regular', 'geodir-tickets' ); ?>">
											</label>
										</div>

										<div class="<?php echo ( $aui_bs5 ? 'row' : 'form-row' ); ?>">

											<div class="<?php echo ( $aui_bs5 ? 'mb-3' : 'form-group' ); ?> col-md-6">
												<label class="d-block">
													<span><?php echo _e( 'Ticket Price', 'geodir-tickets' ); ?></span>
													<input name="geodir[ticket_prices][0]" data-name="geodir[ticket_prices]" type="number" min="0.00" step="0.01" class="form-control" placeholder="<?php echo esc_attr_e( 'Price per ticket', 'geodir-tickets' ); ?>">
												</label>
											</div>

											<div class="<?php echo ( $aui_bs5 ? 'mb-3' : 'form-group' ); ?> col-md-6">
												<label class="d-block">
													<span><?php echo _e( 'Available Quantity', 'geodir-tickets' ); ?></span>
													<input name="geodir[ticket_quantity][0]" data-name="geodir[ticket_quantity]" type="number" class="form-control" placeholder="<?php echo esc_attr_e( '# of available tickets', 'geodir-tickets' ); ?>">
												</label>
											</div>

										</div>

										<div class="<?php echo ( $aui_bs5 ? 'mb-3' : 'form-group' ); ?> ">
											<label class="d-block">
												<input name="geodir[ticket_default][0]" data-name="geodir[ticket_default]" type="checkbox" value="1" class="geodir-ticket-selected-by-default">
												<span><?php echo _e( 'Selected by default', 'geodir-tickets' ); ?></span>
											</label>
										</div>

										<div class="<?php echo ( $aui_bs5 ? 'mb-3' : 'form-group' ); ?> ">
											<label class="d-block">
												<input name="geodir[sell_tickets_till][0]" data-name="geodir[sell_tickets_till]" type="radio" value="starts" class="geodir-ticket-sell-till" checked="checked">
												<span><?php echo _e( 'Sell tickets till the event starts (once the event start time kicks off, tickets can no longer be bought)', 'geodir-tickets' ); ?></span>
											</label>
										</div>

										<div class="<?php echo ( $aui_bs5 ? 'mb-3' : 'form-group' ); ?> ">
											<label class="d-block">
												<input name="geodir[sell_tickets_till][0]" data-name="geodir[sell_tickets_till]" type="radio" value="ends" class="geodir-ticket-sell-till">
												<span><?php echo _e( 'Sell tickets till the event ends (ticket can be purchased till the event ends)', 'geodir-tickets' ); ?></span>
											</label>
										</div>

										<a href="#" class="btn btn-sm btn-outline-danger small d-block geodir-remove-ticket d-none"><i class="fas fa-minus" aria-hidden="true"></i> <?php _e( 'Remove Ticket', 'geodir-tickets' ); ?></a>
									</div>
								</div>

							</div>
						</div>
						<input type="hidden" name="action" value="geodir_sell_tickets" />
						<input type="hidden" name="_wpnonce" value="<?php echo esc_attr( wp_create_nonce( 'geodir_sell_tickets_nonce' ) ); ?>" />
						<input type="hidden" name="listing_id" value="<?php echo esc_attr( $post_id ); ?>" />
					</form>

					<div class="<?php echo ( $aui_bs5 ? 'mb-3 d-grid gap-2' : 'form-group' ); ?>">
						<button class="btn<?php echo ( $aui_bs5 ? '' : ' btn-block' ); ?> btn-outline-primary geodir-tickets-duplicate"><i class="fas fa-plus" aria-hidden="true"></i> <?php echo esc_html_e( 'Add Another Ticket', 'geodir-tickets' ); ?></button>
					</div>

				</div>
				<div class="modal-footer justify-content-start">
					<button type="button" class="btn btn-primary geodir-submit-sell-tickets-form"><?php _e( 'Save Tickets', 'geodir-tickets' ); ?></button>
				</div>
			</div>
		</div>
	</div>
</div>