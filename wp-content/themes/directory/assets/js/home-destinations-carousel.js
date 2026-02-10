(function () {
	'use strict';

	function initDestinationsCarousel(slider) {
		var grid = slider.querySelector('[data-destinations-grid]');
		var cards = grid ? Array.prototype.slice.call(grid.children) : [];
		var pager = slider.querySelector('[data-destinations-pages]');

		if (!grid || !cards.length || !pager) {
			return;
		}

		var currentPage = 0;
		var perPage = 4;
		var totalPages = 1;

		function computePerPage() {
			var width = slider.clientWidth || window.innerWidth;
			if (width >= 1200) return 4;
			if (width >= 900) return 3;
			if (width >= 640) return 2;
			return 1;
		}

		function applyLayout() {
			perPage = computePerPage();
			totalPages = Math.max(1, Math.ceil(cards.length / perPage));
			if (currentPage >= totalPages) {
				currentPage = totalPages - 1;
			}
			var start = currentPage * perPage;
			var end = start + perPage;
			cards.forEach(function (card, index) {
				if (index >= start && index < end) {
					card.style.display = '';
					card.removeAttribute('aria-hidden');
				} else {
					card.style.display = 'none';
					card.setAttribute('aria-hidden', 'true');
				}
			});
			renderPager();
		}

		function goTo(pageIndex) {
			if (pageIndex < 0 || pageIndex >= totalPages) return;
			currentPage = pageIndex;
			applyLayout();
		}

		function renderPager() {
			pager.innerHTML = '';
			if (totalPages <= 1) {
				return;
			}

			var prevBtn = document.createElement('button');
			prevBtn.type = 'button';
			prevBtn.className = 'fp__dest-page fp__dest-page--arrow';
			prevBtn.textContent = '‹';
			prevBtn.disabled = currentPage === 0;
			prevBtn.addEventListener('click', function () {
				goTo(currentPage - 1);
			});
			pager.appendChild(prevBtn);

			for (var i = 0; i < totalPages; i++) {
				var btn = document.createElement('button');
				btn.type = 'button';
				btn.className = 'fp__dest-page' + (i === currentPage ? ' is-active' : '');
				btn.textContent = (i + 1).toString();
				(function (page) {
					btn.addEventListener('click', function () {
						goTo(page);
					});
				})(i);
				pager.appendChild(btn);
			}

			var nextBtn = document.createElement('button');
			nextBtn.type = 'button';
			nextBtn.className = 'fp__dest-page fp__dest-page--arrow';
			nextBtn.textContent = '›';
			nextBtn.disabled = currentPage === totalPages - 1;
			nextBtn.addEventListener('click', function () {
				goTo(currentPage + 1);
			});
			pager.appendChild(nextBtn);
		}

		window.addEventListener('resize', function () {
			applyLayout();
		});

		applyLayout();
	}

	document.addEventListener('DOMContentLoaded', function () {
		var sliders = document.querySelectorAll('[data-destinations-slider]');
		Array.prototype.forEach.call(sliders, initDestinationsCarousel);
	});
})();

