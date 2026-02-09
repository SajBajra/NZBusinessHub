<?php
/**
 * Single GeoDirectory place (business/listing detail) – custom layout per mockup.
 *
 * @package Directory
 */

require_once get_stylesheet_directory() . '/includes/custom-frontend-doc-start.php';
get_header( 'custom' );

$gd_home = function_exists( 'directory_relative_url' ) ? directory_relative_url( home_url( '/' ) ) : home_url( '/' );
$gd_archive = function_exists( 'get_post_type_archive_link' ) ? get_post_type_archive_link( 'gd_place' ) : $gd_home;
if ( $gd_archive && function_exists( 'directory_relative_url' ) ) {
	$gd_archive = directory_relative_url( $gd_archive );
}

while ( have_posts() ) :
	the_post();
	$pid = get_the_ID();
	$link = get_the_permalink();
	if ( function_exists( 'directory_relative_url' ) ) {
		$link = directory_relative_url( $link );
	}

	$cat_name = '';
	if ( function_exists( 'geodir_get_post_top_parent_terms' ) ) {
		$top = geodir_get_post_top_parent_terms( $pid, 'gd_placecategory' );
		if ( ! empty( $top[0] ) && is_object( $top[0] ) ) {
			$cat_name = $top[0]->name;
		}
	}
	if ( $cat_name === '' ) {
		$terms = get_the_terms( $pid, 'gd_placecategory' );
		if ( $terms && ! is_wp_error( $terms ) && ! empty( $terms ) ) {
			$cat_name = $terms[0]->name;
		}
	}
	?>
<main class="custom-frontend-main cf-single-place" id="main">
	<div class="cf-single-place-inner">
		<nav class="cf-single-place-breadcrumb" aria-label="<?php esc_attr_e( 'Breadcrumb', 'directory' ); ?>">
			<a href="<?php echo esc_url( $gd_home ); ?>"><?php esc_html_e( 'Home', 'directory' ); ?></a>
			<span class="cf-single-place-breadcrumb-sep" aria-hidden="true">/</span>
			<a href="<?php echo esc_url( $gd_archive ); ?>"><?php esc_html_e( 'Businesses', 'directory' ); ?></a>
			<?php if ( $cat_name ) : ?>
				<span class="cf-single-place-cat-tag"><?php echo esc_html( $cat_name ); ?></span>
			<?php endif; ?>
		</nav>

		<header class="cf-single-place-header">
			<div class="cf-single-place-header-text">
				<h1 class="cf-single-place-title"><?php the_title(); ?></h1>
				<?php if ( function_exists( 'geodir_get_rating_stars' ) && function_exists( 'geodir_get_post_rating' ) ) : ?>
					<?php $post_rating = geodir_get_post_rating( $pid ); ?>
					<?php if ( $post_rating !== '' && $post_rating !== null ) : ?>
						<div class="cf-single-place-rating"><?php echo geodir_get_rating_stars( $post_rating, $pid ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
					<?php endif; ?>
				<?php endif; ?>
			</div>
			<div class="cf-single-place-gallery">
				<?php if ( function_exists( 'do_shortcode' ) ) : ?>
					<?php echo do_shortcode( '[gd_post_images type="image" ajax_load="true" link_to="lightbox" types="logo,post_images" limit="3" limit_show="3" image_size="medium_large" css_class="cf-single-place-gallery-inner"]' ); ?>
				<?php else : ?>
					<?php $thumb = get_the_post_thumbnail_url( $pid, 'medium_large' ); ?>
					<?php if ( $thumb ) : ?>
						<div class="cf-single-place-gallery-inner">
							<img src="<?php echo esc_url( $thumb ); ?>" alt="" class="cf-single-place-main-img" />
						</div>
					<?php endif; ?>
				<?php endif; ?>
			</div>
		</header>

		<div class="cf-single-place-layout">
			<div class="cf-single-place-main">
				<section class="cf-single-place-section cf-single-place-overview" aria-labelledby="cf-overview-heading">
					<h2 id="cf-overview-heading" class="cf-single-place-section-title"><?php esc_html_e( 'Overview', 'directory' ); ?></h2>
					<div class="cf-single-place-content entry-content">
						<?php the_content(); ?>
					</div>
				</section>

				<section class="cf-single-place-section cf-single-place-photos" aria-labelledby="cf-photos-heading">
					<h2 id="cf-photos-heading" class="cf-single-place-section-title"><?php esc_html_e( 'Photos', 'directory' ); ?></h2>
					<div class="cf-single-place-photos-grid">
						<?php if ( function_exists( 'do_shortcode' ) ) : ?>
							<?php echo do_shortcode( '[gd_post_images type="image" ajax_load="true" link_to="lightbox" types="logo,post_images" limit="" image_size="medium_large" css_class="cf-single-place-photos-inner"]' ); ?>
						<?php endif; ?>
					</div>
				</section>
			</div>

			<aside class="cf-single-place-sidebar">
				<section class="cf-single-place-details" aria-labelledby="cf-details-heading">
					<h2 id="cf-details-heading" class="cf-single-place-sidebar-title"><?php esc_html_e( 'Details', 'directory' ); ?></h2>
					<div class="cf-single-place-details-list">
						<?php
						$detail_keys = array( 'street', 'city', 'region', 'country', 'zip', 'phone', 'email', 'website', 'business_hours_today' );
						foreach ( $detail_keys as $key ) {
							if ( ! function_exists( 'geodir_get_post_meta' ) ) {
								break;
							}
							$val = geodir_get_post_meta( $pid, $key, true );
							if ( $val === '' || $val === null ) {
								continue;
							}
							$label = ucfirst( str_replace( '_', ' ', $key ) );
							if ( $key === 'business_hours_today' ) {
								$label = __( 'Hours today', 'directory' );
							}
							$is_link = in_array( $key, array( 'email', 'website' ), true ) && ( is_email( $val ) || filter_var( $val, FILTER_VALIDATE_URL ) );
							?>
							<div class="cf-single-place-detail-item">
								<span class="cf-single-place-detail-label"><?php echo esc_html( $label ); ?></span>
								<?php if ( $is_link && $key === 'website' ) : ?>
									<a href="<?php echo esc_url( $val ); ?>" class="cf-single-place-detail-value cf-single-place-detail-link" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $val ); ?></a>
								<?php elseif ( $is_link && $key === 'email' ) : ?>
									<a href="mailto:<?php echo esc_attr( $val ); ?>" class="cf-single-place-detail-value cf-single-place-detail-link"><?php echo esc_html( $val ); ?></a>
								<?php else : ?>
									<span class="cf-single-place-detail-value"><?php echo esc_html( $val ); ?></span>
								<?php endif; ?>
							</div>
						<?php } ?>
						<?php if ( function_exists( 'do_shortcode' ) ) : ?>
							<div class="cf-single-place-detail-meta">
								<?php echo do_shortcode( '[gd_post_meta key="address" show="value"]' ); ?>
							</div>
						<?php endif; ?>
					</div>
				</section>

				<section class="cf-single-place-reviews" aria-labelledby="cf-review-heading">
					<h2 id="cf-review-heading" class="cf-single-place-sidebar-title"><?php esc_html_e( 'Review', 'directory' ); ?></h2>
					<div class="cf-single-place-review-form">
						<?php if ( function_exists( 'do_shortcode' ) ) : ?>
							<?php echo do_shortcode( '[gd_single_reviews title="" template="clean"]' ); ?>
						<?php endif; ?>
					</div>
				</section>
			</aside>
		</div>

		<section class="cf-single-place-section cf-single-place-similar" aria-labelledby="cf-similar-heading">
			<h2 id="cf-similar-heading" class="cf-single-place-section-title"><?php esc_html_e( 'Similar places', 'directory' ); ?></h2>
			<div class="cf-single-place-similar-inner">
						<?php
						$similar_term_ids = array();
						$current_terms = get_the_terms( $pid, 'gd_placecategory' );
						if ( $current_terms && ! is_wp_error( $current_terms ) ) {
							$similar_term_ids = wp_list_pluck( $current_terms, 'term_id' );
						}
						$similar_query = new WP_Query( array(
							'post_type'      => 'gd_place',
							'post_status'    => 'publish',
							'posts_per_page' => 3,
							'post__not_in'   => array( $pid ),
							'tax_query'      => ! empty( $similar_term_ids ) ? array(
								array(
									'taxonomy' => 'gd_placecategory',
									'field'    => 'term_id',
									'terms'    => $similar_term_ids,
								),
							) : array(),
							'orderby'        => 'rand',
						) );
						if ( $similar_query->have_posts() ) :
							while ( $similar_query->have_posts() ) :
								$similar_query->the_post();
								$sp_id    = get_the_ID();
								$sp_link  = get_the_permalink();
								$sp_thumb = get_the_post_thumbnail_url( $sp_id, 'medium_large' );
								if ( function_exists( 'directory_relative_url' ) ) {
									$sp_link = directory_relative_url( $sp_link );
								}
								$sp_excerpt = get_the_excerpt();
								$sp_lead    = wp_trim_words( $sp_excerpt, 12 );
								$sp_more    = $sp_lead !== $sp_excerpt ? wp_trim_words( $sp_excerpt, 24 ) . '…' : $sp_excerpt;
								?>
								<article class="cf-similar-card">
									<a class="cf-similar-card-link" href="<?php echo esc_url( $sp_link ); ?>">
										<div class="cf-similar-card-image-wrap">
											<?php if ( $sp_thumb ) : ?>
												<img src="<?php echo esc_url( $sp_thumb ); ?>" alt="" class="cf-similar-card-image" loading="lazy" />
											<?php else : ?>
												<div class="cf-similar-card-image-placeholder"></div>
											<?php endif; ?>
										</div>
										<div class="cf-similar-card-body">
											<h3 class="cf-similar-card-title"><?php the_title(); ?></h3>
											<p class="cf-similar-card-lead"><?php echo esc_html( $sp_lead ); ?></p>
											<p class="cf-similar-card-excerpt"><?php echo esc_html( $sp_more ); ?></p>
											<time class="cf-similar-card-date" datetime="<?php echo esc_attr( get_the_date( 'c', $sp_id ) ); ?>"><?php echo esc_html( get_the_date( '', $sp_id ) ); ?></time>
										</div>
									</a>
								</article>
								<?php
							endwhile;
							wp_reset_postdata();
						endif;
						?>
			</div>
		</section>
	</div>
</main>
	<?php
endwhile;

get_footer( 'custom' );
require_once get_stylesheet_directory() . '/includes/custom-frontend-doc-end.php';
