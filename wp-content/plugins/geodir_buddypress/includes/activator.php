<?php

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      2.0.0
 * @package    GeoDir_BuddyPress
 * @subpackage GeoDir_BuddyPress/includes
 * @author     GeoDirectory Team <info@wpgeodirectory.com>
 */

class GeoDir_BuddyPress_Activator
{

    public static function activate($network_wide = false)
    {
        global $wpdb;

        if ( is_multisite() && $network_wide ) {
            foreach ( $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs LIMIT 100" ) as $blog_id ) {
                switch_to_blog( $blog_id );

                $updated = self::run_install();

                do_action( 'geodir_buddypress_network_activate', $blog_id, $updated );

                restore_current_blog();
            }
        } else {
            $updated = self::run_install();

            do_action( 'geodir_buddypress_activate', $updated );
        }

        // Bail if activating from network, or bulk
        if ( is_network_admin() || isset( $_GET['activate-multi'] ) ) {
            return;
        }
    }

    /**
     * Short Description.
     *
     * Long Description.
     *
     * @since    1.0.0
     */
    public function deactivate() {
        do_action( 'geodir_buddypress_deactivate' );
    }

    public static function run_install() {

        $current_version = geodir_get_option( 'geodir_buddypress_version' );

        if ( $current_version ) {
            geodir_update_option( 'geodir_buddypress_version_upgraded_from', $current_version );
        }

        geodir_update_option('geodir_buddypress_activation_redirect', 1);

        geodir_update_option( 'geodir_buddypress_version', GEODIR_BUDDYPRESS_VERSION );

        do_action( 'geodir_buddypress_install' );

        return true;
    }
}