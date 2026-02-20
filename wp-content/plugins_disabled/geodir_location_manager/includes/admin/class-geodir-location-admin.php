<?php
/**
 * GeoDirectory Admin
 *
 * @class    GeoDir_Admin
 * @author   AyeCode
 * @category Admin
 * @package  GeoDirectory/Admin
 * @version  2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * GeoDir_Location_Admin class.
 */
class GeoDir_Location_Admin {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'includes' ) );
		add_action( 'current_screen', array( $this, 'conditional_includes' ) );
		add_action( 'admin_init', array( $this, 'buffer' ), 1 );
		add_action( 'admin_init', array( $this, 'admin_redirects' ) );
		add_filter( 'geodir_get_settings_pages', array( $this, 'load_settings_page' ), 13, 1 );
		add_filter( 'geodir_load_gomap_script', array( $this, 'load_gomap_script' ), 10, 1 );
		add_filter( 'geodir_get_sections_general', array( $this, 'hide_default_location_setting' ), 10, 1 );
		add_filter( 'geodir_default_location', array( $this, 'default_location_setting' ), 11, 1 );
		add_filter( 'geodir_load_db_language', array( $this, 'load_db_text_translation' ), 20, 1 );
		add_filter( 'geodir_search_options', array( __CLASS__, 'general_search_settings' ), 13, 1 );

		add_action( 'geodir_clear_version_numbers' ,array( $this, 'clear_version_number'));
		add_action( 'geodir_address_extra_admin_fields', 'geodir_location_address_extra_admin_fields', 1, 2 );
		add_filter( 'geodir_uninstall_options', 'geodir_location_uninstall_settings', 10, 1 );
		add_filter( 'geodir_setup_wizard_default_location_saved', 'geodir_location_setup_wizard_default_location', 10, 1 );
		add_action( 'admin_init', array( $this, 'add_custom_notice' ), 20 );
		add_filter( 'geodir_debug_tools' , 'geodir_location_diagnostic_tools', 20 );
		add_filter( 'geodir_add_custom_sort_options', array( $this, 'add_sort_options' ), 9, 2 );
		add_action('geodir_status_tool_after_desc', array( $this, 'geodir_tool_content' ), 999, 2 );

		if ( ! empty( $_REQUEST['taxonomy'] ) && is_admin() ) {
			$taxonomy = sanitize_text_field( $_REQUEST['taxonomy'] );

			if ( geodir_taxonomy_type( $taxonomy ) == 'category' && geodir_is_gd_taxonomy( $taxonomy ) ) {
				// Category + Location description.
				add_action( $taxonomy . '_edit_form_fields', 'geodir_location_cat_loc_desc', 9, 2 );
			}
		}

		if ( is_admin() ) {
			if ( ! empty( $_REQUEST['tab'] ) && $_REQUEST['tab'] == 'locations' && ! empty( $_REQUEST['section'] ) && $_REQUEST['section'] == 'add_location' ) {
				remove_action( 'geodir_update_marker_address', 'geodir_setup_timezone_api', 1, 1 );
				add_action( 'geodir_update_marker_address', 'geodir_location_setup_timezone_api', 1, 1 );
			}

			add_action( 'admin_notices', array( $this, 'display_admin_notices' ), 10 );
		}
	}

	/**
	 * Deletes the version number from the DB so install functions will run again.
	 */
	public function clear_version_number(){
		delete_option( 'geodir_location_version' );
	}

	/**
	 * Output buffering allows admin screens to make redirects later on.
	 */
	public function buffer() {
		ob_start();
	}

	/**
	 * Include any classes we need within admin.
	 */
	public function includes() {
		include_once( dirname( __FILE__ ) . '/admin-functions.php' );
		include_once( dirname( __FILE__ ) . '/class-geodir-location-admin-assets.php' );
		include_once( dirname( __FILE__ ) . '/class-geodir-location-admin-import-export.php' );
		include_once( dirname( __FILE__ ) . '/class-geodir-location-admin-dashboard.php' );
	}

	/**
	 * Include admin files conditionally.
	 */
	public function conditional_includes() {
		if ( ! $screen = get_current_screen() ) {
			return;
		}

		switch ( $screen->id ) {
			case 'dashboard' :
			break;
			case 'options-permalink' :
			break;
			case 'users' :
			case 'user' :
			case 'profile' :
			case 'user-edit' :
			break;
			case 'customize':
			case 'widgets' :
			break;
		}
	}

	/**
	 * Handle redirects to setup/welcome page after install and updates.
	 *
	 * For setup wizard, transient must be present, the user must have access rights, and we must ignore the network/bulk plugin updaters.
	 */
	public function admin_redirects() {
		// Nonced plugin install redirects (whitelisted)
		if ( ! empty( $_GET['geodir-location-install-redirect'] ) ) {
			$plugin_slug = geodir_clean( $_GET['geodir-location-install-redirect'] );

			$url = admin_url( 'plugin-install.php?tab=search&type=term&s=' . $plugin_slug );

			wp_safe_redirect( $url );
			exit;
		}

		// Setup wizard redirect
		if ( get_transient( '_geodir_location_activation_redirect' ) ) {
			delete_transient( '_geodir_location_activation_redirect' );
		}
	}
	
	public static function load_settings_page( $settings_pages ) {
		$post_type = ! empty( $_REQUEST['post_type'] ) ? sanitize_title( $_REQUEST['post_type'] ) : 'gd_place';
		if ( !( isset( $_REQUEST['page'] ) && $_REQUEST['page'] == $post_type.'-settings' ) ) {
			$settings_pages[] = include( GEODIR_LOCATION_PLUGIN_DIR . 'includes/admin/settings/class-geodir-location-settings-locations.php' );
		}

		return $settings_pages;
	}
	
	public static function load_gomap_script( $load ) {
		$tab 		= ! empty( $_GET['tab'] ) ? $_GET['tab'] : '';
		$section 	= ! empty( $_GET['section'] ) ? $_GET['section'] : '';

		if ( $tab == 'locations' ) {
			if ( $section == 'add_location' ) {
				$load = true;
			} else if ( $section == 'neighbourhoods' && ! empty( $_GET['add_neighbourhood'] ) ) {
				$load = true;
			}
		}

		return $load;
	}
	
	public static function hide_default_location_setting( $sections ) {
		if ( empty( $_GET['tab'] ) || (! empty( $_GET['tab'] ) && $_GET['tab'] == 'general') ) {
			if ( isset( $sections['location'] ) ) {
				unset( $sections['location'] );
			}
		}
		return $sections;
	}

	public static function add_custom_notice() {
		global $geodirectory;

		$page = ! empty( $_GET['page'] ) ? $_GET['page'] : '';
		$tab = ! empty( $_GET['tab'] ) ? $_GET['tab'] : '';

		if ( wp_doing_ajax() ) {
			return;
		}

		if ( ! empty( $page ) && in_array( $page, array( 'geodirectory', 'gd-settings', 'gd-status' ) ) && $tab != 'fix_duplicate_location_slugs' ) {
			// Check location duplicate slugs
			if ( ! GeoDir_Admin_Notices::has_notice( 'geodir_location_duplicate_slug_error' ) && $geodirectory->location->has_duplicate_slugs() ) {
				GeoDir_Admin_Notices::add_custom_notice(
					'geodir_location_duplicate_slug_error',
					wp_sprintf(
						__( 'There are duplicate slugs found for some locations. Go to GoeDirectory > Status > Tools & run a tool <a href="%1$s">Fix location duplicate slugs</a> to fix duplicate slugs.', 'geodirlocation' ),
						esc_url( admin_url( 'admin.php?page=gd-status&tab=tools' ) )
					)
				);
			}
		}
	}

	/**
	 * Filter default location page setting.
	 *
	 * @since 2.1.0.6
	 *
	 * @param array $settings Default location settings.
	 */
	public function default_location_setting( $settings ) {
		if ( ! empty( $settings ) ) {
			foreach ( $settings as $key => $setting ) {
				// Hide core multi city setting.
				if ( ! empty( $setting['id'] ) && $setting['id'] == 'multi_city' ) {
					$settings[ $key ]['type'] = 'hidden';
				}
			}
		}

		return $settings;
	}

	/**
	 * Load locations text for translation.
	 *
	 * @since 2.1.0.10
	 *
	 * @global object $wpdb WordPress database abstraction object.
	 *
	 * @param  array $translations Array of text strings.
	 * @return array
	 */
	public function load_db_text_translation( $translations = array() ) {
		global $wpdb;

		if ( ! is_array( $translations ) ) {
			$translations = array();
		}

		// Locations
		$results = $wpdb->get_results( "SELECT meta_title, meta_desc, image_tagline, location_desc, cpt_desc FROM `" . GEODIR_LOCATION_SEO_TABLE . "`" );

		if ( ! empty( $results ) ) {
			foreach ( $results as $row ) {
				if ( ! empty( $row->meta_title ) ) {
					$translations[] = stripslashes( $row->meta_title );
				}

				if ( ! empty( $row->meta_desc ) ) {
					$translations[] = stripslashes( $row->meta_desc );
				}

				if ( ! empty( $row->image_tagline ) ) {
					$translations[] = stripslashes( $row->image_tagline );
				}

				if ( ! empty( $row->location_desc ) ) {
					$translations[] = stripslashes( $row->location_desc );
				}

				if ( ! empty( $row->cpt_desc ) ) {
					$cpt_desc = json_decode( $row->cpt_desc, true );

					if ( ! empty( $cpt_desc ) && is_array( $cpt_desc ) ) {
						foreach ( $cpt_desc as $post_type => $desc ) {
							if ( ! empty( $desc ) ) {
								$translations[] = stripslashes( $desc );
							}
						}
					}
				}
			}
		}

		if ( ! GeoDir_Location_Neighbourhood::is_active() ) {
			return $translations;
		}

		// Neighbourhoods
		$results = $wpdb->get_results( "SELECT hood_meta_title, hood_meta, hood_description, cpt_desc FROM `" . GEODIR_NEIGHBOURHOODS_TABLE . "`" );

		if ( ! empty( $results ) ) {
			foreach ( $results as $row ) {
				if ( ! empty( $row->hood_meta_title ) ) {
					$translations[] = stripslashes( $row->hood_meta_title );
				}

				if ( ! empty( $row->hood_meta ) ) {
					$translations[] = stripslashes( $row->hood_meta );
				}

				if ( ! empty( $row->hood_description ) ) {
					$translations[] = stripslashes( $row->hood_description );
				}

				if ( ! empty( $row->cpt_desc ) ) {
					$cpt_desc = json_decode( $row->cpt_desc, true );

					if ( ! empty( $cpt_desc ) && is_array( $cpt_desc ) ) {
						foreach ( $cpt_desc as $post_type => $desc ) {
							if ( ! empty( $desc ) ) {
								$translations[] = stripslashes( $desc );
							}
						}
					}
				}
			}
		}

		return $translations;
	}

	public function add_sort_options( $fields, $post_type ) {
		if ( GeoDir_Post_types::supports( $post_type, 'location' ) ) {
			$fields['country'] = array(
				'post_type'      => $post_type,
				'data_type'      => '',
				'field_type'     => 'text',
				'frontend_title' => __( 'Country', 'geodirlocation' ),
				'htmlvar_name'   => 'country',
				'field_icon'     => 'fas fa-map-marker-alt',
				'description'    => __( 'Sort by country.', 'geodirlocation' )
			);

			$fields['region'] = array(
				'post_type'      => $post_type,
				'data_type'      => '',
				'field_type'     => 'text',
				'frontend_title' => __( 'Region', 'geodirlocation' ),
				'htmlvar_name'   => 'region',
				'field_icon'     => 'fas fa-map-marker-alt',
				'description'    => __( 'Sort by region.', 'geodirlocation' )
			);

			$fields['city'] = array(
				'post_type'      => $post_type,
				'data_type'      => '',
				'field_type'     => 'text',
				'frontend_title' => __( 'City', 'geodirlocation' ),
				'htmlvar_name'   => 'city',
				'field_icon'     => 'fas fa-map-marker-alt',
				'description'    => __( 'Sort by city.', 'geodirlocation' )
			);

			$fields['city'] = array(
				'post_type'      => $post_type,
				'data_type'      => '',
				'field_type'     => 'text',
				'frontend_title' => __( 'City', 'geodirlocation' ),
				'htmlvar_name'   => 'city',
				'field_icon'     => 'fas fa-map-marker-alt',
				'description'    => __( 'Sort by city.', 'geodirlocation' )
			);

			if ( GeoDir_Location_Neighbourhood::is_active() ) {
				$fields['neighbourhood'] = array(
					'post_type'      => $post_type,
					'data_type'      => '',
					'field_type'     => 'text',
					'frontend_title' => __( 'Neighbourhood', 'geodirlocation' ),
					'htmlvar_name'   => 'neighbourhood',
					'field_icon'     => 'fas fa-map-marker-alt',
					'description'    => __( 'Sort by neighbourhood.', 'geodirlocation' )
				);
			}
		}

		return $fields;
	}

	public static function general_search_settings( $settings = array() ) {
		if ( geodir_get_option( 'lm_default_country' ) == 'selected' && ( (array) geodir_get_option( 'lm_selected_countries' ) ) ) {
			$_settings = array();

			foreach ( $settings as $key => $setting ) {
				$_settings[] = $setting;

				if ( ! empty( $setting['type'] ) && $setting['type'] == 'sectionend' && ! empty( $setting['id'] ) && $setting['id'] == 'search_results_options' ) {
					$_settings[] = array(
						'id' => 'search_google_bounds_bias_options',
						'type' => 'title',
						'title' => __( 'Google Maps Bounds Bias (bias an area for search near results)', 'geodirlocation' )
					);

					$_settings[] = array(
						'id' => 'lm_search_bound_sw',
						'type' => 'text',
						'name' => __( 'LatLngBounds South West Corner', 'geodirlocation' ),
						'desc' => __( 'Comma separated latitude & longitude of the south-west corner of the viewport bounding box. The LatLngBounds within which to bias geocode results more prominently. The bounds parameter will only influence, not fully restrict, results from the geocoder.', 'geodirlocation' ),
						'placeholder' => '35.464840471822555,-9.104163644955786',
						'default' => '',
						'desc_tip' => true,
						'advanced' => false
					);

					$_settings[] = array(
						'id' => 'lm_search_bound_ne',
						'type' => 'text',
						'name' => __( 'LatLngBounds North East  Corner', 'geodirlocation' ),
						'desc' => __( 'Comma separated latitude & longitude of the north-east corner of the viewport bounding box. The LatLngBounds within which to bias geocode results more prominently. The bounds parameter will only influence, not fully restrict, results from the geocoder.', 'geodirlocation' ),
						'placeholder' => '43.25829086950618,4.0113094706612085',
						'default' => '',
						'desc_tip' => true,
						'advanced' => false
					);

					$_settings[] = array(
						'id' => 'search_google_bounds_bias_options',
						'type' => 'sectionend'
					);
				}
			}

			$settings = $_settings;
		}

		return $settings;
	}

	public function geodir_tool_content( $action, $tool ) {
		global $geodir_tool_render;

		if ( empty( $geodir_tool_render ) ) {
			$geodir_tool_render = array();
		}

		$geodir_tool_render[ $action ] = true;
	
		if ( $action == 'merge_post_locations' ) {
			$post_types = geodir_get_posttypes();
			$merge_post_types = array();

			foreach ( $post_types as $post_type ) {
				if ( ! GeoDir_Post_types::supports( $post_type, 'location' ) ) {
					continue;
				}

				$count = 0;
				$count_posts = wp_count_posts( $post_type );

				foreach ( $count_posts as $status => $_count ) {
					$count += (int) $_count;
				}

				if ( $count > 0 ) {
					$merge_post_types[ $post_type ] = $count;
				}
			}
?>
			<div class="bsui" style="max-width:600px">
				<?php if ( empty( $merge_post_types ) ) { ?>
				<div class="bsui"><div class="alert alert-info mb-0 mt-3"><?php esc_html_e( 'No locations to merge :)', 'geodirlocation' ); ?></div></div>
				<?php } else { ?>
				<table class="table table-sm">
					<thead class="fw-bold">
						<tr>
							<th scope="col"><?php esc_html_e( 'Post Type', 'geodirlocation' ); ?></th>
							<th scope="col" class="text-end text-right"><?php esc_html_e( 'Listings', 'geodirlocation' ); ?></th>
							<th scope="col" class="text-end text-right"><?php esc_html_e( 'Locations', 'geodirlocation' ); ?></th>
							<th scope="col" style="width:90px" class="text-center"></th>
							<th scope="col" style="width:120px" class="text-center"></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $merge_post_types as $post_type => $count ) { ?>
						<tr class="gdlmm-post-type gdlmm-pending" data-count="0" data-updated="0" data-post-type="<?php echo esc_attr( $post_type ); ?>" data-per-page="10">
							<th scope="row" class="align-middle"><?php echo geodir_post_type_name( $post_type ); ?></th>
							<td class="align-middle text-end text-right"><?php echo (int) $count; ?></td>
							<td class="align-middle text-end text-right gdlmm-count">~</td>
							<td class="align-middle text-center"><span class="gdlmm-loader d-none"><i class="fas fa-circle-notch fa-spin fa-fw" aria-hidden="true"></i></span><span class="gdlmm-success text-success d-none"><i class="fas fa-check-circle fa-fw" aria-hidden="true"></i></span><span class="gdlmm-error text-danger d-none"><i class="fas fa-circle-exclamation fa-fw" aria-hidden="true"></i></span></td>
							<td class="gdlmm-stats align-middle text-center"><span class="gdlmm-stat fs-sm d-none">0%</span> <span class="gdlmm-stat-n d-none">(<span class="gdlmm-stat-num fs-sm">0</span>)</span></td>
						</tr>
						<?php } ?>
					</tbody>
				</table>
			</div>
			<script type="text/javascript">
				jQuery(function($) {
					$("a.merge_post_locations").on("click", function(e){
						if (!jQuery('.gdlmm-pending:first').length) {
							return false;
						}

						if (window.isLMMerging) {
							window.isLMMerging = false;
							$("a.merge_post_locations").text("<?php echo addslashes( esc_html__( 'Continue', 'geodirlocation' ) ); ?>");
						} else {
							window.isLMMerging = true;
							$("a.merge_post_locations").text("<?php echo addslashes( esc_html__( 'Pause', 'geodirlocation' ) ); ?>");
						}
						geodir_location_merge_locations(window.isLMMergePage);
					});
				});

				function geodir_location_merge_locations(page, stop) {
					var $row = jQuery('.gdlmm-pending:first');
					if (!$row.length) {
						jQuery("a.merge_post_locations").text("<?php echo addslashes( esc_html__( 'Finished', 'geodirlocation' ) ); ?>");
						console.log('FINISHED');
						return false;
					}

					if (!window.isLMMerging) {
						console.log('PAUSED');
						$row.find('.gdlmm-loader').addClass('d-none');
						return false;
					}

					var data = 'action=geodir_merge_post_locations&_post_type=' + $row.attr('data-post-type') + '&_per_page=' + $row.attr('data-per-page') + '&security=<?php echo esc_js( strip_tags( wp_create_nonce( 'geodir-merge-post-locations' ) ) ); ?>';
					if (!page) {
						page = 1;
					}
					if (page) {
						data += '&_page=' + page;
					}
					var tot = parseInt($row.attr('data-count'));

					jQuery.ajax({
						url: geodir_params.gd_ajax_url,
						type: 'POST',
						data: data,
						dataType: 'json',
						beforeSend: function(xhr, obj) {
							$row.find('.gdlmm-loader').removeClass('d-none');
							$row.find('.gdlmm-stat').removeClass('d-none');
							window.isLMMergePage = page;
						}
					})
					.done(function(res, textStatus, jqXHR) {
						if (typeof res == 'object' && res.success) {
							console.log(res.data);
							if (page <= 1) {
								$row.attr('data-per-page', res.data.per_page);
								$row.find('.gdlmm-count').text(res.data.total);
								$row.attr('data-count', res.data.total);
								tot = parseInt(res.data.total)
							}
							var updated = parseInt($row.attr('data-updated'));
							updated = updated > 0 ? updated : 0;
							updated = updated + parseInt(res.data.updated);
							$row.attr('data-updated', updated);
							if (updated > tot) {
								updated = tot;
							}
							var progress = tot > 0 ? Math.floor(updated / tot * 100) : 100;
							if (progress >= 100) {
								progress = 100;
								res.data.next_cpt = true;
							} else if (progress < 1) {
								progress = 1;
							}
							$row.find('.gdlmm-stat').text(progress+'%');
							$row.find('.gdlmm-stat-n').removeClass('d-none');
							$row.find('.gdlmm-stat-num').text(updated);
							$row.attr('data-page', res.data.next_page);
							window.isLMMergePage = parseInt(res.data.next_page);
							if (res.data.next_cpt) {
								$row.find('.gdlmm-loader').addClass('d-none');
								$row.find('.gdlmm-success').removeClass('d-none');
								$row.removeClass('gdlmm-pending');
								res.data.next_page = 0;
							}
							if (window.isLMMerging) {
								return geodir_location_merge_locations(res.data.next_page);
							}
						}
					})
					.always(function(res, textStatus, jqXHR) {
						if (textStatus!= 'success') {
							console.log(textStatus);
							console.log(res);
							$row.find('.gdlmm-loader').addClass('d-none');
							$row.find('.gdlmm-error').removeClass('d-none');
						}
					});
				}
			</script><?php
			}
		}
	}

	/**
	 * Display admin notices.
	 *
	 * @since 2.3.30
	 */
	public function display_admin_notices() {
		global $geodirectory;

		$messages = array();

		// Selected countries
		if ( geodir_get_option( 'lm_default_country' ) == 'selected' ) {
			$default_location = $geodirectory->location->get_default_location();
			$country = ! empty( $default_location->country ) ? $default_location->country : '';
			$selected_countries = geodir_get_option( 'lm_selected_countries' );
			$messages = array();

			if ( ! ( ! empty( $country ) && is_array( $selected_countries ) && in_array( $country, $selected_countries ) ) ) {
				$messages[] = wp_sprintf( __( '- Default location country %s must be within Selected Countries: %s.', 'geodirlocation' ),
					'<b>' . stripslashes( $country ) . '</b>',
					'<b>' . ( is_array( $selected_countries ) ? implode( ", ", $selected_countries ) : $selected_countries ) . '</b>'
				);
			}
		}

		// Selected regions
		if ( geodir_get_option( 'lm_default_region' ) == 'selected' ) {
			if ( empty( $default_location ) ) {
				$default_location = $geodirectory->location->get_default_location();
			}
			$region = ! empty( $default_location->region ) ? $default_location->region : '';
			$selected_regions = geodir_get_option( 'lm_selected_regions' );

			if ( ! ( ! empty( $region ) && is_array( $selected_regions ) && in_array( $region, $selected_regions ) ) ) {
				$messages[] = wp_sprintf( __( '- Default location region %s must be within Selected Regions: %s.', 'geodirlocation' ),
					'<b>' . stripslashes( $region ) . '</b>',
					'<b>' . ( is_array( $selected_regions ) ? implode( ", ", $selected_regions ) : $selected_regions ) . '</b>'
				);
			}
		}

		// Selected cities
		if ( geodir_get_option( 'lm_default_city' ) == 'selected' ) {
			if ( empty( $default_location ) ) {
				$default_location = $geodirectory->location->get_default_location();
			}
			$city = ! empty( $default_location->city ) ? $default_location->city : '';
			$selected_cities = geodir_get_option( 'lm_selected_cities' );

			if ( ! ( ! empty( $city ) && is_array( $selected_cities ) && in_array( $city, $selected_cities ) ) ) {
				$messages[] = wp_sprintf( __( '- Default location city %s must be within Selected cities: %s.', 'geodirlocation' ),
					'<b>' . stripslashes( $city ) . '</b>',
					'<b>' . ( is_array( $selected_cities ) ? implode( ", ", $selected_cities ) : $selected_cities ) . '</b>'
				);
			}
		}

		if ( ! empty( $messages ) ) {
			$message = wp_sprintf( __( '<b>Error:</b> Setup correct default location at %shere%s.', 'geodirlocation' ),
				'<a href="' . esc_attr( admin_url( 'admin.php?page=gd-settings&tab=locations&section=cities' ) ) . '">',
				'</a>'
			);
			$message .= '<br>' . implode( "<br>", $messages );

			echo '<div class="error"><p><i class="fas fa-exclamation-circle" aria-hidden="true"></i> ' . $message . '</p></div>';
		}
	}
}