# Stage 10 — Task 04: Account Shortcodes & Shortcodes Reference Page

| Key | Value |
|---|---|
| Stage | 10 — Profile Builder & My Account Customization |
| Module | Shortcodes — Account ([arraysubs_login], [arraysubs_logout], [arraysubs_user]) + Admin shortcodes-reference page |
| Plugin Coverage | Free |
| Estimated Time | 25 min |
| Depends On | none |

## Objective
Place the three account-related shortcodes — `[arraysubs_login]`, `[arraysubs_logout]`, `[arraysubs_user]` — on a sandbox page using both default and custom attributes. Verify each renders correctly when logged in vs logged out, and that the `redirect`, `text`, `class`, `field`, and `fallback` attributes behave as documented. Verify the admin shortcodes-reference page lists every available shortcode.

## Pre-conditions
- `qa_admin` available for admin verification.
- `pf_test@example.com` registered with First Name = `Tester`, Last Name = `One`, Display Name = `Tester O.`. If not set, update the user's profile first.
- Test page **Account Shortcodes Sandbox** at slug `account-shortcodes-sandbox`, published, empty body.

## Test Data
- Sandbox page: `/account-shortcodes-sandbox`
- Customer: `pf_test@example.com`
- Display Name: `Tester O.`
- First Name: `Tester`
- Last Name: `One`

## Sub-Tasks

### Sub-Task 04.1 — Place shortcodes on the Sandbox page
**Steps:**
1. Edit **Account Shortcodes Sandbox**.
2. Replace the page content with this exact block (use a Code block or a Shortcode block per snippet):

```
SECTION 1 — default login link:
[arraysubs_login]

SECTION 2 — custom text login link:
[arraysubs_login text="Sign In Here"]

SECTION 3 — login link with redirect and class:
[arraysubs_login text="Member Login" redirect="/my-account/" class="btn-primary"]

SECTION 4 — default logout link:
[arraysubs_logout]

SECTION 5 — custom logout link:
[arraysubs_logout text="Sign Out Now" redirect="/"]

SECTION 6 — user display_name:
Hello, [arraysubs_user]!

SECTION 7 — user first_name with fallback:
Hi, [arraysubs_user field="first_name" fallback="Guest"]!

SECTION 8 — user full_name:
Signed in as [arraysubs_user field="full_name" fallback="Visitor"]

SECTION 9 — user username:
Your username is [arraysubs_user field="username"].

SECTION 10 — visibility logged_in with login fallback:
[arraysubs_visibility show="logged_in" fallback="[arraysubs_login text='Log In']"]
Welcome back, [arraysubs_user field="first_name"]!
[/arraysubs_visibility]
```

3. Publish/Update the page.

**Expected Result:**
- Page saved successfully.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 04.2 — Logged-out (incognito) view
**Steps:**
1. Open an incognito window. Navigate to `/account-shortcodes-sandbox`.
2. Note what each Section renders.

**Expected Result:**
- SECTION 1: clickable link with default text "Log In".
- SECTION 2: link with text "Sign In Here".
- SECTION 3: link with text "Member Login", anchor has CSS class `arraysubs-login-link btn-primary`, redirect query param points to `/my-account/`.
- SECTION 4: empty (logout shortcode renders nothing for guests).
- SECTION 5: empty.
- SECTION 6: `Hello, !` (the user shortcode renders nothing for guests with no fallback).
- SECTION 7: `Hi, Guest!`.
- SECTION 8: `Signed in as Visitor`.
- SECTION 9: `Your username is .` (no fallback, empty output).
- SECTION 10: a "Log In" link is rendered (the fallback is processed, including the nested `[arraysubs_login]`).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 04.3 — Logged-in view (`pf_test@example.com`)
**Steps:**
1. In a normal browser, log in as `pf_test@example.com`.
2. Navigate to `/account-shortcodes-sandbox`.

**Expected Result:**
- SECTION 1: empty (login shortcode renders nothing for logged-in users).
- SECTION 2: empty.
- SECTION 3: empty.
- SECTION 4: clickable link with default text "Log Out".
- SECTION 5: link with text "Sign Out Now". Clicking will redirect to `/` after logout.
- SECTION 6: `Hello, Tester O.!` (display name).
- SECTION 7: `Hi, Tester!` (first name).
- SECTION 8: `Signed in as Tester One` (full name = first + last).
- SECTION 9: `Your username is pf_test.` (or the actual login username — record actual value).
- SECTION 10: `Welcome back, Tester!` is rendered (visibility passed; fallback is hidden).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 04.4 — Verify redirect after login (Section 3)
**Steps:**
1. In an incognito window, navigate to `/account-shortcodes-sandbox` and click the SECTION 3 link `Member Login`.
2. Sign in as `pf_test@example.com`.

**Expected Result:**
- After successful login, the browser is redirected to `/my-account/` (per the `redirect` attribute), NOT back to the sandbox page.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 04.5 — Verify redirect after logout (Section 5)
**Steps:**
1. In a logged-in browser, navigate to `/account-shortcodes-sandbox`.
2. Click the SECTION 5 link `Sign Out Now`.

**Expected Result:**
- Logout completes (WordPress nonce-secured) and the browser lands on `/` (home page).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 04.6 — Built-in CSS classes verification
**Steps:**
1. As guest, view the sandbox page source.
2. Search for the classes `arraysubs-login-link` and `arraysubs-logout-link`.

**Expected Result:**
- Login link anchor has class `arraysubs-login-link` (plus any custom class on Section 3).
- Logout link anchor has class `arraysubs-logout-link` for sections 4 and 5 (when viewing as logged in).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 04.7 — Admin shortcodes-reference page lists all shortcodes
**Steps:**
1. Log in as `qa_admin`.
2. Navigate to the ArraySubs admin area and locate the **Shortcodes** reference page (commonly at **ArraySubs → Shortcodes** or **ArraySubs → Tools → Shortcodes**). Record the exact menu path used.
3. Inspect the list of shortcodes shown.

**Expected Result:**
- The page lists at least:
  - `[arraysubs_login]`
  - `[arraysubs_logout]`
  - `[arraysubs_user]`
  - `[arraysubs_visibility]`
  - `[arraysubs_restrict]`
  - `[arraysubs_buy_credits]` *(Pro)* — only if Pro is active
- Each entry shows a short description and a usage example or link to the manual.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 04.8 — Cleanup
**Steps:**
1. Leave the sandbox page in place if downstream stages need it; otherwise revert to empty body.
2. Note final page state.

**Expected Result:**
- Sandbox page state recorded.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

## Regression / Cross-checks
- Verify nesting: Section 10 demonstrates `[arraysubs_login]` inside `fallback=""` of `[arraysubs_visibility]`. Confirm the nested shortcode is fully processed.
- Confirm `[arraysubs_user field="full_name"]` falls back to `display_name` when first/last name are both empty (test by clearing first/last name on `pf_test2@example.com` and re-verifying).
- Confirm `[arraysubs_logout]` URL contains a WordPress nonce (`_wpnonce=` parameter visible when hovering over the link).
- Confirm full-page caching is excluded for pages using these shortcodes (per the manual's caching note in Account Shortcodes troubleshooting).

## Sign-off
- Tester:
- Date:
- Browser & version:
- Admin shortcodes-reference page menu path:
- pf_test login username (Section 9 actual value):
- Pro active during this test (yes/no — affects shortcodes list):
- Notes:
