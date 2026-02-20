<?php
/**
 * Contains the ad template.
 *
 * You can override this template by copying it to your-theme/gpa/ad=code.php
 *
 * @var Adv_Ad_Template $ad The ad to display.
 */

defined( 'ABSPATH' ) || exit;

printf(
    '<p class="form-text d-block small">%s</p>',
    esc_html( substr( wp_unslash( $ad->ad->get( 'description' ) ), 0, 120 ) )
);
