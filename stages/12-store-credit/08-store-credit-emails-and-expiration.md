# Stage 12 — Task 08: Store Credit Emails and Expiration

| Key | Value |
|---|---|
| Stage | 12 — Store Credit (Pro) |
| Module | Store Credit — Emails / Expiration |
| Plugin Coverage | Pro |
| Estimated Time | 30 min |
| Depends On | 12.01–12.07 |

## Objective
Trigger and verify each of the four Store Credit emails: **Credit Added**, **Credit Used**, **Credit Expiring**, and **Credit Expired**. The Added and Used emails are already exercised in 12.02 / 12.05 / 12.06 / 12.07; this task focuses on consolidating that proof and on time-traveling a credit's expiration date so both the Expiring (7-day-out) and Expired (post-expiry) emails fire and the expired amount is removed from the customer's balance.

## Pre-conditions
- 12.01–12.07 complete; **Expiration Period (Days)** is set to `365`.
- All four Store Credit emails are **Enabled** in **WC → Settings → Emails**.
- `cust3@test.local` has a known balance — note it as `pre_task08_balance`.
- A reliable way to time-travel either the credit's `expires_at` meta or the daily 3:00 AM expiration job. Document the method.

## Test Data
- Test credit added in 8.2: `$30.00` with note `Stage 12 expiry test`.
- After time-travel:
  - Expiring email fires when the credit's expiry is 7 days away.
  - Expired email fires after the expiry date passes.
  - Customer's balance drops by the expired amount.

## Sub-Tasks

### Sub-Task 8.1 — Confirm placeholder rendering on Credit Added (regression)
**Steps:**
1. Open the most recent **Store Credit Added** email received by `cust3@test.local` (from 12.07 refund refund or 12.04 purchase).
2. Verify the body.

**Expected Result:**
- `{customer_name}`, `{credit_amount}`, `{new_balance}`, `{credit_source}`, `{my_account_url}` are all rendered with real values.
- No raw `{...}` placeholder strings remain in the email body.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 8.2 — Add a $30 credit destined for expiration
**Steps:**
1. As admin, **ArraySubs → Store Credit → Manage Credits** → load `cust3`.
2. Add `$30.00` with note `Stage 12 expiry test`.
3. Note today's date as `issue_date`.
4. Note the new balance as `pre_expiry_balance`.

**Expected Result:**
- Balance increased by `$30.00`.
- A Credit History row exists with the note and source `Admin Adjustment`.
- A `Store Credit Added` email arrives.
- Internally the credit row's `expires_at` should be `issue_date + 365 days` — confirm via the database or a Custom Fields panel if accessible. Otherwise document the next two steps as the visible verification.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 8.3 — Confirm Credit Used email by spending some of the credit
**Steps:**
1. As `cust3`, place an order using any qualifying subscription product (e.g., the canonical `Standard Weekly` $19.99/week) so credit is applied at checkout.
2. Confirm the order completes with credit applied.
3. Open the customer inbox.

**Expected Result:**
- A **Store Credit Used** email arrives with subject `Store credit used for Order #<order_id>`.
- Body shows correct Credit Applied amount, Remaining Balance, and order link.
- All placeholders render.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 8.4 — Time-travel the credit to T-7 days
**Steps:**
1. Pick ONE method:
   - **Method A — Edit credit row meta:** open the database table or REST endpoint that stores credit transactions, find the `$30` row from 8.2, set `expires_at` to a date exactly 7 days from now.
   - **Method B — Date-mock plugin:** advance simulated date by `(365 - 7)` days so the credit is now T-7 from expiry.
2. Trigger the daily expiration batch job manually:
   - **WP-Admin → Tools → Scheduled Actions** → search for the expiration hook (e.g., `arraysubs_credit_expiration`) → **Run** the action.
   - OR run `wp action-scheduler run --hooks=arraysubs_credit_*` from CLI.

**Expected Result:**
- The expiration job runs without error.
- The job queues a Credit Expiring notification for the `$30` row.
- The email is sent within a minute (the manual confirms the queue → email → inbox path).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 8.5 — Verify Credit Expiring email
**Steps:**
1. Inspect `cust3@test.local`'s inbox for the new email.

**Expected Result:**
- Subject `Your store credit expires soon`.
- Body contains:
  - Expiring Credit `$30.00` (highlighted, often red).
  - Expiration Date matching the simulated `expires_at`.
  - Days Remaining `7 days`.
  - **Shop Now** button linking to the shop page.
- Placeholders rendered: `{customer_name}`, `{expiring_amount}`, `{expires_at}`, `{days_remaining}`, `{shop_url}`, `{my_account_url}` all replaced with real values.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 8.6 — Time-travel past expiry
**Steps:**
1. Advance the same credit's `expires_at` to 1 minute in the past, OR advance the date-mock plugin by 8 days.
2. Re-run the expiration batch job (same way as 8.4).

**Expected Result:**
- The job processes the now-expired credit.
- The customer balance drops by `$30.00`.
- A Credit History row appears with source `Expired`, amount `-$30.00`.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 8.7 — Verify Credit Expired email
**Steps:**
1. Inspect `cust3@test.local`'s inbox.

**Expected Result:**
- Subject `Your store credit has expired`.
- Body contains:
  - Expired Credit `$30.00` (often strikethrough / grayed out).
  - **Visit Our Shop** button.
- Placeholders rendered: `{customer_name}`, `{expired_amount}`, `{shop_url}`, `{my_account_url}`.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 8.8 — Disable an email and confirm it is not sent
**Steps:**
1. **WC → Settings → Emails → Store Credit Added → Manage**.
2. Untick **Enable this email notification** and save.
3. As admin, **Manage Credits** → add `$5` to `cust3`.
4. Wait and refresh the customer inbox.
5. Re-enable Credit Added and save.

**Expected Result:**
- With Credit Added disabled, the customer balance still increases by `$5` and a Credit History row is logged.
- NO Credit Added email is delivered for the $5 add.
- After re-enabling and adding another $5, the email returns.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

## Regression / Cross-checks
- After all time-travel, restore the date-mock plugin to real time (or disable it).
- Confirm `cust3`'s final balance and note it in Sign-off.
- DevTools / debug.log: zero new errors during the expiration job runs.
- Verify the **Store Credit Expiring** flag prevents duplicate emails — re-running the expiration job before the next 7-day mark should NOT send a second Expiring email for the same credit row (we cannot easily reproduce here, but note this expectation).

## Sign-off
- Tester:
- Date:
- Browser & version:
- Time-travel method (A / B):
- Final cust3 balance:
- Notes:
