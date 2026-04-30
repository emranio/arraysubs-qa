# Stage 06 — Task 04: Customer Portal Display

| Key | Value |
|---|---|
| Stage | Initial Subscription Lifecycle |
| Module | Customer Portal — My Subscriptions / View Subscription |
| Plugin Coverage | Both |
| Estimated Time | 15 min |
| Depends On | Task 01 |

## Objective
As the customer, log in and verify the **My Subscriptions** table at `/my-account/subscriptions/` and the per-subscription **View Subscription** detail page at `/my-account/view-subscription/{id}/` mirror the admin record from Stage 06 Task 01: column headers, status badge, next payment date, recurring total + billing schedule, and the action buttons (Cancel, Change Plan, etc.) that depend on settings.

## Pre-conditions
- Stage 05 Task 01 subscription is Active for `customer-classic@example.test`.
- ArraySubs → Settings → General Settings:
  - **Customer cancellation**: Enabled (so the Cancel Subscription button appears).
  - **Plan switching**: Enabled and the product has linked switch targets configured (so Change Plan appears).
  - Skip and Pause features: any state — record what is configured.
- The customer has access to log in.

## Test Data
- Customer: `customer-classic@example.test` with the password set in Stage 00.
- Subscription ID: from Stage 05 Task 01 sign-off.

## Sub-Tasks

### Sub-Task 4.1 — Log in and open My Subscriptions
**Steps:**
1. Open Incognito and log in as `customer-classic@example.test` via My Account.
2. Click the **Subscriptions** tab in the My Account navigation.
3. Wait for the page to load.

**Expected Result:**
- URL is `/my-account/subscriptions/`.
- The page renders the My Subscriptions table.
- Empty-state text *"You have no subscriptions yet."* is NOT shown.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 4.2 — Verify the table columns
**Steps:**
1. Read the column headers on the table.
2. Read each cell of the row representing the Stage 05 Task 01 subscription.

**Expected Result:**
- Columns present (in this order): **Product**, **Status**, **Next Payment**, **Total**, **Actions**.
- Product cell shows the subscription ID as a link plus the product name `Basic Monthly`.
- Status cell shows a green **Active** badge.
- Next Payment cell shows Stage 05 Task 01 date + 1 month.
- Total cell shows `$19.99` with `every 1 week` (or store-format equivalent) underneath. The Basic Monthly subscription, if listed, shows `$29.99` with `every 1 month`.
- Actions cell shows a **View** button.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 4.3 — Open the View Subscription detail page
**Steps:**
1. Click **View** on the row.
2. Wait for the detail page to load.

**Expected Result:**
- URL is `/my-account/view-subscription/<ID>/`.
- The page loads without an error.
- A subscription overview table is shown at the top.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 4.4 — Verify the Subscription Overview table
**Steps:**
1. Read each row of the overview table.

**Expected Result:**
- **Status** row: green **Active** badge.
- **Product** row: `Basic Monthly`.
- **Start Date** row: Stage 05 Task 01 date.
- **Next Payment** row: Stage 05 Task 01 date + 1 month.
- **Recurring Amount** row: `$19.99` with the billing schedule `every 1 week` for Standard Weekly. (For the Basic Monthly subscription verify `$29.99` / `every 1 month`.)
- **End Date** row: NOT shown (no end date set).
- **Coupon Discount** row: NOT shown (no recurring coupon on this subscription).
- **Payment Method** row: shows the manual gateway name with a link to `My Account → Payment methods`.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 4.5 — Verify the Subscription Actions buttons
**Steps:**
1. Look below the overview table for the **Subscription Actions** section.
2. Inspect which buttons are present.

**Expected Result:**
- The section appears because the subscription is Active.
- **Change Plan** button appears IF plan switching is enabled and the `Basic Monthly` product has at least one linked upgrade/downgrade/crossgrade target (it does — `Pro Monthly` is linked).
- **Cancel Subscription** button appears because customer cancellation is enabled.
- Clicking each button does not throw a JS error (do not submit; just observe modal / form opens).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 4.6 — Verify the Skip and Pause section behavior
**Steps:**
1. Look for the **Manage Your Subscription** section beneath the actions.
2. Note whether Skip Next Renewal and/or Pause Subscription controls are present (or if a "feature disabled" message is shown).

**Expected Result:**
- The visibility matches the General Settings configuration:
  - If Skip Renewal is enabled: a **Skip Next Renewal** button appears.
  - If Pause Subscription is enabled: a **Pause Subscription** button appears.
  - If both are disabled: the section is absent.
- Record actual state: __________.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 4.7 — Verify the Related Orders table
**Steps:**
1. Scroll down to the **Related Orders** table.

**Expected Result:**
- Table columns: Order, Date, Status, Total, Actions.
- A row for the Stage 05 Task 01 initial order is present with: order number (linked), Stage 05 Task 01 date, status `Completed`, total `$29.99`, action `View`.
- No `Pay` action appears for this row (the order is paid).
- No `Invoice` action unless a PDF invoice plugin is installed.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 4.8 — Verify Subscription Notes are visible to the customer
**Steps:**
1. Scroll to the bottom of the page where customer-visible subscription notes appear.

**Expected Result:**
- Any system-generated notes that were marked customer-visible are listed.
- Each note shows author badge (System / Staff / Customer), content, timestamp.
- Notes marked Private (admin-only) do NOT appear here.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

## Regression / Cross-checks
- Try to load `/my-account/view-subscription/<ID>/` for a subscription belonging to a different user (replace `<ID>` with another customer's subscription) — the page must show *"Subscription not found or you do not have permission to view it."*
- Confirm the Subscriptions tab label and position match what is configured in **ArraySubs → Profile Builder → My Account**.
- Confirm pagination is shown if the customer has more than 10 subscriptions (per manual).

## Sign-off
- Tester:
- Date:
- Browser & version:
- Subscription ID verified:
- Notes:
