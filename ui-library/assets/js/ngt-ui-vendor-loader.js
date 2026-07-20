/**
 * Lazy vendor script loader (GSAP / Three) — only when interactive components request it.
 */
(function (window) {
  'use strict';

  window.NGTUI = window.NGTUI || {};
  var cache = window.NGTUI._vendorPromises || (window.NGTUI._vendorPromises = {});

  function loadScript(src) {
    if (cache[src]) {
      return cache[src];
    }
    cache[src] = new Promise(function (resolve, reject) {
      var existing = document.querySelector('script[data-ngt-vendor-src="' + src + '"]');
      if (existing) {
        existing.addEventListener('load', resolve);
        existing.addEventListener('error', reject);
        return;
      }
      var s = document.createElement('script');
      s.src = src;
      s.async = true;
      s.setAttribute('data-ngt-vendor-src', src);
      s.onload = resolve;
      s.onerror = reject;
      document.head.appendChild(s);
    });
    return cache[src];
  }

  window.NGTUI.loadScript = loadScript;

  /**
   * Optional Three.js for globe — falls back to 2D canvas if unavailable.
   */
  window.NGTUI.ensureThree = function () {
    if (window.THREE) {
      return Promise.resolve(window.THREE);
    }
    return loadScript('https://cdn.jsdelivr.net/npm/three@0.160.0/build/three.min.js').then(function () {
      return window.THREE || null;
    }).catch(function () {
      return null;
    });
  };

  /**
   * Optional GSAP for future motion-heavy interactives.
   */
  window.NGTUI.ensureGsap = function () {
    if (window.gsap) {
      return Promise.resolve(window.gsap);
    }
    return loadScript('https://cdn.jsdelivr.net/npm/gsap@3.12.5/dist/gsap.min.js').then(function () {
      return window.gsap || null;
    }).catch(function () {
      return null;
    });
  };
})(window);
