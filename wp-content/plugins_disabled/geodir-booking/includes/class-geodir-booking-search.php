<?php
/**
 * Booking Search class
 *
 * @package GeoDir_Booking
 * @author AyeCode Ltd
 * @since 2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * GeoDir_Booking_Search class.
 */
class GeoDir_Booking_Search {
	/**
	 * Initialize
	 *
	 * @since 2.0
	 */
	public function __construct() {
		if ( is_admin() ) {
			// Advance search settings.
			if ( geodir_design_style() ) {
				add_filter( 'geodir_advance_search_field_in_main_search_bar', array( __CLASS__, 'search_field_in_main_search_bar' ), 11, 3 );
				add_action( 'geodir_search_cfa_before_save_button', array( __CLASS__, 'search_field_before_save_button' ), 10, 4 );
			}
		}

		if ( geodir_design_style() ) {
			add_filter( 'geodir_search_output_to_main_field_property_guests', array( __CLASS__, 'main_search_input_guests' ), 10, 3 );
			add_filter( 'geodir_search_output_to_main_field_property_infants', array( __CLASS__, 'skip_guests_inputs' ), 20, 3 );
			add_filter( 'geodir_search_output_to_main_field_property_pets', array( __CLASS__, 'skip_guests_inputs' ), 20, 3 );
			add_filter( 'geodir_search_output_to_advance_field_property_infants', array( __CLASS__, 'skip_guests_inputs' ), 20, 3 );
			add_filter( 'geodir_search_output_to_advance_field_property_pets', array( __CLASS__, 'skip_guests_inputs' ), 20, 3 );
			add_action( 'geodir_adv_search_inline_script', array( __CLASS__, 'search_inline_script' ), 10, 1 );
			add_action( 'geodir_search_filter_searched_params', array( __CLASS__, 'show_filters' ), 20, 3 );
		}
	}

	/**
	 * Check if guests search is enabled.
	 *
	 * This function checks if the guests search feature is available and enabled.
	 *
	 * @since 2.0
	 *
	 * @global wpdb $wpdb WordPress database abstraction object.
	 *
	 * @return bool True if guests search is enabled, false otherwise.
	 */
	public static function has_guests_search() {
		global $wpdb;

		if ( ! ( defined( 'GEODIR_ADV_SEARCH_VERSION' ) && geodir_design_style() ) ) {
			return false;
		}

		return (bool) $wpdb->get_var( 'SELECT COUNT( * ) FROM `' . GEODIR_ADVANCE_SEARCH_TABLE . "` WHERE `htmlvar_name` = 'property_guests' AND `main_search` = 1 LIMIT 1" );
	}

	/**
	 * Get the guests fields for a given post type.
	 *
	 * This function retrieves the guests fields for a specified post type from the database.
	 *
	 * @since 2.0
	 *
	 * @param string $post_type The post type to retrieve the guests fields for.
	 * @return array An array of guests fields for the specified post type.
	 */
	public static function guests_fields( $post_type ) {
		global $wpdb, $geodir_guests_fields;

		$fields = array();

		if ( empty( $geodir_guests_fields ) ) {
			$geodir_guests_fields = array();
		}

		if ( isset( $geodir_guests_fields[ $post_type ] ) ) {
			return $geodir_guests_fields[ $post_type ];
		}

		if ( ! ( defined( 'GEODIR_ADV_SEARCH_VERSION' ) && geodir_design_style() ) ) {
			return $fields;
		}

		$results = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM `' . GEODIR_ADVANCE_SEARCH_TABLE . "` WHERE `post_type` = %s AND `htmlvar_name` IN ( 'property_guests', 'property_infants', 'property_pets' ) ORDER BY `id` DESC", $post_type ) );

		if ( ! empty( $results ) ) {
			foreach ( $results as $row ) {
				$fields[ $row->htmlvar_name ] = $row;
			}
		}

		if ( empty( $fields['property_guests']->main_search ) ) {
			$geodir_guests_fields[ $post_type ] = array();

			return array();
		}

		$geodir_guests_fields[ $post_type ] = $fields;

		return $fields;
	}

	/**
	 * Determine if the property guests field should be shown in the main search bar.
	 *
	 * This function checks if the property guests field should be shown in the main search bar
	 * based on the field's htmlvar_name.
	 *
	 * @since 2.0
	 *
	 * @param bool $show Whether the field should be shown in the main search bar.
	 * @param object $field The field object.
	 * @param object $cf The custom field object.
	 * @return bool True if the field should be shown, false otherwise.
	 */
	public static function search_field_in_main_search_bar( $show, $field, $cf ) {
		if ( ! empty( $field->htmlvar_name ) && $field->htmlvar_name == 'property_guests' ) {
			// Show main search bar setting.
			$show = true;
		}

		return $show;
	}

	/**
	 * Add custom styles to the form settings view.
	 *
	 * This function adds custom styles to the form settings view to hide certain elements
	 * based on the field's htmlvar_name.
	 *
	 * @since 2.0
	 *
	 * @param string $field_type The type of field.
	 * @param object $field The field object.
	 * @param object $cf The custom field object.
	 * @param string $key The key of the field.
	 */
	public static function search_field_before_save_button( $field_type, $field, $cf, $key ) {
		if ( ! empty( $field->htmlvar_name ) && $field->htmlvar_name == 'property_guests' ) {
			?><style>.gd-form-settings-view [data-argument="data_type_change"],.gd-form-settings-view [data-argument="search_condition_select"],.gd-form-settings-view [data-argument="gd_range_min<?php echo esc_attr( $key ); ?>"],.gd-form-settings-view [data-argument="gd_range_max<?php echo esc_attr( $key ); ?>"],.gd-form-settings-view [data-argument="gd_range_step<?php echo esc_attr( $key ); ?>"],.gd-form-settings-view [data-argument="gd_range_start<?php echo esc_attr( $key ); ?>"],.gd-form-settings-view [data-argument="gd_range_from_title<?php echo esc_attr( $key ); ?>"],.gd-form-settings-view [data-argument="gd_range_to_title<?php echo esc_attr( $key ); ?>"],.gd-form-settings-view [data-argument="gd_range_expand<?php echo esc_attr( $key ); ?>"]{display:none!important}</style>
																																																	<?php
		}
	}

	/**
	 * Generate the HTML for the guests search input field.
	 *
	 * This function generates the HTML for the guests search input field based on the provided field object.
	 *
	 * @since 2.0
	 *
	 * @param string $output The HTML output for the guests search input field.
	 * @param object $field The field object.
	 * @param string $post_type The post type.
	 * @return string The HTML output for the guests search input field.
	 */
	public static function main_search_input_guests( $output, $field, $post_type ) {
		global $aui_bs5;

		$htmlvar_name  = $field->htmlvar_name;
		$title         = esc_html__( $field->frontend_title, 'geodirectory' );
		$field_id      = '_' . uniqid();
		$adults        = ! empty( $_REQUEST['adults'] ) ? absint( $_REQUEST['adults'] ) : 0;
		$children      = ! empty( $_REQUEST['children'] ) ? absint( $_REQUEST['children'] ) : 0;
		$guests        = isset( $_REQUEST['sproperty_guests'] ) ? absint( $_REQUEST['sproperty_guests'] ) : $adults + $children;
		$infants       = isset( $_REQUEST['sproperty_infants'], $_REQUEST['infants'] ) ? absint( $_REQUEST['infants'] ) : 0;
		$pets          = isset( $_REQUEST['sproperty_pets'], $_REQUEST['pets'] ) ? absint( $_REQUEST['pets'] ) : 0;
		$guests_fields = self::guests_fields( $post_type );

		$search_terms = array();
		if ( $guests > 0 ) {
			$search_terms[] = sprintf(
				_n( '%1$d %2$s', '%1$d %2$s', $guests, 'geodir-booking' ),
				$guests,
				$guests > 1 ? esc_html_x( 'guests', 'guests search', 'geodir-booking' ) : esc_html_x( 'guest', 'guests search', 'geodir-booking' )
			);
		}

		if ( ! empty( $_REQUEST['sproperty_infants'] ) && ! empty( $guests_fields['property_infants'] ) ) {
			$search_terms[] = sprintf(
				_n( '%1$d %2$s', '%1$d %2$s', $infants, 'geodir-booking' ),
				$infants,
				$infants > 1 ? esc_html_x( 'infants', 'guests search', 'geodir-booking' ) : esc_html_x( 'infant', 'guests search', 'geodir-booking' )
			);
		}

		if ( ! empty( $_REQUEST['sproperty_pets'] ) && ! empty( $guests_fields['property_pets'] ) ) {
			$search_terms[] = sprintf(
				_n( '%1$d %2$s', '%1$d %2$s', $pets, 'geodir-booking' ),
				$pets,
				$pets > 1 ? esc_html_x( 'pets', 'guests search', 'geodir-booking' ) : esc_html_x( 'pet', 'guests search', 'geodir-booking' )
			);
		}

		$search_term = ! empty( $search_terms ) ? implode( ', ', $search_terms ) : '';

		$wrap_attrs = geodir_search_conditional_field_attrs( $field );

		$input_group_html = sprintf(
			'<div class="%s position-absolute h-100">
				<div class="input-group-text px-2 bg-transparent border-0">
					<span class="geodir-search-input-label hover-swap text-muted">
						<i class="fas fa-user hover-content-original"></i>
						<i class="fas fa-times geodir-search-input-label-clear hover-content c-pointer" title="%s"></i>
					</span>
				</div>
			</div>',
			$aui_bs5 ? '' : 'input-group-prepend',
			esc_attr__( 'Clear field', 'geodir-booking' )
		);

		$dropdown_html = sprintf(
			'<div class="dropdown-menu dropdown-caret-0 my-1 px-3 py-2 w-100 geodir-dropdown-guests" aria-labelledby="%s" style="min-width:18rem;z-index:99999">',
			esc_attr( $field_id )
		);

		// Adults counter
		$dropdown_html .= self::generate_counter_html( 'adults', $adults, __( 'Adults', 'geodir-booking' ), __( 'Ages 13 or above', 'geodir-booking' ), $aui_bs5 );

		// Children counter
		$dropdown_html .= '<div class="dropdown-divider mx-0"></div>';
		$dropdown_html .= self::generate_counter_html( 'children', $children, __( 'Children', 'geodir-booking' ), __( 'Ages 2-12', 'geodir-booking' ), $aui_bs5 );

		// Infants counter
		if ( ! empty( $guests_fields['property_infants'] ) ) {
			$dropdown_html .= '<div class="dropdown-divider mx-0"></div>';
			$dropdown_html .= self::generate_counter_html( 'infants', $infants, __( 'Infants', 'geodir-booking' ), __( '2 and under', 'geodir-booking' ), $aui_bs5 );
		}

		// Pets counter
		if ( ! empty( $guests_fields['property_pets'] ) ) {
			$dropdown_html .= '<div class="dropdown-divider mx-0"></div>';
			$dropdown_html .= self::generate_counter_html( 'pets', $pets, __( 'Pets', 'geodir-booking' ), __( 'Charges may apply', 'geodir-booking' ), $aui_bs5 );
		}

		$dropdown_html .= '</div>';

		$output = sprintf(
			'<div class="gd-search-field-search col-auto flex-fill%1$s gd-search-field%2$s" style="flex-grow:9999!important;"%3$s>
				<div class="%4$s">
					<label for="%5$s" class="sr-only visually-hidden">%6$s</label>
					<div class="input-group-inside position-relative w-100">
						%7$s
						<input id="%5$s" type="text" placeholder="%8$s" value="%9$s" class="form-control geodir-guests-search w-100 c-pointer dropdown-toggle%10$s" onkeydown="return false;" autocomplete="off" size="16" aria-label="%8$s" data-%11$stoggle="dropdown" aria-haspopup="true" aria-expanded="false"%12$s>
						<input id="geodir_search_property_adults" name="sproperty_guests" type="hidden" value="%13$s">
						%14$s
					</div>
				</div>
			</div>',
			$aui_bs5 ? ' px-0' : '',
			esc_attr( $htmlvar_name ),
			$wrap_attrs,
			$aui_bs5 ? '' : 'form-group',
			esc_attr( $field_id ),
			esc_html( $title ),
			$input_group_html,
			esc_attr( $title ),
			esc_attr( $search_term ),
			$aui_bs5 ? ' ps-4' : ' pl-4',
			$aui_bs5 ? 'bs-' : '',
			$aui_bs5 ? ' data-bs-auto-close="outside"' : '',
			$guests > 0 ? esc_attr( $guests ) : '',
			$dropdown_html
		);

		return $output;
	}

	/**
	 * Generate the HTML for the guests search counter field.
	 *
	 * This function generates the HTML for the guests search counter field based on the provided field object.
	 *
	 * @since 2.0
	 *
	 * @param string $type The type of the field.
	 * @param int $count The count of the field.
	 * @param string $label The label of the field.
	 * @param string $sublabel The sublabel of the field.
	 * @param bool $aui_bs5 Whether to use AUI BS5 styles.
	 * @return string The HTML for the guests search counter field.
	 */
	private static function generate_counter_html( $type, $count, $label, $sublabel, $aui_bs5 ) {
		return sprintf(
			'<div data-argument="geodir_search_%1$s_count" class="%2$s row geodir-guests-search-group mb-0 align-items-center">
				<label for="geodir_search_%1$s_count" class="col-sm-6 col-form-label">%3$s<small class="d-block text-muted">%4$s</small></label>
				<div class="col-sm-6">
					<div class="input-group input-group-sm flex-nowrap geodir-counter-wrap">
						<div class="%5$s">
							<span class="input-group-text border-0 bg-transparent p-0 c-pointer text-primary geodir-counter-btn%6$s" data-counter="minus">
								<i aria-hidden="true" class="fas fa-circle-minus fa-2x"></i>
							</span>
						</div>
						<span class="input-group-text border-0 px-3 bg-transparent geodir-counter-val%6$s">%7$s</span>
						<input class="geodir-counter-input" name="%1$s" type="hidden" value="%7$s">
						<div class="%8$s">
							<span class="input-group-text border-0 bg-transparent p-0 c-pointer text-primary geodir-counter-btn%6$s" data-counter="plus">
								<i aria-hidden="true" class="fas fa-circle-plus fa-2x"></i>
							</span>
						</div>
					</div>
				</div>
			</div>',
			esc_attr( $type ),
			$aui_bs5 ? '' : 'form-group',
			esc_html( $label ),
			esc_html( $sublabel ),
			$aui_bs5 ? '' : 'input-group-prepend',
			$aui_bs5 ? ' position-relative' : '',
			esc_attr( $count ),
			$aui_bs5 ? '' : 'input-group-append'
		);
	}

	/**
	 * Generate the HTML for the guests search checkbox field.
	 *
	 * This function generates the HTML for the guests search checkbox field based on the provided field object.
	 *
	 * @since 2.0
	 *
	 * @param string $type The type of the field.
	 * @param object $field The field object.
	 * @param string $field_id The ID of the field.
	 * @param bool $aui_bs5 Whether to use AUI BS5 styles.
	 * @return string The HTML for the guests search checkbox field.
	 */
	private static function generate_checkbox_html( $type, $field, $field_id, $aui_bs5 ) {
		$field_label = $field->frontend_title ? stripslashes( $field->frontend_title ) : stripslashes( $field->admin_title );

		return sprintf(
			'<div class="dropdown-divider mx-0"></div>%s',
			aui()->input(
				array(
					'type'             => 'checkbox',
					'id'               => 'geodir_search_property_' . $type . $field_id,
					'title'            => esc_html__( $field_label, 'geodirectory' ),
					'label'            => esc_html__( $field_label, 'geodirectory' ),
					'label_type'       => 'horizontal',
					'label_col'        => '8',
					'label_force_left' => true,
					'value'            => '1',
					'switch'           => 'md',
					'checked'          => ! empty( $_REQUEST[ 'sproperty_' . $type ] ),
					'required'         => false,
					'help_text'        => ' ',
					'extra_attributes' => array(
						'name'       => 'sproperty_' . $type,
						'data-label' => esc_attr_x( $type, 'guests search', 'geodir-booking' ),
					),
					'class'            => 'geodir-guests-search-input c-pointer',
					'wrap_class'       => 'geodir-guests-search-group mb-0',
					'label_class'      => 'c-pointer',
				)
			)
		);
	}

	/**
	 * Add inline script for guests search.
	 *
	 * This function adds inline script for guests search based on the field's htmlvar_name.
	 *
	 * @since 2.0
	 */
	public static function search_inline_script() {
		$has_guests_search = self::has_guests_search();

		$search_labels = array(
			'guests'  => esc_html_x( 'guests', 'guests search', 'geodir-booking' ),
			'guest'   => esc_html_x( 'guest', 'guests search', 'geodir-booking' ),
			'infants' => esc_html_x( 'infants', 'guests search', 'geodir-booking' ),
			'infant'  => esc_html_x( 'infant', 'guests search', 'geodir-booking' ),
			'pets'    => esc_html_x( 'pets', 'guests search', 'geodir-booking' ),
			'pet'     => esc_html_x( 'pet', 'guests search', 'geodir-booking' ),
		);

		if ( ! $has_guests_search ) {
			return;
		}

		if ( 0 ) :
			?>
			<script>
		<?php endif; ?>
		
		jQuery(function($) {
			const $document = $(document);
			const GD_Booking_Search_Labels = <?php echo wp_json_encode( $search_labels ); ?>;

			$document.on('click', '.geodir-dropdown-guests', function(event) {
				event.stopPropagation();
			});

			$document.on('click', '.geodir-counter-btn', function(event) {
				event.preventDefault();
				const $this = $(this);
				const $counterWrapper = $this.closest('.geodir-counter-wrap');
				const $counterVal = $counterWrapper.find('.geodir-counter-val');
				const $counterInput = $counterWrapper.find('.geodir-counter-input');
				let currentCount = parseInt($counterVal.text()) || 0;

				currentCount += $this.data('counter') === 'minus' ? -1 : 1;
				currentCount = Math.max(0, currentCount);

				$counterInput.val(currentCount);
				$counterVal.text(currentCount);
				updateGuestsCounter($this);
			});

			$document.on('click', '.geodir-search-input-label-clear', function() {
				const $searchField = $(this).closest('.gd-search-field-search');
				$searchField.find('.geodir-counter-wrap').each(function() {
					const $this = $(this);
					$this.find('.geodir-counter-val').text('0');
					$this.find('.geodir-counter-input').val('0');
				});
				$searchField.find('[type="checkbox"]:checked').trigger('click');
				updateGuestsCounter(this);
			});

			$document.on('click', '.gd-search-fieldproperty_guests [type="checkbox"]', function() {
				updateGuestsCounter(this);
			});

			$document.on('click', '.gd-adv-search-property_guests', function() {
				$('.gd-search-field-search [name="adults"], .gd-search-field-search [name="children"]').val(0);
				$('.gd-search-fieldproperty_guests').each(function() {
					updateGuestsSelection($(this), true);
				});
			});

			$document.on('click', '.gd-adv-search-property_infants, .gd-adv-search-property_pets', function() {
				setTimeout(() => {
					$('.gd-search-fieldproperty_guests').each(function() {
						updateGuestsSelection($(this));
					});
				}, 100);
			});

			function updateGuestsCounter(element) {
				const $guestsWrapper = $(element).closest('.gd-search-fieldproperty_guests');
				const totalGuests = $guestsWrapper.find('.geodir-counter-wrap').toArray().reduce((sum, el) => {
					const $input = $(el).find('.geodir-counter-input');
					return ['adults', 'children'].includes($input.attr('name')) ? sum + (parseInt($input.val()) || 0) : sum;
				}, 0);

				updateGuestsSelection($guestsWrapper);
				$guestsWrapper.find('[name="sproperty_guests"]').val(totalGuests || '').trigger('change');
			}

			function updateGuestsSelection($wrapper, resetZero = false) {
				if (!$wrapper.hasClass('gd-search-fieldproperty_guests')) return;

				let guestCount = 0;
				let guestLabel = '';

				$wrapper.find('.geodir-counter-wrap').each(function() {
					const $element = $(this);
					const $counterInput = $element.find('.geodir-counter-input');

					if (resetZero) {
						$element.find('.geodir-counter-val').text('0');
					} else if (['adults', 'children'].includes($counterInput.attr('name'))) {
						guestCount += parseInt($counterInput.val()) || 0;
					}
				});

				if (guestCount > 0) {
					guestLabel = guestCount + ' ' + (guestCount > 1 ? GD_Booking_Search_Labels.guests : GD_Booking_Search_Labels.guest);
				}

				const infants = parseInt($wrapper.find('.geodir-counter-input[name="infants"]').val()) || 0;
				const pets = parseInt($wrapper.find('.geodir-counter-input[name="pets"]').val()) || 0;

				if (infants > 0) {
					guestLabel += (guestLabel ? ', ' : '') + infants + ' ' + (infants > 1 ? GD_Booking_Search_Labels.infants : GD_Booking_Search_Labels.infant);
					$wrapper.find('input[name="sproperty_infants"]').remove();
					$wrapper.append('<input type="hidden" name="sproperty_infants" value="1">');
				} else {
					$wrapper.find('input[name="sproperty_infants"]').remove();
				}

				if (pets > 0) {
					guestLabel += (guestLabel ? ', ' : '') + pets + ' ' + (pets > 1 ? GD_Booking_Search_Labels.pets : GD_Booking_Search_Labels.pet);
					$wrapper.find('input[name="sproperty_pets"]').remove();
					$wrapper.append('<input type="hidden" name="sproperty_pets" value="1">');
				} else {
					$wrapper.find('input[name="sproperty_pets"]').remove();
				}

				$wrapper.find('[type="checkbox"]:checked').each(function() {
					const label = $(this).data('label');
					if (label) {
						guestLabel += (guestLabel ? ', ' : '') + label;
					}
				});

				$wrapper.find('.geodir-guests-search').val(guestLabel);
			}
		});
		
		<?php if ( 0 ) : ?>
			</script>
			<?php
		endif;
	}

	/**
	 * Skip guests inputs in the search form.
	 *
	 * This function skips the guests inputs in the search form based on the field's htmlvar_name.
	 *
	 * @since 2.0
	 *
	 * @param string $output The HTML output for the search form.
	 * @param object $field The field object.
	 * @param string $post_type The post type.
	 * @return string The HTML output for the search form.
	 */
	public static function skip_guests_inputs( $output, $field, $post_type ) {
		if ( ( $field->htmlvar_name == 'property_infants' || $field->htmlvar_name == 'property_pets' ) && ( $fields = self::guests_fields( $post_type ) ) ) {
			if ( ! empty( $fields['property_guests']->main_search ) ) {
				$output = '<!---->';
			}
		}

		return $output;
	}

	/**
	 * Show filters in the search form.
	 *
	 * This function shows the filters in the search form based on the field's htmlvar_name.
	 *
	 * @since 2.0
	 *
	 * @param array $params The HTML output for the search form.
	 * @param string $post_type The post type.
	 * @param array $fields The fields array.
	 * @return string The HTML output for the search form.
	 */
	public static function show_filters( $params, $post_type, $fields ) {
		global $aui_bs5;

		$guests_fields = self::guests_fields( $post_type );

		if ( ! empty( $guests_fields['property_guests'] ) && ! empty( $_REQUEST['sproperty_guests'] ) && ( $guests = absint( $_REQUEST['sproperty_guests'] ) ) > 0 ) {
			$guests_label = wp_sprintf( _n( '%d Guest', '%s Guests', $guests, 'geodir-booking' ), $guests );
			$guests_label = '<label class="gd-adv-search-label badge c-pointer gd-adv-search-default gd-adv-search-property_guests ' . ( $aui_bs5 ? 'text-bg-info me-2' : 'badge-info mr-2' ) . '" data-name="sproperty_guests"><i class="fas fa-times" aria-hidden="true"></i> ' . $guests_label . '</label>';

			if ( empty( $params ) ) {
				$params = array( $guests_label );
			} else {
				$_params = array();
				$set     = false;

				foreach ( $params as $key => $label ) {
					if ( ! $set && ( strpos( $label, '"sproperty_guests"' ) !== false || strpos( $label, '"sproperty_infants"' ) !== false || strpos( $label, '"sproperty_pets"' ) !== false ) ) {
						$set       = true;
						$_params[] = $guests_label;

						if ( strpos( $label, '"sproperty_guests"' ) !== false ) {
							continue;
						}
					}

					$_params[] = $label;
				}

				$params = $_params;
			}
		}

		return $params;
	}
}
