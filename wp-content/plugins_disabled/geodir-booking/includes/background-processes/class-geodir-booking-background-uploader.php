<?php
/**
 * Main background uploader Class.
 *
 * @package GeoDirectory
 * @subpackage Booking
 * @version 1.0.0
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Main class for background uploading of calendar events.
 *
 * @package GeoDirectory
 * @subpackage Booking
 * @version 1.0.0
 * @since   1.0.0
 */
class GeoDir_Booking_Background_Uploader extends GeoDir_Booking_Background_Worker {
	/**
	 * The one true instance of GeoDir_Booking_Background_Uploader.
	 *
	 * @var GeoDir_Booking_Background_Uploader
	 */
	private static $instance;

	protected $action = 'upload';

	/**
	 * Retrieves the one true instance of GeoDir_Booking_Background_Uploader.
	 *
	 * @since 1.0
	 * @return GeoDir_Booking_Background_Uploader
	 */
	public static function instance() {
		if ( ! self::$instance ) {
			self::$instance = new GeoDir_Booking_Background_Uploader();
		}

		return self::$instance;
	}

	/**
	 * Retrieves the current item.
	 *
	 * @return string The current item
	 */
	public function get_current_item() {
		return apply_filters( 'geodir_booking_background_uploader_current_item', 'upload' );
	}

	/**
	 * Resets before a new start and clears the stats on finish.
	 */
	public function reset() {
		$queue_item = $this->get_current_item();
		GeoDir_Booking_Queue::instance()->create_uploader_item( $queue_item );

		parent::reset();

		$this->logger->clear();
	}

	/**
	 * Parses new events immediately and adds new "import" tasks.
	 *
	 * @param int    $listing_id The ID of the room
	 * @param string $calendar_uri The URI of the calendar
	 */
	public function parse_calendar( $listing_id, $calendar_uri ) {
		$calendar_name = $this->retrieve_calendar_name_from_source( $calendar_uri );

		$this->task_parse(
			array(
				'listing_id'   => $listing_id,
				'calendar_uri' => $calendar_uri,
				'sync_id'      => md5( $calendar_name ),
				'queue_id'     => $this->stats->get_queue_id(),
			)
		);
	}

	/**
	 * Retrieves the calendar name from the source.
	 *
	 * @param string $calendar_uri The URI of the calendar
	 * @return string The calendar name
	 */
	protected function retrieve_calendar_name_from_source( $calendar_uri ) {
		if ( isset( $_FILES['import'] ) && isset( $_FILES['import']['name'] ) ) {
			return sanitize_text_field( wp_unslash( $_FILES['import']['name'] ) );
		} else {
			return $calendar_uri;
		}
	}

	/**
	 * Retrieves the calendar content from the source.
	 *
	 * @param string $calendar_uri The URI of the calendar
	 * @return string The calendar content
	 */
	protected function retrieve_calendar_content_from_source( $calendar_uri ) {
		$calendar_content = @file_get_contents( $calendar_uri );
		if ( $calendar_content === false ) {
			$this->logger->error( __( 'Cannot read uploaded file', 'geodir-booking' ) );
			return '';
		} else {
			return $calendar_content;
		}
	}

	/**
	 * Pulls URLs. Not needed for the uploader.
	 *
	 * @param array $task The task to pull URLs
	 * @return bool Always returns false
	 */
	protected function task_pull_urls( $task ) {
		return false;
	}

	/**
	 * Retrieves the details including logs and statistics.
	 *
	 * @param int $skip_logs Optional. How many logs to skip. Defaults to 0.
	 * @return array An array containing logs and stats
	 */
	public function get_details( $skip_logs = 0 ) {
		return array(
			'logs'  => $this->logger->get_logs( $skip_logs ),
			'stats' => $this->stats->get_stats(),
		);
	}
}
