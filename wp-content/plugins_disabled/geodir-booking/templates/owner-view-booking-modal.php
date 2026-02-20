<?php

/**
 * This template renders all bookings modals for a given owner.
 *
 * You can overide this template by copying it to your-theme-folder/geodir-booking/owner-booking-modals.php
 *
 * @version 2.0.16
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

global $aui_bs5;
?>
<div class="bsui" id="geodir-all-owner-bookings-modals">
	<div class="modal fade" id="geodir-all-owner-view-bookings-modal" tabindex="-1" aria-hidden="true">
		<div class="modal-dialog modal-dialog-centered">
			<div class="modal-content">
				<div class="modal-header">
					<!-- Listing details -->
					<a v-if="listing.url" :href="listing.url" class="d-flex align-items-center text-dark h5 modal-title" target="_blank">
						<div class="me-2 mr-2">
							<img v-if="listing.image" :src="listing.image" :alt="listing.title" width="32" height="32" class="img-thumbnail">
							<i v-if="!listing.image" class="fa-solid fa-image fa-2xl text-muted"></i>
						</div>
						<div class="flex-fill overflow-hidden"><?php esc_html_e( 'Booking Details', 'geodir-booking' ); ?></div>
					</a>
					<h5 v-if="!listing.url" class="modal-title"><?php esc_html_e( 'Booking Details', 'geodir-booking' ); ?></h5>

					<?php if ( $aui_bs5 ) { ?>
						<button type="button" class="btn-close" data-bs-dismiss="modal" @click.prevent="booking=null" aria-label="<?php esc_attr_e( 'Close', 'geodir-booking' ); ?>"></button>
					<?php } else { ?>
						<button type="button" class="close" data-dismiss="modal" @click.prevent="booking=null" aria-label="<?php esc_attr_e( 'Close', 'geodir-booking' ); ?>">&times;</button>
					<?php } ?>
				</div>
				<div class="modal-body position-relative" style="min-height: 100px;" id="geodir-view-owner-booking-modal-body">

					<!-- View booking details -->
					<div class="geodir-single-booking-details-wrapper" v-if="booking">

						<!-- Customer details -->
						<div class="mb-4 geodir-booking-customer-details d-flex justify-content-between align-items-center">
							<div>
								<h3 class="h6 mb-2">{{ booking.name }}</h3>
								<small class="form-text d-block text-muted">{{ booking.email }}</small>
								<a class="small form-text d-block" :href="booking.listing_url" target="_blank">{{ booking.listing_title }}</a>
							</div>
							<span v-if="booking.avatar" v-html="booking.avatar"></span>
						</div>

						<!-- Message / Call -->
						<div class="row mb-4 geodir-booking-message-call" v-if="booking.email">
							<div class="col<?php echo ( $aui_bs5 ? '-sm-4' : '' ); ?> mb-1">
								<a :href="'mailto:' + booking.email" class="btn btn-outline-dark btn-sm <?php echo ( $aui_bs5 ? 'd-block' : 'btn-block' ); ?>"><?php esc_html_e( 'Email', 'geodir-booking' ); ?></a>
							</div>
							<div class="col<?php echo ( $aui_bs5 ? '-sm-4' : '' ); ?> mb-1">
								<a :href="'sms:' + booking.phone" class="btn btn-outline-dark btn-sm <?php echo ( $aui_bs5 ? 'd-block' : 'btn-block' ); ?>"><?php esc_html_e( 'SMS', 'geodir-booking' ); ?></a>
							</div>
							<div class="col<?php echo ( $aui_bs5 ? '-sm-4' : '' ); ?> mb-1">
								<a :href="'tel:' + booking.phone" class="btn btn-outline-dark btn-sm <?php echo ( $aui_bs5 ? 'd-block' : 'btn-block' ); ?>"><?php esc_html_e( 'Call', 'geodir-booking' ); ?></a>
							</div>
							<div class="col-12 text-center form-text d-block text-muted small">
								<?php
									printf(
										esc_html( 'Phone: %s', 'geodir-booking' ),
										'{{booking.phone}}'
									);
									?>
							</div>
						</div>

						<!-- Booking details -->
						<div class="mb-4 geodir-booking-details">
							<ul class="list-group">
								<li class="list-group-item d-flex flex-wrap justify-content-between align-items-center" v-for="(detail, key) in booking.details" :class="'geodir-booking-detail-' + key" :key="key">
									<strong>{{ detail.label }}</strong>
									<span class="text-muted" v-html="detail.value"></span>
								</li>
							</ul>
						</div> 

						<!-- Amount breakdown -->
						<div class="mb-4 geodir-booking-amount-breakdown" v-if="booking.date_amounts_fmt && booking.date_amounts_fmt.length">
							<h3 class="h6"><?php esc_html_e( 'Amount breakdown', 'geodir-booking' ); ?></h3>
							<ul class="list-group">
								<li class="list-group-item d-flex flex-wrap justify-content-between align-items-center" v-for="date_amount in booking.date_amounts_fmt">
									<strong>{{ date_amount.date }}</strong>
									<span class="text-muted">{{ formatAmount( date_amount.amount ) }}</span>
								</li>
							</ul>
						</div>

						<!-- Booking action -->
						<div class="mb-4 geodir-booking-set-status" v-if="booking.old_status == 'pending_confirmation'">
							<label for="geodir-booking-set-status" class="form-label"><?php esc_html_e( 'Booking Action', 'geodir-booking' ); ?></label>
							<select class="form-control <?php echo ( $aui_bs5 ? 'form-select form-select-sm' : 'custom-select custom-select-sm' ); ?>" id="geodir-booking-set-status" v-model="booking.status">
								<option value="pending_confirmation"><?php esc_html_e( 'Select Action', 'geodir-booking' ); ?></option>
								<option value="confirmed"><?php esc_html_e( 'Approve', 'geodir-booking' ); ?></option>
								<option value="rejected"><?php esc_html_e( 'Reject', 'geodir-booking' ); ?></option>
							</select>
							<small class="form-text d-block text-muted"><?php esc_html_e( 'You will not be able to undo this change.', 'geodir-booking' ); ?></small>
						</div>

						<!-- Booking notes -->
						<div class="mb-4 geodir-booking-private-note">
							<label for="geodir-booking-notes" class="form-label"><?php esc_html_e( 'Private Note', 'geodir-booking' ); ?></label>
							<textarea class="form-control" id="geodir-booking-notes" rows="3" v-model="booking.private_note"></textarea>
							<small class="form-text d-block text-muted"><?php esc_html_e( 'This note will not be visible to the client.', 'geodir-booking' ); ?></small>
						</div>

						<!-- Save booking -->
						<div class="d-flex flex-wrap justify-content-between geodir-booking-save">
							<button type="button" class="btn btn-sm btn-primary" @click="saveBooking( booking )">
								<span class="spinner-border spinner-border-sm me-1 mr-1" role="status" v-if="booking.saving" aria-hidden="true"></span>
								<span class="me-1 mr-1" v-if="booking.saving"><?php esc_html_e( 'Saving...', 'geodir-booking' ); ?></span>
								<span v-if="!booking.saving"><?php esc_html_e( 'Save', 'geodir-booking' ); ?></span>
							</button>
							<button type="button" class="btn btn-sm btn-danger" @click="cancelBooking( booking )" v-if="'confirmed' === booking.status">
								<?php esc_html_e( 'Cancel Booking', 'geodir-booking' ); ?>
							</button>
							<button class="btn btn-sm btn-secondary text-white" href="#" <?php echo ( $aui_bs5 ) ? 'data-bs-dismiss="modal"' : ' data-dismiss="modal"'; ?> @click.prevent="booking=null" >
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

	<div class="modal fade" id="geodir-all-owner-edit-bookings-modal" tabindex="-1" aria-hidden="true">
		<div class="modal-dialog modal-dialog-centered">
			<div class="modal-content" v-if="booking">
				<div class="modal-header">
					<h5 class="modal-title"><?php esc_html_e( 'Edit Booking', 'geodir-booking' ); ?></h5>

					<?php if ( $aui_bs5 ) { ?>
						<button type="button" class="btn-close" data-bs-dismiss="modal" @click.prevent="booking=null" aria-label="<?php esc_attr_e( 'Close', 'geodir-booking' ); ?>"></button>
					<?php } else { ?>
						<button type="button" class="close" data-dismiss="modal" @click.prevent="booking=null" aria-label="<?php esc_attr_e( 'Close', 'geodir-booking' ); ?>">&times;</button>
					<?php } ?>
				</div>

				<div class="modal-body">
					<!-- Guest Info -->
					<?php
					aui()->input(
						array(
							'type'             => 'text',
							'id'               => 'geodir-booking-customer-name',
							'name'             => 'geodir_booking[name]',
							'label'            => __( 'Customer Name', 'geodir-booking' ),
							'label_class'      => ( $aui_bs5 ? 'fw-bold' : 'font-weight-bold' ),
							'label_type'       => 'top',
							'size'             => 'sm',
							'required'         => true,
							'extra_attributes' => array(
								'v-model' => 'booking.name',
							),
						),
						true
					);

					aui()->input(
						array(
							'type'             => 'text',
							'id'               => 'geodir-booking-customer-email',
							'name'             => 'geodir_booking[email]',
							'label'            => __( 'Customer Email', 'geodir-booking' ),
							'label_class'      => ( $aui_bs5 ? 'fw-bold' : 'font-weight-bold' ),
							'label_type'       => 'top',
							'size'             => 'sm',
							'required'         => true,
							'extra_attributes' => array(
								'v-model' => 'booking.email',
							),
						),
						true
					);

					aui()->input(
						array(
							'type'             => 'text',
							'id'               => 'geodir-booking-customer-phone',
							'name'             => 'geodir_booking[phone]',
							'label'            => __( 'Customer Phone', 'geodir-booking' ),
							'label_class'      => ( $aui_bs5 ? 'fw-bold' : 'font-weight-bold' ),
							'label_type'       => 'top',
							'size'             => 'sm',
							'required'         => true,
							'extra_attributes' => array(
								'v-model' => 'booking.phone',
							),
						),
						true
					);
					?>

					<!-- Booking notes -->
					<div class="mb-4 geodir-booking-private-note">
						<label for="geodir-booking-notes" class="form-label <?php echo ( $aui_bs5 ? 'fw-bold' : 'font-weight-bold' ); ?>"><?php esc_html_e( 'Private Note', 'geodir-booking' ); ?></label>
						<textarea class="form-control" name="geodir_booking[private_note]" id="geodir-booking-notes" rows="3" v-model="booking.private_note"></textarea>
						<small class="form-text d-block text-muted"><?php esc_html_e( 'This note will not be visible to the client.', 'geodir-booking' ); ?></small>
					</div>

					<?php
					aui()->select(
						array(
							'type'             => 'select',
							'id'               => 'geodir-booking-status',
							'name'             => 'geodir_booking[status]',
							'label'            => __( 'Status', 'geodir-booking' ),
							'label_type'       => 'top',
							'label_class'      => ( $aui_bs5 ? 'fw-bold' : 'font-weight-bold' ),
							'size'             => 'sm',
							'options'          => $booking_statuses,
							'class'            => 'w-100',
							'extra_attributes' => array(
								'v-model' => 'booking.status',
							),
						),
						true
					);
					?>

					<!-- Errors -->
					<div class="geodir-bookings-error alert alert-danger mt-2" role="alert" v-if="error">{{error}}</div>

					<div class="w-100 h-100 position-absolute bg-light d-flex justify-content-center align-items-center getpaid-block-ui" style="top: 0; left: 0; opacity: 0.7; cursor: progress;" v-if="is_loading"><div class="spinner-border" role="status"><span class="sr-only visually-hidden"><?php esc_html_e( 'Loading...', 'geodir-booking' ); ?></div></div>
				</div>

				<div class="modal-footer d-flex flex-wrap justify-content-between">
					<button type="button" class="btn btn-sm btn-primary" @click="saveBooking( booking )">
						<span class="spinner-border spinner-border-sm me-1 mr-1" role="status" v-if="booking.saving" aria-hidden="true"></span>
						<span class="me-1 mr-1" v-if="booking.saving"><?php esc_html_e( 'Saving...', 'geodir-booking' ); ?></span>
						<span v-if="!booking.saving"><?php esc_html_e( 'Save', 'geodir-booking' ); ?></span>
					</button>

					<button class="btn btn-sm btn-secondary text-white" href="#" <?php echo ( $aui_bs5 ) ? 'data-bs-dismiss="modal"' : ' data-dismiss="modal"'; ?> @click.prevent="booking=null">
						<?php esc_html_e( 'Go Back', 'geodir-booking' ); ?>
					</button>
				</div>
			</div>
		</div>
	</div>
</div>
