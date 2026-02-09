"use strict";

jQuery(document).ready(function ($) {
    // Toggle bootstrap modal whenever button is clicked.
    $('body').on('click', '.geodir-view-bookings-btn', function (e) {
        e.preventDefault();
        geodir_view_bookings.$data.listing_id = $(this).data('id');
        geodir_view_bookings.$data.current_booking = '';
        $('#geodir-view-bookings-modal').modal('toggle');
    });
});

// Init app.
window.geodir_view_bookings = Vue.createApp({
    // Data.
    data: function data() {
        return Object.assign({}, GD_Booking_View_Bookings, {
            current_booking: '',
            listing_id: 0,
            is_loading: false,
            currentTab: 'all',
            allBookings: [],
            error: ''
        });
    },
    // Computed properties.
    computed: {
        // Returns bookings for the selected tab.
        bookings: function bookings() {
            var _this = this;
            // Are we viewing all bookings.
            if ('all' === this.currentTab) {
                return this.allBookings;
            }

            // Are we viewing upcoming bookings.
            if ('upcoming' === this.currentTab) {
                return this.allBookings.filter(function (booking) {
                    return booking.is_upcoming && 'confirmed' === booking.status;
                });
            }

            // Filter by status.
            return this.allBookings.filter(function (booking) {
                return booking.status === _this.currentTab;
            });
        },
        // Checks if there are any bookings.
        hasBookings: function hasBookings() {
            return this.bookings.length;
        }
    },
    // Methods.
    methods: {
        // Formats an amount.
        formatAmount: function formatAmount(amount) {
            return this.price_format.replace('%1$s', this.currency_symbol).replace('%2$s', parseFloat(amount).toFixed(this.decimal_places));
        },
        // Refresh bookings.
        badgeClass: function badgeClass(booking) {
            if (jQuery("body").hasClass("aui_bs5")) {
                return "text-bg-".concat(booking.context_class, " geodir-booking-badge-").concat(booking.status);
            }
            return "badge-".concat(booking.context_class, " geodir-booking-badge-").concat(booking.status);
        },
        // Refresh bookings.
        refreshBookings: function refreshBookings() {
            var _this2 = this;
            // Show loading indicator.
            this.is_loading = true;
            this.allBookings = [];
            this.current_booking = '';
            if (!this.listing_id) {
                this.is_loading = false;
                return;
            }

            // GET bookings.
            wp

                // Fetch the bookings.
                .apiFetch({
                    path: "/geodir/v2/booking/bookings?listing_id=".concat(this.listing_id, "&nonce=").concat(this.nonce, "&imported=")
                })

                // Success.
                .then(function (res) {
                    _this2.allBookings = res;
                    return res;
                })

            // Handle errors.
            ["catch"](function (err) {
                // Error will have a message, code and data that's passed to WP_Error.
                if (err && err.message) {
                    _this2.error = err.message;
                }

                // If not, render the default error message.
                else {
                    _this2.error = wp.i18n.__('An unexpected error occured. Please try again.', 'geodir-booking');
                }
                setTimeout(function () {
                    return _this2.error = '';
                }, 3000);
            })

            // Unblock the form.
            ["finally"](function () {
                _this2.is_loading = false;
            });
        },
        // Save current booking.
        saveBooking: function saveBooking(booking) {
            var _this3 = this;
            booking.saving = true;
            booking.nonce = _this3.nonce;
            this.is_loading = true;

            // POST
            wp

                // Save the ruleset.
                .apiFetch({
                    path: '/geodir/v2/booking/save_booking',
                    method: 'POST',
                    data: booking
                })

                // Handle success.
                .then(function (res) {
                    // Copy keys from res to booking.
                    Object.keys(res).forEach(function (key) {
                        booking[key] = res[key];
                    });
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
                booking.saving = false;
                _this3.is_loading = false;
            });
        },
        // Cancel current booking.
        cancelBooking: function cancelBooking(booking) {
            var _this3 = this;

            aui_confirm(geodir_params.booking_confirm_cancel_owner, geodir_params.txt_confirm, geodir_params.booking_txt_go_back).then(function (confirmed) {
                if (confirmed) {
                    booking.saving = true;
                    booking.nonce = _this3.nonce;
                    _this3.is_loading = true;

                    // POST
                    wp

                        // Cancel the booking.
                        .apiFetch({
                            path: '/geodir/v2/booking/cancel_booking',
                            method: 'POST',
                            data: booking
                        })

                        // Handle success.
                        .then(function (res) {
                            // Copy keys from res to booking.
                            Object.keys(res).forEach(function (key) {
                                booking[key] = res[key];
                            });
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
                        booking.saving = false;
                        _this3.is_loading = false;
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
        },
    }
}).mount('#geodir-view-bookings-modal');