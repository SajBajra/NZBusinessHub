"use strict";

const Geodir_All_Customer_Bookings = {
    /**
     * Initialize the app.
     * 
     * @param {string} appId The app ID.
     * @param {string} mountPoint The mount point.
     */
    init(appId, mountPoint) {
        this.app = Vue.createApp({
            data() {
                return {
                    listing: {},
                    booking: {},
                    cancelDetails: {},
                    error: '',
                    isLoading: false,
                };
            },

            methods: {
                cancelBooking() {
                    const _this = this;
					jQuery('#geodir-all-customer-bookings-modal').modal('toggle');

                    aui_confirm(
                        geodir_params.booking_confirm_cancel_customer,
                        geodir_params.txt_confirm,
                        geodir_params.booking_txt_go_back,
                    ).then((confirmed) => {
                        if (confirmed) {
                            _this.isLoading = true;

                            wp.apiFetch({
                                path: '/geodir/v2/booking/customer_cancel_booking',
                                method: 'POST',
                                data: _this.cancelDetails,
                            })
                                .then((res) => {
                                    // Copy keys from res to booking.
                                    Object.keys(res).forEach((key) => {
                                        _this.booking[key] = res[key];
                                    });

                                    window.location.reload();
                                })
                                .catch((err) => {
                                    // Error will have a message, code and data that's passed to WP_Error.
                                    if (err && err.message) {
                                        aui_toast("geodir_all_customer_bookings", "error", err.message);
                                    } else {
                                        aui_toast("geodir_all_customer_bookings", "error", geodir_params.booking_delete_error_message);
                                    }
                                })
                                .finally(() => {
                                    _this.isLoading = false;
                                });
                        }
                    });
                },
            },
        }).mount(mountPoint);

        window[appId] = this.app;

        jQuery(document).ready(($) => {
            if ($(window).width() < 768 && $(window).width() > 100 && $('.geodir-booking-table-md').length) {
                $('.geodir-booking-table-md').addClass('table-sm');
            }
            $(document).on(
                'click',
                '.geodir-customer-bookings-wrapper .geodir-customer-booking-view-details-button',
                this.handleViewBooking.bind(this),
            );
        });
    },

    /**
     * Handle view booking.
     * 
     * @param {Event} e The event.
     */
    handleViewBooking(e) {
        e.preventDefault();

        const $this = jQuery(e.currentTarget);
        this.app.$data.booking = $this.data('booking');
        this.app.$data.listing = $this.data('listing');
        this.app.$data.cancelDetails = $this.data('cancel-details');
        jQuery('#geodir-all-customer-bookings-modal').modal('toggle');
    },
};

// Init app.
Geodir_All_Customer_Bookings.init('geodir_all_customer_bookings', '#geodir-all-customer-bookings-modal');