<?php
/**
 * The public-specific functionality of the plugin.
 *
 * @since 2.0.0
 *
 * @package    GD_Google_Maps
 * @subpackage GD_Google_Maps/public
 *
 * Class GD_Google_Maps_Public
 */
class GD_Google_Maps_Public{

    /**
     * Constructor.
     *
     * @since 2.0.0
     *
     * GD_Google_Maps_Public constructor.
     */
    public function __construct() {

        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles_and_scripts' ) ,99 );
        add_action('widgets_init', array( $this, 'gmaps_style_init' ), 10);
        add_action( 'geodir_maps_extra_script', array( $this, 'maps_extra_script' ), 20 );
        add_filter( 'geodir_params', array( $this, 'localize_params' ), 20, 1 );
        add_filter( 'geodir_map_api_osm_data', array( $this, 'map_api_osm_data' ), 20, 1 );
    }

	/**
	 * Enqueue scripts after call.
	 *
	 * @since 2.3.5
	 */
	public function enqueue_scripts_after_call( $lazy_load_scripts, $args, $widget, $extra ) {
		// OSM + Custom style on add listing page map.
		if ( $lazy_load_scripts && $widget->id_base == 'gd_add_listing' && get_option( 'gd_custom_osm_add_listing_options' ) ) {
			$this->load_scripts();
		}
	}

    public function enqueue_styles_and_scripts() {
		if ( function_exists( 'geodir_load_scripts_on_call' ) && geodir_load_scripts_on_call() ) {
			return;
		}

		$this->load_scripts();
	}

	/**
	 * Register and enqueue duplicate alert styles and scripts.
	 *
	 * @since 2.0.0
	 */
	public function load_scripts( $args = array() ) {
		$map = GeoDir_Maps::active_map();

		if ( 'osm' === $map && ! geodir_lazy_load_map() ) {
			$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

			if ( function_exists( 'geodir_load_scripts_on_call' ) && geodir_load_scripts_on_call() && wp_script_is( 'geodir-leaflet-providers', 'enqueued' ) ) {
				return;
			}

			wp_register_script( 'geodir-leaflet-providers', GD_GOOGLE_MAPS_PLUGIN_URL . 'assets/js/leaflet-providers' . $suffix . '.js', array( 'geodir-leaflet' ), GD_GOOGLE_MAPS_VERSION, true );
			wp_enqueue_script( 'geodir-leaflet-providers' );
		}
	}

    /**
     * Custom google map style widget init action.
     *
     * Filter for add custom google map options in map.
     *
     * @since 2.0.0
     */
    public function gmaps_style_init() {
        add_action( 'geodir_add_listing_map_inline_js', array( $this, 'add_listing_map_inline_js' ), 5, 4 );
        add_filter( 'geodir_map_params', array( $this, 'custom_google_map_options') , 10, 2 );
        add_filter( 'geodir_template_render_map_js_params', array( $this, 'template_render_map_js_params') , 10 );
    }

    /**
     * Set custom google map options.
     *
     * If google map API set as google then set google map options in home, listing and detail page.
     * else if google map API set as osm then set open street map options in home, listing and detail page.
     *
     * @since 2.0.0
     */
    public function custom_google_map_options( $params, $map_args = array() ) {
        $custom_maps_settings = geodir_get_option('custom_google_maps', array());

        $get_map_home_option = !empty( $custom_maps_settings['gd_custom_map_home_checkbox'] ) ? $custom_maps_settings['gd_custom_map_home_checkbox'] : '';
        $get_map_listing_option = !empty( $custom_maps_settings['gd_custom_map_listing_checkbox'] ) ? $custom_maps_settings['gd_custom_map_listing_checkbox'] : '';
        $get_map_detail_option = !empty( $custom_maps_settings['gd_custom_map_detail_checkbox'] ) ? $custom_maps_settings['gd_custom_map_detail_checkbox'] : '';
		$get_map_add_listing_option = ! empty( $custom_maps_settings['gd_custom_map_add_listing_checkbox'] ) ? $custom_maps_settings['gd_custom_map_add_listing_checkbox'] : '';

        $active_map = GeoDir_Maps::active_map();

        // directory map
        if($get_map_home_option && !empty( $params['map_type'] ) && $params['map_type']=='directory'){
            $style_option = get_option('gd_custom_maps_home_style');
            $style_option = maybe_unserialize($style_option);

            if ( $active_map == 'google' || $active_map == 'auto' ) {
                if (!empty($style_option) && (is_array($style_option) || is_object($style_option))) {
                    $params['mapStyles'] = json_encode($style_option);
                }

            }

			if ( $active_map == 'osm' || $active_map == 'auto' ) {
                $osm_style = get_gd_cgm_custom_osm_styles( 'home' );

                $params['osmBaseLayer'] = !empty( $osm_style['baseLayer'] ) ? $osm_style['baseLayer'] : 'OpenStreetMap.Mapnik';
                $params['osmOverlays'] = !empty($osm_style['overlays']) ? $osm_style['overlays'] : array();

            }
        }
        // archive map
        elseif($get_map_listing_option && !empty( $params['map_type'] ) && $params['map_type']=='archive'){
            $style_option = get_option('gd_custom_maps_listing_style');
            $style_option = maybe_unserialize($style_option);

            if ( $active_map == 'google' || $active_map == 'auto' ) {
                if (!empty($style_option) && (is_array($style_option) || is_object($style_option))) {
                    $params['mapStyles'] = json_encode($style_option);
                }

            }

			if ( $active_map == 'osm' || $active_map == 'auto' ) {
                $osm_style = get_gd_cgm_custom_osm_styles( 'listing' );

                $params['osmBaseLayer'] = !empty( $osm_style['baseLayer'] ) ? $osm_style['baseLayer'] : 'OpenStreetMap.Mapnik';
                $params['osmOverlays'] = !empty($osm_style['overlays']) ? $osm_style['overlays'] : array();

            }
        }
        // post map
        elseif($get_map_detail_option && !empty( $params['map_type'] ) && $params['map_type']=='post'){
            $style_option = get_option('gd_custom_maps_details_style');
            $style_option = maybe_unserialize($style_option);

            if ( $active_map == 'google' || $active_map == 'auto' ) {
                if (!empty($style_option) && (is_array($style_option) || is_object($style_option))) {
                    $params['mapStyles'] = json_encode($style_option);
                }

            }

			if ( $active_map == 'osm' || $active_map == 'auto' ) {
                $osm_style = get_gd_cgm_custom_osm_styles( 'detail' );

                $params['osmBaseLayer'] = !empty( $osm_style['baseLayer'] ) ? $osm_style['baseLayer'] : 'OpenStreetMap.Mapnik';
                $params['osmOverlays'] = !empty($osm_style['overlays']) ? $osm_style['overlays'] : array();

            }
        }
		// add listing map
        elseif ( $get_map_add_listing_option && ! empty( $params['map_type'] ) && $params['map_type'] == 'add_listing' ) {
            $style_option = get_option( 'gd_custom_maps_add_listing_style' );
            $style_option = maybe_unserialize( $style_option );

            if ( $active_map == 'google' || $active_map == 'auto' ) {
                if ( ! empty( $style_option ) && ( is_array( $style_option ) || is_object( $style_option ) ) ) {
                    $params['mapStyles'] = json_encode( $style_option );
                }
            }

			if ( $active_map == 'osm' || $active_map == 'auto' ) {
                $osm_style = get_gd_cgm_custom_osm_styles( 'add_listing' );

                $params['osmBaseLayer'] = ! empty( $osm_style['baseLayer'] ) ? $osm_style['baseLayer'] : 'OpenStreetMap.Mapnik';
                $params['osmOverlays'] = ! empty($osm_style['overlays']) ? $osm_style['overlays'] : array();
            }
        }

        if(!empty($custom_maps_settings['custom_map_osm_api_key'])){
            $params['osmApiKey'] = esc_attr($custom_maps_settings['custom_map_osm_api_key']);
        }

        return $params;
    }

	public function template_render_map_js_params() {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : NULL;
		$screen_id = $screen ? $screen->id : '';

		if ( ! ( $screen_id && geodir_is_gd_post_type( $screen_id ) || geodir_is_page( 'add-listing' ) ) ) {
			return;
		}

		$active_map = GeoDir_Maps::active_map();
		$styles = $this->custom_google_map_options( array( 'map_type' => 'add_listing' ), array() );

		$params = array();
		if ( $active_map == 'osm' || $active_map == 'auto' ) {
			if ( ! empty( $styles['osmBaseLayer'] ) ) {
				$params[] = "osmBaseLayer: '" . $styles['osmBaseLayer'] . "'";
			}
			if ( ! empty( $styles['osmOverlays'] ) ) {
				$params[] = "osmOverlays: " . ( is_array( $styles['osmOverlays'] ) ? json_encode( $styles['osmOverlays'] ) : $styles['osmOverlays'] );
			}
			if ( ! empty( $styles['osmApiKey'] ) ) {
				$params[] = "osmApiKey: '" . $styles['osmApiKey'] . "'";
			}
		}

		if ( ! empty( $params ) ) {
			echo implode( ',', $params ) . ',';
		}
	}

	/**
	 * Add custom JS on add listing map.
	 *
	 * @since 2.3.5
	 *
	 * @param string $map Map name.
	 * @param string $active_map Active map name.
	 * @param bool   $manual_map True for manual map.
	 * @param string $move_inline_script True if script moved under inline JS.
	 */
	public function add_listing_map_inline_js( $map, $active_map, $manual_map, $move_inline_script ) {
		if ( $map == 'google' && ( 'google' === $active_map || $active_map == 'auto' ) && geodir_is_page( 'add-listing' ) ) {
			$styles = $this->custom_google_map_options( array( 'map_type' => 'add_listing' ), array() );

			if ( ! empty( $styles ) && ! empty( $styles['mapStyles'] ) ) {
				echo 'try{$.goMap.map.setOptions({styles:' . $styles['mapStyles'] . '});}catch(err){console.log(err)}';
			}
		}
	}

	public function maps_extra_script() {
		$extra_script = true;

		if ( is_admin() ) {
			$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : NULL;
			$screen_id = $screen ? $screen->id : '';

			if ( ! geodir_is_gd_post_type( $screen_id ) ) {
				$extra_script = false;
			}
		}

		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		if ( $extra_script ) {?> 
			if (!(window.google && typeof google.maps !== 'undefined')) {
				document.write('<' + 'script id="geodir-leaflet-providers" src="<?php echo GD_GOOGLE_MAPS_PLUGIN_URL; ?>assets/js/leaflet-providers<?php echo $suffix; ?>.js?ver=<?php echo GD_GOOGLE_MAPS_VERSION; ?>"><' + '/script>');
			}
			<?php
		}
	}

	/**
	 * Localize parameters.
	 *
	 * @since 2.0.1.0
	 *
	 * @return array Localize parameters.
	 */
	public function localize_params( $params = array() ) {
		$custom_maps_options = geodir_get_option( 'custom_google_maps', array() );

		if ( ! empty( $custom_maps_options ) && ! empty( $custom_maps_options['custom_map_osm_api_key'] ) ) {
			$params['providerApiKey'] = trim( $custom_maps_options['custom_map_osm_api_key'] );
		}

		$params['providersApiKeys'] = apply_filters( 'geodir_custom_map_providers_keys', array() ); // Ex: 'OpenWeatherMap' => 'API-KEY'

		return $params;
	}

	public function map_api_osm_data( $data ) {
		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		$data['scripts'][] = array( 
			'id' => 'geodir-leaflet-providers-script',
			'src' => GD_GOOGLE_MAPS_PLUGIN_URL . 'assets/js/leaflet-providers' . $suffix . '.js?ver=' . GD_GOOGLE_MAPS_VERSION,
		);

		return $data;
	}
}
