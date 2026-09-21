/**
 * NEXTGEN Beyond-Infinity — constellation, magnetic CTAs, marketplace drawer.
 */
(function () {
  "use strict";

  var cfg = window.nbiInfinity || {};
  var reduced = window.matchMedia("(prefers-reduced-motion: reduce)").matches;

  /* ── Subject constellation (2D canvas) ───────────────────── */
  function initConstellation(canvas) {
    if (!canvas || reduced) return;
    var ctx = canvas.getContext("2d");
    if (!ctx) return;

    var nodes = [
      { label: "Physics", x: 0.22, y: 0.28, r: 6 },
      { label: "Calculus", x: 0.42, y: 0.18, r: 5 },
      { label: "Literature", x: 0.68, y: 0.32, r: 5 },
      { label: "AI", x: 0.55, y: 0.52, r: 7 },
      { label: "Chemistry", x: 0.3, y: 0.58, r: 4 },
      { label: "History", x: 0.78, y: 0.62, r: 4 },
      { label: "Biology", x: 0.48, y: 0.72, r: 5 },
    ];
    var t0 = performance.now();

    function resize() {
      var rect = canvas.parentElement ? canvas.parentElement.getBoundingClientRect() : canvas.getBoundingClientRect();
      var dpr = Math.min(window.devicePixelRatio || 1, 2);
      canvas.width = Math.max(1, Math.floor(rect.width * dpr));
      canvas.height = Math.max(1, Math.floor(rect.height * dpr));
      canvas.style.width = rect.width + "px";
      canvas.style.height = rect.height + "px";
      ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
    }

    function draw(now) {
      var w = canvas.clientWidth;
      var h = canvas.clientHeight;
      if (!w || !h) return;
      ctx.clearRect(0, 0, w, h);
      var pulse = 0.5 + 0.5 * Math.sin((now - t0) * 0.0012);

      for (var i = 0; i < nodes.length; i++) {
        for (var j = i + 1; j < nodes.length; j++) {
          if (Math.abs(i - j) > 2 && (i + j) % 3 !== 0) continue;
          ctx.strokeStyle = "rgba(56, 189, 248, " + (0.12 + pulse * 0.1) + ")";
          ctx.lineWidth = 1;
          ctx.beginPath();
          ctx.moveTo(nodes[i].x * w, nodes[i].y * h);
          ctx.lineTo(nodes[j].x * w, nodes[j].y * h);
          ctx.stroke();
        }
      }

      nodes.forEach(function (n, idx) {
        var x = n.x * w;
        var y = n.y * h;
        var glow = n.r + pulse * 2;
        var grd = ctx.createRadialGradient(x, y, 0, x, y, glow * 3);
        grd.addColorStop(0, "rgba(167, 139, 250, 0.9)");
        grd.addColorStop(0.4, "rgba(56, 189, 248, 0.35)");
        grd.addColorStop(1, "transparent");
        ctx.fillStyle = grd;
        ctx.beginPath();
        ctx.arc(x, y, glow * 3, 0, Math.PI * 2);
        ctx.fill();
        ctx.fillStyle = "rgba(255,255,255,0.92)";
        ctx.beginPath();
        ctx.arc(x, y, n.r, 0, Math.PI * 2);
        ctx.fill();
        ctx.font = "600 10px system-ui, sans-serif";
        ctx.fillStyle = "rgba(226, 232, 240, 0.85)";
        ctx.fillText(n.label, x + 10, y + 4);
      });

      requestAnimationFrame(draw);
    }

    resize();
    window.addEventListener("resize", resize);
    requestAnimationFrame(draw);
  }

  /* ── Magnetic buttons ──────────────────────────────────────── */
  function initMagnetic(el) {
    el.addEventListener("mousemove", function (e) {
      var rect = el.getBoundingClientRect();
      var x = ((e.clientX - rect.left) / rect.width - 0.5) * 12;
      var y = ((e.clientY - rect.top) / rect.height - 0.5) * 12;
      el.style.setProperty("--nbi-mag-x", x + "px");
      el.style.setProperty("--nbi-mag-y", y + "px");
      if (!reduced) el.style.transform = "translate(" + x * 0.15 + "px," + y * 0.15 + "px)";
    });
    el.addEventListener("mouseleave", function () {
      el.style.transform = "";
      el.style.setProperty("--nbi-mag-x", "0");
      el.style.setProperty("--nbi-mag-y", "0");
    });
  }

  /* ── Scroll progress ───────────────────────────────────────── */
  function initScrollProgress() {
    var bar = document.querySelector("[data-nbi-scroll-progress]");
    if (!bar) return;
    var fill = bar.querySelector(".nbi-scroll-progress__bar");
    if (!fill) return;
    function tick() {
      var doc = document.documentElement;
      var max = doc.scrollHeight - doc.clientHeight;
      var pct = max > 0 ? (window.scrollY / max) * 100 : 0;
      fill.style.width = pct + "%";
    }
    window.addEventListener("scroll", tick, { passive: true });
    tick();
  }

  /**
   * Escape text for HTML attribute values and element text injected via innerHTML.
   */
  function escAttr(s) {
    return String(s == null ? "" : s)
      .replace(/&/g, "&amp;")
      .replace(/"/g, "&quot;")
      .replace(/'/g, "&#39;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;");
  }

  function appendDrawerRow(container, label, value) {
    var p = document.createElement("p");
    var strong = document.createElement("strong");
    strong.textContent = label;
    p.appendChild(strong);
    p.appendChild(document.createTextNode(" " + value));
    container.appendChild(p);
  }

  /* ── Marketplace booking drawer ────────────────────────────── */
  function ensureDrawer() {
    var existing = document.getElementById("nbi-mkt-drawer");
    if (existing) return existing;
    var root = document.createElement("div");
    root.id = "nbi-mkt-drawer";
    root.className = "nbi-mkt-drawer";
    root.setAttribute("hidden", "");
    root.innerHTML =
      '<div class="nbi-mkt-drawer__backdrop" data-nbi-drawer-close tabindex="-1"></div>' +
      '<aside class="nbi-mkt-drawer__panel" role="dialog" aria-modal="true" aria-labelledby="nbi-mkt-drawer-title">' +
      '<button type="button" class="nbi-mkt-drawer__close" data-nbi-drawer-close aria-label="' +
      escAttr(cfg.i18n && cfg.i18n.close ? cfg.i18n.close : "Close") +
      '">&times;</button>' +
      '<h2 id="nbi-mkt-drawer-title">' +
      escAttr(cfg.i18n && cfg.i18n.bookSession ? cfg.i18n.bookSession : "Book Session") +
      "</h2>" +
      '<p class="nbi-mkt-drawer__lead" data-nbi-drawer-lead></p>' +
      '<div class="nbi-mkt-drawer__body" data-nbi-drawer-body></div>' +
      '<a class="nbi-btn nbi-btn--magnetic nbi-mkt-drawer__cta" data-nbi-drawer-cta href="#">' +
      escAttr(cfg.i18n && cfg.i18n.bookSession ? cfg.i18n.bookSession : "Book Session") +
      "</a></aside>";
    document.body.appendChild(root);
    root.querySelectorAll("[data-nbi-drawer-close]").forEach(function (btn) {
      btn.addEventListener("click", closeDrawer);
    });
    document.addEventListener("keydown", function (e) {
      if (e.key === "Escape" && root.classList.contains("is-open")) closeDrawer();
    });
    return root;
  }

  function openDrawer(tutor) {
    var drawer = ensureDrawer();
    var lead = drawer.querySelector("[data-nbi-drawer-lead]");
    var body = drawer.querySelector("[data-nbi-drawer-body]");
    var cta = drawer.querySelector("[data-nbi-drawer-cta]");
    if (lead) lead.textContent = tutor.name || "";
    if (body) {
      body.textContent = "";
      appendDrawerRow(body, "Rating:", Number(tutor.rating || 0).toFixed(1));
      if (tutor.subjects && tutor.subjects.length) {
        appendDrawerRow(body, "Subjects:", tutor.subjects.slice(0, 4).join(", "));
      }
      if (tutor.province) {
        appendDrawerRow(body, "Province:", tutor.province);
      }
    }
    if (cta) {
      cta.href = tutor.bookUrl || ((tutor.permalink || tutor.url || "") + "#book") || "#book";
    }
    drawer.removeAttribute("hidden");
    drawer.classList.add("is-open");
    var panel = drawer.querySelector(".nbi-mkt-drawer__panel");
    if (panel) panel.focus();
  }

  function closeDrawer() {
    var drawer = document.getElementById("nbi-mkt-drawer");
    if (!drawer) return;
    drawer.classList.remove("is-open");
    drawer.setAttribute("hidden", "");
  }

  window.nbiOpenBookingDrawer = openDrawer;

  /* ── Bento ring charts (lightweight) ───────────────────────── */
  function paintRing(canvas, pct) {
    if (!canvas) return;
    var ctx = canvas.getContext("2d");
    if (!ctx) return;
    var size = canvas.width;
    var cx = size / 2;
    var cy = size / 2;
    var r = size * 0.38;
    ctx.clearRect(0, 0, size, size);
    ctx.lineWidth = 10;
    ctx.strokeStyle = "rgba(148, 163, 184, 0.25)";
    ctx.beginPath();
    ctx.arc(cx, cy, r, 0, Math.PI * 2);
    ctx.stroke();
    var grad = ctx.createLinearGradient(0, 0, size, size);
    grad.addColorStop(0, "#6d28d9");
    grad.addColorStop(1, "#14b8a6");
    ctx.strokeStyle = grad;
    ctx.lineCap = "round";
    ctx.beginPath();
    ctx.arc(cx, cy, r, -Math.PI / 2, -Math.PI / 2 + (Math.PI * 2 * pct) / 100);
    ctx.stroke();
    ctx.fillStyle = "#0f172a";
    ctx.font = "bold 22px system-ui";
    ctx.textAlign = "center";
    ctx.textBaseline = "middle";
    ctx.fillText(Math.round(pct) + "%", cx, cy);
  }

  function hydrateBentoFromRest() {
    document.querySelectorAll("[data-nbi-bento]").forEach(function (shell) {
      var mount = shell.closest(".ngt-container, .bi-narrow") || document;
      var rest = mount.querySelector(".bi-dashboard-rest[data-dashboard]");
      if (!rest || rest.dataset.nbiHydrated) return;
      rest.dataset.nbiHydrated = "1";
      rest.addEventListener("nbi:dashboard-loaded", function (e) {
        var data = (e && e.detail) || {};
        var kpis = data.kpis || [];
        var wallet = shell.querySelector("[data-nbi-wallet-balance]");
        if (wallet && kpis[0]) wallet.textContent = kpis[0].value || "—";
        var tasks = shell.querySelector('[data-nbi-ring="tasks"] canvas');
        var sessions = shell.querySelector('[data-nbi-ring="sessions"] canvas');
        paintRing(tasks, kpis[1] ? parseFloat(String(kpis[1].value).replace(/[^\d.]/g, "")) || 0 : 62);
        paintRing(sessions, kpis[2] ? parseFloat(String(kpis[2].value).replace(/[^\d.]/g, "")) || 0 : 78);
      });
    });
  }

  function boot() {
    document.querySelectorAll("[data-nbi-constellation]").forEach(initConstellation);
    document.querySelectorAll(".nbi-btn--magnetic, .ngi-magnetic, [data-nbi-magnetic]").forEach(initMagnetic);

    if (!document.querySelector("[data-nbi-scroll-progress]")) {
      var track = document.createElement("div");
      track.className = "nbi-scroll-progress";
      track.setAttribute("data-nbi-scroll-progress", "");
      track.innerHTML = '<span class="nbi-scroll-progress__bar"></span>';
      document.body.appendChild(track);
    }
    initScrollProgress();
    hydrateBentoFromRest();
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", boot);
  } else {
    boot();
  }
})();
