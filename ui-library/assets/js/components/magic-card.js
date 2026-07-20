/**
 * Magic Card pointer spotlight.
 */
(function () {
  'use strict';

  function bind(card) {
    if (card.getAttribute('data-ngt-bound') === '1') return;
    card.setAttribute('data-ngt-bound', '1');

    if (window.NGTUI && window.NGTUI.prefersReducedMotion()) {
      return;
    }

    var size = parseFloat(getComputedStyle(card).getPropertyValue('--ngt-mc-size')) || 200;

    function setPos(x, y) {
      card.style.setProperty('--ngt-mc-x', x + 'px');
      card.style.setProperty('--ngt-mc-y', y + 'px');
    }

    function onMove(e) {
      var rect = card.getBoundingClientRect();
      setPos(e.clientX - rect.left, e.clientY - rect.top);
      card.classList.add('is-active');
    }

    function onLeave() {
      setPos(-size, -size);
      card.classList.remove('is-active');
    }

    card.addEventListener('pointermove', onMove);
    card.addEventListener('pointerleave', onLeave);
    card.addEventListener('pointerenter', function () {
      card.classList.add('is-active');
    });
    onLeave();
  }

  function init(scope) {
    (scope || document).querySelectorAll('[data-ngt-ui="magic-card"]').forEach(bind);
  }

  if (window.NGTUI) {
    window.NGTUI.register('magic-card', init);
  } else {
    document.addEventListener('DOMContentLoaded', function () { init(document); });
  }
})();
