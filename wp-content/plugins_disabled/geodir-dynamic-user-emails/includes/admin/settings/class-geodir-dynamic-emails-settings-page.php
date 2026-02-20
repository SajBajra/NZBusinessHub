<?php
/**
 * Dynamic User Emails Settings Page class
 *
 * @author    AyeCode Ltd
 * @package   GeoDir_Dynamic_Emails
 * @version   2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'GeoDir_Dynamic_Emails_Settings_Page', false ) ) {
	/**
	 * GeoDir_Dynamic_Emails_Settings_Page class.
	 */
	class GeoDir_Dynamic_Emails_Settings_Page extends GeoDir_Settings_Page {
		/**
		 * Constructor.
		 */
		public function __construct() {
			$this->id = 'dynamic-emails';
			$this->label = __( 'Dynamic Emails', 'geodir-dynamic-emails' );

			add_filter( 'geodir_settings_tabs_array', array( $this, 'add_settings_page' ), 51 );
			add_action( 'geodir_settings_form_method_tab_' . $this->id, array( $this, 'form_method' ) );
			add_action( 'geodir_sections_' . $this->id, array( $this, 'output_sections' ) );
			add_action( 'geodir_settings_' . $this->id, array( $this, 'output' ) );
			add_action( 'geodir_settings_save_' . $this->id, array( $this, 'save' ) );

			add_action( 'geodir_admin_field_email_lists_output', array( $this, 'email_lists_output' ) );
			add_action( 'geodir_admin_field_email_log_output', array( $this, 'email_log_output' ) );
		}

		/**
		 * Get sections.
		 *
		 * @return array
		 */
		public function get_sections() {
			$sections = array(
				'' => __( 'Email Lists', 'geodir-dynamic-emails' ),
				'de-log' => __( 'Email Log', 'geodir-dynamic-emails' ),
				'de-settings' => __( 'Settings', 'geodir-dynamic-emails' )
			);

			return apply_filters( 'geodir_get_sections_' . $this->id, $sections );
		}

		/**
		 * Form method.
		 *
		 * @param  string $method
		 *
		 * @return string
		 */
		public function form_method( $method ) {
			global $current_section;

			if ( 'de-lists' == $current_section || 'de-log' == $current_section || empty( $current_section ) ) {
				return 'get';
			}

			return $method;
		}

		/**
		 * Output the settings.
		 */
		public function output() {
			global $current_section;

			$settings = $this->get_settings( $current_section );

			GeoDir_Admin_Settings::output_fields( $settings );
		}

		/**
		 * Save settings.
		 */
		public function save() {
			global $current_section;

			$settings = $this->get_settings( $current_section );

			GeoDir_Admin_Settings::save_fields( $settings );
		}

		/**
		 * Get settings array.
		 *
		 * @return array
		 */
		public function get_settings( $current_section = '' ) {
			global $aui_bs5;

			$tab = ! empty( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : '';

			if ( 'de-settings' == $current_section ) {
				$settings = apply_filters( 'geodir_dynamic_emails_notifications_settings', 
					array(
						array(
							'id' => 'dynamic_emails_notifications_section',
							'type' => 'title',
							'name' => __( 'Notifications Settings', 'geodir-dynamic-emails' ),
							'desc' => ''
						),
						array(
							'id' => 'email_user_dynamic_emails',
							'type' => 'checkbox',
							'name' => __( 'Enable Email', 'geodir-dynamic-emails' ),
							'desc' => __( 'Untick to disable emails notifications sent within dynamic emails events.', 'geodir-dynamic-emails' ),
							'default' => '1',
						),
						array(
							'id' => 'dynamic_emails_notifications_section',
							'type' => 'sectionend'
						)
					)
				);
			} else if ( 'de-log' == $current_section ) {
				$settings = apply_filters( 'geodir_dynamic_emails_settings_email_log_settings', 
					array(
						array( 
							'title_html' => __( 'Email Log', 'geodir-dynamic-emails' ),
							'type' => 'page-title',
							'desc' => '', 
							'id' => 'geodir_dynamic_emails_log_settings' 
						),
						array(
							'name' => __( 'Email Log', 'geodir-dynamic-emails' ),
							'type' => 'email_log_output',
							'desc' => '',
							'id' => 'geodir_dynamic_emails_log_settings'
						)
					)
				);
			} else if ( 'de-new-list' == $current_section ) {
				$item = self::get_data();

				if ( empty( $item['email_list_id'] ) ) {
					$title = __( 'Add New Email List', 'geodir-dynamic-emails' );
				} else {
					$title =  __( 'Edit Email List:', 'geodir-dynamic-emails' ) . ' #' . $item['email_list_id'];
				}

				$panel_actions = '';
				if ( ! empty( $item['email_list_id'] ) ) {
					$panel_actions .= ' <a href="' . esc_url( admin_url( 'admin.php?page=gd-settings&tab=dynamic-emails&section=de-new-list' ) ) . '" class="float-right float-end ms-2 ml-2 add-new-h2"><i class="fas fa-plus-circle" aria-hidden="true"></i> ' . __( 'Add New List', 'geodir-dynamic-emails' ) . '</a> ';
				}
				$panel_actions .= ' <a href="' . esc_url( admin_url( 'admin.php?page=gd-settings&tab=dynamic-emails' ) ) . '" class="float-right float-end ml-2 ms-2 add-new-h2"><i class="fas fa-arrow-alt-circle-left" aria-hidden="true"></i> ' . __( 'Back to Email Lists', 'geodir-dynamic-emails' ) . '</a> ';

				$cpt_actions = geodir_dynamic_emails_post_type_actions();
				$cpt_element_require = array();
				foreach ( $cpt_actions as $action => $name ) {
					$cpt_element_require[] = '[%email_list_action%] == "' . $action . '"';
				}
				$cpt_element_require = '( ' . implode( ' || ', $cpt_element_require ) . ' )';

				$subject_button = aui()->button(
					array(
						'type'    => 'a',
						'href'    => 'javascript:void(0)',
						'content' => __( 'Default Subject', 'geodir-dynamic-emails' ),
						'class'   => 'btn btn-secondary text-white geodir-de-default',
						'extra_attributes' => array(
							'title' => esc_html( 'Insert default subject for the selected event', 'geodir-dynamic-emails' ),
							'data-field' => 'subject'
						)
					)
				);

				$template_button = aui()->button(
					array(
						'type'    => 'a',
						'href'    => 'javascript:void(0)',
						'content' => __( 'Default Template', 'geodir-dynamic-emails' ),
						'class'   => 'btn btn-secondary text-white geodir-de-default',
						'extra_attributes' => array(
							'title' => esc_html( 'Insert default template for the selected event', 'geodir-dynamic-emails' ),
							'data-field' => 'template'
						)
					)
				);

				$settings = array(
					array(
						'id' => 'geodir_dynamic_emails_new_list_settings',
						'type' => 'title',
						'name' => $title,
						'title_html' => $panel_actions
					),
					array(
						'id' => 'email_list_id',
						'type' => 'hidden',
						'value' => $item['email_list_id'],
					),
					array(
						'id' => 'email_list_security',
						'type' => 'hidden',
						'value' => wp_create_nonce( 'geodir-save-email-list' ),
					),
					array(
						'id' => 'email_list_name',
						'type' => 'text',
						'name' => __( 'Name', 'geodir-dynamic-emails' ),
						'desc' => __( 'Email list name.', 'geodir-dynamic-emails' ),
						'default' => '',
						'value' => $item['name'],
						'required' => true,
						'desc_tip' => true
					),
					array(
						'id' => 'email_list_action',
						'type' => 'select',
						'name' => __( 'Event', 'geodir-dynamic-emails' ),
						'desc' => __( 'Select event to send emails.', 'geodir-dynamic-emails' ),
						'default' => 'instant',
						'value' => $item['action'],
						'options' => GeoDir_Dynamic_Emails_Email::email_actions(),
						'required' => true,
						'desc_tip' => true
					),
					array(
						'id' => 'email_list_post_type',
						'type' => 'select',
						'name' => __( 'Post Type', 'geodir-dynamic-emails' ),
						'desc' => __( 'Select post type.', 'geodir-dynamic-emails' ),
						'default' => '',
						'value' => $item['post_type'],
						'options' => array_merge( array( '' => __( 'All', 'geodir-dynamic-emails' ) ), geodir_get_posttypes( 'options-plural' ) ),
						'placeholder' => __( 'All', 'geodir-dynamic-emails' ),
						'element_require' => $cpt_element_require,
						'desc_tip' => true
					),
					array(
						'id' => 'email_list_category',
						'type' => 'multiselect',
						'name' => __( 'Category', 'geodir-dynamic-emails' ),
						'desc' => __( 'Select the category.', 'geodir-dynamic-emails' ),
						'default' => '',
						'value' => $item['category'],
						'options' => ! empty( $item['post_type'] ) && $item['post_type'] != 'all' ? geodir_category_tree_options( $item['post_type'] ) : array(),
						'placeholder' => __( 'All', 'geodir-dynamic-emails' ),
						'class' => $aui_bs5 ? 'aui-select2' : 'geodir-select',
						'element_require' => '([%email_list_post_type%] && [%email_list_post_type%] != "all" && ' . $cpt_element_require . ')',
						'desc_tip' => true
					),
					array(
						'id' => 'email_list_gd_fields',
						'type' => 'text',
						'name' => __( 'Fields', 'geodir-dynamic-emails' ),
						'desc' => __( 'Filter by fields.', 'geodir-dynamic-emails' ),
						'default' => '',
						'value' => '',
						'class' => 'd-none',
						'element_require' => '([%email_list_post_type%] && [%email_list_post_type%] != "all" && ' . $cpt_element_require . ')',
						'input_group_right' => $this->render_fields_filter( $item ),
						'desc_tip' => true
					),
					array(
						'id' => 'email_list_user_roles',
						'type' => 'multiselect',
						'name' => __( 'User Roles', 'geodir-dynamic-emails' ),
						'desc' => __( 'Select user roles.', 'geodir-dynamic-emails' ),
						'default' => array( 'subscriber' ),
						'value' => $item['user_roles'],
						'options' => geodir_user_roles(),
						'placeholder' => __( 'All', 'geodir-dynamic-emails' ),
						'class' => $aui_bs5 ? 'aui-select2' : 'geodir-select',
						'desc_tip' => true
					),
					array(
						'id' => 'email_list_recipient',
						'type' => 'select',
						'name' => __( 'Email To', 'geodir-dynamic-emails' ),
						'desc' => __( 'Select the recipient email.', 'geodir-dynamic-emails' ),
						'default' => '',
						'value' => ( ! empty( $item['meta']['recipient'] ) ? $item['meta']['recipient'] : '' ),
						'options' => array( '' => __( 'Author Email', 'geodir-dynamic-emails' ), 'listing_email' => __( 'Listing Email (requires email field value is set)', 'geodir-dynamic-emails' ) ),
						'placeholder' => __( 'Author Email', 'geodir-dynamic-emails' ),
						'element_require' => $cpt_element_require,
						'desc_tip' => true
					),
					array(
						'name' => __( 'Email Subject', 'geodir-dynamic-emails' ),
						'desc' => __('The email subject.', 'geodir-dynamic-emails'),
						'id' => 'email_list_subject',
						'type' => 'text',
						'value' => $item['subject'],
						'class' => 'active-placeholder',
						'placeholder' => GeoDir_Dynamic_Emails_Email::email_subject(),
						'desc_tip' => true,
						'input_group_right' => $subject_button
					),
					array(
						'id' => 'email_list_template',
						'type' => 'textarea',
						'name' => __( 'Email Template', 'geodir-dynamic-emails' ),
						'desc' => __( 'The email body, this can be text or HTML.', 'geodir-dynamic-emails' ),
						'value' => $item['template'],
						'placeholder' => GeoDir_Dynamic_Emails_Email::email_body(),
						'custom_desc' => '<div class="d-block w-100 pb-2 mt-n2 text-right text-end">' . $template_button . '</div>' . __( 'Available template tags:', 'geodir-dynamic-emails' ) . ' ' . GeoDir_Dynamic_Emails_Email::email_tags(),
						'class' => 'code gd-email-body',
						'required' => true,
						'desc_tip' => true
					),
					array(
						'id' => 'email_list_status',
						'type' => 'select',
						'name' => __( 'Email List Status', 'geodir-dynamic-emails' ),
						'desc' => __( 'Email list status.', 'geodir-dynamic-emails' ),
						'default' => '',
						'value' => $item['status'],
						'options' => array(
							'pending' => __( 'Pending Review', 'geodir-dynamic-emails' ),
							'publish' => __( 'Published', 'geodir-dynamic-emails' )
						),
						'required' => true,
						'desc_tip' => true
					),
					/*array(
						'id' => 'email_list_instant_send',
						'type' => 'checkbox',
						'name' => __( 'Send After Save', 'geodir-dynamic-emails' ),
						'desc' => __( 'Send the emails immediately after the list is published. If unticked then it will not triger send emails and it will just save the email list data (only for instant email action).', 'geodir-dynamic-emails' ),
						'default' => '',
						'value' => '',
						'desc_tip' => false,
						'element_require' => '[%email_list_action%] == "instant"',
					),*/
					array(
						'id' => 'geodir_dynamic_emails_new_list_settings',
						'type' => 'sectionend'
					)
				);

				$settings = apply_filters( 'geodir_dynamic_emails_settings_new_list_settings', $settings );
			} else {
				$settings = apply_filters( 'geodir_dynamic_emails_settings_email_lists_settings', 
					array(
						array( 
							'title_html' => __( 'Email Lists', 'geodir-dynamic-emails' ) . ' <a href="' . esc_url( admin_url( 'admin.php?page=gd-settings&tab=dynamic-emails&section=de-new-list' ) ) . '" class="add-new-h2">' . __( 'Add New List', 'geodir-dynamic-emails' ) . '</a>',
							'type' => 'page-title',
							'desc' => '', 
							'id' => 'geodir_dynamic_emails_lists_settings' 
						),
						array(
							'name' => __( 'Email Lists', 'geodir-dynamic-emails' ),
							'type' => 'email_lists_output',
							'desc' => '',
							'id' => 'geodir_dynamic_emails_lists_settings'
						)
					)
				);
			}

			return apply_filters( 'geodir_get_settings_' . $this->id, $settings, $current_section );
		}

		public static function email_lists_output( $option ) {
			GeoDir_Dynamic_Emails_Admin_Lists::page_output();
		}

		public static function email_log_output( $option ) {
			GeoDir_Dynamic_Emails_Admin_Log::page_output();
		}

		public static function get_data() {
			$id = ! empty( $_GET['email_list'] ) ? absint( $_GET['email_list'] ) : 0;
			$item = $id > 0 ? GeoDir_Dynamic_Emails_List::get_item( $id ) : array();

			$defaults = array(
				'email_list_id' => '',
				'action' => '',
				'name' => '',
				'post_type' => '',
				'category' => '',
				'user_roles' => '',
				'subject' => '',
				'template' => '',
				'date_added' => '',
				'status' => '',
				'meta' => array()
			);

			if ( empty( $item ) ) {
				return $defaults;
			}

			if ( ! empty( $item->category ) ) {
				$item->category = explode( ',', $item->category );
			}

			if ( ! empty( $item->user_roles ) ) {
				$item->user_roles = explode( ',', $item->user_roles );
			}

			if ( ! empty( $item->meta ) ) {
				$item->meta = GeoDir_Dynamic_Emails_Fields::parse_meta( $item->meta );
			}

			return wp_parse_args( (array) $item, $defaults );
		}
	
		public function render_fields_filter( $data ) {
			global $geodir_render_advanced;

			$output = '';

			if ( ! empty( $geodir_render_advanced ) ) {
				return $output;
			}

			$post_type = ! empty( $data['post_type'] ) && $data['post_type'] != 'all' ? $data['post_type'] : '';
			$fields = ! empty( $data['meta']['fields'] ) ? $data['meta']['fields'] : array();

			$saved_items = '';
			$count = 0;

			if ( ! empty( $fields ) && is_array( $fields ) ) {
				foreach ( $fields as $key => $rule ) {
					if ( ! empty( $rule['field'] ) && ! empty( $rule['condition'] ) ) {
						$count++;
						$rule['post_type'] = $post_type;
						$rule['index'] = $count;

						$saved_items .= GeoDir_Dynamic_Emails_Admin_Settings::gd_field_row( $rule );
					}
				}
			}

			$output = '</div><div class="w-100"><div class="geodir-de-field-rows" data-post-type=' . esc_attr( $post_type ) . '>' . $saved_items . '</div><div class="pt-1 text-right text-end d-flex align-items-start justify-content-between">';
			$output .= aui()->alert(
				array(
					'type'=> 'info',
					'content'=> sprintf( __('Date fields need the format: YYYY-MM-DD. You can also use strings such as "-6 months", "+5 days", "-2 weeks" or "now"','geodir-dynamic-emails'),'<a href="https://www.unixtimestamp.com/" target="_blank">','</a>',time()),
					'class' => 'geodir-new-tmpl-msg mb-3 text-left text-start'
				)
			);
			$output .= '<a href="javascript:void(0);" class="btn btn-secondary text-white geodir-de-field-add"><i class="fas fa-plus-circle"></i> ' . __( 'Add Field', 'geodir-dynamic-emails' ) . '</a></div>';

			return $output;
		}
	}

	return new GeoDir_Dynamic_Emails_Settings_Page();
}
