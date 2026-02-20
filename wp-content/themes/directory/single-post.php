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

		// Share URLs.
		$permalink   = get_permalink( $pid );
		$title_attr  = get_the_title( $pid );
		$encoded_url = rawurlencode( $permalink );
		$encoded_ttl = rawurlencode( $title_attr );
		$share_links = array(
			'facebook' => 'https://www.facebook.com/sharer/sharer.php?u=' . $encoded_url,
			'twitter'  => 'https://twitter.com/intent/tweet?url=' . $encoded_url . '&text=' . $encoded_ttl,
			'linkedin' => 'https://www.linkedin.com/sharing/share-offsite/?url=' . $encoded_url,
		);

		$share_count = get_comments_number( $pid );
		?>

		<div class="cf-blog-single-shell">
			<nav class="cf-blog-single-breadcrumb" aria-label="<?php esc_attr_e( 'Breadcrumb', 'directory' ); ?>">
				<a href="<?php echo esc_url( $blog_home ); ?>"><?php esc_html_e( 'Home', 'directory' ); ?></a>
				<span class="cf-blog-single-breadcrumb-sep" aria-hidden="true">›</span>
				<a href="<?php echo esc_url( $blog_page ); ?>"><?php esc_html_e( 'Blog', 'directory' ); ?></a>
				<span class="cf-blog-single-breadcrumb-sep" aria-hidden="true">›</span>
				<span class="cf-blog-single-breadcrumb-current"><?php echo esc_html( get_the_title( $pid ) ); ?></span>
			</nav>

			<section class="cf-blog-single-hero" aria-labelledby="cf-blog-hero-title">
				<div class="cf-blog-single-hero-inner">
					<?php if ( ! empty( $cats ) && ! is_wp_error( $cats ) ) : ?>
						<div class="cf-blog-single-cats cf-blog-single-cats--main">
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

					<h1 id="cf-blog-hero-title" class="cf-blog-single-hero-title">
						<?php the_title(); ?>
					</h1>

					<div class="cf-blog-single-meta cf-blog-single-meta--top">
						<span class="cf-blog-single-meta-item">
							<span class="cf-blog-single-meta-icon" aria-hidden="true"><i class="fas fa-user-circle"></i></span>
							<span class="cf-blog-single-meta-text"><?php echo esc_html( get_the_author() ); ?></span>
						</span>
						<span class="cf-blog-single-meta-item">
							<span class="cf-blog-single-meta-icon" aria-hidden="true"><i class="fas fa-calendar-alt"></i></span>
							<span class="cf-blog-single-meta-text"><?php echo esc_html( get_the_date( '', $pid ) ); ?></span>
						</span>
					</div>
				</div>
			</section>
		</div>

		<?php if ( $thumb ) : ?>
			<div class="cf-blog-single-hero-image-wrap">
				<img src="<?php echo esc_url( $thumb ); ?>" alt="" class="cf-blog-single-hero-image" />
			</div>
		<?php endif; ?>

		<div class="cf-blog-single-shell">
			<article id="post-<?php echo esc_attr( $pid ); ?>" <?php post_class( 'cf-blog-single-layout' ); ?>>
				<div class="cf-blog-single-body-two-col">
					<aside class="cf-blog-single-share-sidebar" aria-label="<?php esc_attr_e( 'Share this blog', 'directory' ); ?>">
						<div class="cf-blog-single-share-sticky">
							<p class="cf-blog-single-share-label"><?php esc_html_e( 'Share this blog', 'directory' ); ?></p>
							<ul class="cf-blog-single-share-list" aria-label="<?php esc_attr_e( 'Share this blog', 'directory' ); ?>">
								<li>
									<a class="cf-blog-single-share-btn cf-blog-single-share-btn--facebook" href="<?php echo esc_url( $share_links['facebook'] ); ?>" target="_blank" rel="noopener noreferrer">
										<span class="cf-blog-single-share-icon" aria-hidden="true"><i class="fab fa-facebook-f"></i></span>
										<span class="cf-blog-single-share-text"><?php esc_html_e( 'Facebook', 'directory' ); ?></span>
									</a>
								</li>
								<li>
									<a class="cf-blog-single-share-btn cf-blog-single-share-btn--instagram" href="https://www.instagram.com/" target="_blank" rel="noopener noreferrer">
										<span class="cf-blog-single-share-icon" aria-hidden="true"><i class="fab fa-instagram"></i></span>
										<span class="cf-blog-single-share-text"><?php esc_html_e( 'Instagram', 'directory' ); ?></span>
									</a>
								</li>
								<li>
									<a class="cf-blog-single-share-btn cf-blog-single-share-btn--tiktok" href="https://www.tiktok.com/" target="_blank" rel="noopener noreferrer">
										<span class="cf-blog-single-share-icon" aria-hidden="true"><i class="fab fa-tiktok"></i></span>
										<span class="cf-blog-single-share-text"><?php esc_html_e( 'TikTok', 'directory' ); ?></span>
									</a>
								</li>
								<li>
									<a class="cf-blog-single-share-btn cf-blog-single-share-btn--twitter" href="<?php echo esc_url( $share_links['twitter'] ); ?>" target="_blank" rel="noopener noreferrer">
										<span class="cf-blog-single-share-icon" aria-hidden="true"><i class="fab fa-twitter"></i></span>
										<span class="cf-blog-single-share-text"><?php esc_html_e( 'Twitter', 'directory' ); ?></span>
									</a>
								</li>
							</ul>
						</div>
					</aside>

					<div class="cf-blog-single-content-wrap entry-content">
						<?php
						$toc_data = function_exists( 'directory_get_post_toc_and_content' )
							? directory_get_post_toc_and_content( $pid )
							: array(
								'toc'     => '',
								'content' => apply_filters( 'the_content', get_the_content() ),
							);

						// Table of contents (built from H2 headings).
						if ( ! empty( $toc_data['toc'] ) ) {
							echo $toc_data['toc']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						}

						// Main post content with injected anchor IDs.
						echo $toc_data['content']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						?>

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

						<?php
						// Newsletter signup section after content + footer.
						if ( function_exists( 'directory_render_newsletter_section' ) ) {
							directory_render_newsletter_section( get_permalink( $pid ) );
						}
						?>
					</div>
				</div>
			</article>

			<?php
			// Similar posts (same categories if possible) for grid section.
			$similar_args = array(
				'post_type'           => 'post',
				'post_status'         => 'publish',
				'posts_per_page'      => 3,
				'post__not_in'        => array( $pid ),
				'ignore_sticky_posts' => true,
			);
			if ( ! empty( $cats ) && ! is_wp_error( $cats ) ) {
				$cat_ids = wp_list_pluck( $cats, 'term_id' );
				if ( ! empty( $cat_ids ) ) {
					$similar_args['category__in'] = $cat_ids;
				}
			}
			$recent = new WP_Query( $similar_args );

			if ( $recent->have_posts() ) :
				?>
				<section class="cf-blog-single-next" aria-label="<?php esc_attr_e( 'Similar posts', 'directory' ); ?>">
					<div class="cf-blog-single-next-header">
						<h2 class="cf-blog-single-next-title"><?php esc_html_e( 'Similar posts', 'directory' ); ?></h2>
					</div>
					<div class="cf-blog-single-next-grid">
						<?php
						while ( $recent->have_posts() ) :
							$recent->the_post();
							$r_id    = get_the_ID();
							$r_thumb = get_the_post_thumbnail_url( $r_id, 'medium_large' );
							$r_link  = get_the_permalink();
							if ( function_exists( 'directory_relative_url' ) ) {
								$r_link = directory_relative_url( $r_link );
							}
							?>
							<article id="recent-post-<?php echo esc_attr( $r_id ); ?>" <?php post_class( 'cf-blog-card', $r_id ); ?>>
								<a href="<?php echo esc_url( $r_link ); ?>" class="cf-blog-card-link">
									<div class="cf-blog-card-image-wrap">
										<?php if ( $r_thumb ) : ?>
											<img src="<?php echo esc_url( $r_thumb ); ?>" alt="" class="cf-blog-card-image" loading="lazy" />
										<?php else : ?>
											<div class="cf-blog-card-image-placeholder" aria-hidden="true"></div>
										<?php endif; ?>
									</div>
									<div class="cf-blog-card-body">
										<p class="cf-blog-card-author"><?php echo esc_html( get_the_date() ); ?></p>
										<h3 class="cf-blog-card-title"><?php the_title(); ?></h3>
										<p class="cf-blog-card-excerpt"><?php echo esc_html( wp_trim_words( get_the_excerpt(), 18 ) ); ?></p>
									</div>
								</a>
							</article>
							<?php
						endwhile;
						wp_reset_postdata();
						?>
					</div>
				</section>
				<?php
			endif;
			?>
		</div>
		<?php
	endwhile;
	?>
</main>

<?php
get_footer( 'custom' );
require_once get_stylesheet_directory() . '/includes/custom-frontend-doc-end.php';

