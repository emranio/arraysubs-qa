/* ArraySubs homepage motion pitch. One paused GSAP master timeline, seeked frame by frame.
 * Text, flat shapes, colours and flat objects only: no screenshots, windows or UI panels.
 * Free act (box reward, renewal, recovery, members, drip, Home) -> divider -> Pro act
 * (save offer, bundle, store credit, refunds + churn, forecast) -> end card -> seamless loop.
 * Desktop cut 1920x1200; `?layout=phone` gives the 1080x1080 phone cut (text on top, scene below).
 * Storyboard and fact sources: STORYBOARD.md. */
(async function () {
  await document.fonts.load("700 100px 'FD'");
  await document.fonts.load("700 100px 'FS'");
  await document.fonts.load("600 100px 'FS'");
  await document.fonts.ready;
  gsap.registerPlugin(CustomEase, CustomWiggle, DrawSVGPlugin, MorphSVGPlugin, MotionPathPlugin);
  // Origin changes must never nudge x/y (irises grow from different points of the same disc).
  if (window.CSSPlugin) window.CSSPlugin.defaultSmoothOrigin = false;

  const C = {
    violet: '#7E43F6', vs: '#6A38CF', tint: '#EFE8FE', orange: '#FF4D00', o100: '#FFE3D5', otext: '#A13504',
    ink: '#14100B', ink3: '#262626', p2: '#F2F2F2', p3: '#E7E7E7', line: '#DCDCDC', dot: '#C7AEFB',
    band: '#39225D', pend: '#AB85F9', white: '#FFFFFF',
  };
  const DUR = 44.2;

  // ---------------------------------------------------------------- layout (desktop / phone)
  const PHONE = /[?&]layout=phone\b/.test(location.search);
  const L = PHONE ? {
    W: 1080, H: 1080, tx: 72, tierTop: 140,
    head: { size: 112, base: 332, max: 936, floor: 96 }, sub: { size: 54, base: 512, max: 936 }, statusTop: 548,
    ticks: { left: 72, top: 1036, dot: 10, gap: 10, on: 36, split: 14 }, chipK: 1.45, dots: { left: 906, top: 970 }, orange: { cx: 1048, cy: 14, r: 170 }, ring: { r: 112, sw: 28 },
    scene: { s: 0.58, x: 540, y: 862 }, objClip: 'polygon(-4000px 240px, 6000px 240px, 6000px 5000px, -4000px 5000px)', flood: 5.6,
    cx: 540, hook: { size: 118, builtBase: 492, kcBase: 562, shift: 50 }, squig: { x: 96, y: 770, w: 180 }, squigEnd: { dx: -10, dy: 150 },
    dv: { size: 84, base: 492, pitch: 96, max: 980 }, dvSquig: { x: 390, y: 662, w: 300 },
    end: { wordSize: 56, wordBase: 296, size: 80, base: 420, pitch: 78, l3Base: 576, max: 980, pillY: 690, pillSize: 40, pillH: 76, pillPad: 30, pillMax: 960, rowGap: 92 },
  } : {
    W: 1920, H: 1200, tx: 192, tierTop: 268,
    head: { size: 120, base: 508, max: 872, floor: 110 }, sub: { size: 52, base: 730, max: 872 }, statusTop: 816,
    ticks: { left: 192, top: 1104, dot: 14, gap: 16, on: 56, split: 24 }, chipK: 1, dots: { left: 1650, top: 1036 }, orange: { cx: 1880, cy: 30, r: 260 }, ring: { r: 190, sw: 40 },
    scene: null, objClip: 'none', flood: 4.7,
    cx: 960, hook: { size: 168, builtBase: 615, kcBase: 700, shift: 70 }, squig: { x: 230, y: 900, w: 220 }, squigEnd: { dx: -30, dy: 120 },
    dv: { size: 120, base: 540, pitch: 120, max: 1520 }, dvSquig: { x: 760, y: 730, w: 400 },
    end: { wordSize: 72, wordBase: 316, size: 112, base: 506, pitch: 103, l3Base: 726, max: 1520, pillY: 860, pillSize: 46, pillH: 88, pillPad: 36, pillMax: 1680, rowGap: 96 },
  };

  const $ = (id) => document.getElementById(id);
  const NS = 'http://www.w3.org/2000/svg';
  const stage = $('stage'), textL = $('textL'), chipL = $('chipL'), objL = $('objL'), discL = $('discL'), fxL = $('fxL'), baton = $('baton');
  stage.style.width = `${L.W}px`; stage.style.height = `${L.H}px`;
  [textL, fxL, $('ringL'), $('orangeL')].forEach((e) => { e.style.width = `${L.W}px`; e.style.height = `${L.H}px`; if (e.tagName === 'svg') e.setAttribute('viewBox', `0 0 ${L.W} ${L.H}`); });
  if (L.scene) {
    const { s, x, y } = L.scene;
    [discL, objL, chipL].forEach((e) => { e.style.transform = `translate(${x - 1440 * s}px, ${y - 620 * s}px) scale(${s})`; });
  }
  objL.style.clipPath = L.objClip;
  const tl = gsap.timeline({ paused: true, defaults: { immediateRender: false } });

  CustomWiggle.create('wig3', { wiggles: 3, type: 'easeOut' });
  CustomWiggle.create('wig4', { wiggles: 4, type: 'easeOut' });
  CustomWiggle.create('wig5', { wiggles: 5, type: 'easeOut' });

  // ---------------------------------------------------------------- DOM helpers
  const div = (cls, parent, css = '') => { const e = document.createElement('div'); if (cls) e.className = cls; if (css) e.style.cssText = css; parent.appendChild(e); return e; };
  const S = (tag, a = {}, p = objL) => { const e = document.createElementNS(NS, tag); for (const k in a) e.setAttribute(k, a[k]); if (p) p.appendChild(e); return e; };
  /** Object group: outer translate (static) + inner group animated around its local origin. */
  const G = (cx, cy, html = '', p = objL) => {
    const o = S('g', { transform: `translate(${cx} ${cy})` }, p);
    const g = S('g', {}, o);
    if (html) g.innerHTML = html;
    gsap.set(g, { svgOrigin: '0 0' });
    return g;
  };
  const iconInner = (n) => window.ICONS[n].replace(/^<svg[^>]*>/, '').replace(/<\/svg>$/, '');
  const icon = (n, x, y, size, color, sw = 2) =>
    `<svg x="${x}" y="${y}" width="${size}" height="${size}" viewBox="0 0 24 24" fill="none" stroke="${color}" stroke-width="${sw}" stroke-linecap="round" stroke-linejoin="round">${iconInner(n)}</svg>`;
  const rectOf = (el) => { const r = el.getBoundingClientRect(); return { left: r.left, top: r.top, width: r.width, height: r.height }; };
  const clipDisc = (t) => tl.set(objL, { clipPath: 'circle(340px at 1440px 620px)' }, t);
  const clipOff = (t) => tl.set(objL, { clipPath: L.objClip }, t);

  // z-ordered object groups (back to front)
  const zBack = S('g'), zMid = S('g'), zTop = S('g');
  // phone: clip a group to the disc while it falls in through the white gap above the scene
  const discClip = S('clipPath', { id: 'discClipBox', clipPathUnits: 'userSpaceOnUse' }, S('defs'));
  S('circle', { cx: 0, cy: -100, r: 340 }, discClip);
  const rimClip = (el, t0, t1) => { if (!PHONE) return; el.setAttribute('clip-path', 'none'); tl.set(el, { attr: { 'clip-path': 'url(#discClipBox)' } }, t0); tl.set(el, { attr: { 'clip-path': 'none' } }, t1); };

  // Baseline ratio (baseline offset from a 0.92 line box top, per px of font size).
  const baseRatio = (family, weight) => {
    const d = div('', document.body, `position:absolute;left:0;top:0;font:${weight} 100px/0.92 ${family};white-space:nowrap`);
    d.innerHTML = 'H<span style="display:inline-block;width:1px;height:0;vertical-align:baseline"></span>';
    const r = d.getBoundingClientRect(), b = d.lastChild.getBoundingClientRect();
    d.remove();
    return (b.bottom - r.top) / 100;
  };
  const RAT = { FD: baseRatio("'FD'", 700), FS: baseRatio("'FS'", 700) };
  const PAD_T = 0.1; // .line padding-top (em)

  // ---------------------------------------------------------------- text system
  /** Lines with word spans inside overflow masks. `[a b]` = marked span (sits on the baton). */
  function text({ lines, size, base, pitch, x = L.tx, align = 'left', cx = L.cx, family = 'FD', weight = 700, color = C.ink,
    mark = C.white, maxW = L.head.max, floor = null, tracking = null, parent = textL, hidden = true }) {
    const out = { lines: [], marks: [], words: [], size };
    const build = (sz) => {
      out.lines.forEach((l) => l.el.remove());
      out.lines = []; out.marks = []; out.words = [];
      const p = pitch ?? sz * 0.92;
      lines.forEach((src, i) => {
        const ln = div(`line ${align === 'center' ? 'center ' : ''}${family === 'FD' ? 'disp' : 'sans'}`, parent);
        ln.style.fontSize = `${sz}px`;
        ln.style.color = color;
        ln.style.fontWeight = weight;
        if (tracking) ln.style.letterSpacing = tracking;
        if (align === 'center') { ln.style.left = `${cx - 960}px`; } else ln.style.left = `${x}px`;
        ln.style.top = `${base + i * p - RAT[family] * sz - PAD_T * sz}px`;
        const lw = document.createElement('span'); lw.className = 'lw'; ln.appendChild(lw);
        const words = [];
        const re = /\[([^\]]+)\]|([^\s[]+)|(\s+)/g;
        let m;
        while ((m = re.exec(src))) {
          if (m[3]) { lw.appendChild(document.createTextNode(' ')); continue; }
          const w = document.createElement('span'); w.className = 'w';
          w.textContent = m[1] ?? m[2];
          if (m[1]) { w.style.color = mark; w.dataset.mark = '1'; w.style.margin = '0 0.22em'; }
          lw.appendChild(w); words.push(w);
        }
        out.lines.push({ el: ln, lw, words });
        out.words.push(...words);
      });
    };
    build(size);
    if (maxW) {
      const widest = Math.max(...out.lines.map((l) => l.lw.getBoundingClientRect().width));
      if (widest > maxW) {
        const sz = Math.max(floor ?? size * 0.88, Math.floor(size * maxW / widest));
        out.size = sz; build(sz);
        const w2 = Math.max(...out.lines.map((l) => l.lw.getBoundingClientRect().width));
        if (w2 > maxW + 1) console.warn(`text too wide (${Math.round(w2)} > ${maxW}): ${lines.join(' / ')}`);
      }
    }
    out.words.forEach((w) => { if (w.dataset.mark) out.marks.push({ el: w, r: rectOf(w) }); });
    if (hidden) gsap.set(out.words, { yPercent: 140 });
    return out;
  }
  const head = (lines, o = {}) => text({ lines, size: L.head.size, base: L.head.base, floor: L.head.floor, ...o });
  const sub = (s, o = {}) => text({ lines: [s], size: L.sub.size, base: L.sub.base, family: 'FS', weight: 600, color: C.ink3, tracking: '-0.01em', maxW: L.sub.max, ...o });

  const IN = (words, t, d = 0.55, s = 0.045, ease = 'expo.out') => tl.fromTo(words, { yPercent: 140 }, { yPercent: 0, duration: d, ease, stagger: s }, t);
  const headIn = (h, t) => h.lines.forEach((l, i) => IN(l.words, t + i * 0.1));
  const lineIn = (h, i, t, d = 0.5) => IN(h.lines[i].words, t, d);
  /** A word the baton leaves closes its marker gap, so the punctuation sits back against it. */
  const unmark = (el, t, d = 0.45) => tl.to(el, { marginLeft: '0em', marginRight: '0em', duration: d, ease: 'power3.inOut' }, t);
  /** Beat exit: the word on the baton (and its punctuation) sinks into the pill; every other word rises out. */
  function exitText(h, t) {
    const live = h.marks.length ? h.marks[h.marks.length - 1].el : null; // the word the baton sits on
    const first = [];
    if (live) h.lines.forEach((l) => l.words.forEach((w, i) => {
      if (w !== live) return;
      first.push(w);
      const nx = l.words[i + 1];
      if (nx && /^[.,?!]$/.test(nx.textContent)) first.push(nx);
    }));
    h.marks.forEach((m) => unmark(m.el, t, 0.3));
    const rest = h.words.filter((w) => !first.includes(w));
    // the mask bottom matches the pill bottom, so the white word is clipped cleanly at the pill edge
    if (first.length) tl.to(first, { yPercent: 140, duration: 0.3, ease: 'power3.out' }, t);
    if (rest.length) tl.to(rest, { yPercent: -140, duration: 0.35, ease: 'power3.in', stagger: 0.025 }, t);
  }
  const subIn = (s, t) => tl.fromTo(s.words, { yPercent: 140 }, { yPercent: 0, duration: 0.45, ease: 'power3.out' }, t);
  const subOut = (s, t) => tl.to(s.words, { yPercent: -140, duration: 0.3, ease: 'power3.in' }, t);

  // ---------------------------------------------------------------- baton (the one marker)
  const px = (r) => ({ left: r.left, top: r.top, width: r.width, height: r.height });
  const brect = (r, s) => ({ left: r.left - 0.22 * s, top: r.top + 0.04 * s, width: r.width + 0.44 * s, height: r.height - 0.04 * s + 0.21 * s });
  const markRect = (h, i = 0) => brect(h.marks[i].r, h.size);
  const BATON = (t, rect, d = 0.5) => tl.to(baton, { ...px(rect), duration: d, ease: 'power3.inOut' }, t);
  const batonColor = (t, color, d = 0.25) => tl.to(baton, { backgroundColor: color, duration: d, ease: 'none' }, t);
  /** Violet <-> orange swaps are hard cuts once the pill has left the old word and before the next word rises (no muddy RGB blend). */
  const batonSwap = (t, color) => tl.set(baton, { backgroundColor: color }, t);

  // ---------------------------------------------------------------- status rows
  const TONES = {
    Rewarded: [C.o100, C.otext, 'gift'], Paid: [C.violet, C.white, 'truck'], Recovered: [C.tint, C.vs, 'mail-check'],
    Granted: [C.p2, C.ink, 'key-round'], Dripped: [C.white, C.vs, 'book-open', true], Tracked: [C.tint, C.vs, 'chart-line'],
    Saved: [C.ink, C.white, 'heart-handshake'], Bundled: [C.violet, C.white, 'boxes'], Applied: [C.o100, C.otext, 'wallet'],
    Flagged: [C.ink, C.white, 'gauge'], Projected: [C.white, C.vs, 'trending-up', true],
  };
  function status(word) {
    const [bg, fg, ic, ring] = TONES[word];
    const s = div('status', textL, `left:${L.tx}px;top:${L.statusTop}px`);
    const border = ring ? `border:4px solid ${C.violet};` : '';
    const disc = div('disc', s, `background:${bg};color:${fg};${border}`); disc.innerHTML = window.ICONS[ic];
    const pill = div('pill', s, `background:${bg};${border}`);
    const w = div('word', s, `color:${fg}`); w.textContent = word;
    pill.style.width = `${w.getBoundingClientRect().width}px`;
    gsap.set(disc, { scale: 0 }); gsap.set(pill, { scaleX: 0 }); gsap.set(w, { clipPath: 'inset(0% 100% 0% 0%)' });
    return { disc, pill, w };
  }
  const STATUS = (s, t) => {
    tl.fromTo(s.disc, { scale: 0 }, { scale: 1, duration: 0.3, ease: 'back.out(2)' }, t);
    tl.fromTo(s.pill, { scaleX: 0 }, { scaleX: 1, duration: 0.3, ease: 'power3.out' }, t + 0.08);
    tl.fromTo(s.w, { clipPath: 'inset(0% 100% 0% 0%)', x: -24 }, { clipPath: 'inset(0% 0% 0% 0%)', x: 0, duration: 0.25, ease: 'power2.out' }, t + 0.15);
  };
  const STATUS_OUT = (s, t) => {
    tl.to(s.w, { clipPath: 'inset(0% 100% 0% 0%)', duration: 0.2, ease: 'power2.in' }, t);
    tl.to(s.pill, { scaleX: 0, duration: 0.3, ease: 'back.in(1.7)' }, t);
    tl.to(s.disc, { scale: 0, duration: 0.25, ease: 'back.in(1.7)' }, t + 0.05);
  };

  // ---------------------------------------------------------------- chips (HTML, centred)
  function chip(label, x, y, { bg = C.white, fg = C.ink, ring = null, ringW = 3, size = 44, h = 80, pad = 30, html = null, parent = chipL } = {}) {
    // scene chips are scaled down with the scene on phone, so they get bigger type there
    const k = parent === chipL ? L.chipK : 1;
    const e = div('chip', parent, `left:${x}px;top:${y}px;background:${bg};color:${fg};height:${h * k}px;padding:0 ${pad * k}px;font-size:${size * k}px;gap:${14 * k}px;${ring ? `border:${ringW * k}px solid ${ring};` : ''}`);
    if (html) e.innerHTML = html; else e.textContent = label;
    if (k !== 1) e.querySelectorAll('svg').forEach((v) => { v.style.width = v.style.height = `${40 * k}px`; });
    gsap.set(e, { xPercent: -50, yPercent: -50, scale: 0 });
    return e;
  }
  const pop = (e, t, d = 0.35, ease = 'back.out(1.6)') => tl.fromTo(e, { scale: 0 }, { scale: 1, duration: d, ease }, t);
  const unpop = (e, t, d = 0.3, ease = 'back.in(1.7)') => tl.to(e, { scale: 0, duration: d, ease }, t);

  // ---------------------------------------------------------------- discs
  let curDisc = null;
  const disc = (color) => { const c = S('circle', { cx: 1440, cy: 620, r: 340, fill: color }, discL); gsap.set(c, { scale: 0, svgOrigin: '1440 620' }); return c; };
  function IRIS(d, t, ox = 1440, oy = 620) {
    tl.fromTo(d, { scale: 0, x: 0, y: 0 }, { scale: 1, x: 0, y: 0, duration: 0.6, ease: 'expo.out', svgOrigin: `${ox} ${oy}`, smoothOrigin: false }, t);
    if (curDisc) tl.set(curDisc, { visibility: 'hidden' }, t + 0.6);
    curDisc = d;
  }

  // ---------------------------------------------------------------- spark accent
  function SPARK(t, x, y) {
    const g = G(x, y, '', zTop);
    const lines = [-20, -55, -90].map((a) => {
      const r = (a * Math.PI) / 180;
      return S('line', { x1: Math.cos(r) * 34, y1: Math.sin(r) * 34, x2: Math.cos(r) * 78, y2: Math.sin(r) * 78, stroke: C.orange, 'stroke-width': 10, 'stroke-linecap': 'round' }, g);
    });
    gsap.set(g, { scale: 0 });
    tl.fromTo(g, { scale: 0 }, { scale: 1, duration: 0.3, ease: 'expo.out' }, t);
    tl.fromTo(lines, { drawSVG: '0% 100%' }, { drawSVG: '100% 100%', duration: 0.2, ease: 'power2.in' }, t + 0.45);
    tl.set(g, { visibility: 'hidden' }, t + 0.66);
  }

  // ---------------------------------------------------------------- shapes
  const burstPts = (n, R, r) => Array.from({ length: n * 2 }, (_, i) => {
    const a = (Math.PI * i) / n - Math.PI / 2, rr = i % 2 ? r : R;
    return `${(Math.cos(a) * rr).toFixed(1)},${(Math.sin(a) * rr).toFixed(1)}`;
  }).join(' ');
  const zig = (w = 200) => `M0 0 q${w / 12} -30 ${w / 6} 0 t${w / 6} 0 t${w / 6} 0 t${w / 6} 0 t${w / 6} 0 t${w / 6} 0`;
  const arcPath = (cx, cy, r, a0, a1) => { // degrees, clockwise in screen space
    const p = (a) => [cx + r * Math.cos((a * Math.PI) / 180), cy + r * Math.sin((a * Math.PI) / 180)];
    const [x0, y0] = p(a0), [x1, y1] = p(a1);
    return `M${x0.toFixed(1)} ${y0.toFixed(1)} A${r} ${r} 0 ${a1 - a0 > 180 ? 1 : 0} 1 ${x1.toFixed(1)} ${y1.toFixed(1)}`;
  };
  const polar = (r, a) => [r * Math.cos((a * Math.PI) / 180), r * Math.sin((a * Math.PI) / 180)];
  const arrowHead = (x, y, angDeg, size, color) => {
    const r = (angDeg * Math.PI) / 180, s = size;
    const pts = [[s, 0], [-s * 0.6, -s * 0.75], [-s * 0.6, s * 0.75]].map(([px2, py]) => `${(x + px2 * Math.cos(r) - py * Math.sin(r)).toFixed(1)},${(y + px2 * Math.sin(r) + py * Math.cos(r)).toFixed(1)}`);
    return `<polygon points="${pts.join(' ')}" fill="${color}" stroke="${color}" stroke-width="4" stroke-linejoin="round"/>`;
  };
  const check = (sw, color, s = 1) => `<path d="M${-28 * s} ${2 * s} L${-8 * s} ${22 * s} L${30 * s} ${-20 * s}" fill="none" stroke="${color}" stroke-width="${sw}" stroke-linecap="round" stroke-linejoin="round"/>`;
  const slipShape = (w, h, fill = C.white) => {
    const x0 = -w / 2, y0 = -h / 2, n = 6, step = w / n;
    let zz = `M${x0} ${y0} H${-x0} V${h / 2}`;
    for (let i = 0; i < n; i++) zz += ` l${-step / 2} -14 l${-step / 2} 14`;
    return `<path d="${zz} Z" fill="${fill}" stroke="${C.ink}" stroke-width="8" stroke-linejoin="round"/>`;
  };

  // ================================================================ PERSISTENT LAYER
  // Violet ring, top-left (two 120° arcs, one full turn over the loop)
  const ringG = S('g', {}, $('ringL'));
  ringG.innerHTML = `<path d="${arcPath(0, 0, L.ring.r, 0, 120)}" fill="none" stroke="${C.violet}" stroke-width="${L.ring.sw}" stroke-linecap="round"/>
    <path d="${arcPath(0, 0, L.ring.r, 180, 300)}" fill="none" stroke="${C.violet}" stroke-width="${L.ring.sw}" stroke-linecap="round"/>`;
  gsap.set(ringG, { svgOrigin: '0 0' });
  tl.fromTo(ringG, { rotation: 0 }, { rotation: 360, duration: DUR, ease: 'none' }, 0);

  // Orange circle, top-right, heartbeat on headline landings
  const orangeC = S('circle', { cx: L.orange.cx, cy: L.orange.cy, r: L.orange.r, fill: C.orange }, $('orangeL'));
  gsap.set(orangeC, { svgOrigin: `${L.orange.cx} ${L.orange.cy}` });
  [0.65, 3.45, 6.45, 10.1, 13.1, 16.1, 19.05, 21.45, 24.8, 27.55, 30.6, 35.75, 38.75, 41.1].forEach((t) => {
    tl.to(orangeC, { scale: 1.035, duration: 0.2, ease: 'power2.out' }, t);
    tl.to(orangeC, { scale: 1, duration: 0.35, ease: 'power2.inOut' }, t + 0.2);
  });

  // Dot grid, bottom-right, wave on headline landings
  const dotsEl = $('dots'), dotCols = [];
  dotsEl.style.left = `${L.dots.left}px`; dotsEl.style.top = `${L.dots.top}px`;
  for (let c = 0; c < 5; c++) {
    dotCols.push([]);
    for (let r = 0; r < 3; r++) dotCols[c].push(div('', dotsEl, `position:absolute;left:${c * 34}px;top:${r * 34}px;width:12px;height:12px;border-radius:50%;background:${C.dot}`));
  }
  [0.65, 2.85, 5.85, 8.7, 12.45, 15.45, 18.45, 23.85, 26.95, 29.95, 32.95, 35.6, 38.15, 41.1].forEach((t) => {
    dotCols.forEach((col, c) => {
      tl.to(col, { scale: 1.6, duration: 0.2, ease: 'sine.inOut' }, t + c * 0.03);
      tl.to(col, { scale: 1, duration: 0.2, ease: 'sine.inOut' }, t + c * 0.03 + 0.2);
    });
  });

  // Progress ticks: 6 Free beats | 5 Pro beats
  const ticksEl = $('ticks');
  ticksEl.style.left = `${L.ticks.left}px`; ticksEl.style.top = `${L.ticks.top}px`;
  const TK = L.ticks;
  ticksEl.style.height = `${TK.dot}px`; ticksEl.style.gap = `${TK.gap}px`;
  const ticks = Array.from({ length: 11 }, (_, i) => {
    const e = document.createElement('i'); if (i === 6) e.className = 'gap';
    e.style.cssText = `width:${TK.dot}px;height:${TK.dot}px;border-radius:${TK.dot / 2}px;${i === 6 ? `margin-left:${TK.split}px;` : ''}`;
    ticksEl.appendChild(e); return e;
  });
  const tickOn = (i, t) => {
    tl.to(ticks[i], { width: TK.on, backgroundColor: C.violet, duration: 0.4, ease: 'power3.inOut' }, t);
    if (i > 0) tl.to(ticks[i - 1], { width: TK.dot, duration: 0.4, ease: 'power3.inOut' }, t);
  };

  // Tier chips (FREE flips to PRO at the divider)
  const tierCss = `left:${L.tx}px;top:${L.tierTop}px;`;
  const tierFree = div('tier', textL, `${tierCss}background:${C.white};border:4px solid ${C.ink};color:${C.ink}`); tierFree.textContent = 'FREE';
  const tierPro = div('tier', textL, `${tierCss}background:${C.white};color:${C.violet}`); tierPro.textContent = 'PRO';
  gsap.set([tierFree, tierPro], { scale: 0 });

  // ================================================================ HOOK 0.00–2.10
  const HK = L.hook, hk = HK.size / 168;
  const hookBuilt = text({ lines: ['Built to'], size: HK.size, base: HK.builtBase, align: 'center', maxW: 0 });
  const kc = text({ lines: ['[keep customers].'], size: HK.size, base: HK.kcBase, align: 'center', maxW: 0, hidden: false });
  const kcLine = kc.lines[0].el, kcMark = kc.marks[0].el;
  gsap.set(kcLine, { transformOrigin: '0 0' });
  const hookB = markRect(kc);
  gsap.set(baton, px(hookB));

  const burstHookC = { x: hookB.left + hookB.width - 30 * hk, y: hookB.top + hookB.height / 2 - 80 * hk };
  const hookBurst = G(burstHookC.x, burstHookC.y, `<polygon points="${burstPts(12, 110 * hk, 78 * hk)}" fill="${C.orange}"/>`, fxL);
  const squig = G(L.squig.x, L.squig.y, `<path d="${zig(L.squig.w)}" fill="none" stroke="${C.ink}" stroke-width="14" stroke-linecap="round" stroke-linejoin="round"/>`, fxL);
  const squigPath = squig.querySelector('path');
  gsap.set(squig, { svgOrigin: `${L.squig.w / 2} 0` });

  // the pill drops first, then "Built to" falls in from above and lands on it
  tl.to(kcLine, { y: HK.shift, duration: 0.38, ease: 'power3.inOut' }, 0.08);
  tl.to(baton, { top: hookB.top + HK.shift, duration: 0.38, ease: 'power3.inOut' }, 0.08);
  tl.to(hookBurst, { y: HK.shift, duration: 0.38, ease: 'power3.inOut' }, 0.08);
  tl.fromTo(hookBuilt.words, { yPercent: -140 }, { yPercent: 0, duration: 0.42, ease: 'expo.out', stagger: 0.06 }, 0.26);
  tl.to(baton, { scaleY: 0.92, duration: 0.07, ease: 'power2.in' }, 0.46);
  tl.to(baton, { scaleY: 1, duration: 0.3, ease: 'back.out(3)' }, 0.53);
  tl.fromTo(squig, { rotation: 0 }, { rotation: 6, duration: 0.6, ease: 'wig4' }, 0.35);
  tl.fromTo(hookBurst, { rotation: 0 }, { rotation: 30, duration: 0.45, ease: 'power3.out' }, 0.3);
  tl.to(hookBurst, { scale: 1.12, duration: 0.22, ease: 'power2.out' }, 0.3);
  tl.to(hookBurst, { scale: 1, duration: 0.23, ease: 'power2.inOut' }, 0.52);
  exitText(hookBuilt, 1.75);
  exitText(kc, 1.75);
  tl.to(squigPath, { drawSVG: '0%', duration: 0.3, ease: 'power2.in' }, 1.75);
  tl.to(hookBurst, { scale: 0, rotation: 70, duration: 0.3, ease: 'back.in(1.7)' }, 1.75);

  // ================================================================ BEAT 1 — box reward (FREE) 2.10–5.10
  const h1 = head(['Fill the box.', '[Gift] unlocked.']);
  const s1 = sub('Discounts and gifts as the box fills');
  const st1 = status('Rewarded');
  const d1 = disc(C.tint);
  BATON(1.8, markRect(h1));

  // box (centre 1440,720)
  const boxG = G(1440, 720, '', zMid);
  const b1burst = G(0, -270, `<polygon points="${burstPts(12, 140, 98)}" fill="${C.orange}"/>`, boxG);
  const backStrip = G(0, -130, `<rect x="-160" y="-50" width="320" height="50" fill="${C.p3}" stroke="${C.ink}" stroke-width="8"/>`, boxG);
  const itemsG = S('g', {}, boxG);
  const item = (x, html) => G(x, -40, html, itemsG);
  const jar = item(-100, `<rect x="-50" y="-140" width="100" height="140" rx="18" fill="${C.white}" stroke="${C.ink}" stroke-width="8"/><rect x="-58" y="-170" width="116" height="38" rx="10" fill="${C.violet}" stroke="${C.ink}" stroke-width="8"/>`);
  const bottle = item(0, `<rect x="-38" y="-130" width="76" height="130" rx="18" fill="${C.orange}" stroke="${C.ink}" stroke-width="8"/><rect x="-16" y="-160" width="32" height="34" fill="${C.white}" stroke="${C.ink}" stroke-width="8"/><rect x="-22" y="-196" width="44" height="40" rx="18" fill="${C.ink}"/>`);
  const tube = item(100, `<path d="M-42 -170 H42 L30 -20 H-30 Z" fill="${C.tint}" stroke="${C.ink}" stroke-width="8" stroke-linejoin="round"/><rect x="-26" y="-20" width="52" height="30" rx="6" fill="${C.violet}" stroke="${C.ink}" stroke-width="8"/>`);
  const items = [jar, bottle, tube];
  const gift = G(0, 0, `<rect x="-75" y="-65" width="150" height="130" rx="14" fill="${C.violet}" stroke="${C.ink}" stroke-width="8"/>
    <rect x="-16" y="-63" width="32" height="126" fill="${C.white}" stroke="${C.ink}" stroke-width="5"/><rect x="-73" y="-14" width="146" height="28" fill="${C.white}" stroke="${C.ink}" stroke-width="5"/>
    <rect x="-75" y="-65" width="150" height="130" rx="14" fill="none" stroke="${C.ink}" stroke-width="8"/>
    <path d="M0 -65 C -50 -120 -80 -70 0 -65 C 80 -70 50 -120 0 -65 Z" fill="${C.white}" stroke="${C.ink}" stroke-width="7" stroke-linejoin="round"/>`, itemsG);
  const front = S('g', {}, boxG);
  front.innerHTML = `<rect x="-180" y="-130" width="360" height="260" rx="24" fill="${C.white}" stroke="${C.ink}" stroke-width="8"/><rect x="-20" y="-126" width="40" height="252" fill="${C.violet}"/><rect x="-180" y="-130" width="360" height="260" rx="24" fill="none" stroke="${C.ink}" stroke-width="8"/>`;
  // flaps hinge on the rim corners and splay outward
  const flapL = G(-180, -130, `<rect x="-180" y="0" width="180" height="84" rx="6" fill="${C.o100}" stroke="${C.ink}" stroke-width="8"/>`, boxG);
  const flapR = G(180, -130, `<rect x="0" y="0" width="180" height="84" rx="6" fill="${C.o100}" stroke="${C.ink}" stroke-width="8"/>`, boxG);
  const lid = G(0, -130, `<rect x="-192" y="-34" width="384" height="44" rx="10" fill="${C.o100}" stroke="${C.ink}" stroke-width="8"/>`, boxG);
  const tag = G(176, -126, `<line x1="0" y1="0" x2="36" y2="36" stroke="${C.ink}" stroke-width="4"/>
    <rect x="-40" y="34" width="216" height="84" rx="18" fill="${C.orange}"/><circle cx="-20" cy="76" r="9" fill="${C.white}"/>
    <text x="80" y="91" text-anchor="middle" font-family="FS" font-weight="700" font-size="44" fill="${C.white}">15% off</text>`, boxG);
  gsap.set(flapL, { rotation: 50 }); gsap.set(flapR, { rotation: -50 });
  gsap.set(lid, { scaleY: 0 });
  gsap.set(boxG, { y: 260, scale: 0 });
  gsap.set(items, { y: -760, opacity: 0 }); // opacity, not visibility: a visible child would override the hidden box later
  if (PHONE) tag.parentNode.setAttribute('transform', 'translate(176 -126) scale(1.4)');
  gsap.set([gift, b1burst, tag], { scale: 0 });
  const trackG = G(1440, 886, '', zMid);
  const track = [-194, -60, 74].map((x) => S('rect', { x, y: -14, width: 120, height: 28, rx: 14, fill: C.p3 }, trackG));
  gsap.set(track, { scaleX: 0, transformOrigin: '0% 50%' });

  IRIS(d1, 2.1); tickOn(0, 2.1);
  pop(tierFree, 2.15);
  headIn(h1, 2.15);
  tl.fromTo(boxG, { y: 260, scale: 1 }, { y: 0, scale: 1, duration: 0.6, ease: 'back.out(1.4)' }, 2.25);
  tl.fromTo(track, { scaleX: 0 }, { scaleX: 1, duration: 0.4, ease: 'power3.out', stagger: 0.05 }, 2.45);
  subIn(s1, 2.6);
  items.forEach((it, i) => {
    const t = 2.65 + i * 0.13;
    tl.set(it, { opacity: 1 }, t);
    tl.fromTo(it, { y: -760 }, { y: 0, duration: 0.4, ease: 'power1.in' }, t);
    tl.fromTo(it, { scaleY: 0.85 }, { scaleY: 1, duration: 0.2, ease: 'back.out(3)' }, t + 0.4);
    tl.to(track[i], { fill: C.violet, duration: 0.25, ease: 'power3.out' }, t + 0.4);
  });
  rimClip(itemsG, 2.6, 3.32);
  tl.fromTo(b1burst, { scale: 0, rotation: 0 }, { scale: 1, rotation: 20, duration: 0.35, ease: 'back.out(1.8)' }, 3.31);
  tl.fromTo(gift, { y: -40, scale: 0.5 }, { y: -270, scale: 0.85, duration: 0.45, ease: 'back.out(2)' }, 3.31);
  tl.fromTo(tag, { scale: 0 }, { scale: 1, duration: 0.15, ease: 'power2.out' }, 3.35);
  tl.fromTo(tag, { rotation: 0 }, { rotation: -40, duration: 0.7, ease: 'wig3' }, 3.35);
  SPARK(3.35, 1560, 400);
  STATUS(st1, 3.3);
  exitText(h1, 4.65); subOut(s1, 4.65); STATUS_OUT(st1, 4.65);
  tl.to(gift, { y: -40, scale: 0.5, duration: 0.3, ease: 'power3.in' }, 4.65);
  tl.to(b1burst, { scale: 0, duration: 0.3, ease: 'back.in(1.7)' }, 4.65);
  tl.to(items, { y: 160, duration: 0.3, ease: 'power3.in' }, 4.65);
  tl.to([flapL, flapR], { scale: 0, duration: 0.22, ease: 'power2.in' }, 4.7);
  tl.to(backStrip, { scaleY: 0, duration: 0.3, ease: 'power2.inOut' }, 4.7);
  tl.fromTo(lid, { scaleY: 0 }, { scaleY: 1, duration: 0.3, ease: 'back.out(2)' }, 4.86);
  tl.to(track, { scaleX: 0, transformOrigin: '100% 50%', duration: 0.3, ease: 'power3.in', stagger: -0.04 }, 4.65);

  // ================================================================ BEAT 2 — renewal paid (FREE) 5.10–8.10
  const h2 = head(['Renewal paid.', 'Box [ready].']);
  const s2 = sub('A new order every renewal');
  const st2 = status('Paid');
  const d2 = disc(C.o100);
  BATON(4.65, markRect(h2));

  const [rhx, rhy] = polar(290, 255);
  const renewG = G(1440, 650, `<path d="${arcPath(0, 0, 290, -75, 255)}" fill="none" stroke="${C.violet}" stroke-width="18" stroke-linecap="round"/>${arrowHead(rhx, rhy, 255 + 90, 26, C.violet)}`, zBack);
  const renewArc = renewG.querySelector('path'), renewHead = renewG.querySelector('polygon');
  gsap.set(renewArc, { drawSVG: '0%' }); gsap.set(renewHead, { scale: 0, transformOrigin: '50% 50%' });
  // the slip draws above the ring but behind the box (zMid)
  const slipG = G(1380, 660, `${slipShape(200, 270)}
    <rect x="-64" y="-90" width="128" height="14" rx="7" fill="${C.p3}"/><rect x="-64" y="-56" width="128" height="14" rx="7" fill="${C.p3}"/>
    <rect x="-64" y="-22" width="128" height="14" rx="7" fill="${C.p3}"/><rect x="-64" y="12" width="96" height="14" rx="7" fill="${C.p3}"/>
    <rect x="-64" y="52" width="70" height="18" rx="9" fill="${C.violet}"/>`, zBack);
  const slipBars = [...slipG.querySelectorAll('rect')];
  gsap.set(slipG, { scale: 0 }); gsap.set(slipBars, { scaleX: 0, transformOrigin: '0% 50%' });
  const seal2 = G(1668, 772, `<circle r="55" fill="${C.violet}" stroke="${C.ink}" stroke-width="6"/>${check(10, C.white, 1)}`, zTop);
  gsap.set(seal2, { scale: 0 });

  IRIS(d2, 5.1, 1440, 720); tickOn(1, 5.1);
  tl.to(boxG, { x: -60, y: -60, scale: 0.9, duration: 0.4, ease: 'power3.inOut' }, 5.1);
  headIn(h2, 5.15);
  tl.to(renewArc, { drawSVG: '100%', duration: 0.6, ease: 'power2.inOut' }, 5.2);
  tl.to(renewHead, { scale: 1, duration: 0.2, ease: 'back.out(2)' }, 5.72);
  tl.fromTo(renewG, { rotation: 0 }, { rotation: 360, duration: 0.5, ease: 'power3.inOut' }, 5.8);
  subIn(s2, 5.6);
  tl.to(tag, { scale: 0, duration: 0.2, ease: 'back.in(1.7)' }, 5.85);
  tl.fromTo(slipG, { x: 0, scale: 0.9, rotation: 0 }, { x: 240, y: 30, scale: 1, rotation: 6, duration: 0.45, ease: 'expo.out' }, 5.95);
  tl.to(slipBars, { scaleX: 1, duration: 0.25, ease: 'power3.out', stagger: 0.06 }, 6.1);
  tl.fromTo(seal2, { scale: 1.6 }, { scale: 1, duration: 0.3, ease: 'back.out(2.2)' }, 6.3);
  SPARK(6.35, 1750, 760);
  STATUS(st2, 6.3);
  clipDisc(7.6);
  exitText(h2, 7.65); subOut(s2, 7.65); STATUS_OUT(st2, 7.65);
  tl.to([boxG, slipG, renewG, seal2], { x: '-=760', duration: 0.45, ease: 'power3.in' }, 7.65);
  tl.set([boxG, slipG, renewG, seal2, trackG], { visibility: 'hidden' }, 8.1);

  // ================================================================ BEAT 3 — declined, then paid (FREE) 8.10–11.70
  const h3 = head(['Card [declined].', 'Then [paid].']);
  const s3 = sub('Email sent right away');
  const st3 = status('Recovered');
  const d3 = disc(C.p2);
  BATON(7.65, markRect(h3, 0));
  batonSwap(8.03, C.orange);

  const cardG = G(1440, 600, `<rect x="-220" y="-140" width="440" height="280" rx="28" fill="${C.violet}" stroke="${C.ink}" stroke-width="8"/>
    <rect x="-186" y="-58" width="72" height="56" rx="10" fill="${C.o100}" stroke="${C.ink}" stroke-width="5"/>
    ${[0, 1, 2, 3].map((g) => [0, 1, 2, 3].map((d) => `<circle cx="${-176 + g * 96 + d * 18}" cy="90" r="5" fill="${C.white}"/>`).join('')).join('')}
    ${icon('nfc', 128, -96, 60, C.white, 2.2)}`, zMid);
  gsap.set(cardG, { x: 700, rotation: 10, visibility: 'hidden' });
  const xPath = 'M-20 -20 L20 20 M20 -20 L-20 20', okPath = 'M-24 2 L-7 19 L24 -18';
  const badge = G(1660, 460, `<circle r="50" fill="${C.orange}" stroke="${C.ink}" stroke-width="6"/><path d="${xPath}" fill="none" stroke="${C.white}" stroke-width="10" stroke-linecap="round" stroke-linejoin="round"/>`, zTop);
  const badgeC = badge.querySelector('circle'), badgeMark = badge.querySelector('path');
  gsap.set(badge, { scale: 0 });
  // closed envelope with a fold line; a violet check badge pops on its corner once it is sent
  const envG = G(0, 0, `<rect x="-100" y="-70" width="200" height="140" rx="16" fill="${C.white}" stroke="${C.ink}" stroke-width="8"/>
    <path d="M-92 -62 L0 8 L92 -62" fill="none" stroke="${C.ink}" stroke-width="5" stroke-linejoin="round" stroke-linecap="round"/>
    <g class="env-ok"><circle cx="88" cy="58" r="28" fill="${C.violet}" stroke="${C.ink}" stroke-width="5"/><path d="M76 58 L85 67 L101 49" fill="none" stroke="${C.white}" stroke-width="6" stroke-linecap="round" stroke-linejoin="round"/></g>`, zTop);
  const envOk = envG.querySelector('.env-ok');
  gsap.set(envG, { x: 1440, y: 600, scale: 0 }); gsap.set(envOk, { scale: 0, svgOrigin: '88 58' });
  const [qx, qy] = polar(280, 180);
  const retryG = G(1440, 600, `<path d="${arcPath(0, 0, 280, -90, 180)}" fill="none" stroke="${C.violet}" stroke-width="16" stroke-linecap="round"/>${arrowHead(qx, qy, 270, 24, C.violet)}`, zBack);
  const retryArc = retryG.querySelector('path'), retryHead = retryG.querySelector('polygon');
  gsap.set(retryArc, { drawSVG: '0%' }); gsap.set(retryHead, { scale: 0, transformOrigin: '50% 50%' });
  const retryChip = chip(PHONE ? 'Retry 1 of 3' : 'Stripe · retry 1 of 3', 1440, PHONE ? 850 : 930, { ring: C.ink });

  IRIS(d3, 8.1); tickOn(2, 8.1);
  tl.set(cardG, { visibility: 'visible' }, 8.1);
  tl.fromTo(cardG, { x: 700, rotation: 10 }, { x: 0, rotation: 0, duration: 0.55, ease: 'expo.out' }, 8.1);
  clipOff(8.7);
  lineIn(h3, 0, 8.15, 0.55);
  tl.fromTo(cardG, { x: 0 }, { x: 22, duration: 0.45, ease: 'wig5' }, 8.7);
  tl.fromTo(badge, { scale: 0 }, { scale: 1, duration: 0.3, ease: 'back.out(2.2)' }, 8.75);
  subIn(s3, 8.8);
  tl.set(envG, { scale: 0.4 }, 9.0);
  tl.to(envG, { motionPath: { path: [{ x: 1440, y: 600 }, { x: 1320, y: 300 }, { x: 1250, y: 420 }], curviness: 1.4 }, scale: 1, duration: 0.4, ease: 'power2.out' }, 9.0);
  tl.to(envOk, { scale: 1, duration: 0.3, ease: 'back.out(2.4)' }, 9.3);
  tl.to(retryArc, { drawSVG: '100%', duration: 0.4, ease: 'power2.inOut' }, 9.3);
  tl.to(retryHead, { scale: 1, duration: 0.18, ease: 'back.out(2)' }, 9.62);
  pop(retryChip, 9.4);
  // baton hands over to "paid" before line 2 rises into it
  BATON(9.2, markRect(h3, 1), 0.45);
  batonSwap(9.53, C.violet);
  tl.set(h3.marks[0].el, { color: C.ink }, 9.2);
  unmark(h3.marks[0].el, 9.2);
  lineIn(h3, 1, 9.55, 0.5);
  tl.to(badgeMark, { morphSVG: okPath, duration: 0.3, ease: 'power3.inOut' }, 9.8);
  tl.set(badgeC, { fill: C.violet }, 9.95);
  SPARK(9.9, 1690, 420);
  STATUS(st3, 9.95);
  exitText(h3, 11.25); subOut(s3, 11.25); STATUS_OUT(st3, 11.25);
  tl.to([cardG, envG, retryG], { scale: 0, duration: 0.25, ease: 'power3.in' }, 11.25);
  unpop(retryChip, 11.25, 0.3, 'power3.in');
  // match cut: the badge grows into the next beat's disc and settles on it before the 11.70 handoff
  tl.to(badge, { x: 1440 - 1660, y: 620 - 460, scale: 6.8, duration: 0.45, ease: 'expo.inOut' }, 11.25);
  tl.to(badgeC, { strokeWidth: 0, duration: 0.2, ease: 'power2.out' }, 11.25);
  tl.to(badgeC, { fill: C.tint, duration: 0.45, ease: 'expo.inOut' }, 11.25);
  tl.to(badgeMark, { scale: 0, transformOrigin: '50% 50%', duration: 0.2, ease: 'power2.in' }, 11.25);

  // ================================================================ BEAT 4 — members area (FREE) 11.70–14.70
  const h4 = head(['Members area,', '[unlocked].']);
  const s4 = sub('No separate membership plugin');
  const st4 = status('Granted');
  const d4 = disc(C.tint);
  BATON(11.25, markRect(h4));
  tl.set(d4, { scale: 1 }, 11.7);
  tl.set(badge, { visibility: 'hidden' }, 11.7);
  tl.set(curDisc, { visibility: 'hidden' }, 11.7);
  curDisc = d4;

  const arch = (inset) => { const w = 170 - inset, top = -460 + inset; return `M${-w} ${-inset / 2} V${top + w} A${w} ${w} 0 0 1 ${w} ${top + w} V${-inset / 2} Z`; };
  const openLeaf = 'M-146 -12 V-300 L-206 -324 V-22 Z';
  const doorG = G(1440, 900, `<path d="${arch(0)}" fill="${C.white}" stroke="${C.ink}" stroke-width="8" stroke-linejoin="round"/>
    <path d="${arch(24)}" fill="${C.violet}" stroke="${C.ink}" stroke-width="6"/>
    <g class="glow">${[-50, 0, 50].map((dx, i) => `<line x1="${dx}" y1="${-250 - (i === 1 ? 20 : 0)}" x2="${dx * 1.6}" y2="${-310 - (i === 1 ? 40 : 0)}" stroke="${C.white}" stroke-width="10" stroke-linecap="round"/>`).join('')}</g>
    <path class="leaf" d="${arch(24)}" fill="${C.o100}" stroke="${C.ink}" stroke-width="6" stroke-linejoin="round"/><circle class="knob" cx="110" cy="-200" r="12" fill="${C.ink}"/>`, zBack);
  const leaf = doorG.querySelector('.leaf'), knob = doorG.querySelector('.knob'), glow = doorG.querySelector('.glow');
  gsap.set(doorG, { scaleY: 0 }); gsap.set(knob, { transformOrigin: '50% 50%' }); gsap.set(glow, { scale: 0, svgOrigin: '0 -280' });
  const lockG = G(1440, 720, `<g class="shackle"><path d="M-62 -88 V-140 A62 62 0 0 1 62 -140 V-88" fill="none" stroke="${C.ink}" stroke-width="32" stroke-linecap="round"/></g>
    <rect x="-110" y="-90" width="220" height="180" rx="36" fill="${C.violet}" stroke="${C.ink}" stroke-width="8"/>
    <circle cx="0" cy="-2" r="20" fill="${C.white}"/><rect x="-9" y="6" width="18" height="40" rx="9" fill="${C.white}"/>`, zMid);
  const shackle = lockG.querySelector('.shackle');
  gsap.set(lockG, { y: -320, scale: 0 }); gsap.set(shackle, { svgOrigin: '-62 -88' });
  const keyG = G(0, 0, `<rect x="0" y="-10" width="120" height="20" rx="6" fill="${C.orange}" stroke="${C.ink}" stroke-width="6"/>
    <rect x="18" y="6" width="16" height="22" fill="${C.orange}" stroke="${C.ink}" stroke-width="6"/><rect x="48" y="6" width="16" height="16" fill="${C.orange}" stroke="${C.ink}" stroke-width="6"/>
    <circle cx="170" cy="0" r="50" fill="${C.orange}" stroke="${C.ink}" stroke-width="6"/><circle cx="170" cy="0" r="18" fill="${C.white}" stroke="${C.ink}" stroke-width="6"/>`, zTop);
  gsap.set(keyG, { x: 1800, y: 980, rotation: 45, scale: 0 });
  const memChip = chip('/members/', 1440, 390, { bg: C.ink, fg: C.white });

  tickOn(3, 11.7);
  headIn(h4, 11.75);
  tl.fromTo(doorG, { scaleY: 0 }, { scaleY: 1, duration: 0.5, ease: 'expo.out' }, 11.75);
  tl.fromTo(lockG, { y: -320, scale: 1 }, { y: 0, scale: 1, duration: 0.5, ease: 'back.out(1.6)' }, 11.95);
  rimClip(lockG.parentNode, 11.94, 12.45);
  subIn(s4, 12.2);
  tl.set(keyG, { scale: 1 }, 12.25);
  tl.to(keyG, { motionPath: { path: [{ x: 1800, y: 980 }, { x: 1640, y: 900 }, { x: 1440, y: 735 }], curviness: 1.2 }, rotation: 0, duration: 0.45, ease: 'power3.out' }, 12.25);
  tl.to(keyG, { rotation: 90, duration: 0.2, ease: 'back.out(1.6)' }, 12.7);
  tl.to(shackle, { y: -50, rotation: -12, duration: 0.3, ease: 'back.out(2.4)' }, 12.85);
  SPARK(12.9, 1530, 560);
  clipDisc(12.95);
  tl.to(lockG, { y: 520, duration: 0.4, ease: 'power3.in' }, 12.95);
  tl.to(keyG, { y: '+=520', duration: 0.4, ease: 'power3.in' }, 12.95);
  tl.set([lockG, keyG], { visibility: 'hidden' }, 13.35);
  clipOff(13.36);
  tl.to(leaf, { morphSVG: openLeaf, duration: 0.4, ease: 'power3.inOut' }, 12.95);
  tl.to(knob, { scale: 0, duration: 0.15, ease: 'power2.in' }, 12.95);
  tl.fromTo(glow, { scale: 0 }, { scale: 1, duration: 0.3, ease: 'back.out(2)' }, 13.2);
  STATUS(st4, 12.95);
  pop(memChip, 13.2);
  exitText(h4, 14.25); subOut(s4, 14.25); STATUS_OUT(st4, 14.25);
  tl.to(doorG, { scaleY: 0, duration: 0.4, ease: 'power3.in' }, 14.25);
  unpop(memChip, 14.25);

  // ================================================================ BEAT 5 — drip (FREE) 14.70–17.70
  const h5 = head(['Lesson 2 opens', 'on [day 7].']);
  const s5 = sub('Ebooks drip into Downloads too');
  const st5 = status('Dripped');
  const d5 = disc(C.o100);
  BATON(14.25, markRect(h5));

  const trackLine = S('path', { d: 'M1220 780 L1660 780', stroke: C.ink, 'stroke-width': 12, 'stroke-linecap': 'round', fill: 'none' }, zBack);
  gsap.set(trackLine, { drawSVG: '0%' });
  const fill5 = S('path', { d: 'M1220 780 L1440 780', stroke: C.violet, 'stroke-width': 20, 'stroke-linecap': 'round', fill: 'none' }, zMid);
  gsap.set(fill5, { drawSVG: '0%' });
  const openBook = `<g transform="scale(0.78 1)"><path d="M0 -70 C -40 -86 -80 -86 -110 -74 V70 C -80 58 -40 58 0 74 C 40 58 80 58 110 70 V-74 C 80 -86 40 -86 0 -70 Z" fill="${C.violet}" stroke="${C.ink}" stroke-width="8" stroke-linejoin="round"/>
    <path d="M0 -60 C -34 -74 -70 -74 -96 -64 V58 C -70 48 -34 48 0 62 C 34 48 70 48 96 58 V-64 C 70 -74 34 -74 0 -60 Z" fill="${C.white}" stroke="${C.ink}" stroke-width="6" stroke-linejoin="round"/>
    <line x1="0" y1="-60" x2="0" y2="62" stroke="${C.ink}" stroke-width="6"/></g>`;
  const closedBook = `<rect x="-60" y="-80" width="120" height="160" rx="12" fill="${C.violet}" stroke="${C.ink}" stroke-width="8"/><rect x="-60" y="-80" width="22" height="160" rx="8" fill="${C.vs}" stroke="${C.ink}" stroke-width="8"/>`;
  const lockBadge = `<circle cx="0" cy="0" r="28" fill="${C.white}" stroke="${C.ink}" stroke-width="5"/>${icon('lock', -15, -16, 30, C.ink, 2.6)}`;
  const book1 = G(1220, 650, openBook, zMid);
  const book2 = G(1440, 650, `<g class="cl">${closedBook}</g><g class="op">${openBook}</g>`, zMid);
  const book3 = G(1660, 650, closedBook, zMid);
  const lb2 = G(1440 + 46, 650 - 66, lockBadge, zTop), lb3 = G(1660 + 46, 650 - 66, lockBadge, zTop);
  const b2closed = book2.querySelector('.cl'), b2open = book2.querySelector('.op');
  gsap.set(b2open, { scaleX: 0, svgOrigin: '0 0' }); gsap.set(b2closed, { svgOrigin: '0 0' });
  gsap.set([book1, book2, book3, lb2, lb3], { scale: 0 });
  const clockDot = G(1220, 780, `<circle r="18" fill="${C.ink}"/><circle class="ring" r="18" fill="none" stroke="${C.white}" stroke-width="0"/>`, zTop);
  const clockC = clockDot.querySelector('circle'), clockRing = clockDot.querySelector('.ring');
  gsap.set(clockDot, { scale: 0 });
  const nSign = chip('Signup', 1220, 850, { ring: C.ink, size: 40, h: 76, pad: 26 });
  const nDay7 = chip('Day 7', 1440, 850, { bg: C.tint, fg: C.vs, size: 40, h: 76, pad: 26 });
  const nDay14 = chip('Day 14', 1660, 850, { ring: C.ink, size: 40, h: 76, pad: 26 });
  if (PHONE) [nSign, nDay14].forEach((c) => { c.style.display = 'none'; });
  const ebook = G(0, 0, `<path d="M-48 -62 H22 L48 -36 V62 H-48 Z" fill="${C.white}" stroke="${C.ink}" stroke-width="7" stroke-linejoin="round"/><path d="M22 -62 V-36 H48" fill="none" stroke="${C.ink}" stroke-width="7" stroke-linejoin="round"/>${icon('download', -26, -14, 52, C.orange, 3)}`, zTop);
  gsap.set(ebook, { x: 1440, y: 650, scale: 0 });

  IRIS(d5, 14.7); tickOn(4, 14.7);
  headIn(h5, 14.75);
  tl.to(trackLine, { drawSVG: '100%', duration: 0.4, ease: 'power2.out' }, 14.8);
  tl.fromTo([book1, book2, book3], { scale: 0 }, { scale: 1, duration: 0.35, ease: 'back.out(1.8)', stagger: 0.08 }, 14.9);
  tl.fromTo([lb2, lb3], { scale: 0 }, { scale: 1, duration: 0.3, ease: 'back.out(2)', stagger: 0.08 }, 15.05);
  [nSign, nDay7, nDay14].forEach((c, i) => pop(c, 15.0 + i * 0.08));
  tl.fromTo(clockDot, { scale: 0 }, { scale: 1, duration: 0.25, ease: 'back.out(2)' }, 15.2);
  subIn(s5, 15.25);
  tl.to(fill5, { drawSVG: '100%', duration: 0.5, ease: 'power2.inOut' }, 15.4);
  tl.to(clockDot, { x: 220, duration: 0.5, ease: 'power2.inOut' }, 15.4);
  tl.to(lb2, { y: -70, rotation: 25, duration: 0.1, ease: 'power2.out' }, 15.9);
  clipDisc(15.99);
  tl.to(lb2, { y: 500, duration: 0.3, ease: 'power2.in' }, 16.0);
  tl.set(lb2, { visibility: 'hidden' }, 16.3);
  clipOff(16.32);
  tl.to(b2closed, { scaleX: 0, duration: 0.15, ease: 'power2.in' }, 15.9);
  tl.to(b2open, { scaleX: 1, duration: 0.18, ease: 'back.out(1.6)' }, 16.05);
  tl.set(nDay7, { backgroundColor: C.violet, color: C.white }, 15.9);
  tl.to(nDay7, { scale: 1.1, duration: 0.12, ease: 'power2.out' }, 15.9);
  tl.to(nDay7, { scale: 1, duration: 0.2, ease: 'back.out(2)' }, 16.02);
  STATUS(st5, 15.95);
  tl.to(ebook, { motionPath: { path: [{ x: 1440, y: 650 }, { x: 1470, y: 420 }, { x: 1440, y: 468 }], curviness: 1.3 }, duration: 0.35, ease: 'power2.out' }, 16.05);
  tl.fromTo(ebook, { scale: 0 }, { scale: 1, duration: 0.2, ease: 'back.out(2)' }, 16.05);
  tl.fromTo(ebook, { scaleY: 0.82 }, { scaleY: 1, duration: 0.2, ease: 'back.out(3)' }, 16.4);
  SPARK(16.1, 1510, 420);
  exitText(h5, 17.25); subOut(s5, 17.25); STATUS_OUT(st5, 17.25);
  tl.to([book1, book2, book3, ebook, lb3], { scale: 0, duration: 0.3, ease: 'power3.in', stagger: 0.03 }, 17.25);
  [nSign, nDay7, nDay14].forEach((c, i) => unpop(c, 17.25 + i * 0.03, 0.3, 'power3.in'));
  tl.to(trackLine, { drawSVG: '0%', duration: 0.3, ease: 'power2.in' }, 17.25);
  // match cut: the drip fill becomes the MRR sparkline
  const spark12 = 'M1180 815 L1228 800 L1276 806 L1324 784 L1372 790 L1420 770 L1468 774 L1516 748 L1564 752 L1612 722 L1660 712 L1710 680';
  tl.set(fill5, { strokeDasharray: 'none' }, 17.25);
  tl.to(fill5, { morphSVG: spark12, stroke: C.tint, strokeWidth: 16, duration: 0.55, ease: 'power3.inOut' }, 17.25);
  tl.to(clockDot, { x: 1710 - 1220, y: 680 - 780, duration: 0.55, ease: 'power3.inOut' }, 17.25);
  tl.to(clockC, { fill: C.orange, duration: 0.3, ease: 'none' }, 17.4);
  tl.to(clockRing, { attr: { 'stroke-width': 6, r: 21 }, duration: 0.3, ease: 'none' }, 17.4);

  // ================================================================ BEAT 6 — Home dashboard (FREE) 17.70–20.70
  const h6 = head(['Your business,', 'in [one glance].']);
  const s6 = sub('MRR, renewals and what needs you');
  const st6 = status('Tracked');
  const d6 = disc(C.ink);
  BATON(17.25, markRect(h6));

  const mrrChip = chip('Current MRR', 1440, 410, { bg: C.violet, fg: C.white });
  const mrrNum = text({ lines: ['$10,428'], size: 140, base: 610, align: 'center', cx: 1440, color: C.white, maxW: 0, tracking: '-0.03em', parent: chipL });
  const mrrWord = mrrNum.words[0];
  const liveChip = chip('', 1282, 920, { ring: C.ink, html: `<span style="width:18px;height:18px;border-radius:50%;background:${C.violet};display:inline-block"></span>99 live` });
  const dueChip = chip('', PHONE ? 1440 : 1567, PHONE ? 850 : 920, { bg: C.o100, fg: C.otext, ring: C.ink, html: `${window.ICONS['calendar-days']}$284 due` });
  if (PHONE) liveChip.style.display = 'none';
  const mrrProxy = { v: 0 };

  IRIS(d6, 17.7); tickOn(5, 17.7);
  headIn(h6, 17.75);
  tl.to(clockDot, { scale: 1.35, duration: 0.15, ease: 'power2.out' }, 17.85);
  tl.to(clockDot, { scale: 1, duration: 0.2, ease: 'back.out(3)' }, 18.0);
  pop(mrrChip, 17.9, 0.3);
  IN(mrrNum.words, 17.95, 0.4, 0);
  tl.fromTo(mrrProxy, { v: 0 }, { v: 10428, duration: 0.9, ease: 'power3.out', onUpdate: () => { mrrWord.textContent = `$${Math.round(mrrProxy.v).toLocaleString('en-US')}`; } }, 17.95);
  subIn(s6, 18.3);
  pop(liveChip, 18.55); pop(dueChip, 18.67);
  STATUS(st6, 18.9);
  SPARK(18.95, 1730, 660);
  tl.to(liveChip, { y: -6, duration: 0.45, ease: 'sine.inOut' }, 19.25); tl.to(liveChip, { y: 0, duration: 0.45, ease: 'sine.inOut' }, 19.7);
  tl.to(dueChip, { y: 6, duration: 0.45, ease: 'sine.inOut' }, 19.25); tl.to(dueChip, { y: 0, duration: 0.45, ease: 'sine.inOut' }, 19.7);
  // text leaves first, then the disc floods the frame
  exitText(h6, 20.15); subOut(s6, 20.15); STATUS_OUT(st6, 20.15);
  unpop(liveChip, 20.25, 0.25); unpop(dueChip, 20.25, 0.25); unpop(mrrChip, 20.25, 0.25);
  tl.to(mrrNum.words, { yPercent: 140, duration: 0.3, ease: 'power3.in' }, 20.25);
  tl.to(fill5, { drawSVG: '0%', duration: 0.3, ease: 'power2.in' }, 20.25);
  tl.to(clockDot, { scale: 0, duration: 0.25, ease: 'back.in(1.7)' }, 20.25);
  tl.to(d6, { fill: C.violet, duration: 0.15, ease: 'none' }, 20.4);
  tl.to(d6, { scale: L.flood, duration: 0.5, ease: 'expo.inOut', svgOrigin: '1440 620', smoothOrigin: false }, 20.45);

  // ================================================================ DIVIDER 20.70–23.10
  const dv = text({ lines: ['Start [free].', 'Add [Pro] when you grow.'], size: L.dv.size, base: L.dv.base, pitch: L.dv.pitch, align: 'center', color: C.white, mark: C.violet, maxW: L.dv.max });
  const dvFree = dv.marks[0].el, dvPro = dv.marks[1].el;
  dvPro.style.color = C.white;
  gsap.set(dvPro, { marginLeft: '0em', marginRight: '0em' }); // the gap opens when the baton arrives
  const dvSquig = G(L.dvSquig.x, L.dvSquig.y, `<path d="${zig(L.dvSquig.w)}" fill="none" stroke="${C.ink}" stroke-width="14" stroke-linecap="round" stroke-linejoin="round"/>`, fxL);
  const dvSquigPath = dvSquig.querySelector('path');
  gsap.set(dvSquigPath, { drawSVG: '0%' });
  BATON(20.35, brect(dv.marks[0].r, dv.size), 0.45);
  batonColor(20.62, C.white, 0.2);
  tl.to(ticks.slice(0, 6), { backgroundColor: C.white, duration: 0.25, ease: 'none' }, 20.72);
  tl.to(ticks.slice(6), { backgroundColor: C.pend, duration: 0.25, ease: 'none' }, 20.72);

  lineIn(dv, 0, 20.95, 0.5); lineIn(dv, 1, 21.05, 0.5);
  tl.to(dvSquigPath, { drawSVG: '100%', duration: 0.45, ease: 'power2.out' }, 21.2);
  BATON(21.5, brect(dv.marks[1].r, dv.size), 0.45);
  unmark(dvFree, 21.5);
  tl.to(dvPro, { marginLeft: '0.22em', marginRight: '0.22em', duration: 0.45, ease: 'power3.inOut' }, 21.5);
  tl.set([dvFree, dvPro], { color: C.ink }, 21.5); // ink during the handover: reads on the white baton and on the flood
  tl.set(dvFree, { color: C.white }, 21.95);
  tl.set(dvPro, { color: C.violet }, 21.95);
  tl.to(tierFree, { scaleX: 0, duration: 0.15, ease: 'power2.in' }, 21.65);
  tl.set(tierPro, { scale: 1, scaleX: 0 }, 21.8);
  tl.to(tierPro, { scaleX: 1, duration: 0.15, ease: 'power2.out' }, 21.8);
  // exit: words clear while the flood still covers the frame, then a grey disc irises over the shrinking flood
  tl.to(dv.words, { yPercent: -140, duration: 0.3, ease: 'power3.in', stagger: 0.015 }, 22.75);
  tl.to(dvSquigPath, { drawSVG: '0%', duration: 0.3, ease: 'power2.in' }, 22.75);
  const d7 = disc(C.p2);
  // the flood shrinks past the grey disc's radius so the two edges cross, then hides
  tl.to(d6, { scale: 0.85, duration: 0.5, ease: 'expo.inOut', svgOrigin: '1440 620', smoothOrigin: false }, 23.05);
  tl.set(d6, { visibility: 'hidden' }, 23.6);
  IRIS(d7, 23.15);
  tl.to(tierPro, { scaleX: 0, duration: 0.12, ease: 'power2.in' }, 23.05);
  tl.set(tierPro, { backgroundColor: C.violet, color: C.white }, 23.17);
  tl.to(tierPro, { scaleX: 1, duration: 0.12, ease: 'power2.out' }, 23.17);
  tl.set(ticks.slice(0, 6), { backgroundColor: C.violet }, 23.17);
  tl.set(ticks.slice(6), { backgroundColor: C.line }, 23.17);

  // ================================================================ BEAT 7 — save offer (PRO) 23.10–26.20
  const h7 = head(['[Cancel]?', 'They [stayed].']);
  const s7 = sub('Offers match the cancel reason');
  const st7 = status('Saved');
  BATON(23.15, markRect(h7, 0), 0.3);
  batonSwap(23.25, C.orange);

  const zz = 'L-20 110 L16 62 L-14 14 L18 -28 L0 -60';
  const halfL = G(1440, 660, `<path d="M0 -60 C -120 -160 -230 0 0 150 ${zz} Z" fill="${C.violet}" stroke="${C.ink}" stroke-width="8" stroke-linejoin="round"/>`, zMid);
  const halfR = G(1440, 660, `<path d="M0 -60 C 120 -160 230 0 0 150 ${zz} Z" fill="${C.violet}" stroke="${C.ink}" stroke-width="8" stroke-linejoin="round"/>`, zMid);
  const heart = G(1440, 660, `<path d="M0 -60 C -120 -160 -230 0 0 150 C 230 0 120 -160 0 -60 Z" fill="${C.violet}" stroke="${C.ink}" stroke-width="8" stroke-linejoin="round"/>`, zMid);
  const heartP = heart.querySelector('path');
  const burst7 = G(1440, 650, `<polygon points="${burstPts(12, 260, 190)}" fill="${C.o100}"/>`, zBack);
  const offer = G(1440, 660, `<rect x="-150" y="-95" width="300" height="190" rx="26" fill="${C.orange}" stroke="${C.ink}" stroke-width="6"/>
    <text x="0" y="4" text-anchor="middle" font-family="FD" font-weight="700" font-size="74" letter-spacing="-2" fill="${C.white}">20% off</text>
    <text x="0" y="62" text-anchor="middle" font-family="FS" font-weight="700" font-size="${PHONE ? 54 : 46}" fill="${C.white}">3 renewals</text>`, zTop);
  gsap.set([halfL, halfR, heart, burst7], { scale: 0 }); gsap.set(offer, { scaleX: 0 });
  const reason = chip('Too expensive', 1440, 390, { ring: C.ink });

  tickOn(6, 23.3);
  lineIn(h7, 0, 23.32, 0.5);
  tl.set(reason, { scale: 1 }, 23.38);
  tl.fromTo(reason, { y: PHONE ? -60 : -200 }, { y: 0, duration: 0.4, ease: 'back.out(1.6)' }, 23.38);
  subIn(s7, 23.7);
  tl.set([halfL, halfR], { scale: 1 }, 23.7);
  tl.fromTo(halfL, { x: -260, rotation: -10 }, { x: -60, rotation: -10, duration: 0.45, ease: 'expo.out' }, 23.7);
  tl.fromTo(halfR, { x: 260, rotation: 10 }, { x: 60, rotation: 10, duration: 0.45, ease: 'expo.out' }, 23.7);
  tl.fromTo(offer, { scaleX: 0 }, { scaleX: 1, duration: 0.4, ease: 'power3.out' }, 23.95);
  // baton hands over to "stayed" before line 2 rises into it
  BATON(23.95, markRect(h7, 1), 0.45);
  batonSwap(24.27, C.violet);
  tl.set(h7.marks[0].el, { color: C.ink }, 23.95);
  unmark(h7.marks[0].el, 23.95);
  lineIn(h7, 1, 24.3, 0.5);
  tl.to([halfL, halfR], { x: 0, rotation: 0, duration: 0.4, ease: 'back.out(1.8)' }, 24.25);
  tl.set(heart, { scale: 1 }, 24.62); tl.set([halfL, halfR], { visibility: 'hidden' }, 24.62);
  tl.to(offer, { x: 140, y: PHONE ? 100 : 130, rotation: 12, scale: PHONE ? 1 : 0.8, duration: 0.4, ease: 'power3.inOut' }, 24.25);
  tl.fromTo(burst7, { scale: 0 }, { scale: 1, duration: 0.35, ease: 'back.out(1.8)' }, 24.5);
  SPARK(24.55, 1570, 520);
  STATUS(st7, 24.65);
  [24.95, 25.4].forEach((t) => { tl.to(heart, { scale: 1.04, duration: 0.15, ease: 'sine.inOut' }, t); tl.to(heart, { scale: 1, duration: 0.15, ease: 'sine.inOut' }, t + 0.15); });
  exitText(h7, 25.75); subOut(s7, 25.75); STATUS_OUT(st7, 25.75);
  tl.to(reason, { y: -160, scale: 0, duration: 0.3, ease: 'power3.in' }, 25.75);
  tl.to([burst7, offer], { scale: 0, duration: 0.3, ease: 'back.in(1.7)' }, 25.75);
  // the heart parks above the stage, then flies onto the bundle as its bow
  tl.to(heart, { scale: 0.25, y: -190, duration: 0.45, ease: 'power3.inOut' }, 25.75);
  tl.to(heartP, { strokeWidth: 32, duration: 0.45, ease: 'power3.inOut' }, 25.75);
  tl.to(heart, { y: -198, duration: 0.35, ease: 'sine.inOut' }, 26.25);

  // ================================================================ BEAT 8 — bundle (PRO) 26.20–29.20
  const h8 = head(['Sell the', '[whole kit].']);
  const s8 = sub('Every item renews together');
  const st8 = status('Bundled');
  const d8 = disc(C.tint);
  BATON(25.75, markRect(h8));

  const kitG = G(0, 0, '', zMid);
  const [k8x, k8y] = polar(290, 45);
  const ring8 = G(1440, 650, `<path d="${arcPath(0, 0, 290, 135, 405)}" fill="none" stroke="${C.violet}" stroke-width="16" stroke-linecap="round"/>${arrowHead(k8x, k8y, 45 + 90, 24, C.violet)}`, kitG);
  const ring8Arc = ring8.querySelector('path'), ring8Head = ring8.querySelector('polygon');
  gsap.set(ring8Arc, { drawSVG: '0%' }); gsap.set(ring8Head, { scale: 0, transformOrigin: '50% 50%' });
  const candle = G(1298, 800, `<rect x="-75" y="-150" width="150" height="150" rx="22" fill="${C.o100}" stroke="${C.ink}" stroke-width="8"/><rect x="-82" y="-178" width="164" height="36" rx="10" fill="${C.violet}" stroke="${C.ink}" stroke-width="8"/>`, kitG);
  const tin = G(1440, 800, `<rect x="-85" y="-240" width="170" height="240" rx="18" fill="${C.violet}" stroke="${C.ink}" stroke-width="8"/>`, kitG);
  const bottle8 = G(1582, 800, `<rect x="-65" y="-170" width="130" height="170" rx="26" fill="${C.white}" stroke="${C.ink}" stroke-width="8"/><rect x="-30" y="-214" width="60" height="50" rx="10" fill="${C.violet}" stroke="${C.ink}" stroke-width="8"/>`, kitG);
  const prods = [candle, tin, bottle8];
  const ribbonH = S('rect', { x: 1223, y: 680, width: 424, height: 36, fill: C.orange, stroke: C.ink, 'stroke-width': 6 }, kitG);
  const ribbonV = S('rect', { x: 1422, y: 562, width: 36, height: 236, fill: C.orange, stroke: C.ink, 'stroke-width': 6 }, kitG);
  gsap.set(ribbonH, { scaleX: 0, transformOrigin: '0% 50%' }); gsap.set(ribbonV, { scaleY: 0, transformOrigin: '50% 0%' });
  const bow = G(1440, 552, `<path d="M0 0 C -40 -60 -110 -50 -100 0 C -110 50 -40 60 0 0 Z" fill="${C.orange}" stroke="${C.ink}" stroke-width="7" stroke-linejoin="round"/>
    <path d="M0 0 C 40 -60 110 -50 100 0 C 110 50 40 60 0 0 Z" fill="${C.orange}" stroke="${C.ink}" stroke-width="7" stroke-linejoin="round"/>
    <circle r="22" fill="${C.orange}" stroke="${C.ink}" stroke-width="7"/>`, kitG);
  gsap.set(prods, { scale: 0 }); gsap.set(bow, { scale: 0 });
  gsap.set(candle, { x: -500 }); gsap.set(tin, { y: 500 }); gsap.set(bottle8, { x: 500 });
  const subChip = chip('1 subscription', 1440, PHONE ? 850 : 930, { ring: C.ink });

  IRIS(d8, 26.2); tickOn(7, 26.2);
  headIn(h8, 26.25);
  clipDisc(26.24);
  tl.set(prods, { scale: 1 }, 26.25);
  tl.fromTo(candle, { x: -500 }, { x: 0, duration: 0.5, ease: 'expo.out' }, 26.25);
  tl.fromTo(tin, { y: 500 }, { y: 0, duration: 0.5, ease: 'expo.out' }, 26.31);
  tl.fromTo(bottle8, { x: 500 }, { x: 0, duration: 0.5, ease: 'expo.out' }, 26.37);
  subIn(s8, 26.7);
  tl.to(ribbonH, { scaleX: 1, duration: 0.4, ease: 'power3.inOut' }, 26.8);
  tl.to(ribbonV, { scaleY: 1, duration: 0.25, ease: 'power3.out' }, 27.05);
  clipOff(26.9);
  // the heart lands on the bundle exactly as the bow pops, so one object replaces the other
  tl.to(heart, { y: 552 - 660, scale: 0.45, duration: 0.58, ease: 'power2.inOut' }, 26.6);
  tl.to(heartP, { strokeWidth: 16, duration: 0.58, ease: 'power2.inOut' }, 26.6);
  tl.set(heartP, { fill: C.orange }, 26.89);
  tl.set(heart, { visibility: 'hidden' }, 27.18);
  tl.fromTo(bow, { scale: 0.6, rotation: -12 }, { scale: 1, rotation: 0, duration: 0.3, ease: 'back.out(2.2)' }, 27.18);
  tl.to(ring8Arc, { drawSVG: '100%', duration: 0.5, ease: 'power2.inOut' }, 27.3);
  tl.to(ring8Head, { scale: 1, duration: 0.18, ease: 'back.out(2)' }, 27.75);
  pop(subChip, 27.4);
  STATUS(st8, 27.4);
  SPARK(27.45, 1540, 500);
  tl.to(kitG, { rotation: 2, svgOrigin: '1440 800', smoothOrigin: false, duration: 0.5, ease: 'sine.inOut' }, 27.8);
  tl.to(kitG, { rotation: 0, svgOrigin: '1440 800', smoothOrigin: false, duration: 0.45, ease: 'sine.inOut' }, 28.3);
  clipDisc(28.7);
  exitText(h8, 28.75); subOut(s8, 28.75); STATUS_OUT(st8, 28.75);
  tl.to(kitG, { x: 700, duration: 0.4, ease: 'power3.in' }, 28.75);
  tl.set(kitG, { visibility: 'hidden' }, 29.15);
  unpop(subChip, 28.75);

  // ================================================================ BEAT 9 — store credit (PRO) 29.20–32.20
  const h9 = head(['Store credit', '[pays renewals].']);
  const s9 = sub('Refunds can return as credit');
  const st9 = status('Applied');
  const d9 = disc(C.o100);
  BATON(28.75, markRect(h9));

  const wallet = G(1330, 650, `<rect x="-160" y="-115" width="320" height="230" rx="40" fill="${C.violet}" stroke="${C.ink}" stroke-width="8"/>
    <g class="flap"><path d="M-160 -60 V-75 A40 40 0 0 1 -120 -115 H120 A40 40 0 0 1 160 -75 V-10 H-160 Z" fill="${C.vs}" stroke="${C.ink}" stroke-width="8" stroke-linejoin="round"/></g>
    <circle cx="112" cy="-10" r="20" fill="${C.orange}" stroke="${C.ink}" stroke-width="6"/>`, zMid);
  const wFlap = wallet.querySelector('.flap');
  gsap.set(wallet, { x: -600, visibility: 'hidden' }); gsap.set(wFlap, { svgOrigin: '-160 -60' });
  const balance = { v: 65 };
  const balChip = chip('', 1330, PHONE ? 810 : 850, { ring: C.ink, html: 'Balance <span class="amt">$65.00</span>' });
  const balAmt = balChip.querySelector('.amt');
  const slip9 = G(1620, 560, `${slipShape(200, 260)}<g class="glyph">${icon('refresh-cw', -36, -96, 72, C.violet, 2.6)}</g>
    <rect class="cb" x="-64" y="-6" width="128" height="14" rx="7" fill="${C.p3}"/><rect class="cb" x="-64" y="26" width="128" height="14" rx="7" fill="${C.p3}"/><rect class="cb" x="-64" y="58" width="90" height="14" rx="7" fill="${C.p3}"/>`, zMid);
  const slip9Glyph = slip9.querySelector('.glyph'), creditBars = [...slip9.querySelectorAll('.cb')];
  gsap.set(slip9, { scale: 0 }); gsap.set(slip9Glyph, { svgOrigin: '0 -60' });
  const tokens = [0, 1, 2].map(() => G(0, 0, `<circle r="35" fill="${C.orange}" stroke="${C.ink}" stroke-width="6"/>${icon('star', -18, -18, 36, C.white, 2.6)}`, zTop));
  gsap.set(tokens, { x: 1330, y: 600, scale: 0 });
  const seal9 = G(1715, 440, `<circle r="44" fill="${C.violet}" stroke="${C.ink}" stroke-width="6"/>${check(9, C.white, 0.78)}`, zTop);
  gsap.set(seal9, { scale: 0 });

  IRIS(d9, 29.2); tickOn(8, 29.2);
  headIn(h9, 29.25);
  tl.set(wallet, { visibility: 'visible' }, 29.25);
  tl.fromTo(wallet, { x: -600 }, { x: 0, duration: 0.5, ease: 'expo.out' }, 29.25);
  clipOff(29.8);
  pop(balChip, 29.55);
  subIn(s9, 29.7);
  pop(slip9, 29.75);
  tl.to(wFlap, { rotation: -30, duration: 0.2, ease: 'power2.out' }, 29.85);
  // each credit token arcs onto the renewal slip, sinks in and fills one line orange
  tokens.forEach((tk, i) => {
    const t = 29.9 + i * 0.1;
    const ty = 560 + [1, 33, 65][i];
    tl.set(tk, { scale: 1 }, t);
    tl.to(tk, { motionPath: { path: [{ x: 1330, y: 600 }, { x: 1470, y: 420 }, { x: 1610, y: ty }], curviness: 1.3 }, duration: 0.4, ease: 'power2.inOut' }, t);
    tl.fromTo(tk, { scaleY: 0.8 }, { scaleY: 1, duration: 0.1, ease: 'power2.out' }, t + 0.4);
    tl.to(tk, { scale: 0, duration: 0.15, ease: 'power2.in' }, t + 0.5);
    tl.to(creditBars[i], { fill: C.orange, duration: 0.15, ease: 'none' }, t + 0.5);
  });
  tl.fromTo(balance, { v: 65 }, { v: 20, duration: 0.45, ease: 'power2.out', onUpdate: () => { balAmt.textContent = `$${balance.v.toFixed(2)}`; } }, 30.25);
  tl.fromTo(seal9, { scale: 1.6 }, { scale: 1, duration: 0.3, ease: 'back.out(2.2)' }, 30.6);
  STATUS(st9, 30.45);
  SPARK(30.65, 1755, 410);
  exitText(h9, 31.75); subOut(s9, 31.75); STATUS_OUT(st9, 31.75);
  tl.to(seal9, { scale: 0, duration: 0.2, ease: 'power3.in' }, 31.55); // gone before the clip, so the rim never cuts it
  clipDisc(31.74);
  tl.to(wallet, { y: 640, duration: 0.35, ease: 'power3.in' }, 31.75);
  unpop(balChip, 31.75, 0.3, 'power3.in');
  tl.set([wallet, balChip], { visibility: 'hidden' }, 32.1);
  clipOff(32.15);
  // match cut: the renewal slip becomes the refund receipt
  tl.to(slip9, { x: 1260 - 1620, y: 620 - 560, scale: 1.15, duration: 0.45, ease: 'power3.inOut' }, 31.75);
  tl.to(slip9Glyph, { scale: 0, duration: 0.25, ease: 'power3.in' }, 31.75);
  tl.to(creditBars, { fill: C.p3, duration: 0.3, ease: 'none' }, 31.85);

  // ================================================================ BEAT 10 — refunds, then churn risk (PRO) 32.20–37.40
  const hA = head(['[Why] money', 'goes back.']);
  const sA = sub('Refunds by product, reason and timing');
  const hB = head(['Churn risk,', 'scored [0–100].'], { base: L.head.base - 8, pitch: L.head.size * 0.92 + 8 });
  const sB = sub('Scores work without AI');
  const st10 = status('Flagged');
  const d10 = disc(C.p2);
  BATON(31.75, markRect(hA));

  const retArc = S('path', { d: 'M1380 450 C 1350 370 1225 360 1192 440', fill: 'none', stroke: C.orange, 'stroke-width': 16, 'stroke-linecap': 'round' }, zTop);
  const retHeadG = G(1192, 446, arrowHead(0, 0, 112, 22, C.orange), zTop);
  gsap.set(retArc, { drawSVG: '0%' }); gsap.set(retHeadG, { scale: 0 });
  const pill = (x, y, w, h = 44) => { const r = h / 2; return `M${x + r} ${y} H${x + w - r} A${r} ${r} 0 0 1 ${x + w - r} ${y + h} H${x + r} A${r} ${r} 0 0 1 ${x + r} ${y} Z`; };
  // bars: refunds by product / reason / timing, each with its glyph in the left cap
  const barSpec = [[300, C.violet, 516, 'package'], [204, C.orange, 586, 'message-square'], [126, C.ink, 656, 'clock']];
  const bars = barSpec.map(([w, f, y]) => S('path', { d: pill(1420, y, w), fill: f, stroke: C.ink, 'stroke-width': 6 }, zMid));
  const barGlyphs = barSpec.map(([, , y, ic]) => G(1444, y + 22, icon(ic, -14, -14, 28, C.white, 2.6), zMid));
  gsap.set(bars, { scaleX: 0, transformOrigin: '0% 50%' }); gsap.set(barGlyphs, { scale: 0 });
  const GC = { x: 1440, y: 760 }, R1 = 250, R0 = 190;
  const pt = (v, r) => { const a = Math.PI - (v / 100) * Math.PI; return [GC.x + r * Math.cos(a), GC.y - r * Math.sin(a)]; };
  const band = (v0, v1) => {
    const [ax, ay] = pt(v0, R1), [bx, by] = pt(v1, R1), [cx2, cy2] = pt(v1, R0), [dx, dy] = pt(v0, R0);
    return `M${ax.toFixed(1)} ${ay.toFixed(1)} A${R1} ${R1} 0 0 1 ${bx.toFixed(1)} ${by.toFixed(1)} L${cx2.toFixed(1)} ${cy2.toFixed(1)} A${R0} ${R0} 0 0 0 ${dx.toFixed(1)} ${dy.toFixed(1)} Z`;
  };
  const bands = [band(0, 39), band(40, 69), band(70, 100)];
  const bandFill = [C.tint, C.o100, C.orange];
  const barToBand = [1, 2, 0]; // each bar morphs into its nearest band (violet->middle, orange->right, ink->left)
  const needleG = G(1440, 760, `<path d="M0 -9 L-200 0 L0 9 Z" fill="${C.ink}" stroke="${C.ink}" stroke-width="4" stroke-linejoin="round"/><circle r="28" fill="${C.ink}"/><circle r="10" fill="${C.white}"/>`, zTop);
  gsap.set(needleG, { scale: 0 });
  const riskNum = text({ lines: ['72'], size: 140, base: 900, align: 'center', cx: 1440, color: C.ink, maxW: 0, parent: chipL });
  const riskWord = riskNum.words[0];
  const riskProxy = { v: 0 };
  const highChip = chip('High', 1650, 465, { bg: C.ink, fg: C.white });
  const poly = [[1180, 815], [1260, 790], [1340, 770], [1420, 720]];
  const seg = (a, b) => { // thin filled quad along a segment (morph target)
    const [x0, y0] = poly[a], [x1, y1] = poly[b], Ls = Math.hypot(x1 - x0, y1 - y0), nx = (-(y1 - y0) / Ls) * 8, ny = ((x1 - x0) / Ls) * 8;
    return `M${(x0 + nx).toFixed(1)} ${(y0 + ny).toFixed(1)} L${(x1 + nx).toFixed(1)} ${(y1 + ny).toFixed(1)} L${(x1 - nx).toFixed(1)} ${(y1 - ny).toFixed(1)} L${(x0 - nx).toFixed(1)} ${(y0 - ny).toFixed(1)} Z`;
  };
  const measured = S('polyline', { points: poly.map((p) => p.join(',')).join(' '), fill: 'none', stroke: C.violet, 'stroke-width': 16, 'stroke-linecap': 'round', 'stroke-linejoin': 'round' }, zTop);
  gsap.set(measured, { visibility: 'hidden' });

  IRIS(d10, 32.2, 1260, 620); tickOn(9, 32.2);
  headIn(hA, 32.25);
  tl.to(retArc, { drawSVG: '100%', duration: 0.45, ease: 'power2.inOut' }, 32.45);
  tl.to(retHeadG, { scale: 1, duration: 0.18, ease: 'back.out(2)' }, 32.85);
  subIn(sA, 32.65);
  tl.to(bars, { scaleX: 1, duration: 0.4, ease: 'power3.out', stagger: 0.08 }, 32.7);
  tl.to(barGlyphs, { scale: 1, duration: 0.25, ease: 'back.out(2)', stagger: 0.08 }, 32.85);
  exitText(hA, 34.5); subOut(sA, 34.5);
  clipDisc(34.49);
  tl.to([slip9, retArc, retHeadG], { y: '+=640', duration: 0.3, ease: 'power2.in' }, 34.5);
  tl.set([slip9, retArc, retHeadG], { visibility: 'hidden' }, 34.8);
  clipOff(34.85);
  tl.to(barGlyphs, { scale: 0, duration: 0.2, ease: 'power2.in' }, 34.5);
  bars.forEach((b, i) => {
    tl.set(b, { scaleX: 1, transformOrigin: '50% 50%' }, 34.7);
    tl.to(b, { morphSVG: bands[barToBand[i]], duration: 0.55, ease: 'power3.inOut' }, 34.7);
    tl.to(b, { fill: bandFill[barToBand[i]], duration: 0.25, ease: 'none' }, 35.0);
  });
  BATON(34.5, markRect(hB));
  headIn(hB, 34.9);
  tl.set(needleG, { rotation: 0 }, 35.0);
  tl.fromTo(needleG, { scale: 0 }, { scale: 1, duration: 0.25, ease: 'back.out(2)' }, 35.0);
  tl.to(needleG, { rotation: 129.6, duration: 0.6, ease: 'back.out(1.6)' }, 35.05);
  IN(riskNum.words, 35.05, 0.4, 0);
  tl.fromTo(riskProxy, { v: 0 }, { v: 72, duration: 0.6, ease: 'power3.out', onUpdate: () => { riskWord.textContent = String(Math.round(riskProxy.v)); } }, 35.05);
  subIn(sB, 35.3);
  pop(highChip, 35.55, 0.3);
  STATUS(st10, 35.6);
  SPARK(35.65, 1567, 606);
  exitText(hB, 36.95); subOut(sB, 36.95); STATUS_OUT(st10, 36.95);
  tl.to(needleG, { scale: 0, duration: 0.3, ease: 'power3.in' }, 36.95);
  tl.to(riskNum.words, { yPercent: 140, duration: 0.3, ease: 'power3.in' }, 36.95);
  unpop(highChip, 36.95);
  // match cut: the gauge bands become the measured MRR line (violet reads on grey and on ink)
  bars.forEach((b, i) => { const k = barToBand[i]; tl.to(b, { morphSVG: seg(k, k + 1), strokeWidth: 0, duration: 0.45, ease: 'power3.inOut' }, 36.95); tl.set(b, { fill: C.violet }, 37.15); });

  // ================================================================ BEAT 11 — forecast (PRO) 37.40–40.40
  const h11 = head(['Forecast MRR', '[12 months] out.']);
  const s11 = sub('A range, not one number');
  const st11 = status('Projected');
  const d11 = disc(C.ink);
  BATON(36.95, markRect(h11));

  const range = S('polygon', { points: '1420,720 1720,480 1720,690', fill: C.band }, zBack);
  gsap.set(range, { scaleX: 0, svgOrigin: '1420 720' });
  const defs = S('defs', {}, objL);
  defs.innerHTML = `<mask id="dashMask" maskUnits="userSpaceOnUse" x="0" y="0" width="1920" height="1200"><path id="dashReveal" d="M1420 720 L1720 585" fill="none" stroke="#fff" stroke-width="24" stroke-linecap="round"/></mask>`;
  const dashReveal = defs.querySelector('#dashReveal');
  gsap.set(dashReveal, { drawSVG: '0%' });
  S('path', { d: 'M1420 720 L1720 585', fill: 'none', stroke: C.orange, 'stroke-width': 12, 'stroke-dasharray': '24 18', 'stroke-linecap': 'round', mask: 'url(#dashMask)' }, zMid);
  const todayNode = G(1420, 720, `<circle r="22" fill="${C.white}"/><circle r="8" fill="${C.ink}"/>`, zTop);
  const endNode = G(1720, 585, `<circle r="18" fill="${C.orange}" stroke="${C.white}" stroke-width="6"/>`, zTop);
  gsap.set([todayNode, endNode], { scale: 0 });
  const todayChip = chip('Today', 1420, 810);
  const upChip = chip('+4% to +13%', 1540, 440, { bg: C.o100, fg: C.otext });

  IRIS(d11, 37.4, 1300, 770); tickOn(10, 37.4);
  tl.set(measured, { visibility: 'visible' }, 37.4);
  tl.set(bars, { visibility: 'hidden' }, 37.4);
  tl.to(measured, { stroke: C.white, duration: 0.25, ease: 'power2.out' }, 37.45);
  headIn(h11, 37.45);
  tl.fromTo(todayNode, { scale: 0 }, { scale: 1, duration: 0.3, ease: 'back.out(2)' }, 37.8);
  pop(todayChip, 37.9, 0.3);
  subIn(s11, 37.9);
  tl.to(range, { scaleX: 1, duration: 0.6, ease: 'power3.inOut' }, 38.0);
  tl.to(dashReveal, { drawSVG: '100%', duration: 0.5, ease: 'power2.inOut' }, 38.2);
  STATUS(st11, 38.6);
  tl.fromTo(endNode, { scale: 0 }, { scale: 1, duration: 0.3, ease: 'back.out(3)' }, 38.65);
  pop(upChip, 38.65, 0.35, 'back.out(2)');
  SPARK(38.7, 1750, 550);
  exitText(h11, 39.95); subOut(s11, 39.95); STATUS_OUT(st11, 39.95);
  tl.to(range, { scaleY: 0, duration: 0.35, ease: 'power3.in' }, 39.95);
  tl.to([measured, dashReveal], { drawSVG: '0%', duration: 0.35, ease: 'power3.in' }, 39.95);
  tl.to([todayNode, endNode], { scale: 0, duration: 0.3, ease: 'power3.in' }, 39.95);
  unpop(todayChip, 39.95); unpop(upChip, 39.95);
  tl.to(d11, { scale: 0, duration: 0.4, ease: 'power3.in', svgOrigin: '1440 620', smoothOrigin: false }, 40.0);
  tl.to(tierPro, { scale: 0, duration: 0.3, ease: 'back.in(1.7)' }, 40.0);

  // ================================================================ END CARD 40.40–44.20 + loop seam
  const E = L.end;
  const word = text({ lines: ['ArraySubs'], size: E.wordSize, base: E.wordBase, align: 'center', maxW: 0 });
  // wordmark rises by character
  const wordChars = [];
  word.words.forEach((w) => { const t = w.textContent; w.textContent = ''; [...t].forEach((ch) => { const s = document.createElement('span'); s.className = 'w'; s.textContent = ch; w.appendChild(s); wordChars.push(s); }); });
  gsap.set(word.words, { yPercent: 0 }); gsap.set(wordChars, { yPercent: 140 });
  const tag12 = text({ lines: ['The WooCommerce', 'subscription plugin'], size: E.size, base: E.base, pitch: E.pitch, align: 'center', maxW: E.max });
  const tag3 = text({ lines: ['built to [keep customers].'], size: tag12.size, base: E.l3Base, align: 'center', maxW: E.max });
  const l3 = tag3.lines[0].words;
  const kcEndMark = tag3.marks[0];
  const kcEndWords = l3.filter((w) => w === kcEndMark.el || w.textContent === '.');
  kcEndWords.forEach((w) => { w.style.visibility = 'hidden'; });
  const tagVisible = [...tag12.words, ...l3.filter((w) => !kcEndWords.includes(w))];
  // map the hook KC line onto the end-card slot
  const sEnd = tag3.size / HK.size;
  const L0 = rectOf(kcLine);
  const mR = kc.marks[0].r, eR = kcEndMark.r;
  const kcEndX = eR.left - L0.left - sEnd * (mR.left - L0.left);
  const kcEndY = eR.top - L0.top - sEnd * (mR.top - L0.top);
  const endB = brect(eR, tag3.size);
  const burstEnd = { x: endB.left + endB.width - 30 * hk * sEnd - burstHookC.x, y: endB.top + endB.height / 2 - 80 * hk * sEnd - burstHookC.y };

  // pills: verified Free facts the hero copy does not repeat; tinted or outlined, never a button
  const pills = [['Memberships & boxes', C.tint, C.vs, null], ['25 automatic emails', C.o100, C.otext, null], ['No cut of your sales', C.white, C.vs, C.violet]].map(([l, bg, fg, ring]) =>
    chip(l, 0, E.pillY, { bg, fg, ring, ringW: 4, size: E.pillSize, h: E.pillH, pad: E.pillPad, parent: textL }));
  const widths = pills.map((p) => p.offsetWidth);
  const rows = [[]];
  widths.reduce((acc, w, i) => { const next = acc + (rows[rows.length - 1].length ? 24 : 0) + w; if (next > E.pillMax && rows[rows.length - 1].length) { rows.push([i]); return w; } rows[rows.length - 1].push(i); return next; }, 0);
  rows.forEach((row, r) => {
    const total = row.reduce((a, i) => a + widths[i], 0) + 24 * (row.length - 1);
    let xx = L.cx - total / 2;
    row.forEach((i) => { pills[i].style.left = `${xx + widths[i] / 2}px`; pills[i].style.top = `${E.pillY + r * E.rowGap}px`; xx += widths[i] + 24; });
  });

  BATON(39.95, endB, 0.6);
  tl.to(ticks, { scale: 1.5, duration: 0.15, ease: 'power2.out', stagger: 0.035 }, 40.4);
  tl.to(ticks, { scale: 1, duration: 0.15, ease: 'back.out(2)', stagger: 0.035 }, 40.55);
  tl.to(ticks[10], { width: TK.dot, duration: 0.3, ease: 'power3.inOut' }, 40.4);
  IN(wordChars, 40.45, 0.5, 0.02);
  tag12.lines.forEach((l, i) => IN(l.words, 40.5 + i * 0.08, 0.55, 0.04));
  IN(l3.filter((w) => !kcEndWords.includes(w)), 40.66, 0.55, 0.04);
  // the hook's KC line takes the end-card slot (restored to its white marked state first)
  tl.set(kcMark, { color: C.white, marginLeft: '0.22em', marginRight: '0.22em' }, 39.0);
  tl.set(kcLine, { x: kcEndX, y: kcEndY, scale: sEnd }, 39.0);
  IN(kc.words, 40.66, 0.55, 0.04);
  // squiggle + starburst take their end-card places
  tl.set(squig, { x: L.squigEnd.dx, y: L.squigEnd.dy }, 39.0);
  tl.to(squigPath, { drawSVG: '100%', duration: 0.45, ease: 'power2.out' }, 40.55);
  tl.set(hookBurst, { x: burstEnd.x, y: burstEnd.y, rotation: -30, scale: 0 }, 39.0);
  tl.to(hookBurst, { scale: sEnd, rotation: 0, duration: 0.4, ease: 'back.out(1.8)' }, 40.6);
  pills.forEach((p, i) => {
    tl.fromTo(p, { scale: 0.6, y: 20 }, { scale: 1, y: 0, duration: 0.4, ease: 'back.out(1.6)' }, 41.15 + i * 0.08);
  });
  // seam
  tl.to([...ticks].reverse(), { backgroundColor: C.line, duration: 0.2, ease: 'none', stagger: 0.02 }, 43.2);
  tl.to(wordChars, { yPercent: -140, duration: 0.35, ease: 'power3.in', stagger: 0.01 }, 43.2);
  tl.to(tagVisible, { yPercent: -140, duration: 0.35, ease: 'power3.in', stagger: 0.04 }, 43.2);
  tl.to([...pills].reverse(), { scale: 0, duration: 0.25, ease: 'power3.in', stagger: 0.04 }, 43.2);
  tl.to(kcLine, { x: 0, y: 0, scale: 1, duration: 0.7, ease: 'power3.inOut' }, 43.45);
  BATON(43.45, hookB, 0.7);
  tl.to(squig, { x: 0, y: 0, duration: 0.7, ease: 'power3.inOut' }, 43.45);
  tl.to(hookBurst, { x: 0, y: 0, scale: 1, duration: 0.7, ease: 'power3.inOut' }, 43.45);
  tl.set({}, {}, DUR);

  // ---------------------------------------------------------------- expose
  tl.seek(0, false);
  window.__duration = DUR;
  window.__seek = (t) => { tl.seek(Math.max(0, Math.min(DUR - 1e-6, t)), false); };
  window.__tl = tl;
})();
