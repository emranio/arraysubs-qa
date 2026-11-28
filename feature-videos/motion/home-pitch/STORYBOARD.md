# Home pitch storyboard

Source storyboard for `home-pitch/` (homepage hero motion pitch). It was synthesized from three candidate boards and then revised by two review rounds; `pitch.js` is the source of truth for exact timings (beat 10B onward runs 0.4 s later than below, total 44.2 s).

Fact and tier sources are cited in section 6 (user guide paths and web-content data files).

## FINAL STORYBOARD

### 0. Spec

| Item | Value |
|---|---|
| Canvas | 1920×1200 (16:10), 30 fps |
| Length | **43.80 s = 1314 frames (0–1313).** Frame 1314 equals frame 0 |
| Sound | None |
| Poster | Frame 0 |
| Order | Hook → **Free act:** T2, T1, T3, T4, T5, T8 → Divider → **Pro act:** T6, T7, T9, T10, T11 → End card → seam |
| Tiers | FREE chip on T2, T1, T3, T4, T5, T8. **PRO chip on T6, T7, T9, T10, T11** |
| Content | Only kinetic type, flat shapes, palette colours, simple flat objects, chips and pills |
| Not allowed | Screenshots, windows, panels, admin screens, browser chrome |

Topic 2 comes before topic 1 on purpose: the box is filled first, then it renews, and its reward tag stays on.

---

### 1. Global system

#### 1.1 Layout (px, 1920×1200)

**Safe area:**
- Important content stays at x ≥ 192 and ≤ 1824, with y 96–1128.
- x < 154 is for decoration only.

**Text column (left-aligned, x 192–1064, 872 px wide):**

| Element | Position |
|---|---|
| Tier chip | x 192, y 268–344 |
| Headline | Line 1 baseline y 508, line 2 baseline y 618 |
| Sub | Baseline y 730 |
| Status row | y 816–912: icon disc Ø96 at x 192–288, pill starting at x 308 |

**Stage disc:**
- Centre (1440, 620), Ø680, so x 1100–1780 and y 280–960.
- Object safe box: x 1170–1720, y 340–940. Chips may overhang the disc edge.

**Progress ticks:**
- y 1104–1118, starting at x 192.
- 11 ticks, each 14×14 with radius 7, on a 30 px pitch.
- An extra 24 px gap after tick 6 (Free | Pro).
- They lay out in a flex row, so a widening tick pushes the ones after it.

**Centred scenes (Hook, Divider, End):** use x 260–1660. The hook and end card do not show the tier chip or stage disc.

#### 1.2 Type

Fonts are Funnel Display and Funnel Sans, both variable, loaded from local woff2.

| Role | Font, weight, size | Details |
|---|---|---|
| Hook | Display 700, 168 | Tracking −0.045em, line-height 0.92, centred |
| Beat headline | Display 700, **120** | Floor 110 if the widest line is over 872 px. Tracking −0.045em, line-height 0.92, margin-inline-start −0.04em, ink |
| Divider | Display 700, 120 | White, centred |
| End tagline | Display 700, 112 | Centred, line pitch 103 |
| Wordmark | Display 700, 72 | Ink, plain type. No logo file |
| Big numbers | Display 700, 140 | Tabular figures, or fixed-width digit slots |
| Sub | Sans 600, 52 | `#262626`, tracking −0.01em, one line of 34 characters or fewer |
| Status word | Sans 700, 50 | Pill 96 tall, radius 999, padding 0 36 |
| Tier chip | Sans 700, 44 | Uppercase, +0.09em, 76 tall, padding 0 28 |
| Object chips | Sans 700, 44 | 80 tall, radius 999, padding 0 30 |
| End pills | Sans 700, 46 | 88 tall, padding 0 36 |

**Tier chip styles:**
- FREE: white fill, 4 px ink ring, ink text.
- PRO: `#7E43F6` fill, white text.

**Marker ("baton"):**
- One absolutely positioned div. Solid `#7E43F6`, radius 999, padding `0 0.22em 0.21em`, top inset 0.04em.
- The highlighted word on it is white. Punctuation always sits outside the marker, in ink.
- Only one baton exists, and it is on screen for all 43.80 s.

#### 1.3 Colour tokens (solid fills only)

| Token | Hex |
|---|---|
| violet | `#7E43F6` |
| violet-strong | `#6A38CF` |
| tint | `#EFE8FE` |
| orange | `#FF4D00` |
| orange-100 | `#FFE3D5` |
| orange text | `#A13504` |
| ink | `#14100B` |
| ink-3 | `#262626` |
| paper-2 | `#F2F2F2` |
| paper-3 | `#E7E7E7` |
| line | `#DCDCDC` |
| dot-grid tint | `#C7AEFB` |
| forecast band | `#39225D` |
| flood pending-tick | `#AB85F9` |

Hard rules: no gradients, shadows, blur, filters or 3D. Flips use scaleX only, and there are no opacity fades.

**Object outlines:**
- 8 px ink with round joins on light discs.
- On ink discs, line art is white or coloured with no ink outline.

**Status pill tones (the disc and the pill share the tone):**

| Status | Fill / text | Lucide icon |
|---|---|---|
| Rewarded | `#FFE3D5` / `#A13504` | gift |
| Paid | `#7E43F6` / white | truck |
| Recovered | `#EFE8FE` / `#6A38CF` | mail-check |
| Granted | `#F2F2F2` / ink | key-round |
| Dripped | white with 4 px `#7E43F6` ring / `#6A38CF` | book-open |
| Tracked | `#EFE8FE` / `#6A38CF` | chart-line |
| Saved | `#14100B` / white | heart-handshake |
| Bundled | `#7E43F6` / white | boxes |
| Applied | `#FFE3D5` / `#A13504` | wallet |
| Flagged | `#14100B` / white | gauge |
| Projected | white with 4 px violet ring / `#6A38CF` | trending-up |

- Icons are drawn at 48 px with stroke-width 2 on the 24 grid, round caps.
- All the icons used were checked and are present in `web-content/node_modules/lucide-react/dist/esm/icons/`.
- These words are event pills, never subscription statuses.

#### 1.4 Persistent layer

Layer order, back to front:
1. White canvas
2. Violet ring
3. Dot grid
4. Orange circle
5. Stage disc / flood
6. Object layer
7. Baton
8. Text (tier chip, headline, sub, status row)
9. Ticks

**Orange circle:**
- `#FF4D00`, Ø520, centre (1880, 30), cropped by the canvas edge.
- Heartbeat: scale 1→1.035 (power2.out 0.20), then back to 1 (power2.inOut 0.35).
- Heartbeat times: 0.65, 3.45, 6.45, 10.10, 13.10, 16.10, 19.05, 24.60, 27.55, 30.60, 35.35, 38.35, 40.70.

**Violet ring:**
- Centre (0, 0), centreline r 190, stroke 40 `#7E43F6`, round caps.
- Two 120° arcs with 60° gaps.
- Rotates 0→360° with ease `none` over exactly 0.00–43.80. This is the only global loop.

**Dot grid:**
- 5×3 dots, Ø12, pitch 34, `#C7AEFB`, at x 1650–1798, y 1036–1116.
- On each headline landing it runs a wave: each dot scales 1→1.6→1 over 0.40 (sine.inOut), staggered 0.03 from the left.
- Wave times: 0.65, 2.85, 5.85, 8.70, 12.45, 15.45, 18.45, 23.55, 26.95, 29.95, 32.95, 35.20, 37.75, 40.70.

**Progress ticks:**
- Pending `#DCDCDC`. Active is a 56×14 violet pill. Done is a 14×14 violet dot.
- At each beat start the tick widens 14→56 (power3.inOut 0.40) and the previous tick shrinks to done.

#### 1.5 Motion macros

All times are absolute seconds.

**`HEAD_IN(T)`**
- SplitText lines and words, inside overflow-hidden line masks with padding-bottom 0.12em.
- Line 1 words: yPercent 110→0, expo.out 0.55, stagger 0.045, starting at T.
- Line 2 starts at T+0.10.
- **Landed by T+0.70.**
- The highlighted word rises already white into the waiting baton.

**`SUB_IN(T)`:** line mask, yPercent 100→0, power3.out 0.45.

**`STATUS(T)`**
- Disc: scale 0→1, back.out(2) 0.30, at T.
- Pill: scaleX 0→1 from the left, power3.out 0.30, at T+0.08.
- Word: x −24→0 with a clip reveal, power2.out 0.20, at T+0.15.
- **Done at T+0.35.**

**`TEXT_OUT(T)`**
- Headline words: yPercent 0→−110, power3.in 0.35, stagger 0.025.
- Sub: yPercent 0→−100, power3.in 0.30.
- Pill: scaleX→0 from the left, back.in(1.7) 0.30.
- Disc: scale→0, back.in(1.7) 0.25, at T+0.05.

**`BATON(T, word)`**
- Tween left, top, width and height to the measured landed rect of that word, power3.inOut 0.50.
- The rect is in stage coordinates at yPercent 0.

**`IRIS(T, hex, origin)`**
- A new disc above the old one, scale 0→1 from the origin, expo.out 0.60.
- Remove the old disc at T+0.60.

**`SPARK(T, x, y)`**
- Three `#FF4D00` strokes, 10 px wide, 44 px long, round caps, at −20°, −55° and −90° from the point.
- Scale 0→1, expo.out 0.30. Hold 0.15. Then DrawSVG retract, power2.in 0.20.

**Idle during holds:** one bob cycle on the hero object, y 0→−6→0 (sine.inOut) over the hold. Text never moves while it is held.

**Clip windows:** the object layer gets `clip-path: circle(372px at 1440px 620px)` only during 7.60–8.70 and 28.70–29.80, for the push transitions.

---

### 2. Master beat table

`[ ]` marks the word on the marker.

| # | Time (s) | Frames | Topic | Tier | Headline | Sub | Pill | Disc |
|---|---|---|---|---|---|---|---|---|
| H | 0.00–2.10 | 0–62 | Hook | — | "Built to" / "[keep customers]." | — | — | none |
| 1 | 2.10–5.10 | 63–152 | T2 Box reward | FREE | "Fill the box." / "[Gift] unlocked." | Gifts unlock as the box fills | Rewarded | `#EFE8FE` |
| 2 | 5.10–8.10 | 153–242 | T1 Renewal paid | FREE | "Renewal [paid]." / "Box ready." | A new order every renewal | Paid | `#FFE3D5` |
| 3 | 8.10–11.70 | 243–350 | T3 Declined, then paid | FREE | "Card [declined]." then "Then [paid]." | Email sent right away | Recovered | `#F2F2F2` |
| 4 | 11.70–14.70 | 351–440 | T4 New member | FREE | "Members area," / "[unlocked]." | No separate membership plugin | Granted | `#EFE8FE` (match cut) |
| 5 | 14.70–17.70 | 441–530 | T5 Drip | FREE | "Lesson 2 opens" / "on [day 7]." | Ebooks drip into Downloads too | Dripped | `#FFE3D5` |
| 6 | 17.70–20.70 | 531–620 | T8 Home | FREE | "Your business," / "in [one glance]." | MRR, renewals and what needs you | Tracked | `#14100B` |
| D | 20.70–23.00 | 621–689 | Free→Pro | flip | "Start [free]." / "Add [Pro] when you grow." | — | — | violet flood |
| 7 | 23.00–26.20 | 690–785 | T6 Save offer | **PRO** | "[Cancel]?" then "They [stayed]." | Offers match the cancel reason | Saved | `#F2F2F2` |
| 8 | 26.20–29.20 | 786–875 | T7 Bundle | **PRO** | "Sell the" / "[whole kit]." | Every item renews together | Bundled | `#EFE8FE` |
| 9 | 29.20–32.20 | 876–965 | T9 Store credit | **PRO** | "Store credit" / "[pays renewals]." | Refunds can return as credit | Applied | `#FFE3D5` |
| 10 | 32.20–37.00 | 966–1109 | T10 Refunds + churn | **PRO** | A: "[Why] money" / "goes back." B: "Churn risk," / "scored [0–100]." | A: By product, reason and timing. B: Scores work without AI | Flagged | `#F2F2F2` |
| 11 | 37.00–40.00 | 1110–1199 | T11 Forecast | **PRO** | "Forecast MRR" / "[12 months] out." | A range, not one number | Projected | `#14100B` |
| E | 40.00–43.80 | 1200–1313 | End card | — | "ArraySubs" + "The WooCommerce" / "subscription plugin" / "built to [keep customers]." | Pills: Start free · No cut of your sales · 1-minute setup | — | none |

---

### 3. Beat-by-beat

#### H: Hook, 0.00–2.10

**Frame 0 (poster):**
- **KC group:**
  - The baton sits behind "keep customers" in Display 700 168 px white, followed by "." in ink.
  - The group is centred on x 960, baseline y 700.
- **Starburst:**
  - 12 points, `#FF4D00`, Ø220.
  - It sits behind the baton, centred on the baton's right end, offset (−30, −80).
- **Squiggle:** ink, 14 px stroke, round caps, 3 waves, at x 230–450, y 870–930.
- **Motifs:** the ring is at 0°.
- **Ticks:** all `#DCDCDC`.
- **Not shown:** no disc and no chip.

| Time | Element | Action | Ease · duration |
|---|---|---|---|
| 0.00–0.20 | — | hold | — |
| 0.20–0.65 | KC group | y +70, so the baseline moves to 770 | power3.inOut 0.45 |
| 0.20–0.65 | "Built to" (168 px ink, centred, baseline 615) | words rise | expo.out 0.45, stagger 0.06 |
| 0.58–0.95 | baton | squash: scaleY 1→0.92 (power2.in 0.07), then 0.92→1 (back.out(3) 0.30), origin at the bottom | — |
| 0.35–0.95 | squiggle | CustomWiggle rotation ±6°, 4 wiggles | 0.60 |
| 0.40–0.85 | starburst | rotation 0→30° (power3.out); scale 1→1.12→1 (yoyo) | 0.45 |
| 0.65 | — | heartbeat and dot wave | — |
| 0.65–1.75 | — | hold. "Built to" is readable for 1.10 s | — |
| 1.75–2.05 | "Built to" and KC text with its "." | yPercent 0→−110. The baton stays, now empty | power3.in 0.30, stagger 0.04 |
| 1.75–2.05 | squiggle | DrawSVG 100%→0 | power2.in 0.30 |
| 1.75–2.05 | starburst | scale→0, rotation +40° | back.in(1.7) 0.30 |
| 1.80–2.30 | baton | BATON → "Gift" | power3.inOut 0.50 |

#### Beat 1: T2, box reward (FREE), 2.10–5.10

**Objects:**
- **Open box:**
  - Front face 360×260 with radius 24, centred (1440, 720). White fill, 8 px ink outline.
  - A violet tape stripe, 40 px wide, runs down x 1420–1460.
  - The inner back face is a `#E7E7E7` strip at y 540–590.
  - Two flaps, each 180×90, in `#FFE3D5` with ink outline, rotated outward ±35°.
- **Track:** 3 pills, each 120×28, in `#E7E7E7`, at y 872–900. They span x 1246–1366, 1380–1500 and 1514–1634.
- **Items:**
  - Jar: white with a violet lid.
  - Dropper bottle: orange.
  - Tube: tint body with a violet cap.
  - Each item lands behind the front face so its top peeks out.
- **Gift:** a violet cube, 150×130, with an orange ribbon cross and bow.
- **Tag:** `#FF4D00`, 200×84, radius 18, white "15% off" in Sans 700 44 px. It hangs from the right flap on a 4 px ink string, at about (1690, 640).

| Time | Action |
|---|---|
| 2.10 | IRIS `#EFE8FE` from (1440, 620). Tick 1 becomes active |
| 2.15–2.50 | FREE chip pops in: scale 0.6→1, back.out(1.6) 0.35 |
| 2.15 | HEAD_IN. Landed 2.85 |
| 2.25–2.85 | Box rises: y +260→0, back.out(1.4) 0.60 |
| 2.45–2.85 | Track segments: scaleX 0→1 from the left, power3.out 0.40, stagger 0.05 |
| 2.60 | SUB_IN |
| 2.75 / 2.88 / 3.01 | Items drop: y −420→0, power2.in 0.30. They land at 3.05 / 3.18 / 3.31 |
| on each landing | The item squashes: scaleY 0.85→1, back.out(3) 0.20. The next track segment fills `#7E43F6`, power3.out 0.25 |
| 3.31–3.66 | Orange starburst, Ø320, behind the gift at (1440, 520): scale 0→1, rotation 0→20°, back.out(1.8) 0.35 |
| 3.31–3.76 | The gift pops out of the box to (1440, 520): scale 0.5→1, back.out(2) 0.45 |
| 3.35–4.05 | The tag swings in: rotation −40→0°, CustomWiggle with 3 wiggles, 0.70 |
| 3.35 | SPARK at (1530, 450) |
| 3.30 | STATUS "Rewarded". Done 3.65 |
| 3.65–4.65 | Hold |
| 4.65 | TEXT_OUT |
| 4.65–5.00 | Gift sinks into the box (y +80, scale 0.5), power3.in 0.30. Starburst scales to 0, back.in(1.7) 0.30. Flaps close, power2.inOut 0.35. Track scales X to 0 from the right, power3.in 0.30 |
| 4.65–5.10 | The **closed box keeps its "15% off" tag** and carries into the next beat |
| 4.65–5.15 | BATON → "paid" |

#### Beat 2: T1, renewal paid (FREE), 5.10–8.10

| Time | Action |
|---|---|
| 5.10 | IRIS `#FFE3D5` from the box centre. Tick 2 becomes active |
| 5.10–5.50 | Box moves from (1440, 720) to (1380, 660) and scales 1→0.9, power3.inOut 0.40. The tag follows |
| 5.15 | HEAD_IN "Renewal [paid]." / "Box ready.". Landed 5.85 |
| 5.20–5.80 | Renewal ring draws: r 290 around (1440, 650), 18 px `#7E43F6`, a 330° arc with an arrowhead. DrawSVG 0→100%, power2.inOut 0.60 |
| 5.80–6.30 | The ring rotates 360°, power3.inOut 0.50 |
| 5.60 | SUB_IN "A new order every renewal" |
| 5.95–6.40 | Order slip slides out from behind the box to (1620, 690), rotation 0→6°, expo.out 0.45. It is 200×270, white, with an 8 px ink outline and a zigzag bottom |
| 6.10–6.40 | Four `#E7E7E7` bars (14 px) and one short violet bar draw in: scaleX, stagger 0.06, power3.out 0.25 |
| 6.30–6.60 | Check seal stamps on at (1685, 790): Ø110, `#7E43F6` fill, white 10 px check. Scale 1.6→1, back.out(2.2) 0.30 |
| 6.35 | SPARK at the seal |
| 6.30 | STATUS "Paid". Done 6.65 |
| 6.65–7.65 | Hold |
| 7.60 | Clip on |
| 7.65 | TEXT_OUT |
| 7.65–8.10 | Box, slip, ring and tag move x −760, power3.in 0.45 |
| 7.65–8.15 | BATON → "declined". Its fill recolours to `#FF4D00` during 7.90–8.15 |

**Do not show:** a shipping row, changing contents or a stock check.

#### Beat 3: T3, card declined, then paid (FREE), 8.10–11.70

**Card:**
- 440×280, radius 28, centred (1440, 600). `#7E43F6` fill with an 8 px ink outline.
- A `#FFE3D5` chip, 72×56, at (1290, 570).
- Four groups of four white Ø10 dots at y 690.
- A white contactless glyph at (1600, 540).
- No digits.

| Time | Action |
|---|---|
| 8.10 | IRIS `#F2F2F2`. Tick 3 becomes active |
| 8.10–8.65 | The card pushes in: x +700→0, rotation 10→0°, expo.out 0.55. The clip turns off at 8.70 |
| 8.15 | HEAD_IN for line 1 only, "Card [declined].": white text on the orange baton. Landed 8.70 |
| 8.70–9.15 | The card shakes: CustomWiggle x ±22 px, 5 wiggles, 0.45 |
| 8.75–9.05 | An X badge pops at (1660, 460): Ø100 `#FF4D00` with a 6 px ink ring and a white 10 px X. back.out(2.2) 0.30 |
| 8.80 | SUB_IN "Email sent right away" |
| 9.00–9.40 | An envelope flies out on a MotionPath from (1440, 600) through (1300, 300) to (1215, 400), scaling 0.4→1, power2.out 0.40. It is 200×140, white with an ink outline and a tint flap |
| 9.30–9.50 | The flap flips (scaleY 1→−1) to show a violet check |
| 9.30–9.70 | A retry ring draws around the card: r 280, 16 px `#7E43F6`, to 85% plus an arrowhead. power2.inOut 0.40 |
| 9.40–9.75 | Chip **"Stripe · retry 1 of 3"** pops at (1440, 930): white fill, 3 px ink ring, ink text. back.out(1.6) 0.35 |
| 9.45 | Line 2 "Then [paid]." comes in, expo.out 0.50. Landed 9.95 |
| 9.50–9.95 | BATON "declined" → "paid", recolouring `#FF4D00`→`#7E43F6`. "declined" turns white→ink during 9.50–9.65 |
| 9.80–10.10 | The badge morphs X→check (MorphSVG) and its fill turns orange→violet, power3.inOut 0.30 |
| 9.90 | SPARK at the badge |
| 9.95 | STATUS "Recovered". Done 10.30 |
| 10.30–11.25 | Hold |
| 11.25 | TEXT_OUT |
| 11.25–11.60 | The card drops y +360, power3.in 0.35. The envelope, ring and chip scale to 0, power3.in 0.30 |
| 11.25–11.70 | **Match cut:** the violet badge moves to (1440, 620), scales ×6.8 and recolours to `#EFE8FE`, while its check scales to 0. power3.in 0.45. At 11.70 it is swapped for the beat 4 disc |
| 11.25–11.75 | BATON → "unlocked" |

**Do not show:** smart or unlimited retries, PayPal or Paddle retries, a decline reason, or any guarantee.

#### Beat 4: T4, members area unlocked (FREE), 11.70–14.70

**Objects:**
- **Arch door frame:** 340×460 with an r 170 arched top, white fill, 8 px ink outline, base at y 900, centred x 1440.
- **Door leaf:** `#FFE3D5`, inset 24 px.
- **Interior:** `#7E43F6`, revealed when the door opens.
- **Padlock:** body 220×180, radius 36, `#7E43F6` with ink outline, centred (1440, 720). White keyhole. Ink shackle with a 32 px stroke.
- **Key:** `#FF4D00` bow ring Ø100 with a white hole, a blade with 2 teeth, 6 px ink outline.

| Time | Action |
|---|---|
| 11.70 | Disc is in place from the match cut. Tick 4 becomes active |
| 11.75 | HEAD_IN "Members area," / "[unlocked].". Landed 12.45 |
| 11.75–12.25 | Door grows: scaleY 0→1 from the bottom, expo.out 0.50 |
| 11.95–12.45 | Padlock drops in: y −320→0, back.out(1.6) 0.50 |
| 12.20 | SUB_IN "No separate membership plugin" |
| 12.25–12.70 | Key flies on a MotionPath from (1800, 980) into the keyhole at (1440, 735), rotation 45→0°, power3.out 0.45 |
| 12.70–12.90 | Key turns 90°, back.out(1.6) 0.20 |
| 12.85–13.15 | Shackle pops: y −50, rotation −12°, back.out(2.4) 0.30 |
| 12.90 | SPARK at the shackle |
| 12.95–13.35 | Lock and key drop y +520, power3.in 0.40 |
| 12.95–13.35 | Door leaf opens: scaleX 1→0.12 from the left hinge, power3.inOut 0.40 |
| 13.20 | Three white strokes pop inside the violet interior |
| 12.95 | STATUS "Granted". Done 13.30 |
| 13.20–13.55 | Chip "/members/" (ink fill, white text) pops at (1440, 390), back.out(1.6) 0.35 |
| 13.30–14.25 | Hold |
| 14.25 | TEXT_OUT |
| 14.25–14.65 | Door: scaleY to 0 from the bottom, power3.in 0.40. Chip scales to 0 |
| 14.25–14.75 | BATON → "day 7" |

**Do not show:** Login Limit, or any claim about paused or on-hold access.

#### Beat 5: T5, drip (FREE), 14.70–17.70

**Objects:**
- **Track:** a 12 px ink line from x 1220 to 1660 at y 780, round caps.
- **Books** centred at y 650:
  - Book 1 at x 1220: an open spread, violet covers with white pages.
  - Book 2 at x 1440 and book 3 at x 1660: closed, 120×160, violet, each with a Ø56 padlock badge.
- **Node chips**, centred at x 1220, 1440 and 1660, y 810–890:
  - "Signup": white fill, ink ring.
  - "Day 7": `#EFE8FE` fill, `#6A38CF` text.
  - "Day 14": white fill, ink ring.

| Time | Action |
|---|---|
| 14.70 | IRIS `#FFE3D5`. Tick 5 becomes active |
| 14.75 | HEAD_IN "Lesson 2 opens" / "on [day 7].". Landed 15.45 |
| 14.80–15.20 | Track draws: DrawSVG 0→100%, power2.out 0.40 |
| 14.90–15.30 | Books pop in, back.out(1.8) 0.35, stagger 0.08 |
| 15.00–15.35 | Node chips pop in, stagger 0.08 |
| 15.25 | SUB_IN "Ebooks drip into Downloads too" |
| 15.40–15.90 | A violet fill (20 px) runs from x 1220 to 1440, power2.inOut 0.50. A Ø36 ink "clock" dot rides at its front |
| 15.90–16.30 | Book 2's padlock pops off (y −70, rotation 25°, 0.10), then falls y +500, power2.in 0.30 |
| 15.90–16.20 | Book 2 opens (MorphSVG closed→open), power3.inOut 0.30. The "Day 7" chip turns violet with white text over 0.20 |
| 15.95 | STATUS "Dripped". Done 16.30 |
| 16.00 | SPARK at book 2 |
| 16.05–16.40 | An ebook hops out to (1560, 520) and lands with a squash, back.out(3). It is 96×124, white with a folded corner and an orange download arrow |
| — | Day 14 stays locked throughout |
| 16.30–17.25 | Hold |
| 17.25 | TEXT_OUT |
| 17.25–17.55 | Books, ebook, chips and padlocks scale to 0, power3.in 0.30, stagger 0.03. The ink track undraws, power2.in 0.30 |
| 17.25–17.80 | **Match cut:** the violet fill path morphs into a 12-point sparkline from (1180, 815) to (1710, 680). It recolours to `#EFE8FE` and the stroke goes 20→16, power3.inOut 0.55. The clock dot moves to the line's end and becomes `#FF4D00` with a 6 px white ring |
| 17.25–17.75 | BATON → "one glance" |

**Do not show:** LMS or course words, or calendar dates.

#### Beat 6: T8, Home dashboard as objects only (FREE), 17.70–20.70

**Objects:** there is no panel or frame.
- Chip "Current MRR": `#7E43F6` fill, white text, centred (1440, 410).
- **"$10,428"**: Display 700 140 px white, centred x 1440, baseline 610.
- The sparkline from beat 5.
- Bottom row of chips at y 880–960:
  - "99 live": white fill, ink text, Ø18 violet dot, x 1167–1397.
  - "$284 due": `#FFE3D5` fill, `#A13504` text, calendar-days icon, x 1421–1713.

| Time | Action |
|---|---|
| 17.70 | IRIS `#14100B`. Tick 6 becomes active |
| 17.75 | HEAD_IN "Your business," / "in [one glance].". Landed 18.45 |
| 17.85–18.15 | End dot pops, back.out(3) 0.30 |
| 17.90–18.20 | The "Current MRR" chip pops, back.out(1.6) 0.30 |
| 17.95–18.85 | The number masks in (expo.out 0.40) and counts 0→10,428 (power3.out 0.90, snap 1) |
| 18.30 | SUB_IN "MRR, renewals and what needs you" |
| 18.55–18.90 | The two bottom chips pop, back.out(1.6) 0.35, stagger 0.12 |
| 18.90 | STATUS "Tracked". Done 19.25 |
| 18.95 | SPARK at the end dot |
| 19.25–20.25 | Hold. The chips float y ±6 in opposite phase, one cycle |
| 20.25 | TEXT_OUT |
| 20.25–20.55 | Chips scale to 0 (back.in(1.7) 0.25). The number yPercent goes to 100 (power3.in 0.30). The sparkline undraws (power2.in 0.30) |
| 20.25–20.40 | The disc recolours `#14100B`→`#7E43F6` |
| 20.30–20.80 | **Flood:** the disc scales 1→4.7 around (1440, 620), expo.inOut 0.50 |
| 20.25–20.75 | BATON → "free". It recolours to `#FFFFFF` during 20.45–20.75. The marked word text will be violet |
| 20.55–20.85 | Ticks recolour for the flood: done ticks to white, pending ticks to `#AB85F9`. The FREE chip stays white with its ink ring |

**Do not show:** churn, forecast, revenue at risk, refunds, "real-time" or a LIVE pulse.

#### D: Free→Pro divider, 20.70–23.00

**Layout:**
- The whole canvas is violet. White type, 120 px, centred.
- "Start [free]." sits at baseline 540. "Add [Pro] when you grow." sits at baseline 660.
- The marker is white with violet text.

| Time | Action |
|---|---|
| 20.80 | Line 1 words rise, expo.out 0.50, stagger 0.05. Landed 21.30 |
| 20.90 | Line 2 words rise. Landed 21.40 |
| 21.10–21.55 | An ink squiggle (14 px, x 760–1160, y 710–750) draws in, power2.out 0.45 |
| 21.50–21.95 | BATON "free" → "Pro", power3.inOut 0.45. "free" turns violet→white and "Pro" turns white→violet |
| 21.65–21.95 | **The tier chip flat-flips FREE→PRO:** scaleX 1→0 (power2.in 0.15), swap, then 0→1 (power2.out 0.15). On the flood it is white fill with violet text |
| 21.95–22.55 | Hold |
| 22.55–22.90 | All words: yPercent to −110, power3.in 0.35, stagger 0.03. The squiggle undraws |
| 22.55–23.10 | The flood contracts 4.7→1, expo.inOut 0.55, recolouring to `#F2F2F2` during 22.80–23.05. The motifs reappear |
| 22.55–23.05 | BATON → "Cancel". It recolours white→`#FF4D00` during 22.75–23.00 |
| 22.75–23.00 | The PRO chip becomes `#7E43F6` with white text. Ticks return to their normal colours |

#### Beat 7: T6, save offer (PRO), 23.00–26.20

**Objects:**
- **Reason chip** "Too expensive": white fill, 3 px ink ring, at (1440, 390).
- **Violet heart:** about 340 px, centred (1440, 660), split into two halves along zigzag inner edges.
- **Offer tag:** `#FF4D00`, 280×170, radius 24.
  - "20% off" in Display 700 60 px white.
  - Under it, "3 renewals" in Sans 700 44 px white.

| Time | Action |
|---|---|
| 23.00 | Tick 7 becomes active |
| 23.05 | HEAD_IN for line 1 only, "[Cancel]?": white text on the orange baton. Landed 23.55 |
| 23.10–23.50 | The reason chip drops in: y −200→0, back.out(1.6) 0.40 |
| 23.45 | SUB_IN "Offers match the cancel reason" |
| 23.50–23.95 | The heart halves enter from x ±260 and stop at ±60 px apart, rotated ∓10°, expo.out 0.45 |
| 23.75–24.15 | The tag flat-flips in between the halves: scaleX 0→1, power3.out 0.40 |
| 24.00 | Line 2 "They [stayed]." comes in. Landed 24.50 |
| 24.05–24.50 | BATON "Cancel" → "stayed", recolouring to `#7E43F6`. "Cancel" turns to ink |
| 24.05–24.45 | The halves lock together (back.out(1.8) 0.40) and the crack undraws (0.25). The tag tucks to (1580, 790) at 12° and scale 0.8, power3.inOut 0.40 |
| 24.30–24.65 | A `#FFE3D5` 12-point burst, Ø520, opens behind the heart: back.out(1.8) 0.35 |
| 24.35 | SPARK at the heart's top-right |
| 24.45 | STATUS "Saved". Done 24.80 |
| 24.80–25.75 | Hold. The heart beats (1→1.04→1, sine.inOut 0.30) at 24.95 and 25.40 |
| 25.75 | TEXT_OUT |
| 25.75–26.05 | The reason chip moves y −160 and scales to 0. The burst and tag scale to 0 |
| 25.75–26.20 | The heart scales to 0.25 and moves to (1440, 470), power3.inOut 0.45. It will become the bundle bow |
| 25.75–26.25 | BATON → "whole kit" |

**Do not show:** a blocked cancel, locks or trap imagery, a pause length, or the name "Retention Flow".

#### Beat 8: T7, bundle (PRO), 26.20–29.20

**Products** stand on a base line at y 860:
- Left, centred x 1290: candle jar, 150×170, `#FFE3D5` with a violet lid.
- Centre, x 1440: tea tin, 170×240, violet with a white band.
- Right, x 1590: bottle, 130×210, white with a violet cap.

| Time | Action |
|---|---|
| 26.20 | IRIS `#EFE8FE`. Tick 8 becomes active |
| 26.25 | HEAD_IN "Sell the" / "[whole kit].". Landed 26.95 |
| 26.25–26.75 | The products arrive from the left, from below and from the right (±500 px) and land touching, expo.out 0.50, stagger 0.06 |
| 26.70 | SUB_IN "Every item renews together" |
| 26.80–27.20 | A 36 px `#FF4D00` ribbon wraps across all three at y 740–776: scaleX 0→1 from the left, power3.inOut 0.40 |
| 27.05–27.30 | A vertical ribbon band crosses the tin: scaleY 0→1, power3.out 0.25 |
| 27.15–27.45 | The heart morphs into a bow (MorphSVG), recolours violet→orange and moves to (1440, 605), power3.inOut 0.30 |
| 27.30–27.80 | A renewal ring draws: a 300° arc with its gap at the bottom, r 290 around (1440, 650), 16 px violet with an arrowhead. DrawSVG, power2.inOut 0.50 |
| 27.40–27.75 | Chip **"1 subscription"** (white fill, 3 px ink ring) pops at (1440, 930), inside the ring gap: back.out(1.6) 0.35 |
| 27.40 | STATUS "Bundled". Done 27.75 |
| 27.45 | SPARK at the bow |
| 27.75–28.75 | Hold. The kit sways ±2°, one cycle |
| 28.70 | Clip on |
| 28.75 | TEXT_OUT |
| 28.75–29.15 | The whole kit moves x +700, power3.in 0.40 |
| 28.75–29.25 | BATON → "pays renewals" |

**Do not show:** a quantity stepper, a struck-through price, or a customer picking items.

#### Beat 9: T9, store credit (PRO), 29.20–32.20

**Objects:**
- **Wallet:** 320×230, radius 40, violet, with a `#6A38CF` flap, a Ø40 `#FF4D00` clasp and an ink outline. Centred (1330, 650).
- **Balance chip:** "Balance $65.00", white fill, 3 px ink ring, at (1330, 850).
- **Renewal slip:** 200×260, white, zigzag bottom, with a violet refresh-cw glyph and three `#E7E7E7` bars. At (1620, 560).
- **Tokens:** Ø70, `#FF4D00`, ink outline, white star glyph. **They carry no "$".**

| Time | Action |
|---|---|
| 29.20 | IRIS `#FFE3D5`. Tick 9 becomes active |
| 29.25 | HEAD_IN "Store credit" / "[pays renewals].". Landed 29.95 |
| 29.25–29.75 | The wallet slides in: x −600→0, expo.out 0.50. The clip turns off at 29.80 |
| 29.55–29.90 | The balance chip pops, back.out(1.6) 0.35 |
| 29.70 | SUB_IN "Refunds can return as credit" |
| 29.75–30.10 | The slip pops, back.out(1.6) 0.35 |
| 29.85–30.05 | The flap opens to −30°, power2.out 0.20 |
| 29.90 / 29.98 / 30.06 | Three tokens arc on MotionPaths from the wallet to the slip, peaking at y 420, power2.inOut 0.40. They land at 30.30 / 30.38 / 30.46 with a squash |
| 30.15–30.50 | The balance counts $65.00→$20.00, power2.out 0.35, snap 0.01 |
| 30.45–30.75 | A check seal stamps on the slip at (1690, 660): scale 1.6→1, back.out(2.2) 0.30 |
| 30.45 | STATUS "Applied". Done 30.80 |
| 30.50 | SPARK at the slip |
| 30.80–31.75 | Hold |
| 31.75 | TEXT_OUT |
| 31.75–32.10 | The wallet and chip drop y +420, power3.in 0.35. The tokens and seal scale to 0 |
| 31.75–32.20 | **Match cut:** the slip moves to (1260, 620) and scales 1→1.15, power3.inOut 0.45, while its glyph scales to 0. It becomes the refund receipt |
| 31.75–32.25 | BATON → "Why" |

**Do not show:** cash, withdrawals, guests or checkout use.

#### Beat 10: T10, refunds, then churn risk (PRO), 32.20–37.00

**Half A objects:**
- The receipt at (1260, 620).
- A `#FF4D00` 16 px return arrow arcing over the receipt, from (1380, 450) to (1140, 450), pointing left.
- Three bars starting at x 1420, each 36 px tall and fully rounded:
  - violet, 300 long, at y 520
  - orange, 204 long, at y 590
  - ink, 126 long, at y 660

**Half B objects:**
- **Gauge:** a semicircle centred (1440, 760), r 250, with 60 px bands and a 6 px ink outline.
  - 0–39: `#EFE8FE`
  - 40–69: `#FFE3D5`
  - 70–100: `#FF4D00`
- **Needle:** ink, 200 long. The pivot is a Ø56 ink disc with a Ø20 white centre.
- **"72":** Display 700 140 px ink, centred x 1440, baseline 900.
- **Chip "High":** `#FF4D00` fill, white text, at (1700, 600).

| Time | Action |
|---|---|
| 32.20 | IRIS `#F2F2F2` from (1260, 620). Tick 10 becomes active |
| 32.25 | HEAD_IN "[Why] money" / "goes back.". Landed 32.95 |
| 32.45–32.90 | The return arrow draws, power2.inOut 0.45 |
| 32.65 | SUB_IN "By product, reason and timing" |
| 32.70–33.20 | The bars grow, power3.out 0.40, stagger 0.08 |
| 33.20–34.10 | Hold. Half A is readable for 1.15 s |
| 34.10–34.45 | Headline A and sub A go out (power3.in 0.35). The receipt and arrow drop y +420 |
| 34.10–34.65 | The bars morph into the gauge segments (MorphSVG, filled band paths): violet→`#EFE8FE`, orange→`#FFE3D5`, ink→`#FF4D00`. power3.inOut 0.55 |
| 34.10–34.60 | BATON "Why" → "0–100" |
| 34.50 | HEAD_IN "Churn risk," / "scored [0–100].". Landed 35.20 |
| 34.60–34.85 | Pivot and needle pop, back.out(2) 0.25 |
| 34.65–35.25 | The needle swings 0→72 with a CustomEase overshoot (to about 80) and settles. "72" masks in and counts 0→72, snap 1 |
| 34.90 | SUB_IN "Scores work without AI" |
| 35.15–35.45 | The "High" chip pops, back.out(1.6) 0.30 |
| 35.20 | STATUS "Flagged". Done 35.55 |
| 35.25 | SPARK at the needle tip (1567, 606) |
| 35.55–36.55 | Hold |
| 36.55 | TEXT_OUT |
| 36.55–36.85 | The needle scales to 0 about its pivot. "72" goes out downward. The chip scales to 0 |
| 36.55–37.00 | **Match cut:** the three segments morph into three pieces of one polyline running (1180, 815) → (1260, 790) → (1340, 770) → (1420, 720), recolouring to white. power3.inOut 0.45 |
| 36.55–37.05 | BATON → "12 months" |

**Do not show:** customer names, an "AI required" badge, or any guarantee.

#### Beat 11: T11, forecast (PRO), 37.00–40.00

| Time | Action |
|---|---|
| 37.00 | IRIS `#14100B` from (1300, 770). Tick 11 becomes active. The white measured line (16 px) settles |
| 37.05 | HEAD_IN "Forecast MRR" / "[12 months] out.". Landed 37.75 |
| 37.40–37.70 | The "Today" node pops at (1420, 720): Ø44 white with a Ø16 ink centre, back.out(2) 0.30 |
| 37.50–37.80 | The "Today" chip (white fill, ink text) pops at (1420, 810) |
| 37.50 | SUB_IN "A range, not one number" |
| 37.60–38.20 | The range band wipes open from the left, power3.inOut 0.60. It is a flat `#39225D` polygon from the Today node to x 1720, with the upper edge rising to y 480 and the lower edge to y 690 |
| 37.80–38.30 | A dashed `#FF4D00` line (12 px, dashes 24/18) draws to (1720, 585), power2.inOut 0.50. It is revealed by a DrawSVG mask |
| 38.20 | STATUS "Projected". Done 38.55 |
| 38.25–38.60 | The end node pops: Ø36 orange with a 6 px white ring, back.out(3). Chip **"+8.5%"** (`#FFE3D5` fill, `#A13504` text) pops at (1640, 440), back.out(2) 0.35 |
| 38.30 | SPARK at the end node |
| 38.55–39.55 | Hold. The band's upper edge breathes ±4 px, one cycle |
| 39.55 | TEXT_OUT |
| 39.55–39.90 | The band scales Y to 0, the lines undraw and the chips scale to 0, power3.in 0.35 |
| 39.60–40.00 | The disc scales to 0, power3.in 0.40 |
| 39.60–39.90 | The PRO chip scales to 0, back.in(1.7) 0.30 |
| 39.55–40.15 | BATON → the end-card "keep customers" (112 px), power3.inOut 0.60 |

**Do not show:** automatic or always-on claims, guaranteed figures, or any horizon other than 6, 12 or 24 months.

---

### 4. End card and loop seam, 40.00–43.80

**Layout:**
- White canvas.
- "ArraySubs" in Display 700 72 px ink, centred at baseline 330.
- Tagline at 112 px, centred, on baselines 520, 623 and 726:
  - "The WooCommerce"
  - "subscription plugin"
  - "built to [keep customers]."
- Pill row centred at y 816–904, with 24 px gaps:
  - **"Start free"**: `#7E43F6` fill, white text.
  - "No cut of your sales": `#FFE3D5` fill, `#A13504` text.
  - "1-minute setup": white fill, 4 px violet ring, `#6A38CF` text.
- No price and no "$0" anywhere.

| Time | Action |
|---|---|
| 40.00–40.40 | Tick flourish: each tick scales 1→1.5→1 (back.out(2) 0.30), staggered 0.035 from the left |
| 40.05–40.60 | The wordmark's characters rise, expo.out 0.50, stagger 0.02 |
| 40.10 | The tagline lines rise, each 0.08 after the previous one, expo.out 0.55, word stagger 0.04. Landed 40.70. "keep customers" rises white into the baton |
| 40.15–40.60 | The squiggle draws in at x 200–420, y 990–1050, power2.out 0.45 |
| 40.20–40.60 | The starburst (Ø147) pops behind the baton's right end: rotation −30→0°, back.out(1.8) 0.40 |
| 40.70 | Heartbeat and dot wave |
| 40.75–41.15 | The pills pop: scale 0.6→1 and y +20→0, back.out(1.6) 0.40, stagger 0.08 |
| 41.15–42.80 | Hold. The tagline is readable for 2.10 s |
| 42.80–43.20 | Ticks drain to `#DCDCDC` from right to left, 0.20 each, stagger 0.02 |
| 42.80–43.15 | The wordmark, "The WooCommerce", "subscription plugin" and "built to" go out: yPercent to −110, power3.in 0.35, stagger 0.04 |
| 42.80–43.10 | The pills scale to 0 from right to left, back.in(1.7) 0.30, stagger 0.04 |
| 43.05–43.75 | The **KC group** (baton, "keep customers" and the ".") moves from the end-card rect to the frame-0 rect: scale 1→1.5, centred on x 960, baseline 700, power3.inOut 0.70. In the same tween the squiggle glides to x 230–450, y 870–930, and the starburst moves to its frame-0 size (Ø220) and position. Its rotation stays at 0 |
| 43.75–43.80 | The frame matches frame 0. The ring reaches 360° at 43.80 |

**Seam test:** frame 1313 should differ from frame 0 only by ring rotation of 0.27°. Every other tween must be resolved by 43.75.

**Story loop:** "built to keep customers" (end) leads into "keep customers" (poster), then "Built to keep customers" (hook).

---

### 5. Readability

| Beat | Headline readable | Status pill readable |
|---|---|---|
| H | "Built to": 0.65→1.75 = 1.10 s. "keep customers" is visible from 43.75 through 1.75 | — |
| 1 | 2.85→4.65 = 1.80 | 1.00 |
| 2 | 5.85→7.65 = 1.80 | 1.00 |
| 3 | Line 1: 2.55. Line 2: 9.95→11.25 = 1.30 | 0.95 |
| 4 | 12.45→14.25 = 1.80 | 0.95 |
| 5 | 15.45→17.25 = 1.80 | 0.95 |
| 6 | 18.45→20.25 = 1.80 | 1.00 |
| D | Line 1: 1.25. Line 2: 1.15 | — |
| 7 | Line 1: 2.20. Line 2: 24.50→25.75 = 1.25 | 0.95 |
| 8 | 26.95→28.75 = 1.80 | 1.00 |
| 9 | 29.95→31.75 = 1.80 | 0.95 |
| 10 | A: 1.15. B: 35.20→36.55 = 1.35 | 1.00 |
| 11 | 37.75→39.55 = 1.80 | 1.00 |
| E | 40.70→42.80 = 2.10 | Pills: 1.65 |

**Rules met:**
- Each headline line is 14 characters or fewer.
- Each sub is 34 characters or fewer.
- Only one idea is on screen at a time.
- Each object's main action finishes before its pill lands.

**Display sizes:**
- At 340 px wide, the headline renders at about 21 px. At 800 px it is about 50 px.
- Subs and pills render at about 22 px at 800 px wide.

---

### 6. Fact and tier check

Every line is taken from the fact sheet. Today I re-checked `homepage.ts:84`, `:88`, `:91` and `:498`, plus several doc lines: retry delays (`retry-failed-renewal-payments.md:37`), 0–100 scores (`find-subscribers-at-risk-of-churning.md:30`), "Conservative to optimistic" (`forecast-recurring-revenue.md:103`), "in one glance" (`read-the-home-dashboard.md:32`) and the reward repeating each renewal (`add-box-discount-tiers-and-free-gifts.md:30`).

| Beat | On-screen claim | Support |
|---|---|---|
| 1 (FREE) | Gifts unlock as the box fills. "15% off" plus a gift | T2: "Free gifts unlock as the box fills". Discount tiers. Hero row "15% off and a gift unlocked" |
| 2 (FREE) | A new order every renewal. The tag stays on | T1: "Every renewal creates a new WooCommerce order". T2: "Reached reward repeats on every renewal". No shipping row |
| 3 (FREE) | Email sent right away. "Stripe · retry 1 of 3". Then paid | T3: email sent right away. Up to 3 retries on Stripe and Mollie. "Scheduled retry 1 of 3". No PayPal or Paddle retry claim |
| 4 (FREE) | No separate membership plugin. /members/ | T4 facts and the URL rule example |
| 5 (FREE) | Lesson 2 on day 7. Ebooks in Downloads. Timing from Signup | T5. Day 14 is shown locked. No LMS |
| 6 (FREE) | One glance: MRR, renewals and what needs you. $10,428, 99 live, $284 due | T8. Only Free Home items appear |
| D | "Start free. Add Pro when you grow." | `homepage.ts:498` |
| 7 (PRO) | Offer matches the cancel reason. "Too expensive". 20% off for 3 renewals | T6. No trap imagery. Uses the Cancellation Flow naming |
| 8 (PRO) | Whole kit, one subscription. Every item renews together | T7. No stepper and no picking |
| 9 (PRO) | Credit pays renewals. Refunds can return as credit. Tokens show no "$" | T9. Credit stays in the store, never as cash |
| 10 (PRO) | Why money goes back: by product, reason and timing. Churn scored 0–100. Scores work without AI | T10 |
| 11 (PRO) | MRR forecast 12 months out. A range, not one number. +8.5% | T11. No claim that it runs automatically or is guaranteed |
| E | Tagline. "Start free". "No cut of your sales". "1-minute setup" | `homepage.ts:84`, `:88`, `:91` |

- **Prices:** no Pro price, "from" price or savings figure appears anywhere, and no amount sits on a CTA. Store amounts appear only inside objects.
- **Status words:** Paid, Rewarded, Recovered, Granted, Dripped and Saved are the homepage's event words. The other pill words are event words too. None of them is presented as a subscription status.

---

### 7. Build notes (HTML + CSS + GSAP 3.15.1, seekable)

**Fonts:**
- `@font-face` from `/Users/emran/Local Sites/wp-test2/app/public/wp-content/plugins/web-content/node_modules/@fontsource-variable/funnel-display/files/funnel-display-latin-wght-normal.woff2`
- and `.../funnel-sans/files/funnel-sans-latin-wght-normal.woff2`.
- Run SplitText and the baton rect measurement only after `await document.fonts.ready`.

**Timeline:**
- One paused master timeline. End it with `tl.set({}, {}, 43.80)` so its duration is exact.
- Render frames f = 0…1313 with `tl.seek(f/30, false)`.
- No CSS animations or transitions, no `Math.random`, no Physics2D.
- Count-ups tween a proxy object and format it in `onUpdate`, so seeking gives the same result every time.

**Plugins:** load SplitText, DrawSVGPlugin, MorphSVGPlugin, MotionPathPlugin, CustomEase and CustomWiggle from `/Users/emran/Local Sites/wp-test2/app/public/wp-content/plugins/web-content/node_modules/gsap/dist/`. Flip is not needed, because the baton is tweened directly to rects measured ahead of time in stage coordinates.

**MorphSVG pairs:**
- X → check
- book closed → open
- heart → bow
- 3 bars → 3 gauge bands (filled paths)
- 3 gauge bands → 3 line pieces
- drip fill → sparkline

**Dashed line:** reveal it through a solid-stroke mask drawn with DrawSVG, because DrawSVG needs the stroke dash array for itself.

**Icons:** Lucide `__iconData.node` from `/Users/emran/Local Sites/wp-test2/app/public/wp-content/plugins/web-content/node_modules/lucide-react/dist/esm/icons/`. All 21 icons used were checked and are present, including gift, truck, mail-check, key-round, book-open, chart-line, heart-handshake, boxes, wallet, gauge, trending-up, calendar-days, refresh-cw, star and download.

**Width check:** after layout, assert that every headline line is ≤ 872 px wide, every sub is ≤ 872 px, and the divider and end lines are ≤ 1520 px. Auto-fit the headline down to a 110 px floor. If a line is still too wide, the lead must shorten that copy.

**Poster:** frame 0, the centred violet pill reading "keep customers."