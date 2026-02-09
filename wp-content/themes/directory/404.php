<?php
/**
 * 404 Not Found – custom frontend (no BlockStrap).
 *
 * @package Directory
 */

require_once get_stylesheet_directory() . '/includes/custom-frontend-doc-start.php';
get_header( 'custom' );
?>
<main class="custom-frontend-main" id="main">
	<div class="custom-frontend-content" style="text-align:center;padding:4rem 1.5rem;">
		<h1 class="entry-title" style="font-size:2rem;margin-bottom:0.5rem;"><?php esc_html_e( 'Page not found', 'directory' ); ?></h1>
		<p style="color:#6b7280;margin-bottom:1.5rem;"><?php esc_html_e( 'The page you are looking for does not exist or has been moved.', 'directory' ); ?></p>
		<a href="<?php echo esc_url( home_url( '/' ) ); ?>" style="display:inline-block;padding:0.5rem 1.25rem;background:#2563eb;color:#fff;text-decoration:none;border-radius:0.5rem;font-weight:600;"><?php esc_html_e( 'Back to home', 'directory' ); ?></a>
	</div>
</main>
<?php
get_footer( 'custom' );
require_once get_stylesheet_directory() . '/includes/custom-frontend-doc-end.php';
