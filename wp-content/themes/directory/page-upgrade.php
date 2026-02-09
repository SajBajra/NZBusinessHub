<?php
/**
 * Upgrade page – Free vs Premium listing plans.
 *
 * Uses the [directory_listing_plan_table] shortcode for the plan cards,
 * wrapped in the custom frontend header/footer layout.
 *
 * @package Directory
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once get_stylesheet_directory() . '/includes/custom-frontend-doc-start.php';
get_header( 'custom' );
?>

<main class="custom-frontend-main" id="main">
	<div class="cf-plans-shell">
		<?php
		// Render the plans layout (two cards, Free vs Premium).
		echo do_shortcode( '[directory_listing_plan_table]' );
		?>
	</div>
</main>

<?php
get_footer( 'custom' );
require_once get_stylesheet_directory() . '/includes/custom-frontend-doc-end.php';

