/**
 * In-place booking drawer — keeps tutor + slot context instead of bouncing
 * the user to a blank /find-a-tutor page.
 * Spec: documentation/ux-redesign/02-information-architecture.md §4.1
 */
(function () {
  'use strict';

  var drawer = document.getElementById('bi-booking-drawer');
  if (!drawer) { return; }

  var panel = drawer.querySelector('.bi-booking-drawer__panel');
  var titleEl = drawer.querySelector('[data-bi-bd-title]');
  var metaEl = drawer.querySelector('[data-bi-bd-meta]');
  var trustEl = drawer.querySelector('[data-bi-bd-trust]');
  var continueBtn = drawer.querySelector('[data-bi-bd-continue]');
  var loginBtn = drawer.querySelector('[data-bi-bd-login]');
  var trap = null;
  var openTrigger = null;

  function esc(s) {
    var d = document.createElement('div');
    d.textContent = s == null ? '' : String(s);
    return d.innerHTML;
  }

  function close() {
    if (!drawer.classList.contains('is-open')) { return; }
    drawer.classList.remove('is-open');
    document.body.classList.remove('bi-drawer-open');
    if (window.BIFocusTrap) { window.BIFocusTrap.release(trap); }
    trap = null;
    if (openTrigger && typeof openTrigger.focus === 'function') {
      openTrigger.focus();
    }
    openTrigger = null;
  }

  function open(data, trigger) {
    openTrigger = trigger || null;
    var tutor = data.tutorId || '';
    var date = data.date || '';
    var start = data.start || '';
    var end = data.end || '';
    var subject = data.subject || '';
    var delivery = data.delivery || '';
    var tutorName = data.tutorName || '';

    if (titleEl) {
      titleEl.textContent = tutorName
        ? ('Book with ' + tutorName)
        : 'Confirm this time';
    }
    if (metaEl) {
      var bits = [];
      if (date) { bits.push(date); }
      if (start) { bits.push(end ? (start + '–' + end) : start); }
      if (subject) { bits.push(subject); }
      if (delivery) { bits.push(delivery.charAt(0).toUpperCase() + delivery.slice(1)); }
      metaEl.innerHTML = bits.length
        ? '<ul class="bi-booking-drawer__facts">' + bits.map(function (b) {
            return '<li>' + esc(b) + '</li>';
          }).join('') + '</ul>'
        : '<p>' + esc('Pick a time on the calendar, then continue.') + '</p>';
    }
    if (trustEl) {
      trustEl.hidden = false;
    }

    var params = new URLSearchParams();
    if (tutor) { params.set('ngc_tutor_id', tutor); }
    if (date) { params.set('ngc_slot_date', date); }
    if (start) { params.set('ngc_slot_start', start); }
    if (end) { params.set('ngc_slot_end', end); }
    if (subject) { params.set('ngc_subject', subject); }
    if (delivery) { params.set('ngc_delivery_mode', delivery); }

    var continueUrl = (drawer.getAttribute('data-continue-url') || '/find-a-tutor/') +
      (params.toString() ? ('?' + params.toString()) : '');
    if (continueBtn) {
      continueBtn.setAttribute('href', continueUrl);
    }
    if (loginBtn) {
      var loginBase = drawer.getAttribute('data-login-url') || '/login/';
      loginBtn.setAttribute('href', loginBase + (loginBase.indexOf('?') >= 0 ? '&' : '?') + 'redirect_to=' + encodeURIComponent(continueUrl));
      loginBtn.hidden = drawer.getAttribute('data-logged-in') === '1';
    }

    drawer.classList.add('is-open');
    document.body.classList.add('bi-drawer-open');
    if (window.BIFocusTrap) {
      trap = window.BIFocusTrap.activate(panel || drawer, {
        initialFocus: '[data-bi-bd-continue]',
        onEscape: close,
      });
    }
  }

  function readFromLink(link) {
    return {
      tutorId: link.getAttribute('data-tutor-id') || '',
      date: link.getAttribute('data-date') || '',
      start: link.getAttribute('data-start') || '',
      end: link.getAttribute('data-end') || '',
      subject: link.getAttribute('data-subject') || '',
      delivery: link.getAttribute('data-delivery') || '',
      tutorName: link.getAttribute('data-tutor-name') || '',
    };
  }

  document.addEventListener('click', function (e) {
    var closeBtn = e.target.closest('[data-bi-bd-close]');
    if (closeBtn) {
      e.preventDefault();
      close();
      return;
    }
    if (e.target === drawer) {
      close();
      return;
    }

    var trigger = e.target.closest('[data-bi-booking-drawer], [data-ngc-slot="1"], .bi-book-lesson-trigger');
    if (!trigger) { return; }
    // Progressive enhancement: drawer opens, link still works without JS.
    e.preventDefault();
    open(readFromLink(trigger), trigger);
  });

  // Prefill if the page already has slot query args (deep link / refresh).
  var qs = new URLSearchParams(window.location.search);
  if (qs.get('ngc_tutor_id') && qs.get('ngc_slot_date') && qs.get('ngc_slot_start')) {
    window.setTimeout(function () {
      open({
        tutorId: qs.get('ngc_tutor_id'),
        date: qs.get('ngc_slot_date'),
        start: qs.get('ngc_slot_start'),
        end: qs.get('ngc_slot_end') || '',
        subject: qs.get('ngc_subject') || '',
        delivery: qs.get('ngc_delivery_mode') || '',
      }, null);
    }, 400);
  }
})();
