<?php
/**
 * Check GeoDir_Lists_Ajax class exists or not.
 */
if ( ! class_exists( 'GeoDir_Lists_AJAX' ) ) {

	/**
	 * Main GD Lists class.
	 *
	 * @class GeoDir_Lists
	 *
	 * @since 2.0.0
	 */
	class GeoDir_Lists_AJAX {

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
				'get_save_dialog' => true,
				'save_to_list' => false,
				'get_new_dialog' => false,
				'save_list' => false,
				'delete_list' => false,
				'edit_list_dialog' => false,

			);

			foreach ( $ajax_events as $ajax_event => $nopriv ) {
				add_action( 'wp_ajax_geodir_lists_' . $ajax_event, array( __CLASS__, $ajax_event ) );

				if ( $nopriv ) {
					add_action( 'wp_ajax_nopriv_geodir_lists_' . $ajax_event, array( __CLASS__, $ajax_event ) );

					// GeoDir AJAX can be used for frontend ajax requests.
					add_action( 'geodir_ajax_lists_' . $ajax_event, array( __CLASS__, $ajax_event ) );
				}
			}
		}

		public static function edit_list_dialog() {
			$list_id = isset($_REQUEST['list_id']) ? absint($_REQUEST['list_id']) : '';
			$result = GeoDir_Lists_Forms::edit_dialog( $list_id );
			if(!$result){
				wp_send_json_error( __("Something went wrong","gd-lists") );
			}else{

				if(is_wp_error( $result ) ){
					wp_send_json_error( $result->get_error_message() );
				}else{
					$result = "<div class='lity-show gd-list-popup gd-edit-list-popup" . ( geodir_design_style() ? ' bsui' : '' ) . "'>".$result."</div>";
					$data = array('html_content'=>$result);
					wp_send_json_success($data);
				}
			}
			wp_die();
		}

		public static function delete_list() {

			check_ajax_referer( 'geodir_basic_nonce', 'security' );

			$list_id = isset($_REQUEST['list_id']) ? absint($_REQUEST['list_id']) : '';
			
			$result = GeoDir_Lists_Data::delete_list($list_id);

			if(!$result){
				wp_send_json_error( __("Something went wrong","gd-lists") );
			}else{

				if(is_wp_error( $result ) ){
					wp_send_json_error( $result->get_error_message() );
				}else{
					$data = array('redirect'=>get_post_type_archive_link( 'gd_list' ));
					wp_send_json_success($data);
				}
			}

			wp_die();
		}

		public static function get_save_dialog() {
			$post_id = isset($_REQUEST['post_id']) ? absint($_REQUEST['post_id']) : '';
			$result = GeoDir_Lists_Forms::save_dialog( $post_id );
			if(!$result){
				wp_send_json_error( __("Something went wrong","gd-lists") );
			}else{

				if(is_wp_error( $result ) ){
					wp_send_json_error( $result->get_error_message() );
				}else{
					$result = "<div class='lity-show gd-list-popup gd-add-to-list-popup" . ( geodir_design_style() ? ' bsui' : '' ) . "'>".$result."</div>";
					$data = array('html_content'=>$result);
					wp_send_json_success($data);
				}
			}
			wp_die();
		}

		public static function get_new_dialog() {

			check_ajax_referer( 'geodir_basic_nonce', 'security' );

			$post_id = isset($_REQUEST['post_id']) ? absint($_REQUEST['post_id']) : '';
			$result = GeoDir_Lists_Forms::new_dialog( $post_id );
			if(!$result){
				wp_send_json_error( __("Something went wrong","gd-lists") );
			}else{

				if(is_wp_error( $result ) ){
					wp_send_json_error( $result->get_error_message() );
				}else{
					$result = "<div class='lity-show gd-list-popup gd-add-new-list-popup" . ( geodir_design_style() ? ' bsui' : '' ) . "'>".$result."</div>";
					$data = array('html_content'=>$result);
					wp_send_json_success($data);
				}
			}
			wp_die();
		}

		public static function save_to_list() {

			check_ajax_referer( 'geodir_basic_nonce', 'security' );
//			echo "<b>saved</b>";exit;
//			print_r( $_REQUEST );exit;
			$list_id = isset($_REQUEST['list_id']) ? absint($_REQUEST['list_id']) : '';
			$post_id = isset($_REQUEST['post_id']) ? absint($_REQUEST['post_id']) : '';
			$action = isset($_REQUEST['list_action']) ? esc_attr($_REQUEST['list_action']) : '';
			$result = false;
			if($list_id && $list_id && $action=='add'){
				$result = GeoDir_Lists_Data::save_to_list($list_id , $post_id);
			}elseif($list_id && $list_id && $action=='remove'){
				$result = GeoDir_Lists_Data::remove_from_list($list_id , $post_id);
			}

			if(!$result){
				wp_send_json_error( __("Something went wrong","gd-lists") );
			}else{

				if(is_wp_error( $result ) ){
					wp_send_json_error( $result->get_error_message() );
				}else{
					$in_user_lists = GeoDir_Lists_Data::in_user_lists($post_id);

					$data = array(
						'in_user_lists' => $in_user_lists,
						'button' => GeoDir_Lists_Forms::get_list_save_button( $list_id, $post_id )
					);

					wp_send_json_success($data);
				}
			}

			wp_die();
		}


		public static function save_list() {

			check_ajax_referer( 'geodir_basic_nonce', 'security' );

			$list_name = isset($_REQUEST['list_name']) ? wp_strip_all_tags($_REQUEST['list_name']) : '';
			$list_description = isset($_REQUEST['list_description']) ? sanitize_textarea_field($_REQUEST['list_description']) : '';
			$post_id = isset($_REQUEST['post_id']) ? absint($_REQUEST['post_id']) : ''; // after action if saving a listing to a new list
			$list_id = isset($_REQUEST['list_id']) ? absint($_REQUEST['list_id']) : '';
			$post_status = isset( $_REQUEST['is_public'] ) && ( $_REQUEST['is_public'] == '1' || $_REQUEST['is_public'] == 'publish' ) ? 'publish' : 'private';
			$post_args = array(
				'post_title'    => $list_name,
				'post_content'  => $list_description,
				'post_status'   => $post_status,
			);

			if($list_id){
				$post_args['ID'] = $list_id;
			}

			$result = GeoDir_Lists_Data::save_list($post_args);

			if(!$result){
				wp_send_json_error( __("Something went wrong","gd-lists") );
			}else{

				if(is_wp_error( $result ) ){
					wp_send_json_error( $result->get_error_message() );
				}else{
					$data = array('list_id'=>$result);
					wp_send_json_success($data);
				}
			}

			wp_die();
		}

	}

	GeoDir_Lists_AJAX::init();
}
