# Stage 09 — Task 10: Pause-State Access Behavior (None / Limited / Full)

| Key | Value |
|---|---|
| Stage | 09 — Member Access & Restriction Rules |
| Module | Member Access — Pause/On-Hold interactions |
| Plugin Coverage | Free |
| Estimated Time | 30 min |
| Depends On | 02-url-rules.md, 03-post-type-rules.md, 04-discount-rules.md |

## Objective
Verify that when a customer's subscription is in **Paused** state, three different access behaviors can be configured and observed: **None** (paused = no access), **Limited** (paused = access to a separate limited rule), and **Full** (paused = same access as active by including `paused` in the rule conditions). Confirm the customer's restricted-content access matches each setting exactly.

## Pre-conditions
- `member2@example.com` has a Paused subscription to **Pro Plan** (created in Stage 06 or by pausing `member1@example.com`'s subscription temporarily — note which approach in Sign-off).
- `member1@example.com` still has an Active Pro Plan subscription for comparison.
- Test page **Pause Test Page** at slug `pause-test-page`, body text "PAUSE TEST CONTENT OK".
- The Stage 09 Task 03 KB Articles rule is enabled.
- The Stage 09 Task 04 Discount rule is enabled.

## Test Data
- Test customer: `member2@example.com` (Paused Pro Plan)
- Test page: `/pause-test-page`
- Three rules will be created/modified in this task — clean up at end.

## Sub-Tasks

### Sub-Task 10.1 — Setup: Confirm paused subscription state
**Steps:**
1. Go to **ArraySubs → Subscriptions**. Filter by status `Paused`.
2. Confirm `member2@example.com` has a Pro Plan subscription with status `Paused`.
3. If not, take an Active Pro Plan subscription (from member2 or another customer) and pause it via the customer portal flow tested in Stage 07.

**Expected Result:**
- At least one paused Pro Plan subscription exists for the test customer.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 10.2 — Behavior NONE: paused customer is denied
**Steps:**
1. Create a new Post Type rule:
   - Name: `Pause Test — None (active only)`.
   - Target: Specific Posts → **Pause Test Page**.
   - IF: Has Active Subscription to **Pro Plan** (status filter = `active` only).
   - THEN: Action = `Message`, Message = `Active Pro Plan required.`
2. Save.
3. Log in as `member2@example.com` (paused).
4. Navigate to `/pause-test-page`.

**Expected Result:**
- Body "PAUSE TEST CONTENT OK" is NOT shown.
- Message `Active Pro Plan required.` is rendered.
- This confirms the default behavior — paused state is excluded from `active` condition.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 10.3 — Behavior FULL: include paused in conditions
**Steps:**
1. Edit the rule from 10.2.
2. Update the IF condition's status filter to include both `active` AND `paused` (multi-select).
3. Save.
4. Reload `/pause-test-page` as `member2@example.com`.

**Expected Result:**
- Body "PAUSE TEST CONTENT OK" is now visible.
- No restriction message.
- This proves explicit inclusion of `paused` in the status filter grants full access during pause.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 10.4 — Behavior LIMITED: separate rules for active vs paused
**Steps:**
1. Revert 10.2's rule to status filter `active` only.
2. Create a SECOND Post Type rule:
   - Name: `Pause Test — Limited (paused-only message)`.
   - Target: Specific Posts → **Pause Test Page**.
   - IF: Has Subscription with status `paused` to **Pro Plan**.
   - THEN: Action = `Message`, Message = `You are on pause — please resume your subscription to access this content. <a href="/my-account/subscriptions/">Resume now</a>.`
3. Save.
4. Reload `/pause-test-page` as `member2@example.com`.

**Expected Result:**
- The custom limited message including the "Resume now" link is shown.
- Body "PAUSE TEST CONTENT OK" is NOT shown.
- This proves a paused-specific rule can deliver a limited / informational experience without granting full access.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 10.5 — Active subscriber unaffected
**Steps:**
1. Log in as `member1@example.com` (Active Pro Plan).
2. Navigate to `/pause-test-page`.

**Expected Result:**
- Body "PAUSE TEST CONTENT OK" is shown.
- Neither the limited message nor the active-only message is shown.
- The active rule from 10.2 grants access; the paused rule from 10.4 does NOT match.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 10.6 — Verify Discount Rule does NOT apply to paused
**Steps:**
1. As `member2@example.com` (paused), navigate to **Members Tee** product page.
2. Inspect the price.

**Expected Result:**
- Regular price `$20.00` is shown — no member discount.
- Confirms that for Discount Rules, `paused` subscriptions are not considered "active" (per the manual: "No discounts (not an active subscription)").

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 10.7 — Verify Role Mapping behavior on paused
**Steps:**
1. In wp-admin, open **Users → All Users → member2@example.com**.
2. Inspect the Role field.

**Expected Result:**
- The `pro_member` role is still present (per the manual: "Paused — No explicit role changes — roles assigned during the active period remain in place").
- This is expected behavior. Document role-list verbatim in Sign-off.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 10.8 — Cleanup
**Steps:**
1. Delete or disable the two pause test rules created in 10.2 and 10.4.
2. If `member1`'s subscription was paused for setup purposes, resume it.
3. Confirm rule list at **ArraySubs → Member Access → Post Types** matches pre-task baseline plus only the Stage 09 Task 03 rule.

**Expected Result:**
- Pause test rules removed. Other Stage 09 rules remain enabled.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

## Regression / Cross-checks
- Confirm if the rule's IF status set is `[active, trial]` (the default), paused subscribers are denied. If it includes `paused`, they are granted access. There is no separate "pause-state setting" — pause behavior is controlled by which statuses are listed in the rule conditions.
- Confirm the same rule conditions principle applies to URL Rules and Ecommerce Rules (spot-check by adding `paused` to the URL rule's conditions in Task 02 if needed).
- Confirm Role Mapping has its own `On Hold Behavior` setting (separate from `paused`) — Keep roles vs Remove roles for `on-hold`. Paused is always preserved.

## Sign-off
- Tester:
- Date:
- Browser & version:
- Source of paused subscription (member2 baseline / member1 paused for setup):
- Roles list for member2 verified verbatim:
- Notes:
