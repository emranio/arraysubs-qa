# Stage 11 — Task 02: Customer and Storefront Display

| Key | Value |
|---|---|
| Stage | 11 — Feature Manager (Pro) |
| Module | Feature Manager — Storefront and My Account display |
| Plugin Coverage | Pro |
| Estimated Time | 25 min |
| Depends On | 11.01 |

## Objective
Confirm the "What's Included" section renders on the storefront single-product page when the appropriate setting is on, that disabled features are hidden, that a customer who subscribes sees their entitlements on the My Features page (`/my-account/features/`), and that the page reflects the configured aggregation mode and usage column visibility.

## Pre-conditions
- 11.01 complete — `Pro Plan` has the seven test features defined.
- Logged-in admin session at `admin@test.local` for settings work.
- A second browser (or incognito window) available to act as `cust1@test.local`.
- A working payment method (Stripe test mode — already configured — or BACS / COD as a manual fallback).

## Test Data
- Storefront URL: permalink for `Pro Plan` ($19.99 / week).
- Customer: `cust1@test.local` / portal at `/my-account/`.
- My Features URL: `/my-account/features/`.

## Sub-Tasks

### Sub-Task 2.1 — Enable storefront display in settings
**Steps:**
1. **WP-Admin → ArraySubs → Settings → Feature Manager**.
2. Confirm **Enable Feature Manager** is **On**.
3. Toggle **Show on Product Page** to **On**.
4. Toggle **Show in My Account** to **On**.
5. Set **Feature Aggregation Mode** to **Per Subscription** (default).
6. Toggle **Show Usage Count in My Account** to **On**.
7. Click **Save Settings**.

**Expected Result:**
- The settings page reloads with a success message (e.g., `Settings saved.`).
- All four toggles persist on a hard refresh of the settings page.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 2.2 — Verify "What's Included" on the product page
**Steps:**
1. Open an incognito window and go to the permalink of `Pro Plan` (no login required).
2. Scroll below the product summary / Add to Cart area.
3. Locate the **What's Included** heading.

**Expected Result:**
- A **What's Included** section is rendered.
- It lists exactly the six **enabled** features in the saved order:
  1. `Seats — 5`
  2. `API Calls — 10000`
  3. `Storage (GB) — 10`
  4. `Priority Support — ✓` (or similar yes glyph)
  5. `Custom Domain — ✗` (or similar no glyph)
  6. `Plan Tier — Starter`
- The disabled feature `Hidden Beta Flag` is NOT shown.
- DevTools console shows no JS errors.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 2.3 — Negative check: storefront toggle off
**Steps:**
1. As admin, set **Show on Product Page** to **Off** in Feature Manager settings and save.
2. Reload the `Pro Plan` product page in incognito.
3. Look for the **What's Included** section.
4. Re-enable **Show on Product Page** and save before continuing.

**Expected Result:**
- With the toggle Off, the **What's Included** section is absent from the product page.
- Re-enabling restores the section.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 2.4 — Customer subscribes to Pro Plan
**Steps:**
1. In a separate browser session log in as `cust1@test.local`.
2. Open `Pro Plan` and click **Add to Cart**.
3. Go to **Cart** then **Proceed to Checkout**.
4. Complete checkout using Stripe test card `4242 4242 4242 4242` (or BACS / COD as a manual fallback).
5. After checkout, leave the order on **Order received** screen.
6. As admin, **WP-Admin → ArraySubs → Subscriptions** and confirm a new subscription exists for `cust1@test.local` with status **Active** (for a manual gateway you may need to mark the order as **Processing** or **Completed** to activate).

**Expected Result:**
- The new subscription appears in the admin subscriptions list with `Active` status.
- Customer's My Account dashboard shows the order.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 2.5 — Verify the My Features menu item
**Steps:**
1. As `cust1@test.local`, go to **My Account** (`/my-account/`).
2. Look at the My Account navigation sidebar.
3. Confirm a menu item labelled **My Features** is present.
4. Click **My Features**.

**Expected Result:**
- The **My Features** entry appears in the My Account sidebar after **Subscriptions** (or after **Orders** if Subscriptions is not present).
- Clicking it loads `/my-account/features/` without a 404 or PHP error.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 2.6 — Verify per-subscription entitlement table
**Steps:**
1. On `/my-account/features/`, read the page heading and content.
2. Locate the section header for the `Pro Plan` subscription. It should show the product name and a `Subscription #<ID>` link.
3. Read the entitlement table.

**Expected Result:**
- Page heading is `My Features` (default) — or the custom heading if one was set.
- A single section (because the customer has one subscription) is rendered.
- The table shows columns **Feature**, **Type**, **Your Entitlement**, **Usage**.
- The six enabled features appear with the values from 1.1:
  - `Seats` / Number / `5`
  - `API Calls` / Number / `10000`
  - `Storage (GB)` / Number / `10`
  - `Priority Support` / Toggle / `✓` or `Yes`
  - `Custom Domain` / Toggle / `✗` or `No`
  - `Plan Tier` / Text / `Starter`
- The disabled `Hidden Beta Flag` is absent.
- The Usage column appears for number-type rows; toggle and text rows show no usage value.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 2.7 — Confirm subscription link works
**Steps:**
1. Click the `Subscription #<ID>` link in the section header.

**Expected Result:**
- The customer is taken to the subscription detail page in their portal.
- No 403 or 404; the URL matches the customer's own subscription.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 2.8 — Custom My Account page title
**Steps:**
1. As admin, **ArraySubs → Settings → Feature Manager → My Account Page Title** = `My Plan Benefits`.
2. Click **Save Settings**.
3. Reload `/my-account/features/` as `cust1`.
4. Restore the title to blank after this check.

**Expected Result:**
- The page heading now reads `My Plan Benefits`.
- Reverting the field to blank restores `My Features`.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

## Regression / Cross-checks
- Verify the storefront product page does not render the section for any non-subscription product.
- Confirm `/my-account/features/` is unreachable for the guest user — visiting the URL while logged out redirects to the WooCommerce login.
- DevTools Network panel: no 404 / 500 responses for Feature Manager assets on either the product page or My Features.

## Sign-off
- Tester:
- Date:
- Browser & version:
- Notes:
