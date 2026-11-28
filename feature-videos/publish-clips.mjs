#!/usr/bin/env node
// Copy finished clips/stills from clip.mjs into the website and register them in captured.json.
// Usage: node publish-clips.mjs <key> [<key> ...] [--crf=23]   (key = media key, e.g. checkout/mixed-cart)
import fs from 'node:fs';
import path from 'node:path';
import { execFileSync } from 'node:child_process';
import { fileURLToPath } from 'node:url';

const HERE = path.dirname(fileURLToPath(import.meta.url));
const WEB = path.resolve(HERE, '../../web-content');
const SRC = path.join(process.env.FV_OUT || path.join(HERE, 'out'), 'clips');
const argv = process.argv.slice(2);
const crf = (argv.find((a) => a.startsWith('--crf=')) || '--crf=23').split('=')[1];
const capturedPath = path.join(WEB, 'src/data/product/captured.json');

for (const key of argv.filter((a) => !a.startsWith('--'))) {
  const base = path.join(SRC, key);
  const destBase = path.join(WEB, 'public/media', key);
  fs.mkdirSync(path.dirname(destBase), { recursive: true });
  let entry;
  if (fs.existsSync(`${base}.mp4`)) {
    execFileSync('ffmpeg', ['-y', '-loglevel', 'error', '-i', `${base}.mp4`, '-an', '-c:v', 'libx264', '-preset', 'slow', '-tune', 'stillimage',
      '-crf', crf, '-profile:v', 'high', '-level', '4.1', '-pix_fmt', 'yuv420p', '-g', '120', '-movflags', '+faststart', `${destBase}.mp4`]);
    fs.copyFileSync(`${base}.webp`, `${destBase}.webp`);
    const probe = JSON.parse(execFileSync('ffprobe', ['-v', 'error', '-select_streams', 'v:0', '-show_entries', 'stream=width,height', '-of', 'json', `${destBase}.mp4`], { encoding: 'utf8' }));
    entry = { type: 'video', width: probe.streams[0].width, height: probe.streams[0].height };
  } else if (fs.existsSync(`${base}.webp`)) {
    fs.copyFileSync(`${base}.webp`, `${destBase}.webp`);
    const probe = JSON.parse(execFileSync('ffprobe', ['-v', 'error', '-select_streams', 'v:0', '-show_entries', 'stream=width,height', '-of', 'json', `${destBase}.webp`], { encoding: 'utf8' }));
    entry = { type: 'image', width: probe.streams[0].width, height: probe.streams[0].height };
  } else {
    console.log(`${key}: nothing to publish`);
    continue;
  }
  // re-read just before writing: other sessions edit this file too
  const captured = JSON.parse(fs.readFileSync(capturedPath, 'utf8'));
  captured[key] = entry;
  const sorted = Object.fromEntries(Object.keys(captured).sort().map((k) => [k, captured[k]]));
  fs.writeFileSync(capturedPath, `${JSON.stringify(sorted, null, 2)}\n`);
  const size = fs.statSync(entry.type === 'video' ? `${destBase}.mp4` : `${destBase}.webp`).size;
  console.log(`${key}: ${entry.type} ${entry.width}x${entry.height} ${(size / 1048576).toFixed(2)}MB`);
}
