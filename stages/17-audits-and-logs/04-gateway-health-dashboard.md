# Stage 17 — Task 04: Gateway Health Dashboard (Stripe only)

| Key | Value |
|---|---|
| Stage | 17 — Audits, Logs & Troubleshooting |
| Module | Audits → Gateway Logs (Gateway Health Dashboard) |
| Plugin Coverage | Pro |
| Estimated Time | 25 min |
| Depends On | Stripe is **already configured** (Test mode, webhook endpoint registered) — no setup task in this stage |

## Objective
Verify the **ArraySubs → Audits [beta] → Gateway Logs** screen renders the **Stripe** status card with the correct **Status**, **Subscriptions**, and **Last Webhook** fields, exposes the webhook URL and capability tags in the expanded detail, and that the Webhook Event Log table below the card lists incoming Stripe webhook events with pagination working at 50 per page. Trigger a real Stripe test webhook and confirm the event appears in the log. Verify webhook event-type filters cover the five event types in scope.

> **Note:** PayPal and Paddle gateway cards are **out of scope** for this regression cycle. If those cards render anyway, verify they show `Not Configured` / `Needs Setup` and skip their sub-tests.

## Pre-conditions
- Logged in as administrator at `/wp-admin/`.
- ArraySubs Pro active with Automatic Payments feature enabled.
- Stripe is **already configured** in Test mode (API keys saved; webhook URL registered in the Stripe dashboard) — confirm presence; do not re-configure.
- Stripe CLI installed locally (e.g., `stripe trigger payment_intent.succeeded`) OR access to "Send test webhook" inside the Stripe developer dashboard.
- At least one Active subscription paid by Stripe (e.g., **Standard Weekly $19.99/wk** with saved card `4242 4242 4242 4242`) so the Stripe card shows a non-zero Subscriptions count.

## Test Data
- Active subscription on Stripe (ID: ____)
- Stripe test event types in scope for this stage:
  - `payment_intent.succeeded`
  - `payment_intent.payment_failed`
  - `customer.subscription.updated`
  - `invoice.payment_succeeded`
  - `invoice.payment_failed`
- Stripe test event chosen for the live webhook trigger: `payment_intent.succeeded` (or alternative: ____)

## Sub-Tasks

### Sub-Task 04.1 — Open Gateway Logs and verify Stripe card exists (already configured)
**Steps:**
1. Go to **ArraySubs → Audits [beta] → Gateway Logs**.
2. Confirm the **Stripe** card is rendered. Read the header (status icon + title + yellow **Test** badge).
3. If PayPal and/or Paddle cards also render, note their status (expected: `Not Configured` / `Needs Setup` / `Disabled`) and skip them.

**Expected Result:**
- Stripe card present, with status icon = green checkmark and status label = `Connected (Test Mode)`.
- Yellow **Test** badge appears on the Stripe card.
- Stripe is **already configured** — no setup banner / "Connect Stripe" CTA on this card.
- PayPal / Paddle (if rendered) clearly indicate not-configured state and are out of scope.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 04.2 — Verify Stripe stats grid
**Steps:**
1. Read all three stats on the Stripe card:
   - **Status** value (expected: `Connected (Test Mode)`)
   - **Subscriptions** count (numeric — counts active, on-hold, trial, pending subscriptions attributed to Stripe)
   - **Last Webhook** timestamp (or `Never` if no events have been received yet)
2. Record the Stripe card's Subscriptions count: ____
3. Cross-check: go to **ArraySubs → Subscriptions** and filter by gateway = Stripe; the count should match the stats grid number.

**Expected Result:**
- Status = `Connected (Test Mode)`.
- Subscriptions count on the Stripe card matches the filtered subscription list.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 04.3 — Expand the Stripe card and verify capabilities and webhook URL
**Steps:**
1. Click the expand toggle on the **Stripe** card.
2. Inspect the expanded section.
3. Copy the **Webhook URL** displayed in a monospace code block.
4. Note the **Capabilities** tags: should include at least `subscription`, plus some of `trial`, `pause`, `refunds`, `card auto update`.
5. Click the **WooCommerce Settings** button.
6. Confirm it opens the Stripe gateway configuration page in WooCommerce.
7. Return to Gateway Logs.

**Expected Result:**
- Webhook URL is a complete absolute URL (e.g. `/wc-api/...` or a REST-API path). Format is monospace and selectable.
- Capabilities tags appear as small pills.
- WooCommerce Settings button navigates to the correct Stripe gateway settings page.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 04.4 — Verify Webhook Event Log columns and event-type filters
**Steps:**
1. Scroll below the Stripe card to the **Webhook Event Log** table.
2. Verify the columns: **Gateway**, **Event ID**, **Event Type**, **Processed At**.
3. Open the gateway filter dropdown above the table and select **Stripe** (or `arraysubs_stripe`). Confirm only Stripe rows appear.
4. Open the **Event Type** filter (or use the search box scoped to the Event Type column). Verify each of the five in-scope event types is selectable / filterable:
   - `payment_intent.succeeded`
   - `payment_intent.payment_failed`
   - `customer.subscription.updated`
   - `invoice.payment_succeeded`
   - `invoice.payment_failed`
5. Apply each filter in turn and confirm only matching rows render (some may be empty until events arrive).
6. Reset filters. Click the **Refresh** button.

**Expected Result:**
- Four columns visible in the order listed.
- Gateway filter narrows the table to Stripe only.
- Each of the five event-type filters is available and narrows the table correctly.
- Refresh reloads data without resetting the filter.
- Pagination shows 50 events per page with `Page X of Y` and total event count.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 04.5 — Trigger a real Stripe test webhook and confirm it appears
**Steps:**
1. Open a terminal locally and run: `stripe trigger payment_intent.succeeded` (or use the Stripe dashboard "Send test webhook" button to deliver a `payment_intent.succeeded` to the registered endpoint).
2. Wait up to 30 seconds for the event to be received and processed.
3. Return to Gateway Logs and click **Refresh** on the Webhook Event Log.
4. Filter the gateway dropdown to **Stripe**.
5. Inspect the top row of the event log.

**Expected Result:**
- A new event row appears at the top with:
  - **Gateway**: gray badge `arraysubs_stripe`
  - **Event ID**: starts with `evt_` (Stripe convention) — copy and record: ____
  - **Event Type**: `payment_intent.succeeded` (or the exact event you triggered)
  - **Processed At**: timestamp matching "just now" in site timezone
- The Stripe card's **Last Webhook** stat updates to the new timestamp on next page reload.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 04.6 — Pagination of the webhook event log
**Steps:**
1. Reset the gateway filter to **All Gateways**.
2. Confirm the pagination total at the bottom: ____
3. If more than 50 events exist, click **Next** to navigate to Page 2; confirm the page indicator increments.
4. Click **Previous** to return.
5. Confirm Previous is disabled on Page 1, Next is disabled on the last page.

**Expected Result:**
- 50 events per page rendered.
- Pagination buttons enable/disable correctly at boundaries.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 04.7 — Verify duplicate-protection (re-send the same event)
**Steps:**
1. From the Stripe dashboard or CLI, manually re-deliver the **same** event (using the same `evt_xxxx` event ID) to the webhook URL. (In Stripe dashboard: Developers → Webhooks → click on event → Resend.)
2. Wait 10 seconds and Refresh the event log.

**Expected Result:**
- The event log does NOT contain a duplicate row for the same event ID.
- The original row is still visible. (Duplicate protection: each gateway event is identified by `(gateway_slug, event_id)` pair.)
- Optionally check server error logs to confirm the second delivery returned a non-error "already processed" response.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

## Regression / Cross-checks
- After triggering the Stripe webhook, confirm Activity Audits (Task 17.01) shows a new audit row with **Author = Gateway** (Stripe) for the same event.
- Confirm the recorded subscription count on the Stripe card matches a manual filter of subscriptions in **ArraySubs → Subscriptions**.
- Confirm the Webhook URL on the Stripe card matches the URL configured in the Stripe dashboard webhook endpoint.
- **Out of scope:** PayPal and Paddle gateway cards. If they render, verify they show `Not Configured` / `Needs Setup` and skip their sub-tests — do NOT spend time configuring them.

## Sign-off
- Tester:
- Date:
- Browser & version:
- Stripe event ID captured:
- Stripe Subscriptions count observed:
- PayPal / Paddle observed (if rendered): ____ (expected: Not Configured)
- Notes:
