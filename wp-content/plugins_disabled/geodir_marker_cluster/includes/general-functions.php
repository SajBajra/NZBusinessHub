<?php
/**
 * Find the X value of an X/Y axis point reference for a given longitude.
 *
 * @since 1.1.1
 * @param float $lon The longitude number to be processed.
 * @package GeoDirectory_Marker_Cluster
 * @return float
 */
function geodir_lonToX($lon) {
    $offset = 268435456;
    $radius = 85445659.4471; /* $offset / pi() */
    return round($offset + $radius * $lon * pi() / 180);
}

/**
 * Find the Y value of an X/Y axis point reference for a given longitude.
 *
 * @since 1.1.1
 * @param float $lat The latitude number to be processed.
 * @package GeoDirectory_Marker_Cluster
 * @return float
 */
function geodir_latToY($lat) {
    $offset = 268435456;
    $radius = 85445659.4471; /* $offset / pi() */
    return round($offset - $radius *
        log((1 + sin($lat * pi() / 180)) /
            (1 - sin($lat * pi() / 180))) / 2);
}

/**
 * Find the distance in pixels between two GPS points.
 *
 * @since 1.1.1
 * @param float $lat1 The latitude number for the first point.
 * @param float $lon1 The longitude number for the first point.
 * @param float $lat2 The latitude number for the second point.
 * @param float $lon2 The longitude number for the second point.
 * @param int $zoom The map zoom level 1-21.
 * @package GeoDirectory_Marker_Cluster
 * @return float
 */
function geodir_pixelDistance($lat1, $lon1, $lat2, $lon2, $zoom) {
    $x1 = geodir_lonToX($lon1);
    $y1 = geodir_latToY($lat1);

    $x2 = geodir_lonToX($lon2);
    $y2 = geodir_latToY($lat2);

    return sqrt(pow(($x1-$x2),2) + pow(($y1-$y2),2)) >> (21 - $zoom);
}

function geodir_pixelDistance2($lat1, $lon1, $lat2, $lon2, $zoom) {
    $x1 = $lon1*10000000; //This is what I did to compensate for using lat/lon values instead of pixels.
    $y1 = $lat1*10000000;
    $x2 = $lon2*10000000;
    $y2 = $lat2*10000000;

    return ($x1-$x2) + ($y1-$y2) >> (21 - $zoom);
}


/**
 * Get a center latitude,longitude from an array of like geopoints
 *
 * @param array data 2 dimensional array of latitudes and longitudes
 * For Example:
 * $data = array
 * (
 *   0 = > array(45.849382, 76.322333),
 *   1 = > array(45.843543, 75.324143),
 *   2 = > array(45.765744, 76.543223),
 *   3 = > array(45.784234, 74.542335)
 * );
 * @since 1.1.1
 * @package GeoDirectory_Marker_Cluster
 * @return array
 */
function geodir_GetCenterFromDegrees($data)
{
    if (!is_array($data)) return FALSE;

    $num_coords = count($data);

    $X = 0.0;
    $Y = 0.0;
    $Z = 0.0;

    foreach ($data as $coord)
    {
        $lat = $coord->post_latitude * pi() / 180;
        $lon = $coord->post_longitude * pi() / 180;

        $a = cos($lat) * cos($lon);
        $b = cos($lat) * sin($lon);
        $c = sin($lat);

        $X += $a;
        $Y += $b;
        $Z += $c;
    }

    $X /= $num_coords;
    $Y /= $num_coords;
    $Z /= $num_coords;

    $lon = atan2($Y, $X);
    $hyp = sqrt($X * $X + $Y * $Y);
    $lat = atan2($Z, $hyp);

    return array($lat * 180 / pi(), $lon * 180 / pi());
}

/**
 * Find the bounds of the given cluster data.
 *
 * @since 1.1.1
 * @param array $data The array of cluster data.
 * @package GeoDirectory_Marker_Cluster
 * @return array
 */
function geodir_get_cluster_bounds($data) {
    if (!is_array($data)) return array();

    $max_lat = '';
    $max_lon = '';
    $min_lat = '';
    $min_lon = '';

    foreach ($data as $target) {
        if(!$max_lat){$max_lat = $target['lt'];}elseif($max_lat<$target['lt']){$max_lat = $target['lt'];}
        if(!$min_lat){$min_lat = $target['lt'];}elseif($min_lat>$target['lt']){$min_lat = $target['lt'];}
        if(!$max_lon){$max_lon = $target['ln'];}elseif($max_lon>$target['ln']){$max_lon = $target['ln'];}
        if(!$min_lon){$min_lon = $target['ln'];}elseif($min_lon<$target['ln']){$min_lon = $target['ln'];}
    }

    return array($max_lat,$max_lon,$min_lat,$min_lon);
}

/**
 * Convert latitude to radian value.
 *
 * @since 1.1.1
 * @param float $lat The latitude value to convert.
 * @return float The latitude value in radians.
 * @package GeoDirectory_Marker_Cluster
 */
function geodir_latRad($lat) {
    $sin = sin($lat * pi() / 180);
    $radX2 = log((1 + $sin) / (1 - $sin)) / 2;
    return max(min($radX2, pi()), -pi()) / 2;
}

/**
 * Calculate the maximum zoom level for two given points on a map.
 *
 * @since 1.1.1
 * @param float $mapPx The px value of the given point to check.
 * @param float $worldPx The px value of the world in pixels.
 * @param float $fraction The fraction distance between the two points to check.
 * @return int The max zoom level needed to view the point on a map 1-21.
 * @package GeoDirectory_Marker_Cluster
 */
function geodir_cluster_zoom($mapPx, $worldPx, $fraction) {
    if( $fraction=='0'){ $fraction=1;}
    return floor(log($mapPx / $worldPx / $fraction) / M_LN2);
}

/**
 * Calculate the maximum zoom level on a map given the bounds array and the map size in pixels.
 *
 * @since 1.1.1
 * @param array $bounds The array of 4 points, the lat/lon of the north east and the lat/long of the south west.
 * @param array $mapDim An array of the map height and width in px value.
 * @return int The max zoom level needed to view the points on a map 1-21.
 * @package GeoDirectory_Marker_Cluster
 */
function geodir_getBoundsZoomLevel($bounds, $mapDim) {
    $world_dim = array( 'height'=> 256, 'width'=> 256 );
    $zoom_max = 21;

    $ne = array();
    $sw = array();

    $ne['lat'] = max($bounds[0], $bounds[2]);
    $ne['lon'] = max($bounds[1], $bounds[3]);
    $sw['lat'] = min($bounds[0], $bounds[2]);
    $sw['lon'] = min($bounds[1], $bounds[3]);

    if ($ne['lat'] == $sw['lat'] && $ne['lon'] == $sw['lon']) {
        return 20;
    }

    $latFraction = (geodir_latRad($ne['lat']) - geodir_latRad($sw['lat'])) / pi();

    $lngDiff = $ne['lon'] - $sw['lon'];
    $lngFraction = (($lngDiff < 0) ? ($lngDiff + 360) : $lngDiff) / 360;

    $latZoom = geodir_cluster_zoom($mapDim['h'], $world_dim['height'], $latFraction);
    $lngZoom = geodir_cluster_zoom($mapDim['w'], $world_dim['width'], $lngFraction);

    return min($latZoom, $lngZoom, $zoom_max);
}

function geodir_mc_bounding_box($lat_degrees,$lon_degrees,$distance_in_miles) {
    $radius = 3963.1; // of earth in miles

    // bearings - FIX
    $due_north = deg2rad(0);
    $due_south = deg2rad(180);
    $due_east = deg2rad(90);
    $due_west = deg2rad(270);

    // convert latitude and longitude into radians
    $lat_r = deg2rad($lat_degrees);
    $lon_r = deg2rad($lon_degrees);

    // find the northmost, southmost, eastmost and westmost corners $distance_in_miles away
    // original formula from
    // http://www.movable-type.co.uk/scripts/latlong.html

    $northmost  = asin(sin($lat_r) * cos($distance_in_miles/$radius) + cos($lat_r) * sin ($distance_in_miles/$radius) * cos($due_north));
    $southmost  = asin(sin($lat_r) * cos($distance_in_miles/$radius) + cos($lat_r) * sin ($distance_in_miles/$radius) * cos($due_south));

    $eastmost = $lon_r + atan2(sin($due_east)*sin($distance_in_miles/$radius)*cos($lat_r),cos($distance_in_miles/$radius)-sin($lat_r)*sin($lat_r));
    $westmost = $lon_r + atan2(sin($due_west)*sin($distance_in_miles/$radius)*cos($lat_r),cos($distance_in_miles/$radius)-sin($lat_r)*sin($lat_r));

    $northmost = rad2deg($northmost);
    $southmost = rad2deg($southmost);
    $eastmost = rad2deg($eastmost);
    $westmost = rad2deg($westmost);

    // sort the lat and long so that we can use them for a between query
    if ($northmost > $southmost) {
        $lat1 = $southmost;
        $lat2 = $northmost;
    } else {
        $lat1 = $northmost;
        $lat2 = $southmost;
    }

    if ($eastmost > $westmost) {
        $lon1 = $westmost;
        $lon2 = $eastmost;
    } else {
        $lon1 = $eastmost;
        $lon2 = $westmost;
    }

    return array($lat1,$lat2,$lon1,$lon2);
}