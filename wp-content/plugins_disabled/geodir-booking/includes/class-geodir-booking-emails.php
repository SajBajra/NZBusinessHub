<?php
/**
 * Bookings Manager Emails class.
 *
 * @since 1.0.0
 * @package GeoDir_Booking_Manager
 * @author AyeCode Ltd
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * GeoDir_Booking_Emails class.
 */
class GeoDir_Booking_Emails {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_filter( 'geodir_email_settings', array( $this, 'filter_email_settings' ), 21, 1 );
		add_filter( 'geodir_user_email_settings', array( $this, 'filter_user_email_settings' ), 21, 1 );
		add_filter( 'geodir_email_subject', array( $this, 'get_subject' ), 10, 3 );
		add_filter( 'geodir_email_content', array( $this, 'get_content' ), 10, 3 );
		add_filter( 'geodir_email_wild_cards', array( $this, 'set_wild_cards' ), 10, 4 );
	}

	/**
	 * Filter the email settings.
	 *
	 * @since 1.0.0
	 * @param array $settings The email settings.
	 * @return array The email settings.
	 */
	public function filter_email_settings( $settings ) {
		if ( $merge_settings = $this->bcc_email_settings() ) {
			$position = count( $settings ) - 1;
			$settings = array_merge( array_slice( $settings, 0, $position ), $merge_settings, array_slice( $settings, $position ) );
		}

		return $settings;
	}

	/**
	 * Filter the user email settings.
	 *
	 * @since 1.0.0
	 * @param array $settings The email settings.
	 * @return array
	 */
	public function filter_user_email_settings( $settings ) {
		if ( $merge_settings = $this->user_email_settings() ) {
			$position = count( $settings );
			$settings = array_merge( array_slice( $settings, 0, $position ), $merge_settings, array_slice( $settings, $position ) );
		}

		return $settings;
	}

	/**
	 * Get the BCC email settings.
	 *
	 * @return array
	 */
	public function bcc_email_settings() {

		$settings = array(

			array(
				'type'     => 'checkbox',
				'id'       => 'email_bcc_owner_booking_request',
				'name'     => __( 'Booking Request (Listing Owner)', 'geodir-booking' ),
				'desc'     => __( 'This will send a BCC email to the site admin when there is a booking request.', 'geodir-booking' ),
				'default'  => 0,
				'advanced' => false,
			),

			array(
				'type'     => 'checkbox',
				'id'       => 'email_bcc_owner_booking_confirmation',
				'name'     => __( 'Booking Confirmation (Listing Owner)', 'geodir-booking' ),
				'desc'     => __( 'This will send a BCC email to the site admin for listing owner booking confirmations.', 'geodir-booking' ),
				'default'  => 0,
				'advanced' => false,
			),

			array(
				'type'     => 'checkbox',
				'id'       => 'email_bcc_owner_booking_cancellation',
				'name'     => __( 'Booking Cancellation (Listing Owner)', 'geodir-booking' ),
				'desc'     => __( 'This will send a BCC email to the site admin for listing owner booking cancellations.', 'geodir-booking' ),
				'default'  => 0,
				'advanced' => false,
			),

			array(
				'type'     => 'checkbox',
				'id'       => 'email_bcc_owner_booking_refunded',
				'name'     => __( 'Booking Refunded (Listing Owner)', 'geodir-booking' ),
				'desc'     => __( 'This will send a BCC email to the site admin for listing owner booking refunds.', 'geodir-booking' ),
				'default'  => 0,
				'advanced' => false,
			),

			array(
				'type'     => 'checkbox',
				'id'       => 'email_bcc_user_booking_pending',
				'name'     => __( 'Booking Pending (Customer)', 'geodir-booking' ),
				'desc'     => __( 'This will send a BCC email to the site admin for customer booking pending review emails.', 'geodir-booking' ),
				'default'  => 0,
				'advanced' => false,
			),

			array(
				'type'     => 'checkbox',
				'id'       => 'email_bcc_user_booking_rejected',
				'name'     => __( 'Booking Rejected (Customer)', 'geodir-booking' ),
				'desc'     => __( 'This will send a BCC email to the site admin for customer booking rejected emails.', 'geodir-booking' ),
				'default'  => 0,
				'advanced' => false,
			),

			array(
				'type'     => 'checkbox',
				'id'       => 'email_bcc_user_booking_confirmation',
				'name'     => __( 'Booking Confirmation (Customer)', 'geodir-booking' ),
				'desc'     => __( 'This will send a BCC email to the site admin for customer booking confirmations.', 'geodir-booking' ),
				'default'  => 0,
				'advanced' => false,
			),

			array(
				'type'     => 'checkbox',
				'id'       => 'email_bcc_user_booking_cancellation',
				'name'     => __( 'Booking Cancellation (Customer)', 'geodir-booking' ),
				'desc'     => __( 'This will send a BCC email to the site admin for customer booking cancellations.', 'geodir-booking' ),
				'default'  => 0,
				'advanced' => false,
			),

			array(
				'type'     => 'checkbox',
				'id'       => 'email_bcc_user_booking_refunded',
				'name'     => __( 'Booking Refunded (Customer)', 'geodir-booking' ),
				'desc'     => __( 'This will send a BCC email to the site admin for customer booking refunds.', 'geodir-booking' ),
				'default'  => 0,
				'advanced' => false,
			),

		);

		return apply_filters( 'geodir_booking_bcc_email_settings', $settings );
	}

	/**
	 * Get the email settings.
	 *
	 * @return array
	 */
	public function user_email_settings() {

		$settings = array_merge(
			// Booking Request (Listing Owner).
			$this->individual_email_settings(
				'owner_booking_request',
				__( 'Booking Request (Listing Owner)', 'geodir-booking' ),
				__( 'This will send an email to the listing owner when there is a booking request.', 'geodir-booking' )
			),
			// Booking Confirmation (Listing Owner).
			$this->individual_email_settings(
				'owner_booking_confirmation',
				__( 'Booking Confirmation (Listing Owner)', 'geodir-booking' ),
				__( 'This will send an email to the listing owner when a booking is confirmed.', 'geodir-booking' )
			),
			// Booking Cancellation (Listing Owner).
			$this->individual_email_settings(
				'owner_booking_cancellation',
				__( 'Booking Cancellation (Listing Owner)', 'geodir-booking' ),
				__( 'This will send an email to the listing owner when a booking is canceled.', 'geodir-booking' )
			),
			// Booking Refunded (Listing Owner).
			$this->individual_email_settings(
				'owner_booking_refunded',
				__( 'Booking Refunded (Listing Owner)', 'geodir-booking' ),
				__( 'This will send an email to the listing owner when a booking is refunded.', 'geodir-booking' )
			),
			// Booking Pending (Customer).
			$this->individual_email_settings(
				'user_booking_pending',
				__( 'Booking Pending (Customer)', 'geodir-booking' ),
				__( 'This will send an email to the customer when a booking is pending review.', 'geodir-booking' )
			),
			// Booking Reject (Customer).
			$this->individual_email_settings(
				'user_booking_rejected',
				__( 'Booking Rejected (Customer)', 'geodir-booking' ),
				__( 'This will send an email to the customer when a booking is rejected.', 'geodir-booking' )
			),
			// Booking Confirmation (Customer).
			$this->individual_email_settings(
				'user_booking_confirmation',
				__( 'Booking Confirmation (Customer)', 'geodir-booking' ),
				__( 'This will send an email to the customer when a booking is confirmed.', 'geodir-booking' )
			),
			// Booking Cancellation (Customer).
			$this->individual_email_settings(
				'user_booking_cancellation',
				__( 'Booking Cancellation (Customer)', 'geodir-booking' ),
				__( 'This will send an email to the customer when a booking is canceled.', 'geodir-booking' )
			),
			// Booking Refunded (Customer).
			$this->individual_email_settings(
				'user_booking_refunded',
				__( 'Booking Refunded (Customer)', 'geodir-booking' ),
				__( 'This will send an email to the customer when a booking is refunded.', 'geodir-booking' )
			)
		);

		return apply_filters( 'geodir_booking_user_email_settings', $settings );
	}

	/**
	 * Prepares a given email's settings.
	 *
	 * @param string $email_id The email ID.
	 * @param string $email_title The email title.
	 * @param string $email_description The email description.
	 * @return array
	 */
	protected function individual_email_settings( $email_id, $email_title, $email_description ) {

		$settings = array(

			// Section title.
			array(
				'type' => 'title',
				'id'   => "email_{$email_id}_settings",
				'name' => $email_title,
				'desc' => '',
			),

			// Enable disable.
			array(
				'type'    => 'checkbox',
				'id'      => "email_{$email_id}",
				'name'    => __( 'Enable email', 'geodir-booking' ),
				'desc'    => $email_description,
				'default' => 1,
			),

			// Email subject.
			array(
				'type'        => 'text',
				'id'          => "email_{$email_id}_subject",
				'name'        => __( 'Subject', 'geodir-booking' ),
				'desc'        => __( 'The email subject.', 'geodir-booking' ),
				'class'       => 'active-placeholder',
				'desc_tip'    => true,
				'placeholder' => include plugin_dir_path( __FILE__ ) . "views/emails/{$email_id}_subject.php",
				'advanced'    => true,
			),

			// Email body.
			array(
				'type'        => 'textarea',
				'id'          => "email_{$email_id}_body",
				'name'        => __( 'Body', 'geodir-booking' ),
				'desc'        => __( 'The email body, this can be text or HTML.', 'geodir-booking' ),
				'class'       => 'code gd-email-body',
				'desc_tip'    => true,
				'advanced'    => true,
				'placeholder' => include plugin_dir_path( __FILE__ ) . "views/emails/{$email_id}_body.php",
				'custom_desc' => __( 'Available template tags:', 'geodir-booking' ) . ' ' . static::get_email_tags( $email_id, true ),
			),

			array(
				'type' => 'sectionend',
				'id'   => "email_{$email_id}_settings",
			),

		);

		return apply_filters( "geodir_booking_{$email_id}_settings", $settings );
	}

	/**
	 * Get email tags.
	 *
	 * @param string $email_type Email type.
	 * @param bool $inline Optional. Email tag inline value. Default true.
	 * @return string[]|string
	 */
	public function get_email_tags( $email_type, $inline = true ) {

		// Global email tags.
		$tags = array( '[#blogname#]', '[#site_link#]', '[#site_name#]', '[#site_url#]', '[#site_name_url#]', '[#login_url#]', '[#login_link#]', '[#date#]', '[#time#]', '[#date_time#]', '[#current_date#]', '[#to_name#]', '[#to_email#]', '[#from_name#]', '[#from_email#]' );
		$tags = apply_filters( 'geodir_email_global_email_tags', $tags );

		// Booking email tags.
		$tags = array_merge(
			$tags,
			array(
				'[#booking_id#]',
				'[#booking_customer_email#]',
				'[#booking_customer_name#]',
				'[#booking_customer_phone#]',
				'[#booking_private_note#]',
				'[#booking_modified_date_time#]',
				'[#booking_modified_date]',
				'[#booking_modified_time]',
				'[#booking_created_date_time#]',
				'[#booking_created_date]',
				'[#booking_created_time]',
				'[#booking_status#]',
				'[#booking_check_in_date#]',
				'[#booking_check_out_date#]',
				'[#booking_total#]',
				'[#booking_payable_amount#]',
				'[#booking_service_fee#]',
				'[#booking_potential_payout_amount#]',
				'[#booking_site_commission#]',
				'[#booking_invoice_url#]',
			)
		);

		// Listing email tags.
		$tags = array_merge(
			$tags,
			array(
				'[#post_id#]',
				'[#post_status#]',
				'[#post_date#]',
				'[#posted_date#]',
				'[#post_author_ID#]',
				'[#post_author_name#]',
				'[#post_author_email#]',
				'[#client_name#]',
				'[#listing_title#]',
				'[#listing_url#]',
				'[#listing_link#]',
			)
		);

		// Filter tags.
		$tags = apply_filters( 'geodir_booking_email_tags', array_unique( array_filter( $tags ) ), $email_type );

		// Maybe convert array to string.
		if ( $inline ) {
			$tags = '<code>' . implode( '</code> <code>', $tags ) . '</code>';
		}

		return $tags;
	}

	/**
	 * Filters the email subject.
	 *
	 * @param string $subject
	 * @param string $email_name
	 * @param array $email_vars
	 * @return string
	 */
	public function get_subject( $subject, $email_name = '', $email_vars = array() ) {
		// Prevent conflicts with Appointments plugin.
		if ( ! empty( $email_vars['booking_type'] ) && $email_vars['booking_type'] != 'booking' ) {
			return $subject;
		}

		// Abort if no subject.
		if ( ! empty( $subject ) || ! file_exists( plugin_dir_path( __FILE__ ) . "views/emails/{$email_name}_subject.php" ) ) {
			return $subject;
		}

		return GeoDir_Email::replace_variables( include plugin_dir_path( __FILE__ ) . "views/emails/{$email_name}_subject.php", $email_name, $email_vars );
	}

	/**
	 * Filters the email content.
	 *
	 * @param string $content
	 * @param string $email_name
	 * @param array $email_vars
	 * @return string
	 */
	public function get_content( $content, $email_name = '', $email_vars = array() ) {
		// Prevent conflicts with Appointments plugin.
		if ( ! empty( $email_vars['booking_type'] ) && $email_vars['booking_type'] != 'booking' ) {
			return $content;
		}

		// Abort if no content.
		if ( ! empty( $content ) || ! file_exists( plugin_dir_path( __FILE__ ) . "views/emails/{$email_name}_body.php" ) ) {
			return $content;
		}

		return GeoDir_Email::replace_variables( include plugin_dir_path( __FILE__ ) . "views/emails/{$email_name}_body.php", $email_name, $email_vars );
	}

	/**
	 * Set email wildcards.
	 *
	 * @param array  $wildcards Email wildcards.
	 * @param string $content Email content.
	 * @param string $email_name Email name.
	 * @param array  $email_vars Email variables.
	 */
	public function set_wild_cards( $wild_cards, $content, $email_name, $email_vars = array() ) {

		// Handle our emails.
		if ( false !== strpos( $email_name, '_booking_' ) ) {

			$booking_tags = array(
				'[#customer_name#]'   => '',
				'[#listing_title#]'   => '',
				'[#listing_url#]'     => '',
				'[#listing_owner#]'   => '',
				'[#booking_details#]' => '',
			);

			if ( ! empty( $email_vars['post'] ) ) {
				$gd_post = $email_vars['post'];

				$booking_tags['[#listing_owner#]'] = geodir_get_client_name( $gd_post->post_author );
				$booking_tags['[#listing_title#]'] = $gd_post->post_title;
				$booking_tags['[#listing_url#]']   = geodir_get_listing_url( $gd_post->ID );
			}

			$wild_cards = array_merge(
				$wild_cards,
				apply_filters( "geodir_booking_{$email_name}_wild_cards", $booking_tags, $wild_cards, $content, $email_name, $email_vars )
			);
		}

		return $wild_cards;
	}

	/**
	 * Sends an email.
	 *
	 * @param string $email_name For example, owner_booking_request.
	 * @param WP_Post $post The listing post object.
	 * @param GeoDir_Customer_Booking $booking The booking object.
	 */
	public function send_email( $email_name, $post, $booking ) {
		// Abort if the email is not enabled.
		if ( ! GeoDir_Email::is_email_enabled( $email_name, 'yes' ) ) {
			return false;
		}

		// Fetch author data.
		$author_data = get_userdata( $post->post_author );
		if ( empty( $author_data ) ) {
			return false;
		}

		// Either send it to the author or the customer.
		if ( 0 === strpos( $email_name, 'owner_' ) ) {
			$recipient         = $author_data->user_email;
			$is_customer_email = false;
		} else {
			$recipient         = $booking->email;
			$is_customer_email = true;
		}

		// Abort if the recipient is not set.
		if ( ! is_email( $recipient ) ) {
			return;
		}

		// Switch language to user language.
		do_action( 'wpml_switch_language_for_email', $recipient );

		do_action( 'geodir_booking_send_email_start', $email_name, $post, $booking );

		// Fetch invoice.
		$invoice = new WPInv_Invoice( $booking->invoice_id );

		$email_vars = array(
			'booking_type'                    => 'booking',
			'post_id'                         => $post->ID,
			'booking_id'                      => $booking->id,
			'booking_customer_email'          => $booking->email,
			'booking_customer_name'           => $booking->name,
			'booking_customer_phone'          => $booking->phone,
			'booking_private_note'            => wp_kses_post( wpautop( $booking->private_note ) ),
			'booking_modified_date_time'      => date_i18n( geodir_date_time_format(), strtotime( $booking->modified ) ),
			'booking_modified_date'           => date_i18n( geodir_date_format(), strtotime( $booking->modified ) ),
			'booking_modified_time'           => date_i18n( geodir_time_format(), strtotime( $booking->modified ) ),
			'booking_created_date_time'       => date_i18n( geodir_date_time_format(), strtotime( $booking->created ) ),
			'booking_created_date'            => date_i18n( geodir_date_format(), strtotime( $booking->created ) ),
			'booking_created_time'            => date_i18n( geodir_time_format(), strtotime( $booking->created ) ),
			'booking_status'                  => geodir_get_booking_status_label( $booking->status ),
			'booking_check_in_date'           => date_i18n( geodir_date_format(), strtotime( $booking->start_date ) ),
			'booking_check_out_date'          => date_i18n( geodir_date_format(), strtotime( $booking->end_date ) ),
			'booking_total'                   => wpinv_price( $booking->total_amount, $invoice->get_currency() ),
			'booking_payable_amount'          => wpinv_price( $booking->payable_amount, $invoice->get_currency() ),
			'booking_service_fee'             => wpinv_price( $booking->service_fee, $invoice->get_currency() ),
			'booking_potential_payout_amount' => wpinv_price( $booking->payable_amount - $booking->site_commission, $invoice->get_currency() ),
			'booking_site_commission'         => wpinv_price( $booking->site_commission, $invoice->get_currency() ),
			'booking_invoice_url'             => $invoice->get_view_url(),
			'post_author_email'               => $author_data->user_email,
		);

		// Either send it to the author or the customer.
		if ( 0 === strpos( $email_name, 'owner_' ) ) {
			$email_vars['to_name']  = geodir_get_client_name( $post->post_author );
			$email_vars['to_email'] = $recipient;
		} else {
			$email_vars['to_name']  = $booking->name;
			$email_vars['to_email'] = $recipient;
		}

		/**
		 * Skip email send.
		 *
		 * @since 2.0.8
		 */
		$skip = apply_filters( 'geodir_skip_email_send', false, $email_name, $email_vars );

		if ( $skip === true ) {
			// Switch language back.
			do_action( 'wpml_restore_language_from_email' );

			return;
		}

		do_action( 'geodir_booking_pre_' . $email_name . '_email', $booking, $email_vars );

		$subject     = GeoDir_Email::get_subject( $email_name, $email_vars );
		$headers     = GeoDir_Email::get_headers( $email_name, $email_vars );
		$attachments = GeoDir_Email::get_attachments( $email_name, $email_vars );

		$plain_text = GeoDir_Email::get_email_type() != 'html' ? true : false;
		$template   = $plain_text ? 'plain_text-email.php' : 'html-email.php';

		$content = geodir_get_template_html(
			$template,
			array(
				'email_name'    => $email_name,
				'email_vars'    => $email_vars,
				'email_heading' => '',
				'sent_to_admin' => false,
				'message_body'  => GeoDir_Email::get_content( $email_name, $email_vars ),
			),
			'geodir-booking',
			plugin_dir_path( GEODIR_BOOKING_FILE ) . 'templates'
		);

		$sent = GeoDir_Email::send( $recipient, $subject, $content, $headers, $attachments );

		if ( GeoDir_Email::is_admin_bcc_active( $email_name ) ) {
			$recipient = GeoDir_Email::get_admin_email();
			$subject  .= ' - ADMIN BCC COPY';
			GeoDir_Email::send( $recipient, $subject, $content, $headers, $attachments );
		}

		do_action( 'geodir_booking_post_' . $email_name . '_email', $booking, $email_vars );

		// Switch language back.
		do_action( 'wpml_restore_language_from_email' );

		return $sent;
	}
}
