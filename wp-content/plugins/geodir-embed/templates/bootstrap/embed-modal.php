<?php

if ( ! defined( 'ABSPATH' ) ) {
  exit; // Exit if accessed directly
}

global $aui_bs5;
?>
<div class="modal fade bsui" id="embedModal" tabindex="-1" aria-labelledby="embedModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-xl">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title m-0"><?php _e('Build your embed code', 'geodir-embed'); ?></h5>
        <?php if ( $aui_bs5 ) { ?>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?php esc_attr_e( 'Close', 'geodir-embed' ); ?>"></button>
        <?php } else { ?>
        <button type="button" class="close" data-dismiss="modal" aria-label="<?php _e('Close', 'geodir-embed'); ?>">
          <span aria-hidden="true">&times;</span>
        </button>
        <?php } ?>
      </div>
      <div class="modal-body mb-0 p-0 rounded overflow-hidden">
        <div class="embed-loading text-center mt-5"><div class="spinner-border" role="status"></div></div>
        <iframe id="embedModal-iframe" src="" width="100%" height="100%" frameborder="0" allowtransparency="true"></iframe>  
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary" data-<?php echo ( $aui_bs5 ? 'bs-' : '' ); ?>dismiss="modal" aria-label="Close"><?php _e('Close', 'geodir-embed'); ?></button>
      </div>
    </div>
  </div>
</div>