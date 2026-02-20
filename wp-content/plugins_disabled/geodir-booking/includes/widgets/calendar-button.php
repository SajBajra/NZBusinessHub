<?php
/**
 * Contains the bookings calendar button widget class.
 *
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * GeoDir_Booking_Calendar_Button_Widget class.
 *
 * @since 1.0.0
 */
class GeoDir_Booking_Calendar_Button_Widget extends WP_Super_Duper {

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
			'base_id'          => 'gd_booking_calendar_button',                                           // this is used as the widget id and the shortcode id.
			'name'             => __( 'GD > Booking Owner Management', 'geodir-booking' ),                    // the name of the widget.
			'widget_ops'       => array(
				'classname'     => 'geodir-booking-setup-wrapper bsui',                                       // widget class
				'description'   => esc_html__( 'Displays a button to set-up or view bookings', 'geodir-booking' ),   // widget description
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
						__( 'Setup Button', 'geodir-booking' ),
						__( 'Manage Button', 'geodir-booking' ),
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
		global $aui_bs5;

		$arguments = array(
			'id'                  => array(
				'type'        => 'number',
				'title'       => __( 'Listing ID:', 'geodir-booking' ),
				'desc'        => __( 'Leave blank to use the listing being viewed.', 'geodir-booking' ),
				'placeholder' => __( 'Listing being viewed', 'geodir-booking' ),
				'default'     => '',
				'desc_tip'    => true,
				'advanced'    => false,
				'group'       => __( 'Display', 'geodir-booking' ),
			),

			'show_setup'          => array(
				'title'    => __( 'Show Setup Instructions', 'geodir-booking' ),
				'desc'     => __( 'Show admin setup instruction and then show owner setup instructions.', 'geodir-booking' ),
				'type'     => 'select',
				'options'  => array(
					''   => __( 'Yes', 'geodir-booking' ),
					'no' => __( 'No', 'geodir-booking' ),
				),
				'default'  => '',
				'desc_tip' => true,
				'advanced' => false,
				'group'    => __( 'Display', 'geodir-booking' ),
			),

			'primary_shadow'      => array(
				'title'    => __( 'Button Shadow', 'geodir-booking' ),
				'type'     => 'select',
				'options'  => array(
					''          => __( 'Default', 'geodir-booking' ),
					'shadow-sm' => __( 'Small', 'geodir-booking' ),
					'shadow'    => __( 'Medium', 'geodir-booking' ),
					'shadow-lg' => __( 'Large', 'geodir-booking' ),
				),
				'default'  => '',
				'desc_tip' => true,
				'advanced' => false,
				'group'    => __( 'Setup Button', 'geodir-booking' ),
			),

			'secondary_shadow'    => array(
				'title'    => __( 'Button Shadow', 'geodir-booking' ),
				'type'     => 'select',
				'options'  => array(
					''          => __( 'Default', 'geodir-booking' ),
					'shadow-sm' => __( 'Small', 'geodir-booking' ),
					'shadow'    => __( 'Medium', 'geodir-booking' ),
					'shadow-lg' => __( 'Large', 'geodir-booking' ),
				),
				'default'  => '',
				'desc_tip' => true,
				'advanced' => false,
				'group'    => __( 'Manage Button', 'geodir-booking' ),
			),

			'primary_color'       => array(
				'title'    => __( 'Button Color', 'geodir-booking' ),
				'desc'     => __( 'Select the the button color.', 'geodir-booking' ),
				'type'     => 'select',
				'options'  => geodir_aui_colors( true ),
				'default'  => 'primary',
				'desc_tip' => true,
				'advanced' => false,
				'group'    => __( 'Setup Button', 'geodir-booking' ),
			),

			'secondary_color'     => array(
				'title'    => __( 'Button Color', 'geodir-booking' ),
				'desc'     => __( 'Select the the button color.', 'geodir-booking' ),
				'type'     => 'select',
				'options'  => geodir_aui_colors( true ),
				'default'  => 'secondary',
				'desc_tip' => true,
				'advanced' => false,
				'group'    => __( 'Manage Button', 'geodir-booking' ),
			),

			'primary_list_hide'   => array(
				'title'    => __( 'Hide button on:', 'geodir-booking' ),
				'desc'     => __( 'You can set at what view the button will become hidden. Only affects buttons placed inside the archive item template.', 'geodir-booking' ),
				'type'     => 'select',
				'options'  => array(
					''          => __( 'Always show', 'geodir-booking' ),
					'gv-hide-2' => __( 'Grid view 2', 'geodir-booking' ),
					'gv-hide-3' => __( 'Grid view 3', 'geodir-booking' ),
					'gv-hide-4' => __( 'Grid view 4', 'geodir-booking' ),
					'gv-hide-5' => __( 'Grid view 5', 'geodir-booking' ),
				),
				'desc_tip' => true,
				'group'    => __( 'Setup Button', 'geodir-booking' ),
			),

			'secondary_list_hide' => array(
				'title'    => __( 'Hide button on:', 'geodir-booking' ),
				'desc'     => __( 'You can set at what view the button will become hidden. Only affects buttons placed inside the archive item template.', 'geodir-booking' ),
				'type'     => 'select',
				'options'  => array(
					''          => __( 'Always show', 'geodir-booking' ),
					'gv-hide-2' => __( 'Grid view 2', 'geodir-booking' ),
					'gv-hide-3' => __( 'Grid view 3', 'geodir-booking' ),
					'gv-hide-4' => __( 'Grid view 4', 'geodir-booking' ),
					'gv-hide-5' => __( 'Grid view 5', 'geodir-booking' ),
				),
				'desc_tip' => true,
				'group'    => __( 'Manage Button', 'geodir-booking' ),
			),

			'primary_css_class'   => array(
				'type'        => 'text',
				'title'       => __( 'Extra class:', 'geodir-booking' ),
				'desc'        => __( 'Give the button an extra class so you can style things as you want.', 'geodir-booking' ),
				'placeholder' => 'rounded btn-lg btn-block',
				'default'     => '',
				'desc_tip'    => true,
				'group'       => __( 'Setup Button', 'geodir-booking' ),
			),

			'secondary_css_class' => array(
				'type'        => 'text',
				'title'       => __( 'Extra class:', 'geodir-booking' ),
				'desc'        => __( 'Give the button an extra class so you can style things as you want.', 'geodir-booking' ),
				'placeholder' => 'rounded btn-lg ' . ( $aui_bs5 ? 'd-block' : 'btn-block' ),
				'default'     => '',
				'desc_tip'    => true,
				'group'       => __( 'Manage Button', 'geodir-booking' ),
			),

		);

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

		return apply_filters( 'geodir_booking_button_widget_arguments', $arguments );
	}

	/**
	 * Outputs the post badge on the front-end.
	 *
	 * @param array $args
	 * @param array $widget_args
	 * @param string $content
	 *
	 * @return mixed|string|void
	 */
	public function get_button_class( $args, $type = 'primary' ) {
		$class = empty( $args[ "{$type}_css_class" ] ) ? '' : esc_attr( $args[ "{$type}_css_class" ] );

		if ( empty( $class ) && $type == 'secondary' ) {
			$class = 'text-white';
		}

		if ( empty( $args[ "{$type}_color" ] ) ) {
			$class .= ' btn-' . sanitize_html_class( $type );
		} else {
			$class .= ' btn-' . sanitize_html_class( $args[ "{$type}_color" ] );
		}

		foreach ( $args as $key => $value ) {
			if ( 0 === strpos( $key, $type ) ) {
				$class .= ' ' . esc_attr( $args[ $key ] );
			}
		}

		return apply_filters( 'geodir_bookings_widget_button_class', $class, $type, $args );
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
		global $aui_bs5, $post, $gd_post;

		// Ensure we have an array.
		if ( ! is_array( $args ) ) {
			$args = array();
		}

		// Maybe generate preview.
		if ( $this->is_preview() ) {
			return $this->generate_booking_preview( $args );
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

		// Ensure this is a GD post.
		$listing = get_post( $post_id );

		if ( empty( $listing ) || 'publish' !== $listing->post_status || ! in_array( $listing->post_type, geodir_get_posttypes() ) ) {
			return '';
		}

		// Abort if booking is not enabled.
		if ( ! geodir_booking_is_enabled( $post_id ) ) {

			$booking_field_added = property_exists( $gd_post, 'gdbooking' ) && ( $gd_post->gdbooking || $gd_post->gdbooking === null ) ? true : false;
			// maybe show setup instructions
			if ( empty( $args['show_setup'] ) && current_user_can( 'manage_options' ) && ! $booking_field_added ) {
				return aui()->alert(
					array(
						'type'    => 'warning',
						'content' => sprintf( __( 'Admin Notice: The booking fields have not been added to this Custom Post Type. Please see %1$ssetup instructions%2$s to enable booking.', 'geodir-booking' ), '<a href="https://docs.wpgeodirectory.com/article/731-booking-marketplace-setup-guide" target="_blank">', '</a>' ),
						'class'   => 'mb-0',
					)
				);
			} elseif ( empty( $args['show_setup'] ) && geodir_listing_belong_to_current_user( (int) $listing->ID ) && $booking_field_added && empty( $gd_post->gdbooking ) ) {
				return aui()->alert(
					array(
						'type'    => 'warning',
						'content' => __( 'Booking is not enabled for this listing, please edit your listing to enable booking.', 'geodir-booking' ),
						'class'   => 'mb-0',
					)
				);
			}
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
		$output = apply_filters( 'geodir_booking_setup_pre_widget_output', null, $args, $widget_args, $content, $listing );
		if ( $output !== null ) {
			return $output;
		}

		$rooms      = geodir_get_listing_rooms( $listing->ID );
		$listing_id = empty( $rooms ) ? $listing->ID : $rooms[0];
		$html       = '';

		// Load admin view.
		if ( geodir_listing_belong_to_current_user( (int) $listing->ID ) ) {

			// Set-up bookings.
			$html .= aui()->button(
				array(
					'type'             => 'button',
					'class'            => 'geodir-setup-booking-btn btn ' . ( $aui_bs5 ? '' : 'btn-block' ) . ' ' . $this->get_button_class( $args, 'primary' ),
					'content'          => __( 'Setup Bookings', 'geodir-booking' ),
					'id'               => 'geodir-setup-booking-btn-' . $listing->ID,
					'extra_attributes' => array(
						'data-id' => $listing_id,
					),
				)
			);

			// View bookings.
			$html .= aui()->button(
				array(
					'type'             => 'button',
					'class'            => 'geodir-view-bookings-btn btn ' . ( $aui_bs5 ? '' : 'btn-block' ) . ' ' . $this->get_button_class( $args, 'secondary' ),
					'content'          => __( 'Manage Bookings', 'geodir-booking' ),
					'id'               => 'geodir-view-bookings-btn-' . $listing->ID,
					'extra_attributes' => array(
						'data-id' => $listing->ID,
					),
				)
			);

			$GLOBALS['geodir_booking_load_setup_modal']             = true;
			$GLOBALS['geodir_booking_load_bookings_modal']          = true;
			$GLOBALS['geodir_booking_load_ical_sync_status_modal']  = true;
			$GLOBALS['geodir_booking_rendered_listings']            = ! empty( $GLOBALS['geodir_booking_rendered_listings'] ) ? array_unique( $GLOBALS['geodir_booking_rendered_listings'] + array( $listing->ID ) ) : array( $listing->ID );
			$GLOBALS['geodir_booking_rendered_post_types']          = ! empty( $GLOBALS['geodir_booking_rendered_post_types'] ) ? array_unique( $GLOBALS['geodir_booking_rendered_post_types'] + array( $listing->post_type ) ) : array( $listing->post_type );
		} else {

			// Book now button.
			//$html .= aui()->button(
			//  array(
			//      'type'             => 'button',
			//      'class'            => 'geodir-book-now-btn btn ' . ( $aui_bs5 ? '' : 'btn-block' ) . ' ' . $this->get_button_class( $args, 'primary' ),
			//      'content'          => __( 'Book Now', 'geodir-booking' ),
			//      'id'               => 'geodir-book-now-btn-' . $listing->ID,
			//      'extra_attributes' => array(
			//          'data-id'             => $listing->ID,
			//          'data-ruleset'        => wp_json_encode( new GeoDir_Booking_Ruleset( 0, $listing->ID ) ),
			//          'data-disabled_dates' => wp_json_encode( GeoDir_Booking::get_disabled_dates( $listing->ID ) ),
			//      )
			//  )
			//);

			// View bookings.
			if ( is_user_logged_in() ) {
				$html .= aui()->button(
					array(
						'type'             => 'button',
						'class'            => 'geodir-view-customer-bookings-btn btn ' . ( $aui_bs5 ? '' : 'btn-block' ) . ' ' . $this->get_button_class( $args, 'secondary' ),
						'content'          => __( 'View Bookings', 'geodir-booking' ),
						'id'               => 'geodir-view-customer-bookings-btn-' . $listing->ID,
						'extra_attributes' => array(
							'data-id' => $listing->ID,
						),
					)
				);

				$GLOBALS['geodir_booking_load_customer_bookings_modal'] = true;
			}

			$GLOBALS['geodir_booking_load_book_now_modal'] = true;
		}

		if ( $html && $aui_bs5 ) {
			$wrap_class = sd_build_aui_class( $args );
			$html       = '<div class="d-grid gap-2 ' . esc_attr( $wrap_class ) . '">' . $html . '</div>';
		}

		return $html;
	}

	/**
	 * Outputs preview.
	 *
	 * @param array $args
	 *
	 * @return mixed|string|void
	 */
	public function generate_booking_preview( $args ) {
		global $aui_bs5;

		$html = aui()->button(
			array(
				'type'    => 'button',
				'class'   => 'geodir-manage-bookings-btn btn ' . ( $aui_bs5 ? '' : 'btn-block' ) . ' ' . $this->get_button_class( $args, 'primary' ),
				'content' => __( 'Setup Bookings', 'geodir-booking' ),
			)
		);

		$html .= aui()->button(
			array(
				'type'    => 'button',
				'class'   => 'geodir-manage-bookings-btn btn ' . ( $aui_bs5 ? '' : 'btn-block' ) . ' ' . $this->get_button_class( $args, 'secondary' ),
				'content' => __( 'Manage Bookings', 'geodir-booking' ),
			)
		);

		if ( $aui_bs5 ) {
			$wrap_class = sd_build_aui_class( $args );
			$html       = '<div class="d-grid gap-2 ' . esc_attr( $wrap_class ) . '">' . $html . '</div>';
		}

		return $html;
	}
}
