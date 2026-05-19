# Stage 08 — Task 03: Pause Offer

| Key | Value |
|---|---|
| Stage | 08 — Retention Flow |
| Module | Retention Flow / Pause Offer |
| Plugin Coverage | Free |
| Estimated Time | 15 min |
| Depends On | 01 — Cancellation reasons setup |

## Objective
Configure a 30-day pause offer triggered by **Just need a temporary break**. As a customer, cancel with that reason; the **"Before You Go..."** modal must show the pause card. Accept it. Verify the subscription transitions to On-Hold, an auto-resume is scheduled, and the next-payment-date is shifted forward by 30 days.

## Pre-conditions
- 01 — Cancellation reasons setup PASSED.
- Subscription R2 — Active Basic Monthly belonging to `cust1@example.com`.
- Original next-payment-date for R2 recorded.

## Test Data
- Maximum Pause Duration: `30 days`
- Trigger reason: `Just need a temporary break`

## Sub-Tasks

### Sub-Task 03.1 — Configure the pause offer
**Steps:**
1. As Administrator, open **ArraySubs → Retention Flow**.
2. In the Retention Offers section, find **Pause Offer** and set:
   - **Enable Pause Offer:** ON
   - **Show for these reasons:** select only `temporary_pause` (Just need a temporary break)
   - **Maximum Pause Duration (days):** `30`
   - Leave Custom Headline / Description blank.
3. Click **Save Changes**.

**Expected Result:**
- A success toast confirms the save.
- After refresh, settings persist.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 03.2 — Trigger cancellation with matching reason
**Steps:**
1. As `cust1@example.com`, open Subscription R2's detail page.
2. Click **Cancel Subscription**.
3. Select reason **Just need a temporary break**.
4. Click **Continue**.

**Expected Result:**
- The **"Before You Go..."** modal appears.
- A pause offer card is displayed with default headline **"Need a Break?"** and description **"Take a break for up to 30 days. Your subscription will automatically resume."**
- An **Accept** button is on the card.
- The Discount card is NOT shown (its trigger reason does not match).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 03.3 — Accept the pause offer
**Steps:**
1. Click **Accept** on the pause card.

**Expected Result:**
- The retention modal closes.
- A success notice confirms the pause (record exact wording).
- The subscription transitions to **On-Hold** (yellow badge).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 03.4 — On-Hold indicator and resume date
**Steps:**
1. Refresh Subscription R2.

**Expected Result:**
- Status badge is **On-Hold**.
- A note about the scheduled resume date is shown — exactly today + 30 days.
- The next payment date is shifted forward by 30 days from the originally recorded value.
- Per the manual: the customer does **not** see a manual Resume Now button for retention pauses (resume happens automatically) — record the actual UI behaviour.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 03.5 — Auto-resume scheduled in Action Scheduler
**Steps:**
1. As Administrator, open **Tools → Scheduled Actions**.
2. Filter Pending and search by Subscription R2's ID.

**Expected Result:**
- An auto-resume action is scheduled for the resume date (record the hook name, e.g. `arraysubs_subscription_auto_resume`).
- No renewal action is scheduled inside the pause window.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 03.6 — Subscription notes and analytics
**Steps:**
1. Open Subscription R2 in admin.
2. Inspect notes and the activity log in **WooCommerce → Analytics → Retention**.

**Expected Result:**
- A subscription note records: pause via retention offer, duration 30 days, reason **Just need a temporary break**.
- An "offer shown" and an "offer accepted" entry exist in the Retention activity log for this subscription.
- The pending cancellation (if any was being staged) is cleared.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 03.7 — Optional simulate auto-resume
**Steps:**
1. As Administrator, in **Tools → Scheduled Actions**, locate the auto-resume action and click **Run** to execute it now.

**Expected Result:**
- Status returns to **Active**.
- Pause metadata is cleared.
- Next payment date is recalculated and a new renewal action is scheduled.
- A note confirms the auto-resume completed.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

## Regression / Cross-checks
- Confirm the My Subscriptions list shows Subscription R2 with the **On-Hold** badge while paused, then **Active** after auto-resume.
- The pause offer through retention should NOT count against the standalone customer-side pause feature toggle for future eligibility checks.

## Sign-off
- Tester:
- Date:
- Browser & version:
- Notes:
