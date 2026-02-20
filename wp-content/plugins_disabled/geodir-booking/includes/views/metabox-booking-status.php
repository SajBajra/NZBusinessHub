<?php

/**
 * Admin View: Booking status metabox.
 *
 * @var GeoDir_Customer_Booking $booking The booking object.
 */
defined( 'ABSPATH' ) || exit;

global $aui_bs5;

$booking_status_options = geodir_get_booking_statuses();

if ( ! $booking->is_booking_past() ) {
	unset( $booking_status_options['completed'] );
}
?>

<div class="submitbox" id="submitpost">
	<div id="minor-publishing">
		<div id="misc-publishing-actions">
			<div class="misc-pub-section pb-0">
				<?php
				aui()->select(
					array(
						'type'        => 'select',
						'id'          => 'geodir-booking-status',
						'name'        => 'geodir_booking[status]',
						'label'       => __( 'Status', 'geodir-booking' ),
						'label_type'  => 'top',
						'label_class' => ( $aui_bs5 ? 'fw-bold' : 'font-weight-bold' ),
						'size'        => 'sm',
						'options'     => $booking_status_options,
						'select2'     => true,
						'class'       => 'w-100',
						'value'       => $booking->status,
					),
					true
				);
				?>
			</div>
			<div class="misc-pub-section">
				<span><?php esc_html_e( 'Created on:', 'geodir-booking' ); ?></span>
				<strong><?php echo esc_html( getpaid_format_date_value( $booking->created, '&mdash;', true ) ); ?></strong>
			</div>
		</div>
	</div>

	<div id="major-publishing-actions">
		<div id="delete-action">
			<?php
			printf(
				'<a class="submitdelete deletion" href="%s" onclick="return confirm(\'%s\')">%s</a>',
				esc_url(
					add_query_arg(
						array(
							'page'       => 'geodir-booking',
							'gd-booking' => absint( $booking->id ),
							'action'     => 'delete',
							'nonce'      => wp_create_nonce( 'gd_booking_delete_' . $booking->id ),
						),
						admin_url( 'admin.php' )
					)
				),
				esc_attr__( 'Are you sure you want to delete this booking?', 'geodir-booking' ),
				esc_html__( 'Delete Permanently', 'geodir-booking' )
			);
			?>
		</div>
		<div id="publishing-action">
			<?php submit_button( __( 'Update Booking', 'geodir-booking' ), 'primary', 'update_booking', false ); ?>
		</div>
		<div class="clear"></div>
	</div>

</div>
