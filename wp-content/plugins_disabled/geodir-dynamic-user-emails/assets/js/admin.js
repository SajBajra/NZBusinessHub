jQuery(function($) {
    if ($('input#email_list_security').length) {
        GeoDir_Dynamic_Emails_List.init();
    }
    $('.geodir-de-resend').on('click', function(e) {
        geodir_dynamic_emails_send_list(this);
    });
    $('.geodir-de-default').on('click', function(e) {
        geodir_dynamic_emails_set_default(this);
    });
    $('select#email_list_action').on('change', function(e) {
        window.geodirDEChange = true;
        $('.geodir-de-default').trigger('click');
        window.geodirDEChange = false;
    });
    $('select#email_list_action').trigger('change');
    if ($('.geodir-de-field-rows').length) {
        GeoDir_Dynamic_Emails_List.initFields();
    }
});
var GeoDir_Dynamic_Emails_List = {
    init: function() {
        var $self = this;
        this.el = jQuery('.gd-settings-wrap');
        this.form = jQuery('form#mainform');
        jQuery(".geodir-save-button", this.el).on("click", function(e) {
            $self.save(e);
        });
        jQuery("#email_list_post_type", this.el).on("change", function(e) {
            var postType = jQuery(this).val();
            if (!postType) {
                return;
            }
            var fPt = jQuery('.geodir-de-field-rows').attr('data-post-type');
            if (fPt != postType) {
                GeoDir_Dynamic_Emails_List.resetFields();
                jQuery('.geodir-de-field-rows').attr('data-post-type', postType);
            }
            jQuery.ajax({
                url: geodir_params.gd_ajax_url,
                type: 'POST',
                dataType: 'json',
                data: 'email_list_post_type=' + postType + '&action=geodir_email_list_cat_options',
                beforeSend: function() {},
                success: function(res, textStatus, xhr) {
                    if (res.success && typeof res.data.options != 'undefined') {
                        var $sel = jQuery('select#email_list_category');
                        $sel.html(res.data.options);
                        if ($sel.hasClass('select2-hidden-accessible')) {
                            $sel.select2('destroy');
                            $sel.removeClass('select2-hidden-accessible');
                            aui_init_select2();
                        }
                    }
                },
                error: function(xhr, textStatus, errorThrown) {
                    console.log(errorThrown);
                }
            });
        });
    },
    block: function() {
        jQuery('.geodir-save-button', this.el).prop('disabled', true);
        jQuery(this.el).css({
            opacity: 0.6
        });
    },
    unblock: function() {
        jQuery('.geodir-save-button', this.el).prop('disabled', false);
        jQuery(this.el).css({
            opacity: 1
        });
    },
    save: function(e) {
        e.preventDefault();
        var $self = this;
        var err = false;
        if (window.tinyMCE) {
            window.tinyMCE.triggerSave();
        }
        $self.form.find('input,select,textarea').each(function() {
            if (jQuery(this).attr('required') == 'required' && !jQuery(this).val()) {
                jQuery(this).focus();
                err = true;
                return false;
            }
        });
        if (err) {
            return false;
        }
        $self.block();
        jQuery.ajax({
            url: geodir_params.gd_ajax_url,
            type: 'POST',
            dataType: 'json',
            data: $self.form.serialize() + '&action=geodir_save_email_list',
            beforeSend: function() {},
            success: function(res, textStatus, xhr) {
                if (res.success) {
                    if (res.data.item_id && parseInt(res.data.item_id) > 0) {
                        if (!$self.form.find('input#email_list_id').val()) {
                            $self.form.find('input#email_list_id').val(parseInt(res.data.item_id));
                        }
                        try {
                            var lHref = window.location.href;
                            if (lHref.indexOf("&email_list=") === -1) {
                                if (window.history && window.history.replaceState) {
                                    window.history.replaceState(null, "", lHref + "&email_list=" + parseInt(res.data.item_id));
                                }
                            }
                        } catch (err) {}
                    }
                    aui_toast('gd_save_email_list' + Math.floor(Math.random() * 1000), 'success', res.data.message ? res.data.message : geodir_params.txt_saved);
                } else {
                    aui_toast('gd_save_email_list_e' + Math.floor(Math.random() * 1000), 'error', res.data.message);
                }
                $self.unblock();
            },
            error: function(xhr, textStatus, errorThrown) {
                console.log(errorThrown);
                $self.unblock();
            }
        });
    },
    initFields: function() {
        var $self = this;
        jQuery(document).off('click', '.geodir-de-field-add').on('click', '.geodir-de-field-add', function(e) {
            e.preventDefault();
            $self.addField(this);
        });
        jQuery(document).off('click', '.geodir-de-field-remove').on('click', '.geodir-de-field-remove', function(e) {
            e.preventDefault();
            $self.removeField(this);
        });
        jQuery(document).off('change', '.geodir_decf_cond').on('change', '.geodir_decf_cond', function(e) {
            e.preventDefault();
            $self.changeFieldCondition(this);
        });
        if (!jQuery('.geodir-de-field-rows .geodir-de-field-row').length) {
            jQuery('.geodir-de-field-add').trigger('click');
        }
    },
    resetFields: function() {
        jQuery('.geodir-de-field-rows').html('');
        jQuery('.geodir-de-field-add').trigger('click');
    },
    addField: function(el) {
        var cPt = jQuery('#email_list_post_type').val();
        if (!(cPt && jQuery('.geodir-de-tmpl-' + cPt).length)) {
            return;
        }
        var gdTmpl = jQuery('.geodir-de-tmpl-' + cPt).html();
        var c = parseInt(jQuery('.geodir-de-field-rows .geodir-de-field-row:last').data('row-index'));
        if (c > 0) {
            c++;
        } else {
            c = 1;
        }
        gdTmpl = gdTmpl.replace(/GDDEINDEX/g, c);
        jQuery('.geodir-de-field-rows').append(gdTmpl);
    },
    removeField: function(el) {
        jQuery(el).closest('.geodir-de-field-row').remove();
    },
    changeFieldCondition: function(el) {
        var cVal = jQuery(el).val(),
            cRow = jQuery(el).closest('.geodir-de-field-row').find('.geodir_decf_search');
        if (cVal && cVal != 'is_empty' && cVal != 'is_not_empty') {
            cRow.removeAttr('readonly');
        } else {
            cRow.attr({
                'readonly': 'readonly'
            });
        }
    }
}

function geodir_dynamic_emails_send_list(el) {
    var $el = jQuery(el),
        listId = parseInt($el.data('id')),
        security = $el.data('nonce');
    if (!listId || !security || window.geodirDESending) {
        return;
    }
    jQuery.ajax({
        url: geodir_params.gd_ajax_url,
        type: 'POST',
        dataType: 'json',
        data: 'action=geodir_send_email_list&list_id=' + listId + '&security=' + security,
        beforeSend: function() {
            window.geodirDESending = true;
            $el.closest('.row-actions').addClass('gd-left-0');
            $el.addClass('text-muted');
            $el.find('i').addClass('d-none');
            $el.find('.fa-spin').removeClass('d-none');
        },
        success: function(res, textStatus, xhr) {
            var toastT, toastM;
            if (typeof res == 'object') {
                if (res.success) {
                    toastT = 'success';
                } else {
                    toastT = 'error';
                }
                if (res.data.message) {
                    toastM = res.data.message;
                }
            }
            if (toastT && toastM) {
                aui_toast('gd_send_email_list' + Math.floor(Math.random() * 1000), toastT, toastM);
            }
        },
        error: function(xhr, textStatus, errorThrown) {
            console.log(errorThrown);
        }
    }).always(function(data, textStatus, jqXHR) {
        window.geodirDESending = false;
        $el.closest('.row-actions').removeClass('gd-left-0');
        $el.removeClass('text-muted');
        $el.find('i').removeClass('d-none');
        $el.find('.fa-spin').addClass('d-none');
    });
}

function geodir_dynamic_emails_set_default(el) {
    var $el = jQuery(el),
        action = $el.closest('form').find('select#email_list_action').val(),
        field = $el.data('field'),
        defVal;
    if (!action || !field) {
        return;
    }
    try {
        defVal = geodirDynamicEmailsAdmin.actions[action][field];
    } catch (err) {
        defVal = '';
    }
    if (!defVal) {
        return;
    }
    if (!(window.geodirDEChange && $el.closest('form').find('#email_list_' + field).val())) {
        $el.closest('form').find('#email_list_' + field).val(defVal);
    }
    $el.closest('form').find('#email_list_' + field).attr({
        'placeholder': defVal
    });
}