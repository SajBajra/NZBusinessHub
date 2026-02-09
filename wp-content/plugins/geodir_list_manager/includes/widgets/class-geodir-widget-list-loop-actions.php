<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * GeoDirectory Search widget.
 *
 * @since 1.0.0
 */
class GeoDir_Widget_List_Loop_Actions extends WP_Super_Duper {

	/**
	 * Register the advanced search widget with WordPress.
	 *
	 */
	public function __construct() {

		$options = array(
			'textdomain'     => GEODIRECTORY_TEXTDOMAIN,
			'block-icon'     => 'list-view',
			'block-category' => 'geodirectory',
			'block-keywords' => "['list loop','lists','geodir']",
			'block-supports' => array(
				'customClassName' => false,
			),
			'class_name'    => __CLASS__,
			'base_id'       => 'gd_list_loop_actions',
			'name'          => __( 'GD > List Loop Actions', 'gd-lists' ),
			'widget_ops'    => array(
				'classname'    => 'geodir-list-loop-actions-container' . ( geodir_design_style() ? ' bsui' : '' ),
				'description'  => esc_html__( 'Shows the actions available to the user on a list page, like the author actions like edit and delete list.', 'gd-lists' ),
				'geodirectory' => true,
			)
		);

		parent::__construct( $options );
	}

	/**
	 * Set widget arguments.
	 */
	public function set_arguments() {
		$design_style = geodir_design_style();

		$arguments['alignment'] = array(
			'title' => __( 'Button Align:', 'gd-lists'),
			'desc' => __( 'How the buttons should be aligned.', 'gd-lists'),
			'type' => 'select',
			'options' =>  array(
				"" => __( 'Default (Right)', 'gd-lists' ),
				"left" => __( 'Left', 'gd-lists' ),
				"center" => __( 'Center', 'gd-lists' ),
				"block" => __( 'Block', 'gd-lists' ),
				"none" => __( 'None', 'gd-lists' )
			),
			'desc_tip' => true
		);

		if ( $design_style ) {
			$arguments['size'] = array(
				'type' => 'select',
				'title' => __( 'Button Size:', 'gd-lists' ),
				'desc' => __( 'Size of the buttons.', 'gd-lists' ),
				'options' => array(
					"" => __( 'Small (default)', 'gd-lists' ),
					"medium" => __( 'Medium', 'gd-lists' ),
					"large" => __( 'Large', 'gd-lists' ),
				),
				'default' => '',
				'desc_tip' => true,
				'advanced' => false,
				'group' => __( 'Design', 'geodirectory' ),
			);

			// button margins
			$arguments['btn_mt']  = geodir_get_sd_margin_input( 'mt', array( 'group' => __( 'Design', 'geodirectory' ) ), false );
			$arguments['btn_mr']  = geodir_get_sd_margin_input( 'mr', array( 'group' => __( 'Design', 'geodirectory' ), 'default' => 1 ), false );
			$arguments['btn_mb']  = geodir_get_sd_margin_input( 'mb', array( 'group' => __( 'Design', 'geodirectory' ) ), false );
			$arguments['btn_ml']  = geodir_get_sd_margin_input( 'ml', array( 'group' => __( 'Design', 'geodirectory' ), 'default' => 1 ), false );

			// margins
			$arguments['mt']  = geodir_get_sd_margin_input( 'mt' );
			$arguments['mr']  = geodir_get_sd_margin_input( 'mr' );
			$arguments['mb']  = geodir_get_sd_margin_input( 'mb', array( 'default' => 4 ) );
			$arguments['ml']  = geodir_get_sd_margin_input( 'ml' );

			// padding
			$arguments['pt']  = geodir_get_sd_padding_input( 'pt' );
			$arguments['pr']  = geodir_get_sd_padding_input( 'pr' );
			$arguments['pb']  = geodir_get_sd_padding_input( 'pb' );
			$arguments['pl']  = geodir_get_sd_padding_input( 'pl' );
		}

		return $arguments;
	}

	/**
	 * The Super block output function.
	 *
	 * @param array $args
	 * @param array $widget_args
	 * @param string $content
	 *
	 * @return mixed|string|void
	 */
	public function output( $args = array(), $widget_args = array(), $content = '' ) {
		global $post, $geodir_list_post;

		if ( ( ( is_single() || ! empty( $geodir_list_post ) ) && isset( $post->post_type ) && $post->post_type == 'gd_list' ) || $this->is_preview() ) {
			$defaults = array(
				'alignment' => 'right',
				// AUI
				'size' => '',
				'btn_mt' => '',
				'btn_mb' => '',
				'btn_mr' => '1',
				'btn_ml' => '1',
				'mt' => '',
				'mr' => '',
				'mb' => '4',
				'ml' => '',
				'pt' => '',
				'pr' => '',
				'pb' => '',
				'pl' => ''
			);

			$args = shortcode_atts( $defaults, $args, 'gd_list_loop_actions' );

			if ( empty( $args['alignment'] ) ) {
				$args['alignment'] = 'right';
			} else if ( $args['alignment'] == 'none' ) {
				$args['alignment'] = '';
			}

			// size
			if ( $args['size'] == 'medium' ) {
				$args['size'] = '';
			} elseif ( $args['size'] == 'large' ) {
				$args['size'] = 'lg';
			} else {
				$args['size'] = 'sm';
			}

			ob_start();

			do_action( 'geodir_lists_before_loop_actions', $args );
			do_action( 'geodir_lists_loop_actions', $args );
			do_action( 'geodir_lists_after_loop_actions', $args );

			$output = ob_get_clean();

			if ( $output != '' ) {
				$output = trim( $output );
			}

			return $output;
		}

		return;
	}

}