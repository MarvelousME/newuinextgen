/**
 * View navigation, sidebar, filters, and manual-install UX.
 */
(function (UI) {
	'use strict';

	UI.navigation = {};

	UI.navigation.titles = {
		dashboard: 'Dashboard',
		readiness: 'System Readiness',
		discovery: 'Plugin Discovery',
		missing: 'Missing Plugins',
		queue: 'Install Queue',
		graph: 'Dependency Graph',
		activation: 'Activation Manager',
		configuration: 'Plugin Configuration',
		health: 'System Health',
		repair: 'Repair Center',
		diagnostics: 'Diagnostics',
		verification: 'Verification Center',
		logs: 'Audit Logs',
		exceptions: 'Exception Logs',
		security: 'Security Center',
		export: 'Export Center',
		about: 'About',
	};

	UI.navigation.navigate = function (viewId) {
		UI.refs.views.forEach(function (view) {
			var active = view.getAttribute('data-view') === viewId;
			view.classList.toggle('is-active', active);
			if (active) {
				view.removeAttribute('hidden');
			} else {
				view.setAttribute('hidden', '');
			}
		});
		UI.refs.navLinks.forEach(function (link) {
			var match = link.getAttribute('data-nav') === viewId;
			link.classList.toggle('is-active', match);
		});
		if (UI.refs.pageTitle && UI.navigation.titles[viewId]) {
			UI.refs.pageTitle.textContent = UI.navigation.titles[viewId];
		}
		if (viewId === 'queue' && UI.queue) {
			UI.queue.buildPreview();
		}
		if (viewId === 'diagnostics' && UI.diagnostics) {
			UI.diagnostics.maybeLoad();
		}
		try {
			localStorage.setItem('ngcpm_last_view', viewId);
		} catch (e) { /* ignore */ }
		UI.navigation.closeSidebar();
	};

	UI.navigation.showManual = function (slug) {
		var notes = (NGCPM.manualNotes && NGCPM.manualNotes[slug]) || '';
		UI.navigation.navigate('missing');
		window.setTimeout(function () {
			var shell = UI.refs.shell;
			var card = shell ? shell.querySelector('.ngcpm-card[data-slug="' + slug + '"]') : null;
			if (card) {
				card.scrollIntoView({ behavior: 'smooth', block: 'center' });
				card.classList.add('is-highlight');
				window.setTimeout(function () {
					card.classList.remove('is-highlight');
				}, 2500);
				var details = card.querySelector('.ngcpm-card__details');
				if (details) {
					details.open = true;
				}
			}
			var title = NGCPM.i18n.manualTitle || 'Manual install required';
			UI.feedback.showToast(notes ? title + ': ' + notes : title, 'error');
		}, 150);
	};

	UI.navigation.openSidebar = function () {
		if (UI.refs.sidebar) {
			UI.refs.sidebar.classList.add('is-open');
		}
		if (UI.refs.scrim) {
			UI.refs.scrim.hidden = false;
		}
	};

	UI.navigation.closeSidebar = function () {
		if (UI.refs.sidebar) {
			UI.refs.sidebar.classList.remove('is-open');
		}
		if (UI.refs.scrim) {
			UI.refs.scrim.hidden = true;
		}
	};

	UI.navigation.applyFilter = function (filter, search) {
		var shell = UI.refs.shell;
		if (!shell) {
			return;
		}
		var cards = shell.querySelectorAll('.ngcpm-card[data-filter-tags]');
		var rows = shell.querySelectorAll('.ngcpm-table tbody tr[data-filter-tags]');
		var q = (search || '').toLowerCase();

		function match(el) {
			var tags = el.getAttribute('data-filter-tags') || '';
			var name = el.getAttribute('data-name') || '';
			var tagOk = filter === 'all' || tags.indexOf(filter) !== -1;
			var searchOk = !q || name.indexOf(q) !== -1 || tags.indexOf(q) !== -1;
			el.classList.toggle('is-hidden', !(tagOk && searchOk));
		}

		cards.forEach(match);
		rows.forEach(match);
	};

	UI.navigation.restoreLastView = function () {
		try {
			var last = localStorage.getItem('ngcpm_last_view');
			if (last && UI.navigation.titles[last] && last !== 'dashboard') {
				UI.navigation.navigate(last);
			}
		} catch (err) { /* ignore */ }
	};
})(window.NGCPM_UI);
