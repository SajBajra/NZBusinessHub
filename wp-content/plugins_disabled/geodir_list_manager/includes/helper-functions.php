<?php

/**
 * Get the plural name for the CPT.
 * 
 * @since 2.0.0.0
 * @return string|void
 */
function geodir_lists_name_plural(){
    $cpt = geodir_get_option('list_post_type');
    $name = __( "Lists", "gd-lists" );
    if(!empty($cpt['labels']['name'])){
        return __( $cpt['labels']['name'], "gd-lists" );
    }
    
    return $name;
}

/**
 * Get the singular name for the CPT.
 *
 * @since 2.0.0.0
 * @return string|void
 */
function geodir_lists_name_singular(){
    $cpt = geodir_get_option('list_post_type');
    $name = __( "List", "gd-lists" );
    if(!empty($cpt['labels']['singular_name'])){
        return __( $cpt['labels']['singular_name'], "gd-lists" );
    }

    return $name;
}

/**
 * Get the slug for the CPT.
 *
 * @since 2.0.0.0
 * @return string|void
 */
function geodir_lists_slug(){
    $pt = GeoDir_Lists_CPT::post_type_args();

    return ! empty( $pt['rewrite']['slug'] ) ? $pt['rewrite']['slug'] : 'lists';
}

function geodir_lists_get_real_post_status($post_id){
    global $wpdb;

    $result = $wpdb->get_var( $wpdb->prepare( "SELECT post_status FROM $wpdb->posts WHERE ID=%d", $post_id ) );

    return $result;

}