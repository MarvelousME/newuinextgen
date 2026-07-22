/**
 * Premium page loader controller.
 * - Picks a random branded variant (avoids repeating the previous one).
 * - Fades out on window.load + document.fonts.ready (2.5s failsafe).
 * - Gates content entrances via body.bi-loaded.
 * - Drives a slim top progress bar for same-origin navigations.
 * Spec: documentation/ux-redesign/04-motion-spec.md §5
 */
(function () {
  'use strict';

  var VARIANTS = ['constellation', 'orb', 'wave', 'nodes', 'pulse'];
  var FAILSAFE_MS = 2500;
  var loader = document.getElementById('bi-loader');
  var reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var skipped = document.documentElement.classList.contains('bi-loader-skip');
  var rafId = 0;

  function pickVariant() {
    var last = '';
    try { last = sessionStorage.getItem('biLoaderLast') || ''; } catch (e) { /* private mode */ }
    var pool = VARIANTS.filter(function (v) { return v !== last; });
    var pick = pool[Math.floor(Math.random() * pool.length)] || VARIANTS[0];
    try { sessionStorage.setItem('biLoaderLast', pick); } catch (e) { /* private mode */ }
    return pick;
  }

  function startConstellation(canvas) {
    var ctx = canvas.getContext('2d');
    if (!ctx) { return; }
    var w = canvas.width;
    var h = canvas.height;
    var points = [];
    for (var i = 0; i < 26; i++) {
      points.push({
        x: Math.random() * w,
        y: Math.random() * h,
        vx: (Math.random() - 0.5) * 0.5,
        vy: (Math.random() - 0.5) * 0.5
      });
    }
    function frame() {
      ctx.clearRect(0, 0, w, h);
      for (var i = 0; i < points.length; i++) {
        var p = points[i];
        p.x += p.vx; p.y += p.vy;
        if (p.x < 0 || p.x > w) { p.vx *= -1; }
        if (p.y < 0 || p.y > h) { p.vy *= -1; }
        ctx.beginPath();
        ctx.arc(p.x, p.y, 1.8, 0, Math.PI * 2);
        ctx.fillStyle = 'rgba(255,255,255,0.85)';
        ctx.fill();
        for (var j = i + 1; j < points.length; j++) {
          var q = points[j];
          var dx = p.x - q.x, dy = p.y - q.y;
          var d = Math.sqrt(dx * dx + dy * dy);
          if (d < 60) {
            ctx.beginPath();
            ctx.moveTo(p.x, p.y);
            ctx.lineTo(q.x, q.y);
            ctx.strokeStyle = 'rgba(5,150,105,' + (0.6 * (1 - d / 60)).toFixed(2) + ')';
            ctx.lineWidth = 1;
            ctx.stroke();
          }
        }
      }
      rafId = window.requestAnimationFrame(frame);
    }
    rafId = window.requestAnimationFrame(frame);
  }

  function dismiss() {
    if (!loader || loader.classList.contains('is-leaving')) { return; }
    loader.classList.add('is-leaving');
    document.body.classList.add('bi-loaded');
    try { sessionStorage.setItem('biLoaderSeen', '1'); } catch (e) { /* private mode */ }
    if (rafId) { window.cancelAnimationFrame(rafId); }
    window.setTimeout(function () {
      if (loader.parentNode) { loader.parentNode.removeChild(loader); }
    }, 450);
  }

  function whenReady(fn) {
    var done = false;
    function once() { if (!done) { done = true; fn(); } }
    if (document.readyState === 'complete') {
      once();
    } else {
      window.addEventListener('load', function () {
        if (document.fonts && document.fonts.ready && typeof document.fonts.ready.then === 'function') {
          document.fonts.ready.then(once, once);
        } else {
          once();
        }
      });
    }
    window.setTimeout(once, FAILSAFE_MS);
  }

  if (loader && !skipped && !reduced) {
    var variant = pickVariant();
    loader.setAttribute('data-bi-loader-variant', variant);
    if (variant === 'constellation') {
      var canvas = loader.querySelector('.bi-loader__canvas');
      if (canvas) { startConstellation(canvas); }
    }
    whenReady(dismiss);
  } else if (loader) {
    // Reduced motion / repeat view: brand mark only, quick fade (or hidden pre-paint).
    whenReady(dismiss);
  } else {
    document.body.classList.add('bi-loaded');
  }

  // Slim progress bar for subsequent same-origin navigations.
  var bar = document.getElementById('bi-loader-bar');
  if (bar) {
    document.addEventListener('click', function (e) {
      var a = e.target && e.target.closest ? e.target.closest('a[href]') : null;
      if (!a || a.target === '_blank' || e.defaultPrevented || e.metaKey || e.ctrlKey || e.shiftKey) { return; }
      var href = a.getAttribute('href') || '';
      if (href.charAt(0) === '#' || href.indexOf('javascript:') === 0 || href.indexOf('mailto:') === 0 || href.indexOf('tel:') === 0) { return; }
      if (a.origin && a.origin !== window.location.origin) { return; }
      bar.classList.add('is-active');
      bar.style.width = '70%';
    });
    window.addEventListener('pagehide', function () {
      bar.style.width = '100%';
    });
    window.addEventListener('pageshow', function (e) {
      if (e.persisted) {
        bar.style.width = '0';
        bar.classList.remove('is-active');
        document.body.classList.add('bi-loaded');
      }
    });
  }
})();
