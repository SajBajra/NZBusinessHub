var map_clusters_arr = [];

function create_marker_cluster(map_canvas_name) {
    var clusert_already_exists = false;
    var map_id = (map_canvas_name);
    var map_hash = "#" + map_canvas_name;
    if (geodir_params.marker_cluster_size) {
        var size = geodir_params.marker_cluster_size;
    } else {
        var size = 60;
    }
    if (geodir_params.marker_cluster_zoom) {
        var zoom = geodir_params.marker_cluster_zoom;
    } else {
        var zoom = 15;
    }
    jQuery(map_hash).goMap();
    if (typeof map_clusters_arr !== 'undefined' && map_clusters_arr.length) {
        for (let i = 0; i < map_clusters_arr.length; i++) {
            if (map_clusters_arr[i].label == map_canvas_name) {
                clusert_already_exists = true;
                add_cluster_markers(map_clusters_arr[i]);
            }
        }
    }
    if (!clusert_already_exists) {
        var mcOptions = {
            maxZoom: parseInt(zoom),
            ignoreHidden: true,
            gridSize: parseInt(size),
            imagePath: geodir_params.imagePath
        };
        var cluster_obj = {};
        cluster_obj.label = map_id;
        marker_clusterer = new MarkerClusterer(jQuery.goMap.map, null, mcOptions);
        cluster_obj.cluster = marker_clusterer;
        map_clusters_arr.push(cluster_obj)
        add_cluster_markers(cluster_obj);
    }
}

function add_cluster_markers(cluster_obj) {
    var split_str, pid, catid, show_cat;
    var pcm_array, $old_marker, selected_cat;
    var already_visible = false;
    var to_show;
    split_str = '';
    pid = '';
    catid = '';
    show_cat = '';
    pcm_array = null;
    $old_marker = null;
    selected_cat = null;
    pcm_array = new Array();
    $old_marker = new Array();
    selected_cat = new Array();
    var map_id = cluster_obj.label;
    var map_hash = "#" + map_id;
    jQuery(map_hash).goMap();
    cluster_markers = jQuery.goMap.getMarkers('markers');
    if (typeof cluster_markers !== 'undefined' && cluster_markers.length) {
        for (let i = 0; i < cluster_markers.length; i++) {
            var clmarker = cluster_markers[i];
            var clmarker_id = clmarker.id;
            split_str = clmarker_id.split('_');
            pid = split_str[0];
            catid = split_str[1];
            if (!(pid in pcm_array)) {
                pcm_array[pid] = new Array();
            }
            pcm_array[pid][catid] = cluster_markers[i];
            cluster_obj.cluster.addMarker(clmarker, true);
        }
    }
    cluster_obj.cluster.repaint();
}

function remove_cluster_markers(map_canvas_name) {
    if (typeof map_clusters_arr !== 'undefined' && map_clusters_arr.length) {
        for (let i = 0; i < map_clusters_arr.length; i++) {
            if (map_clusters_arr[i].label == map_canvas_name) {
                map_clusters_arr[i].cluster.clearMarkers();
            }
        }
    }
}