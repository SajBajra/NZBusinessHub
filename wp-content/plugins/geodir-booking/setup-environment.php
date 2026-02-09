<?php
/**
 * Checks the state of all required plugins.
 *
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    exit;
}

/**
 * Registers the free required plugins.
 */
function geodir_booking_register_required_plugins() {

	/*
	 * Array of plugin arrays. Required keys are name and slug.
	 */
	$plugins = array(

		array(
			'name'             => 'GetPaid',
			'slug'             => 'invoicing',
            'required'         => true,
            'version'          => '2.4.7',
		),

		array(
			'name'             => 'GeoDirectory',
			'slug'             => 'geodirectory',
            'required'         => true,
            'version'          => '2.1.0.20',
		),

		array(
			'name'             => __( 'GetPaid > Wallet', 'geodir-booking' ),
			'slug'             => 'getpaid-wallet',
            'required'         => true,
            'version'          => '2.0.2',
		),

	);

	/*
	 * Array of configuration settings. Amend each line as needed.
	 *
	 */
	$config = array(
		'id'           => 'gd-booking',
		'default_path' => '',
		'menu'         => 'gd-booking',
		'parent_slug'  => 'plugins.php',
		'capability'   => 'manage_options',
		'has_notices'  => true,
		'dismissable'  => false,
		'dismiss_msg'  => '',
		'is_automatic' => false,
		'message'      => '',
		'strings'      => array(

			'notice_can_install_required' => _n_noop(
				/* translators: 1: plugin name(s). */
				'This plugin requires the following plugin: %1$s.',
				'This plugin requires the following plugins: %1$s.',
				'geodir-booking'
			),

		)
	);

	tgmpa( $plugins, $config );
}
add_action( 'tgmpa_register', 'geodir_booking_register_required_plugins' );
require_once plugin_dir_path( GEODIR_BOOKING_FILE ) . 'includes/class-tgm-plugin-activation.php';

/**
 * Maybe auto-install free required plugins.
 */
function geodir_booking_maybe_install_plugins() {

	$installed = get_option( 'geodir_booking_installed_plugins', 0 );

	if ( ! empty( $installed ) ) {
		return;
	}

	include_once ABSPATH . 'wp-admin/includes/file.php';
	include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
	include_once ABSPATH . 'wp-admin/includes/plugin.php';

	$required_plugins = array(
		'geodirectory/geodirectory.php'                        => array( 'GeoDirectory', 'geodirectory' ),
		'invoicing/invoicing.php'                              => array( 'WPInv_Plugin', 'invoicing' ),
		'getpaid-wallet/wpinv-wallet.php'                      => array( 'WPInv_Wallet', 'getpaid-wallet' ),
	);

	$flush_cache = false;

	// For each required plugin...
	foreach ( $required_plugins as $file => $data ) {

		// Abort if it is active.
		if ( class_exists( $data[0] ) ) {
			continue;
		}

		// If it is not installed, install it.
		if ( ! file_exists( WP_PLUGIN_DIR . '/' . $file ) ) {

			$plugin_zip = esc_url( 'https://downloads.wordpress.org/plugin/' . $data[1] . '.latest-stable.zip' );
			$upgrader   = new Plugin_Upgrader( new Automatic_Upgrader_Skin() );
			$installed  = $upgrader->install( $plugin_zip );

			if ( is_wp_error( $installed ) && $installed ) {
				error_log( $upgrader->skin->get_upgrade_messages() );
				continue;
			}

		}

		// Activate the plugin.
		activate_plugin( $file, '', false, true );

	}

	if ( $flush_cache ) {
		wp_cache_flush();
	}

	update_option( 'geodir_booking_installed_plugins', 1 );
}
add_action( 'admin_init', 'geodir_booking_maybe_install_plugins' );
