<?php
/**
 * GeoDirectory Marketplace shop widget
 *
 * @package GeoDir_Marketplace
 * @author AyeCode Ltd
 * @since 2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * GeoDir_Marketplace_Widget_Shop class.
 */
class GeoDir_Marketplace_Widget_Shop extends WP_Super_Duper {
	public $arguments;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$options = array(
			'base_id'        => 'gd_marketplace',
			'name'           => __( 'GD > Marketplace', 'geomarketplace' ),
			'class_name'     => __CLASS__,
			'textdomain'     => GEODIRECTORY_TEXTDOMAIN,
			'block-category' => 'geodirectory',
			'block-icon'     => 'cart',
			'block-keywords' => "['geodir','woocommerce','marketplace']",
			'widget_ops'     => array(
				'classname'    => 'geodir-wc-marketplace-container ' . geodir_bsui_class(),
				'description'  => esc_html__( 'Displays WooCommerce vendor products on single listing page.', 'geomarketplace' ),
				'geodirectory' => true,
			)
		);

		parent::__construct( $options );
	}

	/**
	 * Set widget arguments.
	 */
	public function set_arguments() {
		$columns = GeoDir_Marketplace_WooCommerce::wc_default_catalog_columns();

		$arguments = array(
			'title' => array(
				'type' => 'text',
				'title' => __( 'Title:', 'geomarketplace' ),
				'desc' => __( 'The widget title.', 'geomarketplace' ),
				'default' => '',
				'desc_tip' => true,
				'advanced' => false,
			),
			'category' => array(
				'type' => 'select',
				'title' => __( 'Filter by Product Categories:', 'geomarketplace' ),
				'desc' => __( 'Filter products by product categories.', 'geomarketplace' ),
				'multiple' => true,
				'options' => $this->get_categories(),
				'default' => '',
				'desc_tip' => true,
				'advanced' => false
			),
			'cat_operator' => array(
				'type' => 'select',
				'title' => __( 'Categories Operator:', 'geomarketplace' ),
				'desc' => __( 'Operator to compare categories. Possible values are IN, NOT IN, AND.', 'geomarketplace' ),
				'options' => array( 'IN' => 'IN', 'NOT IN' => 'NOT IN', 'AND' => 'AND' ),
				'default' => '',
				'desc_tip' => true,
				'advanced' => false,
				'element_require' => '[%category%] != ""',
			),
			'orderby' => array(
				'type' => 'select',
				'title' => __( 'Shop Order:', 'geomarketplace' ),
				'desc' => __( 'Shop order to show the results.', 'geomarketplace' ),
				'options' => GeoDir_Marketplace_WooCommerce::wc_catalog_orderby_options(),
				'default' => GeoDir_Marketplace_WooCommerce::wc_default_catalog_orderby(),
				'desc_tip' => true,
				'advanced' => false
			),
			'paginate' => array(
				'type' => 'checkbox',
				'title' => __( 'Show the results with pagination', 'geomarketplace' ),
				'desc_tip' => true,
				'value' => '1',
				'default' => '1',
				'advanced' => false
			),
			'per_page' => array(
				'title' => __( 'Products Per Page:', 'geomarketplace'),
				'desc' => __( 'Number of products to show per page. Leave blank or enter 0 to show all products.', 'geomarketplace' ),
				'type' => 'number',
				'default' => (int) GeoDir_Marketplace_WooCommerce::wc_default_shop_per_page(),
				'desc_tip' => true,
				'advanced' => false
			),
			'columns' => array(
				'type' => 'select',
				'title' => __( 'Columns:', 'geomarketplace' ),
				'desc' => __( 'Columns to show the results.', 'geomarketplace' ),
				'options' => array(
					'' => wp_sprintf( __( 'Default (%d columns)', 'geomarketplace' ), $columns ),
					'1' => __( 'One column', 'geomarketplace' ),
					'2' => __( 'Two columns', 'geomarketplace' ),
					'3' => __( 'Three columns', 'geomarketplace' ),
					'4' => __( 'Four columns', 'geomarketplace' ),
					'5' => __( 'Five columns', 'geomarketplace' ),
					'6' => __( 'Six columns', 'geomarketplace' ),
				),
				'default' => $columns,
				'desc_tip' => true,
				'advanced' => false
			),
			'show_count' => array(
				'type' => 'checkbox',
				'title' => __( 'Show results count (with pagination only).', 'geomarketplace' ),
				'desc_tip' => true,
				'value' => '1',
				'default' => '',
				'advanced' => false,
			),
			'show_orderby' => array(
				'type' => 'checkbox',
				'title' => __( 'Show orderby selection (with pagination only).', 'geomarketplace' ),
				'desc_tip' => true,
				'value' => '1',
				'default' => '',
				'advanced' => false,
			),
			'view_all_link' => array(
				'type' => 'checkbox',
				'title' => __( 'Show link to view all products from the vendor.', 'geomarketplace' ),
				'desc_tip' => true,
				'value' => '1',
				'default' => '',
				'advanced' => false,
			)
		);

		return $arguments;
	}

	/**
	 * Widget output.
	 *
	 * @param array $instance Settings for the widget instance.
	 * @param array $args Display arguments.
	 * @param string $content
	 *
	 * @return mixed|string|void
	 */
	public function output( $instance = array(), $args = array(), $content = '' ) {
		global $gd_post;

		if ( ! ( ! empty( $gd_post->post_type ) && geodir_marketplace_valid_post_type( $gd_post->post_type ) ) ) {
			return;
		}

		if ( $this->is_preview() ) {
			return '';
		}

		$defaults = array(
			'title' => '',
			'category' => '',
			'cat_operator' => '',
			'orderby' => '',
			'per_page' => '',
			'paginate' => '',
			'columns' => '',
			'show_count' => '',
			'show_orderby' => '',
			'view_all_link' => ''
		);

		$instance = wp_parse_args( $instance, $defaults );

		if ( ! apply_filters( 'geodir_marketplace_display_shop', true, $instance ) ) {
			return;
		}

		$orderby = empty( $instance['orderby'] ) ? GeoDir_Marketplace_WooCommerce::wc_default_catalog_orderby() : $instance['orderby'];
		$per_page = absint( $instance['per_page'] );
		if ( $per_page < 1 ) {
			$per_page = -1;
		}
		$paginate = $per_page > 0 && ! empty( $instance['paginate'] ) ? true : false;
		$columns = absint( $instance['columns'] );
		if ( ! ( $columns >= 1 && $columns <= 6 ) ) {
			$columns = GeoDir_Marketplace_WooCommerce::wc_default_catalog_columns();

			if ( $columns < 1 ) {
				$columns = 4;
			}
		}

		$_category = array();

		if ( ! empty( $instance['category'] ) ) {
			if ( ! is_array( $instance['category'] ) ) {
				$_category = explode( ",", $instance['category'] );
			} else {
				$_category = $instance['category'];
			}

			$_category = array_map( 'absint', array_filter( $_category ) );
		}

		$category = ! empty( $_category ) ? implode( ",", $_category ) : '';
		$cat_operator = ! empty( $instance['category'] ) && in_array( $instance['category'], array( 'IN', 'NOT IN', 'AND' ) ) ? $instance['category'] : 'IN';

		$rows = $per_page > 0 ? ceil( $per_page / $columns ) : 0;

		$atts = array(
			'limit' => $per_page,
			'columns' => $columns,
			'rows' => $rows,
			'paginate' => $paginate,
			'orderby' => $orderby,
			'category' => $category,
			'cat_operator' => $cat_operator,
			'cache' => false
		);

		if ( isset( $_GET['orderby'] ) ) {
			$backup_orderby = wc_clean( wp_unslash( $_GET['orderby'] ) );
		} else {
			$backup_orderby = null;
		}

		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$_GET['orderby'] = wc_clean( wp_unslash( $orderby ) );
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		if ( empty( $instance['show_count'] ) && ( $has_result_count = has_action( 'woocommerce_before_shop_loop', 'woocommerce_result_count', 20 ) ) ) {
			remove_action( 'woocommerce_before_shop_loop', 'woocommerce_result_count', 20 );
		}

		if ( empty( $instance['show_orderby'] ) && ( $has_catalog_ordering = has_action( 'woocommerce_before_shop_loop', 'woocommerce_catalog_ordering', 30 ) ) ) {
			remove_action( 'woocommerce_before_shop_loop', 'woocommerce_catalog_ordering', 30 );
		}

		// Set tab link hash to pagination links.
		if ( $paginate ) {
			add_filter( 'woocommerce_pagination_args', array( $this, 'woocommerce_pagination_args' ), 20, 1 );
		}

		// View all products link
		if ( ! empty( $instance['view_all_link'] ) && class_exists( 'GeoDir_Marketplace_WooCommerce' ) ) {
			add_action( 'woocommerce_shortcode_after_gd_marketplace_loop', array( 'GeoDir_Marketplace_WooCommerce', 'wc_loop_view_all_link' ), 90, 1 );
		}

		$atts = apply_filters( 'geodir_marketplace_shortcode_products_atts', $atts, $instance );

		$shortcode = new WC_Shortcode_Products( $atts, 'gd_marketplace' );

		$output = $shortcode->get_content();

		unset( $_GET['orderby'] );

		if ( $backup_orderby !== null ) {
			// phpcs:disable WordPress.Security.NonceVerification.Recommended
			$_GET['orderby'] = wc_clean( wp_unslash( $backup_orderby ) );
			// phpcs:enable WordPress.Security.NonceVerification.Recommended
		}

		if ( empty( $instance['show_count'] ) && $has_result_count ) {
			add_action( 'woocommerce_before_shop_loop', 'woocommerce_result_count', 20 );
		}

		if ( empty( $instance['show_orderby'] ) && $has_catalog_ordering ) {
			add_action( 'woocommerce_before_shop_loop', 'woocommerce_catalog_ordering', 30 );
		}

		if ( ! empty( $instance['view_all_link'] ) && class_exists( 'GeoDir_Marketplace_WooCommerce' ) ) {
			remove_action( 'woocommerce_shortcode_after_gd_marketplace_loop', array( 'GeoDir_Marketplace_WooCommerce', 'wc_loop_view_all_link' ), 90, 1 );
		}

		// Unset hook.
		if ( $paginate ) {
			remove_filter( 'woocommerce_pagination_args', array( $this, 'woocommerce_pagination_args' ), 20, 1 );
		}

		// Hide widget when empty results.
		if ( strpos( $output, 'gdmp-no-results' ) !== false ) {
			return;
		} else {
			$output = str_replace( 'class="wcmp_fpm_buttons', 'style="display:none!important" class="wcmp_fpm_buttons', $output );
			$output = str_replace( 'class="mvx_fpm_buttons', 'style="display:none!important" class="mvx_fpm_buttons', $output );
		}

		return $output;
	}

	public function get_categories() {
		$term_args = array( 'orderby' => 'name', 'order' => 'ASC', 'hide_empty' => true );
		$term_args = apply_filters( 'geodir_marketplace_widget_product_categories_args', $term_args );

		$categories = get_terms( 'product_cat', $term_args );
		$categories = apply_filters( 'geodir_marketplace_widget_filter_product_cats', $categories );

		$options = array( '0' => __( 'All', 'geomarketplace' ) );

		if ( ! empty( $categories ) ) {
			$categories = self::term_hierarchy( $categories );

			foreach ( $categories as $category ) {
				$options[ $category->term_id ] = $category->name;
			}
		}

		return $options;
	}

	public static function term_hierarchy( $terms, $parent = '0', $level = 0 ) {
		$terms_temp = array();
		$_level = $level;
		$level++;

		foreach ( $terms as $term ) {
			if ( $term->parent == $parent && $term->term_id != $parent ) {
				$terms_temp[] = $term;

				$child_terms = self::term_hierarchy( $terms, $term->term_id, $_level );

				if ( ! empty( $child_terms ) ) {
					foreach ( $child_terms as $child_term ) {
						$pad = str_repeat( '- ', $level );
						$pad = apply_filters( 'geodir_marketplace_widget_term_hierarchy_prefix', $pad, $term, $level );

						$child_term->name = $pad . $child_term->name;
						$terms_temp[] = $child_term;
					}
				}
			}
		}

		return $terms_temp;
	}

	/**
	 * Append shop tab link has to pagination links.
	 *
	 * @since 2.2.1
	 *
	 * @param array $args Pagination args.
	 * @return array Filtered pagination args.
	 */
	public function woocommerce_pagination_args( $args ) {
		if ( ! empty( $args['format'] ) ) {
			$args['format'] = $args['format'] . '#gdmp-shop';
		}

		if ( ! empty( $args['base'] ) ) {
			$args['base'] = $args['base'] . '#gdmp-shop';
		}

		return $args;
	}
}
