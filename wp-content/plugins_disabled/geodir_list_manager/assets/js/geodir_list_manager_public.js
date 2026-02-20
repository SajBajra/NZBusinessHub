jQuery(function() {
    if (jQuery('.uwp-profile-entries .geodir-list-loop-actions-container .gd-lists-loop-author-actions').length) {
        var $output = jQuery('.uwp-profile-entries .geodir-list-loop-actions-container .gd-lists-loop-author-actions').clone();
        if (jQuery('.uwp-loop-posts-toolbar').is(':visible')) {
            jQuery('.uwp-loop-posts-toolbar').addClass('text-end text-right').prepend($output);
            jQuery('.gd-lists-loop-author-actions, .uwp-loop-posts-toolbar .btn-toolbar.justify-content-end').addClass('d-inline-block');
        } else {
            console.log($output);
            jQuery('.uwp-loop-posts-title').after('<div class="col-sm p-0">' + $output[0].outerHTML + '</div>');
            jQuery('.gd-lists-loop-author-actions').removeClass('me-1 mr-1');
        }
        jQuery('.uwp-profile-entries .geodir-list-loop-actions-container .gd-lists-loop-author-actions').remove();
        jQuery('.uwp-profile-entries .geodir-list-loop-actions-container').show();
    }
});
/**
 * Show the save to list dialog.
 *
 * This can be called by logged out users so we don't use a nonce here as it could cause caching issues and there is nothing actioned.
 *
 * @param $post_id
 * @param $this
 */
function gd_list_save_to_list_dialog($post_id, $this) {
    if (gd_list_manager_vars.aui) {
        gd_list_save_to_list_dialog_aui($post_id, $this);
        return;
    }
    if ($post_id) {
        var loading_instance;
        jQuery.ajax({
            url: geodir_params.ajax_url,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'geodir_lists_get_save_dialog',
                post_id: $post_id
            },
            beforeSend: function() {
                loading_instance = lity('loading');
            },
            success: function(data, textStatus, xhr) {
                if (data.success) {
                    loading_instance.close();
                    var instance = lity(data.data.html_content);
                }
            },
            error: function(xhr, textStatus, errorThrown) {
                console.log(textStatus);
            }
        });
    }
}

/**
 * Show the save to list dialog.
 *
 * This can be called by logged out users so we don't use a nonce here as it could cause caching issues and there is nothing actioned.
 *
 * @param $post_id
 * @param $this
 */
function gd_list_save_to_list_dialog_aui($post_id, $this) {
    if ($post_id) {
        /* Show loading screen */
        gd_list_manager_vars.loader = aui_modal();

        jQuery.ajax({
            url: geodir_params.ajax_url,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'geodir_lists_get_save_dialog',
                post_id: $post_id
            },
            beforeSend: function() {},
            success: function(data, textStatus, xhr) {
                if (data.success) {
                    gd_list_manager_vars.addPopup = aui_modal('', data.data.html_content, '', '', '', '');
                }
            },
            error: function(xhr, textStatus, errorThrown) {
                console.log(textStatus);
            }
        });
    }
}

/**
 * Save to a list for the current user.
 * 
 * @param $list_id
 * @param $post_id
 */
function gd_list_save_to_list($list_id, $post_id, action, el, action2) {
    if (gd_list_manager_vars.aui) {
        gd_list_save_to_list_aui($list_id, $post_id, action, el, action2);
        return;
    }
    if ($list_id && $post_id) {
        var $button = jQuery('[data-lists-save-id="' + $post_id + '"]');
        var $list_action = '';
        if (action == 'add') {
            $list_action = 'add';
        } else if (action == 'remove') {
            $list_action = 'remove';
        } else {
            $list_action = jQuery(action).hasClass('gd-list-action-remove') ? 'remove' : 'add';
        }
        // alert($list_action );return;
        jQuery.ajax({
            url: geodir_params.ajax_url,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'geodir_lists_save_to_list',
                list_id: $list_id,
                post_id: $post_id,
                list_action: $list_action,
                security: geodir_params.basic_nonce
            },
            beforeSend: function() {
                // loading_instance = lity('');
            },
            success: function(data, textStatus, xhr) {
                if (data.success) {
                    if (data.data.in_user_lists) {
                        var $button_html = '';
                        var $text = jQuery($button).data("lists-saved-text");
                        var $icon = jQuery($button).data("lists-saved-icon");
                        if ($icon) {
                            $button_html += "<i class='" + $icon + "' aria-hidden='true'></i> ";
                        }
                        if ($text) {
                            $button_html += '<span class="gd-secondary">' + $text + '</span>';
                        }
                        jQuery($button).addClass('gd-lists-is-in-user-lists').find('.gd-badge').html($button_html);
                    } else {
                        var $button_html = '';
                        var $text = jQuery($button).data("lists-save-text");
                        var $icon = jQuery($button).data("lists-save-icon");
                        if ($icon) {
                            $button_html += "<i class='" + $icon + "' aria-hidden='true'></i> ";
                        }
                        if ($text) {
                            $button_html += '<span class="gd-secondary">' + $text + '</span>';
                        }
                        jQuery($button).removeClass('gd-lists-is-in-user-lists').find('.gd-badge').html($button_html);
                    }
                    jQuery('.lity-close').trigger('click');
                }
            },
            error: function(xhr, textStatus, errorThrown) {
                console.log(textStatus);
            }
        });
    }
}

/**
 * Save to a list for the current user.
 * 
 * @param $list_id
 * @param $post_id
 */
function gd_list_save_to_list_aui($list_id, $post_id, action, el, action2) {
    if ($list_id && $post_id) {
        var $list_action = '';
        if (action == 'add') {
            $list_action = 'add';
        } else if (action == 'remove') {
            $list_action = 'remove';
        } else {
            $list_action = jQuery(el).hasClass('gd-list-action-remove') ? 'remove' : 'add';
        }

        jQuery.ajax({
            url: geodir_params.ajax_url,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'geodir_lists_save_to_list',
                list_id: $list_id,
                post_id: $post_id,
                list_action: $list_action,
                security: geodir_params.basic_nonce
            },
            beforeSend: function() {
                jQuery(el).addClass('disabled');
            },
            success: function(data, textStatus, xhr) {
                if (data.success) {
                    var $badge = jQuery('.gd-list-save-' + $post_id),
                        sSaveT = jQuery($badge).data("lists-save-text"),
                        sSaveI = jQuery($badge).data("lists-save-icon"),
                        sSavedT = jQuery($badge).data("lists-saved-text"),
                        sSavedI = jQuery($badge).data("lists-saved-icon");
                    if (data.data.in_user_lists) {
                        jQuery($badge).addClass('gd-lists-is-in-user-lists');
                        if (sSavedI && jQuery('.gd-list-icon', $badge).length) {
                            jQuery('.gd-list-icon', $badge).removeClass(sSaveI).addClass(sSavedI);
                        }
                        if (sSavedT && jQuery('span', $badge).length) {
                            jQuery('span', $badge).text(sSavedT);
                        }
                    } else {
                        jQuery($badge).removeClass('gd-lists-is-in-user-lists');
                        if (sSaveI && jQuery('.gd-list-icon', $badge).length) {
                            jQuery('.gd-list-icon', $badge).removeClass(sSavedI).addClass(sSaveI);
                        }
                        if (sSaveT && jQuery('span', $badge).length) {
                            jQuery('span', $badge).text(sSaveT);
                        }
                    }

                    if (action2 == 'show_list') {
                        gd_list_save_to_list_dialog_aui($post_id, el);
                    } else if (data.data.button) {
                        jQuery(el).replaceWith(data.data.button);
                    }
                }
            },
            error: function(xhr, textStatus, errorThrown) {
                console.log(textStatus);
            }
        });
    }
}

function gd_list_create_new_list_dialog($post_id) {
    if (gd_list_manager_vars.aui) {
        gd_list_create_new_list_dialog_aui($post_id);
        return;
    }
    if ($post_id) {
        var loading_instance;
        jQuery.ajax({
            url: geodir_params.ajax_url,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'geodir_lists_get_new_dialog',
                post_id: $post_id,
                security: geodir_params.basic_nonce
            },
            beforeSend: function() {
                jQuery('.lity-close').trigger('click');
                loading_instance = lity('loading');
            },
            success: function(data, textStatus, xhr) {
                loading_instance.close();
                if (data.success) {
                    var instance = lity(data.data.html_content);
                } else {
                    alert(geodir_params.i18n_ajax_error);
                }

            },
            error: function(xhr, textStatus, errorThrown) {
                loading_instance.close();
                alert(geodir_params.i18n_ajax_error);
                console.log(textStatus);
            }
        });
    }
}

function gd_list_create_new_list_dialog_aui($post_id) {
    if ($post_id) {
        jQuery.ajax({
            url: geodir_params.ajax_url,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'geodir_lists_get_new_dialog',
                post_id: $post_id,
                security: geodir_params.basic_nonce
            },
            beforeSend: function() {
                gd_list_manager_vars.addPopup = aui_modal();
            },
            success: function(data, textStatus, xhr) {
                if (data.success) {
                    gd_list_manager_vars.addPopup = aui_modal('', data.data.html_content, '', '', '', '');
                } else {
                    jQuery('.aui-modal.show [data-dismiss="modal"]').trigger('click');
                    alert(geodir_params.i18n_ajax_error);
                }

            },
            error: function(xhr, textStatus, errorThrown) {
                jQuery('.aui-modal.show [data-dismiss="modal"]').trigger('click');
                alert(geodir_params.i18n_ajax_error);
                console.log(textStatus);
            }
        });
    }
}

function gd_list_save_list($post_id, $this, $list_id) {
    if (gd_list_manager_vars.aui) {
        gd_list_save_list_aui($post_id, $this, $list_id);
        return;
    }
    if ($post_id || $list_id) {
        var $form = jQuery($this).closest("form");
        var $list_name = jQuery($form).find('input[name=list_name]').val();
        var $list_description = jQuery($form).find('textarea[name=list_description]').val();
        var $is_public = jQuery($form).find('input[name=is_public]:checked').val();
        if (!$list_name) {
            return false;
        }
        var loading_instance;
        jQuery.ajax({
            url: geodir_params.ajax_url,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'geodir_lists_save_list',
                post_id: $post_id,
                list_id: $list_id,
                list_name: $list_name,
                is_public: $is_public,
                list_description: $list_description,
                security: geodir_params.basic_nonce
            },
            beforeSend: function() {
                jQuery('.lity-close').trigger('click');
                loading_instance = lity('loading');
            },
            success: function(data, textStatus, xhr) {
                loading_instance.close();
                if (data.success) {
                    if ($post_id) {
                        gd_list_save_to_list(data.data.list_id, $post_id, 'add');
                    } else if ($list_id) {
                        // refresh
                        location.reload(true);
                    }
                } else {
                    alert(geodir_params.i18n_ajax_error);
                }

            },
            error: function(xhr, textStatus, errorThrown) {
                loading_instance.close();
                alert(geodir_params.i18n_ajax_error);
                console.log(textStatus);
            }
        });
    }
}

function gd_list_save_list_aui($post_id, $this, $list_id) {
    if ($post_id || $list_id) {
        var $form = jQuery($this).closest("form");
        var $list_name = jQuery($form).find('input[name=list_name]').val();
        var $list_description = jQuery($form).find('textarea[name=list_description]').val();
        var $is_public = jQuery($form).find('input[name=is_public]:checked').val();
        if (!$list_name) {
            return false;
        }
        jQuery.ajax({
            url: geodir_params.ajax_url,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'geodir_lists_save_list',
                post_id: $post_id,
                list_id: $list_id,
                list_name: $list_name,
                is_public: $is_public,
                list_description: $list_description,
                security: geodir_params.basic_nonce
            },
            beforeSend: function() {
                gd_list_manager_vars.addPopup = aui_modal();
            },
            success: function(data, textStatus, xhr) {
                jQuery('.aui-modal.show [data-dismiss="modal"]').trigger('click');
                if (gd_list_manager_vars.addPopup) {
                    gd_list_manager_vars.addPopup.remove();
                }
                if (data.success) {
                    if ($post_id) {
                        gd_list_save_to_list_aui(data.data.list_id, $post_id, 'add', jQuery('.gd-list-save-action-link'), 'show_list');
                    } else if ($list_id) {
                        location.reload(true);
                    }
                } else {
                    alert(geodir_params.i18n_ajax_error);
                }

            },
            error: function(xhr, textStatus, errorThrown) {
                jQuery('.aui-modal.show [data-dismiss="modal"]').trigger('click');
                alert(geodir_params.i18n_ajax_error);
                console.log(textStatus);
            }
        });
    }
}

function gd_list_delete_list($list_id) {
    if ($list_id) {
        var message = geodir_params.txt_are_you_sure;

        if (confirm(message)) {
            // alert('deleted');return;
            var loading_instance;
            jQuery.ajax({
                url: geodir_params.ajax_url,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'geodir_lists_delete_list',
                    list_id: $list_id,
                    security: geodir_params.basic_nonce
                },
                beforeSend: function() {
                    // jQuery('.lity-close').trigger('click');
                    // loading_instance = lity('loading');
                },
                success: function(data, textStatus, xhr) {
                    if (data.success) {
                        if (jQuery('.uwp-profile-entries .geodir-list-loop-actions-container').length) {
                            window.location.reload();
                        } else {
                            window.location.replace(data.data.redirect);
                        }
                    } else {
                        alert(geodir_params.i18n_ajax_error);
                    }

                },
                error: function(xhr, textStatus, errorThrown) {
                    loading_instance.close();
                    alert(geodir_params.i18n_ajax_error);
                    console.log(textStatus);
                }
            });
        }
    }
}

function gd_list_edit_list_dialog($list_id) {
    if (gd_list_manager_vars.aui) {
        gd_list_edit_list_dialog_aui($list_id);
        return;
    }
    if ($list_id) {
        // alert('deleted');return;
        var loading_instance;
        jQuery.ajax({
            url: geodir_params.ajax_url,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'geodir_lists_edit_list_dialog',
                list_id: $list_id,
                security: geodir_params.basic_nonce
            },
            beforeSend: function() {
                // jQuery('.lity-close').trigger('click');
                loading_instance = lity('loading');
            },
            success: function(data, textStatus, xhr) {
                if (data.success) {
                    loading_instance.close();
                    var instance = lity(data.data.html_content);
                } else {
                    alert(geodir_params.i18n_ajax_error);
                }

            },
            error: function(xhr, textStatus, errorThrown) {
                loading_instance.close();
                alert(geodir_params.i18n_ajax_error);
                console.log(textStatus);
            }
        });

    }
}

function gd_list_edit_list_dialog_aui($list_id) {
    if ($list_id) {
        jQuery('.aui-modal.show [data-dismiss="modal"]').trigger('click');
        gd_list_manager_vars.addPopup = aui_modal();
        jQuery.ajax({
            url: geodir_params.ajax_url,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'geodir_lists_edit_list_dialog',
                list_id: $list_id,
                security: geodir_params.basic_nonce
            },
            beforeSend: function() {},
            success: function(data, textStatus, xhr) {
                if (data.success) {
                    gd_list_manager_vars.addPopup = aui_modal('', data.data.html_content, '', '', '', '');
                } else {
                    alert(geodir_params.i18n_ajax_error);
                }

            },
            error: function(xhr, textStatus, errorThrown) {
                jQuery('.aui-modal.show [data-dismiss="modal"]').trigger('click');
                alert(geodir_params.i18n_ajax_error);
                console.log(textStatus);
            }
        });

    }
}