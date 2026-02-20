<?php
/**
 * This template displays all the list of listings when viewing a calendar with multiple listings.
 *
 * You can overide this template by copying it to your-theme-folder/geodir-booking/calendar-listings.php
 *
 * @since   1.7.0
 * @package Noptin
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

global $aui_bs5;
?>

<div class="gdbc-listings__wrapper col-4">

    <ul class="gdbc-listings list-group">
        <li v-for="listing in listings" class="list-group-item" :class="listingClass(listing, 'gdbc-list-group-item')">
            <a :href="listing.guid" class="d-flex align-items-center text-dark" target="_blank">
                <div class="gdbc-list-group__image <?php echo ( $aui_bs5 ? 'me-2' : 'mr-2' ); ?>">
                    <img v-if="listing.featured_image" :src="listing.featured_image" :alt="listing.post_title" width="32" height="32">
                    <i v-else class="fa-solid fa-image fa-2xl text-muted"></i>
                </div>
                <div class="gdbc-list-group__title flex-fill overflow-hidden small">{{ listing.post_title }}</div>
            </a>
        </li>
    </ul>

</div>
