/**
 * NGT UI Catalog — interactive effects (canvas, confetti, lens, cursor).
 * Lazy-loads Three/GSAP only when data-ngt-needs-three is present.
 */
(function (window, document) {
  'use strict';

  function qs(root, sel) {
    return Array.prototype.slice.call(root.querySelectorAll(sel));
  }

  function drawGlobe2d(ctx, w, h) {
    ctx.fillStyle = '#050510';
    ctx.fillRect(0, 0, w, h);
    ctx.strokeStyle = 'rgba(158,122,255,.55)';
    ctx.beginPath();
    ctx.arc(w / 2, h / 2, Math.min(w, h) * 0.32, 0, Math.PI * 2);
    ctx.stroke();
    for (var i = 0; i < 8; i++) {
      ctx.beginPath();
      ctx.ellipse(w / 2, h / 2, Math.min(w, h) * 0.32, Math.min(w, h) * 0.12 + i * 4, 0, 0, Math.PI * 2);
      ctx.strokeStyle = 'rgba(255,255,255,' + (0.08 + i * 0.02) + ')';
      ctx.stroke();
    }
  }

  function initCanvas(scope) {
    qs(scope, '[data-ngt-canvas]').forEach(function (canvas) {
      if (canvas.dataset.ngtBound) return;
      canvas.dataset.ngtBound = '1';
      var ctx = canvas.getContext('2d');
      if (!ctx) return;
      var mode = canvas.getAttribute('data-ngt-canvas');
      var w = canvas.width;
      var h = canvas.height;
      var needsThree = canvas.getAttribute('data-ngt-needs-three') === '1';

      function start2d() {
        if (mode === 'globe') {
          drawGlobe2d(ctx, w, h);
          return;
        }
        var dots = [];
        for (var n = 0; n < 48; n++) {
          dots.push({ x: Math.random() * w, y: Math.random() * h, vx: (Math.random() - 0.5) * 0.6, vy: (Math.random() - 0.5) * 0.6 });
        }
        (function frame() {
          ctx.clearRect(0, 0, w, h);
          ctx.fillStyle = '#050510';
          ctx.fillRect(0, 0, w, h);
          dots.forEach(function (d) {
            d.x += d.vx; d.y += d.vy;
            if (d.x < 0 || d.x > w) d.vx *= -1;
            if (d.y < 0 || d.y > h) d.vy *= -1;
            ctx.beginPath();
            ctx.fillStyle = 'rgba(158,122,255,.9)';
            ctx.arc(d.x, d.y, 2, 0, Math.PI * 2);
            ctx.fill();
          });
          if (!(window.NGTUI && window.NGTUI.prefersReducedMotion())) {
            requestAnimationFrame(frame);
          }
        })();
      }

      if (needsThree && window.NGTUI && typeof window.NGTUI.ensureThree === 'function') {
        window.NGTUI.ensureThree().then(function (THREE) {
          if (THREE && mode === 'globe') {
            drawGlobe2d(ctx, w, h);
            return;
          }
          start2d();
        }).catch(start2d);
      } else {
        start2d();
      }
    });
  }

  function initConfetti(scope) {
    qs(scope, '[data-ngt-confetti]').forEach(function (btn) {
      if (btn.dataset.ngtBound) return;
      btn.dataset.ngtBound = '1';
      var host = btn.closest('[data-ngt-ui]') || btn.parentElement;
      var canvas = host.querySelector('[data-ngt-confetti-canvas]');
      btn.addEventListener('click', function () {
        if (!canvas) return;
        var ctx = canvas.getContext('2d');
        if (!ctx) return;
        var bits = [];
        for (var i = 0; i < 60; i++) {
          bits.push({ x: canvas.width / 2, y: canvas.height / 2, vx: (Math.random() - 0.5) * 8, vy: Math.random() * -8 - 2, c: 'hsl(' + Math.floor(Math.random() * 360) + ' 90% 60%)' });
        }
        var frames = 0;
        (function tick() {
          frames += 1;
          ctx.clearRect(0, 0, canvas.width, canvas.height);
          bits.forEach(function (b) {
            b.vy += 0.2; b.x += b.vx; b.y += b.vy;
            ctx.fillStyle = b.c;
            ctx.fillRect(b.x, b.y, 4, 8);
          });
          if (frames < 90) requestAnimationFrame(tick);
          else ctx.clearRect(0, 0, canvas.width, canvas.height);
        })();
      });
    });
  }

  function initCoolMode(scope) {
    qs(scope, '[data-ngt-cool]').forEach(function (btn) {
      if (btn.dataset.ngtBound) return;
      btn.dataset.ngtBound = '1';
      btn.addEventListener('click', function (ev) {
        for (var i = 0; i < 10; i++) {
          var s = document.createElement('span');
          s.textContent = '✦';
          s.style.cssText = 'position:fixed;left:' + ev.clientX + 'px;top:' + ev.clientY + 'px;pointer-events:none;z-index:99999;transition:transform .7s,opacity .7s;color:hsl(' + (i * 36) + ' 90% 60%)';
          document.body.appendChild(s);
          requestAnimationFrame(function () {
            s.style.transform = 'translate(' + ((Math.random() - 0.5) * 120) + 'px,' + ((Math.random() - 0.5) * 120) + 'px) scale(0)';
            s.style.opacity = '0';
          });
          setTimeout(function () { s.remove(); }, 800);
        }
      });
    });
  }

  function initLens(scope) {
    qs(scope, '[data-ngt-lens]').forEach(function (lens) {
      if (lens.dataset.ngtBound) return;
      lens.dataset.ngtBound = '1';
      var glass = lens.querySelector('.ngt-ui-lens__glass');
      if (!glass) return;
      lens.addEventListener('pointermove', function (ev) {
        var rect = lens.getBoundingClientRect();
        glass.style.left = (ev.clientX - rect.left) + 'px';
        glass.style.top = (ev.clientY - rect.top) + 'px';
      });
    });
  }

  function initCursor(scope) {
    qs(scope, '[data-ngt-cursor]').forEach(function (zone) {
      if (zone.dataset.ngtBound) return;
      zone.dataset.ngtBound = '1';
      var dot = zone.querySelector('.ngt-ui-cursor-dot');
      if (!dot) return;
      var smooth = zone.getAttribute('data-ngt-cursor') === 'smooth-cursor';
      var x = 0, y = 0, tx = 0, ty = 0;
      zone.addEventListener('pointermove', function (ev) {
        var rect = zone.getBoundingClientRect();
        tx = ev.clientX - rect.left;
        ty = ev.clientY - rect.top;
        if (!smooth) {
          dot.style.left = tx + 'px';
          dot.style.top = ty + 'px';
        }
      });
      if (smooth) {
        (function loop() {
          x += (tx - x) * 0.18;
          y += (ty - y) * 0.18;
          dot.style.left = x + 'px';
          dot.style.top = y + 'px';
          requestAnimationFrame(loop);
        })();
      }
    });
  }

  function boot(scope) {
    if (scope.querySelector('[data-ngt-needs-vendor="1"]') && window.NGTUI && typeof window.NGTUI.ensureGsap === 'function') {
      window.NGTUI.ensureGsap().finally(function () {
        initCanvas(scope);
      });
    } else {
      initCanvas(scope);
    }
    initConfetti(scope);
    initCoolMode(scope);
    initLens(scope);
    initCursor(scope);
  }

  if (window.NGTUI && typeof window.NGTUI.register === 'function') {
    window.NGTUI.register('catalog-interactive', boot);
  }
})(window, document);
