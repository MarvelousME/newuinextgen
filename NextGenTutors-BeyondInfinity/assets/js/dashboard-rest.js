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

  function t(key, fallback) {
    var i18n = cfg.i18n || {};
    var v = i18n[key];
    return v == null || v === "" ? fallback : v;
  }

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

  function fmtWhen(str) {
    if (!str) return "";
    var d = new Date(String(str).replace(" ", "T"));
    if (Number.isNaN(d.getTime())) return str;
    return d.toLocaleString("en-ZA", {
      weekday: "short",
      day: "numeric",
      month: "short",
      hour: "2-digit",
      minute: "2-digit",
    });
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

  function kpiCard(label, value, hint, href) {
    var inner =
      '<div class="bi-dash-kpi__label">' +
      esc(label) +
      "</div>" +
      '<div class="bi-dash-kpi__value">' +
      esc(value) +
      "</div>" +
      (hint ? '<div class="bi-dash-kpi__hint">' + esc(hint) + "</div>" : "");
    if (href) {
      return '<a class="bi-dash-kpi ngt-card" href="' + esc(href) + '">' + inner + "</a>";
    }
    return '<div class="bi-dash-kpi ngt-card">' + inner + "</div>";
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
            t("joinFailed", "Join failed");
          var reason =
            (data && data.reason) ||
            (data && data.window && data.window.reason) ||
            (data && data.data && data.data.window && data.data.window.reason) ||
            "";
          var err = new Error(typeof msg === "string" ? msg : t("joinFailed", "Join failed"));
          err.reason = reason;
          throw err;
        }
        var payload = data && data.data ? data.data : data;
        return payload || {};
      });
    });
  }

  function joinReasonCopy(reason) {
    var key = String(reason || "");
    if (key === "too_early") return t("joinTooEarly", "This lesson is not open to join yet.");
    if (key === "too_late") return t("joinTooLate", "The join window for this lesson has closed.");
    if (key === "payment_required") return t("joinPayment", "Payment is still required before you can join.");
    if (key === "session_closed") return t("joinClosed", "This lesson is no longer available.");
    if (key === "missing_session" || key === "not_ready" || key.indexOf("status_") === 0) {
      return t("joinNotReady", "This lesson is not ready to join yet.");
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
    container.querySelectorAll(".bi-dash-join-btn").forEach(function (btn) {
      if (btn.dataset.bound === "1") return;
      btn.dataset.bound = "1";
      btn.addEventListener("click", function () {
        var sid = Number(btn.getAttribute("data-session-id") || 0);
        var bid = Number(btn.getAttribute("data-booking-id") || 0);
        var original = btn.textContent;
        btn.disabled = true;
        btn.setAttribute("aria-busy", "true");
        btn.textContent = t("joining", "Joining…");
        launchSession(sid, bid)
          .then(function (payload) {
            var url = payload.launch_url || payload.classroom_url || payload.player_url || payload.meeting_url || payload.join_url || "";
            if (!url) throw new Error(t("joinFailed", "No launch URL returned"));
            var note = btn.parentNode && btn.parentNode.querySelector(".bi-dash-join-error");
            if (note) note.remove();
            window.location.assign(url);
          })
          .catch(function (err) {
            var mapped = joinReasonCopy(err && err.reason) || joinReasonCopy(btn.getAttribute("data-join-reason"));
            var msg = mapped || (err && err.message) || t("joinFailed", "Unable to join lesson");
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
    var join = joinButton(s, "bi-dash-session__join ngt-btn ngt-btn--sm ngt-btn--primary", t("joinLesson", "Join lesson"));
    var alt = s.peerName ? s.peerName : "";
    return (
      '<li class="bi-dash-session">' +
      (s.peerImage ? '<img src="' + esc(s.peerImage) + '" alt="' + esc(alt) + '" class="bi-dash-session__img" loading="lazy" />' : "") +
      '<div class="bi-dash-session__body"><div class="bi-dash-session__title">' +
      esc(s.peerName) +
      " · " +
      esc(s.subject) +
      '</div><div class="bi-dash-session__meta">' +
      esc(fmtWhen(s.createdAt)) +
      "</div></div>" +
      join +
      '<span class="bi-dash-session__status">' +
      esc(s.statusLabel || s.attendance || "") +
      "</span></li>"
    );
  }

  function sourceBadge(meta) {
    if (!cfg.isStaff) return "";
    var src = (meta && meta.source) || "unknown";
    return '<span class="bi-dashboard-rest__source" data-source="' + esc(src) + '">' + esc(t("source", "Source") + ": " + src) + "</span>";
  }

  function setHeading(el, text, meta) {
    var h = el.querySelector("[data-bi-dash-heading]");
    if (h) h.textContent = text;
    var existing = el.querySelector(":scope > .bi-dashboard-rest__source");
    if (existing) existing.remove();
    if (cfg.isStaff && h) h.insertAdjacentHTML("afterend", sourceBadge(meta));
  }

  /**
   * Next-session hero card. Empty state keeps the slot visible with a booking CTA.
   */
  function nextSessionHero(s, type) {
    if (!s) {
      var ctaUrl = "";
      var ctaLabel = "";
      if (type === "tutor") {
        ctaUrl = pages.becomeATutor || "";
        ctaLabel = t("updateProfile", "Update profile");
      } else if (type === "parent") {
        ctaUrl = pages.findATutor || "";
        ctaLabel = t("bookForChild", "Book for your child");
      } else {
        ctaUrl = pages.findATutor || "";
        ctaLabel = t("bookSession", "Book a session");
      }
      var cta = ctaUrl
        ? '<a href="' + esc(ctaUrl) + '" class="ngt-btn ngt-btn--sm ngt-btn--primary">' + esc(ctaLabel) + "</a>"
        : "";
      return (
        '<div class="bi-dash-hero bi-dash-hero--empty ngt-card" role="status">' +
        '<div class="bi-dash-hero__eyebrow">' +
        esc(t("nextSession", "Next session")) +
        "</div>" +
        '<p class="bi-dashboard-rest__empty">' +
        esc(t("noUpcoming", "No upcoming lesson yet.")) +
        "</p>" +
        cta +
        "</div>"
      );
    }
    var join = joinButton(s, "bi-dash-hero__join ngt-btn ngt-btn--primary", t("joinAv", "Join audio + video lesson"));
    var alt = s.peerName ? s.peerName : "";
    return (
      '<div class="bi-dash-hero ngt-card">' +
      '<div class="bi-dash-hero__eyebrow">' +
      esc(t("nextSession", "Next session")) +
      "</div>" +
      '<div class="bi-dash-hero__row">' +
      (s.peerImage ? '<img src="' + esc(s.peerImage) + '" alt="' + esc(alt) + '" class="bi-dash-hero__img" loading="lazy" />' : "") +
      '<div class="bi-dash-hero__body">' +
      '<div class="bi-dash-hero__title">' +
      esc(s.peerName) +
      " · " +
      esc(s.subject) +
      "</div>" +
      '<div class="bi-dash-hero__meta">' +
      esc(fmtWhen(s.createdAt)) +
      (s.statusLabel ? " · " + esc(s.statusLabel) : "") +
      "</div>" +
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
      if (pages.support || pages.contact) actions.push({ label: t("getSupport", "Get support"), url: pages.support || pages.contact });
    } else if (type === "admin") {
      if (pages.adminArea) actions.push({ label: t("openAdmin", "Open admin area"), url: pages.adminArea, primary: true });
      if (pages.support || pages.contact) actions.push({ label: t("supportInbox", "Support inbox"), url: pages.support || pages.contact });
    } else if (type === "parent") {
      if (pages.findATutor) actions.push({ label: t("bookForChild", "Book for your child"), url: pages.findATutor, primary: true });
      if (pages.pricing) actions.push({ label: t("viewPricing", "View pricing"), url: pages.pricing });
      if (pages.support || pages.contact) actions.push({ label: t("billingHelp", "Billing help"), url: pages.support || pages.contact });
    } else {
      if (pages.findATutor) actions.push({ label: t("bookSession", "Book a session"), url: pages.findATutor, primary: true });
      if (pages.pricing) actions.push({ label: t("viewPricing", "View pricing"), url: pages.pricing });
      if (pages.support || pages.contact) actions.push({ label: t("getHelp", "Get help"), url: pages.support || pages.contact });
    }
    if (!actions.length) return "";
    var html = '<nav class="bi-dash-actions" aria-label="' + esc(t("quickActions", "Quick actions")) + '">';
    actions.forEach(function (a) {
      html +=
        '<a href="' +
        esc(a.url) +
        '" class="ngt-btn ngt-btn--sm ' +
        (a.primary ? "ngt-btn--primary" : "ngt-btn--outline") +
        '">' +
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
    var status = String(app.status).toLowerCase();
    var copy = {
      pending: t("appPending", "Your tutor application is being reviewed. We typically respond within 2 business days."),
      approved: t("appApproved", "Your tutor application has been approved. Welcome aboard!"),
      rejected: t("appRejected", "Your tutor application was not approved this time."),
    };
    var html =
      '<div class="bi-dash-app-status ngt-card" data-status="' +
      esc(status) +
      '">' +
      '<div class="bi-dash-app-status__head">' +
      '<h3 class="bi-dash-app-status__title">' +
      esc(t("applicationStatus", "Application status")) +
      "</h3>" +
      '<span class="ngt-badge ngt-badge--' +
      esc(status) +
      '">' +
      esc(status.charAt(0).toUpperCase() + status.slice(1)) +
      "</span>" +
      "</div>" +
      '<p class="bi-dash-app-status__copy">' +
      esc(copy[status] || t("applicationUpdate", "We'll update you when there's news.")) +
      "</p>";
    if (status === "rejected" && app.reviewNotes) {
      html += '<p class="bi-dash-app-status__notes">' + esc(app.reviewNotes) + "</p>";
    }
    if (status === "rejected") {
      html += '<p class="bi-dash-app-status__next">';
      if (pages.becomeATutor) {
        html +=
          '<a class="ngt-btn ngt-btn--sm ngt-btn--primary" href="' +
          esc(pages.becomeATutor) +
          '">' +
          esc(t("updateApplication", "Update application")) +
          "</a> ";
      }
      if (pages.support || pages.contact) {
        html +=
          '<a class="ngt-btn ngt-btn--sm ngt-btn--outline" href="' +
          esc(pages.support || pages.contact) +
          '">' +
          esc(t("contactSupport", "Contact support")) +
          "</a>";
      }
      html += "</p>";
    }
    if (app.submittedAt) {
      html += '<p class="bi-dash-app-status__meta">' + esc(t("submitted", "Submitted") + " " + fmtDate(app.submittedAt)) + "</p>";
    }
    html += "</div>";
    return html;
  }

  function chartHasData(spec) {
    var data = (spec && spec.data) || [];
    if (!data.length) return false;
    return data.some(function (n) {
      return Number(n) !== 0;
    });
  }

  function chartsHtml(charts) {
    if (!charts) return "";
    var keys = Object.keys(charts).filter(function (key) {
      return chartHasData(charts[key]);
    });
    if (!keys.length) return "";
    var html = '<div class="bi-dash-charts">';
    keys.forEach(function (key) {
      var spec = charts[key] || {};
      var title = spec.label || key;
      html +=
        '<div class="bi-dash-chart ngt-card">' +
        '<h3 class="bi-dash-chart__title">' +
        esc(title) +
        "</h3>" +
        '<canvas id="bi-dash-chart-' +
        esc(key) +
        '" data-chart-key="' +
        esc(key) +
        '" height="180" role="img" aria-label="' +
        esc(title) +
        '"></canvas></div>';
    });
    html += "</div>";
    return html;
  }

  function destroyCharts() {
    chartInstances.forEach(function (c) {
      try {
        c.destroy();
      } catch (e) {}
    });
    chartInstances = [];
  }

  function paintCharts(charts) {
    if (!window.Chart || !charts) return;
    destroyCharts();
    Object.keys(charts).forEach(function (key) {
      var spec = charts[key];
      if (!chartHasData(spec)) return;
      var canvas = document.getElementById("bi-dash-chart-" + key);
      if (!canvas || !spec) return;
      var title = spec.label || key;
      canvas.setAttribute("aria-label", title);
      var chart = new Chart(canvas.getContext("2d"), {
        type: spec.type === "line" ? "line" : "bar",
        data: {
          labels: spec.labels || [],
          datasets: [
            {
              label: title,
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
    if (!window.confirm(t("exportConfirm", "Download a CSV of your dashboard metrics?"))) {
      return;
    }
    var rows = [["metric", "value"]];
    var kpis = data.kpis || {};
    Object.keys(kpis).forEach(function (k) {
      rows.push([k, String(kpis[k])]);
    });
    var csv = rows
      .map(function (r) {
        return r
          .map(function (c) {
            return '"' + String(c).replace(/"/g, '""') + '"';
          })
          .join(",");
      })
      .join("\n");
    var blob = new Blob([csv], { type: "text/csv;charset=utf-8" });
    var a = document.createElement("a");
    a.href = URL.createObjectURL(blob);
    a.download = "dashboard-" + type + ".csv";
    a.click();
  }

  function toolbar() {
    return (
      '<div class="bi-dashboard-rest__toolbar">' +
      '<button type="button" class="ngt-btn ngt-btn--outline bi-dash-refresh" data-action="refresh" data-bi-focus="refresh">' +
      esc(t("refresh", "Refresh")) +
      "</button>" +
      '<button type="button" class="ngt-btn ngt-btn--outline bi-dash-export" data-action="export" data-bi-focus="export">' +
      esc(t("exportCsv", "Export CSV")) +
      "</button>" +
      "</div>"
    );
  }

  function recentList(data) {
    var recent = data.recentSessions || [];
    var html = '<section id="bi-dash-sessions" class="bi-dash-sessions"><h3>' + esc(t("sessions", "Recent sessions")) + "</h3>";
    html += recent.length
      ? '<ul class="bi-dash-session-list">' + recent.map(sessionRow).join("") + "</ul>"
      : '<p class="bi-dashboard-rest__empty">' + esc(t("emptySessions", t("empty", "No sessions yet."))) + "</p>";
    html += "</section>";
    return html;
  }

  function ratingLabel(value) {
    if (value == null || value === "" || value === 0 || value === "0") {
      return t("noRatings", "No ratings yet");
    }
    return String(value);
  }

  /**
   * Student — next-lesson hero first, then KPIs and sessions.
   */
  function renderStudent(data) {
    var kpis = data.kpis || {};
    var html = '<div class="bi-dashboard-rest__inner">';
    html += nextSessionHero(data.nextSession, "student");
    html += quickActions("student");
    html += '<div class="bi-dash-kpi-grid">';
    html += kpiCard(t("kpiSessionsCompleted", "Sessions completed"), String(kpis.sessionsCompleted != null ? kpis.sessionsCompleted : 0), "", "#bi-dash-sessions");
    html += kpiCard(t("kpiAvgRatingGiven", "Avg. rating given"), ratingLabel(kpis.avgRatingGiven), "", "#bi-dash-sessions");
    html += kpiCard(t("kpiAccountBalance", "Account balance"), "R" + zar(kpis.accountBalance), "", pages.checkout || pages.pricing || "");
    html += kpiCard(t("kpiAchievements", "Achievements"), String(kpis.achievementCount != null ? kpis.achievementCount : 0));
    html += "</div>";
    html += chartsHtml(data.charts);
    html += recentList(data);
    html += toolbar();
    html += "</div>";
    return html;
  }

  /**
   * Parent — family overview (learners) and balance first.
   */
  function renderParent(data) {
    var kpis = data.kpis || {};
    var learners = data.learners || [];
    var html = '<div class="bi-dashboard-rest__inner">';
    if (learners.length) {
      html += '<div class="bi-dash-learners" role="list" aria-label="' + esc(t("yourLearners", "Your learners")) + '">';
      learners.forEach(function (l) {
        var name = typeof l === "string" ? l : (l && (l.name || l.displayName)) || "";
        var id = typeof l === "object" && l ? l.id || l.userId || l.user_id || "" : "";
        if (!name) return;
        var href = pages.findATutor || "";
        if (href && id) {
          href += (href.indexOf("?") >= 0 ? "&" : "?") + "learner=" + encodeURIComponent(id);
        }
        if (href) {
          html += '<a class="bi-dash-learners__chip" role="listitem" href="' + esc(href) + '">' + esc(name) + "</a>";
        } else {
          html += '<span class="bi-dash-learners__chip" role="listitem">' + esc(name) + "</span>";
        }
      });
      html += "</div>";
    }
    html += nextSessionHero(data.nextSession, "parent");
    html += quickActions("parent");
    html += '<div class="bi-dash-kpi-grid">';
    html += kpiCard(t("kpiAccountBalance", "Account balance"), "R" + zar(kpis.accountBalance), "", pages.checkout || pages.pricing || "");
    html += kpiCard(t("kpiLearners", "Learners"), String(kpis.learnerCount != null ? kpis.learnerCount : learners.length), "", pages.findATutor || "");
    html += kpiCard(t("kpiSessionsCompleted", "Sessions completed"), String(kpis.sessionsCompleted != null ? kpis.sessionsCompleted : 0), "", "#bi-dash-sessions");
    html += kpiCard(t("kpiAvgRatingGiven", "Avg. rating given"), ratingLabel(kpis.avgRatingGiven), "", "#bi-dash-sessions");
    html += "</div>";
    html += chartsHtml(data.charts);
    html += recentList(data);
    html += toolbar();
    html += "</div>";
    return html;
  }

  /**
   * Tutor — next lesson join, earnings/payout, application status, recent sessions.
   */
  function renderTutor(data) {
    var kpis = data.kpis || {};
    var html = '<div class="bi-dashboard-rest__inner">';
    html += nextSessionHero(data.nextSession, "tutor");
    html += applicationCard(data.application);
    html += quickActions("tutor");
    html += '<div class="bi-dash-kpi-grid">';
    html += kpiCard(t("kpiThisMonth", "This month"), "R" + zar(kpis.monthEarnings), "", pages.payouts || "#bi-dash-sessions");
    html += kpiCard(t("kpiPendingPayout", "Pending payout"), "R" + zar(kpis.pendingPayout), "", pages.payouts || "#bi-dash-sessions");
    html += kpiCard(t("kpiSessions", "Sessions"), String(kpis.sessionsMonth != null ? kpis.sessionsMonth : 0), "", "#bi-dash-sessions");
    html += kpiCard(t("kpiRating", "Rating"), ratingLabel(kpis.averageRating), "", pages.becomeATutor || "#bi-dash-sessions");
    html += "</div>";
    html += chartsHtml(data.charts);
    html += recentList(data);
    html += toolbar();
    html += "</div>";
    return html;
  }

  function renderAdmin(data) {
    var kpis = data.kpis || {};
    var html = '<div class="bi-dashboard-rest__inner">';
    html += quickActions("admin");
    html += '<div class="bi-dash-kpi-grid">';
    html += kpiCard(t("kpiRevenue", "Revenue"), "R" + zar(kpis.revenue), "", pages.adminArea || "");
    html += kpiCard(t("kpiSessions", "Sessions"), String(kpis.sessions != null ? kpis.sessions : 0), "", pages.adminArea || "");
    html += kpiCard(t("kpiTutors", "Tutors"), String(kpis.tutors != null ? kpis.tutors : 0), "", pages.adminArea || "");
    html += kpiCard(t("kpiPendingApps", "Pending apps"), String(kpis.pending != null ? kpis.pending : 0), "", pages.adminArea || "");
    html += "</div>";
    html += chartsHtml(data.charts);
    html += toolbar();
    html += "</div>";
    return html;
  }

  function render(type, data) {
    if (type === "admin") return renderAdmin(data);
    if (type === "tutor") return renderTutor(data);
    if (type === "parent") return renderParent(data);
    return renderStudent(data);
  }

  function welcomeText(type, data) {
    var user = (data && data.user) || {};
    var name = user.displayName || "";
    if (type === "admin") return t("operations", "Operations");
    if (type === "tutor") return name || t("tutor", "Tutor");
    if (type === "parent") return t("welcomeBack", "Welcome back") + (name ? ", " + name : "");
    return t("welcomeBack", "Welcome back") + (name ? ", " + name : "");
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

  function captureFocus(el) {
    var a = document.activeElement;
    if (!a || !el.contains(a)) return "";
    return a.getAttribute("data-bi-focus") || a.getAttribute("data-action") || "";
  }

  function restoreFocus(el, token) {
    if (!token) return;
    var n = el.querySelector('[data-bi-focus="' + token + '"]') || el.querySelector('[data-action="' + token + '"]');
    if (n && typeof n.focus === "function") n.focus();
  }

  function revealMount(el) {
    el.classList.add("is-visible");
    el.classList.remove("ngt-animate");
  }

  function ensureShell(el) {
    var body = el.querySelector(".bi-dashboard-rest__body");
    if (body) return body;
    var heading = document.createElement("h2");
    heading.className = "bi-dashboard-rest__heading";
    heading.setAttribute("data-bi-dash-heading", "1");
    body = document.createElement("div");
    body.className = "bi-dashboard-rest__body";
    body.setAttribute("role", "region");
    body.setAttribute("aria-live", "polite");
    while (el.firstChild) {
      body.appendChild(el.firstChild);
    }
    el.appendChild(heading);
    el.appendChild(body);
    el.removeAttribute("aria-live");
    return body;
  }

  function initMount(el) {
    var type = el.getAttribute("data-dashboard") || cfg.type || "student";
    var lastData = {};
    var body = ensureShell(el);
    var focusToken = captureFocus(el);
    revealMount(el);
    el.setAttribute("aria-busy", "true");
    body.setAttribute("aria-busy", "true");
    body.innerHTML =
      '<div class="ngt-skeleton-grid" role="status" aria-label="' +
      esc(t("loading", "Loading your dashboard…")) +
      '">' +
      '<span class="ngt-skeleton ngt-skeleton--kpi" aria-hidden="true"></span>' +
      '<span class="ngt-skeleton ngt-skeleton--kpi" aria-hidden="true"></span>' +
      '<span class="ngt-skeleton ngt-skeleton--kpi" aria-hidden="true"></span>' +
      '<span class="ngt-skeleton ngt-skeleton--kpi" aria-hidden="true"></span>' +
      "</div>" +
      '<span class="ngt-skeleton ngt-skeleton--title" aria-hidden="true"></span>' +
      '<span class="ngt-skeleton ngt-skeleton--card" aria-hidden="true"></span>' +
      '<p class="screen-reader-text bi-dashboard-rest__loading">' +
      esc(t("loading", "Loading your dashboard…")) +
      "</p>";
    fetchDashboard()
      .then(function (result) {
        var data = result.payload || {};
        var meta = result.meta || {};
        lastData = data;
        setHeading(el, welcomeText(type, data), meta);
        body.innerHTML = render(type, data);
        paintCharts(data.charts);
        bindToolbar(el, type, function () {
          return lastData;
        });
        bindJoinButtons(body);
        el.setAttribute("aria-busy", "false");
        body.setAttribute("aria-busy", "false");
        restoreFocus(el, focusToken);
      })
      .catch(function () {
        body.innerHTML =
          '<div class="bi-dashboard-rest__error" role="alert"><p>' +
          esc(t("error", "Could not load dashboard data.")) +
          "</p>" +
          '<button type="button" class="ngt-btn ngt-btn--primary bi-dash-retry" data-bi-focus="retry">' +
          esc(t("retry", "Try again")) +
          "</button></div>";
        el.setAttribute("aria-busy", "false");
        body.setAttribute("aria-busy", "false");
        var retry = body.querySelector(".bi-dash-retry");
        if (retry) {
          retry.addEventListener("click", function () {
            initMount(el);
          });
          restoreFocus(el, focusToken || "retry");
        }
      });
  }

  document.addEventListener("DOMContentLoaded", function () {
    document.querySelectorAll(".bi-dashboard-rest[data-dashboard]").forEach(initMount);
  });
})();
