/**
 * NGT UI runtime — shared helpers for component init/teardown.
 */
(function (window, document) {
  'use strict';

  var registry = [];

  function ready(fn) {
    if (document.readyState !== 'loading') fn();
    else document.addEventListener('DOMContentLoaded', fn);
  }

  function prefersReducedMotion() {
    return window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  }

  window.NGTUI = {
    ready: ready,
    prefersReducedMotion: prefersReducedMotion,
    register: function (name, initFn) {
      registry.push({ name: name, init: initFn });
    },
    initAll: function (root) {
      var scope = root || document;
      registry.forEach(function (entry) {
        try {
          entry.init(scope);
        } catch (e) {
          if (window.console && console.error) console.error('NGTUI', entry.name, e);
        }
      });
    }
  };

  ready(function () {
    window.NGTUI.initAll(document);
  });

  document.addEventListener('ngt-ui:refresh', function (ev) {
    window.NGTUI.initAll((ev && ev.detail && ev.detail.root) || document);
  });
})(window, document);
