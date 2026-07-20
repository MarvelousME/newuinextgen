/**
 * Dashboard REST client — KPIs, sessions, Chart.js analytics (ngc/v1).
 */
(function () {
  "use strict";

  var cfg = window.biDashboard || {};
  var root = (cfg.restRoot || "/wp-json/").replace(/\/?$/, "/");
  var ns = cfg.namespace || "ngc/v1";
  var chartInstances = [];

  function esc(s) {
    var d = document.createElement("div");
    d.textContent = s == null ? "" : String(s);
    return d.innerHTML;
  }

  function zar(n) {
    var v = Number(n) || 0;
    return v.toLocaleString("en-ZA", { minimumFractionDigits: 0, maximumFractionDigits: 0 });
  }

  function fmtDate(str) {
    if (!str) return "";
    var d = new Date(String(str).replace(" ", "T"));
    if (Number.isNaN(d.getTime())) return str;
    return d.toLocaleDateString("en-ZA", { weekday: "short", day: "numeric", month: "short" });
  }

  function fetchDashboard() {
    var headers = { Accept: "application/json" };
    if (cfg.nonce) headers["X-WP-Nonce"] = cfg.nonce;
    return fetch(root + ns + cfg.path, { credentials: "same-origin", headers: headers }).then(function (res) {
      return res.json().then(function (data) {
        if (!res.ok) throw new Error(data.error || data.message || "Request failed");
        if (data && data.success && data.data) {
          return { payload: data.data, meta: data.meta || {} };
        }
        return { payload: data || {}, meta: {} };
      });
    });
  }

  function kpiCard(label, value, hint) {
    return (
      '<div class="bi-dash-kpi ngt-card">' +
      '<div class="bi-dash-kpi__label">' + esc(label) + "</div>" +
      '<div class="bi-dash-kpi__value">' + esc(value) + "</div>" +
      (hint ? '<div class="bi-dash-kpi__hint">' + esc(hint) + "</div>" : "") +
      "</div>"
    );
  }

  function sessionRow(s) {
    return (
      '<div class="bi-dash-session">' +
      (s.peerImage ? '<img src="' + esc(s.peerImage) + '" alt="" class="bi-dash-session__img" loading="lazy" />' : "") +
      '<div class="bi-dash-session__body"><div class="bi-dash-session__title">' +
      esc(s.peerName) + " · " + esc(s.subject) +
      '</div><div class="bi-dash-session__meta">' + esc(fmtDate(s.createdAt)) + "</div></div>" +
      '<span class="bi-dash-session__status">' + esc(s.statusLabel || s.attendance || "") + "</span></div>"
    );
  }

  function sourceBadge(meta) {
    var src = (meta && meta.source) || "unknown";
    return '<span class="bi-dashboard-rest__source" data-source="' + esc(src) + '">' + esc("Source: " + src) + "</span>";
  }

  function chartsHtml(charts) {
    if (!charts || !Object.keys(charts).length) return "";
    var html = '<div class="bi-dash-charts">';
    Object.keys(charts).forEach(function (key, i) {
      html += '<div class="bi-dash-chart ngt-card"><canvas id="bi-dash-chart-' + esc(key) + '" data-chart-key="' + esc(key) + '" height="180"></canvas></div>';
    });
    html += "</div>";
    return html;
  }

  function destroyCharts() {
    chartInstances.forEach(function (c) {
      try { c.destroy(); } catch (e) {}
    });
    chartInstances = [];
  }

  function paintCharts(charts) {
    if (!window.Chart || !charts) return;
    destroyCharts();
    Object.keys(charts).forEach(function (key) {
      var spec = charts[key];
      var canvas = document.getElementById("bi-dash-chart-" + key);
      if (!canvas || !spec) return;
      var chart = new Chart(canvas.getContext("2d"), {
        type: spec.type === "line" ? "line" : "bar",
        data: {
          labels: spec.labels || [],
          datasets: [
            {
              label: spec.label || key,
              data: spec.data || [],
              backgroundColor: spec.type === "line" ? "rgba(19,68,119,0.15)" : "rgba(19,68,119,0.75)",
              borderColor: "#134477",
              borderWidth: 2,
              fill: spec.type === "line",
              tension: 0.35,
            },
          ],
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: { legend: { display: true, position: "bottom" } },
          scales: { y: { beginAtZero: true } },
        },
      });
      chartInstances.push(chart);
    });
  }

  function exportCsv(data, type) {
    var rows = [["metric", "value"]];
    var kpis = data.kpis || {};
    Object.keys(kpis).forEach(function (k) {
      rows.push([k, String(kpis[k])]);
    });
    var csv = rows.map(function (r) { return r.map(function (c) { return '"' + String(c).replace(/"/g, '""') + '"'; }).join(","); }).join("\n");
    var blob = new Blob([csv], { type: "text/csv;charset=utf-8" });
    var a = document.createElement("a");
    a.href = URL.createObjectURL(blob);
    a.download = "dashboard-" + type + ".csv";
    a.click();
  }

  function toolbar(type) {
    return (
      '<div class="bi-dashboard-rest__toolbar">' +
      '<button type="button" class="ngt-btn ngt-btn--outline bi-dash-refresh" data-action="refresh">' + esc("Refresh") + "</button>" +
      '<button type="button" class="ngt-btn ngt-btn--outline bi-dash-export" data-action="export">' + esc("Export CSV") + "</button>" +
      "</div>"
    );
  }

  function renderStudentLike(data, isParent, meta) {
    var user = data.user || {};
    var kpis = data.kpis || {};
    var i18n = cfg.i18n || {};
    var html = '<div class="bi-dashboard-rest__inner">';
    html += toolbar(isParent ? "parent" : "student");
    if (isParent) html += '<p class="bi-dashboard-rest__note">' + esc("Family learning overview.") + "</p>";
    html += '<h2 class="bi-dashboard-rest__heading">' + esc("Welcome back, " + (user.displayName || "Learner")) + "</h2>" + sourceBadge(meta);
    html += '<div class="bi-dash-kpi-grid">';
    html += kpiCard("Sessions completed", String(kpis.sessionsCompleted ?? 0));
    html += kpiCard("Avg. rating given", kpis.avgRatingGiven != null ? String(kpis.avgRatingGiven) : "—");
    html += kpiCard("Account balance", "R" + zar(kpis.accountBalance));
    html += kpiCard("Achievements", String(kpis.achievementCount ?? 0));
    html += "</div>";
    html += chartsHtml(data.charts);
    if (data.nextSession) html += "<h3>Next session</h3>" + sessionRow(data.nextSession);
    var recent = data.recentSessions || [];
    html += "<h3>" + esc(i18n.sessions || "Recent sessions") + "</h3>";
    html += recent.length ? recent.map(sessionRow).join("") : '<p class="bi-dashboard-rest__empty">' + esc(i18n.empty) + "</p>";
    html += "</div>";
    return html;
  }

  function renderTutor(data, meta) {
    var user = data.user || {};
    var kpis = data.kpis || {};
    var html = '<div class="bi-dashboard-rest__inner">' + toolbar("tutor");
    html += '<h2 class="bi-dashboard-rest__heading">' + esc(user.displayName || "Tutor") + "</h2>" + sourceBadge(meta);
    html += '<div class="bi-dash-kpi-grid">';
    html += kpiCard("This month", "R" + zar(kpis.monthEarnings));
    html += kpiCard("Sessions", String(kpis.sessionsMonth ?? 0));
    html += kpiCard("Rating", kpis.averageRating ? String(kpis.averageRating) : "—");
    html += kpiCard("Pending payout", "R" + zar(kpis.pendingPayout));
    html += "</div>";
    html += chartsHtml(data.charts);
    html += "</div>";
    return html;
  }

  function renderAdmin(data, meta) {
    var kpis = data.kpis || {};
    var html = '<div class="bi-dashboard-rest__inner">' + toolbar("admin") + sourceBadge(meta);
    html += '<div class="bi-dash-kpi-grid">';
    html += kpiCard("Revenue", "R" + zar(kpis.revenue));
    html += kpiCard("Sessions", String(kpis.sessions ?? 0));
    html += kpiCard("Tutors", String(kpis.tutors ?? 0));
    html += kpiCard("Pending apps", String(kpis.pending ?? 0));
    html += "</div>";
    html += chartsHtml(data.charts);
    html += "</div>";
    return html;
  }

  function render(type, data, meta) {
    if (type === "admin") return renderAdmin(data, meta);
    if (type === "tutor") return renderTutor(data, meta);
    if (type === "parent") return renderStudentLike(data, true, meta);
    return renderStudentLike(data, false, meta);
  }

  function bindToolbar(el, type, getData) {
    el.querySelectorAll("[data-action]").forEach(function (btn) {
      btn.addEventListener("click", function () {
        var action = btn.getAttribute("data-action");
        if (action === "refresh") initMount(el);
        if (action === "export") exportCsv(getData(), type);
      });
    });
  }

  function initMount(el) {
    var type = el.getAttribute("data-dashboard") || cfg.type || "student";
    var i18n = cfg.i18n || {};
    var lastData = {};
    el.setAttribute("aria-busy", "true");
    el.innerHTML = '<p class="bi-dashboard-rest__loading">' + esc(i18n.loading) + "</p>";
    fetchDashboard()
      .then(function (result) {
        var data = result.payload || {};
        var meta = result.meta || {};
        lastData = data;
        el.innerHTML = render(type, data, meta);
        paintCharts(data.charts);
        bindToolbar(el, type, function () { return lastData; });
        el.setAttribute("aria-busy", "false");
      })
      .catch(function () {
        el.innerHTML = '<div class="bi-dashboard-rest__error" role="alert"><p>' + esc(i18n.error) + "</p></div>";
        el.setAttribute("aria-busy", "false");
      });
  }

  document.addEventListener("DOMContentLoaded", function () {
    document.querySelectorAll(".bi-dashboard-rest[data-dashboard]").forEach(initMount);
  });
})();
