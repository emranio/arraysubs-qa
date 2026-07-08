---
id: 176
title: 'stage-21: Stripe auto-renewal sends invoice email before auto-charge'
status: closed
priority: high
created: 2026-07-08T01:00:16.263261945+02:00
updated: 2026-07-08T08:02:00.204063716+02:00
started: 2026-07-08T07:36:03.913585795+02:00
completed: 2026-07-08T08:02:00.204062964+02:00
tags:
    - qa
    - stage-21
    - flexible-renewal-sync
    - stripe
    - email
    - renewals
class: standard
---

QA progress task: #194, Stage 21, task 07.
QA plan path: qa/stages/21-flexible-renewal-sync/07-renewal-execution-and-advancement.md

Affected subscription/order IDs: subscription #8751, renewal order #8842, PaymentIntent pi_3Tqhx9JG5OzSNVs20n8KiXub, charge ch_3Tqhx9JG5OzSNVs20bBn84YT.
Affected WordPress user/customer IDs: WP user/customer ID 334, login sync.stripefull, email sync-stripe-full-20260708-0412@example.test, role customer.

Exact test URL/admin route: WP-CLI time travel and Action Scheduler execution for subscription #8751; customer-facing email evidence captured in Dev Assist QA Mail Log after activating the QA mail logger. Browser/user context: admin/CLI QA run against https://mirror-help.arrayhash.com.

Reproduction steps:
1. Set subscription #8751 _next_payment_date to 2026-07-06 00:00:00 UTC.
2. Run action #9104 arraysubs_generate_renewal_invoice, then action #9105 arraysubs_process_renewal.
3. Inspect renewal order #8842, subscription #8751, subscription notes, and QA Mail Log.

Expected result: Stripe renewal auto-charges off-session for $30.00 and should not send a renewal-invoice / please-pay email to the customer. Only the payment-success email should be sent after the automatic charge succeeds.

Actual result: The Stripe off-session payment succeeded, but a renewal invoice email was sent first. QA Mail Log shows 2026-07-07 22:58:55 UTC to sync-stripe-full-20260708-0412@example.test with subject "[mirror-help.arrayhash.com] Invoice for subscription #8751". Subscription note #8844 says "Email sent: [ArraySubs] Renewal Invoice" and order #8842 meta has _arraysubs_renewal_invoice_email_sent=yes. The payment-success email followed at 2026-07-07 22:59:04 UTC.

Concrete proof:
- Renewal order #8842 status completed, total $30.00, payment=stripe, _stripe_intent_id=pi_3Tqhx9JG5OzSNVs20n8KiXub, _stripe_charge_id=ch_3Tqhx9JG5OzSNVs20bBn84YT.
- Subscription #8751 stayed active, completed payments advanced to 2, next payment re-anchored to 2026-08-31 18:00:00 UTC.
- QA Mail Log captured both "Invoice for subscription #8751" and "Payment received for subscription #8751" for the same automatic renewal.

Known scope notes/counterexamples: Manual renewal invoice emails are expected and were captured for manual subscriptions #8705 and #8728. This issue is specific to automatic Stripe renewal processing where the renewal is paid off-session without customer interaction.

[[2026-07-08]] Fix applied: EmailManager::on_renewal_invoice_created (arraysubs/src/Features/Emails/Services/EmailManager.php) now skips the renewal-invoice email when the subscription is an automatic-payment subscription (arraysubs_is_automatic_payment_subscription) with auto-renew enabled — those renewals are charged off-session moments later, so the customer only gets the payment-success email. Manual gateways and auto-renew-disabled subscriptions still receive the invoice email. Verified live: time-traveled subscription #8751, ran arraysubs_generate_renewal_invoice + arraysubs_process_renewal; renewal order #9068 auto-charged off-session (completed, $30.00, pi_3Tqo85JG5OzSNVs20QpApygX). QA Mail Log newest entry is only "Payment received for subscription #8751" — no new "Invoice for subscription #8751" entry; order meta _arraysubs_renewal_invoice_email_sent empty, _arraysubs_renewal_payment_success_email_sent=yes. Subscription advanced to next=2026-09-30 18:00:00 (also confirms #177 fix on Stripe path). Counterexample kept intact: manual BACS renewal for #8682 during this same session did send its invoice email before payment (mail log shows Invoice → Payment received for #8682).
