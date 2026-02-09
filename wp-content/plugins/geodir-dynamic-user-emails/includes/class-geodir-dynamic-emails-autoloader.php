<?php
/**
 * Dynamic User Emails autoloader class
 *
 * @author    AyeCode Ltd
 * @package   GeoDir_Dynamic_Emails
 * @version   2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * GeoDir_Dynamic_Emails_Autoloader class.
 */
class GeoDir_Dynamic_Emails_Autoloader {
	/**
	 * Path to the includes directory.
	 *
	 * @var string
	 */
	private $include_path = '';

	/**
	 * The Constructor.
	 */
	public function __construct() {
		if ( function_exists( "__autoload" ) ) {
			spl_autoload_register( "__autoload" );
		}

		spl_autoload_register( array( $this, 'autoload' ) );

		$this->include_path = untrailingslashit( plugin_dir_path( GEODIR_DYNAMIC_EMAILS_PLUGIN_FILE ) ) . '/includes/';
	}

	/**
	 * Take a class name and turn it into a file name.
	 *
	 * @param  string $class
	 * @return string
	 */
	private function get_file_name_from_class( $class ) {
		return 'class-' . str_replace( '_', '-', $class ) . '.php';
	}

	/**
	 * Include a class file.
	 *
	 * @param  string $path
	 * @return bool successful or not
	 */
	private function load_file( $path ) {
		if ( $path && is_readable( $path ) ) {
			include_once( $path );
			return true;
		}
		return false;
	}

	/**
	 * Auto-load GeoDir classes on demand to reduce memory consumption.
	 *
	 * @param string $class
	 */
	public function autoload( $class ) {
		$class = strtolower( $class );

		if ( 0 !== strpos( $class, 'geodir_dynamic_emails_' ) ) {
			return;
		}

		$file  = $this->get_file_name_from_class( $class );
		$path  = '';

		if ( strpos( $class, 'geodir_dynamic_emails_admin' ) === 0 || ( strpos( $class, '_list_table' ) > 0 ) && is_readable( $this->include_path . 'admin/' . $file ) ) {
			$path = $this->include_path . 'admin/';
		} else if ( strpos( $class, 'geodir_dynamic_emails_settings' ) === 0 ) {
			$path = $this->include_path . 'admin/settings/';
		} else if ( strpos( $class, 'geodir_dynamic_emails_widget' ) === 0 ) {
			$path = $this->include_path . 'widgets/';
		}

		if ( empty( $path ) || ! $this->load_file( $path . $file ) ) {
			$this->load_file( $this->include_path . $file );
		}
	}
}

new GeoDir_Dynamic_Emails_Autoloader();
