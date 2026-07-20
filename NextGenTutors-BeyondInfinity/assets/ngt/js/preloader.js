/* ============================================================
   NextGen Tutors — Spiral Preloader (vanilla port)
   Shows on the HOME page, once per browser session.
   ============================================================ */
(function () {
  "use strict";

  // Cross-script "enter" signal so home.js can gate its hero entrance.
  window.NGT_ENTER = window.NGT_ENTER || {
    fired: false, cbs: [],
    onEnter(cb) { this.fired ? cb() : this.cbs.push(cb); },
    fire() { if (this.fired) return; this.fired = true; this.cbs.forEach((c) => c()); this.cbs = []; },
  };

  const isHome = (document.body.dataset.page || "home") === "home";
  const reduceMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
  const alreadyEntered = sessionStorage.getItem("ngt_entered") === "1";

  if (!isHome || alreadyEntered) {
    // No preloader needed — let the page proceed immediately.
    document.body.classList.remove("preloading");
    requestAnimationFrame(() => window.NGT_ENTER.fire());
    return;
  }

  document.body.classList.add("preloading");

  /* ---------------- Spiral animation engine ---------------- */
  function rng() { let seed = 1234; return () => { seed = (seed * 9301 + 49297) % 233280; return seed / 233280; }; }
  function vrand(min, max) { return min + Math.random() * (max - min); }

  class Star {
    constructor(cameraZ, travel) {
      this.angle = Math.random() * Math.PI * 2;
      this.distance = 30 * Math.random() + 15;
      this.rotationDirection = Math.random() > 0.5 ? 1 : -1;
      this.expansionRate = 1.2 + Math.random() * 0.8;
      this.finalScale = 0.7 + Math.random() * 0.6;
      this.dx = this.distance * Math.cos(this.angle);
      this.dy = this.distance * Math.sin(this.angle);
      this.spiralLocation = (1 - Math.pow(1 - Math.random(), 3.0)) / 1.3;
      this.z = vrand(0.5 * cameraZ, travel + cameraZ);
      const lerp = (a, b, t) => a * (1 - t) + b * t;
      this.z = lerp(this.z, travel / 2, 0.3 * this.spiralLocation);
      this.strokeWeightFactor = Math.pow(Math.random(), 2.0);
    }
    render(p, c) {
      const spiralPos = c.spiralPath(this.spiralLocation);
      const q = p - this.spiralLocation;
      if (q <= 0) return;
      const dp = c.constrain(4 * q, 0, 1);
      const elastic = c.easeOutElastic(dp);
      const powerE = Math.pow(dp, 2);
      let sx, sy;
      if (dp < 0.3) {
        const e = c.lerp(dp, powerE, dp / 0.3);
        sx = c.lerp(spiralPos.x, spiralPos.x + this.dx * 0.3, e / 0.3);
        sy = c.lerp(spiralPos.y, spiralPos.y + this.dy * 0.3, e / 0.3);
      } else if (dp < 0.7) {
        const mid = (dp - 0.3) / 0.4;
        const curve = Math.sin(mid * Math.PI) * this.rotationDirection * 1.5;
        const bx = spiralPos.x + this.dx * 0.3, by = spiralPos.y + this.dy * 0.3;
        const tx = spiralPos.x + this.dx * 0.7, ty = spiralPos.y + this.dy * 0.7;
        const px = -this.dy * 0.4 * curve, py = this.dx * 0.4 * curve;
        sx = c.lerp(bx, tx, mid) + px * mid;
        sy = c.lerp(by, ty, mid) + py * mid;
      } else {
        const fp = (dp - 0.7) / 0.3;
        const bx = spiralPos.x + this.dx * 0.7, by = spiralPos.y + this.dy * 0.7;
        const td = this.distance * this.expansionRate * 1.5;
        const sa = this.angle + 1.2 * this.rotationDirection * fp * Math.PI;
        const tx = spiralPos.x + td * Math.cos(sa), ty = spiralPos.y + td * Math.sin(sa);
        sx = c.lerp(bx, tx, fp); sy = c.lerp(by, ty, fp);
      }
      const vx = (this.z - c.cameraZ) * sx / c.viewZoom;
      const vy = (this.z - c.cameraZ) * sy / c.viewZoom;
      let sizeMul = 1.0;
      if (dp < 0.6) sizeMul = 1.0 + dp * 0.2;
      else { const t = (dp - 0.6) / 0.4; sizeMul = 1.2 * (1 - t) + this.finalScale * t; }
      const dotSize = 8.5 * this.strokeWeightFactor * sizeMul;
      c.showProjectedDot(vx, vy, this.z, dotSize);
    }
  }

  class Spiral {
    constructor(canvas, ctx, size) {
      this.canvas = canvas; this.ctx = ctx; this.size = size; this.time = 0;
      this.changeEventTime = 0.32; this.cameraZ = -400; this.cameraTravelDistance = 3400;
      this.startDotYOffset = 28; this.viewZoom = 100;
      this.numberOfStars = Math.min(2600, Math.floor((size * size) / 900));
      this.trailLength = 80; this.stars = [];
      const orig = Math.random; Math.random = rng();
      for (let i = 0; i < this.numberOfStars; i++) this.stars.push(new Star(this.cameraZ, this.cameraTravelDistance));
      Math.random = orig;
    }
    ease(p, g) { return p < 0.5 ? 0.5 * Math.pow(2 * p, g) : 1 - 0.5 * Math.pow(2 * (1 - p), g); }
    easeOutElastic(x) { const c4 = (2 * Math.PI) / 4.5; if (x <= 0) return 0; if (x >= 1) return 1; return Math.pow(2, -8 * x) * Math.sin((x * 8 - 0.75) * c4) + 1; }
    map(v, a, b, c, d) { return c + (d - c) * ((v - a) / (b - a)); }
    constrain(v, mn, mx) { return Math.min(Math.max(v, mn), mx); }
    lerp(a, b, t) { return a * (1 - t) + b * t; }
    spiralPath(p) {
      p = this.constrain(1.2 * p, 0, 1); p = this.ease(p, 1.8);
      const theta = 2 * Math.PI * 6 * Math.sqrt(p); const r = 170 * Math.sqrt(p);
      return { x: r * Math.cos(theta), y: r * Math.sin(theta) + this.startDotYOffset };
    }
    showProjectedDot(x3, y3, z3, sizeFactor) {
      const t2 = this.constrain(this.map(this.time, this.changeEventTime, 1, 0, 1), 0, 1);
      const newCamZ = this.cameraZ + this.ease(Math.pow(t2, 1.2), 1.8) * this.cameraTravelDistance;
      if (z3 > newCamZ) {
        const depth = z3 - newCamZ;
        const x = this.viewZoom * x3 / depth, y = this.viewZoom * y3 / depth;
        this.ctx.beginPath(); this.ctx.arc(x, y, 0.5 + 0.5 * (400 * sizeFactor / depth), 0, Math.PI * 2); this.ctx.fill();
      }
    }
    drawTrail(t1) {
      for (let i = 0; i < this.trailLength; i++) {
        const f = this.map(i, 0, this.trailLength, 1.1, 0.1);
        const sw = (1.3 * (1 - t1) + 3.0 * Math.sin(Math.PI * t1)) * f;
        const pos = this.spiralPath(t1 - 0.00015 * i);
        this.ctx.fillStyle = i % 5 === 0 ? "#aece61" : "#ffffff";
        this.ctx.beginPath(); this.ctx.arc(pos.x, pos.y, Math.max(0.4, sw / 2), 0, Math.PI * 2); this.ctx.fill();
      }
    }
    render() {
      const ctx = this.ctx; if (!ctx) return;
      ctx.fillStyle = "#061528"; ctx.fillRect(0, 0, this.size, this.size);
      ctx.save(); ctx.translate(this.size / 2, this.size / 2);
      const t1 = this.constrain(this.map(this.time, 0, this.changeEventTime + 0.25, 0, 1), 0, 1);
      const t2 = this.constrain(this.map(this.time, this.changeEventTime, 1, 0, 1), 0, 1);
      ctx.rotate(-Math.PI * this.ease(t2, 2.7));
      this.drawTrail(t1);
      ctx.fillStyle = "#fff";
      for (const s of this.stars) s.render(t1, this);
      if (this.time > this.changeEventTime) {
        const dy = this.cameraZ * this.startDotYOffset / this.viewZoom;
        this.showProjectedDot(0, dy, this.cameraTravelDistance, 2.5);
      }
      ctx.restore();
    }
  }

  /* ---------------- Build DOM ---------------- */
  const pre = document.createElement("div");
  pre.className = "preloader";
  pre.id = "preloader";
  pre.innerHTML = `
    <canvas id="spiral-canvas"></canvas>
    <div class="preloader__stage">
      <div class="pre-flash" id="pre-flash"></div>
      <div class="pre-logo-wrap" id="pre-logo-wrap">
        <img class="pre-logo" id="pre-logo" src="assets/img/logo-200.png" width="200" height="200" alt="NextGen Tutors" />
      </div>
    </div>
    <button class="pre-skip" id="pre-skip">Skip Intro →</button>`;
  document.body.appendChild(pre);

  const canvas = document.getElementById("spiral-canvas");
  const ctx = canvas.getContext("2d");
  let spiral = null, raf = null, tl = null, finished = false;

  function sizeCanvas() {
    const dpr = Math.min(window.devicePixelRatio || 1, 2);
    const vw = window.innerWidth, vh = window.innerHeight;
    const size = Math.max(vw, vh);
    canvas.width = size * dpr; canvas.height = size * dpr;
    canvas.style.width = size + "px"; canvas.style.height = size + "px";
    canvas.style.left = (vw - size) / 2 + "px";
    canvas.style.top = (vh - size) / 2 + "px";
    ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
    return size;
  }

  function finish() {
    if (finished) return;
    finished = true;
    sessionStorage.setItem("ngt_entered", "1");
    document.body.classList.remove("preloading");
    window.NGT_ENTER.fire(); // home hero animates in behind the fade
    const done = () => { if (tl) tl.kill(); if (raf) cancelAnimationFrame(raf); pre.remove(); };
    if (window.gsap && !reduceMotion) gsap.to(pre, { opacity: 0, duration: 0.6, ease: "power2.inOut", onComplete: done });
    else { pre.style.transition = "opacity .5s"; pre.style.opacity = "0"; setTimeout(done, 500); }
  }

  function start() {
    const size = sizeCanvas();
    spiral = new Spiral(canvas, ctx, size);
    const logo = "#pre-logo", flash = "#pre-flash";

    if (reduceMotion || !window.gsap) {
      spiral.time = 0.7; spiral.render();
      const el = document.getElementById("pre-logo");
      el.style.opacity = "1"; el.classList.add("is-glowing");
      setTimeout(finish, 1600);
      return;
    }

    // Master cinematic timeline:
    // spiral particles warp & flow out → logo blooms in → grows toward viewer → explodes → reveal home.
    tl = gsap.timeline({ onComplete: finish });
    tl.to(spiral, { time: 1, duration: 4.4, ease: "power1.inOut", onUpdate: () => spiral.render() }, 0);
    // logo blooms from the vanishing point as particles disperse
    tl.fromTo(logo, { opacity: 0, scale: 0.12, filter: "blur(16px)" },
      { opacity: 1, scale: 0.82, filter: "blur(0px)", duration: 1.4, ease: "power3.out",
        onStart: () => document.getElementById("pre-logo").classList.add("is-glowing") }, 2.5);
    // it advances toward the screen, growing steadily (accelerating)
    tl.to(logo, { scale: 1.45, duration: 1.25, ease: "power2.in" }, 4.05);
    // explosion: rushes straight at the viewer and bursts
    tl.to(logo, { scale: 7.5, opacity: 0, filter: "blur(22px)", duration: 0.55, ease: "power3.in" }, 5.15);
    tl.fromTo(flash, { opacity: 0, scale: 0.2 }, { opacity: 1, scale: 1.5, duration: 0.42, ease: "power2.out" }, 5.28);
    tl.to(flash, { opacity: 0, duration: 0.4, ease: "power2.in" }, 5.62);
  }

  function skip() {
    if (finished || !window.gsap) { finish(); return; }
    // fast-forward to the explosion so the user still gets the payoff
    if (tl) { tl.seek(4.0); tl.timeScale(3.2); }
    else finish();
  }

  let resizeT = null;
  window.addEventListener("resize", () => {
    if (!spiral) return;
    clearTimeout(resizeT);
    resizeT = setTimeout(() => { const size = sizeCanvas(); spiral.size = size; }, 150);
  });

  document.getElementById("pre-skip").addEventListener("click", skip);
  window.addEventListener("keydown", (e) => { if (e.key === "Enter" || e.key === "Escape") skip(); }, { once: true });

  if (document.readyState === "loading") document.addEventListener("DOMContentLoaded", start);
  else start();
})();
