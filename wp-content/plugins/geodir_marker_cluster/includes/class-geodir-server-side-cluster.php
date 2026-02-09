<?php
/**
 * Script used to do server side clustering
 *
 * @package GeoDirectory_Marker_Cluster
 * @subpackage GeoDirectory_Marker_Cluster/includes
 * @author     GeoDirectory <info@wpgeodirectory.com>
 * @since 2.0.0
 */
if(!class_exists('GeoDir_Marker_Cluster_Serverside')) {

    class GeoDir_Marker_Cluster_Serverside
    {
        /**
         * Initialize the class and set its properties.
         *
         * @since    2.0.0
         */
        public function __construct() {
            add_action('init', array($this, 'init'));
        }

        function init() {
            if ((isset($_REQUEST['lat_ne']) && $_REQUEST['lat_ne']) || (isset($_REQUEST['my_lat']) && $_REQUEST['my_lat'])) {
                add_filter('geodir_rest_markers_query_where', array($this, 'geodir_cluster_marker_search'), 10, 1);
            }

            if (isset($_REQUEST['zl'])) {
                add_filter('geodir_rest_get_markers', array($this, 'geodir_cluster_markers_process'), 10, 1);
            }
        }

        /**
         * Filters the map query for server side clustering.
         *
         * Alters the query to limit the search area to the bounds of the map view.
         *
         * @since 1.1.1
         * @param string $search The where query string for marker search.
         * @package GeoDirectory_Marker_Cluster
         * @return string $search The where query string for marker search.
         */
        public function geodir_cluster_marker_search($search) {
            if (geodir_get_option('marker_cluster_type','client')!='server') {
                return $search;
            }

            if (isset($_REQUEST['my_lat']) && $_REQUEST['my_lat']) {
                $my_lat = filter_var($_REQUEST['my_lat'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
                $my_lon = filter_var($_REQUEST['my_lon'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);

                if (geodir_get_option('geodir_near_me_dist') != '') {
                    $distance_in_miles = geodir_get_option('geodir_near_me_dist');
                } else {
                    $distance_in_miles = 50;
                }

                $data = geodir_mc_bounding_box($my_lat, $my_lon, $distance_in_miles);

                $lat_sw = filter_var($data[0], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
                $lat_ne = filter_var($data[1], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
                $lon_sw = filter_var($data[2], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
                $lon_ne = filter_var($data[3], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
            } else {

                $lat_sw = filter_var($_REQUEST['lat_sw'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
                $lat_ne = filter_var($_REQUEST['lat_ne'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
                $lon_sw = filter_var($_REQUEST['lon_sw'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
                $lon_ne = filter_var($_REQUEST['lon_ne'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
            }


            $lon_not = '';
            //if the corners span more than half the world

            if ($lon_ne > 0 && $lon_sw > 0 && $lon_ne < $lon_sw) {
                $lon_not = 'not';
            } elseif ($lon_ne < 0 && $lon_sw < 0 && $lon_ne < $lon_sw) {
                $lon_not = 'not';
            } elseif ($lon_ne < 0 && $lon_sw > 0 && ($lon_ne + 360 - $lon_sw) > 180) {
                $lon_not = 'not';
            } elseif ($lon_ne < 0 && $lon_sw > 0 && abs($lon_ne) + abs($lon_sw) > 180) {
                $lon_not = 'not';
            }
            //elseif($lon_ne>0 && $lon_sw<0 && ($lon_sw+360-$lon_ne)<180){$lon_not = 'not';}
            //print_r($_REQUEST);
            if ($lon_ne == 180 && $lon_sw == -180) {
                return $search;
            }

            $search .= " AND pd.latitude between least($lat_sw,$lat_ne) and greatest($lat_sw,$lat_ne)  AND pd.longitude $lon_not between least($lon_sw,$lon_ne) and greatest($lon_sw,$lon_ne)";

            return $search;
        }

        /**
         * Filters the map marker array and return them as a cluster array.
         *
         * @since 1.1.1
         * @param array $markers The array of markers found.
         * @package GeoDirectory_Marker_Cluster
         * @return array $clustered
         */
        function geodir_cluster_markers_process($markers) {

            if (!is_array($markers) || geodir_get_option('marker_cluster_type','client')!='server') {
                return $markers;
            }
            $time = microtime(true);

            $distance = geodir_get_option('marker_cluster_size');
            $zoom = (isset($_REQUEST['zl']) && $_REQUEST['zl']) ? esc_attr($_REQUEST['zl']) : 1;
            $max_zoom = geodir_get_option('marker_cluster_zoom');

            if (isset($_REQUEST['zl']) && isset($_REQUEST['gd_map_h']) && isset($_REQUEST['gd_map_w'])) {
                $bounds_markers = geodir_get_cluster_bounds($markers);
//                print_r($bounds_markers);

                if (!empty($bounds_markers)) {
                    $mapDim = array(
                        'h' => filter_var($_REQUEST['gd_map_h'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION),
                        'w' => filter_var($_REQUEST['gd_map_w'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION)
                    );
                    $zoom = geodir_getBoundsZoomLevel($bounds_markers, $mapDim);
                }
            }

            // if max zoom for custer is reached then bail
//            echo $zoom.'###'.$max_zoom;
            if ($zoom >= $max_zoom) {
                return $markers;
            }
            //print_r( $markers );

            $clustered = array();
            /* Loop until all markers have been compared. */
            $distance = (10000000 >> $zoom) / 100000;
            while (count($markers)) {
                $marker = array_pop($markers);
                $cluster = array();
                $max_lat = '';
                $max_lon = '';
                $min_lat = '';
                $min_lon = '';

                /* Compare against all markers which are left. */
                foreach ($markers as $key => $target) {
                    $pixels = abs($marker['lt'] - $target['lt']) + abs($marker['ln'] - $target['ln']);

                    if (!$max_lat) {
                        $max_lat = $marker['lt'];
                    } elseif ($max_lat < $marker['lt']) {
                        $max_lat = $marker['lt'];
                    }
                    if (!$min_lat) {
                        $min_lat = $marker['lt'];
                    } elseif ($min_lat > $marker['lt']) {
                        $min_lat = $marker['lt'];
                    }
                    if (!$max_lon) {
                        $max_lon = $marker['ln'];
                    } elseif ($max_lon > $marker['ln']) {
                        $max_lon = $marker['ln'];
                    }
                    if (!$min_lon) {
                        $min_lon = $marker['ln'];
                    } elseif ($min_lon < $marker['ln']) {
                        $min_lon = $marker['ln'];
                    }

                    /* If two markers are closer than given distance remove */
                    /* target marker from array and add it to cluster.      */
                    if ($distance > $pixels) {
                        unset($markers[$key]);

                        $cluster[] = $target;
                    }
                }

                /* If a marker has been added to cluster, add also the one  */
                /* we were comparing to and remove the original from array. */
                if (count($cluster) > 0) {
                    $cluster[] = $marker;

                    $c_count = count($cluster);

                    $max_lat = '';
                    $max_lon = '';
                    $min_lat = '';
                    $min_lon = '';

                    $num_coords = count($cluster);

                    $X = 0.0;
                    $Y = 0.0;
                    $Z = 0.0;

                    foreach ($cluster as $coord) {
                        //print_r( $coord );
                        $lat = $coord['lt'] * pi() / 180;
                        $lon = $coord['ln'] * pi() / 180;

                        $a = cos($lat) * cos($lon);
                        $b = cos($lat) * sin($lon);
                        $c = sin($lat);

                        $X += $a;
                        $Y += $b;
                        $Z += $c;

                        if (!$max_lat) {
                            $max_lat = $coord['lt'];
                        } elseif ($max_lat < $coord['lt']) {
                            $max_lat = $coord['lt'];
                        }
                        if (!$min_lat) {
                            $min_lat = $coord['lt'];
                        } elseif ($min_lat > $coord['lt']) {
                            $min_lat = $coord['lt'];
                        }
                        if (!$max_lon) {
                            $max_lon = $coord['ln'];
                        } elseif ($max_lon > $coord['ln']) {
                            $max_lon = $coord['ln'];
                        }
                        if (!$min_lon) {
                            $min_lon = $coord['ln'];
                        } elseif ($min_lon < $coord['ln']) {
                            $min_lon = $coord['ln'];
                        }
                    }

                    $X /= $num_coords;
                    $Y /= $num_coords;
                    $Z /= $num_coords;

                    $lon = atan2($Y, $X);
                    $hyp = sqrt($X * $X + $Y * $Y);
                    $lat = atan2($Z, $hyp);

                    $center = array($lat * 180 / pi(), $lon * 180 / pi());

                    $clust = new stdClass();
                    //$clust->default_category = '';
                    //$clust->gd_placecategory = '';
//                    print_r( $cluster[0] );
                    $clust->t = $c_count;
                    $clust->m = $cluster[0]['m'];
                    $clust->lt = $center[0];
                    $clust->ln = $center[1];
                    $clust->cs = $c_count . '_' . $max_lat . '_' . $max_lon . '_' . $min_lat . '_' . $min_lon;

                    $clustered[] = $clust;
                } else {
                    $clustered[] = $marker;
                }
            }

            return $clustered;
        }

    }
}

new GeoDir_Marker_Cluster_Serverside();
