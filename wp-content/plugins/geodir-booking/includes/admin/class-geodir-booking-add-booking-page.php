<?php
/**
 * Add Bookings page class.
 *
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 *  Add Bookings page class.
 */
class GeoDir_Booking_Add_Booking_Page {
	/**
	 * The one true instance of GeoDir_Booking_Add_Booking_Page.
	 *
	 * @var GeoDir_Booking_Add_Booking_Page
	 */
	private static $instance;

	/**
	 * The action being processed.
	 *
	 * @var bool
	 */
	private $action;

	/**
	 * Indicates if the current action is a search.
	 *
	 * @var bool
	 */
	private $is_search = false;

	/**
	 * The check-in date for the booking.
	 *
	 * @var int|null
	 */
	private $checkin_date = null;

	/**
	 * The check-out date for the booking.
	 *
	 * @var int|null
	 */
	private $checkout_date = null;

	/**
	 * The ID of the listing being processed.
	 *
	 * @var int|null
	 */
	private $listing_id = null;

	/**
	 * The number of adults for the booking.
	 *
	 * @var int|null
	 */
	private $adults = null;

	/**
	 * The number of children for the booking.
	 *
	 * @var int|null
	 */
	private $children = null;

	/**
	 * An array of empty options.
	 *
	 * @var array
	 */
	public $empty_options = array();

	/**
	 * An array of rooms.
	 *
	 * @var array
	 */
	private $rooms = array();

	/**
	 * An array of bookings.
	 *
	 * @var array
	 */
	private $bookings = array();

	/**
	 * An array of errors.
	 *
	 * @var array
	 */
	private $errors = array();

	/**
	 * Initializes the GeoDir_Booking_Add_Booking_Page object.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'init' ) );
	}

	public function init() {
		$this->empty_options = array( -1 => __( '— Any —', 'geodir-booking' ) );

		$this->process();

		$this->hooks();
	}

	/**
	 * Get the one true instance of GeoDir_Booking_Add_Booking_Page.
	 *
	 * @since 1.0
	 * @return $instance
	 */
	public static function instance() {
		if ( ! self::$instance ) {
			self::$instance = new GeoDir_Booking_Add_Booking_Page();
		}

		return self::$instance;
	}

	/**
	 * Registers hooks to enqueue scripts.
	 *
	 * @since  1.0
	 * @return void
	 */
	private function hooks() {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
	}

	/**
	 * Register admin scripts
	 */
	public function enqueue_scripts() {
		$is_bookings_page = (
			isset( $_REQUEST['page'] ) &&
			( 'geodir-booking-add-new' === $_REQUEST['page'] ||
			'geodir-booking' === $_REQUEST['page'] )
		);

		if ( ! $is_bookings_page ) {
			return;
		}

		wp_enqueue_style( 'geodir-bookings-css', plugin_dir_url( GEODIR_BOOKING_FILE ) . 'assets/css/admin-bookings.css', array(), GEODIR_BOOKING_VERSION );

		wp_enqueue_script( 'geodir-booking-admin-bookings', plugin_dir_url( GEODIR_BOOKING_FILE ) . 'assets/js/admin-bookings.js', array( 'jquery' ), GEODIR_BOOKING_VERSION, true );

		wp_localize_script(
			'geodir-booking-admin-bookings',
			'Geodir_Bookings',
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonces'   => GeoDir_Booking_Ajax::instance()->get_nonces(),
				'i18n'     => array(
					'error' => __( 'There is something that went wrong!', 'geodir-booking' ),
				),
			)
		);
	}

	/**
	 * Processes search form.
	 */
	public function process() {
		$this->action    = isset( $_POST['action'] ) ? $_POST['action'] : ( isset( $_GET['action'] ) ? $_GET['action'] : '' );
		$this->is_search = 'search' === $this->action ? true : false;

		$this->checkin_date  = isset( $_GET['gdbc_check_in'] ) && ! empty( $_GET['gdbc_check_in'] ) ? wp_unslash( $_GET['gdbc_check_in'] ) : '';
		$this->checkout_date = isset( $_GET['gdbc_check_out'] ) && ! empty( $_GET['gdbc_check_out'] ) ? wp_unslash( $_GET['gdbc_check_out'] ) : '';
		$this->listing_id    = ( isset( $_GET['gdbc_listing_id'] ) && $_GET['gdbc_listing_id'] !== '-1' ) ? absint( $_GET['gdbc_listing_id'] ) : '';
		$this->adults        = ( isset( $_GET['gdbc_adults'] ) && $_GET['gdbc_adults'] !== '-1' ) ? absint( $_GET['gdbc_adults'] ) : '';
		$this->children      = ( isset( $_GET['gdbc_children'] ) && $_GET['gdbc_children'] !== '-1' ) ? absint( $_GET['gdbc_children'] ) : '';

		if ( $this->is_search ) {
			if ( empty( $this->checkin_date ) || empty( $this->checkout_date ) ) {
				$this->errors[] = esc_html__( 'Check-in and check-out dates are required.', 'geodir-booking' );
			} elseif ( strtotime( $this->checkout_date ) < strtotime( $this->checkin_date ) ) {
				$this->errors[] = esc_html__( 'Check-out date cannot be before check-in date.', 'geodir-booking' );
			}
		}
	}

	/**
	 * Retrieves the check-in date of the booking.
	 *
	 * @return DateTime The check-in date.
	 */
	public function get_check_in_date() {
		return new DateTime( $this->checkin_date );
	}

	/**
	 * Retrieves the check-out date of the booking.
	 *
	 * @return DateTime The check-out date.
	 */
	public function get_check_out_date() {
		return new DateTime( $this->checkout_date );
	}

	/**
	 * Retrieves the list of bookable listings.
	 *
	 * @return array The list of bookable listings.
	 */
	public function get_listings_options() {
		$listings_query = geodir_booking_get_bookable_listings_query(
			array(
				'order'       => 'ASC',
				'post_status' => 'publish',
				'post_parent' => '0',
			)
		);

		$listings = $listings_query->get_posts();

		$listings_options = array();
		foreach ( $listings as $listing ) {
			$listing_id = geodir_booking_post_id( $listing->ID );
			$listing = geodir_get_post_info( $listing_id );

			if ( isset( $listing->gdbooking ) && $listing->gdbooking === '1' ) {
				$listings_options[ $listing->ID ] = $listing->post_title;
			}
		}

		$listings_options = array_replace( $this->empty_options, $listings_options );

		return $listings_options;
	}

	/**
	 * Retrieves the options for the number of adults.
	 *
	 * @param int $max The maximum number of adults.
	 * @return array The options for the number of adults.
	 */
	public function get_adults_options( $max = 30 ) {
		$adults_options = array_map( 'strval', range( 0, $max ) );
		$adults_options = array_replace( $this->empty_options, $adults_options );

		return $adults_options;
	}

	/**
	 * Retrieves the options for the number of children.
	 *
	 * @param int $max The maximum number of children.
	 * @return array The options for the number of children.
	 */
	public function get_children_options( $max = 10 ) {
		$children_options = array_map( 'strval', range( 0, $max ) );
		$children_options = array_replace( $this->empty_options, $children_options );

		return $children_options;
	}

	/**
	 * Displays the price breakdown for the selected bookings.
	 *
	 * @param array $bookings An array of selected bookings with their details.
	 */
	public function display_price_breakdown( $bookings = array() ) {
		global $aui_bs5;

		$booking_total        = 0;
		$total_site_commision = 0;
		$total_fees           = 0;

		$generate_table_row = function ( $label, $value ) use ( $aui_bs5 ) {
			if ( empty( $value ) ) {
				return '';
			}

			return sprintf(
				'<tr>
                    <td colspan="1">%s</td>
                    <td>
                        <span class="text-success %s">%s</span>
                    </td>
                </tr>',
				esc_html( $label ),
				$aui_bs5 ? 'fw-semibold' : 'font-weight-bold',
				wp_kses_post( wpinv_price( $value ) )
			);
		};

		?>
		<table class="geodir-booking-price-breakdown table table-bordered" cellspacing="0">
			<tbody>
				<?php
				foreach ( $bookings as $key => $booking ) :
					$booking->calculate_prices();
					$listing = $booking->get_listing_details();

					$total_fees += $booking->cleaning_fee;
					$total_fees += $booking->pet_fee;
					$total_fees += $booking->service_fee;
					$total_fees += $booking->extra_guest_fee;

					$booking_total        += $booking->payable_amount;
					$total_site_commision += $booking->site_commission;
					?>
					
					<tr class="geodir-booking-price-breakdown-booking geodir-booking-price-breakdown-group">
						<td colspan="1">
							<?php
							$title = sprintf( _x( '#%1$d %2$s', 'Listing in price breakdown table. Example: #1 Double Room', 'geodir-booking' ), $key + 1, $listing['title'] );
							$title = '<a href="javascript:void(0);" class="geodir-booking-price-breakdown-listing geodir-booking-price-breakdown-expand btn-link text-primary" title="' . __( 'Expand', 'geodir-booking' ) . '">'
								. '<span class="geodir-booking-inner-icon">&plus;</span>'
								. '<span class="geodir-booking-inner-icon geodir-booking-hide">&minus;</span>'
								. $title
								. '</a>';

							echo $title;
							?>
						</td>
						<td class="geodir-booking-table-price-column">
							<?php echo wp_kses_post( wpinv_price( $booking->payable_amount ) ); ?>
						</td>
					</tr>
	
					<!-- Additional details hidden by default -->
					<tr class="geodir-booking-price-breakdown-adults geodir-booking-hide">
						<td colspan="1"><?php esc_html_e( 'Adults', 'geodir-booking' ); ?></td>
						<td><?php echo esc_html( $booking->adults ); ?></td>
					</tr>

					<tr class="geodir-booking-price-breakdown-children geodir-booking-hide">
						<td colspan="1"><?php esc_html_e( 'Children', 'geodir-booking' ); ?></td>
						<td><?php echo esc_html( $booking->children ); ?></td>
					</tr>

					<tr class="geodir-booking-price-breakdown-nights geodir-booking-hide">
						<td colspan="1"><?php esc_html_e( 'Nights', 'geodir-booking' ); ?></td>
						<td><?php echo esc_html( count( $booking->date_amounts ) ); ?></td>
					</tr>
	
					<!-- Dates breakdown -->
					<tr class="geodir-booking-price-breakdown-dates geodir-booking-hide">
						<th colspan="1"><?php esc_html_e( 'Dates', 'geodir-booking' ); ?></th>
						<th class="geodir-booking-table-price-column"><?php esc_html_e( 'Amount', 'geodir-booking' ); ?></th>
					</tr>

					<?php
					foreach ( $booking->format_date_amounts() as $date_amount ) :
						printf(
							'<tr class="geodir-booking-price-breakdown-dates geodir-booking-hide">
                                <td colspan="1">%s</td>
                                <td>
                                    <span class="text-success %s">%s</span>
                                </td>
                            </tr>',
							esc_html( $date_amount['date'] ),
							$aui_bs5 ? 'fw-semibold' : 'font-weight-bold',
							wp_kses_post( wpinv_price( $date_amount['amount'] ) )
						);
					endforeach;

					// Fees and commissions.
					$fees_breakdown_details = array(
						'extra_guest_fee' => __( 'Extra Guests Fee', 'geodir-booking' ),
						'cleaning_fee'    => __( 'Cleaning Fee', 'geodir-booking' ),
						'extra_guest_fee' => __( 'Extra Guests Fee', 'geodir-booking' ),
						'pet_fee'         => __( 'Pet Fee', 'geodir-booking' ),
						'service_fee'     => __( 'Service Fee', 'geodir-booking' ),
						'site_commission' => __( 'Site Commission', 'geodir-booking' ),
						'payable_amount'  => __( 'Total', 'geodir-booking' ),
					);

					foreach ( $fees_breakdown_details as $price_key => $price_label ) :
						if ( ! empty( $booking->{$price_key} ) ) {
							printf(
								'<tr class="geodir-booking-price-breakdown-%s geodir-booking-hide">
                                    <td colspan="1">%s</td>
                                    <td>
                                        <span class="text-success %s">%s</span>
                                    </td>
                                </tr>',
								esc_attr( $price_key ),
								esc_html( $price_label ),
								$aui_bs5 ? 'fw-semibold' : 'font-weight-bold',
								wp_kses_post( wpinv_price( $booking->{$price_key} ) )
							);
						}
					endforeach;

				endforeach;
				?>
				
				<!-- Fees and commissions -->
				<?php
                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo $generate_table_row( __( 'Fees', 'geodir-booking' ), $total_fees );
                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo $generate_table_row( __( 'Site Commission', 'geodir-booking' ), $total_site_commision );
                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo $generate_table_row( __( 'Total', 'geodir-booking' ), $booking_total );
				?>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Parses the selected rooms for booking.
	 *
	 * @param array $room_ids An array of selected room IDs.
	 * @return array An array of parsed rooms with their details.
	 */
	public function parse_rooms( $room_ids ) {
		$rooms = array();

		if ( empty( $room_ids ) ) {
			return $rooms;
		}

		foreach ( $room_ids as $listing_id => $room_ids ) {
			$listing_id = filter_var( $listing_id, FILTER_VALIDATE_INT );
			$listing    = geodir_get_post_info( absint( $listing_id ) );

			if ( ! isset( $listing->ID ) ) {
				continue;
			}

			foreach ( $room_ids as $room_id ) {
				$room_id = absint( $room_id );

				$rooms[] = array(
					'room_id'      => $room_id,
					'listing_id'   => $listing_id,
					'max_guests'   => $listing->property_guests,
					'has_children' => (bool) $listing->property_infants,
				);
			}
		}

		return $rooms;
	}

	/**
	 * Retrieves the available rooms for booking based on search criteria.
	 *
	 * @param string $checkin_date The check-in date in 'YYYY-MM-DD' format.
	 * @param string $checkout_date The check-out date in 'YYYY-MM-DD' format.
	 * @param int|null $listing_id The ID of the listing to filter by.
	 * @return array An array of available rooms with their details.
	 */
	public function get_available_rooms( $checkin_date, $checkout_date, $listing_id = null ) {
		$locked_rooms = array();

		$args = array(
			'availability_checkin_date'  => $checkin_date,
			'availability_checkout_date' => $checkout_date,
		);

		if ( ! empty( $listing_id ) && is_numeric( $listing_id ) ) {
			$args['listings'] = geodir_booking_post_id( $listing_id );
		}

		$bookings = geodir_get_bookings( $args );

		foreach ( $bookings as $booking ) {
			if ( in_array( $booking->status, array( 'confirmed', 'pending_confirmation' ) ) ) {
				$locked_rooms[] = geodir_booking_post_id( $booking->listing_id );
			}
		}

		$args = array(
			'order'       => 'ASC',
			'post_parent' => 0,
			'post_status' => 'publish',
		);

		if ( ! empty( $listing_id ) && is_numeric( $listing_id ) ) {
			$args['post__in'] = array( absint( $listing_id ) );
		} else {
			$args['post__not_in'] = $locked_rooms;
		}

		$available_rooms_query = geodir_booking_get_bookable_listings_query( $args );
		$results               = $available_rooms_query->get_posts();

		$available_rooms = array();

		foreach ( $results as $row ) {
			$room_id = geodir_booking_post_id( intval( $row->ID ) );

			$gd_post = geodir_get_post_info( $room_id );

			$gdb_multiple_units  = ! empty( $gd_post ) && ! empty( $gd_post->gdb_multiple_units ) ? true : false;
			$gdb_number_of_rooms = ! empty( $gd_post ) && ! empty( $gd_post->gdb_number_of_rooms ) ? absint( $gd_post->gdb_number_of_rooms ) : 0;

			if ( true === (bool) $gdb_multiple_units && $gdb_number_of_rooms > 0 ) {
				$rooms = geodir_get_listing_rooms( $room_id );

				$listing_rooms = array_filter(
					$rooms,
					function ( $room_id ) use ( $locked_rooms ) {
						return ! in_array( (int) $room_id, $locked_rooms );
					}
				);

				if ( ! empty( $listing_rooms ) ) {
					$available_rooms[ $room_id ] = $listing_rooms;
				}
			} elseif ( ! in_array( (int) $room_id, $locked_rooms ) ) {
				$available_rooms[ $room_id ] = array( $room_id );
			}
		}

		return $available_rooms;
	}

	/**
	 * Filters the available rooms by the capacity of adults and children.
	 *
	 * @param array $rooms An array of available rooms with their details.
	 * @param int $adults The number of adults.
	 * @param int $children The number of children.
	 * @return array An array of filtered rooms based on capacity.
	 */
	public function filter_rooms_by_capacity( $rooms, $adults = 0, $children = 0 ) {
		foreach ( array_keys( $rooms ) as $listing_id ) {
			$listing = geodir_get_post_info( $listing_id );

			if ( ! $listing->gdbooking ) {
				unset( $rooms[ $listing_id ] );
			}

			if ( is_null( $listing ) || ( ! empty( $listing->property_guests ) && $listing->property_guests < ( (int) $adults + (int) $children ) ) ) {
				unset( $rooms[ $listing_id ] );
			}
		}

		return $rooms;
	}

	/**
	 * Pulls information about the available rooms.
	 *
	 * @param array $rooms An array of available rooms with their details.
	 * @return array An array of room information pulled from the database.
	 */
	public function pull_rooms_info( $rooms ) {
		$info = array();

		foreach ( $rooms as $listing_id => $room_ids ) {
			$listing = geodir_get_post_info( $listing_id );

			$info[ $listing_id ] = array(
				'id'       => $listing_id,
				'title'    => $listing->post_title,
				'url'      => get_permalink( $listing_id ),
				'guests'   => ! empty( $listing->property_guests ) ? $listing->property_guests : 0,
				'children' => (bool) $listing->property_infants,
				'price'    => (float) $listing->gdbprice,
				'rooms'    => array(),
			);

			foreach ( $room_ids as $room_id ) {
				$room = get_post( (int) $room_id );

				$info[ $listing_id ]['rooms'][] = array(
					'id'         => $room_id,
					'listing_id' => $listing_id,
					'title'      => $room->post_title,
				);
			}
		}

		return $info;
	}

	/**
	 * Displays the add booking page.
	 *
	 * @return void
	 *
	 */
	public function display() {
		$action_url = admin_url(
			add_query_arg(
				array(
					'page' => 'geodir-booking-add-new',
				),
				'admin.php'
			)
		);
		?>
		<div class="wrap">
			<h1 class="wp-heading-inline"><?php esc_html_e( 'Add New Booking', 'geodir-booking' ); ?></h1>
			<a href="<?php echo esc_url( $action_url ); ?>" class="page-title-action"><?php esc_html_e( 'Clear Search Results', 'geodir-booking' ); ?></a>
			<hr class="wp-header-end" />

			<?php
			if ( 'reserve-booking' === $this->action ) {
				$this->display_booking_form();
			} elseif ( $this->is_search ) {
				$this->display_search_form();

				if ( empty( $this->errors ) ) {
					$this->display_booking_rooms();
				}
			} else {
				$this->display_search_form();
			}
			?>
		</div>
		<?php
	}

	/**
	 * Displays the search form for booking.
	 */
	public function display_search_form() {
		global $aui_bs5;

		$listings_options = $this->get_listings_options();
		$adults_options   = $this->get_adults_options();
		$children_options = $this->get_children_options();

		$action_url = admin_url(
			add_query_arg(
				array(
					'page' => 'geodir-booking-add-new',
				),
				'admin.php'
			)
		);

		?>
		<form method="GET" class="bsui geodir-booking-search-form" action="<?php echo esc_url( $action_url ); ?>">
			<input type="hidden" name="page" value="geodir-booking-add-new">
			<input type="hidden" name="action" value="search">
			<div class="postbox mt-3">
				<div class="inside pb-0">
					<p class="mt-2 mb-3"><small><?php esc_html_e( 'Required fields are followed by', 'geodir-booking' ); ?><abbr title="required">*</abbr></small>

					<?php
					if ( ! empty( $this->errors ) ) {
						$error = current( $this->errors );
						aui()->alert(
							array(
								'type'    => 'danger',
								'class'   => 'd-inline-block w-auto',
								'content' => $error,
							),
							true
						);
					}
					?>

					<div class="row">
						<div class="col-xl-2 col-lg-6 col-md-6 col-sm-6">
							<?php
							aui()->input(
								array(
									'type'        => 'datepicker',
									'id'          => 'geodir-booking-start_date',
									'name'        => 'gdbc_check_in',
									'label'       => __( 'Check-in *', 'geodir-booking' ),
									'label_type'  => 'top',
									'label_class' => ( $aui_bs5 ? 'fw-semibold' : 'font-weight-bold' ),
									'size'        => 'sm',
									'placeholder' => __( 'Check-in Date', 'geodir-booking' ),
									'required'    => true,
									'value'       => esc_attr( $this->checkin_date ),
								),
								true
							);
							?>
						</div>
						<div class="col-xl-2 col-lg-6 col-md-6 col-sm-6">
							<?php
							aui()->input(
								array(
									'type'        => 'datepicker',
									'id'          => 'geodir-booking-end_date',
									'name'        => 'gdbc_check_out',
									'label'       => __( 'Check-out *', 'geodir-booking' ),
									'label_type'  => 'top',
									'label_class' => ( $aui_bs5 ? 'fw-semibold' : 'font-weight-bold' ),
									'size'        => 'sm',
									'placeholder' => __( 'Check-out Date', 'geodir-booking' ),
									'required'    => true,
									'value'       => esc_attr( $this->checkout_date ),
								),
								true
							);
							?>
						</div>
						<div class="col-xl-2 col-lg-6 col-md-6 col-sm-6">
							<?php
							aui()->select(
								array(
									'type'        => 'select',
									'id'          => 'geodir-booking-listing-id',
									'name'        => 'gdbc_listing_id',
									'label'       => __( 'Listing', 'geodir-booking' ),
									'label_type'  => 'top',
									'label_class' => ( $aui_bs5 ? 'fw-semibold' : 'font-weight-bold' ),
									'size'        => 'sm',
									'select2'     => true,
									'options'     => $listings_options,
									'class'       => 'w-100',
									'value'       => esc_attr( $this->listing_id ),
								),
								true
							);
							?>
						</div>
						<div class="col-xl-2 col-md-3 col-sm-6">
							<?php
							aui()->select(
								array(
									'type'        => 'select',
									'id'          => 'geodir-booking-adults',
									'name'        => 'gdbc_adults',
									'label'       => __( 'Adults', 'geodir-booking' ),
									'label_type'  => 'hortopizontal',
									'label_class' => ( $aui_bs5 ? 'fw-semibold' : 'font-weight-bold' ),
									'size'        => 'sm',
									'select2'     => true,
									'options'     => $adults_options,
									'class'       => 'w-100',
									'value'       => esc_attr( $this->adults ),
								),
								true
							);
							?>
						</div>
						<div class="col-xl-2 col-md-3 col-sm-6">
							<?php
								aui()->select(
									array(
										'type'        => 'select',
										'id'          => 'geodir-booking-children',
										'name'        => 'gdbc_children',
										'label'       => __( 'Children', 'geodir-booking' ),
										'label_type'  => 'top',
										'label_class' => ( $aui_bs5 ? 'fw-semibold' : 'font-weight-bold' ),
										'size'        => 'sm',
										'select2'     => true,
										'options'     => $children_options,
										'class'       => 'w-100',
										'value'       => esc_attr( $this->children ),
									),
									true
								);
							?>
						</div>
						<div class="col-md-2 col-sm-12 d-flex align-items-center">
							<div>
								<?php
								aui()->button(
									array(
										'type'    => 'submit',
										'class'   => 'btn btn-primary',
										'content' => __( 'Search', 'geodir-booking' ),
									),
									true
								);
								?>
							</div>
							
						</div>
					</div>
				</div>
			</div>
		</form>
		<?php
	}

	/**
	 * Displays the available booking rooms based on search criteria.
	 */
	public function display_booking_rooms() {
		$rooms          = $this->get_available_rooms( $this->checkin_date, $this->checkout_date, $this->listing_id );
		$rooms          = $this->filter_rooms_by_capacity( $rooms, $this->adults, $this->children );
		$listings       = $this->pull_rooms_info( $rooms );
		$found_listings = count( $listings );

		$action_url = admin_url(
			add_query_arg(
				array(
					'page'   => 'geodir-booking-add-new',
					'action' => 'reserve-booking',
				),
				'admin.php'
			)
		);

		?>
			<form method="POST" class="geodir-booking-reserve-booking bsui" action="<?php echo esc_url( $action_url ); ?>">
				<input type="hidden" name="gdbc_check_in_date" value="<?php echo esc_html( $this->checkin_date ); ?>">
				<input type="hidden" name="gdbc_check_out_date" value="<?php echo esc_html( $this->checkout_date ); ?>">

				<?php if ( $this->adults > 0 ) : ?>
					<input type="hidden" name="gdbc_adults" value="<?php echo absint( $this->adults ); ?>">
				<?php endif; ?>

				<?php if ( $this->children > 0 ) : ?>
					<input type="hidden" name="gdbc_children" value="<?php echo absint( $this->children ); ?>">
				<?php endif; ?>

				<div class="geodir-booking-search-results bsui">
					<h2 class="h6"><?php esc_html_e( 'Search Results', 'geodir-booking' ); ?></h2>
				</div>

				<p class="geodir-search-results-summary">
					<?php
					if ( $found_listings > 0 ) {
						printf(
							esc_html( _n( '%s listing found', '%s listings found', $found_listings, 'geodir-booking' ) ),
							esc_html( number_format_i18n( $found_listings ) )
						);
					} else {
						esc_html_e( 'No listings found', 'geodir-booking' );
					}

					printf(
						esc_html__( ' from %1$s - till %2$s', 'geodir-booking' ),
						esc_html( geodir_booking_date( $this->checkin_date, 'view_day' ) ),
						esc_html( geodir_booking_date( $this->checkout_date, 'view_day' ) )
					);
					?>
				</p>

				<?php foreach ( $listings as $listing_id => $listing ) : ?>
					<?php $rooms = $listing['rooms']; ?>

					<h6 class="fs-sm">
						<a href="<?php echo esc_url( $listing['url'] ); ?>" target="_blank"><?php echo esc_html( $listing['title'] ); ?></a>
					</h6>

					<table class="widefat striped fixed mb-3">
						<thead>
							<tr>
								<th class="check-column">&nbsp;</th>
								<th class="row-title"><?php esc_html_e( 'Title', 'geodir-booking' ); ?></th>
								<th class="row-title"><?php esc_html_e( 'Guests', 'geodir-booking' ); ?></th>
								<th class="row-title"><?php esc_html_e( 'Nightly price', 'geodir-booking' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $rooms as $room ) : ?>
								<tr>
									<td>
										<input type="checkbox" name="gdbc_rooms[<?php echo esc_attr( $room['listing_id'] ); ?>][]" value="<?php echo esc_attr( $room['id'] ); ?>" id="gdbc_rooms-<?php echo esc_attr( $room['id'] ); ?>"/>
									</td>
									<td>
										<label for="gdbc_rooms-<?php echo esc_attr( $room['id'] ); ?>">
											<?php echo esc_html( $room['title'] ); ?>
										</label>
									</td>
									<td>
										<?php
										if ( $this->adults > 0 ) {
											echo esc_html__( 'Adults:', 'geodir-booking' ) . '&nbsp;' . esc_html( absint( $this->adults ) );
										} elseif ( $listing['guests'] > 0 ) {
											echo esc_html__( 'Max guests:', 'geodir-booking' ) . '&nbsp;' . esc_html( $listing['guests'] );
										}

										if ( $this->children > 0 ) {
											echo ',&nbsp;' . esc_html__( 'Children:', 'geodir-booking' ) . '&nbsp;' . esc_html( absint( $this->children ) );
										} elseif ( $listing['children'] ) {
											echo ',&nbsp;' . esc_html__( 'Suitable for children', 'geodir-booking' );
										}
										?>
									</td>
									<td>
										<?php echo wp_kses_post( wpinv_price( $listing['price'] ) ); ?>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				<?php endforeach; ?>

				<?php
				if ( ! empty( $listings ) ) :
					aui()->button(
						array(
							'type'    => 'submit',
							'class'   => 'btn btn-primary',
							'content' => __( 'Reserve', 'geodir-booking' ),
						),
						true
					);
				endif;
				?>
			</form>
		<?php
	}

	/**
	 * Displays an alert when there are no available rooms for reservation.
	 */
	public function display_empty_rooms_alert() {
		$output      = '<div class="bsui">';
			$output .= '<div class="col-lg-6 col-md-12">';
			$output .= aui()->alert(
				array(
					'type'    => 'danger',
					'class'   => 'd-inline-block w-auto mt-2',
					'content' => __( 'There are no listings selected for reservation.', 'geodir-booking' ),
				)
			);
			$output .= '</div>';
		$output     .= '</div>';

        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $output;
	}

	/**
	 * Displays the booking form for selected rooms.
	 */
	public function display_booking_form() {
		global $aui_bs5;

		$this->checkin_date  = isset( $_POST['gdbc_check_in_date'] ) && ! empty( $_POST['gdbc_check_in_date'] ) ? wp_unslash( $_POST['gdbc_check_in_date'] ) : null;
		$this->checkout_date = isset( $_POST['gdbc_check_out_date'] ) && ! empty( $_POST['gdbc_check_out_date'] ) ? wp_unslash( $_POST['gdbc_check_out_date'] ) : null;
		$this->adults        = isset( $_POST['gdbc_adults'] ) && ! empty( $_POST['gdbc_adults'] ) ? absint( $_POST['gdbc_adults'] ) : null;
		$this->children      = isset( $_POST['gdbc_children'] ) && ! empty( $_POST['gdbc_children'] ) ? absint( $_POST['gdbc_children'] ) : null;

		if ( $this->checkin_date && $this->checkout_date ) {
			$room_ids = isset( $_POST['gdbc_rooms'] ) && ! empty( $_POST['gdbc_rooms'] ) ? wp_unslash( $_POST['gdbc_rooms'] ) : array();

			$this->rooms = $this->parse_rooms( $room_ids );
		}

		if ( empty( $this->rooms ) ) {
			return $this->display_empty_rooms_alert();
		}

		foreach ( $this->rooms as $room ) {
			$adults = $this->adults ? $this->adults : $room['max_guests'];

			$booking = new GeoDir_Customer_Booking();
			$booking->set_args(
				array(
					'start_date' => $this->checkin_date,
					'end_date'   => $this->checkout_date,
					'listing_id' => $room['listing_id'],
					'guests'     => ( $adults + $this->children ),
					'adults'     => $adults,
					'children'   => $room['has_children'] ? $this->children : 0,
				)
			);

			$this->bookings[] = $booking;
		}

		$booking_status_options = geodir_get_booking_statuses();
		unset( $booking_status_options['cancelled'] );
		unset( $booking_status_options['rejected'] );
		unset( $booking_status_options['refunded'] );

		$action_url = admin_url(
			add_query_arg(
				array(
					'page'   => 'geodir-booking-add-new',
					'action' => 'reserve-booking',
				),
				'admin.php'
			)
		);

		?>
		<section id="geodir-booking-details" class="geodir-booking-details geodir-checkout-section bsui">
			<form id="poststuff" method="POST" class="geodir-booking-create-booking row" action="<?php echo esc_url( $action_url ); ?>">
				<input type="hidden" name="gdbc_check_in_date" value="<?php echo esc_html( $this->checkin_date ); ?>">
				<input type="hidden" name="gdbc_check_out_date" value="<?php echo esc_html( $this->checkout_date ); ?>">

				<div class="col-lg-5 col-md-6">
					<div class="postbox">
						<div class="postbox-header">
							<h2 class="geodir-booking-details-title">
								<?php esc_html_e( 'Booking Details', 'geodir-booking' ); ?>
							</h2>
						</div>
						
						<div class="inside pt-2">
							<p class="geodir-booking-check-in-date">
								<span><?php esc_html_e( 'Check-in:', 'geodir-booking' ); ?></span>
								<time datetime="<?php echo esc_attr( $this->get_check_in_date()->format( 'Y-m-d' ) ); ?>">
									<strong>
										<?php echo esc_html( $this->get_check_in_date()->format( 'F j, Y' ) ); ?>
									</strong>
								</time>
							</p>

							<p class="geodir-booking-check-out-date">
								<span><?php esc_html_e( 'Check-out:', 'geodir-booking' ); ?></span>
								<time datetime="<?php echo esc_attr( $this->get_check_out_date()->format( 'Y-m-d' ) ); ?>">
									<strong>
										<?php echo esc_html( $this->get_check_out_date()->format( 'F j, Y' ) ); ?>
									</strong>
								</time>
							</p>

							<div class="geodir-booking-reserve-rooms-details">
								<?php
								foreach ( $this->rooms as $index => $room ) :
									$listing          = geodir_get_post_info( absint( $room['listing_id'] ) );
									$max_guests       = $listing->property_guests ? $listing->property_guests : 30;
									$adults_options   = $this->get_adults_options( $max_guests );
									$children_options = $this->get_children_options();

									$room_adults = ( ! empty( $this->adults ) && $this->adults <= $listing->property_guests ) ? $listing->property_guests : $listing->property_guests;
									?>
									<div class="geodir-booking-room-details" data-index="<?php echo esc_attr( $index ); ?>">
										<input type="hidden"
												name="gdbc_room_details[<?php echo esc_attr( $index ); ?>][room_id]"
												value="<?php echo esc_attr( $room['room_id'] ); ?>"
												/>

										<h6 class="geodir-booking-room-number">
											<?php echo esc_html( sprintf( __( 'Room #%d', 'geodir-booking' ), $index + 1 ) ); ?>
										</h6>

										<p class="geodir-booking-room-type-title">
											<span>
												<?php esc_html_e( 'Listing:', 'geodir-booking' ); ?>
											</span>
											<a class="btn-link text-primary" href="<?php echo esc_url( get_permalink( $listing->ID ) ); ?>" target="_blank">
												<?php echo esc_html( $listing->post_title ); ?>
											</a>
										</p>

										<?php
										aui()->select(
											array(
												'type'    => 'select',
												'id'      => esc_attr( uniqid() ) . '-adults',
												'name'    => "gdbc_room_details[{$index}][adults]",
												'label'   => ( $listing->property_infants ? __( 'Adults', 'geodir-booking' ) : __( 'Guests', 'geodir-booking' ) ),
												'label_type' => 'horizontal',
												'label_class' => ( $aui_bs5 ? 'fw-semibold' : 'font-weight-bold' ),
												'label_col' => '3',
												'size'    => 'sm',
												'select2' => true,
												'options' => $adults_options,
												'class'   => 'w-100',
												'value'   => esc_attr( $room_adults ),
											),
											true
										);

										if ( true === (bool) $listing->property_infants ) :
											aui()->select(
												array(
													'type' => 'select',
													'id'   => esc_attr( uniqid() ) . '-children',
													'name' => "gdbc_room_details[{$index}][children]",
													'label' => __( 'Children', 'geodir-booking' ),
													'label_type' => 'horizontal',
													'label_class' => ( $aui_bs5 ? 'fw-semibold' : 'font-weight-bold' ),
													'label_col' => '3',
													'size' => 'sm',
													'select2' => true,
													'options' => $children_options,
													'class' => 'w-100',
													'value' => esc_attr( $this->children ),
												),
												true
											);
										endif;
										?>
									</div>
								<?php endforeach; ?>
							</div>
						</div>
					</div>
					
					<div class="postbox">
						<div class="postbox-header">
							<h2 class="geodir-booking-details-title">
								<?php esc_html_e( 'Guest Information', 'geodir-booking' ); ?>
							</h2>
						</div>
						<div class="inside pt-2">
							<div id="geodir-booking-customer-details">
								<?php
									aui()->input(
										array(
											'type'        => 'text',
											'id'          => 'geodir-booking-customer_name',
											'name'        => 'gdbc_customer_name',
											'label'       => __( 'Full Name', 'geodir-booking' ),
											'label_type'  => 'horizontal',
											'label_class' => ( $aui_bs5 ? 'fw-semibold' : 'font-weight-bold' ),
											'label_col'   => '3',
											'size'        => 'sm',
											'required'    => true,
										),
										true
									);

									aui()->input(
										array(
											'type'        => 'text',
											'id'          => 'geodir-booking-customer_email',
											'name'        => 'gdbc_customer_email',
											'label'       => __( 'Email', 'geodir-booking' ),
											'label_type'  => 'horizontal',
											'label_class' => ( $aui_bs5 ? 'fw-semibold' : 'font-weight-bold' ),
											'label_col'   => '3',
											'size'        => 'sm',
											'required'    => true,
										),
										true
									);

									aui()->input(
										array(
											'type'        => 'text',
											'id'          => 'geodir-booking-customer_phone',
											'name'        => 'gdbc_customer_phone',
											'label'       => __( 'Phone', 'geodir-booking' ),
											'label_type'  => 'horizontal',
											'label_class' => ( $aui_bs5 ? 'fw-semibold' : 'font-weight-bold' ),
											'label_col'   => '3',
											'size'        => 'sm',
											'required'    => true,
										),
										true
									);

									aui()->textarea(
										array(
											'id'          => 'geodir-booking-private_note',
											'name'        => 'gdbc_private_note',
											'label'       => __( 'Private Notes', 'geodir-booking' ),
											'label_type'  => 'horizontal',
											'label_class' => ( $aui_bs5 ? 'fw-semibold' : 'font-weight-bold' ),
											'label_col'   => '3',
											'size'        => 'sm',
										),
										true
									);
								?>
							</div>
						</div>
					</div>
				</div>

				<div class="col-lg-4 col-md-6 offset-lg-1">
					<div class="postbox">
						<div class="postbox-header">
							<h2 class="geodir-booking-details-title">
								<?php esc_html_e( 'Price Breakdown', 'geodir-booking' ); ?>
							</h2>
						</div>

						<div class="inside">
							<?php $this->display_price_breakdown( $this->bookings ); ?>

							<?php
							aui()->select(
								array(
									'type'        => 'select',
									'id'          => 'geodir-booking-status',
									'name'        => 'gdbc_booking_status',
									'label'       => __( 'Status', 'geodir-booking' ),
									'label_type'  => 'horizontal',
									'label_col'   => '3',
									'label_class' => ( $aui_bs5 ? 'd-block fw-semibold' : 'd-block font-weight-bold' ),
									'size'        => 'sm',
									'select2'     => true,
									'options'     => $booking_status_options,
									'class'       => '',
								),
								true
							);

							aui()->alert(
								array(
									'type'    => 'danger',
									'content' => '<span></span>',
									'class'   => 'geodir-booking-hide',
								),
								true
							);

							aui()->alert(
								array(
									'type'    => 'success',
									'content' => '<span></span>',
									'class'   => 'geodir-booking-hide',
								),
								true
							);

							aui()->button(
								array(
									'type'    => 'submit',
									'class'   => 'btn btn-primary w-100',
									'content' => __( 'Book Now', 'geodir-booking' ),
								),
								true
							);
							?>
						</div>
					</div>
									
				</div>
			</form>
		</section>
		<?php
	}
}
