/**
 * Login affordances — password visibility toggle + focus error summary.
 */
(function () {
  'use strict';

  function enhancePasswordField(input) {
    if (!input || input.dataset.biToggleBound === '1') { return; }
    input.dataset.biToggleBound = '1';

    var wrap = document.createElement('div');
    wrap.className = 'bi-password-field';
    input.parentNode.insertBefore(wrap, input);
    wrap.appendChild(input);

    var btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'bi-password-toggle';
    btn.setAttribute('aria-pressed', 'false');
    btn.setAttribute('aria-label', 'Show password');
    btn.textContent = 'Show';
    wrap.appendChild(btn);

    btn.addEventListener('click', function () {
      var show = input.type === 'password';
      input.type = show ? 'text' : 'password';
      btn.setAttribute('aria-pressed', show ? 'true' : 'false');
      btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
      btn.textContent = show ? 'Hide' : 'Show';
      input.focus();
    });
  }

  function boot() {
    document.querySelectorAll('.ngc-form--login #user_pass, .ngc-form--login input[type="password"]').forEach(enhancePasswordField);
    var err = document.querySelector('.bi-login__error');
    if (err && typeof err.focus === 'function') {
      err.focus();
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
  } else {
    boot();
  }
})();
