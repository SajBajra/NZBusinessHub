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
				<div class="cf-add-listing-hero-content">
					<h1 id="cf-add-listing-title" class="cf-add-listing-title">
						<?php esc_html_e( 'Add your business to NZ Business Hub', 'directory' ); ?>
					</h1>
					<p class="cf-add-listing-lead">
						<?php esc_html_e( 'Create a free account or sign in to add and manage your business listings, keep details up to date, and help customers find you easily.', 'directory' ); ?>
					</p>
					<div class="cf-add-listing-actions">
						<?php
						$redirect = esc_url( home_url( add_query_arg( array(), $_SERVER['REQUEST_URI'] ) ) );
						if ( function_exists( 'geodir_curPageURL' ) ) {
							$redirect = esc_url( geodir_curPageURL() );
						}
						$login_url    = wp_login_url( $redirect );
						$register_url = get_option( 'users_can_register' ) ? wp_registration_url() : '';
						?>
						<a class="cf-btn cf-btn-primary cf-add-listing-btn" href="<?php echo esc_url( $login_url ); ?>">
							<?php esc_html_e( 'Login', 'directory' ); ?>
						</a>
						<?php if ( $register_url ) : ?>
							<a class="cf-btn cf-btn-outline cf-add-listing-btn" href="<?php echo esc_url( $register_url ); ?>">
								<?php esc_html_e( 'Register', 'directory' ); ?>
							</a>
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

