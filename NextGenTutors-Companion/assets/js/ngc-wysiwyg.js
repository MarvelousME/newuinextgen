/**
 * Front-end: upgrade multiline textareas in public forms to TinyMCE editors.
 */
(function () {
	'use strict';

	var cfg = window.NGC_WYSIWYG || {};

	function ready(fn) {
		if (document.readyState !== 'loading') {
			fn();
		} else {
			document.addEventListener('DOMContentLoaded', fn);
		}
	}

	function shouldUpgrade(ta) {
		if (!ta || ta.dataset.ngcWysiwyg === 'off') {
			return false;
		}
		if (ta.classList.contains('ngc-wysiwyg') || ta.dataset.ngcWysiwyg === '1') {
			return true;
		}
		var rows = parseInt(ta.getAttribute('rows') || '0', 10);
		if (rows < 3) {
			return false;
		}
		var form = ta.closest('form');
		if (!form) {
			return false;
		}
		return (
			form.classList.contains('ngc-form') ||
			form.classList.contains('bi-ngc-form') ||
			form.classList.contains('ngt-form') ||
			form.classList.contains('ngc-studio-form') ||
			form.classList.contains('ngc-match-form') ||
			form.classList.contains('support-form')
		);
	}

	function initOne(ta, index) {
		if (ta.dataset.ngcWysiwygReady === '1') {
			return;
		}
		if (!window.wp || !wp.editor || typeof wp.editor.initialize !== 'function') {
			return;
		}
		if (!ta.id) {
			ta.id = 'ngc-wysiwyg-' + index + '-' + Date.now();
		}
		ta.classList.add('ngc-wysiwyg');
		try {
			wp.editor.initialize(ta.id, {
				tinymce: {
					wpautop: true,
					plugins: 'lists link paste',
					toolbar1: 'formatselect bold italic bullist numlist link unlink undo redo',
					toolbar2: '',
					height: Math.max(160, (parseInt(ta.getAttribute('rows') || '4', 10) || 4) * 28),
				},
				quicktags: true,
				mediaButtons: false,
			});
			ta.dataset.ngcWysiwygReady = '1';
		} catch (e) {
			/* leave plain textarea */
		}
	}

	function syncOnSubmit(form) {
		form.addEventListener('submit', function () {
			if (!window.tinymce) {
				return;
			}
			Array.prototype.forEach.call(form.querySelectorAll('textarea.ngc-wysiwyg'), function (ta) {
				var ed = tinymce.get(ta.id);
				if (ed) {
					ed.save();
				}
			});
		});
	}

	ready(function () {
		var nodes = document.querySelectorAll('textarea');
		var i = 0;
		Array.prototype.forEach.call(nodes, function (ta) {
			if (shouldUpgrade(ta)) {
				initOne(ta, i++);
			}
		});
		document.querySelectorAll('form.ngc-form, form.bi-ngc-form, form.ngt-form, form.ngc-studio-form, form.ngc-match-form').forEach(syncOnSubmit);

		if (cfg.debug) {
			/* no-op hook for smoke tests */
		}
	});
})();
