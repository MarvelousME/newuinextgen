/**
 * BeyondInfinity - Main JavaScript
 */
(function($) {
  'use strict';

  var data = window.biData || window.ngtData || {};

  /* Scroll-triggered animations */
  var animObserver = new IntersectionObserver(function(entries) {
    entries.forEach(function(entry) {
      if (entry.isIntersecting) {
        entry.target.classList.add('is-visible');
        animObserver.unobserve(entry.target);
      }
    });
  }, { threshold: 0.1, rootMargin: '0px 0px -50px 0px' });

  document.querySelectorAll('.bi-theme-content .ngt-animate').forEach(function(el) {
    animObserver.observe(el);
  });

  /* Parallax hero (legacy — motion.js handles when Framer pack enabled) */
  if (!document.body.classList.contains('bi-motion-enabled')) {
  var heroBg = document.querySelector('.ngt-hero__bg');
  if (heroBg) {
    window.addEventListener('scroll', function() {
      heroBg.style.transform = 'translateY(' + (window.pageYOffset * 0.4) + 'px)';
    }, { passive: true });
  }
  }

  /* 3D card tilt — bi-3d.js handles when 3D pack enabled; Framer motion uses hover-lift */
  if (!document.body.classList.contains('bi-motion-enabled') && !document.body.classList.contains('bi-3d-enabled')) {
  document.querySelectorAll('.bi-theme-content .ngt-card, .bi-theme-content .ngt-tutor-card, .bi-theme-content .ngt-pricing-card').forEach(function(card) {
    card.addEventListener('mousemove', function(e) {
      var rect = card.getBoundingClientRect();
      var rotateX = ((e.clientY - rect.top - rect.height / 2) / (rect.height / 2)) * -4;
      var rotateY = ((e.clientX - rect.left - rect.width / 2) / (rect.width / 2)) * 4;
      card.style.transform = 'perspective(1000px) rotateX(' + rotateX + 'deg) rotateY(' + rotateY + 'deg) translateY(-8px)';
    });
    card.addEventListener('mouseleave', function() {
      card.style.transform = '';
    });
  });
  }

  /* Sticky nav */
  var nav = document.querySelector('.ngt-nav');
  if (nav) {
    window.addEventListener('scroll', function() {
      nav.classList.toggle('ngt-nav--scrolled', window.scrollY > 50);
    }, { passive: true });
  }

  /* Tutor filter AJAX */
  var filterForm = document.querySelector('#ngt-tutor-filter');
  if (filterForm && data.ajaxUrl) {
    filterForm.addEventListener('change', function() {
      var grid = document.querySelector('#ngt-tutor-grid');
      if (!grid) return;
      grid.style.opacity = '0.5';
      $.ajax({
        url: data.ajaxUrl,
        type: 'POST',
        data: {
          action: 'ngt_search_tutors',
          nonce: data.nonce,
          subject: filterForm.querySelector('[name="subject"]')?.value || '',
          grade: filterForm.querySelector('[name="grade"]')?.value || '',
          province: filterForm.querySelector('[name="province"]')?.value || ''
        },
        success: function(response) {
          if (response.success) {
            grid.innerHTML = response.data.html;
            grid.style.opacity = '1';
          }
        },
        error: function() { grid.style.opacity = '1'; }
      });
    });
  }

  /* Pricing calculator */
  var calcFormat  = document.getElementById('bi-calc-format');
  var calcLessons = document.getElementById('bi-calc-lessons');
  var calcWeeks   = document.getElementById('bi-calc-weeks');
  var calcTotal   = document.getElementById('bi-calc-total');

  function updatePricingCalc() {
    if (!calcFormat || !calcLessons || !calcTotal) return;
    var rates = data.rates || {};
    var map = {
      online_short: rates.online_short || 320,
      online_long: rates.online_long || 300,
      inperson_short: rates.inperson_short || 350,
      inperson_long: rates.inperson_long || 320,
      tertiary: rates.tertiary || 500
    };
    var rate = map[calcFormat.value] || 320;
    var lessons = parseInt(calcLessons.value, 10) || 1;
    var weeks = calcWeeks ? parseInt(calcWeeks.value, 10) : 4;
    var total = rate * lessons * weeks;
    calcTotal.textContent = 'R' + total.toLocaleString('en-ZA');
  }

  if (calcFormat && calcLessons && calcTotal) {
    calcFormat.addEventListener('change', updatePricingCalc);
    calcLessons.addEventListener('change', updatePricingCalc);
    if (calcWeeks) calcWeeks.addEventListener('change', updatePricingCalc);
    updatePricingCalc();
  }

  /* Smooth scroll */
  document.querySelectorAll('a[href^="#"]').forEach(function(link) {
    link.addEventListener('click', function(e) {
      var target = document.querySelector(this.getAttribute('href'));
      if (target) {
        e.preventDefault();
        window.scrollTo({ top: target.getBoundingClientRect().top + window.pageYOffset - 90, behavior: 'smooth' });
      }
    });
  });

  /* Tutor directory format filters */
  var dirGrid = document.getElementById('bi-dir-grid');
  if (dirGrid) {
    var chips = document.querySelectorAll('.bi-fchip[data-format]');
    var cards = dirGrid.querySelectorAll('.bi-dir-card');
    var countEl = document.getElementById('bi-dir-count');
    function applyDirFilter(fmt) {
      var visible = 0;
      cards.forEach(function(card) {
        var type = card.getAttribute('data-format') || 'both';
        var show = fmt === 'all' || type === fmt || type === 'both' ||
          (fmt === 'personal' && type === 'in-person');
        card.classList.toggle('is-hidden', !show);
        if (show) visible++;
      });
      if (countEl) countEl.textContent = String(visible);
    }
    chips.forEach(function(chip) {
      chip.addEventListener('click', function() {
        chips.forEach(function(c) { c.classList.remove('is-active'); });
        chip.classList.add('is-active');
        applyDirFilter(chip.getAttribute('data-format'));
      });
    });
  }

  /* Stats animation */
  document.querySelectorAll('.ngt-stat__number').forEach(function(el) {
    var statObserver = new IntersectionObserver(function(entries) {
      entries.forEach(function(entry) {
        if (entry.isIntersecting) {
          entry.target.style.animation = 'ngt-countUp 0.8s ease-out';
          statObserver.unobserve(entry.target);
        }
      });
    }, { threshold: 0.5 });
    statObserver.observe(el);
  });

  /* Numeric counters — shared, suffix-safe and reduced-motion aware. */
  var reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var counterObserver = new IntersectionObserver(function (entries) {
    entries.forEach(function (entry) {
      if (!entry.isIntersecting) { return; }
      var el = entry.target;
      var rawTarget = el.getAttribute('data-target') || el.getAttribute('data-bi-count') || '0';
      var target = parseFloat(rawTarget);
      counterObserver.unobserve(el);
      if (!Number.isFinite(target) || target <= 0) { return; }
      var original = el.textContent || '';
      var numberMatch = original.match(/-?[\d,\s]+(?:\.\d+)?/);
      var inferredPrefix = numberMatch ? original.slice(0, numberMatch.index) : '';
      var inferredSuffix = numberMatch ? original.slice(numberMatch.index + numberMatch[0].length) : '';
      var prefix = el.hasAttribute('data-bi-prefix') ? el.getAttribute('data-bi-prefix') : inferredPrefix;
      var suffix = el.hasAttribute('data-bi-suffix') ? el.getAttribute('data-bi-suffix') : inferredSuffix;
      var decimalPart = String(rawTarget).split('.')[1] || '';
      var decimals = Math.min(2, parseInt(el.getAttribute('data-bi-decimals') || String(decimalPart.length), 10) || 0);

      function renderCounter(value) {
        el.textContent = prefix + value.toLocaleString('en-ZA', {
          minimumFractionDigits: decimals,
          maximumFractionDigits: decimals
        }) + suffix;
      }

      if (reducedMotion) {
        renderCounter(target);
        return;
      }
      var startTime;
      function tick(now) {
        if (!startTime) { startTime = now; }
        var progress = Math.min((now - startTime) / 900, 1);
        var eased = 1 - Math.pow(1 - progress, 3);
        renderCounter(target * eased);
        if (progress < 1) {
          window.requestAnimationFrame(tick);
        } else {
          renderCounter(target);
        }
      }
      window.requestAnimationFrame(tick);
    });
  }, { threshold: 0.3 });
  document.querySelectorAll('.counter[data-target], [data-bi-count]').forEach(function (el) {
    counterObserver.observe(el);
  });

})(jQuery);
