/**
 * Automation Studio fallback UI (no React build required).
 */
(function () {
  const cfg = window.NGC_STUDIO || {};
  const root = document.getElementById('ngc-studio-root');
  if (!root || !cfg.restRoot) return;

  const headers = {
    'Content-Type': 'application/json',
    'X-WP-Nonce': cfg.nonce || '',
  };

  async function api(path, opts) {
    const res = await fetch(cfg.restRoot + path, Object.assign({ headers }, opts || {}));
    return res.json();
  }

  root.innerHTML =
    '<div class="ngc-studio-fallback">' +
    '<header><h2>Automation Studio</h2><span id="ngc-studio-fb-status"></span></header>' +
    '<div class="ngc-studio-fb-toolbar">' +
    '<select id="ngc-studio-fb-wf"></select>' +
    '<button type="button" id="ngc-studio-fb-save">Save & Apply</button>' +
    '<button type="button" id="ngc-studio-fb-publish">Publish</button>' +
    '<button type="button" id="ngc-studio-fb-sim">Simulate</button>' +
    '</div>' +
    '<div id="ngc-studio-fb-canvas" class="ngc-studio-fb-canvas"></div>' +
    '<pre id="ngc-studio-fb-json" class="ngc-studio-fb-json"></pre>' +
    '</div>';

  const style = document.createElement('style');
  style.textContent =
    '.ngc-studio-fallback{font-family:Inter,system-ui,sans-serif;padding:16px}' +
    '.ngc-studio-fb-toolbar{display:flex;gap:8px;align-items:center;margin:12px 0}' +
    '.ngc-studio-fb-toolbar button{background:#123c7c;color:#fff;border:0;border-radius:8px;padding:8px 12px;cursor:pointer}' +
    '.ngc-studio-fb-canvas{display:flex;flex-wrap:wrap;gap:10px;min-height:200px;padding:12px;background:#fff;border:1px solid #e6edf7;border-radius:12px}' +
    '.ngc-studio-fb-node{padding:10px 14px;border:2px solid #123c7c;border-radius:10px;background:#f6f9ff;font-weight:700;font-size:12px}' +
    '.ngc-studio-fb-json{margin-top:12px;padding:12px;background:#07172f;color:#d8f4ff;border-radius:10px;max-height:240px;overflow:auto}';
  document.head.appendChild(style);

  let activeId = null;
  let graph = { nodes: [], edges: [] };

  function setStatus(msg) {
    const el = document.getElementById('ngc-studio-fb-status');
    if (el) el.textContent = msg;
  }

  function renderCanvas() {
    const canvas = document.getElementById('ngc-studio-fb-canvas');
    const json = document.getElementById('ngc-studio-fb-json');
    if (!canvas) return;
    canvas.innerHTML = (graph.nodes || [])
      .map(function (n) {
        return '<div class="ngc-studio-fb-node">' + (n.data && n.data.label ? n.data.label : n.type) + '</div>';
      })
      .join('');
    if (json) json.textContent = JSON.stringify(graph, null, 2);
  }

  async function loadList() {
    const list = await api('workflows');
    const sel = document.getElementById('ngc-studio-fb-wf');
    if (!sel) return;
    sel.innerHTML = list
      .map(function (w) {
        return '<option value="' + w.id + '">' + w.name + ' (' + w.status + ')</option>';
      })
      .join('');
    if (list.length) {
      activeId = list[0].id;
      await loadWorkflow(activeId);
    }
  }

  async function loadWorkflow(id) {
    const wf = await api('workflows/' + id);
    graph = wf.graph || { nodes: [], edges: [] };
    renderCanvas();
  }

  document.getElementById('ngc-studio-fb-wf').addEventListener('change', function (e) {
    activeId = Number(e.target.value);
    loadWorkflow(activeId);
  });

  document.getElementById('ngc-studio-fb-save').addEventListener('click', async function () {
    if (!activeId) return;
    setStatus('Saving…');
    const result = await api('workflows/' + activeId, {
      method: 'PUT',
      body: JSON.stringify({ graph: graph }),
    });
    setStatus(result.ok ? 'Saved & applied' : 'Failed');
  });

  document.getElementById('ngc-studio-fb-publish').addEventListener('click', async function () {
    if (!activeId) return;
    setStatus('Publishing…');
    const result = await api('workflows/' + activeId + '/publish', { method: 'POST' });
    setStatus(result.ok ? 'Published' : 'Failed');
    loadList();
  });

  document.getElementById('ngc-studio-fb-sim').addEventListener('click', async function () {
    if (!activeId) return;
    setStatus('Simulating…');
    const result = await api('workflows/' + activeId + '/simulate', {
      method: 'POST',
      body: JSON.stringify({ context: {} }),
    });
    setStatus(result.ok ? 'Simulation OK' : 'Simulation failed');
  });

  loadList();
})();
