# Stage 12 — Task 06: Auto-Apply on Renewal

| Key | Value |
|---|---|
| Stage | 12 — Store Credit (Pro) |
| Module | Store Credit — Auto-Apply to Renewals |
| Plugin Coverage | Pro |
| Estimated Time | 30 min |
| Depends On | 12.01, 12.02, 12.04, 12.05 |

## Objective
With **Auto-Apply to Renewals** enabled and the customer carrying $100 of credit, time-travel five consecutive weekly renewals of a `Standard Weekly` ($19.99/week) subscription. Confirm each renewal invoice is fully covered by credit, the customer's credit balance drops by $19.99 per cycle, the **Credit Used** email arrives every cycle, and an `Applied to Renewal` debit row is recorded in Credit History each time. After five cycles the balance must be approximately `$0.05` (`$100 − 5 × $19.99 = $0.05`) and the next renewal must fall below the auto-apply threshold.

## Pre-conditions
- 12.01–12.05 complete; **Auto-Apply to Renewals** = On, **Minimum Order Amount** = `5.00`.
- `cust3@test.local` balance entering this task is `$185.01` (carried from 12.05).
- Bring `cust3`'s balance down to a known `$100.00` test value: as admin, **Manage Credits** → search `cust3` → **Deduct** `$85.01` with note `Stage 12 reset for auto-renewal test`.
- The canonical `Standard Weekly` subscription product ($19.99 / week) exists.
- `cust3` has an active `Standard Weekly` subscription. If not, run a fresh checkout (Stripe test mode is already configured, or use BACS / COD) and activate the subscription before time-travel.
- Time-travel method ready: edit `_next_payment_date` post meta or run `wp action-scheduler run --hooks=arraysubs_*`.

## Test Data
- Subscription: `cust3`'s active `Standard Weekly` ($19.99 / week).
- Customer balance before cycle 1: `$100.00`.
- Renewal amount per cycle: `$19.99`.
- Expected balance progression: `$100.00` → `$80.01` → `$60.02` → `$40.03` → `$20.04` → `$0.05`.

## Sub-Tasks

### Sub-Task 6.1 — Reset balance to $100
**Steps:**
1. As admin, **ArraySubs → Store Credit → Manage Credits**.
2. Search `cust3` and load profile.
3. Deduct enough so the balance is exactly `$100.00` (note: balance was `$185.01`, so deduct `$85.01` with note `Stage 12 reset for auto-renewal test`).

**Expected Result:**
- Balance shows `$100.00`.
- A debit row with note `Stage 12 reset for auto-renewal test` is in Credit History.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 6.2 — Confirm an active Standard Weekly subscription exists
**Steps:**
1. **ArraySubs → Subscriptions** and locate the `Standard Weekly` subscription for `cust3`.
2. Note the subscription ID and the current `Next Payment Date`.

**Expected Result:**
- The subscription exists with status **Active**.
- Recurring amount is `$19.99 / week`.
- Note the subscription ID in the Sign-off block.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 6.3 — Document the time-travel approach
**Steps:**
1. Pick ONE method and document it in Sign-off — the same method will be reused across all five cycles:
   - **Method A — Custom Fields edit:** open the subscription detail screen → expand the **Custom Fields** panel (or use the **Screen Options** to enable Custom Fields). Edit `_next_payment_date` to a date 5 minutes in the past (format `YYYY-MM-DD HH:MM:SS`). Save.
   - **Method B — CLI:** SSH to the server and run `wp action-scheduler run --hooks=arraysubs_renewal --due-now` (or the equivalent renewal hook).
   - **Method C — date-mock plugin:** advance the simulated `current_time` by `1 week + 1 hour` per cycle, run cron, then disable the plugin between cycles.
2. After each time-travel, trigger the WP-Cron / Action Scheduler so the renewal runs:
   - **WP-Admin → Tools → Scheduled Actions** → search for `arraysubs_renewal` → **Run** the pending action, OR
   - Run `wp cron event run --due-now` from CLI.

**Expected Result:**
- The chosen method is recorded in Sign-off and used identically for cycles 1–5 below.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 6.4 — Run cycle 1 ($100.00 → $80.01)
**Steps:**
1. Apply the documented time-travel method to advance the subscription's `Next Payment Date` to the past.
2. Run the renewal action.
3. Open the new renewal order.
4. Reload **Manage Credits** for `cust3`.

**Expected Result:**
- Renewal order total `$19.99`, fee line `Store Credit -$19.99`, order total `$0.00`, status Processing/Completed.
- A `Applied to Renewal` debit row of `-$19.99` is in Credit History.
- Customer balance is `$80.01`.
- A **Store Credit Used** email arrives at `cust3@test.local`.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 6.5 — Run cycle 2 ($80.01 → $60.02)
**Steps:**
1. Repeat the time-travel + renewal flow.
2. Verify the new renewal order, history row, and balance.

**Expected Result:**
- Renewal order paid in full via credit.
- Customer balance is `$60.02`.
- New `Applied to Renewal` history row of `-$19.99`.
- Second **Store Credit Used** email arrives.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 6.6 — Run cycles 3, 4, 5
**Steps:**
1. Repeat the same flow three more times.
2. After each cycle, capture the post-renewal balance.

**Expected Result:**
- After cycle 3: balance `$40.03`.
- After cycle 4: balance `$20.04`.
- After cycle 5: balance `$0.05`.
- Five new `Applied to Renewal` rows total in Credit History; five new **Store Credit Used** emails total.
- Subscription remains **Active** with the next payment date advancing by one week each cycle.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 6.7 — Verify subscription continues
**Steps:**
1. Open the subscription detail screen for `cust3`'s `Standard Weekly` subscription.
2. Read the next payment date and the **Related orders** block.

**Expected Result:**
- Status remains **Active**.
- `Next Payment Date` is now five weeks after the original date captured in 6.2.
- All five renewal orders are linked under **Related orders**, each marked paid in full.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 6.8 — Negative: insufficient credit + minimum-order block on cycle 6
**Steps:**
1. With balance at `$0.05` (below the $5.00 minimum and far below the $19.99 renewal), time-travel a sixth renewal.

**Expected Result:**
- Auto-apply is skipped because the available balance is below the minimum order amount.
- The renewal order falls back to charging the saved Stripe gateway (or stays in Pending Payment for a manual gateway).
- Customer balance remains `$0.05`.
- Document the exact behaviour observed (gateway charge vs pending) in the Sign-off block.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

## Regression / Cross-checks
- Confirm the customer's portal Store Credit page shows the balance and history rows correctly across all five cycles.
- DevTools / debug.log must not show errors during any cron / action scheduler run.
- Document the exact time-travel method and any deviation observed in cycle 6.

## Sign-off
- Tester:
- Date:
- Browser & version:
- Time-travel method (A / B / C):
- Subscription ID renewed:
- Balances after cycles 1–5:
- Cycle 6 fallback behaviour:
- Notes:
