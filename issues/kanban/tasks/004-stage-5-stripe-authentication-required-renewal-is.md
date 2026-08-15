---
id: 4
title: 'stage-5: Stripe authentication_required renewal is overwritten as payment failure'
status: closed
priority: high
created: 2026-08-15T12:19:18.581650633+02:00
updated: 2026-08-15T18:10:44.933673912+02:00
started: 2026-08-15T12:19:55.868409276+02:00
completed: 2026-08-15T18:10:44.9336731+02:00
tags:
    - stage-05
    - stripe
    - sca
    - renewal
    - email
    - payment-migration
class: standard
---

QA source:
- Progress task: qa/progress task #3, stage-5.
- QA plans: qa/stages/05-checkout/11-stripe-test-card-flow.md and qa/subscription-lifecycle-test/kanban/tasks/030-classic-checkout-with-stripe-sca-card-3ds-at.md.
- Found during the post-fix off-session counterexample for QA issue #3.

Affected records:
- Subscription #27614; initial parent order #27598; renewal order #27628.
- WordPress customer #470, stripe-sca-fix-20260815@example.test, role customer.
- Stripe PaymentIntent pi_3U4eeoJG5OzSNVs21MoRbaKI and failed charge ch_3U4eeoJG5OzSNVs21KHJSsNr.

Routes and context:
- Customer renewal URL: https://mirror-help.arrayhash.com/checkout/order-pay/27628/?pay_for_order=true&wc-stripe-confirmation=1 (order key omitted from issue text).
- Initial signup browser session: qa-fix-stripe-sca.
- Renewal was created and processed through the real core RenewalProcessor against Stripe test mode, then provider state and browser-pay URL were inspected.

Reproduction:
1. Complete initial signup with Stripe SCA card 4000 0027 6000 3184 and retain the saved pm_ binding.
2. Create the next canonical renewal invoice and run the normal automatic renewal processor.
3. Observe the synchronous Stripe response classify authentication_required and persist the payment-action intent/URL plus a pending renewal-verification outbox record.
4. Wait for Stripe payment_intent.payment_failed webhook delivery.
5. Inspect renewal order status, emails, and remote PaymentIntent.

Expected:
- Authentication required is a customer-action pending state, not an ordinary decline. The renewal order remains pending, the subscription remains active, retry attempts stay zero, a renewal-verification email/link is sent, and no payment-failed email is sent.

Actual:
- The synchronous path correctly left renewal order #27628 pending with action intent/url and pending outbox. Seconds later internal webhook #1226 payment_intent.payment_failed marked the order failed and sent Mailpit message 4RXJmlMToPtVCcVGR8IOaL, subject Payment failed for subscription #27614.
- Stripe authoritative state is requires_payment_method with last_payment_error.code and decline_code authentication_required, message This transaction requires authentication, exact renewal metadata order_id=27628/subscription_id=27614/renewal=true, and the same saved card ending 3184.

Proof:
- Local before/after output captured order pending -> failed, PI/action context intact, subscription still arraysubs-active, payment retry attempts empty, and outbox pending.
- Webhook table row #1226 is payment_intent.payment_failed at 2026-08-15 10:17:43 UTC.
- Mailpit ID 4RXJmlMToPtVCcVGR8IOaL is the incorrect admin payment-failed email.
- Remote PI has status requires_payment_method and last_payment_error.code=authentication_required.

Known scope/counterexamples:
- The same card initial on-session flow is correct after issue #3: requires_action -> one visible challenge -> charge.succeeded -> payment_intent.succeeded, with no renewal email.
- Generic Stripe 4242 initial payments and Paddle payments are unaffected.
- The synchronous off-session Stripe adapter already recognizes authentication_required; the defect is the later payment_failed webhook classification/handler overriding that pending customer-action state.


Verification-link browser reproduction (same issue):
- Mailpit then delivered exactly one correct verification email (ID 4QgvYOFn0EwfxV2rL9J1Z8), and the authenticated order-pay URL showed renewal #27628, total $29.99, Stripe, and saved Visa 3184.
- Clicking Pay for order once did not open 3DS. The same page returned with `Unable to process this payment`, and Woo Stripe marked the unpaid renewal failed again.
- Screenshot proof: qa/artifacts/payment-bug-fixes-20260815/issue-004/renewal-pay-failed.png.
- Woo Stripe log at 2026-08-15 10:23:51 UTC proves the exact cause: the existing PI was created with automatic payment methods and `allow_redirects=always`; its confirm request omitted `return_url`, so Stripe rejected confirmation before SCA. The response retained the same PI pi_3U4eeoJG5OzSNVs21MoRbaKI, same saved PM/card 3184, status requires_payment_method, and authentication_required. No second PaymentIntent or charge was created.
- Fix scope therefore includes both webhook classification and a backward-safe order-pay confirmation path for already-running authentication-required renewal PaymentIntents, plus narrowing newly-created off-session renewal intents to the saved method type.


Post-success cleanup finding and fix plan:
- The repaired browser flow completed the exact PI and renewed once, but the paid order/subscription retained `_arraysubs_payment_action_url`, `_arraysubs_payment_action_intent_id`, `_arraysubs_payment_action_required_at`, and `_arraysubs_pending_transaction_id`. This stale customer-action state must not survive a successful payment.
- Plan: add a strictly bound Stripe success cleanup helper in core and the Pro fallback; run it only for the canonical order/subscription and the exact successful PI, clear order and subscription action markers without touching a newer intent, and suppress/clear that PI outbox state.
- Re-run the completed-payment state audit, then execute a brand-new automatic renewal from PI creation through email link, confirmation, 3DS, webhook settlement, and cleanup to prove the return_url and new card-only intent behavior from start to finish.


Retry-race finding and fix plan:
- Final code review found that reconciliation preserves `requires_action` but treats `requires_payment_method + authentication_required` as terminal, even though the signed verification-link flow supports that exact Stripe state. A scheduled/manual renewal retry before the customer clicks could therefore clear the original PI and create a second one.
- Plan: classify the exact authentication-required PaymentIntent as customer-action pending during open-attempt reconciliation in Core and Pro, re-store/reuse the same PI/action context, and add a live repeat-process assertion before browser completion proving the order and PI IDs do not change.


Fresh-renewal email finding and fix plan:
- Fresh order #27663 / PI pi_3U4fHeJG5OzSNVs20Z3YhyXT proves the new PI is card-only and the ArraySubs webhook handler restores the final order to pending, but Woo Stripe emitted admin failed-order email 3RU5rqnNfFzHgTCwyGR4Hs during its earlier pending->failed transition. This is false customer-action state and violates the no-failure-email acceptance condition.
- Plan: inspect the official webhook hook order and add an exact, fail-closed compatibility guard for canonical ArraySubs Stripe renewal PIs whose immutable error is authentication_required. Prevent or suppress only that false failed-order transition/email, while leaving genuine declines and ordinary orders untouched; then create another fresh renewal and assert zero failed-order/payment-failed messages.


Fresh card-only confirmation finding and fix plan:
- Browser click on order #27663 did not reuse action PI pi_3U4fHeJG5OzSNVs20Z3YhyXT; Woo Stripe created replacement PI pi_3U4fOGJG5OzSNVs21eNBuHLc. The browser was stopped before completing either; both are unpaid. This violates exact-PI reuse even though the old broad-PI compatibility case happened to reuse its intent.
- Plan: inspect Woo Stripe deferred-intent selection and move the customer action flow to an explicit, strictly-bound confirmation/resume of `_arraysubs_payment_action_intent_id`, never allowing Woo Stripe to create a PI for this route. Preserve official return URL/Stripe.js continuation behavior, cancel the unused replacement test PI after proving it is uncharged, and repeat click/retry assertions against one exact PI.


Resolution verification — final:
- Core and the dormant Pro fallback now preserve authentication_required as customer-action pending, keep the canonical renewal and exact PaymentIntent bound, and do not let a later payment_failed delivery overwrite it as an ordinary decline.
- Every exact-intent confirmation supplies a validated return URL. The customer completes the action through the ordinary signed WooCommerce order-pay page; a fresh authoritative intent/payment-method check runs immediately before confirmation.
- Success cleanup is exact-intent scoped and removes stale order/subscription action markers and the matching outbox state without touching a newer attempt.
- A new real core-only Stripe mixed checkout completed one initial 3DS challenge. Its automatic renewal then produced the normal customer-action link, displayed a second real 3DS challenge, and completed from the ordinary order-pay route using the same bound intent.
- Before customer completion, repeated processing preserved the same renewal order and intent. After completion there was exactly one paid/captured provider charge, one completed renewal payment, no remaining payment-action context, and no failure/retry state.
- The fresh customer-action attempt sent the verification path rather than payment-failed mail. Genuine decline behavior was separately retained and verified under issue #5.
- Browser proof: qa/artifacts/payment-bug-fixes-20260815/core-only-final/stripe-renewal-ordinary-pay-page.png, stripe-renewal-ordinary-pay-3ds.png, and stripe-renewal-completed.png.
- The Woo Stripe logs contain only the two historical missing-return_url errors from the pre-fix fixture and no new error from any post-fix initial or renewal confirmation.
- Closed after live browser, provider, webhook, order/subscription, Mailpit, and scheduler verification.
