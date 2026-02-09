<?php
/**
 * Contains the bookings availability widget class.
 *
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * GeoDir_Booking_Availability_Widget class.
 *
 * @since 1.0.0
 */
class GeoDir_Booking_Availability_Widget extends WP_Super_Duper {

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
			'base_id'        => 'gd_booking_availability',										// this is used as the widget id and the shortcode id.
			'name'           => __( 'GD > Booking Calendar', 'geodir-booking' ),				    // the name of the widget.
			'widget_ops'     => array(
				'classname'       => 'geodir-booking-setup-wrapper bsui',                                     	// widget class
				'description'     => esc_html__( 'Displays a calendar to view available days, the booking form is also required.', 'geodir-booking' ),   // widget description
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

		$arguments['lightbox_notice'] = array(
			'type'            => 'notice',
			'desc'            => __( 'The GD > Booking Form block is also required on the page to complete a booking', 'geodir-booking' ),
			'status'          => 'warning',
			'group'           => __( 'Display', 'geodir-booking' ),
//			'element_require' => '[%type%]=="lightbox"',
		);

		$arguments['id'] = array(
				'type'        => 'number',
				'title'       => __( 'Listing ID:', 'geodir-booking' ),
				'desc'        => __( 'Leave blank to use the listing being viewed.', 'geodir-booking' ),
				'placeholder' => __( 'Listing being viewed', 'geodir-booking' ),
				'default'     => '',
				'desc_tip'    => true,
				'advanced'    => false,
				'group'    => __( 'Display', 'geodir-booking' ),
		);

		$arguments = $arguments + sd_get_background_inputs( 'bg', array(), false, false, false );

		// text color
		$arguments['text_color'] = sd_get_text_color_input();

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

		return apply_filters( 'geodir_booking_availability_widget_arguments', $arguments );
	}

	/**
	 * Outputs the bookings calendar.
	 *
	 * @param array $args
	 * @param array $widget_args
	 * @param string $content
	 *
	 * @return mixed|string|void
	 */
	public function output( $args = array(), $widget_args = array(), $content = '' ) {
		global $post, $gd_post;

		// Ensure we have an array.
		if ( ! is_array( $args ) ) {
			$args = array();
		}

		$wrap_class = sd_build_aui_class( $args );

		// Maybe generate preview.
		if ( $this->is_preview() ) {
			return aui()->alert( array(
				'type'    => 'info',
				'class' => $wrap_class,
				'content' => __( 'Booking availability calendar will appear here. ( The "GD > Booking Form" block is also required for booking )', 'geodir-booking' ),
			) );
		}

		// Prepare the listing.
		if ( ! empty( $args['id'] ) ) {
			$post_id = absint( $args['id'] );
		} else if ( ! empty( $post->ID ) ) {
			$post_id = $post->ID;
		} else if ( ! empty( $gd_post->ID ) ) {
			$post_id = $gd_post->ID;
		} else {
			return '';
		}

		$post_id = geodir_booking_post_id( $post_id );

		// Abort if booking is not enabled.
		if ( ! geodir_booking_is_enabled( $post_id ) ) {
			return '';
		}

		// Ensure this is a GD post.
		$listing = get_post( $post_id );

		if ( empty( $listing ) || 'publish' !== $listing->post_status || ! in_array( $listing->post_type, geodir_get_posttypes() )  ){
			return '';
		}

		/**
		 * Filters the widget output before the original output.
		 *
		 * @since 1.0.0
		 *
		 * @param string $output Pre output. Default NULL.
		 * @param array  $args Widget parameters.
		 * @param array  $widget_args Widget arguments.
		 * @param string $content Widget content.
		 */
		$output = apply_filters( 'geodir_booking_availability_pre_widget_output', null, $args, $widget_args, $content, $listing );
		if ( $output !== NULL ) {
			return $output;
		}

		$GLOBALS['geodir_booking_load_availability_app'] = true;

		$rooms = geodir_get_listing_rooms( $listing->ID );

		if ( empty( $rooms ) ) {
			return geodir_get_template_html( 'booking-availability.php', array( 'listing' => $listing, 'wrap_class' => $wrap_class ), 'geodir-booking', plugin_dir_path( GEODIR_BOOKING_FILE ) . 'templates' );
		}

		$default   = current( $rooms );
		$options   = array();
		$templates = '';

		foreach ( $rooms as $room ) {
			$template = geodir_get_template_html( 'booking-availability.php', array( 'listing' => get_post( $room ), 'wrap_class' => $wrap_class ), 'geodir-booking', plugin_dir_path( GEODIR_BOOKING_FILE ) . 'templates' );
			$templates .= '<div class="geodir-booking-form-room-container" data-id="'.absint( $room ).'" style="display: none;">' . $template . '</div>';
			$options[ $room ] = get_the_title( $room );
		}

		$return  = '<div class="geodir-booking-form-rooms-container geodir-booking-form-rooms-container__availability ' . esc_attr( $wrap_class ) . '" data-parent="'. absint(  $listing->ID  ). '">' . $templates;
		$return .= aui()->select(
			array(
				'id'          => wp_unique_id( 'booking_room__' ),
				'value'       => $default,
				'label'       => __( 'Room type', 'geodir-booking' ),
				'label_type'  => 'top',
				'label_class' => 'form-label',
				'class'       => 'geodir-booking-room-select',
				'placeholder' => __( 'Select a room', 'geodir-booking' ),
				'options'     => $options,
			)
		);

		$GLOBALS['geodir_booking_added_room_selector_' . $listing->ID] = true;

		return $return . '</div>';
	}

}
