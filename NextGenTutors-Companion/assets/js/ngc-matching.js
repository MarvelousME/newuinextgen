/**
 * NGC Match Wizard — multi-step tutor matching with AJAX scoring.
 * Attaches to [data-ngc-matcher] elements rendered by [ngc_match_tutor].
 */
(function () {
  'use strict';
  if (typeof window.NGC_MATCH === 'undefined') { return; }

  function ready(fn) {
    if (document.readyState !== 'loading') { fn(); }
    else { document.addEventListener('DOMContentLoaded', fn); }
  }

  ready(function () {
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

      function showStep(n) {
        [step1, step2].forEach(function (s) {
          var isActive = parseInt(s.getAttribute('data-step'), 10) === n;
          s.classList.toggle('is-active', isActive);
          s.hidden = !isActive;
        });
        root.scrollIntoView({ block: 'start', behavior: 'smooth' });
      }

      function setLoading(on) {
        if (!submitBtn) { return; }
        submitBtn.disabled = on;
        label  && (label.hidden  = on);
        spinner && (spinner.hidden = !on);
      }

      form.addEventListener('submit', function (e) {
        e.preventDefault();
        // Validation fires first via ngc-validation.js; if the form
        // native submit gets here, all fields passed — proceed.
        var fd   = new FormData(form);
        var body = new URLSearchParams();
        fd.forEach(function (v, k) { body.append(k, v); });
        body.append('action', 'ngc_match_tutors');
        body.append('nonce',  window.NGC_MATCH.nonce);

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
              if (resTitle) { resTitle.textContent = res.data.count > 0
                ? res.data.count + ' tutor' + (res.data.count !== 1 ? 's' : '') + ' matched'
                : 'No exact matches yet'; }
              if (resSub) { resSub.textContent = res.data.count > 0
                ? 'Ranked by compatibility with your learner\'s needs.'
                : 'We will match you personally — submit the form below.'; }
              showStep(2);
            } else {
              alert((res && res.data && res.data.message) || 'An error occurred. Please try again.');
            }
          })
          .catch(function () {
            alert('Network error. Please check your connection and try again.');
          })
          .finally(function () { setLoading(false); });
      });

      restart && restart.addEventListener('click', function () {
        showStep(1);
      });

      // Initial state.
      showStep(1);
    });
  });
})();
