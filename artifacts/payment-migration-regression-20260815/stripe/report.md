# Stripe Payment Migration Regression QA

| Field | Value |
|---|---|
| Date | 2026-08-15 |
| Site | `https://mirror-help.arrayhash.com` |
| Browser sessions | `stripe-mig-qa-*` |
| Scope | Subscription-only, regular-only, mixed cart, SCA, and cancelled Stripe checkout |
| Plugins | ArraySubs 1.8.12; ArraySubsPro 1.1.3; WooCommerce 10.9.4; WooCommerce Stripe 10.8.4 |
| Browser automation | agent-browser 0.27.3, isolated Chrome sessions |
| Tester | `hawk-twig` |

## Secret-safe baseline

- Published products: Standard Weekly `#200` ($19.99/week), Basic Monthly `#197` ($29.99/month), Standard Tee `#447` ($15.00 one-time).
- Stripe: connected in test mode; Gateway Logs showed 13 active Stripe subscriptions.
- Webhook table before checkout: 363 rows, maximum internal row ID 1129.
- Mailpit: healthy. Checkout-specific mail baselines are recorded per scenario because other QA runs are concurrent.
- Each disposable customer began with zero orders and zero subscriptions.
- Existing `Private member store` rule intentionally blocks all products for customers without an active subscription. Checkout actions were paused until the coordinating agent could apply and later restore a narrowly scoped product exclusion with before/after hashes. No entitlement subscription was seeded.

## Disposable customers

| Scenario | Customer ID | Email | Baseline orders | Baseline subscriptions |
|---|---:|---|---:|---:|
| Subscription success | 463 | `stripe-mig-sub-20260815-hawktwig@example.test` | 0 | 0 |
| Regular product success | 464 | `stripe-mig-regular-20260815-hawktwig@example.test` | 0 | 0 |
| Mixed cart success | 465 | `stripe-mig-mixed-20260815-hawktwig@example.test` | 0 | 0 |
| SCA success | 466 | `stripe-mig-sca-20260815-hawktwig@example.test` | 0 | 0 |
| Cancel/return | 467 | `stripe-mig-cancel-20260815-hawktwig@example.test` | 0 | 0 |

## Results

### 1. Subscription-only Stripe checkout — PASS

- Customer `#463`; Standard Weekly `#200`; Visa test card ending `4242`.
- Browser order `#27280`: `processing`, USD `$19.99`, one line item (`Standard Weekly`, quantity 1), Stripe transaction shape `ch_…` (27 characters).
- Exactly one subscription was created: `#27296`, `arraysubs-active`, recurring `$19.99` every one week, next payment `2026-08-22 07:23:27`, completed payments `1`.
- Gateway binding is intact and secret-safe: Stripe status `active`; customer shape `cus_…`; payment method shape `pm_…`; Visa `4242`, expiry `12/2034`; last transaction shape `ch_…`.
- Exact pending actions: reminder `#23882` (`arraysubs-emails`), renewal invoice `#23883` (`arraysubs-billing`), renewal payment `#23884` (`arraysubs-renewals`). All use indexed subscription arguments.
- Checkout Store API POST returned HTTP 200; order-received document returned HTTP 200. Browser page errors were empty. WooCommerce emitted only its known dependency-detection warning, not a JavaScript exception.
- Two Stripe webhook records landed for this purchase: `charge.succeeded` and `payment_intent.succeeded`. Concurrent Paddle QA events were separated by gateway and timestamp.
- Mailpit captured four exact messages: admin new order, customer order received, customer subscription active, and admin new subscription.
- No checkout-time PHP fatal, warning, notice, deprecation, or uncaught error was found in the corresponding debug-log window.
- Evidence: `screenshots/01-subscription-cart-before.png`, `screenshots/01-subscription-checkout-before.png`, `screenshots/01-subscription-card-filled.png`, `screenshots/01-subscription-order-after.png`.

### 2. Regular-product-only Stripe checkout — PASS

- Customer `#464`; Standard Tee `#447`; Visa test card ending `4242`.
- Pre-submit controls proved this customer still had zero orders and zero subscriptions; webhook baseline was internal row ID `1157`; Mailpit baseline was message ID `5XiWjS36KdEfedBEAJB8uh`.
- Browser order `#27315`: `processing`, USD `$15.00`, exactly one Standard Tee line (quantity 1), payment method `stripe` / `Credit / Debit Card`, transaction shape `ch_…` (27 characters; SHA-256 `604931199969dc612322aeb3ee2afa9643dfe44caeaae16151a50c54d1ed2e48`).
- Post-checkout subscription count remained exactly zero. No ArraySubs Action Scheduler action referenced the order ID.
- Checkout Store API POST returned HTTP 200; the order-received document returned HTTP 200 and showed only Standard Tee at `$15.00`. Browser page errors were empty; the console contained only WooCommerce's known dependency-detection warning.
- Exactly two attributable Stripe webhook records landed: internal `#1158` `charge.succeeded` and `#1160` `payment_intent.succeeded`, both processed at `2026-08-15 07:30:52` UTC.
- Mailpit captured exactly the two expected messages after the scenario baseline: admin new order `#27315` and customer order received. It emitted no subscription mail.
- No PHP fatal, warning, notice, deprecation, uncaught exception, or checkout error appeared in the `07:30` UTC debug-log window.
- Evidence: `screenshots/02-regular-cart-before.png`, `screenshots/02-regular-card-viewport.png`, `screenshots/02-regular-card-filled.png`, `screenshots/02-regular-order-after.png`.

### 3. Mixed subscription and regular-product Stripe checkout — PASS

- Customer `#465`; Standard Weekly `#200` plus Standard Tee `#447`; Visa test card ending `4242`.
- Cart and checkout showed two distinct lines, a `$34.99` initial total, and recurring terms only on Standard Weekly. Pre-submit controls proved zero orders and zero subscriptions; webhook baseline was internal row ID `1182`; Mailpit baseline was message ID `6U6QUBEVXJZnw1cdkFXZ9R`.
- Browser order `#27346`: `processing`, USD `$34.99`, exactly two lines (Standard Weekly `$19.99` and Standard Tee `$15.00`, each quantity 1), payment method `stripe` / `Credit / Debit Card`, transaction shape `ch_…` (27 characters).
- Subscription cardinality was exactly one: `#27362`, `arraysubs-active`, linked only to product `#200` and parent order `#27346`; the one-time Tee did not create a second subscription.
- Gateway binding is intact and secret-safe: gateway `stripe`, status `active`, customer shape `cus_…` (18 characters), payment method shape `pm_…` (27 characters), Visa `4242`, expiry `12/2034`, and last transaction shape `ch_…` (27 characters). The order and subscription transaction hashes match.
- Recurrence is `$19.99` every one week; completed payments `1`; next payment `2026-08-22 07:37:39`. Exact pending actions are reminder `#23915` (`arraysubs-emails`, args `[27362,3]`), renewal invoice `#23916` (`arraysubs-billing`, args `[27362]`), and renewal payment `#23917` (`arraysubs-renewals`, args `[27362]`).
- Checkout Store API POST returned HTTP 200; the order-received document returned HTTP 200 and visibly showed both lines, total `$34.99`, and exactly one related active subscription. Browser page errors were empty; the console contained only WooCommerce's known dependency-detection warning.
- Exactly two attributable Stripe webhook records landed: internal `#1183` `charge.succeeded` and `#1185` `payment_intent.succeeded`. Concurrent Paddle rows `#1186+` were separated by gateway and timestamp.
- Mailpit captured exactly four Stripe-scenario messages before the concurrent Paddle mail: admin new order `#27346`, customer order received, customer subscription `#27362` active, and admin new subscription `#27362`.
- No PHP fatal, warning, notice, deprecation, uncaught exception, or checkout error appeared in the `07:37` UTC debug-log window.
- Evidence: `screenshots/03-mixed-cart-before.png`, `screenshots/03-mixed-checkout-before.png`, `screenshots/03-mixed-card-viewport.png`, `screenshots/03-mixed-card-filled.png`, `screenshots/03-mixed-order-after.png`.

### 4. Stripe 3DS/SCA checkout — PASS

- Customer `#466`; Basic Monthly `#197`; Stripe SCA test Visa ending `3184`.
- Pre-submit controls proved zero orders and zero subscriptions; webhook baseline was internal row ID `1205`; Mailpit baseline was message ID `0mftAcRdqWakyTvXuCsfxd`.
- Submitting the card visibly opened Stripe's `3D Secure 2 Test Page`. The browser viewport was expanded so both `FAIL` and `COMPLETE` controls were visible, then `COMPLETE` was clicked exactly once. The challenge, intermediate pending state, and final receipt were all captured.
- The initial authorization state was one pending order `#27387` and one pending subscription `#27403`; after challenge completion that same order became `processing` and that same subscription became `arraysubs-active`. No duplicate order or subscription was created.
- Final order `#27387`: USD `$29.99`, one Basic Monthly line (quantity 1), Stripe transaction shape `ch_…` (27 characters). Final subscription `#27403`: product `#197`, parent/order list `[27387]`, recurring `$29.99` every one month, completed payments `1`, next payment `2026-09-15 07:42:47`.
- Gateway binding is intact and secret-safe: Stripe status `active`; customer shape `cus_…` (18 characters); payment method shape `pm_…` (27 characters); Visa `3184`, expiry `12/2034`; last transaction shape `ch_…`. Order and subscription transaction hashes match.
- Exact pending actions are reminder `#23942` (`arraysubs-emails`, args `[27403,3]`), renewal invoice `#23943` (`arraysubs-billing`, args `[27403]`), and renewal payment `#23944` (`arraysubs-renewals`, args `[27403]`).
- The browser network showed HTTP 200 for the Store checkout POST, Stripe PaymentIntent confirmation, `3ds2/authenticate`, `3ds2/challenge_complete`, PaymentIntent retrieval, and the order-received document. Browser page errors were empty; the console contained only WooCommerce's known dependency-detection warning.
- The exact ArraySubs Stripe webhook chain was internal `#1206` `payment_intent.requires_action` at `07:42:51` UTC, `#1208` `charge.succeeded` at `07:44:58`, and `#1210` `payment_intent.succeeded` at `07:44:59`.
- Mailpit captured the requires-verification message during the pending state, then the four successful-order/subscription messages after authentication: admin new order, customer order received, customer subscription active, and admin new subscription.
- Subscription notes record the required-authentication state, official-webhook confirmation, status transition from Pending to Active, and final payment success. The requires-verification email/note describes this initial checkout as a renewal; this wording was recorded as an observation but not treated as a payment-flow failure.
- No PHP checkout/runtime error appeared in the `07:42`–`07:44` UTC window. Unrelated WP-CLI query errors from concurrent Paddle auditing at `07:45` were identified by their WP-CLI stack and excluded.
- Evidence: `screenshots/04-sca-checkout-before.png`, `screenshots/04-sca-card-filled.png`, `screenshots/04-sca-challenge.png`, `screenshots/04-sca-challenge-expanded.png`, `screenshots/04-sca-order-after.png`.

### 5. Incomplete/cancel-and-return Stripe checkout — PASS

- Customer `#467`; Standard Weekly `#200`; selected embedded Stripe Payment Element.
- This installed WooCommerce Stripe path is embedded on the store checkout rather than a separate Stripe-hosted Checkout Session, so there is no hosted `cancel_url` arrow to test. The equivalent negative path was tested in the actual architecture.
- With complete billing fields but no card data, clicking Place Order produced a visible inline `Your payment information is incomplete.` error and kept the customer on checkout. The customer then used the checkout Cart link and returned to a cart retaining exactly one Standard Weekly item and its `$19.99/week` terms.
- Pre/post state for customer `#467` remained exactly zero orders and zero subscriptions. Webhook table remained exactly 408 rows with maximum row ID `1210`; Mailpit latest message remained `5x7VTxLDfWEq86RFHf4UUa`. There was no PaymentMethod/PaymentIntent request and no order-received navigation.
- Browser page errors were empty; the console contained only WooCommerce's known dependency-detection warning. No PHP checkout/runtime error appeared in the `07:51`–`07:53` UTC window.
- Evidence: `screenshots/05-cancel-checkout-before.png`, `screenshots/05-cancel-validation.png`, `screenshots/05-cancel-return-cart.png`.

## Matrix conclusion

| Scenario | Order delta | Subscription delta | Result |
|---|---:|---:|---|
| Subscription-only, Visa 4242 | 1 (`#27280`) | 1 (`#27296`) | PASS |
| Regular-only, Visa 4242 | 1 (`#27315`) | 0 | PASS |
| Mixed cart, Visa 4242 | 1 (`#27346`) | exactly 1 (`#27362`) | PASS |
| SCA/3DS, Visa 3184 | exactly 1 (`#27387`) | exactly 1 (`#27403`) | PASS |
| Incomplete/cancel-return | 0 | 0 | PASS |

- After a full Gateway Logs reload, Stripe remained `Connected (Test Mode)`, both official Woo Stripe and ArraySubs secondary webhook URLs showed `Configured`, the latest SCA webhook trio was visible in order, and the dashboard subscription count moved from baseline `13` to `16`. That exact `+3` matches subscription-only, mixed-cart, and SCA successes; regular-only and cancel-return added none. Evidence: `screenshots/00-gateway-health-before.png` and `screenshots/99-gateway-health-after.png`.

## Issues

- Longstanding non-payment defect, not a migration regression: every tested paid Stripe subscription received two identical private notes saying `Stripe payment confirmed via official webhook` after the `charge.succeeded` / `payment_intent.succeeded` pair. It reproduced on subscriptions `#27296`, `#27362`, and `#27403`; payment, activation, binding, and scheduling were unaffected. The coordinating agent cross-checked historical records showing the behavior predates this migration and mirrored the full evidence into the formal QA issue board as issue `#1`.
- Observation only: the SCA requires-verification email and private note use renewal wording during an initial checkout. The successful 3DS state transition and final customer/order/subscription data were correct; this wording observation was not independently reproduced a second time in this run.
