(function ($, Geodir_Bookings) {
    "use strict";

    // button status
    function gdbc_button_status(element, handle) {
        if (handle == "loading") {
            /* loading */
            element.data('text', element.html());
            element.prop('disabled', true);
            element.html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> <span>Loading...</span>');
        } else {
            /* reset */
            element.prop('disabled', false);
            element.html(element.data('text'));
        }
    }

    $(function () {
        $('.geodir-booking-price-breakdown-expand').on('click', function(event) {
            event.preventDefault();
            var _this = $(this);
            var tr = _this.closest('tr.geodir-booking-price-breakdown-group');

            _this.blur(); // Don't save a:focus style on last clicked item

            tr.find('.geodir-booking-price-breakdown-rate').toggleClass('geodir-booking-hide');
            tr.nextUntil('tr.geodir-booking-price-breakdown-group').toggleClass('geodir-booking-hide');

            _this.children('.geodir-booking-inner-icon').toggleClass('geodir-booking-hide');
        });

        $('.geodir-booking-create-booking').on('submit', function (e) {
            e.preventDefault();
            var action = 'geodir_booking_create_booking';
            var submit = $(this).find('button[type="submit"]');
            var error = $(this).find('.alert.alert-danger');
            var success = $(this).find('.alert.alert-success');
            var data = $(this).serializeObject();

            data['action'] = action;
            data['geodir_booking_nonce'] = Geodir_Bookings.nonces.hasOwnProperty(action) ? Geodir_Bookings.nonces[action] : '';

            /* show loading */
            gdbc_button_status(submit, "loading");

            $.post(Geodir_Bookings.ajax_url, data, function (response) {
                /* hide loading */
                gdbc_button_status(submit, "reset");

                if (response.success === false) {
                    if (success.is(":visible")) success.hide(); // hide previous alert
                    error.find('span').html(response.data.message), error.slideDown();
                } else if (response.success === true) {
                    if (error.is(":visible")) error.hide(); // hide previous alert
                    success.find('span').html(response.data.message), success.slideDown();
                } else {
                    eval(response.callback);
                }
            }, "json")
                .fail(function (err) {
                    /* hide loading */
                    gdbc_button_status(submit, "reset");
                    /* handle error */
                    if (success.is(":visible")) success.hide(); // hide previous alert

                    let error_message = Geodir_Bookings.i18n.error;
                    if (err.responseJSON && err.responseJSON.message) {
                        error_message = err.responseJSON.message;
                    }

                    error.find('span').html(error_message), error.slideDown();
                });
        })
    });

    $.fn.serializeObject = function () {
        var o = {};
        var a = this.serializeArray();
        $.each(a, function () {
            if (o[this.name]) {
                if (!o[this.name].push) {
                    o[this.name] = [o[this.name]];
                }
                o[this.name].push(this.value || '');
            } else {
                o[this.name] = this.value || '';
            }
        });
        return o;
    };

})(jQuery, Geodir_Bookings);