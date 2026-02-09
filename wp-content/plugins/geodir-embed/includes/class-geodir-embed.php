<?php
/**
 * GeoDirectory Embed
 *
 * Adds the ability to embed widgets on external websites.
 *
 * @author   AyeCode
 * @category Embed
 * @package  GeoDirectory
 * @since    2.0.0
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * GeoDir_Embed class.
 */
class GeoDir_Embed {


	/**
	 * Init.
	 */
	public static function init() {

		// call embed actions
		include_once( dirname( __FILE__ ) . "/class-geodir-embed-action.php" );

		if ( is_admin() ) {
			include_once( dirname( __FILE__ ) . "/admin/class-geodir-embed-admin.php" );
			include_once( dirname( __FILE__ ) . "/class-geodir-embed-builder.php" );
		}

		add_action( 'geodir_get_widgets', array( __CLASS__, 'register_widgets' ), 11, 1 );
		add_action( 'plugins_loaded', array( __CLASS__, 'load_textdomain' ) );

		if ( ( ! empty( $_REQUEST['action'] ) && $_REQUEST['action'] == 'geodir_embed_builder' && ! empty( $_REQUEST['post_id'] ) ) || ( ! empty( $_REQUEST['gd-embed'] ) && $_REQUEST['gd-embed'] == 'rating' && ! empty( $_REQUEST['id'] ) ) ) {
			add_filter( 'wp_headers', array( __CLASS__, 'filter_iframe_security_headers' ), 10, 2 );
		}

		//Enqueue js for the modal.
		$design_style = geodir_design_style();

		if( $design_style ) {
			add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue' ) );
		}
	}

	/**
	 * Inline JavaScript for modal.
	 *
	 */
	public static function enqueue(){
		if(geodir_is_page('single')){
			wp_add_inline_script( 'geodir', self::embed_modal() );
		}
	}

	public static function embed_modal(){
		ob_start();

		if ( 0 ) { ?><script><?php } ?>
jQuery(function($) {
	var iFrame = $('#embedModal iframe');
	if (iFrame.length) {
		$('#embedModal').on('show.bs.modal', function(e) {
			$('.embed-loading').show();
			var button = $(e.relatedTarget);
			var url = button.data("builder_url");
			iFrame.attr({
				src: url,
			});
		});
		$("#embedModal").on("hidden.bs.modal", function() {
			iFrame.removeAttr("src allow");
		});

		/* Resize the iframe once loaded. */
		iFrame.on("load", function() {
			// 50 is the padding.
			this.style.height = this.contentWindow.document.body.offsetHeight + 50 + 'px';
			$('.embed-loading').hide();
		});
	}
});
		<?php if ( 0 ) { ?></script><?php }

		$script = ob_get_clean();

		return trim( $script );
	}


	/**
	 * Register widgets.
	 *
	 * @since 2.0.0.0
	 *
	 * @param array $widgets The list of available widgets.
	 * @return array Available GD widgets.
	 */
	public static function register_widgets( $widgets ) {
		if ( ! class_exists( 'GeoDir_Widget_Embed' ) ) {
			include_once( dirname( __FILE__ ) . "/../widgets/class-geodir-widget-embed.php" );
		}

		$widgets[] = 'GeoDir_Widget_Embed';

		return $widgets;
	}

	/**
	 * Loads the plugin language files
	 *
	 * @access public
	 * @since 2.0.0
	 * @return void
	 */
	public static function load_textdomain() {
		// Determines the current locale.
		$locale = determine_locale();

		/**
		 * Filter the plugin locale.
		 *
		 * @since   2.0.0
		 * @package GeoDir_Embed
		 */
		$locale = apply_filters( 'plugin_locale', $locale, 'geodir-embed' );

		unload_textdomain( 'geodir-embed', true );
		load_textdomain( 'geodir-embed', WP_LANG_DIR . '/geodir-embed/geodir-embed-' . $locale . '.mo' );
		load_plugin_textdomain( 'geodir-embed', false, basename( dirname( GEODIR_EMBED_PLUGIN_FILE ) ) . '/languages/' );
	}

	/**
	 * Filters the X-Frame-Options and Content-Security-Policy headers to ensure embed content load in iframe.
	 *
	 * @since 2.2.1
	 *
	 * @param array  $headers Associative array of headers to be sent.
	 * @param object $wp Current WordPress environment instance.
	 * @return array Headers.
	 */
	public static function filter_iframe_security_headers( $headers, $wp ) {
		$headers['X-Frame-Options']         = 'SAMEORIGIN';
		$headers['Content-Security-Policy'] = "frame-ancestors 'self'";
		return $headers;
	}
}