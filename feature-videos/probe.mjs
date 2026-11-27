#!/usr/bin/env node
// Scout a page before scripting it. Takes a screenshot (what the video will see) and prints
// an outline of headings, buttons, links, tabs and inputs with selector hints.
//
//   node probe.mjs --url=/wp-admin/admin.php?page=arraysubs-mainadmin#/subscriptions [--as=1|login|guest]
//        [--click="text=Save"] [--scroll=600] [--wait=1500] [--out=/abs/path.jpg] [--full] [--no-outline]
//        [--eval="document.title"]
// --click may repeat; each is a Playwright selector inside the page (css=, text=, role=…).
import path from 'node:path';
import fs from 'node:fs';
import { Video } from './lib/director.mjs';

const argv = process.argv.slice(2);
const all = (k) => argv.filter((a) => a.startsWith(`--${k}=`)).map((a) => a.slice(k.length + 3));
const one = (k, d = null) => all(k)[0] ?? d;
const flag = (k) => argv.includes(`--${k}`);

const outDir = process.env.FV_OUT || path.join(path.dirname(new URL(import.meta.url).pathname), 'out');
const out = one('out', path.join(outDir, '_probe', `probe-${Date.now()}.jpg`));
fs.mkdirSync(path.dirname(out), { recursive: true });

const v = new Video({ slug: '_probe', outDir: path.dirname(out), log: () => {} });
try {
  await v.init();
  const who = one('as', '1');
  await v.as(who === 'guest' ? 'guest' : who);
  await v.goto(one('url', '/'));
  for (const sel of all('click')) {
    await v.frame.locator(sel).first().click({ timeout: 15000 });
    await v.settle();
  }
  if (one('scroll')) await v.frame.evaluate((y) => window.scrollTo(0, y), Number(one('scroll')));
  if (one('wait')) await new Promise((r) => setTimeout(r, Number(one('wait'))));
  if (one('eval')) console.log('eval:', JSON.stringify(await v.frame.evaluate(one('eval'))));
  if (flag('full')) {
    const h = await v.frame.evaluate(() => document.scrollingElement.scrollHeight);
    console.log(`page height ${h}px (viewport 810)`);
  }
  await v.apply();
  fs.writeFileSync(out, await v.capture());
  console.log(`url: ${v.frame.url()}`);
  console.log(`shot: ${out}`);
  if (!flag('no-outline')) {
    const outline = await v.frame.evaluate(() => {
      const vis = (e) => { const r = e.getBoundingClientRect(); const cs = getComputedStyle(e); return r.width > 0 && r.height > 0 && cs.visibility !== 'hidden' && cs.display !== 'none'; };
      const txt = (e) => (e.innerText || e.value || e.getAttribute('aria-label') || e.getAttribute('placeholder') || e.title || '').trim().replace(/\s+/g, ' ').slice(0, 70);
      const hint = (e) => {
        if (e.id) return `#${e.id}`;
        const cls = [...e.classList].filter((c) => /arraysubs|wc-|woocommerce|components-|button|nav-tab|tab/.test(c)).slice(0, 2);
        return `${e.tagName.toLowerCase()}${cls.length ? '.' + cls.join('.') : ''}`;
      };
      const rows = [];
      const seen = new Set();
      for (const e of document.querySelectorAll('h1,h2,h3,h4,[role=tab],button,a.button,.nav-tab,input:not([type=hidden]),select,textarea,[role=switch],[role=checkbox],a[href*="#/"],.arraysubs-card-title,label')) {
        if (!vis(e) || e.closest('#wpadminbar') || e.closest('#adminmenuwrap')) continue;
        const t = txt(e);
        const key = `${e.tagName}|${t}`;
        if (seen.has(key)) continue;
        seen.add(key);
        const r = e.getBoundingClientRect();
        rows.push(`${String(Math.round(r.y + scrollY)).padStart(5)}y  ${e.tagName.toLowerCase().padEnd(8)} ${hint(e).padEnd(42)} ${t}`);
        if (rows.length > 140) break;
      }
      return rows.join('\n');
    });
    console.log(outline);
  }
} finally {
  await v.close();
}
