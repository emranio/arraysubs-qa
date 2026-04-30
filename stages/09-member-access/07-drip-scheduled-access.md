# Stage 09 — Task 07: Drip / Scheduled Access (7-Day Unlock)

| Key | Value |
|---|---|
| Stage | 09 — Member Access & Restriction Rules |
| Module | Member Access → Post Types (Schedule) + Content Restriction |
| Plugin Coverage | Free |
| Estimated Time | 30 min |
| Depends On | 03-post-type-rules.md |

## Objective
Configure a Post Type rule for a single test post that unlocks 7 days after the subscriber's earliest qualifying subscription start date. Verify that before the 7 days have elapsed the post is locked, and after a 7-day time-travel the post is unlocked. Document the time-travel approach used so the test is reproducible on every environment.

## Pre-conditions
- A published page or post called **Drip Test Post** with body text "DRIP CONTENT UNLOCKED OK".
- `member1@example.com` is the test subscriber. The current ArraySubs subscription record's `start_date` is known and has been recorded (visible at **ArraySubs → Subscriptions → [subscription] → Start Date**).
- One of three time-travel approaches is available (pick before starting and document in Sign-off):
  - **Approach 1 — Adjust subscription start date**: Edit the subscription record and set `start_date` to a value 8 days in the past via wp-admin or WP-CLI (`wp post meta update <id> _arraysubs_start_date '2025-XX-XX 00:00:00'`).
  - **Approach 2 — Adjust system clock**: SSH to the isolated test server and run `sudo date -s '+8 days'`. Verify with `date`. Restore at end.
  - **Approach 3 — Apply filter override**: Add a tester-only mu-plugin snippet using `arraysubs_drip_now_timestamp` (or similar filter) to override the "current time" used by the schedule check.

## Test Data
- Post: **Drip Test Post** (slug `drip-test-post`)
- Schedule: 7 days
- Schedule unit: `days`
- IF condition: Has Active Subscription to **Pro Plan**
- Action when blocked: `Message`

## Sub-Tasks

### Sub-Task 07.1 — Create the drip rule
**Steps:**
1. Go to **ArraySubs → Member Access → Post Types**.
2. Click **Add Rule**.
3. Name: `Drip Test — 7 day unlock`.
4. **TARGET:**
   - Target Type: `Specific Posts/Pages`.
   - Search and select **Drip Test Post**.
5. **IF conditions:** Has Active Subscription to **Pro Plan**.
6. **THEN:** Action = `Message`, Message = `This lesson unlocks 7 days after your subscription start date. Check back soon.`
7. **Schedule:** Toggle ON. Delay value `7`. Delay unit `days`.
8. Archive Behavior: `Show with lock`.
9. Save.

**Expected Result:**
- Rule listed, schedule indicator shows "7 days" next to the rule name. Toggle enabled.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 07.2 — Confirm post is locked BEFORE 7 days
**Steps:**
1. Confirm `member1@example.com`'s subscription start date is within the past 0–6 days. If not, edit the subscription start date back to today (or 1 day ago) so the schedule is not yet satisfied.
2. Log in as `member1@example.com`.
3. Navigate to **Drip Test Post**.

**Expected Result:**
- Body text "DRIP CONTENT UNLOCKED OK" is NOT shown.
- The schedule message "This lesson unlocks 7 days after your subscription start date. Check back soon." is rendered.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 07.3 — Apply chosen time-travel approach
**Steps:**
1. Apply Approach 1, 2, or 3 from the Pre-conditions. Record the exact command, snippet, or admin action in Sign-off.
2. After the time travel is applied, refresh the rule evaluation cache if needed:
   - Log out and back in as `member1@example.com` (login clears discount/access caches per the manual).
3. Spot-check the rule list at **ArraySubs → Member Access → Post Types** to confirm the rule is still enabled.

**Expected Result:**
- Time travel applied successfully. The "current effective time" for rule evaluation is now ≥ subscription_start + 7 days.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 07.4 — Confirm post is unlocked AFTER 7 days
**Steps:**
1. As `member1@example.com`, navigate to **Drip Test Post**.
2. Inspect the body.

**Expected Result:**
- Body text "DRIP CONTENT UNLOCKED OK" is fully visible.
- The schedule message is no longer shown.
- No restriction banner.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 07.5 — Confirm non-subscriber is still locked regardless of time
**Steps:**
1. Log in as `nonmember@example.com`.
2. Navigate to **Drip Test Post**.

**Expected Result:**
- The default global restriction message is shown (because `nonmember` does not pass the IF condition — the schedule is irrelevant when conditions fail).
- Body text "DRIP CONTENT UNLOCKED OK" is NOT shown.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 07.6 — Reset the test environment
**Steps:**
1. If Approach 2 was used: SSH and run `sudo timedatectl set-ntp true` (or equivalent) to restore correct time.
2. If Approach 1 was used: restore `member1@example.com`'s subscription start date to its real value.
3. If Approach 3 was used: remove the mu-plugin snippet.
4. Disable the Drip Test rule (toggle off) so it does not interfere with later stages.
5. Confirm wp-admin loads cleanly and dashboard "current date" shows today's actual date.

**Expected Result:**
- Server time, subscription start date, and any filters are back to baseline. Rule disabled.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

## Regression / Cross-checks
- Confirm a paused subscription does NOT advance the drip clock (re-test by pausing subscription before time travel — the drip should remain locked because paused is not `active`/`trial`).
- Confirm if `member1@example.com` has multiple qualifying subscriptions, the **earliest** start date is used (per the manual's "Which Subscription Counts" reference).
- Confirm Schedule does NOT appear on Role Mapping rules or Login Limit rules (those rule types do not support schedules).

## Sign-off
- Tester:
- Date:
- Browser & version:
- Time-travel approach used (Approach 1 / 2 / 3):
- Exact command or snippet applied:
- Subscription start_date before / after:
- Notes:
