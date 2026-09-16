/* NextGen Tutors — Pricing calculator + FAQ */
(function () {
  "use strict";
  const reduceMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
  const $ = (s, c) => (c || document).querySelector(s);
  const $$ = (s, c) => Array.from((c || document).querySelectorAll(s));

  const st = { level: "high", format: "online", commit: "1-3", lessons: 4 };

  function calcRate() {
    if (st.level === "tertiary") return 500;
    if (st.lessons >= 12 || st.commit === "12+") return 300;
    if (st.format === "online") return st.commit === "3-12" ? 300 : 320;
    return st.commit === "3-12" ? 320 : 350;
  }
  function payout() {
    if (st.level === "tertiary") return 350;
    return st.format === "online" ? 200 : 250;
  }
  function baseRate() { return st.format === "online" ? 320 : 350; }

  function update() {
    const rate = calcRate();
    const total = rate * st.lessons;
    const saved = st.level === "tertiary" ? 0 : (baseRate() - rate) * st.lessons;
    $("#calc-rate").textContent = rate;
    $("#calc-payout").textContent = payout();
    $("#calc-total").textContent = total.toLocaleString("en-US");
    const saveEl = $("#calc-save");
    if (saved > 0) { saveEl.style.display = "flex"; $("#calc-saved").textContent = saved.toLocaleString("en-US"); }
    else saveEl.style.display = "none";
    // tertiary hides format + commitment
    const tertiary = st.level === "tertiary";
    $("#calc-format-field").style.display = tertiary ? "none" : "";
    $("#calc-commit-field").style.display = tertiary ? "none" : "";
  }

  function seg(id, key, after) {
    const root = $(id);
    if (!root) return;
    root.addEventListener("click", (e) => {
      const b = e.target.closest("button"); if (!b) return;
      $$(id + " button").forEach((x) => x.classList.remove("is-active"));
      b.classList.add("is-active");
      st[key] = b.dataset[key === "commit" ? "commit" : key];
      if (after) after(b);
      update();
    });
  }

  function initCalc() {
    // Prototype calculator (#calc-*) — skip when page uses theme bi-calc controls only.
    if (!$("#calc-level") || !$("#calc-lessons") || !$("#calc-rate")) return;

    seg("#calc-level", "level", (b) => {
      // varsity forces commitment reset
      if (b.dataset.level === "tertiary") { st.commit = "1-3"; }
    });
    seg("#calc-format", "format");
    seg("#calc-commit", "commit", (b) => {
      if (b.dataset.commit === "12+" && st.lessons < 12) { st.lessons = 12; $("#calc-lessons").value = 12; $("#calc-lessons-val").textContent = 12; }
    });
    const range = $("#calc-lessons");
    range.addEventListener("input", () => {
      st.lessons = +range.value; $("#calc-lessons-val").textContent = range.value;
      if (st.lessons < 12 && st.commit === "12+") {
        st.commit = "1-3";
        $$("#calc-commit button").forEach((x) => x.classList.toggle("is-active", x.dataset.commit === "1-3"));
      }
      update();
    });
    update();
  }

  function initFAQ() {
    $$(".faq-item").forEach((item) => {
      const q = $(".faq-q", item), a = $(".faq-a", item);
      if (!q || !a) return;
      q.addEventListener("click", () => {
        const open = item.classList.contains("is-open");
        $$(".faq-item").forEach((o) => { o.classList.remove("is-open"); const fa = $(".faq-a", o); if (fa) fa.style.maxHeight = null; });
        if (!open) { item.classList.add("is-open"); a.style.maxHeight = a.scrollHeight + "px"; }
      });
    });
  }

  function initReveals() {
    if (reduceMotion || !window.gsap || !window.ScrollTrigger) return;
    gsap.registerPlugin(ScrollTrigger);
    $$("[data-reveal]").forEach((el) => { if (el.matches(".price-card")) return; gsap.to(el, { opacity: 1, y: 0, duration: 0.9, ease: "power3.out", scrollTrigger: { trigger: el, start: "top 86%", once: true } }); });
    $$("[data-reveal-scale]").forEach((el) => gsap.to(el, { opacity: 1, scale: 1, duration: 1, ease: "power3.out", scrollTrigger: { trigger: el, start: "top 85%", once: true } }));
    ScrollTrigger.batch(".price-grid .price-card", { start: "top 88%", onEnter: (b) => gsap.to(b, { opacity: 1, y: 0, stagger: 0.1, duration: 0.8, ease: "power3.out", overwrite: true }) });
  }

  function boot() { initCalc(); initFAQ(); initReveals(); }
  if (document.readyState === "loading") document.addEventListener("DOMContentLoaded", boot);
  else boot();
})();
