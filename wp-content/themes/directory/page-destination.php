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
				// Use GeoDirectory listings shortcode with location filter inherited from the search URL.
				// We append the query args temporarily so gd_listings sees the same request vars.
				$original_get = $_GET;
				$_GET         = array_merge(
					$_GET,
					array(
						'geodir_search' => '1',
						'stype'         => 'gd_place',
						's'             => '+',
						'snear'         => '',
						'sgeo_lat'      => '',
						'sgeo_lon'      => '',
						'city'          => $city_slug,
					)
				);

				if ( function_exists( 'do_shortcode' ) ) {
					echo do_shortcode( '[gd_listings post_limit="12" add_location_filter="1" layout="3" with_pagination="true"]' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				}

				// Restore original request.
				$_GET = $original_get;
				?>
			</div>
		</section>
	</div>
</main>

<?php
get_footer( 'custom' );
require_once get_stylesheet_directory() . '/includes/custom-frontend-doc-end.php';

