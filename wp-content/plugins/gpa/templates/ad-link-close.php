<?php
/**
 * Contains the ad link close template.
 *
 * You can override this template by copying it to your-theme/gpa/ad-link-close.php
 *
 * @var Adv_Ad_Template $ad The ad to display.
 */

defined( 'ABSPATH' ) || exit;

?>

<?php if ( $ad->wrap_with_link() ) : ?>
	</a>
<?php endif; ?>
