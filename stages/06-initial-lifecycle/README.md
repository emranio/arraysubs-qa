# Stage 06 — Initial Subscription Lifecycle

**Goal:** Verify that the subscription records created during Stage 05 are correctly persisted, that their status reflects the checkout path (Active, Trial, Pending), that the admin subscription detail screen renders every always-visible card with accurate data, that the customer-facing portal shows the same record, that the parent order page displays the Related Subscriptions table, and that the customer-facing and admin lifecycle emails (New Subscription / Trial Started / Admin New Subscription) were delivered with placeholders rendered correctly.

**Prerequisites:**
- Stage 05 completed successfully — at least one Active subscription, one Trial subscription, and one Pending subscription (or an order in pending payment) exist.
- Stage 00 mail-delivery check confirmed inbound email is reachable for all test users.
- Administrator access plus access to each test customer's mailbox.

**Reusable subscriptions (created in Stage 05):**
- The Active subscription from Task 01 (`Basic Monthly`, customer `customer-classic@example.test`).
- The Trial subscription from Task 03 (`Trial 14-Day`, customer `customer-trial@example.test`).
- A Pending subscription created on demand in Task 02 of this stage (we will leave one Stage 05 order in `Pending payment` to drive that case).

**Run order:**
1. [01 — Subscription record after checkout](01-subscription-record-after-checkout.md) — Open WP Admin → ArraySubs → Subscriptions and verify the subscription created in Stage 05 appears with: customer, product, status, recurring amount, next payment date, parent order link.
2. [02 — Status: Active vs Trial vs Pending](02-status-active-vs-trial-vs-pending.md) — Confirm trial subscription starts as Trial, manual-paid subscription is Active, and a subscription whose order is in pending payment is Pending.
3. [03 — Subscription detail screen](03-subscription-detail-screen.md) — Open the subscription detail screen and verify every always-visible card: Subscription Info, Customer Information, Product, Billing Information, Billing Address, Shipping Address, Order History, Payment Timeline, Subscription Notes.
4. [04 — Customer portal display](04-customer-portal-display.md) — As the customer, log in and verify the My Subscriptions list and the View Subscription detail page.
5. [05 — Order page Related Subscriptions](05-order-page-related-subscriptions.md) — Open the parent order on the customer-facing order details page and verify the Related Subscriptions table.
6. [06 — Emails on creation](06-emails-on-creation.md) — Confirm the customer received New Subscription / Trial Started email and admin received Admin New Subscription email; both placeholders rendered.

**Exit criteria:**
- Every Stage 05 subscription is found in **ArraySubs → Subscriptions** with correct status, product, customer, recurring amount, and next payment date.
- The detail screen renders all always-visible cards with non-empty values for Active and Trial subscriptions.
- The Payment Timeline lists the initial paid order with the correct timestamp.
- The customer portal table and detail page mirror the admin data (status, next payment, recurring total).
- Related Subscriptions table appears on the customer-facing order details page for every Stage 05 order.
- All expected creation emails (customer + admin) were received with placeholders rendered correctly.
