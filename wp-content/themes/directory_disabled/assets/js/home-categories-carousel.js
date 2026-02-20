/**
 * Simple carousel for the home "Business categories" section.
 *
 * - Shows 6 category cards at a time
 * - Auto-rotates through all categories
 * - Adds dots and prev/next arrows
 * - Does not change the design of individual cards – it only hides/shows them.
 */
(function () {
	document.addEventListener('DOMContentLoaded', function () {
		// Skip the Business Categories page – we want a simple list there.
		if (document.body.classList.contains('page-business-categories')) {
			return;
		}

		var containers = document.querySelectorAll('.wp-block-geodirectory-geodir-widget-categories');
		if (!containers.length) {
			return;
		}

		containers.forEach(function (block) {
			var row = block.querySelector('.gd-cptcat-row');
			if (!row) {
				return;
			}

			var items = Array.prototype.slice.call(row.querySelectorAll('.gd-cptcat-parent'));
			var visibleCount = 6;
			var total = items.length;

			if (total <= visibleCount) {
				// Nothing to carousel.
				return;
			}

			var slideCount = Math.ceil(total / visibleCount);
			var currentSlide = 0;

			// Helper: show a specific slide index with a fade animation.
			function showSlide(index) {
				currentSlide = index;

				var start = currentSlide * visibleCount;
				var end = start + visibleCount;

				// Trigger fade animation on the row wrapper.
				row.classList.remove('nz-cat-carousel-fade');
				// Force reflow so the animation can restart.
				// eslint-disable-next-line no-unused-expressions
				row.offsetHeight;
				row.classList.add('nz-cat-carousel-fade');

				items.forEach(function (item, i) {
					if (i >= start && i < end) {
						item.classList.remove('d-none');
					} else {
						item.classList.add('d-none');
					}
				});

				// Update dots
				if (dots) {
					dots.forEach(function (dot, i) {
						dot.classList.toggle('active', i === currentSlide);
					});
				}
			}

			// Build controls container
			var controlsWrapper = document.createElement('div');
			controlsWrapper.className = 'nz-cat-carousel-controls d-flex justify-content-between align-items-center mt-3';

			// Prev/next arrows
			var prevBtn = document.createElement('button');
			prevBtn.type = 'button';
			prevBtn.className = 'btn btn-link px-0 nz-cat-carousel-prev';
			prevBtn.innerHTML = '&laquo;';

			var nextBtn = document.createElement('button');
			nextBtn.type = 'button';
			nextBtn.className = 'btn btn-link px-0 nz-cat-carousel-next';
			nextBtn.innerHTML = '&raquo;';

			// Dots
			var dotsWrapper = document.createElement('div');
			dotsWrapper.className = 'nz-cat-carousel-dots d-flex justify-content-center';

			var dots = [];
			for (var i = 0; i < slideCount; i++) {
				var dot = document.createElement('button');
				dot.type = 'button';
				dot.className = 'nz-cat-carousel-dot btn btn-sm rounded-circle mx-1';
				dot.setAttribute('aria-label', 'Go to slide ' + (i + 1));

				(function (slideIndex) {
					dot.addEventListener('click', function () {
						showSlide(slideIndex);
						resetAuto();
					});
				})(i);

				dotsWrapper.appendChild(dot);
				dots.push(dot);
			}

			controlsWrapper.appendChild(prevBtn);
			controlsWrapper.appendChild(dotsWrapper);
			controlsWrapper.appendChild(nextBtn);

			// Insert controls after the categories block.
			block.parentNode.appendChild(controlsWrapper);

			// Navigation handlers
			prevBtn.addEventListener('click', function () {
				var nextIndex = currentSlide - 1;
				if (nextIndex < 0) {
					nextIndex = slideCount - 1;
				}
				showSlide(nextIndex);
				resetAuto();
			});

			nextBtn.addEventListener('click', function () {
				var nextIndex = currentSlide + 1;
				if (nextIndex >= slideCount) {
					nextIndex = 0;
				}
				showSlide(nextIndex);
				resetAuto();
			});

			// Auto-rotation
			var autoInterval = null;
			function startAuto() {
				autoInterval = window.setInterval(function () {
					var nextIndex = currentSlide + 1;
					if (nextIndex >= slideCount) {
						nextIndex = 0;
					}
					showSlide(nextIndex);
				}, 5000);
			}

			function resetAuto() {
				if (autoInterval) {
					window.clearInterval(autoInterval);
				}
				startAuto();
			}

			// Kick things off
			showSlide(0);
			startAuto();
		});
	});
})();

