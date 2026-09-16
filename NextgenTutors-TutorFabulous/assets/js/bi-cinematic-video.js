/**
 * Cinematic background video controller.
 * - IntersectionObserver play/pause
 * - prefers-reduced-motion → poster only
 * - Save-Data / slow 2G → poster only
 * - Fade-in once ready (no CLS — poster holds space)
 */
(function () {
  'use strict';

  var SELECTOR = 'video[data-bi-cinematic], .ng-page-hero__video, .ngi-hero-video, .bi-cinematic-video';

  function prefersReducedMotion() {
    return window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  }

  function shouldDeferVideo() {
    if (prefersReducedMotion()) return true;
    try {
      var c = navigator.connection || navigator.mozConnection || navigator.webkitConnection;
      if (!c) return false;
      if (c.saveData) return true;
      var t = String(c.effectiveType || '').toLowerCase();
      return t === 'slow-2g' || t === '2g';
    } catch (e) {
      return false;
    }
  }

  function markReady(video) {
    video.classList.add('is-ready');
    var host = video.closest('.ng-page-hero, .ngi-hero, .bi-cinematic-band');
    if (host) host.classList.add('bi-cinematic--ready');
  }

  function softPlay(video) {
    if (video._biLocked) return;
    var p = video.play();
    if (p && typeof p.catch === 'function') p.catch(function () {});
  }

  function softPause(video) {
    if (!video.paused) video.pause();
  }

  function bindVideo(video) {
    if (video._biCinematicBound) return;
    video._biCinematicBound = true;

    video.muted = true;
    video.defaultMuted = true;
    video.playsInline = true;
    video.setAttribute('playsinline', '');
    video.setAttribute('webkit-playsinline', '');
    video.loop = true;
    video.removeAttribute('controls');
    video.setAttribute('aria-hidden', 'true');

    if (shouldDeferVideo()) {
      video._biLocked = true;
      softPause(video);
      video.classList.add('is-deferred');
      var host = video.closest('.ng-page-hero, .ngi-hero, .bi-cinematic-band');
      if (host) host.classList.add('bi-cinematic--poster-only');
      return;
    }

    // Prefer metadata-only preload; upgrade when near viewport.
    if (!video.getAttribute('preload')) {
      video.preload = 'metadata';
    }

    var onCanPlay = function () {
      markReady(video);
    };
    video.addEventListener('loadeddata', onCanPlay, { once: true });
    video.addEventListener('canplay', onCanPlay, { once: true });

    if ('IntersectionObserver' in window) {
      var io = new IntersectionObserver(
        function (entries) {
          entries.forEach(function (entry) {
            if (entry.isIntersecting && entry.intersectionRatio > 0.15) {
              if (video.readyState < 2) {
                try {
                  video.load();
                } catch (e) {}
              }
              softPlay(video);
            } else {
              softPause(video);
            }
          });
        },
        { root: null, rootMargin: '80px 0px', threshold: [0, 0.15, 0.35] }
      );
      io.observe(video);
    } else {
      softPlay(video);
      markReady(video);
    }

    document.addEventListener('visibilitychange', function () {
      if (document.hidden) softPause(video);
    });
  }

  function boot() {
    var nodes = document.querySelectorAll(SELECTOR);
    if (!nodes.length) return;

    var run = function () {
      nodes.forEach(bindVideo);
    };

    if (typeof window.requestIdleCallback === 'function') {
      window.requestIdleCallback(run, { timeout: 1200 });
    } else {
      window.setTimeout(run, 0);
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
  } else {
    boot();
  }
})();
