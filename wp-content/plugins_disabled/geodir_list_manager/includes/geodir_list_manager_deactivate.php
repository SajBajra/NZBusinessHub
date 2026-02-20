<?php
/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since 2.0.0
 */
class GD_Lists_Deactivate{

    /**
     * Plugin deactivation.
     *
     * @since  2.0.0
     */
    public static function deactivate() {
        wp_schedule_single_event( time(), 'geodir_flush_rewrite_rules' );
    }
}