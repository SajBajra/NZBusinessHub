<?php
/**
 * Bookings database class.
 *
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Bookings installer/updater class.
 *
 */
class GeoDir_Booking_Installer {

	/**
	 * Class constructor.
	 *
	 * @param int $upgrade_from The current database version.
	 */
	public function __construct( $upgrade_from ) {

		if ( null !== $upgrade_from ) {
			$method = "upgrade_from_$upgrade_from";

			// Create the bookings tables.
			$this->create_tables();

			if ( method_exists( $this, $method ) ) {
				$this->$method();
			}
		}
	}

	/**
	 * Do a fresh install.
	 *
	 */
	public function upgrade_from_0() {

		// Create booking item.
		geodir_booking_item();
	}

	/**
	 * Update bookings table.
	 *
	 */
	public function upgrade_from_2() {
		global $wpdb, $plugin_prefix;

		// add cleaning, pet & extra guests fee column.
		$wpdb->query( "ALTER TABLE {$wpdb->prefix}gdbc_rulesets ADD COLUMN `cleaning_fee` DECIMAL(26,8) UNSIGNED NOT NULL DEFAULT 0" );
		$wpdb->query( "ALTER TABLE {$wpdb->prefix}gdbc_rulesets ADD COLUMN `pet_fee` DECIMAL(26,8) UNSIGNED NOT NULL DEFAULT 0" );
		$wpdb->query( "ALTER TABLE {$wpdb->prefix}gdbc_rulesets ADD COLUMN `extra_guest_count` INT(10) NOT NULL DEFAULT 0" );
		$wpdb->query( "ALTER TABLE {$wpdb->prefix}gdbc_rulesets ADD COLUMN `extra_guest_fee` DECIMAL(26,8) UNSIGNED NOT NULL DEFAULT 0" );

		// cancellation policy fix.
		$wp_gd_place_detail = $plugin_prefix . 'gd_place_detail';
		$wpdb->query( "ALTER TABLE {$wp_gd_place_detail} ADD COLUMN `gdb_cancellation_policy` LONGTEXT DEFAULT NULL" );

		$wp_gdbc_bookings = $wpdb->prefix . 'gdbc_bookings';

		// add adults, children, infants, pets column.
		$wpdb->query( "ALTER TABLE {$wp_gdbc_bookings} ADD COLUMN `adults` INT(11) NOT NULL DEFAULT 1" );
		$wpdb->query( "ALTER TABLE {$wp_gdbc_bookings} ADD COLUMN `children` INT(11) NOT NULL DEFAULT 0" );
		$wpdb->query( "ALTER TABLE {$wp_gdbc_bookings} ADD COLUMN `infants` INT(11) NOT NULL DEFAULT 0" );
		$wpdb->query( "ALTER TABLE {$wp_gdbc_bookings} ADD COLUMN `pets` INT(11) NOT NULL DEFAULT 0" );

		// cleaning, pet & extra guests fee columns.
		$wpdb->query( "ALTER TABLE {$wp_gdbc_bookings} ADD COLUMN `cleaning_fee` DECIMAL(26,8) UNSIGNED NOT NULL DEFAULT 0" );
		$wpdb->query( "ALTER TABLE {$wp_gdbc_bookings} ADD COLUMN `pet_fee` DECIMAL(26,8) UNSIGNED NOT NULL DEFAULT 0" );
		$wpdb->query( "ALTER TABLE {$wp_gdbc_bookings} ADD COLUMN `extra_guest_fee` DECIMAL(26,8) UNSIGNED NOT NULL DEFAULT 0" );

		return false;
	}

	public function upgrade_from_3() {
		global $wpdb;

		$wp_gdbc_bookings = $wpdb->prefix . 'gdbc_bookings';

		// add total discount.
		$wpdb->query( "ALTER TABLE {$wp_gdbc_bookings} ADD COLUMN `total_discount` DECIMAL(26,8) UNSIGNED NOT NULL DEFAULT 0" );
		$wpdb->query( "ALTER TABLE {$wp_gdbc_bookings} ADD COLUMN `uid` VARCHAR(255) DEFAULT NULL" );
		$wpdb->query( "ALTER TABLE {$wp_gdbc_bookings} ADD COLUMN `sync_id` VARCHAR(250) DEFAULT NULL" );
		$wpdb->query( "ALTER TABLE {$wp_gdbc_bookings} ADD COLUMN `sync_queue_id` BIGINT(20) UNSIGNED DEFAULT NULL" );

		$wp_gdbc_day_rules = $wpdb->prefix . 'gdbc_day_rules';

		// ical field columns.
		$wpdb->query( "ALTER TABLE {$wp_gdbc_day_rules} ADD COLUMN `uid` VARCHAR(255) DEFAULT NULL" );
		$wpdb->query( "ALTER TABLE {$wp_gdbc_day_rules} ADD COLUMN `sync_id` VARCHAR(255) DEFAULT NULL" );
		$wpdb->query( "ALTER TABLE {$wp_gdbc_day_rules} ADD COLUMN `sync_queue_id` BIGINT(20) UNSIGNED DEFAULT NULL" );
		$wpdb->query( "ALTER TABLE {$wp_gdbc_day_rules} ADD COLUMN `ical_prodid` VARCHAR(255) DEFAULT NULL" );
		$wpdb->query( "ALTER TABLE {$wp_gdbc_day_rules} ADD COLUMN `ical_summary` TEXT DEFAULT NULL" );
		$wpdb->query( "ALTER TABLE {$wp_gdbc_day_rules} ADD COLUMN `ical_description` TEXT DEFAULT NULL" );

		return false;
	}

	/**
	 * Retrieves the database schema.
	 *
	 * @return string
	 */
	public function get_schema() {
		global $wpdb, $plugin_prefix;

		$charset_collate = '';

		if ( $wpdb->has_cap( 'collation' ) ) {
			$charset_collate = $wpdb->get_charset_collate();
		}

		// Price rules.
		$sql = "CREATE TABLE {$wpdb->prefix}gdbc_rulesets (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			listing_id BIGINT(20) UNSIGNED NOT NULL,
			nightly_price DECIMAL(26,8) UNSIGNED NOT NULL DEFAULT 1,
			cleaning_fee DECIMAL(26,8) UNSIGNED NOT NULL DEFAULT 0,
			pet_fee DECIMAL(26,8) UNSIGNED NOT NULL DEFAULT 0,
            extra_guest_count INT(10) NOT NULL DEFAULT 0,
            extra_guest_fee DECIMAL(26,8) UNSIGNED NOT NULL DEFAULT 0,
			restricted_check_in_days TEXT NOT NULL,
			restricted_check_out_days TEXT NOT NULL,
			minimum_stay INT NULL DEFAULT NULL,
			maximum_stay INT NULL DEFAULT NULL,
			per_day_minimum_stay TINYINT(1) NOT NULL DEFAULT 0,
			monday_minimum_stay INT NULL DEFAULT NULL,
			tuesday_minimum_stay INT NULL DEFAULT NULL,
			wednesday_minimum_stay INT NULL DEFAULT NULL,
			thursday_minimum_stay INT NULL DEFAULT NULL,
			friday_minimum_stay INT NULL DEFAULT NULL,
			saturday_minimum_stay INT NULL DEFAULT NULL,
			sunday_minimum_stay INT NULL DEFAULT NULL,
			early_bird_discounts TEXT NULL,
			last_minute_discounts TEXT NULL,
			duration_discounts TEXT NULL,
			PRIMARY KEY  (id),
			KEY listing_id (listing_id),
			KEY nightly_price (nightly_price)
        ) $charset_collate;";

		// Day rules.
		$sql .=
			"CREATE TABLE {$wpdb->prefix}gdbc_day_rules (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			listing_id BIGINT(20) UNSIGNED NOT NULL,
			nightly_price DECIMAL(26,8) UNSIGNED NOT NULL DEFAULT 1,
			is_available TINYINT(1) NOT NULL DEFAULT 1,
			rule_date DATE NOT NULL,
			private_note text NOT NULL,
            uid VARCHAR(255) DEFAULT NULL,
            sync_id VARCHAR(255) DEFAULT NULL,
            sync_queue_id BIGINT(20) UNSIGNED DEFAULT NULL,
            ical_prodid VARCHAR(255) DEFAULT NULL,
            ical_summary TEXT DEFAULT NULL,
            ical_description TEXT DEFAULT NULL,
			PRIMARY KEY  (id),
			KEY listing_id (listing_id)
        ) $charset_collate;";

		// Availability database table.
		$sql .= "CREATE TABLE {$wpdb->prefix}gdbc_availability (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			post_id BIGINT(20) UNSIGNED NOT NULL,
			owner_id BIGINT(20) UNSIGNED NOT NULL,
			year INT(4) DEFAULT NULL, \n";

		// Store a slot for day 1 to 366.
		for ( $i = 1; $i <= 366; $i++ ) {
			$sql .= "d{$i} BIGINT(20) UNSIGNED NULL DEFAULT NULL, \n"; // Null, not booked. 0 not bookable. Other, Booking ID.
		}

		$sql .= "PRIMARY KEY  (id),
				KEY post_id (post_id),
				KEY owner_id (owner_id)
				) $charset_collate;";

		// Bookings.
		$sql .= "CREATE TABLE {$wpdb->prefix}gdbc_bookings (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			listing_id BIGINT(20) UNSIGNED NOT NULL,
			invoice_id BIGINT(20) UNSIGNED NULL,
			guests INT(11) NOT NULL DEFAULT 1,
			adults INT(11) NOT NULL DEFAULT 1,
			children INT(11) NOT NULL DEFAULT 0,
			infants INT(11) NOT NULL DEFAULT 0,
			pets INT(11) NOT NULL DEFAULT 0,
			name VARCHAR(255) NOT NULL,
			email VARCHAR(255) NOT NULL,
			phone VARCHAR(20) NOT NULL,
			start_date DATE NOT NULL,
			end_date DATE NOT NULL,
			early_bird_discount_ge DECIMAL(5,2) UNSIGNED NOT NULL DEFAULT 0,
			last_minute_discount_ge DECIMAL(5,2) UNSIGNED NOT NULL DEFAULT 0,
			duration_discount_ge DECIMAL(5,2) UNSIGNED NOT NULL DEFAULT 0,
			total_amount DECIMAL(26,8) UNSIGNED NOT NULL DEFAULT 0,
            total_discount DECIMAL(26,8) UNSIGNED NOT NULL DEFAULT 0,
			payable_amount DECIMAL(26,8) UNSIGNED NOT NULL DEFAULT 0,
			service_fee DECIMAL(26,8) UNSIGNED NOT NULL DEFAULT 0,
			cleaning_fee DECIMAL(26,8) UNSIGNED NOT NULL DEFAULT 0,
            pet_fee DECIMAL(26,8) UNSIGNED NOT NULL DEFAULT 0,
            extra_guest_fee DECIMAL(26,8) UNSIGNED NOT NULL DEFAULT 0,
			deposit_amount DECIMAL(26,8) UNSIGNED NOT NULL DEFAULT 0,
			site_commission DECIMAL(26,8) UNSIGNED NOT NULL DEFAULT 0,
			date_amounts TEXT NOT NULL,
			status VARCHAR(20) NOT NULL DEFAULT 'draft',
            uid VARCHAR(255) DEFAULT NULL,
			sync_id VARCHAR(255) DEFAULT NULL,
			sync_queue_id BIGINT(20) UNSIGNED DEFAULT NULL,
			created DATETIME NOT NULL,
			modified DATETIME NOT NULL,
			private_note text NOT NULL,
			PRIMARY KEY  (id),
			KEY listing_id (listing_id),
			KEY invoice_id (invoice_id),
			KEY start_date (start_date),
			KEY end_date (end_date),
			KEY status (status)
        ) $charset_collate;";

		// sync urls
		$sql .= "CREATE TABLE {$wpdb->prefix}gdbc_sync_urls (
            `url_id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			`listing_id` BIGINT(20) UNSIGNED NOT NULL,
			`sync_id` VARCHAR(255) NOT NULL,
			`calendar_url` VARCHAR(255) NOT NULL,
            PRIMARY KEY  (url_id),
			KEY listing_id (listing_id),
			KEY calendar_url (calendar_url)
        ) $charset_collate;";

		// sync queue
		$sql .= "CREATE TABLE {$wpdb->prefix}gdbc_sync_queue (
            `queue_id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			`queue_name` TINYTEXT NOT NULL,
			`queue_status`  VARCHAR(30) NOT NULL,
            PRIMARY KEY  (queue_id)
        ) $charset_collate;";

		// sync stats
		$sql .= "CREATE TABLE {$wpdb->prefix}gdbc_sync_stats (
            `stat_id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			`queue_id` BIGINT(20) UNSIGNED NOT NULL,
			`import_total` INT(10) NOT NULL DEFAULT 0,
			`import_succeed` INT(10) NOT NULL DEFAULT 0,
			`import_skipped` INT(10) NOT NULL DEFAULT 0,
			`import_failed` INT(10) NOT NULL DEFAULT 0,
			`clean_total` INT(10) NOT NULL DEFAULT 0,
			`clean_done` INT(10) NOT NULL DEFAULT 0,
			`clean_skipped` INT(10) NOT NULL DEFAULT 0,
            PRIMARY KEY  (stat_id),
            UNIQUE KEY queue_id (queue_id)
        ) $charset_collate;";

		// sync logs
		$sql .= "CREATE TABLE {$wpdb->prefix}gdbc_sync_logs (
            `log_id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			`queue_id` BIGINT(20) UNSIGNED NOT NULL,
			`log_status` VARCHAR(30) NOT NULL,
            `log_message` TEXT DEFAULT NULL,
            PRIMARY KEY  (log_id),
            KEY queue_id (queue_id)
        ) $charset_collate;";

		return $sql;
	}

	/**
	 * Creates/Updates database tables.
	 *
	 */
	public function create_tables() {
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $this->get_schema() );
	}
}
