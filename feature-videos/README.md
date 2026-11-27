# ArraySubs feature videos (recording harness)

Long, voiceless product tours for the 11 flagship feature pages on the website
(`web-content/src/pages/features/<slug>.astro`, hero `VideoPlaceholder`). Recorded on the
local store `http://localhost:10013` (wp-test2) with a headless Chromium. No windows open.

Output: 1920x1080, 30 fps, H.264, no audio track. Annotation text, lower-third captions,
spotlight rings, a visible cursor and smooth zooms are drawn by the harness.

## How it works

- `lib/stage/index.html` is served same-origin at `http://localhost:10013/__fvstage/` (Playwright
  route, nothing written to the site). The store runs in an iframe (`name="app"`, 1440x810 CSS px,
  device scale 4/3 = 1920x1080). Overlays (captions, ring, label, cursor, title cards) sit above it.
  Zoom = CSS transform on the iframe, so zoomed text stays sharp.
- Time is **stepped**, not real: every animation frame is set, then captured (CDP JPEG) and piped
  into ffmpeg. Page loads, spinners and waits are cut out. Holds cost one capture.
- Every state change (navigation, click, `act`) freezes the old picture and crossfades to the
  settled new one.
- Each chapter becomes `seg-NN.mp4`; `run.mjs` joins them into `<slug>.mp4` and writes
  `poster.webp`. Re-record one chapter with `--chapters=N` and the others are reused.
- `mu-plugins/zz-feature-video-clean.php` (temporary) only affects requests with the
  `fv_clean=1` cookie, which only the recording browser sends: it strips the `Demo · ` product
  prefix, applies display-only renames from `clean-map.d/*.json`, and names the store
  "Brightside Market". Real data is never changed. Do **not** save product edit forms while
  recording unless you mean to (the form would hold the cleaned title).

## Commands

```bash
export FV_OUT=/private/tmp/claude-501/-Users-emran-Local-Sites-wp-test2-app-public-wp-content-plugins/e62dc59c-5e0b-4c62-9e18-7f9bad2105f8/scratchpad/fv-out
cd "/Users/emran/Local Sites/wp-test2/app/public/wp-content/plugins/qa/feature-videos"

# scout a page: screenshot + outline of headings/buttons/inputs with y positions and selector hints
node probe.mjs --as=1 --url="/wp-admin/admin.php?page=arraysubs-mainadmin#/subscriptions" --out=$FV_OUT/<slug>/probe-1.jpg
node probe.mjs --as=1050 --url=/my-account/subscriptions/ --click="text=View" --out=...

# record (all chapters, or some) -> $FV_OUT/<slug>/<slug>.mp4 + poster.webp + review/*.jpg stills
node run.mjs <slug>
node run.mjs <slug> --chapters=3,4      # re-record chapters 3 and 4, then re-join
node run.mjs <slug> --assemble-only

# review: contact sheets (3x4 tiles, one frame every N seconds)
./sheet.sh $FV_OUT/<slug>/<slug>.mp4 3
# a single full-size frame
ffmpeg -loglevel error -y -ss 42.5 -i $FV_OUT/<slug>/<slug>.mp4 -frames:v 1 $FV_OUT/<slug>/f-42.jpg
```

`review/NN-MMM-<note>.jpg` are the frames held >= 0.6 s, in order, per chapter. Look at them.
WP-CLI from a scene: `wp(['option','get','x'])` or `wpJson('echo json_encode(...);')` (both run
with `--allow-root` and Local's `.envrc`).

## Scene file: `scenes/<slug>.mjs`

```js
export default {
  slug: 'audit-logs',
  tier: 'Free',                          // or 'Pro' — shown as a chip on every card
  intro: { title: 'Audit *logs*', sub: 'One sentence on the outcome.' },   // replaces chapter 1's card
  outro: { title: 'Know who changed *what*.', sub: 'Short closing line.', url: 'subscriptionplugin.pro' },
  setup: async (v, { wp }) => {},        // optional, not recorded (prepare data)
  teardown: async (v, { wp }) => {},     // optional, always runs (restore settings!)
  chapters: [
    {
      kicker: 'Admin',                   // short: Admin, Storefront, My Account, Checkout, Settings…
      title: 'Every change, *logged*',   // card headline (uppercase on screen, *word* = accent)
      sub: 'Optional card subline.',
      before: async (v, { wp }) => {},   // optional, not recorded
      run: async (v) => {
        await v.as(1);                   // admin. v.as(<user id|login>) for a customer, v.as('guest')
        await v.goto('/wp-admin/admin.php?page=arraysubs-mainadmin#/audit-logs');
        await v.say('Every change to a subscription is **written down**.');
        await v.focus('.arraysubs-data-list', { label: 'Who, what and when', dim: true });
        await v.zoom('.some-row', { scale: 1.7 });
        await v.click('text=View details');
        await v.zoomOut();
        v.poster();                      // optional: use the current frame as the poster
      },
    },
  ],
};
```

### API (`v`)

Targets are Playwright selectors inside the store frame (`css`, `text=…`, `role=button[name="Save"]`,
`.class >> nth=2`, `:has-text()`), a Locator from `v.frame.locator(...)`, or a rect `{x,y,w,h}` in
page (iframe viewport) pixels. The first match is used.

| Call | What it does |
| --- | --- |
| `await v.as(user)` | Log in as a WP user id/login (`1` = admin) or `'guest'`. Cookies only, no forms. |
| `await v.goto(url, {hold})` | Navigate (site-relative ok), wait until settled, crossfade. Resets zoom. |
| `await v.say(text, {kicker, pos, hold})` | Lower-third caption, stays until replaced. `**bold**` = accent. `v.say(null)` hides. `pos`: `bl` (default), `br`, `bc`, `tl`, `tr`. Auto hold ~ reading time. |
| `v.captionPos('br')` | Move the caption for the rest of the chapter (when it would hide the UI you show). |
| `await v.focus(target, {label, dim, side, num, pad, hold})` | Purple ring around the target, optional dimmed surroundings and a label pill (`side`: auto/top/bottom/left/right; `num` = numbered badge). |
| `await v.unfocus()` | Remove ring/label. (Clicks, scrolls and navigation also clear it.) |
| `await v.zoom(target, {scale, pad, dur, hold})` | Smooth camera zoom onto the target (fit by default, 1–2.4x). |
| `await v.zoomOut()` | Back to full screen. |
| `await v.click(target, {nav, wait, settle})` | Cursor travels there, press + ripple, real click, settle, crossfade. `nav:true` waits for a page navigation; `wait` = extra ms. |
| `await v.hover(target)` | Move the cursor (real hover states apply). |
| `await v.type(target, text, {clear, cps})` | Click the field, clear it, type visibly. |
| `await v.press('Enter')` | Key press + settle + crossfade. |
| `await v.select(target, value)` | Native `<select>`: animate and pick an option. |
| `await v.scrollTo(target or y, {block})` | Smooth scroll (finds the right scroll container). `block`: center/start/end. |
| `await v.scrollBy(dy)` | Smooth scroll the document. |
| `await v.act(async (frame, page) => {...})` | Do anything off-camera (fill forms fast, open menus, wait), then crossfade. |
| `await v.hold(sec)` | Keep the current frame on screen. |
| `await v.settle()` | Wait for network quiet + no visible spinners (called by goto/click/act). |
| `v.frame` / `v.page` | Playwright Frame (the store) and Page (the stage). `await v.eval(fn, arg)` runs in the store frame. |
| `v.poster()` | Mark the current frame as the poster. |

Notes:
- Zoom and focus measure the element when called. Scroll first, then focus/zoom.
- Fixed headers: `scrollTo` accounts for the WP admin bar.
- If an element is inside a scrollable panel (React admin pages), `scrollTo` scrolls that panel.
- `v.click` uses real mouse events at the cursor point, so menus/hover-dependent UI work.
- Typing in React inputs: `v.type` uses real key events.
- After a click that opens a modal, the crossfade shows the modal; `focus` it next.

## Style rules (what a good tour looks like)

- **Normally full screen.** Zoom only to make a detail readable, then zoom out. Never sit zoomed for
  more than ~4 s. Keep zoom 1.4–2.0x for UI details.
- **One idea per caption**, 4–12 words, plain customer language. Short `kicker` (Admin, Storefront,
  My Account, Checkout, Settings, Reports). No marketing fluff, no claims the UI does not show.
- Show the real flow: open the screen, point at the control, use it, show the result.
- Pace: a caption stays >= its auto reading time; each screen gets 3–8 s; whole video 2.5–5 min.
- Annotation labels are short (2–6 words). Use `dim: true` for the one thing that matters on a busy screen.
- No QA junk on camera: product or customer names like `test`, `QA …`, `QPC …`, `[Refund Analytics QA]`,
  `Fixture`, `12 prod`, `wst`, `@example.test` emails, empty or broken states, PHP notices, debug text.
  Pick clean demo records; for unavoidable ones add display-only renames in `clean-map.d/<slug>.json`
  (`{"map":{"QA Name":"Real Name"}}`).
- Storefront product pages: use the demo products with images (coffee, beauty, pets, supplements,
  meal kits). Avoid the Shop archive (full of test products).
- Never show feature *types* (text/number/toggle) on customer screens (they are not there anyway).
