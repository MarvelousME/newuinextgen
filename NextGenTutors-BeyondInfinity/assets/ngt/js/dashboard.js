/* NextGen Tutors — Student Dashboard */
(function () {
  "use strict";
  const reduceMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
  const $ = (s, c) => (c || document).querySelector(s);
  const $$ = (s, c) => Array.from((c || document).querySelectorAll(s));

  function initCounters() {
    if (!window.ScrollTrigger) { $$(".counter").forEach((el) => (el.textContent = el.dataset.target)); return; }
    $$(".counter").forEach((el) => {
      ScrollTrigger.create({ trigger: el, start: "top 95%", once: true, onEnter: () => {
        const target = +el.dataset.target;
        if (reduceMotion) { el.textContent = target; return; }
        const o = { v: 0 };
        gsap.to(o, { v: target, duration: 1.6, ease: "power2.out", onUpdate: () => (el.textContent = Math.round(o.v)) });
      } });
    });
  }

  function initReveals() {
    if (reduceMotion || !window.gsap || !window.ScrollTrigger) {
      $$("[data-reveal]").forEach((el) => { el.style.opacity = 1; el.style.transform = "none"; });
      return;
    }
    $$(".kpi-card").forEach((el, i) => gsap.from(el, { opacity: 0, y: 24, duration: 0.7, ease: "power3.out", delay: i * 0.08 }));
    $$("[data-reveal]").forEach((el) => gsap.to(el, { opacity: 1, y: 0, duration: 0.8, ease: "power3.out", scrollTrigger: { trigger: el, start: "top 90%", once: true } }));
  }

  function initReferral() {
    const btn = $("#copy-ref"), input = $("#referral-link");
    if (!btn || !input) return;
    btn.addEventListener("click", () => {
      input.select();
      try { document.execCommand("copy"); } catch (e) {}
      if (navigator.clipboard) navigator.clipboard.writeText(input.value).catch(() => {});
      const old = btn.textContent;
      btn.textContent = "✓ Copied!";
      setTimeout(() => (btn.textContent = old), 1800);
    });
  }

  function boot() {
    if (window.gsap && window.ScrollTrigger) gsap.registerPlugin(ScrollTrigger);
    initCounters();
    initReveals();
    initReferral();
    if (window.lucide) lucide.createIcons();
  }
  if (document.readyState === "loading") document.addEventListener("DOMContentLoaded", boot);
  else boot();
})();
