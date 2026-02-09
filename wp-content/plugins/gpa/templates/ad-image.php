<?php
/**
 * Contains the ad template.
 *
 * You can override this template by copying it to your-theme/gpa/ad=code.php
 *
 * @var Adv_Ad_Template $ad The ad to display.
 */

defined( 'ABSPATH' ) || exit;

$image = $ad->ad->get( 'image' );

if ( empty( $image ) ) {
    adv_get_template( 'placeholder-image.php', array( 'ad' => $ad ) );
    return;
}

?>
<img src="<?php echo esc_url( $image ); ?>" alt="<?php echo esc_attr( $ad->get_title() ); ?>" class="w-100 h-100">
