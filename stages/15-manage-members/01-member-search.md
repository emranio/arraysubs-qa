# Stage 15 — Task 01: Member Search

| Key | Value |
|---|---|
| Stage | Manage Members |
| Module | Manage Members — Find a Member search |
| Plugin Coverage | Pro |
| Estimated Time | 15 min |
| Depends On | Stage 14 (subscriptions exist), Pro plugin active |

## Objective
Open **ArraySubs → Manage Members**, search by username and by email, and verify the dropdown shows up to 20 matching users ordered by display name. Each result should display the customer's display name, email and `@username`, plus a `{active} / {total} subs` indicator. Confirm the 2-character minimum, the 300 ms debounce, and that the URL hash updates to `#/manage-members/{user_id}` when a result is selected.

## Pre-conditions
- Logged in as Administrator.
- ArraySubs Pro is active.
- At least 5 test customers exist with email addresses on the same domain (e.g. `customer1@arraysubs.test` … `customer5@arraysubs.test`).
- One additional customer has a fully different domain (e.g. `vip@example.com`).
- DevTools Network tab open to verify the debounced request.

## Test Data
- Username search: `customer` (should match multiple users).
- Email search: `vip@example.com` (should match one user).
- Short query: `c` (one character — should not search).

## Sub-Tasks

### Sub-Task 1.1 — Open Manage Members
**Steps:**
1. From the wp-admin sidebar, click **ArraySubs → Manage Members**.
2. Wait for the page to render.

**Expected Result:**
- Page loads with a centered **Find a Member** search field.
- No customer is preselected.
- DevTools Console is clean.
- The page heading reads **Manage Members** (or equivalent label per the build).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 1.2 — Verify the 2-character minimum
**Steps:**
1. Click into the search field.
2. Type a single character `c`.
3. Wait 1 second.

**Expected Result:**
- No request fires (verify in DevTools Network — no `/users` or member search call).
- No dropdown appears.
- A help text or placeholder may indicate at least 2 characters are required.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 1.3 — Search by partial username
**Steps:**
1. Type `customer` in the search field.
2. Watch DevTools Network for the request.

**Expected Result:**
- A single network request fires roughly 300 ms after the last keystroke (debounce).
- A dropdown opens listing every user whose username, display name, or email contains `customer`.
- Up to 20 results, ordered by display name.
- Each row shows: display name, second line `email · @username`, and a third element `{active} / {total} subs`.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 1.4 — Search by exact email
**Steps:**
1. Clear the search field.
2. Type `vip@example.com`.

**Expected Result:**
- The dropdown shows the single matching user.
- The email is highlighted or bolded in the row (if the build highlights matches).
- The subs indicator shows that user's actual active and total subscription counts.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 1.5 — Select a result and confirm the URL hash
**Steps:**
1. Click the matching row in the dropdown.
2. Watch the URL hash in the address bar.

**Expected Result:**
- The URL hash becomes `#/manage-members/{user_id}` where `{user_id}` is the WordPress user ID for that customer.
- The profile card loads below the search field.
- The dropdown closes.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 1.6 — Verify subs count accuracy
**Steps:**
1. From the previous selection, note the displayed `{active} / {total} subs` numbers in the dropdown row that was clicked.
2. Open **ArraySubs → Subscriptions** and search for the same customer's email.
3. Manually count the rows for that customer in the Active tab and the All tab.

**Expected Result:**
- Active count from the dropdown indicator = number of rows in the Active tab for that customer.
- Total count from the dropdown indicator = number of rows in the All tab for that customer.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 1.7 — Pagination / 20-result cap
**Steps:**
1. Search a term that would match more than 20 users (e.g. `arraysubs.test` if your test domain is shared).
2. Scroll to the bottom of the dropdown.

**Expected Result:**
- The dropdown caps at 20 results.
- A help message or count indicator notes "Showing 20 results — refine your search" (or equivalent), per the manual.
- The 20 results are ordered by display name.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

## Regression / Cross-checks
- Open the URL `#/manage-members/{user_id}` directly (e.g. paste it into a new tab while logged in) and confirm the profile loads without using the search.
- Type a term that matches no users (e.g. `zzzznoone`) and confirm an empty-state message ("No members found.") is shown — not a stuck loading skeleton.
- Confirm the search results and profile load with no console errors and the request status is 2xx.

## Sign-off
- Tester:
- Date:
- Browser & version:
- Notes:
