<?php
/**
 * User Bundle Widget
 *
 * @author    AyeCode Ltd
 * @package   GeoDir_Save_Search
 * @version   2.7.10
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * GeoDir_Pricing_Widget_User_Bundle class.
 */
class GeoDir_Pricing_Widget_User_Bundle extends WP_Super_Duper {

	public $arguments;

	/**
	 * Sets up the widgets name etc
	 */
	public function __construct() {

		$options = array(
			'base_id'        => 'gd_user_bundle',
			'name'           => __( 'GD > Bundle Posts', 'geodir_pricing' ),
			'class_name'     => __CLASS__,
			'textdomain'     => GEODIRECTORY_TEXTDOMAIN,
			'block-icon'     => 'fas fa-briefcase',
			'block-category' => 'geodirectory',
			'block-supports' => array(
				'customClassName' => false,
			),
			'block-keywords' => "['bundle','geodir','pricing']",
			'widget_ops'     => array(
				'classname'    => 'geodir-user-bundle-container' . ( geodir_design_style() ? ' bsui' : '' ),
				'description'  => esc_html__( 'Displays a posts allowed to add after purchase of bundle of listings.', 'geodir_pricing' ),
				'customize_selective_refresh' => true,
				'geodirectory' => true,
			),
			'block_group_tabs' => array(
				'content' => array(
					'groups' => array(
						__( 'Title', 'geodirectory' )
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
		$arguments = array();
		$arguments['title'] = array(
			'type' => 'text',
			'title' => __( 'Title:', 'geodirectory' ),
			'desc' => __( 'The widget title.', 'geodirectory' ),
			'default' => '',
			'desc_tip' => true,
			'group' => __( 'Title', 'geodirectory' )
		);

		$arguments['no_wrap'] = array(
			'type' => 'checkbox',
			'title' => __( 'Remove widget main wrapping div', 'geodir_pricing' ),
			'default' => '0',
			'desc_tip' => true,
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
		global $aui_bs5;

		$design_style = geodir_design_style();

		if ( ! $design_style ) {
			return;
		}

		$defaults = array(
			'title' => '',
			'no_wrap' => '',
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

		$instance = shortcode_atts( $defaults, $instance, 'gd_user_bundle' );

		if ( ! empty( $instance['is_preview'] ) ) {
			return '';
		}

		$output = GeoDir_Pricing_Bundle::display_user_bundle( $instance );

		if ( empty( $output ) ) {
			return;
		}

		return $output;
	}

	/**
	 * Get the post type options.
	 *
	 * @since 2.7.10
	 *
	 * @return array $options
	 */
	public function post_type_options() {
		$post_types = geodir_get_posttypes( 'options-plural' );

		$options = array();
		if ( ! empty( $post_types ) ) {
			$options = array( '' => __( 'All', 'geodir_pricing' ) );
			$options = array_merge( $options, $post_types );
		}

		return $options;
	}
}
