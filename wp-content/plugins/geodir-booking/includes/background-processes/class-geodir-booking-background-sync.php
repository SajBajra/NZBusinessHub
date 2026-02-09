<?php
/**
 * Main background synchronizer Class.
 *
 * @package GeoDirectory
 * @subpackage Booking
 * @version 1.0.0
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Background synchronization class for calendars.
 *
 * Handles background synchronization of calendars, pulling URLs and initiating parsing tasks.
 *
 * @package GeoDirectory
 * @subpackage Booking
 * @version 1.0.0
 * @since   1.0.0
 */
class GeoDir_Booking_Background_Sync extends GeoDir_Booking_Background_Worker {
	/**
	 * The one true instance of GeoDir_Booking_Background_Sync.
	 *
	 * @var GeoDir_Booking_Background_Sync
	 */
	private static $instance;

	protected $action = 'sync';

	/**
	 * Retrieves the one true instance of GeoDir_Booking_Background_Sync.
	 *
	 * @since 1.0
	 * @return GeoDir_Booking_Background_Sync
	 */
	public static function instance() {
		if ( ! self::$instance ) {
			self::$instance = new GeoDir_Booking_Background_Sync();
		}

		return self::$instance;
	}

	/**
	 * Retrieves the current item from options.
	 *
	 * @return mixed The current item
	 */
	public function get_current_item() {
		return $this->options->get_option( 'current_item' );
	}

	/**
	 * Retrieves the calendar name from the source.
	 *
	 * @param string $calendar_uri Link to external calendar.
	 * @return string The calendar name
	 */
	protected function retrieve_calendar_name_from_source( $calendar_uri ) {
		// Placeholder method; can be implemented for fetching calendar names.
		return $calendar_uri;
	}


	/**
	 * Retrieves the calendar content from the source.
	 *
	 * @param string $calendar_uri Link to the external calendar.
	 * @return string The calendar content
	 *
	 * @throws GeoDir_Booking_Execution_Time_Exception
	 * @throws GeoDir_Booking_Request_Exception
	 */
	protected function retrieve_calendar_content_from_source( $calendar_uri ) {
		// Time left until script termination.
		$time_left = $this->time_left();

		// Leave 5 seconds for parsing/batching/logging.
		$timeout = min( $time_left - 5, self::MAX_REQUEST_TIMEOUT );

		if ( $timeout <= 0 ) {
			throw new GeoDir_Booking_Execution_Time_Exception( sprintf( esc_attr__( 'Maximum execution time is set to %d seconds.', 'geodir-booking' ), $timeout ) );
		}

		$request_args = array(
			'timeout'    => $timeout,
			'user-agent' => geodir_booking_site_domain() . '/' . GEODIR_BOOKING_VERSION,
		);

		$request_args = apply_filters( "{$this->identifier}_retrieve_calendar_request_args", $request_args, $calendar_uri );

		$response = wp_remote_get( $calendar_uri, $request_args );

		if ( is_wp_error( $response ) ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			throw new GeoDir_Booking_Request_Exception( $response->get_error_message() );
		}

		$calendar_content = wp_remote_retrieve_body( $response );

		return $calendar_content;
	}

	/**
	 * Task to pull URLs and initiate parsing.
	 *
	 * @param array $task [listing_id, queue_id]
	 * @return mixed
	 */
	protected function task_pull_urls( $task ) {
		// Retrieve URLs for syncing from the database for the given listing ID
		$urls = GeoDir_Booking_Sync_Urls::instance()->get_urls( (int) $task['listing_id'] );

		$count = count( $urls );

		if ( $count > 0 ) {
			// Generate workloads for parsing from the retrieved URLs
			$workloads = array_map(
				function ( $sync_id, $calendar_url ) use ( $task ) {
					return array(
						'listing_id'   => $task['listing_id'],
						'calendar_uri' => $calendar_url,
						'sync_id'      => $sync_id,
						'queue_id'     => $task['queue_id'],
					);
				},
				array_keys( $urls ),
				$urls
			);

			// Add parsing tasks to the queue
			$this->add_parse_tasks( $workloads );

			$message = sprintf( _n( '%d URL pulled for parsing.', '%d URLs pulled for parsing.', $count, 'geodir-booking' ), $count );
		} else {
			$message = sprintf( __( 'Skipped. No URLs found for parsing.', 'geodir-booking' ) );
		}

		$this->logger->info( $message );

		return false;
	}
}
