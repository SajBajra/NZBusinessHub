<?php
/**
 * This template displays available dates.
 *
 * You can overide this template by copying it to your-theme-folder/geodir-booking/booking-availability.php
 * @var WP_Post $listing
 * @var string $wrap_class
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

global $aui_bs5;

$js_data = array(
	'listing_id'     => $listing->ID,
	'disabled_dates' => GeoDir_Booking::get_disabled_dates( $listing->ID ),
);

?>
<div class="geodir-booking-availability-wrapper position-relative <?php echo esc_attr( $wrap_class ); ?>" id="geodir-booking-availability-wrapper-<?php echo esc_attr( $listing->ID ); ?>" data-js_data="<?php echo esc_attr( wp_json_encode( $js_data ) ); ?>">
	<div class="geodir-booking-availability">

		<div class="my-3">
			<small class="geodir-booking-availability__selected_dates form-text d-block text-muted mb-3">&nbsp;{{selectedRange}}</small>
			<div class="geodir-booking-availability__date_picker" style="width: 100%;"></div>
		</div>

		<div class="geodir-booking-availability__clear-dates <?php echo ( $aui_bs5 ? 'mb-3' : 'form-group' ); ?> d-flex justify-content-end">
			<a href="#" class="btn btn-link btn-sm" @click.prevent="clearDates"><?php esc_html_e( 'Clear dates', 'geodir-booking' ); ?></a>
		</div>

	</div>
	<div class="geodir-loader position-absolute bg-white" style="top: 0;bottom: 0;left: 0;right: 0;height: 100%;width: 100%;z-index: 10;" v-if="false"></div>
</div>
