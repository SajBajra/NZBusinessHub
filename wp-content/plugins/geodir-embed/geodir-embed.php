<?php
/**
 * GeoDirectory Embed
 *
 * @package           GeoDir_Embed
 * @author            AyeCode Ltd
 * @copyright         2019 AyeCode Ltd
 * @license           GPLv3
 *
 * @wordpress-plugin
 * Plugin Name:       GeoDirectory Embed
 * Plugin URI:        https://wpgeodirectory.com/downloads/embeddable-ratings-badge/
 * Description:       Allows users to build an embedded ratings widget for their listing to show on their own website.
 * Version:           2.3.4
 * Requires at least: 5.0
 * Requires PHP:      5.6
 * Author:            AyeCode Ltd
 * Author URI:        https://ayecode.io
 * License:           GPLv3
 * License URI:       http://www.gnu.org/licenses/gpl-3.0.html
 * Requires Plugins:  geodirectory
 * Text Domain:       geodir-embeds
 * Domain Path:       /languages
 * Update URL:        https://wpgeodirectory.com
 * Update ID:         696082
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

if ( ! defined( 'GEODIR_EMBED_VERSION' ) ) {
	define( 'GEODIR_EMBED_VERSION', '2.3.4' );
}

if ( ! defined( 'GEODIR_EMBED_MIN_CORE' ) ) {
	define( 'GEODIR_EMBED_MIN_CORE', '2.3' );
}

/**
 * The function to launch the plugin class.
 *
 * @since 2.0.0
 */
function geodir_load_embed_manager() {
	if ( ! defined( 'GEODIR_EMBED_PLUGIN_FILE' ) ) {
		define( 'GEODIR_EMBED_PLUGIN_FILE', __FILE__ );
	}

	// min core version check
	if( !function_exists("geodir_min_version_check") || !geodir_min_version_check("Embed Code",GEODIR_EMBED_MIN_CORE)){
		return '';
	}

	include_once( dirname( __FILE__ ) . "/includes/class-geodir-embed.php" );
	GeoDir_Embed::init();
}

add_action( 'geodirectory_loaded', 'geodir_load_embed_manager' );