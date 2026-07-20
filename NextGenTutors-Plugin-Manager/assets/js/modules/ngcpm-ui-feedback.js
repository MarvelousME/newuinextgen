/**
 * Toast, progress, errors, and busy-state feedback.
 * Busy state only locks AJAX submit controls — never permanently rewrites nav UI.
 */
(function (UI) {
	'use strict';

	UI.feedback = {};

	UI.feedback.showToast = function (message, type) {
		var toastEl = UI.refs.toastEl;
		if (!toastEl) {
			return;
		}
		toastEl.textContent = message;
		toastEl.className = 'ngcpm-toast' + (type ? ' is-' + type : '');
		toastEl.hidden = false;
		window.clearTimeout(UI.feedback.showToast._t);
		UI.feedback.showToast._t = window.setTimeout(function () {
			toastEl.hidden = true;
		}, 4000);
	};

	UI.feedback.setProgress = function (pct, label, success) {
		var progressEl = UI.refs.progressEl;
		if (!progressEl) {
			return;
		}
		progressEl.hidden = false;
		progressEl.classList.toggle('is-success', !!success);
		if (UI.refs.progressBar) {
			UI.refs.progressBar.style.width = Math.min(100, Math.max(0, pct)) + '%';
		}
		if (UI.refs.progressLabel) {
			UI.refs.progressLabel.textContent = label || '';
		}
	};

	UI.feedback.hideProgress = function () {
		var progressEl = UI.refs.progressEl;
		if (!progressEl) {
			return;
		}
		progressEl.hidden = true;
		progressEl.classList.remove('is-success');
	};

	/**
	 * @param {boolean} state Busy on/off.
	 * @param {HTMLElement} [targetBtn] Optional submit button that triggered the AJAX action.
	 */
	UI.feedback.setBusy = function (state, targetBtn) {
		UI.state.busy = state;
		var shell = UI.refs.shell;
		if (!shell) {
			return;
		}

		if (state) {
			shell.classList.add('is-busy');
			shell.setAttribute('aria-busy', 'true');

			if (targetBtn && shell.contains(targetBtn)) {
				UI.state.activeAjaxBtn = targetBtn;
				if (window.NGC_ButtonProcessing) {
					window.NGC_ButtonProcessing.start(targetBtn);
				} else {
					if (!targetBtn.dataset.ngcOriginalHtml) {
						targetBtn.dataset.ngcOriginalHtml = targetBtn.innerHTML;
					}
					targetBtn.disabled = true;
					targetBtn.classList.add('is-loading', 'is-processing', 'ngc-btn-processing');
					targetBtn.textContent = '...processing';
				}
			}

			shell.querySelectorAll('.ngcpm-btn[data-action]').forEach(function (btn) {
				if (window.NGC_ButtonProcessing && window.NGC_ButtonProcessing.isNavOrUiOnly && window.NGC_ButtonProcessing.isNavOrUiOnly(btn)) {
					return;
				}
				if (btn === targetBtn) {
					return;
				}
				if (!btn.dataset.ngcWasDisabled) {
					btn.dataset.ngcWasDisabled = btn.disabled ? '1' : '0';
				}
				btn.disabled = true;
			});
			return;
		}

		shell.classList.remove('is-busy');
		shell.removeAttribute('aria-busy');

		if (UI.state.activeAjaxBtn) {
			if (window.NGC_ButtonProcessing) {
				window.NGC_ButtonProcessing.stop(UI.state.activeAjaxBtn);
			} else {
				var active = UI.state.activeAjaxBtn;
				active.disabled = false;
				active.classList.remove('is-loading', 'is-processing', 'ngc-btn-processing');
				if (active.dataset.ngcOriginalHtml) {
					active.innerHTML = active.dataset.ngcOriginalHtml;
				}
			}
			UI.state.activeAjaxBtn = null;
		}

		if (window.NGC_ButtonProcessing) {
			window.NGC_ButtonProcessing.releaseActive();
		}

		shell.querySelectorAll('.ngcpm-btn[data-action]').forEach(function (btn) {
			if (btn.dataset.ngcWasDisabled !== undefined) {
				btn.disabled = btn.dataset.ngcWasDisabled === '1';
				delete btn.dataset.ngcWasDisabled;
			} else if (!btn.classList.contains('is-processing')) {
				btn.disabled = false;
			}
			btn.classList.remove('is-loading', 'is-processing', 'ngc-btn-processing');
			if (btn.dataset.ngcOriginalHtml && btn.textContent.trim() === '...processing') {
				btn.innerHTML = btn.dataset.ngcOriginalHtml;
			} else if (btn.dataset.ngcOriginalText && btn.textContent.trim() === '...processing') {
				btn.textContent = btn.dataset.ngcOriginalText;
			}
		});
	};

	UI.feedback.badgeClass = function (status) {
		return 'ngcpm-badge ngcpm-badge--' + String(status || '').toLowerCase();
	};

	UI.feedback.updateScore = function (health) {
		if (!health) {
			return;
		}
		var shell = UI.refs.shell;
		if (!shell) {
			return;
		}
		var pct = health.readiness_percent || 0;
		var ring = shell.querySelector('.ngcpm-ring__fill');
		if (ring) {
			ring.style.strokeDasharray = (364 * pct / 100) + ' 364';
		}
		var pctEl = shell.querySelector('[data-ngcpm-pct]');
		if (pctEl) {
			pctEl.textContent = String(pct);
		}
		var overallEl = shell.querySelector('[data-ngcpm-overall]');
		if (overallEl) {
			overallEl.textContent = health.overall_status || '';
			overallEl.className = UI.feedback.badgeClass(health.overall_status);
		}
	};

	UI.feedback.showErrors = function (items) {
		var errorsEl = UI.refs.errorsEl;
		if (!errorsEl) {
			return;
		}
		if (!items || !items.length) {
			errorsEl.hidden = true;
			errorsEl.textContent = '';
			return;
		}
		errorsEl.hidden = false;
		var list = document.createElement('ul');
		items.forEach(function (item) {
			var li = document.createElement('li');
			li.textContent = (item.message || item.status || 'Failed') + (item.slug ? ' (' + item.slug + ')' : '');
			list.appendChild(li);
		});
		errorsEl.textContent = '';
		var strong = document.createElement('strong');
		strong.textContent = NGCPM.i18n.error || 'Errors';
		errorsEl.appendChild(strong);
		errorsEl.appendChild(list);
	};
})(window.NGCPM_UI);
