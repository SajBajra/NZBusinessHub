"use strict";

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
jQuery(document).ready(function ($) {
  // Init app.
  window.geodir_booknow_app = Vue.createApp({
    data: function data() {
      return Object.assign({}, GD_Booking_BookNow, {
        phone: '',
        start_date: '',
        end_date: '',
        error: '',
        success: false,
        listing_id: 0,
        booking_id: 0,
        confirmBooking: false,
        ruleset: {},
        disabled_dates: [],
        flatpickr: false,
        isSubmitting: false,
        showValidations: false,
        weekdays: ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'],
        date_error: '',
        bookingDetails: false
      });
    },
    computed: {
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
        var start_date = new Date(this.start_date),
          end_date = new Date(this.end_date),
          day_in_milliseconds = 1000 * 60 * 60 * 24,
          difference = end_date.getTime() - start_date.getTime(),
          // Check-out date is not included in the booked dates.
          checkin_day = start_date.getDay(),
          checkout_day = end_date.getDay();

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
      }
    },
    methods: {
      // Checks whether a given field is valid or not.
      isFieldInvalid: function isFieldInvalid(isInvalid) {
        return isInvalid && this.showValidations;
      },
      // Retrieves the field class.
      fieldClass: function fieldClass(isInvalid, isValid) {
        return {
          'is-invalid': this.isFieldInvalid(isInvalid),
          'is-valid': isValid
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
            nonce: this.nonce
          }
        })

        // Copy new items.
        .then(function (res) {
          _this.bookingDetails = res.booking;
          _this.confirmBooking = true;
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
      // Displays the booking edit form.
      showEditForm: function showEditForm() {
        this.error = '';
        this.success = false;
        this.isSubmitting = false;
        this.showValidations = true;
        this.bookingDetails = false;
        this.confirmBooking = false;
        this.initFlatpickr();
      },
      // Confirms the current booking.
      confirmBookingDetails: function confirmBookingDetails() {
        var _this2 = this;
        this.error = '';
        this.success = false;
        this.isSubmitting = true;

        // POST
        wp

        // Save the ruleset.
        .apiFetch({
          path: "/geodir/v2/booking/".concat(this.booking_id, "/customer_confirm"),
          method: 'POST',
          data: {
            encrypted_email: this.encrypted_email,
            nonce: this.nonce
          }
        })

        // Copy new items.
        .then(function (res) {
          _this2.bookingDetails = res.booking;
          _this2.confirmBooking = false;
          _this2.success = true;

          // Redirect to the payment page.
          if (res.redirect && res.redirect.length) {
            window.location.href = res.redirect;
          }
          return res;
        })

        // Delete the cached details from localstorage.
        .then(function (res) {
          // Delete cached booking details.
          if (res.booking) {
            localStorage.removeItem("geodir_booking_".concat(_this2.listing_id));
          }
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
            _this2.error = __('An unexpected error occured. Please try again.', 'geodir-booking');
          }
          setTimeout(function () {
            _this2.error = '';
          }, 3000);
        })

        // Unblock the form.
        ["finally"](function () {
          _this2.isSubmitting = false;
        });
      },
      // Refreshses the booking cache.
      refreshBookingDetails: function refreshBookingDetails(data) {
        var _this3 = this;
        this.isSubmitting = true;
        wp

        // Refresh the details.
        .apiFetch({
          path: "/geodir/v2/booking/".concat(data.booking_id, "/refresh_details?encrypted_email=").concat(data.encrypted_email, "&nonce=").concat(this.nonce)
        })

        // Copy new items.
        .then(function (res) {
          // Copy keys from res to the object.
          Object.keys(res).forEach(function (key) {
            _this3[key] = res[key];
          });

          // Check if selected from availability calendar.
          if (window.geodir_booking_dates && window.geodir_booking_dates[_this3.listing_id]) {
            geodir_booknow_app.$data.start_date = window.geodir_booking_dates[_this3.listing_id].start_date;
            geodir_booknow_app.$data.end_date = window.geodir_booking_dates[_this3.listing_id].end_date;
          }
          _this3.initFlatpickr();
          return res;
        })

        // Handle errors.
        ["catch"](function (err) {
          console.log(err);
        })

        // Unblock the form.
        ["finally"](function () {
          _this3.isSubmitting = false;
        });
      },
      // Destroy flatpickr.
      destroyFlatpickr: function destroyFlatpickr() {
        // Maybe clear the existing instance.
        var flatpk = $('#geodir-book-now-date');
        if (flatpk[0] && flatpk[0]._flatpickr) {
          flatpk[0]._flatpickr.destroy();
        }
      },
      // Init flatpickr.
      initFlatpickr: function initFlatpickr() {
        var _this4 = this;
        this.destroyFlatpickr();

        // Create a flatpickr instance to select a date range.
        // And exclude the dates that are not available.
        $('#geodir-book-now-date').flatpickr({
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
            return _this4.disabled_dates.indexOf(formatDate(date)) > -1;
          }],
          // Set start and end dates on change.
          onChange: function onChange(selectedDates) {
            if (selectedDates[0]) {
              _this4.start_date = formatDate(selectedDates[0]);
            }
            if (selectedDates[1]) {
              _this4.end_date = formatDate(selectedDates[1]);
            } else if (selectedDates[0]) {
              _this4.end_date = formatDate(new Date(selectedDates[0]).fp_incr(1));
            } else {
              _this4.end_date = '';
            }
          }
        });
      }
    },
    mounted: function mounted() {
      // Init flatpickr.
      this.initFlatpickr();
    },
    unmounted: function unmounted() {
      // Destroy flatpickr.
      this.destroyFlatpickr();
    }
  }).mount('#geodir-book-now-form-modal-body');

  // Toggle bootstrap modal whenever button is clicked.
  $('body').on('click', '.geodir-book-now-btn', function (e) {
    e.preventDefault();
    var listing_id = $(this).data('id');

    // Prepare vars.
    geodir_booknow_app.$data.listing_id = listing_id;
    geodir_booknow_app.$data.booking_id = 0;
    geodir_booknow_app.$data.ruleset = $(this).data('ruleset');
    geodir_booknow_app.$data.disabled_dates = $(this).data('disabled_dates');
    geodir_booknow_app.$data.start_date = '';
    geodir_booknow_app.$data.end_date = '';
    geodir_booknow_app.$data.success = false;
    geodir_booknow_app.$data.error = '';
    geodir_booknow_app.$data.isSubmitting = false;
    geodir_booknow_app.$data.showValidations = false;
    geodir_booknow_app.$data.bookingDetails = false;
    geodir_booknow_app.$data.confirmBooking = false;
    if (geodir_booknow_app.$data.flatpickr && geodir_booknow_app.$data.flatpickr.clear) {
      geodir_booknow_app.$data.flatpickr.clear();
    }

    // Maybe fetch previous state from local storage.
    if (localStorage.getItem("geodir_booking_".concat(listing_id))) {
      var cached_booking_data = JSON.parse(localStorage.getItem("geodir_booking_".concat(listing_id)));
      if (cached_booking_data) {
        geodir_booknow_app.refreshBookingDetails(cached_booking_data);
      }
    }

    // Check if selected from availability calendar.
    if (window.geodir_booking_dates && window.geodir_booking_dates[listing_id]) {
      geodir_booknow_app.$data.start_date = window.geodir_booking_dates[listing_id].start_date;
      geodir_booknow_app.$data.end_date = window.geodir_booking_dates[listing_id].end_date;
    }
    geodir_booknow_app.initFlatpickr();

    // Toggle the modal.
    $('#geodir-book-now-modal').modal('toggle');
  });
});