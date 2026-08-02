/**
 * Kinetic motion for admin surfaces.
 */
(function () {
  'use strict';
  if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
  var motion = getComputedStyle(document.documentElement).getPropertyValue('--ngt-admin-motion').trim();
  if (motion === '0') return;

  var nodes = document.querySelectorAll('[data-ngt-motion]');
  if (!nodes.length || !('IntersectionObserver' in window)) return;

  var io = new IntersectionObserver(function (entries) {
    entries.forEach(function (entry) {
      if (!entry.isIntersecting) return;
      var el = entry.target;
      el.classList.add('is-enter');
      window.setTimeout(function () { el.classList.remove('is-enter'); }, 320);
      io.unobserve(el);
    });
  }, { threshold: 0.2 });

  nodes.forEach(function (n) { io.observe(n); });
})();
