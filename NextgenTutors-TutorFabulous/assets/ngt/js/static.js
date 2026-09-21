/* NextGen Tutors — shared logic for static pages (become / about / contact) */
(function () {
  "use strict";
  const NGT = window.NGT;
  const reduceMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
  const $ = (s, c) => (c || document).querySelector(s);
  const $$ = (s, c) => Array.from((c || document).querySelectorAll(s));

  /* Reveals */
  function initReveals() {
    if (reduceMotion || !window.gsap || !window.ScrollTrigger) {
      $$("[data-reveal],[data-reveal-x],[data-reveal-scale]").forEach((el) => { el.style.opacity = 1; el.style.transform = "none"; });
      return;
    }
    gsap.registerPlugin(ScrollTrigger);
    const batched = ".value-card, .step, .stat-card, .tl-item";
    $$("[data-reveal]").forEach((el) => { if (el.matches(batched)) return; gsap.to(el, { opacity: 1, y: 0, duration: 0.9, ease: "power3.out", scrollTrigger: { trigger: el, start: "top 87%", once: true } }); });
    $$("[data-reveal-x]").forEach((el) => gsap.to(el, { opacity: 1, x: 0, duration: 1, ease: "power3.out", scrollTrigger: { trigger: el, start: "top 85%", once: true } }));
    $$("[data-reveal-scale]").forEach((el) => gsap.to(el, { opacity: 1, scale: 1, duration: 1, ease: "power3.out", scrollTrigger: { trigger: el, start: "top 85%", once: true } }));
    [".values-grid .value-card", ".steps .step", ".stat-grid .stat-card", ".timeline .tl-item"].forEach((sel) => {
      if (!$(sel)) return;
      ScrollTrigger.batch(sel, { start: "top 88%", onEnter: (b) => gsap.to(b, { opacity: 1, y: 0, stagger: 0.1, duration: 0.8, ease: "power3.out", overwrite: true }) });
    });
  }

  /* Counters */
  function initCounters() {
    if (!window.ScrollTrigger) { $$(".counter").forEach((el) => (el.textContent = (+el.dataset.target).toLocaleString("en-US"))); return; }
    $$(".counter").forEach((el) => {
      ScrollTrigger.create({ trigger: el, start: "top 92%", once: true, onEnter: () => {
        const target = +el.dataset.target;
        const fmt = (n) => (n >= 1000 ? Math.round(n).toLocaleString("en-US") : Math.round(n).toString());
        if (reduceMotion) { el.textContent = fmt(target); return; }
        const o = { v: 0 };
        gsap.to(o, { v: target, duration: 2, ease: "power2.out", onUpdate: () => (el.textContent = fmt(o.v)) });
      } });
    });
  }

  /* FAQ accordion */
  function initFAQ() {
    $$(".faq-item").forEach((item) => {
      const q = $(".faq-q", item), a = $(".faq-a", item);
      if (!q) return;
      q.addEventListener("click", () => {
        const open = item.classList.contains("is-open");
        $$(".faq-item").forEach((o) => { o.classList.remove("is-open"); const oa = $(".faq-a", o); if (oa) oa.style.maxHeight = null; });
        if (!open) { item.classList.add("is-open"); a.style.maxHeight = a.scrollHeight + "px"; }
      });
    });
  }

  /* Earnings calculator (become page) */
  function initEarnings() {
    const levelSeg = $("#et-level"), hours = $("#et-hours");
    if (!levelSeg || !hours) return;
    let rate = 225;
    const update = () => {
      const h = +hours.value;
      $("#et-hours-val").textContent = h;
      const week = rate * h, month = week * 4.3;
      $("#et-week").textContent = Math.round(week).toLocaleString("en-US");
      $("#et-month").textContent = Math.round(month).toLocaleString("en-US");
    };
    levelSeg.addEventListener("click", (e) => {
      const b = e.target.closest("button"); if (!b) return;
      $$("#et-level button").forEach((x) => x.classList.remove("is-active"));
      b.classList.add("is-active"); rate = +b.dataset.rate; update();
    });
    hours.addEventListener("input", update);
    update();
  }

  /* Contact form */
  function initContactForm() {
    const form = $("#contact-form");
    if (!form) return;
    const sel = $("#cf-subject");
    if (sel && NGT) NGT.subjects.forEach((s) => { const o = document.createElement("option"); o.textContent = s.name; sel.appendChild(o); });
    form.addEventListener("submit", (e) => {
      e.preventDefault();
      const ok = $("#cf-success");
      ok.classList.add("show");
      if (window.lucide) lucide.createIcons();
      form.querySelectorAll("input,textarea").forEach((f) => (f.value = ""));
      if (window.gsap && !reduceMotion) gsap.fromTo(ok, { y: 10, opacity: 0 }, { y: 0, opacity: 1, duration: 0.5, ease: "power3.out" });
    });
  }

  /* Newsletter form */
  function initNewsletter() {
    const form = $("#newsletter-form");
    if (!form) return;
    form.addEventListener("submit", (e) => {
      e.preventDefault();
      form.style.display = "none";
      const ok = $("#newsletter-success");
      if (ok) {
        ok.style.display = "block";
        if (window.gsap && !reduceMotion) gsap.fromTo(ok, { y: 10, opacity: 0 }, { y: 0, opacity: 1, duration: 0.5, ease: "power3.out" });
      }
    });
  }

  function boot() {
    initReveals();
    initCounters();
    initFAQ();
    initEarnings();
    initContactForm();
    initNewsletter();
    if (window.lucide) lucide.createIcons();
  }
  if (document.readyState === "loading") document.addEventListener("DOMContentLoaded", boot);
  else boot();
})();
