# Stage 09 — Task 09: Restriction Shortcodes — `[arraysubs_restrict]` & `[arraysubs_visibility]`

| Key | Value |
|---|---|
| Stage | 09 — Member Access & Restriction Rules |
| Module | Shortcodes — Content Gating |
| Plugin Coverage | Free |
| Estimated Time | 30 min |
| Depends On | 01-role-mapping.md, 04-discount-rules.md (Pro Plan subscriptions) |

## Objective
Place `[arraysubs_restrict]` (with a variety of conditions) and `[arraysubs_visibility]` shortcodes on test pages and verify each one renders the correct content for each visitor type. Specifically test status, products, roles, OR-logic, custom messages, and the visibility wrapper for logged-in vs logged-out.

## Pre-conditions
- Pro Plan product ID recorded (used in `products` attribute).
- Test page **Shortcode Sandbox** at slug `shortcode-sandbox`, published.
- Customers ready: `member1@example.com` (Active Pro Plan), `nonmember@example.com`, an admin user, and an incognito guest.
- Pro Plan product ID known (record in Test Data).

## Test Data
- Pro Plan product ID: `____` (record before starting)
- Test page: `/shortcode-sandbox`
- Custom denial message: `Subscribe to the Pro Plan to unlock this section.`
- Test admin username: `qa_admin`

## Sub-Tasks

### Sub-Task 09.1 — Add shortcodes to the Sandbox page
**Steps:**
1. In wp-admin, edit **Shortcode Sandbox**.
2. Replace the page content with the following blocks (one Shortcode block per snippet, or paste all into a single Code block):

```
[H1] Section A — visibility logged-in
[arraysubs_visibility show="logged_in"]
A_LOGGED_IN_OK
[/arraysubs_visibility]

[H1] Section B — visibility logged-out
[arraysubs_visibility show="logged_out"]
B_LOGGED_OUT_OK
[/arraysubs_visibility]

[H1] Section C — restrict by status active
[arraysubs_restrict status="active"]
C_ACTIVE_OK
[/arraysubs_restrict]

[H1] Section D — restrict by product (Pro Plan)
[arraysubs_restrict products="<PRO_PLAN_ID>"]
D_PRODUCT_OK
[/arraysubs_restrict]

[H1] Section E — restrict by role administrator
[arraysubs_restrict roles="administrator"]
E_ADMIN_OK
[/arraysubs_restrict]

[H1] Section F — OR logic active OR administrator
[arraysubs_restrict status="active" roles="administrator" condition="or"]
F_OR_OK
[/arraysubs_restrict]

[H1] Section G — custom message
[arraysubs_restrict status="active" message="Subscribe to the Pro Plan to unlock this section."]
G_PREMIUM_OK
[/arraysubs_restrict]

[H1] Section H — show_to_admins=false
[arraysubs_restrict status="active" show_to_admins="false"]
H_NO_ADMIN_BYPASS_OK
[/arraysubs_restrict]
```

3. Replace `<PRO_PLAN_ID>` with the actual Pro Plan product ID.
4. Click **Update / Publish**.

**Expected Result:**
- Page is saved successfully. Visiting the page (as admin) renders all sections (admin bypass on by default).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 09.2 — Guest (logged-out) view
**Steps:**
1. Open an incognito browser. Navigate to `/shortcode-sandbox`.
2. Note which section markers (A_LOGGED_IN_OK, B_LOGGED_OUT_OK, etc.) appear.

**Expected Result:**
- A: NOT shown.
- B: `B_LOGGED_OUT_OK` is shown.
- C: NOT shown — default denial message renders ("This content is restricted..." or the global default from Task 08).
- D: NOT shown — default denial.
- E: NOT shown.
- F: NOT shown — default denial. (Note: with default `login_required="true"`, a logged-out visitor sees the "Please log in to access this content." message even with OR logic.)
- G: Custom message `Subscribe to the Pro Plan to unlock this section.` is shown.
- H: NOT shown.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 09.3 — Logged-in non-member (`nonmember@example.com`)
**Steps:**
1. Log in as `nonmember@example.com`.
2. Navigate to `/shortcode-sandbox`.
3. Note which section markers appear.

**Expected Result:**
- A: `A_LOGGED_IN_OK` is shown.
- B: NOT shown.
- C: NOT shown — default denial message.
- D: NOT shown — default denial.
- E: NOT shown — denial (user is not administrator).
- F: NOT shown — denial (no active subscription, not administrator).
- G: NOT shown — `Subscribe to the Pro Plan to unlock this section.` shown.
- H: NOT shown.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 09.4 — Logged-in active subscriber (`member1@example.com`)
**Steps:**
1. Log in as `member1@example.com`.
2. Navigate to `/shortcode-sandbox`.

**Expected Result:**
- A: `A_LOGGED_IN_OK` shown.
- B: NOT shown.
- C: `C_ACTIVE_OK` shown.
- D: `D_PRODUCT_OK` shown (subscribed to Pro Plan).
- E: NOT shown (not administrator).
- F: `F_OR_OK` shown (matches the `status=active` half of the OR).
- G: `G_PREMIUM_OK` shown.
- H: `H_NO_ADMIN_BYPASS_OK` shown (active subscriber passes the condition without admin bypass needed).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 09.5 — Logged-in administrator
**Steps:**
1. Log in as `qa_admin` (or any user with role `administrator`, no Pro Plan subscription).
2. Navigate to `/shortcode-sandbox`.

**Expected Result:**
- A: `A_LOGGED_IN_OK` shown.
- B: NOT shown.
- C: `C_ACTIVE_OK` shown (admin bypass = `show_to_admins="true"` default).
- D: `D_PRODUCT_OK` shown (admin bypass).
- E: `E_ADMIN_OK` shown (matches role).
- F: `F_OR_OK` shown.
- G: `G_PREMIUM_OK` shown (admin bypass).
- H: NOT shown — denial message displayed (because `show_to_admins="false"` disables the admin bypass and the admin has no active subscription).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 09.6 — `[arraysubs_visibility]` fallback
**Steps:**
1. Edit Shortcode Sandbox and add this snippet near the top:
   ```
   [arraysubs_visibility show="logged_in" fallback="Please log in to view this content."]
   I_LOGGED_IN_GREETING_OK
   [/arraysubs_visibility]
   ```
2. Save.
3. View as guest (incognito).
4. View as `nonmember@example.com`.

**Expected Result:**
- Guest: `Please log in to view this content.` is shown.
- Non-member (logged in): `I_LOGGED_IN_GREETING_OK` is shown.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 09.7 — Cleanup
**Steps:**
1. Leave the Shortcode Sandbox page in place — Task 10 (pause-state) reuses it.
2. Note the final page revision in Sign-off.

**Expected Result:**
- Sandbox page is saved with all shortcodes in place.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

## Regression / Cross-checks
- Confirm restricted content is NOT in the page source for non-qualifying visitors (View Source: the marker text "C_ACTIVE_OK" should be absent from raw HTML when viewed as guest).
- Confirm nesting `[arraysubs_visibility]` inside `[arraysubs_restrict]` works (try wrapping a logged-in visibility block inside an active-status restrict block).
- Confirm shortcodes inside the `fallback` attribute are processed (e.g., `fallback="[arraysubs_login text='Sign In']"`).

## Sign-off
- Tester:
- Date:
- Browser & version:
- Pro Plan product ID used:
- Notes:
