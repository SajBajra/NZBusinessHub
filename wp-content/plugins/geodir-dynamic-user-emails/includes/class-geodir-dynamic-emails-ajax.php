<?php
/**
 * Dynamic User Emails AJAX class
 *
 * @author    AyeCode Ltd
 * @package   GeoDir_Dynamic_Emails
 * @version   2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * GeoDir_Dynamic_Emails_AJAX class.
 */
class GeoDir_Dynamic_Emails_AJAX {
	/**
	 * Hook in ajax handlers.
	 */
	public static function init() {
		self::add_ajax_events();
	}

	/**
	 * Hook in methods - uses WordPress ajax handlers (admin-ajax).
	 */
	public static function add_ajax_events() {
		// geodirectory_EVENT => nopriv
		$ajax_events = array(
			'save_email_list' => false,
			'send_email_list' => false,
			'email_list_cat_options' => false
		);

		foreach ( $ajax_events as $ajax_event => $nopriv ) {
			add_action( 'wp_ajax_geodir_' . $ajax_event, array( __CLASS__, $ajax_event ) );

			if ( $nopriv ) {
				add_action( 'wp_ajax_nopriv_geodir_' . $ajax_event, array( __CLASS__, $ajax_event ) );
			}

			// GeoDir AJAX can be used for frontend ajax requests.
			add_action( 'geodir_ajax_geodir_' . $ajax_event, array( __CLASS__, $ajax_event ) );
		}
	}

	public static function save_email_list() {
		// Security
		if ( ! ( ! empty( $_POST['email_list_security'] ) && wp_verify_nonce( sanitize_text_field( $_POST['email_list_security'] ), 'geodir-save-email-list' ) ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid access!', 'geodir-dynamic-emails' ) ) );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'You are not allowed to perform this action!', 'geodir-dynamic-emails' ) ) );
		}

		if ( has_action( 'geodir_dynamic_emails_do_save_email_list' ) ) {
			try {
				do_action( 'geodir_dynamic_emails_do_save_email_list' );
			} catch ( Exception $e ) {
				wp_send_json_error( array( 'message' => $e->getMessage() ) );
			}
		} else {
			wp_send_json_error( array( 'message' => __( 'Something went wrong, please try again after some time.', 'geodir-dynamic-emails' ) ) );
		}

		wp_die();
	}

	public static function send_email_list() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'You are not allowed to perform this action!', 'geodir-dynamic-emails' ) ) );
		}

		$list_id = ! empty( $_POST['list_id'] ) ? absint( $_POST['list_id'] ) : 0;
		$security = ! empty( $_POST['security'] ) ? sanitize_text_field( $_POST['security'] ) : '';

		// Security
		if ( ! ( $list_id && $security && wp_verify_nonce( $security, 'geodir-de-send-list-' . $list_id ) ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid access!', 'geodir-dynamic-emails' ) ) );
		}

		$sent = GeoDir_Dynamic_Emails_Action::action_instant( $list_id );


		if ( is_wp_error( $sent ) ) {
			wp_send_json_error( array( 'message' => $sent->get_error_message() ) );
		} else if ( $sent === null ) {
			wp_send_json_error( array( 'message' => __( 'Something went wrong, please try again after some time.', 'geodir-dynamic-emails' ) ) );
		}

		if ( is_scalar( $sent ) && $sent > 0 ) {
			$response = array( 'message' => wp_sprintf( __( '%d emails are added in queue to send to the users.', 'geodir-dynamic-emails' ), $sent ) );
		} else {
			$response = array( 'message' => __( 'No matching results found to send emails.', 'geodir-dynamic-emails' ) );
		}

		wp_send_json_success( $response );

		wp_die();
	}

	public static function email_list_cat_options() {
		$options = '';

		if ( current_user_can( 'manage_options' ) ) {
			$post_type = ! empty( $_POST['email_list_post_type'] ) ? sanitize_text_field( $_POST['email_list_post_type'] ) : '';
			$categories = geodir_is_gd_post_type( $post_type ) ? geodir_category_tree_options( $post_type ) : array();

			if ( ! empty( $categories ) ) {
				foreach ( $categories as $term_id => $name ) {
					$options .= '<option value="' . absint( $term_id ) . '">' . esc_html( $name ) . '</option>';
				}
			}
		}

		wp_send_json_success( array( 'options' => $options ) );
	}
}