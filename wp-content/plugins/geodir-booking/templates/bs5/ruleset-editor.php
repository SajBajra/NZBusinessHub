<?php

/**
 * This template displays a listing's ruleset editor.
 *
 * You can overide this template by copying it to your-theme-folder/geodir-booking/bs5/ruleset-editor.php
 *
 * @since 1.0.0
 */

// Exit if accessed directly.
defined('ABSPATH') || exit;

?>
<div class="gdbc-ruleset-editor__wrapper bg-white overflow-auto position-fixed shadow p-3" v-if="edit_mode && ! selected_days.length">
	<template v-for="listing in listings" :key="listing.ID">
		<div class="gdbc-ruleset-editor" v-if="listing.ID === listing_id">

			<div class="gdbc-ruleset-editor__header d-flex align-items-center mb-4">
				<div class="gdbc-ruleset-editor-header__image mr-2 me-2">
					<img v-if="listing.featured_image" :src="listing.featured_image" :alt="listing.post_title" width="32" height="32">
					<i v-else class="fa-solid fa-image fa-2xl text-muted"></i>
				</div>
				<a :href="listing.guid" class="d-block text-muted mr-2 me-2" target="_blank">
					<strong class="gdbc-ruleset-editor-header__title flex-fill overflow-hidden">{{ listing.post_title }}</strong>
				</a>
				<a href="#" v-if="!listing.editing_title" @click="listing.editing_title = true" class="gdbc-ruleset-editor-header__edit-title">
					<i class="fa-solid fa-edit text-primary"></i>
				</a>
			</div>

			<div class="my-2" v-if="listing.editing_title">
				<div class="mb-3">
					<label for="gdbc-ruleset-title"><?php esc_html_e( 'New Title', 'geodir-booking' ); ?></label>
					<input type="text" id="gdbc-ruleset-title" v-model="listing.new_title" class="form-control form-control-sm">
				</div>
				<div class="mb-3">
					<button type="button" class="btn btn-sm btn-primary" @click.prevent="saveTitle(listing)"><?php esc_html_e( 'Save', 'geodir-booking' ); ?></button>
					<button type="button" class="btn btn-sm btn-link" @click.prevent="listing.editing_title = false"><?php esc_html_e( 'Cancel', 'geodir-booking' ); ?></button>
				</div>
			</div>

			<div class="accordion mb-4" id="gdbc-ruleset-editor-accordion">

				<div class="accordion-item">
					<h2 class="accordion-header" id="gdbc-ruleset-editor-nightly-price-h">
						<button class="accordion-button collapsed fw-normal" type="button" data-bs-toggle="collapse" data-bs-target="#gdbc-ruleset-editor-nightly-price">
							<?php esc_html_e( 'Nightly Price', 'geodir-booking' ); ?>
						</button>
					</h2>
					<div id="gdbc-ruleset-editor-nightly-price" class="accordion-collapse collapse" aria-labelledby="gdbc-ruleset-editor-nightly-price-h" data-bs-parent="#gdbc-ruleset-editor-accordion">
						<div class="accordion-body">
							<div class="mb-3">
								<label class="form-label" for="gdbc-ruleset-nightly-price"><?php esc_html_e( 'Nightly price', 'geodir-booking' ); ?></label>
								<div class="input-group input-group-sm">
									<input type="number" id="gdbc-ruleset-nightly-price" v-model="listing.ruleset.nightly_price" placeholder="0.00" class="form-control form-control-sm" min="1">
									<div style="top: 0px; right: 0px;">
										<span class="input-group-text rounded-start-0 rounded-start-0">{{currency_symbol}}</span>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>

                <div class="accordion-item">
					<h2 class="accordion-header" id="gdbc-ruleset-editor-extra-fees-h">
						<button class="accordion-button collapsed fw-normal" type="button" data-bs-toggle="collapse" data-bs-target="#gdbc-ruleset-editor-extra-fees">
							<?php esc_html_e( 'Extra Fees', 'geodir-booking' ); ?>
						</button>
					</h2>
					<div id="gdbc-ruleset-editor-extra-fees" class="accordion-collapse collapse" aria-labelledby="gdbc-ruleset-editor-extra-fees-h" data-bs-parent="#gdbc-ruleset-editor-accordion">
						<div class="accordion-body">
							<div class="mb-3">
								<label class="form-label" for="gdbc-ruleset-cleaning-fee"><?php esc_html_e( 'Cleaning Fee', 'geodir-booking' ); ?> <small>(<?php esc_html_e( 'per booking', 'geodir-booking' ); ?>)</small></label>
								<div class="input-group input-group-sm">
									<input type="number" id="gdbc-ruleset-cleaning-fee" v-model="listing.ruleset.cleaning_fee" placeholder="0.00" class="form-control form-control-sm">
									<div style="top: 0px; right: 0px;">
										<span class="input-group-text rounded-start-0 rounded-start-0">{{currency_symbol}}</span>
									</div>
								</div>
							</div>
 
                            <div class="mb-3" v-if="listing.is_pets_enabled">
								<label class="form-label" for="gdbc-ruleset-pet-fee"><?php esc_html_e( 'Pet Fee', 'geodir-booking' ); ?> <small>(<?php esc_html_e( 'per booking', 'geodir-booking' ); ?>)</small></label>
								<div class="input-group input-group-sm">
									<input type="number" id="gdbc-ruleset-pet-fee" v-model="listing.ruleset.pet_fee" placeholder="0.00" class="form-control form-control-sm">
									<div style="top: 0px; right: 0px;">
										<span class="input-group-text rounded-start-0 rounded-start-0">{{currency_symbol}}</span>
									</div>
								</div>
							</div>

							<div class="mb-3">
								<label class="form-label mb-1" for="gdbc-ruleset-extra-guest-fee"><?php esc_html_e( 'Extra Guests Fee', 'geodir-booking' ); ?></label>
								<div class="form-text mt-0 mb-2">
									<?php
										printf(
											/* translators: %s: After more than: x guests, charge x per extra person, per night. */
											esc_html__( 'After more than: %s, charge %s per extra person, per night.', 'geodir-booking' ),
											"{{ listing.ruleset.extra_guest_count }} {{ pluralize('guest', listing.ruleset.extra_guest_count)}} ",
											wp_kses_post( geodir_booking_price_placeholder( '{{listing.ruleset.extra_guest_fee}}' ) )
										);
									?>
								</div>
								<div class="input-group input-group-sm">
									<input type="number" id="gdbc-ruleset-extra-guest-fee" v-model="listing.ruleset.extra_guest_fee" placeholder="0.00" class="form-control form-control-sm">
									<div style="top: 0px; right: 0px;">
										<span class="input-group-text rounded-start-0 rounded-start-0">{{currency_symbol}}</span>
									</div>
								</div>
							</div>

							<div class="mb-3">
								<label class="form-label" for="gdbc-ruleset-extra-guest-count"><?php esc_html_e( 'For each guest after', 'geodir-booking' ); ?></label>
								<select class="form-select custom-select custom-select-sm" v-model="listing.ruleset.extra_guest_count" id="gdbc-ruleset-extra-guest-count">
									<?php for ( $i = 2; $i <= 10; $i++ ) : ?>
										<option value="<?php echo esc_attr( $i ); ?>"><?php echo esc_html( $i ); ?></option>
									<?php endfor; ?>
								</select>
							</div>
						</div>
					</div>
				</div>

				<div class="accordion-item">
					<h2 class="accordion-header" id="gdbc-ruleset-editor-duration-discount-h">
						<button class="accordion-button collapsed fw-normal" type="button" data-bs-toggle="collapse" data-bs-target="#gdbc-ruleset-editor-duration-discount">
							<?php esc_html_e( 'Length-of-stay discounts', 'geodir-booking' ); ?>
						</button>
					</h2>

					<div id="gdbc-ruleset-editor-duration-discount" class="accordion-collapse collapse" aria-labelledby="gdbc-ruleset-editor-duration-discount-h" data-bs-parent="#gdbc-ruleset-editor-accordion">
						<div class="accordion-body">
							<small class="form-text d-block text-muted mb-3"><?php esc_html_e( 'Set discounts based on weekly or monthly stays, or customize your own.', 'geodir-booking' ); ?></small>

							<div v-if="listing.ruleset.duration_discounts.length">
								<div class="gdbc-ruleset-editor-duration-discount row mb-3">
									<div class="gdbc-ruleset-editor-duration-discount__nights col"><small><?php esc_html_e( 'Nights', 'geodir-booking' ); ?></small></div>
									<div class="gdbc-ruleset-editor-duration-discount__discount col"><small><?php esc_html_e( 'Discount', 'geodir-booking' ); ?></small></div>
								</div>
							</div>

							<div v-for="discount in listing.ruleset.duration_discounts">
								<div class="gdbc-ruleset-editor-duration-discount row mb-3">
									<div class="gdbc-ruleset-editor-duration-discount__nights col">
										<input type="number" class="form-control form-control-sm" placeholder="0" v-model="discount.nights" min="1">
										<a href="#" class="gdbc-ruleset-editor-duration-discount__remove-discount text-danger" @click.prevent="removeDurationDiscount(listing.ruleset, discount)">
											<small><?php esc_html_e( 'Remove', 'geodir-booking' ); ?></small>
										</a>
									</div>
									<div class="gdbc-ruleset-editor-duration-discount__discount col">
										<div class="input-group input-group-sm">
											<input type="number" class="form-control form-control-sm" placeholder="%" v-model="discount.percent" min="1">
											<div style="top: 0px; right: 0px;">
												<span class="input-group-text rounded-start-0 px-2">%</span>
											</div>
										</div>
									</div>
								</div>
							</div>

							<div class="gdbc-ruleset-editor-duration-discount__add">
								<button type="button" class="btn btn-sm btn-primary" @click.prevent="addDurationDiscount(listing.ruleset)">
									<i class="fa-solid fa-plus"></i>
									<?php esc_html_e( 'Add discount', 'geodir-booking' ); ?>
								</button>
							</div>
						</div>
					</div>
				</div>

				<div class="accordion-item">
					<h2 class="accordion-header" id="gdbc-ruleset-editor-last-minute-discount-h">
						<button class="accordion-button collapsed fw-normal" type="button" data-bs-toggle="collapse" data-bs-target="#gdbc-ruleset-editor-last-minute-discount">
							<?php esc_html_e( 'Last-minute discounts', 'geodir-booking' ); ?>
						</button>
					</h2>

					<div id="gdbc-ruleset-editor-last-minute-discount" class="accordion-collapse collapse" aria-labelledby="gdbc-ruleset-editor-last-minute-discount-h" data-bs-parent="#gdbc-ruleset-editor-accordion">
						<div class="accordion-body">
							<small class="form-text d-block text-muted mb-3"><?php esc_html_e( 'Offer a discount for bookings that happen close to arrival.', 'geodir-booking' ); ?></small>

							<div v-if="listing.ruleset.last_minute_discounts.length">
								<div class="gdbc-ruleset-editor-last-minute-discount row mb-3">
									<div class="gdbc-ruleset-editor-last-minute-discount__days col"><small><?php esc_html_e( 'Days', 'geodir-booking' ); ?></small></div>
									<div class="gdbc-ruleset-editor-last-minute-discount__discount col"><small><?php esc_html_e( 'Discount', 'geodir-booking' ); ?></small></div>
								</div>
							</div>

							<div v-for="discount in listing.ruleset.last_minute_discounts">
								<div class="gdbc-ruleset-editor-last-minute-discount row mb-3">
									<div class="gdbc-ruleset-editor-last-minute-discount__days col">
										<input type="number" class="form-control form-control-sm" placeholder="0" v-model="discount.days" min="1">
										<a href="#" class="gdbc-ruleset-editor-last-minute-discount__remove-discount text-danger" @click.prevent="removeLastMinuteDiscount(listing.ruleset, discount)">
											<small><?php esc_html_e( 'Remove', 'geodir-booking' ); ?></small>
										</a>
									</div>
									<div class="gdbc-ruleset-editor-last-minute-discount__discount col">
										<div class="input-group input-group-sm">
											<input type="number" class="form-control form-control-sm" placeholder="%" v-model="discount.percent" min="1">
											<div style="top: 0px; right: 0px;">
												<span class="input-group-text rounded-start-0 px-2">%</span>
											</div>
										</div>
									</div>
								</div>
							</div>

							<div class="gdbc-ruleset-editor-last-minute-discount__add">
								<button type="button" class="btn btn-sm btn-primary" @click.prevent="addLastMinuteDiscount(listing.ruleset)">
									<i class="fa-solid fa-plus"></i>
									<?php esc_html_e( 'Add discount', 'geodir-booking' ); ?>
								</button>
							</div>
						</div>
					</div>
				</div>

				<div class="accordion-item">
					<h2 class="accordion-header" id="gdbc-ruleset-editor-early-bird-discount-h">
						<button class="accordion-button collapsed fw-normal" type="button" data-bs-toggle="collapse" data-bs-target="#gdbc-ruleset-editor-early-bird-discount">
							<?php esc_html_e( 'Early-bird discounts', 'geodir-booking' ); ?>
						</button>
					</h2>

					<div id="gdbc-ruleset-editor-early-bird-discount" class="accordion-collapse collapse" aria-labelledby="gdbc-ruleset-editor-early-bird-discount-h" data-bs-parent="#gdbc-ruleset-editor-accordion">
						<div class="accordion-body">
							<small class="form-text d-block text-muted mb-3"><?php esc_html_e( 'Offer a discount for bookings that happen well in advance.', 'geodir-booking' ); ?></small>

							<div v-if="listing.ruleset.early_bird_discounts.length">
								<div class="gdbc-ruleset-editor-early-bird-discount row mb-3">
									<div class="gdbc-ruleset-editor-early-bird-discount__months col"><small><?php esc_html_e( 'Months', 'geodir-booking' ); ?></small></div>
									<div class="gdbc-ruleset-editor-early-bird-discount__discount col"><small><?php esc_html_e( 'Discount', 'geodir-booking' ); ?></small></div>
								</div>
							</div>

							<div v-for="discount in listing.ruleset.early_bird_discounts">
								<div class="gdbc-ruleset-editor-early-bird-discount row mb-3">
									<div class="gdbc-ruleset-editor-early-bird-discount__months col">
										<input type="number" class="form-control form-control-sm" placeholder="0" v-model="discount.months" min="1">
										<a href="#" class="gdbc-ruleset-editor-early-bird-discount__remove-discount text-danger" @click.prevent="removeEarlyBirdDiscount(listing.ruleset, discount)">
											<small><?php esc_html_e( 'Remove', 'geodir-booking' ); ?></small>
										</a>
									</div>
									<div class="gdbc-ruleset-editor-early-bird-discount__discount col">
										<div class="input-group input-group-sm">
											<input type="number" class="form-control form-control-sm" placeholder="%" v-model="discount.percent" min="1">
											<div style="top: 0px; right: 0px;">
												<span class="input-group-text rounded-start-0 px-2">%</span>
											</div>
										</div>
									</div>
								</div>
							</div>

							<div class="gdbc-ruleset-editor-early-bird-discount__add">
								<button type="button" class="btn btn-sm btn-primary" @click.prevent="addEarlyBirdDiscount(listing.ruleset)">
									<i class="fa-solid fa-plus"></i>
									<?php esc_html_e( 'Add discount', 'geodir-booking' ); ?>
								</button>
							</div>
						</div>
					</div>
				</div>

				<div class="accordion-item">
					<h2 class="accordion-header" id="gdbc-ruleset-editor-availability-h">
						<button class="accordion-button collapsed fw-normal" type="button" data-bs-toggle="collapse" data-bs-target="#gdbc-ruleset-editor-availability">
							<?php esc_html_e( 'Availability', 'geodir-booking' ); ?>
						</button>
					</h2>

					<div id="gdbc-ruleset-editor-availability" class="accordion-collapse collapse" aria-labelledby="gdbc-ruleset-editor-availability-h" data-bs-parent="#gdbc-ruleset-editor-accordion">
						<div class="accordion-body">
							<small class="form-text d-block text-muted mb-3"><?php esc_html_e( 'Set the minimum and maximum stay, and customize it by day if needed.', 'geodir-booking' ); ?></small>

							<div class="mb-3">
								<label class="form-label" for="gdbc-ruleset-minimum-stay"><?php esc_html_e( 'Minimum stay', 'geodir-booking' ); ?></label>
								<div class="input-group input-group-sm">
									<input type="number" id="gdbc-ruleset-minimum-stay" v-model="listing.ruleset.minimum_stay" placeholder="<?php esc_attr_e( 'Unlimited', 'geodir-booking' ); ?>" class="form-control form-control-sm" min="1">
									<div style="top: 0px; right: 0px;">
										<span class="input-group-text rounded-start-0"><?php esc_html_e( 'Nights', 'geodir-booking' ); ?></span>
									</div>
								</div>
							</div>

							<div class="mb-3">
								<label class="form-label" for="gdbc-ruleset-maximum-stay"><?php esc_html_e( 'Maximum stay', 'geodir-booking' ); ?></label>
								<div class="input-group input-group-sm">
									<input type="number" id="gdbc-ruleset-maximum-stay" v-model="listing.ruleset.maximum_stay" placeholder="<?php esc_attr_e( 'Unlimited', 'geodir-booking' ); ?>" class="form-control form-control-sm" min="1">
									<div style="top: 0px; right: 0px;">
										<span class="input-group-text rounded-start-0"><?php esc_html_e( 'Nights', 'geodir-booking' ); ?></span>
									</div>
								</div>
							</div>

							<div class="form-check mb-3">
								<input type="checkbox" v-model="listing.ruleset.per_day_minimum_stay"  class="form-check-input" id="gdbc-ruleset-per-day-minimum-stay">
								<label class="form-check-label" for="gdbc-ruleset-per-day-minimum-stay"><?php esc_html_e( 'Customize minimum stay by check in day', 'geodir-booking' ); ?></label>
							</div>

							<div class="mb-3" v-if="listing.ruleset.per_day_minimum_stay">
								<label class="form-label" for="gdbc-ruleset-monday-minimum-stay"><?php esc_html_e( 'Monday check in', 'geodir-booking' ); ?></label>
								<div class="input-group input-group-sm">
									<input type="number" id="gdbc-ruleset-tuesday-minimum-stay" v-model="listing.ruleset.monday_minimum_stay" placeholder="<?php esc_attr_e( 'Unlimited', 'geodir-booking' ); ?>" class="form-control form-control-sm" min="1">
									<div style="top: 0px; right: 0px;">
										<span class="input-group-text rounded-start-0"><?php esc_html_e( 'Nights', 'geodir-booking' ); ?></span>
									</div>
								</div>
							</div>

							<div class="mb-3" v-if="listing.ruleset.per_day_minimum_stay">
								<label class="form-label" for="gdbc-ruleset-tuesday-minimum-stay"><?php esc_html_e( 'Tuesday check in', 'geodir-booking' ); ?></label>
								<div class="input-group input-group-sm">
									<input type="number" id="gdbc-ruleset-tuesday-minimum-stay" v-model="listing.ruleset.tuesday_minimum_stay" placeholder="<?php esc_attr_e( 'Unlimited', 'geodir-booking' ); ?>" class="form-control form-control-sm" min="1">
									<div style="top: 0px; right: 0px;">
										<span class="input-group-text rounded-start-0"><?php esc_html_e( 'Nights', 'geodir-booking' ); ?></span>
									</div>
								</div>
							</div>

							<div class="mb-3" v-if="listing.ruleset.per_day_minimum_stay">
								<label class="form-label" for="gdbc-ruleset-wednesday-minimum-stay"><?php esc_html_e( 'Wednesday check in', 'geodir-booking' ); ?></label>
								<div class="input-group input-group-sm">
									<input type="number" id="gdbc-ruleset-wednesday-minimum-stay" v-model="listing.ruleset.wednesday_minimum_stay" placeholder="<?php esc_attr_e( 'Unlimited', 'geodir-booking' ); ?>" class="form-control form-control-sm" min="1">
									<div style="top: 0px; right: 0px;">
										<span class="input-group-text rounded-start-0"><?php esc_html_e( 'Nights', 'geodir-booking' ); ?></span>
									</div>
								</div>
							</div>

							<div class="mb-3" v-if="listing.ruleset.per_day_minimum_stay">
								<label class="form-label" for="gdbc-ruleset-thursday-minimum-stay"><?php esc_html_e( 'Thursday check in', 'geodir-booking' ); ?></label>
								<div class="input-group input-group-sm">
									<input type="number" id="gdbc-ruleset-thursday-minimum-stay" v-model="listing.ruleset.thursday_minimum_stay" placeholder="<?php esc_attr_e( 'Unlimited', 'geodir-booking' ); ?>" class="form-control form-control-sm" min="1">
									<div style="top: 0px; right: 0px;">
										<span class="input-group-text rounded-start-0"><?php esc_html_e( 'Nights', 'geodir-booking' ); ?></span>
									</div>
								</div>
							</div>

							<div class="mb-3" v-if="listing.ruleset.per_day_minimum_stay">
								<label class="form-label" for="gdbc-ruleset-friday-minimum-stay"><?php esc_html_e( 'Friday check in', 'geodir-booking' ); ?></label>
								<div class="input-group input-group-sm">
									<input type="number" id="gdbc-ruleset-friday-minimum-stay" v-model="listing.ruleset.friday_minimum_stay" placeholder="<?php esc_attr_e( 'Unlimited', 'geodir-booking' ); ?>" class="form-control form-control-sm" min="1">
									<div style="top: 0px; right: 0px;">
										<span class="input-group-text rounded-start-0"><?php esc_html_e( 'Nights', 'geodir-booking' ); ?></span>
									</div>
								</div>
							</div>

							<div class="mb-3" v-if="listing.ruleset.per_day_minimum_stay">
								<label class="form-label" for="gdbc-ruleset-saturday-minimum-stay"><?php esc_html_e( 'Saturday check in', 'geodir-booking' ); ?></label>
								<div class="input-group input-group-sm">
									<input type="number" id="gdbc-ruleset-saturday-minimum-stay" v-model="listing.ruleset.saturday_minimum_stay" placeholder="<?php esc_attr_e( 'Unlimited', 'geodir-booking' ); ?>" class="form-control form-control-sm" min="1">
									<div style="top: 0px; right: 0px;">
										<span class="input-group-text rounded-start-0"><?php esc_html_e( 'Nights', 'geodir-booking' ); ?></span>
									</div>
								</div>
							</div>

							<div class="mb-3" v-if="listing.ruleset.per_day_minimum_stay">
								<label class="form-label" for="gdbc-ruleset-sunday-minimum-stay"><?php esc_html_e( 'Sunday check in', 'geodir-booking' ); ?></label>
								<div class="input-group input-group-sm">
									<input type="number" id="gdbc-ruleset-sunday-minimum-stay" v-model="listing.ruleset.sunday_minimum_stay" placeholder="<?php esc_attr_e( 'Unlimited', 'geodir-booking' ); ?>" class="form-control form-control-sm" min="1">
									<div style="top: 0px; right: 0px;">
										<span class="input-group-text rounded-start-0"><?php esc_html_e( 'Nights', 'geodir-booking' ); ?></span>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>

				<div class="accordion-item">
					<h2 class="accordion-header" id="gdbc-ruleset-editor-check-in-days-h">
						<button class="accordion-button collapsed fw-normal" type="button" data-bs-toggle="collapse" data-bs-target="#gdbc-ruleset-editor-check-in-days">
							<?php esc_html_e( 'Check-in and checkout days', 'geodir-booking' ); ?>
						</button>
					</h2>

					<div id="gdbc-ruleset-editor-check-in-days" class="accordion-collapse collapse" aria-labelledby="gdbc-ruleset-editor-check-in-days-h" data-bs-parent="#gdbc-ruleset-editor-accordion">
						<div class="accordion-body">
							<small class="form-text d-block text-muted"><?php esc_html_e( "Select the days when you don't want guests to check in and check out", 'geodir-booking' ); ?></small>

							<div class="row mt-3">

								<div class="col-md-6">
									<div class="mb-3"><?php esc_html_e( "Guests can't check in on", 'geodir-booking' ); ?></div>
									<div v-for="(day, index) in days" class="form-check mb-3">
										<input type="checkbox" v-model="listing.ruleset.restricted_check_in_days" :value="index" class="form-check-input" :id="'gdbc-ruleset-restricted-check-in-days' + index">
										<label class="form-check-label" :for="'gdbc-ruleset-restricted-check-in-days' + index"><small>{{ day }}</small></label>
									</div>
								</div>

								<div class="col-md-6">
									<div class="mb-3"><?php esc_html_e( "Guests can't check out on", 'geodir-booking' ); ?></div>
									<div v-for="(day, index) in days" class="form-check mb-3">
										<input type="checkbox" v-model="listing.ruleset.restricted_check_out_days" :value="index" class="form-check-input" :id="'gdbc-ruleset-restricted-check-out-days' + index">
										<label class="form-check-label" :for="'gdbc-ruleset-restricted-check-out-days' + index"><small>{{ day }}</small></label>
									</div>
								</div>
							</div>

						</div>
					</div>
				</div>

                <div class="accordion-item">
					<h2 class="accordion-header" id="gdbc-ruleset-editor-icalendar-h">
						<button class="accordion-button collapsed fw-normal" type="button" data-bs-toggle="collapse" data-bs-target="#gdbc-ruleset-editor-icalendar">
							<?php esc_html_e( 'iCalendar Import / Export', 'geodir-booking' ); ?>
						</button>
					</h2>

					<div id="gdbc-ruleset-editor-icalendar" class="accordion-collapse collapse" aria-labelledby="gdbc-ruleset-editor-icalendar-h" data-bs-parent="#gdbc-ruleset-editor-accordion">
						<div class="accordion-body">
                            <small class="d-block mb-2">
                                <?php esc_html_e( 'Export iCalendar Feed URL', 'geodir-booking' ); ?>
                            </small>

                            <div class="input-group input-group-sm mb-2">
                                <input type="text" name="_geodir_booking_icalendar_url" class="geodir-booking-listing-ical-url form-control form-control-sm" v-model="listing.ics_url" readonly>

                                <div>
                                    <span role="button" class="input-group-text rounded-start-0 geodir-booking-ical-copy-url" data-copy-success="<?php esc_attr_e( 'Copied', 'geodir-booking' ); ?>">
                                        <?php esc_html_e( 'Copy', 'geodir-booking' ); ?> 
                                    </span>
                                </div>
                            </div>

                            <a v-bind:href="listing.ics_url" class="geodir-booking-icalendar-download-btn btn-link text-primary mb-3"><?php esc_html_e( 'Download iCalendar', 'geodir-booking' ); ?></a>

                            <small class="d-block mt-3 mb-2">
                                <?php esc_html_e( 'Import iCalendar From External Source.', 'geodir-booking' ); ?>
                            </small>
                            
                            <small class="form-text d-block text-muted mb-3"><?php esc_html_e( 'Add the URL to the external calendar you want to import. The calendar must be in a valid Calendar format (.ics).', 'geodir-booking' ); ?></small>
                            
                            <div class="geodir-booking-extrnal-urls-wrapper">
                                <div v-for="(url, index) in listing.sync_urls" :key="index">
                                    <div class="geodir-booking-ical-extrnal-url mb-3">
                                        <input name="_geodir_booking_urls[]" type="text" class="form-control form-control-sm" placeholder="<?php esc_attr_e('Calendar URL', 'geodir-booking'); ?>" v-model="listing.sync_urls[index]">
                                        <a href="#" class="btn-link text-danger" @click.prevent="removeSyncUrl(listing, url)"><small><?php esc_html_e( 'Delete', 'geodir-booking' ); ?></small></a>
                                    </div>
                                </div>
                            </div>

                            <button class="btn btn-sm btn-outline-secondary geodir-booking-add-ical-url" @click.prevent="addSyncUrl(listing)" type="button">
                                <i class="fa-solid fa-plus"></i> <?php esc_html_e( 'Add New Calendar', 'geodir-booking' ); ?>
                            </button>

                            <hr class="mt-3 mb-3">

                            <button class="btn btn-sm btn-primary w-100 geodir-booking-ical-sync-btn" type="button" @click.prevent="syncExternalCalendars(listing)">
                                <span class="spinner-border spinner-border-sm" role="status" v-if="listing.is_syncing" aria-hidden="true"></span>&nbsp;
                                <span v-if="listing.is_syncing"><?php esc_html_e( 'Syncing...', 'geodir-booking' ); ?></span>
                                <span v-if="!listing.is_syncing"><i class="fa-solid fa-sync"></i> <?php esc_html_e( 'Sync External Calendars', 'geodir-booking' ); ?></span>
                            </button>
                        </div>
                    </div>
                </div>

			</div>

			<div class="d-grid gap-2">
				<button type="submit" @click.prevent="saveRuleset(listing.ruleset)" class="btn btn-primary d-block">
					<span class="spinner-border spinner-border-sm" role="status" v-if="listing.ruleset.is_saving" aria-hidden="true"></span>&nbsp;
					<span v-if="listing.ruleset.is_saving"><?php esc_html_e( 'Saving...', 'geodir-booking' ); ?></span>
					<span v-if="!listing.ruleset.is_saving"><?php esc_html_e( 'Save', 'geodir-booking' ); ?></span>
				</button>
				<button type="submit" @click.prevent="edit_mode = ! edit_mode" class="btn btn-secondary d-block text-white"><?php esc_html_e( 'Close', 'geodir-booking' ); ?></button>
			</div>

			<div class="alert alert-danger mt-2" v-if="listing.ruleset.error" role="alert">{{listing.ruleset.error}}</div>
			<div class="alert alert-success mt-2" v-if="listing.ruleset.is_saved" role="alert"><?php esc_html_e( 'Your changes have been saved.', 'geodir-booking' ); ?></div>
		</div>
	</template>
</div>