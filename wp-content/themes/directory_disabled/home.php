<?php
/**
 * Blog posts listing – custom frontend: breadcrumb, title, cards (image, author, title, excerpt), pagination.
 *
 * @package Directory
 */

require_once get_stylesheet_directory() . '/includes/custom-frontend-doc-start.php';
get_header( 'custom' );

// When "Blog" is a Page (slug blog) rather than Settings > Reading "Posts page", run the blog query.
if ( is_page( 'blog' ) ) {
	global $wp_query;
	$wp_query = new WP_Query( array(
		'post_type'      => 'post',
		'post_status'    => 'publish',
		'posts_per_page' => get_option( 'posts_per_page' ),
		'paged'          => max( 1, get_query_var( 'paged' ) ),
	) );
}

$blog_breadcrumb_home = function_exists( 'directory_relative_url' ) ? directory_relative_url( home_url( '/' ) ) : home_url( '/' );
?>
<main class="custom-frontend-main cf-blog-archive" id="main">
	<div class="custom-frontend-content cf-blog-archive-inner">
		<nav class="cf-blog-breadcrumb" aria-label="<?php esc_attr_e( 'Breadcrumb', 'directory' ); ?>">
			<a href="<?php echo esc_url( $blog_breadcrumb_home ); ?>"><?php esc_html_e( 'Home', 'directory' ); ?></a>
			<span class="cf-blog-breadcrumb-sep" aria-hidden="true"><?php echo esc_html( '›' ); ?></span>
			<span class="cf-blog-breadcrumb-current"><?php esc_html_e( 'Blog', 'directory' ); ?></span>
		</nav>
		<h1 class="cf-blog-title"><?php esc_html_e( 'Blogs', 'directory' ); ?></h1>

		<?php if ( have_posts() ) : ?>
			<div class="cf-blog-cards">
				<?php
				while ( have_posts() ) :
					the_post();
					$post_id   = get_the_ID();
					$thumb    = get_the_post_thumbnail_url( $post_id, 'medium_large' );
					$author   = get_the_author();
					$permalink = get_the_permalink();
					$rel_permalink = function_exists( 'directory_relative_url' ) ? directory_relative_url( $permalink ) : $permalink;
					?>
					<article id="post-<?php echo esc_attr( $post_id ); ?>" <?php post_class( 'cf-blog-card' ); ?>>
						<a href="<?php echo esc_url( $rel_permalink ); ?>" class="cf-blog-card-link">
							<div class="cf-blog-card-image-wrap">
								<?php if ( $thumb ) : ?>
									<img src="<?php echo esc_url( $thumb ); ?>" alt="" class="cf-blog-card-image" loading="lazy" />
								<?php else : ?>
									<div class="cf-blog-card-image-placeholder" aria-hidden="true"></div>
								<?php endif; ?>
							</div>
							<div class="cf-blog-card-body">
								<p class="cf-blog-card-author"><?php echo esc_html( $author ); ?></p>
								<h2 class="cf-blog-card-title"><?php the_title(); ?></h2>
								<p class="cf-blog-card-excerpt"><?php echo esc_html( wp_trim_words( get_the_excerpt(), 18 ) ); ?></p>
							</div>
						</a>
					</article>
				<?php endwhile; ?>
			</div>

			<?php
			$pagination = get_the_posts_pagination(
				array(
					'mid_size'  => 1,
					'prev_text' => '&larr; ' . __( 'Previous', 'directory' ),
					'next_text' => __( 'Next', 'directory' ) . ' &rarr;',
					'class'     => 'cf-blog-pagination',
				)
			);
			if ( $pagination ) {
				echo '<nav class="cf-blog-pagination-nav" aria-label="' . esc_attr__( 'Posts navigation', 'directory' ) . '">' . $pagination . '</nav>';
			}
			?>
		<?php else : ?>
			<p class="cf-blog-empty"><?php esc_html_e( 'No posts yet.', 'directory' ); ?></p>
		<?php endif; ?>
	</div>
</main>
<?php
get_footer( 'custom' );
require_once get_stylesheet_directory() . '/includes/custom-frontend-doc-end.php';
