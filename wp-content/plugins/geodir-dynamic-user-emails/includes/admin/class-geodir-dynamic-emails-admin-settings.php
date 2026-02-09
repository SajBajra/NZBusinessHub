<?php
/**
 * Dynamic User Emails Admin Settings class
 *
 * @author    AyeCode Ltd
 * @package   GeoDir_Dynamic_Emails
 * @version   2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * GeoDir_Dynamic_Emails_Admin_Settings class.
 */
class GeoDir_Dynamic_Emails_Admin_Settings {
	/**
	 * Init.
	 *
	 * @since 2.0.0
	 */
	public static function init() {
		add_filter( 'geodir_get_settings_pages', array( __CLASS__, 'load_settings_page' ), 51, 1 );
		add_action( 'geodirectory_page_gd-settings', array( __CLASS__, 'gd_field_template' ), 51, 3 );
	}

	/**
	 * Plugin settings page.
	 */
	public static function load_settings_page( $settings_pages ) {
		$post_type = ! empty( $_REQUEST['post_type'] ) ? sanitize_title( $_REQUEST['post_type'] ) : 'gd_place';

		if ( !( isset( $_REQUEST['page'] ) && $_REQUEST['page'] == $post_type.'-settings' ) ) {
			$settings_pages[] = include( GEODIR_DYNAMIC_EMAILS_PLUGIN_DIR . 'includes/admin/settings/class-geodir-dynamic-emails-settings-page.php' );
		}

		return $settings_pages;
	}

	public static function gd_field_template() {
		$post_types = geodir_get_posttypes();

		$content = '<div class="bsui">';
			foreach ( $post_types as $post_type ) {
				$content .= '<div class="geodir-de-tmpl-' . esc_attr( $post_type ) . ' d-none">';
					$content .= self::gd_field_row( array( 'post_type' => $post_type ) );
				$content .= '</div>';
			}
		$content .= '<div class="geodir-de-backups d-none"></div></div>';

		echo $content;
	}

	public static function gd_field_row( $args = array() ) {
		global $aui_bs5;

		$args = wp_parse_args( $args, array(
			'post_type' => '',
			'index' => 'GDDEINDEX',
			'field' => '',
			'condition' => '',
			'search' => '',
		) );

		$index = $args['index'];
		$post_type = $args['post_type'];

		$content = '<div class="geodir-de-field-row input-group mb-2" data-row-index="' . esc_attr( $index ) . '">';
			$content .= '<div class="' . ( $aui_bs5 ? '' : 'input-group-prepend' ) . '"><span class="input-group-text px-2' . ( $aui_bs5 ? ' rounded-start rounded-0' : '' ) . '">' . __( 'IF', 'geodir-dynamic-emails' ) . '</span></div>';
			$content .= aui()->select(
				array(
					'id'          => 'geodir_decf_' . esc_attr( $index ),
					'name'        => 'email_list_fields[' . esc_attr( $index ) . '][field]',
					'label'       => __( 'FIELD', 'geodir-dynamic-emails' ),
					'placeholder' => __( 'FIELD', 'geodir-dynamic-emails' ),
					'class'       => 'geodir_decf_field form-selects',
					'options'     => geodir_get_field_key_options( array( 'post_type' => $post_type, 'context' => 'dynamic-email-filter' ) ),
					'default'     => '',
					'value'       => $args['field'],
					'label_type'  => '',
					'select2'     => false,
					'no_wrap'     => 1,
					'extra_attributes' => array(
						'data-minimum-results-for-search' => '-1'
					)
				)
			);
			$content .= aui()->select(
				array(
					'id'          => 'geodir_decf_cond_' . esc_attr( $index ),
					'name'        => 'email_list_fields[' . esc_attr( $index ) . '][condition]',
					'label'       => __( 'CONDITION', 'geodir-dynamic-emails' ),
					'placeholder' => __( 'CONDITION', 'geodir-dynamic-emails' ),
					'class'       => 'geodir_decf_cond form-selects',
					'options'     => geodir_get_field_condition_options( $args ),
					'default'     => '',
					'value'       => $args['condition'],
					'label_type'  => '',
					'select2'     => false,
					'no_wrap'     => 1,
					'extra_attributes' => array(
						'data-minimum-results-for-search' => '-1'
					)
				)
			);

			$extra_attrs = array();
			if ( $args['condition'] == 'is_empty' || $args['condition'] == 'is_not_empty' ) {
				$extra_attrs['readonly'] = 'readonly';
			}

			$content .= aui()->input(
				array(
					'type'            => 'text',
					'id'              => 'geodir_decf_search_' . esc_attr( $index ),
					'name'            => 'email_list_fields[' . esc_attr( $index ) . '][search]',
					'label'           => __( 'VALUE TO MATCH', 'geodir-dynamic-emails' ),
					'class'           => 'geodir_decf_search',
					'placeholder'     => __( 'VALUE TO MATCH', 'geodir-dynamic-emails' ),
					'label_type'      => '',
					'value'           => $args['search'],
					'no_wrap'         => 1,
					'extra_attributes'=> $extra_attrs
				)
			);

			$content .= '<div class="' . ( $aui_bs5 ? '' : 'input-group-append' ) . '"><span class="input-group-text px-2 ' . ( $aui_bs5 ? 'rounded-end rounded-0' : '' ) . '"><a class="text-danger geodir-de-field-remove" data-toggle="tooltip"  href="javascript:void(0);" title="' . esc_attr__( 'Remove', 'geodir-dynamic-emails' ) . '"><i class="fas fa-circle-minus"></i></a></span></div>';
		$content .= '</div>';

		return $content;
	}
}