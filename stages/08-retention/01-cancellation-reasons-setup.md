# Stage 08 — Task 01: Cancellation Reasons Setup

| Key | Value |
|---|---|
| Stage | 08 — Retention Flow |
| Module | Retention Flow / Cancellation Reasons |
| Plugin Coverage | Free |
| Estimated Time | 15 min |
| Depends On | Stage 07 complete |

## Objective
Open **ArraySubs → Retention Flow** in the WordPress admin. Enable **Require Reason**, manage the predefined cancellation reasons (edit, reorder, add a custom one, ensure "Other" is present), save, and verify the customer-facing cancel modal shows the reason selector. Selecting the **Other** reason must reveal the free-text field.

## Pre-conditions
- Logged in as Administrator on one window.
- A second incognito window logged in as `cust1@example.com` to test the customer modal.
- General Settings → Customer Actions → Allow Cancellation: ON. Cancel Immediately can be either; the modal layout is identical.
- A spare Active subscription exists for `cust1@example.com` (call it Subscription S1) — do not actually complete the cancellation in this task.

## Test Data
- New custom reason key: `shipping_issues`
- New custom reason label: `Shipping or delivery problems`

## Sub-Tasks

### Sub-Task 01.1 — Open Retention Flow page
**Steps:**
1. As Administrator, click **ArraySubs → Retention Flow** in the WordPress sidebar.
2. Wait for the page to load.

**Expected Result:**
- The Retention Flow page renders without console errors.
- Two sections are visible: **Cancellation Reasons** and **Retention Offers**.
- A **Save Changes** button is at the bottom.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 01.2 — Enable Require Reason
**Steps:**
1. In the Cancellation Reasons section, locate the **Require Reason** toggle.
2. Set it to ON.
3. Click **Save Changes**.

**Expected Result:**
- A success toast confirms the save.
- After page refresh, the toggle remains ON.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 01.3 — Verify default reasons present
**Steps:**
1. Scroll the reasons list and read each row.

**Expected Result:**
- The 7 default reasons are present with these key/label pairs:
  - `too_expensive` / Too expensive
  - `not_using` / Not using it enough
  - `found_alternative` / Found a better alternative
  - `missing_features` / Missing features I need
  - `technical_issues` / Technical issues
  - `temporary_pause` / Just need a temporary break
  - `other` / Other
- Each row has a Reason Key field, Display Label field, drag handle, and delete button.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 01.4 — Add a custom reason
**Steps:**
1. Click **Add Reason**.
2. Enter Reason Key: `shipping_issues`.
3. Enter Display Label: `Shipping or delivery problems`.
4. Drag the new row to be the second-to-last (just above `Other`).
5. Click **Save Changes**.

**Expected Result:**
- The new reason appears in the list.
- After page refresh, the row order is preserved.
- A success toast confirms the save.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 01.5 — Verify reason selector on customer modal
**Steps:**
1. Switch to the customer browser window.
2. Open Subscription S1's detail page.
3. Click **Cancel Subscription**.

**Expected Result:**
- The Cancel modal opens with a reason selector (dropdown or radio list).
- All 8 reasons are listed in the order configured (defaults + the new `Shipping or delivery problems`).
- A **Keep Subscription** button and a **Continue** button are visible.
- The Continue button is disabled until a reason is chosen (because Require Reason is ON).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 01.6 — Other reason reveals free-text field
**Steps:**
1. In the reason selector, choose **Other**.

**Expected Result:**
- A free-text textarea appears below the selector for additional details.
- The Continue button becomes enabled (a reason is selected).
- Type a few characters in the textarea — they are accepted, no validation error.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 01.7 — Close modal without cancelling
**Steps:**
1. Click **Keep Subscription**.

**Expected Result:**
- The modal closes.
- The subscription remains Active with no pending cancellation. Subscription S1 is preserved for later tasks.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

## Regression / Cross-checks
- Confirm the warning text in the cancel modal aligns with the current Cancel Immediately setting (immediate vs end-of-period wording).
- Verify on the My Subscriptions list that Subscription S1 still shows as Active.

## Sign-off
- Tester:
- Date:
- Browser & version:
- Notes:
