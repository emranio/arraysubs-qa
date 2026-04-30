# Stage 07 — Task 06: Skip Next Renewal and Undo Skip

| Key | Value |
|---|---|
| Stage | 07 — Customer Portal Self-Service |
| Module | Customer Portal / Skip Renewal |
| Plugin Coverage | Free |
| Estimated Time | 15 min |
| Depends On | 02 — View Subscription detail |

## Objective
With Skip Renewal enabled, take an Active subscription, click **Skip Next Renewal**, choose a number of cycles between 1 and 3, confirm, observe the "Skipping X cycle(s)" indicator with request date and the **Undo Skip** button, then click **Undo Skip** and verify the next-payment-date is restored to its original value.

## Pre-conditions
- **General Settings → Skip & Renewal → Enable Skip Renewal:** Enabled.
- **General Settings → Skip & Renewal → Customer Can Skip:** Enabled.
- **General Settings → Skip & Renewal → Maximum Skip Cycles:** Set to at least `3`.
- `cust1@example.com` owns Subscription G — an Active Basic Monthly with a next payment date that is clearly known (record the date before testing).
- Subscription G has no active pause and no active skip currently.

## Test Data
- Subscription G — Active Basic Monthly
- Original next payment date (record): __________
- Cycles to skip: `2`

## Sub-Tasks

### Sub-Task 06.1 — Verify Skip control visibility
**Steps:**
1. As `cust1@example.com`, open Subscription G's detail page.
2. Scroll to the **Manage Your Subscription** section.

**Expected Result:**
- A **Skip Next Renewal** button is visible.
- No current "Skipping" indicator is shown.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 06.2 — Open the Skip prompt
**Steps:**
1. Click **Skip Next Renewal**.

**Expected Result:**
- A prompt / modal opens asking how many cycles to skip.
- The selectable range is 1 up to the configured maximum (3).
- A confirm button (e.g. **Confirm** or **Skip**) is present alongside a Cancel option.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 06.3 — Confirm skipping 2 cycles
**Steps:**
1. Choose **2** cycles.
2. Click the confirm button.

**Expected Result:**
- A success notice reads exactly: **"Successfully skipped 2 renewal cycle(s)."**
- After refresh, the Manage Your Subscription section now shows:
  - The ⏭ icon with the text **"Skipping 2 cycle(s)"**.
  - The date the skip was requested (today's date).
  - An **Undo Skip** button.
- The **Skip Next Renewal** button is no longer offered while the skip is active.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 06.4 — Next payment date shifted forward
**Steps:**
1. Look at the overview table's Next Payment row.
2. Compare to the originally recorded date.

**Expected Result:**
- The Next Payment date has moved forward by exactly 2 billing cycles (e.g. if the original was 1 May, the new date is 1 July for a monthly product).
- Status remains **Active** (skipping does not change status).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 06.5 — Undo Skip
**Steps:**
1. Click **Undo Skip**.
2. If a confirmation prompt appears, accept it.

**Expected Result:**
- A success notice confirms the undo (record exact wording).
- The **Skipping 2 cycle(s)** indicator disappears.
- The Next Payment date returns to the originally recorded value.
- The **Skip Next Renewal** button is visible again, allowing a new skip to be initiated.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 06.6 — Pause and Skip mutual exclusion
**Steps:**
1. Initiate a Skip again for 1 cycle (do not undo this time yet).
2. Look at the **Pause Subscription** button.

**Expected Result:**
- While a skip is active, the Pause control either is hidden or shows a disabled state — confirming skip and pause cannot overlap. Record exact behaviour observed.
- Undo the skip to leave Subscription G in a clean state for later tasks.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

## Regression / Cross-checks
- Verify in **Tools → Scheduled Actions** that the renewal action's scheduled date moves to match the new Next Payment date when skipping, and returns to the original date after Undo Skip.
- Confirm a subscription note is added on both Skip and Undo Skip events.

## Sign-off
- Tester:
- Date:
- Browser & version:
- Notes:
