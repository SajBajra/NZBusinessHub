<?php
/**
 * Render Customer View Booking Modal
 *
 * This template can be overridden by copying it to yourtheme/geodir-booking/customer-view-booking-modal.php.
 *
 * HOWEVER, on occasion GeoDirectory will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see        https://docs.wpgeodirectory.com/article/346-customizing-templates/
 * @package    GeoDirectory\Templates
 * @version    2.1.1
 *
 * Variables.
 *
 * @var array $bookings The bookings object.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $aui_bs5;
?>
<div class="bsui geodir-all-customer-bookings-modal">
	<div class="modal fade" id="geodir-all-customer-bookings-modal" tabindex="-1" aria-hidden="true">
		<div class="modal-dialog modal-dialog-centered">
			<div class="modal-content">
				<div class="modal-header">

					<!-- Listing details -->
					<a v-if="listing.url" :href="listing.url" class="d-flex align-items-center text-dark h5 modal-title" target="_blank">
						<div class="me-2 mr-2">
							<img v-if="listing.image" :src="listing.image" :alt="listing.title" width="32" height="32" class="img-thumbnail">
							<i v-if="!listing.image" class="fa-solid fa-image fa-2xl text-muted"></i>
						</div>
						<div class="flex-fill overflow-hidden">{{ listing.title }}</div>
					</a>

					<h5 v-if="!listing.url" class="modal-title"><?php esc_html_e( 'Booking Details', 'geodir-booking' ); ?></h5>

					<?php if ( $aui_bs5 ) { ?>
						<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?php esc_attr_e( 'Close', 'geodir-booking' ); ?>"></button>
					<?php } else { ?>
						<button type="button" class="close" data-dismiss="modal" aria-label="<?php esc_attr_e( 'Close', 'geodir-booking' ); ?>">&times;</button>
					<?php } ?>
				</div>
				<div class="modal-body position-relative" style="min-height: 100px;" id="geodir-view-customer-booking-modal-body">

					<!-- View booking details -->
					<div class="geodir-single-booking-details-wrapper">

						<!-- Booking details -->
						<div class="mb-4 geodir-booking-details">
							<ul class="list-group m-0">
								<li class="list-group-item d-flex flex-wrap justify-content-between align-items-center" v-for="(detail, key) in booking" :class="'geodir-booking-detail-' + key" :key="key">
									<strong v-html="detail.label"></strong>
									<span class="text-muted" v-html="detail.value"></span>
								</li>
							</ul>
						</div>

						<div class="d-flex flex-wrap justify-content-between">
							<button class="btn btn-sm btn-primary" data-dismiss="modal" data-bs-dismiss="modal">
								<?php esc_html_e( 'Go Back', 'geodir-booking' ); ?>
							</button>&nbsp;
							<button type="button" class="btn btn-sm btn-danger" @click="cancelBooking()" v-if="cancelDetails.can_cancel">
								<span class="spinner-border spinner-border-sm mr-1 me-1" role="status" v-if="is_loading" aria-hidden="true"></span>
								<span class="mr-1 me-1" v-if="is_loading"><?php esc_html_e( 'Cancelling...', 'geodir-booking' ); ?></span>
								<span v-if="!is_loading"><?php esc_html_e( 'Cancel Booking', 'geodir-booking' ); ?></span>
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