/**
 * Floating Notification Centre.
 */
(function () {
  'use strict';
  var cfg = window.ngtAdminNotif || {};
  var fab = document.getElementById('ngt-notif-fab');
  var drawer = document.getElementById('ngt-notif-drawer');
  var list = document.getElementById('ngt-notif-list');
  if (!fab || !drawer || !list) return;

  var items = cfg.items || [];

  function api(path, body) {
    return fetch((cfg.restRoot || '').replace(/\/$/, '') + path, {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': cfg.nonce || '' },
      body: JSON.stringify(body || {})
    }).then(function (r) { return r.json(); });
  }

  function esc(s) {
    var d = document.createElement('div');
    d.textContent = s == null ? '' : String(s);
    return d.innerHTML;
  }

  function isOpen() {
    return drawer.classList.contains('is-open') && !drawer.hasAttribute('hidden');
  }

  function openDrawer() {
    drawer.classList.add('is-open');
    drawer.removeAttribute('hidden');
    fab.setAttribute('aria-expanded', 'true');
  }

  function closeDrawer() {
    drawer.classList.remove('is-open');
    drawer.setAttribute('hidden', 'hidden');
    drawer.style.display = 'none';
    fab.setAttribute('aria-expanded', 'false');
  }

  function toggleDrawer() {
    if (isOpen()) closeDrawer();
    else openDrawer();
  }

  function unreadCount() {
    return items.filter(function (i) {
      return !i.read && (!i.snooze_until || i.snooze_until < Date.now() / 1000);
    }).length;
  }

  function updateBadge() {
    var el = fab.querySelector('.ngt-notif-fab__count');
    if (!el) return;
    var n = unreadCount();
    el.textContent = String(n);
    el.setAttribute('data-count', String(n));
    if (n > 0) fab.classList.add('has-new');
  }

  function render() {
    var q = (document.getElementById('ngt-notif-search') || {}).value || '';
    var sev = (document.getElementById('ngt-notif-filter') || {}).value || '';
    q = String(q).toLowerCase();
    var rows = items.filter(function (i) {
      if (sev && i.severity !== sev) return false;
      if (!q) return true;
      return (i.title + ' ' + i.message + ' ' + i.plugin).toLowerCase().indexOf(q) >= 0;
    });
    list.innerHTML = rows.map(function (i) {
      return '<article class="ngt-notif-item' + (i.read ? '' : ' is-unread') + '" data-id="' + esc(i.id) + '">' +
        '<div class="ngt-notif-item__sev">' + esc(i.severity) + ' · ' + esc(i.plugin) + '</div>' +
        '<strong>' + esc(i.title) + '</strong>' +
        '<p>' + esc(i.message) + '</p>' +
        '<p><button type="button" class="button-link" data-op="ack">Ack</button> ' +
        '<button type="button" class="button-link" data-op="snooze">Snooze</button> ' +
        '<button type="button" class="button-link" data-op="dismiss">Dismiss</button></p></article>';
    }).join('') || '<p style="padding:12px;color:#64748b">No notifications</p>';
    updateBadge();
  }

  function ingestNotices() {
    var notices = document.querySelectorAll('#wpbody-content .notice, .wrap > .notice');
    notices.forEach(function (n) {
      if (n.getAttribute('data-ngt-ingested')) return;
      n.setAttribute('data-ngt-ingested', '1');
      var text = (n.textContent || '').trim();
      if (!text) return;
      var severity = 'info';
      if (n.classList.contains('notice-error')) severity = 'error';
      else if (n.classList.contains('notice-warning')) severity = 'warning';
      else if (n.classList.contains('notice-success')) severity = 'success';
      api('/notifications', { op: 'ingest', title: 'System message', message: text.slice(0, 500), severity: severity, plugin: 'wordpress' })
        .then(function (res) { items = res.items || items; render(); });
    });
  }

  // Start closed (CSS display:flex was overriding [hidden]).
  closeDrawer();

  fab.addEventListener('click', function (e) {
    e.preventDefault();
    e.stopPropagation();
    toggleDrawer();
  });

  var closeBtn = document.getElementById('ngt-notif-close');
  if (closeBtn) {
    closeBtn.addEventListener('click', function (e) {
      e.preventDefault();
      e.stopPropagation();
      closeDrawer();
    });
  }

  // Click outside drawer/FAB closes.
  document.addEventListener('click', function (e) {
    if (!isOpen()) return;
    if (drawer.contains(e.target) || fab.contains(e.target)) return;
    closeDrawer();
  });

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && isOpen()) closeDrawer();
  });

  list.addEventListener('click', function (e) {
    var btn = e.target.closest('[data-op]');
    if (!btn) return;
    var item = e.target.closest('[data-id]');
    if (!item) return;
    api('/notifications', { op: btn.getAttribute('data-op'), ids: [item.getAttribute('data-id')] })
      .then(function (res) { items = res.items || items; render(); });
  });

  var ackAll = document.getElementById('ngt-notif-ack-all');
  if (ackAll) {
    ackAll.addEventListener('click', function () {
      var ids = items.map(function (i) { return i.id; });
      api('/notifications', { op: 'ack', ids: ids }).then(function (res) { items = res.items || items; render(); });
    });
  }
  ['ngt-notif-search', 'ngt-notif-filter'].forEach(function (id) {
    var el = document.getElementById(id);
    if (el) el.addEventListener('input', render);
    if (el) el.addEventListener('change', render);
  });

  ingestNotices();
  render();
})();
