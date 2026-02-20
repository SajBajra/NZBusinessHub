<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * GeoDirectory GeoDir_Location_Bricks.
 *
 * @class    GeoDir_Location_Bricks
 * @package  GeoDirectory_Location_Manager/Classes
 * @category Class
 * @author   AyeCode
 */
class GeoDir_Location_Bricks {

    public static function init() {

        add_filter( 'geodir_bricks_dynamic_data_tags', array( __CLASS__, 'tags_config' ), 10, 2 );
        add_filter( 'geodir_bricks_get_tag_value', array( __CLASS__, 'tag_value' ), 10, 4 );
    }

    public static function tag_value( $tag, $post, $args, $context ) {

        if ( strpos( $tag, 'gd_location_meta_' ) === 0 ) {
            $value = '';
            $_tag = explode( 'gd_location_meta_', $tag, 2 );
            $key = esc_attr($_tag[1]);


            // maybe get location image
            if ($key === 'location_image') {

                $info = GeoDir_Location_SEO::get_location_seo();

                if (!empty($info->image)) {
                    $value = 'image' === $context ? array($info->image) : wp_get_attachment_url($info->image);
                }

            }else
            if ($key) {
                $value = do_shortcode( '[gd_location_meta key="' . esc_attr($key ) . '" no_wrap="1"]' );
            }

        }

        return $value;
    }

    public static function tags_config( $tags, $instance ) {


        $post_meta_group = esc_html__( 'GD Current Location Meta', 'geodirlocation' );
        $post_meta_keys = array(
            'location_name' => __( 'Location Name', 'geodirlocation' ),
            'location_slug' => __( 'Location Slug', 'geodirlocation' ),
            'location_url' => __( 'Location Url', 'geodirlocation' ),
            'location_link' => __( 'Location Link', 'geodirlocation' ),
            'location_cpt_url' => __( 'Location + CPT Url', 'geodirlocation' ),
            'location_cpt_link' => __( 'Location + CPT Link', 'geodirlocation' ),
            'location_description' => __( 'Location Description', 'geodirlocation' ),
            'location_meta_title' => __( 'Location Meta Title', 'geodirlocation' ),
            'location_meta_description' => __( 'Location Meta Description', 'geodirlocation' ),
            'location_image' => __( 'Location Image', 'geodirlocation' ),
            'location_image_tagline' => __( 'Location Image Tagline', 'geodirlocation' ),
        );

        foreach( $post_meta_keys as $key => $label ) {
            $tags[ 'gd_location_meta_' . $key ] = array(
                'label' => esc_html( $label . ' (' . $key . ')' ),
                'group' => $post_meta_group
            );
        }

        return $tags;
    }
}

GeoDir_Location_Bricks::init();