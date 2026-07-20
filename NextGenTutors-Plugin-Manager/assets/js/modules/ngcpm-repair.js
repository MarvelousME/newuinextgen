/**
 * Repair center AJAX workflows.
 */
(function (UI) {
	'use strict';

	UI.repair = {};

	UI.repair.runOne = function (slug, strategy) {
		UI.actions.beginBusy();
		UI.feedback.setProgress(30, NGCPM.i18n.repairing || 'Repairing…');
		UI.post('ngcpm_repair', { slug: slug, strategy: strategy }).then(function (json) {
			if (!UI.actionSucceeded(json)) {
				var msg = (json.data && json.data.message) || NGCPM.i18n.error;
				UI.feedback.showToast(msg, 'error');
				return;
			}
			window.location.reload();
		}).finally(function () {
			UI.feedback.hideProgress();
			UI.actions.endBusy();
		});
	};

	UI.repair.runAll = function () {
		var shell = UI.refs.shell;
		var cards = shell ? shell.querySelectorAll('[data-action="repair-one"]') : [];
		if (!cards.length) {
			UI.feedback.showToast(NGCPM.i18n.done, 'success');
			return;
		}
		var queue = [];
		cards.forEach(function (btn) {
			queue.push({
				slug: btn.getAttribute('data-slug'),
				strategy: btn.getAttribute('data-strategy'),
			});
		});
		var idx = 0;
		var failures = [];
		UI.actions.beginBusy();

		function nextRepair() {
			if (idx >= queue.length) {
				UI.feedback.showErrors(failures);
				UI.actions.endBusy();
				if (!failures.length) {
					window.location.reload();
				}
				return;
			}
			var job = queue[idx];
			UI.post('ngcpm_repair', { slug: job.slug, strategy: job.strategy }).then(function (json) {
				if (!UI.actionSucceeded(json)) {
					failures.push({ slug: job.slug, message: (json.data && json.data.message) || 'Repair failed' });
				}
				idx++;
				nextRepair();
			}).catch(function () {
				failures.push({ slug: job.slug, message: 'Network error' });
				idx++;
				nextRepair();
			});
		}
		nextRepair();
	};
})(window.NGCPM_UI);
