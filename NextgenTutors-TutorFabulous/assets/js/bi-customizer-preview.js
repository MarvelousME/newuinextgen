(function ($) {
  'use strict';

  if (typeof wp === 'undefined' || !wp.customize || !window.biCustomizerPreview) {
    return;
  }

  function applyScheme(schemeId) {
    var schemes = window.biCustomizerPreview.schemes || {};
    var tokens = schemes[schemeId] || schemes.default || {};
    var root = document.documentElement;
    Object.keys(tokens).forEach(function (key) {
      root.style.setProperty(key, tokens[key]);
    });
    root.setAttribute('data-bi-scheme', schemeId);
    document.body.classList.forEach(function (cls) {
      if (cls.indexOf('bi-scheme-') === 0) {
        document.body.classList.remove(cls);
      }
    });
    document.body.classList.add('bi-scheme-' + schemeId);
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

  wp.customize('color_scheme', function (setting) {
    setting.bind(applyScheme);
    applyScheme(setting.get());
  });

  wp.customize('visual_preset', function (setting) {
    setting.bind(function (skinId) {
      applySkin(skinId);
      // Skin CSS is a separate file — reload preview to load correct token layer.
      if (window.wp && wp.customize && wp.customize.previewer) {
        wp.customize.previewer.refresh();
      }
    });
    applySkin(setting.get());
  });
})(jQuery);
