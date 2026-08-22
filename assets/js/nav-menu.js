/**
 * Grouped nav dropdown toggles + mobile menu toggle + float dock body class.
 */
(function () {
  'use strict';

  if (document.getElementById('float-dock')) {
    document.body.classList.add('has-float-dock');
  }

  function closeAllDropdowns(except) {
    document.querySelectorAll('.ngt-nav__dropdown.is-open, .menu-item-has-children.is-open').forEach(function (el) {
      if (el !== except) {
        el.classList.remove('is-open');
        var btn = el.querySelector('.ngt-nav__dropdown-trigger, .menu-item-has-children > a');
        if (btn) { btn.setAttribute('aria-expanded', 'false'); }
      }
    });
  }

  // Dropdown toggles (fallback button + WP custom parent links)
  document.addEventListener('click', function (e) {
    var trigger = e.target.closest('.ngt-nav__dropdown-trigger');
    if (!trigger) {
      var parentLink = e.target.closest('.menu-item-has-children > a');
      if (parentLink) {
        var href = (parentLink.getAttribute('href') || '').trim();
        if (href === '' || href === '#' || href.indexOf('#') === 0 && href.length <= 1) {
          trigger = parentLink;
        }
      }
    }
    if (trigger) {
      e.preventDefault();
      var wrap = trigger.closest('.ngt-nav__dropdown, .menu-item-has-children');
      if (!wrap) { return; }
      var open = wrap.classList.contains('is-open');
      closeAllDropdowns();
      if (!open) {
        wrap.classList.add('is-open');
        trigger.setAttribute('aria-expanded', 'true');
      }
      return;
    }
    if (!e.target.closest('.ngt-nav__dropdown, .menu-item-has-children')) {
      closeAllDropdowns();
    }
  });

  // Mobile menu toggle
  var navToggle = document.querySelector('.ngt-nav__toggle');
  var navMenu = document.querySelector('.ngt-nav__menu');

  function setOffcanvas(open) {
    navMenu.classList.toggle('is-open', open);
    navToggle.setAttribute('aria-expanded', String(open));
    document.body.classList.toggle('ngt-offcanvas-open', open);
    document.body.style.overflow = open ? 'hidden' : '';
    if (open) {
      closeAllDropdowns();
    }
  }

  function isMobileNav() {
    return window.matchMedia('(max-width: 1099px)').matches;
  }

  if (navToggle && navMenu) {
    navToggle.addEventListener('click', function () {
      setOffcanvas(!navMenu.classList.contains('is-open'));
    });

    document.addEventListener('click', function (e) {
      if (navMenu.classList.contains('is-open') &&
          !navToggle.contains(e.target) &&
          !navMenu.contains(e.target)) {
        setOffcanvas(false);
      }
    });

    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && navMenu.classList.contains('is-open')) {
        setOffcanvas(false);
        navToggle.focus();
      }
    });

    // Portrait→landscape / mobile→desktop must not leave body scroll-locked.
    var mq = window.matchMedia('(max-width: 1099px)');
    function onBreakpointChange() {
      if (!isMobileNav() && navMenu.classList.contains('is-open')) {
        setOffcanvas(false);
      }
    }
    if (typeof mq.addEventListener === 'function') {
      mq.addEventListener('change', onBreakpointChange);
    } else if (typeof mq.addListener === 'function') {
      mq.addListener(onBreakpointChange);
    }
    window.addEventListener('orientationchange', function () {
      window.setTimeout(onBreakpointChange, 50);
    });
  }
})();
