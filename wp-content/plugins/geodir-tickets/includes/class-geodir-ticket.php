<?php
/**
 * Contains the ticket class
 *
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Ticket class.
 *
 * @since 1.0.0
 *
 */
class GeoDir_Ticket extends GetPaid_Data  {

	/**
	 * Which data store to load.
	 *
	 * @var string
	 */
    protected $data_store_name = 'ticket';

    /**
	 * This is the name of this object type.
	 *
	 * @var string
	 */
	protected $object_type = 'ticket';

	/**
	 * Discount Data array. This is the core item data exposed in APIs.
	 *
	 * @since 1.0.0
	 * @var array
	 */
	protected $data = array(
		'price'              => 0,
		'seller_price'       => 0,
		'site_commision'     => 0,
        'event_id'           => 0,
        'seller_id'          => 0,
        'buyer_id'           => 0,
		'invoice_id'         => 0,
		'date_created'       => '0000-00-00 00:00:00',
		'date_used'          => '0000-00-00 00:00:00',
        'status'             => 'pending',
        'type'               => 'default',
    );

	/**
	 * Get the ticket if ID is passed, otherwise the ticket is new and empty.
	 *
	 * @param int|string|GeoDir_Ticket $ticket ticket id, object, or key.
	 */
	public function __construct( $ticket = 0 ) {
		parent::__construct( $ticket );

		if ( is_numeric( $ticket ) ) {
			$this->set_id( $ticket );
		} elseif ( $ticket instanceof self ) {
			$this->set_id( $ticket->get_id() );
		} elseif ( is_object( $ticket ) ) {
			$this->set_id( $ticket->id );
		} else {
			$this->set_object_read( true );
		}

        // Load the datastore.
		$this->data_store = GetPaid_Data_Store::load( $this->data_store_name );

		if ( $this->get_id() > 0 ) {
			$this->data_store->read( $this );
        }

	}

	/**
     * Clears the ticket's cache.
     */
    public function clear_cache() {
		wp_cache_delete( $this->get_id(), 'geodir_tickets' );
	}

	/*
	|--------------------------------------------------------------------------
	| CRUD methods
	|--------------------------------------------------------------------------
	|
	| Methods which create, read, update and delete discounts from the database.
	|
    */

    /*
	|--------------------------------------------------------------------------
	| Getters
	|--------------------------------------------------------------------------
	*/

	/**
	 * Get ticket status.
	 *
	 * @since 1.0.0
	 * @param  string $context View or edit context.
	 * @return string
	 */
	public function get_status( $context = 'view' ) {
		return $this->get_prop( 'status', $context );
	}

    /**
	 * Get ticket type.
	 *
	 * @since 1.0.0
	 * @param  string $context View or edit context.
	 * @return string
	 */
	public function get_type( $context = 'view' ) {
		return $this->get_prop( 'type', $context );
    }

    /**
	 * Get event id.
	 *
	 * @since 1.0.0
	 * @param  string $context View or edit context.
	 * @return int
	 */
	public function get_event_id( $context = 'view' ) {
		return (int) $this->get_prop( 'event_id', $context );
	}

	/**
	 * Get seller id.
	 *
	 * @since 1.0.0
	 * @param  string $context View or edit context.
	 * @return int
	 */
	public function get_seller_id( $context = 'view' ) {
		return (int) $this->get_prop( 'seller_id', $context );
	}

	/**
	 * Get buyer id.
	 *
	 * @since 1.0.0
	 * @param  string $context View or edit context.
	 * @return int
	 */
	public function get_buyer_id( $context = 'view' ) {
		return (int) $this->get_prop( 'buyer_id', $context );
	}

	/**
	 * Get invoice id.
	 *
	 * @since 1.0.0
	 * @param  string $context View or edit context.
	 * @return int
	 */
	public function get_invoice_id( $context = 'view' ) {
		return (int) $this->get_prop( 'invoice_id', $context );
	}

	/**
	 * Get the date the ticket was created.
	 *
	 * @since 1.0.0
	 * @param  string $context View or edit context.
	 * @return string
	 */
	public function get_date_created( $context = 'view' ) {
		return $this->get_prop( 'date_created', $context );
    }

	/**
	 * Get the date the ticket was used.
	 *
	 * @since 1.0.0
	 * @param  string $context View or edit context.
	 * @return string
	 */
	public function get_date_used( $context = 'view' ) {
		return $this->get_prop( 'date_used', $context );
    }

	/**
	 * Get the site commision.
	 *
	 * @since 1.0.0
	 * @param  string $context View or edit context.
	 * @return float
	 */
	public function get_site_commision( $context = 'view' ) {
		return (float) $this->get_prop( 'site_commision', $context );
    }

	/**
	 * Get the seller price.
	 *
	 * @since 1.0.0
	 * @param  string $context View or edit context.
	 * @return float
	 */
	public function get_seller_price( $context = 'view' ) {
		return (float) $this->get_prop( 'seller_price', $context );
    }

	/**
	 * Get the ticket price.
	 *
	 * @since 1.0.0
	 * @param  string $context View or edit context.
	 * @return float
	 */
	public function get_price( $context = 'view' ) {
		return (float) $this->get_prop( 'price', $context );
    }

	/**
	 * Get the ticket number.
	 *
	 * @since 1.0.0
	 * @param  string $context View or edit context.
	 * @return string
	 */
	public function get_number( $context = 'view' ) {
		return sprintf(
			'%d-%d-%d-%d',
			$this->get_event_id( $context ),
			$this->get_type( $context ),
			$this->get_id( $context ),
			substr( strtotime( $this->get_date_created( $context ) ), -4 )
		);
    }

	/**
	 * Get the event date.
	 *
	 * @since 2.1.2
	 * @param  string $context View or edit context.
	 * @return string
	 */
	public function get_event_date( $context = 'view' ) {
		$event_dates = geodir_get_invoice_ticket_dates( $this->get_invoice_id(), $this->get_event_id() );

		if ( empty( $event_dates ) ) {
			return '';
		}

		$start_date = '';
		$end_date = '';

		if ( is_array( $event_dates ) && count( $event_dates ) > 3 ) {
			$start_date = $event_dates[0];
			$end_date = $event_dates[1];
		} else if ( is_array( $event_dates ) && count( $event_dates ) == 1 ) {
			$start_date = $event_dates[0];
		} else if ( is_scalar( $event_dates ) ) {
			$start_date = $event_dates;
		}

		if ( empty( $start_date ) ) {
			return '';
		}

		return geodir_ticket_format_event_date( $start_date, $end_date );
	}

	/**
	 * Get the event time.
	 *
	 * @since 2.1.2
	 * @param  string $context View or edit context.
	 * @return string
	 */
	public function get_event_time( $context = 'view' ) {
		$event_dates = geodir_get_invoice_ticket_dates( $this->get_invoice_id(), $this->get_event_id() );

		if ( empty( $event_dates ) ) {
			return '';
		}

		if ( is_array( $event_dates ) && count( $event_dates ) > 3 ) {
			$start_time = $event_dates[2];
			$end_time = $event_dates[3];
		} else {
			return '';
		}

		return geodir_ticket_format_event_time( $start_time, $end_time );
	}

	/*
	|--------------------------------------------------------------------------
	| Setters
	|--------------------------------------------------------------------------
	|
	| Functions for setting ticket data. These should not update anything in the
	| database itself and should only change what is stored in the class
	| object.
	*/

	/**
	 * Sets ticket status.
	 *
	 * @since 1.0.0
	 * @param  string $status New status.
	 */
	public function set_status( $status ) {

		if ( in_array( $status, array_keys( geodir_get_ticket_statuses() ) ) ) {
			return $this->set_prop( 'status', $status );
		}

		$this->set_prop( 'status', 'pending' );
	}

	/**
	 * Sets ticket type.
	 *
	 * @since 1.0.0
	 * @param  string $type New type.
	 */
	public function set_type( $type ) {
		$this->set_prop( 'type', $type );
	}

	/**
	 * Sets the event id.
	 *
	 * @since 1.0.0
	 * @param  int $event_id event id.
	 */
	public function set_event_id( $event_id ) {
		$this->set_prop( 'event_id', absint( $event_id ) );
	}

	/**
	 * Sets the seller id.
	 *
	 * @since 1.0.0
	 * @param  int $seller_id seller id.
	 */
	public function set_seller_id( $seller_id ) {
		$this->set_prop( 'seller_id', absint( $seller_id ) );
	}

	/**
	 * Sets the buyer id.
	 *
	 * @since 1.0.0
	 * @param  int $buyer_id buyer id.
	 */
	public function set_buyer_id( $buyer_id ) {
		$this->set_prop( 'buyer_id', absint( $buyer_id ) );
	}

	/**
	 * Sets the invoice id.
	 *
	 * @since 1.0.0
	 * @param  int $invoice_id invoice id.
	 */
	public function set_invoice_id( $invoice_id ) {
		$this->set_prop( 'invoice_id', absint( $invoice_id ) );
	}

	/**
	 * Sets the created date.
	 *
	 * @since 1.0.0
	 * @param  string $date_created date created.
	 */
	public function set_date_created( $date_created ) {

		$date = strtotime( $date_created );

        if ( $date && $date_created !== '0000-00-00 00:00:00'  && $date_created !== '0000-00-00 00:00' ) {
            $this->set_prop( 'date_created', date( 'Y-m-d H:i:s', $date ) );
            return;
		}

		$this->set_prop( 'date_created', '0000-00-00 00:00:00' );
	}

	/**
	 * Sets the date the ticket was used.
	 *
	 * @since 1.0.0
	 * @param  string $date_used date used.
	 */
	public function set_date_used( $date_used ) {

		$date = strtotime( $date_used );

        if ( $date && $date_used !== '0000-00-00 00:00:00'  && $date_used !== '0000-00-00 00:00' ) {
            $this->set_prop( 'date_used', date( 'Y-m-d H:i:s', $date ) );
            return;
		}

		$this->set_prop( 'date_used', '0000-00-00 00:00:00' );
	}

	/**
	 * Sets the site commision.
	 *
	 * @since 1.0.0
	 * @param  float $site_commision site_commision.
	 */
	public function set_site_commision( $site_commision ) {
		$this->set_prop( 'site_commision', floatval( $site_commision ) );
	}

	/**
	 * Sets the seller price.
	 *
	 * @since 1.0.0
	 * @param  float $seller_price seller price.
	 */
	public function set_seller_price( $seller_price ) {
		$this->set_prop( 'seller_price', floatval( $seller_price ) );
	}

	/**
	 * Sets the price.
	 *
	 * @since 1.0.0
	 * @param  float $price price.
	 */
	public function set_price( $price ) {
		$this->set_prop( 'price', floatval( $price ) );
	}

}
