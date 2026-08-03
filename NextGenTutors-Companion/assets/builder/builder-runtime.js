/**
 * Public runtime for Visual Builder interactions (no jQuery).
 * Honours prefers-reduced-motion; hydrates Lottie placeholders and scroll parallax.
 */
(function () {
  'use strict';
  if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
    document.documentElement.classList.add('ngc-b-reduced-motion');
    return;
  }

  function readIx() {
    var el = document.getElementById('ngc-builder-ix');
    if (!el) return null;
    try {
      return JSON.parse(el.textContent || '{}');
    } catch (e) {
      return null;
    }
  }

  function bindParallax(nodeId, speed) {
    var el = document.querySelector('[data-ngc-node="' + nodeId + '"]');
    if (!el) return;
    var s = typeof speed === 'number' ? speed : 0.25;
    window.addEventListener(
      'scroll',
      function () {
        var y = window.scrollY * s;
        el.style.transform = 'translate3d(0,' + y + 'px,0)';
      },
      { passive: true }
    );
  }

  function hydrateLottie() {
    document.querySelectorAll('[data-ngc-lottie]').forEach(function (el) {
      var src = el.getAttribute('data-ngc-lottie');
      if (!src) return;
      // Lazy placeholder — full Lottie player can be swapped via filter/extension.
      el.setAttribute('role', 'img');
      el.setAttribute('aria-label', 'Animation');
      el.dataset.ngcLottieReady = '1';
    });
  }

  document.addEventListener('DOMContentLoaded', function () {
    hydrateLottie();
    var cfg = readIx();
    if (!cfg || !cfg.nodes) return;
    Object.keys(cfg.nodes).forEach(function (nodeId) {
      var list = cfg.nodes[nodeId] || [];
      list.forEach(function (ix) {
        if (!ix || !ix.action) return;
        if (ix.action === 'parallax' || (ix.trigger === 'scroll' && ix.action === 'parallax')) {
          bindParallax(nodeId, ix.config && ix.config.speed);
        }
      });
    });
  });
})();
