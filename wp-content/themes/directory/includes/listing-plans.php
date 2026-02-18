<?php
/**
 * Listing plans: Free vs Premium – field-based gating.
 *
 * - Free: unlimited listings, but only a limited set of fields (title, description, images, address, contact, etc.).
 * - Premium: all fields unlocked.
 *
 * @package Directory
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** User meta key for plan. */
define( 'DIRECTORY_LISTING_PLAN_META', 'directory_listing_plan' );

/** Free plan: max images per listing (premium = unlimited). */
define( 'DIRECTORY_FREE_MAX_IMAGES_PER_LISTING', 1 );

/**
 * Custom field htmlvar_name values that free users can see and edit.
 * All other custom fields are premium-only (hidden on form, cleared on save for free users).
 *
 * @return array
 */
function directory_get_free_allowed_fields() {
	/**
	 * For FREE users we only allow:
	 * - post_title     (Place title)
	 * - post_category  (Business category)
	 * - address        (single address line shown on the card – advanced
	 *                   address/map bits are visually hidden via CSS)
	 * - post_images    (1 image max; premium unlocks gallery/multiple images)
	 *
	 * No description (post_content) and no separate country / region / city / zip / map /
	 * latitude / longitude inputs on the form – those are effectively
	 * PREMIUM-only.
	 */
	$default = array(
		'post_title',
		'post_category',
		'address',
		'post_images',
	);
	return apply_filters( 'directory_free_allowed_fields', $default );
}

/**
 * On Add Listing page, show a small inline CTA for FREE users linking
 * to the full Upgrade page (separate pricing layout).
 */
function directory_listing_plan_add_listing_cta( $content ) {
	// Temporarily disabled: no upsell CTA, all users have full fields.
	return $content;
}
add_filter( 'the_content', 'directory_listing_plan_add_listing_cta', 20 );

/**
 * Add a body class on the Add Listing page so we can hide map/images
 * UI for free users via CSS only on that page.
 */
function directory_listing_plan_body_class( $classes ) {
	if ( function_exists( 'geodir_add_listing_page_id' ) ) {
		$add_page_id = geodir_add_listing_page_id( 'gd_place' );
		if ( $add_page_id && is_page( $add_page_id ) ) {
			// Treat everyone as premium for now – no restricted Add Listing UI.
			$classes[] = 'directory-plan-premium';
		}
	}
	return $classes;
}
add_filter( 'body_class', 'directory_listing_plan_body_class' );

/**
 * Get the listing plan for a user (free or premium).
 *
 * @param int $user_id User ID. Default current user.
 * @return string 'free' or 'premium'
 */
function directory_get_listing_plan( $user_id = 0 ) {
	if ( ! $user_id ) {
		$user_id = get_current_user_id();
	}
	if ( ! $user_id ) {
		return 'free';
	}
	$plan = get_user_meta( $user_id, DIRECTORY_LISTING_PLAN_META, true );
	if ( $plan === 'premium' ) {
		return 'premium';
	}
	return 'free';
}

/**
 * Check if user is on premium plan.
 *
 * @param int $user_id User ID. Default current user.
 * @return bool
 */
function directory_is_premium_listing_user( $user_id = 0 ) {
	return directory_get_listing_plan( $user_id ) === 'premium';
}

/**
 * Count how many published gd_place listings the user has.
 *
 * @param int $user_id User ID.
 * @return int
 */
function directory_count_user_listings( $user_id ) {
	if ( ! $user_id ) {
		return 0;
	}
	$count = count_user_posts( $user_id, 'gd_place', true ); // true = only published
	return (int) $count;
}

/* Free users can add unlimited listings; no form blocking. */

/**
 * For free users: clear premium-only field values on save so they cannot be set or retained.
 */
function directory_listing_plan_save_post_data( $postarr, $gd_post, $post, $update ) {
	// Temporarily disable field-level restrictions – keep all submitted data.
	return $postarr;
}
add_filter( 'geodir_save_post_data', 'directory_listing_plan_save_post_data', 10, 4 );

/**
 * After post save: trim images to max for free plan (delete excess from GD attachment table).
 */
function directory_listing_plan_after_post_save( $result, $postarr, $format, $gd_post, $post, $update ) {
	// Temporarily disabled: do not trim images for free plan.
}
add_action( 'geodir_after_post_save', 'directory_listing_plan_after_post_save', 10, 6 );

/**
 * Limit file upload count for post_images for free users (add/edit listing form).
 */
function directory_listing_plan_file_limit( $file_limit, $cf, $gd_post ) {
	// Temporarily disabled: keep original upload limits (no free-plan cap).
	return $file_limit;
}
add_filter( 'geodir_custom_field_file_limit', 'directory_listing_plan_file_limit', 10, 3 );

/**
 * Hide premium-only custom fields on add listing form for free users.
 * Only fields in directory_get_free_allowed_fields() are shown.
 */
function directory_listing_plan_hide_premium_fields( $is_hidden, $val, $package_id, $default ) {
	// Temporarily disabled: show all fields for all users.
	return false;
}
add_filter( 'geodir_add_listing_custom_field_is_hidden', 'directory_listing_plan_hide_premium_fields', 10, 4 );


/**
 * Get upgrade URL (page or #).
 *
 * @return string
 */
function directory_get_upgrade_url() {
	$page = get_page_by_path( 'upgrade' );
	if ( $page && $page->post_status === 'publish' ) {
		return get_permalink( $page );
	}
	return home_url( '/upgrade/' );
}

/**
 * Add Listing plan field to user profile (admin).
 */
function directory_listing_plan_user_profile_field( $user ) {
	if ( ! current_user_can( 'edit_users' ) ) {
		return;
	}
	$plan = get_user_meta( $user->ID, DIRECTORY_LISTING_PLAN_META, true );
	if ( $plan !== 'premium' ) {
		$plan = 'free';
	}
	?>
	<h2><?php esc_html_e( 'Listing plan', 'directory' ); ?></h2>
	<table class="form-table">
		<tr>
			<th><label for="directory_listing_plan"><?php esc_html_e( 'Plan', 'directory' ); ?></label></th>
			<td>
				<select name="directory_listing_plan" id="directory_listing_plan">
					<option value="free" <?php selected( $plan, 'free' ); ?>><?php esc_html_e( 'Free', 'directory' ); ?></option>
					<option value="premium" <?php selected( $plan, 'premium' ); ?>><?php esc_html_e( 'Premium', 'directory' ); ?></option>
				</select>
				<p class="description"><?php esc_html_e( 'Free: unlimited listings with limited fields (basics only). Premium: all fields unlocked.', 'directory' ); ?></p>
			</td>
		</tr>
	</table>
	<?php
}
add_action( 'show_user_profile', 'directory_listing_plan_user_profile_field' );
add_action( 'edit_user_profile', 'directory_listing_plan_user_profile_field' );

/**
 * Save Listing plan from user profile.
 */
function directory_listing_plan_save_user_profile( $user_id ) {
	if ( ! current_user_can( 'edit_users' ) || ! isset( $_POST['directory_listing_plan'] ) ) {
		return;
	}
	$plan = sanitize_text_field( $_POST['directory_listing_plan'] );
	if ( in_array( $plan, array( 'free', 'premium' ), true ) ) {
		update_user_meta( $user_id, DIRECTORY_LISTING_PLAN_META, $plan );
	}
}
add_action( 'personal_options_update', 'directory_listing_plan_save_user_profile' );
add_action( 'edit_user_profile_update', 'directory_listing_plan_save_user_profile' );

/**
 * Shortcode: [directory_listing_plan_table] – Free vs Premium cards (separate page).
 */
function directory_listing_plan_table_shortcode() {
	$upgrade_url = directory_get_upgrade_url();

	ob_start();
	?>
	<section class="directory-plan-cta" aria-label="<?php esc_attr_e( 'Listing plans', 'directory' ); ?>">
		<header class="directory-plan-cta-header">
			<p class="directory-plan-cta-eyebrow"><?php esc_html_e( 'Choose your plan for listing', 'directory' ); ?></p>
			<h2 class="directory-plan-cta-title"><?php esc_html_e( 'Start free. Upgrade when you need more.', 'directory' ); ?></h2>
		</header>

		<div class="directory-plan-cta-grid">
			<article class="directory-plan-card directory-plan-card--free">
				<h3 class="directory-plan-card-title"><?php esc_html_e( 'Free listing', 'directory' ); ?></h3>
				<p class="directory-plan-card-subtitle"><?php esc_html_e( 'Great for getting started.', 'directory' ); ?></p>
				<ul class="directory-plan-card-list">
					<li><?php esc_html_e( 'Place title & category', 'directory' ); ?></li>
					<li><?php esc_html_e( 'Single address line', 'directory' ); ?></li>
					<li><?php esc_html_e( '1 photo (upgrade for gallery)', 'directory' ); ?></li>
				</ul>
			</article>

			<article class="directory-plan-card directory-plan-card--premium">
				<p class="directory-plan-card-pill"><?php esc_html_e( 'Recommended', 'directory' ); ?></p>
				<h3 class="directory-plan-card-title"><?php esc_html_e( 'Premium listing', 'directory' ); ?></h3>
				<p class="directory-plan-card-subtitle"><?php esc_html_e( 'Unlock full details and visibility.', 'directory' ); ?></p>
				<ul class="directory-plan-card-list">
					<li><?php esc_html_e( 'Full address + interactive map', 'directory' ); ?></li>
					<li><?php esc_html_e( 'Rich description & extra fields', 'directory' ); ?></li>
					<li><?php esc_html_e( 'Multiple images in a gallery', 'directory' ); ?></li>
					<li><?php esc_html_e( 'Higher impact, more engagement', 'directory' ); ?></li>
				</ul>
				<a class="directory-plan-cta-btn" href="<?php echo esc_url( $upgrade_url ); ?>">
					<?php esc_html_e( 'Unlock more listing options', 'directory' ); ?>
				</a>
			</article>
		</div>
	</section>
	<?php

	return ob_get_clean();
}
add_shortcode( 'directory_listing_plan_table', 'directory_listing_plan_table_shortcode' );
