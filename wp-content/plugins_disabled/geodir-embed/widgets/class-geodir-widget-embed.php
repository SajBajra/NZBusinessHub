<?php
/**
 * GeoDirectory Embed code Widget
 *
 * @since 2.0.0
 *
 * @package GeoDir_Embed
 */

/**
 * GeoDir_Widget_Embed class.
 *
 * @since 2.0.0
 */
class GeoDir_Widget_Embed extends WP_Super_Duper {

	public $arguments;

	/**
	 * Sets up the widgets name etc
	 */
	public function __construct() {

		$badgesize = array();
		$design_style 	= geodir_design_style();
		
		if( $design_style ){
			$badgesize = array(
				"h6"	=> __( 'Small', 'geodir-embed' ),
				"h5"	=> __( 'Normal', 'geodir-embed' ),
				"h4"	=> __( 'Medium', 'geodir-embed' ),
				"h3"	=> __( 'Large', 'geodir-embed' ),
				"h2"	=> __( 'Extra Large', 'geodir-embed' ),
			);
		}else{
			$badgesize = array(
				"small"       => __( 'Small', 'geodir-embed' ),
				""            => __( 'Normal', 'geodir-embed' ),
				"medium"      => __( 'Medium', 'geodir-embed' ),
				"large"       => __( 'Large', 'geodir-embed' ),
				"extra-large" => __( 'Extra Large', 'geodir-embed' ),
			);
		}
						

		$options = array(
			'textdomain'     => GEODIRECTORY_TEXTDOMAIN, // still used so we can add GD settings.
			'block-icon'     => 'admin-site',
			'block-category' => 'geodirectory',
			'block-keywords' => "['embed','link','geodir']",
			'class_name'     => __CLASS__,
			'base_id'        => 'gd_embed', // this us used as the widget id and the shortcode id.
			'name'           => __( 'GD > Embed', 'geodir-embed' ), // the name of the widget.
			'widget_ops'     => array(
				'classname'                   => 'geodir-embed '.geodir_bsui_class(),
				// widget class
				'description'                 => esc_html__( 'This shows a button to get embed code for a listing', 'geodir-embed' ),
				// widget description
				'customize_selective_refresh' => true,
				'geodir-embed'                => true,
				'gd_wgt_showhide'             => 'show_on',
				'gd_wgt_restrict'             => array( 'gd-detail' ),
			),
			'arguments'      => array(
				'badge'               => array(
					'type'        => 'text',
					'title'       => __( 'Badge:', 'geodir-embed' ),
					'desc'        => __( 'Embed badge text.', 'geodir-embed' ),
					'placeholder' => __( "Get embed code", "goedir-embed" ),
					'default'     => '',
					'desc_tip'    => true,
					'advanced'    => false
				),
				'icon_class'          => array(
					'type'        => 'text',
					'title'       => __( 'Icon class:', 'geodir-embed' ),
					'desc'        => __( 'You can show a font-awesome icon here by entering the icon class.', 'geodir-embed' ),
					'placeholder' => 'fas fa-code',
					'default'     => '',
					'desc_tip'    => true,
					'advanced'    => true
				),
				'show_to'             => array(
					'type'     => 'select',
					'title'    => __( 'Show to:', 'geodir-embed' ),
					'desc'     => __( 'Sets who can see the button. (allays visible to admins)', 'geodir-embed' ),
					'options'  => array(
						""      => __( 'All', 'geodir-embed' ),
						"user"  => __( 'Logged in users', 'geodir-embed' ),
						"owner" => __( 'Owner', 'geodir-embed' ),
					),
					'default'  => '',
					'desc_tip' => true,
					'advanced' => false
				),
				'bg_color'            => array(
					'type'        => 'color',
					'title'       => __( 'Badge background color:', 'geodir-embed' ),
					'desc'        => __( 'Color for the badge background.', 'geodir-embed' ),
					'placeholder' => '',
					'default'     => '#0073aa',
					'desc_tip'    => true,
					'advanced'    => true
				),
				'txt_color'           => array(
					'type'        => 'color',
					'title'       => __( 'Badge text color:', 'geodir-embed' ),
					'desc'        => __( 'Color for the badge text.', 'geodir-embed' ),
					'placeholder' => '',
					'desc_tip'    => true,
					'default'     => '#ffffff',
					'advanced'    => true
				),
				'size'                => array(
					'type'     => 'select',
					'title'    => __( 'Badge size:', 'geodir-embed' ),
					'desc'     => __( 'Size of the badge.', 'geodir-embed' ),
					'options'  => $badgesize,
					'default'  => '',
					'desc_tip' => true,
					'advanced' => true
				),
				'alignment'           => array(
					'type'     => 'select',
					'title'    => __( 'Alignment:', 'geodir-embed' ),
					'desc'     => __( 'How the item should be positioned on the page.', 'geodir-embed' ),
					'options'  => array(
						""       => __( 'None', 'geodir-embed' ),
						"left"   => __( 'Left', 'geodir-embed' ),
						"center" => __( 'Center', 'geodir-embed' ),
						"right"  => __( 'Right', 'geodir-embed' ),
					),
					'desc_tip' => true,
					'advanced' => true
				),
				'list_hide'           => array(
					'title'    => __( 'Hide item on view:', 'geodir-embed' ),
					'desc'     => __( 'You can set at what view the item will become hidden.', 'geodir-embed' ),
					'type'     => 'select',
					'options'  => array(
						""  => __( 'None', 'geodir-embed' ),
						"2" => __( 'Grid view 2', 'geodir-embed' ),
						"3" => __( 'Grid view 3', 'geodir-embed' ),
						"4" => __( 'Grid view 4', 'geodir-embed' ),
						"5" => __( 'Grid view 5', 'geodir-embed' ),
					),
					'desc_tip' => true,
					'advanced' => true
				),
				'list_hide_secondary' => array(
					'title'    => __( 'Hide secondary info on view', 'geodir-embed' ),
					'desc'     => __( 'You can set at what view the secondary info such as label will become hidden.', 'geodir-embed' ),
					'type'     => 'select',
					'options'  => array(
						""  => __( 'None', 'geodir-embed' ),
						"2" => __( 'Grid view 2', 'geodir-embed' ),
						"3" => __( 'Grid view 3', 'geodir-embed' ),
						"4" => __( 'Grid view 4', 'geodir-embed' ),
						"5" => __( 'Grid view 5', 'geodir-embed' ),
					),
					'desc_tip' => true,
					'advanced' => true
				),
				'css_class'           => array(
					'type'        => 'text',
					'title'       => __( 'Extra class:', 'geodir-embed' ),
					'desc'        => __( 'Give the wrapper an extra class so you can style things as you want.', 'geodir-embed' ),
					'placeholder' => '',
					'default'     => '',
					'desc_tip'    => true,
					'advanced'    => true,
				),
			)
		);

		parent::__construct( $options );
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
		global $aui_bs5, $gd_post;

		$defaults = array(
			'show'                => '', // icon, text
			'alignment'           => '', // left, center, right
			'list_hide'           => '',
			'list_hide_secondary' => '',
			'badge'               => __( "Get embed code", "goedir-embed" ),
			'show_to'             => '',
		);

		/**
		 * Parse incoming $args into an array and merge it with $defaults
		 */
		$args			= wp_parse_args( $args, $defaults );
		$user_id		= get_current_user_id();
		$post_author	= isset( $gd_post->post_author ) ? absint( $gd_post->post_author ) : '';
		$post_id		= isset( $gd_post->ID ) ? absint( $gd_post->ID ) : '';
		$design_style 	= geodir_design_style();

		// Bail if no post id or not on the details page
		if ( ! $post_id || ! geodir_is_page( 'single' ) ) {
			return '';
		}

		// Show to checks
		if ( $args['show_to'] == 'user' && ! $user_id ) {
			return '';
		} else if ( $args['show_to'] == 'owner' && $post_author && $post_author != $user_id && ! current_user_can( 'edit_others_posts' ) ) {
			return '';
		}

		if ( ! apply_filters( 'geodir_embed_check_widget_display', true, $args, $gd_post ) ) {
			return;
		}

		$builder_url     = admin_url( "admin-ajax.php?action=geodir_embed_builder&post_id=$post_id" );
		if ( empty( $args['icon_class'] ) && empty( $args['badge'] ) ) {
			$args['badge'] = __( "Get embed code", "goedir-embed" );
		}
		$args['link'] = 'javascript:void(0);';

		if( $design_style ) {
			$bs_prefix = $aui_bs5 ? 'bs-' : '';

			$args['extra_attributes'] = array(
				"data-" . $bs_prefix . "toggle" => 'modal',
				"data-" . $bs_prefix . "target" => '#embedModal',
				"data-builder_url" => $builder_url
			);
		}else{
			$args['onclick'] = "lity('$builder_url');return false;";
		}

		// set list_hide class
		if ( $args['list_hide'] == '2' ) {
			$args['css_class'] .= " gd-lv-2 ";
		}
		if ( $args['list_hide'] == '3' ) {
			$args['css_class'] .= " gd-lv-3 ";
		}
		if ( $args['list_hide'] == '4' ) {
			$args['css_class'] .= " gd-lv-4 ";
		}
		if ( $args['list_hide'] == '5' ) {
			$args['css_class'] .= " gd-lv-5 ";
		}

		// set list_hide_secondary class
		if ( $args['list_hide_secondary'] == '2' ) {
			$args['css_class'] .= " gd-lv-s-2 ";
		}
		if ( $args['list_hide_secondary'] == '3' ) {
			$args['css_class'] .= " gd-lv-s-3 ";
		}
		if ( $args['list_hide_secondary'] == '4' ) {
			$args['css_class'] .= " gd-lv-s-4 ";
		}
		if ( $args['list_hide_secondary'] == '5' ) {
			$args['css_class'] .= " gd-lv-s-5 ";
		}

		$output = geodir_get_post_badge( $post_id, $args );

		// Include the modal content.
		if ( $output && $design_style ) {
			$template_args = array();

			ob_start();
			geodir_get_template( $design_style . '/embed-modal.php', $template_args, '', plugin_dir_path( GEODIR_EMBED_PLUGIN_FILE ). "/templates/" );
			$output .= ob_get_clean();
		}

		return $output;
	}
}
