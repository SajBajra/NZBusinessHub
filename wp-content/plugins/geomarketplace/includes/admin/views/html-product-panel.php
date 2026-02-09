<?php
/**
 * The GeoDirectory listing selection tab HTML in the product tabs
 *
 * @package GeoDir_Marketplace
 * @author AyeCode Ltd
 * @since 2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<div id="geodir_marketplace_tab" class="panel woocommerce_options_panel hidden">
	<div class="options_group">
		<?php 
		if ( ! empty( $vendor_options ) ) {
			woocommerce_wp_select(
				array(
					'id'            => 'gdmp_vendor_id',
					'value'         => $vendor_id,
					'wrapper_class' => 'gdmp_vendor_id_field',
					'label'         => __( 'Vendor', 'geomarketplace' ),
					'options'       => $vendor_options,
					'desc_tip'      => true,
					'description'   => __( 'Choose the vendor to fetch the listings.', 'geomarketplace' ),
				)
			);
		}

		woocommerce_wp_select(
			array(
				'id'            => 'gdmp_post_id',
				'value'         => $gd_post_id,
				'wrapper_class' => 'gdmp_post_id_field',
				'label'         => __( 'Linked Listing', 'geomarketplace' ),
				'options'       => $vendor_post_options,
				'desc_tip'      => true,
				'description'   => __( 'Choose the listing to link this product.', 'geomarketplace' ),
			)
		);
		?>
		<input type="hidden" name="gdmp_product_id" value="<?php echo absint( $product_id ); ?>"/>
		<input type="hidden" name="gdmp_nonce" value="<?php echo esc_attr( wp_create_nonce( 'geodir_marketplace_meta_admin' ) ); ?>"/>
		<?php echo $script; ?>
	</div>
</div>
