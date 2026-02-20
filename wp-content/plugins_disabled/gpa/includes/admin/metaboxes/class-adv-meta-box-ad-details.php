<?php

/**
 * Ad details
 *
 * Display the ad details meta box.
 *
 */

defined( 'ABSPATH' ) || exit;

/**
 * Adv_Meta_Box_Ad_Details Class.
 */
class Adv_Meta_Box_Ad_Details {

    /**
	 * Output the metabox.
	 *
	 * @param WP_Post $post
	 */
    public static function output( $post ) {
		global $aui_bs5;

		$zone        = adv_ad_get_meta( $post->ID, 'zone', true );
		$is_gd_zone  = (int) adv_zone_get_meta( $zone, 'link_to_packages', true, 0 );
		$type        = adv_ad_get_meta( $post->ID, 'type', true );
		$target_url  = adv_ad_get_meta( $post->ID, 'target_url', true );
		$locations   = adv_ad_get_meta( $post->ID, 'locations', true );
		$image       = adv_ad_get_meta( $post->ID, 'image', true );
		$code        = adv_ad_get_meta( $post->ID, 'code', true );
		$description = adv_ad_get_meta( $post->ID, 'description', true );
		$listing     = adv_ad_get_meta( $post->ID, 'listing', true );
		$new_tab     = adv_ad_get_meta( $post->ID, 'new_tab', true );

        do_action( 'adv_zone_details_meta_box_top', $post );

		// Nonce field.
        wp_nonce_field( 'adv_meta_nonce', 'adv_meta_nonce' );

		?>

		<style>
            #poststuff .input-group-text,
            #poststuff .form-control {
                border-color: #7e8993;
            }

            .bsui label.col-sm-3.col-form-label {
                font-weight: 600;
            }

        </style>
		<div class='bsui' style='max-width: 600px;padding-top: 10px;'>

			<?php do_action( 'adv_ad_details_form_first', $post ); ?>

			<?php do_action( 'adv_ad_details_form_form_before_show_advertisers', $post ); ?>
            <div class="mb-3 row">
                <label class="col-sm-3 col-form-label" for="adv_advertiser"><span><?php esc_html_e( 'Advertiser', 'advertising' ); ?></span></label>
                <div class="col-sm-8">
					<?php
						wp_dropdown_users(
							array(
								'id'               => 'adv_advertiser',
								'name'             => 'advertiser',
								'selected'         => $post->post_author,
								'include_selected' => true,
								'show'             => 'display_name_with_login',
								'class'            => 'adv-advertisers-list regular-text form-select',
							)
						);
					?>
                </div>
            </div>
            <?php do_action( 'adv_ad_details_form_form_after_show_advertisers', $post ); ?>

			<?php do_action( 'adv_ad_details_form_form_before_show_zone', $post ); ?>
        	<div class="mb-3 row">
                <label class="col-sm-3 col-form-label" for="adv_zone"><span><?php esc_html_e( 'Zone', 'advertising' ); ?></span></label>
                <div class="col-sm-8">
					<?php
						adv_dropdown_zones(
							array(
								'echo'             => true,
								'id'               => 'adv_zone',
								'name'             => 'zone',
								'selected'         => empty( $zone ) ? '-1' : (int) $zone,
								'include_selected' => true,
								'class'            => 'adv-zones-list regular-text',
								'label_type'       => 'hidden',
								'is_gd'            => ! empty( $is_gd_zone ),
							)
						);
					?>
                </div>
            </div>
            <?php do_action( 'adv_ad_details_form_form_after_show_zone', $post ); ?>

			<?php do_action( 'adv_ad_details_form_form_before_show_type', $post ); ?>
        	<div class="mb-3 row">
                <label class="col-sm-3 col-form-label" for="adv_type"><span><?php esc_html_e( 'Ad Type', 'advertising' ); ?></span></label>
                <div class="col-sm-8">
					<?php
						aui()->select(
							array(
								'id'          => 'adv_type',
								'label'       => __( 'Ad Type', 'advertising' ),
								'name'        => 'type',
								'value'       => empty( $type ) ? 'text' : esc_attr( $type ),
								'placeholder' => __( 'Select ad type', 'advertising' ),
								'options'     => advertising_ad_types( true ),
							),
							true
						);
					?>
					<?php if ( ! empty( $is_gd_zone ) ) : ?>
					<script>
						jQuery('document').ready(function(){
							jQuery( '#adv_type option:not(:selected), #adv_select_listing option:not(:selected)' ).attr( 'disabled', 'disabled' )
						})
					</script>
					<?php endif; ?>
                </div>
            </div>
            <?php do_action( 'adv_ad_details_form_form_after_show_type', $post ); ?>

			<?php do_action( 'adv_ad_details_form_form_before_target_url', $post ); ?>
        	<div class="mb-3 row adv-none adv-show-text adv-show-image">
                <label class="col-sm-3 col-form-label" for="adv_target_url"><span><?php esc_html_e( 'Target URL', 'advertising' ); ?></span></label>
                <div class="col-sm-8">
					<?php

						aui()->input(
							array(
								'type'        => 'url',
								'id'          => 'adv_target_url',
								'label'       => __( 'Target URL', 'advertising' ),
								'name'        => 'target_url',
								'value'       => empty( $target_url ) ? '' : esc_url( $target_url ),
								'placeholder' => 'https://example.com',
							),
							true
						);

						aui()->input(
							array(
								'type'    => 'checkbox',
								'id'      => 'adv_new_tab',
								'label'   => __( 'Open in new tab', 'advertising' ),
								'name'    => 'new_tab',
								'checked' => ! empty( $new_tab ),
								'value'   => 1,
							),
							true
						);

					?>

					<span class="form-text d-block text-muted"><?php esc_html_e( 'Where should users be redirected to after clicking on the ad?', 'advertising' ); ?></span>

                </div>
            </div>
            <?php do_action( 'adv_ad_details_form_form_after_target_url', $post ); ?>

			<?php

				do_action( 'adv_ad_details_form_form_before_show_image', $post );

				aui()->input(
					array(
						'label'             => __( 'Ad Image', 'advertising' ),
						'label_type'        => 'horizontal',
						'label_col'         => '3',
						'class'             => 'regular-text',
						'wrap_class'        => 'adv-media-upload adv-none adv-show-image',
						'type'              => 'url',
						'name'              => 'image',
						'value'             => empty( $image ) ? '' : esc_url( $image ),
						'id'                => 'adv_image',
						'placeholder'       => __( 'Enter image URL', 'advertising' ),
						'input_group_right' => '<button class="btn btn-outline-secondary" type="button"><i class="fa-solid fa-upload"></i></button>',
						'help_text'         => sprintf(
							'<img src="%s" class="img-fluid img-fluid border shadow rounded %s" style="max-width: 100%%; height: auto;" />',
							empty( $image ) ? '' : esc_url( $image ),
							empty( $image ) ? 'd-none' : ''
						),
					),
					true
				);

				do_action( 'adv_ad_details_form_form_after_show_image', $post );
			?>

			<?php do_action( 'adv_ad_details_form_form_before_show_code', $post ); ?>
            <div class="mb-3 row adv-none adv-show-code">
                <label class="col-sm-3 col-form-label" for="adv_code"><span><?php esc_html_e( 'Ad Code', 'advertising' ); ?></span></label>
                <div class="col-sm-8">
					<textarea name="code" id="adv_code" class="form-control regular-text"><?php echo esc_textarea( $code ); ?></textarea>
					<p class="description"><?php esc_html_e( 'HTML or JavaScript code (e.g adsense) responsible for displaying the ad', 'advertising' ); ?></p>
                </div>
            </div>
            <?php do_action( 'adv_ad_details_form_form_after_show_code', $post ); ?>

			<?php do_action( 'adv_ad_details_form_form_before_show_description', $post ); ?>
        	<div class="mb-3 row adv-none adv-show-text">
                <label class="col-sm-3 col-form-label" for="adv_description"><span><?php esc_html_e( 'Ad Text', 'advertising' ); ?></span></label>
                <div class="col-sm-8">
					<textarea maxlength="120" name="description" id="adv_description" class="regular-text form-control"><?php echo esc_textarea( $description ); ?></textarea>
					<p class="description"><?php esc_html_e( 'Shown below the title. Must be 120 characters or less.', 'advertising' ); ?></p>
                </div>
            </div>
            <?php do_action( 'adv_ad_details_form_form_after_show_description', $post ); ?>

			<?php
				if ( class_exists( 'GeoDirectory' ) && is_user_logged_in() ) :
					$user_listings = array();

					if ( ! empty( $listing ) ) {
						$listing_status = get_post_status( (int) $listing );
						$suffix = '';

						// Display ID to admins.
						if ( current_user_can( 'manage_options' ) ) {
							$suffix .= ' #' . (int) $listing;
						}

						// Display post status for non published ad.
						if ( $listing_status != 'publish' ) {
							$suffix .= ' - ' . geodir_get_post_status_name( $listing_status );
						}

						$user_listings[ intval( $listing ) ] = Adv_GeoDirectory::get_post_title( $listing ) . $suffix;
					}
        	?>

				<?php do_action( 'adv_ad_details_form_form_before_show_listing', $post ); ?>
          			<div class="mb-3 row adv-none adv-show-listing">
						<label class="col-sm-3 col-form-label" for="adv_select_listing"><span><?php esc_html_e( 'Select Listing', 'advertising' ); ?></span></label>
						<div class="col-sm-8">
							<?php
								aui()->select(
									array(
										'id'          => 'adv_select_listing',
										'label'       => __( 'Select Listing', 'advertising' ),
										'name'        => 'listing',
										'class'       => 'gpa-select-listing',
										'value'       => empty( $listing ) ? '' : intval( $listing ),
										'placeholder' => __( 'Select listing', 'advertising' ),
										'options'     => $user_listings,
										'select2'     => true,
									),
									true
								);
							?>
						</div>
					</div>
				<?php do_action( 'adv_ad_details_form_form_after_show_listing', $post ); ?>

				<?php if ( defined( 'GEODIR_LOCATION_PLUGIN_FILE' ) ) : ?>
					<div class="mb-3 row adv-none adv-show-text adv-show-image adv-show-code">
						<label class="col-sm-3 col-form-label" for="adv_locations"><span><?php esc_html_e( 'Target locations', 'advertising' ); ?></span></label>
						<div class="col-sm-8">
							<?php

								$default_location = array(
									sanitize_title( geodir_get_option( 'default_location_city' ) ),
									sanitize_title( geodir_get_option( 'default_location_region' ) ),
								);
								$default_location = implode( ',', array_filter( $default_location ) );

								aui()->input(
									array(
										'type'        => 'text',
										'id'          => 'adv_locations',
										'label'       => __( 'Target Location', 'advertising' ),
										'name'        => 'locations',
										'value'       => empty( $locations ) ? '' : esc_attr( $locations ),
										'placeholder' => $default_location,
										'help_text'   => __( 'Optional. Enter a comma separated list of region or city slugs to target.', 'advertising' ),
									),
									true
								);
							?>
						</div>
					</div>
				<?php endif; ?>
			<?php endif; ?>
		</div>
		<?php
		do_action( 'adv_ad_details_meta_box_bottom', $post );

    }

	/**
	 * Returns an array of zone metabox fields.
	 */
	public static function metabox_fields() {

		$fields = array(
            '_adv_ad_description',
            '_adv_ad_advertiser',
            '_adv_ad_zone',
            '_adv_ad_type',
            '_adv_ad_target_url',
			'_adv_ad_locations',
            '_adv_ad_new_tab',
            '_adv_ad_no_bg_color',
            '_adv_ad_bg_color',
            '_adv_ad_text_color',
            '_adv_ad_image',
            '_adv_ad_code',
            '_adv_ad_listing',
            '_adv_ad_listing_content',
        );

		return apply_filters( 'adv_metabox_fields_save_ad', $fields );
	}

	/**
	 * Save the metabox.
	 *
	 * @param int $post_id
	 * @param WP_Post $post
	 * @param array $data
	 */
    public static function save( $post_id, $post, $data ) {

		foreach ( self::metabox_fields() as $field ) {

			$field_name = str_ireplace( '_adv_ad_', '', $field );

			if ( ! isset( $data[ $field_name ] ) ) {
				delete_post_meta( $post_id, $field );
				continue;
			}

			$value = ! is_array( $data[ $field_name ] ) ? sanitize_text_field( $data[ $field_name ] ) : map_deep( $data[ $field_name ], 'sanitize_text_field' );

			if ( 'code' === $field_name ) {
				$value = $data[ $field_name ];
			}

			$value = apply_filters( 'adv_ad_metabox_save_' . $field_name, $value );
			$value = apply_filters( 'adv_ad_metabox_save', $value, $field_name );

			// Save advertiser;
			if ( 'advertiser' === $field_name ) {
				if ( intval( $post->post_author ) !== intval( $value ) ) {
					$args = array(
						'ID'          => $post_id,
						'post_author' => (int) $value,
					);
					wp_update_post( $args );
				}
				continue;
			}

			update_post_meta( $post_id, $field, $value );

		}

		wp_cache_delete( $post_id, 'Adv_Ad' );
		do_action( 'adv_ad_after_metabox_save', $post_id );

	}

}
