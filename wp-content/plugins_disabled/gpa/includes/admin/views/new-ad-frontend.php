<div class="adv-nav-content adv-content-new-ad adv-ad-form">
	<h3 class="adv-navc-title mt-3 mb-3">
		<?php echo wp_kses_post( $page_title ); ?>
	</h3>

	<?php
		if ( is_wp_error( $notices ) ) {

			aui()->alert(
				array(
					'content' => esc_html( $notices->get_error_message() ),
					'type'    => 'danger',
				),
				true
			);

		}


		if ( true === $notices ) {

			aui()->alert(
				array(
					'content' => __( 'Your Ad was saved successfully', 'advertising' ),
					'type'    => 'success',
				),
				true
			);

			do_action( 'adv_ad_saved_successfully', $post_id );

			echo '</div>';
			return;
		}

		if ( ! empty( $zone ) && (int) adv_zone_get_meta( $zone, 'link_to_packages', true, 0 ) ) {

			aui()->alert(
				array(
					'content' => __( 'This ad is not editable', 'advertising' ),
					'type'    => 'info',
				),
				true
			);

			echo '</div>';
			return;

		}

		$adv_zone = $zone ? adv_get_zone( $zone ) : array();

		if ( empty( $post_id ) && ! empty( $adv_zone ) && $adv_zone->is_full() ) {
			aui()->alert(
				array(
					'content' => __( 'Ad zone has reached the maximum number of ads allowed for this zone.', 'advertising' ),
					'type'    => 'info',
				),
				true
			);

			echo '</div>';
			return;
		}

	?>
	<form action="<?php echo( esc_url( $form_action ) ); ?>" method="post" class="adv-ad-form" enctype="multipart/form-data">

	<?php

		aui()->input(
			array(
				'id'          => 'advertising-ad-title',
				'label'       => __( 'Title', 'advertising' ),
				'label_class' => '',
				'class'       => 'bg-light',
				'name'        => 'title',
				'value'       => esc_attr( $title ),
				'placeholder' => __( 'Title', 'advertising' ),
				'required'    => true,
				'label_type'  => 'top',
			),
			true
		);

		$available_zones = array();

		if ( empty( $post_id ) && ! ( isset( $_GET['zone'] ) && ! empty( $zone ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			adv_dropdown_zones(
				array(
					'label'     => __( 'Zone', 'advertising' ),
					'selected'  => empty( $zone ) ? '-1' : $zone,
					'id'        => 'advertising-zone-id',
					'hide_full' => true
				)
			);

			foreach ( array_keys( adv_get_dropdown_zones( true ) ) as $zone_id ) {
				$zone_id     = (int) $zone_id;
				$available_zones[] = $zone_id;
				$zone_obj    = get_post( $zone_id );
				$description = empty( $zone_obj ) ? '' : $zone_obj->post_excerpt;

				if ( ! empty( $description ) ) {
					echo wp_kses_post( "<small class='mb-2 form-text d-block text-muted d-none adv-zone-description zone-$zone_id-description'>$description</small>" );
				}

				// Quantities selector.
				$amount       = adv_zone_get_meta( $zone_id, 'price' );
				$pricing_term = (int) adv_zone_get_meta( $zone_id, 'pricing_term' );
				$pricing_type = adv_zone_get_meta( $zone_id, 'pricing_type' );
				$quantities   = array();
				$zone_price   = adv_zone_price( $zone_id, 'per' );

				for ( $i = 1; $i < 20; $i++ ) {
					$quantities[ $i ] = $pricing_term * $i;
				}

				if ( ! empty( $amount ) && ! empty( $pricing_type ) && ! empty( $pricing_term ) ) {

					aui()->select(
						array(
							'id'          => 'adv-zone_' . $zone_id . '_qty',
							'label'       => esc_html( adv_get_pricing_type_title( $pricing_type ) . " &mdash; $zone_price" ),
							'label_class' => '',
							'class'       => 'adv-ad-qty bg-light',
							'wrap_class'  => 'adv-zone-quantites ' . ( absint( $zone ) === $zone_id ? '' : 'd-none' ),
							'name'        => 'zone_' . $zone_id . '_qty',
							'value'       => 1,
							'label_type'  => 'top',
							'options'     => $quantities,
							'select2'     => true,
						),
						true
					);

				}
			}
		} else {
			echo '<input type="hidden" name="zone" value="' . esc_attr( $zone ) . '" id="advertising-zone-id" />';

			// Quantities selector.
			$amount       = adv_zone_get_meta( $zone, 'price' );
			$pricing_term = (int) adv_zone_get_meta( $zone, 'pricing_term' );
			$pricing_type = adv_zone_get_meta( $zone, 'pricing_type' );
			$quantities   = array();
			$zone_price   = adv_zone_price( $zone, 'per' );

			for ( $i = 1; $i < 20; $i++ ) {
				$quantities[ $i ] = $pricing_term * $i;
			}

			if ( ! empty( $amount ) && ! empty( $pricing_type ) && ! empty( $pricing_term ) ) {

				aui()->select(
					array(
						'id'          => 'adv-zone_' . $zone . '_qty',
						'label'       => esc_html( adv_get_pricing_type_title( $pricing_type ) . " &mdash; $zone_price" ),
						'label_class' => '',
						'class'       => 'adv-ad-qty bg-light',
						'wrap_class'  => 'adv-zone-quantites',
						'name'        => 'zone_' . $zone . '_qty',
						'value'       => 1,
						'label_type'  => 'top',
						'options'     => $quantities,
						'select2'     => true,
					),
					true
				);

			}
		}

		$ad_types = advertising_ad_types( false );

		foreach ( adv_get_zones() as $zone_id ) {
			if ( empty( $post_id ) && ! in_array( $zone_id, $available_zones ) ) {
				continue;
			}

			$allowed_ad_types = adv_zone_get_meta( $zone_id, 'allowed_ad_types', true, false );

			if ( empty( $allowed_ad_types ) ) {
				$allowed_ad_types = array_keys( advertising_ad_types() );
			}

			printf(
				'<span class="adv-zone-%s-allowed-ad-types" data-types="%s"></span>',
				(int) $zone_id,
				esc_attr( wp_json_encode( $allowed_ad_types ) )
			);

		}

		aui()->select(
			array(
				'id'          => 'advertising-ad-type',
				'label'       => __( 'Ad Type', 'advertising' ),
				'label_class' => '',
				'class'       => 'adv-ad-type bg-light',
				'name'        => 'type',
				'value'       => $type,
				'placeholder' => __( 'Select Ad Type', 'advertising' ),
				'required'    => true,
				'label_type'  => 'top',
				'options'     => $ad_types,
			),
			true
		);

		$width  = ! empty( $adv_zone ) && ! empty( $adv_zone->meta['width'] ) ? $adv_zone->meta['width'] : 0;
		$height = ! empty( $adv_zone ) && ! empty( $adv_zone->meta['width'] ) ? $adv_zone->meta['height'] : 0;

		$img_style = $width && $height ? 'max-width:' . (int) $width . 'px;max-height:' . (int) $height . 'px;' : 'max-width:100%;height:auto;';

		$image_content = '<div class="position-relative overflow-hidden d-block"><img src="' . ( empty( $image ) ? '' : esc_url( $image ) ) . '" class="img-fluid img-fluid border shadow rounded' . ( empty( $image ) ? ' d-none' : '' ) . '" style="' . esc_attr( $img_style ) . '" /></div>';

		aui()->input(
			array(
				'id'                => 'advertising-ad-image',
				'label'             => __( 'Ad Image', 'advertising' ) . ( empty( $post_id ) || empty( $zone ) ) ? '' : '(' . adv_zone_size( $zone, true ) . ')',
				'wrap_class'        => 'adv-none adv-show-image',
				'class'             => 'bg-light',
				'name'              => 'image',
				'value'             => esc_url( $image ),
				'placeholder'       => 'https://example.com/banner.jpg',
				'label_type'        => 'top',
				'type'              => 'url',
				'help_text'         => __( 'Note: Max upload image size: ', 'advertising' ) . size_format( wp_max_upload_size() ) . $image_content,
				'input_group_right' => '
				<span  class="input-group-text">
					<span class="d-none spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
					<span class="adv-svg"><svg xmlns="http://www.w3.org/2000/svg" height="24" viewBox="0 0 24 24" width="24"><path d="M0 0h24v24H0z" fill="none"/><path d="M19.35 10.04C18.67 6.59 15.64 4 12 4 9.11 4 6.6 5.64 5.35 8.04 2.34 8.36 0 10.91 0 14c0 3.31 2.69 6 6 6h13c2.76 0 5-2.24 5-5 0-2.64-2.05-4.78-4.65-4.96zM14 13v4h-4v-4H7l5-5 5 5h-3z"/></svg></span>
				</span>',
			),
			true
		);

		?>

		<div class="adv_upload_field" style="display: none">
			<input name="image" accept=".jpg,.jpeg,.png,.gif,image/png,image/jpeg,image/jpg,image/gif" id="adv_upload_image" type="file" value="">
		</div>

		<?php

		aui()->input(
			array(
				'id'          => 'advertising-ad-url',
				'label'       => __( 'Target URL', 'advertising' ),
				'wrap_class'  => 'adv-none adv-show-image adv-show-text',
				'class'       => 'bg-light',
				'name'        => 'target_url',
				'value'       => esc_url( $target_url ),
				'placeholder' => 'https://www.mysite.com',
				'label_type'  => 'top',
				'type'        => 'url',
				'help_text'   => __( 'Add a destination URL for the ad. Ex: https://www.mysite.com', 'advertising' ),
			),
			true
		);

		aui()->input(
			array(
				'type'       => 'checkbox',
				'id'         => 'adv_new_tab',
				'label'      => __( 'Open in new tab', 'advertising' ),
				'name'       => 'new_tab',
				'wrap_class' => 'adv-none adv-show-text adv-show-image',
				'checked'    => ! empty( $new_tab ),
				'value'      => 1,
			),
			true
		);

		aui()->textarea(
			array(
				'id'               => 'advertising-ad-content',
				'label'            => __( 'Ad Text', 'advertising' ),
				'label_class'      => '',
				'class'            => 'bg-light',
				'wrap_class'       => 'adv-none adv-show-text',
				'name'             => 'description',
				'value'            => esc_textarea( $ad_text ),
				'placeholder'      => __( 'Enter the ad plain text description.', 'advertising' ),
				'help_text'        => __( 'Shown below the title. Must be 120 characters or less.', 'advertising' ),
				'label_type'       => 'top',
				'extra_attributes' => array(
					'maxlength' => 120,
				),
			),
			true
		);

		?>

		<?php if ( isset( $ad_types['code'] ) ) : ?>
		<div class="<?php echo ( ! empty( $GLOBALS['aui_bs5'] ) ? 'mb-3' : 'form-group' ); ?> adv-none adv-show-code">
			<label for="advertising-ad-code" class=" "><span><?php esc_html_e( 'Ad Code', 'advertising' ); ?></span></label>
			<textarea rows="5" name="code" id="advertising-ad-code" placeholder="<?php esc_attr_e( 'Enter the Ad Code', 'advertising' ); ?>" class="form-control bg-light" spellcheck="false"><?php echo esc_textarea( $ad_code ); ?></textarea>
			<p class="description"><?php esc_html_e( 'HTML or JavaScript code responsible for displaying the ad', 'advertising' ); ?></p>
		</div>
		<?php endif; ?>

		<?php

			if ( class_exists( 'GeoDirectory' ) && is_user_logged_in() ) :

				// Get users last x listings.
				$listings_to_show = apply_filters( 'geodir_advertising_listings_to_show', 10 );
				$user_listings    = array();

				$listings_query_args = array(
					'post_type'      => geodir_get_posttypes(),
					'posts_per_page' => $listings_to_show + 1,
					'post_status'    => Adv_GeoDirectory::get_post_statuses(),
					'author'         => get_current_user_id(),
					'post_parent'    => 0
				);
				$listings_query_args = apply_filters( 'adv_ajax_listings_query_args', $listings_query_args );

				$recent_listings  = get_posts( $listings_query_args );

				foreach ( $recent_listings as $recent_listing ) {
					$user_listings[ intval( $recent_listing->ID ) ] = Adv_GeoDirectory::get_post_title( $recent_listing );
				}

				// Add current listing to the list.
				if ( ! empty( $listing ) && ! isset( $user_listings[ intval( $listing ) ] ) ) {
					$user_listings[ intval( $listing ) ] = Adv_GeoDirectory::get_post_title( $listing );
				}

				$listing_select_class = 'aui-select2 adv-ad-listing gpa-select-listing bg-light';

				if ( count( $user_listings ) < $listings_to_show + 1 ) {
					$listing_select_class .= ' adv-all-listings-shown';
				}

				aui()->select(
					array(
						'id'          => 'advertising-ad-listing',
						'label'       => __( 'Select Listing', 'advertising' ),
						'label_class' => '',
						'class'       => $listing_select_class,
						'wrap_class'  => 'adv-none adv-show-listing',
						'name'        => 'listing',
						'value'       => $listing,
						'placeholder' => __( 'Select Listing', 'advertising' ),
						'label_type'  => 'top',
						'options'     => $user_listings,
						'select2'     => true,
					),
					true
				);

				if ( defined( 'GEODIR_LOCATION_PLUGIN_FILE' ) ) {
					$default_location = array(
						geodir_get_option( 'default_location_city' ),
						geodir_get_option( 'default_location_region' ),
					);
					$default_location = implode( ',', array_filter( $default_location ) );

					aui()->input(
						array(
							'id'          => 'advertising-ad-target-location',
							'label'       => __( 'Target Location', 'advertising' ),
							'wrap_class'  => 'adv-none adv-show-image adv-show-text adv-show-code',
							'class'       => 'bg-light',
							'name'        => 'locations',
							'value'       => empty( $locations ) ? '' : esc_attr( $locations ),
							'placeholder' => $default_location,
							'label_type'  => 'top',
							'type'        => 'text',
							'help_text'   => __( 'Optional. Enter a comma separated list of region or city slugs to target.', 'advertising' ),
						),
						true
					);
				}
			endif;

			if ( ! empty( $post_id ) ) {
				echo "<input type='hidden' name='ad' value='" . absint( $post_id ) . "' />";
			}

			wp_nonce_field( 'adv_save_ad', 'adv_nonce' );

			$zone_sizes = array();

			foreach ( array_keys( adv_get_dropdown_zones() ) as $_zone ) {
				$zone_sizes[ $_zone ] = array(
					'w' => (int) adv_zone_get_meta( $_zone, 'width' ),
					'h' => (int) adv_zone_get_meta( $_zone, 'height' ),
				);
			}
		?>

		<button type="submit" class="btn btn-primary"><?php esc_html_e( 'Save', 'advertising' ); ?></button>
		<input type="hidden" name="adv_action" value="save_ad" />
		<input type="hidden" name="image_x" id="adv_image_x" />
		<input type="hidden" name="image_y" id="adv_image_y" />
		<input type="hidden" name="image_h" id="adv_image_h" />
		<input type="hidden" name="image_w" id="adv_image_w" />
		<input type="hidden" name="widget_h" id="adv_widget_h" />
		<input type="hidden" name="widget_w" id="adv_widget_w" />
		<input type="hidden" id="adv_zone_sizes" value="<?php echo esc_attr( wp_json_encode( $zone_sizes ) ); ?>" />
	</form>
</div>
