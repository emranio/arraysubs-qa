#!/usr/bin/env node
// Publish a rendered motion piece to the website: web H.264 encode (no audio, faststart) plus a
// WebP poster from frame 0.
//   node publish.mjs <rendered.mp4> <web-content public path without extension> [--crf=22]
//   e.g. node publish.mjs out/home-pitch.mp4 media/home/pitch
import fs from 'node:fs';
import path from 'node:path';
import { execFileSync } from 'node:child_process';
import { fileURLToPath } from 'node:url';
import { createRequire } from 'node:module';

const HERE = path.dirname(fileURLToPath(import.meta.url));
const WEB = path.resolve(HERE, '../../../web-content/public');
const argv = process.argv.slice(2);
const crf = (argv.find((a) => a.startsWith('--crf=')) || '--crf=22').split('=')[1];
const [src, key] = argv.filter((a) => !a.startsWith('--'));
if (!src || !key) { console.log('usage: node publish.mjs <rendered.mp4> <public path without extension>'); process.exit(1); }
const mp4 = path.join(WEB, `${key}.mp4`);
const webp = path.join(WEB, `${key}.webp`);
fs.mkdirSync(path.dirname(mp4), { recursive: true });
execFileSync('ffmpeg', ['-y', '-loglevel', 'error', '-i', src, '-an', '-c:v', 'libx264', '-preset', 'veryslow', '-tune', 'animation',
  '-crf', crf, '-profile:v', 'high', '-level', '4.2', '-pix_fmt', 'yuv420p', '-color_range', 'tv', '-colorspace', 'bt709',
  '-color_primaries', 'bt709', '-color_trc', 'bt709', '-g', '120', '-movflags', '+faststart', mp4]);
// Poster: frame 0 as PNG, then WebP through the website's own sharp (this ffmpeg has no libwebp).
const png = `${webp}.tmp.png`;
execFileSync('ffmpeg', ['-y', '-loglevel', 'error', '-i', src, '-frames:v', '1', png]);
const sharp = createRequire(import.meta.url)(path.resolve(HERE, '../../../web-content/node_modules/sharp'));
await sharp(png).webp({ quality: 88 }).toFile(webp);
fs.rmSync(png);
const probe = JSON.parse(execFileSync('ffprobe', ['-v', 'error', '-select_streams', 'v:0', '-show_entries', 'stream=width,height:format=duration', '-of', 'json', mp4], { encoding: 'utf8' }));
console.log(`${key}: ${Number(probe.format.duration).toFixed(2)}s ${probe.streams[0].width}x${probe.streams[0].height} mp4 ${(fs.statSync(mp4).size / 1048576).toFixed(2)}MB, poster ${(fs.statSync(webp).size / 1024).toFixed(0)}KB`);
