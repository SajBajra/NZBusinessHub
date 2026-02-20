<?php
/**
 * Contains the ad template.
 *
 * You can override this template by copying it to your-theme/gpa/ad=code.php
 *
 * @var Adv_Ad_Template $ad The ad to display.
 */

defined( 'ABSPATH' ) || exit;

echo $ad->ad->get( 'code' );
