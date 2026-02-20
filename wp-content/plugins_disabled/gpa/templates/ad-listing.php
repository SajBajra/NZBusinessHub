<?php
/**
 * Contains the ad template.
 *
 * You can override this template by copying it to your-theme/gpa/ad=code.php
 *
 * @var Adv_Ad_Template $ad The ad to display.
 */

defined( 'ABSPATH' ) || exit;

global $post;

if ( isset( $post ) ) {
    $reset_post = $post;
}

$listing = absint( $ad->ad->get( 'listing' ) );

if ( empty( $listing ) || 'publish' !== get_post_status( $listing ) ) {

    aui()->alert(
        array(
            'content' => __( 'This is listing is either expired or deleted', 'advertising' ),
            'type'    => 'alert',
        ),
        true
    );
    return;

}

$post = get_post( $listing );
setup_postdata( $post );

add_filter( 'post_type_link', 'adv_filter_listing_permalink', 1000, 2 );
add_filter( 'the_title', 'adv_filter_listing_title' );
add_filter( 'geodir_widget_post_title_output', 'adv_replace_gd_title_ad_text' );
add_filter( 'elementor/widget/render_content', 'adv_elementor_replace_gd_title_ad_text', 10, 2 );

$GLOBALS['adv_current_listing'] = $post;
$GLOBALS['adv_current_ad_id'] = $ad->get_id();

geodir_get_template(
    "bootstrap/content-listing.php",
    array(
        'column_gap_class'   => 'mb-1',
        'row_gap_class'      => '',
        'card_border_class'  => '',
        'card_shadow_class'  => '',
    )
);

remove_filter( 'post_type_link', 'adv_filter_listing_permalink', 1000 );
remove_filter( 'the_title', 'adv_filter_listing_title', 10 );
remove_filter( 'geodir_widget_post_title_output', 'adv_replace_gd_title_ad_text', 10 );
remove_filter( 'elementor/widget/render_content', 'adv_elementor_replace_gd_title_ad_text', 10, 2 );

if ( isset( $reset_post ) ) {
    $post = $reset_post;
    setup_postdata( $reset_post );
}

unset( $GLOBALS['adv_current_listing'] );
unset( $GLOBALS['adv_current_ad_id'] );
