/* NextGen Tutors — Admin Dashboard */
(function () {
  "use strict";
  const reduceMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
  const $ = (s, c) => (c || document).querySelector(s);
  const $$ = (s, c) => Array.from((c || document).querySelectorAll(s));

  function initCounters() {
    if (!window.ScrollTrigger) { $$(".counter").forEach((el) => { el.textContent = Number(el.dataset.target).toLocaleString("en-ZA"); }); return; }
    $$(".counter").forEach((el) => {
      ScrollTrigger.create({ trigger: el, start: "top 96%", once: true, onEnter: () => {
        const target = +el.dataset.target;
        if (reduceMotion) { el.textContent = target.toLocaleString("en-ZA"); return; }
        const o = { v: 0 };
        gsap.to(o, { v: target, duration: 1.6, ease: "power2.out", onUpdate: () => { el.textContent = Math.round(o.v).toLocaleString("en-ZA"); } });
      } });
    });
  }

  function initReveals() {
    if (reduceMotion || !window.gsap || !window.ScrollTrigger) {
      $$("[data-reveal]").forEach((el) => { el.style.opacity = 1; el.style.transform = "none"; });
      return;
    }
    gsap.registerPlugin(ScrollTrigger);
    $$(".admin-kpi").forEach((el, i) => gsap.from(el, { opacity: 0, y: 24, duration: 0.7, ease: "power3.out", delay: i * 0.08 }));
    $$("[data-reveal]").forEach((el) => gsap.to(el, { opacity: 1, y: 0, duration: 0.8, ease: "power3.out", scrollTrigger: { trigger: el, start: "top 90%", once: true } }));
  }

  function initApprovalButtons() {
    $$(".btn-approve").forEach((btn) => {
      btn.addEventListener("click", () => {
        const card = btn.closest(".approval-card");
        if (!card) return;
        card.style.transition = "opacity .4s, transform .4s";
        card.style.opacity = "0"; card.style.transform = "translateX(40px)";
        setTimeout(() => card.remove(), 420);
        const panel = document.querySelector(".panel__h .chip");
        if (panel) {
          const current = parseInt(panel.textContent);
          if (current > 1) panel.textContent = (current - 1) + " Applications";
          else panel.textContent = "All Reviewed";
        }
      });
    });
    $$(".btn-reject").forEach((btn) => {
      btn.addEventListener("click", () => {
        const card = btn.closest(".approval-card");
        if (!card) return;
        card.style.transition = "opacity .4s, transform .4s";
        card.style.opacity = "0"; card.style.transform = "translateX(-40px)";
        setTimeout(() => card.remove(), 420);
      });
    });
  }

  function boot() {
    if (window.gsap && window.ScrollTrigger) gsap.registerPlugin(ScrollTrigger);
    initCounters();
    initReveals();
    initApprovalButtons();
    if (window.lucide) lucide.createIcons();
  }
  if (document.readyState === "loading") document.addEventListener("DOMContentLoaded", boot);
  else boot();
})();
