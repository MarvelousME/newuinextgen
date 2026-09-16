(function () {
  'use strict';

  var cfg = window.biThemeSwitcher;
  if (!cfg || !cfg.presets) {
    return;
  }

  function qs(sel, root) {
    return (root || document).querySelector(sel);
  }

  function qsa(sel, root) {
    return Array.prototype.slice.call((root || document).querySelectorAll(sel));
  }

  function applySkin(skinId) {
    document.documentElement.setAttribute('data-bi-skin', skinId);
    document.body.classList.forEach(function (cls) {
      if (cls.indexOf('bi-skin-') === 0) {
        document.body.classList.remove(cls);
      }
    });
    document.body.classList.add('bi-skin-' + skinId);
  }

  function applyScheme(schemeId) {
    document.documentElement.setAttribute('data-bi-scheme', schemeId);
    var schemes = (window.biCustomizerPreview && window.biCustomizerPreview.schemes) || cfg.schemeTokens || {};
    var tokens = schemes[schemeId] || schemes.default || {};
    Object.keys(tokens).forEach(function (key) {
      document.documentElement.style.setProperty(key, tokens[key]);
    });
    document.body.classList.forEach(function (cls) {
      if (cls.indexOf('bi-scheme-') === 0) {
        document.body.classList.remove(cls);
      }
    });
    document.body.classList.add('bi-scheme-' + schemeId);
  }

  function buildOptions(container, type, items, activeId, onSelect) {
    if (!container) {
      return;
    }
    container.innerHTML = '';
    var label = document.createElement('p');
    label.className = 'bi-theme-switcher__group-label';
    label.textContent = type === 'skin' ? cfg.i18n.skin : cfg.i18n.scheme;
    container.appendChild(label);

    Object.keys(items).forEach(function (id) {
      var item = items[id];
      var btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'bi-theme-switcher__option' + (id === activeId ? ' is-active' : '');
      btn.dataset.value = id;
      btn.innerHTML =
        '<span class="bi-theme-switcher__option-title">' +
        (item.title || id) +
        '</span>' +
        (item.desc ? '<span class="bi-theme-switcher__option-desc">' + item.desc + '</span>' : '');
      btn.addEventListener('click', function () {
        onSelect(id);
        qsa('.bi-theme-switcher__option', container).forEach(function (el) {
          el.classList.toggle('is-active', el.dataset.value === id);
        });
      });
      container.appendChild(btn);
    });
  }

  function persistSkinPreview(skinId) {
    var body = new URLSearchParams();
    body.append('action', 'bi_set_skin_preview');
    body.append('nonce', cfg.nonce);
    body.append('skin', skinId);
    return fetch(cfg.ajaxUrl, {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: body.toString(),
    });
  }

  function initRoot(root) {
    var skinGroup = qs('[data-bi-switcher="skin"]', root);
    var schemeGroup = qs('[data-bi-switcher="scheme"]', root);

    buildOptions(skinGroup, 'skin', cfg.presets, cfg.active, function (skinId) {
      applySkin(skinId);
      persistSkinPreview(skinId).then(function () {
        if (cfg.reload) {
          window.location.reload();
        }
      });
    });

    if (cfg.schemes) {
      var schemeItems = {};
      Object.keys(cfg.schemes).forEach(function (id) {
        schemeItems[id] = { title: cfg.schemes[id] };
      });
      buildOptions(schemeGroup, 'scheme', schemeItems, cfg.scheme, function (schemeId) {
        applyScheme(schemeId);
      });
    }
  }

  function initFloating() {
    var widget = document.getElementById('bi-theme-switcher');
    if (!widget) {
      return;
    }
    widget.hidden = false;
    initRoot(widget);

    var toggle = qs('.bi-theme-switcher__toggle', widget);
    var panel = qs('.bi-theme-switcher__panel', widget);
    if (!toggle || !panel) {
      return;
    }

    toggle.addEventListener('click', function () {
      var open = panel.hidden;
      panel.hidden = !open;
      toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    });

    document.addEventListener('click', function (e) {
      if (!widget.contains(e.target)) {
        panel.hidden = true;
        toggle.setAttribute('aria-expanded', 'false');
      }
    });
  }

  function initInline() {
    qsa('[data-bi-switcher-root]').forEach(initRoot);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function () {
      initFloating();
      initInline();
    });
  } else {
    initFloating();
    initInline();
  }
})();
