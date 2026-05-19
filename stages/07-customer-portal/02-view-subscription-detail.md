# Stage 07 — Task 02: View Subscription Detail

| Key | Value |
|---|---|
| Stage | 07 — Customer Portal Self-Service |
| Module | Customer Portal / View Subscription Page |
| Plugin Coverage | Both (Free + Pro) |
| Estimated Time | 20 min |
| Depends On | 01 — My Subscriptions list |

## Objective
From the My Subscriptions list, click into one subscription and verify the View Subscription detail page renders the complete overview table, the retention discount line (when present), the recurring coupon line (when present), the action buttons appropriate to the subscription's status and settings, and the supporting sections (Skip & Pause, Shipping, Related Orders, Refund History, Notes).

## Pre-conditions
- Task 01 passed.
- `cust1@example.com` has these specific subscriptions to test:
  - **Subscription A:** Active Basic Monthly with a 20% retention discount for 3 cycles (one cycle remaining, so the line `Discounted from $29.99 for the next 1 renewal(s).` appears).
  - **Subscription B:** Active Pro Monthly with a recurring coupon `HALFOFF3` applied (3 cycles, count initial — 2 remaining).
  - **Subscription C:** Active subscription on a physical product (so shipping section appears).
  - **Subscription D:** Lifetime Deal subscription (so "Lifetime Deal — No recurring payment" line shows).

## Test Data
- URL pattern: `/my-account/view-subscription/{id}/`

## Sub-Tasks

### Sub-Task 02.1 — Overview table on Subscription A (with retention discount)
**Steps:**
1. From the My Subscriptions list, click **View** on Subscription A.
2. Locate the overview table at the top of the page.
3. Read each row in order.

**Expected Result:**
- The table contains rows in this order: **Status**, **Product**, **Start Date**, **Next Payment**, **Recurring Amount**, **Payment Method**.
- The Status row shows a green **Active** badge.
- The Recurring Amount row displays the discounted price.
- Directly below the discounted amount the line reads: **"Discounted from $29.99 for the next 1 renewal(s)."**
- The Payment Method row shows the gateway name and a **Manage payment methods** link.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 02.2 — Coupon discount line on Subscription B
**Steps:**
1. Return to subscriptions list, click **View** on Subscription B.
2. Find the coupon discount row in the overview table (immediately below Recurring Amount).

**Expected Result:**
- A **Coupon Discount** row is visible.
- The text reads either `50% off (HALFOFF3)` (percentage coupon) or `$X.00 off (HALFOFF3)` (fixed coupon) — record exact text.
- Below the discount, a lifecycle note reads **"X renewal cycle(s) remaining"** with the correct remaining count.
- For an unlimited recurring coupon, the note reads **"Applied to all future renewals"**.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 02.3 — Lifetime subscription Next Payment text
**Steps:**
1. Return to list, click **View** on Subscription D (Lifetime).
2. Locate the Next Payment row.

**Expected Result:**
- The Next Payment row reads exactly: **"Lifetime Deal — No recurring payment"**.
- No End Date row is shown unless an end date has been explicitly set.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 02.4 — Action buttons rendered correctly
**Steps:**
1. Open Subscription A again.
2. Scroll to the **Subscription Actions** section below the overview table.
3. Note the buttons that appear.

**Expected Result:**
- Because Subscription A is **Active**, **Allow Cancellation** is on, and **Plan Switching** is on with linked products configured, both **Change Plan** and **Cancel Subscription** buttons are visible.
- No **Reactivate** button appears (Free does not render this in the portal template).
- The Subscription Actions section is present (not hidden).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 02.5 — Manage Your Subscription (Skip / Pause) section
**Steps:**
1. Still on Subscription A, scroll to the **Manage Your Subscription** section below Subscription Actions.
2. Verify both controls are visible.

**Expected Result:**
- A **Skip Next Renewal** button is visible.
- A **Pause Subscription** button is visible.
- Neither shows a current "Skipping" or "Subscription Paused" indicator (no active skip or pause yet).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 02.6 — Shipping section on Subscription C
**Steps:**
1. Open Subscription C (physical product).
2. Scroll to the Shipping Address section.

**Expected Result:**
- The current shipping address is displayed in standard mailing-address format.
- A line reads either **"Shipping is charged on each renewal order."** (recurring shipping) or **"Shipping was charged on the initial order only."** (one-time shipping) — record which one.
- An **Update Shipping Address** button is visible (assuming next payment is more than 3 days away).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 02.7 — Related Orders, Refund History, Notes
**Steps:**
1. Continue scrolling on Subscription A.
2. Locate Related Orders table, Refund History (if any refunds exist), and Subscription Notes.

**Expected Result:**
- Related Orders table columns are: **Order**, **Date**, **Status**, **Total**, **Actions**.
- A **View** link appears in Actions for each order; for unpaid renewal invoices a **Pay** link is also present.
- Refund History table appears only when at least one refund exists, with columns **Date**, **Order**, **Amount**, **Reason**.
- Subscription Notes at the bottom show author badges (System / Customer / Staff) and timestamps.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

## Regression / Cross-checks
- Confirm DevTools Network panel does not show 4xx/5xx for any portal endpoint while loading the detail page.
- Verify that for a Cancelled subscription, the **Subscription Actions** section is hidden entirely (it only appears for Active or On-Hold).

## Sign-off
- Tester:
- Date:
- Browser & version:
- Notes:
