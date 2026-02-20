<?php
defined( 'ABSPATH' ) || exit;

global $aui_bs5;
?>
<div class="bsui geodir-view-customer-bookings-modal">
	<div class="modal fade" id="geodir-view-customer-bookings-modal" tabindex="-1" aria-hidden="true">
		<div class="modal-dialog modal-dialog-centered" :class="current_booking ? '' : 'modal-xl'">
			<div class="modal-content">
				<div class="modal-header">

					<!-- Listing details -->
					<a v-if="listing.url" :href="listing.url" class="d-flex align-items-center text-dark h5 modal-title" target="_blank">
						<div class="<?php echo ( $aui_bs5 ? 'me-2' : 'mr-2' ); ?>">
							<img v-if="listing.image" :src="listing.image" :alt="listing.title" width="32" height="32">
							<i v-else class="fa-solid fa-image fa-2xl text-muted"></i>
						</div>
						<div class="flex-fill overflow-hidden">{{ listing.title }}</div>
					</a>

					<h5 v-else class="modal-title"><?php esc_html_e( 'View Bookings', 'geodir-booking' ); ?></h5>

					<?php if ( $aui_bs5 ) { ?>
						<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?php esc_attr_e( 'Close', 'geodir-booking' ); ?>"></button>
					<?php } else { ?>
						<button type="button" class="close" data-dismiss="modal" aria-label="<?php esc_attr_e( 'Close', 'geodir-booking' ); ?>">&times;</button>
					<?php } ?>
				</div>
				<div class="modal-body position-relative" style="min-height: 100px;" id="geodir-view-customer-bookings-modal-body">

					<!-- Bookings wrapper ... -->
					<div class="geodir-customer-listings-table" v-if="! is_loading && ! current_booking">

						<!-- Check if there are records -->
						<div class="geodir-bookings-no-records alert alert-danger mt-2" role="alert" v-if="!hasBookings && !error">
							<?php esc_html_e( 'No bookings found.', 'geodir-booking' ); ?>
						</div>

						<!-- List bookings -->
						<div class="geodir-bookings-list table-responsive" v-if="hasBookings">
							<table class="table table-hover">

								<thead>
									<tr>
										<th v-for="(label, key) in table_cols" :class="'geodir-booking-col-' + key">{{ label }}</th>
										<th>&nbsp;</th>
									</tr>
								</thead>

								<tbody>
									<tr v-for="booking in bookings">
										<td v-for="(label, key) in table_cols" :class="'geodir-booking-col-' + key" :title="label">
											<span v-if="booking[key]" v-html="booking[key].value"></span>
											<span v-else>&mdash;</span>
										</td>
										<td class="align-middle">
											<a class="btn btn-outline-primary btn-sm" href="#" @click.prevent="current_booking=booking">
												<?php esc_html_e( 'View Details', 'geodir-booking' ); ?>
											</a>
											<span v-if="booking.pay" class="d-inline-block <?php echo ( $aui_bs5 ? 'ms-1' : 'ml-1' ); ?>" v-html="booking.pay.value"></span>
										</td>
									</tr>
								</tbody>
							</table>
						</div>
					</div>

					<!-- View booking details -->
					<div class="geodir-single-booking-details-wrapper" v-if="current_booking">

						<!-- Booking details -->
						<div class="mb-4 geodir-booking-details">
							<ul class="list-group">
								<li class="list-group-item d-flex flex-wrap justify-content-between align-items-center" v-for="(detail, key) in current_booking" :class="'geodir-booking-detail-' + key" :key="key">
									<strong v-if="'total_amount' == key" v-html="detail.label"></strong>
									<strong v-else>{{ detail.label }}</strong>
									<div class="text-align-right">
										<span class="text-muted" v-html="detail.value"></span>
										<small class="d-block form-text text-danger" v-if="'status' == key && detail.cancel && detail.cancel.can_cancel">
											<a href="#" @click.prevent="cancelBooking( detail.cancel )">
												<span v-if="is_cancelling" class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
												<span v-if="is_cancelling"><?php esc_html_e( 'Cancelling...', 'geodir-booking' ); ?></span>
												<span v-if="!is_cancelling"><?php esc_html_e( 'Cancel Booking', 'geodir-booking' ); ?></span>
											</a>
										</small>
									</div>
								</li>
							</ul>
						</div>

						<a class="btn btn-primary text-white <?php echo ( $aui_bs5 ? 'd-block' : 'btn-block' ); ?>" href="#" @click.prevent="current_booking=null">
							<?php esc_html_e( 'Go Back', 'geodir-booking' ); ?>
						</a>
					</div>

					<!-- Errors -->
					<div class="geodir-bookings-error alert alert-danger mt-2" role="alert" v-if="error">{{error}}</div>

					<div class="w-100 h-100 position-absolute bg-light d-flex justify-content-center align-items-center getpaid-block-ui" style="top: 0; left: 0; opacity: 0.7; cursor: progress;" v-if="is_loading"><div class="spinner-border" role="status"><span class="sr-only visually-hidden"><?php esc_html_e( 'Loading...', 'geodir-booking' ); ?></div></div>
				</div>
			</div>
		</div>
	</div>
</div>
