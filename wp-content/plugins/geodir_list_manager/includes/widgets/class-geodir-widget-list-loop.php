<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * GeoDirectory Search widget.
 *
 * @since 1.0.0
 */
class GeoDir_Widget_List_Loop extends WP_Super_Duper {

	/**
	 * Register the advanced search widget with WordPress.
	 *
	 */
	public function __construct() {

		$options = array(
			'textdomain'    => GEODIRECTORY_TEXTDOMAIN,
			'block-icon'    => 'list-view',
			'block-category'=> 'geodirectory',
			'block-keywords'=> "['list loop','lists','geodir']",
			'class_name'    => __CLASS__,
			'base_id'       => 'gd_list_loop', // this us used as the widget id and the shortcode id.
			'name'          => __( 'GD > List Loop', 'gd-lists' ), // the name of the widget.
			'widget_ops'    => array(
				'classname'    => 'geodir-list-loop-container' . ( geodir_design_style() ? ' bsui' : '' ),
				'description'  => esc_html__( 'Shows the current posts saved to a list.', 'gd-lists' ), // widget description
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

		$arguments = array();

		$arguments['layout'] = array(
			'title' => __( 'Layout:', 'gd-lists' ),
			'desc' => __( 'How the listings should laid out by default.', 'gd-lists' ),
			'type' => 'select',
			'options' => geodir_get_layout_options(),
			'default' => '2',
			'desc_tip' => true,
			'advanced' => true
		);

		if ( $design_style ) {
			$arguments['template_type'] = array(
				'type' => 'select',
				'title' => __( 'Archive Item Template Type:', 'geodirectory' ),
				'desc' => __( 'Select archive item template type to assign template to listings loop.', 'geodirectory' ),
				'options' => geodir_template_type_options(),
				'default' => '',
				'desc_tip' => true,
				'advanced' => false,
				'group' => __( 'Card Design', 'geodirectory' ),
			);

			$arguments['tmpl_page'] = array(
				'type' => 'select',
				'title' => __( 'Archive Item Template Page:', 'geodirectory' ),
				'desc' => __( 'Select archive item template page.', 'geodirectory' ),
				'options' => geodir_template_page_options(),
				'default' => '',
				'desc_tip' => true,
				'advanced' => false,
				'element_require' => '[%template_type%]=="page"',
				'group' => __( 'Card Design', 'geodirectory' ),
			);

			if ( geodir_is_block_theme() ) {
				$arguments['tmpl_part'] = array(
					'type' => 'select',
					'title' => __( 'Archive Item Template Part:', 'geodirectory' ),
					'desc' => __( 'Select archive item template part.', 'geodirectory' ),
					'options' => geodir_template_part_options(),
					'default' => '',
					'desc_tip' => true,
					'advanced' => false,
					'element_require' => '[%template_type%]=="template_part"',
					'group' => __( 'Card Design', 'geodirectory' ),
				);
			}
		}

		/*
		* Elementor Pro features below here
		*/
		if ( defined( 'ELEMENTOR_PRO_VERSION' ) ) {
			$arguments['skin_id'] = array(
				'title'           => __( 'Archive Item Elementor Skin:', 'geodirectory' ),
				'desc'            => '',
				'type'            => 'select',
				'options'         => GeoDir_Elementor::get_elementor_pro_skins(),
				'default'         => '',
				'desc_tip'        => false,
				'advanced'        => false,
				'element_require' => '([%template_type%]=="" || [%template_type%]=="elementor_skin")',
				'group'           => __( 'Card Design', 'geodirectory' ),
			);

			$arguments['skin_column_gap'] = array(
				'title'           => __( 'Skin column gap', 'geodirectory' ),
				'desc'            => __( 'The px value for the column gap.', 'geodirectory' ),
				'type'            => 'number',
				'default'         => '30',
				'desc_tip'        => true,
				'advanced'        => false,
				'element_require' => '([%template_type%]=="" || [%template_type%]=="elementor_skin")',
				'group'           => __( 'Card Design', 'geodirectory' ),
			);

			$arguments['skin_row_gap'] = array(
				'title'           => __( 'Skin row gap', 'geodirectory' ),
				'desc'            => __( 'The px value for the row gap.', 'geodirectory' ),
				'type'            => 'number',
				'default'         => '35',
				'desc_tip'        => true,
				'advanced'        => false,
				'element_require' => '([%template_type%]=="" || [%template_type%]=="elementor_skin")',
				'group'           => __( 'Card Design', 'geodirectory' ),
			);
		}

		if ( $design_style ) {
			$arguments['row_gap'] = array(
				'title' => __( 'Card row gap', 'gd-lists' ),
				'desc' => __('This adjusts the spacing between the cards horizontally.', 'gd-lists' ),
				'type' => 'select',
				'options' => array(
					'' => __( 'Default', 'geodirectory' ),
					'1' => '1',
					'2' => '2',
					'3' => '3',
					'4' => '4',
					'5' => '5',
				),
				'default' => '',
				'desc_tip' => false,
				'advanced' => false,
				'group' => __( 'Card Design', 'geodirectory' )
			);

			$arguments['column_gap'] = array(
				'title' => __( 'Card column gap', 'gd-lists' ),
				'desc' => __('This adjusts the spacing between the cards vertically.', 'gd-lists' ),
				'type' => 'select',
				'options' => array(
					'' => __( 'Default', 'geodirectory' ),
					'1' => '1',
					'2' => '2',
					'3' => '3',
					'4' => '4',
					'5' => '5',
				),
				'default' => '',
				'desc_tip' => false,
				'advanced' => false,
				'group' => __( 'Card Design', 'geodirectory' )
			);

			$arguments['card_border'] = array(
				'title' => __( 'Card border', 'gd-lists' ),
				'desc' => __( 'Set the border style for the card.', 'gd-lists' ),
				'type' => 'select',
				'options' => array(
					'' => __( 'Default', 'geodirectory' ),
					'none' => __( 'None', 'geodirectory' ),
				) + geodir_aui_colors(),
				'default' => '',
				'desc_tip' => false,
				'advanced' => false,
				'group' => __( 'Card Design', 'geodirectory' )
			);

			$arguments['card_shadow'] = array(
				'title' => __( 'Card shadow', 'gd-lists' ),
				'desc' => __( 'Set the card shadow style.', 'gd-lists' ),
				'type' => 'select',
				'options' => array(
					'' => __( 'None', 'geodirectory' ),
					'small' => __( 'Small', 'geodirectory' ),
					'medium' => __( 'Medium', 'geodirectory' ),
					'large' => __( 'Large', 'geodirectory' ),
				),
				'default' => '',
				'desc_tip' => false,
				'advanced' => false,
				'group' => __( 'Card Design', 'geodirectory' )
			);

			// margins
			$arguments['mt'] = geodir_get_sd_margin_input( 'mt' );
			$arguments['mr'] = geodir_get_sd_margin_input( 'mr' );
			$arguments['mb'] = geodir_get_sd_margin_input( 'mb' );
			$arguments['ml'] = geodir_get_sd_margin_input( 'ml' );

			// padding
			$arguments['pt'] = geodir_get_sd_padding_input( 'pt' );
			$arguments['pr'] = geodir_get_sd_padding_input( 'pr' );
			$arguments['pb'] = geodir_get_sd_padding_input( 'pb' );
			$arguments['pl'] = geodir_get_sd_padding_input( 'pl' );
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
		global $wp_query, $post, $geodir_is_widget_listing, $gd_layout_class, $geodir_item_tmpl;

		$design_style = geodir_design_style();

		if ( is_single() && ! empty( $post ) && ! empty( $post->post_type ) && $post->post_type == 'gd_list' ) {
			$post_id = (int) $post->ID;
		} else if ( $this->is_preview() ) {
			$post_id = (int) $this->get_preview_list_id();
		} else {
			$post_id = 0;
		}

		ob_start();

		if ( ! empty( $post_id ) ) {
			// Check if we have listings
			$data = new GeoDir_Lists_Data();
			$posts = $data->get_posts( $post_id );

			$args = wp_parse_args( $args, array(
				'layout' => '',
				// AUI settings
				'column_gap' => '',
				'row_gap' => '',
				'card_border' => '',
				'card_shadow' => '',
				// Template Settings
				'template_type' => '',
				'tmpl_page' => '',
				'tmpl_part' => '',
				// Elementor settings
				'skin_id' => '',
				'skin_column_gap' => '',
				'skin_row_gap' => '',
			) );

			$layout = ! empty( $args['layout'] ) ? absint( $args['layout'] ) : '2';

			/**
			 * Filter the widget template_type param.
			 *
			 * @param string $template_type Filter template_type.
			 *
			 * @since 2.3.9
			 *
			 */
			$template_type = apply_filters( 'geodir_widget_gd_list_loop_template_type', ( ! empty( $args['template_type'] ) ? $args['template_type'] : '' ), $args, $this->id_base );

			$template_page = 0;
			/**
			 * Filter the widget tmpl_page param.
			 *
			 * @param int $template_page Filter tmpl_page.
			 *
			 * @since 2.3.9
			 *
			 */
			if ( $template_type == 'page' ) {
				$template_page = apply_filters( 'geodir_widget_gd_list_loop_tmpl_page', ( ! empty( $args['tmpl_page'] ) ? (int) $args['tmpl_page'] : 0 ), $args, $this->id_base );
			}

			$template_part = '';
			/**
			 * Filter the widget tmpl_part param.
			 *
			 * @param string $template_part Filter tmpl_part.
			 *
			 * @since 2.3.9
			 *
			 */
			if ( $template_type == 'template_part' && geodir_is_block_theme() ) {
				$template_part = apply_filters( 'geodir_widget_gd_list_loop_tmpl_part', ( ! empty( $args['tmpl_part'] ) ? $args['tmpl_part'] : '' ), $args, $this->id_base );
			}

			$skin_id = 0;
			if ( empty( $template_type ) || $template_type == 'elementor_skin' ) {
				/**
				 * Filter the widget skin_id param.
				 *
				 * @param int $skin_id Filter skin_id.
				 *
				 * @since 2.3.9
				 *
				 */
				$skin_id = apply_filters( 'geodir_widget_gd_list_loop_skin_id', ( ! empty( $args['skin_id'] ) ? (int) $args['skin_id'] : 0 ), $args, $this->id_base );
			}

			$geodir_item_tmpl = array();

			if ( ! empty( $template_page ) && get_post_type( $template_page ) == 'page' && get_post_status( $template_page ) == 'publish' ) {
				$geodir_item_tmpl = array(
					'id'   => $template_page,
					'type' => 'page',
				);
			} else if ( ! empty( $template_part ) && ( $_template_part = geodir_get_template_part_by_slug( $template_part ) ) ) {
				$geodir_item_tmpl = array(
					'id'      => $_template_part->slug,
					'content' => $_template_part->content,
					'type'    => 'template_part',
				);
			}

			// Wrap class
			$wrap_class = geodir_build_aui_class( $args );

			// Elementor Pro
			$skin_active = false;

			if ( defined( 'ELEMENTOR_PRO_VERSION' ) && $skin_id ) {
				if ( $this->is_preview() && ! $this->is_elementor_preview() ) {
					$skin_id = 0;
				}

				if ( get_post_status( $skin_id ) == 'publish' ) {
					$skin_active = true;

					$geodir_item_tmpl = array(
						'id'   => $skin_id,
						'type' => 'elementor_skin',
					);
				}

				if ( $skin_active ) {
					$columns = $args['layout'] !== "" ? absint( $args['layout'] ) : '2';

					if ( $columns < 1 ) {
						$columns = 6; // We have no 6 row option to lets use list view
					}

					$wrap_class .= ' elementor-element elementor-element-9ff57fdx elementor-posts--thumbnail-top elementor-grid-' . $columns . ' elementor-grid-tablet-2 elementor-grid-mobile-1 elementor-widget elementor-widget-posts ';
				}
			}

			// Set default from template Lists CPT settings.
			if ( empty( $geodir_item_tmpl ) ) {
				$template_page = 0;
				$list_tmpl_page = geodir_archive_item_page_id( 'gd_list' );

				if ( $list_tmpl_page && $list_tmpl_page != geodir_archive_item_page_id() ) {
					$template_page = $list_tmpl_page;
				}

				if ( ! empty( $template_page ) && get_post_type( $template_page ) == 'page' && get_post_status( $template_page ) == 'publish' ) {
					$geodir_item_tmpl = array(
						'id'   => $template_page,
						'type' => 'page',
					);
				}
			}

			$gd_layout_class = geodir_convert_listing_view_class( $layout );

			// Card border class
			$card_border_class = '';
			if ( ! empty( $args['card_border'] ) ) {
				if ( $args['card_border'] == 'none' ) {
					$card_border_class = 'border-0';
				} else {
					$card_border_class = 'border-' . sanitize_html_class( $args['card_border'] );
				}
			}

			// Card shadow class
			$card_shadow_class = '';
			if ( ! empty( $args['card_shadow'] ) ) {
				if ( $args['card_shadow'] == 'small' ) {
					$card_shadow_class = 'shadow-sm';
				} elseif ( $args['card_shadow'] == 'medium' ) {
					$card_shadow_class = 'shadow';
				} elseif ( $args['card_shadow'] == 'large' ) {
					$card_shadow_class = 'shadow-lg';
				}
			}

			if ( $wrap_class ) { 
				echo '<div class="' . $wrap_class . ' geodir_locations geodir_location_listing">';
			}

			// Check if we have listings or if we are faking it
			if ( $wp_query->post_count == 1 && empty( $wp_query->posts ) ) {
				geodir_no_listings_found();
			} else {
				// Check we are not inside a template builder container
				if ( isset( $wp_query->posts[0] ) && $wp_query->posts[0]->post_type == 'page' ) {
					// Reset the query count so the correct number of listings are output.
					rewind_posts();

					// Reset the proper loop content
					global $wp_query, $gd_temp_wp_query;

					$wp_query->posts = $gd_temp_wp_query;
				}

				if ( isset( $post ) ) {
					$reset_post = $post;
				}

				if ( isset( $gd_post ) ) {
					$reset_gd_post = $gd_post;
				}

				$geodir_is_widget_listing = true;


				if ( $skin_active ) {
					$column_gap = ! empty( $args['skin_column_gap'] ) ? absint( $args['skin_column_gap'] ) : '';
					$row_gap    = ! empty( $args['skin_row_gap'] ) ? absint( $args['skin_row_gap'] ) : '';

					geodir_get_template(
						'elementor/content-widget-listing.php',
						array(
							'widget_listings' => $posts,
							'skin_id'         => $skin_id,
							'columns'         => $columns,
							'column_gap'      => $column_gap,
							'row_gap'         => $row_gap,
						)
					);
				} else {

					$template = $design_style ? $design_style . '/content-widget-listing.php' : 'content-widget-listing.php';

					echo geodir_get_template_html(
						$template,
						array(
							'widget_listings'   => $posts,
							'column_gap_class'  => $args['column_gap'] ? 'mb-' . absint( $args['column_gap'] ) : 'mb-4',
							'row_gap_class'     => $args['row_gap'] ? 'px-' . absint( $args['row_gap'] ) : '',
							'card_border_class' => $card_border_class,
							'card_shadow_class' => $card_shadow_class,
						)
					);
				}

				$geodir_is_widget_listing = false;

				if ( isset( $reset_post ) ) {
					if ( ! empty( $reset_post ) ) {
						setup_postdata( $reset_post );
					}
					$post = $reset_post;
				}

				if ( isset( $reset_gd_post ) ) {
					$gd_post = $reset_gd_post;
				}
			}

			if ( $wrap_class ) { 
				echo '</div>';
			}
		} else {
			_e( "This list is empty at the moment, check back later.", "gd-lists" );
		}

		return ob_get_clean();
	}

	/**
	 * Filter to close the comments for archive pages after the GD loop.
	 * 
	 * @param $open
	 * @param $post_id
	 *
	 * @return bool
	 */
	public static function comments_open( $open, $post_id ) {
		global $post;

		if ( isset( $post->ID ) && $post->ID == $post_id ) {
			$open = false;
		}

		return $open;
	}

	public function get_preview_list_id() {
		global $wpdb;

		return $wpdb->get_var( "SELECT `pp`.`p2p_to` FROM `{$wpdb->prefix}p2p` AS `pp` LEFT JOIN `{$wpdb->posts}` AS p ON p.ID = pp.p2p_to WHERE p.post_status = 'publish' ORDER BY pp.p2p_id ASC LIMIT 1" );
	}

}