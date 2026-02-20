<?php
/**
 * Contains the class that displays the evens report.
 *
 *
 */

defined( 'ABSPATH' ) || exit;

/**
 * GeoDir_Tickets_Report_Events Class.
 */
class GeoDir_Tickets_Report_Events extends GetPaid_Reports_Abstract_Report {

	/**
	 * @var string
	 */
	public $field = 'event_name';

	/**
	 * @var int
	 */
	public $total_items = 0;

	/**
	 * Retrieves the earning sql.
	 *
	 */
	public function get_sql( $range, $mode = 'individual' ) {
		global $wpdb;

		$clauses    = $this->get_range_sql( $range, 'CAST(date_created AS DATE)', 'date_created' );
		$where      = 'status != "pending" AND ' . $clauses[1];

		if ( 'total' === $mode ) {
			return "SELECT COUNT(id) FROM {$wpdb->prefix}geodir_tickets WHERE $where";
		}

		return "SELECT event_id, COUNT(id) as sales FROM {$wpdb->prefix}geodir_tickets WHERE $where GROUP BY event_id ORDER BY sales DESC LIMIT 0, 10";

	}

	/**
	 * Prepares the report stats.
	 *
	 */
	public function prepare_stats() {
		global $wpdb;
		$this->stats = $wpdb->get_results( $this->get_sql( $this->get_range() ) );
		$this->total_items = $wpdb->get_var( $this->get_sql( $this->get_range(), 'total' ) );
	}

	/**
	 * Displays the actual report.
	 *
	 */
	public function display_stats() {

		$colors = ['#F44336', '#E91E63', '#9C27B0', '#673AB7', '#3F51B5', '#2196F3', '#03A9F4', '#00BCD4', '#009688', '#4CAF50', '#8BC34A', '#CDDC39', '#FFEB3B', '#FFC107', '#FF9800', '#FF5722', '#795548', '#607D8B'];

		foreach ( $this->stats as $i => $stat ) {

			if ( empty( $stat->sales ) ) {
				continue;
			}

			$percent  = floor( $stat->sales / $this->total_items * 100 );
			$bg_color = isset( $colors[ $i ] ) ? $colors[ $i ] : sprintf( '#%06X', wp_rand( 0, 0xcccccc ) );
			$color    = wpinv_light_or_dark( $bg_color, 'text-dark', 'text-white' );
			?>
				<a href="<?php echo esc_url( get_the_permalink( $stat->event_id ) ); ?>" class="form-text d-block text-muted"><?php echo strip_tags( get_the_title( $stat->event_id ) ); ?></a>
				<div class="progress mb-3">
					<div class="progress-bar <?php echo sanitize_html_class( $color ); ?>" role="progressbar" style="width: <?php echo (int) $percent; ?>%; background-color: <?php echo esc_attr( $bg_color ); ?>" aria-valuenow="<?php echo (int) $percent; ?>" aria-valuemin="0" aria-valuemax="100"><?php echo (int) $stat->sales ?></div>
				</div>
			<?php
		}

		?>
			<strong class="form-text d-block text-dark"><?php printf( __( 'Total Tickets Sold: %d', 'geodir-tickets' ), $this->total_items ); ?></strong>
		<?php

	}

}
