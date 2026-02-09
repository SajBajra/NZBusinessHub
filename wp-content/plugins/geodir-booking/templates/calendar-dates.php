<?php
/**
 * This template displays the calendar dates when viewing a calendar with multiple listings.
 *
 * You can overide this template by copying it to your-theme-folder/geodir-booking/calendar-dates.php
 *
 * @since   1.7.0
 * @package Noptin
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

?>

<div class="gdbc-listing-dates__wrapper col-8">

    <ul class="geodir-bookings-calendar__date-list">
        <li v-for="date_group in dates">
            <a href="#" class="geodir-bookings-calendar__date-list-item" @click="selectDate(date_group.date)">
                <div class="geodir-bookings-calendar__date-list-item-date">
                    <span>{{ date_group.date }}</span>
                </div>
                <div class="geodir-bookings-calendar__date-list-item-count">
                    <span>{{ date_group.count }}</span>
                </div>
            </a>
        </li>
    </ul>

</div>
