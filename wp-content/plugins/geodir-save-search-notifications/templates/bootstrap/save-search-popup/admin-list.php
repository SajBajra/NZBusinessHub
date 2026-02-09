<?php
/**
 * Saved Search Admin List
 *
 * This template can be overridden by copying it to yourtheme/geodirectory/bootstrap/save-search-popup/admin-list.php.
 *
 * HOWEVER, on occasion GeoDirectory will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see       https://wpgeodirectory.com/documentation/article/how-tos/customizing-templates/
 * @package   GeoDir_Save_Search
 * @version   2.1.4
 */

defined( 'ABSPATH' ) || exit;

global $aui_bs5;

?>
<div class="geodir-saved-search-list geodir-ss-table">
	<?php if ( empty( $items ) ) { ?>
	<p><?php _e( 'No saved search list found for this user!', 'geodir-save-search' ); ?></p>
	<?php } else { ?>
	<table class="widefat fixed" cellspacing="0">
		<thead class="thead-light">
			<tr>
				<th scope="col" class="manage-column column-id text-center">#</th>
				<th scope="col" class="manage-column column-name"><?php _e( 'Name', 'geodir-save-search' ); ?></th>
				<th scope="col" class="manage-column column-action"></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ( $items as $k => $item ) { $sent = (int) GeoDir_Save_Search_Query::count_emails_sent( $item->subscriber_id ); ?>
			<tr>
				<th class="column-id text-center" scope="row"><?php echo ( (int) $item->subscriber_id ); ?></th>
				<td class="align-middle column-name"><a href="<?php echo esc_url( GeoDir_Save_Search_Post::get_url( $item->search_uri ) ); ?>" target="_blank" title="<?php echo esc_attr__( 'Open search link', 'geodir-save-search' ); ?>"><?php echo esc_html( stripslashes( $item->search_name ) ); ?></a><div class="d-block text-muted mt-1"><small class="mr-2 me-2"><i class="fas fa-calendar-alt"></i> <?php echo date_i18n( geodir_date_time_format(), strtotime( $item->date_added ) ); ?></small> <small class="mr-2 me-2"><i class="fas fa-link"></i> <?php echo geodir_post_type_name( $item->post_type, true ); ?></small> <small class="mr-2 me-2" title="<?php echo esc_attr__( 'Emails sent', 'geodir-save-search' ); ?>"><i class="far fa-envelope"></i> <?php echo wp_sprintf( $sent > 1 ? __( '%d emails sent', 'geodir-save-search' ) : __( '%d email sent', 'geodir-save-search' ), $sent ) ?></small></div></td>
				<td class="align-middle column-action text-center"><a class="text-danger" href="javascript:void(0)" title="<?php echo esc_attr__( 'Delete', 'geodir-save-search' ); ?>" onclick="javascript:geodir_save_search_delete(this, <?php echo (int) $item->subscriber_id; ?>, '<?php echo esc_attr( wp_create_nonce( 'geodir_save_search_delete_' . $item->subscriber_id ) ); ?>', <?php echo ( (int) $item->user_id ); ?>)"><i class="far fa-trash-can"></i> <?php echo esc_html__( 'Delete', 'geodir-save-search' ); ?></a></td>
			</tr>
			<?php } ?>
		</tbody>
	</table>
	<?php } ?>
	<style>.geodir-ss-table .me-2,.geodir-ss-table .mr-2,.geodir-ss-table .mx-2{margin-right:.5rem!important}.geodir-ss-table table{border:0!important}.geodir-ss-table .column-id{width:50px}.geodir-ss-table .column-action{width:75px}.geodir-ss-table .text-center{text-align:center}.geodir-ss-table .align-middle{vertical-align:middle}.geodir-ss-table .mx-2{margin-left:.5rem!important}.geodir-ss-table .mt-1{margin-top:.25rem!important}.geodir-ss-table .text-danger{color:red}.geodir-ss-table tbody tr>*{border-bottom:1px solid #eee}</style>
</div>
