<?php

/**
 * This template renders owner new booking modal
 *
 * You can overide this template by copying it to your-theme-folder/geodir-booking/owner-new-booking-modal.php
 *
 * @version 2.0.16
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

global $aui_bs5;
?>
<div class="bsui">
	<div class="modal fade" id="geodir-all-owner-add-bookings-modal" tabindex="-1" aria-hidden="true">
		<div class="modal-dialog modal-dialog-centered">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title"><?php esc_html_e( 'Add New Booking', 'geodir-booking' ); ?></h5>

					<?php if ( $aui_bs5 ) { ?>
						<button type="button" class="btn-close" data-bs-dismiss="modal" @click.prevent="resetForm" aria-label="<?php esc_attr_e( 'Close', 'geodir-booking' ); ?>"></button>
					<?php } else { ?>
						<button type="button" class="close" data-dismiss="modal" @click.prevent="resetForm" aria-label="<?php esc_attr_e( 'Close', 'geodir-booking' ); ?>">&times;</button>
					<?php } ?>
				</div>

				<div class="modal-body">
					<div class="row" v-if="steps.search">
						<?php
						aui()->input(
							array(
								'type'             => 'datepicker',
								'id'               => 'geodir-booking-start_date',
								'name'             => 'gdbc_check_in',
								'label'            => __( 'Check-in *', 'geodir-booking' ),
								'label_type'       => 'top',
								'label_class'      => ( $aui_bs5 ? 'fw-semibold' : 'font-weight-bold' ),
								'size'             => 'sm',
								'placeholder'      => __( 'Check-in Date', 'geodir-booking' ),
								'wrap_class'       => 'col-md-6',
								'required'         => true,
								'extra_attributes' => array(
									'v-model' => 'formData.checkin_date',
								),
							),
							true
						);

						aui()->input(
							array(
								'type'             => 'datepicker',
								'id'               => 'geodir-booking-end_date',
								'name'             => 'gdbc_check_out',
								'label'            => __( 'Check-out *', 'geodir-booking' ),
								'label_type'       => 'top',
								'label_class'      => ( $aui_bs5 ? 'fw-semibold' : 'font-weight-bold' ),
								'size'             => 'sm',
								'placeholder'      => __( 'Check-out Date', 'geodir-booking' ),
								'wrap_class'       => 'col-md-6',
								'required'         => true,
								'extra_attributes' => array(
									'v-model' => 'formData.checkout_date',
								),
							),
							true
						);

						aui()->select(
							array(
								'type'             => 'select',
								'id'               => 'geodir-booking-listing-id',
								'name'             => 'gdbc_listing_id',
								'label'            => __( 'Listing', 'geodir-booking' ),
								'label_type'       => 'top',
								'label_class'      => ( $aui_bs5 ? 'fw-semibold' : 'font-weight-bold' ),
								'size'             => 'sm',
								'options'          => $listing_options,
								'class'            => 'w-100',
								'wrap_class'       => 'col-md-12',
								'extra_attributes' => array(
									'v-model' => 'formData.search_listing_id',
								),
							),
							true
						);

						aui()->select(
							array(
								'type'             => 'select',
								'id'               => 'geodir-booking-adults',
								'name'             => 'gdbc_adults',
								'label'            => __( 'Adults', 'geodir-booking' ),
								'label_type'       => 'hortopizontal',
								'label_class'      => ( $aui_bs5 ? 'fw-semibold' : 'font-weight-bold' ),
								'size'             => 'sm',
								'options'          => $adults_options,
								'class'            => 'w-100',
								'wrap_class'       => 'col-md-6',
								'extra_attributes' => array(
									'v-model' => 'formData.adults',
								),
							),
							true
						);

						aui()->select(
							array(
								'type'             => 'select',
								'id'               => 'geodir-booking-children',
								'name'             => 'gdbc_children',
								'label'            => __( 'Children', 'geodir-booking' ),
								'label_type'       => 'top',
								'label_class'      => ( $aui_bs5 ? 'fw-semibold' : 'font-weight-bold' ),
								'size'             => 'sm',
								'options'          => $children_options,
								'class'            => 'w-100',
								'wrap_class'       => 'col-md-6',
								'extra_attributes' => array(
									'v-model' => 'formData.children',
								),
							),
							true
						);
						?>
					</div>

					<div v-if="numberOfListings && steps.search">
						<hr class="mt-2 mb-3" v-if="!steps.booking"></hr>

						<span class="text-dark d-block mb-2" v-if="listings_found" v-html="listings_found"></span>

						<div class="form-group mb-0" v-if="listings && !hasSearchedListingID && !steps.booking">
							<label class="form-label <?php echo ( $aui_bs5 ? 'fw-semibold' : 'font-weight-bold' ); ?>"><?php esc_html_e( 'Available Listings', 'geodir-booking' ); ?></label>
							<select class="<?php echo ( $aui_bs5 ? 'form-select' : 'custom-select mb-3' ); ?> select-2 w-100" v-model="formData.listing_id">
								<option value="-1" disabled selected><?php esc_html_e( 'Select Listing', 'geodir-booking' ); ?></option>
								<option v-for="(listing, listing_id) in listings" :key="listing_id" :value="listing_id">
									{{ listing.title }}
								</option>
							</select>
						</div>

						<div class="form-group mb-0" v-if="selectedListing && hasMultipleRooms">
							<label class="form-label <?php echo ( $aui_bs5 ? 'fw-semibold' : 'font-weight-bold' ); ?>"><?php esc_html_e( 'Available Rooms', 'geodir-booking' ); ?></label>
							<select class="<?php echo ( $aui_bs5 ? 'form-select' : 'custom-select' ); ?> select-2 w-100" v-model="formData.room_id">
								<option value="-1" disabled selected><?php esc_html_e( 'Select Room', 'geodir-booking' ); ?></option>
								<option v-for="room in selectedListing.rooms" :key="room.id" :value="room.id">
									{{ room.title }}
								</option>
							</select>
						</div>
					</div>

					<div v-if="steps.booking">
						<div class="mb-4 geodir-booking-details">
							<ul class="list-group m-0">
								<li class="list-group-item d-flex flex-wrap justify-content-between align-items-center">
									<strong><?php esc_html_e( 'Listing', 'geodir-booking' ); ?></strong>
									<span>
										<a v-if="selectedListing" class="text-primary fw-bold mb-2 d-block" target="_blank" v-href="selectedListing.url">
											<span v-if="selectedRoom">{{ selectedRoom.title }}</span>
											<span v-if="!selectedRoom">{{ selectedListing.title }}</span>
										</a>
									</span>
								</li>
								<li class="list-group-item d-flex flex-wrap justify-content-between align-items-center">
									<strong><?php esc_html_e( 'Check-in Date', 'geodir-booking' ); ?></strong>
									<span class="text-muted" v-html="formattedCheckinDate"></span>
								</li>
								<li class="list-group-item d-flex flex-wrap justify-content-between align-items-center">
									<strong><?php esc_html_e( 'Check-out Date', 'geodir-booking' ); ?></strong>
									<span class="text-muted" v-html="formattedCheckoutDate"></span>
								</li>
								<li v-if="totalGuests" class="list-group-item d-flex flex-wrap justify-content-between align-items-center">
									<strong><?php esc_html_e( 'Guests', 'geodir-booking' ); ?></strong>
									<span class="text-muted">{{ guestsSummary }}</span>
								</li>
							</ul>
						</div>

						<!-- Guest Info -->
						<?php
						aui()->input(
							array(
								'type'             => 'text',
								'label'            => __( 'Customer Name', 'geodir-booking' ),
								'label_class'      => ( $aui_bs5 ? 'fw-bold' : 'font-weight-bold' ),
								'label_type'       => 'top',
								'size'             => 'sm',
								'required'         => true,
								'extra_attributes' => array(
									'v-model' => 'formData.customer_name',
								),
							),
							true
						);

						aui()->input(
							array(
								'type'             => 'text',
								'label'            => __( 'Customer Email', 'geodir-booking' ),
								'label_class'      => ( $aui_bs5 ? 'fw-bold' : 'font-weight-bold' ),
								'label_type'       => 'top',
								'size'             => 'sm',
								'required'         => true,
								'extra_attributes' => array(
									'v-model' => 'formData.customer_email',
								),
							),
							true
						);

						aui()->input(
							array(
								'type'             => 'text',
								'label'            => __( 'Customer Phone', 'geodir-booking' ),
								'label_class'      => ( $aui_bs5 ? 'fw-bold' : 'font-weight-bold' ),
								'label_type'       => 'top',
								'size'             => 'sm',
								'required'         => true,
								'extra_attributes' => array(
									'v-model' => 'formData.customer_phone',
								),
							),
							true
						);
						?>

						<!-- Booking notes -->
						<div class="mb-4">
							<label class="form-label <?php echo ( $aui_bs5 ? 'fw-bold' : 'font-weight-bold' ); ?>"><?php esc_html_e( 'Private Note', 'geodir-booking' ); ?></label>
							<textarea class="form-control" rows="3" v-model="formData.private_note"></textarea>
							<small class="form-text d-block text-muted"><?php esc_html_e( 'This note will not be visible to the client.', 'geodir-booking' ); ?></small>
						</div>

						<?php
						aui()->select(
							array(
								'type'             => 'select',
								'label'            => __( 'Status', 'geodir-booking' ),
								'label_type'       => 'top',
								'label_class'      => ( $aui_bs5 ? 'fw-bold' : 'font-weight-bold' ),
								'size'             => 'sm',
								'options'          => $booking_statuses,
								'class'            => 'w-100',
								'extra_attributes' => array(
									'v-model' => 'formData.booking_status',
								),
							),
							true
						);
						?>
					</div>

					<!-- Errors -->
					<div class="geodir-bookings-error alert alert-danger mb-0 mt-2" role="alert" v-if="error">{{error}}</div>

					<div class="w-100 h-100 position-absolute bg-light d-flex justify-content-center align-items-center getpaid-block-ui" style="top: 0; left: 0; opacity: 0.7; cursor: progress;" v-if="is_loading"><div class="spinner-border" role="status"><span class="sr-only visually-hidden"><?php esc_html_e( 'Loading...', 'geodir-booking' ); ?></div></div>
				</div>

				<div class="modal-footer">
					<button type="button" class="btn btn-primary w-100" @click="searchListings" v-if="!selectedListing">
						<span class="spinner-border spinner-border-sm" role="status" v-if="steps.searching" aria-hidden="true"></span>&nbsp;
						<span v-if="steps.searching"><?php esc_html_e( 'Searching...', 'geodir-booking' ); ?></span>
						<span v-if="!steps.searching"><?php esc_html_e( 'Search Available Listings', 'geodir-booking' ); ?></span>
					</button>

					<button type="button" class="btn btn-primary w-100" @click="reserveBooking" v-if="selectedListing && !steps.booking">
						<?php esc_html_e( 'Reserve', 'geodir-booking' ); ?>
					</button>

					<button type="button" class="btn btn-primary w-100" @click="addBooking" v-if="selectedListing && steps.booking">
						<span class="spinner-border spinner-border-sm" role="status" v-if="steps.processing" aria-hidden="true"></span>&nbsp;
						<span v-if="steps.processing"><?php esc_html_e( 'Booking...', 'geodir-booking' ); ?></span>
						<span v-if="!steps.processing"><?php esc_html_e( 'Book Now', 'geodir-booking' ); ?></span>
					</button>
				</div>
			</div>
		</div>
	</div>                                   
</div>
