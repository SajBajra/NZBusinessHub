<?php $sold_tickets = (int) geodir_get_tickets( array( 'type' => (int) $geodir_item->get_id() ), 'count' ); ?>
		<tr id="geodir-manage-ticket-type-item-<?php echo intval( $geodir_item->get_id() ); ?>">
			<th scope="row"><?php echo esc_html( $geodir_item->get_name() ); ?></th>
			<td class="text-center"><?php echo wpinv_price( $geodir_item->get_price() ); ?></td>
			<td class="text-center"><?php echo (int) $GLOBALS['getpaid_item_inventory']->inventory->available_stock( $geodir_item->get_id() ); ?></td>
			<td class="text-center"><?php echo ( 'ends' === get_post_meta( $geodir_item->get_id(), 'sell_till', true ) ) ? __( 'Event Ends', 'geodir-tickets' ) : __( 'Event Starts', 'geodir-tickets' ); ?></td>
			<td class="text-center gp-hide-if-expired">
				<a
					href="#"
					class="geodir-edit-ticket-type"
					title="<?php esc_attr_e( 'Edit Ticket', 'geodir-tickets' ); ?>"
					data-item="<?php echo intval( $geodir_item->get_id() ); ?>"
					data-owner="<?php echo intval( $geodir_item->get_author() ); ?>"
					data-ticket_name="<?php echo esc_attr( $geodir_item->get_name() ); ?>"
					data-ticket_price="<?php echo floatval( $geodir_item->get_price() ); ?>"
					data-default="<?php echo (int) get_post_meta( $geodir_item->get_id(), 'selected_by_default', true ); ?>"
					data-till="<?php echo ( 'ends' === get_post_meta( $geodir_item->get_id(), 'sell_till', true ) ) ? 'ends' : 'starts'; ?>"
					data-ticket_count="<?php echo intval( $GLOBALS['getpaid_item_inventory']->inventory->available_stock( $geodir_item->get_id() ) ); ?>"
				><i class="fas fa-edit"></i></a>
				&nbsp;
				<?php if ( $sold_tickets > 0 ) { ?>
				<a
					href="javascript:void(0)"
					class="text-muted cursor-default" 
					onclick="alert('<?php echo addslashes( esc_attr__( 'This ticket cannot be deleted because it purchased by the user.', 'geodir-tickets' ) ); ?>');return false;"
				><i class="fas fa-trash"></i></a>
				<?php } else { ?>
				<a
					href="#"
					class="geodir-delete-ticket-type text-danger"
					title="<?php esc_attr_e( 'Delete Ticket', 'geodir-tickets' ); ?>"
					data-action="geodir_delete_ticket"
					data-item="<?php echo intval( $geodir_item->get_id() ); ?>"
					data-_wpnonce="<?php echo wp_create_nonce( 'geodir_delete_ticket' . $geodir_item->get_id() ); ?>"
				><i class="fas fa-trash"></i></a>
				<?php } ?>
			</td>
		</tr>
