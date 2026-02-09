<?php
    global $wpdb;

    // Displays the report tab.
    defined( 'ABSPATH' ) || exit;

    $table    = $wpdb->prefix . 'geodir_tickets';
    $_tickets = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT invoice_id, `type`, `status`, COUNT(id) as qty, SUM(seller_price) as seller_price, SUM(site_commision) as site_commision, SUM(price) as price FROM $table WHERE event_id=%d GROUP BY invoice_id, `type` ORDER BY date_created ASC;",
            (int) $post_id
        )
    );

    $tickets = array();

    foreach ( $_tickets as $ticket ) {

        if ( ! isset( $tickets[ $ticket->invoice_id ] ) ) {
            $tickets[ $ticket->invoice_id ] = array(
                'invoice_id'     => $ticket->invoice_id,
                'qty'            => 0,
                'seller_price'   => 0,
                'site_commision' => 0,
                'price'          => 0,
                'status'         => $ticket->status,
                'types'          => array(),
            );
        }

        $tickets[ $ticket->invoice_id ]['qty']            += (float) $ticket->qty;
        $tickets[ $ticket->invoice_id ]['seller_price']   += (float) $ticket->seller_price;
        $tickets[ $ticket->invoice_id ]['site_commision'] += (float) $ticket->site_commision;
        $tickets[ $ticket->invoice_id ]['price']          += (float) $ticket->price;

        $tickets[ $ticket->invoice_id ]['types'][ (int) $ticket->type] = (float) $ticket->qty;

    }

    if ( empty( $tickets ) ) {
        echo aui()->alert(
            array(
                'content' => __( 'No tickets sold yet', 'geodir-tickets' ),
            )
        );

        return;
    }
?>
<table data-toggle="table" data-sortable="true" data-pagination="true" data-search="true" class="table my-3 geodir-manage-ticket-sales-table">
	<thead>
		<tr>
			<th scope="col"><?php _e( 'Buyer', 'geodir-tickets' ); ?></th>
			<th scope="col" class="text-center"><?php _e( 'Paid', 'geodir-tickets' ); ?></th>
            <th scope="col" class="text-center"><?php printf( __( 'Fees (%s%%)', 'geodir-tickets' ), geodir_tickets_get_commision_percentage() ); ?></th>
            <th scope="col" class="text-center"><?php _e( 'Earnings', 'geodir-tickets' ); ?></th>
			<th scope="col" class="text-center"><?php _e( 'Tickets', 'geodir-tickets' ); ?></th>
			<th scope="col" class="text-center"><?php _e( 'Actions', 'geodir-tickets' ); ?></th>
		</tr>
	</thead>
	<tbody>
		<?php

            foreach ( $tickets as $ticket ) :

                $invoice = wpinv_get_invoice( $ticket['invoice_id'] );

                if ( empty( $invoice ) ) {
                    continue;
                }

                $types = '<ul class="list-unstyled p-0 m-0">';

                foreach ( $ticket['types'] as $type => $qty ) {
                    $types .= '<li class="list-unstyled p-0 m-0">' . strip_tags( get_the_title( $type ) ) . ': (' . (float) $qty . ')</li>';
                }

                $types .= '</ul>';

				$event_dates = geodir_get_invoice_ticket_dates( $ticket['invoice_id'], $post_id );
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
				}
        ?>
            <tr>
                <th scope="row"><?php echo esc_html( $invoice->get_full_name() ) . ( $event_date ? '<small class="fs-xs text-sm text-muted lh-sm d-block">' . wp_kses_post( $event_date ) . '</small>' : '' ); ?></th>
                <td class="text-center"><?php echo wpinv_price( $ticket['price'], $invoice->get_currency() ); ?></td>
                <td class="text-center"><?php echo wpinv_price( $ticket['site_commision'], $invoice->get_currency() ); ?></td>
                <td class="text-center"><?php echo wpinv_price( $ticket['seller_price'], $invoice->get_currency() ); ?></td>
			    <td class="text-center">
                    <a href="#" class="geodir-tickets-view-sales-types d-inline-block c-pointer" data-<?php echo ( $aui_bs5 ? 'bs-' : '' ); ?>toggle="tooltip" data-<?php echo ( $aui_bs5 ? 'bs-' : '' ); ?>html="true" data-<?php echo ( $aui_bs5 ? 'bs-' : '' ); ?>container=".geodir-manage-ticket-sales-table" title="<?php echo esc_attr( $types ); ?>"><?php echo (int) $ticket['qty']; ?></a>
                </td>
                <td class="text-center">
                    <?php if ( $invoice->is_paid() ) { ?>
                        <a
                            href="#"
                            class="geodir-resend-ticket"
                            title="<?php esc_attr_e( 'Resend Tickets', 'geodir-tickets' ); ?>"
                            data-action="geodir_resend_ticket"
                            data-invoice="<?php echo intval( $invoice->get_id() ); ?>"
                            data-_wpnonce="<?php echo wp_create_nonce( 'geodir_resend_invoice_tickets_' . $invoice->get_id() ); ?>"
                        ><i class="fas fa-envelope"></i></a>
                        &nbsp;
                        <a
                            href="#"
                            class="geodir-refund-ticket"
                            title="<?php esc_attr_e( 'Refund Ticket', 'geodir-tickets' ); ?>"
                            data-action="geodir_refund_ticket"
                            data-invoice="<?php echo intval( $invoice->get_id() ); ?>"
                            data-_wpnonce="<?php echo wp_create_nonce( 'geodir_refund_invoice_tickets_' . $invoice->get_id() ); ?>"
                        ><i class="fas fa-undo"></i></a>
                    <?php } else { ?>
                    <?php echo esc_html( geodir_ticket_status_name( $ticket['status'] ) ); ?>
                    <?php } ?>
                </td>
		</tr>

        <?php endforeach; ?>
	</tbody>
</table>
