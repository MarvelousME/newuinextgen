import fs from 'fs';

for (const f of ['deploy-out/theme-fail.html', 'deploy-out/plugin-fail.html', 'deploy-out/del-0.html']) {
  if (!fs.existsSync(f)) {
    console.log('missing', f);
    continue;
  }
  const h = fs.readFileSync(f, 'utf8');
  const p = h
    .replace(/<script[\s\S]*?<\/script>/gi, ' ')
    .replace(/<style[\s\S]*?<\/style>/gi, ' ')
    .replace(/<[^>]+>/g, ' ')
    .replace(/\s+/g, ' ');
  console.log('\n====', f);
  const idx = p.search(/could not|Destination|error|Error|failed|permission|Unable|Installing|delete|Remove/i);
  console.log(p.slice(Math.max(0, idx - 40), idx + 600));
}
