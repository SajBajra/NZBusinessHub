<?php

if( !function_exists('gd_lists_get_page_id' ) ) {

    /**
     * Get add lists page id If page exists.
     *
     * @since 2.0.0
     *
     * @return int $page_id
     */
    function gd_lists_get_page_id() {

        $page_id = geodir_get_option( 'geodir_add_list_page' );

        // check site wpml.
        if ( gd_lists_is_wpml() ) {
            $page_id =  gd_lists_wpml_object_id( $page_id, 'page', true );
        }

        return $page_id;

    }

}

if( !function_exists('gd_lists_is_wpml' ) ) {

    /**
     * Check site is wpml or not.
     *
     * @since 2.0.0
     *
     * @return bool
     */
    function gd_lists_is_wpml() {

        if (class_exists('SitePress') && function_exists('icl_object_id')) {
            return true;
        }

        return false;
    }

}

// check gd_lists_wpml_object_id function exists or not.
if( !function_exists( 'gd_lists_wpml_object_id' ) ) {

    /**
     * Get wpml object id.
     *
     * @since 2.0.0
     *
     * @param int $element_id Get element id.
     * @param string $element_type Get element type.
     * @param bool $return_original_if_missing
     * @param null $ulanguage_code Get language code
     * @return string $element_id
     */
    function gd_lists_wpml_object_id( $element_id, $element_type = 'post', $return_original_if_missing = false, $ulanguage_code = null ) {

        if( gd_lists_is_wpml() ) {

            if ( function_exists( 'wpml_object_id_filter' ) ) {
                return apply_filters( 'wpml_object_id', $element_id, $element_type, $return_original_if_missing, $ulanguage_code );
            } else {
                return icl_object_id( $element_id, $element_type, $return_original_if_missing, $ulanguage_code );
            }

        }

        return $element_id;
    }
}

if( !function_exists( 'gd_list_check_buddypress_exists' ) ) {

    /**
     * Check buddypress plugin is active or not.
     *
     * @since 2.0.0
     *
     * @return bool
     */
    function gd_list_check_buddypress_exists(){

        if ( !function_exists( 'is_plugin_active' ) ) {
            include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
        }
        return ( is_plugin_active( 'buddypress/bp-loader.php' ) ) ? true : false;


    }

}

if( !function_exists( 'gd_list_check_userswp_exsits') ) {

    /**
     * Check userswp plugin is active or not.
     *
     * @since 2.0.0
     *
     * @return bool
     */
    function gd_list_check_userswp_exsits(){

        if ( !function_exists( 'is_plugin_active' ) ) {
            include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
        }
        return ( is_plugin_active( 'userswp/userswp.php' ) ) ? true : false;

    }

}

if( !function_exists( 'gd_list_bp_members_page_slug' )) {

    /**
     * Get buddypress members page slug.
     *
     * @since 2.0.0
     *
     * @return string
     */
    function gd_list_bp_members_page_slug(){

        $bp = buddypress();

        return !empty( $bp->loaded_components['members'] ) ? $bp->loaded_components['members'] :'';
    }

}

// check gd_list_all_list_link function exists or not.
if( !function_exists( 'gd_list_all_list_link' ) ) {

    /**
     * Get all gd list page link.
     *
     * @since 2.0.0
     *
     * @return string
     */
    function gd_list_all_list_link(){

        return apply_filters('gd_list_view_list_link', esc_url( site_url('lists') ));

    }

}

if( !function_exists('gd_list_edit_list_link' ) ) {

    /**
     * Get list edit link using list_id.
     *
     * @since 2.0.0
     *
     * @param int $list_id Get List id.
     * @return bool|string
     *
     */
    function gd_list_edit_list_link( $list_id ) {

        if( empty( $list_id ) ){
            return false;
        }

        $link = esc_url( get_the_permalink( $list_id ).'?edit-list=1' );

        return apply_filters('gd_list_view_list_link', $link, $list_id);
    }

}

// check gd_list_edit_title_desc_link function exists or not.
if( !function_exists( 'gd_list_edit_title_desc_link' ) ) {

    /**
     * Get edit title and description using list_id.
     *
     * @since 2.0.0
     *
     * @param int $list_id Get List id.
     * @return bool|string
     */
    function gd_list_edit_title_desc_link( $list_id ) {

        $add_list_page_id = geodir_get_option('geodir_add_list_page');

        if( empty( $list_id ) ) {
            return false;
        }

        if( empty( $add_list_page_id ) ) {
            return false;
        }

        $link = esc_url( get_the_permalink($add_list_page_id).'?pid='.$list_id);

        return apply_filters('gd_list_view_list_link', $link, $list_id);
    }

}

// check gd_list_view_list_link function exists or not.
if( !function_exists( 'gd_list_view_list_link' ) ) {

    function gd_list_view_list_link( $user_id, $list_id ) {

        if( empty($list_id)){
            return false;
        }

        $view_list = get_author_posts_url($user_id);

        if( gd_list_check_userswp_exsits() ){

            $user_profile_link = uwp_build_profile_tab_url($user_id);

            $view_list = $user_profile_link.'/lists/';

        } elseif ( gd_list_check_buddypress_exists() ) {

            $member_page = gd_list_bp_members_page_slug();

            $user_link =  home_url( '/'.$member_page.'/' . bp_core_get_username( $user_id ) );

            $view_list = !empty( $user_link ) ? $user_link.'/lists/' :'';

        }

        return apply_filters('gd_list_view_list_link', esc_url( $view_list ), $list_id, $user_id);
    }

}

function geodir_list_page_single_content( $no_filter = false,$blocks = false ) {
		if ( $blocks ) {
			$content = "<!-- wp:geodirectory/geodir-widget-list-loop-actions {\"content\":\"\",\"sd_shortcode\":\"[gd_list_loop_actions alignment=''  size=''  btn_mt=''  btn_mr='1'  btn_mb=''  btn_ml='1'  mt=''  mr=''  mb='4'  ml=''  pt=''  pr=''  pb=''  pl='' ]\"} -->
<div class=\"wp-block-geodirectory-geodir-widget-list-loop-actions\"></div>
<!-- /wp:geodirectory/geodir-widget-list-loop-actions -->

<!-- wp:geodirectory/geodir-widget-notifications {\"content\":\"\",\"sd_shortcode\":\"[gd_notifications]\",\"className\":\"wp-block-geodirectory-geodir-widget-notifications\"} /-->

<!-- wp:geodirectory/geodir-widget-list-single-description {\"content\":\"\",\"sd_shortcode\":\"[gd_list_single_description title=''  limit=''  strip_tags='false'  alignment=''  text_color=''  bg=''  mt=''  mr=''  mb='3'  ml=''  pt=''  pr=''  pb=''  pl='' ]\"} -->
<div class=\"wp-block-geodirectory-geodir-widget-list-single-description\"></div>
<!-- /wp:geodirectory/geodir-widget-list-single-description -->

<!-- wp:geodirectory/geodir-widget-list-loop {\"content\":\"\",\"sd_shortcode\":\"[gd_list_loop layout='2'  row_gap=''  column_gap=''  card_border=''  card_shadow=''  mt=''  mr=''  mb=''  ml=''  pt=''  pr=''  pb=''  pl='' ]\"} -->
<div class=\"wp-block-geodirectory-geodir-widget-list-loop\"></div>
<!-- /wp:geodirectory/geodir-widget-list-loop -->";
		} else {
			$content = "[gd_list_loop_actions]\n[gd_notifications]\n[gd_list_single_description]\n[gd_list_loop layout='2']";
		}

		if ( $no_filter ) {
			return $content;
		} else {
			return apply_filters( 'geodir_list_default_page_single_content', $content, $blocks );
		}
	}