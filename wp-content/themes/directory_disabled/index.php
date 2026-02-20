<?php
/**
 * Fallback template – custom frontend (no BlockStrap).
 *
 * @package Directory
 */

require_once get_stylesheet_directory() . '/includes/custom-frontend-doc-start.php';
get_header( 'custom' );
?>
<main class="custom-frontend-main" id="main">
	<div class="custom-frontend-content">
		<?php
		if ( have_posts() ) :
			while ( have_posts() ) :
				the_post();
				?>
				<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
					<h1 class="entry-title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h1>
					<div class="entry-content"><?php the_content(); ?></div>
				</article>
				<?php
			endwhile;
		else :
			?>
			<p><?php esc_html_e( 'No content found.', 'directory' ); ?></p>
		<?php endif; ?>
	</div>
</main>
<?php
get_footer( 'custom' );
require_once get_stylesheet_directory() . '/includes/custom-frontend-doc-end.php';
