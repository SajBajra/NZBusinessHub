<?php
/**
 * Contains the zone template.
 *
 * You can override this template by copying it to your-theme/gpa/zone.php
 *
 * @var Adv_Zone_Template $zone The zone to display.
 */

defined( 'ABSPATH' ) || exit;

$ad_rotation = $zone->get_ad_rotation();
$ad_rotation_interval = (int) $zone->get_ad_rotation_interval();
$ad_rotation_interval = empty( $ad_rotation_interval ) || 0 === $ad_rotation_interval ? 60 : $ad_rotation_interval;
?>
<div data-id="<?php echo absint( $zone->get_id() ); ?>" 
    <?php if ( $ad_rotation ) : ?>
        data-adr="<?php echo absint( $ad_rotation_interval ); ?>"
    <?php endif; ?> 
    class="bsui adv-single-zone id-<?php echo absint( $zone->get_id() ); ?>">
	<div class="<?php echo esc_attr( adv_zone_wrapper_class( $zone->get_ads() && $zone->zone->get( 'width' ) ) ); ?>">
	<?php echo $zone_inner_content; ?>
	</div>
</div>