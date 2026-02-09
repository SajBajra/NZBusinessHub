var gmbAccessToken, gmbRefreshToken;

jQuery(function($) {
    $('#gmb_auth_code').val('');
    $('#geodir_gmb_connect').on('click', function(e) {
        setTimeout(function(){
            $('#gmb_auth_code_row').slideDown();
        }, 5000);
        $('#gmb_auth_code').val('');
        var gmbWin = window.open(geodir_params.gmb_auth_url, 'gmb_auth', 'scrollbars=no,menubar=no,height=600,width=750,resizable=yes,toolbar=no,status=no');
        return false;
    });

    $('#geodir_gmb_authorize').on('click', function() {
        var code = $('#gmb_auth_code').val(),
            nonce = $(this).data('nonce');

        if (code && nonce) {
            $btn = jQuery(this);
            var data = {
                'action': 'geodir_gmb_authorize_user',
                'security': nonce,
                'gmb_code': code
            };

            jQuery.ajax({
                    url: geodir_params.ajax_url,
                    type: 'POST',
                    data: data,
                    dataType: 'json',
                    beforeSend: function(xhr, obj) {
                        $btn.prop("disabled", true);
                        $btn.addClass("disabled");
                        $btn.find('.geodir-spin-wrap').remove();
                        $btn.prepend('<span class="geodir-spin-wrap"><i class="fas fa-spinner fa-spin"></i>&nbsp;</span>');
                    }
                })
                .done(function(data, textStatus, jqXHR) {
                    if (typeof data == 'object') {
                        if (data.success) {
                            if (data.data.access_token && data.data.refresh_token) {
                                gmbAccessToken = data.data.access_token;
                                gmbRefreshToken = data.data.refresh_token;

                                $('#gmb_connect_row').hide();
                                $('#gmb_auth_code_row').hide();
                                $('#gmb_authorized_row').show();

                                geodir_social_gmb_accounts();
                            }
                        }

                        if (data.data.message) {
                            alert(data.data.message);
                        }
                    }
                })
                .always(function(data, textStatus, jqXHR) {
                    $btn.prop("disabled", false);
                    $btn.removeClass("disabled");
                    $btn.find('.geodir-spin-wrap').remove();
                });
        } else {
            $('#gmb_auth_code').trigger('focus');
        }
    });
 
    $('#geodir_gmb_revoke').on('click', function() {
        var nonce = $(this).data('nonce');

        if (nonce) {
            $btn = jQuery(this);
            var data = {
                'action': 'geodir_gmb_revoke_user',
                'access_token': gmbAccessToken,
                'security': nonce
            };

            jQuery.ajax({
                    url: geodir_params.ajax_url,
                    type: 'POST',
                    data: data,
                    dataType: 'json',
                    beforeSend: function(xhr, obj) {
                        $('#gmb_auth_code').val('');
                        $btn.prop("disabled", true);
                        $btn.addClass("disabled");
                        $btn.find('.geodir-spin-wrap').remove();
                        $btn.prepend('<span class="geodir-spin-wrap mr-1"><i class="fas fa-spinner fa-spin"></i>&nbsp;</span>');

                        gmbAccessToken = '';
                        gmbRefreshToken = '';

                        $('#gmb_account_row').hide();
                        $('#gmb_location_row').hide();
                        $('#gmb_account_row .gmb-input-wrap').html("<span class=\"form-text\"><i class=\"fas fa-spinner fa-spin mr-1\" aria-hidden=\"true\"></i> " + geodir_params.textGMBAccounts + "</span>");
                        $('#gmb_location_row .gmb-input-wrap').html("<span class=\"form-text\"><i class=\"fas fa-spinner fa-spin mr-1\" aria-hidden=\"true\"></i> " + geodir_params.textGMBLocations + "</span>");
                    }
                })
                .done(function(data, textStatus, jqXHR) {
                    if (typeof data == 'object') {
                        if (data.success) {
                            $('#gmb_connect_row').show();
                            $('#gmb_auth_code_row').hide();
                            $('#gmb_authorized_row').hide();
                        }

                        if (data.data.message) {
                            alert(data.data.message);
                        }
                    }
                })
                .always(function(data, textStatus, jqXHR) {
                    $btn.prop("disabled", false);
                    $btn.removeClass("disabled");
                    $btn.find('.geodir-spin-wrap').remove();
                });
        }
    });
});

function geodir_social_gmb_accounts() {
    jQuery('#gmb_account_row').show();

    var data = {
        'action': 'geodir_gmb_get_accounts',
        'access_token': gmbAccessToken,
        'refresh_token': gmbRefreshToken,
        'security': jQuery('#geodir_gmb_authorize').data('account-nonce')
    };

    jQuery.ajax({
            url: geodir_params.ajax_url,
            type: 'POST',
            data: data,
            dataType: 'json',
            beforeSend: function(xhr, obj) {}
        })
        .done(function(data, textStatus, jqXHR) {
            if (typeof data == 'object') {
                var res = '',
                    success = false;
                if (data.success && data.data.input) {
                    success = true;
                    res = data.data.input;
                } else if (data.data.message) {
                    res = data.data.message;
                }
                if (res) {
                    jQuery('#gmb_account_row .gmb-input-wrap').html(res);
                    if (success) {
                        jQuery("#gmb_account_row select.geodir-select").trigger('geodir-select-init');

                        jQuery('#gmb_account').on('change', function() {
                            geodir_social_gmb_locations(this);
                        });
                    }
                }
            }
        })
        .always(function(data, textStatus, jqXHR) {});
}

function geodir_social_gmb_locations(el) {
    var account = jQuery('#gmb_account').val();

    if (account) {
        jQuery('#gmb_location_row').show();

        $el = jQuery(el);

        var data = {
            'action': 'geodir_gmb_get_locations',
            'access_token': gmbAccessToken,
            'refresh_token': gmbRefreshToken,
            'account': account,
            'security': jQuery('#geodir_gmb_authorize').data('location-nonce')
        };

        jQuery.ajax({
                url: geodir_params.ajax_url,
                type: 'POST',
                data: data,
                dataType: 'json',
                beforeSend: function(xhr, obj) {
                    $el.prop("disabled", true);
                    $el.addClass("disabled");
                    jQuery('#gmb_location_row .gmb-input-wrap').html("<span class=\"form-text\"><i class=\"fas fa-spinner fa-spin mr-1\" aria-hidden=\"true\"></i> " + geodir_params.textGMBLocations + "</span>");
                }
            })
            .done(function(data, textStatus, jqXHR) {
                if (typeof data == 'object') {
                    var res = '',
                        success = false;
                    if (data.success && data.data.input) {
                        success = true;
                        res = data.data.input;
                    } else if (data.data.message) {
                        res = data.data.message;
                    }
                    if (res) {
                        jQuery('#gmb_location_row .gmb-input-wrap').html(res);

                        if (success) {
                            jQuery("#gmb_location_row select.geodir-select").trigger('geodir-select-init');
                        }
                    }
                }
            })
            .always(function(data, textStatus, jqXHR) {
                $el.prop("disabled", false);
                $el.removeClass("disabled");
            });
    } else {
        jQuery('#gmb_location_row').hide();
        jQuery('#gmb_account').trigger('focus');
    }
}