<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class Adv_Widget_Zone
 *
 * @since 1.0.1-dev
 */
class Adv_Widget_Zone extends WP_Super_Duper {

    public $arguments;

    /**
     * Main class constructor.
     *
     * @since 1.0.1-dev
     */
    public function __construct() {

        $options = array(
            'textdomain'     => 'advertising',
            'block-icon'     => 'index-card',
            'block-category' => 'widgets',
            'block-keywords' => "['advertising','zones','ads']",
            'base_id'        => 'ads',
            'class_name'     => __CLASS__,
            'name'           => __( 'Ads > Zone', 'advertising' ),
            'widget_ops'     => array(
                'classname'   => 'adv-widget-zone bsui',
                'description' => esc_html__( 'Displays ads in a given zone.', 'advertising' ),
            ),
        );

        parent::__construct( $options );
	}

	/**
     * Set widget arguments.
     *
     * @since 1.0.1-dev
     * @return array
     */
    public function set_arguments() {

		//Get a list of all zones
        $all_zones = adv_get_zones(
            array(
				'meta_query' => array(),
				'fields'     => 'all',
            )
        );

		//And filter them as id => title
		$all_zones = wp_list_pluck( $all_zones, 'post_title', 'ID' );

		//Add a default item to prevent some browsers from making the first item unselectable
		$all_zones['0'] = __( 'Select a zone', 'advertising' );

		//Sort by key
		ksort( $all_zones, SORT_NUMERIC );

		$arguments = array(
			'zone' => array(
				'title'       => __( 'Zone:', 'advertising' ),
				'desc'        => __( 'Select the zone whose ads should be displayed.', 'advertising' ),
				'type'        => 'select',
				'placeholder' => __( 'Select zone', 'advertising' ),
				'options'     => $all_zones,
				'default'     => '',
				'desc_tip'    => true,
				'advanced'    => false,
			),
		);

	    // background
	    $arguments['bg']  = adv_get_sd_background_input( 'mt' );

	    // margins
	    $arguments['mt']  = adv_get_sd_margin_input( 'mt' );
	    $arguments['mr']  = adv_get_sd_margin_input( 'mr' );
	    $arguments['mb']  = adv_get_sd_margin_input( 'mb', array( 'default' => 3 ) );
	    $arguments['ml']  = adv_get_sd_margin_input( 'ml' );

	    // padding
	    $arguments['pt']  = adv_get_sd_padding_input( 'pt' );
	    $arguments['pr']  = adv_get_sd_padding_input( 'pr' );
	    $arguments['pb']  = adv_get_sd_padding_input( 'pb' );
	    $arguments['pl']  = adv_get_sd_padding_input( 'pl' );

	    // border
	    $arguments['border']  = adv_get_sd_border_input( 'border' );
	    $arguments['rounded']  = adv_get_sd_border_input( 'rounded' );
	    $arguments['rounded_size']  = adv_get_sd_border_input( 'rounded_size' );

	    // shadow
	    $arguments['shadow']  = adv_get_sd_shadow_input( 'shadow' );

        return $arguments;
    }


    /**
     * Displays a zone
     *
     * @since 1.0.1-dev
     *
     * @param array $args Get Arguments.
     * @param array $widget_args Get widget arguments.
     * @param string $content Get widget content.
     * @return string
     *
     */
    public function output( $args = array(), $widget_args = array(), $content = '' ) {

		//Abort early if no zone has been selected
		if ( empty( $args['zone'] ) ) {
			return;
		}

	    global $adv_zone_wrapper_class;

	    // wrapper class
	    $adv_zone_wrapper_class['wrapper'] = adv_build_aui_class( $args );

        return ads_get_zone_html( $args['zone'] );
    }
}
