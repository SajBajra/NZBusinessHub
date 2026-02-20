<?php
/**
 * Metaboxes Admin.
 *
 */

defined( 'ABSPATH' ) || exit;

/**
 * Metaboxes Admin Class
 *
 */
class Adv_Metaboxes {

	/**
	 * Only save metaboxes once.
	 *
	 * @var boolean
	 */
	private static $saved_meta_boxes = false;

    /**
	 * Hook in methods.
	 */
	public static function init() {

		// Register metaboxes.
		add_action( 'add_meta_boxes', 'Adv_Metaboxes::add_meta_boxes', 5, 2 );

		// Remove metaboxes.
		add_action( 'add_meta_boxes', 'Adv_Metaboxes::remove_meta_boxes', 30 );

		// Rename metaboxes.
		add_action( 'add_meta_boxes', 'Adv_Metaboxes::rename_meta_boxes', 45 );

		// Save metaboxes.
		add_action( 'save_post', 'Adv_Metaboxes::save_meta_boxes', 1, 2 );
	}

	/**
	 * Register core metaboxes.
	 */
	public static function add_meta_boxes( $post_type, $post ) {

		// For adverts...
		self::add_ad_meta_boxes( $post_type );

		// For advertising zones.
		self::add_zone_meta_boxes( $post_type );

	}

	/**
	 * Register ad metaboxes.
	 */
	protected static function add_ad_meta_boxes( $post_type ) {

		if ( adv_ad_post_type() === $post_type ) {

			// Ad details.
			add_meta_box( 'adv-ad-details', __( 'Ad Details', 'advertising' ), 'Adv_Meta_Box_Ad_Details::output', $post_type, 'normal', 'high' );

			// Ad stats.
			add_meta_box( 'adv-ad-stats', __( 'Ad Stats', 'advertising' ), 'Adv_Meta_Box_Ad_Stats::output', $post_type, 'side', 'high' );

			// Ad Invoice.
			if ( function_exists( 'wpinv_get_invoice' ) ) {
				add_meta_box( 'adv-ad-invoice', __( 'Related Invoices', 'advertising' ), 'Adv_Meta_Box_Ad_Invoice::output', $post_type, 'normal', 'high' );
			}
		}

	}

	/**
	 * Register zone metaboxes.
	 */
	protected static function add_zone_meta_boxes( $post_type ) {

		if ( adv_zone_post_type() === $post_type ) {

			// Zone details.
			add_meta_box( 'adv-zone-details', __( 'Zone Details', 'advertising' ), 'Adv_Meta_Box_Zone_Details::output', $post_type, 'normal', 'high' );

			// Zone display restrictions.
			add_meta_box( 'adv-zone-display', __( 'Display Rules', 'advertising' ), 'Adv_Meta_Box_Zone_Display_Rules::output', $post_type, 'normal', 'high' );

			// Zone usage details.
			add_meta_box( 'adv-zone-usage', __( 'Usage Details', 'advertising' ), 'Adv_Meta_Box_Zone_Usage::output', $post_type, 'normal', 'high' );

			// Zone stats.
			add_meta_box( 'adv-zone-stats', __( 'Zone Stats', 'advertising' ), 'Adv_Meta_Box_Zone_Stats::output', $post_type, 'side', 'high' );

		}

	}

	/**
	 * Remove some metaboxes.
	 */
	public static function remove_meta_boxes() {
		remove_meta_box( 'wpseo_meta', adv_zone_post_type(), 'normal' );
	}

	/**
	 * Rename other metaboxes.
	 */
	public static function rename_meta_boxes() {

	}

	/**
	 * Check if we're saving, then trigger an action based on the post type.
	 *
	 * @param  int    $post_id Post ID.
	 * @param  object $post Post object.
	 */
	public static function save_meta_boxes( $post_id, $post ) {
		$post_id = absint( $post_id );
		$data    = wp_unslash( $_POST );

		// Do not save for ajax requests.
		if ( ( defined( 'DOING_AJAX') && DOING_AJAX ) || isset( $_REQUEST['bulk_edit'] ) ) {
			return;
		}

		// $post_id and $post are required
		if ( empty( $post_id ) || empty( $post ) || self::$saved_meta_boxes ) {
			return;
		}

		// Dont' save meta boxes for revisions or autosaves.
		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || is_int( wp_is_post_revision( $post ) ) || is_int( wp_is_post_autosave( $post ) ) ) {
			return;
		}

		// Check the nonce.
		if ( empty( $data['adv_meta_nonce'] ) || ! wp_verify_nonce( $data['adv_meta_nonce'], 'adv_meta_nonce' ) ) {
			return;
		}

		// Check the post being saved == the $post_id to prevent triggering this call for other save_post events.
		if ( empty( $data['post_ID'] ) || absint( $data['post_ID'] ) !== $post_id ) {
			return;
		}

		// Check user has permission to edit.
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// Ensure this is our post type.
		$post_types_map = array(
			adv_zone_post_type() => 'Adv_Meta_Box_Zone_Details',
			adv_ad_post_type()   => 'Adv_Meta_Box_Ad_Details',
		);

		// Is this our post type?
		if ( ! isset( $post_types_map[ $post->post_type ] ) ) {
			return;
		}

		// We need this save event to run once to avoid potential endless loops.
		self::$saved_meta_boxes = true;

		// Save the post.
		$class = $post_types_map[ $post->post_type ];
		$class::save( $post_id, $post, $_POST );

	}

}
