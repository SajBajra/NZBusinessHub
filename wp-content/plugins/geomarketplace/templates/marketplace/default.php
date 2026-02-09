<?php
/**
 * Vendor Product GeoDirectory tab
 *
 * This template can be overridden by copying it to yourtheme/geodirectory/marketplace/default.php.
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

global $aui_bs5;
?>
<div id="geodir_marketplace_tab">
	<?php if ( ! empty( $vendor_options ) ) { ?>
	<div class="<?php echo ( $aui_bs5 ? 'mb-3' : 'form-group' ); ?>">
		<label class="control-label col-sm-3 col-md-3" for="gdmp_vendor_id"><abbr title="<?php echo esc_attr__( 'Vendor', 'geomarketplace' ); ?>">
			<?php echo esc_html__( 'Vendor', 'geomarketplace' ); ?></abbr>
			<span class="img_tip" data-desc="<?php esc_html_e( 'Choose the vendor to fetch the listings.', 'geomarketplace' ); ?>"></span>
		</label>
		<div class="col-md-6 col-sm-9">
			<select id="gdmp_vendor_id" name="gdmp_vendor_id" class="form-control">
				<?php foreach ( $vendor_options as $value => $label ) { ?>
				<option value="<?php echo esc_attr( $value ); ?>"<?php selected( $value == $vendor_id, true ); ?>><?php echo esc_attr( $label ); ?></option>
				<?php } ?>
			</select>
		</div>
	</div>
	<?php } ?>
	<div class="<?php echo ( $aui_bs5 ? 'mb-3' : 'form-group' ); ?>">
		<label class="control-label col-sm-3 col-md-3" for="gdmp_post_id"><abbr title="<?php echo esc_attr__( 'Linked Listing', 'geomarketplace' ); ?>">
			<?php echo esc_html__( 'Linked Listing', 'geomarketplace' ); ?></abbr>
			<span class="img_tip" data-desc="<?php esc_html_e( 'Choose the listing to link this product.', 'geomarketplace' ); ?>"></span>
		</label>
		<div class="col-md-6 col-sm-9">
			<select id="gdmp_post_id" name="gdmp_post_id" class="form-control">
				<?php foreach ( $vendor_post_options as $value => $label ) { ?>
				<option value="<?php echo esc_attr( $value ); ?>"<?php selected( $value == $gd_post_id, true ); ?>><?php echo esc_attr( $label ); ?></option>
				<?php } ?>
			</select>
			<input type="hidden" name="gdmp_product_id" value="<?php echo absint( $product_id ); ?>"/>
			<input type="hidden" name="gdmp_nonce" value="<?php echo esc_attr( wp_create_nonce( 'geodir_marketplace_meta' ) ); ?>"/>
		</div>
	</div>
	<?php echo $script; ?>
</div>
