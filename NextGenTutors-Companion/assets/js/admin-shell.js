/**
 * Unified admin shell — search + favorites persistence.
 */
(function () {
  'use strict';

  var cfg = window.ngtAdminShell || {};
  var input = document.getElementById('ngt-admin-search');
  var results = document.getElementById('ngt-admin-search-results');
  var timer = null;

  function esc(s) {
    var d = document.createElement('div');
    d.textContent = s == null ? '' : String(s);
    return d.innerHTML;
  }

  function renderHits(hits) {
    if (!results) return;
    if (!hits || !hits.length) {
      results.innerHTML = '<div class="ngt-admin-search-meta" style="padding:12px" data-testid="ngt-admin-search-empty">' +
        esc((cfg.i18n && cfg.i18n.noResults) || 'No results') + '</div>';
      results.hidden = false;
      return;
    }
    results.innerHTML = hits.map(function (h) {
      return '<a role="option" href="' + esc(h.url) + '" data-testid="ngt-admin-search-hit"><strong>' + esc(h.title) + '</strong>' +
        '<span class="ngt-admin-search-meta">' + esc(h.module || '') + ' · ' + esc(h.slug || '') + '</span></a>';
    }).join('');
    results.hidden = false;
  }

  function parseHits(payload) {
    if (!payload) return [];
    if (Array.isArray(payload.results)) return payload.results;
    if (payload.data && Array.isArray(payload.data.results)) return payload.data.results;
    return [];
  }

  function searchLocal(q) {
    var index = Array.isArray(cfg.index) ? cfg.index : [];
    var needle = String(q || '').toLowerCase();
    if (!needle) return [];
    var hits = [];
    for (var i = 0; i < index.length; i++) {
      var row = index[i] || {};
      var hay = (Array.isArray(row.keywords) ? row.keywords.join(' ') : '') + ' ' +
        (row.title || '') + ' ' + (row.module || '') + ' ' + (row.slug || '');
      if (hay.toLowerCase().indexOf(needle) !== -1) {
        hits.push({
          slug: row.slug || '',
          title: row.title || row.slug || '',
          url: row.url || '#',
          module: row.module || ''
        });
      }
      if (hits.length >= 25) break;
    }
    return hits;
  }

  function searchAjax(q) {
    var ajaxUrl = cfg.ajaxUrl || (typeof window.ajaxurl === 'string' ? window.ajaxurl : '');
    if (!ajaxUrl) {
      renderHits(searchLocal(q));
      return;
    }
    var url = ajaxUrl +
      (ajaxUrl.indexOf('?') >= 0 ? '&' : '?') +
      'action=ngt_admin_search&q=' + encodeURIComponent(q) +
      '&nonce=' + encodeURIComponent(cfg.ajaxNonce || '');
    fetch(url, { credentials: 'same-origin' })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        var hits = parseHits(data.success ? data : { results: parseHits(data) });
        renderHits(hits.length ? hits : searchLocal(q));
      })
      .catch(function () {
        renderHits(searchLocal(q));
      });
  }

  function search(q) {
    if (!q || q.length < 2) {
      if (results) results.hidden = true;
      return;
    }
    // Prefer embedded index (works even when REST/ajax host differs in Docker).
    var local = searchLocal(q);
    if (local.length) {
      renderHits(local);
      return;
    }
    var root = (cfg.restRoot || '').replace(/\/$/, '');
    if (!root) {
      searchAjax(q);
      return;
    }
    var url = root + '/search?q=' + encodeURIComponent(q);
    fetch(url, {
      credentials: 'same-origin',
      headers: { 'X-WP-Nonce': cfg.nonce || '' }
    }).then(function (r) {
      if (!r.ok) {
        throw new Error('search http ' + r.status);
      }
      return r.json();
    }).then(function (data) {
      var hits = parseHits(data);
      renderHits(hits.length ? hits : searchLocal(q));
    }).catch(function () {
      // REST may fail under cookie/nonce/permalink edge cases — fall back to admin-ajax / local.
      searchAjax(q);
    });
  }

  if (input) {
    input.addEventListener('input', function () {
      clearTimeout(timer);
      var q = input.value;
      timer = setTimeout(function () { search(q); }, 200);
    });
    input.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && results) results.hidden = true;
    });
    document.addEventListener('click', function (e) {
      if (!results || results.hidden) return;
      if (e.target === input || results.contains(e.target)) return;
      results.hidden = true;
    });
  }

  // Persist collapsed submenu preference (WP already handles some of this).
  try {
    var key = 'ngtAdminTheme';
    if (localStorage.getItem(key) === 'dark') {
      document.body.classList.add('ngt-admin-dark');
    }
  } catch (err) { /* ignore */ }
})();
