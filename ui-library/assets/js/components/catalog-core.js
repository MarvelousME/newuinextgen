/**
 * NGT UI Catalog — core effects (text, grids, terminal, buttons, progress).
 */
(function (window, document) {
  'use strict';

  function qs(root, sel) {
    return Array.prototype.slice.call(root.querySelectorAll(sel));
  }

  function parseItems(el) {
    try {
      var raw = el.getAttribute('data-items') || el.getAttribute('data-lines') || '[]';
      var arr = JSON.parse(raw);
      return Array.isArray(arr) ? arr : [];
    } catch (e) {
      return [];
    }
  }

  function animateNumber(el, from, to, duration) {
    var start = performance.now();
    function frame(now) {
      var t = Math.min(1, (now - start) / duration);
      var eased = 1 - Math.pow(1 - t, 3);
      el.textContent = String(Math.round(from + (to - from) * eased));
      if (t < 1) requestAnimationFrame(frame);
    }
    requestAnimationFrame(frame);
  }

  function initWordCycles(scope) {
    qs(scope, '[data-ngt-words]').forEach(function (node) {
      if (node.dataset.ngtBound) return;
      node.dataset.ngtBound = '1';
      var wrap = node.closest('[data-items]') || node.parentElement;
      var words = parseItems(wrap);
      if (!words.length) return;
      var i = 0;
      if (window.NGTUI && window.NGTUI.prefersReducedMotion()) {
        node.textContent = words[0];
        return;
      }
      setInterval(function () {
        i = (i + 1) % words.length;
        node.style.opacity = '0';
        setTimeout(function () {
          node.textContent = words[i];
          node.style.opacity = '1';
        }, 180);
      }, 2200);
    });
  }

  function initTickers(scope) {
    qs(scope, '[data-ngt-ticker]').forEach(function (node) {
      if (node.dataset.ngtBound) return;
      node.dataset.ngtBound = '1';
      var host = node.closest('[data-from]') || node.parentElement;
      var from = parseFloat(host.getAttribute('data-from') || '0');
      var to = parseFloat(host.getAttribute('data-to') || host.getAttribute('data-value') || '100');
      if (window.NGTUI && window.NGTUI.prefersReducedMotion()) {
        node.textContent = String(Math.round(to));
        return;
      }
      animateNumber(node, from, to, 1600);
    });
  }

  function initGrids(scope) {
    qs(scope, '[data-ngt-grid]').forEach(function (grid) {
      if (grid.dataset.ngtBound) return;
      grid.dataset.ngtBound = '1';
      var total = 16 * 8;
      for (var i = 0; i < total; i++) {
        grid.appendChild(document.createElement('span'));
      }
      var cells = qs(grid, 'span');
      function flicker() {
        cells.forEach(function (c) {
          c.classList.toggle('is-on', Math.random() > 0.82);
        });
      }
      flicker();
      if (!(window.NGTUI && window.NGTUI.prefersReducedMotion())) {
        setInterval(flicker, 500);
      }
      grid.addEventListener('pointermove', function (ev) {
        var rect = grid.getBoundingClientRect();
        var idx = Math.floor(((ev.clientY - rect.top) / rect.height) * 8) * 16 + Math.floor(((ev.clientX - rect.left) / rect.width) * 16);
        if (cells[idx]) cells[idx].classList.add('is-on');
      });
    });
  }

  function initScrollProgress(scope) {
    qs(scope, '[data-ngt-ui="scroll-progress"]').forEach(function (host) {
      if (host.dataset.ngtBound) return;
      host.dataset.ngtBound = '1';
      host.classList.add('ngt-ui-scroll-progress');
      var bar = host.querySelector('[data-ngt-scroll-progress]') || host.querySelector('.ngt-ui-progress__bar');
      if (!bar) return;
      function update() {
        var max = document.documentElement.scrollHeight - window.innerHeight;
        bar.style.width = (max > 0 ? (window.scrollY / max) * 100 : 0) + '%';
      }
      update();
      window.addEventListener('scroll', update, { passive: true });
    });
  }

  function initTerminal(scope) {
    qs(scope, '[data-ngt-terminal]').forEach(function (term) {
      if (term.dataset.ngtBound) return;
      term.dataset.ngtBound = '1';
      var out = term.querySelector('[data-ngt-term-out]');
      var lines = parseItems(term);
      if (!out || !lines.length) return;
      var i = 0;
      out.textContent = '';
      (function typeLine() {
        if (i >= lines.length) return;
        out.textContent += (i ? '\n' : '') + lines[i++];
        setTimeout(typeLine, 700);
      })();
    });
  }

  function initGlare(scope) {
    qs(scope, '[data-ngt-ui="glare-hover"]').forEach(function (card) {
      if (card.dataset.ngtBound) return;
      card.dataset.ngtBound = '1';
      card.addEventListener('pointermove', function (ev) {
        var rect = card.getBoundingClientRect();
        card.style.setProperty('--gx', ((ev.clientX - rect.left) / rect.width) * 100 + '%');
        card.style.setProperty('--gy', ((ev.clientY - rect.top) / rect.height) * 100 + '%');
      });
    });
  }

  function initRippleButtons(scope) {
    qs(scope, '[data-ngt-ui="ripple-button"] .ngt-ui-btn').forEach(function (btn) {
      if (btn.dataset.ngtBound) return;
      btn.dataset.ngtBound = '1';
      btn.addEventListener('click', function (ev) {
        var rect = btn.getBoundingClientRect();
        var ripple = document.createElement('span');
        var size = Math.max(rect.width, rect.height);
        ripple.style.cssText = 'position:absolute;border-radius:50%;background:rgba(255,255,255,.35);width:' + size + 'px;height:' + size + 'px;left:' + (ev.clientX - rect.left - size / 2) + 'px;top:' + (ev.clientY - rect.top - size / 2) + 'px;transform:scale(0);animation:ngt-ripple-btn .6s ease-out forwards;pointer-events:none;';
        btn.appendChild(ripple);
        setTimeout(function () { ripple.remove(); }, 650);
      });
    });
  }

  function initSubscribe(scope) {
    qs(scope, '[data-ngt-ui="animated-subscribe-button"] .ngt-ui-btn').forEach(function (btn) {
      if (btn.dataset.ngtBound) return;
      btn.dataset.ngtBound = '1';
      btn.addEventListener('click', function (ev) {
        ev.preventDefault();
        btn.classList.add('is-done');
        var label = btn.querySelector('.ngt-ui-btn__label');
        if (label) label.textContent = 'Subscribed';
      });
    });
  }

  function initThemeToggle(scope) {
    qs(scope, '[data-ngt-ui="animated-theme-toggler"] .ngt-ui-btn').forEach(function (btn) {
      if (btn.dataset.ngtBound) return;
      btn.dataset.ngtBound = '1';
      btn.addEventListener('click', function (ev) {
        ev.preventDefault();
        document.documentElement.classList.toggle('ngt-ui-dark');
        var label = btn.querySelector('.ngt-ui-btn__label');
        if (label) {
          label.textContent = document.documentElement.classList.contains('ngt-ui-dark') ? 'Dark' : 'Light';
        }
      });
    });
  }

  function initBlurFade(scope) {
    qs(scope, '[data-ngt-ui="blur-fade"]').forEach(function (el) {
      el.classList.add('ngt-ui-blur-fade');
    });
  }

  function initHyperText(scope) {
    qs(scope, '[data-ngt-ui="hyper-text"] [data-ngt-words]').forEach(function (node) {
      if (node.dataset.ngtHyper) return;
      node.dataset.ngtHyper = '1';
      var target = node.textContent || 'NextGen';
      var chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
      var frame = 0;
      (function scramble() {
        frame += 1;
        node.textContent = target.split('').map(function (ch, i) {
          return i < frame / 2 ? target[i] : chars[Math.floor(Math.random() * chars.length)];
        }).join('');
        if (frame < target.length * 2) requestAnimationFrame(scramble);
        else node.textContent = target;
      })();
    });
  }

  function boot(scope) {
    initWordCycles(scope);
    initTickers(scope);
    initGrids(scope);
    initScrollProgress(scope);
    initTerminal(scope);
    initGlare(scope);
    initRippleButtons(scope);
    initSubscribe(scope);
    initThemeToggle(scope);
    initBlurFade(scope);
    initHyperText(scope);
  }

  if (window.NGTUI && typeof window.NGTUI.register === 'function') {
    window.NGTUI.register('catalog-core', boot);
  }

  if (!document.getElementById('ngt-ui-catalog-keyframes')) {
    var style = document.createElement('style');
    style.id = 'ngt-ui-catalog-keyframes';
    style.textContent = '@keyframes ngt-ripple-btn{to{transform:scale(2.4);opacity:0}}';
    document.head.appendChild(style);
  }
})(window, document);
