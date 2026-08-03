/**
 * Enterprise Data Grid — list / export / Open → responsive modal.
 */
(function () {
  'use strict';
  var cfg = window.ngtAdminGrid || {};
  var mounts = document.querySelectorAll('.ngt-admin-grid[data-entity]');

  function esc(s) {
    var d = document.createElement('div');
    d.textContent = s == null ? '' : String(s);
    return d.innerHTML;
  }

  function api(path, opts) {
    return fetch((cfg.restRoot || '').replace(/\/$/, '') + path, Object.assign({
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': cfg.nonce || '' }
    }, opts || {})).then(function (r) { return r.json(); });
  }

  /**
   * Shared responsive modal for any admin grid Open action.
   */
  var GridModal = (function () {
    var root = null;
    var lastFocus = null;

    function ensure() {
      if (root) return root;
      root = document.createElement('div');
      root.className = 'ngt-grid-modal';
      root.setAttribute('hidden', 'hidden');
      root.innerHTML =
        '<div class="ngt-grid-modal__backdrop" data-ngt-grid-modal-close="1"></div>' +
        '<div class="ngt-grid-modal__panel" role="dialog" aria-modal="true" aria-labelledby="ngt-grid-modal-title" tabindex="-1">' +
        '<div class="ngt-grid-modal__header">' +
        '<h2 class="ngt-grid-modal__title" id="ngt-grid-modal-title"></h2>' +
        '<button type="button" class="ngt-grid-modal__close" data-ngt-grid-modal-close="1" aria-label="Close">&times;</button>' +
        '</div>' +
        '<div class="ngt-grid-modal__body" data-testid="ngt-admin-grid-detail"></div>' +
        '</div>';
      document.body.appendChild(root);
      root.addEventListener('click', function (e) {
        if (e.target && e.target.getAttribute('data-ngt-grid-modal-close') === '1') {
          close();
        }
      });
      document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && root && root.classList.contains('is-open')) {
          close();
        }
      });
      return root;
    }

    function open(opts) {
      opts = opts || {};
      var el = ensure();
      lastFocus = document.activeElement;
      el.querySelector('.ngt-grid-modal__title').textContent = opts.title || 'Record';
      var body = el.querySelector('.ngt-grid-modal__body');
      body.innerHTML = opts.html || '<p class="ngt-grid-modal__loading">Loading…</p>';
      el.classList.add('is-open');
      el.removeAttribute('hidden');
      document.documentElement.classList.add('ngt-grid-modal-open');
      var panel = el.querySelector('.ngt-grid-modal__panel');
      if (panel) panel.focus();
      if (typeof opts.onReady === 'function') opts.onReady(body);
      return body;
    }

    function setContent(html) {
      var el = ensure();
      var body = el.querySelector('.ngt-grid-modal__body');
      body.innerHTML = html || '';
      return body;
    }

    function close() {
      if (!root) return;
      root.classList.remove('is-open');
      root.setAttribute('hidden', 'hidden');
      document.documentElement.classList.remove('ngt-grid-modal-open');
      var body = root.querySelector('.ngt-grid-modal__body');
      if (body) body.innerHTML = '';
      if (lastFocus && lastFocus.focus) {
        try { lastFocus.focus(); } catch (e) { /* noop */ }
      }
    }

    function isOpen() {
      return !!(root && root.classList.contains('is-open'));
    }

    return { open: open, setContent: setContent, close: close, isOpen: isOpen };
  })();

  window.NGTAdminGridModal = GridModal;

  function wireCrudForm(body, entity, id, onSaved) {
    var form = body.querySelector('.ngt-admin-crud-form');
    if (!form) return;
    form.addEventListener('submit', function (ev) {
      ev.preventDefault();
      var data = {};
      Array.prototype.forEach.call(form.elements, function (field) {
        if (!field.name) return;
        data[field.name] = field.value;
      });
      api('/entities/' + entity + '/' + id, { method: 'PUT', body: JSON.stringify(data) }).then(function (res) {
        if (res && res.ok === false) return;
        GridModal.close();
        if (typeof onSaved === 'function') onSaved();
      });
    });
  }

  function openEntityRecord(entity, id, label, onSaved) {
    GridModal.open({
      title: (label || entity || 'Record') + ' #' + id,
      html: '<p class="ngt-grid-modal__loading">Loading…</p>',
    });
    return api('/entities/' + entity + '/' + id).then(function (res) {
      if (!res || !res.ok) {
        GridModal.setContent('<p>Could not load this record.</p>');
        return;
      }
      var html = res.detail_html || ('<pre>' + esc(JSON.stringify(res.item, null, 2)) + '</pre>');
      var body = GridModal.setContent(html);
      wireCrudForm(body, entity, id, onSaved);
    }).catch(function () {
      GridModal.setContent('<p>Could not load this record.</p>');
    });
  }

  function openUrlInModal(url, title) {
    var safe = esc(url);
    GridModal.open({
      title: title || 'Details',
      html: '<iframe class="ngt-grid-modal__frame" title="' + esc(title || 'Details') + '" src="' + safe + '" loading="lazy"></iframe>',
    });
  }

  /**
   * Global: any grid "Open" control opens a modal (entity API or same-origin URL).
   */
  document.addEventListener('click', function (e) {
    var btn = e.target.closest('.ngt-grid-open, [data-ngt-grid-open], a.ngt-grid-open');
    if (!btn) {
      // Plain "Open" link/button inside a table Actions cell.
      var candidate = e.target.closest('table td a, table td button');
      if (!candidate) return;
      var label = (candidate.textContent || '').replace(/\s+/g, ' ').trim().toLowerCase();
      if (label !== 'open') return;
      if (candidate.closest('.ngt-grid-modal')) return;
      btn = candidate;
    }

    // Let checkbox / other controls alone.
    if (btn.classList.contains('ngt-grid-check') || btn.classList.contains('ngt-grid-check-all')) return;

    var grid = btn.closest('.ngt-admin-grid[data-entity]');
    var tr = btn.closest('tr[data-id]');
    var entity = (grid && grid.getAttribute('data-entity')) || btn.getAttribute('data-entity') || '';
    var id = (tr && tr.getAttribute('data-id')) || btn.getAttribute('data-id') || '';

    if (entity && id) {
      e.preventDefault();
      e.stopPropagation();
      var confLabel = entity;
      if (grid) {
        try {
          var conf = JSON.parse(grid.getAttribute('data-config') || '{}');
          confLabel = conf.label || entity;
        } catch (err) { /* noop */ }
      }
      openEntityRecord(entity, id, confLabel, function () {
        if (grid && typeof grid._ngtReload === 'function') grid._ngtReload();
      });
      return;
    }

    var href = btn.getAttribute('href') || btn.getAttribute('data-url') || '';
    if (href && href !== '#' && href.indexOf('javascript:') !== 0) {
      // Same-origin admin URLs → iframe modal; external → new tab.
      try {
        var u = new URL(href, window.location.origin);
        if (u.origin === window.location.origin) {
          e.preventDefault();
          e.stopPropagation();
          openUrlInModal(u.href, (btn.textContent || 'Open').trim());
          return;
        }
      } catch (err2) { /* fall through */ }
    }
  }, true);

  if (!mounts.length) return;

  mounts.forEach(function (el) {
    var entity = el.getAttribute('data-entity');
    var conf = {};
    try { conf = JSON.parse(el.getAttribute('data-config') || '{}'); } catch (e) { conf = {}; }
    var page = 1;
    var perPage = 25;
    var search = '';
    var sortKey = 'id';
    var sortDir = 'desc';
    var hiddenCols = {};
    var rows = [];
    var total = 0;
    var selected = {};

    var thead = el.querySelector('thead');
    var tbody = el.querySelector('tbody');
    var meta = el.querySelector('.ngt-admin-grid__meta');
    var searchInput = el.querySelector('.ngt-admin-grid__search');

    function columns() {
      return (conf.columns || []).filter(function (c) { return !hiddenCols[c.key]; });
    }

    function renderHead() {
      thead.innerHTML = '<tr><th><input type="checkbox" class="ngt-grid-check-all" /></th>' +
        columns().map(function (c) {
          return '<th data-sort="' + esc(c.key) + '">' + esc(c.label || c.key) + '</th>';
        }).join('') + '<th>Actions</th></tr>';
    }

    function renderBody() {
      if (!rows.length) {
        tbody.innerHTML = '<tr><td colspan="' + (columns().length + 2) + '">No records</td></tr>';
        return;
      }
      tbody.innerHTML = rows.map(function (r) {
        var id = r.id;
        return '<tr data-id="' + id + '" class="' + (selected[id] ? 'is-selected' : '') + '">' +
          '<td><input type="checkbox" class="ngt-grid-check" ' + (selected[id] ? 'checked' : '') + ' /></td>' +
          columns().map(function (c) { return '<td>' + esc(r[c.key]) + '</td>'; }).join('') +
          '<td><button type="button" class="button-link ngt-grid-open" data-entity="' + esc(entity) + '" data-id="' + esc(id) + '" aria-haspopup="dialog">Open</button></td></tr>';
      }).join('');
      if (meta) meta.textContent = 'Showing ' + rows.length + ' of ' + total + ' · page ' + page;
    }

    function load() {
      var q = '/entities/' + encodeURIComponent(entity) + '?page=' + page + '&per_page=' + perPage +
        '&search=' + encodeURIComponent(search);
      return api(q).then(function (res) {
        if (!res.ok) return;
        rows = res.rows || [];
        total = res.total || 0;
        if (sortKey) {
          rows.sort(function (a, b) {
            var av = a[sortKey], bv = b[sortKey];
            if (av === bv) return 0;
            return (av > bv ? 1 : -1) * (sortDir === 'asc' ? 1 : -1);
          });
        }
        renderHead();
        renderBody();
      });
    }

    el._ngtReload = load;

    if (searchInput) {
      var t;
      searchInput.addEventListener('input', function () {
        clearTimeout(t);
        t = setTimeout(function () { search = searchInput.value; page = 1; load(); }, 250);
      });
    }

    var prev = el.querySelector('.ngt-admin-grid__prev');
    var next = el.querySelector('.ngt-admin-grid__next');
    if (prev) {
      prev.addEventListener('click', function () {
        if (page > 1) { page--; load(); }
      });
    }
    if (next) {
      next.addEventListener('click', function () {
        if (page * perPage < total) { page++; load(); }
      });
    }

    thead.addEventListener('click', function (e) {
      var th = e.target.closest('[data-sort]');
      if (!th) return;
      var key = th.getAttribute('data-sort');
      if (sortKey === key) sortDir = sortDir === 'asc' ? 'desc' : 'asc';
      else { sortKey = key; sortDir = 'asc'; }
      renderBody();
    });

    tbody.addEventListener('click', function (e) {
      var tr = e.target.closest('tr[data-id]');
      if (!tr) return;
      var id = tr.getAttribute('data-id');
      if (e.target.classList.contains('ngt-grid-check')) {
        selected[id] = e.target.checked;
        tr.classList.toggle('is-selected', e.target.checked);
        return;
      }
      var checkAll = e.target.classList.contains('ngt-grid-check-all');
      if (checkAll) return;
      // Open handled by global capture listener.
      if (e.target.closest('.ngt-grid-open')) return;
    });

    thead.addEventListener('change', function (e) {
      if (!e.target.classList.contains('ngt-grid-check-all')) return;
      var on = e.target.checked;
      rows.forEach(function (r) { selected[r.id] = on; });
      renderBody();
    });

    var exportBtn = el.querySelector('.ngt-admin-grid__export');
    var formatSel = el.querySelector('.ngt-admin-grid__export-format');
    if (exportBtn) {
      exportBtn.addEventListener('click', function () {
        var ids = Object.keys(selected).filter(function (k) { return selected[k]; }).map(Number);
        api('/entities/' + entity + '/export', {
          method: 'POST',
          body: JSON.stringify({ format: (formatSel && formatSel.value) || 'csv', ids: ids, search: search })
        }).then(function (res) {
          if (!res.ok) return;
          var blob = new Blob([res.content || ''], { type: res.mime || 'text/plain' });
          var a = document.createElement('a');
          a.href = URL.createObjectURL(blob);
          a.download = res.filename || (entity + '.csv');
          a.click();
          URL.revokeObjectURL(a.href);
        });
      });
    }

    var colsBtn = el.querySelector('.ngt-admin-grid__cols');
    if (colsBtn) {
      colsBtn.addEventListener('click', function () {
        var menu = document.createElement('div');
        menu.className = 'ngt-admin-grid__col-menu';
        menu.innerHTML = (conf.columns || []).map(function (c) {
          return '<label><input type="checkbox" data-col="' + esc(c.key) + '"' + (hiddenCols[c.key] ? '' : ' checked') + ' /> ' + esc(c.label || c.key) + '</label>';
        }).join('<br/>');
        colsBtn.parentNode.appendChild(menu);
        menu.addEventListener('change', function (ev) {
          var cb = ev.target.closest('[data-col]');
          if (!cb) return;
          hiddenCols[cb.getAttribute('data-col')] = !cb.checked;
          renderHead();
          renderBody();
        });
        setTimeout(function () {
          document.addEventListener('click', function once(ev) {
            if (!menu.contains(ev.target) && ev.target !== colsBtn) {
              menu.remove();
              document.removeEventListener('click', once);
            }
          });
        }, 0);
      });
    }

    load();
  });
})();
