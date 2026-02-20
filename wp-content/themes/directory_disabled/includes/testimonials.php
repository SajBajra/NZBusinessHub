<?php
/**
 * Testimonials (Customer feedback) – custom post type and meta for backend management.
 *
 * @package Directory
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register Testimonial post type.
 */
function directory_register_testimonial_post_type() {
	$labels = array(
		'name'               => _x( 'Testimonials', 'post type general name', 'directory' ),
		'singular_name'      => _x( 'Testimonial', 'post type singular name', 'directory' ),
		'menu_name'          => _x( 'Testimonials', 'admin menu', 'directory' ),
		'add_new'            => _x( 'Add New', 'testimonial', 'directory' ),
		'add_new_item'       => __( 'Add New Testimonial', 'directory' ),
		'edit_item'          => __( 'Edit Testimonial', 'directory' ),
		'new_item'           => __( 'New Testimonial', 'directory' ),
		'view_item'          => __( 'View Testimonial', 'directory' ),
		'search_items'       => __( 'Search Testimonials', 'directory' ),
		'not_found'          => __( 'No testimonials found.', 'directory' ),
		'not_found_in_trash' => __( 'No testimonials found in Trash.', 'directory' ),
	);

	$args = array(
		'labels'              => $labels,
		'public'              => false,
		'publicly_queryable'  => false,
		'show_ui'             => true,
		'show_in_menu'        => true,
		'menu_icon'           => 'dashicons-format-quote',
		'menu_position'       => 6,
		'query_var'           => false,
		'rewrite'             => false,
		'capability_type'     => 'post',
		'map_meta_cap'        => true,
		'has_archive'         => false,
		'hierarchical'        => false,
		'supports'            => array( 'title', 'editor', 'thumbnail' ),
		'show_in_admin_bar'   => true,
	);

	register_post_type( 'dir_testimonial', $args );
}
add_action( 'init', 'directory_register_testimonial_post_type', 0 );
add_action( 'admin_init', 'directory_register_testimonial_post_type', 0 );

// Ensure CPT is registered when this file is loaded in admin.
if ( is_admin() && ! post_type_exists( 'dir_testimonial' ) ) {
	directory_register_testimonial_post_type();
}

/**
 * Add "Customer feedback" under Settings – custom list page so we don't rely on native CPT screen.
 */
function directory_add_testimonials_settings_link() {
	add_submenu_page(
		'options-general.php',
		__( 'Customer feedback', 'directory' ),
		__( 'Customer feedback', 'directory' ),
		'edit_posts',
		'directory-testimonials',
		'directory_render_testimonials_admin_page'
	);
}

function directory_render_testimonials_admin_page() {
	directory_register_testimonial_post_type();
	$list_url = admin_url( 'edit.php?post_type=dir_testimonial' );
	$new_url  = admin_url( 'post-new.php?post_type=dir_testimonial' );
	$posts    = get_posts( array( 'post_type' => 'dir_testimonial', 'post_status' => 'any', 'numberposts' => 50, 'orderby' => 'menu_order title' ) );
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Customer feedback (Testimonials)', 'directory' ); ?></h1>
		<p>
			<a href="<?php echo esc_url( $new_url ); ?>" class="button button-primary"><?php esc_html_e( 'Add New Testimonial', 'directory' ); ?></a>
			<?php if ( post_type_exists( 'dir_testimonial' ) ) : ?>
				<a href="<?php echo esc_url( $list_url ); ?>" class="button"><?php esc_html_e( 'View all in list', 'directory' ); ?></a>
			<?php endif; ?>
		</p>
		<?php if ( empty( $posts ) ) : ?>
			<p><?php esc_html_e( 'No testimonials yet. Add one to show customer feedback on your homepage.', 'directory' ); ?></p>
		<?php else : ?>
			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Author', 'directory' ); ?></th>
						<th><?php esc_html_e( 'Role', 'directory' ); ?></th>
						<th><?php esc_html_e( 'Quote', 'directory' ); ?></th>
						<th><?php esc_html_e( 'Actions', 'directory' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $posts as $p ) : ?>
						<tr>
							<td><?php echo esc_html( $p->post_title ); ?></td>
							<td><?php echo esc_html( get_post_meta( $p->ID, DIRECTORY_TESTIMONIAL_ROLE_META, true ) ); ?></td>
							<td><?php echo esc_html( wp_trim_words( wp_strip_all_tags( $p->post_content ), 10 ) ); ?></td>
							<td>
								<a href="<?php echo esc_url( get_edit_post_link( $p->ID, 'raw' ) ); ?>"><?php esc_html_e( 'Edit', 'directory' ); ?></a>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>
	</div>
	<?php
}
add_action( 'admin_menu', 'directory_add_testimonials_settings_link', 99 );

/**
 * Meta key for testimonial role (job title / company).
 */
define( 'DIRECTORY_TESTIMONIAL_ROLE_META', '_testimonial_role' );

/**
 * Add meta box for Testimonial role.
 */
function directory_testimonial_add_meta_boxes() {
	add_meta_box(
		'directory_testimonial_role',
		__( 'Role / Title', 'directory' ),
		'directory_testimonial_role_meta_box_cb',
		'dir_testimonial',
		'normal',
		'default'
	);
}

function directory_testimonial_role_meta_box_cb( $post ) {
	wp_nonce_field( 'directory_testimonial_role_save', 'directory_testimonial_role_nonce' );
	$role = get_post_meta( $post->ID, DIRECTORY_TESTIMONIAL_ROLE_META, true );
	?>
	<p>
		<label for="directory_testimonial_role"><?php esc_html_e( 'e.g. Content Designer at Uber Eats', 'directory' ); ?></label><br>
		<input type="text" id="directory_testimonial_role" name="directory_testimonial_role" value="<?php echo esc_attr( $role ); ?>" class="widefat" />
	</p>
	<p class="description"><?php esc_html_e( 'Post title = author name. Content = quote. Featured image = avatar.', 'directory' ); ?></p>
	<?php
}

add_action( 'add_meta_boxes', 'directory_testimonial_add_meta_boxes' );

/**
 * Save testimonial role meta.
 */
function directory_testimonial_save_meta( $post_id ) {
	if ( ! isset( $_POST['directory_testimonial_role_nonce'] ) ||
	     ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['directory_testimonial_role_nonce'] ) ), 'directory_testimonial_role_save' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	if ( get_post_type( $post_id ) !== 'dir_testimonial' ) {
		return;
	}
	$role = isset( $_POST['directory_testimonial_role'] ) ? sanitize_text_field( wp_unslash( $_POST['directory_testimonial_role'] ) ) : '';
	update_post_meta( $post_id, DIRECTORY_TESTIMONIAL_ROLE_META, $role );
}
add_action( 'save_post_dir_testimonial', 'directory_testimonial_save_meta' );

/**
 * List columns for Testimonials.
 */
function directory_testimonial_columns( $columns ) {
	$new = array();
	$new['cb'] = $columns['cb'];
	$new['title'] = __( 'Author', 'directory' );
	$new['role'] = __( 'Role / Title', 'directory' );
	$new['date'] = $columns['date'];
	return $new;
}
add_filter( 'manage_dir_testimonial_posts_columns', 'directory_testimonial_columns' );

function directory_testimonial_column_content( $column, $post_id ) {
	if ( $column === 'role' ) {
		echo esc_html( get_post_meta( $post_id, DIRECTORY_TESTIMONIAL_ROLE_META, true ) );
	}
}
add_action( 'manage_dir_testimonial_posts_custom_column', 'directory_testimonial_column_content', 10, 2 );

/**
 * Admin notice with link to Testimonials (Customer feedback).
 */
function directory_testimonials_admin_notice() {
	$screen = get_current_screen();
	if ( ! $screen || $screen->id !== 'dashboard' ) {
		return;
	}
	if ( ! current_user_can( 'edit_posts' ) ) {
		return;
	}
	$url = admin_url( 'options-general.php?page=directory-testimonials' );
	?>
	<div class="notice notice-info is-dismissible">
		<p>
			<?php esc_html_e( 'Customer feedback (testimonials) for your homepage:', 'directory' ); ?>
			<a href="<?php echo esc_url( $url ); ?>"><?php esc_html_e( 'Go to Customer feedback', 'directory' ); ?></a>
		</p>
	</div>
	<?php
}
add_action( 'admin_notices', 'directory_testimonials_admin_notice' );

/**
 * Insert dummy testimonials when theme is activated (only if none exist).
 */
function directory_insert_dummy_testimonials() {
	if ( get_posts( array( 'post_type' => 'dir_testimonial', 'post_status' => 'any', 'numberposts' => 1 ) ) ) {
		return;
	}
	$dummies = array(
		array(
			'title'   => 'Abhishek',
			'content' => 'This is some dummy text. Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt.',
			'role'    => 'Content Designer at Uber Eats',
		),
		array(
			'title'   => 'Sarah Mitchell',
			'content' => 'Great service and easy to find local businesses. The directory has been really helpful for our team when we travel to New Zealand.',
			'role'    => 'Marketing Manager',
		),
		array(
			'title'   => 'James Chen',
			'content' => 'We listed our restaurant here and saw a noticeable increase in bookings. Highly recommend for any business in NZ.',
			'role'    => 'Restaurant Owner, Auckland',
		),
	);
	foreach ( $dummies as $i => $d ) {
		$id = wp_insert_post( array(
			'post_type'    => 'dir_testimonial',
			'post_title'   => $d['title'],
			'post_content' => $d['content'],
			'post_status'  => 'publish',
			'menu_order'   => $i,
		) );
		if ( $id && ! is_wp_error( $id ) ) {
			update_post_meta( $id, DIRECTORY_TESTIMONIAL_ROLE_META, $d['role'] );
		}
	}
}
add_action( 'after_switch_theme', 'directory_insert_dummy_testimonials' );

/**
 * Insert dummy testimonials when admin first visits Testimonials list (if empty).
 */
function directory_maybe_insert_dummy_testimonials_on_admin_load() {
	if ( ! is_admin() || ! isset( $_GET['post_type'] ) || $_GET['post_type'] !== 'dir_testimonial' ) {
		return;
	}
	if ( ! current_user_can( 'edit_posts' ) ) {
		return;
	}
	directory_insert_dummy_testimonials();
}
add_action( 'load-edit.php', 'directory_maybe_insert_dummy_testimonials_on_admin_load' );
