<?php
/**
 * Main cron Class.
 *
 * @package GeoDirectory
 * @subpackage Booking
 * @version 1.0.0
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Main cron class for handling scheduled tasks related to bookings.
 *
 * This class manages various cron jobs for GeoDirectory Bookings plugin.
 *
 * @package GeoDirectory
 * @subpackage Booking
 * @since   1.0.0
 */
class GeoDir_Booking_CRON {
	// Constants for cron intervals.
	const INTERVAL_COMPLETE_BOOKINGS = 'geodir_booking_complete_bookings';
	const INTERVAL_CANCEL_INVOICES   = 'geodir_booking_cancel_invoices';
	const INTERVAL_DELETE_SYNC_LOGS  = 'geodir_booking_delete_sync_logs';
	const INTERVAL_QUARTER_AN_HOUR   = 'gdbc_15m';
	const INTERVAL_HALF_AN_HOUR      = 'gdbc_30m';

	// Default WordPress intervals.
	const INTERVAL_DAILY       = 'daily';
	const INTERVAL_TWICE_DAILY = 'twicedaily';
	const INTERVAL_HOURLY      = 'hourly';

	/**
	 * The one true instance of GeoDir_Booking_CRON.
	 *
	 * @var GeoDir_Booking_CRON
	 */
	private static $instance;

	/**
	 * Prefix for options.
	 *
	 * @var string
	 */
	protected $options_prefix = 'geodir_booking_ical';

	/**
	 * @var GeoDir_Booking_Options_Handler
	 */
	private $options;

	/**
	 * Array to hold cron objects.
	 *
	 * @var Cron[]
	 */
	private $crons = array();

	/**
	 * Class constructor.
	 */
	public function __construct() {
		$this->options = new GeoDir_Booking_Options_Handler( $this->options_prefix );

		add_action( 'init', array( $this, 'init' ) );
	}

	public function init() {
		$this->init_hooks();

		$this->init_crons();
	}

	/**
	 * Initialize hooks.
	 */
	public function init_hooks() {
		// Schedule the cron and update it whenever the schedule changes.
		add_action( 'cron_schedules', array( $this, 'create_cron_intervals' ) );

		// And update it whenever the schedule changes.
		add_filter( 'geodir_booking_settings_saved', array( $this, 'myabe_reschedule' ) );
	}

	/**
	 * Initialize cron jobs.
	 */
	public function init_crons() {
		$auto_sync_interval = geodir_booking_get_option( 'ical_auto_sync_interval', self::INTERVAL_TWICE_DAILY );

		$crons = array(
			new GeoDir_Booking_Cancel_Invoices_Cron(
				'cancel_invoices',
				self::INTERVAL_CANCEL_INVOICES
			),
			new GeoDir_Booking_Complete_Bookings_Cron(
				'complete_bookings',
				self::INTERVAL_COMPLETE_BOOKINGS
			),
			new GeoDir_Booking_Ical_Auto_Sync_Cron(
				'ical_auto_synchronization',
				$auto_sync_interval
			),
			new GeoDir_Booking_Delete_Sync_Logs_Cron(
				'ical_auto_delete',
				self::INTERVAL_DELETE_SYNC_LOGS
			),
		);

		foreach ( $crons as $cron ) {
			$this->add_cron( $cron );
		}
	}

	/**
	 * Get the one true instance of GeoDir_Booking_CRON.
	 *
	 * @since 1.0
	 * @return GeoDir_Booking_CRON|null
	 */
	public static function instance() {
		if ( ! self::$instance ) {
			self::$instance = new GeoDir_Booking_CRON();
		}

		return self::$instance;
	}

	/**
	 * Create custom cron intervals.
	 *
	 * @param array $schedules Existing cron schedules.
	 * @return array Modified cron schedules.
	 */
	public function create_cron_intervals( $schedules ) {

		$schedules[ self::INTERVAL_QUARTER_AN_HOUR ] = array(
			'interval' => 15 * MINUTE_IN_SECONDS,
			'display'  => __( 'Quarter an Hour', 'geodir-booking' ),
		);

		$schedules[ self::INTERVAL_HALF_AN_HOUR ] = array(
			'interval' => 30 * MINUTE_IN_SECONDS,
			'display'  => __( 'Half an Hour', 'geodir-booking' ),
		);

		$schedules[ self::INTERVAL_CANCEL_INVOICES ] = array(
			'interval' => GeoDir_Booking_Cancel_Invoices_Cron::get_held_duration() * MINUTE_IN_SECONDS,
			'display'  => __( 'Cancel all unpaid invoices after held duration to prevent stock lock.', 'geodir-booking' ),
		);

		$schedules[ self::INTERVAL_COMPLETE_BOOKINGS ] = array(
			'interval' => 1 * HOUR_IN_SECONDS,
			'display'  => __( 'Mark bookings as completed', 'geodir-booking' ),
		);

		$schedules[ self::INTERVAL_DELETE_SYNC_LOGS ] = array(
			'interval' => 6 * HOUR_IN_SECONDS,
			'display'  => __( 'Interval for automatic cleaning of synchronization logs.', 'geodir-booking' ),
		);

		return $schedules;
	}

	/**
	 * Add a cron job.
	 *
	 * @param Cron $cron The cron object.
	 */
	public function add_cron( $cron ) {
		$this->crons[ $cron->get_id() ] = $cron;

		// Schedule the cron
		$cron->schedule();
	}

	/**
	 * Retrieve a cron job by its ID.
	 *
	 * @param string $id The ID of the cron job.
	 * @return Cron|null The cron object if found, otherwise null.
	 */
	public function get_cron( $id ) {
		return isset( $this->crons[ $id ] ) ? $this->crons[ $id ] : null;
	}

	/**
	 * Reschedules the automatic synchronization process based on the provided parameters.
	 *
	 * @param bool   $enable        Whether to enable or disable the automatic synchronization.
	 * @param string $clock_time    Time in 12-hour or 24-hour format: "08:15 pm" or "20:15".
	 * @param string $interval_name Cron interval name.
	 */
	public function reschedule_auto_synchronization( $enable, $clock_time = '01:00', $interval_name = self::INTERVAL_DAILY ) {
		$cron = $this->get_cron( 'ical_auto_synchronization' );

		// If synchronization is disabled, unschedule and delete related options
		if ( ! $enable ) {
			$cron->unschedule();
			$this->options->delete_option( 'auto_sync_previous_clock' );
			$this->options->delete_option( 'auto_sync_previous_interval' );
			$this->options->delete_option( 'auto_sync_worked_once' );
			return;
		}

		// Retrieve previous settings
		$previous_clock_time    = $this->options->get_option( 'auto_sync_previous_clock', false );
		$previous_interval_name = $this->options->get_option( 'auto_sync_previous_interval', false );

		// Check if clock time or interval has changed
		$clock_changed    = ( $previous_clock_time === false || $clock_time != $previous_clock_time );
		$interval_changed = ( $previous_interval_name === false || $interval_name != $previous_interval_name );
		$sync_worked_once = (bool) $this->options->get_option( 'auto_sync_worked_once', false );

		// If no changes made to settings, return
		if ( ! $clock_changed && ! $interval_changed ) {
			return;
		}

		// Calculate scheduled timestamp based on changes
		if ( $clock_changed ) {
			$scheduled_timestamp = geodir_booking_parse_and_calc_timestamp( $clock_time );
		} else {
			$scheduled_timestamp = wp_next_scheduled( $cron->get_action() );

			// Adjust wait time if the process was started
			if ( $scheduled_timestamp !== false && $sync_worked_once ) {
				$schedules     = wp_get_schedules();
				$interval_time = $schedules[ $interval_name ]['interval'];
				$current_time  = time();
				$wait_time     = $scheduled_timestamp - $current_time;
				if ( $wait_time > $interval_time ) {
					$scheduled_timestamp = $current_time + $interval_time;
				}
			} else {
				$scheduled_timestamp = geodir_booking_parse_and_calc_timestamp( $clock_time );
			}
		}

		// Unscheduled the previous task and schedule the new one
		$cron->unschedule();
		$cron->set_interval( $interval_name );
		$cron->schedule_at( $scheduled_timestamp );

		// Update previous settings
		$this->options->update_option( 'auto_sync_previous_clock', $clock_time, 'no' );
		$this->options->update_option( 'auto_sync_previous_interval', $interval_name, 'no' );
	}

	/**
	 * @return string
	 */
	public function get_duration_delete_sync_logs() {
		return geodir_booking_get_option( 'ical_auto_delete_period', 'quarter' );
	}

	/**
	 * Reschedule the crons after settings update.
	 */
	public function myabe_reschedule() {
		// update iCal autosynchronization cron.
		$auto_sync_enabled  = (bool) geodir_booking_get_option( 'ical_auto_sync_enable', false );
		$auto_sync_clock    = geodir_booking_get_option( 'ical_auto_sync_clock', false );
		$auto_sync_interval = geodir_booking_get_option( 'ical_auto_sync_interval', false );

		$this->reschedule_auto_synchronization( $auto_sync_enabled, $auto_sync_clock, $auto_sync_interval );

		// delete old sync logs.
		$auto_delete_period = $this->get_duration_delete_sync_logs();

		if ( 'never' !== $auto_delete_period ) {
			self::instance()->get_cron( 'ical_auto_delete' )->schedule();
		} else {
			self::instance()->get_cron( 'ical_auto_delete' )->unschedule();
		}

		// cancel invoices cron.
		self::instance()->get_cron( 'cancel_invoices' )->unschedule();
		self::instance()->get_cron( 'cancel_invoices' )->schedule();
	}
}
