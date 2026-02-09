<?php
/**
 * Save Search Notifications Admin Class
 *
 * @author    AyeCode Ltd
 * @package   GeoDir_Save_Search
 * @version   1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * GeoDir_Save_Search_Admin class.
 */
class GeoDir_Save_Search_Admin {
	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'init' ) );
		add_action( 'admin_init', array( $this, 'admin_init' ) );
		add_action( 'geodir_clear_version_numbers', array( $this, 'clear_version_number' ), 41 );
		add_filter( 'geodir_adv_search_general_settings', array( $this, 'adv_search_save_search_settings' ), 41, 1 );
		add_filter( 'geodir_get_settings_pages', array( $this, 'load_settings_page' ), 41, 1 );

		add_filter( 'geodir_gd_options_for_translation', 'geodir_save_search_options_for_translation', 41, 1 );
		add_filter( 'geodir_uninstall_options', 'geodir_save_search_uninstall_settings', 41, 1 );

		// Settings
		add_filter( 'geodir_get_sections_emails', array( $this, 'emails_settings_sections' ), 41, 1 );
		add_filter( 'geodir_get_settings_emails', array( $this, 'emails_settings' ), 41, 2 );

		// Stats
		add_filter( 'geodir_dashboard_get_pending_stats', 'geodir_save_search_dashboard_stats', 41, 1 );

		// Users
		add_filter( 'manage_users_columns', array( $this, 'get_users_columns' ), 41, 1 );
		add_filter( 'manage_users_custom_column', array( $this, 'get_users_custom_column' ), 41, 3 );
	}

	/**
	 * Handle init.
	 */
	public function init() {
		
	}

	/**
	 * Handle admin init.
	 */
	public function admin_init() {
		if ( ! empty( $_GET['geodir-save-search-install-redirect'] ) ) {
			$plugin_slug = geodir_clean( $_GET['geodir-save-search-install-redirect'] );

			$url = admin_url( 'plugin-install.php?tab=search&type=term&s=' . $plugin_slug );

			wp_safe_redirect( $url );
			exit;
		}

		// Setup wizard redirect
		if ( get_transient( '_geodir_save_search_activation_redirect' ) ) {
			delete_transient( '_geodir_save_search_activation_redirect' );
		}
	}

	/**
	 * Deletes the version number from the DB so install functions will run again.
	 */
	public function clear_version_number(){
		delete_option( 'geodir_save_search_version' );
	}

	/**
	 * Plugin settings page.
	 */
	public static function load_settings_page( $settings_pages ) {

		return $settings_pages;
	}

	public function emails_settings_sections( $sections ) {
		$sections['save-search-notifications'] = __( 'Saved Search Notifications', 'geodir-save-search' );

		return $sections;
	}

	public function emails_settings( $settings, $current_section ) {
		if ( $current_section == 'save-search-notifications' ) {
			$settings = apply_filters( 'geodir_save_search_notifications_settings', 
				array(
					array(
						'id' => 'save_search_notifications_section',
						'type' => 'title',
						'name' => __( 'Notifications Settings', 'geodir-save-search' ),
						'desc' => ''
					),
					array(
						'id' => 'save_search_interval',
						'type' => 'select',
						'title' => __( 'How Often to Send', 'geodir-save-search' ),
						'desc' => __( 'When you would like send email notifications to usres.', 'geodir-save-search' ),
						'options' => GeoDir_Save_Search_Email::email_intervals(),
						'class' => '',
						'desc_tip' => true
					),
					array(
						'id' => 'save_search_limit',
						'type' => 'number',
						'name' => __( 'Max Limit (Every X Hours)', 'geodir-save-search' ),
						'placeholder' => __( 'Unlimited', 'geodir-save-search' ),
						'desc' => __( 'How many emails to send per every X hours.', 'geodir-save-search' ),
						'element_require' => '[%save_search_interval%] != 0',
						'desc_tip' => true,
						'custom_attributes' => array(
							'step' => "1",
							'min' => "1"
						)
					),
					array(
						'id' => 'save_search_notifications_section',
						'type' => 'sectionend'
					),
					array(
						'id' => 'save_search_new_post_section',
						'type' => 'title',
						'name' => __( 'New Listing', 'geodir-save-search' ),
						'desc' => ''
					),
					array(
						'id' => 'email_user_save_search',
						'type' => 'checkbox',
						'name' => __( 'Enable Email', 'geodir-save-search' ),
						'desc' => __( 'Send email notification to user when new listing with matching search will be published.', 'geodir-save-search' ),
						'default' => '1',
					),
					array(
						'id' => 'email_user_save_search_subject',
						'type' => 'text',
						'name' => __( 'Subject', 'geodir-save-search' ),
						'desc' => __( 'The email subject.', 'geodir-save-search' ),
						'placeholder' => GeoDir_Save_Search_Email::email_user_save_search_subject(),
						'class' => 'active-placeholder',
						'desc_tip' => true
					),
					array(
						'id' => 'email_user_save_search_body',
						'type' => 'textarea',
						'name' => __( 'Body', 'geodir-save-search' ),
						'desc' => __( 'The email body, this can be text or HTML.', 'geodir-save-search' ),
						'placeholder' => GeoDir_Save_Search_Email::email_user_save_search_body(),
						'custom_desc' => __( 'Available template tags:', 'geodir-save-search' ) . ' ' . GeoDir_Save_Search_Email::user_save_search_email_tags(),
						'class' => 'code gd-email-body',
						'desc_tip' => true,
						'advanced' => true
					),
					array(
						'id' => 'save_search_new_post_section',
						'type' => 'sectionend'
					),
					array(
						'id' => 'save_search_edit_post_section',
						'type' => 'title',
						'name' => __( 'Edit Listing', 'geodir-save-search' ),
						'desc' => ''
					),
					array(
						'id' => 'email_user_save_search_edit',
						'type' => 'checkbox',
						'name' => __( 'Enable Email', 'geodir-save-search' ),
						'desc' => __( 'Send email notification to user when a listing with matching search will be updated.', 'geodir-save-search' ),
						'default' => '1',
					),
					array(
						'id' => 'email_user_save_search_edit_subject',
						'type' => 'text',
						'name' => __( 'Subject', 'geodir-save-search' ),
						'desc' => __( 'The email subject.', 'geodir-save-search' ),
						'placeholder' => GeoDir_Save_Search_Email::email_user_save_search_edit_subject(),
						'class' => 'active-placeholder',
						'desc_tip' => true
					),
					array(
						'id' => 'email_user_save_search_edit_body',
						'type' => 'textarea',
						'name' => __( 'Body', 'geodir-save-search' ),
						'desc' => __( 'The email body, this can be text or HTML.', 'geodir-save-search' ),
						'placeholder' => GeoDir_Save_Search_Email::email_user_save_search_edit_body(),
						'custom_desc' => __( 'Available template tags:', 'geodir-save-search' ) . ' ' . GeoDir_Save_Search_Email::user_save_search_email_tags(),
						'class' => 'code gd-email-body',
						'desc_tip' => true,
						'advanced' => true
					),
					array(
						'id' => 'save_search_edit_post_section',
						'type' => 'sectionend'
					)
				)
			);
		}

		return $settings;
	}

	public function adv_search_save_search_settings( $settings ) {
		if ( geodir_design_style() ) {
			$saved_search_settings = apply_filters( 'adv_search_saved_search_notifications_settings', 
				array(
					array(
						'id' => 'adv_search_saved_search_notifications_section',
						'type' => 'title',
						'name' => __( 'Saved Search Notifications', 'geodir-save-search' ),
						'desc' => ''
					),
					array(
						'id' => 'save_search_loop',
						'type' => 'checkbox',
						'name' => __( 'Show In Loop Actions', 'geodir-save-search' ),
						'desc' => __( 'Tick to show Save this Search button in archive pages loop actions.', 'geodir-save-search' ),
						'default' => '1',
					),
					array(
						'id' => 'save_search_loop_shortcode',
						'type' => 'text',
						'name' => __( 'Shortcode', 'geodir-save-search' ),
						'desc' => __( 'Save Search Button shortcode to render in loop actions.', 'geodir-save-search' ),
						'placeholder' => GeoDir_Save_Search_Post::loop_action_shortcode(),
						'class' => 'active-placeholder',
						'desc_tip' => true
					),
					array(
						'id' => 'adv_search_saved_search_notifications_section',
						'type' => 'sectionend'
					)
				)
			);

			$settings = array_merge( $settings, $saved_search_settings );
		}

		return $settings;
	}

	/**
	 * Add the column headers for a users list table.
	 *
	 * @since 2.1.4
	 *
	 * @param string[] $columns The column header labels.
	 * @return string[] Filtered columns.
	 */
	public function get_users_columns( $columns ) {
		$columns['saved_search'] = __( 'Saved Search', 'geodir-save-search' );

		return $columns;
	}

	/**
	 * Display output of saved search column in the Users list table.
	 *
	 * @since 2.1.4
	 *
	 * @param string $output      Saved search column output.
	 * @param string $column_name Column name.
	 * @param int    $user_id     ID of the currently-listed user.
	 * @return string Saved search column output.
	 */
	public function get_users_custom_column( $output, $column_name, $user_id ) {
		global $gd_save_search_thickbox;

		if ( $column_name != 'saved_search' ) {
			return $output;
		}

		$count = GeoDir_Save_Search_Query::count_subscribers_by_user( $user_id );

		if ( $count > 0 ) {
			// Add thickbox.
			if ( empty( $gd_save_search_thickbox ) ) {
				self::add_thickbox_script();
				$gd_save_search_thickbox = true;
			}

			$output .= wp_sprintf(
				'<a href="%s" class="edit geodir-ss-list geodir-ss-list-' . (int) $user_id . '" data-user-id="' . (int) $user_id . '"><span class="geodir-save-search-count" aria-hidden="true">%s</span><span class="screen-reader-text">%s</span></a>',
				"javascript:void(0)",
				$count,
				wp_sprintf(
					/* translators: Hidden accessibility text. %s: Number of saved search lists. */
					_n( '%s saved list by this author', '%s saved lists by this author', $count, 'geodir-save-search' ),
					number_format_i18n( $count )
				)
			);
		} else {
			$output .= 0;
		}

		return $output;
	}

	public static function add_thickbox_script() {
		add_thickbox();

		wp_add_inline_script( 'thickbox', trim( self::get_script() ) );
		add_action( 'admin_footer', array( __CLASS__, 'add_style' ) );
	}

	public static function add_style() {
?>
<style>.column-saved_search{text-align:center!important}</style>
<?php
	}

	public static function get_script() {
		$ajax_url = add_query_arg( 
			array( 
				'action' => 'geodir_save_search_admin_list',
				'security' => wp_create_nonce( 'geodir_basic_nonce' )
			), 
			geodir_ajax_url( true )
		);

		ob_start();

		if ( 0 ) { ?><script><?php } ?>
jQuery(function($){$('.geodir-ss-list').on('click',function(){var uId=$(this).data('user-id');tb_show('<?php echo esc_js( __( 'Saved Search Lists: #', 'geodir-save-search' ) ); ?>'+uId, '<?php echo $ajax_url; ?>&user_id='+uId);});});function geodir_save_search_delete(el,id,nonce,uId){var $el=jQuery(el),$row=jQuery(el).closest("tr");jQuery.ajax({url:'<?php echo esc_url( geodir_ajax_url( true ) ); ?>',type:"POST",data:data={action:"geodir_save_search_delete",id:id,security:nonce},dataType:"json",beforeSend:function(xhr,obj){$row.css({opacity:"0.67"});$el.prop("disabled",true),$el.text('<?php echo esc_js( __( 'Deleting', 'geodir-save-search' ) ); ?>');}}).done(function(res,textStatus,jqXHR){$row.fadeOut();var cnt=parseInt(jQuery(".geodir-ss-list-"+uId+" .geodir-save-search-count").text());if(cnt>0){jQuery(".geodir-ss-list-"+uId+" .geodir-save-search-count").text(cnt-1)}}).always(function(data,textStatus,jqXHR){})}
		<?php if ( 0 ) { ?></script><?php }

		return ob_get_clean();
	}
}
