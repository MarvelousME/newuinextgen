/**
 * Sticky UI — sole owner of header scroll state + collapsible FAB dock.
 * Spec: sticky-ui/STICKY_UI_PROMPT.md
 * WhatsApp stays always visible outside #fab-menu.
 */
(function () {
  'use strict';

  var STAGGER_MS = [0, 50, 100, 150];
  var SCROLL_THRESHOLD = 8;
  var state = { fabOpen: false, timers: [], scrolled: false };

  function $(sel, ctx) {
    return (ctx || document).querySelector(sel);
  }

  function $$(sel, ctx) {
    return Array.prototype.slice.call((ctx || document).querySelectorAll(sel));
  }

  function clearTimers() {
    state.timers.forEach(function (id) {
      clearTimeout(id);
    });
    state.timers = [];
  }

  /** Single sitewide owner — do not duplicate in main.js / ngt-wp-bridge.js */
  function initHeaderSticky() {
    var nav = $('.ngt-nav');
    if (!nav || nav.getAttribute('data-bi-sticky-nav') === '1') return;
    nav.setAttribute('data-bi-sticky-nav', '1');

    var onScroll = function () {
      var scrolled = window.scrollY > SCROLL_THRESHOLD;
      if (scrolled === state.scrolled) return;
      state.scrolled = scrolled;
      nav.classList.toggle('ngt-nav--scrolled', scrolled);
      nav.classList.toggle('is-solid', scrolled);
      nav.classList.toggle('ngt-nav--solid', scrolled);
    };

    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();
  }

  function showItem(el) {
    el.classList.remove('is-collapsing');
    el.classList.add('is-revealed');
  }

  function hideItemInstant(el) {
    el.classList.add('is-collapsing');
    el.classList.remove('is-revealed');
    void el.offsetHeight;
    el.classList.remove('is-collapsing');
  }

  function setFabOpen(next) {
    var dock = $('#float-dock');
    var toggle = $('#fab-toggle');
    var menu = $('#fab-menu');
    if (!dock || !toggle || !menu) return;

    clearTimers();
    state.fabOpen = next;
    dock.classList.toggle('is-open', next);
    toggle.setAttribute('aria-expanded', next ? 'true' : 'false');
    toggle.setAttribute(
      'aria-label',
      next ? 'Close quick actions' : 'Open quick actions'
    );

    var items = $$('.float-dock__item', menu);

    if (next) {
      menu.hidden = false;
      items.forEach(function (el) {
        var idx = parseInt(el.getAttribute('data-fab-index'), 10) || 0;
        var delay = STAGGER_MS[idx] != null ? STAGGER_MS[idx] : idx * 50;
        var tid = window.setTimeout(function () {
          showItem(el);
        }, delay);
        state.timers.push(tid);
      });
      var first = items[0];
      if (first && typeof first.focus === 'function') {
        window.setTimeout(function () {
          try {
            first.focus({ preventScroll: true });
          } catch (e) {
            first.focus();
          }
        }, STAGGER_MS[0] + 20);
      }
    } else {
      items.forEach(hideItemInstant);
      menu.hidden = true;
      if (typeof toggle.focus === 'function') {
        try {
          toggle.focus({ preventScroll: true });
        } catch (e2) {
          toggle.focus();
        }
      }
    }
  }

  function initFab() {
    var dock = $('#float-dock');
    var toggle = $('#fab-toggle');
    var menu = $('#fab-menu');
    if (!dock || !toggle || !menu) return;
    if (dock.getAttribute('data-bi-fab-ready') === '1') return;
    dock.setAttribute('data-bi-fab-ready', '1');

    document.body.classList.add('has-float-dock');

    toggle.addEventListener('click', function (e) {
      e.stopPropagation();
      setFabOpen(!state.fabOpen);
    });

    $$('.float-dock__item', menu).forEach(function (el) {
      el.addEventListener('click', function () {
        window.setTimeout(function () {
          setFabOpen(false);
        }, 0);
      });
    });

    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && state.fabOpen) setFabOpen(false);
    });

    document.addEventListener('click', function (e) {
      if (!state.fabOpen) return;
      if (dock.contains(e.target)) return;
      // Keep FAB open while interacting with support/chat panels.
      if (e.target.closest('#support-panel, #chat-panel, .ngc-match-panel')) return;
      setFabOpen(false);
    });
  }

  function boot() {
    initHeaderSticky();
    initFab();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
  } else {
    boot();
  }

  // Expose for diagnostics / optional bridge no-op checks.
  window.BI_STICKY_UI = {
    scrollThreshold: SCROLL_THRESHOLD,
    isFabOpen: function () {
      return state.fabOpen;
    },
  };
})();
