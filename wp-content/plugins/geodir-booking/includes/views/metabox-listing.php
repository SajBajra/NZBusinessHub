<?php

/**
 * Admin View: Listing metabox.
 *
 * @var array $booking_settings
 */
defined( 'ABSPATH' ) || exit;

global $aui_bs5;

wp_nonce_field( 'geodir_booking_meta', 'geodir_booking_meta_nonce' );

echo '<div class="bsui">';

foreach ( $booking_settings as $setting_id => $args ) {

	if ( 'text' === $args['type'] ) {
		aui()->input(
			array(
				'type'        => 'text',
				'id'          => 'geodir-booking__' . $setting_id,
				'name'        => 'geodir_booking[' . $setting_id . ']',
				'value'       => $args['value'],
				'label'       => $args['label'],
				'label_type'  => 'top',
				'label_class' => ( $aui_bs5 ? 'fw-bold' : 'font-weight-bold' ),
				'size'        => 'sm',
				'placeholder' => __( 'Use Default', 'geodir-booking' ),
				'help_text'   => isset( $args['desc'] ) ? $args['desc'] : '',
			),
			true
		);
	}

	if ( 'number' === $args['type'] ) {
		aui()->input(
			array(
				'type'             => 'number',
				'id'               => 'geodir-booking__' . $setting_id,
				'name'             => 'geodir_booking[' . $setting_id . ']',
				'value'            => $args['value'],
				'label'            => $args['label'],
				'label_type'       => 'top',
				'label_class'      => ( $aui_bs5 ? 'fw-bold' : 'font-weight-bold' ),
				'size'             => 'sm',
				'placeholder'      => __( 'Use Default', 'geodir-booking' ),
				'help_text'        => isset( $args['desc'] ) ? $args['desc'] : '',
				'extra_attributes' => array(
					'min' => isset( $args['min'] ) ? $args['min'] : '-' . PHP_INT_MAX,
					'max' => isset( $args['max'] ) ? $args['max'] : PHP_INT_MAX,
				),
			),
			true
		);
	}

	if ( 'select' === $args['type'] ) {
		aui()->select(
			array(
				'id'          => 'geodir-booking__' . $setting_id,
				'name'        => 'geodir_booking[' . $setting_id . ']',
				'value'       => $args['value'],
				'label'       => $args['label'],
				'label_type'  => 'top',
				'label_class' => ( $aui_bs5 ? 'fw-bold' : 'font-weight-bold' ),
				'size'        => 'sm',
				'placeholder' => __( 'Use Default', 'geodir-booking' ),
				'help_text'   => isset( $args['desc'] ) ? $args['desc'] : '',
				'options'     => array_merge(
					array(
						'' => __( 'Use Default', 'geodir-booking' ),
					),
					$args['options']
				),
			),
			true
		);
	}
}

echo '</div>';
