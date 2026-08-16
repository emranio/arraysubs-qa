# Stage 09 — Task 10: Pause-State Access Behavior (None / Limited / Full)

| Key | Value |
|---|---|
| Stage | 09 — Member Access & Restriction Rules |
| Module | Member Access — Paused entitlement policy |
| Plugin Coverage | Free + Pro integrations |
| Estimated Time | 35 min |
| Depends On | 01-role-mapping.md, 02-url-rules.md, 03-post-type-rules.md, 04-discount-rules.md, 06-download-rules.md |

## Objective

Verify the global **Settings → Skip & Pause → Access During Pause** policy across every entitlement consumer. **None** revokes all implicit member access, **Limited** permits protected-content viewing only, and **Full** treats Paused as access-granting for every scope. An explicit `Subscription Status = Paused` condition remains independently matchable in the condition builders.

## Pre-conditions

- `member2@example.com` has a **Paused** Pro Plan subscription; `member1@example.com` has an Active Pro Plan subscription.
- Test page **Pause Test Page** at `/pause-test-page` contains `PAUSE TEST CONTENT OK` and is protected by a **Has Active Subscription → Pro Plan** condition.
- Stage 09 role, discount, download, ecommerce, and URL rules are enabled.
- If available, one third-party entitlement integration is connected for a full-access spot-check.

## Sub-Tasks

### Sub-Task 10.1 — Confirm first-class Paused state

1. Open **ArraySubs → Subscriptions** and filter by **Paused**.
2. Confirm the customer, subscription ID, Paused badge, pause dates, and Resume action.
3. Filter **On Hold** separately and confirm the paused subscription is absent.

**Expected Result:** Paused and On Hold have independent filters, counts, badges, and actions.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 10.2 — None: revoke implicit access

1. Set **Access During Pause** to **No access (fully restricted)** and save.
2. As `member2@example.com`, open `/pause-test-page`, the protected download, and the discounted product.
3. Inspect the customer's mapped WordPress roles.

**Expected Result:**

- Protected content and downloads are denied.
- No member discount, purchase benefit, comment privilege, session privilege, course/licence/integration entitlement, or mapped member role is retained because of the paused subscription.
- The Active comparison customer is unaffected.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 10.3 — Limited: view-only access

1. Set **Access During Pause** to **Limited access (content only)** and save.
2. Repeat the page, URL, block/widget, download, discount, ecommerce, role, comment, session, and integration checks.

**Expected Result:**

- Protected page/URL/shortcode/Gutenberg/Elementor content is visible.
- Downloads, discounts, purchase benefits, comments, session privileges, mapped roles, courses, licences, CRM tags, and other integrations remain revoked.
- Changing the setting clears evaluator caches and reconciles roles without requiring a status toggle.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 10.4 — Full: all entitlement scopes

1. Set **Access During Pause** to **Full access** and save.
2. Repeat every check from 10.3.

**Expected Result:**

- Paused grants the same content, role, download, discount, ecommerce, comment, session, and integration entitlements as Active/Trial.
- Existing paused customers are reconciled immediately; no manual resume/re-pause is required.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 10.5 — Explicit Paused conditions

1. In a Member Access rule, add **Subscription Status** and open its status selector.
2. Confirm **Paused** and **On Hold** are separate choices; select only **Paused** and save a temporary rule.
3. Test it under None, Limited, and Full.

**Expected Result:**

- The explicit Paused condition matches the paused subscription in every policy mode; the global mode controls implicit entitlement, not the truth of a status-specific condition.
- On Hold does not match the Paused condition.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 10.6 — Gutenberg and Elementor builders

1. Edit a Gutenberg page and select the ArraySubs Restricted Content block.
2. Open its Subscription Status control.
3. If Elementor is installed, enable ArraySubs Content Restrictions on a test container and open the equivalent status control without publishing unrelated changes.

**Expected Result:** Both builders list Active, Trial, **Paused**, On Hold, Pending, Cancelled, and Expired as distinct values. Runtime access follows None/Limited/Full because both builders delegate to the shared evaluator.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 10.7 — Restore baseline

1. Set **Access During Pause** back to the recorded baseline.
2. Remove temporary explicit-status rules and unsaved builder test content.
3. Resume any subscription paused only for this task.
4. Confirm all test users, roles, plugins, and rules match their pre-task state.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

## Regression / Cross-checks

- On Hold Behavior in Role Mapping remains a separate payment/admin-state control and never governs Paused.
- Paused never generates renewal invoices or charges, regardless of access mode.
- Changing access mode must not change subscription status, pause dates, next-payment date, or scheduler jobs.

## Sign-off

- Tester:
- Date:
- Browser & version:
- Paused subscription ID:
- Baseline/final access mode:
- Gutenberg result:
- Elementor result:
- Notes:
