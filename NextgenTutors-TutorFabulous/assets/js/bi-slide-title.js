/**
 * BeyondInfinity — slide-reveal titles (Anime.js) + 3D hover tilt.
 * Underline / sweep line removed per design.
 */
(function () {
  'use strict';

  var SELECTORS = [
    '[data-bi-slide-title]',
    '.ng-page-hero__title',
    '.ng-page-heading__title',
    '.bi-hero__title',
    '.entry-title',
    '.ng-page-footer-band__title',
    '.ngi-title',
    '.ngi-heading',
    '.bi-cinematic-band__title',
    '.section__title',
    '.about-hero h1',
    '.contact-hero h1',
    '.bat-hero h1',
    '.guar-hero h1',
    '#bi-brand-story-title',
    '#bi-brand-mission-title',
  ].join(',');

  var reduced =
    window.matchMedia &&
    window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var canHover =
    window.matchMedia && window.matchMedia('(hover: hover)').matches;

  function animeLib() {
    return typeof window.anime === 'function' ? window.anime : null;
  }

  function shouldSkip(el) {
    if (!el || el.getAttribute('data-bi-slide-ready') === '1') return true;
    if (el.closest('.bi-slide-title__viewport, .bi-slide-title__text')) return true;
    if (el.closest('#wpadminbar, .wp-block-navigation, .bi-nav, .ngt-admin')) return true;
    var text = (el.textContent || '').replace(/\s+/g, ' ').trim();
    return text.length < 2;
  }

  function wrapTitle(el) {
    if (shouldSkip(el)) return null;

    var raw = el.innerHTML;
    el.classList.add('bi-slide-title', 'is-pending');
    el.setAttribute('data-bi-slide-ready', '1');

    el.innerHTML =
      '<span class="bi-slide-title__viewport">' +
      '<span class="bi-slide-title__text">' +
      raw +
      '</span></span>';

    var isHero =
      el.classList.contains('ng-page-hero__title') ||
      el.classList.contains('bi-hero__title') ||
      el.classList.contains('ngi-title') ||
      !!el.closest(
        '.ng-page-hero, .bi-hero, .ngt-hero, .ngi-hero, .about-hero, .contact-hero, .bat-hero, .guar-hero'
      );

    if (isHero) {
      el.classList.add('bi-slide-title--on-dark');
    } else {
      el.classList.add('bi-slide-title--accent');
    }

    return {
      root: el,
      text: el.querySelector('.bi-slide-title__text'),
    };
  }

  function showFinal(parts) {
    if (!parts) return;
    parts.root.classList.remove('is-pending');
    parts.root.classList.add('is-revealed');
    if (parts.text) {
      parts.text.style.clipPath = 'inset(0 0% 0 0)';
      parts.text.style.transform = 'translate3d(0,0,0)';
      parts.text.style.opacity = '1';
    }
    bindTilt(parts.root);
  }

  function play(parts) {
    if (!parts || parts.root.classList.contains('is-revealed')) return;

    var anime = animeLib();
    if (reduced || !anime) {
      showFinal(parts);
      return;
    }

    parts.root.classList.remove('is-pending');
    parts.root.classList.add('is-animating');

    anime({
      targets: parts.text,
      clipPath: ['inset(0 100% 0 0)', 'inset(0 0% 0 0)'],
      translateX: [-28, 0],
      opacity: [0.15, 1],
      duration: 820,
      easing: 'cubicBezier(0.16, 1, 0.3, 1)',
      complete: function () {
        parts.root.classList.remove('is-animating');
        parts.root.classList.add('is-revealed');
        if (parts.text) {
          parts.text.style.transform = '';
        }
        bindTilt(parts.root);
      },
    });
  }

  function bindTilt(root) {
    if (!root || reduced || !canHover || root.getAttribute('data-bi-tilt') === '1') {
      return;
    }
    root.setAttribute('data-bi-tilt', '1');

    var max = 9;

    function onMove(e) {
      var rect = root.getBoundingClientRect();
      if (!rect.width || !rect.height) return;
      var px = (e.clientX - rect.left) / rect.width;
      var py = (e.clientY - rect.top) / rect.height;
      var rotY = (px - 0.5) * max * 2;
      var rotX = (0.5 - py) * max * 2;
      root.classList.add('is-tilting');
      root.style.setProperty('--bi-tilt-x', rotX.toFixed(2) + 'deg');
      root.style.setProperty('--bi-tilt-y', rotY.toFixed(2) + 'deg');
    }

    function onLeave() {
      root.classList.remove('is-tilting');
      root.style.setProperty('--bi-tilt-x', '0deg');
      root.style.setProperty('--bi-tilt-y', '0deg');
    }

    root.addEventListener('pointermove', onMove);
    root.addEventListener('pointerleave', onLeave);
  }

  function observe(parts) {
    if (!parts) return;
    if (reduced || !('IntersectionObserver' in window)) {
      showFinal(parts);
      return;
    }

    var io = new IntersectionObserver(
      function (entries) {
        entries.forEach(function (entry) {
          if (!entry.isIntersecting) return;
          play(parts);
          io.unobserve(entry.target);
        });
      },
      { threshold: 0.35, rootMargin: '0px 0px -8% 0px' }
    );
    io.observe(parts.root);

    requestAnimationFrame(function () {
      var rect = parts.root.getBoundingClientRect();
      var vh = window.innerHeight || document.documentElement.clientHeight;
      if (rect.top < vh * 0.85 && rect.bottom > 0) {
        play(parts);
        io.unobserve(parts.root);
      }
    });
  }

  function init() {
    if (!document.body || document.body.classList.contains('bi-builder-edit')) {
      return;
    }

    var nodes = document.querySelectorAll(SELECTORS);
    var seen = [];
    nodes.forEach(function (el) {
      if (seen.indexOf(el) !== -1) return;
      seen.push(el);
      observe(wrapTitle(el));
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
