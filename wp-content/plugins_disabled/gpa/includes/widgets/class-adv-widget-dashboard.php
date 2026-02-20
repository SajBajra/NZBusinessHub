<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class Adv_Widget_Dashboard
 *
 * @since 1.0.1-dev
 */
class Adv_Widget_Dashboard extends WP_Super_Duper {

    public $arguments;

    /**
     * Main class constructor.
     *
     * @since 1.0.1-dev
     */
    public function __construct() {
	
        $options = array(
            'textdomain'            => 'advertising',
            'block-icon'            => 'dashboard',
            'block-category'        => 'widgets',
            'block-keywords'        => "['advertising','dashboard','ads']",
            'base_id'               => 'ads_dashboard',
            'class_name'            => __CLASS__,
            'name'                  => __('Ads > Dashboard','advertising'),
            'widget_ops'            => array(
                'classname'         => 'adv-widget-dashboard bsui',
                'description'       => esc_html__("Displays a user's dashboard.",'advertising'),
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
        return array();
    }


    /**
     * Displays advertisers dashboard
     *
     * @since 1.0.1-dev
     *
     * @param array $args Get Arguments.
     * @param array $widget_args Get widget arguments.
     * @param string $content Get widget content.
     * @return string
     *
     */
    public function output( $args = array(), $widget_args = array(),$content = '' ){
        $dashbaord = Adv_Dashboard::instance();
        return $dashbaord->get_html();
    }


}