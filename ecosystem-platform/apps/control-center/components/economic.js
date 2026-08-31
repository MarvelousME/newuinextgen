/**
 * Shared Economic Control Center primitives — reuse Control Center tokens/classes.
 */

const STATUS_TONE = {
  PAID: 'success',
  SETTLED: 'success',
  RECONCILED: 'success',
  APPROVED: 'success',
  ACTIVE: 'success',
  HEALTHY: 'success',
  COMPLETED: 'success',
  WON: 'success',
  OK: 'success',
  POSTED: 'success',
  PROCESSING: 'info',
  PENDING: 'info',
  DRAFT: 'info',
  SENT: 'info',
  ISSUED: 'info',
  AUTHORIZED: 'info',
  CAPTURED: 'info',
  QUEUED: 'info',
  RUNNING: 'info',
  CALCULATING: 'info',
  VALIDATING: 'info',
  READY: 'info',
  OVERDUE: 'warning',
  PARTIAL: 'warning',
  WARNING: 'warning',
  REVIEW: 'warning',
  THRESHOLD: 'warning',
  PARTIALLY_PAID: 'warning',
  RETRYING: 'warning',
  FAILED: 'danger',
  REJECTED: 'danger',
  DISPUTED: 'danger',
  FRAUD: 'danger',
  VOID: 'danger',
  CANCELLED: 'danger',
  LOST: 'danger',
  DELINQUENT: 'danger',
};

export function formatMoney(amountMinor, currency = 'ZAR', locale = 'en-ZA') {
  if (amountMinor === null || amountMinor === undefined || amountMinor === '') {
    return '—';
  }
  const n = Number(amountMinor);
  if (!Number.isFinite(n)) {
    return '—';
  }
  const major = n / 100;
  try {
    return new Intl.NumberFormat(locale, {
      style: 'currency',
      currency,
      minimumFractionDigits: 2,
      maximumFractionDigits: 2,
    }).format(major);
  } catch {
    return `${currency} ${major.toFixed(2)}`;
  }
}

export function moneyEl(amountMinor, currency = 'ZAR', { negative = false } = {}) {
  const span = document.createElement('span');
  span.className = 'ecc-money';
  const n = Number(amountMinor);
  if (Number.isFinite(n) && n < 0) {
    span.classList.add('ecc-money--negative');
  }
  if (negative) {
    span.classList.add('ecc-money--negative');
  }
  span.textContent = formatMoney(amountMinor, currency);
  return span;
}

export function statusPill(status) {
  const label = String(status || 'UNKNOWN').replace(/_/g, ' ');
  const tone = STATUS_TONE[String(status || '').toUpperCase()] || 'neutral';
  const span = document.createElement('span');
  span.className = `ecc-status ecc-status--${tone}`;
  const icon = document.createElement('span');
  icon.className = 'ecc-status__dot';
  icon.setAttribute('aria-hidden', 'true');
  span.appendChild(icon);
  span.appendChild(document.createTextNode(label));
  span.setAttribute('aria-label', `Status ${label}`);
  return span;
}

export function unavailableBanner(reason) {
  const box = document.createElement('div');
  box.className = 'ecc-unavailable cc-panel cc-panel--orange';
  box.setAttribute('role', 'status');
  const h = document.createElement('h3');
  h.className = 'cc-panel__heading';
  h.textContent = 'Unavailable';
  const p = document.createElement('p');
  p.textContent = reason || 'Unavailable — capability not registered.';
  box.appendChild(h);
  box.appendChild(p);
  return box;
}

export function metricCard({ label, value, hint, available = true, reason = '', kinetic = true }) {
  const card = document.createElement('article');
  card.className = 'ecc-metric cc-card';
  if (kinetic && available) {
    card.dataset.kinetic = 'tilt';
  }
  const k = document.createElement('div');
  k.className = 'ecc-metric__label';
  k.textContent = label;
  card.appendChild(k);
  const v = document.createElement('div');
  v.className = 'ecc-metric__value';
  if (!available) {
    v.textContent = '—';
    card.classList.add('ecc-metric--unavailable');
  } else {
    v.textContent = value;
  }
  card.appendChild(v);
  const h = document.createElement('div');
  h.className = 'ecc-metric__hint';
  h.textContent = available ? hint || '' : reason || 'Unavailable';
  card.appendChild(h);
  return card;
}

/**
 * @param {{ columns: {key:string,label:string,align?:string,money?:boolean,status?:boolean}[], rows: object[], empty?: string, currency?: string, onRow?: Function }} opts
 */
export function dataGrid(opts) {
  const wrap = document.createElement('div');
  wrap.className = 'ecc-grid';
  const toolbar = document.createElement('div');
  toolbar.className = 'ecc-grid__toolbar';
  const search = document.createElement('input');
  search.type = 'search';
  search.className = 'ecc-input';
  search.placeholder = 'Search…';
  search.setAttribute('aria-label', 'Search table');
  toolbar.appendChild(search);
  wrap.appendChild(toolbar);

  const table = document.createElement('table');
  table.className = 'ecc-table';
  const thead = document.createElement('thead');
  const hr = document.createElement('tr');
  opts.columns.forEach((col) => {
    const th = document.createElement('th');
    th.textContent = col.label;
    th.scope = 'col';
    if (col.align === 'right' || col.money) th.className = 'ecc-num';
    hr.appendChild(th);
  });
  thead.appendChild(hr);
  table.appendChild(thead);
  const tbody = document.createElement('tbody');
  table.appendChild(tbody);
  wrap.appendChild(table);

  const empty = document.createElement('p');
  empty.className = 'ecc-empty';
  empty.hidden = true;
  empty.textContent = opts.empty || 'No records.';
  wrap.appendChild(empty);

  function paint(query = '') {
    const q = query.trim().toLowerCase();
    const rows = (opts.rows || []).filter((row) => {
      if (!q) return true;
      return JSON.stringify(row).toLowerCase().includes(q);
    });
    tbody.innerHTML = '';
    empty.hidden = rows.length > 0;
    table.hidden = rows.length === 0;
    rows.forEach((row) => {
      const tr = document.createElement('tr');
      tr.tabIndex = 0;
      opts.columns.forEach((col) => {
        const td = document.createElement('td');
        if (col.align === 'right' || col.money) td.className = 'ecc-num';
        const val = row[col.key];
        if (col.money) {
          td.appendChild(moneyEl(val, row.currency || opts.currency || 'ZAR'));
        } else if (col.status) {
          td.appendChild(statusPill(val));
        } else {
          td.textContent = val == null || val === '' ? '—' : String(val);
        }
        tr.appendChild(td);
      });
      if (opts.onRow) {
        tr.addEventListener('click', () => opts.onRow(row));
        tr.addEventListener('keydown', (e) => {
          if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            opts.onRow(row);
          }
        });
      }
      tbody.appendChild(tr);
    });
  }

  search.addEventListener('input', () => paint(search.value));
  paint('');
  return wrap;
}

export function textField({ id, label, name, type = 'text', required = false, placeholder = '', value = '' }) {
  const wrap = document.createElement('div');
  wrap.className = 'cc-field ecc-field';
  const lab = document.createElement('label');
  lab.htmlFor = id;
  lab.textContent = label;
  const input = document.createElement('input');
  input.id = id;
  input.name = name || id;
  input.type = type;
  input.placeholder = placeholder;
  input.value = value;
  input.required = required;
  input.className = 'ecc-input';
  wrap.appendChild(lab);
  wrap.appendChild(input);
  return { wrap, input };
}

export function moneyField({ id, label, currency = 'ZAR' }) {
  const wrap = document.createElement('div');
  wrap.className = 'cc-field ecc-field';
  const lab = document.createElement('label');
  lab.htmlFor = id;
  lab.textContent = `${label} (${currency})`;
  const input = document.createElement('input');
  input.id = id;
  input.name = id;
  input.inputMode = 'decimal';
  input.placeholder = '0.00';
  input.className = 'ecc-input ecc-input--money';
  input.setAttribute('aria-describedby', `${id}-help`);
  const help = document.createElement('p');
  help.id = `${id}-help`;
  help.className = 'ecc-help';
  help.textContent = 'Major units. Converted to integer minor units on submit.';
  wrap.appendChild(lab);
  wrap.appendChild(input);
  wrap.appendChild(help);
  return { wrap, input };
}

export function openDrawer({ title, body }) {
  let backdrop = document.getElementById('ecc-drawer');
  if (!backdrop) {
    backdrop = document.createElement('div');
    backdrop.id = 'ecc-drawer';
    backdrop.className = 'ecc-drawer-backdrop';
    backdrop.hidden = true;
    document.body.appendChild(backdrop);
  }
  backdrop.innerHTML = '';
  const panel = document.createElement('aside');
  panel.className = 'ecc-drawer';
  panel.setAttribute('role', 'dialog');
  panel.setAttribute('aria-modal', 'true');
  panel.setAttribute('aria-labelledby', 'ecc-drawer-title');
  const h = document.createElement('h2');
  h.id = 'ecc-drawer-title';
  h.textContent = title;
  const close = document.createElement('button');
  close.type = 'button';
  close.className = 'cc-btn-ghost';
  close.textContent = 'Close';
  const header = document.createElement('div');
  header.className = 'ecc-drawer__header';
  header.appendChild(h);
  header.appendChild(close);
  panel.appendChild(header);
  const content = document.createElement('div');
  content.className = 'ecc-drawer__body';
  if (typeof body === 'string') {
    content.textContent = body;
  } else {
    content.appendChild(body);
  }
  panel.appendChild(content);
  backdrop.appendChild(panel);
  backdrop.hidden = false;
  close.focus();

  function hide() {
    backdrop.hidden = true;
  }
  close.addEventListener('click', hide);
  backdrop.addEventListener('click', (e) => {
    if (e.target === backdrop) hide();
  });
  window.addEventListener(
    'keydown',
    (e) => {
      if (e.key === 'Escape') hide();
    },
    { once: true }
  );
  return hide;
}
