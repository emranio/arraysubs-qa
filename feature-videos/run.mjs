#!/usr/bin/env node
// Usage: node run.mjs <slug> [--chapters=2,3] [--no-assemble] [--assemble-only] [--out=DIR]
// Records scenes/<slug>.mjs chapter by chapter into <out>/<slug>/seg-NN.mp4, then joins
// them into <out>/<slug>/<slug>.mp4 and writes poster.webp.
import fs from 'node:fs';
import path from 'node:path';
import { execFileSync } from 'node:child_process';
import { fileURLToPath, pathToFileURL } from 'node:url';
import { Video, wp, toWebp } from './lib/director.mjs';

const HERE = path.dirname(fileURLToPath(import.meta.url));
const argv = process.argv.slice(2);
const slug = argv.find((a) => !a.startsWith('--'));
const opt = (k) => { const a = argv.find((x) => x.startsWith(`--${k}=`)); return a ? a.split('=').slice(1).join('=') : null; };
const flag = (k) => argv.includes(`--${k}`);
if (!slug) { console.error('slug required'); process.exit(1); }

const OUT_ROOT = opt('out') || process.env.FV_OUT || path.join(HERE, 'out');
const outDir = path.join(OUT_ROOT, slug);
fs.mkdirSync(outDir, { recursive: true });
const only = opt('chapters') ? new Set(opt('chapters').split(',').map(Number)) : null;

const scene = (await import(pathToFileURL(path.join(HERE, 'scenes', `${slug}.mjs`)).href + `?t=${Date.now()}`)).default;
const chapters = scene.chapters;
const total = chapters.length;

function assemble() {
  const segs = chapters.map((_, i) => path.join(outDir, `seg-${String(i + 1).padStart(2, '0')}.mp4`));
  const missing = segs.filter((s) => !fs.existsSync(s));
  if (missing.length) { console.log(`assemble skipped, missing: ${missing.map((m) => path.basename(m)).join(', ')}`); return null; }
  const list = path.join(outDir, 'concat.txt');
  fs.writeFileSync(list, segs.map((s) => `file '${s.replace(/'/g, "'\\''")}'`).join('\n'));
  const final = path.join(outDir, `${slug}.mp4`);
  const sizes = segs.map((s) => execFileSync('ffprobe', ['-v', 'error', '-select_streams', 'v:0', '-show_entries', 'stream=width,height', '-of', 'csv=p=0', s], { encoding: 'utf8' }).trim().replace(/,$/, ''));
  if (sizes.every((z) => z === sizes[0])) {
    execFileSync('ffmpeg', ['-y', '-loglevel', 'error', '-f', 'concat', '-safe', '0', '-i', list, '-c', 'copy', '-movflags', '+faststart', final]);
  } else {
    // Mixed segment sizes (1440x810 CSS-pixel takes + 1920x1080 device-pixel takes): scale all to 1080p.
    console.log(`  mixed segment sizes (${[...new Set(sizes)].join(' / ')}), re-encoding to 1920x1080`);
    const inputs = segs.flatMap((s) => ['-i', s]);
    const chains = segs.map((_, i) => `[${i}:v]scale=1920:1080:flags=lanczos,setsar=1,fps=30[v${i}]`).join(';');
    const filter = `${chains};${segs.map((_, i) => `[v${i}]`).join('')}concat=n=${segs.length}:v=1:a=0[out]`;
    execFileSync('ffmpeg', ['-y', '-loglevel', 'error', ...inputs, '-filter_complex', filter, '-map', '[out]', '-an', '-c:v', 'libx264', '-preset', 'medium',
      '-tune', 'stillimage', '-crf', '18', '-g', '120', '-pix_fmt', 'yuv420p', '-movflags', '+faststart', final]);
  }
  const dur = execFileSync('ffprobe', ['-v', 'error', '-show_entries', 'format=duration', '-of', 'csv=p=0', final], { encoding: 'utf8' }).trim();
  const size = (fs.statSync(final).size / 1048576).toFixed(1);
  console.log(`final ${final} ${Number(dur).toFixed(1)}s ${size}MB`);
  return { final, dur: Number(dur) };
}

async function poster(info) {
  const jpg = path.join(outDir, 'poster.jpg');
  if (!fs.existsSync(jpg) || flag('auto-poster')) {
    // fallback: a frame 30% in
    execFileSync('ffmpeg', ['-y', '-loglevel', 'error', '-ss', String((info.dur * 0.3).toFixed(2)), '-i', info.final, '-frames:v', '1', '-q:v', '2', jpg]);
  }
  await toWebp(jpg, path.join(outDir, 'poster.webp'));
  console.log(`poster ${path.join(outDir, 'poster.webp')}`);
}

if (flag('assemble-only')) { const info = assemble(); if (info) await poster(info); process.exit(0); }

const v = new Video({ slug, outDir, chapters: only });
const t0 = Date.now();
let failed = null;
try {
  await v.init();
  if (scene.setup) { console.log('setup…'); await scene.setup(v, { wp }); }
  for (let i = 0; i < total; i++) {
    const no = i + 1;
    const ch = chapters[i];
    if (only && !only.has(no)) continue;
    console.log(`chapter ${no}/${total}: ${ch.title}`);
    v.kicker = ch.kicker || '';
    // reset per-chapter presentation state
    v.ring = null; v.cam = { s: 1, x: 0, y: 0 }; v.lowerState = { chip: '', kicker: '', text: '', o: 0, pos: 'bl' };
    if (ch.before) await ch.before(v, { wp });
    await v.beginSegment(no);
    const spec = no === 1 && scene.intro
      ? { ...scene.intro, tier: scene.intro.tier ?? scene.tier }
      : { tier: scene.tier, kicker: ch.kicker, num: String(no).padStart(2, '0'), title: ch.title, sub: ch.sub };
    if (no === 1 && scene.intro) {
      await v.cardNow(spec, { variant: 'intro' });
      await v.hold(scene.intro.hold ?? 2.6, { note: 'intro' });
    } else {
      await v.cardNow(spec);
      await v.hold(ch.cardHold ?? 1.9, { note: 'card' });
    }
    v.chapterNo = no;
    await ch.run(v, { wp });
    // close: slide in the next chapter card (or the outro) so cuts stay seamless
    await v.say(null);
    if (v.ring) await v.unfocus();
    await v.hideCursor();
    if (no < total) {
      const n = chapters[no];
      await v.card({ tier: scene.tier, kicker: n.kicker, num: String(no + 1).padStart(2, '0'), title: n.title, sub: n.sub });
    } else if (scene.outro) {
      await v.card({ tier: scene.tier, ...scene.outro }, { variant: 'outro' });
      await v.hold(scene.outro.hold ?? 3.2, { note: 'outro' });
    }
    await v.endSegment();
  }
  if (scene.teardown) { console.log('teardown…'); await scene.teardown(v, { wp }); }
} catch (e) {
  failed = e;
  console.error(`FAILED: ${e.stack || e}`);
  try { await v.page?.evaluate(() => window.S.apply({ ghost: { o: 0 } })); await v.debugShot(`error-ch${v.chapterNo}`); } catch {}
  try { if (scene.teardown) await scene.teardown(v, { wp }); } catch (te) { console.error(`teardown failed: ${te}`); }
} finally {
  await v.close();
}
console.log(`recorded in ${((Date.now() - t0) / 1000).toFixed(0)}s`);
if (failed) process.exit(1);
if (!flag('no-assemble')) { const info = assemble(); if (info) await poster(info); }
