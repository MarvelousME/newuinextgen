import { ECC_SCREENS, ECC_ACTIONS, getEconomicScreen } from './economic-catalog.js';
import {
  dataGrid,
  metricCard,
  unavailableBanner,
  textField,
  moneyField,
  statusPill,
  moneyEl,
  openDrawer,
  formatMoney,
} from './components/economic.js';
import { enableKinetic } from './components/kinetic.js';

const TENANT_KEY = 'ecc_tenant_id';
const ACTION_ACCENT = Object.fromEntries(ECC_ACTIONS.map((a) => [a.id, a.accent]));

/** @type {{ api: Function, getToken: Function, el: Function, showTokenModal: Function, renderEvents: Function, staggerEnter: Function } | null} */
let ctx = null;
let catalog = { screens: [], grants: {} };
let overview = null;
let tenants = [];

export function parseEconomicHash() {
  const hash = window.location.hash || '#/';
  const match = hash.match(/^#\/economic(?:\/([a-z0-9-]+))?/i);
  if (!match) {
    return { product: 'architecture', screenId: null };
  }
  return { product: 'economic', screenId: match[1] || 'overview' };
}

export function bindEconomic(runtime) {
  ctx = runtime;
  ensureProductSwitch();
  window.addEventListener('hashchange', syncFromHash);
  syncFromHash();
}

function ensureProductSwitch() {
  const actions = document.querySelector('.cc-header__actions');
  if (!actions || document.getElementById('cc-product-switch')) {
    return;
  }
  const wrap = ctx.el('div', 'cc-product-switch');
  wrap.id = 'cc-product-switch';
  wrap.setAttribute('role', 'tablist');
  wrap.setAttribute('aria-label', 'Control Center product');
  wrap.appendChild(productBtn('architecture', 'Architecture'));
  wrap.appendChild(productBtn('economic', 'Economic'));
  const tenant = document.createElement('select');
  tenant.id = 'ecc-tenant';
  tenant.className = 'cc-header__tenant';
  tenant.setAttribute('aria-label', 'Tenant');
  tenant.hidden = true;
  tenant.addEventListener('change', () => {
    sessionStorage.setItem(TENANT_KEY, tenant.value);
    if (parseEconomicHash().product === 'economic') {
      loadEconomic(parseEconomicHash().screenId);
    }
  });
  actions.prepend(tenant);
  actions.prepend(wrap);
}

function productBtn(id, label) {
  const btn = ctx.el('button', 'cc-product-switch__btn', label);
  btn.type = 'button';
  btn.dataset.product = id;
  btn.addEventListener('click', () => {
    window.location.hash = id === 'economic' ? '#/economic' : '#/';
  });
  return btn;
}

function syncFromHash() {
  const route = parseEconomicHash();
  document.body.classList.toggle('cc-shell--economic', route.product === 'economic');
  document.querySelectorAll('.cc-product-switch__btn').forEach((btn) => {
    btn.setAttribute('aria-current', btn.dataset.product === route.product ? 'true' : 'false');
  });
  const title = document.querySelector('.cc-header__title');
  const sub = document.querySelector('.cc-header__subtitle');
  const tenantSel = document.getElementById('ecc-tenant');
  if (route.product === 'economic') {
    if (title) title.textContent = 'ECONOMIC CONTROL CENTER';
    if (tenantSel) tenantSel.hidden = false;
    loadEconomic(route.screenId);
  } else {
    if (title) title.textContent = 'ECOSYSTEM CONTROL CENTER';
    if (sub) {
      sub.textContent = 'Sovereign Multi-Tenant Agentic Architecture';
      sub.classList.remove('cc-header__context');
    }
    if (tenantSel) tenantSel.hidden = true;
    ctx.restoreArchitecture?.();
  }
}

function selectedTenant() {
  return sessionStorage.getItem(TENANT_KEY) || tenants[0]?.id || '';
}

function qs() {
  const tenantId = selectedTenant();
  return tenantId ? `?tenantId=${encodeURIComponent(tenantId)}` : '';
}

function screenState(id) {
  return catalog.screens.find((s) => s.id === id) || null;
}

function can(permission) {
  return Boolean(catalog.grants?.[permission]);
}

async function loadEconomic(screenId) {
  renderEconomicNav(screenId);
  const page = document.getElementById('ecc-page');
  if (!page) return;
  page.innerHTML = '';
  page.appendChild(ctx.el('p', 'ecc-help', 'Loading…'));

  try {
    if (!ctx.getToken()) {
      page.innerHTML = '';
      page.appendChild(unavailableBanner('Authentication required. Set the platform API token to load live economic data.'));
      renderEconomicNav(screenId);
      renderEconomicActions(screenId);
      return;
    }
    catalog = await ctx.api(`/api/v1/economic/catalog${qs()}`);
    overview = await ctx.api(`/api/v1/economic/overview${qs()}`);
    try {
      const t = await ctx.api('/api/v1/platform/tenants');
      tenants = t.items || [];
      fillTenantSelect();
    } catch {
      tenants = [];
    }
    updateHeaderContext();
    renderEconomicNav(screenId);
    renderEconomicActions(screenId);
    await renderScreen(screenId);
    const auditEvents = (overview.auditTail || []).map((row) => ({
      type: row.action || row.eventType || 'audit',
      status: row.result === 'ok' ? 'green' : 'orange',
    }));
    ctx.renderEvents(auditEvents);
  } catch (err) {
    page.innerHTML = '';
    page.appendChild(unavailableBanner(String(err.message || err)));
  }
}

function fillTenantSelect() {
  const sel = document.getElementById('ecc-tenant');
  if (!sel) return;
  const current = sessionStorage.getItem(TENANT_KEY) || tenants[0]?.id || '';
  sel.innerHTML = '';
  if (!tenants.length) {
    const opt = document.createElement('option');
    opt.value = '';
    opt.textContent = 'No tenants';
    sel.appendChild(opt);
    return;
  }
  tenants.forEach((t) => {
    const opt = document.createElement('option');
    opt.value = t.id;
    opt.textContent = `${t.slug} · ${t.status}`;
    sel.appendChild(opt);
  });
  if (current && tenants.some((t) => t.id === current)) {
    sel.value = current;
  } else {
    sel.value = tenants[0].id;
    sessionStorage.setItem(TENANT_KEY, sel.value);
  }
}

function updateHeaderContext() {
  const sub = document.querySelector('.cc-header__subtitle');
  if (!sub || !overview) return;
  const tenant = tenants.find((t) => t.id === selectedTenant());
  const env = overview.environment || 'local';
  const currency = overview.currency || 'ZAR';
  const fy = overview.fiscalPeriod || 'FY2026';
  sub.textContent = `${env} · tenant: ${tenant?.slug || 'none'} · ${currency} · ${fy}`;
  sub.classList.add('cc-header__context');
}

function renderEconomicNav(activeId) {
  const nav = document.getElementById('cc-nav');
  const title = document.querySelector('.cc-sidebar__title');
  if (title) title.textContent = 'ECONOMIC MODULES';
  if (!nav) return;
  nav.innerHTML = '';
  ECC_SCREENS.forEach((item) => {
    const state = screenState(item.id);
    const li = ctx.el('li', 'cc-nav__item');
    const btn = ctx.el('button', 'cc-nav__btn', item.label);
    btn.type = 'button';
    btn.dataset.navId = item.id;
    if (item.id === activeId) btn.setAttribute('aria-current', 'page');
    if (state && !state.registered) btn.title = state.reason;
    btn.appendChild(ctx.el('span', 'cc-nav__dot'));
    btn.addEventListener('click', () => {
      window.location.hash = `#/economic/${item.id}`;
    });
    li.appendChild(btn);
    nav.appendChild(li);
  });
}

function renderEconomicActions(screenId) {
  const bar = document.getElementById('cc-action-bar');
  if (!bar) return;
  bar.innerHTML = '';
  const meta = getEconomicScreen(screenId);
  const state = screenState(screenId);
  const allowed = meta.actions || [];
  ECC_ACTIONS.forEach((action) => {
    if (!allowed.includes(action.id)) return;
    if (state && !state.available && action.id !== 'audit' && action.id !== 'observe') return;
    const btn = ctx.el('button', `cc-action-bar__btn cc-action-bar__btn--${ACTION_ACCENT[action.id] || 'cyan'}`);
    btn.type = 'button';
    btn.appendChild(ctx.el('span', 'cc-action-bar__icon', '◆'));
    btn.appendChild(document.createTextNode(action.label));
    btn.addEventListener('click', () => runAction(action.id, screenId));
    bar.appendChild(btn);
  });
}

async function runAction(actionId, screenId) {
  if (actionId === 'audit') {
    window.location.hash = '#/economic/audit';
    return;
  }
  if (actionId === 'report') {
    window.location.hash = '#/economic/reports';
    return;
  }
  if (actionId === 'observe') {
    return;
  }
  try {
    await ctx.api('/api/v1/economic/actions', {
      method: 'POST',
      body: JSON.stringify({ action: `${screenId}.${actionId}`, tenantId: selectedTenant() }),
    });
  } catch (err) {
    openDrawer({ title: 'Action unavailable', body: String(err.message || err) });
  }
}

async function renderScreen(screenId) {
  const page = document.getElementById('ecc-page');
  page.innerHTML = '';
  const meta = getEconomicScreen(screenId);
  const state = screenState(screenId);
  page.appendChild(ctx.el('h2', 'ecc-page-title', meta.label));

  const loaders = {
    overview: renderOverview,
    customers: renderCustomers,
    invoices: renderInvoices,
    billing: renderInvoices,
    audit: renderAudit,
    currencies: renderCurrencies,
    settings: renderSettings,
    reports: renderReports,
    compliance: renderAudit,
    revenue: renderRevenue,
    vendors: renderVendors,
    pricing: renderPricing,
    automation: renderAutomation,
  };

  if (loaders[screenId]) {
    await loaders[screenId](page, state);
    ctx.staggerEnter(page);
    enableKinetic(page);
    return;
  }

  if (!state?.available) {
    page.appendChild(unavailableBanner(state?.reason || `Unavailable — ${meta.capability} capability not registered.`));
    ctx.staggerEnter(page);
    return;
  }

  page.appendChild(
    unavailableBanner(`This ${meta.label} workspace is registered but has no dedicated HTTP collection yet.`)
  );
}

async function renderOverview(page) {
  const kpis = overview?.kpis || [];
  const grid = ctx.el('div', 'ecc-kpi-grid');
  kpis.forEach((kpi) => {
    let value = '—';
    if (kpi.available && kpi.unit === 'count') value = String(kpi.value ?? 0);
    if (kpi.available && kpi.amountMinor != null) value = formatMoney(kpi.amountMinor, kpi.currency || overview.currency);
    grid.appendChild(
      metricCard({
        label: kpi.label,
        value,
        available: kpi.available,
        reason: kpi.reason,
        hint: kpi.unit === 'count' ? 'Live count' : '',
      })
    );
  });
  page.appendChild(grid);

  const split = ctx.el('div', 'cc-split-row');
  const ops = document.createElement('section');
  ops.className = 'cc-panel cc-panel--blue';
  ops.appendChild(ctx.el('h3', 'ecc-section-title', 'Gateway / ERP health'));
  ops.appendChild(ctx.el('p', '', `Odoo: ${overview?.odoo?.status || 'UNKNOWN'} · mode ${overview?.odoo?.mode || 'n/a'}`));
  ops.appendChild(ctx.el('p', 'ecc-help', `${overview?.tenantCount ?? 0} tenants · ${overview?.capabilities?.unregistered?.length ?? 0} economic capabilities unregistered`));
  split.appendChild(ops);
  const events = document.createElement('section');
  events.className = 'cc-panel cc-panel--purple';
  events.appendChild(ctx.el('h3', 'ecc-section-title', 'Recent financial events'));
  const list = document.createElement('ul');
  list.className = 'cc-event-list';
  (overview?.auditTail || []).slice(0, 8).forEach((row) => {
    const li = document.createElement('li');
    li.className = 'cc-event-item';
    li.appendChild(statusPill(row.result || 'INFO'));
    li.appendChild(document.createTextNode(` ${row.action || row.eventType}`));
    list.appendChild(li);
  });
  if (!overview?.auditTail?.length) {
    list.appendChild(ctx.el('li', 'ecc-help', 'No audit events yet.'));
  }
  events.appendChild(list);
  split.appendChild(events);
  page.appendChild(split);
}

async function renderCustomers(page, state) {
  if (!can('customers.read')) {
    page.appendChild(unavailableBanner('Unavailable — permission denied.'));
    return;
  }
  if (!selectedTenant()) {
    page.appendChild(unavailableBanner('Select a tenant with CRM provisioned.'));
    return;
  }
  const data = await ctx.api(`/api/v1/economic/customers${qs()}`);
  page.appendChild(
    dataGrid({
      columns: [
        { key: 'name', label: 'Name' },
        { key: 'email', label: 'Email' },
        { key: 'phone', label: 'Phone' },
        { key: 'company', label: 'Company' },
        { key: 'id', label: 'ID' },
      ],
      rows: data.items || [],
      empty: 'No customers in this tenant.',
      onRow: (row) => showRecordDrawer('Customer', row),
    })
  );
  if (can('customers.create')) {
    page.appendChild(customerForm());
  }
}

async function renderInvoices(page) {
  if (!can('invoices.read')) {
    page.appendChild(unavailableBanner('Unavailable — permission denied.'));
    return;
  }
  if (!selectedTenant()) {
    page.appendChild(unavailableBanner('Select a tenant with invoicing provisioned.'));
    return;
  }
  const data = await ctx.api(`/api/v1/economic/invoices${qs()}`);
  page.appendChild(
    ctx.el(
      'p',
      'ecc-help',
      'Invoice documents from the Odoo invoicing adapter. Posted invoices are not editable here; corrections require reversal/adjustment once ledger posting is registered.'
    )
  );
  page.appendChild(
    dataGrid({
      columns: [
        { key: 'reference', label: 'Reference' },
        { key: 'customer', label: 'Customer' },
        { key: 'amountMinor', label: 'Amount', money: true },
        { key: 'currency', label: 'Currency' },
        { key: 'status', label: 'Status', status: true },
        { key: 'date', label: 'Date' },
        { key: 'id', label: 'ID' },
      ],
      rows: data.items || [],
      empty: 'No invoices in this tenant.',
      currency: overview?.currency || 'ZAR',
      onRow: (row) => showRecordDrawer('Invoice', row),
    })
  );
  if (can('invoices.create')) {
    page.appendChild(invoiceForm());
  }
}

async function renderAudit(page) {
  if (!can('audit.read')) {
    page.appendChild(unavailableBanner('Unavailable — permission denied.'));
    return;
  }
  const data = await ctx.api(`/api/v1/economic/audit${qs()}`);
  page.appendChild(ctx.el('p', 'ecc-help', 'Audit is read-only. Destructive CRUD over audit data is not provided.'));
  page.appendChild(
    dataGrid({
      columns: [
        { key: 'timestamp', label: 'Timestamp' },
        { key: 'actorId', label: 'Actor' },
        { key: 'tenantId', label: 'Tenant' },
        { key: 'action', label: 'Action' },
        { key: 'resource', label: 'Resource' },
        { key: 'result', label: 'Result', status: true },
        { key: 'correlationId', label: 'Correlation' },
      ],
      rows: data.items || [],
      empty: 'No audit records.',
      onRow: (row) => showRecordDrawer('Audit event', row),
    })
  );
}

async function renderCurrencies(page) {
  const data = await ctx.api(`/api/v1/economic/currencies${qs()}`);
  page.appendChild(ctx.el('p', 'ecc-help', data.note || ''));
  page.appendChild(
    dataGrid({
      columns: [
        { key: 'code', label: 'Code' },
        { key: 'symbol', label: 'Symbol' },
        { key: 'precision', label: 'Precision' },
        { key: 'status', label: 'Status', status: true },
        { key: 'baseCurrency', label: 'Base' },
        { key: 'settlementSupport', label: 'Settlement' },
        { key: 'source', label: 'Source' },
      ],
      rows: data.items || [],
    })
  );
}

async function renderSettings(page) {
  const cfg = overview?.configured || {};
  const panel = document.createElement('section');
  panel.className = 'cc-panel cc-panel--purple';
  panel.appendChild(ctx.el('h3', 'ecc-section-title', 'Configured platform values'));
  const dl = document.createElement('dl');
  dl.className = 'ecc-kv';
  [
    ['Environment', cfg.environment],
    ['Base currency', cfg.currency],
    ['Fiscal period', cfg.fiscalPeriod],
    ['Tenant', tenants.find((t) => t.id === selectedTenant())?.slug || 'none'],
  ].forEach(([k, v]) => {
    dl.appendChild(ctx.el('dt', '', k));
    dl.appendChild(ctx.el('dd', '', String(v || '—')));
  });
  panel.appendChild(dl);
  panel.appendChild(
    ctx.el(
      'p',
      'ecc-help',
      'Branding, tax, gateway credentials and feature flags remain on their owning subsystems. Secrets are never displayed here.'
    )
  );
  page.appendChild(panel);
}

async function renderReports(page) {
  const reports = [
    'Financial Statement',
    'Revenue',
    'Expense',
    'Cash Flow',
    'Tax',
    'Settlement',
    'Reconciliation',
    'Commissions',
    'Payments',
    'Fraud',
    'Risk',
    'Audit',
  ];
  const wrap = ctx.el('div', 'cc-card-row');
  reports.forEach((name) => {
    const card = ctx.el('article', 'cc-card cc-card--compact');
    card.appendChild(ctx.el('div', 'cc-card__title', name));
    const available = name === 'Audit' && can('audit.read');
    card.appendChild(ctx.el('div', 'cc-card__desc', available ? 'Export live audit tail' : 'Unavailable — report generator not registered.'));
    if (available) {
      const btn = ctx.el('button', 'cc-btn-secondary', 'Export JSON');
      btn.type = 'button';
      btn.addEventListener('click', async () => {
        const data = await ctx.api(`/api/v1/economic/audit${qs()}`);
        const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' });
        const a = document.createElement('a');
        a.href = URL.createObjectURL(blob);
        a.download = 'economic-audit.json';
        a.click();
      });
      card.appendChild(btn);
    }
    wrap.appendChild(card);
  });
  page.appendChild(wrap);
}

async function renderRevenue(page) {
  if (!selectedTenant() || !can('invoices.read')) {
    page.appendChild(unavailableBanner('Select a tenant and invoice-read permission to inspect document totals. Revenue recognition is not registered.'));
    return;
  }
  const data = await ctx.api(`/api/v1/economic/invoices${qs()}`);
  const docs = data.items || [];
  const draftMinor = docs.filter((i) => i.status === 'DRAFT').reduce((acc, i) => acc + Number(i.amountMinor || 0), 0);
  page.appendChild(
    ctx.el(
      'p',
      'ecc-help',
      'These figures are invoice document totals, not recognized revenue. Revenue recognition is not registered.'
    )
  );
  const grid = ctx.el('div', 'ecc-kpi-grid');
  grid.appendChild(metricCard({ label: 'Invoice documents', value: String(docs.length), available: true, hint: 'Live count' }));
  grid.appendChild(
    metricCard({
      label: 'Draft document total',
      value: formatMoney(draftMinor, overview?.currency || 'ZAR'),
      available: true,
      hint: 'Not recognized revenue',
    })
  );
  grid.appendChild(
    metricCard({
      label: 'MRR / ARR',
      value: '—',
      available: false,
      reason: 'Unavailable — revenue recognition capability not registered.',
    })
  );
  page.appendChild(grid);
}

async function renderAutomation(page) {
  page.appendChild(
    unavailableBanner('Workflow engine is registered. Economic automation designer is not registered.')
  );
}

async function renderVendors(page) {
  page.appendChild(
    unavailableBanner(
      'Vendor classification is not registered on the CRM adapter. Customer partners are available under Customers.'
    )
  );
}

async function renderPricing(page) {
  page.appendChild(
    unavailableBanner('Unavailable — pricing versioning API is not registered. Invoice documents remain under Invoices.')
  );
}

function customerForm() {
  const form = document.createElement('form');
  form.className = 'ecc-form cc-panel cc-panel--blue';
  form.appendChild(ctx.el('h3', 'ecc-section-title', 'Create customer'));
  const name = textField({ id: 'ecc-cust-name', label: 'Name', required: true });
  const email = textField({ id: 'ecc-cust-email', label: 'Email', type: 'email' });
  const phone = textField({ id: 'ecc-cust-phone', label: 'Phone' });
  const company = textField({ id: 'ecc-cust-company', label: 'Company' });
  [name, email, phone, company].forEach((f) => form.appendChild(f.wrap));
  const actions = ctx.el('div', 'ecc-form__actions');
  const submit = ctx.el('button', 'cc-btn-primary', 'Create');
  submit.type = 'submit';
  actions.appendChild(submit);
  form.appendChild(actions);
  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    try {
      await ctx.api('/api/v1/economic/customers', {
        method: 'POST',
        body: JSON.stringify({
          tenantId: selectedTenant(),
          name: name.input.value.trim(),
          email: email.input.value.trim(),
          phone: phone.input.value.trim(),
          company: company.input.value.trim(),
        }),
      });
      loadEconomic('customers');
    } catch (err) {
      openDrawer({ title: 'Create failed', body: String(err.message || err) });
    }
  });
  return form;
}

function invoiceForm() {
  const form = document.createElement('form');
  form.className = 'ecc-form cc-panel cc-panel--purple';
  form.appendChild(ctx.el('h3', 'ecc-section-title', 'Create draft invoice'));
  const reference = textField({ id: 'ecc-inv-ref', label: 'Reference', placeholder: 'INV-draft' });
  const amount = moneyField({ id: 'ecc-inv-amount', label: 'Amount', currency: overview?.currency || 'ZAR' });
  [reference, amount].forEach((f) => form.appendChild(f.wrap));
  const actions = ctx.el('div', 'ecc-form__actions');
  const submit = ctx.el('button', 'cc-btn-primary', 'Create draft');
  submit.type = 'submit';
  actions.appendChild(submit);
  form.appendChild(actions);
  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    try {
      await ctx.api('/api/v1/economic/invoices', {
        method: 'POST',
        body: JSON.stringify({
          tenantId: selectedTenant(),
          reference: reference.input.value.trim(),
          amountMajor: amount.input.value,
          currency: overview?.currency || 'ZAR',
        }),
      });
      loadEconomic('invoices');
    } catch (err) {
      openDrawer({ title: 'Create failed', body: String(err.message || err) });
    }
  });
  return form;
}

function showRecordDrawer(title, row) {
  const body = document.createElement('div');
  const dl = document.createElement('dl');
  dl.className = 'ecc-kv';
  Object.entries(row).forEach(([k, v]) => {
    if (k === 'meta') return;
    dl.appendChild(ctx.el('dt', '', k));
    const dd = document.createElement('dd');
    if (k.toLowerCase().includes('amount')) {
      dd.appendChild(moneyEl(v, row.currency || overview?.currency || 'ZAR'));
    } else {
      dd.textContent = v == null ? '—' : String(v);
    }
    dl.appendChild(dd);
  });
  body.appendChild(dl);
  openDrawer({ title, body });
}

export function economicCommands() {
  return ECC_SCREENS.map((item) => ({
    id: `ecc-${item.id}`,
    label: item.label,
    group: 'Economic',
    run: () => {
      window.location.hash = `#/economic/${item.id}`;
    },
  }));
}
