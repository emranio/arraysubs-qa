# Stage 06 — Task 03: Subscription Detail Screen — Cards Verification

| Key | Value |
|---|---|
| Stage | Initial Subscription Lifecycle |
| Module | Subscription Operations — Detail Screen |
| Plugin Coverage | Both |
| Estimated Time | 25 min |
| Depends On | Task 01 (record located), Task 02 (statuses confirmed) |

## Objective
Open the subscription detail screen for the Stage 05 Task 01 Active subscription. Verify every always-visible card renders with the correct data, and that the Payment Timeline lists the initial paid order with the correct timestamp. Per the manual the always-visible cards are: Subscription Info, Customer Information, Product, Billing Information, Billing Address, Shipping Address, Order History, Payment Timeline, Subscription Notes (the Skip & Pause card is also always-visible but in a disabled state if features are off — covered briefly here).

## Pre-conditions
- Stage 05 Task 01 subscription exists with status Active.
- Administrator login.

## Test Data
- Subscription ID: from Stage 05 Task 01 sign-off (`__________`).
- Expected customer email: `customer-classic@example.test`.
- Expected product: `Basic Monthly`.
- Expected recurring amount: `$29.99`.

## Sub-Tasks

### Sub-Task 3.1 — Open the detail screen and verify the header
**Steps:**
1. As Administrator, go to **ArraySubs → Subscriptions** and click **View Details** on the Stage 05 Task 01 subscription.
2. Read the header area at the top of the page.

**Expected Result:**
- The page title reads `Subscription #<ID>`.
- A subtitle / display title is shown.
- A green **Active** status badge is shown.
- Header action buttons include at least **Cancel Subscription**, **Edit Subscription**, and (if Login as User plugin is active) **Login as Customer**.
- The "Cancels at end of period" notice is NOT present.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 3.2 — Verify the Subscription Info card
**Steps:**
1. Locate the **Subscription Info** card.
2. Read every row.

**Expected Result:**
- Subscription ID: `#<ID>` matches the URL.
- Created: Stage 05 Task 01 timestamp.
- Start Date: Stage 05 Task 01 timestamp.
- Next Payment: Stage 05 Task 01 date + 1 month.
- Last Payment: Stage 05 Task 01 timestamp (the initial order).
- Total Paid: `$29.99` (one completed payment so far).
- Trial Length / Trial End Date: NOT shown (this product has no trial).
- End Date: NOT shown (subscription is not cancelled or expired).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 3.3 — Verify the Customer Information card
**Steps:**
1. Locate the **Customer Information** card.
2. Read each row.

**Expected Result:**
- Customer Name: links to the customer's profile.
- Email: `customer-classic@example.test`, clickable mailto link.
- Invoice Email: shows `customer-classic@example.test` or `Same as customer email`.
- Date Registered: the timestamp of the user account creation.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 3.4 — Verify the Product card
**Steps:**
1. Locate the **Product** card.
2. Read each row.

**Expected Result:**
- Product Image: thumbnail loads (no broken image icon).
- Product Name: `Basic Monthly` linked to the WooCommerce product edit page.
- SKU: shown if the product has one (note value here: __________).
- Quantity: `1`.
- Variation attributes: NOT shown (simple product).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 3.5 — Verify the Billing Information card
**Steps:**
1. Locate the **Billing Information** card.
2. Read each row.

**Expected Result:**
- Recurring Amount: `$29.99`.
- Billing Schedule: `Every 1 month(s)`.
- Different Renewal Price: NOT shown (this product has no different renewal price).
- Signup Fee: NOT shown (no signup fee).
- Completed Payments: `1`.
- Payment Method: `Direct bank transfer` (or the configured manual gateway label).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 3.6 — Verify the Billing Address and Shipping Address cards
**Steps:**
1. Locate the **Billing Address** card.
2. Locate the **Shipping Address** card.
3. Read each.

**Expected Result:**
- Billing Address card shows the full formatted address used at checkout (name, company if any, street, city, state, postcode, country, email, phone).
- Shipping Address card shows the same address (or whatever was entered if "Ship to a different address" was used during Stage 05 Task 01).
- No "Address not set" placeholder appears.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 3.7 — Verify the Order History card and the initial order
**Steps:**
1. Locate the **Order History** card.
2. Read the header and the table.

**Expected Result:**
- The card header may include a **Total Refunded** badge — for this subscription the badge should NOT appear (no refunds).
- The table contains at least one row with: Order ID (linked), Date (Stage 05 Task 01 date), Status `Completed`, Total `$29.99`, Refunded `—`, Type `Initial`, Actions `View Order`.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 3.8 — Verify the Payment Timeline card lists the initial paid order
**Steps:**
1. Locate the **Payment Timeline** card.
2. Read the most recent timeline events.

**Expected Result:**
- At least one event with the timeline label **`Renewal Payment Successful`** OR **`Subscription Activated`** OR an equivalent system-level note for the initial activation appears at the top.
- The event has a green icon marker.
- The event description references the order ID and total `$29.99` and includes a **View Order** link.
- The timestamp of the event matches the Stage 05 Task 01 order completion time (within 1 minute tolerance).
- Events are sorted newest-first; the timeline shows up to 5 by default with a **Show All** button.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 3.9 — Verify the Subscription Notes panel
**Steps:**
1. Scroll to the **Subscription Notes** panel at the bottom.
2. Read each note.

**Expected Result:**
- A chronological list of notes is shown, newest first.
- At least one system note (e.g., "Subscription activated", "Order #X marked as paid") is present with author badge **System**.
- Each note shows date, author badge, and a Private/Customer indicator.
- The Add Note form with rich-text editor and the Private/Customer type selector is visible.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 3.10 — Confirm Skip & Pause card behavior
**Steps:**
1. Locate the **Skip & Pause** card.
2. Read its content.

**Expected Result:**
- The card is always visible per manual.
- If skip and pause features are enabled in settings, the card shows the action buttons (Skip Next Renewal / Pause Subscription).
- If they are disabled, the card shows a "feature disabled" message instead.
- Record the actual state observed: __________.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

## Regression / Cross-checks
- Repeat Sub-Tasks 3.2 and 3.5 on the Trial subscription from Stage 05 Task 03 — Subscription Info card should now also include Trial Length `14` and Trial End Date.
- Confirm conditional cards are correctly absent on this Active subscription: Cancellation Details, Pending Plan Switch, Coupon Discount, Payment Gateway, Checkout Builder Fields, Subscription Shipping (none should appear unless a Stage 05 task added the relevant data).
- Confirm the Edit Subscription header button opens the edit form, that Cancel Subscription opens the cancel modal, and that Login as Customer (if available) opens the storefront in a new session.

## Sign-off
- Tester:
- Date:
- Browser & version:
- Subscription ID verified:
- Notes:
