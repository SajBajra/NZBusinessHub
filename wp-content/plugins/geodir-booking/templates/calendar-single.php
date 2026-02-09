<?php

/**
 * This template displays a calendar for a single listing.
 *
 * You can overide this template by copying it to your-theme-folder/geodir-booking/calendar-single.php
 *
 * @since 1.0.0
 */

// Exit if accessed directly.
defined('ABSPATH') || exit;

?>

<div class="gdbc-calendar__header d-flex justify-content-between mb-3">
    <?php geodir_get_template( 'calendar-single-header.php', array(), 'geodir-booking', plugin_dir_path( GEODIR_BOOKING_FILE ) . 'templates' ); ?>
</div>

<div class="gdbc-calendar__body">
    <?php geodir_get_template( 'calendar-single-body.php', array(), 'geodir-booking', plugin_dir_path( GEODIR_BOOKING_FILE ) . 'templates' ); ?>
</div>
