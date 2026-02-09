<?php
/**
 * Dynamic User Emails Action class
 *
 * @author    AyeCode Ltd
 * @package   GeoDir_Dynamic_Emails
 * @version   2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * GeoDir_Dynamic_Emails_Action class.
 */
class GeoDir_Dynamic_Emails_Action {
	/**
	 * Init.
	 *
	 * @since 2.0.0
	 */
	public static function init() {
		add_action( 'register_new_user', array( __CLASS__, 'on_register_new_user' ), 9999, 1 );
		add_action( 'user_register', array( __CLASS__, 'on_user_register' ), 9999, 2 );
		add_action( 'post_updated', array( __CLASS__, 'on_post_updated' ), 9, 3 );
		add_action( 'wp_insert_post', array( __CLASS__, 'on_wp_insert_post' ), 9999, 3 );
		add_action( 'geodir_post_published', array( __CLASS__, 'on_geodir_post_published' ), 9999, 3 );
		add_action( 'comment_post', array( __CLASS__, 'on_comment_post' ), 99999, 3 );
		add_action( 'comment_unapproved_to_approved', array( __CLASS__, 'on_comment_approved' ), 9999, 1 );
		add_action( 'comment_spam_to_approved', array( __CLASS__, 'on_comment_approved' ), 9999, 1 );

		add_action( 'geodir_dynamic_emails_action_user_register', array( __CLASS__, 'action_user_register' ), 10, 2 );
		add_action( 'geodir_dynamic_emails_action_new_post', array( __CLASS__, 'action_new_post' ), 10, 2 );
		add_action( 'geodir_dynamic_emails_action_edit_post', array( __CLASS__, 'action_edit_post' ), 10, 2 );
		add_action( 'geodir_dynamic_emails_action_new_comment', array( __CLASS__, 'action_new_comment' ), 10, 2 );
		add_action( 'geodir_dynamic_emails_action_review_response', array( __CLASS__, 'action_review_response' ), 10, 2 );

		add_filter( 'geodir_dynamic_emails_check_post_rules', array( __CLASS__, 'check_post_rules' ), 10, 3 );
		add_action( 'geodir_dynamic_emails_user_log_saved', array( __CLASS__, 'on_user_log_saved' ), 10, 4 );
	}

	public static function on_register_new_user( $user_id ) {
		self::on_user_register( $user_id );
	}

	public static function on_user_register( $user_id, $userdata = array() ) {
		global $geodir_de_user_register;

		if ( ! geodir_design_style() ) {
			return;
		}

		if ( empty( $geodir_de_user_register ) ) {
			$geodir_de_user_register = array();
		}

		if ( ! empty( $geodir_de_user_register[ $user_id ] ) ) {
			return;
		}

		if ( ! geodir_dynamic_emails_has_action( 'user_register', array( 'user_id' => $user_id ) ) ) {
			return;
		}

		$geodir_de_user_register[ $user_id ] = true;

		do_action( 'geodir_dynamic_emails_action_user_register', $user_id );
	}

	public static function on_post_updated( $post_ID, $post_after, $post_before ) {
		global $geodir_wp_post_before, $geodir_de_post_updated;

		if ( ! geodir_design_style() || empty( $post_after->post_status ) || empty( $post_before->post_status ) ) {
			return;
		}

		$unpublished = array( 'draft', 'auto-draft', 'inherit', 'trash', 'pending', 'gd-expired', 'gd-closed' );

		if ( in_array( $post_after->post_status, $unpublished ) || in_array( $post_before->post_status, $unpublished ) ) {
			return;
		}

		if ( ! geodir_dynamic_emails_has_action( 'new_post', array( 'post' => $post_after ) ) && ! geodir_dynamic_emails_has_action( 'edit_post', array( 'post' => $post_after ) ) ) {
			return;
		}

		if ( wp_is_post_revision( $post_ID ) ) {
			$post_ID = wp_get_post_parent_id( $post_ID );
		}

		$post_type = get_post_type( $post_ID );

		if ( ! geodir_is_gd_post_type( $post_type ) ) {
			return;
		}

		if ( empty( $geodir_wp_post_before ) ) {
			$geodir_wp_post_before = array();
		}

		if ( empty( $geodir_de_post_updated ) ) {
			$geodir_de_post_updated = array();
		}

		if ( ! empty( $geodir_de_post_updated[ $post_ID ] ) ) {
			return;
		}

		$publish_statuses = geodir_get_publish_statuses( array( 'post_type' => $post_type ) );

		if ( ! ( in_array( $post_after->post_status, $publish_statuses ) && in_array( $post_before->post_status, $publish_statuses ) ) ) {
			return;
		}

		$gd_post = geodir_get_post_info( $post_ID );

		if ( empty( $gd_post ) ) {
			return;
		}

		$geodir_wp_post_before[ $post_ID ] = $post_before;
		$geodir_de_post_updated[ $post_ID ] = $gd_post;
	}

	public static function on_wp_insert_post( $post_ID, $post, $update ) {
		global $geodir_wp_post_before, $geodir_post_before, $geodir_dynamic_emails_post, $geodir_de_insert_post, $geodir_de_post_updated, $geodir_de_save_post;

		if ( ! geodir_design_style() ) {
			return;
		}

		if ( ! geodir_dynamic_emails_has_action( 'new_post', array( 'post' => $post ) ) && ! geodir_dynamic_emails_has_action( 'edit_post', array( 'post' => $post ) ) ) {
			return;
		}

		if ( empty( $geodir_de_insert_post ) ) {
			$geodir_de_insert_post = array();
		}

		if ( ! empty( $geodir_de_insert_post[ $post_ID ] ) || ! empty( $geodir_dynamic_emails_post[ $post_ID ] ) ) {
			return;
		}

		if ( ! ( ! empty( $post->post_type ) && geodir_is_gd_post_type( $post->post_type ) ) ) {
			return;
		}

		if ( ! in_array( $post->post_status, geodir_get_publish_statuses( (array) $post ) ) ) {
			return;
		}

		if ( isset( $_REQUEST['action'] ) && $_REQUEST['action'] == 'geodir_save_post' && empty( $geodir_de_save_post[ $post->ID ] ) ) {
			if ( empty( $geodir_de_save_post ) ) {
				$geodir_de_save_post = array();
			}

			$geodir_de_save_post[ $post->ID ] = $post;

			return;
		}

		$gd_post = geodir_get_post_info( $post_ID );

		if ( empty( $gd_post ) ) {
			return;
		}

		$gd_post_before = array();
		if ( ! empty( $geodir_post_before[ $post_ID ] ) ) {
			$gd_post_before = $geodir_post_before[ $post_ID ];
		} else if ( ! empty( $geodir_de_post_updated[ $post_ID ] ) ) {
			$gd_post_before = $geodir_de_post_updated[ $post_ID ];
		}

		if ( ! empty( $gd_post_before ) ) {
			if ( ! empty( $geodir_wp_post_before[ $post_ID ] ) ) {
				$gd_post_before->post_content = $geodir_wp_post_before[ $post_ID ]->post_content;
			}

			$unset_keys = array( 'ID', 'post_date', 'post_date_gmt', 'comment_status', 'ping_status', 'post_password', 'post_name', 'to_ping', 'pinged', 'comment_count', 'post_modified', 'post_modified_gmt', 'post_parent', 'guid', 'post_type', 'post_id', 'overall_rating', 'rating_count', 'post_author' );

			$_gd_post = (array) $gd_post;
			$_gd_post_before = (array) $gd_post_before;

			foreach ( $unset_keys as $key ) {
				if ( isset( $_gd_post[ $key ] ) ) {
					unset( $_gd_post[ $key ] );
				}

				if ( isset( $_gd_post_before[ $key ] ) ) {
					unset( $_gd_post_before[ $key ] );
				}
			}
		} else {
			$_gd_post = array();
			$_gd_post_before = array();
		}

		$geodir_de_insert_post[ $post_ID ] = $gd_post;

		if ( ! empty( $_gd_post ) && ! empty( $_gd_post_before ) ) {
			if ( maybe_serialize( $_gd_post ) != maybe_serialize( $_gd_post_before ) ) {
				do_action( 'geodir_dynamic_emails_action_edit_post', $gd_post, $gd_post_before );
			} else {
				do_action( 'geodir_dynamic_emails_save_post', $gd_post );
			}
		}

		if ( ! empty( $geodir_de_save_post[ $post->ID ] ) ) {
			unset( $geodir_de_save_post[ $post->ID ] );
		}
	}

	public static function on_geodir_post_published( $gd_post, $data = array(), $checked = false ) {
		global $wpdb, $geodir_dynamic_emails_post;

		if ( ! geodir_design_style() ) {
			return;
		}

		if ( ! geodir_dynamic_emails_has_action( 'new_post', array( 'gd_post' => $gd_post ) ) ) {
			return;
		}

		if ( ! $checked && ! ( ! empty( $gd_post->post_type ) && geodir_is_gd_post_type( $gd_post->post_type ) ) ) {
			return;
		}

		if ( empty( $geodir_dynamic_emails_post ) ) {
			$geodir_dynamic_emails_post = array();
		}

		if ( ! empty( $geodir_dynamic_emails_post[ $gd_post->ID ] ) ) {
			return;
		}

		if ( ! $checked && ! in_array( $gd_post->post_status, geodir_get_publish_statuses( (array) $gd_post ) ) ) {
			return;
		}

		$geodir_dynamic_emails_post[ $gd_post->ID ] = $gd_post;

		do_action( 'geodir_dynamic_emails_action_new_post', $gd_post, $data );
	}

	public static function on_comment_post( $comment_id, $comment_approved, $commentdata ) {
		if ( ! geodir_design_style() ) {
			return;
		}

		// Only send notifications for approved or pending comments.
		if ( ! ( $comment_approved == '0' || $comment_approved === 'hold' || $comment_approved == '1' || $comment_approved === 'approve' ) ) {
			return;
		}

		if ( ! ( ! empty( $commentdata['comment_post_ID'] ) && geodir_is_gd_post_type( get_post_type( (int) $commentdata['comment_post_ID'] ) ) ) ) {
			return;
		}

		// New comment.
		$has_new_comment = geodir_dynamic_emails_has_action( 'new_comment', array( 'comment_id' => $comment_id, 'comment_approved' => $comment_approved, 'commentdata' => $commentdata ) );

		// Response to review owner.
		$has_review_response = ( $comment_approved == '1' || $comment_approved === 'approve' ) && empty( $commentdata['comment_parent'] ) && geodir_dynamic_emails_has_action( 'review_response', array( 'comment_id' => $comment_id, 'comment_approved' => $comment_approved, 'commentdata' => $commentdata ) );

		if ( ! $has_new_comment && ! $has_review_response ) {
			return;
		}

		$comment = get_comment( $comment_id );

		if ( empty( $comment ) ) {
			return;
		}

		$gd_post = geodir_get_post_info( (int) $comment->comment_post_ID );

		if ( empty( $gd_post ) ) {
			return;
		}

		if ( $has_new_comment ) {
			do_action( 'geodir_dynamic_emails_action_new_comment', $comment, $gd_post );
		}

		if ( $has_review_response ) {
			do_action( 'geodir_dynamic_emails_action_review_response', $comment, $gd_post );
		}
	}

	/**
	 * Handle the comment status is in transition from spam/unapproved to approved.
	 *
	 * @since 2.0.4
	 *
	 * @param WP_Comment $comment Comment object.
	 */
	public static function on_comment_approved( $comment ) {
		if ( ! geodir_design_style() ) {
			return;
		}

		$current_time = (int) current_time( 'timestamp', 1 );
		$time_diff = ! empty( $comment->comment_date_gmt ) ? $current_time - strtotime( $comment->comment_date_gmt ) : 0;

		// Handle comment posted within last 7 days.
		if ( $time_diff > 7 * DAY_IN_SECONDS ) {
			return;
		}

		// Check comment.
		if ( empty( $comment->comment_parent ) && get_comment_meta( $comment->comment_ID, '_gd_review_response', true ) ) {
			return;
		}

		// Check post type.
		if ( ! ( ! empty( $comment->comment_post_ID ) && geodir_is_gd_post_type( get_post_type( $comment->comment_post_ID ) ) ) ) {
			return;
		}

		// Response to review owner.
		$has_review_response = geodir_dynamic_emails_has_action( 'review_response', array( 'comment_id' => $comment->comment_ID, 'comment_approved' => $comment->comment_approved, 'comment' => $comment ) );

		if ( ! $has_review_response ) {
			return;
		}

		$gd_post = geodir_get_post_info( (int) $comment->comment_post_ID );

		if ( empty( $gd_post ) ) {
			return;
		}

		do_action( 'geodir_dynamic_emails_action_review_response', $comment, $gd_post );
	}

	public static function action_user_register( $user_id, $args = array() ) {
		$userdata = GeoDir_Dynamic_Emails_User::get_userdata( $user_id );

		if ( empty( $userdata->roles ) ) {
			return;
		}

		$action = 'user_register';

		$items = GeoDir_Dynamic_Emails_List::get_active_items( array( 'action' => $action, 'role__in' => array_values( $userdata->roles ), 'role__empty' => true ) );

		if ( empty( $items ) ) {
			return;
		}

		$user_log_args = array(
			'user_id' => (int) $userdata->ID,
			'meta' => array( 'userdata' => $userdata )
		);

		$pending_logs = array();

		foreach ( $items as $item ) {
			$user_log_args['email_list_id'] = (int) $item->email_list_id;

			$email_log_id = GeoDir_Dynamic_Emails_Log::save_item( array( 'email_list_id' => (int) $item->email_list_id ) );

			if ( ! is_wp_error( $email_log_id ) ) {
				$pending_logs[] = (int) $email_log_id;

				$user_log_args['email_log_id'] = (int) $email_log_id;

				GeoDir_Dynamic_Emails_User::save_item( $user_log_args );
			}
		}

		if ( ! empty( $pending_logs ) ) {
			$pending_items = GeoDir_Dynamic_Emails_User::get_pending_items( array( 'email_log__in', $pending_logs ) );

			if ( ! empty( $pending_items ) ) {
				foreach ( $pending_items as $email_user ) {
					$_sent = GeoDir_Dynamic_Emails_Email::send_email( $email_user );

					if ( $_sent !== null ) {
						GeoDir_Dynamic_Emails_User::mark_item_sent( (int) $email_user->email_user_id, $email_user );
					}
				}
			}
		}
	}

	public static function action_post( $action, $email_args ) {
		if ( empty( $email_args['gd_post'] ) ) {
			return;
		}

		$gd_post = $email_args['gd_post'];
		$email_args['post'] = $gd_post;

		if ( empty( $gd_post->post_author ) ) {
			return;
		}

		$user_id = ! empty( $email_args['recipient'] ) && isset( $email_args['recipient']['user_id'] ) ? absint( $email_args['recipient']['user_id'] ) : (int) $gd_post->post_author;
		$userdata = GeoDir_Dynamic_Emails_User::get_userdata( (int) $user_id );

		if ( ! ( ! empty( $userdata ) && ! empty( $userdata->roles ) ) ) {
			return;
		}

		$post_category = array();
		if ( ! empty( $gd_post->post_category ) ) {
			$post_category = array_map( 'absint', array_filter( explode( ",", $gd_post->post_category ) ) );
			$post_category = array_filter( array_unique( $post_category ) );
		}

		$_args = array(
			'action' => $action,
			'post_type' => $gd_post->post_type,
			'post_type__empty' => true,
			'role__in' => array_values( $userdata->roles ),
			'role__empty' => true,
			'category__in' => $post_category,
			'category__empty' => true,
		);

		$items = GeoDir_Dynamic_Emails_List::get_active_items( $_args );

		if ( empty( $items ) ) {
			return;
		}

		$meta = $email_args;
		$meta['action'] = $action;
		$meta['userdata'] = $userdata;

		$user_log_args = array(
			'user_id' => (int) $userdata->ID,
			'post_type' => $gd_post->post_type,
			'post_id' => (int) $gd_post->ID,
			'meta' => $meta
		);

		$pending_logs = array();

		foreach ( $items as $item ) {
			$check = apply_filters( 'geodir_dynamic_emails_check_post_rules', true, $item, $gd_post );

			if ( ! $check ) {
				continue;
			}

			$user_log_args['email_list_id'] = (int) $item->email_list_id;

			$meta = ! empty( $email_list->meta ) ? GeoDir_Dynamic_Emails_Fields::parse_meta( $email_list->meta ) : array();
			$fields = ! empty( $meta['fields'] ) ? $meta['fields'] : array();

			$email_log_id = GeoDir_Dynamic_Emails_Log::save_item( array( 'email_list_id' => (int) $item->email_list_id ) );

			if ( ! is_wp_error( $email_log_id ) ) {
				$pending_logs[] = (int) $email_log_id;

				$user_log_args['email_log_id'] = (int) $email_log_id;

				GeoDir_Dynamic_Emails_User::save_item( $user_log_args );
			}
		}

		if ( ! empty( $pending_logs ) ) {
			$pending_items = GeoDir_Dynamic_Emails_User::get_pending_items( array( 'email_log__in', $pending_logs ) );

			if ( ! empty( $pending_items ) ) {
				foreach ( $pending_items as $email_user ) {
					$_sent = GeoDir_Dynamic_Emails_Email::send_email( $email_user, $email_args );

					if ( $_sent !== null ) {
						GeoDir_Dynamic_Emails_User::mark_item_sent( (int) $email_user->email_user_id, $email_user );
					}
				}
			}
		}
	}

	public static function action_new_post( $gd_post, $args = array() ) {
		self::action_post( 'new_post', array( 'gd_post' => $gd_post ) );
	}

	public static function action_edit_post( $gd_post, $gd_post_before ) {
		self::action_post( 'edit_post', array( 'gd_post' => $gd_post, 'gd_post_before' => $gd_post_before ) );
	}

	public static function action_new_comment( $comment, $gd_post ) {
		if ( empty( $comment ) || empty( $gd_post ) ) {
			return;
		}

		self::action_post( 'new_comment', array( 'comment' => (object) $comment->to_array(), 'gd_post' => $gd_post ) );
	}

	public static function action_review_response( $comment, $gd_post ) {
		if ( empty( $comment ) || empty( $gd_post ) ) {
			return;
		}

		$recipient = array(
			'user_id' => $comment->user_id,
			'user_name' => $comment->comment_author,
			'user_email' => $comment->comment_author_email
		);

		self::action_post( 'review_response', array( 'comment' => (object) $comment->to_array(), 'gd_post' => $gd_post, 'recipient' => $recipient ) );
	}

	public static function action_instant( $email_list ) {
		global $geodir_de_pending_log;

		if ( ! geodir_design_style() ) {
			return null;
		}

		if ( ! geodir_dynamic_emails_has_action( 'instant', array( 'email_list' => $email_list ) ) ) {
			return null;
		}

		if ( ! empty( $email_list ) && ! is_object( $email_list ) && is_scalar( $email_list ) ) {
			$email_list = GeoDir_Dynamic_Emails_List::get_item( absint( $email_list ) );
		}

		if ( ! ( ! empty( $email_list ) &&  ! empty( $email_list->action ) && $email_list->action == 'instant' && ! empty( $email_list->status ) && $email_list->status == 'publish' ) ) {
			return new WP_Error( 'invalid_email_list', __( 'Email list is not published or not allowed to send instantly.', 'geodir-dynamic-emails' ) );
		}

		$count = 0;
		$items = GeoDir_Dynamic_Emails_List::get_instant_items( $email_list );

		if ( empty( $items ) ) {
			return $count;
		}

		$email_log_id = (int) GeoDir_Dynamic_Emails_Log::save_item( array( 'email_list_id' => (int) $email_list->email_list_id ) );

		if ( empty( $geodir_de_pending_log ) ) {
			$geodir_de_pending_log = array();
		}

		foreach ( $items as $_post ) {
			if ( empty( $_post->post_author ) ) {
				continue;
			}

			$skip = apply_filters( 'geodir_dynamic_emails_skip_instant_item', false, $_post, $email_list );

			if ( $skip === true ) {
				continue;
			}

			$user_log_args = array(
				'email_list_id' => (int) $email_list->email_list_id,
				'email_log_id' => (int) $email_log_id,
				'user_id' => (int) $_post->post_author,
				'post_type' => $_post->post_type,
				'post_id' => (int) $_post->ID
			);

			if ( ! empty( $_post->post_id ) ) {
				$user_log_args['meta'] = array( 'gd_post' => $_post );
			}

			$email_user_id = GeoDir_Dynamic_Emails_User::save_item( $user_log_args );

			if ( ! is_wp_error( $email_user_id ) && $email_user_id > 0 ) {
				$count++;
			}
		}

		if ( $count > 0 ) {
			$geodir_de_pending_log[] = $email_log_id;

			geodir_update_option( 'dynamic_emails_trigger_send', 1 );
		}

		return $count;
	}

	public static function check_post_rules( $check, $email_list, $the_post ) {
		global $gd_post;

		if ( $check ) {
			$meta = ! empty( $email_list->meta ) ? GeoDir_Dynamic_Emails_Fields::parse_meta( $email_list->meta ) : array();
			$fields = ! empty( $meta['fields'] ) ? $meta['fields'] : array();

			if ( ! empty( $fields ) ) {
				$rules = array();

				foreach ( $fields as $key => $rule ) {
					$rule['type'] = 'gd_field';

					$rules[ $key ] = $rule;
				}

				$backup_gd_post = $gd_post;
				$gd_post = $the_post;

				$check = sd_block_check_rules( $rules );

				$gd_post = $backup_gd_post;
			}
		}

		return $check;
	}

	public static function on_user_log_saved( $user_log_id, $data, $args, $orig_args ) {
		if ( ! ( ! empty( $args ) && ! empty( $args['meta']['action'] ) ) ) {
			return;
		}

		$action = $args['meta']['action'];

		if ( $action == 'review_response' && ! empty( $args['meta']['comment'] ) ) {
			update_comment_meta( $args['meta']['comment']->comment_ID, '_gd_review_response', current_time( 'timestamp', 1 ) );
		}
	}
}