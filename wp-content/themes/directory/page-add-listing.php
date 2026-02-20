<?php
/**
 * Custom Add Listing page.
 *
 * Shows a nicer \"please log in / register\" hero when the user is not logged in,
 * and falls back to the normal [gd_add_listing] content when logged in.
 *
 * @package Directory
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once get_stylesheet_directory() . '/includes/custom-frontend-doc-start.php';
get_header( 'custom' );

?>

<main class="custom-frontend-main cf-add-listing-main" id="main">
	<div class="cf-single-place-inner cf-add-listing-inner">
		<?php if ( ! is_user_logged_in() ) : ?>
			<section class="cf-add-listing-hero" aria-labelledby="cf-add-listing-title">
				<div class="cf-add-listing-hero-media">
					<img src="https://images.pexels.com/photos/1181395/pexels-photo-1181395.jpeg?auto=compress&cs=tinysrgb&w=800" alt="<?php esc_attr_e( 'People collaborating in an office', 'directory' ); ?>" class="cf-add-listing-hero-image" loading="lazy" />
				</div>
				<div class="cf-add-listing-hero-content">
					<h1 id="cf-add-listing-title" class="cf-add-listing-title">
						<?php esc_html_e( 'Add your business to NZ Business Hub', 'directory' ); ?>
					</h1>
					<p class="cf-add-listing-lead">
						<?php esc_html_e( 'Create a free account or sign in to add and manage your business listings, keep details up to date, and help customers find you easily.', 'directory' ); ?>
					</p>
					<div class="cf-add-listing-actions">
						<button
							type="button"
							class="cf-add-listing-btn cf-add-listing-btn--login"
							data-bs-toggle="modal"
							data-bs-target="#cf-auth-modal"
							data-auth-modal-tab="login"
						>
							<?php esc_html_e( 'Login', 'directory' ); ?>
						</button>
						<?php if ( get_option( 'users_can_register' ) ) : ?>
							<button
								type="button"
								class="cf-add-listing-btn cf-add-listing-btn--register"
								data-bs-toggle="modal"
								data-bs-target="#cf-auth-modal"
								data-auth-modal-tab="register"
							>
								<?php esc_html_e( 'Register', 'directory' ); ?>
							</button>
						<?php endif; ?>
					</div>
				</div>
			</section>
		<?php else : ?>
			<section class="cf-add-listing-form" aria-label="<?php esc_attr_e( 'Add business form', 'directory' ); ?>">
				<?php
				while ( have_posts() ) :
					the_post();
					?>
					<article id="post-<?php the_ID(); ?>" <?php post_class( 'cf-add-listing-article' ); ?>>
						<header class="cf-add-listing-header">
							<h1 class="cf-add-listing-title">
								<?php the_title(); ?>
							</h1>
						</header>
						<div class="cf-add-listing-form-inner entry-content">
							<?php the_content(); ?>
						</div>
					</article>
					<?php
				endwhile;
				?>
			</section>
		<?php endif; ?>
	</div>
</main>

<?php
get_footer( 'custom' );
require_once get_stylesheet_directory() . '/includes/custom-frontend-doc-end.php';

