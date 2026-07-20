/**
 * NextGenTutors Plugin Manager — bootstrap and event wiring.
 */
(function (UI) {
	'use strict';

	if (typeof NGCPM === 'undefined') {
		return;
	}

	var shell = document.getElementById('ngcpm-app');
	if (!shell) {
		return;
	}

	UI.initRefs(shell);
	UI.command.buildList();
	UI.notifications.bindDelegation(shell);
	UI.navigation.closeSidebar();
	if (UI.interactions) {
		UI.interactions.init(shell);
	}

	shell.querySelectorAll('[data-copy-path]').forEach(function (el) {
		el.addEventListener('click', function () {
			var text = el.textContent || '';
			if (!text) {
				return;
			}
			if (navigator.clipboard && navigator.clipboard.writeText) {
				navigator.clipboard.writeText(text).then(function () {
					if (UI.feedback) {
						UI.feedback.showToast((NGCPM.i18n && NGCPM.i18n.pathCopied) || 'Copied', 'success');
					}
				});
			}
		});
	});

	function onClick(e) {
		var nav = e.target.closest('[data-nav]');
		if (nav && shell.contains(nav)) {
			e.preventDefault();
			UI.navigation.navigate(nav.getAttribute('data-nav'));
			return;
		}

		var btn = e.target.closest('[data-action]');
		if (!btn || !shell.contains(btn)) {
			return;
		}
		if (UI.refs.readonly && btn.getAttribute('data-action') !== 'command-palette') {
			return;
		}
		e.preventDefault();
		var action = btn.getAttribute('data-action');
		var slug = btn.getAttribute('data-slug');
		var wporgSlug = btn.getAttribute('data-wporg-slug');
		var searchForm = btn.closest('[data-wporg-search-form]');
		var uploadForm = btn.closest('[data-upload-plugin-form]');
		var pluginFile = btn.getAttribute('data-plugin-file') || '';

		UI.state.pendingAjaxBtn = btn;

		UI.actions.handle(action, slug, {
			strategy: btn.getAttribute('data-strategy') || '',
			wporg_slug: wporgSlug || '',
			plugin_file: pluginFile,
			term: searchForm ? (searchForm.querySelector('[name="term"]') || {}).value || '' : '',
			form: uploadForm || null,
			button: btn,
		});
	}

	shell.addEventListener('click', onClick);

	if (UI.refs.scrim) {
		UI.refs.scrim.addEventListener('click', UI.navigation.closeSidebar);
	}
	if (UI.refs.queueEl) {
		UI.refs.queueEl.addEventListener('click', function (e) {
			if (e.target === UI.refs.queueEl) {
				UI.queue.close();
			}
		});
	}
	if (UI.refs.commandEl) {
		UI.refs.commandEl.addEventListener('click', function (e) {
			if (e.target === UI.refs.commandEl) {
				UI.command.close();
			}
		});
	}

	shell.querySelectorAll('[data-filter]').forEach(function (chip) {
		chip.addEventListener('click', function () {
			shell.querySelectorAll('[data-filter]').forEach(function (c) {
				c.classList.remove('is-active');
			});
			chip.classList.add('is-active');
			var search = shell.querySelector('[data-filter-search]');
			UI.navigation.applyFilter(chip.getAttribute('data-filter'), search ? search.value : '');
		});
	});

	var searchInput = shell.querySelector('[data-filter-search]');
	if (searchInput) {
		searchInput.addEventListener('input', function () {
			var active = shell.querySelector('[data-filter].is-active');
			UI.navigation.applyFilter(active ? active.getAttribute('data-filter') : 'all', searchInput.value);
		});
	}

	var wporgForm = shell.querySelector('[data-wporg-search-form]');
	if (wporgForm) {
		wporgForm.addEventListener('submit', function (e) {
			e.preventDefault();
			var term = (wporgForm.querySelector('[name="term"]') || {}).value || '';
			var submitBtn = e.submitter || wporgForm.querySelector('[type="submit"], [data-action="search-wporg"]');
			UI.state.pendingAjaxBtn = submitBtn || null;
			UI.actions.runWporgSearch(term, submitBtn);
		});
	}

	shell.querySelectorAll('[data-view-mode]').forEach(function (btn) {
		btn.addEventListener('click', function () {
			var section = btn.closest('[data-plugin-list]');
			if (!section) {
				return;
			}
			shell.querySelectorAll('[data-view-mode]').forEach(function (b) {
				b.classList.remove('is-active');
			});
			btn.classList.add('is-active');
			section.setAttribute('data-layout', btn.getAttribute('data-view-mode'));
		});
	});

	shell.querySelectorAll('[data-kpi]').forEach(function (kpi) {
		kpi.addEventListener('click', function () {
			var key = kpi.getAttribute('data-kpi');
			if (key === 'missing' || key === 'manual') {
				UI.navigation.navigate('missing');
			} else if (key === 'security') {
				UI.navigation.navigate('security');
			} else {
				UI.navigation.navigate('discovery');
			}
		});
	});

	if (UI.refs.commandInput) {
		UI.refs.commandInput.addEventListener('input', function () {
			UI.command.render(UI.refs.commandInput.value);
		});
		UI.refs.commandInput.addEventListener('keydown', function (e) {
			if (e.key === 'ArrowDown') {
				e.preventDefault();
				UI.state.commandIndex = Math.min(UI.state.commandIndex + 1, UI.state.filteredCommands.length - 1);
				UI.command.updateSelection();
			} else if (e.key === 'ArrowUp') {
				e.preventDefault();
				UI.state.commandIndex = Math.max(UI.state.commandIndex - 1, 0);
				UI.command.updateSelection();
			} else if (e.key === 'Enter') {
				e.preventDefault();
				UI.command.exec(UI.state.filteredCommands[UI.state.commandIndex]);
			} else if (e.key === 'Escape') {
				UI.command.close();
			}
		});
	}

	if (UI.refs.commandList) {
		UI.refs.commandList.addEventListener('click', function (e) {
			var item = e.target.closest('[data-cmd-index]');
			if (!item) {
				return;
			}
			UI.command.exec(UI.state.filteredCommands[parseInt(item.getAttribute('data-cmd-index'), 10)]);
		});
	}

	document.addEventListener('keydown', function (e) {
		if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
			e.preventDefault();
			if (UI.refs.commandEl && UI.refs.commandEl.hidden) {
				UI.command.open();
			} else {
				UI.command.close();
			}
		}
		if (e.key === 'Escape') {
			UI.command.close();
			UI.queue.close();
			UI.navigation.closeSidebar();
		}
	});

	UI.navigation.restoreLastView();

	if (NGCPM.localPackages && NGCPM.localPackages.auto_enabled && NGCPM.localPackages.pending_count > 0 && NGCPM.canInstall && !UI.refs.readonly) {
		try {
			var autoKey = 'ngcpm_local_auto_queue';
			if (!sessionStorage.getItem(autoKey)) {
				sessionStorage.setItem(autoKey, '1');
				UI.feedback.showToast('Local plugin zips detected — starting install queue…', 'success');
				window.setTimeout(function () {
					UI.navigation.navigate('queue');
					if (UI.queue && UI.queue.runInstall) {
						UI.queue.runInstall();
					}
				}, 700);
			}
		} catch (autoErr) {
			UI.navigation.navigate('queue');
			if (UI.queue && UI.queue.runInstall) {
				UI.queue.runInstall();
			}
		}
	}

	if (UI.tour && !UI.refs.readonly) {
		UI.tour.maybeAutoStart();
	}

	if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
		shell.setAttribute('data-theme', 'dark');
	}
})(window.NGCPM_UI);
