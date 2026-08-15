# ArraySubs payment migration final QA report

Date: 2026-08-15  
Environment: `https://mirror-help.arrayhash.com`  
Products: ArraySubs 1.8.12 and ArraySubsPro 1.1.3

## Outcome

All payment-migration QA issues are fixed and closed. The final live regression passed with ArraySubs alone and again with ArraySubsPro active. Stripe and Paddle remain connected in test mode, no duplicate gateway or callback ownership was observed, and no disposable QA subscription remains billable.

The QA issue board is 8/8 closed. The QA progress board is 7/7 closed.

## Fixed defects

- Stripe PaymentIntent confirmations now include a validated `return_url` on every relevant path.
- Authentication-required renewals remain customer-action pending instead of being overwritten as ordinary failures.
- The normal signed order-pay flow confirms the exact bound PaymentIntent, performs a fresh provider binding check, and clears only matching action state after success.
- Renewal reconciliation is fail-closed for non-terminal/unknown states and inconclusive pagination; per-order locking prevents replacement races.
- Genuine decline notes and customer/admin/WooCommerce failure emails are claimed once per exact provider attempt. Same-attempt replay has no duplicate effect.
- Immediate cancellation retires pending provider action and makes the exact unpaid renewal unpayable before lifecycle completion.
- A valid but elapsed cancellation boundary now falls back to that immediate-cancellation path under the existing lifecycle lock. Missing or malformed dates still fail closed.

## Live Stripe coverage

- Completed a real mixed cart with one monthly subscription product and one normal product.
- Completed the initial Stripe 3DS challenge and created exactly one subscription for the recurring line.
- Ran the automatic-renewal customer-action path, followed its ordinary order-pay link, completed a second real 3DS challenge, and settled the same exact PaymentIntent once.
- Confirmed one paid/captured charge, one completed renewal payment, cleared action/outbox state, and no retry state after settlement.
- Ran a real generic-decline renewal. It retained genuine failure behavior while producing exactly one semantic note and the three expected emails. Same-attempt replay added zero notes and zero emails.
- Retested overdue cancellation through the customer browser: the identical request changed from HTTP 500 `update_failed` to HTTP 200 after the fix, and left no payable renewal or scheduled retry.
- Woo Stripe logs contain exactly two historical pre-fix missing-`return_url` errors and no post-fix occurrence.

## Live Paddle coverage

- Completed a real Paddle sandbox mixed cart: an $11/day subscription item plus a $15 normal item, total $26.
- Provider transaction completed with one recurring and one one-time item. The provider subscription contains only the recurring item.
- Local order #27827 and subscription #27828 matched the provider amount, product, status, payment count, next billing date, webhook stream, and invoice/process schedule.
- Verified the expected four initial emails and confirmed subscription emails excluded the normal product.
- Verified future-boundary end-of-period cancellation in the customer portal, then cancelled the disposable remote subscription before removing local fixtures.

## Combined-plugin verification

- Reactivated ArraySubsPro after core-only testing.
- Checkout displayed one saved Stripe method, one Credit/Debit Card gateway, and one Paddle gateway without duplicates.
- Runtime inspection showed one core provider instance and one callback owner for gateway registration, Blocks registration, automatic renewal, and Stripe webhook paths. The Pro provider fallback stayed dormant.
- A fresh Gateway Logs reload showed Stripe and Paddle connected in test mode, Paddle count 2, Stripe count 14, recent webhook events, and no browser errors or console warnings.

## Cleanup and preserved controls

Permanently removed only disposable local QA fixtures after guarded verification:

- Subscriptions #27766 and #27828
- Orders #27752, #27776, #27788, #27798, and #27827
- Customer #471

Provider test history remains, but the disposable remote Paddle subscription was cancelled and no disposable remote billing remains active.

Preserved controls:

- Stripe subscription #4406 remains cancelled with one completed payment.
- Paddle subscription #7809 remains active with 41 completed payments and next payment `2026-08-16 15:07:52`.
- Its natural scheduled renewal order #27808 remains completed/paid for $60 through Paddle.

All temporary settings were restored byte-for-byte to their serialized SHA-256 baselines:

- `arraysubs_settings`: `ef5e20f24ae03fcab4967dbe713bb7c1fb2fb5667a3d01600e4c38ccf166b3ae`
- `woocommerce_stripe_settings`: `fb63cc191988edf4ba749d4f983e2004742823544b06101dc10f19cc7b7bfb87`
- `arraysubs_stripe_extras`: `55647a87f8d3ce8b75cde717b4923e53f61bbcc06ae47e03f81cf3f4c93cf289`
- `woocommerce_arraysubs_paddle_settings`: `bf12616dc7011f4fbe005e7b2f0e6f7af324a12ad8620a051554486d664a2a32`

The PHP debug log remained at the pre-final-regression baseline of 3,602 lines. No lint or PHPCS was run, per the workspace issue-fix workflow.

## Primary browser evidence

- `core-only-final/stripe-initial-order-received-final.png`
- `core-only-final/stripe-renewal-ordinary-pay-3ds.png`
- `core-only-final/stripe-renewal-completed.png`
- `core-only-final/stripe-decline-deduped-customer-portal.png`
- `core-only-final/paddle-mixed-order-received-final.png`
- `core-only-final/paddle-subscription-customer-view.png`
- `stripe-overdue-cancellation-fixed.png`
- `both-active-checkout-single-gateways.png`
- `final-gateway-health-after-cleanup.png`
