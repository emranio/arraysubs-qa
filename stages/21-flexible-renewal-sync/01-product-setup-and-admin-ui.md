# Stage 21 — Task 01: Product Setup & Admin UI (Segment Picker)

| Key | Value |
|---|---|
| Stage | Flexible Renewal Sync |
| Module | FlexibleRenewalSync (product editor UI + meta persistence) |
| Plugin Coverage | Pro (renders inside the free plugin's Subscription panel) |
| Estimated Time | 35 min |
| Depends On | Stage 00 baseline; ArraySubsPro build containing FlexibleRenewalSync deployed |

## Objective
Create the stage's test products and verify the product-editor UI end to end: section placement (immediately **after** "Different Renewal Price"), the dual-thumb range picker over the colored segment bar, the day bubbles, per-segment toggles with the partition rule, live exclusivity hiding (Different Renewal Price / Trial / Lifetime), cycle rescaling when the billing schedule changes, and meta persistence across saves. **This task gates the stage** — if the section does not render, stop and file an issue.

## Pre-conditions
- Logged in as Administrator.
- Global ArraySubs setting **Sync renewals to next billing cycle** is OFF.
- DevTools console open for the whole task (any console error = FAIL).

## Test Data
Create (or confirm) these products — all **Virtual**, no signup fee, no trial:
- `FRS Monthly 30` — Simple, subscription, **$30.00 / every 1 month**.
- `FRS Weekly 20` — Simple, subscription, **$20.00 / every 1 week**.
- `FRS Variable` — Variable, one attribute (e.g. Plan: Silver / Gold), both variations subscriptions, monthly, $30.00 / $50.00.

## Sub-Tasks

### Sub-Task 1.1 — Section presence and placement
**Steps:**
1. Edit `FRS Monthly 30` → Product data → **Subscription** tab.
2. Locate the checkbox **Flexible Renewal Sync to Next Billing Cycle**.

**Expected Result:**
- The section renders **directly after** the "Different Renewal Price" section and **before** any other pro sections (e.g. Fixed Period Membership) and Subscription Shipping.
- Unchecked by default; the configuration area below it is hidden until checked.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 1.2 — Picker anatomy with all three segments active
**Steps:**
1. Tick the checkbox.
2. Inspect the picker and legend.

**Expected Result:**
- A single horizontal bar colored **green → blue → red** with **two draggable thumbs** on it and a `1 … 30` scale beneath.
- A day-number bubble sits above each thumb showing its current boundary day; defaults for a fresh monthly product are **10 / 20**.
- Legend shows three rows, each with a small colored toggle switch, a day range, and a label: `1 - 10  Full amount` (green), `11 - 20  Prorate amount` (blue), `21 - 30  Charge full for next billing cycle` (red).
- Toggles are compact (pill ~34 px wide) — not stretched or overlapping the layout.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 1.3 — Dragging and clamping
**Steps:**
1. Drag thumb 1 right toward thumb 2; drag thumb 2 left toward thumb 1.
2. Drag thumb 2 to the far right; use keyboard arrows on a focused thumb.

**Expected Result:**
- Thumbs move independently — moving one never moves the other.
- Thumb 1 stops at `thumb2 − 1`; thumb 2 stops at `thumb1 + 1` and at `29` (cycle end − 1), so every segment keeps ≥ 1 day.
- Bar colors, bubbles, and legend ranges update live while dragging; arrow keys nudge by exactly 1 day.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 1.4 — Segment toggles and the partition rule
**Steps:**
1. Toggle **Prorate amount** off.
2. Toggle it back on. Then toggle off **Full amount** and **Charge full for next billing cycle**, attempting to leave zero segments on.

**Expected Result:**
- With Prorate off: blue disappears from the bar, only **one** thumb remains, legend shows `Off` for the prorate row, and the two remaining ranges cover the whole cycle (e.g. `1 - 15` / `16 - 30`).
- Re-enabling restores three segments (re-spaced to thirds).
- The **last** active toggle refuses to turn off; a notice "At least one segment must stay active." appears briefly.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 1.5 — Cycle rescaling on billing-schedule change
**Steps:**
1. With boundaries at 10 / 20, change Billing Period **Month → Year**, then back to **Month**.
2. Change Billing Interval to 2, then back to 1.

**Expected Result:**
- Year: scale becomes `1 … 365`, boundaries rescale proportionally (≈ 122 / 243).
- Back to Month: `1 … 30` and ≈ 10 / 20 again. Interval 2 (month): scale `1 … 60`.
- No console errors during any change.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 1.6 — Live exclusivity hiding
**Steps:**
1. Tick **Different Renewal Price** → observe. Untick.
2. Set **Trial Length** to `7` → observe. Set back to `0`.
3. Set Billing Period to **Lifetime Deal** → observe. Set back to Month.

**Expected Result:**
- Each of the three conditions hides the whole Flexible Renewal Sync section instantly; undoing restores it with its previous state intact (checkbox + boundaries preserved).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 1.7 — Persistence
**Steps:**
1. On `FRS Monthly 30` set boundaries **10 / 20**, all segments active, feature enabled. **Update** the product and reload the editor.
2. Repeat a quick enable + save on `FRS Weekly 20` (defaults 2 / 5 on the 7-day scale).

**Expected Result:**
- After reload: checkbox still ticked, boundaries 10 / 20, ranges `1 - 10 / 11 - 20 / 21 - 30`.
- Weekly product shows the 7-day scale with sane defaults.
- Product meta (`wp post meta list`) shows `_arraysubs_flex_sync_enabled = yes`, `_arraysubs_flex_sync_seg1_end = 10`, `_arraysubs_flex_sync_seg2_end = 20`, all three `_arraysubs_flex_sync_seg*_active = yes`.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 1.8 — Variation editor parity
**Steps:**
1. Edit `FRS Variable` → Variations → expand **Silver**.
2. Confirm the section renders inside the variation (after its Different Renewal Price block), enable it, set boundaries, **Save changes**, reload, re-expand.
3. Repeat Sub-Task 1.6's three exclusivity checks on the variation's own fields.

**Expected Result:**
- Each variation gets its own independent picker (field names indexed per variation).
- Config persists per variation after save/reload; **Gold** stays untouched.
- Variation-level trial / renewal-price / lifetime hide only that variation's section.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

## Regression / Cross-checks
- The "Different Renewal Price" section itself still works (fields show/hide with its checkbox).
- Non-subscription products: the section is not visible when **Subscription** is unticked.
- No new entries in `wp-content/debug.log`.

## Sign-off
- Tester:
- Date:
- Browser & version:
- Site timezone / today's day-of-cycle D:
- Product IDs created (Monthly / Weekly / Variable):
- Notes:
