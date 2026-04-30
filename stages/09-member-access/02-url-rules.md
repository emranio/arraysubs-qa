# Stage 09 — Task 02: URL Rules — `/premium-content` Restriction

| Key | Value |
|---|---|
| Stage | 09 — Member Access & Restriction Rules |
| Module | Member Access → URL |
| Plugin Coverage | Free |
| Estimated Time | 25 min |
| Depends On | 01-role-mapping.md (Pro Plan subscription is the gating condition) |

## Objective
Add a URL Rule with **Pattern Type = Prefix** matching `/premium-content` and **IF condition = Has Active Subscription**. Verify three visitor states behave correctly: a logged-out visitor is redirected to login, a logged-in non-subscriber is blocked with the restriction page, and an active Pro Plan subscriber receives the page content normally.

## Pre-conditions
- Role Mapping rule from Task 01 is in place but does **not** affect URL rules (URL rules check subscription status directly).
- Page **Premium Content** exists at slug `premium-content` with body text "PREMIUM CONTENT — TEST PAGE OK" so the tester can confirm the page rendered when access is granted.
- **ArraySubs → Member Access → Settings** has Default Redirect URL = `/pricing` and Require Login = `true` (default).
- Three browsers / contexts ready:
  - Browser A: incognito (logged out).
  - Browser B: logged in as `nonmember@example.com` (no subscriptions).
  - Browser C: logged in as `member1@example.com` (active Pro Plan subscription, post-Task 01).

## Test Data
- URL pattern: `/premium-content`
- Pattern Type: `Prefix`
- Priority: `10`
- Action when blocked: `Redirect`
- Redirect URL (rule-level): `/pricing`
- IF condition: Has Active Subscription to **Pro Plan**

## Sub-Tasks

### Sub-Task 02.1 — Create the URL rule
**Steps:**
1. Go to **ArraySubs → Member Access → URL**.
2. Click **Add Rule**.
3. Name the rule `Premium Content URL gate`.
4. **TARGET:**
   - Pattern Type: select `Prefix`.
   - URL Pattern: enter `/premium-content`.
   - Priority: enter `10`.
   - Exclusions: leave empty.
5. **IF conditions:** choose **Has Active Subscription** → product **Pro Plan**.
6. **THEN:** Action = `Redirect`, Redirect URL = `/pricing`.
7. Leave **Schedule** disabled.
8. Click **Save**.
9. Confirm the rule appears in the URL rule list with priority 10 and the toggle enabled.

**Expected Result:**
- Rule "Premium Content URL gate" is saved and enabled. The list shows pattern `/premium-content`, priority `10`, action `Redirect`.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 02.2 — Logged-out visitor is sent to login (Require Login = true)
**Steps:**
1. In Browser A (incognito), navigate directly to `https://<test-site>/premium-content`.
2. Observe the URL after the request resolves.
3. Inspect the page that loads.

**Expected Result:**
- The browser is redirected to the WordPress login page (`/wp-login.php` or `/my-account/`, depending on theme/Setup Wizard config).
- After login, the visitor is returned to `/premium-content` and re-evaluated.
- The browser URL is not `/premium-content` — it is the login form URL.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 02.3 — Non-subscriber is redirected to /pricing
**Steps:**
1. In Browser B, log in as `nonmember@example.com` at `/my-account/` (this user has no active subscription).
2. After login, navigate to `https://<test-site>/premium-content`.
3. Observe the destination URL and the body text on the page.

**Expected Result:**
- Browser is redirected to `/pricing`.
- The body text "PREMIUM CONTENT — TEST PAGE OK" is **not** visible.
- No PHP notices/warnings on the destination page.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 02.4 — Active subscriber sees the content
**Steps:**
1. In Browser C, log in as `member1@example.com`.
2. Navigate to `https://<test-site>/premium-content`.
3. Confirm the URL bar still reads `/premium-content` (no redirect).
4. Confirm the body text "PREMIUM CONTENT — TEST PAGE OK" is visible on the page.

**Expected Result:**
- URL is `/premium-content`, not `/pricing`.
- Body text is visible.
- No restriction message banner appears.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 02.5 — Verify exclusion behaviour
**Steps:**
1. Edit the URL rule and add `/premium-content/preview/` to the **Exclusions** field. Save.
2. Create a new published page at slug `premium-content/preview` (or `/premium-content-preview` if your permalink structure forbids nested slugs — note substitution).
3. In Browser A (incognito, logged out), navigate to `/premium-content/preview/`.
4. Confirm the page loads without redirect.

**Expected Result:**
- The excluded path loads for everyone, including logged-out visitors. The base `/premium-content` URL still enforces the rule.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 02.6 — Switch action to "403 Forbidden" and verify
**Steps:**
1. Edit the URL rule. Change Action to `403 Forbidden`. Save.
2. In Browser B (logged-in non-subscriber), navigate to `/premium-content`.
3. Inspect the HTTP status of the response (DevTools → Network panel → top-level document).

**Expected Result:**
- The HTTP status returned is `403 Forbidden`.
- Browser shows a forbidden / restriction page (theme-styled 403 or ArraySubs default restriction page).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 02.7 — Restore action and clean up
**Steps:**
1. Edit the rule, change Action back to `Redirect`, Redirect URL = `/pricing`.
2. Remove the exclusion path.
3. Save. Confirm rule still enabled.

**Expected Result:**
- Rule is back to original config: Prefix `/premium-content`, Redirect to `/pricing`, no exclusions.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

## Regression / Cross-checks
- Confirm `/wp-admin/` and the WP REST API are NOT affected by this URL rule (open `/wp-admin/` in Browser B — admin login screen still loads).
- Confirm a query-string version `https://<test-site>/premium-content?utm=test` still triggers the rule (URL matching ignores query strings).
- Confirm the Stage 09 Role Mapping rule from Task 01 is still active and assigning `pro_member` correctly.

## Sign-off
- Tester:
- Date:
- Browser & version (A / B / C):
- Notes:
