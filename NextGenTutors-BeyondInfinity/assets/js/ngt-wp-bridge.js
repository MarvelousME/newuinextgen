/**
 * WordPress bridge for NextGen design system assets.
 * Does NOT inject chrome.js nav/footer — theme templates own those.
 */
(function () {
  'use strict';

  if ('scrollRestoration' in history) {
    history.scrollRestoration = 'manual';
  }

  var cfg = window.NGT_WP || {};
  window.NGT_SKIP_CSS_INJECT = true;

  function $(s, c) { return (c || document).querySelector(s); }
  function $$(s, c) { return Array.from((c || document).querySelectorAll(s)); }

  /* Lucide icons */
  function initLucide() {
    if (typeof lucide !== 'undefined' && lucide.createIcons) {
      lucide.createIcons();
    }
  }

  /* NGT nav scroll solid state on theme header */
  function initNavScroll() {
    var nav = $('.ngt-nav');
    if (!nav) return;
    var onScroll = function () {
      if (window.scrollY > 40) {
        nav.classList.add('is-solid', 'ngt-nav--solid');
      } else {
        nav.classList.remove('is-solid', 'ngt-nav--solid');
      }
    };
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();
  }

  /* Rewrite img src for bundled logos in imported HTML content */
  function fixAssetPaths() {
    if (!cfg.imgUrl) return;
    $$('.bi-theme-content img[src*="assets/img/"]').forEach(function (img) {
      var file = img.getAttribute('src').split('/').pop();
      img.src = cfg.imgUrl + file;
    });
  }

  /* Back to top in floating dock */
  function initBackToTop() {
    var btn = $('#back-to-top');
    if (!btn) return;
    window.addEventListener('scroll', function () {
      btn.classList.toggle('is-visible', window.scrollY > 500);
    }, { passive: true });
    btn.addEventListener('click', function () {
      if (window.NGT_LENIS && typeof window.NGT_LENIS.scrollTo === 'function') {
        window.NGT_LENIS.scrollTo(0, { duration: 1.1 });
      } else {
        window.scrollTo({ top: 0, behavior: 'smooth' });
      }
    });
  }

  /* WhatsApp link from theme option */
  function patchWhatsAppLink() {
    var wa = $('.fdock-btn--wa');
    if (!wa || !cfg.waNumber) return;
    var text = encodeURIComponent('Hi NextGen Tutors, I need help');
    wa.href = 'https://wa.me/' + cfg.waNumber + '?text=' + text;
  }

  document.addEventListener('DOMContentLoaded', function () {
    if (!window.location.hash || window.location.hash === '#') {
      window.scrollTo(0, 0);
      if (window.NGT_LENIS && typeof window.NGT_LENIS.scrollTo === 'function') {
        window.NGT_LENIS.scrollTo(0, { immediate: true });
      }
    }
    initLucide();
    initNavScroll();
    fixAssetPaths();
    initBackToTop();
    patchWhatsAppLink();
    document.body.classList.remove('preload');

    var matchBtn = document.getElementById('match-dock-btn');
    if (matchBtn) {
      matchBtn.addEventListener('click', function (e) {
        if (document.getElementById('ngc-match-panel')) {
          e.preventDefault();
          e.stopPropagation();
          document.dispatchEvent(new CustomEvent('ngc:open-match-widget'));
        }
      });
    }

    var heroForm = document.querySelector('.bi-hero-search');
    if (heroForm && document.getElementById('ngc-match-panel')) {
      heroForm.addEventListener('submit', function (e) {
        e.preventDefault();
        var subjectEl = heroForm.querySelector('[name="subject"]');
        var locationEl = heroForm.querySelector('[name="location"]');
        var subjectVal = subjectEl && subjectEl.options ? subjectEl.options[subjectEl.selectedIndex].text : '';
        if (subjectEl && subjectEl.value && subjectVal && subjectVal.indexOf('Choose') === 0) {
          subjectVal = subjectEl.value;
        }
        document.dispatchEvent(new CustomEvent('ngc:open-match-widget', {
          detail: {
            subject: subjectVal || (subjectEl ? subjectEl.value : ''),
            province: locationEl ? locationEl.value : ''
          }
        }));
      });
    }
  });

  document.addEventListener('ngt:icons', initLucide);
})();
