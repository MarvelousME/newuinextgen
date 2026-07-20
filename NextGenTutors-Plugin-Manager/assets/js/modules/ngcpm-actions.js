/**
 * Plugin install/activate/scan actions and action router.
 */
(function (UI) {
	'use strict';

	UI.actions = {};

	UI.actions.beginBusy = function (button) {
		var btn = button || UI.state.pendingAjaxBtn || null;
		UI.feedback.setBusy(true, btn);
		UI.state.pendingAjaxBtn = null;
	};

	UI.actions.endBusy = function () {
		UI.actions.endBusy();
		UI.state.pendingAjaxBtn = null;
	};

	UI.actions.applyScanPayload = function (data) {
		if (!data) {
			return;
		}
		if (data.health) {
			UI.feedback.updateScore(data.health);
		}
	};

	UI.actions.handle = function (action, slug, extra) {
		extra = extra || {};
		if (extra.button) {
			UI.state.pendingAjaxBtn = extra.button;
		}
		switch (action) {
			case 'scan':
			case 'force-rescan':
				UI.actions.runScan(true);
				break;
			case 'refresh-status':
				UI.actions.runRefreshStatus();
				break;
			case 'install':
				if (slug) {
					UI.actions.runInstall(slug);
				}
				break;
			case 'install-missing':
				UI.actions.runInstallMissing();
				break;
			case 'activate':
				if (slug) {
					UI.actions.runActivate(slug);
				} else if (extra.plugin_file) {
					UI.actions.runManageInstalled('activate', extra.plugin_file, extra.wporg_slug || '');
				}
				break;
			case 'install-all':
				UI.queue.runInstallAll();
				break;
			case 'activate-all':
				UI.actions.runActivateAll();
				break;
			case 'install-activate-all':
				UI.queue.runFixAll();
				break;
			case 'run-sequential-queue':
				UI.queue.runInstall();
				break;
			case 'repair-one':
				if (slug) {
					UI.repair.runOne(slug, extra.strategy || 'install');
				}
				break;
			case 'repair-all':
				UI.repair.runAll();
				break;
			case 'refresh-diagnostics':
				UI.state.diagnosticsLoaded = false;
				UI.diagnostics.refresh(false);
				break;
			case 'verify-system':
				UI.actions.runVerifySystem();
				break;
			case 'cookie-probe':
				UI.notifications.runCookieProbe();
				break;
			case 'show-manual':
				if (slug) {
					UI.navigation.showManual(slug);
				}
				break;
			case 'export':
				UI.actions.exportReport();
				break;
			case 'export-logs':
				UI.actions.exportLogs();
				break;
			case 'clear-logs':
				UI.actions.clearLogs();
				break;
			case 'clear-cache':
				UI.actions.clearCache();
				break;
			case 'dismiss-optional':
				if (slug) { UI.actions.runDismissOptional(slug); }
				break;
			case 'restore-optional':
				if (slug) { UI.actions.runRestoreOptional(slug); }
				break;
			case 'deactivate':
				if (slug) {
					UI.actions.runDeactivate(slug);
				} else if (extra.plugin_file) {
					UI.actions.runManageInstalled('deactivate', extra.plugin_file, extra.wporg_slug || '');
				}
				break;
			case 'uninstall':
				if (slug) {
					UI.actions.runUninstall(slug);
				} else if (extra.plugin_file) {
					UI.actions.runManageInstalled('delete', extra.plugin_file, extra.wporg_slug || '');
				}
				break;
			case 'delete-installed':
				if (extra.plugin_file) {
					UI.actions.runManageInstalled('delete', extra.plugin_file, extra.wporg_slug || '');
				}
				break;
			case 'search-wporg':
				UI.actions.runWporgSearch(extra.term || '', extra.button);
				break;
			case 'install-wporg':
				if (extra.wporg_slug) { UI.actions.runInstallWporg(extra.wporg_slug); }
				break;
			case 'upload-plugin':
				UI.actions.runUploadPlugin(extra.form);
				break;
			case 'install-local-packages':
				UI.actions.runInstallLocalPackages();
				break;
			case 'open-add-plugin':
				UI.navigation.navigate('add-plugin');
				break;
			case 'open-queue':
				UI.queue.open();
				break;
			case 'close-queue':
				UI.queue.close();
				break;
			case 'start-tour':
				if (UI.tour) {
					UI.tour.start(true);
				}
				break;
			case 'command-palette':
				UI.command.open();
				break;
			case 'toggle-sidebar':
				if (UI.refs.sidebar && UI.refs.sidebar.classList.contains('is-open')) {
					UI.navigation.closeSidebar();
				} else {
					UI.navigation.openSidebar();
				}
				break;
			case 'toggle-drawer':
				UI.navigation.openSidebar();
				break;
			default:
				break;
		}
	};

	UI.actions.runScan = function (force) {
		UI.actions.beginBusy();
		UI.feedback.setProgress(20, NGCPM.i18n.scanning);
		var endpoint = force ? 'ngcpm_force_rescan' : 'ngcpm_scan';
		UI.post(endpoint).then(function (json) {
			if (json.success) {
				UI.actions.applyScanPayload(json.data);
				UI.feedback.showToast(NGCPM.i18n.done, 'success');
				window.location.reload();
			} else {
				UI.feedback.showToast((json.data && json.data.message) || NGCPM.i18n.error, 'error');
			}
		}).catch(function () {
			UI.feedback.showToast(NGCPM.i18n.networkError || NGCPM.i18n.error, 'error');
		}).finally(function () {
			UI.feedback.hideProgress();
			UI.actions.endBusy();
		});
	};

	UI.actions.runRefreshStatus = function () {
		UI.actions.beginBusy();
		UI.post('ngcpm_refresh_status').then(function (json) {
			if (json.success) {
				UI.actions.applyScanPayload(json.data);
				UI.feedback.showToast(NGCPM.i18n.done, 'success');
				window.location.reload();
			} else {
				UI.feedback.showToast((json.data && json.data.message) || NGCPM.i18n.error, 'error');
			}
		}).finally(function () {
			UI.actions.endBusy();
		});
	};

	UI.actions.runVerifySystem = function () {
		UI.actions.beginBusy();
		UI.feedback.setProgress(30, NGCPM.i18n.scanning);
		UI.post('ngcpm_verify_system').then(function (json) {
			if (json.success) {
				UI.actions.applyScanPayload(json.data);
				UI.feedback.showToast(NGCPM.i18n.done, 'success');
				UI.navigation.navigate('verification');
				window.location.reload();
			} else {
				UI.feedback.showToast((json.data && json.data.message) || NGCPM.i18n.error, 'error');
			}
		}).finally(function () {
			UI.feedback.hideProgress();
			UI.actions.endBusy();
		});
	};

	UI.actions.runInstallMissing = function () {
		if (!NGCPM.canInstall) {
			UI.feedback.showToast(NGCPM.i18n.error, 'error');
			return;
		}
		UI.actions.beginBusy();
		UI.feedback.setProgress(40, NGCPM.i18n.installing);
		UI.post('ngcpm_install_missing').then(function (json) {
			if (!json.success) {
				UI.feedback.showToast((json.data && json.data.message) || NGCPM.i18n.error, 'error');
				return;
			}
			UI.feedback.showToast((json.data && json.data.message) || NGCPM.i18n.done, 'success');
			window.location.reload();
		}).finally(function () {
			UI.feedback.hideProgress();
			UI.actions.endBusy();
		});
	};

	UI.actions.runInstallLocalPackages = function () {
		if (!NGCPM.canInstall) {
			UI.feedback.showToast(NGCPM.i18n.error, 'error');
			return;
		}
		UI.actions.beginBusy();
		UI.feedback.setProgress(30, NGCPM.i18n.installingLocal || NGCPM.i18n.installing);
		UI.post('ngcpm_install_local_packages').then(function (json) {
			if (!json.success) {
				UI.feedback.showToast((json.data && json.data.message) || NGCPM.i18n.error, 'error');
				return;
			}
			if (json.data && json.data.health) {
				UI.feedback.updateScore(json.data.health);
			}
			UI.feedback.showToast((json.data && json.data.message) || NGCPM.i18n.localInstallDone || NGCPM.i18n.done, 'success');
			window.location.reload();
		}).catch(function () {
			UI.feedback.showToast(NGCPM.i18n.networkError || NGCPM.i18n.error, 'error');
		}).finally(function () {
			UI.feedback.hideProgress();
			UI.actions.endBusy();
		});
	};

	UI.actions.runActivateAll = function () {
		if (!NGCPM.canActivate) {
			UI.feedback.showToast(NGCPM.i18n.error, 'error');
			return;
		}
		UI.actions.beginBusy();
		UI.post('ngcpm_activate_all').then(function (json) {
			if (!json.success) {
				UI.feedback.showToast((json.data && json.data.message) || NGCPM.i18n.error, 'error');
				return;
			}
			UI.feedback.showToast((json.data && json.data.message) || NGCPM.i18n.done, 'success');
			window.location.reload();
		}).finally(function () {
			UI.actions.endBusy();
		});
	};

	UI.actions.runInstall = function (slug) {
		if (!NGCPM.canInstall) {
			UI.feedback.showToast(NGCPM.i18n.error, 'error');
			return;
		}
		UI.actions.beginBusy();
		UI.feedback.setProgress(40, NGCPM.i18n.installing);
		var doInstall = UI.queue && UI.queue.installOne
			? UI.queue.installOne(slug, false)
			: UI.post('ngcpm_install', { slug: slug });
		doInstall.then(function (json) {
			if (json && json.cancelled) {
				UI.feedback.showToast(NGCPM.i18n.installCancelled || 'Install cancelled.', 'warning');
				return;
			}
			if (!UI.actionSucceeded(json)) {
				var msg = (json.data && json.data.message) || (json.data && json.data.result && json.data.result.message) || NGCPM.i18n.error;
				UI.feedback.showErrors([{ slug: slug, message: msg }]);
				UI.feedback.showToast(msg, 'error');
				return;
			}
			window.location.reload();
		}).catch(function () {
			UI.feedback.showToast(NGCPM.i18n.networkError || NGCPM.i18n.error, 'error');
		}).finally(function () {
			UI.feedback.hideProgress();
			UI.actions.endBusy();
		});
	};

	UI.actions.runActivate = function (slug) {
		if (!NGCPM.canActivate) {
			UI.feedback.showToast(NGCPM.i18n.error, 'error');
			return;
		}
		UI.actions.beginBusy();
		UI.feedback.setProgress(40, NGCPM.i18n.activating);
		UI.post('ngcpm_activate', { slug: slug }).then(function (json) {
			if (!UI.actionSucceeded(json)) {
				var msg = (json.data && json.data.message) || (json.data && json.data.result && json.data.result.message) || NGCPM.i18n.error;
				UI.feedback.showToast(msg, 'error');
				return;
			}
			window.location.reload();
		}).finally(function () {
			UI.feedback.hideProgress();
			UI.actions.endBusy();
		});
	};

	UI.actions.exportReport = function () {
		UI.actions.beginBusy();
		UI.post('ngcpm_export_report').then(function (json) {
			if (!json.success) {
				UI.feedback.showToast(NGCPM.i18n.error, 'error');
				return;
			}
			var blob = new Blob([JSON.stringify(json.data, null, 2)], { type: 'application/json' });
			var url = URL.createObjectURL(blob);
			var a = document.createElement('a');
			a.href = url;
			a.download = 'ngcpm-dependency-report.json';
			document.body.appendChild(a);
			a.click();
			a.remove();
			URL.revokeObjectURL(url);
			UI.feedback.showToast(NGCPM.i18n.done, 'success');
		}).finally(function () {
			UI.actions.endBusy();
		});
	};

	UI.actions.exportLogs = function () {
		UI.actions.beginBusy();
		UI.post('ngcpm_export_logs').then(function (json) {
			if (!json.success) {
				UI.feedback.showToast(NGCPM.i18n.error, 'error');
				return;
			}
			var blob = new Blob([JSON.stringify(json.data, null, 2)], { type: 'application/json' });
			var url = URL.createObjectURL(blob);
			var a = document.createElement('a');
			a.href = url;
			a.download = 'ngcpm-audit-logs.json';
			document.body.appendChild(a);
			a.click();
			a.remove();
			URL.revokeObjectURL(url);
			UI.feedback.showToast(NGCPM.i18n.done, 'success');
		}).finally(function () {
			UI.actions.endBusy();
		});
	};

	UI.actions.clearLogs = function () {
		if (!window.confirm(NGCPM.i18n.confirmClearLogs || 'Clear all audit logs?')) {
			return;
		}
		UI.actions.beginBusy();
		UI.post('ngcpm_clear_logs').then(function (json) {
			if (json.success) {
				UI.feedback.showToast((json.data && json.data.message) || NGCPM.i18n.done, 'success');
				window.location.reload();
			} else {
				UI.feedback.showToast((json.data && json.data.message) || NGCPM.i18n.error, 'error');
			}
		}).finally(function () {
			UI.actions.endBusy();
		});
	};

	UI.actions.clearCache = function () {
		UI.actions.beginBusy();
		UI.post('ngcpm_clear_cache').then(function (json) {
			if (json.success) {
				UI.feedback.showToast((json.data && json.data.message) || NGCPM.i18n.done, 'success');
				if (json.data && json.data.health) {
					UI.feedback.updateScore(json.data.health);
				}
			} else {
				UI.feedback.showToast((json.data && json.data.message) || NGCPM.i18n.error, 'error');
			}
		}).finally(function () {
			UI.actions.endBusy();
		});
	};

	UI.actions.runDismissOptional = function (slug) {
		UI.actions.beginBusy();
		UI.post('ngcpm_dismiss_optional', { slug: slug }).then(function (json) {
			if (!UI.actionSucceeded(json)) {
				UI.feedback.showToast((json.data && json.data.message) || NGCPM.i18n.error, 'error');
				return;
			}
			UI.feedback.showToast((json.data && json.data.message) || NGCPM.i18n.done, 'success');
			window.location.reload();
		}).finally(function () { UI.actions.endBusy(); });
	};

	UI.actions.runRestoreOptional = function (slug) {
		UI.actions.beginBusy();
		UI.post('ngcpm_restore_optional', { slug: slug }).then(function (json) {
			if (!UI.actionSucceeded(json)) {
				UI.feedback.showToast((json.data && json.data.message) || NGCPM.i18n.error, 'error');
				return;
			}
			window.location.reload();
		}).finally(function () {
			UI.actions.endBusy();
		});
	};

	UI.actions.runDeactivate = function (slug) {
		UI.actions.beginBusy();
		UI.post('ngcpm_deactivate', { slug: slug }).then(function (json) {
			if (!UI.actionSucceeded(json)) {
				UI.feedback.showToast((json.data && json.data.message) || NGCPM.i18n.error, 'error');
				return;
			}
			window.location.reload();
		}).finally(function () { UI.actions.endBusy(); });
	};

	UI.actions.runUninstall = function (slug) {
		var msg = (NGCPM.i18n && NGCPM.i18n.confirmUninstall) || 'Uninstall this optional plugin and remove its files?';
		if (!window.confirm(msg)) { return; }
		UI.actions.beginBusy();
		UI.post('ngcpm_uninstall', { slug: slug, confirm: '1' }).then(function (json) {
			if (!UI.actionSucceeded(json)) {
				UI.feedback.showToast((json.data && json.data.message) || NGCPM.i18n.error, 'error');
				return;
			}
			UI.feedback.showToast((json.data && json.data.message) || NGCPM.i18n.done, 'success');
			window.location.reload();
		}).finally(function () { UI.actions.endBusy(); });
	};

	UI.actions.runWporgSearch = function (term, button) {
		var resultsEl = document.querySelector('[data-wporg-results]');
		if (!resultsEl) { return; }
		if (button) {
			UI.state.pendingAjaxBtn = button;
		}
		UI.actions.beginBusy();
		resultsEl.textContent = NGCPM.i18n.searching || 'Searching…';
		UI.post('ngcpm_search_plugins', { term: term }).then(function (json) {
			resultsEl.textContent = '';
			if (!json.success || !json.data || !json.data.results) {
				resultsEl.textContent = (json.data && json.data.message) || NGCPM.i18n.error;
				return;
			}
			if (!json.data.results.length) {
				resultsEl.textContent = NGCPM.i18n.noResults || 'No plugins found.';
				return;
			}
			json.data.results.forEach(function (row) {
				var card = document.createElement('article');
				card.className = 'ngcpm-wporg-card';
				card.innerHTML = '<strong>' + row.name + '</strong><p>' + (row.description || '') + '</p>';
				var actions = document.createElement('div');
				actions.className = 'ngcpm-wporg-card__actions';
				if (!row.installed) {
					var btn = document.createElement('button');
					btn.type = 'button';
					btn.className = 'ngcpm-btn ngcpm-btn--sm ngcpm-btn--primary';
					btn.textContent = NGCPM.i18n.installFromOrg || 'Install';
					btn.setAttribute('data-action', 'install-wporg');
					btn.setAttribute('data-wporg-slug', row.slug);
					actions.appendChild(btn);
				} else {
					var tag = document.createElement('span');
					tag.className = 'ngcpm-badge ngcpm-badge--' + (row.active ? 'ready' : 'warning');
					tag.textContent = row.active ? 'Active' : 'Installed';
					actions.appendChild(tag);
					if (row.plugin_file) {
						if (!row.active) {
							var act = document.createElement('button');
							act.type = 'button';
							act.className = 'ngcpm-btn ngcpm-btn--sm ngcpm-btn--secondary';
							act.textContent = NGCPM.i18n.activate || 'Activate';
							act.setAttribute('data-action', 'activate');
							act.setAttribute('data-plugin-file', row.plugin_file);
							act.setAttribute('data-wporg-slug', row.slug);
							actions.appendChild(act);
						} else {
							var deact = document.createElement('button');
							deact.type = 'button';
							deact.className = 'ngcpm-btn ngcpm-btn--sm ngcpm-btn--ghost';
							deact.textContent = NGCPM.i18n.deactivate || 'Deactivate';
							deact.setAttribute('data-action', 'deactivate');
							deact.setAttribute('data-plugin-file', row.plugin_file);
							deact.setAttribute('data-wporg-slug', row.slug);
							actions.appendChild(deact);
						}
						var del = document.createElement('button');
						del.type = 'button';
						del.className = 'ngcpm-btn ngcpm-btn--sm ngcpm-btn--danger';
						del.textContent = NGCPM.i18n.delete || 'Delete';
						del.setAttribute('data-action', 'delete-installed');
						del.setAttribute('data-plugin-file', row.plugin_file);
						del.setAttribute('data-wporg-slug', row.slug);
						actions.appendChild(del);
					}
				}
				card.appendChild(actions);
				resultsEl.appendChild(card);
			});
		}).finally(function () {
			UI.actions.endBusy();
		});
	};

	UI.actions.runInstallWporg = function (wporgSlug) {
		UI.actions.beginBusy();
		UI.post('ngcpm_install_wporg', { wporg_slug: wporgSlug, activate: '1' }).then(function (json) {
			if (!json.success) {
				UI.feedback.showToast((json.data && json.data.message) || NGCPM.i18n.error, 'error');
				return;
			}
			UI.feedback.showToast((json.data && json.data.message) || NGCPM.i18n.done, 'success');
			window.location.reload();
		}).finally(function () { UI.actions.endBusy(); });
	};

	UI.actions.runUploadPlugin = function (form) {
		if (!form) { return; }
		var input = form.querySelector('input[type="file"]');
		if (!input || !input.files || !input.files[0]) {
			UI.feedback.showToast(NGCPM.i18n.pickZip || 'Choose a .zip file first.', 'error');
			return;
		}
		var fd = new FormData();
		fd.append('plugin_zip', input.files[0]);
		fd.append('activate', '1');
		UI.actions.beginBusy();
		UI.postForm('ngcpm_upload_plugin', fd).then(function (json) {
			if (!json.success) {
				UI.feedback.showToast((json.data && json.data.message) || NGCPM.i18n.error, 'error');
				return;
			}
			UI.feedback.showToast((json.data && json.data.message) || NGCPM.i18n.done, 'success');
			window.location.reload();
		}).finally(function () { UI.actions.endBusy(); });
	};

	/**
	 * Activate / deactivate / delete a non-registry plugin by plugin file.
	 *
	 * @param {string} op activate|deactivate|delete
	 * @param {string} pluginFile Relative plugin main file.
	 * @param {string} [label] Optional slug label.
	 */
	UI.actions.runManageInstalled = function (op, pluginFile, label) {
		if (!pluginFile) {
			return;
		}
		if (op === 'delete') {
			var msg = (NGCPM.i18n && NGCPM.i18n.confirmUninstall) || 'Delete this plugin and remove its files?';
			if (!window.confirm(msg)) {
				return;
			}
		}
		UI.actions.beginBusy();
		UI.post('ngcpm_manage_installed', {
			op: op,
			plugin_file: pluginFile,
			wporg_slug: label || '',
			confirm: op === 'delete' ? '1' : '',
		}).then(function (json) {
			if (!json.success) {
				UI.feedback.showToast((json.data && json.data.message) || NGCPM.i18n.error, 'error');
				return;
			}
			UI.feedback.showToast((json.data && json.data.message) || NGCPM.i18n.done, 'success');
			window.location.reload();
		}).finally(function () {
			UI.actions.endBusy();
		});
	};
})(window.NGCPM_UI);
