/**
 * Accessible confirmation modal (folder overwrite, destructive actions).
 */
(function (UI) {
	'use strict';

	UI.modal = {};

	UI.modal.ensure = function () {
		if (UI.refs.modalEl) {
			return UI.refs.modalEl;
		}
		var shell = UI.refs.shell;
		if (!shell) {
			return null;
		}
		var el = document.createElement('div');
		el.id = 'ngcpm-modal';
		el.className = 'ngcpm-modal';
		el.setAttribute('role', 'dialog');
		el.setAttribute('aria-modal', 'true');
		el.hidden = true;
		el.innerHTML =
			'<div class="ngcpm-modal__panel">' +
			'<header class="ngcpm-modal__head">' +
			'<h2 class="ngcpm-modal__title" data-modal-title></h2>' +
			'<button type="button" class="ngcpm-btn ngcpm-btn--icon" data-modal-close aria-label="Close">' +
			'<span aria-hidden="true">×</span></button>' +
			'</header>' +
			'<div class="ngcpm-modal__body" data-modal-body></div>' +
			'<footer class="ngcpm-modal__foot">' +
			'<button type="button" class="ngcpm-btn ngcpm-btn--secondary" data-modal-cancel></button>' +
			'<button type="button" class="ngcpm-btn ngcpm-btn--primary" data-modal-confirm></button>' +
			'</footer>' +
			'</div>';
		shell.appendChild(el);
		UI.refs.modalEl = el;
		UI.refs.modalTitle = el.querySelector('[data-modal-title]');
		UI.refs.modalBody = el.querySelector('[data-modal-body]');
		UI.refs.modalConfirm = el.querySelector('[data-modal-confirm]');
		UI.refs.modalCancel = el.querySelector('[data-modal-cancel]');
		UI.refs.modalClose = el.querySelector('[data-modal-close]');

		el.addEventListener('click', function (e) {
			if (e.target === el) {
				UI.modal._resolve(false);
			}
		});
		UI.refs.modalClose.addEventListener('click', function () {
			UI.modal._resolve(false);
		});
		UI.refs.modalCancel.addEventListener('click', function () {
			UI.modal._resolve(false);
		});
		UI.refs.modalConfirm.addEventListener('click', function () {
			UI.modal._resolve(true);
		});
		document.addEventListener('keydown', function (e) {
			if (e.key === 'Escape' && UI.refs.modalEl && !UI.refs.modalEl.hidden) {
				UI.modal._resolve(false);
			}
		});
		return el;
	};

	UI.modal._resolve = function (value) {
		if (UI.refs.modalEl) {
			UI.refs.modalEl.hidden = true;
		}
		if (typeof UI.modal._pending === 'function') {
			var fn = UI.modal._pending;
			UI.modal._pending = null;
			fn(value);
		}
	};

	/**
	 * @param {Object} opts Options.
	 * @return {Promise<boolean>}
	 */
	UI.modal.confirm = function (opts) {
		opts = opts || {};
		UI.modal.ensure();
		return new Promise(function (resolve) {
			UI.modal._pending = resolve;
			if (UI.refs.modalTitle) {
				UI.refs.modalTitle.textContent = opts.title || (NGCPM.i18n && NGCPM.i18n.confirmTitle) || 'Confirm';
			}
			if (UI.refs.modalBody) {
				UI.refs.modalBody.textContent = opts.message || '';
			}
			if (UI.refs.modalConfirm) {
				UI.refs.modalConfirm.textContent = opts.confirmLabel || (NGCPM.i18n && NGCPM.i18n.confirmOk) || 'OK';
			}
			if (UI.refs.modalCancel) {
				UI.refs.modalCancel.textContent = opts.cancelLabel || (NGCPM.i18n && NGCPM.i18n.confirmCancel) || 'Cancel';
			}
			if (UI.refs.modalEl) {
				UI.refs.modalEl.hidden = false;
				UI.refs.modalConfirm.focus();
			}
		});
	};

	/**
	 * @param {Object} json AJAX response.
	 * @return {boolean}
	 */
	UI.modal.isFolderExists = function (json) {
		if (!json || !json.data) {
			return false;
		}
		var code = json.data.code || (json.data.result && json.data.result.code) || '';
		return code === 'folder_exists';
	};

	/**
	 * @param {Object} json AJAX response.
	 * @return {string}
	 */
	UI.modal.folderFromResponse = function (json) {
		if (!json || !json.data) {
			return '';
		}
		return (json.data.result && json.data.result.folder) || json.data.folder || '';
	};

	/**
	 * Prompt to overwrite folder, then install with overwrite flag.
	 *
	 * @param {string} slug Plugin slug.
	 * @param {Object} json Failed install response.
	 * @return {Promise<Object>}
	 */
	UI.modal.promptOverwriteInstall = function (slug, json) {
		var folder = UI.modal.folderFromResponse(json) || slug;
		var plugin = (NGCPM.plugins || []).find(function (p) { return p.slug === slug; });
		var name = (plugin && plugin.name) || slug;
		return UI.modal.confirm({
			title: (NGCPM.i18n && NGCPM.i18n.folderExistsTitle) || 'Folder already exists',
			message: ((NGCPM.i18n && NGCPM.i18n.folderExistsMessage) || 'The folder "{folder}" already exists for {plugin}. Overwrite it and reinstall?')
				.replace('{folder}', folder)
				.replace('{plugin}', name),
			confirmLabel: (NGCPM.i18n && NGCPM.i18n.folderOverwrite) || 'Overwrite & install',
			cancelLabel: (NGCPM.i18n && NGCPM.i18n.confirmCancel) || 'Cancel',
		}).then(function (ok) {
			if (!ok) {
				return { success: false, cancelled: true, data: { message: (NGCPM.i18n && NGCPM.i18n.installCancelled) || 'Install cancelled.' } };
			}
			return UI.post('ngcpm_install', { slug: slug, overwrite: '1' });
		});
	};
})(window.NGCPM_UI);
