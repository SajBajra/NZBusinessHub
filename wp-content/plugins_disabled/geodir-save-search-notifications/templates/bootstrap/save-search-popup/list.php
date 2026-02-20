<?php
/**
 * Save Search Saved Search List
 *
 * This template can be overridden by copying it to yourtheme/geodirectory/bootstrap/save-search-popup/list.php.
 *
 * HOWEVER, on occasion GeoDirectory will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see       https://wpgeodirectory.com/documentation/article/how-tos/customizing-templates/
 * @package   GeoDir_Save_Search
 * @version   2.1.3
 */

defined( 'ABSPATH' ) || exit;

global $aui_bs5;

?>
<div class="geodir-search-search-list-wrap overflow-auto" style="max-height:400px">
	<?php if ( empty( $saved_items ) ) { ?>
	<div class="text-muted p-3"><?php _e( 'No saved search found!', 'geodir-save-search' ); ?></div>
	<?php } else { ?>
	<table class="table table-sm">
		<thead class="thead-light">
			<tr>
				<th scope="col">#</th>
				<th scope="col"><?php _e( 'Name', 'geodir-save-search' ); ?></th>
				<th scope="col"></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ( $saved_items as $k => $item ) { $sent = (int) GeoDir_Save_Search_Query::count_emails_sent( $item->subscriber_id ); ?>
			<tr>
				<th class="align-middle" scope="row"><?php echo ( $k + 1 ); ?></th>
				<td class="align-middle"><?php echo esc_html( stripslashes( $item->search_name ) ); ?><div class="d-block text-muted mt-1"><small class="mr-2 me-2"><i class="fas fa-calendar-alt mr-1 me-1"></i><?php echo date_i18n( geodir_date_time_format(), strtotime( $item->date_added ) ); ?></small><small class="mr-2 me-2"><i class="fas fa-link mr-1 me-1"></i><?php echo geodir_post_type_name( $item->post_type, true ); ?></small><small class="mr-2 me-2" title="<?php echo esc_attr__( 'Emails sent', 'geodir-save-search' ); ?>"><i class="far fa-envelope mr-1 me-1"></i><?php echo wp_sprintf( $sent > 1 ? __( '%d emails sent', 'geodir-save-search' ) : __( '%d email sent', 'geodir-save-search' ), $sent ) ?></small></div></td>
				<td class="align-middle"><a class="mx-1" href="<?php echo esc_url( GeoDir_Save_Search_Post::get_url( $item->search_uri ) ); ?>" target="_blank" title="<?php echo esc_attr__( 'Open link', 'geodir-save-search' ); ?>"><i class="fas fa-arrow-up-right-from-square"></i></a><a class="mx-2 text-danger" href="javascript:void(0)" title="<?php echo esc_attr__( 'Delete', 'geodir-save-search' ); ?>" onclick="javascript:geodir_save_search_delete(this, <?php echo (int) $item->subscriber_id; ?>, '<?php echo esc_attr( wp_create_nonce( 'geodir_save_search_delete_' . $item->subscriber_id ) ); ?>')"><i class="far fa-trash-can"></i></a></td>
			</tr>
			<?php } ?>
		</tbody>
	</table>
	<?php } ?>
</div>
