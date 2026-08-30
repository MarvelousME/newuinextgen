import {
  NAV_ITEMS,
  ACTION_FLOW,
  DESIGN_SYSTEM_CARDS,
  CONTROL_PLANE_CARDS,
  PROVIDER_INTERFACES,
  SUBSYSTEMS,
  INFRA_TILES,
  DATA_STATE_TILES,
  SECURITY_ITEMS,
  EVENT_TYPES,
  LIFECYCLE_STAGES,
  LEGEND_ITEMS,
} from './architecture.js';
import { compactCard, ifaceCard, subsystemCard } from './components/primitives.js';
import { mountConnectors } from './components/connectors.js';

const API = window.ECOSYSTEM_API_URL || '';
const SUPER_ADMIN = 'platform-super-admin';

const LIFECYCLE_GLYPH = {
  queued: '◷',
  running: '▶',
  retrying: '↻',
  succeeded: '✓',
  failed: '✕',
  'rolling-back': '↶',
  'rolled-back': '↩',
  cancelled: '⊘',
  amber: '◷',
  blue: '▶',
  orange: '↻',
  green: '✓',
  red: '✕',
  'red-muted': '⊘',
};

async function api(path, options = {}) {
  const base = API || window.location.origin;
  const headers = {
    'Content-Type': 'application/json',
    'X-User-Id': SUPER_ADMIN,
    'X-Correlation-Id': crypto.randomUUID(),
    ...(options.headers || {}),
  };
  const res = await fetch(`${base}${path}`, { ...options, headers });
  const json = await res.json().catch(() => ({}));
  if (!res.ok) {
    throw new Error(json.error || res.statusText);
  }
  return json;
}

function el(tag, className, text) {
  const node = document.createElement(tag);
  if (className) node.className = className;
  if (text) node.textContent = text;
  return node;
}

function staggerEnter(root) {
  const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  root.querySelectorAll('.cc-enter').forEach((node, i) => {
    if (reduced) {
      node.style.opacity = '1';
      return;
    }
    node.style.animationDelay = `${i * 45}ms`;
  });
}

function renderNav(activeId = 'overview') {
  const nav = document.getElementById('cc-nav');
  nav.innerHTML = '';
  NAV_ITEMS.forEach((item) => {
    const li = el('li', 'cc-nav__item');
    const btn = el('button', 'cc-nav__btn', item.label);
    btn.type = 'button';
    btn.dataset.navId = item.id;
    if (item.id === activeId) btn.setAttribute('aria-current', 'page');
    btn.appendChild(el('span', 'cc-nav__dot'));
    btn.addEventListener('click', () => {
      nav.querySelectorAll('.cc-nav__btn').forEach((b) => b.removeAttribute('aria-current'));
      btn.setAttribute('aria-current', 'page');
      scrollToSection(item.id);
    });
    li.appendChild(btn);
    nav.appendChild(li);
  });
}

function scrollToSection(id) {
  const map = {
    overview: '[data-section="design-system"]',
    topology: '[data-section="control-plane"]',
    subsystems: '[data-section="subsystems"]',
    infrastructure: '[data-section="infrastructure"]',
    security: '[data-section="security"]',
    data: '[data-section="data-state"]',
    audit: '#cc-events',
    settings: '#btn-tenant-modal',
  };
  const sel = map[id] || `[data-section="${id}"]`;
  const target = document.querySelector(sel);
  if (target) target.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

function renderActionBar() {
  const bar = document.getElementById('cc-action-bar');
  ACTION_FLOW.forEach((action) => {
    const btn = el('button', `cc-action-bar__btn cc-action-bar__btn--${action.accent}`);
    btn.type = 'button';
    btn.appendChild(el('span', 'cc-action-bar__icon', '◆'));
    btn.appendChild(document.createTextNode(action.label));
    btn.title = action.label;
    bar.appendChild(btn);
  });
}

function renderDesignSystem() {
  const root = document.getElementById('cc-design-system');
  DESIGN_SYSTEM_CARDS.forEach((title) => root.appendChild(compactCard(title)));
}

function renderControlPlane() {
  const root = document.getElementById('cc-control-plane');
  CONTROL_PLANE_CARDS.forEach((item) => root.appendChild(compactCard(item.title, item.desc)));
}

function renderProviders() {
  const root = document.getElementById('cc-providers');
  PROVIDER_INTERFACES.forEach((iface) => root.appendChild(ifaceCard(iface)));
}

function renderSubsystems() {
  const root = document.getElementById('cc-subsystems');
  SUBSYSTEMS.forEach((sub) => root.appendChild(subsystemCard(sub)));
}

function renderInfra() {
  const root = document.getElementById('cc-infrastructure');
  INFRA_TILES.forEach((name) => root.appendChild(compactCard(name)));
}

function renderDataState() {
  const root = document.getElementById('cc-data-state');
  DATA_STATE_TILES.forEach((name) => root.appendChild(compactCard(name)));
}

function renderSecurity() {
  const root = document.getElementById('cc-security');
  SECURITY_ITEMS.forEach((item) => root.appendChild(el('li', '', item)));
}

function renderEvents(extra = []) {
  const root = document.getElementById('cc-events');
  root.innerHTML = '';
  const merged = [...extra, ...EVENT_TYPES].slice(0, 14);
  merged.forEach((ev, i) => {
    const li = el('li');
    li.className = 'cc-event-item cc-enter';
    li.style.animationDelay = `${i * 30}ms`;
    const status = ev.status || 'blue';
    li.appendChild(el('span', `cc-status-dot cc-status-dot--${status}`, ''));
    li.setAttribute('aria-label', `${ev.type || ev.action} (${status})`);
    li.appendChild(document.createTextNode(ev.type || ev.action || 'event'));
    root.appendChild(li);
  });
}

function renderLifecycle() {
  const root = document.getElementById('cc-lifecycle');
  LIFECYCLE_STAGES.forEach((stage) => {
    const li = el('li');
    const glyph = LIFECYCLE_GLYPH[stage.id] || LIFECYCLE_GLYPH[stage.tone] || '•';
    const icon = el('span', 'cc-lifecycle__glyph', glyph);
    icon.setAttribute('aria-hidden', 'true');
    li.appendChild(icon);
    li.appendChild(el('span', `cc-status-dot cc-status-dot--${stage.tone}`, ''));
    const label = el('span', 'cc-lifecycle__label', stage.label);
    label.setAttribute('aria-label', stage.label);
    li.appendChild(label);
    root.appendChild(li);
  });
}

function renderLegend() {
  const root = document.getElementById('cc-legend');
  LEGEND_ITEMS.forEach((item) => {
    const row = el('div', 'cc-legend__item');
    const styleKey = item.style.split('-')[0];
    row.appendChild(el('span', `cc-legend__line cc-legend__line--${styleKey}`, ''));
    row.appendChild(document.createTextNode(item.label));
    root.appendChild(row);
  });
}

function bindTenantModal() {
  const modal = document.getElementById('tenant-modal');
  document.getElementById('btn-tenant-modal').addEventListener('click', () => {
    modal.hidden = false;
    const pre = document.getElementById('tenants-json');
    if (pre.textContent && pre.textContent !== 'Loading…') {
      document.getElementById('create-result').textContent = pre.textContent;
    }
  });
  modal.addEventListener('click', (e) => {
    if (e.target === modal) modal.hidden = true;
  });
  document.getElementById('btn-create').addEventListener('click', async () => {
    const slug = document.getElementById('slug').value.trim();
    const name = document.getElementById('name').value.trim() || slug;
    const out = document.getElementById('create-result');
    out.textContent = 'Provisioning…';
    try {
      const created = await api('/api/v1/platform/tenants', {
        method: 'POST',
        body: JSON.stringify({
          slug,
          name,
          blueprintId: 'education-tutoring',
          ownerUserId: 'tenant-owner-' + slug,
        }),
      });
      const provisioned = await api(`/api/v1/platform/tenants/${created.tenant.id}/provision`, {
        method: 'POST',
      });
      out.textContent = JSON.stringify(provisioned, null, 2);
      loadLiveData();
    } catch (err) {
      out.textContent = String(err.message);
    }
  });
}

async function loadLiveData() {
  const badge = document.getElementById('cc-api-badge');
  try {
    const health = await api('/health');
    const overview = await api('/api/v1/platform/overview');
    const tenants = await api('/api/v1/platform/tenants');
    document.getElementById('tenants-json').textContent = JSON.stringify(tenants.items, null, 2);
    badge.textContent = `${health.status} · ${tenants.items.length} tenants · ${overview.blueprints.length} bp`;
    badge.className = 'cc-header__meta cc-api-status cc-api-status--ok';

    const auditEvents = (overview.auditTail || []).map((row) => ({
      type: row.action || row.eventType || 'audit',
      status: row.result === 'ok' ? 'green' : 'orange',
    }));
    renderEvents(auditEvents);
  } catch (err) {
    badge.textContent = 'API unreachable';
    badge.className = 'cc-header__meta cc-api-status cc-api-status--err';
    renderEvents();
  }
}

function init() {
  renderNav('overview');
  renderActionBar();
  renderDesignSystem();
  renderControlPlane();
  renderProviders();
  renderSubsystems();
  renderInfra();
  renderDataState();
  renderSecurity();
  renderLifecycle();
  renderLegend();
  mountConnectors(document.getElementById('cc-connector-layer'));
  bindTenantModal();
  staggerEnter(document.getElementById('cc-workspace-inner'));
  loadLiveData();
}

init();
