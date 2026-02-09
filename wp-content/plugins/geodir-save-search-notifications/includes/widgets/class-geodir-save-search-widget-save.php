<?php
/**
 * Save Search Widget
 *
 * @author    AyeCode Ltd
 * @package   GeoDir_Save_Search
 * @version   1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * GeoDir_Save_Search_Widget_Save class.
 */
class GeoDir_Save_Search_Widget_Save extends WP_Super_Duper {

	public $arguments;

	/**
	 * Sets up the widgets name etc
	 */
	public function __construct() {

		$options = array(
			'base_id'        => 'gd_save_search',
			'name'           => __( 'GD > Save Search', 'geodir-save-search' ),
			'class_name'     => __CLASS__,
			'textdomain'     => GEODIRECTORY_TEXTDOMAIN,
			'block-icon'     => 'fas fa-bell',
			'block-category' => 'geodirectory',
			'block-supports' => array(
				'customClassName' => false,
			),
			'block-keywords' => "['geodir','search','save']",
			'widget_ops'     => array(
				'classname'    => 'geodir-save-search-container' . ( geodir_design_style() ? ' bsui' : '' ),
				'description'  => esc_html__( 'Displays a button to save search on search page.', 'geodir-save-search' ),
				'customize_selective_refresh' => true,
				'geodirectory' => true,
			),
			'block_group_tabs' => array(
				'content' => array(
					'groups' => array(
						__( 'Title', 'geodirectory' ),
						__( 'Button Content', 'geodir-save-search' )
					),
					'tab' => array(
						'title' => __( 'Content', 'geodirectory' ),
						'key' => 'bs_tab_content',
						'tabs_open' => true,
						'open' => true,
						'class' => 'text-center flex-fill d-flex justify-content-center'
					),
				),
				'styles' => array(
					'groups' => array(
						__( 'Design', 'geodirectory' )
					),
					'tab' => array(
						'title' => __( 'Styles', 'geodirectory' ),
						'key' => 'bs_tab_styles',
						'tabs_open' => true,
						'open' => true,
						'class' => 'text-center flex-fill d-flex justify-content-center'
					)
				),
				'advanced' => array(
					'groups' => array(
						__( 'Wrapper Styles', 'geodirectory' ),
						__( 'Advanced', 'geodirectory' ),
					),
					'tab' => array(
						'title' => __( 'Advanced', 'geodirectory' ),
						'key' => 'bs_tab_advanced',
						'tabs_open' => true,
						'open' => true,
						'class' => 'text-center flex-fill d-flex justify-content-center'
					)
				),
			)
		);

		parent::__construct( $options );
	}

	/**
	 * Set widget arguments.
	 */
	public function set_arguments() {
		$arguments = array(
			'title' => array(
				'type' => 'text',
				'title' => __( 'Title:', 'geodirectory' ),
				'desc' => __( 'The widget title.', 'geodirectory' ),
				'default' => '',
				'desc_tip' => true,
				'advanced' => false,
				'group' => __( 'Title', 'geodirectory' )
			),
			'output' => array(
				'type' => 'select',
				'title' => __( 'Output Type:', 'geodir-save-search' ),
				'desc' => __( 'Select save search button output type.', 'geodir-save-search' ),
				'options' => array(
					'' => __( 'Default (icon + text)', 'geodir-save-search' ),
					'icon' => __( 'Icon', 'geodir-save-search' ),
					'text' => __( 'Text', 'geodir-save-search' )
				),
				'default' => '',
				'desc_tip' => true,
				'advanced' => false,
				'group' => __( 'Button Content', 'geodir-save-search' )
			),
			'btn_text' => array(
				'type' => 'text',
				'title' => __( 'Button Title:', 'geodir-save-search' ),
				'desc' => __( 'Save search button title.', 'geodir-save-search' ),
				'placeholder' => __( 'Save this Search', 'geodir-save-search' ),
				'default' => '',
				'desc_tip' => true,
				'advanced' => false,
				'element_require' => '[%output%]!="icon"',
				'group' => __( 'Button Content', 'geodir-save-search' )
			),
			'btn_icon' => array(
				'type' => 'text',
				'title' => __( 'Button Icon Class (font-awesome)', 'geodir-save-search' ),
				'desc' => __( 'FontAwesome icon class to use.', 'geodir-save-search' ),
				'placeholder' => 'fas fa-bell',
				'default' => '',
				'desc_tip' => true,
				'advanced' => false,
				'element_require' => '[%output%]!="text"',
				'group' => __( 'Button Content', 'geodir-save-search' )
			)
		);

		$arguments['btn_alignment'] = array(
			'type' => 'select',
			'title' => __( 'Button Position:', 'geodir-save-search' ),
			'desc' => __( 'Button alignment.', 'geodir-save-search' ),
			'options' => array(
				'' => __( 'Default', 'geodir-save-search' ),
				'left' => __( 'Left', 'geodir-save-search' ),
				'center' => __( 'Center', 'geodir-save-search' ),
				'right' => __( 'Right', 'geodir-save-search' ),
				'block' => __( 'Block', 'geodir-save-search' )
			),
			'default' => '',
			'desc_tip' => true,
			'advanced' => false,
			'group' => __( 'Design', 'geodirectory' )
		);

		$arguments['btn_size'] = array(
			'type' => 'select',
			'title' => __( 'Button Size:', 'geodir-save-search' ),
			'desc' => __( 'Select button size.', 'geodir-save-search' ),
			'options' => array(
				'' => __( 'Default (medium)', 'geodir-save-search' ),
				'sm' => __( 'Small', 'geodir-save-search' ),
				'lg' => __( 'Large', 'geodir-save-search' )
			),
			'default' => '',
			'desc_tip' => true,
			'advanced' => false,
			'group' => __( 'Design', 'geodirectory' )
		);

		$arguments['btn_color'] = array(
			'type' => 'select',
			'title' => __( 'Button Color:', 'geodir-save-search' ),
			'desc' => __( 'Button color.', 'geodir-save-search' ),
			'options' => array(
				'' => __( 'Default (primary)', 'geodir-save-search' ),
			) + geodir_aui_colors( false, true ),
			'default' => '',
			'desc_tip' => true,
			'advanced' => false,
			'group' => __( 'Design', 'geodirectory' )
		);

		$arguments['no_wrap'] = array(
			'type' => 'checkbox',
			'title' => __( 'Remove widget main wrapping div', 'geodir-save-search' ),
			'default' => '0',
			'desc_tip' => true,
			'advanced' => true,
			'group' => __( 'Wrapper Styles', 'geodirectory' )
		);

		$arguments['mt']  = geodir_get_sd_margin_input( 'mt' );
		$arguments['mr']  = geodir_get_sd_margin_input( 'mr' );
		$arguments['mb']  = geodir_get_sd_margin_input( 'mb' );
		$arguments['ml']  = geodir_get_sd_margin_input( 'ml' );

		// Padding
		$arguments['pt'] = geodir_get_sd_padding_input( 'pt' );
		$arguments['pr'] = geodir_get_sd_padding_input( 'pr' );
		$arguments['pb'] = geodir_get_sd_padding_input( 'pb' );
		$arguments['pl'] = geodir_get_sd_padding_input( 'pl' );

		// CSS Class
		$arguments['css_class'] = sd_get_class_input();

		return $arguments;
	}

	/**
	 * Outputs the save search on the front-end.
	 *
	 * @param array $instance Settings for the widget instance.
	 * @param array $args     Display arguments.
	 * @param string $content
	 *
	 * @return mixed|string|void
	 */
	public function output( $instance = array(), $args = array(), $content = '' ) {
		$output = $this->output_html( $instance, $args );

		return $output;
	}

	/**
	 * Output HTML.
	 *
	 * @param array $instance Settings for the widget instance.
	 * @param array $args     Display arguments.
	 * @return bool|string
	 */
	public function output_html( $instance = array(), $args = array() ) {
		global $wp_query, $aui_bs5;

		$design_style = geodir_design_style();

		if ( ! $design_style ) {
			return;
		}

		$defaults = array(
			'title' => '',
			'output' => '',
			'btn_text' => '',
			'btn_icon' => 'fas fa-bell',
			'btn_alignment' => '',
			'no_wrap' => '',
			'btn_size' => '',
			'btn_color' => 'primary',
			'mt' => '',
			'mb' => '',
			'mr' => '',
			'ml' => '',
			'pt' => '',
			'pb' => '',
			'pr' => '',
			'pl' => '',
			'css_class' => '',
			'is_preview' => $this->is_preview()
		);

		$instance = shortcode_atts( $defaults, $instance, 'gd_save_search' );

		$show = ( ( geodir_is_page( 'post_type' ) || geodir_is_page( 'archive' ) || geodir_is_page( 'search' ) ) && ! geodir_is_page( 'author' ) || ! empty( $instance['is_preview'] ) );

		if ( ! apply_filters( 'geodir_save_search_show_output', $show, $instance ) ) {
			return;
		}

		add_action( 'wp_footer', array( __CLASS__, 'add_script' ), 100 );

		$button_class = 'btn';
		$default_text = __( 'Save this Search', 'geodir-save-search' );

		// Button text (btn_text)
		if ( $instance['output'] != 'icon' && empty( $instance['btn_text'] ) ) {
			$instance['btn_text'] = $default_text;
		}

		// Button icon (btn_icon)
		if ( $instance['output'] != 'text' && empty( $instance['btn_icon'] ) ) {
			$instance['btn_icon'] = 'fas fa-bell';
		}

		if ( $instance['output'] == 'icon' ) {
			$instance['btn_text'] = '';
		}

		// Button color (btn_color)
		if ( empty( $instance['btn_color'] ) ) {
			$instance['btn_color'] = 'primary';
		}
		$button_class .= ' btn-' . sanitize_html_class( $instance['btn_color'] );

		// Button size (btn_size)
		if ( ! empty( $instance['btn_size'] ) ) {
			$button_class .= ' btn-' . sanitize_html_class( $instance['btn_size'] );
		}

		// Alignment
		if ( $instance['btn_alignment'] == 'block' ) {
			$button_class .= " d-block w-100";
		} else if ( $instance['btn_alignment'] == 'left' ) {
			$button_class .= ( $aui_bs5 ? ' float-start ms-2 ' : ' float-left mr-2 ' );
		} else if ( $instance['btn_alignment'] == 'right' ) {
			$button_class .= ( $aui_bs5 ? ' float-end me-2 ' : ' float-right ml-2 ' );
		} else if ( $instance['btn_alignment'] == 'center' ) {
			$button_class .= " mw-100 d-block mx-auto ";
		}

		$wrap_class = geodir_build_aui_class( $instance ) . ' geodir-save-search-btn-wrap';

		if ( ! empty( $instance['css_class'] ) ) {
			$wrap_class .= ' ' . geodir_sanitize_html_class( $instance['css_class'] );
		}

		$post_type = geodir_get_current_posttype();

		if ( empty( $instance['is_preview'] ) ) {
			$extra_params = array( 'url' => esc_url( geodir_curPageURL() ), 'post_type' => $post_type );

			if ( ! geodir_is_page( 'search' ) ) {
				// Terms
				if ( is_tax() && ( $queried_object = get_queried_object() ) ) {
					if ( ! empty( $queried_object->taxonomy ) ) {
						if ( $queried_object->taxonomy == $post_type . 'category' ) {
							$extra_params['post_category'] = ',' . (int) $queried_object->term_id . ',';
						} elseif ( $queried_object->taxonomy == $post_type . '_tags' ) {
							$extra_params['post_tags'] = $queried_object->slug;
						}
					}
				}

				// Locations
				if ( GeoDir_Post_types::supports( $post_type, 'location' ) && ( $location_terms = geodir_get_current_location_terms( 'query_vars' ) ) ) {
					foreach ( array( 'country', 'region', 'city', 'neighbourhood' ) as $type ) {
						if ( ! empty( $location_terms[ $type ] ) ) {
							$extra_params[ $type ] = esc_attr( $location_terms[ $type ] );
						}
					}
				}

				// Events
				if ( GeoDir_Post_types::supports( $post_type, 'events' ) ) {
					$event_type = ! empty( $_REQUEST['etype'] ) ? sanitize_text_field( $_REQUEST['etype'] ) : geodir_get_option( 'event_default_filter' );

					if ( ( $gd_event_type = get_query_var( 'gd_event_type' ) ) ) {
						$event_type = $gd_event_type;
					}

					if ( ! empty( $event_type ) ) {
						$extra_params['etype'] = esc_attr( $event_type );
					}
				}
			}

			$extra = json_encode( $extra_params );
			$extra = apply_filters( 'geodir_save_search_extra_params', $extra, $extra_params, $instance );

			$onclick = "geodir_save_search_aui_modal('" . esc_attr( addslashes( $default_text ) ) . "','" . esc_attr( addslashes( $extra ) ) . "'); return false;";
		} else {
			$onclick = "";
		}

		$template_args = array(
			'instance' => $instance,
			'wrap_class' => trim( $wrap_class ),
			'button_class' => $button_class,
			'onclick' => $onclick
		);

		$output = geodir_get_template_html( $design_style . '/save-search-button.php', $template_args, '', geodir_save_search_templates_path() );

		return $output;
	}

	public static function add_script() {
		global $gd_save_search_script;

		if ( ! empty( $gd_save_search_script ) ) {
			return;
		}

		$gd_save_search_script = true;

		GeoDir_Save_Search_Post::get_widget_script( 'gd_save_search' );
	}
}
