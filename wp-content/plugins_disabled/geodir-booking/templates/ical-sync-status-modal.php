<?php
defined( 'ABSPATH' ) || exit;

global $aui_bs5;
?>

<style type="text/css">
.geodir-ical-status-modal-modal .geodir-booking-logs {
    list-style: none;
    margin: 0;
    padding: 0;
}

.geodir-booking-logs li:nth-child(odd) {
    background-color: #f6f7f7
}

.geodir-booking-logs li {
    display: flex;
    margin-bottom: 5px;
    line-height: 30px;
}

.geodir-booking-logs li p {
    position: relative;
    padding-left: 20px;
    margin: 0;
    font-size: 14px;
}

.geodir-booking-logs li p::before {
    content: '';
    position: absolute;
    display: inline-block;
    vertical-align: middle;
    background: #708090;
    left: 0;
    top: 0;
    width: 10px;
    height: 30px;
}

.geodir-booking-logs li p.notice-done::before,
.geodir-booking-logs li p.notice-success::before {
    background: #228b22;
}

.geodir-booking-logs li p.notice-in-progress::before,
.geodir-booking-logs li p.notice-info::before {
    background: #1e90ff;
}

.geodir-booking-logs li p.notice-warning::before {
    background: #f56800;
}

.geodir-booking-logs li p.notice-error::before {
    background: #f50000;
}
</style>

<div class="bsui geodir-ical-status-modal-modal">
    <div class="modal" id="ical-status-modal" tabindex="-1" role="dialog">
		<div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><?php esc_html_e( 'iCalendar Sync Status'); ?></h5>
                    <?php if ( $aui_bs5 ) { ?>
						<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?php esc_attr_e( 'Close', 'geodir-booking' ); ?>"></button>
					<?php } else { ?>
						<button type="button" class="close" data-dismiss="modal" aria-label="<?php esc_attr_e( 'Close', 'geodir-booking' ); ?>">&times;</button>
					<?php } ?>
                </div>
                <div class="modal-body"></div>
            </div>
        </div>
    </div>
</div>