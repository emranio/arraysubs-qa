# Stage 12 — Task 07: Refund to Credit

| Key | Value |
|---|---|
| Stage | 12 — Store Credit (Pro) |
| Module | Store Credit — Refund to Credit |
| Plugin Coverage | Pro |
| Estimated Time | 20 min |
| Depends On | 12.01, 12.02 |

## Objective
Refund a paid order using the **As Store Credit** refund method instead of the standard gateway refund. Confirm that the customer's credit balance increases by the refund amount, the Credit History records source = `Refund`, the order page shows the credit refund alongside the order, and that the gateway refund maximum decreases by the credited amount (preventing double refund).

## Pre-conditions
- 12.01 complete; Store Credit enabled.
- A paid order exists on the site for `cust3@test.local`. If none, place a fresh `Coffee Plan — Daily` ($2.99) order via Stripe test card `4242 4242 4242 4242` (or BACS / COD) and mark it **Completed**. Or use any prior order from 12.04 / 12.05 with non-zero gateway revenue.
- For a clear test, use a regular non-credit-funded order with a real positive amount (e.g., the $45 `Credit Pack $50` order from 12.04 Sub-Task 4.5).

## Test Data
- Refund target order: the $45 BACS / COD order from 12.04 Sub-Task 4.5 (or another order with a real $X total — adjust amounts below).
- Refund amount: `$15.00`.
- Refund reason: `QA refund-to-credit test`.
- Customer's pre-refund balance: read it from **Manage Credits** before starting.

## Sub-Tasks

### Sub-Task 7.1 — Open the target order
**Steps:**
1. **WC → Orders**.
2. Search for the order from 12.04 (the `Credit Pack $50` order, $45 total, customer `cust3`).
3. Open the order edit screen.
4. Confirm the order status is **Processing** or **Completed**.

**Expected Result:**
- The order opens in the standard WooCommerce edit view.
- An action area shows a **Refund** button beneath the line items.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 7.2 — Read the customer's current credit balance
**Steps:**
1. In a second tab, **ArraySubs → Store Credit → Manage Credits** → load `cust3`.
2. Note the current balance (e.g., `$0.00` if 12.06 left it at zero, or whatever the current value is).
3. Note this value as `pre_balance` for later comparison.

**Expected Result:**
- The pre-refund balance is captured.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 7.3 — Initiate a refund and select As Store Credit
**Steps:**
1. Back on the order edit screen, click **Refund**.
2. The refund interface expands.
3. Locate the **Refund Method** radio buttons:
   - **Via Gateway** (default)
   - **As Store Credit**
4. Select **As Store Credit**.
5. Read the help block — it should display the customer's current credit balance and a preview of the new balance after refund.

**Expected Result:**
- Both radio options are visible.
- Selecting **As Store Credit** reveals (or updates) the balance preview block.
- The current balance shown matches the value captured in 7.2.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 7.4 — Process the credit refund
**Steps:**
1. Enter refund amount `15.00`.
2. Enter reason `QA refund-to-credit test`.
3. Click the refund-as-credit button (label may read **Refund $15.00 as Store Credit** or similar).
4. Confirm any AJAX confirmation prompt.

**Expected Result:**
- The refund completes; a success toast appears.
- The order page refreshes to show:
  - A new order note recording the credit refund amount and reason.
  - An entry in the order's refund/credit history block referencing `As Store Credit — $15.00`.
- The refundable amount on the page decreases by `$15.00` (e.g., from `$45.00` to `$30.00`).
- No gateway refund was issued (no entry in WooCommerce's standard refund record list, since this is a credit refund).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 7.5 — Customer balance and history updated
**Steps:**
1. Reload **Manage Credits** for `cust3`.

**Expected Result:**
- Balance is `pre_balance + $15.00`.
- A new history row appears:
  - Description: `Refund` plus the reason.
  - Amount: `+$15.00` (green).
  - Balance After: matches the new balance.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 7.6 — Credit Added email arrived
**Steps:**
1. Inspect `cust3@test.local`'s inbox.

**Expected Result:**
- A **Store Credit Added** email arrives.
- Source displayed in the email body is `Refund`.
- Amount `$15.00`, new balance matches what was observed in 7.5.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 7.7 — Verify Credit History filter
**Steps:**
1. **ArraySubs → Store Credit → Credit History**.
2. Filter Source = `Refund`.

**Expected Result:**
- The new $15 entry is in the list under Source = Refund.
- Description includes the refund reason `QA refund-to-credit test`.
- No other refund entries (yet) exist unless a prior task created one.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 7.8 — Cannot exceed remaining refundable
**Steps:**
1. Reopen the same order's refund interface.
2. Try to refund `$50.00` as Store Credit (more than the $30 remaining refundable).

**Expected Result:**
- The action is rejected with an error similar to `Refund amount cannot exceed remaining refundable amount.`
- No additional credit is added; balance is unchanged.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

## Regression / Cross-checks
- The order's WooCommerce **Order total** in admin is unchanged (still the original total) — credit refund is tracked in `_refunded_as_credit` meta, not the WC refund record.
- A guest-checkout order should NOT show the **As Store Credit** option. Verify by opening any guest order if available.
- DevTools / debug.log free of errors.

## Sign-off
- Tester:
- Date:
- Browser & version:
- Order number refunded:
- Pre-refund balance / Post-refund balance:
- Notes:
