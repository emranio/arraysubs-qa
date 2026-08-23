# SLT-REF-09 — Stripe and Paddle mechanics

Fresh-cycle guide updated 2026-08-22. ArraySubs core owns both gateway integrations. Pro may add a
premium workflow, but no ArraySubs automatic-payment class, route, renewal, webhook, refund or
payment-method service may require Pro.

## Stripe primary path

- Official WooCommerce Stripe remains the host/vendor surface.
- Reconcile local invoice/payment actions, saved method/customer IDs, PaymentIntent/charge,
  SCA/decline/retry, webhook replay, refund/cancel and the next action pair.

## Paddle parity path

- Reconcile hosted checkout, product/price IDs, pending-to-webhook-paid state, remote subscription
  and billing date, transaction/webhook replay, method-update URL, remote switch price and supported
  refund/cancel.
- For an unsupported capability, prove the action is hidden/refused with zero partial mutation.

## Current sources

- `arraysubs/src/Features/AutomaticPayments/Gateways/Stripe/`
- `arraysubs/src/Features/AutomaticPayments/Gateways/Paddle/`
- `arraysubs/src/Features/AutomaticPayments/REST/`
- `arraysubs/src/Features/AutomaticPayments/Services/WebhookRouter.php`
- `documentations/architecture/gateway-sync.md`

PayPal and Mollie are not configured or executed in this cycle.
