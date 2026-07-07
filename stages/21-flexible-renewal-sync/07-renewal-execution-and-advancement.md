# Stage 21 — Task 07: Renewal Execution & Date Advancement (Manual + Stripe)

| Key | Value |
|---|---|
| Stage | Flexible Renewal Sync |
| Module | RecurringBilling engine on synced subscriptions |
| Plugin Coverage | Free engine + Pro Stripe delegate |
| Estimated Time | 60 min |
| Depends On | Stage 21 Tasks 02, 03, 04, 05 (uses their subscriptions) |

## Objective
Prove the **second half of the money flow**: when a synced subscription reaches its boundary, the engine generates the invoice/renewal order for the **full recurring amount**, charges it (Stripe off-session) or emails a pay link (manual), and after payment advances `_next_payment_date` to the **next cycle boundary** (not "paid date + 1 month"). For the segment-3 subscription, confirm the skipped boundary truly produces **no invoice, no email, no charge**.

## Pre-conditions
- Subscription IDs recorded from Tasks 02 (manual/full), 03 (manual/prorate), 04 (manual/next_cycle), 05 (Stripe).
- WP-CLI access to the target site for time-traveling `_next_payment_date` (see the stage README — the admin UI intentionally rejects manual edits).
- Mail capture available (site inbox / mail log) to verify the renewal invoice email.

## Test Data
- Time-travel value: yesterday `00:00:00` UTC.
- Expected renewal charge for every subscription: **$30.00** (full recurring; never the prorated amount).

## Sub-Tasks

### Sub-Task 7.1 — Manual subscription (Task 02): invoice → email → pay → advance
**Steps:**
1. `wp post meta update <sub02_id> _next_payment_date "<yesterday 00:00:00>"`.
2. Tools → Scheduled Actions: run the pending `arraysubs_generate_renewal_invoice` for the subscription; then run `arraysubs_process_renewal`.
3. Check the new renewal order, the email, then pay the order from the emailed link (or mark Processing as admin).
4. Re-open the subscription.

**Expected Result:**
- A **pending renewal order for $30.00** exists with `_is_renewal_order = yes`, `_renewal_cycle_number = 2`, `_renewal_scheduled_date` = the time-traveled date.
- The **renewal invoice email** with a working pay link was sent once (not duplicated).
- After payment: Completed Payments = 2, Last Payment ≈ now, and **Next Payment re-anchors to the boundary derived from `_renewal_sync_first_full_renewal_date`** (first synced renewal re-anchors by design; see README) — i.e. a proper 1st-of-month, never `yesterday + 1 month`.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 7.2 — Prorate subscription (Task 03): renewal bills the FULL amount
**Steps:**
1. Repeat 7.1 steps 1–3 for the Task 03 subscription.

**Expected Result:**
- Renewal order total is **$30.00** — the prorated first charge must never leak into renewals.
- After payment the next date is the following boundary.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 7.3 — next_cycle subscription (Task 04): the skipped boundary stays silent
**Steps:**
1. Do **not** time-travel yet. With system time still before `B1`, confirm via Scheduled Actions + WooCommerce → Orders that nothing exists for `B1`.
2. Now time-travel `_next_payment_date` to yesterday and run the two actions.

**Expected Result:**
- Step 1: zero pending renewal orders and zero scheduled jobs before `B2`; no invoice email for `B1` in the mail log.
- Step 2: exactly one renewal order for $30.00 is produced (this simulates `B2`); after payment the next date advances one further boundary.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 7.4 — Stripe subscription (Task 05): off-session auto-charge
**Steps:**
1. Time-travel the Stripe subscription's `_next_payment_date` to yesterday; run invoice + process actions.
2. Watch the renewal order and the Stripe test dashboard.

**Expected Result:**
- The renewal order is **paid automatically** (off-session PaymentIntent, `off_session`/`confirm`) for **$30.00** with no customer interaction; order note records the intent ID.
- Subscription: Completed Payments +1; Next Payment = next boundary; status stays Active.
- No renewal-invoice "please pay" email for the Stripe subscription (it auto-charges).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 7.5 — Second advancement stays on boundaries
**Steps:**
1. For one manual subscription already renewed above, time-travel again and run a **second** renewal cycle end-to-end.

**Expected Result:**
- The next payment lands on the **subsequent calendar boundary** (e.g. Oct 1 after Sep 1) — advancement is boundary-to-boundary every cycle, including across months of different lengths.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

## Regression / Cross-checks
- Every renewal order used the **subscription's stored recurring amount**, not the live product price (raise the product price temporarily and re-check one renewal if time allows; restore afterwards).
- Audit log (ArraySubs → Audits) records the renewal events; `debug.log` unchanged.
- Grace-period behavior out of scope here (covered by Stage 18).

## Sign-off
- Tester:
- Date:
- Browser & version:
- Sub IDs exercised (02/03/04/05):
- Renewal order IDs + amounts:
- Next-payment dates after each advancement:
- Notes:
