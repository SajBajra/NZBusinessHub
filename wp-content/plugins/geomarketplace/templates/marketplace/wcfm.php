<?php
/**
 * WCFM - WooCommerce Frontend Manager: Vendor Product GeoDirectory tab
 *
 * This template can be overridden by copying it to yourtheme/geodirectory/marketplace/wcfm.php.
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

global $WCFM;

?>
<!-- collapsible 11.5 - GeoDirectory -->
<div class="page_collapsible products_manage_geodirectory_listing simple variable grouped external booking" id="wcfm_products_manage_form_geodirectory_listing_head"><label class="wcfmfa fa-globe"></label><?php _e( 'GeoDirectory', 'geomarketplace' )?><span></span></div>
<div class="wcfm-container simple variable external grouped booking">
	<div id="wwcfm_products_manage_form_geodirectory_listing_expander" class="wcfm-content">
		<div id="geodir_marketplace_tab">
		<?php 
		if ( ! empty( $vendor_options ) ) {
			$WCFM->wcfm_fields->wcfm_generate_form_field( array( "gdmp_vendor_id" => array( 'label' => esc_html__( 'Vendor', 'geomarketplace' ), 'desc' => __( 'Choose the vendor to fetch the listings.', 'geomarketplace' ), 'type' => 'select', 'options' => $vendor_options, 'attributes' => array( 'style' => 'width: 60%;' ), 'class' => 'wcfm-select', 'label_class' => 'wcfm_title', 'value' => $vendor_id ) ) );
		}

		$WCFM->wcfm_fields->wcfm_generate_form_field( array( "gdmp_post_id" => array( 'label' => esc_html__( 'Linked Listing', 'geomarketplace' ), 'desc' => __( 'Choose the listing to link this product.', 'geomarketplace' ), 'type' => 'select', 'options' => $vendor_post_options, 'attributes' => array( 'style' => 'width: 60%;' ), 'class' => 'wcfm-select', 'label_class' => 'wcfm_title', 'value' => $gd_post_id ) ) );
		?>
		<input type="hidden" name="gdmp_product_id" value="<?php echo absint( $product_id ); ?>"/>
		<input type="hidden" name="gdmp_nonce" value="<?php echo esc_attr( wp_create_nonce( 'geodir_marketplace_meta' ) ); ?>"/>
		</div>
	</div>
	<?php echo $script; ?>
</div>
<!-- end collapsible -->
<div class="wcfm_clearfix"></div>
