<?php

/**
 * This template displays a calendar header for a single listing.
 *
 * It allows the listing owner to switch the month dates and listings.
 *
 * You can overide this template by copying it to your-theme-folder/geodir-booking/calendar-single-header.php
 *
 * @since 1.0.0
 */

// Exit if accessed directly.
defined('ABSPATH') || exit;

global $aui_bs5;

$current_month = gmdate( 'n', time() );
$current_year  = gmdate( 'Y', time() );
$years         = array( $current_year, $current_year + 1, $current_year + 2, $current_year + 3, $current_year + 4, $current_year + 5 );
?>

<div class="gdbc__header-left">
	<select v-model="current_month_year" class="gdbc-header-left__switch-month <?php echo ( $aui_bs5 ? 'form-select form-select-sm' : 'custom-select custom-select-sm' ); ?>">
		<?php foreach ( $years as $calendar_year ) : ?>
			<?php foreach ( range( 1, 12 ) as $calendar_month ) : ?>

				<?php if ( $calendar_year == $current_year && $calendar_month < $current_month ) : ?>
					<?php continue; ?>
				<?php endif; ?>

				<option value="<?php echo esc_attr( $calendar_year . '-' . $calendar_month ); ?>"><?php echo esc_html( date_i18n( 'F Y', strtotime( $calendar_year . '-' . $calendar_month . '-01' ) )); ?></option>
			<?php endforeach; ?>
		<?php endforeach; ?>
	</select>
</div>

<div class="gdbc__header-right d-flex align-items-start">
	<div class="<?php echo ( $aui_bs5 ? 'me-2' : 'mr-2' ); ?>">
		<select v-model="listing_id" class="gdbc-header-right__switch-listing <?php echo ( $aui_bs5 ? 'form-select form-select-sm' : 'custom-select custom-select-sm' ); ?>">
			<option v-for="listing in listings" :value="listing.ID">{{ listing.post_title }}</option>
		</select>
	</div>
	<a href="#" class="btn btn-light" v-if="! isModal" @click.prevent="isFullScreen = !isFullScreen">
		<i class="fas fa-expand-arrows-alt fa-2xl text-dark" v-if="! isFullScreen"></i>
		<i class="fas fa-compress-arrows-alt fa-2xl text-dark" v-if="isFullScreen"></i>
	</a>
</div>
