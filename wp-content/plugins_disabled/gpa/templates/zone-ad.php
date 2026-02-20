<?php
/**
 * Contains the zone ad template.
 *
 * You can override this template by copying it to your-theme/gpa/zone-ad.php
 *
 * @var Adv_Zone_Template $zone The zone to display.
 * @var Adv_Ad $ad The ad to display.
 */

defined( 'ABSPATH' ) || exit;

$html = $ad->get_html();

if ( empty( $html ) ) {
	return;
}

$width  = esc_attr( $zone->zone->get('width') );
$height = esc_attr( $zone->zone->get('height') );

if ( is_numeric( $width ) && ! empty( $width ) ) {
	$width = absint( $width ) . 'px';
}

if ( is_numeric( $height ) && ! empty( $height ) ) {
	$height = absint( $height ) . 'px';
}

$is_grid = $zone->zone->get( 'display_grid' );

if ( ! empty( $is_grid ) ) {
	$ads_per_grid = absint( (int) $zone->zone->get( 'ads_per_grid' ) );
	$ads_per_grid = max( 1, $ads_per_grid );
	$width        = round( 100 / $ads_per_grid, 5 ) . '%';
}

$style = '';
if ( ! empty( $width ) ) {
	$style .= "width: $width;";
}

if ( ! empty( $height ) ) {
	$style .= "height: $height;";
}

$random_id = wp_unique_id( 'adv-' . $zone->get_id() . '-' . $ad->ID . '-' );
?>

<style>
	#<?php echo esc_attr( $random_id ); ?> {
		<?php echo esc_attr( $style ); ?>
	}

	@media (max-width: 575px) {
		#<?php echo esc_attr( $random_id ); ?> {
			width: 100% !important;
		}
	}
</style>
<div class="overflow-hidden" id="<?php echo esc_attr( $random_id ); ?>">
	<?php echo $html; ?>
</div>
