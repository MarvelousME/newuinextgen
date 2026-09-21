/* NextGen Tutors — Tutor Dashboard */
(function () {
  "use strict";
  const reduceMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
  const $ = (s, c) => (c || document).querySelector(s);
  const $$ = (s, c) => Array.from((c || document).querySelectorAll(s));

  const MONTHS = [
    { lbl: "Jan", val: 5200 }, { lbl: "Feb", val: 6100 }, { lbl: "Mar", val: 9010 },
    { lbl: "Apr", val: 7650 }, { lbl: "May", val: 8415 }, { lbl: "Jun", val: 9350, current: true },
  ];

  function renderEarningsChart() {
    const chart = $("#earn-chart");
    if (!chart) return;
    const max = Math.max(...MONTHS.map((m) => m.val));
    chart.innerHTML = MONTHS.map((m) => {
      const pct = Math.round((m.val / max) * 100);
      return `<div class="earn-bar${m.current ? " is-current" : ""}">
        <div class="earn-bar__fill" style="height:${reduceMotion ? pct : 0}%;max-height:${pct}%" data-val="R${m.val.toLocaleString("en-ZA")}"></div>
        <div class="earn-bar__lbl">${m.lbl}</div>
      </div>`;
    }).join("");
    if (!reduceMotion && window.gsap) {
      setTimeout(() => {
        MONTHS.forEach((m, i) => {
          const bar = chart.querySelectorAll(".earn-bar__fill")[i];
          const pct = Math.round((m.val / max) * 100);
          gsap.to(bar, { height: pct + "%", duration: 1.2, ease: "power3.out", delay: i * 0.1 });
        });
      }, 400);
    }
  }

  function initCounters() {
    if (!window.ScrollTrigger) { $$(".counter").forEach((el) => { el.textContent = Number(el.dataset.target).toLocaleString("en-ZA"); }); return; }
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
    gsap.registerPlugin(ScrollTrigger);
    $$(".admin-kpi").forEach((el, i) => gsap.from(el, { opacity: 0, y: 24, duration: 0.7, ease: "power3.out", delay: i * 0.08 }));
    $$("[data-reveal]").forEach((el) => gsap.to(el, { opacity: 1, y: 0, duration: 0.8, ease: "power3.out", scrollTrigger: { trigger: el, start: "top 90%", once: true } }));
  }

  function boot() {
    if (window.gsap && window.ScrollTrigger) gsap.registerPlugin(ScrollTrigger);
    renderEarningsChart();
    initCounters();
    initReveals();
    if (window.lucide) lucide.createIcons();
  }
  if (document.readyState === "loading") document.addEventListener("DOMContentLoaded", boot);
  else boot();
})();
