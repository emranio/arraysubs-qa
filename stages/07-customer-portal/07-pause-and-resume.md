# Stage 07 — Task 07: Pause Subscription and Resume

| Key | Value |
|---|---|
| Stage | 07 — Customer Portal Self-Service |
| Module | Customer Portal / Pause Subscription (Vacation Mode) |
| Plugin Coverage | Free |
| Estimated Time | 15 min |
| Depends On | 02 — View Subscription detail, 06 — Skip next renewal (no overlapping skip) |

## Objective
On an Active subscription, pause it for 30 days with a reason, observe the on-hold status with the ⏸ Subscription Paused indicator and auto-resume scheduling, then click **Resume Now** before the auto-resume date and verify the next-payment-date is recalculated correctly.

## Pre-conditions
- **General Settings → Pause Subscription → Enable Pause:** Enabled.
- **General Settings → Pause Subscription → Customer Can Pause:** Enabled.
- **Maximum Pause Duration** set to at least 30 days.
- "Require Pause Reason" toggle set per your store config — record which it is before testing.
- `cust1@example.com` owns Subscription H — an Active Basic Monthly with no active skip and no pending cancellation.
- Original Next Payment date recorded.

## Test Data
- Pause duration: `30 days`
- Pause reason: `Going on vacation`
- Subscription H — Active Basic Monthly

## Sub-Tasks

### Sub-Task 07.1 — Open Pause prompt
**Steps:**
1. As `cust1@example.com`, open Subscription H's detail page.
2. In the Manage Your Subscription section, click **Pause Subscription**.

**Expected Result:**
- A pause prompt / modal opens with a duration input (in days) and an optional or required reason field (depending on settings).
- A confirm button is present.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 07.2 — Submit pause for 30 days
**Steps:**
1. Enter `30` in the duration field.
2. Enter `Going on vacation` in the reason field.
3. Click the confirm button.

**Expected Result:**
- A success notice reads exactly: **"Subscription paused. Will auto-resume on [date]."** where `[date]` is today + 30 days.
- The status badge updates to **On-Hold** (yellow).
- The page now shows:
  - The ⏸ icon with the text **"Subscription Paused"**.
  - The pause start date (today).
  - The scheduled resume date (today + 30 days).
  - A **Resume Now** button.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 07.3 — Next payment date shifted
**Steps:**
1. Inspect the Next Payment row in the overview table.

**Expected Result:**
- The Next Payment date is shifted forward by 30 days from the originally recorded value (i.e. the pause duration is added to the billing timeline).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 07.4 — Renewal processing suspended in Action Scheduler
**Steps:**
1. As Administrator, open **Tools → Scheduled Actions**.
2. Filter Pending and search by Subscription H's ID.

**Expected Result:**
- A scheduled auto-resume action is queued for the resume date (record the exact hook name, e.g. `arraysubs_subscription_auto_resume`).
- No renewal action is scheduled inside the pause window.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 07.5 — Resume Now early
**Steps:**
1. Back in the customer browser, click **Resume Now** on Subscription H.
2. Confirm any dialog that appears.

**Expected Result:**
- A success notice confirms the resume (record exact wording).
- Status badge returns to **Active** (green).
- The ⏸ Subscription Paused indicator disappears, replaced by the standard Skip / Pause buttons again.
- The Next Payment row is recalculated. Compare to: original next payment + (days actually paused). Verify the date matches the expected recalculation.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 07.6 — Auto-resume scheduling cleared
**Steps:**
1. As Administrator, re-check **Tools → Scheduled Actions** for Subscription H.

**Expected Result:**
- The previously scheduled auto-resume action is cancelled or marked complete (no longer pending).
- A normal renewal action is scheduled for the new Next Payment date.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 07.7 — Subscription notes audit
**Steps:**
1. Open Subscription H in the admin and inspect the notes timeline.

**Expected Result:**
- A note records the pause request with duration `30 days` and reason `Going on vacation`.
- A note records the manual resume with timestamp.
- Notes have appropriate author badges (Customer / System).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

## Regression / Cross-checks
- Confirm the My Subscriptions list shows the subscription with a yellow **On-Hold** badge while paused, then green **Active** after resume.
- Confirm Skip and Pause cannot overlap: while paused, the Skip Next Renewal control should be hidden or disabled.

## Sign-off
- Tester:
- Date:
- Browser & version:
- Notes:
