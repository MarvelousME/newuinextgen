/**
 * Dashboard REST client — role-specific layouts, KPIs, quick actions,
 * sessions, Chart.js analytics (ngc/v1).
 */
(function () {
  "use strict";

  var cfg = window.biDashboard || {};
  var root = (cfg.restRoot || "/wp-json/").replace(/\/?$/, "/");
  var ns = cfg.namespace || "ngc/v1";
  var pages = cfg.pages || {};
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

  function launchSession(sessionId, bookingId) {
    var headers = {
      Accept: "application/json",
      "Content-Type": "application/json",
    };
    if (cfg.nonce) headers["X-WP-Nonce"] = cfg.nonce;
    var url =
      sessionId > 0
        ? root + ns + "/sessions/" + sessionId + "/launch"
        : root + ns + "/bookings/" + bookingId + "/join";
    return fetch(url, {
      method: "POST",
      credentials: "same-origin",
      headers: headers,
      body: "{}",
    }).then(function (res) {
      return res.json().then(function (data) {
        if (!res.ok) {
          var msg =
            (data && (data.message || data.error)) ||
            (data && data.data && data.data.message) ||
            "Join failed";
          var reason =
            (data && data.reason) ||
            (data && data.window && data.window.reason) ||
            (data && data.data && data.data.window && data.data.window.reason) ||
            "";
          var err = new Error(typeof msg === "string" ? msg : "Join failed");
          err.reason = reason;
          throw err;
        }
        var payload = data && data.data ? data.data : data;
        return payload || {};
      });
    });
  }

  function joinReasonCopy(reason, i18n) {
    var key = String(reason || "");
    if (key === "too_early") return i18n.joinTooEarly || "This lesson is not open to join yet.";
    if (key === "too_late") return i18n.joinTooLate || "The join window for this lesson has closed.";
    if (key === "payment_required") return i18n.joinPayment || "Payment is still required before you can join.";
    if (key === "session_closed") return i18n.joinClosed || "This lesson is no longer available.";
    if (key === "missing_session" || key === "not_ready" || key.indexOf("status_") === 0) {
      return i18n.joinNotReady || "This lesson is not ready to join yet.";
    }
    return "";
  }

  function joinButton(s, className, label) {
    if (!s || !s.canJoin) return "";
    var sid = Number(s.sessionId || s.session_id || 0);
    var bid = Number(s.bookingId || s.id || 0);
    var reason = s.joinReason || s.join_reason || "";
    return (
      '<button type="button" class="' +
      esc(className) +
      ' bi-dash-join-btn" data-session-id="' +
      esc(sid) +
      '" data-booking-id="' +
      esc(bid) +
      '" data-join-reason="' +
      esc(reason) +
      '">' +
      esc(label) +
      "</button>"
    );
  }

  function bindJoinButtons(container) {
    if (!container) return;
    var i18n = cfg.i18n || {};
    container.querySelectorAll(".bi-dash-join-btn").forEach(function (btn) {
      if (btn.dataset.bound === "1") return;
      btn.dataset.bound = "1";
      btn.addEventListener("click", function () {
        var sid = Number(btn.getAttribute("data-session-id") || 0);
        var bid = Number(btn.getAttribute("data-booking-id") || 0);
        var original = btn.textContent;
        btn.disabled = true;
        btn.setAttribute("aria-busy", "true");
        btn.textContent = i18n.joining || "Joining…";
        launchSession(sid, bid)
          .then(function (payload) {
            var url = payload.launch_url || payload.classroom_url || payload.player_url || payload.meeting_url || payload.join_url || "";
            if (!url) throw new Error("No launch URL returned");
            var note = btn.parentNode && btn.parentNode.querySelector(".bi-dash-join-error");
            if (note) note.remove();
            window.location.assign(url);
          })
          .catch(function (err) {
            var mapped = joinReasonCopy(err && err.reason, i18n) || joinReasonCopy(btn.getAttribute("data-join-reason"), i18n);
            var msg = mapped || (err && err.message) || i18n.joinFailed || "Unable to join lesson";
            var note = btn.parentNode && btn.parentNode.querySelector(".bi-dash-join-error");
            if (!note) {
              note = document.createElement("p");
              note.className = "bi-dash-join-error";
              note.setAttribute("role", "alert");
              btn.insertAdjacentElement("afterend", note);
            }
            note.textContent = msg;
            btn.disabled = false;
            btn.removeAttribute("aria-busy");
            btn.textContent = original;
          });
      });
    });
  }

  function sessionRow(s) {
    var join = joinButton(s, "bi-dash-session__join ngt-btn ngt-btn--sm ngt-btn--primary", "Join lesson");
    var alt = s.peerName ? s.peerName : "";
    return (
      '<div class="bi-dash-session">' +
      (s.peerImage ? '<img src="' + esc(s.peerImage) + '" alt="' + esc(alt) + '" class="bi-dash-session__img" loading="lazy" />' : "") +
      '<div class="bi-dash-session__body"><div class="bi-dash-session__title">' +
      esc(s.peerName) + " · " + esc(s.subject) +
      '</div><div class="bi-dash-session__meta">' + esc(fmtDate(s.createdAt)) + "</div></div>" +
      join +
      '<span class="bi-dash-session__status">' + esc(s.statusLabel || s.attendance || "") + "</span></div>"
    );
  }

  function sourceBadge(meta) {
    var src = (meta && meta.source) || "unknown";
    return '<span class="bi-dashboard-rest__source" data-source="' + esc(src) + '">' + esc("Source: " + src) + "</span>";
  }

  /**
   * Next-session hero card. Empty state keeps the slot visible with a booking CTA.
   */
  function nextSessionHero(s, type) {
    var i18n = cfg.i18n || {};
    if (!s) {
      var ctaUrl = "";
      var ctaLabel = "";
      if (type === "tutor") {
        ctaUrl = pages.becomeATutor || "";
        ctaLabel = i18n.updateProfile || "Update profile";
      } else if (type === "parent") {
        ctaUrl = pages.findATutor || "";
        ctaLabel = i18n.bookForChild || "Book for your child";
      } else {
        ctaUrl = pages.findATutor || "";
        ctaLabel = i18n.bookSession || "Book a session";
      }
      var cta = ctaUrl
        ? '<a href="' + esc(ctaUrl) + '" class="ngt-btn ngt-btn--sm ngt-btn--primary">' + esc(ctaLabel) + "</a>"
        : "";
      return (
        '<div class="bi-dash-hero bi-dash-hero--empty ngt-card" role="status">' +
        '<div class="bi-dash-hero__eyebrow">' + esc("Next session") + "</div>" +
        '<p class="bi-dashboard-rest__empty">' + esc(i18n.noUpcoming || "No upcoming lesson yet.") + "</p>" +
        cta +
        "</div>"
      );
    }
    var join = joinButton(s, "bi-dash-hero__join ngt-btn ngt-btn--primary", "Join audio + video lesson");
    var alt = s.peerName ? s.peerName : "";
    return (
      '<div class="bi-dash-hero ngt-card">' +
      '<div class="bi-dash-hero__eyebrow">' + esc("Next session") + "</div>" +
      '<div class="bi-dash-hero__row">' +
      (s.peerImage ? '<img src="' + esc(s.peerImage) + '" alt="' + esc(alt) + '" class="bi-dash-hero__img" loading="lazy" />' : "") +
      '<div class="bi-dash-hero__body">' +
      '<div class="bi-dash-hero__title">' + esc(s.peerName) + " · " + esc(s.subject) + "</div>" +
      '<div class="bi-dash-hero__meta">' + esc(fmtDate(s.createdAt)) + (s.statusLabel ? " · " + esc(s.statusLabel) : "") + "</div>" +
      join +
      "</div></div></div>"
    );
  }

  /**
   * Role-specific quick actions built from localized page URLs.
   */
  function quickActions(type) {
    var actions = [];
    if (type === "tutor") {
      if (pages.support || pages.contact) actions.push({ label: "Get support", url: pages.support || pages.contact });
    } else if (type === "admin") {
      if (pages.adminArea) actions.push({ label: "Open admin area", url: pages.adminArea, primary: true });
      if (pages.support || pages.contact) actions.push({ label: "Support inbox", url: pages.support || pages.contact });
    } else if (type === "parent") {
      if (pages.findATutor) actions.push({ label: "Book for your child", url: pages.findATutor, primary: true });
      if (pages.pricing) actions.push({ label: "View pricing", url: pages.pricing });
      if (pages.support || pages.contact) actions.push({ label: "Billing help", url: pages.support || pages.contact });
    } else {
      if (pages.findATutor) actions.push({ label: "Book a session", url: pages.findATutor, primary: true });
      if (pages.pricing) actions.push({ label: "View pricing", url: pages.pricing });
      if (pages.support || pages.contact) actions.push({ label: "Get help", url: pages.support || pages.contact });
    }
    if (!actions.length) return "";
    var html = '<nav class="bi-dash-actions" aria-label="' + esc("Quick actions") + '">';
    actions.forEach(function (a) {
      html +=
        '<a href="' + esc(a.url) + '" class="ngt-btn ngt-btn--sm ' + (a.primary ? "ngt-btn--primary" : "ngt-btn--outline") + '">' +
        esc(a.label) +
        "</a>";
    });
    html += "</nav>";
    return html;
  }

  /**
   * Tutor application status card (pending / approved / rejected).
   */
  function applicationCard(app) {
    if (!app || !app.status) return "";
    var i18n = cfg.i18n || {};
    var status = String(app.status).toLowerCase();
    var copy = {
      pending: "Your tutor application is being reviewed. We typically respond within 2 business days.",
      approved: "Your tutor application has been approved. Welcome aboard!",
      rejected: "Your tutor application was not approved this time.",
    };
    var html =
      '<div class="bi-dash-app-status ngt-card" data-status="' + esc(status) + '">' +
      '<div class="bi-dash-app-status__head">' +
      '<h3 class="bi-dash-app-status__title">' + esc("Application status") + "</h3>" +
      '<span class="ngt-badge ngt-badge--' + esc(status) + '">' + esc(status.charAt(0).toUpperCase() + status.slice(1)) + "</span>" +
      "</div>" +
      '<p class="bi-dash-app-status__copy">' + esc(copy[status] || i18n.applicationUpdate || "We'll update you when there's news.") + "</p>";
    if (status === "rejected" && app.reviewNotes) {
      html += '<p class="bi-dash-app-status__notes">' + esc(app.reviewNotes) + "</p>";
    }
    if (app.submittedAt) {
      html += '<p class="bi-dash-app-status__meta">' + esc("Submitted " + fmtDate(app.submittedAt)) + "</p>";
    }
    html += "</div>";
    return html;
  }

  function chartsHtml(charts) {
    if (!charts || !Object.keys(charts).length) return "";
    var html = '<div class="bi-dash-charts">';
    Object.keys(charts).forEach(function (key) {
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

  function toolbar() {
    return (
      '<div class="bi-dashboard-rest__toolbar">' +
      '<button type="button" class="ngt-btn ngt-btn--outline bi-dash-refresh" data-action="refresh">' + esc("Refresh") + "</button>" +
      '<button type="button" class="ngt-btn ngt-btn--outline bi-dash-export" data-action="export">' + esc("Export CSV") + "</button>" +
      "</div>"
    );
  }

  function heading(text, meta) {
    return '<h2 class="bi-dashboard-rest__heading">' + esc(text) + "</h2>" + sourceBadge(meta);
  }

  function recentList(data) {
    var i18n = cfg.i18n || {};
    var recent = data.recentSessions || [];
    var html = "<h3>" + esc(i18n.sessions || "Recent sessions") + "</h3>";
    html += recent.length
      ? recent.map(sessionRow).join("")
      : '<p class="bi-dashboard-rest__empty">' + esc(i18n.emptySessions || i18n.empty || "No sessions yet.") + "</p>";
    return html;
  }

  /**
   * Student — next-lesson hero first, then KPIs and sessions.
   */
  function renderStudent(data, meta) {
    var user = data.user || {};
    var kpis = data.kpis || {};
    var html = '<div class="bi-dashboard-rest__inner">' + toolbar();
    html += heading("Welcome back, " + (user.displayName || "Learner"), meta);
    html += nextSessionHero(data.nextSession, "student");
    html += quickActions("student");
    html += '<div class="bi-dash-kpi-grid">';
    html += kpiCard("Sessions completed", String(kpis.sessionsCompleted != null ? kpis.sessionsCompleted : 0));
    html += kpiCard("Avg. rating given", kpis.avgRatingGiven != null ? String(kpis.avgRatingGiven) : "—");
    html += kpiCard("Account balance", "R" + zar(kpis.accountBalance));
    html += kpiCard("Achievements", String(kpis.achievementCount != null ? kpis.achievementCount : 0));
    html += "</div>";
    html += chartsHtml(data.charts);
    html += recentList(data);
    html += "</div>";
    return html;
  }

  /**
   * Parent — family overview (learners) and balance first.
   */
  function renderParent(data, meta) {
    var user = data.user || {};
    var kpis = data.kpis || {};
    var learners = data.learners || [];
    var html = '<div class="bi-dashboard-rest__inner">' + toolbar();
    html += heading("Welcome back, " + (user.displayName || "Parent"), meta);
    if (learners.length) {
      html += '<div class="bi-dash-learners" role="list" aria-label="' + esc("Your learners") + '">';
      learners.forEach(function (l) {
        var name = typeof l === "string" ? l : (l && (l.name || l.displayName)) || "";
        if (name) html += '<span class="bi-dash-learners__chip" role="listitem">' + esc(name) + "</span>";
      });
      html += "</div>";
    }
    html += nextSessionHero(data.nextSession, "parent");
    html += quickActions("parent");
    html += '<div class="bi-dash-kpi-grid">';
    html += kpiCard("Account balance", "R" + zar(kpis.accountBalance));
    html += kpiCard("Learners", String(kpis.learnerCount != null ? kpis.learnerCount : learners.length));
    html += kpiCard("Sessions completed", String(kpis.sessionsCompleted != null ? kpis.sessionsCompleted : 0));
    html += kpiCard("Avg. rating given", kpis.avgRatingGiven != null ? String(kpis.avgRatingGiven) : "—");
    html += "</div>";
    html += chartsHtml(data.charts);
    html += recentList(data);
    html += "</div>";
    return html;
  }

  /**
   * Tutor — next lesson join, earnings/payout, application status, recent sessions.
   */
  function renderTutor(data, meta) {
    var user = data.user || {};
    var kpis = data.kpis || {};
    var html = '<div class="bi-dashboard-rest__inner">' + toolbar();
    html += heading(user.displayName || "Tutor", meta);
    html += nextSessionHero(data.nextSession, "tutor");
    html += applicationCard(data.application);
    html += quickActions("tutor");
    html += '<div class="bi-dash-kpi-grid">';
    html += kpiCard("This month", "R" + zar(kpis.monthEarnings));
    html += kpiCard("Pending payout", "R" + zar(kpis.pendingPayout));
    html += kpiCard("Sessions", String(kpis.sessionsMonth != null ? kpis.sessionsMonth : 0));
    html += kpiCard("Rating", kpis.averageRating ? String(kpis.averageRating) : "—");
    html += "</div>";
    html += chartsHtml(data.charts);
    html += recentList(data);
    html += "</div>";
    return html;
  }

  function renderAdmin(data, meta) {
    var kpis = data.kpis || {};
    var html = '<div class="bi-dashboard-rest__inner">' + toolbar() + sourceBadge(meta);
    html += quickActions("admin");
    html += '<div class="bi-dash-kpi-grid">';
    html += kpiCard("Revenue", "R" + zar(kpis.revenue));
    html += kpiCard("Sessions", String(kpis.sessions != null ? kpis.sessions : 0));
    html += kpiCard("Tutors", String(kpis.tutors != null ? kpis.tutors : 0));
    html += kpiCard("Pending apps", String(kpis.pending != null ? kpis.pending : 0));
    html += "</div>";
    html += chartsHtml(data.charts);
    html += "</div>";
    return html;
  }

  function render(type, data, meta) {
    if (type === "admin") return renderAdmin(data, meta);
    if (type === "tutor") return renderTutor(data, meta);
    if (type === "parent") return renderParent(data, meta);
    return renderStudent(data, meta);
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

  function revealMount(el) {
    el.classList.add("is-visible");
    el.classList.remove("ngt-animate");
  }

  function initMount(el) {
    var type = el.getAttribute("data-dashboard") || cfg.type || "student";
    var i18n = cfg.i18n || {};
    var lastData = {};
    revealMount(el);
    el.setAttribute("aria-busy", "true");
    el.innerHTML =
      '<div class="ngt-skeleton-grid" role="status" aria-label="' + esc(i18n.loading) + '">' +
      '<span class="ngt-skeleton ngt-skeleton--kpi" aria-hidden="true"></span>' +
      '<span class="ngt-skeleton ngt-skeleton--kpi" aria-hidden="true"></span>' +
      '<span class="ngt-skeleton ngt-skeleton--kpi" aria-hidden="true"></span>' +
      '<span class="ngt-skeleton ngt-skeleton--kpi" aria-hidden="true"></span>' +
      '</div>' +
      '<span class="ngt-skeleton ngt-skeleton--title" aria-hidden="true"></span>' +
      '<span class="ngt-skeleton ngt-skeleton--card" aria-hidden="true"></span>' +
      '<p class="screen-reader-text bi-dashboard-rest__loading">' + esc(i18n.loading) + "</p>";
    fetchDashboard()
      .then(function (result) {
        var data = result.payload || {};
        var meta = result.meta || {};
        lastData = data;
        el.innerHTML = render(type, data, meta);
        paintCharts(data.charts);
        bindToolbar(el, type, function () { return lastData; });
        bindJoinButtons(el);
        el.setAttribute("aria-busy", "false");
      })
      .catch(function () {
        el.innerHTML =
          '<div class="bi-dashboard-rest__error" role="alert"><p>' +
          esc(i18n.error || "Could not load dashboard data.") +
          "</p>" +
          '<button type="button" class="ngt-btn ngt-btn--primary bi-dash-retry">' +
          esc(i18n.retry || "Try again") +
          "</button></div>";
        el.setAttribute("aria-busy", "false");
        var retry = el.querySelector(".bi-dash-retry");
        if (retry) {
          retry.addEventListener("click", function () {
            initMount(el);
          });
        }
      });
  }

  document.addEventListener("DOMContentLoaded", function () {
    document.querySelectorAll(".bi-dashboard-rest[data-dashboard]").forEach(initMount);
  });
})();
