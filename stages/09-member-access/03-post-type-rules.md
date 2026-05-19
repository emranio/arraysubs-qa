# Stage 09 — Task 03: Post Type Rules + Per-Post Override

| Key | Value |
|---|---|
| Stage | 09 — Member Access & Restriction Rules |
| Module | Member Access → Post Types |
| Plugin Coverage | Free |
| Estimated Time | 30 min |
| Depends On | 01-role-mapping.md |

## Objective
Restrict an entire post type (custom post type `kb_article`, falling back to `page` if CPT plugin not available) to active **Pro Plan** subscribers. Verify that the per-post restriction meta UI on individual posts overrides the global Post Type rule (both more-restrictive and less-restrictive overrides).

## Pre-conditions
- CPT `kb_article` registered. If not available, substitute the default `page` post type and document the substitution in Sign-off.
- Three test posts exist:
  - `KB-A` — published, body text "KB-A BODY OK". No per-post restriction.
  - `KB-B` — published, body text "KB-B BODY OK". Will be the per-post override target (open to everyone).
  - `KB-C` — published, body text "KB-C BODY OK". Will be the per-post override target (extra-restrictive: requires subscription to **Basic Plan**, not Pro).
- Customers ready: `nonmember@example.com`, `member1@example.com` (Active Pro Plan), and `member2@example.com` who must have an Active **Basic Plan** subscription for sub-task 03.6 (create one via Stage 05/06 flow if missing).
- Default restriction message at **ArraySubs → Member Access → Settings** still set to "This content is restricted. Please subscribe to access."

## Test Data
- Target post type: `kb_article` (or `page`)
- Action when blocked: `Message`
- Archive Behavior: `Show with lock`
- Per-post meta UI keys: `_arraysubs_restrict_enabled`, `_arraysubs_restrict_conditions`, `_arraysubs_restrict_message`

## Sub-Tasks

### Sub-Task 03.1 — Create the Post Type rule (entire post type)
**Steps:**
1. Go to **ArraySubs → Member Access → Post Types**.
2. Click **Add Rule**.
3. Name the rule `KB Articles — Pro Plan only`.
4. **TARGET:**
   - Target Type: `Entire Post Type`.
   - Post Type: select `kb_article` (or `page`).
5. **IF conditions:** Has Active Subscription to **Pro Plan**.
6. **THEN:** Action = `Message`, Message = `KB articles are available to Pro Plan subscribers. {pricing_link} to view our plans.`
7. **Archive Behavior:** `Show with lock`.
8. Save.
9. Confirm the rule appears enabled in the list.

**Expected Result:**
- Rule listed with Target Type "Entire Post Type", Post Type `kb_article`, Action `Message`, Archive Behavior `Show with lock`.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 03.2 — Non-subscriber sees restriction message
**Steps:**
1. In an incognito browser, log in as `nonmember@example.com`.
2. Navigate to the single post URL for **KB-A**.
3. Inspect the body of the page.

**Expected Result:**
- The body text "KB-A BODY OK" is **not** rendered.
- The restriction message reads: `KB articles are available to Pro Plan subscribers.` followed by a clickable pricing link.
- The `{pricing_link}` merge tag has been replaced with an `<a href="/pricing">` link.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 03.3 — Active subscriber sees the content
**Steps:**
1. In a different browser, log in as `member1@example.com`.
2. Navigate to **KB-A**.

**Expected Result:**
- Body text "KB-A BODY OK" is fully visible.
- No restriction banner appears.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 03.4 — Archive shows lock indicator
**Steps:**
1. As `nonmember@example.com`, navigate to the KB archive (e.g., `/kb_article/` or `/kb-articles/`).
2. Locate the entry for **KB-A** in the listing.

**Expected Result:**
- KB-A appears in the archive (because Archive Behavior is `Show with lock`).
- A lock icon or indicator is rendered next to KB-A's title.
- Clicking through still triggers the restriction message.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 03.5 — Per-post override: open KB-B to everyone
**Steps:**
1. In wp-admin, edit post **KB-B**.
2. Locate the **ArraySubs Restrictions** metabox in the post sidebar (or the Custom Fields panel).
3. Set **Enable per-post restriction** = ON, then set the conditions to `(no condition — public)` OR set the meta `_arraysubs_restrict_enabled` = `0` explicitly via the **Custom Fields** panel.
   - The cleanest way (per the manual): set `_arraysubs_restrict_enabled` to `0` so the per-post check returns "not restricted" and overrides the global rule.
4. Click **Update**.
5. As `nonmember@example.com`, navigate to **KB-B**.

**Expected Result:**
- "KB-B BODY OK" is visible to the non-subscriber.
- No restriction message appears.
- The global Post Type rule is overridden by the per-post setting.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 03.6 — Per-post override: extra-restrictive on KB-C
**Steps:**
1. In wp-admin, edit post **KB-C**.
2. Set **Enable per-post restriction** = ON.
3. Set per-post conditions to: Has Active Subscription to **Basic Plan** (not Pro Plan).
4. Set per-post **Message** to: `KB-C is reserved for Basic Plan subscribers only.`
5. **Update**.
6. As `member1@example.com` (Active **Pro Plan**, NOT Basic), navigate to **KB-C**.

**Expected Result:**
- The per-post message `KB-C is reserved for Basic Plan subscribers only.` is shown.
- "KB-C BODY OK" is **not** rendered (per-post rule is more restrictive than the global rule for this user).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 03.7 — Confirm Basic Plan subscriber sees KB-C
**Steps:**
1. Log in as `member2@example.com` (must have Active Basic Plan subscription).
2. Navigate to **KB-C**.

**Expected Result:**
- "KB-C BODY OK" is visible.
- No restriction banner.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 03.8 — Cleanup
**Steps:**
1. Open the KB Articles rule in admin and confirm it is still enabled (do not delete — it is referenced in Task 09.10).
2. Leave KB-B's per-post override in place if Task 10 (pause-state) needs it, otherwise reset to default.
3. Restore KB-C to use the global rule by setting `_arraysubs_restrict_enabled` = `0`.

**Expected Result:**
- Global KB rule remains active. Per-post overrides recorded in Sign-off for traceability.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

## Regression / Cross-checks
- Confirm WP search results respect Archive Behavior: as `nonmember`, search for "KB-A" — it should still appear with the lock indicator (Show with lock).
- Confirm the WP REST API still returns `kb_article` content for authorized requests (REST is not affected by frontend rules).
- Confirm the per-post `_arraysubs_restrict_message` overrides both the rule message AND the global default — never falls through to the global default while per-post is enabled with its own message.

## Sign-off
- Tester:
- Date:
- Browser & version:
- CPT used (`kb_article` or `page`):
- KB-B per-post final state (open / restored to default):
- Notes:
