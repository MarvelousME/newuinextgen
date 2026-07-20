/**
 * BeyondInfinity — 3D tutors carousel (no GSAP required).
 */
(function () {
  "use strict";

  var reduceMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;

  function initCarousel(root) {
    var stage = root.querySelector(".carousel-3d__stage");
    if (!stage) return;

    var cards = Array.prototype.slice.call(stage.querySelectorAll(".tutor-card3d"));
    var n = cards.length;
    if (n < 1) return;

    var nav = document.querySelector('[data-carousel-nav="' + root.id + '"]');
    var dots = nav ? Array.prototype.slice.call(nav.querySelectorAll(".cdot")) : [];
    var live = document.querySelector('[data-carousel-live="' + root.id + '"]');
    var current = 0;
    var startX = null;
    var dragging = false;

    function tutorLabel(card) {
      var name = card.querySelector(".tutor-card__name");
      return name ? name.textContent.trim() : "";
    }

    function announce() {
      if (!live) return;
      var label = tutorLabel(cards[current]);
      live.textContent = label
        ? "Showing tutor " + (current + 1) + " of " + n + ": " + label
        : "Showing tutor " + (current + 1) + " of " + n;
    }

    function render() {
      var spread = Math.min(380, Math.max(220, Math.floor(root.clientWidth * 0.2)));
      var cardTop = window.matchMedia("(max-width: 767px)").matches ? "62%" : "58%";
      cards.forEach(function (card, i) {
        var diff = i - current;
        if (diff > n / 2) diff -= n;
        if (diff < -n / 2) diff += n;
        var abs = Math.abs(diff);
        var tx = diff * spread;
        var tz = -abs * 120;
        var rot = -diff * 22;
        var scale = 1 - abs * 0.08;
        var opacity = abs > 3 ? 0 : 1 - abs * 0.18;
        card.style.top = cardTop;
        card.style.transform =
          "translateX(calc(-50% + " + tx + "px)) translateY(-50%) translateZ(" + tz + "px) rotateY(" + rot + "deg) scale(" + scale + ")";
        card.style.opacity = String(opacity);
        card.style.zIndex = String(100 - abs);
        card.style.pointerEvents = diff === 0 ? "auto" : "none";
      });
      dots.forEach(function (d, i) {
        d.classList.toggle("is-active", i === current);
        d.setAttribute("aria-selected", i === current ? "true" : "false");
      });
      announce();
    }

    function go(dir) {
      current = (current + dir + n) % n;
      render();
    }

    function goTo(i) {
      current = ((i % n) + n) % n;
      render();
    }

    if (nav) {
      nav.querySelectorAll("[data-dir]").forEach(function (btn) {
        btn.addEventListener("click", function () {
          go(parseInt(btn.getAttribute("data-dir"), 10));
        });
      });
      dots.forEach(function (d) {
        d.addEventListener("click", function () {
          goTo(parseInt(d.getAttribute("data-index"), 10));
        });
      });
    }

    function onPointerUp(clientX) {
      if (startX === null) return;
      var dx = clientX - startX;
      if (Math.abs(dx) > 60) go(dx < 0 ? 1 : -1);
      startX = null;
      dragging = false;
    }

    root.addEventListener("mousedown", function (e) {
      startX = e.clientX;
      dragging = true;
    });

    root.addEventListener("mouseup", function (e) {
      onPointerUp(e.clientX);
    });

    root.addEventListener(
      "touchstart",
      function (e) {
        startX = e.touches[0].clientX;
        dragging = true;
      },
      { passive: true }
    );

    root.addEventListener("touchend", function (e) {
      onPointerUp(e.changedTouches[0].clientX);
    });

    var timer;
    function startAuto() {
      if (reduceMotion) return;
      timer = setInterval(function () {
        go(1);
      }, 5000);
    }
    function stopAuto() {
      if (timer) clearInterval(timer);
    }
    root.addEventListener("mouseenter", stopAuto);
    root.addEventListener("mouseleave", function () {
      startAuto();
      dragging = false;
      startX = null;
    });
    startAuto();

    window.addEventListener("resize", function () {
      render();
    });

    if (!root.hasAttribute("tabindex")) {
      root.setAttribute("tabindex", "0");
    }
    root.addEventListener("keydown", function (e) {
      if (e.key === "ArrowLeft") {
        e.preventDefault();
        go(-1);
        stopAuto();
      } else if (e.key === "ArrowRight") {
        e.preventDefault();
        go(1);
        stopAuto();
      }
    });

    cards.forEach(function (card) {
      card.style.position = "absolute";
      card.style.top = "58%";
      card.style.left = "50%";
      card.style.transformStyle = "preserve-3d";
      card.style.transition = reduceMotion ? "none" : "transform 0.75s cubic-bezier(0.16,1,0.3,1), opacity 0.75s";
    });
    stage.style.transformStyle = "preserve-3d";
    render();
  }

  document.addEventListener("DOMContentLoaded", function () {
    document.querySelectorAll("[data-carousel]").forEach(initCarousel);
  });
})();
