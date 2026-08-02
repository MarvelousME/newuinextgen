/**
 * Theme Designer live controls.
 */
(function () {
  'use strict';
  var root = document.getElementById('ngt-theme-designer');
  if (!root) return;
  var cfg = window.ngtAdminShell || {};

  function api(path, body) {
    return fetch((cfg.restRoot || '').replace(/\/$/, '') + path, {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': cfg.nonce || '' },
      body: JSON.stringify(body || {})
    }).then(function (r) { return r.json(); });
  }

  function collect() {
    var theme = {};
    root.querySelectorAll('[data-theme-key]').forEach(function (el) {
      theme[el.getAttribute('data-theme-key')] = el.value;
    });
    return theme;
  }

  function applyLive(theme) {
    var map = {
      primary: '--ngt-admin-primary', secondary: '--ngt-admin-secondary', accent: '--ngt-admin-accent',
      success: '--ngt-admin-success', warning: '--ngt-admin-warning', error: '--ngt-admin-error',
      background: '--ngt-admin-bg', foreground: '--ngt-admin-fg', sidebar: '--ngt-admin-sidebar',
      sidebar_text: '--ngt-admin-sidebar-text', hover: '--ngt-admin-hover', active: '--ngt-admin-active',
      card: '--ngt-admin-card', button: '--ngt-admin-button'
    };
    Object.keys(map).forEach(function (k) {
      if (theme[k]) document.documentElement.style.setProperty(map[k], theme[k]);
    });
    if (theme.motion != null) document.documentElement.style.setProperty('--ngt-admin-motion', String(theme.motion));
    if (theme.border_radius != null) document.documentElement.style.setProperty('--ngt-admin-radius', theme.border_radius + 'px');
  }

  root.addEventListener('input', function () {
    applyLive(collect());
  });

  var save = document.getElementById('ngt-theme-save');
  if (save) {
    save.addEventListener('click', function () {
      var theme = collect();
      api('/theme', { theme: theme, scope: 'user' }).then(function () {
        applyLive(theme);
      });
    });
  }
  var reset = document.getElementById('ngt-theme-reset');
  if (reset) {
    reset.addEventListener('click', function () {
      api('/theme', { theme: {}, scope: 'user' }).then(function () { window.location.reload(); });
    });
  }
})();
