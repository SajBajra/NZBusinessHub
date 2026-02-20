<?php
/**
 * Dokan: Vendor Product GeoDirectory tab
 *
 * This template can be overridden by copying it to yourtheme/geodirectory/marketplace/dokan.php.
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
<div class="dokan-geodirectory dokan-edit-row dokan-clearfix" id="geodir_marketplace_tab">
	<div class="dokan-section-heading" data-togglehandler="dokan_geodirectory">
		<h2><i class="fa fa-globe" aria-hidden="true"></i> <?php esc_html_e( 'GeoDirectory', 'geomarketplace' ); ?></h2>
		<p><?php esc_html_e( 'Link your product with GeoDirectory listing.', 'geomarketplace' ); ?></p>
		<a href="#" class="dokan-section-toggle"><i class="fas fa-sort-down fa-flip-vertical" aria-hidden="true"></i></a>
		<div class="dokan-clearfix"></div>
	</div>

	<div class="dokan-section-content">
		<?php if ( ! empty( $vendor_options ) ) { ?>
		<div class="dokan-form-group content-half-part">
			<label for="gdmp_vendor_id" class="form-label"><?php esc_html_e( 'Vendor', 'geomarketplace' ); ?></label>
			<select id="gdmp_vendor_id" class="dokan-form-control" name="gdmp_vendor_id">
				<?php foreach ( $vendor_options as $value => $label ) { ?>
					<option value="<?php echo esc_attr( $value ); ?>"<?php selected( $value == $vendor_id, true ); ?>><?php echo esc_html( $label ); ?></option>
				<?php } ?>
			</select>
		</div>
		<?php } ?>

		<div class="dokan-form-group content-half-part">
			<label for="gdmp_post_id" class="form-label"><?php esc_html_e( 'Linked Listing', 'geomarketplace' ); ?></label>
			<select id="gdmp_post_id" class="dokan-form-control" name="gdmp_post_id">
				<?php foreach ( $vendor_post_options as $value => $label ) { ?>
					<option value="<?php echo esc_attr( $value ); ?>"<?php selected( $value == $gd_post_id, true ); ?>><?php echo esc_html( $label ); ?></option>
				<?php } ?>
			</select>
			<input type="hidden" name="gdmp_product_id" value="<?php echo absint( $product_id ); ?>"/>
			<input type="hidden" name="gdmp_nonce" value="<?php echo esc_attr( wp_create_nonce( 'geodir_marketplace_meta' ) ); ?>"/>
		</div>
	</div>
	<?php echo $script; ?>
</div><!-- .dokan-geodirectory -->
