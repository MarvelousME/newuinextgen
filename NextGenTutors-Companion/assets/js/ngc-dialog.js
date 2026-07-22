/**
 * Accessible confirm dialog for Companion admin screens.
 * Standalone (no theme dependency). Promise-based.
 *
 * NGCDialog.confirm({ title, message, confirmLabel, cancelLabel, danger }) → Promise<boolean>
 */
(function (global) {
  'use strict';

  var root = null;
  var resolver = null;
  var previousFocus = null;

  var FOCUSABLE = 'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])';

  function ensure() {
    if (root) { return root; }
    root = document.createElement('div');
    root.className = 'ngc-dialog-root';
    root.setAttribute('aria-hidden', 'true');
    root.innerHTML =
      '<div class="ngc-dialog" role="alertdialog" aria-modal="true" aria-labelledby="ngc-dialog-title" aria-describedby="ngc-dialog-body" tabindex="-1">' +
        '<h2 class="ngc-dialog__title" id="ngc-dialog-title"></h2>' +
        '<p class="ngc-dialog__body" id="ngc-dialog-body"></p>' +
        '<div class="ngc-dialog__actions">' +
          '<button type="button" class="button ngc-dialog__cancel"></button>' +
          '<button type="button" class="button button-primary ngc-dialog__confirm"></button>' +
        '</div>' +
      '</div>';
    document.body.appendChild(root);

    root.addEventListener('click', function (e) {
      if (e.target === root) { finish(false); }
    });
    root.querySelector('.ngc-dialog__cancel').addEventListener('click', function () { finish(false); });
    root.querySelector('.ngc-dialog__confirm').addEventListener('click', function () { finish(true); });
    root.addEventListener('keydown', onKey);
    return root;
  }

  function onKey(e) {
    if (!root || !root.classList.contains('is-open')) { return; }
    if (e.key === 'Escape') {
      e.preventDefault();
      finish(false);
      return;
    }
    if (e.key !== 'Tab') { return; }
    var dialog = root.querySelector('.ngc-dialog');
    var nodes = Array.prototype.filter.call(dialog.querySelectorAll(FOCUSABLE), function (el) {
      return el.offsetParent !== null || el === document.activeElement;
    });
    if (!nodes.length) { return; }
    var first = nodes[0];
    var last = nodes[nodes.length - 1];
    if (e.shiftKey && document.activeElement === first) {
      e.preventDefault();
      last.focus();
    } else if (!e.shiftKey && document.activeElement === last) {
      e.preventDefault();
      first.focus();
    }
  }

  function finish(value) {
    if (!root) { return; }
    root.classList.remove('is-open');
    root.setAttribute('aria-hidden', 'true');
    if (previousFocus && typeof previousFocus.focus === 'function') {
      try { previousFocus.focus(); } catch (e) { /* gone */ }
    }
    previousFocus = null;
    var r = resolver;
    resolver = null;
    if (r) { r(value); }
  }

  function open(opts) {
    opts = opts || {};
    ensure();
    previousFocus = document.activeElement;
    root.querySelector('#ngc-dialog-title').textContent = opts.title || 'Confirm';
    root.querySelector('#ngc-dialog-body').textContent = opts.message || '';
    var confirmBtn = root.querySelector('.ngc-dialog__confirm');
    var cancelBtn = root.querySelector('.ngc-dialog__cancel');
    confirmBtn.textContent = opts.confirmLabel || 'Confirm';
    cancelBtn.textContent = opts.cancelLabel || 'Cancel';
    cancelBtn.hidden = !!opts.hideCancel;
    confirmBtn.classList.toggle('ngc-dialog__confirm--danger', !!opts.danger);

    return new Promise(function (resolve) {
      resolver = resolve;
      root.classList.add('is-open');
      root.setAttribute('aria-hidden', 'false');
      window.setTimeout(function () { confirmBtn.focus(); }, 10);
    });
  }

  global.NGCDialog = {
    confirm: function (opts) { return open(opts); },
    alert: function (opts) {
      opts = opts || {};
      opts.hideCancel = true;
      opts.confirmLabel = opts.confirmLabel || 'OK';
      return open(opts).then(function () { return undefined; });
    },
  };
})(window);
