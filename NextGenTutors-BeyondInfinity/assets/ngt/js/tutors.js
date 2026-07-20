/* NextGen Tutors — Find a Tutor directory */
(function () {
  "use strict";
  const NGT = window.NGT;
  const reduceMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
  const $ = (s, c) => (c || document).querySelector(s);
  const $$ = (s, c) => Array.from((c || document).querySelectorAll(s));

  const state = { subject: "all", format: "all", maxPrice: 500, sort: "rating" };

  function cardHTML(t) {
    const online = t.type === "online" || t.type === "both";
    const home = t.type === "personal" || t.type === "both";
    const star = `<svg viewBox="0 0 24 24"><polygon points="12 2 15 9 22 9.5 17 14.5 18.5 22 12 18 5.5 22 7 14.5 2 9.5 9 9"/></svg>`;
    return `
      <div class="tutor-card3d" data-reveal>
        <div class="tutor-card">
          <div class="tutor-card__photo">
            <img src="${t.img}" alt="${t.name}" referrerpolicy="no-referrer" />
            <div class="tutor-badges">
              ${online ? `<span class="tutor-badge tutor-badge--online"><i data-lucide="monitor"></i>Online</span>` : ""}
              ${home ? `<span class="tutor-badge tutor-badge--home"><i data-lucide="users"></i>In-Person</span>` : ""}
            </div>
            <div class="tutor-price"><span class="r">R</span><span class="n">${t.rate}</span><span class="h">/hr</span></div>
          </div>
          <div class="tutor-card__body">
            <div class="tutor-card__top">
              <h3 class="tutor-card__name">${t.name}</h3>
              <span class="tutor-rating">${star}${t.rating.toFixed(2)}</span>
            </div>
            <div class="tutor-card__degree"><i data-lucide="award"></i><span>${t.degree}</span></div>
            <p class="tutor-card__bio">“${t.bio}”</p>
            <div class="tutor-tags">${t.subjects.map((s) => `<span class="tutor-tag">${s}</span>`).join("")}</div>
            <div class="tutor-card__btns">
              <a class="btn" style="background:var(--slate-100);color:var(--navy);padding:11px" href="tutor-profile.html" data-internal>View Bio</a>
              <a class="btn btn--lime" style="padding:11px" href="tutor-profile.html#calendar" data-internal>Book Class</a>
            </div>
          </div>
        </div>
      </div>`;
  }

  function buildSubjectChips() {
    const tags = new Set();
    NGT.tutors.forEach((t) => t.subjects.forEach((s) => tags.add(s)));
    const wrap = $("#filter-subjects");
    wrap.innerHTML =
      `<button class="fchip is-active" data-subject="all">All</button>` +
      Array.from(tags).sort().map((s) => `<button class="fchip" data-subject="${s}">${s}</button>`).join("");
  }

  function filterSort() {
    let list = NGT.tutors.filter((t) => {
      if (state.subject !== "all" && !t.subjects.includes(state.subject)) return false;
      if (state.format === "online" && !(t.type === "online" || t.type === "both")) return false;
      if (state.format === "personal" && !(t.type === "personal" || t.type === "both")) return false;
      if (t.rate > state.maxPrice) return false;
      return true;
    });
    if (state.sort === "rating") list.sort((a, b) => b.rating - a.rating);
    if (state.sort === "price-low") list.sort((a, b) => a.rate - b.rate);
    if (state.sort === "price-high") list.sort((a, b) => b.rate - a.rate);
    return list;
  }

  function render() {
    const grid = $("#dir-grid");
    const list = filterSort();
    $("#result-count").textContent = list.length;
    grid.innerHTML = list.length
      ? list.map(cardHTML).join("")
      : `<div class="dir-empty">No tutors match those filters yet — try widening your budget or format.</div>`;
    if (window.lucide) lucide.createIcons();
    if (!reduceMotion && window.gsap) {
      gsap.fromTo(grid.querySelectorAll(".tutor-card3d"), { opacity: 0, y: 26 }, { opacity: 1, y: 0, duration: 0.6, stagger: 0.06, ease: "power3.out", overwrite: true });
    }
  }

  function initFilters() {
    $("#filter-subjects").addEventListener("click", (e) => {
      const b = e.target.closest(".fchip"); if (!b) return;
      $$("#filter-subjects .fchip").forEach((c) => c.classList.remove("is-active"));
      b.classList.add("is-active"); state.subject = b.dataset.subject; render();
    });
    $("#filter-format").addEventListener("click", (e) => {
      const b = e.target.closest(".fchip"); if (!b) return;
      $$("#filter-format .fchip").forEach((c) => c.classList.remove("is-active"));
      b.classList.add("is-active"); state.format = b.dataset.format; render();
    });
    const price = $("#filter-price");
    price.addEventListener("input", () => { state.maxPrice = +price.value; $("#price-val").textContent = "R" + price.value; render(); });
    document.querySelector(".dir-bar .filter-chips").addEventListener("click", (e) => {
      const b = e.target.closest(".fchip"); if (!b) return;
      $$(".dir-bar .fchip").forEach((c) => c.classList.remove("is-active"));
      b.classList.add("is-active"); state.sort = b.dataset.sort; render();
    });
    $("#clear-filters").addEventListener("click", () => {
      state.subject = "all"; state.format = "all"; state.maxPrice = 500; state.sort = "rating";
      $$("#filter-subjects .fchip, #filter-format .fchip").forEach((c) => c.classList.remove("is-active"));
      $("#filter-subjects .fchip").classList.add("is-active");
      $('#filter-format .fchip[data-format="all"]').classList.add("is-active");
      price.value = 500; $("#price-val").textContent = "R500";
      $$(".dir-bar .fchip").forEach((c) => c.classList.remove("is-active"));
      $('.dir-bar .fchip[data-sort="rating"]').classList.add("is-active");
      render();
    });
  }

  function initReveals() {
    if (reduceMotion || !window.gsap || !window.ScrollTrigger) {
      $$("[data-reveal],[data-reveal-x],[data-reveal-scale]").forEach((el) => { el.style.opacity = 1; el.style.transform = "none"; });
      return;
    }
    $$("[data-reveal]").forEach((el) => { if (el.matches(".step")) return; gsap.to(el, { opacity: 1, y: 0, duration: 0.9, ease: "power3.out", scrollTrigger: { trigger: el, start: "top 88%", once: true } }); });
    $$("[data-reveal-scale]").forEach((el) => gsap.to(el, { opacity: 1, scale: 1, duration: 1, ease: "power3.out", scrollTrigger: { trigger: el, start: "top 85%", once: true } }));
    if ($("#how, .steps") || $(".steps")) {
      ScrollTrigger.batch(".steps .step", { start: "top 88%", onEnter: (b) => gsap.to(b, { opacity: 1, y: 0, stagger: 0.1, duration: 0.8, ease: "power3.out", overwrite: true }) });
    }
  }

  function boot() {
    if (window.gsap && window.ScrollTrigger) gsap.registerPlugin(ScrollTrigger);
    buildSubjectChips();
    initFilters();
    render();
    initReveals();
  }
  if (document.readyState === "loading") document.addEventListener("DOMContentLoaded", boot);
  else boot();
})();
