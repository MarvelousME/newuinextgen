/**
 * Dark-mode toggle — swaps html[data-bi-scheme] between the configured
 * scheme and "dark", persisting the choice in localStorage.
 * The token overrides live in assets/css/tokens/unified.css.
 */
(function () {
  "use strict";

  var html = document.documentElement;

  function baseScheme() {
    return window.__biBaseScheme || "default";
  }

  function isDark() {
    return html.getAttribute("data-bi-scheme") === "dark";
  }

  function apply(dark) {
    html.setAttribute("data-bi-scheme", dark ? "dark" : baseScheme());
    try {
      if (dark) {
        localStorage.setItem("bi-scheme", "dark");
      } else {
        localStorage.removeItem("bi-scheme");
      }
    } catch (e) {}
    document.querySelectorAll("[data-bi-scheme-toggle]").forEach(function (btn) {
      btn.setAttribute("aria-pressed", dark ? "true" : "false");
    });
    document.dispatchEvent(new CustomEvent("bi:scheme-change", { detail: { dark: dark } }));
  }

  function init() {
    document.querySelectorAll("[data-bi-scheme-toggle]").forEach(function (btn) {
      btn.setAttribute("aria-pressed", isDark() ? "true" : "false");
      btn.addEventListener("click", function () {
        apply(!isDark());
      });
    });
  }

  // Public hook so the command palette (and others) can toggle the scheme.
  window.BIScheme = { toggle: function () { apply(!isDark()); }, isDark: isDark };

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", init);
  } else {
    init();
  }
})();
