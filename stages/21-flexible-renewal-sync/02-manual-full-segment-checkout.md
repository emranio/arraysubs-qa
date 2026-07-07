# Stage 21 — Task 02: Manual Gateway — Segment 1 (Full Amount) Checkout

| Key | Value |
|---|---|
| Stage | Flexible Renewal Sync |
| Module | Checkout + subscription creation (first-charge mode `full`) |
| Plugin Coverage | Free engine + Pro feature |
| Estimated Time | 25 min |
| Depends On | Stage 21 Task 01 |

## Objective
Run a complete Direct-bank-transfer checkout for a purchase day inside **segment 1** and verify every required condition: full first charge, synced summary rows, subscription meta (`mode = full`), next payment on the upcoming cycle boundary, correct activation on payment, and correctly scheduled renewal jobs.

## Pre-conditions
- `FRS Monthly 30` configured per Task 01.
- Today's day-of-cycle `D` noted (site timezone). Set boundaries so **`b1 ≥ D`** (e.g. D=8 → `10 / 20`); all three segments active. Update the product.
- **Direct bank transfer** enabled. Global renewal-sync setting OFF.
- A customer with no live subscription for this product (use `sync-full@example.test` or a fresh incognito session with auto-create account).

## Test Data
- Product: `FRS Monthly 30` ($30.00 / month, virtual).
- Expected first charge: **$30.00**.
- Expected next payment: **1st of next month, 00:00 site time**.

## Sub-Tasks

### Sub-Task 2.1 — Cart & checkout summary
**Steps:**
1. As the customer, add `FRS Monthly 30` to cart and open checkout.
2. Read the order review rows for the item.

**Expected Result:**
- **Renewals:** `30.00$ Every month`.
- **Today's charge:** `30.00$ full first charge`.
- **Next charge:** 1st of next month with `(30.00$)`.
- Order **Total = 30.00$**. No proration wording anywhere.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 2.2 — Place the order (Direct bank transfer)
**Steps:**
1. Select **Direct bank transfer**, place the order.
2. Note the order ID on the confirmation page.

**Expected Result:**
- Order received page loads with total **$30.00**; order status **On hold** (bacs default) or Pending.
- No console/PHP errors.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 2.3 — Subscription record before payment
**Steps:**
1. Admin → the order → open the linked **Subscription: #ID**.
2. Verify meta via the detail page and WP-CLI (`wp post meta list <sub_id> …`).

**Expected Result:**
- Status `arraysubs-pending` before the order is paid.
- **Next Payment = 1st of next month 00:00 site time** (stored UTC equivalent).
- **Recurring Amount = $30.00**.
- Meta: `_renewal_sync_enabled = yes`, `_renewal_sync_first_charge_mode = full`, `_renewal_sync_first_full_renewal_date` = same as next payment, `_renewal_sync_cycle_start_date` = 1st of the **current** month.
- Order item meta mirrors the `_renewal_sync_*` values; `_renewal_sync_initial_recurring_amount = 30`.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 2.4 — Mark paid → activation keeps the synced date
**Steps:**
1. Set the order status to **Processing** (simulates received bank payment). Save.
2. Re-open the subscription detail.

**Expected Result:**
- Subscription status **Active**; **Completed Payments = 1**; **Last Payment** ≈ now.
- **Next Payment is still the 1st of next month** — activation must NOT rewrite it to `today + 1 month`.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 2.5 — Renewal jobs scheduled on the boundary
**Steps:**
1. Tools → Scheduled Actions → search the subscription ID.

**Expected Result:**
- `arraysubs_generate_renewal_invoice` pending ~6 h **before** the boundary; `arraysubs_process_renewal` pending **at** the boundary. Nothing scheduled earlier.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

## Regression / Cross-checks
- Customer **My Account → Subscriptions** shows the synced next payment date and $30.00 recurring.
- `wp-content/debug.log` unchanged.
- Record the subscription ID for **Task 07**.

## Sign-off
- Tester:
- Date:
- Browser & version:
- Day-of-cycle D / boundaries used:
- Order ID / Subscription ID:
- Expected vs actual next payment date:
- Notes:
