! function($) {
	"use strict";
	$.event.special.destroyed || ($.event.special.destroyed = {
		remove: function(o) {
			o.handler && o.handler()
		}
	}), $.fn.extend({
		advmaxlength: function(options, callback) {
			var documentBody = $("body");

			function utf8CharByteCount(character) {
				var c = character.charCodeAt();
				return c ? c < 128 ? 1 : c < 2048 ? 2 : 3 : 0
			}

			function utf8Length(string) {
				return string.split("").map(utf8CharByteCount).concat(0).reduce((function(sum, val) {
					return sum + val
				}))
			}

			function inputLength(input) {
				var text = input.val();
				text = options.twoCharLinebreak ? text.replace(/\r(?!\n)|\n(?!\r)/g, "\r\n") : text.replace(/(?:\r\n|\r|\n)/g, "\n");
				var currentLength = 0;
				return currentLength = options.utf8 ? utf8Length(text) : text.length, "file" === input.prop("type") && "" !== input.val() && (currentLength -= 12), currentLength
			}

			function remainingChars(input, maxlength) {
				return maxlength - inputLength(input)
			}

			function showRemaining(currentInput, indicator) {
				indicator.css({
					display: "block"
				}), currentInput.trigger("maxlength.shown")
			}

			function updateMaxLengthHTML(currentInputText, maxLengthThisInput, typedChars) {
				var output = "";
				return options.message ? output = "function" == typeof options.message ? options.message(currentInputText, maxLengthThisInput) : options.message.replace("%charsTyped%", typedChars).replace("%charsRemaining%", maxLengthThisInput - typedChars).replace("%charsTotal%", maxLengthThisInput) : (options.preText && (output += options.preText), options.showCharsTyped ? output += typedChars : output += maxLengthThisInput - typedChars, options.showMaxLength && (output += options.separator + maxLengthThisInput), options.postText && (output += options.postText)), output
			}

			function manageRemainingVisibility(remaining, currentInput, maxLengthCurrentInput, maxLengthIndicator) {
				var input, threshold, maxlength, output;
				maxLengthIndicator && (maxLengthIndicator.html(updateMaxLengthHTML(currentInput.val(), maxLengthCurrentInput, maxLengthCurrentInput - remaining)), remaining > 0 ? (input = currentInput, threshold = options.threshold, maxlength = maxLengthCurrentInput, output = !0, !options.alwaysShow && maxlength - inputLength(input) > threshold && (output = !1), output ? showRemaining(currentInput, maxLengthIndicator.removeClass(options.limitReachedClass + " " + options.limitExceededClass).addClass(options.warningClass)) : function(currentInput, indicator) {
					options.alwaysShow || (indicator.css({
						display: "none"
					}), currentInput.trigger("maxlength.hidden"))
				}(currentInput, maxLengthIndicator)) : options.limitExceededClass ? showRemaining(currentInput, 0 === remaining ? maxLengthIndicator.removeClass(options.warningClass + " " + options.limitExceededClass).addClass(options.limitReachedClass) : maxLengthIndicator.removeClass(options.warningClass + " " + options.limitReachedClass).addClass(options.limitExceededClass)) : showRemaining(currentInput, maxLengthIndicator.removeClass(options.warningClass).addClass(options.limitReachedClass))), options.customMaxAttribute && (remaining < 0 ? currentInput.addClass(options.customMaxClass) : currentInput.removeClass(options.customMaxClass))
			}

			function place(currentInput, maxLengthIndicator) {
				var pos = function(currentInput) {
					var el = currentInput[0];
					return $.extend({}, "function" == typeof el.getBoundingClientRect ? el.getBoundingClientRect() : {
						width: el.offsetWidth,
						height: el.offsetHeight
					}, currentInput.offset())
				}(currentInput);
				if ("function" !== $.type(options.placement))
					if ($.isPlainObject(options.placement)) ! function(placement, maxLengthIndicator) {
						if (placement && maxLengthIndicator) {
							var cssPos = {};
							$.each(["top", "bottom", "left", "right", "position"], (function(i, key) {
								var val = options.placement[key];
								void 0 !== val && (cssPos[key] = val)
							})), maxLengthIndicator.css(cssPos)
						}
					}(options.placement, maxLengthIndicator);
					else {
						var inputOuter = currentInput.outerWidth(),
							outerWidth = maxLengthIndicator.outerWidth(),
							actualWidth = maxLengthIndicator.width(),
							actualHeight = maxLengthIndicator.height();
						switch (options.appendToParent && (pos.top -= currentInput.parent().offset().top, pos.left -= currentInput.parent().offset().left), options.placement) {
							case "bottom":
								maxLengthIndicator.css({
									top: pos.top + pos.height,
									left: pos.left + pos.width / 2 - actualWidth / 2
								});
								break;
							case "top":
								maxLengthIndicator.css({
									top: pos.top - actualHeight,
									left: pos.left + pos.width / 2 - actualWidth / 2
								});
								break;
							case "left":
								maxLengthIndicator.css({
									top: pos.top + pos.height / 2 - actualHeight / 2,
									left: pos.left - actualWidth
								});
								break;
							case "right":
								maxLengthIndicator.css({
									top: pos.top + pos.height / 2 - actualHeight / 2,
									left: pos.left + pos.width
								});
								break;
							case "bottom-right":
								maxLengthIndicator.css({
									top: pos.top + pos.height,
									left: pos.left + pos.width
								});
								break;
							case "top-right":
								maxLengthIndicator.css({
									top: pos.top - actualHeight,
									left: pos.left + inputOuter
								});
								break;
							case "top-left":
								maxLengthIndicator.css({
									top: pos.top - actualHeight,
									left: pos.left - outerWidth
								});
								break;
							case "bottom-left":
								maxLengthIndicator.css({
									top: pos.top + currentInput.outerHeight(),
									left: pos.left - outerWidth
								});
								break;
							case "centered-right":
								maxLengthIndicator.css({
									top: pos.top + actualHeight / 2,
									left: pos.left + inputOuter - outerWidth - 3
								});
								break;
							case "bottom-right-inside":
								maxLengthIndicator.css({
									top: pos.top + pos.height,
									left: pos.left + pos.width - outerWidth
								});
								break;
							case "top-right-inside":
								maxLengthIndicator.css({
									top: pos.top - actualHeight,
									left: pos.left + inputOuter - outerWidth
								});
								break;
							case "top-left-inside":
								maxLengthIndicator.css({
									top: pos.top - actualHeight,
									left: pos.left
								});
								break;
							case "bottom-left-inside":
								maxLengthIndicator.css({
									top: pos.top + currentInput.outerHeight(),
									left: pos.left
								})
						}
					}
				else options.placement(currentInput, maxLengthIndicator, pos)
			}

			function getMaxLength(currentInput) {
				var max = currentInput.attr("maxlength") || options.customMaxAttribute;
				if (options.customMaxAttribute && !options.allowOverMax) {
					var custom = currentInput.attr(options.customMaxAttribute);
					(!max || custom < max) && (max = custom)
				}
				return max || (max = currentInput.attr("size")), max
			}
			return $.isFunction(options) && !callback && (callback = options, options = {}), options = $.extend({
				showOnReady: !1,
				alwaysShow: !0,
				threshold: 10,
				warningClass: "small form-text text-muted",
				limitReachedClass: "small form-text text-danger",
				limitExceededClass: "",
				separator: " / ",
				preText: "",
				postText: "",
				showMaxLength: !0,
				placement: "bottom-right-inside",
				message: null,
				showCharsTyped: !0,
				validate: !1,
				utf8: !1,
				appendToParent: !1,
				twoCharLinebreak: !0,
				customMaxAttribute: null,
				customMaxClass: "overmax",
				allowOverMax: !1,
				zIndex: 1099
			}, options), this.each((function() {
				var maxLengthCurrentInput, maxLengthIndicator, currentInput = $(this);

				function firstInit() {
					var maxlengthContent = updateMaxLengthHTML(currentInput.val(), maxLengthCurrentInput, "0");
					maxLengthCurrentInput = getMaxLength(currentInput), maxLengthIndicator || (maxLengthIndicator = $('<span class="bootstrap-maxlength"></span>').css({
						display: "none",
						position: "absolute",
						whiteSpace: "nowrap",
						zIndex: options.zIndex
					}).html(maxlengthContent)), currentInput.is("textarea") && (currentInput.data("maxlenghtsizex", currentInput.outerWidth()), currentInput.data("maxlenghtsizey", currentInput.outerHeight()), currentInput.mouseup((function() {
						currentInput.outerWidth() === currentInput.data("maxlenghtsizex") && currentInput.outerHeight() === currentInput.data("maxlenghtsizey") || place(currentInput, maxLengthIndicator), currentInput.data("maxlenghtsizex", currentInput.outerWidth()), currentInput.data("maxlenghtsizey", currentInput.outerHeight())
					}))), options.appendToParent ? (currentInput.parent().append(maxLengthIndicator), currentInput.parent().css("position", "relative")) : documentBody.append(maxLengthIndicator), manageRemainingVisibility(remainingChars(currentInput, getMaxLength(currentInput)), currentInput, maxLengthCurrentInput, maxLengthIndicator), place(currentInput, maxLengthIndicator)
				}
				$(window).resize((function() {
					maxLengthIndicator && place(currentInput, maxLengthIndicator)
				})), options.showOnReady ? currentInput.ready((function() {
					firstInit()
				})) : currentInput.focus((function() {
					firstInit()
				})), currentInput.on("maxlength.reposition", (function() {
					place(currentInput, maxLengthIndicator)
				})), currentInput.on("destroyed", (function() {
					maxLengthIndicator && maxLengthIndicator.remove()
				})), currentInput.on("blur", (function() {
					maxLengthIndicator && !options.showOnReady && maxLengthIndicator.remove()
				})), currentInput.on("input", (function() {
					var maxlength = getMaxLength(currentInput),
						remaining = remainingChars(currentInput, maxlength),
						output = !0;
					return options.validate && remaining < 0 ? (! function(input, maxlength) {
						var text = input.val();
						if (options.twoCharLinebreak && "\n" === (text = text.replace(/\r(?!\n)|\n(?!\r)/g, "\r\n"))[text.length - 1] && (maxlength -= text.length % 2), options.utf8) {
							for (var indexedSize = text.split("").map(utf8CharByteCount), removedBytes = 0, bytesPastMax = utf8Length(text) - maxlength; removedBytes < bytesPastMax; removedBytes += indexedSize.pop());
							maxlength -= maxlength - indexedSize.length
						}
						input.val(text.substr(0, maxlength))
					}(currentInput, maxlength), output = !1) : manageRemainingVisibility(remaining, currentInput, maxLengthCurrentInput, maxLengthIndicator), output
				}))
			}))
		}
	})
}(jQuery);

jQuery(function($) {
	// Ajax search listings.
	$('#advertising-ad-listing:not(.adv-all-listings-shown)').each(function() {
		var $el = $(this);
		var select2Args = {
			allowClear: ($(this).data('allow-clear') || $(this).data('allow_clear')) ? true : false,
			placeholder: $(this).data('placeholder'),
			minimumInputLength: $(this).data('min-input-length') ? $(this).data('min-input-length') : 3,
			ajax: {
				url: adv_params.ajax_url,
				type: 'POST',
				dataType: 'json',
				delay: 250,
				data: function(params) {
					var data = {
						action: 'advertising_listings',
						search: params.term,
						adv_zone: (($el.closest('form').length && $el.closest('form').find('[name="zone"]').length) ? parseInt($el.closest('form').find('[name="zone"]').val()) : 0),
						_ajax_nonce: adv_params.ajax_nonce,
					}

					// Query parameters will be ?search=[term]&type=public
					return data;
				},
				processResults: function(res) {
					if (res.success) {
						return {
							results: res.data
						};
					}

					return {
						results: []
					};
				}
			},
			templateResult: function(item) {
				if (item.loading) {
					return adv_params.searching;
				}

				if (!item.id) {
					return item.text;
				}

				return $('<span>' + item.text + '</span>')
			},
			language: {
				inputTooShort: function() {
					return adv_params.search_listings;
				}
			}
		};

		if (typeof aui_select2_locale == 'function') {
			select2Args = $.extend(select2Args, aui_select2_locale());
		}
		var $select2 = $(this).select2(select2Args);
		$select2.addClass('enhanced adv-all-listings-shown').removeClass('aui-select2');
	});

	$('#advertising-ad-content').advmaxlength({
		threshold: 20
	});

	$('.adv-content-ads .adv-delete-ad').on('click', function(e) {
		var $this = $(this);
		var $row = $this.closest('tr');

		var id = parseInt($row.data('id'));
		if (!id > 0) {
			return false;
		}

		if (!confirm(adv_params.ConfirmDeleteAd)) {
			return false;
		}

		var data = {
			adv_action: 'delete_advertiser_ad',
			_nonce: adv_params.nonce,
			_id: id
		};

		jQuery.ajax({
			url: adv_params.ajax_url,
			data: data,
			type: 'POST',
			cache: false,
			beforeSend: function(xhr) {
				$row.addClass('adv-deleting');
			},
			success: function(res, status, xhr) {
				if (res && typeof res == 'object') {
					if (res.success) {
						$row.fadeOut().remove();
					} else if (res.error) {
						alert(res.error);
					}
				}
			}
		}).fail(function(xhr, status, error) {
			if (window.console && window.console.log) {
				console.log(error);
			}
		}).complete(function(xhr, status) {
			$row.removeClass('adv-deleting');
		});
	});

	$('.adv-content-ads .adv-repay-ad').on('click', function(e) {
		var $this = $(this);
		var $row = $this.closest('tr');

		var id = parseInt($row.data('id'));
		if (!id > 0) {
			return false;
		}

		var data = {
			adv_action: 'renew_advertiser_ad',
			_nonce: adv_params.nonce,
			_id: id
		};

		jQuery.ajax({
			url: adv_params.ajax_url,
			data: data,
			type: 'POST',
			cache: false,
			beforeSend: function(xhr) {
				$row.addClass('adv-renewing');
			},
			success: function(res, status, xhr) {
				if (res && typeof res == 'object') {
					if (res.success) {
						window.location.href = res.payment_url
					} else if (res.error) {
						alert(res.error);
					}
				}
			}
		}).fail(function(xhr, status, error) {
			alert(error)
		}).complete(function(xhr, status) {
			$row.removeClass('adv-renewing');
		});
	});

	$('.adv-ad-form [name="zone"]').on('change.type', function(e) {
		e.preventDefault();

		var form = $(this).closest('form');
		var zone = $(this).val();
		var allowed_ads = form.find('.adv-zone-' + zone + '-allowed-ad-types');

		if (allowed_ads.length) {
			var curZone = form.find('#advertising-ad-type').val();
			var onlyInclude = allowed_ads.data('types');
			var hasValue = false;

			form.find('#advertising-ad-type option').each(function() {
				if ($(this).attr('value') && -1 == onlyInclude.indexOf($(this).attr('value'))) {
					$(this).hide();
				} else {
					$(this).show();

					if (curZone && curZone == $(this).attr('value')) {
						hasValue = true;
					}
				}
			});

			if (!hasValue) {
				form.find('#advertising-ad-type').val('').trigger('change');
			}

			// Reset the selected option if it's hidden.
			if (!form.find('#advertising-ad-type option:selected').is(':visible')) {
				form.find('#advertising-ad-type option:visible:first').prop('selected', true);
			}

			return;
		}

		form.find('#advertising-ad-type').val('').trigger('change');

		form.find('#advertising-ad-type option').each(function() {
			$(this).show();
		})
	});
	$('.adv-ad-form [name="zone"]').trigger('change.type');

	$('.adv-ad-form .adv-zones-list').on('change', function(e) {
		e.preventDefault();

		var form = $(this).closest('form');
		$(form).find('.adv-zone-description').addClass('d-none');
		$(form).find('.zone-' + $(this).val() + '-description').removeClass('d-none');

		if (jQuery("body").hasClass("aui_bs5")) {
			$(form).find('.adv-ad-qty').closest('.mb-3').addClass('d-none');
			$(form).find('#adv-zone_' + $(this).val() + '_qty').closest('.mb-3').removeClass('d-none');
		} else {
			$(form).find('.adv-ad-qty').closest('.form-group').addClass('d-none');
			$(form).find('#adv-zone_' + $(this).val() + '_qty').closest('.form-group').removeClass('d-none');
		}
	});
	$('.adv-ad-form .adv-zones-list').trigger('change');

	$('.adv-ad-form .adv-ad-type').on('change', function(e) {
		e.preventDefault();

		var val = $(this).val();
		var form = $(this).closest('form');
		$(form).find('.adv-none').hide();
		$(form).find('.adv-show-' + val).show();

	});
	$('.adv-ad-form .adv-ad-type').trigger('change');

	$('#advertising-ad-image+.input-group-append, #advertising-ad-image+.input-group-text').on('click', function(e) {
		e.preventDefault();
		$('#adv_upload_image').trigger('click')
	});

	$('#advertising-ad-image').on('change', function(e) {
		$('#advertising-ad-image').removeClass('is-invalid')
		$('[data-argument="advertising-ad-image"] .invalid-feedback').remove()
	});

	function adv_image_cropper() {
		var image_url = $('#advertising-ad-image').val()

		if (!image_url || 'image' !== $('#advertising-ad-type').val()) {
			return;
		}

		var zone_id = $('#advertising-zone-id').val()
		var zone_sizes = JSON.parse(jQuery('#adv_zone_sizes').val())

		if (!zone_sizes || !zone_sizes[zone_id] || !zone_sizes[zone_id]['h'] || !zone_sizes[zone_id]['w']) {
			return
		}

		var zone_size = zone_sizes[zone_id];
		var winW = parseInt($(window).width()),boxWidth;
		if (winW <= 576) {
			boxWidth = 576;
		} else if (winW <= 768) {
			boxWidth = 650;
		} else if (winW <= 992) {
			boxWidth = 768;
		} else {
			boxWidth = 992;
		}
		$('#adv-image-crop-template > .modal-dialog').css({'max-width':'fit-content'});
		var jcrop_api;
		var modal = new bootstrap.Modal(document.getElementById('adv-image-crop-template'))
		modal.show()

		$('#adv-image-crop-template').on('hidden.bs.modal', function(event) {
			jcrop_api.destroy()
		})

		$('#adv-image-crop-template .modal-footer').css("visibility", "hidden");

		$('#adv-image-to-crop')
			.attr('src', image_url)
			.Jcrop({
					allowSelect: true,
					allowMove: true,
					allowResize: true,
					fixedSupport: true,
					aspectRatio: zone_size['w'] / zone_size['h'],
					setSelect: [0, 0, zone_size['w'], zone_size['h']],
					boxWidth: boxWidth,
					onSelect: function(c) {
						$('#adv_image_x').val(c.x)
						$('#adv_image_y').val(c.y)
						$('#adv_image_w').val(c.w)
						$('#adv_image_h').val(c.h)

						$('#adv-image-crop-template .modal-footer').css("visibility", "visible")

					}
				},
				function() {
					jcrop_api = this;
					var widget_size = jcrop_api.getWidgetSize()
					$('#adv_widget_w').val(widget_size[0])
					$('#adv_widget_h').val(widget_size[1])
				}
			)

		modal.handleUpdate()
	}

	$('body').on('change', '#advertising-zone-id', adv_image_cropper)
	$('body').on('change', '#advertising-ad-image', adv_image_cropper)
	$('body').on('click', '#advertising-handle-crop', function() {
		wpinvBlock('form.adv-ad-form')

		$.ajax({
				url: adv_params.ajax_url,
				type: 'POST',
				data: {
					action: 'advertising_image_crop',
					image_x: $('#adv_image_x').val(),
					image_y: $('#adv_image_y').val(),
					image_w: $('#adv_image_w').val(),
					image_h: $('#adv_image_h').val(),
					widget_w: $('#adv_widget_w').val(),
					widget_h: $('#adv_widget_h').val(),
					image_url: $('#advertising-ad-image').val(),
					nonce: adv_params.nonce,
				},
				success: function(response) {
					if (response.success) {
						$('#advertising-ad-image').val(response.data)
					}

					if (false === response.success) {
						alert(response.data)
					}
				},
			})
			.always(function() {
				wpinvUnblock('form.adv-ad-form')
			})

	})

	$('body').on('change', '#adv_upload_image', function() {
		var file_data = $(this).prop('files')[0];

		if (!file_data) {
			return
		}

		// Check file data to prevent submitting unsupported file types.
		var ext = file_data.name.match(/\.([^\.]+)$/)[1];
		switch (ext.toString().toLowerCase()) {
			case 'jpg':
			case 'jpeg':
			case 'png':
			case 'gif':
				break;
			default:
				console.log(ext)
				$('#advertising-ad-image').addClass('is-invalid')
				$('[data-argument="advertising-ad-image"] .input-group-append')
					.after('<div class="invalid-feedback">' + adv_params.invalid_file + '</div>')
				$('#adv_upload_image').val('')
				return;
		}

		form_data = new FormData();
		form_data.append('file', file_data);
		form_data.append('action', 'advertising_image_upload');
		form_data.append('nonce', adv_params.nonce);

		$('[data-argument="advertising-ad-image"] .input-group-append .spinner-border').removeClass('d-none')
		$('[data-argument="advertising-ad-image"] .input-group-append .adv-svg').addClass('d-none')
		$('#advertising-ad-image').removeClass('is-invalid')
		$('[data-argument="advertising-ad-image"] .invalid-feedback').remove()

		$.ajax({
				url: adv_params.ajax_url,
				type: 'POST',
				contentType: false,
				processData: false,
				data: form_data,
				success: function(response) {
					if (response.success) {
						if (response.data.image_size) {
							$('#advertising-ad-image').attr('data-width',response.data.image_size[0]);
							$('#advertising-ad-image').attr('data-height',response.data.image_size[1]);
							/*$('#adv-image-to-crop')
							    .attr( 'width', response.data.image_size[0] )
							    .attr( 'height', response.data.image_size[1] )*/
						} else {
							$('#advertising-ad-image').attr('data-width',0);
							$('#advertising-ad-image').attr('data-height',0);
						}
						$('#advertising-ad-image').val(response.data.image_url).trigger('change')
					} else {
						$('#advertising-ad-image').addClass('is-invalid')
						$('[data-argument="advertising-ad-image"] .input-group-append')
							.after('<div class="invalid-feedback">' + response.data + '</div>')
					}

				},
				error: function(request, status, message) {
					$('#advertising-ad-image').addClass('is-invalid')
					$('[data-argument="advertising-ad-image"] .input-group-append')
						.after('<div class="invalid-feedback">' + message + '</div>')
				}
			})
			.always(function() {
				$('[data-argument="advertising-ad-image"] .input-group-append .spinner-border').addClass('d-none')
				$('[data-argument="advertising-ad-image"] .input-group-append .adv-svg').removeClass('d-none')
			})
	});

});

function advRemoveQueryVar(url, parameter) {
	//prefer to use l.search if you have a location/link object
	var urlparts = url.split('?');
	var urlparts2 = url.split('&');
	if (urlparts.length >= 2) {
		var prefix = encodeURIComponent(parameter) + '=';
		var pars = urlparts[1].split(/[&;]/g);
		//reverse iteration as may be destructive
		for (var i = pars.length; i-- > 0;) {
			//idiom for string.startsWith
			if (pars[i].lastIndexOf(prefix, 0) !== -1) {
				pars.splice(i, 1);
			}
		}
		url = urlparts[0] + (pars.length > 0 ? '?' + pars.join('&') : "");
		return url;
	} else if (urlparts2.length >= 2) {
		var prefix = encodeURIComponent(parameter) + '=';
		var pars = url.split(/[&;]/g);
		//reverse iteration as may be destructive
		for (var i = pars.length; i-- > 0;) {
			//idiom for string.startsWith
			if (pars[i].lastIndexOf(prefix, 0) !== -1) {
				pars.splice(i, 1);
			}
		}
		url = pars.join('&');
		return url;
	} else {
		return url;
	}
}