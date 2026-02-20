<?php
/**
 * Contains the cropping modal template.
 *
 * You can override this template by copying it to your-theme/gpa/crop-ad-image.php
 */

defined( 'ABSPATH' ) || exit;

global $aui_bs5;
?>
<div class="bsui">
	<div class="modal fade" id="adv-image-crop-template" tabindex="-1" role="dialog" aria-hidden="true">
		<div class="modal-dialog modal-dialog-centered modal-lg" role="checkout" style="max-width: 650px;">
			<div class="modal-content">
				<form>
					<div class="modal-header">
						<h4 class="modal-title"><?php _e( 'Crop Ad Image', 'advertising' ); ?></h4>
						<?php if ( $aui_bs5 ) { ?>
							<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?php esc_attr_e( 'Close', 'advertising' ); ?>"></button>
						<?php } else { ?>
							<button type="button" class="close" data-dismiss="modal" aria-label="<?php esc_attr_e( 'Close', 'advertising' ); ?>"><span aria-hidden="true">&times;</span></button>
						<?php } ?>
					</div>

					<div class="modal-body">
						<div align="center">
							<div class="alert alert-success advertising-alert d-none" role="alert"></div>
		                    <img id="adv-image-to-crop" style="max-width: 100%; height: auto;" />
	                    </div>
					</div>

					<div class="modal-footer">
						<button type="button" class="btn btn-secondary" data-<?php echo ( $aui_bs5 ? 'bs-' : '' ); ?>dismiss="modal"><?php _e( 'Cancel', 'advertising' ); ?></button>
						<button type="button" class="btn btn-primary" data-<?php echo ( $aui_bs5 ? 'bs-' : '' ); ?>dismiss="modal" id="advertising-handle-crop"><?php _e( 'Crop Image', 'advertising' ); ?></button>
					</div>

				</form>
			</div>
		</div>
	</div>
</div>
