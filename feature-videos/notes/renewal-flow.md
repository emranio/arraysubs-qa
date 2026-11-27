# renewal-flow — storyboard and store-change log

Feature page: `web-content/src/pages/features/renewal-flow.astro` (tier: Free; Renew early is Pro and is not shown).
Scene: `scenes/renewal-flow.mjs`. Clean map: `clean-map.d/renewal-flow.json` (display-only: `demo.260927.NNN@example.test` -> `first.last@example.com` for the failure-queue customers).

## Storyboard (8 chapters, ~4 min)

1. **Settings: Set the timing once.** Renewal Flow > Grace Period: invoice lead (3 days on this store), Days Active After Due 3, Days On-Hold Before Cancel 7, timeline alert. Email Reminders tab: reminder 3 days, expiring soon 7 days, per-minute cap (0 = unlimited, extras re-queued).
2. **Settings: Retry declined cards.** Renewal Settings > Payment Retries: 3 retry gaps (dropdown opened: 6 h/12 h/1 day/2 days), hard-decline alert, Stripe/Mollie-only alert; Renewal Performance spread window.
3. **Emails: Every email, ready.** Settings > Emails: Renewals & payments group, cancellation emails, Payment Failed (Admin) team alert; opens Renewal Payment Failed in WooCommerce: placeholders, preview, Pay Now.
4. **Admin: An invoice goes out.** Subscription 29689 (Theo Ferrari, user 929, Plus Membership $39/mo, bank transfer, due Dec 2). Renewal invoice created off-camera in `before()` via `RenewalProcessor::createRenewalInvoice()` (same path the scheduler uses). Shows Pending renewal order + timeline (invoice created, invoice email sent).
5. **My Account: The customer pays.** As user 929: subscription page, Related Orders Pay button, order-pay page, Direct bank transfer, Order received.
6. **Admin: Paid, the date moves on.** WooCommerce order edit: status On hold -> Completed (Update). Subscription detail: next payment Jan 2, timeline with payment + receipt email.
7. **My Account: When a card is declined.** Read-only: subscription 25905 (customer 65, Stripe, On Hold, insufficient funds). Notes (failure reason, on hold after grace), Retry Payment + Pay, retry confirm dialog (cancelled, nothing charged).
8. **Admin: Failures and health.** Renewal Flow > Renewal Failures (plain reasons, Retry / Mark Resolved; nothing clicked). Home: renewal outlook 7/30/90 days, Billing operations (queue healthy), Action Center overdue/failed renewals.

Shared settings: none changed.

## Store changes (log)

| When (local) | What | IDs | Undo |
| --- | --- | --- | --- |
| 2026-11-28 ~03:00 | Practice run: created renewal invoice via `RenewalProcessor::createRenewalInvoice(29727)` (Austin Tran, user 939, Plus Membership, bacs). Before: next=2026-12-06 11:20:12 UTC, completed=3, last=2026-11-06 11:20:12, orders=[29728,29729,29730] | sub 29727, new order 32088 | Trash order 32088, remove it from `_order_ids`, delete `_pending_renewal_order_id` (or simply let it be paid; it is a valid renewal) |
| 2026-11-28 ~03:01 | Practice: paid order 32088 as customer 939 on order-pay with Direct bank transfer (-> On hold), then WP-CLI `update_status('completed')`. Sub 29727 now next=2027-01-06 11:20:12, completed=4, last=2026-11-27 21:01 UTC. Renewal emails (invoice, payment successful) went to austin.tran@example.com (local mail only). | sub 29727, order 32088 | Valid paid renewal; leave as is. To fully revert: set order 32088 to cancelled/trash, restore `_next_payment_date`=2026-12-06 11:20:12, `_completed_payments`=3, `_last_payment_date`=2026-11-06 11:20:12, remove 32088 from `_order_ids`, reschedule renewal actions |
| 2026-11-28 ~04:05 | Recording (ch 4): created renewal invoice via `RenewalProcessor::createRenewalInvoice(29689)` (Theo Ferrari, user 929, Plus Membership $39/mo, bacs). Before: next=2026-12-02 13:47:30 UTC, completed=3, orders=[29690,29691,29692], no pending order. Renewal Invoice email sent to theo.ferrari@example.com (local mail). | sub 29689, new order 32265 | see next row |
