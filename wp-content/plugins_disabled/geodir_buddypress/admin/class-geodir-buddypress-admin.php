<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://wpgeodirectory.com/
 * @since      2.0.0
 *
 * @package    GeoDir_BuddyPress
 * @subpackage GeoDir_BuddyPress/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    GeoDir_BuddyPress
 * @subpackage GeoDir_BuddyPress/admin
 * @author     GeoDirectory <info@wpgeodirectory.com>
 */
class GeoDir_BuddyPress_Admin {

    /**
     * Initialize the class and set its properties.
     *
     * @since    2.0.0
     */
    public function __construct() {

    }

    /**
     * Redirects user to review rating manager settings page after plugin activation.
     *
     * @since 2.0.0
     * @package GeoDir_BuddyPress
     */
    public function activation_redirect() {
        if (geodir_get_option('geodir_buddypress_activation_redirect', false)) {
            geodir_delete_option('geodir_buddypress_activation_redirect');
            wp_redirect(admin_url('admin.php?page=gd-settings&tab=gd-buddypress'));
        }
    }

    public function admin_scripts($hook){

    }

    public function admin_styles($hook) {

    }

    public function load_settings_page( $settings_pages ) {

        $post_type = ! empty( $_REQUEST['post_type'] ) ? sanitize_title( $_REQUEST['post_type'] ) : 'gd_place';
        if ( !( isset( $_REQUEST['page'] ) && $_REQUEST['page'] == $post_type.'-settings' ) ) {
            $settings_pages[] = include( GEODIR_BUDDYPRESS_PLUGIN_PATH . '/admin/settings/class-geodir-buddypress-settings.php' );
        }

        return $settings_pages;
    }

}
