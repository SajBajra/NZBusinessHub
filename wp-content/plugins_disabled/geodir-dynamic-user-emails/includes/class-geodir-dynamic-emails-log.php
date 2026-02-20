<?php
/**
 * Dynamic User Emails Log class
 *
 * @author    AyeCode Ltd
 * @package   GeoDir_Dynamic_Emails
 * @version   2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * GeoDir_Dynamic_Emails_Log class.
 */
class GeoDir_Dynamic_Emails_Log {
	/**
	 * Init.
	 *
	 * @since 2.0.0
	 */
	public static function init() {
		add_action( 'geodir_dynamic_emails_list_deleted_item', array( __CLASS__, 'delete_items_by_list' ), 10, 2 );
	}

	public static function save_item( $args ) {
		global $wpdb;

		$orig_args = $args;

		$defaults = array(
			'email_list_id' => 0,
			'date_sent' => current_time( 'mysql' )
		);

		$args = wp_parse_args( $args, $defaults );

		$data = array(
			'email_list_id' => (int) $args['email_list_id'],
			'date_sent' => $args['date_sent']
		);

		if ( false === $wpdb->insert( GEODIR_DYNAMIC_EMAILS_LOG_TABLE, $data, array( '%d', '%s' ) ) ) {
				$message = __( 'Could not save log data into the database.', 'geodir-dynamic-emails' );

			return new WP_Error( 'db_insert_error', $message );
		}

		$insert_id = (int) $wpdb->insert_id;

		do_action( 'geodir_dynamic_emails_log_saved', $insert_id, $data, $args, $orig_args );

		return $insert_id;
	}

	public static function get_item( $id ) {
		global $wpdb;

		$item = geodir_cache_get( (int) $id, 'geodir_email_log' );

		if ( $item === false ) {
			$item = $wpdb->get_row( $wpdb->prepare( "SELECT `log`.*,`list`.* FROM `" . GEODIR_DYNAMIC_EMAILS_LOG_TABLE . "` AS `log` LEFT JOIN `" . GEODIR_DYNAMIC_EMAILS_LISTS_TABLE . "` AS `list` ON `list`.`email_list_id` = `log`.`email_list_id` WHERE `log`.`email_log_id` = %d LIMIT 1", array( (int) $id ) ) );

			geodir_cache_set( (int) $id, $item, 'geodir_email_log' );
		}

		return $item;
	}

	public static function get_items_by( $key, $value ) {
		global $wpdb;

		$results = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM `" . GEODIR_DYNAMIC_EMAILS_LOG_TABLE . "` WHERE `{$key}` = %s", array( $value ) ) );

		return $results;
	}

	public static function delete_item( $item_id ) {
		global $wpdb;

		$item = self::get_item( $item_id );

		if ( empty( $item ) ) {
			return false;
		}

		$check = apply_filters( 'geodir_dynamic_emails_log_pre_delete_item', null, $item_id, $item );

		if ( null !== $check ) {
			return $check;
		}

		do_action( 'geodir_dynamic_emails_log_before_delete_item', $item_id, $item );

		$result = $wpdb->delete( GEODIR_DYNAMIC_EMAILS_LOG_TABLE, array( 'email_log_id' => $item_id ) );

		if ( ! $result ) {
			return false;
		}

		do_action( 'geodir_dynamic_emails_log_deleted_item', $item_id, $item );

		self::clean_item_cache( $item );

		return true;
	}

	public static function delete_items_by_list( $email_list_id, $email_list ) {
		global $wpdb;

		$items = self::get_items_by( 'email_list_id', $email_list_id );

		if ( empty( $items ) ) {
			return false;
		}

		$check = apply_filters( 'geodir_dynamic_emails_log_pre_delete_items_by_list', null, $items, $email_list_id, $email_list );

		if ( null !== $check ) {
			return $check;
		}

		do_action( 'geodir_dynamic_emails_log_before_delete_items_by_list', $items, $email_list_id, $email_list );

		$result = $wpdb->delete( GEODIR_DYNAMIC_EMAILS_LOG_TABLE, array( 'email_list_id' => $email_list_id ) );

		if ( ! $result ) {
			return false;
		}

		do_action( 'geodir_dynamic_emails_log_deleted_items_by_list', $items, $email_list_id, $email_list );

		geodir_cache_flush_group( 'geodir_email_log' );

		return true;
	}

	public static function clean_item_cache( $item ) {
		if ( is_object( $item ) && ! empty( $item->email_log_id ) ) {
			$item_id = (int) $item->email_log_id;
		} else if ( is_array( $item ) && ! empty( $item['email_log_id'] ) ) {
			$item_id = (int) $item['email_log_id'];
		} else if ( is_scalar( $item ) && (int) $item > 0 ) {
			$item_id = (int) $item;
		} else {
			return;
		}

		geodir_cache_delete( (int) $item_id, 'geodir_email_log' );
	}
}