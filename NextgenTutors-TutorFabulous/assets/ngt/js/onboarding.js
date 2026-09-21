/* NextGen Tutors — Onboarding Management */
(function () {
  "use strict";
  const reduceMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
  const $ = (s, c) => (c || document).querySelector(s);
  const $$ = (s, c) => Array.from((c || document).querySelectorAll(s));

  const USERS = [
    { name: "Karabo Molefe",  role: "Tutor",   dept: "tutor",   pct: 100, steps: "7/7", pts: 925, last: "3 Jun 2026", img: "https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?auto=format&fit=crop&q=80&w=100&h=100" },
    { name: "Lindiwe Nkosi",  role: "Tutor",   dept: "tutor",   pct: 100, steps: "7/7", pts: 900, last: "5 Jun 2026", img: "https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?auto=format&fit=crop&q=80&w=100&h=100" },
    { name: "Priya Govender", role: "Tutor",   dept: "tutor",   pct: 85,  steps: "6/7", pts: 725, last: "7 Jun 2026", img: "https://images.unsplash.com/photo-1580489944761-15a19d654956?auto=format&fit=crop&q=80&w=100&h=100" },
    { name: "Thabo Mokoena",  role: "Tutor",   dept: "tutor",   pct: 57,  steps: "4/7", pts: 400, last: "6 Jun 2026", img: "https://images.unsplash.com/photo-1531384441138-2736e62e0919?auto=format&fit=crop&q=80&w=100&h=100" },
    { name: "Lerato Dlamini", role: "Tutor",   dept: "tutor",   pct: 28,  steps: "2/7", pts: 150, last: "2 Jun 2026", img: "https://images.unsplash.com/photo-1544005313-94ddf0286df2?auto=format&fit=crop&q=80&w=100&h=100" },
    { name: "Naledi Admin",   role: "Staff",   dept: "staff",   pct: 100, steps: "7/7", pts: 850, last: "1 Jun 2026", img: "https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&q=80&w=100&h=100" },
    { name: "Brian Support",  role: "Support", dept: "support", pct: 71,  steps: "5/7", pts: 575, last: "8 Jun 2026", img: "https://images.unsplash.com/photo-1539571696357-5a69c17a67c6?auto=format&fit=crop&q=80&w=100&h=100" },
    { name: "Sipho Ndlovu",   role: "Tutor",   dept: "tutor",   pct: 14,  steps: "1/7", pts: 50,  last: "9 Jun 2026", img: "https://images.unsplash.com/photo-1500648767791-00dcc994a43e?auto=format&fit=crop&q=80&w=100&h=100" },
  ];

  const NOTIFS = [
    { time: "08 Jun · 09:14", msg: "Lerato Dlamini completed Step 2: SA ID Upload", type: "inapp", status: "Delivered" },
    { time: "08 Jun · 09:15", msg: "Welcome email sent to Sipho Ndlovu — Step 1 reminder", type: "email", status: "Delivered" },
    { time: "07 Jun · 14:02", msg: "Overdue alert: Sipho Ndlovu — 7 days inactive", type: "inapp", status: "Delivered" },
    { time: "07 Jun · 14:02", msg: "Overdue SMS sent: Sipho Ndlovu +27811234567", type: "sms", status: "Delivered" },
    { time: "07 Jun · 12:30", msg: "Priya Govender earned badge 🎓 '5 Steps Done'", type: "inapp", status: "Delivered" },
    { time: "06 Jun · 10:05", msg: "Thabo Mokoena completed Subject Competency — 200 pts awarded", type: "inapp", status: "Delivered" },
    { time: "05 Jun · 08:00", msg: "Monthly onboarding digest sent to Admin Team", type: "email", status: "Delivered" },
    { time: "03 Jun · 16:44", msg: "Karabo Molefe reached 100% — Certified Tutor badge awarded 🏆", type: "inapp", status: "Delivered" },
  ];

  let activeDept = "all";

  function pctClass(p) { return p < 30 ? "critical" : p < 60 ? "low" : ""; }

  function renderTable() {
    const tbody = $("#onb-tbody");
    if (!tbody) return;
    const list = activeDept === "all" ? USERS : USERS.filter((u) => u.dept === activeDept);
    tbody.innerHTML = list.map((u) => `
      <tr data-dept="${u.dept}">
        <td><div class="prog-user"><img src="${u.img}" alt="${u.name}" referrerpolicy="no-referrer" /><div><div class="prog-user__n">${u.name}</div><div class="prog-user__r">${u.role}</div></div></div></td>
        <td><span class="dept-tag dept-tag--${u.dept}">${u.dept}</span></td>
        <td>
          <div style="display:flex;align-items:center;gap:10px">
            <div class="prog-inline" style="width:80px"><div class="prog-inline__fill ${pctClass(u.pct)}" style="width:${u.pct}%"></div></div>
            <span style="font-family:var(--font-serif);font-weight:900;font-style:italic;font-size:15px;color:var(--navy)">${u.pct}%</span>
          </div>
        </td>
        <td style="font-weight:800;color:var(--slate-600)">${u.steps}</td>
        <td><span style="font-family:var(--font-serif);font-weight:900;font-style:italic;color:var(--navy)">${u.pts}</span></td>
        <td style="color:var(--slate-400);font-size:12px;font-weight:700">${u.last}</td>
        <td>
          <button onclick="window.ngtSendNotif('${u.name}')" style="font-size:10px;font-weight:900;text-transform:uppercase;padding:6px 12px;border-radius:var(--r-sm);background:var(--navy);color:var(--lime);letter-spacing:.04em" title="Send reminder">Remind</button>
        </td>
        <td><a href="tutor-profile.html" data-internal style="font-size:11px;font-weight:800;color:var(--blue);text-transform:uppercase;letter-spacing:.04em">View Profile →</a></td>
      </tr>`).join("");
    animateProgressBars();
  }

  function animateProgressBars() {
    if (reduceMotion || !window.gsap) return;
    $$(".prog-inline__fill").forEach((fill) => {
      const target = fill.style.width;
      fill.style.width = "0%";
      setTimeout(() => { fill.style.width = target; }, 200);
    });
  }

  function renderDeptChart() {
    const chart = $("#dept-chart");
    if (!chart) return;
    const depts = [
      { name: "Tutors",  pct: 65, color: "var(--lime-deep)" },
      { name: "Staff",   pct: 100, color: "var(--navy)" },
      { name: "Support", pct: 71, color: "var(--blue)" },
    ];
    chart.innerHTML = depts.map((d) => `
      <div>
        <div style="display:flex;justify-content:space-between;font-size:12px;font-weight:800;text-transform:uppercase;color:var(--slate-600);margin-bottom:6px">
          <span>${d.name}</span><span style="color:var(--navy);font-family:var(--font-serif);font-style:italic">${d.pct}%</span>
        </div>
        <div style="height:10px;background:var(--slate-100);border-radius:5px;overflow:hidden">
          <div style="height:100%;width:${d.pct}%;background:${d.color};border-radius:5px;transition:width 1s var(--ease-expo)"></div>
        </div>
      </div>`).join("");
  }

  function renderTopPerformers() {
    const el = $("#top-performers");
    if (!el) return;
    const sorted = [...USERS].sort((a, b) => b.pts - a.pts).slice(0, 4);
    el.innerHTML = sorted.map((u, i) => `
      <div style="display:flex;align-items:center;gap:12px;padding:10px 0;border-bottom:1px solid var(--slate-100)">
        <div style="font-family:var(--font-serif);font-weight:900;font-style:italic;font-size:18px;color:${i===0?"var(--amber)":"var(--slate-300)"};width:26px">${i===0?"🥇":i===1?"🥈":i===2?"🥉":i+1}</div>
        <img src="${u.img}" alt="${u.name}" referrerpolicy="no-referrer" style="width:36px;height:36px;border-radius:50%;object-fit:cover" />
        <div style="flex:1"><div style="font-weight:900;font-size:13px;color:var(--navy)">${u.name}</div><div style="font-size:10.5px;font-weight:700;text-transform:uppercase;color:var(--slate-400)">${u.dept}</div></div>
        <div style="font-family:var(--font-serif);font-weight:900;font-style:italic;font-size:16px;color:var(--navy)">${u.pts} pts</div>
      </div>`).join("");
  }

  function renderNotifHistory() {
    const el = $("#notif-history");
    if (!el) return;
    el.innerHTML = `
      <div style="display:grid;grid-template-columns:140px 1fr 90px 80px;gap:12px;padding:10px 0 8px;border-bottom:2px solid var(--slate-100)">
        <span style="font-size:10.5px;font-weight:900;text-transform:uppercase;color:var(--slate-400);letter-spacing:.06em">Timestamp</span>
        <span style="font-size:10.5px;font-weight:900;text-transform:uppercase;color:var(--slate-400);letter-spacing:.06em">Event</span>
        <span style="font-size:10.5px;font-weight:900;text-transform:uppercase;color:var(--slate-400);letter-spacing:.06em">Channel</span>
        <span style="font-size:10.5px;font-weight:900;text-transform:uppercase;color:var(--slate-400);letter-spacing:.06em">Status</span>
      </div>` +
      NOTIFS.map((n) => `
        <div class="notif-h-row">
          <span class="notif-h-row__time">${n.time}</span>
          <span class="notif-h-row__msg">${n.msg}</span>
          <span class="notif-h-row__type type-${n.type}">${n.type === "inapp" ? "In-App" : n.type.toUpperCase()}</span>
          <span class="pay-pill pay-pill--paid" style="font-size:9px">${n.status}</span>
        </div>`).join("");
  }

  function initFilters() {
    $$(".onb-filter").forEach((btn) => btn.addEventListener("click", () => {
      $$(".onb-filter").forEach((b) => b.classList.remove("is-active"));
      btn.classList.add("is-active");
      activeDept = btn.dataset.dept;
      renderTable();
    }));
  }

  function initStepToggles() {
    $$(".step-toggle").forEach((t) => t.addEventListener("click", () => t.classList.toggle("off")));
  }

  function initCounters() {
    if (!window.ScrollTrigger) { $$(".counter").forEach((el) => (el.textContent = el.dataset.target)); return; }
    $$(".counter").forEach((el) => {
      ScrollTrigger.create({ trigger: el, start: "top 96%", once: true, onEnter: () => {
        const target = +el.dataset.target;
        if (reduceMotion) { el.textContent = target; return; }
        const o = { v: 0 };
        gsap.to(o, { v: target, duration: 1.4, ease: "power2.out", onUpdate: () => (el.textContent = Math.round(o.v)) });
      } });
    });
  }

  window.ngtSendNotif = function (name) {
    const row = $(`tr[data-dept]`);
    alert(`✓ In-app reminder sent to ${name}. Email/SMS queued.`);
  };

  function boot() {
    if (window.gsap && window.ScrollTrigger) gsap.registerPlugin(ScrollTrigger);
    renderTable();
    renderDeptChart();
    renderTopPerformers();
    renderNotifHistory();
    initFilters();
    initStepToggles();
    initCounters();
    if (window.lucide) lucide.createIcons();
  }
  if (document.readyState === "loading") document.addEventListener("DOMContentLoaded", boot);
  else boot();
})();
