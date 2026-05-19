# Stage 15 — Manage Members (Pro)

**Goal:** Verify the Pro **Manage Members** experience: search any customer by username or email with debounced AJAX results; open a rich profile card with stats, roles, addresses, and quick links; impersonate a customer with **Login as Customer**; review their full commerce history (subscriptions, non-subscription products, store credit balance with adjustments); and confirm the Member Insight metrics on the stats grid render correct values consistent with the data created in earlier stages.

**Prerequisites:**
- Stage 14 complete (admin subscription management verified).
- ArraySubs Pro is active and the **Member Insight** feature is enabled.
- **Login as User** is enabled in **ArraySubs → Settings → Toolkit**.
- The Pro **Store Credit** feature is enabled and at least one customer has a non-zero balance plus several adjustments on file.
- At least three test customers exist with mixed history: one with multiple active subscriptions, one with cancelled history only, one with both subscription orders and one-time product orders.

**Run order:**
1. [01 — Member search](01-member-search.md) — Username + email search, pagination, result accuracy, debounce, count badges.
2. [02 — Member profile](02-member-profile.md) — Profile card identity, contact, roles, addresses, quick links, Login as Customer impersonation.
3. [03 — Member commerce overview](03-member-commerce-overview.md) — Subscriptions table, non-subscription Purchased Products, Store Credit balance + adjustments tab.
4. [04 — Member operations and insights](04-member-operations-and-insights.md) — Open subscription detail from member view, manage store credit adjustments, verify Member Insight metric calculations.

**Exit criteria:**
- Searching a customer by username and by email returns matching users with the documented `subs (active / total)` indicator.
- The selected customer's profile loads with avatar, full name, email, phone, joined date, role badges, and the six-card stats grid.
- The **Login as Customer** impersonation flow works end-to-end including the frontend banner and switch-back.
- The Subscriptions table, Purchased Products (Non-Subscription) table, and Addresses card render with correct counts and values.
- Store credit balance matches what is shown on the dedicated Store Credit page; opening the credit transaction history shows the recent adjustments.
- The five admin-shortcut integrations (subscription list customer link, WC order edit, WC order preview, WP user edit, WC Analytics customers row) all open the correct member profile at `#/manage-members/{user_id}`.
- Each Member Insight metric (Total Spent, Total Orders, Active Subscriptions, Total Subscriptions, Store Credit, Total Refunds) matches a manual recalculation from the customer's order and subscription data.
