/**
 * Orchestration Cockpit — drag/resize workspace, CRUD grids, toasts.
 */
(function () {
  'use strict';

  var cfg = window.NGC_COCKPIT || {};
  var snap = cfg.snapshot || {};
  var entities = cfg.entities || {};
  var layout = cfg.layout || {};
  var COLS = 12;
  var ROW_H = 28;
  var GAP = 10;
  var toastMs = parseInt(cfg.toastMs, 10) || 5000;
  var editingId = null;
  var saveLayoutTimer = null;

  function $(sel, root) {
    return (root || document).querySelector(sel);
  }

  function $all(sel, root) {
    return Array.prototype.slice.call((root || document).querySelectorAll(sel));
  }

  function esc(s) {
    var d = document.createElement('div');
    d.textContent = s == null ? '' : String(s);
    return d.innerHTML;
  }

  function toast(message, kind) {
    var host = $('#ngc-cockpit-toasts');
    if (!host || !message) return;
    var el = document.createElement('div');
    el.className = 'ngc-cockpit-toast is-' + (kind || 'info');
    el.innerHTML = '<strong>' + esc((kind || 'info').toUpperCase()) + '</strong><span>' + esc(message) + '</span>';
    host.appendChild(el);
    requestAnimationFrame(function () { el.classList.add('is-in'); });
    setTimeout(function () {
      el.classList.remove('is-in');
      el.classList.add('is-out');
      setTimeout(function () { el.remove(); }, 320);
    }, toastMs);
  }

  function post(action, fields) {
    var body = new URLSearchParams();
    body.set('action', action);
    body.set('nonce', cfg.ajaxNonce || '');
    Object.keys(fields || {}).forEach(function (k) {
      body.set(k, fields[k] == null ? '' : String(fields[k]));
    });
    return fetch(cfg.ajaxUrl || window.ajaxurl, {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
      body: body.toString()
    }).then(function (r) { return r.json(); });
  }

  function statusClass(s) {
    s = String(s || '').toLowerCase();
    if (/up|ok|ready|running|active|success/.test(s)) return 'is-up';
    if (/down|error|danger|paused/.test(s)) return 'is-down';
    if (/warn|warning|degraded|configured/.test(s)) return 'is-warn';
    return 'is-idle';
  }

  function hiddenSet(type) {
    var h = (entities._hidden && entities._hidden[type]) || [];
    var map = {};
    h.forEach(function (id) { map[String(id)] = true; });
    return map;
  }

  function mergeRows(type, liveRows, mapLive) {
    var hidden = hiddenSet(type);
    var custom = (entities[type] || []).slice();
    var byId = {};
    custom.forEach(function (r) { byId[String(r.id)] = r; });
    var out = [];
    (liveRows || []).forEach(function (live) {
      var mapped = mapLive(live);
      if (!mapped || !mapped.id) return;
      if (hidden[mapped.id]) return;
      if (byId[mapped.id]) {
        out.push(Object.assign({}, mapped, byId[mapped.id], { source: byId[mapped.id].source || 'override' }));
        delete byId[mapped.id];
      } else {
        out.push(mapped);
      }
    });
    Object.keys(byId).forEach(function (id) {
      if (!hidden[id]) out.push(byId[id]);
    });
    return out;
  }

  function tableHtml(type, rows, columns) {
    var head = columns.map(function (c) { return '<th>' + esc(c.label) + '</th>'; }).join('') +
      '<th class="ngc-cockpit-actions-col">Actions</th>';
    var body = rows.map(function (r) {
      var cells = columns.map(function (c) {
        var v = r[c.key];
        if (c.key === 'status') {
          return '<td><span class="ngc-cockpit-pill ' + statusClass(v) + '">' + esc(v || '—') + '</span></td>';
        }
        return '<td>' + esc(v == null ? '—' : v) + '</td>';
      }).join('');
      return '<tr data-id="' + esc(r.id) + '" data-type="' + esc(type) + '">' + cells +
        '<td class="ngc-cockpit-actions-col">' +
        '<button type="button" class="button button-small" data-crud="edit">' + esc((cfg.i18n && cfg.i18n.edit) || 'Edit') + '</button> ' +
        '<button type="button" class="button button-small button-primary" data-crud="update">' + esc((cfg.i18n && cfg.i18n.update) || 'Update') + '</button> ' +
        '<button type="button" class="button button-small" data-crud="delete">' + esc((cfg.i18n && cfg.i18n.delete) || 'Delete') + '</button>' +
        '</td></tr>';
    }).join('') || '<tr><td colspan="' + (columns.length + 1) + '">No rows</td></tr>';
    return '<div class="ngc-cockpit-table-toolbar">' +
      '<button type="button" class="button button-primary" data-crud="add" data-type="' + esc(type) + '">+ ' +
      esc((cfg.i18n && cfg.i18n.add) || 'Add') + '</button></div>' +
      '<div class="ngc-cockpit-table-scroll"><table class="widefat striped ngc-cockpit-table"><thead><tr>' +
      head + '</tr></thead><tbody>' + body + '</tbody></table></div>';
  }

  function drawSpark(canvas, series, color) {
    if (!canvas || !canvas.getContext) return;
    var ctx = canvas.getContext('2d');
    var w = canvas.width = Math.max(220, canvas.clientWidth || 320);
    var h = canvas.height = 140;
    ctx.clearRect(0, 0, w, h);
    var data = (series || []).map(function (n) { return Number(n) || 0; });
    if (!data.length) {
      ctx.fillStyle = '#64748b';
      ctx.fillText('No samples yet', 12, h / 2);
      return;
    }
    var max = Math.max.apply(null, data.concat([1]));
    var min = Math.min.apply(null, data.concat([0]));
    var span = Math.max(max - min, 1);
    ctx.strokeStyle = color || '#2563eb';
    ctx.lineWidth = 2.5;
    ctx.beginPath();
    data.forEach(function (v, i) {
      var x = (i / Math.max(data.length - 1, 1)) * (w - 16) + 8;
      var y = h - 12 - ((v - min) / span) * (h - 24);
      if (i === 0) ctx.moveTo(x, y); else ctx.lineTo(x, y);
    });
    ctx.stroke();
  }

  function renderKpi(s) {
    var el = $('#ngc-cockpit-mount-kpi');
    if (!el) return;
    var rt = s.runtime || {};
    var cards = [
      { label: 'Memory', value: (rt.memory_pct != null ? rt.memory_pct + '%' : '—'), sub: (rt.memory_usage || '') + ' / ' + (rt.memory_limit || '') },
      { label: 'Load 1m', value: rt.load_1m != null ? String(rt.load_1m) : 'n/a', sub: rt.server_software || 'host' },
      { label: 'Disk', value: rt.disk_used_pct != null ? rt.disk_used_pct + '%' : '—', sub: (rt.disk_free || '—') + ' free' },
      { label: 'Errors 24h', value: String((s.logs && s.logs.errors_24h) || 0), sub: 'system log' },
      { label: 'PHP', value: rt.php_version || '—', sub: 'WP ' + (rt.wp_version || '') }
    ];
    el.innerHTML = '<div class="ngc-cockpit-kpi">' + cards.map(function (c) {
      return '<div class="ngc-cockpit-kpi__card"><span>' + esc(c.label) + '</span><strong>' + esc(c.value) + '</strong><em>' + esc(c.sub) + '</em></div>';
    }).join('') + '</div>';
  }

  function renderTables(s) {
    var conn = mergeRows('connectivity', s.connectivity || [], function (r) {
      return { id: r.id, label: r.label, detail: r.detail, status: r.status, latency: r.latency != null ? r.latency + ' ms' : '—', source: 'live' };
    });
    var apis = mergeRows('apis', s.apis || [], function (r) {
      return { id: r.id, label: r.label, detail: r.path, status: r.status, latency: r.latency != null ? r.latency + ' ms' : '—', source: 'live' };
    });
    var schedules = mergeRows('schedules', s.schedules || s.cron || [], function (r) {
      return { id: r.hook, label: r.label || r.hook, detail: r.next_run || '—', status: r.scheduled ? 'ok' : 'idle', source: 'live' };
    });
    var processes = mergeRows('processes', s.processes || [], function (r) {
      return { id: r.id, label: r.label, detail: r.detail, status: r.status, source: 'live' };
    });
    var agents = mergeRows('agents', s.agents || [], function (r) {
      return { id: r.id, label: r.name, detail: 'Autonomy L' + r.autonomy, status: r.status, source: 'live' };
    });

    var mounts = {
      connectivity: [conn, [{ key: 'label', label: 'Service' }, { key: 'detail', label: 'Detail' }, { key: 'status', label: 'Status' }, { key: 'latency', label: 'Latency' }]],
      apis: [apis, [{ key: 'label', label: 'API' }, { key: 'detail', label: 'Path' }, { key: 'status', label: 'Status' }, { key: 'latency', label: 'Latency' }]],
      schedules: [schedules, [{ key: 'label', label: 'Schedule' }, { key: 'detail', label: 'Next run' }, { key: 'status', label: 'Status' }]],
      processes: [processes, [{ key: 'label', label: 'Process' }, { key: 'detail', label: 'Detail' }, { key: 'status', label: 'Status' }]],
      agents: [agents, [{ key: 'label', label: 'Agent' }, { key: 'detail', label: 'Detail' }, { key: 'status', label: 'Status' }]]
    };
    Object.keys(mounts).forEach(function (type) {
      var el = $('#ngc-cockpit-mount-' + type);
      if (el) el.innerHTML = tableHtml(type, mounts[type][0], mounts[type][1]);
    });
  }

  function renderAlerts(s) {
    var el = $('#ngc-cockpit-mount-alerts');
    if (!el) return;
    var alerts = s.alerts || [];
    el.innerHTML = '<div class="ngc-cockpit-alert-feed">' + alerts.map(function (a) {
      return '<div class="ngc-cockpit-alert is-' + esc(a.level || 'info') + '"><strong>' +
        esc((a.level || 'info').toUpperCase()) + ':</strong> ' + esc(a.title || '') +
        '<span>' + esc(a.message || '') + '</span></div>';
    }).join('') + '</div>';
  }

  function renderArchitecture(s) {
    var el = $('#ngc-cockpit-mount-architecture');
    if (!el) return;
    var nodes = s.architecture || [];
    var groups = { edge: [], host: [], app: [], vps: [] };
    nodes.forEach(function (n) {
      var g = n.group || 'app';
      if (!groups[g]) groups[g] = [];
      groups[g].push(n);
    });
    el.innerHTML = '<div class="ngc-cockpit-arch">' + Object.keys(groups).map(function (g) {
      if (!groups[g].length) return '';
      return '<div class="ngc-cockpit-arch__col"><h4>' + esc(g.toUpperCase()) + '</h4>' +
        groups[g].map(function (n) { return '<div class="ngc-cockpit-arch__node">' + esc(n.label) + '</div>'; }).join('') +
        '</div>';
    }).join('') + '</div>';
  }

  function renderStatus(s) {
    var el = $('#ngc-cockpit-global-status');
    var wrap = document.querySelector('.ngc-cockpit-status');
    if (el && s.status) el.textContent = s.status.label || 'READY';
    if (wrap && s.status) wrap.setAttribute('data-level', s.status.level || 'info');
  }

  function renderAll(s) {
    snap = s || snap;
    renderStatus(snap);
    renderKpi(snap);
    drawSpark($('#ngc-cockpit-mem-chart'), (snap.runtime && snap.runtime.history && snap.runtime.history.memory) || [], '#2563eb');
    drawSpark($('#ngc-cockpit-cpu-chart'), (snap.runtime && snap.runtime.history && snap.runtime.history.cpu) || [], '#06b6d4');
    renderTables(snap);
    renderAlerts(snap);
    renderArchitecture(snap);
  }

  /* ——— Workspace layout ——— */
  function cellW(workspace) {
    return (workspace.clientWidth - GAP) / COLS;
  }

  function applyBox(widget, box, workspace) {
    var cw = cellW(workspace);
    widget.style.left = (box.x * cw + GAP) + 'px';
    widget.style.top = (box.y * ROW_H + GAP) + 'px';
    widget.style.width = (box.w * cw - GAP) + 'px';
    widget.style.height = (box.h * ROW_H - GAP) + 'px';
    widget.dataset.x = String(box.x);
    widget.dataset.y = String(box.y);
    widget.dataset.w = String(box.w);
    widget.dataset.h = String(box.h);
  }

  function readBox(widget) {
    return {
      x: parseInt(widget.dataset.x, 10) || 0,
      y: parseInt(widget.dataset.y, 10) || 0,
      w: parseInt(widget.dataset.w, 10) || 4,
      h: parseInt(widget.dataset.h, 10) || 4
    };
  }

  function collectLayout() {
    var out = {};
    $all('.ngc-cockpit-widget').forEach(function (w) {
      out[w.getAttribute('data-widget')] = readBox(w);
    });
    return out;
  }

  function persistLayout() {
    clearTimeout(saveLayoutTimer);
    saveLayoutTimer = setTimeout(function () {
      post('ngc_cockpit_layout', { layout: JSON.stringify(collectLayout()) })
        .then(function (json) {
          if (json && json.success) {
            /* quiet save — no toast spam while dragging */
          }
        });
    }, 500);
  }

  function layoutWorkspace() {
    var workspace = $('#ngc-cockpit-workspace');
    if (!workspace) return;
    var maxY = 0;
    $all('.ngc-cockpit-widget', workspace).forEach(function (widget) {
      var id = widget.getAttribute('data-widget');
      var box = layout[id] || readBox(widget);
      applyBox(widget, box, workspace);
      maxY = Math.max(maxY, box.y + box.h);
    });
    workspace.style.minHeight = ((maxY + 2) * ROW_H) + 'px';
  }

  function bindDragResize() {
    var workspace = $('#ngc-cockpit-workspace');
    if (!workspace) return;

    $all('.ngc-cockpit-widget', workspace).forEach(function (widget) {
      var drag = widget.querySelector('.ngc-cockpit-widget__drag');
      var resize = widget.querySelector('.ngc-cockpit-widget__resize');

      function startDrag(e) {
        e.preventDefault();
        var startX = e.clientX;
        var startY = e.clientY;
        var box = readBox(widget);
        var cw = cellW(workspace);
        widget.classList.add('is-dragging');
        function move(ev) {
          var dx = Math.round((ev.clientX - startX) / cw);
          var dy = Math.round((ev.clientY - startY) / ROW_H);
          var next = {
            x: Math.max(0, Math.min(COLS - box.w, box.x + dx)),
            y: Math.max(0, box.y + dy),
            w: box.w,
            h: box.h
          };
          applyBox(widget, next, workspace);
        }
        function up() {
          widget.classList.remove('is-dragging');
          document.removeEventListener('pointermove', move);
          document.removeEventListener('pointerup', up);
          layout = collectLayout();
          persistLayout();
          layoutWorkspace();
        }
        document.addEventListener('pointermove', move);
        document.addEventListener('pointerup', up);
      }

      function startResize(e) {
        e.preventDefault();
        e.stopPropagation();
        var startX = e.clientX;
        var startY = e.clientY;
        var box = readBox(widget);
        var cw = cellW(workspace);
        widget.classList.add('is-resizing');
        function move(ev) {
          var dw = Math.round((ev.clientX - startX) / cw);
          var dh = Math.round((ev.clientY - startY) / ROW_H);
          var next = {
            x: box.x,
            y: box.y,
            w: Math.max(2, Math.min(COLS - box.x, box.w + dw)),
            h: Math.max(2, Math.min(16, box.h + dh))
          };
          applyBox(widget, next, workspace);
        }
        function up() {
          widget.classList.remove('is-resizing');
          document.removeEventListener('pointermove', move);
          document.removeEventListener('pointerup', up);
          layout = collectLayout();
          persistLayout();
          layoutWorkspace();
          renderAll(snap);
        }
        document.addEventListener('pointermove', move);
        document.addEventListener('pointerup', up);
      }

      if (drag) drag.addEventListener('pointerdown', startDrag);
      if (resize) resize.addEventListener('pointerdown', startResize);
    });

    window.addEventListener('resize', function () {
      layoutWorkspace();
    });
  }

  /* ——— CRUD modal ——— */
  function openModal(mode, type, row) {
    var modal = $('#ngc-cockpit-modal');
    if (!modal) return;
    editingId = row && row.id ? row.id : null;
    $('#ngc-cockpit-entity-type').value = type;
    $('#ngc-cockpit-entity-id').value = editingId || '';
    $('#ngc-cockpit-field-label').value = (row && row.label) || '';
    $('#ngc-cockpit-field-detail').value = (row && (row.detail || row.path)) || '';
    $('#ngc-cockpit-field-status').value = (row && row.status) || 'ok';
    $('#ngc-cockpit-modal-title').textContent = mode === 'add' ? 'Add row' : 'Edit row';
    $('#ngc-cockpit-modal-save').textContent = mode === 'add'
      ? ((cfg.i18n && cfg.i18n.add) || 'Add')
      : ((cfg.i18n && cfg.i18n.update) || 'Update');
    modal.dataset.mode = mode;
    if (typeof modal.showModal === 'function') modal.showModal();
    else modal.setAttribute('open', 'open');
  }

  function closeModal() {
    var modal = $('#ngc-cockpit-modal');
    if (!modal) return;
    if (typeof modal.close === 'function') modal.close();
    else modal.removeAttribute('open');
  }

  function rowFromTr(tr) {
    var tds = tr.querySelectorAll('td');
    return {
      id: tr.getAttribute('data-id'),
      label: tds[0] ? tds[0].textContent.trim() : '',
      detail: tds[1] ? tds[1].textContent.trim() : '',
      status: (tr.querySelector('.ngc-cockpit-pill') || {}).textContent || 'ok'
    };
  }

  function bindCrud() {
    var workspace = $('#ngc-cockpit-workspace');
    if (!workspace) return;

    workspace.addEventListener('click', function (e) {
      var addBtn = e.target.closest('[data-crud="add"]');
      if (addBtn) {
        openModal('add', addBtn.getAttribute('data-type'), null);
        return;
      }
      var btn = e.target.closest('[data-crud]');
      if (!btn) return;
      var tr = btn.closest('tr[data-id]');
      if (!tr) return;
      var type = tr.getAttribute('data-type');
      var op = btn.getAttribute('data-crud');
      var row = rowFromTr(tr);

      if (op === 'edit') {
        openModal('edit', type, row);
        return;
      }
      if (op === 'update') {
        openModal('edit', type, row);
        return;
      }
      if (op === 'delete') {
        if (!window.confirm((cfg.i18n && cfg.i18n.confirmDelete) || 'Delete this row?')) return;
        post('ngc_cockpit_entity', { op: 'delete', entity_type: type, entity_id: row.id })
          .then(function (json) {
            if (!json || !json.success) {
              toast((json && json.data && json.data.message) || (cfg.i18n && cfg.i18n.error) || 'Failed', 'error');
              return;
            }
            entities = json.data.entities || entities;
            toast((json.data && json.data.message) || (cfg.i18n && cfg.i18n.deleted) || 'Deleted', 'success');
            renderTables(snap);
          });
      }
    });

    var form = $('#ngc-cockpit-entity-form');
    if (form) {
      form.addEventListener('submit', function (e) {
        e.preventDefault();
        var mode = ($('#ngc-cockpit-modal') || {}).dataset.mode || 'edit';
        var type = $('#ngc-cockpit-entity-type').value;
        var payload = {
          op: mode === 'add' ? 'create' : 'update',
          entity_type: type,
          entity_id: $('#ngc-cockpit-entity-id').value,
          label: $('#ngc-cockpit-field-label').value,
          detail: $('#ngc-cockpit-field-detail').value,
          status: $('#ngc-cockpit-field-status').value
        };
        post('ngc_cockpit_entity', payload).then(function (json) {
          if (!json || !json.success) {
            toast((json && json.data && json.data.message) || (cfg.i18n && cfg.i18n.error) || 'Failed', 'error');
            return;
          }
          entities = json.data.entities || entities;
          toast(
            (json.data && json.data.message) ||
              (mode === 'add' ? ((cfg.i18n && cfg.i18n.added) || 'Added') : ((cfg.i18n && cfg.i18n.updated) || 'Updated')),
            'success'
          );
          closeModal();
          renderTables(snap);
        });
      });
    }
    ['ngc-cockpit-modal-close', 'ngc-cockpit-modal-cancel'].forEach(function (id) {
      var el = document.getElementById(id);
      if (el) el.addEventListener('click', closeModal);
    });
  }

  function refresh() {
    post('ngc_cockpit_snapshot', {}).then(function (json) {
      if (json && json.success && json.data) {
        if (json.data.snapshot) snap = json.data.snapshot;
        if (json.data.entities) entities = json.data.entities;
        renderAll(snap);
      }
    });
  }

  document.addEventListener('DOMContentLoaded', function () {
    if (!$('.ngc-cockpit-wrap')) return;

    // Prevent global BeyondInfinity script from appending a second badge into the h1.
    var title = $('.ngc-cockpit-title');
    if (title) title.dataset.ngbiDone = '1';

    bindDragResize();
    layoutWorkspace();
    bindCrud();
    renderAll(snap);

    var refreshBtn = $('#ngc-cockpit-refresh');
    if (refreshBtn) refreshBtn.addEventListener('click', refresh);

    var resetBtn = $('#ngc-cockpit-reset-layout');
    if (resetBtn) {
      resetBtn.addEventListener('click', function () {
        layout = {};
        $all('.ngc-cockpit-widget').forEach(function (w) {
          applyBox(w, {
            x: parseInt(w.getAttribute('data-default-x'), 10) || 0,
            y: parseInt(w.getAttribute('data-default-y'), 10) || 0,
            w: parseInt(w.getAttribute('data-default-w'), 10) || 4,
            h: parseInt(w.getAttribute('data-default-h'), 10) || 4
          }, $('#ngc-cockpit-workspace'));
        });
        layout = collectLayout();
        layoutWorkspace();
        persistLayout();
        toast((cfg.i18n && cfg.i18n.savedLayout) || 'Layout saved.', 'success');
      });
    }

    var emergency = document.querySelector('.ngc-cockpit-emergency-form');
    if (emergency) {
      emergency.addEventListener('submit', function (e) {
        var paused = emergency.querySelector('input[name="paused"]');
        var engaging = paused && paused.value === '1';
        var msg = engaging
          ? ((cfg.i18n && cfg.i18n.confirmStop) || 'Engage emergency stop?')
          : ((cfg.i18n && cfg.i18n.confirmResume) || 'Resume?');
        if (!window.confirm(msg)) e.preventDefault();
      });
    }

    var wrap = $('.ngc-cockpit-wrap');
    if (wrap && wrap.getAttribute('data-flash') === 'config') {
      toast('Configuration saved.', 'success');
    }
    if (wrap && wrap.getAttribute('data-flash') === 'emergency') {
      toast('Emergency control updated.', 'warning');
    }

    setInterval(refresh, parseInt(cfg.pollMs, 10) || 20000);
  });
})();
