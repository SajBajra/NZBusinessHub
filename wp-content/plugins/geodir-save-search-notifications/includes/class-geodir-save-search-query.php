<?php
/**
 * Save Search Notifications Query class
 *
 * @author    AyeCode Ltd
 * @package   GeoDir_Save_Search
 * @version   1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * GeoDir_Save_Search_Query class.
 */
class GeoDir_Save_Search_Query {
	/**
	 * Init.
	 *
	 * @since 1.0
	 */
	public static function init() {
		
	}

	public static function get_subscriber( $subscriber_id ) {
		global $wpdb;

		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM `" . GEODIR_SAVE_SEARCH_SUBSCRIBERS_TABLE . "` WHERE `subscriber_id` = %d LIMIT 1", (int) $subscriber_id ) );
	}

	public static function count_subscribers() {
		global $wpdb;

		return (int) $wpdb->get_var( "SELECT COUNT( * ) FROM `" . GEODIR_SAVE_SEARCH_SUBSCRIBERS_TABLE . "`" );
	}

	public static function count_users() {
		global $wpdb;

		return (int) $wpdb->get_var( "SELECT COUNT( DISTINCT user_id ) FROM `" . GEODIR_SAVE_SEARCH_SUBSCRIBERS_TABLE . "`" );
	}

	public static function get_subscribers_by_user( $user_id = 0 ) {
		global $wpdb;

		if ( empty( $user_id ) ) {
			$user_id = (int) get_current_user_id();
		}

		if ( empty( $user_id ) ) {
			return array();
		}

		return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM `" . GEODIR_SAVE_SEARCH_SUBSCRIBERS_TABLE . "` WHERE `user_id` = %d ORDER BY `date_added` DESC", (int) $user_id ) );
	}

	public static function count_subscribers_by_user( $user_id = 0 ) {
		global $wpdb;

		if ( empty( $user_id ) ) {
			$user_id = (int) get_current_user_id();
		}

		if ( empty( $user_id ) ) {
			return 0;
		}

		return (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM `" . GEODIR_SAVE_SEARCH_SUBSCRIBERS_TABLE . "` WHERE `user_id` = %d", (int) $user_id ) );
	}

	public static function save_subscriber( $args ) {
		global $wpdb;

		$user = wp_get_current_user();
		$orig_args = $args;

		$defaults = array(
			//'subscriber_id' => 0,
			'user_id' => (int) $user->ID,
			'user_email' => $user->user_email,
			'user_name' => geodir_save_search_user_name( $user ),
			'post_type' => '',
			'search_name' => '',
			'search_uri' => '',
			'date_added' => current_time( 'mysql' )
		);

		$args = wp_parse_args( $args, $defaults );

		$data = array(
			'user_id' => (int) $args['user_id'],
			'user_email' => $args['user_email'],
			'user_name' => $args['user_name'],
			'post_type' => $args['post_type'],
			'search_name' => $args['search_name'],
			'search_uri' => $args['search_uri'],
			'date_added' => $args['date_added'],
		);

		if ( false === $wpdb->insert( GEODIR_SAVE_SEARCH_SUBSCRIBERS_TABLE, $data, array( '%d', '%s', '%s', '%s', '%s', '%s', '%s' ) ) ) {
				$message = __( 'Could not save search into the database.', 'geodir-save-search' );

			return new WP_Error( 'db_insert_error', $message );
		}

		$subscriber_id = (int) $wpdb->insert_id;

		if ( ! empty( $args['fields'] ) ) {
			foreach ( $args['fields'] as $field => $value ) {
				self::update_subscriber_field( $subscriber_id, $field, $value );
			}
		}

		do_action( 'geodir_save_search_subscriber_saved', $subscriber_id, $data, $args, $orig_args );

		return $subscriber_id;
	}

	public static function delete_subscriber( $subscriber_id ) {
		global $wpdb;

		$wpdb->delete( GEODIR_SAVE_SEARCH_FIELDS_TABLE, array( 'subscriber_id' => $subscriber_id ), array( '%d' ) );
		$wpdb->delete( GEODIR_SAVE_SEARCH_EMAILS_TABLE, array( 'subscriber_id' => $subscriber_id ), array( '%d' ) );
		$deleted = $wpdb->delete( GEODIR_SAVE_SEARCH_SUBSCRIBERS_TABLE, array( 'subscriber_id' => $subscriber_id ), array( '%d' ) );

		return $deleted;
	}

	public static function save_subscriber_email( $args ) {
		global $wpdb;

		$orig_args = $args;

		$defaults = array(
			'subscriber_id' => 0,
			'post_id' => '',
			'post_title' => '',
			'post_url' => '',
			'email_action' => '',
			'date_added' => current_time( 'mysql' ),
			'date_sent' => '',
			'status' => 'pending'
		);

		$args = wp_parse_args( $args, $defaults );

		$data = array(
			'subscriber_id' => (int) $args['subscriber_id'],
			'post_id' => (int) $args['post_id'],
			'post_title' => $args['post_title'],
			'post_url' => $args['post_url'],
			'email_action' => $args['email_action'],
			'date_added' => $args['date_added'],
			'status' => $args['status']
		);

		if ( false === $wpdb->insert( GEODIR_SAVE_SEARCH_EMAILS_TABLE, $data, array( '%d', '%d', '%s', '%s', '%s', '%s', '%s' ) ) ) {
				$message = __( 'Could not save subscriber email data into the database.', 'geodir-save-search' );

			return new WP_Error( 'db_insert_error', $message );
		}

		$email_id = (int) $wpdb->insert_id;

		do_action( 'geodir_save_search_subscriber_email_saved', $email_id, $data, $args, $orig_args );

		return $email_id;
	}

	public static function subscriber_email_exists( $subscriber_id, $post_id ) {
		global $wpdb;

		$email_id = (int) $wpdb->get_var( $wpdb->prepare( "SELECT `email_id` FROM `" . GEODIR_SAVE_SEARCH_EMAILS_TABLE . "` WHERE `subscriber_id` = %d AND `post_id` = %d LIMIT 1", $subscriber_id, $post_id ) );

		return $email_id;
	}

	public static function update_subscriber_field( $subscriber_id, $field, $value ) {
		global $wpdb;

		$data = array(
			'subscriber_id' => $subscriber_id,
			'field_name' => $field,
			'field_value' => $value
		);

		if ( false === $wpdb->insert( GEODIR_SAVE_SEARCH_FIELDS_TABLE, $data, array( '%d', '%s', '%s' ) ) ) {
				$message = __( 'Could not save search field into the database.', 'geodir-save-search' );

			return new WP_Error( 'db_insert_error', $message );
		}

		return (int) $wpdb->insert_id;
	}

	public static function get_saved_fields( $post_type ) {
		global $wpdb;

		$fields = $wpdb->get_col( $wpdb->prepare( "SELECT `f`.`field_name` FROM `" . GEODIR_SAVE_SEARCH_FIELDS_TABLE . "` AS `f` LEFT JOIN `" . GEODIR_SAVE_SEARCH_SUBSCRIBERS_TABLE . "` AS `s` ON `s`.`subscriber_id` = `f`.`subscriber_id` WHERE `s`.`post_type` = %s GROUP BY `f`.`field_name`", $post_type ) );

		return $fields;
	}

	public static function get_subscribers_fields( $post_type, $post_id = 0 ) {
		global $wpdb, $geodir_ss_subscribers_fields;

		if ( empty( $geodir_ss_subscribers_fields ) ) {
			$geodir_ss_subscribers_fields = array();
		}

		if ( ! empty( $geodir_ss_subscribers_fields[ $post_type . $post_id ] ) ) {
			return $geodir_ss_subscribers_fields[ $post_type . $post_id ];
		}

		if ( $post_id ) {
			$results = $wpdb->get_results( $wpdb->prepare( "SELECT `gds`.*, `gdf`.* FROM `" . GEODIR_SAVE_SEARCH_SUBSCRIBERS_TABLE . "` AS `gds` LEFT JOIN `" . GEODIR_SAVE_SEARCH_FIELDS_TABLE . "` AS `gdf` ON `gdf`.`subscriber_id` = `gds`.`subscriber_id` LEFT JOIN `" . GEODIR_SAVE_SEARCH_EMAILS_TABLE . "` AS `gde` ON ( `gde`.`subscriber_id` = `gds`.`subscriber_id` AND `gde`.`post_id` = %d ) WHERE `gds`.`post_type` = %s AND `gde`.`email_id` IS NULL ORDER BY `gds`.`subscriber_id` ASC, `gdf`.`search_id` ASC", $post_id, $post_type ) );
		} else {
			$results = $wpdb->get_results( $wpdb->prepare( "SELECT `gds`.*, `gdf`.* FROM `" . GEODIR_SAVE_SEARCH_SUBSCRIBERS_TABLE . "` AS `gds` LEFT JOIN `" . GEODIR_SAVE_SEARCH_FIELDS_TABLE . "` AS `gdf` ON `gdf`.`subscriber_id` = `gds`.`subscriber_id` WHERE `gds`.`post_type` = %s ORDER BY `gds`.`subscriber_id` ASC, `gdf`.`search_id` ASC", $post_type ) );
		}

		$geodir_ss_subscribers_fields[ $post_type . $post_id ] = $results;

		return $results;
	}

	public static function get_single_checkbox_fields( $post_type ) {
		global $wpdb, $geodir_ss_checkbox_fields;

		if ( empty( $geodir_ss_checkbox_fields ) ) {
			$geodir_ss_checkbox_fields = array();
		}

		if ( ! empty( $geodir_ss_checkbox_fields[ $post_type ] ) ) {
			return $geodir_ss_checkbox_fields[ $post_type ];
		}

		$results = $wpdb->get_col( $wpdb->prepare( "SELECT `htmlvar_name` FROM `" . GEODIR_ADVANCE_SEARCH_TABLE . "` WHERE `post_type` = %s AND `field_type` = 'checkbox' AND `input_type` = 'SINGLE' ORDER BY `sort_order` ASC", $post_type ) );

		$geodir_ss_checkbox_fields[ $post_type ] = $results;

		return $results;
	}

	public static function get_single_select_fields( $post_type ) {
		global $wpdb, $geodir_ss_select_fields;

		if ( empty( $geodir_ss_select_fields ) ) {
			$geodir_ss_select_fields = array();
		}

		if ( ! empty( $geodir_ss_select_fields[ $post_type ] ) ) {
			return $geodir_ss_select_fields[ $post_type ];
		}

		$results = $wpdb->get_col( $wpdb->prepare( "SELECT `htmlvar_name` FROM `" . GEODIR_ADVANCE_SEARCH_TABLE . "` WHERE `post_type` = %s AND ( `field_type` = 'select' || `field_type` = 'radio' ) AND `input_type` != 'RANGE' ORDER BY `sort_order` ASC", $post_type ) );

		$geodir_ss_select_fields[ $post_type ] = $results;

		return $results;
	}

	public static function get_multiselect_fields( $post_type ) {
		global $wpdb, $geodir_ss_multiselect_fields;

		if ( empty( $geodir_ss_multiselect_fields ) ) {
			$geodir_ss_multiselect_fields = array();
		}

		if ( ! empty( $geodir_ss_multiselect_fields[ $post_type ] ) ) {
			return $geodir_ss_multiselect_fields[ $post_type ];
		}

		$results = $wpdb->get_col( $wpdb->prepare( "SELECT `htmlvar_name` FROM `" . GEODIR_ADVANCE_SEARCH_TABLE . "` WHERE `post_type` = %s AND `field_type` = 'multiselect' AND `input_type` != 'RANGE' ORDER BY `sort_order` ASC", $post_type ) );

		$geodir_ss_multiselect_fields[ $post_type ] = $results;

		return $results;
	}

	public static function get_range_fields( $post_type ) {
		global $wpdb, $geodir_ss_range_fields;

		if ( empty( $geodir_ss_range_fields ) ) {
			$geodir_ss_range_fields = array();
		}

		if ( ! empty( $geodir_ss_range_fields[ $post_type ] ) ) {
			return $geodir_ss_range_fields[ $post_type ];
		}

		$results = $wpdb->get_col( $wpdb->prepare( "SELECT `htmlvar_name` FROM `" . GEODIR_ADVANCE_SEARCH_TABLE . "` WHERE `post_type` = %s AND `input_type` = 'RANGE' ORDER BY `sort_order` ASC", $post_type ) );

		$geodir_ss_range_fields[ $post_type ] = $results;

		return $results;
	}

	public static function get_date_fields( $post_type ) {
		global $wpdb, $geodir_ss_range_fields;

		if ( empty( $geodir_ss_range_fields ) ) {
			$geodir_ss_range_fields = array();
		}

		if ( ! empty( $geodir_ss_range_fields[ $post_type . 'date' ] ) ) {
			return $geodir_ss_range_fields[ $post_type . 'date' ];
		}

		$results = $wpdb->get_col( $wpdb->prepare( "SELECT `htmlvar_name` FROM `" . GEODIR_ADVANCE_SEARCH_TABLE . "` WHERE `post_type` = %s AND `field_type` = 'datepicker' ORDER BY `sort_order` ASC", $post_type ) );

		$geodir_ss_range_fields[ $post_type . 'date' ] = $results;

		return $results;
	}

	public static function get_time_fields( $post_type ) {
		global $wpdb, $geodir_ss_range_fields;

		if ( empty( $geodir_ss_range_fields ) ) {
			$geodir_ss_range_fields = array();
		}

		if ( ! empty( $geodir_ss_range_fields[ $post_type . 'time' ] ) ) {
			return $geodir_ss_range_fields[ $post_type . 'time' ];
		}

		$results = $wpdb->get_col( $wpdb->prepare( "SELECT `htmlvar_name` FROM `" . GEODIR_ADVANCE_SEARCH_TABLE . "` WHERE `post_type` = %s AND `field_type` = 'time' ORDER BY `sort_order` ASC", $post_type ) );

		$geodir_ss_range_fields[ $post_type . 'time' ] = $results;

		return $results;
	}

	public static function get_pending_emails( $limit = 0 ) {
		global $wpdb;

		$limit = (int) $limit > 0 ? " LIMIT " . (int) $limit : '';

		$results = $wpdb->get_results( "SELECT DISTINCT `gde`.`subscriber_id`, `gde`.`email_action`, `gds`.* FROM `" . GEODIR_SAVE_SEARCH_EMAILS_TABLE . "` AS `gde` LEFT JOIN `" . GEODIR_SAVE_SEARCH_SUBSCRIBERS_TABLE . "` AS `gds` ON `gds`.`subscriber_id` = `gde`.`subscriber_id` WHERE `status` = 'pending' ORDER BY `gde`.`date_added` ASC" . $limit );

		return $results;
	}

	public static function get_pending_email_posts( $subscriber_id ) {
		global $wpdb;

		$results = $wpdb->get_results( $wpdb->prepare( "SELECT DISTINCT `post_id`, post_title, post_url FROM `" . GEODIR_SAVE_SEARCH_EMAILS_TABLE . "` WHERE `status` = 'pending' AND `subscriber_id` = %d ORDER BY `date_added` ASC", $subscriber_id ) );

		return $results;
	}

	public static function update_email_sent( $subscriber_id ) {
		global $wpdb;

		$data = array(
			'status' => 'sent',
			'date_sent' => current_time( 'mysql' )
		);

		$format = array(
			'%s',
			'%s'
		);

		$where = array(
			'subscriber_id' => $subscriber_id,
			'status' => 'pending'
		);

		$where_format = array(
			'%d',
			'%s'
		);

		return $wpdb->update( GEODIR_SAVE_SEARCH_EMAILS_TABLE, $data, $where, $format, $where_format );
	}

	public static function count_emails_sent( $subscriber_id ) {
		global $wpdb;

		return (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT( * ) AS `sent` FROM `" . GEODIR_SAVE_SEARCH_EMAILS_TABLE . "` WHERE `status` = 'sent' AND `subscriber_id` = %d", $subscriber_id ) );
	}

	public static function count_emails( $status = '' ) {
		global $wpdb;

		$where = array();
		if ( $status == 'pending' ) {
			$where[] = "`status` = 'pending'";
		} else if ( $status == 'sent' ) {
			$where[] = "`status` = 'sent'";
		}
		$where = ! empty( $where ) ? "WHERE " . implode( " AND ", $where ) : '';

		return (int) $wpdb->get_var( "SELECT COUNT( * ) FROM `" . GEODIR_SAVE_SEARCH_EMAILS_TABLE . "` {$where}" );
	}
	

	public static function event_has_schedule( $post_id, $dates ) {
		global $wpdb;

		if ( ! defined( 'GEODIR_EVENT_SCHEDULES_TABLE' ) ) {
			return false;
		}

		$where = "";

		if ( is_array( $dates ) ) {
			$from_date = ! empty( $dates['from'] ) ? date_i18n( 'Y-m-d', strtotime( sanitize_text_field( $dates['from'] ) ) ) : '';
			$to_date = ! empty( $dates['to'] ) ? date_i18n( 'Y-m-d', strtotime( sanitize_text_field( $dates['to'] ) ) ) : '';

			if ( ! empty( $from_date ) && ! empty( $to_date ) ) {
				$where .= " AND ( ( '{$from_date}' BETWEEN `start_date` AND `end_date` ) OR ( `start_date` BETWEEN '{$from_date}' AND `end_date` ) ) AND ( ( '{$to_date}' BETWEEN `start_date` AND `end_date` ) OR ( `end_date` BETWEEN `start_date` AND '{$to_date}' ) )";
			} else {
				if ( $from_date || $to_date ) {
					$date = ! empty( $from_date ) ? $from_date : $to_date;

					if ( $from_date ) {
						$where .= " AND ( `start_date` >='{$date}' OR ( '{$date}' BETWEEN `start_date` AND `end_date` ) )";
					} elseif ( $to_date ) {
						$where .= " AND ( `end_date` <='{$date}' OR ( '{$date}' BETWEEN `start_date` AND `end_date` ) )";
					}
				}
			}
		} else {
			$date = date_i18n( 'Y-m-d', strtotime( sanitize_text_field( $dates ) ) );
			$where .= " AND ( '{$date}' BETWEEN `start_date` AND `end_date` )";
		}

		if ( empty( $where ) ) {
			return false;
		}

		return (int) $wpdb->get_var( $wpdb->prepare( "SELECT `schedule_id` FROM `" . GEODIR_EVENT_SCHEDULES_TABLE . "` WHERE `event_id` = %d {$where} LIMIT 1", $post_id ) );
	}
}