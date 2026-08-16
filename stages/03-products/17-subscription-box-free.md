# Stage 03 — Task 17: Subscription Box (Free)

| Key | Value |
|---|---|
| Stage | Subscription Product Creation |
| Module | Subscription Box product type, admin wizard, storefront builder |
| Plugin Coverage | ArraySubs Free |
| Estimated Time | 30 min |
| Depends On | Stage 03 Task 01; one purchasable simple WooCommerce product |

## Objective

Verify that Subscription Box is owned entirely by ArraySubs core: it can be configured and purchased while ArraySubsPro is inactive, its optional renewal-segment plan is saved by the Core runtime, and reactivating Pro does not duplicate the product type or its UI.

## Pre-conditions

- ArraySubs is active.
- A published, in-stock simple WooCommerce product exists for the box contents.
- Administrator session available.

## Test Data

- Product name: **QA Free Subscription Box**.
- Product type: **Subscription Box [ArraySubs]**.
- Billing schedule: every 1 month, no fixed length.
- One required box step containing one Product element with maximum quantity 2.

## Sub-Tasks

### Sub-Task 17.1 — Verify the product type without Pro

1. Deactivate ArraySubsPro.
2. Open **Products → Add New**.
3. Open the Product data type selector.

**Expected Result:**

- **Subscription Box [ArraySubs]** is present exactly once.
- Selecting it keeps the General tab visible and shows the Subscription Box configuration surface.
- No Pro asset URL, Pro DOM prefix, JavaScript error, PHP error, 404, or 500 response occurs.

### Sub-Task 17.2 — Configure and save the box

1. Enter the product name.
2. Open **Configure Subscription Box**.
3. Set the test billing schedule.
4. Add one required step and one Product element using the existing simple product.
5. Enable the box's own renewal-segment plan and leave all three segments active.
6. Save the configuration and publish the product.
7. Reload the editor.

**Expected Result:**

- The shared modal and confirmation UI render correctly.
- Saving shows a visible loading state and completes without a native browser dialog.
- The summary displays the saved schedule, step, selected product, and renewal-sync plan after reload.
- The product is publishable without a fixed WooCommerce regular price.

### Sub-Task 17.3 — Build the box on the storefront

1. View the published product.
2. Click **Create Subscription Box**.
3. Select one unit of the configured product and proceed through the builder.
4. Add the box to the cart.

**Expected Result:**

- The overlay shows the configured step, product card, quantity control, live total, and navigation controls.
- The Add button shows a visible pending/loading state.
- The cart contains one Subscription Box line at the server-calculated total with a Box contents row beneath it.
- No child product is charged as a second cart line.

### Sub-Task 17.4 — Reactivate Pro and check coexistence

1. Reactivate ArraySubsPro.
2. Reload the product editor and the storefront product page.
3. Repeat the product-type selector and box-opening checks.

**Expected Result:**

- Subscription Box remains present exactly once.
- The saved configuration remains intact.
- Only Core Subscription Box assets load; there are no duplicate handlers, duplicate roots, or duplicate UI controls.
- Pro-only Subscription Bundle remains separate and available.

## Sign-off

- Tester:
- Date:
- Browser & version:
- Product ID:
- Cart/order IDs:
- Notes:
