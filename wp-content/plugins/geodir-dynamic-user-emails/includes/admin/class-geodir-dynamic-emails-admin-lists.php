<?php
/**
 * Dynamic User Emails Admin Lists class
 *
 * @author    AyeCode Ltd
 * @package   GeoDir_Dynamic_Emails
 * @version   2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * GeoDir_Dynamic_Emails_Admin_Lists class.
 */
class GeoDir_Dynamic_Emails_Admin_Lists {

	/**
	 * Initialize the cities admin actions.
	 */
	public function __construct() {
		$this->actions();
		$this->notices();
	}

	/**
	 * Check if is cities settings page.
	 * @return bool
	 */
	private function is_settings_page() {
		return isset( $_GET['page'] ) && 'gd-settings' === $_GET['page'] && isset( $_GET['tab'] ) && 'dynamic-emails' === $_GET['tab'] && ( ( isset( $_GET['section'] ) && 'de-lists' === $_GET['section'] ) || empty( $_GET['section'] ) );
	}

	public static function current_action() {
		if ( ! empty( $_GET['action'] ) && $_GET['action'] != -1 ) {
			return $_GET['action'];
		} else if ( ! empty( $_GET['action2'] ) ) {
			return $_GET['action2'];
		}

		return NULL;
	}

	/**
	 * Page output.
	 */
	public static function page_output() {
		// Hide the save button
		$GLOBALS['hide_save_button'] = true;

		self::table_list_output();
	}

	/**
	 * Cities admin actions.
	 */
	public function actions() {
		if ( $this->is_settings_page() ) {
			// Bulk actions
			if ( $this->current_action() && ! empty( $_GET['email_list'] ) ) {
				$this->bulk_actions();
			}
		}
	}

	/**
	 * Bulk actions.
	 */
	private function bulk_actions() {
		if ( ! ( ! empty( $_REQUEST['_wpnonce'] ) && ( wp_verify_nonce( $_REQUEST['_wpnonce'], 'bulk-email-lists' ) || wp_verify_nonce( $_REQUEST['_wpnonce'], 'geodirectory-settings' ) ) ) ) {
			wp_die( __( 'Action failed. Please refresh the page and retry.', 'geodir-dynamic-emails' ) );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'Sorry, you are not allowed to perform this action.', 'geodir-dynamic-emails' ) );
		}

		$ids = array_map( 'absint', (array) $_GET['email_list'] );

		$sendback = wp_get_referer();

		if ( ! $sendback ) {
			$sendback = admin_url( 'admin.php?page=gd-settings&tab=dynamic-emails' );
		} else {
			$sendback = remove_query_arg( array( 'duplicated', 'removed', 'deleted', 'ids' ), $sendback );
		}

		if ( 'delete' == $this->current_action() ) {
			if ( empty( $ids ) ) {
				wp_die( __( 'Select at-least one item to delete.', 'geodir-dynamic-emails' ) );
			}

			$count = 0;
			if ( ! empty( $ids ) ) {
				foreach ( $ids as $id ) {
					if ( GeoDir_Dynamic_Emails_List::delete_item( (int) $id ) ) {
						$count++;
					}
				}
			}

			$sendback = add_query_arg( 'removed', $count, $sendback );

			wp_redirect( $sendback );
			exit;
		} else if ( 'duplicate' == $this->current_action() ) {
			if ( empty( $ids ) ) {
				wp_die( __( 'Select at-least one item to duplicate.', 'geodir-dynamic-emails' ) );
			}

			$count = 0;
			if ( ! empty( $ids ) ) {
				foreach ( $ids as $id ) {
					if ( GeoDir_Dynamic_Emails_List::duplicate( (int) $id ) ) {
						$count++;
					}
				}
			}

			$sendback = add_query_arg( 'duplicated', $count, $sendback );

			wp_redirect( $sendback );
			exit;
		}
	}

	/**
	 * Notices.
	 */
	public static function notices() {
		if ( isset( $_GET['removed'] ) ) {
			if ( ! empty( $_GET['removed'] ) ) {
				$count = absint( $_GET['removed'] );
				$message = wp_sprintf( _n( 'Item deleted successfully.', '%d items deleted successfully.', $count, 'geodir-dynamic-emails' ), $count );
			} else {
				$message = __( 'No item deleted.', 'geodir-dynamic-emails' );
			}
			GeoDir_Admin_Settings::add_message( $message );
		} else if ( isset( $_GET['duplicated'] ) ) {
			if ( ! empty( $_GET['duplicated'] ) ) {
				$count = absint( $_GET['duplicated'] );
				$message = wp_sprintf( _n( 'Item duplicated successfully.', '%d items duplicated successfully.', $count, 'geodir-dynamic-emails' ), $count );
			} else {
				$message = __( 'No item duplicated.', 'geodir-dynamic-emails' );
			}
			GeoDir_Admin_Settings::add_message( $message );
		}
	}

	/**
	 * Table list output.
	 */
	private static function table_list_output() {
		ob_start();
		echo '<div class="bsui"><h2 class="wp-heading-inline d-inline-block mt-3 mr-2 me-2">' . __( 'Email Lists', 'geodir-dynamic-emails' ) . '</h2> <a href="' . esc_url( admin_url( 'admin.php?page=gd-settings&tab=dynamic-emails&section=de-new-list' ) ) . '" class="page-title-action">' . __( 'Add New List', 'geodir-dynamic-emails' ) . '</a></div>';

		GeoDir_Admin_Settings::show_messages();

		$_SERVER['REQUEST_URI'] = remove_query_arg( array( '_wp_http_referer', '_wpnonce', 'action', 'action2', 'paged' ), wp_unslash( $_SERVER['REQUEST_URI'] ) ); // WPCS: input var ok, CSRF ok, sanitization ok.

		$table_list = new GeoDir_Dynamic_Emails_Lists_List_Table();
		$table_list->prepare_items();
		echo '<div class="geodir-email-lists">';
		echo '<input type="hidden" name="page" value="gd-settings" />';
		echo '<input type="hidden" name="tab" value="dynamic-emails" />';
		echo '<input type="hidden" name="section" value="" />';

		$table_list->views();
		$table_list->search_box( __( 'Search Email List', 'geodir-dynamic-emails' ), 'email_list' );
		$table_list->display();
		echo '<style>p.search-box{margin-bottom:0!important}.tablenav .actions > select{line-height:28px}.wp-list-table .column-id{width:60px}.wp-list-table .column-post_type{width:140px}.gd-left-0{left:0!important;}</style>';
		echo '</div>';
		$output = ob_get_clean();

		echo $output;
	}
}

new GeoDir_Dynamic_Emails_Admin_Lists();
