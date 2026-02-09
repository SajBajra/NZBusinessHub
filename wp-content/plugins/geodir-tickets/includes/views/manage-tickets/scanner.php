<?php
defined( 'ABSPATH' ) || exit;

global $aui_bs5;
?>
<form class="geodir-ticket-number-scanner">

    <div id="geodir-ticket-scanner-app<?php echo esc_attr( $id );?>" class="border my-3 geodir-ticket-scanner-app" width="300px" style="min-height: 200px;"></div>

    <div class="geodir-ticket-number-separator d-flex align-items-center justify-content-center my-4 big"><h4><strong><?php _e( 'Or', 'geodir-tickets' ); ?></strong></h4></div>

    <div class="input-group <?php echo ( $aui_bs5 ? 'mb-3' : 'form-group' ); ?>">

        <input
            type="text"
            class="form-control"
            name="geodir-ticket-number"
            id="geodir-ticket-scanner-<?php echo esc_attr( $id );?>"
            aria-describedby="geodir-ticket-scanner-submit-<?php echo esc_attr( $id );?>"
            aria-label="<?php echo esc_attr_e( 'Enter Ticket Number', 'geodir-tickets' );?>"
            placeholder="<?php echo esc_attr_e( 'Enter Ticket Number', 'geodir-tickets' );?>"
            required="required"
        />

        <button
            class="btn btn-primary geodir-tickets-scan-ticket-id"
            type="submit"
            id="geodir-ticket-scanner-submit-<?php echo esc_attr( $id );?>"
            style="border-bottom-left-radius: 0; border-top-left-radius: 0;"
        ><?php echo esc_html_e( 'Verify', 'geodir-tickets' );?></button>
    </div>

    <div class="alert alert-success d-none" role="alert"></div>
    <div class="alert alert-warning d-none" role="alert"></div>
    <div class="alert alert-danger d-none" role="alert"></div>

    <div class="position-relative geodir-verified-ticket-details border p-1 d-none">
        <div class="geodir-verified-ticket-type h2"></div>
        <div class="geodir-verified-ticket-id position-absolute text-danger h2" style="top: 0; right: 0;"></div>
        <div class="form-row mt-2 small">
            <div class="col">
                <div class="text-uppercase <?php echo ( $aui_bs5 ? 'fw-bold' : 'font-weight-bold' ); ?>"><?php esc_html_e( 'Booked', 'geodir-tickets' ); ?></div>
                <div class="geodir-verified-ticket-booked"></div>
            </div>
            <div class="col">
                <div class="text-uppercase <?php echo ( $aui_bs5 ? 'fw-bold' : 'font-weight-bold' ); ?>"><?php esc_html_e( 'Ticket Number', 'geodir-tickets' ); ?></div>
                <div class="geodir-verified-ticket-number"></div>
            </div>
            <div class="col">
                <div class="text-uppercase <?php echo ( $aui_bs5 ? 'fw-bold' : 'font-weight-bold' ); ?>"><?php esc_html_e( 'Ticket Status', 'geodir-tickets' ); ?></div>
                <div class="geodir-verified-ticket-status"></div>
            </div>
            <div class="col geodir-scanner-redeem-ticket-button-wrapper d-none">
                <a href="#" class="btn btn-info" data-nonce="<?php echo esc_attr( wp_create_nonce( 'geodir_verify_ticket_nonce' ) ); ?>" data-listing="<?php echo esc_attr( $post_id ); ?>"><?php esc_html_e( 'Redeem Ticket', 'geodir-tickets' ); ?></a>
            </div>
        </div>
    </div>

    <input type="hidden" name="action" value="geodir_verify_ticket" />
	<input type="hidden" name="_wpnonce" value="<?php echo esc_attr( wp_create_nonce( 'geodir_verify_ticket_nonce' ) ); ?>" />
	<input type="hidden" name="listing_id" value="<?php echo esc_attr( $post_id ); ?>" />

</form>
<style>
    .geodir-ticket-number-separator::before,
    .geodir-ticket-number-separator::after {
        content: '';
        flex: 1;
        border-bottom: 1px solid #dee2e6;
    }

    .geodir-ticket-number-separator:not(:empty)::before {
        margin-right: 2em;
    }

    .geodir-ticket-number-separator:not(:empty)::after {
        margin-left: 2em;
    }
</style>
