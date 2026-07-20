/**
 * 3D hover tilt and light-green click ripple for Plugin Manager controls.
 */
(function (UI) {
	'use strict';

	UI.interactions = {};

	var SELECTOR = '.ngcpm-btn, .ngcpm-sidebar__link, .ngcpm-link-btn, .ngcpm-queue-page__item';

	UI.interactions.resetTilt = function (el) {
		if (!el) {
			return;
		}
		el.classList.remove('ngcpm-3d-hover');
		el.style.removeProperty('--ngcpm-tilt-x');
		el.style.removeProperty('--ngcpm-tilt-y');
	};

	UI.interactions.ripple = function (el, e) {
		var rect = el.getBoundingClientRect();
		var size = Math.max(rect.width, rect.height) * 1.25;
		var clientX = e && typeof e.clientX === 'number' ? e.clientX : rect.left + rect.width / 2;
		var clientY = e && typeof e.clientY === 'number' ? e.clientY : rect.top + rect.height / 2;
		var ripple = document.createElement('span');
		ripple.className = 'ngcpm-ripple';
		ripple.style.width = size + 'px';
		ripple.style.height = size + 'px';
		ripple.style.left = (clientX - rect.left - size / 2) + 'px';
		ripple.style.top = (clientY - rect.top - size / 2) + 'px';
		el.classList.add('ngcpm-ripple-host');
		el.appendChild(ripple);
		ripple.addEventListener('animationend', function () {
			ripple.remove();
			if (!el.querySelector('.ngcpm-ripple')) {
				el.classList.remove('ngcpm-ripple-host');
			}
		});
	};

	UI.interactions.init = function (shell) {
		if (!shell) {
			return;
		}

		var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

		shell.addEventListener('click', function (e) {
			var el = e.target.closest(SELECTOR);
			if (!el || !shell.contains(el)) {
				return;
			}
			if (el.disabled || el.classList.contains('is-loading') || el.classList.contains('is-processing')) {
				return;
			}
			// Visual ripple only — AJAX busy state is owned by setBusy / NGC_ButtonProcessing.
			UI.interactions.ripple(el, e);
		});

		if (reduceMotion) {
			return;
		}

		shell.addEventListener('pointerover', function (e) {
			var el = e.target.closest(SELECTOR);
			if (!el || !shell.contains(el) || el.disabled || el.classList.contains('is-loading')) {
				return;
			}
			el.classList.add('ngcpm-3d-hover');
		});

		shell.addEventListener('pointermove', function (e) {
			var el = e.target.closest(SELECTOR);
			if (!el || !shell.contains(el) || !el.classList.contains('ngcpm-3d-hover')) {
				return;
			}
			var rect = el.getBoundingClientRect();
			var x = (e.clientX - rect.left) / rect.width - 0.5;
			var y = (e.clientY - rect.top) / rect.height - 0.5;
			el.style.setProperty('--ngcpm-tilt-x', (-y * 5).toFixed(2) + 'deg');
			el.style.setProperty('--ngcpm-tilt-y', (x * 5).toFixed(2) + 'deg');
		});

		shell.addEventListener('pointerout', function (e) {
			var el = e.target.closest(SELECTOR);
			if (!el || !shell.contains(el)) {
				return;
			}
			var to = e.relatedTarget;
			if (to && el.contains(to)) {
				return;
			}
			UI.interactions.resetTilt(el);
		});
	};
})(window.NGCPM_UI);
