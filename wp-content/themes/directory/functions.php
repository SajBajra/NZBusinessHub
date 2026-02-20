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
require_once 'includes/geodir-category-cms.php';
require_once 'includes/listing-plans.php';
require_once 'includes/admin-listing-contact-fields.php';
require_once 'includes/testimonials.php';
require_once 'includes/home-featured-sections.php';
require_once 'includes/home-destinations.php';

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
 * Ensure a "Profile" page exists for signed-in users (user details + my listings).
 * Uses the User Profile page template.
 */
function directory_ensure_profile_page_exists() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$profile_page = get_page_by_path( 'profile' );
	if ( $profile_page instanceof WP_Post ) {
		return;
	}

	$page_id = wp_insert_post(
		array(
			'post_title'   => __( 'Profile', 'directory' ),
			'post_name'    => 'profile',
			'post_status'  => 'publish',
			'post_type'    => 'page',
			'post_content' => '',
		),
		true
	);

	if ( is_wp_error( $page_id ) ) {
		return;
	}

	update_post_meta( $page_id, '_wp_page_template', 'page-profile.php' );
}
add_action( 'init', 'directory_ensure_profile_page_exists' );

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
 * Always show the GeoDirectory claim listing button, even for already claimed
 * listings or owner views. The underlying claim plugin will still handle any
 * permission/validation, but the UI trigger is always visible.
 */
function directory_always_show_claim_link( $show, $post_id ) {
	// Force the "claim" button/link to display on all listings.
	return true;
}
add_filter( 'geodir_claim_show_claim_link', 'directory_always_show_claim_link', 20, 2 );

/**
 * Force GeoDirectory Claim Listings to allow claims for gd_place post type
 * even if the "Is Claimed" custom field is not configured in the admin UI.
 *
 * This bypasses the plugin's internal check in GeoDir_Claim_Post::post_type_claim_allowed().
 */
function directory_force_gd_place_claim_allowed( $allow, $post_type ) {
	if ( $post_type === 'gd_place' ) {
		return true;
	}

	return $allow;
}
add_filter( 'geodir_claim_post_type_claim_allowed', 'directory_force_gd_place_claim_allowed', 10, 2 );

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
		wp_enqueue_script(
			'directory-home-featured-sliders',
			get_stylesheet_directory_uri() . '/assets/js/home-featured-sliders.js',
			array(),
			$theme_version,
			true
		);
		wp_enqueue_script(
			'directory-home-destinations-carousel',
			get_stylesheet_directory_uri() . '/assets/js/home-destinations-carousel.js',
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
	$defer_handles = array( 'directory-home-categories-carousel', 'directory-home-featured-sliders', 'directory-home-destinations-carousel' );
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
 * Force custom template for the GeoDirectory Add Listing page.
 */
function directory_force_add_listing_template( $template ) {
	if ( ! function_exists( 'geodir_add_listing_page_id' ) ) {
		return $template;
	}

	$add_page_id = geodir_add_listing_page_id( 'gd_place' );
	if ( $add_page_id && is_page( $add_page_id ) ) {
		$custom = get_stylesheet_directory() . '/page-add-listing.php';
		if ( file_exists( $custom ) ) {
			return $custom;
		}
	}

	return $template;
}
add_filter( 'template_include', 'directory_force_add_listing_template', 9999 );

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
 * Preload LCP image (hero globe) on front page to improve Largest Contentful Paint.
 */
function directory_preload_front_page_lcp() {
	if ( ! is_front_page() ) {
		return;
	}
	$hero_globe  = content_url( '/uploads/2026/02/Group-394831.png' );
	$hero_pattern = content_url( '/uploads/2026/02/bg-pattern.png' );
	echo '<link rel="preload" href="' . esc_url( $hero_globe ) . '" as="image" fetchpriority="high">' . "\n";
	echo '<link rel="preload" href="' . esc_url( $hero_pattern ) . '" as="image" fetchpriority="low">' . "\n";
}
add_action( 'wp_head', 'directory_preload_front_page_lcp', 2 );

/**
 * Inline critical hero CSS on front page so LCP (title or hero image) can paint without waiting for full stylesheet.
 */
function directory_inline_critical_hero_css() {
	if ( ! is_front_page() ) {
		return;
	}
	$css = '
.fp__hero{position:relative;background-color:#f0f7ff;padding:3rem 1.5rem 4rem;text-align:center;overflow:hidden}
.fp__hero-in{position:relative;z-index:1;max-width:720px;margin:0 auto}
.fp__hero-title{margin:0 0 .75rem;font-size:clamp(1.75rem,4.5vw,2.5rem);font-weight:700;line-height:1.2;color:#111827}
.fp__hero-desc{margin:0 0 1.5rem;font-size:1.0625rem;color:#475569}
.fp__hero-globe-wrap{position:absolute;inset:0;display:flex;align-items:center;justify-content:center;pointer-events:none}
.fp__hero-globe{max-width:min(98vw,1200px);width:100%;height:auto;object-fit:contain}
';
	echo '<style id="directory-critical-hero">' . wp_strip_all_tags( $css ) . '</style>' . "\n";
}
add_action( 'wp_head', 'directory_inline_critical_hero_css', 3 );

/**
 * Blog archive: 6 posts per page.
 */
function directory_blog_posts_per_page( $query ) {
	if ( ! is_admin() && $query->is_main_query() && $query->is_home() ) {
		$query->set( 'posts_per_page', 6 );
	}
}
add_action( 'pre_get_posts', 'directory_blog_posts_per_page' );

/**
 * Custom login message for GeoDirectory Add Listing widget.
 * Replaces the default "You must login to post." text with a friendlier message.
 */
function directory_add_listing_login_message_atts( $out, $pairs, $atts, $shortcode ) {
	if ( $shortcode !== 'gd_add_listing' ) {
		return $out;
	}

	// Only override if not explicitly set in shortcode/widget.
	if ( empty( $atts['login_msg'] ) ) {
		$out['login_msg'] = __( 'To add a business listing, please sign in or create a free account.', 'directory' );
	}

	return $out;
}
add_filter( 'shortcode_atts_gd_add_listing', 'directory_add_listing_login_message_atts', 10, 4 );

/**
 * Fallback: globally override GeoDirectory's "You must login to post." text.
 * Ensures the Add Listing notice uses our friendlier copy even if a widget/block sets its own login_msg.
 */
function directory_override_geodirectory_login_text( $translated, $text, $domain ) {
	if ( $domain === 'geodirectory' && $text === 'You must login to post.' ) {
		return __( 'To add a business listing, please sign in or create a free account.', 'directory' );
	}

	return $translated;
}
add_filter( 'gettext', 'directory_override_geodirectory_login_text', 10, 3 );

/**
 * Build a table of contents for a post from its H2 headings and return both
 * the TOC markup and updated content with anchor IDs injected.
 *
 * @param int|WP_Post|null $post Post ID or object (defaults to current post).
 * @return array{toc:string,content:string}
 */
function directory_get_post_toc_and_content( $post = null ) {
	$post = get_post( $post );
	if ( ! $post instanceof WP_Post ) {
		return array(
			'toc'     => '',
			'content' => '',
		);
	}

	$content = apply_filters( 'the_content', $post->post_content );

	$pattern    = '/(<h2\b[^>]*>)(.*?)(<\/h2>)/i';
	$toc_items  = array();
	$used_ids   = array();
	$updated    = preg_replace_callback(
		$pattern,
		function ( $matches ) use ( &$toc_items, &$used_ids ) {
			$open_tag  = $matches[1];
			$heading   = $matches[2];
			$close_tag = $matches[3];

			$text = wp_strip_all_tags( $heading );
			if ( $text === '' ) {
				return $matches[0];
			}

			$base_id = sanitize_title( $text );
			if ( $base_id === '' ) {
				$base_id = 'section';
			}
			$id    = $base_id;
			$count = 2;
			while ( in_array( $id, $used_ids, true ) ) {
				$id = $base_id . '-' . $count;
				$count++;
			}
			$used_ids[] = $id;

			$toc_items[] = array(
				'id'    => $id,
				'title' => $text,
			);

			if ( strpos( $open_tag, 'id=' ) === false ) {
				$open_tag = rtrim( substr( $open_tag, 0, -1 ) ) . ' id="' . esc_attr( $id ) . '">';
			}

			return $open_tag . $heading . $close_tag;
		},
		$content
	);

	if ( empty( $toc_items ) ) {
		return array(
			'toc'     => '',
			'content' => $content,
		);
	}

	ob_start();
	?>
	<nav class="cf-blog-single-toc" aria-label="<?php esc_attr_e( 'Table of contents', 'directory' ); ?>">
		<h2 class="cf-blog-single-toc-title"><?php esc_html_e( 'Table of contents', 'directory' ); ?></h2>
		<ol class="cf-blog-single-toc-list">
			<?php foreach ( $toc_items as $item ) : ?>
				<li class="cf-blog-single-toc-item">
					<a href="#<?php echo esc_attr( $item['id'] ); ?>" class="cf-blog-single-toc-link">
						<?php echo esc_html( $item['title'] ); ?>
					</a>
				</li>
			<?php endforeach; ?>
		</ol>
	</nav>
	<?php
	$toc_html = ob_get_clean();

	return array(
		'toc'     => $toc_html,
		'content' => $updated,
	);
}

/**
 * Handle newsletter subscription form submissions from the frontend.
 * Stores subscribers in a single option as an associative array keyed by email.
 */
function directory_handle_newsletter_subscribe() {
	if ( ! isset( $_POST['directory_newsletter_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['directory_newsletter_nonce'] ) ), 'directory_newsletter_subscribe' ) ) {
		wp_die( esc_html__( 'Security check failed.', 'directory' ) );
	}

	$email_raw = isset( $_POST['directory_newsletter_email'] ) ? wp_unslash( $_POST['directory_newsletter_email'] ) : '';
	$email     = sanitize_email( $email_raw );

	$redirect = isset( $_POST['directory_newsletter_redirect'] ) ? esc_url_raw( wp_unslash( $_POST['directory_newsletter_redirect'] ) ) : home_url( '/' );

	if ( ! is_email( $email ) ) {
		$redirect = add_query_arg( 'newsletter_status', 'invalid_email', $redirect );
		wp_safe_redirect( $redirect );
		exit;
	}

	$subscribers = get_option( 'directory_newsletter_subscribers', array() );
	if ( ! is_array( $subscribers ) ) {
		$subscribers = array();
	}

	$key   = strtolower( $email );
	$time  = current_time( 'mysql' );
	$status = 'subscribed';

	if ( isset( $subscribers[ $key ] ) ) {
		$status = 'already';
	} else {
		$subscribers[ $key ] = array(
			'email'         => $email,
			'subscribed_at' => $time,
		);
		update_option( 'directory_newsletter_subscribers', $subscribers );
	}

	$redirect = add_query_arg( 'newsletter_status', $status, $redirect );
	wp_safe_redirect( $redirect );
	exit;
}
add_action( 'admin_post_nopriv_directory_newsletter_subscribe', 'directory_handle_newsletter_subscribe' );
add_action( 'admin_post_directory_newsletter_subscribe', 'directory_handle_newsletter_subscribe' );

/**
 * Render the newsletter signup section for single blog posts.
 *
 * @param string $redirect_url URL to redirect back to after submission.
 */
function directory_render_newsletter_section( $redirect_url = '' ) {
	if ( ! is_singular( 'post' ) ) {
		return;
	}

	if ( ! $redirect_url ) {
		$redirect_url = get_permalink();
	}

	$status  = isset( $_GET['newsletter_status'] ) ? sanitize_key( wp_unslash( $_GET['newsletter_status'] ) ) : '';
	$message = '';
	$type    = '';

	if ( $status === 'subscribed' ) {
		$message = __( 'Thanks for subscribing! Please check your inbox.', 'directory' );
		$type    = 'success';
	} elseif ( $status === 'already' ) {
		$message = __( 'You are already subscribed with this email address.', 'directory' );
		$type    = 'info';
	} elseif ( $status === 'invalid_email' ) {
		$message = __( 'Please enter a valid email address.', 'directory' );
		$type    = 'error';
	}

	?>
	<section class="cf-blog-single-newsletter" aria-labelledby="cf-blog-newsletter-title">
		<div class="cf-blog-single-newsletter-inner">
			<div class="cf-blog-single-newsletter-copy">
				<h2 id="cf-blog-newsletter-title" class="cf-blog-single-newsletter-title">
					<?php esc_html_e( 'Stay in the loop', 'directory' ); ?>
				</h2>
				<p class="cf-blog-single-newsletter-text">
					<?php esc_html_e( 'Get the latest guides, stories, and tips from NZ Business Hub straight to your inbox.', 'directory' ); ?>
				</p>
			</div>
			<div class="cf-blog-single-newsletter-form-wrap">
				<?php if ( $message ) : ?>
					<div class="cf-blog-single-newsletter-message cf-blog-single-newsletter-message--<?php echo esc_attr( $type ); ?>">
						<?php echo esc_html( $message ); ?>
					</div>
				<?php endif; ?>
				<form class="cf-blog-single-newsletter-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<label for="directory-newsletter-email" class="cf-blog-single-newsletter-label">
						<?php esc_html_e( 'Email address', 'directory' ); ?>
					</label>
					<div class="cf-blog-single-newsletter-fields">
						<input
							type="email"
							name="directory_newsletter_email"
							id="directory-newsletter-email"
							class="cf-blog-single-newsletter-input"
							required
							autocomplete="email"
							placeholder="<?php esc_attr_e( 'you@example.com', 'directory' ); ?>"
						/>
						<button type="submit" class="cf-blog-single-newsletter-submit">
							<?php esc_html_e( 'Subscribe', 'directory' ); ?>
						</button>
					</div>
					<input type="hidden" name="action" value="directory_newsletter_subscribe" />
					<input type="hidden" name="directory_newsletter_redirect" value="<?php echo esc_attr( $redirect_url ); ?>" />
					<?php wp_nonce_field( 'directory_newsletter_subscribe', 'directory_newsletter_nonce' ); ?>
					<p class="cf-blog-single-newsletter-footnote">
						<?php esc_html_e( 'We respect your privacy. Unsubscribe anytime.', 'directory' ); ?>
					</p>
				</form>
			</div>
		</div>
	</section>
	<?php
}

/**
 * Register a simple admin page to list newsletter subscribers.
 */
function directory_register_newsletter_admin_page() {
	add_menu_page(
		__( 'Newsletter signups', 'directory' ),
		__( 'Newsletter signups', 'directory' ),
		'manage_options',
		'directory-newsletter',
		'directory_render_newsletter_admin_page',
		'dashicons-email-alt2',
		60
	);
}
add_action( 'admin_menu', 'directory_register_newsletter_admin_page' );

/**
 * Render the admin page table of newsletter subscribers stored in options.
 */
function directory_render_newsletter_admin_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$subscribers = get_option( 'directory_newsletter_subscribers', array() );
	if ( ! is_array( $subscribers ) ) {
		$subscribers = array();
	}

	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Newsletter signups', 'directory' ); ?></h1>
		<p><?php esc_html_e( 'These email addresses have subscribed via the single blog page newsletter form.', 'directory' ); ?></p>
		<?php if ( empty( $subscribers ) ) : ?>
			<p><?php esc_html_e( 'No subscribers yet.', 'directory' ); ?></p>
		<?php else : ?>
			<table class="widefat striped">
				<thead>
					<tr>
						<th scope="col"><?php esc_html_e( 'Email', 'directory' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Subscribed at', 'directory' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $subscribers as $row ) : ?>
						<tr>
							<td><?php echo isset( $row['email'] ) ? esc_html( $row['email'] ) : ''; ?></td>
							<td><?php echo isset( $row['subscribed_at'] ) ? esc_html( $row['subscribed_at'] ) : ''; ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>
	</div>
	<?php
}
