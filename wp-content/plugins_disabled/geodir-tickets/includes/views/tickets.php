<?php

	$table = new GeoDir_Tickets_List_Table();
	$table->prepare_items();

?>

<style>

	#geodir-tickets-table-wrap th a{
		padding-left: 0;
	}

	#geodir-tickets-table-wrap .column-action {
		text-align: center;
	}

	#geodir-tickets-table-wrap .column-action button {
		border: none;
    	background-color: transparent;
	}

	.popover-body li a {
		text-decoration: none;
		font-size: 13px;
	}

	@media screen and (min-width:782px) {
		#geodir-tickets-table-wrap .column-title {
			min-width: 320px;
		}

		#geodir-tickets-table-wrap th.manage-column:not(.column-email,.column-cb,.column-title) {
			min-width: 100px;
		}
		#geodir-tickets-table-wrap th.column-quantity,#geodir-tickets-table-wrap th.column-action {
			width: 90px;
		}
	}
</style>

<div class="wrap geodir-tickets-page">

	<h1 class="wp-heading-inline"><?php echo esc_html( get_admin_page_title() ); ?></h1>

	<form id="geodir-tickets-table" method="POST" action="<?php echo remove_query_arg( array( 'geodir-tickets-action', '_wpnonce', 'ticket' ) ); ?>">
		<?php $table->display(); ?>
	</form>

</div>

<script>
	jQuery(function ($) {

		$( '.gp-ticket-action-button' ).popover({
			html: true,
			content: function() { return $(this).closest( '.bsui' ).find( '.gp-ticket-action-content' ).html() }
		});

	});

</script>