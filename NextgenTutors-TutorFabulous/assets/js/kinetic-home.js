(function () {
  const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  const root = document.querySelector('.ngi-home');
  if (!root) return;

  const revealEls = root.querySelectorAll('.ngi-reveal');
  if (reduced) {
    revealEls.forEach((el) => el.classList.add('is-in'));
  }

  const counterSeen = new WeakSet();
  function animateCounter(el) {
    if (counterSeen.has(el)) return;
    counterSeen.add(el);
    const end = Number(el.dataset.count || 0);
    const suffix = el.dataset.suffix || '+';
    const startTime = performance.now();
    const duration = 1250;
    function tick(now) {
      const p = Math.min((now - startTime) / duration, 1);
      const eased = 1 - Math.pow(1 - p, 4);
      el.textContent = Math.round(end * eased).toLocaleString() + suffix;
      if (p < 1) requestAnimationFrame(tick);
    }
    requestAnimationFrame(tick);
  }

  const io = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          entry.target.classList.add('is-in');
          entry.target.querySelectorAll?.('[data-count]').forEach(animateCounter);
        }
      });
    },
    { threshold: 0.16 }
  );
  revealEls.forEach((el) => io.observe(el));
  root.querySelectorAll('.ngi-stats').forEach((el) => io.observe(el));

  function setBars(score, homework) {
    const cb = root.querySelector('#ngiCourseBar');
    const hb = root.querySelector('#ngiHomeworkBar');
    if (cb) cb.style.width = score + '%';
    if (hb) hb.style.width = homework + '%';
  }
  setTimeout(() => setBars(82, 76), 250);

  root.querySelectorAll('.ngi-chip').forEach((chip) => {
    chip.addEventListener('click', () => {
      root.querySelectorAll('.ngi-chip').forEach((c) => {
        c.classList.remove('is-active');
        c.setAttribute('aria-pressed', 'false');
      });
      chip.classList.add('is-active');
      chip.setAttribute('aria-pressed', 'true');
      const course = chip.dataset.course;
      const score = chip.dataset.score;
      const homework = chip.dataset.homework;
      root.querySelector('#ngiCourseName').textContent = course;
      root.querySelector('#ngiCourseScore').textContent = score + '%';
      root.querySelector('#ngiHomeworkScore').textContent = homework + '%';
      setBars(0, 0);
      setTimeout(() => setBars(score, homework), 80);
    });
  });

  root.querySelectorAll('.ngi-tab').forEach((tab) => {
    tab.addEventListener('click', () => {
      root.querySelectorAll('.ngi-tab').forEach((t) => {
        t.classList.remove('is-active');
        t.setAttribute('aria-selected', 'false');
      });
      tab.classList.add('is-active');
      tab.setAttribute('aria-selected', 'true');
      const panel = root.querySelector('.ngi-subject-panel');
      panel.style.opacity = 0;
      panel.style.transform = 'translateY(12px)';
      setTimeout(() => {
        root.querySelector('#ngiSubjectTitle').textContent = tab.dataset.title;
        root.querySelector('#ngiSubjectBody').textContent = tab.dataset.body;
        const bullets = (tab.dataset.bullets || '').split('|').filter(Boolean);
        root.querySelector('#ngiSubjectBullets').innerHTML = bullets
          .map(
            (b) =>
              '<div class="ngi-bullet">' +
              b.replace(/[<>&]/g, (s) => ({ '<': '&lt;', '>': '&gt;', '&': '&amp;' }[s])) +
              '</div>'
          )
          .join('');
        panel.style.transition = '.25s';
        panel.style.opacity = 1;
        panel.style.transform = 'translateY(0)';
      }, 160);
    });
  });

  root.querySelectorAll('.ngi-faq-q').forEach((btn) => {
    btn.addEventListener('click', () => {
      const answer = btn.parentElement.querySelector('.ngi-faq-a');
      const isOpen = btn.getAttribute('aria-expanded') === 'true';
      root.querySelectorAll('.ngi-faq-a').forEach((a) => {
        a.style.maxHeight = null;
      });
      root.querySelectorAll('.ngi-faq-q').forEach((b) => {
        b.setAttribute('aria-expanded', 'false');
        const icon = b.querySelector('.ngi-faq-toggle');
        if (icon) icon.innerHTML = '<svg class="ngi-icon-svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 5v14"/><path d="M5 12h14"/></svg>';
      });
      if (!isOpen) {
        answer.style.maxHeight = answer.scrollHeight + 'px';
        btn.setAttribute('aria-expanded', 'true');
        const icon = btn.querySelector('.ngi-faq-toggle');
        if (icon) icon.innerHTML = '<svg class="ngi-icon-svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M5 12h14"/></svg>';
      }
    });
  });

  if (!reduced) {
    root.addEventListener('pointermove', (e) => {
      const r = root.getBoundingClientRect();
      root.style.setProperty('--mx', ((e.clientX - r.left) / Math.max(r.width, 1)) * 100 + '%');
      root.style.setProperty('--my', ((e.clientY - r.top) / Math.max(r.height, 1)) * 100 + '%');
    });
    root.querySelectorAll('.ngi-magnetic').forEach((btn) => {
      btn.addEventListener('pointermove', (e) => {
        const b = btn.getBoundingClientRect();
        btn.style.transform = `translate(${(e.clientX - b.left - b.width / 2) * 0.1}px,${(e.clientY - b.top - b.height / 2) * 0.1}px)`;
        btn.style.setProperty('--magx', e.clientX - b.left - b.width / 2 + 'px');
        btn.style.setProperty('--magy', e.clientY - b.top - b.height / 2 + 'px');
      });
      btn.addEventListener('pointerleave', () => {
        btn.style.transform = '';
        btn.style.setProperty('--magx', '0');
        btn.style.setProperty('--magy', '0');
      });
    });
    root.querySelectorAll('.ngi-kinetic-text').forEach((el) => {
      const words = el.textContent.trim().split(/\s+/);
      el.setAttribute('aria-label', el.textContent.trim());
      el.innerHTML = words.map((w, i) => `<span style="transition-delay:${i * 35}ms">${w}</span>`).join(' ');
    });
  }

  const divider = root.querySelector('#ngiScrollDivider');
  window.addEventListener(
    'scroll',
    () => {
      if (!divider) return;
      const max = document.documentElement.scrollHeight - window.innerHeight;
      divider.style.width = Math.min(100, Math.max(0, (window.scrollY / Math.max(max, 1)) * 100)) + '%';
    },
    { passive: true }
  );

  const baRange = root.querySelector('#ngiBaRange');
  const baAfter = root.querySelector('#ngiBaAfter');
  baRange?.addEventListener('input', () => {
    baAfter.style.clipPath = `inset(0 0 0 ${baRange.value}%)`;
  });

  const videoModal = document.getElementById('ngiVideoModal');
  const videoFrame = document.getElementById('ngiVideoFrame');
  let videoTrap = null;
  let bookingTrap = null;

  function openDialog(el, trapRefSetter, onClose) {
    if (!el) return;
    el.classList.add('is-open');
    if (window.BIFocusTrap) {
      trapRefSetter(window.BIFocusTrap.activate(el.querySelector('.ngi-modal-card') || el, {
        onEscape: onClose,
      }));
    }
  }
  function closeDialog(el, trap) {
    if (!el) return;
    el.classList.remove('is-open');
    if (window.BIFocusTrap) window.BIFocusTrap.release(trap);
  }

  root.querySelector('#ngiOpenVideo')?.addEventListener('click', () => {
    if (videoFrame && !videoFrame.src) {
      videoFrame.src = 'https://www.youtube.com/embed/dQw4w9WgXcQ?rel=0';
    }
    openDialog(videoModal, (t) => { videoTrap = t; }, () => {
      closeDialog(videoModal, videoTrap);
      videoTrap = null;
      if (videoFrame) videoFrame.src = '';
    });
  });
  root.querySelectorAll('[data-ngi-video-close]').forEach((btn) =>
    btn.addEventListener('click', () => {
      closeDialog(videoModal, videoTrap);
      videoTrap = null;
      if (videoFrame) videoFrame.src = '';
    })
  );
  videoModal?.addEventListener('click', (e) => {
    if (e.target === videoModal) {
      closeDialog(videoModal, videoTrap);
      videoTrap = null;
      if (videoFrame) videoFrame.src = '';
    }
  });

  let audioOn = false;
  let audioTick = null;
  const audioBtn = root.querySelector('#ngiAudioToggle');
  audioBtn?.addEventListener('click', (e) => {
    audioOn = !audioOn;
    e.currentTarget.setAttribute('aria-pressed', audioOn ? 'true' : 'false');
    e.currentTarget.setAttribute('aria-label', audioOn ? 'Pause audio preview' : 'Play audio preview');
    const bar = root.querySelector('#ngiAudioBar');
    let w = 42;
    clearInterval(audioTick);
    if (audioOn) {
      audioTick = setInterval(() => {
        w = (w + 7) % 100;
        if (bar) bar.style.width = Math.max(8, w) + '%';
      }, 420);
    }
  });

  root.querySelectorAll('.ngi-cursor-item').forEach((item) => {
    item.addEventListener('pointerenter', () => {
      root.querySelectorAll('.ngi-cursor-item').forEach((i) => {
        i.classList.remove('is-active');
        i.setAttribute('aria-selected', 'false');
      });
      item.classList.add('is-active');
      item.setAttribute('aria-selected', 'true');
      root.querySelector('#ngiCursorTitle').textContent = item.dataset.title;
      root.querySelector('#ngiCursorCopy').textContent = item.dataset.copy;
    });
  });
  root.querySelector('#ngiCursorPreview')?.addEventListener('pointermove', (e) => {
    const b = e.currentTarget.getBoundingClientRect();
    e.currentTarget.style.setProperty('--rx', ((e.clientX - b.left) / b.width) * 100 + '%');
    e.currentTarget.style.setProperty('--ry', ((e.clientY - b.top) / b.height) * 100 + '%');
  });

  const heroVideo = document.getElementById('ngiHeroVideo');
  document.getElementById('ngiHeroVideoToggle')?.addEventListener('click', () => {
    if (!heroVideo) return;
    if (heroVideo.paused) {
      heroVideo.play();
    } else {
      heroVideo.pause();
    }
  });
  if (heroVideo && !reduced) {
    heroVideo.play().catch(() => {});
  }

  const modal = document.getElementById('ngiBookingModal');
  root.querySelectorAll('[data-ngi-open]').forEach((btn) =>
    btn.addEventListener('click', () => {
      openDialog(modal, (t) => { bookingTrap = t; }, () => {
        closeDialog(modal, bookingTrap);
        bookingTrap = null;
      });
    })
  );
  root.querySelectorAll('[data-ngi-close]').forEach((btn) =>
    btn.addEventListener('click', () => {
      closeDialog(modal, bookingTrap);
      bookingTrap = null;
    })
  );
  modal?.addEventListener('click', (e) => {
    if (e.target === modal) {
      closeDialog(modal, bookingTrap);
      bookingTrap = null;
    }
  });
})();
