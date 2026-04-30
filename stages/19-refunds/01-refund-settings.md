# Stage 19 — Task 01: Refund Settings Persistence

| Key | Value |
|---|---|
| Stage | 19 — Refund Management |
| Module | Settings / Refunds |
| Plugin Coverage | Free |
| Estimated Time | 12 min |
| Depends On | Stage 02 — Settings |

## Objective
Open **ArraySubs → Settings → Refunds**, toggle every option (Refund on Cancellation behaviour, Automatic Gateway Refund, Allow Prorated Refunds, Minimum Refund Amount), save, hard-reload, and verify each option persists exactly as set. Also confirm the in-page UI affordances (radio group, toggles, numeric input) behave correctly.

## Pre-conditions
- Logged in as Administrator.
- ArraySubs core active.
- Browser dev tools open in case a setting fails to persist (so the response payload can be inspected).

## Test Data
- Settings page URL: `/wp-admin/admin.php?page=arraysubs-settings` → **Refunds** tab.
- Minimum Refund Amount value to test: `2.50`.

## Sub-Tasks

### Sub-Task 01.1 — Settings page loads and shows all four controls
**Steps:**
1. Go to **ArraySubs → Settings**.
2. Click the **Refunds** tab.

**Expected Result:**
- Page loads under 2 seconds with no PHP notices/errors visible at the top.
- The following controls are all visible on a single screen:
  - **Refund on Cancellation** — radio group with options labelled exactly: `Allow Immediate Refund`, `Refund at End of Period`, `No Automatic Refund`.
  - **Automatic Gateway Refund** — on/off toggle.
  - **Allow Prorated Refunds** — on/off toggle.
  - **Minimum Refund Amount** — numeric input (currency-aware).
- Default values match the manual: `Allow Immediate Refund`, Auto Gateway Refund **Enabled**, Allow Prorated Refunds **Enabled**, Minimum Refund Amount `0`.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 01.2 — Toggle Refund on Cancellation through all three values
**Steps:**
1. Select **Refund at End of Period**.
2. Click **Save Changes**. Wait for the success toast.
3. Hard-reload the page (Ctrl+Shift+R / Cmd+Shift+R).
4. Verify **Refund at End of Period** is still selected.
5. Repeat with **No Automatic Refund** — save, reload, verify.
6. Repeat with **Allow Immediate Refund** — save, reload, verify (returns to default for downstream tasks).

**Expected Result:**
- After each save, a success toast appears (`Settings saved` or equivalent).
- After each hard-reload, the previously selected radio is still selected — no value snap-back, no silent reset to default.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 01.3 — Toggle Automatic Gateway Refund off and on
**Steps:**
1. Disable **Automatic Gateway Refund** (toggle off).
2. Save Changes.
3. Hard-reload the page and confirm the toggle is still off.
4. Re-enable the toggle.
5. Save Changes and hard-reload — confirm it is on again.

**Expected Result:**
- The toggle persists across reloads in both states.
- No console errors thrown when toggling.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 01.4 — Toggle Allow Prorated Refunds off and on
**Steps:**
1. Disable **Allow Prorated Refunds**.
2. Save Changes, hard-reload, confirm off.
3. Re-enable, save, hard-reload, confirm on.

**Expected Result:**
- Setting persists in both states.
- (Cross-check that downstream Task 02 will still find the prorated refund affordance only when this is enabled — that verification belongs to Task 02 itself, but make a note here if the UI in admin elsewhere immediately changes after this toggle.)

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 01.5 — Set Minimum Refund Amount to 2.50
**Steps:**
1. Set **Minimum Refund Amount** to `2.50`.
2. Save Changes.
3. Hard-reload and confirm the field still reads `2.50` (or the locale-formatted equivalent like `2,50` if site uses comma decimal).
4. Set the value back to `0` and save (so downstream tasks have a clean default).

**Expected Result:**
- Numeric value persists.
- Negative numbers, alphabetic characters, and very large values (`9999999`) are either rejected by the input or sanitised to a sensible bound — record observed behaviour.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 01.6 — Final state matches Task 02 starting state
**Steps:**
1. Confirm settings now read: Refund on Cancellation = `Allow Immediate Refund`, Auto Gateway Refund = **Enabled**, Allow Prorated Refunds = **Enabled**, Minimum Refund Amount = `0`.

**Expected Result:**
- Stage is left in the documented default so Task 02 can begin without re-configuration.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

## Regression / Cross-checks
- Re-open **ArraySubs → Settings → General Settings** and confirm none of the General settings (grace period, customer actions, etc.) were inadvertently mutated by the Refunds save action.
- Open `/wp-admin/options.php` (or use a settings inspector) and confirm the saved option blob contains the four refund keys with the values you expect — a missing key indicates a save handler is dropping it silently.

## Sign-off
- Tester:
- Date:
- Browser & version:
- Notes:
