"use strict";

jQuery(document).ready(function ($) {
    // Toggle bootstrap modal whenever button is clicked.
    $('body').on('click', '.geodir-view-customer-bookings-btn', function (e) {
        e.preventDefault();
        geodir_view_customer_bookings.$data.listing_id = $(this).data('id');
        $('#geodir-view-customer-bookings-modal').modal('toggle');
    });
});

// Init app.
window.geodir_view_customer_bookings = Vue.createApp({
    // Data.
    data: function data() {
        return Object.assign({}, GD_Booking_View_Customer_Bookings, {
            listing_id: 0,
            is_loading: true,
            is_cancelling: false,
            bookings: [],
            current_booking: '',
            listing: {},
            error: ''
        });
    },
    // Computed properties.
    computed: {
        // Checks if there are any bookings.
        hasBookings: function hasBookings() {
            return this.bookings.length;
        }
    },
    // Methods.
    methods: {
        // Refresh bookings.
        badgeClass: function badgeClass(booking) {
            if (jQuery("body").hasClass("aui_bs5")) {
                return "text-bg-".concat(booking.context_class, " geodir-booking-badge-").concat(booking.status);
            }
            return "badge-".concat(booking.context_class, " geodir-booking-badge-").concat(booking.status);
        },
        // Refresh bookings.
        refreshBookings: function refreshBookings() {
            var _this = this;
            // Reset app props.
            this.error = '';
            this.listing = {};
            this.is_loading = true;
            this.bookings = [];
            this.current_booking = '';
            if (!this.listing_id) {
                this.error = wp.i18n.__('Listing not found.', 'geodir-booking');
                this.is_loading = false;
                return;
            }

            // GET bookings.
            wp

                // Fetch the bookings.
                .apiFetch({
                    path: "/geodir/v2/booking/customer_bookings?listing_id=".concat(this.listing_id)
                })

                // Success.
                .then(function (res) {
                    _this.listing = res.listing;
                    _this.bookings = res.bookings;
                    return res;
                })

            // Handle errors.
            ["catch"](function (err) {
                // Error will have a message, code and data that's passed to WP_Error.
                if (err && err.message) {
                    _this.error = err.message;
                }

                // If not, render the default error message.
                else {
                    _this.error = wp.i18n.__('An unexpected error occured. Please try again.', 'geodir-booking');
                }
            })

            // Unblock the form.
            ["finally"](function () {
                _this.is_loading = false;
            });
        },
        // Cancel current booking.
        cancelBooking: function cancelBooking(cancelDetails) {
            var _this3 = this;

            aui_confirm(geodir_params.booking_confirm_cancel_customer, geodir_params.txt_confirm, geodir_params.booking_txt_go_back).then(function (confirmed) {
                if (confirmed) {
                    _this3.is_loading = true;
                    _this3.error = '';
                    _this3.is_cancelling = true;

                    // POST
                    wp

                        // Save the ruleset.
                        .apiFetch({
                            path: '/geodir/v2/booking/customer_cancel_booking',
                            method: 'POST',
                            data: cancelDetails,
                        })

                        // Handle success.
                        .then(function (res) {

                            // Copy keys from res to booking.
                            _this3.current_booking = res;

                            window.location.reload();

                            return res;
                        })

                    // Handle errors.
                    ["catch"](function (err) {
                        // Error will have a message, code and data that's passed to WP_Error.
                        if (err && err.message) {
                            _this3.error = err.message;
                        }

                        // If not, render the default error message.
                        else {
                            _this3.error = __('An unexpected error occured. Please try again.', 'geodir-booking');
                        }
                        setTimeout(function () {
                            _this3.error = '';
                        }, 3000);
                    })

                    // Unblock the form.
                    ["finally"](function () {
                        _this3.is_loading = false;
                        _this3.is_cancelling = false;
                    });

                }
            });
        }

    },
    // Watchers.
    watch: {
        // Refresh bookings when the listing_id changes.
        listing_id: function listing_id() {
            this.refreshBookings();
        }
    }
}).mount('#geodir-view-customer-bookings-modal');