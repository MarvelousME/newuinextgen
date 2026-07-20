/**
 * Button submit/AJAX processing — disable, "...processing", ripple.
 * Only for real form submits, navigation links, or explicit opt-in (data-ngc-process).
 * Never locks pure UI / navigation controls (data-nav, data-ngc-no-process).
 */
(function (global) {
	'use strict';

	var SELECTOR = [
		'button:not([data-ngc-no-process])',
		'input[type="submit"]:not([data-ngc-no-process])',
		'input[type="button"]:not([data-ngc-no-process])',
		'.button:not([data-ngc-no-process])',
		'.btn:not([data-ngc-no-process])',
		'.ngt-btn:not([data-ngc-no-process])',
		'.ngcpm-btn:not([data-ngc-no-process])',
		'a.button:not([data-ngc-no-process])',
	].join(', ');

	var activeEl = null;

	function isNavOrUiOnly(el) {
		if (!el || !el.getAttribute) {
			return true;
		}
		if (el.hasAttribute('data-ngc-no-process') || el.hasAttribute('data-nav') || el.hasAttribute('data-filter') || el.hasAttribute('data-view-mode') || el.hasAttribute('data-kpi')) {
			return true;
		}
		if (el.classList.contains('ngcpm-sidebar__link') || el.classList.contains('ngcpm-bottom-nav__item') || el.classList.contains('ngcpm-chip') || el.classList.contains('ngcpm-link-btn')) {
			return true;
		}
		var action = el.getAttribute('data-action') || '';
		var uiOnly = {
			'open-add-plugin': 1,
			'open-queue': 1,
			'close-queue': 1,
			'toggle-sidebar': 1,
			'toggle-drawer': 1,
			'command-palette': 1,
			'show-manual': 1,
			'start-tour': 1,
			'dismiss-notification': 1,
		};
		return !!uiOnly[action];
	}

	function shouldAutoProcess(el, e) {
		if (!el || isNavOrUiOnly(el)) {
			return false;
		}
		if (el.getAttribute('data-ngc-process') === '1' || el.getAttribute('data-ngc-process') === 'true') {
			return true;
		}
		if (el.tagName === 'A') {
			var href = el.getAttribute('href');
			if (!href || href === '#' || href.indexOf('javascript:') === 0) {
				return false;
			}
			if (el.target === '_blank' || (e && (e.metaKey || e.ctrlKey || e.shiftKey || e.altKey))) {
				return false;
			}
			return true;
		}
		if (el.type === 'submit') {
			return false;
		}
		return false;
	}

	function ripple(el, e) {
		if (!el || (global.matchMedia && global.matchMedia('(prefers-reduced-motion: reduce)').matches)) {
			return;
		}
		var rect = el.getBoundingClientRect();
		var size = Math.max(rect.width, rect.height) * 1.25;
		var clientX = e && typeof e.clientX === 'number' ? e.clientX : rect.left + rect.width / 2;
		var clientY = e && typeof e.clientY === 'number' ? e.clientY : rect.top + rect.height / 2;
		var node = document.createElement('span');
		node.className = 'ngc-btn-ripple';
		node.style.width = size + 'px';
		node.style.height = size + 'px';
		node.style.left = (clientX - rect.left - size / 2) + 'px';
		node.style.top = (clientY - rect.top - size / 2) + 'px';
		el.classList.add('ngc-btn-ripple-host');
		el.appendChild(node);
		node.addEventListener('animationend', function () {
			node.remove();
			if (!el.querySelector('.ngc-btn-ripple')) {
				el.classList.remove('ngc-btn-ripple-host');
			}
		});
	}

	function storeOriginal(el) {
		if (el.dataset.ngcOriginalHtml || el.dataset.ngcOriginalText) {
			return;
		}
		if (el.tagName === 'INPUT') {
			el.dataset.ngcOriginalText = el.value;
		} else {
			el.dataset.ngcOriginalHtml = el.innerHTML;
			el.dataset.ngcOriginalText = el.textContent;
		}
	}

	function start(el, e) {
		if (!el || el.disabled || el.classList.contains('is-processing')) {
			return;
		}
		if (isNavOrUiOnly(el)) {
			return;
		}
		storeOriginal(el);
		activeEl = el;
		el.classList.add('is-processing', 'ngc-btn-processing', 'is-loading');
		el.setAttribute('aria-busy', 'true');
		if (el.tagName === 'A') {
			el.setAttribute('aria-disabled', 'true');
			el.style.pointerEvents = 'none';
		} else {
			el.disabled = true;
		}
		if (el.tagName === 'INPUT') {
			el.value = '...processing';
		} else {
			el.textContent = '...processing';
		}
		ripple(el, e);
	}

	function stop(el) {
		if (!el) {
			return;
		}
		el.classList.remove('is-processing', 'ngc-btn-processing', 'is-loading');
		el.removeAttribute('aria-busy');
		if (el.tagName === 'A') {
			el.removeAttribute('aria-disabled');
			el.style.pointerEvents = '';
		} else {
			el.disabled = false;
		}
		if (el.tagName === 'INPUT') {
			if (el.dataset.ngcOriginalText) {
				el.value = el.dataset.ngcOriginalText;
			}
		} else if (el.dataset.ngcOriginalHtml) {
			el.innerHTML = el.dataset.ngcOriginalHtml;
		} else if (el.dataset.ngcOriginalText) {
			el.textContent = el.dataset.ngcOriginalText;
		}
		if (activeEl === el) {
			activeEl = null;
		}
	}

	function releaseActive() {
		if (activeEl) {
			stop(activeEl);
		}
	}

	function init() {
		document.addEventListener('click', function (e) {
			var el = e.target.closest(SELECTOR);
			if (!el || el.disabled || el.classList.contains('is-processing')) {
				return;
			}
			if (!shouldAutoProcess(el, e)) {
				return;
			}
			start(el, e);
		}, true);

		document.addEventListener('submit', function (e) {
			var form = e.target;
			if (!form || form.tagName !== 'FORM') {
				return;
			}
			if (typeof form.checkValidity === 'function' && !form.checkValidity()) {
				return;
			}
			var btn = e.submitter || form.querySelector('[type="submit"], .button-primary, button.ngt-btn, .ngcpm-btn[type="submit"]');
			if (btn && !isNavOrUiOnly(btn)) {
				start(btn, e);
			}
		}, true);

		document.addEventListener('ngc:form-invalid', function (e) {
			var form = e.detail && e.detail.form;
			if (!form) {
				releaseActive();
				return;
			}
			form.querySelectorAll('.is-processing').forEach(stop);
		});

		global.addEventListener('pageshow', releaseActive);
	}

	global.NGC_ButtonProcessing = {
		start: start,
		stop: stop,
		releaseActive: releaseActive,
		ripple: ripple,
		isNavOrUiOnly: isNavOrUiOnly,
	};

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})(window);
