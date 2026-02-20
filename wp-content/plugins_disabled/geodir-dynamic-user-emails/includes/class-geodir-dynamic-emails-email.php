<?php
/**
 * Dynamic User Emails Email class
 *
 * @author    AyeCode Ltd
 * @package   GeoDir_Dynamic_Emails
 * @version   2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * GeoDir_Dynamic_Emails_Email class.
 */
class GeoDir_Dynamic_Emails_Email {
	/**
	 * Init.
	 *
	 * @since 2.0.0
	 */
	public static function init() {
		add_filter( 'geodir_email_subject', array( __CLASS__, 'get_subject' ), 1, 3 );
		add_filter( 'geodir_email_content', array( __CLASS__, 'get_content' ), 1, 3 );
		add_filter( 'geodir_email_wild_cards', array( __CLASS__, 'set_wild_cards' ), 999, 4 );
		add_filter( 'geodir_dynamic_emails_scheduled_emails', array( __CLASS__, 'cron_schedule_emails' ) );
		add_action( 'shutdown', array( __CLASS__, 'dispatch_emails' ) );
	}

	public static function email_actions( $options_only = true ) {
		$actions_list = array(
			'instant' => array(
				'title' => __( 'Instant', 'geodir-dynamic-emails' ),
				'supports' => array( 'post_type' ),
				'subject' => __( '[[#site_name#]] Instant Email Subject', 'geodir-dynamic-emails' ),
				'template' => __( 'Hello [#to_name#],

Instant email content.

Thank You.', 'geodir-dynamic-emails' )
			),
			'user_register' => array(
				'title' => __( 'User Registration', 'geodir-dynamic-emails' ),
				'supports' => array(),
				'subject' => __( '[[#site_name#]] User Registration Email Subject', 'geodir-dynamic-emails' ),
				'template' => __( 'Hello [#to_name#],

User Registration email content.

Thank You.', 'geodir-dynamic-emails' )
			),
			'new_post' => array(
				'title' => __( 'New Listing', 'geodir-dynamic-emails' ),
				'supports' => array( 'post_type' ),
				'subject' => __( '[[#site_name#]] New Listing Email Subject', 'geodir-dynamic-emails' ),
				'template' => __( 'Hello [#to_name#],

New Listing email content.

Thank You.', 'geodir-dynamic-emails' )
			),
			'edit_post' => array(
				'title' => __( 'Edit Listing', 'geodir-dynamic-emails' ),
				'supports' => array( 'post_type' ),
				'subject' => __( '[[#site_name#]] Edit Listing Email Subject', 'geodir-dynamic-emails' ),
				'template' => __( 'Hello [#to_name#],

Edit Listing email content.

Thank You.', 'geodir-dynamic-emails' )
			),
			'new_comment' => array(
				'title' => __( 'New Comment/Review on Listing', 'geodir-dynamic-emails' ),
				'supports' => array( 'post_type' ),
				'subject' => __( '[[#site_name#]] A new comment has been submitted on your listing [#listing_title#]', 'geodir-dynamic-emails' ),
				'template' => __( 'Hello [#to_name#],

A new comment has been submitted on your listing [#listing_link#].

Thank You.', 'geodir-dynamic-emails' )
			),
			'review_response' => array(
				'title' => __( 'Response to Review Owner', 'geodir-dynamic-emails' ),
				'supports' => array( 'post_type' ),
				'subject' => __( '[[#site_name#]] Thank You for your Review on [#listing_title#]', 'geodir-dynamic-emails' ),
				'template' => __( 'Hello [#to_name#],

Thank you so much for your valuable feedback on [#listing_link#]! We really appreciate you taking the time to share your experience with us and we would love to see you again.

Thank You.', 'geodir-dynamic-emails' )
			)
		);

		if ( $options_only ) {
			$actions = array();

			foreach ( $actions_list as $action => $data ) {
				$actions[ $action ] = $data['title'];
			}
		} else {
			$actions = $actions_list;
		}

		return apply_filters( 'geodir_dynamic_emails_actions', $actions, $actions_list, $options_only );
	}

	public static function email_intervals() {
		$options = array(
			'0' => __( 'Immediately on Post Published / Updated', 'geodir-dynamic-emails' )
		);

		for ( $i = 1; $i <= 24; $i++ ) {
			$options[ $i * HOUR_IN_SECONDS ] = $i > 1 ? wp_sprintf( __( 'Every %d Hours', 'geodir-dynamic-emails' ), $i ) : wp_sprintf( __( 'Every %d Hour', 'geodir-dynamic-emails' ), $i );
		}

		return apply_filters( 'geodir_dynamic_emails_intervals', $options );
	}

	/**
	 * Global email tags.
	 *
	 * @since 2.0.0
	 *
	 * @param bool $inline Optional. Email tag inline value. Default true.
	 * @return array|string $tags.
	 */
	public static function global_email_tags( $inline = true ) { 
		$tags = array( '[#blogname#]', '[#site_name#]', '[#site_url#]', '[#site_name_url#]', '[#login_url#]', '[#login_link#]', '[#date#]', '[#time#]', '[#date_time#]', '[#current_date#]', '[#to_name#]', '[#to_email#]', '[#from_name#]', '[#from_email#]', '[#post_id#]', '[#post_status#]', '[#post_date#]', '[#post_author_ID#]', '[#post_author_name#]', '[#client_name#]', '[#listing_title#]', '[#listing_url#]', '[#listing_link#]', '[#comment_ID#]', '[#comment_author#]', '[#comment_author_IP#]', '[#comment_author_email#]', '[#comment_date#]', '[#comment_content#]', '[#comment_url#]', '[#comment_post_ID#]', '[#comment_post_title#]', '[#comment_post_url#]', '[#comment_post_link#]', '[#review_rating_star#]', '[#review_rating_title#]', '[#review_city#]', '[#review_region#]', '[#review_country#]', '[#review_latitude#]', '[#review_longitude#]' );
		
		$tags = apply_filters( 'geodir_email_global_email_tags', $tags );

		if ( $inline ) {
			$tags = '<code>' . implode( '</code> <code>', $tags ) . '</code>';
		}
		
		return $tags;
	}

	/**
	 * Plugin email tags.
	 *
	 * @since 2.0.0
	 *
	 * @param bool $inline Optional. Email tag inline value. Default true.
	 * @return array|string $tags.
	 */
	public static function plugin_email_tags( $inline = true ) { 
		$tags = array( '[#post_type_name#]', '[#post_type_singular_name#]' );

		$tags = apply_filters( 'geodir_dynamic_emails_plugin_email_tags', $tags );

		if ( $inline ) {
			$tags = '<code>' . implode( '</code> <code>', $tags ) . '</code>';
		}
		
		return $tags;
	}

	/**
	 * Email tags.
	 *
	 * @since 2.0.0
	 *
	 * @param bool $inline Optional. Email tag inline value. Default true.
	 * @return array|string $tags.
	 */
	public static function email_tags( $inline = true ) { 
		$email_tags = self::global_email_tags( false );
		$local_tags = self::plugin_email_tags( false );

		if ( ! empty( $local_tags ) ) {
			$email_tags = array_merge( $email_tags, $local_tags );
		}

		$email_tags = apply_filters( 'geodir_dynamic_emails_email_tags', $email_tags );

		if ( $inline ) {
			$email_tags = '<code>' . implode( '</code> <code>', $email_tags ) . '</code>';
			$email_tags = str_replace( '<code>[#post_id#]</code>', '<div class="d-block w-100 mt-1"></div>' . __( 'Post tags:', 'geodir-dynamic-emails' ) . ' <code>[#post_id#]</code>', $email_tags );
			$email_tags = str_replace( '<code>[#comment_ID#]</code>', '<div class="d-block w-100 mt-1"></div>' . __( 'Comment tags:', 'geodir-dynamic-emails' ) . ' <code>[#comment_ID#]</code>', $email_tags );
		}
		
		return $email_tags;
	}

	public static function email_subject(){
		return apply_filters( 'geodir_dynamic_emails_email_subject', __( '[[#site_name#]] EMAIL SUBJECT HERE', 'geodir-dynamic-emails' ) );
	}

	/**
	 * The listing owner comment submit email body default.
	 *
	 * @return mixed|void
	 */
	public static function email_body(){
		return apply_filters( 'geodir_dynamic_emails_email_body', __( 'Hello [#to_name#],

EMAIL CONTENT HERE.

Thank You.', 'geodir-dynamic-emails' ) );
	}

	public static function get_subject( $subject, $email_name = '', $email_vars = array() ) {
		if ( ! empty( $subject ) ) {
			return $subject;
		}

		if ( ! ( strpos( $email_name, 'dynamic-' ) === 0 && ! empty( $email_vars['email_list'] ) ) ) {
			return $subject;
		}

		$subject = $email_vars['email_list']->subject;

		if ( $subject ) {
			$subject = GeoDir_Email::replace_variables( __( $subject, 'geodirectory' ), $email_name, $email_vars );
		}

		return $subject;
	}

	public static function get_content( $content, $email_name = '', $email_vars = array() ) {
		if ( ! empty( $content ) ) {
			return $content;
		}

		if ( ! ( strpos( $email_name, 'dynamic-' ) === 0 && ! empty( $email_vars['email_list'] ) ) ) {
			return $content;
		}

		$content = $email_vars['email_list']->template;

		if ( $content ) {
			$content = GeoDir_Email::replace_variables( __( $content, 'geodirectory' ), $email_name, $email_vars );
		}

		return $content;
	}

	public static function set_wild_cards( $wild_cards, $content, $email_name, $email_vars = array() ) {
		if ( strpos( $email_name, 'dynamic-' ) === 0 ) {
			$local_tags = self::plugin_email_tags( false );

			if ( ! empty( $email_vars['post'] ) ) {
				$wild_cards[ '[#post_type_name#]' ] = geodir_post_type_name( $email_vars['post']->post_type, true );
				$wild_cards[ '[#post_type_singular_name#]' ] = geodir_post_type_singular_name( $email_vars['post']->post_type, true );
			}

			foreach ( $local_tags as $tag ) {
				if ( ! isset( $wild_cards[ $tag ] ) ) {
					$wild_cards[ $tag ] = '';
				}
			}
		}

		return $wild_cards;
	}

	public static function send_emails( $limit = 0, $email_logs = array() ) {
		$sent = 0;

		$send = (int) geodir_get_option( 'email_user_dynamic_emails', 1 ) == 1 && geodir_design_style() ? true : false;
		$send = apply_filters( 'geodir_dynamic_emails_send_emails', $send );

		if ( ! $send ) {
			return $sent;
		}

		$pending_items = GeoDir_Dynamic_Emails_User::get_pending_items( array( 'limit' => $limit, 'email_log__in' => $email_logs ) );

		if ( ! empty( $pending_items ) ) {
			foreach ( $pending_items as $email_user ) {
				$_sent = GeoDir_Dynamic_Emails_Email::send_email( $email_user );

				if ( $_sent !== null ) {
					$sent++;

					GeoDir_Dynamic_Emails_User::mark_item_sent( (int) $email_user->email_user_id, $email_user );
				}
			}
		}

		return $sent;
	}

	public static function cron_schedule_emails() {
		if ( (int) geodir_dynamic_emails_schedule_interval() > 0 ) {
			$sent = (int) self::send_emails( absint( geodir_dynamic_emails_limit() ) );

			geodir_error_log( $sent, 'Dynamic User Emails Cron Emails Sent' );
		}
	}

	public static function dispatch_emails() {
		global $geodir_de_pending_log;

		$interval = (int) geodir_dynamic_emails_schedule_interval();

		// Check cron schedules
		if ( $interval > 0 && ! wp_doing_ajax() && is_admin() && $interval != (int) geodir_get_option( 'dynamic_emails_interval_time' ) ) {
			wp_clear_scheduled_hook( 'geodir_dynamic_emails_scheduled_emails' );

			// Reschedule
			geodir_dynamic_emails_schedule_events();
		}

		if ( ! empty( $geodir_de_pending_log ) && (int) geodir_get_option( 'dynamic_emails_trigger_send' ) === 1 ) {
			$limit = apply_filters( 'geodir_dynamic_emails_limit_instant', 5 );

			self::send_emails( $limit, $geodir_de_pending_log );

			geodir_update_option( 'dynamic_emails_trigger_send', 0 );
		}
	}

	public static function send_email( $email_user, $args = array() ) {
		if ( empty( $email_user ) ) {
			return null;
		}

		if ( ! GeoDir_Email::is_email_enabled( 'user_dynamic_emails' ) ) {
			return null;
		}

		$args['email_user'] = $email_user;

		$check = apply_filters( 'geodir_dynamic_emails_disable_email', null, $args );
		if ( $check === true ) {
			return null;
		}

		if ( empty( $args['email_list'] ) ) {
			$args['email_list'] = GeoDir_Dynamic_Emails_List::get_item( (int) $email_user->email_list_id );
		}

		if ( empty( $args['email_list'] ) ) {
			return null;
		}

		$args['email_list']->meta = ! empty( $args['email_list']->meta ) ? GeoDir_Dynamic_Emails_Fields::parse_meta( $args['email_list']->meta ) : array();
		$to_listing_email = ! empty( $args['email_list']->meta['recipient'] ) && $args['email_list']->meta['recipient'] == 'listing_email' ? true : false;

		$email_name = 'dynamic-' . $args['email_list']->action;

		$check = apply_filters( 'geodir_dynamic_emails_check_send_email', null, $email_name, $args );
		if ( null !== $check ) {
			return null;
		}

		if ( empty( $args['userdata'] ) ) {
			$_userdata = GeoDir_Dynamic_Emails_User::get_meta( $email_user->meta, 'userdata' );

			if ( ! empty( $_userdata ) && isset( $_userdata->ID ) && (int)  $_userdata->ID == (int) $email_user->user_id ) {
				$args['userdata'] = $_userdata;
			} else {
				$args['userdata'] = GeoDir_Dynamic_Emails_User::get_userdata( (int) $email_user->user_id );
			}
		}

		if ( empty( $args['userdata'] ) ) {
			return null;
		}

		$recipient = $args['userdata']->user_email;

		if ( ! is_email( $recipient ) && ! $to_listing_email ) {
			return null;
		}

		if ( ! ( ! empty( $args['post'] ) && isset( $args['post']->post_category ) ) ) {
			if ( ! empty( $args['gd_post'] ) ) {
				$args['post'] = $args['gd_post'];
			} else {
				$_gd_post = GeoDir_Dynamic_Emails_User::get_meta( $email_user->meta, 'gd_post' );

				if ( ! empty( $_gd_post ) && isset( $_gd_post->ID ) && (int)  $_gd_post->ID == (int) $email_user->post_id ) {
					$args['post'] = $_gd_post;
				}
			}
		}

		if ( empty( $args['post'] ) && ! empty( $email_user->post_id ) ) {
			$args['post'] = geodir_get_post_info( (int) $email_user->post_id );
		}

		// Set listing email as recipient.
		if ( $to_listing_email && ! empty( $args['post'] ) && in_array( 'email', array_keys( (array) $args['post'] ) ) ) {
			if ( ! empty( $args['post']->email ) ) {
				$recipient = $args['post']->email;
			} else {
				$recipient = '';
			}
		}

		$email_vars = $args;
		$email_vars['to_name'] = html_entity_decode( GeoDir_Dynamic_Emails_User::get_display_name( $args['userdata'] ), ENT_COMPAT, 'UTF-8' );
		$email_vars['to_email'] = $recipient;

		$email_vars = apply_filters( 'geodir_dynamic_emails_filter_email_vars', $email_vars, $email_name, $args, $email_user );

		if ( ! is_email( $email_vars['to_email'] ) ) {
			return null;
		}

		do_action( 'geodir_dynamic_emails_pre_send_email', $email_name, $email_vars );

		$subject      = GeoDir_Email::get_subject( $email_name, $email_vars );
		$message_body = GeoDir_Email::get_content( $email_name, $email_vars );
		$headers      = GeoDir_Email::get_headers( $email_name, $email_vars );
		$attachments  = GeoDir_Email::get_attachments( $email_name, $email_vars );

		$plain_text = GeoDir_Email::get_email_type() != 'html' ? true : false;
		$template = $plain_text ? 'emails/plain/email-' . $email_name . '.php' : 'emails/email-' . $email_name . '.php';

		$template = apply_filters( 'geodir_dynamic_emails_email_template', $template, $plain_text, $email_name, $email_vars );

		$content = geodir_get_template_html( $template, array(
			'email_name'    => $email_name,
			'email_vars'    => $email_vars,
			'email_heading' => '',
			'sent_to_admin' => false,
			'plain_text'    => $plain_text,
			'message_body'  => $message_body,
		) );

		$sent = GeoDir_Email::send( $email_vars['to_email'], $subject, $content, $headers, $attachments, $email_name, $email_vars );

		if ( GeoDir_Email::is_admin_bcc_active( $email_name ) ) {
			$recipient = GeoDir_Email::get_admin_email();
			$subject .= ' - ADMIN BCC COPY';
			GeoDir_Email::send( $recipient, $subject, $content, $headers, $attachments, $email_name, $email_vars );
		}

		do_action( 'geodir_dynamic_emails_post_send_email', $email_name, $email_vars, $sent );

		return $sent;
	}
}