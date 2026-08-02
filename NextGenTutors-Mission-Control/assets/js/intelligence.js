/**
 * Mission Control — enterprise operational intelligence dashboard.
 */
(function () {
  'use strict';

  var cfg = window.ngtmcIntel || {};
  var root = (cfg.restRoot || '').replace(/\/$/, '');
  var charts = {};
  var sseSince = 0;
  var pollTimer = null;
  var eventPage = 1;
  var eventPages = 1;
  var eventFilters = {};
  var eventGrid = null;
  var layoutSaveTimer = null;

  function headers() {
    return {
      'Content-Type': 'application/json',
      'X-WP-Nonce': cfg.nonce || ''
    };
  }

  function api(path, opts) {
    opts = opts || {};
    return fetch(root + path, Object.assign({ headers: headers(), credentials: 'same-origin' }, opts))
      .then(function (r) {
        if (!r.ok) throw new Error('HTTP ' + r.status);
        var ct = r.headers.get('content-type') || '';
        if (ct.indexOf('application/json') >= 0) return r.json();
        return r.text();
      });
  }

  function fmtValue(kpi) {
    var v = kpi.value;
    if (kpi.format === 'currency') return 'R ' + Number(v).toFixed(2);
    return String(v);
  }

  function esc(s) {
    var d = document.createElement('div');
    d.textContent = s == null ? '' : String(s);
    return d.innerHTML;
  }

  function renderKpis(kpis) {
    var el = document.getElementById('ngtmc-intel-kpis');
    if (!el || !Array.isArray(kpis)) return;
    el.innerHTML = kpis.map(function (k) {
      var sev = k.severity === 'warning' ? ' is-warn' : '';
      var drill = k.drill ? ' data-drill=\'' + JSON.stringify(k.drill).replace(/'/g, '&#39;') + '\'' : '';
      return '<article class="ngtmc-intel-kpi' + sev + '"' + drill + ' tabindex="0" role="button" data-testid="ngtmc-kpi-' + k.key + '">' +
        '<span class="ngtmc-intel-kpi-label">' + esc(k.label) + '</span>' +
        '<strong class="ngtmc-intel-kpi-value">' + esc(fmtValue(k)) + '</strong>' +
        '</article>';
    }).join('');
  }

  function renderSeries(id, rows, label, color, type) {
    var canvas = document.getElementById(id);
    if (!canvas || typeof Chart === 'undefined') return;
    type = type || 'line';
    var labels = (rows || []).map(function (r) { return r.d; });
    var data = (rows || []).map(function (r) { return Number(r.c || r.value || 0); });
    if (charts[id]) {
      if (type === 'doughnut') {
        charts[id].data.datasets[0].data = data;
        charts[id].data.labels = labels;
      } else {
        charts[id].data.labels = labels;
        charts[id].data.datasets[0].data = data;
      }
      charts[id].update('none');
      return;
    }
    charts[id] = new Chart(canvas, {
      type: type,
      data: {
        labels: labels,
        datasets: [{
          label: label,
          data: data,
          borderColor: color,
          backgroundColor: type === 'doughnut' ? [color, '#e2e8f0'] : color + '33',
          tension: 0.3,
          fill: type === 'line'
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: type === 'doughnut' } },
        scales: type === 'line' ? { y: { beginAtZero: true } } : {}
      }
    });
  }

  function renderEvents(rows) {
    var tbody = document.querySelector('#ngtmc-intel-events tbody');
    if (!tbody) return;
    tbody.innerHTML = (rows || []).map(function (r) {
      return '<tr data-event-id="' + r.id + '">' +
        '<td>' + esc(r.recorded_at) + '</td>' +
        '<td><code>' + esc(r.event_key) + '</code></td>' +
        '<td>' + esc(r.plugin_slug) + '</td>' +
        '<td>' + esc(r.module || '') + '</td>' +
        '<td><span class="ngtmc-sev ngtmc-sev--' + esc(r.severity) + '">' + esc(r.severity) + '</span></td>' +
        '<td>' + esc(r.message || '') + '</td>' +
        '</tr>';
    }).join('');
  }

  function renderNotifications(rows) {
    var el = document.getElementById('ngtmc-intel-notifications');
    if (!el) return;
    if (!rows || !rows.length) {
      el.innerHTML = '<p class="ngtmc-meta">No open notifications.</p>';
      return;
    }
    el.innerHTML = rows.map(function (n) {
      return '<article class="ngtmc-intel-notice ngtmc-intel-notice--' + esc(n.type) + '" data-id="' + n.id + '">' +
        '<strong>' + esc(n.title) + '</strong>' +
        '<p>' + esc(n.message) + '</p>' +
        '<button type="button" class="button button-small ngtmc-intel-ack" data-id="' + n.id + '">Acknowledge</button>' +
        '</article>';
    }).join('');
  }

  function renderBrief(data) {
    var el = document.getElementById('ngtmc-intel-brief');
    if (!el || !data) return;
    var html = '<p><strong>Executive summary:</strong> ' + esc(data.summary || '') + '</p>';
    if (data.recommendations && data.recommendations.length) {
      html += '<p><strong>Recommendations:</strong></p><ul>' +
        data.recommendations.map(function (r) { return '<li>' + esc(r) + '</li>'; }).join('') + '</ul>';
    }
    if (data.anomalies && data.anomalies.length) {
      html += '<p class="ngtmc-intel-anomaly"><strong>Anomalies:</strong></p><ul>' +
        data.anomalies.map(function (a) { return '<li>' + esc(a) + '</li>'; }).join('') + '</ul>';
    }
    el.innerHTML = html;
  }

  function renderHealth(health) {
    var el = document.getElementById('ngtmc-intel-health');
    if (!el || !health) return;
    el.innerHTML = Object.keys(health).map(function (k) {
      var v = health[k];
      var ok = v === true || (typeof v === 'object' && v && v !== false);
      return '<span class="ngtmc-intel-health-pill ' + (ok ? 'is-ok' : 'is-bad') + '">' + esc(k) + '</span>';
    }).join('');
  }

  function renderPluginMatrix(plugins) {
    var tbody = document.querySelector('#ngtmc-intel-plugin-matrix tbody');
    if (!tbody || !plugins) return;
    tbody.innerHTML = plugins.map(function (p) {
      return '<tr><td><strong>' + esc(p.name || p.slug) + '</strong></td>' +
        '<td>' + esc(p.version || '—') + '</td>' +
        '<td><span class="ngtmc-intel-status ngtmc-intel-status--' + esc(p.status) + '">' + esc(p.status) + '</span></td>' +
        '<td>' + esc((p.features || []).join(', ')) + '</td></tr>';
    }).join('');
  }

  function renderCronMatrix(rows) {
    var tbody = document.querySelector('#ngtmc-intel-cron-matrix tbody');
    if (!tbody) return;
    tbody.innerHTML = (rows || []).map(function (r) {
      return '<tr><td><code>' + esc(r.hook) + '</code></td><td>' + esc(r.label) + '</td>' +
        '<td>' + (r.scheduled ? 'YES' : 'NO') + '</td><td>' + esc(r.next_run || '—') + '</td></tr>';
    }).join('');
  }

  function populatePluginFilter(plugins) {
    var sel = document.getElementById('ngtmc-intel-filter-plugin');
    if (!sel || sel.options.length > 1) return;
    (plugins || []).forEach(function (p) {
      var o = document.createElement('option');
      o.value = p.slug;
      o.textContent = p.name || p.slug;
      sel.appendChild(o);
    });
  }

  function loadVisualizations() {
    if (!document.getElementById('ngtmc-chart-sankey') && !document.getElementById('ngtmc-network-graph')) {
      return Promise.resolve();
    }
    return api('/visualizations').then(function (payload) {
      if (window.NGTMCIntelCharts) {
        window.NGTMCIntelCharts.renderAll(payload);
      }
    });
  }

  function initEventGrid() {
    var container = document.getElementById('ngtmc-virtual-grid');
    if (!container || !window.NGTMCEnterpriseGrid) return null;
    var grid = new window.NGTMCEnterpriseGrid(container, function (q) {
      return api('/events' + q);
    });
    grid.loadMore(true);
    return grid;
  }

  function applyEventFilters() {
    var search = document.getElementById('ngtmc-intel-search');
    var sev = document.getElementById('ngtmc-intel-filter-severity');
    var plug = document.getElementById('ngtmc-intel-filter-plugin');
    eventFilters = {};
    if (search && search.value) eventFilters.search = search.value;
    if (sev && sev.value) eventFilters.severity = sev.value;
    if (plug && plug.value) eventFilters.plugin_slug = plug.value;
    if (eventGrid) {
      return eventGrid.setFilters(eventFilters);
    }
    eventPage = 1;
    return loadEvents(1);
  }

  function saveLayout(widgets) {
    return api('/layout', { method: 'POST', body: JSON.stringify({ widgets: widgets }) });
  }

  function applyLayoutOrder(order) {
    var canvas = document.getElementById('ngtmc-intel-widgets');
    if (!canvas || !Array.isArray(order)) return;
    order.forEach(function (wid) {
      var el = canvas.querySelector('[data-widget="' + wid + '"]');
      if (el) canvas.appendChild(el);
    });
  }

  function collectLayoutOrder() {
    var canvas = document.getElementById('ngtmc-intel-widgets');
    if (!canvas) return [];
    return Array.prototype.map.call(canvas.querySelectorAll('[data-widget]'), function (el) {
      return el.getAttribute('data-widget');
    });
  }

  function initLayoutDragDrop() {
    var canvas = document.getElementById('ngtmc-intel-widgets');
    if (!canvas) return;
    var dragEl = null;
    api('/layout').then(function (res) {
      if (res && res.widgets) applyLayoutOrder(res.widgets);
    });
    canvas.addEventListener('dragstart', function (e) {
      var w = e.target.closest('.ngtmc-intel-widget');
      if (!w) return;
      dragEl = w;
      e.dataTransfer.effectAllowed = 'move';
    });
    canvas.addEventListener('dragover', function (e) {
      e.preventDefault();
      var over = e.target.closest('.ngtmc-intel-widget');
      if (!over || !dragEl || over === dragEl) return;
      var rect = over.getBoundingClientRect();
      var after = e.clientY > rect.top + rect.height / 2;
      canvas.insertBefore(dragEl, after ? over.nextSibling : over);
    });
    canvas.addEventListener('dragend', function () {
      dragEl = null;
      clearTimeout(layoutSaveTimer);
      layoutSaveTimer = setTimeout(function () {
        saveLayout(collectLayoutOrder());
      }, 400);
    });
  }

  function loadDashboard() {
    return api('/dashboard').then(function (dash) {
      renderKpis(dash.kpis);
      renderSeries('ngtmc-chart-bookings', dash.series && dash.series.bookings_7d, 'Bookings', '#2563eb');
      renderSeries('ngtmc-chart-errors', dash.series && dash.series.errors_7d, 'Errors', '#dc2626');
      renderSeries('ngtmc-chart-api', dash.series && dash.series.api_7d, 'API', '#7c3aed');
      if (dash.workflows) {
        renderSeries('ngtmc-chart-workflows', [
          { d: 'OK', c: Math.max(0, (dash.workflows.today || 0) - (dash.workflows.failed || 0)) },
          { d: 'Failed', c: dash.workflows.failed || 0 }
        ], 'Workflows', '#059669', 'doughnut');
      }
      renderHealth(dash.health);
      populatePluginFilter(Object.values(dash.plugins || {}));
      var stamp = document.getElementById('ngtmc-intel-updated');
      if (stamp) stamp.textContent = dash.generated_at || new Date().toISOString();
    });
  }

  function loadEvents(page) {
    page = page || eventPage;
    var q = new URLSearchParams(Object.assign({ page: page, per_page: 25 }, eventFilters)).toString();
    return api('/events?' + q).then(function (res) {
      renderEvents(res.rows);
      eventPage = res.page || page;
      eventPages = res.pages || 1;
      var count = document.getElementById('ngtmc-intel-events-count');
      if (count) count.textContent = (res.total || 0) + ' events';
      var label = document.getElementById('ngtmc-intel-page-label');
      if (label) label.textContent = 'Page ' + eventPage + ' / ' + eventPages;
      var prev = document.getElementById('ngtmc-intel-prev');
      var next = document.getElementById('ngtmc-intel-next');
      if (prev) prev.disabled = eventPage <= 1;
      if (next) next.disabled = eventPage >= eventPages;
    });
  }

  function loadNotifications() {
    return api('/notifications?limit=10').then(function (res) {
      renderNotifications(res.rows);
    });
  }

  function loadBrief() {
    return api('/insights').then(renderBrief);
  }

  function loadHealthFull() {
    return api('/health').then(function (h) {
      renderPluginMatrix(h.plugins || []);
      renderCronMatrix(h.cron_queues || []);
    });
  }

  function loadConfigForm() {
    return api('/config').then(function (c) {
      var form = document.getElementById('ngtmc-intel-config-form');
      if (!form) return;
      ['enabled', 'mask_pii', 'sse_enabled'].forEach(function (k) {
        var el = form.querySelector('[name="' + k + '"]');
        if (el) el.checked = !!c[k];
      });
      ['retention_days', 'refresh_interval_ms', 'sampling_rate', 'notify_email', 'webhook_url',
        'teams_webhook_url', 'slack_webhook_url', 'whatsapp_webhook_url', 'sms_webhook_url'].forEach(function (k) {
        var el = form.querySelector('[name="' + k + '"]');
        if (el && c[k] != null) el.value = c[k];
      });
    });
  }

  function loadAudit() {
    return api('/audit?limit=20').then(function (res) {
      var ul = document.getElementById('ngtmc-intel-audit');
      if (!ul) return;
      ul.innerHTML = (res.rows || []).map(function (r) {
        return '<li><code>' + esc(r.action) + '</code> · ' + esc(r.recorded_at) + '</li>';
      }).join('') || '<li class="ngtmc-meta">No audit entries yet.</li>';
    });
  }

  function refreshAll() {
    var view = document.querySelector('.ngtmc-intel-view');
    var name = view ? view.getAttribute('data-view') : 'overview';
    var tasks = [];
    if (name === 'overview' || !name) {
      tasks = [loadDashboard(), loadNotifications(), loadBrief(), loadVisualizations()];
    } else if (name === 'events') {
      if (!eventGrid) eventGrid = initEventGrid();
      else tasks = [eventGrid.setFilters(eventFilters)];
    } else if (name === 'plugins') {
      tasks = [loadHealthFull()];
    } else if (name === 'settings') {
      tasks = [loadConfigForm(), loadAudit()];
    } else {
      tasks = [loadDashboard()];
    }
    return Promise.all(tasks).catch(function (e) { console.warn('[ngtmc-intel]', e); });
  }

  function connectSse() {
    if (!cfg.sseEnabled || typeof EventSource === 'undefined') return;
    var url = root + '/stream?since=' + sseSince + '&timeout=25&_wpnonce=' + encodeURIComponent(cfg.nonce || '');
    var es = new EventSource(url, { withCredentials: true });
    es.addEventListener('event.ingested', refreshAll);
    es.addEventListener('notification.created', loadNotifications);
    es.onmessage = function (ev) {
      try {
        var data = JSON.parse(ev.data);
        if (data.id) sseSince = Math.max(sseSince, data.id);
      } catch (err) { /* ignore */ }
    };
    es.onerror = function () { es.close(); setTimeout(connectSse, 5000); };
  }

  function bindUi() {
    document.addEventListener('click', function (e) {
      var ack = e.target.closest('.ngtmc-intel-ack');
      if (ack) {
        api('/notifications/' + ack.getAttribute('data-id') + '/ack', { method: 'POST' }).then(loadNotifications);
        return;
      }
      var kpi = e.target.closest('[data-drill]');
      if (kpi) {
        var drill = JSON.parse(kpi.getAttribute('data-drill'));
        eventFilters = drill;
        eventPage = 1;
        var drillBar = document.getElementById('ngtmc-intel-drill');
        if (drillBar) {
          drillBar.hidden = false;
          document.getElementById('ngtmc-intel-drill-label').textContent = JSON.stringify(drill);
        }
        loadEvents(1);
        var eventsTab = document.querySelector('[data-intel-view="events"]');
        if (eventsTab) eventsTab.click();
        setTimeout(function () {
          if (!eventGrid) eventGrid = initEventGrid();
          else eventGrid.setFilters(eventFilters);
        }, 300);
      }
    });

    var askBtn = document.getElementById('ngtmc-intel-ask-btn');
    var askInput = document.getElementById('ngtmc-intel-ask');
    var askOut = document.getElementById('ngtmc-intel-ask-answer');
    if (askBtn && askInput && askOut) {
      askBtn.addEventListener('click', function () {
        api('/insights/ask', { method: 'POST', body: JSON.stringify({ question: askInput.value }) })
          .then(function (res) { askOut.textContent = res.answer || ''; });
      });
      askInput.addEventListener('keydown', function (ev) {
        if (ev.key === 'Enter') askBtn.click();
      });
    }

    var themeBtn = document.getElementById('ngtmc-intel-theme-toggle');
    if (themeBtn) {
      themeBtn.addEventListener('click', function () {
        var shell = document.getElementById('ngtmc-intelligence');
        var dark = shell.classList.toggle('ngtmc-intel--dark');
        themeBtn.setAttribute('aria-pressed', dark ? 'true' : 'false');
        try { localStorage.setItem('ngtmcIntelTheme', dark ? 'dark' : 'light'); } catch (e) { /* ignore */ }
      });
      try {
        if (localStorage.getItem('ngtmcIntelTheme') === 'dark') {
          document.getElementById('ngtmc-intelligence').classList.add('ngtmc-intel--dark');
          themeBtn.setAttribute('aria-pressed', 'true');
        }
      } catch (e) { /* ignore */ }
    }

    var search = document.getElementById('ngtmc-intel-search');
    var sev = document.getElementById('ngtmc-intel-filter-severity');
    var plug = document.getElementById('ngtmc-intel-filter-plugin');
    function applyFilters() {
      applyEventFilters();
    }
    if (search) search.addEventListener('change', applyFilters);
    if (sev) sev.addEventListener('change', applyFilters);
    if (plug) plug.addEventListener('change', applyFilters);

    var prev = document.getElementById('ngtmc-intel-prev');
    var next = document.getElementById('ngtmc-intel-next');
    if (prev) prev.addEventListener('click', function () { if (eventPage > 1) loadEvents(eventPage - 1); });
    if (next) next.addEventListener('click', function () { if (eventPage < eventPages) loadEvents(eventPage + 1); });

    var exportBtn = document.getElementById('ngtmc-intel-export-csv');
    if (exportBtn) {
      exportBtn.addEventListener('click', function () {
        var q = new URLSearchParams(eventFilters).toString();
        window.location.href = root + '/events/export?' + q + '&_wpnonce=' + encodeURIComponent(cfg.nonce || '');
      });
    }

    var configForm = document.getElementById('ngtmc-intel-config-form');
    if (configForm) {
      configForm.addEventListener('submit', function (ev) {
        ev.preventDefault();
        var fd = new FormData(configForm);
        var body = {
          enabled: !!fd.get('enabled'),
          mask_pii: !!fd.get('mask_pii'),
          sse_enabled: !!fd.get('sse_enabled'),
          retention_days: parseInt(fd.get('retention_days'), 10),
          refresh_interval_ms: parseInt(fd.get('refresh_interval_ms'), 10),
          sampling_rate: parseFloat(fd.get('sampling_rate')),
          notify_email: fd.get('notify_email'),
          webhook_url: fd.get('webhook_url'),
          teams_webhook_url: fd.get('teams_webhook_url'),
          slack_webhook_url: fd.get('slack_webhook_url'),
          whatsapp_webhook_url: fd.get('whatsapp_webhook_url'),
          sms_webhook_url: fd.get('sms_webhook_url')
        };
        api('/config', { method: 'POST', body: JSON.stringify(body) }).then(function () {
          var st = document.getElementById('ngtmc-intel-config-status');
          if (st) st.textContent = 'Settings saved.';
          loadAudit();
        });
      });
    }

    var drillBack = document.getElementById('ngtmc-intel-drill-back');
    if (drillBack) {
      drillBack.addEventListener('click', function () {
        eventFilters = {};
        document.getElementById('ngtmc-intel-drill').hidden = true;
        loadEvents(1);
      });
    }
  }

  function init() {
    var shell = document.getElementById('ngtmc-intelligence');
    if (!shell || !root) return;
    bindUi();
    var view = document.querySelector('.ngtmc-intel-view');
    var name = view ? view.getAttribute('data-view') : 'overview';
    if (name === 'events') eventGrid = initEventGrid();
    if (name === 'overview') initLayoutDragDrop();
    refreshAll();
    connectSse();
    pollTimer = setInterval(refreshAll, cfg.refreshMs || 5000);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
