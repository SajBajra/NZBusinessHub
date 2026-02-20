<?php
/**
 * Main sync urls Class.
 *
 * @package GeoDirectory
 * @subpackage Booking
 * @version 1.0.0
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Manages synchronization URLs for GeoDirectory bookings.
 *
 * @package GeoDirectory
 * @subpackage Booking
 * @since 1.0.0
 */
class GeoDir_Booking_Sync_Urls {
	/**
	 * The one true instance of GeoDir_Booking_Sync_Urls.
	 *
	 * @var GeoDir_Booking_Sync_Urls
	 */
	private static $instance;

	/**
	 * The name of the database table storing sync URLs.
	 *
	 * @var string
	 */
	protected $table_name;

	/**
	 * Sets up the table name by prefixing it with the WordPress database prefix.
	 */
	public function __construct() {
		global $wpdb;
		$this->table_name = $wpdb->prefix . 'gdbc_sync_urls';
	}

	/**
	 * Get the one true instance of GeoDir_Booking_Sync_Urls.
	 *
	 * @since 1.0
	 * @return GeoDir_Booking_Sync_Urls
	 */
	public static function instance() {
		if ( ! self::$instance ) {
			self::$instance = new GeoDir_Booking_Sync_Urls();
		}

		return self::$instance;
	}

	/**
	 * Prepares the URLs for insertion into the database.
	 *
	 * @param array $urls An array of URLs to be prepared.
	 * @return array An array of prepared URLs with their corresponding sync IDs.
	 */
	protected function prepare_urls( array $urls ) {
		$prepared_urls = array();
		foreach ( $urls as $url ) {
			$sync_id                   = md5( $url );
			$prepared_urls[ $sync_id ] = $url;
		}
		return $prepared_urls;
	}

	/**
	 * Inserts sync URLs into the database.
	 *
	 * @param int $listing_id The ID of the listing associated with the URLs.
	 * @param array $urls An array of URLs to be inserted.
	 */
	public function insert_urls( int $listing_id, array $urls ) {
		global $wpdb;
		if ( empty( $urls ) ) {
			return;
		}
		$prepared_urls = $this->prepare_urls( $urls );

		$values = array();
		foreach ( $prepared_urls as $sync_id => $url ) {
			$values[] = $wpdb->prepare( '(%d, %s, %s)', $listing_id, $sync_id, $url );
		}

		$sql = "INSERT INTO {$this->table_name} (listing_id, sync_id, calendar_url) VALUES " . implode( ', ', $values );
		$wpdb->query( $sql );
	}

	/**
	 * Retrieves all listing IDs associated with sync URLs.
	 *
	 * @return array An array of listing IDs.
	 */
	public function get_all_listing_ids() {
		global $wpdb;

		$listing_ids = $wpdb->get_col( "SELECT DISTINCT listing_id FROM {$this->table_name}" );

		return array_map( 'absint', $listing_ids );
	}

	/**
	 * Retrieves sync URLs associated with a specific listing ID.
	 *
	 * @param int $listing_id The ID of the listing.
	 * @return array An array of sync URLs.
	 */
	public function get_urls( int $listing_id ) {
		global $wpdb;

		$sql  = $wpdb->prepare( "SELECT sync_id, calendar_url FROM {$this->table_name} WHERE listing_id = %d", $listing_id );
		$rows = $wpdb->get_results( $sql, ARRAY_A );

		$urls = array();
		foreach ( $rows as $row ) {
			$urls[ $row['sync_id'] ] = $row['calendar_url'];
		}

		return $urls;
	}

	/**
	 * Retrieves duplicate sync URLs associated with a specific listing ID.
	 *
	 * @param int $listing_id The ID of the listing.
	 * @return array An array of duplicate sync URLs.
	 */
	public function get_duplicating_urls( int $listing_id ) {
		global $wpdb;

		$sql = $wpdb->prepare(
			"SELECT urls2.listing_id, urls.sync_id, urls.calendar_url 
            FROM {$this->table_name} AS urls
            INNER JOIN {$this->table_name} AS urls2 ON urls2.sync_id = urls.sync_id AND urls2.listing_id != %d
            WHERE urls.listing_id = %d",
			$listing_id,
			$listing_id
		);

		$rows = $wpdb->get_results( $sql, ARRAY_A );
		$urls = array();
		foreach ( $rows as $row ) {
			$sync_id = $row['sync_id'];

			if ( ! isset( $urls[ $sync_id ] ) ) {
				$urls[ $sync_id ] = array(
					'listing_ids'  => array(),
					'calendar_url' => $row['calendar_url'],
				);
			}

			$urls[ $sync_id ]['listing_ids'][] = $row['listing_id'];
		}

		return $urls;
	}

	/**
	 * Updates sync URLs associated with a specific listing ID.
	 *
	 * @param int $listing_id The ID of the listing.
	 * @param array $urls An array of sync URLs to be updated.
	 */
	public function update_urls( int $listing_id, array $urls ) {
		if ( empty( $urls ) ) {
			$this->remove_urls( $listing_id );
		} else {
			$new_urls      = $this->prepare_urls( $urls );
			$existing_urls = $this->get_urls( $listing_id );

			$to_insert = array_diff_key( $new_urls, $existing_urls );
			$to_remove = array_diff_key( $existing_urls, $new_urls );

			if ( ! empty( $to_insert ) ) {
				$this->insert_urls( $listing_id, $to_insert );
			}

			if ( ! empty( $to_remove ) ) {
				$this->remove_urls( $listing_id, array_keys( $to_remove ) );
			}
		}
	}

	/**
	 * Removes sync URLs associated with a specific listing ID.
	 *
	 * @param int $listing_id The ID of the listing.
	 * @param null|string|string[] $sync_id The sync ID(s) of the URLs to be removed.
	 */
	public function remove_urls( int $listing_id, $sync_id = null ) {
		global $wpdb;

		if ( is_null( $sync_id ) ) {
			$sql = $wpdb->prepare( "DELETE FROM {$this->table_name} WHERE listing_id = %d", $listing_id );
		} elseif ( is_array( $sync_id ) ) {
			$sync_ids = esc_sql( $sync_id );
			$sync_ids = "'" . implode( "', '", $sync_ids ) . "'";

			$sql = $wpdb->prepare( "DELETE FROM {$this->table_name} WHERE listing_id = %d AND sync_id IN ({$sync_ids})", $listing_id );
		} else {
			$sql = $wpdb->prepare( "DELETE FROM {$this->table_name} WHERE listing_id = %d AND sync_id = %s", $listing_id, $sync_id );
		}

		$wpdb->query( $sql );
	}
}
