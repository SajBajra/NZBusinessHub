<?php
/**
 * Contains the ad link open template.
 *
 * You can override this template by copying it to your-theme/gpa/ad-link-open.php
 *
 * @var Adv_Ad_Template $ad The ad to display.
 */

defined( 'ABSPATH' ) || exit;

?>

<?php if ( $ad->wrap_with_link() ) : ?>
	<a
		href="<?php echo esc_url( $ad->get_url( false ) ); ?>"
		<?php echo $ad->ad->get( 'new_tab' ) ? 'target="_blank"' : ''; ?>
		class="d-block text-decoration-none shadow-none w-100 h-100 text-dark"
	>
<?php endif; ?>
