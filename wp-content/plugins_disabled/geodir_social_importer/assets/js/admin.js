jQuery(function($) {
    $('#gmb_authorize').on('click', function() {
        var code = $('#si_gmb_auth_code').val(),
            nonce = $(this).data('nonce');

        if (code && nonce) {
            $btn = jQuery(this);
            var data = {
                'action': 'geodir_gmb_authorize',
                'security': nonce,
                'gmb_code': code
            };

            jQuery.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: data,
                    dataType: 'json',
                    beforeSend: function(xhr, obj) {
                        $btn.prop("disabled", true);
                        $btn.find('.geodir-spin-wrap').remove();
                        $btn.prepend('<span class="geodir-spin-wrap"><i class="fas fa-spinner fa-spin"></i>&nbsp;</span>');
                    }
                })
                .done(function(data, textStatus, jqXHR) {
                    if (typeof data == 'object') {
                        if (data.data.message) {
                            alert(data.data.message);
                        }

                        if (true === data.data.reload) {
                            window.location.reload();
                            return;
                        }
                    }
                })
                .always(function(data, textStatus, jqXHR) {
                    $btn.prop("disabled", false);
                    $btn.find('.geodir-spin-wrap').remove();
                });
        } else {
            $('#si_gmb_auth_code').trigger('focus');
        }
    });

    $('#gmb_revoke').on('click', function() {
        var nonce = $(this).data('nonce');

        if (nonce) {
            $btn = jQuery(this);
            var data = {
                'action': 'geodir_gmb_revoke',
                'security': nonce
            };

            jQuery.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: data,
                    dataType: 'json',
                    beforeSend: function(xhr, obj) {
                        $btn.prop("disabled", true);
                        $btn.find('.geodir-spin-wrap').remove();
                        $btn.prepend('<span class="geodir-spin-wrap"><i class="fas fa-spinner fa-spin"></i>&nbsp;</span>');
                    }
                })
                .done(function(data, textStatus, jqXHR) {
                    if (typeof data == 'object') {
                        if (data.data.message) {
                            alert(data.data.message);
                        }

                        if (true === data.data.reload) {
                            window.location.reload();
                            return;
                        }
                    }
                })
                .always(function(data, textStatus, jqXHR) {
                    $btn.prop("disabled", false);
                    $btn.find('.geodir-spin-wrap').remove();
                });
        }
    });

    gdfi_posting_to_gmb = false;

    $('.geodir-gmb-post').on('click', function() {
        var post_id = parseInt($(this).data('id')),
            nonce = $(this).data('nonce'),
            postedText = $(this).data('posted');

        if (gdfi_posting_to_gmb) {
            return false;
        }

        if (post_id && nonce) {
            $btn = jQuery(this);
            $wrap = jQuery(this).closest('.misc-pub-post-to-gmb');
            var data = {
                'action': 'gdfi_post_to_gmb_ajax',
                'security': nonce,
                'post_id': post_id
            };

            jQuery.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: data,
                    dataType: 'json',
                    beforeSend: function(xhr, obj) {
                        gdfi_posting_to_gmb = true;
                        $btn.prop("disabled", true);
                        $btn.addClass("disabled");
                        jQuery('.gdfi-posting-wait', $wrap).show();
                    }
                })
                .done(function(data, textStatus, jqXHR) {
                    $btn.prop("disabled", false);
                    $btn.removeClass("disabled");
                    $('.gdfi-posting-wait', $wrap).hide();
                    if (typeof data == 'object') {
                        if (data.success) {
                            $(".dashicons-google", $wrap).css("color", "blue");
                            $btn.text(postedText);
                        }

                        if (data.data.message) {
                            alert(data.data.message);
                        }
                    }
                    gdfi_posting_to_gmb = false;
                })
                .always(function(data, textStatus, jqXHR) {
                    $btn.prop("disabled", false);
                    $btn.removeClass("disabled");
                    $('.gdfi-posting-wait', $wrap).hide();
                    gdfi_posting_to_gmb = false;
                });
        }
    });
});