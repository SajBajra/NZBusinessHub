<?php
defined( 'ABSPATH' ) || exit; 

global $aui_bs5;
?>
<div class="bsui geodir-book-now-modal">
	<div class="modal fade" id="geodir-book-now-modal" tabindex="-1" aria-hidden="true">
		<div class="modal-dialog modal-dialog-centered">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title"><?php esc_html_e( 'Book Now', 'geodir-booking' ); ?></h5>
					<?php if ( $aui_bs5 ) { ?>
						<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?php esc_attr_e( 'Close', 'geodir-booking' ); ?>"></button>
					<?php } else { ?>
						<button type="button" class="close" data-dismiss="modal" aria-label="<?php esc_attr_e( 'Close', 'geodir-booking' ); ?>">&times;</button>
					<?php } ?>
				</div>
				<div class="modal-body" id="geodir-book-now-form-modal-body">

					<!-- Success message -->
					<div class="alert alert-info mb-3" role="alert" v-if="success">
						<span v-if="confirmBooking">
							<?php esc_html_e( 'Confirm your booking details to continue.', 'geodir-booking' ); ?>
						</span>
						<span v-else>
							<span v-if="bookingDetails && bookingDetails.payment_url"><?php esc_html_e( 'Confirm and pay.', 'geodir-booking' ); ?></span>
							<span v-else><?php esc_html_e( 'Your booking has been successfully submitted. We will contact you shortly.', 'geodir-booking' ); ?></span>
						</span>
					</div>

					<!-- Display the booking form. -->
					<form v-show="!bookingDetails" id="geodir-book-now-form" class="geodir-book-now-form position-relative" @submit.prevent="saveBooking" action="" method="post" novalidate>

						<div class="<?php echo ( $aui_bs5 ? 'mb-3' : 'form-group' ); ?>">
							<label for="geodir-book-now-name"><?php esc_html_e( 'Name', 'geodir-booking' ); ?></label>
							<input v-model="name" type="text" class="form-control" :class="fieldClass(isNameInvalid, isNameValid)" id="geodir-book-now-name" name="geodir_book_now_name" placeholder="<?php esc_attr_e( 'Name', 'geodir-booking' ); ?>" :aria-describedby="isFieldInvalid(isNameInvalid) ? 'geodir-book-now-name-validation' : ''" >
							<div class="invalid-feedback" v-if="isFieldInvalid(isNameInvalid)"><?php esc_html_e( 'Please enter your full name.', 'geodir-booking' ); ?></div>
						</div>
						<div class="<?php echo ( $aui_bs5 ? 'mb-3' : 'form-group' ); ?>">
							<label for="geodir-book-now-email"><?php esc_html_e( 'Email', 'geodir-booking' ); ?></label>
							<input v-model="email" type="email" class="form-control" :class="fieldClass(isEmailInvalid, isEmailValid)" id="geodir-book-now-email" name="geodir_book_now_email" placeholder="<?php esc_attr_e( 'Email', 'geodir-booking' ); ?>" :aria-describedby="isFieldInvalid(isEmailInvalid) ? 'geodir-book-now-email-validation' : ''" >
							<div class="invalid-feedback" v-if="isFieldInvalid(isEmailInvalid)"><?php esc_html_e( 'Please enter a valid email address.', 'geodir-booking' ); ?></div>
						</div>
						<div class="<?php echo ( $aui_bs5 ? 'mb-3' : 'form-group' ); ?>">
							<label for="geodir-book-now-phone"><?php esc_html_e( 'Phone', 'geodir-booking' ); ?></label>
							<input v-model="phone" type="text" class="form-control" :class="fieldClass(isPhoneInvalid, isPhoneValid)" id="geodir-book-now-phone" name="geodir_book_now_phone" placeholder="<?php esc_attr_e( 'Phone', 'geodir-booking' ); ?>" :aria-describedby="isFieldInvalid(isPhoneInvalid) ? 'geodir-book-now-phone-validation' : ''" >
							<div class="invalid-feedback" v-if="isFieldInvalid(isPhoneInvalid)"><?php esc_html_e( 'Please enter a valid phone number.', 'geodir-booking' ); ?></div>
						</div>
						<div class="<?php echo ( $aui_bs5 ? 'mb-3' : 'form-group' ); ?>">
							<label for="geodir-book-now-check-in"><?php esc_html_e( 'Check-in and Check-out dates', 'geodir-booking' ); ?></label>
							<input type="text" class="form-control" :class="fieldClass(isDatesInvalid, isDatesValid)" id="geodir-book-now-date" name="geodir_book_now_check_in" placeholder="<?php esc_attr_e( 'Dates', 'geodir-booking' ); ?>" :aria-describedby="isFieldInvalid(isDatesInvalid) ? 'geodir-book-now-date-validation' : ''">
							<div class="invalid-feedback" v-if="isFieldInvalid(isDatesInvalid)">{{date_error}}</div>
						</div>

						<button type="submit" class="btn btn-primary <?php echo ( $aui_bs5 ? 'd-block' : 'btn-block' ); ?>">
							<span class="spinner-border spinner-border-sm" role="status" v-if="isSubmitting" aria-hidden="true"></span>&nbsp;
							<span v-if="isSubmitting"><?php esc_html_e( 'Processing...', 'geodir-booking' ); ?></span>
							<span v-if="!isSubmitting"><?php esc_html_e( 'Reserve', 'geodir-booking' ); ?></span>
						</button>

						<div class="alert alert-danger mt-2" role="alert" v-if="error">{{error}}</div>

						<div class="w-100 h-100 position-absolute bg-light d-flex justify-content-center align-items-center getpaid-block-ui" style="top: 0; left: 0; opacity: 0.7; cursor: progress;" v-if="isSubmitting"><div class="spinner-border" role="status"><span class="sr-only visually-hidden"><?php esc_html_e( 'Processing...', 'geodir-booking' ); ?></div></div>
					</form>

					<!-- Booking details -->
					<div class="geodir-booking-details" v-if="bookingDetails">
						<ul class="list-group">
							<li class="list-group-item d-flex justify-content-between align-items-center" v-for="(detail, key) in bookingDetails.customer_details" :class="'geodir-booking-detail-' + key" :key="key">
								<strong>{{ detail.label }}</strong>
								<span class="small" v-html="detail.value"></span>
							</li>
						</ul>

						<button type="submit" class="mt-4 btn btn-primary btn-block has-background <?php echo ( $aui_bs5 ? 'd-block' : 'btn-block' ); ?>" v-if="confirmBooking" @click="confirmBookingDetails">
							<span class="spinner-border spinner-border-sm" role="status" v-if="isSubmitting" aria-hidden="true"></span>&nbsp;
							<span v-if="isSubmitting"><?php esc_html_e( 'Confirming...', 'geodir-booking' ); ?></span>
							<span v-if="!isSubmitting"><?php esc_html_e( 'Confirm Booking Details', 'geodir-booking' ); ?></span>
						</button>

						<button class="mt-2 btn btn-secondary has-background <?php echo ( $aui_bs5 ? 'd-block' : 'btn-block' ); ?>" v-if="confirmBooking" @click="showEditForm"><?php esc_html_e( 'Edit Booking Details', 'geodir-booking' ); ?></button>

						<a :href="bookingDetails.payment_url" class="mt-4 btn btn-primary <?php echo ( $aui_bs5 ? 'd-block' : 'btn-block' ); ?>" v-if="!confirmBooking && bookingDetails.payment_url"><?php esc_html_e( 'Confirm and pay', 'geodir-booking' ); ?></a>

						<div class="alert alert-danger mt-2" role="alert" v-if="error">{{error}}</div>
					</div>

				</div>
			</div>
		</div>
	</div>
</div>
