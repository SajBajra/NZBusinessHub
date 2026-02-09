<?php
/**
 * Ad Invoice meta box.
 *
 * Display the ad Invoice meta box.
 *
 * @package advertising
 */

defined( 'ABSPATH' ) || exit;

/**
 * Adv_Meta_Box_Ad_Invoice Class.
 */
class Adv_Meta_Box_Ad_Invoice {

	/**
	 * Output the metabox.
	 *
	 * @param WP_Post $post post object.
	 */
	public static function output( $post ) {
		do_action( 'adv_ad_invoice_meta_box_top', $post );

		$invoice_id = adv_ad_get_meta( $post->ID, 'invoicing_invoice_id' );
		$invoice    = function_exists('wpinv_get_invoice') ? wpinv_get_invoice( $invoice_id ) : '';
		$invoices   = empty( $invoice ) ? array() : array( $invoice->get_id() => $invoice );

		if ( ! empty( $invoice ) && $invoice->exists() ) {

			$children = get_posts(
				array(
					'post_type'     => 'wpi_invoice',
					'post_status'   => array_keys( wpinv_get_invoice_statuses() ),
					'fields'        => 'ids',
					'numberposts'   => 100,
					'post_parent'   => $invoice->get_id()
				)
			);

			foreach ( $children as $child ) {
				$invoices[ $child ] = wpinv_get_invoice( $child );
			}
		}

		$invoices   = apply_filters( 'ads_get_ad_invoices', $invoices, $post->ID );

		if ( empty( $invoices ) ) {
			self::output_no_invoices();
		} else {
			self::output_invoices( $invoices );
		}

		do_action( 'adv_ad_invoice_meta_box_bottom', $post );

	}

	public static function output_no_invoices() {
		global $aui_bs5;

		echo '<div class="bsui">';

		echo aui()->alert(
			array(
				'content' => __( 'This ad is not linked to any invoice. You can manually link an existing invoice or automatically generate a new invoice after you save the ad.', 'advertising' )
			)
		);

		?>

    <div class="mb-3 row" style="max-width: 600px;">
			<label class="col-sm-3 col-form-label" for="adv_invoice_save_action"><span><?php _e( 'Invoice Action', 'advertising' )?></span></label>
			<div class="col-sm-8">
				<?php
					echo aui()->select(
						array(
							'id'            => 'adv_invoice_save_action',
							'label'         => __( 'Invoice Action', 'advertising' ),
							'name'          => 'adv_invoice_save_action',
							'value'         => 'none',
							'placeholder'   => __( 'Select an action', 'advertising' ),
							'help_text'     => __( 'Optional. What should happen after you save this ad?', 'advertising' ),
							'options'       => array(
								'none'     => __( 'Do nothing', 'advertising' ),
								'generate' => __( 'Automatically generate a new invoice', 'advertising' ),
								'link'     => __( 'Link an existing invoice', 'advertising' ),
							),
						)
					);
				?>
				<script>
					jQuery('document').ready(function(){
						jQuery( '#adv_invoice_save_action' ).on( 'change', function( e ) {
							if ( 'link' == jQuery(this).val()) {
								jQuery( '.adv-linked-invoice-wrapper' ).show();
							} else {
								jQuery( '.adv-linked-invoice-wrapper' ).hide();
							}
						} )
					})
				</script>
			</div>
		</div>

    <div class="mb-3 row adv-linked-invoice-wrapper" style="max-width: 600px; display: none;">
			<label class="col-sm-3 col-form-label" for="adv_linked_invoice"><span><?php _e( 'Link Invoice', 'advertising' )?></span></label>
			<div class="col-sm-8">
				<?php
					echo aui()->input(
						array(
							'type'          => 'text',
							'id'            => 'adv_linked_invoice',
							'label'         => __( 'Link Invoice', 'advertising' ),
							'name'          => 'adv_linked_invoice',
							'value'         => '',
							'placeholder'   => __( 'Invoice id or number', 'advertising' ),
							'help_text'     => __( 'Enter the ID or Number of an existing invoice to link it to this ad.', 'advertising' ),
						)
					);
				?>
			</div>
		</div>

		<?php

		echo '</div>';
	}

	/**
	 *
	 * @param WPInv_Invoice[] $invoices
	 */
	public static function output_invoices( $invoices ) {

		?>
		<table class="bsui wp-list-table widefat fixed striped posts gd-listing-invoices">
			<thead>
				<th class="column-wpi_number"><?php esc_html_e( 'Invoice', 'advertising' ); ?></th>
				<th class="column-wpi_customer"><?php esc_html_e( 'Created', 'advertising' ); ?></th>
				<th class="column-wpi_amount"><?php esc_html_e( 'Paid', 'advertising' ); ?></th>
				<th class="column-wpi_invoice_date"><?php esc_html_e( 'Status', 'advertising' ); ?></th>
				<th class="column-wpi_payment_date"><?php esc_html_e( 'Total', 'advertising' ); ?></th>
			</thead>
			<tbody>
				<?php
			
					/** @var WPInv_Invoice $invoice */
					foreach ( $invoices as $invoice ) :

						$edit_link = get_edit_post_link( $invoice->get_id() );
						$value     = sprintf(
							'<a title="%s" href="%s">%s</a>',
							esc_attr( 'View Invoice Details', 'advertising' ),
							esc_url( $edit_link ),
							$invoice->get_number()
						);
						$status         = $invoice->get_status_label_html();
						$invoice_date   = getpaid_format_date_value( $invoice->get_date_created() );
						$date_completed = getpaid_format_date_value( $invoice->get_completed_date() );
						$total          = wpinv_price( $invoice->get_total(), $invoice->get_currency() );
				?>
				<tr>
					<td class="column-wpi_number"><?php echo wp_kses_post( $value ); ?></td>
					<td class="column-wpi_invoice_date"><?php echo esc_html( $invoice_date ); ?></td>
					<td class="column-wpi_payment_date"><?php echo esc_html( $date_completed ); ?></td>
					<td class="column-wpi_status"><?php echo wp_kses_post( $status ); ?></td>
					<td class="column-wpi_amount"><?php echo wp_kses_post( $total ); ?></td>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php
	
	}

}
