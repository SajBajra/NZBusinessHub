<?php
/**
 * GeoDirectory category CMS fields (term meta) + frontend helpers.
 *
 * Adds admin UI for gd_placecategory terms:
 * - Rich content
 * - Two images
 * - FAQ repeater (Q/A)
 *
 * @package Directory
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Taxonomy slug used by GeoDirectory for places categories.
 */
function directory_gd_category_taxonomy() {
	return 'gd_placecategory';
}

function directory_gd_cat_meta_key_content() {
	return '_directory_cat_content';
}

function directory_gd_cat_meta_key_image1() {
	return '_directory_cat_image_1_id';
}

function directory_gd_cat_meta_key_image2() {
	return '_directory_cat_image_2_id';
}

function directory_gd_cat_meta_key_faq() {
	return '_directory_cat_faq';
}

/**
 * Enqueue admin media + small inline JS for term screens.
 */
function directory_gd_cat_cms_admin_enqueue( $hook_suffix ) {
	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	if ( ! $screen ) {
		return;
	}

	$tax = directory_gd_category_taxonomy();
	if ( empty( $screen->taxonomy ) || $screen->taxonomy !== $tax ) {
		return;
	}

	// We only need these on the term add/edit screens.
	if ( $screen->base !== 'term' && $screen->base !== 'edit-tags' ) {
		return;
	}

	wp_enqueue_media();
	wp_enqueue_script( 'jquery' );

	// Minimal JS for media fields + FAQ repeater.
	wp_add_inline_script(
		'jquery',
		"(function($){\n" .
		"function pickMedia(onSelect){\n" .
		"  var frame = wp.media({title:'Select image',button:{text:'Use this image'},multiple:false});\n" .
		"  frame.on('select', function(){ var att = frame.state().get('selection').first().toJSON(); onSelect(att); });\n" .
		"  frame.open();\n" .
		"}\n" .
		"function bindImageField($wrap){\n" .
		"  $wrap.on('click','.dir-cat-img-pick',function(e){e.preventDefault();\n" .
		"    var $w=$(this).closest('.dir-cat-img-field');\n" .
		"    pickMedia(function(att){\n" .
		"      $w.find('input.dir-cat-img-id').val(att.id);\n" .
		"      $w.find('img.dir-cat-img-preview').attr('src',att.url).show();\n" .
		"      $w.find('.dir-cat-img-remove').show();\n" .
		"    });\n" .
		"  });\n" .
		"  $wrap.on('click','.dir-cat-img-remove',function(e){e.preventDefault();\n" .
		"    var $w=$(this).closest('.dir-cat-img-field');\n" .
		"    $w.find('input.dir-cat-img-id').val('');\n" .
		"    $w.find('img.dir-cat-img-preview').attr('src','').hide();\n" .
		"    $(this).hide();\n" .
		"  });\n" .
		"}\n" .
		"function bindFaq($root){\n" .
		"  $root.on('click','.dir-faq-add',function(e){e.preventDefault();\n" .
		"    var $list=$root.find('.dir-faq-list');\n" .
		"    var idx=$list.children('.dir-faq-item').length;\n" .
		"    var html='<div class=\"dir-faq-item\" style=\"border:1px solid #ddd;padding:10px;margin:10px 0;border-radius:6px;\">'\n" .
		"      +'<p style=\"margin:0 0 6px;\"><strong>Question</strong></p>'\n" .
		"      +'<input type=\"text\" class=\"widefat\" name=\"directory_cat_faq['+idx+'][q]\" value=\"\" placeholder=\"Question\" />'\n" .
		"      +'<p style=\"margin:10px 0 6px;\"><strong>Answer</strong></p>'\n" .
		"      +'<textarea class=\"widefat\" rows=\"3\" name=\"directory_cat_faq['+idx+'][a]\" placeholder=\"Answer\"></textarea>'\n" .
		"      +'<p style=\"margin:10px 0 0;\"><a href=\"#\" class=\"button dir-faq-remove\">Remove</a></p>'\n" .
		"      +'</div>';\n" .
		"    $list.append(html);\n" .
		"  });\n" .
		"  $root.on('click','.dir-faq-remove',function(e){e.preventDefault(); $(this).closest('.dir-faq-item').remove(); });\n" .
		"}\n" .
		"$(function(){\n" .
		"  bindImageField($(document));\n" .
		"  bindFaq($(document));\n" .
		"});\n" .
		"})(jQuery);"
	);
}
add_action( 'admin_enqueue_scripts', 'directory_gd_cat_cms_admin_enqueue' );

/**
 * Render image picker field (shared).
 */
function directory_gd_cat_render_image_field( $label, $field_name, $current_id ) {
	$img_url = $current_id ? wp_get_attachment_image_url( (int) $current_id, 'thumbnail' ) : '';
	?>
	<div class="dir-cat-img-field">
		<p><strong><?php echo esc_html( $label ); ?></strong></p>
		<input type="hidden" class="dir-cat-img-id" name="<?php echo esc_attr( $field_name ); ?>" value="<?php echo esc_attr( (string) $current_id ); ?>" />
		<div style="display:flex;align-items:center;gap:12px;">
			<img class="dir-cat-img-preview" src="<?php echo esc_url( $img_url ); ?>" alt="" style="<?php echo $img_url ? '' : 'display:none;'; ?>width:72px;height:72px;object-fit:cover;border:1px solid #ddd;border-radius:8px;background:#f9fafb;" />
			<div style="display:flex;gap:8px;flex-wrap:wrap;">
				<a href="#" class="button dir-cat-img-pick"><?php esc_html_e( 'Select image', 'directory' ); ?></a>
				<a href="#" class="button dir-cat-img-remove" style="<?php echo $img_url ? '' : 'display:none;'; ?>"><?php esc_html_e( 'Remove', 'directory' ); ?></a>
			</div>
		</div>
	</div>
	<?php
}

/**
 * Add-term fields (quick add screen).
 */
function directory_gd_cat_add_fields() {
	?>
	<div class="form-field term-description-wrap">
		<label for="directory_cat_content"><?php esc_html_e( 'Category content', 'directory' ); ?></label>
		<textarea name="directory_cat_content" id="directory_cat_content" rows="6" class="large-text" placeholder="<?php esc_attr_e( 'Write a short description for this category...', 'directory' ); ?>"></textarea>
		<p class="description"><?php esc_html_e( 'Displayed on the category archive page under the business cards.', 'directory' ); ?></p>
	</div>
	<div class="form-field">
		<?php directory_gd_cat_render_image_field( __( 'Image 1', 'directory' ), 'directory_cat_image_1_id', '' ); ?>
	</div>
	<div class="form-field">
		<?php directory_gd_cat_render_image_field( __( 'Image 2', 'directory' ), 'directory_cat_image_2_id', '' ); ?>
	</div>
	<div class="form-field">
		<label><?php esc_html_e( 'FAQs', 'directory' ); ?></label>
		<div class="dir-faq-list"></div>
		<p><a href="#" class="button dir-faq-add"><?php esc_html_e( 'Add FAQ', 'directory' ); ?></a></p>
		<p class="description"><?php esc_html_e( 'Add common questions and answers for this category.', 'directory' ); ?></p>
	</div>
	<?php
}
add_action( directory_gd_category_taxonomy() . '_add_form_fields', 'directory_gd_cat_add_fields' );

/**
 * Edit-term fields (full editor + previews).
 */
function directory_gd_cat_edit_fields( $term ) {
	$content = get_term_meta( $term->term_id, directory_gd_cat_meta_key_content(), true );
	$image1  = get_term_meta( $term->term_id, directory_gd_cat_meta_key_image1(), true );
	$image2  = get_term_meta( $term->term_id, directory_gd_cat_meta_key_image2(), true );
	$faq     = get_term_meta( $term->term_id, directory_gd_cat_meta_key_faq(), true );
	if ( ! is_array( $faq ) ) {
		$faq = array();
	}
	?>
	<tr class="form-field term-directory-cat-content-wrap">
		<th scope="row"><label for="directory_cat_content"><?php esc_html_e( 'Category content', 'directory' ); ?></label></th>
		<td>
			<?php
			wp_editor(
				wp_kses_post( (string) $content ),
				'directory_cat_content',
				array(
					'textarea_name' => 'directory_cat_content',
					'textarea_rows' => 10,
					'media_buttons' => true,
					'tinymce'       => true,
				)
			);
			?>
			<p class="description"><?php esc_html_e( 'Displayed on the category archive page under the business cards.', 'directory' ); ?></p>
		</td>
	</tr>
	<tr class="form-field term-directory-cat-images-wrap">
		<th scope="row"><?php esc_html_e( 'Category images', 'directory' ); ?></th>
		<td>
			<div style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px;max-width:760px;">
				<?php directory_gd_cat_render_image_field( __( 'Image 1', 'directory' ), 'directory_cat_image_1_id', $image1 ); ?>
				<?php directory_gd_cat_render_image_field( __( 'Image 2', 'directory' ), 'directory_cat_image_2_id', $image2 ); ?>
			</div>
			<p class="description"><?php esc_html_e( 'Optional: show up to two images in the category content section.', 'directory' ); ?></p>
		</td>
	</tr>
	<tr class="form-field term-directory-cat-faq-wrap">
		<th scope="row"><?php esc_html_e( 'FAQs', 'directory' ); ?></th>
		<td>
			<div class="dir-faq-list">
				<?php
				$idx = 0;
				foreach ( $faq as $item ) {
					$q = isset( $item['q'] ) ? (string) $item['q'] : '';
					$a = isset( $item['a'] ) ? (string) $item['a'] : '';
					?>
					<div class="dir-faq-item" style="border:1px solid #ddd;padding:10px;margin:10px 0;border-radius:6px;">
						<p style="margin:0 0 6px;"><strong><?php esc_html_e( 'Question', 'directory' ); ?></strong></p>
						<input type="text" class="widefat" name="directory_cat_faq[<?php echo (int) $idx; ?>][q]" value="<?php echo esc_attr( $q ); ?>" placeholder="<?php esc_attr_e( 'Question', 'directory' ); ?>" />
						<p style="margin:10px 0 6px;"><strong><?php esc_html_e( 'Answer', 'directory' ); ?></strong></p>
						<textarea class="widefat" rows="3" name="directory_cat_faq[<?php echo (int) $idx; ?>][a]" placeholder="<?php esc_attr_e( 'Answer', 'directory' ); ?>"><?php echo esc_textarea( $a ); ?></textarea>
						<p style="margin:10px 0 0;"><a href="#" class="button dir-faq-remove"><?php esc_html_e( 'Remove', 'directory' ); ?></a></p>
					</div>
					<?php
					$idx++;
				}
				?>
			</div>
			<p><a href="#" class="button dir-faq-add"><?php esc_html_e( 'Add FAQ', 'directory' ); ?></a></p>
			<p class="description"><?php esc_html_e( 'Add common questions and answers for this category.', 'directory' ); ?></p>
		</td>
	</tr>
	<?php
}
add_action( directory_gd_category_taxonomy() . '_edit_form_fields', 'directory_gd_cat_edit_fields', 10, 1 );

/**
 * Save handler for term meta.
 */
function directory_gd_cat_save_fields( $term_id ) {
	if ( isset( $_POST['directory_cat_content'] ) ) {
		$content = wp_kses_post( wp_unslash( $_POST['directory_cat_content'] ) );
		update_term_meta( $term_id, directory_gd_cat_meta_key_content(), $content );
	}

	if ( isset( $_POST['directory_cat_image_1_id'] ) ) {
		update_term_meta( $term_id, directory_gd_cat_meta_key_image1(), absint( $_POST['directory_cat_image_1_id'] ) );
	}
	if ( isset( $_POST['directory_cat_image_2_id'] ) ) {
		update_term_meta( $term_id, directory_gd_cat_meta_key_image2(), absint( $_POST['directory_cat_image_2_id'] ) );
	}

	if ( isset( $_POST['directory_cat_faq'] ) && is_array( $_POST['directory_cat_faq'] ) ) {
		$raw = wp_unslash( $_POST['directory_cat_faq'] );
		$out = array();
		foreach ( $raw as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}
			$q = isset( $item['q'] ) ? sanitize_text_field( $item['q'] ) : '';
			$a = isset( $item['a'] ) ? wp_kses_post( $item['a'] ) : '';
			if ( $q === '' && $a === '' ) {
				continue;
			}
			$out[] = array(
				'q' => $q,
				'a' => $a,
			);
		}
		update_term_meta( $term_id, directory_gd_cat_meta_key_faq(), $out );
	} else {
		// If no FAQ posted, clear it.
		update_term_meta( $term_id, directory_gd_cat_meta_key_faq(), array() );
	}
}
add_action( 'created_' . 'gd_placecategory', 'directory_gd_cat_save_fields', 10, 1 );
add_action( 'edited_' . 'gd_placecategory', 'directory_gd_cat_save_fields', 10, 1 );

/**
 * Optionally seed a bit of dummy CMS content/FAQs for categories that have none yet.
 * This helps visually test the layout on archive pages.
 *
 * Runs once in admin; only fills empty categories and never overwrites existing content.
 */
function directory_gd_cat_seed_dummy_content() {
	if ( ! is_admin() ) {
		return;
	}

	$flag = get_option( 'directory_gd_cat_cms_seeded', false );
	if ( $flag ) {
		return;
	}

	$tax = directory_gd_category_taxonomy();
	$terms = get_terms(
		array(
			'taxonomy'   => $tax,
			'hide_empty' => false,
			'number'     => 5,
		)
	);

	if ( is_wp_error( $terms ) || empty( $terms ) ) {
		update_option( 'directory_gd_cat_cms_seeded', 1 );
		return;
	}

	foreach ( $terms as $term ) {
		$term_id   = (int) $term->term_id;
		$has_any   = false;
		$content   = get_term_meta( $term_id, directory_gd_cat_meta_key_content(), true );
		$image1    = get_term_meta( $term_id, directory_gd_cat_meta_key_image1(), true );
		$image2    = get_term_meta( $term_id, directory_gd_cat_meta_key_image2(), true );
		$faq_exist = get_term_meta( $term_id, directory_gd_cat_meta_key_faq(), true );

		if ( is_string( $content ) && trim( $content ) !== '' ) {
			$has_any = true;
		}
		if ( ! empty( $image1 ) || ! empty( $image2 ) ) {
			$has_any = true;
		}
		if ( ! empty( $faq_exist ) && is_array( $faq_exist ) ) {
			$has_any = true;
		}

		if ( $has_any ) {
			continue;
		}

		// Seed simple content + FAQs.
		$sample_content  = '<p><strong>' . esc_html__( 'About this category', 'directory' ) . '</strong></p>';
		$sample_content .= '<p>' . esc_html__( 'This is sample copy for your category CMS section. Replace it in Places → Categories → edit this category.', 'directory' ) . '</p>';
		update_term_meta( $term_id, directory_gd_cat_meta_key_content(), wp_kses_post( $sample_content ) );

		$sample_faq = array(
			array(
				'q' => __( 'What kind of businesses are listed here?', 'directory' ),
				'a' => __( 'This sample FAQ explains what appears in this category. Edit or remove it from the category edit screen.', 'directory' ),
			),
			array(
				'q' => __( 'How do I add my business?', 'directory' ),
				'a' => __( 'Sign in and use the “Add listing” button to submit your business to this category.', 'directory' ),
			),
		);
		update_term_meta( $term_id, directory_gd_cat_meta_key_faq(), $sample_faq );
	}

	// Mark as done so we do not keep re-seeding.
	update_option( 'directory_gd_cat_cms_seeded', 1 );
}
add_action( 'admin_init', 'directory_gd_cat_seed_dummy_content' );


