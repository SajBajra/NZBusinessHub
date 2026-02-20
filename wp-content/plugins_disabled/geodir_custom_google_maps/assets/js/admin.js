(function($) {
    $(document).ready(function() {
        // $("#custom_google_maps_accordion").accordion();
        $('.gd-map-color-picker').wpColorPicker();

        // $('body').on('click', 'a.custom-google-map-btn', function() {
        //     var parentid = $(this).parent().parent().attr('id');
        //     $('#' + parentid + ' a.custom-google-map-btn').removeClass('button-primary');
        //     $(this).addClass('button-primary');
        //     var get_current_val = $(this).attr('data-val');
        //
        //     if ('open_street_map' == get_current_val) {
        //         //$('#'+parentid+' .open-street-section').show();
        //         //$('#'+parentid+' .google-map-section').hide();
        //     } else {
        //         $('#' + parentid + ' .google-map-section').show();
        //         $('#' + parentid + ' .open-street-section').hide();
        //     }
        // });

        $('body').on('click', '.map-style-btn', function() {
            var get_current_value = $(this).val();
            var parentid = $(this).parent().parent().attr('id');

            if ('Import Styles' == get_current_value) {
                $('#' + parentid + ' .custom-style-content').hide();
                $('#' + parentid + ' .custom-import-style-content').show();
            } else {
                $('#' + parentid + ' .custom-style-content').show();
                $('#' + parentid + ' .custom-import-style-content').hide();
            }
        });

        $('body').on('click', 'input.add-new-custom-styles', function() {
            var get_data_id = $(this).attr('data-value'),
                get_map_style_id = $('#map_style_id_' + get_data_id).val(),
                get_map_feature_type = $('#map_feature_type_' + get_data_id).val(),
                get_map_element_type = $('#map_element_type_' + get_data_id).val(),
                get_map_color = $('#map_color_' + get_data_id).val(),
                get_map_gamma = $('#map_gamma_' + get_data_id).val(),
                get_map_hue = $('#map_hue_' + get_data_id).val(),
                get_map_invert_lightness = $('#map_invert_lightness_' + get_data_id).val(),
                get_map_lightness = $('#map_lightness_' + get_data_id).val(),
                get_map_saturation = $('#map_saturation_' + get_data_id).val(),
                get_map_visibility = $('#map_visibility_' + get_data_id).val(),
                get_map_weight = $('#map_weight_' + get_data_id).val();

            var data = {
                'action': 'gd_add_new_custom_styles',
                'data_id': get_data_id,
                'map_style_id': get_map_style_id,
                'map_feature_type': get_map_feature_type,
                'map_element_type': get_map_element_type,
                'map_color': get_map_color,
                'map_gamma': get_map_gamma,
                'map_hue': get_map_hue,
                'map_invert_lightness': get_map_invert_lightness,
                'map_lightness': get_map_lightness,
                'map_saturation': get_map_saturation,
                'map_visibility': get_map_visibility,
                'map_weight': get_map_weight,
                'security': geodir_params.basic_nonce
            };

            $.post(ajaxurl, data, function(response) {
                response = $.parseJSON(response);
                var id = response.id;
                $('#custom_styles_content_' + id + ' .custom-map-style-data').html('');
                $('#custom_styles_content_' + id + ' .custom-map-style-data').html(response.html);
                $('input[name=cusom_preview_style_btn_' + id + ']').trigger("click");
            });
        });
        $('body').on('click', '.custom-map-listing-blog a.remove-btn', function() {
            var get_remove_id = $(this).attr('data-id'),
                get_parent_id = $(this).parent().attr('id'),
                get_option_key = $('#' + get_parent_id + ' #listing_option_' + get_remove_id).val(),
                get_fields = $('#' + get_parent_id + ' #listing_fields_id_' + get_remove_id).val();

            var data = {
                'action': 'gd_remove_custom_styles',
                'get_remove_id': get_remove_id,
                'get_option_key': get_option_key,
                'get_fields': get_fields,
                'security': geodir_params.basic_nonce
            };

            $.post(ajaxurl, data, function(response) {
                response = $.parseJSON(response);
                var id = response.id;
                $('#custom_styles_content_' + id + ' .custom-map-style-data').html('');
                $('#custom_styles_content_' + id + ' .custom-map-style-data').html(response.html);
                $('input[name=cusom_preview_style_btn_' + id + ']').trigger("click");
            });
        });

        $('body').on('click', 'input.import-custom-styles', function() {
            var data_id = $(this).attr('data-value');
            var style_id = $('#map_import_style_id_' + data_id).val();
            var import_value = $('#import_styles_content_' + data_id).val();
            var data = {
                'action': 'gd_import_custom_style',
                'data_id': data_id,
                'style_id': style_id,
                'import_value': import_value
            };

            $.post(ajaxurl, data, function(response) {
                response = $.parseJSON(response);
                var id = response.id;
                $('#custom_styles_content_' + id + ' .custom-map-style-data').html('');
                $('#custom_styles_content_' + id + ' .custom-map-style-data').html(response.html);
                $('#import_styles_content_' + id).val('');
                $('#gd-map-style-custom-tab_' + id ).trigger("click");
                $('input[name=cusom_preview_style_btn_' + id + ']').trigger("click");
            });
        });

        $('body').on('click', '.map-preview-btn', function() {
            var data_id = $(this).attr('data-id'),
                get_parent_id = $(this).parent().attr('id'),
                get_field_id = $('#' + get_parent_id + ' #map_preview_id_' + data_id).val(),
                get_option_id = $('#' + get_parent_id + ' #map_preview_option_' + data_id).val();
            var data = {
                'action': 'preview_map_styles',
                'get_field_id': get_field_id,
                'get_option_id': get_option_id,
            };

            $([document.documentElement, document.body]).animate({
                scrollTop: $("#google_map_preview_"+ get_field_id).offset().top
            }, 200);

            $.post(ajaxurl, data, function(response) {
                response = $.parseJSON(response);
                map_id = response.id;
                var get_id = 'google_map_preview_' + map_id;

                $('#' + get_id).html('');
                var map_options = {
                    zoom: 8,
                    center: new google.maps.LatLng(response.latitude, response.longitude),
                    mapTypeId: google.maps.MapTypeId.ROADMAP,
                };
                var map = new google.maps.Map(document.getElementById(get_id), map_options);
                var mapStyles = JSON.parse(response.styles);
                if (typeof mapStyles == 'object' && mapStyles) {
                    map.setOptions({
                        'styles': mapStyles
                    });
                }
            });
        });

        $('body').on('click', 'input.gd-save-osm-btn', function() {
            var get_id = $(this).attr('data-id'),
                get_field_key = $('#gd_osm_fields_key_' + get_id).val(),
                get_base_value = $('#gd_osm_base_value_' + get_id).val(),
                get_overlay_value_arr = [];
            $('#save_gd_osm_val_' + get_id).attr('disabled', true);
            $('#save_gd_osm_val_' + get_id).addClass('osm_disable_btn');
            $("#gd_custom_osm_fields_" + get_id + " input.gd-osm-overlays").each(function() {
                get_overlay_value_arr.push($(this).val());
            });

            var data = {
                'action': 'gd_save_osm_layers',
                'get_id': get_id,
                'get_field_key': get_field_key,
                'get_base_value': get_base_value,
                'get_overlay_value': get_overlay_value_arr,
            };

            $.post(ajaxurl, data, function(response) {
                response = $.parseJSON(response);
                $('#save_gd_osm_val_' + response.id).attr('disabled', false);
                $('#save_gd_osm_val_' + response.id).removeClass('osm_disable_btn');
                $('input[name=gd_osm_preview_btn_' + response.id + ']').trigger("click");
                aui_toast('gd_tabs_save_tab_success','success',geodir_params.txt_saved);
            });
        });

        $('body').on('click', 'input.gd-osm-preview', function() {
            var get_data_id = parseInt($(this).attr('data-id')),
                get_field_id = $('#gd_osm_fields_key_' + get_data_id).val();
            var queryParameters = {},
                queryString = location.search.substring(1),
                re = /([^&=]+)=([^&]*)/g,
                m;
            while (m = re.exec(queryString)) {
                queryParameters[decodeURIComponent(m[1])] = decodeURIComponent(m[2]);
            }
            queryParameters['map_field'] = get_data_id;
            location.search = $.param(queryParameters); // Causes page to reload
        });

        function getQueryStringValue(key) {
            return decodeURIComponent(window.location.search.replace(new RegExp("^(?:.*[&\\?]" + encodeURIComponent(key).replace(/[\.\+\*]/g, "\\$&") + "(?:\\=([^&]*))?)?.*$", "i"), "$1"));
        }

        var get_map_field = getQueryStringValue("map_field");

        if (get_map_field && get_map_field > 0) {
            $('#custom_google_maps_accordion').accordion({
                active: parseInt(get_map_field) - 1
            });
            $('#google_menu_buttons_' + parseInt(get_map_field) + ' a.custom-google-map-btn').removeClass('button-primary');
            $('#google_menu_buttons_' + parseInt(get_map_field) + ' a.custom-osm-button').addClass('button-primary');
            $('#google_open_street_section_' + parseInt(get_map_field) + '.open-street-section').show();
            $('#google_map_section_' + parseInt(get_map_field) + '.google-map-section').hide();
        }
    });
})(jQuery);