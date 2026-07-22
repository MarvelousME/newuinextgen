/**
 * Command palette — Ctrl/Cmd+K quick navigation.
 *
 * Renders an accessible dialog (combobox + listbox) from the localized
 * biCommand.routes registry. Keyboard: arrows to move, Enter to run,
 * Escape to close. Uses BIFocusTrap when available.
 */
(function () {
  "use strict";

  var cfg = window.biCommand || {};
  var routes = cfg.routes || [];
  var i18n = cfg.i18n || {};
  var overlay = null;
  var input = null;
  var listEl = null;
  var visible = [];
  var activeIndex = -1;
  var lastFocus = null;
  var trap = null;

  function esc(s) {
    var d = document.createElement("div");
    d.textContent = s == null ? "" : String(s);
    return d.innerHTML;
  }

  function build() {
    if (overlay) return;
    overlay = document.createElement("div");
    overlay.className = "bi-command";
    overlay.setAttribute("hidden", "");
    overlay.innerHTML =
      '<div class="bi-command__backdrop" data-command-close></div>' +
      '<div class="bi-command__panel" role="dialog" aria-modal="true" aria-label="' + esc(i18n.title || "Command palette") + '">' +
      '<input type="text" class="bi-command__input" role="combobox" aria-expanded="true" aria-controls="bi-command-list" aria-autocomplete="list" autocomplete="off" spellcheck="false" placeholder="' + esc(i18n.placeholder || "Search…") + '" />' +
      '<div class="bi-command__list" id="bi-command-list" role="listbox" aria-label="' + esc(i18n.title || "Command palette") + '"></div>' +
      '<div class="bi-command__hint">' + esc(i18n.hint || "") + "</div>" +
      "</div>";
    document.body.appendChild(overlay);
    input = overlay.querySelector(".bi-command__input");
    listEl = overlay.querySelector(".bi-command__list");

    input.addEventListener("input", function () {
      filter(input.value);
    });
    input.addEventListener("keydown", function (e) {
      if (e.key === "ArrowDown") { e.preventDefault(); move(1); }
      else if (e.key === "ArrowUp") { e.preventDefault(); move(-1); }
      else if (e.key === "Enter") { e.preventDefault(); run(activeIndex); }
    });
    overlay.addEventListener("click", function (e) {
      if (e.target.hasAttribute("data-command-close")) close();
    });
    listEl.addEventListener("click", function (e) {
      var item = e.target.closest("[data-command-index]");
      if (item) run(parseInt(item.getAttribute("data-command-index"), 10));
    });
  }

  function filter(query) {
    var q = (query || "").trim().toLowerCase();
    visible = routes.filter(function (r) {
      if (!q) return true;
      var hay = (r.label + " " + (r.keywords || "") + " " + (r.section || "")).toLowerCase();
      return q.split(/\s+/).every(function (part) { return hay.indexOf(part) !== -1; });
    });
    activeIndex = visible.length ? 0 : -1;
    paint();
  }

  function paint() {
    if (!visible.length) {
      listEl.innerHTML = '<div class="bi-command__empty" role="status">' + esc(i18n.empty || "No results.") + "</div>";
      return;
    }
    var html = "";
    var lastSection = null;
    visible.forEach(function (r, i) {
      if (r.section && r.section !== lastSection) {
        html += '<div class="bi-command__section" role="presentation">' + esc(r.section) + "</div>";
        lastSection = r.section;
      }
      html +=
        '<div class="bi-command__item' + (i === activeIndex ? " is-active" : "") + '" role="option" id="bi-command-item-' + i + '" aria-selected="' + (i === activeIndex ? "true" : "false") + '" data-command-index="' + i + '">' +
        esc(r.label) +
        (r.action ? '<span class="bi-command__kbd">action</span>' : "") +
        "</div>";
    });
    listEl.innerHTML = html;
    input.setAttribute("aria-activedescendant", activeIndex >= 0 ? "bi-command-item-" + activeIndex : "");
    var active = listEl.querySelector(".is-active");
    if (active && active.scrollIntoView) active.scrollIntoView({ block: "nearest" });
  }

  function move(delta) {
    if (!visible.length) return;
    activeIndex = (activeIndex + delta + visible.length) % visible.length;
    paint();
  }

  function run(index) {
    var route = visible[index];
    if (!route) return;
    if (route.action === "toggle-scheme") {
      if (window.BIScheme) window.BIScheme.toggle();
      close();
      return;
    }
    if (route.url) {
      close();
      window.location.href = route.url;
    }
  }

  function open() {
    build();
    lastFocus = document.activeElement;
    overlay.removeAttribute("hidden");
    document.body.classList.add("bi-command-open");
    input.value = "";
    filter("");
    if (window.BIFocusTrap) {
      trap = window.BIFocusTrap.activate(overlay.querySelector(".bi-command__panel"), { onEscape: close });
    }
    input.focus();
  }

  function close() {
    if (!overlay || overlay.hasAttribute("hidden")) return;
    overlay.setAttribute("hidden", "");
    document.body.classList.remove("bi-command-open");
    if (window.BIFocusTrap && trap) {
      window.BIFocusTrap.release(trap);
      trap = null;
    }
    if (lastFocus && lastFocus.focus) lastFocus.focus();
  }

  function isOpen() {
    return overlay && !overlay.hasAttribute("hidden");
  }

  document.addEventListener("keydown", function (e) {
    if ((e.ctrlKey || e.metaKey) && String(e.key).toLowerCase() === "k") {
      e.preventDefault();
      if (isOpen()) { close(); } else { open(); }
    } else if (e.key === "Escape" && isOpen()) {
      e.preventDefault();
      close();
    }
  });

  // Optional trigger buttons, e.g. a search affordance in the header.
  document.addEventListener("click", function (e) {
    var trigger = e.target.closest("[data-bi-command-open]");
    if (trigger) {
      e.preventDefault();
      open();
    }
  });
})();
