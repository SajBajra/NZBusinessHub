<?php
/**
 * Contains the zone ads template.
 *
 * You can override this template by copying it to your-theme/gpa/zone-ads.php
 *
 * @var Adv_Zone_Template $zone The zone to display.
 */

defined( 'ABSPATH' ) || exit;

// Get ads belonging to this zone.
$ads = $zone->get_ads();

?>

<?php if ( empty( $ads ) ) : ?>
	<?php adv_get_template( 'zone-no-ads.php', array( 'zone' => $zone ) ); ?>
<?php else : ?>
	<div class="<?php echo $zone->zone->get( 'display_grid' ) ? 'd-flex flex-column flex-sm-row flex-wrap' : ''; ?>">
		<?php foreach ( $ads as $ad ) : ?>
			<?php adv_get_template( 'zone-ad.php', array( 'zone' => $zone, 'ad' => new Adv_Ad( $ad ) ) ); ?>
		<?php endforeach; ?>
	</div>
<?php endif; ?>
