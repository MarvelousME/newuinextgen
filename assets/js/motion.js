/**
 * BeyondInfinity — Framer-style motion (scroll reveals, parallax, stagger).
 */
(function () {
  'use strict';

  var reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var root = document.documentElement;

  if (document.body.classList.contains('bi-motion-enabled') && !reduced) {
    root.classList.add('framer-motion');
  }

  if (reduced) {
    document.querySelectorAll('[data-bi-motion], .mask-reveal, .stagger-auto > *').forEach(function (el) {
      el.classList.add('is-motion-in', 'revealed');
      el.style.opacity = '1';
    });
    return;
  }

  /* Scroll-triggered entrance */
  var motionEls = document.querySelectorAll('[data-bi-motion]');
  if (motionEls.length) {
    var motionIo = new IntersectionObserver(
      function (entries) {
        entries.forEach(function (entry) {
          if (entry.isIntersecting) {
            entry.target.classList.add('is-motion-in');
            motionIo.unobserve(entry.target);
          }
        });
      },
      { threshold: 0.12, rootMargin: '0px 0px -40px 0px' }
    );
    motionEls.forEach(function (el) {
      motionIo.observe(el);
    });
  }

  /* Mask reveal */
  document.querySelectorAll('.mask-reveal').forEach(function (el) {
    var io = new IntersectionObserver(
      function (entries) {
        entries.forEach(function (entry) {
          if (entry.isIntersecting) {
            el.classList.add('revealed');
            io.unobserve(el);
          }
        });
      },
      { threshold: 0.2 }
    );
    io.observe(el);
  });

  /* Auto-stagger children */
  document.querySelectorAll('[data-bi-stagger]').forEach(function (container) {
    var motion = container.getAttribute('data-bi-stagger') || 'slide-up';
    var children = container.children;
    for (var i = 0; i < children.length; i++) {
      children[i].setAttribute('data-bi-motion', motion);
      children[i].style.animationDelay = 0.1 * i + 's';
    }
  });

  /* Parallax backgrounds */
  var parallaxEls = document.querySelectorAll('[data-parallax-rate], .bi-hero__bg--parallax, .bi-parallax-cta__bg');
  if (parallaxEls.length && window.innerWidth > 768) {
    var ticking = false;
    function updateParallax() {
      parallaxEls.forEach(function (el) {
        var rate = parseFloat(el.getAttribute('data-parallax-rate') || '0.35', 10);
        var rect = el.getBoundingClientRect();
        var offset = (rect.top + window.scrollY) * rate;
        if (el.classList.contains('bi-hero__bg--parallax')) {
          el.style.transform = 'translate3d(0,' + window.pageYOffset * rate + 'px,0)';
        } else {
          el.style.backgroundPosition = 'center ' + offset + 'px';
        }
      });
      ticking = false;
    }
    window.addEventListener(
      'scroll',
      function () {
        if (!ticking) {
          requestAnimationFrame(updateParallax);
          ticking = true;
        }
      },
      { passive: true }
    );
    updateParallax();
  }
})();
