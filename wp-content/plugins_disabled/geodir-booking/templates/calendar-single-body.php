<?php

/**
 * This template displays a calendar body for a single listing.
 *
 * The calendar shows bookings for the current month and the selected listing.
 *
 * You can overide this template by copying it to your-theme-folder/geodir-booking/calendar-single-body.php
 *
 * @since 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

global $aui_bs5;

?> 
<div class="gdbc-calendar-body__wrapper">
	<div class="gdbc-calendar-body w-100 pb-5 overflow-auto" @mouseup="handleMouseUp" @mouseleave="handleMouseLeave">
		
		<div class="row border border-bottom-0 mx-0 <?php echo ( $aui_bs5 ? 'border-end-0' : 'border-right-0' ); ?>">
			<div class="gdbc-calendar__weekday col py-2 <?php echo ( $aui_bs5 ? 'border-end fw-bold' : 'border-right font-weight-bold' ); ?>" v-for="day in days" :key="day">{{day}}</div>
		</div>
		<div v-for="(row, index) in monthRows" :key="index" class="row gdbc-calendar__weekday border border-bottom-0 mx-0 <?php echo ( $aui_bs5 ? 'border-end-0' : 'border-right-0' ); ?>">
			<div 
				v-for="(day, dayNum) in row" 
				:key="day" 
				class="col py-2 position-relative d-flex align-items-center justify-content-center <?php echo ( $aui_bs5 ? 'border-end' : 'border-right' ); ?>" 
				:class="getDayRuleClass(day)" 
				style="min-height: 80px;" 
				@click.prevent.exact="prepareDayRuleForEditing(day, false)" 
				@click.prevent.ctrl.exact="prepareDayRuleForEditing(day, true)"
				@click.prevent.exact="handleDayClick($event, day)"
				:data-day="day.day"
				@mousedown="handleMouseDown($event, day)"
				@mousemove="handleMouseMove($event, day)" 
				v-bind="!day.is_available && day.uid ? getPopoverAttributes(day) : {}"
			>
				<small :class="getDayRuleDayClass( day )" class="position-absolute" style="top: 6px; left: 6px;">{{day.day}}</small>
				<span v-if="day.active && day.is_available">{{ getCalDayAmount( day, currentListing ) }}</span>
				
				<div :class="getRibbonDayClass( day )" v-if="day.is_booked" 
					:data-gdbc-key="day.checkin_date"
					data-bs-toggle="popover-html" 
					title="<?php esc_html_e( 'Booking Details', 'geodir-booking' ); ?>" 
					data-bs-custom-class="gdbc-day__popover" 
					:data-bs-content="bookingDetailsPopover(day)" 
					data-bs-trigger="focus" 
					data-bs-html="true" 
					data-bs-placement="top"
					@click.prevent.exact="handleRibbonClick(day)"
					@mouseover="handleRibbonMouseover(day)"
					@mouseleave="handleRibbonMouseleave(day)"
				>
					<span v-if="!day.is_imported && day.is_checkin_day" class="gdbc-day__summary" @mouseover="handleRibbonMouseover(day)" @mouseleave="handleRibbonMouseleave(day)"><?php esc_html_e( 'Booking #', 'geodir-booking' ); ?>{{ day.booking_id }}</span>
					<span v-else-if="day.is_imported && day.is_checkin_day && day.ical" class="gdbc-day__summary" @mouseover="handleRibbonMouseover(day)" @mouseleave="handleRibbonMouseleave(day)">{{ day.ical.summary }}</span>
				</div>
			</div>
		</div>
	</div>
</div>

<a href="#" class="btn btn-primary <?php echo ( $aui_bs5 ? 'd-block' : 'btn-block' ); ?>" v-if="! edit_mode" @click.prevent="edit_mode = ! edit_mode"><?php esc_html_e( 'Edit Price Data', 'geodir-booking' ); ?></a>
