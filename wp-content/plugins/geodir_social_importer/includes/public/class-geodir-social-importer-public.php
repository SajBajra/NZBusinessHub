<?php
/**
 * The public-specific functionality of the plugin.
 *
 * @since 2.0.0
 *
 * @package    GD_Social_Importer
 * @subpackage GD_Social_Importer/public
 *
 * Class GD_Social_Importer_Public
 */

class GD_Social_Importer_Public {

	/**
	 * Constructor.
	 *
	 * @since 2.0.0
	 *
	 * GD_Social_Importer_Public constructor.
	 */
	public function __construct() {
		
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles_and_scripts' ) );

		// form fields.
		//add_action( 'geodir_before_detail_fields', array( $this, 'gd_add_listing_top_browser_extension' ) ); // @todo uncomment once chrome extension is ready
		add_action( 'geodir_before_detail_fields', array( $this, 'gd_add_listing_top_code' ) );
		add_action( 'geodir_before_detail_fields', array( $this, 'gmb_import_input' ) );
		add_action( 'geodir_after_main_form_fields', array( $this, 'gd_add_listing_bottom_code' ) );
		
		// ajax handlers.
		add_action( 'wp_ajax_gdfi_get_fb_page_data', array( $this, 'gdfi_get_fb_page_data' ) );
		add_action( 'wp_ajax_nopriv_gdfi_get_fb_page_data', array( $this, 'gdfi_get_fb_page_data' ) );
		add_action( 'wp_ajax_gdfi_get_fb_scraping_progress', array( $this, 'gdfi_get_fb_scraping_progress' ) );
		add_action( 'wp_ajax_nopriv_gdfi_get_fb_scraping_progress', array( $this, 'gdfi_get_fb_scraping_progress' ) );

		// auth tokens.
		add_action( 'wp_ajax_geodir_gmb_authorize_user', array( $this, 'gmb_authorize_user' ) );
		add_action( 'wp_ajax_nopriv_geodir_gmb_authorize_user', array( $this, 'gmb_authorize_user' ) );
		add_action( 'wp_ajax_geodir_gmb_revoke_user', array( $this, 'gmb_revoke_user' ) );
		add_action( 'wp_ajax_nopriv_geodir_gmb_revoke_user', array( $this, 'gmb_revoke_user' ) );

		add_action( 'wp_ajax_geodir_gmb_get_accounts', array( $this, 'gmb_get_accounts' ) );
		add_action( 'wp_ajax_nopriv_geodir_gmb_get_accounts', array( $this, 'gmb_get_accounts' ) );

		// import lisitng.
		//add_action( 'wp_ajax_geodir_gmb_ce_import_listing', array( $this, 'gmb_ce_import_listing' ) ); // @todo uncomment once chrome extension is ready
		//add_action( 'wp_ajax_nopriv_geodir_gmb_ce_import_listing', array( $this, 'gmb_ce_import_listing' ) ); // @todo uncomment once chrome extension is ready

		add_action( 'wp_ajax_geodir_gmb_get_locations', array( $this, 'gmb_get_locations' ) );
		add_action( 'wp_ajax_nopriv_geodir_gmb_get_locations', array( $this, 'gmb_get_locations' ) );
		add_action( 'wp_ajax_geodir_gmb_import_location', array( $this, 'gmb_import_location' ) );
		add_action( 'wp_ajax_nopriv_geodir_gmb_import_location', array( $this, 'gmb_import_location' ) );
		add_action( 'geodir_params', array( $this, 'localize_core_params' ), 20 );
	}

	/**
	 * Register and enqueue duplicate alert styles and scripts.
	 *
	 * @since 2.0.0
	 */
	public function enqueue_styles_and_scripts(){
		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		$aui = geodir_design_style() ? '/aui' : '';

		// Register scripts
		wp_register_script( 'geodir-social-gmb', GD_SOCIAL_IMPORTER_PLUGIN_URL . 'assets' . $aui . '/js/gmb' . $suffix . '.js', array( 'jquery', 'geodir' ), GD_SOCIAL_IMPORTER_VERSION );

		if ( geodir_is_page( 'add-listing' ) && ! geodir_is_page( 'edit-listing' ) && $this->is_import_gmb_active() ) {
			wp_enqueue_script( 'geodir-social-gmb' );
		}
	}

	/**
	 * Add the Chrome Extension field above listing fields.
	 *
	 * @since 2.0.0
	 */
	public function gd_add_listing_top_browser_extension(){
		global $aui_bs5;

		if ( ! ( geodir_is_page( 'add-listing' ) && ! geodir_is_page( 'edit-listing' ) ) ) {
			return;
		}

		$design_style = geodir_design_style();
		$user_website_url = home_url(); 

		$user_token = $this->gmb_get_user_token_id();

		if( $design_style ) {
			$buttons = '<span class="d-block mt-2">';

			$buttons .= aui()->button(
				array(
					'type' => 'a',
					'href' => 'https://wpgeodirectory.com/',
					'class' => 'btn btn-success',
					'content' => __( 'Install Extension', 'gd-social-importer' ),
				)
			);

			$buttons .= aui()->button(
				array(
					'type' => 'button',
					'class' => 'btn btn-primary gmb_copy_extension_token',
					'content' => __( 'Copy Token', 'gd-social-importer' ),
					'extra_attributes' => array(
						'data-token' => $user_website_url . '@' . $user_token,
						'data-copied' => __( 'Token Copied!', 'gd-social-importer' ),
					),
				)
			);

			$buttons .= '</div>';

			echo "<div id='gd-social-importer'>";
				echo aui()->alert(
					array(
						'type'		=> 'dark',
						'heading' 	=> __( "GeoDirectory Chrome Extension", "gd-social-importer" ),
						'icon' 		=> false,
						'content'	=> __( "Install the GeoDirectory Chrome extension to effortlessly import listings while browsing, and then paste your web token below to link it with your account.", "gd-social-importer" ),
						'footer'	=> $buttons,
					)
				);
			echo "</span>";
		}

		?>
		<script type="text/javascript">
			jQuery(function($) {
				$('.gmb_copy_extension_token').on('click', function(e) {
					e.preventDefault();

					var $btn = jQuery(this);
					var token = $btn.data("token");
					$("<input>")
						.val(token)
						.appendTo("body")
						.select()
						.each(function() {
							document.execCommand('copy');
						})
					.remove();
        
        			$btn.html($btn.data('copied'));
				});
			});
		</script>
		<?php
	}

	public function gmb_ce_import_listing() {

	}

	/**
	 * Add Social import fields above listing fields.
	 *
	 * @since 2.0.0
	 */
	public function gd_add_listing_top_code(){
		global $aui_bs5;

		if ( ! ( geodir_is_page( 'add-listing' ) && ! geodir_is_page( 'edit-listing' ) ) ) {
			return;
		}

		$show_facebook = geodir_get_option('si_enable_fb_scrapper') || geodir_get_option('si_fb_scraping_bot_io_api_key') ? true : false;
		$show_yelp = empty(geodir_get_option('si_yelp_api_key')) ? false : true;
		$show_ta = empty(geodir_get_option('si_enable_ta_scrapper')) ? false : true;

		if($show_facebook || $show_yelp || $show_ta) {

			$show_array = array();
			if ( $show_facebook ) {
				$show_array[] = __( 'facebook page', 'gd-social-importer' );
			}
			if ( $show_yelp ) {
				$show_array[] = __( 'Yelp page', 'gd-social-importer' );
			}
			if  ( $show_ta ){
				$show_array[] = __( 'TripAdvisor', 'gd-social-importer' );
			}

			$text = sprintf( __('Enter %s url', 'gd-social-importer'), implode(", ",$show_array));

			$text = apply_filters( 'geodir_social_importer_input_text', $text, $show_facebook, $show_yelp );

			$nonce = wp_create_nonce( 'si-import-nonce' );
			$design_style = geodir_design_style();
			
			if( $design_style ){
				echo "<div id='gd-social-importer'>";
				echo aui()->alert(
					array(
						'type'		=> 'info',
						'content'	=> __( "Import Details from Social URLs", "gd-social-importer" )
					)
				);
				echo aui()->input(
					array(
						'id'			=> 'gdfi_import_url',
						'name'			=> 'gdfi_import_url',
						'type'			=> 'text',
						'placeholder'	=> esc_attr( $text ),
						'input_group_right' => '<span id="gdsi-loading" class="input-group-text bg-white z-index-1 ' . ( $aui_bs5 ? 'border-start-0' : 'border-left-0' ) . '" style="display:none;"><div class="spinner-border spinner-border-sm" role="status"></div></span>'.aui()->button(
							array(
								'type'		=> 'button',
								'class'		=> 'geodir_button btn btn-primary',
								'id'		=> 'gd_facebook_import',
								'content'	=>  __( 'Import Details', 'gd-social-importer' )
							)
						)
					) 
				);

		        echo aui()->input(
					array(
						'id'	=> 'si_import_nonce',
						'name'	=> 'si_import_nonce',
						'type'	=> 'hidden',
						'value'	=> $nonce,
					) 
				);
				echo '
				<div class="gdsi-import-progress d-none">
					<div class="progress ' . ( $aui_bs5 ? 'mb-3' : 'form-group' ) . '" style="height: 25px;">
						<div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width: 0%"></div>
					</div>
				';

				aui()->alert(
					array(
						'type'		=> 'info',
						'class'     => 'gdsi-import-progress-taking-long d-none',
						'content'	=> __( 'Things are taking a little longer but are still working', 'gd-social-importer' )
					),
					true
				);

				echo '</div>';
				echo "</div>";
			}else{
			?>
				<div id="gd-social-importer">
		            <h5><?php _e( 'Import Details from Social', 'gd-social-importer' ); ?></h5>
		            <input type="text" placeholder="<?php echo esc_attr($text); ?>" id="gdfi_import_url" style="width:100%;"/>
		            <button id="gd_facebook_import" style="margin: 10px 0 0 0;" class="geodir_button"><?php _e( 'Import Details', 'gd-social-importer' ); ?></button>
		            <input type="hidden" name="si_import_nonce" id="si_import_nonce" value="<?php echo $nonce; ?>">
		            <div id="gdsi-loading" style="margin:0px;display:inline-block;display:none;"><i class="fa fa-refresh fa-spin" aria-hidden="true"></i></div>
				</div>
			<?php
			}
		}

	}

	/**
	 * Show GMB input in frontend add listing page.
	 *
	 * @since 2.1.1.0
	 */
	public function gmb_import_input(){
		global $aui_bs5, $geodir_label_type;

		if ( ! ( geodir_is_page( 'add-listing' ) && ! geodir_is_page( 'edit-listing' ) ) ) {
			return;
		}

		if ( ! $this->is_import_gmb_active() ) {
			return;
		}

		if ( ! geodir_design_style() ) {
			$this->gmb_import_input_legacy();

			return;
		}

		$output = '<fieldset class="' . ( $aui_bs5 ? 'mb-3' : 'form-group' ) . '" id="geodir_fieldset_import_gmb" data-rule-key="import_gmb" data-rule-type="fieldset"><h3 class="h3">' . __( 'Import from Google My Business', 'gd-social-importer' ) . '</h3></fieldset>';
		$label_type = $geodir_label_type ? $geodir_label_type : 'horizontal';

		if ( $label_type == 'floating' ) {
			$label_type = 'top';
		}

		$connect_button = aui()->button(
			array(
				'type' => 'badge',
				'id' => 'geodir_gmb_connect',
				'class' => 'btn btn-secondary rounded',
				'content' => __( 'Connect to Google My Business', 'gd-social-importer' ),
				'icon' => ''
			)
		);

		$output .= aui()->input(
			array(
				'id'                => 'gmb_connect',
				'name'              => '',
				'label'             => __( 'Connect', 'gd-social-importer' ),
				'label_show'        => true,
				'label_type'        => $label_type,
				'type'              => 'hidden',
				'class'             => '',
				'input_group_right' => $connect_button,
				'help_text'         => __( 'Connect to your Google My Business account and get auth code.', 'gd-social-importer' )
			)
		);

		$authorize_button = aui()->button(
			array(
				'type' => 'badge',
				'id' => 'geodir_gmb_authorize',
				'class' => 'btn btn-secondary',
				'content' => __( 'Authorize', 'gd-social-importer' ),
				'extra_attributes' => array(
					'data-nonce' => wp_create_nonce( 'gmb_authorize' ),
					'data-account-nonce' => wp_create_nonce( 'gmb_accounts' ),
					'data-location-nonce' => wp_create_nonce( 'gmb_locations' ),
				),
			)
		);

		$output .= aui()->input(
			array(
				'type' => 'text',
				'id' => 'gmb_auth_code',
				'name' => '',
				'label' => __( 'Auth Code', 'gd-social-importer' ),
				'label_show' => true,
				'label_type' => $label_type,
				'title' => __( 'GMB Auth Code', 'gd-social-importer' ),
				'placeholder' => __( 'ENTER GMB AUTH CODE HERE', 'gd-social-importer' ),
				'value' => '',
				'help_text' => __( 'You must save changes after entering auth code here.', 'gd-social-importer' ),
				'input_group_right' => $authorize_button,
				'wrap_class' => 'd-none'
			)
		);

		$authorized_text = '<span class="text-success border border-white px-3 py-1 align-middle"><i class="fas fa-check-circle"></i> ' . __( 'Authorized', 'gd-social-importer' ) . '</span>';

		$revoke_button = aui()->button(
			array(
				'type' => 'badge',
				'id' => 'geodir_gmb_revoke',
				'class' => 'btn btn-secondary align-middle rounded ' . ( $aui_bs5 ? 'ms-3' : 'ml-3' ),
				'content' => __( 'Revoke', 'gd-social-importer' ),
				'extra_attributes' => array(
					'data-nonce' => wp_create_nonce( 'gmb_revoke' )
				),
			)
		);

		$output .= aui()->input(
			array(
				'id' => 'gmb_authorized',
				'type' => 'hidden',
				'name' => '',
				'label' => __( 'Connect', 'gd-social-importer' ),
				'label_show' => true,
				'label_type' => $label_type,
				'wrap_class' => 'd-none',
				'input_group_right' => $authorized_text . $revoke_button,
				'help_text' => '',
			)
		);

		$output .= aui()->input(
			array(
				'id' => 'gmb_account',
				'type' => 'hidden',
				'name' => '',
				'label' => __( 'Business Account', 'gd-social-importer' ),
				'label_show' => true,
				'label_type' => $label_type,
				'wrap_class' => 'd-none',
				'input_group_right' => '<span class="form-text d-block"><i class="fas fa-spinner fa-spin ' . ( $aui_bs5 ? 'me-1' : 'mr-1' ) . '" aria-hidden="true"></i> ' . __( 'Searching for the business accounts...', 'gd-social-importer' ) , '</span>',
				'help_text' => '',
			)
		);

		$output .= aui()->input(
			array(
				'id' => 'gmb_location',
				'type' => 'hidden',
				'name' => '',
				'label' => __( 'Business Location', 'gd-social-importer' ),
				'label_show' => true,
				'label_type' => $label_type,
				'wrap_class' => 'd-none',
				'input_group_right' => '<span class="form-text d-block"><i class="fas fa-spinner fa-spin ' . ( $aui_bs5 ? 'me-1' : 'mr-1' ) . '" aria-hidden="true"></i> ' . __( 'Searching for the business locations...', 'gd-social-importer' ) , '</span>',
				'help_text' => '',
			)
		);

		echo $output;
	}

	/**
	 * Show GMB input in frontend add listing page.
	 *
	 * @since 2.1.1.0
	 */
	public function gmb_import_input_legacy(){
		?>
		<h5 id="geodir_fieldset_import_gmb" class="geodir-fieldset-row" gd-fieldset="import_gmb"><?php _e( 'Import from Google My Business', 'gd-social-importer' ); ?></h5>
		<div id="gmb_connect_row" class="geodir_form_row clearfix gd-fieldset-import_gmb">
			<label for="geodir_gmb_connect"><?php _e( 'Connect', 'gd-social-importer' ); ?></label> 
			<a id="geodir_gmb_connect" class="geodir_button button" href="javascript:void(0)"><?php _e( 'Connect to Google My Business', 'gd-social-importer' ); ?></a>
			<span class="geodir_message_note"><?php _e( 'Connect to your Google My Business account and get auth code.', 'gd-social-importer' ); ?></span>
		</div>
		<div id="gmb_auth_code_row" class="geodir_form_row clearfix gd-fieldset-import_gmb" style="display:none;">
			<label for="gmb_auth_code"><?php _e( 'Auth Code', 'gd-social-importer' ); ?></label> 
			<span><input field_type="text" id="gmb_auth_code" placeholder="<?php esc_attr_e( 'ENTER GMB AUTH CODE HERE', 'gd-social-importer' ); ?>" value="" type="text" class="geodir_textfield" style="width:50%">&nbsp;&nbsp;<a id="geodir_gmb_authorize" class="geodir_button button" href="javascript:void(0)" data-nonce="<?php echo esc_attr( wp_create_nonce( 'gmb_authorize' ) ); ?>" data-account-nonce="<?php echo esc_attr( wp_create_nonce( 'gmb_accounts' ) ); ?>" data-location-nonce="<?php echo esc_attr( wp_create_nonce( 'gmb_locations' ) ); ?>"><?php _e( 'Authorize', 'gd-social-importer' ); ?></a></span>
			<span class="geodir_message_note"><?php _e( 'You must save changes after entering auth code here.', 'gd-social-importer' ); ?></span>
		</div>
		<div id="gmb_authorized_row" class="geodir_form_row clearfix gd-fieldset-import_gmb" style="padding-bottom:15px;display:none">
			<label><?php _e( 'Connect', 'gd-social-importer' ); ?></label> 
			<span class=""><i class="fas fa-check-circle"></i> <?php _e( 'Authorized', 'gd-social-importer' ); ?></span>&nbsp;&nbsp;<a id="geodir_gmb_revoke" class="geodir_button button" href="javascript:void(0)" data-nonce="<?php echo esc_attr( wp_create_nonce( 'gmb_revoke' ) ); ?>"><?php _e( 'Revoke', 'gd-social-importer' ); ?></a>
		</div>
		<div id="gmb_account_row" class="geodir_form_row clearfix gd-fieldset-import_gmb" style="display:none">
			<label for="gmb_account"><?php _e( 'Business Account', 'gd-social-importer' ); ?></label> 
			<div class="gmb-input-wrap"><span class="form-text d-block"><i class="fas fa-spinner fa-spin" aria-hidden="true"></i>&nbsp;&nbsp;<?php _e( 'Searching for the business accounts...', 'gd-social-importer' ); ?></span></div>
		</div>
		<div id="gmb_location_row" class="geodir_form_row clearfix gd-fieldset-import_gmb" style="display:none">
			<label for="gmb_location"><?php _e( 'Business Location', 'gd-social-importer' ); ?></label> 
			<div class="gmb-input-wrap"><span class="form-text d-block"><i class="fas fa-spinner fa-spin" aria-hidden="true"></i>&nbsp;&nbsp;<?php _e( 'Searching for the business locations...', 'gd-social-importer' ); ?></span></div>
		</div>
		<?php
	}

	/**
	 * Add Import data using page url.
	 *
	 * @since 2.0.0
	 */
	public function gd_add_listing_bottom_code(){
		global $aui_bs5, $gd_post;

		if ( ! ( geodir_is_page( 'add-listing' ) && ! geodir_is_page( 'edit-listing' ) ) ) {
			return;
		}

		$post_type = ! empty( $_REQUEST['listing_type'] ) ? sanitize_text_field( $_REQUEST['listing_type'] ) : '';
		$location_less = ! GeoDir_Post_types::supports( $post_type, 'location' );
		$city_option = geodir_get_option('geodir_enable_city');
		$selected_cities = $city_option == 'selected' ? geodir_get_option('geodir_selected_cities') : array();
		$package = geodir_get_post_package( $gd_post, $post_type );
		?>
		<script type="text/javascript">
			var locLess = <?php echo ( $location_less ? 'true' : 'false' ); ?>;
			gdfi_codeaddress = false;
			gdfi_city = '';
			gdfi_street = '';
			gdfi_zip = '';
			// Here is a VERY basic generic trigger method
			function gdfi_triggerEvent(el, type) {
				if ((el[type] || false) && typeof el[type] == 'function') {
					el[type](el);
				}
			}

			jQuery(function($) {
<?php if ( $this->is_import_gmb_active() ) { ?>
$(document).on('click', '#geodir_gmb_import', function() {
	var gmb_account = jQuery('#gmb_account').val(), gmb_location = jQuery('#gmb_location').val(), gmb_token = $(this).data('access_token');
	if (gmb_account && gmb_location) {
		$el = $('#geodir_gmb_import');

		var data = {
			'action': 'geodir_gmb_import_location',
			'access_token': gmb_token,
			'account': gmb_account,
			'location': gmb_location,
			'post_id': jQuery('#geodirectory-add-post input[name="ID"]').val(),
			'security': jQuery('#geodir_gmb_authorize').data('location-nonce')
		};
		jQuery.ajax({
			url: geodir_params.ajax_url,
			type: 'POST',
			data: data,
			dataType: 'json',
			beforeSend: function(xhr, obj) {
				window.geodirUploading = true;
				$el.prop("disabled", true);
				$el.addClass("disabled");
				$el.find('.geodir-spin-wrap').remove();
				$el.prepend('<span class="geodir-spin-wrap <?php echo ( $aui_bs5 ? 'me-1' : 'mr-1' ); ?>"><i class="fas fa-spinner fa-spin"></i>&nbsp;</span>');
			}
		})
		.done(function(data, textStatus, jqXHR) {
			if (typeof data == 'object') {
				if (data.success && data.data) {
					item = data.data;
					jQuery('#post_imagesdropbox input#post_images').val('');
					jQuery('#post_imagesdropbox input#post_imagestotImg').val('');
					jQuery('#post_imagesdropbox #post_imagesplupload-thumbs').html('');
					
					jQuery('input#post_title').val(item.title);
					jQuery('textarea#post_content').html(item.description);
					if (typeof(tinyMCE) != "undefined") {
						if (item.description && tinyMCE.get('post_content')) {
							tinyMCE.get('post_content').setContent(item.description.replace(/\n/g, "<br />"));
						}
					}
					jQuery('#post_content').trigger('change');
					if ( jQuery('input#phone').length ) {
						jQuery('input#phone').val(item.phone);
					} else {
						jQuery('input[type="tel"]:first').val(item.phone);
					}
					jQuery('input#email').val(item.email);
					jQuery('input#website').val(item.website);

					// Images
					if(item.post_images && jQuery('input#post_images').length){
						jQuery('input#post_images').val(item.post_images);
						plu_show_thumbs('post_images');
						jQuery('#post_imagesdropbox input#post_imagestotImg').val(item.images_count);
					}

					// Logo
					if(item.logo && jQuery('input#logo').length){
						jQuery('input#logo').val(item.logo);
						jQuery('#logodropbox input#logoimage_limit').val(1);
						plu_show_thumbs('logo');
						jQuery('#logodropbox input#logototImg').val(1);
					}

					// Address
					jQuery('input#address_street').val(item.street);
					jQuery('input#address_zip').val(item.zip);

					if(item.latitude && item.longitude){
						jQuery('input#address_latitude').val(item.latitude);
						jQuery('input#address_longitude').val(item.longitude);
						user_address = true;// so the marker move does not change the address
						<?php if ( geodir_lazy_load_map() ) { ?>
						if (window.gdMaps == 'google' || window.gdMaps == 'osm') {
						jQuery("#address_map").geodirLoadMap({
						callback: function() {<?php } ?>
						if (window.gdMaps == 'google') {
							latlon = new google.maps.LatLng(item.latitude, item.longitude);
							jQuery.goMap.map.setCenter(latlon);
							updateMarkerPosition(latlon);
							centerMarker();
							google.maps.event.trigger(baseMarker, 'dragend');
						} else if (window.gdMaps == 'osm') {
							latlon = new L.latLng(item.latitude, item.longitude);
							jQuery.goMap.map.setView(latlon, jQuery.goMap.map.getZoom());
							updateMarkerPositionOSM(latlon);
							centerMarker();
							baseMarker.fireEvent('dragend');
						}

						setTimeout(function () {
							if (window.gdMaps == 'google') {
								google.maps.event.trigger(baseMarker, 'dragend');
							} else if (window.gdMaps == 'osm') {
								baseMarker.fireEvent('dragend');
							}
						}, 1600);<?php if ( geodir_lazy_load_map() ) { ?>
						} }); }<?php } ?>
					}

					if(item.default_city) {
						var set_address = setInterval(function(){
							jQuery('input#address_street').val(item.street);
							jQuery('input#address_zip').val(item.zip);
							clearInterval(set_address);
						}, 1000);

					}

					if(item.business_hours) {
						jQuery('[data-field="active"]').parent().parent().find('input[value="1"]').prop("checked", true).trigger('change');
						jQuery('.gd-bh-remove').trigger( "click" );
						jQuery.each(item.business_hours, function( index, value ) {
							jQuery.each(value, function( keys, bh_value ) {
								var bh_data_value = 'td.gd-bh-time[data-day="'+index+'"]';
								jQuery( bh_data_value ).next('td').find('.gd-bh-add').trigger( "click" );

								var bh_keys = parseInt(keys)+1;
								var bh_open = 'input[name="business_hours_f[hours]['+index+'][open][]"]';
								var bh_close = 'input[name="business_hours_f[hours]['+index+'][close][]"]';
								var $el = jQuery( bh_data_value+" .gd-bh-hours:nth-child("+bh_keys+") "+ bh_open  ).closest('.gd-bh-hours');
								jQuery( bh_data_value+" .gd-bh-hours:nth-child("+bh_keys+") "+ bh_open  ).val(bh_value.open);
								jQuery( bh_data_value+" .gd-bh-hours:nth-child("+bh_keys+") "+ bh_close  ).val(bh_value.close);
								$el.find('[data-field-alt="open"]').val(bh_value.open);
								$el.find('[data-field-alt="close"]').val(bh_value.close);
								$el.find('.gd-alt-open').val(bh_value.open_display);
								$el.find('.gd-alt-close').val(bh_value.close_display);
							});
						});
						<?php if (  geodir_design_style() ) { ?>try{jQuery('.gd-bh-items').find('input').removeClass('flatpickr-input');aui_init_flatpickr();}catch(err){}<?php } ?>

						var	businee_weeks = '['+item.business_hidden_hours+']';
						var	utf_value = '["UTC":"' + geodir_params.gmt_offset + '"]';

						jQuery('input[name="business_hours"]').val(businee_weeks+','+utf_value);
					}else {
						jQuery('.gd-bh-remove').trigger( "click" );
						jQuery('.gd-bh-add').trigger( "click" );
						jQuery('[data-field="active"]').parent().parent().find('input[value="0"]').prop("checked", true).trigger('change');
					}
				} else if (data.data.message) {
					alert(data.data.message);
				}
			}
		})
		.always(function(data, textStatus, jqXHR) {
			window.geodirUploading = false;
			$el.prop("disabled", false);
			$el.removeClass("disabled");
			$el.find('.geodir-spin-wrap').remove();
		});
	} else {
		jQuery('#gmb_location').trigger('focus');
	}
});
<?php } ?>

	function gdfi_scraping_bot_poll_error( error_message ) {
		$( '.gdsi-import-progress' ).addClass( 'd-none' );

		// Display error message.
		alert( error_message );

		jQuery('#gdsi-loading').hide();
		jQuery('#gd_facebook_import').prop('disabled', false);

	}

	function gdfi_scraping_bot_poll( args ) {

		// Fetch response progress.
		jQuery.get(
			"<?php echo esc_url( geodir_get_ajax_url() ); ?>",
			jQuery.extend(
				{
					_ajax_nonce: jQuery('#si_import_nonce').val(),
					post_id: jQuery('#geodirectory-add-post input[name="ID"]').val(),
					action: 'gdfi_get_fb_scraping_progress'
				},
				args
			)
		)

		.done(function( data ) {

			// If not yet crawled, wait for 10 seconds then poll again.
			if ( data.status && 'pending' === data.status ) {
				setTimeout( function() {
					gdfi_scraping_bot_poll( args );
				}, 10000 );

			// If we have a result, display it.
			} else if ( Array.isArray( data ) && data.length ) {
				var profile_info = data[0];

                aui_toast('gd__social_import_success', 'success', '<?php _e("Imported","gd-social-importer");?>');

				$( '.gdsi-import-progress' ).addClass( 'd-none' );

				jQuery('#gdsi-loading').hide();
				jQuery('#gd_facebook_import').prop('disabled', false);

				jQuery('input#post_title').val(profile_info.profile_name);

				if ( jQuery('input#phone').length ) {
					jQuery('input#phone').val(profile_info.phone);
				} else {
					jQuery('input[type="tel"]:first').val(profile_info.phone);
				}

				jQuery('input#email').val(profile_info.email);
				jQuery('input#website').val(profile_info.website);
				if ( profile_info.address ) {
					jQuery('input#address_street').val( profile_info.address.replace(/\n/g, ', ' ) );
				}
				jQuery('input#address_zip').val( profile_info.zip );
				jQuery('input#address_longitude').val( profile_info.longitude );
				jQuery('input#address_latitude').val( profile_info.latitude );
				if (jQuery('input#address_street').val() || profile_info.state_name) {
					jQuery('input#address_region').val( profile_info.state_name );
				}
				if (jQuery('input#address_street').val() || profile_info.city_name) {
					jQuery('input#address_city').val( profile_info.city_name );
				}

				if ( profile_info.country_name ) {
					jQuery("#address_country").val(profile_info.country_name).trigger('change.select2');
				}

				// images
				jQuery( 'input#post_images' ).val( profile_info.images );
				plu_show_thumbs( 'post_images' ); // show the pics
				jQuery( '#post_imagesdropbox input#post_imagestotImg' ).val( profile_info.image_count );

				// logo image
                jQuery( '#logodropbox input#logo' ).val( profile_info.logo );
                jQuery( '#logodropbox input#logototImg' ).val( '' );
                jQuery( '#logodropbox #logoplupload-thumbs' ).html( '' );

                if ( jQuery( 'input#logo' ).val() == '' ) {
                    jQuery( 'input#logo' ).val( profile_info.logo );
                    jQuery( 'input#logototImg' ).val( 1 );
                    jQuery( 'input#logoimage_limit' ).val( 1 );
                    plu_show_thumbs( 'logo' ); // show the pics
                }

				user_address = true;
				if (jQuery('input#address_street').val()) {
					jQuery('#address_set_address_button').trigger('click');
				}

				var content = profile_info.about ? profile_info.about : '';
				jQuery( 'textarea#post_content').val(content);
				if ( typeof(tinyMCE) !== "undefined" && tinyMCE.get( 'post_content' ) ) {
					tinyMCE.get('post_content').setContent( content.replace(/\n/g, '<br />' ) );
				}
				jQuery('#post_content').trigger('change');

			// If we're here then an error occured.
			} else if ( data.error_msg ) {
				gdfi_scraping_bot_poll_error( data.error_msg );

			} else {
				gdfi_scraping_bot_poll_error( data.error );
			}

		})

		.fail(function( err ) {
			console.log( err );
			gdfi_scraping_bot_poll_error( '<?php echo esc_js( __( 'An error occured. Please refresh the page then try again.', 'gd-social-importer' ) ); ?>' );
		});

	}

				jQuery("#gd_facebook_import").on('click', function() {
					var gdfi_url = jQuery('#gdfi_import_url').val();
					var ajax_nonce = jQuery('#si_import_nonce').val();
					var post_id = jQuery('#geodirectory-add-post input[name="ID"]').val();

					if (!gdfi_url) {
						alert('<?php _e('Please enter a value','gd-social-importer'); ?>');
						return false;
					}
					var data = {action: 'gdfi_get_fb_page_data', gdfi_url: gdfi_url,ajax_nonce:ajax_nonce,post_id:post_id};
					<?php if ( ! empty( $package ) && $package->id > 0 ) { ?> 
					data.package_id = <?php echo $package->id; ?>;
					<?php } ?>

					jQuery.ajax({
						type: "POST",
						url: "<?php echo admin_url().'admin-ajax.php';?>",
						data: data,
						beforeSend: function () {
							jQuery('#gdsi-loading').show();
							jQuery('#gd_facebook_import').prop('disabled', true);

							// Animate the progress for facebook imports bar.
							if ( gdfi_url.indexOf( 'facebook.com' ) > -1 ) {
								$( '.gdsi-import-progress-taking-long' ).addClass( 'd-none' );
								$( '.gdsi-import-progress' ).removeClass( 'd-none' );

								// Clear country, region, city.
								jQuery('#address_country,#address_region,#address_city').val('').trigger('change');

								// Dummy animate the progress bar.
								$( '.gdsi-import-progress .progress-bar' )
									.html( '0%' )
									.css( 'width', '0%' )
									.animate({width: "100%"}, {
										duration: 30000,
										easing: "linear",
										step: function( now, fx ) {
											var data = Math.round( now );
											$( this ).html( data + '%' );
											$( this ).attr( 'aria-valuenow', data );
										},
										complete: function() {
											$( this ).html( '100%' );
											$( this ).attr( 'aria-valuenow', 100 );
											$( '.gdsi-import-progress-taking-long' ).removeClass( 'd-none' );
										}
									});
							}

						},
						success: function (data) {
							try {
								data = jQuery.parseJSON(data);
							} catch (err) {
								alert(data);
								jQuery('#gdsi-loading').hide();
								jQuery('#gd_facebook_import').prop('disabled', false);
								return
							}
							jQuery('#gdsi-loading').hide();
							jQuery('#gd_facebook_import').prop('disabled', false);

							if( data.error_msg ) {
								$( '.gdsi-import-progress' ).addClass( 'd-none' );
								alert(data.error_msg);
								return false;
							}

							if( data.error ) {
								$( '.gdsi-import-progress' ).addClass( 'd-none' );
								alert(data.error);
								return false;
							}

							// If this is scrapping-bot, start polling.
							if( data.src && data.src == 'scrapingbot' ) {
								jQuery('#gdsi-loading').show();
								jQuery('#gd_facebook_import').prop('disabled', true);

								// Wait before checking response status.
								setTimeout( function() {
									gdfi_scraping_bot_poll( data );
								}, 10000 );
								return;
							}

							$( '.gdsi-import-progress' ).addClass( 'd-none' );

							jQuery('#post_imagesdropbox input#post_images').val('');
							jQuery('#post_imagesdropbox input#post_imagestotImg').val('');
							jQuery('#post_imagesdropbox #post_imagesplupload-thumbs').html('');

                            aui_toast('gd__social_import_success', 'success', '<?php _e("Imported","gd-social-importer");?>');

							var tags = '';

							// Import Facebook data.
							if(data.is_facebook) {

								jQuery('input#post_title').val(data.fb_title);
								jQuery('textarea#post_content').html(data.fb_description);
								if (typeof(tinyMCE) != "undefined") {
									if (data.fb_description && tinyMCE.get('post_content')) {
										tinyMCE.get('post_content').setContent(data.fb_description.replace(/\n/g, "<br />"));
									}
								}
								jQuery('#post_content').trigger('change');
								if ( jQuery('input#phone').length ) {
									jQuery('input#phone').val(data.fb_contact);
								} else {
									jQuery('input[type="tel"]:first').val(data.fb_contact);
								}
								jQuery('input#email').val(data.fb_email);
								jQuery('input#website').val(data.fb_website);
								jQuery('input#facebook').val(data.fb_facebook);

								if (data.fb_event) {
									jQuery('input#event_start_date').val(data.fb_event_sdate);
									jQuery('input#event_end_date').val(data.fb_event_edate);
									jQuery('#event_start_time').val(data.fb_event_stime).trigger('change');
									jQuery('#event_end_time').val(data.fb_event_etime).trigger('change');
								}

								// images
								if(jQuery('input#post_images').val()==''){
									jQuery('input#post_images').val(data.fb_post_images);
									plu_show_thumbs('post_images'); // show the pics
									jQuery('#post_imagesdropbox input#post_imagestotImg').val(data.fb_images_count);
								}

								// logo image
                                jQuery('#logodropbox input#logo').val('');
                                jQuery('#logodropbox input#logototImg').val('');
                                jQuery('#logodropbox #logoplupload-thumbs').html('');

                                if(jQuery('input#logo').val()==''){
                                    jQuery('input#logo').val(data.fb_logo_images);
                                    jQuery('input#logototImg').val(1);
                                    jQuery('input#logoimage_limit').val(1);
                                    plu_show_thumbs('logo'); // show the pics
                                }
								// address
								jQuery('input#address_street').val(data.fb_address);
								jQuery('input#address_zip').val(data.fb_zipcode);

								if( data.fb_videos && data.fb_videos != '' ) {
                                    var video_arr = data.fb_videos.split("|");
                                    var videos_string = video_arr.join("\r\n");
									jQuery('textarea#video').val(videos_string);
                                }

								if(data.fb_address_latitude && data.fb_address_longitude){
									jQuery('input#address_latitude').val(data.fb_address_latitude);
									jQuery('input#address_longitude').val(data.fb_address_longitude);
									user_address = true;// so the marker move does not change the address
									<?php if ( geodir_lazy_load_map() ) { ?>
									if (window.gdMaps == 'google' || window.gdMaps == 'osm') {
									jQuery("#address_map").geodirLoadMap({
									callback: function() {<?php } ?>
									if (window.gdMaps == 'google') {
										latlon = new google.maps.LatLng(data.fb_address_latitude, data.fb_address_longitude);
										jQuery.goMap.map.setCenter(latlon);
										updateMarkerPosition(latlon);
										centerMarker();
										google.maps.event.trigger(baseMarker, 'dragend');
									} else if (window.gdMaps == 'osm') {
										latlon = new L.latLng(data.fb_address_latitude, data.fb_address_longitude);
										jQuery.goMap.map.setView(latlon, jQuery.goMap.map.getZoom());
										updateMarkerPositionOSM(latlon);
										centerMarker();
										baseMarker.fireEvent('dragend');
									}

									setTimeout(function () {
										if (window.gdMaps == 'google') {
											google.maps.event.trigger(baseMarker, 'dragend');
										} else if (window.gdMaps == 'osm') {
											baseMarker.fireEvent('dragend');
										}
									}, 1600);<?php if ( geodir_lazy_load_map() ) { ?>
									} }); }<?php } ?>
								}

								if(data.fb_default_city) {
									var set_address = setInterval(function(){
										jQuery('input#address_street').val(data.fb_address);
										jQuery('input#address_zip').val(data.fb_zipcode);
										clearInterval(set_address);
									}, 1000);
								}

								if(data.fb_business_hour) {
									jQuery('[data-field="active"]').parent().parent().find('input[value="1"]').prop("checked", true).trigger('change');
									jQuery('.gd-bh-remove').trigger( "click" );

									jQuery.each(data.fb_business_hour, function( index, value ) {

										jQuery.each(value, function( keys, bh_value ) {
											var bh_data_value = 'td.gd-bh-time[data-day="'+index+'"]';
											jQuery( bh_data_value ).next('td').find('.gd-bh-add').trigger( "click" );

											var bh_keys = parseInt(keys)+1;
											var bh_open = 'input[name="business_hours_f[hours]['+index+'][open][]"]';
											var bh_close = 'input[name="business_hours_f[hours]['+index+'][close][]"]';
											var $el = jQuery( bh_data_value+" .gd-bh-hours:nth-child("+bh_keys+") "+ bh_open  ).closest('.gd-bh-hours');
											jQuery( bh_data_value+" .gd-bh-hours:nth-child("+bh_keys+") "+ bh_open  ).val(bh_value.open);
											jQuery( bh_data_value+" .gd-bh-hours:nth-child("+bh_keys+") "+ bh_close  ).val(bh_value.close);
											$el.find('[data-field-alt="open"]').val(bh_value.open);
											$el.find('[data-field-alt="close"]').val(bh_value.close);
											$el.find('.gd-alt-open').val(bh_value.open_display);
											$el.find('.gd-alt-close').val(bh_value.close_display);
										});
									});
									<?php if (  geodir_design_style() ) { ?>try{jQuery('.gd-bh-items').find('input').removeClass('flatpickr-input');aui_init_flatpickr();}catch(err){}<?php } ?>

									var	businee_weeks = '['+data.fb_business_hidden_hours+']';
									var	utf_value = '["UTC":"' + geodir_params.gmt_offset + '"]';

									jQuery('input[name="business_hours"]').val(businee_weeks+','+utf_value);
								}else {
									jQuery('.gd-bh-remove').trigger( "click" );
									jQuery('.gd-bh-add').trigger( "click" );
									jQuery('[data-field="active"]').parent().parent().find('input[value="0"]').prop("checked", true).trigger('change');
								}
							}

							// Import Tripadvisor data.
							if(data.is_tripadvisor) {
								jQuery('input#post_title').val(data.trip_title);
								jQuery('textarea#post_content').html(data.trip_description);
								if (typeof(tinyMCE) != "undefined") {
									if (data.trip_description && tinyMCE.get('post_content')) {
										tinyMCE.get('post_content').setContent(data.trip_description.replace(/\n/g, "<br />"));
									}
								}
								jQuery('#post_content').trigger('change');
								if (data.trip_mobile) {
									if ( jQuery('input#phone').length ) {
										jQuery('input#phone').val(data.trip_mobile);
									} else if ( jQuery('input#mobile').length ) {
										jQuery('input#mobile').val(data.trip_mobile);
									} else {
										jQuery('input[type="tel"]:first').val(data.trip_mobile);
									}
								}
								jQuery('input#email').val(data.trip_email);
								jQuery('input#website').val(data.trip_website);

								jQuery('input#address_street').val(data.trip_address);
								jQuery('input#address_zip').val(data.trip_zipcode);

								if(data.trip_latitude && data.trip_longitude){
									jQuery('input#address_latitude').val(data.trip_latitude);
									jQuery('input#address_longitude').val(data.trip_longitude);
									user_address = true;// so the marker move does not change the address
									<?php if ( geodir_lazy_load_map() ) { ?>
									if (window.gdMaps == 'google' || window.gdMaps == 'osm') {
									jQuery("#address_map").geodirLoadMap({
									callback: function() {<?php } ?>
									if (window.gdMaps == 'google') {
										latlon = new google.maps.LatLng(data.trip_latitude, data.trip_longitude);
										jQuery.goMap.map.setCenter(latlon);
										updateMarkerPosition(latlon);
										centerMarker();
										google.maps.event.trigger(baseMarker, 'dragend');
									} else if (window.gdMaps == 'osm') {
										latlon = new L.latLng(data.trip_latitude, data.trip_longitude);
										jQuery.goMap.map.setView(latlon, jQuery.goMap.map.getZoom());
										updateMarkerPositionOSM(latlon);
										centerMarker();
										baseMarker.fireEvent('dragend');
									}

									setTimeout(function () {
										if (window.gdMaps == 'google') {
											google.maps.event.trigger(baseMarker, 'dragend');
										} else if (window.gdMaps == 'osm') {
											baseMarker.fireEvent('dragend');
										}
									}, 1600);<?php if ( geodir_lazy_load_map() ) { ?>
									} }); }<?php } ?>
								}

								if (data.business_hours && data.business_hidden_hours) {
									jQuery('[data-field="active"]').parent().parent().find('input[value="1"]').prop("checked", true).trigger('change');
									jQuery('.gd-bh-remove').trigger("click");

									jQuery.each(data.business_hours, function(index, value) {
										jQuery.each(value, function(keys, bh_value) {
											var bh_data_value = 'td.gd-bh-time[data-day="' + index + '"]';
											jQuery(bh_data_value).next('td').find('.gd-bh-add').trigger("click");
											var bh_keys = parseInt(keys) + 1;
											var bh_open = 'input[name="business_hours_f[hours][' + index + '][open][]"]';
											var bh_close = 'input[name="business_hours_f[hours][' + index + '][close][]"]';
											var $el = jQuery(bh_data_value + " .gd-bh-hours:nth-child(" + bh_keys + ") " + bh_open).closest('.gd-bh-hours');
											jQuery(bh_data_value + " .gd-bh-hours:nth-child(" + bh_keys + ") " + bh_open).val(bh_value.open);
											jQuery(bh_data_value + " .gd-bh-hours:nth-child(" + bh_keys + ") " + bh_close).val(bh_value.close);
											$el.find('[data-field-alt="open"]').val(bh_value.open);
											$el.find('[data-field-alt="close"]').val(bh_value.close);
											$el.find('.gd-alt-open').val(bh_value.open_display);
											$el.find('.gd-alt-close').val(bh_value.close_display);
										});
									});
									<?php if (  geodir_design_style() ) { ?>try{jQuery('.gd-bh-items').find('input').removeClass('flatpickr-input');aui_init_flatpickr();}catch(err){}<?php } ?>

									var businee_weeks = '[' + data.business_hidden_hours + ']';
									var utf_value = '["UTC":"' + geodir_params.gmt_offset + '"]';

									jQuery('input[name="business_hours"]').val(businee_weeks + ',' + utf_value);
								} else {
									jQuery('.gd-bh-remove').trigger("click");
									jQuery('.gd-bh-add').trigger("click");
									jQuery('[data-field="active"]').parent().parent().find('input[value="0"]').prop("checked", true).trigger('change');
								}

								// Images
								if(data.trip_images_path && jQuery('input#post_images').length){
									jQuery('input#post_images').val(data.trip_images_path);
									plu_show_thumbs('post_images');
									jQuery('#post_imagesdropbox input#post_imagestotImg').val(data.trip_images_count);
								}
							}

							// Import Yelp data.
							if( data.is_yelp ) {
								jQuery('input#post_title').val(data.yelp_title);
								jQuery('textarea#post_content').html(data.yelp_description);
								if (typeof(tinyMCE) != "undefined") {
									if (data.yelp_description && tinyMCE.get('post_content')) {
										tinyMCE.get('post_content').setContent(data.yelp_description.replace(/\n/g, "<br />"));
									}
								}
								jQuery('#post_content').trigger('change');
								jQuery('input#address_street').val(data.yelp_address);
								jQuery('input#address_zip').val(data.yelp_zipcode);

								// clear country
								if(jQuery('#address_country').length){
									jQuery('#address_country').prepend("<option value='' data-country_code='"+data.yelp_country_code+"'></option>").val('');
									jQuery("#address_country").trigger("chosen:updated");
								}

								// clear region
								if(jQuery('#address_region').length){
									jQuery('#address_region').prepend("<option value=''></option>").val('');
									jQuery("#address_region").trigger("chosen:updated");
								}

								// clear city
								if(jQuery('#address_city').length){
									jQuery('#address_city').prepend("<option value=''></option>").val('');
									jQuery("#address_city").trigger("chosen:updated");
								}

								if (data.yelp_mobile) {
									if(jQuery('input#mobile').length){
										jQuery('input#mobile').val(data.yelp_mobile);
									} else if(jQuery('input#phone').length){
										jQuery('input#phone').val(data.yelp_mobile);
									} else if(jQuery('input[type="phone"]').length){
										jQuery('input[type="phone"]:first').val(data.yelp_mobile);
									}
								}
								jQuery('input#email').val(data.yelp_email);
								jQuery('input#website').val(data.yelp_website);
								
								jQuery('#address_set_address_button').trigger( "click" );

								if( data.yelp_business_hour ) {
									jQuery('[data-field="active"]').parent().parent().find('input[value="1"]').prop("checked", true).trigger('change');
									jQuery('.gd-bh-remove').trigger( "click" );

									jQuery.each(data.yelp_business_hour, function( index, value ) {
										jQuery.each(value, function( keys, bh_value ) {
											var bh_data_value = 'td.gd-bh-time[data-day="'+index+'"]';
											jQuery( bh_data_value ).next('td').find('.gd-bh-add').trigger( "click" );

											var bh_keys = parseInt(keys)+1;
											var bh_open = 'input[name="business_hours_f[hours]['+index+'][open][]"]';
											var bh_close = 'input[name="business_hours_f[hours]['+index+'][close][]"]';
											var $el = jQuery( bh_data_value+" .gd-bh-hours:nth-child("+bh_keys+") "+ bh_open  ).closest('.gd-bh-hours');
											jQuery( bh_data_value+" .gd-bh-hours:nth-child("+bh_keys+") "+ bh_open  ).val(bh_value.open);
											jQuery( bh_data_value+" .gd-bh-hours:nth-child("+bh_keys+") "+ bh_close  ).val(bh_value.close);
											$el.find('[data-field-alt="open"]').val(bh_value.open);
											$el.find('[data-field-alt="close"]').val(bh_value.close);
											$el.find('.gd-alt-open').val(bh_value.open_display);
											$el.find('.gd-alt-close').val(bh_value.close_display);
										});
									});
									<?php if (  geodir_design_style() ) { ?>try{jQuery('.gd-bh-items').find('input').removeClass('flatpickr-input');aui_init_flatpickr();}catch(err){}<?php } ?>

									var	businee_weeks = '['+data.yelp_business_hidden_hour+']';
									var	utf_value = '["UTC":"' + geodir_params.gmt_offset + '"]';

									jQuery('input[name="business_hours"]').val(businee_weeks+','+utf_value);
									
								} else{
									jQuery('.gd-bh-remove').trigger( "click" );
									jQuery('.gd-bh-add').trigger( "click" );
									jQuery('[data-field="active"]').parent().parent().find('input[value="0"]').prop("checked", true).trigger('change');
								}

								if( data.yelp_images_path ) {
									jQuery('#post_imagesdropbox input#post_images').val(data.yelp_images_path);
									jQuery('#post_imagesdropbox input#post_imagestotImg').val(data.yelp_images_count);

									plu_show_thumbs('post_images');
								}
							}

							jQuery('body').trigger('geodir_social_import_data', data);
						},
						error: function(xhr, textStatus, errorThrown) {
							$( '.gdsi-import-progress' ).addClass( 'd-none' );
						}
					});

					return false;
				});
			});
		</script>
		<?php

	}

	/**
	 * Function is used for social import ajax callback.
	 *
	 * @since 2.0.0
	 */
	public function gdfi_get_fb_page_data(){

		// check ajax nonce for security.
		if ( empty( $_REQUEST['ajax_nonce'] ) || ! wp_verify_nonce( $_REQUEST['ajax_nonce'], 'si-import-nonce' ) ) {
			wp_die( __( 'Action failed. Please refresh the page and retry.', 'gd-social-importer' ) );
		}

		$page_url = !empty( $_POST['gdfi_url'] ) ? esc_url(trim($_POST['gdfi_url'])) :'';

		$page_response = $this->gdfi_get_import_page_id( $page_url );

		echo $page_response;

		wp_die();
	}

	/**
	 * Checks the progress of the scraping request.
	 *
	 * @since 2.0.0
	 */
	public function gdfi_get_fb_scraping_progress() {
		check_ajax_referer( 'si-import-nonce' );

		$args = array(
			'responseId' => isset( $_GET['responseId'] ) ? sanitize_text_field( $_GET['responseId'] ) : '',
			'scraper'    => isset( $_GET['scraper'] ) ? sanitize_text_field( $_GET['scraper'] ) : '',
		);

		$facebook = new GD_Social_Importer_Facebook();
		$response = $facebook->get_srapingbot_response( $args );

		if ( is_wp_error( $response ) ) {
			$response = array( 'error' => $response->get_error_message() );
		}

		wp_send_json( $response );
	}

    /**
     * Get listing fields import data using Page URL.
     *
     * @since 2.0.0
     *
     * @param string $url Get social importer Page URL.
     *
     * @return mixed|string $response
     *
     * @throws Exception
     */
	public function gdfi_get_import_page_id( $url ){

	    $response = '';

        $url = urldecode($url);

        if ( strpos( $url, 'facebook.com/' ) !== false ) {

	        $url = str_replace( 'www.facebook.com', 'en-gb.facebook.com', $url);

            $enable_scrapper = geodir_get_option('si_enable_fb_scrapper','');

            $facebook_obj = new GD_Social_Importer_Facebook();

            if( !empty( $enable_scrapper ) && 1 == $enable_scrapper ) {

                $response = $facebook_obj->gdfi_get_fb_meta( $url );

            } else{

				$response = $facebook_obj->get_srapingbot_response_id( $url );

				if ( is_wp_error( $response ) ) {
					$response = json_encode( array('error_msg'=> $response->get_error_message() ) );
				} else {
					$response = json_encode( $response );
				}
            }

        } elseif ( strpos( $url, 'www.yelp.' ) !== false ) {

            $explode_url = !empty( $url ) ? explode('/',$url ) : '';

            $url = !empty( $explode_url ) ? end( $explode_url ) : '';

            $yelp_api_key = geodir_get_option('si_yelp_api_key');

            $yelp_obj = new GD_Social_Importer_Yelp();

            if( !empty( $yelp_api_key ) && '' != $yelp_api_key ){

                $response = $yelp_obj->gdfi_yelp_get_v3($url);

            } else{

                $response = json_encode( __('Invalid Yelp API Key.','gd-social-importer') );

            }

        } elseif ( strpos($url, 'www.tripadvisor.') !== false ) {

            $enable_ta_scrapper = geodir_get_option('si_enable_ta_scrapper','');

            if( !empty( $enable_ta_scrapper ) && 1 == $enable_ta_scrapper ) {

                $tripadvisor_obj = new GD_Social_Importer_Tripadvisor();

                $response = $tripadvisor_obj->get_tripadvisor_reponse( $url );

            }

        } else{

            $response = json_encode( array('error_msg'=>  __( 'Please enter a correct facebook page/event url, Yelp url or Tripadvisor Url & try again', 'gd-social-importer' ) ) );

        }

	    return $response;

	}

	public function localize_core_params( $params ) {
		if ( geodir_is_page( 'add-listing' ) && ! geodir_is_page( 'edit-listing' ) && $this->is_import_gmb_active() ) {
			$params['gmb_auth_url'] = geodir_social_gmb_auth_url();
			$params['textGMBAccounts'] = esc_attr( __( 'Searching for the business accounts...', 'gd-social-importer' ) );
			$params['textGMBLocations'] = esc_attr( __( 'Searching for the business locations...', 'gd-social-importer' ) );
		}

		return $params;
	}

	public function gmb_get_user_token_id() {
		$user_id = get_current_user_id();
		$extension_token_id = get_user_meta( $user_id, 'gmb_extension_token_id', true);

		if( !$extension_token_id ) {
			$current_time = current_time('timestamp');
			$random_string = wp_generate_password(7, false);
			$extension_token_id = $user_id . $random_string . $current_time;
			
			update_user_meta($user_id, 'gmb_extension_token_id', $extension_token_id);
		}

		return $extension_token_id;
	}

	public function gmb_authorize_user() {
		check_ajax_referer( 'gmb_authorize', 'security' );

		$auth_code = ! empty( $_POST['gmb_code'] ) ? sanitize_text_field( $_POST['gmb_code'] ) : '';

		$success = false;

		if ( empty( $auth_code ) ) {
			$result = __( 'Invalid request.', 'gd-social-importer' );
		} else {
			$response = wp_remote_post( geodir_social_gmb_token_url( $auth_code ), array( 'timeout' => 15 ) );

			$result = __( 'Something went wrong while authorization.','gd-social-importer' );

			if ( ! is_wp_error( $response ) ) {
				if ( 200 == wp_remote_retrieve_response_code( $response ) ) {
					$_response = json_decode( wp_remote_retrieve_body( $response ), true );

					if ( ! empty( $_response['access_token'] ) ) {
						$success = true;
						$result = $_response;
					} else {
						$result = __( 'Access token not found.','gd-social-importer' );
					}
				} elseif ( ! empty( $response['response']['code'] ) ) {
					$_response = json_decode( wp_remote_retrieve_body( $response ), true );

					if ( isset( $_response['error'] ) ) {
						$result = $_response['error'];
						if ( ! empty( $_response['error']['code'] ) ) {
							$result .= '[' . $_response['error']['code'] . ']';
						}
						$result .= ': ' . $_response['error_description'];
					}
				}
			} else {
				$result = $response->get_error_message();
			}
		}

		if ( $success ) {
			wp_send_json_success( $result );
		} else {
			wp_send_json_error( array( 'message' => $result ) );
		}
	}

	public function gmb_revoke_user() {
		check_ajax_referer( 'gmb_revoke', 'security' );

		$access_token = ! empty( $_POST['access_token'] ) ? sanitize_text_field( $_POST['access_token'] ) : '';

		if ( $access_token ) {
			wp_remote_post( geodir_social_gmb_revoke_url( $access_token ), array( 'timeout' => 15 ) );
		}

		wp_send_json_success( array() );
	}

	public function gmb_get_accounts() {
		check_ajax_referer( 'gmb_accounts', 'security' );

		$access_token = ! empty( $_POST['access_token'] ) ? sanitize_text_field( $_POST['access_token'] ) : '';

		$success = false;

		if ( is_wp_error( $access_token ) ) {
			$result = $access_token->get_error_message();
		} else {
			$response = wp_remote_get( 'https://mybusinessaccountmanagement.googleapis.com/v1/accounts', array(
				'headers' => array(
					'Authorization' => 'Bearer '. $access_token,
				),
				'timeout' => 15
			) );

			$result = __( 'Something went wrong while retrieving the business accounts.','gd-social-importer' );

			if ( ! is_wp_error( $response ) ) {
				if ( 200 == wp_remote_retrieve_response_code( $response ) ) {
					$_response = json_decode( wp_remote_retrieve_body( $response ), true );

					if ( ! empty( $_response['accounts'] ) ) {
						$success = true;

						$_accounts = array( '' => __( 'Select Account', 'gd-social-importer' ) );
						foreach ( $_response['accounts'] as $account ) {
							$_accounts[ $account['name'] ] = esc_html( $account['accountName'] );
						}
						$description = __( 'Select the business account to retrieve locations.', 'gd-social-importer' );

						if ( geodir_design_style() ) {
							$result = aui()->select( array(
								'id' => 'gmb_account',
								'name' => '',
								'title' => esc_html( __( 'Select Account', 'gd-social-importer' ) ),
								'placeholder' => '',
								'value' => '',
								'label_show' => false,
								'label' => '',
								'options' => $_accounts,
								'select2' => true,
								'multiple' => false,
								'data-allow-clear' => false,
								'no_wrap' => true
							) );

							$result .= '<small class="form-text d-block text-muted">' .  $description . '</small>';
						} else {
							$result = '<select id="gmb_account" class="geodir-select">';
							foreach ( $_accounts as $value => $label ) {
								$result .= '<option value="' . esc_attr( $value ) . '">' . $label . '</option>';
							}
							$result .= '</select><small class="geodir_message_note">' .  $description . '</small>';
						}
					} else {
						$result = __( 'No business accounts found.', 'gd-social-importer' );
					}
				} elseif ( ! empty( $response['response']['code'] ) ) {
					$_response = json_decode( wp_remote_retrieve_body( $response ), true );

					if ( ! empty( $_response['error']['code'] ) ) {
						$result = "[" . $_response['error']['code'] . "] " . $_response['error']['message'];
					}
				}
			} else {
				$result = $response->get_error_message();
			}
		}

		if ( $success ) {
			wp_send_json_success( array( 'input' => $result ) );
		} else {
			wp_send_json_error( array( 'message' => $result ) );
		}
	}

	public function gmb_get_locations() {
		global $aui_bs5;

		check_ajax_referer( 'gmb_locations', 'security' );

		$account = ! empty( $_POST['account'] ) ? sanitize_text_field( $_POST['account'] ) : '';
		$access_token = ! empty( $_POST['access_token'] ) ? sanitize_text_field( $_POST['access_token'] ) : '';

		$success = false;

		if ( empty( $account ) ) {
			$result = __( 'Select business account to get locations.','gd-social-importer' );
		} else {
			$response = wp_remote_get( 'https://mybusinessbusinessinformation.googleapis.com/v1/' . $account . '/locations/?readMask=name,title,storefrontAddress', array(
				'headers' => array(
					'Authorization' => 'Bearer '. $access_token,
				),
				'timeout' => 15
			) );

			$result = __( 'Something went wrong while retrieving the business account locations.','gd-social-importer' );

			if ( ! is_wp_error( $response ) ) {
				if ( 200 == wp_remote_retrieve_response_code( $response ) ) {
					$_response = json_decode( wp_remote_retrieve_body( $response ), true );

					if ( ! empty( $_response['locations'] ) ) {
						$success = true;

						$locations = geodir_social_gmb_parse_locations( $_response['locations'] );

						$description = __( 'Select the business location to import.', 'gd-social-importer' );

						if ( geodir_design_style() ) {
							$result = '<div class="input-group">';
							$result .= aui()->select( array(
								'id' => 'gmb_location',
								'name' => '',
								'title' => esc_html( __( 'Select Location','gd-social-importer' ) ),
								'placeholder' => '',
								'value' => '',
								'label_show' => false,
								'label' => '',
								'options' => array_merge( array( '' => __( 'Select Location','gd-social-importer' ) ), $locations ),
								'select2' => true,
								'multiple' => false,
								'data-allow-clear' => false,
								'no_wrap' => true
							) );
							if ( ! $aui_bs5 ) {
								$result .= '<div class="input-group-append">';
							}
							$result .= aui()->button(
								array(
									'type' => 'badge',
									'id' => 'geodir_gmb_import',
									'class' => 'btn btn-secondary py-1',
									'content' => __( 'Import', 'gd-social-importer' ),
									'extra_attributes' => array(
										'data-access_token' => $access_token,
									),
								)
							);
							if ( ! $aui_bs5 ) {
								$result .= '</div>';
							}
							$result .= '</div><small class="form-text d-block text-muted">' . $description . '</small>';
							$result = str_replace( "width:100%;", "", $result );
						} else {
							$result = '<select id="gmb_location" class="geodir-select" style="width:55%">';
							$result .= '<option value="">' . __( 'Select Location','gd-social-importer' ) . '</option>';
							foreach ( $locations as $value => $label ) {
								$result .= '<option value="' . esc_attr( $value ) . '">' . esc_html( $label ) . '</option>';
							}
							$result .= '</select>&nbsp;&nbsp;<a id="geodir_gmb_import" class="geodir_button button" href="javascript:void(0)" data-access_token="' . esc_attr( $access_token ) . '">' . __( 'Import', 'gd-social-importer' ) . '</a>';
							$result .= '<small class="geodir_message_note">' .  $description . '</small>';
						}
					} else {
						$result = __( 'No business locations found.','gd-social-importer' );
					}
				} elseif ( ! empty( $response['response']['code'] ) ) {
					$_response = json_decode( wp_remote_retrieve_body( $response ), true );

					if ( ! empty( $_response['error']['code'] ) ) {
						$result = "[" . $_response['error']['code'] . "] " . $_response['error']['message'];
					}
				}
			} else {
				$result = $response->get_error_message();
			}
		}

		if ( $success ) {
			wp_send_json_success( array( 'input' => $result ) );
		} else {
			wp_send_json_error( array( 'message' => $result ) );
		}
	}

	public function gmb_import_location() {
		check_ajax_referer( 'gmb_locations', 'security' );

		$account = ! empty( $_POST['account'] ) ? sanitize_text_field( $_POST['account'] ) : '';
		$location = ! empty( $_POST['location'] ) ? sanitize_text_field( $_POST['location'] ) : '';
		$access_token = ! empty( $_POST['access_token'] ) ? sanitize_text_field( $_POST['access_token'] ) : '';

		$success = false;

		if ( empty( $account ) || empty( $location ) ) {
			$result = __( 'Select business account to get locations.','gd-social-importer' );
		} else {
			$location = geodir_social_gmb_get_location( $location, $access_token );

			if ( is_wp_error( $location ) ) {
				$result = $location->get_error_message();
			} else {
				wp_send_json_success( $this->gmb_prepare_import( $account, $location, $access_token ) );
			}
		}

		wp_send_json_error( array( 'message' => $result ) );
	}

	public function gmb_prepare_import( $account, $location, $access_token ) {
		global $geodirectory, $gd_post;

		$post_id = ! empty( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
		$gd_post = geodir_get_post_info( $post_id );

		$social_importer_general = new Social_Importer_General();
		$default_location = $geodirectory->location->get_default_location();
		$default_city = ! empty( $default_location->city ) ? $default_location->city : '';

		$address = ! empty( $location['storefrontAddress'] ) ? $location['storefrontAddress'] : array();
		$phoneNumbers = ! empty( $location['phoneNumbers'] ) ? $location['phoneNumbers'] : array();
		$business_hours = ! empty( $location['regularHours']['periods'] ) ? $this->gmb_business_hours( $location['regularHours']['periods'] ) : '';
		$business_hidden_hours = ! empty( $business_hours ) ? $social_importer_general->get_business_hours_hidden_time( $business_hours ) : '';
		$media = $this->gmb_parse_media( $account, $location['name'], $access_token );

		$data = array();
		$data['title'] = stripslashes( $location['title'] );
		$data['description'] = ! empty( $location['profile']['description'] ) ? stripslashes( $location['profile']['description'] ) : '';
		$data['phone'] = ! empty( $phoneNumbers['primaryPhone'] ) ? stripslashes( $phoneNumbers['primaryPhone'] ) : ( ! empty( $phoneNumbers['additionalPhones'] ) && is_array( $phoneNumbers['additionalPhones'] ) ? stripslashes( $phoneNumbers['additionalPhones'][0] ) : '' );
		$data['website'] = ! empty( $location['websiteUri'] ) ? stripslashes( $location['websiteUri'] ) : '';
		$data['email'] = ! empty( $location['email'] ) ? stripslashes( $location['email'] ) : '';
		$data['street'] = ! empty( $address['addressLines'] ) ? stripslashes( implode( ", ", $address['addressLines'] ) ) : '';
		$data['country_code'] = ! empty( $address['regionCode'] ) ? stripslashes( $address['regionCode'] ) : '';
		$data['region'] = ! empty( $address['administrativeArea'] ) ? stripslashes( $address['administrativeArea'] ) : '';
		$data['city'] = ! empty( $address['locality'] ) ? stripslashes( $address['locality'] ) : '';
		$data['zip'] = ! empty( $address['postalCode'] ) ? stripslashes( $address['postalCode'] ) : '';
		$data['latitude'] = ! empty( $location['latlng']['latitude'] ) ? stripslashes( $location['latlng']['latitude'] ) : '';
		$data['longitude'] = ! empty( $location['latlng']['longitude'] ) ? stripslashes( $location['latlng']['longitude'] ) : '';
		$data['business_hours'] = $business_hours;
		$data['business_hidden_hours'] = $business_hidden_hours;
		$data['post_images'] = ! empty( $media['post_images'] ) ? implode( "::", $media['post_images'] ) : '';
		$data['images_count'] = ! empty( $media['post_images'] ) ? count( $media['post_images'] ) : 0;
		$data['logo'] = ! empty( $media['logo'] ) ? $media['logo'] : '';

		$data['default_city'] = ( ! empty( $data['city'] ) &&  strpos( strtolower( $data['city'] ), strtolower( $default_city ) ) !== false ) ? true : false;

		return $data;
	}

	public function gmb_business_hours( $regularHours ) {
		$weeks = array( 'Mo' => 'MONDAY', 'Tu' => 'TUESDAY', 'We' => 'WEDNESDAY', 'Th' => 'THURSDAY', 'Fr' => 'FRIDAY', 'Sa' => 'SATURDAY', 'Su' => 'SUNDAY' );

		$social_importer_general = new Social_Importer_General();

		$business_hours = array();

		$time_format = geodir_bh_input_time_format();

		foreach ( $weeks as $week_day => $week_name ) {
			$times = array();

			foreach ( $regularHours as $item ) {
				if ( ! empty( $item['openDay'] ) && $item['openDay'] == $week_name ) {
					$openTime = ! empty( $item['openTime']['hours'] ) ? $item['openTime']['hours'] : '00';
					$openTime .= ! empty( $item['openTime']['minutes'] ) ? ':' . $item['openTime']['minutes'] : ':00';
					$closeTime = ! empty( $item['closeTime']['hours'] ) ? $item['closeTime']['hours'] : '00';
					$closeTime .= ! empty( $item['closeTime']['minutes'] ) ? ':' . $item['closeTime']['minutes'] : ':00';
					$open = $social_importer_general->convert_time_in_24h_format( $openTime );
					$close = $social_importer_general->convert_time_in_24h_format( $closeTime );
					$open_display = $open ? date_i18n( $time_format, strtotime( $open ) ) : $open;
					$close_display = $close ? date_i18n( $time_format, strtotime( $close ) ) : $close;
					$times[] = array(
						'open' => $open,
						'close' => $close,
						'open_display' => $open_display,
						'close_display' => $close_display,
					);
				}
			}

			$business_hours[ $week_day ] = $times;
		}

		return $business_hours;
	}

	public function gmb_parse_media( $account, $location, $access_token ) {
		global $gd_post;

		$media = array(
			'post_images' => array(),
			'logo' => ''
		);
		$media_items = geodir_social_gmb_get_media( $account, $location, $access_token );

		if ( ! is_wp_error( $media_items ) && ! empty( $media_items['mediaItems'] ) && ! empty( $gd_post ) ) {
			$images = array();
			$logo = array();

			foreach ( $media_items['mediaItems'] as $k => $item ) {
				if ( ! empty( $item['googleUrl'] ) && ! empty( $item['mediaFormat'] ) && $item['mediaFormat'] == 'PHOTO' ) {
					$category = geodir_strtolower( preg_replace( '/[_-]/', ' ', $item['locationAssociation']['category'] ) );
					if ( $category == 'logo' ) {
						$logo[ $category . ' ' . ( $k + 1 ) ] = $item['googleUrl'];
					} else {
						$images[ $category . ' ' . ( $k + 1 ) ] = $item['googleUrl'];
					}
				}
			}

			$geodir_media = new GeoDir_Media();
			$uploads = wp_upload_dir();
			$post_id = absint( $gd_post->ID );
			$counter = 0;
			$cf = geodir_get_field_infoby( 'htmlvar_name', 'post_images', $gd_post->post_type );

			// Images limit
			$image_limit = apply_filters( "geodir_custom_field_file_limit", 0, $cf, $gd_post );

			if ( ! empty( $images ) ) {
				foreach ( $images as $name => $url ) {
					$image_path = $uploads['basedir'] . '/geodir_temp/' . sanitize_file_name( geodir_sanitize_keyword( $name ) ) . '-' . $post_id . '.jpg';

					if ( $response = wp_remote_get( $url, array( 'timeout' => 300, 'stream' => true, 'filename' => $image_path ) ) ) {
						if ( ! empty( $response ) && ! is_wp_error( $response ) && 200 == wp_remote_retrieve_response_code( $response ) ) {
							$image_url = str_replace( $uploads['basedir'], $uploads['baseurl'], $image_path );
							$names = explode( " ", $name );
							array_pop( $names );
							$filename = implode( " ", $names );

							$filename = apply_filters( 'geodir_social_post_image_title', $filename, array( 'post_type' => $gd_post->post_type, 'site' => 'gmb', 'image_name' => $filename, 'post_id' => $post_id, 'order' => $counter, 'image_url' => $url, 'image_type' => 'post_images' ) );

							$attachment = $geodir_media::insert_attachment( $post_id, 'post_images', $image_url, $filename, '', -1, 0, 0 );

							if ( ! is_wp_error( $attachment ) && ! empty( $attachment['file'] ) ) {
								$media['post_images'][] = $uploads['baseurl'] . $attachment['file'] . '|' . $attachment['ID'] . '|' . $filename;
							} elseif ( is_wp_error( $attachment ) ) {
								geodir_error_log( $attachment->get_error_message(), 'insert_attachment', __FILE__, __LINE__ );
							}
							$counter++;
						}
					}

					if ( $image_limit > 0 && $counter >= $image_limit ) {
						break;
					}
				}
			}

			if ( ! empty( $logo ) ) {
				foreach ( $logo as $name => $url ) {
					$image_path = $uploads['basedir'] . '/geodir_temp/' . sanitize_file_name( geodir_sanitize_keyword( $name ) ) . '-' . $post_id . '.jpg';

					if ( $response = wp_remote_get( $url, array( 'timeout' => 300, 'stream' => true, 'filename' => $image_path ) ) ) {
						if ( ! empty( $response ) && ! is_wp_error( $response ) && 200 == wp_remote_retrieve_response_code( $response ) ) {
							$image_url = str_replace( $uploads['basedir'], $uploads['baseurl'], $image_path );
							$names = explode( " ", $name );
							array_pop( $names );
							$filename = implode( " ", $names );

							$filename = apply_filters( 'geodir_social_post_image_title', $filename, array( 'post_type' => $gd_post->post_type, 'site' => 'gmb', 'image_name' => $filename, 'post_id' => $post_id, 'order' => 0, 'image_url' => $url, 'image_type' => 'logo' ) );

							$attachment = $geodir_media::insert_attachment( $post_id, 'logo', $image_url, $filename, '', -1, 0, 0 );

							if ( ! is_wp_error( $attachment ) && ! empty( $attachment['file'] ) ) {
								$media['logo'] = $uploads['baseurl'] . $attachment['file'] . '|' . $attachment['ID'] . '|' . $filename;
								break;
							} elseif ( is_wp_error( $attachment ) ) {
								geodir_error_log( $attachment->get_error_message(), 'insert_attachment', __FILE__, __LINE__ );
							}
						}
					}
				}
			}
		}

		return $media;
	}

	public function is_import_gmb_active( $post_type = '' ) {
		if ( empty( $post_type ) && ! empty( $_REQUEST['listing_type'] ) ) {
			$post_type = sanitize_text_field( $_REQUEST['listing_type'] );
		}

		$post_types = (array) geodir_get_option( 'si_gmb_cpt_to_import' );

		if ( ! empty( $post_type ) && in_array( $post_type, $post_types ) ) {
			return true;
		}

		return false;
	}
}