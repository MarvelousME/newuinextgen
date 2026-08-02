import fs from 'fs';

for (const f of ['deploy-out/plugin-resp.html', 'deploy-out/theme-resp.html', 'deploy-out/plugin-ui-fallback.html']) {
  if (!fs.existsSync(f)) {
    console.log('missing', f);
    continue;
  }
  const h = fs.readFileSync(f, 'utf8');
  console.log('====', f, 'len', h.length);
  console.log('installed?', /installed successfully/i.test(h));
  console.log('exists?', /already exists/i.test(h));
  console.log('unpack/error?', /Unable to|PCLZIP|failed|forbidden|not allowed/i.test(h));
  const text = h.replace(/<script[\s\S]*?<\/script>/gi, ' ').replace(/<style[\s\S]*?<\/style>/gi, ' ').replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ');
  const idx = text.search(/error|install|upload|destination|unpack|plugin|theme/i);
  console.log('text around match:', text.slice(Math.max(0, idx - 80), idx + 320));
}
