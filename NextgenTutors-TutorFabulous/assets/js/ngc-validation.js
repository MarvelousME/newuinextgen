/**
 * NGC Form Validation — polished, animated, accessible.
 * Auto-attaches to every .ngc-form on the page.
 *
 * Phase 2 additions:
 *  - Accessible error summary (linked to fields via aria-describedby)
 *  - localStorage autosave for long tutor applications
 *
 * data-validate="rule1|rule2:arg|rule3" on any <input>, <select>, <textarea>.
 */
(function () {
  'use strict';

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
    alpha: function (v) { return /^[a-zA-Z\s]+$/.test(v); },
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

  function validateField(el) {
    var rules = (el.getAttribute('data-validate') || '').split('|').filter(Boolean);
    if (el.hasAttribute('required') && rules.indexOf('required') === -1) { rules.unshift('required'); }

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

  function setFieldState(el, err) {
    var group  = el.closest('.ngc-field-group') || el.parentElement;
    var errEl  = group && group.querySelector('.ngc-field-error');

    el.setAttribute('aria-invalid', err ? 'true' : 'false');
    if (group) {
      group.classList.toggle('has-error', !!err);
      group.classList.toggle('is-valid', !err && el.value && String(el.value).trim() !== '');
    }

    if (errEl) {
      if (!errEl.id) {
        errEl.id = (el.id || ('ngc-field-' + Math.random().toString(36).slice(2, 8))) + '-error';
      }
      var described = (el.getAttribute('aria-describedby') || '').split(/\s+/).filter(Boolean);
      if (described.indexOf(errEl.id) === -1) {
        described.push(errEl.id);
        el.setAttribute('aria-describedby', described.join(' '));
      }
      if (err) {
        if (errEl.textContent === err) { return; }
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

  function updateProgress(form) {
    var bar = form.querySelector('.ngc-form-progress-inner');
    if (!bar) { return; }
    var fields = Array.from(form.querySelectorAll('[data-validate], [required]'));
    if (!fields.length) { return; }
    var valid = fields.filter(function (el) {
      return el.getAttribute('aria-invalid') !== 'true' && String(el.value || '').trim();
    }).length;
    bar.style.width = Math.round((valid / fields.length) * 100) + '%';
  }

  function shake(el) {
    el.classList.remove('ngc-shake');
    void el.offsetWidth;
    el.classList.add('ngc-shake');
    el.addEventListener('animationend', function () { el.classList.remove('ngc-shake'); }, { once: true });
  }

  function ensureErrorSummary(form) {
    var summary = form.querySelector('.ngc-form-error-summary');
    if (summary) { return summary; }
    summary = document.createElement('div');
    summary.className = 'ngc-form-error-summary';
    summary.setAttribute('role', 'alert');
    summary.setAttribute('tabindex', '-1');
    summary.hidden = true;
    summary.innerHTML = '<p class="ngc-form-error-summary__title">Please fix the following:</p><ul></ul>';
    var progress = form.querySelector('.ngc-form-progress');
    if (progress && progress.nextSibling) {
      form.insertBefore(summary, progress.nextSibling);
    } else {
      form.insertBefore(summary, form.firstChild);
    }
    return summary;
  }

  function renderErrorSummary(form, errors) {
    var summary = ensureErrorSummary(form);
    var list = summary.querySelector('ul');
    list.innerHTML = '';
    if (!errors.length) {
      summary.hidden = true;
      return;
    }
    errors.forEach(function (item) {
      var li = document.createElement('li');
      var a = document.createElement('a');
      a.href = '#' + item.id;
      a.textContent = item.label + ': ' + item.message;
      a.addEventListener('click', function (e) {
        e.preventDefault();
        var target = document.getElementById(item.id);
        if (target) { target.focus(); target.scrollIntoView({ behavior: 'smooth', block: 'center' }); }
      });
      li.appendChild(a);
      list.appendChild(li);
    });
    summary.hidden = false;
    summary.focus();
  }

  function fieldLabel(el) {
    if (el.id) {
      var lab = formLabelFor(el);
      if (lab) { return lab; }
    }
    return el.getAttribute('name') || el.id || 'Field';
  }

  function formLabelFor(el) {
    var lab = el.id ? document.querySelector('label[for="' + el.id + '"]') : null;
    if (lab) { return lab.textContent.trim(); }
    var group = el.closest('.ngc-field-group');
    var nested = group && group.querySelector('label');
    return nested ? nested.textContent.trim() : '';
  }

  function ensureFieldId(el, index) {
    if (!el.id) { el.id = 'ngc-field-' + index; }
    return el.id;
  }

  /* ------- Autosave (tutor application / long forms) ------------------ */
  function autosaveKey(form) {
    var formId = form.querySelector('[name="ngc_form_id"]');
    var id = formId ? formId.value : (form.getAttribute('data-ngc-form') || '');
    if (!id) { return ''; }
    if (id.indexOf('become') === -1 && id.indexOf('tutor') === -1 && !form.hasAttribute('data-ngc-autosave')) {
      return '';
    }
    return 'ngc-form-draft:' + id;
  }

  function saveDraft(form) {
    var key = autosaveKey(form);
    if (!key) { return; }
    var data = {};
    form.querySelectorAll('input, select, textarea').forEach(function (el) {
      if (!el.name || el.type === 'password' || el.type === 'hidden' || el.type === 'file') { return; }
      if (el.type === 'checkbox' || el.type === 'radio') {
        if (el.checked) { data[el.name] = el.value || '1'; }
        return;
      }
      data[el.name] = el.value;
    });
    try { localStorage.setItem(key, JSON.stringify({ savedAt: Date.now(), data: data })); } catch (e) { /* private mode */ }
  }

  function restoreDraft(form) {
    var key = autosaveKey(form);
    if (!key) { return; }
    var raw;
    try { raw = localStorage.getItem(key); } catch (e) { return; }
    if (!raw) { return; }
    var parsed;
    try { parsed = JSON.parse(raw); } catch (e) { return; }
    if (!parsed || !parsed.data) { return; }
    // Drop drafts older than 7 days.
    if (parsed.savedAt && (Date.now() - parsed.savedAt) > 7 * 24 * 60 * 60 * 1000) {
      try { localStorage.removeItem(key); } catch (e) { /* ignore */ }
      return;
    }
    Object.keys(parsed.data).forEach(function (name) {
      var el = form.querySelector('[name="' + name + '"]');
      if (!el || el.type === 'password' || el.type === 'file') { return; }
      if (el.type === 'checkbox' || el.type === 'radio') {
        el.checked = !!parsed.data[name];
      } else {
        el.value = parsed.data[name];
      }
    });
  }

  function clearDraft(form) {
    var key = autosaveKey(form);
    if (!key) { return; }
    try { localStorage.removeItem(key); } catch (e) { /* ignore */ }
  }

  function attachForm(form) {
    if (form._ngcValidation) { return; }
    if (!form.matches('.ngc-form, .bi-ngc-form')) { return; }
    form._ngcValidation = true;

    if (!form.querySelector('.ngc-form-progress')) {
      var pb = document.createElement('div');
      pb.className = 'ngc-form-progress';
      pb.innerHTML = '<div class="ngc-form-progress-inner"></div>';
      form.insertBefore(pb, form.firstChild);
    }

    restoreDraft(form);

    var fields = form.querySelectorAll('input, select, textarea');
    fields.forEach(function (el, index) {
      ensureFieldId(el, index);
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
        saveDraft(form);
      });
      el.addEventListener('change', function () { saveDraft(form); });
    });

    form.addEventListener('submit', function (e) {
      var valid = true;
      var errors = [];
      fields.forEach(function (el, index) {
        if (el.getAttribute('data-validate') || el.hasAttribute('required')) {
          if (!validateField(el)) {
            valid = false;
            var id = ensureFieldId(el, index);
            var errEl = el.closest('.ngc-field-group') && el.closest('.ngc-field-group').querySelector('.ngc-field-error');
            errors.push({
              id: id,
              label: fieldLabel(el),
              message: (errEl && errEl.textContent) || 'Invalid value',
            });
          }
        }
      });
      updateProgress(form);
      if (!valid) {
        e.preventDefault();
        e.stopImmediatePropagation();
        shake(form);
        renderErrorSummary(form, errors);
        document.dispatchEvent(new CustomEvent('ngc:form-invalid', { detail: { form: form } }));
        return;
      }
      renderErrorSummary(form, []);
      clearDraft(form);
      var submitBtn = e.submitter || form.querySelector('[type="submit"], .button-primary, button, .ngt-btn');
      if (submitBtn && window.NGC_ButtonProcessing) {
        window.NGC_ButtonProcessing.start(submitBtn, e);
      }
    });
  }

  function init() {
    document.querySelectorAll('.ngc-form, .bi-ngc-form').forEach(attachForm);
    if (window.MutationObserver) {
      var mo = new MutationObserver(function (mutations) {
        mutations.forEach(function (m) {
          m.addedNodes.forEach(function (node) {
            if (node.nodeType !== 1) { return; }
            if (node.matches && (node.matches('.ngc-form') || node.matches('.bi-ngc-form'))) { attachForm(node); }
            if (node.querySelectorAll) { node.querySelectorAll('.ngc-form, .bi-ngc-form').forEach(attachForm); }
          });
        });
      });
      mo.observe(document.body, { childList: true, subtree: true });
    }
  }

  if (document.readyState !== 'loading') { init(); }
  else { document.addEventListener('DOMContentLoaded', init); }
})();
