<?php
/**
 * Pricing Single Package
 *
 * This template can be overridden by copying it to yourtheme/geodirectory/bootstrap/loop/package.php.
 *
 * HOWEVER, on occasion GeoDirectory will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see        https://wpgeodirectory.com/documentation/article/how-tos/customizing-templates/
 * @package    GeoDir_Pricing_Manager
 * @version    2.7.12
 */

defined( 'ABSPATH' ) || exit;

global $aui_bs5;
?>
<div class="col <?php echo esc_attr( $card_class ); ?>">
	<div class="card mb-lg-0 <?php echo esc_attr( $highlight_class . ' ' . $border_class . ' ' . $card_shadow_class ); ?>">
		<div class="card-header <?php echo esc_attr( $card_header_border_class ); ?><?php echo ( $aui_bs5 ? ' bg-light' : '' ); ?>">
			<h4 class="my-0 mb-1 text-base subtitle text-center py-3 text-nowrap text-<?php echo esc_attr( $color ); ?>"><?php echo wp_kses_post( $display_name ); ?></h4>
			<p class="text-muted text-center mb-3"><span class="h2 text-dark"><?php echo wp_kses_post( $display_price ); ?></span><span class="<?php echo ( $aui_bs5 ? ' ms-2' : ' ml-2' ); ?>">/ <?php echo esc_html( $display_lifetime ); ?></span></p>
		</div>
		<div class="card-body">
			<ul class="fa-ul my-2<?php echo ( $aui_bs5 ? ' ps-0 ms-3' : '' ); ?>">
			<?php if ( ! empty( $package->features ) ) { ?>
				<?php foreach( $package->features as $feature => $data ) { $icon_class = ( ! empty( $data['icon'] ) ? str_replace( array( 'fas fa-', 'far fa-', 'fab fa-' ), '', $data['icon'] ) : '' ); ?>
				<li class="mb-3<?php echo ( $aui_bs5 ? ' ms-4' : '' ); ?><?php echo ( $icon_class ? ' ' . sanitize_html_class( 'geodir-fe-' . $icon_class ) : '' ); ?>" data-geodir-feature="<?php echo esc_attr( $feature ); ?>"><span class="fa-li text-<?php echo esc_attr( $data['color'] ); ?>"><i class="<?php echo esc_attr( $data['icon'] ); ?>"></i></span><?php echo $data['text']; ?></li>
				<?php } ?>
			<?php } ?>
			</ul>
			<div class="text-center"><a class="btn btn-<?php echo esc_attr( $color ); ?> <?php echo ( $aui_bs5 ? 'd-block' : 'btn-block' ); ?>" href="<?php echo esc_url( $package_link ); ?>"><?php _e( 'Select Plan', 'geodir_pricing' ); ?></a></div>
		</div>
	</div>
</div>