<?php
/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.2.1
 */
class GD_Duplicate_Alert_Activate {

	/**
	 * Plugin activate.
	 *
	 * When plugin active then set global options in GD duplicate alert.
	 *
	 * @since  1.2.1
	 */
	public static function activate() {
		$post_types = geodir_get_posttypes();

		if ( ! empty( $post_types ) ) {
			$options = geodir_get_option( 'duplicate_alert', array() );

			foreach ( $post_types as $post_type ) {
				if ( ! empty( $options ) && ! empty( $options[ $post_type ] ) && is_array( $options[ $post_type ] ) ) {
					continue;
				}

				$post_type_name = geodir_post_type_singular_name( $post_type );

				$options[ $post_type ] = array(
					'duplicate_alert_fields' => array( 'post_title' ),
					'duplicate_alert_validation_message' => wp_sprintf(__( '%s with this field is already listed! Please make sure you are not adding a duplicate entry.','geodir-duplicate-alert' ), $post_type_name )
				);
			}

			geodir_update_option( 'duplicate_alert', $options );
		}

		// Bail if activating from network, or bulk
		if ( is_network_admin() || isset( $_GET['activate-multi'] ) || apply_filters( 'geodir_duplicate_alert_skip_activation_redirect', false ) ) {
			return;
		}

		set_transient( 'gd_duplicate_alert_redirect', true, 30 );
	}
}
