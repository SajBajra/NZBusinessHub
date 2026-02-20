<?php
/**
 * Contains the ad template.
 *
 * You can override this template by copying it to your-theme/gpa/ad.php
 *
 * @var Adv_Ad_Template $ad The ad to display.
 */

defined( 'ABSPATH' ) || exit;

?>

<div data-id="<?php echo absint( $ad->get_id() ); ?>" class="bsui adv-single id-<?php echo absint( $ad->get_id() ); ?> type-<?php echo esc_attr( sanitize_html_class( $ad->get_type() ) ); ?>">
    <div class="overflow-hidden w-100 mw-100 position-relative">
        <?php adv_get_template( 'ad-link-open.php', array( 'ad' => $ad ) ); ?>
        <?php adv_get_template( 'ad-' . $ad->ad->get( 'type' ) . '.php', array( 'ad' => $ad ) ); ?>
        <?php adv_get_template( 'ad-link-close.php', array( 'ad' => $ad ) ) ?>
    </div>
</div>
