<?php

/**
 * Zone display rules
 *
 * Display the zone display rules meta box.
 *
 */

defined( 'ABSPATH' ) || exit;

/**
 * Adv_Meta_Box_Zone_Display_Rules Class.
 */
class Adv_Meta_Box_Zone_Display_Rules {

    /**
	 * Output the metabox.
	 *
	 * @param WP_Post $post
	 */
    public static function output( $post ) {

		$GLOBALS['adv_post'] = $post;

        do_action( 'adv_zone_display_rules_meta_box_top', $post );
		echo adv_show_tabs( array( 'tabs' => adv_get_display_rules() ) );
		do_action( 'adv_zone_display_rules_meta_box_bottom', $post );

    }

}