# Stage 02 — Task 04: Validation and Error Handling

| Key | Value |
|---|---|
| Stage | General & Toolkit Settings |
| Module | Settings — Validation |
| Plugin Coverage | Both |
| Estimated Time | 25 min |
| Depends On | 02-settings/01-general-settings-each-section.md, 02-settings/02-toolkit-settings.md |

## Objective
Push invalid input into every numeric and required field on **General Settings** and **Toolkit Settings** and confirm the form rejects the submission with a visible error message. Critically, confirm that previously-saved good values are **not** wiped when validation fails. This proves admin error handling is non-destructive.

## Pre-conditions
- Stage 02 Tasks 01–03 passed.
- Both plugins active.
- Logged in as Administrator.
- An export `pre-validation.json` is downloaded as a safety net.

## Test Data
Documented numeric ranges (from `general-settings.md` and `toolkit-settings.md`):

| Field | Section | Range |
|---|---|---|
| Generate Invoice Before Due | General → Grace Period | 1–30 (Hours or Days) |
| Days Active After Due | General → Grace Period | 0–30 |
| Days On-Hold Before Cancel | General → Grace Period | 1–60 |
| Renewal Reminder (Days Before) | General → Email Reminder Schedule | 1–30 |
| Trial Ending Reminder (Days Before) | General → Email Reminder Schedule | 1–30 |
| Expiring Soon Reminder (Days Before) | General → Email Reminder Schedule | 1–60 |
| Default Max Sessions Per User | Toolkit → Multi-Login Prevention (Pro) | minimum 1 |

## Sub-Tasks

### Sub-Task 4.1 — Negative number rejected on Days Active After Due
**Steps:**
1. Open **ArraySubs → Settings → General**.
2. Note the current saved value for **Days Active After Due** (let's call it X).
3. Set the field to `-1`.
4. Click **Save Settings**.
5. Read any error message.
6. Hard-refresh the page.

**Expected Result:**
- An inline validation error is shown next to the field (e.g., "Value must be 0 or higher" or similar).
- The save did not persist `-1`.
- After hard-refresh, the field shows the previous saved value X — not `-1`, not blank.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 4.2 — Out-of-range value rejected on Days On-Hold Before Cancel
**Steps:**
1. Note the current saved value Y for **Days On-Hold Before Cancel**.
2. Set the field to `0` (below the documented minimum of 1).
3. Click **Save Settings**.
4. Read the error.
5. Set the field to `999` (above the documented max of 60).
6. Click **Save Settings**.
7. Read the error.
8. Hard-refresh.

**Expected Result:**
- Both submissions are rejected with an inline error referencing the valid range (e.g., "Must be between 1 and 60").
- After hard-refresh, the saved value is still Y.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 4.3 — Out-of-range Renewal Reminder
**Steps:**
1. Set **Renewal Reminder (Days Before)** to `0`, save, observe error.
2. Set it to `99`, save, observe error.
3. Hard-refresh.

**Expected Result:**
- Both submissions show an inline error (range 1–30).
- The previous saved value is preserved through the failed saves.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 4.4 — Letters and special characters in numeric field
**Steps:**
1. In **Days Active After Due**, type `abc` (most browsers will block letters in `<input type="number">` but the back-end must still defend).
2. If the browser allowed the value, click **Save Settings**.
3. If the browser blocked it, paste `abc` directly via DevTools (set the input value programmatically) and submit.

**Expected Result:**
- Either the browser silently strips the letters, or the back-end returns an inline error: "Please enter a valid number".
- The previously saved value is not lost.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 4.5 — Empty Multi-Login Max Sessions
**Steps:**
1. Open **ArraySubs → Settings → Toolkit**.
2. Toggle **Enable Multi-Login Prevention** to **On**.
3. Clear the **Default Max Sessions Per User** field (delete the value).
4. Click **Save Settings**.

**Expected Result:**
- Inline error: "This field is required" or "Value must be at least 1".
- The toggle remains **On** but the bad save does not persist; previous saved Max Sessions value is restored on hard-refresh.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 4.6 — Restrict wp-admin without Allowed Roles or saved redirect
**Steps:**
1. On Toolkit Settings, toggle **Restrict wp-admin access** to **On**.
2. Clear the **Allowed Roles** multi-select (so no roles other than admin are selected).
3. Set **Redirect Unauthorized Users To** to its default and save.

**Expected Result:**
- The save succeeds — the setting allows zero non-admin roles (admin is implicit), per the manual.
- No spurious "Allowed Roles is required" error is shown (it is not a required field per the docs; document any deviation).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 4.7 — Combined-bad-value submit preserves all good values
**Steps:**
1. Open General Settings.
2. In one save attempt, set:
   - **Days Active After Due** = `-5` (invalid).
   - **Days On-Hold Before Cancel** = `8` (valid).
   - **Renewal Reminder (Days Before)** = `4` (valid).
3. Click **Save Settings**.
4. Read the error and observe which fields are flagged.
5. Hard-refresh.

**Expected Result:**
- An error is shown for Days Active After Due.
- The whole save is rejected (per common WP forms) **or** valid fields save and only the invalid field errors — note which behavior is observed.
- After hard-refresh, no setting holds an invalid value. If the whole save rejected, all three fields revert to prior saved values.
- Previous baseline values are intact — nothing was wiped silently.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 4.8 — Confirm error messages clear when valid value is re-entered
**Steps:**
1. With an inline error displayed (from Sub-Task 4.7), correct **Days Active After Due** to `3`.
2. Click **Save Settings**.

**Expected Result:**
- The inline error is removed.
- The success notice appears.
- All three fields hold their corrected values after hard-refresh.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

## Regression / Cross-checks
- Watch DevTools Network during each invalid submission — confirm the response body includes a structured error message (not a 500).
- `wp-content/debug.log` should not grow during these failed saves; validation must be handled cleanly.
- Re-import `pre-validation.json` at the end if any value strayed from the baseline. Save a fresh `post-stage-02.json` once everything is in the documented baseline state.

## Sign-off
- Tester:
- Date:
- Browser & version:
- Number of fields with broken validation:
- Notes:
