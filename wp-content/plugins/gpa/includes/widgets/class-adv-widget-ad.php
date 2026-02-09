<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class Adv_Widget_Ad
 *
 * @since 1.0.1-dev
 */
class Adv_Widget_Ad extends WP_Super_Duper {

    public $arguments;

    /**
     * Main class constructor.
     *
     * @since 1.0.1-dev
     */
    public function __construct() {
	
        $options = array(
            'textdomain'            => 'advertising',
            'block-icon'            => 'index-card',
            'block-category'        => 'widgets',
            'block-keywords'        => "['advertising','ad','ads']",
            'base_id'               => 'ads_ad',
            'class_name'            => __CLASS__,
            'name'                  => __('Ads > Ad','advertising'),
            'widget_ops'            => array(
                'classname'         => 'adv-widget-ad bsui',
                'description'       => esc_html__('Displays a single ad.','advertising'),
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

		// Get a list of all ads.
        $all_ads = adv_get_ads( array( 'fields'        => 'all', ) );


		// And filter them as id => title.
		$all_ads = wp_list_pluck( $all_ads, 'post_title', 'ID' );

		// Add a default item to prevent some browsers from making the first item unselectable.
		$all_ads['0'] = __( 'Select an ad', 'advertising' );

		// Sort by key.
		ksort( $all_ads, SORT_NUMERIC );
//	    print_r($all_ads);exit;
		$arguments = array(
			'id'  				=> array(
				'title' 		=> __('Ad:', 'advertising'),
				'desc' 			=> __('Select an ad to display.', 'advertising'),
				'type' 			=> 'select',
				'placeholder' 	=> __( 'Select ad', 'advertising' ),
				'options'       => $all_ads, 
				'default'  		=> '',
				'desc_tip' 		=> true,
				'advanced' 		=> false
            ),
		);


	   

        return $arguments;
    }


    /**
     * Displays a single ad
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

		//Abort early if no ad has been selected
		if ( empty( $args['id'] ) ) {
			return;
		}

        return adv_get_ad_html( $args['id'] );

    }

}
