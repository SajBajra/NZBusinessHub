<?php
/**
 * Contains plugin functions.
 *
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Retrieves an option value.
 *
 * @param string $key Option key to retrieve.
 * @param mixed $default The default value.
 * @return mixed
 */
function geodir_tickets_get_option( $key, $default = null ) {
	$options = get_option( 'geodir_tickets' );

	if ( empty( $options ) ) {
		$options = array();
	}

	return isset( $options[ $key ] ) ? $options[ $key ] : $default;
}

/**
 * Retrieves the ticket commision %ge.
 *
 * @return float
 */
function geodir_tickets_get_commision_percentage() {
    return min( 100, floatval( geodir_tickets_get_option( 'commision', 10 ) ) );
}

/**
 * Counts tickets in each status.
 *
 * @return array
 */
function geodir_get_tickets_status_counts( $args = array() ) {

	$statuses = array_keys( geodir_get_ticket_statuses() );
	$counts   = array();

	foreach ( $statuses as $status ) {
		$_args             = wp_parse_args( "status=$status", $args );
		$counts[ $status ] = geodir_get_tickets( $_args, 'count' );
	}

	return $counts;

}

/**
 * Returns an array of valid ticket statuses.
 *
 * @return array
 */
function geodir_get_ticket_statuses() {

	return apply_filters(
		'geodir_get_ticket_statuses',
		array(
			'pending'    => __( 'Pending', 'geodir-tickets' ),
			'confirmed'  => __( 'Confirmed', 'geodir-tickets' ),
			'used'       => __( 'Used', 'geodir-tickets' ),
			'refunded'   => __( 'Refunded', 'geodir-tickets' ),
		)
	);

}

/**
 * Get ticket status name.
 *
 * @since 2.1.2
 *
 * @return string
 */
function geodir_ticket_status_name( $status ) {
	$statuses = geodir_get_ticket_statuses();

	return isset( $statuses[ $status ] ) ? $statuses[ $status ] : $status;
}

/**
 * Queries the tickets database.
 *
 * @param array $args Query arguments.For a list of all supported args, refer to GeoDir_Tickets_Query::prepare_query()
 * @param string $return 'results' returns the found tickets, $count returns the total count while 'query' returns GeoDir_Tickets_Query
 *
 *
 * @return int|array|GeoDir_Ticket[]|GeoDir_Tickets_Query
 */
function geodir_get_tickets( $args = array(), $return = 'results' ) {

	// Do not retrieve all fields if we just want the count.
	if ( 'count' == $return ) {
		$args['fields'] = 'id';
		$args['number'] = 1;
	}

	// Do not count all matches if we just want the results.
	if ( 'results' == $return ) {
		$args['count_total'] = false;
	}

	$query = new GeoDir_Tickets_Query( $args );

	if ( 'results' == $return ) {
		return $query->get_results();
	}

	if ( 'count' == $return ) {
		return $query->get_total();
	}

	return $query;
}

/**
 * Converts a string into a QR Code
 *
 * @param string $ticket_number The string to generate a QR code for.
 *
 * @return string QR Code table markup
 */
function geodir_print_ticket_qr_code( $ticket_number ) {
	require_once 'qrcode.php';

	// Prepare library.
	$qr = geodir_tickets\QRCode::getMinimumQRCode( $ticket_number );
	$qr->printSVG(4);

}

/**
 * Returns ticket dates.
 *
 * @return string|array -> String if is single-day event, array of start and end dates otherwise.
 */
function geodir_get_invoice_ticket_dates( $invoice_id, $event_id ) {
	global $wpdb, $geodir_ticket_events;

	if ( empty( $geodir_ticket_events ) ) {
		$geodir_ticket_events = array();
	}

	$key = (int) $invoice_id . '::' . (int) $event_id;

	if ( isset( $geodir_ticket_events[ $key ] ) ) {
		return $geodir_ticket_events[ $key ];
	}

	$start_date = get_post_meta( $invoice_id, 'geodir_tickets_date', true );

	if ( empty( $start_date ) ) {
		$geodir_ticket_events[ $key ] = $start_date;

		return '';
	}

	if ( empty( $event_id ) ) {
		$geodir_ticket_events[ $key ] = $start_date;

		return $start_date;
	}

	$table    = constant( 'GEODIR_EVENT_SCHEDULES_TABLE' );
	$schedule = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE event_id=%d AND start_date=%s LIMIT 1", array( $event_id, $start_date ) ) );

	if ( empty( $schedule ) ) {
		$geodir_ticket_events[ $key ] = $start_date;

		return $start_date;
	}

	$value = array( $schedule->start_date, $schedule->end_date, $schedule->start_time, $schedule->end_time );

	$geodir_ticket_events[ $key ] = $value;

	return $value;
}

/**
 * Checks the date validity of an event.
 *
 * @return string
 */
function geodir_check_invoice_ticket_dates( $dates, $invoice_id ) {

	if ( is_string( $dates ) ) {
		$dates = array( $dates, $dates );
	}

	$used_dates = get_post_meta( $invoice_id, 'geodir_tickets_used_dates', true );

	if ( empty( $used_dates ) ) {
		$used_dates = array();
	}

	// Abort if this is a past event.
	if ( strtotime( $dates[1] . ' 23:59:59' ) < time() ) {
		return 'expired';
	}

	// Check if this is an ongoing event.
	if ( ( strtotime( $dates[0] . ' 00:00:00' ) <= time() ) && ( time() <= strtotime( $dates[1] . ' 23:59:59' ) ) ) {

		if ( in_array( date( 'Y-m-d' ), $used_dates ) ) {
			return 'used';
		}

		return 'ongoing';
	}

	// This is a future event.
	return 'future';
}

/**
 * Event listing merge tags.
 *
 * @param WPInv_Invoice $invoice Customer invoice.
 * @param int $event_id
 *
 * @return array()
 */
function geodir_get_ticket_event_merge_tags( $invoice, $event_id ) {
	global $post, $gd_post;

	$old_post   = $post;
	$old_gdpost = $gd_post;
	$event      = get_post( $event_id );
	$post       = $event;
	$gd_post    = geodir_get_post_info( $event->ID );
	$author     = get_userdata( $event->post_author );
	$date       = geodir_get_invoice_ticket_dates( $invoice->get_id(), $event_id );

	if ( is_array( $date ) ) {
		$date = sprintf(
			'%s - %s',
			esc_html( getpaid_format_date( $date[0] ) ),
			esc_html( getpaid_format_date( $date[1] ) )
		);
	} else {
		$date = esc_html( getpaid_format_date( $date ) );
	}

	$merge_tags = array(
		'{SITE_TITLE}'           => esc_html( wpinv_get_blogname() ),
		'{DATE}'                 => esc_html( getpaid_format_date_value( current_time( 'mysql' ) ) ),
		'{CUSTOMER_NAME}'        => esc_html( $invoice->get_customer_full_name() ),
		'{FIRST_NAME}'           => esc_html( $invoice->get_customer_first_name() ),
		'{LAST_NAME}'            => esc_html( $invoice->get_customer_last_name() ),
		'{EMAIL}'                => esc_html( $invoice->get_customer_email() ),
		'{INVOICE_NUMBER}'       => esc_html( $invoice->get_number() ),
		'{INVOICE_TOTAL}'        => wpinv_price( $invoice->get_total(), $invoice->get_currency() ),
		'{INVOICE_LINK}'         => esc_url_raw( $invoice->get_view_url() ),
		'{INVOICE_RECEIPT_LINK}' => esc_url_raw( $invoice->get_receipt_url() ),
		'{INVOICE_DATE}'         => esc_html( $invoice->get_date_created() ),
		'{INVOICE_DESCRIPTION}'  => $invoice->get_description(),
		'{POST_AUTHOR}'          => esc_html( $author->display_name ),
		'{POST_URL}'             => esc_url_raw( get_the_permalink( $event->ID ) ),
		'{event_name}'           => esc_html( get_the_title( $event->ID ) ),
		'{EVENT_DATE}'           => $date,
	);

	$package_id = geodir_get_post_package_id( $event->ID, $event->post_type );
	$fields     = geodir_post_custom_fields( $package_id, 'all', $event->post_type, 'none' );

	foreach ( $fields as $field ) {

		if ( isset( $field['extra_fields'] ) ) {
			$extra_fields = $field['extra_fields'];
		}

		$field = stripslashes_deep( $field ); // strip slashes

		if ( isset( $field['extra_fields'] ) ) {
			$field['extra_fields'] = $extra_fields;
		}

		$field_type  = $field['type'];
		$html_var    = isset( $field['htmlvar_name'] ) ? $field['htmlvar_name'] : '';
		if ( $html_var == 'post' ) {
			$html_var = 'post_address';
		}

		/**
		 * Filter the output for custom fields.
		 *
		 * Here we can remove or add new functions depending on the field type.
		 *
		 * @param string $html The html to be filtered (blank).
		 * @param string $fields_location The location the field is to be show.
		 * @param array $type The array of field values.
		 */
		$html = apply_filters( "geodir_custom_field_output_{$field_type}", '', 'listing', $field, $gd_post, 'value' );

		if ( 'event' === $field_type ) {
			$html = str_replace( array( '<div', '</div>' ), array( '<span', '</span>' ), $html );
		}

		$variables_array = array();


		if ( $field['type'] != 'fieldset' ):
			$variables_array['post_id'] = ! empty( $event->ID ) ? $event->ID : null;
			$variables_array['label']   = __( $field['frontend_title'], 'geodirectory' );
			$variables_array['value']   = '';
			if ( isset( $event->{$field['htmlvar_name']} ) ) {
				$variables_array['value'] = $event->{$field['htmlvar_name']};
			}
		endif;

		$field_value = apply_filters( "geodir_show_{$html_var}", $html, $variables_array );
		$field_value = preg_replace( '#(<span class="geodir_post_meta_title.*?>).*?(</span>)#', '', $field_value );
		if ( ! empty( $field_value ) ) {
			preg_match( '#(<div class="geodir_post_meta.*?>)(.*?)(</div>)#', $field_value, $matches );

			if ( ! empty( $matches ) && ! empty( $matches[2] ) ) {
				$field_value = trim( $matches[2] );
			}
		}
		$merge_tags['{' . $html_var . '}' ] = $field_value;
	}

	$post    = $old_post;
	$gd_post = $old_gdpost;

	foreach ( $merge_tags as $key => $value ) {
		$merge_tags[ strtoupper( $key ) ] = $value;
		$merge_tags[ strtolower( $key ) ] = $value;
	}

	return $merge_tags;
}

/**
 * Checks whether or not we should display a download button.
 *
 * @param WPInv_Invoice $invoice
 */
function geodir_tickets_should_display_download_button( $invoice ) {

	if ( ! wpinv_user_can_view_invoice( $invoice ) || ! $invoice->is_paid() ) {
		return false;
	}

	$filtered = array();
	$tickets  = geodir_get_tickets(
		array(
			'invoice_in' => array( $invoice->get_id() ),
		)
	);

	/** @var GeoDir_Ticket $ticket */
	foreach ( $tickets as $ticket ) {
		$event = get_post( $ticket->get_event_id() );

		if ( $event && 'publish' == $event->post_status ) {
			$filtered[] = $event;
		}

	}

    return ! empty( $filtered );
}

/**
 * Displays the tickets download button.
 *
 *  @param WPInv_Invoice $invoice
 */
function geodir_tickets_display_download_button( $invoice ) {

    ?>

    <?php if ( geodir_tickets_should_display_download_button( $invoice ) && ( $download_url = geodir_tickets_get_download_url( $invoice ) ) ): ?>
        <a class="btn btn-sm btn-outline btn-purple m-1 d-inline-block invoice-action-tickets" href="<?php echo esc_url( $download_url ); ?>">
            <?php _e( 'View Tickets', 'geodir-tickets' ); ?>
        </a>
    <?php endif; ?>

    <?php

}
add_action( 'wpinv_invoice_display_left_actions', 'geodir_tickets_display_download_button' );

/**
 * Displays the tickets download action.
 *
 * @param array $actions post actions
 * @param WP_Post $post
 * @return array $actions actions
 */
function geodir_tickets_filter_invoice_row_actions( $actions, $post ) {

    if ( getpaid_is_invoice_post_type( $post->post_type ) ) {

        $invoice = new WPInv_Invoice( $post );

        if ( geodir_tickets_should_display_download_button( $invoice ) && ( $download_url = geodir_tickets_get_download_url( $invoice ) ) ) {

            $actions['tickets'] =  sprintf(
                '<a href="%1$s">%2$s</a>',
                esc_url( $download_url ),
                esc_html( __( 'View Tickets', 'geodir-tickets' ) )
            );

        }

    }

    return $actions;

}
add_action( 'post_row_actions', 'geodir_tickets_filter_invoice_row_actions', 100, 2 );

/**
 * Displays the tickets download button when viewing invoices on frontend.
 *
 * @param array $actions post actions
 * @param WPInv_Invoice $invoice
 * @return array $actions actions
 */
function geodir_tickets_filter_invoice_frontend_actions( $actions, $invoice ) {

    if ( geodir_tickets_should_display_download_button( $invoice ) && ( $download_url = geodir_tickets_get_download_url( $invoice ) ) ) {

		$actions['tickets'] = array(
			'url'   => esc_url( $download_url ),
			'name'  => __( 'View Tickets', 'geodir-tickets' ),
			'class' => 'btn-purple'
		);

	}

    return $actions;

}
add_action( 'wpinv_user_invoices_actions', 'geodir_tickets_filter_invoice_frontend_actions', 10, 2 );
add_action( 'wpinv_invoice_receipt_actions', 'geodir_tickets_filter_invoice_frontend_actions', 10, 2 );

/**
 * Displays the pdf download button when editing an invoice.
 *
 * @param array $actions post actions
 * @param WPInv_Invoice $invoice
 */
function geodir_tickets_filter_invoice_edit_actions( $actions, $invoice ) {

	if ( geodir_tickets_should_display_download_button( $invoice ) && ( $download_url = geodir_tickets_get_download_url( $invoice ) ) ) {

		$actions['tickets'] = array(
			'url'   => $download_url,
			'label' => __( 'View Tickets', 'geodir-tickets' ),
			'class' => 'btn-purple'
		);

	}

    return $actions;

}
add_action( 'getpaid_edit_invoice_actions', 'geodir_tickets_filter_invoice_edit_actions', 10, 2 );

/**
 * Returns the tickets download link.
 *
 *  @param WPInv_Invoice $invoice
 */
function geodir_tickets_get_download_url( $invoice ) {
	return add_query_arg(
		array(
			'invoice_key' => $invoice->get_key(),
			'geodir_ticket_embed' => 1,
		),
		home_url()
	);
}

/**
 * Function for display widget content.
 *
 * @since 2.0.0
 *
 * @param array $instance {
 *      An array display widget arguments.
 *
 * @type string $gd_wgt_showhide Widget display type.
 * @type string $gd_wgt_restrict Widget restrict pages.
 * }
 *
 * @param WP_Widget $widget Display widget options.
 * @param array $args Widget arguments.
 *
 * @return bool|array $instance
 */
function geodir_tickets_widget_display_callback( $instance, $widget, $args ) {

	if ( ! is_admin() && ! empty( $widget->widget_options['gd_is_tickets'] ) ) {

		if ( ! empty( $instance['id'] ) ) {

			// Ensure the post supports events.
			$listing = get_post( $instance['id'] );

			if ( empty( $listing ) || 'publish' !== $listing->post_status || ! GeoDir_Post_types::supports( $listing->post_type, 'events' ) ) {
				$instance = false;
			}

		} else if ( is_singular() ) {

			if ( ! get_post_type() || ! GeoDir_Post_types::supports( get_post_type(), 'events' ) ){
				$instance = false;
			}

		} else {

			$instance = false;
		}

	}

	return $instance;
}

add_filter( 'widget_display_callback', 'geodir_tickets_widget_display_callback', 10, 3 );

/**
 * Displays event tickets.
 *
 * @return string
 */
function geodir_ticket_filter_embed_template( $template ) {

    if ( isset( $_GET['geodir_ticket_embed'] ) ) {
        wpinv_get_template( 'tickets/base.php' );
        exit;
    }

    return $template;
}
add_filter( 'template_include', 'geodir_ticket_filter_embed_template' );

function geodir_ticket_filter_report_graphs( $graphs ) {

	$ticket_graphs = array(
		'tickets_sold'       => __( 'Sold Tickets', 'geodir-tickets' ),
		'tickets_earnings'   => __( 'Ticket Revenue', 'geodir-tickets' ),
		'tickets_commisions' => __( 'Ticket Commissions', 'geodir-tickets' ),
	);

	if ( isset( $_GET['page' ] ) && 'geodir-tickets-reports' === $_GET['page' ] ) {
		return $ticket_graphs;
	}

	return array_merge( $graphs, $ticket_graphs );

}
add_filter( 'getpaid_report_graphs', 'geodir_ticket_filter_report_graphs' );

function geodir_ticket_filter_report_cards( $cards ) {

	$ticket_cards = array(
		'period_ticket_sales'       => array(
			'description' => __( 'Total number of ticket sales in this period.', 'geodir-tickets' ),
			'label'       => __( 'Tickets Sold', 'geodir-tickets' ),
		),
		'period_ticket_earnings'   => array(
			'description' => __( 'Total amount of ticket revenue in this period.', 'geodir-tickets' ),
			'label'       => __( 'Ticket Revenue', 'geodir-tickets' ),
		),
		'period_ticket_commisions' => array(
			'description' => __( 'Total amount of ticket commisions in this period.', 'geodir-tickets' ),
			'label'       => __( 'Ticket Commissions', 'geodir-tickets' ),
		),
	);

	if ( isset( $_GET['page' ] ) && 'geodir-tickets-reports' === $_GET['page' ] ) {
		return $ticket_cards;
	}

	return array_merge( $cards, $ticket_cards );

}
add_filter( 'wpinv_report_cards', 'geodir_ticket_filter_report_cards' );

function geodir_ticket_filter_report_data( $data, $controller ) {
	global $wpdb;

	if ( 'day' === $controller->groupby ) {
		$group_by = "YEAR(date_created), MONTH(date_created), DAY(date_created)";
	} else {
		$group_by = "YEAR(date_created), MONTH(date_created)";
	}

	$where = 'status != "pending"';

	if ( ! empty( $controller->report_range['before'] ) ) {
		$where .= "
			AND 	date_created < '" . date( 'Y-m-d 23:59:59', strtotime( $controller->report_range['before'] ) ) . "'
		";
	}

	if ( ! empty( $controller->report_range['after'] ) ) {
		$where .= "
			AND 	date_created > '" . date( 'Y-m-d 23:59:59', strtotime( $controller->report_range['after'] ) ) . "'
		";
	}

	$data->tickets_sold = (array) $wpdb->get_results( "SELECT date_created as date, COUNT(id) as val FROM {$wpdb->prefix}geodir_tickets WHERE $where GROUP BY $group_by ORDER BY date_created ASC" );

	$data->tickets_earnings = (array) $wpdb->get_results( "SELECT date_created as date, SUM(price) as val FROM {$wpdb->prefix}geodir_tickets WHERE $where GROUP BY $group_by ORDER BY date_created ASC" );

	$data->tickets_commisions = (array) $wpdb->get_results("SELECT date_created as date, SUM(site_commision) as val FROM {$wpdb->prefix}geodir_tickets WHERE $where GROUP BY $group_by ORDER BY date_created ASC" );

	// Cards.
	$data->period_ticket_sales      = (int) $wpdb->get_var("SELECT COUNT(id) FROM {$wpdb->prefix}geodir_tickets WHERE $where" );
	$data->period_ticket_earnings   = (float) $wpdb->get_var("SELECT SUM(price) FROM {$wpdb->prefix}geodir_tickets WHERE $where" );
	$data->period_ticket_commisions = (float) $wpdb->get_var("SELECT SUM(site_commision) FROM {$wpdb->prefix}geodir_tickets WHERE $where" );

	return $data;

}
add_filter( 'getpaid_rest_api_filter_report_data', 'geodir_ticket_filter_report_data', 10, 2 );

/**
 * Tell AyeCode UI to load on certain admin pages.
 *
 * @param $screen_ids
 *
 * @return array
 */
function geodir_tickets_add_aui_screens($screen_ids){
	$screen_ids[] = 'tickets_page_geodir-tickets-reports';
    return $screen_ids;
}
add_filter('wpinv_screen_ids','geodir_tickets_add_aui_screens');

function geodir_tickets_filter_report_views( $views ) {
	require_once plugin_dir_path( __FILE__ ) . 'class-geodir-tickets-report-events.php';
	require_once plugin_dir_path( __FILE__ ) . 'class-geodir-tickets-report-event-sales.php';

	$views[ 'events' ] = array(
		'label' => __( 'Popular Events', 'geodir-tickets' ),
		'class' => 'GeoDir_Tickets_Report_Events',
		'disable-downloads' => true,
	);

	$views[ 'event_revenue' ] = array(
		'label' => __( 'Highest Revenue Events', 'geodir-tickets' ),
		'class' => 'GeoDir_Tickets_Report_Event_Sales',
		'disable-downloads' => true,
	);

    return $views;
}
add_filter('wpinv_report_views','geodir_tickets_filter_report_views');

/**
 * Checks if all tickets are in stock.
 */
function geodir_tickets_are_all_sold_out( $item_ids ) {
	global $getpaid_item_inventory;

	$item_ids = wp_parse_id_list( $item_ids );

	foreach ( $item_ids as $item_id ) {

		if ( $getpaid_item_inventory->inventory->is_in_stock( $item_id ) ) {
			return false; // Abort early.
		}

	}

	return true;
}

/**
 * Get the event formatted date.
 *
 * @since 2.1.2
 *
 * @param  string $start_date Start date.
 * @param  string $end_date End date.
 * @return string Formatted date.
 */
function geodir_ticket_format_event_date( $start_date, $end_date = '' ) {
	if ( empty( $end_date ) ) {
		return $start_date ? wp_date( 'M j, Y', strtotime( $start_date ) ) : '';
	}

	$i_start_date = strtotime( $start_date );
	$i_end_date = strtotime( $end_date );

	$start_y = wp_date( 'Y', $i_start_date );
	$start_m = wp_date( 'M', $i_start_date );
	$start_d = wp_date( 'j', $i_start_date );
	$end_y = wp_date( 'Y', $i_end_date );
	$end_m = wp_date( 'M', $i_end_date );
	$end_d = wp_date( 'j', $i_end_date );

	$output = '';

	if ( $start_y == $end_y ) {
		if ( $start_m == $end_m ) {
			$output = $start_m;
			$output .= ' ' . $start_d;

			if ( $start_d != $end_d ) {
				$output .= '-' . $end_d;
			}
		} else {
			$output = $start_m . ' ' . $start_d . '-' . $end_m . ' ' . $end_d;
		}

		$output .= ', ' . $start_y;
	} else {
		$output = $start_m . ' ' . $start_d . ', ' . $start_y .  '-' . $end_m . ' ' . $end_d . ', ' . $end_y;
	}

	return $output;
}

/**
 * Get the event formatted time.
 *
 * @since 2.1.2
 *
 * @param  string $start_time Start time.
 * @param  string $end_time End time.
 * @return string Formatted time.
 */
function geodir_ticket_format_event_time( $start_time = '', $end_time = '' ) {
	if ( empty( $end_time ) ) {
		return $start_time ? wp_date( 'g:i A', strtotime( $start_time ) ) : '';
	}

	$output = '';

	if ( ! ( strpos( $start_time, '00:00' ) === 0 && strpos( $end_time, '00:00' ) === 0 ) ) {
		$_start_time = wp_date( 'g:i A', strtotime( $start_time ) );
		$_end_time = wp_date( 'g:i A', strtotime( $end_time ) );

		$output = $_start_time . '-' . $_end_time;
	}

	return $output;
}