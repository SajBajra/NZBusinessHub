<?php
/**
 * Product Tab Script
 *
 * This template can be overridden by copying it to yourtheme/geodirectory/marketplace/script.php.
 *
 * HOWEVER, on occasion GeoDirectory will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see        https://docs.wpgeodirectory.com/article/346-customizing-templates/
 * @package    GeoDir_Marketplace
 * @version    2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<script type="text/javascript">
jQuery(function($) {
	$('select#gdmp_vendor_id').on('change', function() {
		var $el = $(this), vendor_id = parseInt($(this).val());
		var data = {
			action: 'geodir_marketplace_post_options',
			post_id: <?php echo absint( $gd_post_id ); ?>,
			vendor_id: vendor_id,
			security: '<?php echo esc_attr( wp_create_nonce( "geodir_basic_nonce" ) ); ?>'
		};
		jQuery.ajax({
			url: '<?php echo esc_url( geodir_ajax_url() ); ?>',
			type: 'POST',
			data: data,
			dataType: 'json',
			beforeSend: function(xhr, obj) {
				$el.prop("disabled", true);
				$('select#gdmp_post_id').html('<option value="0"><?php echo __( "Fetching Listings...", "geomarketplace" ); ?></option>');
			}
		})
		.done(function(data, textStatus, jqXHR) {
			if (typeof data == 'object') {
				if (data.data.options) {
					$('select#gdmp_post_id').html(data.data.options);
				} else if (data.data.message) {
					alert(data.data.message);
				}
			}
		})
		.always(function(data, textStatus, jqXHR) {
			$el.prop("disabled", false);
		});
	});
});
</script>