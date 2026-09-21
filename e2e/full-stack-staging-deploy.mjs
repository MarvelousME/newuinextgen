/**
 * Full-stack staging deploy: delete duplicates → install first-party zips →
 * activate → run Plugin Manager sequential queue for core/offline plugins.
 *
 * Usage (PowerShell):
 *   $env:NGT_STAGING_USER='admin'
 *   $env:NGT_STAGING_PASS='...'
 *   $env:NGT_STAGING_BASE='https://nextgentutors.co.za/staging'
 *   node e2e/full-stack-staging-deploy.mjs
 */
import { chromium } from '@playwright/test';
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const ROOT = path.resolve(__dirname, '..');
const BASE = (process.env.NGT_STAGING_BASE || 'https://nextgentutors.co.za/staging').replace(/\/$/, '');
const USER = process.env.NGT_STAGING_USER || '';
const PASS = process.env.NGT_STAGING_PASS || '';
const OUT = path.join(__dirname, 'deploy-out');

// Prefer slim Plugin Manager (no offline zips) to stay under host upload limits.
// Full bundle remains dist/NextGenTutors-Plugin-Manager.zip (~79MB with offline packages).
const PM_ZIP = fs.existsSync(path.join(ROOT, 'dist', 'NextGenTutors-Plugin-Manager-slim.zip'))
  ? 'NextGenTutors-Plugin-Manager-slim.zip'
  : 'NextGenTutors-Plugin-Manager.zip';

const PLUGINS = [
  { zip: PM_ZIP, match: /NextGenTutors-Plugin-Manager|Plugin Manager/i, main: /NextGenTutors-Plugin-Manager\.php$/i },
  { zip: 'NextGenTutors-Companion.zip', match: /NextGenTutors-Companion|NextGen Companion/i, main: /nextgencompanion\.php$/i },
  { zip: 'NextGenTutors-AI-Integration.zip', match: /AI-Integration|AI Integration/i, main: /nextgentutors-ai-integration\.php$/i },
  { zip: 'NextGenTutors-Mission-Control.zip', match: /Mission-Control|Mission Control/i, main: /nextgentutors-mission-control\.php$/i },
  { zip: 'NextGenTutors-BeyondMeasure.zip', match: /BeyondMeasure|Beyond Measure/i, main: /nextgentutors-beyond-measure\.php$/i },
  { zip: 'NextGenTutors-Html-Importer.zip', match: /Html-Importer|HTML Importer|Revamp/i, main: /revamp-html-importer\.php$/i },
];

const THEME_ZIP = path.join(ROOT, 'dist', 'NextGenTutors-BeyondInfinity.zip');
const THEME_MATCH = /BeyondInfinity|NextGenTutors-BeyondInfinity/i;

function plain(html) {
  return html
    .replace(/<script[\s\S]*?<\/script>/gi, ' ')
    .replace(/<style[\s\S]*?<\/style>/gi, ' ')
    .replace(/<[^>]+>/g, ' ')
    .replace(/\s+/g, ' ')
    .trim();
}

function extractNonce(html) {
  const m =
    html.match(/name=["']_wpnonce["']\s+value=["']([^"']+)["']/i) ||
    html.match(/value=["']([^"']+)["']\s+name=["']_wpnonce["']/i) ||
    html.match(/id=["']_wpnonce["'][^>]*value=["']([^"']+)["']/i);
  if (!m) throw new Error('nonce missing');
  return m[1];
}

function extractAjaxNonce(html) {
  const m =
    html.match(/["']nonce["']\s*:\s*["']([^"']+)["']/i) ||
    html.match(/ngcpmNonce["']?\s*[:=]\s*["']([^"']+)["']/i) ||
    html.match(/_ajax_nonce["']?\s*[:=]\s*["']([^"']+)["']/i);
  return m ? m[1] : '';
}

async function login(page) {
  await page.goto(`${BASE}/wp-login.php`, { waitUntil: 'domcontentloaded' });
  await page.fill('#user_login', USER);
  await page.fill('#user_pass', PASS);
  await page.check('#rememberme').catch(() => {});
  await page.click('#wp-submit');
  await page.waitForSelector('#wpadminbar, body.logged-in, #wpbody', { timeout: 90_000 });
  await page.goto(`${BASE}/wp-admin/`, { waitUntil: 'domcontentloaded' });
}

function acceptDialogs(page) {
  page.on('dialog', async (d) => {
    console.log('dialog', d.type(), d.message().slice(0, 100));
    await d.accept();
  });
}

async function deleteMatchingPlugins(page, match) {
  for (let pass = 0; pass < 10; pass++) {
    await page.goto(`${BASE}/wp-admin/plugins.php`, { waitUntil: 'domcontentloaded' });
    const rows = page.locator('#the-list tr').filter({ hasText: match });
    const count = await rows.count();
    console.log('delete pass', match.toString(), pass, 'count', count);
    if (!count) return;

    // Prefer bulk when multiple
    if (count > 1) {
      for (let i = 0; i < count; i++) {
        const box = rows.nth(i).locator('input[type="checkbox"][name="checked[]"]');
        if (await box.count()) await box.check({ force: true });
      }
      // Deactivate actives first
      const actives = rows.locator('a:has-text("Deactivate")');
      while (await actives.count()) {
        await actives.first().click();
        await page.waitForLoadState('domcontentloaded');
      }
      await page.goto(`${BASE}/wp-admin/plugins.php`, { waitUntil: 'domcontentloaded' });
      const rows2 = page.locator('#the-list tr').filter({ hasText: match });
      const n2 = await rows2.count();
      for (let i = 0; i < n2; i++) {
        const box = rows2.nth(i).locator('input[type="checkbox"][name="checked[]"]');
        if (await box.count()) await box.check({ force: true });
      }
      if (n2) {
        await page.locator('#bulk-action-selector-top').selectOption('delete-selected');
        await page.locator('#doaction').click();
        await page.waitForLoadState('domcontentloaded');
        const confirm = page.locator('input#submit, input[value*="Yes"], button:has-text("Yes, delete these files")').first();
        if (await confirm.isVisible().catch(() => false)) await confirm.click();
        await page.waitForLoadState('domcontentloaded');
      }
      continue;
    }

    const row = rows.first();
    const deactivate = row.locator('a:has-text("Deactivate")').first();
    if (await deactivate.isVisible().catch(() => false)) {
      await deactivate.click();
      await page.waitForLoadState('domcontentloaded');
      continue;
    }
    const del = row.locator('a:has-text("Delete")').first();
    if (!(await del.count())) return;
    await del.click();
    await page.waitForTimeout(800);
    const confirm = page.locator('input#submit, input[value*="Yes"], button:has-text("Yes, delete these files")').first();
    if (await confirm.isVisible().catch(() => false)) await confirm.click();
    await page.waitForLoadState('domcontentloaded');
  }
}

async function ensureFallbackTheme(page) {
  await page.goto(`${BASE}/wp-admin/themes.php`, { waitUntil: 'domcontentloaded' });
  const fallback = page
    .locator('div[data-slug="hello-elementor"] .activate, div[data-slug="twentytwentyfive"] .activate, div[data-slug="twentytwentyfour"] .activate')
    .first();
  if (await fallback.isVisible().catch(() => false)) {
    await fallback.click();
    await page.waitForLoadState('domcontentloaded');
  }
}

async function deleteBeyondInfinity(page) {
  await ensureFallbackTheme(page);
  for (let i = 0; i < 5; i++) {
    await page.goto(`${BASE}/wp-admin/themes.php`, { waitUntil: 'domcontentloaded' });
    const bi = page.locator('.theme').filter({ hasText: THEME_MATCH }).first();
    if (!(await bi.count())) return;
    await bi.click();
    await page.waitForTimeout(600);
    const del = page.locator('.theme-overlay .delete-theme, a.delete-theme').first();
    if (!(await del.count())) return;
    await del.click();
    await page.waitForTimeout(2000);
  }
}

async function waitInstallResult(page, kind) {
  for (let i = 0; i < 120; i++) {
    await page.waitForTimeout(2000);
    let html = '';
    try {
      html = await page.content();
    } catch {
      continue;
    }
    const p = plain(html);
    const hit = p.match(
      kind === 'plugin'
        ? /Plugin installed successfully|Destination folder already exists|The package could not be installed|Plugin installation failed|Unpacking the package|PCLZIP_ERR/i
        : /Theme installed successfully|Destination folder already exists|The package could not be installed|Stylesheet is missing|Unpacking the package|PCLZIP_ERR/i
    );
    if (hit) {
      console.log(kind, 'result', hit[0], '@', i);
      fs.writeFileSync(path.join(OUT, `${kind}-last.html`), html);
      console.log(p.slice(p.search(/Install|Plugin|Theme|Destination|error|Error|Stylesheet|PCLZIP/i), 500));
      return hit[0];
    }
    if (i % 15 === 0) console.log(kind, 'waiting', i, page.url());
  }
  fs.writeFileSync(path.join(OUT, `${kind}-timeout.html`), await page.content().catch(() => ''));
  return 'TIMEOUT';
}

async function uploadZipUi(page, kind, zipPath) {
  if (kind === 'plugin') {
    await page.goto(`${BASE}/wp-admin/plugin-install.php?tab=upload`, { waitUntil: 'domcontentloaded' });
  } else {
    await page.goto(`${BASE}/wp-admin/theme-install.php`, { waitUntil: 'domcontentloaded' });
    await page.getByRole('button', { name: /Upload Theme/i }).click({ timeout: 8000 }).catch(() => {});
  }
  await page.evaluate(() => {
    document.body.classList.add('show-upload-view');
    document.querySelectorAll('.wp-upload-form').forEach((el) => {
      el.style.display = 'block';
      el.style.visibility = 'visible';
    });
  });
  const input = kind === 'plugin' ? '#pluginzip' : '#themezip';
  const submit = kind === 'plugin' ? '#install-plugin-submit' : '#install-theme-submit';
  await page.setInputFiles(input, zipPath);
  await page.locator(submit).evaluate((el) => el.click());
  return waitInstallResult(page, kind);
}

async function uploadZipMultipart(page, request, kind, zipPath, zipName) {
  const url =
    kind === 'plugin'
      ? `${BASE}/wp-admin/plugin-install.php?tab=upload`
      : `${BASE}/wp-admin/theme-install.php?upload`;
  await page.goto(url, { waitUntil: 'domcontentloaded' });
  await page.evaluate(() => {
    document.body.classList.add('show-upload-view');
    document.querySelectorAll('.wp-upload-form').forEach((el) => {
      el.style.display = 'block';
      el.style.visibility = 'visible';
    });
  });
  const html = await page.content();
  const nonce = extractNonce(html);
  const action =
    kind === 'plugin'
      ? `${BASE}/wp-admin/update.php?action=upload-plugin`
      : `${BASE}/wp-admin/update.php?action=upload-theme`;
  const fileField = kind === 'plugin' ? 'pluginzip' : 'themezip';
  const submitName = kind === 'plugin' ? 'install-plugin-submit' : 'install-theme-submit';
  const resp = await request.post(action, {
    multipart: {
      _wpnonce: nonce,
      _wp_http_referer: url.replace(BASE, '') || url,
      [fileField]: {
        name: zipName,
        mimeType: 'application/zip',
        buffer: fs.readFileSync(zipPath),
      },
      [submitName]: 'Install Now',
    },
    timeout: 600_000,
    maxRedirects: 5,
    failOnStatusCode: false,
  });
  const body = await resp.text();
  fs.writeFileSync(path.join(OUT, `${zipName}-resp.html`), body);
  const p = plain(body);
  const hit = p.match(
    /installed successfully|Destination folder already exists|could not be installed|Stylesheet is missing|PCLZIP_ERR|failed/i
  );
  console.log(kind, zipName, 'status', resp.status(), 'hit', hit?.[0] || 'none');
  console.log(p.slice(p.search(/Install|Plugin|Theme|Destination|error|Error|Stylesheet|PCLZIP/i), 400));
  return hit?.[0] || `HTTP_${resp.status()}`;
}

async function activatePlugin(page, match, mainRe) {
  await page.goto(`${BASE}/wp-admin/plugins.php`, { waitUntil: 'domcontentloaded' });
  const rows = page.locator('#the-list tr').filter({ hasText: match });
  const count = await rows.count();
  console.log('activate candidates', match.toString(), count);
  let target = rows.first();
  for (let i = 0; i < count; i++) {
    const dp = (await rows.nth(i).getAttribute('data-plugin')) || '';
    if (mainRe.test(dp) && !dp.includes(`${path.sep}${path.sep}`)) {
      // Prefer non-nested: folder/file.php once
      const parts = dp.split('/');
      if (parts.length === 2) {
        target = rows.nth(i);
        break;
      }
    }
  }
  const dataPlugin = await target.getAttribute('data-plugin');
  console.log('activating', dataPlugin);
  if (String(dataPlugin).split('/').length > 2) {
    console.warn('WARN nested plugin path still present:', dataPlugin);
  }
  const act = target.locator('a:has-text("Activate")').first();
  if (await act.isVisible().catch(() => false)) {
    await act.click();
    await page.waitForLoadState('domcontentloaded');
  }
  const errors = (await page.locator('.notice-error, #message.error, .wp-die-message').allTextContents()).map((t) =>
    t.replace(/\s+/g, ' ').trim()
  );
  const active = await page.locator('#the-list tr.active').filter({ hasText: match }).count();
  console.log('active?', active, 'errors', errors.slice(0, 3));
  return { active: active > 0, errors, dataPlugin };
}

async function activateTheme(page) {
  await page.goto(`${BASE}/wp-admin/themes.php`, { waitUntil: 'domcontentloaded' });
  const card = page.locator('.theme').filter({ hasText: THEME_MATCH }).first();
  const act = card.locator('.activate').first();
  if (await act.count()) {
    await act.click();
    await page.waitForLoadState('domcontentloaded');
  }
  const activeText = plain(await page.locator('.theme.active').innerHTML().catch(() => ''));
  console.log('active theme', activeText.slice(0, 160));
  return /BeyondInfinity|NextGenTutors-BeyondInfinity/i.test(activeText);
}

async function ajaxAdminPost(page, request, action, extra = {}) {
  // Load Plugin Manager admin to harvest nonce if needed
  await page.goto(`${BASE}/wp-admin/admin.php?page=ngcpm`, { waitUntil: 'domcontentloaded' }).catch(async () => {
    await page.goto(`${BASE}/wp-admin/admin.php?page=nextgentutors-plugin-manager`, { waitUntil: 'domcontentloaded' });
  });
  const html = await page.content();
  fs.writeFileSync(path.join(OUT, 'ngcpm-admin.html'), html);
  let nonce =
    extractAjaxNonce(html) ||
    (await page.evaluate(() => {
      const cfg = window.ngcpmData || window.NGCPM || window.ngcpm || {};
      return cfg.nonce || cfg.ajaxNonce || cfg._ajax_nonce || '';
    }).catch(() => ''));

  if (!nonce) {
    // Fallback: WordPress REST / heartbeat style from localized script tags
    const m = html.match(/var\s+ngcpm[^=]*=\s*(\{[\s\S]*?\});/i);
    if (m) {
      try {
        const obj = JSON.parse(m[1].replace(/([{,]\s*)(\w+)\s*:/g, '$1"$2":').replace(/'/g, '"'));
        nonce = obj.nonce || obj.ajax_nonce || '';
      } catch {
        /* ignore */
      }
    }
  }
  console.log('ngcpm nonce', nonce ? 'yes' : 'no', 'action', action);

  const form = {
    action,
    _ajax_nonce: nonce,
    nonce,
    ...extra,
  };
  const resp = await request.post(`${BASE}/wp-admin/admin-ajax.php`, {
    form,
    timeout: 300_000,
    failOnStatusCode: false,
  });
  const text = await resp.text();
  fs.writeFileSync(path.join(OUT, `ajax-${action}.json`), text.slice(0, 500_000));
  let json = null;
  try {
    json = JSON.parse(text);
  } catch {
    json = { raw: text.slice(0, 1000) };
  }
  console.log('ajax', action, 'status', resp.status(), 'success', json?.success, 'keys', Object.keys(json?.data || {}).slice(0, 8));
  return json;
}

async function runCorePluginQueue(page, request) {
  // Try local packages install first (bundled offline zips inside Plugin Manager)
  const local = await ajaxAdminPost(page, request, 'ngcpm_install_local_packages');
  const planJson = await ajaxAdminPost(page, request, 'ngcpm_queue_plan');
  const plan = planJson?.data?.plan || [];
  console.log('queue plan items', Array.isArray(plan) ? plan.length : 0);
  if (Array.isArray(plan)) {
    fs.writeFileSync(path.join(OUT, 'queue-plan.json'), JSON.stringify(plan, null, 2));
  }

  const results = [];
  for (const step of plan) {
    const slug = step.slug || step.registry_key || step.key;
    const strategy = step.action || step.strategy || (step.needs_activate ? 'activate' : 'install');
    if (!slug) continue;
    console.log('queue step', strategy, slug);
    if (strategy === 'activate') {
      const r = await ajaxAdminPost(page, request, 'ngcpm_activate', { slug });
      results.push({ slug, strategy, ok: !!r?.success, message: r?.data?.message || r?.data?.error || '' });
    } else {
      const r = await ajaxAdminPost(page, request, 'ngcpm_install', { slug, overwrite: '1' });
      results.push({ slug, strategy: 'install', ok: !!r?.success, message: r?.data?.message || r?.data?.error || '' });
      if (r?.success) {
        const a = await ajaxAdminPost(page, request, 'ngcpm_activate', { slug });
        results.push({ slug, strategy: 'activate', ok: !!a?.success, message: a?.data?.message || a?.data?.error || '' });
      }
    }
  }

  const verify = await ajaxAdminPost(page, request, 'ngcpm_verify_system');
  fs.writeFileSync(path.join(OUT, 'core-queue-results.json'), JSON.stringify({ local, results, verify }, null, 2));
  return { local, results, verify };
}

async function dedupeReport(page) {
  await page.goto(`${BASE}/wp-admin/plugins.php`, { waitUntil: 'domcontentloaded' });
  const plugins = await page.locator('#the-list tr').evaluateAll((trs) =>
    trs.map((tr) => ({
      plugin: tr.getAttribute('data-plugin'),
      text: (tr.querySelector('.plugin-title strong')?.textContent || '').trim(),
      active: tr.classList.contains('active'),
    }))
  );
  const ngt = plugins.filter((p) => /NextGen|Beyond|Companion|Mission|Html|AI Integration|Plugin Manager/i.test(`${p.plugin} ${p.text}`));
  await page.goto(`${BASE}/wp-admin/themes.php`, { waitUntil: 'domcontentloaded' });
  const themes = await page.locator('.theme').evaluateAll((els) =>
    els.map((el) => ({
      slug: el.getAttribute('data-slug'),
      text: el.textContent.replace(/\s+/g, ' ').trim().slice(0, 120),
      active: el.classList.contains('active'),
    }))
  );
  const report = { plugins: ngt, themes: themes.filter((t) => /beyond|nextgen/i.test(`${t.slug} ${t.text}`)) };
  fs.writeFileSync(path.join(OUT, 'dedupe-report.json'), JSON.stringify(report, null, 2));
  console.log('NGT plugins on site:', ngt.length);
  for (const p of ngt) console.log(' -', p.active ? 'ACTIVE' : '     ', p.plugin);
  return report;
}

async function main() {
  if (!USER || !PASS) throw new Error('Set NGT_STAGING_USER and NGT_STAGING_PASS');
  fs.mkdirSync(OUT, { recursive: true });

  for (const p of PLUGINS) {
    const zp = path.join(ROOT, 'dist', p.zip);
    if (!fs.existsSync(zp)) throw new Error(`Missing zip: ${zp}`);
    console.log('zip ok', p.zip, (fs.statSync(zp).size / 1e6).toFixed(1) + 'MB');
  }
  if (!fs.existsSync(THEME_ZIP)) throw new Error(`Missing theme zip: ${THEME_ZIP}`);
  console.log('theme zip', (fs.statSync(THEME_ZIP).size / 1e6).toFixed(1) + 'MB');

  const browser = await chromium.launch({ channel: 'chrome', headless: true });
  const context = await browser.newContext();
  const page = await context.newPage();
  const request = context.request;
  acceptDialogs(page);
  await login(page);

  // 1) Purge duplicates
  for (const p of PLUGINS) {
    await deleteMatchingPlugins(page, p.match);
  }
  await deleteBeyondInfinity(page);

  // 2) Install first-party plugins (Plugin Manager first so core install is available)
  const installResults = {};
  for (const p of PLUGINS) {
    const zipPath = path.join(ROOT, 'dist', p.zip);
    let result = await uploadZipMultipart(page, request, 'plugin', zipPath, p.zip);
    if (/Destination folder already exists/i.test(result)) {
      console.log('exists — deleting again then UI upload', p.zip);
      await deleteMatchingPlugins(page, p.match);
      result = await uploadZipUi(page, 'plugin', zipPath);
    } else if (/could not be installed|failed|PCLZIP|TIMEOUT/i.test(result)) {
      console.log('multipart weak — UI fallback', p.zip);
      result = await uploadZipUi(page, 'plugin', zipPath);
    }
    installResults[p.zip] = result;
    const act = await activatePlugin(page, p.match, p.main);
    installResults[`${p.zip}:activate`] = act;
  }

  // 3) Theme
  let themeResult = await uploadZipMultipart(page, request, 'theme', THEME_ZIP, 'NextGenTutors-BeyondInfinity.zip');
  if (/Destination folder already exists|could not|failed|PCLZIP|TIMEOUT/i.test(themeResult)) {
    await deleteBeyondInfinity(page);
    themeResult = await uploadZipUi(page, 'theme', THEME_ZIP);
  }
  const themeActive = await activateTheme(page);

  // 4) Core / offline stack via Plugin Manager
  let core = null;
  try {
    core = await runCorePluginQueue(page, request);
  } catch (e) {
    console.error('core queue error', e);
    core = { error: String(e) };
  }

  const report = await dedupeReport(page);
  const summary = {
    base: BASE,
    installResults,
    themeResult,
    themeActive,
    coreOk: !(core && core.error),
    coreFailures: (core?.results || []).filter((r) => !r.ok),
    pluginCount: report.plugins.length,
    nestedPlugins: report.plugins.filter((p) => (p.plugin || '').split('/').length > 2),
  };
  fs.writeFileSync(path.join(OUT, 'full-stack-summary.json'), JSON.stringify(summary, null, 2));
  console.log('SUMMARY', JSON.stringify(summary, null, 2));

  await browser.close();

  const nested = summary.nestedPlugins.length;
  const activateFails = Object.entries(installResults).filter(([k, v]) => k.endsWith(':activate') && !v.active);
  if (nested || activateFails.length || !themeActive) {
    process.exit(2);
  }
}

main().catch((e) => {
  console.error(e);
  process.exit(1);
});
