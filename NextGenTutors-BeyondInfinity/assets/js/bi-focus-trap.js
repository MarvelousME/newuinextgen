/**
 * Shared focus trap for dialogs/drawers.
 * Usage: var trap = BIFocusTrap.activate(dialogEl); … BIFocusTrap.release(trap);
 * Spec: documentation/ux-redesign/04-motion-spec.md §5.4
 */
(function (global) {
  'use strict';

  var FOCUSABLE = 'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])';

  function getFocusable(root) {
    return Array.prototype.filter.call(root.querySelectorAll(FOCUSABLE), function (el) {
      return el.offsetParent !== null || el === document.activeElement;
    });
  }

  function onKeydown(e) {
    var trap = e.currentTarget._biFocusTrap;
    if (!trap) { return; }
    if (e.key === 'Escape' && typeof trap.onEscape === 'function') {
      e.preventDefault();
      trap.onEscape();
      return;
    }
    if (e.key !== 'Tab') { return; }
    var nodes = getFocusable(trap.root);
    if (!nodes.length) {
      e.preventDefault();
      return;
    }
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

  function activate(root, options) {
    if (!root) { return null; }
    options = options || {};
    var trap = {
      root: root,
      previous: document.activeElement,
      onEscape: options.onEscape || null,
    };
    root._biFocusTrap = trap;
    root.addEventListener('keydown', onKeydown);
    root.setAttribute('aria-hidden', 'false');
    var nodes = getFocusable(root);
    var initial = options.initialFocus
      ? root.querySelector(options.initialFocus)
      : (nodes[0] || root);
    window.setTimeout(function () {
      if (initial && typeof initial.focus === 'function') { initial.focus(); }
    }, 10);
    return trap;
  }

  function release(trap) {
    if (!trap || !trap.root) { return; }
    trap.root.removeEventListener('keydown', onKeydown);
    trap.root.setAttribute('aria-hidden', 'true');
    delete trap.root._biFocusTrap;
    if (trap.previous && typeof trap.previous.focus === 'function') {
      try { trap.previous.focus(); } catch (e) { /* element may be gone */ }
    }
  }

  global.BIFocusTrap = { activate: activate, release: release };
})(window);
