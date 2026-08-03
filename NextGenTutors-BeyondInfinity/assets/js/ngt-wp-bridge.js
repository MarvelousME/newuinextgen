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

  function hasDeepLinkHash() {
    var hash = window.location.hash || '';
    if (!hash || hash === '#') return false;
    try {
      return !!document.querySelector(hash);
    } catch (e) {
      return false;
    }
  }

  function forceScrollTop() {
    if (hasDeepLinkHash()) return;
    window.scrollTo(0, 0);
    if (document.documentElement) document.documentElement.scrollTop = 0;
    if (document.body) document.body.scrollTop = 0;
    if (window.NGT_LENIS && typeof window.NGT_LENIS.scrollTo === 'function') {
      window.NGT_LENIS.scrollTo(0, { immediate: true });
    }
  }

  /* Beat late restorers (images, widgets, bfcache) that re-scroll after first paint. */
  function pinScrollTopOnLoad() {
    if (hasDeepLinkHash()) return;
    forceScrollTop();
    var frames = 0;
    function tick() {
      forceScrollTop();
      frames += 1;
      if (frames < 12) requestAnimationFrame(tick);
    }
    requestAnimationFrame(tick);
    window.setTimeout(forceScrollTop, 0);
    window.setTimeout(forceScrollTop, 120);
    window.setTimeout(forceScrollTop, 400);
  }

  /* Lucide icons */
  function initLucide() {
    if (typeof lucide !== 'undefined' && lucide.createIcons) {
      lucide.createIcons();
    }
  }

  /* Nav scroll solid state owned by bi-sticky-ui.js (single listener). */
  function initNavScroll() {
    /* no-op — kept so older call sites remain safe */
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

  /* ---- Page transition (logo + data-ngt-transition links) ---- */
  var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  function ensureTransitionDom() {
    if ($('#page-transition')) return;
    var t = document.createElement('div');
    t.className = 'page-transition';
    t.id = 'page-transition';
    t.setAttribute('aria-hidden', 'true');
    t.innerHTML = '<div class="pt-strip"></div><div class="pt-strip"></div><div class="pt-strip"></div><div class="pt-strip"></div><div class="pt-strip"></div>';
    document.body.appendChild(t);
    var logo = document.createElement('div');
    logo.className = 'page-transition__logo';
    logo.id = 'pt-logo';
    logo.innerHTML = '<div class="logo__name">NextGen<span>Tutors</span></div>';
    document.body.appendChild(logo);
  }

  function sameOriginPath(url) {
    try {
      var u = new URL(url, window.location.href);
      return u.origin === window.location.origin ? (u.pathname + u.search + u.hash) : null;
    } catch (e) {
      return null;
    }
  }

  function navigateWithTransition(href) {
    ensureTransitionDom();
    var strips = $$('.pt-strip');
    var logo = $('#pt-logo');
    try { sessionStorage.setItem('ngt_pt', '1'); } catch (e) {}

    if (reduceMotion || !strips.length) {
      window.location.href = href;
      return;
    }

    if (window.gsap) {
      window.gsap.set(strips, { transformOrigin: 'bottom', scaleY: 0 });
      var tl = window.gsap.timeline({
        onComplete: function () { window.location.href = href; }
      });
      tl.to(strips, { scaleY: 1, duration: 0.48, stagger: 0.055, ease: 'power3.inOut' })
        .to(logo, { opacity: 1, duration: 0.22 }, '-=0.22');
      return;
    }

    // CSS fallback when GSAP is not on the page
    document.documentElement.classList.add('pt-covering');
    strips.forEach(function (s, i) {
      s.style.transition = 'transform .45s cubic-bezier(.22,1,.36,1) ' + (i * 55) + 'ms';
      s.style.transformOrigin = 'bottom';
      s.style.transform = 'scaleY(1)';
    });
    if (logo) {
      logo.style.transition = 'opacity .25s ease .2s';
      logo.style.opacity = '1';
    }
    window.setTimeout(function () { window.location.href = href; }, 700);
  }

  window.NGT_pageTransition = function (href, evt) {
    if (evt && (evt.metaKey || evt.ctrlKey || evt.shiftKey || evt.altKey || evt.button === 1)) {
      return true;
    }
    if (evt) evt.preventDefault();
    var target = href || (cfg.homeUrl || '/');
    var path = sameOriginPath(target);
    if (!path) {
      window.location.href = target;
      return false;
    }
    // Same page (home): still play a short wipe then scroll/reload home.
    if (path === window.location.pathname + window.location.search + window.location.hash
        || (path.replace(/\/$/, '') === window.location.pathname.replace(/\/$/, '') && !window.location.hash)) {
      navigateWithTransition(cfg.homeUrl || target);
      return false;
    }
    navigateWithTransition(target);
    return false;
  };

  function revealOnLoad() {
    try {
      if (sessionStorage.getItem('ngt_pt') !== '1') return;
      sessionStorage.removeItem('ngt_pt');
    } catch (e) { return; }
    ensureTransitionDom();
    document.documentElement.classList.add('pt-incoming');
    var strips = $$('.pt-strip');
    var logo = $('#pt-logo');
    if (!window.gsap || !strips.length) {
      document.documentElement.classList.remove('pt-incoming');
      return;
    }
    window.gsap.set(strips, { scaleY: 1, transformOrigin: 'top' });
    window.gsap.set(logo, { opacity: 1 });
    requestAnimationFrame(function () {
      document.documentElement.classList.remove('pt-incoming');
      var tl = window.gsap.timeline();
      tl.to(logo, { opacity: 0, duration: 0.28, ease: 'power2.in' })
        .to(strips, { scaleY: 0, duration: 0.5, stagger: 0.05, ease: 'power3.inOut' }, '-=0.08');
    });
  }

  function initTransitionLinks() {
    ensureTransitionDom();
    document.addEventListener('click', function (e) {
      var a = e.target.closest('a.ngt-nav__logo, a.ngi-logo, a[data-ngt-transition]');
      if (!a) return;
      if (e.metaKey || e.ctrlKey || e.shiftKey || e.altKey || e.button === 1) return;
      var href = a.getAttribute('href') || '';
      if (!href || href.startsWith('#') || href.startsWith('mailto:') || href.startsWith('tel:')) return;
      e.preventDefault();
      window.NGT_pageTransition(a.href, e);
    });
  }

  forceScrollTop();
  try {
    if (sessionStorage.getItem('ngt_pt') === '1') {
      document.documentElement.classList.add('pt-incoming');
    }
  } catch (e) {}

  document.addEventListener('DOMContentLoaded', function () {
    pinScrollTopOnLoad();
    initLucide();
    initNavScroll();
    fixAssetPaths();
    initBackToTop();
    patchWhatsAppLink();
    initTransitionLinks();
    revealOnLoad();
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

  window.addEventListener('load', pinScrollTopOnLoad);
  window.addEventListener('pageshow', function () {
    pinScrollTopOnLoad();
  });

  document.addEventListener('ngt:icons', initLucide);
})();
