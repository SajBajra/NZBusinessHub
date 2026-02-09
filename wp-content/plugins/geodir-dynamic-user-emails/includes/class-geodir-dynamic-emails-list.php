<?php
/**
 * Dynamic User Emails List class
 *
 * @author    AyeCode Ltd
 * @package   GeoDir_Dynamic_Emails
 * @version   2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * GeoDir_Dynamic_Emails_List class.
 */
class GeoDir_Dynamic_Emails_List {
	/**
	 * Init.
	 *
	 * @since 2.0.0
	 */
	public static function init() {
		add_filter( 'geodir_dynamic_emails_do_save_email_list', array( __CLASS__, 'save_request' ) );
		add_filter( 'posts_clauses', array( __CLASS__, 'posts_clauses' ), 20, 2 );
	}

	public static function get_item( $id ) {
		global $wpdb;

		$item = geodir_cache_get( (int) $id, 'geodir_email_lists' );

		if ( $item === false ) {
			$item = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM `" . GEODIR_DYNAMIC_EMAILS_LISTS_TABLE . "` WHERE `email_list_id` = %d LIMIT 1", array( (int) $id ) ) );

			geodir_cache_set( (int) $id, $item, 'geodir_email_lists' );
		}

		return $item;
	}

	public static function get_active_items( $args ) {
		$defaults = array(
			'status' => 'publish'
		);

		$args = wp_parse_args( $args, $defaults );

		$args = apply_filters( 'geodir_dynamic_emails_list_items_query_args', $args );

		$email_lists_query = new GeoDir_Dynamic_Emails_List_Query( $args );

		$results = $email_lists_query->get_results();

		return apply_filters( 'geodir_dynamic_emails_list_items_query_results', $results, $args );
	}

	public static function process_conditional_fields( $_fields, $post_type = 'gd_place' ) {
		$allowed_fields = geodir_get_field_key_options( array( 'post_type' => $post_type, 'context' => 'dynamic-email-filter' ) );
		$c = 0;

		$fields = array();

		foreach ( $_fields as $key => $field ) {
			$rule = array();

			if ( ! empty( $field['field'] ) && ! empty( $field['condition'] ) ) {
				if ( empty( $allowed_fields[ $field['field'] ] ) ) {
					continue;
				}

				$rule['field'] = sanitize_text_field( $field['field'] );
				$rule['condition'] = sanitize_text_field( $field['condition'] );

				if ( $rule['condition'] != 'is_empty' && $rule['condition'] != 'is_not_empty' ) {
					if ( isset( $field['search'] ) ) {
						$rule['search'] = sanitize_text_field( wp_unslash( $field['search'] ) );
					} else {
						$rule['search'] = '';
					}
				}

				$c++;
				$fields['rule' . $c] = $rule;
			}
		}

		return $fields;
	}

	public static function save_request() {
		$email_list_id = ! empty( $_POST['email_list_id'] ) ? absint( $_POST['email_list_id'] ) : 0;
		$name = ! empty( $_POST['email_list_name'] ) ? sanitize_text_field( $_POST['email_list_name'] ) : '';
		$action = ! empty( $_POST['email_list_action'] ) ? sanitize_text_field( $_POST['email_list_action'] ) : '';
		$post_type = ! empty( $_POST['email_list_post_type'] ) ? sanitize_text_field( $_POST['email_list_post_type'] ) : '';
		$category = $post_type && $post_type != 'all' && ! empty( $_POST['email_list_category'] ) && is_array( $_POST['email_list_category'] ) ? array_map( 'sanitize_text_field', $_POST['email_list_category'] ) : '';
		$user_roles = ! empty( $_POST['email_list_user_roles'] ) && is_array( $_POST['email_list_user_roles'] ) ? array_map( 'sanitize_text_field', $_POST['email_list_user_roles'] ) : '';
		$recipient = ! empty( $_POST['email_list_recipient'] ) ? sanitize_text_field( $_POST['email_list_recipient'] ) : '';
		$subject = ! empty( $_POST['email_list_subject'] ) ? sanitize_text_field( $_POST['email_list_subject'] ) : '';
		$template = ! empty( $_POST['email_list_template'] ) ? geodir_sanitize_html_field( $_POST['email_list_template'] ) : '';
		$status = ! empty( $_POST['email_list_status'] ) ? sanitize_text_field( $_POST['email_list_status'] ) : '';
		$trigger = $action == 'instant' && $status == 'publish' && ! empty( $_POST['email_list_instant_send'] ) ? true : false;

		$errs = array();
		if ( empty( $name ) ) {
			$errs[] = __( 'Email list name is required.', 'geodir-dynamic-emails' );
		}
		if ( empty( $action ) ) {
			$errs[] = __( 'Event action is required.', 'geodir-dynamic-emails' );
		}
		if ( empty( $subject ) ) {
			$errs[] = __( 'Email subject is required.', 'geodir-dynamic-emails' );
		}
		if ( empty( $template ) ) {
			$errs[] = __( 'Email template is required.', 'geodir-dynamic-emails' );
		}
		if ( ! empty( $errs ) ) {
			throw new Exception( implode( '<br>', $errs ) );
		}

		$fields = array();

		$_fields = ! empty( $_POST['email_list_fields'] ) && is_array( $_POST['email_list_fields'] ) ? $_POST['email_list_fields'] : array();

		if ( ! empty( $_fields ) && geodir_dynamic_emails_action_supports( $action, 'post_type' ) && ! empty( $post_type ) && geodir_is_gd_post_type( $post_type ) && $action != 'user_register' ) {
			$fields = self::process_conditional_fields( $_fields, $post_type );
		}

		$meta = array();
		if ( ! empty( $recipient ) ) {
			$meta['recipient'] = $recipient;
		}

		if ( ! empty( $fields ) ) {
			$meta['fields'] = $fields;
		}

		$data = array(
			'email_list_id' => $email_list_id,
			'name' => $name,
			'action' => $action,
			'post_type' => $post_type,
			'category' => $category,
			'user_roles' => $user_roles,
			'subject' => $subject,
			'template' => $template,
			'meta' => $meta,
			'status' => $status
		);

		$data = apply_filters( 'geodir_dynamic_emails_save_email_list_data', $data );

		if ( is_wp_error( $data ) ) {
			throw new Exception( $data->get_error_message() );
		}

		$item_id = self::save_item( $data );

		if ( is_wp_error( $item_id ) ) {
			throw new Exception( $item_id->get_error_message() );
		}

		if ( ! empty( $email_list_id ) ) {
			$message = __( 'Email list has been updated.', 'geodir-dynamic-emails' );
		} else {
			$message = __( 'Email list has been saved.', 'geodir-dynamic-emails' );
		}

		wp_send_json_success( array( 'message' => $message, 'item_id' => (int) $item_id ) );
	}

	public static function save_item( $dataarr ) {
		global $wpdb;

		$unsanitized_dataarr = $dataarr;

		$defaults = array(
			'email_list_id' => 0,
			'name' => '',
			'action' => '',
			'post_type' => '',
			'category' => '',
			'user_roles' => '',
			'subject' => '',
			'template' => '',
			'meta' => '',
			'date_added' => '',
			'status' => ''
		);

		$dataarr = wp_parse_args( $dataarr, $defaults );

		$email_list_id = 0;
		$update = false;

		if ( ! empty( $dataarr['email_list_id'] ) ) {
			$update = true;

			$email_list_id = $dataarr['email_list_id'];
			$item_before = self::get_item( $email_list_id );

			if ( empty( $item_before ) ) {
				return new WP_Error( 'invalid_email_list', __( 'Invalid email list ID.', 'geodir-dynamic-emails' ) );
			}

			$previous_status = $item_before->status;
		} else {
			$previous_status = 'new';
			$item_before = null;
		}

		if ( ! empty( $dataarr['action'] ) ) {
			$action = $dataarr['action'];
		} else if ( $update && ! isset( $unsanitized_dataarr['action'] ) ) {
			$action = $item_before->action;
		} else {
			return new WP_Error( 'invalid_email_list_action', __( 'Invalid email list event action.', 'geodir-dynamic-emails' ) );
		}

		if ( ! empty( $dataarr['subject'] ) ) {
			$subject = $dataarr['subject'];
		} else if ( $update && ! isset( $unsanitized_dataarr['subject'] ) ) {
			$subject = $item_before->subject;
		} else {
			return new WP_Error( 'invalid_email_list_subject', __( 'Invalid email subject.', 'geodir-dynamic-emails' ) );
		}

		if ( ! empty( $dataarr['template'] ) ) {
			$template = $dataarr['template'];
		} else if ( $update && ! isset( $unsanitized_dataarr['template'] ) ) {
			$template = $item_before->template;
		} else {
			return new WP_Error( 'invalid_email_list_template', __( 'Invalid email template.', 'geodir-dynamic-emails' ) );
		}

		if ( ! empty( $dataarr['name'] ) ) {
			$name = $dataarr['name'];
		} else if ( $update && ! isset( $unsanitized_dataarr['name'] ) ) {
			$name = $item_before->name;
		} else {
			return new WP_Error( 'invalid_email_list_name', __( 'Invalid email list name.', 'geodir-dynamic-emails' ) );
		}

		if ( ! empty( $dataarr['post_type'] ) ) {
			$post_type = is_array( $dataarr['post_type'] ) ? implode( ",", array_filter( $dataarr['post_type'] ) ) : $dataarr['post_type'];
		} else if ( $update && ! isset( $unsanitized_dataarr['post_type'] ) ) {
			$post_type = $item_before->post_type;
		} else {
			$post_type = '';
		}

		if ( ! empty( $dataarr['category'] ) ) {
			$category = is_array( $dataarr['category'] ) ? implode( ",", array_filter( $dataarr['category'] ) ) : $dataarr['category'];
		} else if ( $update && ! isset( $unsanitized_dataarr['category'] ) ) {
			$category = $item_before->category;
		} else {
			$category = '';
		}

		if ( ! empty( $dataarr['user_roles'] ) ) {
			$user_roles = is_array( $dataarr['user_roles'] ) ? implode( ",", array_filter( $dataarr['user_roles'] ) ) : $dataarr['user_roles'];
		} else if ( $update && ! isset( $unsanitized_dataarr['user_roles'] ) ) {
			$user_roles = $item_before->user_roles;
		} else {
			$user_roles = '';
		}

		if ( ! empty( $dataarr['meta'] ) ) {
			$meta = is_array( $dataarr['meta'] ) ? json_encode( $dataarr['meta'] ) : $dataarr['meta'];
		} else if ( $update && ! isset( $unsanitized_dataarr['meta'] ) ) {
			$meta = $item_before->meta;
		} else {
			$meta = '';
		}

		if ( ! empty( $dataarr['date_added'] ) && '0000-00-00 00:00:00' != $dataarr['date_added'] ) {
			$date_added = $dataarr['date_added'];
		} else if ( $update && ! empty( $item_before->date_added ) && '0000-00-00 00:00:00' != $item_before->date_added ) {
			$date_added = $item_before->date_added;
		} else {
			$date_added = current_time( 'mysql' );
		}

		if ( ! empty( $dataarr['status'] ) ) {
			$status = $dataarr['status'];
		} else if ( $update && ! isset( $unsanitized_dataarr['status'] ) ) {
			$status = $item_before->status;
		} else {
			$status = 'pending';
		}

		$data = compact(
			'action',
			'name',
			'post_type',
			'category',
			'user_roles',
			'subject',
			'template',
			'meta',
			'date_added',
			'status'
		);

		$data = apply_filters( 'geodir_dynamic_emails_insert_email_list_data', $data, $dataarr, $unsanitized_dataarr, $update );

		$data = wp_unslash( $data );

		if ( $update ) {
			do_action( 'geodir_dynamic_emails_pre_email_list_update', $email_list_id, $data, $dataarr, $unsanitized_dataarr );

			if ( false === $wpdb->update( GEODIR_DYNAMIC_EMAILS_LISTS_TABLE, $data, array( 'email_list_id' => $email_list_id ) ) ) {
				$message = __( 'Could not update email list in the database.', 'geodir-dynamic-emails' );

				return new WP_Error( 'db_update_error', $message, $wpdb->last_error );
			}
		} else {
			if ( false === $wpdb->insert( GEODIR_DYNAMIC_EMAILS_LISTS_TABLE, $data ) ) {
				$message = __( 'Could not insert email list into the database.', 'geodir-dynamic-emails' );

				return new WP_Error( 'db_insert_error', $message, $wpdb->last_error );
			}

			$email_list_id = (int) $wpdb->insert_id;
		}

		self::clean_item_cache( $email_list_id );

		$item = self::get_item( $email_list_id );

		if ( $update ) {
			do_action( 'geodir_dynamic_emails_email_list_updated', $email_list_id, $item, $item_before, $data, $dataarr, $unsanitized_dataarr );
		}

		do_action( 'geodir_dynamic_emails_save_email_list', $email_list_id, $item, $update, $data, $dataarr, $unsanitized_dataarr );

		return $email_list_id;
	}

	public static function clean_item_cache( $item ) {
		if ( is_object( $item ) && ! empty( $item->email_list_id ) ) {
			$item_id = (int) $item->email_list_id;
		} else if ( is_array( $item ) && ! empty( $item['email_list_id'] ) ) {
			$item_id = (int) $item['email_list_id'];
		} else if ( is_scalar( $item ) && (int) $item > 0 ) {
			$item_id = (int) $item;
		} else {
			return;
		}

		geodir_cache_delete( (int) $item_id, 'geodir_email_lists' );
	}

	public static function delete_item( $item_id ) {
		global $wpdb;

		$item = self::get_item( $item_id );

		if ( empty( $item ) ) {
			return false;
		}

		$check = apply_filters( 'geodir_dynamic_emails_list_pre_delete_item', null, $item_id, $item );

		if ( null !== $check ) {
			return $check;
		}

		do_action( 'geodir_dynamic_emails_list_before_delete_item', $item_id, $item );

		$result = $wpdb->delete( GEODIR_DYNAMIC_EMAILS_LISTS_TABLE, array( 'email_list_id' => $item_id ) );

		if ( ! $result ) {
			return false;
		}

		do_action( 'geodir_dynamic_emails_list_deleted_item', $item_id, $item );

		self::clean_item_cache( $item );

		return true;
	}

	public static function duplicate( $item_id ) {
		global $wpdb;

		$source_item = self::get_item( $item_id );

		if ( empty( $source_item ) ) {
			return false;
		}

		$check = apply_filters( 'geodir_dynamic_emails_list_pre_duplicate_item', null, $item_id, $source_item );

		if ( null !== $check ) {
			return $check;
		}

		do_action( 'geodir_dynamic_emails_list_before_duplicate_item', $item_id, $source_item );

		$data = (array) $source_item;

		unset( $data['email_list_id'] );
		unset( $data['date_added'] );

		$data = apply_filters( 'geodir_dynamic_emails_duplicate_email_list_data', $data, $source_item );

		if ( is_wp_error( $data ) ) {
			return false;
		}

		$item_id = self::save_item( $data );

		if ( is_wp_error( $item_id ) ) {
			return false;
		}

		do_action( 'geodir_dynamic_emails_list_duplicate_item', $item_id, $source_item );

		return $item_id;
	}

	public static function get_instant_items( $email_list ) {
		if ( ! empty( $email_list ) && ! is_object( $email_list ) && is_scalar( $email_list ) ) {
			$email_list = self::get_item( absint( $email_list ) );
		}

		$items = array();

		if ( ! ( ! empty( $email_list ) && ! empty( $email_list->action ) && $email_list->action == 'instant' ) ) {
			return $items;
		}

		$post_type = $email_list->post_type;
		$tax_query = array();

		if ( $post_type && geodir_is_gd_post_type( $post_type ) ) {
			$publish_statuses = geodir_get_publish_statuses( array( 'post_type' => $post_type ) );
			$categories = ! empty( $email_list->category ) ? geodir_dynamic_emails_parse_array( $email_list->category, 'absint' ) : array();

			if ( ! empty( $categories ) ) {
				$tax_query[] = array(
					'taxonomy' => $post_type . 'category',
					'field' => 'term_id',
					'terms' => $categories,
				);
			}
		} else {
			$post_type = geodir_get_posttypes();
			$publish_statuses = array();

			foreach ( $post_type as $_post_type ) {
				$publish_statuses = array_merge( $publish_statuses, geodir_get_publish_statuses( array( 'post_type' => $_post_type ) ) );
			}

			$publish_statuses = array_unique( $publish_statuses );
		}

		$user_roles = ! empty( $email_list->user_roles ) ? geodir_dynamic_emails_parse_array( $email_list->user_roles ) : array();
		$meta = ! empty( $email_list->meta ) ? GeoDir_Dynamic_Emails_Fields::parse_meta( $email_list->meta ) : array();
		$fields = ! empty( $meta['fields'] ) ? $meta['fields'] : array();

		$wp_query_args = array(
			'post_type' => $post_type,
			'post_status' => $publish_statuses,
			'posts_per_page' => -1,
			'tax_query' => $tax_query,
			'orderby' => 'ID',
			'order' => 'ASC',
			'geodir_dynamic_email_query' => true,
			'geodir_fields' => $fields,
			'geodir_user_roles' => $user_roles
		);

		$wp_query = new WP_Query( $wp_query_args );

		$items = ! empty( $wp_query->posts ) ? $wp_query->posts : array();

		return apply_filters( 'geodir_dynamic_emails_get_instant_items', $items, $wp_query_args, $email_list );
	}

	public static function posts_clauses( $clauses, $wp_query ) {
		global $wpdb;

		if ( ! ( ! empty( $wp_query->query_vars ) && ! empty( $wp_query->query_vars['geodir_dynamic_email_query'] ) ) ) {
			return $clauses;
		}

		$post_type = ! empty( $wp_query->query_vars['post_type'] ) && geodir_is_gd_post_type( $wp_query->query_vars['post_type'] ) ? $wp_query->query_vars['post_type'] : '';

		// GD Fields
		if ( ! empty( $wp_query->query_vars['geodir_fields'] ) && $post_type ) {
			$table = geodir_db_cpt_table( $post_type );

			$clauses['fields'] .= ", `pd`.*";
			$clauses['join'] .= " INNER JOIN `{$table}` AS `pd` ON `pd`.`post_id` = `{$wpdb->posts}`.`ID`";

			if ( GeoDir_Dynamic_Emails_Fields::has_event_filter( $post_type, $wp_query->query_vars['geodir_fields'] ) ) {
				$clauses['fields'] .= ", `gdes`.*, MIN( `gdes`.`schedule_id` ) AS `set_schedule_id`";
				$clauses['join'] .= " JOIN `" . GEODIR_EVENT_SCHEDULES_TABLE . "` AS `gdes` ON `gdes`.`event_id` = `{$wpdb->posts}`.`ID`";
				$clauses['groupby'] = "`gdes`.`event_id`";
				$clauses['orderby'] = "`gdes`.`start_date` ASC, `gdes`.`start_time` ASC, `{$wpdb->posts}`.`ID` ASC";
			}

			$fields_where = GeoDir_Dynamic_Emails_Fields::get_fields_where( $post_type, $wp_query->query_vars['geodir_fields'] );

			if ( ! empty( $fields_where ) && trim( $fields_where ) != "" ) {
				$clauses['where'] .= " AND " . $fields_where;
			}
		}

		// User Roles
		if ( ! empty( $wp_query->query_vars['geodir_user_roles'] ) ) {
			$clauses['join'] .= " LEFT JOIN `{$wpdb->usermeta}` ON ( `{$wpdb->usermeta}`.`user_id` = `{$wpdb->posts}`.`post_author` AND `{$wpdb->usermeta}`.`meta_key` = '" . $wpdb->prefix . "capabilities' )";
			$roles_where = array();
			foreach ( $wp_query->query_vars['geodir_user_roles'] as $role ) {
				$roles_where[] = "`{$wpdb->usermeta}`.`meta_value` LIKE '%\"{$role}\"%'";
			}

			if ( count( $roles_where ) == 1 ) {
				$clauses['where'] .= " AND " . $roles_where[0];
			} else {
				$clauses['where'] .= " AND ( " . implode( " OR ", $roles_where ) . " )";
			}
		}

//		print_r( $clauses );exit;

		return $clauses;
	}
}