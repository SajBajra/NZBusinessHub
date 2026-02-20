<?php
// Displays the manage tickets widget.
defined( 'ABSPATH' ) || exit;

global $aui_bs5;

$manage_tickets_nav = apply_filters(
	'geodir_manage_tickets_nav',
	array(
		'types'       => array(
			'label'   => __( 'Types', 'geodir-tickets' ),
			'fa-icon' => 'fas fa-list',
		),
		'sales'       => array(
			'label'   => __( 'Sales', 'geodir-tickets' ),
			'fa-icon' => 'fas fa-dollar-sign',
		),
		'report'      => array(
			'label'   => __( 'Report', 'geodir-tickets' ),
			'fa-icon' => 'fas fa-chart-pie',
		),
		'scanner'     => array(
			'label'   => __( 'Scanner', 'geodir-tickets' ),
			'fa-icon' => 'fas fa-ticket',
		)
	)
);
?>

<div class="bsui geodir-tickets-modal">
	<div class="modal fade" id="geodir-manage-tickets-modal<?php echo esc_attr( $id ); ?>" tabindex="-1" aria-labelledby="geodir-manage-tickets-title<?php echo esc_attr( $id ); ?>" aria-hidden="true">
		<div class="modal-dialog modal-lg modal-dialog-centered">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title" id="geodir-manage-tickets-title<?php echo esc_attr( $id ); ?>"><?php _e( 'Manage Tickets', 'geodir-tickets' ); ?></h5>
					<?php if ( $aui_bs5 ) { ?>
						<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?php esc_attr_e( 'Close', 'geodir-tickets' ); ?>"></button>
					<?php } else { ?>
						<button type="button" class="btn-close close" data-dismiss="modal" aria-label="<?php esc_attr_e( 'Close', 'geodir-tickets' ); ?>">&times;</button>
					<?php } ?>
				</div>
				<div class="modal-body">
					<ul class="nav nav-tabs geodir-manage-tickets-nav" id="geodir-manage-tickets-nav<?php echo esc_attr( $id ); ?>" role="tablist">
						<?php foreach ( $manage_tickets_nav as $_id => $data ) : ?>
							<li class="nav-item" role="presentation">
								<button
									class="btn py-2 nav-link shadow-none geodir-manage-tickets-tab-<?php echo ( 'types' === $_id ? 'types active' : sanitize_html_class( $_id ) ); ?>"
									id="geodir-manage-tickets-tab-<?php echo esc_attr( $_id ); ?><?php echo esc_attr( $id ); ?>"
									data-<?php echo ( $aui_bs5 ? 'bs-' : '' ); ?>toggle="tab"
									data-<?php echo ( $aui_bs5 ? 'bs-' : '' ); ?>target="#geodir-manage-tickets-tab-content-<?php echo esc_attr( $_id ); ?><?php echo esc_attr( $id ); ?>"
									type="button"
									role="tab"
									aria-controls="geodir-manage-tickets-tab-content-<?php echo esc_attr( $_id ); ?><?php echo esc_attr( $id ); ?>"
									aria-selected="<?php echo 'types' === $_id ? 'true' : 'false'; ?>"
								><?php echo ( ! empty( $data['fa-icon'] ) ? '<i class="' . esc_attr( $data['fa-icon'] ) . '" aria-hidden="true"></i> ' : '' ); ?><?php echo esc_html( $data['label'] ); ?></button>
							</li>
						<?php endforeach; ?>
					</ul>

					<div class="tab-content geodir-manage-tickets-content geodir-event-is-<?php echo empty( $has_schedule ) ? 'expired' : 'upcoming' ?>" id="geodir-manage-tickets-content<?php echo esc_attr( $id ); ?>">
						<?php foreach ( $manage_tickets_nav as $_id => $data ) : ?>
							<div
								class="mt-3 tab-pane fade geodir-manage-tickets-tab-content-<?php echo 'types' === $_id ? 'types show active' : sanitize_html_class( $_id ); ?>"
								id="geodir-manage-tickets-tab-content-<?php echo esc_attr( $_id ); ?><?php echo esc_attr( $id ); ?>"
								role="tabpanel"
								aria-labelledby="geodir-manage-tickets-tab-<?php echo esc_attr( $_id ); ?><?php echo esc_attr( $id ); ?>"
							><?php

								if ( empty( $data['content'] ) ) {
									include plugin_dir_path( __FILE__ ) . "$_id.php";
								} else {
									echo wp_kses_post( $data['content'] );
								}

							?></div>
						<?php endforeach; ?>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
<style>
	#geodir-manage-tickets-content<?php echo esc_attr( $id ); ?>.geodir-event-is-expired .gp-hide-if-expired {
		display: none !important;
	}
</style>
