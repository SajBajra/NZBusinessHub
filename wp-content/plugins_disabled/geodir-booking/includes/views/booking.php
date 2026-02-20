<?php

/**
 * Admin View: Single Booking.
 *
 */
defined( 'ABSPATH' ) || exit;

$booking = geodir_get_booking( absint( $_GET['gd-booking'] ) );

if ( ! $booking ) {
	esc_html_e( 'Booking not found.', 'geodir-booking' );
	return;
}

do_action( 'geodir_booking_admin_before_single_booking', $booking );
?>

<style>
	.wp-heading-inline .gd-booking-date,
	.wp-heading-inline .gd-booking-status-wrapper {
		font-size: 13px;
		color: #777;
	}
</style>

<div class="wrap geodir-bookings-page" id="geodir-bookings-wrapper">

	<h1 class="wp-heading-inline">
		<span><?php printf( /* translators: %s Booking name */ esc_html__( 'Booking %s', 'geodir-booking' ), esc_html( '#' . absint( $booking->id ) ) ); ?></span>
	</h1>

	<?php
		if ( false === $saved_booking || is_wp_error( $saved_booking ) ) {
			printf(
				'<div class="error is-dismissible"><p>%s</p></div>',
				esc_html__( 'Could not save your changes. Please try again.', 'geodir-booking' )
			);
		}

		if ( is_a( $saved_booking, 'GeoDir_Customer_Booking' ) ) {
			printf(
				'<div class="notice notice-success is-dismissible"><p>%s</p></div>',
				esc_html__( 'Your changes have been saved.', 'geodir-booking' )
			);
		}
	?>
	<form id="gd-edit-booking" class="bsui" method="POST">
		<?php wp_nonce_field( 'meta-box-order', 'meta-box-toplevel_page_geodir-booking-nonce', false ); ?>
		<?php wp_nonce_field( 'closedpostboxes', 'closedpostboxesnonce', false ); ?>
		<?php wp_nonce_field( 'geodir-booking', 'geodir-booking' ); ?>
		<input type="hidden" name="gd_edit_booking" value="1" />
		<input type="hidden" name="geodir_booking[id]" value="<?php echo esc_attr( $booking->id ); ?>" />

		<div id="poststuff">
			<div id="post-body" class="metabox-holder columns-<?php echo 1 === get_current_screen()->get_columns() ? '1' : '2'; ?>">

				<div id="postbox-container-1" class="postbox-container">
    				<?php do_meta_boxes( 'toplevel_page_geodir-booking', 'side', $booking ); ?>
				</div>

				<div id="postbox-container-2" class="postbox-container">
    				<?php do_meta_boxes( 'toplevel_page_geodir-booking', 'normal', $booking ); ?>
					<?php do_meta_boxes( 'toplevel_page_geodir-booking', 'advanced', $booking ); ?>
				</div>
			</div>
		</div>

		<script>jQuery(document).ready(function(){ postboxes.add_postbox_toggles( 'toplevel_page_geodir-booking' ); });</script>

	</form>
</div>
