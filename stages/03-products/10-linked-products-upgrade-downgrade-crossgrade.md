# Stage 03 — Task 10: Linked Products — Upgrade / Downgrade / Crossgrade

| Key | Value |
|---|---|
| Stage | 03 — Subscription Product Creation |
| Module | Subscription Products / Plan Switching |
| Plugin Coverage | Free |
| Estimated Time | 30 min |
| Depends On | 03.01 |

## Objective
Build the canonical three-tier plan-switching set (`Basic Plan`, `Pro Plan`, `Enterprise Plan`) plus a `Crossgrade Plus` peer product. All four products are weekly so plan-switch tests run fast inside a real-time regression session. Configure upgrade, downgrade, and crossgrade targets on each via the **Linked Products → Subscription Plan Switching** section, save, reload, and confirm every selection persists.

## Pre-conditions
- Stage 03.01 complete.
- ArraySubs core active. Plan switching is enabled in **ArraySubs → Settings**.
- No existing products named `Basic Plan`, `Pro Plan`, `Enterprise Plan`, or `Crossgrade Plus` (rename if conflicting).

## Test Data
- `Basic Plan` — $9.99/week subscription.
- `Pro Plan` — $19.99/week subscription.
- `Enterprise Plan` — $39.99/week subscription.
- `Crossgrade Plus` — $19.99/week subscription (peer pricing to `Pro Plan`, used for crossgrade verification).

## Sub-Tasks

### Sub-Task 10.1 — Create the four subscription products
**Steps:**
1. Create `Basic Plan` (Simple, Subscription, Regular price `9.99`, Period `Week`, Interval `1`, Length `0`). Publish.
2. Create `Pro Plan` (Subscription, $19.99/week). Publish.
3. Create `Enterprise Plan` (Subscription, $39.99/week). Publish.
4. Create `Crossgrade Plus` (Subscription, $19.99/week). Publish.

**Expected Result:**
- All four products published as simple subscription products with the configured prices.
- Each product page renders the recurring text `$X.XX / week` for its respective tier.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 10.2 — Configure `Basic Plan` linked products
**Steps:**
1. Open `Basic Plan` in the editor.
2. Click **Linked Products** tab.
3. Confirm the `Subscription Plan Switching` section is visible (because Subscription is enabled on this product).
4. In **Upgrade to**, search for and select `Pro Plan` and `Enterprise Plan`.
5. Leave **Downgrade to** empty.
6. In **Crossgrade to**, leave empty.
7. **Auto-downgrade to** — leave empty.
8. **Update**.

**Expected Result:**
- Both `Pro Plan` and `Enterprise Plan` chips appear in the Upgrade to field.
- Save succeeds without errors.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 10.3 — Configure `Pro Plan` linked products
**Steps:**
1. Open `Pro Plan` → Linked Products.
2. **Upgrade to**: select `Enterprise Plan`.
3. **Downgrade to**: select `Basic Plan`.
4. **Crossgrade to**: select `Crossgrade Plus`.
5. **Auto-downgrade to**: select `Basic Plan`.
6. **Update**.

**Expected Result:**
- Each field shows its selection chip(s).
- Auto-downgrade is a single-select; verify only `Basic Plan` is selectable at a time.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 10.4 — Configure `Enterprise Plan` linked products
**Steps:**
1. Open `Enterprise Plan` → Linked Products.
2. **Downgrade to**: select `Pro Plan` and `Basic Plan`.
3. **Auto-downgrade to**: select `Basic Plan`.
4. Leave Upgrade to / Crossgrade to empty.
5. **Update**.

**Expected Result:**
- Downgrade chips reflect both lower-tier products.
- Save succeeds.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 10.5 — Configure `Crossgrade Plus` linked products
**Steps:**
1. Open `Crossgrade Plus` → Linked Products.
2. **Crossgrade to**: select `Pro Plan`.
3. **Update**.

**Expected Result:**
- `Pro Plan` is selected as a crossgrade target.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 10.6 — Persistence sweep
**Steps:**
1. Reload each of the four products (`Basic Plan`, `Pro Plan`, `Enterprise Plan`, `Crossgrade Plus`) in fresh tabs.
2. Open the Linked Products tab on each.
3. Verify every selection from 10.2 – 10.5 is still in place exactly as configured.

**Expected Result:**
- Selections persist across page reloads.
- Search chips reload with correct product titles.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 10.7 — Confirm AJAX product search restricts to subscription products
**Steps:**
1. On `Basic Plan` → Linked Products → Upgrade to, type the name of a non-subscription product (e.g., a regular WooCommerce test product if available).

**Expected Result:**
- The non-subscription product is filtered out / not selectable as a switch target (per manual: "type to search for subscription products by name or SKU").

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

## Regression / Cross-checks
- Confirm Linked Products tab shows the `Subscription Plan Switching` section ONLY when the Subscription checkbox is enabled. Briefly untick it on a throwaway product (or check `Lifetime Deal`) and reload — the section should be hidden.
- Capture screenshots of each persisted Linked Products tab for stage 05 plan-switching tests.

## Sign-off
- Tester:
- Date:
- Browser & version:
- Notes:
