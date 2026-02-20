<div class="wrap geodir-booking-settings">

	<?php

		// Display the title.
		$title = esc_html( get_admin_page_title() );
		echo '<h1>' . esc_html($title) . '</h1>';

		// Fire a hook before printing the settings page.
		do_action( 'geodir_booking_settings_page_top' );

		if ( false === $saved_settings ) {
		printf(
            '<div class="error is-dismissible"><p>%s</p></div>',
            esc_html__( 'Could not save your settings. Please try again.', 'geodir-booking' )
        );
		}

		if ( true === $saved_settings ) {
		printf(
            '<div class="notice notice-success is-dismissible"><p>%s</p></div>',
            esc_html__( 'Your settings have been saved.', 'geodir-booking' )
        );
		}

		add_thickbox()
	?>

	<form method="POST" class="geodir-booking-main-settings-form" style="max-width: 1200px;" onsubmit="window.tinyMCE ? window.tinyMCE.triggerSave() : ''">
		<?php wp_nonce_field( 'geodir-booking', 'geodir-booking' ); ?>

		<table class="form-table">
			<tbody>

				<?php foreach ( $settings as $setting_id => $args ) : ?>
					<tr class="form-field-row form-field-row-<?php echo sanitize_html_class( $setting_id ); ?>">
						<th scope="row">
							<label for="geodir-booking-field-<?php echo sanitize_html_class( $setting_id ); ?>"><?php echo esc_html( $args['label'] ); ?></label>
						</th>
						<td>

							<?php if ( 'text' == $args['type'] ) : ?>
								<input
									type="text"
									class="regular-text"
									name="geodir_booking[<?php echo esc_attr( $setting_id ); ?>]"
									value="<?php echo esc_attr( geodir_booking_get_option( $setting_id, $args['default'] ) ); ?>"
									placeholder="<?php echo isset( $args['placeholder'] ) ? esc_attr( $args['placeholder'] ) : ''; ?>"
								>
							<?php endif; ?>

							<?php if ( 'number' == $args['type'] ) : ?>
								<input
									type="number"
									class="regular-text"
									step="0.01"
									min="<?php echo isset( $args['min'] ) ? (float) $args['min'] : '-' . PHP_INT_MAX; ?>"
									max="<?php echo isset( $args['max'] ) ? (float) $args['max'] : PHP_INT_MAX; ?>"
									name="geodir_booking[<?php echo esc_attr( $setting_id ); ?>]"
									value="<?php echo floatval( geodir_booking_get_option( $setting_id, $args['default'] ) ); ?>"
									placeholder="<?php echo isset( $args['placeholder'] ) ? esc_attr( $args['placeholder'] ) : ''; ?>"
								>
							<?php endif; ?>

							<?php if ( 'checkbox' == $args['type'] ) : ?>
								<input type="hidden" name="geodir_booking[<?php echo esc_attr( $setting_id ); ?>]" value="no">
								<input
									type="checkbox"
									name="geodir_booking[<?php echo esc_attr( $setting_id ); ?>]"
									<?php checked( 'no' !== geodir_booking_get_option( $setting_id, $args['default'] ) ); ?>
									value="yes"
								>&nbsp;<span><?php echo isset( $args['label2'] ) ? wp_kses_post( $args['label2'] ) : ''; ?></span>
							<?php endif; ?>

							<?php
								if ( 'select' == $args['type'] ) :
								$value = geodir_booking_get_option( $setting_id, $args['default'] );
							?>
								<select name= "geodir_booking[<?php echo esc_attr( $setting_id ); ?>]" class="regular-text">
									<option value="" <?php selected( '', $value ); ?> disabled><?php echo esc_html( $args['placeholder'] ); ?></option>
							<?php foreach ( $args['options'] as $option_id => $option_label ) : ?>
										<option value="<?php echo esc_attr( $option_id ); ?>" <?php selected( $option_id, $value ); ?>><?php echo esc_html( $option_label ); ?></option>
									<?php endforeach; ?>
								</select>
							<?php endif; ?>

							<?php if ( 'textarea' == $args['type'] ) : ?>
								<textarea
									class="widefat"
									rows="10"
									name="geodir_booking[<?php echo esc_attr( $setting_id ); ?>]"
								><?php echo esc_textarea( wp_unslash( geodir_booking_get_option( $setting_id, $args['default'] ) ) ); ?></textarea>
							<?php endif; ?>

							<?php if ( 'cancellation_policies' == $args['type'] ) : ?>
								<div class="geodir-cancellation-policies">

									<?php
										$policies       = wp_unslash( geodir_booking_get_option( $setting_id ) );
										$policies       = is_array( $policies ) ? $policies : array();
										$default_policy = geodir_booking_get_option( $setting_id . '_default', '' );

										$policies['geodir_booking_default_template'] = array(
											'policy_name' => '',
											'policy_desc' => '',
											'policy_days' => 5,
											'policy_if'   => 100,
											'policy_if_not' => 50,
										);

										foreach ( $policies as $policy_id => $policy ) :
											$policy = wp_parse_args( $policy, $policies['geodir_booking_default_template'] );
										?>
											<div class="card geodir-booking-policy geodir-booking-policy__<?php echo esc_attr( $policy_id ); ?>" style="max-width: 720px;">

												<p style="margin: 1em 0;">
													<label for="geodir-booking-policy-days-<?php echo esc_attr( $policy_id ); ?>"><?php esc_html_e( 'If cancelled at least', 'geodir-booking' ); ?></label>&nbsp;
													<input type="number" id="geodir-booking-policy-days-<?php echo esc_attr( $policy_id ); ?>" name="geodir_booking[<?php echo esc_attr( $setting_id ); ?>][<?php echo esc_attr( $policy_id ); ?>][policy_days]" value="<?php echo esc_attr( $policy['policy_days'] ); ?>" min="0" max="100" step="1" style="width: 60px;">&nbsp;
													<label for="geodir-booking-policy-policy-if-<?php echo esc_attr( $policy_id ); ?>"><?php esc_html_e( 'days before arrival, then refund', 'geodir-booking' ); ?></label>&nbsp;
													<input type="number" id="geodir-booking-policy-policy-if-<?php echo esc_attr( $policy_id ); ?>" name="geodir_booking[<?php echo esc_attr( $setting_id ); ?>][<?php echo esc_attr( $policy_id ); ?>][policy_if]" value="<?php echo esc_attr( $policy['policy_if'] ); ?>" style="width: 60px;">%,&nbsp;
													<label for="geodir-booking-policy-policy-if-not-<?php echo esc_attr( $policy_id ); ?>"><?php esc_html_e( 'else refund', 'geodir-booking' ); ?></label>
													<input type="number" id="geodir-booking-policy-policy-if-not-<?php echo esc_attr( $policy_id ); ?>" name="geodir_booking[<?php echo esc_attr( $setting_id ); ?>][<?php echo esc_attr( $policy_id ); ?>][policy_if_not]" value="<?php echo esc_attr( $policy['policy_if_not'] ); ?>" style="width: 60px;">%.
												</p>

												<p style="margin: 1em 0;">
													<label for="geodir-booking-policy-name-<?php echo esc_attr( $policy_id ); ?>"><?php esc_html_e( 'Policy Name', 'geodir-booking' ); ?></label><br />
													<input type="text" class="regular-text" id="geodir-booking-policy-name-<?php echo esc_attr( $policy_id ); ?>" name="geodir_booking[<?php echo esc_attr( $setting_id ); ?>][<?php echo esc_attr( $policy_id ); ?>][policy_name]" value="<?php echo esc_attr( $policy['policy_name'] ); ?>">
												</p>

												<p style="margin: 1em 0;">
													<label for="geodir-booking-policy-desc-<?php echo esc_attr( $policy_id ); ?>"><?php esc_html_e( 'Policy Description', 'geodir-booking' ); ?></label><br />
													<textarea class="regular-text" id="geodir-booking-policy-desc-<?php echo esc_attr( $policy_id ); ?>" name="geodir_booking[<?php echo esc_attr( $setting_id ); ?>][<?php echo esc_attr( $policy_id ); ?>][policy_desc]"><?php echo esc_textarea( $policy['policy_desc'] ); ?></textarea>
												</p>

												<p style="margin: 1em 0;">
													<label for="geodir-booking-policy-default-<?php echo esc_attr( $policy_id ); ?>">
														<input type="radio" id="geodir-booking-policy-default-<?php echo esc_attr( $policy_id ); ?>" name="geodir_booking[<?php echo esc_attr( $setting_id ); ?>_default]" value="<?php echo esc_attr( $policy_id ); ?>" <?php checked( $policy_id, $default_policy ); ?>>
														<?php esc_html_e( 'Set as default', 'geodir-booking' ); ?>
													</label>
												</p>

												<p style="margin: 1em 0;">
													<a href="#" class="geodir-booking-policy-remove button button-link button-link-delete"><?php esc_html_e( 'Remove', 'geodir-booking' ); ?></a>
												</p>
											</div>
										<?php endforeach; ?>
										<p>
											<a href="#" class="geodir-booking-policy-add button button-secondary"><?php esc_html_e( 'Add Policy', 'geodir-booking' ); ?></a>
										</p>
								</div>
							<?php endif; ?>

							<?php
                                if ( 'timepicker' == $args['type'] ) :
                                $values        = geodir_booking_get_option( $setting_id, array() );
                                $selected_hour  = isset( $values['hours'] ) && ! empty( $values['hours'] ) ? (int) $values['hours'] : '00';
                                $selected_minute  = isset( $values['minutes'] ) && ! empty( $values['minutes'] ) ? (int) $values['minutes'] : '00';
                                ?>

                                <select name="geodir_booking[<?php echo esc_attr( $setting_id ); ?>][hours]" style="max-width: 100px !important;">
								<?php
								for ( $hour = 0; $hour <= 23; $hour++ ) :
									$hour_string = str_pad( $hour, 2, '0', \STR_PAD_LEFT );
                                    ?>
                                        <option value="<?php echo esc_attr( $hour_string ); ?>" <?php echo selected( $selected_hour, $hour, false ); ?> > <?php echo esc_attr( $hour_string ); ?> </option>
                                    <?php endfor; ?>	
                                </select>

                                <select name="geodir_booking[<?php echo esc_attr( $setting_id ); ?>][minutes]" style="max-width: 100px !important;">
                                    <?php
                                    for ( $minute = 0; $minute <= 59; $minute++ ) :
                                        $minute_string = str_pad( $minute, 2, '0', \STR_PAD_LEFT );
                                    ?>
                                        <option value="<?php echo esc_attr( $minute_string ); ?>" <?php echo selected( $selected_minute, $minute, false ); ?> > <?php echo esc_attr( $minute_string ); ?> </option>
                                    <?php endfor; ?>	
                                </select>
                            <?php endif; ?>

							<?php do_action( 'geodir_booking_settings_display_' . $args['type'], $args, $setting_id ); ?>

							<?php
								if ( ! empty( $args['desc'] ) ) {
								printf(
                                    '<p class="description">%s</p>',
                                    wp_kses_post( $args['desc'] )
                                );
								}
							?>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>

		<?php submit_button(); ?>

	</form>
	<?php do_action( 'geodir_booking_settings_page_bottom' ); ?>
</div>

<script>
	jQuery( 'select[name="geodir_booking[payment_type]"]' )
		.on( 'change', function() {
			var booking_type = jQuery( this ).val();

			if ( 'full' === booking_type ) {
				jQuery( '.form-field-row-deposit, .form-field-row-commision, .form-field-row-payment_form, .form-field-row-hold_booking_minutes, .form-field-row-service_fee, .form-field-row-tax_behaviour' ).show();
				jQuery( '.form-field-row-deposit' ).hide();
			} else if ( 'deposit' === booking_type ) {
				jQuery( '.form-field-row-deposit, .form-field-row-commision, .form-field-row-payment_form, .form-field-row-hold_booking_minutes, .form-field-row-service_fee, .form-field-row-tax_behaviour' ).show();
			} else {
				jQuery( '.form-field-row-deposit, .form-field-row-commision, .form-field-row-payment_form, .form-field-row-hold_booking_minutes, .form-field-row-service_fee, .form-field-row-tax_behaviour' ).hide();
			}

		})
		.trigger( 'change' );

	// Add cancellation policy.
	jQuery( '.geodir-booking-policy-add' ).on( 'click', function( e ) {
		e.preventDefault();

		var defaultPolicy = jQuery( '.geodir-booking-policy__geodir_booking_default_template' );
		var newPolicy = defaultPolicy.prop('outerHTML');

		// Generate a random ID that is 12 digits long, all strings.
		var id = 'new_policy_' + Math.random().toString( 36 ).substr( 2, 12 );

		// Replace geodir_booking_default_template with the random ID.
		newPolicy = newPolicy.replace( /geodir_booking_default_template/g, id );

		// Insert before defaultPolicy
		defaultPolicy.before( newPolicy );
	});

	// Remove cancellation policy.
	jQuery( '.geodir-cancellation-policies' ).on( 'click', '.geodir-booking-policy-remove', function( e ) {
		e.preventDefault();

		var policy = jQuery( this ).closest( '.geodir-booking-policy' );

		policy.remove();
	});
</script>
<style>
	.geodir-booking-policy__geodir_booking_default_template {
		display: none !important;
	}
</style>
