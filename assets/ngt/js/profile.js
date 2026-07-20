/* NextGen Tutors — Tutor Profile (documents modal + booking calendar) */
(function () {
  "use strict";
  const reduceMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
  const $ = (s, c) => (c || document).querySelector(s);
  const $$ = (s, c) => Array.from((c || document).querySelectorAll(s));

  /* ---------- Documents ---------- */
  const DOCS = [
    { id: "id", title: "SA ID Document", issuer: "Dept. of Home Affairs", date: "Verified 12 Jan 2026", redact: true },
    { id: "degree", title: "Degree Certificate", issuer: "University of Cape Town", date: "Verified 12 Jan 2026", redact: false },
    { id: "sace", title: "SACE Registration", issuer: "SA Council for Educators", date: "Verified 15 Jan 2026", redact: true },
    { id: "police", title: "Police Clearance", issuer: "SAPS / MIE Screening", date: "Verified 18 Jan 2026", redact: true },
    { id: "transcript", title: "Academic Transcript", issuer: "University of Cape Town", date: "Verified 12 Jan 2026", redact: false },
    { id: "reference", title: "Reference Letter", issuer: "Head of Dept · UCT Physics", date: "Verified 20 Jan 2026", redact: false },
  ];

  function renderDocs() {
    const grid = $("#doc-grid");
    if (!grid) return;
    grid.innerHTML = DOCS.map((d) => `
      <button class="doc-card" data-doc="${d.id}">
        <span class="doc-card__thumb"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M20 6 9 17l-5-5"/></svg></span>
        <span>
          <span class="doc-card__t">${d.title}</span>
          <span class="doc-card__s"><i data-lucide="badge-check"></i> ${d.date}</span>
        </span>
        <span class="doc-card__lock"><i data-lucide="eye"></i></span>
      </button>`).join("");
  }

  /* ---------- Modal ---------- */
  function initModal() {
    const modal = $("#docmodal");
    const open = (doc) => {
      $("#docmodal-title").textContent = doc.title;
      $("#docmodal-doctitle").textContent = doc.title;
      $("#docmodal-issuer").textContent = doc.issuer + " · " + doc.date;
      $("#docmodal-foot").textContent = doc.redact
        ? "Verified by NextGen Tutors · Read-only. ID / personal numbers are redacted for privacy."
        : "Verified by NextGen Tutors · Read-only copy for parent transparency.";
      modal.classList.add("is-open");
      if (window.NGT_LENIS) window.NGT_LENIS.stop();
      if (window.lucide) lucide.createIcons();
      if (window.gsap && !reduceMotion) gsap.fromTo(".docmodal__dialog", { y: 24, opacity: 0, scale: 0.97 }, { y: 0, opacity: 1, scale: 1, duration: 0.4, ease: "power3.out" });
    };
    const close = () => { modal.classList.remove("is-open"); if (window.NGT_LENIS) window.NGT_LENIS.start(); };
    document.addEventListener("click", (e) => {
      const card = e.target.closest(".doc-card");
      if (card) { const doc = DOCS.find((d) => d.id === card.dataset.doc); if (doc) open(doc); }
    });
    $("#docmodal-close").addEventListener("click", close);
    $("#docmodal-backdrop").addEventListener("click", close);
    window.addEventListener("keydown", (e) => { if (e.key === "Escape") close(); });
  }

  /* ---------- Calendar ---------- */
  const DOW = ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"];
  const MONTHS = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
  let viewY = 2026, viewM = 5; // June 2026
  // booked day-of-month for June 2026
  const BOOKED = { "2026-5": [3, 5, 9, 12, 18, 24], "2026-6": [2, 8, 15, 21] };
  const ALL_SLOTS = ["09:00", "11:00", "14:00", "16:00", "18:00"];

  function key() { return viewY + "-" + viewM; }
  function bookedDays() { return BOOKED[key()] || []; }

  function renderCal() {
    const grid = $("#cal-grid");
    $("#cal-month").textContent = MONTHS[viewM] + " " + viewY;
    const first = new Date(viewY, viewM, 1).getDay();
    const days = new Date(viewY, viewM + 1, 0).getDate();
    let html = DOW.map((d) => `<div class="cal__dow">${d.charAt(0)}</div>`).join("");
    for (let i = 0; i < first; i++) html += `<div class="cal__day is-empty"></div>`;
    const booked = bookedDays();
    for (let d = 1; d <= days; d++) {
      const dow = new Date(viewY, viewM, d).getDay();
      const weekend = dow === 0 || dow === 6;
      if (booked.includes(d)) {
        html += `<div class="cal__day is-booked" data-day="${d}">${d}<span class="cal__dot"></span></div>`;
      } else if (weekend) {
        html += `<div class="cal__day">${d}</div>`;
      } else {
        html += `<div class="cal__day has-slots" data-day="${d}">${d}<span class="cal__dot"></span></div>`;
      }
    }
    grid.innerHTML = html;
  }

  function showSlots(day, isBooked) {
    $$(".cal__day").forEach((c) => c.classList.remove("is-selected"));
    const cell = $(`.cal__day[data-day="${day}"]`);
    if (cell) cell.classList.add("is-selected");
    $("#slots-title").textContent = `${MONTHS[viewM]} ${day} · ${isBooked ? "Some slots booked" : "Available slots"}`;
    // deterministic booked slots for booked days
    const takenCount = isBooked ? 3 : (day % 3);
    $("#slot-chips").innerHTML = ALL_SLOTS.map((s, i) => {
      const taken = i < takenCount;
      return taken
        ? `<span class="slot-chip is-booked">${s}</span>`
        : `<a class="slot-chip" href="contact.html" data-internal>${s}</a>`;
    }).join("");
  }

  function initCal() {
    renderCal();
    $("#cal-grid").addEventListener("click", (e) => {
      const cell = e.target.closest(".cal__day[data-day]");
      if (!cell) return;
      showSlots(+cell.dataset.day, cell.classList.contains("is-booked"));
    });
    $("#cal-prev").addEventListener("click", () => { viewM--; if (viewM < 0) { viewM = 11; viewY--; } renderCal(); resetSlots(); });
    $("#cal-next").addEventListener("click", () => { viewM++; if (viewM > 11) { viewM = 0; viewY++; } renderCal(); resetSlots(); });
  }
  function resetSlots() { $("#slots-title").textContent = "Select an available day"; $("#slot-chips").innerHTML = ""; }

  /* ---------- Reveals ---------- */
  function initReveals() {
    if (reduceMotion || !window.gsap || !window.ScrollTrigger) {
      $$("[data-reveal]").forEach((el) => { el.style.opacity = 1; el.style.transform = "none"; });
      return;
    }
    gsap.registerPlugin(ScrollTrigger);
    $$("[data-reveal]").forEach((el) => gsap.to(el, { opacity: 1, y: 0, duration: 0.8, ease: "power3.out", scrollTrigger: { trigger: el, start: "top 90%", once: true } }));
  }

  function boot() {
    renderDocs();
    initModal();
    initCal();
    initReveals();
    if (window.lucide) lucide.createIcons();
  }
  if (document.readyState === "loading") document.addEventListener("DOMContentLoaded", boot);
  else boot();
})();
