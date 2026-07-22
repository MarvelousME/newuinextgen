(function () {
  'use strict';

  var cfg = window.NGC_ADMIN || { rest: '/wp-json/ngc/v1', nonce: '' };

  function api(path, method, body) {
    return fetch(cfg.rest + path, {
      method: method || 'GET',
      headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': cfg.nonce },
      body: body ? JSON.stringify(body) : undefined
    }).then(function (res) { return res.json().then(function (j) { return { ok: res.ok, json: j }; }); });
  }

  function esc(s) {
    return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
  }

  function msg(text, err) {
    var b = document.querySelector('.ngc-ai-msg');
    if (b) { b.textContent = text; b.className = 'ngc-ai-msg' + (err ? ' is-error' : ' is-ok'); }
  }

  function val(sel) { var el = document.querySelector(sel); return el ? el.value : ''; }

  var chatHistory = [];

  document.addEventListener('click', function (e) {
    var tab = e.target.closest('.ngc-ai-tab');
    if (tab) {
      var key = tab.dataset.aiTab;
      document.querySelectorAll('.ngc-ai-tab').forEach(function (t) { t.classList.toggle('is-active', t === tab); });
      document.querySelectorAll('.ngc-ai-panel').forEach(function (p) {
        var on = p.dataset.aiPanel === key;
        p.classList.toggle('is-active', on);
        if (on) { p.removeAttribute('hidden'); } else { p.setAttribute('hidden', ''); }
      });
      return;
    }

    var act = e.target.closest('[data-ai-action]');
    if (!act) { return; }
    e.preventDefault();
    var a = act.dataset.aiAction;
    if (a === 'save-model') { saveModel(); }
    else if (a === 'save-agent') { saveAgent(); }
    else if (a === 'send') { sendChat(); }
  });

  function saveModel() {
    var payload = {
      label: val('[data-ai-model="label"]'),
      base_url: val('[data-ai-model="base_url"]'),
      model: val('[data-ai-model="model"]'),
      api_key: val('[data-ai-model="api_key"]')
    };
    api('/ai/models', 'POST', payload).then(function (out) {
      msg(out.ok ? 'Model saved.' : ((out.json.data && out.json.data.message) || 'Failed.'), !out.ok);
      if (out.ok) {
        document.querySelectorAll('[data-ai-model]').forEach(function (i) { i.value = ''; });
        loadModels();
      }
    });
  }

  function loadModels() {
    var box = document.getElementById('ngc-ai-models');
    if (!box) { return; }
    api('/ai/models').then(function (out) {
      var list = (out.json.data || []);
      box.innerHTML = list.length ? ('<table class="widefat striped"><thead><tr><th>Label</th><th>Model</th><th>Endpoint</th><th>Key</th><th></th></tr></thead><tbody>' +
        list.map(function (m) {
          return '<tr><td>' + esc(m.label) + '</td><td><code>' + esc(m.model) + '</code></td><td>' + esc(m.base_url) +
            '</td><td>' + (m.has_key ? '✓' : '—') + '</td><td>' +
            '<button type="button" class="button button-small" data-test="' + esc(m.id) + '">Test key</button> ' +
            '<button type="button" class="button button-small" data-delm="' + esc(m.id) + '">Delete</button></td></tr>';
        }).join('') + '</tbody></table>') : '<em>No models yet.</em>';
      populateModelSelects(list);
    });
  }

  function populateModelSelects(list) {
    var el = document.querySelector('[data-ai-agent="model_id"]');
    if (el) {
      el.innerHTML = '<option value="">— select —</option>' + list.map(function (m) {
        return '<option value="' + esc(m.id) + '">' + esc(m.label) + '</option>';
      }).join('');
    }
  }

  function loadSkills() {
    api('/ai/skills').then(function (out) {
      var skills = out.json.data || {};
      var box = document.getElementById('ngc-ai-skills');
      if (!box) { return; }
      box.innerHTML = Object.keys(skills).map(function (k) {
        var s = skills[k];
        return '<label style="display:block"><input type="checkbox" data-ai-skill="' + esc(k) + '"> ' + esc(s.label) +
          (s.mutating ? ' <em>(approval required)</em>' : '') + '</label>';
      }).join('');
    });
  }

  function saveAgent() {
    var skills = [];
    document.querySelectorAll('[data-ai-skill]:checked').forEach(function (c) { skills.push(c.dataset.aiSkill); });
    var payload = {
      name: val('[data-ai-agent="name"]'),
      model_id: val('[data-ai-agent="model_id"]'),
      role: val('[data-ai-agent="role"]'),
      rules: val('[data-ai-agent="rules"]'),
      skills: skills
    };
    api('/ai/agents', 'POST', payload).then(function (out) {
      msg(out.ok ? 'Agent saved.' : ((out.json.data && out.json.data.message) || 'Failed.'), !out.ok);
      if (out.ok) { loadAgents(); }
    });
  }

  function loadAgents() {
    var box = document.getElementById('ngc-ai-agents');
    if (!box) { return; }
    api('/ai/agents').then(function (out) {
      var list = out.json.data || [];
      box.innerHTML = list.length ? ('<table class="widefat striped"><thead><tr><th>Name</th><th>Role</th><th>Model</th><th>Skills</th><th></th></tr></thead><tbody>' +
        list.map(function (a) {
          return '<tr><td>' + esc(a.name) + '</td><td>' + esc(a.role) + '</td><td><code>' + esc(a.model_id) +
            '</code></td><td>' + esc((a.skills || []).join(', ')) + '</td><td><button type="button" class="button button-small" data-dela="' + esc(a.id) + '">Delete</button></td></tr>';
        }).join('') + '</tbody></table>') : '<em>No agents yet.</em>';
      var sel = document.getElementById('ngc-ai-chat-agent');
      if (sel) {
        sel.innerHTML = list.map(function (a) {
          return '<option value="' + esc(a.id) + '">' + esc(a.name) + '</option>';
        }).join('');
      }
    });
  }

  function appendLog(who, text) {
    var log = document.getElementById('ngc-ai-log');
    if (!log) { return; }
    var div = document.createElement('div');
    div.className = 'ngc-ai-turn';
    div.innerHTML = '<b>' + esc(who) + ':</b> ' + esc(text).replace(/\n/g, '<br>');
    log.appendChild(div);
    log.scrollTop = log.scrollHeight;
  }

  function sendChat() {
    var input = document.getElementById('ngc-ai-input');
    if (!input) { return; }
    var text = input.value.trim();
    if (!text) { return; }
    var swarmEl = document.getElementById('ngc-ai-swarm');
    var swarm = swarmEl && swarmEl.checked;
    appendLog('You', text);
    input.value = '';

    var payload = { message: text, history: chatHistory };
    if (swarm) {
      api('/ai/agents').then(function (out) {
        payload.agent_ids = (out.json.data || []).map(function (a) { return a.id; });
        doChat(payload, swarm, text);
      });
    } else {
      var agentSel = document.getElementById('ngc-ai-chat-agent');
      payload.agent_id = agentSel ? agentSel.value : '';
      doChat(payload, swarm, text);
    }
  }

  function doChat(payload, swarm, userText) {
    appendLog('…', 'thinking');
    api('/ai/chat', 'POST', payload).then(function (out) {
      var log = document.getElementById('ngc-ai-log');
      if (log && log.lastChild) { log.removeChild(log.lastChild); }
      if (!out.ok) {
        appendLog('Error', (out.json.data && out.json.data.message) || 'Failed.');
        return;
      }
      var d = out.json.data;
      if (swarm) {
        (d.transcript || []).forEach(function (t) { appendLog(t.agent, t.content); });
        appendLog(d.orchestrator || 'Orchestrator', d.final || '');
        chatHistory.push({ role: 'user', content: userText });
        chatHistory.push({ role: 'assistant', content: d.final || '' });
      } else {
        appendLog(d.agent || 'Agent', d.content || '');
        chatHistory.push({ role: 'user', content: userText });
        chatHistory.push({ role: 'assistant', content: d.content || '' });
      }
    });
  }

  document.addEventListener('click', function (e) {
    var t = e.target;
    if (t.dataset && t.dataset.test) {
      msg('Testing…');
      api('/ai/models/test', 'POST', { id: t.dataset.test }).then(function (out) {
        if (out.ok) {
          msg('Key OK (' + (out.json.data.latency || 0) + 'ms): ' + (out.json.data.sample || ''));
        } else {
          msg((out.json.data && out.json.data.message) || 'Test failed.', true);
        }
      });
    } else if (t.dataset && t.dataset.delm) {
      var delModel = function () {
        api('/ai/models/delete', 'POST', { id: t.dataset.delm }).then(function () { loadModels(); });
      };
      if (window.NGCDialog) {
        window.NGCDialog.confirm({
          title: 'Delete model',
          message: 'Delete this model and its key? This cannot be undone.',
          confirmLabel: 'Delete',
          danger: true,
        }).then(function (ok) { if (ok) { delModel(); } });
      } else if (window.confirm('Delete this model and its key?')) {
        delModel();
      }
    } else if (t.dataset && t.dataset.dela) {
      var delAgent = function () {
        api('/ai/agents/delete', 'POST', { id: t.dataset.dela }).then(function () { loadAgents(); });
      };
      if (window.NGCDialog) {
        window.NGCDialog.confirm({
          title: 'Delete agent',
          message: 'Delete this agent? This cannot be undone.',
          confirmLabel: 'Delete',
          danger: true,
        }).then(function (ok) { if (ok) { delAgent(); } });
      } else if (window.confirm('Delete this agent?')) {
        delAgent();
      }
    }
  });

  document.addEventListener('DOMContentLoaded', function () {
    loadModels();
    loadAgents();
    loadSkills();
  });
})();
