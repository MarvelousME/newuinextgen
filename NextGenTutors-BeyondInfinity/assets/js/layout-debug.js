(function () {
  "use strict";

  if (typeof window.biLayoutDebug === "undefined" || !window.biLayoutDebug.enabled) {
    return;
  }

  var focusableSelector = [
    "a[href]",
    "button",
    "input",
    "select",
    "textarea",
    "[tabindex]:not([tabindex='-1'])"
  ].join(",");

  function isElementVisible(el) {
    var style = window.getComputedStyle(el);
    if (style.display === "none" || style.visibility === "hidden" || style.opacity === "0") {
      return false;
    }
    var rect = el.getBoundingClientRect();
    return rect.width > 0 && rect.height > 0;
  }

  function isOutOfViewport(el) {
    var rect = el.getBoundingClientRect();
    var vw = window.innerWidth || document.documentElement.clientWidth;
    var vh = window.innerHeight || document.documentElement.clientHeight;
    return rect.right < 0 || rect.bottom < 0 || rect.left > vw || rect.top > vh;
  }

  function markOverflowingElements() {
    var offenders = [];
    document.querySelectorAll("body *").forEach(function (el) {
      if (!(el instanceof HTMLElement)) {
        return;
      }
      var rect = el.getBoundingClientRect();
      if (rect.width > window.innerWidth + 2) {
        el.setAttribute("data-ng-overflow", "1");
        offenders.push(el);
      }
    });
    return offenders.length;
  }

  function markHiddenControls() {
    var offenders = [];
    document.querySelectorAll(focusableSelector).forEach(function (el) {
      if (!(el instanceof HTMLElement)) {
        return;
      }
      if (!isElementVisible(el) || isOutOfViewport(el)) {
        el.setAttribute("data-ng-hidden-control", "1");
        offenders.push(el);
      }
    });
    return offenders.length;
  }

  function ensurePanel() {
    var panel = document.getElementById("ng-layout-debug-panel");
    if (!panel) {
      panel = document.createElement("aside");
      panel.id = "ng-layout-debug-panel";
      panel.setAttribute("role", "status");
      panel.setAttribute("aria-live", "polite");
      document.body.appendChild(panel);
    }
    return panel;
  }

  function renderSummary(overflowCount, hiddenControlCount) {
    var panel = ensurePanel();
    panel.innerHTML =
      "<strong>Layout Debug</strong><br>" +
      "Layout: " + (window.biLayoutDebug.layoutMode || "unknown") + "<br>" +
      "Template: " + (window.biLayoutDebug.template || "unknown") + "<br>" +
      "Builder: " + (window.biLayoutDebug.builder || "none") + "<br>" +
      "Viewport: " + window.innerWidth + "x" + window.innerHeight + "<br>" +
      "Overflow elements: " + overflowCount + "<br>" +
      "Hidden controls: " + hiddenControlCount;
  }

  function runChecks() {
    document.querySelectorAll("[data-ng-overflow], [data-ng-hidden-control]").forEach(function (el) {
      el.removeAttribute("data-ng-overflow");
      el.removeAttribute("data-ng-hidden-control");
    });
    var overflowCount = markOverflowingElements();
    var hiddenControlCount = markHiddenControls();
    renderSummary(overflowCount, hiddenControlCount);
  }

  window.addEventListener("load", runChecks, { once: true });
  window.addEventListener("resize", runChecks);
})();

