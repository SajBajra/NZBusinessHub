<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://wpgeodirectory.com/
 * @since      2.0.0
 *
 * @package    GeoDirectory_Marker_Cluster
 * @subpackage GeoDirectory_Marker_Cluster/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    GeoDirectory_Marker_Cluster
 * @subpackage GeoDirectory_Marker_Cluster/public
 * @author     GeoDirectory <info@wpgeodirectory.com>
 */
class GeoDir_Marker_Cluster_Public {

	public function __construct() {

	}

	public function enqueue_scripts() {
		if ( function_exists( 'geodir_load_scripts_on_call' ) && geodir_load_scripts_on_call() ) {
			return;
		}

		$this->load_scripts();
	}

	public function load_scripts( $args = array() ) {
		if ( geodir_lazy_load_map() || ! geodir_get_option( 'marker_cluster_type', 'client' ) ) {
			return;
		}

		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		$geodir_map_name = GeoDir_Maps::active_map();
		if ( ! in_array($geodir_map_name, array( 'none', 'auto', 'google', 'osm' ) ) ) {
			$geodir_map_name = 'auto';
		}

		$dependency = array( 'jquery' );

		if ( function_exists( 'geodir_load_scripts_on_call' ) && geodir_load_scripts_on_call() ) {
			// Don't load marker cluster for post map.
			if ( ! empty( $args['map_type'] ) && ( $args['map_type'] == 'post' || empty( $args['marker_cluster'] ) ) ) {
				return;
			}

			if ( wp_script_is( 'gdcluster-js', 'enqueued' ) || wp_script_is( 'gdcluster-leaflet-js', 'enqueued' ) ) {
				return;
			}

			$dependency[] = 'geodir-google-maps';
		} else {
			wp_enqueue_script( 'jquery' );
		}

		if ( in_array( $geodir_map_name, array( 'auto', 'google' ) ) ) {
			if ( geodir_get_option( 'marker_cluster_type', 'client' ) == 'client' ) {
				wp_register_script( 'gdcluster-js', GEODIR_MARKER_PLUGINDIR_URL . '/assets/js/marker_cluster' . $suffix . '.js', $dependency );
			} else {
				wp_register_script( 'gdcluster-js', GEODIR_MARKER_PLUGINDIR_URL . '/assets/js/marker_cluster_ss' . $suffix . '.js', $dependency );
			}
			wp_enqueue_script( 'gdcluster-js' );

			wp_register_script( 'gdcluster-script', GEODIR_MARKER_PLUGINDIR_URL.'/assets/js/cluster_script' . $suffix . '.js',array( 'jquery', 'gdcluster-js' ), '1', true );
			wp_enqueue_script( 'gdcluster-script' );
		} else if ( $geodir_map_name == 'osm' ) {
			wp_register_style( 'gdcluster-leaflet-css', GEODIR_MARKER_PLUGINDIR_URL . '/assets/js/leaflet/leaflet.markercluster.css', array(), GEODIR_MARKERCLUSTER_VERSION );
			wp_enqueue_style( 'gdcluster-leaflet-css' );

			wp_register_script( 'gdcluster-leaflet-js', GEODIR_MARKER_PLUGINDIR_URL . '/assets/js/leaflet/leaflet.markercluster.min.js', array( 'jquery', 'geodir-leaflet' ), GEODIR_MARKERCLUSTER_VERSION);
			wp_enqueue_script( 'gdcluster-leaflet-js' );
		}
	}

    public function footer_script(){
        $geodir_map_name = geodir_get_option('geodir_load_map', 'google');
        if ( ! in_array( $geodir_map_name, array( 'none', 'auto', 'google', 'osm' ) ) ) {
            $geodir_map_name = 'auto';
        }

        if ( ! geodir_lazy_load_map() && in_array( $geodir_map_name, array( 'auto' ) ) && wp_script_is( 'geodirectory-googlemap-script', 'done' ) ) {
            ?>
            <script type="text/javascript">
                if (!(window.google && typeof google.maps !== 'undefined')) {
                    document.write('<' + 'link id="gdcluster-leaflet-css" media="all" type="text/css" href="<?php echo GEODIR_MARKER_PLUGINDIR_URL;?>/assets/js/leaflet/leaflet.markercluster.css?ver=<?php echo GEODIR_MARKERCLUSTER_VERSION;?>" rel="stylesheet"' + '>');
                    document.write('<' + 'script id="gdcluster-leaflet-js" script src="<?php echo GEODIR_MARKER_PLUGINDIR_URL;?>/assets/js/leaflet/leaflet.markercluster.min.js?ver=<?php echo GEODIR_MARKERCLUSTER_VERSION;?>"><' + '/script>');
                }
            </script>
            <?php
        }
    }

    public function geodir_params($vars){

        $imagePath = GEODIR_MARKER_PLUGINDIR_URL . '/assets/images/m';

        $vars['marker_cluster_size'] = geodir_get_option('marker_cluster_size', 60);
        $vars['marker_cluster_zoom'] = geodir_get_option('marker_cluster_zoom', 15);
        $vars['imagePath'] = apply_filters('geodir_marker_cluster_image_path', $imagePath);

	    return $vars;
    }

    /**
     * Add settings to the map options as needed for clustering.
     *
     * @since 2.0.0
     * @package GeoDirectory_Marker_Cluster
     */
    public function update_map_options($map_options)
    {

		if(isset($map_options['marker_cluster']) && (int)$map_options['marker_cluster'] != 1){
            $map_options['marker_cluster'] = false;
            $map_options['marker_cluster_server'] = false;
            return $map_options;
        }

        $cluster_type = geodir_get_option('marker_cluster_type', 'client');
        if($cluster_type=='client'){
            $map_options['marker_cluster'] = true;
            $map_options['marker_cluster_server'] = false;
        }elseif($cluster_type=='server'){
            $map_options['marker_cluster'] = true;
            $map_options['marker_cluster_server'] = true;
            if (!empty($map_options['autozoom'])) {
                $map_options['enable_marker_cluster_no_reposition'] = false;
            } else {
                $map_options['enable_marker_cluster_no_reposition'] = true;
            }
        }else{
            $map_options['marker_cluster'] = false;
            $map_options['marker_cluster_server'] = false;
        }

        return $map_options;
    }

	public function map_api_google_data( $data ) {
		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		if ( geodir_get_option( 'marker_cluster_type', 'client' ) == 'client' ) {
			$data['scripts'][] = array( 
				'id' => 'geodir-gmap-cluster-script',
				'src' => GEODIR_MARKER_PLUGINDIR_URL . '/assets/js/marker_cluster' . $suffix . '.js?ver=' . GEODIR_MARKERCLUSTER_VERSION,
			);
		} else {
			$data['scripts'][] = array( 
				'id' => 'geodir-gmap-cluster-script',
				'src' => GEODIR_MARKER_PLUGINDIR_URL . '/assets/js/marker_cluster_ss' . $suffix . '.js?ver=' . GEODIR_MARKERCLUSTER_VERSION,
			);
		}

		$data['scripts'][] = array( 
			'id' => 'gdcluster-script',
			'src' => GEODIR_MARKER_PLUGINDIR_URL.'/assets/js/cluster_script' . $suffix . '.js?ver=' . GEODIR_MARKERCLUSTER_VERSION,
		);

		return $data;
	}

	public function map_api_osm_data( $data ) {
		$data['styles'][] = array( 
			'id' => 'gdcluster-leaflet-css',
			'src' => GEODIR_MARKER_PLUGINDIR_URL . '/assets/js/leaflet/leaflet.markercluster.css?ver=' . GEODIR_MARKERCLUSTER_VERSION,
		);

		$data['scripts'][] = array( 
			'id' => 'gdcluster-leaflet-script',
			'src' => GEODIR_MARKER_PLUGINDIR_URL . '/assets/js/leaflet/leaflet.markercluster.min.js?ver=' . GEODIR_MARKERCLUSTER_VERSION,
		);

		return $data;
	}

	/**
	 * Filters marker cluster type option.
	 *
	 * @since 2.3.2
	 *
	 * @param string $value Saved marker cluster type value.
	 * @param string $key Marker cluster type option key.
	 * @param string $default Marker cluster type default value.
	 * @return string Filtered value.
	 */
	public function filter_marker_cluster_type( $value, $key, $default ) {
		global $geodir_options;

		if ( $value == 'server' && ! empty( $geodir_options['advs_ajax_search'] ) && ! empty( $geodir_options['design_style'] ) ) {
			 $value = 'client';
		}

		return $value;
	}
}
