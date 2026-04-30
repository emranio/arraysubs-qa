# Stage 09 — Task 01: Role Mapping (Pro Plan → Pro Member)

| Key | Value |
|---|---|
| Stage | 09 — Member Access & Restriction Rules |
| Module | Member Access → Role Mapping |
| Plugin Coverage | Free |
| Estimated Time | 25 min |
| Depends On | Stage 06 lifecycle tasks (subscriber lifecycle states tested) |

## Objective
Verify that a Role Mapping rule binding the **Pro Plan** subscription product to a custom WordPress role **Pro Member** correctly assigns the role when a customer's subscription becomes active, and removes the role when the subscription is cancelled. This validates the bridge between ArraySubs subscription status and WordPress' native role system, including the Fallback Role behavior.

## Pre-conditions
- Logged in as administrator at `/wp-admin/`.
- A code snippet, role-management plugin (e.g., User Role Editor), or WP-CLI access available to create the custom role `pro_member` with display name **Pro Member** before the test starts. If using WP-CLI: `wp role create pro_member "Pro Member"`.
- Customer `nonmember@example.com` exists and currently has the role `customer` only.
- Customer `member1@example.com` exists and currently has an Active subscription to **Pro Plan** (carry-over from Stage 06). For the cancel test we will use this customer.
- Pro Plan product ID recorded from Stage 03 (note in Test Data below).

## Test Data
- Custom role slug: `pro_member`
- Custom role display name: `Pro Member`
- Subscription product: **Pro Plan** (ID: ____ — record before starting)
- Test customer for new-subscription test: `nonmember@example.com`
- Test customer for cancel test: `member1@example.com`
- Fallback role: `subscriber`

## Sub-Tasks

### Sub-Task 01.1 — Create the custom role
**Steps:**
1. Open a terminal and run `wp role create pro_member "Pro Member"` on the test server (or use a role-management plugin).
2. In wp-admin, go to **Users → All Users**, click any test user, and scroll to the **Role** dropdown.
3. Confirm **Pro Member** appears in the dropdown list.
4. Do not assign it manually — close the user without saving.

**Expected Result:**
- The role `pro_member` exists with display name `Pro Member` and is selectable in the WordPress role dropdown.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 01.2 — Create the Role Mapping rule
**Steps:**
1. Go to **ArraySubs → Member Access → Role Mapping**.
2. Click **Add Rule**.
3. Set the rule name to `Pro Plan grants Pro Member role`.
4. Under **IF conditions**, choose **Has Active Subscription** and select product **Pro Plan**.
5. Under **THEN**:
   - **Add Roles:** select `Pro Member`.
   - **Remove Roles:** leave empty.
   - **On Hold Behavior:** select `Keep roles`.
   - **Fallback Role:** select `Subscriber`.
6. Click **Save**.
7. Confirm the rule appears in the Role Mapping list with status enabled.

**Expected Result:**
- A row labelled "Pro Plan grants Pro Member role" appears with the Add Roles column showing `Pro Member` and the toggle in the enabled position.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 01.3 — Trigger role assignment via new subscription
**Steps:**
1. Open an incognito browser window. Go to the **Pro Plan** product page.
2. Add to cart and check out as `nonmember@example.com` (log in during checkout).
3. Use a successful test gateway (Stripe test card `4242 4242 4242 4242` if Pro is active, otherwise the Manual / Cheque gateway with admin marking the order Completed).
4. Once the order is Complete, in the admin, go to **ArraySubs → Subscriptions** and confirm the subscription is **Active**.
5. Go to **Users → All Users**, open `nonmember@example.com`'s profile, and inspect the **Role** field.

**Expected Result:**
- The user `nonmember@example.com` now has role **Pro Member** assigned (in addition to or in place of `customer`, depending on starting roles).
- The ArraySubs activity log (**ArraySubs → Audits → Activity**) shows a "Role assigned" entry for this user with role `pro_member`.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 01.4 — Trigger role removal via cancellation
**Steps:**
1. Still in admin, go to **ArraySubs → Subscriptions**.
2. Open `member1@example.com`'s active Pro Plan subscription.
3. Change the status to **Cancelled** using the status dropdown and click **Update**.
4. Wait 5 seconds for the role manager to react, then refresh.
5. Go to **Users → All Users → member1@example.com** and inspect the **Role** field.

**Expected Result:**
- The role `pro_member` is no longer assigned to `member1@example.com`.
- If the user has no other roles after removal, the **Subscriber** fallback role is now assigned.
- Activity log records "Role removed" for `pro_member` and "Role assigned (fallback)" for `subscriber` if applicable.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 01.5 — Verify on-hold behavior keeps roles
**Steps:**
1. Use `nonmember@example.com` (still has Pro Member role from Sub-Task 01.3).
2. In **ArraySubs → Subscriptions**, change that subscription's status to **On Hold** and click **Update**.
3. Refresh the user profile at **Users → All Users → nonmember@example.com**.

**Expected Result:**
- The `pro_member` role is still present (because the rule's **On Hold Behavior** is `Keep roles`).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 01.6 — Cleanup
**Steps:**
1. Set the on-hold subscription back to **Active**.
2. Confirm `pro_member` role still on the user.
3. Leave the Role Mapping rule **enabled** for downstream tasks (Tasks 09.04, 09.05, 09.10 reference Pro Member role).

**Expected Result:**
- Subscription is Active again; user retains `pro_member` role; rule remains enabled in Role Mapping list.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

## Regression / Cross-checks
- Confirm a non-Pro-Plan subscriber (e.g., a customer subscribed to **Basic Plan**) does **not** get the `pro_member` role. Spot-check `member2@example.com`'s profile — they should have `customer` only, not `pro_member`.
- Confirm the WP user list filter at **Users → All Users → Pro Member** count matches the number of currently active Pro Plan subscribers.
- Confirm Stage 06 lifecycle behavior (status transitions) still functions — open one Active subscription and toggle through On Hold → Active without errors.

## Sign-off
- Tester:
- Date:
- Browser & version:
- Pro Plan product ID used:
- Notes:
