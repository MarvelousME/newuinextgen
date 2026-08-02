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
      results.innerHTML = '<div class="ngt-admin-search-meta" style="padding:12px">' + esc((cfg.i18n && cfg.i18n.noResults) || 'No results') + '</div>';
      results.hidden = false;
      return;
    }
    results.innerHTML = hits.map(function (h) {
      return '<a role="option" href="' + esc(h.url) + '"><strong>' + esc(h.title) + '</strong>' +
        '<span class="ngt-admin-search-meta">' + esc(h.module || '') + ' · ' + esc(h.slug || '') + '</span></a>';
    }).join('');
    results.hidden = false;
  }

  function search(q) {
    if (!q || q.length < 2) {
      if (results) results.hidden = true;
      return;
    }
    var url = (cfg.restRoot || '').replace(/\/$/, '') + '/search?q=' + encodeURIComponent(q);
    fetch(url, {
      credentials: 'same-origin',
      headers: { 'X-WP-Nonce': cfg.nonce || '' }
    }).then(function (r) { return r.json(); }).then(function (data) {
      renderHits(data.results || []);
    }).catch(function () {
      if (results) results.hidden = true;
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
