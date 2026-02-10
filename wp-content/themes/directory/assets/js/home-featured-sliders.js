(function () {
	'use strict';

	function initSlider(slider) {
		var viewport = slider.querySelector('.fp__hf-viewport');
		var track = slider.querySelector('.fp__hf-track');
		var cards = Array.prototype.slice.call(slider.querySelectorAll('.fp__hf-card'));
		var prev = slider.querySelector('.fp__hf-prev');
		var next = slider.querySelector('.fp__hf-next');
		var dotsContainer = document.querySelector('.fp__hf-dots[data-hf-dots=\"' + slider.getAttribute('data-hf-slider') + '\"]');

		if (!viewport || !track || !cards.length) {
			return;
		}

		var current = 0;
		var slideWidth = 0;
		var gap = 16; // desktop track gap

		function goTo(index) {
			if (!cards.length) return;
			current = (index + cards.length) % cards.length;
			viewport.scrollTo({ left: current * slideWidth, behavior: 'smooth' });
			updateActive();
		}

		function updateActive() {
			cards.forEach(function (card, idx) {
				card.classList.toggle('is-active', idx === current);
			});
			if (dotsContainer) {
				var dots = dotsContainer.querySelectorAll('button');
				dots.forEach(function (dot, idx) {
					dot.classList.toggle('is-active', idx === current);
				});
			}
		}

		if (prev) {
			prev.addEventListener('click', function () {
				goTo(current - 1);
			});
		}
		if (next) {
			next.addEventListener('click', function () {
				goTo(current + 1);
			});
		}

		if (dotsContainer) {
			dotsContainer.innerHTML = '';
			cards.forEach(function (_, idx) {
				var b = document.createElement('button');
				b.type = 'button';
				b.className = 'fp__hf-dot' + (idx === 0 ? ' is-active' : '');
				b.setAttribute('aria-label', 'Go to slide ' + (idx + 1));
				b.addEventListener('click', function () { goTo(idx); });
				dotsContainer.appendChild(b);
			});
		}

		// Ensure each card is narrower than viewport to "peek" next slide on desktop,
		// and full-width single-card view on mobile.
		function syncWidths() {
			var viewportWidth = viewport.clientWidth;
			var cardWidth;
			var localGap;

			if (window.innerWidth <= 900) {
				cardWidth = viewportWidth;
				localGap = 0;
			} else {
				cardWidth = Math.round(viewportWidth * 0.6); // desktop: show peek of next card
				localGap = gap;
			}

			slideWidth = cardWidth + localGap;
			cards.forEach(function (card) {
				card.style.width = cardWidth + 'px';
				card.style.flex = '0 0 ' + cardWidth + 'px';
			});
			// Re-align to current slide on resize.
			viewport.scrollTo({ left: current * slideWidth });
		}
		syncWidths();
		window.addEventListener('resize', syncWidths);

		updateActive();
	}

	document.addEventListener('DOMContentLoaded', function () {
		var sliders = document.querySelectorAll('.fp__hf-slider');
		Array.prototype.forEach.call(sliders, initSlider);
	});
})();

