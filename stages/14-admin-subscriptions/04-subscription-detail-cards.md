# Stage 14 — Task 04: Subscription Detail Cards

| Key | Value |
|---|---|
| Stage | Admin Subscription Management |
| Module | Admin SPA — Subscription Detail (cards) |
| Plugin Coverage | Both |
| Estimated Time | 30 min |
| Depends On | 14-02, 14-03; Stage 06 (lifecycle states) and Stage 08 (retention) |

## Objective
Open the **View Details** screen for several subscriptions and verify each conditional card renders only when its data is present, and renders all the fields the manual documents. Cover: Cancellation Details (when scheduled or after retention), Skip & Pause (always shown), Coupon Discount, Payment Gateway *(Pro)* with detach/resync, Checkout Builder fields *(Pro)*, and Subscription Shipping *(Pro)*.

## Pre-conditions
- Logged in as Administrator.
- Subscriptions available with these specific states:
  - **Sub A** — has a scheduled (end-of-period) cancellation pending and at least one retention offer in history.
  - **Sub B** — has a recurring WooCommerce coupon applied (e.g. `SAVE20`).
  - **Sub C** — paid via the Stripe automatic gateway with a card on file (Pro). Stripe is already configured in test mode — no setup required.
  - **Sub D** — went through a Checkout Builder custom field (e.g. "Company Size") (Pro).
  - **Sub E** — product requires shipping with a Recurring shipping type (Pro).
- ArraySubs Pro is active.
- Note: PayPal and Paddle are out of scope this cycle. Only Stripe and manual gateways (BACS / COD) are tested below.

## Test Data
- Sub A scheduled cancellation reason: `Too expensive`.
- Sub B coupon: `SAVE20`, recurring, 5 cycles total, 3 remaining.
- Sub C gateway: Stripe with `Visa ending in 4242`, expiry future.
- Sub D Checkout Builder field: `Company Size = 50–100`.
- Sub E shipping type: `Recurring`, method `Flat Rate`, initial $9.99, renewal $7.99.

## Sub-Tasks

### Sub-Task 4.1 — Cancellation Details card on Sub A
**Steps:**
1. Open Sub A's detail page.
2. Locate the card titled **Scheduled Cancellation** (with a Pending badge).
3. Read every field shown.

**Expected Result:**
- Card title is **Scheduled Cancellation** with a **Pending** badge (per manual title-variation table).
- Fields shown: Cancellation Reason `Too expensive`, Additional Details (if present), Scheduled Date (a future date matching the next payment date).
- A **Retention Offers** section is visible with: Offers Shown, Date Shown, Offer Accepted (if any), Date Accepted.
- An **Undo Scheduled Cancellation** button is present in the page header action bar.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 4.2 — Skip & Pause card (always visible)
**Steps:**
1. On Sub A (or any Active subscription), scroll to the **Skip & Pause** card.
2. Confirm the section is rendered even when no skip or pause is currently active.

**Expected Result:**
- Card is always present.
- If features are disabled in settings, the card displays a disabled-state message.
- If features are enabled and no skip is active, a **Skip Next Renewal** action is offered.
- If a skip is active, the fields **Status: Currently Skipping**, **Cycles Remaining**, **Skip Started**, **Original Next Payment**, and optional **Reason** appear, alongside **Modify Skip** and **Undo Skip** actions.
- If a pause is active, the card shows **Status: Paused**, **Paused Since**, **Resume Date**, **Reason**, **Pause History**, and a **Resume Now** action.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 4.3 — Coupon Discount card on Sub B
**Steps:**
1. Open Sub B's detail page.
2. Locate the **Coupon Discount** card.
3. Read every field and badge.

**Expected Result:**
- Coupon Code displayed in a code element (e.g. `SAVE20`).
- Discount value (percentage or fixed amount).
- A **Recurring** badge (since it is a recurring coupon).
- For recurring coupons: **Total Cycles** shows `5`, **Remaining Cycles** shows `3`.
- **Captured Date** is shown.
- No **Expired** badge yet (because cycle limit not reached).
- If the coupon has been deleted from WooCommerce, a "(coupon deleted)" annotation would appear — confirm absent in this case.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 4.4 — Payment Gateway card on Sub C (Stripe) *(Pro)*
**Steps:**
1. Open Sub C's detail page (Stripe-attached subscription).
2. Locate the **Payment Gateway** card.
3. Read every field. Inspect the action buttons.

**Expected Result:**
- Card only appears because an automatic gateway (Stripe) is attached.
- Fields visible: Gateway `Stripe`, Connection Status `Connected` (green badge), Card on File `Visa ending in 4242`, Expiry (no Expired badge), Customer ID (in code format, e.g. `cus_…`), Last Transaction ID (in code format, e.g. `pi_…`).
- A **Resync from Gateway** button is visible and pulls fresh data from Stripe when clicked.
- A **Detach Gateway** button is visible.
- Clicking **Detach Gateway** opens a confirmation: "Are you sure you want to detach the payment gateway? The subscription will revert to manual payment." — Cancel out of this dialog so the subscription stays attached.
- Note: PayPal and Paddle are out of scope this cycle and are not exercised here.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 4.5 — Verify Payment Gateway card hidden on a manual gateway subscription
**Steps:**
1. Open a subscription that was paid through Bank Transfer (BACS) or Cash on Delivery (manual gateways).
2. Scroll the detail page looking for the **Payment Gateway** card.

**Expected Result:**
- No **Payment Gateway** card renders for manual-gateway subscriptions, per the manual info-box.
- The Billing Information card still surfaces the gateway slug (e.g. "Direct bank transfer" in the Payment Method row).
- Confirms the card renders only for Stripe (Sub-Task 4.4) and is correctly suppressed for manual gateways. PayPal and Paddle are out of scope this cycle.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 4.6 — Checkout Builder Fields card on Sub D *(Pro)*
**Steps:**
1. Open Sub D's detail page.
2. Locate the **Checkout Builder Fields** card (only visible if the "Show on subscription detail" setting is enabled and data exists).
3. Confirm each captured field renders with its label and value.

**Expected Result:**
- Card title is **Checkout Builder Fields** (or equivalent).
- Each captured field row shows the label and the customer-entered value (e.g. `Company Size: 50–100`).
- Different field types render correctly: text as plain text; select as the chosen option label; date range as `Start — End`; file upload as a link.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 4.7 — Subscription Shipping card on Sub E *(Pro)*
**Steps:**
1. Open Sub E's detail page.
2. Locate the **Subscription Shipping** card.
3. Read every field.

**Expected Result:**
- **Shipping Type** badge reads `Recurring (every renewal)`.
- **Shipping Method** shows `Flat Rate`.
- **Initial Shipping** shows `$9.99`.
- **Renewal Shipping** shows `$7.99` (only present for recurring).
- **Initial Shipping Paid** shows `Yes` if the initial order is paid, otherwise `No`.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 4.8 — Header action buttons match subscription state
**Steps:**
1. On Sub A (scheduled cancellation), inspect the header action buttons.
2. On any Active subscription with no scheduled cancellation, inspect the header action buttons.

**Expected Result:**
- Sub A header shows **Undo Scheduled Cancellation**, **Edit Subscription**, **Login as Customer** (and not **Cancel Subscription**, since one is already scheduled).
- An Active subscription with no pending cancellation shows **Cancel Subscription**, **Edit Subscription**, and **Login as Customer**.
- The Login as Customer button is disabled with an explanatory tooltip if the Login-as-User feature is off in Toolkit settings.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

## Regression / Cross-checks
- Open a subscription with no coupon, no scheduled cancellation, no Pro shipping product, no automatic gateway, and no Checkout Builder fields. Confirm exactly **none** of the conditional cards render.
- Reload Sub C in the browser after clicking Resync from Gateway — confirm card-on-file, expiry, and last transaction ID values come back unchanged or freshly synced from Stripe.
- PayPal and Paddle integrations are out of scope this cycle. Do not test or document either gateway in this task.
- Confirm DevTools console is clean throughout.

## Sign-off
- Tester:
- Date:
- Browser & version:
- Subscription IDs covered (A/B/C/D/E):
- Notes:
