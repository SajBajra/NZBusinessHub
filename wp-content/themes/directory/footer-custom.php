<?php
/**
 * Custom frontend footer – 4 columns + bottom bar (Quick Links, About, Recent Posts, contact).
 *
 * @package Directory
 */

$cf_footer_home    = directory_relative_url( home_url( '/' ) );
$cf_footer_blog_id = get_option( 'page_for_posts' );
$cf_footer_blog    = $cf_footer_blog_id ? directory_relative_url( get_permalink( $cf_footer_blog_id ) ) : $cf_footer_home;
$cf_footer_cats    = get_permalink( get_page_by_path( 'business-categories' ) );
$cf_footer_cats    = $cf_footer_cats ? directory_relative_url( $cf_footer_cats ) : $cf_footer_home;
$cf_footer_name    = function_exists( 'directory_display_site_name' ) ? directory_display_site_name() : get_bloginfo( 'name' );
$cf_footer_add     = function_exists( 'geodir_add_listing_page_url' ) ? geodir_add_listing_page_url() : home_url( '/' );
$cf_footer_add     = directory_relative_url( $cf_footer_add );
$cf_footer_account = function_exists( 'geodir_get_account_page_url' ) ? geodir_get_account_page_url() : home_url( '/' );
$cf_footer_account = directory_relative_url( $cf_footer_account );
$cf_footer_listings = $cf_footer_account;
$cf_email          = get_bloginfo( 'admin_email' );
$cf_phone          = get_theme_mod( 'cf_footer_phone', '+44 1010101010' );

// Quick links: top GeoDirectory categories or static list.
$cf_quick_links = array();
if ( function_exists( 'get_terms' ) ) {
	$cf_terms = get_terms( array( 'taxonomy' => 'gd_placecategory', 'parent' => 0, 'number' => 6, 'orderby' => 'name', 'hide_empty' => false ) );
	if ( ! is_wp_error( $cf_terms ) && ! empty( $cf_terms ) ) {
		foreach ( $cf_terms as $t ) {
			$term_url = get_term_link( $t );
			$cf_quick_links[] = array( 'name' => $t->name, 'url' => is_wp_error( $term_url ) ? $cf_footer_home : directory_relative_url( $term_url ) );
		}
	}
}
if ( empty( $cf_quick_links ) ) {
	$cf_quick_links = array(
		array( 'name' => __( 'Accommodation', 'directory' ), 'url' => $cf_footer_home ),
		array( 'name' => __( 'Food & Drink', 'directory' ), 'url' => $cf_footer_home ),
		array( 'name' => __( 'Shopping', 'directory' ), 'url' => $cf_footer_home ),
		array( 'name' => __( 'Art & History', 'directory' ), 'url' => $cf_footer_home ),
		array( 'name' => __( 'Entertainment', 'directory' ), 'url' => $cf_footer_home ),
		array( 'name' => __( 'Carsharing', 'directory' ), 'url' => $cf_footer_home ),
	);
}

$cf_recent = get_posts( array( 'numberposts' => 1, 'post_status' => 'publish', 'post_type' => 'post' ) );
$cf_footer_logo = directory_relative_url( content_url( 'uploads/2026/01/NZ-Directory-LOGO-3.png' ) );
?>
<footer class="cf-footer">
	<div class="cf-footer-top">
		<div class="cf-footer-inner">
			<div class="cf-footer-col cf-footer-brand">
				<a class="cf-footer-logo" href="<?php echo esc_url( $cf_footer_home ); ?>">
					<img src="<?php echo esc_url( $cf_footer_logo ); ?>" alt="<?php echo esc_attr( $cf_footer_name ); ?>" class="cf-footer-logo-img" />
					</a>
				<div class="cf-footer-contact">
					<a class="cf-footer-contact-item" href="mailto:<?php echo esc_attr( $cf_email ); ?>">
						<svg class="cf-footer-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
						<?php echo esc_html( $cf_email ); ?>
					</a>
					<span class="cf-footer-contact-item">
						<svg class="cf-footer-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
						<?php echo esc_html( $cf_phone ); ?>
					</span>
				</div>
			</div>
			<div class="cf-footer-col">
				<h3 class="cf-footer-heading"><?php esc_html_e( 'Quick Links', 'directory' ); ?></h3>
				<ul class="cf-footer-links">
					<?php foreach ( $cf_quick_links as $link ) : ?>
						<li><a href="<?php echo esc_url( $link['url'] ); ?>"><?php echo esc_html( $link['name'] ); ?></a></li>
					<?php endforeach; ?>
				</ul>
			</div>
			<div class="cf-footer-col">
				<h3 class="cf-footer-heading"><?php esc_html_e( 'About', 'directory' ); ?></h3>
				<ul class="cf-footer-links">
					<li><a href="<?php echo esc_url( $cf_footer_account ); ?>"><?php esc_html_e( 'My account', 'directory' ); ?></a></li>
					<li><a href="<?php echo esc_url( $cf_footer_home ); ?>"><?php esc_html_e( 'Wishlist', 'directory' ); ?></a></li>
					<li><a href="<?php echo esc_url( $cf_footer_listings ); ?>"><?php esc_html_e( 'My listings', 'directory' ); ?></a></li>
					<li><a href="<?php echo esc_url( $cf_footer_add ); ?>"><?php esc_html_e( 'Add listing', 'directory' ); ?></a></li>
					<li><a href="<?php echo esc_url( $cf_footer_blog ); ?>"><?php esc_html_e( 'News', 'directory' ); ?></a></li>
				</ul>
			</div>
			<div class="cf-footer-col cf-footer-recent">
				<h3 class="cf-footer-heading"><?php esc_html_e( 'Recent Posts', 'directory' ); ?></h3>
				<?php if ( ! empty( $cf_recent ) ) :
					$post = $cf_recent[0];
					setup_postdata( $post );
					$thumb = get_the_post_thumbnail_url( $post->ID, 'thumbnail' );
					?>
					<article class="cf-footer-post">
						<?php if ( $thumb ) : ?>
							<a href="<?php echo esc_url( directory_relative_url( get_permalink( $post ) ) ); ?>" class="cf-footer-post-img" style="background-image:url(<?php echo esc_url( $thumb ); ?>)"></a>
						<?php endif; ?>
						<div class="cf-footer-post-body">
							<h4 class="cf-footer-post-title"><a href="<?php echo esc_url( directory_relative_url( get_permalink( $post ) ) ); ?>"><?php echo esc_html( get_the_title( $post ) ); ?></a></h4>
							<p class="cf-footer-post-excerpt"><?php echo esc_html( wp_trim_words( get_the_excerpt( $post ), 12 ) ); ?></p>
							<div class="cf-footer-post-meta">
								<span><svg class="cf-footer-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg> <?php echo esc_html( get_the_date( '', $post ) ); ?></span>
								<span><svg class="cf-footer-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg> <?php echo absint( get_comments_number( $post ) ); ?> <?php echo esc_html( _n( 'comment', 'comments', get_comments_number( $post ), 'directory' ) ); ?></span>
							</div>
						</div>
					</article>
					<?php
					wp_reset_postdata();
				else : ?>
					<p class="cf-footer-no-posts"><?php esc_html_e( 'No posts yet.', 'directory' ); ?></p>
				<?php endif; ?>
			</div>
		</div>
	</div>
	<div class="cf-footer-bottom">
		<div class="cf-footer-bottom-inner">
			<span class="cf-footer-copy"><?php printf( esc_html__( 'Copyright © %s', 'directory' ), esc_html( gmdate( 'Y' ) ) ); ?></span>
			<div class="cf-footer-bottom-links">
				<a href="<?php echo esc_url( $cf_footer_home ); ?>"><?php esc_html_e( 'About', 'directory' ); ?></a>
				<a href="<?php echo esc_url( $cf_footer_blog ); ?>"><?php esc_html_e( 'Blog', 'directory' ); ?></a>
				<a href="<?php echo esc_url( $cf_footer_home ); ?>"><?php esc_html_e( 'Support', 'directory' ); ?></a>
				<a href="<?php echo esc_url( $cf_footer_home ); ?>"><?php esc_html_e( 'Contacts', 'directory' ); ?></a>
				<span class="cf-footer-social">
					<a href="#" aria-label="Facebook"><svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg></a>
					<a href="#" aria-label="Twitter"><svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg></a>
					<a href="#" aria-label="Telegram"><svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/></svg></a>
					<a href="#" aria-label="Chat"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg></a>
				</span>
			</div>
		</div>
	</div>
</footer>
<script>
(function() {
	var modal = document.getElementById('cf-location-modal');
	var trigger = document.getElementById('cf-set-location-trigger');
	var closeBtn = modal && modal.querySelector('[data-close-modal]');
	var form = modal && modal.querySelector('.cf-location-modal-form');
	var input = modal && modal.querySelector('#cf-location-input');
	var myLocationBtn = document.getElementById('cf-location-my-location');
	if (!modal || !trigger) return;

	function openModal() {
		modal.classList.add('is-open');
		modal.setAttribute('aria-hidden', 'false');
		trigger.setAttribute('aria-expanded', 'true');
		document.body.style.overflow = 'hidden';
		if (input) { input.value = ''; input.focus(); }
	}
	function closeModal() {
		modal.classList.remove('is-open');
		modal.setAttribute('aria-hidden', 'true');
		trigger.setAttribute('aria-expanded', 'false');
		document.body.style.overflow = '';
	}

	trigger.addEventListener('click', openModal);
	if (closeBtn) closeBtn.addEventListener('click', closeModal);
	modal.addEventListener('click', function(e) {
		if (e.target === modal) closeModal();
	});
	document.addEventListener('keydown', function(e) {
		if (e.key === 'Escape' && modal.classList.contains('is-open')) closeModal();
	});

	if (myLocationBtn && form) {
		myLocationBtn.addEventListener('click', function() {
			if (!navigator.geolocation) {
				if (input) input.placeholder = 'Geolocation not supported';
				return;
			}
			myLocationBtn.disabled = true;
			myLocationBtn.textContent = 'Getting location…';
			navigator.geolocation.getCurrentPosition(
				function(pos) {
					var url = form.getAttribute('data-businesses-url') || form.action;
					var sep = url.indexOf('?') !== -1 ? '&' : '?';
					window.location.href = url + sep + 'near=' + encodeURIComponent(pos.coords.latitude + ',' + pos.coords.longitude);
				},
				function() {
					myLocationBtn.disabled = false;
					myLocationBtn.innerHTML = '<svg class="cf-location-my-location-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 2L11 13M22 2l-7 20-4-9-9-4 20-7z"/></svg><span>Near: My Location</span>';
					if (input) input.placeholder = 'Location denied or unavailable';
				}
			);
		});
	}
})();
</script>
