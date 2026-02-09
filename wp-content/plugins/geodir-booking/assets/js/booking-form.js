"use strict";

window.gd_booking_dates_changed = function (listing_id, start_date, end_date) {
    if (window.gd_booking_forms[listing_id]) {
        window.gd_booking_forms[listing_id].forEach(function (form) {
            form.setBookingDates(start_date, end_date);
        });
    }
};
jQuery(document).ready(function ($) {
    /**
     * Retrieves an item from localStorage with a fallback value
     *
     */
    function getCachedItem(key, defaultValue) {
        return localStorage.getItem(key) || defaultValue;
    }

    /**
     * Formats a date object to YYYY-MM-DD
     *
     * @param {Date} date
     */
    function formatDate(date) {
        var month = '' + (date.getMonth() + 1),
            day = '' + date.getDate(),
            year = date.getFullYear();
        if (month.length < 2) {
            month = '0' + month;
        }
        if (day.length < 2) {
            day = '0' + day;
        }
        return [year, month, day].join('-');
    }

    // init booking form
    $('.geodir-booking-form-container').each(function () {
        var app = $(this);
        var post_id = app.data('id');
        var $html = $('#gd-booking-form-' + post_id).html();
        app.html($html);
    });

    $(document).on('click', '.dropdown-menu[data-keep-open]', function (e) {
        e.stopPropagation();
    });

    // Watch for room changes.
    $('.geodir-booking-room-select').on('change', function () {

        var select = $(this);
        var id = select.closest('.geodir-booking-form-rooms-container').data('parent');
        var value = select.val();

        // Fetch all wrappers where class == geodir-booking-form-rooms-container and data-parent == id
        var wrappers = $('.geodir-booking-form-rooms-container[data-parent="' + id + '"]');

        wrappers.each(function () {
            if (value) {
                $(this).find('.geodir-booking-form-room-container').hide();
                $(this).find('.geodir-booking-form-room-container[data-id="' + value + '"]').show();
                $(this).find('.geodir-booking-room-select').val(value);
            }
        });
    });

    $('.geodir-booking-room-select').change();

    // Init apps.
    window.gd_booking_forms = {};

    // Read cached start and end dates from local storage
    var cached_start_date = getCachedItem('gd_booking_start_date', '');
    var cached_end_date = getCachedItem('gd_booking_end_date', '');
    var cached_adults = getCachedItem('gd_booking_adults', 1);
    var cached_children = getCachedItem('gd_booking_children', 0);
    var cached_infants = getCachedItem('gd_booking_infants', 0);
    var cached_pets = getCachedItem('gd_booking_pets', 0);

    ['gd_booking_adults', 'gd_booking_children', 'gd_booking_infants', 'gd_booking_pets'].forEach(item => {
        localStorage.removeItem(item);
    });

    $('.geodir-booking-form-wrapper').each(function () {
        var app = $(this);

        var app_data = app.data('js_data');
        window.gd_booking_forms[app_data.listing_id] = window.gd_booking_forms[app_data.listing_id] || [];

        // Init app.
        var booking_form = Vue.createApp({
            data: function data() {
                return Object.assign({}, app_data, {
                    start_date: app_data.start_date || cached_start_date,
                    end_date: app_data.end_date || cached_end_date,
                    adults: app_data.adults || cached_adults,
                    children: app_data.children || cached_children,
                    infants: app_data.infants || cached_infants,
                    pets: app_data.pets || cached_pets,
                    error: '',
                    success: false,
                    booking_id: 0,
                    confirmBooking: false,
                    flatpickr: false,
                    isSubmitting: false,
                    isAvgPrice: false,
                    showValidations: false,
                    weekdays: ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'],
                    date_error: '',
                    bookingDetails: false
                });
            },
            computed: {
                nightlyPrice: function () {
                    var total = 0;
                    var prices = [];

                    if (this.countDays && this.countDays > 0) {
                        for (var i = 0; i < this.countDays; i++) {
                            // Add i days to the start date.
                            var day = formatDate(new Date(this.start_date).fp_incr(i));
                            if (typeof this.day_rules[day] != 'undefined' && this.day_rules[day] != null && parseFloat(this.day_rules[day]) >= parseFloat(Geodir_Booking_Form.night_min_price)) {
                                total += parseFloat(this.day_rules[day]);
                                prices.push(this.day_rules[day]);
                            } else {
                                total += parseFloat(this.ruleset.nightly_price);
                                prices.push(this.ruleset.nightly_price);
                            }
                        }

                        total = this.countDays > 0 ? total / this.countDays : total;

                        // only show avg text if the selected days nightly prices are not the same.
                        let allPricesSame = prices.every(price => price === prices[0]);
                        this.isAvgPrice = !allPricesSame;
                    } else {
                        total += parseFloat(this.ruleset.nightly_price);
                    }

                    total = total.toFixed(Geodir_Booking_Form.decimal_places);

                    return total;
                },
                summary: function () {
                    let summary = `${this.totalGuests} ${this.pluralize('guest', this.totalGuests)}`;

                    if (this.infants > 0) {
                        summary += `, ${this.infants} ${this.pluralize('infant', this.infants)}`;
                    }

                    if (this.pets > 0) {
                        summary += `, ${this.pets} ${this.pluralize('pet', this.pets)}`;
                    }

                    return summary;
                },
                maxAdults: function () {
                    return Math.max(parseInt(this.adults), parseInt(this.minAdults));
                },
                maxChildren: function () {
                    return Math.max(parseInt(this.children), parseInt(this.minChildren));
                },
                maxInfants: function () {
                    return Math.max(parseInt(this.infants), parseInt(this.minInfants));
                },
                maxPets: function () {
                    return Math.max(parseInt(this.pets), parseInt(this.minPets));
                },
                totalGuests: function () {
                    return parseInt(this.adults) + parseInt(this.children);
                },
                isMaxGuestsReached: function () {
                    return this.totalGuests >= this.maxGuests;
                },
                isNameInvalid: function isNameInvalid() {
                    return this.name.length < 3;
                },
                isNameValid: function isNameValid() {
                    return this.name.length && !this.isNameInvalid;
                },
                isEmailInvalid: function isEmailInvalid() {
                    var email_regex = /^[a-zA-Z0-9.!#$%&’*+/=?^_`{|}~-]+@[a-zA-Z0-9-]+(?:\.[a-zA-Z0-9-]+)*$/;
                    return !email_regex.test(this.email);
                },
                isEmailValid: function isEmailValid() {
                    return this.email.length && !this.isEmailInvalid;
                },
                isPhoneInvalid: function isPhoneInvalid() {
                    var phone_regex = /^[\+]?[(]?[0-9]{3}[)]?[-\s\.]?[0-9]{3}[-\s\.]?[0-9]{4,6}$/im;
                    return !phone_regex.test(this.phone);
                },
                isPhoneValid: function isPhoneValid() {
                    return this.phone.length && !this.isPhoneInvalid;
                },
                isDatesInvalid: function isDatesInvalid() {
                    var _wp$i18n = wp.i18n,
                        __ = _wp$i18n.__,
                        sprintf = _wp$i18n.sprintf;

                    // Ensure we have a start date and end date.
                    if (this.start_date.length < 1 || this.end_date.length < 1) {
                        this.date_error = __('Please select a start and end date.', 'geodir-booking');
                        return true;
                    }

                    // Prepare vars.
                    // var start_date = new Date(this.start_date),
                    //     end_date = new Date(this.end_date),
                    //     day_in_milliseconds = 1000 * 60 * 60 * 24,
                    //     difference = end_date.getTime() - start_date.getTime(),
                    //     // Check-out date is not included in the booked dates.
                    //     checkin_day = start_date.getDay(),
                    //     checkout_day = end_date.getDay();

                    // Assuming this.start_date and this.end_date are strings in the format 'YYYY-MM-DD'

                    // Convert the date strings to Date objects using UTC to avoid timezone issues
                    var start_date = new Date(this.start_date + 'T00:00:00Z');
                    var end_date = new Date(this.end_date + 'T00:00:00Z');

                    // Calculate the difference in days
                    var day_in_milliseconds = 1000 * 60 * 60 * 24;
                    var difference = end_date.getTime()  - start_date.getTime();

                    // Get the day of the week (UTC)
                    var checkin_day = start_date.getUTCDay();
                    var checkout_day = end_date.getUTCDay();

                    console.log('start_date ', start_date + ', end_date ', end_date + ', difference ', difference + ', checkin_day ', checkin_day + ', checkout_day ',  checkout_day);

                    // Check that the minimum stay is not exceeded.
                    var minimum_stay = this.ruleset.minimum_stay;
                    if (minimum_stay && minimum_stay > 0 && difference / day_in_milliseconds < minimum_stay) {
                        this.date_error = sprintf(__('The minimum stay is %d nights.', 'geodir-booking'), minimum_stay);
                        return true;
                    }

                    // Check that the maximum stay is not exceeded.
                    var maximum_stay = this.ruleset.maximum_stay;
                    if (maximum_stay && maximum_stay > 0 && difference / day_in_milliseconds > maximum_stay) {
                        this.date_error = sprintf(__('The maximum stay is %d nights.', 'geodir-booking'), maximum_stay);
                        return true;
                    }

                    // Per day minimum stay.
                    if (this.ruleset.per_day_minimum_stay) {
                        var per_day_minimum_stay = this.ruleset[this.weekdays[checkin_day] + '_minimum_stay'];
                        if (per_day_minimum_stay && per_day_minimum_stay > 0 && difference / day_in_milliseconds < per_day_minimum_stay) {
                            this.date_error = sprintf(__('The minimum stay for %s check-ins is %d days.', 'geodir-booking'), this.daysi18n[checkin_day], per_day_minimum_stay);
                            return true;
                        }
                    }

                    // Validate restricted checkin days.
                    if (this.ruleset.restricted_check_in_days && this.ruleset.restricted_check_in_days.length && this.ruleset.restricted_check_in_days.indexOf(checkin_day) > -1) {
                        this.date_error = sprintf(__('%s check-ins are not allowed.', 'geodir-booking'), this.daysi18n[checkin_day]);
                        return true;
                    }

                    // Validate restricted checkout days.
                    if (this.ruleset.restricted_check_out_days && this.ruleset.restricted_check_out_days.length && this.ruleset.restricted_check_out_days.indexOf(checkout_day) > -1) {
                        this.date_error = sprintf(__('%s check-outs are not allowed.', 'geodir-booking'), this.daysi18n[checkout_day]);
                        return true;
                    }
                    return false;
                },
                isDatesValid: function isDatesValid() {
                    return this.start_date.length && this.end_date.length && !this.isDatesInvalid;
                },
                isFormValid: function isFormValid() {
                    return !this.error && this.isNameValid && this.isEmailValid && this.isPhoneValid && this.isDatesValid;
                },
                /**
                 * Counts number of days between start and end date.
                 */
                countDays: function countDays() {
                    if (!this.start_date && !this.start_date) {
                        return 0;
                    }
                    var start_date = new Date(this.start_date), end_date = new Date(this.end_date);
                    return Math.floor((end_date.getTime() - start_date.getTime()) / (1000 * 60 * 60 * 24));
                },
                /**
                 * Counts number of days between today and start date.
                 */
                daysTo: function daysTo() {
                    var start_date = new Date(this.start_date);

                    // Get today's date without the time part
                    var today = new Date();
                    today.setHours(0, 0, 0, 0);

                    // Calculate the difference in milliseconds and convert to days
                    var timeDifference = start_date.getTime() - today.getTime();
                    var daysDifference = Math.floor(timeDifference / (1000 * 60 * 60 * 24));

                    return daysDifference;
                },
                lastMinuteDiscount: function lastMinuteDiscount() {
                    var difference = this.daysTo,
                        last_minute_discounts = this.ruleset.last_minute_discounts;

                    last_minute_discounts.length && last_minute_discounts.sort((a, b) => a.days - b.days);

                    // Loop through the discounts and return the first discount that matches.
                    for (var i = 0; i < last_minute_discounts.length; i++) {
                        var discount = last_minute_discounts[i];
                        if (difference < discount.days) {
                            // Convert to percentage.
                            return discount.percent / 100;
                        }
                    }

                    return 0;
                },
                earlyBirdDiscount: function earlyBirdDiscount() {
                    var difference = this.daysTo,
                        early_bird_discounts = this.ruleset.early_bird_discounts;

                    early_bird_discounts.length && early_bird_discounts.sort((a, b) => b.months - a.months);

                    // Convert difference to months.
                    var months = Math.floor(difference / 30);

                    // Loop through the discounts and return the first discount that matches.
                    for (var i = 0; i < early_bird_discounts.length; i++) {
                        var discount = early_bird_discounts[i];
                        if (months >= discount.months) {
                            // Convert to percentage.
                            return discount.percent / 100;
                        }
                    }

                    return 0;
                },
                durationDiscount: function durationDiscount() {
                    var difference = this.countDays,
                        duration_discounts = this.ruleset.duration_discounts;

                    duration_discounts.length && duration_discounts.sort((a, b) => b.nights - a.nights);

                    // Loop through the discounts and return the first discount that matches.
                    for (var i = 0; i < duration_discounts.length; i++) {
                        var discount = duration_discounts[i];
                        if (difference >= discount.nights) {
                            // Convert to percentage.
                            return discount.percent / 100;
                        }
                    }

                    return 0;
                },
                // Calculate the total discount percentage.
                totalPercentageDiscount: function totalPercentageDiscount() {
                    // Choose between early bird and last minute discount, they are mutually exclusive
                    let totalDiscount = Math.max(this.earlyBirdDiscount, this.lastMinuteDiscount);

                    // Calculate total discount (time-based discount + duration discount)
                    totalDiscount += parseFloat(this.durationDiscount);

                    // Ensure the total discount does not exceed the maximum allowed combination (2 discounts)
                    return Math.min(totalDiscount, 1);
                },
                totalDiscount: function totalDiscount() {
                    var discount = this.totalPercentageDiscount * 100;
                    discount = this.formatAmount(discount);

                    return discount;
                },
                totalDiscountedAmount: function totalDiscountedAmount() {
                    var amount = 0;

                    if (this.totalAmountWithFees > 0) {
                        amount = this.totalAmountWithFees - this.totalAmountWithDiscount;
                    }

                    return parseFloat(amount);
                },
                // Calculate the subtotal price.
                subtotalAmount: function subtotalAmount() {
                    // Loop from the start date to the end date and calculate the price for each date.
                    var total = 0;
                    for (var i = 0; i < this.countDays; i++) {
                        // Add i days to the start date.
                        var day = formatDate(new Date(this.start_date).fp_incr(i));
                        if (typeof this.day_rules[day] != 'undefined' && this.day_rules[day] != null && parseFloat(this.day_rules[day]) >= parseFloat(Geodir_Booking_Form.night_min_price)) {
                            total += parseFloat(this.day_rules[day]);
                        } else {
                            total += parseFloat(this.ruleset.nightly_price);
                        }
                    }

                    total = parseFloat(total).toFixed(Geodir_Booking_Form.decimal_places);

                    return parseFloat(total);
                },
                // Calculate the total fees.
                totalFees: function totalFees() {
                    var amount = parseFloat(this.cleaningFee) + parseFloat(this.petFee) + parseFloat(this.extraGuestsFee);

                    return parseFloat(amount);
                },
                // Calculate the total price.
                totalAmountWithFees: function totalAmountWithFees() {
                    var amount = parseFloat(this.subtotalAmount) + parseFloat(this.totalFees);
                    amount = parseFloat(amount).toFixed(Geodir_Booking_Form.decimal_places);

                    return parseFloat(amount);
                },
                totalAmountWithDiscount: function totalAmountWithDiscount() {
                    var amount = this.totalAmountWithFees;

                    // Apply discounts.
                    if (this.totalPercentageDiscount > 0) {
                        amount = amount - (amount * this.totalPercentageDiscount);
                    }

                    return parseFloat(amount);
                },
                totalPayableAmount: function totalPayableAmount() {
                    var amount = this.totalAmountWithDiscount;
                    amount += parseFloat(this.serviceFee);

                    return parseFloat(amount);
                },
                // Checks if we have a service fee.
                hasServiceFee: function hasServiceFee() {
                    return this.totalAmountWithDiscount > 0 && this.service_fee > 0;
                },
                // Calculate the service fee.
                serviceFee: function serviceFee() {
                    var amount = this.totalAmountWithDiscount * this.service_fee / 100;
                    return parseFloat(amount);
                },
                // Calculate the cleaning fee.
                cleaningFee: function cleaningFee() {
                    return parseFloat(this.ruleset.cleaning_fee);
                },
                // Calculate the pet fee.
                petFee: function petFee() {
                    return (this.pets > 0) ? parseFloat(this.ruleset.pet_fee) : 0;
                },
                // Checks if we have extra guests.
                hasExtraGuest: function hasExtraGuest() {
                    if (this.ruleset.extra_guest_count == 0 || this.ruleset.extra_guest_fee == 0) {
                        return false;
                    }

                    return this.totalGuests > this.ruleset.extra_guest_count;
                },
                extraGuests: function () {
                    return (this.totalGuests > this.ruleset.extra_guest_count) ? this.totalGuests - this.ruleset.extra_guest_count : this.totalGuests;
                },
                // Calculate the extra guest fee.
                extraGuestsFee: function extraGuestsFee() {
                    const extra_guest_count = this.ruleset.extra_guest_count;
                    const extra_guest_fee = this.ruleset.extra_guest_fee;

                    var amount = 0;
                    if ((this.totalGuests > extra_guest_count) && this.countDays > 0) {
                        amount = (parseInt(this.extraGuests) * parseFloat(extra_guest_fee)) * parseInt(this.countDays);
                    }

                    return parseFloat(amount);
                },
                // Show or hide the select dates text
                selectDatesText: function selectDatesText() {
                    return this.subtotalAmount > 0 ? '' : this.select_dates_text;
                }
            },
            watch: {
                totalGuests: function (newVal, oldVal) {
                    if (newVal > this.maxGuests) {
                        this.adults = oldVal > this.maxGuests ? this.adults : this.adults - (newVal - this.maxGuests);
                        this.children = oldVal > this.maxGuests ? this.children : this.children - (newVal - this.maxGuests);
                    }
                }
            },
            methods: {
                commaNumber: function commaNumber(n) {
                    const parts = n.toString().split(".");
                    const formattedInteger = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, Geodir_Booking_Form.thousands_separator);
                    const formattedNumber = parts[1] && parseInt(parts[1]) > 0 ? formattedInteger + Geodir_Booking_Form.decimal_separator + parts[1] : formattedInteger;

                    return formattedNumber;
                },
                // Formats an amount.
                formatAmount: function formatAmount(amount) {
                    amount = parseFloat(amount).toFixed(Geodir_Booking_Form.decimal_places);

                    return this.commaNumber(amount);
                },
                increment: function (type) {
                    switch (type) {
                        case 'adults':
                            if (!this.maxGuests || ((parseInt(this.adults) + parseInt(this.children)) < this.maxGuests)) {
                                this.adults++;
                            }
                            break;
                        case 'children':
                            if (!this.maxGuests || ((parseInt(this.adults) + parseInt(this.children)) < this.maxGuests)) {
                                this.children++;
                            }
                            break;
                        case 'infants':
                            if (!this.maxInfants || this.infants < this.maxInfants) {
                                this.infants++;
                            }
                            break;
                        case 'pets':
                            if (!this.maxPets || this.pets < this.maxPets) {
                                this.pets++;
                            }
                            break;
                    }
                },
                decrement: function (type) {
                    switch (type) {
                        case 'adults':
                            this.adults = Math.max(this.adults - 1, this.minAdults);
                            break;
                        case 'children':
                            this.children = Math.max(this.children - 1, this.minChildren);
                            break;
                        case 'infants':
                            this.infants = Math.max(this.infants - 1, this.minInfants);
                            break;
                        case 'pets':
                            this.pets = Math.max(this.pets - 1, this.minPets);
                            break;
                    }
                },
                pluralize: function (word, count) {
                    return count === 1 ? word : word + 's';
                },
                // Checks whether a given field is valid or not.
                isFieldInvalid: function isFieldInvalid(isInvalid, isFieldInvalid = false) {
                    return isInvalid && (this.showValidations || isFieldInvalid);
                },
                // Retrieves the field class.
                fieldClass: function fieldClass(isInvalid, isValid, isFieldInvalid = false) {
                    return {
                        'is-invalid': isInvalid && (this.showValidations || isFieldInvalid),
                        'is-valid': isValid && (this.showValidations || isFieldInvalid)
                    };
                },
                // Saves the current booking.
                saveBooking: function saveBooking() {
                    var _this = this;
                    this.error = '';
                    this.success = false;
                    this.isSubmitting = true;
                    this.showValidations = true;
                    this.bookingDetails = false;
                    this.confirmBooking = false;
                    if (!this.isFormValid) {
                        this.isSubmitting = false;
                        return;
                    }

                    // POST
                    wp
                        // Save the ruleset.
                        .apiFetch({
                            path: '/geodir/v2/booking/process_booking',
                            method: 'POST',
                            data: {
                                name: this.name,
                                email: this.email,
                                phone: this.phone,
                                start_date: this.start_date,
                                encrypted_email: this.encrypted_email,
                                end_date: this.end_date,
                                ruleset_id: this.ruleset.id,
                                listing_id: this.listing_id,
                                booking_id: this.booking_id,
                                guests: this.totalGuests,
                                adults: this.adults,
                                children: this.children,
                                infants: this.infants,
                                pets: this.pets,
                                'status': this.status,
                                nonce: this.nonce
                            }
                        })

                        // Copy new items.
                        .then(function (res) {
                            _this.bookingDetails = res.booking;
                            _this.confirmBooking = false;
                            _this.booking_id = res.booking_id;
                            _this.encrypted_email = res.encrypted_email;
                            _this.success = true;
                            return res;
                        })

                        // Cache the booking details to localstorage.
                        .then(function (res) {
                            var details = {
                                'encrypted_email': _this.encrypted_email,
                                'booking_id': _this.booking_id
                            };
                            localStorage.setItem("geodir_booking_".concat(_this.listing_id), JSON.stringify(details));
                            return res;
                        })

                        // Delete cached dates.
                        .then(function (res) {
                            localStorage.removeItem('gd_booking_start_date');
                            localStorage.removeItem('gd_booking_end_date');
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
                            _this.error = __('An unexpected error occured. Please try again.', 'geodir-booking');
                        }
                        setTimeout(function () {
                            _this.error = '';
                        }, 3000);
                    })

                    // Unblock the form.
                    ["finally"](function () {
                        _this.isSubmitting = false;
                    });
                },
                // Sets the booking dates.
                setBookingDates: function setBookingDates(start_date, end_date) {
                    this.start_date = start_date;
                    this.end_date = end_date;
                    this.initFlatpickr();
                },
                // Destroy flatpickr.
                destroyFlatpickr: function destroyFlatpickr() {
                    // Maybe clear the existing instance.
                    var flatpk = app.find('.gd-booking-dates');
                    if (flatpk[0] && flatpk[0]._flatpickr) {
                        flatpk[0]._flatpickr.destroy();
                    }
                },
                // Init flatpickr.
                initFlatpickr: function initFlatpickr() {
                    var _this2 = this;
                    var _wp$i18n = wp.i18n,
                        __ = _wp$i18n.__,
                        sprintf = _wp$i18n.sprintf;

                    this.destroyFlatpickr();

                    // Create a flatpickr instance to select a date range.
                    // And exclude the dates that are not available.
                    app.find('.gd-booking-dates').flatpickr({
                        // Cannot select past dates.
                        minDate: 'today',
                        // Allow bookings for up to a year in advance.
                        maxDate: new Date().fp_incr(365),
                        // Set the default date.
                        defaultDate: [this.start_date, this.end_date],
                        // We're picking a date range.
                        mode: 'range',
                        // Set disabled dates.
                        disable: [
                            /**
                             * Disable booked dates.
                             *
                             * @param {Date} date
                             */
                            function (date) {
                                return _this2.disabled_dates.indexOf(formatDate(date)) > -1;
                            }
                        ],
                        onReady: function (selectedDates, dateStr, instance) {
                            // Add a custom class to the .flatpickr-calendar div
                            instance.calendarContainer.classList.add('gd-booking-flatpickr-calendar');
                        },
                        onDayCreate: function (dObj, dStr, fp, dayElem) {
                            var minStay = _this2.ruleset.minimum_stay || 1;
                            var maxStay = _this2.ruleset.maximum_stay || 365;
                            var dayOfWeek = dayElem.dateObj.getDay();

                            // Create a tooltip element
                            var tooltip = document.createElement('div');
                            tooltip.className = 'flatpickr-tooltip';
                            tooltip.style.display = 'none';
                            dayElem.appendChild(tooltip);

                            // Set data attributes for restrictions
                            dayElem.dataset.restrictedCheckin = _this2.ruleset.restricted_check_in_days && _this2.ruleset.restricted_check_in_days.includes(dayOfWeek);
                            dayElem.dataset.restrictedCheckout = _this2.ruleset.restricted_check_out_days && _this2.ruleset.restricted_check_out_days.includes(dayOfWeek);

                            // show taken dates with a strikethrough
                            if (dayElem.classList.contains('flatpickr-disabled')) {
                                dayElem.classList.add('text-decoration-line-through');
                            }

                            // Add event listeners for mouse enter and leave
                            dayElem.addEventListener('mouseenter', function (e) {
                                var tooltipText = '';
                                var isRestricted = false;

                                if (fp.selectedDates.length === 0 || fp.selectedDates.length === 2) {
                                    // No dates selected yet, show only check-in restrictions
                                    if (dayElem.dataset.restrictedCheckin === 'true') {
                                        tooltipText = sprintf(__('%s check-ins are not allowed.', 'geodir-booking'), _this2.daysi18n[dayOfWeek]);
                                        isRestricted = true;
                                    }
                                } else if (fp.selectedDates.length === 1) {
                                    // Check-in date selected, show check-out restrictions and stay duration info
                                    var startDate = fp.selectedDates[0];
                                    var currentDate = dayElem.dateObj;
                                    var diffDays = Math.abs((currentDate - startDate) / (1000 * 60 * 60 * 24));

                                    if (dayElem.dataset.restrictedCheckout === 'true') {
                                        tooltipText = sprintf(__('%s check-outs are not allowed.', 'geodir-booking'), _this2.daysi18n[dayOfWeek]);
                                        isRestricted = true;
                                    } else if (minStay > 1 && diffDays < minStay) {
                                        tooltipText = sprintf(__('Minimum stay is %d nights', 'geodir-booking'), minStay);
                                        isRestricted = true;
                                    } else if (diffDays > maxStay) {
                                        tooltipText = sprintf(__('Maximum stay is %d nights', 'geodir-booking'), maxStay);
                                        isRestricted = true;
                                    }
                                }

                                if (tooltipText) {
                                    tooltip.textContent = tooltipText;
                                    tooltip.style.display = 'block';

                                    // Position the tooltip
                                    var rect = dayElem.getBoundingClientRect();
                                    var calendarRect = fp.calendarContainer.getBoundingClientRect();

                                    if (rect.left - calendarRect.left < calendarRect.width / 2) {
                                        tooltip.style.left = '0';
                                        tooltip.style.right = 'auto';
                                        tooltip.classList.remove('right-tooltip');
                                        tooltip.classList.add('left-tooltip');
                                    } else {
                                        tooltip.style.left = 'auto';
                                        tooltip.style.right = '0';
                                        tooltip.classList.remove('left-tooltip');
                                        tooltip.classList.add('right-tooltip');
                                    }

                                    // Disable date selection on hover for restricted dates
                                    if (isRestricted) {

                                        if (dayElem.classList.contains('flatpickr-disabled')) {
                                            // already disabled
                                        }else{
                                            dayElem.classList.add('flatpickr-disabled','gdbc-disabled');
                                        }


                                    }
                                }
                            });
                            dayElem.addEventListener('mouseleave', function () {
                                tooltip.style.display = 'none';
                                // Remove the disabled class if it was added on hover
                                if (dayElem.classList.contains('flatpickr-disabled') &&
                                    dayElem.classList.contains('gdbc-disabled') &&
                                    !dayElem.hasAttribute('aria-disabled')) {
                                    dayElem.classList.remove('flatpickr-disabled','gdbc-disabled');
                                }
                            });
                        },
                        onChange: function onChange(selectedDates, dateStr, instance) {
                            if (selectedDates[0]) {
                                _this2.start_date = formatDate(selectedDates[0]);
                            } else {
                                _this2.end_date = '';
                            }
                            if (selectedDates[1]) {
                                _this2.end_date = formatDate(selectedDates[1]);
                            } else {
                                _this2.end_date = '';
                            }

                            // Save dates to local storage.
                            if (_this2.start_date && _this2.end_date) {
                                localStorage.setItem('gd_booking_start_date', _this2.start_date);
                                localStorage.setItem('gd_booking_end_date', _this2.end_date);
                            }


                            // Rerender the calendar to update disabled dates and tooltips
                            instance.redraw();
                        },
                        onClose: function onClose(selectedDates, dateStr, instance) {
                            var minStay = _this2.ruleset.minimum_stay || 1;
                            var maxStay = _this2.ruleset.maximum_stay || 365;

                            if (selectedDates.length === 2) {
                                var diffDays = Math.ceil((selectedDates[1] - selectedDates[0]) / (1000 * 60 * 60 * 24));
                                if (minStay && maxStay && (diffDays < minStay || diffDays > maxStay)) {
                                    instance.clear();
                                    // Instead of an alert, you might want to show an error message in the UI
                                    _this2.date_error = sprintf(__('Please select a stay between %d and %d nights', 'geodir-booking'), minStay, maxStay);
                                } else {
                                    _this2.date_error = '';
                                }
                            }
                        }
                    });
                }
            },
            mounted: function mounted() {
                // Init flatpickr.
                this.initFlatpickr();

                // Refresh flatpickr when the tab is clicked.
                $('body').on('shown.bs.tab', this.initFlatpickr);
            },
            unmounted: function unmounted() {
                this.destroyFlatpickr();
            }
        }).mount(this);
        window.gd_booking_forms[app_data.listing_id].push(booking_form);
    });
});
