/**
 * Design-system motion bridge.
 * Compiles [data-ngt-el-motion] into NGT3D runtime — does not load GSAP itself.
 */
(function (root, document) {
  'use strict';

  function reduced(el) {
    if (!el || el.getAttribute('data-ngt-reduced') === '0') return false;
    return !!(root.matchMedia && root.matchMedia('(prefers-reduced-motion: reduce)').matches);
  }

  function refreshRuntime() {
    if (root.NGT3D && typeof root.NGT3D.refresh === 'function') {
      try {
        root.NGT3D.refresh();
      } catch (err) {
        /* isolated */
      }
    }
  }

  function decorateHosts() {
    var nodes = document.querySelectorAll('[data-ngt-el-motion="1"]');
    for (var i = 0; i < nodes.length; i++) {
      var el = nodes[i];
      if (reduced(el)) {
        el.setAttribute('data-ngt-el-motion-skipped', 'reduced');
        continue;
      }
      if (el.getAttribute('data-ngt-mouse') === '1') {
        el.setAttribute('data-bi-tilt', '1');
        el.setAttribute('data-ngt-tilt-host', '1');
      }
      var preset = el.getAttribute('data-ngt-motion-preset');
      if (preset) {
        el.setAttribute('data-ngt-3d-target', preset);
      }
    }
  }

  function init() {
    decorateHosts();
    refreshRuntime();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

  root.addEventListener('elementor/frontend/init', function () {
    if (root.elementorFrontend && root.elementorFrontend.hooks) {
      root.elementorFrontend.hooks.addAction('frontend/element_ready/global', init);
    }
  });
})(window, document);
