<?php
/**
 * Tickets database class.
 *
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Tickets installer/updater class.
 *
 */
class GeoDir_Tickets_Installer {

    /**
     * Class constructor.
     * 
     * @param int $upgrade_from The current database version.
     */
    public function __construct( $upgrade_from ) {
        
        $method = "upgrade_from_$upgrade_from";

        if ( method_exists( $this, $method ) ) {
            $this->$method();
        }

    }

    /**
     * Do a fresh install.
     * 
     */
    public function upgrade_from_0() {
        global $wpdb;

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

        // Create table.
        $table           = $wpdb->prefix . 'geodir_tickets';
        $charset_collate = $wpdb->get_charset_collate();
        $sql             = "CREATE TABLE $table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            type varchar(100) NOT NULL DEFAULT 'default',
            status varchar(100) NOT NULL DEFAULT 'pending',
            event_id bigint(20) NOT NULL,
            seller_id bigint(20) NOT NULL,
            buyer_id bigint(20) NOT NULL,
            invoice_id bigint(20) NOT NULL,
            date_created datetime NOT NULL,
            date_used datetime NOT NULL,
            price float(20) NOT NULL DEFAULT 0,
            seller_price float(20) NOT NULL DEFAULT 0,
            site_commision float(20) NOT NULL DEFAULT 0,
            PRIMARY KEY  (id),
            KEY status (status)
            ) $charset_collate;";

        dbDelta( $sql );

    }

}
