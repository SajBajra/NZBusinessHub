<?php
/**
 * Home page featured sliders – configurable via a simple settings page.
 *
 * Two sections, each configurable with:
 * - Title
 * - Category slug (gd_placecategory) – optional
 * - Number of listings
 *
 * @package Directory
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const DIRECTORY_HOME_FEATURED_OPTION = 'directory_home_featured_sections';

/**
 * Get home featured sections config with sane defaults.
 *
 * @return array
 */
function directory_get_home_featured_sections() {
	$defaults = array(
		'sections' => array(
			array(
				'title'   => __( 'Top Restaurants', 'directory' ),
				'cat_slug'=> '',
				'count'   => 8,
			),
			array(
				'title'   => __( 'Top Experiences', 'directory' ),
				'cat_slug'=> '',
				'count'   => 8,
			),
		),
	);

	$stored = get_option( DIRECTORY_HOME_FEATURED_OPTION, array() );
	if ( ! is_array( $stored ) ) {
		$stored = array();
	}

	$sections = isset( $stored['sections'] ) && is_array( $stored['sections'] ) ? $stored['sections'] : array();

	// Merge with defaults for exactly 2 sections.
	for ( $i = 0; $i < 2; $i++ ) {
		if ( ! isset( $sections[ $i ] ) || ! is_array( $sections[ $i ] ) ) {
			$sections[ $i ] = $defaults['sections'][ $i ];
		} else {
			$sections[ $i ] = array_merge( $defaults['sections'][ $i ], $sections[ $i ] );
		}
	}

	return array(
		'sections' => $sections,
	);
}

/**
 * Register a simple settings page under Appearance.
 */
function directory_home_featured_admin_menu() {
	add_theme_page(
		__( 'Home Featured Sections', 'directory' ),
		__( 'Home Sections', 'directory' ),
		'manage_options',
		'directory-home-featured',
		'directory_home_featured_admin_page'
	);
}
add_action( 'admin_menu', 'directory_home_featured_admin_menu' );

/**
 * Register the option for home featured sections.
 */
function directory_home_featured_register_setting() {
	register_setting(
		'directory_home_featured_group',
		DIRECTORY_HOME_FEATURED_OPTION,
		'directory_home_featured_sanitize'
	);
}
add_action( 'admin_init', 'directory_home_featured_register_setting' );

/**
 * Sanitize input from settings page.
 */
function directory_home_featured_sanitize( $input ) {
	$out = array( 'sections' => array() );

	if ( isset( $input['sections'] ) && is_array( $input['sections'] ) ) {
		foreach ( $input['sections'] as $section ) {
			$title   = isset( $section['title'] ) ? sanitize_text_field( $section['title'] ) : '';
			$slug    = isset( $section['cat_slug'] ) ? sanitize_title( $section['cat_slug'] ) : '';
			$count   = isset( $section['count'] ) ? (int) $section['count'] : 8;
			$count   = max( 1, min( 20, $count ) );
			$out['sections'][] = array(
				'title'    => $title,
				'cat_slug' => $slug,
				'count'    => $count,
			);
		}
	}

	return $out;
}

/**
 * Render admin page markup.
 */
function directory_home_featured_admin_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$data = directory_get_home_featured_sections();
	$sections = $data['sections'];

	// Parent GeoDirectory categories to choose from.
	$parent_terms = get_terms(
		array(
			'taxonomy'   => 'gd_placecategory',
			'hide_empty' => false,
			'parent'     => 0,
			'orderby'    => 'name',
			'order'      => 'ASC',
		)
	);
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Home Featured Sections', 'directory' ); ?></h1>
		<p><?php esc_html_e( 'Configure up to two featured sliders that appear on the home page before the customer feedback section.', 'directory' ); ?></p>

		<form action="options.php" method="post">
			<?php
			settings_fields( 'directory_home_featured_group' );
			?>
			<table class="form-table" role="presentation">
				<tbody>
				<?php foreach ( $sections as $i => $section ) : ?>
					<tr>
						<th scope="row">
							<h2 style="margin:0 0 4px;"><?php echo esc_html( sprintf( __( 'Section %d', 'directory' ), $i + 1 ) ); ?></h2>
						</th>
						<td>
							<p>
								<label><strong><?php esc_html_e( 'Title', 'directory' ); ?></strong></label><br />
								<input type="text" class="regular-text" name="<?php echo esc_attr( DIRECTORY_HOME_FEATURED_OPTION ); ?>[sections][<?php echo (int) $i; ?>][title]" value="<?php echo esc_attr( $section['title'] ); ?>" />
							</p>
							<p>
								<label><strong><?php esc_html_e( 'Parent category to feature (optional)', 'directory' ); ?></strong></label><br />
								<select name="<?php echo esc_attr( DIRECTORY_HOME_FEATURED_OPTION ); ?>[sections][<?php echo (int) $i; ?>][cat_slug]">
									<?php
									$current_slug = isset( $section['cat_slug'] ) ? $section['cat_slug'] : '';
									?>
									<option value=""><?php esc_html_e( 'All categories', 'directory' ); ?></option>
									<?php
									if ( ! is_wp_error( $parent_terms ) && ! empty( $parent_terms ) ) :
										foreach ( $parent_terms as $term ) :
											?>
											<option value="<?php echo esc_attr( $term->slug ); ?>" <?php selected( $current_slug, $term->slug ); ?>>
												<?php echo esc_html( $term->name ); ?>
											</option>
											<?php
										endforeach;
									endif;
									?>
								</select>
								<br /><span class="description"><?php esc_html_e( 'Choose which parent GeoDirectory place category to pull listings from. Leave as "All categories" to show latest listings from everywhere.', 'directory' ); ?></span>
							</p>
							<p>
								<label><strong><?php esc_html_e( 'Number of listings', 'directory' ); ?></strong></label><br />
								<input type="number" min="1" max="20" step="1" name="<?php echo esc_attr( DIRECTORY_HOME_FEATURED_OPTION ); ?>[sections][<?php echo (int) $i; ?>][count]" value="<?php echo esc_attr( (int) $section['count'] ); ?>" />
							</p>
						</td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>

			<?php submit_button(); ?>
		</form>
	</div>
	<?php
}

/**
 * Render the featured sliders on the home page.
 */
function directory_render_home_featured_sections() {
	if ( ! function_exists( 'directory_get_home_featured_sections' ) ) {
		return;
	}

	$data     = directory_get_home_featured_sections();
	$sections = $data['sections'];

	$rel_fn = function_exists( 'directory_relative_url' ) ? 'directory_relative_url' : function( $u ) { return $u; };

	foreach ( $sections as $index => $config ) {
		$title   = trim( (string) $config['title'] );
		$cat_slug = trim( (string) $config['cat_slug'] );
		$count   = isset( $config['count'] ) ? (int) $config['count'] : 8;
		$count   = max( 1, min( 20, $count ) );

		$query_args = array(
			'post_type'      => 'gd_place',
			'post_status'    => 'publish',
			'posts_per_page' => $count,
			'orderby'        => 'date',
			'order'          => 'DESC',
		);

		if ( $cat_slug !== '' ) {
			$query_args['tax_query'] = array(
				array(
					'taxonomy' => 'gd_placecategory',
					'field'    => 'slug',
					'terms'    => $cat_slug,
				),
			);
		}

		$loop = new WP_Query( $query_args );
		if ( ! $loop->have_posts() ) {
			wp_reset_postdata();
			continue;
		}

		$slider_id = 'hf-' . ( $index + 1 );

		// Fixed public titles for first two sliders.
		$display_title = $title !== '' ? $title : __( 'Top picks', 'directory' );
		if ( $index === 0 ) {
			$display_title = __( 'Top Restaurants', 'directory' );
		} elseif ( $index === 1 ) {
			$display_title = __( 'Top Experiences', 'directory' );
		}
		?>
		<section class="fp__section fp__hf-section">
			<div class="fp__wrap">
				<header class="fp__hf-header">
					<div class="fp__hf-header-main">
						<h2 class="fp__section-title fp__hf-title">
							<?php echo esc_html( $display_title ); ?>
						</h2>
						<p class="fp__hf-subtitle">
							<?php esc_html_e( 'Highly rated places near you.', 'directory' ); ?>
						</p>
					</div>
				</header>
				<div class="fp__hf-slider" data-hf-slider="<?php echo esc_attr( $slider_id ); ?>">
					<button type="button" class="fp__hf-arrow fp__hf-prev" aria-label="<?php esc_attr_e( 'Previous featured listing', 'directory' ); ?>">
						<span aria-hidden="true">‹</span>
					</button>
					<div class="fp__hf-viewport">
						<div class="fp__hf-track">
							<?php
							$card_index = 0;
							while ( $loop->have_posts() ) :
								$loop->the_post();
								$pid   = get_the_ID();
								$thumb = get_the_post_thumbnail_url( $pid, 'large' );
								$link  = $rel_fn( get_the_permalink() );
								$rating_html = '';
								if ( function_exists( 'geodir_get_rating_stars' ) && function_exists( 'geodir_get_post_rating' ) ) {
									$post_rating = geodir_get_post_rating( $pid );
									if ( $post_rating !== '' && $post_rating !== null ) {
										$rating_html = geodir_get_rating_stars( $post_rating, $pid );
									}
								}
								$card_index++;
								?>
								<article class="fp__hf-card<?php echo $card_index === 1 ? ' is-active' : ''; ?>">
									<a href="<?php echo esc_url( $link ); ?>" class="fp__hf-card-link">
										<div class="fp__hf-card-img-wrap">
											<?php if ( $thumb ) : ?>
												<div class="fp__hf-card-img" style="background-image:url('<?php echo esc_url( $thumb ); ?>');"></div>
											<?php else : ?>
												<div class="fp__hf-card-img fp__hf-card-img--placeholder"></div>
											<?php endif; ?>
										</div>
										<?php
										$addr_parts = array();
										if ( function_exists( 'geodir_get_post_meta' ) ) {
											$addr_city   = geodir_get_post_meta( $pid, 'city', true );
											$addr_street = geodir_get_post_meta( $pid, 'street', true );
											if ( $addr_street ) {
												$addr_parts[] = $addr_street;
											}
											if ( $addr_city ) {
												$addr_parts[] = $addr_city;
											}
										}
										$addr_line = implode( ', ', $addr_parts );

										$primary_cat_name = '';
										$cat_terms        = get_the_terms( $pid, 'gd_placecategory' );
										if ( is_array( $cat_terms ) && ! empty( $cat_terms ) ) {
											$primary_cat_name = $cat_terms[0]->name;
										}
										?>
										<div class="fp__hf-card-body">
											<h3 class="fp__hf-card-title"><?php the_title(); ?></h3>
											<?php if ( $rating_html ) : ?>
												<div class="fp__hf-card-rating"><?php echo $rating_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
											<?php endif; ?>
											<?php if ( $primary_cat_name || $addr_line ) : ?>
												<div class="fp__hf-card-meta-row">
													<?php if ( $primary_cat_name ) : ?>
														<span class="fp__hf-pill"><?php echo esc_html( $primary_cat_name ); ?></span>
													<?php endif; ?>
													<?php if ( $addr_line ) : ?>
														<span class="fp__hf-pill fp__hf-pill--muted"><?php echo esc_html( $addr_line ); ?></span>
													<?php endif; ?>
												</div>
											<?php endif; ?>
										</div>
									</a>
								</article>
							<?php endwhile; ?>
						</div>
					</div>
					<button type="button" class="fp__hf-arrow fp__hf-next" aria-label="<?php esc_attr_e( 'Next featured listing', 'directory' ); ?>">
						<span aria-hidden="true">›</span>
					</button>
				</div>
				<div class="fp__hf-dots" data-hf-dots="<?php echo esc_attr( $slider_id ); ?>"></div>
			</div>
		</section>
		<?php
		wp_reset_postdata();
	}
}

