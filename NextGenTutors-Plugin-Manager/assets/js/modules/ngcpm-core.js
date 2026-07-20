/**
 * NGCPM shared state, DOM refs, and AJAX helpers.
 */
(function (UI) {
	'use strict';

	if (typeof window.NGCPM === 'undefined') {
		window.NGCPM = {};
	}

	NGCPM.parseError = function (text) {
		text = String(text || '');
		var fatal = text.match(/Fatal error:[\s\S]*? on line \d+/i);
		if (fatal) { return fatal[0].replace(/\s+/g, ' ').trim(); }
		var uncaught = text.match(/Uncaught [^:]+:[\s\S]*? on line \d+/i);
		if (uncaught) { return uncaught[0].replace(/\s+/g, ' ').trim(); }
		if (/critical error/i.test(text)) {
			return (NGCPM.i18n && NGCPM.i18n.fatalHint) || 'WordPress fatal error — see debug.log or Exception Logs.';
		}
		var jsonMsg = text.match(/"message"\s*:\s*"([^"]+)"/);
		if (jsonMsg) { return jsonMsg[1]; }
		return ((NGCPM.i18n && NGCPM.i18n.invalidResponse) || 'Unexpected server response') + ': ' + text.replace(/\s+/g, ' ').trim().slice(0, 180);
	};

	UI.refs = {};
	UI.state = {
		busy: false,
		diagnosticsLoaded: false,
		commandIndex: 0,
		filteredCommands: [],
		pendingAjaxBtn: null,
		activeAjaxBtn: null,
	};

	/**
	 * Cache DOM references for the app shell.
	 *
	 * @param {HTMLElement} shell Root app element.
	 */
	UI.initRefs = function (shell) {
		UI.refs.shell = shell;
		UI.refs.readonly = shell.getAttribute('data-readonly') === '1';
		UI.refs.views = shell.querySelectorAll('.ngcpm-view');
		UI.refs.navLinks = shell.querySelectorAll('[data-nav]');
		UI.refs.pageTitle = shell.querySelector('[data-ngcpm-page-title]');
		UI.refs.sidebar = document.getElementById('ngcpm-sidebar');
		UI.refs.scrim = shell.querySelector('[data-ngcpm-scrim]');
		UI.refs.progressEl = shell.querySelector('.ngcpm-progress');
		UI.refs.progressBar = UI.refs.progressEl ? UI.refs.progressEl.querySelector('.ngcpm-progress__bar') : null;
		UI.refs.progressLabel = UI.refs.progressEl ? UI.refs.progressEl.querySelector('.ngcpm-progress__label') : null;
		UI.refs.toastEl = shell.querySelector('.ngcpm-toast');
		UI.refs.errorsEl = shell.querySelector('.ngcpm-errors');
		UI.refs.queueEl = document.getElementById('ngcpm-queue');
		UI.refs.queueList = UI.refs.queueEl ? UI.refs.queueEl.querySelector('[data-queue-list]') : null;
		UI.refs.queueSummary = shell.querySelector('[data-queue-summary]');
		UI.refs.commandEl = document.getElementById('ngcpm-command');
		UI.refs.commandInput = UI.refs.commandEl ? UI.refs.commandEl.querySelector('.ngcpm-command__input') : null;
		UI.refs.commandList = UI.refs.commandEl ? UI.refs.commandEl.querySelector('.ngcpm-command__list') : null;
	};

	/**
	 * Parse admin-ajax response body safely.
	 *
	 * @param {Response} res Fetch response.
	 * @return {Promise<Object>}
	 */
	UI.parseAjaxResponse = function (res) {
		return res.text().then(function (text) {
			if (!text) {
				return {
					success: false,
					data: { message: (NGCPM.i18n && NGCPM.i18n.emptyResponse) || 'Empty server response' },
				};
			}
			try {
				return JSON.parse(text);
			} catch (e) {
				var parsed = (NGCPM.parseError && NGCPM.parseError(text)) || text.replace(/\s+/g, ' ').trim().slice(0, 220);
				return {
					success: false,
					data: { message: parsed },
				};
			}
		});
	};

	/**
	 * POST to admin-ajax.php.
	 *
	 * @param {string} action AJAX action name.
	 * @param {Object} [data]   Extra fields.
	 * @return {Promise<Object>}
	 */
	UI.post = function (action, data) {
		if (typeof NGCPM === 'undefined') {
			return Promise.resolve({
				success: false,
				data: { message: 'Plugin Manager configuration failed to load.' },
			});
		}
		var body = new URLSearchParams();
		body.append('action', action);
		body.append('nonce', NGCPM.nonce);
		if (data) {
			Object.keys(data).forEach(function (key) {
				body.append(key, data[key]);
			});
		}
		return fetch(NGCPM.ajax, {
			method: 'POST',
			credentials: 'same-origin',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
			body: body.toString(),
		})
			.then(UI.parseAjaxResponse)
			.then(function (json) {
				if (json && json.data && json.data.code === 'rate_limited' && UI.feedback) {
					UI.feedback.showToast(NGCPM.i18n.rateLimited || 'Too many requests', 'error');
				}
				return json;
			})
			.catch(function () {
				return {
					success: false,
					data: { message: (NGCPM.i18n && NGCPM.i18n.networkError) || 'Network error' },
				};
			})
			.finally(function () {
				if (window.NGC_ButtonProcessing) {
					window.NGC_ButtonProcessing.releaseActive();
				}
			});
	};

	/**
	 * Multipart POST for zip uploads.
	 *
	 * @param {string} action AJAX action.
	 * @param {FormData} formData Body.
	 * @return {Promise<Object>}
	 */
	UI.postForm = function (action, formData) {
		if (typeof NGCPM === 'undefined') {
			return Promise.resolve({ success: false, data: { message: 'Plugin Manager configuration failed to load.' } });
		}
		formData.append('action', action);
		formData.append('nonce', NGCPM.nonce);
		return fetch(NGCPM.ajax, {
			method: 'POST',
			credentials: 'same-origin',
			body: formData,
		})
			.then(UI.parseAjaxResponse)
			.catch(function () {
				return {
					success: false,
					data: { message: (NGCPM.i18n && NGCPM.i18n.networkError) || 'Network error' },
				};
			})
			.finally(function () {
				if (window.NGC_ButtonProcessing) {
					window.NGC_ButtonProcessing.releaseActive();
				}
			});
	};

	/**
	 * @param {Object} json AJAX response.
	 * @return {boolean}
	 */
	UI.actionSucceeded = function (json) {
		if (!json || !json.success) {
			return false;
		}
		if (json.data && json.data.result && json.data.result.success === false) {
			return false;
		}
		return true;
	};

	/**
	 * @return {boolean}
	 */
	UI.isBusy = function () {
		return UI.state.busy;
	};
})(window.NGCPM_UI = window.NGCPM_UI || {});
