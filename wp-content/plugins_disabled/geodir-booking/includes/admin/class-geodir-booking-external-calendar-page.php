<?php
/**
 * Bookings external calendars page class.
 *
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Bookings external calendars page class.
 *
 */
class GeoDir_Booking_External_Calendar_Page {
	/**
	 * Show edit page instead of room list.
	 *
	 * @var bool
	 */
	public $is_edit = false;

	/**
	 * Listing object.
	 *
	 * @var object
	 */
	protected $listing;

	/**
	 * Duplicate calendars and listings, that have the same iCal links.
	 *
	 * @var array
	 */
	protected $duplicate_urls = array();

	public function __construct() {
		$listing_id = isset( $_GET['listing_id'] ) ? absint( (int) $_GET['listing_id'] ) : 0;

		if ( ! empty( $listing_id ) ) {

			$listing = geodir_get_post_info( $listing_id );
			if ( isset( $listing->ID ) ) {
				$this->listing = $listing;
				$this->is_edit = true;
			}
		}

		$this->process();
	}

	/**
	 * Processes the saved URLs.
	 */
	public function process() {
		if ( isset( $_POST['save'] ) && $this->is_edit ) {
			check_admin_referer( 'update-calendars' );

			$sync_urls = isset( $_POST['_geodir_booking_urls'] ) && is_array( $_POST['_geodir_booking_urls'] ) ? $_POST['_geodir_booking_urls'] : array();
			$sync_urls = array_filter( $sync_urls );
			$sync_urls = array_unique( $sync_urls );
			$sync_urls = array_map( 'wp_unslash', $sync_urls );
			$sync_urls = array_map( 'esc_url_raw', $sync_urls );

			GeoDir_Booking_Sync_Urls::instance()->update_urls( $this->listing->ID, $sync_urls );

			$this->duplicate_urls = GeoDir_Booking_Sync_Urls::instance()->get_duplicating_urls( $this->listing->ID );
		}
	}

	/**
	 * Displays the external calendar page.
	 */
	public function display() {
		$sync_urls = GeoDir_Booking_Sync_Urls::instance()->get_urls( $this->listing->ID );
		?>
		<div class="wrap">
			<h1 class="wp-heading-inline"><?php esc_html_e( sprintf( __( 'Edit External Calendars of "%s"', 'geodir-booking' ), $this->listing->post_title ) ); ?></h1>
			
			<hr class="wp-header-end" />

			<form class="bsui" method="POST" action="">
				<?php wp_nonce_field( 'update-calendars' ); ?>
				<table class="widefat">
					<thead class="text-center">
						<tr>
							<th>
								<?php esc_html_e( 'Calendar URL', 'geodir-booking' ); ?>
							</th>
							<th><?php esc_html_e( 'Actions', 'geodir-booking' ); ?></th>
						</tr>
					</thead>

					<tbody class="text-center">
						<?php
						foreach ( $sync_urls as $url ) :
							$this->get_calendar_row( $url );
						endforeach;
						?>
					</tbody>

					<tfoot>
						<tr>
							<th colspan="5">
								<button type="button" class="button geodir-booking-add-url">
									<?php esc_html_e( 'Add New Calendar', 'geodir-booking' ); ?>
								</button>
							</th>
						</tr>
					</tfoot>
				</table>
				<div class="mt-3">
					<input type="submit" name="save" class="button button-primary" id="publish" value="<?php esc_attr_e( 'Update', 'geodir-booking' ); ?>" />
					<a class="button button-secondary" href="<?php echo esc_url( admin_url( 'admin.php?page=geodir-booking-ical' ) ); ?>"><?php esc_html_e( 'Back', 'geodir-booking' ); ?></a>
				</div> 
			</form>

			<script type="text/html" id="tmpl-geodir-booking-url-row">
				<?php $this->get_calendar_row( '' ); ?>
			</script>

			<script type="text/javascript">
				jQuery(function($) {
					// Inserts a new row
					$(document).on('click', '.geodir-booking-add-url', function(e) {
						e.preventDefault();
						const html = $('#tmpl-geodir-booking-url-row').html();
						const url_row = $(html);
						$(this).closest('table').find('tbody').append(url_row)
					});

					$(document).on('click', '.geodir-booking-remove-url', function(e) {
						e.preventDefault();
						$(this).closest('tr').remove();
					});
				});
			</script>
		</div>
		<?php
	}

	/**
	 * Outputs a row for the calendar URL.
	 *
	 * @param string $url The calendar URL.
	 */
	public function get_calendar_row( $url ) {
		?>
		<tr class="border-bottom">
			<td class="calendar_url">
				<input type="text" name="_geodir_booking_urls[]" class="large-text" placeholder="<?php esc_attr_e( 'Calendar URL', 'geodir-booking' ); ?>" value="<?php echo esc_attr( $url ); ?>" />
			</td>

			<td>
				<button type="button" class="button geodir-booking-remove-url"><?php esc_html_e( 'Delete', 'geodir-booking' ); ?></button>
			</td>
		</tr>
		<?php
	}
}
