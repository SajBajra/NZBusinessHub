<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @since 2.0.0
 * @package    GD_Google_Maps
 * @subpackage GD_Google_Maps/admin
 *
 * Class GD_Google_Maps_Admin
 */

class GD_Google_Maps_Admin extends GeoDir_Settings_Page {

	public $id;

	public $title;

	public $page;

	/**
	 * Constructor.
	 *
	 * @since 2.0.0
	 *
	 * GD_Google_Maps_Admin constructor.
	 */
	public function __construct() {

		$this->id = 'custom_google_maps';
		$this->title = 'Maps Styles';
		$this->page = ! empty( $_REQUEST['page'] ) ? esc_attr( $_REQUEST['page'] ) : '';

		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_styles_and_scripts' ) );
		add_filter( 'geodir_settings_tabs_array', array( $this, 'add_settings_page' ),24,1 );
		add_action( 'geodir_sections_' . $this->id, array( $this, 'output_sections' ) );
		add_action( 'geodir_settings_' . $this->id, array( $this, 'output' ) );
		add_action( 'geodir_settings_save_' . $this->id, array( $this, 'save' ) );
		add_filter( 'geodir_get_settings_'.$this->id , array( $this, 'set_fields_value' ),10,1 );
		add_action('wp_ajax_gd_add_new_custom_styles', array( $this,'add_new_custom_styles_action') );
		add_action('wp_ajax_nopriv_gd_add_new_custom_styles', array( $this,'add_new_custom_styles_action') );
		add_action('wp_ajax_gd_remove_custom_styles', array( $this,'remove_custom_styles_action') );
		add_action('wp_ajax_nopriv_gd_remove_custom_styles', array( $this,'remove_custom_styles_action') );
		add_action('wp_ajax_gd_import_custom_style', array( $this,'import_custom_style_action') );
		add_action('wp_ajax_nopriv_gd_import_custom_style', array( $this,'import_custom_style_action') );
		add_action('wp_ajax_preview_map_styles', array( $this,'preview_map_styles_action') );
		add_action('wp_ajax_nopriv_preview_map_styles', array( $this,'preview_map_styles_action') );
		add_action('wp_ajax_gd_save_osm_layers', array( $this,'gd_save_osm_layers_action') );
		add_action('wp_ajax_nopriv_gd_save_osm_layers', array( $this,'gd_save_osm_layers_action') );
		add_action('wp_ajax_gd_preview_osm_layers', array( $this,'gd_preview_osm_layers_action') );
		add_action('wp_ajax_nopriv_gd_preview_osm_layers', array( $this,'gd_preview_osm_layers_action') );
		add_action('wp_head', array( $this,'gd_set_gd_map'),10);
		add_action('admin_head', array( $this,'gd_set_gd_map'),10);
		add_filter( 'geodir_map_name', array( $this,'set_map_name' ), 20, 1 );
		add_filter( 'geodir_load_gomap_script', array( $this,'load_gomap_script' ), 20, 1 );
	}

	/**
	 * Set gd current map name.
	 *
	 * @since 2.0.0
	 */
	public function gd_set_gd_map() {
		?><script type="text/javascript">window.gdSetMap = window.gdSetMap || '<?php echo GeoDir_Maps::active_map(); ?>';</script><?php
	}

	/**
	 * Register and enqueue duplicate alert styles and scripts.
	 *
	 * @since 2.0.0
	 */
	public function enqueue_styles_and_scripts() {
		$screen = get_current_screen();
		$screen_id = $screen ? $screen->id : '';

		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		$map_lang = "&language=" . get_gd_cgm_map_language();
		$map_key = get_gd_cgm_map_api_key();
		$map_extra = apply_filters('geodir_googlemap_script_extra', '');

		$current_page = !empty( $_GET['page'] ) ? esc_attr($_GET['page']) :'';
		$current_tab = !empty( $_GET['tab'] ) ? esc_attr($_GET['tab']) :'';

		if ( ! empty( $current_page ) && 'gd-settings' === $current_page && ! empty( $current_tab ) && 'custom_google_maps' === $current_tab ) {
			wp_register_style( GD_GOOGLE_MAPS_TEXTDOMAIN, GD_GOOGLE_MAPS_PLUGIN_URL . 'assets/css/style.css', array( 'geodir-leaflet-style', 'wp-color-picker' ), GD_GOOGLE_MAPS_VERSION, 'all' );
			wp_enqueue_style( GD_GOOGLE_MAPS_TEXTDOMAIN );

			wp_register_script( GD_GOOGLE_MAPS_TEXTDOMAIN, GD_GOOGLE_MAPS_PLUGIN_URL . 'assets/js/admin' . $suffix . '.js', array('jquery','jquery-ui-accordion','wp-color-picker'), GD_GOOGLE_MAPS_VERSION, true );
			wp_enqueue_script( GD_GOOGLE_MAPS_TEXTDOMAIN );
		}

		if ( GeoDir_Maps::active_map() == 'osm' && ( ( $screen_id && geodir_is_gd_post_type( $screen_id ) ) || ( ! empty( $_GET['section'] ) && $_GET['section'] == 'manage-map-style' ) ) ) {
			wp_register_script( 'geodir-leaflet-providers', GD_GOOGLE_MAPS_PLUGIN_URL . 'assets/js/leaflet-providers' . $suffix . '.js', array( 'geodir-leaflet-script' ), GD_GOOGLE_MAPS_VERSION, true );
			wp_enqueue_script( 'geodir-leaflet-providers' );
		}
	}

	/**
	 * Add GD Custom google map settings tab.
	 *
	 * Add setting tab in GeoDirectory setting page.
	 *
	 * @since 2.0.0
	 *
	 * @param array $pages GD settings page tab page array.
	 * @return array $pages.
	 */
	public function add_settings_page($pages) {

		$current_page = $this->page;

		if( 'gd-settings' === $current_page ) {

			$pages[ $this->id ] = $this->title;

		}

		return $pages;
	}

	/**
	 * Get Custom google map sub menu tab option.
	 *
	 * @since 2.0.0
	 *
	 * @return array $sections
	 */
	public function get_sections(  ) {

		$get_selected_option_count = $this->get_option_count();

		$sections = array();

		$sections[''] = __( 'General', 'geodir-custom-google-maps' );

		if( !empty( $get_selected_option_count ) && $get_selected_option_count > 0 ) {

			$sections['manage-map-style'] = __('Manage Map Styles', 'geodir-custom-google-maps');

		}

		return apply_filters( 'geodir_get_sections_' . $this->id, $sections );
	}

	/**
	 * Display sub menu in Custom google map section.
	 *
	 * @since 2.0.0
	 */
	public function output_sections() {

		global $aui_bs5, $current_section;

		$sections = $this->get_sections();

		if ( empty( $sections ) || 0 === sizeof( $sections ) ) {
			return;
		}

		$output = '<ul class="subsubsub m-0 p-0	">';

		$array_keys = array_keys( $sections );

		foreach ( $sections as $id => $label ) {
			$output .= '<li><a href="' . admin_url( 'admin.php?page=gd-settings&tab=' . $this->id . '&section=' . sanitize_title( $id ) ) . '" class="' . ( $current_section == $id ? 'current' : '' ) . '">' . $label . '</a> ' . ( end( $array_keys ) == $id ? '' : '|' ) . ' </li>';
		}

		$output .= '</ul>';

		if(!empty($_REQUEST['section']) && $_REQUEST['section']=='manage-map-style'){
			$map_view = !empty($_REQUEST['map_type']) && $_REQUEST['map_type'] == 'osm' ? 'osm' : 'google';
			$g_active = $map_view=='google' ? 'active' : '';
			$osm_active = $map_view=='osm' ? 'active' : '';
			$output .='<div class="list-group list-group-horizontal-sm ' . ( $aui_bs5 ? 'ms-auto' : 'ml-auto' ) . '">
							<a href="'.admin_url( 'admin.php?page=gd-settings&tab=' . $this->id . '&section=manage-map-style&map_type=google' ).'" class="list-group-item py-2 '. $g_active.' custom-google-map-btn" data-val="google_map">'.__( 'Google Map','geodir-custom-google-maps' ).'</a>
							<a href="'.admin_url( 'admin.php?page=gd-settings&tab=' . $this->id . '&section=manage-map-style&map_type=osm' ).'" class="list-group-item py-2 '. $osm_active .'  custom-google-map-btn custom-osm-button" data-val="open_street_map">'. __( 'OpenStreetMap','geodir-custom-google-maps' ).'</a>
					   </div> ';

	//            $output .= '<button class="btn btn-sm btn-primary ml-auto gd-advanced-toggle gd-advanced-btn " type="button"><span class="gdat-text-show">Show Advanced</span><span class="gdat-text-hide">Hide Advanced</span></button>';

		}else{
			ob_start();

			$this->output_toggle_advanced();

			$output .= ob_get_clean();
		}

		if ( $output ) {
			echo "<div class='clearfix d-flex align-content-center flex-wrap'>";
			echo $output;
			echo "</div><div class=\"clear\"></div>";
		}
	}

	/**
	 * Display Custom google map general tab output.
	 *
	 * @since 2.0.0
	 */
	public function output() {
		global $current_section, $hide_save_button;

		$settings = $this->get_settings( $current_section );

		GeoDir_Admin_Settings::output_fields( $settings );
	}

	/**
	 * Save custom google map general settings.
	 *
	 * @since 2.0.0
	 */
	public function save() {
		$get_current_tab = !empty( $_REQUEST['tab'] ) ? $_REQUEST['tab'] :'';

		if( 'custom_google_maps' === $get_current_tab ) {
			$get_current_section = !empty( $_REQUEST['section'] ) ? $_REQUEST['section'] :'';
			$sanitize_fields = $this->sanitize_fields( $_POST ,$get_current_section );

			if( 'manage-map-style' === $get_current_section ) {
			} else {
				$gd_custom_google_maps_settings = geodir_get_option('custom_google_maps', array());

				if( empty( $gd_custom_google_maps_settings ) ) {
					$gd_custom_google_maps_settings = $sanitize_fields;
				} else{
					$gd_custom_google_maps_settings = array_merge($gd_custom_google_maps_settings,$sanitize_fields);
				}

				geodir_update_option( "custom_google_maps", $gd_custom_google_maps_settings );
			}
		}
	}

	/**
	 * Set custom google map values in general tab.
	 *
	 * @since 2.0.0
	 *
	 * @param array $settings
	 *
	 * @return array $settings
	 */
	public function set_fields_value( $settings ) {
		$gd_custom_google_maps_settings = geodir_get_option('custom_google_maps', array());

		if( isset( $gd_custom_google_maps_settings ) ) {
			foreach($settings as $key => $setting){
				if( isset( $setting['id'] ) ) {
					$settings[$key]['default'] =  !empty( $gd_custom_google_maps_settings[$setting['id']] ) ? $gd_custom_google_maps_settings[$setting['id']] :'' ;
				}
			}
		}

		return $settings;
	}

	/**
	 * Sanitize post fields values.
	 *
	 * @since 2.0.0
	 *
	 * @param array $post Post array.
	 * @param string $get_current_section Current menu section.
	 * @return array $output
	 */
	public function sanitize_fields( $post, $get_current_section ) {
		$output = array();

		if ( empty( $post ) ) {
			return $output;
		}

		if ( 'manage-map-style' === $get_current_section ) {

		} else {
			$output['gd_custom_map_home_checkbox'] = ! empty( $post['gd_custom_map_home_checkbox'] ) ? absint( $post['gd_custom_map_home_checkbox'] ) : '';
			$output['gd_custom_map_listing_checkbox'] = ! empty( $post['gd_custom_map_listing_checkbox'] ) ? absint( $post['gd_custom_map_listing_checkbox'] ) : '';
			$output['gd_custom_map_detail_checkbox'] = ! empty( $post['gd_custom_map_detail_checkbox'] ) ? absint( $post['gd_custom_map_detail_checkbox'] ) : '';
			$output['gd_custom_map_add_listing_checkbox'] = ! empty( $post['gd_custom_map_add_listing_checkbox'] ) ? absint( $post['gd_custom_map_add_listing_checkbox'] ) : '';
			$output['custom_map_osm_api_key'] = ! empty( $post['custom_map_osm_api_key'] ) ? strip_tags( sanitize_text_field( $post['custom_map_osm_api_key'] ) ) : '';
		}

		return $output;
	}

	/**
	 * Get settings options fields.
	 *
	 * @since 2.0.0
	 *
	 * @param string $current_section Current display section.
	 *
	 * @return array $settings
	 */
	public function get_settings( $current_section = '' ) {

		if ( 'manage-map-style' === $current_section ) {

			$settings = array();

			echo $this->custom_map_style_html_display();

		} else{

			$settings  = apply_filters( "gd_custom_google_map_settings", array(

				array(
					'name' => __( 'General Settings', 'geodir-custom-google-maps' ),
					'type' => 'title',
					'desc' => '',
					'id'   => 'gd_custom_google_map_settings'
				),

				array(
					'name' => __('Custom Style on Directory Map?', 'geodir-custom-google-maps'),
					'desc' => __('Enable custom style on directory map.', 'geodir-custom-google-maps'),
					'id' => 'gd_custom_map_home_checkbox',
					'type' => 'checkbox',
					'default' => 0,
				),

				array(
					'name' => __('Custom Style on Archive Map?', 'geodir-custom-google-maps'),
					'desc' => __('Enable custom style on archive page map.', 'geodir-custom-google-maps'),
					'id' => 'gd_custom_map_listing_checkbox',
					'type' => 'checkbox',
					'default' => 0,
				),

				array(
					'name' => __('Custom Style on Detail Map?', 'geodir-custom-google-maps'),
					'desc' => __('Enable custom style on detail page map.', 'geodir-custom-google-maps'),
					'id' => 'gd_custom_map_detail_checkbox',
					'type' => 'checkbox',
					'default' => 0,
				),

				array(
					'name' => __('Custom Style on Add Listing page?', 'geodir-custom-google-maps'),
					'desc' => __('Enable custom style on add listing page map.', 'geodir-custom-google-maps'),
					'id' => 'gd_custom_map_add_listing_checkbox',
					'type' => 'checkbox',
					'default' => 0,
				),

				array(
					'name' => __('OSM Layer API Key', 'geodir-custom-google-maps'),
					'desc' => __('Some OSM layers require an API key, you can enter it here.', 'geodir-custom-google-maps'),
					'id' => 'custom_map_osm_api_key',
					'type' => 'text',
					'default' => '',
					'desc_tip' => true,
					'advanced' => true,
				),


				array( 'type' => 'sectionend', 'id' => 'gd_custom_google_map_settings' ),

			));
		}

		return apply_filters( 'geodir_get_settings_' . $this->id, $settings );
	}

	/**
	 * Get custom manage style html.
	 *
	 * Display selected options in accordion sections.
	 *
	 * @since 2.0.0
	 *
	 * @return string $output
	 */
	public function custom_map_style_html_display() {
		global $aui_bs5;

		$custom_maps_settings = geodir_get_option( 'custom_google_maps', array() );
		$map_view = ! empty( $_REQUEST['map_type'] ) && $_REQUEST['map_type'] == 'osm' ? 'osm' : 'google';

		ob_start();
		?>
		<h2 class="gd-settings-title d-none"><?php echo  __( 'Manage Map Styles', 'geodir-custom-google-maps' ); ?></h2>
		<div id="custom_google_maps_accordion" class="accordion mt-4" onclick="window.dispatchEvent(new Event('resize'));">
			<?php
			if ( ! empty( $custom_maps_settings ) ) {
				$current_id = 0;

				foreach ( $custom_maps_settings as $keys => $maps_settings ) {
					$current_id++;

					if ( ! empty( $maps_settings ) && $maps_settings != '' ) {
						if ( $aui_bs5 ) {
						?>
						<div class="accordion-item">
							<h2 class="accordion-header" id="heading_<?php echo esc_attr( $current_id ); ?>">
							  <button class="accordion-button<?php echo ( $current_id == 1 ? '' : ' collapsed' ); ?>" type="button" data-bs-toggle="collapse" data-bs-target="#collapse_<?php echo esc_attr( $current_id ); ?>" aria-expanded="<?php echo ( $current_id == 1 ? 'true' : 'false' ); ?>" aria-controls="collapse_<?php echo esc_attr( $current_id ); ?>"><?php echo $this->get_map_style_title_by_id( $keys ); ?></button>
							</h2>
							<div id="collapse_<?php echo esc_attr( $current_id ); ?>" class="accordion-collapse collapse<?php echo ( $current_id == 1 ? ' show' : '' ); ?>" aria-labelledby="heading_<?php echo esc_attr( $current_id ); ?>" data-bs-parent="#custom_google_maps_accordion">
							  <div class="accordion-body">
								<?php if ( $map_view == 'google' ) { ?>
									<div id="google_map_section_<?php echo esc_attr( $current_id ); ?>" class="google-map-section"><?php echo $this->custom_google_map_html( $current_id, $keys ); ?></div>
									<?php } else { ?>
									<div id="google_open_street_section_<?php echo esc_attr( $current_id ); ?>" class="open-street-sectionz"><?php echo $this->custom_open_street_map_html( $current_id, $keys ); ?></div>
								<?php } ?>
							  </div>
							</div>
						</div>
						<?php
						} else {
						?>
						<div class="card mw-100 p-0 mt-0">
							<div class="card-header" id="heading_<?php echo esc_attr( $current_id ); ?>">
								<h2 class="mb-0">
									<button class="btn btn-link btn-block text-left" type="button" data-toggle="collapse" data-target="#collapse_<?php echo esc_attr( $current_id ); ?>" aria-expanded="<?php if($current_id==1){echo 'true';}else{echo 'false';} ?>" aria-controls="collapse_<?php echo esc_attr( $current_id ); ?>">
										<?php echo $this->get_map_style_title_by_id($keys); ?>
									</button>
								</h2>
							</div>

							<div id="collapse_<?php echo esc_attr( $current_id ); ?>" class="collapse <?php if($current_id==1){echo 'show';} ?>" aria-labelledby="heading_<?php echo esc_attr( $current_id ); ?>" data-parent="#custom_google_maps_accordion">
								<div class="card-body">
									<?php if ( $map_view == 'google' ) { ?>
									<div id="google_map_section_<?php echo esc_attr( $current_id ); ?>" class="google-map-section"><?php echo $this->custom_google_map_html($current_id,$keys); ?></div>
									<?php } else { ?>
									<div id="google_open_street_section_<?php echo esc_attr( $current_id ); ?>" class="open-street-sectionz"><?php echo $this->custom_open_street_map_html($current_id,$keys); ?></div>
									<?php } ?>
								</div>
							</div>
						</div>
						<?php
						}
					}
				}
			}
			?>
		</div>
		<style type="text/css">input.geodir-save-button{display: none !important;}</style>
		<?php
		$output = ob_get_contents();
		ob_end_clean();

		return $output;
	}

	/**
	 * Get google map html in manage style accordion section.
	 *
	 * @since 2.0.0
	 *
	 * @param string $id Current id.
	 * @param string $keys current fields id.
	 * @return string $output
	 */
	public function custom_google_map_html( $id, $keys ) {
		global $aui_bs5;

		ob_start();
		?>
		<div id="custom_style_content_<?php echo esc_attr( $id ); ?>" class="map-tab-content">
			<div id="google_map_preview_<?php echo esc_attr( $id ); ?>" class="custom-google-map-preview"></div>



			<div class="nav-fill list-group list-group-horizontal-sm" id="pills-tab_<?php echo esc_attr( $id ); ?>">
					<a class="list-group-item active" data-<?php echo ( $aui_bs5 ? 'bs-' : '' ); ?>toggle="pill" role="tab" href="#gd-map-style-custom_<?php echo esc_attr( $id ); ?>" id="gd-map-style-custom-tab_<?php echo esc_attr( $id ); ?>"><i class="fas fa-map-marked-alt"></i> <?php echo __( 'Edit Styles','geodir-custom-google-maps' ); ?></a>
					<a class="list-group-item " data-<?php echo ( $aui_bs5 ? 'bs-' : '' ); ?>toggle="pill" role="tab" href="#gd-map-style-import_<?php echo esc_attr( $id ); ?>" id="gd-map-style-import-tab_<?php echo esc_attr( $id ); ?>"><i class="fas fa-upload"></i> <?php echo __( 'Import Styles','geodir-custom-google-maps' ); ?></a>
			</div>

			<div class="tab-content mt-4" id="pills-tab_<?php echo esc_attr( $id ); ?>Content">
				<div class="tab-pane fade  show active" id="gd-map-style-custom_<?php echo esc_attr( $id ); ?>" role="tabpanel" aria-labelledby="gd-map-style-custom-tab_<?php echo esc_attr( $id ); ?>">
					<div id="custom_styles_content_<?php echo esc_attr( $id ); ?>" class="custom-style-content">
						<div class="container border shadow rounded">
							<div class="row">
								<div class="row-blog half-blog col">
									<label><?php echo __( 'Feature Type:','geodir-custom-google-maps' ); ?></label>
									<select name="map_feature_type_<?php echo esc_attr( $id ); ?>" id="map_feature_type_<?php echo esc_attr( $id ); ?>">
										<?php
										$get_get_feature_types = get_gd_cgm_feature_types();
										if( !empty( $get_get_feature_types ) && $get_get_feature_types !='' ) {
											foreach ( $get_get_feature_types as $feature_value => $feature_label ) {
												?>
												<option value="<?php echo esc_attr( $feature_value ); ?>"><?php echo esc_html( $feature_label ); ?></option>
												<?php
											}
										}
										?>
									</select>
								</div>
								<div class="row-blog half-blog col">
									<label><?php echo __( 'Element Type:','geodir-custom-google-maps' ); ?></label>
									<select name="map_element_type_<?php echo esc_attr( $id ); ?>" id="map_element_type_<?php echo esc_attr( $id ); ?>">
										<?php
										$get_elements_types = get_gd_cgm_elements_types();
										if( !empty( $get_elements_types ) && $get_elements_types !='' ) {
											foreach ( $get_elements_types as $element_value => $element_label ) {
												?>
												<option value="<?php echo esc_attr( $element_value ); ?>"><?php echo esc_html( $element_label ); ?></option>
												<?php
											}
										}
										?>
									</select>
								</div>
							</div>

							<div class="row">
								<div class="row-blog col">
									<label><?php echo __( 'Color:','geodir-custom-google-maps' ); ?></label>
									<input type="text" name="map_color_<?php echo esc_attr( $id ); ?>" id="map_color_<?php echo esc_attr( $id ); ?>" class="gd-map-color-picker" value="#FF0000">
								</div>
								<div class="row-blog col">
									<label><?php echo __( 'Gamma:','geodir-custom-google-maps' ); ?></label>
									<input type="text" name="map_gamma_<?php echo esc_attr( $id ); ?>" id="map_gamma_<?php echo esc_attr( $id ); ?>" class="" value="" placeholder="1.0">
								</div>
								<div class="row-blog col">
									<label><?php echo __( 'Hue:','geodir-custom-google-maps' ); ?></label>
									<input type="text" name="map_hue_<?php echo esc_attr( $id ); ?>" id="map_hue_<?php echo esc_attr( $id ); ?>" class="gd-map-color-picker" value="#FF0000">
								</div>
								<div class="row-blog col">
									<label><?php echo __( 'Invert lightness:','geodir-custom-google-maps' ); ?></label>
									<select name="map_invert_lightness_<?php echo esc_attr( $id ); ?>" id="map_invert_lightness_<?php echo esc_attr( $id ); ?>">
										<option value="">Default</option>
										<option value="true">True</option>
									</select>
								</div>
							</div>


							<div class="row">
								<div class="row-blog col">
									<label><?php echo __( 'Lightness:','geodir-custom-google-maps' ); ?></label>
									<input type="text" name="map_lightness_<?php echo esc_attr( $id ); ?>" id="map_lightness_<?php echo esc_attr( $id ); ?>" value="" placeholder="-25">
								</div>
								<div class="row-blog col">
									<label><?php echo __( 'Saturation:','geodir-custom-google-maps' ); ?></label>
									<input type="text" name="map_saturation_<?php echo esc_attr( $id ); ?>" id="map_saturation_<?php echo esc_attr( $id ); ?>"  value="" placeholder="-100">
								</div>
								<div class="row-blog col">
									<label><?php echo __( 'Visibility:','geodir-custom-google-maps' ); ?></label>
									<select name="map_visibility_<?php echo esc_attr( $id ); ?>" id="map_visibility_<?php echo esc_attr( $id ); ?>">
										<option value="">Default</option>
										<option value="on">on</option>
										<option value="off">off</option>
										<option value="simplifed">simplifed</option>
									</select>
								</div>
								<div class="row-blog col">
									<label><?php echo __( 'Weight:','geodir-custom-google-maps' ); ?></label>
									<input type="text" name="map_weight_<?php echo esc_attr( $id ); ?>" id="map_weight_<?php echo esc_attr( $id ); ?>"  value="" placeholder="1">
								</div>
							</div>

							<div class="row-blog no-border full-blog row w-100 mw-100 m-0 <?php echo ( $aui_bs5 ? 'text-end' : 'text-right' ); ?>">
								<a target="_blank" class="btn btn-link <?php echo ( $aui_bs5 ? 'float-start text-start w-auto text-decoration-none' : 'float-left text-left' ); ?>" href="https://developers.google.com/maps/documentation/javascript/reference?csw=1#MapTypeStyler"><?php _e( 'Read more about MapTypeStyler Properties.','geodir-custom-google-maps'); ?></a>

								<input type="hidden" id="map_style_id_<?php echo esc_attr( $id ); ?>" name="map_style_id_<?php echo esc_attr( $id ); ?>" value="<?php echo esc_attr( $keys ); ?>">
								<input type="hidden" id="map_style_tab_<?php echo esc_attr( $id ); ?>" name="map_style_tab_<?php echo esc_attr( $id ); ?>" value="custom-style">
								<input type="button" class="btn btn-sm btn-primary add-new-custom-styles<?php echo ( $aui_bs5 ? ' w-auto' : '' ); ?>" value="<?php echo esc_attr( __( 'Add new styles', 'geodir-custom-google-maps' ) ); ?>" id="save_custom_styles_<?php echo esc_attr( $id ); ?>" name="save_custom_styles_<?php echo esc_attr( $id ); ?>" data-value="<?php echo esc_attr( $id ); ?>">
							</div>
						</div>

						<div id="map_style_button_<?php echo esc_attr( $id ); ?>" class="custom-style-btn mt-4">

							<input type="button" name="cusom_preview_style_btn_<?php echo esc_attr( $id ); ?>" class="btn btn-sm btn-primary map-preview-btn" data-id="<?php echo esc_attr( $id ); ?>" value="<?php echo esc_attr( __('Preview Styles','geodir-custom-google-maps') ); ?>">
							<input type="hidden" name="map_preview_id_<?php echo esc_attr( $id ); ?>" id="map_preview_id_<?php echo esc_attr( $id ); ?>" value="<?php echo esc_attr( $id ); ?>">
							<input type="hidden" name="map_preview_option_<?php echo esc_attr( $id ); ?>" id="map_preview_option_<?php echo esc_attr( $id ); ?>" value="<?php echo esc_attr( $keys ); ?>">
						</div>

						<div class="custom-map-style-data">
							<?php
							$get_option_key = get_gd_cgm_option_key( $keys );
							echo $this->get_custom_map_style_html($id, $get_option_key );
							?>
						</div>
					</div>

				</div>
				<div class="tab-pane custom-import-style-content fade" id="gd-map-style-import_<?php echo esc_attr( $id ); ?>" role="tabpanel" aria-labelledby="gd-map-style-import-tab_<?php echo esc_attr( $id ); ?>">
					<div class="row-blog">
						<p><?php echo __('Use the predefined map styles ( JavaScript Style Array ) available at ','geodir-custom-google-maps'); ?><a target="_blank" href="https://snazzymaps.com/explore"><?php echo __('snazzymaps.com/explore','geodir-custom-google-maps'); ?></a><?php echo __(' It must be an well defined javascript array format.','geodir-custom-google-maps');?></p>
					</div>
					<div class="row-blog">
						<textarea id="import_styles_content_<?php echo esc_attr( $id ); ?>" name="import_styles_content_<?php echo esc_attr( $id ); ?>" rows="10" placeholder='[{"featureType":"administrative","elementType":"labels.text.fill","stylers":[{"color":"#444444"}]},{"featureType":"landscape","elementType":"all","stylers":[{"color":"#f2f2f2"}]},{"featureType":"poi","elementType":"all","stylers":[{"visibility":"off"}]},{"featureType":"road","elementType":"all","stylers":[{"saturation":-100},{"lightness":45}]},{"featureType":"road.highway","elementType":"all","stylers":[{"visibility":"simplified"}]},{"featureType":"road.arterial","elementType":"labels.icon","stylers":[{"visibility":"off"}]},{"featureType":"transit","elementType":"all","stylers":[{"visibility":"off"}]},{"featureType":"water","elementType":"all","stylers":[{"color":"#46bcec"},{"visibility":"on"}]}]'></textarea>
						<p class="desc-tip"><?php echo __('Paste the predefined map styles ( JavaScript Style Array ) here.','geodir-custom-google-maps'); ?></p>
					</div>
					<div class="row-blog full-blog">
						<input type="hidden" id="map_import_style_id_<?php echo esc_attr( $id ); ?>" name="map_import_style_id_<?php echo esc_attr( $id ); ?>" value="<?php echo esc_attr( $keys ); ?>">
						<input type="hidden" id="map_import_style_tab_<?php echo esc_attr( $id ); ?>" name="map_import_style_tab_<?php echo esc_attr( $id ); ?>" value="import-style">
						<input type="button" class="button button-primary import-custom-styles" value="<?php esc_attr_e( 'Import new styles', 'geodir-custom-google-maps' ); ?>" id="import_custom_styles_<?php echo esc_attr( $id ); ?>" name="import_custom_styles_<?php echo esc_attr( $id ); ?>" data-value="<?php echo esc_attr( $id ); ?>">
					</div>
				</div>
			</div>
			<?php
			$get_option_key = get_gd_cgm_option_key( $keys );
			$searlized_data = get_option($get_option_key);
			$saved_option = maybe_unserialize( $searlized_data );
			$default_location   = gd_cgm_default_location();
			$latitude   		= !empty( $default_location->latitude ) ? $default_location->latitude : '-75.163786';
			$longitude  		= !empty( $default_location->longitude ) ? $default_location->longitude : '39.952484';
			?>
			<script type="text/javascript">
				jQuery(document).ready(function(){
					var get_id = 'google_map_preview_<?php echo $id;?>';
					var map_options= {
						zoom: 8,
						center:new google.maps.LatLng('<?php echo $latitude; ?>','<?php echo $longitude; ?>'),
						mapTypeId: google.maps.MapTypeId.ROADMAP,
					};
					var map=new google.maps.Map(document.getElementById(get_id),map_options);

					var mapStyles = JSON.parse('<?php echo json_encode($saved_option);?>');
					if (typeof mapStyles == 'object' && mapStyles ) {
						map.setOptions({'styles':mapStyles});
					}
				});
			</script>
		</div>
		<?php

		$output = ob_get_contents();
		ob_end_clean();

		return $output;
	}

	/**
	 * Get open street html in manage style accordion section.
	 *
	 * @since 2.0.0
	 *
	 * @param string $id Current id.
	 * @param string $keys current fields id.
	 * @return string $output
	 */
	public function custom_open_street_map_html( $id, $keys ) {
		$default_location = gd_cgm_default_location();
		$latitude = !empty( $default_location->latitude ) ? $default_location->latitude : '-75.163786';
		$longitude = !empty( $default_location->longitude ) ? $default_location->longitude : '39.952484';

		$get_osm_option_key = get_gd_cgm_osm_option_key( $keys );
		$get_osm_option_val = maybe_unserialize( get_option( $get_osm_option_key ) );

		$base_layer = ! empty( $get_osm_option_val['base_value'] ) ? $get_osm_option_val['base_value'] : 'OpenStreetMap.Mapnik';
		$overlay_layers = ! empty( $get_osm_option_val['overlay_value'] ) ? $get_osm_option_val['overlay_value'] : array();

		$_base_layers = geodir_custom_map_osm_base_layers();
		$_overlay_layers = geodir_custom_map_osm_overlay_layers();

		$_base_layers = ! empty( $_base_layers ) ? "'" . implode( "', '", $_base_layers ). "'" : '';
		$_overlay_layers = ! empty( $_overlay_layers ) ? "'" . implode( "', '", $_overlay_layers ). "'" : '';

		ob_start();
		?>
		<div id="open_street_map_content_<?php echo esc_attr( $id );?>" class="street-map-tab-content">
			<div id="gd_custom_open_street_map_<?php echo esc_attr( $id ); ?>" class="gd-open-street-map"></div>
			<div id="gd_custom_osm_fields_<?php echo esc_attr( $id ); ?>" class="gd-osm-submit-events">
				<input type="button" id="save_gd_osm_val_<?php echo esc_attr( $id );?>" data-id="<?php echo esc_attr( $id ); ?>" value="<?php esc_attr_e( 'Save Changes', 'geodir-custom-google-maps' ); ?>" class="btn btn-primary btn-sm gd-save-osm-btn">
				<!-- <input type="button" class="button button-primary gd-osm-preview" data-id="<?php echo esc_attr( $id ); ?>" name="gd_osm_preview_btn_<?php echo esc_attr( $id ); ?>" id="gd_osm_preview_btn_<?php echo esc_attr( $id ); ?>" value="Preview"> -->
				<input type="hidden" name="gd_osm_fields_key_<?php echo esc_attr( $id );?>" id="gd_osm_fields_key_<?php echo esc_attr( $id );?>" value="<?php echo esc_attr( $keys ); ?>">
				<input type="hidden" name="gd_osm_base_value_<?php echo esc_attr( $id );?>" id="gd_osm_base_value_<?php echo esc_attr( $id );?>" value="<?php echo esc_attr( $base_layer ); ?>">
				<?php 
				if( ! empty( $overlay_layers ) && count( $overlay_layers ) > 0 ) {
					foreach ( $overlay_layers as $overlay_layer ) {
						?>
						<input type="hidden" class="gd-osm-overlays" value="<?php echo esc_attr( $overlay_layer ); ?>" name="gd_osm_overlays[]">
						<?php
					}
				}
				?>
			</div>
			<script type="text/javascript">
				jQuery(function() {
					var get_map_id = 'gd_custom_open_street_map_<?php echo $id; ?>';

					var map = L.map(get_map_id, {
						center: ['<?php echo $latitude; ?>', '<?php echo $longitude; ?>'],
						zoom: 10,
					});

					var baseLayers = {},
					baseLayerNames = [<?php echo $_base_layers; ?>],
					overlayLayers = {},
					overlayLayerNames = [<?php echo $_overlay_layers; ?>];

					jQuery.each(baseLayerNames, function( i, layer ) {
						baseLayers[layer] = L.tileLayer.provider(layer);

						if ('<?php echo $base_layer; ?>' == layer) {
							baseLayers[layer].addTo(map);
						}
					});

					jQuery.each(overlayLayerNames, function( i, layer ) {
						overlayLayers[layer] = L.tileLayer.provider(layer);

						<?php if ( ! empty( $overlay_layers ) ) { foreach ( $overlay_layers as $overlay_layer ) { ?>
						if ('<?php echo $overlay_layer; ?>' == layer) {
							overlayLayers[layer].addTo(map);
						}
						<?php } } ?>
					});

					L.control.layers(baseLayers, overlayLayers, {collapsed: false}).addTo(map);

					var submit_events_id = 'gd_custom_osm_fields_<?php echo $id; ?>';
					var submit_base_layes = 'gd_osm_base_value_<?php echo $id;?>';

					map.on('baselayerchange', function(e) {
						var name = e.name;
						jQuery('#'+submit_base_layes).val(name);
					});

					map.on("overlayadd", function(e) {
						var name = e.name;
						jQuery("#"+submit_events_id).append('<input type="hidden" class="gd-osm-overlays" value="' + name + '" name="gd_osm_overlays[]" />');
					});

					map.on("overlayremove", function(e) {
						var name = e.name;
						jQuery("#"+submit_events_id).find('[value="' + name + '"]').remove();
					});

					setTimeout(function(){ map.invalidateSize(true)}, 300);
				});
			</script>
		</div>
		<?php
		$output = ob_get_clean();

		return $output;
	}

	/**
	 * Get custom map manage accordion title by fields id.
	 *
	 * @since 2.0.0
	 *
	 * @param string $id Current fields id.
	 * @return string $title
	 */
	public function get_map_style_title_by_id( $id ) {
		$title ='';

		switch ($id) {
			case "gd_custom_map_home_checkbox":
				$title = __( "Directory Map Style" ,'geodir-custom-google-maps');
				break;
			case "gd_custom_map_listing_checkbox":
				$title = __( "Archive Map Style", 'geodir-custom-google-maps' );
				break;
			case "gd_custom_map_detail_checkbox":
				$title = __( "Single Map Style", 'geodir-custom-google-maps' );
				break;
			case "gd_custom_map_add_listing_checkbox":
				$title = __( "Add Listing Map Style", 'geodir-custom-google-maps' );
				break;
			default:
				$title = "";
		}

		return $title;
	}

	/**
	 * Get general tab selected custom google map options count.
	 *
	 * @return int $count
	 */
	public function get_option_count() {
		$count = 0;

		$custom_maps_settings = geodir_get_option('custom_google_maps', array());

		if( !empty( $custom_maps_settings['gd_custom_map_home_checkbox'] ) && $custom_maps_settings['gd_custom_map_home_checkbox'] !='' ) {
			$count = $count + 1;
		}

		if( !empty( $custom_maps_settings['gd_custom_map_listing_checkbox'] ) && $custom_maps_settings['gd_custom_map_listing_checkbox'] !='' ) {
			$count = $count + 1;
		}

		if( !empty( $custom_maps_settings['gd_custom_map_detail_checkbox'] ) && $custom_maps_settings['gd_custom_map_detail_checkbox'] !='' ) {
			$count = $count + 1;
		}

		if ( ! empty( $custom_maps_settings['gd_custom_map_add_listing_checkbox'] ) && $custom_maps_settings['gd_custom_map_add_listing_checkbox'] != '' ) {
			$count = $count + 1;
		}

		return $count;
	}

	/**
	 * Add new custom style in particular accordion section.
	 *
	 * @since 2.0.0
	 */
	public function add_new_custom_styles_action() {
		check_ajax_referer( 'geodir_basic_nonce', 'security' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( -1 );
		}

		$data_id = !empty( $_POST['data_id'] ) ? strip_tags( sanitize_text_field( $_POST['data_id'] ) ) :'';
		$map_style_id = !empty( $_POST['map_style_id'] ) ? strip_tags( sanitize_text_field( $_POST['map_style_id'] ) ) :'';
		$map_feature_type = !empty( $_POST['map_feature_type'] ) ? strip_tags( sanitize_text_field( $_POST['map_feature_type'] ) ) :'';
		$map_element_type = !empty( $_POST['map_element_type'] ) ? strip_tags( sanitize_text_field( $_POST['map_element_type'] ) ) :'';
		$map_color = !empty( $_POST['map_color'] ) ? strip_tags( sanitize_text_field( $_POST['map_color'] ) ) :'';
		$map_gamma = !empty( $_POST['map_gamma'] ) ? strip_tags( sanitize_text_field( $_POST['map_gamma'] ) ) :'';
		$map_hue = !empty( $_POST['map_hue'] ) ? strip_tags( sanitize_text_field( $_POST['map_hue'] ) ) :'';
		$map_invert_lightness = !empty( $_POST['map_invert_lightness'] ) ? strip_tags( sanitize_text_field( $_POST['map_invert_lightness'] ) ) :'';
		$map_lightness = !empty( $_POST['map_lightness'] ) ? strip_tags( sanitize_text_field( $_POST['map_lightness'] ) ) :'';
		$map_saturation = !empty( $_POST['map_saturation'] ) ? strip_tags( sanitize_text_field( $_POST['map_saturation'] ) ) :'';
		$map_visibility = !empty( $_POST['map_visibility'] ) ? strip_tags( sanitize_text_field( $_POST['map_visibility'] ) ) :'';
		$map_weight = !empty( $_POST['map_weight'] ) ? strip_tags( sanitize_text_field( $_POST['map_weight'] ) ) :'';

		$new_custom_style = array(
			'featureType' => $map_feature_type,
			'elementType' => $map_element_type,
			'stylers' => array(
				array( 'color' => $map_color ),
				array( 'gamma' => $map_gamma ),
				array( 'hue' => $map_hue ),
				array( 'invert_lightness' => $map_invert_lightness ),
				array( 'lightness' => $map_lightness ),
				array( 'saturation' => $map_saturation ),
				array( 'visibility' => $map_visibility ),
				array( 'weight' => $map_weight ),
			)
		);

		$get_option_key = get_gd_cgm_option_key( $map_style_id );

		$get_map_styles = get_option( $get_option_key );

		$new_options_values = array( $new_custom_style );

		$options_values = '';

		if( !empty( $get_map_styles ) && $get_map_styles !='' ) {

			$get_map_styles = maybe_unserialize( $get_map_styles );

			$options_values = array_merge($get_map_styles,$new_options_values);

		} else{

			$options_values = $new_options_values;

		}

		$options_values = maybe_serialize($options_values);

		update_option($get_option_key,$options_values);

		$get_style_html = $this->get_custom_map_style_html($data_id ,$get_option_key );

		$output = array(
			'id' => $data_id,
			'html' => $get_style_html,
		);

		echo json_encode( $output );

		wp_die();
	}

	/**
	 * Remove selected custom style in particular accordion section.
	 *
	 * @since 2.0.0
	 */
	public function remove_custom_styles_action() {
		check_ajax_referer( 'geodir_basic_nonce', 'security' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( -1 );
		}

		$get_remove_id = !empty( $_POST['get_remove_id'] ) ? sanitize_text_field( wp_unslash( $_POST['get_remove_id'] ) ) : 0;
		$get_option_key = !empty( $_POST['get_option_key'] ) ? sanitize_text_field( wp_unslash( $_POST['get_option_key'] ) ) : '';
		$get_fields = !empty( $_POST['get_fields'] ) ? sanitize_text_field( wp_unslash( $_POST['get_fields'] ) ) : '';

		$get_map_styles_arr = maybe_unserialize( get_option( $get_option_key ));
		$get_map_styles_arr = !empty( $get_map_styles_arr ) ?  $get_map_styles_arr: '';

		unset($get_map_styles_arr[$get_remove_id] );

		$get_map_styles_arr = array_values($get_map_styles_arr);

		$get_map_styles_arr = maybe_serialize($get_map_styles_arr);
		update_option($get_option_key,$get_map_styles_arr);

		$get_style_html = $this->get_custom_map_style_html($get_fields ,$get_option_key );

		$output = array(
			'id' => $get_fields,
			'html' => $get_style_html,
		);

		echo json_encode( $output );

		wp_die();
	}

	/**
	 * Added custom or import styles html.
	 *
	 * @since 2.0.0
	 *
	 * @param string $field_id Selected section field id.
	 * @param string $option_key Selected section option key.
	 * @return string $output
	 */
	public function get_custom_map_style_html( $field_id , $option_key ) {
		ob_start();

		$get_map_styles_arr = maybe_unserialize( get_option( $option_key ));

		if ( !empty( $get_map_styles_arr ) && $get_map_styles_arr !=''  && (is_array($get_map_styles_arr) || is_object($get_map_styles_arr)) ) {
			foreach ( $get_map_styles_arr as $keys => $option_value ) {
				?>
				<div id="map_listing_<?php echo esc_attr( $keys ); ?>" class="custom-map-listing-blog w-100 border shadow-sm rounded d-flex justify-content-between">
					<p class="half-blog"><b><?php _e( 'Feature Type:', 'geodir-custom-google-maps' ); ?></b> <?php echo ! empty( $option_value['featureType'] ) ? esc_html( $option_value['featureType'] ) :''; ?></p>
					<p class="half-blog"><b><?php _e( 'Element Type:', 'geodir-custom-google-maps' ); ?></b> <?php echo ! empty( $option_value['elementType'] ) ? esc_html( $option_value['elementType'] ) :''; ?></p>
				   <?php
				   if( ! empty( $option_value['stylers'] ) && $option_value['stylers'] != '' ) {
					   foreach ( $option_value['stylers'] as $stylekey => $style_value ) {
						   $style_title = key( $style_value );
						   if($style_value[ $style_title ] ){
							   ?>
							   <p><b><?php echo ! empty( $style_title ) ? esc_html( str_replace( '_', ' ' , $style_title ) ) : ''; ?>: </b><?php echo ! empty( $style_value[ $style_title ] ) ? esc_html( $style_value[ $style_title ] ) : ''; ?> </p>
							   <?php
						   }

					   }
				   }
				   ?>
					<div id="remove_btn_option_<?php echo esc_attr( $field_id ); ?>" class="remove-btn-container  mt-2 pt-1">
						<a href="javascript:void(0);" class="remove-btn" data-id="<?php echo esc_attr( $keys ); ?>"><i class="fas fa-trash-alt"></i></a>
						<input type="hidden" name="listing_option_<?php echo esc_attr( $keys ); ?>" id="listing_option_<?php echo esc_attr( $keys ); ?>" value="<?php echo esc_attr( $option_key ); ?>">
						<input type="hidden" id="listing_fields_id_<?php echo esc_attr( $keys ); ?>" name="listing_fields_id_<?php echo esc_attr( $keys ); ?>" value="<?php echo esc_attr( $field_id ); ?>">
					</div>
				</div>
				<?php
			}
		}

		$output = ob_get_contents();
		ob_end_clean();

		return $output;
	}

	/**
	 * Import custom styles in particular accordion section.
	 *
	 * @since 2.0.0
	 */
	public function import_custom_style_action() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( -1 );
		}

		$data_id = ! empty( $_POST['data_id'] ) ? sanitize_text_field( wp_unslash( $_POST['data_id'] ) ) : '';
		$style_id = ! empty( $_POST['style_id'] ) ? sanitize_text_field( wp_unslash( $_POST['style_id'] ) ) : '';
		$import_values = ! empty( $_POST['import_value'] ) ? json_decode( trim( stripslashes( $_POST['import_value'] ) ) ) : '';

		$get_option_key = get_gd_cgm_option_key( $style_id );

		update_option( $get_option_key, '' );

		$styles = array();

		if ( ! empty( $import_values ) ) {
			foreach ( $import_values as $keys => $values ) {
				$values_array = (array)$values;

				$stylers_values = array();

				if ( !empty( $values_array['stylers'] ) ) {
				   foreach ( $values_array['stylers'] as $style_key => $style_value ) {
					   $style_value_array = (array)$style_value;

					   $stylers_keys = key( $style_value_array );

					   $stylers_values[$stylers_keys] = $style_value_array[$stylers_keys];
				   }
				}

				if ( empty( $values_array ) && empty( $stylers_values ) ) {
				   continue;
				}

				$style = array();
				if ( isset( $values_array['featureType'] ) && $values_array['featureType'] != '' ) {
				   $style['featureType'] = strip_tags( sanitize_text_field( $values_array['featureType'] ) );
				}
				if ( isset( $values_array['elementType'] ) && $values_array['elementType'] != '' ) {
				   $style['elementType'] = strip_tags( sanitize_text_field( $values_array['elementType'] ) );
				}
				$stylers = array();
				if ( isset( $stylers_values['color'] ) && $stylers_values['color'] != '' ) {
				   $stylers[] = array( 'color' => strip_tags( sanitize_text_field( $stylers_values['color'] ) ) );
				}
				if ( isset( $stylers_values['gamma'] ) && $stylers_values['gamma'] != '' ) {
				   $stylers[] = array( 'gamma' => strip_tags( sanitize_text_field( $stylers_values['gamma'] ) ) );
				}
				if ( isset( $stylers_values['hue'] ) && $stylers_values['hue'] != '' ) {
				   $stylers[] = array( 'hue' => strip_tags( sanitize_text_field( $stylers_values['hue'] ) ) );
				}
				if ( isset( $stylers_values['invert_lightness'] ) && $stylers_values['invert_lightness'] != '' ) {
				   $stylers[] = array( 'invert_lightness' => strip_tags( sanitize_text_field( $stylers_values['invert_lightness'] ) ) );
				}
				if ( isset( $stylers_values['lightness'] ) && $stylers_values['lightness'] != '' ) {
				   $stylers[] = array( 'lightness' => strip_tags( sanitize_text_field( $stylers_values['lightness'] ) ) );
				}
				if ( isset( $stylers_values['saturation'] ) && $stylers_values['saturation'] != '' ) {
				   $stylers[] = array( 'saturation' => strip_tags( sanitize_text_field( $stylers_values['saturation'] ) ) );
				}
				if ( isset( $stylers_values['visibility'] ) && $stylers_values['visibility'] != '' ) {
				   $stylers[] = array( 'visibility' => strip_tags( sanitize_text_field( $stylers_values['visibility'] ) ) );
				}
				if ( isset( $stylers_values['weight'] ) && $stylers_values['weight'] != '' ) {
				   $stylers[] = array( 'weight' => strip_tags( sanitize_text_field( $stylers_values['weight'] ) ) );
				}

				$style['stylers'] = $stylers;
				$styles[] = $style;
			}
		}

		$serialize_values = maybe_serialize( $styles );
		update_option( $get_option_key, $serialize_values );

		$get_style_html = $this->get_custom_map_style_html( $data_id , $get_option_key );

		$output = array(
			'id' => $data_id,
			'html' => $get_style_html,
		);

		echo json_encode( $output );

		wp_die();
	}

	/**
	 * Get preview map styles.
	 *
	 * When user add, remove, import or preview then update map styles.
	 *
	 * @since 2.0.0
	 */
	public function preview_map_styles_action() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( -1 );
		}

		$get_field_id = !empty( $_POST['get_field_id'] ) ? sanitize_text_field( wp_unslash( $_POST['get_field_id'] ) ) : '';
		$get_option_id = !empty( $_POST['get_option_id'] ) ? sanitize_text_field( wp_unslash( $_POST['get_option_id'] ) ) : '';

		$option_key = get_gd_cgm_option_key($get_option_id);
		$get_map_styles_arr = maybe_unserialize( get_option( $option_key ));

		$default_location   = gd_cgm_default_location();
		$latitude   		= ! empty( $default_location->latitude ) ? (float) $default_location->latitude : '-75.163786';
		$longitude  		= ! empty( $default_location->longitude ) ? (float) $default_location->longitude : '39.952484';

		$output = array(
			'id' => $get_field_id,
			'styles' => json_encode($get_map_styles_arr),
			'latitude' => $latitude,
			'longitude' => $longitude,
		);

		echo json_encode( $output );

		wp_die();
	}

	/**
	 * Save custom open street map selected layer.
	 *
	 * @since 2.0.0
	 */
	public function gd_save_osm_layers_action() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( -1 );
		}

		$get_id = !empty( $_POST['get_id'] ) ? sanitize_text_field( wp_unslash( $_POST['get_id'] ) ) :'';
		$get_field_key = !empty( $_POST['get_field_key'] ) ? sanitize_text_field( wp_unslash( $_POST['get_field_key'] ) ) :'';
		$get_base_value = !empty( $_POST['get_base_value'] ) ? sanitize_text_field( wp_unslash( $_POST['get_base_value'] ) ) :'';
		$get_overlay_value = !empty( $_POST['get_overlay_value'] ) ? geodir_clean( wp_unslash( $_POST['get_overlay_value'] ) ) :'';
		
		$get_osm_option_key = get_gd_cgm_osm_option_key( $get_field_key );

		$option_arr = array(
			'base_value' => $get_base_value,
			'overlay_value' => $get_overlay_value,
		);

		$option_arr = maybe_serialize($option_arr);
		update_option($get_osm_option_key,$option_arr);

		$output = array( 'id' => $get_id, );
		echo json_encode($output);
		wp_die();
	}

	/**
	 * Preview custom open street map layer.
	 *
	 * @since 2.0.0
	 */
	public function gd_preview_osm_layers_action() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( -1 );
		}

		$get_id = !empty( $_POST['get_id'] ) ? sanitize_text_field( wp_unslash( $_POST['get_id'] ) ) :'';
		$field_id = !empty( $_POST['field_id'] ) ? sanitize_text_field( wp_unslash( $_POST['field_id'] ) ) :'';

		$default_location   = gd_cgm_default_location();
		$latitude           = !empty( $default_location->latitude ) ? (float) $default_location->latitude : '-75.163786';
		$longitude          = !empty( $default_location->longitude ) ? (float) $default_location->longitude : '39.952484';

		$map_key = get_gd_cgm_map_api_key();
		$map_key = str_replace('&key=','',$map_key);

		$get_osm_base_layers = stripslashes(get_gd_cgm_osm_base_layers($field_id));
		$get_osm_overlay_layers = stripslashes(get_gd_cgm_osm_overlay_layers($field_id));

		$get_osm_option_key = get_gd_cgm_osm_option_key( $field_id );
		$get_osm_option_val = maybe_unserialize( get_option($get_osm_option_key) );

		$get_base_value = !empty( $get_osm_option_val['base_value'] ) ? $get_osm_option_val['base_value'] :'OpenStreetMap.Mapnik';
		$get_overlay_value = !empty( $get_osm_option_val['overlay_value'] ) ? $get_osm_option_val['overlay_value'] : array();

		$output = array(
			'id' => $get_id, 
			'field_id' => $field_id,
			'base_layers' => json_decode($get_osm_base_layers),
			'osm_layers' => json_decode($get_osm_overlay_layers),
			'latitude' => $latitude,
			'longitude' => $longitude ,
		);

		echo json_encode($output);

		wp_die();
	}

	public function set_map_name( $active_map ) {
		if ( ! empty( $_GET['section'] ) && $_GET['section'] == 'manage-map-style' && is_admin() ) {
			if ( ! empty( $_GET['map_type'] ) && $_GET['map_type'] == 'osm' ) {
				$active_map = 'osm';
			} else {
				$active_map = 'google';
			}
		}

		return $active_map;
	}

	public function load_gomap_script( $load ) {
		if ( ! $load && ! empty( $_GET['section'] ) && $_GET['section'] == 'manage-map-style' && is_admin() ) {
			$load = true;
		}

		return $load;
	}
}
