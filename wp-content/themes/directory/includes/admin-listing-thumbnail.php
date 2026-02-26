<?php
/**
 * Optional card thumbnail field for GeoDirectory listings (gd_place).
 *
 * Lets admins upload a dedicated image used for business cards on the
 * frontend, instead of always relying on the main featured image.
 *
 * @package Directory
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Add meta box for Card thumbnail on gd_place edit screen.
 */
function directory_add_listing_thumbnail_meta_box() {
	if ( ! function_exists( 'geodir_get_posttypes' ) ) {
		return;
	}

	$post_types = geodir_get_posttypes();
	if ( ! in_array( 'gd_place', $post_types, true ) ) {
		return;
	}

	add_meta_box(
		'directory_listing_thumbnail',
		__( 'Listing card thumbnail', 'directory' ),
		'directory_listing_thumbnail_meta_box_cb',
		'gd_place',
		'side',
		'low'
	);
}
add_action( 'add_meta_boxes', 'directory_add_listing_thumbnail_meta_box', 20 );

/**
 * Meta box callback: output media uploader for card thumbnail.
 *
 * @param WP_Post $post Current post.
 */
function directory_listing_thumbnail_meta_box_cb( $post ) {
	if ( ! $post || $post->post_type !== 'gd_place' ) {
		return;
	}

	wp_enqueue_media();

	$attachment_id = (int) get_post_meta( $post->ID, '_directory_card_thumbnail_id', true );
	$image_url     = $attachment_id ? wp_get_attachment_image_url( $attachment_id, 'directory-card-thumb' ) : '';

	wp_nonce_field( 'directory_listing_thumbnail', 'directory_listing_thumbnail_nonce' );
	?>
	<p class="description">
		<?php esc_html_e( 'Optional image used for business cards. Recommended size: 4:3 ratio, around 960×720px (for example 1200×900px).', 'directory' ); ?>
	</p>
	<div class="directory-listing-thumbnail-field">
		<div class="directory-listing-thumbnail-preview" style="width: 240px; height: 180px; background: #f3f4f6; border: 1px solid #e5e7eb; border-radius: 8px; display: flex; align-items: center; justify-content: center; overflow: hidden;">
			<?php if ( $image_url ) : ?>
				<img src="<?php echo esc_url( $image_url ); ?>" alt="" style="max-width: 100%; max-height: 100%; height: auto; width: auto;" />
			<?php else : ?>
				<span style="color:#9ca3af; font-size:12px;"><?php esc_html_e( 'No card thumbnail selected', 'directory' ); ?></span>
			<?php endif; ?>
		</div>
		<input type="hidden" id="directory_card_thumbnail_id" name="directory_card_thumbnail_id" value="<?php echo esc_attr( $attachment_id ); ?>" />
		<p style="margin-top:8px;">
			<button type="button" class="button" id="directory_card_thumbnail_select">
				<?php esc_html_e( 'Select image', 'directory' ); ?>
			</button>
			<button type="button" class="button" id="directory_card_thumbnail_remove" <?php echo $attachment_id ? '' : 'style="display:none;"'; ?>>
				<?php esc_html_e( 'Remove', 'directory' ); ?>
			</button>
		</p>
	</div>
	<script>
		(function($){
			$(function(){
				var frame;
				var $field   = $('#directory_card_thumbnail_id');
				var $preview = $('.directory-listing-thumbnail-preview');
				var $remove  = $('#directory_card_thumbnail_remove');

				$('#directory_card_thumbnail_select').on('click', function(e){
					e.preventDefault();

					if (frame) {
						frame.open();
						return;
					}

					frame = wp.media({
						title: '<?php echo esc_js( __( 'Select card thumbnail', 'directory' ) ); ?>',
						button: { text: '<?php echo esc_js( __( 'Use this image', 'directory' ) ); ?>' },
						multiple: false
					});

					frame.on('select', function(){
						var attachment = frame.state().get('selection').first().toJSON();
						if (!attachment || !attachment.id) {
							return;
						}

						$field.val(attachment.id);
						$preview.html('<img src=\"' + attachment.url + '\" alt=\"\" style=\"max-width:100%;max-height:100%;height:auto;width:auto;\" />');
						$remove.show();
					});

					frame.open();
				});

				$remove.on('click', function(e){
					e.preventDefault();
					$field.val('');
					$preview.html('<span style="color:#9ca3af; font-size:12px;"><?php echo esc_js( __( 'No card thumbnail selected', 'directory' ) ); ?></span>');
					$remove.hide();
				});
			});
		})(jQuery);
	</script>
	<?php
}

/**
 * Save card thumbnail attachment ID when listing is saved.
 *
 * @param array   $postarr  GeoDirectory post array.
 * @param object  $gd_post  GeoDirectory post object.
 * @param WP_Post $post     WP_Post object.
 * @param bool    $update   Whether this is an existing post being updated.
 * @return array
 */
function directory_save_listing_thumbnail( $postarr, $gd_post, $post, $update ) {
	if ( ! $post || $post->post_type !== 'gd_place' ) {
		return $postarr;
	}
	if ( ! isset( $_POST['directory_listing_thumbnail_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['directory_listing_thumbnail_nonce'] ) ), 'directory_listing_thumbnail' ) ) {
		return $postarr;
	}

	$attachment_id = isset( $_POST['directory_card_thumbnail_id'] ) ? (int) $_POST['directory_card_thumbnail_id'] : 0;

	if ( $attachment_id > 0 ) {
		update_post_meta( $post->ID, '_directory_card_thumbnail_id', $attachment_id );
	} else {
		delete_post_meta( $post->ID, '_directory_card_thumbnail_id' );
	}

	return $postarr;
}
add_filter( 'geodir_save_post_data', 'directory_save_listing_thumbnail', 15, 4 );

