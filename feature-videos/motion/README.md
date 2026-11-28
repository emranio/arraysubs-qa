# Motion pieces (text, shapes and objects only)

Voiceless motion graphics for the website, built as seekable HTML + GSAP and rendered frame by
frame. No screenshots or recorded UI: every frame is type, flat shapes, palette colours and flat
objects (brand rules in `AGENTS.md`, "Image Generation Style").

## Layout

- `render.mjs`: renders a piece to MP4, PNG stills or a contact sheet. The page must define
  `window.__duration` and a synchronous `window.__seek(t)`. Time is stepped (30 fps), not real.
- `publish.mjs`: web encode (H.264, no audio, faststart) into `web-content/public/<path>.mp4`
  plus a frame-0 WebP poster (through the website's sharp; this ffmpeg has no libwebp).
- `icons.mjs`: writes `<piece>/icons.js` from lucide-react's icon data (same icons as the site).
- `home-pitch/`: the homepage hero pitch. `pitch.js` holds one paused GSAP timeline; `?layout=phone`
  renders the 1080x1080 phone cut from the same timeline. `STORYBOARD.md` is the design source.
  GSAP plugins and the Funnel fonts are vendored in `home-pitch/vendor/`.

## Commands

```bash
cd "/Users/emran/Local Sites/wp-test2/app/public/wp-content/plugins/qa/feature-videos/motion"
node render.mjs home-pitch --sheet=/tmp/sheet.jpg --every=1.1            # contact sheet
node render.mjs home-pitch --stills=2.9,38.8 --still-dir=/tmp/stills      # full-size PNG stills
node render.mjs home-pitch --out=/tmp/pitch.mp4 --png                     # desktop 1920x1200
node render.mjs home-pitch --query=layout=phone --w=1080 --h=1080 --out=/tmp/pitch-phone.mp4 --png
node publish.mjs /tmp/pitch.mp4 media/home/pitch
node publish.mjs /tmp/pitch-phone.mp4 media/home/pitch-phone
```

The homepage hero (`web-content/src/data/product/homepage.ts`, `hero.primary`) plays
`media/home/pitch` and switches to `media/home/pitch-phone` below 640 px (`MediaAsset.phone`).

## Traps

- `page.evaluate(() => window.__seek(t))` must not return the GSAP timeline (serializing it hangs).
- Set `CSSPlugin.defaultSmoothOrigin = false`: irises that grow from different points of one disc
  otherwise drift off centre.
- Tweening a margin keeps its original unit: animate `'0.22em'` strings, never `0.22 * size`.
- Hidden words in line masks need `yPercent` ±140, not ±110, or glyph tops leak under the mask.
- Retracted round-cap strokes leave dots: hide spark groups after they retract.
