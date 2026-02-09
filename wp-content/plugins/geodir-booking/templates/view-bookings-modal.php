<?php
/**
 * @version 2.1.10
 */

defined( 'ABSPATH' ) || exit;

global $aui_bs5;

$listings = get_posts(
	array(
		'post_type'   => empty( $GLOBALS['geodir_booking_rendered_post_types'] ) ? geodir_get_posttypes() : array_unique( $GLOBALS['geodir_booking_rendered_post_types'] ),
		'post_status' => 'publish',
		'author'      => get_current_user_id(),
		'fields'      => 'ids',
		'numberposts' => 25,
		'parent'      => 0,
	)
);

// Make sure rendered listings are included.
if ( ! empty( $GLOBALS['geodir_booking_rendered_listings'] ) ) {
	$listings = array_merge( wp_parse_id_list( $GLOBALS['geodir_booking_rendered_listings'] ), $listings );
}

$prepared = array();

foreach ( $listings as $listing_id ) {
	$listing_id = geodir_booking_post_id( $listing_id );

	$prepared[] = $listing_id;

	$rooms = geodir_get_listing_rooms( $listing_id );

	if ( ! empty( $rooms ) ) {
		$prepared = array_merge( $prepared, $rooms );
	}
}

// Convert to array of objects.
$listings = array_map( 'get_post', array_unique( $prepared ) );
?>

<div class="bsui geodir-view-bookings-modal">
	<div class="modal fade" id="geodir-view-bookings-modal" tabindex="-1" aria-hidden="true">
		<div class="modal-dialog modal-xl modal-dialog-centered" :class="current_booking ? '' : 'modal-xl'">
			<div class="modal-content">
				<div class="modal-header">
					<h5 v-if="! current_booking" class="modal-title"><?php esc_html_e( 'Bookings', 'geodir-booking' ); ?></h5>
					<h5 v-else class="modal-title"><?php esc_html_e( 'Booking Details', 'geodir-booking' ); ?></h5>
					<?php if ( $aui_bs5 ) { ?>
						<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?php esc_attr_e( 'Close', 'geodir-booking' ); ?>"></button>
					<?php } else { ?>
						<button type="button" class="close" data-dismiss="modal" aria-label="<?php esc_attr_e( 'Close', 'geodir-booking' ); ?>">&times;</button>
					<?php } ?>
				</div>
				<div class="modal-body position-relative" style="min-height: 100px;" id="geodir-view-bookings-modal-body">

					<!-- View all listings... -->
					<div class="geodir-listings-table" v-if="!is_loading && ! current_booking">

						<div class="mb-3 geodir-booking-list-header d-flex justify-content-between align-items-center flex-wrap">
							<select v-model="currentTab" class="small <?php echo ( $aui_bs5 ? 'form-select form-select-sm' : 'custom-select custom-select-sm' ); ?>" style="width: 200px;">
								<option value="all"><?php esc_html_e( 'All', 'geodir-booking' ); ?></option>
								<option value="upcoming"><?php esc_html_e( 'Upcoming', 'geodir-booking' ); ?></option>
								<?php foreach ( geodir_get_booking_statuses() as $status => $label ) : ?>
									<option value="<?php echo esc_attr( $status ); ?>"><?php echo esc_html( $label ); ?></option>
								<?php endforeach; ?>
							</select>

							<select v-model="listing_id" class="small <?php echo ( $aui_bs5 ? 'form-select form-select-sm' : 'custom-select custom-select-sm' ); ?>" style="width: 200px;">
								<?php foreach ( $listings as $listing ) : ?>
									<option value="<?php echo esc_attr( $listing->ID ); ?>"><?php echo esc_html( $listing->post_title ); ?></option>
								<?php endforeach; ?>
							</select>
						</div>

						<!-- Check if there are records -->
						<div class="geodir-bookings-no-records alert alert-danger mt-2" role="alert" v-if="!hasBookings && !error">
							<?php esc_html_e( 'No bookings found.', 'geodir-booking' ); ?>
						</div>

						<!-- List bookings -->
						<div class="geodir-bookings-list table-responsive" v-if="hasBookings">
							<table class="table table-hover">

								<thead>
									<tr>
										<th>#</th>
										<th><?php esc_html_e( 'Status', 'geodir-booking' ); ?></th>
										<th><?php esc_html_e( 'Client', 'geodir-booking' ); ?></th>
										<th><?php esc_html_e( 'Check-in', 'geodir-booking' ); ?>/<?php esc_html_e( 'Checkout', 'geodir-booking' ); ?></th>
										<th><?php esc_html_e( 'Booked', 'geodir-booking' ); ?></th>
										<th><?php esc_html_e( 'You Receive', 'geodir-booking' ); ?></th>
										<th>&nbsp;</th>
									</tr>
								</thead> 

								<tbody>
									<tr v-for="booking in bookings">
										<td class="align-middle">
											<a href="#" @click.prevent="current_booking=booking">
												{{ booking.id }}
											</a>
										</td>
										<td class="align-middle">
											<span v-if="booking.is_imported" class="badge <?php echo ( $aui_bs5 ? 'rounded-pill' : 'badge-pill' ); ?> text-bg-salmon me-1"><?php esc_html_e( 'iCal', 'geodir-booking' ); ?></span>
											<span v-if="!booking.is_imported" class="badge <?php echo ( $aui_bs5 ? 'rounded-pill' : 'badge-pill' ); ?>" :class="badgeClass( booking )">{{ booking.status_label }}</span>
										</td>
										<td class="align-middle">
											<a :href="'mailto:' + booking.email" :title="'<?php echo esc_attr( wp_sprintf( addslashes( __( 'Send email to %s', 'geodir-booking' ) ), "' + booking.name + '" ) ); ?>'">{{ booking.name }}</a>
											<a v-if="booking.phone" :href="'tel:' + booking.phone" class=" small form-text d-block text-muted" :title="'<?php echo esc_attr( wp_sprintf( addslashes( __( 'Call to %s', 'geodir-booking' ) ), "' + booking.name + '" ) ); ?>'">{{ booking.phone }}</a>
										</td>
										<td class="align-middle">
											<div>
												<i class="fas fa-calendar fa-fw me-1" aria-hidden="true"></i>{{ booking.check_in }}
											</div>
											<div class="text-muted">
												<i class="fas fa-calendar fa-fw me-1" aria-hidden="true"></i>{{ booking.check_out }}
											</div>
										</td>
										<td class="align-middle">
											{{ booking.booking_date }}
											<small class="mt-0 form-text d-block text-muted">{{ booking.booking_time }}</small>
										</td>
										<td class="align-middle">
											{{ formatAmount( booking.deposit_amount - booking.site_commission ) }}
										</td>
										<td class="align-middle">
											<a class="btn btn-outline-primary btn-sm" href="#" @click.prevent="current_booking=booking">
												<?php esc_html_e( 'View Details', 'geodir-booking' ); ?>
											</a>
										</td>
									</tr>
								</tbody>
							</table>
						</div>
					</div>

					<!-- View booking details -->
					<div class="geodir-single-booking-details-wrapper" v-if="current_booking">

						<!-- Customer details -->
						<div class="mb-4 geodir-booking-customer-details d-flex justify-content-between align-items-center">
							<div>
								<h3 class="h6 mb-2">{{ current_booking.name }}</h3>
								<small class="form-text d-block text-muted">{{ current_booking.email }}</small>
								<a class="small form-text d-block" :href="current_booking.listing_url" target="_blank">{{ current_booking.listing_title }}</a>
							</div>
							<span v-if="current_booking.avatar" v-html="current_booking.avatar"></span>
						</div>

						<!-- Message / Call -->
						<div class="row mb-4 geodir-booking-message-call" v-if="current_booking.phone">
							<div class="col<?php echo ( $aui_bs5 ? '-sm-6' : '' ); ?> mb-1">
								<a :href="'sms:' + current_booking.phone" class="btn btn-outline-dark btn-sm <?php echo ( $aui_bs5 ? 'd-block' : 'btn-block' ); ?>" :title="'<?php echo esc_attr( wp_sprintf( addslashes( __( 'Send SMS to %s', 'geodir-booking' ) ), "' + current_booking.name + '" ) ); ?>'"><?php esc_html_e( 'Message', 'geodir-booking' ); ?></a>
							</div>
							<div class="col<?php echo ( $aui_bs5 ? '-sm-6' : '' ); ?> mb-1">
								<a :href="'tel:' + current_booking.phone" class="btn btn-outline-dark btn-sm <?php echo ( $aui_bs5 ? 'd-block' : 'btn-block' ); ?>" :title="'<?php echo esc_attr( wp_sprintf( addslashes( __( 'Call to %s', 'geodir-booking' ) ), "' + current_booking.name + '" ) ); ?>'"><?php esc_html_e( 'Call', 'geodir-booking' ); ?></a>
							</div>
							<div class="col-12 text-center form-text d-block text-muted small">
								<?php
									printf(
										esc_html( 'Phone: %s', 'geodir-booking' ),
										'{{current_booking.phone}}'
									);
									?>
							</div>
						</div>

						<!-- Booking details -->
						<div class="mb-4 geodir-booking-details">
							<ul class="list-group">
								<li class="list-group-item d-flex flex-wrap justify-content-between align-items-center" v-for="(detail, key) in current_booking.details" :class="'geodir-booking-detail-' + key" :key="key">
									<strong>{{ detail.label }}</strong>
									<span class="text-muted" v-html="detail.value"></span>
								</li>
							</ul>
						</div>

						<!-- Amount breakdown -->
						<div class="mb-4 geodir-booking-amount-breakdown">
							<h3 class="h6"><?php esc_html_e( 'Amount breakdown', 'geodir-booking' ); ?></h3>
							<ul class="list-group">
								<li class="list-group-item d-flex flex-wrap justify-content-between align-items-center" v-for="date_amount in current_booking.date_amounts_fmt">
									<strong>{{ date_amount.date }}</strong>
									<span class="text-muted">{{ formatAmount( date_amount.amount ) }}</span>
								</li>
							</ul>
						</div>

						<!-- Booking action -->
						<div class="mb-4 geodir-booking-set-status" v-if="current_booking.old_status == 'pending_confirmation'">
							<label for="geodir-booking-set-status" class="form-label"><?php esc_html_e( 'Booking Action', 'geodir-booking' ); ?></label>
							<select class="form-control <?php echo ( $aui_bs5 ? 'form-select form-select-sm' : 'custom-select custom-select-sm' ); ?>" id="geodir-booking-set-status" v-model="current_booking.status">
								<option value="pending_confirmation"><?php esc_html_e( 'Select Action', 'geodir-booking' ); ?></option>
								<option value="confirmed"><?php esc_html_e( 'Approve', 'geodir-booking' ); ?></option>
								<option value="rejected"><?php esc_html_e( 'Reject', 'geodir-booking' ); ?></option>
							</select>
							<small class="form-text d-block text-muted"><?php esc_html_e( 'You will not be able to undo this change.', 'geodir-booking' ); ?></small>
						</div>

						<!-- Booking notes -->
						<div class="mb-4 geodir-booking-private-note">
							<label for="geodir-booking-notes" class="form-label"><?php esc_html_e( 'Private Note', 'geodir-booking' ); ?></label>
							<textarea class="form-control" id="geodir-booking-notes" rows="3" v-model="current_booking.private_note"></textarea>
							<small class="form-text d-block text-muted"><?php esc_html_e( 'This note will not be visible to the client.', 'geodir-booking' ); ?></small>
						</div>

						<!-- Save booking -->
						<div class="<?php echo ( $aui_bs5 ? 'mb-3' : 'form-group' ); ?> geodir-booking-save">
							<button type="button" class="btn btn-primary" @click="saveBooking( current_booking )">
								<span class="spinner-border spinner-border-sm" role="status" v-if="current_booking.saving" aria-hidden="true"></span>&nbsp;
								<span v-if="current_booking.saving"><?php esc_html_e( 'Saving...', 'geodir-booking' ); ?></span>
								<span v-if="!current_booking.saving"><?php esc_html_e( 'Save', 'geodir-booking' ); ?></span>
							</button>&nbsp;
							<button type="button" class="btn btn-danger" @click="cancelBooking( current_booking )" v-if="'confirmed' === current_booking.status">
								<?php esc_html_e( 'Cancel Booking', 'geodir-booking' ); ?>
							</button>&nbsp;
							<button class="btn btn-secondary text-white" href="#" @click.prevent="current_booking=null">
								<?php esc_html_e( 'Go Back', 'geodir-booking' ); ?>
							</button>
						</div>
					</div>

					<!-- Errors -->
					<div class="geodir-bookings-error alert alert-danger mt-2" role="alert" v-if="error">{{error}}</div>

					<div class="w-100 h-100 position-absolute bg-light d-flex justify-content-center align-items-center getpaid-block-ui" style="top: 0; left: 0; opacity: 0.7; cursor: progress;" v-if="is_loading"><div class="spinner-border" role="status"><span class="sr-only visually-hidden"><?php esc_html_e( 'Loading...', 'geodir-booking' ); ?></div></div>
				</div>
			</div>
		</div>
	</div>
</div>
