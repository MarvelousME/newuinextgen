/**
 * Lazy-loaded diagnostics view.
 */
(function (UI) {
	'use strict';

	UI.diagnostics = {};

	UI.diagnostics.refresh = function (silent) {
		if (!silent) {
			UI.actions.beginBusy();
		}
		UI.post('ngcpm_diagnostics').then(function (json) {
			if (!json.success || !json.data || !json.data.checks) {
				if (!silent) {
					UI.feedback.showToast(NGCPM.i18n.error, 'error');
				}
				return;
			}
			var shell = UI.refs.shell;
			var list = shell ? shell.querySelector('[data-diagnostics-list]') : null;
			if (!list) {
				return;
			}
			list.textContent = '';
			json.data.checks.forEach(function (check) {
				var li = document.createElement('li');
				li.className = 'ngcpm-diagnostics__row ngcpm-diagnostics__row--' + String(check.status || '').toLowerCase();
				var head = document.createElement('div');
				head.className = 'ngcpm-diagnostics__head';
				var strong = document.createElement('strong');
				strong.textContent = check.name || '';
				head.appendChild(strong);
				var badge = document.createElement('span');
				badge.className = UI.feedback.badgeClass(check.status);
				badge.textContent = check.status || '';
				head.appendChild(badge);
				li.appendChild(head);
				var evidence = document.createElement('p');
				evidence.className = 'ngcpm-diagnostics__evidence';
				evidence.textContent = check.evidence || '';
				li.appendChild(evidence);
				if (check.recommendation) {
					var rec = document.createElement('p');
					rec.className = 'ngcpm-diagnostics__rec';
					rec.textContent = check.recommendation;
					li.appendChild(rec);
				}
				list.appendChild(li);
			});
			UI.state.diagnosticsLoaded = true;
			if (!silent) {
				UI.feedback.showToast(NGCPM.i18n.done, 'success');
			}
		}).finally(function () {
			if (!silent) {
				UI.actions.endBusy();
			}
		});
	};

	UI.diagnostics.maybeLoad = function () {
		if (UI.state.diagnosticsLoaded || UI.refs.readonly) {
			return;
		}
		var shell = UI.refs.shell;
		var list = shell ? shell.querySelector('[data-diagnostics-list]') : null;
		if (!list) {
			return;
		}
		var placeholder = list.querySelector('[data-diagnostics-placeholder]');
		if (placeholder) {
			placeholder.textContent = NGCPM.i18n.diagnosticsLoading || 'Running diagnostics…';
		}
		UI.diagnostics.refresh(true);
	};
})(window.NGCPM_UI);
