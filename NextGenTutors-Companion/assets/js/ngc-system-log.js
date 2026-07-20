(function ($) {
	'use strict';

	var state = { page: 1, perPage: 50, charts: {} };

	function filters() {
		return {
			q: $('#ngc-sl-q').val() || '',
			level: $('#ngc-sl-level').val() || '',
			from: $('#ngc-sl-from').val() || '',
			to: $('#ngc-sl-to').val() || '',
			page: state.page,
			per_page: state.perPage
		};
	}

	function restGet(url, params) {
		return $.ajax({
			url: url,
			data: params,
			headers: { 'X-WP-Nonce': ngcSystemLog.nonce }
		});
	}

	function renderTable(data) {
		var $tbody = $('#ngc-sl-table tbody').empty();
		(data.rows || []).forEach(function (row) {
			var levelClass = 'ngc-sl-level-' + (row.level || 'info');
			var $tr = $('<tr>').attr('data-id', row.id).attr('data-row', JSON.stringify(row));
			$tr.append('<td><input type="checkbox" class="ngc-sl-row-check" value="' + row.id + '" /></td>');
			$tr.append('<td>' + row.id + '</td>');
			$tr.append('<td>' + (row.created_at || '') + '</td>');
			$tr.append('<td class="' + levelClass + '">' + (row.level || '') + '</td>');
			$tr.append('<td>' + (row.channel || '') + '</td>');
			$tr.append('<td>' + (row.source || '') + '</td>');
			$tr.append('<td>' + $('<div>').text(row.message || '').html() + '</td>');
			$tr.append('<td><button type="button" class="button button-small ngc-sl-copy-row">' + 'Copy' + '</button></td>');
			$tbody.append($tr);
		});
		$('#ngc-sl-total').text('Total: ' + (data.total || 0));
		$('#ngc-sl-page-info').text('Page ' + state.page);
	}

	function chartConfig(labels, counts, type) {
		return {
			type: type || 'doughnut',
			data: {
				labels: labels,
				datasets: [{
					data: counts,
					backgroundColor: ['#2271b1', '#72aee6', '#00a32a', '#dba617', '#d63638', '#8c8f94', '#9b59b6', '#1abc9c']
				}]
			},
			options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
		};
	}

	function renderCharts(stats) {
		['level', 'channel', 'source'].forEach(function (key) {
			var canvas = document.getElementById('ngc-sl-chart-' + key);
			if (!canvas) return;
			var rows = stats['by_' + key] || [];
			if (state.charts[key]) state.charts[key].destroy();
			state.charts[key] = new Chart(canvas, chartConfig(
				rows.map(function (r) { return r.label; }),
				rows.map(function (r) { return parseInt(r.count, 10); })
			));
		});
		var dayCanvas = document.getElementById('ngc-sl-chart-day');
		if (dayCanvas) {
			var days = stats.by_day || [];
			if (state.charts.day) state.charts.day.destroy();
			state.charts.day = new Chart(dayCanvas, {
				type: 'line',
				data: {
					labels: days.map(function (r) { return r.label; }),
					datasets: [{ label: 'Events', data: days.map(function (r) { return parseInt(r.count, 10); }), borderColor: '#2271b1', fill: false }]
				},
				options: { responsive: true }
			});
		}
	}

	function load() {
		var f = filters();
		restGet(ngcSystemLog.restUrl, f).done(renderTable);
		restGet(ngcSystemLog.statsUrl, { from: f.from, to: f.to }).done(renderCharts);
	}

	function selectedIds() {
		return $('.ngc-sl-row-check:checked').map(function () { return $(this).val(); }).get();
	}

	function exportLogs(format, idsOnly) {
		var $form = $('<form method="post" action="' + ngcSystemLog.exportUrl + '">');
		$form.append('<input type="hidden" name="action" value="ngc_system_log_export" />');
		$form.append('<input type="hidden" name="_wpnonce" value="' + ngcSystemLog.exportNonce + '" />');
		$form.append('<input type="hidden" name="format" value="' + format + '" />');
		$form.append('<input type="hidden" name="q" value="' + ($('#ngc-sl-q').val() || '') + '" />');
		$form.append('<input type="hidden" name="level" value="' + ($('#ngc-sl-level').val() || '') + '" />');
		$form.append('<input type="hidden" name="from" value="' + ($('#ngc-sl-from').val() || '') + '" />');
		$form.append('<input type="hidden" name="to" value="' + ($('#ngc-sl-to').val() || '') + '" />');
		if (idsOnly) {
			$form.append('<input type="hidden" name="ids" value="' + selectedIds().join(',') + '" />');
		}
		$('body').append($form);
		$form.submit();
		$form.remove();
	}

	function copyText(text) {
		if (navigator.clipboard && navigator.clipboard.writeText) {
			return navigator.clipboard.writeText(text);
		}
		var $ta = $('<textarea>').val(text).appendTo('body').select();
		document.execCommand('copy');
		$ta.remove();
		return $.Deferred().resolve();
	}

	$(function () {
		load();

		$('#ngc-sl-refresh').on('click', function () { state.page = 1; load(); });
		$('#ngc-sl-prev').on('click', function () { if (state.page > 1) { state.page--; load(); } });
		$('#ngc-sl-next').on('click', function () { state.page++; load(); });
		$('#ngc-sl-select-all').on('change', function () {
			$('.ngc-sl-row-check').prop('checked', $(this).prop('checked'));
		});

		$(document).on('click', '.ngc-sl-copy-row', function () {
			var row = $(this).closest('tr').attr('data-row');
			copyText(row).then(function () { alert(ngcSystemLog.i18n.copied); });
		});

		$('#ngc-sl-copy-selected').on('click', function () {
			var ids = selectedIds();
			if (!ids.length) { alert(ngcSystemLog.i18n.selectRow); return; }
			var lines = [];
			$('.ngc-sl-row-check:checked').each(function () {
				lines.push($(this).closest('tr').attr('data-row'));
			});
			copyText(lines.join('\n')).then(function () { alert(ngcSystemLog.i18n.copied); });
		});

		$('.ngc-sl-export').on('click', function () {
			var idsOnly = $('#ngc-sl-export-selected-only').prop('checked');
			if (idsOnly && !selectedIds().length) { alert(ngcSystemLog.i18n.selectRow); return; }
			exportLogs($(this).data('format'), idsOnly);
		});
	});
})(jQuery);
