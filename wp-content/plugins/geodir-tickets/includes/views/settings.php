<div class="wrap geodir-tickets-settings">

	<?php

		// Display the title.
		$title = esc_html( get_admin_page_title() );
		echo "<h1>$title</h1>";

		// Fire a hook before printing the settings page.
		do_action( 'geodir_tickets_settings_page_top' );

		if ( false === $saved_settings ) {
			printf(
				'<div class="error is-dismissible"><p>%s</p></div>',
				__( 'Could not save your settings. Please try again.', 'geodir-tickets' )
			);
		}

		if ( true === $saved_settings ) {
			printf(
				'<div class="notice notice-success is-dismissible"><p>%s</p></div>',
				__( 'Your settings have been saved.', 'geodir-tickets' )
			);
		}

		add_thickbox()
	?>

	<form method="POST" class="geodir-tickets-main-settings-form" style="max-width: 1200px;" onsubmit="window.tinyMCE ? window.tinyMCE.triggerSave() : ''">
		<?php wp_nonce_field( 'geodir-tickets', 'geodir-tickets' ); ?>

		<table class="form-table">
			<tbody>

				<?php foreach ( $settings as $setting_id => $args ) : ?>
					<tr class="form-field-row form-field-row-<?php echo sanitize_html_class( $setting_id ); ?>">
						<th scope="row">
							<label for="geodir-tickets-field-<?php echo sanitize_html_class( $setting_id ); ?>"><?php echo esc_html( $args['label'] ); ?></label>
						</th>
						<td>

							<?php if ( 'text' == $args['type'] ) : ?>
								<input
									type="text"
									class="regular-text"
									name="geodir_tickets[<?php echo esc_attr( $setting_id );?>]"
									value="<?php echo esc_attr( geodir_tickets_get_option( $setting_id, $args['default'] ) );?>"
									placeholder="<?php echo isset( $args['placeholder'] ) ? esc_attr( $args['placeholder'] ) : '' ?>"
								>
							<?php endif; ?>

							<?php if ( 'number' == $args['type'] ) : ?>
								<input
									type="number"
									class="regular-text"
									step="0.01"
									min="<?php echo isset( $args['min'] ) ? (float) $args['min'] : '-' . PHP_INT_MAX ?>"
									max="<?php echo isset( $args['max'] ) ? (float) $args['max'] : PHP_INT_MAX ?>"
									name="geodir_tickets[<?php echo esc_attr( $setting_id );?>]"
									value="<?php echo floatval( geodir_tickets_get_option( $setting_id, $args['default'] ) );?>"
									placeholder="<?php echo isset( $args['placeholder'] ) ? esc_attr( $args['placeholder'] ) : '' ?>"
								>
							<?php endif; ?>

							<?php if ( 'checkbox' == $args['type'] ) : ?>
								<input
									type="checkbox"
									name="geodir_tickets[<?php echo esc_attr( $setting_id );?>]"
									<?php checked( null !== geodir_tickets_get_option( $setting_id, null ) ); ?>
									value="1"
								>&nbsp;<span><?php echo wp_kses_post( $args['label2'] ); ?></span>
							<?php endif; ?>

							<?php
								if ( 'select' == $args['type'] ) :
									$value = geodir_tickets_get_option( $setting_id, $args['default'] );
							?>
								<select name= "geodir_tickets[<?php echo esc_attr( $setting_id );?>]" class="regular-text">
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
									name="geodir_tickets[<?php echo esc_attr( $setting_id );?>]"
								><?php echo esc_textarea( wp_unslash( geodir_tickets_get_option( $setting_id, $args['default'] ) ) ); ?></textarea>
							<?php endif; ?>

							<?php do_action( 'geodir_tickets_settings_display_' . $args['type'], $args, $setting_id ); ?>

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
	<?php do_action( 'geodir_tickets_settings_page_bottom' ); ?>
</div>
<div id="geodir-tickets-merge-tags" style="display: none;"><script src="https://gist.github.com/picocodes/ba65d0476c4127a8447e3c485b62ebda.js"></script></div>