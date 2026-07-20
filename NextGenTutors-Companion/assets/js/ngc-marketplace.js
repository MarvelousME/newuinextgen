/**
 * NextGen Tutors — AJAX marketplace (ngc/v1/marketplace/*).
 */
(function () {
  "use strict";

  var cfg = window.ngcMarketplace || {};
  var root = (cfg.restRoot || "/wp-json/").replace(/\/?$/, "/");
  var ns = cfg.namespace || "ngc/v1";
  var i18n = cfg.i18n || {};

  function esc(s) {
    var d = document.createElement("div");
    d.textContent = s == null ? "" : String(s);
    return d.innerHTML;
  }

  function api(path, params) {
    var url = new URL(root + ns + path, window.location.origin);
    Object.keys(params || {}).forEach(function (k) {
      if (params[k] !== "" && params[k] != null && params[k] !== false) {
        url.searchParams.set(k, String(params[k]));
      }
    });
    var headers = { Accept: "application/json" };
    if (cfg.nonce) headers["X-WP-Nonce"] = cfg.nonce;
    return fetch(url.toString(), { credentials: "same-origin", headers: headers }).then(function (res) {
      return res.json().then(function (data) {
        if (!res.ok) throw new Error((data && data.message) || i18n.error || "Request failed");
        return data.data || data;
      });
    });
  }

  function bindMagnetic(el) {
    if (!el || el.dataset.nbiMagnetic) return;
    el.dataset.nbiMagnetic = "1";
    el.addEventListener("mousemove", function (e) {
      var rect = el.getBoundingClientRect();
      var x = ((e.clientX - rect.left) / rect.width - 0.5) * 12;
      var y = ((e.clientY - rect.top) / rect.height - 0.5) * 12;
      el.style.setProperty("--nbi-mag-x", x + "px");
      el.style.setProperty("--nbi-mag-y", y + "px");
    });
    el.addEventListener("mouseleave", function () {
      el.style.setProperty("--nbi-mag-x", "0");
      el.style.setProperty("--nbi-mag-y", "0");
    });
  }

  function card(t) {
    var subjects = (t.subjects || []).slice(0, 4);
    var rate = t.hourlyRate || t.rate || 0;
    var match = t.matchScore != null ? t.matchScore : Math.min(99, Math.round((Number(t.rating || 4.8) / 5) * 100));
    var available = t.available !== false;
    var avatar = t.avatar || t.imageUrl;
    var avatarHtml = avatar
      ? '<img class="ngc-mkt-card__avatar" src="' + esc(avatar) + '" alt="" loading="lazy" />'
      : '<div class="ngc-mkt-card__avatar ngc-mkt-card__avatar--placeholder" aria-hidden="true"></div>';
    var chips = subjects
      .map(function (s) {
        return '<span class="ngc-mkt-chip">' + esc(s) + "</span>";
      })
      .join("");
    if (t.province) {
      chips += '<span class="ngc-mkt-chip ngc-mkt-chip--province">' + esc(t.province) + "</span>";
    }
    var img = t.imageUrl
      ? '<img class="ngc-mkt-card__img" src="' + esc(t.imageUrl) + '" alt="" loading="lazy" />'
      : "";

    return (
      '<article class="ngc-mkt-card" role="listitem" data-tutor-id="' + esc(t.postId || "") + '">' +
      img +
      '<div class="ngc-mkt-card__header">' +
      avatarHtml +
      '<div class="ngc-mkt-card__status">' +
      (available ? '<span class="ngc-mkt-card__pulse" aria-hidden="true"></span>' + esc(i18n.available || "Available") : esc(i18n.unavailable || "Busy")) +
      "</div>" +
      '<span class="ngc-mkt-card__match">' +
      esc(match) +
      "% " +
      esc(i18n.match || "Match") +
      "</span></div>" +
      '<div class="ngc-mkt-card__body">' +
      '<h3 class="ngc-mkt-card__name">' +
      esc(t.name) +
      (t.vetted ? ' <span class="ngc-mkt-card__badge">' + esc(i18n.verified || "Verified") + "</span>" : "") +
      "</h3>" +
      (chips ? '<div class="ngc-mkt-card__chips">' + chips + "</div>" : "") +
      '<p class="ngc-mkt-card__meta">★ ' +
      esc(Number(t.rating || 0).toFixed(1)) +
      (rate ? " · R" + esc(rate) + "/hr" : "") +
      "</p>" +
      '<div class="ngc-mkt-card__actions">' +
      '<a class="ngc-mkt-btn ngc-mkt-btn--outline" href="' +
      esc(t.permalink || t.url || "#") +
      '">' +
      esc(i18n.view || "View profile") +
      "</a>" +
      '<button type="button" class="ngc-mkt-btn ngc-mkt-btn--book" data-nbi-book="' +
      esc(t.postId || "") +
      '">' +
      esc(i18n.book || "Book Session") +
      "</button>" +
      "</div></div></article>"
    );
  }

  function wireCards(gridEl) {
    gridEl.querySelectorAll("[data-nbi-book]").forEach(function (btn) {
      bindMagnetic(btn);
      btn.addEventListener("click", function () {
        var article = btn.closest(".ngc-mkt-card");
        var id = btn.getAttribute("data-nbi-book");
        var tutor = (window.__ngcMktItems || []).find(function (t) {
          return String(t.postId) === String(id);
        });
        if (!tutor && article) {
          var nameEl = article.querySelector(".ngc-mkt-card__name");
          var linkEl = article.querySelector(".ngc-mkt-btn--outline");
          tutor = {
            name: nameEl ? nameEl.textContent : "",
            permalink: linkEl ? linkEl.getAttribute("href") : "#",
          };
        }
        if (window.nbiOpenBookingDrawer && tutor) {
          window.nbiOpenBookingDrawer(tutor);
        } else if (tutor && tutor.permalink) {
          window.location.href = tutor.permalink + "#book";
        }
      });
    });
  }

  function fillSelect(select, items) {
    if (!select || !items) return;
    items.forEach(function (item) {
      var opt = document.createElement("option");
      opt.value = item.value;
      opt.textContent = item.label;
      select.appendChild(opt);
    });
  }

  function init(rootEl) {
    var state = { page: 1, per_page: cfg.perPage || 12 };
    var statusEl = rootEl.querySelector(".ngc-marketplace__status");
    var gridEl = rootEl.querySelector(".ngc-marketplace__grid");
    var pagerEl = rootEl.querySelector(".ngc-marketplace__pagination");
    var searchEl = rootEl.querySelector(".ngc-marketplace__search");
    var sortEl = rootEl.querySelector(".ngc-marketplace__sort");
    var toggleBtn = rootEl.querySelector(".ngc-marketplace__filters-toggle");
    var filtersEl = rootEl.querySelector(".ngc-marketplace__filters");
    var debounce;

    function collectFilters() {
      var f = { page: state.page, per_page: state.per_page, sort: sortEl ? sortEl.value : "rating", q: searchEl ? searchEl.value.trim() : "" };
      rootEl.querySelectorAll("[data-field]").forEach(function (el) {
        var key = el.getAttribute("data-field");
        if (el.type === "checkbox") {
          if (el.checked) f[key] = "1";
        } else if (el.value) {
          f[key] = el.value;
        }
      });
      return f;
    }

    function setStatus(msg) {
      if (statusEl) statusEl.textContent = msg || "";
    }

    function renderPager(total, page, pages) {
      if (!pagerEl) return;
      if (pages <= 1) {
        pagerEl.innerHTML = "";
        return;
      }
      var html = "";
      for (var p = 1; p <= pages; p++) {
        html +=
          '<button type="button" class="ngc-mkt-page' +
          (p === page ? " is-active" : "") +
          '" data-page="' +
          p +
          '">' +
          p +
          "</button>";
      }
      pagerEl.innerHTML = html;
      pagerEl.querySelectorAll("[data-page]").forEach(function (btn) {
        btn.addEventListener("click", function () {
          state.page = parseInt(btn.getAttribute("data-page"), 10) || 1;
          load();
        });
      });
    }

    function load() {
      setStatus(i18n.loading || "Loading…");
      gridEl.classList.add("is-loading");
      api("/marketplace/tutors", collectFilters())
        .then(function (data) {
          var items = data.items || [];
          window.__ngcMktItems = items;
          gridEl.classList.remove("is-loading");
          setStatus(items.length ? items.length + " " + (i18n.results || "tutors found") : i18n.empty || "No tutors found");
          gridEl.innerHTML = items.length ? items.map(card).join("") : '<p class="ngc-mkt-empty">' + esc(i18n.empty || "") + "</p>";
          wireCards(gridEl);
          renderPager(data.total || 0, data.page || 1, data.pages || 0);
        })
        .catch(function () {
          gridEl.classList.remove("is-loading");
          gridEl.innerHTML = '<p class="ngc-mkt-error" role="alert">' + esc(i18n.error || "Error") + "</p>";
        });
    }

    api("/marketplace/filters").then(function (opts) {
      fillSelect(rootEl.querySelector('[data-field="subject"]'), opts.subjects);
      fillSelect(rootEl.querySelector('[data-field="grade"]'), opts.grades);
      fillSelect(rootEl.querySelector('[data-field="province"]'), opts.provinces);
      fillSelect(rootEl.querySelector('[data-field="format"]'), opts.formats);
    });

    if (toggleBtn && filtersEl) {
      toggleBtn.addEventListener("click", function () {
        var open = filtersEl.classList.toggle("is-open");
        toggleBtn.setAttribute("aria-expanded", open ? "true" : "false");
      });
    }

    rootEl.querySelectorAll("select[data-field], input[data-field]").forEach(function (el) {
      el.addEventListener("change", function () {
        state.page = 1;
        load();
      });
    });

    if (searchEl) {
      searchEl.addEventListener("input", function () {
        clearTimeout(debounce);
        debounce = setTimeout(function () {
          state.page = 1;
          load();
        }, 350);
      });
    }

    if (sortEl) {
      sortEl.addEventListener("change", function () {
        state.page = 1;
        load();
      });
    }

    load();
  }

  document.addEventListener("DOMContentLoaded", function () {
    document.querySelectorAll("[data-ngc-marketplace]").forEach(init);
  });
})();
