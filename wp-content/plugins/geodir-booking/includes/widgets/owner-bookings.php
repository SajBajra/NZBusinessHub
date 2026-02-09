<?php
/**
 * Contains the owner bookings widget class.
 *
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * GeoDir_Owner_Bookings_Widget class.
 *
 * @since 1.0.0
 */
class GeoDir_Owner_Bookings_Widget extends WP_Super_Duper {

	public $arguments;

	/**
	 * Sets up the widgets name etc
	 */
	public function __construct() {

		$options = array(
			'textdomain'     => 'geodir-booking',
			'block-icon'     => 'fas fa-book',
			'block-wrap'    => '', // the element to wrap the block output in. , ie: div, span or empty for no wrap
			'block-supports'=> array(
				'customClassName'   => false,
				'renaming'  => false,
			),
			'block-category' => 'geodirectory',
			'block-keywords' => "['booking','geodir','geodirectory','geodir']",
			'class_name'     => __CLASS__,
			'base_id'        => 'gd_owner_bookings',										// this is used as the widget id and the shortcode id.
			'name'           => __( 'GD > Owner Bookings', 'geodir-booking' ),				    // the name of the widget.
			'widget_ops'     => array(
				'classname'       => 'geodir-owner-bookings-wrapper bsui',                         // widget class
				'description'     => esc_html__( 'Displays all bookings for listings by the current user', 'geodir-booking' ),   // widget description
				'gd_is_booking'   => true,
			),
			'block_group_tabs'   => array(
				'content'  => array(
					'groups' => array( __( 'Display', 'geodir-booking' ) ),
					'tab'    => array(
						'title'     => __( 'Content', 'geodir-booking' ),
						'key'       => 'bs_tab_content',
						'tabs_open' => true,
						'open'      => true,
						'class'     => 'text-center flex-fill d-flex justify-content-center',
					),
				),
				'styles'   => array(
					'groups' => array(
						__( 'Background', 'geodir-booking' ),
						__( 'Typography', 'geodir-booking' ),
					),
					'tab'    => array(
						'title'     => __( 'Styles', 'geodir-booking' ),
						'key'       => 'bs_tab_styles',
						'tabs_open' => true,
						'open'      => true,
						'class'     => 'text-center flex-fill d-flex justify-content-center',
					),
				),
				'advanced' => array(
					'groups' => array( __( 'Wrapper Styles', 'geodir-booking' ), __( 'Advanced', 'geodir-booking' ) ),
					'tab'    => array(
						'title'     => __( 'Advanced', 'geodir-booking' ),
						'key'       => 'bs_tab_advanced',
						'tabs_open' => true,
						'open'      => true,
						'class'     => 'text-center flex-fill d-flex justify-content-center',
					),
				),
			),
		);

		parent::__construct( $options );
	}

	/**
	 * Set widget arguments.
	 *
	 */
	public function set_arguments() {

		$arguments = array(

			'id' => array(
				'type'        => 'number',
				'title'       => __( 'User ID:', 'geodir-booking' ),
				'desc'        => __( "Leave blank to use the current user's ID.", 'geodir-booking' ),
				'placeholder' => __( 'Current user ID', 'geodir-booking' ),
				'default'     => '',
				'desc_tip'    => true,
				'advanced'    => false,
				'group'    => __( 'Display', 'geodir-booking' ),
			),

		);

		$arguments = $arguments + sd_get_background_inputs( 'bg', array(), false, false, false );

		// text color
//		$arguments['text_color'] = sd_get_text_color_input(); // does not work with tables very well

		// margins mobile
		$arguments['mt'] = sd_get_margin_input( 'mt', array( 'device_type' => 'Mobile' ) );
		$arguments['mr'] = sd_get_margin_input( 'mr', array( 'device_type' => 'Mobile' ) );
		$arguments['mb'] = sd_get_margin_input( 'mb', array( 'device_type' => 'Mobile' ) );
		$arguments['ml'] = sd_get_margin_input( 'ml', array( 'device_type' => 'Mobile' ) );

		// margins tablet
		$arguments['mt_md'] = sd_get_margin_input( 'mt', array( 'device_type' => 'Tablet' ) );
		$arguments['mr_md'] = sd_get_margin_input( 'mr', array( 'device_type' => 'Tablet' ) );
		$arguments['mb_md'] = sd_get_margin_input( 'mb', array( 'device_type' => 'Tablet' ) );
		$arguments['ml_md'] = sd_get_margin_input( 'ml', array( 'device_type' => 'Tablet' ) );

		// margins desktop
		$arguments['mt_lg'] = sd_get_margin_input( 'mt', array( 'device_type' => 'Desktop' ) );
		$arguments['mr_lg'] = sd_get_margin_input( 'mr', array( 'device_type' => 'Desktop' ) );
		$arguments['mb_lg'] = sd_get_margin_input( 'mb', array( 'device_type' => 'Desktop' ) );
		$arguments['ml_lg'] = sd_get_margin_input( 'ml', array( 'device_type' => 'Desktop' ) );

		// padding
		$arguments['pt'] = sd_get_padding_input( 'pt', array( 'device_type' => 'Mobile' ) );
		$arguments['pr'] = sd_get_padding_input( 'pr', array( 'device_type' => 'Mobile' ) );
		$arguments['pb'] = sd_get_padding_input( 'pb', array( 'device_type' => 'Mobile' ) );
		$arguments['pl'] = sd_get_padding_input( 'pl', array( 'device_type' => 'Mobile' ) );

		// padding tablet
		$arguments['pt_md'] = sd_get_padding_input( 'pt', array( 'device_type' => 'Tablet' ) );
		$arguments['pr_md'] = sd_get_padding_input( 'pr', array( 'device_type' => 'Tablet' ) );
		$arguments['pb_md'] = sd_get_padding_input( 'pb', array( 'device_type' => 'Tablet' ) );
		$arguments['pl_md'] = sd_get_padding_input( 'pl', array( 'device_type' => 'Tablet' ) );

		// padding desktop
		$arguments['pt_lg'] = sd_get_padding_input( 'pt', array( 'device_type' => 'Desktop' ) );
		$arguments['pr_lg'] = sd_get_padding_input( 'pr', array( 'device_type' => 'Desktop' ) );
		$arguments['pb_lg'] = sd_get_padding_input( 'pb', array( 'device_type' => 'Desktop' ) );
		$arguments['pl_lg'] = sd_get_padding_input( 'pl', array( 'device_type' => 'Desktop' ) );

		// border
		$arguments['border']       = sd_get_border_input( 'border' );
		$arguments['rounded']      = sd_get_border_input( 'rounded' );
		$arguments['rounded_size'] = sd_get_border_input( 'rounded_size' );

		// shadow
		$arguments['shadow'] = sd_get_shadow_input( 'shadow' );

		// advanced
		$arguments['anchor'] = sd_get_anchor_input();

		$arguments['css_class'] = sd_get_class_input();


		return apply_filters( 'geodir_owner_bookings_widget_arguments', $arguments );
	}

	/**
	 * Outputs the owner's bookings.
	 *
	 * @param array $args
	 * @param array $widget_args
	 * @param string $content
	 *
	 * @return mixed|string|void
	 */
	public function output( $args = array(), $widget_args = array(), $content = '' ) {

		// Ensure we have an array.
		if ( ! is_array( $args ) ) {
			$args = array();
		}

		// Prepare the owner.
		if ( ! empty( $args['id'] ) ) {
			$user_id = absint( $args['id'] );
		} else if ( is_user_logged_in() ) {
			$user_id = get_current_user_id();
		} else {
			return aui()->alert( array(
				'type'    => 'warning',
				'content' => __( 'Log in to view bookings.', 'geodir-booking' ),
			) );
		}

		$GLOBALS['geodir_booking_load_all_owner_bookings_script'] = true;
		$wrap_class = sd_build_aui_class( $args );
		return geodir_get_template_html( 'owner-bookings.php', array( 'wrap_class' => $wrap_class, 'user_id' => $user_id, 'is_preview' => $this->is_preview() ), 'geodir-booking', plugin_dir_path( GEODIR_BOOKING_FILE ) . 'templates' );
	}

}
