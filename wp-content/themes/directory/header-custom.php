<?php
/**
 * Custom frontend header – matches design: logo with icon, nav, Sign in, Add listing.
 *
 * @package Directory
 */

$custom_home_url    = directory_relative_url( home_url( '/' ) );
$custom_businesses_url = function_exists( 'get_post_type_archive_link' ) ? get_post_type_archive_link( 'gd_place' ) : home_url( '/' );
if ( ! $custom_businesses_url ) {
	$custom_businesses_url = $custom_home_url;
} else {
	$custom_businesses_url = directory_relative_url( $custom_businesses_url );
}
$custom_categories_url = get_permalink( get_page_by_path( 'business-categories' ) );
if ( ! $custom_categories_url ) {
	$custom_categories_url = $custom_home_url;
} else {
	$custom_categories_url = directory_relative_url( $custom_categories_url );
}
$custom_blog_page_id = get_option( 'page_for_posts' );
$custom_blog_url     = $custom_blog_page_id ? get_permalink( $custom_blog_page_id ) : home_url( '/' );
$custom_blog_url     = directory_relative_url( $custom_blog_url );
$custom_add_listing_url = function_exists( 'geodir_add_listing_page_url' ) ? geodir_add_listing_page_url() : home_url( '/' );
$custom_add_listing_url = directory_relative_url( $custom_add_listing_url );
$custom_site_name    = function_exists( 'directory_display_site_name' ) ? directory_display_site_name() : get_bloginfo( 'name' );
$custom_logout_url   = directory_relative_url( wp_logout_url( get_permalink() ) );
$custom_login_url    = directory_relative_url( wp_login_url( get_permalink() ) );
$custom_logo_id      = get_theme_mod( 'custom_logo' );
$custom_default_logo = directory_relative_url( content_url( 'uploads/2026/01/nz-Business-Hub-1.png' ) );
$custom_logo_src    = '';
if ( $custom_logo_id ) {
	$custom_logo_src = wp_get_attachment_image_url( $custom_logo_id, 'medium' );
	if ( $custom_logo_src && function_exists( 'directory_relative_url' ) ) {
		$custom_logo_src = directory_relative_url( $custom_logo_src );
	}
}
?>
<header class="cf-header">
	<div class="cf-header-inner">
		<a class="cf-logo" href="<?php echo esc_url( $custom_home_url ); ?>">
			<?php if ( $custom_logo_src ) : ?>
				<img src="<?php echo esc_url( $custom_logo_src ); ?>" alt="<?php echo esc_attr( $custom_site_name ); ?>" class="cf-logo-img" />
			<?php else : ?>
				<img src="<?php echo esc_url( $custom_default_logo ); ?>" alt="<?php echo esc_attr( $custom_site_name ); ?>" class="cf-logo-img" />
			<?php endif; ?>
				</a>
		<nav class="cf-nav">
			<a href="<?php echo esc_url( $custom_businesses_url ); ?>"><?php esc_html_e( 'Businesses', 'directory' ); ?></a>
			<a href="<?php echo esc_url( $custom_categories_url ); ?>"><?php esc_html_e( 'Category', 'directory' ); ?></a>
			<a href="<?php echo esc_url( $custom_blog_url ); ?>"><?php esc_html_e( 'Blog', 'directory' ); ?></a>
		</nav>
		<div class="cf-actions">
			<?php if ( is_user_logged_in() ) : ?>
				<a class="cf-link-sign" href="<?php echo esc_url( $custom_logout_url ); ?>">
					<span class="cf-icon-user" aria-hidden="true">
						<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
					</span>
					<?php esc_html_e( 'Sign out', 'directory' ); ?>
				</a>
			<?php else : ?>
				<a class="cf-link-sign" href="<?php echo esc_url( $custom_login_url ); ?>">
					<span class="cf-icon-user" aria-hidden="true">
						<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
					</span>
					<?php esc_html_e( 'Sign in', 'directory' ); ?>
				</a>
			<?php endif; ?>
			<a class="cf-btn-add" href="<?php echo esc_url( $custom_add_listing_url ); ?>">
				<span class="cf-icon-plus" aria-hidden="true">
					<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
				</span>
				<?php esc_html_e( 'Add listing', 'directory' ); ?>
			</a>
		</div>
	</div>
</header>
