<?php
/**
 * Dynamic User Emails Log List Table class
 *
 * @author    AyeCode Ltd
 * @package   GeoDir_Dynamic_Emails
 * @version   2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * GeoDir_Dynamic_Emails_Log_List_Table class.
 */

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class GeoDir_Dynamic_Emails_Log_List_Table extends WP_List_Table {

	/**
	 * Initialize the webhook table list.
	 */
	public function __construct() {
		parent::__construct( array(
			'singular' => 'email-log',
			'plural'   => 'email-log',
			'ajax'     => false,
		) );
	}

	/**
	 * Get list columns.
	 *
	 * @return array
	 */
	public function get_columns() {
		$columns = array(
			'cb' => '<input type="checkbox" />',
			'date_sent' => __( 'Date', 'geodir-dynamic-emails' ),
			'name' => __( 'Email List', 'geodir-dynamic-emails' ),
			'action' => __( 'Event', 'geodir-dynamic-emails' ),
			'post_type' => __( 'Post Type', 'geodir-dynamic-emails' ),
			'user_roles' => __( 'User Roles', 'geodir-dynamic-emails' ),
			'stats' => __( 'Stats', 'geodir-dynamic-emails' )
		);

		return $columns;
	}

	/**
	 * Get a list of sortable columns for the list table.
	 *
	 * @since 2.0.0
	 *
	 * @return array Array of sortable columns.
	 */
	protected function get_sortable_columns() {
		$columns = array(
			'name' => array( 'name,email_list_id', false ),
			'action' => array( 'action,email_list_id', false ),
			'post_type' => array( 'post_type,email_list_id', false ),
			'date_sent' => array( 'date_sent,email_log_id', true ),
		);

		return $columns;
	}

	public function prepare_items() {
		$search = isset( $_REQUEST['s'] ) ? wp_unslash( trim( $_REQUEST['s'] ) ) : '';
		$action = isset( $_REQUEST['act'] ) ? sanitize_text_field( $_REQUEST['act'] ) : '';
		$post_type = isset( $_REQUEST['cpt'] ) ? sanitize_text_field( $_REQUEST['cpt'] ) : '';

		$per_page = $this->get_items_per_page( 'geodir_dynamic_emails_log_per_page' );

		$paged = $this->get_pagenum();

		$args = array(
			'number' => $per_page,
			'offset' => ( $paged - 1 ) * $per_page,
			'search' => $search,
			'fields' => 'all',
			'action' => $action,
			'post_type' => $post_type
		);

		if ( '' !== $args['search'] ) {
			$args['search'] = '*' . $args['search'] . '*';
		}

		if ( ! empty( $_REQUEST['orderby'] ) ) {
			$args['orderby'] = $_REQUEST['orderby'];
		} else {
			$args['orderby'] = 'date_sent,email_log_id';
		}

		if ( ! empty( $_REQUEST['order'] ) ) {
			$args['order'] = $_REQUEST['order'];
		} else {
			$args['order'] = 'desc';
		}

		$args = apply_filters( 'geodir_dynamic_emails_log_table_query_args', $args );

		$columns = $this->get_columns();
		$hidden = array();
		$sortable = $this->get_sortable_columns();

		// Column headers
		$this->_column_headers = array( $columns, $hidden, $sortable );

		$email_log_query = new GeoDir_Dynamic_Emails_Log_Query( $args );

		$this->items = $email_log_query->get_results();

		$this->set_pagination_args(
			array(
				'total_items' => $email_log_query->get_total(),
				'per_page' => $per_page,
			)
		);
	}

	/**
	 * Output 'no items' message.
	 *
	 * @since 2.0.0
	 */
	public function no_items() {
		_e( 'No items found.' );
	}

	/**
	 * Get bulk actions.
	 *
	 * @return array
	 */
	protected function get_bulk_actions() {
		return array(
			'delete' => __( 'Delete', 'geodir-dynamic-emails' )
		);
	}

	/**
	 * Generate the list table rows.
	 *
	 * @since 2.0.0
	 */
	public function display_rows() {
		foreach ( $this->items as $id => $object ) {
			echo "\n\t" . $this->single_row( $object, '', '', 0 );
		}
	}

	public function single_row( $item, $style = '', $role = '', $numposts = 0 ) {
		// Set up the hover actions for this item.
		$actions     = array();
		$checkbox    = '';
		$super_admin = '';

		// Set up the item editing link.
		$edit_link = admin_url( 'admin.php?page=gd-settings&tab=dynamic-emails&section=de-new-list&email_list=' . $item->email_list_id );

		$delete_link = add_query_arg(
			'wp_http_referer',
			urlencode( wp_unslash( $_SERVER['REQUEST_URI'] ) ),
			wp_nonce_url( "admin.php?page=gd-settings&tab=dynamic-emails&section=de-log&action=delete&email_log=$item->email_log_id", 'bulk-email-log' )
		);

		$edit = "<a title=\"" . esc_attr__( 'Edit Email List', 'geodir-dynamic-emails' ) . "\" href=\"{$edit_link}\">{$item->name}</a>";
		$actions['actionid'] = '<small class="text-dark">' . wp_sprintf( __( 'ID: %d', 'geodir-dynamic-emails' ), $item->email_log_id ) . '</small>';
		if ( $item->action == 'instant' && $item->status == 'publish' ) {
			$actions['resend'] = '<a class="geodir-de-resend" data-id="' . (int) $item->email_list_id . '" data-nonce="' . esc_attr( wp_create_nonce( 'geodir-de-send-list-' . (int) $item->email_list_id ) ) . '" href="javascript:void(0);" title="' . esc_attr( __( 'Re-send emails to this email list', 'geodir-dynamic-emails' ) ) . '"><i class="far fa-envelope" aria-hidden="true"></i><i class="fas fa-spinner fa-spin d-none"></i> ' . __( 'Re-Send Emails', 'geodir-dynamic-emails' ) . '</a>';
		}
		$actions['delete'] = "<a class='submitdelete text-danger' href='" . esc_url( $delete_link ) . "'><i class='far fa-trash-can' aria-hidden='true'></i> " . __( 'Delete', 'geodir-dynamic-emails' ) . '</a>';

		$actions = apply_filters( 'geodir_dynamic_emails_log_row_actions', $actions, $item );

		// Role classes.
		$role_classes = '';
		$date_format = str_replace( "F", "M", geodir_date_format() );

		// Set up the checkbox (because the item is editable, otherwise it's empty).
		$checkbox = wp_sprintf(
			'<label class="screen-reader-text" for="email_log_%1$s">%2$s</label>' .
			'<input type="checkbox" name="email_log[]" id="email_log_%1$s" class="%3$s" value="%1$s" />',
			$item->email_log_id,
			wp_sprintf( __( 'Select %s', 'geodir-dynamic-emails' ), $item->name ),
			$role_classes
		);

		$row = "<tr id='email_log-$item->email_log_id'>";

		list( $columns, $hidden, $sortable, $primary ) = $this->get_column_info();

		foreach ( $columns as $column_name => $column_display_name ) {
			$classes = "$column_name column-$column_name";
			if ( $primary === $column_name ) {
				$classes .= ' has-row-actions column-primary';
			}
			if ( in_array( $column_name, $hidden, true ) ) {
				$classes .= ' hidden';
			}

			$data = 'data-colname="' . esc_attr( wp_strip_all_tags( $column_display_name ) ) . '"';

			$attributes = "class='$classes' $data";

			if ( 'cb' === $column_name ) {
				$row .= "<th scope='row' class='check-column'>$checkbox</th>";
			} else {
				$row .= "<td $attributes>";
				switch ( $column_name ) {
					case 'id':
					case 'email_log_id':
						$row .= $item->email_log_id;
						break;
					case 'name':
						$row .= $edit . '<br><small class="text-dark">' . wp_sprintf( __( 'List ID: %d', 'geodir-dynamic-emails' ), $item->email_list_id ) . '</small>';
						break;
					case 'action':
						$row .= geodir_dynamic_emails_display_action( $item->action );
						break;
					case 'post_type':
						if ( geodir_dynamic_emails_action_supports( $item->action, 'post_type' ) ) {
							$row .= geodir_dynamic_emails_display_post_type( $item->post_type );
						} else {
							$row .= '-';
						}
						break;
					case 'user_roles':
						$row .= geodir_dynamic_emails_display_user_roles( $item->user_roles );
						break;
					case 'stats':
						$sent = (int) GeoDir_Dynamic_Emails_User::count_items_by( 'email_log_id', $item->email_log_id, 'sent' );
						$pending = GeoDir_Dynamic_Emails_User::count_items_by( 'email_log_id', $item->email_log_id, 'pending' );
						$row .= '<span>';
							$row .= '<span class="text-muted">'. wp_sprintf( __( 'Emails Sent: %s', 'geodir-dynamic-emails' ), '<span class="text-success fw-bold font-weight-bold">' . (int) $sent . '</span>' ) . '</span>';
							if ( $pending > 0 ) {
								$row .= '<br><span class="text-muted">'. wp_sprintf( __( 'In Queue: %s', 'geodir-dynamic-emails' ), '<span class="text-info fw-bold font-weight-bold">' . (int) $pending . '</span>' ) . '</span>';
							}
						$row .= '</span>';
						break;
					case 'date_sent':
						$date_sent = '-';
						if ( ! empty( $item->date_sent ) && $item->date_sent != '0000-00-00 00:00:00' ) {
							$date_sent = wp_sprintf( __( '%1$s at %2$s' ), date_i18n( $date_format, strtotime( $item->date_sent ) ), date_i18n( geodir_time_format(), strtotime( $item->date_sent ) ) );
						}

						$row .= $date_sent;
						break;
					default:
						$row .= apply_filters( 'geodir_dynamic_emails_manage_log_custom_column', '', $column_name, $item->email_log_id );
				}

				if ( $primary === $column_name ) {
					$row .= $this->row_actions( $actions );
				}
				$row .= '</td>';
			}
		}
		$row .= '</tr>';

		return $row;
	}

	/**
	 * Gets the name of the default primary column.
	 *
	 * @since 2.0.0
	 *
	 * @return string Name of the default primary column, in this case, 'name'.
	 */
	protected function get_default_primary_column_name() {
		return 'name';
	}

	/**
	 * @param string $which
	 */
	protected function extra_tablenav( $which ) {
		if ( 'top' !== $which ) {
			return;
		}

		ob_start();

		$this->email_action_dropdown( $which );
		$this->post_type_dropdown( $which );

		do_action( 'geodir_dynamic_emails_log_extra_tablenav', $which );

		$actions = ob_get_clean();

		if ( trim( $actions ) == '' ) {
			return;
		}
		?>
		<div class="alignleft actions">
		<?php
			echo $actions;

			submit_button( __( 'Filter' ), '', 'filter_action', false, array( 'id' => 'post-query-submit' ) );
		?>
		</div>
		<?php
	}

	protected function email_action_dropdown( $which ) {
		if ( false !== apply_filters( 'geodir_dynamic_emails_disable_actions_dropdown', false ) ) {
			return;
		}

		$actions = GeoDir_Dynamic_Emails_Email::email_actions();
		$action = ! empty( $_REQUEST['act'] ) ? sanitize_text_field( $_REQUEST['act'] ) : '';

		$dropdown = '<label class="screen-reader-text" for="act">' . __( 'Filter by Event', 'geodir-dynamic-emails' ) . '</label>';
		$dropdown .= '<select name="act" id="act" class="postform"><option value="">' . __( 'All Event Actions', 'geodir-dynamic-emails' ) . '</option>';
		foreach ( $actions as $value => $label ) {
			$dropdown .= '<option class="level-0" value="' . esc_attr( $value ) . '" ' . selected( $action == $value, true, false ) . '>' . esc_html( $label ) . '</option>';
		}
		$dropdown .= '</select>';
		echo $dropdown;
	}

	protected function post_type_dropdown( $which ) {
		if ( false !== apply_filters( 'geodir_dynamic_emails_disable_post_type_dropdown', false ) ) {
			return;
		}

		$options = geodir_get_posttypes( 'options-plural' );
		$post_type = ! empty( $_REQUEST['cpt'] ) ? sanitize_text_field( $_REQUEST['cpt'] ) : '';

		$dropdown = '<label class="screen-reader-text" for="cpt">' . __( 'Filter by Post Type', 'geodir-dynamic-emails' ) . '</label>';
		$dropdown .= '<select name="cpt" id="cpt" class="postform"><option value="">' . __( 'All Post Types', 'geodir-dynamic-emails' ) . '</option>';
		foreach ( $options as $value => $label ) {
			$dropdown .= '<option class="level-0" value="' . esc_attr( $value ) . '" ' . selected( $post_type == $value, true, false ) . '>' . esc_html( $label ) . '</option>';
		}
		$dropdown .= '</select>';
		echo $dropdown;
	}
}
