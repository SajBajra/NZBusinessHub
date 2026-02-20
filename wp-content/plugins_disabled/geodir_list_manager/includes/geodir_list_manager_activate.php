<?php
/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since 2.0.0
 */
class GD_Lists_Activate {

    /**
     * Plugin activate.
     *
     * When plugin active then set global options in GD Lists Manager.
     *
     * @since  2.0.0
     */
    public static function activate(){

        /**
         * Crete a Add list page when plugin activate.
         *
         * @since 2.0.0
         */
        flush_rewrite_rules();
        wp_schedule_single_event( time(), 'geodir_flush_rewrite_rules' );

    }
}