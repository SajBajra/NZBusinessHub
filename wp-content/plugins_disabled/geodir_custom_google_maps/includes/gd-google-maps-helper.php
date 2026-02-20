<?php

/**
 * Get custom open street map style feature type.
 *
 * @since 2.0.0
 *
 * @return array $feature_type
 */
function get_gd_cgm_feature_types() {

    $feature_type = array(
        'all' => 'all',
        'administrative' => 'administrative',
        'administrative.country' => 'administrative.country',
        'administrative.land_parcel' => 'administrative.land_parcel',
        'administrative.locality' => 'administrative.locality',
        'administrative.neighborhood' => 'administrative.neighborhood',
        'administrative.province' => 'administrative.province',
        'landscape' => 'landscape',
        'landscape.man_made' => 'landscape.man_made',
        'landscape.natural' => 'landscape.natural',
        'landscape.natural.landcover' => 'landscape.natural.landcover',
        'landscape.natural.terrain' => 'landscape.natural.terrain',
        'poi' => 'poi',
        'poi.attraction' => 'poi.attraction',
        'poi.business' => 'poi.business',
        'poi.government' => 'poi.government',
        'poi.medical' => 'poi.medical',
        'poi.park' => 'poi.park',
        'poi.place_of_worship' => 'poi.place_of_worship',
        'poi.school' => 'poi.school',
        'poi.sports_complex' => 'poi.sports_complex',
        'road' => 'road',
        'road.arterial' => 'road.arterial',
        'road.highway' => 'road.highway',
        'road.highway.controlled_access' => 'road.highway.controlled_access',
        'road.local' => 'road.local',
        'transit' => 'transit',
        'transit.line' => 'transit.line',
        'transit.station' => 'transit.station',
        'transit.station.airport' => 'transit.station.airport',
        'transit.station.bus' => 'transit.station.bus',
        'transit.station.rail' => 'transit.station.rail',
        'water' => 'water',
    );

    return $feature_type;

}

/**
 * Get custom open street map style elements type.
 *
 * @since 2.0.0
 *
 * @return array $elements_types
 */
function get_gd_cgm_elements_types() {
    $elements_types = array(
        '' => __( 'Select', 'geodir-custom-google-maps' ),
        'all' => 'all',
        'geometry' => 'geometry',
        'geometry.fill' => 'geometry.fill',
        'geometry.stroke' => 'geometry.stroke',
        'labels' => 'labels',
        'labels.icon' => 'labels.icon',
        'labels.text' => 'labels.text',
        'labels.text.fill' => 'labels.text.fill',
        'labels.text.stroke' => 'labels.text.stroke',
    );

    return $elements_types;
}

/**
 * Get custom google map option key.
 *
 * @since 2.0.0
 *
 * @param string $id Custom Manage fields id.
 * @return string $option_key
 */
function get_gd_cgm_option_key( $id ) {

    $option_key ='';

    switch ($id) {
        case "gd_custom_map_home_checkbox":
            $option_key ='gd_custom_maps_home_style';
            break;
        case "gd_custom_map_listing_checkbox":
            $option_key ='gd_custom_maps_listing_style';
            break;
        case "gd_custom_map_detail_checkbox":
            $option_key ='gd_custom_maps_details_style';
            break;
		case "gd_custom_map_add_listing_checkbox":
            $option_key ='gd_custom_maps_add_listing_style';
            break;
        default:
            $option_key ='';
    }

    return $option_key;

}

/**
 * Get custom open street map option key.
 *
 * @since 2.0.0
 *
 * @param string $id Custom Manage fields id.
 * @return string $option_key
 */
function get_gd_cgm_osm_option_key( $id ) {

    $option_key ='';

    switch ($id) {
        case "gd_custom_map_home_checkbox":
            $option_key ='gd_custom_osm_home_options';
            break;
        case "gd_custom_map_listing_checkbox":
            $option_key ='gd_custom_osm_listing_options';
            break;
        case "gd_custom_map_detail_checkbox":
            $option_key ='gd_custom_osm_detail_options';
            break;
		case "gd_custom_map_add_listing_checkbox":
            $option_key ='gd_custom_osm_add_listing_options';
            break;
        default:
            $option_key ='';
    }

    return $option_key;

}

/**
 * Get open street map base layers.
 *
 * Get osm base url, default values.
 *
 * @since 2.0.0
 *
 * @param string $fields_id Get google map fields id.
 * @return array $baselayers Base layers.
 */
function get_gd_cgm_osm_base_layers($fields_id) {

    $get_osm_option_key = get_gd_cgm_osm_option_key( $fields_id );
    $get_osm_option_val = maybe_unserialize( get_option($get_osm_option_key) );

    $get_base_value = !empty( $get_osm_option_val['base_value'] ) ? $get_osm_option_val['base_value'] :'OpenStreetMap.Mapnik';

    $baselayers = array(
        'OpenStreetMap.Mapnik' => array(
            'URL' => 'https://tile.openstreetmap.org/{z}/{x}/{y}.png',
            'maxZoom' => '19',
            'default' => !empty( 'OpenStreetMap.Mapnik' == $get_base_value ) ? 'true' : 'false',
            'time' => '',
            'subdomains' => '',
        ),
        'OpenStreetMap.BlackAndWhite' => array(
            'URL' => 'https://{s}.tiles.wmflabs.org/bw-mapnik/{z}/{x}/{y}.png',
            'maxZoom' => '18',
            'default' => !empty( 'OpenStreetMap.BlackAndWhite' == $get_base_value ) ? 'true' : 'false',
            'time' => '',
            'subdomains' => '',
        ),
        'OpenStreetMap.DE' => array(
            'URL' => 'https://tile.openstreetmap.de/{z}/{x}/{y}.png',
            'maxZoom' => '18',
            'default' => !empty( 'OpenStreetMap.DE' == $get_base_value ) ? 'true' : 'false',
            'time' => '',
            'subdomains' => '',
        ),
        'OpenStreetMap.France' => array(
            'URL' => 'https://{s}.tile.openstreetmap.fr/osmfr/{z}/{x}/{y}.png',
            'maxZoom' => '20',
            'default' => !empty( 'OpenStreetMap.France' == $get_base_value ) ? 'true' : 'false',
            'time' => '',
            'subdomains' => '',
        ),
        'OpenStreetMap.HOT' => array(
            'URL' => 'https://{s}.tile.openstreetmap.fr/hot/{z}/{x}/{y}.png',
            'maxZoom' => '19',
            'default' => !empty( 'OpenStreetMap.HOT' == $get_base_value ) ? 'true' : 'false',
            'time' => '',
            'subdomains' => '',
		),
		'MapTilesAPI.OSMEnglish' => array(
			'URL' => 'https://maptiles.p.rapidapi.com/{variant}/{z}/{x}/{y}.png?rapidapi-key={apiKey}',
			'maxZoom' => '19',
			'default' => !empty( 'MapTilesAPI.OSMEnglish' == $get_base_value ) ? 'true' : 'false',
			'time' => '',
			'attribution' => '&copy; <a href="http://www.maptilesapi.com/">MapTiles API</a>, {attribution.OpenStreetMap}',
			'variant' => 'en/map/v1'
		),
		'MapTilesAPI.OSMFrancais' => array(
			'URL' => 'https://maptiles.p.rapidapi.com/{variant}/{z}/{x}/{y}.png?rapidapi-key={apiKey}',
			'maxZoom' => '19',
			'default' => !empty( 'MapTilesAPI.OSMFrancais' == $get_base_value ) ? 'true' : 'false',
			'time' => '',
			'attribution' => '&copy; <a href="http://www.maptilesapi.com/">MapTiles API</a>, {attribution.OpenStreetMap}',
			'variant' => 'fr/map/v1'
		),
		'MapTilesAPI.OSMEspagnol' => array(
			'URL' => 'https://maptiles.p.rapidapi.com/{variant}/{z}/{x}/{y}.png?rapidapi-key={apiKey}',
			'maxZoom' => '19',
			'default' => !empty( 'MapTilesAPI.OSMEspagnol' == $get_base_value ) ? 'true' : 'false',
			'time' => '',
			'attribution' => '&copy; <a href="http://www.maptilesapi.com/">MapTiles API</a>, {attribution.OpenStreetMap}',
			'variant' => 'es/map/v1'
		),
        'OpenTopoMap' => array(
            'URL' => 'https://{s}.tile.opentopomap.org/{z}/{x}/{y}.png',
            'maxZoom' => '17',
            'default' => !empty( 'OpenTopoMap' == $get_base_value ) ? 'true' : 'false',
            'time' => '',
            'subdomains' => '',
        ),
		'Stadia.AlidadeSmooth' => array(
			'URL' => 'https://tiles.stadiamaps.com/tiles/{variant}/{z}/{x}/{y}{r}.{ext}',
			'minZoom' => '0',
			'maxZoom' => '20',
			'default' => !empty( 'Stadia.AlidadeSmooth' == $get_base_value ) ? 'true' : 'false',
			'time' => '',
			'attribution' => '&copy; <a href="https://www.stadiamaps.com/" target="_blank">Stadia Maps</a> &copy; <a href="https://openmaptiles.org/" target="_blank">OpenMapTiles</a> {attribution.OpenStreetMap}',
			'variant' => 'alidade_smooth',
			'ext' => 'png'
		),
		'Stadia.AlidadeSmoothDark' => array(
			'URL' => 'https://tiles.stadiamaps.com/tiles/{variant}/{z}/{x}/{y}{r}.{ext}',
			'minZoom' => '0',
			'maxZoom' => '20',
			'default' => !empty( 'Stadia.AlidadeSmoothDark' == $get_base_value ) ? 'true' : 'false',
			'time' => '',
			'attribution' => '&copy; <a href="https://www.stadiamaps.com/" target="_blank">Stadia Maps</a> &copy; <a href="https://openmaptiles.org/" target="_blank">OpenMapTiles</a> {attribution.OpenStreetMap}',
			'variant' => 'alidade_smooth_dark',
			'ext' => 'png'
		),
		'Stadia.OSMBright' => array(
			'URL' => 'https://tiles.stadiamaps.com/tiles/{variant}/{z}/{x}/{y}{r}.{ext}',
			'minZoom' => '0',
			'maxZoom' => '20',
			'default' => !empty( 'Stadia.OSMBright' == $get_base_value ) ? 'true' : 'false',
			'time' => '',
			'attribution' => '&copy; <a href="https://www.stadiamaps.com/" target="_blank">Stadia Maps</a> &copy; <a href="https://openmaptiles.org/" target="_blank">OpenMapTiles</a> {attribution.OpenStreetMap}',
			'variant' => 'osm_bright',
			'ext' => 'png'
		),
		'Stadia.Outdoors' => array(
			'URL' => 'https://tiles.stadiamaps.com/tiles/{variant}/{z}/{x}/{y}{r}.{ext}',
			'minZoom' => '0',
			'maxZoom' => '20',
			'default' => !empty( 'Stadia.Outdoors' == $get_base_value ) ? 'true' : 'false',
			'time' => '',
			'attribution' => '&copy; <a href="https://www.stadiamaps.com/" target="_blank">Stadia Maps</a> &copy; <a href="https://openmaptiles.org/" target="_blank">OpenMapTiles</a> {attribution.OpenStreetMap}',
			'variant' => 'outdoors',
			'ext' => 'png'
		),
		'Stadia.StamenToner' => array(
			'URL' => 'https://tiles.stadiamaps.com/tiles/{variant}/{z}/{x}/{y}{r}.{ext}',
			'minZoom' => '0',
			'maxZoom' => '20',
			'default' => !empty( 'Stadia.StamenToner' == $get_base_value ) ? 'true' : 'false',
			'time' => '',
			'attribution' => '&copy; <a href="https://www.stadiamaps.com/" target="_blank">Stadia Maps</a> ' .
							'&copy; <a href="https://www.stamen.com/" target="_blank">Stamen Design</a> ' .
							'&copy; <a href="https://openmaptiles.org/" target="_blank">OpenMapTiles</a> ' .
							'{attribution.OpenStreetMap}',
			'variant' => 'stamen_toner',
			'ext' => 'png'
		),
		'Stadia.StamenTonerBackground' => array(
			'URL' => 'https://tiles.stadiamaps.com/tiles/{variant}/{z}/{x}/{y}{r}.{ext}',
			'minZoom' => '0',
			'maxZoom' => '20',
			'default' => !empty( 'Stadia.StamenTonerBackground' == $get_base_value ) ? 'true' : 'false',
			'time' => '',
			'attribution' => '&copy; <a href="https://www.stadiamaps.com/" target="_blank">Stadia Maps</a> ' .
							'&copy; <a href="https://www.stamen.com/" target="_blank">Stamen Design</a> ' .
							'&copy; <a href="https://openmaptiles.org/" target="_blank">OpenMapTiles</a> ' .
							'{attribution.OpenStreetMap}',
			'variant' => 'stamen_toner_background',
			'ext' => 'png'
		),
		'Stadia.StamenTonerLines' => array(
			'URL' => 'https://tiles.stadiamaps.com/tiles/{variant}/{z}/{x}/{y}{r}.{ext}',
			'minZoom' => '0',
			'maxZoom' => '20',
			'default' => !empty( 'Stadia.StamenTonerLines' == $get_base_value ) ? 'true' : 'false',
			'time' => '',
			'attribution' => '&copy; <a href="https://www.stadiamaps.com/" target="_blank">Stadia Maps</a> ' .
							'&copy; <a href="https://www.stamen.com/" target="_blank">Stamen Design</a> ' .
							'&copy; <a href="https://openmaptiles.org/" target="_blank">OpenMapTiles</a> ' .
							'{attribution.OpenStreetMap}',
			'variant' => 'stamen_toner_lines',
			'ext' => 'png'
		),
		'Stadia.StamenTonerLabels' => array(
			'URL' => 'https://tiles.stadiamaps.com/tiles/{variant}/{z}/{x}/{y}{r}.{ext}',
			'minZoom' => '0',
			'maxZoom' => '20',
			'default' => !empty( 'Stadia.StamenTonerLabels' == $get_base_value ) ? 'true' : 'false',
			'time' => '',
			'attribution' => '&copy; <a href="https://www.stadiamaps.com/" target="_blank">Stadia Maps</a> ' .
							'&copy; <a href="https://www.stamen.com/" target="_blank">Stamen Design</a> ' .
							'&copy; <a href="https://openmaptiles.org/" target="_blank">OpenMapTiles</a> ' .
							'{attribution.OpenStreetMap}',
			'variant' => 'stamen_toner_labels',
			'ext' => 'png'
		),
		'Stadia.StamenTonerLite' => array(
			'URL' => 'https://tiles.stadiamaps.com/tiles/{variant}/{z}/{x}/{y}{r}.{ext}',
			'minZoom' => '0',
			'maxZoom' => '20',
			'default' => !empty( 'Stadia.StamenTonerLite' == $get_base_value ) ? 'true' : 'false',
			'time' => '',
			'attribution' => '&copy; <a href="https://www.stadiamaps.com/" target="_blank">Stadia Maps</a> ' .
							'&copy; <a href="https://www.stamen.com/" target="_blank">Stamen Design</a> ' .
							'&copy; <a href="https://openmaptiles.org/" target="_blank">OpenMapTiles</a> ' .
							'{attribution.OpenStreetMap}',
			'variant' => 'stamen_toner_lite',
			'ext' => 'png'
		),
		'Stadia.StamenWatercolor' => array(
			'URL' => 'https://tiles.stadiamaps.com/tiles/{variant}/{z}/{x}/{y}{r}.{ext}',
			'minZoom' => '1',
			'maxZoom' => '16',
			'default' => !empty( 'Stadia.StamenWatercolor' == $get_base_value ) ? 'true' : 'false',
			'time' => '',
			'attribution' => '&copy; <a href="https://www.stadiamaps.com/" target="_blank">Stadia Maps</a> ' .
							'&copy; <a href="https://www.stamen.com/" target="_blank">Stamen Design</a> ' .
							'&copy; <a href="https://openmaptiles.org/" target="_blank">OpenMapTiles</a> ' .
							'{attribution.OpenStreetMap}',
			'variant' => 'stamen_watercolor',
			'ext' => 'jpg'
		),
		'Stadia.StamenTerrain' => array(
			'URL' => 'https://tiles.stadiamaps.com/tiles/{variant}/{z}/{x}/{y}{r}.{ext}',
			'minZoom' => '0',
			'maxZoom' => '18',
			'default' => !empty( 'Stadia.StamenTerrain' == $get_base_value ) ? 'true' : 'false',
			'time' => '',
			'attribution' => '&copy; <a href="https://www.stadiamaps.com/" target="_blank">Stadia Maps</a> ' .
							'&copy; <a href="https://www.stamen.com/" target="_blank">Stamen Design</a> ' .
							'&copy; <a href="https://openmaptiles.org/" target="_blank">OpenMapTiles</a> ' .
							'{attribution.OpenStreetMap}',
			'variant' => 'stamen_terrain',
			'ext' => 'png'
		),
		'Stadia.StamenTerrainBackground' => array(
			'URL' => 'https://tiles.stadiamaps.com/tiles/{variant}/{z}/{x}/{y}{r}.{ext}',
			'minZoom' => '0',
			'maxZoom' => '18',
			'default' => !empty( 'Stadia.StamenTerrainBackground' == $get_base_value ) ? 'true' : 'false',
			'time' => '',
			'attribution' => '&copy; <a href="https://www.stadiamaps.com/" target="_blank">Stadia Maps</a> ' .
							'&copy; <a href="https://www.stamen.com/" target="_blank">Stamen Design</a> ' .
							'&copy; <a href="https://openmaptiles.org/" target="_blank">OpenMapTiles</a> ' .
							'{attribution.OpenStreetMap}',
			'variant' => 'stamen_terrain_background',
			'ext' => 'png'
		),
		'Stadia.StamenTerrainLabels' => array(
			'URL' => 'https://tiles.stadiamaps.com/tiles/{variant}/{z}/{x}/{y}{r}.{ext}',
			'minZoom' => '0',
			'maxZoom' => '18',
			'default' => !empty( 'Stadia.StamenTerrainLabels' == $get_base_value ) ? 'true' : 'false',
			'time' => '',
			'attribution' => '&copy; <a href="https://www.stadiamaps.com/" target="_blank">Stadia Maps</a> ' .
							'&copy; <a href="https://www.stamen.com/" target="_blank">Stamen Design</a> ' .
							'&copy; <a href="https://openmaptiles.org/" target="_blank">OpenMapTiles</a> ' .
							'{attribution.OpenStreetMap}',
			'variant' => 'stamen_terrain_labels',
			'ext' => 'png'
		),
		'Stadia.StamenTerrainLines' => array(
			'URL' => 'https://tiles.stadiamaps.com/tiles/{variant}/{z}/{x}/{y}{r}.{ext}',
			'minZoom' => '0',
			'maxZoom' => '18',
			'default' => !empty( 'Stadia.StamenTerrainLines' == $get_base_value ) ? 'true' : 'false',
			'time' => '',
			'attribution' => '&copy; <a href="https://www.stadiamaps.com/" target="_blank">Stadia Maps</a> ' .
							'&copy; <a href="https://www.stamen.com/" target="_blank">Stamen Design</a> ' .
							'&copy; <a href="https://openmaptiles.org/" target="_blank">OpenMapTiles</a> ' .
							'{attribution.OpenStreetMap}',
			'variant' => 'stamen_terrain_lines',
			'ext' => 'png'
		),
        'Thunderforest.OpenCycleMap' => array(
            'URL' => 'https://{s}.tile.thunderforest.com/cycle/{z}/{x}/{y}.png',
            'maxZoom' => '22',
            'default' => !empty( 'Thunderforest.OpenCycleMap' == $get_base_value ) ? 'true' : 'false',
            'time' => '',
            'subdomains' => '',
        ),
        'Thunderforest.Transport' => array(
            'URL' => 'https://{s}.tile.thunderforest.com/transport/{z}/{x}/{y}.png',
            'maxZoom' => '22',
            'default' => !empty( 'Thunderforest.Transport' == $get_base_value ) ? 'true' : 'false',
            'time' => '',
            'subdomains' => '',
        ),
        'Thunderforest.TransportDark' => array(
            'URL' => 'https://{s}.tile.thunderforest.com/transport-dark/{z}/{x}/{y}.png',
            'maxZoom' => '22',
            'default' => !empty( 'Thunderforest.TransportDark' == $get_base_value ) ? 'true' : 'false',
            'time' => '',
            'subdomains' => '',
        ),
        'Thunderforest.SpinalMap' => array(
            'URL' => 'https://{s}.tile.thunderforest.com/spinal-map/{z}/{x}/{y}.png',
            'maxZoom' => '22',
            'default' => !empty( 'Thunderforest.SpinalMap' == $get_base_value ) ? 'true' : 'false',
            'time' => '',
            'subdomains' => '',
        ),
        'Thunderforest.Landscape' => array(
            'URL' => 'https://{s}.tile.thunderforest.com/landscape/{z}/{x}/{y}.png',
            'maxZoom' => '22',
            'default' => !empty( 'Thunderforest.Landscape' == $get_base_value ) ? 'true' : 'false',
            'time' => '',
            'subdomains' => '',
        ),
        'Thunderforest.Outdoors' => array(
            'URL' => 'https://{s}.tile.thunderforest.com/landscape/{z}/{x}/{y}.png',
            'maxZoom' => '22',
            'default' => !empty( 'Thunderforest.Outdoors' == $get_base_value ) ? 'true' : 'false',
            'time' => '',
            'subdomains' => '',
        ),
        'Thunderforest.Pioneer' => array(
            'URL' => 'https://{s}.tile.thunderforest.com/pioneer/{z}/{x}/{y}.png',
            'maxZoom' => '22',
            'default' => !empty( 'Thunderforest.Pioneer' == $get_base_value ) ? 'true' : 'false',
            'time' => '',
            'subdomains' => '',
        ),
        'BaseMapDE.Color' => array(
            'URL' => 'https://sgx.geodatenzentrum.de/wmts_basemapde/tile/1.0.0/de_basemapde_web_raster_farbe/default/GLOBAL_WEBMERCATOR/{z}/{y}/{x}.png',
            'maxZoom' => '18',
            'default' => !empty( 'BaseMapDE.Color' == $get_base_value ) ? 'true' : 'false',
            'time' => ''
        ),
        'BaseMapDE.Grey' => array(
            'URL' => 'https://sgx.geodatenzentrum.de/wmts_basemapde/tile/1.0.0/de_basemapde_web_raster_grau/default/GLOBAL_WEBMERCATOR/{z}/{y}/{x}.png',
            'maxZoom' => '18',
            'default' => !empty( 'BaseMapDE.Grey' == $get_base_value ) ? 'true' : 'false',
            'time' => ''
        ),
        'OpenMapSurfer.Roads' => array(
            'URL' => 'https://korona.geog.uni-heidelberg.de/tiles/roads/x={x}&y={y}&z={z}',
            'maxZoom' => '20',
            'default' => !empty( 'OpenMapSurfer.Roads' == $get_base_value ) ? 'true' : 'false',
            'time' => '',
            'subdomains' => '',
        ),
        'OpenMapSurfer.Grayscale' => array(
            'URL' => 'https://korona.geog.uni-heidelberg.de/tiles/roadsg/x={x}&y={y}&z={z}',
            'maxZoom' => '19',
            'default' => !empty( 'OpenMapSurfer.Grayscale' == $get_base_value ) ? 'true' : 'false',
            'time' => '',
            'subdomains' => '',
        ),
        'Esri.WorldStreetMap' => array(
            'URL' => 'https://server.arcgisonline.com/ArcGIS/rest/services/World_Street_Map/MapServer/tile/{z}/{y}/{x}',
            'maxZoom' => '9',
            'default' => !empty( 'Esri.WorldStreetMap' == $get_base_value ) ? 'true' : 'false',
            'time' => '',
            'subdomains' => '',
        ),
        'Esri.DeLorme' => array(
            'URL' => 'https://server.arcgisonline.com/ArcGIS/rest/services/Specialty/DeLorme_World_Base_Map/MapServer/tile/{z}/{y}/{x}',
            'maxZoom' => '11',
            'default' => !empty( 'Esri.DeLorme' == $get_base_value ) ? 'true' : 'false',
            'time' => '',
            'subdomains' => '',
        ),
        'Esri.WorldTopoMap' => array(
            'URL' => 'https://server.arcgisonline.com/ArcGIS/rest/services/World_Topo_Map/MapServer/tile/{z}/{y}/{x}',
            'maxZoom' => '9',
            'default' => !empty( 'Esri.WorldTopoMap' == $get_base_value ) ? 'true' : 'false',
            'time' => '',
            'subdomains' => '',
        ),
        'Esri.WorldImagery' => array(
            'URL' => 'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}',
            'maxZoom' => '9',
            'default' => !empty( 'Esri.WorldImagery' == $get_base_value ) ? 'true' : 'false',
            'time' => '',
            'subdomains' => '',
        ),
        'Esri.WorldTerrain' => array(
            'URL' => 'https://server.arcgisonline.com/ArcGIS/rest/services/World_Terrain_Base/MapServer/tile/{z}/{y}/{x}',
            'maxZoom' => '13',
            'default' => !empty( 'Esri.WorldTerrain' == $get_base_value ) ? 'true' : 'false',
            'time' => '',
            'subdomains' => '',
        ),
        'Esri.WorldShadedRelief' => array(
            'URL' => 'https://server.arcgisonline.com/ArcGIS/rest/services/World_Shaded_Relief/MapServer/tile/{z}/{y}/{x}',
            'maxZoom' => '13',
            'default' => !empty( 'Esri.WorldShadedRelief' == $get_base_value ) ? 'true' : 'false',
            'time' => '',
            'subdomains' => '',
        ),
        'Esri.WorldPhysical' => array(
            'URL' => 'https://server.arcgisonline.com/ArcGIS/rest/services/World_Physical_Map/MapServer/tile/{z}/{y}/{x}',
            'maxZoom' => '8',
            'default' => !empty( 'Esri.WorldPhysical' == $get_base_value ) ? 'true' : 'false',
            'time' => '',
            'subdomains' => '',
        ),
        'Esri.OceanBasemap' => array(
            'URL' => 'https://server.arcgisonline.com/ArcGIS/rest/services/Ocean/World_Ocean_Base/MapServer/tile/{z}/{y}/{x}',
            'maxZoom' => '13',
			'attribution' => 'Tiles &copy; Esri &mdash; Sources: GEBCO, NOAA, CHS, OSU, UNH, CSUMB, National Geographic, DeLorme, NAVTEQ, and Esri',
            'default' => !empty( 'Esri.OceanBasemap' == $get_base_value ) ? 'true' : 'false',
            'time' => '',
            'subdomains' => '',
        ),
        'Esri.NatGeoWorldMap' => array(
            'URL' => 'https://server.arcgisonline.com/ArcGIS/rest/services/NatGeo_World_Map/MapServer/tile/{z}/{y}/{x}',
            'maxZoom' => '16',
            'default' => !empty( 'Esri.NatGeoWorldMap' == $get_base_value ) ? 'true' : 'false',
            'time' => '',
            'subdomains' => '',
        ),
        'Esri.WorldGrayCanvas' => array(
            'URL' => 'https://server.arcgisonline.com/ArcGIS/rest/services/Canvas/World_Light_Gray_Base/MapServer/tile/{z}/{y}/{x}',
            'maxZoom' => '16',
            'default' => !empty( 'Esri.WorldGrayCanvas' == $get_base_value ) ? 'true' : 'false',
            'time' => '',
            'subdomains' => '',
        ),
        'FreeMapSK' => array(
            'URL' => 'https://t{s}.freemap.sk/T/{z}/{x}/{y}.jpeg',
            'maxZoom' => '9',
            'default' => !empty( 'FreeMapSK' == $get_base_value ) ? 'true' : 'false',
            'time' => '',
            'subdomains' => '',
        ),
        'MtbMap' => array(
            'URL' => 'http://tile.mtbmap.cz/mtbmap_tiles/{z}/{x}/{y}.png',
            'maxZoom' => '9',
            'default' => !empty( 'MtbMap' == $get_base_value ) ? 'true' : 'false',
            'time' => '',
            'subdomains' => '',
        ),
        'CartoDB.Positron' => array(
            'URL' => 'https://cartodb-basemaps-{s}.global.ssl.fastly.net/light_all/{z}/{x}/{y}.png',
            'maxZoom' => '19',
            'default' => !empty( 'CartoDB.Positron' == $get_base_value ) ? 'true' : 'false',
            'time' => '',
            'subdomains' => '',
        ),
        'CartoDB.PositronNoLabels' => array(
            'URL' => 'https://cartodb-basemaps-{s}.global.ssl.fastly.net/light_nolabels/{z}/{x}/{y}.png',
            'maxZoom' => '19',
            'default' => !empty( 'CartoDB.PositronNoLabels' == $get_base_value ) ? 'true' : 'false',
            'time' => '',
            'subdomains' => '',
        ),
        'CartoDB.PositronOnlyLabels' => array(
            'URL' => 'https://cartodb-basemaps-{s}.global.ssl.fastly.net/light_only_labels/{z}/{x}/{y}.png',
            'maxZoom' => '19',
            'default' => !empty( 'CartoDB.PositronOnlyLabels' == $get_base_value ) ? 'true' : 'false',
            'time' => '',
            'subdomains' => '',
        ),
        'CartoDB.DarkMatter' => array(
            'URL' => 'https://cartodb-basemaps-{s}.global.ssl.fastly.net/dark_all/{z}/{x}/{y}.png',
            'maxZoom' => '19',
            'default' => !empty( 'CartoDB.DarkMatter' == $get_base_value ) ? 'true' : 'false',
            'time' => '',
            'subdomains' => '',
        ),
        'CartoDB.DarkMatterNoLabels' => array(
            'URL' => 'https://cartodb-basemaps-{s}.global.ssl.fastly.net/dark_nolabels/{z}/{x}/{y}.png',
            'maxZoom' => '19',
            'default' => !empty( 'CartoDB.DarkMatterNoLabels' == $get_base_value ) ? 'true' : 'false',
            'time' => '',
            'subdomains' => '',
        ),
        'CartoDB.DarkMatterOnlyLabels' => array(
            'URL' => 'https://cartodb-basemaps-{s}.global.ssl.fastly.net/dark_only_labels/{z}/{x}/{y}.png',
            'maxZoom' => '19',
            'default' => !empty( 'CartoDB.DarkMatterOnlyLabels' == $get_base_value ) ? 'true' : 'false',
            'time' => '',
            'subdomains' => '',
        ),
        'HikeBike.HikeBike' => array(
            'URL' => 'http://{s}.tiles.wmflabs.org/hikebike/{z}/{x}/{y}.png',
            'maxZoom' => '19',
            'default' => !empty( 'HikeBike.HikeBike' == $get_base_value ) ? 'true' : 'false',
            'time' => '',
            'subdomains' => '',
        ),
        'HikeBike.HillShading' => array(
            'URL' => 'http://{s}.tiles.wmflabs.org/hillshading/{z}/{x}/{y}.png',
            'maxZoom' => '15',
            'default' => !empty( 'HikeBike.HillShading' == $get_base_value ) ? 'true' : 'false',
            'time' => '',
            'subdomains' => '',
        ),
        'BasemapAT.basemap' => array(
            'URL' => 'https://mapsneu.wien.gv.at/basemap/geolandbasemap/normal/google3857/{z}/{y}/{x}.png',
            'maxZoom' => '20',
            'default' => !empty( 'BasemapAT.basemap' == $get_base_value ) ? 'true' : 'false',
            'time' => '',
            'subdomains' => '',
        ),
        'BasemapAT.grau' => array(
            'URL' => 'https://mapsneu.wien.gv.at/basemap/bmapgrau/normal/google3857/{z}/{y}/{x}.png',
            'maxZoom' => '19',
            'default' => !empty( 'BasemapAT.grau' == $get_base_value ) ? 'true' : 'false',
            'time' => '',
            'subdomains' => '',
        ),
        'BasemapAT.overlay' => array(
            'URL' => 'https://mapsneu.wien.gv.at/basemap/bmapoverlay/normal/google3857/{z}/{y}/{x}.png',
            'maxZoom' => '19',
            'default' => !empty( 'BasemapAT.overlay' == $get_base_value ) ? 'true' : 'false',
            'time' => '',
            'subdomains' => '',
        ),
        'BasemapAT.highdpi' => array(
            'URL' => 'https://mapsneu.wien.gv.at/basemap/bmaphidpi/normal/google3857/{z}/{y}/{x}.png',
            'maxZoom' => '19',
            'default' => !empty( 'BasemapAT.highdpi' == $get_base_value ) ? 'true' : 'false',
            'time' => '',
            'subdomains' => '',
        ),
        'BasemapAT.orthofoto' => array(
            'URL' => 'https://mapsneu.wien.gv.at/basemap/bmaporthofoto30cm/normal/google3857/{z}/{y}/{x}.png',
            'maxZoom' => '20',
            'default' => !empty( 'BasemapAT.orthofoto' == $get_base_value ) ? 'true' : 'false',
            'time' => '',
            'subdomains' => '',
        ),
        'NASAGIBS.ModisTerraTrueColorCR' => array(
            'URL' => 'https://map1.vis.earthdata.nasa.gov/wmts-webmerc/MODIS_Terra_CorrectedReflectance_TrueColor/default/{time}/GoogleMapsCompatible_Level9/{z}/{y}/{x}.jpg',
            'maxZoom' => '9',
            'default' => !empty( 'NASAGIBS.ModisTerraTrueColorCR' == $get_base_value ) ? 'true' : 'false',
            'time' => 'true',
            'subdomains' => '',
        ),
        'NASAGIBS.ModisTerraBands367CR' => array(
            'URL' => 'https://map1.vis.earthdata.nasa.gov/wmts-webmerc/MODIS_Terra_CorrectedReflectance_Bands367/default/{time}/GoogleMapsCompatible_Level9/{z}/{y}/{x}.jpg',
            'maxZoom' => '9',
            'default' => !empty( 'NASAGIBS.ModisTerraBands367CR' == $get_base_value ) ? 'true' : 'false',
            'time' => 'true',
            'subdomains' => '',
        ),
        'NASAGIBS.ViirsEarthAtNight2012' => array(
            'URL' => 'https://map1.vis.earthdata.nasa.gov/wmts-webmerc/VIIRS_CityLights_2012/default/{time}/GoogleMapsCompatible_Level8/{z}/{y}/{x}.jpg',
            'maxZoom' => '8',
            'default' => !empty( 'NASAGIBS.ViirsEarthAtNight2012' == $get_base_value ) ? 'true' : 'false',
            'time' => 'true',
            'subdomains' => '',
        ),
        'NLS.osgb63k1885' => array(
            'URL' => 'https://api.maptiler.com/tiles/uk-osgb63k1885/{z}/{x}/{y}.jpg',
            'maxZoom' => '18',
            'default' => !empty( 'NLS.osgb63k1885' == $get_base_value ) ? 'true' : 'false',
            'time' => ''
        ),
        'NLS.osgb1888' => array(
            'URL' => 'https://api.maptiler.com/tiles/uk-osgb1888/{z}/{x}/{y}.jpg',
            'maxZoom' => '18',
            'default' => !empty( 'NLS.osgb1888' == $get_base_value ) ? 'true' : 'false',
            'time' => ''
        ),
        'NLS.osgb10k1888' => array(
            'URL' => 'https://api.maptiler.com/tiles/uk-osgb10k1888/{z}/{x}/{y}.jpg',
            'maxZoom' => '18',
            'default' => !empty( 'NLS.osgb10k1888' == $get_base_value ) ? 'true' : 'false',
            'time' => ''
        ),
        'NLS.osgb1919' => array(
            'URL' => 'https://api.maptiler.com/tiles/uk-osgb1919/{z}/{x}/{y}.jpg',
            'maxZoom' => '18',
            'default' => !empty( 'NLS.osgb1919' == $get_base_value ) ? 'true' : 'false',
            'time' => ''
        ),
        'NLS.osgb25k1937' => array(
            'URL' => 'https://api.maptiler.com/tiles/uk-osgb25k1937}/{z}/{x}/{y}.jpg',
            'maxZoom' => '18',
            'default' => !empty( 'NLS.osgb25k1937' == $get_base_value ) ? 'true' : 'false',
            'time' => ''
        ),
        'NLS.osgb63k1955' => array(
            'URL' => 'https://api.maptiler.com/tiles/uk-osgb63k1955/{z}/{x}/{y}.jpg',
            'maxZoom' => '18',
            'default' => !empty( 'NLS.osgb63k1955' == $get_base_value ) ? 'true' : 'false',
            'time' => ''
        ),
        'NLS.oslondon1k1893' => array(
            'URL' => 'https://api.maptiler.com/tiles/uk-oslondon1k1893/{z}/{x}/{y}.jpg',
            'maxZoom' => '18',
            'default' => !empty( 'NLS.oslondon1k1893' == $get_base_value ) ? 'true' : 'false',
            'time' => ''
        ),
		/**
		 * http://htoooth.github.io/Leaflet.ChineseTmsProviders/
		 */
		'China_TianDiTu_Normal_Map' => array(
            'URL' => 'https://t{s}.tianditu.com/DataServer?T=vec_w&X={x}&Y={y}&L={z}&tk=174705aebfe31b79b3587279e211cb9a',
            'maxZoom' => '18',
			'minZoom' => '5',
            'default' => !empty( 'China_TianDiTu_Normal_Map' == $get_base_value ) ? 'true' : 'false',
            'time' => '',
            'subdomains' => array( '0', '1', '2', '3', '4', '5', '6', '7' ),
        ),
		'China_TianDiTu_Satellite_Map' => array(
            'URL' => 'https://t{s}.tianditu.com/DataServer?T=img_w&X={x}&Y={y}&L={z}&tk=174705aebfe31b79b3587279e211cb9a',
            'maxZoom' => '18',
			'minZoom' => '5',
            'default' => !empty( 'China_TianDiTu_Satellite_Map' == $get_base_value ) ? 'true' : 'false',
            'time' => '',
            'subdomains' => array( '0', '1', '2', '3', '4', '5', '6', '7' ),
        ),
		'China_TianDiTu_Terrain_Map' => array(
            'URL' => 'https://t{s}.tianditu.com/DataServer?T=ter_w&X={x}&Y={y}&L={z}&tk=174705aebfe31b79b3587279e211cb9a',
            'maxZoom' => '18',
			'minZoom' => '5',
            'default' => !empty( 'China_TianDiTu_Terrain_Map' == $get_base_value ) ? 'true' : 'false',
            'time' => '',
            'subdomains' => array( '0', '1', '2', '3', '4', '5', '6', '7' ),
        ),
		'China_GaoDe_Normal_Map' => array(
            'URL' => 'https://webrd0{s}.is.autonavi.com/appmaptile?lang=zh_cn&size=1&scale=1&style=8&x={x}&y={y}&z={z}',
            'maxZoom' => '18',
			'minZoom' => '5',
            'default' => !empty( 'China_GaoDe_Normal_Map' == $get_base_value ) ? 'true' : 'false',
            'time' => '',
            'subdomains' => array( '1', '2', '3', '4' ),
        ),
		'China_GaoDe_Satellite_Map' => array(
            'URL' => 'https://webst0{s}.is.autonavi.com/appmaptile?style=6&x={x}&y={y}&z={z}',
            'maxZoom' => '18',
			'minZoom' => '5',
            'default' => !empty( 'China_GaoDe_Satellite_Map' == $get_base_value ) ? 'true' : 'false',
            'time' => '',
            'subdomains' => array( '1', '2', '3', '4' ),
        ),
		'China_Google_Normal_Map' => array(
            'URL' => 'https://www.google.cn/maps/vt?lyrs=m@189&gl=cn&x={x}&y={y}&z={z}',
            'maxZoom' => '18',
			'minZoom' => '5',
            'default' => !empty( 'China_Google_Normal_Map' == $get_base_value ) ? 'true' : 'false',
            'time' => '',
            'subdomains' => '',
        ),
		'China_Google_Satellite_Map' => array(
            'URL' => 'https://www.google.cn/maps/vt?lyrs=s@189&gl=cn&x={x}&y={y}&z={z}',
            'maxZoom' => '18',
			'minZoom' => '5',
            'default' => !empty( 'China_Google_Satellite_Map' == $get_base_value ) ? 'true' : 'false',
            'time' => '',
            'subdomains' => '',
        ),
		'China_Geoq_Normal_Map' => array(
            'URL' => 'https://map.geoq.cn/ArcGIS/rest/services/ChinaOnlineCommunity/MapServer/tile/{z}/{y}/{x}',
            'maxZoom' => '18',
			'minZoom' => '5',
            'default' => !empty( 'China_Geoq_Normal_Map' == $get_base_value ) ? 'true' : 'false',
            'time' => '',
            'subdomains' => '',
        ),
		'China_Geoq_Normal_PurplishBlue' => array(
            'URL' => 'https://map.geoq.cn/ArcGIS/rest/services/ChinaOnlineStreetPurplishBlue/MapServer/tile/{z}/{y}/{x}',
            'maxZoom' => '18',
			'minZoom' => '5',
            'default' => !empty( 'China_Geoq_Normal_PurplishBlue' == $get_base_value ) ? 'true' : 'false',
            'time' => '',
            'subdomains' => '',
        ),
		'China_Geoq_Normal_Gray' => array(
            'URL' => 'https://map.geoq.cn/ArcGIS/rest/services/ChinaOnlineStreetGray/MapServer/tile/{z}/{y}/{x}',
            'maxZoom' => '18',
			'minZoom' => '5',
            'default' => !empty( 'China_Geoq_Normal_Gray' == $get_base_value ) ? 'true' : 'false',
            'time' => '',
            'subdomains' => '',
        ),
		'China_Geoq_Normal_Warm' => array(
            'URL' => 'https://map.geoq.cn/ArcGIS/rest/services/ChinaOnlineStreetWarm/MapServer/tile/{z}/{y}/{x}',
            'maxZoom' => '18',
			'minZoom' => '5',
            'default' => !empty( 'China_Geoq_Normal_Warm' == $get_base_value ) ? 'true' : 'false',
            'time' => '',
            'subdomains' => '',
        ),
		'China_Geoq_Theme_Hydro' => array(
            'URL' => 'https://thematic.geoq.cn/arcgis/rest/services/ThematicMaps/WorldHydroMap/MapServer/tile/{z}/{y}/{x}',
            'maxZoom' => '18',
			'minZoom' => '5',
            'default' => !empty( 'China_Geoq_Theme_Hydro' == $get_base_value ) ? 'true' : 'false',
            'time' => '',
            'subdomains' => '',
        ),
		'China_OSM_Normal_Map' => array(
            'URL' => 'https://{s}.tile.osm.org/{z}/{x}/{y}.png',
            'maxZoom' => '18',
			'minZoom' => '5',
            'default' => !empty( 'China_OSM_Normal_Map' == $get_base_value ) ? 'true' : 'false',
            'time' => '',
            'subdomains' => array( 'a', 'b', 'c' ),
        ),
        'TopPlusOpen.Color' => array(
            'URL' => 'http://sgx.geodatenzentrum.de/wmts_topplus_open/tile/1.0.0/web/default/WEBMERCATOR/{z}/{y}/{x}.png',
            'maxZoom' => '18',
            'default' => !empty( 'TopPlusOpen.Color' == $get_base_value ) ? 'true' : 'false',
            'time' => ''
        ),
        'TopPlusOpen.Grey' => array(
            'URL' => 'http://sgx.geodatenzentrum.de/wmts_topplus_open/tile/1.0.0/web_grau/default/WEBMERCATOR/{z}/{y}/{x}.png',
            'maxZoom' => '18',
            'default' => !empty( 'TopPlusOpen.Grey' == $get_base_value ) ? 'true' : 'false',
            'time' => ''
        )
    );

    return json_encode( $baselayers );

}

/**
 * Get open street map overlay layers.
 *
 * Get osm base url, default values.
 *
 * @since 2.0.0
 *
 * @param string $fields_id Get google map fields id.
 * @return array $overlay_layers Overlay layers.
 */
function get_gd_cgm_osm_overlay_layers($fields_id) {

    $get_osm_option_key = get_gd_cgm_osm_option_key( $fields_id );
    $get_osm_option_val = maybe_unserialize( get_option($get_osm_option_key) );

    $get_overlay_value = !empty( $get_osm_option_val['overlay_value'] ) ? $get_osm_option_val['overlay_value'] : array();

    $overlay_layers = array(
        'OpenSeaMap' => array(
            'URL' => 'https://tiles.openseamap.org/seamark/{z}/{x}/{y}.png',
            'maxZoom' => '19',
            'default' => ( in_array("OpenSeaMap", $get_overlay_value ) ) ? 'true' : 'false',
            'time' => '',
            'subdomains' => '',
        ),
        'OpenMapSurfer.AdminBounds' => array(
            'URL' => 'https://korona.geog.uni-heidelberg.de/tiles/adminb/x={x}&y={y}&z={z}',
            'maxZoom' => '19',
            'default' => ( in_array("OpenMapSurfer.AdminBounds", $get_overlay_value ) ) ? 'true' : 'false',
            'time' => '',
            'subdomains' => '',
        ),
        'OpenWeatherMap.Clouds' => array(
            'URL' => 'http://{s}.tile.openweathermap.org/map/clouds/{z}/{x}/{y}.png',
            'maxZoom' => '19',
            'default' => ( in_array("OpenWeatherMap.Clouds", $get_overlay_value ) ) ? 'true' : 'false',
            'time' => '',
            'subdomains' => '',
        ),
        'OpenWeatherMap.Pressure' => array(
            'URL' => 'http://{s}.tile.openweathermap.org/map/pressure/{z}/{x}/{y}.png',
            'maxZoom' => '19',
            'default' => ( in_array("OpenWeatherMap.Pressure", $get_overlay_value ) ) ? 'true' : 'false',
            'time' => '',
            'subdomains' => '',
        ),
        'OpenWeatherMap.Wind' => array(
            'URL' => 'http://{s}.tile.openweathermap.org/map/wind/{z}/{x}/{y}.png',
            'maxZoom' => '19',
            'default' => ( in_array("OpenWeatherMap.Wind", $get_overlay_value ) ) ? 'true' : 'false',
            'time' => '',
            'subdomains' => '',
        ),
        'NASAGIBS.ModisTerraLSTDay' => array(
            'URL' => 'https://map1.vis.earthdata.nasa.gov/wmts-webmerc/MODIS_Terra_Land_Surface_Temp_Day/default/{time}/GoogleMapsCompatible_Level7/{z}/{y}/{x}.png',
            'maxZoom' => '7',
            'default' => ( in_array("NASAGIBS.ModisTerraLSTDay", $get_overlay_value ) ) ? 'true' : 'false',
            'time' => 'true',
            'subdomains' => '',
        ),
        'NASAGIBS.ModisTerraSnowCover' => array(
            'URL' => 'https://map1.vis.earthdata.nasa.gov/wmts-webmerc/MODIS_Terra_NDSI_Snow_Cover/default/{time}/GoogleMapsCompatible_Level8/{z}/{y}/{x}.png',
            'maxZoom' => '8',
            'default' => ( in_array("NASAGIBS.ModisTerraSnowCover", $get_overlay_value ) ) ? 'true' : 'false',
            'time' => 'true',
            'subdomains' => '',
        ),
        'NASAGIBS.ModisTerraAOD' => array(
            'URL' => 'https://map1.vis.earthdata.nasa.gov/wmts-webmerc/MODIS_Terra_Aerosol/default/{time}/GoogleMapsCompatible_Level6/{z}/{y}/{x}.png',
            'maxZoom' => '6',
            'default' => ( in_array("NASAGIBS.ModisTerraAOD", $get_overlay_value ) ) ? 'true' : 'false',
            'time' => 'true',
            'subdomains' => '',
        ),
        'NASAGIBS.ModisTerraChlorophyll' => array(
            'URL' => 'https://map1.vis.earthdata.nasa.gov/wmts-webmerc/MODIS_Terra_Chlorophyll_A/default/{time}/GoogleMapsCompatible_Level7/{z}/{y}/{x}.png',
            'maxZoom' => '7',
            'default' => ( in_array("NASAGIBS.ModisTerraChlorophyll", $get_overlay_value ) ) ? 'true' : 'false',
            'time' => 'true',
            'subdomains' => '',
        ),
		'China_TianDiTu_Normal_Annotion' => array(
            'URL' => 'https://t{s}.tianditu.com/DataServer?T=cva_w&X={x}&Y={y}&L={z}&tk=174705aebfe31b79b3587279e211cb9a',
            'maxZoom' => '18',
			'minZoom' => '5',
            'default' => ( in_array("China_TianDiTu_Normal_Annotion", $get_overlay_value ) ) ? 'true' : 'false',
            'time' => '',
            'subdomains' => array( '0', '1', '2', '3', '4', '5', '6', '7' ),
        ),
		'China_TianDiTu_Satellite_Annotion' => array(
            'URL' => 'https://t{s}.tianditu.com/DataServer?T=cia_w&X={x}&Y={y}&L={z}&tk=174705aebfe31b79b3587279e211cb9a',
            'maxZoom' => '18',
			'minZoom' => '5',
            'default' => ( in_array("China_TianDiTu_Satellite_Annotion", $get_overlay_value ) ) ? 'true' : 'false',
            'time' => '',
            'subdomains' => array( '0', '1', '2', '3', '4', '5', '6', '7' ),
        ),
		'China_TianDiTu_Terrain_Annotion' => array(
            'URL' => 'https://t{s}.tianditu.com/DataServer?T=cta_w&X={x}&Y={y}&L={z}&tk=174705aebfe31b79b3587279e211cb9a',
            'maxZoom' => '18',
			'minZoom' => '5',
            'default' => ( in_array("China_TianDiTu_Terrain_Annotion", $get_overlay_value ) ) ? 'true' : 'false',
            'time' => '',
            'subdomains' => array( '0', '1', '2', '3', '4', '5', '6', '7' ),
        ),
		'China_GaoDe_Satellite_Annotion' => array(
            'URL' => 'https://webst0{s}.is.autonavi.com/appmaptile?style=8&x={x}&y={y}&z={z}',
            'maxZoom' => '18',
			'minZoom' => '5',
            'default' => ( in_array("China_GaoDe_Satellite_Annotion", $get_overlay_value ) ) ? 'true' : 'false',
            'time' => '',
            'subdomains' => array( '1', '2', '3', '4' ),
        )
    );

    return json_encode( $overlay_layers );

}

/**
 * Get custom open street map styles by page title.
 *
 * @since 2.0.0
 *
 * @param string $page Get style page title.
 * @return array $osm_style_array
 */
function get_gd_cgm_custom_osm_styles( $page ) {

    $default_layer = 'OpenStreetMap.Mapnik';

    $get_osm_options ='';

    $osm_style_array = array();

    if( !empty( $page ) && 'home' === $page ) {
        $get_osm_options = get_option( 'gd_custom_osm_home_options' );
    } elseif ( !empty( $page ) && 'listing' === $page ) {
        $get_osm_options = get_option( 'gd_custom_osm_listing_options' );
    } elseif ( !empty( $page ) && 'detail' === $page ) {
        $get_osm_options = get_option( 'gd_custom_osm_detail_options' );
    } elseif ( ! empty( $page ) && 'add_listing' === $page ) {
        $get_osm_options = get_option( 'gd_custom_osm_add_listing_options' );
    }

    $get_osm_options = maybe_unserialize( $get_osm_options );

    $osm_style_array['baseLayer'] = !empty( $get_osm_options['base_value'] ) ? $get_osm_options['base_value'] : $default_layer;
    $osm_style_array['overlays'] = !empty( $get_osm_options['overlay_value'] ) ? $get_osm_options['overlay_value'] : array();

    return apply_filters('get_gd_custom_osm_styles', $osm_style_array);

}

/**
 * Get the map JS API provider name.
 *
 * @since 2.0.0
 *
 * @return string $gd_map_name The map API provider name.
 */
function gd_cgm_map_name() {

	$gd_map_name = geodir_get_option('maps_api', 'google');

	if (!in_array($gd_map_name, array('none', 'auto', 'google', 'osm'))) {

		$gd_map_name = 'auto';

	}

	/**
	 * Filter the map JS API provider name.
	 *
	 * @since 2.0.0
	 *
	 * @param string $gd_map_name The map API provider name.
	 */
	return apply_filters('gd_map_name', $gd_map_name);
}

/**
 * Returns the default location.
 *
 * @since 2.0.0
 *
 * @return object $location_result
 */
function gd_cgm_default_location() {

	$location = new stdClass();
	$location->city = geodir_get_option('default_location_city');
	$location->region = geodir_get_option('default_location_region');
	$location->country = geodir_get_option('default_location_country');
	$location->latitude = geodir_get_option('default_location_latitude');
	$location->longitude = geodir_get_option('default_location_longitude');

	// slugs
	$location->city_slug = sanitize_title($location->city);
	$location->region_slug = sanitize_title($location->region);
	$location->country_slug = sanitize_title($location->country);

	/**
	 * Filter the default location.
	 *
	 * @since 2.0.0
	 *
	 * @param string $location_result The default location object.
	 */
	return $location_result = apply_filters('gd_custom_map_default_location', $location );
}

/**
 * Get gd map default language.
 *
 * @since 2.0.0
 *
 * @return string Map default language.
 */
function get_gd_cgm_map_language() {

	return geodir_get_option( 'map_language' );

}

/**
 * Get gd map default map api key.
 *
 * @since 2.0.0
 *
 * @return string default map api key.
 */
function get_gd_cgm_map_api_key() {

	return geodir_get_option( 'google_maps_api_key' );

}

function geodir_custom_map_osm_base_layers() {
	$layers = array( 'OpenStreetMap.Mapnik', 'OpenStreetMap.DE'/*, 'OpenStreetMap.CH', 'OpenStreetMap.France'*/, 'OpenStreetMap.HOT', 'MapTilesAPI.OSMEnglish', 'MapTilesAPI.OSMFrancais', 'MapTilesAPI.OSMEspagnol', 'OpenTopoMap', 'Stadia.AlidadeSmooth', 'Stadia.AlidadeSmoothDark', 'Stadia.OSMBright', 'Stadia.Outdoors', 'Stadia.StamenToner', 'Stadia.StamenTonerBackground', 'Stadia.StamenTonerLines', 'Stadia.StamenTonerLabels', 'Stadia.StamenTonerLite', 'Stadia.StamenWatercolor', 'Stadia.StamenTerrain', 'Stadia.StamenTerrainBackground', 'Stadia.StamenTerrainLabels', 'Stadia.StamenTerrainLines', 'Thunderforest.OpenCycleMap', 'Thunderforest.Transport', 'Thunderforest.TransportDark', 'Thunderforest.SpinalMap', 'Thunderforest.Landscape', 'Thunderforest.Outdoors', 'Thunderforest.Pioneer', 'Thunderforest.MobileAtlas', 'Thunderforest.Neighbourhood'/*, 'BaseMapDE.Color', 'BaseMapDE.Grey'*/, 'CyclOSM', 'Jawg.Streets', 'Jawg.Terrain', 'Jawg.Sunny', 'Jawg.Lagoon', 'Jawg.Dark', 'Jawg.Light', 'Jawg.Matrix', 'Esri.NatGeoWorldMap', 'Esri.WorldGrayCanvas', 'MtbMap', 'CartoDB.Positron', 'CartoDB.PositronNoLabels', 'CartoDB.PositronOnlyLabels', 'CartoDB.DarkMatter', 'CartoDB.DarkMatterNoLabels', 'CartoDB.DarkMatterOnlyLabels', 'CartoDB.Voyager', 'CartoDB.VoyagerNoLabels', 'CartoDB.VoyagerOnlyLabels', 'CartoDB.VoyagerLabelsUnder'/*, 'HikeBike.HikeBike', 'HikeBike.HillShading', 'BasemapAT.basemap', 'BasemapAT.grau', 'BasemapAT.overlay', 'BasemapAT.terrain', 'BasemapAT.surface', 'BasemapAT.highdpi', 'BasemapAT.orthofoto', 'nlmaps.standaard', 'nlmaps.pastel', 'nlmaps.grijs', 'nlmaps.water', 'nlmaps.luchtfoto', 'NASAGIBS.ModisTerraTrueColorCR', 'NASAGIBS.ModisTerraBands367CR', 'NASAGIBS.ViirsEarthAtNight2012', 'NLS.osgb63k1885', 'NLS.osgb1888', 'NLS.osgb10k1888', 'NLS.osgb1919', 'NLS.osgb25k1937', 'NLS.osgb63k1955', 'NLS.oslondon1k1893'*/, 'Wikimedia', 'GeoportailFrance.plan', 'GeoportailFrance.orthos', 'USGS.USTopo', 'USGS.USImagery', 'USGS.USImageryTopo', 'ChinaGaoDe.NormalMap', 'ChinaGaoDe.SatelliteMap', 'ChinaGoogle.NormalMap', 'ChinaGoogle.SatelliteMap', 'ChinaGeoq.NormalMap', 'ChinaGeoq.NormalPurplishBlue', 'ChinaGeoq.NormalGray', 'ChinaGeoq.NormalWarm', 'ChinaGeoq.ThemeHydro', 'TopPlusOpen.Color', 'TopPlusOpen.Grey' );

	return apply_filters('geodir_custom_map_osm_base_layers', $layers );
}

function geodir_custom_map_osm_overlay_layers() {
	$layers = array( 'OpenSeaMap', 'OpenRailwayMap', 'SafeCast', 'OpenWeatherMap.Clouds', 'OpenWeatherMap.CloudsClassic', 'OpenWeatherMap.Precipitation', 'OpenWeatherMap.PrecipitationClassic', 'OpenWeatherMap.Rain', 'OpenWeatherMap.RainClassic', 'OpenWeatherMap.Pressure', 'OpenWeatherMap.PressureContour', 'OpenWeatherMap.Wind', 'OpenWeatherMap.Temperature', 'OpenWeatherMap.Snow', 'GeoportailFrance.parcels'/*, 'NASAGIBS.ModisTerraLSTDay', 'NASAGIBS.ModisTerraSnowCover', 'NASAGIBS.ModisTerraAOD', 'NASAGIBS.ModisTerraChlorophyll'*/, 'JusticeMap.income', 'JusticeMap.americanIndian', 'JusticeMap.asian', 'JusticeMap.black', 'JusticeMap.hispanic', 'JusticeMap.multi', 'JusticeMap.nonWhite', 'JusticeMap.white', 'JusticeMap.plurality', 'WaymarkedTrails.hiking', 'WaymarkedTrails.cycling', 'WaymarkedTrails.mtb', 'WaymarkedTrails.slopes', 'WaymarkedTrails.riding', 'WaymarkedTrails.skating', 'ChinaGaoDe.SatelliteAnnotion', 'ChinaGoogle.SatelliteAnnotion' );

	return apply_filters('geodir_custom_map_osm_overlay_layers', $layers );
}