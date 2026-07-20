/**
 * Minimal UI library behaviors — accordion, reduced-motion safe.
 */
(function () {
  'use strict';

  function initAccordions(root) {
    root.querySelectorAll('[data-ng-accordion]').forEach(function (el) {
      var trigger = el.querySelector('.ng-accordion__trigger');
      var panel = el.querySelector('.ng-accordion__panel');
      if (!trigger || !panel) return;
      trigger.setAttribute('aria-expanded', 'false');
      panel.hidden = true;
      trigger.addEventListener('click', function () {
        var open = trigger.getAttribute('aria-expanded') === 'true';
        trigger.setAttribute('aria-expanded', open ? 'false' : 'true');
        panel.hidden = open;
      });
    });
  }

  document.addEventListener('DOMContentLoaded', function () {
    initAccordions(document);
  });
})();
