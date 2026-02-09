<?php
/**
 * Save Search Email class
 *
 * @author    AyeCode Ltd
 * @package   GeoDir_Save_Search
 * @version   1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * GeoDir_Save_Search_Email class.
 */
class GeoDir_Save_Search_Email {
	/**
	 * Init.
	 *
	 * @since 1.0
	 */
	public static function init() {
		add_filter( 'geodir_email_subject', array( __CLASS__, 'get_subject' ), 10, 3 );
		add_filter( 'geodir_email_content', array( __CLASS__, 'get_content' ), 10, 3 );
		add_filter( 'geodir_email_wild_cards', array( __CLASS__, 'set_wild_cards' ), 10, 4 );
		add_filter( 'geodir_save_search_scheduled_emails', array( __CLASS__, 'cron_schedule_emails' ) );
		add_action( 'shutdown', array( __CLASS__, 'dispatch_emails' ), 1 );
		add_action( 'template_redirect', array( __CLASS__, 'unsubscribe' ), 1 );
	}

	public static function email_intervals() {
		$options = array(
			'0' => __( 'Immediately on Post Published / Updated', 'geodir-save-search' )
		);

		for ( $i = 1; $i <= 24; $i++ ) {
			$options[ $i * HOUR_IN_SECONDS ] = $i > 1 ? wp_sprintf( __( 'Every %d Hours', 'geodir-save-search' ), $i ) : wp_sprintf( __( 'Every %d Hour', 'geodir-save-search' ), $i );
		}

		return apply_filters( 'geodir_save_search_email_intervals', $options );
	}

	public static function email_user_save_search_subject(){
		return apply_filters( 'geodir_email_user_save_search_subject', __( '[[#site_name#]] New [#post_type_name#] matching with your saved search', 'geodir-save-search' ) );
	}

	public static function email_user_save_search_body(){
		return apply_filters( 'geodir_email_user_save_search_body', __( 'Dear [#to_name#],

New [#post_type_name#] are available matching with your saved search: <b>[#search_name#]</b>.

[#listing_links#]

Thank you for your contribution.

[#unsubscribe_link#]', 'geodir-save-search' )
		);
	}

	public static function email_user_save_search_edit_subject(){
		return apply_filters( 'geodir_email_user_save_search_edit_subject', __( '[[#site_name#]] [#post_type_name#] matching with your saved search', 'geodir-save-search' ) );
	}

	public static function email_user_save_search_edit_body(){
		return apply_filters( 'geodir_email_user_save_search_edit_body', __( 'Dear [#to_name#],

[#post_type_name#] are available matching with your saved search: <b>[#search_name#]</b>.

[#listing_links#]

Thank you for your contribution.

[#unsubscribe_link#]', 'geodir-save-search' )
		);
	}

	/**
	 * Global email tags.
	 *
	 * @since 1.0
	 *
	 * @param bool $inline Optional. Email tag inline value. Default true.
	 * @return array|string $tags.
	 */
	public static function global_email_tags( $inline = true ) { 
		$tags = array( '[#blogname#]', '[#site_name#]', '[#site_url#]', '[#site_name_url#]', '[#login_url#]', '[#login_link#]', '[#date#]', '[#time#]', '[#date_time#]', '[#current_date#]', '[#to_name#]', '[#to_email#]', '[#from_name#]', '[#from_email#]' );
		
		$tags = apply_filters( 'geodir_email_global_email_tags', $tags );

		if ( $inline ) {
			$tags = '<code>' . implode( '</code> <code>', $tags ) . '</code>';
		}
		
		return $tags;
	}

	/**
	 * Plugin email tags.
	 *
	 * @since 1.0
	 *
	 * @param bool $inline Optional. Email tag inline value. Default true.
	 * @return array|string $tags.
	 */
	public static function local_email_tags( $inline = true ) { 
		$tags = array();
		
		$tags = apply_filters( 'geodir_save_search_local_email_tags', $tags );

		if ( $inline ) {
			$tags = '<code>' . implode( '</code> <code>', $tags ) . '</code>';
		}
		
		return $tags;
	}

	/**
	 * Email tags for save search emails.
	 *
	 * @since 1.0
	 *
	 * @param bool $inline Optional. Email tag inline value. Default true.
	 * @return array|string $tags.
	 */
	public static function email_tags( $inline = true ) { 
		$email_tags = self::global_email_tags( false );
		$local_tags = self::local_email_tags( false );

		if ( ! empty( $local_tags ) ) {
			$email_tags = array_merge( $email_tags, $local_tags );
		}

		if ( $inline ) {
			$email_tags = '<code>' . implode( '</code> <code>', $email_tags ) . '</code>';
		}
		
		return $email_tags;
	}

	public static function user_save_search_email_tags( $inline = true ) { 
		$email_tags = self::email_tags( false );

		$tags = array_merge( $email_tags, array( '[#listing_links#]', '[#search_name#]', '[#saved_search_url#]', '[#unsubscribe_url#]', '[#unsubscribe_link#]', '[#post_type_name#]', '[#post_type_singular_name#]' ) );

		$tags = apply_filters( 'geodir_save_search_user_notification_email_tags', $tags );

		if ( $inline ) {
			$tags = '<code>' . implode( '</code> <code>', $tags ) . '</code>';
		}

		return $tags;
	}

	public static function get_subject( $subject, $email_name = '', $email_vars = array() ) {
		if ( ! empty( $subject ) ) {
			return $subject;
		}

		$method = 'email_' . $email_name . '_subject';
		if (  method_exists( __CLASS__, $method ) ) {
			$subject = self::$method();
		}

		if ( $subject ) {
			$subject = GeoDir_Email::replace_variables( __( $subject, 'geodirectory' ), $email_name, $email_vars );
		}

		return $subject;
	}

	public static function get_content( $content, $email_name = '', $email_vars = array() ) {
		if ( ! empty( $content ) ) {
			return $content;
		}

		$method = 'email_' . $email_name . '_body';
		if (  method_exists( __CLASS__, $method ) ) {
			$content = self::$method();
		}

		if ( $content ) {
			$content = GeoDir_Email::replace_variables( __( $content, 'geodirectory' ), $email_name, $email_vars );
		}

		return $content;
	}

	public static function set_wild_cards( $wild_cards, $content, $email_name, $email_vars = array() ) {
		switch ( $email_name ) {
			case 'user_save_search':
			case 'user_save_search_edit':
				$params = array();

				if ( ! empty( $email_vars['listing_links'] ) ) {
					$params['listing_links'] = $email_vars['listing_links'];
				}

				if ( ! empty( $email_vars['search_subscriber'] ) ) {
					$subscriber = $email_vars['search_subscriber'];

					$params['search_name'] = $subscriber->search_name;
					$params['saved_search_url'] = GeoDir_Save_Search_Post::get_url( $subscriber->search_uri );
					$params['unsubscribe_url'] = add_query_arg( array( 'geodir_ss_action' => 'unsubscribe', '_id' => (int) $subscriber->subscriber_id, '_nonce' => md5( 'unsubscribe::' . (int) $subscriber->subscriber_id . '::' . $subscriber->user_email . '::' . $subscriber->date_added ) ), trailingslashit( home_url() ) );
					$params['unsubscribe_link'] = '<a href="' . esc_url( $params['unsubscribe_url'] ) . '" style="color:red;font-size:96%;float:right">' . html_entity_decode( __( 'Unsubscribe', 'geodir-save-search' ), ENT_COMPAT, 'UTF-8' ) . '</a>';
					$params['post_type_name'] = geodir_post_type_name( $subscriber->post_type, true );
					$params['post_type_singular_name'] = geodir_post_type_singular_name( $subscriber->post_type, true );
				}

				$defaults = array(
					'listing_links' => '',
					'post_type_name' => '',
					'post_type_singular_name' => '',
					'search_name' => '',
					'saved_search_url' => '',
					'unsubscribe_url' => '',
					'unsubscribe_link' => ''
				);

				$params = wp_parse_args( $params, $defaults );

				foreach ( $params as $key => $value ) {
					if ( ! isset( $email_vars[ '[#' . $key . '#]' ] ) ) {
						$wild_cards[ '[#' . $key . '#]' ] = $value;
					}
				}
			break;
		}
		return $wild_cards;
	}

	public static function send_emails( $limit = 0 ) {
		$sent = 0;

		$send = ( (int) geodir_get_option( 'email_user_save_search' ) == 1 || (int) geodir_get_option( 'email_user_save_search_edit' ) == 1 ) && geodir_design_style() ? true : false;
		$send = apply_filters( 'geodir_save_search_send_emails', $send );

		if ( ! $send ) {
			return $sent;
		}

		$subscribers = GeoDir_Save_Search_Query::get_pending_emails( $limit );

		if ( empty( $subscribers ) ) {
			return $sent;
		}

		foreach ( $subscribers as $i => $subscriber ) {
			$posts = GeoDir_Save_Search_Query::get_pending_email_posts( $subscriber->subscriber_id );

			if ( empty( $posts ) ) {
				continue;
			}

			$post_links = array();

			foreach ( $posts as $j => $post ) {
				$post_links[] = '- <a href="' . esc_url( $post->post_url ) . '">' . html_entity_decode( $post->post_title, ENT_COMPAT, 'UTF-8' ) . '</a>';
			}

			$post_links = implode( "\n", $post_links );
			$subscriber->posts = $posts;

			GeoDir_Save_Search_Query::update_email_sent( (int) $subscriber->subscriber_id );

			$_sent = self::send_email( $subscriber, array( 'listing_links' => $post_links ) );

			if ( $_sent !== null ) {
				$sent++;
			}
		}

		return $sent;
	}

	public static function send_email( $subscriber, $data = array() ) {
		// Send notification for edited post.
		if ( ! empty( $subscriber->email_action ) && $subscriber->email_action == 'edit_post' ) {
			$email_name = 'user_save_search_edit';
		} else {
			$email_name = 'user_save_search';
		}

		if ( ! GeoDir_Email::is_email_enabled( $email_name ) ) {
			return null;
		}

		$recipient = ! empty( $subscriber->user_email ) ? $subscriber->user_email : '';

		if ( ! is_email( $recipient ) ) {
			return null;
		}

		$email_vars = $data;
		$email_vars['search_subscriber'] = $subscriber;
		$email_vars['to_name']  = html_entity_decode( $subscriber->user_name, ENT_COMPAT, 'UTF-8' );
		$email_vars['to_email'] = $recipient;

		do_action( 'geodir_save_search_pre_user_save_search_email', $email_name, $email_vars );

		$subject      = GeoDir_Email::get_subject( $email_name, $email_vars );
		$message_body = GeoDir_Email::get_content( $email_name, $email_vars );
		$headers      = GeoDir_Email::get_headers( $email_name, $email_vars );
		$attachments  = GeoDir_Email::get_attachments( $email_name, $email_vars );

		$plain_text = GeoDir_Email::get_email_type() != 'html' ? true : false;
		$template   = $plain_text ? 'emails/plain/email-' . $email_name . '.php' : 'emails/email-' . $email_name . '.php';

		$content = geodir_get_template_html( $template, array(
			'email_name'    => $email_name,
			'email_vars'    => $email_vars,
			'email_heading'	=> '',
			'sent_to_admin' => false,
			'plain_text'    => $plain_text,
			'message_body'  => $message_body,
		) );

		$sent = GeoDir_Email::send( $recipient, $subject, $content, $headers, $attachments, $email_name, $email_vars );

		if ( GeoDir_Email::is_admin_bcc_active( $email_name ) ) {
			$recipient = GeoDir_Email::get_admin_email();
			$subject .= ' - ADMIN BCC COPY';
			GeoDir_Email::send( $recipient, $subject, $content, $headers, $attachments, $email_name, $email_vars );
		}

		do_action( 'geodir_save_search_post_user_save_search', $email_name, $email_vars, $sent );

		return $sent;
	}

	public static function cron_schedule_emails() {
		if ( (int) geodir_get_option( 'save_search_interval' ) > 0 ) {
			$sent = (int) self::send_emails( absint( geodir_get_option( 'save_search_limit' ) ) );

			geodir_error_log( $sent, 'Save Search Cron Emails Sent' );
		}
	}

	public static function dispatch_emails() {
		$interval = (int) geodir_get_option( 'save_search_interval' );

		// Check cron schedules
		if ( $interval > 0 && ! wp_doing_ajax() && is_admin() && $interval != (int) geodir_get_option( 'save_search_interval_time' ) ) {
			wp_clear_scheduled_hook( 'geodir_save_search_scheduled_emails' );

			// Reschedule
			geodir_save_search_schedule_events();
		}

		if ( ! ( (int) geodir_get_option( 'save_search_trigger_send' ) == 1 && $interval == 0 ) ) {
			return;
		}

		self::send_emails();

		geodir_update_option( 'save_search_trigger_send', 0 );
	}

	public static function unsubscribe() {
		if ( ! ( ! empty( $_REQUEST['geodir_ss_action'] ) && $_REQUEST['geodir_ss_action'] == 'unsubscribe' && ! empty( $_REQUEST['_id'] ) && ! empty( $_REQUEST['_nonce'] ) ) ) {
			return;
		}

		$subscriber = GeoDir_Save_Search_Query::get_subscriber( (int) $_REQUEST['_id'] );

		if ( ! ( ! empty( $subscriber ) && sanitize_text_field( $_REQUEST['_nonce'] ) == md5( 'unsubscribe::' . $subscriber->subscriber_id . '::' . $subscriber->user_email . '::' . $subscriber->date_added ) ) ) {
			wp_safe_redirect( home_url() );
			exit;
		}

		$unsubscribe = GeoDir_Save_Search_Query::delete_subscriber( (int) $_REQUEST['_id'] );

		if ( $unsubscribe ) {
			echo geodir_get_template_html( geodir_design_style() . '/save-search-unsubscribe.php', array( 'subscriber' => $subscriber ), '', geodir_save_search_templates_path() );
		} else {
			wp_safe_redirect( home_url() );
		}

		exit;
	}
}