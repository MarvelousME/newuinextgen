/**
 * Generic stepper controller.
 * Markup:
 *   <ol class="ngt-stepper" data-ngt-stepper data-storage-key="optional">
 *     <li class="ngt-stepper__item" data-step="1">…</li>
 *   </ol>
 * Panels: [data-ngt-step-panel="1"]
 * API: element._ngtStepper.go(n)
 */
(function () {
  'use strict';

  function init(root) {
    if (root._ngtStepper) { return root._ngtStepper; }
    var items = Array.prototype.slice.call(root.querySelectorAll('.ngt-stepper__item[data-step]'));
    var scope = root.closest('[data-ngt-stepper-scope]') || document;
    var storageKey = root.getAttribute('data-storage-key') || '';
    var current = 1;

    function panels() {
      return Array.prototype.slice.call(scope.querySelectorAll('[data-ngt-step-panel]'));
    }

    function go(n, opts) {
      opts = opts || {};
      n = parseInt(n, 10);
      if (!n || n < 1) { return; }
      current = n;
      items.forEach(function (item) {
        var step = parseInt(item.getAttribute('data-step'), 10);
        item.classList.toggle('is-active', step === n);
        item.classList.toggle('is-complete', step < n);
        item.setAttribute('aria-current', step === n ? 'step' : 'false');
      });
      panels().forEach(function (panel) {
        var step = parseInt(panel.getAttribute('data-ngt-step-panel'), 10);
        var active = step === n;
        panel.classList.toggle('is-active', active);
        panel.hidden = !active;
      });
      if (storageKey) {
        try { sessionStorage.setItem(storageKey + ':step', String(n)); } catch (e) { /* private */ }
      }
      if (!opts.silent) {
        root.dispatchEvent(new CustomEvent('ngt:step-change', { detail: { step: n }, bubbles: true }));
      }
    }

    function restore() {
      if (!storageKey) { return; }
      try {
        var saved = parseInt(sessionStorage.getItem(storageKey + ':step') || '1', 10);
        if (saved > 1) { go(saved, { silent: true }); }
      } catch (e) { /* private */ }
    }

    items.forEach(function (item) {
      item.setAttribute('role', 'listitem');
    });
    root.setAttribute('role', 'list');

    var api = { go: go, current: function () { return current; }, restore: restore };
    root._ngtStepper = api;
    restore();
    if (!root.querySelector('.ngt-stepper__item.is-active')) { go(1, { silent: true }); }
    return api;
  }

  function boot() {
    document.querySelectorAll('[data-ngt-stepper]').forEach(init);
  }

  if (document.readyState !== 'loading') { boot(); }
  else { document.addEventListener('DOMContentLoaded', boot); }

  window.NGTStepper = { init: init };
})();
