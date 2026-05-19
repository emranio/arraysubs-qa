# Stage 14 — Task 08: Status Change Side Effects (Dropdown vs. Cancel Button)

| Key | Value |
|---|---|
| Stage | Admin Subscription Management |
| Module | Admin SPA — status dropdown vs. Cancel Subscription button |
| Plugin Coverage | Free |
| Estimated Time | 25 min |
| Depends On | 14-03 (status dropdown works), 14-05 (notes panel) |

## Objective
Manually move a subscription from **Active → On Hold** through the status dropdown and confirm the lifecycle hooks fire (a system note is added, the on-hold meta is recorded). Then drive a **Cancellation** through two different paths — the status dropdown and the **Cancel Subscription** button on the detail page — and document the audit-trail gap: the dropdown does NOT capture a cancellation reason, while the Cancel button does. Both paths perform the same lifecycle cleanup, but only the button preserves the reason for analytics and retention reporting.

## Pre-conditions
- Logged in as Administrator.
- Two **Active** subscriptions are available (clones of the same product is fine), labelled **Sub-X** and **Sub-Y** for this task.
- Both subscriptions have at least one previous renewal so the audit context is meaningful.

## Test Data
- Sub-X: will be moved Active → On Hold → Cancelled via the **status dropdown**.
- Sub-Y: will be cancelled via the **Cancel Subscription** button with reason `Too expensive` and additional details `QA test`.

## Sub-Tasks

### Sub-Task 8.1 — Sub-X: Active → On Hold via status dropdown
**Steps:**
1. Open Sub-X edit screen.
2. From the inline status control, select **On Hold**.
3. Click **Change Status** and confirm the modal.
4. Wait for the page reload.
5. Open Sub-X's detail page and scroll to the **Subscription Notes** panel.

**Expected Result:**
- Status badge becomes **On Hold** (yellow).
- A new System note appears at the top of the notes panel logging the status change (e.g. "Status changed from Active to On Hold").
- Per the lifecycle manual, the `_on_hold_date` meta is recorded — visible because the timing of any future on-hold-to-cancelled grace period uses it. (We cannot inspect meta directly in the browser; the system note is the falsifiable check.)
- A "Subscription On Hold" customer email may be triggered (verify in your wp_mail capture if email logging is configured).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 8.2 — Sub-X: On Hold → Cancelled via status dropdown
**Steps:**
1. From Sub-X's edit screen, open the status dropdown again and select **Cancelled**.
2. Click **Change Status**.
3. Read the warning copy: "This will cancel the subscription immediately. All scheduled renewals will be removed. Continue?"
4. Confirm.
5. Wait for the reload, then open Sub-X's detail page.

**Expected Result:**
- Status badge becomes **Cancelled** (red).
- A System note is added documenting the status change.
- The detail page now shows a **Cancellation Details** card (per task 14-04 / detail-cards manual).
- **Crucially:** the Cancellation Reason field is **empty** or shows "—" because the dropdown does NOT capture a reason. This is the audit-trail gap.
- All future scheduled actions are removed (no future renewal in WooCommerce → Status → Scheduled Actions).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 8.3 — Sub-Y: cancellation via the Cancel Subscription button (Immediate)
**Steps:**
1. Open Sub-Y's detail page (it must still be Active).
2. In the page header, click **Cancel Subscription**.
3. In the modal, choose the **Cancel Immediately** radio.
4. Type `Too expensive` in the reason picker (or pick that label from the dropdown if it is select-based).
5. Type `QA test` in the additional details / comment box.
6. Click **Confirm Cancellation**.

**Expected Result:**
- Modal closes; Sub-Y status becomes **Cancelled**.
- Detail page now shows a **Cancellation Details** card with:
  - Cancellation Reason: `Too expensive`.
  - Additional Details: `QA test`.
  - Cancelled Date: today.
- A System note records the cancellation, and the reason is part of that note's structured data (so retention analytics can read it).
- A "Subscription Cancelled" customer email is queued.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 8.4 — Compare the two cancellation outcomes
**Steps:**
1. Open Sub-X's detail page side-by-side with Sub-Y's detail page (two tabs).
2. Look at the **Cancellation Details** card in each.
3. Look at the latest cancellation system note in each.

**Expected Result:**

| Field | Sub-X (dropdown) | Sub-Y (button) |
|---|---|---|
| Status | Cancelled | Cancelled |
| Cancellation Details card visible | Yes | Yes |
| Cancellation Reason | empty / `—` | `Too expensive` |
| Additional Details | empty | `QA test` |
| Cancelled Date | recorded | recorded |
| Future scheduled actions removed | Yes | Yes |
| Pending renewal order cancelled | Yes | Yes |
| Customer email sent | Yes | Yes |

- The cleanup is identical between the two paths — the only documented difference is the missing reason capture on Sub-X.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 8.5 — Document the audit-trail gap in Sub-X's notes panel
**Steps:**
1. On Sub-X's detail page, scroll to the Subscription Notes panel.
2. Click into the **Add Note** editor.
3. Add a Private note: `Audit-trail gap: this subscription was cancelled via the status dropdown, so no cancellation reason was captured. Customer-stated reason (from chat): not available.`
4. Save the note.

**Expected Result:**
- The note is saved as **Private** with the **Admin** badge.
- It appears at the top of the notes panel.
- This compensating note is the manual workaround for the dropdown audit gap.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 8.6 — Verify analytics impact (preview)
**Steps:**
1. Open **WooCommerce → Analytics → Retention** (covered in detail in Stage 16).
2. Look for the **Top Cancellation Reasons** breakdown for the period including these two cancellations.

**Expected Result:**
- Sub-Y contributes one count to the `Too expensive` reason bucket.
- Sub-X contributes to the "Unknown" / "No reason" bucket (or may not be counted in the reason breakdown at all, depending on how the report handles missing reasons).
- This demonstrates concretely how the dropdown gap distorts retention analytics.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 8.7 — Confirm hook-driven side effects (notes only, no meta inspection)
**Steps:**
1. Re-read the latest 10 entries in both Sub-X and Sub-Y notes panels.
2. Confirm each documented system event appears as a note.

**Expected Result:**
- Sub-X has notes: `Active → On Hold` change; `On Hold → Cancelled` change.
- Sub-Y has a single cancellation note with reason and additional details captured.
- Hook firing is observable indirectly through these structured note entries.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

## Regression / Cross-checks
- Reload both subscriptions in a fresh browser session — the cancelled state and the cancellation card persist.
- In **WooCommerce → Status → Scheduled Actions**, confirm no future `arraysubs_*` actions are queued for either subscription.
- Per the lifecycle manual: "For cancellations, always prefer the **Cancel Subscription** button over the status dropdown." This task should be reported alongside any internal docs warning admins about the dropdown gap.

## Sign-off
- Tester:
- Date:
- Browser & version:
- Sub-X ID (dropdown):
- Sub-Y ID (button):
- Notes:
