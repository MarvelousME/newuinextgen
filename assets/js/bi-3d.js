/**
 * BeyondInfinity — 3D tilt, stacked cards, carousel stage parallax.
 */
(function () {
  "use strict";

  var cfg = window.bi3d || {};
  var reduceMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
  var coarsePointer = window.matchMedia("(pointer: coarse)").matches;
  var tiltMax = typeof cfg.tiltMax === "number" ? cfg.tiltMax : 10;

  function initTilt(el) {
    if (reduceMotion || coarsePointer || tiltMax <= 0) return;
    if (el.classList.contains("bi-tilt-3d--carousel") && el.style.pointerEvents === "none") return;

    var inner = el.querySelector(".bi-tilt-3d__inner") || el;
    var max = parseFloat(el.getAttribute("data-bi-tilt-max") || String(tiltMax), 10);
    var raf = 0;

    function onMove(e) {
      if (el.classList.contains("bi-tilt-3d--carousel") && el.style.pointerEvents === "none") return;
      cancelAnimationFrame(raf);
      raf = requestAnimationFrame(function () {
        var rect = el.getBoundingClientRect();
        if (!rect.width || !rect.height) return;
        var x = (e.clientX - rect.left) / rect.width;
        var y = (e.clientY - rect.top) / rect.height;
        var rotY = (x - 0.5) * max * 2;
        var rotX = (0.5 - y) * max * 2;

        el.style.setProperty("--bi-glare-x", String(x * 100) + "%");
        el.style.setProperty("--bi-glare-y", String(y * 100) + "%");
        inner.style.transform =
          "perspective(900px) rotateX(" + rotX + "deg) rotateY(" + rotY + "deg) translateZ(8px)";
      });
    }

    function reset() {
      cancelAnimationFrame(raf);
      inner.style.transform = "";
      el.style.removeProperty("--bi-glare-x");
      el.style.removeProperty("--bi-glare-y");
    }

    el.addEventListener("mousemove", onMove);
    el.addEventListener("mouseleave", reset);
    el.addEventListener("blur", reset, true);
  }

  function initStack(root) {
    var cards = Array.prototype.slice.call(root.querySelectorAll(".bi-stack-3d__card"));
    if (!cards.length) return;

    function activate(index) {
      cards.forEach(function (card, i) {
        card.classList.toggle("is-active", i === index);
        card.setAttribute("aria-pressed", i === index ? "true" : "false");
      });
    }

    cards.forEach(function (card, i) {
      card.setAttribute("role", "button");

      card.addEventListener("click", function () {
        activate(i);
        root.classList.add("is-expanded");
        root.dispatchEvent(new CustomEvent("bi-stack-select", { detail: { index: i }, bubbles: true }));
      });

      card.addEventListener("keydown", function (e) {
        if (e.key === "Enter" || e.key === " ") {
          e.preventDefault();
          activate(i);
          root.classList.add("is-expanded");
          root.dispatchEvent(new CustomEvent("bi-stack-select", { detail: { index: i }, bubbles: true }));
          return;
        }
        if (e.key === "ArrowRight" || e.key === "ArrowDown") {
          e.preventDefault();
          activate((i + 1) % cards.length);
        }
        if (e.key === "ArrowLeft" || e.key === "ArrowUp") {
          e.preventDefault();
          activate((i - 1 + cards.length) % cards.length);
        }
      });
    });

    root.addEventListener("mouseleave", function () {
      root.classList.remove("is-expanded");
    });
  }

  function syncPathwaysStack() {
    var stack = document.querySelector(".bi-stack-3d--pathways");
    var tablist = document.querySelector("#cursor-reveal [role=tablist]");
    if (!stack || !tablist) return;

    stack.addEventListener("bi-stack-select", function (e) {
      var tabs = Array.prototype.slice.call(tablist.querySelectorAll("[role=tab], .ngi-cursor-item"));
      var index = e.detail && typeof e.detail.index === "number" ? e.detail.index : 0;
      if (tabs[index]) tabs[index].click();
    });
  }

  function initCarouselStage(root) {
    if (reduceMotion) return;

    var stage = root.querySelector(".carousel-3d__stage");
    var glow = root.querySelector(".bi-carousel-3d__glow");
    if (!stage) return;

    var spinEnabled = root.hasAttribute("data-carousel-spin") && cfg.carouselSpin !== false;

    function onLeave() {
      stage.style.transform = "";
      if (glow) glow.classList.remove("is-active");
    }

    root.addEventListener("mouseenter", function () {
      if (glow) glow.classList.add("is-active");
    });

    root.addEventListener("mousemove", function (e) {
      if (reduceMotion) return;
      var rect = root.getBoundingClientRect();
      var x = (e.clientX - rect.left) / rect.width - 0.5;
      stage.style.transform = "rotateY(" + x * 4 + "deg)";
      if (spinEnabled && glow) glow.classList.add("is-active");
    });

    root.addEventListener("mouseleave", onLeave);
    root.addEventListener("blur", onLeave, true);
  }

  document.addEventListener("DOMContentLoaded", function () {
    if (!document.body.classList.contains("bi-3d-enabled")) return;

    document.querySelectorAll("[data-bi-tilt]").forEach(initTilt);
    document.querySelectorAll("[data-bi-stack-3d]").forEach(initStack);
    document.querySelectorAll("[data-carousel].bi-carousel-3d--kinetic").forEach(initCarouselStage);
    syncPathwaysStack();
  });
})();
