<?php
/**
 * Main booking queue Class.
 *
 * @package GeoDirectory
 * @subpackage Booking
 * @version 1.0.0
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Main booking queue class.
 *
 * @package GeoDirectory
 * @subpackage Booking
 * @version 1.0.0
 * @since   1.0.0
 */
class GeoDir_Booking_Queue {
	/**
	 * The one true instance of GeoDir_Booking_Queue.
	 *
	 * @var GeoDir_Booking_Queue
	 */
	private static $instance;

	/**
	 * @var GeoDir_Booking_Options_Handler
	 */
	protected $options;

	const STATUS_WAIT        = 'wait';
	const STATUS_IN_PROGRESS = 'in-progress';
	const STATUS_DONE        = 'done';
	const STATUS_AUTO        = 'auto';

	/**
	 * @var string Table name.
	 */
	public $gdbc_sync_queue;

	/**
	 * @param GeoDir_Booking_Options_Handler $options
	 */
	public function __construct( $options ) {
		global $wpdb;

		$this->options         = $options;
		$this->gdbc_sync_queue = $wpdb->prefix . 'gdbc_sync_queue';
	}

	/**
	 * Get the one true instance of GeoDir_Booking_Queue.
	 *
	 * @since 1.0
	 * @return GeoDir_Booking_Queue
	 */
	public static function instance( $options = null ) {
		if ( ! self::$instance ) {
			self::$instance = new GeoDir_Booking_Queue( $options );
		}

		return self::$instance;
	}

	/**
	 * Retrieve the next item from the queue.
	 *
	 * @return mixed The next item from the queue.
	 */
	public function get_next_item() {
		return $this->options->get_option( 'next_item', '' );
	}

	/**
	 * Retrieve the queue.
	 *
	 * @return array The queue.
	 */
	public function get_queue() {
		return $this->options->get_option( 'queue', array() );
	}

	/**
	 * Check if the queue is empty.
	 *
	 * @return bool True if the queue is empty, false otherwise.
	 */
	public function is_empty() {
		$queue = $this->get_queue();
		return empty( $queue );
	}

	/**
	 * Check if the queue is finished.
	 *
	 * @return bool True if the queue is finished, false otherwise.
	 */
	public function is_finished() {
		$next_item = $this->get_next_item();
		return empty( $next_item );
	}

	/**
	 * Set the next item in the queue.
	 *
	 * @param mixed $item The item to set as the next item in the queue.
	 * @return void
	 */
	public function set_next_item( $item ) {
		$this->options->update_option( 'next_item', $item );
	}

	/**
	 * Set the queue.
	 *
	 * @param array $queue The queue to set.
	 * @return void
	 */
	public function set_queue( $queue ) {
		$this->options->update_option( 'queue', $queue );
	}

	/**
	 * Add items to the queue.
	 *
	 * @param array $items The items to add to the queue.
	 * @return void
	 */
	public function add_items( $items ) {
		// Insert new items into database.
		$this->insert_items( $items );

		// Add new items to queue.
		$queue = $this->get_queue();

		if ( empty( $queue ) || $this->is_finished() ) {
			// Start new queue.
			$queue = $items;
		} else {
			// Add more items to current queue.
			$queue = array_merge( $queue, $items );
		}

		// Save new queue.
		$this->set_queue( $queue );

		// Update next item.
		$next_item = $this->get_next_item();

		if ( empty( $next_item ) ) {
			$next_item = reset( $items );

			$this->set_next_item( $next_item );
		}
	}

	/**
	 * Insert items into the database queue.
	 *
	 * @param array $items The items to insert into the database queue.
	 * @return void
	 */
	protected function insert_items( $items ) {
		global $wpdb;

		// Reverse the order to change progress sequence.
		$items = array_reverse( $items );

		// Prepare values for INSERT INTO query (queue name and its status).
		$values = array_map(
			function ( $item ) {
				return "('" . esc_sql( $item ) . "', '" . self::STATUS_WAIT . "')";
			},
			$items
		);

		$query = 'INSERT INTO ' . $this->gdbc_sync_queue . ' (queue_name, queue_status) VALUES ' . implode( ', ', $values );

		$wpdb->query( $query );
	}

	/**
	 * Retrieve an array of listing IDs that are currently queued for processing.
	 *
	 * @return int[] An array of listing IDs.
	 *
	 * @global \wpdb $wpdb
	 *
	 */
	public function get_queued_listing_ids() {
		global $wpdb;

		$queue_items = $wpdb->get_col(
			$wpdb->prepare(
				'SELECT queue_name FROM ' . $this->gdbc_sync_queue . ' WHERE queue_status = %s OR queue_status = %s',
				self::STATUS_IN_PROGRESS,
				self::STATUS_WAIT
			)
		);

		$listing_ids = array_map( 'geodir_booking_parse_queue_listing_id', $queue_items );

		return $listing_ids;
	}

	/**
	 * Proceed to the next item in the queue.
	 *
	 * This method advances to the next item in the queue and updates its status.
	 *
	 * @return string The key of the next room if it exists, otherwise an empty string.
	 */
	public function next() {
		$next_item = $this->get_next_item();

		$queue      = $this->get_queue();
		$item_index = array_search( $next_item, $queue );

		if ( $item_index === false ) {
			$item_index = count( $queue ); // The queue is broken, skip all items.
		}

		$old_value = $next_item;
		$new_index = $item_index + 1;
		$new_value = isset( $queue[ $new_index ] ) ? $queue[ $new_index ] : '';

		$this->set_next_item( $new_value );

		// The previous is done.
		$this->previous_is_done();

		// New item is in progress.
		$this->update_status( $old_value, self::STATUS_IN_PROGRESS );

		return $old_value;
	}

	/**
	 * Update the status of a queue item.
	 *
	 * This method updates the status of a queue item in the database.
	 *
	 * @param string $item   The queue item to update.
	 * @param string $status The new status of the queue item.
	 * @return void
	 */
	protected function update_status( $item, $status ) {
		global $wpdb;

		$query = $wpdb->prepare(
			'UPDATE ' . $this->gdbc_sync_queue . '
            SET queue_status = %s
            WHERE queue_name = %s',
			$status,
			$item
		);

		$wpdb->query( $query );
	}

	/**
	 * Mark the previous item in the queue as done.
	 *
	 * This method updates the status of the previous item in the queue to "done".
	 *
	 * @return void
	 */
	protected function previous_is_done() {
		global $wpdb;

		$query = $wpdb->prepare(
			'UPDATE ' . $this->gdbc_sync_queue . ' 
            SET queue_status = %s 
            WHERE queue_status = %s',
			self::STATUS_DONE,
			self::STATUS_IN_PROGRESS
		);

		$wpdb->query( $query );
	}

	/**
	 * Mark all items in the queue as done.
	 *
	 * This method updates the status of all items in the queue to "done".
	 *
	 * @return void
	 */
	protected function all_is_done() {
		global $wpdb;

		$query = $wpdb->prepare(
			'UPDATE ' . $this->gdbc_sync_queue . ' 
            SET queue_status = %s
            WHERE queue_status = %s OR queue_status = %s',
			self::STATUS_DONE,
			self::STATUS_WAIT,
			self::STATUS_IN_PROGRESS
		);

		$wpdb->query( $query );
	}

	/**
	 * Remove an item from the queue.
	 *
	 * This method removes an item from the queue without deleting it from the database.
	 *
	 * @param mixed $item The item to remove from the queue.
	 * @return void
	 */
	public function remove_item( $item ) {
		$queue      = $this->get_queue();
		$item_index = array_search( $item, $queue );

		if ( $item_index === false ) {
			return;
		}

		// Remove item from queue.
		if ( $item_index === 0 ) {
			array_shift( $queue );
		} else {
			array_splice( $queue, $item_index, 1 );
		}

		// Update next item, if required.
		if ( $this->get_next_item() == $item ) {
			$this->next();
		}

		// Update queue array only after method next().
		$this->set_queue( $queue );
	}

	/**
	 * Abort processing the queue.
	 *
	 * This method resets the next item and marks all items in the queue as done.
	 *
	 * @return void
	 */
	public function abort() {
		$this->set_next_item( '' );
		$this->all_is_done();
	}

	/**
	 * Clear the queue.
	 *
	 * This method aborts processing and clears all items from the queue.
	 *
	 * @return void
	 */
	public function clear() {
		$this->abort();
		$this->set_queue( array() );
	}

	/**
	 * Manually create an uploader item in the queue.
	 *
	 * This method inserts an item into the queue with a status of "auto" if it doesn't already exist.
	 *
	 * @param string $item The item to create in the queue.
	 * @return void
	 */
	public function create_uploader_item( $item ) {
		global $wpdb;

		$item_exists = (bool) self::find_id( $item );

		if ( ! $item_exists ) {
			$wpdb->insert(
				$this->gdbc_sync_queue,
				array(
					'queue_name'   => $item,
					'queue_status' => self::STATUS_AUTO,
				)
			);
		}
	}

	/**
	 * Find the ID of a queue item.
	 *
	 * This method retrieves the ID of a queue item from the database.
	 *
	 * @param string $item The name of the queue item.
	 * @return int The ID of the queue item, or 0 if not found.
	 */
	public function find_id( $item ) {
		global $wpdb;

		$query = $wpdb->prepare(
			'SELECT queue_id FROM ' . $this->gdbc_sync_queue . ' WHERE queue_name = %s',
			$item
		);

		$queue_id = $wpdb->get_var( $query );

		return ! is_null( $queue_id ) ? $queue_id : 0;
	}

	/**
	 * Select specific items from the queue.
	 *
	 * This method retrieves specific items from the queue based on their names.
	 *
	 * @param array $items An array of item names to retrieve.
	 * @return array An associative array of queue IDs and their corresponding names and statuses.
	 */
	public function select_items( $items ) {
		global $wpdb;

		if ( empty( $items ) ) {
			return array();
		}

		$items = esc_sql( $items );
		$items = array_map(
			function ( $item ) {
				return "'" . $item . "'";
			},
			$items
		);

		$query = 'SELECT * FROM ' . $this->gdbc_sync_queue . ' WHERE queue_name IN (' . implode( ', ', $items ) . ')';
		$rows  = $wpdb->get_results( $query, ARRAY_A );

		$items = array();
		foreach ( $rows as $row ) {
			$id = (int) $row['queue_id'];

			$items[ $id ] = array(
				'queue'  => $row['queue_name'],
				'status' => $row['queue_status'],
			);
		}

		return $items;
	}

	/**
	 * Select items from the queue for pagination.
	 *
	 * This method retrieves a subset of items from the queue for pagination purposes.
	 *
	 * @param int $offset The offset for pagination.
	 * @param int $limit  The maximum number of items to retrieve.
	 * @return array An associative array of queue IDs and their corresponding names and statuses.
	 */
	public function select_items_page( $offset, $limit ) {
		global $wpdb;

		$query = $wpdb->prepare(
			'SELECT * FROM ' . $this->gdbc_sync_queue . ' 
            WHERE queue_status != %s 
            ORDER BY queue_id DESC
            LIMIT %d, %d',
			self::STATUS_AUTO,
			$offset,
			$limit
		);

		$rows = $wpdb->get_results( $query, ARRAY_A );

		$items = array();

		foreach ( $rows as $row ) {
			$id = (int) $row['queue_id'];

			$items[ $id ] = array(
				'queue'  => $row['queue_name'],
				'status' => $row['queue_status'],
			);
		}

		return $items;
	}

	/**
	 * Count the number of items in the queue.
	 *
	 * This method counts the total number of items in the queue.
	 *
	 * @return int The total number of items in the queue.
	 */
	public function count_items() {
		global $wpdb;

		$query = $wpdb->prepare(
			'SELECT COUNT(*) FROM ' . $this->gdbc_sync_queue . '  WHERE queue_status != %s',
			self::STATUS_AUTO
		);

		return $wpdb->get_var( $query );
	}

	/**
	 * Delete a specific item from the queue.
	 *
	 * This method deletes a specific item from the queue.
	 *
	 * @param string $item The name of the item to delete.
	 * @return void
	 */
	public function delete_item( $item ) {
		global $wpdb;

		$query = $wpdb->prepare(
			'DELETE FROM ' . $this->gdbc_sync_queue . ' WHERE queue_name = %s',
			$item
		);

		$wpdb->query( $query );
	}

	/**
	 * Delete multiple items from the queue by their IDs.
	 *
	 * This method deletes multiple items from the queue based on their IDs.
	 *
	 * @param array $item_ids An array of item IDs to delete.
	 * @return void
	 */
	public function delete_items_by_ids( $item_ids ) {
		global $wpdb;

		$query = 'DELETE FROM ' . $this->gdbc_sync_queue . ' WHERE queue_id IN (' . implode( ', ', $item_ids ) . ')';

		$wpdb->query( $query );
	}

	/**
	 * Delete all items from the queue except "auto" items.
	 *
	 * This method deletes all items from the queue except those with a status of "auto".
	 *
	 * @return void
	 */
	public function delete_sync() {
		global $wpdb;

		$query = $wpdb->prepare(
			'DELETE FROM ' . $this->gdbc_sync_queue . ' WHERE queue_status != %s',
			self::STATUS_AUTO
		);

		$wpdb->query( $query );
	}
}
