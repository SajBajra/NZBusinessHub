<?php
/**
 * Contains the booking widget class.
 *
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * GeoDir_Booking_Widget class.
 *
 * @since 1.0.0
 */
class GeoDir_Booking_Widget extends WP_Super_Duper {

	public $arguments;

	/**
	 * Sets up the widgets name etc
	 */
	public function __construct() {

		$options = array(
			'textdomain'       => 'geodir-booking',
			'block-icon'       => 'fas fa-book',
			'block-wrap'       => '', // the element to wrap the block output in. , ie: div, span or empty for no wrap
			'block-supports'   => array(
				'customClassName' => false,
				'renaming'        => false,
			),
			'block-category'   => 'geodirectory',
			'block-keywords'   => "['booking','geodir','geodirectory','geodir']",
			'class_name'       => __CLASS__,
			'base_id'          => 'gd_booking',                                       // this is used as the widget id and the shortcode id.
			'name'             => __( 'GD > Booking Form', 'geodir-booking' ),                    // the name of the widget.
			'widget_ops'       => array(
				'classname'     => 'geodir-booking-setup-wrapper bsui',                         // widget class
				'description'   => esc_html__( 'Displays a booking form', 'geodir-booking' ),   // widget description
				'gd_is_booking' => true,
			),
			'block_group_tabs' => array(
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
				'title'       => __( 'Listing ID:', 'geodir-booking' ),
				'desc'        => __( 'Leave blank to use the listing being viewed.', 'geodir-booking' ),
				'placeholder' => __( 'Listing being viewed', 'geodir-booking' ),
				'default'     => '',
				'desc_tip'    => true,
				'advanced'    => false,
				'group'       => __( 'Display', 'geodir-booking' ),
			),

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

		return apply_filters( 'geodir_booking_widget_arguments', $arguments );
	}

	/**
	 * Outputs the booking form.
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

		// Maybe generate preview.
		if ( $this->is_preview() ) {
			return $this->get_preview( $args );
		}

		// Prepare the listing.
		if ( ! empty( $args['id'] ) ) {
			$post_id = absint( $args['id'] );
		} elseif ( ! empty( $post->ID ) ) {
			$post_id = $post->ID;
		} elseif ( ! empty( $gd_post->ID ) ) {
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

		if ( empty( $listing ) || 'publish' !== $listing->post_status || ! in_array( $listing->post_type, geodir_get_posttypes() ) ) {
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
		$output = apply_filters( 'geodir_booking_pre_widget_output', null, $args, $widget_args, $content, $listing );
		if ( $output !== null ) {
			return $output;
		}

		$GLOBALS['geodir_booking_load_booking_form_app'] = true;

		//      $output = geodir_get_template_html( 'booking-form.php', array( 'listing' => $listing ), 'geodir-booking', plugin_dir_path( GEODIR_BOOKING_FILE ) . 'templates' );
		//      return $output;

		$rooms = geodir_get_listing_rooms( $listing->ID );

		if ( empty( $rooms ) ) {
			$this->load_form_template( $post_id );

			return '<div class="geodir-booking-form-container" data-id="' . absint( $post_id ) . '"><div class="spinner-border text-muted" role="status">
  <span class="visually-hidden">' . __( 'Loading...', 'geodir-booking' ) . '</span>
</div></div>';
		}

		$default   = current( $rooms );
		$options   = array();
		$templates = '';

		foreach ( $rooms as $room ) {
			$this->load_form_template( $room );
			$templates       .= '<div class="geodir-booking-form-container geodir-booking-form-room-container" data-id="' . absint( $room ) . '" style="display: none;"><div class="spinner-border text-muted" role="status">
			<span class="visually-hidden">' . __( 'Loading...', 'geodir-booking' ) . '</span>
		  </div></div>';
			$options[ $room ] = get_the_title( $room );
		}

		$wrap_class = sd_build_aui_class( $args );

		$return = '<div class="geodir-booking-form-rooms-container ' . esc_attr( $wrap_class ) . '" data-parent="' . absint( $listing->ID ) . '">';

		if ( empty( $GLOBALS[ 'geodir_booking_added_room_selector_' . $listing->ID ] ) ) {
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
		}

		return $return . $templates . '</div>';
	}

	public function get_preview( $args ) {
		$wrap_class = sd_build_aui_class( $args );
		return '<div class="geodir-book-now-form position-relative ' . esc_attr( $wrap_class ) . '" action="" method="post" novalidate=""><div class="mb-3"><label for="_booking_name" class="form-label">Name <span class="text-danger">*</span></label><input id="_booking_name" type="text" class="form-control" placeholder="Full Name"><!--v-if--></div><div class="mb-3"><label for="_booking_email" class="form-label">Email <span class="text-danger">*</span></label><input id="_booking_email" type="email" class="form-control" placeholder="Email"><!--v-if--></div><div class="mb-3"><label for="_booking_phone" class="form-label">Phone <span class="text-danger">*</span></label><input id="_booking_phone" type="tel" class="form-control" placeholder="Phone"><!--v-if--></div><div class="mb-3"><label for="_booking_dates" class="form-label">Check-in and Check-out dates</label><input type="text" class="form-control gd-booking-dates flatpickr-input" id="_booking_dates" placeholder="2023-04-20 - 2023-04-21" readonly="readonly"><!--v-if--></div><div class="d-grid gap-2 mb-3"><button type="submit" class="btn btn-primary d-block"><!--v-if-->&nbsp; <!--v-if--><span><span> Reserve </span></span></button></div><!--v-if--><!--v-if--><!--v-if--></div>';
	}

	protected function load_form_template( $post_id ) {
		global $gd_booking_form_templates;

		$gd_booking_form_templates = empty( $gd_booking_form_templates ) ? array() : $gd_booking_form_templates;

		if ( empty( $gd_booking_form_templates[ $post_id ] ) ) {
			add_action(
				'wp_footer',
				function () use ( $post_id ) {
					// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					echo geodir_get_template_html(
						'booking-form.php',
						array(
							'listing' => get_post( $post_id ),
						),
						'geodir-booking',
						plugin_dir_path( GEODIR_BOOKING_FILE ) . 'templates'
					);
				}
			);

			$gd_booking_form_templates[ $post_id ] = true;
		}
	}
}
