/**
 * NGC Match Wizard — multi-step tutor matching with AJAX scoring.
 * Attaches to [data-ngc-matcher] elements rendered by [ngc_match_tutor].
 * Phase 3: stepper indicator + sessionStorage form draft.
 */
(function () {
  'use strict';
  if (typeof window.NGC_MATCH === 'undefined') { return; }

  var STORAGE = 'ngc-match-wizard';

  function ready(fn) {
    if (document.readyState !== 'loading') { fn(); }
    else { document.addEventListener('DOMContentLoaded', fn); }
  }

  function saveForm(form) {
    try {
      var data = {};
      new FormData(form).forEach(function (v, k) {
        if (k.indexOf('_wp') === 0 || k === 'ngc_match_nonce') { return; }
        data[k] = v;
      });
      sessionStorage.setItem(STORAGE + ':form', JSON.stringify(data));
    } catch (e) { /* private mode */ }
  }

  function restoreForm(form) {
    try {
      var raw = sessionStorage.getItem(STORAGE + ':form');
      if (!raw) { return; }
      var data = JSON.parse(raw);
      Object.keys(data).forEach(function (name) {
        var el = form.querySelector('[name="' + name + '"]');
        if (!el) { return; }
        if (el.type === 'checkbox' || el.type === 'radio') {
          el.checked = el.value === data[name] || data[name] === '1';
        } else {
          el.value = data[name];
        }
      });
    } catch (e) { /* ignore */ }
  }

  function syncStepper(root, n) {
    var stepper = root.querySelector('[data-ngt-stepper]');
    if (!stepper) { return; }
    if (stepper._ngtStepper && typeof stepper._ngtStepper.go === 'function') {
      stepper._ngtStepper.go(n, { silent: true });
      return;
    }
    stepper.querySelectorAll('.ngt-stepper__item').forEach(function (item) {
      var step = parseInt(item.getAttribute('data-step'), 10);
      item.classList.toggle('is-active', step === n);
      item.classList.toggle('is-complete', step < n);
      item.setAttribute('aria-current', step === n ? 'step' : 'false');
    });
    try { sessionStorage.setItem(STORAGE + ':step', String(n)); } catch (e) { /* ignore */ }
  }

  ready(function () {
    if (window.NGTStepper) {
      document.querySelectorAll('[data-ngt-stepper]').forEach(window.NGTStepper.init);
    }

    document.querySelectorAll('[data-ngc-matcher]').forEach(function (root) {
      var step1 = root.querySelector('[data-step="1"]');
      var step2 = root.querySelector('[data-step="2"]');
      var form  = root.querySelector('.ngc-match-form');
      var out   = root.querySelector('.ngc-match-results');
      var resTitle = root.querySelector('.ngc-match-result-title');
      var resSub   = root.querySelector('.ngc-match-result-sub');
      var restart  = root.querySelector('.ngc-match-restart');
      var submitBtn = root.querySelector('.ngc-match-submit');
      var label     = submitBtn && submitBtn.querySelector('.ngc-btn-label');
      var spinner   = submitBtn && submitBtn.querySelector('.ngc-btn-spinner');

      if (!form || !step1 || !step2) { return; }

      restoreForm(form);

      function showStep(n) {
        [step1, step2].forEach(function (s) {
          var isActive = parseInt(s.getAttribute('data-step'), 10) === n;
          s.classList.toggle('is-active', isActive);
          s.hidden = !isActive;
        });
        syncStepper(root, n);
        root.scrollIntoView({ block: 'start', behavior: 'smooth' });
      }

      function setLoading(on) {
        if (!submitBtn) { return; }
        submitBtn.disabled = on;
        if (label) { label.hidden = on; }
        if (spinner) { spinner.hidden = !on; }
      }

      form.addEventListener('change', function () { saveForm(form); });
      form.addEventListener('input', function () { saveForm(form); });

      form.addEventListener('submit', function (e) {
        e.preventDefault();
        var fd   = new FormData(form);
        var body = new URLSearchParams();
        fd.forEach(function (v, k) { body.append(k, v); });
        body.append('action', 'ngc_match_tutors');
        body.append('nonce',  window.NGC_MATCH.nonce);

        saveForm(form);
        setLoading(true);

        fetch(window.NGC_MATCH.ajax, {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: body.toString()
        })
          .then(function (r) { return r.json(); })
          .then(function (res) {
            if (res && res.success) {
              if (out) { out.innerHTML = res.data.html || ''; }
              if (resTitle) {
                resTitle.textContent = res.data.count > 0
                  ? res.data.count + ' tutor' + (res.data.count !== 1 ? 's' : '') + ' matched'
                  : 'No exact matches yet';
              }
              if (resSub) {
                resSub.textContent = res.data.count > 0
                  ? 'Ranked by compatibility with your learner\'s needs.'
                  : 'We will match you personally — submit the form below.';
              }
              showStep(2);
            } else {
              var msg = (res && res.data && res.data.message) || 'An error occurred. Please try again.';
              if (window.BIDialog) { window.BIDialog.alert({ title: 'Matching', message: msg }); }
              else { window.alert(msg); }
            }
          })
          .catch(function () {
            var msg = 'Network error. Please check your connection and try again.';
            if (window.BIDialog) { window.BIDialog.alert({ title: 'Matching', message: msg }); }
            else { window.alert(msg); }
          })
          .finally(function () { setLoading(false); });
      });

      if (restart) {
        restart.addEventListener('click', function () {
          showStep(1);
        });
      }

      var savedStep = 1;
      try { savedStep = parseInt(sessionStorage.getItem(STORAGE + ':step') || '1', 10); } catch (e) { savedStep = 1; }
      showStep(savedStep === 2 && out && out.innerHTML.trim() ? 2 : 1);
    });
  });
})();
