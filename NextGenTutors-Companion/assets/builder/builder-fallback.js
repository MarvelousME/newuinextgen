/**
 * NGT Visual Builder — admin shell (no-build fallback SPA).
 * Covers Phase 1 section canvas + tokens, Phase 2 layout/history/breakpoints,
 * Phase 3 effects, Phase 4 interactions/chrome, Phase 5 dynamics/assets.
 */
(function () {
  'use strict';

  var cfg = window.NGC_BUILDER || {};
  var root = document.getElementById('ngc-builder-root');
  if (!root || !cfg.restRoot) return;

  var headers = {
    'Content-Type': 'application/json',
    'X-WP-Nonce': cfg.nonce || '',
  };

  var state = {
    list: [],
    active: null,
    document: null,
    selectedId: null,
    breakpoint: 'base',
    tokens: null,
    overlay: {},
    host: cfg.host || {},
    history: [],
    future: [],
    tab: 'canvas',
    assets: [],
    interactions: null,
    dynamics: null,
    status: '',
  };

  function api(path, opts) {
    return fetch(cfg.restRoot + path, Object.assign({ headers: headers, credentials: 'same-origin' }, opts || {})).then(
      function (r) {
        return r.json().then(function (body) {
          if (!r.ok) throw new Error((body && (body.message || body.code)) || 'HTTP ' + r.status);
          return body;
        });
      }
    );
  }

  function esc(s) {
    var d = document.createElement('div');
    d.textContent = s == null ? '' : String(s);
    return d.innerHTML;
  }

  function setStatus(msg) {
    state.status = msg || '';
    var el = document.getElementById('ngc-b-status');
    if (el) el.textContent = state.status;
  }

  function pushHistory() {
    if (!state.document) return;
    state.history.push(JSON.stringify(state.document));
    if (state.history.length > 50) state.history.shift();
    state.future = [];
  }

  function undo() {
    if (!state.history.length) return;
    state.future.push(JSON.stringify(state.document));
    state.document = JSON.parse(state.history.pop());
    render();
  }

  function redo() {
    if (!state.future.length) return;
    state.history.push(JSON.stringify(state.document));
    state.document = JSON.parse(state.future.pop());
    render();
  }

  function selectedNode() {
    if (!state.document || !state.selectedId) return null;
    return state.document.nodes[state.selectedId] || null;
  }

  function rootChildren() {
    var rootNode = state.document && state.document.nodes[state.document.rootId];
    return (rootNode && rootNode.children) || [];
  }

  function shell() {
    root.innerHTML =
      '<div class="ngc-b-app" data-testid="ngc-builder-app">' +
      '<header class="ngc-b-header">' +
      '<div><h1>Visual Builder</h1><p class="ngc-b-sub">Edit BeyondInfinity via structured JSON — theme stays the renderer.</p></div>' +
      '<div class="ngc-b-actions">' +
      '<button type="button" class="button" id="ngc-b-migrate" data-testid="ngc-b-migrate">Migrate sections</button>' +
      '<button type="button" class="button" id="ngc-b-new">New page</button>' +
      '<button type="button" class="button" id="ngc-b-chrome">New chrome…</button>' +
      '</div></header>' +
      '<p id="ngc-b-status" class="ngc-b-status" role="status"></p>' +
      '<div class="ngc-b-host" id="ngc-b-host"></div>' +
      '<div class="ngc-b-layout">' +
      '<aside class="ngc-b-sidebar" id="ngc-b-list"></aside>' +
      '<main class="ngc-b-main">' +
      '<div class="ngc-b-toolbar" id="ngc-b-toolbar"></div>' +
      '<div class="ngc-b-workspace">' +
      '<aside class="ngc-b-layers" id="ngc-b-layers"></aside>' +
      '<section class="ngc-b-canvas" id="ngc-b-canvas" data-testid="ngc-b-canvas"></section>' +
      '<aside class="ngc-b-inspector" id="ngc-b-inspector"></aside>' +
      '</div></main></div></div>';

    document.getElementById('ngc-b-migrate').onclick = function () {
      api('migrate?force=1', { method: 'POST' })
        .then(function (res) {
          setStatus('Migrated: ' + (res.created || []).join(', ') || 'ok');
          return loadList();
        })
        .catch(function (e) {
          setStatus(e.message);
        });
    };
    document.getElementById('ngc-b-new').onclick = function () {
      api('documents', {
        method: 'POST',
        body: JSON.stringify({ title: 'Untitled page', kind: 'page' }),
      })
        .then(function (row) {
          return openDoc(row.document_key || (row.document && row.document.id));
        })
        .then(loadList)
        .catch(function (e) {
          setStatus(e.message);
        });
    };
    document.getElementById('ngc-b-chrome').onclick = function () {
      var kind = window.prompt('Chrome kind: header | footer | popup | mega_menu | template', 'popup');
      if (!kind) return;
      api('chrome', { method: 'POST', body: JSON.stringify({ kind: kind, title: kind }) })
        .then(function (row) {
          return openDoc(row.document_key || (row.document && row.document.id));
        })
        .then(loadList)
        .catch(function (e) {
          setStatus(e.message);
        });
    };

    document.addEventListener('keydown', function (e) {
      var mod = e.metaKey || e.ctrlKey;
      if (mod && e.key === 'z' && !e.shiftKey) {
        e.preventDefault();
        undo();
      }
      if (mod && (e.key === 'y' || (e.key === 'z' && e.shiftKey))) {
        e.preventDefault();
        redo();
      }
      if (mod && e.key === 's') {
        e.preventDefault();
        saveDoc();
      }
      if (mod && e.key === 'd') {
        e.preventDefault();
        duplicateSelected();
      }
    });
  }

  function renderHost() {
    var el = document.getElementById('ngc-b-host');
    if (!el) return;
    var h = state.host || {};
    el.innerHTML = h.ok
      ? '<span class="ngc-b-badge is-ok">Host OK · contract ' + esc(h.contractVersion || '') + ' · ' + esc(String(h.sectionCount || 0)) + ' sections</span>'
      : '<span class="ngc-b-badge is-warn">Read-only — ' + esc(h.message || 'no theme host') + '</span>';
  }

  function renderList() {
    var el = document.getElementById('ngc-b-list');
    if (!el) return;
    el.innerHTML =
      '<h2>Documents</h2>' +
      state.list
        .map(function (row) {
          var active = state.active === row.document_key ? ' is-active' : '';
          return (
            '<button type="button" class="ngc-b-doc' +
            active +
            '" data-key="' +
            esc(row.document_key) +
            '"><strong>' +
            esc(row.title || row.document_key) +
            '</strong><span>' +
            esc(row.kind) +
            ' · ' +
            esc(row.status) +
            '</span></button>'
          );
        })
        .join('') || '<p class="ngc-b-empty">No documents yet.</p>';
    el.querySelectorAll('[data-key]').forEach(function (btn) {
      btn.onclick = function () {
        openDoc(btn.getAttribute('data-key'));
      };
    });
  }

  function renderToolbar() {
    var el = document.getElementById('ngc-b-toolbar');
    if (!el) return;
    if (!state.document) {
      el.innerHTML = '<p class="ngc-b-empty">Select a document.</p>';
      return;
    }
    el.innerHTML =
      '<div class="ngc-b-toolbar__row">' +
      '<strong>' +
      esc(state.document.meta && state.document.meta.title ? state.document.meta.title : state.document.id) +
      '</strong>' +
      '<label>Breakpoint <select id="ngc-b-bp">' +
      ['base', 'tablet', 'mobile']
        .map(function (bp) {
          return '<option value="' + bp + '"' + (state.breakpoint === bp ? ' selected' : '') + '>' + bp + '</option>';
        })
        .join('') +
      '</select></label>' +
      '<button type="button" class="button" id="ngc-b-undo">Undo</button>' +
      '<button type="button" class="button" id="ngc-b-redo">Redo</button>' +
      '<button type="button" class="button button-primary" id="ngc-b-save" data-testid="ngc-b-save">Save</button>' +
      '<button type="button" class="button" id="ngc-b-publish" data-testid="ngc-b-publish">Publish</button>' +
      '<button type="button" class="button" id="ngc-b-tab-canvas">Canvas</button>' +
      '<button type="button" class="button" id="ngc-b-tab-tokens">Tokens</button>' +
      '<button type="button" class="button" id="ngc-b-tab-assets">Assets</button>' +
      '</div>';
    document.getElementById('ngc-b-bp').onchange = function (e) {
      state.breakpoint = e.target.value;
      render();
    };
    document.getElementById('ngc-b-undo').onclick = undo;
    document.getElementById('ngc-b-redo').onclick = redo;
    document.getElementById('ngc-b-save').onclick = saveDoc;
    document.getElementById('ngc-b-publish').onclick = publishDoc;
    document.getElementById('ngc-b-tab-canvas').onclick = function () {
      state.tab = 'canvas';
      render();
    };
    document.getElementById('ngc-b-tab-tokens').onclick = function () {
      state.tab = 'tokens';
      loadTokens().then(render);
    };
    document.getElementById('ngc-b-tab-assets').onclick = function () {
      state.tab = 'assets';
      loadAssets().then(render);
    };
  }

  function renderLayers() {
    var el = document.getElementById('ngc-b-layers');
    if (!el) return;
    if (!state.document || state.tab !== 'canvas') {
      el.innerHTML = '';
      return;
    }
    var kids = rootChildren();
    el.innerHTML =
      '<h2>Layers</h2><ul class="ngc-b-layer-list" id="ngc-b-layer-list">' +
      kids
        .map(function (id, idx) {
          var n = state.document.nodes[id] || {};
          var sel = state.selectedId === id ? ' is-selected' : '';
          return (
            '<li class="ngc-b-layer' +
            sel +
            '" draggable="true" data-id="' +
            esc(id) +
            '" data-idx="' +
            idx +
            '">' +
            '<span>' +
            esc(n.name || id) +
            '</span><em>' +
            esc(n.type) +
            '</em></li>'
          );
        })
        .join('') +
      '</ul>' +
      '<div class="ngc-b-layer-actions">' +
      '<button type="button" class="button" id="ngc-b-dup">Duplicate</button>' +
      '<button type="button" class="button" id="ngc-b-toggle-vis">Toggle visibility</button>' +
      '</div>';

    var dragId = null;
    el.querySelectorAll('.ngc-b-layer').forEach(function (li) {
      li.onclick = function () {
        state.selectedId = li.getAttribute('data-id');
        render();
      };
      li.ondragstart = function () {
        dragId = li.getAttribute('data-id');
      };
      li.ondragover = function (e) {
        e.preventDefault();
      };
      li.ondrop = function (e) {
        e.preventDefault();
        var target = li.getAttribute('data-id');
        if (!dragId || dragId === target) return;
        pushHistory();
        var children = rootChildren().slice();
        var from = children.indexOf(dragId);
        var to = children.indexOf(target);
        if (from < 0 || to < 0) return;
        children.splice(from, 1);
        children.splice(to, 0, dragId);
        state.document.nodes[state.document.rootId].children = children;
        render();
      };
    });
    var dup = document.getElementById('ngc-b-dup');
    if (dup) dup.onclick = duplicateSelected;
    var tv = document.getElementById('ngc-b-toggle-vis');
    if (tv)
      tv.onclick = function () {
        var n = selectedNode();
        if (!n) return;
        pushHistory();
        var when = (n.visibility && n.visibility.when) || 'always';
        n.visibility = { when: when === 'never' ? 'always' : 'never' };
        if (n.props) n.props.enabled = n.visibility.when !== 'never';
        render();
      };
  }

  function renderCanvas() {
    var el = document.getElementById('ngc-b-canvas');
    if (!el) return;
    if (state.tab === 'tokens') {
      renderTokensPanel(el);
      return;
    }
    if (state.tab === 'assets') {
      renderAssetsPanel(el);
      return;
    }
    if (!state.document) {
      el.innerHTML = '<p class="ngc-b-empty">Open a document to edit.</p>';
      return;
    }
    var frameClass = 'ngc-b-frame ngc-b-frame--' + state.breakpoint;
    el.innerHTML =
      '<div class="' +
      frameClass +
      '">' +
      rootChildren()
        .map(function (id) {
          var n = state.document.nodes[id] || {};
          var hidden = n.visibility && n.visibility.when === 'never';
          var sel = state.selectedId === id ? ' is-selected' : '';
          return (
            '<article class="ngc-b-section-card' +
            sel +
            (hidden ? ' is-hidden' : '') +
            '" data-id="' +
            esc(id) +
            '">' +
            '<header><strong>' +
            esc(n.name || id) +
            '</strong><span>' +
            esc(n.type) +
            (n.component ? ' · ' + esc(n.component) : '') +
            '</span></header>' +
            '<pre class="ngc-b-json">' +
            esc(JSON.stringify({ layout: n.layout, style: n.style, props: n.props }, null, 2)) +
            '</pre></article>'
          );
        })
        .join('') +
      '</div>';
    el.querySelectorAll('[data-id]').forEach(function (card) {
      card.onclick = function () {
        state.selectedId = card.getAttribute('data-id');
        render();
      };
    });
  }

  function renderInspector() {
    var el = document.getElementById('ngc-b-inspector');
    if (!el) return;
    if (state.tab !== 'canvas') {
      el.innerHTML = '';
      return;
    }
    var n = selectedNode();
    if (!n) {
      el.innerHTML = '<h2>Inspector</h2><p class="ngc-b-empty">Select a layer.</p>';
      return;
    }
    var layout = n.layout || {};
    var style = n.style || {};
    var props = n.props || {};
    el.innerHTML =
      '<h2>Inspector</h2>' +
      '<label>Name <input type="text" id="ngc-bi-name" value="' +
      esc(n.name || '') +
      '" /></label>' +
      '<fieldset><legend>Layout (Phase 2)</legend>' +
      fieldSelect('display', layout.display || 'flex', ['flex', 'grid', 'block']) +
      fieldSelect('direction', layout.direction || 'column', ['column', 'row']) +
      fieldSelect('position', layout.position || 'relative', ['relative', 'absolute', 'sticky', 'fixed']) +
      fieldText('gap', layout.gap || '') +
      fieldText('gridTemplate', layout.gridTemplate || '') +
      '</fieldset>' +
      '<fieldset><legend>Style / Effects (Phase 3)</legend>' +
      fieldText('paddingBlock', style.paddingBlock || '') +
      fieldText('background', style.background || '') +
      fieldText('borderRadius', style.borderRadius || '') +
      fieldText('boxShadow', style.boxShadow || '') +
      fieldText('backdropFilter', style.backdropFilter || '') +
      fieldText('opacity', style.opacity || '') +
      fieldText('transform', style.transform || '') +
      fieldText('filter', style.filter || '') +
      '</fieldset>' +
      '<fieldset><legend>Typography</legend>' +
      fieldText('fontFamily', style.fontFamily || '') +
      fieldText('fontSize', style.fontSize || '') +
      fieldText('fontWeight', style.fontWeight || '') +
      fieldText('lineHeight', style.lineHeight || '') +
      fieldText('letterSpacing', style.letterSpacing || '') +
      fieldText('color', style.color || '') +
      '</fieldset>' +
      '<fieldset><legend>Props</legend>' +
      '<textarea id="ngc-bi-props" rows="8">' +
      esc(JSON.stringify(props, null, 2)) +
      '</textarea></fieldset>' +
      '<fieldset><legend>Interactions (Phase 4)</legend>' +
      '<textarea id="ngc-bi-ix" rows="5">' +
      esc(JSON.stringify(n.interactions || [], null, 2)) +
      '</textarea></fieldset>' +
      '<fieldset><legend>Bindings (Phase 5)</legend>' +
      '<textarea id="ngc-bi-bind" rows="4">' +
      esc(JSON.stringify(n.bindings || {}, null, 2)) +
      '</textarea></fieldset>' +
      '<button type="button" class="button button-primary" id="ngc-bi-apply">Apply</button>';

    document.getElementById('ngc-bi-apply').onclick = function () {
      pushHistory();
      n.name = document.getElementById('ngc-bi-name').value;
      n.layout = n.layout || {};
      n.style = n.style || {};
      ['display', 'direction', 'position', 'gap', 'gridTemplate'].forEach(function (k) {
        var input = document.getElementById('ngc-bi-' + k);
        if (input) n.layout[k] = input.value;
      });
      [
        'paddingBlock',
        'background',
        'borderRadius',
        'boxShadow',
        'backdropFilter',
        'opacity',
        'transform',
        'filter',
        'fontFamily',
        'fontSize',
        'fontWeight',
        'lineHeight',
        'letterSpacing',
        'color',
      ].forEach(function (k) {
        var input = document.getElementById('ngc-bi-' + k);
        if (input) n.style[k] = input.value;
      });
      try {
        n.props = JSON.parse(document.getElementById('ngc-bi-props').value || '{}');
      } catch (e1) {
        setStatus('Props JSON invalid');
        return;
      }
      try {
        n.interactions = JSON.parse(document.getElementById('ngc-bi-ix').value || '[]');
      } catch (e2) {
        setStatus('Interactions JSON invalid');
        return;
      }
      try {
        n.bindings = JSON.parse(document.getElementById('ngc-bi-bind').value || '{}');
      } catch (e3) {
        setStatus('Bindings JSON invalid');
        return;
      }
      setStatus('Node updated (unsaved)');
      render();
    };
  }

  function fieldText(key, val) {
    return (
      '<label>' +
      esc(key) +
      ' <input type="text" id="ngc-bi-' +
      esc(key) +
      '" value="' +
      esc(val) +
      '" /></label>'
    );
  }

  function fieldSelect(key, val, opts) {
    return (
      '<label>' +
      esc(key) +
      ' <select id="ngc-bi-' +
      esc(key) +
      '">' +
      opts
        .map(function (o) {
          return '<option value="' + o + '"' + (o === val ? ' selected' : '') + '>' + o + '</option>';
        })
        .join('') +
      '</select></label>'
    );
  }

  function renderTokensPanel(el) {
    var t = state.tokens || {};
    el.innerHTML =
      '<div class="ngc-b-tokens">' +
      '<h2>Design Tokens</h2>' +
      '<p>Overlay merges onto UI Library baseline. Values emit as CSS variables.</p>' +
      '<textarea id="ngc-b-token-json" rows="22">' +
      esc(JSON.stringify(state.overlay && Object.keys(state.overlay).length ? state.overlay : t, null, 2)) +
      '</textarea>' +
      '<button type="button" class="button button-primary" id="ngc-b-token-save">Save tokens</button>' +
      '<pre class="ngc-b-css">' +
      esc((state.tokensCss || '').slice(0, 1200)) +
      '</pre></div>';
    document.getElementById('ngc-b-token-save').onclick = function () {
      var overlay;
      try {
        overlay = JSON.parse(document.getElementById('ngc-b-token-json').value);
      } catch (e) {
        setStatus('Token JSON invalid');
        return;
      }
      api('tokens', { method: 'PUT', body: JSON.stringify({ overlay: overlay }) })
        .then(function (res) {
          state.tokens = res.tokens;
          state.overlay = res.overlay;
          state.tokensCss = res.css;
          setStatus('Tokens saved');
          render();
        })
        .catch(function (err) {
          setStatus(err.message);
        });
    };
  }

  function renderAssetsPanel(el) {
    el.innerHTML =
      '<div class="ngc-b-assets"><h2>Asset Manager</h2>' +
      '<input type="search" id="ngc-b-asset-q" placeholder="Search media…" />' +
      '<div class="ngc-b-asset-grid">' +
      (state.assets || [])
        .map(function (a) {
          return (
            '<figure class="ngc-b-asset" data-id="' +
            a.id +
            '">' +
            (a.thumb
              ? '<img src="' + esc(a.thumb) + '" alt="" />'
              : '<div class="ngc-b-asset__ph">' + esc(a.mime || '') + '</div>') +
            '<figcaption>' +
            esc(a.title) +
            ' · #' +
            a.id +
            '</figcaption></figure>'
          );
        })
        .join('') +
      '</div></div>';
    var q = document.getElementById('ngc-b-asset-q');
    if (q)
      q.onchange = function () {
        loadAssets(q.value).then(render);
      };
  }

  function render() {
    renderHost();
    renderList();
    renderToolbar();
    renderLayers();
    renderCanvas();
    renderInspector();
  }

  function loadList() {
    return api('documents').then(function (res) {
      state.list = res.items || [];
      render();
    });
  }

  function openDoc(key) {
    return api('documents/' + encodeURIComponent(key)).then(function (row) {
      state.active = row.document_key;
      state.document = row.document;
      state.selectedId = (rootChildren()[0]) || state.document.rootId;
      state.history = [];
      state.future = [];
      state.tab = 'canvas';
      setStatus('Loaded ' + key);
      render();
    });
  }

  function saveDoc() {
    if (!state.document) return;
    api('documents/' + encodeURIComponent(state.document.id), {
      method: 'PUT',
      body: JSON.stringify({
        document: state.document,
        title: (state.document.meta && state.document.meta.title) || state.document.id,
        status: 'draft',
      }),
    })
      .then(function () {
        setStatus('Draft saved');
        return loadList();
      })
      .catch(function (e) {
        setStatus(e.message);
      });
  }

  function publishDoc() {
    if (!state.document) return;
    saveDoc();
    api('documents/' + encodeURIComponent(state.document.id) + '/publish', { method: 'POST' })
      .then(function () {
        setStatus('Published + revision stored');
        return loadList();
      })
      .catch(function (e) {
        setStatus(e.message);
      });
  }

  function duplicateSelected() {
    var n = selectedNode();
    if (!n || !state.document) return;
    pushHistory();
    var nid = n.id + '_copy_' + Math.random().toString(36).slice(2, 6);
    var clone = JSON.parse(JSON.stringify(n));
    clone.id = nid;
    clone.name = (n.name || n.id) + ' copy';
    state.document.nodes[nid] = clone;
    var children = rootChildren().slice();
    var idx = children.indexOf(n.id);
    children.splice(idx + 1, 0, nid);
    state.document.nodes[state.document.rootId].children = children;
    state.selectedId = nid;
    render();
  }

  function loadTokens() {
    return api('tokens').then(function (res) {
      state.tokens = res.tokens;
      state.overlay = res.overlay || {};
      state.tokensCss = res.css || '';
    });
  }

  function loadAssets(search) {
    var q = search ? '?search=' + encodeURIComponent(search) : '';
    return api('assets' + q).then(function (res) {
      state.assets = res.items || [];
    });
  }

  shell();
  api('host')
    .then(function (h) {
      state.host = h;
    })
    .catch(function () {})
    .then(loadList)
    .then(function () {
      if (state.list.length) return openDoc(state.list[0].document_key);
    })
    .catch(function (e) {
      setStatus(e.message);
    });
})();
