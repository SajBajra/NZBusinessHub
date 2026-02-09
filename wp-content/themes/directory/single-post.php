<?php
/**
 * Single blog post – new custom layout.
 *
 * @package Directory
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once get_stylesheet_directory() . '/includes/custom-frontend-doc-start.php';
get_header( 'custom' );

$blog_home = function_exists( 'directory_relative_url' ) ? directory_relative_url( home_url( '/' ) ) : home_url( '/' );
$blog_page = get_permalink( get_option( 'page_for_posts' ) );
if ( ! $blog_page ) {
	$blog_page = $blog_home;
} elseif ( function_exists( 'directory_relative_url' ) ) {
	$blog_page = directory_relative_url( $blog_page );
}
?>

<main class="custom-frontend-main cf-blog-single-main" id="main">
	<?php
	while ( have_posts() ) :
		the_post();
		$pid   = get_the_ID();
		$thumb = get_the_post_thumbnail_url( $pid, 'large' );
		$cats  = get_the_category( $pid );
		?>

		<section class="cf-blog-single-hero">
			<div class="cf-blog-single-hero-inner">
				<nav class="cf-blog-single-breadcrumb" aria-label="<?php esc_attr_e( 'Breadcrumb', 'directory' ); ?>">
					<a href="<?php echo esc_url( $blog_home ); ?>"><?php esc_html_e( 'Home', 'directory' ); ?></a>
					<span class="cf-blog-single-breadcrumb-sep" aria-hidden="true">›</span>
					<a href="<?php echo esc_url( $blog_page ); ?>"><?php esc_html_e( 'Blog', 'directory' ); ?></a>
					<span class="cf-blog-single-breadcrumb-sep" aria-hidden="true">›</span>
					<span class="cf-blog-single-breadcrumb-current"><?php echo esc_html( get_the_title( $pid ) ); ?></span>
				</nav>

				<?php if ( ! empty( $cats ) && ! is_wp_error( $cats ) ) : ?>
					<div class="cf-blog-single-cats">
						<?php
						foreach ( $cats as $cat ) {
							printf(
								'<span class="cf-blog-single-cat">%s</span>',
								esc_html( $cat->name )
							);
						}
						?>
					</div>
				<?php endif; ?>

				<h1 class="cf-blog-single-title"><?php the_title(); ?></h1>

				<div class="cf-blog-single-meta">
					<span class="cf-blog-single-meta-item">
						<?php echo esc_html( get_the_date( '', $pid ) ); ?>
					</span>
					<span class="cf-blog-single-meta-sep">•</span>
					<span class="cf-blog-single-meta-item">
						<?php
						/* translators: %s: author name */
						printf( esc_html__( 'By %s', 'directory' ), esc_html( get_the_author() ) );
						?>
					</span>
				</div>
			</div>

			<?php if ( $thumb ) : ?>
				<div class="cf-blog-single-hero-image">
					<img src="<?php echo esc_url( $thumb ); ?>" alt="" class="cf-blog-single-hero-img" />
				</div>
			<?php endif; ?>
		</section>

		<section class="cf-blog-single-body">
			<article id="post-<?php echo esc_attr( $pid ); ?>" <?php post_class( 'cf-blog-single-article' ); ?>>
				<div class="cf-blog-single-content entry-content">
					<?php the_content(); ?>
				</div>

				<?php
				wp_link_pages(
					array(
						'before' => '<nav class="cf-single-pages" aria-label="' . esc_attr__( 'Post pages', 'directory' ) . '"><p class="cf-single-pages-title">' . __( 'Pages:', 'directory' ) . '</p><ul class="cf-single-pages-list">',
						'after'  => '</ul></nav>',
					)
				);
				?>

				<footer class="cf-blog-single-footer">
					<a href="<?php echo esc_url( $blog_page ); ?>" class="cf-single-back">← <?php esc_html_e( 'Back to Blog', 'directory' ); ?></a>
				</footer>
			</article>
		</section>

		<?php
	endwhile;
	?>
</main>

<?php
get_footer( 'custom' );
require_once get_stylesheet_directory() . '/includes/custom-frontend-doc-end.php';

