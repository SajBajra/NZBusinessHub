<?php

/**
 * Admin View: Booking prices metabox.
 *
 * @var GeoDir_Customer_Booking $booking The booking object.
 */
defined( 'ABSPATH' ) || exit;

global $aui_bs5;
?>
<table class="form-table">
	<tbody>
		<tr>
			<th>
				<label for="geodir_booking_price_breakdown"><?php esc_html_e( 'Price Breakdown', 'geodir-booking' ); ?></label>
			</th>
			<td colspan="1">
				<?php
				GeoDir_Booking_Add_Booking_Page::instance()->display_price_breakdown(
					array(
						$booking,
					)
				);
				?>
			</td>
		</tr>
	</tbody>
</table>
