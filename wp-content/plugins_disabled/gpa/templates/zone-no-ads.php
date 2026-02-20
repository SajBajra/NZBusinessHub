<?php
/**
 * Contains the zone no ads template.
 *
 * You can override this template by copying it to your-theme/gpa/zone-no-ads.php
 *
 * @var Adv_Zone_Template $zone The zone to display.
 */

defined( 'ABSPATH' ) || exit;

$link_position    = (int) $zone->zone->get( 'link_position' );
$adverisement_url = adv_get_zone_advertisement_url( $zone->get_id() );

?>

<?php if ( $adverisement_url && 0 !== $link_position ) : ?>
	<a href="<?php echo esc_url( $adverisement_url ); ?>" class="text-dark d-block text-decoration-none adv-shadow-on-hover">
		<div class="d-flex align-items-center justify-content-center bg-light py-5 px-2"><?php esc_html_e( 'Advertise Here', 'advertising' ); ?></div>
	</a>
<?php endif; ?>
