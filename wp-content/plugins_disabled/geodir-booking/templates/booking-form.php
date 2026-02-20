<?php
/**
 * This template displays a booking form.
 *
 * You can overide this template by copying it to your-theme-folder/geodir-booking/booking-form.php
 * @var WP_Post $listing
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

global $wp_locale, $aui_bs5;

$current_user = wp_get_current_user();

// if $listing is room use post_parent to get listing options.
$listing_id = ! empty( $listing->post_parent ) ? $listing->post_parent : $listing->ID;

$post_info = geodir_get_post_info( $listing_id );
if ( empty( $post_info ) ) {
	$post_info = geodir_get_post_info( $listing->ID );
}

$phone_number = empty( $current_user->ID ) ? '' : get_user_meta( $current_user->ID, '_wpinv_phone', true );
$js_data      = array(
	'random'            => wp_unique_id( 'booking_form_' . $listing->ID . '_' ),
	'listing_id'        => $listing->ID,
	'disabled_dates'    => GeoDir_Booking::get_disabled_dates( $listing->ID ),
	'daysi18n'          => array_values( $wp_locale->weekday ), // 0 === Sunday.
	'nonce'             => wp_create_nonce( 'gd_booking_process_booking' ),
	'name'              => empty( $current_user->display_name ) ? '' : $current_user->display_name,
	'hide_name'         => ! empty( $current_user->display_name ),
	'email'             => empty( $current_user->user_email ) ? '' : $current_user->user_email,
	'hide_email'        => ! empty( $current_user->user_email ),
	'phone'             => $phone_number,
	'hide_phone'        => ! empty( $phone_number ),
	'encrypted_email'   => empty( $current_user->user_email ) ? '' : geodir_booking_encrypt( $current_user->user_email ),
	'ruleset'           => new GeoDir_Booking_Ruleset( 0, $listing->ID ),
	'day_rules'         => wp_list_pluck( GeoDir_Booking::get_day_rules( $listing->ID ), 'nightly_price', 'rule_date' ),
	'minAdults'         => (int) '1',
	'minChildren'       => (int) '0',
	'minInfants'        => (int) '0',
	'minPets'           => (int) '0',
	'service_fee'       => (float) geodir_booking_get_option( 'service_fee' ),
	'status'            => 'pending_payment',
	'select_dates_text' => __( 'Please select your dates', 'geodir-booking' ),
);

if ( ! empty( $post_info->property_guests ) ) :
	$js_data['maxGuests'] = (int) $post_info->property_guests;
endif;
?>

<style>
    .gd-booking-flatpickr-calendar .flatpickr-days{
        overflow: initial;
    }

.flatpickr-day {
	position: relative;
}

.flatpickr-day .flatpickr-tooltip {
	position: absolute;
	bottom: 100%;
	background-color: #333;
	color: white;
	padding: 5px 10px;
	border-radius: 4px;
	line-height: 20px;
	font-size: 12px;
	white-space: nowrap;
	z-index: 100;
	pointer-events: none;
}

.flatpickr-day .flatpickr-tooltip::after {
	content: "";
	position: absolute;
	top: 100%;
	border-width: 5px;
	border-style: solid;
	border-color: #333 transparent transparent transparent;
}

.flatpickr-day .flatpickr-tooltip.left-tooltip {
	left: 0;
}

.flatpickr-day .flatpickr-tooltip.left-tooltip::after {
	left: 10px;
}

.flatpickr-day .flatpickr-tooltip.right-tooltip {
	right: 0;
}

.flatpickr-day .flatpickr-tooltip.right-tooltip::after {
	right: 10px;
}
</style>

<script id="gd-booking-form-<?php echo esc_attr( $listing->ID ); ?>" type="text/template">
<div class="geodir-booking-form-wrapper position-relative" data-listing_id="<?php echo esc_attr( $listing->ID ); ?>" id="geodir-booking-form-wrapper-<?php echo esc_attr( $js_data['random'] ); ?>" data-js_data="<?php echo esc_attr( wp_json_encode( $js_data ) ); ?>">

	<!-- Success message -->
    <div class="alert alert-info mb-3" role="alert" v-if="success && !bookingDetails.payment_url">
        <span v-if="confirmBooking">
            <?php esc_html_e( 'Confirm your booking details to continue.', 'geodir-booking' ); ?>
        </span>
            <span v-else-if="!bookingDetails.payment_url">
            <?php esc_html_e( 'Your booking has been successfully submitted. We will contact you shortly.', 'geodir-booking' ); ?>
        </span>
    </div>

    <a :href="bookingDetails.payment_url"
       class="d-block mt-4 mb-4 btn btn-primary <?php echo ( $aui_bs5 ? 'd-block' : 'btn-block' ); ?>"
       v-if="!confirmBooking && bookingDetails.payment_url">
        <?php esc_html_e( 'Confirm and pay', 'geodir-booking' ); ?>
    </a>

	<!-- Display the booking form. -->
	<form v-show="!bookingDetails" class="geodir-book-now-form position-relative" @submit.prevent="saveBooking" action="" method="post" novalidate>

		<div class="d-flex justify-content-between">
			<div v-if="ruleset.nightly_price >= <?php echo geodir_booking_night_min_price(); ?>" class="geodir-booking-nightly-fee mb-2">
				<span class="h4 <?php echo esc_attr( $aui_bs5 ? 'fw-bold' : 'font-weight-bold' ); ?>"><?php echo wp_kses_post( geodir_booking_price_placeholder( '{{ formatAmount(nightlyPrice) }}' ) ); ?>&nbsp;</span>
				<span class="lead">/&nbsp;<?php esc_html_e( 'night', 'geodir-booking' ); ?> <small v-if="isAvgPrice"><?php esc_html_e( '(avg)', 'geodir-booking' ); ?></small></span>
			</div>

			<small class="geodir-booking-reviews form-text text-muted">
				<?php if ( 0 < get_comments_number( $listing->ID ) ) : ?>
					<?php geodir_comments_number( $listing->ID ); ?>
				<?php endif; ?>
			</small>
		</div>

		<div class="<?php echo ( $aui_bs5 ? 'mb-3' : 'form-group' ); ?>" v-if="! hide_name">
			<label for="_booking_name" class="form-label"><?php esc_html_e( 'Name', 'geodir-booking' ); ?> <span class="text-danger">*</span></label>
			<input v-model="name" id="_booking_name" type="text" class="form-control" :class="fieldClass(isNameInvalid, isNameValid)" placeholder="<?php esc_attr_e( 'Full Name', 'geodir-booking' ); ?>">
			<div class="invalid-feedback" v-if="isFieldInvalid(isNameInvalid)"><?php esc_html_e( 'Please enter your full name.', 'geodir-booking' ); ?></div>
		</div>

		<div class="<?php echo ( $aui_bs5 ? 'mb-3' : 'form-group' ); ?>" v-if="! hide_email">
			<label for="_booking_email" class="form-label"><?php esc_html_e( 'Email', 'geodir-booking' ); ?> <span class="text-danger">*</span></label>
			<input v-model="email" id="_booking_email" type="email" class="form-control" :class="fieldClass(isEmailInvalid, isEmailValid)" placeholder="<?php esc_attr_e( 'Email', 'geodir-booking' ); ?>">
			<div class="invalid-feedback" v-if="isFieldInvalid(isEmailInvalid)"><?php esc_html_e( 'Please enter a valid email address.', 'geodir-booking' ); ?></div>
		</div>

		<div class="<?php echo ( $aui_bs5 ? 'mb-3' : 'form-group' ); ?>" v-if="! hide_phone || isPhoneInvalid">
			<label for="_booking_phone" class="form-label"><?php esc_html_e( 'Phone', 'geodir-booking' ); ?> <span class="text-danger">*</span></label>
			<input v-model="phone" id="_booking_phone" type="tel" class="form-control" :class="fieldClass(isPhoneInvalid, isPhoneValid)" placeholder="<?php esc_attr_e( 'Phone', 'geodir-booking' ); ?>">
			<div class="invalid-feedback" v-if="isFieldInvalid(isPhoneInvalid)"><?php esc_html_e( 'Please enter a valid phone number.', 'geodir-booking' ); ?></div>
		</div>

		<?php if ( ! empty( $post_info->property_guests ) ) : ?>
			<div class="col-auto flex-fill mb-3 px-0" style="flex-grow:9999!important;">
				<div class="<?php echo $aui_bs5 ? '' : 'form-group'; ?>">
					<label for="_booking_guests" class="form-label"><?php esc_html_e( 'Guests', 'geodir-booking' ); ?></label>
					<div class="input-group-inside position-relative w-100">

						<div class="position-absolute h-100">
							<div class="input-group-text pl-2 pr-2 bg-transparent border-0">
								<span class="geodir-search-input-label hover-swap text-muted">
									<i class="fas fa-user hover-content-original"></i>
								</span>
							</div>
						</div>

						<input id="_booking_guests" type="text" :placeholder="summary" class="form-control geodir-guests-search w-100 c-pointer dropdown-toggle <?php echo $aui_bs5 ? ' pl-4' : ' pl-4'; ?>" onkeydown="return false;" data-<?php echo $aui_bs5 ? 'bs-' : ''; ?>toggle="dropdown" aria-haspopup="true" aria-expanded="false" <?php echo $aui_bs5 ? ' data-bs-auto-close="outside"' : ''; ?>>

						<div class="dropdown-menu dropdown-caret-0 my-1 px-3 py-2 w-100" data-keep-open style="min-width:18rem;z-index:99999">
							<div class="row mb-0 align-items-center">
								<label for="geodir_search_adults_count" class="col-sm-6 col-form-label">
									<?php esc_html_e( 'Adults', 'geodir-booking' ); ?><small class="d-block text-muted"><?php esc_html_e( 'Ages 13 or above', 'geodir-booking' ); ?></small>
								</label>
								<div class="col-sm-6">
									<div class="input-group input-group-sm flex-nowrap justify-content-end geodir-counter-wrap">
										<button type="button" :class="{'input-group-text border-0 bg-transparent p-0 c-pointer position-relative': true, 'text-primary': adults > minAdults, 'text-gray disabled': adults <= minAdults }" @click.prevent="decrement('adults')">
											<i aria-hidden="true" class="fas fa-circle-minus fa-2x"></i>
										</button>
										<span class="input-group-text border-0 px-3 bg-transparent geodir-counter-val position-relative" style="min-width:40px;">{{ adults }}</span>
										<input name="adults" v-model="adults" type="hidden" :min="minAdults" :max="maxAdults">
										<button type="button" :disabled="isMaxGuestsReached"  :class="{ 'input-group-text border-0 bg-transparent p-0 c-pointer position-relative': true, 'text-primary': !isMaxGuestsReached, 'text-gray disabled': isMaxGuestsReached }" @click.prevent="increment('adults')">
											<i aria-hidden="true" class="fas fa-circle-plus fa-2x"></i>
										</button>
									</div>
								</div>
							</div>

							<div class="row mb-0 align-items-center">
								<label for="geodir_search_adults_count" class="col-sm-6 col-form-label">
									<?php esc_html_e( 'Children', 'geodir-booking' ); ?><small class="d-block text-muted"><?php esc_html_e( 'Ages 2-12', 'geodir-booking' ); ?></small>
								</label>
								<div class="col-sm-6">
									<div class="input-group input-group-sm flex-nowrap justify-content-end geodir-counter-wrap">
										<button type="button" :class="{'input-group-text border-0 bg-transparent p-0 c-pointer position-relative': true, 'text-primary': children > minChildren, 'text-gray disabled': children <= minChildren }" @click.prevent="decrement('children')">
											<i aria-hidden="true" class="fas fa-circle-minus fa-2x"></i>
										</button>
										<span class="input-group-text border-0 px-3 bg-transparent geodir-counter-val position-relative" style="min-width:40px;">{{ children }}</span>
										<input name="children" v-model="children" type="hidden" :min="minChildren" :max="maxChildren">
										<button type="button" :disabled="isMaxGuestsReached" :class="{ 'input-group-text border-0 bg-transparent p-0 c-pointer position-relative': true, 'text-primary': !isMaxGuestsReached, 'text-gray disabled': isMaxGuestsReached }" @click.prevent="increment('children')">
											<i aria-hidden="true" class="fas fa-circle-plus fa-2x"></i>
										</button>
									</div>
								</div>
							</div>

							<?php if ( ! empty( $post_info->property_infants ) ) : ?>
								<div class="row mb-0 align-items-center">
									<label for="geodir_search_adults_count" class="col-sm-6 col-form-label">
										<?php esc_html_e( 'Infants', 'geodir-booking' ); ?><small class="d-block text-muted"><?php esc_html_e( 'Infants', 'geodir-booking' ); ?></small>
									</label>
									<div class="col-sm-6">
										<div class="input-group input-group-sm flex-nowrap justify-content-end geodir-counter-wrap">
											<button type="button" :class="{'input-group-text border-0 bg-transparent p-0 c-pointer position-relative': true, 'text-primary': infants > minInfants, 'text-gray disabled': infants <= minInfants }" @click.prevent="decrement('infants')">
												<i aria-hidden="true" class="fas fa-circle-minus fa-2x"></i>
											</button>
											<span class="input-group-text border-0 px-3 bg-transparent geodir-counter-val position-relative" style="min-width:40px;">{{ infants }}</span>
											<input name="infants" v-model="infants" type="hidden" :min="minInfants" :max="maxInfants">
											<button type="button" class="input-group-text border-0 bg-transparent p-0 c-pointer text-primary position-relative" @click.prevent="increment('infants')">
												<i aria-hidden="true" class="fas fa-circle-plus fa-2x"></i>
											</button>
										</div>
									</div>
								</div>
							<?php endif; ?>

							<?php if ( ! empty( $post_info->property_pets ) ) : ?>
								<div class="row mb-0 align-items-center">
									<label for="geodir_search_adults_count" class="col-sm-6 col-form-label">
										<?php esc_html_e( 'Pets', 'geodir-booking' ); ?><small class="d-block text-muted"><?php esc_html_e( 'Charges may apply', 'geodir-booking' ); ?></small>
									</label>
									<div class="col-sm-6">
										<div class="input-group input-group-sm flex-nowrap justify-content-end geodir-counter-wrap">
											<button type="button" :class="{'input-group-text border-0 bg-transparent p-0 c-pointer position-relative': true, 'text-primary': pets > minPets, 'text-gray disabled': pets <= minPets }" @click.prevent="decrement('pets')">
												<i aria-hidden="true" class="fas fa-circle-minus fa-2x"></i>
											</button>
											<span class="input-group-text border-0 px-3 bg-transparent geodir-counter-val position-relative" style="min-width:40px;">{{ pets }}</span>
											<input name="pets" v-model="pets" type="hidden" :min="minPets" :max="maxPets">
											<button type="button" class="input-group-text border-0 bg-transparent p-0 c-pointer text-primary position-relative" @click.prevent="increment('pets')">
												<i aria-hidden="true" class="fas fa-circle-plus fa-2x"></i>
											</button>
										</div>
									</div>
								</div>
							<?php endif; ?>
						</div>
					</div>
				</div>
			</div>
		<?php endif; ?>

		<div class="<?php echo $aui_bs5 ? 'mb-3' : 'form-group'; ?>"> 
			<label for="_booking_dates" class="form-label"><?php esc_html_e( 'Check-in and Check-out dates', 'geodir-booking' ); ?></label>
			<input type="text" class="form-control gd-booking-dates" id="_booking_dates" :class="fieldClass(isDatesInvalid, isDatesValid, (this.start_date && this.start_date.length) )" placeholder="<?php echo esc_attr( date( 'Y-m-d' ) . ' - ' . date( 'Y-m-d', time() + DAY_IN_SECONDS ) ); ?>">
			<div class="invalid-feedback" v-if="isFieldInvalid(isDatesInvalid, (this.start_date && this.start_date.length))">{{date_error}}</div>
		</div>

		<?php
		if ( geodir_listing_belong_to_current_user( (int) $listing->ID ) ) {
			$booking_statuses = geodir_get_booking_statuses();
			unset( $booking_statuses['cancelled'] );
			unset( $booking_statuses['rejected'] );
			unset( $booking_statuses['refunded'] );

			aui()->select(
				array(
					'type'             => 'select',
					'id'               => '_booking_status',
					//'name'             => 'status',
					'label'            => __( 'Status', 'geodir-booking' ),
					'label_type'       => 'top',
					'label_class'      => ( $aui_bs5 ? 'fw-bold' : 'font-weight-bold' ),
					'size'             => 'sm',
					'options'          => $booking_statuses,
					'class'            => 'w-100',
					'value'            => 'pending_payment',
					'extra_attributes' => array(
						'v-model' => 'status',
					),
				),
				true
			);
		}
		?>

		<div v-if="countDays > 0" class="geodir-booking-totals my-4 p-3 border rounded-1">
			<div class="d-flex justify-content-between mb-2">
				<div>
					<?php
						printf(
							/* translators: %s: Booking amount. */
							esc_html__( '%1$s x %2$s %3$s', 'geodir-booking' ),
							wp_kses_post( geodir_booking_price_placeholder( '{{  formatAmount(nightlyPrice) }}' ) ),
							'{{countDays}}',
							"{{pluralize('night', countDays)}}",
						);
						?>
					<small v-if="isAvgPrice" class="d-block fs-sm text-muted"><?php esc_html_e( 'Average nightly rate is rounded.', 'geodir-booking' ); ?></small>
				</div>
				<div class="<?php echo esc_attr( $aui_bs5 ? 'fw-bold' : 'font-weight-bold' ); ?>"><?php echo wp_kses_post( geodir_booking_price_placeholder( '{{  formatAmount(subtotalAmount) }}' ) ); ?></div>
			</div>

			<div v-if="hasExtraGuest && extraGuestsFee" class="geodir-booking-totals-extraguest-fee d-flex justify-content-between mt-2 mb-2">
				<div><?php echo esc_html__( 'Extra Guests Fee:', 'geodir-booking' ); ?></div>
				<div class="<?php echo esc_attr( $aui_bs5 ? 'fw-bold' : 'font-weight-bold' ); ?>"><?php echo wp_kses_post( geodir_booking_price_placeholder( '{{ formatAmount(extraGuestsFee) }}' ) ); ?></div>
			</div>

			<div v-if="cleaningFee > 0" class="geodir-booking-totals-cleaning-fee d-flex justify-content-between mt-2 mb-2">
				<div><?php echo esc_html__( 'Cleaning Fee:', 'geodir-booking' ); ?></div>
				<div class="<?php echo esc_attr( $aui_bs5 ? 'fw-bold' : 'font-weight-bold' ); ?>"><?php echo wp_kses_post( geodir_booking_price_placeholder( '{{  formatAmount(cleaningFee) }}' ) ); ?></div>
			</div>

			<div v-if="petFee > 0" class="geodir-booking-totals-pet-fee d-flex justify-content-between mt-2 mb-2">
				<div><?php echo esc_html__( 'Pet Fee:', 'geodir-booking' ); ?></div>
				<div class="<?php echo esc_attr( $aui_bs5 ? 'fw-bold' : 'font-weight-bold' ); ?>"><?php echo wp_kses_post( geodir_booking_price_placeholder( '{{ formatAmount(petFee) }}' ) ); ?></div>
			</div>

			<div v-if="totalDiscount > 0" class="geodir-booking-totals-discount d-flex justify-content-between mt-2 mb-2 text-info">
				<div class="<?php echo esc_attr( $aui_bs5 ? 'fw-bold' : 'font-weight-bold' ); ?>"><?php echo esc_html__( 'Discount:', 'geodir-booking' ); ?> {{ totalDiscount }}<?php echo esc_html__( '% OFF', 'geodir-booking' ); ?></div>
				<div class="<?php echo esc_attr( $aui_bs5 ? 'fw-bold' : 'font-weight-bold' ); ?>">-<?php echo wp_kses_post( geodir_booking_price_placeholder( '{{ formatAmount(totalDiscountedAmount) }}' ) ); ?></div>
			</div>

			<div v-if="hasServiceFee" class="geodir-booking-totals-service-fee d-flex justify-content-between mt-2 mb-2">
				<div><?php echo esc_html__( 'Service Fee:', 'geodir-booking' ); ?></div>
				<div class="<?php echo esc_attr( $aui_bs5 ? 'fw-bold' : 'font-weight-bold' ); ?>"><?php echo wp_kses_post( geodir_booking_price_placeholder( '{{ formatAmount(serviceFee) }}' ) ); ?></div>
			</div>

			<div v-if="totalPayableAmount > 0" class="geodir-booking-totals-total d-flex justify-content-between mt-2 pt-2 border-top">
				<div class="<?php echo esc_attr( $aui_bs5 ? 'fw-bold' : 'font-weight-bold' ); ?>"><?php echo esc_html__( 'Total:', 'geodir-booking' ); ?></div>
				<div class="<?php echo esc_attr( $aui_bs5 ? 'fw-bold' : 'font-weight-bold' ); ?>"><?php echo wp_kses_post( geodir_booking_price_placeholder( '{{ formatAmount(totalPayableAmount) }}' ) ); ?></div>
			</div>
		</div>
		<div v-else class="alert alert-info my-4" role="alert">
			<span class="geodir-booking-notice-text h6 m-0 <?php echo ( $aui_bs5 ? '' : 'text-white' ); ?>">
				{{selectDatesText}}
			</span>
		</div>

		<?php if ( $aui_bs5 ) : ?>
			<div class="d-grid gap-2 mb-3">
		<?php endif; ?>
			<button type="submit" class="btn btn-primary <?php echo ( $aui_bs5 ? 'd-block' : 'btn-block' ); ?>">
				<span class="spinner-border spinner-border-sm" role="status" v-if="isSubmitting" aria-hidden="true"></span>&nbsp;
				<span v-if="isSubmitting"><?php esc_html_e( 'Processing...', 'geodir-booking' ); ?></span>
				<span v-if="!isSubmitting"> 
					<?php if ( geodir_listing_belong_to_current_user( (int) $listing->ID ) || ( isset( $post_info->gdb_instant_book ) && (bool) $post_info->gdb_instant_book !== false ) ) : ?>
						<span v-if="countDays">
							<?php
							printf(
								/* translators: %s: Booking amount. */
								esc_html__( 'Reserve for %s', 'geodir-booking' ),
								wp_kses_post( geodir_booking_price_placeholder( '{{ formatAmount(totalPayableAmount) }}' ) )
							);
							?>
						</span>
						<span v-else>
							<?php esc_html_e( 'Reserve', 'geodir-booking' ); ?>
						</span>
					<?php else : ?>
						<span>
							<?php esc_html_e( 'Request to Book', 'geodir-booking' ); ?>
						</span>
					<?php endif; ?>
				</span>
			</button>
		<?php if ( $aui_bs5 ) : ?>
			</div>
		<?php endif; ?>

		<div class="alert alert-danger mt-2" role="alert" v-if="error">{{error}}</div>

		<div class="geodir-loader position-absolute bg-white" style="top: 0;bottom: 0;left: 0;right: 0;height: 100%;width: 100%;z-index: 10;" v-if="false"></div>
		<div class="w-100 h-100 position-absolute bg-light d-flex justify-content-center align-items-center getpaid-block-ui" style="top: 0; left: 0; opacity: 0.7; cursor: progress;" v-if="isSubmitting"><div class="spinner-border" role="status"><span class="sr-only visually-hidden"><?php esc_html_e( 'Processing...', 'geodir-booking' ); ?></div></div>
	</form>

	<!-- Booking details -->
	<div class="geodir-booking-details" v-if="bookingDetails">
		<ul class="list-group">
			<li class="list-group-item d-flex justify-content-between align-items-center" v-for="(detail, key) in bookingDetails.customer_details" :class="'geodir-booking-detail-' + key" :key="key">
				<strong v-html="detail.label"></strong>
				<span class="small" v-html="detail.value"></span>
			</li>
		</ul>

		<a :href="bookingDetails.payment_url" class="d-block mt-4 btn btn-primary <?php echo ( $aui_bs5 ? 'd-block' : 'btn-block' ); ?>" v-if="!confirmBooking && bookingDetails.payment_url">
			<?php esc_html_e( 'Confirm and pay', 'geodir-booking' ); ?>
		</a>

		<div class="alert alert-danger mt-2" role="alert" v-if="error">{{error}}</div>
	</div>
</script>
