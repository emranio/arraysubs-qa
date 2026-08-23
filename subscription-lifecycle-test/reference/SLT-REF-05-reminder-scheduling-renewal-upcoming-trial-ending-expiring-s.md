# SLT-REF-05 — reminder and expiry scheduling

Fresh-cycle guide updated 2026-08-22. This build must be tested as fixed; an older absence of a
scheduler is not an expected result.

## Required rows

- Upcoming renewal reminder: live lead days, exact subscription/date/action and one delivered mail.
- Trial ending: trial end, lead days, exact action and one delivered mail before paid conversion.
- Subscription expiring soon: finite end/remaining-cycle condition, exact action and one mail.
- Stripe card expiring: provider/payment-method expiry fixture, exact notifier path and one mail.
- Cancelled/expired/ineligible negatives: no stale action and no mail.

Read current constants/registrations in `arraysubs/src/Supports/ActionScheduler.php`,
`arraysubs/src/Features/Emails/Services/EmailManager.php`,
`arraysubs/src/Features/AutomaticPayments/Services/CardExpiryNotifier.php` and the relevant lifecycle
scheduler. Reconcile queue, logs, UI/meta and Mailpit for every row.
