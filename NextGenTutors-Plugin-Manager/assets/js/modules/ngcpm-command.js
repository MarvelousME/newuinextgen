/**
 * Command palette (Ctrl/Cmd+K).
 */
(function (UI) {
	'use strict';

	UI.command = {};

	UI.command.baseCommands = [
		{ label: 'Scan plugins', action: 'scan', type: 'action' },
		{ label: 'Install & activate all', action: 'install-activate-all', type: 'action' },
		{ label: 'Export dependency report', action: 'export', type: 'action' },
		{ label: 'Go to Dashboard', view: 'dashboard', type: 'nav' },
		{ label: 'Go to Plugin Discovery', view: 'discovery', type: 'nav' },
		{ label: 'Go to System Health', view: 'health', type: 'nav' },
		{ label: 'Go to Verification Center', view: 'verification', type: 'nav' },
		{ label: 'Go to Audit Logs', view: 'logs', type: 'nav' },
		{ label: 'Go to Security Center', view: 'security', type: 'nav' },
		{ label: 'Go to Export Center', view: 'export', type: 'nav' },
		{ label: 'Go to Repair Center', view: 'repair', type: 'nav' },
		{ label: 'Go to Diagnostics', view: 'diagnostics', type: 'nav' },
		{ label: 'Go to Dependency Graph', view: 'graph', type: 'nav' },
		{ label: 'Go to Activation Manager', view: 'activation', type: 'nav' },
		{ label: 'Go to Install Queue', view: 'queue', type: 'nav' },
		{ label: 'Go to Exception Logs', view: 'exceptions', type: 'nav' },
		{ label: 'Open Install Queue panel', action: 'open-queue', type: 'action' },
	];

	UI.command.list = [];

	UI.command.buildList = function () {
		UI.command.list = UI.command.baseCommands.slice();
		if (NGCPM.plugins) {
			NGCPM.plugins.forEach(function (p) {
				UI.command.list.push({ label: 'Install ' + p.name, action: 'install', slug: p.slug, type: 'plugin' });
			});
		}
		UI.state.filteredCommands = UI.command.list.slice();
	};

	UI.command.render = function (filter) {
		var commandList = UI.refs.commandList;
		if (!commandList) {
			return;
		}
		var q = (filter || '').toLowerCase();
		UI.state.filteredCommands = UI.command.list.filter(function (c) {
			return !q || c.label.toLowerCase().indexOf(q) !== -1;
		});
		UI.state.commandIndex = 0;
		commandList.textContent = '';
		UI.state.filteredCommands.forEach(function (c, i) {
			var li = document.createElement('li');
			li.className = 'ngcpm-command__item' + (i === 0 ? ' is-selected' : '');
			li.setAttribute('role', 'option');
			li.setAttribute('data-cmd-index', String(i));
			var label = document.createElement('span');
			label.textContent = c.label;
			li.appendChild(label);
			var badge = document.createElement('span');
			badge.className = 'ngcpm-badge ngcpm-badge--info';
			badge.textContent = c.type;
			li.appendChild(badge);
			commandList.appendChild(li);
		});
	};

	UI.command.open = function () {
		if (!UI.refs.commandEl) {
			return;
		}
		UI.command.render('');
		UI.refs.commandEl.hidden = false;
		if (UI.refs.commandInput) {
			UI.refs.commandInput.value = '';
			UI.refs.commandInput.focus();
		}
	};

	UI.command.close = function () {
		if (UI.refs.commandEl) {
			UI.refs.commandEl.hidden = true;
		}
	};

	UI.command.updateSelection = function () {
		var commandList = UI.refs.commandList;
		if (!commandList) {
			return;
		}
		commandList.querySelectorAll('.ngcpm-command__item').forEach(function (el, i) {
			el.classList.toggle('is-selected', i === UI.state.commandIndex);
		});
	};

	UI.command.exec = function (cmd) {
		UI.command.close();
		if (!cmd) {
			return;
		}
		if (cmd.type === 'nav' && cmd.view) {
			UI.navigation.navigate(cmd.view);
			return;
		}
		if (cmd.action === 'install' && cmd.slug) {
			UI.actions.runInstall(cmd.slug);
			return;
		}
		UI.actions.handle(cmd.action);
	};
})(window.NGCPM_UI);
