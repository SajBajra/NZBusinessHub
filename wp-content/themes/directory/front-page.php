<?php
/**
 * Front page – custom layout from mockup.
 * Sections: hero search, category of listings, featured listings, discover CTA, top pins (map), blog, testimonials, bottom CTA.
 *
 * @package Directory
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once get_stylesheet_directory() . '/includes/custom-frontend-doc-start.php';
get_header( 'custom' );

$fp_site_name  = function_exists( 'directory_display_site_name' ) ? directory_display_site_name() : get_bloginfo( 'name' );
$fp_add_url    = function_exists( 'geodir_add_listing_page_url' ) ? geodir_add_listing_page_url() : '';
if ( $fp_add_url && function_exists( 'directory_relative_url' ) ) {
	$fp_add_url = directory_relative_url( $fp_add_url );
}
$fp_cats_page  = get_permalink( get_page_by_path( 'business-categories' ) );
if ( $fp_cats_page && function_exists( 'directory_relative_url' ) ) {
	$fp_cats_page = directory_relative_url( $fp_cats_page );
}
if ( ! $fp_cats_page ) {
	$fp_cats_page = function_exists( 'directory_relative_url' ) ? directory_relative_url( home_url( '/' ) ) : home_url( '/' );
}
$fp_blog_url   = get_permalink( get_option( 'page_for_posts' ) );
if ( $fp_blog_url && function_exists( 'directory_relative_url' ) ) {
	$fp_blog_url = directory_relative_url( $fp_blog_url );
}
if ( ! $fp_blog_url ) {
	$fp_blog_url = function_exists( 'directory_relative_url' ) ? directory_relative_url( home_url( '/' ) ) : home_url( '/' );
}

$fp_taxonomy = 'gd_placecategory';
if ( class_exists( 'GeoDir_Taxonomies' ) ) {
	$fp_taxes = GeoDir_Taxonomies::get_taxonomies( 'gd_place' );
	if ( ! empty( $fp_taxes ) && is_array( $fp_taxes ) ) {
		foreach ( $fp_taxes as $t ) {
			if ( strpos( $t, 'category' ) !== false ) {
				$fp_taxonomy = $t;
				break;
			}
		}
	}
}
$fp_terms = get_terms( array(
	'taxonomy'   => $fp_taxonomy,
	'hide_empty' => false,
	'parent'     => 0,
	'orderby'    => 'name',
	'order'      => 'ASC',
) );
$fp_has_cats = ! empty( $fp_terms ) && ! is_wp_error( $fp_terms );

// Featured listings (gd_place, 6 posts).
$fp_featured = new WP_Query( array(
	'post_type'      => 'gd_place',
	'posts_per_page' => 6,
	'post_status'    => 'publish',
	'orderby'        => 'date',
	'order'          => 'DESC',
) );

// Listings for "Top pins" section (same or different).
$fp_map_listings = new WP_Query( array(
	'post_type'      => 'gd_place',
	'posts_per_page' => 6,
	'post_status'    => 'publish',
	'orderby'        => 'rand',
) );

// Blog posts (3).
$fp_posts = get_posts( array( 'numberposts' => 3, 'post_status' => 'publish', 'post_type' => 'post' ) );

$fp_hero_pattern = esc_url( content_url( '/uploads/2026/02/bg-pattern.png' ) );
$fp_hero_globe  = esc_url( content_url( '/uploads/2026/02/Group-394831.png' ) );
$fp_rel         = function_exists( 'directory_relative_url' ) ? 'directory_relative_url' : function( $u ) { return $u; };
?>
<main class="custom-frontend-main fp" id="main">
	<div class="fp__in">

		<!-- 1. Hero + Search -->
		<section class="fp__hero" style="background-image: url('<?php echo $fp_hero_pattern; ?>');">
			<div class="fp__hero-globe-wrap" aria-hidden="true">
				<img src="<?php echo $fp_hero_globe; ?>" alt="" class="fp__hero-globe" width="1200" height="600" fetchpriority="high" loading="eager" decoding="async" />
			</div>
			<div class="fp__hero-in">
				<h1 class="fp__hero-title"><?php echo esc_html( __( 'Your Local guide to businesses and services in New Zealand', 'directory' ) ); ?></h1>
				<p class="fp__hero-desc"><?php esc_html_e( 'Hundreds of thousands of businesses, and their clients, appear weekly on our website. Start today.', 'directory' ); ?></p>
				<p class="fp__hero-search-label"><?php esc_html_e( "Search New Zealand's largest range of listings", 'directory' ); ?></p>
				<div class="fp__hero-search">
					<?php if ( function_exists( 'do_shortcode' ) ) : ?>
						<?php echo do_shortcode( '[gd_search hide_search_input="false" hide_near_input="false" input_size="lg" bar_flex_wrap="flex-wrap" bar_flex_wrap_md="flex-md-nowrap" bar_flex_wrap_lg="flex-lg-nowrap"]' ); ?>
					<?php endif; ?>
				</div>
			</div>
		</section>

		<!-- 2. Category of listings (carousel, 6 at a time with icon) -->
		<?php if ( $fp_has_cats ) : ?>
			<section class="fp__section fp__categories">
				<div class="fp__wrap">
					<h2 class="fp__section-title"><?php esc_html_e( 'Category of listings', 'directory' ); ?></h2>
					<div class="fp__cat-carousel">
						<div class="fp__cat-slider">
							<button type="button" class="fp__cat-arrow fp__cat-prev" aria-label="<?php esc_attr_e( 'Previous categories', 'directory' ); ?>">
								<span aria-hidden="true">‹</span>
							</button>
							<div class="fp__cat-viewport" aria-label="<?php esc_attr_e( 'Category carousel', 'directory' ); ?>">
								<div class="fp__cat-track">
									<?php foreach ( $fp_terms as $fp_term ) :
										$fp_link = get_term_link( $fp_term );
										$fp_link = is_wp_error( $fp_link ) ? '#' : $fp_rel( $fp_link );
										$fp_cat_icon = get_term_meta( $fp_term->term_id, 'ct_cat_font_icon', true );
										$fp_cat_img  = function_exists( 'geodir_get_cat_image' ) ? geodir_get_cat_image( $fp_term->term_id, true ) : '';
										$fp_has_icon = ! empty( $fp_cat_icon );
										$fp_has_img  = ! empty( $fp_cat_img );
										/* Universal fallback: globe icon when category has no icon and no image */
										$fp_show_icon = $fp_has_icon || ! $fp_has_img;
										$fp_display_icon = $fp_has_icon ? $fp_cat_icon : 'fas fa-globe';
										?>
										<a class="fp__cat-circle" href="<?php echo esc_url( $fp_link ); ?>">
											<span class="fp__cat-circle-img"<?php if ( ! $fp_has_icon && $fp_has_img ) : ?> style="background-image:url('<?php echo esc_url( $fp_cat_img ); ?>');"<?php endif; ?>>
												<?php if ( $fp_show_icon ) : ?>
													<span class="fp__cat-circle-icon <?php echo esc_attr( $fp_display_icon ); ?>" aria-hidden="true"></span>
												<?php endif; ?>
											</span>
											<span class="fp__cat-circle-name"><?php echo esc_html( $fp_term->name ); ?></span>
										</a>
									<?php endforeach; ?>
								</div>
							</div>
							<button type="button" class="fp__cat-arrow fp__cat-next" aria-label="<?php esc_attr_e( 'Next categories', 'directory' ); ?>">
								<span aria-hidden="true">›</span>
							</button>
						</div>
						<div class="fp__cat-dots" role="tablist" aria-label="<?php esc_attr_e( 'Carousel position', 'directory' ); ?>"></div>
					</div>
				</div>
			</section>
		<?php endif; ?>

		<!-- 3. Featured listings -->
		<section class="fp__section fp__featured">
			<div class="fp__wrap">
				<h2 class="fp__section-title"><?php esc_html_e( 'Featured listings', 'directory' ); ?></h2>
				<div class="fp__listings-grid">
					<?php
					if ( $fp_featured->have_posts() ) :
						while ( $fp_featured->have_posts() ) :
							$fp_featured->the_post();
							$pid   = get_the_ID();
							$thumb = get_the_post_thumbnail_url( $pid, 'medium' );
							$link  = $fp_rel( get_the_permalink() );
							$rating = function_exists( 'geodir_get_post_rating' ) ? geodir_get_post_rating( $pid ) : '';
							?>
							<a class="fp__listing-card" href="<?php echo esc_url( $link ); ?>">
								<span class="fp__listing-img"<?php if ( $thumb ) : ?> style="background-image:url('<?php echo esc_url( $thumb ); ?>');"<?php endif; ?>></span>
								<span class="fp__listing-title"><?php the_title(); ?></span>
								<?php if ( $rating !== '' ) : ?>
									<span class="fp__listing-rating"><?php echo esc_html( $rating ); ?> ★</span>
								<?php endif; ?>
							</a>
							<?php
						endwhile;
						wp_reset_postdata();
					endif;
					?>
				</div>
				<p class="fp__featured-more">
					<a href="<?php echo esc_url( $fp_rel( function_exists( 'get_post_type_archive_link' ) ? get_post_type_archive_link( 'gd_place' ) : home_url( '/' ) ) ); ?>" class="fp__btn fp__btn-primary"><?php esc_html_e( 'Discover More', 'directory' ); ?></a>
				</p>
			</div>
		</section>

		<!-- 4. Discover Listings CTA (full-width bg image) -->
		<?php $fp_discover_bg = get_stylesheet_directory_uri() . '/assets/images/home-bg.jpg'; ?>
		<section class="fp__section fp__discover" style="background-image: url('<?php echo esc_url( $fp_discover_bg ); ?>');">
			<div class="fp__discover-overlay"></div>
			<div class="fp__wrap fp__discover-in">
				<div class="fp__discover-text">
					<h2 class="fp__section-title fp__discover-title"><?php esc_html_e( 'Discover Listings', 'directory' ); ?></h2>
					<p class="fp__discover-p"><?php esc_html_e( 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.', 'directory' ); ?></p>
					<div class="fp__discover-btns">
						<a href="<?php echo esc_url( $fp_rel( function_exists( 'get_post_type_archive_link' ) ? get_post_type_archive_link( 'gd_place' ) : home_url( '/' ) ) ); ?>" class="fp__btn fp__btn-outline"><?php esc_html_e( 'Explore Now', 'directory' ); ?></a>
						<?php if ( $fp_add_url ) : ?>
							<a href="<?php echo esc_url( $fp_add_url ); ?>" class="fp__btn fp__btn-primary"><?php esc_html_e( 'Add Listing', 'directory' ); ?></a>
						<?php endif; ?>
					</div>
				</div>
			</div>
		</section>

		<!-- 5. Explore (map center, listing cards left & right) -->
		<section class="fp__section fp__mappins fp__explore">
			<div class="fp__wrap">
				<h2 class="fp__section-title"><?php esc_html_e( 'Explore', 'directory' ); ?></h2>
				<?php
				$fp_explore_posts = array();
				if ( $fp_map_listings->have_posts() ) {
					while ( $fp_map_listings->have_posts() && count( $fp_explore_posts ) < 6 ) {
						$fp_map_listings->the_post();
						$fp_explore_posts[] = get_the_ID();
					}
					wp_reset_postdata();
				}
				?>
				<div class="fp__explore-inner">
					<div class="fp__explore-cards fp__explore-cards-left">
						<?php
						$fp_slice = array_slice( $fp_explore_posts, 0, 3 );
						foreach ( $fp_slice as $fp_pid ) :
							$thumb  = get_the_post_thumbnail_url( $fp_pid, 'thumbnail' );
							$link   = $fp_rel( get_permalink( $fp_pid ) );
							$rating = function_exists( 'geodir_get_post_rating' ) ? geodir_get_post_rating( $fp_pid ) : '';
							?>
							<a class="fp__listing-card fp__listing-card-sm" href="<?php echo esc_url( $link ); ?>">
								<span class="fp__listing-img"<?php if ( $thumb ) : ?> style="background-image:url('<?php echo esc_url( $thumb ); ?>');"<?php endif; ?>></span>
								<span class="fp__listing-title"><?php echo esc_html( get_the_title( $fp_pid ) ); ?></span>
								<?php if ( $rating !== '' ) : ?>
									<span class="fp__listing-rating"><?php echo esc_html( $rating ); ?> ★</span>
								<?php endif; ?>
							</a>
						<?php endforeach; ?>
					</div>
					<div class="fp__explore-map-wrap">
						<?php if ( function_exists( 'do_shortcode' ) ) : ?>
							<div class="fp__map-inner">
								<?php echo do_shortcode( '[gd_map map_type="directory" height="590" width="100%" search_filter="true"]' ); ?>
							</div>
						<?php endif; ?>
					</div>
					<div class="fp__explore-cards fp__explore-cards-right">
						<?php
						$fp_slice = array_slice( $fp_explore_posts, 3, 3 );
						foreach ( $fp_slice as $fp_pid ) :
							$thumb  = get_the_post_thumbnail_url( $fp_pid, 'thumbnail' );
							$link   = $fp_rel( get_permalink( $fp_pid ) );
							$rating = function_exists( 'geodir_get_post_rating' ) ? geodir_get_post_rating( $fp_pid ) : '';
							?>
							<a class="fp__listing-card fp__listing-card-sm" href="<?php echo esc_url( $link ); ?>">
								<span class="fp__listing-img"<?php if ( $thumb ) : ?> style="background-image:url('<?php echo esc_url( $thumb ); ?>');"<?php endif; ?>></span>
								<span class="fp__listing-title"><?php echo esc_html( get_the_title( $fp_pid ) ); ?></span>
								<?php if ( $rating !== '' ) : ?>
									<span class="fp__listing-rating"><?php echo esc_html( $rating ); ?> ★</span>
								<?php endif; ?>
							</a>
						<?php endforeach; ?>
					</div>
				</div>
			</div>
		</section>

		<!-- 6. Blog -->
		<section class="fp__section fp__blog">
			<div class="fp__wrap">
				<h2 class="fp__section-title"><?php esc_html_e( 'Blog', 'directory' ); ?></h2>
				<div class="fp__blog-slider">
					<?php if ( ! empty( $fp_posts ) ) : ?>
						<div class="fp__blog-track">
							<?php foreach ( $fp_posts as $fp_post ) :
								$thumb = get_the_post_thumbnail_url( $fp_post->ID, 'medium' );
								$link  = $fp_rel( get_permalink( $fp_post ) );
								?>
								<article class="fp__blog-card">
									<a href="<?php echo esc_url( $link ); ?>" class="fp__blog-card-link">
										<span class="fp__blog-card-img"<?php if ( $thumb ) : ?> style="background-image:url('<?php echo esc_url( $thumb ); ?>');"<?php endif; ?>></span>
										<h3 class="fp__blog-card-title"><?php echo esc_html( get_the_title( $fp_post ) ); ?></h3>
										<p class="fp__blog-card-excerpt"><?php echo esc_html( wp_trim_words( get_the_excerpt( $fp_post ), 12 ) ); ?></p>
										<time class="fp__blog-card-date"><?php echo esc_html( get_the_date( '', $fp_post ) ); ?></time>
									</a>
								</article>
							<?php endforeach; ?>
						</div>
						<nav class="fp__blog-nav" aria-label="<?php esc_attr_e( 'Blog carousel', 'directory' ); ?>">
							<button type="button" class="fp__blog-prev" aria-label="<?php esc_attr_e( 'Previous', 'directory' ); ?>">←</button>
							<button type="button" class="fp__blog-next" aria-label="<?php esc_attr_e( 'Next', 'directory' ); ?>">→</button>
						</nav>
					<?php endif; ?>
				</div>
				<?php if ( $fp_blog_url ) : ?>
					<p class="fp__blog-more"><a href="<?php echo esc_url( $fp_blog_url ); ?>"><?php esc_html_e( 'View all posts', 'directory' ); ?> &rarr;</a></p>
				<?php endif; ?>
			</div>
		</section>

		<!-- 7. Customer feedback (dynamic from Testimonials CPT) -->
		<?php
		$fp_testimonials = get_posts( array(
			'post_type'      => 'dir_testimonial',
			'posts_per_page' => 10,
			'orderby'        => 'menu_order title',
			'order'          => 'ASC',
			'post_status'    => 'publish',
		) );
		?>
		<?php if ( ! empty( $fp_testimonials ) ) : ?>
		<section class="fp__section fp__testimonials">
			<div class="fp__wrap">
				<h2 class="fp__section-title"><?php esc_html_e( 'Customer feedback', 'directory' ); ?></h2>
				<div class="fp__testimonials-grid">
					<?php foreach ( $fp_testimonials as $fp_test ) :
						$fp_quote  = $fp_test->post_content;
						$fp_author = $fp_test->post_title;
						$fp_role   = get_post_meta( $fp_test->ID, '_testimonial_role', true );
						$fp_avatar = get_the_post_thumbnail_url( $fp_test->ID, 'thumbnail' );
						?>
						<blockquote class="fp__testimonial">
							<?php if ( $fp_quote ) : ?>
								<div class="fp__testimonial-text"><?php echo wp_kses_post( wpautop( $fp_quote ) ); ?></div>
							<?php endif; ?>
							<footer class="fp__testimonial-footer">
								<?php if ( $fp_avatar ) : ?>
									<img src="<?php echo esc_url( $fp_avatar ); ?>" alt="" class="fp__testimonial-avatar" width="48" height="48" />
								<?php else : ?>
									<span class="fp__testimonial-avatar"></span>
								<?php endif; ?>
								<?php if ( $fp_author ) : ?>
									<cite class="fp__testimonial-author"><?php echo esc_html( $fp_author ); ?></cite>
								<?php endif; ?>
								<?php if ( $fp_role ) : ?>
									<span class="fp__testimonial-role"><?php echo esc_html( $fp_role ); ?></span>
								<?php endif; ?>
							</footer>
						</blockquote>
					<?php endforeach; ?>
				</div>
			</div>
		</section>
		<?php endif; ?>

		<!-- 8. Bottom CTA -->
		<section class="fp__section fp__cta">
			<div class="fp__wrap fp__cta-in">
				<div class="fp__cta-text">
					<h2 class="fp__section-title fp__cta-title"><?php esc_html_e( 'Discover new places in 3 easy steps', 'directory' ); ?></h2>
					<p class="fp__cta-p"><?php esc_html_e( 'Lorem ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry\'s standard dummy text ever since the 1500s.', 'directory' ); ?></p>
					<a href="<?php echo esc_url( $fp_rel( function_exists( 'get_post_type_archive_link' ) ? get_post_type_archive_link( 'gd_place' ) : home_url( '/' ) ) ); ?>" class="fp__btn fp__btn-primary"><?php esc_html_e( 'Explore Now', 'directory' ); ?></a>
				</div>
				<div class="fp__cta-img">
					<img src="<?php echo esc_url( get_stylesheet_directory_uri() . '/assets/images/home-bg.jpg' ); ?>" alt="" />
				</div>
			</div>
		</section>

	</div>
</main>
<?php if ( $fp_has_cats ) : ?>
<script>
(function() {
	function initCatCarousel() {
		var wrap = document.querySelector('.fp__cat-carousel');
		if (!wrap) return;
		var viewport = wrap.querySelector('.fp__cat-viewport');
		var track = wrap.querySelector('.fp__cat-track');
		var prev = wrap.querySelector('.fp__cat-prev');
		var next = wrap.querySelector('.fp__cat-next');
		var dotsEl = wrap.querySelector('.fp__cat-dots');
		if (!viewport || !track) return;

		function step() { return viewport.clientWidth; }
		function maxScroll() { return Math.max(0, viewport.scrollWidth - viewport.clientWidth); }
		function numPages() {
			var m = maxScroll();
			if (m <= 0) return 1;
			return Math.ceil(m / step()) + 1;
		}
		function currentPage() {
			var m = maxScroll();
			if (m <= 0) return 0;
			return Math.round(viewport.scrollLeft / step());
		}

		function buildDots() {
			if (!dotsEl) return;
			dotsEl.innerHTML = '';
			var n = numPages();
			for (var i = 0; i < n; i++) {
				var btn = document.createElement('button');
				btn.type = 'button';
				btn.className = 'fp__cat-dot' + (i === 0 ? ' is-active' : '');
				btn.setAttribute('role', 'tab');
				btn.setAttribute('aria-selected', i === 0 ? 'true' : 'false');
				btn.setAttribute('aria-label', 'Slide ' + (i + 1));
				btn.dataset.page = i;
				btn.addEventListener('click', function() {
					var p = parseInt(this.dataset.page, 10);
					viewport.scrollTo({ left: p * step(), behavior: 'smooth' });
				});
				dotsEl.appendChild(btn);
			}
		}

		function updateDots() {
			var p = currentPage();
			if (!dotsEl) return;
			var dots = dotsEl.querySelectorAll('.fp__cat-dot');
			dots.forEach(function(dot, i) {
				var active = i === p;
				dot.classList.toggle('is-active', active);
				dot.setAttribute('aria-selected', active ? 'true' : 'false');
			});
		}

		function goNext() {
			var st = step();
			var max = maxScroll();
			if (max <= 0) return;
			var nextLeft = viewport.scrollLeft + st;
			if (nextLeft >= max - 2) {
				viewport.scrollTo({ left: 0, behavior: 'smooth' });
			} else {
				viewport.scrollBy({ left: st, behavior: 'smooth' });
			}
		}

		if (prev) prev.addEventListener('click', function() {
			var st = step();
			var cur = viewport.scrollLeft;
			if (cur <= 2) {
				viewport.scrollTo({ left: maxScroll(), behavior: 'smooth' });
			} else {
				viewport.scrollBy({ left: -st, behavior: 'smooth' });
			}
		});
		if (next) next.addEventListener('click', function() { goNext(); });

		viewport.addEventListener('scroll', function() { updateDots(); });
		window.addEventListener('resize', function() {
			buildDots();
			updateDots();
		});

		buildDots();
		updateDots();

		var autoInterval = setInterval(goNext, 5000);
		wrap.addEventListener('mouseenter', function() { clearInterval(autoInterval); });
		wrap.addEventListener('mouseleave', function() { autoInterval = setInterval(goNext, 5000); });
	}
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', initCatCarousel);
	} else {
		initCatCarousel();
	}
})();
</script>
<?php endif; ?>
<?php
get_footer( 'custom' );
require_once get_stylesheet_directory() . '/includes/custom-frontend-doc-end.php';
