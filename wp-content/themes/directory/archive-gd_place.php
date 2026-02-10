<?php
/**
 * GeoDirectory places archive (Businesses) – full revamp.
 *
 * @package Directory
 */

require_once get_stylesheet_directory() . '/includes/custom-frontend-doc-start.php';
get_header( 'custom' );

$gd_archive_home    = function_exists( 'directory_relative_url' ) ? directory_relative_url( home_url( '/' ) ) : home_url( '/' );
$gd_businesses_url  = function_exists( 'get_post_type_archive_link' ) ? get_post_type_archive_link( 'gd_place' ) : $gd_archive_home;
if ( $gd_businesses_url && function_exists( 'directory_relative_url' ) ) {
	$gd_businesses_url = directory_relative_url( $gd_businesses_url );
}
$gd_queried        = get_queried_object();
$gd_is_category    = $gd_queried instanceof WP_Term && isset( $gd_queried->taxonomy ) && $gd_queried->taxonomy === 'gd_placecategory';
$gd_archive_title  = get_the_archive_title();
$gd_archive_clean  = wp_strip_all_tags( $gd_archive_title );
if ( $gd_is_category && ! empty( $gd_queried->name ) ) {
	$gd_archive_clean = $gd_queried->name;
} elseif ( empty( $gd_archive_clean ) ) {
	$gd_archive_clean = __( 'Businesses', 'directory' );
}
// Icon for parent category only (not for child categories).
$gd_cat_icon = '';
if ( $gd_is_category && ( empty( $gd_queried->parent ) || (int) $gd_queried->parent === 0 ) ) {
	$gd_cat_icon = get_term_meta( $gd_queried->term_id, 'ct_cat_font_icon', true );
	if ( empty( $gd_cat_icon ) ) {
		$gd_cat_icon = 'fas fa-globe';
	}
}

global $wp_query;

// Use a dedicated query for gd_place so listings always show (main query may be the archive page).
$gd_query_vars = array(
	'post_type'               => 'gd_place',
	'post_status'             => 'publish',
	'posts_per_page'          => 9,
	'paged'                   => max( 1, (int) get_query_var( 'paged' ) ),
	'update_post_meta_cache'  => false,
);
// Preserve main query vars for search/filters (GD uses query vars).
if ( ! empty( $wp_query->query_vars ) ) {
	foreach ( array( 's', 'gd_location', 'gd_placecategory', 'sort_by', 'near' ) as $var ) {
		if ( isset( $wp_query->query_vars[ $var ] ) && $wp_query->query_vars[ $var ] !== '' ) {
			$gd_query_vars[ $var ] = $wp_query->query_vars[ $var ];
		}
	}
}
$gd_query = new WP_Query( $gd_query_vars );
$gd_total = (int) $gd_query->found_posts;
?>
<main class="custom-frontend-main cf-gd-archive cf-gd-archive-v2" id="main">
	<header class="cf-gd-hero">
		<div class="cf-gd-hero-inner">
			<nav class="cf-gd-breadcrumb" aria-label="<?php esc_attr_e( 'Breadcrumb', 'directory' ); ?>">
				<a href="<?php echo esc_url( $gd_archive_home ); ?>"><?php esc_html_e( 'Home', 'directory' ); ?></a>
				<span class="cf-gd-breadcrumb-sep" aria-hidden="true">›</span>
				<?php if ( $gd_is_category && $gd_businesses_url ) : ?>
					<a href="<?php echo esc_url( $gd_businesses_url ); ?>"><?php esc_html_e( 'Businesses', 'directory' ); ?></a>
					<span class="cf-gd-breadcrumb-sep" aria-hidden="true">›</span>
					<span class="cf-gd-breadcrumb-current"><?php echo esc_html( $gd_archive_clean ); ?></span>
				<?php else : ?>
					<span class="cf-gd-breadcrumb-current"><?php echo esc_html( $gd_archive_clean ); ?></span>
				<?php endif; ?>
			</nav>
			<h1 class="cf-gd-hero-title">
				<?php if ( $gd_cat_icon ) : ?>
					<span class="cf-gd-hero-cat-icon <?php echo esc_attr( $gd_cat_icon ); ?>" aria-hidden="true"></span>
				<?php endif; ?>
				<?php echo esc_html( $gd_archive_clean ); ?>
			</h1>
			<p class="cf-gd-hero-desc"><?php esc_html_e( 'Discover and explore local businesses.', 'directory' ); ?></p>
		</div>
	</header>

	<div class="cf-gd-archive-inner">
		<section class="cf-gd-search-section cf-gd-search-overlap" aria-label="<?php esc_attr_e( 'Search', 'directory' ); ?>">
			<div class="cf-gd-search-bar" role="search">
				<?php if ( function_exists( 'do_shortcode' ) ) : ?>
					<?php echo do_shortcode( '[gd_search hide_search_input="false" hide_near_input="false" input_size="md" bar_flex_wrap="flex-wrap"]' ); ?>
				<?php endif; ?>
			</div>
		</section>

		<div class="cf-gd-layout">
			<div class="cf-gd-list-col">
				<header class="cf-gd-list-header">
					<p class="cf-gd-results-count">
						<?php
						if ( $gd_total === 0 ) {
							esc_html_e( 'No businesses found.', 'directory' );
						} else {
							?>
							<span class="cf-gd-results-badge" aria-hidden="true"><?php echo (int) $gd_total; ?></span>
							<?php echo esc_html( _n( 'business', 'businesses', $gd_total, 'directory' ) ); ?>
							<?php
						}
						?>
					</p>
				</header>

				<?php if ( $gd_query->have_posts() ) : ?>
					<div class="cf-gd-cards cf-gd-cards-grid">
						<?php
						$gd_card_index = 0;
						while ( $gd_query->have_posts() ) :
							$gd_query->the_post();
							$pid   = get_the_ID();
							$thumb = get_the_post_thumbnail_url( $pid, 'medium_large' );
							$link  = get_the_permalink();
							$link  = function_exists( 'directory_relative_url' ) ? directory_relative_url( $link ) : $link;

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

							$address_parts = array();
							if ( function_exists( 'geodir_get_post_meta' ) ) {
								$street = geodir_get_post_meta( $pid, 'street', true );
								$city   = geodir_get_post_meta( $pid, 'city', true );
								if ( ! empty( $street ) ) {
									$address_parts[] = $street;
								}
								if ( ! empty( $city ) ) {
									$address_parts[] = $city;
								}
							}
							$address_line = implode( ', ', $address_parts );

							$rating_html = '';
							if ( function_exists( 'geodir_get_rating_stars' ) && function_exists( 'geodir_get_post_rating' ) ) {
								$post_rating = geodir_get_post_rating( $pid );
								if ( $post_rating !== '' && $post_rating !== null ) {
									$rating_html = geodir_get_rating_stars( $post_rating, $pid );
								}
							}
							$gd_first_image = $gd_card_index === 0;
							$gd_card_index++;
							?>
							<article id="post-<?php echo esc_attr( $pid ); ?>" <?php post_class( 'cf-gd-card' ); ?>>
								<a class="cf-gd-card-link" href="<?php echo esc_url( $link ); ?>">
									<div class="cf-gd-card-image-wrap">
										<?php if ( $thumb ) : ?>
											<img src="<?php echo esc_url( $thumb ); ?>" alt="" class="cf-gd-card-image" loading="<?php echo $gd_first_image ? 'eager' : 'lazy'; ?>" decoding="async"<?php echo $gd_first_image ? ' fetchpriority="high"' : ''; ?> />
										<?php else : ?>
											<div class="cf-gd-card-image-placeholder"></div>
										<?php endif; ?>
										<?php if ( $rating_html ) : ?>
											<div class="cf-gd-card-rating"><?php echo $rating_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
										<?php endif; ?>
									</div>
									<div class="cf-gd-card-body">
										<?php if ( $cat_name ) : ?>
											<p class="cf-gd-card-cat"><?php echo esc_html( $cat_name ); ?></p>
										<?php endif; ?>
										<h2 class="cf-gd-card-title"><?php the_title(); ?></h2>
										<?php if ( $address_line ) : ?>
											<p class="cf-gd-card-address"><?php echo esc_html( $address_line ); ?></p>
										<?php endif; ?>
										<p class="cf-gd-card-excerpt"><?php echo esc_html( wp_trim_words( get_the_excerpt(), 14 ) ); ?></p>
										<span class="cf-gd-card-cta"><?php esc_html_e( 'View listing', 'directory' ); ?> &rarr;</span>
									</div>
								</a>
							</article>
						<?php endwhile; ?>
					</div>

					<?php
					$gd_temp_query = $wp_query;
					$wp_query      = $gd_query; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
					$pag           = get_the_posts_pagination( array(
						'mid_size'  => 1,
						'prev_text' => '&larr; ' . __( 'Previous', 'directory' ),
						'next_text' => __( 'Next', 'directory' ) . ' &rarr;',
						'class'     => 'cf-gd-pagination',
					) );
					$wp_query = $gd_temp_query; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
					if ( $pag ) {
						echo '<nav class="cf-gd-pagination-nav" aria-label="' . esc_attr__( 'Listings navigation', 'directory' ) . '">' . $pag . '</nav>';
					}
					wp_reset_postdata();
					?>
				<?php else : ?>
					<div class="cf-gd-empty-wrap">
						<span class="cf-gd-empty-icon" aria-hidden="true"></span>
						<p class="cf-gd-empty-title"><?php esc_html_e( 'No results yet', 'directory' ); ?></p>
						<p class="cf-gd-empty"><?php esc_html_e( 'Try adjusting your search or filters to explore businesses.', 'directory' ); ?></p>
					</div>
				<?php endif; ?>

				<?php
				// Category CMS content + FAQs (from term meta) – shown under business cards or empty state.
				if ( $gd_is_category && ! empty( $gd_queried->term_id ) ) {
					$term_id  = (int) $gd_queried->term_id;
					$content  = get_term_meta( $term_id, function_exists( 'directory_gd_cat_meta_key_content' ) ? directory_gd_cat_meta_key_content() : '_directory_cat_content', true );
					$image1id = get_term_meta( $term_id, function_exists( 'directory_gd_cat_meta_key_image1' ) ? directory_gd_cat_meta_key_image1() : '_directory_cat_image_1_id', true );
					$image2id = get_term_meta( $term_id, function_exists( 'directory_gd_cat_meta_key_image2' ) ? directory_gd_cat_meta_key_image2() : '_directory_cat_image_2_id', true );
					$faq      = get_term_meta( $term_id, function_exists( 'directory_gd_cat_meta_key_faq' ) ? directory_gd_cat_meta_key_faq() : '_directory_cat_faq', true );

					$image1 = $image1id ? wp_get_attachment_image_url( (int) $image1id, 'large' ) : '';
					$image2 = $image2id ? wp_get_attachment_image_url( (int) $image2id, 'large' ) : '';
					if ( ! is_array( $faq ) ) {
						$faq = array();
					}

					$has_content = is_string( $content ) && trim( $content ) !== '';
					$has_images  = (bool) ( $image1 || $image2 );
					$has_faq     = false;
					foreach ( $faq as $item ) {
						$q = isset( $item['q'] ) ? (string) $item['q'] : '';
						$a = isset( $item['a'] ) ? (string) $item['a'] : '';
						if ( trim( $q ) !== '' || trim( $a ) !== '' ) {
							$has_faq = true;
							break;
						}
					}

					if ( $has_content || $has_images || $has_faq ) :
						?>
						<section class="cf-gd-cat-cms" aria-label="<?php esc_attr_e( 'Category information', 'directory' ); ?>">
							<div class="cf-gd-cat-cms-inner">
								<?php if ( $has_content || $has_images ) : ?>
									<div class="cf-gd-cat-cms-grid">
										<div class="cf-gd-cat-cms-content">
											<h2 class="cf-gd-cat-cms-title"><?php echo esc_html( $gd_archive_clean ); ?></h2>
											<?php if ( $has_content ) : ?>
												<div class="cf-gd-cat-cms-text"><?php echo wp_kses_post( $content ); ?></div>
											<?php else : ?>
												<p class="cf-gd-cat-cms-empty"><?php esc_html_e( 'More details coming soon.', 'directory' ); ?></p>
											<?php endif; ?>
										</div>
										<?php if ( $has_images ) : ?>
											<div class="cf-gd-cat-cms-images" aria-hidden="true">
												<?php if ( $image1 ) : ?>
													<div class="cf-gd-cat-cms-img" style="background-image:url('<?php echo esc_url( $image1 ); ?>');"></div>
												<?php endif; ?>
												<?php if ( $image2 ) : ?>
													<div class="cf-gd-cat-cms-img" style="background-image:url('<?php echo esc_url( $image2 ); ?>');"></div>
												<?php endif; ?>
											</div>
										<?php endif; ?>
									</div>
								<?php endif; ?>

								<?php if ( $has_faq ) : ?>
									<div class="cf-gd-cat-faq">
										<h2 class="cf-gd-cat-faq-title"><?php esc_html_e( 'Frequently asked questions', 'directory' ); ?></h2>
										<div class="cf-gd-cat-faq-list">
											<?php foreach ( $faq as $item ) :
												$q = isset( $item['q'] ) ? trim( (string) $item['q'] ) : '';
												$a = isset( $item['a'] ) ? trim( (string) $item['a'] ) : '';
												if ( $q === '' && $a === '' ) {
													continue;
												}
												?>
												<details class="cf-gd-cat-faq-item">
													<summary class="cf-gd-cat-faq-q"><?php echo esc_html( $q ?: __( 'Question', 'directory' ) ); ?></summary>
													<div class="cf-gd-cat-faq-a"><?php echo wp_kses_post( $a ); ?></div>
												</details>
											<?php endforeach; ?>
										</div>
									</div>
								<?php endif; ?>
							</div>
						</section>
						<?php
					endif;
				}
				?>
			</div>
		</div>
	</div>
</main>
<?php
wp_reset_postdata();
get_footer( 'custom' );
require_once get_stylesheet_directory() . '/includes/custom-frontend-doc-end.php';
