<?php
/**
 * GeoDirectory Embed Actions.
 *
 * Adds the ability to embed widgets on external websites.
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
 * Class GeoDir_Embed_Action
 */
class GeoDir_Embed_Action {


	/**
	 * Init.
	 */
	public static function init() {
		add_action( 'template_redirect', array( __CLASS__, 'maybe_embed_and_exit' ), 0 );
		add_filter( 'geodir_embed_check_widget_display', array( __CLASS__, 'check_widget_display' ), 10, 3 );
		add_filter( 'geodir_pricing_package_features', array( __CLASS__, 'pricing_package_features' ), 22, 4 );
		self::add_embed_events();
	}

	/**
	 * Hook in methods - uses WordPress ajax handlers (admin-ajax).
	 */
	public static function add_embed_events() {
		$embed_events = array(
			"rating"
		);

		foreach ( $embed_events as $embed_event ) {
			// GeoDir Embed action
			add_action( 'gd_embed_' . $embed_event, array( __CLASS__, $embed_event ) );
		}
	}

	/**
	 * Check if we should show embed settings and exit.
	 *
	 * @since 2.0.0
	 */
	public static function maybe_embed_and_exit() {
		global $wp_query;

		if ( ! empty( $_GET['gd-embed'] ) ) {
			$wp_query->set( 'gd-embed', sanitize_text_field( wp_unslash( $_GET['gd-embed'] ) ) );
		}

		$action = $wp_query->get( 'gd-embed' );

		if ( $action ) {
			self::gd_embed_headers();
			$action = sanitize_text_field( $action );
			do_action( 'gd_embed_' . $action );
			exit;
		}
	}

	/**
	 * Send headers for GD Embed Requests.
	 *
	 * @since 2.0.0
	 */
	private static function gd_embed_headers() {
		send_origin_headers();
		@header( 'Content-Type: application/javascript; charset=' . get_option( 'blog_charset' ) );
		@header( 'X-Robots-Tag: noindex' );
		send_nosniff_header();
		nocache_headers();
		status_header( 200 );
	}

	/**
	 * Gets the JS request for the external embed.
	 *
	 * @since 2.0.0
	 * @return bool
	 */
	public static function rating() {
		$id = ! empty( $_REQUEST['id'] ) && absint( $_REQUEST['id'] ) ? absint( $_REQUEST['id'] ) : '';

		if ( $id ) {
			$gd_post = geodir_get_post_info( $id );

			if ( $gd_post ) {
				if ( ! self::has_allowed_embed_rating( $gd_post ) ) {
					?>
					try{var _rm = document.getElementById('GD_widget_embed_<?php echo $id; ?>');if(_rm){_rm.remove();}}catch(err){};
					<?php
					return;
				}

				$args = array();
				$rating_html = self::sanitize_html_for_js( GeoDir_Comments::rating_output( $gd_post->overall_rating, $args ) );
				$branding_html = self::get_branding_html();
ob_start();
// <script> tag for formatting, stripped on return
?>
<script>
(function () {
	var rating_html = "<?php echo $rating_html;?>";
	var branding_html = "<?php echo $branding_html;?>";
	var title_html = "<a href='<?php echo esc_url( get_permalink( $gd_post->ID ) ); ?>'><?php echo trim( esc_html( strip_tags( get_the_title( (int) $gd_post->ID ) ) ) );?></a>";
	var gd_post = new Object();
	gd_post.rating_count = "<?php echo ! empty( $gd_post->rating_count ) ? absint( $gd_post->rating_count ) : '0';?>";
	var template = "<?php echo self::widget_style_classic( $gd_post );?>";
	var $id = "GD_widget_embed_<?php echo esc_attr( $id );?>";
	var geodir_ec, geodir_ecs = document.querySelectorAll("#" + $id);
	if (geodir_ecs.length > 0) {
		for (var _i = 0; _i < geodir_ecs.length; _i++) {
			geodir_ec = geodir_ecs[_i];
			geodir_ec.innerHTML = template;
			gde_check_font_awesome($id, geodir_ec); /* Maybe add delay */
		}
	}
})();
function gde_check_font_awesome($id, geodir_ec) {
	if (document.querySelector('.gde-rating-stars i')) {
		$fa = window.getComputedStyle(
			document.querySelector('.gde-rating-stars i'), null
		).getPropertyValue('font-family');
		if (!$fa.startsWith('"Font Awesome 5')) {
			/* If no font awesome then we add a class to make Unicode backup stars work. */
			geodir_ec.classList.add("gde-utf8-stars");
		}
	}
}
<?php
				$content = ob_get_clean();
				$content = str_replace( "<script>", "", trim( $content ) );
				echo $content;
				exit;
			}
		}

		return false;
	}

	/**
	 * Remove line breaks and replace double quotes with single quotes in html.
	 *
	 * @param $html
	 *
	 * @return mixed
	 */
	public static function sanitize_html_for_js( $html ) {
		$html = str_replace( array( "\r\n", "\r", "\n" ), '', $html );
		$html = str_replace( '"', "'", $html );

		// replace CDN if set
		$cdn_url = geodir_get_option( "embed_cdn_url" );
		if($cdn_url){
			$cdn_url = esc_url($cdn_url);
			$html = str_replace( trailingslashit( site_url() ), trailingslashit( $cdn_url ), $html );
		}

		return $html;
	}

	/**
	 * Get the branding html to be used in the external embed.
	 *
	 * @since 2.0.0
	 * @return string
	 */
	public static function get_branding_html() {
		$site_url      = apply_filters( 'geodir_brand_url', esc_url( site_url() ) );
		$branding_html = "<a href='$site_url'>";

		// embed image
		$embed_image = '';
		if ( ! empty( $embed_image_id = geodir_get_option( "embed_logo" ) ) ) {
			$embed_image = wp_get_attachment_image_src( $embed_image_id, 'medium' ); // medium size so that it does not crop
			if ( ! empty( $embed_image[0] ) ) {
				// replace CDN if set
				$cdn_url = geodir_get_option( "embed_cdn_url" );
				if($cdn_url){
					$cdn_url = esc_url($cdn_url);
					$embed_image[0] = str_replace( trailingslashit( site_url() ), trailingslashit( $cdn_url ), $embed_image[0] );
				}
				$branding_html .= "<img src = " . esc_url( $embed_image[0] ) . " alt='" . site_url() . "' style='max-width:100%;' />";
			}

		}

		// fallback text
		if ( ! $embed_image ) {
			$branding_html .= esc_html( geodir_get_option( "embed_branding_text", site_url() ) );
		}

		$branding_html .= '</a>';

		return $branding_html;
	}

	/**
	 * Html template for the classic embed widget.
	 *
	 * Its a bad idea to give the user control over this, they could break users sites.
	 * @since 2.0.0
	 */
	public static function widget_style_classic( $the_post ) {
		// wrap
		$html = "<div class='gde-inner-wrap'>";

		$link_rating_stars = false;
		$link_rating_text = false;
		if ( geodir_get_option( "embed_user_link_ratings", 1 ) && ! empty( $the_post ) ) {
			$link_rating_stars = ! empty( $_REQUEST['link_rs'] ) ? true : false;
			$link_rating_text = ! empty( $_REQUEST['link_rt'] ) ? true : false;
		}

		// title
		$html .= "<div class='gde-title'>";
		$html .= '"+title_html+"';
		$html .= "</div>";

		// rating
		$html .= "<div class='gde-rating'>";
		$html .= "<span class='gde-rating-stars'>";
		if ( $link_rating_stars ) {
			$html .= "<a href='" . esc_url( get_comments_link( (int) $the_post->ID ) ) . "' class='gde-rating-star-link'>";
		}
		$html .= '"+rating_html+"';
		if ( $link_rating_stars ) {
			$html .= "</a>";
		}
		$html .= "</span>";
		$html .= "<span class='gde-rating-text'>";
		if ( $link_rating_text ) {
			$html .= "<a href='" . esc_url( get_comments_link( (int) $the_post->ID ) ) . "' class='gde-rating-text-link'>";
		}
		$html .= '" + ( gd_post.rating_count > 1 ? ' . sprintf( __( "%s Ratings", "geodir-embed" ), 'gd_post.rating_count + "' ) . '" : ' . sprintf( __( "%s Rating", "geodir-embed" ), 'gd_post.rating_count + "' ) . '" ) + "';
		if ( $link_rating_text ) {
			$html .= "</a>";
		}
		$html .= "</span>";
		$html .= "</div>";

		// title
		$html .= "<div class='gde-branding'>";
		$html .= '"+branding_html+"';
		$html .= "</div>";

		$html .= "</div>"; // end wrap

		$html .= self::get_css();

		return $html;
	}

	/**
	 * Builds the CSS settings for the external embed.
	 *
	 * @since 2.0.0
	 * @return string
	 */
	public static function get_css() {
		$settings = array(
			"border_color"  => geodir_get_option( "embed_border_color", "#FF9800" ),
			"border_width"  => geodir_get_option( "embed_border_width", "2" ),
			"border_radius" => geodir_get_option( "embed_border_radius", "0" ),
			"border_shadow" => geodir_get_option( "embed_border_shadow", "0" ),
			"background"    => geodir_get_option( "embed_background", "#FFFFFF" ),
			"link_color"    => geodir_get_option( "embed_link_color", "#353535" ),
			"text_color"    => geodir_get_option( "embed_text_color", "#7d7d7d" ),
			"padding"       => "5",
		);

		// border color
		if ( geodir_get_option( "embed_user_border_color", 0 ) && ! empty( $_REQUEST['border_color'] ) && sanitize_hex_color( $_REQUEST['border_color'] ) ) {
			$settings['border_color'] = sanitize_hex_color( $_REQUEST['border_color'] );
		}
		// border width
		if ( geodir_get_option( "embed_user_border_width", 1 ) && isset( $_REQUEST['border'] ) ) {
			$settings['border_width'] = absint( $_REQUEST['border'] );
		}
		// border radius
		if ( geodir_get_option( "embed_user_border_radius", 1 ) && isset( $_REQUEST['radius'] ) ) {
			$settings['border_radius'] = absint( $_REQUEST['radius'] );
		}
		// border shadow
		if ( geodir_get_option( "embed_user_border_shadow", 1 ) && isset( $_REQUEST['shadow'] ) ) {
			$settings['border_shadow'] = absint( $_REQUEST['shadow'] );
		} elseif ( geodir_get_option( "embed_user_border_shadow", 1 ) && ! isset( $_REQUEST['shadow'] ) ) {
			$settings['border_shadow'] = 0;
		}

		// background
		if ( geodir_get_option( "embed_user_background", 1 ) && isset( $_REQUEST['background'] ) ) {
			$settings['background'] = sanitize_hex_color( $_REQUEST['background'] );
		}
		// link color
		if ( geodir_get_option( "embed_user_link_color", 0 ) && ! empty( $_REQUEST['link_color'] ) && sanitize_hex_color( $_REQUEST['link_color'] ) ) {
			$settings['link_color'] = sanitize_hex_color( $_REQUEST['link_color'] );
		}
		// text color
		if ( geodir_get_option( "embed_user_text_color", 0 ) && ! empty( $_REQUEST['text_color'] ) && sanitize_hex_color( $_REQUEST['text_color'] ) ) {
			$settings['text_color'] = sanitize_hex_color( $_REQUEST['text_color'] );
		}

		// ESCAPE ALL THE THINGS!!! :Q
		$settings['border_color']  = sanitize_hex_color( $settings['border_color'] );
		$settings['border_width']  = absint( $settings['border_width'] );
		$settings['border_radius'] = absint( $settings['border_radius'] );
		$settings['border_shadow'] = absint( $settings['border_shadow'] );
		$settings['background']    = sanitize_hex_color( $settings['background'] );
		$settings['link_color']    = sanitize_hex_color( $settings['link_color'] );
		$settings['text_color']    = sanitize_hex_color( $settings['text_color'] );


		$id        = ! empty( $_REQUEST['id'] ) && absint( $_REQUEST['id'] ) ? absint( $_REQUEST['id'] ) : '';
		$css_rules = array();
		$css       = "<style>";

		// size
		if ( isset( $_REQUEST['size'] ) && $_REQUEST['size'] == 'wide' ) {
			$css_rules[ '#GD_widget_embed_' . $id ]['max-width']                             = "468px";
			$css_rules[ '#GD_widget_embed_' . $id . ' .gde-inner-wrap' ]['display']          = "flex";
			$css_rules[ '#GD_widget_embed_' . $id . ' .gde-inner-wrap > div' ]['flex-basis'] = "100%";
			$css_rules[ '#GD_widget_embed_' . $id . ' .gde-inner-wrap > div' ]['padding']    = $settings['padding'] . "px";
			$css_rules[ '#GD_widget_embed_' . $id . ' .gde-branding' ]['order']              = "1";
			// border
			if ( $settings['border_width'] && $settings['border_color'] ) {
				$css_rules[ '#GD_widget_embed_' . $id . ' .gde-branding' ]['border-right'] = $settings['border_width'] . "px solid " . $settings['border_color'];
			}
			$css_rules[ '#GD_widget_embed_' . $id . ' .gde-title' ]['order'] = "2";

			// vertically align text
			$css_rules[ '#GD_widget_embed_' . $id . ' .gde-title' ]['display']          = "table";
			$css_rules[ '#GD_widget_embed_' . $id . ' .gde-title' ]['height']           = "49px";
			$css_rules[ '#GD_widget_embed_' . $id . ' .gde-title a' ]['display']        = "table-cell";
			$css_rules[ '#GD_widget_embed_' . $id . ' .gde-title a' ]['vertical-align'] = "middle";

			$css_rules[ '#GD_widget_embed_' . $id . ' .gde-branding' ]['display']          = "table";
			$css_rules[ '#GD_widget_embed_' . $id . ' .gde-branding' ]['height']           = "49px";
			$css_rules[ '#GD_widget_embed_' . $id . ' .gde-branding a' ]['display']        = "table-cell";
			$css_rules[ '#GD_widget_embed_' . $id . ' .gde-branding a' ]['vertical-align'] = "middle";

			$css_rules[ '#GD_widget_embed_' . $id . ' .gde-rating' ]['order'] = "3";
		} else {
			$css_rules[ '#GD_widget_embed_' . $id ]['max-width'] = "160px";
			$css_rules[ '#GD_widget_embed_' . $id ]['padding']   = $settings['padding'] . "px";

		}

		// defaults
		$css_rules[ '#GD_widget_embed_' . $id ]['overflow']   = "hidden";
		$css_rules[ '#GD_widget_embed_' . $id ]['text-align'] = "center";

		// link color
		if ( $settings['link_color'] ) {
			$css_rules[ '#GD_widget_embed_' . $id . ' a' ]['color']         = $settings['link_color'];
			$css_rules[ '#GD_widget_embed_' . $id . ' a:visited' ]['color'] = $settings['link_color'];
		}

		// text color
		if ( $settings['text_color'] ) {
			$css_rules[ '#GD_widget_embed_' . $id ]['color'] = $settings['text_color'];
		}

		// border
		if ( $settings['border_width'] && $settings['border_color'] ) {
			$css_rules[ '#GD_widget_embed_' . $id ]['border'] = $settings['border_width'] . "px solid " . $settings['border_color'];
		}

		// border radius
		if ( $settings['border_radius'] ) {
			$css_rules[ '#GD_widget_embed_' . $id ]['border-radius'] = $settings['border_radius'] . "px";
		}

		// shadow
		if ( $settings['border_shadow'] && $settings['border_shadow'] == '1' ) {
			$css_rules[ '#GD_widget_embed_' . $id ]['-webkit-box-shadow'] = "5px 5px 6px -6px rgba(0,0,0,0.75)";
			$css_rules[ '#GD_widget_embed_' . $id ]['-moz-box-shadow']    = "5px 5px 6px -6px rgba(0,0,0,0.75)";
			$css_rules[ '#GD_widget_embed_' . $id ]['box-shadow']         = "5px 5px 6px -6px rgba(0,0,0,0.75)";
		}

		// background
		if ( $settings['background'] ) {
			$css_rules[ '#GD_widget_embed_' . $id ]['background'] = $settings['background'];
		}


		// ratings styles
		$css_rules['.gd-rating-info-wrap .gd-list-rating-stars']['display']                                      = "inline-block";
		$css_rules['.gd-rating.gd-rating-output']['font-size']                                                   = "16px";
		$css_rules['.gd-rating-outer-wrap .gd-rating-input, .gd-rating-outer-wrap .gd-rating-output']['display'] = "inline-block";
		$css_rules['.gd-rating']['line-height']                                                                  = "0";
		$css_rules['.gd-rating']['position']                                                                     = "relative";
		$css_rules['.gd-rating']['font-size']                                                                    = "20px";
		$css_rules['.gd-rating']['margin']                                                                       = "5px 0";
		$css_rules['.gd-rating .gd-rating-wrap']['display']                                                      = "inline-grid";
		$css_rules['.gd-rating .gd-rating-wrap']['max-width']                                                    = "max-content";
		$css_rules['.gd-rating .gd-rating-wrap']['overflow']                                                     = "hidden";
		$css_rules['.gd-rating .gd-rating-wrap']['position']                                                     = "relative";
		$css_rules['.gd-rating .gd-rating-wrap']['cursor']                                                       = "pointer";
		$css_rules['.gd-rating .gd-rating-wrap']['vertical-align']                                               = "middle";
		$css_rules['.gd-rating .gd-rating-wrap .gd-rating-foreground']['color']                                  = "orange";
		$css_rules['.gd-rating .gd-rating-wrap .gd-rating-foreground']['position']                               = "absolute";
		$css_rules['.gd-rating .gd-rating-wrap .gd-rating-foreground']['width']                                  = "50%";
		$css_rules['.gd-rating .gd-rating-wrap .gd-rating-foreground']['white-space']                            = "nowrap";
		$css_rules['.gd-rating .gd-rating-wrap .gd-rating-foreground']['overflow']                               = "hidden";
		$css_rules['.gd-rating .gd-rating-wrap .gd-rating-background']['white-space']                            = "nowrap";
		$css_rules['.gd-rating .gd-rating-wrap .gd-rating-background']['overflow']                               = "hidden";
		$css_rules['.gd-rating .gd-rating-wrap .gd-rating-background']['color']                                  = "#ccc";
		$css_rules['.gde-utf8-stars i']['display']                                                               = "inline-block";
		$css_rules['.gde-utf8-stars i:after']['content']                                                         = '★';// https://www.utf8icons.com/character/9733/black-star
		$css_rules['.gde-utf8-stars i:after']['display']                                                         = "block";
		$css_rules['.gde-utf8-stars i:after']['font-size']                                                       = "25px";
		$css_rules['.gde-utf8-stars i:after']['padding']                                                         = "10px 0";
		$css_rules['.gde-utf8-stars i:after']['font-style']                                                      = "normal";
		$css_rules['.gde-utf8-stars i:before']['display']                                                        = "none";


		// convert rules to CSS
		foreach ( $css_rules as $key => $vals ) {
			$css .= "$key{";
			if ( ! empty( $vals ) ) {
				foreach ( $vals as $rule => $val ) {
					if ( $rule == 'content' ) {
						$val = "'" . $val . "'";
					} // content needs to be wrapped in quotes
					else {
						$val = esc_attr( $val );
					}
					$css .= esc_attr( $rule ) . ":" . $val . ";";
				}
			}
			$css .= "}";
		}

		$css .= "</style>";

		return $css;
	}

	/**
	 * Get the rating stars for the listing.
	 *
	 * @since 2.0.0
	 *
	 * @param int $star_count
	 *
	 * @return string
	 */
	public static function get_rating_stars( $star_count = 5 ) {
		$template = "<span>&#9734;</span>";
		$html     = "";
		$i        = 0;
		while ( $i < $star_count ) {
			$html .= $template;
			$i ++;
		}

		return $html;
	}

	public static function check_widget_display( $show, $args, $gd_post ) {
		if ( $show && ! self::has_allowed_embed_rating( $gd_post ) ) {
			$show = false;
		}

		return $show;
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

	/**
	 * Set embed feature in pricing table.
	 *
	 * @since 2.3.2
	 *
	 * @param array  $features Pricing features.
	 * @param object $package The package.
	 * @param array  $params Pricing widget parameters.
	 * @param array  $args Pricing item args.
	 * @param array Package features.
	 */
	public static function pricing_package_features( $features, $package, $params, $args ) {
		// Review replies
		$embed_rating = array( 
			'order' => 221
		);

		$no_embed_rating = geodir_pricing_get_meta( $package->id, 'no_embed_rating', true );

		if ( empty( $no_embed_rating ) ) {
			$embed_rating['text'] = __( 'Embeddable ratings', 'geodir-embed' );
			$embed_rating['icon'] = $params['fa_icon_tick'];
			$embed_rating['color'] = $params['color_highlight'];
		} else {
			$embed_rating['text'] = __( 'No embeddable ratings', 'geodir-embed' );
			$embed_rating['icon'] = $params['fa_icon_untick'];
			$embed_rating['color'] = $params['color_default'];
		}
		$features['embed_rating'] = $embed_rating;

		return $features;
	}
}

GeoDir_Embed_Action::init();