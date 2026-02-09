<?php
/**
 * WC Vendors Marketplace: Vendor Product GeoDirectory tab
 *
 * This template can be overridden by copying it to yourtheme/geodirectory/marketplace/wcv.php.
 *
 * HOWEVER, on occasion GeoDirectory will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see        https://docs.wpgeodirectory.com/article/346-customizing-templates/
 * @package    GeoDir_Marketplace
 * @version    2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<!-- GeoDirectory -->
<div id="geodir_marketplace_tab" class="wcv-marketplace-tab tabs-content">
	<?php 
	if ( ! empty( $vendor_options ) ) {
		WCVendors_Pro_Form_Helper::select(
			apply_filters(
				'geodir_marketplace_wcv_gdmp_vendor_id',
				array(
					'id'                => 'gdmp_vendor_id',
					'label'             => esc_attr__( 'Vendor', 'geomarketplace' ),
					'description'       => __( 'Choose the vendor to fetch the listings.', 'geomarketplace' ),
					'value'             => $vendor_id,
					'style'             => 'width: 100%;',
					'class'             => 'gdmp-vendor-id',
					'show_label'        => true,
					'desc_tip'          => true,
					'multiple'          => false,
					'options'           => $vendor_options,
				)
			)
		);
	}

	WCVendors_Pro_Form_Helper::select(
		apply_filters(
			'geodir_marketplace_wcv_gdmp_post_id',
			array(
				'id'                => 'gdmp_post_id',
				'label'             => esc_attr__( 'Linked Listing', 'geomarketplace' ),
				'description'       => __( 'Choose the listing to link this product.', 'geomarketplace' ),
				'value'             => $gd_post_id,
				'style'             => 'width: 100%;',
				'class'             => 'gdmp-post-id',
				'show_label'        => true,
				'desc_tip'          => true,
				'multiple'          => false,
				'options'           => $vendor_post_options,
			)
		)
	);
	?>
	<input type="hidden" name="gdmp_product_id" value="<?php echo absint( $product_id ); ?>"/>
	<input type="hidden" name="gdmp_nonce" value="<?php echo esc_attr( wp_create_nonce( 'geodir_marketplace_meta' ) ); ?>"/>
	<?php echo $script; ?>
</div>
