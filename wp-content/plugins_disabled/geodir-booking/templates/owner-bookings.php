<?php

/**
 * This template renders all bookings for a given owner.
 *
 * You can overide this template by copying it to your-theme-folder/geodir-booking/owner-bookings.php
 *
 * @since 1.0.0
 * @var int $user_id The owner's user ID.
 * @var bool $is_preview Whether this is a preview.
 * @var string $wrap_class The wrapper class.
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

$gd_booking_add_page = GeoDir_Booking_Add_Booking_Page::instance();

// Abort if the user has no listings.
$listings         = geodir_booking_get_listing_ids_by_user_id( $user_id );
$_listings_options = array();

foreach ( $listings as $listing_id ) {
	$listing_id = geodir_booking_post_id( $listing_id );
	$listing = geodir_get_post_info( $listing_id );

	if ( isset( $listing->gdbooking ) && $listing->gdbooking === '1' ) {
		$_listings_options[ $listing->ID ] = $listing->post_title;
	}
}

if ( empty( $_listings_options ) ) {
	aui()->alert(
		array(
			'type'    => 'info',
			'content' => __( 'No listings found.', 'geodir-booking' ),
		),
		true
	);
	return;
}

$listings_options = array_replace( $gd_booking_add_page->empty_options, $_listings_options );

// Get the bookings.
$bookings = geodir_get_bookings( array( 'listings' => $listings ) );

if ( empty( $bookings ) ) {
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
	'number'    => '#',
	'listing'   => __( 'Listing', 'geodir-booking' ),
	'status'    => __( 'Status', 'geodir-booking' ),
	'client'    => __( 'Client', 'geodir-booking' ),
	'booked'    => __( 'Booked', 'geodir-booking' ),
	'check_in'  => __( 'Check-in', 'geodir-booking' ),
	'check_out' => __( 'Checkout', 'geodir-booking' ),
	'earnings'  => __( 'You Receive', 'geodir-booking' ),
);

global $aui_bs5;
?>
<div class="<?php echo esc_attr( $wrap_class ); ?>">
	<div class="position-relative table-responsive">
		<button type="button" class="btn btn-sm btn-primary mb-3 geodir-owner-booking-add-button">
			<i class="fas fa-plus me-1 fa-fw"></i>&nbsp;<?php esc_html_e( 'Add New Booking', 'geodir-booking' ); ?>
		</button>
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
					<tr>
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
									case 'client':
										?>

											<a href="mailto:<?php echo esc_attr( $booking->email ); ?>" title="<?php echo esc_attr( wp_sprintf( __( 'Send email to %s', 'geodir-booking' ), esc_html( $booking->name ) ) ); ?>"><?php echo esc_html( $booking->name ); ?></a>

											<?php if ( ! empty( $booking->phone ) ) : ?>
												<a href="tel: <?php echo esc_attr( $booking->phone ); ?>" class="small form-text d-block text-muted" title="<?php echo esc_attr( wp_sprintf( __( 'Call to %s', 'geodir-booking' ), esc_html( $booking->name ) ) ); ?>"><?php echo esc_html( $booking->phone ); ?></a>
											<?php endif; ?>

											<?php
										break;

									case 'booked':
										echo esc_html( geodir_booking_date( $booking->created, 'view_day' ) );
										break;

									case 'check_in':
										echo esc_html( geodir_booking_date( $booking->start_date, 'view_day' ) );
										break;

									case 'check_out':
										echo esc_html( geodir_booking_date( $booking->end_date, 'view_day' ) );
										break;

									case 'earnings':
										echo wp_kses_post( wpinv_price( $booking->deposit_amount - $booking->site_commission ) );
										break;

									case 'number':
										echo esc_html( $booking->id );
										break;
								}
								?>
							</td>
						<?php endforeach; ?>

						<td class="geodir-booking-col geodir-booking-col-actions align-middle">
							<div class="dropdown">
								<button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id='<?php echo esc_attr( "dropdown-menu-btn-{$booking->id}" ); ?>' <?php echo $aui_bs5 ? 'data-bs-toggle="dropdown"' : 'data-toggle="dropdown"'; ?> aria-expanded="false">
									<?php esc_html_e( 'Actions', 'geodir-booking' ); ?>
								</button>
								<ul class="dropdown-menu" aria-labelledby="<?php echo esc_attr( "dropdown-menu-btn-{$booking->id}" ); ?>">
									<?php if ( ! empty( $is_preview ) ) : ?>
										<li>
											<a class="geodir-owner-booking-view-details-button dropdown-item" href="javascript:void(0);"><?php esc_html_e( 'View Details', 'geodir-booking' ); ?></a>
										</li>
									<?php else : ?>
										<li>
											<a class="geodir-owner-booking-view-details-button dropdown-item" href="javascript:void(0);" data-listing="<?php echo esc_attr( wp_json_encode( $booking->get_listing_details() ) ); ?>" data-booking="<?php echo esc_attr( wp_json_encode( $booking->to_array() ) ); ?>"><?php esc_html_e( 'View Details', 'geodir-booking' ); ?></a>
										</li>
									<?php endif; ?>

									<li>
										<a class="geodir-owner-booking-edit-details-button dropdown-item" href="javascript:void(0);" data-listing="<?php echo esc_attr( wp_json_encode( $booking->get_listing_details() ) ); ?>" data-booking="<?php echo esc_attr( wp_json_encode( $booking->to_array() ) ); ?>"><?php esc_html_e( 'Edit Booking', 'geodir-booking' ); ?></a>
									</li>
									
									<li>
										<a class="geodir-owner-booking-delete-button dropdown-item" href="javascript:void(0);" data-booking="<?php echo esc_attr( $booking->get_id() ); ?>"><?php esc_html_e( 'Delete', 'geodir-booking' ); ?></a>
									</li>
								</ul>
							</div>
						</td>

					</tr>
				<?php endforeach; ?>
			</tbody>

		</table>
	</div>
</div>
<?php do_action( 'geodir_booking_owner_booking_render_modals', $user_id, null, $listings, $listings_options ); ?>
