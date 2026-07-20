/**
 * Tutor income calculator — live recalculation.
 *
 * @package NGT_UI
 */
(function () {
  'use strict';

  function formatMoney(symbol, value) {
    var n = Math.round(value);
    try {
      return symbol + ' ' + n.toLocaleString();
    } catch (e) {
      return symbol + ' ' + String(n);
    }
  }

  function init(root) {
    if (!root || root.dataset.ngtIcBound) {
      return;
    }
    root.dataset.ngtIcBound = '1';

    var symbol = root.getAttribute('data-symbol') || 'R';
    var weeks = parseFloat(root.getAttribute('data-weeks') || '4.33') || 4.33;

    var hoursInput = root.querySelector('[data-ngt-ic-hours]');
    var rateInput = root.querySelector('[data-ngt-ic-rate]');
    var feeInput = root.querySelector('[data-ngt-ic-fee]');
    var hoursOut = root.querySelector('[data-ngt-ic-hours-out]');
    var feeOut = root.querySelector('[data-ngt-ic-fee-out]');
    var weeklyEl = root.querySelector('[data-ngt-ic-weekly]');
    var monthlyEl = root.querySelector('[data-ngt-ic-monthly]');
    var annualEl = root.querySelector('[data-ngt-ic-annual]');

    function recalc() {
      var hours = parseFloat(hoursInput && hoursInput.value) || 0;
      var rate = parseFloat(rateInput && rateInput.value) || 0;
      var fee = parseFloat(feeInput && feeInput.value) || 0;

      if (hoursOut) {
        hoursOut.textContent = String(Math.round(hours));
      }
      if (feeOut) {
        feeOut.textContent = String(Math.round(fee)) + '%';
      }

      var weeklyGross = hours * rate;
      var monthlyGross = weeklyGross * weeks;
      var monthlyNet = monthlyGross * (1 - fee / 100);
      var annualNet = monthlyNet * 12;

      if (weeklyEl) {
        weeklyEl.textContent = formatMoney(symbol, weeklyGross);
      }
      if (monthlyEl) {
        monthlyEl.textContent = formatMoney(symbol, monthlyNet);
      }
      if (annualEl) {
        annualEl.textContent = formatMoney(symbol, annualNet);
      }
    }

    [hoursInput, rateInput, feeInput].forEach(function (el) {
      if (!el) {
        return;
      }
      el.addEventListener('input', recalc);
      el.addEventListener('change', recalc);
    });

    recalc();
  }

  function boot() {
    document.querySelectorAll('[data-ngt-income-calculator]').forEach(init);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
  } else {
    boot();
  }

  document.addEventListener('ngt-ui-rendered', boot);
})();
