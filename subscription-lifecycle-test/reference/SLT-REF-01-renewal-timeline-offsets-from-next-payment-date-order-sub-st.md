# SLT-REF-01 — renewal timeline and relationship map

Fresh-cycle guide updated 2026-08-22. This is a source map, not execution evidence. Re-read the
current code and record the live setting/action values before using any timing assertion.

## Canonical flow

1. `_next_payment_date` is the billing due date in UTC.
2. `RenewalScheduler` schedules the invoice and payment legs through the centralized
   `ArraySubs\Supports\ActionScheduler` constants/groups.
3. The deterministic spread changes action execution time, not the stored billing due date.
4. `OrderCreation` stamps the renewal order with `_renewal_scheduled_date` and exact subscription
   relationships.
5. `RenewalProcessor` checks that the subscription is due, creates/reuses the owned invoice and
   delegates the automatic payment.
6. Paid-order handling advances counters/dates, clears the pending pointer and queues the next
   invoice/payment pair.

## Current sources

- `arraysubs/src/Features/RecurringBilling/Services/RenewalScheduler.php`
- `arraysubs/src/Features/RecurringBilling/Services/OrderCreation.php`
- `arraysubs/src/Features/RecurringBilling/Services/RenewalProcessor.php`
- `arraysubs/src/Features/Subscriptions/Services/OrderIntegration.php`
- `arraysubs/src/functions/renewal-spread-helpers.php`
- `documentations/architecture/action-scheduler-system.md`

## QA rule

Derive `k`, due time, invoice/charge action IDs and provider ownership from the fresh subscription.
Assert windows and bidirectional relationships; never reuse an authored numeric ID or drain a hook.
