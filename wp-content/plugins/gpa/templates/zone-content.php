<?php
/**
 * Contains the zone inner content template.
 *
 * You can override this template by copying it to your-theme/gpa/zone-content.php
 *
 * @var Adv_Zone_Template $zone The zone to display.
 */

defined( 'ABSPATH' ) || exit;

$link_position           = (int) $zone->zone->get( 'link_position' );
$adverisement_url        = adv_get_zone_advertisement_url( $zone->get_id() );

if ( 0 === count( $zone->get_ads() ) ) {
	$link_position = 0;
}

if ( 'yes' === $zone->zone->get( 'show_title' ) ) : ?>
	<div class="h5 gpa-zone-title">
		<?php echo esc_html( get_the_title( $zone->get_id() ) ); ?>
	</div>
<?php endif; ?>

<?php if ( $adverisement_url && ( 2 === $link_position || 3 === $link_position ) && ! $zone->zone->is_full() ) : ?>
	<div>
		<a class="text-dark gpa-adv-url" href="<?php echo esc_url( $adverisement_url ); ?>"><?php esc_html_e( 'Advertise Here', 'advertising' ); ?></a>
	</div>
<?php endif; ?>

<?php adv_get_template( 'zone-ads.php', array( 'zone' => $zone ) ); ?>

<?php if ( $adverisement_url && ( 1 === $link_position || 3 === $link_position ) && ! $zone->zone->is_full() ) : ?>
	<div>
		<a class="text-dark gpa-adv-url" href="<?php echo esc_url( $adverisement_url ); ?>"><?php esc_html_e( 'Advertise Here', 'advertising' ); ?></a>
	</div>
<?php endif; ?>
