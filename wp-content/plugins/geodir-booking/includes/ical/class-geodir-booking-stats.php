<?php
/**
 * Main booking stats Class.
 *
 * @package GeoDirectory
 * @subpackage Booking
 * @version 1.0.0
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Main booking stats class.
 *
 * @package GeoDirectory
 * @subpackage Booking
 * @version 1.0.0
 * @since   1.0.0
 */
class GeoDir_Booking_Stats {
	/**
	 * The one true instance of GeoDir_Booking_Stats.
	 *
	 * @var GeoDir_Booking_Stats
	 */
	private static $instance;

	/**
	 * @var int
	 */
	protected $queue_id = 0;

	/**
	 * @var string Table name.
	 */
	public $gdbc_sync_stats;

	/**
	 * Initializes a new instance of the Stats class with an optional queue ID.
	 *
	 * @param int $queue_id Optional. The queue ID.
	 */
	public function __construct( $queue_id = 0 ) {
		global $wpdb;

		$this->set_queue_id( $queue_id );

		$this->gdbc_sync_stats = $wpdb->prefix . 'gdbc_sync_stats';
	}

	/**
	 * Get the one true instance of GeoDir_Booking_Stats.
	 *
	 * @since 1.0
	 * @return GeoDir_Booking_Stats
	 */
	public static function instance() {
		if ( ! self::$instance ) {
			self::$instance = new GeoDir_Booking_Stats();
		}

		return self::$instance;
	}

	/**
	 * Sets the queue ID for the current instance of the Stats class.
	 *
	 * @param int $queue_id The queue ID.
	 */
	public function set_queue_id( $queue_id ) {
		$this->queue_id = intval( $queue_id );
	}

	/**
	 * Retrieves the queue ID for the current instance of the Stats class.
	 *
	 * @return int The queue ID.
	 */
	public function get_queue_id() {
		return $this->queue_id;
	}

	/**
	 * Increases the total count of imports by the specified increment.
	 *
	 * @param int $increment The amount by which to increase the total imports count.
	 */
	public function increase_imports_total( $increment ) {
		$this->increase_field( 'import_total', $increment );
	}

	/**
	 * Increases the count of successful imports by the specified increment.
	 *
	 * @param int $increment The amount by which to increase the successful imports count.
	 */
	public function increase_succeed_imports( $increment ) {
		$this->increase_field( 'import_succeed', $increment );
	}

	/**
	 * Increases the count of skipped imports by the specified increment.
	 *
	 * @param int $increment The amount by which to increase the skipped imports count.
	 */
	public function increase_skipped_imports( $increment ) {
		$this->increase_field( 'import_skipped', $increment );
	}

	/**
	 * Increases the count of failed imports by the specified increment.
	 *
	 * @param int $increment The amount by which to increase the failed imports count.
	 */
	public function increase_failed_imports( $increment ) {
		$this->increase_field( 'import_failed', $increment );
	}

	/**
	 * Increases the total count of cleans by the specified increment.
	 *
	 * @param int $increment The amount by which to increase the total cleans count.
	 */
	public function increase_cleans_total( $increment ) {
		$this->increase_field( 'clean_total', $increment );
	}

	/**
	 * Increases the count of completed cleans by the specified increment.
	 *
	 * @param int $increment The amount by which to increase the completed cleans count.
	 */
	public function increase_done_cleans( $increment ) {
		$this->increase_field( 'clean_done', $increment );
	}

	/**
	 * Increases the count of skipped cleans by the specified increment.
	 *
	 * @param int $increment The amount by which to increase the skipped cleans count.
	 */
	public function increase_skipped_cleans( $increment ) {
		$this->increase_field( 'clean_skipped', $increment );
	}

	/**
	 * Increases the value of a specific field by the specified increment in the database.
	 *
	 * @param string $field The name of the field to increase.
	 * @param int    $increment The amount by which to increase the field's value.
	 */
	protected function increase_field( $field, $increment ) {
		global $wpdb;

		if ( empty( $this->queue_id ) ) {
			return;
		}

		$query = $wpdb->prepare(
			"UPDATE {$this->gdbc_sync_stats} SET {$field} = {$field} + %d WHERE queue_id = %d",
			$increment,
			$this->queue_id
		);

		$wpdb->query( $query );
	}

	/**
	 * Retrieves the statistics for the current queue ID.
	 *
	 * @return array An array containing statistics (total, succeed, skipped, failed, removed).
	 */
	public function get_stats() {
		global $wpdb;

		$query = $wpdb->prepare(
			"SELECT import_total, import_succeed, import_skipped, import_failed, clean_total, clean_done, clean_skipped FROM {$this->gdbc_sync_stats} WHERE queue_id = %d",
			$this->queue_id
		);

		$row = $wpdb->get_row( $query, ARRAY_A );

		if ( ! is_null( $row ) ) {
			return array(
				'total'   => $row['import_total'] + $row['clean_total'],
				'succeed' => $row['import_succeed'],
				'skipped' => $row['import_skipped'] + $row['clean_skipped'],
				'failed'  => $row['import_failed'],
				'removed' => $row['clean_done'],
			);
		} else {
			return self::empty_stats();
		}
	}

	/**
	 * Returns an array representing empty statistics, with all counts initialized to 0.
	 *
	 * @return array An array containing empty statistics.
	 */
	public function empty_stats() {
		return array(
			'total'   => 0,
			'succeed' => 0,
			'skipped' => 0,
			'failed'  => 0,
			'removed' => 0,
		);
	}

	/**
	 * Retrieves statistics for multiple queue IDs provided in the array $queue_ids.
	 *
	 * @param array $queue_ids An array containing queue IDs.
	 * @return array An array containing statistics for each queue ID.
	 */
	public function select_stats( $queue_ids ) {
		global $wpdb;
		if ( empty( $queue_ids ) ) {
			return array();
		}

		$query = 'SELECT queue_id, import_total, import_succeed, import_skipped, import_failed, clean_total, clean_done, clean_skipped 
            FROM ' . $this->gdbc_sync_stats . ' 
            WHERE queue_id IN (' . implode( ', ', $queue_ids ) . ')';

		$rows  = $wpdb->get_results( $query, ARRAY_A );
		$stats = array();
		foreach ( $rows as $row ) {
			$id = (int) $row['queue_id'];
			unset( $row['queue_id'] );
			$row          = array_map( 'absint', $row );
			$stats[ $id ] = array(
				'total'   => $row['import_total'] + $row['clean_total'],
				'succeed' => $row['import_succeed'],
				'skipped' => $row['import_skipped'] + $row['clean_skipped'],
				'failed'  => $row['import_failed'],
				'removed' => $row['clean_done'],
			);
		}
		$results = array();
		foreach ( $queue_ids as $queue_id ) {
			if ( isset( $stats[ $queue_id ] ) ) {
				$results[ $queue_id ] = $stats[ $queue_id ];
			} else {
				$results[ $queue_id ] = self::empty_stats();
			}
		}
		return $results;
	}

	/**
	 * Resets the statistics for a specific queue ID, setting all counts to 0.
	 *
	 * @param int $queue_id The queue ID.
	 */
	public function reset_stats( $queue_id ) {
		global $wpdb;

		$item_exists = (bool) $wpdb->get_var( $wpdb->prepare( 'SELECT stat_id FROM ' . $this->gdbc_sync_stats . ' WHERE queue_id = %d', $queue_id ) );

		$values  = array(
			'queue_id'       => $queue_id,
			'import_total'   => 0,
			'import_succeed' => 0,
			'import_skipped' => 0,
			'import_failed'  => 0,
			'clean_total'    => 0,
			'clean_done'     => 0,
			'clean_skipped'  => 0,
		);
		$formats = '%d';
		$where   = array( 'queue_id' => $queue_id );

		if ( $item_exists ) {
			$wpdb->update( $this->gdbc_sync_stats, $values, $where, $formats );
		} else {
			$wpdb->insert( $this->gdbc_sync_stats, $values, $formats );
		}
	}

	/**
	 * Deletes the statistics for a specific queue ID from the database.
	 *
	 * @param int $queue_id The queue ID.
	 */
	public function delete_queue( $queue_id ) {
		global $wpdb;

		$query = $wpdb->prepare( 'DELETE FROM ' . $this->gdbc_sync_stats . ' WHERE queue_id = %d', $queue_id );
		$wpdb->query( $query );
	}

	/**
	 * Deletes the statistics for multiple queue IDs provided in the array $queue_ids from the database.
	 *
	 * @param array $queue_ids An array containing queue IDs.
	 */
	public function delete_queues( $queue_ids ) {
		global $wpdb;

		$query = 'DELETE FROM ' . $this->gdbc_sync_stats . ' WHERE queue_id IN (' . implode( ', ', $queue_ids ) . ')';
		$wpdb->query( $query );
	}

	/**
	 * Deletes synchronization statistics entries for non-automatic 'auto' queue items, ensuring that automatic synchronization statistics are preserved.
	 */
	public function delete_sync() {
		global $wpdb;

		$gdbc_sync_stats = $this->gdbc_sync_stats;
		$gdbc_sync_queue = GeoDir_Booking_Queue::instance()->gdbc_sync_queue;

		$query = $wpdb->prepare(
			"DELETE stats FROM {$gdbc_sync_stats} AS stats 
            INNER JOIN {$gdbc_sync_queue} AS queue ON stats.queue_id = queue.queue_id
            WHERE queue.queue_status != %s",
			GeoDir_Booking_Queue::STATUS_AUTO
		);

		$wpdb->query( $query );
	}
}
