<?php
defined( 'ABSPATH' ) || exit;

global $aui_bs5;
?>
<div class="bsui geodir-booking-setup-booking-modal">
    <div class="modal" id="geodir-booking-setup-booking-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><?php esc_html_e( 'Set-up Bookings', 'geodir-booking' ); ?></h5>
                    <?php if ( $aui_bs5 ) { ?>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?php esc_attr_e( 'Close', 'geodir-booking' ); ?>"></button>
                    <?php } else { ?>
                        <button type="button" class="close" data-dismiss="modal" aria-label="<?php esc_attr_e( 'Close', 'geodir-booking' ); ?>">&times;</button>
                    <?php } ?>
                </div>
                <div class="modal-body">
                    <?php geodir_get_template( 'calendar.php', array(), 'geodir-booking', plugin_dir_path( GEODIR_BOOKING_FILE ) . 'templates' );

                    aui()->alert(
                        array(
                            'type'    => 'info',
                            'class'   => 'mt-3 mb-0 text-center',
                            'content' => esc_attr__( 'Click and drag to select multiple dates or Hold SHIFT + CLICK.', 'geodir-booking' ),
                        ),
                        true
                    );
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>