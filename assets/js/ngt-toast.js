/**
 * Form submission toast — 10s visible, then dismiss.
 */
(function () {
  'use strict';

  var DURATION = 10000;
  var LABELS = {
    find_tutor: 'Find a tutor request',
    become_tutor: 'Tutor application',
    contact_support: 'Support message',
    parent_register: 'Parent registration',
    student_register: 'Student registration',
    parent_register_child: 'Child registration',
    login: 'Sign-in',
  };

  function ensureToast() {
    var el = document.getElementById('ngt-toast');
    if (el) { return el; }
    el = document.createElement('div');
    el.id = 'ngt-toast';
    el.className = 'ngt-toast';
    el.setAttribute('role', 'status');
    el.setAttribute('aria-live', 'polite');
    el.hidden = true;
    document.body.appendChild(el);
    return el;
  }

  function hideToast(el) {
    el.classList.remove('is-visible');
    el.hidden = true;
  }

  window.ngtShowToast = function (message, type) {
    var el = ensureToast();
    el.textContent = message;
    el.className = 'ngt-toast is-visible' + (type ? ' ngt-toast--' + type : '');
    el.hidden = false;
    clearTimeout(window._ngtToastTimer);
    window._ngtToastTimer = setTimeout(function () { hideToast(el); }, DURATION);
  };

  function labelFor(formId) {
    if (LABELS[formId]) { return LABELS[formId]; }
    return formId.replace(/_/g, ' ');
  }

  function checkSubmittedParam() {
    var params = new URLSearchParams(window.location.search);
    var id = params.get('ngc_submitted');
    if (!id) { return; }
    window.ngtShowToast(
      'Thank you! Your ' + labelFor(id) + ' was submitted successfully.',
      'success'
    );
    if (window.history && window.history.replaceState) {
      params.delete('ngc_submitted');
      var qs = params.toString();
      var url = window.location.pathname + (qs ? '?' + qs : '') + window.location.hash;
      window.history.replaceState({}, document.title, url);
    }
  }

  document.addEventListener('DOMContentLoaded', checkSubmittedParam);
})();
