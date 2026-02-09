<?php

/**
 * Ad stats
 *
 * Display the ad stats meta box.
 *
 */

defined( 'ABSPATH' ) || exit;

/**
 * Adv_Meta_Box_Ad_Stats Class.
 */
class Adv_Meta_Box_Ad_Stats {

    /**
	 * Output the metabox.
	 *
	 * @param WP_Post $post
	 */
    public static function output( $post ) {
		global $aui_bs5;

        do_action( 'adv_ad_stats_meta_box_top', $post );
		?>

		<div class="adv-stats bsui">
			<div class="row no-gutters">
      <div class="col-6 mb-3"><strong><?php esc_html_e( 'CTR Rate:', 'advertising' ); ?></strong></div>
				<div class="col-6 mb-3"><?php echo sanitize_text_field( adv_ad_ctr( $post->ID, true ) ); ?></div>
				<div class="col-6 mb-3"><strong><?php esc_html_e( 'Clicks:', 'advertising' ); ?></strong></div>
				<div class="col-6 mb-3"><?php echo sanitize_text_field( adv_ad_clicks( $post->ID, true ) ); ?></div>
				<div class="col-6 mb-3"><strong><?php esc_html_e( 'Impressions:', 'advertising' ); ?></strong></div>
				<div class="col-6 mb-3"><?php echo sanitize_text_field( adv_ad_impressions( $post->ID, true ) ); ?></div>
			</div>
		</div>

		<?php
		do_action( 'adv_ad_stats_meta_box_bottom', $post );

    }

}