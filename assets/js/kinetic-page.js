/**
 * Kinetic surface — hero mesh pointer + magnetic CTAs on inner pages.
 */
(function () {
  'use strict';

  var page = document.querySelector('.ng-page') || document.querySelector('.bi-prototype-blend');
  if (!page || !(document.body.classList.contains('bi-kinetic-surface') || document.body.classList.contains('bi-prototype-blend-active'))) {
    return;
  }

  var reduced = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var mesh = document.querySelector('.ng-page-hero__mesh') || document.querySelector('.bi-prototype-blend .pagehead__mesh');
  var meshHost = mesh ? (mesh.closest('.ng-page-hero') || mesh.closest('.pagehead') || page) : null;

  if (mesh && meshHost && !reduced) {
    meshHost.addEventListener('pointermove', function (e) {
      var r = meshHost.getBoundingClientRect();
      mesh.style.setProperty('--mx', ((e.clientX - r.left) / Math.max(r.width, 1)) * 100 + '%');
      mesh.style.setProperty('--my', ((e.clientY - r.top) / Math.max(r.height, 1)) * 100 + '%');
    });
  }

  if (reduced) {
    return;
  }

  page.querySelectorAll('.ngt-btn--primary, .ng-btn--primary, .ngt-btn--outline').forEach(function (btn) {
    btn.addEventListener('pointermove', function (e) {
      var b = btn.getBoundingClientRect();
      btn.style.transform = 'translate(' + ((e.clientX - b.left - b.width / 2) * 0.08) + 'px,' + ((e.clientY - b.top - b.height / 2) * 0.08) + 'px)';
    });
    btn.addEventListener('pointerleave', function () {
      btn.style.transform = '';
    });
  });
})();
