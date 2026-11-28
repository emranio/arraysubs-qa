#!/usr/bin/env node
// Short website clips and stills (16:10, 1920x1200, no title cards, no captions by default).
// Usage: node clip.mjs <scene> [--only=key1,key2] [--out=DIR]
//   scenes live in clips/<scene>.mjs:
//   export default {
//     setup: async (v, { wp }) => {},      // not recorded (prepare data, toggle settings)
//     teardown: async (v, { wp }) => {},   // always runs (restore!)
//     items: [
//       { key: 'my-account/plan-switch', type: 'video', before: async (v) => {}, run: async (v) => {} },
//       { key: 'plan-switching/settings', type: 'image', run: async (v) => {} },  // capture after run
//     ],
//   };
// Output: <out>/<key>.mp4 (+ .webp poster) or <out>/<key>.webp. Publish with publish-clips.mjs.
import fs from 'node:fs';
import path from 'node:path';
import { execFileSync } from 'node:child_process';
import { fileURLToPath, pathToFileURL } from 'node:url';

process.env.FV_W ||= '1152';
process.env.FV_H ||= '720';
process.env.FV_DPR ||= String(5 / 3);
process.env.FV_CAPTURE ||= 'pw';

const { Video, wp, wpJson, toWebp } = await import('./lib/director.mjs');

const HERE = path.dirname(fileURLToPath(import.meta.url));
const argv = process.argv.slice(2);
const name = argv.find((a) => !a.startsWith('--'));
const opt = (k) => { const a = argv.find((x) => x.startsWith(`--${k}=`)); return a ? a.split('=').slice(1).join('=') : null; };
if (!name) { console.error('scene required'); process.exit(1); }
const OUT = opt('out') || path.join(process.env.FV_OUT || path.join(HERE, 'out'), 'clips');
const only = opt('only') ? new Set(opt('only').split(',')) : null;
const work = path.join(OUT, '_work', name);
fs.mkdirSync(work, { recursive: true });

const scene = (await import(pathToFileURL(path.join(HERE, 'clips', `${name}.mjs`)).href + `?t=${Date.now()}`)).default;

// Display-only text cleanup inside the store frame (client-rendered blocks the PHP buffer misses).
const CLEAN = () => {
  if (window.top === window) return;
  const rules = [
    [/Demo\s·\s/g, ''],
    [/\s*[^.\n]*—\s*demo catalog\.?/gi, ''],
  ];
  const fix = (node) => {
    const t = node.nodeValue;
    if (!t || !/Demo\s·|demo catalog/i.test(t)) return;
    let s = t;
    for (const [re, to] of rules) s = s.replace(re, to);
    if (s !== t) node.nodeValue = s;
  };
  const walk = (root) => {
    const it = document.createTreeWalker(root, NodeFilter.SHOW_TEXT);
    let n; while ((n = it.nextNode())) fix(n);
  };
  const start = () => {
    walk(document.body);
    new MutationObserver((muts) => {
      for (const m of muts) {
        if (m.type === 'characterData') fix(m.target);
        for (const n of m.addedNodes || []) { if (n.nodeType === 3) fix(n); else if (n.nodeType === 1) walk(n); }
      }
    }).observe(document.body, { subtree: true, childList: true, characterData: true });
  };
  if (document.body) start(); else document.addEventListener('DOMContentLoaded', start);
};

const v = new Video({ slug: name, outDir: work });
let failed = null;
const done = [];
try {
  await v.init();
  await v.context.addInitScript(CLEAN);
  // Website clips carry no annotations: hide rings, labels, dimming, click ripples and captions.
  await v.page.addStyleTag({ content: '#hole, #ring, #label, #ripple, #lower { display: none !important; }' });
  // Every session of this scene also carries fv_clip=<scene>: zz-fv-clips.php then applies
  // clips/overrides/<scene>.json to those requests only (shared settings never change).
  const as = v.as.bind(v);
  v.as = async (user) => {
    await as(user);
    if (scene.overrides) await v.context.addCookies([{ name: 'fv_clip', value: name, domain: 'localhost', path: '/', expires: Math.floor(Date.now() / 1000) + 86400 }]);
  };
  const overridesFile = path.join(HERE, 'clips', 'overrides', `${name}.json`);
  if (scene.overrides) {
    fs.mkdirSync(path.dirname(overridesFile), { recursive: true });
    fs.writeFileSync(overridesFile, JSON.stringify(scene.overrides, null, 2));
  }
  if (scene.setup) { console.log('setup…'); await scene.setup(v, { wp, wpJson }); }
  let no = 0;
  for (const item of scene.items) {
    no += 1;
    if (only && !only.has(item.key)) continue;
    console.log(`${item.type} ${item.key}`);
    const dest = path.join(OUT, item.key);
    fs.mkdirSync(path.dirname(dest), { recursive: true });
    v.ring = null; v.cam = { s: 1, x: 0, y: 0 }; v.lowerState = { chip: '', kicker: '', text: '', o: 0, pos: 'bl' };
    if (item.before) await item.before(v, { wp, wpJson });
    if (item.type === 'image') {
      v.chapterNo = no;
      const target = await item.run(v, { wp, wpJson });
      await v.hideCursor().catch(() => {});
      await v.apply();
      const jpg = `${dest}.jpg`;
      let stillWidth = 1920;
      if (target) {
        // Clip a 16:10 window around the target straight from the stage (no camera zoom).
        await v.zoomOut().catch(() => {});
        await v.frame.evaluate(() => {
          for (const e of document.querySelectorAll('body *')) {
            const cs = getComputedStyle(e);
            if ((cs.position === 'sticky' || cs.position === 'fixed') && !e.closest('[role=dialog], .arraysubs-modal')) e.style.setProperty('position', cs.position === 'sticky' ? 'static' : 'absolute', 'important');
          }
          const bar = document.getElementById('wpadminbar'); if (bar) bar.style.display = 'none';
          document.documentElement.style.setProperty('padding-top', '0', 'important');
        });
        const loc = typeof target === 'string' ? v.frame.locator(target).first() : null;
        if (loc) await loc.evaluate((e) => e.scrollIntoView({ block: 'center' }));
        await new Promise((r) => setTimeout(r, 400));
        const W = Number(process.env.FV_W), H = Number(process.env.FV_H);
        const r = loc ? await loc.evaluate((e) => { const b = e.getBoundingClientRect(); return { x: b.left, y: b.top, w: b.width, h: b.height }; }) : target;
        const pad = item.pad ?? 20;
        let w = r.w + pad * 2, h = r.h + pad * 2;
        if (w / h > 1.6) h = w / 1.6; else w = h * 1.6;
        w = Math.min(w, W); h = Math.min(h, H);
        const x = Math.max(0, Math.min(W - w, r.x + r.w / 2 - w / 2));
        const y = Math.max(0, Math.min(H - h, r.y + r.h / 2 - h / 2));
        stillWidth = Math.round(w * Number(process.env.FV_DPR));
        fs.writeFileSync(jpg, await v.page.screenshot({ type: 'jpeg', quality: 93, clip: { x, y, width: w, height: h } }));
      } else {
        fs.writeFileSync(jpg, await v.capture());
      }
      await toWebp(jpg, `${dest}.webp`, { quality: 0.9, width: stillWidth });
      fs.rmSync(jpg);
      done.push(`${dest}.webp`);
      continue;
    }
    const posterJpg = path.join(work, 'poster.jpg');
    fs.rmSync(posterJpg, { force: true });
    await v.beginSegment(no);
    await item.run(v, { wp, wpJson });
    await v.say(null);
    if (v.ring) await v.unfocus();
    await v.hideCursor();
    await v.hold(item.tail ?? 0.6, { note: 'end' });
    await v.endSegment();
    const seg = path.join(work, `seg-${String(no).padStart(2, '0')}.mp4`);
    fs.copyFileSync(seg, `${dest}.mp4`);
    if (!fs.existsSync(posterJpg)) {
      const dur = Number(execFileSync('ffprobe', ['-v', 'error', '-show_entries', 'format=duration', '-of', 'csv=p=0', seg], { encoding: 'utf8' }).trim());
      execFileSync('ffmpeg', ['-y', '-loglevel', 'error', '-ss', String((dur * 0.6).toFixed(2)), '-i', seg, '-frames:v', '1', '-q:v', '2', posterJpg]);
    }
    await toWebp(posterJpg, `${dest}.webp`, { quality: 0.86, width: 1920 });
    done.push(`${dest}.mp4`);
  }
} catch (e) {
  failed = e;
  console.error(`FAILED: ${e.stack || e}`);
  try { await v.debugShot(`error-${name}`); } catch {}
} finally {
  try { if (scene.teardown) { console.log('teardown…'); await scene.teardown(v, { wp, wpJson }); } } catch (te) { console.error(`teardown failed: ${te.stack || te}`); }
  if (scene.overrides && !argv.includes('--keep-overrides')) fs.rmSync(path.join(HERE, 'clips', 'overrides', `${name}.json`), { force: true });
  await v.close();
}
for (const f of done) console.log(`out ${f}`);
if (failed) process.exit(1);
