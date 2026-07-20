/* NextGen Tutors — WordPress Setup / Demo Importer */
(function () {
  "use strict";
  const reduceMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
  const $ = (s, c) => (c || document).querySelector(s);
  const $$ = (s, c) => Array.from((c || document).querySelectorAll(s));

  function initReveals() {
    if (reduceMotion || !window.gsap || !window.ScrollTrigger) {
      $$("[data-reveal],[data-reveal-scale]").forEach((el) => { el.style.opacity = 1; el.style.transform = "none"; });
      return;
    }
    gsap.registerPlugin(ScrollTrigger);
    $$("[data-reveal]").forEach((el) => gsap.to(el, { opacity: 1, y: 0, duration: 0.9, ease: "power3.out", scrollTrigger: { trigger: el, start: "top 88%", once: true } }));
    $$("[data-reveal-scale]").forEach((el) => gsap.to(el, { opacity: 1, scale: 1, duration: 1, ease: "power3.out", scrollTrigger: { trigger: el, start: "top 85%", once: true } }));
  }

  function initDemoImporter() {
    const btn = $("#run-import");
    const fill = $("#prog-fill");
    const pct = $("#prog-pct");
    if (!btn) return;

    const STEPS = [
      { label: "Demo Pages (14)", targetPct: 62 },
      { label: "Demo Tutors (3)", targetPct: 75 },
      { label: "CRM Tags (23)", targetPct: 88 },
      { label: "PayFast Config", targetPct: 100 },
    ];

    btn.addEventListener("click", () => {
      btn.disabled = true;
      btn.textContent = "Importing…";
      const waitItems = $$(".prog-item__status--wait");
      let i = 0;
      function next() {
        if (i >= STEPS.length) {
          btn.textContent = "✓ Import Complete";
          btn.style.background = "var(--lime-deep)";
          return;
        }
        const item = waitItems[i];
        if (item) { item.textContent = "Done"; item.className = "prog-item__status prog-item__status--done"; }
        const p = STEPS[i].targetPct;
        if (fill) fill.style.width = p + "%";
        if (pct) pct.textContent = p + "%";
        i++;
        setTimeout(next, 900);
      }
      setTimeout(next, 600);
    });
  }

  function boot() {
    initReveals();
    initDemoImporter();
    if (window.lucide) lucide.createIcons();
    window.addEventListener("load", () => { if (window.ScrollTrigger) ScrollTrigger.refresh(); });
  }
  if (document.readyState === "loading") document.addEventListener("DOMContentLoaded", boot);
  else boot();
})();
