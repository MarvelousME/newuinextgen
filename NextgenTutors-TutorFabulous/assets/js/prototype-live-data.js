/**
 * Hydrate prototype page shells with live REST / platform stats.
 */
(function () {
  "use strict";

  var cfg = window.biPrototypeLive || {};
  var stats = cfg.stats || {};

  function esc(s) {
    var d = document.createElement("div");
    d.textContent = s == null ? "" : String(s);
    return d.innerHTML;
  }

  function patchPageheadStats() {
    if (!stats.tutor_count && !stats.avg_rating) return;

    document.querySelectorAll(".pagehead__stats .pagehead__stat").forEach(function (stat) {
      var label = (stat.querySelector(".l") || {}).textContent || "";
      var num = stat.querySelector(".n");
      if (!num) return;

      if (/tutor|educator|verified/i.test(label) && stats.tutor_count) {
        num.textContent = stats.tutor_count + "+";
      }
      if (/rating|★/i.test(label) && stats.avg_rating) {
        num.textContent = stats.avg_rating + "★";
      }
      if (/satisfaction|match/i.test(label) && stats.satisfaction) {
        num.textContent = stats.satisfaction + "%";
      }
    });

    document.querySelectorAll(".pagehead__eyebrow").forEach(function (el) {
      if (/500\+|verified educator/i.test(el.textContent) && stats.tutor_count) {
        el.textContent = stats.tutor_count + "+ Verified Educators";
      }
    });
  }

  function cardHTML(t) {
    var type = t.groupType || t.mode || "both";
    var online = type === "online" || type === "both";
    var home = type === "personal" || type === "in-person" || type === "both";
    var rate = t.hourlyRate || t.rate || 320;
    var img = t.imageUrl || t.avatar || "";
    var name = t.name || "Tutor";
    var rating = Number(t.rating || 4.8);
    var degree = t.degree || "";
    var bio = t.bio || "";
    var subjects = Array.isArray(t.subjects) ? t.subjects : [];
    var url = t.permalink || t.url || "#";
    var star = '<svg viewBox="0 0 24 24"><polygon points="12 2 15 9 22 9.5 17 14.5 18.5 22 12 18 5.5 22 7 14.5 2 9.5 9 9"/></svg>';

    return (
      '<div class="tutor-card3d" data-reveal data-format="' + esc(type) + '">' +
      '<div class="tutor-card">' +
      '<div class="tutor-card__photo">' +
      (img ? '<img src="' + esc(img) + '" alt="' + esc(name) + '" loading="lazy" referrerpolicy="no-referrer" />' : "") +
      '<div class="tutor-badges">' +
      (online ? '<span class="tutor-badge tutor-badge--online"><i data-lucide="monitor"></i>Online</span>' : "") +
      (home ? '<span class="tutor-badge tutor-badge--home"><i data-lucide="users"></i>In-Person</span>' : "") +
      "</div>" +
      '<div class="tutor-price"><span class="r">R</span><span class="n">' + esc(rate) + '</span><span class="h">/hr</span></div>' +
      "</div>" +
      '<div class="tutor-card__body">' +
      '<div class="tutor-card__top"><h3 class="tutor-card__name">' + esc(name) + "</h3>" +
      '<span class="tutor-rating">' + star + rating.toFixed(2) + "</span></div>" +
      (degree ? '<div class="tutor-card__degree"><i data-lucide="award"></i><span>' + esc(degree) + "</span></div>" : "") +
      (bio ? '<p class="tutor-card__bio">"' + esc(bio) + '"</p>' : "") +
      '<div class="tutor-tags">' + subjects.map(function (s) { return '<span class="tutor-tag">' + esc(s) + "</span>"; }).join("") + "</div>" +
      '<div class="tutor-card__btns">' +
      '<a class="btn" style="background:var(--slate-100);color:var(--navy);padding:11px" href="' + esc(url) + '">View Bio</a>' +
      '<a class="btn btn--lime" style="padding:11px" href="' + esc(url) + '#book">Book Class</a>' +
      "</div></div></div></div>"
    );
  }

  function hydrateFindTutorDirectory() {
    if (cfg.slug !== "find-a-tutor") return;
    if (document.body.classList.contains("bi-has-live-marketplace")) return;

    var grid = document.getElementById("dir-grid");
    var countEl = document.getElementById("result-count");
    var subjectWrap = document.getElementById("filter-subjects");
    if (!grid || !cfg.rest) return;

    var root = (cfg.rest.root || "/wp-json/").replace(/\/?$/, "/");
    var ns = cfg.rest.namespace || "ngc/v1";
    var headers = { Accept: "application/json" };
    if (cfg.rest.nonce) headers["X-WP-Nonce"] = cfg.rest.nonce;

    fetch(root + ns + "/marketplace/tutors?per_page=24&sort=rating", { credentials: "same-origin", headers: headers })
      .then(function (res) { return res.json(); })
      .then(function (body) {
        var data = body.data || body;
        var items = data.items || [];
        if (!items.length) {
          grid.innerHTML = '<p class="bi-prototype-live-empty">No tutors published yet. Check back soon or <a href="' + esc(cfg.pages.findTutor || "/find-a-tutor/") + '">contact us</a>.</p>';
          if (countEl) countEl.textContent = "0";
          return;
        }

        grid.innerHTML = items.map(cardHTML).join("");
        if (countEl) countEl.textContent = String(data.total || items.length);
        if (window.lucide && window.lucide.createIcons) window.lucide.createIcons();

        if (subjectWrap && !subjectWrap.dataset.liveBound) {
          subjectWrap.dataset.liveBound = "1";
          var tags = {};
          items.forEach(function (t) {
            (t.subjects || []).forEach(function (s) { tags[s] = true; });
          });
          subjectWrap.innerHTML =
            '<button class="fchip is-active" data-subject="all">All</button>' +
            Object.keys(tags).sort().map(function (s) {
              return '<button class="fchip" data-subject="' + esc(s) + '">' + esc(s) + "</button>";
            }).join("");
        }
      })
      .catch(function () {
        grid.innerHTML = '<p class="bi-prototype-live-empty">Could not load tutors. Try the marketplace section below.</p>';
      });
  }

  function patchDashboardShell(detail) {
    var kpis = (detail && detail.kpis) || [];
    document.body.classList.add("bi-dashboard-live-active");

    var hello = document.querySelector(".dash-hello h1");
    if (hello && detail.user && detail.user.displayName) {
      hello.textContent = "Welcome back, " + detail.user.displayName;
    }

    var counters = document.querySelectorAll(".kpi-card .kpi-val, .admin-kpi__val");
    if (counters.length && kpis.length) {
      counters.forEach(function (el, i) {
        if (kpis[i] && kpis[i].value != null) {
          el.textContent = kpis[i].value;
        }
      });
    }
  }

  function bindDashboardHydration() {
    if (!cfg.dashboardType) return;
    var mount = document.querySelector(".bi-dashboard-rest[data-dashboard]");
    if (!mount) return;

    mount.addEventListener("nbi:dashboard-loaded", function (e) {
      patchDashboardShell((e && e.detail) || {});
    });

    if (cfg.dashboard && cfg.dashboard.path) {
      var root = (cfg.dashboard.restRoot || cfg.rest.root || "/wp-json/").replace(/\/?$/, "/");
      var ns = cfg.dashboard.namespace || "ngc/v1";
      var headers = { Accept: "application/json" };
      if (cfg.dashboard.nonce) headers["X-WP-Nonce"] = cfg.dashboard.nonce;

      fetch(root + ns + cfg.dashboard.path, { credentials: "same-origin", headers: headers })
        .then(function (res) { return res.json(); })
        .then(function (body) {
          var payload = body.data || body;
          var user = payload.user || {};
          patchDashboardShell({
            user: user,
            kpis: [
              { value: payload.kpis && payload.kpis.sessionsCompleted != null ? payload.kpis.sessionsCompleted : (payload.kpis && payload.kpis.sessions) },
              { value: payload.kpis && payload.kpis.avgRatingGiven != null ? payload.kpis.avgRatingGiven : (payload.kpis && payload.kpis.averageRating) },
              { value: payload.kpis && payload.kpis.accountBalance != null ? "R" + payload.kpis.accountBalance : (payload.kpis && payload.kpis.monthEarnings != null ? "R" + payload.kpis.monthEarnings : null) },
              { value: payload.kpis && payload.kpis.achievementCount != null ? payload.kpis.achievementCount : (payload.kpis && payload.kpis.pending) },
            ],
          });
        })
        .catch(function () { /* REST mount handles errors */ });
    }
  }

  function boot() {
    patchPageheadStats();
    hydrateFindTutorDirectory();
    bindDashboardHydration();
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", boot);
  } else {
    boot();
  }
})();
