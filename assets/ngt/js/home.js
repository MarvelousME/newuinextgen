/* ============================================================
   NextGen Tutors — HOME page logic
   (chrome/nav/lenis/transitions live in chrome.js)
   ============================================================ */
(function () {
  "use strict";
  const NGT = window.NGT;
  const reduceMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
  const $ = (s, c) => (c || document).querySelector(s);
  const $$ = (s, c) => Array.from((c || document).querySelectorAll(s));

  /* ---------- Render ---------- */
  function renderMarquee() {
    const el = $("#marquee");
    if (!el) return;
    const make = (b) => `<span class="marq-pill"><span class="e">${b.e}</span>${b.t}</span>`;
    el.innerHTML = NGT.marquee.map(make).join("") + NGT.marquee.map(make).join("");
  }
  function renderSubjects() {
    const grid = $("#subj-grid");
    if (!grid) return;
    grid.innerHTML = NGT.subjects.map((s) => `
      <div class="flip" data-reveal>
        <div class="flip__inner">
          <div class="flip__face flip__front">
            <span class="flip__ico"><i data-lucide="${s.icon}"></i></span>
            <span class="flip__name">${s.name}</span>
            <span class="flip__hint">Schedules Active</span>
          </div>
          <div class="flip__face flip__back">
            <span class="flip__name">${s.name}</span>
            <p class="flip__back-desc">${s.desc}</p>
            <a class="flip__back-cta" href="find-a-tutor.html" data-internal>Find Tutors <i data-lucide="arrow-right"></i></a>
          </div>
        </div>
      </div>`).join("");
  }
  function tutorCardHTML(t) {
    const online = t.type === "online" || t.type === "both";
    const home = t.type === "personal" || t.type === "both";
    const star = `<svg viewBox="0 0 24 24"><polygon points="12 2 15 9 22 9.5 17 14.5 18.5 22 12 18 5.5 22 7 14.5 2 9.5 9 9"/></svg>`;
    return `
      <div class="tutor-card">
        <div class="tutor-card__photo">
          <img src="${t.img}" alt="${t.name}" referrerpolicy="no-referrer" />
          <div class="tutor-badges">
            ${online ? `<span class="tutor-badge tutor-badge--online"><i data-lucide="monitor"></i>Online</span>` : ""}
            ${home ? `<span class="tutor-badge tutor-badge--home"><i data-lucide="users"></i>In-Person</span>` : ""}
          </div>
          <div class="tutor-price"><span class="r">R</span><span class="n">${t.rate}</span><span class="h">/hr</span></div>
        </div>
        <div class="tutor-card__body">
          <div class="tutor-card__top">
            <h3 class="tutor-card__name">${t.name}</h3>
            <span class="tutor-rating">${star}${t.rating.toFixed(2)}</span>
          </div>
          <div class="tutor-card__degree"><i data-lucide="award"></i><span>${t.degree}</span></div>
          <p class="tutor-card__bio">“${t.bio}”</p>
          <div class="tutor-tags">${t.subjects.map((s) => `<span class="tutor-tag">${s}</span>`).join("")}</div>
          <div class="tutor-card__btns">
            <a class="btn" style="background:var(--slate-100);color:var(--navy);padding:11px" href="tutor-profile.html" data-internal>Bio</a>
            <a class="btn btn--lime" style="padding:11px" href="tutor-profile.html#calendar" data-internal>Book Class</a>
          </div>
        </div>
      </div>`;
  }
  function renderTestimonials() {
    const track = $("#ttrack"), dots = $("#t-dots");
    if (!track) return;
    const star = `<svg viewBox="0 0 24 24"><polygon points="12 2 15 9 22 9.5 17 14.5 18.5 22 12 18 5.5 22 7 14.5 2 9.5 9 9"/></svg>`;
    track.innerHTML = NGT.testimonials.map((t) => `
      <div class="tcard">
        <div class="tcard__inner">
          <div class="tcard__photo"><img src="${t.img}" alt="${t.author}" referrerpolicy="no-referrer" /></div>
          <div>
            <div class="tcard__quote-mark">“</div>
            <p class="tcard__quote">${t.quote}</p>
            <div class="tcard__author">${t.author}</div>
            <div class="tcard__role">${t.role}</div>
            <div class="tcard__stars">${star + star + star + star + star}</div>
          </div>
        </div>
      </div>`).join("");
    dots.innerHTML = NGT.testimonials.map((_, i) => `<button class="cdot${i === 0 ? " is-active" : ""}" data-i="${i}" aria-label="Testimonial ${i + 1}"></button>`).join("");
  }

  /* ---------- Counters ---------- */
  function animateCounter(el) {
    const target = +el.dataset.target;
    const fmt = (n) => (n >= 1000 ? Math.round(n).toLocaleString("en-US") : Math.round(n).toString());
    if (reduceMotion) { el.textContent = fmt(target); return; }
    const obj = { v: 0 };
    gsap.to(obj, { v: target, duration: 2, ease: "power2.out", onUpdate: () => (el.textContent = fmt(obj.v)) });
  }
  function initCounters() {
    $$(".counter").forEach((el) => ScrollTrigger.create({ trigger: el, start: "top 92%", once: true, onEnter: () => animateCounter(el) }));
  }

  /* ---------- Reveals ---------- */
  function initReveals() {
    if (reduceMotion) return;
    const batched = ".flip, #how .step, .stat-grid .stat-card, .price-grid .price-card";
    $$("[data-reveal]").forEach((el) => {
      if (el.matches(batched)) return;
      gsap.to(el, { opacity: 1, y: 0, duration: 0.9, ease: "power3.out", scrollTrigger: { trigger: el, start: "top 86%", once: true } });
    });
    $$("[data-reveal-x]").forEach((el) => gsap.to(el, { opacity: 1, x: 0, duration: 1, ease: "power3.out", scrollTrigger: { trigger: el, start: "top 84%", once: true } }));
    $$("[data-reveal-scale]").forEach((el) => gsap.to(el, { opacity: 1, scale: 1, duration: 1, ease: "power3.out", scrollTrigger: { trigger: el, start: "top 84%", once: true } }));
    ScrollTrigger.batch("#subj-grid .flip", { start: "top 90%", onEnter: (b) => gsap.to(b, { opacity: 1, y: 0, stagger: 0.07, duration: 0.7, ease: "power3.out", overwrite: true }) });
    ["#how .step", ".stat-grid .stat-card", ".price-grid .price-card"].forEach((sel) =>
      ScrollTrigger.batch(sel, { start: "top 88%", onEnter: (b) => gsap.to(b, { opacity: 1, y: 0, stagger: 0.1, duration: 0.8, ease: "power3.out", overwrite: true }) })
    );
  }

  /* ---------- Hero entrance (gated on Enter) ---------- */
  function playHero() {
    if (reduceMotion) { gsap.set("[data-hero]", { opacity: 1 }); return; }
    const words = $$("#hero-title .word");
    const tl = gsap.timeline();
    tl.from(words, { yPercent: 115, opacity: 0, rotateX: -40, stagger: 0.08, duration: 0.9, ease: "power4.out" })
      .from("[data-hero='1']", { y: 20, opacity: 0, duration: 0.6 }, 0.1)
      .from("[data-hero='2']", { y: 24, opacity: 0, duration: 0.7 }, "-=0.4")
      .from("[data-hero='3']", { y: 24, opacity: 0, duration: 0.7 }, "-=0.45")
      .from("[data-hero='4']", { y: 24, opacity: 0, duration: 0.7 }, "-=0.5")
      .from(".hero__visual", { x: 60, opacity: 0, duration: 1.1, ease: "power3.out" }, "-=0.9")
      .from(".float-card", { scale: 0.6, opacity: 0, stagger: 0.15, duration: 0.7, ease: "back.out(1.7)" }, "-=0.6");
  }

  /* ---------- Parallax + tilt ---------- */
  function initParallax() {
    if (reduceMotion) return;
    $$("[data-parallax]").forEach((el) => {
      const speed = parseFloat(el.dataset.parallax);
      gsap.to(el, { y: () => -(window.innerHeight * speed), ease: "none", scrollTrigger: { trigger: "#hero", start: "top top", end: "bottom top", scrub: 1.2 } });
    });
  }
  function initTilt() {
    if (reduceMotion || window.matchMedia("(hover: none)").matches) return;
    const card = $("#hero-tilt"), hero = $("#hero");
    if (!card) return;
    const qx = gsap.quickTo(card, "rotationY", { duration: 0.6, ease: "power3" });
    const qy = gsap.quickTo(card, "rotationX", { duration: 0.6, ease: "power3" });
    gsap.set(card, { transformPerspective: 1000, transformOrigin: "center" });
    hero.addEventListener("mousemove", (e) => {
      const r = hero.getBoundingClientRect();
      qx(((e.clientX - r.left) / r.width - 0.5) * 14);
      qy(-((e.clientY - r.top) / r.height - 0.5) * 12);
    });
    hero.addEventListener("mouseleave", () => { qx(0); qy(0); });
  }
  function attachCardTilt(scope) {
    if (reduceMotion || window.matchMedia("(hover: none)").matches) return;
    $$(".tutor-card", scope).forEach((card) => {
      const set = gsap.quickSetter(card, "css");
      card.addEventListener("mousemove", (e) => {
        const r = card.getBoundingClientRect();
        set({ transform: `perspective(900px) rotateY(${((e.clientX - r.left) / r.width - 0.5) * 8}deg) rotateX(${-((e.clientY - r.top) / r.height - 0.5) * 8}deg) translateZ(10px)` });
      });
      card.addEventListener("mouseleave", () => set({ transform: "perspective(900px) rotateY(0) rotateX(0) translateZ(0)" }));
    });
  }

  /* ---------- 3D tutor carousel ---------- */
  function initTutorCarousel() {
    const stage = $("#tutor-stage"), dotsWrap = $("#tutor-dots");
    if (!stage) return;
    const data = NGT.tutors, n = data.length;
    let current = 0;
    stage.innerHTML = data.map((t) => `<div class="tutor-card3d">${tutorCardHTML(t)}</div>`).join("");
    const cards = $$(".tutor-card3d", stage);
    dotsWrap.innerHTML = data.map((_, i) => `<button class="cdot${i === 0 ? " is-active" : ""}" data-i="${i}" aria-label="Tutor ${i + 1}"></button>`).join("");
    const dots = $$(".cdot", dotsWrap);
    function render() {
      cards.forEach((card, i) => {
        let diff = i - current;
        if (diff > n / 2) diff -= n;
        if (diff < -n / 2) diff += n;
        const abs = Math.abs(diff);
        gsap.to(card, { x: diff * 300, z: -abs * 130, rotationY: -diff * 26, scale: 1 - abs * 0.08, opacity: abs > 2 ? 0 : 1 - abs * 0.18, duration: 0.8, ease: "power3.out", zIndex: 100 - abs });
        card.style.pointerEvents = diff === 0 ? "auto" : "none";
      });
      dots.forEach((d, i) => d.classList.toggle("is-active", i === current));
    }
    const go = (dir) => { current = (current + dir + n) % n; render(); };
    const goTo = (i) => { current = i; render(); };
    $("#tutor-next").addEventListener("click", () => go(1));
    $("#tutor-prev").addEventListener("click", () => go(-1));
    dots.forEach((d) => d.addEventListener("click", () => goTo(+d.dataset.i)));
    let startX = null;
    const carousel = $("#tutor-carousel");
    carousel.addEventListener("mousedown", (e) => (startX = e.clientX));
    window.addEventListener("mouseup", (e) => { if (startX !== null) { const dx = e.clientX - startX; if (Math.abs(dx) > 60) go(dx < 0 ? 1 : -1); startX = null; } });
    carousel.addEventListener("touchstart", (e) => (startX = e.touches[0].clientX), { passive: true });
    carousel.addEventListener("touchend", (e) => { if (startX !== null) { const dx = e.changedTouches[0].clientX - startX; if (Math.abs(dx) > 60) go(dx < 0 ? 1 : -1); startX = null; } });
    let timer = setInterval(() => go(1), 4500);
    carousel.addEventListener("mouseenter", () => clearInterval(timer));
    carousel.addEventListener("mouseleave", () => (timer = setInterval(() => go(1), 4500)));
    gsap.set(cards, { transformStyle: "preserve-3d" });
    render();
    attachCardTilt(stage);
  }

  /* ---------- Testimonial slider ---------- */
  function initTestimonialSlider() {
    const track = $("#ttrack");
    if (!track) return;
    const dots = $$("#t-dots .cdot"), n = NGT.testimonials.length;
    let i = 0;
    const go = (idx) => { i = (idx + n) % n; track.style.transform = `translateX(-${i * 100}%)`; dots.forEach((d, k) => d.classList.toggle("is-active", k === i)); };
    $("#t-next").addEventListener("click", () => go(i + 1));
    $("#t-prev").addEventListener("click", () => go(i - 1));
    dots.forEach((d) => d.addEventListener("click", () => go(+d.dataset.i)));
    let timer = setInterval(() => go(i + 1), 6000);
    track.addEventListener("mouseenter", () => clearInterval(timer));
    track.addEventListener("mouseleave", () => (timer = setInterval(() => go(i + 1), 6000)));
  }

  /* ---------- Hero particle canvas ---------- */
  function initCanvas() {
    const canvas = $("#hero-canvas");
    if (!canvas || reduceMotion) return;
    const ctx = canvas.getContext("2d");
    let w, h, dots, raf;
    function resize() {
      w = canvas.width = canvas.offsetWidth * devicePixelRatio;
      h = canvas.height = canvas.offsetHeight * devicePixelRatio;
      const count = Math.min(70, Math.floor((canvas.offsetWidth * canvas.offsetHeight) / 16000));
      dots = Array.from({ length: count }, () => ({ x: Math.random() * w, y: Math.random() * h, vx: (Math.random() - 0.5) * 0.25 * devicePixelRatio, vy: (Math.random() - 0.5) * 0.25 * devicePixelRatio, r: (Math.random() * 1.6 + 0.6) * devicePixelRatio }));
    }
    function draw() {
      ctx.clearRect(0, 0, w, h);
      for (let a = 0; a < dots.length; a++) {
        const d = dots[a];
        d.x += d.vx; d.y += d.vy;
        if (d.x < 0 || d.x > w) d.vx *= -1;
        if (d.y < 0 || d.y > h) d.vy *= -1;
        ctx.beginPath(); ctx.arc(d.x, d.y, d.r, 0, Math.PI * 2); ctx.fillStyle = "rgba(174,206,97,0.55)"; ctx.fill();
        for (let b = a + 1; b < dots.length; b++) {
          const e = dots[b], dist = Math.hypot(d.x - e.x, d.y - e.y), max = 130 * devicePixelRatio;
          if (dist < max) { ctx.beginPath(); ctx.moveTo(d.x, d.y); ctx.lineTo(e.x, e.y); ctx.strokeStyle = `rgba(174,206,97,${0.16 * (1 - dist / max)})`; ctx.lineWidth = devicePixelRatio; ctx.stroke(); }
        }
      }
      raf = requestAnimationFrame(draw);
    }
    resize();
    window.addEventListener("resize", () => { cancelAnimationFrame(raf); resize(); draw(); });
    draw();
  }

  /* ---------- Search ---------- */
  function initSearch() {
    const sel = $("#search-subject");
    if (sel) NGT.subjects.forEach((s) => { const o = document.createElement("option"); o.textContent = s.name; sel.appendChild(o); });
    const form = $("#hero-search");
    if (form) form.addEventListener("submit", (e) => { e.preventDefault(); if (window.ngtScrollTo) window.ngtScrollTo("#tutors"); });
  }

  /* ---------- Boot ---------- */
  function boot() {
    renderMarquee();
    renderSubjects();
    renderTestimonials();
    initSearch();
    if (window.lucide) lucide.createIcons();

    if (window.gsap && window.ScrollTrigger) gsap.registerPlugin(ScrollTrigger);
    initTutorCarousel();
    initTestimonialSlider();
    initCanvas();
    if (window.lucide) lucide.createIcons();

    document.body.classList.remove("preload");
    if (window.gsap && window.ScrollTrigger) {
      initReveals();
      initCounters();
      initParallax();
      initTilt();
      ScrollTrigger.refresh();
      // Hero entrance waits for the preloader "Enter".
      window.NGT_ENTER.onEnter(() => { playHero(); ScrollTrigger.refresh(); });
    } else {
      $$("[data-reveal],[data-reveal-x],[data-reveal-scale],[data-hero]").forEach((el) => { el.style.opacity = "1"; el.style.transform = "none"; });
    }
    window.addEventListener("load", () => { if (window.ScrollTrigger) ScrollTrigger.refresh(); });
  }

  if (document.readyState === "loading") document.addEventListener("DOMContentLoaded", boot);
  else boot();
})();
