/* ============================================================
   NextGen Tutors — Shared chrome: nav, footer, smooth scroll,
   page transitions. Loaded on EVERY page.
   ============================================================ */
(function () {
  "use strict";
  const reduceMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
  const $ = (s, c) => (c || document).querySelector(s);
  const $$ = (s, c) => Array.from((c || document).querySelectorAll(s));
  const page = document.body.dataset.page || "home";

  const COMPLIANCE_KEYS = ["compliance", "safety", "vetting", "terms", "privacy", "guarantee"];
  const NAV_LINKS = [
    { label: "Find a Tutor", href: "find-a-tutor.html", key: "tutors" },
    { label: "Pricing", href: "pricing.html", key: "pricing" },
    { label: "Become a Tutor", href: "become-a-tutor.html", key: "become" },
    { label: "About", href: "about.html", key: "about" },
    { label: "Contact", href: "contact.html", key: "contact" },
    { label: "Compliance", key: "compliance", type: "dropdown", items: [
      { label: "Safety Guide", href: "safety-guide.html", icon: "shield-check" },
      { label: "Terms & Conditions", href: "terms.html", icon: "file-text" },
      { label: "Privacy Policy", href: "privacy.html", icon: "lock" },
      { label: "Tutor Vetting", href: "tutor-vetting.html", icon: "badge-check" },
      { label: "1st Lesson Guarantee", href: "guarantee.html", icon: "star" },
    ]},
    { label: "Blog", href: "blog.html", key: "blog" },
  ];

  function logoMarkup() {
    return `
      <a class="logo" href="index.html" data-internal aria-label="NextGen Tutors home">
        <img class="logo__img" src="assets/img/logo.png" alt="NextGen Tutors — Next Level Learning" />
      </a>`;
  }

  /* ---------- Inject NAV ---------- */
  function buildNav() {
    const inCompliance = COMPLIANCE_KEYS.includes(page);
    const links = NAV_LINKS.map((l) => {
      if (l.type === "dropdown") {
        const isActive = inCompliance;
        return `<div class="nav__dropdown-wrap">
          <button class="nav__dropdown-trigger${isActive ? " is-active" : ""}" aria-haspopup="true">
            ${l.label}
            <svg class="chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round"><path d="M6 9l6 6 6-6"/></svg>
          </button>
          <div class="nav__dropdown-menu">
            ${l.items.map((item, i) =>
              (i === 2 ? `<div class="nav__dropdown-divider"></div>` : "") +
              `<a class="nav__dropdown-item" href="${item.href}" data-internal><i data-lucide="${item.icon}"></i>${item.label}</a>`
            ).join("")}
          </div>
        </div>`;
      }
      return `<a class="nav__link${l.key === page ? " is-active" : ""}" href="${l.href}" data-internal>${l.label}</a>`;
    }).join("");
    const header = document.createElement("header");
    header.className = "nav nav--transparent";
    header.id = "nav";
    header.innerHTML = `
      <div class="wrap nav__inner">
        ${logoMarkup()}
        <nav class="nav__links" aria-label="Primary">${links}</nav>
        <div class="nav__cta">
          <a class="btn btn--ghost" href="dashboard.html" data-internal>Sign In</a>
        </div>
        <button class="nav__burger" id="burger" aria-label="Open menu">
          <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
        </button>
      </div>`;
    document.body.insertBefore(header, document.body.firstChild);

    const drawer = document.createElement("div");
    drawer.className = "drawer";
    drawer.id = "drawer";
    drawer.innerHTML = `
      <div class="drawer__head">
        <img class="logo__img" src="assets/img/logo.png" alt="NextGen Tutors" style="height:46px" />
        <button class="nav__burger" id="drawer-close" style="display:flex;background:rgba(255,255,255,0.12)" aria-label="Close menu">
          <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.6" stroke-linecap="round"><path d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
      </div>
      ${NAV_LINKS.filter((l) => l.type !== "dropdown").map((l) => `<a class="drawer__link" href="${l.href}" data-internal>${l.label}</a>`).join("")}
      <div class="drawer__sub-label">Compliance</div>
      ${NAV_LINKS.find((l) => l.type === "dropdown").items.map((item) => `<a class="drawer__sub-link" href="${item.href}" data-internal>${item.label}</a>`).join("")}
      <a class="btn btn--ghost btn--block" href="login.html" data-internal style="margin-top:20px">Sign In</a>`;
    document.body.appendChild(drawer);
  }

  /* ---------- Inject FOOTER ---------- */
  function buildFooter() {
    const footer = document.createElement("footer");
    footer.className = "footer";
    footer.innerHTML = `
      <div class="wrap">
        <div class="footer__top">
          <div class="footer__brand">
            <img class="logo__img footer__logo" src="assets/img/logo.png" alt="NextGen Tutors" />
            <p class="footer__desc">South Africa's premier online tutoring platform, connecting Grade 1–12 and varsity students with verified, SACE-registered tutors across all nine provinces.</p>
            <div class="footer__socials">
              <a class="footer__social" href="#" aria-label="Facebook"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M24 12.07C24 5.4 18.63 0 12 0S0 5.4 0 12.07C0 18.1 4.39 23.1 10.13 24v-8.44H7.08v-3.49h3.05V9.41c0-3.02 1.79-4.69 4.53-4.69 1.31 0 2.68.24 2.68.24v2.97h-1.51c-1.49 0-1.96.93-1.96 1.89v2.25h3.33l-.53 3.49h-2.8V24C19.61 23.1 24 18.1 24 12.07Z"/></svg></a>
              <a class="footer__social" href="#" aria-label="Instagram"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2.16c3.2 0 3.58.01 4.85.07 1.17.05 1.8.25 2.23.41.56.22.96.48 1.38.9.42.42.68.82.9 1.38.16.42.36 1.06.41 2.23.06 1.27.07 1.65.07 4.85s-.01 3.58-.07 4.85c-.05 1.17-.25 1.8-.41 2.23-.22.56-.48.96-.9 1.38-.42.42-.82.68-1.38.9-.42.16-1.06.36-2.23.41-1.27.06-1.65.07-4.85.07s-3.58-.01-4.85-.07c-1.17-.05-1.8-.25-2.23-.41a3.7 3.7 0 0 1-1.38-.9 3.7 3.7 0 0 1-.9-1.38c-.16-.42-.36-1.06-.41-2.23C2.17 15.58 2.16 15.2 2.16 12s.01-3.58.07-4.85c.05-1.17.25-1.8.41-2.23.22-.56.48-.96.9-1.38.42-.42.82-.68 1.38-.9.42-.16 1.06-.36 2.23-.41C8.42 2.17 8.8 2.16 12 2.16Zm0 3.68A6.16 6.16 0 1 0 18.16 12 6.16 6.16 0 0 0 12 5.84Zm0 10.16A4 4 0 1 1 16 12a4 4 0 0 1-4 4Zm6.41-10.4a1.44 1.44 0 1 1-1.44-1.44 1.44 1.44 0 0 1 1.44 1.44Z"/></svg></a>
              <a class="footer__social" href="#" aria-label="X"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M18.24 2.25h3.31l-7.23 8.26 8.5 11.24h-6.66l-5.21-6.82-5.97 6.82H1.66l7.73-8.84L1.25 2.25h6.83l4.71 6.23 5.45-6.23Zm-1.16 17.52h1.83L7.01 4.13H5.04l12.04 15.64Z"/></svg></a>
              <a class="footer__social" href="#" aria-label="LinkedIn"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M20.45 20.45h-3.56v-5.57c0-1.33-.02-3.04-1.85-3.04-1.85 0-2.13 1.45-2.13 2.94v5.67H9.35V9h3.41v1.56h.05c.48-.9 1.64-1.85 3.37-1.85 3.6 0 4.27 2.37 4.27 5.46v6.28ZM5.34 7.43a2.07 2.07 0 1 1 0-4.14 2.07 2.07 0 0 1 0 4.14Zm1.78 13.02H3.56V9h3.56v11.45ZM22.22 0H1.77C.79 0 0 .77 0 1.73v20.54C0 23.23.79 24 1.77 24h20.45c.98 0 1.78-.77 1.78-1.73V1.73C24 .77 23.2 0 22.22 0Z"/></svg></a>
            </div>
          </div>
          <div>
            <h4 class="footer__col-h">Explore</h4>
            <a class="footer__link" href="find-a-tutor.html" data-internal>Find a Tutor</a>
            <a class="footer__link" href="pricing.html" data-internal>Pricing</a>
            <a class="footer__link" href="become-a-tutor.html" data-internal>Become a Tutor</a>
            <a class="footer__link" href="blog.html" data-internal>Blog</a>
            <a class="footer__link" href="index.html#subjects" data-internal>Subjects</a>
          </div>
          <div>
            <h4 class="footer__col-h">Company</h4>
            <a class="footer__link" href="about.html" data-internal>About Us</a>
            <a class="footer__link" href="contact.html" data-internal>Contact</a>
            <a class="footer__link" href="safety-guide.html" data-internal>Safety &amp; Trust</a>
            <a class="footer__link" href="tutor-vetting.html" data-internal>Tutor Vetting</a>
            <a class="footer__link" href="guarantee.html" data-internal>Lesson Guarantee</a>
            <a class="footer__link" href="support.html" data-internal>Help &amp; Support</a>
          </div>
          <div>
            <h4 class="footer__col-h">Get In Touch</h4>
            <div class="footer__contact"><i data-lucide="phone"></i> +27 (0)12 345 6789</div>
            <div class="footer__contact"><i data-lucide="mail"></i> hello@nextgentutors.co.za</div>
            <div class="footer__contact"><i data-lucide="map-pin"></i> 123 Education St, Sandton, JHB 2196</div>
            <span class="chip" style="margin-top:14px">🇿🇦 Proudly South African</span>
          </div>
        </div>
        <div class="footer__bottom">
          <span>© 2026 NextGen Tutors (Pty) Ltd. All rights reserved.</span>
          <div style="display:flex;gap:20px">
            <a href="#">Privacy Policy</a>
            <a href="#">Terms</a>
            <a href="#">POPIA Compliance</a>
          </div>
        </div>
      </div>`;
    document.body.appendChild(footer);
  }

  /* ---------- Transition overlay ---------- */
  function buildTransition() {
    const t = document.createElement("div");
    t.className = "page-transition";
    t.id = "page-transition";
    t.innerHTML = `<div class="pt-strip"></div><div class="pt-strip"></div><div class="pt-strip"></div><div class="pt-strip"></div><div class="pt-strip"></div>`;
    document.body.appendChild(t);
    const logo = document.createElement("div");
    logo.className = "page-transition__logo";
    logo.id = "pt-logo";
    logo.innerHTML = `<img class="pt-logo-img" src="assets/img/logo.png" alt="NextGen Tutors" />`;
    document.body.appendChild(logo);
  }

  /* ---------- NAV behaviour ---------- */
  let lenis = null;
  function initNav() {
    const nav = $("#nav");
    if (!nav) return;
    const onScroll = () => {
      if (window.scrollY > 30) { nav.classList.add("is-solid"); nav.classList.remove("nav--transparent"); }
      else { nav.classList.remove("is-solid"); nav.classList.add("nav--transparent"); }
    };
    window.addEventListener("scroll", onScroll, { passive: true });
    onScroll();

    // Dropdown click-toggle for touch/keyboard users
    $$(".nav__dropdown-wrap").forEach((wrap) => {
      const trigger = wrap.querySelector(".nav__dropdown-trigger");
      trigger.addEventListener("click", (e) => {
        const open = wrap.classList.contains("is-open");
        $$(".nav__dropdown-wrap").forEach((w) => w.classList.remove("is-open"));
        if (!open) wrap.classList.add("is-open");
      });
    });
    document.addEventListener("click", (e) => {
      if (!e.target.closest(".nav__dropdown-wrap")) {
        $$(".nav__dropdown-wrap").forEach((w) => w.classList.remove("is-open"));
      }
    });

    const drawer = $("#drawer");
    const burger = $("#burger");
    const drawerClose = $("#drawer-close");
    if (!drawer || !burger || !drawerClose) return;
    burger.addEventListener("click", () => { drawer.classList.add("is-open"); if (lenis) lenis.stop(); });
    const close = () => { drawer.classList.remove("is-open"); if (lenis) lenis.start(); };
    drawerClose.addEventListener("click", close);
    window.__ngtCloseDrawer = close;
  }

  /* ---------- Lenis ---------- */
  function initLenis() {
    if (reduceMotion || typeof Lenis === "undefined") return;
    lenis = new Lenis({ duration: 1.15, easing: (t) => Math.min(1, 1.001 - Math.pow(2, -10 * t)), smoothWheel: true });
    if (window.gsap && window.ScrollTrigger) {
      lenis.on("scroll", ScrollTrigger.update);
      gsap.ticker.add((time) => lenis.raf(time * 1000));
      gsap.ticker.lagSmoothing(0);
    } else {
      const raf = (t) => { lenis.raf(t); requestAnimationFrame(raf); };
      requestAnimationFrame(raf);
    }
    window.NGT_LENIS = lenis;
  }
  function scrollToEl(target) {
    const el = typeof target === "string" ? $(target) : target;
    if (!el) return;
    if (lenis) lenis.scrollTo(el, { offset: -70 });
    else el.scrollIntoView({ behavior: "smooth" });
  }
  window.ngtScrollTo = scrollToEl;

  /* ---------- Page transitions ---------- */
  function isInternal(a) {
    const href = a.getAttribute("href") || "";
    if (a.hasAttribute("data-no-transition")) return false;
    if (a.target === "_blank") return false;
    if (href.startsWith("#")) return false;
    if (/^https?:\/\//i.test(href)) return false;
    if (href.startsWith("mailto:") || href.startsWith("tel:")) return false;
    return href.endsWith(".html") || a.hasAttribute("data-internal");
  }

  function navigateWithTransition(href) {
    const strips = $$(".pt-strip");
    const logo = $("#pt-logo");
    if (reduceMotion || !window.gsap || !strips.length) { window.location.href = href; return; }
    sessionStorage.setItem("ngt_pt", "1");
    if (lenis) lenis.stop();
    gsap.set(strips, { transformOrigin: "bottom" });
    const tl = gsap.timeline({ onComplete: () => { window.location.href = href; } });
    tl.to(strips, { scaleY: 1, duration: 0.5, stagger: 0.06, ease: "power3.inOut" })
      .to(logo, { opacity: 1, duration: 0.25 }, "-=0.25");
  }

  function revealOnLoad() {
    if (sessionStorage.getItem("ngt_pt") !== "1") return;
    sessionStorage.removeItem("ngt_pt");
    const strips = $$(".pt-strip");
    const logo = $("#pt-logo");
    if (!window.gsap || !strips.length) { document.documentElement.classList.remove("pt-incoming"); return; }
    gsap.set(strips, { scaleY: 1, transformOrigin: "top" });
    gsap.set(logo, { opacity: 1 });
    requestAnimationFrame(() => {
      document.documentElement.classList.remove("pt-incoming");
      const tl = gsap.timeline();
      tl.to(logo, { opacity: 0, duration: 0.3, ease: "power2.in" })
        .to(strips, { scaleY: 0, duration: 0.55, stagger: 0.06, ease: "power3.inOut" }, "-=0.1");
    });
  }

  function initTransitionLinks() {
    document.addEventListener("click", (e) => {
      const a = e.target.closest("a");
      if (!a) return;
      const href = a.getAttribute("href") || "";
      // same-page anchor → smooth scroll
      if (href.startsWith("#") && href.length > 1 && $(href)) {
        e.preventDefault();
        if (window.__ngtCloseDrawer) window.__ngtCloseDrawer();
        scrollToEl(href);
        return;
      }
      if (isInternal(a)) {
        e.preventDefault();
        navigateWithTransition(href);
      }
    });
  }

  /* ---------- Heading entrance animations ---------- */
  function initHeadingAnims() {
    if (reduceMotion || !window.gsap) return;
    const SELECTORS = '.admin-bar__title, .pagehead__title, .dash-hello h1, .tdb-head h1';
    const headings = $$(SELECTORS);
    headings.forEach((el) => {
      const hasChildren = !!el.children.length;
      if (!hasChildren) {
        // Word-by-word slot reveal
        const words = el.textContent.trim().split(/\s+/);
        el.innerHTML = words.map(w => {
          const safe = w.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
          return `<span style="display:inline-block;overflow:hidden;vertical-align:bottom"><span class="ngt-hw" style="display:inline-block">${safe}</span></span>`;
        }).join(' ');
        gsap.from(el.querySelectorAll('.ngt-hw'), {
          y: '115%', duration: 0.82, stagger: 0.055, ease: 'power3.out', delay: 0.1
        });
      } else {
        // Has child spans — animate as whole
        gsap.from(el, { y: 44, opacity: 0, duration: 1, ease: 'power3.out', delay: 0.1 });
      }
      // Sub-text
      const sub = el.nextElementSibling;
      if (sub) gsap.from(sub, { y: 22, opacity: 0, duration: 0.85, ease: 'power3.out', delay: 0.44 });
    });
  }

  /* ---------- Boot chrome ---------- */
  function boot() {
    const wpOwnsChrome = !!document.querySelector('.ngt-nav, footer.ngt-footer');
    if (!wpOwnsChrome) {
      buildNav();
      buildFooter();
    }
    buildTransition();
    if (window.lucide) lucide.createIcons();
    if (window.gsap && window.ScrollTrigger) gsap.registerPlugin(ScrollTrigger);
    initLenis();
    initNav();
    initTransitionLinks();
    revealOnLoad();
    initHeadingAnims();
    window.dispatchEvent(new Event("ngt:chrome-ready"));

    // Load floating widgets + RTM chat on all pages
    ["assets/js/floating.js", "assets/js/chat.js", "assets/js/export.js"].forEach((src) => {
      if (!document.querySelector(`script[src="${src}"]`)) {
        const s = document.createElement("script"); s.src = src;
        document.body.appendChild(s);
      }
    });
  }

  if (document.readyState === "loading") document.addEventListener("DOMContentLoaded", boot);
  else boot();
})();
