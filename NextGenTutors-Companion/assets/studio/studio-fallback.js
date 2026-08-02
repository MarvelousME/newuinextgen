/**
 * Automation Studio — full workflow CRUD + multi-source import UI.
 */
(function () {
  'use strict';
  var cfg = window.NGC_STUDIO || {};
  var root = document.getElementById('ngc-studio-root');
  if (!root || !cfg.restRoot) return;

  var headers = { 'Content-Type': 'application/json', 'X-WP-Nonce': cfg.nonce || '' };
  var list = [];
  var active = null;
  var graph = { nodes: [], edges: [] };
  var filterSource = '';

  function api(path, opts) {
    return fetch(cfg.restRoot + path, Object.assign({ headers: headers, credentials: 'same-origin' }, opts || {})).then(function (r) {
      return r.json().then(function (body) {
        if (!r.ok) throw new Error((body && (body.message || body.code)) || ('HTTP ' + r.status));
        return body;
      });
    });
  }

  function esc(s) {
    var d = document.createElement('div');
    d.textContent = s == null ? '' : String(s);
    return d.innerHTML;
  }

  function status() {
    return document.getElementById('ngc-studio-status');
  }

  function setStatus(msg) {
    var el = status();
    if (el) el.textContent = msg || '';
  }

  function sourceOf(w) {
    return (w.settings && w.settings.source) || (w.template_key ? 'templates' : 'custom');
  }

  root.innerHTML =
    '<div class="ngc-studio-app" data-testid="ngc-studio-app">' +
    '<header class="ngc-studio-app__header">' +
    '<div><h1>Automation Studio</h1><p class="ngc-studio-app__sub">All Hub, Integrate, Orchestrator and template workflows — visible and editable.</p></div>' +
    '<div class="ngc-studio-app__actions">' +
    '<button type="button" class="button button-primary" id="ngc-studio-import" data-testid="ngc-studio-import">Import / Sync All Sources</button>' +
    '<button type="button" class="button" id="ngc-studio-create" data-testid="ngc-studio-create">New Workflow</button>' +
    '</div></header>' +
    '<p id="ngc-studio-status" class="ngc-studio-status" role="status"></p>' +
    '<div class="ngc-studio-layout">' +
    '<aside class="ngc-studio-list-pane">' +
    '<div class="ngc-studio-list-toolbar">' +
    '<input type="search" id="ngc-studio-filter" placeholder="Filter workflows…" data-testid="ngc-studio-filter" />' +
    '<select id="ngc-studio-source-filter" data-testid="ngc-studio-source-filter">' +
    '<option value="">All sources</option>' +
    '<option value="hub">Hub</option>' +
    '<option value="integrate">Integrate</option>' +
    '<option value="orchestrator">Orchestrator</option>' +
    '<option value="templates">Templates</option>' +
    '<option value="custom">Custom</option>' +
    '</select></div>' +
    '<div id="ngc-studio-list" class="ngc-studio-list" data-testid="ngc-studio-list"></div>' +
    '</aside>' +
    '<section class="ngc-studio-editor-pane">' +
    '<div id="ngc-studio-empty" class="ngc-studio-empty">Select or create a workflow.</div>' +
    '<div id="ngc-studio-editor" class="ngc-studio-editor" hidden>' +
    '<div class="ngc-studio-editor__meta">' +
    '<label>Name <input type="text" id="ngc-studio-name" data-testid="ngc-studio-name" /></label>' +
    '<label>Status <select id="ngc-studio-wf-status"><option value="draft">draft</option><option value="published">published</option><option value="paused">paused</option></select></label>' +
    '<span id="ngc-studio-key" class="ngc-studio-key"></span>' +
    '<span id="ngc-studio-src-badge" class="ngc-studio-badge"></span>' +
    '</div>' +
    '<label class="ngc-studio-desc">Description<textarea id="ngc-studio-desc" rows="2"></textarea></label>' +
    '<div class="ngc-studio-editor__toolbar">' +
    '<button type="button" class="button button-primary" id="ngc-studio-save" data-testid="ngc-studio-save">Save</button>' +
    '<button type="button" class="button" id="ngc-studio-publish" data-testid="ngc-studio-publish">Publish</button>' +
    '<button type="button" class="button" id="ngc-studio-sim">Simulate</button>' +
    '<button type="button" class="button button-link-delete" id="ngc-studio-delete" data-testid="ngc-studio-delete">Delete</button>' +
    '</div>' +
    '<div id="ngc-studio-canvas" class="ngc-studio-canvas" data-testid="ngc-studio-canvas"></div>' +
    '<label class="ngc-studio-json-label">Graph JSON (edit nodes/edges)<textarea id="ngc-studio-json" class="ngc-studio-json" data-testid="ngc-studio-json" spellcheck="false"></textarea></label>' +
    '</div></section></div></div>';

  function renderList() {
    var q = (document.getElementById('ngc-studio-filter').value || '').toLowerCase();
    var el = document.getElementById('ngc-studio-list');
    var rows = list.filter(function (w) {
      var src = sourceOf(w);
      if (filterSource && src !== filterSource) return false;
      if (!q) return true;
      return (w.name + ' ' + w.workflow_key + ' ' + src).toLowerCase().indexOf(q) >= 0;
    });
    if (!rows.length) {
      el.innerHTML = '<p class="ngc-studio-empty">No workflows. Click Import / Sync.</p>';
      return;
    }
    el.innerHTML = rows.map(function (w) {
      var activeCls = active && active.id === w.id ? ' is-active' : '';
      return '<button type="button" class="ngc-studio-list-item' + activeCls + '" data-id="' + w.id + '">' +
        '<strong>' + esc(w.name) + '</strong>' +
        '<span class="ngc-studio-list-meta">' + esc(sourceOf(w)) + ' · ' + esc(w.status) + ' · #' + w.id + '</span></button>';
    }).join('');
  }

  function renderCanvas() {
    var canvas = document.getElementById('ngc-studio-canvas');
    canvas.innerHTML = (graph.nodes || []).map(function (n) {
      var label = (n.data && n.data.label) || n.type || n.id;
      return '<div class="ngc-studio-node" title="' + esc(n.type) + '">' + esc(label) + '</div>';
    }).join('') || '<em>No nodes</em>';
    document.getElementById('ngc-studio-json').value = JSON.stringify(graph, null, 2);
  }

  function showEditor(wf) {
    active = wf;
    graph = wf.graph || { nodes: [], edges: [] };
    document.getElementById('ngc-studio-empty').hidden = true;
    document.getElementById('ngc-studio-editor').hidden = false;
    document.getElementById('ngc-studio-name').value = wf.name || '';
    document.getElementById('ngc-studio-desc').value = wf.description || '';
    document.getElementById('ngc-studio-wf-status').value = wf.status || 'draft';
    document.getElementById('ngc-studio-key').textContent = wf.workflow_key || '';
    document.getElementById('ngc-studio-src-badge').textContent = sourceOf(wf);
    renderCanvas();
    renderList();
  }

  function loadList() {
    setStatus('Loading workflows…');
    return api('workflows').then(function (rows) {
      list = Array.isArray(rows) ? rows : [];
      setStatus(list.length + ' workflows loaded');
      renderList();
      if (active) {
        var found = list.find(function (w) { return w.id === active.id; });
        if (found) return api('workflows/' + found.id).then(showEditor);
      }
      if (list.length && !active) {
        return api('workflows/' + list[0].id).then(showEditor);
      }
    }).catch(function (e) { setStatus('Load failed: ' + e.message); });
  }

  document.getElementById('ngc-studio-list').addEventListener('click', function (e) {
    var btn = e.target.closest('[data-id]');
    if (!btn) return;
    api('workflows/' + btn.getAttribute('data-id')).then(showEditor).catch(function (err) {
      setStatus(err.message);
    });
  });

  document.getElementById('ngc-studio-filter').addEventListener('input', renderList);
  document.getElementById('ngc-studio-source-filter').addEventListener('change', function (e) {
    filterSource = e.target.value;
    renderList();
  });

  document.getElementById('ngc-studio-import').addEventListener('click', function () {
    setStatus('Importing Hub + Integrate + Orchestrator + Templates…');
    api('import', { method: 'POST', body: JSON.stringify({ force: true }) }).then(function (res) {
      setStatus('Import done — created ' + (res.created || 0) + ', updated ' + (res.updated || 0) + ', skipped ' + (res.skipped || 0));
      return loadList();
    }).catch(function (e) { setStatus('Import failed: ' + e.message); });
  });

  document.getElementById('ngc-studio-create').addEventListener('click', function () {
    var name = window.prompt('New workflow name', 'Custom Workflow');
    if (!name) return;
    setStatus('Creating…');
    api('workflows', {
      method: 'POST',
      body: JSON.stringify({
        name: name,
        workflow_key: 'custom_' + Date.now().toString(36),
        description: 'Created in Automation Studio',
        graph: { nodes: [{ id: 'start', type: 'START', position: { x: 80, y: 120 }, data: { label: 'Start' } }, { id: 'end', type: 'END', position: { x: 280, y: 120 }, data: { label: 'End' } }], edges: [{ id: 'e1', source: 'start', target: 'end' }] },
        settings: { source: 'custom', editable: true }
      })
    }).then(function (res) {
      if (!res.ok && !res.id) throw new Error(res.message || 'Create failed');
      return loadList().then(function () {
        return api('workflows/' + (res.id || (res.workflow && res.workflow.id))).then(showEditor);
      });
    }).catch(function (e) { setStatus(e.message); });
  });

  document.getElementById('ngc-studio-save').addEventListener('click', function () {
    if (!active) return;
    try {
      graph = JSON.parse(document.getElementById('ngc-studio-json').value);
    } catch (err) {
      setStatus('Invalid graph JSON');
      return;
    }
    setStatus('Saving…');
    api('workflows/' + active.id, {
      method: 'PUT',
      body: JSON.stringify({
        name: document.getElementById('ngc-studio-name').value,
        description: document.getElementById('ngc-studio-desc').value,
        status: document.getElementById('ngc-studio-wf-status').value,
        graph: graph
      })
    }).then(function (res) {
      setStatus(res.ok === false ? (res.message || 'Save failed') : 'Saved');
      return loadList();
    }).catch(function (e) { setStatus(e.message); });
  });

  document.getElementById('ngc-studio-publish').addEventListener('click', function () {
    if (!active) return;
    setStatus('Publishing…');
    api('workflows/' + active.id + '/publish', { method: 'POST' }).then(function () {
      setStatus('Published — triggers active');
      return loadList();
    }).catch(function (e) { setStatus(e.message); });
  });

  document.getElementById('ngc-studio-sim').addEventListener('click', function () {
    if (!active) return;
    setStatus('Simulating…');
    api('workflows/' + active.id + '/simulate', { method: 'POST', body: JSON.stringify({ context: {} }) }).then(function (res) {
      setStatus(res.ok ? 'Simulation OK' : (res.message || 'Simulation failed'));
    }).catch(function (e) { setStatus(e.message); });
  });

  document.getElementById('ngc-studio-delete').addEventListener('click', function () {
    if (!active) return;
    if (!window.confirm('Delete workflow "' + active.name + '"?')) return;
    api('workflows/' + active.id, { method: 'DELETE' }).then(function () {
      active = null;
      document.getElementById('ngc-studio-editor').hidden = true;
      document.getElementById('ngc-studio-empty').hidden = false;
      setStatus('Deleted');
      return loadList();
    }).catch(function (e) { setStatus(e.message); });
  });

  // Auto-import once on open so Hub/Integrate appear immediately.
  api('import', { method: 'POST', body: JSON.stringify({ force: false }) })
    .then(function (res) {
      setStatus('Synced sources — created ' + (res.created || 0) + ', updated ' + (res.updated || 0));
      return loadList();
    })
    .catch(function () { return loadList(); });
})();
