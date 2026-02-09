<?php
/**
 * Dynamic User Emails User class
 *
 * @author    AyeCode Ltd
 * @package   GeoDir_Dynamic_Emails
 * @version   2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * GeoDir_Dynamic_Emails_User class.
 */
class GeoDir_Dynamic_Emails_User {
	/**
	 * Init.
	 *
	 * @since 2.0.0
	 */
	public static function init() {
		add_action( 'geodir_dynamic_emails_list_deleted_item', array( __CLASS__, 'delete_items_by_list' ), 10, 2 );
		add_action( 'geodir_dynamic_emails_log_deleted_item', array( __CLASS__, 'delete_items_by_log' ), 10, 2 );
		add_action( 'deleted_user', array( __CLASS__, 'delete_items_by_user' ), 10, 3 );
		add_action( 'delete_post', array( __CLASS__, 'delete_items_by_post' ), 10, 2 );
	}

	public static function get_display_name( $user ) {
		$user_name = '';

		if ( ! empty( $user ) ) {
			if ( isset( $user->display_name ) && trim( $user->display_name ) != '' ) {
				$user_name = trim( $user->display_name );
			} else if ( isset( $user->user_nicename ) && trim( $user->user_nicename ) != '' ) {
				$user_name = trim( $user->user_nicename );
			} else {
				$user_name = trim( $user->user_login );
			}
		}

		return $user_name;
	}

	public static function get_userdata( $user_id ) {
		global $geodir_de_userdata;

		if ( empty( $geodir_de_userdata ) ) {
			$geodir_de_userdata = array();
		}

		if ( ! empty( $geodir_de_userdata[ $user_id ] ) ) {
			$userdata = $geodir_de_userdata[ $user_id ];
		} else {
			$userdata = get_userdata( $user_id );

			if ( isset( $userdata->data->user_pass ) ) {
				unset( $userdata->data->user_pass );
			}

			$geodir_de_userdata[ $user_id ] = $userdata;
		}

		return $userdata;
	}

	public static function get_meta( $value, $key = '' ) {
		if ( empty( $value ) ) {
			return array();
		}

		if ( ! is_array( $value ) ) {
			$value = maybe_unserialize( $value );
		}

		if ( ! is_array( $value ) ) {
			$value = array();
		}

		if ( $key ) {
			$value = isset( $value[ $key ] ) ? $value[ $key ] : '';
		}

		return $value;
	}

	public static function save_item( $args ) {
		global $wpdb;

		$orig_args = $args;

		$defaults = array(
			'email_list_id' => 0,
			'email_log_id' => 0,
			'user_id' => 0,
			'post_type' => '',
			'post_id' => 0,
			'date_added' => current_time( 'mysql' ),
			'date_sent' => '',
			'status' => 'pending',
			'meta' => array()
		);

		$args = wp_parse_args( $args, $defaults );

		$meta = ! empty( $args['meta'] ) && is_array( $args['meta'] ) ? maybe_serialize( $args['meta'] ) : '';

		$data = array(
			'email_list_id' => (int) $args['email_list_id'],
			'email_log_id' => (int) $args['email_log_id'],
			'user_id' => (int) $args['user_id'],
			'post_type' => $args['post_type'],
			'post_id' => (int) $args['post_id'],
			'date_added' => $args['date_added'],
			'date_sent' => $args['date_sent'],
			'status' => $args['status'],
			'meta' => $meta
		);

		if ( false === $wpdb->insert( GEODIR_DYNAMIC_EMAILS_USERS_TABLE, $data, array( '%d', '%d', '%d', '%s', '%d', '%s', '%s', '%s', '%s' ) ) ) {
				$message = __( 'Could not save user email log data into the database.', 'geodir-dynamic-emails' );

			return new WP_Error( 'db_insert_error', $message );
		}

		$insert_id = (int) $wpdb->insert_id;

		do_action( 'geodir_dynamic_emails_user_log_saved', $insert_id, $data, $args, $orig_args );

		return $insert_id;
	}

	public static function get_items_by( $key, $value ) {
		global $wpdb;

		$results = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM `" . GEODIR_DYNAMIC_EMAILS_USERS_TABLE . "` WHERE `{$key}` = %s", array( $value ) ) );

		return $results;
	}

	public static function get_last_sent_by( $key, $value ) {
		global $wpdb;

		$value = $wpdb->get_var( $wpdb->prepare( "SELECT `date_sent` FROM `" . GEODIR_DYNAMIC_EMAILS_USERS_TABLE . "` WHERE `{$key}` = %s ORDER BY `date_sent` DESC LIMIT 1", array( $value ) ) );

		return $value;
	}

	public static function get_pending_items( $args ) {
		global $wpdb;

		$defaults = array(
			'email_log' => 0,
			'email_log__in' => array(),
			'limit' => 0
		);

		$args = wp_parse_args( $args, $defaults );

		$query_where = '';

		if ( ! empty( $args['email_log'] ) ) {
			$query_where .= $wpdb->prepare( ' AND `email_log_id` = %d', $args['email_log'] );
		}

		if ( ! empty( $args['email_log__in'] ) && is_array( $args['email_log__in'] ) ) {
			$email_log__in = implode( ',', $args['email_log__in'] );
			$query_where .= " AND `email_log_id` IN ( $email_log__in )";
		}

		$limit = ! empty( $args['limit'] ) && (int) $args['limit'] > 0 ? " LIMIT " . (int) $args['limit'] : '';

		$results = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM `" . GEODIR_DYNAMIC_EMAILS_USERS_TABLE . "` WHERE `status` = %s {$query_where} ORDER BY `date_added` ASC{$limit}", array( 'pending' ) ) );

		return $results;
	}

	public static function mark_item_sent( $email_user_id, $email_user = array() ) {
		global $wpdb;

		$data = array(
			'status' => 'sent',
			'meta' => '',
			'date_sent' => current_time( 'mysql' )
		);

		$format = array(
			'%s',
			'%s'
		);

		$where = array(
			'email_user_id' => $email_user_id,
			'status' => 'pending'
		);

		$where_format = array(
			'%d',
			'%s',
			'%s'
		);

		$update = $wpdb->update( GEODIR_DYNAMIC_EMAILS_USERS_TABLE, $data, $where, $format, $where_format );

		do_action( 'geodir_dynamic_emails_mark_item_sent', $update, $email_user_id, $email_user );

		return $update;
	}

	public static function count_items_by( $key, $value, $status = '' ) {
		global $wpdb;

		$where = array();

		$where[] = $wpdb->prepare( "{$key} = %s", $value );

		if ( $status == 'pending' ) {
			$where[] = "`status` = 'pending'";
		} else if ( $status == 'sent' ) {
			$where[] = "`status` = 'sent'";
		}
		$where = ! empty( $where ) ? "WHERE " . implode( " AND ", $where ) : '';

		return (int) $wpdb->get_var( "SELECT COUNT( * ) FROM `" . GEODIR_DYNAMIC_EMAILS_USERS_TABLE . "` {$where}" );
	}

	public static function delete_items_by_list( $email_list_id, $email_list ) {
		global $wpdb;

		$items = self::get_items_by( 'email_list_id', $email_list_id );

		if ( empty( $items ) ) {
			return false;
		}

		$check = apply_filters( 'geodir_dynamic_emails_user_pre_delete_items_by_list', null, $items, $email_list_id, $email_list );

		if ( null !== $check ) {
			return $check;
		}

		do_action( 'geodir_dynamic_emails_user_before_delete_items_by_list', $items, $email_list_id, $email_list );

		$result = $wpdb->delete( GEODIR_DYNAMIC_EMAILS_USERS_TABLE, array( 'email_list_id' => $email_list_id ) );

		if ( ! $result ) {
			return false;
		}

		do_action( 'geodir_dynamic_emails_user_deleted_items_by_list', $items, $email_list_id, $email_list );

		return true;
	}

	public static function delete_items_by_log( $email_log_id, $email_log ) {
		global $wpdb;

		$items = self::get_items_by( 'email_log_id', $email_log_id );

		if ( empty( $items ) ) {
			return false;
		}

		$check = apply_filters( 'geodir_dynamic_emails_user_pre_delete_items_by_log', null, $items, $email_log_id, $email_log );

		if ( null !== $check ) {
			return $check;
		}

		do_action( 'geodir_dynamic_emails_user_before_delete_items_by_log', $items, $email_log_id, $email_log );

		$result = $wpdb->delete( GEODIR_DYNAMIC_EMAILS_USERS_TABLE, array( 'email_log_id' => $email_log_id ) );

		if ( ! $result ) {
			return false;
		}

		do_action( 'geodir_dynamic_emails_user_deleted_items_by_log', $items, $email_log_id, $email_log );

		return true;
	}

	public static function delete_items_by_user( $user_id, $reassign, $user ) {
		global $wpdb;

		if ( empty( $user_id ) ) {
			return false;
		}

		$items = self::get_items_by( 'user_id', $user_id );

		if ( empty( $items ) ) {
			return false;
		}

		$check = apply_filters( 'geodir_dynamic_emails_user_pre_delete_items_by_user', null, $items, $user_id, $user );

		if ( null !== $check ) {
			return $check;
		}

		do_action( 'geodir_dynamic_emails_user_before_delete_items_by_user', $items, $user_id, $user );

		$result = $wpdb->delete( GEODIR_DYNAMIC_EMAILS_USERS_TABLE, array( 'user_id' => $user_id ) );

		if ( ! $result ) {
			return false;
		}

		do_action( 'geodir_dynamic_emails_user_deleted_items_by_user', $items, $user_id, $user );

		return true;
	}

	public static function delete_items_by_post( $post_id, $post ) {
		global $wpdb;

		if ( ! ( ! empty( $post_id ) && ! empty( $post->post_type ) && geodir_is_gd_post_type( $post->post_type ) ) ) {
			return false;
		}

		$items = self::get_items_by( 'post_id', $post_id );

		if ( empty( $items ) ) {
			return false;
		}

		$check = apply_filters( 'geodir_dynamic_emails_user_pre_delete_items_by_post', null, $items, $post_id, $post );

		if ( null !== $check ) {
			return $check;
		}

		do_action( 'geodir_dynamic_emails_user_before_delete_items_by_post', $items, $post_id, $post );

		$result = $wpdb->delete( GEODIR_DYNAMIC_EMAILS_USERS_TABLE, array( 'post_id' => $post_id ) );

		if ( ! $result ) {
			return false;
		}

		do_action( 'geodir_dynamic_emails_user_deleted_items_by_post', $items, $post_id, $post );

		return true;
	}
}