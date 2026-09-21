/* NextGen Tutors — Reports & Analytics */
(function () {
  "use strict";
  const reduceMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
  const $ = (s, c) => (c || document).querySelector(s);
  const $$ = (s, c) => Array.from((c || document).querySelectorAll(s));

  const REVENUE = [
    { lbl: "Jan", val: 38200, sessions: 124, date: new Date("2026-01-01") },
    { lbl: "Feb", val: 42100, sessions: 138, date: new Date("2026-02-01") },
    { lbl: "Mar", val: 54800, sessions: 171, date: new Date("2026-03-01") },
    { lbl: "Apr", val: 48600, sessions: 156, date: new Date("2026-04-01") },
    { lbl: "May", val: 57900, sessions: 189, date: new Date("2026-05-01") },
    { lbl: "Jun", val: 68450, sessions: 214, hi: true, date: new Date("2026-06-01") },
  ];
  const SUBJECTS = [
    { lbl: "Maths", val: 68 }, { lbl: "Physics", val: 42 }, { lbl: "English", val: 31 },
    { lbl: "Acct", val: 28 }, { lbl: "Life Sci", val: 22 }, { lbl: "Coding", val: 18, hi: true },
  ];
  const ENGAGEMENT = [
    { lbl: "Sessions Completed", pct: 94 }, { lbl: "Reviews Submitted", pct: 78 },
    { lbl: "Second Booking", pct: 62 }, { lbl: "Bundle Purchased", pct: 38 }, { lbl: "Referral Made", pct: 21 },
  ];
  const TOP_SUBJECTS = [
    { name: "Mathematics", count: 68, pct: 100 }, { name: "Physical Sciences", count: 42, pct: 62 },
    { name: "English HL", count: 31, pct: 46 }, { name: "Accounting", count: 28, pct: 41 },
    { name: "Life Sciences", count: 22, pct: 32 },
  ];

  function buildBarChart(containerId, data, labelFn) {
    const container = $(containerId);
    if (!container) return;
    const max = Math.max(...data.map((d) => d.val));
    container.innerHTML = data.map((d) => {
      const pct = Math.round((d.val / max) * 100);
      return `<div class="rchart-bar${d.hi ? " highlight" : ""}">
        <div class="rchart-bar__val">${labelFn ? labelFn(d.val) : d.val}</div>
        <div class="rchart-bar__fill" style="height:${reduceMotion ? pct : 0}%;max-height:${pct}%"></div>
        <div class="rchart-bar__lbl">${d.lbl}</div>
      </div>`;
    }).join("");
    if (!reduceMotion && window.gsap) {
      setTimeout(() => data.forEach((d, i) => {
        const fill = container.querySelectorAll(".rchart-bar__fill")[i];
        const pct = Math.round((d.val / max) * 100);
        gsap.to(fill, { height: pct + "%", duration: 1.2, ease: "power3.out", delay: i * 0.08 });
      }), 300);
    }
  }

  function buildEngagementBars() {
    const el = $("#engagement-bars");
    if (!el) return;
    el.innerHTML = ENGAGEMENT.map((e) => `
      <div>
        <div style="display:flex;justify-content:space-between;font-size:12px;font-weight:800;color:var(--slate-600);margin-bottom:5px">
          <span>${e.lbl}</span><span style="font-family:var(--font-serif);font-style:italic;color:var(--navy)">${e.pct}%</span>
        </div>
        <div style="height:8px;background:var(--slate-100);border-radius:4px;overflow:hidden">
          <div style="height:100%;width:${e.pct}%;background:linear-gradient(90deg,var(--navy),var(--blue));border-radius:4px;transition:width 1s var(--ease-expo)"></div>
        </div>
      </div>`).join("");
  }

  function buildDonut(container, pct, label, color) {
    const r = 46, cx = 60, cy = 60, circ = 2 * Math.PI * r;
    const dash = (pct / 100) * circ;
    container.innerHTML = `
      <div style="display:flex;flex-direction:column;align-items:center;gap:8px">
        <div class="report-donut">
          <svg width="120" height="120" viewBox="0 0 120 120">
            <circle cx="${cx}" cy="${cy}" r="${r}" fill="none" stroke="var(--slate-100)" stroke-width="12"/>
            <circle cx="${cx}" cy="${cy}" r="${r}" fill="none" stroke="${color}" stroke-width="12" stroke-dasharray="${dash} ${circ}" stroke-linecap="round"/>
          </svg>
          <div class="report-donut__center">
            <div class="report-donut__val">${pct}%</div>
          </div>
        </div>
        <div style="font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.04em;color:var(--slate-500)">${label}</div>
      </div>`;
  }

  function buildFormatDonuts() {
    const el = $("#format-donuts");
    if (!el) return;
    const a = document.createElement("div"); buildDonut(a, 58, "Online", "var(--navy)");
    const b = document.createElement("div"); buildDonut(b, 34, "In-Person", "var(--lime-deep)");
    const c = document.createElement("div"); buildDonut(c, 8, "Hybrid", "var(--amber)");
    el.appendChild(a); el.appendChild(b); el.appendChild(c);
  }

  function buildTopSubjects() {
    const el = $("#top-subjects");
    if (!el) return;
    el.innerHTML = TOP_SUBJECTS.map((s) => `
      <div style="display:flex;align-items:center;gap:12px;padding:10px 0;border-bottom:1px solid var(--slate-100)">
        <div style="flex:1;font-size:13px;font-weight:800;color:var(--navy)">${s.name}</div>
        <div style="display:flex;align-items:center;gap:10px;width:180px">
          <div style="flex:1;height:6px;background:var(--slate-100);border-radius:3px;overflow:hidden"><div style="height:100%;width:${s.pct}%;background:var(--navy);border-radius:3px;transition:width 1s var(--ease-expo)"></div></div>
          <span style="font-size:11px;font-weight:900;color:var(--slate-600);width:24px;text-align:right">${s.count}</span>
        </div>
      </div>`).join("");
  }

  function initCounters() {
    if (!window.ScrollTrigger) { $$(".counter").forEach((el) => (el.textContent = Number(el.dataset.target).toLocaleString("en-ZA"))); return; }
    $$(".counter").forEach((el) => {
      ScrollTrigger.create({ trigger: el, start: "top 96%", once: true, onEnter: () => {
        const target = +el.dataset.target;
        if (reduceMotion) { el.textContent = target.toLocaleString("en-ZA"); return; }
        const o = { v: 0 };
        gsap.to(o, { v: target, duration: 1.6, ease: "power2.out", onUpdate: () => (el.textContent = Math.round(o.v).toLocaleString("en-ZA")) });
      } });
    });
  }

  function initReveals() {
    if (reduceMotion || !window.gsap || !window.ScrollTrigger) return;
    gsap.registerPlugin(ScrollTrigger);
    $$(".admin-kpi").forEach((el, i) => gsap.from(el, { opacity: 0, y: 24, duration: 0.7, ease: "power3.out", delay: i * 0.08 }));
    $$(".panel").forEach((el) => gsap.from(el, { opacity: 0, y: 18, duration: 0.8, ease: "power3.out", scrollTrigger: { trigger: el, start: "top 90%", once: true } }));
  }

  /* ---- Date-range filtering ---- */
  function animateCounter(el, target) {
    if (!el) return;
    if (window.gsap) {
      const o = { v: parseFloat(el.textContent.replace(/\D/g, "")) || 0 };
      gsap.to(o, { v: target, duration: 1.2, ease: "power2.out",
        onUpdate: () => { el.textContent = Math.round(o.v).toLocaleString("en-ZA"); } });
    } else {
      el.textContent = target.toLocaleString("en-ZA");
    }
  }

  function applyDateRange() {
    const fromVal = document.getElementById("date-from").value;
    const toVal   = document.getElementById("date-to").value;
    if (!fromVal || !toVal) return;
    const from = new Date(fromVal);
    const to   = new Date(toVal); to.setDate(to.getDate() + 1);

    const filtered = REVENUE.filter(d => d.date >= from && d.date <= to);
    if (!filtered.length) return;

    buildBarChart("#revenue-chart", filtered, v => "R" + (v / 1000).toFixed(0) + "k");

    const totalRev  = filtered.reduce((s, d) => s + d.val, 0);
    const totalSess = filtered.reduce((s, d) => s + d.sessions, 0);

    animateCounter(document.getElementById("kpi-revenue-val"),  totalRev);
    animateCounter(document.getElementById("kpi-sessions-val"), totalSess);

    // Feedback pulse on KPI cards
    document.querySelectorAll(".admin-kpi").forEach(el => {
      el.style.transition = "box-shadow .3s";
      el.style.boxShadow  = "0 0 0 3px var(--lime)";
      setTimeout(() => { el.style.boxShadow = ""; }, 700);
    });
  }

  function boot() {
    if (window.gsap && window.ScrollTrigger) gsap.registerPlugin(ScrollTrigger);
    buildBarChart("#revenue-chart", REVENUE, (v) => "R" + (v / 1000).toFixed(0) + "k");
    buildBarChart("#subject-chart", SUBJECTS, (v) => v + " sess.");
    buildEngagementBars();
    buildFormatDonuts();
    buildTopSubjects();
    initCounters();
    initReveals();
    if (window.lucide) lucide.createIcons();

    const applyBtn = document.getElementById("apply-range");
    if (applyBtn) applyBtn.addEventListener("click", applyDateRange);
  }
  if (document.readyState === "loading") document.addEventListener("DOMContentLoaded", boot);
  else boot();
})();
