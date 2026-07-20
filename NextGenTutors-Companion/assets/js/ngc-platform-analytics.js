/**
 * Platform Analytics Dashboard — Chart.js + realtime REST refresh.
 */
(function ($) {
	'use strict';

	var cfg = window.ngcPlatformAnalytics || {};
	var state = {
		charts: {},
		data: null,
		timer: null,
		live: true,
		pollMs: 30000,
		drill: null,
	};

	var COLORS = [
		'#0f4c81', '#1d6fb8', '#3d8fd1', '#5dade2',
		'#0e8a5f', '#2a9d8f', '#e9a319', '#d97706',
		'#c0392b', '#8e44ad', '#566573', '#1abc9c'
	];

	function restGet(fresh) {
		var url = cfg.restUrl || '';
		var params = {};
		if (cfg.demo) {
			params.demo = '1';
		}
		if (fresh) {
			params.fresh = '1';
		}
		return $.ajax({
			url: url,
			data: params,
			headers: { 'X-WP-Nonce': cfg.nonce || '' },
		});
	}

	function unwrap(payload) {
		if (!payload) {
			return {};
		}
		if (payload.data && typeof payload.data === 'object' && !Array.isArray(payload.data)) {
			// Envelope { success, data, meta }
			if (payload.meta || typeof payload.success !== 'undefined') {
				return payload.data;
			}
		}
		return payload;
	}

	function num(v) {
		var n = parseFloat(v);
		return isFinite(n) ? n : 0;
	}

	function labelize(key) {
		return String(key || '')
			.replace(/_/g, ' ')
			.replace(/\b\w/g, function (c) { return c.toUpperCase(); });
	}

	function objectToPairs(obj) {
		if (!obj) {
			return [];
		}
		if (Array.isArray(obj)) {
			return obj.map(function (row, i) {
				if (row && typeof row === 'object') {
					var k = row.label || row.id || row.affiliate_id || ('Item ' + (i + 1));
					var v = row.count != null ? row.count : (row.value != null ? row.value : 1);
					return { label: String(k), value: num(v) };
				}
				return { label: String(row), value: 1 };
			});
		}
		return Object.keys(obj).map(function (k) {
			return { label: String(k), value: num(obj[k]) };
		});
	}

	function destroyChart(key) {
		if (state.charts[key]) {
			state.charts[key].destroy();
			delete state.charts[key];
		}
	}

	function ensureChart(key, canvas, config) {
		if (!canvas || typeof Chart === 'undefined') {
			return null;
		}
		destroyChart(key);
		state.charts[key] = new Chart(canvas, config);
		return state.charts[key];
	}

	function baseOptions(extra) {
		return $.extend(true, {
			responsive: true,
			maintainAspectRatio: false,
			plugins: {
				legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } },
				tooltip: { mode: 'nearest', intersect: true },
			},
			onClick: function (evt, elements) {
				if (!elements || !elements.length) {
					return;
				}
				var chart = this;
				var idx = elements[0].index;
				var label = chart.data.labels[idx];
				var value = chart.data.datasets[0].data[idx];
				var drillKey = chart.canvas.getAttribute('data-drill');
				showDrilldown(drillKey || chart.canvas.id, label, value, chart);
			},
		}, extra || {});
	}

	function doughnutConfig(labels, values, colors) {
		return {
			type: 'doughnut',
			data: {
				labels: labels,
				datasets: [{
					data: values,
					backgroundColor: colors || COLORS.slice(0, labels.length),
					borderWidth: 0,
				}],
			},
			options: baseOptions({
				cutout: '58%',
			}),
		};
	}

	function barConfig(labels, values, horizontal) {
		return {
			type: 'bar',
			data: {
				labels: labels,
				datasets: [{
					label: 'Count',
					data: values,
					backgroundColor: COLORS.slice(0, Math.max(labels.length, 1)),
					borderRadius: 4,
					maxBarThickness: 36,
				}],
			},
			options: baseOptions({
				indexAxis: horizontal ? 'y' : 'x',
				scales: {
					x: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: 'rgba(0,0,0,0.06)' } },
					y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: 'rgba(0,0,0,0.06)' } },
				},
				plugins: { legend: { display: false } },
			}),
		};
	}

	function renderKpis(data) {
		var items = [
			{ key: 'total_users', label: 'Users', format: 'int' },
			{ key: 'total_tutors', label: 'Tutors', format: 'int' },
			{ key: 'active_bookings', label: 'Active bookings', format: 'int' },
			{ key: 'completed_lessons', label: 'Completed lessons', format: 'int' },
			{ key: 'revenue', label: 'Revenue', format: 'money' },
			{ key: 'average_tutor_rating', label: 'Avg rating', format: 'rating' },
			{ key: 'conversion_rate', label: 'Conversion', format: 'pct' },
			{ key: 'session_count', label: 'Sessions', format: 'int' },
		];
		var $grid = $('#ngc-pa-kpis').empty();
		items.forEach(function (item) {
			var raw = num(data[item.key]);
			var display = String(raw);
			if (item.format === 'money') {
				display = (cfg.currency || 'R') + ' ' + raw.toLocaleString(undefined, { maximumFractionDigits: 2 });
			} else if (item.format === 'rating') {
				display = raw.toFixed(1) + ' / 5';
			} else if (item.format === 'pct') {
				display = raw.toFixed(1) + '%';
			} else {
				display = Math.round(raw).toLocaleString();
			}
			var $card = $('<button type="button" class="ngc-pa-kpi" data-kpi="' + item.key + '"></button>');
			$card.append($('<span class="ngc-pa-kpi__label"></span>').text(item.label));
			$card.append($('<span class="ngc-pa-kpi__value"></span>').text(display));
			$card.on('click', function () {
				showDrilldown('kpi', item.label, raw, null, flattenMetric(data, item.key));
			});
			$grid.append($card);
		});
	}

	function flattenMetric(data, key) {
		var rows = [];
		if (key === 'total_users' || key.indexOf('total_') === 0) {
			rows = [
				{ label: 'Parents', value: num(data.total_parents) },
				{ label: 'Students', value: num(data.total_students) },
				{ label: 'Tutors', value: num(data.total_tutors) },
				{ label: 'New (month)', value: num(data.new_users) },
				{ label: 'Returning', value: num(data.returning_users) },
			];
		} else if (key === 'active_bookings' || key === 'completed_lessons') {
			rows = [
				{ label: 'Active', value: num(data.active_bookings) },
				{ label: 'Completed', value: num(data.completed_lessons) },
				{ label: 'Cancelled', value: num(data.cancelled_lessons) },
			];
		} else if (key === 'revenue') {
			rows = [
				{ label: 'Revenue (paid)', value: num(data.revenue) },
				{ label: 'Paid invoices', value: num(data.paid_invoices) },
				{ label: 'Pending payments', value: num(data.pending_payments) },
				{ label: 'Failed payments', value: num(data.failed_payments) },
				{ label: 'Refunds', value: num(data.refunds) },
				{ label: 'Wallet balances', value: num(data.wallet_balances) },
				{ label: 'Tutor payouts', value: num(data.tutor_payouts) },
			];
		}
		return rows;
	}

	function paintPairsChart(canvasId, chartKey, pairs, type, horizontal) {
		var canvas = document.getElementById(canvasId);
		if (!canvas) {
			return;
		}
		var labels = pairs.map(function (p) { return p.label; });
		var values = pairs.map(function (p) { return p.value; });
		if (!labels.length) {
			labels = ['No data'];
			values = [0];
		}
		var config = type === 'doughnut'
			? doughnutConfig(labels, values)
			: barConfig(labels, values, !!horizontal);
		ensureChart(chartKey, canvas, config);
	}

	function renderCharts(data) {
		paintPairsChart('ngc-pa-chart-audience', 'audience', [
			{ label: 'Parents', value: num(data.total_parents) },
			{ label: 'Students', value: num(data.total_students) },
			{ label: 'Tutors', value: num(data.total_tutors) },
		], 'doughnut');

		paintPairsChart('ngc-pa-chart-pipeline', 'pipeline', [
			{ label: 'Applicants', value: num(data.tutor_applicants) },
			{ label: 'Approved', value: num(data.approved_tutors) },
			{ label: 'Rejected', value: num(data.rejected_tutors) },
		], 'bar');

		paintPairsChart('ngc-pa-chart-lessons', 'lessons', [
			{ label: 'Active', value: num(data.active_bookings) },
			{ label: 'Completed', value: num(data.completed_lessons) },
			{ label: 'Cancelled', value: num(data.cancelled_lessons) },
		], 'bar');

		paintPairsChart('ngc-pa-chart-payments', 'payments', [
			{ label: 'Paid', value: num(data.paid_invoices) },
			{ label: 'Pending', value: num(data.pending_payments) },
			{ label: 'Failed', value: num(data.failed_payments) },
			{ label: 'Refunds', value: num(data.refunds) },
		], 'doughnut');

		var funnel = objectToPairs(data.funnel_drop_off || {});
		paintPairsChart('ngc-pa-chart-funnel', 'funnel', funnel, 'bar', true);

		paintPairsChart('ngc-pa-chart-sources', 'sources', objectToPairs(data.lead_source_performance), 'bar');
		paintPairsChart('ngc-pa-chart-campaigns', 'campaigns', objectToPairs(data.query_string_performance), 'bar');
		paintPairsChart('ngc-pa-chart-devices', 'devices', objectToPairs(data.device_breakdown), 'doughnut');
		paintPairsChart('ngc-pa-chart-browsers', 'browsers', objectToPairs(data.browser_breakdown), 'doughnut');
		paintPairsChart('ngc-pa-chart-locations', 'locations', objectToPairs(data.location_breakdown), 'bar', true);
		paintPairsChart('ngc-pa-chart-affiliates', 'affiliates', objectToPairs(data.affiliate_performance), 'bar');
	}

	function showDrilldown(source, label, value, chart, rows) {
		state.drill = { source: source, label: label, value: value };
		var $panel = $('#ngc-pa-drill');
		var $title = $('#ngc-pa-drill-title');
		var $meta = $('#ngc-pa-drill-meta');
		var $list = $('#ngc-pa-drill-list').empty();

		$title.text(labelize(source.replace(/^ngc-pa-chart-/, '')) + ' → ' + label);
		$meta.text('Value: ' + (typeof value === 'number' ? value.toLocaleString() : value));

		var detailRows = rows && rows.length ? rows : [];
		if (!detailRows.length && state.data) {
			var map = {
				'ngc-pa-chart-audience': flattenMetric(state.data, 'total_users'),
				'ngc-pa-chart-pipeline': [
					{ label: 'Applicants', value: num(state.data.tutor_applicants) },
					{ label: 'Approved', value: num(state.data.approved_tutors) },
					{ label: 'Rejected', value: num(state.data.rejected_tutors) },
				],
				'ngc-pa-chart-lessons': flattenMetric(state.data, 'active_bookings'),
				'ngc-pa-chart-payments': flattenMetric(state.data, 'revenue'),
				'ngc-pa-chart-funnel': objectToPairs(state.data.funnel_drop_off),
				'ngc-pa-chart-sources': objectToPairs(state.data.lead_source_performance),
				'ngc-pa-chart-campaigns': objectToPairs(state.data.query_string_performance),
				'ngc-pa-chart-devices': objectToPairs(state.data.device_breakdown),
				'ngc-pa-chart-browsers': objectToPairs(state.data.browser_breakdown),
				'ngc-pa-chart-locations': objectToPairs(state.data.location_breakdown),
				'ngc-pa-chart-affiliates': objectToPairs(state.data.affiliate_performance),
			};
			detailRows = map[source] || [{ label: label, value: value }];
		}

		detailRows.forEach(function (row) {
			var active = String(row.label) === String(label);
			var $li = $('<li></li>').toggleClass('is-active', active);
			$li.append($('<strong></strong>').text(row.label));
			$li.append($('<span></span>').text(num(row.value).toLocaleString()));
			$list.append($li);
		});

		$panel.prop('hidden', false);
		$panel[0].scrollIntoView({ block: 'nearest', behavior: 'smooth' });
	}

	function setStatus(text, ok) {
		var $el = $('#ngc-pa-status');
		$el.text(text).toggleClass('is-error', ok === false).toggleClass('is-live', ok !== false);
	}

	function applySnapshot(data, meta) {
		state.data = data || {};
		renderKpis(state.data);
		renderCharts(state.data);
		var when = (meta && meta.retrieved_at) ? meta.retrieved_at : new Date().toISOString();
		var source = (meta && meta.source) ? meta.source : 'real';
		setStatus('Live · ' + source + ' · updated ' + when.replace('T', ' ').replace(/\.\d+Z$/, ' UTC'), true);

		if (state.drill) {
			showDrilldown(state.drill.source, state.drill.label, state.drill.value, null);
		}
	}

	function refresh(fresh) {
		setStatus('Refreshing…', true);
		return restGet(!!fresh).done(function (payload) {
			var data = unwrap(payload);
			var meta = payload && payload.meta ? payload.meta : null;
			applySnapshot(data, meta);
		}).fail(function (xhr) {
			setStatus('Refresh failed (' + (xhr.status || 0) + ')', false);
		});
	}

	function startPoll() {
		stopPoll();
		if (!state.live) {
			return;
		}
		state.timer = window.setInterval(function () {
			refresh(true);
		}, state.pollMs);
	}

	function stopPoll() {
		if (state.timer) {
			window.clearInterval(state.timer);
			state.timer = null;
		}
	}

	function boot() {
		if (!$('#ngc-pa-dashboard').length) {
			return;
		}

		if (cfg.initial) {
			applySnapshot(cfg.initial, { source: 'bootstrap', retrieved_at: new Date().toISOString() });
		}

		$('#ngc-pa-refresh').on('click', function () {
			refresh(true);
		});

		$('#ngc-pa-live').on('change', function () {
			state.live = $(this).is(':checked');
			if (state.live) {
				startPoll();
				refresh(true);
			} else {
				stopPoll();
				setStatus('Paused', true);
			}
		});

		$('#ngc-pa-drill-close').on('click', function () {
			state.drill = null;
			$('#ngc-pa-drill').prop('hidden', true);
		});

		$('#ngc-pa-toggle-matrix').on('click', function () {
			var $panel = $('#ngc-pa-matrix');
			var open = $panel.prop('hidden');
			$panel.prop('hidden', !open);
			$(this).attr('aria-expanded', open ? 'true' : 'false');
		});

		refresh(false);
		startPoll();
	}

	$(boot);
})(jQuery);
