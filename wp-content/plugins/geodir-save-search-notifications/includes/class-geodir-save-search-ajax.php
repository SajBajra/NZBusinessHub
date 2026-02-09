<?php
/**
 * Save Search AJAX Class
 *
 * @author    AyeCode Ltd
 * @package   GeoDir_Save_Search
 * @version   1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * GeoDir_Save_Search_AJAX class.
 */
class GeoDir_Save_Search_AJAX {
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
			'save_search_popup' => true,
			'save_search_list' => true,
			'save_search_admin_list' => true,
			'save_search_save' => true,
			'save_search_delete' => true
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

	/**
	 * Save search popup.
	 *
	 * @since 1.0
	 *
	 * @return mixed
	 */
	public static function save_search_popup() {
		// Security
		if ( ! ( ! empty( $_POST['security'] ) && wp_verify_nonce( sanitize_text_field( $_POST['security'] ), 'geodir_basic_nonce' ) ) ) {
			wp_send_json_error( array( 'message' => GeoDir_Save_Search_Post::alert( __( 'Invalid access!', 'geodir-save-search' ), 'warning' ) ) );
		}

		$success = false;
		$data = array();

		if ( ! apply_filters( 'geodir_save_search_check_popup', true ) ) {
			$data['body'] = aui()->alert(
				array(
					'type'=> 'warning',
					'content'=> __( 'You are not allowed to save this search.', 'geodir-save-search' ),
					'class' => 'mb-0'
				)
			);
		} else {
			$success = true;
			$data['body'] = GeoDir_Save_Search_Post::get_popup_content( false, ! empty( $_POST['_saved'] ) );
		}

		$data = apply_filters( 'geodir_save_search_popup_response', $data, $success );

		if ( $success ) {
			wp_send_json_success( $data );
		} else {
			wp_send_json_error( $data );
		}

		wp_die();
	}

	/**
	 * Get search search list.
	 *
	 * @since 2.1.4
	 *
	 * @return string HTML response.
	 */
	public static function save_search_admin_list() {
		// Security
		if ( ! ( ! empty( $_REQUEST['security'] ) && wp_verify_nonce( sanitize_text_field( $_REQUEST['security'] ), 'geodir_basic_nonce' ) ) || ! current_user_can( 'manage_options' ) ) {
			echo esc_html( __( 'Invalid access!', 'geodir-save-search' ) );

			wp_die();
		}

		$user_id = ! empty( $_REQUEST['user_id'] ) ? absint( $_REQUEST['user_id'] ) : 0;

		$content = GeoDir_Save_Search_Post::get_admin_popup_content( $user_id );

		echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

		wp_die();
	}

	/**
	 * Handle search get list.
	 *
	 * @since 1.0
	 *
	 * @return string JSON response.
	 */
	public static function save_search_list() {
		if ( ! is_user_logged_in() ) {
			wp_send_json_error( array( 'message' => GeoDir_Save_Search_Post::alert( __( 'Invalid access!', 'geodir-save-search' ), 'warning' ) ) );
		}

		$design_style = geodir_design_style();
		$saved_items = GeoDir_Save_Search_Query::get_subscribers_by_user();

		$template = $design_style ? $design_style . '/save-search-popup/list.php' : 'save-search-popup/list.php';
		$params = apply_filters( 'geodir_save_search_list_template_params', array( 'saved_items' => $saved_items ) );
		$content = geodir_get_template_html( $template, $params );

		wp_send_json_success( array( 'content' => $content, 'count' => count( $saved_items ) ) );

		wp_die();
	}

	/**
	 * Handle search save.
	 *
	 * @since 1.0
	 *
	 * @return string JSON response.
	 */
	public static function save_search_save() {
		if ( ! is_user_logged_in() ) {
			wp_send_json_error( array( 'message' => GeoDir_Save_Search_Post::alert( __( 'You need to login to save search.', 'geodir-save-search' ), 'warning' ) ) );
		}

		if ( ! ( ! empty( $_POST['security'] ) && wp_verify_nonce( sanitize_text_field( $_POST['security'] ), 'geodir_save_search_save' ) ) ) {
			wp_send_json_error( array( 'message' => GeoDir_Save_Search_Post::alert( __( 'Invalid access!', 'geodir-save-search' ), 'warning' ) ) );
		}

		if ( has_action( 'geodir_save_search_handle_save' ) ) {
			try {
				do_action( 'geodir_save_search_handle_save' );
			} catch ( Exception $e ) {
				wp_send_json_error( array( 'message' => GeoDir_Save_Search_Post::alert( $e->getMessage(), 'warning' ) ) );
			}
		} else {
			wp_send_json_error( array( 'message' => GeoDir_Save_Search_Post::alert( __( 'Something went wrong, please try again after some time.', 'geodir-save-search' ), 'warning' ) ) );
		}

		wp_die();
	}

	/**
	 * Handle search delete.
	 *
	 * @since 1.0
	 *
	 * @return string JSON response.
	 */
	public static function save_search_delete() {
		if ( ! is_user_logged_in() ) {
			wp_send_json_error( array( 'message' => GeoDir_Save_Search_Post::alert( __( 'Invalid access!', 'geodir-save-search' ), 'warning' ) ) );
		}

		$subscriber_id = ! empty( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;

		if ( empty( $subscriber_id ) ) {
			wp_send_json_error( array( 'message' => GeoDir_Save_Search_Post::alert( __( 'Invalid subscriber id!', 'geodir-save-search' ), 'warning' ) ) );
		}

		if ( ! ( ! empty( $_POST['security'] ) && wp_verify_nonce( sanitize_text_field( $_POST['security'] ), 'geodir_save_search_delete_' . $subscriber_id ) ) ) {
			wp_send_json_error( array( 'message' => GeoDir_Save_Search_Post::alert( __( 'Invalid access!', 'geodir-save-search' ), 'warning' ) ) );
		}

		$subscriber = GeoDir_Save_Search_Query::get_subscriber( $subscriber_id );

		if ( ! empty( $subscriber ) && ( (int) $subscriber->user_id == (int) get_current_user_id() || current_user_can( 'manage_options' ) ) ) {
			$response = GeoDir_Save_Search_Query::delete_subscriber( $subscriber_id );

			wp_send_json_success( array() );
		}

		wp_send_json_error( array( 'message' => GeoDir_Save_Search_Post::alert( __( 'Something went wrong, please try again later!', 'geodir-save-search' ), 'warning' ) ) );

		wp_die();
	}
}