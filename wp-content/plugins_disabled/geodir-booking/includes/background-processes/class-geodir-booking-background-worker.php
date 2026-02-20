<?php
/**
 * Main calendar background worker Class.
 *
 * @package GeoDirectory
 * @subpackage Booking
 * @version 1.0.0
 * @since   1.0.0
 */

use GeoDir\Bookings\Libraries\Wp_Background_Processing\WP_Background_Process;

defined( 'ABSPATH' ) || exit;

/**
 * Abstract class for background workers handling booking synchronization tasks.
 *
 * This class extends the WP_Background_Process class and provides methods to handle background tasks related to booking synchronization.
 *
 * @package GeoDirectory
 * @subpackage Booking
 * @version 1.0.0
 * @since   1.0.0
 */
abstract class GeoDir_Booking_Background_Worker extends WP_Background_Process {

	/**
	 * The batch size for processing tasks.
	 */
	const BATCH_SIZE = 1000;

	/**
	 * Action identifier for pulling URLs.
	 */
	const ACTION_PULL_URLS = 'pull-urls';

	/**
	 * Action identifier for parsing calendar data.
	 */
	const ACTION_PARSE = 'parse';

	/**
	 * Action identifier for importing booking data.
	 */
	const ACTION_IMPORT = 'import';

	/**
	 * Action identifier for cleaning up outdated bookings.
	 */
	const ACTION_CLEAN = 'clean';

	/**
	 * Maximum request timeout duration.
	 */
	const MAX_REQUEST_TIMEOUT = 30;

	/**
	 * Prefix for background worker.
	 *
	 * @var string
	 */
	protected $prefix = 'geodir_booking_ical';

	/**
	 * Instance of the calendar importer.
	 *
	 * @var GeoDir_Booking_Ical_Importer
	 */
	public $importer;

	/**
	 * Instance of the logger.
	 *
	 * @var GeoDir_Booking_Logger
	 */
	public $logger;

	/**
	 * Instance of the statistics handler.
	 *
	 * @var GeoDir_Booking_Stats
	 */
	public $stats;

	/**
	 * Instance of the options handler.
	 *
	 * @var GeoDir_Booking_Options_Handler
	 */
	protected $options;

	/**
	 * Maximum execution time for the background process.
	 *
	 * @var int
	 */
	protected $max_execution_time = 0;

	/**
	 * Constructor method.
	 * Adds the blog ID to the prefix, initializes options, logger, importer, stats, and sets the maximum execution time.
	 */
	public function __construct() {
		// Add blog ID to the prefix (only for multisites and only for IDs 2, 3 and so on).
		$blog_id = get_current_blog_id();
		if ( $blog_id > 1 ) {
			$this->prefix .= '_' . $blog_id;
		}

		parent::__construct();

		// We'll need options to get current item from wp_option in background-sync
		$this->options = new GeoDir_Booking_Options_Handler( $this->identifier );

		// Get the current item and find its queue ID.
		$current_item = $this->get_current_item();
		$queue_id     = ! empty( $current_item ) ? GeoDir_Booking_Queue::instance()->find_id( $current_item ) : 0;

		$this->logger             = new GeoDir_Booking_Logger( $queue_id );
		$this->importer           = new GeoDir_Booking_Ical_Importer( $this->logger );
		$this->stats              = new GeoDir_Booking_Stats( $queue_id );
		$this->max_execution_time = intval( ini_get( 'max_execution_time' ) );
	}

	/**
	 * Checks if the background process is in progress.
	 *
	 * @return bool
	 */
	public function is_in_progress() {
		return $this->is_process_running() || ! $this->is_queue_empty();
	}

	/**
	 * Checks if the background process is aborting.
	 *
	 * @return bool
	 */
	public function is_aborting() {
		return $this->options->get_option_no_cache( 'abort_current', false );
	}

	/**
	 * Touches the background process to restart if needed.
	 */
	public function touch() {
		if ( ! $this->is_process_running() && ! $this->is_queue_empty() ) {
			// Background process down, but was not finished. Restart it.
			$this->dispatch();
		}
	}

	/**
	 * Aborts the background process if it's in progress.
	 */
	public function abort() {
		if ( $this->is_in_progress() ) {
			$this->options->update_option( 'abort_current', true );
		}
	}

	/**
	 * Resets the background process before starting a new one.
	 * On finish, resets the stats.
	 */
	public function reset() {
		$this->clear_options();

		$queue_item = $this->get_current_item();
		$queue_id   = GeoDir_Booking_Queue::instance()->find_id( $queue_item );

		$this->logger->set_queue_id( $queue_id );
		$this->stats->set_queue_id( $queue_id );

		if ( ! empty( $queue_id ) ) {
			GeoDir_Booking_Stats::instance()->reset_stats( $queue_id );
		}
	}

	/**
	 * Clears options on start and finish.
	 */
	public function clear_options() {
		$this->options->delete_option( 'abort_current' );
	}

	/**
	 * Handles completion of background process.
	 */
	protected function complete() {
		parent::complete();

		$this->clear_options();

		do_action( $this->identifier . '_complete' );
	}

	/**
	 * Calculates time left for the background process.
	 *
	 * @return int
	 */
	protected function time_left() {
		if ( $this->max_execution_time > 0 ) {
			return $this->start_time + $this->max_execution_time - time();
		} else {
			return self::MAX_REQUEST_TIMEOUT;
		}
	}

	/**
	 * Retrieves the identifier of the background process.
	 *
	 * @return string
	 */
	public function get_identifier() {
		return $this->identifier;
	}

	/**
	 * Retrieves the options of the background process.
	 *
	 * @return GeoDir_Booking_Options_Handler
	 */
	public function get_options() {
		return $this->options;
	}

	/**
	 * Retrieves the current item of the background process.
	 *
	 * @return string
	 */
	public function get_current_item() {
		return '';
	}

	/**
	 * Retrieves the progress of the background process.
	 *
	 * @return int
	 */
	public function get_progress() {
		$stats = $this->stats->get_stats();

		$total     = $stats['total'];
		$processed = $stats['succeed'] + $stats['skipped'] + $stats['failed'] + $stats['removed'];

		if ( $total == 0 ) {
			return $this->is_in_progress() ? 0 : 100;
		} else {
			return min( round( $processed / $total * 100 ), 100 );
		}
	}

	/**
	 * Handles each task of the background process.
	 *
	 * @param array $task
	 * @return array|false
	 */
	protected function task( $task ) {
		if ( $this->is_aborting() ) {
			$this->cancel_process();
			return false;
		}

		if ( ! isset( $task['action'] ) ) {
			return false;
		}

		if ( ! empty( $task['queue_id'] ) ) {
			$this->logger->set_queue_id( $task['queue_id'] );
			$this->stats->set_queue_id( $task['queue_id'] );
		}

		switch ( $task['action'] ) {
			case self::ACTION_PARSE:
				$task = $this->task_parse( $task );
				break;
			case self::ACTION_IMPORT:
				$task = $this->task_import( $task );
				break;
			case self::ACTION_PULL_URLS:
				$task = $this->task_pull_urls( $task );
				break;
			case self::ACTION_CLEAN:
				$task = $this->task_clean( $task );
				break;
		}

		return $task;
	}

	/**
	 * Adds pull URL task to the background process.
	 *
	 * @param array $workload [listing_id]
	 */
	public function add_pull_url_task( $workload ) {
		$tasks = array(
			array_merge(
				$workload,
				array(
					'action'   => self::ACTION_PULL_URLS,
					'queue_id' => $this->stats->get_queue_id(),
				)
			),
		);

		$this->add_tasks( $tasks );
	}

	/**
	 * Adds parse tasks to the background process.
	 *
	 * @param array $workloads [[listing_id, calendar_uri, sync_id, queue_id], ...]
	 */
	public function add_parse_tasks( $workloads ) {
		$tasks = array_map(
			function ( $workload ) {
				$workload['action'] = GeoDir_Booking_Background_Worker::ACTION_PARSE;
				return $workload;
			},
			$workloads
		);

		$this->add_tasks( $tasks );
	}

	/**
	 * Adds import tasks to the background process.
	 *
	 * @param array $workloads [[event, sync_id, queue_id], ...]
	 */
	public function add_import_tasks( $workloads ) {
		$tasks = array_map(
			function ( $workload ) {
				$workload['action'] = GeoDir_Booking_Background_Worker::ACTION_IMPORT;
				return $workload;
			},
			$workloads
		);

		$this->add_tasks( $tasks );
	}

	/**
	 * Adds clean tasks to the background process.
	 *
	 * @param array $workloads
	 */
	public function add_clean_tasks( $workloads ) {
		$tasks = array_map(
			function ( $workload ) {
				$workload['action'] = GeoDir_Booking_Background_Worker::ACTION_CLEAN;
				return $workload;
			},
			$workloads
		);

		$this->add_tasks( $tasks );
	}

	/**
	 * Adds tasks to the background process.
	 *
	 * @param array $tasks
	 */
	protected function add_tasks( $tasks ) {
		// Save new batches
		$batch_size = apply_filters( "{$this->identifier}_batch_size", self::BATCH_SIZE );
		$batches    = array_chunk( $tasks, $batch_size );

		foreach ( $batches as $batch ) {
			$this->data( $batch )->save();
		}

		$this->touch();
	}

	/**
	 * Abstract method to retrieve calendar name from source.
	 *
	 * @param string $calendar_uri
	 * @return mixed
	 */
	abstract protected function retrieve_calendar_name_from_source( $calendar_uri );

	/**
	 * Abstract method to retrieve calendar content from source.
	 *
	 * @param string $calendar_uri
	 * @return mixed
	 *
	 * @throws GeoDir_Booking_Execution_Time_Exception
	 * @throws GeoDir_Booking_Request_Exception
	 *
	 */
	abstract protected function retrieve_calendar_content_from_source( $calendar_uri );

	/**
	 * Parses the task for parsing calendar data.
	 *
	 * This method parses the calendar data from the provided URI, extracts events, and performs necessary actions based on the parsed data.
	 *
	 * @param array $task The task containing listing ID, calendar URI, sync ID, and queue ID.
	 * @return array|false Returns an array of tasks or false if the parsing encounters an error.
	 */
	protected function task_parse( $task ) {
		global $wpdb;

		$listing_id    = $task['listing_id'];
		$calendar_uri  = $task['calendar_uri'];
		$calendar_name = $this->retrieve_calendar_name_from_source( $calendar_uri );

		try {
			$calendar_content = $this->retrieve_calendar_content_from_source( $calendar_uri );

			$ical         = new GeoDir_Booking_Ical( $calendar_content );
			$events       = $ical->get_events_data( $listing_id );
			$events_count = count( $events );

			if ( 0 < $events_count ) {
				$this->logger->info(
					sprintf(
						_nx(
							'%1$d event found in calendar %2$s',
							'%1$d events found in calendar %2$s',
							$events_count,
							'%s - calendar URI or calendar filename',
							'geodir-booking'
						),
						$events_count,
						$calendar_name
					)
				);

				$import_tasks = array_map(
					function ( $event ) use ( $task ) {
						return array(
							'event'    => $event,
							'sync_id'  => $task['sync_id'],
							'queue_id' => $task['queue_id'],
						);
					},
					$events
				);

				$this->add_import_tasks( $import_tasks );
				$this->stats->increase_imports_total( $events_count );

			} elseif ( empty( $calendar_content ) ) {

				$this->logger->info(
					sprintf(
						_x(
							'Calendar source is empty (%s)',
							'%s - calendar URI or calendar filename',
							'geodir-booking'
						),
						$calendar_name
					)
				);

			} else {

				$this->logger->info(
					sprintf(
						_x(
							'Calendar file is not empty, but there are no events in %s',
							'%s - calendar URI or calendar filename',
							'geodir-booking'
						),
						$calendar_name
					)
				);
			}

			// Remove all old day rules (which are not in import anymore)
			$old_day_rules = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT DISTINCT uid, id, listing_id, rule_date FROM {$wpdb->prefix}gdbc_day_rules WHERE sync_id = %s GROUP BY uid",
					$task['sync_id']
				)
			);

			if ( ! empty( $old_day_rules ) ) {
				$clean_tasks = array_map(
					function ( $day_rule ) use ( $task ) {
						return array(
							'day_rule_id' => (int) $day_rule->id,
							'rule_date'   => $day_rule->rule_date,
							'sync_id'     => $task['sync_id'],
							'queue_id'    => $task['queue_id'],
							'listing_id'  => (int) $day_rule->listing_id,
						);
					},
					$old_day_rules
				);

				$this->add_clean_tasks( $clean_tasks );

				$tasks_count = count( $clean_tasks );
				$this->stats->increase_cleans_total( $tasks_count );

				$log_message = sprintf(
					_n( 'We will need to check %d previous day rule after importing and remove it if it is outdated.', 'We will need to check %d previous day rules after importing and remove the outdated ones.', $tasks_count, 'geodir-booking' ),
					$tasks_count
				);

				$this->logger->info( $log_message );
			}
		} catch ( GeoDir_Booking_Execution_Time_Exception $e ) {
			// Stop executing ACTION_PARSE task, restart the process, and give more time to request files
			add_filter( $this->identifier . '_time_exceeded', '__return_true' );

			/**
			 * There might be problems on hosts with a low max_execution_time:
			 *
			 * WP Background Processing library does not check the execution time option and always schedules 20 seconds for every handle cycle.
			 * Process can fall and restart only by cron (only every 5 minutes).
			 * Process can go into an infinite loop, restarting every time because of a negative timeout.
			 */

			return $task;

		} catch ( GeoDir_Booking_Request_Exception $e ) {
			$this->logger->error( sprintf( __( 'Error while loading calendar (%1$s): %2$s', 'geodir-booking' ), $calendar_uri, $e->getMessage() ) );
		} catch ( Exception $e ) {
			$this->logger->error( sprintf( _x( 'Parse error. %s', '%s - error description', 'geodir-booking' ), $e->getMessage() ) );
		}

		return false;
	}

	/**
	 * Imports the task data into the system.
	 *
	 * @param array $task
	 * @return array|false
	 */
	protected function task_import( $task ) {
		$import_status = $this->importer->import( $task['event'], $task['sync_id'], $task['queue_id'] );

		switch ( $import_status ) {
			case GeoDir_Booking_Import_Status::SUCCESS:
				$this->stats->increase_succeed_imports( 1 );
				break;

			case GeoDir_Booking_Import_Status::SKIPPED:
				$this->stats->increase_skipped_imports( 1 );
				break;

			case GeoDir_Booking_Import_Status::FAILED:
				$this->stats->increase_failed_imports( 1 );
				break;
		}

		return false;
	}

	/**
	 * Cleans up outdated day rules.
	 *
	 * @param array $task
	 * @return array|false
	 */
	protected function task_clean( $task ) {
		global $wpdb;

		// Get the day rule.
		$day_rule = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}gdbc_day_rules WHERE id = %d",
				absint( $task['day_rule_id'] )
			)
		);

		if ( empty( $day_rule ) ) {
			// The day rule was removed by "import" task
			$this->logger->info( sprintf( __( 'Skipped. Outdated day rule #%d already removed.', 'geodir-booking' ), $task['day_rule_id'] ) );
			$this->stats->increase_skipped_cleans( 1 );
			return false;
		}

		if ( (int) $day_rule->sync_queue_id === (int) $task['queue_id'] ) {
			$this->logger->info( sprintf( __( 'Skipped. Day rule #%d updated with new data.', 'geodir-booking' ), $task['day_rule_id'] ) );
			$this->stats->increase_skipped_cleans( 1 );
			return false;
		}

		if ( GeoDir_Booking_Ical_Importer::is_booking_too_old_for_import( new DateTime( $day_rule->rule_date ) ) ) {
			$this->stats->increase_skipped_cleans( 1 );
			return false;
		}

		$_day_rule = new GeoDir_Booking_Day_Rule( array( 'id' => (int) $day_rule->id ) );

		// Remove the ical day rule.
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
				'uid'        => $day_rule->uid,
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

		if ( ! empty( $_day_rule ) && $_day_rule->exists() ) {
			// Sync with the availability table.
			$_day_rule->release_dates();
		}

		$this->stats->increase_done_cleans( 1 );
		$this->logger->success(
			sprintf(
				__( 'The outdated day rule #%d has been removed.', 'geodir-booking' ),
				$task['day_rule_id']
			)
		);

		return false;
	}

	/**
	 * Pulls URLs for background processing.
	 *
	 * @param array $task
	 * @return array|false
	 */
	abstract protected function task_pull_urls( $task );
}
