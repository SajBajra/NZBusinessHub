<?php
/**
 * GeoDirectory Embed Builder
 *
 * Lets users build their external embed code.
 *
 * @author   AyeCode
 * @category Embed
 * @package  GeoDirectory
 * @since    1.0.0
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class GeoDir_Embed_Builder
 */
class GeoDir_Embed_Builder {


	/**
	 * Init.
	 */
	public static function init() {
		add_action( 'wp_ajax_geodir_embed_builder', array( __CLASS__, 'html_output' ) );
		add_action( 'wp_ajax_nopriv_geodir_embed_builder', array( __CLASS__, 'html_output' ) );
		add_action( 'wp_ajax_geodir_embed_builder_settings', array( __CLASS__, 'ajax_settings' ) );
		add_action( 'wp_ajax_nopriv_geodir_embed_builder_settings', array( __CLASS__, 'ajax_settings' ) );
	}

	/**
	 * Get the settings via ajax.
	 *
	 * @since 2.0.0
	 */
	public static function ajax_settings() {
		$type = isset( $_REQUEST['type'] ) ? esc_attr( $_REQUEST['type'] ) : 'rating';
		self::html_settings_inputs( $type );
		exit;
	}

	/**
	 * Gets the html for the settings inputs to build the embed code.
	 *
	 * @since 2.0.0
	 *
	 * @param string $type
	 */
	public static function html_settings_inputs( $type = 'rating' ) {
		$embed    = new GeoDir_Widget_Embed();
		$args     = self::settings( $type );
		$instance = array();
		if ( $args ) {
			if ( ! empty( $args['description'] ) ) {
				echo "<p>" . esc_attr( $args['description'] ) . "</p>";
			}

			foreach ( $args['arguments'] as $name => $arg ) {
				$arg['name'] = $name; // set the name for Super Duper
				$embed->widget_inputs( $arg, $instance );
			}
		}

	}

	/**
	 * Get the settings array for each embed type.
	 *
	 * @since 2.0.0
	 *
	 * @param string $type
	 *
	 * @return array|mixed
	 */
	public static function settings( $type = '' ) {
		$settings  = array();
		$arguments = array();

		// size
		$arguments['size'] = array(
			'title'    => __( 'Size:', 'geodir-embed' ),
			'desc'     => __( 'The size of the widget.', 'geodir-embed' ),
			'type'     => 'select',
			'options'  => array(
				"narrow" => __( 'Narrow: 160px width, variable height', 'geodir-embed' ),
				"wide"   => __( 'Wide: 468px width maximum, 47px height', 'geodir-embed' ),
			),
			'desc_tip' => true,
			'advanced' => false
		);

		// border color
		if ( geodir_get_option( "embed_user_border_color", 0 ) ) {
			$arguments['border_color'] = array(
				'title'    => __( 'Border color:', 'geodir-embed' ),
				'desc'     => __( 'Set the border color.', 'geodir-embed' ),
				'type'     => 'color',
				'desc_tip' => true,
				'default'  => geodir_get_option( "embed_border_color", "#ff9900" ),
				'advanced' => true
			);
		}

		// border width
		if ( geodir_get_option( "embed_user_border_width", 1 ) ) {
			$arguments['border'] = array(
				'title'    => __( 'Border width:', 'geodir-embed' ),
				'desc'     => __( 'Set the border width in px.', 'geodir-embed' ),
				'type'     => 'number',
				'desc_tip' => true,
				'default'  => geodir_get_option( "embed_border_width", "2" ),
				'advanced' => true
			);
		}

		// border radius
		if ( geodir_get_option( "embed_user_border_radius", 1 ) ) {
			$arguments['radius'] = array(
				'title'    => __( 'Border radius:', 'geodir-embed' ),
				'desc'     => __( 'Set the border corner radius', 'geodir-embed' ),
				'type'     => 'number',
				'desc_tip' => true,
				'value'    => '',
				'default'  => geodir_get_option( "embed_border_radius", "0" ),
				'advanced' => true
			);
		}

		// maybe allow border adjust
		if ( geodir_get_option( "embed_user_border_shadow", 1 ) ) {
			$arguments['shadow'] = array(
				'title'    => __( 'Show shadow', 'geodir-embed' ),
				'desc'     => __( 'Show the widget border shadow.', 'geodir-embed' ),
				'type'     => 'checkbox',
				'desc_tip' => true,
				'value'    => '1',
				'default'  => geodir_get_option( "embed_border_shadow", "0" ),
				'advanced' => true
			);
		}

		// maybe allow border adjust
		if ( geodir_get_option( "embed_user_background", 1 ) ) {
			$arguments['background'] = array(
				'title'    => __( 'Background:', 'geodir-embed' ),
				'desc'     => __( 'The background color.', 'geodir-embed' ),
				'default'  => geodir_get_option( "embed_background", "#FFFFFF" ),
				'desc_tip' => true,
				'type'     => 'color',
				'advanced' => false
			);
		}

		// Rating Link
		if ( geodir_get_option( "embed_user_link_ratings", 1 ) ) {
			$arguments['link_rs'] = array(
				'title'    => __( 'Link rating stars to reviews', 'geodir-embed' ),
				'desc'     => __( 'Add link to rating stars, so click on rating stars redirects to the listing reviews.', 'geodir-embed' ),
				'type'     => 'checkbox',
				'desc_tip' => true,
				'value'    => '1',
				'default'  => '',
				'advanced' => true
			);

			$arguments['link_rt'] = array(
				'title'    => __( 'Link rating text to reviews', 'geodir-embed' ),
				'desc'     => __( 'Add link to rating text, so click on rating text redirects to the listing reviews.', 'geodir-embed' ),
				'type'     => 'checkbox',
				'desc_tip' => true,
				'value'    => '1',
				'default'  => 1,
				'advanced' => true
			);
		}

		// Link color
		if ( geodir_get_option( "embed_user_link_color", 0 ) ) {
			$arguments['link_color'] = array(
				'title'    => __( 'Link color', 'geodir-embed' ),
				'desc'     => __( 'Set the link color.', 'geodir-embed' ),
				'type'     => 'color',
				'desc_tip' => true,
				'default'  => geodir_get_option( "embed_link_color", "#353535" ),
				'advanced' => true
			);
		}


		// Link color
		if ( geodir_get_option( "embed_user_text_color", 0 ) ) {
			$arguments['text_color'] = array(
				'title'    => __( 'Text color', 'geodir-embed' ),
				'desc'     => __( 'Set the text color.', 'geodir-embed' ),
				'type'     => 'color',
				'desc_tip' => true,
				'default'  => geodir_get_option( "embed_text_color", "#7d7d7d" ),
				'advanced' => true
			);
		}


		// rating
		$settings['rating'] = array(
			'name'        => 'rating',
			'option'      => __( 'Rating (embed your ratings score on your site).', 'geodir-embed' ),
			'description' => __( 'Embed your ratings on your site.', 'geodir-embed' ),
			'arguments'   => $arguments,
		);

//
//		// reviews @todo add more options in latter versions
//		$settings['reviews'] = array(
//			'name'        => 'rating',
//			'option'      => __( 'Read reviews (embed a link to read your reviews).', 'geodir-embed' ),
//			'description' => __( 'Embed a link to read your reviews on your site.', 'geodir-embed' ),
//			'arguments'   => $arguments,
//		);

		if ( $type && isset( $settings[ $type ] ) ) {
			return $settings[ $type ];
		}

		return $settings;
	}

	/**
	 * Get the html output for the embed iframe lightbox.
	 *
	 * @since 2.0.0
	 */
	public static function html_output() {
		echo self::html_header();
		echo self::html_content();
		echo self::html_footer();
		exit;
	}

	/**
	 * Gets the header for the embed lightbox html.
	 */
	public static function html_header() {
		$headers = array();
		$headers['X-Frame-Options']         = 'SAMEORIGIN';
		$headers['Content-Security-Policy'] = "frame-ancestors 'self'";
		
		/**
		 * Filters the HTTP headers before they're sent to the browser.
		 *
		 * @since 2.2.1
		 *
		 * @param array $headers Associative array of headers to be sent.
		 */
		$headers = apply_filters( 'geodir_embed_builder_headers', $headers );

		if ( ! empty( $headers ) && ! headers_sent() ) {
			foreach ( $headers as $name => $field_value ) {
				header( "{$name}: {$field_value}" );
			}
		}

		/* Fix conflict with "LearnDash LMS" & "Astra PRO" plugins. */
		if ( class_exists( 'Astra_Ext_LearnDash_Loader' ) && ! function_exists( 'astra_ldrv3_dynamic_css' ) && defined( 'ASTRA_EXT_LEARNDASH_DIR' ) && file_exists( ASTRA_EXT_LEARNDASH_DIR . 'classes/dynamic.css.php' ) ) {
			require_once ASTRA_EXT_LEARNDASH_DIR . 'classes/dynamic.css.php';
		}

		$class = '';
		$design_style = geodir_design_style();
		if( $design_style ){
			add_action( 'wp_print_styles', function () {
				global $wp_styles;
				$wp_styles->queue = array( 'ayecode-ui' );
				$wp_styles->queue = array( 'font-awesome' );
			}, 1000 );
			$class = 'bsui';
		}else{
			/*
			 * We only need the form and its basic CSS/JS so we hack away all lots of others stuff in a naughty way.
			 */
			add_action( 'wp_print_styles', function () {
				global $wp_styles;
				$wp_styles->queue = array( 'dashicons' ) /* array('font-awesome')*/
				;
			}, 1000 );
		}

		add_action( 'wp_print_scripts', function () {
			global $wp_scripts;
			$wp_scripts->queue = array( 'jquery' );
		}, 1000 );

		echo '<!DOCTYPE html><html lang="en-US"><head>';
		wp_head();
		echo self::script();
		echo '</head><body class='. $class .'>';
	}

	/**
	 * Gets the CSS and JS for the embed lightbox html.
	 */
	public static function script() {
		$post_id = isset( $_REQUEST['post_id'] ) ? absint( $_REQUEST['post_id'] ) : '';
		?>
		<style>

			body {
				background-color: #ffffff;
				padding: 20px 0;
				color: #353535;
			}

			#gde-embed-settings {

			}

			.gde-embed-builder-wrapper {
				display: flex;
				flex-flow: row wrap;
			}

			.gde-embed-builder-wrapper > * {
				flex: 1 100%;
			}

			.gde-left-wrap, .gde-right-wrap {
				padding: 0 20px;
			}

			.gde-left-wrap {
				border-right: 1px solid #eee;
			}

			.gde-embed-builder-code {
				padding-top: 20px;
				border-top: 1px solid #eee;
				margin-top: 20px;
			}

			textarea {
				width: 100%;
				min-height: 100px;
				box-sizing: border-box;
				background: rgba(255, 255, 255, .5);
				border-color: rgba(222, 222, 222, .75);
				box-shadow: inset 0 1px 2px rgba(0, 0, 0, .04);
				color: rgba(51, 51, 51, .5);
				overflow: auto;
				padding: 2px 6px;
				line-height: 1.4;
				resize: vertical;
			}

			select, input {
				width: 100%;
				background: #fff;
				padding: 5px;
				border: 1px solid #ccc;
				border-radius: 2px;
				box-sizing: border-box;
			}

			input[type=checkbox] {
				width: initial;
			}

			input[type=color] {
				padding: 0;
			}

			.gd-help-tip:after {
				content: "\1F6C8";
				cursor: help;
			}

			.gde-embed-builder-code button {
				width: 100%;
				padding: 8px;
				background: #dddddd;
				border: 1px solid #ccc;
				color: #464646;
				cursor: pointer;
			}

			.gde-embed-info {
				padding: 5px;
				margin: 0 0 5px 0;
				background: #ececec;
				font-size: 14px;
				color: #484848;
			}

			/* Large screens */
			@media all and (min-width: 800px) {
				.gde-left-wrap {
					flex: 1 0;
				}

				.gde-right-wrap {
					flex: 2 0;
				}
			}

			.gd-help-tip::after {
				content: inherit;
			}
			.gd-no-embed-rating {
				padding: 7.5px 15px;
			}
		</style>
		<script>
			/*
			 Builds the embed code from the html settings from.
			 */
			function gde_build_embed_code() {
				var $post_id = jQuery('#post_id').val();
				var $type = jQuery('#gde-embed-type').val();
				var $settings = jQuery('#gde-embed-settings').serialize();
				var $url = "<?php echo trailingslashit(  geodir_get_option( "embed_cdn_url" ) ? esc_url( geodir_get_option( "embed_cdn_url" ) ) : get_home_url() ) . "?gd-embed=";?>" + $type + "&id=" + $post_id + "&" + $settings;
				var $html = "<div id='GD_widget_embed_" + $post_id + "'><?php esc_attr_e( "Loading...", "geodir-embed" );?></div>";

				// remove extra name spacing
				$url = $url.replace(/widget-gd_embed%5B%5D%5B/g, "");
				$url = $url.replace(/%5D=/g, "=");

				$html += "<" + "script async src='" + $url + "'></" + "script>";
				jQuery(".gde-embed-builder-preview").html($html);
				jQuery("#gde-embed-code").val($html);
			}

			/*
			 Gets the settings for the embed type.
			 */
			function gde_get_settings($this) {
				$short_code = jQuery($this).val();
				if ($short_code) {

					jQuery.ajax({
						url: '<?php echo admin_url( "admin-ajax.php" ); ?>',
						type: 'POST',
						data: {
							action: 'geodir_embed_builder_settings',
							//security: $nonce,
							post_id: <?php echo absint( $post_id );?>,
							type: $short_code
						},
						//timeout: 20000,
						beforeSend: function () {
							jQuery('#gde-embed-settings').html("<?php _e( "Loading...", "geodir-embed" ); ?>");
						},
						success: function (content) {
							jQuery('#gde-embed-settings').html(content);
							gde_build_embed_code();
						}
					});
				}
			}

			/*
			 Copies the embed text so it can be pasted.
			 */
			function gde_copy_code() {
				/* Get the text field */
				var copyText = document.querySelector("#gde-embed-code");
				//un-disable the field
				copyText.disabled = false;
				/* Select the text field */
				copyText.select();
				/* Copy the text inside the text field */
				document.execCommand("Copy");
				//re-disable the field
				copyText.disabled = true;
				/* Alert the copied text */
				alert("<?php esc_attr_e( "Code Copied!", "geodir-embed" )?>");
			}

			// run on load
			jQuery(function () {
				gde_build_embed_code();
			});
		</script>
		<?php
	}

	/**
	 * Get the html content for the settings lightbox.
	 *
	 * @since 2.0.0
	 */
	public static function html_content() {
		$design_style = geodir_design_style();

		$post_id  = isset( $_REQUEST['post_id'] ) ? absint( $_REQUEST['post_id'] ) : 0;

		if ( $post_id > 0 && ! self::has_allowed_embed_rating( $post_id ) ) {
			ob_start();
			?>
			<div class="gde-embed-builder-wrapper"><div class="gd-no-embed-rating"><?php _e( 'Embeddable reviews & ratings feature is not allowed for this listing!', 'geodir-embed' ); ?></div></div>
			<?php
			$content = ob_get_clean();
			echo $content;
			return;
		}

		if( ! $design_style ){
		?>
		<div class="gde-embed-builder-wrapper">
			<div class="gde-left-wrap">
				<?php echo self::html_settings(); ?>

			</div>
			<div class="gde-right-wrap">

				<div class="gde-embed-builder-preview">
					<?php _e( "Loading...", "geodir-embed" ); ?>
				</div>

				<div class="gde-embed-builder-code">
					<p class="gde-embed-info"><span
							class="gd-help-tip dashicons dashicons-editor-help"></span><?php esc_attr_e( "To use a embedded widget, add the customized code below into an HTML page on your website.", "geodir-embed" ); ?>
					</p>
					<textarea id="gde-embed-code"><?php _e( "Loading...", "geodir-embed" ); ?></textarea>
					<button onclick="gde_copy_code();"><?php _e( "Copy Code", "geodir-embed" ); ?></button>
				</div>

			</div>

		</div>
		<?php
		}
		else {
			?>
				<div class="gde-embed-builder-wrapper">
					<div class="gde-left-wrap">
						<?php echo self::html_settings(); ?>
					</div>
					<div class="gde-right-wrap">

						<?php 
							echo aui()->alert(array(
									'type'		=> 'info',
									'content'	=> __( 'Loading...', 'geodir-embed' ),
									'class'		=> 'gde-embed-builder-preview border-0 bg-white text-primary'
								)
							);
						?>

						<div class="gde-embed-builder-code">
							<?php 
								echo aui()->alert(array(
										'type'		=> 'info',
										'content'	=> __( 'To use a embedded widget, add the customized code below into an HTML page on your website.', 'geodir-embed' ),
										'class'		=> 'gde-embed-info'
									)
								);
								echo aui()->textarea(
									array(
										'id'		=> 'gde-embed-code',
										'value'		=>	__( "Loading..", "geodir-embed" )
									)
								); 
								echo aui()->button(
									array(
										'type'		=> 'button',
										'onclick'	=>	'gde_copy_code();',
										'content'		=>	__( "Copy Code", "geodir-embed" )
									)
								);  
							?>
						</div>
					</div>
				</div>
			<?php
		}
	}

	/**
	 * Gets the settings html for the lightbox content.
	 *
	 * @since 2.0.0
	 *
	 * @param string $type
	 */
	public static function html_settings( $type = 'rating' ) {
		$settings = self::settings();
		$post_id  = isset( $_REQUEST['post_id'] ) ? absint( $_REQUEST['post_id'] ) : '';
		$design_style = geodir_design_style();
		
		if( ! $design_style ){
		?>
			<div class="gde-embed-builder-settings">
				<div class="type-selector">
					<input type="hidden" id="post_id" value="<?php echo absint( $post_id ); ?>">
					<select id="gde-embed-type" onchange="gde_get_settings(this);">
						<?php
						foreach ( $settings as $key => $setting ) {
							echo "<option value='" . esc_attr( $key ) . "'>" . esc_attr( $setting['option'] ) . "</option>";
						}
						?>
					</select>
				</div>
				<form id="gde-embed-settings" onchange="gde_build_embed_code();">
					<?php
					self::html_settings_inputs( $type );
					?>
				</form>
			</div>
		<?php
		} else{
			?>
				<div class="gde-embed-builder-settings">
					<div class="type-selector">
						<input type="hidden" id="post_id" value="<?php echo absint( $post_id ); ?>">
						<select id="gde-embed-type" onchange="gde_get_settings(this);">
							<?php
							foreach ( $settings as $key => $setting ) {
								echo "<option value='" . esc_attr( $key ) . "'>" . esc_attr( $setting['option'] ) . "</option>";
							}
							?>
						</select>
					</div>
					<form id="gde-embed-settings" onchange="gde_build_embed_code();">
						<?php
						self::html_settings_inputs( $type );
						?>
					</form>
				</div>
			<?php
		}
	}

	/**
	 * The html footer close.
	 */
	public static function html_footer() {
		echo '</body></html>';
	}

	/**
	 * Gets the embed code preview html.
	 *
	 * @since 2.0.0
	 */
	public static function html_preview() {
		echo self::html_embed_code();
	}

	/**
	 * Gets the embed code for the embed builder lightbox.
	 */
	public static function html_embed_code() {
		$post_id = isset( $_REQUEST['post_id'] ) ? absint( $_REQUEST['post_id'] ) : '';
		if ( $post_id ) {
			$gd_post = geodir_get_post_info( $post_id );
			if ( $gd_post ) {
				echo GeoDir_External_Embed::get_embed_code( $gd_post, $type = 1, $settings = array() );
			}
		}
	}

	public static function has_allowed_embed_rating( $gd_post ) {
		$allowed = true;

		if ( function_exists( 'geodir_pricing_get_meta' ) && ! empty( $gd_post ) && ( $package = geodir_get_post_package( $gd_post ) ) ) {
			if ( ! empty( $package ) && ! empty( $package->id ) && geodir_pricing_get_meta( (int) $package->id, 'no_embed_rating', true ) ) {
				$allowed = false;
			}
		}

		return apply_filters( 'geodir_embed_has_allowed_rating', $allowed, $gd_post );
	}
}

GeoDir_Embed_Builder::init();