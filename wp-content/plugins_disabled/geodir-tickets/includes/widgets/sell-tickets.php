<?php
/**
 * Contains the sell tickets class
 *
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * GeoDir_Widget_Post_Badge class.
 *
 * @since 2.0.0
 */
class GeoDir_Tickets_Sell_Tickets_Widget extends WP_Super_Duper {

	public $arguments;

	/**
	 * Sets up the widgets name etc
	 */
	public function __construct() {

		$options = array(
			'textdomain'     => 'geodir-tickets',
			'block-icon'     => 'fas fa-ticket-alt',
			'block-wrap'    => '', // the element to wrap the block output in. , ie: div, span or empty for no wrap
			'block-supports'=> array(
				'customClassName'   => true
			),
			'block-category' => 'geodirectory',
			'block-keywords' => "['tickets','geodir','geodirectory','geodir']",
			'class_name'     => __CLASS__,
			'base_id'        => 'gd_sell_tickets',												// this us used as the widget id and the shortcode id.
			'name'           => __( 'GD > Sell Tickets', 'geodir-tickets' ),						// the name of the widget.
			'widget_ops'     => array(
				'classname'       => 'geodir-sell-tickets bsui',                                     	// widget class
				'description'     => esc_html__( 'Allows listing owners to sell tickets', 'geodir-tickets' ),	// widget description
				'gd_is_tickets'   => true,
			)
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
				'title'       => __( 'Event ID:', 'geodir-tickets' ),
				'desc'        => __( 'Leave blank to use the event being viewed.', 'geodir-tickets' ),
				'placeholder' => __( 'Event being viewed', 'geodir-tickets' ),
				'default'     => '',
				'desc_tip'    => true,
				'advanced'    => false
			),

			'primary_shadow' => array(
				'title'         => __( 'Button Shadow', 'geodir-tickets' ),
				'type'          => 'select',
				'options'       =>  array(
					''          => __( 'Default', 'geodir-tickets' ),
					"shadow-sm" => __( 'Small', 'geodir-tickets' ),
					"shadow"    => __( 'Medium', 'geodir-tickets' ),
					"shadow-lg" => __( 'Large', 'geodir-tickets' ),
				),
				'default'       => '',
				'desc_tip'      => true,
				'advanced'      => false,
				'group'         => __( 'Primary Button','geodir-tickets' )
			),

			'secondary_shadow' => array(
				'title'         => __( 'Button Shadow', 'geodir-tickets' ),
				'type'          => 'select',
				'options'       =>  array(
					''          => __( 'Default', 'geodir-tickets' ),
					"shadow-sm" => __( 'Small', 'geodir-tickets' ),
					"shadow"    => __( 'Medium', 'geodir-tickets' ),
					"shadow-lg" => __( 'Large', 'geodir-tickets' ),
				),
				'default'       => '',
				'desc_tip'      => true,
				'advanced'      => false,
				'group'         => __( 'Secondary Button','geodir-tickets' )
			),

			'primary_color' => array(
				'title'         => __( 'Button Color', 'geodir-tickets' ),
				'desc'          => __( 'Select the the button color.', 'geodir-tickets' ),
				'type'          => 'select',
				'options'       =>  geodir_aui_colors( true ),
				'default'       => 'primary',
				'desc_tip'      => true,
				'advanced'      => false,
				'group'         => __( 'Primary Button','geodir-tickets' )
			),

			'secondary_color' => array(
				'title'         => __( 'Button Color', 'geodir-tickets' ),
				'desc'          => __( 'Select the the button color.', 'geodir-tickets' ),
				'type'          => 'select',
				'options'       =>  geodir_aui_colors( true ),
				'default'       => 'secondary',
				'desc_tip'      => true,
				'advanced'      => false,
				'group'         => __( 'Secondary Button','geodir-tickets' )
			),

			'primary_list_hide' => array(
				'title'   => __( 'Hide button on:', 'geodir-tickets' ),
				'desc'    => __( 'You can set at what view the button will become hidden. Only affects buttons placed inside the archive item template.', 'geodir-tickets' ),
				'type'    => 'select',
				'options' =>  array(
					''          => __( 'Always show', 'geodir-tickets' ),
					'gv-hide-2' => __( 'Grid view 2', 'geodir-tickets' ),
					'gv-hide-3' => __( 'Grid view 3', 'geodir-tickets' ),
					'gv-hide-4' => __( 'Grid view 4', 'geodir-tickets' ),
					'gv-hide-5' => __( 'Grid view 5', 'geodir-tickets' ),
				),
				'desc_tip'      => true,
				'group'         => __( 'Primary Button','geodir-tickets' )
			),

			'secondary_list_hide' => array(
				'title'   => __( 'Hide button on:', 'geodir-tickets' ),
				'desc'    => __( 'You can set at what view the button will become hidden. Only affects buttons placed inside the archive item template.', 'geodir-tickets' ),
				'type'    => 'select',
				'options' =>  array(
					''            => __( 'Always show', 'geodir-tickets' ),
					'gv-hide-2'   => __( 'Grid view 2', 'geodir-tickets' ),
					'gv-hide-3'   => __( 'Grid view 3', 'geodir-tickets' ),
					'gv-hide-4'   => __( 'Grid view 4', 'geodir-tickets' ),
					'gv-hide-5'   => __( 'Grid view 5', 'geodir-tickets' ),
				),
				'desc_tip'      => true,
				'group'         => __( 'Secondary Button','geodir-tickets' )
			),

			'primary_css_class' => array(
				'type'        => 'text',
				'title'       => __( 'Extra class:', 'geodir-tickets' ),
				'desc'        => __( 'Give the button an extra class so you can style things as you want.', 'geodir-tickets' ),
				'placeholder' => 'btn-block',
				'default'     => '',
				'desc_tip'    => true,
				'group'       => __( 'Primary Button','geodir-tickets' )
			),

			'secondary_css_class' => array(
				'type'        => 'text',
				'title'       => __( 'Extra class:', 'geodir-tickets' ),
				'desc'        => __( 'Give the button an extra class so you can style things as you want.', 'geodir-tickets' ),
				'placeholder' => 'btn-block',
				'default'     => '',
				'desc_tip'    => true,
				'group'       => __( 'Secondary Button','geodir-tickets' )
			),

		);

		return apply_filters( 'geodir_tickets_widget_arguments', $arguments );
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
	public function output( $args = array(), $widget_args = array(), $content = '' ) {
		global $post, $gd_post, $geodir_tickets_load_scripts;

		if ( $this->is_preview() ) {
			return $this->generate_tickets_preview( $args );
		}

		// Is events manager loaded?
		if ( ! class_exists( 'GeoDir_Event_Schedules' ) ) {
			return '';
		}

		// Ensure we have an array.
		if ( ! is_array( $args ) ) {
			$args = array();
		}

		// Prepare the listing and ensure it supports events.
		if ( ! empty( $args['id'] ) ) {
			$post_id = absint( $args['id'] );
		} else if ( ! empty( $post->ID ) ) {
			$post_id = (int) $post->ID;
		} else if ( ! empty( $gd_post->ID ) ) {
			$post_id = (int) $gd_post->ID;
		} else {
			return '';
		}

		// Ensure the post supports events.
		$listing = get_post( $post_id );

		if( empty( $listing ) || 'publish' !== $listing->post_status || ! GeoDir_Post_types::supports( $listing->post_type, 'events' )  ){
			return '';
		}

		/**
		 * Filters the widget output before the original output.
		 *
		 * @since 2.0.1
		 *
		 * @param string $output Pre output. Default NULL.
		 * @param array  $args Widget parameters.
		 * @param object $listing The event post object.
		 * @param array  $widget_args Widget arguments.
		 * @param string $content Widget content.
		 */
		$output = apply_filters( 'geodir_tickets_pre_widget_output', NULL, $args, $listing, $widget_args, $content );
		if ( $output !== NULL ) {
			return $output;
		}

		// Prepare variables.
		$current_user_id = (int) get_current_user_id();
		$html         = '';
		$id           = '-event-' . $post_id;
		$ticket_types = wp_parse_id_list( get_post_meta( $post_id, 'listing_tickets', true ) );
		$is_ongoing   = false;

		add_filter( 'geodir_get_option_event_include_ongoing', '__return_false' );
		$upcoming_schedule = GeoDir_Event_Schedules::get_schedules( $post_id, 'upcoming', 1 );
		remove_filter( 'geodir_get_option_event_include_ongoing', '__return_false' );

		$today_schedule = GeoDir_Event_Schedules::get_schedules( $post_id, 'today', 1 );
		$has_schedule = ! empty( $upcoming_schedule ) || ! empty( $today_schedule ) ? true : false;

		// Prepare buyable tickets.
		if ( ! empty( $upcoming_schedule ) ) {
			$item_ids = $ticket_types;
		} else if ( ! empty( $today_schedule ) ) {
			$ongoing_ticket_types = array();

			if ( ! empty( $ticket_types ) ) {
				foreach ( $ticket_types as $ticket_type ) {
					if ( 'ends' === get_post_meta( (int) $ticket_type, 'sell_till', true ) ) {
						$ongoing_ticket_types[] = (int) $ticket_type;
					}
				}
			}

			$item_ids = $ongoing_ticket_types;
		} else {
			$item_ids = array();
		}

		// Prepare buy URL.
		$buy_url = '#getpaid-item-';

		$payment_form = geodir_tickets_get_option( 'payment_form' );

		if ( empty( $payment_form ) ) {
			$payment_form = wpinv_get_default_payment_form();
		}

		$payment_form = apply_filters( 'geodir_tickets_payment_form', $payment_form, $post_id );

		if ( ! empty( $payment_form ) ) {
			$payment_form = (int) $payment_form;
			$buy_url = "#getpaid-form-$payment_form|";
		}

		foreach ( $item_ids as $item_id ) {
			$buy_url .= "$item_id|0,";
		}

		// Load scripts.
		$geodir_tickets_load_scripts = true;

		if ( ( $current_user_id && $current_user_id == (int) $listing->post_author ) || current_user_can( 'manage_options' ) ) {
			if ( empty( $ticket_types ) ) {
				if ( empty( $has_schedule ) ) {
					return '';
				}

				$html .= aui()->button(
					array(
						'type'             => 'button',
						'class'            => 'geodir-sell-tickets-btn btn ' . $this->get_button_class( $args, 'primary' ),
						'content'          => __( 'Sell Tickets', 'geodir-tickets' ),
						'id'               => "geodir-sell-tickets-btn$id",
						'extra_attributes' => array(
							'onclick'        => "new bootstrap.Modal(document.getElementById('geodir-sell-tickets-modal$id')).show()"
						)
					)
				);

				if ( ! $this->has_modal( 'create-tickets-' . $id ) ) {
					ob_start();
					include plugin_dir_path( GEODIR_TICKETS_FILE ) . 'includes/views/create-tickets.php';
					$this->load_modal( 'create-tickets-' . $id, ob_get_clean() );
				}
			} else {
				$GLOBALS['geodir_tickets_load_management_scripts'] = true;

				if ( ! empty( $item_ids ) ) {
					$buy_url = trim( $buy_url, ',' );

					// If all tickets are sold out, display sold out button.
					if ( geodir_tickets_are_all_sold_out( $item_ids ) ) {
						$html   .= aui()->button(
							array(
								'type'    => 'a',
								'href'    => '#',
								'class'   => 'geodir-buy-tickets-btn disabled btn btn-outline-danger btn-block',
								'content' => __( 'Tickets Sold Out', 'geodir-tickets' ),
								'id'      => "geodir-buy-tickets-btn$id",
								'onclick' => 'return false',
								'extra_attributes'  => array(
									'disabled' => 'disabled',
								),
							)
						);
					} else {
						$html   .= aui()->button(
							array(
								'type'    => 'a',
								'href'    => $buy_url,
								'class'   => 'geodir-buy-tickets-btn btn ' . $this->get_button_class( $args, 'primary' ),
								'content' => __( 'Buy Tickets', 'geodir-tickets' ),
								'id'      => "geodir-buy-tickets-btn$id",
							)
						);
					}
				}

				$html .= aui()->button(
					array(
						'type'    => 'button',
						'class'   => 'geodir-manage-tickets-btn mt-2 btn ' . $this->get_button_class( $args, 'secondary' ),
						'content' => __( 'Ticket Management', 'geodir-tickets' ),
						'id'      => "geodir-manage-tickets-btn$id",
						'extra_attributes' => array(
							'onclick'        => "new bootstrap.Modal(document.getElementById('geodir-manage-tickets-modal$id')).show()"
						)
					)
				);

				if ( ! $this->has_modal( 'manage-tickets-' . $id ) ) {
					ob_start();
					include plugin_dir_path( GEODIR_TICKETS_FILE ) . 'includes/views/manage-tickets/index.php';
					$this->load_modal( 'manage-tickets-' . $id, ob_get_clean() );
				}
			}
		} else if ( ! empty( $ticket_types ) ) {
			$user_tickets = geodir_get_tickets(
				array(
					'buyer_in' => array( $current_user_id ),
					'event_in' => array( $post_id )
				)
			);

			if ( ! empty( $item_ids ) ) {
				$buy_url = trim( $buy_url, ',' );

				// If all tickets are sold out, display sold out button.
				if ( geodir_tickets_are_all_sold_out( $item_ids ) ) {
					$html   .= aui()->button(
						array(
							'type'    => 'a',
							'href'    => '#',
							'class'   => 'geodir-buy-tickets-btn disabled btn btn-outline-danger btn-block',
							'content' => __( 'Tickets Sold Out', 'geodir-tickets' ),
							'id'      => "geodir-buy-tickets-btn$id",
							'onclick' => 'return false',
							'extra_attributes'  => array(
								'disabled' => 'disabled',
							),
						)
					);
				} else {
					$html   .= aui()->button(
						array(
							'type'    => 'a',
							'href'    => $buy_url,
							'class'   => 'geodir-buy-tickets-btn btn ' . $this->get_button_class( $args, 'primary' ),
							'content' => __( 'Buy Tickets', 'geodir-tickets' ),
							'id'      => "geodir-buy-tickets-btn$id",
						)
					);
				}
			}

			if ( ! empty( $user_tickets ) && 0 !== $current_user_id ) {
				$html .= aui()->button(
					array(
						'type'             => 'button',
						'class'            => 'geodir-view-tickets-btn mt-2 btn ' . $this->get_button_class( $args, 'secondary' ),
						'content'          => __( 'View Tickets', 'geodir-tickets' ),
						'id'               => "geodir-view-tickets-btn$id",
						'extra_attributes' => array(
							'onclick'        => "new bootstrap.Modal(document.getElementById('geodir-view-tickets-modal$id')).show()"
						)
					)
				);

				if ( ! $this->has_modal( 'view-tickets-' . $id ) ) {
					ob_start();
					include plugin_dir_path( GEODIR_TICKETS_FILE ) . 'includes/views/view-tickets.php';
					$this->load_modal( 'view-tickets-' . $id, ob_get_clean() );
				}
			}
		}

		return $html;
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
		global $aui_bs5;

		$class = empty( $args["{$type}_css_class"] ) ? '' : esc_attr( $args["{$type}_css_class"] );

		if ( empty( $class ) ) {
			$class = 'btn-block';
		}

		if ( empty( $args["{$type}_color"] ) ) {
			$class .= ' btn-' . sanitize_html_class( $type );
		} else {
			$class .= ' btn-' . sanitize_html_class( $args["{$type}_color"] );
		}

		if ( ! empty( $args["{$type}_list_hide"] ) ) {
			$class .= ' ' . sanitize_html_class( $args["{$type}_list_hide"] );
		}

		if ( $aui_bs5 ) {
			$class = str_replace( 'btn-block', 'd-block w-100', $class );
		}

		return apply_filters( 'geodir_tickets_widget_button_class', $class, $type, $args );
	}

	/**
	 * Prepares modals for display.
	 *
	 * @param WP_Post $listing
	 *
	 * @return mixed|string|void
	 */
	public function load_modal( $id, $modal ) {
		global $geodir_ticket_modals;

		if ( empty( $geodir_ticket_modals ) ) {
			$geodir_ticket_modals = array();
		}

		$geodir_ticket_modals[ $id ] = $modal;

	}

	/**
	 * Checks if a given modal has been loaded.
	 *
	 * @param int $id
	 * @return bool
	 */
	public function has_modal( $id ) {

		return array_key_exists( $id, $this->get_modals() );
	}

	/**
	 * Returns an array of loaded modals.
	 *
	 * @return array
	 */
	public function get_modals() {

		return empty( $GLOBALS['geodir_ticket_modals'] ) ? array() : $GLOBALS['geodir_ticket_modals'];
	}

	/**
	 * Outputs preview.
	 *
	 * @param array $args
	 *
	 * @return mixed|string|void
	 */
	public function generate_tickets_preview( $args ) {

		$html = aui()->button(
			array(
				'type'    => 'button',
				'class'   => 'geodir-sell-tickets-btn btn ' . $this->get_button_class( $args, 'primary' ),
				'content' => __( 'Primary Button Preview', 'geodir-tickets' ),
			)
		);

		$html .= aui()->button(
			array(
				'type'    => 'button',
				'class'   => 'geodir-manage-tickets-btn btn ' . $this->get_button_class( $args, 'secondary' ),
				'content' => __( 'Secondary Button Preview', 'geodir-tickets' ),
			)
		);

		return $html;
	}

}
