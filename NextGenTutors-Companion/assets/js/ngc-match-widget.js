/**
 * NGC Floating Dynamic Tutor Matching Widget
 * Owns open/close/toggle for FAB only; dock opens via ngc:open-match-widget event.
 */
(function () {
  'use strict';

  if (window.NGCMatchWidgetInitialized === true) {
    return;
  }
  window.NGCMatchWidgetInitialized = true;

  var cfg = window.NGC_MATCH_WIDGET || {};
  var reducedMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  function $(s, c) { return (c || document).querySelector(s); }

  function closeOthers() {
    ['#support-panel', '#chat-panel'].forEach(function (sel) {
      var el = $(sel);
      if (el) { el.classList.remove('is-open'); }
    });
  }

  function openPanel(detail) {
    var panel = $('#ngc-match-panel');
    if (!panel || panel.classList.contains('is-open')) { return; }
    closeOthers();
    panel.classList.add('is-open');
    if (reducedMotion) { panel.classList.add('ngc-match-panel--reduced-motion'); }
    panel.setAttribute('aria-hidden', 'false');
    document.body.classList.add('ngc-match-open');
    var dockBtn = $('#match-dock-btn');
    if (dockBtn) { dockBtn.classList.remove('has-pulse'); }
    applyPrefill(detail || {});
    var first = panel.querySelector('select, input');
    if (first) {
      var delay = reducedMotion ? 0 : 280;
      setTimeout(function () { first.focus(); }, delay);
    }
  }

  function closePanel() {
    var panel = $('#ngc-match-panel');
    if (!panel) { return; }
    panel.classList.remove('is-open');
    panel.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('ngc-match-open');
  }

  function toggleFabPanel() {
    var panel = $('#ngc-match-panel');
    if (!panel) { return; }
    if (panel.classList.contains('is-open')) { closePanel(); }
    else { openPanel(); }
  }

  function applyPrefill(override) {
    var pre = Object.assign({}, cfg.prefill || {}, override || {});
    if (!pre.subject && !pre.grade && !pre.province) { return; }
    var map = [
      ['subject', '#ngcmw-subject'],
      ['grade', '#ngcmw-grade'],
      ['province', '#ngcmw-province'],
      ['format', '#ngcmw-format']
    ];
    map.forEach(function (pair) {
      var val = pre[pair[0]];
      var el = $(pair[1]);
      if (!val || !el) { return; }
      Array.prototype.forEach.call(el.options || [], function (opt) {
        if (opt.value === val || opt.textContent.toLowerCase().indexOf(String(val).toLowerCase()) !== -1) {
          el.value = opt.value;
        }
      });
      if (!el.value && el.tagName === 'SELECT') {
        var opt = document.createElement('option');
        opt.value = val;
        opt.textContent = val;
        opt.selected = true;
        el.appendChild(opt);
      }
    });
  }

  function ensureFab() {
    if ($('#float-dock') || $('#ngc-match-fab')) { return; }
    var fab = document.createElement('button');
    fab.type = 'button';
    fab.id = 'ngc-match-fab';
    fab.className = 'ngc-match-fab is-visible' + (reducedMotion ? '' : ' has-pulse');
    fab.setAttribute('aria-label', cfg.i18n && cfg.i18n.open ? cfg.i18n.open : 'Find a tutor');
    fab.innerHTML = '<span class="ngc-match-fab__pulse" aria-hidden="true"></span>'
      + '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round">'
      + '<path d="M12 2l2.4 7.2H22l-6 4.6 2.3 7.2L12 16.8 5.7 21l2.3-7.2-6-4.6h7.6z"/></svg>';
    document.body.appendChild(fab);
    fab.addEventListener('click', toggleFabPanel);
  }

  function bindPanel() {
    var panel = $('#ngc-match-panel');
    var closeBtn = $('#ngc-match-close');
    if (closeBtn) { closeBtn.addEventListener('click', closePanel); }

    document.addEventListener('click', function (e) {
      if (!panel || !panel.classList.contains('is-open')) { return; }
      if (e.target.closest('#ngc-match-panel') || e.target.closest('#match-dock-btn') || e.target.closest('#ngc-match-fab')) {
        return;
      }
      closePanel();
    });

    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') { closePanel(); }
    });

    document.addEventListener('ngc:open-match-widget', function (e) {
      openPanel(e.detail || {});
    });
    document.addEventListener('ngc:close-match-widget', closePanel);
  }

  function bindHeroTriggers() {
    document.addEventListener('click', function (e) {
      var trigger = e.target.closest('[data-ngc-open-match], .ngt-hero-search button, .bi-hero-search button');
      if (!trigger) { return; }
      e.preventDefault();
      openPanel();
    });
  }

  function boot() {
    if (!$('#ngc-match-panel')) { return; }
    if (!$('#match-dock-btn')) { ensureFab(); }
    bindPanel();
    bindHeroTriggers();
    if (cfg.autoOpen) { openPanel(); }
  }

  if (document.readyState !== 'loading') { boot(); }
  else { document.addEventListener('DOMContentLoaded', boot); }
})();
