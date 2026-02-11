<?php
/**
 * Template Name: User Profile
 * For signed-in users: shows profile details and their listed businesses (card layout).
 *
 * @package Directory
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Redirect to login if not signed in.
if ( ! is_user_logged_in() ) {
	wp_safe_redirect( wp_login_url( get_permalink() ) );
	exit;
}

require_once get_stylesheet_directory() . '/includes/custom-frontend-doc-start.php';
get_header( 'custom' );

$profile_user_id = get_current_user_id();
$profile_user    = get_userdata( $profile_user_id );
$profile_name    = $profile_user ? $profile_user->display_name : '';
$profile_email   = $profile_user ? $profile_user->user_email : '';
$profile_avatar  = get_avatar_url( $profile_user_id, array( 'size' => 96 ) );
$profile_url     = get_edit_user_link( $profile_user_id );
if ( $profile_url && function_exists( 'directory_relative_url' ) ) {
	$profile_url = directory_relative_url( $profile_url );
}

// Current user's gd_place listings (any status for owner: publish, draft, pending).
$profile_listings = new WP_Query( array(
	'post_type'      => 'gd_place',
	'author'         => $profile_user_id,
	'posts_per_page' => 24,
	'orderby'        => 'date',
	'order'          => 'DESC',
	'post_status'    => array( 'publish', 'draft', 'pending' ),
) );

$add_listing_url = function_exists( 'geodir_add_listing_page_url' ) ? geodir_add_listing_page_url() : home_url( '/' );
if ( $add_listing_url && function_exists( 'directory_relative_url' ) ) {
	$add_listing_url = directory_relative_url( $add_listing_url );
}
?>
<main class="custom-frontend-main cf-profile-main" id="main">
	<header class="cf-profile-hero">
		<div class="cf-profile-hero-inner">
			<div class="cf-profile-avatar-wrap">
				<?php if ( $profile_avatar ) : ?>
					<img src="<?php echo esc_url( $profile_avatar ); ?>" alt="" class="cf-profile-avatar" width="96" height="96" />
				<?php else : ?>
					<span class="cf-profile-avatar-placeholder" aria-hidden="true"></span>
				<?php endif; ?>
			</div>
			<div class="cf-profile-details">
				<h1 class="cf-profile-title"><?php echo esc_html( $profile_name ?: __( 'My Profile', 'directory' ) ); ?></h1>
				<?php if ( $profile_email ) : ?>
					<p class="cf-profile-email"><?php echo esc_html( $profile_email ); ?></p>
				<?php endif; ?>
				<?php if ( $profile_url ) : ?>
					<a class="cf-profile-edit" href="<?php echo esc_url( $profile_url ); ?>"><?php esc_html_e( 'Edit profile', 'directory' ); ?></a>
				<?php endif; ?>
			</div>
		</div>
	</header>

	<section class="cf-profile-listings-section" aria-labelledby="cf-profile-listings-heading">
		<div class="cf-profile-listings-inner">
			<header class="cf-profile-listings-header">
				<h2 id="cf-profile-listings-heading" class="cf-profile-listings-title"><?php esc_html_e( 'My listings', 'directory' ); ?></h2>
				<a class="cf-btn-add cf-profile-add-listing" href="<?php echo esc_url( $add_listing_url ); ?>">
					<span class="cf-icon-plus" aria-hidden="true">
						<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
					</span>
					<?php esc_html_e( 'Add listing', 'directory' ); ?>
				</a>
			</header>

			<?php if ( $profile_listings->have_posts() ) : ?>
				<div class="cf-profile-cards cf-gd-cards cf-gd-cards-grid">
					<?php
					while ( $profile_listings->have_posts() ) :
						$profile_listings->the_post();
						$pid   = get_the_ID();
						$thumb = get_the_post_thumbnail_url( $pid, 'medium_large' );
						$link  = get_the_permalink();
						if ( $link && function_exists( 'directory_relative_url' ) ) {
							$link = directory_relative_url( $link );
						}
						$excerpt = get_the_excerpt();
						$date    = get_the_date();
						$status  = get_post_status( $pid );
						?>
						<article id="post-<?php echo esc_attr( $pid ); ?>" <?php post_class( 'cf-gd-card cf-profile-card' ); ?>>
							<a class="cf-gd-card-link" href="<?php echo esc_url( $link ); ?>">
								<div class="cf-gd-card-image-wrap">
									<?php if ( $thumb ) : ?>
										<img src="<?php echo esc_url( $thumb ); ?>" alt="" class="cf-gd-card-image" loading="lazy" decoding="async" />
									<?php else : ?>
										<div class="cf-gd-card-image-placeholder"></div>
									<?php endif; ?>
									<?php if ( $status !== 'publish' ) : ?>
										<span class="cf-profile-card-status cf-profile-card-status-<?php echo esc_attr( $status ); ?>"><?php echo esc_html( ucfirst( $status ) ); ?></span>
									<?php endif; ?>
								</div>
								<div class="cf-gd-card-body">
									<h3 class="cf-gd-card-title"><?php the_title(); ?></h3>
									<?php if ( $excerpt ) : ?>
										<p class="cf-gd-card-excerpt"><?php echo esc_html( wp_trim_words( $excerpt, 18 ) ); ?></p>
									<?php endif; ?>
									<p class="cf-profile-card-date"><?php echo esc_html( $date ); ?></p>
								</div>
							</a>
						</article>
					<?php endwhile; ?>
				</div>
				<?php
				wp_reset_postdata();
			?>
			<?php else : ?>
				<div class="cf-profile-empty">
					<p class="cf-profile-empty-title"><?php esc_html_e( 'No listings yet', 'directory' ); ?></p>
					<p class="cf-profile-empty-desc"><?php esc_html_e( 'Add your first business listing to see it here.', 'directory' ); ?></p>
					<a class="cf-btn-add" href="<?php echo esc_url( $add_listing_url ); ?>">
						<span class="cf-icon-plus" aria-hidden="true">
							<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
						</span>
						<?php esc_html_e( 'Add listing', 'directory' ); ?>
					</a>
				</div>
			<?php endif; ?>
		</div>
	</section>
</main>
<?php
get_footer( 'custom' );
require_once get_stylesheet_directory() . '/includes/custom-frontend-doc-end.php';
