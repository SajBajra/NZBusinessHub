<?php
/**
 * Abstract cron Class.
 *
 * @package GeoDirectory
 * @subpackage Booking
 * @version 1.0.0
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Abstract cron class for scheduling and managing cron jobs.
 *
 * @package GeoDirectory
 * @subpackage Booking
 * @version 1.0.0
 * @since   1.0.0
 */
abstract class GeoDir_Booking_Abstract_Cron {

	/**
	 * The prefix for the action hook.
	 */
	const ACTION_PREFIX = 'geodir_booking_';

	/**
	 * The unique identifier for the cron job.
	 *
	 * @var string
	 */
	protected $id;

	/**
	 * The action hook to execute when the cron job is run.
	 *
	 * @var string
	 */
	protected $action;

	/**
	 * How often the event should recur. Registered WP Interval Name.
	 *
	 * @var string
	 */
	protected $interval;

	/**
	 * Constructor.
	 *
	 * @param string $id       The unique identifier for the cron job.
	 * @param string $interval How often the event should recur. Registered WP Interval Name.
	 */
	public function __construct( $id, $interval ) {
		$this->id       = $id;
		$this->action   = self::ACTION_PREFIX . $this->id;
		$this->interval = $interval;

		add_action( $this->action, array( $this, 'do_cron_job' ) );
	}

	/**
	 * Retrieves the unique identifier for the cron job.
	 *
	 * @return string The unique identifier.
	 */
	public function get_id() {
		return $this->id;
	}

	/**
	 * Retrieves the action hook for the cron job.
	 *
	 * @return string The action hook.
	 */
	public function get_action() {
		return $this->action;
	}

	/**
	 * Sets the interval for the cron job.
	 *
	 * @param string $interval The interval to set.
	 */
	public function set_interval( $interval ) {
		$this->interval = $interval;
	}

	/**
	 * Abstract method to be implemented by subclasses to define the cron job logic.
	 */
	abstract public function do_cron_job();

	/**
	 * Schedules the cron job.
	 */
	public function schedule() {
		if ( ! $this->is_scheduled() ) {
			wp_schedule_event( time(), $this->interval, $this->action );
		}
	}

	/**
	 * Schedules the cron job at a specific timestamp.
	 *
	 * @param int $timestamp The timestamp at which to schedule the cron job.
	 */
	public function schedule_at( $timestamp ) {
		if ( ! $this->is_scheduled() ) {
			wp_schedule_event( $timestamp, $this->interval, $this->action );
		}
	}

	/**
	 * Unschedules the cron job.
	 */
	public function unschedule() {
		// This also works for wp_schedule_single_event()
		wp_clear_scheduled_hook( $this->action );
	}

	/**
	 * Checks if the cron job is scheduled.
	 *
	 * @return bool True if the cron job is scheduled, false otherwise.
	 */
	public function is_scheduled() {
		return (bool) wp_next_scheduled( $this->action );
	}

	/**
	 * Retrieves the timestamp at which the cron job is scheduled to run next.
	 *
	 * @return int|false The timestamp at which the cron job is scheduled to run next, or false if not scheduled.
	 */
	public function scheduled_at() {
		return wp_next_scheduled( $this->action );
	}
}
