<?php
/**
 * Main complete bookings cron Class.
 *
 * @package GeoDirectory
 * @subpackage Booking
 * @version 1.0.0
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Main class for completing bookings via cron job.
 *
 * @package GeoDirectory
 * @subpackage Booking
 * @version 1.0.0
 * @since   1.0.0
 */
class GeoDir_Booking_Delete_Sync_Logs_Cron extends GeoDir_Booking_Abstract_Cron {

	/**
	 * Execute the cron job to complete past bookings.
	 */
	public function do_cron_job() {
		global $wpdb;

		$period = GeoDir_Booking_CRON::instance()->get_duration_delete_sync_logs();

		if ( 'never' === $period ) {
			return;
		}

		$date = new \DateTime( '-6 months' );

		switch ( $period ) {
			case 'day':
				$date = new \DateTime( '-1 day' );
				break;
			case 'week':
				$date = new \DateTime( '-1 week' );
				break;
			case 'month':
				$date = new \DateTime( '-1 month' );
				break;
			case 'quarter':
				$date = new \DateTime( '-3 months' );
				break;
			case 'half_year':
				$date = new \DateTime( '-6 months' );
				break;
		}

		$timestamp = $date->getTimestamp();

		$queue_table = GeoDir_Booking_Queue::instance()->gdbc_sync_queue;
		$stat_table  = GeoDir_Booking_Stats::instance()->gdbc_sync_stats;
		$logs_table  = GeoDir_Booking_Logger::instance()->gdbc_sync_logs;

		$wpdb->query( "DELETE FROM $logs_table WHERE queue_id IN (SELECT queue_id FROM $queue_table WHERE queue_name < '$timestamp')" );
		$wpdb->query( "DELETE FROM $stat_table WHERE queue_id IN (SELECT queue_id FROM $queue_table WHERE queue_name < '$timestamp')" );
		$wpdb->query( "DELETE FROM $queue_table WHERE queue_id IN ( SELECT * FROM (SELECT queue_id FROM $queue_table WHERE queue_name < '$timestamp') AS p )" );
	}
}
