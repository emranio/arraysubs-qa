# Stage 19 — Task 05: Refund to Store Credit (Pro)

| Key | Value |
|---|---|
| Stage | 19 — Refund Management |
| Module | Store Credit / Refund-to-Credit |
| Plugin Coverage | Pro |
| Estimated Time | 22 min |
| Depends On | 01 — Refund settings |

## Objective
With ArraySubs Pro active and **Store Credit** enabled, refund a paid order using the **As Store Credit** refund method. Verify that the gateway is **not** charged a refund, the customer's Store Credit balance increases by the refund amount, the credit transaction shows source "Refund", an order note documents the credit refund, and the customer can see the credit-refund record in their My Account → Orders / Store Credit pages.

## Pre-conditions
- ArraySubs **Pro** active.
- **ArraySubs → Store Credit → Settings:** Store Credit feature **Enabled**.
- A registered (non-guest) customer `cust5@example.com` has a paid order on a refund-capable gateway. For this task, a one-time / non-subscription order is fine — but if a subscription parent order is used, the test still works.
- Note the customer's Store Credit balance **before** the test (most likely `$0.00`).
- Note the order total — for this test use **$40.00** so the math is unambiguous.
- **WooCommerce → Settings → Emails → Credit Added** email is enabled, so we can verify the customer notification.

## Test Data
- Order total: `$40.00`.
- Refund amount to issue as credit: `$40.00` (full order).
- Pre-test customer balance: `<record>`.

## Sub-Tasks

### Sub-Task 05.1 — Verify "As Store Credit" option is visible
**Steps:**
1. Open the order in **WooCommerce → Orders → Edit Order**.
2. Click **Refund**.
3. Inspect the refund interface.

**Expected Result:**
- A **Refund Method** radio group is visible with two options: **Via Gateway** (default), **As Store Credit**.
- A read-out shows the customer's **current credit balance** and (after entering an amount) a preview of the **balance after** the refund.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 05.2 — Process a $40 refund as store credit
**Steps:**
1. Select **As Store Credit**.
2. Enter `40.00` in the refund amount.
3. Enter the reason: `QA refund-to-credit full order`.
4. Click the refund-confirm button.

**Expected Result:**
- The refund processes within a few seconds with no error.
- An on-page confirmation indicates the credit was added.
- The displayed customer balance updates to `previous + 40.00`.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 05.3 — Verify gateway was NOT refunded
**Steps:**
1. Open the gateway dashboard (Stripe test mode or equivalent) for the original transaction.
2. Refresh the WooCommerce order edit page.

**Expected Result:**
- The gateway shows **no refund** for this charge (the original charge is still listed as fully captured).
- The WooCommerce order does **not** show a standard `-$40.00` WC refund line in the items table — the credit refund is tracked separately, not as a WC refund record.
- The order's `_refunded_as_credit` (or similarly named) meta now contains `40.00`.
- Order notes contain a note documenting the credit refund: amount, reason, and "processed as store credit".

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 05.4 — Verify credit balance and credit history
**Steps:**
1. Go to **ArraySubs → Store Credit → Management** (or wherever the customer balance is shown).
2. Find `cust5@example.com`.
3. Go to **ArraySubs → Store Credit → Credit History** (or the global transaction log).

**Expected Result:**
- Customer's balance is `previous + 40.00`.
- Credit History includes a new transaction with **Source = Refund**, **Amount = +$40.00**, **Linked Order = the order ID**, **Reason = `QA refund-to-credit full order`**.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 05.5 — Customer email notification
**Steps:**
1. Open the inbox / mail-trap for `cust5@example.com`.

**Expected Result:**
- A **Credit Added** email arrives.
- Body shows `+$40.00`, source `Refund`, and the new total balance.
- A link to **My Account → Store Credit** is included and works.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 05.6 — Customer-visible refund history in My Account
**Steps:**
1. Log in as `cust5@example.com`.
2. Open **My Account → Orders** and click the order.
3. Open **My Account → Store Credit** (or whatever the front-end Pro page is named).

**Expected Result:**
- The order detail page indicates that a credit refund was processed (record exact wording — e.g. "Refunded as store credit: $40.00").
- The Store Credit page shows balance `previous + 40.00` and the transaction in the customer's transaction history with source "Refund".

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 05.7 — Maximum refundable enforcement
**Steps:**
1. Back in admin, attempt another refund on the same order — try to refund another `$10.00` as store credit.

**Expected Result:**
- The refund is **rejected** with an error like "Refund amount exceeds the maximum refundable" because the order is already fully refunded as credit (`$40 / $40` consumed, $0 remaining refundable).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

## Regression / Cross-checks
- Confirm a guest order (no registered customer) does **not** show the "As Store Credit" option — only "Via Gateway" is offered.
- Confirm that the WooCommerce → Reports → Refunds report does **not** count this as a gateway refund (manual states it is tracked separately and does not reduce gross revenue in standard refund reporting).
- Confirm the Two-Way Sync Guard is not relevant here (no gateway refund event was triggered).

## Sign-off
- Tester:
- Date:
- Browser & version:
- Notes:
