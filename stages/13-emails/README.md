# Stage 13 — Email Notifications

**Goal:** Trigger and verify every email ArraySubs and ArraySubsPro register with WooCommerce — customer lifecycle emails (16 events from new subscription through reactivation), the three admin emails (new subscription, payment failed, cancelled), the four Pro Store Credit emails (toggles, customization, placeholder rendering), the two Pro Stripe-driven emails (Card Expiring Notice and SCA Authentication Required), and the enable/disable behaviour for any individual email. This stage focuses purely on email delivery, content, and toggles — actual subscription lifecycle behaviour was already proven in earlier stages.

**Prerequisites:**
- Stages 00–12 complete.
- Mailbox or mail-capture tool reachable for `admin@test.local`, `cust1@test.local`, `cust2@test.local`, `cust3@test.local` (Mailpit, MailHog, WP Mail Logging plugin, or real inboxes).
- ArraySubs and ArraySubsPro both active.
- Stripe is already configured in test mode and is the only automatic gateway under test. Use cards `4000 0000 0000 0341` (failed renewal) and `4000 0027 6000 3184` (SCA) for Task 04. PayPal and Paddle are out of scope this cycle.
- The canonical `Standard Weekly` ($19.99/week) subscription product exists for any generic-subscription scenario, and `Basic Monthly` ($29.99/month) is kept available for the one trigger that must verify monthly placeholders.
- Time-travel method available (post-meta edit or `wp action-scheduler run --hooks=arraysubs_*`).

**Run order:**
1. [01 — Customer emails trigger matrix](01-customer-emails-trigger-matrix.md) — Trigger and verify all 16 customer lifecycle emails. For each: subject line, From address, body placeholders rendered.
2. [02 — Admin emails](02-admin-emails.md) — Trigger Admin New Subscription, Admin Payment Failed, Admin Subscription Cancelled. Confirm delivery to the configured admin recipient.
3. [03 — Store credit emails](03-store-credit-emails.md) *(Pro)* — Verify per-email enable/disable toggles, subject + body customization, and placeholder rendering for `{credit_amount}`, `{new_balance}`, `{customer_name}`, etc.
4. [04 — Pro emails](04-pro-emails.md) *(Pro)* — Verify the Stripe-driven Card Expiring Notice and SCA Authentication Required emails. Use `4000 0000 0000 0341` to fail a renewal and trigger SCA flow with `4000 0027 6000 3184`.
5. [05 — Enable/disable toggles](05-enable-disable-toggles.md) — Disable each customer email type, trigger the lifecycle event, confirm no email sent. Re-enable and confirm it sends again.

**Exit criteria:**
- Every email registered in **WC → Settings → Emails** by ArraySubs / ArraySubsPro can be triggered via a real lifecycle event and arrives at the right inbox with placeholders rendered.
- All admin emails are routed to the correct address (the WC admin email or the `emails.admin_email` override) and can be redirected via the **Recipient(s)** field.
- All Pro Store Credit emails respect their per-email enable/disable toggle.
- The Pro Card Expiring and SCA Authentication Required emails (Stripe-driven) are documented as either actively triggering or marked as not yet wired (per the user manual note) — note observed behaviour in Sign-off.
- Disabling any single email blocks delivery for that one email type only; re-enabling restores delivery.
