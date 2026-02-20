<?php
/**
 * Contains the ads overview template.
 *
 * You can override this template by copying it to your-theme/gpa/ads-overview.php
 *
 * @var Adv_Ad_Template $ad The ad to display.
 */

defined( 'ABSPATH' ) || exit;

$table_cols = apply_filters(
	'adv_ads_overview_table_cols',
	array(
		'title'       => __( 'Ad Title', 'advertising' ),
		'type'        => __( 'Ad Type', 'advertising' ),
		'zone'        => __( 'Zone', 'advertising' ),
		'ctr'         => __( 'CTR', 'advertising' ),
		'clicks'      => __( 'Clicks', 'advertising' ),
		'impressions' => __( 'Impressions', 'advertising' ),
		'status'      => __( 'Status', 'advertising' ),
		'action'      => __( 'Action', 'advertising' ),
	)
);

$new_ad_url = apply_filters( 'adv_ads_new_ad_url', adv_dashboard_endpoint_url( 'new-ad' ) );

?>
<div class="adv-nav-content adv-content-ads">
	<h3 class="adv-navc-title mt-3 mb-3"><?php _e( 'Ads', 'advertising' ); ?>&nbsp;<a class="btn btn-sm btn-dark adv-dashboard-new-ad-button" href="<?php echo esc_url( adv_dashboard_endpoint_url( 'new-ad' ) ); ?>"><?php _e( 'Add New', 'advertising' ); ?></a></h3>
	<p class="adv-navc-desc mb-3 text-muted small"><?php _e( 'Here you can see a list of your ads. If you want to add new ads, click on "Add a new Ad" button.', 'advertising' ); ?></p>

	<?php

		if( empty( $ads ) ) {

			_e( 'No ads found!', 'advertising' );
			echo '</div>';
			return;
		}
	?>

	<style>
		.adv-renewing,
		.adv-deleting {
			opacity: 0.3;
			cursor: progress;
		}
		.adv-col-title {
			min-width: 200px;
		}
		.adv-col-type,
		.adv-col-impressions {
			min-width: 120px;
		}
		.adv-col-zone {
			min-width: 150px;
		}
		.adv-col-ctr,
		.adv-col-status,
		.adv-col-action,
		.adv-col-clicks {
			min-width: 100px;
		}
		.adv-col-status,
		.adv-col-action {
			text-align: center;
		}
	</style>
	<div class="table-responsive">
		<table class="table table table-bordered">

			<thead>
				<tr>
					<?php foreach ( $table_cols as $col => $title ) : ?>
						<th class="adv-col-<?php echo esc_attr( $col ); ?>" data-colname="<?php echo esc_attr( $col ); ?>"><?php echo esc_html( $title ); ?></th>
					<?php endforeach; ?>
				</tr>
			</thead>

			<tbody>

				<?php foreach ( $ads as $ad_id ) : ?>
					<tr class="adv-ad-<?php echo absint( $ad_id ); ?>" data-id="<?php echo absint( $ad_id ); ?>" data-status="<?php echo adv_ad_is_active( $ad_id ) ? 'approved' : 'pending'; ?>">

						<?php foreach ( $table_cols as $col => $title ) : ?>
							<td class="adv-col-<?php echo esc_attr( $col ); ?>" data-colname="<?php echo esc_attr( $col ); ?>" data-title="<?php echo esc_attr( $title ); ?>">
								<?php

									switch ( $col ) {
										case 'title':
											$edit_url     = add_query_arg( array( 'ad' => $ad_id ), $new_ad_url );
											$package_link = adv_zone_get_meta( adv_ad_zone( $ad_id ), 'link_to_packages', true, false );

											if ( ! empty( $package_link ) ) {
												$listing = adv_ad_get_meta( $ad_id, 'listing', true );

												if ( ! empty( $listing ) && 'publish' == get_post_status( $listing ) ) {
													echo '<a class="adv-edit" href="' . esc_url( get_permalink( $listing ) ) . '" title="' . esc_attr( 'View Listing', 'advertising' ) . '">' . esc_html( get_the_title( $ad_id ) ) . '</a>';
												} else {
													echo '&nbsp;';
												}
											} else if ( 'code' === adv_ad_type( $ad_id, true ) ) {
												echo esc_html( get_the_title( $ad_id ) );
											} else {
												echo '<a class="adv-edit" href="' . esc_url( $edit_url ) . '" title="' . esc_attr( 'Edit', 'advertising' ) . '">' . esc_html( get_the_title( $ad_id ) ) . '</a>';
											}
											break;
										case 'type':
											echo esc_html( adv_ad_type( $ad_id, true ) );
											break;
										case 'zone':
											echo esc_html( adv_ad_zone( $ad_id, true) );
											break;
										case 'ctr':
											echo esc_html( adv_ad_ctr( $ad_id, true ) );
											break;
										case 'clicks':
											echo esc_html( adv_ad_clicks( $ad_id, true ) );
											break;
										case 'impressions':
											echo esc_html( adv_ad_impressions( $ad_id, true ) );
											break;
										case 'status':

											if ( adv_ad_is_active( $ad_id ) ) {
												echo '<svg xmlns="http://www.w3.org/2000/svg" fill="#28a745" height="24" viewBox="0 0 24 24" width="24"><path d="M0 0h24v24H0z" fill="none"/><path d="M9 16.2L4.8 12l-1.4 1.4L9 19 21 7l-1.4-1.4L9 16.2z"/></svg>';
											} else {
												echo '<svg xmlns="http://www.w3.org/2000/svg" fill="#dc3545" height="24" viewBox="0 0 24 24" width="24"><path d="M0 0h24v24H0z" fill="none"/><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>';
											}

											do_action( 'adv_ad_status_td', $ad_id, adv_ad_is_active( $ad_id ) );
											break;
										case 'action':
											echo '<a class="adv-delete adv-delete-ad" href="javascript:void(0)" title="' . esc_attr__( 'Delete this Ad', 'advertising' ) . '"><svg xmlns="http://www.w3.org/2000/svg" fill="#dc3545" height="24" viewBox="0 0 24 24" width="24"><path d="M0 0h24v24H0z" fill="none"/><path d="M0 0h24v24H0V0z" fill="none"/><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zm2.46-7.12l1.41-1.41L12 12.59l2.12-2.12 1.41 1.41L13.41 14l2.12 2.12-1.41 1.41L12 15.41l-2.12 2.12-1.41-1.41L10.59 14l-2.13-2.12zM15.5 4l-1-1h-5l-1 1H5v2h14V4z"/></svg></a>';

											if ( 1 == get_post_meta( $ad_id, 'adv_repay', true ) ) {
												echo '&nbsp;<a class="adv-repay-ad" href="javascript:void(0)">' . esc_html__( 'Renew Ad', 'advertising' ) . '</a>';
											}
											break;
									}
								?>
							</td>
						<?php endforeach; ?>

					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>
</div>
