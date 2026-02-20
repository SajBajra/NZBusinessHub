<?php defined( 'ABSPATH' ) || exit; ?>

<?php
	global $wpdb;
	$table    = $wpdb->prefix . 'geodir_tickets';

	$t_types  = $wpdb->get_results(
		$wpdb->prepare(
            "SELECT type, COUNT(id) as qty, SUM(seller_price) as total FROM $table WHERE event_id=%d GROUP BY type ORDER BY total DESC;",
            (int) $post_id
        )
	);

	$t_totals = $wpdb->get_row(
		$wpdb->prepare(
            "SELECT SUM(seller_price) as earnings, SUM(price) as total, SUM(site_commision) as fees FROM $table WHERE event_id=%d;",
            (int) $post_id
        )
	);

	$chart_1  = array(
		'labels'   => array_map( 'get_the_title', wp_list_pluck( $t_types, 'type' ) ),
		'datasets' => array(
			array(
				'label'           => __( 'Earnings', 'geodir-tickets' ),
    			'data'            => array(),
				'backgroundColor' => array(),
			),
			array(
				'label'           => __( 'Sales', 'geodir-tickets' ),
    			'data'            => array(),
				'backgroundColor' => array(),
			)
		)
	);

	$colors = array(
		'#f44336', '#e91e63', '#673ab7', '#9c27b0', '#3f51b5', '#2196f3', '#03a9f4',
		'#00bcd4', '#009688', '#4caf50', '#8bc34a', '#cddc39', '#ffeb3b', '#ffc107',
		'#ff9800', '#ff5722', '#795548', '#607d8b'
	);

	foreach ( $t_types as $index => $t_type ) {
		$chart_1['datasets'][0]['data'][]            = wpinv_round_amount( $t_type->total );
		$chart_1['datasets'][0]['backgroundColor'][] = $colors[ $index ];
		$chart_1['datasets'][1]['data'][]            = $t_type->qty;
		$chart_1['datasets'][1]['backgroundColor'][] = $colors[ $index ];
	}

?>
<div class="row">
	<div class="col-12 col-sm-6">

		<canvas class="geodir-tickets-pie-chart" data-points="<?php echo esc_attr( wp_json_encode( $chart_1 ) ); ?>" width="400" height="400"></canvas>

	</div>

	<div class="col-12 col-sm-6">

		<h4><?php _e( 'Ticket Types','geodir-tickets' );?></h4>

		<table class="table my-3 geodir-manage-ticket-types-stats">
			<thead>
				<tr>
					<th scope="col"><?php _e( 'Type', 'geodir-tickets' ); ?></th>
					<th scope="col" class="text-center"><?php _e( 'Quantity', 'geodir-tickets' ); ?></th>
					<th scope="col" class="text-center"><?php _e( 'Total', 'geodir-tickets' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $t_types as $t_type ) : ?>
					<tr>
						<th scope="row"><?php echo esc_html( get_the_title( $t_type->type ) ); ?></th>
						<td class="text-center"><?php echo absint( $t_type->qty ); ?></td>
						<td class="text-center"><?php echo wpinv_price( $t_type->total ); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>

		<h4><?php _e( 'Totals','geodir-tickets' );?></h4>

		<table class="table my-3 geodir-manage-ticket-totals-stats">
			<thead>
				<tr>
					<th scope="col"><?php _e( 'Items', 'geodir-tickets' ); ?></th>
					<th scope="col" class="text-center"><?php _e( 'Total', 'geodir-tickets' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<th scope="row"><?php _e( 'Total', 'geodir-tickets' ); ?></th>
					<td class="text-center"><?php echo wpinv_price( $t_totals->total ); ?></td>
				</tr>
				<tr>
					<th scope="row"><?php printf( __( 'Fees (%s%%)', 'geodir-tickets' ), geodir_tickets_get_commision_percentage() ); ?></th>
					<td class="text-center"><?php echo wpinv_price( $t_totals->fees ); ?></td>
				</tr>
				<tr>
					<th scope="row"><?php _e( 'Earnings', 'geodir-tickets' ); ?></th>
					<td class="text-center"><?php echo wpinv_price( $t_totals->earnings ); ?></td>
				</tr>
			</tbody>
		</table>

	</div>
</div>
