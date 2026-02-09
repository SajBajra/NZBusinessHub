<?php
/**
 * Save Search Post class
 *
 * @author    AyeCode Ltd
 * @package   GeoDir_Save_Search
 * @version   1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * GeoDir_Save_Search_Post class.
 */
class GeoDir_Save_Search_Post {
	/**
	 * Init.
	 *
	 * @since 1.0
	 */
	public static function init() {
		add_filter( 'geodir_save_search_handle_save', array( __CLASS__, 'save_request' ) );
		add_action( 'post_updated', array( __CLASS__, 'on_post_updated' ), 9, 3 );
		add_action( 'wp_insert_post', array( __CLASS__, 'on_wp_insert_post' ), 9999, 3 );
		add_action( 'geodir_post_published', array( __CLASS__, 'on_geodir_post_published' ), 9999, 3 );

		add_action( 'geodir_save_search_action_new_post', array( __CLASS__, 'action_new_post' ), 10, 2 );
		add_action( 'geodir_save_search_action_edit_post', array( __CLASS__, 'action_edit_post' ), 10, 2 );

		add_action( 'geodir_extra_loop_actions', array( __CLASS__, 'show_in_loop_actions' ), 41, 2 );
	}

	public static function loop_action_shortcode() {
		$shortcode = '[gd_save_search output="" btn_icon="fas fa-bell" btn_alignment="" btn_size="" btn_color="outline-primary" css_class="btn-group btn-group-sm geodir-ss-loop-actions" no_wrap=1]';

		return apply_filters( 'geodir_save_search_loop_action_default_shortcode', $shortcode );
	}

	public static function show_in_loop_actions( $post_type, $args ) {
		if ( ! geodir_design_style() ) {
			return;
		}

		$show = (bool) geodir_get_option( 'save_search_loop' );

		if ( ! apply_filters( 'geodir_save_search_show_in_loop_actions', $show, $post_type, $args ) ) {
			return;
		}

		$shortcode = trim( geodir_get_option( 'save_search_loop_shortcode', "" ) );

		if ( empty( $shortcode ) ) {
			$shortcode = self::loop_action_shortcode();
		}

		$shortcode = apply_filters( 'geodir_save_search_loop_action_shortcode', $shortcode, $post_type, $args );

		$content = do_shortcode( $shortcode );

		$content = apply_filters( 'geodir_save_search_loop_actions_template', $content, $shortcode, $post_type, $args );

		echo $content;
	}

	public static function get_popup_content( $inline = false, $saved = false ) {
		$content = apply_filters( 'geodir_save_search_pre_get_popup', NULL, $inline, $saved );

		if ( $content ) {
			return $content;
		}

		$design_style = geodir_design_style();

		$req_params = ! empty( $_POST['params'] ) ? json_decode( wp_unslash( $_POST['params'] ), true ) : '';
		if ( ! is_array( $req_params ) ) {
			$req_params = array();
		}

		if ( is_user_logged_in() ) {
			$query_params = array();
			foreach ( $req_params as $key => $value ) {
				$query_params[ $key ] = sanitize_text_field( $value );
			}

			if ( ! $saved ) {
				// Form
				$template = $design_style ? $design_style . '/save-search-popup/form.php' : 'save-search-popup/form.php';
				$params = apply_filters( 'geodir_save_search_form_template_params', array( 'query_params' => $query_params ), $saved );
				$input_content = geodir_get_template_html( $template, $params );
			} else {
				$input_content = '';
			}

			// List
			$saved_items = GeoDir_Save_Search_Query::get_subscribers_by_user();

			$template = $design_style ? $design_style . '/save-search-popup/list.php' : 'save-search-popup/list.php';
			$params = apply_filters( 'geodir_save_search_list_template_params', array( 'saved_items' => $saved_items ), $saved );
			$list_content = geodir_get_template_html( $template, $params );

			// Popup
			$template = $design_style ? $design_style . '/save-search-popup/' : 'save-search-popup/';

			if ( $saved ) {
				$template .= 'saved.php';
			} else {
				$template .= 'tabs.php';
			}

			$params = apply_filters( 'geodir_save_search_popup_template_params', array(
				'inline' => $inline,
				'saved' => $saved,
				'input_content' => $input_content,
				'list_content' => $list_content,
				'count' => count( $saved_items )
			) );

			$content = geodir_get_template_html( $template, $params );
		} else {
			$current_url = ! empty( $req_params['url'] ) ? $req_params['url'] : '';
			$redirect_to = add_query_arg( array( 'gd_go' => 'save-search' ), $current_url ) ;

			$content = geodir_notification( array( 'login_msg' => array( 'type' => 'info', 'note' => __( 'Login to save search and you will receive an email notification when new listings matching your search will be published.', 'geodir-save-search' ) ) ) );
			$content .= GeoDir_User::login_link( $redirect_to );
			if ( class_exists( 'UsersWP' ) ) {
				$content .= '<script type="text/javascript">if(typeof uwp_init_auth_modal==="function"){try{var lHref=window.location.href;lHref=lHref.replace("?gd_go=save-search","").replace("&gd_go=save-search","");if(window.history&&window.history.replaceState){lHref+=lHref.indexOf("?")===-1?"?":"&";window.history.replaceState(null,"",lHref+"gd_go=save-search")}uwp_init_auth_modal()}catch(err){}jQuery(document).on("show.bs.modal",".uwp-auth-modal",function(e){jQuery(".geodir-save-search-modal").remove()})};</script>';
			}

			$content = str_replace( "-link uwp-", "-link w-100 mt-3 uwp-", '<div class="geodir-ss-login-wrap">' . $content . '</div>' );

			$content = apply_filters( 'geodir_save_search_login_content', $content, $redirect_to, $saved );
		}

		return $content;
	}

	public static function save_request() {
		$query_vars = ! empty( $_POST['query_vars'] ) ? json_decode( wp_unslash( $_POST['query_vars'] ), true ) : '';
		if ( ! is_array( $query_vars ) ) {
			$query_vars = array();
		}
		$name = ! empty( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
		$uri = ! empty( $_POST['args'] ) ? $_POST['args'] : ( ! empty( $query_vars['url'] ) ? $query_vars['url'] : '' );
		$uri_parts = explode( rtrim( site_url(), '/' ), $uri, 2 );
		$query = ! empty( $uri ) ? wp_parse_url( $uri, PHP_URL_QUERY ) : '';
		$query_params = wp_parse_args( $query );

		if ( ! empty( $query_vars['post_type'] ) ) {
			$post_type = sanitize_text_field( $query_vars['post_type'] );
		} else if ( ! empty( $query_params['stype'] ) ) {
			$post_type = sanitize_text_field( $query_params['stype'] );
		} else {
			$post_type = '';
		}

		if ( ! ( ! empty( $post_type ) && geodir_is_gd_post_type( $post_type ) ) ) {
			throw new Exception( __( 'Invalid post type search.', 'geodir-save-search' ) );
		}

		if ( ! empty( $query_vars ) ) {
			foreach ( $query_vars as $query_var => $query_val ) {
				if ( in_array( $query_var, geodir_save_search_global_params( $post_type ) ) ) {
					$query_params[ $query_var ] = sanitize_text_field( $query_val );
				}
			}
		}

		$query_params = geodir_save_search_parse_args( $query_params );

		$parse_fields = geodir_save_search_parse_fields( $post_type, $query_params );

		if ( empty( $parse_fields ) ) {
			throw new Exception( __( 'Try to search with atleast one filter to save search.', 'geodir-save-search' ) );
		}

		ksort( $parse_fields );

		$user = wp_get_current_user();

		$data = array(
			'user_id' => $user->ID,
			'user_email' => $user->user_email,
			'user_name' => geodir_save_search_user_name( $user ),
			'post_type' => $post_type,
			'search_name' => $name,
			'search_uri' => ! empty( $uri_parts[1] ) ? $uri_parts[1] : $uri,
			'fields' => $parse_fields
		);

		$response = GeoDir_Save_Search_Query::save_subscriber( $data );

		if ( is_wp_error( $response ) ) {
			throw new Exception( $response->get_error_message() );
		}

		wp_send_json_success( array( 'message' => GeoDir_Save_Search_Post::alert( __( 'Search has been saved. You will receive an email notification when new listings matching your search have been published.', 'geodir-save-search' ), 'success' ) ) );
	}

	public static function on_post_updated( $post_ID, $post_after, $post_before ) {
		global $geodir_ss_wp_post_before, $geodir_ss_post_updated;

		if ( ! geodir_design_style() || empty( $post_after->post_status ) || empty( $post_before->post_status ) ) {
			return;
		}

		$unpublished = array( 'draft', 'auto-draft', 'inherit', 'trash', 'pending', 'gd-expired', 'gd-closed' );

		if ( in_array( $post_after->post_status, $unpublished ) || in_array( $post_before->post_status, $unpublished ) ) {
			return;
		}

		if ( ! geodir_save_search_has_action( 'new_post', array( 'post' => $post_after ) ) && ! geodir_save_search_has_action( 'edit_post', array( 'post' => $post_after ) ) ) {
			return;
		}

		if ( wp_is_post_revision( $post_ID ) ) {
			$post_ID = wp_get_post_parent_id( $post_ID );
		}

		$post_type = get_post_type( $post_ID );

		if ( ! geodir_is_gd_post_type( $post_type ) ) {
			return;
		}

		if ( empty( $geodir_ss_wp_post_before ) ) {
			$geodir_ss_wp_post_before = array();
		}

		if ( empty( $geodir_ss_post_updated ) ) {
			$geodir_ss_post_updated = array();
		}

		if ( ! empty( $geodir_ss_post_updated[ $post_ID ] ) ) {
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

		$geodir_ss_wp_post_before[ $post_ID ] = $post_before;
		$geodir_ss_post_updated[ $post_ID ] = $gd_post;
	}

	public static function on_wp_insert_post( $post_ID, $post, $update ) {
		global $geodir_ss_wp_post_before, $geodir_post_before, $_geodir_save_s_post, $geodir_ss_insert_post, $geodir_ss_post_updated, $geodir_ss_save_post;

		if ( ! geodir_design_style() ) {
			return;
		}

		if ( ! geodir_save_search_has_action( 'new_post', array( 'post' => $post ) ) && ! geodir_save_search_has_action( 'edit_post', array( 'post' => $post ) ) ) {
			return;
		}

		if ( empty( $geodir_ss_insert_post ) ) {
			$geodir_ss_insert_post = array();
		}

		if ( ! empty( $geodir_ss_insert_post[ $post_ID ] ) || ! empty( $_geodir_save_s_post[ $post_ID ] ) ) {
			return;
		}

		if ( ! ( ! empty( $post->post_type ) && geodir_is_gd_post_type( $post->post_type ) ) ) {
			return;
		}

		if ( ! in_array( $post->post_status, geodir_get_publish_statuses( (array) $post ) ) ) {
			return;
		}

		if ( isset( $_REQUEST['action'] ) && $_REQUEST['action'] == 'geodir_save_post' && empty( $geodir_ss_save_post[ $post->ID ] ) ) {
			if ( empty( $geodir_ss_save_post ) ) {
				$geodir_ss_save_post = array();
			}

			$geodir_ss_save_post[ $post->ID ] = $post;

			return;
		}

		$gd_post = geodir_get_post_info( $post_ID );

		if ( empty( $gd_post ) ) {
			return;
		}

		$gd_post_before = array();
		if ( ! empty( $geodir_post_before[ $post_ID ] ) ) {
			$gd_post_before = $geodir_post_before[ $post_ID ];
		} else if ( ! empty( $geodir_ss_post_updated[ $post_ID ] ) ) {
			$gd_post_before = $geodir_ss_post_updated[ $post_ID ];
		}

		if ( ! empty( $gd_post_before ) ) {
			if ( ! empty( $geodir_ss_wp_post_before[ $post_ID ] ) ) {
				$gd_post_before->post_content = $geodir_ss_wp_post_before[ $post_ID ]->post_content;
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

		$geodir_ss_insert_post[ $post_ID ] = $gd_post;

		if ( ! empty( $_gd_post ) && ! empty( $_gd_post_before ) ) {
			if ( maybe_serialize( $_gd_post ) != maybe_serialize( $_gd_post_before ) ) {
				do_action( 'geodir_save_search_action_edit_post', $gd_post, $gd_post_before );
			} else {
				do_action( 'geodir_save_search_save_post', $gd_post );
			}
		}

		if ( ! empty( $geodir_ss_save_post[ $post->ID ] ) ) {
			unset( $geodir_ss_save_post[ $post->ID ] );
		}
	}

	public static function on_geodir_post_published( $gd_post, $data = array(), $checked = false ) {
		global $wpdb, $_geodir_save_s_post;

		if ( ! geodir_design_style() ) {
			return;
		}

		if ( ! geodir_save_search_has_action( 'new_post', array( 'gd_post' => $gd_post ) ) ) {
			return;
		}

		if ( ! $checked && ! ( ! empty( $gd_post->post_type ) && geodir_is_gd_post_type( $gd_post->post_type ) ) ) {
			return;
		}

		if ( empty( $_geodir_save_s_post ) ) {
			$_geodir_save_s_post = array();
		}

		if ( ! empty( $_geodir_save_s_post[ $gd_post->ID ] ) ) {
			return;
		}

		if ( ! $checked && ! in_array( $gd_post->post_status, geodir_get_publish_statuses( (array) $gd_post ) ) ) {
			return;
		}

		$_geodir_save_s_post[ $gd_post->ID ] = $gd_post;

		do_action( 'geodir_save_search_action_new_post', $gd_post, $data );
	}

	public static function action_new_post( $gd_post, $args = array() ) {
		self::action_post( 'new_post', array( 'gd_post' => $gd_post ) );
	}

	public static function action_edit_post( $gd_post, $gd_post_before ) {
		self::action_post( 'edit_post', array( 'gd_post' => $gd_post, 'gd_post_before' => $gd_post_before ) );
	}

	public static function action_post( $action, $args ) {
		if ( empty( $args['gd_post'] ) ) {
			return;
		}

		$gd_post = $args['gd_post'];

		if ( ! geodir_save_search_has_action( $action, array( 'gd_post' => $gd_post ) ) ) {
			return;
		}

		$subscribers_fields = GeoDir_Save_Search_Query::get_subscribers_fields( $gd_post->post_type, (int) $gd_post->ID );

		if ( empty( $subscribers_fields ) ) {
			return;
		}

		$subscribers = array();

		foreach ( $subscribers_fields as $data ) {
			$exists = GeoDir_Save_Search_Query::subscriber_email_exists( (int) $data->subscriber_id, (int) $gd_post->ID );

			if ( ! empty( $exists ) ) {
				continue;
			}

			$field = array(
				'search_id' => $data->search_id,
				'field_name' => $data->field_name,
				'field_value' => $data->field_value
			);

			unset( $data->search_id, $data->field_name, $data->field_value );

			if ( empty( $subscribers[ $data->subscriber_id ] ) ) {
				$subscribers[ $data->subscriber_id ] = (array) $data;
			}

			if ( empty( $subscribers[ $data->subscriber_id ]['fields'] ) ) {
				$subscribers[ $data->subscriber_id ]['fields'] = array();
			}

			$subscribers[ $data->subscriber_id ]['fields'][ $field['field_name'] ] = $field['field_value'];
		}

		if ( empty( $subscribers ) ) {
			return;
		}

		$matched_subscribers = array();

		foreach ( $subscribers as $subscriber_id => $subscriber_data ) {
			if ( empty( $subscriber_data['fields'] ) ) {
				continue;
			}

			$matched = self::has_fields_matched( $subscriber_data['fields'], $gd_post, $subscriber_data );

			if ( $matched ) {
				$matched_subscribers[ $subscriber_id ] = $subscriber_data;
			}
		}

		$matched_subscribers = apply_filters( 'geodir_save_search_post_matched_subscribers', $matched_subscribers, $gd_post, $subscribers );

		if ( ! empty( $matched_subscribers ) ) {
			$post_title = get_the_title( (int) $gd_post->ID );
			$post_url = get_permalink( (int) $gd_post->ID );

			foreach ( $matched_subscribers as $subscriber_id => $subscriber ) {
				$data = array(
					'subscriber_id' => $subscriber['subscriber_id'],
					'post_id' => $gd_post->ID,
					'post_title' => $post_title,
					'post_url' => $post_url,
					'email_action' => $action
				);

				$data = apply_filters( 'geodir_save_search_subscriber_email_data', $data, $gd_post, $subscriber );

				$response = GeoDir_Save_Search_Query::save_subscriber_email( $data );
			}

			if ( (int) geodir_get_option( 'save_search_interval' ) == 0 ) {
				geodir_update_option( 'save_search_trigger_send', 1 );
			}
		}
	}

	public static function has_fields_matched( $fields, $gd_post, $subscriber ) {
		$match_fields = array();

		foreach ( $fields as $field_name => $field_value ) {
			$value_matched = null;

			if ( $field_name == 'post_type' ) {
				if ( ! empty( $field_value ) && $gd_post->post_type == $field_value ) {
					$value_matched = true;
				} else {
					$value_matched = 0;
				}
			} else if ( $field_name == 'post_tags' ) {
				$value_matched = 0;

				if ( ! empty( $gd_post->{$field_name} ) ) {
					$post_value = array_map( 'trim', array_filter( explode( ",", geodir_strtolower( $gd_post->{$field_name} ) ) ) );
					$search_value = array_map( 'trim', array_filter( explode( ",", geodir_strtolower( $field_value ) ) ) );

					foreach ( $search_value as $value ) {
						if ( in_array( $value, $post_value ) ) {
							$value_matched = true;
						}
					}
				}
			} else if ( $field_name == 's' ) {
				$value_matched = 0;

				if ( ! empty( $field_value ) ) {
					$post_title = geodir_strtolower( $gd_post->post_title );
					$_search_title = geodir_sanitize_keyword( $field_value, $gd_post->post_type );
					$post_content  = geodir_strtolower( $gd_post->post_content  );
					$search_value = geodir_strtolower( $field_value );

					if ( strpos( $post_title, $search_value ) === 0 || strpos( $post_title, " " . $search_value ) !== false ) {
						$value_matched = true;
					} else if ( strpos( $gd_post->_search_title, $_search_title ) === 0 || strpos( $gd_post->_search_title, " " . $_search_title ) !== false ) {
						$value_matched = true;
					} else if ( strpos( $post_content, $search_value ) === 0 || strpos( $post_content, " " . $search_value ) !== false || strpos( $post_content, ">" . $search_value ) !== false || strpos( $post_content, "\ " . $search_value ) !== false ) {
						$value_matched = true;
					} else if ( ! empty( $gd_post->post_tags ) && in_array( $search_value, explode( ",", geodir_strtolower( $gd_post->post_tags ) ) ) ) {
						$value_matched = true;
					} else {
						$value_matched = 0;
					}
				}
			} else if ( $field_name == 'post_category' ) {
				$value_matched = 0;

				if ( ! empty( $gd_post->post_category ) ) {
					$post_value = array_map( 'absint', array_filter( explode( ",", $gd_post->post_category ) ) );
					$post_value = array_filter( array_unique( $post_value ) );

					$search_value = array_map( 'absint', array_filter( explode( ",", $field_value ) ) );
					$search_value = array_filter( array_unique( $search_value ) );

					foreach ( $search_value as $value ) {
						if ( in_array( $value, $post_value ) ) {
							$value_matched = true;
						}
					}
				}
			} else if ( $field_name == 'neighbourhood' ) {
				if ( ! empty( $gd_post->neighbourhood ) && $field_value == $gd_post->neighbourhood . ',' . $gd_post->city . ',' . $gd_post->region . ',' . $gd_post->country ) {
					$value_matched = true;
				} else {
					$value_matched = 0;
				}
			} else if ( $field_name == 'city' ) {
				if ( ! empty( $gd_post->city ) && $field_value == $gd_post->city . ',' . $gd_post->region . ',' . $gd_post->country ) {
					$value_matched = true;
				} else {
					$value_matched = 0;
				}
			} else if ( $field_name == 'region' ) {
				if ( ! empty( $gd_post->region ) && $field_value == $gd_post->region . ',' . $gd_post->country ) {
					$value_matched = true;
				} else {
					$value_matched = 0;
				}
			} else if ( $field_name == 'country' ) {
				if ( ! empty( $gd_post->country ) && $field_value == $gd_post->country ) {
					$value_matched = true;
				} else {
					$value_matched = 0;
				}
			} else if ( $field_name == 'geo_lon' ) {
				$value_matched = 0;

				if ( ! empty( $gd_post->latitude ) && ! empty( $gd_post->longitude ) && ! empty( $fields['geo_lon'] ) && ! empty( $fields['geo_lat'] ) ) {
					$search_distance = ! empty( $fields['dist'] ) ? geodir_sanitize_float( $fields['dist'] ) : geodir_get_option( 'search_radius', 5 );
					$search_unit = ! empty( $fields['_unit'] ) ? $fields['_unit'] : geodir_get_option( 'search_distance_long' );

					$distance = geodir_sanitize_float( geodir_calculateDistanceFromLatLong( array( 'latitude' => $fields['geo_lat'], 'longitude' => $fields['geo_lon'] ), array( 'latitude' => $gd_post->latitude, 'longitude' => $gd_post->longitude ), $search_unit ) );
					
					if ( $distance <= $search_distance ) {
						$value_matched = true;
					}
				}
			} else if ( $field_name == 'event_dates' ) {
				if ( GeoDir_Post_types::supports( $gd_post->post_type, 'events' ) ) {
					$value_matched = 0;

					$search_value = explode( ' to ', $field_value );

					if ( count( $search_value ) == 1 ) {
						$dates = trim( $search_value[0] );
					} else if ( count( $search_value ) == 2 ) {
						$dates = array( 'from' => trim( $search_value[0] ), 'to' => trim( $search_value[1] ) );
					} else {
						$dates = '';
					}

					$value_matched = ! empty( $dates ) && GeoDir_Save_Search_Query::event_has_schedule( (int) $gd_post->ID, $dates ) ? true : 0;
				}
			} else if ( $field_name == 'etype' ) {
				$value_matched = 0;

				if ( $field_value == 'all' ) {
					$value_matched = true;
				} else {
					if ( GeoDir_Post_types::supports( $gd_post->post_type, 'events' ) ) {
						$schedules = GeoDir_Event_Schedules::get_schedules( (int) $gd_post->ID, $field_value, 1 );

						if ( ! empty( $schedules ) ) {
							$value_matched = true;
						}
					}
				}
			} else {
				if ( in_array( $field_name, GeoDir_Save_Search_Query::get_single_select_fields( $gd_post->post_type ) ) ) {
					if ( ! empty( $gd_post->{$field_name} ) && ( $field_value == $gd_post->{$field_name} || $field_value == ',' . $gd_post->{$field_name} . ',' ) ) {
						$value_matched = true;
					} else {
						$value_matched = 0;
					}
				} else if ( in_array( $field_name, GeoDir_Save_Search_Query::get_multiselect_fields( $gd_post->post_type ) ) ) {
					$value_matched = 0;

					if ( ! empty( $gd_post->{$field_name} ) ) {
						$post_value = array_map( 'trim', array_filter( explode( ",", geodir_strtolower( $gd_post->{$field_name} ) ) ) );
						$search_value = geodir_strtolower( $field_value );

						foreach ( $post_value as $value ) {
							if ( strpos( $search_value, "," . $value . "," ) !== false ) {
								$value_matched = true;
							}
						}
					}
				} else if ( in_array( $field_name, GeoDir_Save_Search_Query::get_single_checkbox_fields( $gd_post->post_type ) ) ) {
					if ( ! empty( $gd_post->{$field_name} ) && $gd_post->{$field_name} == $field_value ) {
						$value_matched = true;
					} else {
						$value_matched = 0;
					}
				} else if ( in_array( $field_name, GeoDir_Save_Search_Query::get_range_fields( $gd_post->post_type ) ) ) {
					$value_matched = 0;

					if ( isset( $gd_post->{$field_name} ) ) {
						$post_value = geodir_sanitize_float( $gd_post->{$field_name} );

						$search_value = explode( "-", $field_value );
						$min_search_value = isset( $search_value[0] ) ? geodir_sanitize_float( $search_value[0] ) : '';

						if ( count( $search_value ) > 1 ) {
							$max_search_value = isset( $search_value[1] ) ? geodir_sanitize_float( $search_value[1] ) : '';

							if ( $min_search_value != '' && $max_search_value != '' ) {
								if ( $max_search_value >= $post_value && $post_value >= $min_search_value ) {
									$value_matched = true;
								}
							} else if ( $min_search_value != '' ) {
								if ( $post_value >= $min_search_value ) {
									$value_matched = true;
								}
							} else if ( $max_search_value != '' ) {
								if ( $max_search_value >= $post_value ) {
									$value_matched = true;
								}
							}
						} else {
							if ( $min_search_value == $post_value ) {
								$value_matched = true;
							}
						}
					}
				} else if ( ( strpos( $field_name, "min") === 0 || strpos( $field_name, "max") === 0 ) && in_array( substr( $field_name, 3 ), GeoDir_Save_Search_Query::get_range_fields( $gd_post->post_type ) ) ) {
					$value_matched = 0;
					$_field_name = substr( $field_name, 3 );

					if ( isset( $gd_post->{$_field_name} ) ) {
						$post_value = geodir_sanitize_float( $gd_post->{$_field_name} );

						$min_search_value = isset( $fields[ 'min' . $_field_name ] ) ? geodir_sanitize_float( $fields[ 'min' . $_field_name ] ) : '';
						$max_search_value = isset( $fields[ 'max' . $_field_name ] ) ? geodir_sanitize_float( $fields[ 'max' . $_field_name ] ) : '';

						if ( $min_search_value != '' && $max_search_value != '' ) {
							if ( $max_search_value >= $post_value && $post_value >= $min_search_value ) {
								$value_matched = true;
							}
						} else if ( $min_search_value != '' ) {
							if ( $post_value >= $min_search_value ) {
								$value_matched = true;
							}
						} else if ( $max_search_value != '' ) {
							if ( $max_search_value >= $post_value ) {
								$value_matched = true;
							}
						}
					}
				} else if ( in_array( $field_name, GeoDir_Save_Search_Query::get_date_fields( $gd_post->post_type ) ) ) {
					$value_matched = 0;

					if ( ! empty( $gd_post->{$field_name} ) && $gd_post->{$field_name} != '0000-00-00' && $gd_post->{$field_name} != '0000-00-00 00:00:00' ) {
						$post_value = date( 'Y-m-d H:i:s', strtotime( $gd_post->{$field_name} ) );
						$search_value = explode( ' to ', $field_value );

						if ( count( $search_value ) == 1 ) {
							if ( date( 'Y-m-d H:i:s', strtotime( trim( $search_value[0] ) ) ) == $post_value ) {
								$value_matched = true;
							}
						} else if ( count( $search_value ) == 2 ) {
							$post_value_time = strtotime( $post_value );

							$search_value_min = trim( $search_value[0] ) != '' ? strtotime( date( 'Y-m-d H:i:s', strtotime( trim( $search_value[0] ) ) ) ) : 0;
							$search_value_max = trim( $search_value[1] ) != '' ? strtotime( date( 'Y-m-d H:i:s', strtotime( trim( $search_value[1] ) ) ) ) : 0;

							if ( $search_value_min && $search_value_max && $search_value_min <= $post_value_time && $search_value_max >= $post_value_time ) {
								$value_matched = true;
							} else if ( $search_value_min && ! $search_value_max && $search_value_min <= $post_value_time ) {
								$value_matched = true;
							} else if ( ! $search_value_min && $search_value_max && $search_value_max >= $post_value_time ) {
								$value_matched = true;
							} 
						}
					}
				} else if ( in_array( $field_name, GeoDir_Save_Search_Query::get_time_fields( $gd_post->post_type ) ) ) {
					$value_matched = 0;

					if ( ! empty( $gd_post->{$field_name} ) && $gd_post->{$field_name} != '00:00' && $gd_post->{$field_name} != '00:00:00' ) {
						$post_value = date( 'H:i', strtotime( $gd_post->{$field_name} ) );
						$search_value = explode( ' to ', $field_value );

						if ( count( $search_value ) == 1 ) {
							if ( date( 'H:i', strtotime( trim( $search_value[0] ) ) ) == $post_value ) {
								$value_matched = true;
							}
						} else if ( count( $search_value ) == 2 ) {
							$post_value_time = strtotime( $post_value );
							$search_value_min = trim( $search_value[0] ) != '' ? strtotime( date( 'H:i', strtotime( trim( $search_value[0] ) ) ) ) : 0;
							$search_value_max = trim( $search_value[1] ) != '' ? strtotime( date( 'H:i', strtotime( trim( $search_value[1] ) ) ) ) : 0;

							if ( $search_value_min && $search_value_max && $search_value_min <= $post_value_time && $search_value_max >= $post_value_time ) {
								$value_matched = true;
							} else if ( $search_value_min && ! $search_value_max && $search_value_min <= $post_value_time ) {
								$value_matched = true;
							} else if ( ! $search_value_min && $search_value_max && $search_value_max >= $post_value_time ) {
								$value_matched = true;
							} 
						}
					}
				}
			}

			$match_fields[ $field_name ] = apply_filters( 'geodir_save_search_match_field_value', $value_matched, $field_name, $field_value, $gd_post, $subscriber );
		}

		$match_fields = apply_filters( 'geodir_save_search_match_fields', $match_fields, $fields, $gd_post, $subscriber );

		$match_found = true;
		$has_match = false;

		foreach ( $match_fields as $field => $match ) {
			if ( $match === 0 || $match === false ) {
				$match_found = false;
			} else if ( $match === 1 || $match === true ) {
				$has_match = true;
			}
		}

		if ( $match_found && ! $has_match ) {
			$match_found = false;
		}
		//geodir_error_log( $match_fields, $gd_post->ID . ' (' . (int) $match_found . ')', __FILE__, __LINE__ );

		return $match_found;
	}

	public static function alert( $message, $type = 'info' ) {
		return aui()->alert(
			array(
				'type'=> $type,
				'content'=> $message,
				'class' => 'mb-3 text-left text-start'
			)
		);
	}

	public static function get_url( $uri ) {
		if ( strpos( $uri, 'https://' ) === 0 || strpos( $uri, 'http://' ) === 0 ) {
			$url = $uri;
		} else {
			$url = rtrim( site_url(), '/' ) . $uri;
		}

		return apply_filters( 'geodir_save_search_get_url', $url, $uri );
	}

	public static function get_widget_script( $widget = 'gd_save_search', $echo = true ) {
		ob_start();
?>
<script type="text/javascript">jQuery(function($){<?php if ( ! empty( $_REQUEST['gd_go'] ) && $_REQUEST['gd_go'] == 'save-search' && is_user_logged_in() ) { ?>try{var lHref=window.location.href;lHref=lHref.replace("?gd_go=save-search","").replace("&gd_go=save-search","");if(window.history&&window.history.replaceState)window.history.replaceState(null,"",lHref)}catch(err){};$('.geodir-save-search-btn').trigger('click');<?php } ?>$(document).on("submit","form.geodir-save-search-form",function(e){e.preventDefault();geodir_save_search(this);return false});});function geodir_save_search_aui_modal(title,params,saved){aui_modal(title,"","",true,"geodir-save-search-modal");jQuery.ajax({url:geodir_params.gd_ajax_url,type:"POST",data:{action:"geodir_save_search_popup",security:geodir_params.basic_nonce,params:params,_saved:saved?1:0},dataType:"json",beforeSend:function(xhr,obj){}}).done(function(res,textStatus,jqXHR){if(res.success&&res.data.body){var sBody=res.data.body}else if(res.data.message){sBody=res.data.message}else{var sBody="<?php echo esc_attr( __( 'Something went wrong, try again later.', 'geodir-save-search' ) ); ?>"}jQuery(".geodir-save-search-modal .modal-body").html(sBody)}).always(function(data,textStatus,jqXHR){})}function geodir_save_search(el){var $form=jQuery(el),$button=jQuery(".geodir-save-search-button",$form),$name=jQuery('[name="gd_save_search_name"]',$form),$status=jQuery(".geodri-save-search-status",$form);if($form.find(".g-recaptcha-response").length&&$form.find(".g-recaptcha-response").val()==""){return false}name=$name.val();if(name){name=name.trim()}if(!name){$name.focus();return false}var data={action:"geodir_save_search_save",name:name,query_vars:jQuery('[name="gd_save_search_vars"]',$form).length?jQuery('[name="gd_save_search_vars"]',$form).val():"",args:document.location.href,security:jQuery('[name="gd_save_search_nonce"]',$form).val()};<?php do_action( 'geodir_save_search_before_submit', $widget ); ?>jQuery.ajax({url:geodir_params.gd_ajax_url,type:"POST",data:data,dataType:"json",beforeSend:function(xhr,obj){$status.html("");$button.parent().find(".fa-spin").remove();$button.prop("disabled",true).append('<i class="fas fa-circle-notch fa-spin ml-2 ms-2"></i>')}}).done(function(res,textStatus,jqXHR){if(typeof res=="object"&&res.data.message){$status.html('<div class="mt-2">'+res.data.message+"</div>");if(res.success){jQuery('[name="gd_save_search_name"]',$form).val("");geodir_save_search_get_list()}setTimeout(function(){$status.html("")},5e3)}else{$status.html("")}}).always(function(data,textStatus,jqXHR){$button.parent().find(".fa-spin").remove();$button.prop("disabled",false)})}function geodir_save_search_delete(el,id,nonce){var $el=jQuery(el),$row=jQuery(el).closest("tr");jQuery.ajax({url:geodir_params.gd_ajax_url,type:"POST",data:data={action:"geodir_save_search_delete",id:id,security:nonce},dataType:"json",beforeSend:function(xhr,obj){$row.css({opacity:"0.5"});$el.prop("disabled",true)}}).done(function(res,textStatus,jqXHR){$row.fadeOut();var cnt=parseInt(jQuery(".geodir-save-search-count").text());if(cnt>0){jQuery(".geodir-save-search-count").text(cnt-1)}}).always(function(data,textStatus,jqXHR){})}function geodir_save_search_get_list(){jQuery.ajax({url:geodir_params.gd_ajax_url,type:"POST",data:data={action:"geodir_save_search_list"},dataType:"json",beforeSend:function(xhr,obj){}}).done(function(res,textStatus,jqXHR){if(res.success&&res.data.content){jQuery("#geodir-save-search-tc2").html(res.data.content);if(typeof res.data.count!="undefined"){jQuery(".geodir-save-search-count").text(parseInt(res.data.count))}}}).always(function(data,textStatus,jqXHR){})}</script>
<?php
		$script = ob_get_clean();

		$script = apply_filters( 'geodir_save_search_widget_script', trim( $script ), $widget );

		if ( $echo ) {
			echo $script;
		} else {
			return $script;
		}
	}

	public static function get_admin_popup_content( $user_id ) {
		$content = '';

		if ( empty( $user_id ) ) {
			return $content;
		}

		$design_style = geodir_design_style();

		// List
		$items = GeoDir_Save_Search_Query::get_subscribers_by_user( $user_id );

		$template = $design_style ? $design_style . '/save-search-popup/admin-list.php' : 'save-search-popup/admin-list.php';
		$params = apply_filters( 'geodir_save_search_admin_list_template_params', array( 'items' => $items ) );

		$content = geodir_get_template_html( $template, $params );

		return $content;
	}
}