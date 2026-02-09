<?php
defined( 'ABSPATH' ) || exit;

global $aui_bs5;
?>
<table class="table my-3 geodir-manage-ticket-types-table">
	<thead>
		<tr>
			<th scope="col"><?php _e( 'Type', 'geodir-tickets' ); ?></th>
			<th scope="col" class="text-center"><?php _e( 'Price', 'geodir-tickets' ); ?></th>
			<th scope="col" class="text-center"><?php _e( 'Remaining', 'geodir-tickets' ); ?></th>
			<th scope="col" class="text-center"><?php _e( 'Sold Till', 'geodir-tickets' ); ?></th>
			<th scope="col" class="text-center gp-hide-if-expired"><?php _e( 'Actions', 'geodir-tickets' ); ?></th>
		</tr>
	</thead>
	<tbody>
		<?php

			foreach ( $ticket_types as $ticket_type ) {

				$geodir_item = new WPInv_Item( (int) $ticket_type );

				if ( $geodir_item->exists() ) {
					include plugin_dir_path( __FILE__ ) . 'type-row.php';
				}

			}
		?>
	</tbody>
</table>

<div class="<?php echo ( $aui_bs5 ? 'mb-3 d-grid gap-2' : 'form-group' ); ?> gp-hide-if-expired">
	<button class="btn<?php echo ( $aui_bs5 ? '' : ' btn-block' ); ?> btn-outline-primary geodir-add-ticket-type"><i class="fas fa-plus" aria-hidden="true"></i> <?php echo esc_html_e( 'Add Another Ticket', 'geodir-tickets' ); ?></button>
</div>

<form class="geodir-add-ticket-type-form" style="display: none;">
	<div class="<?php echo ( $aui_bs5 ? 'mb-3' : 'form-group' ); ?>">
		<label class="d-block">
			<span class="mb-1 d-inline-block"><?php echo _e( 'Ticket Name', 'geodir-tickets' ); ?></span>
			<input name="geodir_ticket_name" type="text" class="form-control geodir-ticket-name-input" placeholder="<?php echo esc_attr_e( 'e.g Regular', 'geodir-tickets' ); ?>" required="required">
		</label>
	</div>

	<div class="<?php echo ( $aui_bs5 ? 'row' : 'form-row' ); ?>">

		<div class="<?php echo ( $aui_bs5 ? 'mb-3' : 'form-group' ); ?> col-md-6">
			<label class="d-block">
				<span class="mb-1 d-inline-block"><?php echo _e( 'Ticket Price', 'geodir-tickets' ); ?></span>
				<input name="geodir_ticket_price" type="number" min="0.00" step="0.01" class="form-control geodir-ticket-price-input" placeholder="<?php echo esc_attr_e( 'Price per ticket', 'geodir-tickets' ); ?>">
			</label>
		</div>

		<div class="<?php echo ( $aui_bs5 ? 'mb-3' : 'form-group' ); ?> col-md-6">
			<label class="d-block">
				<span class="mb-1 d-inline-block"><?php echo _e( 'Available Quantity', 'geodir-tickets' ); ?></span>
				<input name="geodir_ticket_quantity" type="number" class="form-control geodir-ticket-quantity-input" placeholder="<?php echo esc_attr_e( '# of available tickets', 'geodir-tickets' ); ?>">
			</label>
		</div>

	</div>

	<div class="<?php echo ( $aui_bs5 ? 'mb-3' : 'form-group' ); ?>">
		<label class="d-block">
			<input name="geodir_ticket_selected_by_default" type="checkbox" value="1" class="geodir-ticket-selected-by-default">
			<span><?php echo _e( 'Selected by default', 'geodir-tickets' ); ?></span>
		</label>
	</div>

	<div class="<?php echo ( $aui_bs5 ? 'mb-3' : 'form-group' ); ?>">
		<label class="d-block">
			<input name="geodir_sell_tickets_till" type="radio" value="starts" class="geodir-ticket-sell-till" checked="checked">
			<span><?php echo _e( 'Sell tickets till the event starts (once the event start time kicks off, tickets can no longer be bought)', 'geodir-tickets' ); ?></span>
		</label>
	</div>

	<div class="<?php echo ( $aui_bs5 ? 'mb-3' : 'form-group' ); ?>">
		<label class="d-block">
			<input name="geodir_sell_tickets_till" type="radio" value="ends" class="geodir-ticket-sell-till">
			<span><?php echo _e( 'Sell tickets till the event ends (ticket can be purchased till the event ends)', 'geodir-tickets' ); ?></span>
		</label>
	</div>

	<div class="mt-3  d-inline-block <?php echo ( $aui_bs5 ? '' : 'form-group' ); ?>">
		<button class="btn btn-primary geodir-add-ticket-type-form-submit"><?php _e( 'Save', 'geodir-tickets' ); ?></button>
		<button type="submit" class="btn btn-secondary mx-2 geodir-add-ticket-type-form-cancel"><?php _e( 'Cancel', 'geodir-tickets' ); ?></button>
	</div>

	<input name="geodir_item_id" type="hidden" class="form-control geodir-ticket-item-id-input" />
	<input name="_wpnonce" type="hidden" value="<?php echo wp_create_nonce( 'geodir_edit_ticket' ); ?>" />
	<input name="action" type="hidden" value="geodir_edit_ticket" />
	<input name="listing_id" type="hidden" value="<?php echo esc_attr( $listing->ID ); ?>" />

</form>
