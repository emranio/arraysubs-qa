# Stage 03 — Task 11: Fixed Period Membership (Pro)

| Key | Value |
|---|---|
| Stage | 03 — Subscription Product Creation |
| Module | Plan Switching / Fixed Period Membership |
| Plugin Coverage | Pro |
| Estimated Time | 30 min |
| Depends On | 03.01, ArraySubs Pro active |

## Objective
Configure a Fixed Period Membership product covering both end-date modes (absolute date + recurring annual cutoff) plus the enrollment window. Verify that the configuration UI only appears when Pro is active and that the storefront product page renders the membership end date and enrollment window text.

The absolute-end-date product uses a **week-out** date so the expiration path is reachable inside this regression cycle. The recurring annual cutoff product still exercises the calendar-aware Pro feature even though renewal won't fire during this run.

## Pre-conditions
- ArraySubs Pro plugin installed and active.
- Stage 03.01 complete.
- Today's date noted (the test references a "week-out" date relative to today, 2026-04-29 unless retested later — that puts the absolute end at `2026-05-06`).

## Test Data
- Product 1 name: `Library Week Pass (Absolute)` — $24.99 / week, end date `2026-05-06` (≈ one week from 2026-04-29).
- Product 2 name: `Academic Year Pass (Recurring)` — $99 / year, recurring cutoff Month `August`, Day `31`. Enrollment opens `2026-08-01`, closes `2026-10-31`.
- Period end behaviour:
  - Product 1: `Expire`.
  - Product 2: `Expire` initially; later switch to `Renew` for the renewal-mode check.

## Sub-Tasks

### Sub-Task 11.1 — Pro-only gating check
**Steps:**
1. With Pro active, create a draft product `Library Week Pass (Absolute)` (Simple, Subscription, $24.99, Period `Week`, Interval `1`).
2. Open the **Subscription** tab and locate the **Fixed Period Membership** section below the standard billing fields.
3. Save and refresh.
4. (If feasible) Temporarily deactivate ArraySubs Pro from **Plugins**, reload the product, and confirm the Fixed Period Membership section disappears.
5. Reactivate Pro before continuing.

**Expected Result:**
- Fixed Period Membership section is visible only with Pro active.
- Without Pro: the section is absent (matches manual: "Fixed Period fields not visible | Pro plugin is not active | Install and activate ArraySubs Pro").

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 11.2 — Configure Absolute end date product
**Steps:**
1. With Pro re-enabled, edit `Library Week Pass (Absolute)`.
2. In Subscription tab, tick **Use fixed end date**.
3. Set **End Date Type** to `Absolute date`.
4. Set **End date** to `2026-05-06` (one week after the regression session starts).
5. Set **At period end** to `Expire`.
6. Leave Enrollment opens / closes empty.
7. Click **Update**.

**Expected Result:**
- Fields persist across reload.
- No validation errors.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 11.3 — Storefront display for Absolute product
**Steps:**
1. Open the storefront product page in incognito.

**Expected Result:**
- Page shows a `Membership ends:` line with `May 6, 2026` (or locale-formatted equivalent).
- No enrollment window line (because both fields were left blank).
- No `Auto-renews into the next membership period` text (since action is Expire).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 11.4 — Configure Recurring annual product with enrollment window
**Steps:**
1. Create `Academic Year Pass (Recurring)` (Simple, Subscription, $99, Period `Year`, Interval `1`).
2. In Subscription tab, tick **Use fixed end date**.
3. Set **End Date Type** to `Recurring annual cutoff`.
4. Set **Month** to `August`, **Day** to `31`.
5. Set **At period end** to `Expire`.
6. Set **Enrollment opens** to `2026-08-01`.
7. Set **Enrollment closes** to `2026-10-31`.
8. **Publish**.

**Expected Result:**
- Saves with no errors.
- Recurring fields persist on reload.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 11.5 — Storefront display for Recurring product
**Steps:**
1. Open the product page in incognito.

**Expected Result:**
- `Membership ends:` line shows the next August 31 occurrence.
- `Enrollment window:` line shows the open and close dates.
- Because today's date (`2026-04-29`) falls before enrollment opens (`2026-08-01`), the product should display as not-yet-purchasable per manual: "When set, customers who try to purchase outside the window will be blocked." Confirm Add-to-cart is blocked or shows an unpurchasable notice.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 11.6 — Switch period-end behaviour to Renew
**Steps:**
1. Edit `Academic Year Pass (Recurring)`.
2. Change **At period end** from `Expire` to `Renew`.
3. **Update**.
4. Reload product page (incognito).

**Expected Result:**
- The `Renew` option is available only for recurring annual type (matches manual: "Only available for the recurring annual type").
- Storefront now shows `Auto-renews into the next membership period`.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 11.7 — Confirm Subscription Length is forced to 0
**Steps:**
1. On either fixed-period product, set Subscription Length to `5` and save.
2. Reload and inspect Subscription Length.

**Expected Result:**
- Subscription Length is reset to `0` automatically (per manual: "the standard length field is cleared to 0 automatically").

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

## Regression / Cross-checks
- Verify the absolute-date product becomes unpurchasable on/after `2026-05-06` (note for QA — flag for time-travel re-test once the regression date passes).
- Confirm fixed-period products do not break the rest of the suite: the Subscription tab still renders core billing fields above the Fixed Period section.

## Sign-off
- Tester:
- Date:
- Browser & version:
- Notes:
