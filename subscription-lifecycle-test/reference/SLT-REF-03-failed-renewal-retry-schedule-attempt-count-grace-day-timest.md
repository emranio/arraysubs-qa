# SLT-REF-03 — failed renewal, retry and grace map

Fresh-cycle guide updated 2026-08-22. Retry settings, decline classification and grace values must be
read live. Prior-cycle attempt counts or timestamps are not valid.

## Assertions to derive

- Gateway capability and retry configuration for the exact Stripe/Paddle subscription.
- One canonical renewal order per due cycle; retries must not mint duplicate orders or charges.
- Attempt counter, next-retry timestamp, failure category, notes, customer/admin mail and provider ID.
- Natural active/trial to on-hold and on-hold to cancelled sweeps using live grace settings.
- Recovery removes obsolete retry actions, settles the owned order and restores the normal schedule.

## Current sources

- `arraysubs/src/Features/RecurringBilling/Services/RenewalProcessor.php`
- `arraysubs/src/Features/RecurringBilling/Services/Hooks.php`
- `arraysubs/src/Features/AutomaticPayments/Gateways/Stripe/`
- `arraysubs/src/Features/AutomaticPayments/Gateways/Paddle/`
- `arraysubs/src/functions/gateway-helpers.php`
- `documentations/architecture/payment-retry-system.md`

Stripe is the primary decline/retry path. Paddle is tested according to its provider-owned billing
and current capability contract. PayPal and Mollie are excluded from this cycle.
