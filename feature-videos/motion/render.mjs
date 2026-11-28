#!/usr/bin/env node
// Render a seekable HTML motion piece to MP4, frame by frame (deterministic, not real time).
// The page must define window.__duration (seconds) and window.__seek(t) (synchronous).
//
//   node render.mjs <piece-dir> --out=<file.mp4> [--fps=30] [--png] [--from=0] [--to=<s>]
//   node render.mjs <piece-dir> --stills=0.5,3.2,7 --still-dir=<dir>     # PNG stills only
//   node render.mjs <piece-dir> --sheet=<file.jpg> [--every=1.5]           # contact sheet only
import { createRequire } from 'node:module';
import { spawn, execFileSync } from 'node:child_process';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath, pathToFileURL } from 'node:url';

const require = createRequire(import.meta.url);
const HERE = path.dirname(fileURLToPath(import.meta.url));
const PLUGINS = path.resolve(HERE, '../../..');
const { chromium } = require(path.join(PLUGINS, 'arraysubs/node_modules/playwright'));

const argv = process.argv.slice(2);
const opt = (k, d) => { const a = argv.find((x) => x.startsWith(`--${k}=`)); return a ? a.slice(k.length + 3) : d; };
const flag = (k) => argv.includes(`--${k}`);
const dir = path.resolve(argv.find((a) => !a.startsWith('--')) || '.');
const W = Number(opt('w', 1920));
const H = Number(opt('h', 1200));
const FPS = Number(opt('fps', 30));

const browser = await chromium.launch({
  executablePath: process.env.FV_CHROME || `${process.env.HOME}/Library/Caches/ms-playwright/chromium_headless_shell-1255/chrome-headless-shell-mac-arm64/chrome-headless-shell`,
  args: ['--allow-file-access-from-files', '--font-render-hinting=none', '--disable-lcd-text'],
});
const page = await browser.newPage({ viewport: { width: W, height: H }, deviceScaleFactor: 1 });
page.on('console', (m) => { if (m.type() === 'error' || m.type() === 'warning') console.log(`[page ${m.type()}]`, m.text()); });
page.on('pageerror', (e) => console.log('[pageerror]', e.message));
await page.goto(pathToFileURL(path.join(dir, 'index.html')).href, { waitUntil: 'load' });
await page.evaluate(() => document.fonts.ready);
await page.waitForFunction(() => typeof window.__seek === 'function' && window.__duration > 0);
const duration = await page.evaluate(() => window.__duration);
const cdp = await page.context().newCDPSession(page);

async function grab(t, fmt = 'png', quality = 95) {
  await page.evaluate((tt) => window.__seek(tt), t);
  const { data } = await cdp.send('Page.captureScreenshot', fmt === 'png' ? { format: 'png' } : { format: 'jpeg', quality });
  return Buffer.from(data, 'base64');
}

const stills = opt('stills');
const sheet = opt('sheet');
if (stills) {
  const out = path.resolve(opt('still-dir', path.join(dir, 'stills')));
  fs.mkdirSync(out, { recursive: true });
  for (const s of stills.split(',').map(Number)) {
    fs.writeFileSync(path.join(out, `t-${s.toFixed(2).padStart(6, '0')}.png`), await grab(s));
  }
  console.log(`stills -> ${out}`);
} else if (sheet) {
  const every = Number(opt('every', 1.5));
  const tmp = fs.mkdtempSync(path.join(path.dirname(path.resolve(sheet)), '.sheet-'));
  let i = 0;
  for (let t = Number(opt('from', 0)); t < Number(opt('to', duration)); t += every) {
    fs.writeFileSync(path.join(tmp, `f-${String(i++).padStart(4, '0')}.jpg`), await grab(t, 'jpeg', 85));
  }
  const cols = 4, rows = Math.ceil(i / cols);
  execFileSync('ffmpeg', ['-y', '-loglevel', 'error', '-framerate', '1', '-i', path.join(tmp, 'f-%04d.jpg'),
    '-vf', `scale=480:-1,drawtext=text='%{n}':x=8:y=8:fontsize=22:fontcolor=white:box=1:boxcolor=black@0.6,tile=${cols}x${rows}:padding=6:margin=6:color=white`, '-frames:v', '1', path.resolve(sheet)]);
  fs.rmSync(tmp, { recursive: true, force: true });
  console.log(`sheet (${i} tiles, every ${every}s) -> ${sheet}`);
} else {
  const out = path.resolve(opt('out', path.join(dir, 'out.mp4')));
  const png = flag('png');
  const from = Number(opt('from', 0));
  const to = Number(opt('to', duration));
  const frames = Math.round((to - from) * FPS);
  const ff = spawn('ffmpeg', ['-y', '-loglevel', 'error', '-f', 'image2pipe', '-framerate', String(FPS), '-c:v', png ? 'png' : 'mjpeg', '-i', '-',
    '-vf', 'scale=in_range=pc:out_range=tv,format=yuv420p', '-color_range', 'tv', '-colorspace', 'bt709', '-color_primaries', 'bt709', '-color_trc', 'bt709',
    '-an', '-c:v', 'libx264', '-preset', opt('preset', 'slow'), '-tune', 'animation', '-crf', opt('crf', '18'), '-profile:v', 'high', '-level', '4.2',
    '-pix_fmt', 'yuv420p', '-g', String(FPS * 4), '-movflags', '+faststart', out], { stdio: ['pipe', 'inherit', 'inherit'] });
  const t0 = Date.now();
  for (let f = 0; f < frames; f++) {
    const buf = await grab(from + f / FPS, png ? 'png' : 'jpeg', 96);
    if (!ff.stdin.write(buf)) await new Promise((r) => ff.stdin.once('drain', r));
    if (f % 150 === 0) process.stdout.write(`  frame ${f}/${frames} (${((Date.now() - t0) / 1000).toFixed(0)}s)\n`);
  }
  ff.stdin.end();
  await new Promise((r) => ff.on('close', r));
  console.log(`${out}: ${frames} frames, ${(fs.statSync(out).size / 1048576).toFixed(2)} MB, ${((Date.now() - t0) / 1000).toFixed(0)}s`);
}
await browser.close();
