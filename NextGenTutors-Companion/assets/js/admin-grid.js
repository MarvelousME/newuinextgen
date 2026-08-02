/**
 * Enterprise Data Grid — list/detail/export against /admin/entities/{key}.
 */
(function () {
  'use strict';
  var cfg = window.ngtAdminGrid || {};
  var mounts = document.querySelectorAll('.ngt-admin-grid[data-entity]');
  if (!mounts.length) return;

  function api(path, opts) {
    return fetch((cfg.restRoot || '').replace(/\/$/, '') + path, Object.assign({
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': cfg.nonce || '' }
    }, opts || {})).then(function (r) { return r.json(); });
  }

  function esc(s) {
    var d = document.createElement('div');
    d.textContent = s == null ? '' : String(s);
    return d.innerHTML;
  }

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
    var detail = el.querySelector('.ngt-admin-grid__detail');
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
          '<td><button type="button" class="button-link ngt-grid-open">Open</button></td></tr>';
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

    if (searchInput) {
      var t;
      searchInput.addEventListener('input', function () {
        clearTimeout(t);
        t = setTimeout(function () { search = searchInput.value; page = 1; load(); }, 250);
      });
    }

    el.querySelector('.ngt-admin-grid__prev').addEventListener('click', function () {
      if (page > 1) { page--; load(); }
    });
    el.querySelector('.ngt-admin-grid__next').addEventListener('click', function () {
      if (page * perPage < total) { page++; load(); }
    });

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
      if (!e.target.classList.contains('ngt-grid-open') && e.target.tagName !== 'TD') return;
      api('/entities/' + entity + '/' + id).then(function (res) {
        if (!res.ok || !detail) return;
        detail.hidden = false;
        detail.innerHTML = res.detail_html || ('<pre>' + esc(JSON.stringify(res.item, null, 2)) + '</pre>');
        var form = detail.querySelector('.ngt-admin-crud-form');
        if (form) {
          form.addEventListener('submit', function (ev) {
            ev.preventDefault();
            var data = {};
            Array.prototype.forEach.call(form.elements, function (field) {
              if (!field.name) return;
              data[field.name] = field.value;
            });
            api('/entities/' + entity + '/' + id, { method: 'PUT', body: JSON.stringify(data) }).then(function () {
              load();
            });
          });
        }
      });
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
        menu.addEventListener('change', function (e) {
          var cb = e.target.closest('[data-col]');
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
