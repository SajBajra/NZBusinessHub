<?php
/**
 * Destination landing page – per-city listing page.
 *
 * Each active "Destinations nearby" item gets its own pretty URL like /auckland,
 * using this template to show listings for that destination.
 *
 * @package Directory
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once get_stylesheet_directory() . '/includes/custom-frontend-doc-start.php';
get_header( 'custom' );

$home_url = function_exists( 'directory_relative_url' ) ? directory_relative_url( home_url( '/' ) ) : home_url( '/' );
$dest_url = '';

// Current page slug.
$slug = get_post_field( 'post_name', get_queried_object_id() );

// Find matching destination config so we can get the nice name.
$dest_name = ucwords( str_replace( '-', ' ', $slug ) );

if ( function_exists( 'directory_get_home_destinations' ) ) {
	$data         = directory_get_home_destinations();
	$destinations = $data['destinations'];
	foreach ( $destinations as $dest ) {
		$conf_slug = isset( $dest['slug'] ) && $dest['slug'] !== '' ? $dest['slug'] : sanitize_title( $dest['name'] );
		if ( $conf_slug === $slug ) {
			$dest_name = $dest['name'];
			break;
		}
	}
}

// Build a GeoDirectory search URL for this destination – we will link to it if needed.
$city_slug = $slug;
$search_url = add_query_arg(
	array(
		'geodir_search' => '1',
		'stype'         => 'gd_place',
		's'             => '+',
		'snear'         => '',
		'sgeo_lat'      => '',
		'sgeo_lon'      => '',
		'city'          => $city_slug,
	),
	$home_url
);
?>

<main class="custom-frontend-main cf-single-place cf-destination-main" id="main">
	<div class="cf-single-place-inner cf-destination-inner">
		<nav class="cf-single-place-breadcrumb" aria-label="<?php esc_attr_e( 'Breadcrumb', 'directory' ); ?>">
			<a href="<?php echo esc_url( $home_url ); ?>"><?php esc_html_e( 'Home', 'directory' ); ?></a>
			<span class="cf-single-place-breadcrumb-sep" aria-hidden="true">›</span>
			<span class="cf-single-place-breadcrumb-current"><?php esc_html_e( 'Destinations', 'directory' ); ?></span>
			<span class="cf-single-place-breadcrumb-sep" aria-hidden="true">›</span>
			<span class="cf-single-place-breadcrumb-current"><?php echo esc_html( $dest_name ); ?></span>
		</nav>

		<header class="cf-single-place-header">
			<div class="cf-single-place-header-text">
				<p class="cf-single-place-cat-badge">
					<?php esc_html_e( 'Destination', 'directory' ); ?>
				</p>
				<h1 class="cf-single-place-title">
					<?php
					printf(
						/* translators: %s: destination name */
						esc_html__( 'Destination: %s', 'directory' ),
						esc_html( $dest_name )
					);
					?>
				</h1>
				<p class="cf-single-place-header-subline">
					<?php esc_html_e( 'Browse businesses and listings in this area.', 'directory' ); ?>
				</p>
			</div>
		</header>

		<section class="cf-single-place-section cf-single-place-overview" aria-label="<?php esc_attr_e( 'Listings', 'directory' ); ?>">
			<h2 class="cf-single-place-section-title">
				<?php esc_html_e( 'Listings in this destination', 'directory' ); ?>
			</h2>
			<div class="cf-single-place-content entry-content">
				<?php
				// Query gd_place listings filtered by this destination (city/region) via GeoDirectory detail table.
				global $wpdb;

				$gd_table = ( function_exists( 'geodir_db_cpt_table' ) ? geodir_db_cpt_table( 'gd_place' ) : '' );
				if ( ! is_string( $gd_table ) || $gd_table === '' ) {
					$gd_table = '';
				}

				$directory_dest_join = function ( $join, $query ) use ( $gd_table ) {
					global $wpdb;
					if ( ! $gd_table || ! ( $query instanceof WP_Query ) ) {
						return $join;
					}
					$qv = $query->get( 'post_type' );
					if ( $qv !== 'gd_place' && ( ! is_array( $qv ) || ! in_array( 'gd_place', $qv, true ) ) ) {
						return $join;
					}
					if ( strpos( $join, $gd_table ) !== false ) {
						return $join;
					}
					$join .= " INNER JOIN {$gd_table} AS directory_gd_detail ON directory_gd_detail.post_id = {$wpdb->posts}.ID ";
					return $join;
				};

				$directory_dest_where = function ( $where, $query ) use ( $gd_table, $dest_name ) {
					global $wpdb;
					if ( ! $gd_table || ! ( $query instanceof WP_Query ) ) {
						return $where;
					}
					$qv = $query->get( 'post_type' );
					if ( $qv !== 'gd_place' && ( ! is_array( $qv ) || ! in_array( 'gd_place', $qv, true ) ) ) {
						return $where;
					}
					$where .= $wpdb->prepare( " AND directory_gd_detail.city = %s ", $dest_name );
					return $where;
				};

				add_filter( 'posts_join', $directory_dest_join, 10, 2 );
				add_filter( 'posts_where', $directory_dest_where, 10, 2 );

				$gd_dest_paged = max( 1, (int) get_query_var( 'paged' ) );
				if ( $gd_dest_paged < 1 && (int) get_query_var( 'page' ) > 0 ) {
					$gd_dest_paged = (int) get_query_var( 'page' );
				}

				$gd_dest_query = new WP_Query( array(
					'post_type'              => 'gd_place',
					'post_status'            => 'publish',
					'posts_per_page'         => 12,
					'paged'                  => $gd_dest_paged,
					'update_post_meta_cache' => false,
					'orderby'                => 'title',
					'order'                  => 'ASC',
				) );

				remove_filter( 'posts_join', $directory_dest_join, 10 );
				remove_filter( 'posts_where', $directory_dest_where, 10 );

				$gd_dest_total = (int) $gd_dest_query->found_posts;

				if ( $gd_dest_query->have_posts() ) :
					?>
					<div class="cf-gd-cards cf-gd-cards-grid">
						<?php
						$gd_card_index = 0;
						while ( $gd_dest_query->have_posts() ) :
							$gd_dest_query->the_post();
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
					$wp_query      = $gd_dest_query; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
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
						<p class="cf-gd-empty-title"><?php esc_html_e( 'No listings in this destination', 'directory' ); ?></p>
						<p class="cf-gd-empty"><?php esc_html_e( 'Try another destination or check back later.', 'directory' ); ?></p>
					</div>
				<?php endif; ?>
			</div>
		</section>
	</div>
</main>

<?php
get_footer( 'custom' );
require_once get_stylesheet_directory() . '/includes/custom-frontend-doc-end.php';

