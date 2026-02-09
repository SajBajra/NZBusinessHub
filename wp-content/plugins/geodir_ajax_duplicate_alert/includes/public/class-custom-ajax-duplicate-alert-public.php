<?php
/**
 * The public-specific functionality of the plugin.
 *
 * @since 1.2.0
 * @package    GD_Duplicate_Alert
 * @subpackage GD_Duplicate_Alert/public
 *
 * Class GD_Duplicate_Alert_Public
 */


class GD_Duplicate_Alert_Public {

	/**
	 * Constructor.
	 *
	 * @since 1.2.1
	 *
	 * GD_Duplicate_Alert_Public constructor.
	 */
	public function __construct() {

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles_and_scripts' ) );
		add_action( 'wp_ajax_geodir_duplicate_alert_action', array( $this, 'gd_alert_ajax_action' ) );
		add_action( 'wp_ajax_nopriv_geodir_duplicate_alert_action', array( $this, 'gd_alert_ajax_action' ) );
		add_action( 'geodir_ajax_geodir_duplicate_alert_action', array( $this, 'gd_alert_ajax_action' ) );

		add_action( 'rest_api_init', array( $this, 'rest_api_init' ), 10 );
	}

	public function rest_api_init() {
		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			$post_types = geodir_get_posttypes();

			foreach ( $post_types as $post_type ) {
				$active_fields = $this->get_active_fields( $post_type );

				if ( ! empty( $active_fields ) ) {
					add_filter( "rest_pre_insert_{$post_type}", array( $this, 'rest_validate_post' ), 10, 2 );
				}
			}
		}
	}

	/**
	 * Validate a duplicate post before it is inserted via the REST API.
	 *
	 * @since 2.0.0
	 *
	 * @param stdClass        $prepared_post An object representing a single post prepared
	 *                                       for inserting or updating the database.
	 * @param WP_REST_Request $request       Request object.
	 */
	public function rest_validate_post( $prepared_post, $request ) {
		if ( ! empty( $prepared_post ) && ! is_wp_error( $prepared_post ) && ! empty( $prepared_post->post_type ) ) {
			$fields = $this->get_active_fields( $prepared_post->post_type );

			if ( empty( $fields ) ) {
				return $prepared_post;
			}

			$post_id = ! empty( $prepared_post->ID ) ? (int) $prepared_post->ID : 0;

			$errors = new WP_Error();

			foreach ( $fields as $field_key => $field_name ) {
				$_field_name = str_replace( "address_", "", $field_name );

				if ( ! isset( $prepared_post->{$_field_name} ) ) {
					continue;
				}

				$res = $this->gd_alert_ajax_action( array( 'post_type' => $prepared_post->post_type, 'post_id' => $post_id, 'field_id' => $field_name, 'field_value' => $prepared_post->{$_field_name} ), true, true );

				if ( ! empty( $res['message'] ) ) {
					$errors->add( 'rest_duplicate_' . $_field_name, $res['message'], array( 'status' => 400 ) );
				}
			}

			if ( $errors->has_errors() ) {
				$prepared_post = $errors;
			}
		}

		return $prepared_post;
	}

	/**
	 * Register and enqueue duplicate alert styles and scripts.
	 *
	 * @since 1.2.1
	 */
	public function enqueue_styles_and_scripts() {
		if ( ! geodir_is_page( 'add-listing' ) ) {
			return;
		}

		$post_type = geodir_get_current_posttype();
		$fields = $post_type ? $this->get_active_fields( $post_type ) : array();

		if ( empty( $fields ) ) {
			return;
		}

		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		$design_style = geodir_design_style();

		if ( $design_style ) {
			wp_add_inline_script( 'geodir-add-listing', self::add_listing_script( $fields ) );
		} else {
			wp_register_script( 'geodir-duplicate-alert', GD_DUPLICATE_ALERT_PLUGIN_URL . 'assets/js/custom_ajax_duplicate_alert_public' . $suffix . '.js', array( 'jquery', 'geodir' ), GD_DUPLICATE_ALERT_VERSION, true );

			wp_register_style( 'geodir-duplicate-alert', GD_DUPLICATE_ALERT_PLUGIN_URL . 'assets/css/custom_ajax_duplicate_alert.css', array(), GD_DUPLICATE_ALERT_VERSION, 'all' );

			wp_enqueue_script( 'geodir-duplicate-alert' );
			wp_enqueue_style( 'geodir-duplicate-alert' );
		}
	}

	/**
	 * Script for the add listing page. (AUI only)
	 *
	 * @return string
	 */
	public static function add_listing_script( $fields = array() ) {
		ob_start();
		if (0) { ?><script><?php } ?>
	var duplicate_validation_check = false;
	jQuery(function($) {
		if ($('form#geodirectory-add-post').length) {
			geodir_duplicate_alert_setup($);

			$('body').on("geodir_social_import_data", function(e) {
				geodir_duplicate_alert_setup($);
			});

			var timeout = null;

			$('body').on('keyup', '#geodirectory-add-post input', function() {
				var handle = $(this);
				duplicate_validation_check = false;
				var current_field_id = handle.attr('id')
				if (!(current_field_id && $.inArray(current_field_id, <?php echo json_encode( array_values( $fields ) ); ?>) > -1)) {
					return;
				}
				// Clear the timeout if it has already been set.
				clearTimeout(timeout);
				// Make a new timeout set to go off in 1000ms (1 second)
				timeout = setTimeout(function() {
					var get_current_posttype = $('#geodirectory-add-post input[name=post_type]').val(),
						current_field_value = handle.val(),
						get_post_parent = $('#geodirectory-add-post input[name=post_parent]').val();

					if ('' == get_post_parent) {
						geodir_duplicate_alert_trigger(get_current_posttype, current_field_id, current_field_value, true);
					}
				}, 1000);

			});
		}
	});

	function geodir_duplicate_alert_setup($) {
		// disable submit button before validation.
		var get_posttype = $('#geodirectory-add-post input[name=post_type]').val(), get_post_parent = $('#geodirectory-add-post input[name=post_parent]').val();
		if ('' == get_post_parent) {
			//jQuery('#geodir-add-listing-submit button').attr('disabled', 'disabled');
			<?php foreach ( $fields as $option => $input ) { ?>
				var fVal = $('input#<?php echo esc_attr( $input ); ?>').val();
				if (typeof fVal != 'undefined' && fVal !== '') {
					geodir_duplicate_alert_trigger(get_posttype, '<?php echo esc_attr( $input ); ?>', fVal);
				}
			<?php } ?>
		}
	}

	function geodir_duplicate_alert_trigger(post_type, field_id, field_value, isKeyUp) {
		var gd_alert_ajaxurl = geodir_params.gd_ajax_url;

		var data = {
			'action': 'geodir_duplicate_alert_action',
			'post_type': post_type,
			'field_id': field_id,
			'field_value': field_value,
		};

		jQuery.post(gd_alert_ajaxurl, data, function(response) {
			response = jQuery.parseJSON(response);
			var message = response.message;
			var field_id = response.field_id;
			var exClass = response.skip ? 'alert-info' : 'alert-danger geodir-duplicate-err';
			var gd_duplicate_alert_message_html = "<span class='geodir-duplicate-msg m-0 d-block alert " + exClass + "'>" + message + "</span>";

			if (field_id != null) {
				// after ajax enable submit button again and let the validation work.
				if (isKeyUp && !jQuery('#geodir-add-listing-submit button').closest('form').find('.geodir-duplicate-err').length) {
					jQuery('#geodir-add-listing-submit button').removeAttr('disabled');
				}
				var get_parent_id = jQuery('#' + field_id).parent();
				if ('address_street' == field_id) {
					get_parent_id = jQuery('#' + field_id).parent().parent();
				}

				if (message != null) {
					duplicate_validation_check = true;
					jQuery(get_parent_id).find('span.geodir-duplicate-msg').remove();
					jQuery(get_parent_id).append(gd_duplicate_alert_message_html);
				} else {
					jQuery(get_parent_id).find('span.geodir-duplicate-msg').remove();
					if (isKeyUp && !jQuery(get_parent_id).closest('form').find('.geodir-duplicate-err').length) {
						jQuery('#geodir-add-listing-submit button').removeAttr('disabled');
					}
				}
				if( duplicate_validation_check && !response.skip ){
					jQuery('#geodir-add-listing-submit button').attr('disabled', 'disabled');
				}
			}
		});
	}
		<?php if ( 0 ) { ?></script><?php }

		return ob_get_clean();
	}

	/**
	 * A Duplicate alert action check Listing fields are available or not.
	 *
	 * If filed value are already available in each post type then display message.
	 *
	 * @since 1.2.1
	 *
	 */
	public function gd_alert_ajax_action( $args = array(), $return = false, $pre_skip = false ) {
		global $wpdb;

		$current_output = array();

		if ( ! empty( $args['post_type'] ) && ! empty( $args['field_id'] ) && isset( $args['field_value'] ) ) {
			$post_type = ! empty( $args['post_type'] ) ? sanitize_text_field( $args['post_type'] ) : '';
			$post_id = ! empty( $args['post_id'] ) ? absint( $args['post_id'] ) : 0;
			$field_id = ! empty( $args['field_id'] ) ? sanitize_text_field( $args['field_id'] ) : '';
			$value = ! empty( $args['field_value'] ) ? sanitize_text_field( stripslashes( $args['field_value'] ) ) : '';
		} else {
			$post_type = ! empty( $_POST['post_type'] ) ? sanitize_text_field( $_POST['post_type'] ) : '';
			$post_id = ! empty( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
			$field_id = ! empty( $_POST['field_id'] ) ? sanitize_text_field( $_POST['field_id'] ) : '';
			$value = ! empty( $_POST['field_value'] ) ? sanitize_text_field( stripslashes( $_POST['field_value'] ) ) : '';
		}

		$response['field_id'] = $field_id;

		if ( ! empty( $post_type ) && ! empty( $field_id ) && ! empty( $value ) ) {
			$fields = $this->get_selected_fields_by_posttype( $post_type );
			$skip_fields = $this->get_fields_skip_alert( $post_type );

			if ( ! empty( $fields ) ) {
				$match_key = $field_id;

				if ( 'post_title' === $field_id && in_array( 'post_title', $fields ) ) {
					$match = 'post_title';
				} else if ( 'address_street' === $field_id && in_array( 'post_address', $fields ) ) {
					$match = 'street';
					$match_key = 'post_address';
				} else if ( 'address_zip' === $field_id && in_array( 'post_zip', $fields ) ) {
					$match = 'zip';
					$match_key = 'post_zip';
				} else if ( 'phone' === $field_id && in_array( 'geodir_contact', $fields ) ) {
					$match = 'phone';
					$match_key = 'geodir_contact';
				} else if ( 'email' === $field_id && in_array( 'geodir_email', $fields ) ) {
					$match = 'email';
					$match_key = 'geodir_email';
				} else if ( 'website' === $field_id && in_array( 'geodir_website', $fields ) ) {
					$match = 'website';
					$match_key = 'geodir_website';
				} else {
					$match = '';
					$match_key = '';
				}

				if ( ! empty( $match ) ) {
					if ( $pre_skip && $match_key && in_array( $match_key, $skip_fields ) ) {
						return $response;
					}

					$statuses = geodir_get_post_stati( 'author-archive', array( 'post_type' => $post_type ) );
					$statuses = array_diff( $statuses, array( 'draft', 'auto-draft', 'inherit' ) );

					$exists = $wpdb->get_var( $wpdb->prepare( "SELECT `post_id` FROM `" . geodir_db_cpt_table( $post_type ) . "` WHERE `$match` LIKE %s AND `post_id` != %d AND `post_status` IN( '" . implode( "', '", $statuses ) . "' ) LIMIT 1", $value, $post_id ) );

					if ( ! empty( $exists ) ) {
						$response['message'] = $this->get_separate_msg_by_selected_fields( $post_type, $field_id );

						if ( ! empty( $skip_fields ) && $match_key && in_array( $match_key, $skip_fields ) ) {
							$response['skip'] = true;
						}
					}
				}
			}
		}

		if ( $return ) {
			return $response;
		}

		echo json_encode( $response );

		geodir_die();
	}

	/**
	 * Get duplicate alert message by post type.
	 *
	 * @since 1.2.1
	 *
	 * @param string $posttype Current GD CPT post type.
	 * @return string $validation_message Validation message.
	 */
	public function get_alert_message_by_posttype( $posttype ) {

		$cpt_duplicate_alert = geodir_get_option('duplicate_alert', array());

		$validation_message ='';

		if( isset( $cpt_duplicate_alert[$posttype] ) ) {

			$cpt_alert_arr = $cpt_duplicate_alert[$posttype];

			$validation_message = $cpt_alert_arr['duplicate_alert_validation_message'];
		}

		return $validation_message;

	}

	/**
	 * Get selected fields by post type.
	 *
	 * @since 1.2.1
	 *
	 * @param string $posttype Current GD CPT post type.
	 * @return array $fields;
	 */
	public function get_selected_fields_by_posttype ( $posttype ) {

		$cpt_duplicate_alert = geodir_get_option('duplicate_alert', array());

		$fields = array();

		if ( ! empty( $cpt_duplicate_alert ) && isset( $cpt_duplicate_alert[$posttype] ) ) {
			$cpt_alert_arr = $cpt_duplicate_alert[$posttype];

			if ( ! empty( $cpt_alert_arr['duplicate_alert_fields'] ) ) {
				$fields = (array) $cpt_alert_arr['duplicate_alert_fields'];
			}
		}

		return $fields;

	}

	/**
	 * Get active fields pair by post type.
	 *
	 * @since 2.3.4
	 *
	 * @param string $posttype Current GD post type.
	 * @return array $fields;
	 */
	public function get_active_fields( $post_type ) {
		$active_fields = $this->get_selected_fields_by_posttype( $post_type );

		$fields = array();

		if ( ! empty( $active_fields ) ) {
			$mapping = $this->get_fields_mapping();

			foreach ( $active_fields as $key ) {
				if ( isset( $mapping[ $key ] ) ) {
					$fields[ $key ] = $mapping[ $key ];
				}
			}
		}

		return $fields;
	}

	/**
	 * Get fields mapping.
	 *
	 * @since 2.3.4
	 *
	 * @return array Fields mapping;
	 */
	public function get_fields_mapping() {
		$mapping = array(
			'post_title' => 'post_title',
			'post_address' => 'address_street',
			'post_zip' => 'address_zip',
			'geodir_contact' => 'phone',
			'geodir_email' => 'email',
			'geodir_website' => 'website'
		);

		return $mapping;
	}

	/**
	 * Get skip alert fields by post type.
	 *
	 * @since 2.3
	 *
	 * @param string $posttype Current GD CPT post type.
	 * @return array $fields;
	 */
	public function get_fields_skip_alert( $post_type ) {
		$options = geodir_get_option( 'duplicate_alert', array() );

		$fields = array();
		$cpt_fields = $this->get_selected_fields_by_posttype( $post_type );

		if ( ! empty( $cpt_fields ) && ! empty( $options[ $post_type ] ) ) {
			$cpt_options = $options[ $post_type ];

			if ( ! empty( $cpt_options['duplicate_alert_skip_fields'] ) ) {
				$fields = (array) $cpt_options['duplicate_alert_skip_fields'];
			}
		}

		return $fields;

	}

	/**
	 * Get separate message in separate field.
	 *
	 * IF separate message checkbox checked then return field separate message
	 * otherwise return default message.
	 *
	 * @since 1.2.1
	 *
	 * @param string $post_type current listing post type.
	 * @param string $field selected input fields.
	 * @return string $validation_message.
	 */
	public function get_separate_msg_by_selected_fields ( $post_type, $field ) {
		$messages = geodir_get_option( 'duplicate_alert', array() );

		$message = '';
		$field_id = '';

		if( 'post_title' === $field ) {
			$field_id = 'post_title';
		} elseif ( 'address_street' === $field ) {
			$field_id = 'post_address';
		} elseif ( 'address_zip' === $field ) {
			$field_id = 'post_zip';
		} elseif ( 'phone' === $field ) {
			$field_id = 'geodir_contact';
		} elseif ( 'email' === $field ) {
			$field_id = 'geodir_email';
		} elseif ( 'website' === $field ) {
			$field_id = 'geodir_website';
		}

		if ( isset( $messages[ $post_type ] ) ) {
			$cpt_alert_arr = $messages[ $post_type ];

			$message = ! empty( $cpt_alert_arr[ 'alert_message_' . $field_id ] ) ? $cpt_alert_arr[ 'alert_message_' . $field_id ] : $this->get_alert_message_by_posttype( $post_type ) ;
			if ( ! empty( $message ) ) {
				$message = stripslashes( __( $message, 'geodirectory' ) );
			}
		}

		$field_name = $field == 'post_title' ? 'title' : str_replace( "address_", "", $field );

		if ( ! $message ) {
			$message = GD_Duplicate_Alert_Defaults::duplicate_alert_validation_message( $post_type, $field_name );
		}

	   return apply_filters( 'geodir_duplicate_alert_field_message', $message, $post_type, $field_name );
	}
}