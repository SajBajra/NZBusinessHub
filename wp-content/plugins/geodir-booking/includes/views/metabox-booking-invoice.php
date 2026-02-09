<?php
/**
 * Admin View: Booking invoice metabox.
 *
 * @var GeoDir_Customer_Booking $booking The booking object.
 */
defined( 'ABSPATH' ) || exit;

global $aui_bs5;

$columns = apply_filters(
	'geodir_booking_invoice_columns',
	array(
		'invoice'      => __( 'Invoice', 'invoicing' ),
		'date'         => __( 'Created', 'invoicing' ),
		'paid'         => __( 'Paid', 'invoicing' ),
		'status'       => __( 'Status', 'invoicing' ),
		'total'        => __( 'Total', 'invoicing' ),
	),
	$booking
);

$table_class = geodir_design_style() ? 'w-100 bg-white' : 'wp-list-table widefat fixed striped';

$invoice = wpinv_get_invoice( $booking->invoice_id );

if ( ! $invoice->is_paid() && isset( $columns['paid'] ) ) {
	unset( $columns['paid'] );
}
?>
	<style>
		#poststuff #gd-booking-invoice .inside {
   			margin: 0;
    		padding: 0;
		}

		#poststuff #gd-booking-invoice .postbox-header {
			display: none;
		}

		.getpaid-invoice-status-wpi-pending {
			background-color: #f9fbe7;
			color: #33691e;
			border-bottom: 1px solid #dcedc8;
		}

		.getpaid-invoice-status-wpi-cancelled {
			background-color: #ede7f6;
			color: #311b92;
			border-bottom: 1px solid #d1c4e9;
		}

		.getpaid-invoice-status-wpi-failed {
			background-color: #fff3e0;
			border-bottom: 1px solid #ffe0b2;
			color: #bf360c;
		}

		.getpaid-invoice-status-wpi-renewal {
			background-color:#e0f7fa;
			border-bottom: 1px solid#bbdefb;
			color: #006064;
		}

		.getpaid-invoice-status-publish {
			background-color:#f1f8e9;
			border-bottom: 1px solid#dcedc8;
			color: #33691e;
		}

		.getpaid-invoice-status-wpi-onhold {
			background-color: #fbe9e7;
			border-bottom: 1px solid#ffccbc;
			color: #bf360c;
		}

		.getpaid-invoice-status-wpi-processing {
			background-color: #eceff1;
			border-bottom: 1px solid#9e9e9e;
			color: #263238;
		}

	</style>

	<div class="bsui" style="overflow: auto;">
		<table class="gd-listing-invoices <?php echo esc_attr( $table_class ); ?>">
			<thead>
				<tr>
					<?php
						foreach ( $columns as $key => $label ) {
							printf(
								'<th class="geodir-listing-invoice-field-%s bg-light p-2 color-dark ' . ( $aui_bs5 ? 'text-start' : 'text-left' ) . '">%s</th>',
								esc_attr( $key ),
								esc_html( $label )
							);
						}
					?>
				</tr>
			</thead>

			<tbody>
				<tr>
					<?php foreach ( array_keys( $columns ) as $key ) : ?>
						<td class="p-2 <?php echo ( $aui_bs5 ? 'text-start' : 'text-left' ); ?>">
							<?php
								switch( $key ) {

									case 'total':
										echo '<strong>' . wp_kses_post( wpinv_price( $invoice->get_total(), $invoice->get_currency() ) ) . '</strong>';
										break;

									case 'paid':
										echo esc_html( getpaid_format_date_value( $invoice->get_date_completed(), null, true ) );
										break;

									case 'date':
										echo esc_html( getpaid_format_date_value( $invoice->get_date_created(), null, true ) );
										break;

									case 'status':
										echo wp_kses_post( $invoice->get_status_label_html() );
										break;

									case 'invoice':
										printf(
											'<a href="%s">%s</a>',
											esc_url( get_edit_post_link( $invoice->get_id() ) ),
											esc_html( $invoice->get_number() )
										);
										break;
								}
							?>
						</td>
					<?php endforeach; ?>
				</tr>
				<tr>
					<td class="p-2 <?php echo ( $aui_bs5 ? 'text-start' : 'text-left' ); ?>" colspan="<?php echo absint( count( $columns ) ); ?>">
						<p class="description"><?php esc_html_e( 'Booking status automatically changes whenever the invoice status changes.', 'geodir-booking' ); ?></p>
					</td>
				</tr>
			</tbody>

		</table>
	</div>
