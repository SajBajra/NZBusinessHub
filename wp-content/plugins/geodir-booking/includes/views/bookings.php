<?php
/**
 * Admin View: Bookings Table.
 *
 */
defined( 'ABSPATH' ) || exit;

if ( ! empty( $_GET['action'] ) ) {
	switch( $_GET['action'] ) {
		case 'delete':
			if ( ! empty( $_GET['nonce'] ) && ! empty( $_GET['gd-booking'] ) && wp_verify_nonce( $_GET['nonce'], 'gd_booking_delete_' . absint( $_GET['gd-booking'] ) ) ) {
				if ( geodir_delete_booking( absint( $_GET['gd-booking'] ) ) ) {
					echo '<div class="updated"><p>' . esc_html__( 'Booking deleted.', 'geodir-booking' ) . '</p></div>';
				}
			}
			break;
	}
}

$bookings_table = new GeoDir_Booking_Admin_Bookings_Table();

$add_booking_url = add_query_arg(
	array(
		'page' => 'geodir-booking-add-new',
	),
	admin_url( 'admin.php' )
);
?>

<div class="wrap geodir-bookings-page" id="geodir-bookings-wrapper">

	<h1 class="wp-heading-inline"><?php esc_html_e( 'Bookings', 'geodir-booking' ); ?></h1>
	<a href="<?php echo esc_url( $add_booking_url ); ?>" class="page-title-action"><?php esc_html_e( 'Add New Booking', 'geodir-booking' ); ?></a>
	<hr class="wp-header-end" />

	<style>
		.column-status,
		.column-created,
		.column-start_date,
		.column-end_date {
			width: 100px;
		}

		.column-site_commission,
		.column-service_fee,
		.column-payable_amount {
			width: 140px;
		}
	</style>

	<form id="geodir-bookings-table" class="bsui" method="GET">
		<input type="hidden" name="page" value="geodir-booking">
		<?php $bookings_table->search_box( __( 'Search Bookings', 'geodir-booking' ), 'post-search-input' ); ?>
		<?php $bookings_table->display(); ?>
	</form>

</div>
