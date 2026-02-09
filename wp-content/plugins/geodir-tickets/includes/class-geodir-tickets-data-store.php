<?php

/**
 * GeoDir_Tickets_Data_Store class file.
 *
 */
defined( 'ABSPATH' ) || exit;

/**
 * Tickets Data Store: Stored in a custom table.
 *
 * @version  1.0.0
 */
class GeoDir_Tickets_Data_Store {

	/**
	 * A map of database fields to data types.
	 *
	 * @since 1.0.0
	 * @var array
	 */
	protected $database_fields_to_data_type = array(
		'id'             => '%d',
		'price'          => '%s',
		'seller_price'   => '%s',
		'site_commision' => '%s',
		'status'         => '%s',
		'type'           => '%s',
		'event_id'       => '%d',
		'seller_id'      => '%d',
		'buyer_id'       => '%d',
		'invoice_id'     => '%d',
		'date_created'   => '%s',
		'date_used'      => '%s',
	);

	/*
	|--------------------------------------------------------------------------
	| CRUD Methods
	|--------------------------------------------------------------------------
	*/

	/**
	 * Method to create a new ticket in the database.
	 *
	 * @param GeoDir_Ticket $ticket ticket object.
	 */
	public function create( &$ticket ) {
		global $wpdb;

		$values  = array();
		$formats = array();

		$fields = $this->database_fields_to_data_type;
		unset( $fields['id'] );

		foreach ( $fields as $key => $format ) {
			$method       = "get_$key";
			$values[$key] = $ticket->$method( 'edit' );
			$formats[]    = $format;
		}

		$result = $wpdb->insert( $wpdb->prefix . 'geodir_tickets', $values, $formats );

		if ( $result ) {
			$ticket->set_id( $wpdb->insert_id );
			$ticket->apply_changes();
			$ticket->clear_cache();
			do_action( 'geodir_new_ticket', $ticket );
			return true;
		}

		return false;
	}

	/**
	 * Method to read a ticket from the database.
	 *
	 * @param GeoDir_Ticket $ticket ticket object.
	 *
	 */
	public function read( &$ticket ) {
		global $wpdb;

		$ticket->set_defaults();

		if ( ! $ticket->get_id() ) {
			$ticket->last_error = __( 'Invalid ticket.', 'geodir-tickets' );
			$ticket->set_id( 0 );
			return false;
		}

		// Maybe retrieve from the cache.
		$raw_ticket = wp_cache_get( $ticket->get_id(), 'geodir_tickets' );

		// If not found, retrieve from the db.
		if ( false === $raw_ticket ) {

			$raw_ticket = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT * FROM {$wpdb->prefix}geodir_tickets WHERE id = %d",
					$ticket->get_id()
				)
			);

			// Update the cache with our data
			wp_cache_set( $ticket->get_id(), $raw_ticket, 'geodir_tickets' );

		}

		if ( ! $raw_ticket ) {
			$raw_ticket->last_error = __( 'Invalid ticket.', 'geodir-tickets' );
			return false;
		}

		foreach ( array_keys( $this->database_fields_to_data_type ) as $key ) {
			$method     = "set_$key";
			$ticket->$method( $raw_ticket->$key );
		}

		$ticket->set_object_read( true );
		do_action( 'geodir_read_ticket', $ticket );

	}

	/**
	 * Method to update a ticket in the database.
	 *
	 * @param GeoDir_Ticket $ticket ticket object.
	 */
	public function update( &$ticket ) {
		global $wpdb;

		$changes = $ticket->get_changes();
		$values  = array();
		$format  = array();

		foreach ( $this->database_fields_to_data_type as $key => $format ) {
			if ( array_key_exists( $key, $changes ) ) {
				$method       = "get_$key";
				$values[$key] = $ticket->$method( 'edit' );
				$formats[]    = $format;
			}
		}

		if ( empty( $values ) ) {
			return;
		}

		$wpdb->update(
			$wpdb->prefix . 'geodir_tickets',
			$values,
			array(
				'id' => $ticket->get_id(),
			),
			$formats,
			'%d'
		);

		// Apply the changes.
		$ticket->apply_changes();

		// Delete cache.
		$ticket->clear_cache();

		// Fire a hook.
		do_action( 'geodir_update_ticket', $ticket );

	}

	/**
	 * Method to delete a ticket from the database.
	 *
	 * @param GeoDir_Ticket $ticket
	 */
	public function delete( &$ticket ) {
		global $wpdb;

		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->prefix}geodir_tickets
				WHERE id = %d",
				$ticket->get_id()
			)
		);

		// Delete cache.
		$ticket->clear_cache();

		// Fire a hook.
		do_action( 'geodir_delete_ticket', $ticket );

		$ticket->set_id( 0 );
	}

}
