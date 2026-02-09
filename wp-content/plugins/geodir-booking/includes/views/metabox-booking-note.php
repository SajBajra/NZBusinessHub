<?php

    /**
     * Admin View: Booking prices metabox.
     *
     * @var GeoDir_Customer_Booking $booking The booking object.
     */
    defined( 'ABSPATH' ) || exit;

	aui()->textarea(
		array(
			'id'        => 'geodir-booking-notes',
			'name'      => 'geodir_booking[private_note]',
			'value'     => $booking->private_note,
			'label'     => __( 'Private Note', 'geodir-booking' ),
			'help_text' => __( 'This note will not be visible to the client.', 'geodir-booking' ),
		),
		true
	);
