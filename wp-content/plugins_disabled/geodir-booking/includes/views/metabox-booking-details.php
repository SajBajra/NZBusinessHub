<?php

/**
 * Admin View: Booking details metabox.
 *
 * @var GeoDir_Customer_Booking $booking The booking object.
 */
defined( 'ABSPATH' ) || exit;

global $aui_bs5;

echo '<div style="margin-top:16px; max-width: 600px;">';

aui()->input(
	array(
		'type'              => 'text',
		'id'                => 'geodir-booking-listing',
		'name'              => 'geodir-booking-listing',
		'value'             => get_the_title( $booking->listing_id ),
		'label'             => __( 'Listing', 'geodir-booking' ),
		'label_type'        => 'horizontal',
		'label_class'       => ( $aui_bs5 ? 'fw-bold' : 'font-weight-bold' ),
		'label_col'         => '3',
		'size'              => 'sm',
		'class'             => 'bg-light',
		'input_group_right' => sprintf(
			'<span class="input-group-text"><a href="%s" class="text-dark" target="_blank"><i class="fa fa-eye" aria-hidden="true"></i></a></span>',
			esc_url( geodir_get_listing_url( $booking->listing_id ) )
		),
		'extra_attributes'  => array(
			'readonly' => 'readonly',
		),
	),
	true
);

aui()->input(
	array(
		'type'        => 'text',
		'id'          => 'geodir-booking-name',
		'name'        => 'geodir_booking[name]',
		'value'       => $booking->name,
		'label'       => __( 'Customer Name', 'geodir-booking' ),
		'label_type'  => 'horizontal',
		'label_class' => ( $aui_bs5 ? 'fw-bold' : 'font-weight-bold' ),
		'label_col'   => '3',
		'size'        => 'sm',
	),
	true
);

aui()->input(
	array(
		'type'        => 'email',
		'id'          => 'geodir-booking-email',
		'name'        => 'geodir_booking[email]',
		'value'       => $booking->email,
		'label'       => __( 'Customer Email', 'geodir-booking' ),
		'label_type'  => 'horizontal',
		'label_class' => ( $aui_bs5 ? 'fw-bold' : 'font-weight-bold' ),
		'label_col'   => '3',
		'size'        => 'sm',
	),
	true
);

aui()->input(
	array(
		'type'        => 'tel',
		'id'          => 'geodir-booking-phone',
		'name'        => 'geodir_booking[phone]',
		'value'       => $booking->phone,
		'label'       => __( 'Customer Phone', 'geodir-booking' ),
		'label_type'  => 'horizontal',
		'label_class' => ( $aui_bs5 ? 'fw-bold' : 'font-weight-bold' ),
		'label_col'   => '3',
		'size'        => 'sm',
	),
	true
);

aui()->input(
	array(
		'type'            => 'text',
		'id'               => 'geodir-booking-start_date',
		'name'             => 'geodir_booking_start_date',
		'value'            => geodir_booking_date( $booking->start_date, 'view_day' ),
		'label'            => __( 'Check-in Date', 'geodir-booking' ),
		'label_type'       => 'horizontal',
		'label_class'      => ( $aui_bs5 ? 'fw-bold' : 'font-weight-bold' ),
		'size'             => 'sm',
		'label_col'        => '3',
		'class'            => 'bg-light',
		'extra_attributes' => array(
			'readonly' => 'readonly',
		),
	),
	true
);

aui()->input(
	array(
		'type'            => 'text',
		'id'               => 'geodir-booking-end_date',
		'name'             => 'geodir-booking-end_date',
		'value'            => geodir_booking_date( $booking->end_date, 'view_day' ),
		'label'            => __( 'Check-out Date', 'geodir-booking' ),
		'label_type'       => 'horizontal',
		'label_class'      => ( $aui_bs5 ? 'fw-bold' : 'font-weight-bold' ),
		'size'             => 'sm',
		'label_col'        => '3',
		'class'            => 'bg-light',
		'extra_attributes' => array(
			'readonly' => 'readonly',
		),
	),
	true
);

echo '</div>';
