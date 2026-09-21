/**
 * Content hub jump nav — active pill on scroll.
 */
(function () {
  'use strict';

  var jump = document.querySelector('.bi-hub-jump');
  if (!jump) {
    return;
  }

  var pills = Array.from(jump.querySelectorAll('.bi-hub-jump__pill'));
  var hubs = pills
    .map(function (pill) {
      var id = (pill.getAttribute('href') || '').replace('#', '');
      var el = id ? document.getElementById(id) : null;
      return el ? { pill: pill, el: el } : null;
    })
    .filter(Boolean);

  if (!hubs.length) {
    return;
  }

  function setActive(activePill) {
    pills.forEach(function (p) {
      p.classList.toggle('is-active', p === activePill);
    });
  }

  if ('IntersectionObserver' in window) {
    var io = new IntersectionObserver(
      function (entries) {
        entries.forEach(function (entry) {
          if (!entry.isIntersecting) {
            return;
          }
          var match = hubs.find(function (h) {
            return h.el === entry.target;
          });
          if (match) {
            setActive(match.pill);
          }
        });
      },
      { rootMargin: '-30% 0px -55% 0px', threshold: 0.01 }
    );
    hubs.forEach(function (h) {
      io.observe(h.el);
    });
  }

  pills.forEach(function (pill) {
    pill.addEventListener('click', function () {
      setActive(pill);
    });
  });
})();
