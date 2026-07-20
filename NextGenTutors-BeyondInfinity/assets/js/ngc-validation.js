/**
 * NGC Form Validation — polished, animated, accessible.
 * Auto-attaches to every .ngc-form on the page.
 *
 * data-validate="rule1|rule2:arg|rule3" on any <input>, <select>, <textarea>.
 *
 * Built-in rules:
 *   required            — field must not be empty
 *   email               — valid email address
 *   sa-phone            — South African mobile (06x/07x/08x/+27...)
 *   url                 — valid URL
 *   min-length:N        — minimum N characters
 *   max-length:N        — maximum N characters
 *   min:N               — numeric ≥ N
 *   max:N               — numeric ≤ N
 *   match:#otherId      — value must match another field
 *   popia               — checkbox must be checked (POPIA consent)
 *   numeric             — only digits
 *   alpha               — only letters + spaces
 *   no-script           — no HTML/script injection
 */
(function () {
  'use strict';

  /* ------- Validators ------------------------------------------------- */
  var V = {
    required: function (v, _a, el) {
      if (el.type === 'checkbox') { return el.checked; }
      return v.trim().length > 0;
    },
    email: function (v) {
      return /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(v.trim());
    },
    'sa-phone': function (v) {
      var s = v.replace(/[\s\-()]/g, '');
      return /^(\+27|0)[6-8]\d{8}$/.test(s);
    },
    url: function (v) {
      try { new URL(v); return true; } catch (_) { return false; }
    },
    'min-length': function (v, a) { return v.trim().length >= parseInt(a, 10); },
    'max-length': function (v, a) { return v.trim().length <= parseInt(a, 10); },
    min: function (v, a) { return parseFloat(v) >= parseFloat(a); },
    max: function (v, a) { return parseFloat(v) <= parseFloat(a); },
    numeric: function (v) { return /^\d+$/.test(v.trim()); },
    alpha: function (v) { return /^[a-zA-Z\s]+$/.test(v.trim()); },
    popia: function (_v, _a, el) { return el.checked; },
    'no-script': function (v) { return !/<[^>]*>/.test(v); },
    match: function (v, a) {
      var other = document.querySelector(a);
      return other ? v === other.value : true;
    },
  };

  var MSGS = {
    required:     'This field is required.',
    email:        'Please enter a valid email address.',
    'sa-phone':   'Enter a valid SA phone number (e.g. 082 345 6789).',
    url:          'Please enter a valid URL.',
    'min-length': 'Must be at least {a} characters.',
    'max-length': 'Must be no more than {a} characters.',
    min:          'Must be at least {a}.',
    max:          'Must be no more than {a}.',
    numeric:      'Please enter numbers only.',
    alpha:        'Please enter letters only.',
    popia:        'You must accept the terms to continue.',
    'no-script':  'HTML is not allowed in this field.',
    match:        'Fields do not match.',
  };

  /* ------- Validate one field ----------------------------------------- */
  function validateField(el) {
    var rules = (el.getAttribute('data-validate') || '').split('|').filter(Boolean);
    if (el.hasAttribute('required') && !rules.includes('required')) { rules.unshift('required'); }

    var err = null;
    for (var i = 0; i < rules.length; i++) {
      var parts = rules[i].split(':');
      var rule  = parts[0];
      var arg   = parts[1] || '';
      if (!V[rule]) { continue; }
      if (!V[rule](el.value, arg, el)) {
        err = (MSGS[rule] || 'Invalid.').replace('{a}', arg);
        break;
      }
    }

    setFieldState(el, err);
    return err === null;
  }

  /* ------- Apply visual state ----------------------------------------- */
  function setFieldState(el, err) {
    var group  = el.closest('.ngc-field-group') || el.parentElement;
    var errEl  = group && group.querySelector('.ngc-field-error');

    el.setAttribute('aria-invalid', err ? 'true' : 'false');
    group && group.classList.toggle('has-error', !!err);
    group && group.classList.toggle('is-valid', !err && el.value.trim() !== '');

    if (errEl) {
      if (err) {
        if (errEl.textContent === err) { return; } // no flicker
        errEl.textContent = err;
        errEl.classList.add('is-visible');
        errEl.classList.remove('ngc-err-exit');
      } else {
        errEl.classList.add('ngc-err-exit');
        setTimeout(function () {
          errEl.textContent = '';
          errEl.classList.remove('is-visible', 'ngc-err-exit');
        }, 250);
      }
    }
  }

  /* ------- Progress bar ----------------------------------------------- */
  function updateProgress(form) {
    var bar = form.querySelector('.ngc-form-progress-inner');
    if (!bar) { return; }
    var fields = Array.from(form.querySelectorAll('[data-validate], [required]'));
    if (!fields.length) { return; }
    var valid = fields.filter(function (el) {
      return el.getAttribute('aria-invalid') !== 'true' && el.value.trim();
    }).length;
    bar.style.width = Math.round((valid / fields.length) * 100) + '%';
  }

  /* ------- Shake animation ------------------------------------------- */
  function shake(el) {
    el.classList.remove('ngc-shake');
    void el.offsetWidth; // reflow
    el.classList.add('ngc-shake');
    el.addEventListener('animationend', function () { el.classList.remove('ngc-shake'); }, { once: true });
  }

  /* ------- Attach to a form ------------------------------------------ */
  function attachForm(form) {
    if (form._ngcValidation) { return; }
    if (!form.matches('.ngc-form, .bi-ngc-form')) { return; }
    form._ngcValidation = true;

    // Inject progress bar if not already present.
    if (!form.querySelector('.ngc-form-progress')) {
      var pb = document.createElement('div');
      pb.className = 'ngc-form-progress';
      pb.innerHTML = '<div class="ngc-form-progress-inner"></div>';
      form.insertBefore(pb, form.firstChild);
    }

    var fields = form.querySelectorAll('input, select, textarea');
    fields.forEach(function (el) {
      // Ensure error span exists.
      if (!el.closest('.ngc-field-group')) { return; }
      if (!el.closest('.ngc-field-group').querySelector('.ngc-field-error')) {
        var sp = document.createElement('span');
        sp.className = 'ngc-field-error';
        sp.setAttribute('aria-live', 'polite');
        el.parentElement.appendChild(sp);
      }

      el.addEventListener('blur', function () {
        if (el.getAttribute('data-validate') || el.hasAttribute('required')) {
          validateField(el);
          updateProgress(form);
        }
      });

      el.addEventListener('input', function () {
        el.dataset.ngcTouched = '1';
        if (el.getAttribute('data-validate') || el.hasAttribute('required')) {
          validateField(el);
          updateProgress(form);
        }
      });
    });

    form.addEventListener('submit', function (e) {
      var valid = true;
      fields.forEach(function (el) {
        if (el.getAttribute('data-validate') || el.hasAttribute('required')) {
          if (!validateField(el)) { valid = false; }
        }
      });
      updateProgress(form);
      if (!valid) {
        e.preventDefault();
        e.stopImmediatePropagation();
        shake(form);
        document.dispatchEvent(new CustomEvent('ngc:form-invalid', { detail: { form: form } }));
        var firstErr = form.querySelector('[aria-invalid="true"]');
        if (firstErr) { firstErr.focus(); }
        return;
      }
      var submitBtn = e.submitter || form.querySelector('[type="submit"], .button-primary, button, .ngt-btn');
      if (submitBtn && window.NGC_ButtonProcessing) {
        window.NGC_ButtonProcessing.start(submitBtn, e);
      }
    });
  }

  /* ------- Boot ------------------------------------------------------- */
  function init() {
    document.querySelectorAll('.ngc-form, .bi-ngc-form').forEach(attachForm);
    // Watch for dynamically added forms (e.g. shortcode injected via AJAX).
    if (window.MutationObserver) {
      var mo = new MutationObserver(function (mutations) {
        mutations.forEach(function (m) {
          m.addedNodes.forEach(function (node) {
            if (node.nodeType !== 1) { return; }
            if (node.matches && (node.matches('.ngc-form') || node.matches('.bi-ngc-form'))) { attachForm(node); }
            node.querySelectorAll && node.querySelectorAll('.ngc-form, .bi-ngc-form').forEach(attachForm);
          });
        });
      });
      mo.observe(document.body, { childList: true, subtree: true });
    }
  }

  if (document.readyState !== 'loading') { init(); }
  else { document.addEventListener('DOMContentLoaded', init); }
})();
