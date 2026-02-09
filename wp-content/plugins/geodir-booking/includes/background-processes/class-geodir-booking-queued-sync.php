<?php
/**
 * Main queued synchronizer Class.
 *
 * @package GeoDirectory
 * @subpackage Booking
 * @version 1.0.0
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Main queued synchronizer stats class.
 *
 * @package GeoDirectory
 * @subpackage Booking
 * @version 1.0.0
 * @since   1.0.0
 */
class GeoDir_Booking_Queued_Sync {
	/**
	 * The one true instance of GeoDir_Booking_Queued_Sync.
	 *
	 * @var GeoDir_Booking_Queued_Sync
	 */
	private static $instance;

	/**
	 * @var GeoDir_Booking_Background_Sync
	 */
	public $synchronizer;

	/**
	 * @var GeoDir_Booking_Options_Handler
	 */
	public $options;

	/**
	 * @var GeoDir_Booking_Queue
	 */
	public $queue;

	/**
	 * Constructs the queued synchronizer with a background synchronizer instance.
	 *
	 * @param GeoDir_Booking_Background_Sync $synchronizer The background synchronizer instance
	 */
	public function __construct( $synchronizer ) {
		$this->synchronizer = $synchronizer;
		$this->options      = $synchronizer->get_options();
		$this->queue        = new GeoDir_Booking_Queue( $this->options );

		add_action( $synchronizer->get_identifier() . '_complete', array( $this, 'do_next' ) );
	}

	/**
	 * Get the one true instance of GeoDir_Booking_Queued_Sync.
	 *
	 * @since 1.0
	 * @return GeoDir_Booking_Queued_Sync
	 */
	public static function instance() {
		if ( ! self::$instance ) {
			self::$instance = new GeoDir_Booking_Queued_Sync( GeoDir_Booking_Background_Sync::instance() );
		}

		return self::$instance;
	}

	/**
	 * Retrieves the current item.
	 *
	 * @return string The current item
	 */
	public function get_current_item() {
		return $this->options->get_option_no_cache( 'current_item', '' );
	}

	/**
	 * Adds listing IDs to the queue.
	 *
	 * @param array $listing_ids The listing IDs to add to the queue
	 */
	protected function add_to_queue( $listing_ids ) {
		if ( apply_filters( 'geodir_booking_block_sync', false ) ) {
			return;
		}

		$time = time();

		$items = array_map(
			function ( $listing_id ) use ( $time ) {
				return $time . '_' . $listing_id;
			},
			$listing_ids
		);

		$this->queue->add_items( $items );
	}

	/**
	 * Retrieves the timestamp from the queue item.
	 *
	 * @param string $queue_item The queue item in the format "%Timestamp%_%Room ID%"
	 * @return int The timestamp extracted from the queue item
	 */
	public static function retrieve_time_from_item( $queue_item ) {
		return (int) preg_replace( '/^(\d+)_\d+/', '$1', $queue_item );
	}

	/**
	 * Removes an item from the queue.
	 *
	 * @param string $queue_item The queue item to remove
	 */
	public function remove_item( $queue_item ) {
		if ( $this->get_current_item() == $queue_item ) {
			$this->synchronizer->abort();
			$this->synchronizer->touch();
		}

		$this->queue->remove_item( $queue_item );

		$queue_id = GeoDir_Booking_Queue::instance()->find_id( $queue_item );

		GeoDir_Booking_Queue::instance()->delete_item( $queue_item );
		GeoDir_Booking_Stats::instance()->delete_queue( $queue_id );
		GeoDir_Booking_Logger::instance()->delete_queue( $queue_id );
	}

	/**
	 * Aborts an item.
	 *
	 * @param string $queue_item The queue item to abort
	 */
	public function abort_item( $queue_item ) {
		if ( $this->get_current_item() == $queue_item ) {
			$this->synchronizer->abort();
			$this->synchronizer->touch();
		}
	}

	/**
	 * Aborts all items.
	 */
	public function abort_all() {
		$this->options->update_option( 'abort_all', true );

		$this->queue->abort();

		$this->synchronizer->abort();
		$this->synchronizer->touch();
	}

	/**
	 * Clears all items.
	 */
	public function clear_all() {
		$this->abort_all();

		$this->queue->clear();

		GeoDir_Booking_Stats::instance()->delete_sync();
		GeoDir_Booking_Logger::instance()->delete_sync();
		GeoDir_Booking_Queue::instance()->delete_sync();

		if ( ! $this->is_in_progress() ) {
			$this->do_next();
		}
	}

	/**
	 * Checks if the queue is empty.
	 *
	 * @return bool True if the queue is empty, otherwise false
	 */
	public function is_queue_empty() {
		return $this->queue->is_empty();
	}

	/**
	 * Checks if synchronization is in progress.
	 *
	 * @return bool True if synchronization is in progress, otherwise false
	 */
	public function is_in_progress() {
		$current_item = $this->get_current_item();

		return ( ! empty( $current_item ) || ! $this->queue->is_finished() );
	}

	/**
	 * Checks if the synchronization is aborting.
	 *
	 * @return bool True if synchronization is aborting, otherwise false
	 */
	protected function is_aborting() {
		return $this->options->get_option_no_cache( 'abort_all' );
	}

	/**
	 * Initiates synchronization for the given listing IDs.
	 *
	 * @param array $listing_ids The listing IDs to synchronize
	 */
	public function sync( $listing_ids ) {
		GeoDir_Booking_Logger::instance()->delete_ghosts();

		$listings_to_sync = array_intersect( $listing_ids, GeoDir_Booking_Sync_Urls::instance()->get_all_listing_ids() );

		$listings_to_sync = array_diff( $listings_to_sync, $this->queue->get_queued_listing_ids() );

		if ( ! empty( $listings_to_sync ) ) {
			$this->add_to_queue( array_values( $listings_to_sync ) );
			$this->do_next();
		} else {
			$this->synchronizer->touch();
		}
	}

	/**
	 * Processes the next item in the queue.
	 */
	public function do_next() {
		if ( $this->synchronizer->is_in_progress() ) {
			$this->synchronizer->touch();
			return;
		}

		if ( $this->is_aborting() ) {
			$this->options->delete_option( 'abort_all' );
			$this->options->update_option( 'current_item', '' );

			$this->synchronizer->reset();
		} else {
			$next_item = $this->queue->next();

			$this->queue->remove_item( $this->get_current_item() );
			$this->options->update_option( 'current_item', $next_item );

			$this->synchronizer->reset();

			if ( $next_item ) {
				$this->synchronizer->add_pull_url_task(
					array(
						'listing_id' => geodir_booking_parse_queue_listing_id( $next_item ),
					)
				);
			}
		}
	}
}
