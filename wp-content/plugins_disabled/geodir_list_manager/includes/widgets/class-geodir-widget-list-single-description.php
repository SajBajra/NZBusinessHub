<?php
/**
 * List description.
 *
 * @since 2.3.1
 * @package GeoDir_List_Manager
 * @author AyeCode Ltd
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * GeoDir_Widget_List_Single_Description class.
 */
class GeoDir_Widget_List_Single_Description extends WP_Super_Duper {

	public $arguments;

	/**
	 * Widget constructor.
	 */
	public function __construct() {
		$options = array(
			'textdomain'    => GEODIRECTORY_TEXTDOMAIN,
			'block-icon'    => 'menu',
			'block-category'=> 'geodirectory',
			'block-keywords'=> "['geo','description','list']",
			'class_name'    => __CLASS__,
			'base_id'       => 'gd_list_single_description',
			'name'          => __( 'GD > List Description', 'gd-lists' ),
			'widget_ops'    => array(
				'classname'   => 'geodir-list-description-container ' . geodir_bsui_class(),
				'description' => esc_html__( 'Shows the description on list single page.', 'gd-lists' ),
				'customize_selective_refresh' => true,
				'geodirectory' => true
			)
		);

		parent::__construct( $options );
	}

	/**
	 * Set widget arguments.
	 */
	public function set_arguments() {
		$design_style = geodir_design_style();

		$arguments = array(
			'title' => array(
				'title' => __( 'Title:', 'gd-lists' ),
				'desc' => __( 'Extra main title if needed.', 'gd-lists' ),
				'type' => 'text',
				'placeholder' => __( 'Extra main title if needed.', 'gd-lists' ),
				'default'  => '',
				'desc_tip' => true
			),
			'limit' => array(
				'title' => __( 'Word limit:', 'gd-lists' ),
				'desc' => __( 'How many words to limit the text to. (will auto strip tags)', 'gd-lists' ),
				'type' => 'number',
				'placeholder'  => '',
				'desc_tip' => true,
				'advanced' => false
			),
			'strip_tags' => array(
				'title' => __( 'Strip Tags:', 'gd-lists' ),
				'desc' => __( 'Strip tags from description.', 'gd-lists' ),
				'type' => 'checkbox',
				'desc_tip' => true,
				'value' => '1',
				'default' => '0'
			)
		);

		$arguments['alignment'] = array(
			'title' => __( 'Text Align:', 'gd-lists'),
			'desc' => __( 'How the text should be aligned.', 'gd-lists'),
			'type' => 'select',
			'options' =>  array(
				"" => __( 'None', 'gd-lists' ),
				"left" => __( 'Left', 'gd-lists' ),
				"center" => __( 'Center', 'gd-lists' ),
				"right" => __( 'Right', 'gd-lists' ),
				"justify" => __( 'Justify', 'gd-lists' )
			),
			'desc_tip' => true
		);

		if ( $design_style ) {
			// text color
			$arguments['text_color'] = geodir_get_sd_text_color_input( array( 'group' => __( 'General', 'gd-lists' ) ) );

			// background
			$arguments['bg']  = geodir_get_sd_background_input();

			// margins
			$arguments['mt']  = geodir_get_sd_margin_input( 'mt' );
			$arguments['mr']  = geodir_get_sd_margin_input( 'mr' );
			$arguments['mb']  = geodir_get_sd_margin_input( 'mb', array( 'default' => 3 ) );
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
	 * Outputs the widget content on the front-end.
	 *
	 * @param array $args Widget settings.
	 * @param array $widget_args Widget display arguments.
	 * @param string $content
	 *
	 * @return mixed|string|void
	 */
	public function output( $args = array(), $widget_args = array(), $content = '' ) {
		global $post, $geodir_list_post, $geodir_list_post_content;

		$design_style = geodir_design_style();

		$defaults = array(
			'title' => '',
			'strip_tags' => '',
			'limit' => '',
			'alignment' => '',
			// AUI
			'text_color' => '',
			'bg' => '',
			'mt' => '',
			'mr' => '',
			'mb' => '3',
			'ml' => '',
			'pt' => '',
			'pr' => '',
			'pb' => '',
			'pl' => ''
		);

		$args = shortcode_atts( $defaults, $args, 'gd_list_description' );

		if ( ! empty( $post ) && ! empty( $post->post_type ) && $post->post_type == 'gd_list' && ( is_single() || ! empty( $geodir_list_post ) ) ) {
			$post_content = ! empty( $geodir_list_post_content ) && isset( $geodir_list_post_content[ $post->ID ] ) ? $geodir_list_post_content[ $post->ID ] : $post->post_content;

			if ( ! empty( $post_content ) ) {
				$gd_skip_the_content = true;

				$post_content = apply_filters( 'the_content', stripslashes( $post_content ), $post->ID );

				if ( ! empty( $args['strip_tags'] ) ) {
					$post_content = wp_strip_all_tags( $post_content );
				}
			}
		} else if ( $this->is_preview() ) {
			$post_content = __( 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Etiam tristique vitae sem ac aliquet. Quisque in posuere quam. Nullam accumsan augue nec ultricies dapibus. Sed facilisis sapien neque, sed venenatis mi egestas ut. Nullam non ligula sodales, feugiat eros sed, pharetra tellus. Duis enim felis, pharetra eget congue a, viverra vitae sem. Cras viverra, augue ut sagittis tincidunt, erat dolor sollicitudin mi, ac eleifend libero orci sed risus.', 'gd-lists' );
		} else {
			$post_content = '';
		}

		$post_content = apply_filters( 'geodir_list_single_description_content', $post_content, $args, $widget_args, $content, $this->is_preview() );

		if ( empty( $post_content ) ) {
			return;
		}

		if ( ! empty( $args['limit'] ) ) {
			$limit = absint( $args['limit'] );

			$post_content = wp_trim_words( $post_content, $limit, '' );
		}

		if ( $design_style ) {
			$wrap_class = 'position-relative ' . geodir_build_aui_class( $args );

			if ( $args['alignment'] != '' ) {
				$wrap_class .= ' text-' . sanitize_html_class( $args['alignment'] );
			} else {
				$wrap_class .= ' clear-both';
			}
		} else {
			$wrap_class = 'geodir-list-content-text';

			if ( $args['alignment'] != '' ) {
				$wrap_class .= ' geodir-text-align' . sanitize_html_class( $args['alignment'] );
			} else {
				$wrap_class .= ' clear-both';
			}
		}

		$output = '<div class="' . $wrap_class . '">';
		$output .= $post_content;
		$output .= '</div>';

		return $output;
	}
}
