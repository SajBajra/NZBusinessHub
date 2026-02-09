<?php
/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since   2.0.0
 */
class GD_Social_Importer_Activate {

	/**
	 * Plugin activate.
	 *
	 * When plugin active then set global options in GD Social Importer.
	 *
	 * @since  2.0.0
	 */
	public static function activate(){

		set_transient( 'gd_social_importer_redirect', true, 30 );

	}

}