<?php
/**
 * Single post – custom frontend, minimal layout.
 *
 * @package Directory
 */

require_once get_stylesheet_directory() . '/includes/custom-frontend-doc-start.php';
get_header( 'custom' );

$single_home  = function_exists( 'directory_relative_url' ) ? directory_relative_url( home_url( '/' ) ) : home_url( '/' );
$single_blog  = get_permalink( get_option( 'page_for_posts' ) );
if ( ! $single_blog ) {
	$single_blog = $single_home;
} elseif ( function_exists( 'directory_relative_url' ) ) {
	$single_blog = directory_relative_url( $single_blog );
}
?>
<main class="custom-frontend-main cf-single-post" id="main">
	<div class="cf-single-inner">
		<?php
		while ( have_posts() ) :
			the_post();
			$pid   = get_the_ID();
			$thumb = get_the_post_thumbnail_url( $pid, 'large' );
			?>
			<article id="post-<?php echo esc_attr( $pid ); ?>" <?php post_class( 'cf-single-article' ); ?>>
				<nav class="cf-single-breadcrumb" aria-label="<?php esc_attr_e( 'Breadcrumb', 'directory' ); ?>">
					<a href="<?php echo esc_url( $single_home ); ?>"><?php esc_html_e( 'Home', 'directory' ); ?></a>
					<span class="cf-single-breadcrumb-sep" aria-hidden="true">›</span>
					<a href="<?php echo esc_url( $single_blog ); ?>"><?php esc_html_e( 'Blog', 'directory' ); ?></a>
					<span class="cf-single-breadcrumb-sep" aria-hidden="true">›</span>
					<span class="cf-single-breadcrumb-current"><?php echo esc_html( get_the_title( $pid ) ); ?></span>
				</nav>

				<?php if ( $thumb ) : ?>
					<div class="cf-single-featured">
						<img src="<?php echo esc_url( $thumb ); ?>" alt="" class="cf-single-featured-img" />
					</div>
				<?php endif; ?>

				<header class="cf-single-header">
					<h1 class="cf-single-title"><?php the_title(); ?></h1>
					<div class="cf-single-meta">
						<span class="cf-single-date"><?php echo esc_html( get_the_date( '', $pid ) ); ?></span>
						<span class="cf-single-meta-sep">·</span>
						<span class="cf-single-author"><?php the_author_posts_link(); ?></span>
					</div>
				</header>

				<div class="cf-single-content entry-content">
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

				<footer class="cf-single-footer">
					<a href="<?php echo esc_url( $single_blog ); ?>" class="cf-single-back">← <?php esc_html_e( 'Back to Blog', 'directory' ); ?></a>
				</footer>
			</article>
			<?php
		endwhile;
		?>
	</div>
</main>
<?php
get_footer( 'custom' );
require_once get_stylesheet_directory() . '/includes/custom-frontend-doc-end.php';
