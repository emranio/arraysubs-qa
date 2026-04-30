# Stage 05 — Task 13: Checkout Builder Customization (Pro)

| Key | Value |
|---|---|
| Stage | Checkout Flow |
| Module | Checkout Builder |
| Plugin Coverage | Pro |
| Estimated Time | 30 min |
| Depends On | Task 01 |

## Objective
Open the Checkout Builder, add a custom **Text** field, a **Heading**, and an **Image Select** field. Save. Place a real subscription checkout — confirm the fields render in the configured positions, that conditional visibility rules work as the customer types, and that the captured values are stored against the order and (if the relevant data-flow toggle is on) copied to the subscription.

## Pre-conditions
- ArraySubsPro is active.
- **ArraySubs → Checkout Builder → Settings**:
  - **Enabled** (master toggle): ON.
  - **Copy to subscription**: ON.
  - **Show on order admin**: ON.
  - **Show on subscription detail**: ON.
  - **Uploads enabled**: ON (default Max file size 5 MB — uploads aren't required for this task but enabling is harmless).
- Customer `customer-builder@example.test` exists.
- Product `Basic Monthly` is published.
- Two image files are available locally to use as Image Select options (e.g., `option-blue.png`, `option-red.png`).

## Test Data
- Custom Text field: Label `Company Name`, key auto-generated to `_arraysubs_cf_company_name`, Required = ON.
- Heading element: H3 with content `Tell us about your company`.
- Image Select field: Label `Preferred Theme`, options `Blue` (with `option-blue.png`) and `Red` (with `option-red.png`), Required = OFF, **multi: false**.
- Conditional visibility rule: Add a Toggle called `Has company` (key `_arraysubs_cf_has_company`); the Text field `Company Name` is shown only when `Has company` is ON.

## Sub-Tasks

### Sub-Task 13.1 — Open the Checkout Builder
**Steps:**
1. As Administrator, go to **ArraySubs → Checkout Builder**.
2. Click **Open Builder**.
3. Confirm the editor opens in full-screen with a left sidebar (Elements / Design tabs) and a right editor area with at least one step tab.

**Expected Result:**
- The builder UI loads.
- The left sidebar shows the Elements palette with Standard, Advanced, and Layout categories visible.
- The right editor shows the default step layout including the locked fields (Billing Email, Billing First Name, Billing Last Name, Billing Country) marked with lock icons.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 13.2 — Add the Heading and the Toggle "Has company"
**Steps:**
1. Drag the **Heading** layout element into Step 1 above the existing fields.
2. Click the heading to open its settings. Set heading level `h3` and content `Tell us about your company`.
3. Drag the **Toggle** standard input element into Step 1 below the heading.
4. Click the toggle to open its settings. Set Label `Has company`, default value off, key `_arraysubs_cf_has_company`.
5. Save (do not exit yet).

**Expected Result:**
- Heading and Toggle render in the editor preview.
- Save action returns success.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 13.3 — Add the Text "Company Name" with conditional visibility
**Steps:**
1. Drag the **Text** standard input element into Step 1 below the toggle.
2. Set Label `Company Name`, key auto-generates to `_arraysubs_cf_company_name`, Required = ON.
3. Scroll to the **Visibility Rules** section in the field settings.
4. Click **Add Rule**.
5. Configure: reference field `Has company`, operator `is`, value `1` (or `on`).
6. Save.

**Expected Result:**
- The Text field shows the visibility rule in the field settings panel.
- Editor preview shows the field with a small "conditional" indicator if the builder displays one.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 13.4 — Add the Image Select "Preferred Theme"
**Steps:**
1. Drag the **Image Select** advanced input element into Step 1 below the Company Name field.
2. Set Label `Preferred Theme`, key `_arraysubs_cf_preferred_theme`, Required OFF.
3. In the type-specific settings, configure two options:
   - Option 1: Label `Blue`, image upload `option-blue.png`.
   - Option 2: Label `Red`, image upload `option-red.png`.
4. Set `multi: false`.
5. Save the builder.
6. Click **Save** in the toolbar.

**Expected Result:**
- The Image Select field renders two image tiles in the preview.
- Save returns success.
- Reloading the builder restores the same layout.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 13.5 — Place a real checkout and observe the fields
**Steps:**
1. In Incognito, log in as `customer-builder@example.test`.
2. Add `Basic Monthly` to cart.
3. Open the checkout page.
4. Read the layout of the form.

**Expected Result:**
- The Heading `Tell us about your company` is rendered as an H3.
- The Toggle `Has company` is rendered (off by default).
- The Text `Company Name` field is **not visible** while the toggle is off (conditional visibility hides it).
- The Image Select `Preferred Theme` is visible with two image tiles.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 13.6 — Toggle the field on and submit
**Steps:**
1. Turn **Has company** toggle ON.
2. The `Company Name` text field appears (still without page reload — conditional visibility is real-time).
3. Type `Acme Corp` into Company Name.
4. Click the `Blue` tile in Preferred Theme to select it.
5. Fill any remaining required fields, select Direct bank transfer.
6. Click **Place order**.

**Expected Result:**
- Conditional visibility reveals `Company Name` instantly.
- Submission succeeds.
- Order received page is reached.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 13.7 — Verify custom field values on the order and subscription
**Steps:**
1. As Administrator, open the new order in **WooCommerce → Orders**.
2. Confirm a meta box (or the order details panel) displays `Company Name: Acme Corp`, `Has company: 1`, `Preferred Theme: Blue` (per the Show on order admin setting).
3. Also confirm the order meta keys are `_arraysubs_cf_company_name`, `_arraysubs_cf_has_company`, `_arraysubs_cf_preferred_theme`.
4. Open the subscription's **View Details** page.
5. Locate the **Checkout Builder Fields** card.

**Expected Result:**
- All three values appear in the admin order meta box with correct keys and values.
- The Checkout Builder Fields card on the subscription detail shows: Company Name `Acme Corp`, Has company `Yes` (or `1`), Preferred Theme `Blue`.
- Per manual: *"This card only appears if the **Show on subscription detail** setting is enabled in Checkout Builder settings and the subscription has captured field data."*

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 13.8 — Negative test: hidden conditional field is not validated
**Steps:**
1. In a new Incognito session, log in as a different customer (or empty cart and start fresh).
2. Add `Basic Monthly` to cart and open checkout.
3. Leave the **Has company** toggle off.
4. Submit the order without filling Company Name.

**Expected Result:**
- Submission succeeds even though Company Name is marked Required, because hidden fields are not validated (per manual: *"Hidden fields are not validated and not submitted"*).
- The order completes.
- The order meta does NOT contain a `_arraysubs_cf_company_name` value.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

## Regression / Cross-checks
- Confirm the **Order Info / Payment** element is still present in the layout (per manual: *"Required and can only appear once... if removed, it is automatically re-added"*).
- Confirm zero JavaScript console errors on the checkout page (DevTools → Console).
- Confirm the same fields render in Block Checkout if the store uses Block Checkout — per the manual the builder supports both.
- Disable the Checkout Builder (`Enabled` toggle off) and confirm the standard WooCommerce checkout returns; re-enable to restore.

## Sign-off
- Tester:
- Date:
- Browser & version:
- Order ID created:
- Subscription ID created:
- Notes:
