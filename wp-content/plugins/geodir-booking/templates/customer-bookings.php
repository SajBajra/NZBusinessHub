<?php

/**
 * This template renders all bookings for a given customer.
 *
 * You can override this template by copying it to your-theme-folder/geodir-booking/customer-bookings.php
 *
 * @since 1.0.0
 * @var int $user_id The customer's user ID.
 * @var bool $is_preview Whether this is a preview.
 * @var string $wrap_class The wrapper class.
 */

// Exit if accessed directly.
defined('ABSPATH') || exit;

// Get the bookings.
$user = get_user_by( 'id', $user_id );

if ( ! $user || empty( $user->user_email ) ) {
	aui()->alert(
		array(
			'type'    => 'danger',
			'content' => __( 'User email not found.', 'geodir-booking' ),
		),
		true
	);
	return;
}

$bookings = geodir_get_bookings( array( 'email' => $user->user_email ) );

if ( empty ( $bookings ) ) {
	aui()->alert(
		array(
			'type'    => 'info',
			'content' => __( 'No bookings found.', 'geodir-booking' ),
		),
		true
	);
	return;
}

$columns = array(
	'number'         => '#',
	'listing'        => __( 'Listing', 'geodir-booking' ),
	'status'         => __( 'Status', 'geodir-booking' ),
	'check_in'       => __( 'Check-in', 'geodir-booking' ),
	'check_out'      => __( 'Checkout', 'geodir-booking' ),
	'payable_amount' => __( 'Booking Amount', 'geodir-booking' ),
	'service_fee'    => __( 'Service Fee', 'geodir-booking' ),
);

global $aui_bs5;
?>
<div class="<?php echo esc_attr( $wrap_class ); ?>">
	<div class="position-relative table-responsive">
		<table class="table table-hover align-middle geodir-booking-table-md" style="min-width:992px">
			<thead>
				<tr>
					<?php foreach ( $columns as $key => $label ) : ?>
						<th class="geodir-booking-col geodir-booking-col-<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></th>
					<?php endforeach; ?>
					<th class="geodir-booking-col geodir-booking-col-actions">&nbsp;</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $bookings as $booking ) : ?>
					<tr class="geodir-customer-booking-<?php echo esc_attr( $booking->id ); ?>">

						<?php foreach ( $columns as $key => $label ) : ?>
							<td class="geodir-booking-col geodir-booking-col-<?php echo esc_attr( $key ); ?>" title="<?php echo esc_attr( $label ); ?>">
								<?php
									switch ( $key ) {
										case 'listing':
											?>
											<a href="<?php echo esc_url( geodir_get_listing_url( $booking->listing_id ) ); ?>">
												<?php echo esc_html( get_the_title( $booking->listing_id ) ); ?>
											</a>
											<?php
											break;
										case 'status':
											echo wp_kses_post( $booking->get_status_html() );
											break;
										case 'check_in':
											echo esc_html( geodir_booking_date( $booking->start_date, 'view_day' ) );
											break;
										case 'check_out':
											echo esc_html( geodir_booking_date( $booking->end_date, 'view_day' ) );
											break;
										case 'payable_amount':
											echo wp_kses_post( wpinv_price( $booking->payable_amount ) );
											break;
										case 'service_fee':
											echo wp_kses_post( wpinv_price( $booking->service_fee ) );
											break;
										case 'number':
											echo esc_html( $booking->id );
											break;
									}
								?>
							</td>
						<?php endforeach; ?>

						<td class="geodir-booking-col geodir-booking-col-actions align-middle">
							<?php if ( ! empty( $is_preview ) ) : ?>
								<button class="btn geodir-customer-booking-view-details-button btn-outline-primary btn-sm">
									<?php esc_html_e( 'View Details', 'geodir-booking' ); ?>
								</button>
							<?php else : ?>
								<button class="btn geodir-customer-booking-view-details-button btn-outline-primary btn-sm" data-listing="<?php echo esc_attr( wp_json_encode( $booking->get_listing_details() ) ); ?>" data-booking="<?php echo esc_attr( wp_json_encode( $booking->get_customer_details() ) ); ?>" data-cancel-details="<?php echo esc_attr( wp_json_encode( $booking->get_cancel_details() ) ); ?>">
									<?php esc_html_e( 'View Details', 'geodir-booking' ); ?>
								</button>
							<?php endif; ?>
							<?php if ( $booking->get_pay_link() ) : ?>
								<span class="d-inline-block ms-1 ml-1">
									<?php echo wp_kses_post( $booking->get_pay_link() ); ?>
								</span>
							<?php endif; ?>
						</td>

					</tr>
				<?php endforeach; ?>
			</tbody>

		</table>
	</div>
</div>

<?php
/**
 * Render customer booking modal.
 *
 * @since 2.1.1
 *
 * @param array $modals Modal types.
 * @param array $bookings Bookings array.
 */
do_action( 'geodir_booking_customer_booking_render_modals', array( 'user' => $user, 'bookings' => $bookings ) );
?>