<?php
/**
 * Dynamic User Emails installation class
 *
 * @author    AyeCode Ltd
 * @package   GeoDir_Dynamic_Emails
 * @version   2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * GeoDir_Dynamic_Emails_Admin_Install class.
 */
class GeoDir_Dynamic_Emails_Admin_Install {
	/**
	 * @var array DB updates and callbacks that need to be run per version.
	 *
	 * @since 2.0.0
	 */
	private static $db_updates = array(
	);

	private static $background_updater;

	/**
	 * Hook in tabs.
	 *
	 * @since 2.0.0
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
	 * @since 2.0.0
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
	 * @since 2.0.0
	 */
	public static function check_version() {
		if ( ! defined( 'IFRAME_REQUEST' ) ) {
			if ( get_option( 'geodir_dynamic_emails_version' ) !== GEODIR_DYNAMIC_EMAILS_VERSION ) {
				self::install();

				do_action( 'geodir_dynamic_emails_updated' );
			} else if ( is_admin() && ! wp_doing_ajax() && geodir_get_option( 'geodir_dynamic_activation_hook' ) ) {
				geodir_delete_option( 'geodir_dynamic_activation_hook' );

				// Reschedule
				geodir_dynamic_emails_schedule_events( time() + 180 );
			}
		}
	}

	/**
	 * Install actions when a update button is clicked within the admin area.
	 *
	 * This function is hooked into admin_init to affect admin only.
	 *
	 * @since 2.0.0
	 */
	public static function install_actions() {
		if ( ! empty( $_GET['do_update_geodir_dynamic_emails'] ) ) {
			self::update();
		}

		if ( ! empty( $_GET['force_update_geodir_dynamic_emails'] ) ) {
			$blog_id = get_current_blog_id();

			// Used to fire an action added in WP_Background_Process::_construct() that calls WP_Background_Process::handle_cron_healthcheck().
			// This method will make sure the database updates are executed even if cron is disabled. Nothing will happen if the updates are already running.
			do_action( 'wp_' . $blog_id . '_geodir_dynamic_emails_updater_cron' );

			wp_safe_redirect( admin_url( 'admin.php?page=gd-settings' ) );
			exit;
		}
	}

	/**
	 * Install plugin.
	 *
	 * @since 2.0.0
	 */
	public static function install() {
		global $wpdb;

		if ( ! is_blog_installed() ) {
			return;
		}

		if ( ! defined( 'GEODIR_DYNAMIC_EMAILS_INSTALLING' ) ) {
			define( 'GEODIR_DYNAMIC_EMAILS_INSTALLING', true );
		}

		self::create_tables();
		self::save_default_options();
		self::add_example_data();

		// Cron schedules
		self::cron_schedules();

		// Update GD version
		self::update_gd_version();

		// Update DB version
		self::maybe_update_db_version();

		// Flush rules after install
		do_action( 'geodir_dynamic_emails_flush_rewrite_rules' );

		// Trigger action
		do_action( 'geodir_dynamic_emails_installed' );
	}
	
	/**
	 * Is this a brand new GeoDirectory install?
	 *
	 * @since 2.0.0
	 *
	 * @return boolean
	 */
	private static function is_new_install() {
		return is_null( get_option( 'geodir_dynamic_emails_version', null ) ) && is_null( get_option( 'geodir_dynamic_emails_db_version', null ) );
	}

	/**
	 * Is a DB update needed?
	 *
	 * @since 2.0.0
	 *
	 * @return boolean
	 */
	private static function needs_db_update() {
		$current_db_version = get_option( 'geodir_dynamic_emails_db_version', null );
		$updates = self::get_db_update_callbacks();

		return ! is_null( $current_db_version ) && ! empty( $updates ) && version_compare( $current_db_version, max( array_keys( $updates ) ), '<' );
	}

	/**
	 * See if we need to show or run database updates during install.
	 *
	 * @since 2.0.0
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
	 * @since 2.0.0
	 */
	private static function update_gd_version() {
		delete_option( 'geodir_dynamic_emails_version' );
		add_option( 'geodir_dynamic_emails_version', GEODIR_DYNAMIC_EMAILS_VERSION );
	}

	/**
	 * Get list of DB update callbacks.
	 *
	 * @since 2.0.0
	 *
	 * @return array
	 */
	public static function get_db_update_callbacks() {
		return self::$db_updates;
	}

	/**
	 * Push all needed DB updates to the queue for processing.
	 *
	 * @since 2.0.0
	 */
	private static function update() {
		$current_db_version = get_option( 'geodir_dynamic_emails_db_version' );
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
	 * @since 2.0.0
	 */
	private static function cron_schedules() {
		geodir_dynamic_emails_schedule_events( time() + 180 );
	}

	/**
	 * Update DB version to current.
	 *
	 * @since 2.0.0
	 *
	 * @param string $version
	 */
	public static function update_db_version( $version = null ) {
		delete_option( 'geodir_dynamic_emails_db_version' );
		add_option( 'geodir_dynamic_emails_db_version', is_null( $version ) ? GEODIR_DYNAMIC_EMAILS_VERSION : $version );
	}

	/**
	 * Add example data for dynamic emails
	 *
	 * This method adds example data for dynamic emails if it does not already exist.
	 *
	 * @return void
	 * @since 2.0.0
	 */
	private static function add_example_data() {
		if ( ! get_option( 'geodir_dynamic_email_examples', false ) ) {
			// Welcome email example
			$data = array(
				'email_list_id' => '',
				'name'          => 'Welcome Email Example',
				'action'        => 'user_register',
				'post_type'     => '',
				'category'      => '',
				'user_roles'    => '',
				'subject'       => 'Welcome to [[#site_name#]]',
				'template'      => 'Hello [#to_name#],

Thanks for registering on our site!  Please take time to have a look around and contact us if you have any questions :)

Thank You.',
				'meta'          => '',
				'status'        => 'pending'
			);

			$data = apply_filters( 'geodir_dynamic_emails_save_email_list_data', $data );

			GeoDir_Dynamic_Emails_List::save_item( $data );


			// Keep listing updated reminder
			$data = array(
				'email_list_id' => '',
				'name'          => 'Keep listing updated reminder - Example',
				'action'        => 'instant',
				'post_type'     => 'gd_place',
				'category'      => '',
				'user_roles'    => '',
				'subject'       => '[[#site_name#]] Are your listing details still correct?',
				'template'      => 'Hello [#to_name#],

It has been more then 6 months since you last updated your listing [#listing_title#], now might be a good time to check if your details are still all correct.
You can view your listing here: [#listing_link#]
If you need to change any details be sure to [#login_link#] and then visit your listing and click the "Edit" button.

Thank You.',
				'status'        => 'pending'
			);

			$_fields[1] = array(
				'field' => 'post_modified',
				'condition'  => 'is_less_than',
				'search'    => '-6 months'
			);

			$data['meta']['fields'] = GeoDir_Dynamic_Emails_List::process_conditional_fields( $_fields );

			$data = apply_filters( 'geodir_dynamic_emails_save_email_list_data', $data );

			GeoDir_Dynamic_Emails_List::save_item( $data );

			// prevent example data installing again
			update_option( 'geodir_dynamic_email_examples', true );
		}

	}

	/**
	 * Default options.
	 *
	 * @since 2.0.0
	 *
	 * Sets up the default options used on the settings page.
	 */
	private static function save_default_options() {
		$email_user_dynamic_emails = geodir_get_option( 'email_user_dynamic_emails' );

		if ( $email_user_dynamic_emails === false || $email_user_dynamic_emails === null ) {
			geodir_update_option( 'email_user_dynamic_emails', 1 );
		}
	}

	/**
	 * Set up the database tables which the plugin needs to function.
	 *
	 * @since 2.0.0
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
	 * @since 2.0.0
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
		$tables = "CREATE TABLE `" . GEODIR_DYNAMIC_EMAILS_LISTS_TABLE . "` (
 `email_list_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
 `action` varchar(250) NOT NULL,
 `name` text NOT NULL,
 `post_type` varchar(50) NOT NULL,
 `category` text NOT NULL,
 `user_roles` text NOT NULL,
 `subject` text NOT NULL,
 `template` text NOT NULL,
 `meta` text NOT NULL,
 `date_added` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
 `status` varchar(20) NOT NULL,
 PRIMARY KEY (`email_list_id`),
 KEY action (action({$max_index_length})),
 KEY post_type (post_type(50))
) {$charset_collate};
CREATE TABLE `" . GEODIR_DYNAMIC_EMAILS_LOG_TABLE . "` (
 `email_log_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
 `email_list_id` bigint(20) UNSIGNED NOT NULL DEFAULT '0',
 `date_sent` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
 PRIMARY KEY (`email_log_id`),
 KEY email_list_id (`email_list_id`)
) {$charset_collate};
CREATE TABLE `" . GEODIR_DYNAMIC_EMAILS_USERS_TABLE . "` (
 `email_user_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
 `email_list_id` bigint(20) UNSIGNED NOT NULL DEFAULT '0',
 `email_log_id` bigint(20) UNSIGNED NOT NULL DEFAULT '0',
 `user_id` bigint(20) UNSIGNED NOT NULL DEFAULT '0',
 `post_type` varchar(50) NOT NULL,
 `post_id` bigint(20) UNSIGNED NOT NULL DEFAULT '0',
 `date_added` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
 `date_sent` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
 `status` varchar(20) NOT NULL,
 `meta` text NOT NULL,
 PRIMARY KEY (`email_user_id`),
 KEY email_list_id (`email_list_id`),
 KEY email_log_id (`email_log_id`),
 KEY user_id (`user_id`),
 KEY post_type (post_type(50)),
 KEY post_id (`post_id`)
) {$charset_collate};";

		return $tables;
	}

	/**
	 * Uninstall tables when MU blog is deleted.
	 *
	 * @since 2.0.0
	 *
	 * @param  array $tables
	 * @return string[]
	 */
	public static function wpmu_drop_tables( $tables ) {
		global $wpdb;

		$tables['geodir_email_lists'] = "{$wpdb->prefix}geodir_email_lists";
		$tables['geodir_email_log'] = "{$wpdb->prefix}geodir_email_log";
		$tables['geodir_email_users'] = "{$wpdb->prefix}geodir_email_users";

		return $tables;
	}
}
