<?php
/**
 * Contains settings related functions
 *
 * @package Advertising
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Retrieves all default settings.
 * 
 * @return array
 */
function adv_get_default_settings() {
    $defaults = array();

    foreach ( array_values( adv_get_registered_settings() ) as $key => $setting ) {

        if ( isset( $setting['std'] ) ) {
			$defaults[ $key ] = $setting['std'];
		}

    }

    return $defaults;

}

/**
 * Retrieves all saved settings.
 * 
 * @return array
 */
function adv_get_options() {
    global $adv_options;

	if ( is_array( $adv_options ) ) {
		return $adv_options;
	}

    // Try fetching the saved options.
    $adv_options = get_option( 'adv_settings' );

    // Ensure that we have an array.
    if ( ! is_array( $adv_options ) ) {
        $adv_options = array();
    }

	// Merge with defaults.
	$adv_options = array_merge( adv_get_default_settings(), $adv_options );

    return $adv_options;
}

/**
 * Retrieves a single setting.
 * 
 * @param string $key the setting key.
 * @param mixed $default The default value to use if the setting has not been set.
 * @return mixed
 */
function adv_get_option( $key = '', $default = false ) {

    $options = adv_get_options();
    $value   = isset( $options[ $key ] ) ? $options[ $key ] : $default;
    $value   = apply_filters( 'adv_get_option', $value, $key, $default );

    return apply_filters( 'adv_get_option_' . $key, $value, $key, $default );
}

/**
 * Updates all settings.
 * 
 * @param array $options the new options.
 * @return bool
 */
function adv_update_options( $options ) {
    global $adv_options;

    // update the option.
    if ( is_array( $options ) && update_option( 'adv_settings', $options ) ) {
        $adv_options = $options;
        return true;
    }

    return false;
}

/**
 * Updates a single setting.
 * 
 * @param string $key the setting key.
 * @param mixed $value The setting value.
 * @return bool
 */
function adv_update_option( $key = '', $value = false ) {

    // If no key, exit.
    if ( empty( $key ) ) {
        return false;
    }

    // Maybe delete the option instead.
    if ( is_null( $value ) ) {
        return adv_delete_option( $key );
    }

	if ( 'tracking_slug' === $key ) {
		flush_rewrite_rules();
	}

    // Prepare the new options.
    $options         = adv_get_options();
    $options[ $key ] = apply_filters( 'adv_update_option', $value, $key );

    // Save the new options.
    return adv_update_options( $options );

}

/**
 * Deletes a single setting.
 * 
 * @param string $key the setting key.
 * @return bool
 */
function adv_delete_option( $key = '' ) {

    // If no key, exit
    if ( empty( $key ) ) {
        return false;
    }

    $options = adv_get_options();

    if ( isset( $options[ $key ] ) ) {
        unset( $options[ $key ] );
        return adv_update_options( $options );
    }

    return true;

}

/**
 * Register settings after admin inits.
 * 
 */
function adv_register_settings() {

	// Register the setting section.
	add_settings_section(
		'adv_settings_main',
		null,
		'__return_false',
		'adv_settings_main'
	);

    // Loop through all settings.
    foreach ( adv_get_registered_settings() as $id => $args ) {
		adv_register_settings_option( $id, $args );
	}

    // Creates our settings in the options table.
    register_setting( 'adv_settings', 'adv_settings', 'adv_settings_sanitize' );

	if ( ! get_option( 'adv_flushed_rewrite_rules' ) ) {
		update_option( 'adv_flushed_rewrite_rules', 1 );
		flush_rewrite_rules();
	}

}
add_action( 'admin_init', 'adv_register_settings' );

/**
 * Register a single settings option.
 * 
 * @param string $id
 * @param args $args
 * 
 */
function adv_register_settings_option( $id, $args ) {

    $label   = isset( $args['label'] ) ? $args['label'] : '';
	$type    = isset( $args['type'] ) ? $args['type'] : 'text';
    $cb      = isset( $args['callback'] ) ? $args['callback'] : "adv_{$type}_callback";

    // Register the setting.
    add_settings_field(
        "adv_settings[$id]",
        $label,
        is_callable( $cb ) ? $cb : 'adv_missing_callback',
        'adv_settings_main',
        'adv_settings_main',
        array(
            'section'     => 'adv_settings_main',
            'id'          => esc_attr( $id ),
            'desc'        => isset( $args['description'] )        ? $args['description']        : '',
            'name'        => $label,
            'size'        => isset( $args['size'] )        ? $args['size']        : null,
            'options'     => isset( $args['options'] )     ? $args['options']     : array(),
			'data'        => isset( $args['data'] )        ? $args['data']        : '',
            'std'         => isset( $args['std'] )         ? $args['std']         : '',
            'min'         => isset( $args['min'] )         ? $args['min']         : 0,
            'max'         => isset( $args['max'] )         ? $args['max']         : 999999,
            'step'        => isset( $args['step'] )        ? $args['step']        : 1,
            'placeholder' => isset( $args['placeholder'] ) ? $args['placeholder'] : null,
            'allow_blank' => isset( $args['allow_blank'] ) ? $args['allow_blank'] : true,
            'readonly'    => isset( $args['readonly'] )    ? $args['readonly']    : false,
            'faux'        => isset( $args['faux'] )        ? $args['faux']        : false,
            'onchange'    => isset( $args['onchange'] )    ? $args['onchange']     : '',
            'custom'      => isset( $args['custom'] )      ? $args['custom']       : '',
			'class'       => isset( $args['class'] )       ? $args['class']         : '',
			'style'       => isset( $args['style'] )       ? $args['style']         : '',
            'cols'        => isset( $args['cols'] ) && (int) $args['cols'] > 0 ? (int) $args['cols'] : 50,
            'rows'        => isset( $args['rows'] ) && (int) $args['rows'] > 0 ? (int) $args['rows'] : 5,
        )
    );

}

/**
 * Returns an array of all registered settings.
 *
 * @return array
 */
function adv_get_registered_settings() {

	return apply_filters(
		'adv_get_registered_settings',
		array(

            'cart'                => array(
                'type'            => 'select',
                'data'            => 'cart_options',
                'label'           => __( 'Cart', 'advertising' ),
	            'std'             => 'invoicing',
                'description'     => wp_sprintf( __( 'You must have %sGetPaid%s installed and active.', 'advertising' ), '<a href="https://wordpress.org/plugins/invoicing/" target="_blank">', '</a>'),
            ),

            'dashboard_page_id'   => array(
                'type'            => 'select',
                'data'            => 'pages',
                'label'           => __( 'Dashboard Page', 'advertising' ),
                'description'     => __( 'This is the advertisers dashboard page. The <b>[ads_dashboard]</b> shortcode must be on this page.', 'advertising' ) ,
            ),

            'tracking_slug'       => array(
                'type'            => 'text',
                'label'           => __( 'Tracking link base slug', 'advertising' ),
                'placeholder'     => __( 'click', 'advertising' ),
				'std'             => 'click',
                'description'     => wp_sprintf( __( 'The slug variable for tracking click links. For example: <b>%s</b>, so tracking link will be %s.', 'advertising' ), adv_tracking_slug(), adv_tracking_url( '[ID]' ) ),
            ),

            'uninstall_advertising'       => array(
                'type'            => 'checkbox',
                'label'           => __( 'Delete data on uninstall', 'advertising' ),
				'std'             => 0,
                'description'     => __( 'Delete all data related to this plugin when deleting the plugin.', 'advertising' ) ,
            ),
			'paid_ads_status'     => array(
                'type'            => 'select',
                'data'            => 'paid_ads_status_options',
                'label'           => __( 'Paid ads status', 'advertising' ),
				'std'             => 'publish',
                'description'     => __( 'Select the status to assign an ad after it has been paid for', 'advertising' ),
            ),
			'html_ads'     => array(
                'type'            => 'select',
                'options'         => array(
					'disable' => __( 'Do not allow HTML ads', 'advertising' ),
					'admin'   => __( 'Only allow HTML ads in the admin area', 'advertising' ),
					'allow'   => __( 'Allow HTML ads', 'advertising' ),
				),
                'label'           => __( 'HTML Ads', 'advertising' ),
				'std'             => 'admin',
                'description'     => __( 'Select whether or not to allow HTML ads', 'advertising' ),
            ),
        )
	);

}

/**
 * Sanitizes settings before they are saved.
 * 
 * @return array
 */
function adv_settings_sanitize( $input = array() ) {

	$adv_options = adv_get_options();
    $settings    = adv_get_registered_settings();
    $input       = is_array( $input ) ? $input : array();
    $input       = apply_filters( 'adv_settings_sanitize', $input );

    // Loop through each setting being saved and pass it through a sanitization filter
    foreach ( $input as $key => $value ) {

        // Get the setting type (checkbox, select, etc)
        $type = isset( $settings[ $key ]['type'] ) ? $settings[ $key ]['type'] : false;

		// Field type specific filter
        if ( $type ) {
            $input[$key] = apply_filters( 'adv_settings_sanitize_' . $type, $value, $key );
        }

        // General filter
		$input[ $key ] = apply_filters( 'adv_setting_sanitize', $input[ $key ], $key );

		// Key specific filter.
		$input[ $key ] = apply_filters( "adv_settings_sanitize_$key", $input[ $key ] );
    }

    foreach ( $settings as $key => $value ) {

		if ( ! isset( $input[ $key ] ) && isset( $adv_options[ $key ] ) ) {
			unset( $adv_options[ $key ] );
		}

	}

    // Merge our new settings with the existing
    $output = array_merge( $adv_options, $input );

    add_settings_error( 'adv-notices', '', __( 'Settings updated.', 'advertising' ), 'updated' );
	delete_option( 'adv_flushed_rewrite_rules' );

    return $output;
}

/**
 * Sanitizes a text field.
 */
function adv_sanitize_text_field( $input ) {
    return trim( $input );
}
add_filter( 'adv_settings_sanitize_text', 'adv_sanitize_text_field' );

/**
 * Sanitizes a checkbox.
 */
function adv_sanitize_checkbox_field( $input ) {
    return ! empty( $input );
}
add_filter( 'adv_settings_sanitize_checkbox', 'adv_sanitize_checkbox_field' );

/**
 * Displays a header.
 */
function adv_header_callback( $args ) {
	if ( ! empty( $args['desc'] ) ) {
        echo $args['desc'];
    }
}

/**
 * Displays a hidden input.
 */
function adv_hidden_callback( $args ) {

	$name  = esc_attr( $args['id'] );
	$value = esc_attr( $args['std'] );

	?>
		<input type="hidden" id="adv_settings-<?php echo $name; ?>" name="adv_settings[<?php echo $name; ?>]"value="<?php echo $value; ?>" />
	<?php

}

/**
 * Displays a checkbox settings callback.
 */
function adv_checkbox_callback( $args ) {

	$std = isset( $args['std'] ) ? $args['std'] : '';
	$std = adv_get_option( $args['id'], ! empty( $args['std'] ) );
	$id  = esc_attr( $args['id'] );

	adv_hidden_field( "adv_settings[$id]", '0' );
	?>
		<input type="hidden" name="adv_settings[<?php echo $id; ?>]" value="0" />
		<fieldset>
			<label>
				<input id="adv-settings-<?php echo $id; ?>" name="adv_settings[<?php echo $id; ?>]" <?php checked( empty( $std ), false ); ?> value="1" type="checkbox">
				<?php echo wp_kses_post( $args['desc'] ); ?>
			</label>
		</fieldset>
	<?php
}

/**
 * Displays a radio settings field.
 */
function adv_radio_callback( $args ) {

	$std = isset( $args['std'] ) ? $args['std'] : '';
	$std = adv_get_option( $args['id'], $std );

	if ( isset( $args['data'] ) && is_callable( 'adv_get_' . $args['data'] ) ) {
		$args['options'] = call_user_func( 'adv_get_' . $args['data'] );
	}

	?>
		<fieldset>
			<ul id="adv-settings-<?php echo esc_attr( $args['id'] ); ?>" style="margin-top: 0;">
				<?php foreach( $args['options'] as $key => $option ) : ?>
					<li>
						<label>
							<input name="adv_settings[<?php echo esc_attr( $args['id'] ); ?>]" <?php checked( $std, $key ); ?> value="<?php echo esc_attr( $key ); ?>" type="radio">
							<?php echo wp_kses_post( $option ); ?>
						</label>
					</li>
				<?php endforeach; ?>
			</ul>
		</fieldset>
	<?php
	adv_settings_description_callback( $args );
}

/**
 * Displays a description if available.
 */
function adv_settings_description_callback( $args ) {

	if ( ! empty( $args['desc'] ) ) {
		$description = wp_kses_post( $args['desc'] );
		echo "<p class='description'>$description</p>";
	}

}

/**
 * Generates attributes.
 * 
 * @param array $args
 * @return string
 */
function adv_settings_attrs_helper( $args ) {

	$value        = isset( $args['std'] ) ? $args['std'] : '';
	$id           = esc_attr( $args['id'] );
	$placeholder  = esc_attr( $args['placeholder'] );

	if ( ! empty( $args['faux'] ) ) {
		$args['readonly'] = true;
		$name             = '';
	} else {
		$value  = adv_get_option( $args['id'], $value );
		$name   = "adv_settings[$id]";
	}

	$value    = is_scalar( $value ) ? esc_attr( $value ) : '';
	$class    = esc_attr( $args['class'] );
	$style    = esc_attr( $args['style'] );
	$readonly = empty( $args['readonly'] ) ? '' : 'readonly onclick="this.select()"';

	$onchange = '';
    if ( ! empty( $args['onchange'] ) ) {
        $onchange = ' onchange="' . esc_attr( $args['onchange'] ) . '"';
	}

	return "name='$name' id='adv-settings-$id' style='$style' value='$value' class='$class' placeholder='$placeholder' data-placeholder='$placeholder' $onchange $readonly";
}

/**
 * Displays a text input settings callback.
 */
function adv_text_callback( $args ) {

	$desc           = wp_kses_post( $args['desc'] );
	$desc           = empty( $desc ) ? '' : "<p class='description'>$desc</p>";
	$args['style'] .= 'width: 25em;';
	$attr           = adv_settings_attrs_helper( $args );

	?>
		<label style="width: 100%;">
			<input type="text" <?php echo $attr; ?>>
			<?php echo $desc; ?>
		</label>
	<?php

}

/**
 * Displays a number input settings callback.
 */
function adv_number_callback( $args ) {

	$args['style'] .= 'width: 25em;';
	$desc           = wp_kses_post( $args['desc'] );
	$desc           = empty( $desc ) ? '' : "<p class='description'>$desc</p>";
	$attr           = adv_settings_attrs_helper( $args );
	$max            = intval( $args['max'] );
	$min            = intval( $args['min'] );
	$step           = floatval( $args['step'] );

	?>
		<label style="width: 100%;">
			<input type="number" step="<?php echo $step; ?>" max="<?php echo $max; ?>" min="<?php echo $min; ?>" <?php echo $attr; ?>>
			<?php echo $desc; ?>
		</label>
	<?php

}

/**
 * Displays a textarea settings field.
 *
 * @param array $args
 */
function adv_textarea_callback( $args ) {

	$args['style'] .= 'width: 25em;';
	$desc           = wp_kses_post( $args['desc'] );
	$desc           = empty( $desc ) ? '' : "<p class='description'>$desc</p>";
	$attr           = adv_settings_attrs_helper( $args );
	$value          = isset( $args['std'] ) ? $args['std'] : '';
	$value          = adv_get_option( $args['id'], $value );

	?>
		<label style="width: 100%;">
			<textarea <?php echo $attr; ?>>
				<?php echo esc_textarea( stripslashes( $value ) ); ?>
			</textarea>
			<?php echo $desc; ?>
		</label>
	<?php

}

/**
 * Displays a missing settings field.
 *
 * @param array $args
 */
function adv_missing_callback( $args ) {
	printf(
		__( 'The callback function used for the %s setting is missing.', 'advertising' ),
		'<strong>' . $args['id'] . '</strong>'
	);
}

/**
 * Displays a select box settings field.
 * 
 * @param array $args
 */
function adv_select_callback( $args ) {

	$args['style'] .= 'width: 25em;';
	$desc           = wp_kses_post( $args['desc'] );
	$desc           = empty( $desc ) ? '' : "<p class='description'>$desc</p>";
	$attr           = adv_settings_attrs_helper( $args );
	$value          = isset( $args['std'] ) ? $args['std'] : '';
	$value          = adv_get_option( $args['id'], $value );

	if ( isset( $args['data'] ) && is_callable( 'adv_get_' . $args['data'] ) ) {
		$args['options'] = call_user_func( 'adv_get_' . $args['data'] );
	}

	?>
		<label style="width: 100%;">
			<select <?php echo $attr; ?>>
				<?php foreach ( $args['options'] as $option => $name ) : ?>
					<option value="<?php echo esc_attr( $option ); ?>" <?php echo selected( $option, $value ); ?>><?php echo sanitize_text_field( $name ); ?></option>
				<?php endforeach;?>
			</select>
			<?php echo $desc; ?>
		</label>
	<?php

}

/**
 * Displays a descriptive text settings field.
 * 
 * @param array $args
 */
function adv_descriptive_text_callback( $args ) {
	echo wp_kses_post( $args['desc'] );
}

/**
 * Displays a raw html settings field.
 * 
 * @param array $args
 */
function adv_raw_html_callback( $args ) {
	echo $args['desc'];
}

/**
 * Displays a "hook-rendered" settings field.
 * 
 * @param array $args
 */
function adv_hook_callback( $args ) {
	do_action( 'adv_' . $args['id'], $args );
}
