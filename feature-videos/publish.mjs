#!/usr/bin/env node
// Copy finished tours into the website: re-encode for the web, copy the poster, register
// the key in captured.json. Usage: node publish.mjs <slug> [<slug> ...] [--crf=23]
import fs from 'node:fs';
import path from 'node:path';
import { execFileSync } from 'node:child_process';
import { fileURLToPath } from 'node:url';

const HERE = path.dirname(fileURLToPath(import.meta.url));
const WEB = path.resolve(HERE, '../../web-content');
const OUT = process.env.FV_OUT || path.join(HERE, 'out');
const argv = process.argv.slice(2);
const crf = (argv.find((a) => a.startsWith('--crf=')) || '--crf=23').split('=')[1];
const slugs = argv.filter((a) => !a.startsWith('--'));
const dest = path.join(WEB, 'public/media/tours');
fs.mkdirSync(dest, { recursive: true });
const capturedPath = path.join(WEB, 'src/data/product/captured.json');

for (const slug of slugs) {
  const src = path.join(OUT, slug, `${slug}.mp4`);
  const poster = path.join(OUT, slug, 'poster.webp');
  if (!fs.existsSync(src) || !fs.existsSync(poster)) { console.log(`${slug}: missing video or poster, skipped`); continue; }
  const mp4 = path.join(dest, `${slug}.mp4`);
  execFileSync('ffmpeg', ['-y', '-loglevel', 'error', '-i', src, '-an', '-c:v', 'libx264', '-preset', 'slow', '-tune', 'stillimage',
    '-crf', crf, '-profile:v', 'high', '-level', '4.1', '-pix_fmt', 'yuv420p', '-g', '120', '-movflags', '+faststart', mp4]);
  fs.copyFileSync(poster, path.join(dest, `${slug}.webp`));
  const probe = JSON.parse(execFileSync('ffprobe', ['-v', 'error', '-select_streams', 'v:0', '-show_entries', 'stream=width,height:format=duration', '-of', 'json', mp4], { encoding: 'utf8' }));
  const { width, height } = probe.streams[0];
  const dur = Number(probe.format.duration);
  // re-read just before writing: other sessions edit this file too
  const captured = JSON.parse(fs.readFileSync(capturedPath, 'utf8'));
  captured[`tours/${slug}`] = { type: 'video', width, height };
  const sorted = Object.fromEntries(Object.keys(captured).sort().map((k) => [k, captured[k]]));
  fs.writeFileSync(capturedPath, `${JSON.stringify(sorted, null, 2)}\n`);
  const mm = Math.floor(dur / 60), ss = String(Math.round(dur % 60)).padStart(2, '0');
  console.log(`${slug}: ${mm}:${ss} ${(fs.statSync(mp4).size / 1048576).toFixed(1)}MB ${width}x${height}`);
}
