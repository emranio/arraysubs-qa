# Paddle Payment Migration QA Report

| Field | Value |
|---|---|
| Date | 2026-08-15 |
| Site | Staging (`mirror-help.arrayhash.com`) |
| Browser | Headless Chrome 149.0.0.0 on Linux x86_64 via `agent-browser` 0.27.3 |
| Sessions | `paddle-mig-qa-*` isolated customer sessions |
| Scope | Paddle sandbox subscription-only, regular-only, mixed-cart, and cancelled-overlay checkout |
| Plugins | ArraySubs 1.8.12; ArraySubsPro 1.1.3 |

## Verdict

**PASS for the exercised Paddle migration scope.** Real Paddle sandbox checkout completed successfully for a regular product, a subscription product, and a mixed cart. The subscription flows produced exactly one active ArraySubs subscription each, durable Paddle bindings, one authoritative pending invoice action and renewal action, correct customer-portal state, and the expected emails. Returning from the Paddle overlay left the unpaid order and subscription pending without creating a remote subscription, scheduled renewal, or email.

No ArraySubs or ArraySubsPro source files were changed during this QA run.

## Baseline

- Paddle gateway enabled in test mode with API key, client token, seller ID, and webhook secret configured (values redacted).
- Mixed carts enabled; multiple subscriptions disabled; automatic customer account creation enabled.
- Mailpit latest-message marker before QA: `4NGz5mAhfB83ELxbV6TVsp`.
- Existing pending Paddle-specific actions: two global plan-switch sweeps; no per-fixture Paddle actions.
- Existing published products selected for QA: `SLT Paddle Daily` (#12112, $11/day) and `Standard Tee` (#447, $15 one-time).
- A sitewide member-only purchasing rule applies to all products. The root QA run temporarily excluded only products #200, #197, #12112, and #447 so disposable customers could exercise checkout without manufacturing an unrelated entitlement. The root run owns restoration and its pre/post hashes; this Paddle worker did not mutate shared settings.
- Renewal sync was temporarily disabled by the root QA run so the first charge remained the full product price. The Paddle worker did not mutate that setting.
- QA guidance consulted: stage 05 checkout tasks 01, 02, 12, and 14; stage 04 mixed-cart rule coverage; stage 06 lifecycle and portal coverage. The formal stage notes normally exclude Paddle, but migration task #134 explicitly expanded the gateway scope.

## Results

| Flow | Disposable customer | Local result | Remote Paddle result | Evidence summary |
|---|---|---|---|---|
| Regular-only | #460, `paddle_mig_regular_070215@example.test` | Order #27275 is paid/processing for $15, one #447 line, transaction `txn_01m024nzx82yyq6sfswc4zkwae`; zero subscriptions | Transaction completed; no subscription | Order-received page showed Standard Tee x1 and $15; admin/customer order emails captured; +10 webhooks |
| Subscription-only | #459, `paddle_mig_sub_070215@example.test` | Order #27307 is paid/completed for $11; subscription #27309 is active, one completed payment, $11/day, next payment `2026-08-16 07:32:25` | Transaction `txn_01m024z0hy94xq2f6ejm9292jp` completed; subscription `sub_01m025c6cjbrmq4n30g6c8g2en` active, next billed `2026-08-16T07:32:25.69038Z` | Exactly one subscription; Paddle/Visa 4242 displayed in portal; customer/admin subscription and order emails captured; +12 webhooks |
| Mixed cart | #461, `paddle_mig_mixed_070215@example.test` | Order #27339 is paid/processing for $26 with #12112 ($11) and #447 ($15); exactly one active subscription #27341, one completed payment, $11/day, next payment `2026-08-16 07:38:24` | Transaction `txn_01m025kjtqkg6yy5jnsfcnb9mp` completed; subscription `sub_01m025q44zfj9v7nzh7c0jeqc3` active, next billed `2026-08-16T07:38:24.351659Z` | Paddle consent correctly stated $26 now then $11/day; regular item did not create a second subscription; customer/admin subscription and order emails captured; +12 webhooks |
| Return/cancel overlay | #462, `paddle_mig_cancel_070215@example.test` | Order #27381 remains pending/unpaid for $11; subscription #27383 remains pending with zero completed payments and no Paddle binding | Transaction `txn_01m025xa86q5mdqynwfx3rdv0e` is draft with no customer or subscription; no remote subscription exists | `Return to ArrayHash` restored an enabled checkout with the cart retained; no scheduled action, transaction ID on the Woo order, or email; +2 setup webhooks only |

### Checkout and browser observations

- Product, cart, Checkout Block, Paddle overlay, and order-received pages were driven in isolated real browser sessions. Paddle assets `paddle-checkout.js` and `paddle-checkout-blocks.js` loaded with HTTP 200 responses.
- The official Paddle sandbox non-3DS Visa test card was used. The subscription-only consent stated $11 now and $11/day from 16 August; the mixed consent stated $26 now and $11/day thereafter.
- The regular checkout created no subscription. The subscription checkout created exactly one subscription. The mixed checkout created exactly one subscription for #12112; #447 remained a normal order line.
- Expected order statuses were preserved: the subscription-only virtual order completed, while orders containing the regular physical product remained processing.
- No failed Paddle checkout API request was observed. Transaction checkout/pay calls and their preflights returned 2xx/204 responses.

### Bindings and scheduler invariants

- Subscription #27309 stores `arraysubs_paddle`, remote subscription `sub_01m025c6cjbrmq4n30g6c8g2en`, remote customer `ctm_01m025b8rfwd3fxy1w45a8t1n9`, Paddle price `pri_01kzx9086mp8dxrdnvmbngf9s4`, transaction `txn_01m024z0hy94xq2f6ejm9292jp`, gateway status active, and Visa ending 4242.
- Subscription #27341 stores `arraysubs_paddle`, remote subscription `sub_01m025q44zfj9v7nzh7c0jeqc3`, remote customer `ctm_01m025paxb06fpyfwf5w8cgvg2`, the same Paddle price, transaction `txn_01m025kjtqkg6yy5jnsfcnb9mp`, and gateway status active.
- Subscription #27309 has exactly one authoritative pending pair: action #23904 `arraysubs_generate_renewal_invoice` at `2026-08-16 05:44:33` GMT and #23905 `arraysubs_process_renewal` at `2026-08-16 11:44:33` GMT, both with indexed args `[27309]`.
- Subscription #27341 has exactly one authoritative pending pair: action #23925 `arraysubs_generate_renewal_invoice` at `2026-08-16 07:05:02` GMT and #23926 `arraysubs_process_renewal` at `2026-08-16 13:05:02` GMT, both with indexed args `[27341]`.
- Superseded reschedule attempts are canceled, not concurrently pending. Subscription #27383 has no renewal actions.

### Webhooks and email

- Paddle webhook baseline: 106 rows, maximum ID 1115. Final: 142 rows, maximum ID 1205. The exact +36 delta was: `subscription.created` 2, `transaction.created` 4, `transaction.updated` 21, `transaction.ready` 3, `transaction.paid` 3, and `transaction.completed` 3.
- Per-flow deltas were +10 regular, +12 subscription, +12 mixed, and +2 return/cancel. The return/cancel delta contained only one `transaction.created` and one `transaction.updated` event.
- Mailpit captured two order emails for #27275; four order/subscription emails for #27307/#27309; four order/subscription emails for #27339/#27341; and no message for customer #462, order #27381, or subscription #27383.

### Customer portal

- Customer #459 saw exactly subscription #27309 as Active with $11 every day, the next-payment date, Paddle, Visa ending 4242, Update method, Change Plan, Cancel, and related completed order #27307.
- Customer #461 saw exactly subscription #27341 as Active with $11 every day and the expected next-payment date.

### Runtime diagnostics

- No checkout-runtime PHP warning, fatal, or database error was found in `debug.log`. New log entries during the window were attributable to WP-CLI diagnostic probes (including deliberately corrected schema/query probes), not browser requests or gateway handlers.
- WooCommerce emitted its dependency-detection warning that an inline/unknown script accessed `wc.wcBlocksData`. No source URL was supplied, the warning was gateway-independent, and it caused no visible or transactional failure in this matrix.

## Browser-generated `AbortError` verdict

Chrome's protocol error buffer reported `AbortError: Transition was skipped` with `url: null`, line 0, column 0, and no console stack. It was reproducible after clearing the error buffer and selecting **Credit / Debit Card** and again after selecting the core **Direct bank transfer** gateway; it was therefore not specific to Paddle or an ArraySubs action. The exact string is absent from ArraySubs, ArraySubsPro, and WooCommerce source, while the browser/WordPress stack uses the View Transition API. No network failure, visual failure, lost cart, duplicate active subscription, payment-state error, or inaccessible control accompanied it.

Classification: non-product browser/View-Transition rejection during Checkout Block payment-method rerender. No QA issue was filed because it is gateway-agnostic automation/browser noise with no user impact and no attributable ArraySubs/ArraySubsPro source.

## Post-audit remote cleanup

After the root audit completed, the two active sandbox fixtures were cancelled **immediately** through the real ArraySubs admin subscription UI. No subscription, order, or user was deleted.

Cleanup baseline at `2026-08-15T08:03:11Z`:

- Mailpit latest ID: `5x7VTxLDfWEq86RFHf4UUa`.
- Paddle webhook table: 142 rows, maximum ID 1205.
- #27309 and #27341 were locally active, remotely active, had no scheduled Paddle change, and each had one pending invoice action plus one pending renewal action.
- The 407 non-target ArraySubs subscription-status records hashed to `801bc12a2e83b671dd15c8eb0a7b91c66545f10f2d69477c6e0faf70f4d87775`.

Immediate cancellation outcomes:

| Local subscription | Admin REST/UI | Local result | Remote Paddle result | Scheduler result | Webhook result | Email result |
|---|---|---|---|---|---|---|
| #27309 | `POST /arraysubs/v1/subscriptions/27309/cancel` returned 200; **Cancel Immediately** selected | `arraysubs-cancelled`; cancellation type `immediate`, reason `other`, detail `Paddle sandbox QA fixture cleanup` | Exact ID `sub_01m025c6cjbrmq4n30g6c8g2en` is `canceled`; `canceled_at` `2026-08-15T08:04:18.933Z`, no next bill or scheduled change | Actions #23904 and #23905 changed from pending to canceled; zero pending actions remain for `[27309]` | #1212 `subscription.updated` and #1213 `subscription.canceled`, both processed | Customer mail `5fAgmslujN5WKjdDdr8bZj`; admin mail `4rwVZEMaT21MtatU4K2cvT` |
| #27341 | `POST /arraysubs/v1/subscriptions/27341/cancel` returned 200; **Cancel Immediately** selected | `arraysubs-cancelled`; cancellation type `immediate`, reason `other`, same QA detail | Exact ID `sub_01m025q44zfj9v7nzh7c0jeqc3` is `canceled`; `canceled_at` `2026-08-15T08:05:16.622Z`, no next bill or scheduled change | Actions #23925 and #23926 changed from pending to canceled; zero pending actions remain for `[27341]` | #1214 `subscription.updated` and #1215 `subscription.canceled`, both processed | Customer mail `0rjZyUpuGwC1rxApp2WB1W`; admin mail `3kznwr64MOSoiN4hDSj2Y2` |

Post-cleanup controls:

- Paddle webhook table finished at 146 rows, maximum ID 1215: exactly +4 rows, consisting of two `subscription.updated` and two `subscription.canceled` events.
- The same 407 non-target subscription-status records retained the exact pre-cleanup hash. No unrelated subscription status changed.
- Both target detail pages visibly showed `CANCELLED`, an immediate end date, cancellation details, gateway synchronization notes, and the expected customer/admin email note.
- Browser error buffer was empty; console contained only JQMIGRATE logs; both cancellation requests returned HTTP 200. No cancellation-window browser request produced a PHP runtime warning, fatal, or database error.
- Mailpit's final latest ID was the #27341 admin cancellation message `3kznwr64MOSoiN4hDSj2Y2`.
- Regular order #27275 still has no remote subscription. Pending return fixture #27381/#27383 and its draft transaction `txn_01m025xa86q5mdqynwfx3rdv0e` were not mutated and require no subscription cancellation.
- Local fixture teardown remains owned by the root run.

## Artifacts

- Regular: `screenshots/regular-01-product.png` through `regular-04-order-received.png`.
- Subscription: `screenshots/subscription-01-product.png` through `subscription-05-customer-portal.png`.
- Mixed: `screenshots/mixed-01-cart.png`, `issue-001-step-1-mixed-checkout.png`, `issue-001-step-2-paddle-overlay.png`, and `issue-001-result-mixed-order-received.png`. The `issue-001-*` filenames are capture-sequence names only; no product issue was confirmed.
- Return/cancel: `screenshots/cancel-01-overlay-open.png` and `cancel-02-returned-to-checkout.png`.
- Post-audit cleanup: `screenshots/cleanup-01-sub27309-active.png` through `cleanup-03-sub27309-cancelled.png`, and `cleanup-04-sub27341-active.png` through `cleanup-06-sub27341-cancelled.png`.
- Video recording was attempted for the transition-noise investigation, but this host has no `ffmpeg`; no video artifact is claimed. The screenshots, browser snapshots, network log, WP-CLI state, Paddle API state, webhook table, scheduler table, and Mailpit records provide the verification evidence.

## Issues

No confirmed Paddle/ArraySubs product issue was found in this matrix.
