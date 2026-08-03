/**
 * 3D scroll for tutoring narrative panels.
 */
(function () {
  'use strict';

  var root = document.querySelector('[data-bi-narrative-3d]');
  if (!root) return;

  var reduced =
    window.matchMedia &&
    window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var mobile =
    window.matchMedia && window.matchMedia('(max-width: 900px)').matches;

  var panels = Array.prototype.slice.call(
    root.querySelectorAll('[data-bi-narrative-panel]')
  );

  if (reduced || mobile || !panels.length) {
    panels.forEach(function (p) {
      p.classList.add('is-in-view');
    });
    return;
  }

  var ticking = false;

  function clamp(n, a, b) {
    return Math.max(a, Math.min(b, n));
  }

  function update() {
    ticking = false;
    var vh = window.innerHeight || document.documentElement.clientHeight;

    panels.forEach(function (panel) {
      var rect = panel.getBoundingClientRect();
      var mid = rect.top + rect.height * 0.45;
      var progress = clamp(1 - Math.abs(mid - vh * 0.42) / (vh * 0.55), 0, 1);
      var depth = (1 - progress) * 90;
      var tilt = (1 - progress) * 10;
      var scale = 0.94 + progress * 0.06;

      panel.style.transform =
        'translate3d(0,' + ((1 - progress) * 28).toFixed(1) + 'px, ' +
        (-depth).toFixed(1) + 'px) rotateX(' + tilt.toFixed(2) + 'deg) scale(' +
        scale.toFixed(3) + ')';
      panel.style.opacity = String(0.28 + progress * 0.72);
      panel.classList.toggle('is-in-view', progress > 0.35);

      var media = panel.querySelector('[data-bi-narrative-media]');
      var img = media && media.querySelector('img');
      if (media) {
        media.style.transform =
          'translate3d(0,' + ((1 - progress) * -18).toFixed(1) + 'px, ' +
          (progress * 40).toFixed(1) + 'px) rotateY(' +
          ((progress - 0.5) * 6).toFixed(2) + 'deg)';
      }
      if (img) {
        img.style.transform = 'scale(' + (1.04 + (1 - progress) * 0.06).toFixed(3) + ')';
      }
    });
  }

  function onScroll() {
    if (!ticking) {
      requestAnimationFrame(update);
      ticking = true;
    }
  }

  window.addEventListener('scroll', onScroll, { passive: true });
  window.addEventListener('resize', onScroll, { passive: true });
  update();
})();
