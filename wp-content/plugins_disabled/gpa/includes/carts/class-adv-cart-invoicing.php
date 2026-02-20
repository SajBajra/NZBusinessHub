<?php
/**
 * Advertising & Invoicing integration class.
 *
 * @since 1.0.0
 * @package Advertising
 * @author AyeCode Ltd
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Adv_Cart_Invoicing class.
 */
class Adv_Cart_Invoicing extends Adv_Cart {

	public function __construct() {

		//Register hooks if invoicing is the payment cart and the invoicing plugin is installed
		if( 'invoicing' == adv_get_cart() && defined( 'WPINV_VERSION' ) ) {
			add_filter( 'wpinv_get_item_types', array( __CLASS__, 'register_item_type' ), 10, 1 );
			add_action( 'wpinv_admin_invoice_line_item_summary', array( __CLASS__, 'admin_line_item_summary' ), 10, 4 ) ;
			add_action( 'wpinv_print_invoice_line_item_summary', array( __CLASS__, 'invoice_line_item_summary' ), 10, 4 ) ;
			add_action( 'wpinv_email_invoice_line_item_summary', array( __CLASS__, 'invoice_line_item_summary' ), 10, 4 ) ;
			add_action( 'getpaid_invoice_status_publish', array( __CLASS__, 'invoice_paid' ) );
			add_action( 'getpaid_invoice_status_wpi-renewal', array( __CLASS__, 'invoice_paid' ) );
			add_action( 'getpaid_invoice_status_changed', array( __CLASS__, 'invoice_unpaid' ), 10, 3 );
			add_action( 'adv_save_' . adv_zone_post_type(), array( __CLASS__, 'maybe_create_product' ) );
			add_action( 'adv_save_' . adv_ad_post_type(), array( __CLASS__, 'maybe_create_invoice' ), 10, 3 );
			add_action( 'adv_ad_status_td', array( __CLASS__, 'show_payment_status' ), 10, 2 );
			add_action( 'adv_ad_after_metabox_save', array( __CLASS__, 'handle_admin_saved_ad' ), 10, 2 );
			add_action( 'adv_ad_saved_successfully', array( __CLASS__, 'show_payment_action' ) );
			add_filter( 'adv_renew_ad_link', array( __CLASS__, 'renew_ad_link' ), 10, 2 );
			add_filter( 'wpinv_get_emails', array( __CLASS__, 'register_email_settings' ) );
			add_filter( 'getpaid_get_email_merge_tags', array( __CLASS__, 'add_email_merge_tags' ) );
			add_action( 'getpaid_template_default_template_path', array( __CLASS__, 'maybe_filter_default_template_path' ), 10, 2 );
		}

		parent::__construct();
	}

	public function get_currency() {
		return apply_filters( 'adv_get_currency', wpinv_get_currency() );
	}

	public function currency_position( $currency = '' ) {
		return apply_filters( 'adv_currency_position', wpinv_currency_position(), $currency );
	}

	public function currency_sign( $currency = '' ) {
		return apply_filters( 'adv_currency_sign', wpinv_currency_symbol( $currency ), $currency );
	}

	public function thousand_separator( $currency = '' ) {
		return apply_filters( 'adv_thousand_separator', wpinv_thousands_separator(), $currency );
	}

	public function decimal_separator( $currency = '' ) {
		return apply_filters( 'adv_decimal_separator', wpinv_decimal_separator(), $currency );
	}

	public function price_decimals() {
		return apply_filters( 'adv_price_decimals', wpinv_decimals() );
	}

	//Register a new wp invoicing item type
	public static function register_item_type(  $item_types  ){
		//Each zone is associated with a product
		$item_types['adv'] = __( 'Advertising Zone', 'advertising' );
		return $item_types;
	}


	//Make it easy for admins to associate invoices with ads and zones
	public static function admin_line_item_summary( $summary, $cart_item, $wpi_item, $invoice ) {

		//Ensure this item is associated with an ad
		if ( $wpi_item->get_type() == 'adv' && !empty( $cart_item['meta']['ad_id'] ) ) {
			$ad_name   = get_the_title( $cart_item['meta']['ad_id'] );
			$zone_name = get_the_title( $cart_item['meta']['zone_id'] );
			if ( current_user_can( 'manage_options' ) ) {
				$ad   = '<a href="' . get_edit_post_link( $cart_item['meta']['ad_id'] ) .'" target="_blank">' . $ad_name . '</a>';
				$zone = '<a href="' . get_edit_post_link( $cart_item['meta']['zone_id'] ) .'" target="_blank">' . $zone_name . '</a>';
			} else {
				$ad   = $ad_name;
				$zone = $zone_name;
			}
			$summary = wp_sprintf( __( "Advert: %s \nZone: %s ", 'advertising' ), $ad, $zone );
		}
		return $summary;
	}

	//Add a summary to the admin listing and user email
	public static function invoice_line_item_summary( $summary, $cart_item, $wpi_item, $invoice ) {
		if ( $wpi_item && $wpi_item->get_type() == 'adv' && !empty( $cart_item['meta']['ad_id'] ) ) {
			$ad   = get_the_title( $cart_item['meta']['ad_id'] );
			$zone = get_the_title( $cart_item['meta']['zone_id'] );

			$summary = wp_sprintf( __( "Advert: %s \nZone: %s ", 'advertising' ), $ad, $zone );
		}
		return $summary;
	}

	//Prompts the user to pay for the ad in case they havent
	public static function show_payment_status( $ad_id, $is_active ) {

		$invoice = adv_ad_get_meta( $ad_id, 'invoicing_invoice_id' );

		//Abort if the ad is active or has no invoice associated with it
		if( $is_active || !$invoice ) {
			return;
		}

		//Does the associated invoice exist...
		if(! $invoice = wpinv_get_invoice( $invoice ) ) {
			return;
		}

		//...and is it payable?
		if( $invoice->is_free() || $invoice->is_paid() ) {
			if ( 1 == get_post_meta( $ad_id, 'adv_repay', true ) ) {
				echo '<em>' . __('Expired', 'advertising') . '</em>';
			} else {
				echo '<em>' . __('Pending admin approval', 'advertising') . '</em>';
			}
			return;
		}

		//...ask the user to pay for it
		$url = $invoice->get_checkout_payment_url();
		printf(
			__('%sPay for ad%s', 'advertising'),
			"<a href='$url'>",
			'</a>'
		);
	}

	//Prompts the user to pay for the ad in case they havent
	public static function show_payment_action( $ad_id ) {
		global $adv_invoicing_saved_ad;

		if ( empty( $ad_id) && empty( $adv_invoicing_saved_ad ) ) {
			return;
		}

		if ( empty( $ad_id ) ) {
			$ad_id = $adv_invoicing_saved_ad;
		}
		$invoice = adv_ad_get_meta( $ad_id, 'invoicing_invoice_id' );

		// Abort if the ad is active or has no invoice associated with it
		if ( adv_ad_is_active( $ad_id ) || ! $invoice ) {
			return;
		}

		//Does the associated invoice exist...
		if(! $invoice = wpinv_get_invoice( $invoice ) ) {
			return;
		}

		//...and is it payable?
		if( $invoice->is_free() || $invoice->is_paid() ) {

			if ( 1 == get_post_meta( $ad_id, 'adv_repay', true ) ) {
				echo '<em>' . __('Expired', 'advertising') . '</em>';
			} else {
				echo '<em>' . __('Pending admin approval', 'advertising') . '</em>';
			}

			return;
		}

		//...ask the user to pay for it
		$url = $invoice->get_checkout_payment_url();
		printf(
			__('%sPay for ad%s', 'advertising'),
			"<div class='bsui'><a class='btn btn-success wpinv-submit' href='$url'>",
			'</a></div>'
		);
	}

	/**
	 * @param Adv_Ad $ad
	 */
	public static function renew_ad_link( $url, $ad ) {

		$new_invoice = wpinv_get_invoice( get_post_meta( $ad->ID, '_adv_ad_renew_ad_invoice', true ) );
		if ( empty( $new_invoice ) ) {
			$invoice = wpinv_get_invoice( adv_ad_get_meta( $ad->ID, 'invoicing_invoice_id' ) );

			if ( empty( $invoice ) ) {
				return $url;
			}

			$new_invoice = getpaid_duplicate_invoice( $invoice );
			$new_invoice->set_parent_id( $invoice->get_id() );
			$new_invoice->save();

			if ( ! $new_invoice->exists() ) {
				return $url;
			}

			update_post_meta( $ad->ID, '_adv_ad_renew_ad_invoice', $new_invoice->get_id() );
		}

		return $new_invoice->get_checkout_payment_url();

	}

	// Retrieves the product for a zone.
	public static function get_product( $zone ) {
		$item = wpinv_get_item_by( 'custom_id', $zone, 'adv' );
		if ( $item && $item->can_purchase() && $item->is_type( 'adv' ) ) {
			return $item;
		}

		return false;
	}


	//Creates a product and associates it with a zone
	public static function maybe_create_product( $zone ) {

		if(!$zone) {
			return;
		}

		//If there is an associated product update it
		if ( $item = self::get_product( $zone ) ) {
			$meta        	= array(
				'ID'		=> $item->get_id(),
				'title'     => get_the_title( $zone ),
				'price'     => adv_zone_get_meta( $zone, 'price', true, '0.00' ),
				'description' => get_the_excerpt( $zone ),
			);
			wpinv_update_item($meta );
			return;
		}

		$meta        	= array(
			'type'   	=> 'adv',
			'custom_id' => $zone,
			'title'     => get_the_title( $zone ),
     		'price'     => adv_zone_get_meta( $zone, 'price', true, '0.00' ),
     		'status'    => 'publish',
			'description' => get_the_excerpt( $zone ),
		);

		$item = wpinv_create_item( $meta );

		return;
	}

	//Creates an invoice and associates it with an ad
	public static function maybe_create_invoice( $ad, $zone = 0, $redirect = false ) {
		global $adv_invoicing_saved_ad;

		// Abort early if there is already an invoice.
		$invoice = adv_ad_get_meta( $ad, 'invoicing_invoice_id' );
		if ( $invoice && $invoice = wpinv_get_invoice( $invoice ) ){
			return;
		}

		// We use the zone to get a product for the ad.
		if ( empty( $zone ) ) {
			$zone = adv_ad_zone( $ad );
		}

		// attempt to create a product for the zone if non exists.
		if ( ! $item = self::get_product( $zone ) ) {

			//create the product...
			self::maybe_create_product( $zone );

			//...and abort if we can't
			if ( !  $item = wpinv_get_item_by( 'custom_id', $zone, 'adv' ) ){
				return;
			}

		}

		$customer_id = adv_ad_advertiser( $ad );

		// Create the invoice.
		$invoice = new WPInv_Invoice();
		$invoice->set_status( 'wpi-pending' );
		$invoice->set_customer_id( $customer_id );
		$invoice->created_via( 'gpa' );

		// Add the item.
		$qty          = get_post_meta( $ad, '_adv_ad_qty', true );
		$qty          = empty( $qty ) ? 1 : absint( $qty );
		$pricing_term = (int) adv_zone_get_meta( $zone, 'pricing_term' );
		$pricing_type = adv_zone_get_meta( $zone, 'pricing_type' );

		$_item = new GetPaid_Form_Item( $item->get_id() );
		$_item->set_price( adv_zone_get_meta( $zone, 'price', true, '0.00' ) );
		$_item->set_quantity( $qty );
		$_item->set_item_meta(
			array(
				'ad_id' => $ad,
				'zone_id' => $zone
			)
		);

		if ( empty( $pricing_term ) || empty( $pricing_type ) ) {
			$item_name = wp_sprintf( __( 'Ad: %s, %s', 'advertising' ), get_the_title( $ad ), get_the_title( $zone ) );
		} else {
			if ( $qty > 1 && $pricing_term > 1 ) {
				$item_name = wp_sprintf( __( 'Ad: %s, %s, %dx%d %s', 'advertising' ), get_the_title( $ad ), get_the_title( $zone ), $pricing_term, $qty, adv_get_pricing_type_title( $pricing_type ) );
			} else {
				$item_name = wp_sprintf( __( 'Ad: %s, %s, %d %s', 'advertising' ), get_the_title( $ad ), get_the_title( $zone ), $pricing_term, adv_get_pricing_type_title( $pricing_type ) );
			}
		}

		$_item->set_name( esc_html( $item_name ) );

		$possible_error = $invoice->add_item( $_item );

		if ( is_wp_error( $possible_error ) ) {
			return false;
		}

		// Try filling the default address.
		$address_data = wpinv_get_user_address( $customer_id );

		foreach ( $address_data as $key => $value ) {
			$method = "set_$key";

			if ( method_exists( $invoice, $method ) && $value != '' ) {
				$invoice->$method( wpinv_clean( $value ) );
			}
		}

		// Use current country as the invoice's country.
		$country = getpaid_get_ip_country();
		$country = empty( $country ) ? wpinv_get_default_country() : $country;
		$invoice->set_country( $country );

		$invoice->set_description( wp_sprintf( __( "Invoice for the Ad:'%s' in the zone '%s'"), sanitize_text_field( get_the_title( $ad ) ), sanitize_text_field( get_the_title( $zone ) ) ) );

		$invoice->recalculate_total();

		// Do not save free invoices.
		if ( $invoice->is_free() ) {
			return self::maybe_mark_ad_as_paid( $ad );
		}

		$invoice->save();

		// Abort if we were unable to save the invoice.
		if ( ! $invoice->exists() ) {
			return false;
		}

		update_post_meta( $ad, '_adv_ad_invoicing_invoice_id', $invoice->get_id() );

		$adv_invoicing_saved_ad = $ad;

		if ( $redirect ) {
			wp_redirect( $invoice->get_checkout_payment_url() );
			exit;
		}

		return true;
	}

	/**
     * Publishes an ad as soon as an invoice is paid.
     *
     * @param WPInv_Invoice $invoice
     */
	public static function invoice_paid( $invoice ) {

		// Retrieve the ad...
		$is_renewal = false;
		if ( ! $ad = adv_get_product_ad( $invoice->get_id(), 'invoicing_invoice_id' ) ) {
			if ( ! $ad = adv_get_product_ad( $invoice->get_id(), 'renew_ad_invoice' ) ) {
				return;
			}
			update_post_meta( $ad, '_adv_ad_invoicing_invoice_id', $invoice->get_id() );
			$is_renewal = true;
		}

		self::maybe_mark_ad_as_paid( $ad, $is_renewal );
	}

	/**
     * Publishes an ad as soon as an invoice is paid.
     *
     * @param int $ad
	 * @param bool $is_renewal
     */
	public static function maybe_mark_ad_as_paid( $ad, $is_renewal = false ) {

		$paid_status = adv_get_option( 'paid_ads_status' );
		$paid_status = empty( $paid_status ) ? 'publish' : trim( $paid_status );

		if ( 'code' == adv_ad_type( $ad ) ) {
			$paid_status = 'pending';
		}

		if ( $is_renewal ) {
			$paid_status = 'publish';
		}

		$args = array(
			'post_status' => $paid_status,
			'ID'		  => $ad,
		);
		wp_update_post( $args );

		delete_post_meta( $ad, 'adv_repay' );
		delete_post_meta( $ad, '_adv_ad_renew_ad_invoice' );

		if ( $is_renewal ) {
			$renewals    = (int) get_post_meta( $ad, 'adv_renewals', true ) + 1;
			update_post_meta( $ad, 'adv_renewals', $renewals );
		}

		// Update the payment date for use with the cpt payment type.
		update_post_meta( $ad, '_adv_ad_date_paid', current_time( 'timestamp' ) );

		if ( 'pending' === $paid_status ) {
			$invoice = adv_ad_get_meta( $ad, 'invoicing_invoice_id' );
			if ( $invoice && $invoice = wpinv_get_invoice( $invoice ) ) {
				self::send_gpa_ad_pending_review_email( $ad, $invoice );
			}
		}
	}

	/**
     * Unpublishes the invoice ad whenever an invoice status changes.
     * 
     * @param WPInv_Invoice $invoice
     * @param string $from
     * @param string $to
     */
	public static function invoice_unpaid( $invoice, $from, $to ) {

		// Abort if this is a renewal invoice or the previous status was not published.
        if ( $invoice->is_parent() || 'publish' != $from || 'publish' == $to ) {
            return;
        }

		// Retrieve the ad...
		if ( ! $ad = adv_get_product_ad( $invoice->get_id(), 'invoicing_invoice_id' ) ) {
			return;
		}

		//...then unpublish it
		adv_unpublish_ad( $ad );

	}

	public static function handle_admin_saved_ad( $ad_id ) {

		if ( empty( $_POST['adv_invoice_save_action'] ) || 'none' == $_POST['adv_invoice_save_action'] ) {
			return;
		}

		// Create ad.
		if ( 'generate' == $_POST['adv_invoice_save_action'] ) {
			self::maybe_create_invoice( $ad_id );
		}

		// Link existing ad.
		if ( 'link' == $_POST['adv_invoice_save_action'] && ! empty( $_POST['adv_linked_invoice'] ) ) {
			$invoice = wpinv_get_invoice( wpinv_clean( $_POST['adv_linked_invoice'] ) );

			if ( ! empty( $invoice ) ) {
				update_post_meta( $ad_id, '_adv_ad_invoicing_invoice_id', $invoice->get_id() );
			}

		}

	}

	/**
	 * Registers the stripe email settings.
	 *
	 * @since    1.0.0
	 * @param array $settings Current email settings.
	 */
	public static function register_email_settings( $settings ) {

		return array_merge(
			$settings,
			array(

				'gpa_ad_pending_review' => array(

					'email_gpa_ad_pending_review_header'  => array(
						'id'   => 'email_gpa_ad_pending_review_header',
						'name' => '<h3>' . __( 'Ad Pending Review (GetPaid Advertising)', 'advertising' ) . '</h3>',
						'desc' => __( 'These emails are sent to the site admin when an ad is pending review.', 'advertising' ),
						'type' => 'header',
					),

					'email_gpa_ad_pending_review_active'  => array(
						'id'   => 'email_gpa_ad_pending_review_active',
						'name' => __( 'Enable/Disable', 'advertising' ),
						'desc' => __( 'Enable this email notification', 'advertising' ),
						'type' => 'checkbox',
						'std'  => 0,
					),

					'email_gpa_ad_pending_review_subject' => array(
						'id'       => 'email_gpa_ad_pending_review_subject',
						'name'     => __( 'Subject', 'advertising' ),
						'desc'     => __( 'Enter the subject line for this email.', 'advertising' ),
						'help-tip' => true,
						'type'     => 'text',
						'std'      => __( '[{site_title}] Ad pending review', 'advertising' ),
						'size'     => 'large',
					),

					'email_gpa_ad_pending_review_heading' => array(
						'id'       => 'email_gpa_ad_pending_review_heading',
						'name'     => __( 'Email Heading', 'advertising' ),
						'desc'     => __( 'Enter the main heading contained within the email notification.', 'advertising' ),
						'help-tip' => true,
						'type'     => 'text',
						'std'      => __( 'Ad pending review', 'advertising' ),
						'size'     => 'large',
					),

					'email_gpa_ad_pending_review_body'    => array(
						'id'    => 'email_gpa_ad_pending_review_body',
						'name'  => __( 'Email Content', 'advertising' ),
						'desc'  => '',
						'type'  => 'rich_editor',
						'std'   => __( '<p>A new ad "<a href="{ad_link}">{ad_title}</a>" by {name} has been created on your site. <a class="btn btn-success" href="{ad_link}">Review Ad</a></p>', 'advertising' ),
						'class' => 'large',
						'size'  => '10',
					),

				),

			)
		);

	}

	/**
	 * Sends the pending review email.
	 *
	 * @param int $ad
	 * @param WPInv_Invoice $invoice
	 * @since 1.0.0
	 */
	public static function send_gpa_ad_pending_review_email( $ad, $invoice ) {
		$GLOBALS['gpa_current_ad'] = $ad;

		$email  = new GetPaid_Notification_Email( 'gpa_ad_pending_review', $invoice );
		$sender = getpaid()->get( 'invoice_emails' );
		$result = $sender->send_email( $invoice, $email, 'gpa_ad_pending_review', wpinv_get_admin_email() );

		unset( $GLOBALS['gpa_current_ad'] );

		return $result;
	}

	/**
	 * Adds email merge tags.
	 *
	 * @param array $merge_tags
	 */
	public static function add_email_merge_tags( $merge_tags ) {

		if ( ! empty( $GLOBALS['gpa_current_ad'] ) ) {
			$merge_tags['ad_link']  = add_query_arg( 'post', $GLOBALS['gpa_current_ad'], admin_url( 'post.php?action=edit' ) );
			$merge_tags['ad_title'] = get_the_title( $GLOBALS['gpa_current_ad'] );
		}

		return $merge_tags;
	}

	/**
	 * Filters the default template paths.
	 *
	 * @since 1.0.0
	 */
	public static function maybe_filter_default_template_path( $default_path, $template_name ) {

		$our_emails = array(
			'emails/wpinv-email-gpa_ad_pending_review.php',
		);

		if ( in_array( $template_name, $our_emails, true ) ) {
			return ADVERTISING_PLUGIN_DIR . 'templates';
		}

		return $default_path;
	}
}
