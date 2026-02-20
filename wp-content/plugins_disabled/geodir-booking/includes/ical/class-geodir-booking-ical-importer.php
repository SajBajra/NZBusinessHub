<?php
/**
 * Main iCal importer class.
 *
 * @package GeoDirectory
 * @subpackage Booking
 * @since 1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Handles the importing of events.
 *
 * @package GeoDirectory
 * @subpackage Booking
 * @since 1.0.0
 */
class GeoDir_Booking_Ical_Importer {

	/**
	 * The logger instance.
	 *
	 * @var GeoDir_Booking_Logger
	 */
	protected $logger;

	/**
	 * Indicates whether an import process is currently ongoing.
	 *
	 * @var bool
	 */
	protected $is_importing = false;

	/**
	 * Constructor.
	 *
	 * @param GeoDir_Booking_Logger $logger The logger instance.
	 */
	public function __construct( GeoDir_Booking_Logger $logger ) {
		$this->logger = $logger;

		add_filter( 'geodir_booking_prevent_handle_booking_status_transition', array( $this, 'prevent_status_transition' ) );
	}

	/**
	 * Prevents the status transition if import is in progress.
	 *
	 * @param bool $prevent Whether to prevent the transition.
	 * @return bool
	 */
	public function prevent_status_transition( bool $prevent ) {
		return $prevent || $this->is_importing;
	}

	/**
	 * Checks if a booking is too old for import.
	 *
	 * @param DateTime $booking_check_in The check-in date of the booking.
	 * @return bool
	 */
	public static function is_booking_too_old_for_import( DateTime $booking_check_in ) {
		$today = date( 'Y-m-d' );
		return $booking_check_in->format( 'Y-m-d' ) < $today;
	}

	/**
	 * Finds intersecting bookings.
	 *
	 * @param array $event The event details.
	 * @param string $sync_id The synchronization ID.
	* @param int $queue_id The queue ID.
	 * @return array
	 */
	protected function find_intersecting_bookings( array $event, string $sync_id, int $queue_id ) {
		global $wpdb;

		$bookings = geodir_get_bookings(
			array(
				'listings'                   => array( $event['listing_id'] ),
				'availability_checkin_date'  => $event['check_in'],
				'availability_checkout_date' => $event['check_out'],
				'status_in'                  => array(
					'pending_payment',
					'pending_confirmation',
					'confirmed',
				),
			)
		);

		$day_rules = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}gdbc_day_rules 
            WHERE listing_id = %d AND is_available = 0
            AND rule_date BETWEEN %s AND %s",
				(int) $event['listing_id'],
				$event['check_in'],
				$event['check_out']
			)
		);

		// Group day rules by UID as objects
		$grouped_day_rules = new stdClass();
		foreach ( $day_rules as $day_rule ) {
			if ( ! isset( $grouped_day_rules->{$day_rule->uid} ) ) {
				$grouped_day_rules->{$day_rule->uid} = (object) array(
					'id'            => $day_rule->id,
					'uid'           => $day_rule->uid,
					'sync_id'       => $day_rule->sync_id,
					'sync_queue_id' => $day_rule->sync_queue_id,
					'listing_id'    => $day_rule->listing_id,
					'check_in'      => $day_rule->rule_date,
					'check_out'     => $day_rule->rule_date,
					'dates'         => array( $day_rule->rule_date ),
				);
			} else {
				$grouped_day_rules->{$day_rule->uid}->check_in  = min( $grouped_day_rules->{$day_rule->uid}->check_in, $day_rule->rule_date );
				$grouped_day_rules->{$day_rule->uid}->check_out = max( $grouped_day_rules->{$day_rule->uid}->check_out, $day_rule->rule_date );
				$grouped_day_rules->{$day_rule->uid}->dates[]   = $day_rule->rule_date;
			}
		}

		foreach ( $bookings as $booking ) {
			$grouped_day_rules->{$booking->uid} = (object) array(
				'listing_id'    => $booking->listing_id,
				'uid'           => $booking->uid,
				'sync_id'       => $sync_id,
				'sync_queue_id' => $queue_id,
				'check_in'      => $booking->get_check_in_date()->format( 'Y-m-d' ),
				'check_out'     => $booking->get_check_out_date()->format( 'Y-m-d' ),
				'dates'         => array_keys( $booking->date_amounts ),
			);
		}

		// Adjust check_out date to be the day after the last day rule
		foreach ( $grouped_day_rules as $uid => $booking ) {
			$booking->check_out = date( 'Y-m-d', strtotime( $booking->check_out . ' +1 day' ) );
		}

		// Convert object to array for sorting by check-in date
		$grouped_day_rules = (array) $grouped_day_rules;
		uasort(
			$grouped_day_rules,
			function ( $a, $b ) {
				return strcmp( $a->check_in, $b->check_in );
			}
		);

		return $grouped_day_rules;
	}

	/**
	 * Checks if two periods are the same.
	 *
	 * @param array $event The event details.
	 * @param object $day_rule The day rule entity.
	 * @return bool
	 */
	protected function is_same_period( array $event, object $day_rule ) {
		return $event['check_in'] === $day_rule->check_in
			&& $event['check_out'] === $day_rule->check_out;
	}

	/**
	 * Checks if a booking is outdated.
	 *
	 * @param object $day_rule The day rule entity.
	 * @param string $sync_id The synchronization ID.
	 * @param int $queue_id The queue ID.
	 * @return bool
	 */
	protected function is_outdated_booking( object $day_rule, string $sync_id, int $queue_id ) {
		return $day_rule->sync_id === 'Outdated'
			|| ( $day_rule->sync_id === $sync_id && $day_rule->sync_queue_id != $queue_id );
	}

	/**
	 * Filters out outdated bookings.
	 *
	 * @param array $day_rules The list of day rules.
	 * @param string $sync_id The synchronization ID.
	 * @param int $queue_id The queue ID.
	 * @return array
	 */
	protected function filter_outdated_bookings( array $day_rules, string $sync_id, int $queue_id ) {
		return array_filter(
			$day_rules,
			function ( $day_rule ) use ( $sync_id, $queue_id ) {
				return $this->is_outdated_booking( $day_rule, $sync_id, $queue_id );
			}
		);
	}

	/**
	 * Filters conflicting Dates.
	 *
	 * @param array $intersecting_day_rules The intersecting day rules.
	 * @param array $outdated_day_rules The outdated day rules.
	 * @return array
	 */
	protected function filter_conflicting_dates( array $intersecting_day_rules, array $outdated_day_rules ) {
		$intersecting_dates = array_reduce(
			$intersecting_day_rules,
			function ( $carry, $day_rule ) {
				return array_merge( $carry, $day_rule->dates );
			},
			array()
		);

		$outdated_dates = array_reduce(
			$outdated_day_rules,
			function ( $carry, $day_rule ) {
				return array_merge( $carry, $day_rule->dates );
			},
			array()
		);

		return array_diff( $intersecting_dates, $outdated_dates );
	}

	/**
	 * Imports an event.
	 *
	 * @param array $event The event details.
	 * @param string $sync_id The synchronization ID.
	 * @param int $queue_id The queue ID.
	 * @return int Import status code.
	 */
	public function import( array $event, string $sync_id, int $queue_id ) {
		// If the event is too old, then just skip it
		if ( self::is_booking_too_old_for_import( DateTime::createFromFormat( 'Y-m-d', $event['check_in'] ) ) ) {
			$this->logger->info(
				sprintf(
					__( 'Skipped. Event from %1$s to %2$s has passed.', 'geodir-booking' ),
					$event['check_in'],
					$event['check_out']
				)
			);
			return GeoDir_Booking_Import_Status::SKIPPED;
		}

		// Check intersections with other bookings
		$intersecting_day_rules = $this->find_intersecting_bookings( $event, $sync_id, $queue_id );
		$intersections_count    = count( $intersecting_day_rules );

		// Create new booking if no intersections found
		if ( $intersections_count === 0 ) {
			$new_id = $this->create_booking( $event, $sync_id, $queue_id );

			if ( $new_id ) {
				$this->logger->success(
					sprintf(
						__( 'New booking #%1$d. The dates from %2$s to %3$s are now blocked.', 'geodir-booking' ),
						$new_id,
						$event['check_in'],
						$event['check_out']
					)
				);

				return GeoDir_Booking_Import_Status::SUCCESS;
			} else {
				return GeoDir_Booking_Import_Status::FAILED;
			}
		}

		// If only one intersection with the same dates - skip the event
		if ( $intersections_count == 1 ) {
			$day_rule = reset( $intersecting_day_rules );

			if ( $this->is_same_period( $event, $day_rule ) ) {

				if ( $this->is_outdated_booking( $day_rule, $sync_id, $queue_id ) ) {
					$this->update_booking( $event, $day_rule, $sync_id, $queue_id );
					$this->logger->info(
						sprintf(
							__( 'Success. Booking #%d updated with new data.', 'geodir-booking' ),
							$day_rule->id
						)
					);
					return GeoDir_Booking_Import_Status::SUCCESS;
				} else {
					$this->logger->info(
						sprintf(
							__( 'Skipped. The dates from %1$s to %2$s are already blocked.', 'geodir-booking' ),
							$event['check_in'],
							$event['check_out']
						)
					);
					return GeoDir_Booking_Import_Status::SKIPPED;
				}
			}
		}

		// If all bookings - outdated, then update one and remove all others
		$outdated_day_rules = $this->filter_outdated_bookings( $intersecting_day_rules, $sync_id, $queue_id );

		if ( count( $outdated_day_rules ) == $intersections_count ) {
			$updated_id    = $this->update_one( $event, $intersecting_day_rules, $sync_id, $queue_id );
			$removed_count = $intersections_count - 1;
			$message       = $removed_count > 0
				? __( 'Success. Booking #%1$d updated with new data. Removed %2$d outdated booking(s).', 'geodir-booking' )
				: __( 'Success. Booking #%1$d updated with new data.', 'geodir-booking' );
			$this->logger->info( sprintf( $message, $updated_id, $removed_count ) );
			return GeoDir_Booking_Import_Status::SUCCESS;
		}

		// Cannot import the event
		$conflict_dates = $this->filter_conflicting_dates( $intersecting_day_rules, $outdated_day_rules );

		$this->logger->error(
			sprintf(
				_n(
					'Cannot import new event. Date from %1$s to %2$s is partially blocked.',
					'Cannot import new event. Dates from %1$s to %2$s are partially blocked.',
					count( $conflict_dates ),
					'geodir-booking'
				),
				$event['check_in'],
				$event['check_out']
			)
		);

		return GeoDir_Booking_Import_Status::FAILED;
	}

	/**
	 * Creates a booking.
	 *
	 * @param array $event The event details.
	 * @param string $sync_id The synchronization ID.
	 * @param int $queue_id The queue ID.
	 * @return int|false The ID of the created booking, or false if creation failed.
	 */
	protected function create_booking( array $event, string $sync_id, int $queue_id ) {
		$check_in  = $event['check_in'];
		$check_out = $event['check_out'];

		$this->is_importing = true;

		$ruleset = new GeoDir_Booking_Ruleset( 0, (int) $event['listing_id'] );

		$day_rule = $this->create_or_update_day_rule(
			$check_in,
			(int) $event['listing_id'],
			$event['uid'],
			$sync_id,
			$queue_id,
			(float) $ruleset->nightly_price,
			$event['prodid'],
			$event['summary'],
			$event['description']
		);

		if ( is_wp_error( $day_rule ) ) {
			return false;
		}

		$new_id = $day_rule->id;

		// If the booking spans multiple days, create entries for each day
		$current_date = new DateTime( $check_in );
		$end_date     = new DateTime( $check_out );
		$end_date->modify( '-1 day' ); // Exclude the check-out day

		while ( $current_date < $end_date ) {
			$current_date->modify( '+1 day' );
			$this->create_or_update_day_rule(
				$current_date->format( 'Y-m-d' ),
				(int) $event['listing_id'],
				$event['uid'],
				$sync_id,
				$queue_id,
				(float) $ruleset->nightly_price
			);
		}
		do_action( 'geodir_booking_create_day_rule_via_ical', $new_id );

		$this->is_importing = false;

		return $new_id;
	}

	/**
	 * Updates a booking.
	 *
	 * @param array $event The event details.
	 * @param object $day_rule The day rule entity.
	 * @param string $sync_id The synchronization ID.
	 * @param int $queue_id The queue ID.
	 * @return int The ID of the updated booking.
	 */
	protected function update_booking( array $event, object $day_rule, string $sync_id, int $queue_id ) {
		global $wpdb;

		$check_in  = $event['check_in'];
		$check_out = $event['check_out'];

		$this->is_importing = true;

		// Make the dates available for the existing day rules.
		$wpdb->update(
			$wpdb->prefix . 'gdbc_day_rules',
			array(
				'is_available'     => 1,
				'uid'              => '',
				'ical_prodid'      => '',
				'ical_summary'     => '',
				'ical_description' => '',
				'sync_id'          => '',
				'sync_queue_id'    => '',
			),
			array(
				'uid'        => $event['uid'],
				'listing_id' => (int) $day_rule->listing_id,
			),
			array(
				'%d',
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
				'%d',
			),
			array(
				'%s',
				'%d',
			)
		);

		$listing_id = $day_rule->listing_id;
		$ruleset    = new GeoDir_Booking_Ruleset( 0, (int) $listing_id );

		// Update the first day of the booking to have the iCal details.
		$updated_day_rule = $this->create_or_update_day_rule(
			$check_in,
			(int) $listing_id,
			$event['uid'],
			$sync_id,
			$queue_id,
			(float) $ruleset->nightly_price,
			$event['prodid'],
			$event['summary'],
			$event['description']
		);

		// Create new day rules for the remaining days
		$current_date = new DateTime( $check_in );
		$end_date     = new DateTime( $check_out );
		$end_date->modify( '-1 day' ); // Exclude the check-out day

		while ( $current_date < $end_date ) {
			$current_date->modify( '+1 day' );
			$this->create_or_update_day_rule(
				$current_date->format( 'Y-m-d' ),
				(int) $listing_id,
				$event['uid'],
				$sync_id,
				$queue_id,
				(float) $ruleset->nightly_price
			);
		}

		$this->is_importing = false;

		do_action( 'geodir_booking_update_day_rule_via_ical', $updated_day_rule->id );

		return $updated_day_rule->id;
	}

	/**
	 * Updates one booking and removes others.
	 *
	 * @param array $event The event details.
	 * @param array $day_rules The day rule entities.
	 * @param string $sync_id The synchronization ID.
	 * @param int $queue_id The queue ID.
	 * @return int The updated booking ID.
	 */
	protected function update_one( array $event, array $day_rules, string $sync_id, int $queue_id ) {
		global $wpdb;

		// Select the first day rule to update
		$update_day_rule = array_shift( $day_rules );

		// Make the dates available for the existing day rules.
		$wpdb->update(
			$wpdb->prefix . 'gdbc_day_rules',
			array(
				'is_available'     => 1,
				'uid'              => '',
				'ical_prodid'      => '',
				'ical_summary'     => '',
				'ical_description' => '',
				'sync_id'          => '',
				'sync_queue_id'    => '',
			),
			array(
				'uid'        => $update_day_rule->uid,
				'listing_id' => (int) $update_day_rule->listing_id,
			),
			array(
				'%d',
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
				'%d',
			),
			array(
				'%s',
				'%d',
			)
		);

		// Update the selected day rule
		$check_in  = $event['check_in'];
		$check_out = $event['check_out'];

		$this->is_importing = true;

		$ruleset = new GeoDir_Booking_Ruleset( 0, (int) $update_day_rule->listing_id );

		// Update the first day of the booking to have the iCal details.
		$updated_day_rule = $this->create_or_update_day_rule(
			$check_in,
			(int) $update_day_rule->listing_id,
			$event['uid'],
			$sync_id,
			$queue_id,
			(float) $ruleset->nightly_price,
			$event['prodid'],
			$event['summary'],
			$event['description']
		);

		// Create new day rules for the remaining days
		$current_date = new DateTime( $check_in );
		$end_date     = new DateTime( $check_out );
		$end_date->modify( '-1 day' ); // Exclude the check-out day

		while ( $current_date < $end_date ) {
			$current_date->modify( '+1 day' );
			$this->create_or_update_day_rule(
				$current_date->format( 'Y-m-d' ),
				(int) $update_day_rule->listing_id,
				$event['uid'],
				$sync_id,
				$queue_id,
				(float) $ruleset->nightly_price
			);
		}

		$this->is_importing = false;

		do_action( 'geodir_booking_update_day_rule_via_ical', $updated_day_rule->id );

		return $updated_day_rule->id;
	}

	/**
	 * Creates or updates a day rule.
	 *
	 * @param string $rule_date The rule date.
	 * @param int $listing_id The listing ID.
	 * @param string $uid The unique identifier.
	 * @param string $sync_id The synchronization ID.
	 * @param int $queue_id The queue ID.
	 * @param float $nightly_price The nightly price.
	 * @param string $ical_prodid Optional. The iCal product ID.
	 * @param string $ical_summary Optional. The iCal summary.
	 * @param string $ical_description Optional. The iCal description.
	 * @return GeoDir_Booking_Day_Rule The created or updated day rule.
	 */
	protected function create_or_update_day_rule(
		string $rule_date,
		int $listing_id,
		string $uid,
		string $sync_id,
		int $queue_id,
		float $nightly_price,
		string $ical_prodid = '',
		string $ical_summary = '',
		string $ical_description = ''
	) {
		$day_rule = GeoDir_Booking_Day_Rule::get_by_rule_date( $rule_date, $listing_id );
		if ( $day_rule && $day_rule->exists() ) {
			$day_rule->is_available     = 0;
			$day_rule->rule_date        = $rule_date;
			$day_rule->uid              = $uid;
			$day_rule->ical_prodid      = $ical_prodid;
			$day_rule->ical_summary     = $ical_summary;
			$day_rule->ical_description = $ical_description;
			$day_rule->sync_id          = $sync_id;
			$day_rule->sync_queue_id    = $queue_id;
		} else {
			$day_rule = new GeoDir_Booking_Day_Rule(
				array(
					'listing_id'       => $listing_id,
					'is_available'     => 0,
					'nightly_price'    => $nightly_price,
					'rule_date'        => $rule_date,
					'uid'              => $uid,
					'ical_prodid'      => $ical_prodid,
					'ical_summary'     => $ical_summary,
					'ical_description' => $ical_description,
					'sync_id'          => $sync_id,
					'sync_queue_id'    => $queue_id,
				)
			);
		}

		$day_rule->save();

		// Sync with the availability table.
		$day_rule->release_dates();

		return $day_rule;
	}
}
