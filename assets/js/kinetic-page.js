/**
 * Kinetic page controls — 3D scroll reveals + magnetic CTAs.
 * Enhances existing page structure; does not replace content.
 */
(function () {
  'use strict';

  var body = document.body;
  if (!body || !(body.classList.contains('bi-kinetic-ui') || body.classList.contains('bi-kinetic-surface') || body.classList.contains('bi-prototype-blend-active'))) {
    return;
  }
  if (body.classList.contains('bi-weight-light') || body.classList.contains('bi-weight-none')) {
    return;
  }

  var reduced = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var root = document.querySelector('.ng-page') || document.querySelector('.bi-theme-main') || document.querySelector('.ngi-home') || document;

  /* Hero mesh pointer */
  var mesh = document.querySelector('.ng-page-hero__mesh') || document.querySelector('.ngi-kh-mesh');
  var meshHost = mesh ? (mesh.closest('.ng-page-hero') || mesh.closest('.ngi-hero') || mesh.parentElement) : null;
  if (mesh && meshHost && !reduced) {
    meshHost.addEventListener('pointermove', function (e) {
      var r = meshHost.getBoundingClientRect();
      mesh.style.setProperty('--mx', ((e.clientX - r.left) / Math.max(r.width, 1)) * 100 + '%');
      mesh.style.setProperty('--my', ((e.clientY - r.top) / Math.max(r.height, 1)) * 100 + '%');
    });
  }

  if (reduced) {
    document.querySelectorAll('.ng-reveal, [data-bi-scroll-3d]').forEach(function (el) {
      el.classList.add('is-in', 'bi-scroll-3d-in');
    });
    return;
  }

  /* Magnetic primary buttons */
  root.querySelectorAll('.ngt-btn--primary, .ng-btn--primary, .ngi-btn-primary, .bi-kinetic-form .ngt-btn').forEach(function (btn) {
    btn.addEventListener('pointermove', function (e) {
      var b = btn.getBoundingClientRect();
      btn.style.transform =
        'translate3d(' +
        ((e.clientX - b.left - b.width / 2) * 0.08) +
        'px,' +
        ((e.clientY - b.top - b.height / 2) * 0.08) +
        'px,0)';
    });
    btn.addEventListener('pointerleave', function () {
      btn.style.transform = '';
    });
  });

  /* Mark interactive controls for 3D scroll if not already tagged */
  var selectors = [
    '.bi-kinetic-form',
    '.bi-shortcode-block',
    '.ngc-form-card',
    '.ngt-card',
    '.ngt-pricing-card',
    '.bi-stat-card',
    '.bi-steps li',
    '.ng-page-section',
    '.ngt-section > .ngt-container > *',
    '.ng-page-hero__stat',
    'form.ngc-form',
    '.bi-role-card'
  ];
  selectors.forEach(function (sel) {
    root.querySelectorAll(sel).forEach(function (el) {
      if (!el.hasAttribute('data-bi-scroll-3d') && !el.classList.contains('ng-page__canvas')) {
        el.setAttribute('data-bi-scroll-3d', '');
      }
      if (!el.classList.contains('ng-reveal')) {
        el.classList.add('ng-reveal');
      }
    });
  });

  var targets = Array.prototype.slice.call(root.querySelectorAll('[data-bi-scroll-3d], .ng-reveal'));
  if (targets.length && 'IntersectionObserver' in window) {
    var io = new IntersectionObserver(
      function (entries) {
        entries.forEach(function (entry) {
          var el = entry.target;
          if (entry.isIntersecting) {
            var ratio = Math.min(1, Math.max(0, entry.intersectionRatio));
            var depth = (0.5 - ratio) * 24;
            var tilt = (0.5 - ratio) * 8;
            el.style.setProperty('--bi-scroll-z', depth.toFixed(2) + 'px');
            el.style.setProperty('--bi-scroll-tilt', tilt.toFixed(2) + 'deg');
            el.classList.add('is-in', 'bi-scroll-3d-in');
          }
        });
      },
      { threshold: [0.08, 0.2, 0.4, 0.65, 0.9], rootMargin: '0px 0px -8% 0px' }
    );

    targets.forEach(function (el, i) {
      el.style.setProperty('--bi-scroll-delay', (i % 6) * 45 + 'ms');
      io.observe(el);
    });

    /* Parallax depth on scroll for marked controls */
    var ticking = false;
    function onScroll() {
      if (ticking) return;
      ticking = true;
      window.requestAnimationFrame(function () {
        var vh = window.innerHeight || 1;
        targets.forEach(function (el) {
          if (!el.classList.contains('bi-scroll-3d-in')) return;
          var r = el.getBoundingClientRect();
          var mid = r.top + r.height / 2;
          var p = (mid - vh / 2) / vh;
          el.style.setProperty('--bi-scroll-y', (p * -18).toFixed(2) + 'px');
          el.style.setProperty('--bi-scroll-rot', (p * 4).toFixed(2) + 'deg');
        });
        ticking = false;
      });
    }
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();
  } else {
    targets.forEach(function (el) {
      el.classList.add('is-in', 'bi-scroll-3d-in');
    });
  }

  /* Vertical wheel → horizontal rail (preview journey). Homepage only. Mobile stacks. */
  var coarse = window.matchMedia && window.matchMedia('(pointer: coarse)').matches;
  var compact = window.matchMedia && window.matchMedia('(max-width: 980px)');

  function navOffset() {
    var raw = window.getComputedStyle(document.documentElement).getPropertyValue('--nav-h');
    var n = parseFloat(raw);
    return isFinite(n) && n > 0 ? n : 159;
  }

  function bindHJourney(wrap) {
    if (wrap.getAttribute('data-bi-h-bound') === '1') return;
    wrap.setAttribute('data-bi-h-bound', '1');
    var rail = wrap.querySelector('[data-bi-h-rail], .ngi-h-journey__rail');
    if (!rail) return;

    function tick() {
      if (compact.matches || reduced) {
        rail.style.transform = '';
        return;
      }
      var r = wrap.getBoundingClientRect();
      var navH = navOffset();
      var total = wrap.offsetHeight - window.innerHeight;
      if (total <= 0) return;
      if (r.top <= navH && r.bottom >= window.innerHeight) {
        var p = Math.min(1, Math.max(0, (navH - r.top) / total));
        var travel = Math.max(0, rail.scrollWidth - window.innerWidth + window.innerWidth * 0.12);
        rail.style.transform = 'translate3d(' + (-p * travel) + 'px,0,0)';
      }
    }

    window.addEventListener('scroll', tick, { passive: true });
    window.addEventListener('resize', tick);
    tick();

    if (reduced || coarse) return;
    wrap.querySelectorAll('.ngi-h-card, .ngi-h-journey .ngi-tab').forEach(function (card) {
      card.addEventListener('pointermove', function (e) {
        var b = card.getBoundingClientRect();
        var x = (e.clientX - b.left) / Math.max(b.width, 1) - 0.5;
        var y = (e.clientY - b.top) / Math.max(b.height, 1) - 0.5;
        card.style.transform = 'perspective(900px) rotateY(' + x * 8 + 'deg) rotateX(' + -y * 8 + 'deg)';
      });
      card.addEventListener('pointerleave', function () {
        card.style.transform = '';
      });
    });
  }

  document.querySelectorAll('body.bi-kinetic-home [data-bi-h-journey]').forEach(bindHJourney);
})();
