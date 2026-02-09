<?php


// Pattern filters
require_once 'includes/pattern-filters.php';
require_once 'includes/pattern-filters/menu.php';
require_once 'includes/pattern-filters/header.php';
require_once 'includes/pattern-filters/footer.php';
require_once 'includes/pattern-filters/hero.php';
require_once 'includes/pattern-filters/content.php';

// Custom GeoDirectory helpers (parent categories, category page layout, etc.).
require_once 'includes/geodir-parent-cats.php';
require_once 'includes/listing-plans.php';
require_once 'includes/testimonials.php';

// Register patterns
require_once 'includes/register-patterns.php';

// Downgrade functions
require_once 'includes/downgrade-functions.php';

/**
 * Return a relative URL (path + query) so the domain is not shown in header/footer markup.
 *
 * @param string $url Full URL.
 * @return string Relative path (e.g. / or /page/).
 */
function directory_relative_url( $url ) {
	if ( ! $url || ! is_string( $url ) ) {
		return '/';
	}
	$parsed = parse_url( $url );
	$path   = isset( $parsed['path'] ) && $parsed['path'] !== '' ? $parsed['path'] : '/';
	$query  = isset( $parsed['query'] ) && $parsed['query'] !== '' ? '?' . $parsed['query'] : '';
	return $path . $query;
}

/**
 * Site name for display in header/footer – strips temp URL (e.g. nzbusinesshub.tempurl.host) from blog name.
 *
 * @return string
 */
function directory_display_site_name() {
	$name = get_bloginfo( 'name' );
	$name = preg_replace( '/\s*nzbusinesshub\.tempurl\.host\s*/i', ' ', $name );
	$name = preg_replace( '/\s*[a-z0-9-]+\.tempurl\.host\s*/i', ' ', $name );
	return trim( preg_replace( '/\s+/', ' ', $name ) ) ?: get_bloginfo( 'name' );
}

/**
 * Loads the translation files for WordPress.
 *
 * @since 3.0.0
 */
function directory_theme_setup()
{
	load_child_theme_textdomain( 'directory', get_stylesheet_directory() . '/languages' );

	if ( is_admin() ) {
		// Theme admin stuff
		require_once 'includes/class-blockstrap-admin-child.php';
	}
}

add_action('after_setup_theme', 'directory_theme_setup');

/**
 * Ensure an "Upgrade" page exists for listing plans.
 * The page content uses the [directory_listing_plan_table] shortcode.
 */
function directory_ensure_upgrade_page_exists() {
	// Only run for admins to avoid creating pages for anonymous traffic.
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$upgrade_page = get_page_by_path( 'upgrade' );
	if ( $upgrade_page instanceof WP_Post ) {
		return;
	}

	$page_id = wp_insert_post(
		array(
			'post_title'   => 'Upgrade',
			'post_name'    => 'upgrade',
			'post_status'  => 'publish',
			'post_type'    => 'page',
			'post_content' => '[directory_listing_plan_table]',
		),
		true
	);

	// If it failed, do nothing; button will still fall back to /upgrade/.
	if ( is_wp_error( $page_id ) ) {
		return;
	}
}
add_action( 'init', 'directory_ensure_upgrade_page_exists' );

/**
 * Remove the block-based post author panel from single posts rendered
 * inside our custom layouts (we already show compact meta).
 */
function directory_strip_post_author_block( $block_content, $block ) {
	if ( empty( $block['blockName'] ) ) {
		return $block_content;
	}

	// Strip the core Post Author block entirely.
	if ( $block['blockName'] === 'core/post-author' ) {
		return '';
	}

	// If a Group block only contains a Post Author block, strip the group too.
	if ( $block['blockName'] === 'core/group' && ! empty( $block['innerBlocks'] ) ) {
		foreach ( $block['innerBlocks'] as $inner ) {
			if ( isset( $inner['blockName'] ) && $inner['blockName'] === 'core/post-author' ) {
				return '';
			}
		}
	}

	return $block_content;
}
add_filter( 'render_block', 'directory_strip_post_author_block', 12, 2 );

/**
 * Enqueue child-theme assets.
 */
function directory_theme_enqueue_assets() {
	if ( is_admin() ) {
		return;
	}
	$theme_version = wp_get_theme( get_template() )->get( 'Version' );

	// Carousel only on front page; load in footer with defer for faster parsing.
	if ( is_front_page() ) {
		wp_enqueue_script(
			'directory-home-categories-carousel',
			get_stylesheet_directory_uri() . '/assets/js/home-categories-carousel.js',
			array(),
			$theme_version,
			true
		);
	}
}
add_action( 'wp_enqueue_scripts', 'directory_theme_enqueue_assets' );

/**
 * Add defer to theme scripts to avoid render-blocking.
 */
function directory_script_loader_tag( $tag, $handle, $src ) {
	$defer_handles = array( 'directory-home-categories-carousel' );
	if ( in_array( $handle, $defer_handles, true ) ) {
		return str_replace( ' src=', ' defer src=', $tag );
	}
	return $tag;
}
add_filter( 'script_loader_tag', 'directory_script_loader_tag', 10, 3 );

/**
 * Enqueue standalone layout CSS for Business Categories page (outside BlockStrap).
 */
function directory_enqueue_business_categories_standalone() {
	if ( ! is_page( 'business-categories' ) ) {
		return;
	}
	$version = wp_get_theme( get_template() )->get( 'Version' );
	wp_enqueue_style(
		'directory-business-categories-standalone',
		get_stylesheet_directory_uri() . '/assets/css/business-categories-standalone.css',
		array(),
		$version
	);
}
add_action( 'wp_enqueue_scripts', 'directory_enqueue_business_categories_standalone', 25 );

/**
 * Force custom front-page.php when the site front page is requested.
 * Ensures the custom layout is used even if a block template or page content would otherwise show.
 */
function directory_force_custom_front_page_template( $template ) {
	if ( ! is_front_page() ) {
		return $template;
	}
	$custom = get_stylesheet_directory() . '/front-page.php';
	if ( file_exists( $custom ) ) {
		return $custom;
	}
	return $template;
}
add_filter( 'template_include', 'directory_force_custom_front_page_template', 99 );

/**
 * Force custom home.php when the blog (posts) page is requested.
 * Ensures the custom blog layout is used instead of BlockStrap block template.
 * Catches: (1) Settings > Reading "Posts page", (2) A Page with slug "blog" used as blog index.
 */
function directory_force_custom_blog_template( $template ) {
	if ( is_front_page() ) {
		return $template;
	}
	$use_custom_blog = is_home();
	if ( ! $use_custom_blog && is_page( 'blog' ) ) {
		$use_custom_blog = true;
	}
	if ( ! $use_custom_blog ) {
		return $template;
	}
	$custom = get_stylesheet_directory() . '/home.php';
	if ( file_exists( $custom ) ) {
		return $custom;
	}
	return $template;
}
add_filter( 'template_include', 'directory_force_custom_blog_template', 9999 );

/**
 * Force custom archive-gd_place.php for the businesses (gd_place) archive.
 * GeoDirectory normally loads its archive page template; this ensures our revamped layout is used.
 */
function directory_force_custom_gd_archive_template( $template ) {
	if ( ! is_post_type_archive( 'gd_place' ) ) {
		return $template;
	}
	$custom = get_stylesheet_directory() . '/archive-gd_place.php';
	if ( file_exists( $custom ) ) {
		return $custom;
	}
	return $template;
}
add_filter( 'template_include', 'directory_force_custom_gd_archive_template', 9999 );

/**
 * Force theme template single-gd_place.php for single business/listing pages.
 * Without this, GeoDirectory loads its configured "Details" page (BlockStrap), not the theme file.
 */
function directory_force_custom_gd_single_template( $template ) {
	if ( ! is_singular( 'gd_place' ) ) {
		return $template;
	}
	$custom = get_stylesheet_directory() . '/single-gd_place.php';
	if ( file_exists( $custom ) ) {
		return $custom;
	}
	return $template;
}
add_filter( 'template_include', 'directory_force_custom_gd_single_template', 9999 );

/**
 * Enqueue custom frontend CSS for entire site (all pages using custom PHP templates).
 */
function directory_enqueue_custom_frontend() {
	if ( is_admin() ) {
		return;
	}
	$version = wp_get_theme( get_template() )->get( 'Version' );
	wp_enqueue_style(
		'directory-custom-frontend',
		get_stylesheet_directory_uri() . '/assets/css/custom-frontend.css',
		array(),
		$version
	);
}
add_action( 'wp_enqueue_scripts', 'directory_enqueue_custom_frontend', 20 );

/**
 * Preload main frontend CSS for faster first paint (critical path).
 */
function directory_preload_custom_frontend_css() {
	if ( is_admin() || ! is_front_page() ) {
		return;
	}
	$version = wp_get_theme( get_template() )->get( 'Version' );
	$url     = get_stylesheet_directory_uri() . '/assets/css/custom-frontend.css';
	$url     = add_query_arg( 'ver', $version, $url );
	echo '<link rel="preload" href="' . esc_url( $url ) . '" as="style" />' . "\n";
}
add_action( 'wp_head', 'directory_preload_custom_frontend_css', 1 );

/**
 * Blog archive: 6 posts per page.
 */
function directory_blog_posts_per_page( $query ) {
	if ( ! is_admin() && $query->is_main_query() && $query->is_home() ) {
		$query->set( 'posts_per_page', 6 );
	}
}
add_action( 'pre_get_posts', 'directory_blog_posts_per_page' );
