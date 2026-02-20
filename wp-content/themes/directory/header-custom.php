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
$custom_profile_page = get_page_by_path( 'profile' ) ?: get_page_by_path( 'my-profile' );
$custom_profile_url  = $custom_profile_page ? directory_relative_url( get_permalink( $custom_profile_page ) ) : '';
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
		<button type="button" class="cf-nav-toggle" aria-label="<?php esc_attr_e( 'Toggle navigation', 'directory' ); ?>" aria-expanded="false" aria-controls="cf-nav-main">
			<span class="cf-nav-toggle-bar"></span>
			<span class="cf-nav-toggle-bar"></span>
			<span class="cf-nav-toggle-bar"></span>
		</button>
		<nav class="cf-nav" id="cf-nav-main">
			<div class="cf-nav-links">
				<button type="button" class="cf-set-location-trigger" id="cf-set-location-trigger" aria-haspopup="dialog" aria-expanded="false" aria-controls="cf-location-modal" aria-label="<?php esc_attr_e( 'Set location', 'directory' ); ?>">
					<span class="cf-set-location-icon" aria-hidden="true">
						<svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5a2.5 2.5 0 110-5 2.5 2.5 0 010 5z" fill="#3993d5"/></svg>
					</span>
					<span class="cf-set-location-text"><?php esc_html_e( 'Set Location', 'directory' ); ?></span>
					<span class="cf-set-location-divider" aria-hidden="true"></span>
				</button>
				<a href="<?php echo esc_url( $custom_businesses_url ); ?>"><?php esc_html_e( 'Businesses', 'directory' ); ?></a>
				<a href="<?php echo esc_url( $custom_categories_url ); ?>"><?php esc_html_e( 'Category', 'directory' ); ?></a>
				<a href="<?php echo esc_url( $custom_blog_url ); ?>"><?php esc_html_e( 'Blog', 'directory' ); ?></a>
			</div>
			<div class="cf-actions">
				<?php if ( is_user_logged_in() ) : ?>
					<div class="cf-profile-desktop">
						<div class="cf-profile-dropdown" id="cf-profile-dropdown">
							<button type="button" class="cf-profile-dropdown-trigger" id="cf-profile-dropdown-trigger" aria-expanded="false" aria-haspopup="true" aria-controls="cf-profile-dropdown-menu" aria-label="<?php esc_attr_e( 'Account menu', 'directory' ); ?>">
								<span class="cf-profile-dropdown-icon" aria-hidden="true">
									<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
								</span>
							</button>
							<div class="cf-profile-dropdown-menu" id="cf-profile-dropdown-menu" role="menu" aria-label="<?php esc_attr_e( 'Account options', 'directory' ); ?>">
								<?php if ( $custom_profile_url ) : ?>
									<a class="cf-profile-dropdown-item" href="<?php echo esc_url( $custom_profile_url ); ?>" role="menuitem">
										<span class="cf-profile-dropdown-item-icon" aria-hidden="true">
											<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
										</span>
										<?php esc_html_e( 'Profile', 'directory' ); ?>
									</a>
								<?php endif; ?>
								<a class="cf-profile-dropdown-item" href="<?php echo esc_url( $custom_logout_url ); ?>" role="menuitem">
									<span class="cf-profile-dropdown-item-icon" aria-hidden="true">
										<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
									</span>
									<?php esc_html_e( 'Sign out', 'directory' ); ?>
								</a>
							</div>
						</div>
					</div>
					<div class="cf-profile-mobile">
						<?php if ( $custom_profile_url ) : ?>
							<a class="cf-link-sign" href="<?php echo esc_url( $custom_profile_url ); ?>">
								<span class="cf-icon-user" aria-hidden="true">
									<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
								</span>
								<?php esc_html_e( 'Profile', 'directory' ); ?>
							</a>
						<?php endif; ?>
						<a class="cf-link-sign" href="<?php echo esc_url( $custom_logout_url ); ?>">
							<span class="cf-icon-user" aria-hidden="true">
								<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
							</span>
							<?php esc_html_e( 'Sign out', 'directory' ); ?>
						</a>
					</div>
				<?php else : ?>
					<button
						type="button"
						class="cf-link-sign"
						data-bs-toggle="modal"
						data-bs-target="#cf-auth-modal"
						data-auth-modal-tab="login"
						aria-label="<?php esc_attr_e( 'Sign in', 'directory' ); ?>"
					>
						<span class="cf-icon-user" aria-hidden="true">
							<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
						</span>
						<?php esc_html_e( 'Sign in', 'directory' ); ?>
					</button>
				<?php endif; ?>
				<a class="cf-btn-add" href="<?php echo esc_url( $custom_add_listing_url ); ?>">
					<span class="cf-icon-plus" aria-hidden="true">
						<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
					</span>
					<?php esc_html_e( 'Add listing', 'directory' ); ?>
				</a>
			</div>
		</nav>
	</div>
</header>

<div class="cf-location-modal-overlay" id="cf-location-modal" role="dialog" aria-modal="true" aria-labelledby="cf-location-modal-title" aria-hidden="true">
	<div class="cf-location-modal">
		<div class="cf-location-modal-header">
			<h2 class="cf-location-modal-title" id="cf-location-modal-title"><?php esc_html_e( 'Change Location', 'directory' ); ?></h2>
			<button type="button" class="cf-location-modal-close" aria-label="<?php esc_attr_e( 'Close', 'directory' ); ?>" data-close-modal>&times;</button>
		</div>
		<p class="cf-location-modal-subtitle"><?php esc_html_e( 'Find awesome listings near you!', 'directory' ); ?></p>
		<div class="cf-location-modal-search">
			<?php if ( function_exists( 'do_shortcode' ) ) : ?>
				<?php echo do_shortcode( '[gd_search hide_search_input="true" hide_near_input="false" input_size="lg" bar_flex_wrap="flex-wrap" bar_flex_wrap_md="flex-md-nowrap" bar_flex_wrap_lg="flex-lg-nowrap"]' ); ?>
			<?php endif; ?>
		</div>
	</div>
</div>

<script>
(function() {
	var dropdown = document.getElementById('cf-profile-dropdown');
	var trigger = document.getElementById('cf-profile-dropdown-trigger');
	var menu = document.getElementById('cf-profile-dropdown-menu');
	if (!dropdown || !trigger || !menu) return;
	function open() {
		dropdown.classList.add('is-open');
		trigger.setAttribute('aria-expanded', 'true');
	}
	function close() {
		dropdown.classList.remove('is-open');
		trigger.setAttribute('aria-expanded', 'false');
	}
	trigger.addEventListener('click', function(e) {
		e.preventDefault();
		e.stopPropagation();
		dropdown.classList.contains('is-open') ? close() : open();
	});
	document.addEventListener('click', function() { close(); });
	dropdown.addEventListener('click', function(e) { e.stopPropagation(); });
})();
</script>
<style>
@media (max-width: 1024px) {
	body.custom-frontend .cf-profile-desktop {
		display: none !important;
	}
	body.custom-frontend .cf-profile-mobile {
		display: flex !important;
		flex-direction: column;
		width: 100%;
		gap: 0.45rem;
	}
}
</style>
