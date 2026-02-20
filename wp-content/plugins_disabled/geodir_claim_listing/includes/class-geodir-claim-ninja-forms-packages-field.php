<?php
/**
 * Claim Listings Ninja Forms Class.
 * 
 * This class is used to allow claims to be paid if the Pricing Manager addon is installed.
 *
 * @since 2.0.0
 * @package Geodir_Claim_Listing
 * @author AyeCode Ltd
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class GeoDir_Claim_Ninja_Forms_Packages_field extends NF_Abstracts_List {

	protected $_name = 'geodir_packages';

	protected $_type = 'listselect';

	protected $_nicename = 'GD Claim Package';

	protected $_section = 'misc';

	protected $_icon = 'chevron-down';

	protected $_templates = 'listselect';

	protected $_old_classname = 'list-select';

	public function __construct() {
		parent::__construct();

		add_filter( 'ninja_forms_merge_tag_calc_value_' . $this->_type, array( $this, 'get_calc_value' ), 10, 2 );

		// Add the price package options.
		add_filter( 'ninja_forms_render_options', array( $this, 'package_values' ), 10, 2 );
		add_filter( 'ninja_forms_display_fields', array( $this, 'maybe_show_field' ) );
		add_action( 'init', array( $this, 'init' ) );
	}

	public function init() {
		$this->_nicename = _x( 'GD Claim Package', 'GD ninja form', 'geodir-claim' );

		// Rename Label setting to Question.
		$this->_settings[ 'label' ][ 'value' ] = __( 'Select Package', 'geodir-claim' );
		$this->_settings[ 'options' ][ 'value' ] = array();
		$this->_settings[ 'options' ][ 'group' ] = '';
	}

	public function maybe_show_field( $fields ) {
		$post_id = ! empty( $_REQUEST['p'] ) ? absint( $_REQUEST['p'] ) : get_the_ID();
		$post_type = get_post_type( $post_id );

		if ( $post_type && $post_id && ! empty( $fields ) ) {
			$package_id = geodir_get_post_meta( $post_id, 'package_id', true );

			$packages = GeoDir_Claim_Payment::get_upgrade_price_packages( $post_type, $package_id );

			if ( empty( $packages ) ) {
				foreach ( $fields as $key => $field ) {
					if ( $field['type'] == 'geodir_packages' ) {
						// Hide field
						$fields[$key]['options'] = array();
						$fields[$key]['value'] = '';
						$fields[$key]['type'] = 'hidden';
						$fields[$key]['element_templates'] = array('hidden');
					}
				}
			}
		}

		return $fields;
	}

	public function get_calc_value( $value, $field ) {
		if ( isset( $field[ 'options' ] ) ) {
			foreach ($field['options'] as $option ) {
				if( ! isset( $option[ 'value' ] ) || $value != $option[ 'value' ] || ! isset( $option[ 'calc' ] ) ) {
					continue;
				}

				return $option[ 'calc' ];
			}
		}

		return $value;
	}

	/**
	 * Add the price package options.
	 *
	 * @param $options
	 * @param $settings
	 *
	 * @return array
	 */
	public function package_values( $options, $settings ) {
		if ( $settings['type'] == 'geodir_packages' ) {
			$options = array();

			$post_id = ! empty( $_REQUEST['p'] ) ? absint( $_REQUEST['p'] ) : get_the_ID();
			$post_type = get_post_type( $post_id );
			$package_id = geodir_get_post_meta( $post_id, 'package_id', true );

			$packages = GeoDir_Claim_Payment::get_upgrade_price_packages( $post_type, $package_id );

			if ( ! empty( $packages ) ) {
				$options = array();

				foreach ( $packages as $id => $name ) {
					$options[] = array(
						'label' => $name,
						'value' => $id
					);
				}
			}
		}

		return $options;
	}
}