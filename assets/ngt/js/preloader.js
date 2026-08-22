/* ============================================================
   NextGen Tutors — Spiral page intro (vanilla + GSAP)
   Auto-plays once on home; logo click replays via NGT_playIntro().
   ============================================================ */
(function () {
  "use strict";

  window.NGT_ENTER = window.NGT_ENTER || {
    fired: false,
    cbs: [],
    onEnter: function (cb) {
      this.fired ? cb() : this.cbs.push(cb);
    },
    fire: function () {
      if (this.fired) return;
      this.fired = true;
      this.cbs.forEach(function (c) {
        c();
      });
      this.cbs = [];
    },
  };

  var cfg = window.NGT_WP || {};
  var reduceMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
  var playing = false;
  var active = null;

  function logoSrc() {
    if (cfg.logo200Url) return cfg.logo200Url;
    if (cfg.imgUrl) return String(cfg.imgUrl).replace(/\/?$/, "/") + "logo-200.png";
    return (cfg.assetsUrl || "").replace(/\/?$/, "/") + "img/logo-200.png";
  }

  function rng() {
    var seed = 1234;
    return function () {
      seed = (seed * 9301 + 49297) % 233280;
      return seed / 233280;
    };
  }
  function vrand(min, max) {
    return min + Math.random() * (max - min);
  }

  function Star(cameraZ, travel) {
    this.angle = Math.random() * Math.PI * 2;
    this.distance = 30 * Math.random() + 15;
    this.rotationDirection = Math.random() > 0.5 ? 1 : -1;
    this.expansionRate = 1.2 + Math.random() * 0.8;
    this.finalScale = 0.7 + Math.random() * 0.6;
    this.dx = this.distance * Math.cos(this.angle);
    this.dy = this.distance * Math.sin(this.angle);
    this.spiralLocation = (1 - Math.pow(1 - Math.random(), 3.0)) / 1.3;
    this.z = vrand(0.5 * cameraZ, travel + cameraZ);
    var lerp = function (a, b, t) {
      return a * (1 - t) + b * t;
    };
    this.z = lerp(this.z, travel / 2, 0.3 * this.spiralLocation);
    this.strokeWeightFactor = Math.pow(Math.random(), 2.0);
  }
  Star.prototype.render = function (p, c) {
    var spiralPos = c.spiralPath(this.spiralLocation);
    var q = p - this.spiralLocation;
    if (q <= 0) return;
    var dp = c.constrain(4 * q, 0, 1);
    var powerE = Math.pow(dp, 2);
    var sx, sy;
    if (dp < 0.3) {
      var e = c.lerp(dp, powerE, dp / 0.3);
      sx = c.lerp(spiralPos.x, spiralPos.x + this.dx * 0.3, e / 0.3);
      sy = c.lerp(spiralPos.y, spiralPos.y + this.dy * 0.3, e / 0.3);
    } else if (dp < 0.7) {
      var mid = (dp - 0.3) / 0.4;
      var curve = Math.sin(mid * Math.PI) * this.rotationDirection * 1.5;
      var bx = spiralPos.x + this.dx * 0.3,
        by = spiralPos.y + this.dy * 0.3;
      var tx = spiralPos.x + this.dx * 0.7,
        ty = spiralPos.y + this.dy * 0.7;
      var px = -this.dy * 0.4 * curve,
        py = this.dx * 0.4 * curve;
      sx = c.lerp(bx, tx, mid) + px * mid;
      sy = c.lerp(by, ty, mid) + py * mid;
    } else {
      var fp = (dp - 0.7) / 0.3;
      var bx2 = spiralPos.x + this.dx * 0.7,
        by2 = spiralPos.y + this.dy * 0.7;
      var td = this.distance * this.expansionRate * 1.5;
      var sa = this.angle + 1.2 * this.rotationDirection * fp * Math.PI;
      var tx2 = spiralPos.x + td * Math.cos(sa),
        ty2 = spiralPos.y + td * Math.sin(sa);
      sx = c.lerp(bx2, tx2, fp);
      sy = c.lerp(by2, ty2, fp);
    }
    var vx = ((this.z - c.cameraZ) * sx) / c.viewZoom;
    var vy = ((this.z - c.cameraZ) * sy) / c.viewZoom;
    var sizeMul = 1.0;
    if (dp < 0.6) sizeMul = 1.0 + dp * 0.2;
    else {
      var t = (dp - 0.6) / 0.4;
      sizeMul = 1.2 * (1 - t) + this.finalScale * t;
    }
    var dotSize = 8.5 * this.strokeWeightFactor * sizeMul;
    c.showProjectedDot(vx, vy, this.z, dotSize);
  };

  function Spiral(canvas, ctx, size) {
    this.canvas = canvas;
    this.ctx = ctx;
    this.size = size;
    this.time = 0;
    this.changeEventTime = 0.32;
    this.cameraZ = -400;
    this.cameraTravelDistance = 3400;
    this.startDotYOffset = 28;
    this.viewZoom = 100;
    this.numberOfStars = Math.min(2600, Math.floor((size * size) / 900));
    this.trailLength = 80;
    this.stars = [];
    var orig = Math.random;
    Math.random = rng();
    for (var i = 0; i < this.numberOfStars; i++) {
      this.stars.push(new Star(this.cameraZ, this.cameraTravelDistance));
    }
    Math.random = orig;
  }
  Spiral.prototype.ease = function (p, g) {
    return p < 0.5 ? 0.5 * Math.pow(2 * p, g) : 1 - 0.5 * Math.pow(2 * (1 - p), g);
  };
  Spiral.prototype.easeOutElastic = function (x) {
    var c4 = (2 * Math.PI) / 4.5;
    if (x <= 0) return 0;
    if (x >= 1) return 1;
    return Math.pow(2, -8 * x) * Math.sin((x * 8 - 0.75) * c4) + 1;
  };
  Spiral.prototype.map = function (v, a, b, c, d) {
    return c + (d - c) * ((v - a) / (b - a));
  };
  Spiral.prototype.constrain = function (v, mn, mx) {
    return Math.min(Math.max(v, mn), mx);
  };
  Spiral.prototype.lerp = function (a, b, t) {
    return a * (1 - t) + b * t;
  };
  Spiral.prototype.spiralPath = function (p) {
    p = this.constrain(1.2 * p, 0, 1);
    p = this.ease(p, 1.8);
    var theta = 2 * Math.PI * 6 * Math.sqrt(p);
    var r = 170 * Math.sqrt(p);
    return { x: r * Math.cos(theta), y: r * Math.sin(theta) + this.startDotYOffset };
  };
  Spiral.prototype.showProjectedDot = function (x3, y3, z3, sizeFactor) {
    var t2 = this.constrain(this.map(this.time, this.changeEventTime, 1, 0, 1), 0, 1);
    var newCamZ = this.cameraZ + this.ease(Math.pow(t2, 1.2), 1.8) * this.cameraTravelDistance;
    if (z3 > newCamZ) {
      var depth = z3 - newCamZ;
      var x = (this.viewZoom * x3) / depth,
        y = (this.viewZoom * y3) / depth;
      this.ctx.beginPath();
      this.ctx.arc(x, y, 0.5 + 0.5 * ((400 * sizeFactor) / depth), 0, Math.PI * 2);
      this.ctx.fill();
    }
  };
  Spiral.prototype.drawTrail = function (t1) {
    for (var i = 0; i < this.trailLength; i++) {
      var f = this.map(i, 0, this.trailLength, 1.1, 0.1);
      var sw = (1.3 * (1 - t1) + 3.0 * Math.sin(Math.PI * t1)) * f;
      var pos = this.spiralPath(t1 - 0.00015 * i);
      this.ctx.fillStyle = i % 5 === 0 ? "#aece61" : "#ffffff";
      this.ctx.beginPath();
      this.ctx.arc(pos.x, pos.y, Math.max(0.4, sw / 2), 0, Math.PI * 2);
      this.ctx.fill();
    }
  };
  Spiral.prototype.render = function () {
    var ctx = this.ctx;
    if (!ctx) return;
    ctx.fillStyle = "#061528";
    ctx.fillRect(0, 0, this.size, this.size);
    ctx.save();
    ctx.translate(this.size / 2, this.size / 2);
    var t1 = this.constrain(this.map(this.time, 0, this.changeEventTime + 0.25, 0, 1), 0, 1);
    var t2 = this.constrain(this.map(this.time, this.changeEventTime, 1, 0, 1), 0, 1);
    ctx.rotate(-Math.PI * this.ease(t2, 2.7));
    this.drawTrail(t1);
    ctx.fillStyle = "#fff";
    for (var i = 0; i < this.stars.length; i++) this.stars[i].render(t1, this);
    if (this.time > this.changeEventTime) {
      var dy = (this.cameraZ * this.startDotYOffset) / this.viewZoom;
      this.showProjectedDot(0, dy, this.cameraTravelDistance, 2.5);
    }
    ctx.restore();
  };

  function destroyActive() {
    if (!active) return;
    if (active.tl && active.tl.kill) active.tl.kill();
    if (active.raf) cancelAnimationFrame(active.raf);
    if (active.onKey) window.removeEventListener("keydown", active.onKey);
    if (active.onResize) window.removeEventListener("resize", active.onResize);
    if (active.pre && active.pre.parentNode) active.pre.parentNode.removeChild(active.pre);
    active = null;
    playing = false;
    document.body.classList.remove("preloading");
  }

  /**
   * Play the cinematic spiral intro.
   * @param {{ onComplete?: Function, markEntered?: boolean }} opts
   * @returns {boolean} false if already playing or reduced motion skip path handled
   */
  function playIntro(opts) {
    opts = opts || {};
    if (playing) return false;

    if (reduceMotion) {
      document.body.classList.remove("preloading");
      window.NGT_ENTER.fire();
      if (typeof opts.onComplete === "function") opts.onComplete();
      return true;
    }

    if (active) {
      if (active.tl && active.tl.kill) active.tl.kill();
      if (active.raf) cancelAnimationFrame(active.raf);
      if (active.onKey) window.removeEventListener("keydown", active.onKey);
      if (active.onResize) window.removeEventListener("resize", active.onResize);
      if (active.pre && active.pre.parentNode) active.pre.parentNode.removeChild(active.pre);
      active = null;
    }

    playing = true;
    document.body.classList.add("preloading");

    var pre = document.createElement("div");
    pre.className = "preloader";
    pre.id = "preloader";
    pre.setAttribute("role", "dialog");
    pre.setAttribute("aria-label", "NextGen Tutors introduction");
    pre.innerHTML =
      '<canvas id="spiral-canvas"></canvas>' +
      '<div class="preloader__stage">' +
      '<div class="pre-flash" id="pre-flash"></div>' +
      '<div class="pre-logo-wrap" id="pre-logo-wrap">' +
      '<img class="pre-logo" id="pre-logo" src="' +
      logoSrc().replace(/"/g, "&quot;") +
      '" width="200" height="200" alt="NextGen Tutors" />' +
      "</div></div>" +
      '<button type="button" class="pre-skip" id="pre-skip">Skip Intro →</button>';
    document.body.appendChild(pre);

    var canvas = pre.querySelector("#spiral-canvas");
    var ctx = canvas.getContext("2d");
    var spiral = null;
    var tl = null;
    var raf = null;
    var finished = false;
    var resizeT = null;

    function sizeCanvas() {
      var dpr = Math.min(window.devicePixelRatio || 1, 2);
      var vw = window.innerWidth,
        vh = window.innerHeight;
      var size = Math.max(vw, vh);
      canvas.width = size * dpr;
      canvas.height = size * dpr;
      canvas.style.width = size + "px";
      canvas.style.height = size + "px";
      canvas.style.left = (vw - size) / 2 + "px";
      canvas.style.top = (vh - size) / 2 + "px";
      ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
      return size;
    }

    function finish() {
      if (finished) return;
      finished = true;
      if (opts.markEntered !== false) {
        try {
          sessionStorage.setItem("ngt_entered", "1");
        } catch (e) {}
      }
      document.body.classList.remove("preloading");
      window.NGT_ENTER.fire();
      var done = function () {
        destroyActive();
        if (typeof opts.onComplete === "function") opts.onComplete();
      };
      if (window.gsap) {
        window.gsap.to(pre, {
          opacity: 0,
          duration: 0.6,
          ease: "power2.inOut",
          onComplete: done,
        });
      } else {
        pre.style.transition = "opacity .5s";
        pre.style.opacity = "0";
        setTimeout(done, 500);
      }
    }

    function skip() {
      if (finished) return;
      if (tl && window.gsap) {
        tl.seek(4.0);
        tl.timeScale(3.2);
        return;
      }
      finish();
    }

    function start() {
      var size = sizeCanvas();
      spiral = new Spiral(canvas, ctx, size);
      var logo = "#pre-logo";
      var flash = "#pre-flash";

      if (!window.gsap) {
        spiral.time = 0.7;
        spiral.render();
        var el = pre.querySelector("#pre-logo");
        if (el) {
          el.style.opacity = "1";
          el.classList.add("is-glowing");
        }
        setTimeout(finish, 1600);
        return;
      }

      tl = window.gsap.timeline({ onComplete: finish });
      tl.to(
        spiral,
        {
          time: 1,
          duration: 4.4,
          ease: "power1.inOut",
          onUpdate: function () {
            spiral.render();
          },
        },
        0
      );
      tl.fromTo(
        logo,
        { opacity: 0, scale: 0.12, filter: "blur(16px)" },
        {
          opacity: 1,
          scale: 0.82,
          filter: "blur(0px)",
          duration: 1.4,
          ease: "power3.out",
          onStart: function () {
            var img = pre.querySelector("#pre-logo");
            if (img) img.classList.add("is-glowing");
          },
        },
        2.5
      );
      tl.to(logo, { scale: 1.45, duration: 1.25, ease: "power2.in" }, 4.05);
      tl.to(logo, { scale: 7.5, opacity: 0, filter: "blur(22px)", duration: 0.55, ease: "power3.in" }, 5.15);
      tl.fromTo(flash, { opacity: 0, scale: 0.2 }, { opacity: 1, scale: 1.5, duration: 0.42, ease: "power2.out" }, 5.28);
      tl.to(flash, { opacity: 0, duration: 0.4, ease: "power2.in" }, 5.62);
    }

    var onKey = function (e) {
      if (e.key === "Enter" || e.key === "Escape") skip();
    };
    var onResize = function () {
      if (!spiral) return;
      clearTimeout(resizeT);
      resizeT = setTimeout(function () {
        var size = sizeCanvas();
        spiral.size = size;
      }, 150);
    };

    pre.querySelector("#pre-skip").addEventListener("click", skip);
    window.addEventListener("keydown", onKey);
    window.addEventListener("resize", onResize);

    active = { pre: pre, tl: null, raf: raf, onKey: onKey, onResize: onResize };
    start();
    // Re-bind timeline created inside start().
    active.tl = tl;

    return true;
  }

  window.NGT_playIntro = playIntro;
  window.NGT_isIntroPlaying = function () {
    return playing;
  };

  function autoStart() {
    var isHome = (document.body.dataset.page || "") === "home" || document.body.classList.contains("home");
    var alreadyEntered = false;
    try {
      alreadyEntered = sessionStorage.getItem("ngt_entered") === "1";
    } catch (e) {}

    if (!isHome || alreadyEntered) {
      document.body.classList.remove("preloading");
      requestAnimationFrame(function () {
        window.NGT_ENTER.fire();
      });
      return;
    }

    playIntro({ markEntered: true });
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", autoStart);
  } else {
    autoStart();
  }
})();
