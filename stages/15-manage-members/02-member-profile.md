# Stage 15 — Task 02: Member Profile

| Key | Value |
|---|---|
| Stage | Manage Members |
| Module | Manage Members — profile card and Login as Customer |
| Plugin Coverage | Pro |
| Estimated Time | 25 min |
| Depends On | 15-01 (search works), Toolkit "Login as User" enabled |

## Objective
Open a customer's full Manage Members profile and verify the four areas of the profile card (identity & contact, action buttons, stats grid, quick links). Then exercise the **Login as Customer** impersonation: switch to the customer's frontend session, verify the impersonation banner with the **Go back as {admin name}** button, and switch back successfully.

## Pre-conditions
- Logged in as Administrator with `edit_user` capability.
- Toolkit setting "Login as User" is enabled.
- Target customer has: a Gravatar (optional), a billing phone in WooCommerce, a registration date older than today, a non-default WordPress role (e.g. Customer).
- Another admin account exists so the negative test (impersonate-an-admin blocked) can be exercised.

## Test Data
- Target customer: `customer1@arraysubs.test`.
- Other admin (for negative test): `admin2@arraysubs.test`.

## Sub-Tasks

### Sub-Task 2.1 — Profile card identity & contact
**Steps:**
1. From Manage Members, search and select `customer1@arraysubs.test`.
2. Read every visible field on the profile card.

**Expected Result:**
- Avatar (Gravatar tied to the email).
- Full name (from the WordPress user profile).
- Username displayed as `@username`.
- Email displayed as a clickable mailto link.
- Phone — the WooCommerce billing phone, if set.
- Joined date — the WordPress registration date, formatted in the site locale.
- Role badges — every role assigned to the user.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 2.2 — Profile card action buttons
**Steps:**
1. Locate the action buttons in the top-right of the profile card.
2. Hover each one to confirm tooltips.

**Expected Result:**
- Three buttons: **Login as Customer**, **Edit User**, **Clear**.
- **Login as Customer** is enabled (because Toolkit is on, target is not an admin, and admin has the right capability).
- **Edit User** opens the WordPress user edit page in a new tab when clicked (test in sub-task 2.3 below — don't navigate yet).
- **Clear** deselects the current member and returns to the search empty state.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 2.3 — Edit User opens the WP user edit page
**Steps:**
1. Click **Edit User**.
2. A new browser tab should open.

**Expected Result:**
- New tab opens at `wp-admin/user-edit.php?user_id={user_id}`.
- The tab loads the WordPress user edit page for the target customer.
- Close the tab and return to Manage Members.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 2.4 — Stats grid (six cards)
**Steps:**
1. Below the profile card, locate the stats grid.
2. Read each card.

**Expected Result:**
- Six cards in this order (or grouped per the build): **Total Spent** (blue), **Total Orders** (cyan), **Active Subscriptions** (green), **Total Subscriptions** (gray), **Store Credit** (purple), **Total Refunds** (red).
- Currency values use the store's WooCommerce format (correct symbol, decimal separator, thousand separator).
- Numeric values match what is observable in WooCommerce → Customers and ArraySubs → Subscriptions for this user. (Detailed metric verification is in task 15-04.)

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 2.5 — Quick links cards
**Steps:**
1. Below the stats grid, locate the three quick-link cards.
2. Hover each link without clicking yet.

**Expected Result:**
- Three quick links visible: **View Orders**, **Manage Store Credit**, **Show Features**.
- View Orders link target points to **WooCommerce → Orders** with an email filter (will open in a new browser tab when clicked).
- Manage Store Credit link target points to **ArraySubs → Store Credit** scoped to this user (same tab navigation).
- Show Features link target points to **ArraySubs → Subscriptions → Feature Log** with the user_id parameter (same tab navigation).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 2.6 — Login as Customer happy path
**Steps:**
1. Click **Login as Customer**.
2. Wait for redirect.

**Expected Result:**
- The browser redirects to the site frontend.
- A notification bar at the top of every frontend page reads "You are logged in as **{customer name}**" and contains a **Go back as {admin name}** button.
- The WordPress admin bar (if visible to that role) shows a switch-back link as well.
- Visit the customer's `/my-account/subscriptions` page and confirm you see exactly the customer's subscriptions.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 2.7 — Switch back to admin
**Steps:**
1. From any frontend page during the impersonation, click the **Go back as {admin name}** button in the notification bar.
2. Wait for redirect.

**Expected Result:**
- You are returned to the wp-admin context as the admin user.
- The page you land on is the page you were on before impersonating (or the Manage Members profile).
- The frontend customer's session token is destroyed.
- A second click of **Login as Customer** still works (the cookie stack is reusable).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 2.8 — Negative test: cannot impersonate another admin
**Steps:**
1. From Manage Members, search for `admin2@arraysubs.test`.
2. Select that admin.
3. Inspect the **Login as Customer** button.

**Expected Result:**
- The Login as Customer button is disabled.
- Hovering it shows a tooltip explaining the reason ("Target user is an administrator" or similar — per the manual's table of disabled-reasons).
- No impersonation request fires if you try clicking it.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

## Regression / Cross-checks
- Disable Toolkit → Login as User. Reload the profile. Confirm the button is now disabled with the reason "Login as User feature is disabled". Re-enable Toolkit.
- Confirm you can do nested impersonation: while impersonating customer A, navigate the admin (if you have a second tab still authenticated as admin) and impersonate customer B. Switching back must return to customer A first, then admin.
- Confirm zero JavaScript console errors during impersonation flow.

## Sign-off
- Tester:
- Date:
- Browser & version:
- Customer impersonated:
- Notes:
