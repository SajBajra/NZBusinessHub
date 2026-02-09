<?php
/**
 * Search results – custom frontend (no BlockStrap).
 *
 * @package Directory
 */

require_once get_stylesheet_directory() . '/includes/custom-frontend-doc-start.php';
get_header( 'custom' );
?>
<main class="custom-frontend-main" id="main">
	<div class="custom-frontend-content">
		<h1 class="page-title"><?php printf( esc_html__( 'Search results for: %s', 'directory' ), '<span>' . get_search_query() . '</span>' ); ?></h1>
		<?php
		if ( have_posts() ) :
			while ( have_posts() ) :
				the_post();
				?>
				<article id="post-<?php the_ID(); ?>" <?php post_class(); ?> style="margin-bottom:2rem;padding-bottom:2rem;border-bottom:1px solid #e5e7eb;">
					<h2 class="entry-title" style="font-size:1.35rem;margin:0 0 0.5rem;">
						<a href="<?php the_permalink(); ?>" style="color:inherit;text-decoration:none;"><?php the_title(); ?></a>
					</h2>
					<div class="entry-summary"><?php the_excerpt(); ?></div>
					<a href="<?php the_permalink(); ?>"><?php esc_html_e( 'Read more', 'directory' ); ?> &rarr;</a>
				</article>
				<?php
			endwhile;
			the_posts_pagination();
		else :
			?>
			<p><?php esc_html_e( 'No results found.', 'directory' ); ?></p>
		<?php endif; ?>
	</div>
</main>
<?php
get_footer( 'custom' );
require_once get_stylesheet_directory() . '/includes/custom-frontend-doc-end.php';
