# Stage 07 — Task 11: Action Availability by Subscription Status

| Key | Value |
|---|---|
| Stage | 07 — Customer Portal Self-Service |
| Module | Customer Portal / Action Availability Matrix |
| Plugin Coverage | Both (Free + Pro) |
| Estimated Time | 25 min |
| Depends On | 03 through 10 (one subscription per status available) |

## Objective
Cycle through every relevant subscription status (Active, Trial, On-Hold, Pending, Cancelled, Expired) and verify the action availability matrix renders **exactly** as documented in the manual. Each status must show only the actions defined as available in the table — and no others.

## Pre-conditions
- All settings used through Tasks 03–10 returned to their store defaults: Allow Cancellation on, Allow Plan Switching on, Skip Renewal on (Customer Can Skip on), Pause Subscription on (Customer Can Pause on).
- `cust1@example.com` owns one subscription in each of these statuses (see Subscriptions A–N from prior tasks; reuse existing where possible):
  - **Active** — Subscription A or any active Basic Monthly with linked products.
  - **Trial** — Subscription with status Trial (e.g. a still-trialling Stage 03 trial product).
  - **On-Hold** — A subscription currently paused (recreate from Task 07 if needed) OR an admin-set On-Hold subscription.
  - **Pending** — A subscription whose initial payment hasn't completed (admin can set status to Pending).
  - **Cancelled** — Subscription E (Task 03) or any cancelled subscription.
  - **Expired** — Any subscription with status Expired (admin can set, or use a fixed-length subscription that has run its course).

## Reference Matrix (from manual — `self-service-actions.md`)
| Action | Active | Trial | On-Hold | Pending | Cancelled | Expired |
|---|---|---|---|---|---|---|
| Cancel | Yes | Yes | — | Yes | — | — |
| Undo scheduled cancel | Yes (if pending cancel) | — | — | — | — | — |
| Retention offers | Yes | Yes | — | — | — | — |
| Change plan | Yes | — | — | — | — | — |
| Skip renewal | Yes | Yes | — | — | — | — |
| Pause | Yes | Yes | — | — | — | — |
| Resume | — | — | Yes (if paused) | — | — | — |
| Update shipping | Yes | Yes | Yes | — | — | — |

## Sub-Tasks

### Sub-Task 11.1 — Active subscription
**Steps:**
1. As `cust1@example.com`, open the Active test subscription.
2. Inspect Subscription Actions, Manage Your Subscription, and Shipping Address sections.

**Expected Result:**
- **Cancel Subscription** button: visible.
- **Change Plan** button: visible (linked products configured).
- **Skip Next Renewal** button: visible.
- **Pause Subscription** button: visible.
- **Resume Now** button: NOT visible.
- **Update Shipping Address** button: visible (assuming physical product and outside cutoff).
- **Undo Cancellation**: not visible (no pending cancel currently).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 11.2 — Trial subscription
**Steps:**
1. Open the Trial test subscription.

**Expected Result:**
- **Cancel Subscription**: visible.
- **Change Plan**: NOT visible (Change Plan is Active-only per matrix).
- **Skip Next Renewal**: visible.
- **Pause Subscription**: visible.
- **Resume Now**: not visible.
- **Update Shipping Address**: visible (Trial is eligible).
- **Undo Cancellation**: not visible.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 11.3 — On-Hold subscription (paused)
**Steps:**
1. Open an On-Hold subscription that was paused via the customer portal (so it has the active pause metadata).

**Expected Result:**
- **Cancel Subscription**: NOT visible (per matrix On-Hold is not eligible).
- **Change Plan**: NOT visible.
- **Skip Next Renewal**: NOT visible.
- **Pause Subscription**: NOT visible (already paused).
- **Resume Now**: visible.
- **Update Shipping Address**: visible (On-Hold is eligible per shipping rules).
- **Undo Cancellation**: not visible.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 11.4 — Pending subscription
**Steps:**
1. Open the Pending test subscription.

**Expected Result:**
- **Cancel Subscription**: visible (Pending is eligible to cancel).
- **Change Plan**: NOT visible.
- **Skip Next Renewal**: NOT visible.
- **Pause Subscription**: NOT visible.
- **Resume Now**: NOT visible.
- **Update Shipping Address**: NOT visible (Pending not in the eligible status list).
- **Undo Cancellation**: NOT visible.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 11.5 — Cancelled subscription
**Steps:**
1. Open a Cancelled test subscription.

**Expected Result:**
- The Subscription Actions section is **not present** at all (only renders for Active or On-Hold).
- No action buttons appear: Cancel / Change Plan / Skip / Pause / Resume / Update Shipping / Undo Cancellation are all hidden.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 11.6 — Expired subscription
**Steps:**
1. Open an Expired test subscription.

**Expected Result:**
- Same as Cancelled: Subscription Actions section is not present, no action buttons of any kind appear.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 11.7 — Active with pending cancel (Undo state)
**Steps:**
1. Trigger an end-of-period cancel on a fresh Active subscription (per Task 04 flow), then open it.

**Expected Result:**
- Status remains **Active**.
- **Cancel Subscription**: NOT visible (a pending cancel already exists).
- **Undo Cancellation**: visible.
- **Change Plan**, **Skip Next Renewal**, **Pause Subscription**, **Update Shipping Address** behaviours per active status — record exactly which are shown.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

## Regression / Cross-checks
- The matrix in this task is the canonical reference: any deviation should be filed as a bug with a screenshot of the affected status/action.
- Cross-reference with Stage 02 settings: temporarily disable "Allow Plan Switching" and re-test the Active row — confirm the **Change Plan** button disappears, then re-enable.

## Sign-off
- Tester:
- Date:
- Browser & version:
- Notes:
