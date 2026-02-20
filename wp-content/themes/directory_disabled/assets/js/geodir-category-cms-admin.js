(function ($) {
	'use strict';

	function pickMedia(onSelect) {
		var frame = wp.media({
			title: 'Select image',
			button: { text: 'Use this image' },
			multiple: false
		});

		frame.on('select', function () {
			var att = frame.state().get('selection').first().toJSON();
			if (att && onSelect) {
				onSelect(att);
			}
		});

		frame.open();
	}

	// Image picker (Image 1 / Image 2).
	$(document)
		.on('click', '.dir-cat-img-pick', function (e) {
			e.preventDefault();
			var $wrap = $(this).closest('.dir-cat-img-field');
			if (!$wrap.length) {
				return;
			}

			pickMedia(function (att) {
				$wrap.find('input.dir-cat-img-id').val(att.id);
				$wrap.find('img.dir-cat-img-preview')
					.attr('src', att.url)
					.show();
				$wrap.find('.dir-cat-img-remove').show();
			});
		})
		.on('click', '.dir-cat-img-remove', function (e) {
			e.preventDefault();
			var $wrap = $(this).closest('.dir-cat-img-field');
			if (!$wrap.length) {
				return;
			}
			$wrap.find('input.dir-cat-img-id').val('');
			$wrap.find('img.dir-cat-img-preview')
				.attr('src', '')
				.hide();
			$(this).hide();
		});

	// FAQ repeater.
	$(document)
		.on('click', '.dir-faq-add', function (e) {
			e.preventDefault();
			var $container = $(this).closest('.form-field, .term-directory-cat-faq-wrap, .dir-faq-root');
			if (!$container.length) {
				$container = $(document);
			}
			var $list = $container.find('.dir-faq-list').first();
			if (!$list.length) {
				return;
			}

			var idx = $list.children('.dir-faq-item').length;
			var html =
				'<div class="dir-faq-item" style="border:1px solid #ddd;padding:10px;margin:10px 0;border-radius:6px;">' +
				'<p style="margin:0 0 6px;"><strong>Question</strong></p>' +
				'<input type="text" class="widefat" name="directory_cat_faq[' + idx + '][q]" value="" placeholder="Question" />' +
				'<p style="margin:10px 0 6px;"><strong>Answer</strong></p>' +
				'<textarea class="widefat" rows="3" name="directory_cat_faq[' + idx + '][a]" placeholder="Answer"></textarea>' +
				'<p style="margin:10px 0 0;"><a href="#" class="button dir-faq-remove">Remove</a></p>' +
				'</div>';

			$list.append(html);
		})
		.on('click', '.dir-faq-remove', function (e) {
			e.preventDefault();
			$(this).closest('.dir-faq-item').remove();
		});
})(jQuery);

