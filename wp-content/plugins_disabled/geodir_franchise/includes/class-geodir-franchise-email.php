<?php
/**
 * Franchise Manager Email class.
 *
 * @since 2.0.0
 * @package Geodir_Franchise
 * @author AyeCode Ltd
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * GeoDir_Franchise_Email class.
 */
class GeoDir_Franchise_Email {

	public static function init() {
		if ( is_admin() ) {
			//add_filter( 'geodir_email_settings', array( __CLASS__, 'filter_email_settings' ), 11, 1 );
			//add_filter( 'geodir_admin_email_settings', array( __CLASS__, 'filter_admin_email_settings' ), 11, 1 );
			//add_filter( 'geodir_user_email_settings', array( __CLASS__, 'filter_user_email_settings' ), 11, 1 );
		}

		//add_filter( 'geodir_email_subject', array( __CLASS__, 'get_subject' ), 30, 3 );
		//add_filter( 'geodir_email_content', array( __CLASS__, 'get_content' ), 30, 3 );
		//add_filter( 'geodir_email_wild_cards', array( __CLASS__, 'set_wild_cards' ), 30, 4 );
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

	public static function filter_email_settings( $settings ) {
		if ( $merge_settings = self::bcc_email_settings() ) {
			$position = count( $settings ) - 1;
			$settings = array_merge( array_slice( $settings, 0, $position ), $merge_settings, array_slice( $settings, $position ) );
		}

		return $settings;
	}

	public static function filter_admin_email_settings( $settings ) {
		if ( $merge_settings = self::admin_email_settings() ) {
			$position = count( $settings );
			$settings = array_merge( array_slice( $settings, 0, $position ), $merge_settings, array_slice( $settings, $position ) );
		}

		return $settings;
	}

	public static function filter_user_email_settings( $settings ) {
		if ( $merge_settings = self::user_email_settings() ) {
			$position = count( $settings );
			$settings = array_merge( array_slice( $settings, 0, $position ), $merge_settings, array_slice( $settings, $position ) );
		}

		return $settings;
	}

	public static function bcc_email_settings() {
		$settings = array(
			array(
				'type' => 'checkbox',
				'id' => 'email_bcc_user_franchise_approved',
				'name' => __( 'Franchise listing approved', 'geodir-franchise' ),
				'desc' => __( 'This will send a BCC email to the site admin on franchise listing approved.', 'geodir-franchise' ),
				'default' => 0,
				'advanced' => true
			),
		);

		return apply_filters( 'geodir_franchise_bcc_email_settings', $settings );
	}

	public static function admin_email_settings() {
		$settings = array();

		return apply_filters( 'geodir_franchise_admin_email_settings', $settings );
	}

	public static function user_email_settings() {
		$settings = array(
			// Franchise listing approved email to user.
			array(
				'type' => 'title',
				'id' => 'email_user_franchise_approved_settings',
				'name' => __( 'Franchise listing approved', 'geodir-franchise' ),
				'desc' => '',
			),
			array(
				'type' => 'checkbox',
				'id' => 'email_user_franchise_approved',
				'name' => __( 'Enable email', 'geodir-franchise' ),
				'desc' => __( 'Send an email to user on franchise listing approved.', 'geodir-franchise' ),
				'default' => 1,
			),
			array(
				'type' => 'text',
				'id' => 'email_user_franchise_approved_subject',
				'name' => __( 'Subject', 'geodir-franchise' ),
				'desc' => __( 'The email subject.', 'geodir-franchise' ),
				'class' => 'active-placeholder',
				'desc_tip' => true,
				'placeholder' => self::email_user_franchise_approved_subject(),
				'advanced' => true
			),
			array(
				'type' => 'textarea',
				'id' => 'email_user_franchise_approved_body',
				'name' => __( 'Body', 'geodir-franchise' ),
				'desc' => __( 'The email body, this can be text or HTML.', 'geodir-franchise' ),
				'class' => 'code gd-email-body',
				'desc_tip' => true,
				'advanced' => true,
				'placeholder' => self::email_user_franchise_approved_body(),
				'custom_desc' => __( 'Available template tags:', 'geodir-franchise' ) . ' ' . self::user_franchise_approved_email_tags()
			),
			array(
				'type' => 'sectionend',
				'id' => 'email_user_franchise_approved_settings'
			)
		);

		return apply_filters( 'geodir_franchise_user_email_settings', $settings );
	}

	/**
	 * Global email tags.
	 *
	 * @since  2.0.0
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
	 * Franchise manager email tags.
	 *
	 * @since  2.0.0
	 *
	 * @param bool $inline Optional. Email tag inline value. Default true.
	 * @return array|string $tags.
	 */
	public static function franchise_email_tags( $inline = true ) { 
		$tags = array( '[#post_id#]', '[#post_status#]', '[#post_date#]', '[#post_author_ID#]', '[#post_author_name#]', '[#client_name#]', '[#listing_title#]', '[#listing_url#]', '[#listing_link#]' );
		
		$tags = apply_filters( 'geodir_franchise_email_tags', $tags );

		if ( $inline ) {
			$tags = '<code>' . implode( '</code> <code>', $tags ) . '</code>';
		}
		
		return $tags;
	}

	public static function set_wild_cards( $wild_cards, $content, $email_name, $email_vars = array() ) {
		switch ( $email_name ) {
			case 'user_franchise_approved':
				$params = array();

				if ( ! empty( $email_vars['franchise_post'] ) ) {
					$franchise_post = $email_vars['franchise_post'];

					$params['franchise_post_id'] = $franchise_post->ID;
					$params['franchise_post_title'] = get_the_title( $franchise_post->ID );
					$params['franchise_post_url'] = get_permalink( $franchise_post->ID );
					$params['franchise_post_link'] = '<a href="' . esc_url( $params['franchise_post_url'] ) . '">' . $params['franchise_post_title'] . '</a>';
				}

				$params = wp_parse_args( $params, array(
					'franchise_post_id' => '',
					'franchise_post_title' => '',
					'franchise_post_url' => '',
					'franchise_post_link' => '',
				) );

				foreach ( $params as $key => $value ) {
					if ( ! isset( $email_vars[ '[#' . $key . '#]' ] ) ) {
						$wild_cards[ '[#' . $key . '#]' ] = $value;
					}
				}
			break;
		}

		return $wild_cards;
	}

	/**
	 * Email tags for franchise listings emails.
	 *
	 * @since  2.0.0
	 *
	 * @param bool $inline Optional. Email tag inline value. Default true.
	 * @return array|string $tags.
	 */
	public static function email_tags( $inline = true ) { 
		$email_tags = self::global_email_tags( false );
		$franchise_email_tags = self::franchise_email_tags( false );

		if ( ! empty( $franchise_email_tags ) ) {
			$email_tags = array_merge( $email_tags, $franchise_email_tags );
		}

		if ( $inline ) {
			$email_tags = '<code>' . implode( '</code> <code>', $email_tags ) . '</code>';
		}
		
		return $email_tags;
	}

	public static function email_user_franchise_approved_subject() {
		$subject = __( '[[#site_name#]] Franchise listings has been Approved', 'geodir-franchise' );

		return apply_filters( 'geodir_franchise_email_user_franchise_approved_subject', $subject );
	}

	public static function email_user_franchise_approved_body() {
		$body = "" . 
__( "Dear [#client_name#],

Your franchise [#franchise_listing_link#] of the lisitng [#listing_link#] has been published.

Thank You.", "geodir-franchise" );

		return apply_filters( 'geodir_franchise_email_user_franchise_approved_body', $body );
	}

	public static function user_franchise_approved_email_tags( $inline = true ) { 
		$email_tags = self::email_tags( false );

		$tags = array_merge( $email_tags, array( '[#franchise_post_id#], [#franchise_post_title#], [#franchise_post_url#], [#franchise_post_link#]' ) );

		$tags = apply_filters( 'geodir_franchise_user_franchise_approved_email_tags', $tags );

		if ( $inline ) {
			$tags = '<code>' . implode( '</code> <code>', $tags ) . '</code>';
		}

		return $tags;
	}

	// Franchise listing approved email to user
	public static function send_user_franchise_approved_email( $franchise_post, $post, $data = array() ) {
		$email_name = 'user_franchise_approved';

		if ( ! GeoDir_Email::is_email_enabled( $email_name ) ) {
			return false;
		}

		$author_data = get_userdata( $franchise_post->post_author );
		if ( empty( $author_data ) ) {
			return false;
		}

		$recipient = ! empty( $author_data->user_email ) ? $author_data->user_email : '';

		if ( empty( $franchise_post ) || empty( $post ) || ! is_email( $recipient ) ) {
			return;
		}

		$email_vars             = $data;
		$email_vars['post']     = $post;
		$email_vars['franchise_post']	= $franchise_post;
		$email_vars['to_name']  = geodir_get_client_name( $franchise_post->post_author );
		$email_vars['to_email'] = $recipient;

		do_action( 'geodir_franchise_pre_' . $email_name . '_email', $email_name, $email_vars );

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
			'sent_to_admin' => true,
			'plain_text'    => $plain_text,
			'message_body'  => $message_body,
		) );

		$sent = GeoDir_Email::send( $recipient, $subject, $content, $headers, $attachments );

		if ( GeoDir_Email::is_admin_bcc_active( $email_name ) ) {
			$recipient = GeoDir_Email::get_admin_email();
			$subject .= ' - ADMIN BCC COPY';
			GeoDir_Email::send( $recipient, $subject, $content, $headers, $attachments );
		}

		do_action( 'geodir_franchise_post_' . $email_name . '_email', $email_vars );

		return $sent;
	}
}