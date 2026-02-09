<?php

/**
 * Zone details
 *
 * Display the zone details meta box.
 *
 */

defined( 'ABSPATH' ) || exit;

/**
 * Adv_Meta_Box_Zone_Details Class.
 */
class Adv_Meta_Box_Zone_Details {

    /**
	 * Output the metabox.
	 *
	 * @param WP_Post $post
	 */
    public static function output( $post ) {
		global $aui_bs5;

		$advertise_here_url = adv_zone_get_meta( $post->ID, 'advertise_here_url', true, '' );
		$show_title     = adv_zone_get_meta( $post->ID, 'show_title', true, 'yes' );
		$link_position  = adv_zone_get_meta( $post->ID, 'link_position', true, 1 );
		$count          = adv_zone_get_meta( $post->ID, 'count', true, 1 );
		$max            = adv_zone_get_meta( $post->ID, 'max_ads', true, '' );
		$width          = adv_zone_get_meta( $post->ID, 'width', true );
		$height         = adv_zone_get_meta( $post->ID, 'height', true );
		$hide_frontend  = adv_zone_get_meta( $post->ID, 'hide_frontend', true, 0 );
		$display_grid   = adv_zone_get_meta( $post->ID, 'display_grid', true, 0 );
		$ads_per_grid   = adv_zone_get_meta( $post->ID, 'ads_per_grid', true, 2 );
		$ad_rotation    = adv_zone_get_meta( $post->ID, 'ad_rotation', true, 0 );
        $ad_rotation_interval = (int) adv_zone_get_meta( $post->ID, 'ad_rotation_interval', true, 60 );
		$price          = floatval( adv_zone_get_meta( $post->ID, 'price', true, 0 ) );
		$pricing_term   = adv_zone_get_meta( $post->ID, 'pricing_term', true, '1000' );
		$pricing_type   = adv_zone_get_meta( $post->ID, 'pricing_type', true, 'impressions' );
	    $package_link   = adv_zone_get_meta( $post->ID, 'link_to_packages', true, false );
		$zone_description = get_the_excerpt( $post->ID );
		$allowed_ad_types = adv_zone_get_meta( $post->ID, 'allowed_ad_types', true, false );

		if ( empty( $allowed_ad_types ) ) {
			$allowed_ad_types = array_keys( advertising_ad_types() );
		}

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

			.linked-to-geodirectory #adv_size_row {
                display: none;
            }

        </style>
		<div class='bsui' style='padding-top: 10px;'>

			<?php do_action( 'adv_zone_details_form_first', $post ); ?>

			<?php do_action( 'adv_zone_details_form_form_before_show_price', $post ); ?>

		<div class="mb-3 
        <?php
        if ( ! $package_link ) {
echo 'd-none';}
?>
" id="adv_price_row_warning">
			<?php
			echo aui()->alert(
				array(
					'type'    => 'info',
					'content' => __( 'Pricing and expiry terms are controlled by the linked GeoDirectory price package.', 'advertising' ),
				)
			);
			?>
		</div>
            <div class="mb-3 row 
            <?php
            if ( $package_link ) {
echo 'd-none';}
?>
" id="adv_price_row">
                <label class="col-sm-3 col-form-label" for="adv_price"><span><?php _e( 'Price', 'advertising' ); ?></span></label>
                <div class="col-sm-8">
                    <div class="row align-items-center">
                        <div class="col-sm-4 mb-3">
							<?php
								aui()->input(
									array(
										'id'               => 'adv_price',
										'name'             => 'price',
										'value'            => $price,
										'type'             => 'number',
										'placeholder'      => adv_format_decimal( 0 ),
										'no_wrap'          => true,
										'input_group_right' => false !== strstr( adv_currency_position(), 'left' ) ? '' : adv_currency_sign(),
										'input_group_left' => false !== strstr( adv_currency_position(), 'left' ) ? adv_currency_sign() : '',
									),
									true
								);
							?>
                        </div>

						<div class="col-sm-1 mb-3">
							<?php _e( 'for', 'advertising' ); ?>
                        </div>

						<div class="col-sm-3 mb-3">
							<?php
								aui()->input(
									array(
										'id'          => 'advertising-term',
										'name'        => 'pricing_term',
										'value'       => $pricing_term,
										'type'        => 'number',
										'placeholder' => __( 'Unlimited', 'advertising' ),
										'no_wrap'     => true,
									),
									true
								);
							?>
                        </div>

						<div class="col-sm-4">
                            <?php
                                aui()->select(
                                    array(
                                        'id'               => 'advertising_pricing_type',
                                        'name'             => 'pricing_type',
                                        'label'            => __( 'Pricing Type', 'advertising' ),
                                        'value'            => $pricing_type,
                                        'select2'          => true,
                                        'data-allow-clear' => 'false',
                                        'options'          => adv_get_pricing_types(),
									),
									true
                                );
                            ?>
                        </div>
					
                    </div>

                </div>
            </div>
            <?php do_action( 'adv_zone_details_form_form_after_show_price', $post ); ?>

			<?php do_action( 'adv_zone_details_form_form_before_show_title', $post ); ?>
			<div class="mb-3 row">
                <label class="col-sm-3 col-form-label" for="advertising_show_title"><span><?php _e( 'Show Title', 'advertising' ); ?></span></label>
                <div class="col-sm-8">
					<?php
                        echo aui()->select(
                            array(
                                'id'               => 'advertising_show_title',
                                'name'             => 'show_title',
                                'label'            => __( 'Show Title', 'advertising' ),
                                'placeholder'      => __( 'Show Title', 'advertising' ),
                                'value'            => $show_title,
                                'select2'          => true,
                                'data-allow-clear' => 'false',
                                'options'          => array(
                                    'yes' => __( 'Yes', 'advertising' ),
                                    'no'  => __( 'No', 'advertising' ),
								),
								'help_text'        => __( 'Choose yes if you would like the title of this zone to show on the frontend.', 'advertising' ),
                            )
                        );
                    ?>
                </div>
            </div>
			<?php do_action( 'adv_zone_details_form_form_after_show_title', $post ); ?>


			<?php do_action( 'adv_zone_details_form_form_before_show_ad_size', $post ); ?>
			<div class="mb-3 row" id="adv_size_row">
                <label class="col-sm-3 col-form-label" for="advertising-width"><span><?php _e( 'Ad Size', 'advertising' ); ?></span></label>
                <div class="col-sm-8">

					<div class="row">
						<div class="col-sm-6">
							<div>
								<input type="number" class="form-control" name="width" style="width: 100%;" placeholder="<?php esc_attr_e( 'Full width', 'advertising' ); ?>" id="advertising-width" value="<?php echo esc_attr( $width ); ?>" >
							</div>
						</div>
						<div class="col-sm-6">
							<div>
								<input type="number" class="form-control" name="height" style="width: 100%;" placeholder="<?php esc_attr_e( 'Auto height', 'advertising' ); ?>" id="advertising-height" value="<?php echo esc_attr( $height ); ?>" >
							</div>
						</div>
					</div>
					<small class="form-text d-block text-muted"><?php _e( 'Ad size (width x height) in pixels. Leave blank for any size.', 'advertising' ); ?></small>

                </div>
            </div>
			<?php do_action( 'adv_zone_details_form_form_after_show_ad_size', $post ); ?>

			<?php do_action( 'adv_zone_details_form_form_before_show_visible_ads', $post ); ?>
			<div class="mb-3 row">
                <label class="col-sm-3 col-form-label" for="advertising_visible_ads"><span><?php _e( 'Visible Ads', 'advertising' ); ?></span></label>
                <div class="col-sm-8">

					<?php
						echo aui()->select(
							array(
								'id'               => 'advertising_visible_ads',
								'name'             => 'count',
								'label'            => __( 'Visible Ads', 'advertising' ),
								'value'            => $count,
								'select2'          => true,
								'data-allow-clear' => 'false',
								'options'          => array_combine(
                                    range( 1, 10 ),
                                    range( 1, 10 )
                                ) + array(
									'all' => __( 'All', 'advertising' ),
								),
								'help_text'        => __( 'Number of ads that are visible at the same time.', 'advertising' ),
							)
						);
					?>

                </div>
            </div>
			<?php do_action( 'adv_zone_details_form_form_after_show_visible_ads', $post ); ?>
			<?php do_action( 'adv_zone_details_form_form_before_allowed_ad_types', $post ); ?>
			<div class="mb-3 row">
                <label class="col-sm-3 col-form-label" for="advertising_allowed_ad_types"><span><?php _e( 'Allowed Ad Types', 'advertising' ); ?></span></label>
                <div class="col-sm-8">
					<?php
						echo aui()->select(
							array(
								'id'               => 'advertising_allowed_ad_types',
								'name'             => 'allowed_ad_types',
								'label'            => __( 'Allowed Ad Types', 'advertising' ),
								'value'            => $allowed_ad_types,
								'select2'          => true,
								'data-allow-clear' => 'false',
								'multiple'         => true,
								'options'          => advertising_ad_types(),
								'help_text'        => __( 'What ad types should be allowed on the frontend?', 'advertising' ),
							)
						);
					?>
                </div>
            </div>
			<?php do_action( 'adv_zone_details_form_form_after_allowed_ad_types', $post ); ?>

			<?php do_action( 'adv_zone_details_form_form_before_max_ads', $post ); ?>
			<div class="mb-3 row">
                <label class="col-sm-3 col-form-label" for="advertising_max_ads"><span><?php _e( 'Maximum Ads', 'advertising' ); ?></span></label>
                <div class="col-sm-8">
					<input type="number" class="form-control" name="max_ads" placeholder="<?php esc_attr_e( 'Unlimited', 'advertising' ); ?>" id="advertising_max_ads" value="<?php echo empty( $max ) ? '' : intval( $max ); ?>" >
					<small class="form-text d-block text-muted"><?php _e( 'Maximum number of active ads that can be added to this zone.', 'advertising' ); ?></small>
                </div>
            </div>
			<?php do_action( 'adv_zone_details_form_form_after_max_ads', $post ); ?>

			<?php do_action( 'adv_zone_details_form_form_before_show_grid', $post ); ?>
			<div class="mb-3 row">
                <label class="col-sm-3 col-form-label" for="adv_grid"><span><?php _e( 'Grid', 'advertising' ); ?></span></label>
                <div class="col-sm-8">
					<input type="checkbox" id="adv_grid" name="display_grid" value="1" <?php checked( $display_grid, 1 ); ?> />
					<span><?php _e( 'Display ads in a grid with', 'advertising' ); ?></span>&nbsp;
					<input type="number" class="form-control form-control-sm d-inline w-auto" name="ads_per_grid" class="w-25" value="<?php echo $ads_per_grid; ?>" />&nbsp;<?php _e( 'ads per row', 'advertising' ); ?></span>
                </div>
            </div>
			<?php do_action( 'adv_zone_details_form_form_after_show_grid', $post ); ?>

			<?php do_action( 'adv_zone_details_form_form_before_hide_frontend', $post ); ?>
			<div class="mb-3 row">
                <label class="col-sm-3 col-form-label" for="adv_hide_frontend"><span><?php _e( 'Admin Only', 'advertising' ); ?></span></label>
                <div class="col-sm-8">
					<label><input type="checkbox" id="adv_hide_frontend" name="hide_frontend" value="1" <?php checked( $hide_frontend, 1 ); ?> />
					<span><?php _e( 'Hide this zone in the drop down on the frontend ad placement form.', 'advertising' ); ?></span></label>
                </div>
            </div>
			<?php do_action( 'adv_zone_details_form_form_after_hide_frontend', $post ); ?>

            <?php do_action( 'adv_zone_details_form_form_before_ad_rotation', $post ); ?>
			<div class="mb-3 row">
                <label class="col-sm-3 col-form-label" for="adv_ad_rotation"><span><?php _e( 'Ad Rotation', 'advertising' ); ?></span></label>
                <div class="col-sm-8">
					<input type="checkbox" id="adv_ad_rotation" name="ad_rotation" value="1" <?php checked( $ad_rotation, 1 ); ?> />
					<span><?php _e( 'Auto rotate ads in this zone after every', 'advertising' ); ?></span>&nbsp;
					<input type="number" class="form-control form-control-sm d-inline w-auto" name="ad_rotation_interval" class="w-25" value="<?php echo absint( $ad_rotation_interval ); ?>" />&nbsp;<?php _e( 'seconds', 'advertising' ); ?></span>
					<small class="form-text d-block text-muted"><?php _e( 'Allow ads to auto-rotate in this zone while the user is on the page. New ads will be loaded automatically via AJAX.', 'advertising' ); ?></small>&nbsp;
                </div>
            </div>
			<?php do_action( 'adv_zone_details_form_form_after_ad_rotation', $post ); ?>

			<?php do_action( 'adv_zone_details_form_form_before_advertisement_link', $post ); ?>
			<div class="mb-3 row" id="adv_advertisement_link_wrapper">
                <label class="col-sm-3 col-form-label" for="advertisement_link"><span><?php _e( 'Advertisement Link', 'advertising' ); ?></span></label>
                <div class="col-sm-8">
					<?php
                        echo aui()->select(
                            array(
                                'id'               => 'advertisement_link',
                                'name'             => 'link_position',
                                'label'            => __( 'Show "Advertise Here" link', 'advertising' ),
                                'value'            => (int) $link_position,
                                'select2'          => true,
                                'data-allow-clear' => 'false',
                                'options'          => array(
                                    '0' => __( 'Do not show', 'advertising' ),
                                    '1' => __( 'At the bottom', 'advertising' ),
									'2' => __( 'At the top', 'advertising' ),
                                    '3' => __( 'At the top and bottom', 'advertising' ),
									'4' => __( 'Only show if there are no ads', 'advertising' ),
								),
								'help_text'        => __( 'Show an "Advertise Here" link at the bottom, top, or both top and bottom of the zone.', 'advertising' ),
                            )
                        );
                    ?>
                </div>
            </div>
			<div class="mb-3 row" id="adv_advertise_here_url">
                <label class="col-sm-3 col-form-label" for="adv-advertise-here-url"><?php _e( '"Advertise Here" URL', 'advertising' ); ?></label>
                <div class="col-sm-8">

					<div>
						<input type="text" class="form-control" name="advertise_here_url" placeholder="<?php echo esc_url( adv_dashboard_endpoint_url( 'new-ad', array( 'zone' => $post->ID ) ) ); ?>" id="adv-advertise-here-url" value="<?php echo esc_attr( $advertise_here_url ); ?>" >
					</div>
					<small class="form-text d-block text-muted"><?php _e( 'Enter the URL to use for the "advertise here" link, or leave empty to use the default URL.', 'advertising' ); ?></small>

                </div>
            </div>
			<?php do_action( 'adv_zone_details_form_form_after_advertisement_link', $post ); ?>

			<?php do_action( 'adv_zone_details_form_form_before_zone_description', $post ); ?>
			<div class="mb-3 row">
                <label class="col-sm-3 col-form-label" for="zone_description"><span><?php _e( 'Zone Description', 'advertising' ); ?></span></label>
                <div class="col-sm-8">
					<?php
                        echo aui()->textarea(
                            array(
                                'id'         => 'zone_description',
                                'name'       => 'excerpt',
                                'label'      => __( 'Zone Description', 'advertising' ),
                                'value'      => wp_kses_post( $zone_description ),
								'allow_tags' => true,
								'wysiwyg'    => true,
								'help_text'  => __( 'Enter some help text about the zone, shown when adding a new ad.', 'advertising' ),
                            )
                        );
                    ?>
                </div>
            </div>
			<?php do_action( 'adv_zone_details_form_form_after_zone_description', $post ); ?>
	
		</div>

		<?php
		do_action( 'adv_zone_details_meta_box_bottom', $post );
    }

	/**
	 * Returns an array of zone metabox fields.
	 */
	public static function metabox_fields() {

		$fields = array(
			'_adv_zone_advertise_here_url',
			'_adv_zone_show_title',
			'_adv_zone_width',
			'_adv_zone_height',
			'_adv_zone_count',
			'_adv_zone_max_ads',
			'_adv_zone_link_position',
			'_adv_zone_user_role_to',
			'_adv_zone_user_roles',
			'_adv_zone_post_type_to',
			'_adv_zone_post_types',
			'_adv_zone_taxonomy_to',
			'_adv_zone_taxonomies',
			'_adv_zone_post_to',
			'_adv_zone_posts',
			'_adv_zone_term_to',
			'_adv_zone_terms',
			'_adv_zone_inject',
			'_adv_zone_price',
			'_adv_zone_pricing_term', //e.g 10,000 impressions
			'_adv_zone_pricing_type',  //cpc,cpm,cpt
			'_adv_zone_display_grid',
			'_adv_zone_hide_frontend',
			'_adv_zone_allowed_ad_types',
			'_adv_zone_ads_per_grid',
			'_adv_zone_link_to_packages',
            '_adv_zone_ad_rotation',
            '_adv_zone_ad_rotation_interval',
		);

		return apply_filters( 'adv_metabox_fields_save_zone', $fields );
	}

	/**
	 * Save the metabox.
	 *
	 * @param WP_Post $post
	 */
    public static function save( $post_id ) {

		foreach ( self::metabox_fields() as $field ) {

			$field_name = str_ireplace( '_adv_zone_', '', $field );

			if ( ! isset( $_POST[ $field_name ] ) ) {
				delete_post_meta( $post_id, $field );
				continue;
			}

			switch ( $field_name ) {
				case 'width':
				case 'height':
					$value = absint( $_POST[ $field_name ] );
					$value = $value > 0 ? $value : '';
				    break;
				case 'display_grid':
                case 'ad_rotation':
					$value = empty( $_POST[ $field_name ] ) ? 0 : 1;
                    break;
                case 'ad_rotation_interval':
                    $value = absint( $_POST[ $field_name ] );
                    $value = $value > 0 ? $value : 60;
                    break;
				default:
					$value = isset( $_POST[ $field_name ] ) ? map_deep( $_POST[ $field_name ], 'sanitize_text_field' ) : '';
				    break;
			}

			$value = apply_filters( 'adv_zone_metabox_save_' . $field_name, $value );
			$value = apply_filters( 'adv_zone_metabox_save', $value, $field_name );

			update_post_meta( $post_id, $field, $value );

		}

		do_action( 'adv_zone_after_metabox_save', $post_id );
	}
}
