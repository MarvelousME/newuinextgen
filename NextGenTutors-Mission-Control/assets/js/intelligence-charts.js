/**
 * Advanced charts: Sankey, network, geo bubble, radar, funnel.
 */
(function (global) {
  'use strict';

  var charts = {};

  function renderSankey(canvas, data) {
    if (!canvas || !data || !data.links) return;
    var ctx = canvas.getContext('2d');
    var w = canvas.width = canvas.clientWidth || 400;
    var h = canvas.height = canvas.clientHeight || 220;
    ctx.clearRect(0, 0, w, h);
    var cols = { domain: w * 0.2, plugin: w * 0.5, outcome: w * 0.8 };
    var groups = { domain: {}, plugin: {}, outcome: {} };
    data.links.forEach(function (l) {
      var sk = l.source.split(':')[0];
      groups[sk][l.source] = (groups[sk][l.source] || 0) + (l.value || 1);
    });
    Object.keys(groups).forEach(function (g) {
      var y = 20;
      Object.keys(groups[g]).forEach(function (node) {
        var val = groups[g][node];
        var x = cols[g] || w / 2;
        ctx.fillStyle = '#2563eb';
        ctx.fillRect(x - 40, y, 80, Math.min(60, 8 + val));
        ctx.fillStyle = '#0f172a';
        ctx.font = '11px sans-serif';
        ctx.fillText(node.split(':')[1] || node, x - 38, y + 70);
        y += 80;
      });
    });
    ctx.strokeStyle = 'rgba(37,99,235,0.25)';
    data.links.slice(0, 40).forEach(function (l) {
      var sx = cols[l.source.split(':')[0]] || 0;
      var tx = cols[l.target.split(':')[0]] || w;
      ctx.beginPath();
      ctx.moveTo(sx + 40, h / 2);
      ctx.bezierCurveTo(sx + 80, h / 2, tx - 80, h / 2, tx - 40, h / 2);
      ctx.stroke();
    });
  }

  function renderNetwork(container, data) {
    if (!container || !global.vis || !data) return;
    var nodes = (data.nodes || []).map(function (n) {
      return { id: n.id, label: n.label, value: n.value || 1, group: n.group || 'plugin' };
    });
    var edges = (data.edges || []).map(function (e) {
      return { from: e.from, to: e.to, value: e.value || 1 };
    });
    if (charts.network) charts.network.destroy();
    charts.network = new vis.Network(container, { nodes: new vis.DataSet(nodes), edges: new vis.DataSet(edges) }, {
      physics: { stabilization: true },
      nodes: { shape: 'dot', scaling: { min: 10, max: 30 } },
    });
  }

  function renderGeoBubble(canvas, bubbles) {
    if (!canvas || typeof Chart === 'undefined') return;
    var id = canvas.id;
    var labels = (bubbles || []).map(function (b) { return b.label || b.domain; });
    var data = (bubbles || []).map(function (b) { return { x: b.lng, y: b.lat, r: b.r }; });
    if (charts[id]) charts[id].destroy();
    charts[id] = new Chart(canvas, {
      type: 'bubble',
      data: {
        labels: labels,
        datasets: [{
          label: 'Regional activity',
          data: data,
          backgroundColor: 'rgba(124,58,237,0.5)',
        }],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
          x: { title: { display: true, text: 'Longitude' }, min: 16, max: 33 },
          y: { title: { display: true, text: 'Latitude' }, min: -35, max: -22 },
        },
      },
    });
  }

  function renderRadar(canvas, radar) {
    if (!canvas || typeof Chart === 'undefined' || !radar) return;
    var id = canvas.id;
    if (charts[id]) charts[id].destroy();
    charts[id] = new Chart(canvas, {
      type: 'radar',
      data: radar,
      options: { responsive: true, maintainAspectRatio: false, scales: { r: { beginAtZero: true, max: 100 } } },
    });
  }

  function renderFunnel(canvas, funnel) {
    if (!canvas || typeof Chart === 'undefined') return;
    var id = canvas.id;
    if (charts[id]) charts[id].destroy();
    charts[id] = new Chart(canvas, {
      type: 'bar',
      data: {
        labels: (funnel || []).map(function (f) { return f.stage; }),
        datasets: [{ label: 'Funnel', data: (funnel || []).map(function (f) { return f.count; }), backgroundColor: '#059669' }],
      },
      options: { indexAxis: 'y', responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } },
    });
  }

  function renderAll(payload) {
    if (!payload) return;
    renderSankey(document.getElementById('ngtmc-chart-sankey'), payload.sankey);
    renderNetwork(document.getElementById('ngtmc-network-graph'), payload.network);
    renderGeoBubble(document.getElementById('ngtmc-chart-geo'), payload.geo);
    renderRadar(document.getElementById('ngtmc-chart-radar'), payload.radar);
    renderFunnel(document.getElementById('ngtmc-chart-funnel'), payload.funnel);
  }

  global.NGTMCIntelCharts = { renderAll: renderAll, charts: charts };
})(window);
