/**
 * Accessible confirm / alert dialog.
 * Requires BIFocusTrap (bi-focus-trap.js).
 *
 * BIDialog.confirm({ title, message, confirmLabel, cancelLabel, danger })
 *   → Promise<boolean>
 * BIDialog.alert({ title, message, confirmLabel })
 *   → Promise<void>
 */
(function (global) {
  'use strict';

  var root = null;
  var trap = null;
  var resolver = null;

  function ensureRoot() {
    if (root) { return root; }
    root = document.createElement('div');
    root.className = 'bi-dialog-root';
    root.setAttribute('aria-hidden', 'true');
    root.innerHTML =
      '<div class="bi-dialog" role="alertdialog" aria-modal="true" aria-labelledby="bi-dialog-title" aria-describedby="bi-dialog-body" tabindex="-1">' +
        '<h2 class="bi-dialog__title" id="bi-dialog-title"></h2>' +
        '<p class="bi-dialog__body" id="bi-dialog-body"></p>' +
        '<div class="bi-dialog__actions">' +
          '<button type="button" class="ngt-btn ngt-btn--outline bi-dialog__cancel"></button>' +
          '<button type="button" class="ngt-btn ngt-btn--primary bi-dialog__confirm"></button>' +
        '</div>' +
      '</div>';
    document.body.appendChild(root);

    root.addEventListener('click', function (e) {
      if (e.target === root) { finish(false); }
    });
    root.querySelector('.bi-dialog__cancel').addEventListener('click', function () { finish(false); });
    root.querySelector('.bi-dialog__confirm').addEventListener('click', function () { finish(true); });
    return root;
  }

  function finish(value) {
    if (!root) { return; }
    root.classList.remove('is-open');
    root.setAttribute('aria-hidden', 'true');
    if (global.BIFocusTrap) { global.BIFocusTrap.release(trap); }
    trap = null;
    var r = resolver;
    resolver = null;
    if (r) { r(value); }
  }

  function open(opts) {
    opts = opts || {};
    ensureRoot();
    var dialog = root.querySelector('.bi-dialog');
    var title = root.querySelector('#bi-dialog-title');
    var body = root.querySelector('#bi-dialog-body');
    var confirmBtn = root.querySelector('.bi-dialog__confirm');
    var cancelBtn = root.querySelector('.bi-dialog__cancel');

    title.textContent = opts.title || 'Confirm';
    body.textContent = opts.message || '';
    confirmBtn.textContent = opts.confirmLabel || 'Confirm';
    cancelBtn.textContent = opts.cancelLabel || 'Cancel';
    cancelBtn.hidden = !!opts.hideCancel;
    confirmBtn.classList.toggle('ngt-btn--danger', !!opts.danger);

    return new Promise(function (resolve) {
      resolver = resolve;
      root.classList.add('is-open');
      root.setAttribute('aria-hidden', 'false');
      if (global.BIFocusTrap) {
        trap = global.BIFocusTrap.activate(dialog, {
          initialFocus: '.bi-dialog__confirm',
          onEscape: function () { finish(false); },
        });
      } else {
        confirmBtn.focus();
      }
    });
  }

  global.BIDialog = {
    confirm: function (opts) { return open(opts); },
    alert: function (opts) {
      opts = opts || {};
      opts.hideCancel = true;
      opts.confirmLabel = opts.confirmLabel || 'OK';
      return open(opts).then(function () { return undefined; });
    },
  };
})(window);
