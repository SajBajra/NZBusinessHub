"use strict";

const Geodir_All_Owner_Bookings = {
    init(appId, mountPoint, initialData) {
        this.app = Vue.createApp({
            data() {
                return {
                    ...initialData,
                    booking: {},
                    listing: {},
                    isLoading: false,
                    error: ''
                };
            },
            methods: {
                formatAmount(amount) {
                    return this.price_format
                        .replace('%1$s', this.currency_symbol)
                        .replace('%2$s', parseFloat(amount).toFixed(this.decimal_places));
                },
                badgeClass(booking) {
                    const prefix = document.body.classList.contains("aui_bs5") ? "text-bg-" : "badge-";
                    return `${prefix}${booking.context_class} geodir-booking-badge-${booking.status}`;
                },
                async saveBooking(booking) {
                    await this.performAction('/geodir/v2/booking/save_booking', booking);
                },
                async cancelBooking(booking) {
                    const confirmed = await this.confirmAction(
                        geodir_params.booking_confirm_cancel_owner,
                        geodir_params.txt_confirm,
                        geodir_params.booking_txt_go_back
                    );
                    if (confirmed) {
                        await this.performAction('/geodir/v2/booking/cancel_booking', booking);
                    }
                },
                async performAction(path, data) {
                    this.isLoading = true;
                    data.saving = true;
                    data.nonce = this.nonces.view_bookings;

                    try {
                        const res = await wp.apiFetch({ path, method: 'POST', data });
                        Object.assign(data, res);
                    } catch (err) {
                        this.handleError(err);
                    } finally {
                        this.isLoading = false;
                        data.saving = false;
                    }
                },
                handleError(err) {
                    this.error = err.message || GD_Booking_All_Owner_Bookings.i18n.unexpected_error;
                    setTimeout(() => this.error = '', 3000);
                },
                confirmAction(message, confirmText, cancelText) {
                    return new Promise((resolve) => {
                        aui_confirm(message, confirmText, cancelText).then(resolve);
                    });
                }
            }
        }).mount(mountPoint);

        window[appId] = this.app;
    }
}

const Geodir_All_Owner_Add_Booking = {
    init(appId, mountPoint) {
        this.app = Vue.createApp({
            data() {
                return {
                    listings: {},
                    listingsFound: null,
                    steps: {
                        search: true,
                        booking: false,
                        loading: false,
                        searching: false,
                        processing: false,
                    },
                    formData: {
                        checkin_date: '',
                        checkout_date: '',
                        search_listing_id: -1,
                        listing_id: -1,
                        room_id: -1,
                        adults: -1,
                        children: -1,
                        customer_name: null,
                        customer_email: null,
                        customer_phone: null,
                        private_note: null,
                        booking_status: 'pending_payment',
                    },
                    error: ''
                };
            },
            computed: {
                totalGuests() {
                    return (parseInt(this.formData.adults) > 0 ? parseInt(this.formData.adults) : 0) +
                           (parseInt(this.formData.children) > 0 ? parseInt(this.formData.children) : 0);
                },
                guestsSummary() {
                    return `${this.totalGuests} ${this.pluralize('guest', this.totalGuests)}`;
                },
                numberOfListings() {
                    return Object.keys(this.listings).length;
                },
                hasSearchedListingID() {
                    return this.formData.search_listing_id > 0;
                },
                selectedListing() {
                    return this.listings[this.formData.listing_id] || 
                           this.listings[this.formData.search_listing_id] || 
                           null;
                },
                selectedRoom() {
                    return this.selectedListing?.rooms.find(room => room.id === this.formData.room_id) || null;
                },
                hasMultipleRooms() {
                    return this.selectedListing && this.selectedListing.rooms.length > 1;
                },
                formattedCheckinDate() {
                    return this.formatDate(this.formData.checkin_date);
                },
                formattedCheckoutDate() {
                    return this.formatDate(this.formData.checkout_date);
                }
            },
            watch: {
                'formData.search_listing_id': 'resetListings',
                'formData.adults': 'resetListings',
                'formData.children': 'resetListings',
                'formData.checkin_date': 'resetListings',
                'formData.checkout_date': 'resetListings'
            },
            methods: {
                formatDate(dateString) {
                    const date = new Date(dateString);
                    const options = { day: 'numeric', month: 'short', year: 'numeric' };
                    return date.toLocaleDateString('en-US', options)
                        .replace(/(\d+)(st|nd|rd|th)/, '$1<sup>$2</sup>');
                },
                pluralize(word, count) {
                    return count === 1 ? word : word + 's';
                },
                resetListings() {
                    this.listings = {};
                },
                toggleStep(step) {
                    Object.keys(this.steps).forEach(key => {
                        this.steps[key] = key === step;
                    });
                },
                reserveBooking() {
                    this.toggleStep('booking');
                },
                async addBooking() {
                    this.processing = true;
                    const data = this.buildBookingData();
                    
                    try {
                        const res = await wp.apiFetch({
                            path: '/geodir/v2/booking/process_booking',
                            method: 'POST',
                            data
                        });

                        if (res.booking) {
                            this.handleBookingSuccess(res);
                        } else if (res.message) {
                            this.handleError(res.message);
                        } else {
                            this.handleError('');
                        }
                    } catch (err) {
                        this.handleError(err.message || GD_Booking_All_Owner_Bookings.i18n.unexpected_error);
                    } finally {
                        this.toggleStep('booking');
                    }
                },
                buildBookingData() {
                    let listing_id = this.formData.listing_id || this.formData.search_listing_id;

                    if (this.formData.room_id > 0) {
                        listing_id = this.formData.room_id;
                    }

                    const data = {
                        nonce: GD_Booking_All_Owner_Bookings.nonces.process_booking,
                        start_date: this.formData.checkin_date,
                        end_date: this.formData.checkout_date,
                        name: this.formData.customer_name,
                        email: this.formData.customer_email,
                        phone: this.formData.customer_phone,
                        private_note: this.formData.private_note,
                        status: this.formData.booking_status,
                        guests: this.totalGuests,
                        adults: this.formData.adults > 0 ? this.formData.adults : undefined,
                        children: this.formData.children > 0 ? this.formData.children : undefined,
                        listing_id,
                    };

                    if (data.adults === undefined && data.children === undefined) {
                        data.adults = 1;
                    }

                    return data;
                },
                handleBookingSuccess(res) {
                    jQuery('#geodir-all-owner-add-bookings-modal').modal('hide').on('hidden.bs.modal', () => {
                        geodir_all_owner_bookings.$data.booking = res.booking;
                        geodir_all_owner_bookings.$data.listing = res.listing;
                        jQuery('#geodir-all-owner-view-bookings-modal').modal('show');
                        jQuery(this).off('hidden.bs.modal');
                    });
                },
                handleError(message) {
                    this.error = message;
                    setTimeout(() => {
                        this.error = '';
                    }, 3000);
                },
                resetForm() {
                    this.steps = {
                        search: true,
                        booking: false,
                        loading: false,
                        searching: false,
                        processing: false,
                    };
                    this.formData = {
                        checkin_date: '',
                        checkout_date: '',
                        search_listing_id: -1,
                        listing_id: null,
                        room_id: null,
                        adults: -1,
                        children: -1,
                        customer_name: null,
                        customer_email: null,
                        customer_phone: null,
                        private_note: null,
                        booking_status: 'pending_payment',
                    };
                    this.error = '';
                    this.listings = {};
                    this.listingsFound = null;
                },
                async searchListings() {
                    this.steps.searching = true;
                    const data = this.buildSearchData();

                    try {
                        const res = await wp.apiFetch({
                            path: '/geodir/v2/booking/search',
                            method: 'POST',
                            data
                        });

                        this.handleSearchResponse(res);
                    } catch (err) {
                        this.handleError(err.message || GD_Booking_All_Owner_Bookings.i18n.unexpected_error);
                    } finally {
                        this.toggleStep('search');
                    }
                },
                buildSearchData() {
                    return {
                        nonce: GD_Booking_All_Owner_Bookings.nonces.view_bookings,
                        checkin_date: this.formData.checkin_date,
                        checkout_date: this.formData.checkout_date,
                        listing_id: this.formData.search_listing_id > 0 ? parseInt(this.formData.search_listing_id) : undefined,
                        adults: this.formData.adults > 0 ? parseInt(this.formData.adults) : undefined,
                        children: this.formData.children > 0 ? parseInt(this.formData.children) : undefined,
                    };
                },
                handleSearchResponse(res) {
                    if (res.listings !== undefined) {
                        this.listings = res.listings;
                        this.listingsFound = res.listings_found;

                        const listing_ids = Object.keys(this.listings);
                        if (listing_ids.length === 1) {
                            this.formData.listing_id = parseInt(this.listings[listing_ids[0]].id);
                        }
                    }

                    if (res.message) {
                        this.handleError(res.message);
                    } else {
                        this.error = '';
                    }
                }
            }
        }).mount(mountPoint);

        window[appId] = this.app;
    }
}

const Geodir_Bookings_Events_Handler = {
    init() {
        jQuery(document).ready(($) => {
            if ($(window).width() < 768 && $(window).width() > 100 && $('.geodir-booking-table-md').length) {
                $('.geodir-booking-table-md').addClass('table-sm');
            }
            $(document).on('click', '.geodir-owner-bookings-wrapper .geodir-owner-booking-add-button', this.handleAddBooking);
            $(document).on('click', '.geodir-owner-bookings-wrapper .geodir-owner-booking-view-details-button', this.handleViewBooking);
            $(document).on('click', '.geodir-owner-bookings-wrapper .geodir-owner-booking-edit-details-button', this.handleEditBooking);
            $(document).on('click', '.geodir-owner-bookings-wrapper .geodir-owner-booking-delete-button', this.handleDeleteBooking);
        });
    },

    handleAddBooking(e) {
        e.preventDefault();
        jQuery('#geodir-all-owner-add-bookings-modal').modal('toggle');
        jQuery('input[data-aui-init="flatpickr"]:not(.flatpickr-input)').flatpickr();
    },

    handleViewBooking(e) {
        e.preventDefault();
        const $this = jQuery(e.currentTarget);
        geodir_all_owner_bookings.$data.booking = $this.data('booking');
        geodir_all_owner_bookings.$data.listing = $this.data('listing');
        jQuery('#geodir-all-owner-view-bookings-modal').modal('toggle');
    },

    handleEditBooking(e) {
        e.preventDefault();
        const $this = jQuery(e.currentTarget);
        geodir_all_owner_bookings.$data.booking = $this.data('booking');
        geodir_all_owner_bookings.$data.listing = $this.data('listing');
        jQuery('#geodir-all-owner-edit-bookings-modal').modal('toggle');
    },

    handleDeleteBooking(e) {
        e.preventDefault();
        const $this = jQuery(e.currentTarget);
        const row = $this.parents('tr');
        const booking_id = $this.data('booking');

        if (booking_id === undefined) {
            return;
        }

        aui_confirm(geodir_params.booking_confirm_delete, geodir_params.txt_confirm, geodir_params.booking_txt_go_back, true).then((confirmed) => {
            if (confirmed) {
                row.addClass('bg-gray');

                wp.apiFetch({
                    path: '/geodir/v2/booking/delete_booking',
                    method: 'POST',
                    data: {
                        nonce: GD_Booking_All_Owner_Bookings.nonces.view_bookings,
                        booking_id
                    }
                })
                .then((res) => {
                    if (res.deleted === true) {
                        row.fadeOut('slow', function () {
                            row.remove();
                        });
                    } else {
                        aui_modal(geodir_params.booking_delete_error_title, '<div class="gd-notification gd-error"><i class="fas fa-exclamation-circle"></i>' + geodir_params.booking_delete_error_message + '</div>', '', true);
                    }
                })
                .catch((err) => {
                    if (err && err.message) {
                        aui_modal(geodir_params.booking_delete_error_title, '<div class="gd-notification gd-error"><i class="fas fa-exclamation-circle"></i>' + err.message + '</div>', '', true);
                    }
                });
            }
        });
    }
}

// Initialize applications
function geodir_init_booking_modals() {
    Geodir_All_Owner_Bookings.init('geodir_all_owner_bookings', '#geodir-all-owner-bookings-modals', GD_Booking_All_Owner_Bookings);
    Geodir_All_Owner_Add_Booking.init('geodir_all_owner_add_bookings', '#geodir-all-owner-add-bookings-modal');
    Geodir_Bookings_Events_Handler.init();
}

geodir_init_booking_modals();

window.geodir_init_booking_modals = geodir_init_booking_modals;