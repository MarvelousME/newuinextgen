/**
 * Admin notifications — dismiss with event delegation.
 */
(function (UI) {
	'use strict';

	UI.notifications = {};

	UI.notifications.dismissMessage = (NGCPM.i18n && NGCPM.i18n.notificationDismissed) || 'Notification dismissed.';

	UI.notifications.hasCookie = function (name, token) {
		if (!document.cookie) {
			return false;
		}
		var parts = document.cookie.split(';');
		for (var i = 0; i < parts.length; i++) {
			var pair = parts[i].trim().split('=');
			if (pair[0] === name && decodeURIComponent(pair[1] || '') === token) {
				return true;
			}
		}
		return false;
	};

	UI.notifications.dismiss = function (btn) {
		if (!btn || UI.refs.readonly) {
			return;
		}
		var id = btn.getAttribute('data-notice-id');
		var hash = btn.getAttribute('data-notice-hash');
		if (!id || !hash) {
			return;
		}
		btn.disabled = true;
		UI.post('ngcpm_dismiss_notification', {
			id: id,
			hash: hash,
			scope: btn.getAttribute('data-notice-scope') || 'user',
		}).then(function (json) {
			var msg = (json.data && json.data.message) || UI.notifications.dismissMessage;
			if (json.success) {
				var item = btn.closest('[data-notification]');
				if (item) {
					item.classList.add('is-dismissed');
					window.setTimeout(function () {
						if (item.parentNode) {
							item.parentNode.removeChild(item);
						}
					}, 200);
				}
				UI.feedback.showToast(msg, 'success');
			} else {
				UI.feedback.showToast(msg, 'error');
				btn.disabled = false;
			}
		}).catch(function () {
			UI.feedback.showToast(NGCPM.i18n.networkError || NGCPM.i18n.error, 'error');
			btn.disabled = false;
		});
	};

	UI.notifications.runCookieProbe = function () {
		UI.actions.beginBusy();
		UI.feedback.setProgress(15, NGCPM.i18n.cookieProbe || 'Running cookie probe…');
		UI.post('ngcpm_cookie_probe', { step: 'init' }).then(function (initJson) {
			if (!initJson.success || !initJson.data) {
				var initMsg = (initJson.data && initJson.data.message) || NGCPM.i18n.error;
				UI.feedback.showToast(initMsg, 'error');
				return null;
			}
			var data = initJson.data;
			var browserOk = UI.notifications.hasCookie(data.cookie_name, data.token);
			UI.feedback.setProgress(55, NGCPM.i18n.cookieProbeVerify || 'Verifying cookie round-trip…');
			return UI.post('ngcpm_cookie_probe', {
				step: 'verify',
				token: data.token,
				browser_confirmed: browserOk ? '1' : '',
			});
		}).then(function (verifyJson) {
			if (!verifyJson) {
				return;
			}
			if (verifyJson.success) {
				UI.feedback.showToast(verifyJson.data.message || NGCPM.i18n.done, 'success');
			} else {
				var reason = (verifyJson.data && verifyJson.data.message) || NGCPM.i18n.error;
				var repair = verifyJson.data && verifyJson.data.repair;
				UI.feedback.showToast(reason, 'warning');
				if (repair) {
					window.setTimeout(function () {
						UI.feedback.showToast(repair, 'info');
					}, 500);
				}
			}
		}).finally(function () {
			UI.feedback.hideProgress();
			UI.actions.endBusy();
		});
	};

	UI.notifications.bindDelegation = function (shell) {
		if (!shell || shell.getAttribute('data-notifications-bound') === '1') {
			return;
		}
		shell.setAttribute('data-notifications-bound', '1');
		shell.addEventListener('click', function (e) {
			var dismissBtn = e.target.closest('[data-action="dismiss-notification"]');
			if (dismissBtn && shell.contains(dismissBtn)) {
				e.preventDefault();
				UI.notifications.dismiss(dismissBtn);
			}
		});
	};
})(window.NGCPM_UI);
