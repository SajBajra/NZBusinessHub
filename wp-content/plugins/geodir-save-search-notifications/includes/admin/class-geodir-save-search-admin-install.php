<?php
/**
 * Save Search Notifications Installation
 *
 * @author    AyeCode Ltd
 * @package   GeoDir_Save_Search
 * @version   1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * GeoDir_Save_Search_Admin_Install class.
 */
class GeoDir_Save_Search_Admin_Install {
	/**
	 * @var array DB updates and callbacks that need to be run per version.
	 *
	 * @since 1.0
	 */
	private static $db_updates = array(
	);

	private static $background_updater;

	/**
	 * Hook in tabs.
	 *
	 * @since 1.0
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'check_version' ), 5 );
		add_action( 'init', array( __CLASS__, 'init_background_updater' ), 5 );
		add_action( 'admin_init', array( __CLASS__, 'install_actions' ) );
		add_filter( 'wpmu_drop_tables', array( __CLASS__, 'wpmu_drop_tables' ) );
	}

	/**
	 * Init background updates
	 *
	 * @since 1.0
	 */
	public static function init_background_updater() {
		if ( ! class_exists( 'GeoDir_Background_Updater' ) ) {
			include_once( GEODIRECTORY_PLUGIN_DIR . 'includes/class-geodir-background-updater.php' );
		}
		self::$background_updater = new GeoDir_Background_Updater();
	}

	/**
	 * Check plugin version and run the updater as required.
	 *
	 * This check is done on all requests and runs if the versions do not match.
	 *
	 * @since 1.0
	 */
	public static function check_version() {
		if ( ! defined( 'IFRAME_REQUEST' ) ) {
			if ( get_option( 'geodir_save_search_version' ) !== GEODIR_SAVE_SEARCH_VERSION ) {
				self::install();

				do_action( 'geodir_save_search_updated' );
			} else if ( is_admin() && ! wp_doing_ajax() && geodir_get_option( 'geodir_save_search_activation_hook' ) ) {
				geodir_delete_option( 'geodir_save_search_activation_hook' );

				// Reschedule
				geodir_save_search_schedule_events( time() + 300 );
			}
		}
	}

	/**
	 * Install actions when a update button is clicked within the admin area.
	 *
	 * This function is hooked into admin_init to affect admin only.
	 *
	 * @since 1.0
	 */
	public static function install_actions() {
		if ( ! empty( $_GET['do_update_geodir_save_search'] ) ) {
			self::update();
		}

		if ( ! empty( $_GET['force_update_geodir_save_search'] ) ) {
			$blog_id = get_current_blog_id();

			// Used to fire an action added in WP_Background_Process::_construct() that calls WP_Background_Process::handle_cron_healthcheck().
			// This method will make sure the database updates are executed even if cron is disabled. Nothing will happen if the updates are already running.
			do_action( 'wp_' . $blog_id . '_geodir_save_search_updater_cron' );

			wp_safe_redirect( admin_url( 'admin.php?page=gd-settings' ) );
			exit;
		}
	}

	/**
	 * Install plugin.
	 *
	 * @since 1.0
	 */
	public static function install() {
		global $wpdb;

		if ( ! is_blog_installed() ) {
			return;
		}

		if ( ! defined( 'GEODIR_SAVE_SEARCH_INSTALLING' ) ) {
			define( 'GEODIR_SAVE_SEARCH_INSTALLING', true );
		}

		self::create_tables();
		self::save_default_options();

		// Cron schedules
		self::cron_schedules();

		// Update GD version
		self::update_gd_version();

		// Update DB version
		self::maybe_update_db_version();

		// Flush rules after install
		do_action( 'geodir_save_search_flush_rewrite_rules' );

		// Trigger action
		do_action( 'geodir_save_search_installed' );
	}
	
	/**
	 * Is this a brand new GeoDirectory install?
	 *
	 * @since 1.0
	 *
	 * @return boolean
	 */
	private static function is_new_install() {
		return is_null( get_option( 'geodir_save_search_version', null ) ) && is_null( get_option( 'geodir_save_search_db_version', null ) );
	}

	/**
	 * Is a DB update needed?
	 *
	 * @since 1.0
	 *
	 * @return boolean
	 */
	private static function needs_db_update() {
		$current_db_version = get_option( 'geodir_save_search_db_version', null );
		$updates = self::get_db_update_callbacks();

		return ! is_null( $current_db_version ) && ! empty( $updates ) && version_compare( $current_db_version, max( array_keys( $updates ) ), '<' );
	}

	/**
	 * See if we need to show or run database updates during install.
	 *
	 * @since 1.0
	 */
	private static function maybe_update_db_version() {
		if ( self::needs_db_update() ) {
			self::update();
		} else {
			self::update_db_version();
		}
	}

	/**
	 * Update plugin version to current.
	 *
	 * @since 1.0
	 */
	private static function update_gd_version() {
		delete_option( 'geodir_save_search_version' );
		add_option( 'geodir_save_search_version', GEODIR_SAVE_SEARCH_VERSION );
	}

	/**
	 * Get list of DB update callbacks.
	 *
	 * @since 1.0
	 *
	 * @return array
	 */
	public static function get_db_update_callbacks() {
		return self::$db_updates;
	}

	/**
	 * Push all needed DB updates to the queue for processing.
	 *
	 * @since 1.0
	 */
	private static function update() {
		$current_db_version = get_option( 'geodir_save_search_db_version' );
		$update_queued = false;

		if ( empty( self::$background_updater ) ) {
			self::init_background_updater();
		}

		foreach ( self::get_db_update_callbacks() as $version => $update_callbacks ) {
			if ( version_compare( $current_db_version, $version, '<' ) ) {
				foreach ( $update_callbacks as $update_callback ) {
					geodir_error_log( sprintf( 'Queuing %s - %s', $version, $update_callback ) );
					self::$background_updater->push_to_queue( $update_callback );
					$update_queued = true;
				}
			}
		}

		if ( $update_queued ) {
			self::$background_updater->save()->dispatch();
		}
	}

	/**
	 * Schedule cron events.
	 *
	 * @since 1.0
	 */
	private static function cron_schedules() {
		geodir_save_search_schedule_events( time() + 10 );
	}

	/**
	 * Update DB version to current.
	 *
	 * @since 1.0
	 *
	 * @param string $version
	 */
	public static function update_db_version( $version = null ) {
		delete_option( 'geodir_save_search_db_version' );
		add_option( 'geodir_save_search_db_version', is_null( $version ) ? GEODIR_SAVE_SEARCH_VERSION : $version );
	}

	/**
	 * Default options.
	 *
	 * @since 1.0
	 *
	 * Sets up the default options used on the settings page.
	 */
	private static function save_default_options() {
		$email_user_save_search = geodir_get_option( 'email_user_save_search' );
		$email_user_save_search_edit = geodir_get_option( 'email_user_save_search_edit' );
		$interval = geodir_get_option( 'save_search_interval' );
		$save_search_loop = geodir_get_option( 'save_search_loop' );

		if ( $email_user_save_search === false || $email_user_save_search === null ) {
			geodir_update_option( 'email_user_save_search', 1 );
		}

		if ( $email_user_save_search_edit === false || $email_user_save_search_edit === null ) {
			geodir_update_option( 'email_user_save_search_edit', 1 );
		}

		if ( $interval === false || $interval === '' || $interval === null ) {
			geodir_update_option( 'save_search_interval', HOUR_IN_SECONDS * 6 );
		}

		if ( $save_search_loop === false || $save_search_loop === null ) {
			geodir_update_option( 'save_search_loop', 1 );
		}
	}

	/**
	 * Set up the database tables which the plugin needs to function.
	 *
	 * @since 1.0
	 */
	private static function create_tables() {
		global $wpdb;

		$wpdb->hide_errors();

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		dbDelta( self::get_schema() );

	}

	/**
	 * Get Table schema.
	 *
	 * A note on indexes; Indexes have a maximum size of 767 bytes. Historically, we haven't need to be concerned about that.
	 * As of WordPress 4.2, however, we moved to utf8mb4, which uses 4 bytes per character. This means that an index which
	 * used to have room for floor(767/3) = 255 characters, now only has room for floor(767/4) = 191 characters.
	 *
	 * Changing indexes may cause duplicate index notices in logs due to https://core.trac.wordpress.org/ticket/34870 but dropping
	 * indexes first causes too much load on some servers/larger DB.
	 *
	 * @since 1.0
	 *
	 * @return string
	 */
	private static function get_schema() {
		global $wpdb;

		/*
		 * Indexes have a maximum size of 767 bytes. Historically, we haven't need to be concerned about that.
		 * As of 4.2, however, we moved to utf8mb4, which uses 4 bytes per character. This means that an index which
		 * used to have room for floor(767/3) = 255 characters, now only has room for floor(767/4) = 191 characters.
		 */
		$max_index_length = 191;

		$charset_collate = $wpdb->get_charset_collate();

		// Database tables
		$tables = "CREATE TABLE `" . GEODIR_SAVE_SEARCH_EMAILS_TABLE . "` (
 `email_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
 `subscriber_id` bigint(20) UNSIGNED NOT NULL DEFAULT '0',
 `post_id` bigint(20) UNSIGNED NOT NULL DEFAULT '0',
 `post_title` text NOT NULL,
 `post_url` varchar(250) NOT NULL,
 `email_action` varchar(50) NOT NULL,
 `date_added` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
 `date_sent` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
 `status` varchar(20) NOT NULL,
 PRIMARY KEY (`email_id`),
 KEY subscriber_id (`subscriber_id`),
 KEY post_id (`post_id`)
) {$charset_collate};
CREATE TABLE `" . GEODIR_SAVE_SEARCH_FIELDS_TABLE . "` (
 `search_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
 `subscriber_id` bigint(20) UNSIGNED NOT NULL DEFAULT '0',
 `field_name` varchar(100) NOT NULL,
 `field_value` text NOT NULL,
 PRIMARY KEY (`search_id`),
 KEY subscriber_id (`subscriber_id`),
 KEY field_name (field_name(100))
) {$charset_collate};
CREATE TABLE `" . GEODIR_SAVE_SEARCH_SUBSCRIBERS_TABLE . "` (
 `subscriber_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
 `user_id` bigint(20) UNSIGNED NOT NULL DEFAULT '0',
 `user_email` varchar(100) NOT NULL,
 `user_name` varchar(250) NOT NULL,
 `post_type` varchar(50) NOT NULL,
 `search_name` varchar(100) NOT NULL,
 `search_uri` text NOT NULL,
 `date_added` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
 PRIMARY KEY (`subscriber_id`),
 KEY user_id (`user_id`),
 KEY user_email (user_email(100)),
 KEY post_type (post_type(50))
) {$charset_collate};";

		return $tables;
	}

	/**
	 * Uninstall tables when MU blog is deleted.
	 *
	 * @since 1.0
	 *
	 * @param  array $tables
	 * @return string[]
	 */
	public static function wpmu_drop_tables( $tables ) {
		global $wpdb;

		$tables['geodir_save_search_emails'] = "{$wpdb->prefix}geodir_save_search_emails";
		$tables['geodir_save_search_fields'] = "{$wpdb->prefix}geodir_save_search_fields";
		$tables['geodir_save_search_subscribers'] = "{$wpdb->prefix}geodir_save_search_subscribers";

		return $tables;
	}
}
