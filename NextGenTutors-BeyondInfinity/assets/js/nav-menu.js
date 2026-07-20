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

  // Dropdown toggles
  document.addEventListener('click', function (e) {
    var trigger = e.target.closest('.ngt-nav__dropdown-trigger');
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

  if (navToggle && navMenu) {
    navToggle.addEventListener('click', function () {
      var isOpen = navMenu.classList.contains('is-open');
      navMenu.classList.toggle('is-open', !isOpen);
      navToggle.setAttribute('aria-expanded', String(!isOpen));

      // Prevent body scrolling when menu is open on mobile
      document.body.style.overflow = !isOpen ? 'hidden' : '';

      // Close dropdowns when opening mobile menu
      if (!isOpen) {
        closeAllDropdowns();
      }
    });

    // Close mobile menu when clicking outside
    document.addEventListener('click', function (e) {
      if (navMenu.classList.contains('is-open') &&
          !navToggle.contains(e.target) &&
          !navMenu.contains(e.target)) {
        navMenu.classList.remove('is-open');
        navToggle.setAttribute('aria-expanded', 'false');
        document.body.style.overflow = '';
      }
    });
  }
})();
