/**
 * Capability sidebar — tree render + DnD layout persistence.
 */
(function () {
  'use strict';
  var cfg = window.ngtAdminNav || {};
  var root = document.getElementById('ngt-admin-nav-tree');
  var panel = document.getElementById('ngt-admin-nav');
  if (!root || !cfg.tree) return;

  var state = cfg.tree;
  var undo = [];
  var editMode = false;

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

  function itemHtml(node, depth) {
    depth = depth || 0;
    var kids = (node.children || []).map(function (c) { return itemHtml(c, depth + 1); }).join('');
    if (node.type === 'category') {
      return '<details class="ngt-nav-cat" data-id="' + esc(node.id) + '"' + (node.collapsed ? '' : ' open') + '>' +
        '<summary draggable="true">' + esc(node.label) + '</summary>' +
        '<div class="ngt-nav-children">' + kids + '</div></details>';
    }
    var cls = 'ngt-admin-nav__item' + (node.placeholder ? ' is-placeholder' : '');
    var badge = node.badge ? '<span class="badge">' + node.badge + '</span>' : '';
    var inner = '<span>' + esc(node.label) + '</span>' + badge;
    if (node.placeholder || !node.url) {
      return '<div class="' + cls + '" data-id="' + esc(node.id) + '" draggable="true" title="' + esc((cfg.i18n && cfg.i18n.coming) || 'Coming soon') + '">' + inner + '</div>' + kids;
    }
    return '<a class="' + cls + '" href="' + esc(node.url) + '" data-id="' + esc(node.id) + '" draggable="true">' + inner + '</a>' +
      (kids ? '<div class="ngt-nav-children" style="margin-left:12px">' + kids + '</div>' : '');
  }

  function render() {
    var fav = (state.favorites || []).map(function (n) { return itemHtml(n); }).join('');
    var tree = (state.tree || []).map(function (n) { return itemHtml(n); }).join('');
    root.innerHTML =
      (fav ? '<div class="ngt-nav-fav"><strong>' + esc((cfg.i18n && cfg.i18n.favorites) || 'Favorites') + '</strong>' + fav + '</div>' : '') +
      tree;
  }

  function collectOrder() {
    return Array.prototype.map.call(root.querySelectorAll('[data-id]'), function (el) {
      return el.getAttribute('data-id');
    });
  }

  function saveLayout(patch) {
    undo.push(JSON.parse(JSON.stringify(state.layout || {})));
    if (undo.length > 20) undo.shift();
    var layout = Object.assign({}, state.layout || {}, patch || {}, { order: collectOrder() });
    return api('/nav/layout', { method: 'POST', body: JSON.stringify({ layout: layout, scope: 'user' }) }).then(function (res) {
      state = res.tree || state;
      render();
      return res;
    });
  }

  root.addEventListener('dragstart', function (e) {
    if (!editMode) return;
    var el = e.target.closest('[data-id]');
    if (!el) return;
    e.dataTransfer.setData('text/plain', el.getAttribute('data-id'));
  });
  root.addEventListener('dragover', function (e) { if (editMode) e.preventDefault(); });
  root.addEventListener('drop', function (e) {
    if (!editMode) return;
    e.preventDefault();
    var from = e.dataTransfer.getData('text/plain');
    var target = e.target.closest('[data-id]');
    if (!from || !target) return;
    var order = collectOrder().filter(function (id) { return id !== from; });
    var idx = order.indexOf(target.getAttribute('data-id'));
    order.splice(idx < 0 ? order.length : idx, 0, from);
    saveLayout({ order: order });
  });

  var editBtn = document.getElementById('ngt-admin-nav-edit');
  if (editBtn) {
    editBtn.addEventListener('click', function () {
      editMode = !editMode;
      if (panel) panel.classList.toggle('is-edit', editMode);
      editBtn.textContent = editMode ? 'Done' : ((cfg.i18n && cfg.i18n.manage) || 'Customize');
    });
  }
  var undoBtn = document.getElementById('ngt-admin-nav-undo');
  if (undoBtn) {
    undoBtn.addEventListener('click', function () {
      var prev = undo.pop();
      if (!prev) return;
      api('/nav/layout', { method: 'POST', body: JSON.stringify({ layout: prev }) }).then(function (res) {
        state = res.tree || state;
        render();
      });
    });
  }
  var resetBtn = document.getElementById('ngt-admin-nav-reset');
  if (resetBtn) {
    resetBtn.addEventListener('click', function () {
      api('/nav/reset', { method: 'POST', body: '{}' }).then(function (res) {
        state = res.tree || state;
        render();
      });
    });
  }

  root.addEventListener('click', function (e) {
    var star = e.target.closest('[data-fav]');
    if (!star) return;
    e.preventDefault();
    var id = star.getAttribute('data-fav');
    var favs = (state.layout && state.layout.favorites) ? state.layout.favorites.slice() : [];
    var i = favs.indexOf(id);
    if (i >= 0) favs.splice(i, 1); else favs.push(id);
    saveLayout({ favorites: favs });
  });

  render();
})();
