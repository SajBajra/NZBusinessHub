"use strict";

// Init app.
var _methods;
function _defineProperty(obj, key, value) {
    if (key in obj) {
        Object.defineProperty(obj, key, {
            value: value,
            enumerable: true,
            configurable: true,
            writable: true,
        });
    } else {
        obj[key] = value;
    }
    return obj;
}
window.geodir_booking_setup = Vue.createApp({
    data: function data() {
        return Object.assign({}, GD_Booking_Calendar.data, {
            isDragging: false,
            startDay: null,
            endDay: null,
            selectingWithShift: false
        });
    },
    mounted: function mounted() {
        const $ = window.jQuery;

        this.modal = $('.modal');
        this.icalStatusModal = $('#ical-status-modal');
    },
    computed: {
        // Checks if there are any listings.
        hasListings: function hasListings() {
            return this.listings.length > 0;
        },
        // Retrieves the current listing.
        currentListing: function currentListing() {
            var _this = this;
            return this.listings.find(function (listing) {
                return listing.ID == _this.listing_id;
            });
        },
        // Retrieves the current listing's day rules.
        currentListingDayRules: function currentListingDayRules() {
            return this.currentListing ? this.currentListing.day_rules : [];
        },
        // Retrieves the current listing's booked dates.
        currentListingBookedDates: function currentListingBookedDates() {
            return this.currentListing ? this.currentListing.booked_dates : [];
        },
        selectedDayRuleIsAvailable: {
            get: function get() {
                return this.selected_days.length
                    ? this.selected_days[0].is_available
                    : false;
            },
            set: function set(newValue) {
                // Update all days.
                this.selected_days.forEach(function (day) {
                    day.is_available = newValue;
                });
            },
        },
        selectedDayRulePrice: {
            get: function get() {
                if (this.selected_days.length) {
                    if (typeof this.selected_days[0].nightly_price !== 'undefined' && this.selected_days[0].nightly_price !== null) {
                        return Math.max(this.selected_days[0].nightly_price, this.night_min_price);
                    }
                }
                return this.currentListing && this.currentListing.ruleset.nightly_price ? this.currentListing.ruleset.nightly_price : this.night_min_price;
            },
            set: function set(newValue) {
                // Update all days.
                this.selected_days.forEach(function (day) {
                    day.nightly_price = newValue;
                });
            },
        },
        selectedDayNote: {
            get: function get() {
                return this.selected_days.length
                    ? this.selected_days[0].private_note
                    : "";
            },
            set: function set(newValue) {
                // Update all days.
                this.selected_days.forEach(function (day) {
                    day.private_note = newValue;
                });
            },
        },
        selectedDayDates: function selectedDayDates() {
            return this.selected_days
                .map(function (day) {
                    return day.rule_date;
                })
                .join(", ");
        },
        // Returns the selected month.
        month: function month() {
            var month_year = this.current_month_year.split("-");
            return month_year[1] - 1;
        },
        // Returns the previous month.
        lastMonth: function lastMonth() {
            if (this.month === 0) {
                return 11;
            }
            return this.month - 1;
        },
        // Returns the next month.
        nextMonth: function nextMonth() {
            if (this.month === 11) {
                return 0;
            }
            return this.month + 1;
        },
        // Returns the selected year.
        year: function year() {
            var month_year = this.current_month_year.split("-");
            return month_year[0];
        },
        // Returns the previous year.
        lastYear: function lastYear() {
            if (this.lastMonth === 11) {
                return this.year - 1;
            }
            return this.year;
        },
        // Returns the next year.
        nextYear: function nextYear() {
            if (this.nextMonth === 0) {
                return this.year + 1;
            }
            return this.year;
        },
        // Returns the first day in the selected month.
        firstDay: function firstDay() {
            var month_year = this.current_month_year.split("-");
            return new Date(month_year[0], month_year[1]).getDay();
        },
        // Returns the number of days in the selected month.
        daysInMonth: function daysInMonth() {
            return new Date(this.year, this.month + 1, 0).getDate();
        },
        // Returns the number of days in the previous month.
        daysLastMonth: function daysLastMonth() {
            return new Date(this.lastYear, this.lastMonth + 1, 0).getDate();
        },
        // Returns the number of rows in the selected month.
        rowsInMonth: function rowsInMonth() {
            return Math.ceil((this.firstDay + this.daysInMonth) / 7);
        },
        // Returns the month rows.
        monthRows: function monthRows() {
            var rows = [];
            var dayNumber = 1;
            var weekDay = new Date(this.year, this.month).getDay();
            var lastMonthDays = this.daysLastMonth;

            // Calculate the number of rows in the month.
            var rowsInMonth = Math.ceil((weekDay + this.daysInMonth + 1) / 7);
            for (var i = 0; i < rowsInMonth; i++) {
                rows.push([]);

                // Fill the first row with the last month days.
                if (i === 0) {
                    for (var j = 0; j < weekDay; j++) {
                        rows[i].push(
                            this.prepareDayRule({
                                day: lastMonthDays - weekDay + j + 1,
                                month: this.lastMonth,
                                year: this.lastYear,
                                active: false,
                            })
                        );
                    }
                }

                // Add the current month days.
                for (var k = weekDay; k < 7; k++) {
                    if (this.daysInMonth >= dayNumber) {
                        weekDay++;
                        rows[i].push(
                            this.prepareDayRule({
                                day: dayNumber++,
                                month: this.month,
                                year: this.year,
                                active: true,
                            })
                        );
                    }
                }

                // Fill the last row with the next month days.
                if (i === rowsInMonth - 1) {
                    for (var l = 0; l < 7 - weekDay; l++) {
                        rows[i].push(
                            this.prepareDayRule({
                                day: l + 1,
                                month: this.nextMonth,
                                year: this.nextYear,
                                active: false,
                            })
                        );
                    }
                }

                // Reset the weekday.
                weekDay = 0;
            }

            // Returns the number of rows.
            return rows;
        },
    },
    // In component
    methods:
        ((_methods = {
            isToday: function isToday(day) {
                return (
                    this.today ==
                    ""
                        .concat(day.year, "-")
                        .concat(day.month + 1, "-")
                        .concat(day.day)
                );
            },
            listingClass: function listingClass(listing, prefix) {
                return ""
                    .concat(prefix, "__")
                    .concat(listing.post_type, " ")
                    .concat(prefix, "__")
                    .concat(listing.ID);
            },
            formatAmount: function formatAmount(amount) {
                return this.price_format
                    .replace("%1$s", this.currency_symbol)
                    .replace(
                        "%2$s",
                        parseFloat(amount).toFixed(this.decimal_places)
                    );
            },
            addEarlyBirdDiscount: function addEarlyBirdDiscount(ruleset) {
                ruleset.early_bird_discounts.push({
                    months: 1,
                    percent: 10,
                });
            },
            removeEarlyBirdDiscount: function removeEarlyBirdDiscount(
                ruleset,
                discount
            ) {
                ruleset.early_bird_discounts.splice(
                    ruleset.early_bird_discounts.indexOf(discount),
                    1
                );
            },
            addLastMinuteDiscount: function addLastMinuteDiscount(ruleset) {
                ruleset.last_minute_discounts.push({
                    days: 1,
                    percent: 10,
                });
            },
            removeLastMinuteDiscount: function removeLastMinuteDiscount(
                ruleset,
                discount
            ) {
                ruleset.last_minute_discounts.splice(
                    ruleset.last_minute_discounts.indexOf(discount),
                    1
                );
            },
            saveTitle: function saveTitle(listing) {
                // POST
                wp.apiFetch({
                    path: "/geodir/v2/booking/update_listing_title",
                    method: "POST",
                    data: {
                        listing_id: listing.id,
                        new_title: listing.new_title,
                    },
                }).catch(function (error) {
                    return console.error(error);
                });

                listing.editing_title = false;
                listing.post_title = listing.new_title;
                listing.new_title = "";
            },
            pluralize: function pluralize(word, count) {
                return count === 1 ? word : word + 's';
            },
            checkSyncStatus(listing_id, queue_id) {
                const that = this;
                const modal_body = that.icalStatusModal.find('.modal-body');
                const logs_shown = modal_body.find('.geodir-booking-logs li').length;

                wp.apiFetch({
                    path: "/geodir/v2/booking/ical_sync_status",
                    method: "POST",
                    data: {
                        logs_shown,
                        listing_id,
                        queue_id,
                    },
                })
                    .then(function (response) {
                        modal_body.find('.geodir-booking-import-stats').html(response.stats);
                        modal_body.find('.geodir-booking-logs').html(response.logs);

                        // update booked days.
                        that.currentListing.booked_dates = response.booked_dates;

                        if (response.in_progress) {
                            setTimeout(function () {
                                that.checkSyncStatus(listing_id, queue_id);
                            }, 2000);
                        }
                    })
                    .catch(function (err) {
                        return Promise.reject(err);
                    });
            },
            syncExternalCalendars(listing) {
                const that = this;

                listing.is_syncing = true;

                return wp.apiFetch({
                    path: "/geodir/v2/booking/ical_sync",
                    method: "POST",
                    data: {
                        listing_id: listing.id,
                        sync_urls: listing.sync_urls,
                    },
                })
                    .then(function (response) {
                        that.show_ical_status = true;
                        that.ical_sync_status = response;

                        /* check if the modal not visible, show it */
                        if (that.modal.is(":visible")) {
                            that.modal.modal('hide');
                        }

                        /* update the modal-content with the rendered template */
                        that.icalStatusModal.find('.modal-body').html(response.stats);
                        that.icalStatusModal.find('.geodir-booking-import-stats').addClass('alert alert-success');
                        that.icalStatusModal.modal('show');

                        if (response.in_progress) {
                            that.checkSyncStatus(listing.id, response.queue_id);
                        }

                        return response;
                    })
                    .catch(function (err) {
                        return console.error(err);
                    })
                    .finally(function () {
                        listing.is_syncing = false;
                    });
            },
            handleMouseDown(event, day) {
                event.preventDefault();

                this.isDragging = true;
                this.selectingWithShift = event.shiftKey;

                if (day.active) {
                    this.startDay = day;
                    this.endDay = day;

                    if (!this.selectingWithShift) {
                        this.clearSelection(false); // Preserve startDay and endDay
                    }

                    this.toggleSelection(day);
                }
            },
            handleMouseMove(event, day) {
                if (this.isDragging && day.active) {
                    this.endDay = day;
                    this.updateSelection();
                }
            },
            handleMouseUp() {
                this.isDragging = false;
                this.startDay = null;
                this.endDay = null;
            },
            handleMouseLeave() {
                if (this.isDragging) {
                    this.isDragging = false;
                    this.clearSelection();
                }
            },
            handleDayClick(event, day) {
                this.selectingWithShift = event.shiftKey || event.metaKey;

                if (this.selectingWithShift) {
                    // Select range from last selected day to the clicked day
                    this.endDay = day;
                    this.updateSelectionRange();
                } else {
                    // Toggle selection for clicked day
                    this.clearSelection();
                    this.toggleSelection(day);
                }
            },
            toggleSelection(day) {
                if (day.active) {
                    day.selected = !day.selected;

                    if (day.selected) {
                        this.selected_days.push(day);
                    } else {
                        this.selected_days = this.selected_days.filter(d => d !== day);
                    }
                }
            },
            updateSelection() {
                if (this.startDay && this.endDay) {
                    const startDate = Math.min(this.startDay.day, this.endDay.day);
                    const startMonth = Math.min(this.startDay.month, this.endDay.month);
                    const endDate = Math.max(this.startDay.day, this.endDay.day);
                    const endMonth = Math.max(this.startDay.month, this.endDay.month);

                    if (!this.selectingWithShift) {
                        this.clearSelection(false); // Preserve startDay and endDay
                    }

                    this.monthRows.forEach((row) => {
                        row.forEach((day) => {
                            if (day.active && (day.day >= startDate && day.month === startMonth && day.day <= endDate && day.month === endMonth) && !this.selected_days.includes(day)) {
                                day.selected = true;
                                this.selected_days.push(day);
                            }
                        });
                    });

                }
            },
            updateSelectionRange() {
                if (this.startDay && this.endDay) {
                    const startDate = Math.min(this.startDay.day, this.endDay.day);
                    const endDate = Math.max(this.startDay.day, this.endDay.day);

                    this.clearSelection(false); // Preserve startDay and endDay

                    this.monthRows.forEach((row) => {
                        row.forEach((day) => {
                            if (day.active && (day.day >= startDate && day.day <= endDate)) {
                                day.selected = true;
                                this.selected_days.push(day);
                            }
                        });
                    });
                }
            },
            clearSelection(clearAll = true) {
                if (clearAll) {
                    this.startDay = null;
                    this.endDay = null;
                }

                this.selected_days.forEach(day => {
                    day.selected = false;
                });

                this.selected_days = [];
            },
            handleRibbonClick(day) {
                jQuery('[data-gdbc-key]').not(`[data-gdbc-key="${day.checkin_date}"]`).removeClass('gdbc-day__ribbon-clicked');

                var ribbons = jQuery(`[data-gdbc-key="${day.checkin_date}"]`);
                ribbons.addClass('gdbc-day__ribbon-clicked');
            },
            handleRibbonMouseover(day) {
                var ribbons = jQuery(`[data-gdbc-key="${day.checkin_date}"]`);
                ribbons.addClass('gdbc-day__ribbon-clicked');
            },
            handleRibbonMouseleave(day) {
                var ribbons = jQuery(`[data-gdbc-key="${day.checkin_date}"]`);
                ribbons.removeClass('gdbc-day__ribbon-clicked');
            },
            getCalDayAmount(day, currentListing) {
                var dayAmt;
                if (typeof day.nightly_price !== 'undefined' && day.nightly_price !== null) {
                    dayAmt = Math.max(day.nightly_price, this.night_min_price);
                } else {
                    dayAmt = currentListing ? currentListing.ruleset.nightly_price : this.night_min_price;
                }
                return this.formatAmount(dayAmt);
            }
        }),
            _defineProperty(
                _methods,
                "addDurationDiscount",
                function addDurationDiscount(ruleset) {
                    ruleset.duration_discounts.push({
                        days: 1,
                        percent: 10,
                    });
                }
            ),
            _defineProperty(
                _methods,
                "removeDurationDiscount",
                function removeDurationDiscount(ruleset, discount) {
                    ruleset.duration_discounts.splice(
                        ruleset.duration_discounts.indexOf(discount),
                        1
                    );
                }
            ),
            _defineProperty(_methods, "saveRuleset", function saveRuleset(ruleset) {
                ruleset.errror = "";
                ruleset.is_saving = true;

                // POST
                wp

                    // Save the ruleset.
                    .apiFetch({
                        path: "/geodir/v2/booking/update_ruleset",
                        method: "POST",
                        data: {
                            ruleset: ruleset,
                        },
                    })

                    // Copy new items.
                    .then(function (res) {
                        Object.keys(res).forEach(function (key) {
                            ruleset[key] = res[key];
                        });
                        ruleset.is_saved = true;
                        setTimeout(function () {
                            ruleset.is_saved = false;
                        }, 3000);
                        return res;
                    })

                [
                    // Handle errors.
                    "catch"
                ](function (err) {
                    // Error will have a message, code and data that's passed to WP_Error.
                    if (err && err.message) {
                        ruleset.error = err.message;
                    }

                    // If not, render the default error message.
                    else {
                        ruleset.error =
                            "An unexpected error occured. Please try again.";
                    }
                })

                [
                    // Unblock the form.
                    "finally"
                ](function () {
                    ruleset.is_saving = false;
                });
            }),
            _defineProperty(
                _methods,
                "prepareDayRule",
                function prepareDayRule(day) {
                    // Prepare the saved rule.
                    var saved_rule = this.getSavedRule(day);
                    if (!saved_rule) {
                        saved_rule = {
                            is_available: true,
                            rule_date: ""
                                .concat(day.year, "-")
                                .concat(day.month + 1, "-")
                                .concat(day.day),
                            nightly_price: null,
                            private_note: "",
                            listing_id: this.listing_id,
                        };
                    }

                    // Check if the day is in the past.
                    var today = new Date();
                    var rule_date = new Date(
                        day.year,
                        day.month,
                        day.day,
                        24,
                        59,
                        59
                    );
                    day.is_past = day.month == this.month && rule_date < today;
                    day.is_booked = this.isBooked(day);

                    if (day.is_past || day.is_booked) {
                        day.active = false;
                    }

                    const listing = this.currentListing;

                    var month_string = String(day.month + 1).padStart(2, '0');
                    var day_string = String(day.day).padStart(2, '0');
                    var date_key = `${day.year}-${month_string}-${day_string}`;

                    if (listing && listing.booking_details && listing.booking_details[date_key]) {
                        day = Object.assign({}, day, listing.booking_details[date_key]);
                    }

                    // Merge the day with the saved rule.
                    return Object.assign({}, day, saved_rule);
                }
            ),
            _defineProperty(_methods, "getSavedRule", function getSavedRule(day) {
                var date = ""
                    .concat(day.year, "-")
                    .concat(day.month + 1, "-")
                    .concat(day.day, " 00:00:00");
                return this.currentListingDayRules.find(function (day_rule) {
                    return (
                        new Date(
                            "".concat(day_rule.rule_date, " 00:00:00")
                        ).getTime() == new Date(date).getTime()
                    );
                });
            }),
            _defineProperty(_methods, "isBooked", function isBooked(day) {
                var month =
                    9 < day.month + 1 ? day.month + 1 : "0".concat(day.month + 1);
                var _day = 9 < day.day ? day.day : "0".concat(day.day);
                var date = "".concat(day.year, "-").concat(month, "-").concat(_day);

                if (this.currentListingBookedDates[date]) {
                    return this.currentListingBookedDates[date];
                }

                return false;
            }),
            _defineProperty(
                _methods,
                "prepareDayRuleForEditing",
                function prepareDayRuleForEditing(day, append) {
                    if (!day.active) {
                        return;
                    }

                    // Abort if the day is not active.
                    if (!day.active) {
                        return;
                    }

                    this.clearSelection();

                    // Check if the day rule is already being edited.
                    if (this.isDayRuleSelected(day)) {
                        if (append) {
                            this.selected_days.splice(
                                this.selected_days.indexOf(day),
                                1
                            );
                        } else {
                            this.selected_days = [];
                        }
                        return;
                    }

                    // Either append or empty the selected days.
                    if (append) {
                        this.selected_days.push(day);
                    } else {
                        this.selected_days = [day];
                    }
                }
            ),
            _defineProperty(
                _methods,
                "isDayRuleSelected",
                function isDayRuleSelected(day) {
                    return this.selected_days.includes(day);
                }
            ),
            _defineProperty(
                _methods,
                "getDayRuleClass",
                function getDayRuleClass(day) {
                    return {
                        "gdbc-day__is-selected": this.isDayRuleSelected(day) || day.selected,
                        "gdbc-day__is-active": day.active,
                        "gdbc-day__is-inactive": !day.active,
                        "text-muted": !day.active && !day.is_booked,
                        "gdbc-day__is-available": day.is_available,
                        "gdbc-day__is-past": day.is_past,
                        "gdbc-day__is-booked": day.is_booked,
                        "gdbc-day__checkin": day.is_checkin_day,
                        "gdbc-day__is-unavailable": !day.is_available,
                        "bg-dark text-white": !day.is_available && day.active,
                        "gdbc-day__is-lastday": day.day === 31,
                    };
                }
            ),
            _defineProperty(
                _methods,
                "getDayRuleDayClass",
                function getDayRuleDayClass(day) {
                    if (jQuery("body").hasClass("aui_bs5")) {
                        return {
                            "text-muted": !day.is_booked && !this.isToday(day),
                            "text-muted": day.is_booked,
                            "text-info": !day.is_booked && this.isToday(day),
                            "fw-bold": this.isToday(day),
                        };
                    }
                    return {
                        "text-muted": !day.is_booked && !this.isToday(day),
                        "text-muted": day.is_booked,
                        "text-info": !day.is_booked && this.isToday(day),
                        "font-weight-bold": this.isToday(day),
                    };
                }
            ),
            _defineProperty(
                _methods,
                "getRibbonDayClass",
                function getRibbonDayClass(day) {
                    return {
                        "gdbc-day__ribbon": true,
                        "gdbc-day__ribbon-checkin": day.is_checkin_day,
                        "gdbc-day__ribbon-checkout": day.is_checkout_day,
                    };
                }
            ),
            _defineProperty(
                _methods,
                "saveSelectedDayRules",
                function saveSelectedDayRules() {
                    var _this2 = this;
                    this.selectedDayError = "";
                    this.selectedDaySaving = true;
                    var listing_id = this.listing_id;

                    // POST
                    wp

                        // Save the day rule.
                        .apiFetch({
                            path: "/geodir/v2/booking/update_day_rules",
                            method: "POST",
                            data: {
                                day_rules: this.selected_days,
                            },
                        })

                        // Copy new items.
                        .then(function (res) {
                            // Reset day rules.
                            if (listing_id) {
                                var listing = _this2.listings.find(function (
                                    listing
                                ) {
                                    return listing.id == listing_id;
                                });
                                if (listing) {
                                    listing.day_rules = res.day_rules;
                                }
                            }
                            _this2.selectedDaySaved = true;
                            setTimeout(function () {
                                _this2.selectedDaySaved = false;
                            }, 3000);
                            return res;
                        })

                    [
                        // Handle errors.
                        "catch"
                    ](function (err) {
                        // Error will have a message, code and data that's passed to WP_Error.
                        if (err && err.message) {
                            _this2.selectedDayError = err.message;
                        }

                        // If not, render the default error message.
                        else {
                            _this2.selectedDayError =
                                "An unexpected error occured. Please try again.";
                        }
                        setTimeout(function () {
                            _this2.selectedDayError = "";
                        }, 3000);
                    })

                    [
                        // Unblock the form.
                        "finally"
                    ](function () {
                        _this2.selectedDaySaving = false;
                    });
                }
            ),
            _defineProperty(
                _methods,
                "addSyncUrl",
                function addSyncUrl(listing) {
                    listing.sync_urls.push('');
                }
            ),
            _defineProperty(
                _methods,
                "removeSyncUrl",
                function removeSyncUrl(listing, url) {
                    listing.sync_urls.splice(
                        listing.sync_urls.indexOf(url),
                        1
                    );
                }
            ),
            _defineProperty(
                _methods,
                "getPopoverAttributes",
                function getPopoverAttributes(day) {
                    var _wp$i18n = wp.i18n,
                        __ = _wp$i18n.__;

                    return {
                        'data-bs-toggle': 'popover-html',
                        'title': __('Booking Details', 'geodir-booking'),
                        'data-bs-custom-class': 'gdbc-day__popover',
                        'data-bs-content': this.bookingDetailsPopover(day),
                        'data-bs-trigger': 'focus',
                        'data-bs-html': 'true',
                        'data-bs-placement': 'top'
                    };
                }
            ),
            _defineProperty(
                _methods,
                "bookingDetailsPopover",
                function bookingDetailsPopover(day) {
                    var _wp$i18n = wp.i18n,
                        __ = _wp$i18n.__;

                    const listing = this.currentListing;
                    if (!day.checkin_date || listing.booking_details[day.checkin_date] === undefined) {
                        return;
                    }
                    
                    const details = listing.booking_details[day.checkin_date];

                    let bookingDetails = {};
                    if (details.sync_id) {
                        bookingDetails = {
                            [__('Check In', 'geodir-booking')]: details.checkin_formatted,
                            [__('Check Out', 'geodir-booking')]: details.checkout_formatted,
                            [__('Guests', 'geodir-booking')]: '-',
                            [__('UID', 'geodir-booking')]: details.uid,
                            [__('Summary', 'geodir-booking')]: details.ical_summary
                        };

                        if (details.ical_description) {
                            bookingDetails[__('Description', 'geodir-booking')] = details.ical_description;
                        }

                        if (details.ical_prodid) {
                            bookingDetails[__('Source', 'geodir-booking')] = details.ical_prodid;
                        }
                    } else {
                        bookingDetails = {
                            [__('Booking ID', 'geodir-booking')]: `#${details.booking_id}`,
                            [__('Check In', 'geodir-booking')]: details.checkin_formatted,
                            [__('Check Out', 'geodir-booking')]: details.checkout_formatted,
                            [__('Guests', 'geodir-booking')]: details.guests,
                            [__('Amount', 'geodir-booking')]: this.formatAmount(details.amount),
                        };
                    }

                    return `
                        <table class='table table-sm table-striped'>
                            <tbody>
                                ${Object.entries(bookingDetails).map(([key, value]) => `<tr><th class="text-dark">${key}</th><td>${value}</td></tr>`).join('')}
                            </tbody>
                        </table>`;
                }
            ),
            _defineProperty(
                _methods,
                "initPopovers",
                function initPopovers() {
                    const popoverElements = jQuery('.gdbc-calendar-body__wrapper [data-bs-toggle="popover-html"]');

                    popoverElements.popover({
                        html: true,
                        sanitize: false
                    });

                    // Hide popover on click outside.
                    jQuery(document).on('click', function (e) {
                        popoverElements.each(function () {
                            if (!jQuery(this).is(e.target) && jQuery(this).has(e.target).length === 0 && jQuery('.popover').has(e.target).length === 0) {
                                jQuery(this).popover('hide');
                            }
                        });
                    });

                    // Prevent more than a single instance of open popovers.
                    popoverElements.on('click', function () {
                        popoverElements.not(this).popover('hide');
                        jQuery(this).popover('toggle');
                    });

                    popoverElements.on('inserted.bs.popover', function () {
                        jQuery('body > .popover').wrapAll("<div class='bsui' />");
                    });
                }
            ),
            _methods),
    watch: {
        current_month_year: function current_month_year() {
            this.selected_days = [];

            this.$nextTick(() => {
                this.initPopovers();
            });
        },
        listing_id: function listing_id() {
            this.selected_days = [];
        },
    },
}).mount(".geodir-bookings-calendar-outer");

jQuery(document).ready(function ($) {
    // Toggle bootstrap modal whenever button is clicked.
    $("body").on("click", ".geodir-setup-booking-btn", function (e) {
        e.preventDefault();
        // geodir_booking_setup.$data.listing_id = $( this ).data( 'id' );
        geodir_booking_setup.$data.isFullScreen = false;
        geodir_booking_setup.$data.isModal = true;

        // Force reactivity.
        $(".gdbc-header-right__switch-listing")
            .val($(this).data("id"))
            .trigger("change");
        $("#geodir-booking-setup-booking-modal").modal("toggle");

        geodir_booking_setup.$nextTick(() => {
            geodir_booking_setup.initPopovers();
        });
    });

    // Undo is modal when the modal is closed.
    $("#geodir-booking-setup-booking-modal").on(
        "hidden.bs.modal",
        function (e) {
            geodir_booking_setup.$data.isModal = false;
        }
    );

    $(document).on("click", ".geodir-booking-ical-copy-url", function (e) {
        e.preventDefault();
        const $this = $(this);
        const $parent = $this.parents('.accordion-item');
        const url = $parent.find(".geodir-booking-listing-ical-url");
        url.select();

        try {
            const ok = document.execCommand("copy");
            if (ok) {
                $this.addClass("text-success");
                $this.text($this.data("copy-success"));
            }
        } catch (err) {
        }
    });
});
