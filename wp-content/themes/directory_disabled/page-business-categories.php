<?php
/**
 * Template Name: Business Categories (Standalone)
 * Uses custom header/footer (same design as rest of site).
 *
 * @package Directory
 */

if ( ! function_exists( 'directory_business_categories_page_content' ) ) {
	get_header();
	echo '<main class="site-main"><p>' . esc_html__( 'Business Categories content is not available.', 'directory' ) . '</p></main>';
	get_footer();
	return;
}

require_once get_stylesheet_directory() . '/includes/custom-frontend-doc-start.php';
get_header( 'custom' );
?>
<main class="custom-frontend-main standalone-bc-main" id="main">
	<?php
	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo directory_business_categories_page_content();
	?>
</main>
<?php
get_footer( 'custom' );
require_once get_stylesheet_directory() . '/includes/custom-frontend-doc-end.php';
