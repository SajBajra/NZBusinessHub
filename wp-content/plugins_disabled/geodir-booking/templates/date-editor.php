<?php

/**
 * This template displays a listing's date editor.
 *
 * You can overide this template by copying it to your-theme-folder/geodir-booking/date-editor.php
 *
 * @since 1.0.0
 */

// Exit if accessed directly.
defined('ABSPATH') || exit;

global $aui_bs5;
?>
<div class="gdbc-date-editor__wrapper bg-white overflow-auto position-fixed shadow p-3" v-if="selected_days.length">
	<div class="gdbc-date-editor">
		<div class="gdbc-date-editor__header d-flex align-items-center text-muted mb-4">
			<div class="gdbc-date-editor-header__image <?php echo ( $aui_bs5 ? 'me-2' : 'mr-2' ); ?>">
				<i class="fas fa-calendar-alt fa-2xl text-muted"></i>
			</div>
			<strong class="gdbc-date-editor-header__title flex-fill overflow-hidden">{{ selectedDayDates }}</strong>
		</div>

		<div class="<?php echo ( $aui_bs5 ? 'form-check form-switch mb-3' : 'custom-control custom-switch form-group' ); ?>">
			<input type="checkbox" class="<?php echo ( $aui_bs5 ? 'form-check-input' : 'custom-control-input' ); ?>" v-model="selectedDayRuleIsAvailable" id="gdbc-selected-date-availability">
			<label class="<?php echo ( $aui_bs5 ? 'form-check-label' : 'custom-control-label' ); ?>" v-if="selected_days.length == 1" for="gdbc-selected-date-availability"><?php esc_html_e( 'Is this date available?', 'geodir-booking' ); ?></label>
			<label class="<?php echo ( $aui_bs5 ? 'form-check-label' : 'custom-control-label' ); ?>" v-else for="gdbc-selected-date-availability"><?php esc_html_e( 'Are these dates available?', 'geodir-booking' ); ?></label>
		</div>

		<div class="<?php echo ( $aui_bs5 ? 'mb-3' : 'form-group' ); ?>">
			<label class="form-label" for="gdbc-date-nightly-price"><?php esc_html_e( 'Nightly price', 'geodir-booking' ); ?></label>
			<div class="input-group input-group-sm">
				<input type="number" id="gdbc-date-nightly-price" v-model="selectedDayRulePrice" placeholder="0.00" class="form-control form-control-sm<?php echo ( $aui_bs5 ? ' rounded-end-0' : '' ); ?>" min="<?php echo (float) geodir_booking_night_min_price(); ?>">
				<div class="<?php echo ( $aui_bs5 ? '' : 'input-group-append' ); ?>" style="top: 0px; right: 0px;">
					<span class="input-group-text<?php echo ( $aui_bs5 ? ' rounded-start-0' : '' ); ?>">{{ currency_symbol }}</span>
				</div>
			</div>
		</div>

		<div class="<?php echo ( $aui_bs5 ? 'mb-3' : 'form-group' ); ?>">
			<label class="form-label" for="gdbc-date-private-note"><?php esc_html_e( 'Private Note', 'geodir-booking' ); ?></label>
			<textarea id="gdbc-date-private-note" v-model="selectedDayNote" class="form-control form-control-sm" rows="3"></textarea>
		</div>

		<?php if ( $aui_bs5 ) { ?><div class="d-grid gap-2"><?php } ?>
		<button type="submit" @click.prevent="saveSelectedDayRules()" class="btn btn-primary<?php echo ( $aui_bs5 ? '' : ' btn-block' ); ?>">
			<span class="spinner-border spinner-border-sm" role="status" v-if="selectedDaySaving" aria-hidden="true"></span>&nbsp;
			<span v-if="selectedDaySaving"><?php esc_html_e( 'Saving...', 'geodir-booking' ); ?></span>
			<span v-if="!selectedDaySaving"><?php esc_html_e( 'Save', 'geodir-booking' ); ?></span>
		</button>

		<button type="submit" @click.prevent="this.selected_days = []" class="btn btn-secondary<?php echo ( $aui_bs5 ? '' : ' btn-block' ); ?>"><?php esc_html_e( 'Close', 'geodir-booking' ); ?></button>
		<?php if ( $aui_bs5 ) { ?></div><?php } ?>

		<div class="alert alert-danger mt-2" v-if="selectedDayError" role="alert">{{selectedDayError}}</div>
		<div class="alert alert-success mt-2" v-if="selectedDaySaved" role="alert"><?php esc_html_e( 'Your changes have been saved.', 'geodir-booking' ); ?></div>

	</div>

</div>
