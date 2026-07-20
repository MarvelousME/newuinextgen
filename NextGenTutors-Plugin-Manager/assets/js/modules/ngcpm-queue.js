/**

 * Sequential install queue — full-page view (no overlapping drawer).

 */

(function (UI) {

	'use strict';



	UI.queue = {};



	UI.queue.fetchPlan = function () {

		return UI.post('ngcpm_queue_plan').then(function (json) {

			if (!json.success || !json.data) {

				return [];

			}

			return json.data.plan || [];

		});

	};



	UI.queue.getPageList = function () {

		return UI.refs.shell ? UI.refs.shell.querySelector('[data-queue-page-list]') : null;

	};



	UI.queue.renderItems = function (plan, container) {

		if (!container) {

			return 0;

		}

		container.textContent = '';

		var executable = 0;

		if (!plan.length) {

			var empty = document.createElement(container.tagName === 'UL' ? 'li' : 'p');

			empty.className = 'ngcpm-empty';

			empty.textContent = 'No plugins in queue.';

			container.appendChild(empty);

			return 0;

		}

		plan.forEach(function (item) {

			var wrap = document.createElement(container.tagName === 'UL' ? 'li' : 'div');

			wrap.className = container.tagName === 'UL' ? 'ngcpm-queue-page__item' : 'ngcpm-queue__item';

			wrap.setAttribute('data-queue-slug', item.slug);

			wrap.setAttribute('data-queue-action', item.action);

			var title = document.createElement('strong');

			title.textContent = item.name || item.slug;

			wrap.appendChild(title);

			var track = document.createElement('div');

			track.className = 'ngcpm-progress__track';

			track.style.marginTop = '8px';

			var bar = document.createElement('div');

			bar.className = 'ngcpm-progress__bar';

			bar.style.width = '0%';

			track.appendChild(bar);

			wrap.appendChild(track);

			var status = document.createElement('p');

			status.className = container.tagName === 'UL' ? 'ngcpm-queue-page__msg' : 'ngcpm-queue__status';

			status.textContent = item.action === 'manual' ? 'Manual required' : 'Queued';

			wrap.appendChild(status);

			container.appendChild(wrap);

			if (item.action === 'install' || item.action === 'activate') {

				executable++;

			}

		});

		return executable;

	};



	UI.queue.buildPreview = function () {

		UI.queue.fetchPlan().then(function (plan) {

			var pageList = UI.queue.getPageList();

			var count = 0;

			if (pageList) {

				count = UI.queue.renderItems(plan, pageList);

			}

			if (UI.refs.queueSummary) {

				UI.refs.queueSummary.textContent = count + ' plugin(s) ready to process';

			}

		});

	};



	UI.queue.markItem = function (slug, state, message, barWidth) {

		var el = UI.refs.shell ? UI.refs.shell.querySelector('[data-queue-slug="' + slug + '"]') : null;

		if (!el) {

			return;

		}

		el.classList.remove('is-running', 'is-success', 'is-failed');

		if (state) {

			el.classList.add(state);

		}

		var bar = el.querySelector('.ngcpm-progress__bar');

		if (bar && barWidth !== undefined) {

			bar.style.width = barWidth + '%';

		}

		var status = el.querySelector('.ngcpm-queue__status, .ngcpm-queue-page__msg');

		if (status && message) {

			status.textContent = message;

		}

	};



	UI.queue.errorMessage = function (json, fallback) {

		return (json && json.data && json.data.message)

			|| (json && json.data && json.data.result && json.data.result.message)

			|| fallback

			|| 'Failed';

	};



	UI.queue.installOne = function (slug, overwrite) {

		return UI.post('ngcpm_install', { slug: slug, overwrite: overwrite ? '1' : '0' }).then(function (json) {

			if (!UI.actionSucceeded(json) && UI.modal && UI.modal.isFolderExists(json) && !overwrite) {

				return UI.modal.promptOverwriteInstall(slug, json);

			}

			return json;

		});

	};



	UI.queue.runSequential = function (plan, onComplete) {

		var items = (plan || []).filter(function (item) {

			return item.action === 'install' || item.action === 'activate';

		});

		if (!items.length) {

			UI.feedback.showToast(NGCPM.i18n.error, 'error');

			if (onComplete) {

				onComplete([]);

			}

			return;

		}



		var index = 0;

		var failures = [];

		var totalSteps = 0;

		items.forEach(function (item) {

			totalSteps += item.action === 'install' ? 2 : 1;

		});

		var step = 0;



		function next() {

			if (index >= items.length) {

				var summary = !failures.length
					? (NGCPM.i18n.queueDone || 'Installation finished')
					: (failures.length === 1 ? failures[0].message : failures.length + ' plugin(s) failed.');

				UI.feedback.setProgress(100, summary, failures.length === 0);

				UI.feedback.showErrors(failures);

				UI.feedback.showToast(summary, failures.length ? 'error' : 'success');

				UI.actions.endBusy();

				if (!failures.length) {

					window.setTimeout(function () {

						window.location.reload();

					}, 800);

				}

				if (onComplete) {

					onComplete(failures);

				}

				return;

			}



			var item = items[index];

			var slug = item.slug;

			UI.queue.markItem(slug, 'is-running', item.action === 'install' ? (NGCPM.i18n.installing || 'Installing…') : (NGCPM.i18n.activating || 'Activating…'), 10);

			UI.feedback.setProgress(Math.round((step / totalSteps) * 100), NGCPM.i18n.sequential || 'Processing…');



			if (item.action === 'activate') {

				UI.post('ngcpm_activate', { slug: slug }).then(function (json) {

					step++;

					if (UI.actionSucceeded(json)) {

						UI.queue.markItem(slug, 'is-success', 'Active', 100);

					} else {

						UI.queue.markItem(slug, 'is-failed', 'Failed', 100);

						failures.push({

							slug: slug,

							message: (json.data && json.data.message) || (json.data && json.data.result && json.data.result.message) || 'Activate failed',

						});

					}

					index++;

					next();

				}).catch(function () {

					failures.push({ slug: slug, message: 'Network error' });

					index++;

					next();

				});

				return;

			}



			UI.queue.installOne(slug, false).then(function (json) {

				if (json && json.cancelled) {

					UI.queue.markItem(slug, '', NGCPM.i18n.installCancelled || 'Cancelled', 0);

					index++;

					next();

					return;

				}

				step++;

				if (!UI.actionSucceeded(json)) {

					var installMsg = UI.queue.errorMessage(json, 'Install failed');

					UI.queue.markItem(slug, 'is-failed', installMsg, 50);

					failures.push({ slug: slug, message: installMsg });

					index++;

					next();

					return;

				}

				if (item.optional) {

					UI.queue.markItem(slug, 'is-success', 'Installed — activate manually', 100);

					index++;

					next();

					return;

				}

				UI.queue.markItem(slug, 'is-running', NGCPM.i18n.activating || 'Activating…', 60);

				return UI.post('ngcpm_activate', { slug: slug });

			}).then(function (actJson) {

				if (!actJson) {

					return;

				}

				step++;

				if (UI.actionSucceeded(actJson)) {

					UI.queue.markItem(slug, 'is-success', 'Complete', 100);

				} else {

					var activateMsg = UI.queue.errorMessage(actJson, 'Activate failed');

					UI.queue.markItem(slug, 'is-failed', activateMsg, 80);

					failures.push({ slug: slug, message: activateMsg });

				}

				index++;

				next();

			}).catch(function () {

				failures.push({ slug: slug, message: 'Network error' });

				index++;

				next();

			});

		}



		UI.actions.beginBusy();

		next();

	};



	UI.queue.open = function () {

		UI.navigation.navigate('queue');

		UI.queue.buildPreview();

	};



	UI.queue.close = function () {

		UI.navigation.navigate('dashboard');

	};



	UI.queue.runInstall = function () {

		UI.queue.fetchPlan().then(function (plan) {

			var pageList = UI.queue.getPageList();

			if (pageList) {

				UI.queue.renderItems(plan, pageList);

			}

			UI.queue.runSequential(plan);

		});

	};



	UI.queue.runFixAll = function () {

		if (UI.isBusy()) {

			return;

		}

		if (!window.confirm(NGCPM.i18n.confirmBatch)) {

			return;

		}

		UI.queue.open();

		UI.queue.runInstall();

	};



	UI.queue.runInstallAll = function () {

		if (UI.isBusy()) {

			return;

		}

		UI.queue.fetchPlan().then(function (plan) {

			var installOnly = plan.filter(function (item) {

				return item.action === 'install';

			});

			UI.queue.open();

			var pageList = UI.queue.getPageList();

			if (pageList) {

				UI.queue.renderItems(plan, pageList);

			}

			UI.queue.runSequential(installOnly);

		});

	};



	UI.queue.runActivateAll = function () {

		if (UI.isBusy()) {

			return;

		}

		UI.queue.fetchPlan().then(function (plan) {

			var activateOnly = plan.filter(function (item) {

				return item.action === 'activate';

			});

			UI.queue.runSequential(activateOnly);

		});

	};

})(window.NGCPM_UI);

