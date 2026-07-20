/**
 * Guided setup tour — install, configure, seed, verify sequence.
 * Knowledge card docks beside the highlighted control for each step.
 */
(function (UI) {
	'use strict';

	UI.tour = {};
	UI.tour.STORAGE_KEY = 'ngcpm_tour_completed_v1';
	UI.tour._repositionBound = null;

	UI.tour.steps = function () {
		return [
			{
				title: 'Welcome to NextGenTutors',
				body: 'This guided tour walks you through setup in order: discover plugins, install, activate, configure, seed data, and verify your stack. Each card sits next to the control it describes.',
				nav: 'dashboard',
				selector: '.ngcpm-hero',
				placement: 'bottom',
			},
			{
				title: 'Step 1 — Scan your site',
				body: 'Use this Scan control to detect which required plugins are installed, active, or missing. Run Rescan after you change plugins so the readiness score stays accurate.',
				nav: 'dashboard',
				selector: '.ngcpm-hero [data-action="scan"], [data-action="scan"]',
				placement: 'bottom',
			},
			{
				title: 'Step 2 — Review missing plugins',
				body: 'This Missing view lists plugins that still need attention. Premium or manual plugins show clear instructions — never raw error dumps.',
				nav: 'missing',
				selector: '#ngcpm-view-missing .ngcpm-page-head, #ngcpm-view-missing',
				placement: 'bottom',
			},
			{
				title: 'Step 3 — Install queue',
				body: 'This Install Queue installs and activates plugins one at a time. If a folder already exists, you will be asked before overwriting.',
				nav: 'queue',
				selector: '#ngcpm-view-queue .ngcpm-page-head, #ngcpm-view-queue [data-action="run-sequential-queue"], #ngcpm-view-queue',
				placement: 'bottom',
			},
			{
				title: 'Step 4 — Activate plugins',
				body: 'Activation Manager lists installed plugins that are not yet active. Use Activate all here, or activate one plugin at a time from this list.',
				nav: 'activation',
				selector: '#ngcpm-view-activation .ngcpm-page-head, #ngcpm-view-activation [data-action="activate-all"], #ngcpm-view-activation',
				placement: 'bottom',
			},
			{
				title: 'Step 5 — Configure integrations',
				body: 'These configuration links open each plugin setup screen (FluentCRM, WooCommerce, Amelia, and others). Complete them after required plugins are active.',
				nav: 'configuration',
				selector: '#ngcpm-view-configuration .ngcpm-page-head, #ngcpm-view-configuration',
				placement: 'bottom',
			},
			{
				title: 'Step 6 — Companion seeding',
				body: 'After NextGenTutors-Companion is active, open its admin to seed tutors (optional), bootstrap CRM lists/tags, and import WooCommerce products.',
				nav: 'configuration',
				selector: '#ngcpm-view-configuration .ngcpm-page-head, #ngcpm-view-configuration',
				placement: 'bottom',
				extra: (NGCPM.companionUrl)
					? '<a class="ngcpm-btn ngcpm-btn--sm ngcpm-btn--secondary" href="' + NGCPM.companionUrl + '">Open Companion admin</a>'
					: '',
			},
			{
				title: 'Step 7 — System health',
				body: 'System Health groups checks by category (plugins, payments, mail, cookies). Run health probes from this screen anytime.',
				nav: 'health',
				selector: '#ngcpm-view-health .ngcpm-page-head, #ngcpm-view-health [data-action="refresh-diagnostics"], #ngcpm-view-health',
				placement: 'bottom',
			},
			{
				title: 'Step 8 — Verification',
				body: 'Verification Center compares expected vs actual behaviour. Use Run all on this screen before go-live.',
				nav: 'verification',
				selector: '#ngcpm-view-verification .ngcpm-page-head, #ngcpm-view-verification [data-action="verify-system"], #ngcpm-view-verification',
				placement: 'bottom',
			},
			{
				title: 'Step 9 — System Readiness',
				body: 'When deployment gates pass, export your readiness report from this area. Aim for READY status before production.',
				nav: 'readiness',
				selector: '.ngcpm-readiness-hero, #ngcpm-view-readiness .ngcpm-page-head, #ngcpm-view-readiness',
				placement: 'bottom',
			},
			{
				title: 'You are ready',
				body: 'Tour complete. Reopen this guide anytime with this help control. Need the checklist again? Visit Readiness on the sidebar.',
				nav: 'dashboard',
				selector: '.ngcpm-tour-launch',
				placement: 'left',
			},
		];
	};

	UI.tour.resolveTarget = function (selector) {
		if (!selector || !UI.refs.shell) {
			return null;
		}
		var parts = String(selector).split(',');
		var i;
		for (i = 0; i < parts.length; i++) {
			var sel = parts[i].trim();
			if (!sel) {
				continue;
			}
			var el = UI.refs.shell.querySelector(sel);
			if (!el) {
				continue;
			}
			// Fixed controls (tour launch) may have null offsetParent but still be visible.
			if (el.offsetParent !== null || el.getClientRects().length) {
				return el;
			}
			return el;
		}
		return null;
	};

	/**
	 * Place knowledge card beside the highlighted control.
	 *
	 * @param {HTMLElement|null} target Highlighted control.
	 * @param {string} [preferred] preferred|top|bottom|left|right
	 */
	UI.tour.positionCard = function (target, preferred) {
		var card = UI.refs.tourEl ? UI.refs.tourEl.querySelector('.ngcpm-tour__card') : null;
		var shell = UI.refs.shell;
		if (!card || !shell) {
			return;
		}

		card.classList.remove(
			'is-placement-top',
			'is-placement-bottom',
			'is-placement-left',
			'is-placement-right',
			'is-anchored'
		);

		if (!target) {
			card.style.top = '';
			card.style.left = '';
			card.style.right = 'var(--space-4)';
			card.style.bottom = 'var(--space-4)';
			card.style.transform = '';
			return;
		}

		card.style.right = 'auto';
		card.style.bottom = 'auto';
		card.style.transform = '';

		var gap = 14;
		var pad = 12;
		var shellRect = shell.getBoundingClientRect();
		var rect = target.getBoundingClientRect();
		var cardRect = card.getBoundingClientRect();
		var cardW = cardRect.width || Math.min(360, shellRect.width - pad * 2);
		var cardH = cardRect.height || 180;

		var space = {
			top: rect.top - shellRect.top,
			bottom: shellRect.bottom - rect.bottom,
			left: rect.left - shellRect.left,
			right: shellRect.right - rect.right,
		};

		var order = [preferred || 'bottom', 'right', 'left', 'top', 'bottom'];
		var seen = {};
		var placement = 'bottom';
		var i;
		for (i = 0; i < order.length; i++) {
			var p = order[i];
			if (!p || seen[p]) {
				continue;
			}
			seen[p] = true;
			if (p === 'bottom' && space.bottom >= cardH + gap + pad) {
				placement = 'bottom';
				break;
			}
			if (p === 'top' && space.top >= cardH + gap + pad) {
				placement = 'top';
				break;
			}
			if (p === 'right' && space.right >= cardW + gap + pad) {
				placement = 'right';
				break;
			}
			if (p === 'left' && space.left >= cardW + gap + pad) {
				placement = 'left';
				break;
			}
		}

		// Fallback: place where the most room exists.
		if (!seen[placement] || (placement === 'bottom' && space.bottom < 80 && space.top > space.bottom)) {
			var best = 'bottom';
			var bestVal = space.bottom;
			['top', 'left', 'right'].forEach(function (key) {
				if (space[key] > bestVal) {
					bestVal = space[key];
					best = key;
				}
			});
			placement = best;
		}

		var top = 0;
		var left = 0;
		var targetMidX = rect.left - shellRect.left + rect.width / 2;
		var targetMidY = rect.top - shellRect.top + rect.height / 2;

		if (placement === 'bottom') {
			top = rect.bottom - shellRect.top + gap;
			left = targetMidX - cardW / 2;
		} else if (placement === 'top') {
			top = rect.top - shellRect.top - cardH - gap;
			left = targetMidX - cardW / 2;
		} else if (placement === 'right') {
			top = targetMidY - cardH / 2;
			left = rect.right - shellRect.left + gap;
		} else {
			top = targetMidY - cardH / 2;
			left = rect.left - shellRect.left - cardW - gap;
		}

		left = Math.max(pad, Math.min(left, shellRect.width - cardW - pad));
		top = Math.max(pad, Math.min(top, shellRect.height - cardH - pad));

		card.style.top = Math.round(top) + 'px';
		card.style.left = Math.round(left) + 'px';
		card.classList.add('is-anchored', 'is-placement-' + placement);
	};

	UI.tour.positionSpotlight = function (target) {
		if (!UI.refs.tourSpotlight || !UI.refs.shell) {
			return;
		}
		if (!target) {
			UI.refs.tourSpotlight.hidden = true;
			return;
		}
		var rect = target.getBoundingClientRect();
		var shellRect = UI.refs.shell.getBoundingClientRect();
		UI.refs.tourSpotlight.hidden = false;
		UI.refs.tourSpotlight.style.top = (rect.top - shellRect.top - 8) + 'px';
		UI.refs.tourSpotlight.style.left = (rect.left - shellRect.left - 8) + 'px';
		UI.refs.tourSpotlight.style.width = (rect.width + 16) + 'px';
		UI.refs.tourSpotlight.style.height = (rect.height + 16) + 'px';
	};

	UI.tour.focusStep = function (step) {
		var target = UI.tour.resolveTarget(step && step.selector);
		UI.tour._activeTarget = target || null;

		if (target && typeof target.scrollIntoView === 'function') {
			target.scrollIntoView({ block: 'center', inline: 'nearest', behavior: 'smooth' });
		}

		window.setTimeout(function () {
			UI.tour.positionSpotlight(UI.tour._activeTarget);
			UI.tour.positionCard(UI.tour._activeTarget, step && step.placement);
			// Second pass after layout settles (fonts / scroll).
			window.setTimeout(function () {
				UI.tour.positionSpotlight(UI.tour._activeTarget);
				UI.tour.positionCard(UI.tour._activeTarget, step && step.placement);
			}, 180);
		}, 140);
	};

	UI.tour.bindReposition = function () {
		if (UI.tour._repositionBound) {
			return;
		}
		UI.tour._repositionBound = function () {
			if (!UI.refs.tourEl || UI.refs.tourEl.hidden) {
				return;
			}
			var steps = UI.tour.steps();
			var step = steps[UI.tour.index];
			if (!step) {
				return;
			}
			var target = UI.tour._activeTarget || UI.tour.resolveTarget(step.selector);
			UI.tour.positionSpotlight(target);
			UI.tour.positionCard(target, step.placement);
		};
		window.addEventListener('resize', UI.tour._repositionBound);
		if (UI.refs.shell) {
			UI.refs.shell.addEventListener('scroll', UI.tour._repositionBound, true);
		}
	};

	UI.tour.unbindReposition = function () {
		if (!UI.tour._repositionBound) {
			return;
		}
		window.removeEventListener('resize', UI.tour._repositionBound);
		if (UI.refs.shell) {
			UI.refs.shell.removeEventListener('scroll', UI.tour._repositionBound, true);
		}
		UI.tour._repositionBound = null;
	};

	UI.tour.ensure = function () {
		if (UI.refs.tourEl) {
			return;
		}
		var shell = UI.refs.shell;
		if (!shell) {
			return;
		}
		var el = document.createElement('div');
		el.id = 'ngcpm-tour';
		el.className = 'ngcpm-tour';
		el.hidden = true;
		el.innerHTML =
			'<div class="ngcpm-tour__spotlight" data-tour-spotlight hidden></div>' +
			'<article class="ngcpm-tour__card" role="dialog" aria-modal="false" aria-labelledby="ngcpm-tour-title">' +
			'<div class="ngcpm-tour__caret" aria-hidden="true"></div>' +
			'<header class="ngcpm-tour__head">' +
			'<span class="ngcpm-tour__step" data-tour-step-label></span>' +
			'<button type="button" class="ngcpm-btn ngcpm-btn--icon ngcpm-tour__skip" data-tour-skip aria-label="Skip tour">×</button>' +
			'</header>' +
			'<h2 id="ngcpm-tour-title" class="ngcpm-tour__title" data-tour-title></h2>' +
			'<p class="ngcpm-tour__body" data-tour-body></p>' +
			'<div class="ngcpm-tour__extra" data-tour-extra></div>' +
			'<footer class="ngcpm-tour__foot">' +
			'<button type="button" class="ngcpm-btn ngcpm-btn--ghost ngcpm-btn--sm" data-tour-back>Back</button>' +
			'<button type="button" class="ngcpm-btn ngcpm-btn--primary ngcpm-btn--sm" data-tour-next>Next</button>' +
			'</footer>' +
			'</article>';
		shell.appendChild(el);
		UI.refs.tourEl = el;
		UI.refs.tourSpotlight = el.querySelector('[data-tour-spotlight]');
		UI.refs.tourTitle = el.querySelector('[data-tour-title]');
		UI.refs.tourBody = el.querySelector('[data-tour-body]');
		UI.refs.tourExtra = el.querySelector('[data-tour-extra]');
		UI.refs.tourStepLabel = el.querySelector('[data-tour-step-label]');
		UI.refs.tourNext = el.querySelector('[data-tour-next]');
		UI.refs.tourBack = el.querySelector('[data-tour-back]');
		UI.refs.tourSkip = el.querySelector('[data-tour-skip]');

		UI.refs.tourNext.addEventListener('click', function () {
			UI.tour.next();
		});
		UI.refs.tourBack.addEventListener('click', function () {
			UI.tour.back();
		});
		UI.refs.tourSkip.addEventListener('click', function () {
			UI.tour.finish(true);
		});
	};

	UI.tour.render = function () {
		var steps = UI.tour.steps();
		var step = steps[UI.tour.index];
		if (!step || !UI.refs.tourEl) {
			return;
		}
		UI.refs.tourTitle.textContent = step.title;
		UI.refs.tourBody.textContent = step.body;
		UI.refs.tourStepLabel.textContent = 'Step ' + (UI.tour.index + 1) + ' of ' + steps.length;
		UI.refs.tourExtra.innerHTML = step.extra || '';
		UI.refs.tourBack.disabled = UI.tour.index === 0;
		UI.refs.tourNext.textContent = UI.tour.index >= steps.length - 1
			? ((NGCPM.i18n && NGCPM.i18n.tourFinish) || 'Finish')
			: ((NGCPM.i18n && NGCPM.i18n.tourNext) || 'Next');

		if (step.nav) {
			UI.navigation.navigate(step.nav);
		}
		UI.tour.focusStep(step);
	};

	UI.tour.start = function (force) {
		UI.tour.ensure();
		UI.tour.bindReposition();
		UI.tour.index = 0;
		if (UI.refs.tourEl) {
			UI.refs.tourEl.hidden = false;
		}
		UI.tour.render();
		if (force) {
			try {
				localStorage.removeItem(UI.tour.STORAGE_KEY);
			} catch (e) { /* ignore */ }
		}
	};

	UI.tour.next = function () {
		var steps = UI.tour.steps();
		if (UI.tour.index >= steps.length - 1) {
			UI.tour.finish(false);
			return;
		}
		UI.tour.index += 1;
		UI.tour.render();
	};

	UI.tour.back = function () {
		if (UI.tour.index > 0) {
			UI.tour.index -= 1;
			UI.tour.render();
		}
	};

	UI.tour.finish = function (skipped) {
		UI.tour.unbindReposition();
		UI.tour._activeTarget = null;
		if (UI.refs.tourEl) {
			UI.refs.tourEl.hidden = true;
		}
		if (UI.refs.tourSpotlight) {
			UI.refs.tourSpotlight.hidden = true;
		}
		try {
			localStorage.setItem(UI.tour.STORAGE_KEY, skipped ? 'skipped' : 'done');
		} catch (e) { /* ignore */ }
		if (!skipped && UI.feedback) {
			UI.feedback.showToast((NGCPM.i18n && NGCPM.i18n.tourDone) || 'Setup tour complete', 'success');
		}
	};

	UI.tour.maybeAutoStart = function () {
		if (UI.refs.readonly) {
			return;
		}
		try {
			if (localStorage.getItem(UI.tour.STORAGE_KEY)) {
				return;
			}
		} catch (e) {
			return;
		}
		window.setTimeout(function () {
			UI.tour.start(false);
		}, 600);
	};
})(window.NGCPM_UI);
