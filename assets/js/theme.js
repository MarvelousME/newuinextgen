/**
 * NextGen Tutors — front-end interactions.
 * Vanilla JS replacements for the React app's Framer Motion / GSAP behaviors:
 *   mobile menu · sticky header · hero carousel · scroll reveal · counters · 3D tilt.
 * No dependencies. Respects prefers-reduced-motion.
 */
(function () {
  'use strict';

  var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  function ready(fn) {
    if (document.readyState !== 'loading') { fn(); }
    else { document.addEventListener('DOMContentLoaded', fn); }
  }

  /* ---------------- Mobile menu ---------------- */
  function initMobileMenu() {
    var toggle = document.querySelector('.ngt-nav-toggle');
    var menu = document.getElementById('ngt-mobile-menu');
    if (!toggle || !menu) { return; }

    toggle.addEventListener('click', function () {
      var open = toggle.getAttribute('aria-expanded') === 'true';
      toggle.setAttribute('aria-expanded', String(!open));
      if (open) { menu.setAttribute('hidden', ''); }
      else { menu.removeAttribute('hidden'); }
    });

    // Close on link click / escape.
    menu.addEventListener('click', function (e) {
      if (e.target.closest('a')) {
        toggle.setAttribute('aria-expanded', 'false');
        menu.setAttribute('hidden', '');
      }
    });
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && toggle.getAttribute('aria-expanded') === 'true') {
        toggle.setAttribute('aria-expanded', 'false');
        menu.setAttribute('hidden', '');
        toggle.focus();
      }
    });
  }

  /* ---------------- Sticky header shadow ---------------- */
  function initSticky() {
    var header = document.getElementById('ngt-site-header') || document.querySelector('.ngt-nav');
    if (!header) { return; }
    var onScroll = function () {
      var stuck = window.scrollY > 8;
      header.classList.toggle('is-stuck', stuck);
      header.classList.toggle('ngt-nav--scrolled', stuck);
      if (header.classList.contains('ngt-nav')) {
        header.classList.toggle('is-solid', window.scrollY > 40);
        header.classList.toggle('ngt-nav--solid', window.scrollY > 40);
      }
    };
    onScroll();
    window.addEventListener('scroll', onScroll, { passive: true });
  }

  /* ---------------- Hero carousel ---------------- */
  function initCarousel() {
    var root = document.querySelector('[data-carousel]');
    if (!root) { return; }
    var slides = Array.prototype.slice.call(root.querySelectorAll('.ngt-hero-slide'));
    var dots = Array.prototype.slice.call(root.querySelectorAll('.ngt-hero-dotbtn'));
    if (slides.length < 2) { return; }

    var current = 0;
    var interval = parseInt(root.getAttribute('data-interval'), 10) || 7000;
    var timer = null;

    function show(idx) {
      idx = (idx + slides.length) % slides.length;
      slides.forEach(function (s, i) {
        var active = i === idx;
        s.classList.toggle('is-active', active);
        if (active) { s.removeAttribute('aria-hidden'); } else { s.setAttribute('aria-hidden', 'true'); }
      });
      dots.forEach(function (d, i) { d.classList.toggle('is-active', i === idx); });
      current = idx;
    }

    function next() { show(current + 1); }
    function prev() { show(current - 1); }

    function start() {
      if (reduceMotion) { return; }
      stop();
      timer = window.setInterval(next, interval);
    }
    function stop() { if (timer) { window.clearInterval(timer); timer = null; } }

    var nextBtn = root.querySelector('[data-next]');
    var prevBtn = root.querySelector('[data-prev]');
    if (nextBtn) { nextBtn.addEventListener('click', function () { next(); start(); }); }
    if (prevBtn) { prevBtn.addEventListener('click', function () { prev(); start(); }); }
    dots.forEach(function (d, i) {
      d.addEventListener('click', function () { show(i); start(); });
    });

    root.addEventListener('mouseenter', stop);
    root.addEventListener('mouseleave', start);
    root.addEventListener('focusin', stop);
    root.addEventListener('focusout', start);

    // Basic swipe support.
    var startX = 0;
    root.addEventListener('touchstart', function (e) { startX = e.touches[0].clientX; stop(); }, { passive: true });
    root.addEventListener('touchend', function (e) {
      var dx = e.changedTouches[0].clientX - startX;
      if (Math.abs(dx) > 40) { dx < 0 ? next() : prev(); }
      start();
    }, { passive: true });

    show(0);
    start();
  }

  /* ---------------- Scroll reveal ---------------- */
  function initReveal() {
    var els = Array.prototype.slice.call(document.querySelectorAll('[data-reveal]'));
    if (!els.length) { return; }
    if (reduceMotion || !('IntersectionObserver' in window)) {
      els.forEach(function (el) { el.classList.add('is-visible'); });
      return;
    }
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          entry.target.classList.add('is-visible');
          io.unobserve(entry.target);
        }
      });
    }, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });
    els.forEach(function (el) { io.observe(el); });
  }

  /* ---------------- Animated counters ---------------- */
  function initCounters() {
    var nums = Array.prototype.slice.call(document.querySelectorAll('[data-counter]'));
    if (!nums.length) { return; }

    function animate(el) {
      var target = parseFloat(el.getAttribute('data-counter')) || 0;
      var suffix = el.getAttribute('data-suffix') || '';
      if (reduceMotion) { el.textContent = format(target) + suffix; return; }
      var dur = 1400, start = null;
      function step(ts) {
        if (!start) { start = ts; }
        var p = Math.min((ts - start) / dur, 1);
        var eased = 1 - Math.pow(1 - p, 3);
        el.textContent = format(Math.round(target * eased)) + suffix;
        if (p < 1) { requestAnimationFrame(step); }
      }
      requestAnimationFrame(step);
    }
    function format(n) { return n >= 1000 ? n.toLocaleString('en-ZA') : String(n); }

    if (!('IntersectionObserver' in window)) { nums.forEach(animate); return; }
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) { animate(entry.target); io.unobserve(entry.target); }
      });
    }, { threshold: 0.4 });
    nums.forEach(function (el) { io.observe(el); });
  }

  /* ---------------- 3D tilt (replaces ThreeDCard) ---------------- */
  function initTilt() {
    if (reduceMotion || window.matchMedia('(pointer: coarse)').matches) { return; }
    var cards = Array.prototype.slice.call(document.querySelectorAll('[data-tilt]'));
    cards.forEach(function (card) {
      card.style.transformStyle = 'preserve-3d';
      card.addEventListener('mousemove', function (e) {
        var r = card.getBoundingClientRect();
        var px = (e.clientX - r.left) / r.width - 0.5;
        var py = (e.clientY - r.top) / r.height - 0.5;
        card.style.transform = 'perspective(900px) rotateX(' + (-py * 6) + 'deg) rotateY(' + (px * 6) + 'deg) translateY(-4px)';
      });
      card.addEventListener('mouseleave', function () { card.style.transform = ''; });
    });
  }

  ready(function () {
    initMobileMenu();
    initSticky();
    initCarousel();
    initReveal();
    initCounters();
    initTilt();
  });
})();
