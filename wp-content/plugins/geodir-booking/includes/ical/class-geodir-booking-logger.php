<?php
/**
 * Main logger Class.
 *
 * @package GeoDirectory
 * @subpackage Booking
 * @version 1.0.0
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Handles logging for GeoDirectory bookings.
 *
 * @package GeoDirectory
 * @subpackage Booking
 * @since   1.0.0
 */
class GeoDir_Booking_Logger {
	/**
	 * The single instance of GeoDir_Booking_Logger.
	 *
	 * @var GeoDir_Booking_Logger|null
	 */
	private static $instance = null;

	/**
	 * The queue ID.
	 *
	 * @var int
	 */
	protected $queue_id = 0;

	/**
	 * Table name for logs.
	 *
	 * @var string
	 */
	public $gdbc_sync_logs;

	/**
	 * GeoDir_Booking_Logger constructor.
	 *
	 * @param int $queue_id Optional. The queue ID.
	 */
	public function __construct( $queue_id = 0 ) {
		global $wpdb;

		$this->set_queue_id( $queue_id );

		$this->gdbc_sync_logs = $wpdb->prefix . 'gdbc_sync_logs';
	}

	/**
	 * Retrieves the single instance of GeoDir_Booking_Logger.
	 *
	 * @return GeoDir_Booking_Logger
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Sets the queue ID.
	 *
	 * @param int $queue_id The queue ID.
	 */
	public function set_queue_id( $queue_id ) {
		$this->queue_id = intval( $queue_id );
	}

	/**
	 * Retrieves the queue ID.
	 *
	 * @return int The queue ID.
	 */
	public function get_queue_id() {
		return $this->queue_id;
	}

	/**
	 * Logs a success message.
	 *
	 * @param string $message The success message to log.
	 */
	public function success( $message ) {
		$this->log( 'success', $message );
	}

	/**
	 * Logs an informational message.
	 *
	 * @param string $message The informational message to log.
	 */
	public function info( $message ) {
		$this->log( 'info', $message );
	}

	/**
	 * Logs a warning message.
	 *
	 * @param string $message The warning message to log.
	 */
	public function warning( $message ) {
		$this->log( 'warning', $message );
	}

	/**
	 * Logs an error message.
	 *
	 * @param string $message The error message to log.
	 */
	public function error( $message ) {
		$this->log( 'error', $message );
	}

	/**
	 * Logs a message.
	 *
	 * @param string $status  The status of the log message (success, info, warning, error).
	 * @param string $message The message content to log.
	 */
	public function log( $status, $message ) {
		global $wpdb;

		if ( empty( $this->queue_id ) ) {
			return;
		}

		$wpdb->insert(
			$this->gdbc_sync_logs,
			array(
				'queue_id'    => $this->queue_id,
				'log_status'  => $status,
				'log_message' => $message,
			)
		);
	}

	/**
	 * Retrieves logs associated with the current queue ID, optionally skipping a specified number of records.
	 *
	 * @param int $skip_count Optional. The number of records to skip.
	 * @return array An array of logs.
	 */
	public function get_logs( $skip_count = 0 ) {
		return $this->select_logs( $this->queue_id, $skip_count, 400000000 );
	}

	/**
	 * Clears all logs associated with the current queue ID.
	 */
	public function clear() {
		if ( ! empty( $this->queue_id ) ) {
			$this->delete_queue( $this->queue_id );
		}
	}

	/**
	 * Retrieves logs associated with a specified queue ID, with optional offset and limit parameters.
	 *
	 * @param int $queue_id The queue ID.
	 * @param int $offset   Optional. The offset from which to retrieve logs.
	 * @param int $limit    Optional. The maximum number of logs to retrieve.
	 * @return array An array of logs.
	 */
	public function select_logs( $queue_id, $offset, $limit ) {
		global $wpdb;

		$query = $wpdb->prepare(
			"SELECT log_status, log_message 
			FROM {$this->gdbc_sync_logs}
			WHERE queue_id = %d
			LIMIT %d, %d",
			$queue_id,
			$offset,
			$limit
		);

		$rows = $wpdb->get_results( $query, ARRAY_A );

		$logs = array_map(
			function ( $row ) {
				return array(
					'status'  => $row['log_status'],
					'message' => ! empty( $row['log_message'] ) ? $row['log_message'] : '',
				);
			},
			$rows
		);

		return $logs;
	}

	/**
	 * Counts the number of logs associated with a specified queue ID.
	 *
	 * @param int $queue_id The queue ID.
	 * @return int The number of logs.
	 */
	public function count_logs( $queue_id ) {
		global $wpdb;

		$query = $wpdb->prepare(
			"SELECT COUNT(*) 
			FROM {$this->gdbc_sync_logs} 
			WHERE queue_id = %d",
			$queue_id
		);

		return $wpdb->get_var( $query );
	}

	/**
	 * Deletes logs associated with a specified queue ID from the database.
	 *
	 * @param int $queue_id The queue ID.
	 */
	public function delete_queue( $queue_id ) {
		global $wpdb;

		$query = $wpdb->prepare(
			"DELETE FROM {$this->gdbc_sync_logs} WHERE queue_id = %d",
			$queue_id
		);

		$wpdb->query( $query );
	}

	/**
	 * Deletes logs associated with multiple queue IDs provided in the array $queue_ids from the database.
	 *
	 * @param array $queue_ids An array containing queue IDs.
	 */
	public function delete_queues( $queue_ids ) {
		global $wpdb;

		$placeholders = implode( ', ', array_fill( 0, count( $queue_ids ), '%d' ) );

		$query = $wpdb->prepare(
			"DELETE FROM {$this->gdbc_sync_logs} WHERE queue_id IN ({$placeholders})",
			$queue_ids
		);

		$wpdb->query( $query );
	}

	/**
	 * Deletes synchronization logs where the queue status is not 'auto', preserving automatic synchronization logs.
	 */
	public function delete_sync() {
		global $wpdb;

		$gdbc_sync_queue = GeoDir_Booking_Queue::instance()->gdbc_sync_queue;

		$query = $wpdb->prepare(
			"DELETE logs FROM {$this->gdbc_sync_logs} AS logs 
			INNER JOIN {$gdbc_sync_queue} AS queue ON logs.queue_id = queue.queue_id
			WHERE queue.queue_status != %s",
			GeoDir_Booking_Queue::STATUS_AUTO
		);

		$wpdb->query( $query );
	}

	/**
	 * Deletes log messages that were added by background processes before being aborted.
	 *
	 * @return int The number of rows affected by the delete operation.
	 */
	public function delete_ghosts() {
		global $wpdb;

		$gdbc_sync_queue = GeoDir_Booking_Queue::instance()->gdbc_sync_queue;

		$query = "DELETE logs FROM {$this->gdbc_sync_logs} AS logs
            LEFT JOIN {$gdbc_sync_queue} AS queue ON logs.queue_id = queue.queue_id
            WHERE queue.queue_id IS NULL";

		return $wpdb->query( $query );
	}
}
