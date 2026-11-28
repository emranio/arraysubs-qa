#!/usr/bin/env node
// Build <piece>/icons.js (window.ICONS = { name: svgString }) from lucide-react's icon data,
// so pieces can use the same Lucide icons as the website without React.
//   node icons.mjs <piece-dir> truck gift mail-check ...
import fs from 'node:fs';
import path from 'node:path';
import { pathToFileURL, fileURLToPath } from 'node:url';

const HERE = path.dirname(fileURLToPath(import.meta.url));
const ICON_DIR = path.resolve(HERE, '../../../web-content/node_modules/lucide-react/dist/esm/icons');
const [dir, ...names] = process.argv.slice(2);
const out = {};
for (const name of names) {
  const file = path.join(ICON_DIR, `${name}.mjs`);
  if (!fs.existsSync(file)) { console.log(`missing icon: ${name}`); continue; }
  let src = fs.readFileSync(file, 'utf8');
  let m = src.match(/const __iconData = (\{[\s\S]*?\n\});/);
  // alias modules re-export another icon file
  for (let hop = 0; !m && hop < 3; hop++) {
    const ref = src.match(/from '\.\/([\w-]+)\.mjs'/);
    if (!ref) break;
    src = fs.readFileSync(path.join(ICON_DIR, `${ref[1]}.mjs`), 'utf8');
    m = src.match(/const __iconData = (\{[\s\S]*?\n\});/);
  }
  if (!m) { console.log(`unparsed icon: ${name}`); continue; }
  // eslint-disable-next-line no-new-func
  const data = Function(`return ${m[1]}`)();
  const inner = data.node.map(([tag, attrs]) => `<${tag} ${Object.entries(attrs).filter(([k]) => k !== 'key').map(([k, v]) => `${k}="${v}"`).join(' ')}/>`).join('');
  out[name] = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">${inner}</svg>`;
}
fs.writeFileSync(path.join(dir, 'icons.js'), `window.ICONS = ${JSON.stringify(out, null, 1)};\n`);
console.log(`${Object.keys(out).length} icons -> ${path.join(dir, 'icons.js')}`);
