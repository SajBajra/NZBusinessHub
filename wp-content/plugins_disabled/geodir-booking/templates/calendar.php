<?php
/**
 * This template displays all available listings for a single author.
 *
 * You can overide this template by copying it to your-theme-folder/geodir-booking/calendar.php
 *
 * @since   1.7.0
 * @package Noptin
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

global $aui_bs5;
?>
<div class="geodir-bookings-calendar-outer">
	<div class="geodir-bookings-calendar w-100 h-100" :class="{ 'gdbc-fullscreen bg-white text-dark': isFullScreen, 'gdbc-edit-mode': edit_mode, 'gdbc-modal': isModal }">
		<div v-if="hasListings">
			<div class="gdbc__single" v-if="is_single_listing">
				<?php geodir_get_template( 'calendar-single.php', array(), 'geodir-booking', plugin_dir_path( GEODIR_BOOKING_FILE ) . 'templates' ); ?>
			</div>

			<div class="gdbc__multiple overflow-auto row" v-else>
				<?php geodir_get_template( 'calendar-listings.php', array(), 'geodir-booking', plugin_dir_path( GEODIR_BOOKING_FILE ) . 'templates' ); ?>
				<?php geodir_get_template( 'calendar-dates.php', array(), 'geodir-booking', plugin_dir_path( GEODIR_BOOKING_FILE ) . 'templates' ); ?>
			</div>

			<?php geodir_get_template( ( $aui_bs5 ? 'bs5/' : '' ) . 'ruleset-editor.php', array(), 'geodir-booking', plugin_dir_path( GEODIR_BOOKING_FILE ) . 'templates' ); ?>
			<?php geodir_get_template( 'date-editor.php', array(), 'geodir-booking', plugin_dir_path( GEODIR_BOOKING_FILE ) . 'templates' ); ?>

		</div>

		<div v-else>
			<div class="geodir-bookings-calendar__no-listings">
				<?php
					aui()->alert(
						array(
							'type'    => 'info',
							'content' => esc_html__( 'You have no published listings.', 'geodir-booking' ),
						),
						true
					);
					?>
			</div>
		</div>

	</div>
</div>

<style>

	.geodir-bookings-calendar {
		overflow: auto;
	}

	.gdbc__single {
		min-width: 600px;
	}

	.gdbc-calendar__weekday:last-child {
		border-bottom: 1px solid #dee2e6 !important;
	}

	.gdbc-day__is-active {
		cursor: pointer;
	}

	.gdbc-day__is-active:hover {
		background-color: rgba(0,0,0,.075);
	}

	.gdbc-day__is-inactive:not(.gdbc-day__is-past):not(.gdbc-day__is-booked) {
		cursor: not-allowed;
		background: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' version='1.1' preserveAspectRatio='none' viewBox='0 0 100 100'><path d='M100 0 L0 100 ' stroke='grey' stroke-width='1'/><path d='M0 0 L100 100 ' stroke='grey' stroke-width='1'/></svg>");
		background-repeat:no-repeat;
		background-position:center center;
		background-size: 100% 100%, auto;
	}

	.gdbc-day__is-past {
		cursor: not-allowed;
	}

	.gdbc-day__is-booked {
		cursor: not-allowed;
	}

	.gdbc-day__is-lastday, .gdbc-calendar__weekday .col:last-child {
		overflow: hidden;
	}

	.gdbc-day__checkin {
		position: relative;
	}

	.gdbc-day__checkin::before {
		content: '';
		position: absolute;
		width: 4px;
		height: 100%;
		left: 0;
		top: 0;
	}

	.gdbc-day__checkin.bg-success::before {
		background-color: #2a9d4f;
	}

	.gdbc-day__checkin.bg-salmon::before {
		background-color: #e66a57;
	}
	
	.gdbc-day__checkout {
		border-right: none !important;
	}

	.gdbc-day__is-unavailable {
		background: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' version='1.1' preserveAspectRatio='none' viewBox='0 0 100 100'><path d='M100 0 L0 100 ' stroke='grey' stroke-width='1'/><path d='M0 0 L100 100 ' stroke='grey' stroke-width='1'/></svg>");
		background-repeat:no-repeat;
		background-position:center center;
		background-size: 100% 100%, auto;
	}

	.gdbc-day__is-selected {
		background-color: #64b5f6 !important;
		color: #343a40 !important;
	}

	.gdbc-ruleset-editor__wrapper,
	.gdbc-date-editor__wrapper {
		right: 0;
		top: 0;
		bottom: 0;
		z-index: 999999;
		width: 320px;
	}

	.gdbc-ruleset-editor__wrapper .gdbc-ruleset-editor,
	.gdbc-date-editor__wrapper .gdbc-date-editor {
		margin-top: 20px;
	}

	.gdbc-day__summary {
		font-size: 13px;
		text-align: center;
		text-overflow: ellipsis;
	}

	.gdbc-day__ribbon {
		position: absolute;
		top: 25px;
		left: -10px;
		width: 120%;
		height: 30px;
		line-height: 30px;
		background-color: rgb(var(--bs-success-rgb));
		cursor: pointer;
	}

	.gdbc-day__ribbon-imported {
		background-color: rgb(var(--bs-salmon-rgb));
	}

	.gdbc-day__ribbon:not(.gdbc-day__ribbon-imported).gdbc-day__ribbon-clicked {
		background-color: #309C3D;
	}

	.gdbc-day__ribbon-imported.gdbc-day__ribbon-clicked {
		background-color: #FF6F47;
	}

	.gdbc-day__ribbon-checkin {
		left: 5%;
		padding-left: 10px;
		border-top-left-radius: 6px;
		border-bottom-left-radius: 6px;
		z-index: 10;
	}

	.gdbc-day__ribbon-checkout {
		width: 95%;
		border-top-right-radius: 6px;
		border-bottom-right-radius: 6px;
	}

	.gdbc-day__ribbon-checkin.gdbc-day__ribbon-checkout {
		width: 90%;
		height: 45px;
		line-height: 22px;
	}

	.gdbc-day__ribbon-checkin.gdbc-day__ribbon-checkout .gdbc-day__summary {
		text-wrap: wrap;
	}

	.gdbc-day__ribbon .gdbc-day__summary {
		text-wrap: nowrap;
		font-size: 12px;
		color: #fff;
	}

	.modal-open .gdbc-day__popover {
		z-index: 99999;
	}

	.gdbc-day__popover {
		min-width: 350px;
		box-shadow: 0 24px 38px 3px rgba(0, 0, 0, .14), 0 9px 46px 8px rgba(0, 0, 0, .12), 0 11px 15px -7px rgba(0, 0, 0, .2);
	}

	.gdbc-day__popover .table {
		display: block;
		table-layout: fixed;
		font-size: 13px;
		width: 100%;
		margin-bottom: 0;
	}

	.gdbc-day__popover .table tbody {
		display: block;
		width: 100%;
	}

	.gdbc-day__popover .table tr {
		display: flex;
		width: 100%;
	}

	.gdbc-day__popover .table td, .gdbc-day__popover .table th {
		overflow: hidden;
	}

	.gdbc-day__popover .table th {
		width: 30%;
		font-weight: 600;
	}

	.gdbc-day__popover .table td {
		width: 70%;
	}

	.gdbc-day__popover .table td a {
		color: rgb(var(--bs-primary-rgb));
	}
</style>
