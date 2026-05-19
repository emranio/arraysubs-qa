# Stage 19 — Task 06: Subscription State After Full Refund

| Key | Value |
|---|---|
| Stage | 19 — Refund Management |
| Module | Refunds / Subscription State |
| Plugin Coverage | Free |
| Estimated Time | 22 min |
| Depends On | 01 — Refund settings, 02 — Prorated refund |

## Objective
With **Refund on Cancellation = Allow Immediate Refund**, fully refund the parent order of an Active subscription from the WooCommerce order edit screen (not from the subscription page). Verify that ArraySubs auto-detects the full refund, immediately cancels the subscription, unschedules every future Action Scheduler job for that subscription, records refund + cancellation notes, and sends the customer the Subscription Cancelled email.

## Pre-conditions
- Task 01 passed; settings = Allow Immediate Refund, Auto Gateway Refund **Enabled**, Allow Prorated Refunds **Enabled**, Min Refund Amount `0`.
- A new subscription for `cust6@example.com` priced at $30/month, just paid (parent order Completed today), status **Active**.
- Future renewal action is queued in Action Scheduler — note its scheduled run time.
- Renewal reminder action also queued — note its scheduled run time.
- Subscription is paid via a refund-capable gateway.

## Test Data
- Parent order ID: record.
- Parent order total: `$30.00`.
- Refund amount: `$30.00` (full).

## Sub-Tasks

### Sub-Task 06.1 — Capture pre-refund baseline
**Steps:**
1. On the subscription detail page, record: status = `Active`, next payment date = `<record>`, completed payments count = `<record>`, end date = empty.
2. In **Tools → Scheduled Actions**, filter by the subscription ID. Record the count and hooks of all pending future actions.

**Expected Result:**
- All baseline values recorded so they can be compared after the refund.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 06.2 — Issue a full refund from the WC order screen
**Steps:**
1. Open the parent order in **WooCommerce → Orders**.
2. Click **Refund**.
3. Enter `30.00`, reason `QA full refund of parent order`.
4. Click **Refund $30.00 via [Gateway]** (the button that does an actual gateway refund).

**Expected Result:**
- Refund processes successfully and a `-$30.00` row appears.
- Order notes record the gateway refund and the subscription linkage.
- Within seconds (refresh the page if needed), the order's payment status reflects fully refunded.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 06.3 — Subscription auto-cancelled
**Steps:**
1. Reload the subscription detail page.

**Expected Result:**
- Status is now **Cancelled** (red badge).
- `_end_date` is set to roughly the time of the refund (within ~1 minute).
- A subscription note records: "Subscription cancelled — Refund issued" (or equivalent), with the refund order ID referenced.
- Cancellation reason in admin matches the documented "Refund issued" wording.
- Cancelled-by is `system`.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 06.4 — All future scheduled actions removed
**Steps:**
1. Re-open **Tools → Scheduled Actions** filtered by the subscription ID.

**Expected Result:**
- Every future-dated `arraysubs_renewal`, `arraysubs_send_renewal_reminder`, `arraysubs_check_overdue_renewals` (per-subscription), and any other subscription-bound action is now either Cancelled or absent.
- No new actions are pending for this subscription.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 06.5 — Refund history surfaces correctly
**Steps:**
1. On the subscription detail page, view **Related Orders**.
2. View the customer-portal subscription page as `cust6@example.com`.

**Expected Result:**
- Admin Related Orders shows the order with `-$30.00` Refunded and a sub-row for the refund (date, amount, reason).
- A summary "Total Refunded" badge near the top of the section shows `$30.00`.
- Customer portal shows the **Refund History** section with this refund (date, order, amount, reason).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 06.6 — Customer notification
**Steps:**
1. Open the inbox / mail-trap for `cust6@example.com`.

**Expected Result:**
- A **Subscription Cancelled** email is received, naming the subscription. The body wording is consistent with the cancellation reason being a refund.
- Admin **Admin Subscription Cancelled** email is also received in the admin mailbox.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 06.7 — Idempotency: refund webhook does not double-process
**Steps:**
1. (Stripe-only sanity check) Wait ~30 seconds for Stripe's `charge.refunded` webhook to be received by the site.
2. Reload the subscription detail page and Related Orders.
3. Check subscription notes for any duplicate refund entries.

**Expected Result:**
- Only one refund record exists (the Two-Way Sync Guard prevents the gateway webhook from creating a duplicate).
- The cumulative refunded total is still exactly `$30.00`, not `$60.00`.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

## Regression / Cross-checks
- Verify the customer's My Account → Orders shows the order as Refunded with `$30.00` refunded.
- Verify the customer cannot re-cancel or re-pay an already-cancelled subscription from the portal — actions for non-Active/On-Hold subscriptions should be hidden.
- Restart the test by reactivating the subscription manually (via the admin status dropdown) and confirm a Subscription Reactivated email goes out — this ensures the lifecycle is not stuck.

## Sign-off
- Tester:
- Date:
- Browser & version:
- Notes:
