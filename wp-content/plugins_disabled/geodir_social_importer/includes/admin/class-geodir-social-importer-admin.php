<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @since 2.0.0
 *
 * @package    GD_Social_Importer
 * @subpackage GD_Social_Importer/admin
 *
 * Class GD_Social_Importer_Admin
 */
class GD_Social_Importer_Admin extends GeoDir_Settings_Page {

	public $id;

	public $title;

	public $page;

	public $tab;

	public $section;

	/**
	 * Constructor.
	 *
	 * @since 2.0.0
	 *
	 * GD_Social_Importer_Admin constructor.
	 */
	public function __construct() {
		// Social importer plugin ID.
		$this->id    = 'social-importer';

		// Social importer plugin Title.
		$this->title = 'Social Importer'; // Translated on init hook

		// Get current page id.
		$this->page = !empty( $_REQUEST['page'] ) ? sanitize_title( $_REQUEST['page'] ) : '';

		// Get current tab id.
		$this->tab = !empty( $_REQUEST['tab'] ) ? sanitize_title( $_REQUEST['tab'] ) : '';

		// Get current section.
		$this->section = !empty( $_REQUEST['section'] ) ? sanitize_title( $_REQUEST['section'] ) : '';

		if ( $this->tab == 'social-importer' || ( ! empty( $_REQUEST['post'] ) && in_array( get_post_type( absint( $_REQUEST['post'] ) ), (array) geodir_get_option( 'si_gmb_cpt_to_gmb' ) ) ) ) {
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_styles_and_scripts' ) );
		}

		add_action( 'init', array( $this, 'init' ) );
		add_action( 'admin_init', array($this, 'activation_redirect'));
		add_filter( 'geodir_settings_tabs_array', array( $this, 'settings_page' ),99,1 );
		add_action( 'geodir_sections_' . $this->id, array( $this, 'output_sections' ) );
		add_action( 'geodir_settings_' . $this->id, array( $this, 'output' ) );
		add_action( 'geodir_settings_save_' . $this->id, array( $this, 'save' ) );
		add_filter( 'geodir_uninstall_options', array( $this, 'uninstall_data_options' ), 50, 1 );
		add_action( 'geodir_admin_field_fb_connect_app', array( $this, 'fb_connect_app_field' ), 10, 1 );
		add_action( 'admin_init', array( $this, 'fb_integration_oauth' ), 10 );
		add_action( 'post_submitbox_misc_actions', array( $this, 'post_to_facebook_edit_page' ), 200 );
		add_action( 'post_submitbox_misc_actions', array( $this, 'post_to_gmb_edit_page' ), 201 );
		add_action( 'wp_ajax_gdfi_post_to_facebook_ajax', array( $this, 'gdfi_post_to_facebook_ajax' ) );
		add_action( 'wp_ajax_gdfi_post_to_gmb_ajax', array( $this, 'gdfi_post_to_gmb_ajax' ) );
		add_action( 'transition_post_status', array( $this, 'transition_post_status' ), 5000, 3 );
		add_filter( 'geodir_system_status_environment_rows', array( $this , 'xml_status_environment_rows'));

		add_action( 'geodir_admin_field_gmb_connect_account', array( $this, 'gmb_connect_account' ), 10, 1 );
		add_action( 'wp_ajax_geodir_gmb_authorize', array( $this, 'gmb_authorize' ) );
		add_action( 'wp_ajax_geodir_gmb_revoke', array( $this, 'gmb_revoke' ) );
	}

	/**
	 * Register and enqueue duplicate alert styles and scripts.
	 *
	 * @since 2.0.0
	 */
	public function enqueue_styles_and_scripts() {
		global $pagenow;

		$screen = get_current_screen();
		$screen_id = $screen ? $screen->id : '';
		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		wp_register_script( 'geodir-social', GD_SOCIAL_IMPORTER_PLUGIN_URL . 'assets/js/admin' . $suffix . '.js', array( 'jquery', 'geodir-admin-script' ), GD_SOCIAL_IMPORTER_VERSION );

		if ( in_array( $screen_id, geodir_get_screen_ids() ) ) {
			wp_enqueue_script( 'geodir-social' );
		}
	}

	/**
	 * Handle init hooks.
	 *
	 * @since 2.3.8
	 */
	public function init() {
		// Social importer plugin Title.
		$this->title = __( 'Social Importer', 'gd-social-importer' );
	}

	/**
	 * Plugin activation redirection.
	 *
	 * GD social importer settings tab.
	 *
	 * @since 2.0.0
	 *
	 */
	public function activation_redirect() {
		// if not transient set then return.
		if ( !get_transient( 'gd_social_importer_redirect' ) ) {
			return;
		}

		// Delete the redirect transient
		delete_transient( 'gd_social_importer_redirect' );

		// Redirect the Social importer tab.
		wp_safe_redirect( admin_url( 'admin.php?page=gd-settings&tab='.$this->id ) );
		exit;
	}

	/**
	 * Set php xml status in status page environment.
	 *
	 * @since 2.0.0
	 *
	 * @param array $rows Get environment status rows.
	 *
	 * @return array $rows
	 */
	public function xml_status_environment_rows( $rows ) {

		$load_extension = get_loaded_extensions();

		$xml_row = array();

		$xml_row['name'] = __( 'PHP XML', 'gd-social-importer' );
		$xml_row['help'] = geodir_help_tip( __( 'GeoDirectory Social Importer check PHP XML is installed on server or not.','gd-social-importer' ) );
		$xml_row['note'] = '';

		$is_success = false;

		if( is_array( $load_extension ) && in_array('xml',$load_extension )) {
			$is_success = true;
		}
		$xml_row['success'] = $is_success;

		$rows[] = $xml_row;

		return $rows;
	}

	/**
	 * Added new social importer settings page.
	 *
	 * @since 2.0.0
	 *
	 * @param array $pages Settings page.
	 *
	 * @return array $pages
	 */
	public function settings_page( $pages ) {
		if ( ! empty( $this->page ) && 'gd-settings' === $this->page  ) {
			$pages[ $this->id ] = $this->title;
		}

		return $pages;
	}

	/**
	 * Get social importer settings sub sections menu.
	 *
	 * @since 2.0.0
	 *
	 * @return array $sections
	 */
	public function get_sections() {
		$sections = array();
		$sections[''] = __( 'Facebook', 'gd-social-importer' );
		$sections['yelp'] = __( 'Yelp', 'gd-social-importer' );
		$sections['tripadvisor'] = __( 'Tripadvisor', 'gd-social-importer' );
		$sections['gmb'] = __( 'Google My Business', 'gd-social-importer' );

		return apply_filters( 'geodir_get_sections_' . $this->id, $sections );
	}

	/**
	 * Social importer output sections.
	 *
	 * @since 2.0.0
	 */
	public function output_sections() {
		global $current_section;

		$sections = $this->get_sections();

		if ( empty( $sections ) || 0 === sizeof( $sections ) ) {
			return;
		}

		$output = '<ul class="subsubsub m-0 p-0	">';

		$array_keys = array_keys( $sections );

		foreach ( $sections as $id => $label ) {
			$output .=  '<li><a href="' . admin_url( 'admin.php?page=gd-settings&tab=' . $this->id . '&section=' . sanitize_title( $id ) ) . '" class="' . ( $current_section == $id ? 'current' : '' ) . '">' . __( $label ,'gd-social-importer' ) . '</a> ' . ( end( $array_keys ) == $id ? '' : '|' ) . ' </li>';
		}

		$output .=  '</ul><br class="clear" />';

		ob_start();

		$this->output_toggle_advanced();

		$output .= ob_get_clean();

		if ( $output ) {
			echo "<div class='clearfix d-flex align-content-center flex-wrap'>";
			echo $output;
			echo "</div>";
		}
	}

	/**
	 * Social importer output.
	 *
	 * @since 2.0.0
	 */
	public function output() {
		global $current_section;

		$settings = $this->get_settings( $current_section );

		GeoDir_Admin_Settings::output_fields( $settings );
	}

	/**
	 * Save.
	 *
	 * @since 2.0.0
	 */
	public function save() {
		global $current_section;

		$settings = $this->get_settings( $current_section );

		GeoDir_Admin_Settings::save_fields( $settings );

		// Clear transient on account value change.
		if ( ! empty( $_POST['gmb_connect_nonce'] ) && isset( $_POST['si_gmb_account'] ) && isset( $_POST['si_gmb_account_prev'] ) && $_POST['si_gmb_account'] != $_POST['si_gmb_account_prev'] ) { 
			delete_transient( 'geodir_social_gmb_get_locations' );
			geodir_update_option( 'si_gmb_location', '' );
		}
	}

	/**
	 * Get social importer current section settings fields.
	 *
	 * @since 2.0.0
	 *
	 * @param string $current_section Current page section.
	 *
	 * @return array $settings.
	 */
	public function get_settings( $current_section = '' ) {
		$settings = array();

		if ( ! empty( $current_section ) && 'yelp' === $current_section  ) {
			$settings[] = array(
				'name' => __( 'Yelp API V3(fusion) settings.', 'gd-social-importer' ),
				'type' => 'title',
				'id' => 'social_importer_settings'
			);

			$settings[] = array(
				'name'     => __( 'API Key', 'gd-social-importer' ),
				'desc'     => __( 'Enter your Yelp API Key.', 'gd-social-importer' ) .' '. __("Find API details ", "gd-social-importer").'<a href="https://www.yelp.co.uk/developers/v3/manage_app" target="_blank">'. __("here", "gd-social-importer").'</a>',
				'id'       => 'si_yelp_api_key',
				'type'     => 'text',
			);
		} else if ( ! empty( $current_section ) && 'tripadvisor' === $current_section ) {
			$settings[] = array(
				'name' => __( 'Trip advisor Settings', 'gd-social-importer' ),
				'type' => 'title',
				'desc' => '',
				'id' => 'social_importer_settings'
			);

			$settings[] = array(
				'name' => __( 'Enable Direct URL Parse', 'gd-social-importer' ),
				'desc' => __( 'This method bypasses the tripadvisor api and instead directly parses the url return data, a lot like when you enter a url in Tripadvisor it grabs the title and image. ( Content may be copyrighted and/or subject to other T&Cs, this option is used at your own risk )', 'gd-social-importer' ),
				'id'   => 'si_enable_ta_scrapper',
				'type' => 'checkbox',
				'default'  => '0',
			);
		} else if ( $current_section == 'gmb' ) {
			$settings[] = array(
				'name' => __( 'GMB App Connect', 'gd-social-importer' ),
				'type' => 'title',
				'desc' => '',
				'id' => 'social_importer_app_settings'
			);

			$settings[] = array(
				'name'     => __( 'Connect', 'gd-social-importer' ),
				'desc'     => __( 'Connect with Google My Business account to get auth code.', 'gd-social-importer' ),
				'id'       => 'gmb_connect_account',
				'type'     => 'gmb_connect_account',
				'desc_tip' => true,
			);

			if ( geodir_get_option( 'si_gmb_access_token' ) ) {
				$accounts = geodir_social_gmb_get_accounts();

				$_accounts = array();
				if ( ! empty( $accounts ) && is_array( $accounts ) ) {
					// Set default account.
					if ( ! geodir_get_option( 'si_gmb_account' ) ) {
						geodir_update_option( 'si_gmb_account', sanitize_text_field( $accounts[0]['name'] ) );
					}

					foreach ( $accounts as $account ) {
						$_accounts[ $account['name'] ] = $account['accountName'];
					}
				}

				$locations = geodir_social_gmb_parse_locations();

				// Set default location.
				if ( ! empty( $locations ) && ! geodir_get_option( 'si_gmb_location' ) ) {
					$_locations = array_keys( $locations );
					geodir_update_option( 'si_gmb_location', sanitize_text_field( $_locations[0] ) );
				}

				$settings[] = array(
					'type' => 'select',
					'id' => 'si_gmb_account',
					'name' => __( 'Business Account', 'gd-social-importer' ),
					'desc' => __( 'Select the business account.', 'gd-social-importer' ),
					'default' => '',
					'class' => 'geodir-select',
					'options' => $_accounts,
					'placeholder' => __( 'Select Account', 'gd-social-importer' ),
					'desc_tip' => true,
					'advanced' => false,
					'custom_attributes' => array(
						'data-allow-clear' => true
					)
				);

				$settings[] = array(
					'type' => 'select',
					'id' => 'si_gmb_location',
					'name' => __( 'Business Location', 'gd-social-importer' ),
					'desc' => __( 'Select the business location.', 'gd-social-importer' ),
					'default' => '',
					'class' => 'geodir-select',
					'options' => geodir_social_gmb_parse_locations(),
					'placeholder' => __( 'Select Location', 'gd-social-importer' ),
					'desc_tip' => true,
					'advanced' => false,
					'custom_attributes' => array(
						'data-allow-clear' => true
					)
				);
			} else {
				$settings[] = array(
					'type'     => 'text',
					'id'       => 'si_gmb_auth_code',
					'name'     => __( 'Auth Code', 'gd-social-importer' ),
					'desc'     => '<span class="btn btn-sm btn-primary" data-nonce="' . esc_attr( wp_create_nonce( 'gmb_authorize' ) ) . '" id="gmb_authorize">' . __( 'Authorize', 'gd-social-importer' ) . '</span><br>' . __( 'You must save changes after entering auth code here.', 'gd-social-importer' ),
					'placeholder' => __( 'ENTER AUTH CODE HERE', 'gd-social-importer' ),
					'desc_tip' => false,
				);
			}

			$settings[] = array(
				'type' => 'sectionend',
				'id' => 'social_importer_app_settings'
			);

			$settings[] = array(
				'name' => __( 'Import GMB Location', 'gd-social-importer' ),
				'type' => 'title',
				'id' => 'social_importer_import_settings'
			);

			$settings[] = array(
				'type' => 'multiselect',
				'id' => 'si_gmb_cpt_to_import',
				'name' => __( 'CPTs to Import From GMB', 'gd-social-importer' ),
				'desc' => __( 'Select post types allowed frontend users to import their Google My Business location.', 'gd-social-importer' ),
				'default' => '',
				'class' => 'geodir-select',
				'options' => geodir_post_type_options( true, true ),
				'placeholder' => __( 'Select Post Types', 'gd-social-importer' ),
				'desc_tip' => true
			);
			
			$settings[] = array(
				'type' => 'sectionend',
				'id' => 'social_importer_import_settings'
			);

			$settings[] = array(
				'name' => __( 'Post to GMB Location', 'gd-social-importer' ),
				'type' => 'title',
				'id' => 'social_importer_settings'
			);

			$social_importer = new Social_Importer_General();
			$cpt_options = (array) $social_importer->post_cpt_options( 'gmb' );

			$settings[] = array(
				'type' => 'multiselect',
				'id' => 'si_gmb_cpt_to_gmb',
				'name' => __( 'CPTs to Post To GMB', 'gd-social-importer' ),
				'desc' => __( 'Select post types to allow for post to GMB location when a item published. It post to Google My Business location selected in GMB App Connect section', 'gd-social-importer' ),
				'default' => '',
				'class' => 'geodir-select',
				'options' => $cpt_options,
				'placeholder' => __( 'Select Post Types', 'gd-social-importer' ),
				'desc_tip' => true
			);

			$settings[] = array(
				'type' => 'checkbox',
				'id'   => 'si_gmb_auto_post_to_gmb',
				'name' => __( 'Auto Post To GMB', 'gd-social-importer' ),
				'desc' => __( 'Allow auto post to Google My Business location on item published. If disabled you still able to post by using "Post to GMB" button from backend edit page.', 'gd-social-importer' ),
				'default'  => '0',
				'advanced' => false
			);

			$settings[] = array(
				'name' => __( 'Post Text', 'gd-social-importer' ),
				'custom_desc' => __( 'The text content that should be post to GMB location. Google search results shows 80 characters. Allowed upto 1500 max characters.', 'gd-social-importer' ) . '<br>' . __( 'Available template tags:', 'gd-social-importer' ) . ' ' . $social_importer->post_to_gmb_tags(),
				'id' => 'si_gmb_post_text',
				'type' => 'textarea',
				'placeholder' => $social_importer->post_to_gmb_text(),
				'desc_tip' => false,
				'advanced' => true
			);
		} else {
			$settings[] = array(
				'name' => __('Facebook App Details', 'gd-social-importer'),
				'type' => 'sectionstart',
				'id' => 'social_importer_settings'
			);

			$settings[] = array(
				'name'     => sprintf(
					__( '%sscraping-bot.io%s username', 'gd-social-importer' ),
					'<a href="https://scraping-bot.io/" target="_blank">',
					'</a>'
				),
				'desc'     => __( 'Enter your scraping-bot.io username', 'gd-social-importer' ),
				'id'       => 'si_fb_scraping_bot_io_username',
				'type'     => 'text',
				'desc_tip' => true,
			);

			$settings[] = array(
				'name'     => __( 'scraping-bot.io API Key', 'gd-social-importer' ),
				'desc'     => __( 'Enter your scraping-bot.io API Key', 'gd-social-importer' ),
				'id'       => 'si_fb_scraping_bot_io_api_key',
				'type'     => 'password',
				'desc_tip' => true,
			);
		}

		$settings = apply_filters( 'geodir_social_importer_settings', $settings, $current_section );

		$settings[] = array(
			'type' => 'sectionend',
			'id' => 'social_importer_settings'
		);

		// Post To Facebook settings
		if ( empty( $current_section ) ) {
			$settings[] = array(
				'name' => __( 'Post to Facebook settings', 'gd-social-importer' ),
				'type' => 'sectionstart',
				'id' => 'social_importer_post_to_fb_settings'
			);

			$settings[] = array(
				'type' => 'checkbox',
				'id'   => 'si_fb_disable_post_to_fb',
				'name' => __( 'Disable Post To Facebook?', 'gd-social-importer' ),
				'desc' => __( 'Tick to disable post to Facebook feature for all post types.', 'gd-social-importer' ),
				'default'  => '0',
				'advanced' => false
			);

			$social_importer = new Social_Importer_General();
			$cpt_options = (array) $social_importer->post_cpt_options( 'fb' );

			$settings[] = array(
				'type' => 'multiselect',
				'id' => 'si_fb_cpt_to_fb',
				'name' => __( 'Enable post type for Post To Facebook', 'gd-social-importer' ),
				'desc' => __( 'Select post type to enable post to Facebook when listing published. Leave blank to enable post to Facebook for all allowed post types.', 'gd-social-importer' ),
				'default' => '',
				'class' => 'geodir-select',
				'options' => $cpt_options,
				'placeholder' => __( 'Select post types', 'gd-social-importer' ),
				'desc_tip' => true
			);

			$settings[] = array(
				'type' => 'checkbox',
				'id'   => 'si_fb_disable_auto_post',
				'name' => __( 'Disable auto Post To Facebook?', 'gd-social-importer' ),
				'desc' => __( 'Tick to disable auto post to Facebook when listing published. If disabled you still able to post by using "Post to Facebook" button from edit page.', 'gd-social-importer' ),
				'default'  => '1',
				'advanced' => true
			);

			$settings[] = array(
				'name' => __( 'Enable Direct URL Parse', 'gd-social-importer' ),
				'desc' => __( 'This method bypasses the facebook api and instead directly parses the url return data, a lot like when you enter a url in facebook it grabs the title and image. ( Content may be copyrighted and/or subject to other T&Cs, this option is used at your own risk )', 'gd-social-importer' ),
				'id'   => 'si_enable_fb_scrapper',
				'type' => 'checkbox',
				'default'  => '0',
			);

			$settings[] = array(
				'name'     => __( 'Facebook App ID', 'gd-social-importer' ),
				'desc'     => __( 'Enter your facebook app ID', 'gd-social-importer' ),
				'id'       => 'si_fb_app_id',
				'type'     => 'text',
				'desc_tip' => true,
			);

			$settings[] = array(
				'name'     => __( 'Facebook App Secret', 'gd-social-importer' ),
				'desc'     => __( 'Enter your facebook app secret', 'gd-social-importer' ),
				'id'       => 'si_fb_app_secret',
				'type'     => 'password',
				'desc_tip' => true,
			);

			$settings[] = array(
				'name'     => __( 'OAuth redirect URI', 'gd-social-importer' ),
				'desc'     => __( 'This setting needs to be added to your app settings Products>Facebook Login>OAuth redirect URI', 'gd-social-importer' ),
				'id'       => 'gdfi_oauth_uri',
				'type'     => 'text',
				'desc_tip' => true,
				'default' => admin_url( 'admin.php?page=gd-settings&tab=social-importer' ),
				'custom_attributes' => array(
					'disabled' => 'disabled',
				)
			);

			$settings[] = array(
				'name' => __( 'Facebook Connect App', 'gd-social-importer' ),
				'desc' => '',
				'id' => 'fb_connect_app',
				'type' => 'fb_connect_app',
				'css' => 'min-width:300px;',
				'std' => ''
			);

			$fb_app_id = geodir_get_option( 'si_fb_app_id', '' );
			$fb_app_secret = geodir_get_option( 'si_fb_app_secret', '' );

			if ( ! empty( $fb_app_id ) && ! empty( $fb_app_secret ) ) {
				$settings[] = array(
					'type' => 'select',
					'id' => 'si_fb_app_page_post',
					'name' => __( 'Post to FB page', 'gd-social-importer' ),
					'desc' => __( 'Select a Facebook page to post the new listings.', 'gd-social-importer' ),
					'default' => '',
					'class' => 'geodir-select',
					'css' => 'width:300px;',
					'options' => $this->get_fb_pages(),
					'placeholder' => __( 'Select page - DISABLED', 'gd-social-importer' ),
					'desc_tip' => true
				);
			} else {
				$settings[] = array(
					'type' => 'hidden',
					'id' => 'si_fb_app_page_post',
					'default' => ''
				);
			}

			$settings[] = array(
				'type' => 'sectionend',
				'id' => 'social_importer_post_to_fb_settings'
			);
		}

		return apply_filters( 'geodir_get_settings_' . $this->id, $settings );
	}

	/**
	 * Get Facebook pages.
	 *
	 * @since 2.0.0
	 *
	 * @param string $at Default selected pages.
	 *
	 * @return array $default
	 */
	public function get_fb_pages( $at = '' ) {
		$access_token = geodir_get_option( 'si_fb_access_token', '' );

		$default = array();
		$default[''] = __( 'Select page - DISABLED', 'gd-social-importer' );

		if ( empty( $access_token ) ) {
			return $default;
		}

		$url = 'https://graph.facebook.com/me/accounts?limit=1000&access_token=' . $access_token;

		$result = wp_remote_get( $url, array('timeout' => 15 ) );

		if ( ! empty( $result ) && 200 === $result['response']['code'] ) {
			$result_arr = !empty( $result['body'] ) ? json_decode( $result['body'] ) : '';

			if ( !empty( $result_arr->data ) && $result_arr->data != '' ) {
				foreach ($result_arr->data as $fpage) {
					$default[ $fpage->id ] = !empty( $fpage->name ) ? $fpage->name : $fpage->id;
				}
			}
		}

		return $default;
	}

	/**
	 * Connect facebook app using facebook app id.
	 *
	 * @since 2.0.0
	 *
	 * @param array $field {
	 *
	 *      An array of field arguments.
	 *
	 *       @type string $name Field name.
	 *       @type string $desc Field Description.
	 *       @type string $id Field unique id.
	 *       @type string $type Field type.
	 *       @type string $css Field extra css.
	 *       @type string $std Field std.
	 *       @type string $title Field title.
	 *       @type string $class Field add extra class.
	 *       @type string $default Field default values.
	 *       @type string $desc_tip Field Description displaying in tooltip.
	 *       @type string $placeholder Field placeholder value.
	 * }
	 *
	 * @return bool
	 */
	public function fb_connect_app_field( $field ) {
		global $aui_bs5;

		$gdfi_app_id = geodir_get_option('si_fb_app_id', '');
		$app_secret = geodir_get_option('si_fb_app_secret', '');

		if( empty( $gdfi_app_id ) ) {
			return false;
		}

		$app_response = $this->fb_app_id_validate( $gdfi_app_id, $app_secret);

		if( !$app_response ){

			geodir_update_option('si_fb_access_token', '');
			geodir_update_option('si_fb_access_token_expire', '');

		}

		$token_btn_title = __( 'Connect Your App','gd-social-importer' );

		$access_token = geodir_get_option( 'si_fb_access_token' );
		$expires_on_text = '';

		if ( ! empty( $access_token ) ) {
			$token_btn_title = __( 'Refresh Access Token','gd-social-importer' );

			$access_token_expire = geodir_get_option( 'si_fb_access_token_expire' );

			if ( ! empty( $access_token_expire ) && is_numeric( $access_token_expire ) && $access_token_expire > 0 ) {
				$expires_on_text = wp_sprintf( __( 'Facebook Access Token Expires On: %1$s at %2$s', 'gd-social-importer' ), date_i18n( geodir_date_format(), $access_token_expire ), date_i18n( geodir_time_format(), $access_token_expire ) ) . ' ' . date_default_timezone_get();
			} else if ( '0' == $access_token_expire ) {
				$expires_on_text = __( 'Facebook Access Token Expires On: Never', 'gd-social-importer' );
			}
		}
		?>
		<div class="<?php echo ( $aui_bs5 ? 'mb-3' : 'form-group' ); ?> row" data-argument="si_fb_connect_app">
			<label for="si_fb_connect_app" class="<?php echo ( $aui_bs5 ? 'fw-bold' : 'font-weight-bold' ); ?> col-sm-3 col-form-label"><?php echo ! empty( $field['name'] ) ? $field['name'] : ''; ?></label>
			<div class="col-sm-9">
				<span class="btn btn-sm btn-primary" id="si_fb_connect_app" onclick="gdfi_auth_popup();" ><?php echo $token_btn_title; ?></span>
				<?php if ( $access_token && $expires_on_text ) { ?><small class="form-text d-block text-muted description"><?php echo $expires_on_text; ?></small><?php } ?>
				<script type="text/javascript">
					win = '';
					function gdfi_auth_popup() {
						win = window.open("https://www.facebook.com/dialog/oauth?client_id=<?php echo $gdfi_app_id;?>&redirect_uri=<?php echo urlencode(admin_url()."admin.php?page=gd-settings&tab=social-importer");?>&scope=email,pages_show_list,pages_manage_posts", "gdfi_auth", "scrollbars=no,menubar=no,height=450,width=600,resizable=yes,toolbar=no,status=no");

						var pollTimer = window.setInterval(function () {
							if (win.closed !== false) { // !== is required for compatibility with Opera
								window.clearInterval(pollTimer);
								location.reload();// reload the page to show the app as connected
							}
						}, 200);
						return false;
					}
				</script>
			</div>
		</div>
		<?php
	}

	/**
	 * Get access token and update in GD settings.
	 *
	 * If API response success and response code is 200 then get access token and update in GD.
	 * Else display error message.
	 *
	 * @since 2.0.0
	 */
	public function fb_integration_oauth() {

		if( !empty( $_REQUEST['tab'] ) && 'social-importer' == $_REQUEST['tab'] && !empty( $_REQUEST['code'] ) ) {

			$error_msg = __('Something went wrong', 'gd-social-importer');

			$app_id = geodir_get_option('si_fb_app_id', '');

			$app_secret = geodir_get_option('si_fb_app_secret', '');

			$app_response = $this->fb_app_id_validate( $app_id, $app_secret);

			if( $app_response ) {

				$code = !empty( $_REQUEST['code'] ) ? $_REQUEST['code'] : '';

				$url = "https://graph.facebook.com/oauth/access_token?client_id=" . $app_id . "&redirect_uri=" . urlencode(admin_url( 'admin.php?page=gd-settings&tab=social-importer' ) ) . "&client_secret=" . $app_secret . "&code=$code";

				$response = wp_remote_get( $url, array('timeout' => 15) );

				$rjson = json_decode($response['body']);

				if ( !empty( $response['response']['code'] ) && 200 == $response['response']['code'] ) {

					if( !empty( $rjson->access_token ) && isset( $rjson->access_token ) ) {

						geodir_update_option('si_fb_access_token', $rjson->access_token);

						$expiry = '0';

						if ( !empty( $rjson->expires_in ) && isset( $rjson->expires_in ) ) {

							$expiry = time() + $rjson->expires_in;

						}

						geodir_update_option('si_fb_access_token_expire', $expiry);

						?><script type="text/javascript">window.close();</script><?php

					} else {

						if( isset( $rjson->error ) && isset( $rjson->error->message ) ) {

							$error_msg = $error_msg.': '.$rjson->error->message;

						}

						echo $error_msg;

						geodir_update_option('si_fb_access_token', '');
						geodir_update_option('si_fb_access_token_expire', '');

						exit();

					}

				} else {

					if( isset( $rjson->error ) && isset( $rjson->error->message ) ) {

						$error_msg = $error_msg.': '.$rjson->error->message;

					}

					echo $error_msg;

					geodir_update_option('si_fb_access_token', '');
					geodir_update_option('si_fb_access_token_expire', '');

					exit();

				}

			}

			exit();
		}

	}

	/**
	 * Validate facebook APP id.
	 *
	 * @since 2.0.0
	 *
	 * @param string $app_id Facebook App id.
	 * @param string $app_secret Facebook App secret.
	 *
	 * @return bool $app_response
	 */
	public function fb_app_id_validate( $app_id, $app_secret) {

		$app_response = false;

		if( ( empty( $app_id ) && $app_id == '' ) || ( empty( $app_secret ) && $app_secret =='' ) ) {

			return $app_response;

		}

		$url = "https://graph.facebook.com/$app_id?fields=roles&access_token=$app_id|$app_secret";

		$response = wp_remote_get( $url, array('timeout' => 15) );

		if( !empty( $response['response']['code'] ) && 200 === $response['response']['code'] ) {
			$app_response = true;
		}

		return $app_response;

	}

	/**
	 * Add Post to facebook button in selected post type edit page.
	 *
	 * @since 2.0.0
	 */
	public function post_to_facebook_edit_page() {
		global $post;

		if ( !empty( $post->ID ) && !empty( $post->post_type ) && $this->post_to_fb_cpt_check( $post->post_type ) ) {
			if ( get_post_meta( $post->ID, 'gdfi_posted_facebook', true ) ) {
				$button = __('Repost to Facebook', 'gd-social-importer');
				$style = 'color:blue;';
			} else {
				$button = __('Post to Facebook', 'gd-social-importer');
				$style = 'color:#888;';
			}
			?>
			<div class="misc-pub-section misc-pub-social-importer-post-to-facebook">
				<div id="post-to-facebook-display"><span class="dashicons dashicons-facebook" style="<?php echo $style; ?>"></span> <span onclick="gdfi_post_fb_ajax(this);" class="button button-primary button-small"><?php echo $button ?></span> <span class="gdfi-posting-wait" style="display:none;"> <i class="fas fa-spinner fa-spin" aria-hidden="true"></i></span></div>
			</div>
			<script type="text/javascript">

				gdfi_sending_post = false;

				function gdfi_post_fb_ajax(el) {
					var $wrap = jQuery('.misc-pub-social-importer-post-to-facebook');
					if (gdfi_sending_post) {
						alert("<?php  _e('Currently posting, please wait!', 'gd-social-importer'); ?>");
						return;
					}
					var data = {
						'action': 'gdfi_post_to_facebook_ajax',
						'post_id': <?php echo $post->ID;?>,
						'security': '<?php echo wp_create_nonce( "gdfi-ajax-nonce" ); ?>'
					};

					jQuery.ajax({
						url: ajaxurl,
						type: 'POST',
						dataType: 'html',
						data: data,
						beforeSend: function () {
							gdfi_sending_post = true;
							jQuery('.gdfi-posting-wait', $wrap).show();
							jQuery(el).addClass('disabled');
						},
						success: function (data, textStatus, xhr) {
							jQuery('.gdfi-posting-wait', $wrap).hide();
							jQuery(el).removeClass('disabled');
							if (data == '2') {
								alert("<?php  _e('Please publish this post first or you might break the internet!', 'gd-social-importer'); ?>");
							} else if (data == '1') {
								jQuery(".dashicons-facebook", $wrap).css("color", "blue");
								jQuery('.geodir-post-button', $wrap).html('<?php _e('Repost to Facebook', 'gd-social-importer');?>');
								alert("<?php  _e('Post posted to facebook!', 'gd-social-importer'); ?>");
							} else {
								alert("<?php  _e('Something went wrong while posting to facebook!', 'gd-social-importer'); ?>")
							}
							gdfi_sending_post = false;
						},
						error: function (xhr, textStatus, errorThrown) {
							jQuery('.gdfi-posting-wait', $wrap).hide();
							jQuery(el).removeClass('disabled');
							alert(textStatus);
							gdfi_sending_post = false;
						}
					});
				}
			</script>
			<?php
		}
	}

	/**
	 * Add Post to GMB button in selected post type edit page.
	 *
	 * @since 2.1.1.0
	 */
	public function post_to_gmb_edit_page() {
		global $post;

		if ( ! empty( $post->ID ) && ! empty( $post->post_type ) && $this->post_to_gmb_cpt_check( $post->post_type ) ) {
			$title = '';
			if ( $data = get_post_meta( $post->ID, 'gdfi_posted_gmb', true ) ) {
				$button = __( 'Repost to GMB', 'gd-social-importer' );
				$style = 'color:blue;';
				if ( ! empty( $data ) && ( $_data = explode( "|", $data ) ) ) {
					if ( ! empty( $_data[0] ) ) {
						$title = wp_sprintf( __( 'Last posted on %s', 'gd-social-importer'), date_i18n( geodir_date_time_format(), strtotime( $_data[0] ) ) );
					}
				}
			} else {
				$button = __( 'Post to GMB', 'gd-social-importer' );
				$style = 'color:#888;';
			}
			?>
			<div class="misc-pub-section misc-pub-post-to-gmb">
				<div id="post-to-gmb-display" title="<?php echo esc_attr( $title ); ?>"><span class="dashicons dashicons-google" style="<?php echo $style; ?>"></span> <span class="button button-primary button-small geodir-gmb-post" data-id="<?php echo absint( $post->ID );?>" data-nonce="<?php echo esc_attr( wp_create_nonce( "gdfi-ajax-nonce" ) );?>" data-posted="<?php esc_attr_e('Repost to GMB', 'gd-social-importer');?>"><?php echo $button ?></span> <span class="gdfi-posting-wait" style="display:none;"> <i class="fas fa-spinner fa-spin" aria-hidden="true"></i></span></div>
			</div>
			<?php
		}
	}

	/**
	 * Check custom post type is allowed or not in facebook.
	 *
	 * @since 2.0.0
	 *
	 * @param string $post_type Post type.
	 *
	 * @return bool $return
	 */
	public function post_to_fb_cpt_check( $post_type = '' ) {
		$return = false;

		if ( empty( $post_type ) ) {
			return $return;
		}

		$social_importer = new Social_Importer_General();
		$allowed_cpt = $social_importer->post_cpt_options( 'fb' );

		if ( ! isset( $allowed_cpt[ $post_type ] ) || (int) geodir_get_option( 'si_fb_disable_post_to_fb' ) ) {
			return $return;
		}

		$post_types = geodir_get_option( 'si_fb_cpt_to_fb' );

		if ( empty( $post_types ) || ( ! empty( $post_types ) && in_array( $post_type, $post_types ) ) ) {
			$return = true;
		}

		return apply_filters( 'geodir_social_cpt_post_to_facebook', (bool)$return );
	}

	/**
	 * Check custom post type is allowed or not in GMB.
	 *
	 * @since 2.1.1.0
	 *
	 * @param string $post_type Post type.
	 *
	 * @return bool $return
	 */
	public function post_to_gmb_cpt_check( $post_type = '' ) {
		$return = false;

		if ( empty( $post_type ) ) {
			return $return;
		}

		$social_importer = new Social_Importer_General();
		$allowed_cpt = $social_importer->post_cpt_options( 'gmb' );

		if ( ! isset( $allowed_cpt[ $post_type ] ) ) {
			return $return;
		}

		$post_types = geodir_get_option( 'si_gmb_cpt_to_gmb' );
		if ( ! empty( $post_types ) && in_array( $post_type, $post_types ) ) {
			$return = true;
		}

		return apply_filters( 'geodir_social_cpt_post_to_gmb', $return );
	}

	/**
	 * Post to facebook ajax callback function.
	 *
	 * @since 2.0.0
	 */
	public function gdfi_post_to_facebook_ajax(){

		check_ajax_referer('gdfi-ajax-nonce', 'security');

		$post_id = 0;

		if ( !empty( $_POST['post_id'] ) && isset( $_POST['post_id'] ) ) {

			$post_id = $_POST['post_id'];

		} else {

			echo '0';
			wp_die();

		}

		if (get_post_status($post_id) != 'publish') {

			echo '2';
			wp_die();

		}

		$post_type = get_post_type( $post_id );

		if (!$this->post_to_fb_cpt_check($post_type)) {

			echo '0';
			wp_die();

		}

		$permalink = get_permalink( $post_id );

		$title = html_entity_decode( get_the_title( $post_id ), ENT_COMPAT, 'UTF-8' );

		if ( $this->gdfi_fb_post( $title, $permalink ) ) {

			update_post_meta($post_id, 'gdfi_posted_facebook', '1');
			echo '1';

		} else {

			echo '0';

		}

		wp_die();
	}

	/**
	 * Post to GMB ajax callback function.
	 *
	 * @since 2.1.1.0
	 */
	public function gdfi_post_to_gmb_ajax() {
		check_ajax_referer('gdfi-ajax-nonce', 'security');

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( -1 );
		}

		$post_id = 0;

		if ( ! empty( $_POST['post_id'] ) ) {
			$post_id = absint( $_POST['post_id'] );
		} else {
			wp_send_json_error( array( 'message' => __( 'Invalid post found.', 'gd-social-importer' ) ) );
		}

		$post_type = get_post_type( $post_id );

		if ( ! $this->post_to_gmb_cpt_check( $post_type ) ) {
			wp_send_json_error( array( 'message' => __( 'Post to GMB is allowed for the post type.', 'gd-social-importer' ) ) );
		}

		if ( ! in_array( get_post_status( $post_id ), geodir_get_publish_statuses( array( 'post_type' => $post_type ) ) ) ) {
			wp_send_json_error( array( 'message' => __( 'Publish post first to allow for Post to GMB.', 'gd-social-importer' ) ) );
		}

		$response = geodir_social_gmb_create_post( $post_id );

		if ( is_wp_error( $response ) ) {
			wp_send_json_error( array( 'message' => $response->get_error_message() ) );
		} else {
			if ( is_array( $response ) && ! empty( $response['name'] ) ) {
				update_post_meta( $post_id, 'gdfi_posted_gmb', date_i18n( 'Y-m-d H:i:s' ) . '|' . $response['name'] . '|' . $response['searchUrl'] );
				wp_send_json_success( array( 'message' => __( 'Item posted to GMB location.', 'gd-social-importer' ) ) );
			} else {
				wp_send_json_error( array( 'message' => __( 'Something went wrong while posting to GMB.', 'gd-social-importer' ) ) );
			}
		}
	}

	/**
	 * Post page in facebook using title and page url.
	 *
	 * @since 2.0.0
	 *
	 * @param string $msg Get page title.
	 *
	 * @param string $link Get Page url.
	 *
	 * @return bool $response
	 */
	public function gdfi_fb_post( $msg = '', $link = '' ) {

		$app_page_post = geodir_get_option('si_fb_app_page_post', '');

		$page_at = $this->fb_page_accesstoken();

		if ( empty( $app_page_post ) && '' === $app_page_post ) {

			return false;

		}

		if ( empty( $page_at ) && '' === $page_at ) {

			return false;

		}

		$url = 'https://graph.facebook.com/' . $app_page_post . '/feed';

		$args = array();

		$args['body']['access_token'] = $page_at;
		$args['body']['message'] = $msg;
		$args['body']['link'] = $link;
		$args['timeout'] = 30;

		$result = wp_remote_post($url, $args);

		$response = false;

		if( !empty( $result['response']['code'] ) && 200 == $result['response']['code'] ) {

			$response = true;

		}

		return (bool)$response;
	}

	/**
	 * Get selected facebook page access token.
	 *
	 * @since 2.0.0
	 *
	 * @return bool|string $return
	 */
	public function fb_page_accesstoken(){

		$access_token = geodir_get_option('si_fb_access_token', '');

		$app_page_post = geodir_get_option('si_fb_app_page_post', '');

		$return = '';

		if( ( empty( $access_token ) && '' === $access_token ) || ( empty( $app_page_post ) && '' === $app_page_post ) ) {
			return false;
		}

		$url = 'https://graph.facebook.com/me/accounts?limit=1000&access_token=' . $access_token;

		$result = wp_remote_get($url, array('timeout' => 15));

		if( !empty( $result['response']['code'] ) && 200 === $result['response']['code'] ) {

			$result_arr = !empty( $result['body'] ) ? json_decode($result['body']) : '';

			if (!empty( $result_arr ) ) {

				foreach ($result_arr->data as $fpage) {

					if ($app_page_post == $fpage->id) {

						$return = !empty(  $fpage->access_token ) ?  $fpage->access_token :'';

					}
				}
			}

		}

		return $return;

	}

	/**
	 * Post to facebook when post publish and not Disable auto post to facebook option in social importer..
	 *
	 * @since 2.1.1.0
	 *
	 * @param string $new_status Current Post new status.
	 * @param string $old_status Current Post old status.
	 * @param object $post Current Post object.
	 */
	public function transition_post_status( $new_status, $old_status, $post ) {
		if ( isset( $_REQUEST['action'] ) && "geodir_import_export" === $_REQUEST['action'] ) {
			return;
		}

		if ( $old_status != $new_status && ! in_array( $old_status, geodir_get_publish_statuses( array( 'post_type' => $post->post_type ) ) ) && in_array( $new_status, geodir_get_publish_statuses( array( 'post_type' => $post->post_type ) ) ) ) {
			// Post to Facebook
			$this->post_to_facebook( $post );

			// Post to GMB
			$this->auto_post_to_gmb( $post );
		}
	}

	/**
	 * Post to facebook when post publish and not Disable auto post to facebook option in social importer..
	 *
	 * @since 2.0.0
	 *
	 * @param object $post Current Post object.
	 */
	public function post_to_facebook( $post ) {
		if ( get_post_meta( $post->ID, 'gdfi_posted_facebook', true ) ) {
			return;
		}

		if ( $this->post_to_fb_cpt_check( $post->post_type ) && ! geodir_get_option( 'si_fb_disable_auto_post' ) ) {
			$permalink = get_permalink( $post->ID );

			$title = html_entity_decode( get_the_title( $post->ID ), ENT_COMPAT, 'UTF-8' );

			if ( $this->gdfi_fb_post( $title, $permalink ) ) {
				update_post_meta( $post->ID, 'gdfi_posted_facebook', '1' );
			}

			$to_post = get_option( 'gdfi_post_to_facebook' );
			if ( ! is_array( $to_post ) ) {
				$to_post = array();
			}

			$to_post[ $post->ID ] = array( 'ID' => $post->ID, 'title' => $post->post_title );

			update_option( 'gdfi_post_to_facebook', $to_post, false );
		}
	}

	/**
	 * Auto post to GMB when post published.
	 *
	 * @since 2.1.1.0
	 *
	 * @param object $post Current Post object.
	 */
	public function auto_post_to_gmb( $post ) {
		if ( ! geodir_get_option( 'si_gmb_auto_post_to_gmb' ) ) {
			return;
		}

		if ( get_post_meta( $post->ID, 'gdfi_posted_gmb', true ) ) {
			return;
		}

		$to_post = get_option( 'gdfi_post_to_gmb' );

		if ( ! is_array( $to_post ) ) {
			$to_post = array();
		}

		if ( empty( $to_post[ $post->ID ] ) && $this->post_to_gmb_cpt_check( $post->post_type ) ) {
			$response = geodir_social_gmb_create_post( $post->ID );

			if ( ! is_wp_error( $response ) && ! empty( $response['name'] ) ) {
				update_post_meta( $post->ID, 'gdfi_posted_gmb', date_i18n( 'Y-m-d H:i:s' ) . '|' . $response['name'] . '|' . $response['searchUrl'] );

				$to_post[ $post->ID ] = array( 'ID' => $post->ID, 'title' => $post->post_title );

				update_option( 'gdfi_post_to_gmb', $to_post, false );
			}
		}
	}

	/**
	 * Connect Google My Business account.
	 *
	 * @since 2.1.1.0
	 *
	 * @param array $field An array of field arguments.
	 *
	 * @return bool
	 */
	public function gmb_connect_account( $field ) {
		global $aui_bs5;

		if ( geodir_get_option( 'si_gmb_access_token' ) ) {
			?>
			<div class="<?php echo ( $aui_bs5 ? 'mb-3' : 'form-group' ); ?> row">
				<label for="si_gmb_auth_code" class="<?php echo ( $aui_bs5 ? 'fw-bold' : 'font-weight-bold' ); ?> col-sm-3 col-form-label"><?php echo ! empty( $field['name'] ) ? $field['name'] : ''; ?></label>
				<div class="col-sm-9">
					<span class="btn btn-sm btn-primary" data-nonce="<?php echo esc_attr( wp_create_nonce( 'gmb_revoke' ) ); ?>" id="gmb_revoke"><?php echo __( 'Revoke', 'gd-social-importer' ); ?></span>
					<strong class="text-warning"><i class="fas fa-check-circle"></i> <?php echo __( 'Authorized', 'gd-social-importer' ); ?>
					</strong>

					<small class="form-text d-block text-muted"><span class="description">
					<?php if ( $expires_in = absint( geodir_get_option( 'si_gmb_expires_in' ) ) ) { ?>
							<p class="description" style="display:none"><?php echo wp_sprintf( __( 'Expires On: %1$s at %2$s', 'gd-social-importer' ), date_i18n( geodir_date_format(), $expires_in ), date_i18n( geodir_time_format(), $expires_in ) ) . ' ' . date_default_timezone_get(); ?></p>
						<?php } ?>
						<?php if ( $last_authorized_on = geodir_get_option( 'si_gmb_access_token_date' ) ) { ?>
							<p class="description" style="display:none"><?php echo wp_sprintf( __( 'Last Authorized On: %1$s at %2$s', 'gd-social-importer' ), date_i18n( geodir_date_format(), strtotime( $last_authorized_on ) ), date_i18n( geodir_time_format(), strtotime( $last_authorized_on ) ) ) . ' ' . date_default_timezone_get(); ?></p>
						<?php } ?>
						<?php if ( $last_refreshed_on = geodir_get_option( 'si_gmb_refresh_token_date' ) ) { ?>
							<p class="description" style="display:none"><?php echo wp_sprintf( __( 'Last Updated On: %1$s at %2$s', 'gd-social-importer' ), date_i18n( geodir_date_format(), strtotime( $last_refreshed_on ) ), date_i18n( geodir_time_format(), strtotime( $last_refreshed_on ) ) ) . ' ' . date_default_timezone_get(); ?></p>
						<?php } ?>
					</small>
					<?php wp_nonce_field( 'gmb_connect_nonce', 'gmb_connect_nonce', false ); ?>
					<input type="hidden" name="si_gmb_account_prev" value="<?php echo esc_attr( geodir_get_option( 'si_gmb_account', '' ) ); ?>">
				</div>
			</div>
			<?php
		} else {
			?>
			<div class="<?php echo ( $aui_bs5 ? 'mb-3' : 'form-group' ); ?> row">
				<label for="si_gmb_auth_code" class="<?php echo ( $aui_bs5 ? 'fw-bold' : 'font-weight-bold' ); ?> col-sm-3 col-form-label"><?php echo ! empty( $field['name'] ) ? $field['name'] : ''; ?></label>
				<div class="col-sm-9">
					<span class="btn btn-sm btn-primary" onclick="geodir_gmb_connect_account();" ><?php echo _e( 'Connect to Google My Business', 'gd-social-importer' ); ?></span>
					<p class="description"><?php echo ! empty( $field['desc'] ) ? $field['desc'] : ''; ?></p>
					<?php wp_nonce_field( 'gmb_connect_nonce', 'gmb_connect_nonce', false ); ?>
					<script type="text/javascript">
						function geodir_gmb_connect_account() {
							var gmbWin = window.open('<?php echo geodir_social_gmb_auth_url(); ?>', 'gmb_auth', 'scrollbars=no,menubar=no,height=600,width=600,resizable=yes,toolbar=no,status=no');
							return false;
						}
					</script>
				</div>
			</div>
			<?php
		}
	}

	/**
	 * Authorize GMB connect access.
	 *
	 * @since 2.1.1.0
	 *
	 * @mixed
	 */
	public function gmb_authorize() {
		check_ajax_referer( 'gmb_authorize', 'security' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( -1 );
		}

		$auth_code = ! empty( $_POST['gmb_code'] ) ? sanitize_text_field( $_POST['gmb_code'] ) : '';

		$success = false;

		if ( empty( $auth_code ) ) {
			$error = __( 'Invalid request.', 'gd-social-importer' );
		} else {
			$response = wp_remote_post( geodir_social_gmb_token_url( $auth_code ), array( 'timeout' => 15 ) );

			$error =  __( 'Something went wrong.','gd-social-importer' );

			if ( ! is_wp_error( $response ) ) {
				if ( 200 == wp_remote_retrieve_response_code( $response ) ) {
					$_response = json_decode( wp_remote_retrieve_body( $response ), true );

					if ( ! empty( $_response['access_token'] ) ) {
						$success = true;
						geodir_update_option( 'si_gmb_access_token', sanitize_text_field( $_response['access_token'] ) );
						geodir_update_option( 'si_gmb_access_token_date', date( 'Y-m-d H:i:s' ) );
						geodir_update_option( 'si_gmb_refresh_token', sanitize_text_field( $_response['refresh_token'] ) );
						geodir_update_option( 'si_gmb_refresh_token_date', date( 'Y-m-d H:i:s' ) );
						geodir_update_option( 'si_gmb_expires_in', time() + absint( $_response['expires_in'] ) );
						geodir_update_option( 'si_gmb_auth_code', '' );
						geodir_update_option( 'si_gmb_account', '' );
						geodir_update_option( 'si_gmb_location', '' );
						delete_transient( 'geodir_social_gmb_access_token' );
						delete_transient( 'geodir_social_gmb_get_accounts' );
						delete_transient( 'geodir_social_gmb_get_locations' );

						set_transient( 'geodir_social_gmb_access_token', sanitize_text_field( $_response['access_token'] ), absint( $_response['expires_in'] ) - ( MINUTE_IN_SECONDS * 5 ) );
					} else {
						$error =  __( 'Access token not found.','gd-social-importer' );
					}
				} elseif ( ! empty( $response['response']['code'] ) ) {
					$_response = json_decode( wp_remote_retrieve_body( $response ), true );

					if ( isset( $_response['error'] ) ) {
						$error =  $_response['error'] . ": " . $_response['error_description'] . '(' . $response['response']['code'] . ')';
					}
				}
			} else {
				$error =  $response->get_error_message();
			}
		}

		if ( $success ) {
			wp_send_json_success( array( 'reload' => true ) );
		} else {
			wp_send_json_error( array( 'message' => $error ) );
		}

		wp_die();
	}

	/**
	 * Revoke GMB connect access.
	 *
	 * @since 2.1.1.0
	 *
	 * @mixed
	 */
	public function gmb_revoke() {
		check_ajax_referer( 'gmb_revoke', 'security' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( -1 );
		}

		$token = geodir_get_option( 'si_gmb_access_token' );

		if ( empty( $token ) ) {
			$token = geodir_get_option( 'si_gmb_refresh_token' );
		}

		if ( $token ) {
			wp_remote_post( geodir_social_gmb_revoke_url( $token ), array( 'timeout' => 15 ) );
		}

		geodir_update_option( 'si_gmb_access_token', '' );
		geodir_update_option( 'si_gmb_access_token_date', '' );
		geodir_update_option( 'si_gmb_refresh_token', '' );
		geodir_update_option( 'si_gmb_refresh_token_date', '' );
		geodir_update_option( 'si_gmb_expires_in', '' );
		geodir_update_option( 'si_gmb_auth_code', '' );
		geodir_update_option( 'si_gmb_account', '' );
		geodir_update_option( 'si_gmb_location', '' );
		delete_transient( 'geodir_social_gmb_access_token' );
		delete_transient( 'geodir_social_gmb_get_accounts' );
		delete_transient( 'geodir_social_gmb_get_locations' );

		wp_send_json_success( array( 'reload' => true ) );

		wp_die();
	}

	/**
	 * Add the plugin to uninstall settings.
	 *
	 * @since 2.1.1.0
	 *
	 * @return array $settings the settings array.
	 * @return array The modified settings.
	 */
	public function uninstall_data_options( $settings ) {
		array_pop( $settings );

		$settings[] = array(
			'id' => 'uninstall_geodir_social_importer',
			'type'  => 'checkbox',
			'name' => __( 'Social Importer', 'gd-social-importer' ),
			'desc' => __( 'Check this box if you would like to completely remove all of its data when Social Importer is deleted.', 'gd-social-importer' )
		);
		$settings[] = array( 
			'type' => 'sectionend',
			'id' => 'uninstall_options'
		);

		return $settings;
	}
}