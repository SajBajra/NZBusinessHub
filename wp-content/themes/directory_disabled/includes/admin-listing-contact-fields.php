<?php
/**
 * Add Phone, Email, Website fields to the admin edit screen for GeoDirectory listings.
 * These fields are per-listing and stored in the GeoDirectory detail table.
 *
 * @package Directory
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Ensure phone, email, website columns exist in the gd_place_detail table.
 */
function directory_ensure_contact_columns_exist() {
	if ( ! function_exists( 'geodir_column_exist' ) || ! function_exists( 'geodir_db_cpt_table' ) ) {
		return;
	}
	global $wpdb;
	$table = geodir_db_cpt_table( 'gd_place' );
	if ( ! $table ) {
		return;
	}
	$cols = array(
		'phone'   => 'ALTER TABLE `' . esc_sql( $table ) . '` ADD `phone` VARCHAR(100) NULL DEFAULT NULL',
		'email'   => 'ALTER TABLE `' . esc_sql( $table ) . '` ADD `email` VARCHAR(254) NULL DEFAULT NULL',
		'website' => 'ALTER TABLE `' . esc_sql( $table ) . '` ADD `website` TEXT NULL DEFAULT NULL',
	);
	foreach ( $cols as $col => $sql ) {
		if ( ! geodir_column_exist( $table, $col ) ) {
			$wpdb->query( $sql );
		}
	}
}
add_action( 'admin_init', 'directory_ensure_contact_columns_exist', 5 );

/**
 * Add meta box for Phone, Email, Website on gd_place edit screen.
 */
function directory_add_contact_fields_meta_box() {
	if ( ! function_exists( 'geodir_get_posttypes' ) ) {
		return;
	}
	$post_types = geodir_get_posttypes();
	if ( ! in_array( 'gd_place', $post_types, true ) ) {
		return;
	}
	add_meta_box(
		'directory_contact_fields',
		__( 'Contact details', 'directory' ),
		'directory_contact_fields_meta_box_cb',
		'gd_place',
		'normal',
		'default'
	);
}
add_action( 'add_meta_boxes', 'directory_add_contact_fields_meta_box', 15 );

/**
 * Meta box callback: output Phone, Email, Website inputs.
 */
function directory_contact_fields_meta_box_cb( $post ) {
	if ( ! $post || $post->post_type !== 'gd_place' ) {
		return;
	}
	$pid = (int) $post->ID;
	$phone   = function_exists( 'geodir_get_post_meta' ) ? geodir_get_post_meta( $pid, 'phone', true ) : '';
	$email   = function_exists( 'geodir_get_post_meta' ) ? geodir_get_post_meta( $pid, 'email', true ) : '';
	$website = function_exists( 'geodir_get_post_meta' ) ? geodir_get_post_meta( $pid, 'website', true ) : '';
	wp_nonce_field( 'directory_contact_fields', 'directory_contact_fields_nonce' );
	?>
	<table class="form-table" role="presentation">
		<tr>
			<th scope="row"><label for="directory_contact_phone"><?php esc_html_e( 'Phone', 'directory' ); ?></label></th>
			<td>
				<input type="text" id="directory_contact_phone" name="directory_contact_phone" value="<?php echo esc_attr( $phone ); ?>" class="regular-text" />
			</td>
		</tr>
		<tr>
			<th scope="row"><label for="directory_contact_email"><?php esc_html_e( 'Email', 'directory' ); ?></label></th>
			<td>
				<input type="email" id="directory_contact_email" name="directory_contact_email" value="<?php echo esc_attr( $email ); ?>" class="regular-text" />
			</td>
		</tr>
		<tr>
			<th scope="row"><label for="directory_contact_website"><?php esc_html_e( 'Website', 'directory' ); ?></label></th>
			<td>
				<input type="url" id="directory_contact_website" name="directory_contact_website" value="<?php echo esc_attr( $website ); ?>" class="regular-text" placeholder="https://" />
			</td>
		</tr>
	</table>
	<?php
}

/**
 * Save contact fields into GeoDirectory detail table when post is saved.
 */
function directory_save_contact_fields( $postarr, $gd_post, $post, $update ) {
	if ( ! $post || $post->post_type !== 'gd_place' ) {
		return $postarr;
	}
	if ( ! isset( $_POST['directory_contact_fields_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['directory_contact_fields_nonce'] ) ), 'directory_contact_fields' ) ) {
		return $postarr;
	}
	$phone   = isset( $_POST['directory_contact_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['directory_contact_phone'] ) ) : '';
	$email   = isset( $_POST['directory_contact_email'] ) ? sanitize_email( wp_unslash( $_POST['directory_contact_email'] ) ) : '';
	$website = isset( $_POST['directory_contact_website'] ) ? esc_url_raw( wp_unslash( $_POST['directory_contact_website'] ) ) : '';
	$postarr['phone']   = $phone;
	$postarr['email']   = $email;
	$postarr['website'] = $website;
	return $postarr;
}
add_filter( 'geodir_save_post_data', 'directory_save_contact_fields', 10, 4 );
