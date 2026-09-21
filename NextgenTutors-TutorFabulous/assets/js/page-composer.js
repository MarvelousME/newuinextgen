/**
 * Blended page layout — scroll reveals and staggered entrances.
 */
(function () {
  'use strict';

  var reduced = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  function revealInView(el) {
    el.classList.add('is-inview');
  }

  function initReveals(root) {
    var nodes = root.querySelectorAll('.ng-reveal:not(.is-inview)');
    if (!nodes.length) {
      return;
    }
    if (reduced || !('IntersectionObserver' in window)) {
      nodes.forEach(revealInView);
      return;
    }
    var io = new IntersectionObserver(
      function (entries) {
        entries.forEach(function (entry) {
          if (!entry.isIntersecting) {
            return;
          }
          revealInView(entry.target);
          io.unobserve(entry.target);
        });
      },
      { rootMargin: '0px 0px -8% 0px', threshold: 0.12 }
    );
    nodes.forEach(function (el, i) {
      el.style.transitionDelay = Math.min(i * 60, 360) + 'ms';
      io.observe(el);
    });
  }

  function initStaggerGrids(root) {
    root.querySelectorAll('.ng-page-grid, .bi-steps, .bi-stat-grid').forEach(function (grid) {
      Array.from(grid.children).forEach(function (child, i) {
        if (!child.classList.contains('ng-reveal')) {
          child.classList.add('ng-reveal');
        }
        child.style.transitionDelay = Math.min(i * 70, 420) + 'ms';
      });
    });
  }

  document.addEventListener('DOMContentLoaded', function () {
    var page = document.querySelector('.ng-page');
    if (!page) {
      return;
    }
    initStaggerGrids(page);
    initReveals(page);
  });
})();
