/**
 * Reusable Control Center UI primitives — token-driven panels and cards.
 */

export function createPanel(tone, heading, subtitle = '') {
  const section = document.createElement('section');
  section.className = `cc-panel cc-panel--${tone} cc-enter`;
  const h = document.createElement('h2');
  h.className = 'cc-panel__heading';
  h.textContent = heading;
  section.appendChild(h);
  if (subtitle) {
    const p = document.createElement('p');
    p.className = 'cc-panel__subtitle';
    p.textContent = subtitle;
    section.appendChild(p);
  }
  const body = document.createElement('div');
  body.className = 'cc-panel__body';
  section.appendChild(body);
  return { section, body };
}

export function compactCard(title, desc = '', className = 'cc-card cc-card--compact') {
  const card = document.createElement('div');
  card.className = className;
  const t = document.createElement('div');
  t.className = 'cc-card__title';
  t.textContent = title;
  card.appendChild(t);
  if (desc) {
    const d = document.createElement('div');
    d.className = 'cc-card__desc';
    d.textContent = desc;
    card.appendChild(d);
  }
  return card;
}

export function ifaceCard(label) {
  const card = document.createElement('div');
  card.className = 'cc-card cc-card--iface';
  card.textContent = label;
  return card;
}

export function subsystemCard(sub) {
  const card = document.createElement('article');
  card.className = 'cc-subsystem-card';
  const icon = document.createElement('div');
  icon.className = 'cc-subsystem-card__icon';
  icon.textContent = sub.name.slice(0, 2).toUpperCase();
  card.appendChild(icon);
  const title = document.createElement('div');
  title.className = 'cc-subsystem-card__title';
  title.textContent = sub.name;
  card.appendChild(title);
  const ul = document.createElement('ul');
  ul.className = 'cc-subsystem-card__caps';
  sub.caps.forEach((cap) => {
    const li = document.createElement('li');
    li.textContent = cap;
    ul.appendChild(li);
  });
  card.appendChild(ul);
  return card;
}
