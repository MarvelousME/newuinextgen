/**
 * Notification Centre — modal + capture WP admin notices off-screen.
 */
(function () {
  'use strict';

  var cfg = window.ngtAdminNotif || {};
  var root = document.getElementById('ngt-notif-root');
  var fab = document.getElementById('ngt-notif-fab');
  var modal = document.getElementById('ngt-notif-modal');
  var drawer = document.getElementById('ngt-notif-drawer');
  var list = document.getElementById('ngt-notif-list');
  var vault = document.getElementById('ngt-notif-vault');
  if (!fab || !modal || !drawer || !list) return;

  var items = Array.isArray(cfg.items) ? cfg.items.slice() : [];
  var pending = [];
  var flushTimer = null;
  var seenFingerprints = {};

  items.forEach(function (i) {
    if (i && i.fingerprint) seenFingerprints[i.fingerprint] = true;
  });

  function api(path, body) {
    var base = (cfg.restRoot || '').replace(/\/$/, '');
    return fetch(base + path, {
      method: 'POST',
      credentials: 'same-origin',
      headers: {
        'Content-Type': 'application/json',
        'X-WP-Nonce': cfg.nonce || ''
      },
      body: JSON.stringify(body || {})
    }).then(function (r) {
      if (!r.ok) throw new Error('notif_http_' + r.status);
      return r.json();
    });
  }

  function esc(s) {
    var d = document.createElement('div');
    d.textContent = s == null ? '' : String(s);
    return d.innerHTML;
  }

  function fingerprint(text) {
    var s = String(text || '').toLowerCase().replace(/\s+/g, ' ').trim();
    var h = 0;
    for (var i = 0; i < s.length; i++) {
      h = ((h << 5) - h) + s.charCodeAt(i);
      h |= 0;
    }
    return 'fp' + Math.abs(h);
  }

  function isOpen() {
    return modal.classList.contains('is-open') && !modal.hasAttribute('hidden');
  }

  function openModal() {
    modal.classList.add('is-open');
    modal.removeAttribute('hidden');
    modal.style.display = 'flex';
    drawer.classList.add('is-open');
    drawer.removeAttribute('hidden');
    drawer.style.display = 'flex';
    fab.setAttribute('aria-expanded', 'true');
    document.documentElement.classList.add('ngt-notif-modal-open');
    try {
      drawer.focus();
    } catch (e) { /* ignore */ }
  }

  function closeModal() {
    modal.classList.remove('is-open');
    modal.setAttribute('hidden', 'hidden');
    modal.style.display = 'none';
    drawer.classList.remove('is-open');
    drawer.style.display = '';
    fab.setAttribute('aria-expanded', 'false');
    document.documentElement.classList.remove('ngt-notif-modal-open');
  }

  function toggleModal() {
    if (isOpen()) closeModal();
    else openModal();
  }

  function unreadCount() {
    return items.filter(function (i) {
      return !i.read && (!i.snooze_until || i.snooze_until < Date.now() / 1000);
    }).length;
  }

  function updateBadge() {
    var n = unreadCount();
    var el = fab.querySelector('.ngt-notif-fab__count');
    if (el) {
      el.textContent = String(n);
      el.setAttribute('data-count', String(n));
    }
    fab.classList.toggle('has-new', n > 0);

    var ab = document.querySelector('#wp-admin-bar-ngt-notifications .ngt-notif-ab-count, #wp-admin-bar-ngt-notifications .awaiting-mod');
    if (ab) {
      ab.textContent = String(n);
      ab.style.display = n > 0 ? '' : 'none';
    }
  }

  function bodyHtml(i) {
    if (i.html) {
      return '<div class="ngt-notif-item__html">' + i.html + '</div>';
    }
    return '<p>' + esc(i.message || '') + '</p>';
  }

  function render() {
    var q = (document.getElementById('ngt-notif-search') || {}).value || '';
    var sev = (document.getElementById('ngt-notif-filter') || {}).value || '';
    q = String(q).toLowerCase();
    var rows = items.filter(function (i) {
      if (i.snooze_until && i.snooze_until > Date.now() / 1000) return false;
      if (sev && i.severity !== sev) return false;
      if (!q) return true;
      return ((i.title || '') + ' ' + (i.message || '') + ' ' + (i.plugin || '')).toLowerCase().indexOf(q) >= 0;
    });
    list.innerHTML = rows.map(function (i) {
      return '<article class="ngt-notif-item' + (i.read ? '' : ' is-unread') + '" data-id="' + esc(i.id) + '">' +
        '<div class="ngt-notif-item__sev">' + esc(i.severity || 'info') + ' · ' + esc(i.plugin || 'wordpress') + '</div>' +
        '<strong>' + esc(i.title || 'Notice') + '</strong>' +
        bodyHtml(i) +
        '<p class="ngt-notif-item__actions">' +
        '<button type="button" class="button-link" data-op="ack">Ack</button> ' +
        '<button type="button" class="button-link" data-op="snooze">Snooze</button> ' +
        '<button type="button" class="button-link" data-op="dismiss">Dismiss</button></p></article>';
    }).join('') || ('<p class="ngt-notif-empty">' + esc((cfg.i18n && cfg.i18n.empty) || 'No notifications') + '</p>');
    updateBadge();
  }

  function severityFromEl(n) {
    var cls = (n.className || '').toLowerCase();
    if (/\berror\b|notice-error|critical/.test(cls)) return 'error';
    if (/\bwarning\b|notice-warning|update-nag/.test(cls)) return 'warning';
    if (/\bsuccess\b|notice-success|updated\b/.test(cls)) return 'success';
    return 'info';
  }

  function pluginGuess(n, text) {
    var t = (text || '').toLowerCase();
    if (/monsterinsights|google analytics/.test(t)) return 'monsterinsights';
    if (/mailchimp/.test(t)) return 'mailchimp';
    if (/greenshift/.test(t)) return 'greenshift';
    if (/action scheduler/.test(t)) return 'action-scheduler';
    if (/masterstudy|stm[\s_-]?lms/.test(t)) return 'masterstudy';
    if (/woocommerce/.test(t)) return 'woocommerce';
    if (/elementor/.test(t)) return 'elementor';
    var id = (n.id || '') + ' ' + (n.className || '');
    if (/monsterinsights/i.test(id)) return 'monsterinsights';
    if (/woocommerce/i.test(id)) return 'woocommerce';
    return 'wordpress';
  }

  function isExempt(n) {
    if (!n || !n.classList) return true;
    if (n.classList.contains('ngt-notif-exempt')) return true;
    if (n.closest && n.closest('#ngt-notif-root, #ngt-notif-vault, .ngt-notif-drawer, .ngt-notif-modal')) return true;
    if (n.classList.contains('hidden') && n.getAttribute('aria-hidden') === 'true') return true;
    // Keep inline field errors inside forms visible.
    if (n.classList.contains('inline') && n.closest('form')) return true;
    return false;
  }

  function noticeSelector() {
    return [
      '#wpbody-content > .notice',
      '#wpbody-content > .update-nag',
      '#wpbody-content > .updated',
      '#wpbody-content > .error',
      '#wpbody-content > .e-notice',
      '#wpbody-content > .woocommerce-message',
      '#wpbody-content > .woocommerce-info',
      '#wpbody-content > .woocommerce-error',
      '#wpbody-content > .monsterinsights-notice',
      '#wpbody-content > .monsterinsights-box',
      '#wpbody-content > div[class*="monsterinsights-"]',
      '#wpbody-content > .ms_lms_notice',
      '#wpbody-content > .stm-lms-notice',
      '#wpbody-content > .fs-notice',
      '#wpbody-content > .jetpack-jitm-message',
      '.wrap > .notice',
      '.wrap > .update-nag',
      '.wrap > .updated',
      '.wrap > .error',
      '#wpbody-content .notice:not(.ngt-notif-exempt)',
      '#wpbody-content .update-nag'
    ].join(',');
  }

  function queueIngest(payload) {
    if (!payload || !payload.fingerprint || seenFingerprints[payload.fingerprint]) return;
    seenFingerprints[payload.fingerprint] = true;
    pending.push(payload);
    if (flushTimer) clearTimeout(flushTimer);
    flushTimer = setTimeout(flushIngest, 200);
  }

  function flushIngest() {
    flushTimer = null;
    if (!pending.length) return;
    var batch = pending.slice();
    pending = [];
    api('/notifications', { op: 'ingest_many', items: batch })
      .then(function (res) {
        if (res && Array.isArray(res.items)) {
          items = res.items;
          render();
        }
      })
      .catch(function () {
        // Keep local optimistic copies if REST fails.
        batch.forEach(function (b) {
          items.unshift({
            id: 'local_' + b.fingerprint,
            title: b.title,
            message: b.message,
            html: b.html,
            severity: b.severity,
            plugin: b.plugin,
            fingerprint: b.fingerprint,
            created: Math.floor(Date.now() / 1000),
            read: false
          });
        });
        render();
      });
  }

  function captureNotice(n) {
    if (!n || n.nodeType !== 1) return;
    if (n.getAttribute('data-ngt-ingested') === '1') return;
    if (isExempt(n)) return;

    var text = (n.textContent || '').replace(/\s+/g, ' ').trim();
    if (!text || text.length < 8) return;

    n.setAttribute('data-ngt-ingested', '1');
    if (vault && n.parentNode !== vault) {
      try {
        vault.appendChild(n);
      } catch (e) {
        n.style.cssText = 'position:absolute!important;left:-99999px!important;height:1px!important;overflow:hidden!important;';
      }
    }

    var fp = fingerprint(text);
    var html = '';
    try {
      html = (n.innerHTML || '').trim().slice(0, 4000);
    } catch (e2) {
      html = '';
    }

    queueIngest({
      title: (cfg.i18n && cfg.i18n.admin) || 'Admin notice',
      message: text.slice(0, 800),
      html: html,
      severity: severityFromEl(n),
      plugin: pluginGuess(n, text),
      fingerprint: fp
    });
  }

  function ingestNotices() {
    try {
      document.querySelectorAll(noticeSelector()).forEach(captureNotice);
    } catch (e) { /* ignore selector errors */ }
  }

  // Start closed — never leave inline display:none without clearing on open.
  closeModal();

  fab.addEventListener('click', function (e) {
    e.preventDefault();
    e.stopPropagation();
    toggleModal();
  });

  modal.addEventListener('click', function (e) {
    var t = e.target;
    if (t && t.getAttribute && t.getAttribute('data-ngt-notif-close') === '1') {
      e.preventDefault();
      closeModal();
    }
  });

  document.addEventListener('click', function (e) {
    var ab = e.target && e.target.closest && e.target.closest('#wp-admin-bar-ngt-notifications a, .ngt-notif-admin-bar a');
    if (!ab) return;
    e.preventDefault();
    e.stopPropagation();
    openModal();
  }, true);

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && isOpen()) closeModal();
  });

  list.addEventListener('click', function (e) {
    var btn = e.target.closest('[data-op]');
    if (!btn) return;
    var item = e.target.closest('[data-id]');
    if (!item) return;
    api('/notifications', { op: btn.getAttribute('data-op'), ids: [item.getAttribute('data-id')] })
      .then(function (res) {
        items = (res && res.items) || items;
        render();
      });
  });

  var ackAll = document.getElementById('ngt-notif-ack-all');
  if (ackAll) {
    ackAll.addEventListener('click', function () {
      var ids = items.map(function (i) { return i.id; });
      api('/notifications', { op: 'ack', ids: ids }).then(function (res) {
        items = (res && res.items) || items;
        render();
      });
    });
  }

  ['ngt-notif-search', 'ngt-notif-filter'].forEach(function (id) {
    var el = document.getElementById(id);
    if (!el) return;
    el.addEventListener('input', render);
    el.addEventListener('change', render);
  });

  ingestNotices();
  render();

  // Late-injected plugin notices (MonsterInsights, MasterStudy, etc.).
  if (window.MutationObserver) {
    var obs = new MutationObserver(function () {
      ingestNotices();
    });
    var bodyContent = document.getElementById('wpbody-content');
    if (bodyContent) {
      obs.observe(bodyContent, { childList: true, subtree: true });
    }
  }

  // Second pass after slow plugin bootstraps.
  setTimeout(ingestNotices, 800);
  setTimeout(ingestNotices, 2500);
})();
