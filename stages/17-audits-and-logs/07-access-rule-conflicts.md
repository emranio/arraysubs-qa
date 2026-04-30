# Stage 17 — Task 07: Access-Rule Conflicts

| Key | Value |
|---|---|
| Stage | 17 — Audits, Logs & Troubleshooting |
| Module | Audits → Access-Rule Conflicts (or Member Access conflict UI) |
| Plugin Coverage | Free + Pro |
| Estimated Time | 30 min |
| Depends On | Stage 09 (Member Access tasks completed and rules cleaned up) |

## Objective
Create a deliberately overlapping pair of access rules — a URL prefix rule that **blocks** `/premium`, and a per-post (post-meta) rule on `/premium/article-1` that **allows** the same logged-in subscriber — and verify that the conflict-detection / resolution UI shows both rules side by side, ranks them by priority (per-post override beats URL pattern), and correctly resolves so the subscriber ultimately gains access to the specific article while still being blocked elsewhere under `/premium/`.

## Pre-conditions
- Logged in as administrator at `/wp-admin/`.
- Two test pages exist under the `/premium/` URL path:
  - `/premium` — empty body (the parent page or category landing).
  - `/premium/article-1` — published page with placeholder content.
  - `/premium/article-2` — published page with placeholder content (control — should remain blocked).
- Customer `member1@example.com` has an Active subscription to **Pro Plan**.
- Customer `nonmember@example.com` has no subscription (control).

## Test Data
- URL rule name: `Block /premium prefix`
- Post-meta rule applied to: `/premium/article-1`
- Test customer with Active subscription: `member1@example.com`
- Control customer (no subscription): `nonmember@example.com`
- Default restriction message: "This content is restricted. Please subscribe to access."

## Sub-Tasks

### Sub-Task 07.1 — Create the URL rule that blocks `/premium`
**Steps:**
1. Go to **ArraySubs → Member Access → URL Rules**.
2. Click **Add Rule**.
3. Name: `Block /premium prefix`.
4. Match type: **Prefix**.
5. URL pattern: `/premium`.
6. **THEN**: Restrict access. Conditions: require `Has Active Subscription` to **Pro Plan** (so subscribers WITH Pro Plan can still access — but for our test we'll add a stricter condition next).
7. Actually for the conflict test, set the conditions so that even Pro Plan subscribers are blocked: choose **Restrict to** = role `administrator` only (this simulates a deliberate over-restriction that conflicts with the per-post override).
8. Save.
9. Confirm the rule appears in the list with Enabled status.

**Expected Result:**
- A URL rule blocking everything under `/premium` for non-administrators is now active.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 07.2 — Verify the URL rule blocks both articles for the subscriber
**Steps:**
1. Open incognito and log in as `member1@example.com`.
2. Visit `/premium/article-1`.
3. Confirm the restriction page appears with the message "This content is restricted. Please subscribe to access." (or your configured message).
4. Visit `/premium/article-2`.
5. Confirm the same restriction.

**Expected Result:**
- Both articles are blocked by the URL prefix rule even though `member1` has an Active Pro Plan subscription.
- The restriction message is shown.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 07.3 — Add a per-post override that allows `/premium/article-1`
**Steps:**
1. As admin, edit the page `/premium/article-1`.
2. In the editor sidebar, find the **ArraySubs Access** meta box (or post-meta access settings).
3. Set the per-post restriction to **Allow if user has Active subscription** to Pro Plan.
4. Update the page.

**Expected Result:**
- The post now has a per-post access restriction set, which according to the priority order should override the URL prefix rule for this specific page.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 07.4 — Open the Access-Rule Conflicts UI
**Steps:**
1. Navigate to **ArraySubs → Audits [beta] → Access-Rule Conflicts** (or **Member Access → Conflicts** — record exact path: ____).
2. Inspect the listed conflict.

**Expected Result:**
- A conflict entry exists referencing both rules:
  - The URL rule `Block /premium prefix`
  - The per-post rule on `/premium/article-1`
- Each rule is shown with its conditions, priority/specificity score, and effect (allow/deny).
- The UI labels which rule wins per the priority order documented in the manual:
  1. Per-post restriction (highest)
  2. Specific URL rule
  3. URL pattern rule (this is `Block /premium prefix`)
  4. Post type rule
  5. Taxonomy rule
- For `/premium/article-1`, **Per-post restriction wins → Allow**.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 07.5 — Verify the resolved behaviour matches the conflict UI
**Steps:**
1. Return to the incognito session as `member1@example.com`.
2. Hard-refresh `/premium/article-1` (Ctrl+Shift+R / Cmd+Shift+R to bypass page cache).
3. Confirm the article content loads (no restriction message).
4. Visit `/premium/article-2`.
5. Confirm `/premium/article-2` is still blocked (no per-post override there).

**Expected Result:**
- `/premium/article-1` → ACCESSIBLE for `member1` (per-post override wins).
- `/premium/article-2` → BLOCKED for `member1` (URL rule still applies).
- This confirms the conflict UI's resolution prediction is accurate.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 07.6 — Verify the control user is blocked everywhere
**Steps:**
1. Log out `member1` and log in as `nonmember@example.com`.
2. Visit `/premium/article-1`.
3. Visit `/premium/article-2`.

**Expected Result:**
- Both URLs blocked (no Active subscription, so the per-post override condition fails too).
- Restriction message is shown on both pages.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 07.7 — Use the Conflict UI to resolve / disable the URL rule
**Steps:**
1. Return to the Access-Rule Conflicts UI.
2. Choose the resolution action — typically **Disable URL rule** or **Adjust conditions**.
3. Confirm the action.
4. Reload the page.
5. As `member1`, hard-refresh `/premium/article-2`.

**Expected Result:**
- After resolving (disabling the URL prefix rule, for example), the conflict no longer appears.
- `/premium/article-2` is now accessible to `member1` (no rule blocking, per-post override absent → unrestricted by default).
- An audit row is recorded in Activity Audits showing the admin disabled the URL rule.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

## Regression / Cross-checks
- Re-enable the URL rule, then delete both the URL rule and the per-post override at the end so Stage 18 starts clean.
- Verify that no false-positive "conflict" appears when only a single rule exists (delete one of the two and confirm the conflict UI is empty).
- Verify the priority order documented in the manual matches what the UI shows: per-post → specific URL → URL pattern → post type → taxonomy.
- Confirm caching is honoured — if the site uses page caching, clear caches between rule changes (record cache plugin: ____).

## Sign-off
- Tester:
- Date:
- Browser & version:
- URL rule ID:
- Per-post override target page ID:
- Notes:
