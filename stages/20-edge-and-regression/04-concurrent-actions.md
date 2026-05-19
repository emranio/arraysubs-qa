# Stage 20 — Task 04: Concurrent Actions (Race Conditions)

| Key | Value |
|---|---|
| Stage | 20 — Edge Cases & Final Regression |
| Module | Concurrency / Locking |
| Plugin Coverage | Both |
| Estimated Time | 30 min |
| Depends On | Stage 07 (Customer Portal), Stage 14 (Admin Subscriptions) |

## Objective
Test two race-condition scenarios.

1. As the same customer, in two different browsers / two different tabs, trigger **Cancel Subscription** and **Plan Switch** at nearly the same instant on the same subscription. Verify only one operation succeeds; the other reports a clean error or is queued safely. No duplicate subscriptions are created. No inconsistent state (e.g. status simultaneously Cancelled and "switching plans") is left behind.

2. As two different admins, in two browsers, edit the same subscription's billing address simultaneously. Verify last-write-wins (or a conflict warning) without partial corruption.

Document the observed behaviour precisely — different gateways/setups may serialise differently.

## Pre-conditions
- Two browsers (or one normal + one incognito) with different cookies, both logged in as `cust1@example.com`.
- Two more browsers logged in as different admin users (Administrator role) — call them Admin-A and Admin-B.
- Subscription Sub-X owned by `cust1@example.com`, status **Active**, on a product that supports plan switching (linked products / upgrade-downgrade group exists).
- The plan-switching feature is enabled in **General Settings → Customer Actions**.
- A target plan exists for the switch.
- Stopwatch / coordinated start so both clicks happen within ~1 second of each other.

## Test Data
- Sub-X ID: `<record>`.
- Target switch plan: `<record>`.
- Pre-test billing address line 1: `<record>`.

## Sub-Tasks

### Sub-Task 04.1 — Customer race: Cancel vs Plan Switch
**Steps:**
1. In Browser-1 (customer), open Sub-X and navigate to the **Cancel Subscription** modal. Get to the final **Continue/Confirm** button without clicking it yet.
2. In Browser-2 (same customer), open Sub-X and navigate to the **Switch Plan** flow. Pick the target plan and get to the final **Confirm Switch** button without clicking it yet.
3. On a 3-2-1 count, click both Confirm buttons as close to simultaneously as possible.
4. Capture screenshots of both browsers' result screens.

**Expected Result:**
- Exactly one of the two operations succeeds.
- The losing browser shows a clean error like "This subscription has changed since you opened this page. Please refresh." or the operation reports a conflict.
- The other browser shows the normal success message for whichever op won.
- Examine the subscription detail in admin — it is in exactly one of the two valid resulting states (either Cancelled, or with a pending plan switch / new plan applied), never both, and no second subscription record was created.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 04.2 — Verify no orphan state
**Steps:**
1. Inspect **Tools → Scheduled Actions** filtered by Sub-X.
2. Inspect subscription notes.
3. Inspect the WC orders linked to Sub-X.

**Expected Result:**
- No half-completed plan switch (e.g. switch fee charged but plan not actually changed).
- No duplicate Cancelled-then-Active toggling — exactly one terminal status if cancel won.
- No duplicate orders for the same plan-switch event.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 04.3 — Reset for admin race test
**Steps:**
1. If Sub-X is now Cancelled, reactivate it from the admin status dropdown so we have an active subscription for 04.4. Otherwise, undo the plan switch / pick a different active subscription.

**Expected Result:**
- Sub-X is back to Active for the admin test.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 04.4 — Admin race: simultaneous billing address edits
**Steps:**
1. Admin-A opens Sub-X edit page in admin. Loads billing address; about to change "Address Line 1" to `100 First St`. Does NOT save yet.
2. Admin-B opens the same Sub-X edit page. About to change "Address Line 1" to `200 Second Ave`. Does NOT save yet.
3. Both admins click Save within ~1 second of each other (Admin-A first by a hair).
4. Both reload their page after seeing the response.

**Expected Result:**
- Both saves complete with a success notice — OR one wins and the other shows a conflict warning. Record observed behaviour.
- The actual billing address stored on the subscription is exactly one of the two values (last-write-wins is acceptable; partial merge of fields is NOT acceptable).
- Subscription notes record both edit attempts (or at least the winning one) with timestamps and which admin performed it.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 04.5 — No PHP errors / no inconsistent state
**Steps:**
1. Tail `wp-content/debug.log` (if `WP_DEBUG_LOG` enabled) during the tests above.
2. Look for any PHP fatal/notice during 04.1–04.4.

**Expected Result:**
- No fatal errors.
- Notices, if any, are not related to the concurrency tests.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 04.6 — Document final behaviour
**Steps:**
1. Write a 3-5 sentence summary in the Sign-off → Notes describing exactly what the system did in 04.1 and 04.4.

**Expected Result:**
- The summary captures whether locking, last-write-wins, or queueing was used. This becomes the reference behaviour for support / future regression.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

## Regression / Cross-checks
- Confirm WooCommerce's standard order screen behaved normally during the tests (admin race did not corrupt any other order's state).
- Confirm no leftover transient locks or `_arraysubs_lock_*` rows in `wp_options` are stuck (search and remove if so — they should auto-expire).

## Sign-off
- Tester:
- Date:
- Browser & version:
- Notes:
