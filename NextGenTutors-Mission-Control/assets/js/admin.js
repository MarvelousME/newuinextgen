/**
 * Mission Control — confirm destructive / long-running ops.
 */
(function () {
  'use strict';
  document.addEventListener('click', function (e) {
    var btn = e.target.closest('[data-testid="ngtmc-op-pipeline"], [data-testid="ngtmc-op-seed"]');
    if (!btn) return;
    var msg = btn.getAttribute('data-testid') === 'ngtmc-op-pipeline'
      ? 'Run the full Mission Control pipeline? This may take several minutes.'
      : 'Seed Phase 14 demo data now?';
    if (!window.confirm(msg)) {
      e.preventDefault();
    }
  });
})();
