# Stage 14 — Task 02: Create Subscription From Admin

| Key | Value |
|---|---|
| Stage | Admin Subscription Management |
| Module | Admin SPA — Add New Subscription |
| Plugin Coverage | Free |
| Estimated Time | 25 min |
| Depends On | 14-01 (list and filters), Stage 03 (subscription products published) |

## Objective
Open **ArraySubs → Subscriptions → Add New** and create a **Standard Weekly**-style subscription from admin: complete every section of the create form (Customer, Product & Subscription Details with a variation, Trial Settings, Different Renewal Price, Billing Information & Addresses). Save the form and verify the subscription is created with the chosen weekly values, lands in **Pending** status with a temporary title like `Subscription #<timestamp>`, and that the redirect returns the tester to the subscriptions list.

## Pre-conditions
- Logged in as Administrator.
- A variable weekly subscription product exists with at least two variations (e.g., `PM Tool` from Stage 03 — Variable Weekly $9.99 / Bi-weekly $14.99), each with a different recurring amount.
- A simple weekly subscription product (e.g., `Standard Weekly` $19.99/week) also exists for cross-check.
- A test customer account exists (e.g. `customer1@arraysubs.test`).
- Stripe is already configured in test mode (no setup required).
- DevTools Network tab open to capture the POST request on save.

## Test Data
- Customer: `customer1@arraysubs.test`.
- Product: `PM Tool` (variable weekly) — variation `Plan: Weekly`.
- Quantity: `2`.
- Recurring Amount override: `19.99`.
- Billing Interval: `1`.
- Billing Period: `Week(s)`.
- Subscription Length: `12` (12 weeks total).
- Signup Fee: `4.99`.
- Trial Length: `7`, Trial Period: `Day(s)`.
- Different Renewal Price: enabled, `14.99` after `3` payments.
- Invoice Email: `billing@arraysubs.test`.
- Billing Address: First Name `Jane`, Last Name `Cooper`, Company `Acme`, Phone `+1 555 0100`, Address Line 1 `100 Main St`, City `Austin`, State `TX`, Postcode `78701`, Country `United States (US)`.
- Shipping Address: same as billing minus phone, but Address Line 1 `100 Warehouse Way`.

## Sub-Tasks

### Sub-Task 2.1 — Open the Add New form
**Steps:**
1. Click **ArraySubs → Subscriptions**.
2. Click the **Add New** button at the top of the list.
3. Wait for the create form to render.

**Expected Result:**
- Page heading reads **Create Subscription** (or equivalent).
- Five form sections are visible in this order: General, Product & Subscription Details, Trial Settings, Different Renewal Price, Billing Information & Addresses.
- The **Create Subscription** submit button is rendered at the bottom.
- No JavaScript console errors.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 2.2 — Pick a customer (AJAX search)
**Steps:**
1. In the General section, click the **Customer** searchable select.
2. Type `customer1` and wait for the AJAX dropdown.
3. Click the matching result.

**Expected Result:**
- Results load on demand (network call visible in DevTools).
- The customer's display name and email show in the selected pill.
- No autosaving — values are not persisted yet.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 2.3 — Select product, variation, quantity, and confirm auto-fill
**Steps:**
1. In Product & Subscription Details, pick `PM Tool` from **Subscription Product**.
2. The **Product Variation** select must appear because it's variable. Pick `Plan: Weekly`.
3. Confirm the form auto-fills Recurring Amount, Billing Interval, Billing Period, Subscription Length, Signup Fee, and Trial Settings from that variation.
4. Override Recurring Amount to `19.99`, Subscription Length to `12`, Signup Fee to `4.99`, Quantity to `2`.

**Expected Result:**
- Auto-fill happens immediately after selecting the variation (per the info-box in the manual).
- Each numeric field accepts the override.
- Billing Period stays as `Week(s)` and Billing Interval stays as `1`.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 2.4 — Trial settings and Different Renewal Price
**Steps:**
1. In Trial Settings set **Trial Length** to `7` and **Trial Period** to `Day(s)`.
2. In Different Renewal Price flip the **Enable Different Renewal Price** toggle ON.
3. Enter `14.99` in **Different Renewal Price** and `3` in **After How Many Payments**.

**Expected Result:**
- Different Renewal Price and After How Many Payments inputs are hidden until the toggle is on, then visible.
- Minimum value for After How Many Payments is 1 (entering 0 is blocked or shows validation).
- Trial fields accept the values without error.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 2.5 — Fill billing & shipping addresses and invoice email
**Steps:**
1. In Billing Information & Addresses, set **Invoice Email** to `billing@arraysubs.test`.
2. Fill the Billing Address card with the Test Data fields above (First Name, Last Name, Company, Phone, Address Line 1, City, State, Postcode, Country).
3. Fill the Shipping Address card the same way (no Phone field present).
4. Confirm Address Line 1 in Shipping is `100 Warehouse Way` (different from billing).

**Expected Result:**
- Country select shows the same WooCommerce country list used at checkout.
- State field becomes a select for US (Texas / TX selectable).
- Phone field is present on Billing only — absent from Shipping.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 2.6 — Save and verify creation
**Steps:**
1. Click **Create Subscription**.
2. Watch the DevTools Network tab for the POST request and its 2xx response.
3. Wait for the redirect back to the Subscriptions list.

**Expected Result:**
- The redirect lands on **ArraySubs → Subscriptions**.
- A new subscription appears at the top of the list with status **Pending** (orange badge).
- The subscription title (visible by hovering or clicking through) reads `Subscription #<timestamp>` per the manual.
- Customer column shows Jane Cooper / customer1@arraysubs.test.
- Product column shows `PM Tool — Plan: Weekly`.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 2.7 — Verify saved values on detail screen
**Steps:**
1. Click **View Details** on the new row.
2. Inspect each card for the values entered.

**Expected Result:**
- **Customer Information** card shows Jane Cooper, the chosen email, Invoice Email = `billing@arraysubs.test`.
- **Product** card shows `PM Tool` with variation `Plan: Weekly`, quantity `2`.
- **Billing Information** card shows Recurring Amount `$19.99`, Billing Schedule `Every 1 week(s)`, Signup Fee `$4.99`, Different Renewal Price `$14.99` after `3` payments.
- **Billing Address** and **Shipping Address** cards show the correct addresses with the warehouse address on shipping.
- Status badge in the header is **Pending** with orange colouring.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 2.8 — Optional pending invoice order check
**Steps:**
1. While on the detail page, scroll to the **Order History** card.
2. Note whether a pending payment order has been created automatically.
3. Cross-reference **WooCommerce → Orders** for any new "pending payment" order linked to this subscription.

**Expected Result:**
- Per the manual, manually created subscriptions start as **Pending**. An associated WooCommerce order is created only if the configured creation flow generates one — record the actual behaviour observed (created or not, and what status).
- If an order is generated, it should be marked **Pending payment** in WooCommerce and linked to the subscription via the Order History card.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

## Regression / Cross-checks
- Repeat the flow with a **simple** subscription product (no variation) and confirm the variation select is hidden.
- Try saving with required fields empty — confirm inline validation blocks the save and the form does not POST.
- Confirm the new subscription does not silently auto-activate; it must remain Pending until manually moved to Active.

## Sign-off
- Tester:
- Date:
- Browser & version:
- New subscription ID:
- Notes:
