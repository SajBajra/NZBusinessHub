<?php
/**
 * Main logs handler Class.
 *
 * @package GeoDirectory
 * @subpackage Booking
 * @version 1.0.0
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Handles the display of process logs and statistics.
 *
 * @package GeoDirectory
 * @subpackage Booking
 * @version 1.0.0
 * @since   1.0.0
 */
class GeoDir_Booking_Logs_Handler {

	/**
	 * Displays the process details including logs and statistics.
	 *
	 * @param array $process_details An array containing logs and statistics.
	 */
	public function display( array $process_details ) {
		$logs  = $process_details['logs'];
		$stats = $process_details['stats'];

		$this->display_title();

		$this->display_stats( $stats );

		$this->display_logs( $logs );
	}

	/**
	 * Displays the title for the process information section.
	 */
	public function display_title() {
		echo '<h3>';
		esc_html_e( 'Process Information', 'geodir-booking' );
		echo '</h3>';
	}

	/**
	 * Displays the statistics for the process.
	 *
	 * @param array $stats An array containing various statistics.
	 */
	public function display_stats( array $stats ) {
		echo '<p class="geodir-booking-import-stats">';
		printf( esc_html__( 'Total bookings: %s', 'geodir-booking' ), '<span class="geodir-booking-total">' . esc_html( $stats['total'] ) . '</span>' );
		echo '<br />';
		printf( esc_html__( 'Success bookings: %s', 'geodir-booking' ), '<span class="geodir-booking-succeed">' . esc_html( $stats['succeed'] ) . '</span>' );
		echo '<br />';
		printf( esc_html__( 'Skipped bookings: %s', 'geodir-booking' ), '<span class="geodir-booking-skipped">' . esc_html( $stats['skipped'] ) . '</span>' );
		echo '<br />';
		printf( esc_html__( 'Failed bookings: %s', 'geodir-booking' ), '<span class="geodir-booking-failed">' . esc_html( $stats['failed'] ) . '</span>' );
		echo '<br />';
		printf( esc_html__( 'Removed bookings: %s', 'geodir-booking' ), '<span class="geodir-booking-removed">' . esc_html( $stats['removed'] ) . '</span>' );
		echo '</p>';
	}

	/**
	 * Displays the logs associated with the process.
	 *
	 * @param array $logs An array containing log entries.
	 */
	public function display_logs( array $logs = array() ) {
		echo '<ol class="geodir-booking-logs">';
		foreach ( $logs as $log ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo $this->log_to_html( $log );
		}
		echo '</ol>';
	}

	/**
	 * Displays the progress of the process.
	 */
	public function display_progress() {
		echo '<div class="geodir-booking-progress">';
		echo '<div class="geodir-booking-progress__bar"></div>';
		echo '<div class="geodir-booking-progress__text">0%</div>';
		echo '</div>';
	}

	/**
	 * Displays the abort button for the process.
	 *
	 * @param bool $disabled Indicates whether the button should be disabled.
	 */
	public function display_abort_button( bool $disabled = false ) {
		$disabled_attr = $disabled ? ' disabled="disabled"' : '';
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '<button class="button geodir-booking-abort-process"' . $disabled_attr . '>' . esc_html__( 'Abort Process', 'geodir-booking' ) . '</button>';
	}

	/**
	 * Displays the clear button to delete all logs.
	 *
	 * @param bool $disabled Indicates whether the button should be disabled.
	 */
	public function display_clear_button( bool $disabled = false ) {
		$disabled_attr = $disabled ? ' disabled="disabled"' : '';
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '<button class="button geodir-booking-clear-all"' . $disabled_attr . '>' . esc_html__( 'Delete All Logs', 'geodir-booking' ) . '</button>';
	}

	/**
	 * Displays the expand all button.
	 */
	public function display_expand_all_button() {
		echo '<button class="button-link geodir-booking-expand-all">' . esc_html__( 'Expand All', 'geodir-booking' ) . '</button>';
	}

	/**
	 * Displays the collapse all button.
	 */
	public function display_collapse_all_button() {
		echo '<button class="button-link geodir-booking-collapse-all">' . esc_html__( 'Collapse All', 'geodir-booking' ) . '</button>';
	}

	/**
	 * Converts a log entry into HTML format.
	 *
	 * @param array $log Log entry ["status", "message"].
	 * @param bool $inline Indicates whether the log should be displayed inline.
	 * @return string HTML representation of the log entry.
	 */
	public function log_to_html( array $log, bool $inline = false ) {
		$log += array(
			'status'  => 'info',
			'message' => '',
		);

		$html = '';

		if ( ! empty( $log['message'] ) && ! $inline ) {
			$html .= '<li>';
			$html .= '<p class="notice notice-' . esc_attr( $log['status'] ) . '">';
			$html .= esc_html( $log['message'] );
			$html .= '</p>';
			$html .= '</li>';
		} else {
			$html .= esc_html( $log['message'] );
		}

		return $html;
	}

	/**
	 * Converts an array of logs into HTML format.
	 *
	 * @param array $logs An array of log entries.
	 * @param bool $inline Indicates whether the logs should be displayed inline.
	 * @return array HTML representations of the log entries.
	 */
	public function logs_to_html( array $logs, bool $inline = false ) {
		$logs_html = array();
		foreach ( $logs as $log ) {
			$logs_html[] = $this->log_to_html( $log, $inline );
		}
		return $logs_html;
	}

	/**
	 * Builds a notice message based on the number of successful and failed bookings.
	 *
	 * @param int $succeed_count The number of successful bookings.
	 * @param int $failed_count The number of failed bookings.
	 * @return string The HTML representation of the notice message.
	 */
	public function build_notice( int $succeed_count, int $failed_count ) {
		$message  = _n(
			'All done! %1$d booking was successfully added.',
			'All done! %1$d bookings were successfully added.',
			$succeed_count,
			'geodir-booking'
		);
		$message .= _n(
			' There was %2$d failure.',
			' There were %2$d failures.',
			$failed_count,
			'geodir-booking'
		);
		$message  = sprintf( $message, $succeed_count, $failed_count );

		$notice  = '<div class="updated notice notice-success is-dismissible">';
		$notice .= '<p>' . $message . '</p>';
		$notice .= '</div>';

		return $notice;
	}
}
