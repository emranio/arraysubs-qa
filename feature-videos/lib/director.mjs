// Feature video director: drives the real local store inside a same-origin stage page,
// draws annotations/zoom/cursor on top, and captures a deterministic 30 fps timeline
// (frames are stepped, not real-time) straight into ffmpeg.
import { createRequire } from 'node:module';
import { spawn, execFileSync } from 'node:child_process';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const require = createRequire(import.meta.url);
const HERE = path.dirname(fileURLToPath(import.meta.url));
const PLUGINS = path.resolve(HERE, '../../..');
const { chromium } = require(path.join(PLUGINS, 'arraysubs/node_modules/playwright'));

export const SITE = 'http://localhost:10013';
// FV_W / FV_H / FV_DPR override the stage size (e.g. 1152x720 @ 5/3 = 1920x1200 short clips).
export const W = Number(process.env.FV_W) || 1440;
export const H = Number(process.env.FV_H) || 810;
export const DPR = Number(process.env.FV_DPR) || 4 / 3; // 1920x1080 output by default
export const FPS = 30;
const WP_ROOT = '/Users/emran/Local Sites/wp-test2/app/public';
const ENVRC = '/Users/emran/Local Sites/wp-test2/app/.envrc';
const STAGE_URL = `${SITE}/__fvstage/index.html`;

const clamp = (v, a, b) => Math.max(a, Math.min(b, v));
const lerp = (a, b, t) => a + (b - a) * t;
export const ease = {
  linear: (t) => t,
  inOut: (t) => (t < 0.5 ? 4 * t * t * t : 1 - Math.pow(-2 * t + 2, 3) / 2),
  out: (t) => 1 - Math.pow(1 - t, 3),
  in: (t) => t * t * t,
  outBack: (t) => { const c1 = 1.5, c3 = c1 + 1; return 1 + c3 * Math.pow(t - 1, 3) + c1 * Math.pow(t - 1, 2); },
};

/** Run WP-CLI against the local store (always --allow-root). Returns stdout. */
export function wp(args, { input } = {}) {
  const list = Array.isArray(args) ? args : ['eval', args];
  const quoted = list.map((a) => `'${String(a).replace(/'/g, `'\\''`)}'`).join(' ');
  const cmd = `source "${ENVRC}" >/dev/null 2>&1; export FV_PRO=1; cd "${WP_ROOT}" && wp ${quoted} --allow-root 2>/dev/null`;
  return execFileSync('/bin/bash', ['-c', cmd], { encoding: 'utf8', input, maxBuffer: 64 * 1024 * 1024 }).trim();
}

/** Run a PHP snippet through `wp eval` and parse its JSON output. */
export function wpJson(php) {
  const out = wp(['eval', php]);
  const start = out.search(/[[{]/);
  return JSON.parse(out.slice(start));
}

const cookieCache = new Map();
function authCookies(userId) {
  if (cookieCache.has(userId)) return cookieCache.get(userId);
  const data = wpJson(`$u=(int)${Number(userId)}; $e=time()+3*DAY_IN_SECONDS; echo json_encode(['hash'=>COOKIEHASH,'auth'=>wp_generate_auth_cookie($u,$e,'auth'),'li'=>wp_generate_auth_cookie($u,$e,'logged_in')]);`);
  const base = { domain: 'localhost', httpOnly: true, sameSite: 'Lax', secure: false, expires: Math.floor(Date.now() / 1000) + 2 * 86400 };
  const cookies = [
    { ...base, name: `wordpress_logged_in_${data.hash}`, value: data.li, path: '/' },
    { ...base, name: `wordpress_${data.hash}`, value: data.auth, path: '/wp-admin' },
    { ...base, name: `wordpress_${data.hash}`, value: data.auth, path: '/wp-content/plugins' },
  ];
  cookieCache.set(userId, cookies);
  return cookies;
}

// CSS injected into every page shown in the stage iframe: no scrollbars, no smooth
// scroll, no unrelated plugin noise.
const FRAME_CSS = `
html { scroll-behavior: auto !important; }
::-webkit-scrollbar { width: 0 !important; height: 0 !important; display: none !important; }
* { scrollbar-width: none !important; }
#trp-floater-ls, .trp-floating-switcher, .trp-language-switcher-container.trp-floater-ls-names { display: none !important; }
.update-nag, #update-nag, .notice.wcpdf-extensions-notice, .woocommerce-message.updated:not([class*="arraysubs"]),
.notice:not([class*="arraysubs"]):not(.inline):not(.woocommerce-message), .error:not([class*="arraysubs"]):not(.inline):not(.woocommerce-error):not(.form-invalid):not(.notice-error.inline),
#wpbody-content > .updated:not([class*="arraysubs"]), .elementor-message, #elementor-switch-mode, .wcpdf-pro-promo,
#loco-notices, .woocommerce-layout__notice-list-hide, .woocommerce-store-alerts, #woocommerce-embedded-root .woocommerce-store-alerts { display: none !important; }
#wp-admin-bar-elementor_inspector, #wp-admin-bar-trp_edit_translation, #wp-admin-bar-wp-logo, #wp-admin-bar-updates, #wp-admin-bar-comments, #wp-admin-bar-new-content, #wp-admin-bar-elementor_edit_page { display: none !important; }
`;

export class Video {
  constructor({ slug, outDir, chapters = null, log = console.log }) {
    this.slug = slug;
    this.outDir = outDir;
    this.only = chapters; // null = all, else Set of 1-based chapter numbers
    this.log = log;
    this.reviewDir = path.join(outDir, 'review');
    fs.mkdirSync(this.reviewDir, { recursive: true });
    this.cam = { s: 1, x: 0, y: 0 };
    this.cursor = { x: W * 0.62, y: H * 0.72, o: 0, press: 0 };
    this.ring = null; // { target, label, side, dim, num, pad }
    this.lowerState = { chip: '', kicker: '', text: '', o: 0, pos: 'bl' };
    this.cardState = { o: 0, ty: 0 };
    this.lastBuf = null;
    this.dirty = true;
    this.writer = null;
    this.frameCount = 0;
    this.stillIndex = 0;
    this.chapterNo = 0;
    this.kicker = '';
    this.inflight = new Set();
    this.recording = false;
    this.user = null;
  }

  // ---------------------------------------------------------------- lifecycle
  async init() {
    this.browser = await chromium.launch({
      headless: true,
      executablePath: process.env.FV_CHROME || `${process.env.HOME}/Library/Caches/ms-playwright/chromium_headless_shell-1255/chrome-headless-shell-mac-arm64/chrome-headless-shell`,
      args: ['--disable-gpu-vsync', '--disable-frame-rate-limit', '--hide-scrollbars', '--font-render-hinting=none', '--force-color-profile=srgb'],
    });
    this.context = await this.browser.newContext({
      viewport: { width: W, height: H },
      deviceScaleFactor: DPR,
      locale: 'en-US',
      timezoneId: 'UTC',
      colorScheme: 'light',
      reducedMotion: 'no-preference',
      ignoreHTTPSErrors: true,
    });
    const stageDir = path.join(HERE, 'stage');
    await this.context.route(`${SITE}/__fvstage/**`, (route) => {
      const rel = new URL(route.request().url()).pathname.replace('/__fvstage/', '') || 'index.html';
      const file = path.join(stageDir, rel);
      if (!file.startsWith(stageDir) || !fs.existsSync(file)) return route.fulfill({ status: 404, body: 'nope' });
      return route.fulfill({ path: file });
    });
    await this.context.addInitScript((css) => {
      if (window.top === window) return;
      const add = () => {
        if (document.getElementById('fv-frame-css')) return;
        const s = document.createElement('style');
        s.id = 'fv-frame-css';
        s.textContent = css;
        const host = document.head || document.documentElement;
        if (host) host.appendChild(s);
      };
      if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', add);
      add();
      // Native dialogs must never block a recording.
      window.alert = () => {};
    }, FRAME_CSS);
    this.page = await this.context.newPage();
    this.page.on('dialog', (d) => d.dismiss().catch(() => {}));
    this.page.on('pageerror', (e) => this.log(`  [pageerror] ${String(e.message).slice(0, 160)}`));
    const track = (req) => {
      const u = req.url();
      if (/heartbeat|__fvstage|google|gravatar|wp-cron|admin-ajax\.php\?action=as_async/.test(u)) return;
      if (req.frame() !== this.frame) return;
      this.inflight.add(req);
    };
    const untrack = (req) => this.inflight.delete(req);
    this.page.on('request', track);
    this.page.on('requestfinished', untrack);
    this.page.on('requestfailed', untrack);
    await this.page.goto(STAGE_URL);
    if (W !== 1440 || H !== 810) {
      await this.page.addStyleTag({ content: `html, body, #vp, #cam, #app, #card { width: ${W}px !important; height: ${H}px !important; }` });
    }
    await this.page.evaluate(() => window.S.ready());
    this.frame = this.page.frame({ name: 'app' });
    this.cdp = await this.context.newCDPSession(this.page);
    await this.apply(true);
  }

  async close() {
    await this.endSegment().catch(() => {});
    await this.browser?.close().catch(() => {});
  }

  /** Switch the logged-in WordPress user (id, login or 'guest'). Clears cookies. */
  async as(user) {
    let id = user;
    if (user === 'guest' || user === null) id = 0;
    else if (typeof user === 'string' && !/^\d+$/.test(user)) id = Number(wp(['user', 'get', user, '--field=ID']));
    id = Number(id);
    await this.context.clearCookies();
    await this.context.addCookies([{ name: 'fv_clean', value: '1', domain: 'localhost', path: '/', expires: Math.floor(Date.now() / 1000) + 86400 }]);
    if (id > 0) await this.context.addCookies(authCookies(id));
    this.user = id;
  }

  // ---------------------------------------------------------------- frames
  async apply(all = false) {
    const st = { cam: this.cam, cursor: this.cursor, lower: this.lowerState, card: this.cardState };
    if (this.ring) st.ring = await this.ringRect();
    else st.ring = { x: 0, y: 0, w: 0, h: 0, o: 0, label: '', num: '' };
    if (this.ripple) st.ripple = this.ripple;
    else st.ripple = { x: 0, y: 0, p: 0 };
    if (all) st.ghost = { o: 0 };
    await this.page.evaluate((s) => window.S.apply(s), st);
    this.dirty = true;
  }

  async capture() {
    if (!this.dirty && this.lastBuf) return this.lastBuf;
    await this.page.evaluate(() => new Promise((r) => requestAnimationFrame(() => requestAnimationFrame(r))));
    const { data } = await this.cdp.send('Page.captureScreenshot', { format: 'jpeg', quality: 93, optimizeForSpeed: false, fromSurface: true, captureBeyondViewport: false });
    this.lastBuf = Buffer.from(data, 'base64');
    this.dirty = false;
    return this.lastBuf;
  }

  async write(buf, n) {
    if (!this.recording || !this.writer || n <= 0) return;
    for (let i = 0; i < n; i++) {
      if (!this.writer.stdin.write(buf)) await new Promise((r) => this.writer.stdin.once('drain', r));
    }
    this.frameCount += n;
  }

  /** Emit the current state for `sec` seconds (one capture, repeated). */
  async hold(sec = 1, { still = true, note = '' } = {}) {
    await this.apply();
    const buf = await this.capture();
    const n = Math.max(1, Math.round(sec * FPS));
    await this.write(buf, n);
    if (still && this.recording && sec >= 0.6) this.saveStill(buf, note);
  }

  saveStill(buf, note = '') {
    this.stillIndex += 1;
    const name = `${String(this.chapterNo).padStart(2, '0')}-${String(this.stillIndex).padStart(3, '0')}${note ? '-' + note.replace(/[^a-z0-9]+/gi, '-').slice(0, 40) : ''}.jpg`;
    fs.writeFileSync(path.join(this.reviewDir, name), buf);
  }

  /** Step an animation frame by frame: fn(t in 0..1) mutates state, then a frame is captured. */
  async animate(sec, fn, easing = ease.inOut) {
    if (!this.recording) { await fn(1, 1); await this.apply(); return; }
    const n = Math.max(1, Math.round(sec * FPS));
    for (let i = 1; i <= n; i++) {
      await fn(easing(i / n), i / n);
      await this.apply();
      await this.write(await this.capture(), 1);
    }
  }

  // ---------------------------------------------------------------- segments
  async beginSegment(no) {
    this.chapterNo = no;
    this.stillIndex = 0;
    this.segPath = path.join(this.outDir, `seg-${String(no).padStart(2, '0')}.mp4`);
    const tmp = `${this.segPath}.part.mp4`;
    this.writer = spawn('ffmpeg', [
      '-y', '-loglevel', 'error', '-f', 'image2pipe', '-framerate', String(FPS), '-c:v', 'mjpeg', '-i', '-',
      '-an', '-c:v', 'libx264', '-preset', 'medium', '-tune', 'stillimage', '-crf', '20', '-g', String(FPS * 4),
      '-pix_fmt', 'yuv420p', '-r', String(FPS), '-movflags', '+faststart', tmp,
    ], { stdio: ['pipe', 'ignore', 'pipe'] });
    this.writerErr = '';
    this.writer.stderr.on('data', (d) => { this.writerErr += d; });
    this.writerDone = new Promise((res) => this.writer.on('close', res));
    this.writerTmp = tmp;
    this.recording = true;
    this.frameCount = 0;
  }

  async endSegment() {
    if (!this.writer) return;
    this.recording = false;
    this.writer.stdin.end();
    const code = await this.writerDone;
    this.writer = null;
    if (code !== 0) throw new Error(`ffmpeg failed for ${this.segPath}: ${this.writerErr}`);
    fs.renameSync(this.writerTmp, this.segPath);
    this.log(`  segment ${path.basename(this.segPath)}: ${(this.frameCount / FPS).toFixed(1)}s`);
  }

  // ---------------------------------------------------------------- geometry
  /** Resolve a target into a Playwright locator inside the app frame. */
  loc(target) {
    if (typeof target === 'string') return this.frame.locator(target).first();
    return target; // Locator
  }

  /** Rect of a target in app (iframe viewport) coordinates. */
  async appRect(target, { timeout = 12000 } = {}) {
    if (target && typeof target === 'object' && 'x' in target && 'w' in target) return target;
    const l = this.loc(target);
    await l.waitFor({ state: 'visible', timeout });
    return l.evaluate((el) => { const r = el.getBoundingClientRect(); return { x: r.x, y: r.y, w: r.width, h: r.height }; });
  }

  toStage(r) {
    const { s, x, y } = this.cam;
    return { x: r.x * s + x, y: r.y * s + y, w: r.w * s, h: r.h * s };
  }

  async ringRect() {
    const g = this.ring;
    let r;
    try {
      r = g.rect || (await this.appRect(g.target, { timeout: 4000 }));
    } catch {
      r = g.lastRect || { x: 0, y: 0, w: 0, h: 0 };
    }
    g.lastRect = r;
    const sr = this.toStage(r);
    const pad = g.pad ?? 8;
    return { x: sr.x - pad, y: sr.y - pad, w: sr.w + pad * 2, h: sr.h + pad * 2, o: g.o ?? 1, k: g.k ?? 1, dim: !!g.dim, label: g.label || '', side: g.side || 'auto', num: g.num || '' };
  }

  camFor(rect, scale) {
    const s = scale;
    const cx = rect.x + rect.w / 2, cy = rect.y + rect.h / 2;
    const x = clamp(W / 2 - cx * s, W - W * s, 0);
    const y = clamp(H / 2 - cy * s, H - H * s, 0);
    return { s, x, y };
  }

  async moveCam(to, sec = 0.9) {
    const from = { ...this.cam };
    // interpolate the visible app-space rect: left/top linear, width geometric
    const vr = (c) => ({ l: -c.x / c.s, t: -c.y / c.s, w: W / c.s });
    const a = vr(from), b = vr(to);
    await this.animate(sec, (t) => {
      const w = Math.exp(lerp(Math.log(a.w), Math.log(b.w), t));
      const l = lerp(a.l, b.l, t), tp = lerp(a.t, b.t, t);
      const s = W / w;
      this.cam = { s, x: -l * s, y: -tp * s };
    });
    this.cam = { ...to };
  }

  // ---------------------------------------------------------------- camera API
  /** Zoom the camera onto a target. scale defaults to a fit with padding (max 2.2). */
  async zoom(target, { scale, pad = 60, dur = 0.9, hold = 0 } = {}) {
    const r = await this.appRect(target);
    const fit = Math.min(W / (r.w + pad * 2), H / (r.h + pad * 2));
    const s = clamp(scale ?? fit, 1, 2.4);
    await this.moveCam(this.camFor(r, s), dur);
    if (hold) await this.hold(hold);
  }

  async zoomOut({ dur = 0.8, hold = 0 } = {}) {
    if (this.cam.s === 1 && this.cam.x === 0 && this.cam.y === 0) return;
    await this.moveCam({ s: 1, x: 0, y: 0 }, dur);
    if (hold) await this.hold(hold);
  }

  // ---------------------------------------------------------------- annotations
  /** Ring a target, optionally dim the rest and show a label pill next to it. */
  async focus(target, { label = '', side = 'auto', dim = false, num = '', pad = 8, dur = 0.35, hold } = {}) {
    if (this.ring) await this.unfocus({ dur: 0.18 });
    this.ring = { target, label, side, dim, num, pad, o: 0, k: 1.12 };
    this.ring.rect = await this.appRect(target);
    await this.animate(dur, (t) => { this.ring.o = t; this.ring.k = lerp(1.12, 1, t); }, ease.out);
    const h = hold ?? (label ? clamp(1.1 + label.length * 0.035, 1.4, 3.6) : 1.0);
    if (h) await this.hold(h, { note: label || 'focus' });
  }

  async unfocus({ dur = 0.22 } = {}) {
    if (!this.ring) return;
    await this.animate(dur, (t) => { this.ring.o = 1 - t; }, ease.linear);
    this.ring = null;
    await this.apply();
  }

  /** Lower-third caption. Pass null to hide. */
  async say(text, { kicker, pos, hold, chip } = {}) {
    const L = this.lowerState;
    if (L.text && L.o > 0) {
      await this.animate(0.16, (t) => { L.o = 1 - t; }, ease.linear);
    }
    if (!text) { L.text = ''; L.o = 0; await this.apply(); return; }
    L.text = text;
    L.kicker = kicker ?? this.kicker;
    L.chip = chip ?? (this.chapterNo ? String(this.chapterNo).padStart(2, '0') : '');
    if (pos) L.pos = pos;
    await this.animate(0.3, (t) => { L.o = t; }, ease.out);
    const h = hold ?? clamp(0.9 + text.length * 0.03, 1.4, 3.6);
    if (h) await this.hold(h, { note: 'say' });
  }

  captionPos(pos) { this.lowerState.pos = pos; }

  // ---------------------------------------------------------------- cards
  async card(spec, { variant = '', dur = 0.65 } = {}) {
    const html = await this.page.evaluate((c) => window.S.cardHtml(c), spec);
    this.cardState = { html, variant, o: 1, ty: 100 };
    await this.animate(dur, (t) => { this.cardState.ty = 100 * (1 - t); }, ease.inOut);
  }

  /** Show a card instantly (used at the start of a segment so cuts are seamless). */
  async cardNow(spec, { variant = '' } = {}) {
    const html = await this.page.evaluate((c) => window.S.cardHtml(c), spec);
    this.cardState = { html, variant, o: 1, ty: 0 };
    await this.apply();
  }

  async cardOut({ dur = 0.7 } = {}) {
    if (!this.cardState.o) return;
    await this.animate(dur, (t) => { this.cardState.ty = -100 * t; }, ease.inOut);
    this.cardState = { ...this.cardState, o: 0, ty: 0 };
    await this.apply();
  }

  // ---------------------------------------------------------------- page state
  async settle({ timeout = 20000, quiet = 450, extra = 250 } = {}) {
    const f = this.frame;
    const t0 = Date.now();
    await f.waitForLoadState('domcontentloaded', { timeout }).catch(() => {});
    await f.waitForLoadState('load', { timeout: Math.max(1000, timeout - (Date.now() - t0)) }).catch(() => {});
    // network quiet
    let quietSince = Date.now();
    while (Date.now() - t0 < timeout) {
      if (this.inflight.size) quietSince = Date.now();
      if (Date.now() - quietSince >= quiet) break;
      await new Promise((r) => setTimeout(r, 60));
    }
    // no visible spinners / loaders
    await f.waitForFunction(() => {
      const sel = '.arraysubs-page-loader, .components-spinner, .arraysubs-spin, .spinner.is-active, [aria-busy="true"], .arraysubs-skeleton, .wc-block-components-spinner, .blockUI.blockOverlay, .arraysubs-loading, .arraysubs-portal-spinner, .wc-block-components-skeleton, .is-loading';
      return ![...document.querySelectorAll(sel)].some((e) => {
        const r = e.getBoundingClientRect();
        const cs = getComputedStyle(e);
        return r.width > 0 && r.height > 0 && cs.visibility !== 'hidden' && cs.display !== 'none' && Number(cs.opacity) > 0.05;
      });
    }, null, { timeout: Math.max(1500, timeout - (Date.now() - t0)), polling: 120 }).catch(() => this.log('  [settle] loader still visible, continuing'));
    await f.evaluate(() => document.fonts && document.fonts.ready).catch(() => {});
    await new Promise((r) => setTimeout(r, extra));
  }

  /** Freeze the current frame on top, run fn, settle, then crossfade to the new state. */
  async transition(fn, { fade = 0.32, settle = true, during = null } = {}) {
    const cardUp = this.cardState.o > 0;
    if (!cardUp && this.recording) {
      // freeze the app picture (without overlays) under the live overlays
      await this.page.evaluate(() => window.S.bare(true));
      this.dirty = true;
      const buf = await this.capture();
      await this.page.evaluate((src) => { window.S.bare(false); return window.S.apply({ ghost: { src, o: 1 } }); }, `data:image/jpeg;base64,${buf.toString('base64')}`);
      await this.page.evaluate(() => new Promise((r) => { const i = document.getElementById('ghost'); i.complete ? r() : i.onload = r; }));
      this.dirty = true;
    }
    try {
      await fn();
      if (settle) await this.settle();
    } finally {
      if (this.ring) this.ring.rect = null;
    }
    if (this.ring) {
      try { this.ring.rect = await this.appRect(this.ring.target, { timeout: 3000 }); } catch { this.ring = null; }
    }
    if (cardUp) { await this.cardOut(); return; }
    if (!this.recording) { await this.page.evaluate(() => window.S.apply({ ghost: { o: 0 } })); return; }
    await this.animate(fade, async (t) => {
      if (during) during(t);
      await this.page.evaluate((o) => window.S.apply({ ghost: { o } }), 1 - t);
    }, ease.linear);
    await this.page.evaluate(() => window.S.apply({ ghost: { o: 0, src: '' } }));
    this.dirty = true;
  }

  /** Navigate the app frame (relative URLs resolve against the site). */
  async goto(url, { fade = 0.35, hold = 0.4, zoomReset = true } = {}) {
    const full = /^https?:/.test(url) ? url : `${SITE}${url.startsWith('/') ? '' : '/'}${url}`;
    if (this.ring) { this.ring = null; await this.apply(); }
    await this.transition(async () => {
      if (zoomReset) { this.cam = { s: 1, x: 0, y: 0 }; await this.apply(); }
      await this.frame.goto(full, { waitUntil: 'domcontentloaded', timeout: 60000 });
    }, { fade });
    if (hold) await this.hold(hold, { still: true, note: 'goto' });
  }

  /** Run arbitrary work against the app frame off-camera, then crossfade. */
  async act(fn, opts = {}) {
    if (this.ring) { this.ring = null; await this.apply(); }
    await this.transition(() => fn(this.frame, this.page), opts);
  }

  async reload(opts = {}) {
    await this.act((f) => f.goto(f.url(), { waitUntil: 'domcontentloaded' }), opts);
  }

  // ---------------------------------------------------------------- scrolling
  /** Smoothly scroll the target into view (block: center|start|end) or scroll to y (number). */
  async scrollTo(target, { block = 'center', dur, offset = 0, hold = 0.25 } = {}) {
    const wasRing = this.ring;
    if (wasRing) await this.unfocus({ dur: 0.15 });
    let info;
    if (typeof target === 'number') {
      info = await this.frame.evaluate((y) => {
        const s = document.scrollingElement; window.__fvScroller = s;
        return { from: s.scrollTop, to: Math.max(0, Math.min(s.scrollHeight - s.clientHeight, y)) };
      }, target);
    } else {
      const l = this.loc(target);
      await l.waitFor({ state: 'attached', timeout: 12000 });
      info = await l.evaluate((el, o) => {
        const isScroll = (p) => { const cs = getComputedStyle(p); return /(auto|scroll)/.test(cs.overflowY) && p.scrollHeight > p.clientHeight + 2; };
        let s = null;
        for (let p = el.parentElement; p && p !== document.body && p !== document.documentElement; p = p.parentElement) if (isScroll(p)) { s = p; break; }
        const doc = !s; if (doc) s = document.scrollingElement;
        window.__fvScroller = s;
        const r = el.getBoundingClientRect();
        const top = doc ? 0 : s.getBoundingClientRect().top;
        const vh = doc ? innerHeight : s.clientHeight;
        const head = doc ? (document.getElementById('wpadminbar')?.offsetHeight || 0) : 0;
        let delta;
        if (o.block === 'start') delta = r.top - top - head - 24;
        else if (o.block === 'end') delta = r.bottom - top - vh + 24;
        else delta = r.top - top - head / 2 - (vh - Math.min(r.height, vh * 0.8)) / 2;
        delta += o.offset;
        const max = s.scrollHeight - s.clientHeight;
        return { from: s.scrollTop, to: Math.max(0, Math.min(max, s.scrollTop + delta)) };
      }, { block, offset });
    }
    const dist = Math.abs(info.to - info.from);
    if (dist < 4) return;
    const d = dur ?? clamp(0.45 + dist / 1500, 0.55, 1.6);
    await this.animate(d, async (t) => {
      await this.frame.evaluate((y) => { window.__fvScroller.scrollTop = y; }, info.from + (info.to - info.from) * t);
    });
    if (hold) await this.hold(hold, { still: false });
  }

  async scrollBy(dy, opts = {}) {
    const y = await this.frame.evaluate(() => document.scrollingElement.scrollTop);
    await this.scrollTo(y + dy, opts);
  }

  // ---------------------------------------------------------------- pointer
  /** Ensure a target is in the app viewport and inside the camera's visible area. */
  async reveal(target) {
    let r = await this.appRect(target);
    const vh = await this.frame.evaluate(() => innerHeight);
    if (r.y < 40 || r.y + Math.min(r.h, 60) > vh - 10) {
      await this.scrollTo(target);
      r = await this.appRect(target);
    }
    const sr = this.toStage(r);
    const cx = sr.x + sr.w / 2, cy = sr.y + Math.min(sr.h, 40) / 2;
    if (this.cam.s > 1 && (cx < 20 || cx > W - 20 || cy < 20 || cy > H - 20)) {
      await this.moveCam(this.camFor(r, this.cam.s), 0.6);
      r = await this.appRect(target);
    }
    return r;
  }

  async showCursor() {
    if (this.cursor.o >= 1) return;
    await this.animate(0.2, (t) => { this.cursor.o = t; }, ease.linear);
  }

  async hideCursor() {
    if (this.cursor.o <= 0) return;
    await this.animate(0.2, (t) => { this.cursor.o = 1 - t; }, ease.linear);
  }

  /** Move the fake (and real) cursor to a target. Returns the stage point. */
  async moveTo(target, { dx = 0.5, dy = 0.5, dur } = {}) {
    const r = await this.reveal(target);
    const sr = this.toStage(r);
    const tx = sr.x + sr.w * dx, ty = sr.y + sr.h * dy;
    await this.showCursor();
    const fx = this.cursor.x, fy = this.cursor.y;
    const dist = Math.hypot(tx - fx, ty - fy);
    const d = dur ?? clamp(0.35 + dist / 1600, 0.4, 0.95);
    // slight arc
    const nx = -(ty - fy) / (dist || 1), ny = (tx - fx) / (dist || 1);
    const bow = Math.min(60, dist * 0.12);
    await this.animate(d, (t) => {
      const arc = Math.sin(Math.PI * t) * bow;
      this.cursor.x = lerp(fx, tx, t) + nx * arc;
      this.cursor.y = lerp(fy, ty, t) + ny * arc;
    });
    await this.page.mouse.move(tx, ty, { steps: 3 });
    this.dirty = true;
    return { x: tx, y: ty };
  }

  async hover(target, { hold = 0.6, ...o } = {}) {
    await this.moveTo(target, o);
    await new Promise((r) => setTimeout(r, 250));
    this.dirty = true;
    if (hold) await this.hold(hold, { still: false });
  }

  /** Animate a click on a target, perform a real click there, settle and crossfade. */
  async click(target, { fade = 0.28, settle = true, dx, dy, wait = 0, hold = 0.35, nav = false } = {}) {
    const p = await this.moveTo(target, { dx, dy });
    if (this.ring) await this.unfocus({ dur: 0.15 });
    await this.animate(0.1, (t) => { this.cursor.press = t; }, ease.out);
    this.ripple = { x: p.x, y: p.y, p: 0.02 };
    await this.transition(async () => {
      const navP = nav ? this.frame.waitForNavigation({ timeout: 30000 }).catch(() => {}) : null;
      await this.page.mouse.click(p.x, p.y);
      if (navP) await navP;
      if (wait) await new Promise((r) => setTimeout(r, wait));
    }, {
      fade: Math.max(fade, 0.3),
      settle,
      during: (t) => { this.cursor.press = 1 - t; this.ripple.p = Math.max(0.02, t); },
    });
    this.cursor.press = 0;
    this.ripple = null;
    if (hold) await this.hold(hold, { still: false });
  }

  /** Click a target, then type text into it character by character. */
  async type(target, text, { clear = true, cps = 18, click = true, hold = 0.4 } = {}) {
    if (click) await this.click(target, { hold: 0.1, settle: false });
    else await this.loc(target).focus();
    if (clear) {
      await this.page.keyboard.press('ControlOrMeta+A');
      await this.page.keyboard.press('Backspace');
    }
    const per = Math.max(1, Math.round(FPS / (text.length > 40 ? cps * 1.8 : cps)));
    for (const ch of text) {
      await this.page.keyboard.type(ch);
      this.dirty = true;
      await this.write(await this.capture(), per);
    }
    await new Promise((r) => setTimeout(r, 200));
    this.dirty = true;
    if (hold) await this.hold(hold, { still: false });
  }

  async press(key, opts = {}) {
    await this.transition(() => this.page.keyboard.press(key), opts);
  }

  /** Pick an option in a native <select> (cursor clicks it, value changes, crossfade). */
  async select(target, value, opts = {}) {
    await this.moveTo(target);
    await this.animate(0.1, (t) => { this.cursor.press = t; });
    await this.transition(() => this.loc(target).selectOption(value), { fade: 0.25, ...opts });
    await this.animate(0.15, (t) => { this.cursor.press = 1 - t; });
  }

  // ---------------------------------------------------------------- misc
  poster() {
    if (this.lastBuf) fs.writeFileSync(path.join(this.outDir, 'poster.jpg'), this.lastBuf);
  }

  async eval(fn, arg) { return this.frame.evaluate(fn, arg); }

  async debugShot(name) {
    const buf = await this.capture();
    fs.writeFileSync(path.join(this.outDir, `${name}.jpg`), buf);
  }
}

/** Convert a JPEG to WebP with headless Chrome's canvas encoder (ffmpeg here has no libwebp). */
export async function toWebp(jpgPath, webpPath, { quality = 0.86, width = 1920 } = {}) {
  const browser = await chromium.launch({
    headless: true,
    executablePath: process.env.FV_CHROME || `${process.env.HOME}/Library/Caches/ms-playwright/chromium_headless_shell-1255/chrome-headless-shell-mac-arm64/chrome-headless-shell`,
  });
  try {
    const page = await browser.newPage();
    const b64 = fs.readFileSync(jpgPath).toString('base64');
    const out = await page.evaluate(async ({ b64, quality, width }) => {
      const img = new Image();
      img.src = `data:image/jpeg;base64,${b64}`;
      await img.decode();
      const c = document.createElement('canvas');
      c.width = width;
      c.height = Math.round((img.naturalHeight / img.naturalWidth) * width);
      c.getContext('2d').drawImage(img, 0, 0, c.width, c.height);
      return c.toDataURL('image/webp', quality).split(',')[1];
    }, { b64, quality, width });
    fs.writeFileSync(webpPath, Buffer.from(out, 'base64'));
  } finally {
    await browser.close();
  }
}
